<?php
/**
 * Class to proxy API requests to Elvis.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
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
	 * @var string[]
	 */
	private $queryParams;

	/**
	 * Elvis_BizClasses_ProxyClient constructor.
	 *
	 * @param string $shortUserName
	 * @param string $service
	 */
	public function __construct( $shortUserName, $service )
	{
		$this->shortUserName = $shortUserName;
		$queryPos = strpos( $service, '?' );
		$this->queryParams = array();
		if( $queryPos === false ) {
			$this->service = $service;
		} else {
			$this->service = substr( $service, 0, $queryPos );
			$queryParamsAndValues = explode( '&', substr( $service, $queryPos + 1 ) );
			foreach( $queryParamsAndValues as $queryParamsAndValue ) {
				if( strpos( $queryParamsAndValue, '=' ) === false ) {
					$this->queryParams[ $queryParamsAndValue ] = null;
				} else {
					list( $paramName, $paramValue ) = explode( '=', $queryParamsAndValue, 2 );
					$this->queryParams[ $paramName ] = $paramValue;
				}
			}
		}
	}

	/**
	 * Execute a GET request against an Elvis server.
	 *
	 * The HTTP response headers and returned data from Elvis are streamed in the PHP output.
	 *
	 * @return Elvis_BizClasses_ClientResponse
	 * @throws Elvis_BizClasses_ClientException
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
		foreach( $this->queryParams as $paramName => $paramValue ) {
			$request->addQueryParam( $paramName, $paramValue );
		}
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
	 * @throws Elvis_BizClasses_ClientException
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
				throw new Elvis_BizClasses_ClientException( 'HTTP method '.$httpMethod.' not supported' );
		}
	}
}
