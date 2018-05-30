<?php
/**
 * Client class providing Elvis web services. It talks with Elvis server over the REST API.
 *
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.*
 */

class Elvis_BizClasses_Client
{
	/** @var string */
	private $shortUserName;

	/**
	 * Elvis_BizClasses_ProxyClient constructor.
	 *
	 * @param string $shortUserName
	 */
	public function __construct( $shortUserName )
	{
		$this->shortUserName = $shortUserName;
	}

	/**
	 * Checkout an asset at Elvis server.
	 *
	 * @param string $assetId
	 * @throws BizException
	 */
	public function checkout( string $assetId )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/checkout - $assetId:'.$assetId );

		$request = new Elvis_BizClasses_ClientRequest( 'services/checkout' );
		$request->addPathParam( $assetId );
		$request->setUserShortName( $this->shortUserName );

		$client = new Elvis_BizClasses_CurlClient();
		$client->execute( $request );
	}

	/**
	 * Retrieve an asset from Elvis server.
	 *
	 * @param string $assetId
	 * @param string[] $metadataToReturn
	 * @return stdClass representation of ElvisEntHit
	 * @throws BizException
	 */
	public function retrieve( string $assetId, array $metadataToReturn )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/retrieve - $assetId:'.$assetId );

		$request = new Elvis_BizClasses_ClientRequest( 'services/search' );
		$request->addSearchQueryParam( 'q', 'id', $assetId );
		$request->addCsvQueryParam( 'metadataToReturn', $metadataToReturn );
		$request->setUserShortName( $this->shortUserName );
		$request->setExpectJson();

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		$body = $response->jsonBody();
		if( !isset( $body->hits[0] ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Server', 'Elvis assetId: ' . $assetId, null, null, 'INFO' );
		}
		return $body->hits[0];
	}
}