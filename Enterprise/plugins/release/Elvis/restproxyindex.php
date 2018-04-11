<?php

/**
 * @package    Enterprise
 * @subpackage FileStore service
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Proxy server that accepts an Elvis request from an Enterprise client and pass it on to Elvis Server.
 *
 * The original client request must be authorized for Enterprise. The Elvis REST service request must be provided
 * as a separate HTTP parameter. Then this module proxies the request to the Elvis Server for which it applies
 * the Elvis authorization. The response body and headers are streamed back to the waiting Enterprise client application.
 *
 * The proxy is introduced to support image cropping in Content Station with help of the Elvis REST API.
 */

$index = new Elvis_RestProxyIndex();
$index->handle();

class Elvis_RestProxyIndex
{
	/** @var array $httpParams HTTP input parameters (taken from URL or Cookie). */
	private $httpParams;

	/**
	 * Dispatch the incoming HTTP request.
	 */
	public function handle()
	{
		$this->includeEnterpriseServerConfig();

		$httpMethod = $_SERVER['REQUEST_METHOD'];
		LogHandler::Log( 'ElvisRestProxyIndex', 'CONTEXT', "Incoming HTTP {$httpMethod} request." );
		PerformanceProfiler::startProfile( 'ElvisRestProxyIndex', 1 );

		try {
			try {
				$this->allowCrossHeaders();
				$this->parseHttpParams();
				$this->dispatchRequest( $httpMethod );
			} catch( BizException $e ) {
				throw Elvis_RestProxyIndex_HttpException::createFromBizException( $e );
			}
		} catch( Elvis_RestProxyIndex_HttpException $e ) {
			// nothing to do here; the error is handled in the constructor of the exception already
		}

		PerformanceProfiler::stopProfile( 'ElvisRestProxyIndex', 1 );
		LogHandler::Log( 'ElvisRestProxyIndex', 'CONTEXT', "Outgoing HTTP {$httpMethod} response." );
	}

	/**
	 * Include core basics and log the footprint of Enterprise Server (= startup time).
	 */
	private function includeEnterpriseServerConfig()
	{
		$beforeInclude = microtime( true );
		if( file_exists( __DIR__.'/../../config.php' ) ) {
			require_once '../../config.php';
		} else { // fall back at symbolic link to VCS source location of server plug-in
			require_once '../../../Enterprise/config/config.php';
		}
		$footprint = sprintf( '%03d', round( ( microtime( true ) - $beforeInclude ) * 1000 ) );
		LogHandler::Log( 'ElvisRestProxyIndex', 'CONTEXT', 'Enterprise Server footprint: '.$footprint.'ms (= startup time).' );
	}

	/**
	 * Add Cross Origin headers needed by Javascript applications
	 */
	private function allowCrossHeaders()
	{
		require_once BASEDIR.'/server/utils/CrossOriginHeaderUtil.class.php';
		WW_Utils_CrossOriginHeaderUtil::addCrossOriginHeaders();
	}

	/**
	 * Validate the HTTP request params and populate $this->httpParams.
	 *
	 * @throws Elvis_RestProxyIndex_HttpException
	 */
	private function parseHttpParams()
	{
		require_once BASEDIR.'/server/utils/HttpRequest.class.php';
		$requestParams = WW_Utils_HttpRequest::getHttpParams( 'GP' ); // GET and POST only, no cookies

		$this->httpParams = array(
			'ticket' => null,
			'service' => null
		);

		// Accept the ticket param.
		if( isset( $requestParams['ticket'] ) ) {
			$this->httpParams['ticket'] = $requestParams['ticket'];
		} else {
			// Support cookie enabled sessions. When the client has no ticket provided in the URL params, try to grab the ticket
			// from the HTTP cookies. This is to support JSON clients that run multiple web applications which need to share the
			// same ticket. Client side this can be implemented by simply letting the web browser round-trip cookies. [EN-88910]
			$this->httpParams['ticket'] = BizSession::getTicketForClientIdentifier();
		}

		// Accept the service param.
		if( isset( $requestParams['service'] ) ) {
			$this->httpParams['service'] = $requestParams['service'];
		}

		// Log the incoming parameters for debugging purposes.
		if( LogHandler::debugMode() ) {
			$msg = 'Incoming HTTP params: ';
			foreach( $this->httpParams as $key => $value ) {
				$msg .= "- {$key} = '{$value}' ".PHP_EOL;
			}
			LogHandler::Log( 'ElvisRestProxyIndex', 'DEBUG', $msg );
		}
	}

