<?php
/**
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Implements Content Source Service.
 */

require_once __DIR__.'/ElvisAMFClient.php';
require_once __DIR__.'/../model/ElvisSearchResponse.php';
require_once __DIR__.'/../model/ElvisFormattedValue.php';
require_once __DIR__.'/../model/BasicMap.php';
require_once __DIR__.'/../model/ElvisEntHit.php';
require_once __DIR__.'/../model/ElvisEntUpdate.php';
require_once __DIR__.'/../model/ElvisEntUserDetails.php';
require_once __DIR__.'/../model/ElvisCSException.php';
require_once __DIR__.'/../model/ElvisCSNotFoundException.php';
require_once __DIR__.'/../model/ElvisCSAlreadyExistsException.php';
require_once __DIR__.'/../model/ElvisCSLinkedToOtherSystemException.php';
require_once __DIR__.'/../model/ElvisCSAccessDeniedException.php';
require_once __DIR__.'/../model/relation/operation/ElvisUpdateObjectOperation.php';
require_once __DIR__.'/../model/relation/operation/ElvisDeleteObjectOperation.php';
require_once __DIR__.'/../model/relation/operation/ElvisObjectRelation.php';
require_once __DIR__.'/../model/relation/operation/ElvisPlacement.php';
require_once __DIR__.'/../model/relation/operation/ElvisPage.php';
require_once __DIR__.'/../model/relation/operation/ElvisTarget.php';
require_once __DIR__.'/../model/relation/operation/ElvisObjectDescriptor.php';

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

		// Register all possible exceptions for conversion from AMF objects
		ElvisAMFClient::registerClass( ElvisCSException::getName() );
		ElvisAMFClient::registerClass( ElvisCSNotFoundException::getName() );
		ElvisAMFClient::registerClass( ElvisCSAlreadyExistsException::getName() );
		ElvisAMFClient::registerClass( ElvisCSLinkedToOtherSystemException::getName() );
		ElvisAMFClient::registerClass( ElvisCSAccessDeniedException::getName() );
	}

	/**
	 * Create a new asset at the Elvis server.
	 *
	 * @since 10.5.0
	 * @param array $metadata Metadata to be updated in Elvis
	 * @param Attachment|null $fileToUpload
	 * @return ElvisEntHit
	 */
	public function create( array $metadata, ?Attachment $fileToUpload ) : ElvisEntHit
	{
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$metadataToReturn = $this->getMetadataToReturn();
		$stdClassHit = $this->client->create( (object)$metadata, $metadataToReturn, $fileToUpload );
		return ElvisEntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Update an asset at the Elvis server.
	 *
	 * @since 10.5.0
	 * @param string $assetId
	 * @param array $metadata Metadata to be updated in Elvis
	 * @param Attachment|null $fileToUpload
	 * @param bool $undoCheckout
	 * @return ElvisEntHit
	 */
	public function update( string $assetId, array $metadata, ?Attachment $fileToUpload, bool $undoCheckout ) : ElvisEntHit
	{
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$metadataToReturn = $this->getMetadataToReturn();
		$stdClassHit = $this->client->update( $assetId, (object)$metadata, $metadataToReturn, $fileToUpload, $undoCheckout );
		return ElvisEntHit::fromStdClass( $stdClassHit );
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
	 * @return ElvisEntHit
	 */
	public function retrieve( string $assetId, bool $checkOut = false ) : ElvisEntHit
	{
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$metadataToReturn = $this->getMetadataToReturn();
		$stdClassHit = $this->client->retrieve( $assetId, $checkOut, $metadataToReturn );
		return ElvisEntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Compose list of Elvis metadata properties to retrieve for an asset.
	 *
	 * @since 10.5.0
	 * @return string[]
	 */
	private function getMetadataToReturn() : array
	{
		require_once __DIR__.'/../model/MetadataHandler.class.php';

		$metadataHandler = new MetadataHandler();
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
		return $this->client->copy( $assetId, $name );
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
	 * @return ElvisEntHit The copied Elvis asset.
	 */
	public function copyTo( string $assetId, string $destFolderPath, string $name, string $entSystemId ) : ElvisEntHit
	{
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$stdClassHit = $this->client->copyTo( $assetId, $destFolderPath, $name, $entSystemId );
		return ElvisEntHit::fromStdClass( $stdClassHit );
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
	 * Updates given objects.
	 *
	 * @param SabreAMF_ArrayCollection <UpdateObjectOperation> $updateOperations
	 * @throws BizException
	 */
	public function updateObjects( $updateOperations ) : void
	{
		ElvisAMFClient::registerClass( ElvisUpdateObjectOperation::getName() );
		ElvisAMFClient::registerClass( ElvisPage::getName() );
		ElvisAMFClient::registerClass( ElvisPlacement::getName() );
		ElvisAMFClient::registerClass( ElvisTarget::getName() );
		ElvisAMFClient::registerClass( ElvisEntityDescriptor::getName() );
		ElvisAMFClient::registerClass( ElvisObjectDescriptor::getName() );
		ElvisAMFClient::registerClass( ElvisObjectRelation::getName() );

		try {
			$params = array( $updateOperations );
			ElvisAMFClient::send( self::SERVICE, 'updateObjects', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Delete given assets.
	 *
	 * @param ElvisDeleteObjectOperation[] $deleteOperations
	 * @throws BizException
	 */
	public function deleteObjects( array $deleteOperations ) : void
	{
		if( true ) {
			ElvisAMFClient::registerClass( ElvisDeleteObjectOperation::getName() );
			ElvisAMFClient::registerClass( ElvisObjectDescriptor::getName() );

			try {
				$params = array( $deleteOperations );
				ElvisAMFClient::send( self::SERVICE, 'deleteObjects', $params );
			} catch( ElvisCSException $e ) {
				throw $e->toBizException();
			}
		} else { // TODO: replace the entire function body with the else-part below once this problem is solved:
						// {"errorname":"MethodArgumentConversionNotSupportedException","message":"Failed to convert value of type
						// [java.lang.String] to required type [java.util.List]; nested exception is java.lang.IllegalStateException:
						// Cannot convert value of type [java.lang.String] to required type [com.ds.acm.api.contentsource.model.operation.DeleteObjectOperation]:
						// no matching editors or conversion strategy found","errorcode":500}
				$this->client->deleteObjects( $deleteOperations );
		}
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
	 * @return ElvisEntUpdate[]
	 */
	public function retrieveAssetUpdates( int $operationTimeout ) : array
	{
		require_once __DIR__.'/../model/ElvisEntUpdate.php';

		$updatesStdClasses = $this->client->retrieveAssetUpdates( BizSession::getEnterpriseSystemId(), $operationTimeout );
		return array_map( array( 'ElvisEntUpdate', 'fromStdClass' ), $updatesStdClasses );
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
	 * @return ElvisEntUserDetails The user information.
	 */
	public function getUserDetails( string $username ) : ElvisEntUserDetails
	{
		require_once __DIR__.'/../config.php'; // ELVIS_SUPER_USER
		require_once __DIR__.'/../model/ElvisEntUserDetails.php';

		$client = new Elvis_BizClasses_Client( ELVIS_SUPER_USER );
		$stdClassUserDetails = $client->getUserDetails( $username );
		return ElvisEntUserDetails::fromStdClass( $stdClassUserDetails );
	}

	/**
	 * Export the original for a given asset.
	 *
	 * @param string $assetId
	 * @return string File URL
	 * @throws BizException
	 */
	public function exportOriginalForAsset( string $assetId ) : string
	{
		try {
			$params = array( $assetId, HTTP_FILE_TRANSFER_REMOTE_URL, BizSession::getTicket() );
			$resp = ElvisAMFClient::send( self::SERVICE, 'exportOriginal', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
		return $resp;
	}
}
