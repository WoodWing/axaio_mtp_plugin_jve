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
 *
 * The restproxyindex.php supports the following URL parameters:
 * - ticket:    A valid session ticket that was obtained through a LogOn service call (e.g. see SCEnterprise.wsdl).
 * - ww-app:    The client application name that was provided in the LogOn service request. This parameter could used
 *              instead of ticket to have stable URLs and so use the web browser's cache. When using this parameter and
 *              the client does not run in a web browser it should round-trip web cookies by itself.
 * - objectid:  The ID of the workflow object in Enterprise. The object may reside in workflow, history or trash can.
 * - service:   The Elvis REST API call e.g. to do download a native, preview or crop file from Elvis. The URL must be
 *              URL encoded and relative (the Elvis host/ip must be excluded). The Elvis asset id should be indicated
 *              with a placeholder %assetid% which is resolved by the proxy from the object id.
 *
 * Example request:
 *    http://localhost/Enterprise/config/plugins/Elvis/restproxyindex.php?ww-app=Content%20Station&service=preview%2F%25assetid%25&objectid=123
 *
 * The following HTTP codes may be returned:
 * - HTTP 200: The file is found and is streamed back to caller.
 * - HTTP 400: Bad HTTP parameters provided by caller. See above for required parameters.
 * - HTTP 401: When ticket is no longer valid. This should be detected by the client to do a re-login.
 * - HTTP 403: The user has no Read access to the invoked object in Enterprise or Elvis.
 * - HTTP 404: The object could not be found in Enterprise or Elvis.
 * - HTTP 405: Bad HTTP method requested by caller. Only GET, POST and OPTIONS are supported.
 * - HTTP 500: Unexpected server error.*
 */

$index = new Elvis_RestProxyIndex();
$index->handle();

class Elvis_RestProxyIndex
{
	/** @var array $httpParams HTTP input parameters (taken from URL or Cookie). */
	private $httpParams;

	/** @var MetaData $objectMetaData Some essential properties resolved for the invoked Enterprise object. */
	private $objectMetaData;

	/** @var string $elvisAssetId The DocumentID of the invoked shadow object, which equals the Elvis asset id. */
	private $elvisAssetId;

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
		} elseif( isset( $requestParams['ww-app'] ) ) {
			// Support cookie enabled sessions. When the client has no ticket provided in the URL params, try to grab the ticket
			// from the HTTP cookies. This is to support JSON clients that run multiple web applications which need to share the
			// same ticket. Client side this can be implemented by simply letting the web browser round-trip cookies. [EN-88910]
			$this->httpParams['ticket'] = BizSession::getTicketForClientIdentifier();
		}

		// Accept the service param.
		if( isset( $requestParams['service'] ) ) {
			$this->httpParams['service'] = $requestParams['service'];
		}

		// Accept the objectid param.
		if( isset( $requestParams['objectid'] ) ) {
			$this->httpParams['objectid'] = intval( $requestParams['objectid'] );
		}

		// Log the incoming parameters for debugging purposes.
		if( LogHandler::debugMode() ) {
			$msg = 'Incoming HTTP params:'.PHP_EOL;
			foreach( $this->httpParams as $key => $value ) {
				$msg .= "- {$key} = '{$value}'".PHP_EOL;
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
				$this->checkObjectReadAccess();
				$this->resolveElvisAssetId();
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
		if( !array_key_exists( 'ticket', $this->httpParams ) ) {
			$message = 'Please specify a "ticket" param at the URL, or provide web cookies and set the "ww-app" param.';
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
	 * Check if the session user has Read access to the invoked object.
	 *
	 * @throws Elvis_RestProxyIndex_HttpException
	 */
	private function checkObjectReadAccess()
	{
		// Validate the objectid param.
		if( !isset($this->httpParams['objectid']) || !$this->httpParams['objectid'] ) {
			$message = 'Please specify a "objectid" param at the URL.';
			throw new Elvis_RestProxyIndex_HttpException( $message, 400 );
		}
		$objectId = $this->httpParams['objectid'];

		// Get some object properties required by BizAccess::checkRightsForObjectProps().
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$metaDatasPerObject = DBObject::getMultipleObjectsProperties( array( $objectId ) );

		// Bail out when object does not exist in DB.
		if( !array_key_exists( $objectId, $metaDatasPerObject ) ) {
			$message = 'The object could not be found.';
			throw new Elvis_RestProxyIndex_HttpException( $message, 404 );
		}
		$this->objectMetaData = $metaDatasPerObject[ $objectId ];

		// Resolve the overrule issue the object is assigned to. For normal issues, leave it zero.
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$overruleIssue = DBIssue::getOverruleIssueIdsFromObjectIds( array( $objectId ) );
		$issueId = isset( $overruleIssue[$objectId] ) ? $overruleIssue[$objectId] : 0;

		// Check if user has Read access to the object.
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		if( !BizAccess::checkRightsForMetaDataAndIssue( BizSession::getShortUserName(), 'R',
			BizAccess::DONT_THROW_ON_DENIED, $this->objectMetaData, $issueId ) ) {
			$message = 'The user has no Read access to the object.';
			throw new Elvis_RestProxyIndex_HttpException( $message, 403 );
		}
	}

	/**
	 * Resolve the Elvis asset id from the invoked Enterprise object id.
	 *
	 * @throws Elvis_RestProxyIndex_HttpException
	 */
	private function resolveElvisAssetId()
	{
		require_once __DIR__.'/config.php'; // ELVIS_CONTENTSOURCEID

		$contentSource = $this->objectMetaData->BasicMetaData->ContentSource;
		$documentId = $this->objectMetaData->BasicMetaData->DocumentID;
		if( !$contentSource || !$documentId || $contentSource != ELVIS_CONTENTSOURCEID ||
			!BizContentSource::isShadowObjectBasedOnProps( $contentSource, $documentId ) ) {
			$message = 'The object is not an Elvis shadow object.';
			throw new Elvis_RestProxyIndex_HttpException( $message, 404 );
		}
		$this->elvisAssetId = $documentId;
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
		$service = str_replace( '%assetid%', $this->elvisAssetId, $this->httpParams['service'] );
		require_once __DIR__.'/logic/ElvisRESTClient.php';
		$client = new ElvisRESTClient();
		$client->proxy( $service );
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
