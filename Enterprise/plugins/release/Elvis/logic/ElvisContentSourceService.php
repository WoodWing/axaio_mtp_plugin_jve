<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Implements Content Source Service.
 */

require_once dirname(__FILE__) . '/ElvisAMFClient.php';
require_once dirname(__FILE__) . '/../model/ElvisSearchResponse.php';
require_once dirname(__FILE__) . '/../model/ElvisFormattedValue.php';
require_once dirname(__FILE__) . '/../model/BasicMap.php';
require_once dirname(__FILE__) . '/../model/ElvisEntHit.php';
require_once dirname(__FILE__) . '/../model/ElvisEntUpdate.php';
require_once dirname(__FILE__) . '/../model/ElvisEntUserDetails.php';
require_once dirname(__FILE__) . '/../model/ElvisCSException.php';
require_once dirname(__FILE__) . '/../model/ElvisCSNotFoundException.php';
require_once dirname(__FILE__) . '/../model/ElvisCSAlreadyExistsException.php';
require_once dirname(__FILE__) . '/../model/ElvisCSLinkedToOtherSystemException.php';
require_once dirname(__FILE__) . '/../model/ElvisCSAccessDeniedException.php';
require_once dirname(__FILE__) . '/../model/relation/operation/ElvisUpdateObjectOperation.php';
require_once dirname(__FILE__) . '/../model/relation/operation/ElvisDeleteObjectOperation.php';
require_once dirname(__FILE__) . '/../model/relation/operation/ElvisObjectRelation.php';
require_once dirname(__FILE__) . '/../model/relation/operation/ElvisPlacement.php';
require_once dirname(__FILE__) . '/../model/relation/operation/ElvisPage.php';
require_once dirname(__FILE__) . '/../model/relation/operation/ElvisTarget.php';
require_once dirname(__FILE__) . '/../model/relation/operation/ElvisObjectDescriptor.php';

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