	/**
	 * Dispatch (proxy) the incoming REST service request to Elvis Server.
	 *
	 * @param string $httpMethod
	 * @throws Elvis_RestProxyIndex_HttpException
	 * @throws BizException
	 */
	private function dispatchRequest( $httpMethod )
	{
		// The OPTIONS call is send by a web browser as a pre-flight for a CORS request.
		// This request doesn't send or receive any information. There is no need to validate the ticket,
		// and when the OPTIONS calls returns an error the error can't be validated within an application.
		// This is a restriction by web browsers.
		switch( $httpMethod ) {
			case 'OPTIONS':
				throw new Elvis_RestProxyIndex_HttpException( '', 200 );
			case 'GET':
			case 'POST':
				$this->preparePhpForStreaming();
				$this->validateTicketAndStartSession();
				$this->proxyRequestToElvisServer();
				break;
			default:
				$message = 'Unknown HTTP method "'.$_SERVER['REQUEST_METHOD'].'" is used which is not supported.';
				throw new Elvis_RestProxyIndex_HttpException( $message, 405 );
		}
	}

	/**
	 * Set global options for the PHP environment to allow streaming without interference.
	 */
	private function preparePhpForStreaming()
	{
		// Abort after one hour download without streaming activity.
		set_time_limit( 3600 );

		// The following option could corrupt archive files, so disable it
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#49671
		ini_set( "zlib.output_compression", "Off" );

		// This lets a user download a file while still being able to browse your site.
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#48244
		session_write_close();
	}

	/**
	 * Check if the ticket (provided by client) is valid and starts an Enterprise Server session.
	 *
	 * @throws BizException
	 */
	private function validateTicketAndStartSession()
	{
		if( !$this->httpParams['ticket'] ) {
			$message = 'Please specify "ticket" param at URL, or provide it as a web cookie and set the "ww-app" param.';
			throw new Elvis_RestProxyIndex_HttpException( $message, 400 );
		}
		// Explicitly request NOT to update ticket expiration date to save time (since DB updates are expensive).
		// We assume this is settled through regular web services which are called anyway such as GetObjects.
		$user = BizSession::checkTicket( $this->httpParams['ticket'], 'ElvisRestProxyIndex', false );
		BizSession::setServiceName( 'ElvisRestProxyIndex' );
		BizSession::startSession( $this->httpParams['ticket'] );
		BizSession::setTicketCookieForClientIdentifier( $this->httpParams['ticket'] );
	}

	/**
	 * Dispatch (proxy) the incoming REST service request to Elvis Server.
	 *
	 * @throws BizException
	 */
	private function proxyRequestToElvisServer()
	{
		if( !$this->httpParams['service'] ) {
			$message = 'Please specify "service" param at URL.';
			throw new Elvis_RestProxyIndex_HttpException( $message, 400 );
		}
		require_once __DIR__.'/logic/ElvisRESTClient.php';
		$client = new ElvisRESTClient();
		$client->proxy( $this->httpParams['service'] );
	}
}

/**
 * Exception for the Elvis_RestProxyIndex class.
 *
 * When Enterprise Server throws a BizException, this class can be used to compose an HTTP error from it.
 */
class Elvis_RestProxyIndex_HttpException extends Exception
{
	/**
	 * @inheritdoc
	 */
	public function __construct( $message = "", $code = 0, Exception $previous = null )
	{
		$response = new Zend\Http\Response();
		$response->setStatusCode( $code );
		$reasonPhrase = $response->getReasonPhrase();

		$statusMessage = "{$code} {$reasonPhrase}";
		if( $message ) { // if there are more lines, take first one only this only one can be sent through HTTP
			if( strpos( $message, "\n" ) !== false ) {
				$msgLines = explode( "\n", $message );
				$message = reset($msgLines);
			}
			// Add message to status; for apps that can not reach message body (like Flex)
			$statusMessage .= " - {$message}";
		}

		header( "HTTP/1.1 {$code} {$reasonPhrase}" );
		header( "Status: {$statusMessage}" );

		LogHandler::Log( __CLASS__, $response->isServerError() ? 'ERROR' : 'INFO', $statusMessage );
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Composes a new HTTP exception from a given BizException.
	 *
	 * @param BizException $e
	 * @return Elvis_RestProxyIndex_HttpException
	 */
	static public function createFromBizException( BizException $e )
	{
		$message = $e->getMessage().' '.$e->getDetail();
		$errorMap = array(
			'S1002' => 403, // ERR_AUTHORIZATION
			'S1029' => 404, // ERR_NOTFOUND
			'S1036' => 404, // ERR_NO_SUBJECTS_FOUND
			'S1080' => 404, // ERR_NO_CONTENTSOURCE
			'S1043' => 401, // ERR_TICKET
		);
		$sCode = $e->getErrorCode();
		$code = array_key_exists( $sCode, $errorMap ) ? $errorMap[$sCode] : 500;
		return new Elvis_RestProxyIndex_HttpException( $message, $code );
	}
}