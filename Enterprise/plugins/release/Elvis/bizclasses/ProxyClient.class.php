<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Class to proxy API requests to Elvis.
 */

class Elvis_BizClasses_ProxyClient
{
	/**
	 * @var string
	 */
	private $shortUserName;
	/**
	 * @var string
	 */
	private $service;

	/**
	 * Elvis_BizClasses_ProxyClient constructor.
	 *
	 * @param string $shortUserName
	 * @param string $service
	 */
	public function __construct( $shortUserName, $service )
	{
		$this->shortUserName = $shortUserName;
		$this->service = $service;
	}

	/**
	 * Execute a GET request against an Elvis server.
	 *
	 * The HTTP response headers and returned data from Elvis are streamed in the PHP output.
	 *
	 * @return Elvis_BizClasses_ClientResponse
	 * @throws Elvis_BizClasses_Exception
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
				return strlen( $headerLine );
			}
		);
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest( $this->service, $this->shortUserName );
		// Elvis has support for 'ETag' and so it returns it in the file download response headers. When the web browser
		// requests for 'If-None-Match', here we pass on that header to Elvis to let it decide if the client already has
		// the latest file version. If so, it returns HTTP 304 without file, else HTTP 200 with file.
		if( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) {
			$request->setHeader('If-None-Match', $_SERVER['HTTP_IF_NONE_MATCH']);
		}
		$client = new Elvis_BizClasses_CurlClient();
		$client->setCurlOptions( $curlOptions );
		return $client->execute( $request );
	}

	/**
	 * Calls a given web service over the Elvis JSON REST interface.
	 *
	 * The HTTP response headers and returned data from Elvis are streamed in the PHP output.
	 *
	 * @throws Elvis_BizClasses_Exception
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
				throw new Elvis_BizClasses_Exception( 'HTTP method '.$httpMethod.' not supported' );
		}
	}
}
