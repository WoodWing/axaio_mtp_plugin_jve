<?php
/**
 * @package    Enterprise
 * @subpackage Services
 * @since      v9.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

use Graze\GuzzleHttp\JsonRpc\Client;

class WW_JSON_Client
{
	/** @var int $incrementalID  */
	private $incrementalID = 1;

	/**
	 * @var Client $grazeClient
	 */
	private $grazeClient;

	/**
	 * Creates a new instance of the Guzzle JSON-RPC client.
	 *
	 * @param string $baseUrl
	 * @param array|null $config
	 */
	public function __construct( $baseUrl = '', $config = null )
    {
	    if( !isset( $config['transfer'] ) ) {
		    $config['transfer'] = 'HTTP';
	    }
	    if( !isset( $config['protocol'] ) ) {
		    $config['protocol'] = 'JSON';
	    }
	    $urlInfo = parse_url( $baseUrl );
	    $separator = isset( $urlInfo['query'] ) ? '&' : '?';
	    $baseUrl .= $separator.'transfer='. $config['transfer'].'&protocol='. $config['protocol'];

	    // for debugging: additional HTTP entry point params
	    if( LogHandler::debugMode() ) {
		    require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		    $params = WW_Utils_TestSuiteOptions::getHttpEntryPointDebugParams();
		    if( $params ) {
			    $baseUrl .= '&'.$params;
		    }
	    }

	    // Clients can pass an expected error (S-code) on the URL of the entry point.
	    // When that error is thrown, is should be logged as INFO (not as ERROR).
	    // This is for testing purposes only, in case the server log must stay free of errors.
	    if( isset( $config['expectedError'] ) ) {
		    $baseUrl .= '&expectedError='. $config['expectedError'];
	    }

	    // Throw an Exception in case of a RPC error.
	    $config['rpc_error'] = true;

       $this->grazeClient = Client::factory($baseUrl, $config);
    }

	/**
	 * Creates a request for use with the Graze Guzzle JSON-RPC client.
	 *
	 * The function will also restructure objects in two ways:
	 * - Removes elements which are not reflected in the class signature.
	 * - Adds round trip information.
	 *
	 * @param string $method
	 * @param string $id
	 * @param array|mixed $params
	 * @return \Graze\GuzzleHttp\JsonRpc\Message\RequestInterface
	 */
	public function request( $method, $id, $params = null )
	{
		require_once BASEDIR . '/server/protocols/json/Services.php';

		// Make sure the parameters have extra information, so this client is able to round-trip the interface
		$params = WW_JSON_Services::restructureObjects( $params );

		return $this->grazeClient->request($id, $method, $params);
	}

	/**
	 * Magic method used to retrieve a command
	 *
	 * @param string $method Name of the command object to instantiate
	 * @param array  $args   Arguments to pass to the command
	 *
	 * @return mixed Returns the result of the command
	 * @throws Exception when a command is not found
	 */
	public function __call( $method, $args )
	{
		$guzzleRequest = $this->request( $method, (string) $this->incrementalID++, array('req' => $args[0] ) );

		$guzzleResponse = $this->grazeClient->send( $guzzleRequest );
		require_once BASEDIR.'/server/protocols/json/Services.php';
		$services = new WW_JSON_Services;
		$result = $guzzleResponse->getRpcResult();
		$result = $services->arraysToObjects( $result );
		return WW_JSON_Services::restructureObjects( $result );
	}
}