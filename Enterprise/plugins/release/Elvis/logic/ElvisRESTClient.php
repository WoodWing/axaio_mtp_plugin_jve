<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once __DIR__.'/ElvisClient.php';

class ElvisRESTClient extends ElvisClient
{
	/**
	 * @var null|string Type of Load Balancer used with connection to Elvis. e.g: classic load balancer (AWSELB)
	 */
	private $loadBalancerType = null;

	/** @var bool Whether or not this client is a proxy. */
	private $isProxyMode = false;

	/**
	 * Calls an Elvis web service over the REST JSON interface.
	 *
	 * @param string $service Service name of the Elvis API.
	 * @param string[]|null $post Optionally. List of HTTP POST parameters to send along with the request.
	 * @param Attachment|null $file
	 * @return mixed Decoded JSON data on success. Object with 'errorcode' attribute on error.
	 * @throws BizException
	 */
	private function send( $service, $post = null, $file = null )
	{
		$response = $this->callElvisRestServiceAndHandleCookies( $service, $post, $file );
		$isError = isset( $response->errorcode ) && $response->errorcode != 200;

		// This function is called for all kind of services, but never for a logon (since that runs through the AMF client).
		// In case of an error, we want to be robust and retry, but that is all handled by the AMF client's logon implementation.
		// So all the REST client needs to do is logon and retry. If the logon fails, we may conclude that all the AMF client's
		// internal retries have failed so all that is left for us to do is bail out by NOT catching its exception.
		// The logout service is an exception; when it fails there is no point to re-login logout again. So we suppress any
		// failure and assume session has ended. If not ended directly, after a while Elvis will expire it automatically anyway.
		if( $isError && $service != 'services/logout' ) {
			require_once __DIR__.'/ElvisAMFClient.php';
			ElvisAMFClient::login();
			$response = $this->callElvisRestServiceAndHandleCookies( $service, $post, $file );
			$isError = isset( $response->errorcode ) && $response->errorcode != 200;
			if( $isError ) {
				$detail = 'Calling Elvis web service '.$service.' failed (REST API). '.
					'Error code: '.$response->errorcode.'; Message: '.$response->message;
				self::throwExceptionForElvisCommunicationFailure( $detail );
			}
		}
		return $response;
	}

	/**
	 * Call Elvis service, send/receive session data in cookies and log request/response in DEBUG mode.
	 *
	 * @since 10.1.4
	 * @param string $service Service name of the Elvis API.
	 * @param string[]|null $post Optionally. List of HTTP POST parameters to send along with the request.
	 * @param Attachment|null $file
	 * @return mixed Decoded JSON data on success. Object with 'errorcode' attribute on error.
	 */
	private function callElvisRestServiceAndHandleCookies( $service, $post, $file )
	{
		$url = self::getElvisBaseUrl().'/'.$service;
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		$cookies = ElvisSessionUtil::getSessionCookies();
		self::logService( $service, $url, $post, $cookies, true );
		$response = $this->callElvisService( $service, $url, $post, $cookies, $file );
		$isError = isset( $response->errorcode ) && $response->errorcode != 200;
		if( !$this->isProxyMode || $isError ) { // avoid logging downloaded files (proxy mode)
			self::logService( $service, $url, $response, $cookies, $isError ? null : false );
		}
		ElvisSessionUtil::updateSessionCookies( $cookies );
		return $response;
	}

	/**
	 * In debug mode, performs a print_r on $transData and logs the service as JSON.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @param string $service Service method used to give log file a name.
	 * @param string $url REST URL
	 * @param mixed $transData Transport data to be written in log file using print_r.
	 * @param array $cookies HTTP cookies sent with request or returned by response.
	 * @param boolean $isRequest TRUE to indicate a request, FALSE for a response, NULL for error.
	 */
	private static function logService( $service, $url, $transData, $cookies, $isRequest )
	{
		if( LogHandler::debugMode() ) {
			$logService = str_replace( '/', '_', $service );
			$log = 'URL:'.$url.PHP_EOL.'Cookies:'.print_r( $cookies, true ).PHP_EOL.'JSON:'.print_r( $transData, true );
			if( $isRequest ) {
				LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient calling Elvis web service '.$service );
			}
			LogHandler::logService( 'Elvis_'.$logService, $log, $isRequest, 'JSON' );
		}
	}

