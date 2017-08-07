<?php


/**
 * AMF Client
 *
 * Use this class to make a calls to AMF0/AMF3 services. The class makes use of the curl http library, so make sure you have this installed.
 *
 * It sends AMF0 encoded data by default. Change the encoding to AMF3 with setEncoding. sendRequest calls the actual service
 *
 * @package SabreAMF
 * @version $Id$
 * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @licence http://www.freebsd.org/copyright/license.html  BSD License
 * @example ../examples/client.php
 * @uses SabreAMF_Message
 * @uses SabreAMF_OutputStream
 * @uses SabreAMF_InputStream
 */

require_once 'Message.php';
require_once 'OutputStream.php';
require_once 'InputStream.php';
require_once 'Const.php';
require_once 'AMF3/Wrapper.php';
class SabreAMF_Client
{
	/**
	 * endPoint
	 *
	 * @var string
	 */
	private $endPoint;
	/**
	 * httpProxy
	 *
	 * @var mixed
	 */
	private $httpProxy;
	/**
	 * amfInputStream
	 *
	 * @var SabreAMF_InputStream
	 */
	private $amfInputStream;
	/**
	 * amfOutputStream
	 *
	 * @var SabreAMF_OutputStream
	 */
	private $amfOutputStream;

	/**
	 * amfRequest
	 *
	 * @var SabreAMF_Message
	 */
	private $amfRequest;

	/**
	 * amfResponse
	 *
	 * @var SabreAMF_Message
	 */
	private $amfResponse;

	/**
	 * encoding
	 *
	 * @var int
	 */
	private $encoding = SabreAMF_Const::AMF0;

	/**
	 * destination
	 *
	 * @var string
	 */
	private $destination = null;

	/**
	 * HTTP cookies
	 *
	 * @since 10.0.5 / 10.1.2
	 * @var array
	 */
	private $cookies = array();

	/**
	 * cURL options for the HTTP connection.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @var array
	 */
	private $curlOptions = array();

	/**
	 * SabreAMF_Client constructor.
	 *
	 * @param string $endPoint The url to the AMF gateway
	 * @param string $destination
	 */
	public function __construct( $endPoint, $destination = null )
	{
		$this->endPoint = $endPoint;
		$this->destination = $destination;

		$this->amfRequest = new SabreAMF_Message();
		$this->amfOutputStream = new SabreAMF_OutputStream();
	}

	/**
	 * Calling service request to the specified $servicePath.
	 *
	 * Function expects the $servicePath to be the service name and the method name.
	 * The parameters of the method call (method name) should be passed in the $data.
	 *
	 * @param string $servicePath The servicepath (e.g.: myservice.mymethod)
	 * @param array $data The list of data / information to be sent over in the service call.
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @param int $connectionTimeout Max time to wait for network connection to setup.
	 * @return mixed
	 */
	public function sendRequest( $servicePath, $data, $operationTimeout, $connectionTimeout )
	{
		// Use enpty array to prevent NPE server side
		if( $data == null ) {
			$data = array();
		}

		// We're using the FLEX Messaging framework
		if( $this->encoding & SabreAMF_Const::FLEXMSG ) {

			// Setting up the message
			$message = new SabreAMF_AMF3_RemotingMessage();
			$message->body = $data;

			// We need to split serviceName.methodName into separate variables
			$service = explode( '.', $servicePath );
			$method = array_pop( $service );
			$service = implode( '.', $service );
			$message->operation = $method;
			$message->source = $service;
			$message->destination = $this->destination;

			$data = new SabreAMF_AMF3_Wrapper( array( $message ) );
		}

		$this->amfRequest->addBody( array(

			// If we're using the flex messaging framework, target is specified as the string 'null'
			'target' => $this->encoding & SabreAMF_Const::FLEXMSG ? 'null' : $servicePath,
			'response' => '/1',
			'data' => $data
		) );

		$this->amfRequest->serialize( $this->amfOutputStream );

		$result = $this->sendHttpRequest( $operationTimeout, $connectionTimeout );

		$this->amfInputStream = new SabreAMF_InputStream( $result );
		$this->amfResponse = new SabreAMF_Message();
		$this->amfResponse->deserialize( $this->amfInputStream );

		$this->parseHeaders();

		foreach( $this->amfResponse->getBodies() as $body ) {

			if( strpos( $body['target'], '/1' ) === 0 ) {
				return $body['data'];
			}
		}
		return null; // Should not reach here.
	}

