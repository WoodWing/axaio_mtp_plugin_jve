<?php
/**
 * @package    Enterprise
 * @subpackage Services
 * @since      v9.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

use Graze\Guzzle\JsonRpc\JsonRpcClient;
use Graze\Guzzle\JsonRpc\Message\Request;
use Guzzle\Service\Client;
use Guzzle\Http\Message\RequestInterface;

class WW_JSON_Client extends JsonRpcClient
{
	/** @var int $incrementalID  */
	private $incrementalID = 1;

	/**
	 * {@inheritdoc}
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

       parent::__construct( $baseUrl, $config );
    }

	/**
	 * {@inheritdoc}
	 */
	public function request( $method, $id, $params = null, $uri = null, $headers = null )
	{
		require_once BASEDIR . '/server/protocols/json/Services.php';

		// Make sure the parameters have extra information, so this client is able to round-trip the interface
		$params = WW_JSON_Services::restructureObjects( $params );

		// Let parent handle the request.
		return parent::request( $method, $id, $params, $uri, $headers );
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
		$guzzleRequest = $this->request( $method, (string) $this->incrementalID, array('req' => $args[0] ) );
		$guzzleResponse = $guzzleRequest->send();

		if( !$guzzleResponse instanceof \Graze\Guzzle\JsonRpc\Message\ErrorResponse ) {
			require_once BASEDIR.'/server/protocols/json/Services.php';
			$services = new WW_JSON_Services;
			$result = $guzzleResponse->getResult();
			$result = $services->arraysToObjects( $result );
			$response = WW_JSON_Services::restructureObjects( $result );
		} else {
			throw new \Guzzle\Http\Exception\ClientErrorResponseException( $guzzleResponse->getMessage() );
		}
		return $response;
	}
}