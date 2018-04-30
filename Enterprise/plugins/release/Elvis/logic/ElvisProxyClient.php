<?php

require_once __DIR__.'/ElvisCurlClient.php';

class ElvisProxyClient
{
	/**
	 * @param $service
	 * @return ElvisClientResponse
	 * @throws ElvisBizException
	 */
	private static function proxyGet( $service )
	{
		$curlOptions = array(
			CURLOPT_WRITEFUNCTION => function( $curl, $data ) {
				echo $data;
				return strlen( $data );
			},
			CURLOPT_HEADERFUNCTION => function( $ch, $headerLine ) {
				header( $headerLine );
				return strlen( $headerLine ); // Needed by CURLOPT_HEADERFUNCTION
			}
		);
		return ElvisCurlClient::request( $service, $curlOptions );
	}

	/**
	 * Calls a given web service over the Elvis JSON REST interface.
	 *
	 * The HTTP response headers and returned data from Elvis are streamed in the PHP output.
	 *
	 * @since 10.5.0
	 * @param string $service
	 * @throws ElvisBizException
	 */
	public static function proxy( $service )
	{
		set_time_limit( 3600 ); // postpone timeout
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		// TODO handle POST
		switch( $httpMethod ) {
			case 'GET':
				self::proxyGet( $service );
				break;
			default:
				throw new ElvisBizException( 'HTTP method '.$httpMethod.' not supported' );
		}
	}
}
