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

	function __construct()
	{
		// Register all possible exceptions for conversion from AMF objects
		ElvisAMFClient::registerClass(ElvisCSException::getName());
		ElvisAMFClient::registerClass(ElvisCSNotFoundException::getName());
		ElvisAMFClient::registerClass(ElvisCSAlreadyExistsException::getName());
		ElvisAMFClient::registerClass(ElvisCSLinkedToOtherSystemException::getName());
		ElvisAMFClient::registerClass(ElvisCSAccessDeniedException::getName());
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
	 * In debug mode, performs a print_r on $transData and logs the service as AMF.
	 *
	 * @param string $methodName   Service method used to give log file a name.
	 * @param string $transData    Transport data to be written in log file using print_r.
	 * @param boolean $isRequest   TRUE to indicate a request, FALSE for a response, or NULL for error.
	 */
	private function logService( $methodName, $transData, $isRequest )
	{
		if( LogHandler::debugMode() ) {
			$dataStream = print_r( $transData, true );
			LogHandler::logService( $methodName, $dataStream, $isRequest, 'AMF' );
		}
	}

	/**
	 * Retrieve asset with given id
	 *
	 * @param $assetId
	 * @param $checkout
	 * @param $metadataToReturn
	 * @return EntHit $resp
	 */
	public function retrieve($assetId, $checkout, $metadataToReturn)
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'ContentSourceService::retrieve - $assetId:' . $assetId);
		ElvisAMFClient::registerClass(ElvisEntHit::getName());
		ElvisAMFClient::registerClass(ElvisFormattedValue::getName());
		ElvisAMFClient::registerClass(BasicMap::getName());

		$params = array($assetId, $checkout, $metadataToReturn);
		$resp = null;

		try {
			self::logService( 'Elvis_retrieve', $params, true );

			$resp = ElvisAMFClient::send(self::SERVICE, 'retrieve', $params, true);

			self::logService( 'Elvis_retrieve', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}

		return $resp;
	}

	/**
	 * Create a new collection
	 *
	 * @param string $name
	 *            The collection name
	 * @return Id of the collection
	 */
	public function createCollection($name)
	{
		$params = array($name);
		$id = null;

		try {
			self::logService( 'Elvis_createCollection', $params, true );

			$id = ElvisAMFClient::send(self::SERVICE, 'createCollection', $params, true);

			self::logService( 'Elvis_createCollection', $id, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}

		return $id;
	}

	/**
	 * Removes an asset.
	 *
	 * @param $assetId
	 */
	public function remove($assetId)
	{
		$params = array($assetId);

		self::logService( 'Elvis_remove', $params, true );

		try {
			$resp = ElvisAMFClient::send(self::SERVICE, 'remove', $params, true);
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}

		self::logService( 'Elvis_remove', $resp, false );
	}

	/**
	 * Copies an asset.
	 *
	 * @param $assetId
	 * @param $name
	 * @return mixed
	 */
	public function copy($assetId, $name)
	{
		$params = array($assetId, $name);
		$copyId = null;

		try {
			self::logService( 'Elvis_copy', $params, true );

			$copyId = ElvisAMFClient::send(self::SERVICE, 'copy', $params, true);

			self::logService( 'Elvis_copy', $copyId, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}

		return $copyId;
	}

	/**
	 * List versions for an asset.
	 *
	 * @param $assetId
	 * @return mixed
	 */
	public function listVersions($assetId)
	{
		ElvisAMFClient::registerClass(ElvisEntHit::getName());
		ElvisAMFClient::registerClass(ElvisFormattedValue::getName());
		ElvisAMFClient::registerClass(BasicMap::getName());
		$params = array($assetId);
		$hits = null;

		try {
			self::logService( 'Elvis_listVersions', $params, true );

			$hits = ElvisAMFClient::send(self::SERVICE, 'listVersions', $params, true);

			self::logService( 'Elvis_listVersions', $hits, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}

		return $hits;
	}

	/**
	 * Promote provided version to head.
	 *
	 * @param $assetId
	 * @param $versionNumber
	 */
	public function promoteVersion($assetId, $versionNumber)
	{
		$params = array($assetId, $versionNumber);

		try {
			self::logService( 'Elvis_promoteVersion', $params, true );

			$resp = ElvisAMFClient::send(self::SERVICE, 'promoteVersion', $params, true);

			self::logService( 'Elvis_promoteVersion', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}


	}

	/**
	 * Returns particular VersionHit object for a given assetId
	 *
	 * @param $assetId
	 * @param $versionNumber
	 * @return mixed
	 */
	public function retrieveVersion($assetId, $versionNumber)
	{
		ElvisAMFClient::registerClass(ElvisEntHit::getName());
		ElvisAMFClient::registerClass(ElvisFormattedValue::getName());
		ElvisAMFClient::registerClass(BasicMap::getName());

		$params = array($assetId, $versionNumber);
		$resp = null;

		try {
			self::logService( 'Elvis_retrieveVersion', $params, true );

			$resp = ElvisAMFClient::send(self::SERVICE, 'retrieveVersion', $params, true);

			self::logService( 'Elvis_retrieveVersion', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}

		return $resp;
	}

	/**
	 * Undo checks out the asset in Elvis.
	 *
	 * @param $assetId
	 */
	public function undoCheckout($assetId)
	{
		try {
			$params = array($assetId);
			self::logService( 'Elvis_undoCheckout', $params, true );

			$resp = ElvisAMFClient::send(self::SERVICE, 'undoCheckout', $params, true);

			self::logService( 'Elvis_undoCheckout', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
	}

	/**
	 * Updates the Enterprise workflow metadata of the asset in Elvis.
	 *
	 * Does nothing if $metadata is empty.
	 *
	 * @param $assetIds Ids of assets in Elvis
	 * @param $metadata Metadata to be updated in Elvis
	 */
	public function updateWorkflowMetadata($assetIds, $metadata)
	{
		try {
			if( !empty( $metadata ) )
			{
				if( is_string( $assetIds ) ) {
					$assetIds = array( $assetIds );
				}
				$params = array($assetIds, $metadata);
				self::logService( 'Elvis_updateWorkflowMetadata', $params, true );
	

				$resp = ElvisAMFClient::send(self::SERVICE, 'updateWorkflowMetadata', $params, true);

				self::logService( 'Elvis_updateWorkflowMetadata', $resp, false );
			}
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
	}

	/**
	 * Update the objects.
	 *
	 * @param SabreAMF_ArrayCollection<UpdateObjectOperation> $updateOperations
	 */
	public function updateObjects($updateOperations)
	{
		ElvisAMFClient::registerClass(ElvisUpdateObjectOperation::getName());
		ElvisAMFClient::registerClass(ElvisPage::getName());
		ElvisAMFClient::registerClass(ElvisPlacement::getName());
		ElvisAMFClient::registerClass(ElvisTarget::getName());
		ElvisAMFClient::registerClass(ElvisEntityDescriptor::getName());
		ElvisAMFClient::registerClass(ElvisObjectDescriptor::getName());
		ElvisAMFClient::registerClass(ElvisObjectRelation::getName());

		try {
			$params = array($updateOperations);
			self::logService( 'Elvis_updateObjects', $updateOperations, true );

			$resp = ElvisAMFClient::send(self::SERVICE, 'updateObjects', $params, true);

			self::logService( 'Elvis_updateObjects', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}


	}

	/**
	 * Delete the objects.
	 *
	 * @param SabreAMF_ArrayCollection<DeleteObjectOperation> $deleteOperations
	 */
	public function deleteObjects($deleteOperations)
	{
		ElvisAMFClient::registerClass(ElvisDeleteObjectOperation::getName());
		ElvisAMFClient::registerClass(ElvisObjectDescriptor::getName());

		try {
			self::logService( 'Elvis_deleteObjects', $deleteOperations, true );

			$params = array($deleteOperations);
			$resp = ElvisAMFClient::send(self::SERVICE, 'deleteObjects', $params, true);

			self::logService( 'Elvis_deleteObjects', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}


	}

	/**
	 * Links shadow objects to Elvis assets.
	 *
	 * @param ShadowObjectIdentity $shadowObjectIdentity
	 */
	public function registerShadowObjects($shadowObjectIdentity)
	{
		require_once dirname(__FILE__) . '/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

		ElvisAMFClient::registerClass(ElvisShadowObjectIdentity::getName());

		try {
			self::logService( 'Elvis_registerShadowObjects', $shadowObjectIdentity, true );

			$params = array($shadowObjectIdentity);
			$resp = ElvisAMFClient::send(self::SERVICE, 'registerShadowObject', $params, true);

			self::logService( 'Elvis_registerShadowObjects', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
	}

	/**
	 * Un-links shadow objects to Elvis assets.
	 *
	 * @param ShadowObjectIdentity $shadowObjectIdentity
	 */
	public function unregisterShadowObjects($shadowObjectIdentity)
	{
		require_once dirname(__FILE__) . '/../model/shadowobject/ElvisShadowObjectIdentity.class.php';

		ElvisAMFClient::registerClass(ElvisShadowObjectIdentity::getName());

		try {
			self::logService( 'Elvis_unregisterShadowObjects', $shadowObjectIdentity, true );

			$params = array($shadowObjectIdentity);
			$resp = ElvisAMFClient::send(self::SERVICE, 'unregisterShadowObject', $params, true);

			self::logService( 'Elvis_unregisterShadowObjects', $resp, false );
		} catch (ElvisCSException $e) {
			// Ignore asset not found exception
			// May result in an asset flagged as used in Enterprise while it was deleted
			// Also ignore "Linked to Other System" error.
			// Both errors should be non fatal when trying to delete a shadow object (otherwise you wouldn't be able to
			// remove a useless Elvis shadow object).
			if ($e instanceof ElvisCSNotFoundException) {
				LogHandler::Log('ELVIS', 'WARN', 'Unable to remove Enterprise system id from metadata for ' . $shadowObjectIdentity->assetId . '. '.
					'Either the asset was removed from Elvis or the user does not have view permissions for this asset.');
			}
			else if( $e instanceof ElvisCSLinkedToOtherSystemException) {
				LogHandler::Log('ELVIS', 'WARN', 'Unable to remove Enterprise system id from metadata for ' . $shadowObjectIdentity->assetId . '. '.
					'The asset is linked to another Enterprise System and should not exist in this system.');
			} else {
				throw $e->toBizException();
			}
		}
	}
	
	/**
	 * Returns asset updates waiting in the queue, using long-polling
	 * 
	 * @param enterpriseSystemId
	 * 			The id identifying the Enterprise server
	 * @param timeout
	 * 			Timeout, in seconds after which the call should return with no updates
	 * @return
	 * 			List of updates
	 */
	public function retrieveAssetUpdates($enterpriseSystemId, $timeout) {
		ElvisAMFClient::registerClass(ElvisEntUpdate::getName());
		ElvisAMFClient::registerClass(BasicMap::getName());
		
		try {
			$params = array($enterpriseSystemId, $timeout);
			self::logService( 'Elvis_retrieveAssetUpdates', $params, true );
			
			// We will max wait the configured timeout + 60 seconds before we expect the AMF call to return
			$httpTimeout = $timeout + 60;
			$resp = ElvisAMFClient::send(self::SERVICE, 'retrieveAssetUpdates', $params, true, $httpTimeout);
		
			self::logService( 'Elvis_retrieveAssetUpdates', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
		return $resp;
	}
	
	/**
	 * Confirm updates so they can be removed from the queue
	 * 
	 * @param updateIds
	 * 			List of update ids to confirm
	 */
	public function confirmAssetUpdates($enterpriseSystemId, $updateIds) {
		try {
			$params = array($enterpriseSystemId, $updateIds);
			self::logService( 'Elvis_confirmAssetUpdates', $params, true );
		
			$resp = ElvisAMFClient::send(self::SERVICE, 'confirmAssetUpdates', $params, true);
		
			self::logService( 'Elvis_confirmAssetUpdates', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
	}
	
	/**
	 * Configures which metadata fields the associated Enterprise server is
	 * interested in. Only updates for these fields will be send to Enterprise
	 *
	 * @param enterpriseSystemId
	 *            The id identifying the Enterprise server
	 * @param fields
	 *            List of Elvis field names
	 */
	public function configureMetadataFields($enterpriseSystemId, $fields) {
		try {
			$params = array($enterpriseSystemId, $fields);
			self::logService( 'Elvis_configureMetadataFields', $params, true );
	
			$resp = ElvisAMFClient::send(self::SERVICE, 'configureMetadataFields', $params, true);
	
			self::logService( 'Elvis_configureMetadataFields', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
	}
	
	/**
	 * Get detailed user information, can only be requested by users with
	 * SUPER_USER permissions
	 * 
	 * @param username
	 *            The username of the user to request the info for
	 * @return User information or null if the user could not be found
	 */
	public function getUserDetails($username) {
		ElvisAMFClient::registerClass(ElvisEntUserDetails::getName());
		
		try {
			$params = array($username);
			self::logService( 'Elvis_getUserDetails', $params, true );
	
			$resp = ElvisAMFClient::send(self::SERVICE, 'getUserDetails', $params, true);
	
			self::logService( 'Elvis_getUserDetails', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
		return $resp;
	}
	
	public function exportOriginalForAsset($assetId) {
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		try {
			$params = array($assetId, HTTP_FILE_TRANSFER_REMOTE_URL, BizSession::getTicket());
			self::logService( 'Elvis_exportOriginal', $params, true );
		
			$resp = ElvisAMFClient::send(self::SERVICE, 'exportOriginal', $params, true);
		
			self::logService( 'Elvis_exportOriginal', $resp, false );
		} catch (ElvisCSException $e) {
			throw $e->toBizException();
		}
		return $resp;
	}
	
}