	/**
	 * Sends a HTTP request using the Zend Http Client.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @param int $operationTimeout The request / execution timeout of curl in seconds (This is not the connection timeout).
	 * @param int $connectionTimeout Max time to wait for network connection to setup.
	 * @return string HTTP response body
	 * @throws Exception
	 */
	private function sendHttpRequest( $operationTimeout, $connectionTimeout )
	{
		try {
			$curlOpts = array();
			// For zendframework v2.5.3:
			// Make sure that the Execution timeout ( CURLOPT_TIMEOUT is set in the 'curloptions' key ).
			// Doing $client->setOptions( 'timeout' => 3600 ) will be later on flattened by the Curl Adapter
			// to have 'CURLOPT_TIMEOUT' the same as the 'CURLOPT_CONNECTTIMEOUT' which is not wanted.
			if( defined( 'CURLOPT_CONNECTTIMEOUT_MS' ) ) {
				$curlOpts[ CURLOPT_CONNECTTIMEOUT_MS  ] = $connectionTimeout * 1000;
			} else {
				$curlOpts[ CURLOPT_CONNECTTIMEOUT ] =  $connectionTimeout;
			}
			if( defined( 'CURLOPT_TIMEOUT_MS' ) ) {
				$curlOpts[ CURLOPT_TIMEOUT_MS ] =  $operationTimeout * 1000;
			} else {
				$curlOpts[ CURLOPT_TIMEOUT ] = $operationTimeout;
			}
			if( $this->httpProxy ) {
				$curlOpts[ CURLOPT_PROXY ] = $this->httpProxy;
			}
			$curlOpts[ CURLOPT_POSTFIELDS ] = $this->amfOutputStream->getRawData();

			$client = new Zend\Http\Client();
			$client->setUri( $this->endPoint );
			$client->setMethod( Zend\Http\Request::METHOD_POST );
			$client->setHeaders( array( 'Content-type' => SabreAMF_Const::MIMETYPE ) );
			$client->setOptions(
				array(
					'timeout' => null, // trick to allow overruling CURLOPT_TIMEOUT / CURLOPT_CONNECTTIMEOUT above
					'adapter' => 'Zend\Http\Client\Adapter\Curl',
					'curloptions' => $this->curlOptions + $curlOpts
					// L> Note that the + operator on arrays does preserve the LHS while taking over data from RHS.
					//    This is intended since we want to prefer theirs ($curlOptions) over ours. So no overwrite.
				)
			);
			if( $this->cookies ) {
				$client->setCookies( $this->cookies );
			}
			$response = $client->send();
			if( $response->getStatusCode() !== 200 ) {
				throw new Exception( $response->renderStatusLine() );
			}
			$this->cookies = array();
			$cookieJar = $response->getCookie();
			if( $cookieJar ) foreach( $cookieJar as $cookie ) {
				$this->cookies[ $cookie->getName() ] = $cookie->getValue();
			}
			$result = $response->getBody();
		} catch( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
		return $result;
	}

	/**
	 * addHeader
	 *
	 * Add a header to the client request
	 *
	 * @param string $name
	 * @param bool $required
	 * @param mixed $data
	 * @return void
	 */
	public function addHeader( $name, $required, $data )
	{

		$this->amfRequest->addHeader( array( 'name' => $name, 'required' => $required == true, 'data' => $data ) );

	}

	/**
	 * setCredentials
	 *
	 * @param string $username
	 * @param string $password
	 * @return void
	 */
	public function setCredentials( $username, $password )
	{

		$this->addHeader( 'Credentials', false, (object)array( 'userid' => $username, 'password' => $password ) );

	}

	/**
	 * setHttpProxy
	 *
	 * @param mixed $httpProxy
	 * @return void
	 */
	public function setHttpProxy( $httpProxy )
	{
		$this->httpProxy = $httpProxy;
	}

	/**
	 * parseHeaders
	 *
	 * @return void
	 */
	private function parseHeaders()
	{

		foreach( $this->amfResponse->getHeaders() as $header ) {

			switch( $header['name'] ) {

				case 'ReplaceGatewayUrl' :
					if( is_string( $header['data'] ) ) {
						$this->endPoint = $header['data'];
					}
					break;

			}


		}

	}

	/**
	 * Change the AMF encoding (0 or 3)
	 *
	 * @param int $encoding
	 * @return void
	 */
	public function setEncoding( $encoding )
	{

		$this->encoding = $encoding;
		$this->amfRequest->setEncoding( $encoding & SabreAMF_Const::AMF3 );

	}

	/**
	 * Change the destination
	 *
	 * @param string $destination
	 * @return void
	 */
	public function setDestination( $destination )
	{

		$this->destination = $destination;

	}

	/**
	 * Set cURL options for the HTTP connection.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @param array $options
	 */
	public function setCurlOptions( array $options )
	{
		$this->curlOptions = $options;
	}

	/**
	 * Set HTTP cookies to be sent along with the AMF request.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @param array $cookies
	 */
	public function setCookies( array $cookies )
	{
		$this->cookies = $cookies;
	}

	/**
	 * Get HTTP cookies that were sent back along with the AMF response.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @return array $cookies
	 */
	public function getCookies()
	{
		return $this->cookies;
	}
}



