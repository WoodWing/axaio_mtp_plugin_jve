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
	 * Create a new asset at the Elvis server.
	 *
	 * @param array $metadata Metadata to be updated in Elvis
	 * @param string[] $metadataToReturn
	 * @param Attachment $fileToUpload
	 * @return stdClass representation of ElvisEntHit
	 * @throws BizException
	 */
	public function create( array $metadata, array $metadataToReturn, Attachment $fileToUpload ) : stdClass
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/create' );

		$request = new Elvis_BizClasses_ClientRequest( 'services/create', $this->shortUserName );
		$request->addQueryParamAsJson( 'metadata', $metadata );
		$request->addCsvQueryParam( 'metadataToReturn', $metadataToReturn );
		$request->setHttpPostMethod();
		$request->setExpectJson();
		$request->addFileToUpload( $fileToUpload );

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Checkout an asset at the Elvis server.
	 *
	 * @param string $assetId
	 * @throws BizException
	 */
	public function checkout( string $assetId )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/checkout - assetId:'.$assetId );

		$request = new Elvis_BizClasses_ClientRequest( 'services/checkout', $this->shortUserName );
		$request->addPathParam( $assetId );

		$client = new Elvis_BizClasses_CurlClient();
		$client->execute( $request );
	}

	/**
	 * Retrieve an asset from the Elvis server.
	 *
	 * @param string $assetId
	 * @param string[] $metadataToReturn
	 * @return stdClass representation of ElvisEntHit
	 * @throws BizException
	 */
	public function retrieve( string $assetId, array $metadataToReturn ) : stdClass
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/retrieve - assetId:'.$assetId );

		$request = new Elvis_BizClasses_ClientRequest( 'services/search', $this->shortUserName );
		$request->addSearchQueryParam( 'q', 'id', $assetId );
		$request->addCsvQueryParam( 'metadataToReturn', $metadataToReturn );
		$request->setExpectJson();

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		$body = $response->jsonBody();
		if( !isset( $body->hits[0] ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Server', 'Elvis assetId: ' . $assetId, null, null, 'INFO' );
		}
		return $body->hits[0];
	}

	/**
	 * Retrieve versions of an asset from the Elvis server.
	 *
	 * @param string $assetId
	 * @return stdClass[] representation of ElvisEntHit[]
	 * @throws BizException
	 */
	public function listVersions( string $assetId ) : array
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/asset/history - assetId:'.$assetId );

		$request = new Elvis_BizClasses_ClientRequest( 'services/asset/history', $this->shortUserName );
		$request->addQueryParam( 'id', $assetId );
		$request->addQueryParam( 'detailLevel', 1 );
		$request->setExpectJson();

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		$body = $response->jsonBody();
		if( !isset( $body->hits[0] ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Server', 'Elvis assetId: ' . $assetId, null, null, 'INFO' );
		}
		return array_map( function ( $hit ) { return $hit->hit; }, $body->hits );
	}
}