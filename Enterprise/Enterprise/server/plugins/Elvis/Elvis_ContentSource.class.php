<?php
/**
 * Elvis Content Source connector. Creates, updates and deletes shadow objects.
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/config/config_elvis.php'; // auto-loading
require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';

class Elvis_ContentSource extends ContentSource_EnterpriseConnector
{
	/** @var Elvis_BizClasses_Metadata */
	private $metadataHandler;

	/**
	 * @return Elvis_BizClasses_Metadata
	 */
	private function getMetadataHandler()
	{
		if( !isset($this->metadataHandler) ) {
			$this->metadataHandler = new Elvis_BizClasses_Metadata();
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
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getAlienObject called for alienId:' . $alienId . '; rendition:' . $rendition . '; lock:' . $lock );

		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$service = new Elvis_BizClasses_AssetService();
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
		LogHandler::Log ( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObject2 called for alienId:' . $alienId . '; lock:' . $lock . '; rendition:' . $rendition );

		$this->checkUserEditRight( $object->MetaData->BasicMetaData->ID, $lock, $rendition );
		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$service = new Elvis_BizClasses_AssetService();
		$hit = $service->retrieve( $assetId, $lock );

		$hasSceId = array_key_exists('sceId', $hit->{'metadata'}) && empty($hit->{'metadata'}['sceId']);
		if( !$hasSceId ) {
			$hit->{'metadata'}['sceId'] = $object->MetaData->BasicMetaData->ID; // needed by getFiles()
		}

		if( $rendition ) {
			if( !$haveVersion || version_compare( $haveVersion, Elvis_BizClasses_Version::getEnterpriseObjectVersionNumber( $hit->metadata['versionNumber'] ), '<' ) ) {
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

			$service = new Elvis_BizClasses_AssetService();
			$service->updateWorkflowMetadata( array( $assetId ), $elvisMetadata );
		}
	}

	/**
	 * Check if the user is allowed to open a shadow object for editing. If not, the access denied exception (S1002) is thrown.
	 *
	 * Users that do not have a license for Elvis are allowed to see assets from Elvis in Enterprise, but not allowed to
	 * edit them. For example, this allows those users to open a layout for editing that has placed Elvis images. However,
	 * they are not allowed to select such image and perform the Edit Original action in InDesign.
	 *
	 * In other words, this is a kind of license check to make sure we don't give away too much functionality to
	 * customers that did not buy Elvis seats for their Enterprise users. To make it harder to hack this limitation,
	 * all PHP files involved are ionCube Encoded. See Build/auto_build.sh.
	 *
	 * @param string $objectId
	 * @param bool $lock Requested for a lock for editing.
	 * @param string $rendition Requested rendition.
	 * @throws BizException when the user has no rights to edit/lock Elvis assets.
	 */
	private function checkUserEditRight( $objectId, $lock, $rendition )
	{
		if( $lock && $rendition == 'native' ) {
			$restricted = Elvis_BizClasses_UserSetting::getRestricted();
			// L> since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
			if( $restricted ) {
				require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
				$serverJob = DBTicket::getContextualServerJob();
				if( $serverJob ) {
					require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
					require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
					$bizJobConfig = new BizServerJobConfig();
					$jobConfig = $bizJobConfig->findJobConfig( $serverJob->JobType, $serverJob->ServerType );
					$userShortName = isset( $jobConfig->UserId ) ? DBUser::getShortNameByUserDbId( $jobConfig->UserId ) : '';
					LogHandler::Log( 'ELVIS', 'WARN',
						'A job of type '.$serverJob->JobType.' running in background tries to obtain a lock for '.
						'editing for an object (id='.$objectId.'). This happened to be a shadow object linked to an Elvis asset. '.
						'Because the user "'.$userShortName.'" configured to execute the job is unknown to Elvis, the '.
						'ELVIS_DEFAULT_USER is selected to authorize at Elvis Server. That user is restricted and can read '.
						'assets but can not edit assets. For optimization purposes of the Elvis integration, this restriction '.
						'is remembered for the user configured for the job. To solve this problem, please add the user to '.
						'Elvis and manually login with that user in Enterprise.'
					);
				}
				throw new BizException( 'ERR_AUTHORIZATION', 'Client', "{$objectId}(E)",
					null, null, 'INFO' );
			}
		}
	}

	/**
	 * @inheritdoc
	 * @throws BizException in case mode is Copy_To_Production_Zone and no production zone is found.
	 */
	final public function createShadowObject( $alienId, $destObject )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::createShadowObject called for alienId:' . $alienId );

		$service = new Elvis_BizClasses_AssetService();
		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );

		if( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' ) {
			$productionZone = Elvis_BizClasses_BrandAdminConfig::getProductionZoneByPubId( $destObject->MetaData->BasicMetaData->Publication->Id );
			if( $productionZone ) {
				$productionZone = Elvis_BizClasses_BrandAdminConfig::substituteDateInProductionZone( $productionZone );
				$hit = $service->copyTo( $assetId, $productionZone, $destObject->MetaData->BasicMetaData->Name, BizSession::getEnterpriseSystemId() );
			} else {
				throw new BizException( 'ERR_INVALID_PROPERTY', 'Server', 'Unable to find the production zone property.' );
			}
		} else {
			LogHandler::Log( 'ELVIS', 'DEBUG', 'Register shadow object in Elvis for assetId:'.$assetId );
			$hit = $service->retrieve( $assetId );
			$systemId = BizSession::getEnterpriseSystemId();
			$shadowObjectIdentity = new Elvis_DataClasses_ShadowObjectIdentity( $systemId, $assetId );
			$service->registerShadowObjects( $shadowObjectIdentity );
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
	 * Make a hard copy of an Elvis asset to an Enterprise object. Do not created a shadow link between both.
	 *
	 * @param string $alienId
	 * @param Object $destObject
	 * @return Object
	 */
	public function createCopyObject( $alienId, $destObject )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::createCopyObject called for alienId:' . $alienId );

		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$service = new Elvis_BizClasses_AssetService();
		$hit = $service->retrieve( $assetId );

		if( !$destObject ) {
			$destObject = new Object();
			$destObject->MetaData = new MetaData();
			$destObject->Relations = array();
		}
		$this->fillMetadata($destObject, $hit);
		
		// Remove Elvis related metadata
		$destObject->MetaData->BasicMetaData->DocumentID = null;
		$destObject->MetaData->BasicMetaData->ContentSource = null;
		
		$destObject->Files = $this->getFiles( $hit, array( 'native', 'preview', 'thumb' ) );

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
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::saveShadowObject called for alienId:' . $alienId );

		$elvisId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		
		// upload original to Elvis
		if( $object->Files ) {
			$undoCheckout = Elvis_BizClasses_ObjectLock::saveObjectsDoesReleaseObjectLock();
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
		$elvisId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$service = new Elvis_BizClasses_AssetService();

		// Revert smart connection checkout when changing properties
		if( WW_Utils_ClientApplication::isSmartConnection() ) {
			$service->undoCheckout( $elvisId );
		}

		if( !Elvis_BizClasses_Object::isArchivedStatus( $object->MetaData->WorkflowMetaData->State->Name ) ) {
			// Normally all metadata is set using the REST client. These are set using the permissions of the current user.
			// However there are some specific Enterprise metadata properties defined in Elvis, which needs to be set whatever
			// permission the current user has; this only set sce prefixed metadata properties as an ELVIS_DEFAULT_USER user.
			$elvisMetadata = $this->fillElvisEnterpriseMetadata( $object->MetaData );

			$elvisMetadata['sceModified'] = $object->MetaData->WorkflowMetaData->Modified;
			$elvisMetadata['sceModifier'] = $object->MetaData->WorkflowMetaData->Modifier;
			$elvisMetadata['sceArchivedInEnterprise'] = "false";

			$service->updateWorkflowMetadata( array( $elvisId ), $elvisMetadata );
			$this->getMetadataHandler()->update( $elvisId, $object->MetaData );
		} else {
			$elvisMetadata = array();
			$elvisMetadata['sceArchivedInEnterprise'] = "true";
			$service->updateWorkflowMetadata( array( $elvisId ), $elvisMetadata );
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

		// Normally all metadata is set using the REST client. These are set using the permissions of the current user.
		// However there are some specific Enterprise metadata properties defined in Elvis, which needs to be set whatever
		// permission the current user has; this only set sce prefixed metadata properties as an ELVIS_DEFAULT_USER user.
		$elvisMetadata = $this->fillElvisEnterpriseMetadataMulti( $metaDataValues );
		$service = new Elvis_BizClasses_AssetService();
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
		
		// Only remove the system id in Elvis when the asset is completely removed in Enterprise (removed from the trash)
		$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
		$systemId = BizSession::getEnterpriseSystemId();
		if( $assetId && $systemId ) {
			$service = new Elvis_BizClasses_AssetService();
			$shadowObjectIdentity = new Elvis_DataClasses_ShadowObjectIdentity( $systemId, $assetId );
			if( !$restore ) {
				try {
					$service->unregisterShadowObjects( $shadowObjectIdentity );
				} catch( Exception $exception ) {
					LogHandler::Log( 'ELVIS', 'WARN', 'Unable to unregister asset in Elvis: '.$exception );
				}
			} else {
				$service->registerShadowObjects( $shadowObjectIdentity );
			}
		}
	}
	
	/**
	 * @inheritdoc
	 */
	public function listShadowObjectVersions( $alienId, $shadowId, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::listShadowObjectVersions called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; rendition:' . $rendition );

		$versionHandler = new Elvis_BizClasses_Version();
		$elvisAssetVersions = $versionHandler->listVersions( $alienId, $rendition );
		$elvisAssetVersions = Elvis_BizClasses_Object::setVersionStatusFromEnterprise( $shadowId, $elvisAssetVersions );
		return $elvisAssetVersions;
	}

	/**
	 * @inheritdoc
	 */
	public function getShadowObjectVersion( $alienId, $shadowId, $version, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObjectVersion called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; version:' . $version . '; rendition:' . $rendition);

		$versionHandler = new Elvis_BizClasses_Version();
		$versionInfo = $versionHandler->retrieveVersion( $alienId, $version, $rendition );
		$versionInfos = Elvis_BizClasses_Object::setVersionStatusFromEnterprise( $shadowId, array( $versionInfo ) );
		return reset( $versionInfos );
	}

	/**
	 * @inheritdoc
	 */
	public function restoreShadowObjectVersion( $alienId, $shadowId, $version )
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'ContentSource::restoreShadowObjectVersion called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; version:' . $version);

		$versionHandler = new Elvis_BizClasses_Version();
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
				$service = new Elvis_BizClasses_AssetService();
				$destName = $destObject->MetaData->BasicMetaData->Name;
				$assetId = Elvis_BizClasses_AssetId::getAssetIdFromAlienId( $alienId );
				$copyId = $service->copy( $assetId, $destName );
				$destId = Elvis_BizClasses_AssetId::getAlienIdFromAssetId( $copyId );
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
		$service = new Elvis_BizClasses_AssetService();
		$userDetails = $service->getUserDetails( $user->Name );
		if( $userDetails ) {
			require_once BASEDIR.'/config/config_elvis.php'; // ELVIS_INTERNAL_USER_POSTFIX
			$user->FullName = $userDetails->fullName;
			$user->EmailAddress = $userDetails->email;
			if( !$userDetails->ldapUser ) {
				$user->FullName .= ELVIS_INTERNAL_USER_POSTFIX;
				$user->Deactivated = true;
			}
		}
		return $user;
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
		require_once BASEDIR.'/config/config_elvis.php';
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
	 * @param Elvis_DataClasses_EntHit $hit
	 * @param string[] $renditions
	 * @return Attachment[]
	 */
	private function getFiles( Elvis_DataClasses_EntHit $hit, array $renditions ) : array
	{
		if( $this->isContentSourceFileLinksRequested() ) {
			$fileLinkType = 'ContentSourceFileLink';
		} elseif( $this->isContentSourceProxyLinksRequested() ) {
			$fileLinkType = 'ContentSourceProxyLink';
		} else {
			$fileLinkType = 'FileUrl';
		}

		$files = array();
		foreach( $renditions as $rendition ) {
			$file = Elvis_BizClasses_Attachment::getAttachment( $hit, $rendition, $fileLinkType );
			if( $file ) {
				$files[] = $file;
			}
		}
		return $files;
	}

	/**
	 * @param Object $smartObject Object of MetaData that will filled
	 * @param Elvis_DataClasses_EntHit $hit returned from elvis server
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