	/**
	 * Calls an Elvis web service over the REST JSON interface.
	 *
	 * @since 10.1.4 renamed function from sendUrl into callElvisService.
	 * @param string $service Service name of the Elvis API.
	 * @param string $url Request URL (JSON REST)
	 * @param string[]|null $post Optionally. List of HTTP POST parameters to send along with the request.
	 * @param array $cookies HTTP cookies to sent with request. After call, this is replaced with cookies returned by response.
	 * @param Attachment|null $file
	 * @return mixed On success, decoded JSON data in non-proxy mode or NULL in proxy mode. On error, an object with 'errorcode' attribute.
	 */
	private function callElvisService( $service, $url, $post, &$cookies, $file )
	{
		$ch = curl_init();
		if( !$ch ) {
			$detail = 'Elvis '.$service.' failed. '.
				'Failed to create a CURL handle for url: '.$url;
			self::throwExceptionForElvisCommunicationFailure( $detail );
		}
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FAILONERROR, 1 ); // Fail verbosely if the HTTP code returned is >= 400.
		curl_setopt( $ch, CURLOPT_POST, 1 );
		if( $this->isProxyMode ) { // Stream all data to output while calling curl_exec().
			curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function( $curl, $data ) {
				set_time_limit( 3600 ); // postpone timeout
				echo $data;
				return strlen( $data );
			} );
		} else { // Retrieve all data in memory through return value of curl_exec().
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		}
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, ELVIS_CONNECTION_TIMEOUT );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 3600 ); // Using the Enterprise default

		// Enable this to retrieve the HTTP headers sent out (after calling curl_exec)
		if( LogHandler::debugMode() ) {
			curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		}

		// Hidden options, in case customer wants to overrule some settings.
		if( defined( 'ELVIS_CURL_OPTIONS') ) { // hidden option
			$options = unserialize( ELVIS_CURL_OPTIONS );
			if( $options ) foreach( $options as $key => $value ) {
				curl_setopt( $ch, $key, $value );
			}
		}

		// To deal with file upload.
		if( $file ) {
			if( !isset( $post ) ) {
				$post = array();
			}
			curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true );
			$post['Filedata'] = new CURLFile( $file->FilePath, $file->Type ); // Needed by Elvis
		}
		if( isset( $post ) ) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post );
		} else {
			// When there's no post data in a POST method, make sure to clear the CURLOPT_POSTFIELDS.
			// Otherwise, it seems like for some PHP cURL version, it will send Content-Length = -1
			// in the Header which is unwanted ( it will lead to a bad request ).
			// To avoid the above, set the CURLOPT_POSTFIELDS to be empty (array()),
			// to make sure that the Content-Length is 0.
			curl_setopt( $ch, CURLOPT_POSTFIELDS, array() );
		}

		// Setting cookies to be sent over in the Request.
		if( $cookies ) {
			$cookieValueArr = array();
			foreach( $cookies as $cookieKey => $cookieVal ) {
				$encodedCookieValue = urlencode( $cookieVal );
				$cookieValueArr[] = "{$cookieKey}={$encodedCookieValue}";
			}
			$cookieValue = implode( '; ', $cookieValueArr );
			curl_setopt( $ch, CURLOPT_COOKIE, $cookieValue );
		}

		// Read the cookies returned by Elvis ( using a callback function )
		$cookies = array(); // To be passed back(referenced back) to the caller.
		// Example $headerLine = "Set-Cookie: AWSELB=4A5003EE72F010581";
		$curlResponseHeaderCallback = function( $ch, $headerLine ) use ( &$cookies ) {
			if( preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $theSetCookie ) == 1 ) {
				if( $theSetCookie ) foreach( $theSetCookie[1] as $theSetCookieKeyValue ) {
					parse_str( $theSetCookieKeyValue, $returnedCookie ); // does urldecode() !
					$returnedCookie = array_map( 'trim', $returnedCookie );
					$cookies = array_merge( $cookies, $returnedCookie );
				}
			}
			if( $this->isProxyMode ) {
				header( $headerLine );
			}
			return strlen( $headerLine ); // Needed by CURLOPT_HEADERFUNCTION
		};
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, $curlResponseHeaderCallback );

		$result = curl_exec($ch);

		if( LogHandler::debugMode() ) {
			// Require curl_setopt($ch, CURLINFO_HEADER_OUT, 1); as set above.
			$headersSent = curl_getinfo( $ch, CURLINFO_HEADER_OUT );
			$logMessage = 'RESTClient calling '.$service . ' using PHP cURL.'.PHP_EOL.'Headers Sent:'.PHP_EOL;
			LogHandler::logRaw( 'ELVIS', 'DEBUG',
				LogHandler::encodeLogMessage( $logMessage ).LogHandler::composeCodeBlock( $headersSent ) );
		}

		$httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if( $httpStatusCode == 200 ) {
			if( $this->isProxyMode ) {
				$response = null;
			} else {
				$response = json_decode( $result );
			}
		} else {
			$detail = 'Elvis '.$service.' failed with HTTP status code:'.$httpStatusCode.'.'.PHP_EOL;
			if( curl_errno( $ch ) ) {
				$detail .= 'CURL failed with error code "'.curl_errno( $ch ).'" for url: '.$url.'.'.PHP_EOL.curl_error( $ch );
			}
			$response = new stdClass();
			$response->errorcode = $httpStatusCode;
			$response->message = $detail;
		}

		curl_close($ch);

		return $response;
	}

	/**
	 * Performs REST update for provided metadata and file (if any).
	 *
	 * @param string $elvisId Id of asset
	 * @param array $metadata Changed metadata
	 * @param Attachment|null $file
	 * @param bool|null $clearCheckOutState Set to true or null(default) to checkin the object, set to false to retain the checkout status of the object.
	 * @throws BizException
	 */
	public static function update( $elvisId, $metadata, $file = null, $clearCheckOutState=null )
	{
		$post = array();
		$post['id'] = $elvisId;
		$post['clearCheckoutState'] = ( is_null( $clearCheckOutState ) || $clearCheckOutState ) ? 'true' : 'false'; // EN-90305 API requirement: it has to be a string.
		if( !empty( $metadata ) ) {
			$post['metadata'] = json_encode( $metadata );
		}
		$client = new ElvisRESTClient();
		$client->send( 'services/update', $post, $file );
	}

	/**
	 * Performs a bulk update for provided metadata.
	 *
	 * Calls the updatebulk web service over the Elvis JSON REST interface.
	 *
	 * @param string[] $elvisIds Ids of assets
	 * @param MetaData|MetaDataValue[] $metadata Changed metadata
	 * @throws BizException
	 */
	public static function updateBulk( $elvisIds, $metadata )
	{
		$post = array();

		// Build query for ids
		$post['q'] = '';
		foreach( $elvisIds as $elvisId ) {
			if( !empty( $post['q'] ) ) {
				$post['q'] .= ' OR ';
			}
			$post['q'] .= 'id:'.$elvisId;
		}

		if( !empty( $metadata ) ) {
			$post['metadata'] = json_encode( $metadata );
		}
		$client = new ElvisRESTClient();
		$client->send( 'services/updatebulk', $post );
	}

	/**
	 * Performs REST logout of the acting Enterprise user from Elvis.
	 *
	 * Calls the logout web service over the Elvis JSON REST interface.
	 * @since 10.1.4 Any errors are suppressed because sessions will expire automatically anyway.
	 */
	public static function logout()
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		self::logoutSession();
	}

	/**
	 * Does logout of the acting Enterprise user from Elvis.
	 *
	 * Calls the logout web service over the Elvis JSON REST interface.
	 * @since 10.1.4 Any errors are suppressed because sessions will expire automatically anyway.
	 */
	private static function logoutSession()
	{
		$client = new ElvisRESTClient();
		$client->send( 'services/logout' );
	}

	/**
	 * Calls the fieldinfo web service over the Elvis JSON REST interface.
	 *
	 * @return mixed
	 * @throws BizException
	 */
	public static function fieldInfo()
	{
		$client = new ElvisRESTClient();
		return $client->send( 'services/fieldinfo' );
	}

	/**
	 * Calls a given web service over the Elvis JSON REST interface.
	 *
	 * The HTTP response headers and returned data from Elvis are streamed in the PHP output.
	 *
	 * @since 10.5.0
	 * @param string $service
	 * @return mixed
	 * @throws BizException
	 */
	public function proxy( $service )
	{
		$this->isProxyMode = true;
		return $this->send( $service, null, null );
	}

	/**
	 * Pings the Elvis Server and retrieves some basic information.
	 *
	 * This function should only be called when connected to Elvis 5 (or newer).
	 * See {@link:getElvisServerVersion()} to resolve the server version in a Elvis 4 compatible manner.
	 *
	 * @since 10.1.1
	 * @return object Info object with properties state, version, available and server.
	 */
	public function getElvisServerInfo()
	{
		// The Elvis ping service returns a JSON structure like this:
		//     {"state":"running","version":"5.15.2.9","available":true,"server":"Elvis"}
		$client = new ElvisRESTClient();
		return $client->send( 'services/ping' );
	}

	/**
	 * Requests Elvis Server for its version by calling the version.jsp REST service.
	 * This service works at least for Elvis 4 (or newer).
	 *
	 * Calls the version.jsp web page over HTTP, parses the return XMl file and returns the read version.
	 * Note that this is an old and home brewed protocol (unlike the other JSON REST services).
	 *
	 * Function also retrieves the type of Load Balancer used. Caller can retrieve the type of Load Balancer
	 * by calling getLoadBalancerType();
	 *
	 * @since 10.0.5 / 10.1.2
	 * @return string
	 * @throws BizException
	 */
	public function getElvisServerVersion()
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';

		$response = null;
		$url = self::getElvisBaseUrl().'/version.jsp';
		LogHandler::logService( 'Elvis_version_jsp', $url, true, 'REST' );
		try {
			$client = new Zend\Http\Client();
			$client->setUri( $url );
			$cookies = ElvisSessionUtil::getSessionCookies();
			if( $cookies ) {
				$client->setCookies( $cookies );
			}
			$response = $client->send();
		} catch( Exception $e ) {
			LogHandler::logService( 'Elvis_version_jsp', $e->getMessage(), null, 'REST' );
			self::throwExceptionForElvisCommunicationFailure( $e->getMessage() );
		}
		if( $response->getStatusCode() !== 200 ) {
			LogHandler::logService( 'Elvis_version_jsp', $response->getBody(), null, 'REST' );
			self::throwExceptionForElvisCommunicationFailure( $response->renderStatusLine() );
		}

		$cookies = array();
		$cookieJar = $response->getCookie();
		if( $cookieJar ) foreach( $cookieJar as $cookie ) {
			$cookies[ $cookie->getName() ] = $cookie->getValue();
		}
		if( $cookies ) {
			ElvisSessionUtil::updateSessionCookies( $cookies );
		}

		$versionXml = trim($response->getBody());
		LogHandler::logService( 'Elvis_version_jsp', $versionXml, false, 'REST' );

		$serverVersion = '';
		$xmlDoc = new DOMDocument();
		if( $xmlDoc->loadXML( $versionXml ) ) {
			$xPath = new DOMXPath( $xmlDoc );
			$versionNodeList = $xPath->query( '//elvisServer/version' );
			$serverVersion = $versionNodeList->length > 0 ? $versionNodeList->item(0)->nodeValue : '';
		}
		if( !$serverVersion ) {
			throw new BizException( null, 'Server', 'Parsing the XML result of version.jsp failed.',
				'Could not detect Elvis Server version.' );
		}

		// Remember the ELB type if there's any.
		$this->setLoadBalancerType( $cookies );

		return $serverVersion;
	}

	/**
	 * Set the Load Balancer type being used with connection to Elvis.
	 *
	 * The Load Balancer type can be the Classic Load Balancer ( ELB ),
	 * or the Application Load Balancer ( ALB ).
	 *
	 * @param array $cookies Key-value lists of cookies returned in a response from Elvis.
	 */
	private function setLoadBalancerType( $cookies )
	{
		if( $cookies ) {
			if( isset( $cookies['AWSELB'] )) {
				$this->loadBalancerType = 'AWSELB';
			} else if( isset( $cookies['AWSALB'] )) {
				$this->loadBalancerType = 'AWSALB';
			}
		}
	}

	/**
	 * Returns the Load Balancer type being used with connection to Elvis.
	 *
	 * The Load Balancer type can be the Classic Load Balancer ( ELB ),
	 * or the Application Load Balancer ( ALB ).
	 *
	 * @return null|string Returns the type of Load Balancer used with Elvis, Null when no Load Balancer is used.
	 */
	public function getLoadBalancerType()
	{
		return $this->loadBalancerType;
	}
}
