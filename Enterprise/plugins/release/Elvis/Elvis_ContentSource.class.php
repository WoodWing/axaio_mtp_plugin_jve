<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Implements Content Source connector.
 * Creates, updates and deletes shadow objects.
 */

// Plug-in config file
require_once dirname(__FILE__).'/config.php';
// Enterprise includes
require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';


class Elvis_ContentSource extends ContentSource_EnterpriseConnector
{
	private $metadataHandler;

	/**
	 * @return MetdataHandler
	 */
	private function getMetadataHandler()
	{
		require_once dirname(__FILE__).'/model/MetadataHandler.class.php';
		if( !isset($this->metadataHandler) ) {
			$this->metadataHandler = new MetadataHandler();
		}
		return $this->metadataHandler;
	}
	
	/**
	 * getContentSourceId
	 *
	 * Return unique identifier for this content source implementation. Each alien object id needs
	 * to start with _<this id>_
	 *
	 * @return string unique identifier for this content source, without underscores.
	 */
	final public function getContentSourceId()
	{
		return ELVIS_CONTENTSOURCEID;
	}
	
	/**
	 * Elvis content source does not support named queries
	 *
	 * @return array empty array
	 */
	final public function getQueries()
	{
		return array();
	}
	
	/**
	 * Elvis content source doesn't support named queries
	 *
	 * @param $query
	 * @param $params
	 * @param $firstEntry
	 * @param $maxEntries
	 * @param $order
	 * @return null
	 */
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		return null;
	}

	/**
	 * Gets alien object. In case of rendition 'none' the lock param can be set to true, this is the
	 * situation that Properties dialog is shown. If content source allows this, return the object
	 * on failure the dialog will be read-only. If Property dialog is ok-ed, a shadow object will
	 * be created. The object is assumed NOT be locked, hence there is no unlock sent to content source.
	 *
	 * @param string	$alienId	Alien object id, so include the _<ContentSourceId>_ prefix
	 * @param string	$rendition	'none' (to get properties only), 'thumb', 'preview' or 'native'
	 * @param boolean	$lock		See method comment.
	 *
	 * @return Object
	 */
	final public function getAlienObject( $alienId, $rendition, $lock )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getAlienObject called for alienId:' . $alienId . '; rendition:' . $rendition . '; lock:' . $lock );
		
		require_once dirname ( __FILE__ ).'/util/ElvisUtils.class.php';
		$elvisId = ElvisUtils::getElvisId( $alienId );
		$hit = ElvisUtils::getHit( $elvisId );
		$files = $this->getFiles( $hit, $rendition );
		
		$object = new Object();
		$object->MetaData = new MetaData();
		$object->Relations = array();
		$object->Files = $files;
		
		$this->fillMetadata( $object, $hit );
		
		return $object;
	}
	
	/**
	 * deleteAlienObject
	 *
	 * Deletion of alien object.
	 *
	 * @param string	$alienId		Alien id
	 */
	public function deleteAlienObject( $alienId )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::deleteAlienObject called for alienId:' . $alienId );
		
		require_once dirname ( __FILE__ ).'/logic/ElvisContentSourceService.php';
		require_once dirname ( __FILE__ ).'/util/ElvisUtils.class.php';

		$service = new ElvisContentSourceService();
		$elvisId = ElvisUtils::getElvisId( $alienId );
		$service->remove( $elvisId );
	}
	
	/**
	 * listAlienObjectVersions
	 *
	 * Returns versions of alien object
	 *
	 * Default implementation returns an empty array, which makes client show an empty dialog
	 * and also prevents that get/restoreAlienObjectVersion will be called
	 *
	 * @param string	$alienId	Alien id
	 * @param string 	$rendition	Rendition to include in the version info
	 *
	 * @return array of VersionInfo
	 */
	public function listAlienObjectVersions( $alienId, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::listAlienObjectVersions called for alienId:' . $alienId );

		require_once dirname(__FILE__).'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		return $versionHandler->listVersions( $alienId, $rendition );
	}

	/**
	 * getAlienObjectVersion
	 *
	 * Returns versions of alien object
	 *
	 * Default implementation throws invalid operation exception, but this should never be called
	 * if listAlienObjectVersions returns an empty array.
	 *
	 * @param string	$alienId	Alien id
	 * @param string 	$version	Version to get as returned by listAlienVersons
	 * @param string 	$rendition	Rendition to get
	 *
	 * @return VersionInfo
	 */
	public function getAlienObjectVersion( $alienId, $version, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getAlienObjectVersion called for alienId:' . $alienId . '; version:' . $version . '; rendition:' . $rendition );

		require_once dirname(__FILE__).'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		return $versionHandler->retrieveVersion( $alienId, $version, $rendition );
	}

	/**
	 * restoreAlienObjectVersion
	 *
	 * Restores versions of alien object
	 *
	 * Default implementation throws invalid operation exception, but this should never be called
	 * if listAlienObjectVersions returns an empty array.
	 *
	 * @param string	$alienId	Alien id
	 * @param string 	$version	Version to get as returned by listAlienObjectVersions
	 */
	public function restoreAlienObjectVersion( $alienId, $version )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getAlienObjectVersion called for alienId:' . $alienId . '; version:' . $version );

		require_once dirname(__FILE__).'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		$versionHandler->promoteVersion( $alienId, $version );
	}
	
	/**
	 * getShadowObject
	 *
	 * Deprecated since Enterprise 9.7, use getShadowObject2 instead
	 *
	 * Get shadow object. Meta data is all set already, access rights have been set etc.
	 * All that is required is filling in the files for the requested object.
	 * Furthermore the meta data can be adjusted if needed.
	 * If Files is null, Enterprise will fill in the files
	 * Only users who have no restricted access to the Elvis Content Source are allowed to lock the shadow object.
	 * See EN-36871.
	 *
	 * Default implementation does nothing, leaving it all up to Enterprise
	 *
	 * @param string	$alienId 	Alien object id
	 * @param string	$object 	Shadow object from Enterprise
	 * @param array		$objprops 	Array of all properties, both the public (also in Object) as well as internals
	 * @param boolean	$lock		Whether object should be locked
	 * @param string	$rendition	Rendition to get
	 */
	public function getShadowObject( $alienId, &$object, $objprops, $lock, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObject called for alienId:' . $alienId . '; lock:' . $lock . '; rendition:' . $rendition );

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$this->checkUserEditRight( $lock, $rendition );
		$this->getShadowObject2( $alienId, $object, $objprops, $lock, $rendition, null );
	}

	/**
	 * getShadowObject2
	 * 
	 * Get shadow object version 2. It is an extension from the first getShadowObject.
	 * 
	 * Meta data is all set already, access rights have been set etc.
	 * All that is required is filling in the files for the requested object.
	 * This will only be done when the version of the files is newer than the haveVersion.
	 * Furthermore the meta data can be adjusted if needed.
	 * Only users who have no restricted access to the Elvis Content Source are allowed to lock the shadow object.
	 * See EN-36871.
	 * If Files is null, Enterprise will fill in the files
	 * 
	 * @param string $alienId
	 * @param string $object
	 * @param array $objprops
	 * @param bool $lock
	 * @param string $rendition
	 * @param string $haveVersion
	 */
	public function getShadowObject2( $alienId, &$object, $objprops, $lock, $rendition, $haveVersion )
	{
		LogHandler::Log ( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObject2 called for alienId:' . $alienId . '; lock:' . $lock . '; rendition:' . $rendition );

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$this->checkUserEditRight( $lock, $rendition );

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once dirname ( __FILE__ ).'/logic/ElvisContentSourceService.php';
		require_once dirname ( __FILE__ ).'/util/ElvisUtils.class.php';
		$elvisId = ElvisUtils::getElvisId( $alienId );
		$hit = ElvisUtils::getHit( $elvisId, $lock );
		
		if( !$haveVersion || version_compare( $haveVersion, ElvisUtils::getEnterpriseVersionNumber($hit->metadata['versionNumber']), '<' ) ) {
			$object->Files = $this->getFiles( $hit, $rendition );
		}
		$this->getMetadataHandler()->read( $object, $hit->metadata );

		/*
		 * When creating a new shadow object some metadata needs to be set from Enterprise -> Elvis. However,
		 * this data is not available in i.e. createShadowObjects. The object in Enterprise is created at a later stage.
		 * Currently there is no proper way of setting the Entprise object ID, so it is set here (once).
		 * There are way more scenario's to trigger this method except for creating a new shadow object, so check if the
		 * Enterprise object Id is already set, if not, only then update the metadata.  
		 */
		if( !array_key_exists('sceId', $hit->{'metadata'}) && empty($hit->{'metadata'}['sceId']) ) {
			LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObject2 setting Enterprise metadata.' );

			$elvisMetadata = $this->fillElvisEnterpriseMetadata( $object->MetaData );
			$elvisMetadata['sceId'] = $object->MetaData->BasicMetaData->ID;
			$elvisMetadata['sceCreated'] = $objprops['Created'];
			$elvisMetadata['sceCreator'] = $objprops['Creator'];
			$elvisMetadata['sceModified'] = $objprops['Modified'];
			$elvisMetadata['sceModifier'] = $objprops['Modifier'];

			$service = new ElvisContentSourceService();
			$service->updateWorkflowMetadata( $elvisId, $elvisMetadata );
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
		require_once dirname(__FILE__).'/util/ElvisSessionUtil.php';
		if ( $lock && $rendition == 'native' ) {
			$restricted = ElvisSessionUtil::getSessionVar( 'restricted' );
			if ( $restricted || is_null( $restricted ) ) {
				throw new BizException( 'ERR_AUTHORIZATION', 'Client' );
			}
		}
	}

	/**
	 * Create shadow object for specified alien object. The actual creation is done by Enterprise,
	 * the Content Sources needs to instantiate and fill in an object of class Object.
	 * When an empty name is filled in, auto-naming will be used.
	 * It's up to the content source implementation if any renditions (like thumb/preview) are stored
	 * inside Enterprise. If any rendition is stored in Enterprise it's the content source implementation's
	 * responsibility to keep these up to date. This could for example be checked whenever the object
	 * is requested via getShadowObject
	 *
	 * @param string	$alienId 		Alien object id, so include the _<ContentSourceId>_ prefix
	 * @param Object	$destObject		In some cases (CopyObject, SendToNext, Create relation)
	 this can be partly filled in by user, in other cases this is null.
	 * 									In some cases this is mostly empty, so be aware.
	 *
	 * @return Object	filled in with all fields, the actual creation of the Enterprise object is done by Enterprise.
	 */
	final public function createShadowObject( $alienId, $destObject )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::createShadowObject called for alienId:' . $alienId );

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';

		$elvisId = ElvisUtils::getElvisId( $alienId );
		$hit = ElvisUtils::getHit( $elvisId );

		// Register shadow object in Elvis. Throws BizException if not possible (e.g. already linked)
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once dirname(__FILE__).'/logic/ElvisObjectManager.php';
		$systemId = BizSession::getEnterpriseSystemId();
		ElvisObjectManager::registerShadowObject( $elvisId, $systemId );

		if( !$destObject ) {
			$destObject = new Object();
			$destObject->MetaData = new MetaData();
			$destObject->Relations = array();
		}
		$this->fillMetadata( $destObject, $hit );
		$destObject->Files = array();

		return $destObject;
	}
	
	final public function createCopyObject( $alienId, $destObject )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::createCopyObject called for alienId:' . $alienId );
		
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
		require_once dirname(__FILE__).'/logic/ElvisContentSourceService.php';
		
		$elvisId = ElvisUtils::getElvisId( $alienId );
		$hit = ElvisUtils::getHit( $elvisId );
		
		// Export original asset file to Enterprise
		$service = new ElvisContentSourceService();
		$fileUrl = $service->exportOriginalForAsset( $elvisId );
		
		if( !$destObject ) {
			$destObject = new Object();
			$destObject->MetaData = new MetaData();
			$destObject->Relations = array();
		}
		$this->fillMetadata($destObject, $hit);
		
		// Remove Elvis related metadata
		$destObject->MetaData->BasicMetaData->DocumentID = null;
		$destObject->MetaData->BasicMetaData->ContentSource = null;
		
		$attachment = self::createAttachment( $fileUrl, $destObject->MetaData->ContentMetaData->Format );
		$destObject->Files = array( $attachment );
		$destObject->Files = array_merge( $destObject->Files,$this->getFiles($hit, 'preview') );
		$destObject->Files = array_merge( $destObject->Files,$this->getFiles($hit, 'thumb') );
		
		return $destObject;
	}
	
	private function createAttachment( $fileUrl, $type )
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		
		// Create File attachement
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
	 * saveShadowObject
	 *
	 * Saves shadow object. This is called after update of DB records is done in Enterprise, but
	 * before any files are stored. This allows content source to save the files externally in
	 * which case Files can be cleared. If Files not cleared, Enterprise will save the files
	 *
	 * Default implementation does nothing, leaving it all up to Enterpruse
	 *
	 * @param string	$alienId		Alien id of shadow object
	 * @param string	$object
	 */
	public function saveShadowObject( $alienId, &$object )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::saveShadowObject called for alienId:' . $alienId );
		
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
		$elvisId = ElvisUtils::getElvisId( $alienId );
		
		// upload original to Elvis
		if( $object->Files ) {
			foreach( $object->Files as $file ) {
				if( $file->Rendition == 'native' && $file->FilePath != null ) {
					/*$metadata = */$this->getMetadataHandler()->update( $elvisId, $object->MetaData, $file );
				}
			}
		}
		$object->Files = null;
	}
	
	/**
	 * setShadowObjectProperties
	 *
	 * Updates the metadata of a shadow object. This is called after updating DB records
	 * in Enterprise. This allows the content source to synchronize metadata changes with
	 * its external/integrated DB (if any). However, this is an edge case, because the
	 * content source is more about content and less about metadata. Therefor, normally
	 * there would be no need to implement this function. Nevertheless, it can be used in
	 * case a tight integration with the external content source is needed.
	 *
	 * @since v8.2.0
	 * @param string	$alienId		Alien id of shadow object
	 * @param Object	$object
	 */
	public function setShadowObjectProperties( $alienId, &$object )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::setShadowObjectProperties called for alienId:'.$alienId );

		if( $this->triggeredBySyncJob() ) {
			// Update coming from Elvis Sync job, prevent update round-trips
			return;
		}

		require_once dirname(__FILE__).'/util/ElvisSessionUtil.php';
		$sessionId = ElvisSessionUtil::getSessionId();
		
		// This function is indirectly called when exporting shadow objects from Elvis
		// At this point there is no active session and the write can safely be ignored.
		if( isset($sessionId) ) {
			require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
			require_once dirname(__FILE__).'/util/ElvisObjectUtils.class.php';
			require_once dirname(__FILE__).'/logic/ElvisContentSourceService.php';

			$elvisId = ElvisUtils::getElvisId( $alienId );
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
	}


	/**
	 * Setting multiple shadow objects' properties.
	 *
	 * @since v9.2.0
	 * @param array[] $shadowObjectIds List of array where key is the content source id and value its list of shadow ids.
	 * @param MetaDataValues[] $metaDataValues The modified values that needs to be updated at the content source side.
	 */
	public function multiSetShadowObjectProperties( $shadowObjectIds, $metaDataValues )
	{
		if( $this->triggeredBySyncJob() ) {
			// Update coming from Elvis Sync job, prevent update round-trips
			return;
		}

		require_once dirname(__FILE__).'/util/ElvisSessionUtil.php';
		$sessionId = ElvisSessionUtil::getSessionId();

		// This function is indirectly called when exporting shadow objects from Elvis
		// At this point there is no active session and the write can safely be ignored.
		if( isset($sessionId) ) {
			require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
			require_once dirname(__FILE__).'/logic/ElvisContentSourceService.php';
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

			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			// Collect Elvis Ids
			// 9.2.0 bug: shadowObjectIds key should be the CS ID, but instead is index 0.
			$elvisIds = array();
			$Ids = isset( $shadowObjectIds[ELVIS_CONTENTSOURCEID] ) ? $shadowObjectIds[ELVIS_CONTENTSOURCEID] : $shadowObjectIds[0];
			foreach( $Ids as $ObjId ) {
				$elvisId = DBObject::getColumnValueByName( $ObjId, 'Workflow', 'documentid' );
				LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::multiSetShadowObjectProperties called for alienId:'.$elvisId );
				$elvisIds[] = $elvisId;
			}

			// Normally all metadata is set using the REST client. These are set using
			// the permissions of the current user. However there are some specific
			// Enterprise metadata properties defined in Elvis, which needs to be set
			// whatever permission the current user has, so these are set using AMF; this
			// only set sce prefixed metadata properties as a super user.
			$elvisMetadata = $this->fillElvisEnterpriseMetadataMulti( $metaDataValues );
			$service = new ElvisContentSourceService();
			$service->updateWorkflowMetadata( $elvisIds, $elvisMetadata );

			// Bulk REST client update
			$this->getMetadataHandler()->updateBulk( $elvisIds, $metaDataValues );
		}
	}

	/**
	 * Deletion of shadow object, called just before the shadow object record is deleted
	 * or after the object is restored from trash.
	 *
	 * @param string $alienId - Alien id of shadow object
	 * @param string $shadowId - Enterprise id of shadow object
	 * @param bool $permanent - Whether object will be permanently deleted
	 * @param bool $restore if object is restored from trash
	 * @throws Exception
	 */
	public function deleteShadowObject( $alienId, $shadowId, $permanent, $restore )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::deleteShadowObject called for alienId:' . $alienId .
								'; shadowId:' . $shadowId . '; permanent:' . $permanent . '; restore:' . $restore );

		if( $this->triggeredBySyncJob() ) {
			// Update coming from Elvis Sync job, prevent update round-trips
			return;
		}
		
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
		// Only remove the system id in Elvis when the asset is completely removed in Enterprise (removed from the trash)
		$elvisId = ElvisUtils::getElvisId( $alienId );

		require_once dirname(__FILE__).'/logic/ElvisObjectManager.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
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
	 * listShadowObjectVersions
	 *
	 * Returns versions of show object or null if Enterprise should handle this
	 *
	 * Default implementation returns null to have Enterprise handle this.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param string	$shadowId	Enterprise id of shadow object
	 * @param string 	$rendition	Rendition to include in the version info
	 *
	 * @return array of VersionInfo or null if Enterprise should handle this
	 */
	public function listShadowObjectVersions( $alienId, $shadowId, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::listShadowObjectVersions called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; rendition:' . $rendition );

		require_once dirname(__FILE__).'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		$elvisAssetVersions = $versionHandler->listVersions( $alienId, $rendition );

		require_once dirname(__FILE__).'/util/ElvisObjectUtils.class.php';
		$elvisAssetVersions = ElvisObjectUtils::setVersionStatusFromEnterprise( $shadowId, $elvisAssetVersions );

		return $elvisAssetVersions;
	}

	/**
	 * getShadowObjectVersion
	 *
	 * Returns versions of shadow object or null if Enterprise should handle this
	 *
	 * Default implementation returns null to have Enterprise handle this.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param string	$shadowId	Enterprise id of shadow object
	 * @param string 	$version	Version to get as returned by listShadowVersons
	 * @param string 	$rendition	Rendition to get
	 *
	 * @return VersionInfo or null if Enterprise should handle this
	 */
	public function getShadowObjectVersion( $alienId, $shadowId, $version, $rendition )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'ContentSource::getShadowObjectVersion called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; version:' . $version . '; rendition:' . $rendition);

		require_once dirname(__FILE__).'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		return $versionHandler->retrieveVersion( $alienId, $version, $rendition );
	}

	/**
	 * restoreShadowObjectVersion
	 *
	 * Restores versions of alien object, true when handled or null if Enterprise should handle this
	 *
	 * Default implementation returns null to have Enterprise handle this.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param string	$shadowId	Enterprise id of shadow object
	 * @param string 	$version	Version to get as returned by listAlienVersons
	 *
	 * @return true when handled or null if Enterprise should handle this
	 */
	public function restoreShadowObjectVersion( $alienId, $shadowId, $version )
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'ContentSource::restoreShadowObjectVersion called for alienId:' . $alienId .
														'; shadowId:' . $shadowId . '; version:' . $version);

		require_once dirname(__FILE__).'/model/VersionHandler.class.php';
		$versionHandler = new VersionHandler();
		$versionHandler->promoteVersion( $alienId, $version );

		return true;
	}

	/**
	 * copyShadowObject
	 *
	 * Copies a shadow object.
	 * All that is required is filling in the files for the copied object.
	 * Furthermore the meta data can be adjusted if needed.
	 * If Files is null, Enterprise will fill in the files
	 *
	 * Default implementation creates a new shadow object.
	 *
	 * @param string	$alienId	Alien id of shadow object
	 * @param Object	$srcObject	Source Enterprise object (only metadata filled)
	 * @param Object	$destObject	Destination Enterprise object
	 *
	 * @return Object	filled in with all fields, the actual creation of the Enterprise object is done by Enterprise.
	 */
	public function copyShadowObject( $alienId, $srcObject, $destObject )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
		require_once dirname(__FILE__).'/logic/ElvisContentSourceService.php';
		
		$service = new ElvisContentSourceService();
		$destName = $destObject->MetaData->BasicMetaData->Name;

		$assetId = ElvisUtils::getElvisId($alienId);
		$copyId = $service->copy( $assetId, $destName );
		
		$destId = ElvisUtils::getAlienId( $copyId );

		LogHandler::Log( 'ContentSource', 'DEBUG', 'ContentSource::copyShadowObject called for alienId:' . $alienId . 'destId:' . $destId );

		$shadowObject = $this->createShadowObject( $destId, $destObject );

		return $shadowObject;
	}

	/**
	 * Called by the core server to ask the Content Source connector whether or not the
	 * given user has certain access ($right) to the given alien object.
	 *
	 * Access rights are setup per brand or overrule issue. Underneath, rights can be configured
	 * more specific; per object type, status or category. Note that the given parameters
	 * represent the values to be assigned, so the object stored in database might have different
	 * values assigned at the time calling this function.
	 *
	 * By default NULL is returned, which means that the core server does access rights
	 * checking as configured for Enterprise. However, when the connector wants to e.g. let
	 * the integrated Content Source system do the checking, it should implement this function
	 * and return TRUE or FALSE instead.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $right Access right to be checked. See BizAccessFeatureProfiles.class.php for possible flags.
	 * @param integer $brandId
	 * @param integer $overruleIssueId Id of issue that overrules the brand. Zero when none- or normal issue(s) assigned.
	 * @param integer $categoryId
	 * @param string $objectType
	 * @param integer $statusId Valid status id, or -1 for Personal Status.
	 * @param string $alienId
	 * @param string $contentSource
	 * @param string $documentId
	 * @return boolean|null NULL to let core server decide (default). TRUE when allowed. FALSE when not allowed (experimental).
	 */
	public static function checkAccessForAlien( $user, $right,
	$brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
	$alienId, $contentSource, $documentId )
	{
		return true;
	}
	
	/**
	 * Same as {@link:checkAccessForAlien()} but then for shadow objects.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $right Access right to be checked. See BizAccessFeatureProfiles.class.php for possible flags.
	 * @param integer $brandId
	 * @param integer $overruleIssueId Id of issue that overrules the brand. Zero when none- or normal issue(s) assigned.
	 * @param integer $categoryId
	 * @param string $objectType
	 * @param integer $statusId Valid status id, or -1 for Personal Status.
	 * @param string $shadowId
	 * @param string $contentSource
	 * @param string $documentId
	 * @return boolean|null NULL to let core server decide (default). TRUE when allowed. FALSE when not allowed (experimental).
	 */
	public static function checkAccessForShadow( $user, $right,
			$brandId, $overruleIssueId, $categoryId, $objectType, $statusId,
			$shadowId, $contentSource, $documentId )
	{
		return null;
	}
	
	/**
	 * Called by the core server to ask the Content Source connector whether or not the connector can provide more
	 * information about the given user abstracted from MetaData of the alien- or shadow object.
	 *
	 * When a content source creates an alien- or a shadow object it is possible that the MetaData contains user names
	 * that are not known in Enterprise Server (yet). e.g: The fields Modifier, Creator, Deletor, RouteTo and LockedBy
	 * can have such user names as values.
	 *
	 * If LDAP is enabled and if external systems can put objects into Enterprise Server it is possible that users are
	 * only known in LDAP and not in Enterprise Server. Therefore users get abstracted from the MetaData information.
	 * And if they are not known in Enterprise Server they are created on the fly with just the bare minimum information
	 * available.
	 *
	 * Such users get a flag "ImportOnLogon" which is set to true. As soon as such user logs in into Enterprise Server,
	 * the user information is further enriched from the information provided by LDAP. Like groups, external ID,
	 * password and e-mail data etc.
	 *
	 * On the Users admin page, partially imported users are displayed with "Import Groups" set to 'Yes'. As soon as the
	 * user logs in, this will be set to 'No'.
	 *
	 * This method allows the connector to enrich user information before the user gets created. e.g.:
	 * The AdmUser->FullName is used to show the name of the user in the UI. While the MetaData contains the short
	 * username, which not always describes the user properly.
	 *
	 * @since 9.4
	 * @param AdmUser $user Object to enrich with more user information
	 * @return AdmUser $user Enriched object
	 */
	public static function completeUser( AdmUser $user )
	{
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
		return ElvisUtils::enrichUser( $user );
	}

	/**
	 * This function is called by the core server in order to determine whether the plugin
	 * supports requests for file links.
	 *
	 * By default any content source connector does not support requests for file links.
	 * This can be overruled by the connector by implementing this function.
	 *
	 * @since 9.7
	 * @return bool
	 */
	public function isContentSourceFileLinksSupported()
	{
		return true;
	}

	/**
	 * Helper to make array filled with default Enterprise metadata 
	 * fields required for Elvis.
	 * @param array $metadata Enterprise metadata object 
	 * @return array $elvisMetaData Map containing Enterprise metadata properties which are always synchronized to Elvis  
	 */
	private function fillElvisEnterpriseMetadata( $metadata )
	{
		$elvisMetadata = array();
		$elvisMetadata['sceCategoryId'] = $metadata->BasicMetaData->Category->Id;
		$elvisMetadata['sceCategory'] = $metadata->BasicMetaData->Category->Name;
		$elvisMetadata['scePublicationId'] = $metadata->BasicMetaData->Publication->Id;
		$elvisMetadata['scePublication'] = $metadata->BasicMetaData->Publication->Name;

		return $elvisMetadata;
	}

	private function fillElvisEnterpriseMetadataMulti( $metadataValues )
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
	 * Helper function to create an Attachment from an Elvis hit.
	 * If an Attachment can be extracted based on the rendition, it will be returned in an array.
	 *
	 * @param object $hit The Elvis hit from which an attachment will be created.
	 * @param string $rendition Rendition of the file.
	 * @param null $version
	 * @return array A list of Attachments.
	 */
	private function getFiles( $hit, $rendition, $version = null )
	{
		require_once dirname( __FILE__ ).'/util/ElvisUtils.class.php';

		$files = array();
		//isContentSourceFileLinksRequested is only supported for Enterprise 9.7 and up.
		//In order to remain backwards compatible with 9.6 and lower we need to check for the method here.
		$fileLinksRequested = (method_exists( $this, 'isContentSourceFileLinksRequested' )) 
			? $this->isContentSourceFileLinksRequested() : false;
		$file = ElvisUtils::getAttachment( $hit, $rendition, $fileLinksRequested );
		if( !is_null( $file ) ) {
			$files[] = $file;
		}

		return $files;
	}
		
	/**
	 * @param Object $smartObject Object of MetaData that will filled
	 * @param Hit $hit returned from elvis server
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
	 * @param object $metaDataValue Metadata value structure of which the first value needs to be retrieved
	 * @return The metadata value
	 */
	private function getFirstMetaDataValue( $metaDataValue )
	{
		if( !is_null($metaDataValue->Values) ) {
			return $metaDataValue->Values[0];
		} else {
			return $metaDataValue->PropertyValues[0]->Value;
		}
	}
	
	private function triggeredBySyncJob() 
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		return (BizSession::getServiceName() == 'ElvisSync');
	}
}
