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
	 * Retrieve an asset from Elvis server.
	 *
	 * @param string $assetId
	 * @param bool $checkout
	 * @return ElvisEntHit
	 * @throws BizException
	 */
	public function retrieve( $assetId, $checkout )
	{
		require_once __DIR__.'/../model/MetadataHandler.class.php';

		$metadataHandler = new MetadataHandler();
		$metadataToReturn = $metadataHandler->getMetadataToReturn();
		$metadataToReturn[] = 'filename'; // needed to determine mimetype on receive thumb/preview/origin
		$metadataToReturn[] = 'sceId';
		$metadataToReturn[] = 'sceSystemId';
		$metadataToReturn[] = 'resolutionUnit'; // required to convert Elvis resolutionX to Enterprise Dpi

		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		if( $checkout ) {
			$client->checkout( $assetId );
		}
		$stdClassHit = $client->retrieve( $assetId, $metadataToReturn );
		return $this->convertStdClassToElvisEntHit( $stdClassHit );
	}

	/**
	 * Convert a stdClass object into an ElvisEntHit object.
	 *
	 * REST responses from Elvis server are JSON decoded and result into stdClass.
	 * This function can be called to convert it to the real data class ElvisEntHit.
	 *
	 * @param stdClass $stdClassHit
	 * @return ElvisEntHit
	 */
	private function convertStdClassToElvisEntHit( stdClass $stdClassHit )
	{
		/** @var ElvisEntHit $hit */
		$hit = WW_Utils_PHPClass::typeCast( $stdClassHit, 'ElvisEntHit' );
		$hit->metadata = (array)$hit->metadata;
		if( isset( $hit->id ) ) {
			$hit->metadata[ 'id' ] = $hit->id;
		}
		foreach( $hit->metadata as $key => $value ) {
			if( isset( $value->value ) ) {
				$hit->metadata[ $key ] = $value->value;
			}
		}
		$datetimes = array( 'assetCreated', 'assetFileModified', 'fileCreated', 'fileModified' );
		foreach( $datetimes as $datetime ) {
			if( isset( $hit->metadata[ $datetime ] ) ) {
				$hit->metadata[ $datetime ] = $hit->metadata[ $datetime ] / 1000; // msec to sec
			}
		}
		return $hit;
	}

	/**
	 * Copies an asset.
	 *
	 * @param string $assetId
	 * @param string $name
	 * @return string Elvis id of the copied asset
	 * @throws BizException
	 */
	public function copy( $assetId, $name )
	{
		$params = array( $assetId, $name );
		$copyId = null;

		try {
			$copyId = ElvisAMFClient::send( self::SERVICE, 'copy', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}

		return $copyId;
	}

	/**
	 * Copies an asset in Elvis to a pre-defined folder and returns the copy to Enterprise.
	 * The copied asset is already registered as shadow object in Elvis.
	 *
	 * @param string $assetId Id of the original Elvis asset to be copied.
	 * @param string $destFolderPath Path on Elvis where the asset will be copied to.
	 * @param string|null $name The name of the asset. If not set, the value remains empty and Elvis uses the asset filename.
	 * @param string $entSystemId Enterprise system id.
	 * @return ElvisEntHit The copied Elvis asset.
	 * @throws BizException
	 */
	public function copyTo( $assetId, $destFolderPath, $name, $entSystemId )
	{
		ElvisAMFClient::registerClass( ElvisEntHit::getName() );
		ElvisAMFClient::registerClass( ElvisFormattedValue::getName() );
		ElvisAMFClient::registerClass( BasicMap::getName() );
		$resp = null;
		$params = array( $assetId, $destFolderPath, $name, $entSystemId );

		try {
			$resp = ElvisAMFClient::send( self::SERVICE, 'copyTo', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}

		return $resp;
	}

	/**
	 * Lists the versions of an asset.
	 *
	 * @param string $assetId
	 * @return ElvisEntHit[]
	 * @throws BizException
	 */
	public function listVersions( $assetId )
	{
		$client = new Elvis_BizClasses_Client( BizSession::getShortUserName() );
		$stdClassHits = $client->listVersions( $assetId );
		return array_map( array( $this, 'convertStdClassToElvisEntHit' ), $stdClassHits );
	}

	/**
	 * Promotes provided version to head.
	 *
	 * @param string $assetId
	 * @param string $versionNumber
	 * @throws BizException
	 */
	public function promoteVersion( $assetId, $versionNumber )
	{
		$params = array( $assetId, $versionNumber );

		try {
			ElvisAMFClient::send( self::SERVICE, 'promoteVersion', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Returns particular VersionHit object for a given assetId
	 *
	 * @param string $assetId
	 * @param string $versionNumber
	 * @return ElvisEntHit
	 * @throws BizException
	 */
	public function retrieveVersion( $assetId, $versionNumber )
	{
		ElvisAMFClient::registerClass( ElvisEntHit::getName() );
		ElvisAMFClient::registerClass( ElvisFormattedValue::getName() );
		ElvisAMFClient::registerClass( BasicMap::getName() );

		$params = array( $assetId, $versionNumber );
		$resp = null;

		try {
			$resp = ElvisAMFClient::send( self::SERVICE, 'retrieveVersion', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}

		return $resp;
	}

	/**
	 * Does undo check out an asset in Elvis.
	 *
	 * @param string $assetId
	 * @throws BizException
	 */
	public function undoCheckout( $assetId )
	{
		try {
			$params = array( $assetId );
			ElvisAMFClient::send( self::SERVICE, 'undoCheckout', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
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
	 * Deletes given objects.
	 *
	 * @param SabreAMF_ArrayCollection <DeleteObjectOperation> $deleteOperations
	 * @throws BizException
	 */
	public function deleteObjects( $deleteOperations )
	{
		ElvisAMFClient::registerClass( ElvisDeleteObjectOperation::getName() );
		ElvisAMFClient::registerClass( ElvisObjectDescriptor::getName() );

		try {
			$params = array( $deleteOperations );
			ElvisAMFClient::send( self::SERVICE, 'deleteObjects', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Links shadow objects to Elvis assets.
	 *
	 * @param ElvisShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function registerShadowObjects( $shadowObjectIdentity )
	{
		require_once __DIR__.'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

		ElvisAMFClient::registerClass( ElvisShadowObjectIdentity::getName() );

		try {
			$params = array( $shadowObjectIdentity );
			ElvisAMFClient::send( self::SERVICE, 'registerShadowObject', $params );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Un-links shadow objects to Elvis assets.
	 *
	 * @param ElvisShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function unregisterShadowObjects( $shadowObjectIdentity )
	{
		require_once __DIR__.'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

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
