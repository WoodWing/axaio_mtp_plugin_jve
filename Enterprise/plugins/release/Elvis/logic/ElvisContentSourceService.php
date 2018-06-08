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

	public function __construct()
	{
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
	public function create( array $metadata, $fileToUpload ) : ElvisEntHit
	{
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$metadataToReturn = $this->getMetadataToReturn();
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHit = $client->create( (object)$metadata, $metadataToReturn, $fileToUpload );
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
	public function update( string $assetId, array $metadata, $fileToUpload, bool $undoCheckout ) : ElvisEntHit
	{
		require_once __DIR__.'/../model/ElvisEntHit.php';

		$metadataToReturn = $this->getMetadataToReturn();
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHit = $client->update( $assetId, (object)$metadata, $metadataToReturn, $fileToUpload, $undoCheckout );
		return ElvisEntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Lock an asset for editing in Elvis server.
	 *
	 * @since 10.5.0
	 * @param string $assetId
	 */
	public function checkout( $assetId )
	{
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$client->checkout( $assetId );
	}

	/**
	 * Release the edit lock for an asset in Elvis server.
	 *
	 * @param string $assetId
	 */
	public function undoCheckout( $assetId )
	{
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$client->undoCheckout( $assetId );
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
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHit = $client->retrieve( $assetId, $checkOut, $metadataToReturn );
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
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		return $client->copy( $assetId, $name );
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

		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHit = $client->copyTo( $assetId, $destFolderPath, $name, $entSystemId );
		return ElvisEntHit::fromStdClass( $stdClassHit );
	}

	/**
	 * Updates the Enterprise workflow metadata of the asset in Elvis.
	 *
	 * Does nothing if $metadata is empty.
	 *
	 * @param string|string[] $assetIds Ids of assets in Elvis
	 * @param array $metadata Metadata to be updated in Elvis
	 * @throws BizException
	 */
	public function updateWorkflowMetadata( $assetIds, $metadata )
	{
		try {
			if( !empty( $metadata ) ) {
				if( is_string( $assetIds ) ) {
					$assetIds = array( $assetIds );
				}
				$params = array( $assetIds, $metadata );
				ElvisAMFClient::send( self::SERVICE, 'updateWorkflowMetadata', $params );
			}
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Updates given objects.
	 *
	 * @param SabreAMF_ArrayCollection <UpdateObjectOperation> $updateOperations
	 * @throws BizException
	 */
	public function updateObjects( $updateOperations )
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
	public function deleteObjects( array $deleteOperations )
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
			$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
			$client->deleteObjects( $deleteOperations );
		}
	}

	/**
	 * Link a shadow object to an Elvis asset.
	 *
	 * @param ElvisShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function registerShadowObjects( ElvisShadowObjectIdentity $shadowObjectIdentity )
	{
		require_once __DIR__.'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

		if( true ) {
			ElvisAMFClient::registerClass( ElvisShadowObjectIdentity::getName() );
			try {
				$params = array( $shadowObjectIdentity );
				ElvisAMFClient::send( self::SERVICE, 'registerShadowObject', $params );
			} catch( ElvisCSException $e ) {
				throw $e->toBizException();
			}
		} else { // TODO: replace the entire function body with the else-part below [PD-60]
			$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
			$client->registerShadowObjects( $shadowObjectIdentity );
		}
	}

	/**
	 * Un-link a shadow object from an Elvis asset.
	 *
	 * @param ElvisShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function unregisterShadowObjects( ElvisShadowObjectIdentity $shadowObjectIdentity )
	{
		require_once __DIR__.'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

		if( true ) {
			ElvisAMFClient::registerClass( ElvisShadowObjectIdentity::getName() );
			try {
				$params = array( $shadowObjectIdentity );
				ElvisAMFClient::send( self::SERVICE, 'unregisterShadowObject', $params );
			} catch( ElvisCSException $e ) {
				// Ignore asset not found exception
				// May result in an asset flagged as used in Enterprise while it was deleted
				// Also ignore "Linked to Other System" error.
				// Both errors should be non fatal when trying to delete a shadow object (otherwise you wouldn't be able to
				// remove a useless Elvis shadow object).
				if( $e instanceof ElvisCSNotFoundException ) {
					LogHandler::Log( 'ELVIS', 'WARN', 'Unable to remove Enterprise system id from metadata for '.$shadowObjectIdentity->assetId.'. '.
						'Either the asset was removed from Elvis or the user does not have view permissions for this asset.' );
				} else if( $e instanceof ElvisCSLinkedToOtherSystemException ) {
					LogHandler::Log( 'ELVIS', 'WARN', 'Unable to remove Enterprise system id from metadata for '.$shadowObjectIdentity->assetId.'. '.
						'The asset is linked to another Enterprise System and should not exist in this system.' );
				} else {
					throw $e->toBizException();
				}
			}
		} else { // TODO: replace the entire function body with the else-part below [PD-60]
			$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
			$client->unregisterShadowObjects( $shadowObjectIdentity );
		}
	}

	/**
	 * Returns asset updates waiting in the queue, using long-polling
	 *
	 * @param string $enterpriseSystemId The id identifying the Enterprise server
	 * @param int $operationTimeout The operation timeout of the asset updates in seconds.
	 * @return ElvisEntUpdate[] Updates
	 * @throws BizException
	 */
	public function retrieveAssetUpdates( $enterpriseSystemId, $operationTimeout )
	{
		ElvisAMFClient::registerClass( ElvisEntUpdate::getName() );
		ElvisAMFClient::registerClass( BasicMap::getName() );

		try {
			$params = array( $enterpriseSystemId, $operationTimeout );

			// We will max wait the configured timeout + 60 seconds before we expect the AMF call to return
			$operationTimeout = $operationTimeout + 60;
			$resp = ElvisAMFClient::send( self::SERVICE, 'retrieveAssetUpdates', $params, $operationTimeout );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
		return $resp;
	}

	/**
	 * Confirm updates so they can be removed from the queue
	 *
	 * @param string $enterpriseSystemId The id identifying the Enterprise server
	 * @param string[] $updateIds List of update ids to confirm
	 * @throws BizException
	 */
	public function confirmAssetUpdates( $enterpriseSystemId, $updateIds )
	{
		try {
			$params = array( $enterpriseSystemId, $updateIds );
			ElvisAMFClient::send( self::SERVICE, 'confirmAssetUpdates', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Configures which metadata fields the associated Enterprise server is
	 * interested in. Only updates for these fields will be send to Enterprise
	 *
	 * @param string $enterpriseSystemId The id identifying the Enterprise server
	 * @param string[] List of Elvis field names
	 * @throws BizException
	 */
	public function configureMetadataFields( $enterpriseSystemId, $fields )
	{
		try {
			$params = array( $enterpriseSystemId, $fields );
			ElvisAMFClient::send( self::SERVICE, 'configureMetadataFields', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Gets detailed user information.
	 *
	 * Can only be requested by users with SUPER_USER permissions
	 *
	 * @param string $username The username of the user to request the info for
	 * @return ElvisEntUserDetails User information or null if the user could not be found
	 * @throws BizException
	 */
	public function getUserDetails( $username )
	{
		ElvisAMFClient::registerClass( ElvisEntUserDetails::getName() );

		try {
			$params = array( $username );
			$resp = ElvisAMFClient::send( self::SERVICE, 'getUserDetails', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
		return $resp;
	}

	/**
	 * Export the original for a given asset.
	 *
	 * @param string $assetId
	 * @return string File URL
	 * @throws BizException
	 */
	public function exportOriginalForAsset( $assetId )
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
