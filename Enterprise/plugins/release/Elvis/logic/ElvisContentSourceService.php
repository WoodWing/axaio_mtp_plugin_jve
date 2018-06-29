<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Implements Content Source Service.
 */

/**
 * Interface used by the WoodWing Content Source plugin to perform asset
 * operations.
 */
class ElvisContentSourceService
{
	const SERVICE = 'contentSourceService';

	/** @var string */
	private $shortUserName;

	/** @var Elvis_BizClasses_Client $client */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param string|null $shortUserName Username to use for authorization with Elvis, or NULL to use the session user.
	 * @param Elvis_BizClasses_Client|null $client
	 */
	public function __construct( ?string $shortUserName = null, ?Elvis_BizClasses_Client $client = null )
	{
		if( is_null( $shortUserName ) ) {
			$this->shortUserName = BizSession::isStarted() ? BizSession::getShortUserName() : null;
		} else {
			$this->shortUserName = $shortUserName;
		}
		if( is_null( $client ) ) {
			$this->client = new Elvis_BizClasses_Client( $this->shortUserName );
		} else {
			$this->client = $client;
		}
	}

	/**
	 * Create a new asset at the Elvis server.
	 *
	 * @since 10.5.0
	 * @param array $metadata Metadata to be updated in Elvis
	 * @param Attachment|null $fileToUpload
	 * @return Elvis_DataClasses_EntHit
	 */
	public function create( array $metadata, ?Attachment $fileToUpload ) : Elvis_DataClasses_EntHit
	{
		$metadataToReturn = $this->getMetadataToReturn();
		$stdClassHit = $this->client->create( (object)$metadata, $metadataToReturn, $fileToUpload );
		return Elvis_DataClasses_EntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Update an asset at the Elvis server.
	 *
	 * @since 10.5.0
	 * @param string $assetId
	 * @param array $metadata Metadata to be updated in Elvis
	 * @param Attachment|null $fileToUpload
	 * @param bool $undoCheckout
	 * @return Elvis_DataClasses_EntHit
	 */
	public function update( string $assetId, array $metadata, ?Attachment $fileToUpload, bool $undoCheckout ) : Elvis_DataClasses_EntHit
	{
		$metadataToReturn = $this->getMetadataToReturn();
		$stdClassHit = $this->client->update( $assetId, (object)$metadata, $metadataToReturn, $fileToUpload, $undoCheckout );
		return Elvis_DataClasses_EntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Lock an asset for editing in Elvis server.
	 *
	 * @since 10.5.0
	 * @param string $assetId
	 */
	public function checkout( string $assetId ) : void
	{
		$this->client->checkout( $assetId );
	}

	/**
	 * Release the edit lock for an asset in Elvis server.
	 *
	 * @param string $assetId
	 */
	public function undoCheckout( string $assetId ) : void
	{
		$this->client->undoCheckout( $assetId );
	}

	/**
	 * Retrieve an asset from Elvis server.
	 *
	 * @param string $assetId
	 * @param bool $checkOut
	 * @param array $extraFields Aside to the standard fields, more fields can be requested e.g. for testing purposes.
	 * @return Elvis_DataClasses_EntHit
	 */
	public function retrieve( string $assetId, bool $checkOut = false, array $extraFields = array() ) : Elvis_DataClasses_EntHit
	{
		$metadataToReturn = $this->getMetadataToReturn();
		if( $extraFields ) {
			$metadataToReturn = array_merge( $metadataToReturn, $extraFields );
		}
		$stdClassHit = $this->client->retrieve( $assetId, $checkOut, $metadataToReturn );
		return Elvis_DataClasses_EntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Compose list of Elvis metadata properties to retrieve for an asset.
	 *
	 * @since 10.5.0
	 * @return string[]
	 */
	private function getMetadataToReturn() : array
	{
		$metadataHandler = new Elvis_BizClasses_Metadata();
		$metadataToReturn = $metadataHandler->getMetadataToReturn();
		$metadataToReturn[] = 'filename'; // needed to determine mimetype on receive thumb/preview/origin
		$metadataToReturn[] = 'sceId';
		$metadataToReturn[] = 'sceSystemId';
		$metadataToReturn[] = 'resolutionUnit'; // required to convert Elvis resolutionX to Enterprise Dpi
		return $metadataToReturn;
	}

	/**
	 * Copies an asset.
	 *
	 * @param string $assetId
	 * @param string $name
	 * @return string Elvis id of the copied asset
	 */
	public function copy( string $assetId, string $name ) : string
	{
		return $this->client->copy( $assetId, $name )->id;
	}

	/**
	 * Copy an asset in Elvis to a pre-defined folder and return the copy to Enterprise.
	 *
	 * The copied asset is already registered as shadow object in Elvis.
	 *
	 * @param string $assetId Id of the original Elvis asset to be copied.
	 * @param string $destFolderPath Path on Elvis where the asset will be copied to.
	 * @param string|null $name The name of the asset. If not set, the value remains empty and Elvis uses the asset filename.
	 * @param string $entSystemId Enterprise system id.
	 * @return Elvis_DataClasses_EntHit The copied Elvis asset.
	 */
	public function copyTo( string $assetId, string $destFolderPath, ?string $name, string $entSystemId ): Elvis_DataClasses_EntHit
	{
		$stdClassHit = $this->client->copyTo( $assetId, $destFolderPath, $name, $entSystemId );
		return Elvis_DataClasses_EntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Updates the Enterprise workflow metadata of the asset in Elvis.
	 *
	 * Does nothing if $metadata is empty.
	 *
	 * @param string[] $assetIds Ids of assets in Elvis
	 * @param array $metadata Metadata to be updated in Elvis
	 * @throws BizException
	 */
	public function updateWorkflowMetadata( array $assetIds, array $metadata ): void
	{
		if( empty( $metadata ) ) return;
		$this->client->updateWorkflowMetadata( $assetIds, $metadata );
	}

	/**
	 * Updates given assets.
	 *
	 * @param ElvisUpdateObjectOperation[] $updateOperations
	 * @throws BizException
	 */
	public function updateAssetRelations( array $updateOperations ) : void
	{
		if( empty( $updateOperations ) ) return;
		$this->client->updateAssetRelations( $updateOperations );
	}

	/**
	 * Request Elvis to delete relations with child assets.
	 *
	 * For example, when a shadow image is removed from a layout, relations will be removed from Enterprise
	 * side. This function is then called to let Elvis remove the corresponding relations for its assets.
	 *
	 * @param Elvis_DataClasses_DeleteObjectRelationOperation[] $deleteOperations
	 * @throws BizException
	 */
	public function deleteAssetRelations( array $deleteOperations ) : void
	{
		if( empty( $deleteOperations ) ) return;
		$this->client->deleteAssetRelations( $deleteOperations );
	}

	/**
	 * Link a shadow object to an Elvis asset.
	 *
	 * @param Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function registerShadowObjects( Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity ): void
	{
		$this->client->registerShadowObjects( $shadowObjectIdentity );
	}

	/**
	 * Un-link a shadow object from an Elvis asset.
	 *
	 * @param Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function unregisterShadowObjects( Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity ): void
	{
		$this->client->unregisterShadowObjects( $shadowObjectIdentity );
	}

	/**
	 * Return asset updates that are waiting in the Elvis queue, using long-polling.
	 *
	 * @param int $operationTimeout The operation timeout of the asset updates in seconds.
	 * @return Elvis_DataClasses_AssetUpdate[]
	 */
	public function retrieveAssetUpdates( int $operationTimeout ) : array
	{
		$updatesStdClasses = $this->client->retrieveAssetUpdates( BizSession::getEnterpriseSystemId(), $operationTimeout );
		return array_map( array( 'Elvis_DataClasses_AssetUpdate', 'fromStdClass' ), $updatesStdClasses );
	}

	/**
	 * Confirm asset updates so they can be removed from the queue in Elvis.
	 *
	 * @param string[] $updateIds List of update ids to confirm.
	 */
	public function confirmAssetUpdates( array $updateIds ) : void
	{
		$enterpriseSystemId = BizSession::getEnterpriseSystemId();
		$this->client->confirmAssetUpdates( $enterpriseSystemId, $updateIds );
	}

	/**
	 * Configure which metadata fields the associated Enterprise server is interested in.
	 * Only updates for these fields will be send to Enterprise.
	 *
	 * @param string[] List of Elvis field names
	 */
	public function configureMetadataFields( array $fields ) : void
	{
		$enterpriseSystemId = BizSession::getEnterpriseSystemId();
		$this->client->configureMetadataFields( $enterpriseSystemId, $fields );
	}

	/**
	 * Retrieve detailed user information.
	 *
	 * @param string $username The username of the user to request the info for.
	 * @return Elvis_DataClasses_EntUserDetails The user information.
	 */
	public function getUserDetails( string $username ) : Elvis_DataClasses_EntUserDetails
	{
		require_once __DIR__.'/../config.php'; // ELVIS_SUPER_USER

		$client = new Elvis_BizClasses_Client( ELVIS_SUPER_USER );
		$stdClassUserDetails = $client->getUserDetails( $username );
		return Elvis_DataClasses_EntUserDetails::fromStdClass( $stdClassUserDetails );
	}
}