//	/**
//	 * Search Elvis
//	 *
//	 * @param string $query
//	 * @param array $metadataToReturn
//	 * @param int $firstResult
//	 * @param int $maxResultHits
//	 * @return SearchResponse
//	 */
//	public function search($query, $metadataToReturn, $firstResult=null, $maxResultHits=null) {
//		Code currently not used
//		LogHandler::Log('ELVIS', 'DEBUG', 'ContentSourceService::search - query:' . $query . '; metadataToReturn:' . print_r($metadataToReturn, true) . '; firstResult:' . $firstResult . '; maxResultHits:' . $maxResultHits);
//
//		ElvisAMFClient::registerClass(ElvisSearchResponse::getName());
//		ElvisAMFClient::registerClass(HitElement::getName());
//		ElvisAMFClient::registerClass(ElvisFormattedValue::getName());
//		ElvisAMFClient::registerClass(BasicMap::getName());
//		$params = array($query, $metadataToReturn, $firstResult, $maxResultHits);
//		$searchResponse = ElvisAMFClient::send(self::SERVICE, 'search', $params, true);
//		return $searchResponse;
//	}

	/**
	 * Retrieves an asset.
	 *
	 * @param string $assetId
	 * @param bool $checkout
	 * @param string[] $metadataToReturn
	 * @return ElvisEntHit
	 * @throws BizException
	 */
	public function retrieve( $assetId, $checkout, $metadataToReturn )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSourceService::retrieve - $assetId:'.$assetId );
		ElvisAMFClient::registerClass( ElvisEntHit::getName() );
		ElvisAMFClient::registerClass( ElvisFormattedValue::getName() );
		ElvisAMFClient::registerClass( BasicMap::getName() );

		$params = array( $assetId, $checkout, $metadataToReturn );
		$resp = null;

		try {
			$resp = ElvisAMFClient::send( self::SERVICE, 'retrieve', $params, true );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}

		return $resp;
	}

	/**
	 * Creates a new collection
	 *
	 * @param string $name The collection name
	 * @return string Id of the collection
	 * @throws BizException
	 */
	public function createCollection( $name )
	{
		$params = array( $name );
		$id = null;

		try {
			$id = ElvisAMFClient::send( self::SERVICE, 'createCollection', $params, true );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}

		return $id;
	}

	/**
	 * Removes an asset.
	 *
	 * @param string $assetId
	 * @throws BizException
	 */
	public function remove( $assetId )
	{
		$params = array( $assetId );

		try {
			ElvisAMFClient::send( self::SERVICE, 'remove', $params, true );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
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
			$copyId = ElvisAMFClient::send( self::SERVICE, 'copy', $params, true );
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
	 * @param string $entSystemId Enterprise system id.
	 * @return ElvisEntHit The copied Elvis asset.
	 * @throws BizException
	 */
	public function copyTo( $assetId, $destFolderPath, $entSystemId )
	{
		ElvisAMFClient::registerClass( ElvisEntHit::getName() );
		ElvisAMFClient::registerClass( ElvisFormattedValue::getName() );
		ElvisAMFClient::registerClass( BasicMap::getName() );
		$resp = null;
		$params = array( $assetId, $destFolderPath, $entSystemId );

		try {
			$resp = ElvisAMFClient::send( self::SERVICE, 'copyTo', $params, true );
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
		ElvisAMFClient::registerClass( ElvisEntHit::getName() );
		ElvisAMFClient::registerClass( ElvisFormattedValue::getName() );
		ElvisAMFClient::registerClass( BasicMap::getName() );
		$params = array( $assetId );
		$hits = null;

		try {
			$hits = ElvisAMFClient::send( self::SERVICE, 'listVersions', $params, true );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}

		return $hits;
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
			ElvisAMFClient::send( self::SERVICE, 'promoteVersion', $params, true );
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
			$resp = ElvisAMFClient::send( self::SERVICE, 'retrieveVersion', $params, true );
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
			ElvisAMFClient::send( self::SERVICE, 'undoCheckout', $params, true );
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
				ElvisAMFClient::send( self::SERVICE, 'updateWorkflowMetadata', $params, true );
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
			ElvisAMFClient::send( self::SERVICE, 'updateObjects', $params, true );
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
			ElvisAMFClient::send( self::SERVICE, 'deleteObjects', $params, true );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Links shadow objects to Elvis assets.
	 *
	 * @param ShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function registerShadowObjects( $shadowObjectIdentity )
	{
		require_once dirname( __FILE__ ).'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

		ElvisAMFClient::registerClass( ElvisShadowObjectIdentity::getName() );

		try {
			$params = array( $shadowObjectIdentity );
			ElvisAMFClient::send( self::SERVICE, 'registerShadowObject', $params, true );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}

	/**
	 * Un-links shadow objects to Elvis assets.
	 *
	 * @param ShadowObjectIdentity $shadowObjectIdentity
	 * @throws BizException
	 */
	public function unregisterShadowObjects( $shadowObjectIdentity )
	{
		require_once dirname( __FILE__ ).'/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

		ElvisAMFClient::registerClass( ElvisShadowObjectIdentity::getName() );

		try {
			$params = array( $shadowObjectIdentity );
			ElvisAMFClient::send( self::SERVICE, 'unregisterShadowObject', $params, true );
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
	 * @param int Timeout in seconds after which the call should return with no updates
	 * @return ElvisEntUpdate[] Updates
	 * @throws BizException
	 */
	public function retrieveAssetUpdates( $enterpriseSystemId, $timeout )
	{
		ElvisAMFClient::registerClass( ElvisEntUpdate::getName() );
		ElvisAMFClient::registerClass( BasicMap::getName() );

		try {
			$params = array( $enterpriseSystemId, $timeout );

			// We will max wait the configured timeout + 60 seconds before we expect the AMF call to return
			$httpTimeout = $timeout + 60;
			$resp = ElvisAMFClient::send( self::SERVICE, 'retrieveAssetUpdates', $params, true, $httpTimeout );
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
			ElvisAMFClient::send( self::SERVICE, 'confirmAssetUpdates', $params, true );
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
			ElvisAMFClient::send( self::SERVICE, 'configureMetadataFields', $params, true );
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
			$resp = ElvisAMFClient::send( self::SERVICE, 'getUserDetails', $params, true );
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
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		try {
			$params = array( $assetId, HTTP_FILE_TRANSFER_REMOTE_URL, BizSession::getTicket() );
			$resp = ElvisAMFClient::send( self::SERVICE, 'exportOriginal', $params, true );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
		return $resp;
	}
}
