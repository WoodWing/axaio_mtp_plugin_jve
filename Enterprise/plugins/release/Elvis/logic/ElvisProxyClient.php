<?php
/**
 * @package    Elvis
 * @subpackage Logic
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class ElvisProxyClient
{
	/**
	 * @var string
	 */
	private $shortUserName;
	/**
	 * @var string
	 */
	private $service;

	public function __construct( $shortUserName, $service )
	{
		$this->shortUserName = $shortUserName;
		$this->service = $service;
	}

	/**
	 * @return ElvisClientResponse
	 * @throws ElvisBizException
	 */
	private function proxyGet()
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

		require_once __DIR__.'/ElvisCurlClient.php';
		return ElvisCurlClient::request( $this->shortUserName, $this->service, $curlOptions );
	}

	/**
	 * Calls a given web service over the Elvis JSON REST interface.
	 *
	 * The HTTP response headers and returned data from Elvis are streamed in the PHP output.
	 *
	 * @throws ElvisBizException
	 */
	public function proxy()
	{
		set_time_limit( 3600 ); // postpone timeout
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		switch( $httpMethod ) {
			case 'GET':
				$this->proxyGet();
				break;
			default:
				throw new ElvisBizException( 'HTTP method '.$httpMethod.' not supported' );
		}
	}
}
