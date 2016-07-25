<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * >>> BZ#14555 [Note#001]
 * For Set Properties operations, the Server must create a new version when the current object version
 * is a 'minor' version and the new version should be a 'major' version. This criteria is to avoid needless
 * creation of versions. At Server side, new versions are created by copying the file content of latest version.
 *
 * Example: When the current version is v1.0 (=major), there is no need to create a new version, so it remains v1.0.
 * (This in contradiction to Save Version and Check-in operations.) But when the current version is v1.1 (=minor),
 * the new version should become v2.0 (=major). In other terms, when a user does Set Properties for many times in a row,
 * there will be zero or one versions created in total.
 *
 * IMPORTANT: For all written above, assumed is that the 'target' status has 'Create Permanent Version' option enabled!
 * So no matter the current version, there shouldn't be a new version created when the option is disabled.
 * <<<
 */

require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';

class BizVersion
{
	/**
	 * Creates or restores new object version at DB and filestore. Also deals with content and renditions.
	 * Restoring versions is implemented by creating a new version based on an old one.
	 *
	 * @param string $id Object id
	 * @param array  $arr Object DB record/row of current version
	 * @param object $tarStatusCfg Target status definition, wherein the object is pushed into.
	 * @param array $versions smart_objectversions record
	 * @param string $restoreVer Optional. Used to restore a specific version (major.minor notation).
	 * @param boolean $setObjPropMode Optional. Special case for SetObjectProperties context, conditionally creating versions. (See Note#001)
	 * @return string Next version number (major.minor) to be used for current object (=> newer than newest at objectversions table).
	 * @throws BizException on failure
	 */
	static private function createVersion( $id, $arr, $tarStatusCfg, &$versions, $restoreVer=null, $setObjPropMode=false )
	{
		// Ensure input is in DB rows, if it is Biz Props, convert it:
		if( array_key_exists( 'ID', $arr ) ) {
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$arr = BizProperty::objPropToRowValues( $arr );
			LogHandler::Log(__CLASS__,'DEBUG','createVersion - converted biz props to db props');
		}

		require_once BASEDIR.'/server/bizclasses/BizStorage.php'; // StorageFactory
		$storename = $arr['storename'];

		// The current version is NOT stored in the objectversions table, but in objects table.
		// So we take the one of the objects table as the "new" version for the objectversions table.
		$nextVer = null; // used for restoring only
		$newVer = self::getCurrentVersionNr( $arr );

		// Find index of version to restore
		$restoreVerIdx = null;
		if( !is_null($restoreVer) ) {
			foreach( $versions as $verIdx => $version ) {
				if( $version['version'] == $restoreVer ) {
					$restoreVerIdx = $verIdx;
				}
			}
			if( is_null($restoreVerIdx) ) { // restore requested, but not found?
				throw new BizException( 'ERR_NOTFOUND', 'Client', BizResources::localize('OBJ_VERSION_ABBREVIATED').$restoreVer );
			}
		}

		// Put version-info in version table
		if( !DBVersion::insertVersion( $id, $newVer, $arr ) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBVersion::getError() );
		}

		// If there are no versions yet we don't have any types yet, so fetch the new* version to get them.
		// *Note that we have just inserted the new version above.
		$newVerRes = DBVersion::getVersions( $id, $newVer ); // get single version (array with one item)
		if( count($newVerRes) == 0 ) {
			throw new BizException( 'ERR_NOTFOUND', 'Server', BizResources::localize('OBJ_VERSION_ABBREVIATED').$newVer );
		}
		$versions[] = $newVerRes[0];

		// Determine next version to use for current object
		$nextVer = self::determineNextVersionNr( $tarStatusCfg, $arr );

		// Copy files (old version to the new current)
		if( !is_null($restoreVerIdx) ) { // Restore Version or Set Properties operation?
			// For Restore Version operations, we create a new version by copying the requested version.
			$types = unserialize( $versions[$restoreVerIdx]['types'] );
			$sourceVer = $restoreVer;
		} else if( $setObjPropMode ) {
			// For Set Properties operations, we should reach this point -only- if CreatePermanentVersion is enabled !
			// In that case, we create a new version by simply copying the last version. See Note#001.
			$types = unserialize( $arr['types'] );
			$sourceVer = $newVer;
			// Pages files at storage are not versioned (only the last version is kept) but the object version
			// is put in their names. So here we simply update the names and leave DB records as-is.
			require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
			BizPage::versionPageFiles( $id, $storename, $newVer, $nextVer );
		} else {
			$types = null;
			$sourceVer = null;
		}

