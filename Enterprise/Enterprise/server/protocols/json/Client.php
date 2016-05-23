<?php

// This is not ZCA friendly, it's OK for now
use Graze\Guzzle\JsonRpc\JsonRpcClient;
use Graze\Guzzle\JsonRpc\Message\Request;
use Guzzle\Service\Client;
use Guzzle\Http\Message\RequestInterface;

/**
 * Class WW_JSON_Client
 */
class WW_JSON_Client extends JsonRpcClient
{
	/**
	 * @param string $baseUrl
	 * @param null $config
	 */
	public function __construct($baseUrl = '', $config = null)
    {
        parent::__construct($baseUrl, $config);
    }

	/**
	 * Execute JSON-RPC request
	 *
	 * @param string $method
	 * @param int $id
	 * @param null $params
	 * @param null $uri
	 * @param null $headers
	 * @return Request
	 */
	public function request($method, $id, $params = null, $uri = null, $headers = null)
    {
		require_once BASEDIR.'/server/protocols/json/Services.php';

		// Make sure the parameters have extra information, so this client is able to roundtrip the interface
		$params = WW_JSON_Services::restructureObjects($params);
        $request = new Request($this->createRequest(RequestInterface::POST, $uri, $headers), $method, $id);
        $request->setRpcField('params', $params);

        $this->prepareRequest($request);

        return $request;
    }
}