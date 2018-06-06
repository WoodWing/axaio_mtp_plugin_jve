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
	 * @param stdClass $metadata Metadata to be updated in Elvis
	 * @param string[] $metadataToReturn
	 * @param Attachment|null $fileToUpload
	 * @return stdClass representation of ElvisEntHit
	 */
	public function create( stdClass $metadata, array $metadataToReturn, $fileToUpload ) : stdClass
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/create' );

		$request = new Elvis_BizClasses_ClientRequest( 'services/create', $this->shortUserName );
		$request->addPostParamAsJson( 'metadata', $metadata );
		$request->addCsvPostParam( 'metadataToReturn', $metadataToReturn );
		$request->setHttpPostMethod();
		$request->setExpectJson();
		if( !is_null( $fileToUpload ) ) {
			$request->addFileToUpload( $fileToUpload );
		}

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Update an asset at the Elvis server.
	 *
	 * @param string $assetId
	 * @param stdClass $metadata Metadata to be updated in Elvis
	 * @param string[] $metadataToReturn
	 * @param Attachment|null $fileToUpload
	 * @param bool $undoCheckout
	 * @return stdClass representation of ElvisEntHit
	 */
	public function update( string $assetId, stdClass $metadata, array $metadataToReturn, $fileToUpload, bool $undoCheckout ) : stdClass
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/update' );

		$request = new Elvis_BizClasses_ClientRequest( 'services/update', $this->shortUserName );
		$request->addPostParam( 'id', $assetId );
		$request->addPostParamAsJson( 'metadata', $metadata );
		$request->addPostParam( 'clearCheckoutState', $undoCheckout ? 'true' : 'false' );
		$request->addCsvPostParam( 'metadataToReturn', $metadataToReturn );
		$request->setHttpPostMethod();
		$request->setExpectJson();
		if( !is_null( $fileToUpload ) ) {
			$request->addFileToUpload( $fileToUpload );
		}

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Update multiple assets in Elvis server for the provided metadata.
	 *
	 * @param string[] $assetIds
	 * @param array $metadata Changed asset metadata
	 */
	public function updateBulk( array $assetIds, $metadata )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/updatebulk' );

		$request = new Elvis_BizClasses_ClientRequest( 'services/updatebulk', $this->shortUserName );
		$request->addSearchPostParam( 'q', 'id', $assetIds );
		$request->addPostParamAsJson( 'metadata', $metadata );
		$request->setHttpPostMethod();
		$request->setExpectJson();

		$client = new Elvis_BizClasses_CurlClient();
		$client->execute( $request );
	}

	/**
	 * Checkout an asset at the Elvis server.
	 *
	 * @param string $assetId
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
	 * Undo checkout an asset at the Elvis server.
	 *
	 * @param string $assetId
	 */
	public function undoCheckout( string $assetId )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::services/undocheckout - assetId:'.$assetId );

		$request = new Elvis_BizClasses_ClientRequest( 'services/undocheckout', $this->shortUserName );
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
		$request->addSearchQueryParam( 'q', 'id', array( $assetId ) );
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
		if( !isset( $body->hits ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Server', 'Elvis assetId: ' . $assetId, null, null, 'INFO' );
		}
		return array_map( function ( $hit ) { return $hit->hit; }, $body->hits );
	}
}