		try {
			self::createVersionByConnector( $id, $sourceVer, $nextVer, $storename, $setObjPropMode );	
		} catch ( BizException $e) {
			throw $e;
		}

		if( $types ) {
			foreach( array_keys($types) as $tp ) {
				$attachobj = StorageFactory::gen( $storename, $id, $tp, $types[$tp], $sourceVer );
				$dummy = null;
				if( !$attachobj->copyFile( $nextVer, null, null, null, null, $dummy, null ) ) {
					throw new BizException( 'ERR_ATTACHMENT', 'Server', $attachobj->getError() );
				}
			}
		}
		return $nextVer;
	}

	/**
	 * Removes intermediate versions when the configured target status ($tarStatusCfg) of the object ($id)
	 * has RemoveIntermediateVersions option enabled or removes excess intermediates.
	 *
	 * @param array $ids List of Object Ids to check
	 * @param string $type Object type of input ids
	 * @param array|null $storenames Object DB record/row of current version. Will be retrieved when needed if null.
	 * @param array $tarStatusCfgs Target status definitions for passed object ids.
	 * @param array $versions  The current versions
	 * @throws BizException
	 */
	private static function removeIntermediateVersions( $ids, $type, $storenames, $tarStatusCfgs, $versions )
	{
		$deleteObjectIds = array();
		$deleteVersionNumbers = array();
		$typesForVersions = array();

		foreach( $ids as $i => $id ) {
			// Collect intermediate versions (=> all except X.0)
			$intermediates = array();
			if( array_key_exists( $id, $versions ) ) {
				foreach( $versions[$id] as $version ) {
					if( $version['minorversion'] != 0 ) { // intermediate version?
						$intermediates[] = $version;
					}
				}
			}

			// Determine excess intermediate versions
			if( $tarStatusCfgs[$i]->RemoveIntermediateVersions ) { // time for clean up?
				$excess = count($intermediates);
			} else {
				$max = self::maxIntermediates( $type );
				if( $max == 0 ) { // Allow unending versions.
					return;
				}
				$excess = count($intermediates) - $max;	// current record also counts as version
			}

			for( $i = 0; $i < $excess; $i++) {
				$types = unserialize($versions[$id][$i]['types']);
				$deleteObjectIds[] = $id;
				$deleteVersionNumbers[] = $intermediates[$i]['version'];
				$typesForVersions[] = $types;
			}
		}

		// Delete excess intermediate versions
		if( !empty( $deleteObjectIds ) ) {
			if( is_null( $storenames ) ) {
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$storenames = DBObject::getColumnValueByNameForIds( $deleteObjectIds, 'Workflow', 'storename' );
			}

			self::deleteVersions($deleteObjectIds, $deleteVersionNumbers, $storenames, $typesForVersions );
		}
	}

	/**
	 * Deletes one or more specific versions of objects.
	 *
	 * Each version is deleted from the database and the files belonging
	 * to that version are deleted from the filestore.
	 *
	 * @param array $objectIds List of ids for objects
	 * @param array $versionNumbers List of version numbers (<major>.<minor>)
	 * @param array $storenames Reference to the filestore for objectIds
	 * @param array $typesForVersions Array of Key/value pairs of rendition/format
	 * @throws BizException
	 */
	static public function deleteVersions( $objectIds, $versionNumbers, $storenames, $typesForVersions )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php'; // StorageFactory

		foreach( $objectIds as $i => $objectId ) {
			try {
				self::deleteVersionByConnector($objectId, $versionNumbers[$i], $storenames[$objectId] );
			} catch ( BizException $e) {
				throw $e;
			}
		}

		// delete (multiple) version records
		if( !DBVersion::deleteVersions( $objectIds, $versionNumbers ) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBVersion::getError() );
		}

		// delete attachment(s)
		foreach( $objectIds as $i => $objectId ) {
			$types = $typesForVersions[$i];
			$storename = $storenames[$objectId];
			$version = $versionNumbers[$i];

			foreach( array_keys($types) as $tp ) {
				$attachobj = StorageFactory::gen( $storename, $objectId, $tp, $types[$tp], $version );
				if( !$attachobj->deleteFile() ) {
					// >>> BZ#18866: Let's NOT throw exceptions here, since we're doing just a side job (cleaning stuff) !
					// Note that file deletions typically fail for migrated DBs! Such as from v4/v5 to v6/v7.
					// This is because version numbering has been changed since v6.1 from vX to vX.Y notation at DB model
					// but NOT reflected at filestore by migration procedure. So this is something the core needs to deal with ... !
					LogHandler::Log( __CLASS__, 'ERROR', BizResources::localize('ERR_ATTACHMENT') . $attachobj->getError() );
					//throw new BizException( 'ERR_ATTACHMENT', 'Server', $attachobj->getError() );
					// <<<
				}
			}
		}
	}

	/**
	 * Plug-ins can create new version of their own file by implementing the Version business connector.
	 * @param int $objectId Id of the object
	 * @param string $sourceVersion (<major>.<minor>)
	 * @param string $nextVersion (<major>.<minor>)
	 * @param string $storename Reference to the filestore.
	 * @param boolean $setObjPropMode Optional. Special case for SetObjectProperties context, conditionally creating versions. (See Note#001)
	 * @throws BizException
	 */
	static private function createVersionByConnector( $objectId, $sourceVersion, $nextVersion, $storename, $setObjPropMode )
	{	
		try {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			$connectors = BizServerPlugin::searchConnectors('Version', null);
			if ($connectors) foreach ( $connectors as $connClass => $connector ){
				LogHandler::Log(__CLASS__, 'DEBUG', 'Connector '.$connClass.' executes method createVersion');
				$connRetVals[$connClass] = call_user_func_array( array(&$connector, 'createVersion'), 
																 array( $objectId, $sourceVersion, $nextVersion, $storename, $setObjPropMode ) );
				LogHandler::Log(__CLASS__, 'DEBUG', 'Connector completed.' );
			}
		} catch( BizException $e ) {
			throw $e;
		}
	}

	/**
	 * Plug-ins can clean up their own files by implementing the Version business connector.
	 * @param int $objectId Id of the object
	 * @param string $version (<major>.<minor>)
	 * @param string $storename Reference to the filestore.
	 * @throws BizException
	 */
	static private function deleteVersionByConnector( $objectId, $version, $storename )
	{
		try {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			$connectors = BizServerPlugin::searchConnectors('Version', null);
			if ($connectors) foreach ( $connectors as $connClass => $connector ){
				LogHandler::Log(__CLASS__, 'DEBUG', 'Connector '.$connClass.' executes method deleteVersion');
				$connRetVals[$connClass] = call_user_func_array( array(&$connector, 'deleteVersion'), array( $objectId, $version, $storename ));
				LogHandler::Log('BizVersion', 'DEBUG', 'Connector completed.' );
			}
		} catch( BizException $e ) {
			throw $e;
		}
	}

	/**
	 * Determines the number of first-intermediate-versions to keep as well as the maximum number of
	 * intermediate versions to keep of database / filestore.
	 * This relies on the object type dependent settings MAX_<objtype>_VERSION
	 * @param string $type Object type
	 * @return integer Maximum number of intermediate version to keep
	 */
	static private function maxIntermediates( $type )
	{
		$type = str_replace( 'Template', '', $type ); // remove 'Template' postfix to get basic object type
		$type = ($type == 'LayoutModule') ? 'Layout' : $type; // treat layout modules same as layouts
		$maxDefine = 'MAX_'.strtoupper($type).'_VERSION'; // get name of define, such as MAX_ARTICLE_VERSION
		$max = defined($maxDefine) ? constant($maxDefine) : 5; // if not defined, default is 5 versions
		return is_numeric($max) && ($max > 0) ? $max : 0; // paranoid check; avoid negative numbers (zero means unending versions)
	}

	/**
	 * Calculates the next version to be used for create- and save operations.
	 * @param object $statusCfg Admin status definition (wherein the object currently resides).
	 * @param array  $verArr Returned values at keys "majorversion" and "minorversion"
	 * @return Next object version in major.minor notation (to be used for save/create). Empty on error.
	 */
	public static function determineNextVersionNr( $statusCfg, &$verArr )
	{
		if( !$statusCfg ) { // expected
			return ''; // error
		}
		if( $statusCfg->CreatePermanentVersion ) {
			DBVersion::nextPermanentVersion( $verArr );
		} else {
			DBVersion::nextIntermediateVersion( $verArr );
		}
		return DBVersion::joinMajorMinorVersion( $verArr );
	}

	/**
	 * Creates new object version when workflow status configuration tells us to do so.
	 * And, it removes intermediate versions when the target objct status has Remove Intermediate Versions option enabled.
	 *
	 * @param string   $id        Object id
	 * @param array    $currRow   Object DB row, assumed to be as-is
	 * @param array    $newRow    Object DB row, used to read new data from and updated with new version and status info.
	 * @param WorkflowMetaData $wflMetadata Optional. When given, it gets updated with new version and status info. Null to ignore.
	 * @param boolean  $createVer Force create version anyway
	 * @param string   $restoreVer Optional. Used to restore a specific version (major.minor notation).
	 * @param boolean  $setObjPropMode Optional. Special case for SetObjectProperties context, conditionally creating versions. (See Note#001)
	 * @throws BizException on failure
	 */
	public static function createVersionIfNeeded( $id, $currRow, &$newRow, &$wflMetadata, $createVer, $restoreVer = null, $setObjPropMode = false )
	{
		$newConverted = false;
		// If we get Biz props in we convert to DB props. In future input should be BizProps.
		if( array_key_exists( 'ID', $currRow ) ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 'createVersionIfNeeded - converted cur biz props to db props' );
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$currRow = BizProperty::objPropToRowValues( $currRow );
		}
		if( array_key_exists( 'ID', $newRow ) ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 'createVersionIfNeeded - converted new biz props to db props' );
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$newRow = BizProperty::objPropToRowValues( $newRow );
			$newConverted = true;
		}

		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$srcStatus = BizAdmStatus::getStatusWithId( $currRow['state'] );
		$tarStatus = ( $currRow['state'] == $newRow['state'] ) ? $srcStatus : BizAdmStatus::getStatusWithId( $newRow['state'] );
		$versions = DBVersion::getVersions( $id );

		if( $createVer || $restoreVer ||
			( $setObjPropMode && ( $tarStatus->CreatePermanentVersion && $currRow['minorversion'] !== 0 ) ) ) {
			// For Set Properties operations, create a new version when [A] the target status has CreatePermanentVersion
			// option set and the current object version is not a major version. See Note#001
			$newRow['version'] = self::createVersion( $id, $currRow, $tarStatus, $versions, $restoreVer, $setObjPropMode );
			DBVersion::splitMajorMinorVersion( $newRow['version'], $newRow );
			if( $wflMetadata ) {
				$wflMetadata->Version = $newRow['version'];
			}
			self::removeOldVersionWhenNewIsCreatedByIDSA( $currRow, $newRow );
		}

		self::removeIntermediateVersions( array( $id ), $currRow['type'], array( $id => $currRow['storename'] ),
			array( $tarStatus ), array( $id => $versions ) );

		if( $newConverted ) {
			$newRow = BizProperty::objRowToPropValues( $newRow );
		}
	}

	/**
	 * Removes previous version in case InDesign Server Automation (IDSA) created a new version.
	 *
	 * A new version is created the moment an object is saved by the IDSA process. This is in most cases unwanted as it
	 * results in a lot of versions. The moment a new version is created by the IDSA process the previous version is
	 * deleted. We can not overwrite the previous version as in that case client applications are not triggered that a
	 * new version is available. In the rare case that adjacent versions are required a hidden option is added:
	 * REMOVE_INTERMEDIATE_VERSION_BY_IDSA'. If set to 'false' a new version is created without removing the previous one.
	 * See: EN-87467.
	 *
	 * @param array $oldDBRow Original database properties of the object.
	 * @param array $newDBRow New database properties of the object.
	 * @throws BizException
	 */
	private static function removeOldVersionWhenNewIsCreatedByIDSA( array $oldDBRow, array $newDBRow )
	{
		if ( defined( 'REMOVE_INTERMEDIATE_VERSION_BY_IDSA' ) && REMOVE_INTERMEDIATE_VERSION_BY_IDSA == false ) {
			return;
		}

		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
		if( ( version_compare( $oldDBRow['version'], $newDBRow['version'] ) == -1 ) &&
			BizInDesignServerJobs::calledByIDSAutomation( BizSession::getTicket() )
		) {
			$types = unserialize( $oldDBRow['types'] ) ;
			self::deleteVersions( array( $oldDBRow['id'] => $oldDBRow['id'] ),
				array( $oldDBRow['id'] => $oldDBRow['version'] ),
				array( $oldDBRow['id'] => $oldDBRow['storename'] ),
				array( $oldDBRow['id'] => $types )
			);
		}
	}

	/**
	 * Multi-object version for creating new versions when required by the workflow status configuration
	 *
	 * This method is currently only intended for usage with multi-set properties.
	 *
	 * The version and status checks only perform sql queries for all objects at once.
	 * However when a new version must be created for an object, it will perform several queries on per object basis:
	 * - Retrieves the current object row from smart_objects
	 * - Inserts a new row into the smart_objectversions table (based on the just retrieved fields)
	 * - Updates smart_objects table with the new major and minor version fields for the object
	 *
	 * Triggering a "create new version" is exceptional when setting properties on multiple objects.
	 * This only happens when the target status has CreatePermanentVersion and no permanent version
	 * exists yet. Subsequent property changes won't trigger creating a version.
	 *
	 * @param array    $invokedObjects Essential metadata for each object where keys are the object ids and values its Object. Version is updated when needed.
	 * @param array    $newRow Updated new values for all objects
	 * @param DBAdmStatus[] $states All allowed states for the Objects.
	 * @throws BizException on failure
	 */
	public static function multiCreateVersionIfNeeded( &$invokedObjects, $newRow, $states )
	{
		// If the user changes the status for all the selected objects into Personal status, there's no need to
		// create a version. Note that the Personal status cannot be configured in the admin web pages and therefore
		// it doesn't have the CreatePermanentVersion option set.
		if( isset( $newRow['state'] ) && $newRow['state'] == -1 ) {
			return;
		}

		$newTarStatusCfg = null;
		if( array_key_exists( 'state', $newRow ) && array_key_exists($newRow['state'], $states)) {
			$newTarStatusCfg = $states[$newRow['state']];
		}

		// Get all existing versions for the passed objects
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		$versions = DBVersion::getVersionsForObjectIds( array_keys( $invokedObjects ) );

		$statusConfigs = array();

		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		$objectIds = array(); // To collect ids which will be sent to check if there's any intermediate versions that need to be removed.
		foreach( $invokedObjects as $id => $invokedObj ) {
			if( $newTarStatusCfg ) { // User changes the status for all selected objects?
				$tarStatusCfg = $newTarStatusCfg;
			} else {
				// If the user does not change the status (for all the selected objects) and if the object is in the
				// Personal status, there's no need to create a version. Note that the Personal status cannot be
				// configured in the admin web pages and therefore it doesn't have the CreatePermanentVersion option set.
				if( $invokedObj->WorkflowMetaData->State->Id == -1 ) { // Personal status?
					continue; // Proceed with next object.
				} else { // Take the status configuration of the object's current status.
					$tarStatusCfg =  $states[$invokedObj->WorkflowMetaData->State->Id];
				}
			}
			$objectIds[] = $id; // Ids of which will be checked later if its intermediate versions need to be removed(clean up).
			$statusConfigs[] = $tarStatusCfg;

			$versionInfo = array();
			DBVersion::splitMajorMinorVersion( $invokedObj->WorkflowMetaData->Version, $versionInfo ); // update newRow

			// For Set Properties operations, when [A] the target status has CreatePermanentVersion option disabled or
			// when [B] the current object version is a major version, we -avoid- creating new versions here !
			if( !$tarStatusCfg->CreatePermanentVersion || $versionInfo['minorversion'] == 0 ) {
				// nothing to do (See Note#001)
			} else {
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

				// Get object's current properties, needed for storing the current version in smart_objectversions
				$currRow = DBObject::getObjectRows( $id );

				// Store current object as the old version and get the new version number
				$newVersion = self::createVersion( $id, $currRow, $tarStatusCfg, $versions[$id], null, true );
				$invokedObj->WorkflowMetaData->Version = $newVersion;
				DBVersion::splitMajorMinorVersion( $invokedObj->WorkflowMetaData->Version, $versionInfo );

				// Update the new version in smart_objects table
				DBObject::updateObject( $id, null, $versionInfo, null );
			}

		}

		if( $objectIds ) {
			// Remove intermediate versions (when reached status with RemoveIntermediateVersions set)
			$firstObject = reset( $invokedObjects );
			$objectType = $firstObject !== false ? $firstObject->BasicMetaData->Type : null;
			self::removeIntermediateVersions( $objectIds, $objectType , null /* store names */, $statusConfigs, $versions );
		}
	}

	/**
	 * Returns one object version. Can not be used to get current version.
	 *
	 * @param string $id Object id
	 * @param string $user User id
	 * @param string $version Version to get
	 * @param string $rendition Rendition of file attachment to return. Typically 'preview'. Pass 'none' for no attachments.
	 * @param array $areas 'Workflow' or 'Trash' area where the object is resided.
	 * @throws BizException Throws BizException on failure
	 * @return object GetVersionResponse
	 */
	static public function getVersion( $id, $user, $version, $rendition, array $areas=null )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		if( !$id ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'No ID' );
		}

		// Next, check if we have an alien object (from content source, not in our database)
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				// We don't have a shadow, let content source handle the request:
				return BizContentSource::getAlienObjectVersion( $id, $version, $rendition );
			}
		}

		// get current version
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objProps = DBObject::getObjectProps( $id, $areas );

		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBLog::logServiceEx( $user, 'GetVersion', $objProps, null );

		// If we have a shadow, we allow content source to implement this call
		if( trim($objProps['ContentSource'])) {
			$ret = BizContentSource::getShadowObjectVersion( trim($objProps['ContentSource']), trim($objProps['DocumentID']), $id, $version, $rendition );
			if( !is_null($ret) ) return $ret;
		}

		// get version info
		$versions = DBVersion::getVersions( $id, $version );
		if( is_null($versions) || DBVersion::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBVersion::getError() );
		}
		if( count($versions) == 0 ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', BizResources::localize('OBJ_VERSION_ABBREVIATED').$version );
		}
		$verRow = $versions[0];

		$states = self::getVersionStatuses( $objProps, $verRow['state'] );
		$state = count( $states ) > 0 ? $states[$verRow['state']] : null;

		// check authorization, no attachments means: view mode otherwise read
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		$rights = ($rendition == 'none') ? 'V' : 'R';
		BizAccess::checkRightsForObjectProps( $user, $rights, BizAccess::THROW_ON_DENIED, $objProps );

		// create right attachment
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		$atobj = BizStorage::getVersionedFile( $objProps, $verRow, $rendition );

		// BZ#32303 - Get modifier fullname
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		$userFullname = DBUser::getFullName( $verRow['modifier'] );

		return new VersionInfo( $verRow['version'], $userFullname, trim($verRow['comment']),
										$verRow['slugline'], $verRow['created'], $objProps['Name'], $state, $atobj );
	}

	/**
	 * Gets the State objects for the specified object type.
	 *
	 * @param array $objProps
	 * @param int|null $getStateId
	 * @return array
	 * @throws BizException
	 */
	static private function getVersionStatuses( $objProps, $getStateId=null )
	{
		$pubId 	 = $objProps['PublicationId'];
		$issId 	 = array_key_exists( 'issueId', $objProps) ? $objProps['IssueId'] : null;
		$secId 	 = $objProps['SectionId'];
		$objType = $objProps['Type'];

		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';

		// get list of relevant states for extra state info
		$dbDriver = DBDriverFactory::gen();
		$sth = DBWorkflow::listStates( $pubId, $issId, $secId, $objType );
		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		$states = array();

		// BZ#18866: Tracking object status per version was introduced since v6.1. Migrated DBs (from v3/v4/v5) will have
		// default zero (0) filled in for the 'state' field of existing records at the smart_objectversions DB table.
		// Here we define a dummy status on index zero to return valid State elements through ListVersionsResponse.
		// Without this fix, InCopy/InDesign will raise error "An unexpected response from Enterprise occurred..."
		$states[0] = new State( 0, '-', $objType, false, '000000' ); // add dummy state

		// Optionally, add the Personal status
		if( strtolower(PERSONAL_STATE) == strtolower('ON') || $getStateId == -1 ) {
			$states[-1] = new State( -1, BizResources::localize('PERSONAL_STATE'),
								$objType, false, substr(PERSONAL_STATE_COLOR, 1) );
		}

		// Add the configured statuses as retrieved from DB
		while( ($sttRow = $dbDriver->fetch($sth) ) ) {
			if( $getStateId == null || $getStateId == $sttRow['id'] ) {
				$states[$sttRow['id']] = new State( $sttRow['id'], $sttRow['state'], $sttRow['type'],
								(trim($sttRow['produce']) == '') ? false : true,
								substr($sttRow['color'],1) );
			}
		}
		return $states;
	}

	/**
	 * Lists all tracked object versions, including the current object version.
	 * @param string $id Object id
	 * @param string $user User id
	 * @param string $rendition Rendition of file attachments to return. Typically 'thumb'. Pass 'none' for no attachments.
	 * @param array $areas 'Workflow' or 'Trash' area where the object is resided.
	 * @return array of VersionInfo objects
	 * @throws BizException on failure
	 */
	static public function listVersions( $id, $user, $rendition, array $areas=null )
	{
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';

		if( !$id ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'No ID' );
		}

		// Next, check if we have an alien object (from content source, not in our database)
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				// We don't have a shadow, let content source handle the request:
				return BizContentSource::listAlienObjectVersions( $id, $rendition );
			}
		}

		// get current version
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objProps = DBObject::getObjectProps( $id, $areas );

		DBLog::logServiceEx( $user, 'ListVersions', $objProps, null );

		// If we have a shadow, we allow content source to implement this call
		if( trim($objProps['ContentSource'])) {
			$ret = BizContentSource::listShadowObjectVersions( trim($objProps['ContentSource']), trim($objProps['DocumentID']), $id, $rendition );
			if( !is_null($ret) ) return $ret;
		}

		// get list of relevant states for extra status info
		$states = self::getVersionStatuses( $objProps, null ); // null => all statuses for this object type

		// call DB for versions
		$versions = DBVersion::getVersions( $id );

		// fetch versions into array
		$ret = array();
		foreach( $versions as $verRow ) {
			$userFullname = DBUser::getFullName( $verRow['modifier'] );
			if (!empty($rendition)) {
				$attachment = BizStorage::getVersionedFile( $objProps, $verRow, $rendition );
			} else {
				$attachment = null;
			}
			$ret[] = new VersionInfo( $verRow['version'], $userFullname, trim($verRow['comment']), $verRow['slugline'],
										$verRow['created'], $objProps['Name'], $states[$verRow['state']], $attachment );
		}

		// add current version
		if (count($ret) > 0) {
			$usr = $objProps['Modifier'];		// user = modifier
		} else {
			$usr = $objProps['Creator'];
		}
		$userFullname = DBUser::getFullName( $usr );

		$verNr = self::getCurrentVersionNumber( $objProps );
		if (!empty($rendition) && $rendition != 'none' ) {
			$attachment = BizStorage::getFile( $objProps, $rendition, $verNr );
		} else {
			$attachment = null;
		}
		$ret[] = new VersionInfo( $verNr, $userFullname, trim($objProps['Comment']), $objProps['Slugline'],
									$objProps['Modified'], $objProps['Name'], $states[$objProps['StateId']], $attachment );
		return $ret;
	}

	/**
	 * Restores an object version. Done by copying the specified version and making it the current.
	 * @param string $id Object id
	 * @param string $user User id
	 * @param string $restoreVer Object version (major.minor) to restore.
	 * @throws BizException on failure
	 */
	static public function restoreVersion( $id, $user, $restoreVer )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		if( !$id  ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'No ID' );
		}

		// Next, check if we have an alien object (from content source, not in our database)
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				// We don't have a shadow, let content source handle the request:
				BizContentSource::restoreAlienObjectVersion( $id, $restoreVer );
				return;
			}
		}

		// get current version of object
		$objRow = DBObject::getObjectRows( $id );
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php'; // to convert to biz props
		$objProps = BizProperty::objRowToPropValues( $objRow );

		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBLog::logServiceEx( $user, 'RestoreVersion', $objProps, null );

		$restored = false;
		// If we have a shadow, we allow content source to implement this call
		if( trim($objProps['ContentSource'])) {
			$restored = BizContentSource::restoreShadowObjectVersion( trim($objProps['ContentSource']), trim($objProps['DocumentID']), $id, $restoreVer );
			//TODO what to do if content source didn't restore the object?
			// For now, do the same as before (???): try to restore object ourselves.
		}

		if (!$restored){
			require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
			BizAccess::checkRightsForObjectProps( $user, 'WS', BizAccess::THROW_ON_DENIED, $objProps );

			// get version info
			$versions = DBVersion::getVersions( $id, $restoreVer );
			if( count($versions) == 0 ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', BizResources::localize('OBJ_VERSION_ABBREVIATED').$restoreVer );
			}
			$restoreVersion = $versions[0];

			// place lock
			require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
			DBObjectLock::lockObject( $id, $user );

			// update object based on rc and restoreVersion
			$newRow = array();
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$fields = BizProperty::getMetaDataObjFields();
			$fields = array_diff( $fields, array(null) ); // remove non-db props

			foreach( $fields as $field ) {
				if( DBVersion::isVersionField( $field ) && $field != 'state') {
					$newRow[$field] = $restoreVersion[$field];		// from version record
				} else {
					$newRow[$field] = $objRow[$field];		// from current record
				}
			}

			// create new version based on old version (restore of attachment is done inside createVersion)
			$wflMetadata = null;
			self::createVersionIfNeeded( $id, $objProps, $newRow, $wflMetadata, true, $restoreVer );

			$now = date('Y-m-d\TH:i:s');
			DBObject::updateObject( $id, $user, $newRow, $now );

			// to prevent confusion: delete all pages of previous version
			// so an empty preview is the consequence of a restore-version
			require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
			BizPage::cleanPages( $objProps['StoreName'], $id, 'Production', $objProps['Version'] );
			
			// Like pages, object operations are not versioned and so have the same problem.
			// After restoring a layout object, the pending operations may fail since 
			// the older version might no longer have the frames the operations refer to.
			// Here we remove the pending operations to avoid such problems.
			// As a result, pending operations are not processed for a restored layout.
			require_once BASEDIR.'/server/bizclasses/BizObjectOperation.class.php';
			BizObjectOperation::deleteOperations( $id );
			
			// Like pages, InDesignArticles are not versioned and so have the same problem.
			// Older versions of the layout may not have the InDesignArticles yet.
			// If we'd keep them, users might place objects onto their frames.
			// Here we remove the InDesignArticles (and their placements) to avoid such problems.
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
			require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
			DBInDesignArticle::deleteInDesignArticles( $id );
			DBPlacements::deletePlacements( $id, 0, 'Placed' );

			// Update object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
			require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
			require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
			$pubId = $objProps['PublicationId'];
			$pubName = DBPublication::getPublicationName( $pubId );
			$targets = BizTarget::getTargets( $user, $id );
			if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
				require_once BASEDIR.'/server/bizclasses/BizLinkFiles.class.php';
				BizLinkFiles::deleteLinkFiles( $pubId, $pubName, $id, $objProps['Name'], $targets );
				BizLinkFiles::createLinkFiles( $pubId, $pubName, $id, $newRow['name'], $newRow['type'],
							$newRow['version'], $newRow['format'], $newRow['storename'], $targets );
			}

			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			// read object with targets
			$object = BizObject::getObject( $id, $user, false, null,
				array('Relations', 'PagesInfo', 'Messages', 'Elements', 'Targets') );
			// Update the version of the parent object to keep the changes in sync.
			BizObject::updateVersionOfParentObject( $object );

			// release lock
			require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
			DBObjectLock::unlockObject( $id, $user );

			$restored = true;
		}

		if ($restored){
			// send restore version event
			require_once BASEDIR . '/server/smartevent.php';
			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			require_once BASEDIR . '/server/bizclasses/BizUser.class.php';

			// read object with targets
			$object = BizObject::getObject($id, $user, false, 'none', array('Targets'));
			$userfull = BizUser::resolveFullUserName($user);
			new smartevent_restoreversion( BizSession::getTicket(), $userfull, $object, $objProps['RouteTo']);

			// Notify event plugins
			require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
			BizEnterpriseEvent::createObjectEvent( $object->MetaData->BasicMetaData->ID, 'update' );
		}
		// no return
	}

	/**
	 * Determines / formats the current object version.
	 * @param array $verArr Object row with majorversion and minorversion key-values.
	 * @return string Version in major.minor notation.
	 */
	static public function getCurrentVersionNumber( $verArr )
	{
		return DBVersion::getVersionNumber( $verArr );
	}

	/** DEPRECATED - accepts DB row instead of Biz Props...
	 * Determines / formats the current object version.
	 * @param array $verArr Object row with majorversion and minorversion key-values.
	 * @return string Version in major.minor notation.
	 */
	static public function getCurrentVersionNr( $verArr )
	{
		// TO DO
		return DBVersion::joinMajorMinorVersion( $verArr );
	}

	/**
	 * Retrieves the current object version from DB.
	 * @param string $objId Object id.
	 * @return string Version in major.minor notation.
	 */
	static public function getCurrentVersionNrFromId( $objId )
	{
		return DBVersion::getCurrentVersionNrFromId( $objId );
	}
}