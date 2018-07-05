<?php
/**
 * Class to execute a Elvis API request with authentication handling.
 *
 * Implementation uses cURL to make the API requests.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/config/config_elvis.php';

class Elvis_BizClasses_CurlClient
{
	/** @var array */
	private $curlOptions = array();

	/** @var string */
	private static $severity;

	/**
	 * @param array $curlOptions cURL options to override.
	 */
	public function setCurlOptions( array $curlOptions )
	{
		$this->curlOptions = $curlOptions;
	}

	/**
	 * Execute a service request against Elvis server for the session user or ELVIS_DEFAULT_USER.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @return Elvis_BizClasses_ClientResponse
	 * @throws Elvis_BizClasses_ClientException
	 */
	public function execute( Elvis_BizClasses_ClientRequest $request ) : Elvis_BizClasses_ClientResponse
	{
		if( $request->requiresAuthentication() ) {
			$response = $this->executeWithAuthentication( $request );
		} else {
			$response = $this->executeUnauthorized( $request );
		}
		return $response;
	}

	/**
	 * Execute a service request against Elvis server for a specific user.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @return Elvis_BizClasses_ClientResponse
	 */
	private function executeWithAuthentication( Elvis_BizClasses_ClientRequest $request ) : Elvis_BizClasses_ClientResponse
	{
		$curlOptions = $this->curlOptions;
		$accessToken = self::getAccessToken( $request->getUserShortName() );
		$request->setHeader( 'Authorization', 'Bearer '.$accessToken->accessToken );
		$response = self::plainRequest( $request, $curlOptions );
		if( $response->isAuthenticationError() ) {
			$accessToken = self::requestAndSaveAccessToken( $request->getUserShortName() );
			$request->setHeader( 'Authorization', 'Bearer '.$accessToken->accessToken );
			$response = self::plainRequest( $request, $curlOptions );
		}
		$response->setAuthenticationUser( $accessToken->elvisUser );
		return $response;
	}

	/**
	 * Execute a service request against Elvis server without user authorization.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @return Elvis_BizClasses_ClientResponse
	 */
	private function executeUnauthorized( Elvis_BizClasses_ClientRequest $request )
	{
		$curlOptions = $this->curlOptions;
		return self::plainRequest( $request, $curlOptions );
	}

	/**
	 * Execute service request against Elvis server.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @param array $curlOptions cURL options to override.
	 * @return Elvis_BizClasses_ClientResponse
	 * @throws Elvis_BizClasses_ClientException
	 */
	private static function plainRequest( Elvis_BizClasses_ClientRequest $request, array $curlOptions ) : Elvis_BizClasses_ClientResponse
	{
		$responseHeaders = [];
		$ch = curl_init();
		if( !$ch ) {
			throw new Elvis_BizClasses_ClientException( 'Failed to connect with Elvis Server. Failed to create a cURL handle.' );
		}
		curl_setopt_array( $ch, self::getCurlOptions( $request, $curlOptions, $responseHeaders ) );
		$startTime = microtime( true );
		if( isset( $curlOptions[ CURLOPT_WRITEFUNCTION ] ) ) {
			$body = curl_exec( $ch );
			$garbage = null;
		} else {
			ob_start();
			$body = curl_exec( $ch );
			$garbage = ob_get_contents();
			ob_end_clean();
		}
		if( $garbage ) {
			LogHandler::Log( 'ELVIS', 'ERROR', 'Found garbage data in output: '.$garbage );
		}
		$duration = microtime( true ) - $startTime;
		$httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if( !$httpStatusCode ) { // e.g. when bad ELVIS_URL configured, the status code is zero
			$curlError = 'cURL error: '.curl_error( $ch ).' (error code:'.curl_errno( $ch ).')';
			throw new Elvis_BizClasses_ClientException( 'Failed to connect with Elvis Server. '.$curlError );
		}
		$response = new Elvis_BizClasses_ClientResponse( $httpStatusCode, $body, $request->getExpectJson() );

		if( LogHandler::debugMode() ) {
			// Requires curl_setopt($ch, CURLINFO_HEADER_OUT, 1)
			$service = $request->composeServicePath(); // relative path, no query params
			$requestData = curl_getinfo( $ch, CURLINFO_HEADER_OUT ).PHP_EOL.
				'QUERY params:'.PHP_EOL.LogHandler::prettyPrint( $request->getQueryParams() ).PHP_EOL.
				'POST params:'.PHP_EOL.LogHandler::prettyPrint( $request->getPostParams() ).PHP_EOL.
				'BODY:'.PHP_EOL.$request->getBody().PHP_EOL;
			$logService = 'Elvis_'.str_replace( '/', '_', $service );
			LogHandler::logService( $logService, $requestData, true, 'JSON' );
			LogHandler::logService( $logService, join( '', $responseHeaders ).PHP_EOL.$response->body(), $response->isError() ? null : false, 'JSON' );
			LogHandler::Log( 'ELVIS', 'DEBUG', 'Request '.$service.' duration: '.sprintf( '%.3f', $duration * 1000 ).'ms' );
		}
		curl_close( $ch );

		return $response;
	}

	/**
	 * Get all request cURL options.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @param array $curlOptions existing cURL options which will be added.
	 * @param array $headers array to save response headers in.
	 * @return array request cURL options.
	 */
	private static function getCurlOptions( Elvis_BizClasses_ClientRequest $request, array $curlOptions, array &$headers ) : array
	{
		$url = ELVIS_URL.'/'.$request->composeServiceUrl();
		$defaultCurlOptions = array(
			CURLOPT_HEADER => false, // CURLOPT_HEADERFUNCTION is used instead
			CURLOPT_CONNECTTIMEOUT => ELVIS_CONNECTION_TIMEOUT,
			CURLOPT_TIMEOUT => 3600,
			CURLOPT_URL => $url,
			CURLOPT_FAILONERROR => false, // otherwise headers won't be parsed
		);
		// Enable this to retrieve the HTTP headers sent out (after calling curl_exec)
		$defaultCurlOptions[ CURLINFO_HEADER_OUT ] = 1;

		$defaultCurlOptions[ CURLOPT_HTTPHEADER ] = self::composeHttpHeaderOption( $request );

		if( $request->hasBody() ) {
			$defaultCurlOptions[ CURLOPT_POSTFIELDS ] = $request->getBody();
		}

		$httpMethod = $request->getHttpMethod();
		$defaultCurlOptions[ CURLOPT_CUSTOMREQUEST ] = $httpMethod;
		if( $httpMethod == Elvis_BizClasses_ClientRequest::HTTP_METHOD_POST ) {
			$defaultCurlOptions = self::composePostCurlOptions( $request ) + $defaultCurlOptions;
		}

		if( $request->getExpectJson() || $request->getExpectRawData() ) {
			$defaultCurlOptions[ CURLOPT_RETURNTRANSFER ] = 1;
		} else {
			$defaultCurlOptions[ CURLOPT_RETURNTRANSFER ] = 0;
		}

		// Hidden options, in case customer wants to overrule some settings.
		if( defined( 'ELVIS_CURL_OPTIONS' ) ) { // hidden option
			$options = unserialize( ELVIS_CURL_OPTIONS );
			if( $options ) {
				$defaultCurlOptions = $options + $defaultCurlOptions;
			}
		}
		$allCurlOptions = $curlOptions + $defaultCurlOptions;
		self::addSaveHeaderFunction( $allCurlOptions, $headers );

		return $allCurlOptions;
	}

	/**
	 * Compose CURLOPT_HTTPHEADER option from request headers.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @return array indexed array of HTTP header lines.
	 */
	private static function composeHttpHeaderOption( Elvis_BizClasses_ClientRequest $request ): array
	{
		$option = array();
		foreach( $request->getHeaders() as $name => $value ) {
			$option[] = $name.': '.$value;
		}
		return $option;
	}

	/**
	 * Compose POST options.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @return array associative array with cURL options.
	 */
	private static function composePostCurlOptions( Elvis_BizClasses_ClientRequest $request ): array
	{
		$curlOptions[ CURLOPT_POST ] = 1;
		if( !$request->hasBody() ) {
			$curlOptions[ CURLOPT_POSTFIELDS ] = $request->getPostParams();
			$fileToUpload = $request->getFileToUpload();
			if( $fileToUpload ) {
				$curlOptions[ CURLOPT_SAFE_UPLOAD ] = true;
				$curlOptions[ CURLOPT_POSTFIELDS ]['Filedata'] =
					new CURLFile( $fileToUpload->FilePath, $fileToUpload->Type );
			}
		}
		return $curlOptions;
	}

	/**
	 * Add cURL options to save response headers.
	 *
	 * @param array $options cURL option array.
	 * @param array $headers array to save response headers in.
	 */
	private static function addSaveHeaderFunction( array &$options, array &$headers )
	{
		if( array_key_exists( CURLOPT_HEADERFUNCTION, $options ) ) {
			$existingHeaderFunction = $options[ CURLOPT_HEADERFUNCTION ];
			$options[ CURLOPT_HEADERFUNCTION ] = function( $ch, $header ) use ( &$headers, $existingHeaderFunction ) {
				$headers[] = $header;
				return $existingHeaderFunction( $ch, $header );
			};
		} else {
			$options[ CURLOPT_HEADERFUNCTION ] = function( $ch, $header ) use ( &$headers ) {
				$headers[] = $header;
				return strlen( $header );
			};
		}
	}

	/**
	 * Compose POST body for Content-Type: application/x-www-form-urlencoded.
	 *
	 * @param array $post associative array with name value map
	 * @return string POST body
	 */
	private static function composeUrlEncodedPostBody( array $post ): string
	{
		$urlEncodedFields = array();
		foreach( $post as $key => $value ) {
			$urlEncodedFields[] = urlencode( $key ).'='.urlencode( $value );
		}
		return join( '&', $urlEncodedFields );
	}

	/**
	 * Get access token to execute a request against an Elvis server.
	 *
	 * @param string $enterpriseUser Short name of the acting Enterprise user.
	 * @return Elvis_DataClasses_Token
	 * @throws Elvis_BizClasses_ClientException
	 */
	private static function getAccessToken( string $enterpriseUser ) : Elvis_DataClasses_Token
	{
		$accessToken = Elvis_DbClasses_Token::get( $enterpriseUser );
		if( is_null( $accessToken ) ) {
			$accessToken = self::requestAndSaveAccessToken( $enterpriseUser );
		}
		return $accessToken;
	}

	/**
	 * Request access token and save it.
	 *
	 * @param string $enterpriseUser Short name of the acting Enterprise user.
	 * @return Elvis_DataClasses_Token
	 * @throws Elvis_BizClasses_ClientException
	 */
	private static function requestAndSaveAccessToken( string $enterpriseUser ) : Elvis_DataClasses_Token
	{
		$elvisUser = $enterpriseUser; // first try with Enterprise user
		$accessToken = self::requestAccessToken( $enterpriseUser, $elvisUser );
		Elvis_DbClasses_Token::save( $accessToken );
		return $accessToken;
	}

	/**
	 * Request access token from Elvis.
	 *
	 * @param string $enterpriseUser Short name of the acting Enterprise user.
	 * @param string $elvisUser Elvis user to be used for authentication of the back-end connection.
	 * @return Elvis_DataClasses_Token
	 * @throws Elvis_BizClasses_ClientException when access token can't be retrieved.
	 */
	private static function requestAccessToken( string $enterpriseUser, string $elvisUser ) : Elvis_DataClasses_Token
	{
		$post = array(
			'grant_type' => 'client_credentials',
			'impersonator_id' => $elvisUser,
		);
		$curlOptions = array(
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => ELVIS_CLIENT_ID.':'.ELVIS_CLIENT_SECRET
		);
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest( 'oauth/token', $elvisUser );
		$request->setSubjectEntity( BizResources::localize( 'USR_USER' ) );
		$request->setSubjectId( $elvisUser );
		$request->setHttpPostMethod();
		$request->setHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		$request->setBody( self::composeUrlEncodedPostBody( $post ) );
		$request->setExpectJson();

		$response = self::plainRequest( $request, $curlOptions );
		if( $response->isAuthenticationError() ) {
			if( $elvisUser !== ELVIS_DEFAULT_USER ) {
				return self::requestAccessToken( $enterpriseUser, ELVIS_DEFAULT_USER );
			}
		}
		if( $response->isError() ) {
			throw new Elvis_BizClasses_ClientException( 'Failed to retrieve access token from Elvis Server. Details:'.$response->getErrorMessage(), 'ERROR' );
		}

		return new Elvis_DataClasses_Token( $enterpriseUser, $elvisUser, $response->jsonBody()->access_token );
	}
}