<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Class to execute a Elvis API request with authentication handling.
 *
 * Implementation uses cURL to make the API requests.
 */

require_once __DIR__.'/../config.php';

class ElvisCurlClient
{
	/**
	 * Execute service request against Elvis server.
	 *
	 * @param string $shortUserName
	 * @param string $service
	 * @param array $curlOptions cURL options to override.
	 * @return ElvisClientResponse
	 * @throws ElvisBizException
	 */
	public static function request( $shortUserName, $service, $curlOptions )
	{
		$curlOptions[ CURLOPT_HTTPHEADER ] = array( 'Authorization: Bearer '.self::getAccessToken( $shortUserName ) );
		$response = self::plainRequest( $service, $curlOptions );
		if( $response->isAuthenticationError() ) {
			$curlOptions[ CURLOPT_HTTPHEADER ] = array( 'Authorization: Bearer '.self::requestAndSaveAccessToken( $shortUserName ) );
			$response = self::plainRequest( $service, $curlOptions );
		}
		return $response;
	}

	/**
	 * Execute service request against Elvis server.
	 *
	 * @param string $service
	 * @param array $curlOptions cURL options to override.
	 * @return ElvisClientResponse
	 * @throws ElvisBizException
	 */
	public static function plainRequest( $service, $curlOptions )
	{
		$responseHeaders = [];
		$ch = curl_init();
		if( !$ch ) {
			require_once __DIR__.'/ElvisBizException.php';
			throw new ElvisBizException( 'Failed to create a CURL handle' );
		}
		curl_setopt_array( $ch, self::getCurlOptions( $service, $curlOptions, $responseHeaders ) );
		$startTime = microtime( true );
		$body = curl_exec( $ch );
		$duration = microtime( true ) - $startTime;
		$httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		require_once __DIR__.'/ElvisClientResponse.php';
		$response = new ElvisClientResponse( $httpStatusCode, $body );

		if( LogHandler::debugMode() ) {
			// Requires curl_setopt($ch, CURLINFO_HEADER_OUT, 1)
			$requestHeaders = curl_getinfo( $ch, CURLINFO_HEADER_OUT );
			$logService = 'Elvis_'.str_replace( '/', '_', $service );
			LogHandler::logService( $logService, $requestHeaders, true, 'JSON' );
			LogHandler::logService( $logService, join( '', $responseHeaders ).PHP_EOL.$response->body(), $response->isError() ? null : false, 'JSON' );
			LogHandler::Log( 'ELVIS', 'DEBUG', 'Request '.$service.' duration: '.sprintf( '%.3f', $duration * 1000 ).'ms' );
		}
		curl_close( $ch );

		return $response;
	}

	/**
	 * Get all request cURL options.
	 *
	 * @param string $service Elvis service to request.
	 * @param array $curlOptions existing cURL options which will be added.
	 * @param array $headers array to save response headers in.
	 * @return array request cURL options.
	 */
	private static function getCurlOptions( $service, $curlOptions, &$headers )
	{
		$url = ELVIS_URL.'/'.$service;
		$defaultCurlOptions = array(
			CURLOPT_HEADER => false, // CURLOPT_HEADERFUNCTION is used instead
			CURLOPT_CONNECTTIMEOUT => ELVIS_CONNECTION_TIMEOUT,
			CURLOPT_TIMEOUT => 3600,
			CURLOPT_URL => $url,
			CURLOPT_FAILONERROR => false, // otherwise headers won't be parsed
		);
		// Enable this to retrieve the HTTP headers sent out (after calling curl_exec)
		$defaultCurlOptions[ CURLINFO_HEADER_OUT ] = 1;

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
	 * Add cURL options to save response headers.
	 *
	 * @param array $options cURL option array.
	 * @param array $headers array to save response headers in.
	 */
	private static function addSaveHeaderFunction( &$options, &$headers )
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
	 * Get POST request cURL options with Content-Type: application/x-www-form-urlencoded.
	 *
	 * @param array $post
	 * @param array $curlOptions
	 * @return array with cURL options
	 */
	private static function postUrlEncodedOptions( $post, $curlOptions )
	{
		$urlEncodedFields = array();
		foreach( $post as $key => $value ) {
			$urlEncodedFields[] = urlencode( $key ).'='.urlencode( $value );
		}
		$curlOptions = $curlOptions + array(
				CURLOPT_POST => 1,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_POSTFIELDS => join( '&', $urlEncodedFields )
			);
		return $curlOptions;
	}

	/**
	 * Get access token to execute a request against an Elvis server.
	 *
	 * @param  string $shortUserName
	 * @return string access token
	 * @throws ElvisBizException
	 */
	private static function getAccessToken( $shortUserName )
	{
		require_once __DIR__.'/../dbclasses/Token.class.php';
		$accessToken = Elvis_DbClasses_Token::get( $shortUserName );
		if( is_null( $accessToken ) ) {
			$accessToken = self::requestAndSaveAccessToken( $shortUserName );
		}
		return $accessToken;
	}

	/**
	 * Request access token and save it.
	 *
	 * @param  string $shortUserName
	 * @return string access token
	 * @throws ElvisBizException
	 */
	private static function requestAndSaveAccessToken( $shortUserName )
	{
		$accessToken = self::requestAccessToken( $shortUserName );
		require_once __DIR__.'/../dbclasses/Token.class.php';
		Elvis_DbClasses_Token::save( $shortUserName, $accessToken );
		return $accessToken;
	}

	/**
	 * Request access token from Elvis.
	 *
	 * @param  string $shortUserName
	 * @return string access token
	 * @throws ElvisBizException when access token can't be retrieved.
	 */
	private static function requestAccessToken( $shortUserName )
	{
		$post = array(
			'grant_type' => 'client_credentials',
			'impersonator_id' => $shortUserName,
		);
		$curlOptions = self::postUrlEncodedOptions( $post, array(
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => ELVIS_CLIENT_ID.':'.ELVIS_CLIENT_SECRET
		) );
		$response = self::plainRequest( 'oauth/token', $curlOptions );
		if( $response->isError() ) {
			require_once __DIR__.'/ElvisBizException.php';
			throw new ElvisBizException( 'Failed to retrieve access token' );
		}
		return $response->jsonBody()->access_token;
	}
}
