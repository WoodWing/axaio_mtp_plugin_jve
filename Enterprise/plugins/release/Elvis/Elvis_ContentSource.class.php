<?php
/**
 * Elvis Content Source connector. Creates, updates and deletes shadow objects.
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once __DIR__.'/config.php'; // Elvis config file
require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';

class Elvis_ContentSource extends ContentSource_EnterpriseConnector
{
	/** @var MetadataHandler */
	private $metadataHandler;

	/**
	 * @return MetadataHandler
	 */
	private function getMetadataHandler()
	{
		require_once __DIR__.'/model/MetadataHandler.class.php';
		if( !isset($this->metadataHandler) ) {
			$this->metadataHandler = new MetadataHandler();
		}
		return $this->metadataHandler;
	}
	
	/**
	 * @inheritdoc
	 */
	final public function getContentSourceId()
	{
		return ELVIS_CONTENTSOURCEID;
	}
	
	/**
	 * @inheritdoc
	 */
	final public function getQueries()
	{
		return array(); // Elvis content source does not support named queries
	}
	
	/**
	 * @inheritdoc
	 */
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		return null; // Elvis content source does not support named queries
	}

	/**
	 * @inheritdoc
	 */
	final public function getAlienObject( $alienId, $rendition, $lock )
	{
		require_once __DIR__.'/util/ElvisUtils.class.php';
		require_once __DIR__.'/logic/ElvisContentSourceService.php';

		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getAlienObject called for alienId:' . $alienId . '; rendition:' . $rendition . '; lock:' . $lock );

		$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$service = new ElvisContentSourceService();
		$hit = $service->retrieve( $assetId, $lock );

		$object = new Object();
		$object->MetaData = new MetaData();
		$object->Relations = array();
		if( $rendition ) {
			$object->Files = $this->getFiles( $hit, array( $rendition ) );
		}
		
		$this->fillMetadata( $object, $hit );
		
		return $object;
	}
	
	/**
	 * @inheritdoc
	 */
	public function getShadowObject( $alienId, &$object, $objprops, $lock, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObject called for alienId:' . $alienId . '; lock:' . $lock . '; rendition:' . $rendition );
		$this->getShadowObject2( $alienId, $object, $objprops, $lock, $rendition, null );
	}

	/**
	 * @inheritdoc
	 *
	 * Meta data is all set already, access rights have been set etc.
	 * All that is required is filling in the files for the requested object.
	 * This will only be done when the version of the files is newer than the haveVersion.
	 * Furthermore the meta data can be adjusted if needed.
	 * Only users who have no restricted access to the Elvis Content Source are allowed to lock the shadow object.
	 * See EN-36871.
	 * If Files is null, Enterprise will fill in the files
	 */
	public function getShadowObject2( $alienId, &$object, $objprops, $lock, $rendition, $haveVersion )
	{
		require_once __DIR__.'/logic/ElvisContentSourceService.php';
		require_once __DIR__.'/util/ElvisUtils.class.php';

		LogHandler::Log ( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObject2 called for alienId:' . $alienId . '; lock:' . $lock . '; rendition:' . $rendition );

		$this->checkUserEditRight( $lock, $rendition );
		$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$service = new ElvisContentSourceService();
		$hit = $service->retrieve( $assetId, $lock );

		$hasSceId = array_key_exists('sceId', $hit->{'metadata'}) && empty($hit->{'metadata'}['sceId']);
		if( !$hasSceId ) {
			$hit->{'metadata'}['sceId'] = $object->MetaData->BasicMetaData->ID; // needed by getFiles()
		}

		if( $rendition ) {
			if( !$haveVersion || version_compare( $haveVersion, ElvisUtils::getEnterpriseVersionNumber( $hit->metadata['versionNumber'] ), '<' ) ) {
				$object->Files = $this->getFiles( $hit, array( $rendition ) );
			}
		}
		$this->getMetadataHandler()->read( $object, $hit->metadata );

		/*
		 * When creating a new shadow object some metadata needs to be set from Enterprise -> Elvis. However,
		 * this data is not available in i.e. createShadowObjects. The object in Enterprise is created at a later stage.
		 * Currently there is no proper way of setting the Enterprise object ID, so it is set here (once).
		 * There are way more scenario's to trigger this method except for creating a new shadow object, so check if the
		 * Enterprise object Id is already set, if not, only then update the metadata.  
		 */
		if( !$hasSceId ) {
			LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObject2 setting Enterprise metadata.' );

			$elvisMetadata = $this->fillElvisEnterpriseMetadata( $object->MetaData );
			$elvisMetadata['sceId'] = $objprops['ID'];
			$elvisMetadata['sceCreated'] = $objprops['Created'];
			$elvisMetadata['sceCreator'] = $objprops['Creator'];
			$elvisMetadata['sceModified'] = $objprops['Modified'];
			$elvisMetadata['sceModifier'] = $objprops['Modifier'];

			$service = new ElvisContentSourceService();
			$service->updateWorkflowMetadata( $assetId, $elvisMetadata );
		}
	}

	/**
	 * Checks if the user is allowed to open a shadow object for editing. If not, an exception is thrown.
	 *
	 * @param bool $lock Object is locked.
	 * @param string $rendition Requested rendition.
	 * @throws BizException
	 */
	private function checkUserEditRight( $lock, $rendition )
	{
		if ( $lock && $rendition == 'native' ) {
			require_once __DIR__.'/util/ElvisSessionUtil.php';
			$restricted = ElvisSessionUtil::getRestricted();
			// L> since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
			if ( $restricted ) {
				throw new BizException( 'ERR_AUTHORIZATION', 'Client' );
			}
		}
	}

	/**
	 * @inheritdoc
	 * @throws BizException in case mode is Copy_To_Production_Zone and no production zone is found.
	 */
	final public function createShadowObject( $alienId, $destObject )
	{
		require_once __DIR__.'/util/ElvisUtils.class.php';
		require_once __DIR__.'/logic/ElvisContentSourceService.php';

		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::createShadowObject called for alienId:' . $alienId );

		$service = new ElvisContentSourceService();
		$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );

		if( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' ) {
			require_once __DIR__.'/util/ElvisBrandAdminConfig.class.php';
			$productionZone = ElvisBrandAdminConfig::getProductionZoneByPubId( $destObject->MetaData->BasicMetaData->Publication->Id );

			if( $productionZone ) {
				$productionZone = ElvisBrandAdminConfig::substituteDateInProductionZone( $productionZone );

				$hit = $service->copyTo( $assetId, $productionZone, $destObject->MetaData->BasicMetaData->Name, BizSession::getEnterpriseSystemId() );
			} else {
				throw new BizException( 'ERR_INVALID_PROPERTY', 'Server', 'Unable to find the production zone property.' );
			}
		} else {
			$hit = $service->retrieve( $assetId );

			// Register shadow object in Elvis. Throws BizException if not possible (e.g. already linked)
			require_once __DIR__.'/logic/ElvisObjectManager.php';
			$systemId = BizSession::getEnterpriseSystemId();
			ElvisObjectManager::registerShadowObject( $assetId, $systemId );
		}

		if( !$destObject ) {
			$destObject = new Object();
			$destObject->MetaData = new MetaData();
			$destObject->Relations = array();
		}
		$this->fillMetadata( $destObject, $hit );
		$destObject->Files = array();

		// Elvis communicates the UI dimensions of images to Enterprise server, which means that the orientation is already
		// applied to the height and width. Since Enterprise Server uses the orientation to calculate the dimensions on
		// the fly, we need to revert the height and width to their pre-orientation values in the case of rotation (orientation = 5-8).
		if( $destObject->MetaData->ContentMetaData->Orientation && $destObject->MetaData->ContentMetaData->Orientation > 4 ) {
			$width = $destObject->MetaData->ContentMetaData->Width;
			$height = $destObject->MetaData->ContentMetaData->Height;
			$destObject->MetaData->ContentMetaData->Height = $width;
			$destObject->MetaData->ContentMetaData->Width = $height;
		}

		return $destObject;
	}

	/**
	 * @inheritdoc
	 */
	final public function createCopyObject( $alienId, $destObject )
	{
		require_once __DIR__.'/util/ElvisUtils.class.php';
		require_once __DIR__.'/logic/ElvisContentSourceService.php';

		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::createCopyObject called for alienId:' . $alienId );

		$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$service = new ElvisContentSourceService();
		$hit = $service->retrieve( $assetId );

		// Export original asset file to Enterprise
		$fileUrl = $service->exportOriginalForAsset( $assetId );
		
		if( !$destObject ) {
			$destObject = new Object();
			$destObject->MetaData = new MetaData();
			$destObject->Relations = array();
		}
		$this->fillMetadata($destObject, $hit);
		
		// Remove Elvis related metadata
		$destObject->MetaData->BasicMetaData->DocumentID = null;
		$destObject->MetaData->BasicMetaData->ContentSource = null;
		
		$destObject->Files = array( self::createAttachment( $fileUrl, $destObject->MetaData->ContentMetaData->Format ) );
		$destObject->Files = array_merge( $destObject->Files, $this->getFiles( $hit, array( 'preview', 'thumb' ) ) );

		return $destObject;
	}

	/**
	 * Compose a file attachment data object.
	 *
	 * @param string $fileUrl
	 * @param string $type
	 * @return Attachment
	 */
	private function createAttachment( $fileUrl, $type )
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		
		// Create File attachment
		$attachment = new Attachment();
		$attachment->FileUrl = $fileUrl;
		$attachment->Rendition = "native";
		$attachment->Type = $type;
		
		// Change FileUrl to FilePath
		$transferServer = new BizTransferServer();
		$transferServer->urlToFilePath($attachment);
		
		return $attachment;
	}

	/**
	 * Multi version of createShadowObject, but never called by Enterprise.
	 * Just here to suppress warning in health test.
	 *
	 * @param string[] $alienIds
	 * @return array|Object[]
	 */
	public function createShadowObjects( $alienIds )
	{
		$destObjects = array();
		foreach( $alienIds as $alienId ) {
			$destObjects[] = $this->createShadowObject( $alienId, null );
		}
		return $destObjects;
	}
	
	/**
	 * @inheritdoc
	 */
	public function saveShadowObject( $alienId, &$object )
	{
		require_once __DIR__.'/util/ElvisUtils.class.php';

		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::saveShadowObject called for alienId:' . $alienId );

		$elvisId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		
		// upload original to Elvis
		if( $object->Files ) {
			$undoCheckout = ElvisUtils::saveObjectsDoesReleaseObjectLock();
			foreach( $object->Files as $file ) {
				if( $file->Rendition == 'native' && $file->FilePath != null ) {
					$this->getMetadataHandler()->update( $elvisId, $object->MetaData, $file, $undoCheckout );
				}
			}
		}
		$object->Files = null;
	}
	
	/**
	 * @inheritdoc
	 */
	public function setShadowObjectProperties( $alienId, &$object )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::setShadowObjectProperties called for alienId:'.$alienId );

		if( $this->triggeredBySyncJob() ) {
			// Update coming from Elvis Sync job, prevent update round-trips
			return;
		}

		// This function is indirectly called when exporting shadow objects from Elvis
		// At this point there is no active session and the write can safely be ignored.
		require_once __DIR__.'/util/ElvisUtils.class.php';
		require_once __DIR__.'/util/ElvisObjectUtils.class.php';
		require_once __DIR__.'/logic/ElvisContentSourceService.php';

		$elvisId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$service = new ElvisContentSourceService();

		// Revert smart connection checkout when changing properties
		if( ElvisUtils::isSmartConnection() ) {
			$service->undoCheckout( $elvisId );
		}

		if( !ElvisObjectUtils::isArchivedStatus( $object->MetaData->WorkflowMetaData->State->Name ) ) {
			// Normally all metadata is set using the REST client. These are set using
			// the permissions of the current user. However there are some specific
			// Enterprise metadata properties defined in Elvis, which needs to be set
			// whatever permission the current user has, so these are set using AMF; this
			// only set sce prefixed metadata properties as a super user.
			$elvisMetadata = $this->fillElvisEnterpriseMetadata( $object->MetaData );

			$elvisMetadata['sceModified'] = $object->MetaData->WorkflowMetaData->Modified;
			$elvisMetadata['sceModifier'] = $object->MetaData->WorkflowMetaData->Modifier;
			$elvisMetadata['sceArchivedInEnterprise'] = "false";

			$service->updateWorkflowMetadata( $elvisId, $elvisMetadata );
			$this->getMetadataHandler()->update( $elvisId, $object->MetaData );
		} else {
			$elvisMetadata = array();
			$elvisMetadata['sceArchivedInEnterprise'] = "true";
			$service->updateWorkflowMetadata( $elvisId, $elvisMetadata );
		}
	}


	/**
	 * @inheritdoc
	 */
	public function multiSetShadowObjectProperties( $shadowObjectIds, $metaDataValues )
	{
		if( $this->triggeredBySyncJob() ) {
			// Update coming from Elvis Sync job, prevent update round-trips
			return;
		}

		// 9.2.0 bug: shadowObjectIds key should be the CS ID, but instead is index 0.
		$shadowObjectIds = isset( $shadowObjectIds[ ELVIS_CONTENTSOURCEID ] ) ? $shadowObjectIds[ ELVIS_CONTENTSOURCEID ] : $shadowObjectIds[0];
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::multiSetShadowObjectProperties '.
			'called for $shadowObjectIds:'.implode(',', $shadowObjectIds ) );

		// This function is indirectly called when exporting shadow objects from Elvis
		// At this point there is no active session and the write can safely be ignored.
		require_once __DIR__.'/util/ElvisUtils.class.php';
		require_once __DIR__.'/logic/ElvisContentSourceService.php';
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';

		// Add Category name if needed. Normally only the Ids are sent, but we want to display the name in Elvis too.
		$metaDataCategoryId = $this->findMetaDataByField( 'CategoryId', $metaDataValues );
		if( $metaDataCategoryId != null && $this->findMetaDataByField( 'Category', $metaDataValues ) == null ) {
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'Category';
			$propValue = new PropertyValue();
			$propValue->Value = DBSection::getSectionName( $this->getFirstMetaDataValue( $metaDataCategoryId ) );
			$mdValue->PropertyValues = array( $propValue );
			$metaDataValues[] = $mdValue;
		}

		// Collect Elvis asset ids
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$assetIds = array();
		$rows = DBObject::getColumnsValuesForObjectIds( $shadowObjectIds, array( 'Workflow' ), array( 'id', 'documentid' ) );
		foreach( $rows as $row ) {
			$assetIds[] = $row[ 'documentid' ];
		}
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::multiSetShadowObjectProperties called '.
			'for alienIds [ '.implode( ', ', $assetIds ).' ]' );

		// Normally all metadata is set using the REST client. These are set using
		// the permissions of the current user. However there are some specific
		// Enterprise metadata properties defined in Elvis, which needs to be set
		// whatever permission the current user has, so these are set using AMF; this
		// only set sce prefixed metadata properties as a super user.
		$elvisMetadata = $this->fillElvisEnterpriseMetadataMulti( $metaDataValues );
		$service = new ElvisContentSourceService();
		$service->updateWorkflowMetadata( $assetIds, $elvisMetadata );

		// Bulk REST client update
		$this->getMetadataHandler()->updateBulk( $assetIds, $metaDataValues );
	}

	/**
	 * @inheritdoc
	 */
	public function deleteShadowObject( $alienId, $shadowId, $permanent, $restore )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::deleteShadowObject called for alienId:' . $alienId .
								'; shadowId:' . $shadowId . '; permanent:' . $permanent . '; restore:' . $restore );

		if( $this->triggeredBySyncJob() ) {
			// Update coming from Elvis Sync job, prevent update round-trips
			return;
		}
		
		require_once __DIR__.'/util/ElvisUtils.class.php';
		require_once __DIR__.'/logic/ElvisObjectManager.php';

		// Only remove the system id in Elvis when the asset is completely removed in Enterprise (removed from the trash)
		$elvisId = ElvisUtils::getAssetIdFromAlienId( $alienId );
		$systemId = BizSession::getEnterpriseSystemId();
		if( !$restore ) {
			try {
				ElvisObjectManager::unregisterShadowObject( $elvisId, $systemId );
			} catch ( Exception $exception ) {
				LogHandler::Log( 'ELVIS', 'WARN', 'Unable to unregister asset in Elvis: ' . $exception );
			}
		} else {
			ElvisObjectManager::registerShadowObject( $elvisId, $systemId );
		}
	}
	
	/**
	 * @inheritdoc
	 */
	public function listShadowObjectVersions( $alienId, $shadowId, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::listShadowObjectVersions called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; rendition:' . $rendition );

		require_once __DIR__.'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		$elvisAssetVersions = $versionHandler->listVersions( $alienId, $rendition );

		require_once __DIR__.'/util/ElvisObjectUtils.class.php';
		$elvisAssetVersions = ElvisObjectUtils::setVersionStatusFromEnterprise( $shadowId, $elvisAssetVersions );
		return $elvisAssetVersions;
	}

	/**
	 * @inheritdoc
	 */
	public function getShadowObjectVersion( $alienId, $shadowId, $version, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObjectVersion called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; version:' . $version . '; rendition:' . $rendition);

		require_once __DIR__.'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		return $versionHandler->retrieveVersion( $alienId, $version, $rendition );
	}

	/**
	 * @inheritdoc
	 */
	public function restoreShadowObjectVersion( $alienId, $shadowId, $version )
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'ContentSource::restoreShadowObjectVersion called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; version:' . $version);

		require_once __DIR__.'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		$versionHandler->promoteVersion( $alienId, $version );
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function copyShadowObject( $alienId, $srcObject, $destObject )
	{
		$shadowObject = null;
		switch( ELVIS_CREATE_COPY ) {
			case 'Hard_Copy_To_Enterprise':
			case 'Shadow_Only':
				require_once __DIR__.'/util/ElvisUtils.class.php';
				require_once __DIR__.'/logic/ElvisContentSourceService.php';

				$service = new ElvisContentSourceService();
				$destName = $destObject->MetaData->BasicMetaData->Name;
				$assetId = ElvisUtils::getAssetIdFromAlienId( $alienId );
				$copyId = $service->copy( $assetId, $destName );
				$destId = ElvisUtils::getAlienIdFromAssetId( $copyId );
				LogHandler::Log( 'ContentSource', 'DEBUG', 'ContentSource::copyShadowObject called for alienId:' . $alienId . ', destId:' . $destId );
				$shadowObject = $this->createShadowObject( $destId, $destObject );
				break;

			case 'Copy_To_Production_Zone':
				LogHandler::Log( 'ContentSource', 'DEBUG', 'ContentSource::copyShadowObject called for alienId:' . $alienId . '.' );
				$shadowObject = $this->createShadowObject( $alienId, $destObject );
				break;
		}

		return $shadowObject;
	}

	/**
	 * @inheritdoc
	 */
	public static function checkAccessForAlien( $user, $right,
			$brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
			$alienId, $contentSource, $documentId )
	{
		return true;
	}
	
	/**
	 * @inheritdoc
	 */
	public static function checkAccessForShadow( $user, $right,
			$brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
			$shadowId, $contentSource, $documentId )
	{
		return null;
	}
	
	/**
	 * @inheritdoc
	 */
	public static function completeUser( AdmUser $user )
	{
		require_once __DIR__.'/util/ElvisUtils.class.php';
		return ElvisUtils::enrichUser( $user );
	}

	/**
	 * @inheritdoc
	 */
	public function isContentSourceFileLinksSupported()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getContentSourceProxyLinksBaseUrl()
	{
		require_once __DIR__.'/config.php';
		return ELVIS_CONTENTSOURCE_PUBLIC_PROXYURL;
	}

	/**
	 * Populate array with default Enterprise metadata fields required for Elvis.
	 *
	 * @param MetaData $metadata Enterprise metadata object
	 * @return array $elvisMetaData Map containing Enterprise metadata properties which are always synchronized to Elvis
	 */
	private function fillElvisEnterpriseMetadata( MetaData $metadata ) : array
	{
		// Note that when client requests for NoMetaData, the Publication and Category are not set.
		// This happens e.g. when clicking an object in CS in the dossier view having the Versions pane open.
		$elvisMetadata = array();
		if( isset( $metadata->BasicMetaData->Category ) ) {
			$elvisMetadata['sceCategoryId'] = $metadata->BasicMetaData->Category->Id;
			$elvisMetadata['sceCategory'] = $metadata->BasicMetaData->Category->Name;
		}
		if( isset( $metadata->BasicMetaData->Publication ) ) {
			$elvisMetadata['scePublicationId'] = $metadata->BasicMetaData->Publication->Id;
			$elvisMetadata['scePublication'] = $metadata->BasicMetaData->Publication->Name;
		}
		return $elvisMetadata;
	}

	/**
	 * @param MetaDataValue[] $metadataValues
	 * @return array
	 */
	private function fillElvisEnterpriseMetadataMulti( array $metadataValues ) : array
	{
		$elvisMetadata = array();
		foreach( $metadataValues as $metadataValue ) {
			switch( $metadataValue->Property ) {
				case 'Category':
				case 'CategoryId':
				case 'Publication':
				case 'PublicationId':
				case 'Modified':
				case 'Modifier':
					$elvisMetadata['sce'.$metadataValue->Property] = $this->getFirstMetaDataValue( $metadataValue );
				default:
			}
		}

		return $elvisMetadata;
	}

	/**
	 * Compose Attachments from an Elvis hit based for given file renditions.
	 *
	 * @param ElvisEntHit $hit
	 * @param string[] $renditions
	 * @return Attachment[]
	 */
	private function getFiles( ElvisEntHit $hit, array $renditions ) : array
	{
		if( $this->isContentSourceFileLinksRequested() ) {
			$fileLinkType = 'ContentSourceFileLink';
		} elseif( $this->isContentSourceProxyLinksRequested() ) {
			$fileLinkType = 'ContentSourceProxyLink';
		} else {
			$fileLinkType = 'FileUrl';
		}

		require_once __DIR__.'/util/ElvisUtils.class.php';
		$files = array();
		foreach( $renditions as $rendition ) {
			$file = ElvisUtils::getAttachment( $hit, $rendition, $fileLinkType );
			if( $file ) {
				$files[] = $file;
			}
		}
		return $files;
	}

	/**
	 * @param Object $smartObject Object of MetaData that will filled
	 * @param ElvisEntHit $hit returned from elvis server
	 */
	private function fillMetadata( $smartObject, $hit )
	{
		$this->getMetadataHandler()->read( $smartObject, $hit->metadata );

		//Note: we are not handling state here, it will be processed by Enterprise if needed
	}

	/**
	 * Finds metadata for a given field.
	 *
	 * @param string $fieldToIndex Name of the field
	 * @param MetaDataValue[] $metaDataValues contains metadata values of the object
	 * @return metaDataValue found metaDataValue if any. Null if not found.
	 */
	private function findMetaDataByField( $fieldToIndex, $metaDataValues )
	{
		foreach( $metaDataValues as $metaDataValue ) {
			if( $metaDataValue->Property == $fieldToIndex ) {
				return $metaDataValue;
			}
		}

		return null;
	}

	/**
	 * Helper function to get the list of values from a metaDataValue.
	 *
	 * @param MetaDataValue $metaDataValue Metadata value structure of which the first value needs to be retrieved
	 * @return mixed The metadata value
	 */
	private function getFirstMetaDataValue( MetaDataValue $metaDataValue )
	{
		if( !is_null($metaDataValue->Values) ) {
			return $metaDataValue->Values[0];
		} else {
			return $metaDataValue->PropertyValues[0]->Value;
		}
	}

	/**
	 * To determine if the current process is an 'ElvisSync' service call.
	 *
	 * @return bool Returns true when it is an 'ElvisSync' service call, false otherwise.
	 */
	private function triggeredBySyncJob() 
	{
		return (BizSession::getServiceName() == 'ElvisSync');
	}

	/**
	 * To determine if a copy of the image should always be created at the external content source.
	 *
	 * {@inheritdoc}
	 *
	 * @since 10.1.3
	 * @return bool Returns true to always create new instance, false(default) to create only one time.
	 */
	public function willAlwaysCreateACopyForImage()
	{
		return ELVIS_CREATE_COPY_WHEN_MOVED_FROM_PRODUCTION_ZONE ? true : false;
	}

	/**
	 * Returns the renditions stored by Elvis.
	 *
	 * @inheritDoc
	 *
	 * @since 10.1.4
	 * @return array Stored renditions.
	 */
	public function storedRenditionTypes()
	{
		return array( 'native', 'preview', 'thumb' );
	}
}
