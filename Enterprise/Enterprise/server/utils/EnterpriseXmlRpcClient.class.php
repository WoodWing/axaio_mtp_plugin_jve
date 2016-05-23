<?php
/**
 * @package 	Enterprise
 * @subpackage 	Utils
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * XmlRpc client for Enterprise.
 */
class WW_Utils_XmlRpcClient
{
	/**
	 * @var string|null
	 */
	public $url = null;

	/**
	 * The Xml RPC client.
	 * @var null|Zend_XmlRpc_Client
	 */
	public $rpcClient = null;

	/**
	 * Default constructor.
	 *
	 * Creates and sets the rpcClient.
	 *
	 * @param string $url
	 * @param Zend_Http_Client $httpClient
	 */
	public function __construct( $url, $httpClient = null )
	{
		$this->url = $url;
		$this->setXmlRpcClient($this->url, $httpClient);
	}

	/**
	 * Creates an XmlRpcClient.
	 *
	 * @param $url
	 * @param Zend_Http_Client $httpClient
	 */
	public function setXmlRpcClient( $url, $httpClient = null  )
	{
		require_once 'Zend/XmlRpc/Client.php';
		$this->rpcClient = new Zend_XmlRpc_Client( $url, $httpClient );
	}

	/**
	 * Sends a message to a XML-RPC server using the Zend_XmlRpc classes.
	 *
	 * @throws BizException Throws a BizException in case of errors.
	 *
	 * @param string $action
	 * @param mixed $params
	 * @return mixed - If answer is recieved the object will be returned otherwise null is returned.
	 */
	public function callRpcService( $action, $params )
	{
		// Leave a trail in the server log before calling Drupal
		$debugMode = LogHandler::debugMode();
		$area = 'EnterpriseXmlRpc';
		if( $debugMode ) {
			LogHandler::Log( $area, 'DEBUG', 'Calling "'.$action. '" service request with params: '.print_r($params,true) );
		}

		// Call the service (using RPC)
		PerformanceProfiler::startProfile( $area . '- '.$action, 3 );
		try {
			$retVal = $this->rpcClient->call( $action, $params );
		} catch( Exception $e ) {
			$e = $e; // Keep analyzer happy.
			$retVal = null; // Keep analyzer happy.
		}
		PerformanceProfiler::stopProfile( $area . '- '.$action, 3 );

		// Log request and response (or fault) as XML
		if( $debugMode ) { // check here since saveXML() calls below are expensive
			LogHandler::logService( $action, $this->rpcClient->getLastRequest()->saveXML(), true, 'xmlrpc', 'xml' );
			$lastResponse = $this->rpcClient->getLastResponse();
			if( $lastResponse ) {
				if( $lastResponse->isFault() ) {
					LogHandler::logService( $action, $lastResponse->getFault()->saveXML(), null, 'xmlrpc', 'xml' );
				} else {
					LogHandler::logService( $action, $lastResponse->saveXML(), false, 'xmlrpc', 'xml' );
				}
			} else { // HTTP error
				$httpClient = $this->rpcClient->getHttpClient();
				$lastResponse = $httpClient->getLastResponse();
				if( $lastResponse ) {
					$message = $lastResponse->getMessage().' (HTTP '.$lastResponse->getStatus().')';
				} else if( isset($e) ) {
					$message = $e->getMessage();
				} else {
					$message = 'unknown error';
				}
				LogHandler::logService( $action, $message, null, 'xmlrpc', 'txt' );
			}
		}

		// Leave a trail in the server log once we came back from Drupal
		if( $debugMode ) {
			LogHandler::Log( $area, 'DEBUG', 'Received "'.$action.'" service response.' );
		}

		// Now the service I/O is logged above, throw exception in case of a fault.
		if( isset($e) ) {
			LogHandler::Log( $area, 'ERROR', 'RPC call "'.$action.'" failed at URL "'.$this->url.'".' );
			$lastResponse = $this->rpcClient->getLastResponse();
			if( $lastResponse ) {
				if( $lastResponse->isFault() ) {
					$fault = $lastResponse->getFault();
					$detail = $fault->getMessage();
					$code = $fault->getCode();
					if( $code ) {
						$detail .= ' ('.$code.')';
					}
				} else {
					$detail = null;
				}
			} else { // HTTP error
				$httpClient = $this->rpcClient->getHttpClient();
				$lastResponse = $httpClient->getLastResponse();
				if( $lastResponse ) {
					$detail = $lastResponse->getMessage().' (HTTP '.$lastResponse->getStatus().')';
				} else {
					$detail = $e->getMessage();
				}
			}
			throw new BizException( 'ERR_PUBLISH', 'ERROR' /*ugly but needed*/,
				$detail, null, array($area, $e->getMessage()) );
		}

		// is_array is needed for some php versions when $retVal is a string.
		if( is_array($retVal) && isset($retVal['Errors']) && count($retVal['Errors']) > 0 ) {
			throw new BizException( 'ERR_PUBLISH', 'ERROR', /*ugly but needed*/
				null, null, array($area, strip_tags($retVal['Errors'][0])) );
		}

		return $retVal;
	}
}