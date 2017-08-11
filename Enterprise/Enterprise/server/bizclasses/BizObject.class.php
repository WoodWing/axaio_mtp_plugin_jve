<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizObject
{
	/**
	 * This function adds the default values of properties into the given array.
	 *
	 * @param array $arr
	 * @param string $publishSystem
	 * @param integer $templateId
	 */
	private static function addDefaultsToArr(&$arr, $publishSystem, $templateId)
	{
		$publid = $arr['publication'];
		$objtype = $arr['type'];

		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$staticProps = array_flip( BizProperty::getStaticPropIds() );
		$staticProps = array_change_key_case( $staticProps, CASE_LOWER );

		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		$customprops = DBProperty::getProperties( $publid, $objtype, false, $publishSystem, $templateId );

		foreach( $customprops as $custompropname => $customprop ) {
			//BZ#10907 always lowercase db fields
			$custompropname = strtolower($custompropname);
			if( !array_key_exists($custompropname, $arr) &&
				!array_key_exists($custompropname, $staticProps) ) { // filtered out static properties

				$defaultValue = $customprop->DefaultValue;
				if( $customprop->Type == 'bool' ) {
					$defaultValue = ( strtolower( $defaultValue ) == 'true' || $defaultValue == 1 ) ? 1 : 0;
				}
				$arr[$custompropname] = $defaultValue;
			}
		}
	}



	/**
	 * Retrieves the Parent of the InstanceOf relation for the specified Object.
	 *
	 * @static
	 * @param Object $object The object to find the InstanceOf relational parent for.
	 * @return null|int The Parent Object ID or null if not found.
	 */
	public static function getInstanceOfRelationalParentId($object)
	{
		$parent = null;

		if (!is_null($object->Relations)) foreach ($object->Relations as $relation) {
			// If a valid relation is found the Object can be assumed to be a PublishForm.
			if ($relation->Type == 'InstanceOf') {
				$parent = $relation->Parent;
			}
		}
		return $parent;
	}

	/**
	 * Adjusts the Object so it matches the criteria for a new PublishForm.
	 *
	 * @throws BizException $e Throws an exception if the Parent Object cannot be retrieved.
	 * @throws BizException Throws an exception if the Relations for the object are incorrect.
	 *
	 * @param Object $object The Object to be adjusted.
	 * @param string $user The User for which to adjust the Object.
	 * @param bool $lock the Object Lock.
	 * @return Object The transformed Object.
	 */
	public static function transformIntoPublishForm($object, $user, $lock)
	{
		require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';

		// Attempt to determine the InstanceOf relational Parent.
		$publishFormParent = self::getInstanceOfRelationalParentId($object);
		if (is_null($publishFormParent)) {
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve the parent for the PublishForm Object.');
		}

		// Attempt to get the Parent Object.
		$publishFormParent = self::getObject( $publishFormParent, $user, $lock, null,
			null, null, false, null, null );

		// PublishForm's 'Contained' relation Targets should also be stored in
		// 'InstanceOf' relation Target. This is needed to keep track which channel
		// this PublishForm belongs to.
		$instanceOfIndex = null;
		$formTargets = null;
		if( $object->Relations ) foreach( $object->Relations as $index => $relation ) {
			if( $relation->Type == 'InstanceOf' ) {
				if( !$relation->Targets || is_null( $relation->Targets ) ) {
					$instanceOfIndex = $index;
				}
			} else if( $relation->Type == 'Contained' ) {
				$formTargets = $relation->Targets;
			}
		}
		if( !is_null( $instanceOfIndex )) {
			$object->Relations[$instanceOfIndex]->Targets = $formTargets;
		}

		// Validate the Object's Relations.
		if (!BizObjectComposer::validatePublishFormRelations( $object )) {
			$message = 'The object should have two Relations, one \'Contained\' pointed at a Dossier, with a Target '
				. 'pointed to the Template Target, and an \'InstanceOf\' Relation pointed at a PublishFormTemplate.';
			throw new BizException( 'ERR_ERROR', 'Server',  $message);
		}

		// Attempt to adjust the MetaData for the new Object.
		if ($publishFormParent) {
			// MetaData should not be set when creating a PublishForm object, therefore error if it is set.
			$object = BizObjectComposer::compose($user, null, $object, $publishFormParent);
			if (is_null($object)) {
				LogHandler::Log('BizObject', 'ERROR', 'The object could not be converted to a PublishFormObject.');
			}
		}
		return $object;
	}

	/**
	 * Adds a new object to Enterprise.
	 *
	 * @param Object $object New object with properties to be stored into the system.
	 * @param string $user User doing the request.
	 * @param bool $lock Object must be locked after it is created.
	 * @param bool|null $autonaming Whether or not to make up an unique name for the object. DEPRECATED since 9.3.0.
	 * @param bool $replaceGuid Whether or not to replace guids in wcml articles.
	 * @param string|null $rendition
	 * @param string[]|null $requestInfo
	 * @return Object The object as added to the system.
	 * @throws BizException
	 */
	public static function createObject( /** @noinspection PhpLanguageLevelInspection */
		Object $object, $user, $lock,
		/** @noinspection PhpUnusedParameterInspection */$autonaming = null, $replaceGuid = false,
		$rendition = null, $requestInfo = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		//  require_once BASEDIR.'/server/bizclasses/BizObjectJob.class.php'; // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.

		// BZ#9827 $targets can now be null, if this is the case:
		// for now: DO NOT CHANGE EXISTING BEHAVIOR
		if ($object->Targets == null) {
			$object->Targets = array();
		}

		// If the Object was intended as a PublishForm object, transform it thusly.
		if (BizPublishForm::isPublishForm( $object )) {
			BizRelation::validateFormContainedByDossier( null, $object->Targets, $object->Relations );
			$object = self::transformIntoPublishForm($object, $user, $lock);
		}

		// Validate targets count, layouts can be assigned to one issue only
		BizWorkflow::validateMultipleTargets( $object->MetaData, $object->Targets );

		// Validate (and correct,fill in) workflow properties
		BizWorkflow::validateWorkflowData( $object->MetaData, $object->Targets, $user );

		// Only when the relations are given we can filter on PublishSystem and TemplateId
		$publishSystem = $templateId = null;
		if ( $object->Relations ) {
			list( $publishSystem, $templateId ) = self::getPublishSystemAndTemplateId( null, $object->Relations );

			// EN-36057 - Loop through all the relations, when it is new parent dossier object relation,
			// validate the dossier object name, if exist, stop the creation of the child and parent dossier object.
			foreach( $object->Relations as $relation ) {
				if( $relation->Parent == -1 && $relation->Type == 'Contained' ) {
					self::nameValidation(
						$user,
						$object->MetaData,
						null,
						$object->MetaData->BasicMetaData->Name,
						'Dossier',
						$object->Targets,
						null,
						false );
				}
			}
		}

		if ( $object->Targets ) { // Layouts/Dossiers have object targets and their names can be checked immediately.
			self::nameValidation(
				$user,
				$object->MetaData,
				null,
				$object->MetaData->BasicMetaData->Name,
				$object->MetaData->BasicMetaData->Type,
				$object->Targets,
				null,
				false );
		}

		// Validate and fill in name and meta data
		// adjusts $object and returns flattened meta data
		$arr = self::validateForSave( $user, $object, null );
		self::addDefaultsToArr($arr, $publishSystem, $templateId);

		if( $arr['type'] == 'Dossier' ) {
			BizRelation::validateDossierContainsForms( null, $object->Targets, $object->Relations );
		}

		// determine new version nr for the new object
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$status = BizAdmStatus::getStatusWithId( $object->MetaData->WorkflowMetaData->State->Id );

		// Check authorization
		$rights = 'RW'; // check 'Read/Write' access (R and W): Read is added for EN-88613
		if( $arr['type'] == 'Dossier' || $arr['type'] == 'DossierTemplate' ) {
			$rights .= 'd'; // check the 'Create Dossier' access (d) (BZ#17051)
		} else if( $arr['type'] == 'Task' ) {
			$rights .= 't'; // check 'Create Task' access (t) (BZ#17119)
		}
		BizAccess::checkRightsForMetaDataAndTargets(
			$user, $rights, BizAccess::THROW_ON_DENIED,
			$object->MetaData, $object->Targets ); // BZ#17119

		// If possible (depends on DB) we get id for new object beforehand:
		$dbDriver = DBDriverFactory::gen();
		$id = $dbDriver->newid(DBPREFIX."objects", false);
		$storename = $id ? StorageFactory::storename($id, $arr) : '';

		if (!isset($arr['issue'])) {
			$arr['issue'] = BizTarget::getDefaultIssueId($arr['publication'], $object->Targets);
		}

		$now = date('Y-m-d\TH:i:s');

		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		$isShadowObject = BizContentSource::isShadowObject($object);

		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

		$userfull = BizUser::resolveFullUserName($user);
		// If this is a shadow object and the creator is already filled in use this value
		if( !$isShadowObject || !isset($object->MetaData->WorkflowMetaData->Creator) ) {
			$object->MetaData->WorkflowMetaData->Creator = $userfull;
			$arr['creator'] = $user;
		} else {
			$userRow = DBUser::getUser($object->MetaData->WorkflowMetaData->Creator);
			$arr['creator'] = $userRow['user']; // should always be the username
		}

		if( !$isShadowObject || !isset($object->MetaData->WorkflowMetaData->Created) ) {
			$arr['created'] = $now;
			$object->MetaData->WorkflowMetaData->Created = $now;
		} else {
			$created = date('Y-m-d\TH:i:s', strtotime($object->MetaData->WorkflowMetaData->Created));
			$arr['created'] = $created;
			$object->MetaData->WorkflowMetaData->Created = $created;
		}

		// If this is a shadow object and the modifier is already filled in use this value
		if ( !$isShadowObject || !isset($object->MetaData->WorkflowMetaData->Modifier) ) {
			$object->MetaData->WorkflowMetaData->Modifier = $userfull;
			$arr['modifier'] = $user;
		} else {
			$userRow = DBUser::getUser($object->MetaData->WorkflowMetaData->Modifier);
			$arr['modifier'] = $userRow['user']; // should always be the username
		}
		// If this is a shadow object and the modified date is already filled in use this value
		if ( !$isShadowObject || !isset($object->MetaData->WorkflowMetaData->Modified) ) {
			$arr['modified'] = $now;
			$object->MetaData->WorkflowMetaData->Modified = $now;
		} else {
			$modified = date('Y-m-d\TH:i:s', strtotime($object->MetaData->WorkflowMetaData->Modified));
			$arr['modified'] = $modified;
			$object->MetaData->WorkflowMetaData->Modified = $modified;
		}

		if ( $isShadowObject && self::useContentSourceVersion( $object->MetaData->BasicMetaData->ContentSource)) {
			if ( isset($object->MetaData->WorkflowMetaData->Version)) {
				require_once BASEDIR . '/server/dbclasses/DBVersion.class.php';
				$versionInfo = array();
				if ( DBVersion::splitMajorMinorVersion( $object->MetaData->WorkflowMetaData->Version, $versionInfo ) ) {
					$arr['majorversion'] = $versionInfo['majorversion'];
					$arr['minorversion'] = $versionInfo['minorversion'];
				}
			}
		} else {
			$object->MetaData->WorkflowMetaData->Version = BizVersion::determineNextVersionNr( $status, $arr );
		}
		$arr['version'] = $object->MetaData->WorkflowMetaData->Version;

		if( isset($arr['id']) ) { // EN-18998 - Unset 'id' identity field, to avoid new object insertion failed
			unset($arr['id']);
		}

		// Create object record in DB:
		$sth = DBObject::createObject( $storename, $id, $arr['modifier'], $arr['created'], $arr, $arr['modified'] );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// If we did not get an id from DB beforehand, we get it now and update storename
		// that is derived from object id
		if (!$id) {
			$id = $dbDriver->newid(DBPREFIX."objects",true);
			if (!$id) {
				throw new BizException( 'ERR_DATABASE', 'Server', 'No ID' );
			}
		}
		$object->MetaData->BasicMetaData->ID = $id;

		// The saveTargets() call below sends UpdateObjectTargets events and the saveExtended() call below
		// sends CreateObjectsRelations event, which needs to be exposed until we have sent the CreateObjects
		// event, as done at the end of this service (BZ#15317). Note that the createDossierFromObject() call
		// does recursion(!) and so another queue is created. The SmartEventQueue does stack(!) the queues
		// to make sure that events do not get mixed up. Recursion causes queues to get stacked. The top most
		// queue is finalized first, by sending out events in its own context before getting back to caller (unstack).
		require_once BASEDIR.'/server/smartevent.php'; // also SmartEventQueue
		SmartEventQueue::createQueue();

		// Validate targets and store them at DB for this object
		BizTarget::saveTargets( $user, $id, $object->Targets, $object->MetaData );

		// Validate meta data and targets (including validation done by Server Plug-ins)
		self::validateMetaDataAndTargets( $user, $object->MetaData, $object->Targets, $object->Relations, false );

		// BZ#10526 If you want to create relations with the created object you cannot enter the parent or child
		// so do it here
		/* v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		$serverJobs = array();
		$updateParentJob = new WW_BizClasses_ObjectJob();
		*/
		if (! is_null($object->Relations)) {
			foreach ($object->Relations as $relation){
				if (empty($relation->Parent)){
					$relation->Parent = $id;
				} elseif (empty($relation->Child)){
					$relation->Child = $id;
				}
				// don't check if parent/child has $id filled in by client because it has just been created => unlikely
				// BZ#10526 special case: create dossier when parent is -1 and type is Contained
				if ($relation->Parent == -1 && $relation->Type == 'Contained'){
					$dossierObject = self::createDossierFromObject($object);
					if (! is_null($dossierObject)){
						$relation->Parent = $dossierObject->MetaData->BasicMetaData->ID;
					}
					else {
						//TODO else delete relation?
					}
				}

				// Don't delete object targets for overruled issues
				$overruledIssue = isset($object->Targets[0]->Issue) && $object->Targets[0]->Issue->OverrulePublication;
				if( !$overruledIssue ) {
					// BZ#16567 Delete object targets (if not a layout)
					// BZ#17915 Article created in dossier should remain without issues
					// BZ#18405 Delete object targets only if there are targets
					$objTypes = array('Layout', 'LayoutTemplate', 'PublishForm', 'PublishFormTemplate'); // BZ#20886
					if ($relation->Parent > 0 && $relation->Child == $id && $relation->Type == 'Contained' &&
						!in_array( $object->MetaData->BasicMetaData->Type, $objTypes ) && // BZ#20886
						isset($object->Targets) && count($object->Targets) > 0 ) {
						//See BZ#17852 After creating Dossier from Layout (Create) checkin Issue information on layout is wrong.
						BizTarget::deleteTargets($user, $id, $object->Targets);
					}
				}

				/* v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
				$childId = $id; // $childId is the newly created Object ($id)
				if( $relation->Type == 'Contained' &&  $childId == $relation->Child ){
					if( !isset($serverJobs[$childId]) ){
						$serverJobs[$childId] = true;
						$updateParentJob->createUpdateTaskInBgJob( $childId, // childId
																   null // parentId
																 );
					}
				}
				*/
			}
		}

		// Replace the InCopy text component GUIDs in the native file and in the Object's Elements.
		if ( $replaceGuid == true && ( $arr['type'] == 'Article' || $arr['type'] = 'ArticleTemplate' )) {
			self::replaceGuidOfArticle( $object );
		}

		// Object record is now created, now save other stuff elements, relations, messages, etc.
		self::saveExtended( $id, $object, $user, true );

		// Set the deadline. Must be called after the object/relational targets are in place.
		// Handle deadline
		// First look for object-target issues
		$issueIdsDL = self::getTargetIssuesForDeadline( $object );
		// Image/Article without object-target issue can inherit issues from relational-targets. BZ#21218

		$section = isset($arr["section"]) ? $arr["section"] : null;

		// Determine if it is normal brand or overruleIssue.
		$overruleIssueId = 0;
		if( count( $issueIdsDL ) == 1 ) { // When there are more than 1 issue targeted to an Object, it's definitely not an overruleIssue, so don't need to check.
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$overruleIssueId = DBIssue::isOverruleIssue( $issueIdsDL[0] ) ? $issueIdsDL[0] : 0;
		}

		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$pubId = $object->MetaData->BasicMetaData->Publication->Id;
		if( BizPublication::isCalculateDeadlinesEnabled( $pubId, $overruleIssueId ) ) {
			DBObject::objectSetDeadline( $id, $issueIdsDL, $section, $arr['state'] );
		}

		// If requested we lock the object
		if( $lock ) {
			self::Lock( $id, $user );
		}

		// ==== So far it was DB only, now involve files:
		// Save object's files:
		$storename = StorageFactory::storename( $id, $arr );
		/*$sth = */ DBObject::updateObject( $id, null, array(), null, $storename );
		self::saveFiles( $storename, $id, $object->Files, $object->MetaData->WorkflowMetaData->Version );
		// When an image is created, the thumb and preview need to be removed from the TransferServer.
		if ('Image' == $arr['type'] ){
			LogHandler::Log('BizObject', 'DEBUG', 'Removing temporary image files from TransferServer.');
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			foreach( $object->Files as $index => $file ) {
				// Only remove cache files for the thumb and preview. CS takes care of the native file.
				if (('preview' == $file->Rendition) || ('thumb' == $file->Rendition)){
					// Delete file and unset the index from the Objects files.
					$transferServer->deleteFile( $file->FilePath );
					LogHandler::Log('BizObject', 'DEBUG', 'Removed temp file: ' . $file->FilePath);
					unset($object->Files[$index]);
				}
			}
		}

		// Clear the files array, the files are moved to the filestore so they aren't available anymore.
		$object->Files = array();

		// Save pages (both files and DB records)
		BizPage::savePages( $storename, $id, 'Production', $object->Pages, FALSE, null, $object->MetaData->WorkflowMetaData->Version );

		// Create server job to generate preview/thumb async
		//require_once BASEDIR.'/server/bizclasses/BizMetaDataPreview.class.php';
		//$bizMetaPreview = new BizMetaDataPreview();
		//$bizMetaPreview->generatePreviewLater( $object );
		// L> COMMENTED OUT: Server Jobs should not be used for workflow production in Ent 8.0

		// === Saving done, now we do the after party:

		// Get object from DB to make sure we have it all complete for notifications as well to return to caller
		if( is_null( $requestInfo ) ) {
			$requestInfo = self::getDefaultRequestInfos();
		}
		$object = self::getObject( $object->MetaData->BasicMetaData->ID, $user, false,
			$rendition,	$requestInfo );

		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			// Update object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
			require_once BASEDIR . '/server/bizclasses/BizLinkFiles.class.php';
			BizLinkFiles::createLinkFilesObj( $object, $storename );
		}

		// Add to search index:
		require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
		BizSearch::indexObjects( array( $object ), true/*$suppressExceptions*/, array('Workflow')/*$areas*/, true );

		// Do notifications
		$issueIds = $issueNames = $editionIds = $editionNames = '';
		self::listIssuesEditions( $object->Targets, $issueIds, $issueNames, $editionIds, $editionNames );
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		if (!array_key_exists('routeto',$arr)) {
			$arr['routeto'] = '';
		}
		DBlog::logService( $user, 'CreateObjects', $id, $arr['publication'], null, $arr['section'], $arr['state'],
			'', $lock, '', $arr['type'], $arr['routeto'], $editionNames, $arr['version'] );
		SmartEventQueue::startFire(); // let next coming CreateObjects event through directly (BZ#15317).
		new smartevent_createobjectEx( BizSession::getTicket(), $userfull, $object);
		SmartEventQueue::fireQueue(); // Typically fires postponed CreateObjectsRelations and UpdateObjectTargets events (BZ#15317).

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createObjectEvent( $object->MetaData->BasicMetaData->ID, 'create' );

		BizEmail::sendNotification( 'create object', $object, $arr['types'], null );
		if( MTP_SERVER_DEF_ID != '' ) {
			require_once BASEDIR.'/server/MadeToPrintDispatcher.class.php';
			MadeToPrintDispatcher::doPrint( $id, BizSession::getTicket() );
		}

		return $object;
	}

	/**
	 * Creates a new object based on a given object template. Currently layouts are supported only.
	 *
	 * @since 9.7.0
	 * @param string $templateId
	 * @param string $user
	 * @param Object $object The object to create.
	 * @param boolean $lock Whether or not to lock the created object.
	 * @param string $rendition Which rendition to return for the created object.
	 * @param string[]|null $requestInfo Which information to return for the created object.
	 * @return Object The created object, instantiated from template.
	 * @throws BizException on fatal error.
	 */
	public static function instantiateTemplate($templateId, $user, /** @noinspection PhpLanguageLevelInspection */
	                                           Object $object, $lock, $rendition, $requestInfo )
	{
		// Retrieve the template object from DB.
		static $templates = null;
		if( isset( $templates[$templateId] ) ) { // might be called iterative, so cache the templates
			$template = $templates[$templateId];
		} else {
			$tplRequestInfo = array( 'MetaData', 'PagesInfo', 'Relations', 'Targets', 'InDesignArticles', 'Placements', 'ObjectOperations' ); // stuff to copy
			$template = self::getObject( $templateId, $user, false, 'native', $tplRequestInfo ); // $lock=false

			// The above is to get the native, but getObjects does not return page previews/thumbs at once,
			// so when caller is requesting for that, we have to grab the page renditions separately.
			if( $rendition == 'preview' || $rendition == 'thumb' ) {
				$tmpTemplate = self::getObject( $templateId, $user, false, $rendition, array( 'NoMetaData', 'Pages' ) ); // $lock=false
				$template->Pages = $tmpTemplate->Pages;
			}
			$templates[$templateId] = $template; // session cache
		}

		// Validate the given parameters.
		if( $template->MetaData->BasicMetaData->Type != 'LayoutTemplate' &&
			$template->MetaData->BasicMetaData->Type != 'LayoutModuleTemplate' ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'TemplateId is not a LayoutTemplate.' );
		}
		if( $object->MetaData->BasicMetaData->Type != 'Layout' ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Object property "Type" should be set to Layout.' );
		}
		if( $template->MetaData->BasicMetaData->Publication->Id != $object->MetaData->BasicMetaData->Publication->Id ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Layout and LayoutTemplate are assigned to different brands.' );
		}
		if( count( $object->Targets ) != 1 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Layout should have (exactly) one Target.' );
		}
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		if(	DBIssue::isOverruleIssue( $object->Targets[0]->Issue->Id ) ||
			DBIssue::isOverruleIssue( $template->Targets[0]->Issue->Id ) ) {
			if( $object->Targets[0]->Issue->Id != $template->Targets[0]->Issue->Id ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Layout and LayoutTemplate are assigned to different Overrule Issues.' );
			}
		}
		if( !isset($object->MetaData->WorkflowMetaData->State->Id) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Layout should have a workflow Status ("State" property).' );
		}

		//EN-86496 Placed relations should be copied from the template to the new layout.
		$placedRelations = array();
		foreach( $template->Relations as $relation ) {
			if( $relation->Type == 'Placed' ) {
				//Remove any relational information pertaining the template. This will be resolved to the layout information later on.
				$relation->Parent = null;
				$relation->ParentInfo = null;
				$relation->ParentVersion = null;
				$placedRelations[] = $relation;
			}
		}

		// Inherit some from the template, and some from the given object.
		$newObject = $template;
		$newObject->MetaData->BasicMetaData->ID = null;
		$newObject->MetaData->BasicMetaData->DocumentID = null; // to be resolved by SC
		$newObject->MetaData->BasicMetaData->Name = $object->MetaData->BasicMetaData->Name;
		$newObject->MetaData->BasicMetaData->Type = $object->MetaData->BasicMetaData->Type;
		$newObject->MetaData->BasicMetaData->ContentSource = null;
		$newObject->MetaData->WorkflowMetaData = new WorkflowMetaData(); // clear wfl props
		$newObject->MetaData->WorkflowMetaData->State = $object->MetaData->WorkflowMetaData->State;
		$newObject->Relations    = array_merge( $object->Relations, $placedRelations );
		$newObject->Messages     = $object->Messages;
		$newObject->Targets      = $object->Targets;
		$newObject->MessageList  = $object->MessageList;
		$newObject->ObjectLabels = $object->ObjectLabels;
		$newObject->Operations   = $object->Operations;

		return self::createObject( $newObject, $user, $lock, null, false, $rendition, $requestInfo );
	}

	/**
	 * Provides the full set of the RequestInfo param, used for Create/Save/GetObjects services.
	 *
	 * @return string[]
	 */
	private static function getDefaultRequestInfos()
	{
		return array( 'Relations', 'PagesInfo', 'Messages', 'Elements', 'Targets', 'InDesignArticles', 'Placements' );
	}

	public static function saveObject( /** @noinspection PhpLanguageLevelInspection */
		Object $object, $user, $createVersion, $unlock )
	{
		require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		require_once BASEDIR.'/server/bizclasses/BizDeadlines.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		//		require_once BASEDIR.'/server/bizclasses/BizObjectJob.class.php'; // v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.

		$id = $object->MetaData->BasicMetaData->ID;
		$dbDriver = DBDriverFactory::gen();

		// Next, check if we have an alien object (from content source, not in our database)
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			// Check if all users in the metadata are known within the system. If not, create them and import remaining
			// information when such a user logs in through LDAP when enabled.
			self::getOrCreateResolvedUsers($id, $object->MetaData);
			// Check if we already have a shadow object for this alien. If so, change the id
			// to the shadow id
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				LogHandler::Log('bizobject','DEBUG','No shadow found for alien object '.$id);
				throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
			}
		}

		// get current record in db
		$sth = DBObject::getObject( $id );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$currRow = $dbDriver->fetch($sth);
		if (!$currRow) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}

		// BZ#8475: Oracle specific fix - on Oracle ID as well as id is set...
		// this leads further code to believe it is a list of properties, not a databaserow...
		if (isset($currRow['id']) && isset($currRow['ID'])) {
			unset($currRow['ID']);
		}

		$curPub   = $currRow['publication'];
		$curSect  = $currRow['section'];
		$curState = $currRow['state'];

		// Publication, Category, Content Source are crucial to have, but can be empty on save when they are not changed, so fill them in with current values:
		if( !$object->MetaData->BasicMetaData->Publication || !$object->MetaData->BasicMetaData->Publication->Id ) {
			$object->MetaData->BasicMetaData->Publication = new Publication( $curPub );
		}
		if( !$object->MetaData->BasicMetaData->Category || !$object->MetaData->BasicMetaData->Category->Id ) {
			$object->MetaData->BasicMetaData->Category = new Category( $curSect );
		}
		if ( !empty( $currRow['contentsource'] && empty( $object->MetaData->BasicMetaData->ContentSource ) ) ) {
			$object->MetaData->BasicMetaData->ContentSource = $currRow['contentsource'];
		}

		// Determine the current- and new targets and issue.
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$curTargets = DBTarget::getTargetsByObjectId( $id );
		$newTargets = is_null($object->Targets) ? $curTargets : $object->Targets;
		$curIssueId = $curTargets && count($curTargets) ? $curTargets[0]->Issue->Id : 0;
		$newIssueId = $newTargets && count($newTargets) ? $newTargets[0]->Issue->Id : 0;

		// Validate targets count, layouts can be assigned to one issue only
		BizWorkflow::validateMultipleTargets( $object->MetaData, $object->Targets );

		// Validate (and correct,fill in) workflow properties
		BizWorkflow::validateWorkflowData( $object->MetaData, $object->Targets, $user, $curState );

		// Validate and fill in name and meta data
		// adjusts $object and returns flattened meta data
		$newRow = self::validateForSave( $user, $object, $currRow );
		$state = $newRow['state'];

		// Does the user has a lock for this file?
		$lockedby = DBObjectLock::checkLock( $id );
		if( !$lockedby ){
			// object not locked at all:
			$sErrorMessage = BizResources::localize("ERR_NOTLOCKED").' '.BizResources::localize("SAVE_LOCAL");
			throw new BizException( null, 'Client', $id, $sErrorMessage );
		} else if( strtolower($lockedby) != strtolower($user) ) {
			//locked by someone else
			throw new BizException( 'ERR_NOTLOCKED', 'Client', $id );
		}

		// First, authorization check against current values:
		// - If status changes, we need to see if that is allowed.
		// - When in personal status (-1), status change is always allowed.
		if( $curState != $state ) {
			$hasRightF = BizAccess::checkRightsForObjectRow(
				$user, 'F', BizAccess::DONT_THROW_ON_DENIED, $currRow, $curIssueId );
			$hasRightC = BizAccess::checkRightsForObjectRow(
				$user, 'C', BizAccess::DONT_THROW_ON_DENIED, $currRow, $curIssueId );
			if( ( $state != -1 && $curState != -1 ) && $state == DBWorkflow::nextState( $curState ) ) {
				// Check rights: Change && Forward
				if( !$hasRightF && !$hasRightC ) {
					throw new BizException( 'ERR_AUTHORIZATION', 'Client', "$id(F)" );
				}
			} elseif( !$hasRightC ) { // Check rights: Change (to any status)
				throw new BizException( 'ERR_AUTHORIZATION', 'Client', "$id(C)" );
			}
		}

		// Next, authorization check against new values:
		// - Check if we have Write access to the new status.
		BizAccess::checkRightsForParams( $user, 'W', BizAccess::THROW_ON_DENIED,
			$newRow['publication'], $newIssueId, $newRow['section'], $newRow['type'], $state,
			$currRow['id'], $currRow['contentsource'], $currRow['documentid'], $currRow['routeto'] );

		// validate Publish Form and Dossier
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';

		if ( BizPublishForm::isPublishForm( $object )) {
			BizRelation::validateFormContainedByDossier( $object->MetaData->BasicMetaData->ID, $object->Targets, $object->Relations );
		}
		if( $object->MetaData->BasicMetaData->Type == 'Dossier' ) {
			BizRelation::validateDossierContainsForms( $object->MetaData->BasicMetaData->ID, $object->Targets, $object->Relations );
		}

		// Do some DB clean-up before save, remove flags, etc.
		self::cleanUpBeforeSave( $object, $newRow, $curState );

		// Create version if needed, even if $createVersion is false a version might be generated
		BizVersion::createVersionIfNeeded( $id, $currRow, $newRow, $object->MetaData->WorkflowMetaData, $createVersion );

		$now = date('Y-m-d\TH:i:s');

		// Clear indexed flag, so object will be re-indexed when search engine used:
		// At Create it's initialized empty, SetProps does not modify this to prevent overkill
		// of re-indexing again and again. It's assumed that the indexed meta properties don't
		// change after creation.
		$newRow['indexed'] = '';

		// Save to DB:
		$sth = DBObject::updateObject($id, $user, $newRow, $now);
		if (!$sth)	{
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		// v4.2.4 patch for #5700, Save current time be able to send this later as modified time
		$now = date('Y-m-d\TH:i:s');

		$userfull = BizUser::resolveFullUserName($user);

		// Set Modifier for return
		$object->MetaData->WorkflowMetaData->Modified = $now;
		$object->MetaData->WorkflowMetaData->Modifier = $userfull;

		// Delete object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
		$oldtargets = BizTarget::getTargets($user, $id);
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$pubName = DBPublication::getPublicationName($curPub);
		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			require_once BASEDIR . '/server/bizclasses/BizLinkFiles.class.php';
			BizLinkFiles::deleteLinkFiles( $curPub, $pubName, $id, $currRow['name'], $oldtargets );
		}

		// Validate targets and store them at DB for this object
		// BZ#9827 Only save targets if not null
		if ($object->Targets !== null) {
			BizTarget::saveTargets( $user, $id, $object->Targets, $object->MetaData );

			// Validate meta data and targets (including validation done by Server Plug-ins)
			self::validateMetaDataAndTargets( $user, $object->MetaData, $object->Targets, null, FALSE );
		}

		// Object record is now changed, now save other stuff elements, relations, messages, etc.
		self::saveExtended( $id, $object, $user, false );

		// ===== Handle deadline.
		// Collect object-/relational-target issues from object
		$issueIdsDL = self::getTargetIssuesForDeadline( $object );
		// If deadline is set and object has issues check if the set deadline is not beyond earliest possible deadline
		if( $issueIdsDL && isset($newRow['deadline']) && $newRow['deadline'] ) {
			BizDeadlines::checkDeadline($issueIdsDL, $newRow['section'], $newRow['deadline']);
		}
		// If no deadline set, calculate deadline, else just store the deadline
		$deadlinehard = '';
		$oldDeadline = DBObject::getObjectDeadline( $id );
		if( isset($newRow['deadline']) && $newRow['deadline'] ) {
			$deadlinehard = $newRow['deadline'];
			if ( $oldDeadline !== $deadlinehard ) {
				DBObject::setObjectDeadline( $id, $deadlinehard );
				if ( BizDeadlines::canPassDeadlineToChild( $newRow['type'] ) ) {
					// Set the deadlines of children without own object-target issue.
					BizDeadlines::setDeadlinesIssuelessChilds( $id, $deadlinehard );
				}
			}
		} else { // No deadline set, calculate (when "Activate Relative Deadlines" is enabled in brand admin page.).
			// Determine if it is normal brand or overruleIssue.
			$overruleIssueId = 0;
			if( count( $issueIdsDL ) == 1 ) { // When there are more than 1 issue targeted to an Object, it's definitely not an overruleIssue, so don't need to check.
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				$overruleIssueId = DBIssue::isOverruleIssue( $issueIdsDL[0] ) ? $issueIdsDL[0] : 0;
			}

			require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
			if( BizPublication::isCalculateDeadlinesEnabled( $newRow['publication'], $overruleIssueId ) ) {
				$deadlines = DBObject::objectSetDeadline( $id, $issueIdsDL, $newRow['section'], $newRow['state'] );
                $deadlinehard = $deadlines['Deadline'];
				if ( $oldDeadline !== $deadlinehard ) {
					if ( BizDeadlines::canPassDeadlineToChild( $newRow['type'] ) ) {
						// Recalculate the deadlines of children without own object-target issue.
						// This recalculation is limited to an issue change of the parent.
						// New issue of the parent results in new relational-target issue and so
						// a new deadline. If the category of the parent changes this has no effect
						// as the children do not inherit this change.
						BizDeadlines::recalcDeadlinesIssuelessChilds( $id );
					}
				}
			}
		}

		// Broadcast (soft) deadline (Broadcast only when deadlinehard is given by user or re-calculated.
		if ( $oldDeadline !== $deadlinehard ) {
			require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
			$deadlinesoft = DateTimeFunctions::calcTime( $deadlinehard, -DEADLINE_WARNTIME );
			require_once BASEDIR . '/server/smartevent.php';
			new smartevent_deadlinechanged( null, $id, $deadlinehard, $deadlinesoft );
		}
		// ==== So far it was DB only, now involve files:

		// For shadow objects we now pass control to Content Source which may influence what to do with file storing
		// as it can modify $object
		if( trim($currRow['contentsource']) ) {
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			BizContentSource::saveShadowObject( trim($currRow['contentsource']), trim($currRow['documentid']), $object );
		}

		// Save object's files:
		self::saveFiles( $currRow['storename'], $id, $object->Files, $object->MetaData->WorkflowMetaData->Version );

		// Clear the files array, the files are moved to the files store so they aren't available anymore
		$object->Files = array();

		// Save pages (both files and DB records)
		BizPage::savePages( $currRow['storename'], $id, 'Production', $object->Pages, TRUE, $currRow['version'], $object->MetaData->WorkflowMetaData->Version );

		// Create server job to generate preview/thumb async
		//require_once BASEDIR.'/server/bizclasses/BizMetaDataPreview.class.php';
		//$bizMetaPreview = new BizMetaDataPreview();
		//$bizMetaPreview->generatePreviewLater( $object );
		// L> COMMENTED OUT: Server Jobs should not be used for workflow production in Ent 8.0

		// === Saving done, now we do the after party:

		// Get object from DB to make sure we have it all complete for notifications as well to return to caller
		$object = self::getObject( $object->MetaData->BasicMetaData->ID, $user, false, null,
			self::getDefaultRequestInfos() );

		// See if the Object is a placement on the PublishForm.
		self::updateVersionOfParentObject( $object );

		// Update contained Objects if needed.
		self::updateContainedObjects( $object );

		// Update relational targets
		self::updateObjectRelationTargets( $user, $object );

		/* v8.0: Uncomment when serverJob 'UpdateParentModifierAndModified' is supported again.
		$serverJobs = array();
		$updateParentJob = new WW_BizClasses_ObjectJob();
		if( $object->Relations ) foreach ( $object->Relations as $relation ){
			if( $relation->Type == 'Contained' &&  $id == $relation->Child ){
				if( !isset($serverJobs[$id]) ){
					$serverJobs[$id] = true;
					$updateParentJob->createUpdateTaskInBgJob( $id, // childId
															   null // parentId
															 );
				}
			}
		}
		*/

		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			// Create object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
			require_once BASEDIR . '/server/bizclasses/BizLinkFiles.class.php';
			BizLinkFiles::createLinkFilesObj( $object, $currRow['storename'] );
		}

		// Unlock if needed - this is done 'as late as possible', hence it's behind the file operations
		if ($unlock) {
			self::unlockObject( $id, $user, true, false );
			$object->MetaData->WorkflowMetaData->LockedBy = ''; // used for notification below
		}

		// Update search index:
		require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
		BizSearch::updateObjects( array( $object ), true/*$suppressExceptions*/, array('Workflow')/*$areas*/  );


		// Do notifications
		$issueIds = $issueNames = $editionIds = $editionNames = '';
		self::listIssuesEditions( $object->Targets, $issueIds, $issueNames, $editionIds, $editionNames );
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( $user, 'SaveObjects', $id, $newRow['publication'], null, $newRow['section'], $newRow['state'],
			'', '', '', $newRow['type'], $newRow['routeto'], $editionNames, $newRow['version'] );
		if( MTP_SERVER_DEF_ID != '' ) {
			require_once BASEDIR.'/server/MadeToPrintDispatcher.class.php';
			MadeToPrintDispatcher::doPrint( $id, BizSession::getTicket() );
		}
		require_once BASEDIR.'/server/smartevent.php';

		new smartevent_saveobjectEx( BizSession::getTicket(), $userfull, $object, $currRow['routeto'] );

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createObjectEvent( $object->MetaData->BasicMetaData->ID, 'update' );

		BizEmail::sendNotification( 'save object' , $object, $newRow['types'], $currRow['routeto']);

		// Optionally send geo update for placed articles of a saved layout, but only if we are not using XMLGeo
		// XMLGeo would require a geo update file per article which we don't have
		if( isset($object->Relations) && ( $newRow['type'] == 'Layout' || $newRow['type'] == 'PublishForm' )
			&& strtolower(UPDATE_GEOM_SAVE) == strtolower('ON') ) {
			if (! BizSettings::isFeatureEnabled('UseXMLGeometry') ) {
				foreach ($object->Relations as $relation) {
					// If someone else has object lock, send notification
					if( strtolower(DBObjectLock::checkLock( $relation->Child )) !=  strtolower($user) ) {
						new smartevent_updateobjectrelation(BizSession::getTicket(), $relation->Child, $relation->Type, $id, $newRow['name']);
					}
				}
			}
		}

		// Check if elements of an article are placed more than once on same/different layout
		if( $newRow['type'] == 'Layout' || $newRow['type'] == 'LayoutModule' ) {
			if( isset($object->Relations) && is_array($object->Relations) && count($object->Relations) > 0 ) {
				require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
				$object->MessageList = BizMessage::getMessagesForObject( $id );
				require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
				BizRelation::signalParentForDuplicatePlacements( $id, $object->Relations, $user, $object->MessageList->Messages, false );
			}
		}

		return $object;
	}

	/**
	 * Get object from Enterprise or content source
	 *
	 * @param string $id Enterprise object id or Alien object id
	 * @param string $user
	 * @param bool $lock
	 * @param string $rendition
	 * @param array $requestInfo
	 * @param string $haveVersion
	 * @param bool $checkRights
	 * @param array $areas
	 * @param string $editionId Optional. Used to get edition/device specific file renditions.
	 * @param boolean $callGetShadowObject Whether or not to call getShadowObject.
	 * @param array $supportedContentSources A list of the content sources that are understood by a client.
	 * @return Object
	 * @throws BizException Throws BizException when error occurs during object retrieve.
	 */
	public static function getObject( $id, $user, $lock, $rendition, $requestInfo = null,
	                                  $haveVersion = null, $checkRights = true, array $areas=null, $editionId=null,
	                                  $callGetShadowObject=true, array $supportedContentSources=null )
	{
		// if $requestInfo not set, we fallback on old (pre v6) defaults which depend on rendition
		// At the moment we cannot reliably see difference between empty array and nil, so we use
		// these defaults for both cases. To be finetuned for v7.0
		if (empty($requestInfo)) {
			$requestInfo = array();
			switch( $rendition ) {
				case 'thumb':
					break;
				default:
					$requestInfo[] = 'Pages';
					$requestInfo[] = 'Relations';
					$requestInfo[] = 'Messages';
					$requestInfo[] = 'Elements';
					$requestInfo[] = 'Targets';
					break;
			}
		}

		// A client (or internal caller) might request for relations of an Object that resides in the Trash.
		// However, there's a functional and technical problem with this;
		// Functionally, an end-user would be confused if he/she would see a deleted Dossier in the Trash that contains
		// an Image, but that Image is not deleted (not in Trash).
		// Technically, it's not allowed to return deleted relations since the 'Deleted...' relation types are purposely
		// not defined in the WSDL. This is an internal solution that should not be revealed to the outside world.
		// In fact, the client/caller should never ask for relations of objects that are in the Trash. However,
		// this kind of logic is not something to expect from client/caller, and therefore we remove the request for
		// relations. However, in the response the relations will be set to null instead of empty to indicate that
		// we didn't retrieve relations from the database. (BZ#34509)
		if( $areas && in_array( 'Trash', $areas ) ) {
			$relationIndex = array_search( 'Relations', $requestInfo );
			if( $relationIndex !== false ) {
				unset( $requestInfo[$relationIndex] ); // remove 'Relations'
			}
		}
		$fullMetaData = !in_array( 'NoMetaData', $requestInfo );

		//  work around PEAR bug that does not parse 1 element arrayofstring into an array....  JCM
		if( is_object($requestInfo) ) $requestInfo = array( $requestInfo->String );

		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

		// Validate input:
		// check for empty id
		if (!$id) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'SCEntError_ObjectNotFound: null' );
		}

		$alienId = null;

		// Next, check if we have an alien object (from content source, not in our database)
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			$alienId = $id;

			// Check if we already have a shadow object for this alien. If so, change the id
			// to the shadow id
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				LogHandler::Log('bizobject','DEBUG','No shadow found for alien object '.$id);
				// Determine if we should pass request to Content Source or that we should first
				// create a shadow object out of the alien object.
				// We do the latter when placement renditon is asked which needs a relation and thus shadow
				// or when the object is locked.
				if( $rendition == 'placement' || $lock ) {
					// create shadow object for alien
					$shadowObject = BizContentSource::createShadowObject( $id, null );
					$shadowObject = self::createObject( $shadowObject, $user, false /*lock*/, empty($shadowObject->MetaData->BasicMetaData->Name) /*$autonaming*/ );
					// Change alien id into new shadow id, normal Get will continue taking care of getting
					// the new shadow
					$id = $shadowObject->MetaData->BasicMetaData->ID;
				}
				else {
					// get alien object, lock is requested by Properties dialog
					$a= BizContentSource::getAlienObject( $id, $rendition, $lock );
					return $a;
				}
			}
		}

		if ( $alienId ) {
			self::getOrCreateResolvedUser( $alienId, $user );
		}

		// Lock the object, might be premature
		$updateIndex = false;
		if( $lock ) {
			self::Lock( $id, $user );
			require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
			// Indexing an object is an expensive operation. Check if 'LockedBy' is indexed.
			if ( BizSearch::isPropertySearchable( 'LockedBy')) {
				$updateIndex = true;
			}
		}

		// Now enter in a try catch, so we can release the premature lock when something goes wrong:
		try {
			list( $publishSystem, $templateId ) = self::getPublishSystemAndTemplateId( $id );

			// get the object
			$objectProps = BizQuery::queryObjectRow($id, $areas, $publishSystem, $templateId);
			if (!$objectProps) { // check for not found
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'SCEntError_ObjectNotFound: '.$id );
			}

			// get object's targets
			if( in_array('Targets', $requestInfo) ) {
				$targets = DBTarget::getTargetsByObjectId($id);
				foreach ($targets as $target) {
					if ($target->Issue->Id && $target->PubChannel->Id) {
						$objectProps['IssueId'] = $target->Issue->Id;
						$objectProps['Issue'] = $target->Issue->Name;
						break;
					}
				}
			} else {
				$targets = null;
			}

			if ($checkRights) {
				self::checkAccessRights( $lock, $user, $objectProps );
			}

			if (!empty($objectProps['RouteToUser'])) {
				$objectProps['RouteTo'] = $objectProps['RouteToUser'];
			}elseif( !empty( $objectProps['RouteToGroup'] ) ) {
				$objectProps['RouteTo'] = $objectProps['RouteToGroup'];
			}

			// The rows from the database won't contain custom properties when in the Trash.
			$customProps = ( $areas && in_array('Trash', $areas) ) ? false : true;
			$meta = self::queryRow2MetaData( $objectProps, $customProps, $publishSystem, $templateId, $fullMetaData );


			if( in_array('Pages', $requestInfo) || in_array('PagesInfo', $requestInfo) ) {
				$pages = array();
				require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
				// Since v5.0, first try to get planned pages... when not available, we fall back at produced pages.
				// We do this for native/none requests only; planned pages have NO renditions, and so we keep
				// the possibility open to retrieve output/preview page files (which is backwards compat with v4.2)
				// which is typically used by applications such as Smart Mover.
				if( $rendition == 'none' || $rendition == 'native' ) {
					$pages = BizPage::getPageFiles( $id, 'Planning', $objectProps['StoreName'],
						in_array('Pages', $requestInfo) ? $rendition : 'none',
						$objectProps['Version'] );
				}
				// Get produced pages
				if( count( $pages ) <= 0 ) {
					$pages = BizPage::getPageFiles( $id, 'Production', $objectProps['StoreName'],
						in_array('Pages', $requestInfo) ? $rendition : 'none',
						$objectProps['Version'] );
				}
			} else {
				// When pages is not requested, it should return null instead of empty array.
				// -Empty- array means no pages were found which is not the case;
				// -Null- indicates that the server did not resolve pages at all (since it was not requested).
				$pages = null;
			}

			if( in_array('Relations', $requestInfo) ) {
				require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
				// BZ#14481 only attach geo info when server feature "UseXMLGeometry" is on, object type is Article and rendition != none (BZ#8657)
				$attachGeo =
					$objectProps['Type'] == 'Article' &&
					BizSettings::isFeatureEnabled('UseXMLGeometry') &&
					$rendition != 'none';
				$relations = BizRelation::getObjectRelations(
					$id,
					$attachGeo,
					in_array( 'Targets', $requestInfo ),
					null, // use $id in both ways to resolve relations (as parent and child)
					false, // get from workflow and trashcan
					in_array( 'ObjectLabels', $requestInfo ),
					null // get all relation types
				);
			} else {
				$relations = null;
			}

			// Signal layout when an element of an article is placed more than once on same/different layout.
			if( in_array('Messages', $requestInfo) ) {
				require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
				$messageList = BizMessage::getMessagesForObject( $id );
				if( $rendition == 'native' && $objectProps['Type'] == 'Layout' || $objectProps['Type'] == 'LayoutModule' ) {
					require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
					BizRelation::signalParentForDuplicatePlacements( $id, $relations, $user, $messageList->Messages, false );
				}
			} else {
				$messageList = null;
			}
			if( in_array('Elements', $requestInfo) ) {
				require_once BASEDIR.'/server/dbclasses/DBElement.class.php';
				$elements = DBElement::getElements($id);
			} else {
				$elements = null;
			}

			// v7.5 Return rendition information.
			if( in_array('RenditionsInfo', $requestInfo) ) {
				require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
				$renditionsInfo = DBObjectRenditions::getEditionRenditionsInfo( $id );
			} else {
				$renditionsInfo = null;
			}

			// v9.1 Return Object Labels
			require_once BASEDIR.'/server/bizclasses/BizObjectLabels.class.php';
			$objectLabelTypes = BizObjectLabels::getObjectLabelsEnabledParentObjectTypes();
			if( in_array('ObjectLabels', $requestInfo) && in_array($meta->BasicMetaData->Type, $objectLabelTypes) ) {
				require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
				$objectLabels = DBObjectLabels::getLabelsByObjectId( $meta->BasicMetaData->ID );
			} else {
				$objectLabels = null;
			}

			// v9.7 Return InDesign Articles
			if( in_array('InDesignArticles', $requestInfo) ) {
				require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
				$indesignArticles = DBInDesignArticle::getInDesignArticles( $id );
			} else {
				$indesignArticles = null;
			}

			// v9.7 Return Layout Placements (e.g. for InDesign Articles)
			if( in_array('Placements', $requestInfo) ) {
				require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
				$placements = DBPlacements::getPlacements( $id, 0, 'Placed' );
			} else {
				$placements = null;
			}

			// v9.7 Return layout operations.
			if( in_array('ObjectOperations', $requestInfo) ) {
				require_once BASEDIR.'/server/bizclasses/BizObjectOperation.class.php';
				$operations = BizObjectOperation::getOperations( $id );
			} else {
				$operations = null;
			}

			// Build object to return caller.
			$object = new Object();
			$object->MetaData     = $meta;
			$object->Relations    = $relations;
			$object->Pages        = $pages;
			$object->Elements     = $elements;
			$object->Targets      = $targets;
			$object->Renditions   = $renditionsInfo;
			$object->MessageList  = $messageList;
			$object->ObjectLabels = $objectLabels;
			$object->InDesignArticles = $indesignArticles;
			$object->Placements   = $placements;
			$object->Operations   = $operations;

			// If we are getting an shadow object, call content source provider to possibly fill in the files.
			// This way it's up to the Content Source provider to get the files from Enterprise or
			// from the content source, which could be dependent on the rendition.
			// Also the content source can manipulate the meta data:
			if( $callGetShadowObject && trim($objectProps['ContentSource']) ) {
				try {
					require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
					BizContentSource::getShadowObject( trim($objectProps['ContentSource']),
						trim($objectProps['DocumentID']), $object, $objectProps, $lock,
						$rendition, $requestInfo, $supportedContentSources, $haveVersion );
				} catch( BizException $e ) {
					// Let's be robust here; When the Content Source connector has been unplugged, an exception is thrown.
					// Nevertheless, when not asked for a rendition, we already have the metadata, so there is no reason
					// to panic. Logging an error is a more robust solution, as done below. This typically is needed to let
					// Solr indexing/unindexing process continue. An throwing an exception would disturb this badly.
					if( $rendition == 'none' && $e->getMessageKey() == 'ERR_NO_CONTENTSOURCE' ) {
						LogHandler::Log( 'bizobject', 'ERROR', 'Could not get shadow object for '.
							'Content Source "'.$objectProps['ContentSource'].'". But since no rendition '.
							'is requested, and for the sake of robustness, the Enterprise object is returned instead.' );
					} else {
						throw $e;
					}
				}
			}

			// if files is null we get the files, in case of a shadow they could have been filled in
			// by the content source
			if( is_null($object->Files) && $rendition != 'none' ) { // Only resolve the Files when rendition is requested, otherwise should leave it as Null.
				$attachment = null;
				// BZ#13297 don't get files for native and placement renditions when haveversion is same as object version
				if ($rendition && $rendition != 'none'
					&& ! ( ($rendition == 'native' || $rendition == 'placement')
						&& $haveVersion === $object->MetaData->WorkflowMetaData->Version ) ) {
					require_once BASEDIR.'/server/bizclasses/BizStorage.php';
					if( $editionId ) { // edition/device specific rendition
						require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
						$version = DBObjectRenditions::getEditionRenditionVersion( $id, $editionId, $rendition );
					} else { // object rendition
						$version = $objectProps['Version'];
					}
					if( !is_null($version) ) {
						$attachment = BizStorage::getFile( $objectProps, $rendition, $version, $editionId );
					}
				}
				if( $attachment ) {
					$object->Files = array( $attachment );
				} else {
					$object->Files = array();
				}
			}
		} catch ( BizException $e ) {
			// Remove premature lock and re-throw exception
			if( $lock ) {
				self::unlockObject( $id, $user, false );
			}
			throw($e);
		}

		// Do notfications:
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logServiceEx( $user, 'GetObjects', $objectProps, array( 'lock' => $lock, 'rendition' => $rendition ) );
		if ( $lock ) {
			$userfull = BizUser::resolveFullUserName($user);
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_lockobject(BizSession::getTicket(), $id, $userfull);
		}

		if (!isset($object->MetaData->WorkflowMetaData->RouteTo) && $fullMetaData ) {
			$object->MetaData->WorkflowMetaData->RouteTo = '';
		}
		// Update search index:
		if ( $updateIndex) {
			BizSearch::updateObjects( array( $object ) );
		}

		return $object;
	}

	/**
	 * This function tries to resolve the relations and see if there is an 'InstanceOf' relation.
	 * When there is a 'InstanceOf' relation (in v9 this is only used for Publish Forms) it tries to
	 * resolve the parent id (template) and publish system (channel of the parent).
	 *
	 * @param integer $objectId
	 * @param array $relations
	 * @return array as array( $publishSystem, $templateId )
	 * @throws BizException
	 *
	 * @since 9.0
	 */
	public static function getPublishSystemAndTemplateId( $objectId, $relations = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

		static $templateIdAndPublishSystem = array();
		if ( $objectId && isset($templateIdAndPublishSystem[$objectId]) ) {
			return $templateIdAndPublishSystem[$objectId];
		}

		$templateId = null;
		$publishSystem = null;

		if ( is_null($relations) ) {
			if ( !$objectId ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'Object ID should be set for ' . __METHOD__ . ' when relations is null.' );
			}
			$relations = BizRelation::getObjectRelations( $objectId, false, false, null, false, false, 'InstanceOf' );
		}

		if ( $relations ) foreach ( $relations as $relation ) {
			if ( $relation->Type == 'InstanceOf' ) {
				$templateId = $relation->Parent;
				$targets = BizTarget::getTargets('', $templateId);
				if ( $targets ) foreach ( $targets as $target ) {
					$publishSystem = DBChannel::getPublishSystemByChannelId($target->PubChannel->Id);
					break;
				}
				break;
			}
		}

		$retVal = array( $publishSystem, $templateId );
		// **Make sure we store the real value instead of null; otherwise it will lead to missing value and
		// causes SQL error such as 'select * from smart_objects where id = ' (With empty $templateId.)
		if( !is_null( $publishSystem ) && !is_null( $templateId ) // See above **.
			&& $objectId ) {
			$templateIdAndPublishSystem[$objectId] = $retVal;
		}
		return $retVal;
	}

	/**
	 * Removes the lock of an object.
	 *
	 * @param int $id Object id
	 * @param string $user Short user name
	 * @param bool $notify Send broadcast message
	 * @param bool $updateIndex reindex object after unlocking
	 * @throws BizException
	 */
	public static function unlockObject( $id, $user, $notify=true, $updateIndex = true )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

		// Validate input
		if (!$id) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}

		// Check if we have alien object. This happens for properties dialog which when executed
		// will create an Enterprise object. So the subsequent lock can be ignored.
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject( $id ) ) {
			// Check if we already have a shadow object for this alien. If so, change the id
			// to the shadow id
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				return;
			}
		}

		// Get the object to unlock
		$dbDriver = DBDriverFactory::gen();
		$sth = DBObject::getObject( $id );
		if (!$sth) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}
		$curr_row = $dbDriver->fetch($sth);
		if (!$sth) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}

		// check if object has been locked in the first place (BZ#11254)
		$lockedByUser = DBObjectLock::checkLock( $id );
		if (! is_null($lockedByUser) ){
			// Check if unlock is allowed by user
			if( DBUser::isAdminUser( $user ) || // System Admin user?
				DBUser::isPubAdmin( $user, $curr_row['publication'] ) ) { // Brand Admin user?
				$effuser = null; // System/Brand Admin users may always unlock objects
			} else { // Normal user
				// Normal users are allowed to unlocked their own locks only (BZ#11160)
				if( strtolower($lockedByUser) != strtolower($user) ) { // Locked by this user?
					$lockedByUser = BizUser::resolveFullUserName($lockedByUser);
					$msg = BizResources::localize('OBJ_LOCKED_BY') . ' ' . $lockedByUser; // TODO: Add user param in OBJ_LOCKED_BY resource
					throw new BizException( null, 'Client',  $id, $msg );
				}
				// Note: We do NOT check the "Abort Checkout" access feature here!
				// This is because UnlockObjects service is used by SOAP clients to close the document.
				// Without this access feature, users should be able to close their documents.
				// Client applications are responsible to show/hide the "Abort Checkout" action from GUI.
				$effuser = $user;
			}

			// Now do the unlock
			DBObjectFlag::unlockObjectFlags($id);
			$sth = DBObjectLock::unlockObject( $id, $effuser );
			if (!$sth) {
				throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
			}

			require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
			// Indexing an object is an expensive operation. Check if 'LockedBy' is indexed.
			if ( $updateIndex && BizSearch::isPropertySearchable( 'LockedBy')) {
				BizSearch::indexObjectsByIds(array( $id ));
			}

			// Notify event plugins
			require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
			BizEnterpriseEvent::createObjectEvent( $id, 'update' );

			// Do notifications
			if( $notify ) {
				require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
				DBlog::logService( $user, "UnlockObjects", $id, $curr_row['publication'], $curr_row['issue'], $curr_row['section'],
					$curr_row['state'], '', '', '', $curr_row['type'], $curr_row['routeto'], '', $curr_row['version'] );
				$routetofull = BizUser::resolveFullUserName($curr_row['routeto']);
				require_once BASEDIR.'/server/smartevent.php';
				new smartevent_unlockobject( BizSession::getTicket(), $id, '', false, $routetofull);
			}
		}
	}

	/**
	 * Set the Object properties given the MetaData and Targets.
	 *
	 * @throws BizException
	 * @param int $id
	 * @param string $user
	 * @param MetaData $meta
	 * @param array $targets
	 * @return WflSetObjectPropertiesResponse
	 */
	public static function setObjectProperties( $id, $user, MetaData $meta, $targets )
	{
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/bizclasses/BizDeadlines.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';

		// TODO v6.0: Handle targets (expect one, return many)
		// TODO v6.0: Return saved/resolved meta data

		// Validate and prepare
		if (!$id) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}

		// Check if we have an alien as source. If so, we first check if
		// we have a shadow, if so this will be used. If there is no shadow
		// we need to import a he alien which is handled totally different
		require_once BASEDIR .'/server/bizclasses/BizContentSource.class.php';
		$alienId = null;
		if( BizContentSource::isAlienObject($id) ){
			$alienId = $id;
			// It's an alien, do we have a shadow? If so, use that instead
			$shadowID = BizContentSource::getShadowObjectID($id);
			if( $shadowID ) {
				$id = $shadowID;
			} else {
				// An alien without shadow, we treat this as a create:
				$destObject = new Object( $meta,			// meta data
					null, null, null,		// relations, pages, Files array of attachment
					null, null, $targets	// messages, elements, targets
				);
				// Check if all users in the metadata are known within the system. If not, create them and import remaining
				// information when such a user logs in through LDAP when enabled.
				self::getOrCreateResolvedUsers($id, $meta);

				$shadowObject = BizContentSource::createShadowObject( $id, $destObject );
				$shadowObject = self::createObject( $shadowObject, $user, false /*lock*/, empty($shadowObject->MetaData->BasicMetaData->Name) /*$autonaming*/ );

				require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectPropertiesResponse.class.php';
				return new WflSetObjectPropertiesResponse( $shadowObject->MetaData, $targets );
			}
		}

		if ( $alienId ) {
			// Check if all users in the metadata are known within the system. If not, create them and import remaining
			// information when such a user logs in through LDAP when enabled.
			self::getOrCreateResolvedUsers($alienId, $meta);
		}

		// Check if locked or we are the locker
		$lockedby = DBObjectLock::checkLock( $id );
		if( $lockedby && strtolower($lockedby) != strtolower($user) ) {
			throw new BizException( 'ERR_NOTLOCKED', 'Client', $id );
		}

		// Get object's current properties
		$dbDriver = DBDriverFactory::gen();
		$sth = DBObject::getObject( $id );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$currRow = $dbDriver->fetch($sth);
		if (!$currRow) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}
		$curPub		= $currRow['publication'];
		$curSection	= $currRow['section'];
		$curType	= $currRow['type'];
		$curState 	= $currRow['state'];

		// Determine the current- and new targets and issue.
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		$curTargets = DBTarget::getTargetsByObjectId( $id );
		$newTargets = is_null($targets) ? $curTargets : $targets;
		$curIssueId = $curTargets && count($curTargets) ? $curTargets[0]->Issue->Id : 0;
		$newIssueId = $newTargets && count($newTargets) ? $newTargets[0]->Issue->Id : 0;

		// Key props, if not set use the current values, needed for auth check etc.
		if( !isset($meta->BasicMetaData->ID)){ $meta->BasicMetaData->ID = $id; }
		if( !isset($meta->BasicMetaData->Name)){ $meta->BasicMetaData->Name = $currRow['name']; }
		if( !isset($meta->BasicMetaData->Type)){ $meta->BasicMetaData->Type = $curType; }
		if( !isset($meta->BasicMetaData->Publication)){	$meta->BasicMetaData->Publication = new Publication( $curPub ); }
		if( !isset($meta->BasicMetaData->Category)){ $meta->BasicMetaData->Category = new Category( $curSection ); }
		if( !$meta->WorkflowMetaData ){ $meta->WorkflowMetaData = new WorkflowMetaData(); }

		// Validate workflow meta data and adapt if needed
		BizWorkflow::validateWorkflowData( $meta, $targets, $user, $curState );

		// Validate targets count, layouts can be assigned to one issue only
		BizWorkflow::validateMultipleTargets( $meta, $targets );

		// Validate the properties and adjust (defaults) if needed, returned flattened meta data:
		self::validateMetaDataAndTargets( $user, $meta, $targets, null, false );
		$isShadowObject = ($alienId) ? true : false;
		$newRow = self::getFlatMetaData( $meta, $id, null, $isShadowObject );
		$state = $newRow['state'];
		$categoryId = $newRow['section'];

		// If state changes, we need to check if we are allowed to move out of old status
		if( $curState != $state ) {
			$hasRightF = BizAccess::checkRightsForObjectRow(
				$user, 'F', BizAccess::DONT_THROW_ON_DENIED, $currRow, $curIssueId );
			$hasRightC = BizAccess::checkRightsForObjectRow(
				$user, 'C', BizAccess::DONT_THROW_ON_DENIED, $currRow, $curIssueId );
			if( ( $curState != -1 && $state != -1 ) && $state == DBWorkflow::nextState( $curState ) ) {
				// Check rights: Change && Forward
				if( !$hasRightF && !$hasRightC ) {
					throw new BizException( 'ERR_AUTHORIZATION', 'Client', "$id(F)" );
				}
			} elseif( !$hasRightC ) { // Check rights: Change (to any status)
				throw new BizException( 'ERR_AUTHORIZATION', 'Client', "$id(C)" );
			}
		}

		// Next check if we are allowed to modify the object in its existing place (open for edit access)
		// When there is no E-access it may still be that we have W-access (BZ#5519). In that case don't fail.
		if( !BizAccess::checkRightsForObjectRow( $user, 'E', BizAccess::DONT_THROW_ON_DENIED, $currRow, $curIssueId )
			 && !BizAccess::checkRightsForObjectRow( $user, 'W', BizAccess::DONT_THROW_ON_DENIED, $currRow, $curIssueId )
				 // If open for edit is disabled, it could be that user can only edit unplaced files. So first check if user
				 // has that right and if they do check if the object is actually placed.
			 && (!BizAccess::checkRightsForObjectRow( $user, 'O', BizAccess::THROW_ON_DENIED, $currRow, $curIssueId )
					|| BizRelation::hasRelationOfType( $id, 'Placed', 'parents' )) ) {
				throw new BizException( 'ERR_AUTHORIZATION', 'Client', "$id(E)" );
		}

		// Next, check if we have access to destination.
		BizAccess::checkRightsForParams( $user, 'W', BizAccess::THROW_ON_DENIED,
			$newRow['publication'], $newIssueId, $newRow['section'], $newRow['type'], $newRow['state'],
			$currRow['id'], $currRow['contentsource'], $currRow['documentid'], $currRow['routeto'] );

		// Check if user is allowed to change the object's location (ChangePIS)
		if($meta->BasicMetaData->Publication->Id != $curPub ||
			$categoryId != $curSection ){
			BizAccess::checkRightsForObjectRow( $user, 'P', BizAccess::THROW_ON_DENIED, $currRow, $curIssueId );
		}

		// Validate target change: BZ#30518
		$oldtargets = BizTarget::getTargets($user, $id);
		if( ($targets !== null) && $meta->BasicMetaData->Type == 'Dossier' ) { // If targets are passed as null, ignore.
			$targetsToBeRemoved = BizTarget::getTargetsToBeRemoved($oldtargets, $targets);
			if ( $targetsToBeRemoved ) {
				foreach ( $targetsToBeRemoved as $targetToBeRemoved ) {
					require_once BASEDIR. '/server/dbclasses/DBPublishHistory.class.php';
					if (DBPublishHistory::isDossierPublished( $id, $targetToBeRemoved->PubChannel->Id, $targetToBeRemoved->Issue->Id, null )) {
						require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
						require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
						$issueInfo = DBIssue::getIssue( $targetToBeRemoved->Issue->Id);
						$objectName = DBObject::getObjectName( $id );
						$params = array($issueInfo['name'], $objectName);
						throw new BizException( 'ERR_MOVE_DOSSIER', 'Client', '', null, $params);
					}
				}
				// When Targets are removed, the Targets that have been removed
				// need to be checked if there are any related PublishForm(s) in the Dossier.
				// These PublishForm(s) needs to be moved to the TrashCan when the related Target
				// is removed, otherwise an orphan PublishForm (without a Target,PubChannel)
				// will remain in the Dossier.
				BizTarget::removePublishForm( $id, $targetsToBeRemoved, $user );
			}
		}

		if( $curType == 'PublishForm' ) {
			BizRelation::validateFormContainedByDossier( $id, $targets, null ); // let the function to resolve the relations.
		} else if( $curType == 'Dossier' ) {
			BizRelation::validateDossierContainsForms( $id,  $targets, null ); // let the function to resolve the relations.
		}
		// Execute the SetProps action:

		// Depending on configuration, setting props could lead to new version:
		BizVersion::createVersionIfNeeded( $id, $currRow, $newRow, $meta->WorkflowMetaData, false, null, true );

		// Keep MtP in the loop:
		if( MTP_SERVER_DEF_ID != '' ) {
			require_once BASEDIR.'/server/MadeToPrintDispatcher.class.php';
			MadeToPrintDispatcher::clearSentObject( $id, $newRow['publication'], $newRow['state'], $curState );
		}

		if ( $isShadowObject && self::useContentSourceVersion( $meta->BasicMetaData->ContentSource )) {
			if ( isset( $meta->WorkflowMetaData->Version )) {
				require_once BASEDIR . '/server/dbclasses/DBVersion.class.php';
				$versionInfo = array();
				if ( DBVersion::splitMajorMinorVersion( $meta->WorkflowMetaData->Version, $versionInfo )) {
					$newRow['majorversion'] = $versionInfo['majorversion'];
					$newRow['minorversion'] = $versionInfo['minorversion'];
				}
			}
		}

		// Save properties to DB:
		$sth = DBObject::updateObject( $id, null, $newRow, '' );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// Delete object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$pubName = DBPublication::getPublicationName($curPub);
		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			require_once BASEDIR . '/server/bizclasses/BizLinkFiles.class.php';
			BizLinkFiles::deleteLinkFiles( $curPub, $pubName, $id, $currRow['name'], $oldtargets );
		}

		// Validate targets and store them at DB for this object
		// BZ#9827 But only if $targets !== null (!!!)
		if ($targets !== null) {
			BizTarget::saveTargets( $user, $id, $targets, $meta );

			// Validate meta data and targets (including validation done by Server Plug-ins)
			self::validateMetaDataAndTargets( $user, $meta, $targets, null, false );
		}

		$targets = BizTarget::getTargets($user, $id);
		// Targets in the database are updated (if needed). Get the targets from
		// the database and pass it to subsequent methods to determine deadlines
		// and editions.

		$issueid = BizTarget::getDefaultIssueId($currRow['publication'],$targets);
		$newRow['issue'] = $issueid;

		// ==== Handle deadline
		$newDeadline = isset( $newRow['deadline'] ) ? $newRow['deadline'] : null;
		$pubId = $newRow['publication'];
		self::handleDeadline( $id, $pubId, $targets, $curType, $curState, $curSection, $state, $categoryId, $newDeadline );

		//self::saveMetaDataExtended( $id, $newRow, ($curState != $state || $curSection != $meta->BasicMetaData->Category->Id ), $issueIdsDL );

		// BZ#10308 Copy task objects to dossier
		if ($meta->BasicMetaData->Type == 'Task'){
			require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
			BizRelation::copyTaskRelationsToDossiers($id, $meta->WorkflowMetaData->State->Id, $user);
		}

		// Do notifications:
		$issueIds2 = $issueNames = $editionIds = $editionNames = '';
		self::listIssuesEditions( $targets, $issueIds2, $issueNames, $editionIds, $editionNames );

		// Use old values for those that are not set
		foreach (array_keys($currRow) as $k) {
			if (!isset($newRow[$k])) $newRow[$k] = $currRow[$k];
		}
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( $user, 'SetObjectProperties', $id, $newRow['publication'], null, $newRow['section'],
			$newRow['state'], '', '', '', $newRow['type'], $newRow['routeto'], '', $newRow['version'] );

		// Retrieve fresh object from DB to make sure we return correct data (instead of mutated client data!)
		// Relations are needed because otherwise relational targets get lost during re-indexing (BZ#18050)
		$modifiedobj = self::getObject( $id, $user, false, null, array('Targets','Relations'), // no lock, no rendition
			null, true, null, null, // $haveVersion, $checkRights, $areas, $editionId
			false ); // skip calling getShadowObject to avoid getting old data!

		// Update contained Objects if needed.
		self::updateContainedObjects( $modifiedobj );

		// Update relational targets
		self::updateObjectRelationTargets( $user, $modifiedobj );

		// Add to search index:
		require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
		BizSearch::indexObjects( array( $modifiedobj ), true/*$suppressExceptions*/, array('Workflow')/*$areas*/, true );

		// For shadow objects we now pass control to Content Source
		// as it can modify $object
		if( trim($currRow['contentsource']) ) {
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			BizContentSource::setShadowObjectProperties( trim($currRow['contentsource']), trim($currRow['documentid']), $modifiedobj );
		}

		require_once BASEDIR.'/server/smartevent.php';
		new smartevent_setobjectpropertiesEx( BizSession::getTicket(), BizUser::resolveFullUserName($user), $modifiedobj, BizUser::resolveFullUserName($currRow['routeto']));

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createObjectEvent( $modifiedobj->MetaData->BasicMetaData->ID, 'update' );

		BizEmail::sendNotification('set objectprops', $modifiedobj, $newRow['types'], $currRow['routeto']);

		if( defined('MTP_SERVER_DEF_ID') && MTP_SERVER_DEF_ID != '' ) {
			require_once BASEDIR.'/server/MadeToPrintDispatcher.class.php';
			MadeToPrintDispatcher::doPrint( $id, BizSession::getTicket() );
		}

		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			// Create object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
			BizLinkFiles::createLinkFilesObj( $modifiedobj, $newRow['storename'] );
		}

		// return info
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectPropertiesResponse.class.php';
		return new WflSetObjectPropertiesResponse( $modifiedobj->MetaData, $modifiedobj->Targets );
	}

	/**
	 * Handles the deadline of an object.
	 *
	 * Function either takes the deadline entered by the user or
	 * recalculate the deadline (relative deadline) when user changes the category or status.
	 *
	 * When user changes the category / status, and at the same time also entered a deadline in the dialog,
	 * the function will recalculate the deadline (relative deadline) and ignores the deadline entered by the
	 * user.
	 *
	 * When user only entered the deadline, function checks if the deadline entered is later than the one set
	 * at the Issue level, function throws error when the the date set is later than the one set in Issue level.
	 *
	 * @param int $id Id of the object where the deadline will be calculated and handled.
	 * @param int $pubId Publication id.
	 * @param array $targets The targets of the object of which the issue will be retrieved to get its deadline setting.
	 * @param string $objectType Object's type.
	 * @param string $oriState Original object's status.  This is to check if the user has changed the status.
	 * @param string $oriSection Original category. This is to check if the user has changed the category.
	 * @param string $state The current status of the object.
	 * @param int $categoryId The current category of the object.
	 * @param null|string $newDeadline User typed deadline taken from workflow dialog. Null when deadline not shown at dialog.
	 */
	public static function handleDeadline( $id, $pubId, $targets, $objectType, $oriState, $oriSection, $state, $categoryId, $newDeadline )
	{
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizDeadlines.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		// First look for object-target issues
		$issueIdsDL = BizTarget::getIssueIds( $targets ); // Object-target issues
		// Image/Article without object-target issue can inherit issues from relational-targets. BZ#21218
		if (!$issueIdsDL && ( $objectType == 'Article' || $objectType == 'Image' || $objectType == 'Spreadsheet' )) {
			$issueIdsDL = BizTarget::getRelationalTargetIssuesForChildObject( $id );
		}

		// If state or category are changed the deadline is recalculated.
		$reCalcDeadline = ( $oriState != $state || $oriSection != $categoryId );
		$deadlineHard = '';

		// A deadline for an object that is not assigned to any issue has no meaning, so we ignore.
		// If deadline is set and object has issues, check if the set deadline is not beyond earliest possible deadline.
		if( !$reCalcDeadline && $issueIdsDL && $newDeadline ) {
			$deadlineHard = $newDeadline;
			BizDeadlines::checkDeadline( $issueIdsDL, $categoryId, $newDeadline );
		}

		// In case state/category are changed a deadline set by hand is ignored
		// (always recalculate).
		// This behavior is different from the saveObject() where a deadline set
		// by hand always has primacy on status/category changes.
		if ( $reCalcDeadline || empty( $deadlineHard ) ) {
			// Determine if it is normal brand or overruleIssue.
			$overruleIssueId = 0;
			if( count( $issueIdsDL ) == 1 ) { // When there are more than 1 issue targeted to an Object, it's definitely not an overruleIssue, so don't need to check.
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				$overruleIssueId = DBIssue::isOverruleIssue( $issueIdsDL[0] ) ? $issueIdsDL[0] : 0;
			}

			$deadlines = null;
			if( BizPublication::isCalculateDeadlinesEnabled( $pubId, $overruleIssueId ) ) {
				$deadlines = DBObject::objectSetDeadline( $id, $issueIdsDL, $categoryId, $state );
				if ( BizDeadlines::canPassDeadlineToChild( $objectType ) ) {
					// Recalculate the deadlines of children without own object-target issue.
					// This recalculation is limited to an issue change of the parent.
					// New issue of the parent results in new relational-target issue and so
					// a new deadline. If the category of the parent changes this has no effect
					// as the children do not inherit this change.
					BizDeadlines::recalcDeadlinesIssuelessChilds($id);
				}
			}
		} else {
			$deadlines = DBObject::setObjectDeadline( $id, $deadlineHard );
			if ( BizDeadlines::canPassDeadlineToChild( $objectType ) ) {
				// Set the deadlines of children without own object-target issue.
				BizDeadlines::setDeadlinesIssuelessChilds( $id, $deadlineHard );
			}
		}

		if( $deadlines ) {
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_deadlinechanged( null, $id, $deadlines['Deadline'], $deadlines['DeadlineSoft'] );
		}
	}

	/**
	 * Retrieves the essential object properties to serve the MultisetProperties feature.
	 * This is the minimum set of props that is required for the web service (and plugins)
	 * to recognize the objects and apply biz logics before it comes to the real operation
	 * of updating (a few) object properties for (many) multiple objects.
	 *
	 * @since 9.2.0
	 * @param integer[] $objectIds List of object ids.
	 * @return MetaData[] List of properties indexed by object id.
	 */
	public static function resolveInvokedObjectsForMultiSetProps( array $objectIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		$alienIds = BizContentSource::filterAlienIdsFromObjectIds( $objectIds );
		$workflowObjIds = array_diff( $objectIds, $alienIds ); // Get only the normal workflow objects.
		return $workflowObjIds ? DBObject::getMultipleObjectsProperties( $workflowObjIds ) : array();
	}

	/**
	 * Checks if all the Objects have the same publication id and type.
	 *
	 * @since 9.2.0
	 * @param array $objectIds An array of object ids.
	 * @return bool Whether or not all objects have the same publication id and type.
	 */
	public static function isSameObjectTypeAndPublication( $objectIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		return DBObject::isSameObjectTypeAndPublication( $objectIds );
	}

	/**
	 * Updates a list of given object properties for multiple objects at once.
	 *
	 * Note that it might happen that not all requested objects ($objectIds) are found
	 * in the DB. Then, those are -not- invoked (not present in $invokedObjects).
	 *
	 * In case the SendToNext is requested an array is returned with both the properties and the changed states
	 * per Object.
	 *
	 * @param integer[] $requestedObjectIds List of object ids.
	 * @param MetaData[] $invokedObjects List of object properties indexed by object id. Used to update objects.
	 * @param MetaDataValue[] $metaDataValues List of properties to update the given objects ($invokedObjects) with.
	 * @param int $publicationId The PublicationId for the objects to be changed.
	 * @param string $objectType The type of the objects being changed.
	 * @param int $issueId The Id of the overrule issue or 0 if no overrule issue is used.
	 * @param bool $sendToNext Whether or not the service was called with the intention to move objects to the next status.
	 * @return array|MetaDataValue[] An array of changed MetaDataValues, or compound array containing routing info and metadata.
	 * @throws BizException
	 */
	public static function multiSetObjectProperties( array $requestedObjectIds, array $invokedObjects, array $metaDataValues, $publicationId, $objectType, $issueId, $sendToNext )
	{
		require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';

		// Validate the requested Objects, we only support Objects that can be found in the database. Alien Objects will
		// not be present in the database and will therefore be reported. We cannot handle Alien Objects for multi-
		// setObjectProperties.
		$invokedObjectIds = array_keys( $invokedObjects );
		$unknownObjectIds = array_diff( $requestedObjectIds, $invokedObjectIds );
		if ( $unknownObjectIds ) foreach ($unknownObjectIds as $unknownObjectId ) {
			try {
				throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
					BizResources::localize( 'ERR_NOTFOUND' ) . PHP_EOL . 'id=' .$unknownObjectId );
			} catch( BizException $e ) {
				self::reportError( 'Object', $unknownObjectId, 'DoesNotExist', $e );
			}
		}

		/* Lock the Invoked Objects:
			Only attempt to lock those objects that are found in the database (see $invokedObjects ).
			If an object lock cannot be acquired remove the Objects from the list of Objects to change.
			In case the object cannot be locked we report a standard error which will be placed in the Error reports.
		*/
		$user = BizSession::getShortUserName();
		$lockedObjectIds = DBObjectLock::lockObjects( $invokedObjectIds, $user );
		$nonLockedObjectIds = array_diff( $invokedObjectIds, $lockedObjectIds );
		if( $nonLockedObjectIds ) {
			foreach( $nonLockedObjectIds as $nonLockedObjectId ) {
				try {
					$name = isset($invokedObjects[$nonLockedObjectId]->BasicMetaData->Name) ? $invokedObjects[$nonLockedObjectId]->BasicMetaData->Name : '';
					throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client', BizResources::localize( 'ERR_LOCKED' )
						. PHP_EOL . $name . ' (id: '.$nonLockedObjectId.')' );
				} catch( BizException $e ) {
					self::reportError( 'Object', $nonLockedObjectId, null, $e );
				}
				// Object that cannot be locked, the properties will not be updated.
				unset( $invokedObjects[$nonLockedObjectId] );
			}
		}

		// Allow plugins to respond to the requested changed MetaData values prior to flattening the request Metadata
		// values.
		if ( $invokedObjects ) foreach( $invokedObjects as $objectId => $invokedObject ) {
			try {
				self::validateMetaDataInMultiMode( $user, $invokedObject, $metaDataValues );
			} catch( BizException $e ) {
				// Validation failed, report error and remove Object.
				self::reportError( 'Object', $objectId, null, $e );
				unset( $invokedObjects[$objectId] );
			}
		}

		$routeToMetaDataValueIndex = null;
		$stateIdMetaDataValueIndex = null;

		try {
			// Flatten the MetaDataValue list and resolve the type, prevalidation is already done by the service and possibly
			// by the Plugins. We want to create a list of standard properties and custom properties.
			$objectProperties = array( 'standard' => array(), 'custom' => array() );
			foreach( $metaDataValues as $index => $metaDataValue ) {
				if( $metaDataValue->PropertyValues ) {
					// Normalize the property values.
					$key = 'standard';
					$propValues = array();
					foreach( $metaDataValue->PropertyValues as $propValue ) {
						$propValues[] = $propValue->Value;
					}

					// Determine the key for the RouteTo Metadata value if present.
					if ( $metaDataValue->Property == 'RouteTo') {
						$routeToMetaDataValueIndex = $index;
					}

					if ( $metaDataValue->Property == 'StateId') {
						$stateIdMetaDataValueIndex = $index;
					}

					// Determine if we are handling a custom property or a standard property.
					if( BizProperty::isCustomPropertyName( $metaDataValue->Property ) ) {
						$key= 'custom';
						$propType = BizProperty::getCustomPropertyType( $metaDataValue->Property, $publicationId, $objectType );
					} else {
						$propType = BizProperty::getStandardPropertyType( $metaDataValue->Property );
					}
					$val = ( $propType == 'multilist' || $propType == 'multistring')
						? implode( BizProperty::MULTIVALUE_SEPARATOR, $propValues )
						: $propValues[0];
					$objectProperties[$key][$metaDataValue->Property] = $val;
				}
			}

			// Determine the new Category for the Objects, rights checking depends on this.
			$newCategoryId = ( isset( $objectProperties['standard']['CategoryId'] ) && !$sendToNext )
				? $objectProperties['standard']['CategoryId']
				: null;

			// Determine the new State for the Objects, automatic routing and SendToNext depend on this.
			$newStateId = ( isset( $objectProperties['standard']['StateId'] ) )
				? $objectProperties['standard']['StateId']
				: null;

			$personalStatus = ( $newStateId == -1 );

			// Retrieve the statuses that are defined for this publication / overrule issue.
			require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
			$statuses = BizAdmStatus::getStatuses( $publicationId, $issueId, $objectType );

			// Initialize variables to hold the allowed Statuses and Categories for MultiSetObjectProperties.
			$categories = array();
			global $globAuth;
			if ( !is_null( $newStateId ) || !is_null( $newCategoryId ) || $sendToNext ) {
				require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';

				// Authorization settings, only fetch if not yet available.
				if( !isset($globAuth) ) {
					require_once BASEDIR.'/server/authorizationmodule.php';
					$globAuth = new authorizationmodule( );
				}

				// Retrieve available Categories in case a change in Category is requested for the Objects.
				if ( !is_null( $newCategoryId ) ) {
					$cats = BizPublication::getSections($user, $publicationId, $issueId, 'flat', true);
					if ($cats) foreach ($cats as $cat) {
						$categories[$cat->Id] = $cat;
					}
				}
			}

			$categoryIds = array_keys( $categories );
			$groupedObjects = array( ); // In case we are not doing a SendToNext, group all invoked Objects.
			// If we attempt to send Objects to the next status, the StateId as requested is empty and becomes irrelevant
			// to the rest of the process. We do however need to pregroup the Objects to loop through them.
			// Pregroup the invokedObjects into multiple sets when SendToNext is requested. We will also want to set the
			// new RouteTo based on the StateId. On a normal request, the RouteTo change depends on the MetadataValues,
			// If the client has sent along a RouteTo we need to use that instead of AutoRouting.
			if ( isset( $invokedObjects ) ) {
				$routes = array();
				$hasPersonalStateObject = false;
				$hasNormalStateObject = false;
				foreach ( $invokedObjects as $invokedObject ) {
					if ( $invokedObject->WorkflowMetaData->State->Id == -1 ) { $hasPersonalStateObject = true; }
					if ( $invokedObject->WorkflowMetaData->State->Id != -1 ) { $hasNormalStateObject = true; }
				}

				foreach ( $invokedObjects as $objectId => $invokedObject ) {
					if ( $personalStatus || $invokedObject->WorkflowMetaData->State->Id == -1 ) {
						// We cannot send an Object in the personal status to the next status, or from a state to the
						// personal state.
						if ($sendToNext) {
							unset ( $invokedObjects[$objectId] );
							continue;
						}

						// If we have a mixed case of personal / non-personal stated objects we cannot route them
						// automatically to the personal status user, therefore we need to throw an exception

						if ( $hasNormalStateObject && $hasPersonalStateObject && !isset( $stateIdMetaDataValueIndex )
							&& (!isset( $routeToMetaDataValueIndex ) ||
								(isset( $routeToMetaDataValueIndex )
									&& $metaDataValues[$routeToMetaDataValueIndex]->PropertyValues[0]->Value != $user ))) {
							throw new BizException( 'ERR_MIXED_STATUS_WITH_PERSONAL_STATUS', 'Client', '' );
						}

						// If current state is personal,and only the routeto is changed, do not allow a change of route
						// And silently reroute to the person currently logged in.
						// If current state is personal, new state is personal and routeto is changed, change it back to
						// the default user.
						// If the user requests a routeto change, but not a change in status, force the routeto to the
						// currently logged in user.
						// If the user moves the asset to personal status, allow this, but also change the routeto to
						// the currently logged in user.
						// The user is allowed to move out of personal status to another state, and then simultaneously
						// change the routeto to something other than the currently logged in user.
						if ( ( $invokedObject->WorkflowMetaData->State->Id == -1 && isset( $routeToMetaDataValueIndex )
								&& isset( $stateIdMetaDataValueIndex ) && $objectProperties['standard']['StateId'] == -1 )
							|| ( $invokedObject->WorkflowMetaData->State->Id == -1 && isset( $routeToMetaDataValueIndex )
								&& !isset( $stateIdMetaDataValueIndex ) )
							|| ( $invokedObject->WorkflowMetaData->State->Id != -1 && isset( $routeToMetaDataValueIndex )
								&& isset( $stateIdMetaDataValueIndex ) && $objectProperties['standard']['StateId'] == -1 )
						) {

							// Throw an exception if we have mixed items, in which case it is not allowed to autoroute
							// the user to the personal status user.
							$metaDataValues[$routeToMetaDataValueIndex]->PropertyValues[0]->Value = $user;
							$objectProperties['standard']['RouteTo'] = $user;
						}
					}

					if ( $sendToNext ) {
						// First determine the new State for the Object, if next, determine next.
						$state = $statuses[$invokedObject->WorkflowMetaData->State->Id];
						$newStateId = ( $state->NextStatusId ) ? $state->NextStatusId : null;

						if ( is_null( $newStateId ) ) {
							// If there is no state change, then we do not need to include this Object in the set properties so
							// remove it from the invoked Objects.
							unset ( $invokedObjects[$objectId] );
							continue;
						}
					}

					require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
					$categoryId = (!is_null( $newCategoryId ) ) ? $newCategoryId : $invokedObject->BasicMetaData->Category->Id;

					// Determine the new route for this item, if not previously known.
					if ( !isset( $routes[$newStateId])) { $routes[$newStateId] = array(); }
					if ( !isset ($routes[$newStateId][$categoryId]) ) {
						$routes[$newStateId][$categoryId] = BizWorkflow::getDefaultRoutingUsername( $publicationId, $issueId,
							$categoryId, $newStateId );
					}

					// Set new properties and group the remaining invoked Objects.
					if( !is_null($routeToMetaDataValueIndex) ) {
						// If RouteTo exists in MetaDataValue, then add  new / old RouteTo value on the fly.
						$invokedObject->NewRouteTo = isset($objectProperties['standard']['RouteTo'])
							? $objectProperties['standard']['RouteTo']
							: null; // When no changes, don't set anything.
					} else {
						// If RouteTo not exists in MetaDataValue, then get new RouteTo value[from the configured Auto Routing],
						// else take the old RouteTo value.
						if( !is_null($stateIdMetaDataValueIndex) ) {
							$invokedObject->NewRouteTo = !empty( $routes[$newStateId][$categoryId]) // When empty, no auto routing configured
								? $routes[$newStateId][$categoryId]
								: null; // When no changes (taking the old RouteTo value), don't set anything.
						} else {
							$invokedObject->NewRouteTo = $invokedObject->WorkflowMetaData->RouteTo;
						}
					}

					// On a Send To next, we overrule whatever else was sent along for the route and take the RouteTo
					// That is defined for the State and Category.
					if ( $sendToNext ) {
						$invokedObject->NewRouteTo = $routes[$newStateId][$categoryId];
					}

					$invokedObject->NewStateId = $newStateId; // Add the new StatusId on the fly.

					// Group the invokedObject with others of its ilk.
					$groupKey = $newStateId . '_' . $categoryId;
					if (!isset( $groupedObjects[$groupKey]) ) { $groupedObjects[$groupKey] = array(); }
					$groupedObjects[$groupKey][$objectId] = $invokedObject;
				}
			}

			// Go through the grouped Objects and call the updates for them.
			$routingMetaDatas = array();
			if ( $groupedObjects ) foreach ($groupedObjects as $invokedObjectGroup ) {
				$shadowObjectIdsPerCS = array(); // CS - Content Source.
				$targets = array();
				$madeToPrintObjectIds = array();

				foreach( $invokedObjectGroup as $invokedObject ) {
					$objectId = $invokedObject->BasicMetaData->ID;
					$newStateId = ( $sendToNext ) ? $invokedObject->NewStateId : $newStateId;
					if( !self::checkAccessRightsOnStateAndCategory( $invokedObjectGroup, $objectId, $newStateId, $statuses,
						$globAuth, $user, $newCategoryId, $categoryIds, $publicationId, $issueId, $objectType ) ) {
						continue;
					}

					// Checks if it is a shadow object, and add it to the shadow objects per ContentSource.
					if( $invokedObject->BasicMetaData->ContentSource && $invokedObject->BasicMetaData->DocumentID ) {
						$shadowObjectIdsPerCS[$invokedObject->BasicMetaData->ContentSource][] = $objectId;
					}

					// Targets are reused in serveral parts, only get them once.
					$targets[$objectId] = BizTarget::getTargets( $user, $objectId );

					// Dispatch the Objects to the Axiao MadeToPrint dispatcher for printing if the status matches one that
					// triggers the service. This only needs to be done if the Object is of type Article, Layout or Image.
					if( defined('MTP_SERVER_DEF_ID') && MTP_SERVER_DEF_ID != ''
						&& ( $objectType == 'Article' || $objectType == 'Layout' || $objectType == 'Image' )
						&& !is_null( $newStateId ) // A new state is requested for the Objects.
					) {
						// Each object should be send once and only once to MTP.
						$madeToPrintObjectIds[ $invokedObject->BasicMetaData->ID ] = intval( $invokedObject->BasicMetaData->ID );
					}

					// Create routing metadatas.
					if ( $sendToNext ) {
						$state = new State();
						$state->Id = $invokedObject->NewStateId;
						$state->Type = $statuses[$invokedObject->NewStateId]->Type;
						$state->Produce = $statuses[$invokedObject->NewStateId]->Produce;
						$state->Name = $statuses[$invokedObject->NewStateId]->Name;
						$color = $statuses[$invokedObject->NewStateId]->Color;
						BizAdmStatus::restructureMetaDataStatusColor( $invokedObject->NewStateId, $color);
						$state->Color = $color;
						$state->DefaultRouteTo = $invokedObject->NewRouteTo;
						$routingMetadata = new RoutingMetaData();
						$routingMetadata->ID = $objectId;
						$routingMetadata->RouteTo = $invokedObject->NewRouteTo;
						$routingMetadata->State = $state;
						$routingMetaDatas[] = $routingMetadata;
					}
				}

				// Nothing to do if we have no objects after the access right checking
				if( empty( $invokedObjectGroup ) ) {
					continue;
				}

				// Get the grouped routeTo if available from the first Object.
				// Get the grouped Status if available from the first Object.
				$currentObject = current( $invokedObjectGroup );
				$stateIdForGroup = ( $sendToNext ) ? $currentObject->NewStateId : $newStateId;
				$routeToForGroup = isset( $currentObject->NewRouteTo ) ? $currentObject->NewRouteTo : null;

				// Update $objectProperties and the MetaDataValues for the database updates, contentsources and search index.
				if ( $sendToNext || ( !$sendToNext && isset( $objectProperties['standard']['StateId'] ) ) ) {
					$objectProperties['standard']['StateId'] = $stateIdForGroup;
				}
				if( !is_null( $routeToForGroup ) ) { // Only update when there's changes in the RouteTo.
					$objectProperties['standard']['RouteTo'] = $routeToForGroup;
				}

				// If a RouteTo change was not requested, add it to the MetaDataValues as it may have been changed.
				if ( $sendToNext && is_null( $routeToMetaDataValueIndex ) ) {
					$metadataVal = new MetaDataValue();
					$metadataVal->Property = 'RouteTo';
					$propValue = new PropertyValue();
					$propValue->Value = $routeToForGroup;
					$metadataVal->PropertyValues = array( $propValue );
					$metaDataValues[] = $metadataVal;
				}

				// Convert the array of property names (camel case) into DB rows (lower case).
				require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
				$objRow = array();
				if ( count( $objectProperties['standard'] ) > 0 ){
					$objRow = BizProperty::objPropToRowValues( $objectProperties['standard'] );
				}

				if ( count( $objectProperties['custom'] ) > 0 ) {
					$objRow = array_merge( $objRow, $objectProperties['custom']);
				}

				// Depending on configuration, setting props could lead to new version:
				require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
				BizVersion::multiCreateVersionIfNeeded( $invokedObjectGroup, $objRow, $statuses );

				// Update the properties for all objects in the database at once.
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$objectIds = array_keys( $invokedObjectGroup );
				DBObject::updateObject( $objectIds, null, $objRow, null );

				// Dispatch all previously collected objects to the Axiao MadeToPrint dispatcher for printing.
				if( $madeToPrintObjectIds ) {
					require_once BASEDIR.'/server/MadeToPrintDispatcher.class.php';
					$ticket = BizSession::getTicket();
					foreach( $madeToPrintObjectIds as $madeToPrintObjectId ) {
						MadeToPrintDispatcher::doPrint( $madeToPrintObjectId, $ticket );
					}
				}

				// Update the modified properties in ContentSource.
				require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
				if( $shadowObjectIdsPerCS ) {
					// First check if the connector(s) support multiset feature.
					$contentSourceSupportsMultiSet = array();
					$contentSourceDoesNotSupportsMultiSet = array();

					foreach( $shadowObjectIdsPerCS as $contentSource => $shadowObjectIds ) {
						if( BizContentSource::doesContentSourceSupportsMultiSet( $contentSource ) ) {
							$contentSourceSupportsMultiSet[$contentSource] = $shadowObjectIds;
						} else {
							$contentSourceDoesNotSupportsMultiSet[$contentSource] = $shadowObjectIds;
						}
					}

					if( $contentSourceSupportsMultiSet ) {
						BizContentSource::multiSetShadowObjectProperties( $contentSourceSupportsMultiSet, $metaDataValues );
					}
					if( $contentSourceDoesNotSupportsMultiSet ) { // Fallback, which is not advisable.
						// Need to get and update object by object.
						require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
						$user = BizSession::getShortUserName();
						foreach( $contentSourceDoesNotSupportsMultiSet as $contentSource => $shadowObjIds ) {
							if( $shadowObjIds ) {
								foreach( $shadowObjIds as $shadowId ) {
									$modifiedObject = self::getObject( $shadowId, $user,
										false, null,  // no lock, no rendition
										null, // requestInfo (No Targets and Relations for multi-set)
										null, true, null, null, // $haveVersion, $checkRights, $areas, $editionId
										false ); // skip calling getShadowObject to avoid getting old data!

									BizContentSource::setShadowObjectProperties( $contentSource, $modifiedObject->MetaData->BasicMetaData->DocumentID, $modifiedObject );
								}
							}
						}
					}
				}

				// Log the information in the database.
				require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
				$sections = array();
				$states = array();
				$versions = array();
				$routeTos = array();
				foreach( $invokedObjects as $invokedObject ) {
					$sections[] = is_null( $newCategoryId ) ? $invokedObject->BasicMetaData->Category->Id : $newCategoryId;
					$states[] = is_null( $newStateId ) ? $invokedObject->WorkflowMetaData->State->Id : $newStateId;
					$versions[] = $invokedObject->WorkflowMetaData->Version; // invokedObject is updated in BizVersion when needed
					$routeTos[] = is_null( $routeToMetaDataValueIndex ) ? $invokedObject->WorkflowMetaData->RouteTo : $objectProperties['standard']['RouteTo'];
				}
				DBlog::logMultiService( $user, 'MultiSetObjectProperties', $objectIds, $objectType, $publicationId, $sections, $states, $versions, $routeTos );

				require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
				foreach ( $objectIds as $id ) {
					$originalStateId = $invokedObjects[$id]->WorkflowMetaData->State->Id;
					$originalCategoryId = $invokedObjects[$id]->BasicMetaData->Category->Id;

					// Handle Deadlines
					// When state, category is not changed, take back the original value.
					$latestStateIdForDeadline = $newStateId ? $newStateId : $originalStateId;
					$latestCatIdForDeadline = $newCategoryId ? $newCategoryId : $originalCategoryId;
					$deadline = ( isset( $objectProperties['standard']['Deadline'] ) ) ?
						$objectProperties['standard']['Deadline'] : null;
					self::handleDeadline( $id, $publicationId, $targets[$id], $objectType, $originalStateId,
						$originalCategoryId, $latestStateIdForDeadline, $latestCatIdForDeadline, $deadline );

					// Copy task objects to dossier ( For more info, refer to BZ#10308 ).
					if( $objectType == 'Task' ) {
						$latestStateIdForTaskRelations = $newStateId ? $newStateId : $originalStateId;
						BizRelation::copyTaskRelationsToDossiers( $id, $latestStateIdForTaskRelations, $user );
					}
				}

				// Update search index:
				require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
				if ( $sendToNext ) {
					$metaDataValues[$stateIdMetaDataValueIndex]->PropertyValues[0]->Value = $objectProperties['standard']['StateId'];
				}
				BizSearch::updateObjectProperties( $objectIds, $metaDataValues, true /*$suppressExceptions*/, true );

				// Send notifications if needed.
				BizEmail::sendNotifications( $invokedObjects, $objectProperties, $statuses, $categories, $newCategoryId, $newStateId );

				// Update object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
				if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
					require_once BASEDIR . '/server/bizclasses/BizLinkFiles.class.php';
					if (isset($objectIds)) {
						foreach ($objectIds as $objectId) {
							$currObject = $invokedObjects[$objectId];
							$currPubId = $currObject->BasicMetaData->Publication->Id;
							$currPubName = $currObject->BasicMetaData->Publication->Name;

							$objectName = $currObject->BasicMetaData->Name;
							$objectType = $currObject->BasicMetaData->Type;
							$objectVersion = $currObject->WorkflowMetaData->Version;
							$objectFormat = $currObject->ContentMetaData->Format;
							$objectStoreName = $currObject->BasicMetaData->StoreName;

							BizLinkFiles::deleteLinkFiles( $currPubId, $currPubName, $objectId,
								$currObject->BasicMetaData->Name, $targets[$objectId] );

							BizLinkFiles::createLinkFiles( $currPubId, $currPubName, $objectId, $objectName, $objectType,
								$objectVersion, $objectFormat, $objectStoreName, $targets[$objectId] );
						}
					}
				}

				// N-cast the changed objects.
				require_once BASEDIR.'/server/smartevent.php';
				new smartevent_setPropertiesForMultipleObjects( $objectIds, array_merge($objectProperties['standard'],
					$objectProperties['custom'] ) );

				// Notify event plugins
				require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
				BizEnterpriseEvent::createMultiObjectEvent( $objectIds, $metaDataValues );
				if ( $sendToNext ) {
					$metaDataValues[$stateIdMetaDataValueIndex]->PropertyValues[0]->Value = ''; // Response expects an empty StateId.
				}
			}

			// Release lock
			if( $lockedObjectIds ) { // Only release the objects where the lock is obtained by this function.
				DBObjectLock::unlockObjects( $lockedObjectIds, $user );
			}
		} catch ( BizException $e ) {
			if( $lockedObjectIds ) { // Only release the objects where the lock is obtained by this function.
				DBObjectLock::unlockObjects( $lockedObjectIds, $user );
			}
			throw $e; // This should only happen when there's fatal error, thus bail out all the way after unlocking the objects.
		}

		$response = $metaDataValues;
		if ( $sendToNext ) {
			$sendToResponse = array();
			$sendToResponse['MetadataValues'] = $metaDataValues;
			$sendToResponse['RoutingMetaDatas'] = $routingMetaDatas;
			$response = $sendToResponse;
		}
		return $response;
	}

	/**
	 * Reports an error to the service reports.
	 *
	 * Reports an error and optional exception to the service error reporting.
	 *
	 * @param string $type The type of report.
	 * @param int $id The ID for the object that caused the error.
	 * @param null|string $role The optional role for the report.
	 * @param null|BizException $exception The optional exception to be reported.
	 */
	private static function reportError( $type, $id, $role=null, $exception=null )
	{
		// Create a new report for the error.
		$report = BizErrorReport::startReport();
		$report->Type = $type;
		$report->ID = $id;
		$report->Role = $role;
		if ( !is_null( $exception ) ) {
			BizErrorReport::reportException( $exception );
		}
		BizErrorReport::stopReport();
	}

	/**
	 * Checks if the user has rights on the chosen status and category for the selected object.
	 *
	 * If user has no rights to change the status or category of the object passed in, the object will be taken out
	 * from the $invokedObjects list so that properties for those objects will not be updated.
	 *
	 * @param MetaData[] $invokedObjects List of object properties indexed by object id. Object where user has no rights on will be taken out from this list.
	 * @param int $objectId Object to be checked if the user has rights on to change the status and category.
	 * @param int $newStateId The new status id where user has changed to.
	 * @param array $statuses List of statuses(key is the status id and value state object) defined for the publication of the object.
	 * @param authorizationmodule $globAuth To get and check access rights for the acting user.
	 * @param string $user The acting user.
	 * @param int $newCategoryId The category id where user has changed to.
	 * @param array $categories List of categories id defined for the publication of the object.
	 * @param int $publicationId Publication id for the object.
	 * @param int $issueId Issue id (Typically used for overruleissue).
	 * @param string $objectType The object type.
	 * @return bool true on success, or false on failure.
	 */
	private static function checkAccessRightsOnStateAndCategory( &$invokedObjects, $objectId, $newStateId, $statuses,
	                                                             $globAuth, $user, $newCategoryId, $categories,
	                                                             $publicationId, $issueId, $objectType )
	{
		$retVal = true;

		do {
			// Check the rights of the user to make changes to the object status when an update of the status is
			// needed.
			$currentState = $invokedObjects[$objectId]->WorkflowMetaData->State->Id;
			$currentCategory = $invokedObjects[$objectId]->BasicMetaData->Category->Id;
			$contentSource = $invokedObjects[$objectId]->BasicMetaData->ContentSource;
			$documentId = $invokedObjects[$objectId]->BasicMetaData->DocumentID;
			$routeTo = $invokedObjects[$objectId]->WorkflowMetaData->RouteTo;

			if ( !is_null( $newStateId ) ) {
				// Double check that the new status is a valid status for this publication / overrule issue.
				// Personal status(status = -1 ) is excluded as this is not configurable in the admin page.
				if ( $newStateId != -1 && !array_key_exists( $newStateId, $statuses ) ) {
					try {
						throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
							BizResources::localize( 'ERR_INVALIDSTATE', true, array( $newStateId ) ) . PHP_EOL . 'id=' .$newStateId );
					} catch( BizException $e ) {
						self::reportError( 'State', $newStateId, null, $e );
					}

					unset( $invokedObjects[$objectId] );
					$retVal = false;
					break;
				}

				// On a detected state change, check if we may adjust the Object property. We ignore personal status.
				// What to do if the new state has an automatic routing settings on the state?
				if ( $newStateId != $currentState ) {
					// Retrieve the latest rights for the object to check the rights for this object.
					$globAuth->getRights( $user, $publicationId, $issueId, $currentCategory, $objectType );
					$changeStatusForward = $globAuth->checkright( 'F', // 'Change_Status_Forward'
						$publicationId, $issueId, $currentCategory, $objectType, $currentState,
						$objectId, $contentSource, $documentId, $routeTo );
					$changeStatus = $globAuth->checkright( 'C',  // 'Change_Status'
						$publicationId, $issueId, $currentCategory, $objectType, $currentState,
						$objectId, $contentSource, $documentId, $routeTo );

					// If the next status is requested and we cannot move the status forward we need to report an
					// error. Change Status allows any transition while change forward only allows moving forward to
					// the next status. This can be skipped if Personal state is involved.
					if ( $newStateId != -1 && $currentState != -1  ) {
						$currentStateObject = $statuses[$currentState];
						if (  $newStateId == $currentStateObject->NextStatusId) {
							if ( !$changeStatusForward && !$changeStatus ) {
								try {
									throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
										BizResources::localize( 'ERR_AUTHORIZATION' ) . PHP_EOL . 'id=' . $objectId
										. ' (F)' );
								} catch ( BizException $e ) {
									self::reportError( 'Authorization', $objectId, null, $e );
								}

								unset($invokedObjects[$objectId]);
								$retVal = false;
								break;
							}
						}
					} elseif ( !$changeStatus ) {
						try {
							throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
								BizResources::localize( 'ERR_AUTHORIZATION' ) . PHP_EOL . 'id=' .$objectId
								. ' (C)' );
						} catch( BizException $e ) {
							self::reportError( 'Authorization', $objectId, null, $e );
						}

						unset( $invokedObjects[$objectId] );
						$retVal = false;
						break;
					}

					require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
					// Check if we are allowed to change the source Object. We check Write access (W) and open/edit rights (E) and open/edit rights for unplaced (O).
					if( !$globAuth->checkright( 'E',
							$publicationId, $issueId, $currentCategory, $objectType, $currentState,
							$objectId, $contentSource, $documentId, $routeTo )
						&& !$globAuth->checkright( 'W',
							$publicationId, $issueId, $currentCategory, $objectType, $currentState,
							$objectId, $contentSource, $documentId, $routeTo )
						&& ( !$globAuth->checkRight( 'O',
								$publicationId, $issueId, $currentCategory, $objectType, $currentState,
								$objectId, $contentSource, $documentId, $routeTo ) || BizRelation::hasRelationOfType( $objectId, 'Placed', 'parents' ) ) ) {
								try {
									throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
										BizResources::localize( 'ERR_AUTHORIZATION' ).PHP_EOL."id={$objectId} (E)" );
								} catch( BizException $e ) {
									self::reportError( 'Authorization', $objectId, null, $e );
								}

								unset( $invokedObjects[ $objectId ] );
								$retVal = false;
								break;
					}

					// Check if there is write access to the destination Object (W).
					$globAuth->getRights( $user, $publicationId, $issueId, $newCategoryId, $objectType );
					if( !$globAuth->checkright( 'W',
						$publicationId, $issueId, $newCategoryId, $objectType, $newStateId,
						$objectId, $contentSource, $documentId, $routeTo )
					) {
						try {
							throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
								BizResources::localize( 'ERR_AUTHORIZATION' ).PHP_EOL.'id='.$objectId
								.' (W)' );
						} catch( BizException $e ) {
							self::reportError( 'Authorization', $objectId, null, $e );
						}

						unset( $invokedObjects[ $objectId ] );
						$retVal = false;
						break;
					}
				}
			}

			// On a changed category check the rights and the availability of the new category.
			if ( !is_null( $newCategoryId ) ) {
				if ( !in_array( $newCategoryId, $categories) ) {
					try {
						throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
							BizResources::localize( 'ERR_INVALID_PROPERTY' ) . PHP_EOL . 'id=' . $objectId
							. ', category=' .$newCategoryId );
					} catch( BizException $e ) {
						self::reportError( 'Category', $objectId, null, $e );
					}

					unset( $invokedObjects[$objectId] );
					$retVal = false;
					break;
				}

				// Check change Brand / Issue / Category.
				if ( $newCategoryId != $currentCategory ) {
					// Check if user is allowed to change the object's location (ChangePIS)
					$globAuth->getRights($user, $publicationId, $issueId, $currentCategory, $objectType);
					if (!$globAuth->checkright( 'P',
						$publicationId, $issueId, $currentCategory, $objectType, $currentState,
						$objectId, $contentSource, $documentId, $routeTo ) ) {
						try {
							throw new BizException( 'ERR_UNABLE_SETPROPERTIES', 'Client',
								BizResources::localize( 'ERR_AUTHORIZATION' ) . PHP_EOL . 'id=' .$objectId
								. ' (P)' );
						} catch( BizException $e ) {
							self::reportError( 'Authorization', $objectId, null, $e );
						}

						unset( $invokedObjects[$objectId] );
						$retVal = false;
						break;
					}
				}
			}
		} while( false );

		return $retVal;
	}

	/**
	 * Returns a list of object ids and its properties specified in $requestProps.
	 *
	 * The format returned is in an array which has the following construction:
	 * $returnList[ObjId] = array( 'ID' => 99,
	 *                             'Type' => 'Article',
	 *                             'Name' => 'Testing1',
	 *                             'Category' => 'News',
	 *                             'CategoryId' => 19 );
	 *
	 * $returnList[ObjId] = array( 'ID' => 100,
	 *                             'Type' => 'Article',
	 *                             'Name' => 'Testing2',
	 *                             'Category' => 'Sports',
	 *                             'CategoryId' => 76 ); );
	 *
	 * @param string $user Session user.
	 * @param array $objIds List of object Ids of which their properties will be retrieved.
	 * @param array $requestProps List of properties of which the values will be retrieved. (ID,Type and Name are always returned).
	 * @return array Refer to function header.
	 */
	public static function getMultipleObjectsPropertiesByObjectIds( $user, $objIds, $requestProps )
	{
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

		// Treat special cases; When prop is configured, we actually want the id instead.
		// ID,PublicationId,IssueId,SectionId,PubChannelIds,EditionIds are not applicable for multi-set properties.
		if( in_array( 'Issues', $requestProps )) {
			$requestProps[] = 'IssueIds'; // Needed for Overrule issues.
		}
		if( in_array( 'State', $requestProps )) {
			$requestProps[] = 'StateId';
		}
		if( in_array( 'Category', $requestProps )) {
			$requestProps[] = 'CategoryId';
		}

		$ticket = BizSession::getTicket();
		$params = array( );
		if( $objIds ) foreach( $objIds as $objectId ) {
			$queryParam = new QueryParam();
			$queryParam->Property  = 'ID';
			$queryParam->Operation = '=';
			$queryParam->Value     = $objectId;
			$params[] = $queryParam;
		}

		// Query.
		$accessRight = 2; // Read right
		$minProps = array( 'ID', 'Type', 'Name' );
		$requestProps = array_unique( array_merge( $minProps, $requestProps ) );
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $ticket;
		$request->Params = $params;
		$request->Hierarchical = false;
		$request->RequestProps = $requestProps;
		$request->Areas = array( 'Workflow' );
		require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
		$queryObjResp = BizQuery::queryObjects2( $request, $user, $accessRight );

		// Determine column indexes to work with
		$indexes = array_combine( array_values( $requestProps ), array_fill( 1, count( $requestProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $queryObjResp->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}

		$objectsProps = array();
		foreach( $queryObjResp->Rows as $row ) {
			$multiObjsProps = array();
			foreach( $requestProps as $requestProp ) {
				$propValue = $row[$indexes[$requestProp]];
				$multiObjsProps[$requestProp] = $propValue;
			}
			$objectId = $row[$indexes['ID']];
			$objectsProps[$objectId] = $multiObjsProps;
		}

		return $objectsProps;
	}

	/**
	 * Copy an existing object to a new object.
	 *
	 * @param string|int $srcid Object id to be copied. When an alien object is passed, the id is a string.
	 * @param MetaData $meta
	 * @param string $user
	 * @param Target[] $targets
	 * @param Page[] $pages
	 * @param Relation[] $relations
	 * @return WflCopyObjectResponse
	 * @throws BizException
	 */
	public static function copyObject( $srcid, $meta, $user, $targets, $pages, $relations=null )
	{
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		require_once BASEDIR."/server/utils/NumberUtils.class.php";
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';

		static $allowCopyForms = false;
		if( ( $meta->BasicMetaData->Type == 'PublishForm' && !$allowCopyForms ) || // Now allowed to copy a Form unless it is called from internal.
			$meta->BasicMetaData->Type == 'PublishFormTemplate' ) { // Not allowed to copy a PublishFormTemplate regardless who is the caller.
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'It is not allowed to Copy a '.$meta->BasicMetaData->Type.'.' );
		}

		// TODO v6.0: Handle targets (expect one, return many)
		// BZ#9827 $targets can now be null, if this is the case:
		// for now: DO NOT CHANGE EXISTING BEHAVIOR, but for future implementation, if:
		// $targets = array -> save these targets accordingly, even if empty
		// $targets = null -> save targets from the object to be copied... !?!? unspecified behavior
		if ($targets == null) {
			$targets = array();
		}

		// Check source access:

		// check for empty source id
		if (!$srcid) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "$srcid" );
		}

		// meta shouldn't have id yet (but CS sends the source id BZ#12304)
		$meta->BasicMetaData->ID = '';

		// Check if we have an alien as source. if so we need to import a copy
		// of the alien which is handled totally different
		// Note that we don't look for a possible shadow here, we always want
		// to get a copy of the original.
		require_once BASEDIR .'/server/bizclasses/BizContentSource.class.php';
		if( BizContentSource::isAlienObject($srcid) ){
			// It's an alien, do we have a shadow? If so, use that instead
			$shadowID = BizContentSource::getShadowObjectID($srcid);
			if( $shadowID ) {
				$srcid = $shadowID;
			} else {
				// An alien without shadow, we treat this as a create:
				$destObject = new Object( $meta,				// meta data
					null, $pages,			// relations, pages
					null, 				// Files array of attachment
					null, null, $targets	// messages, elements, targets
				);
				// Check if all users in the metadata are known within the system. If not, create them and import remaining
				// information when such a user logs in through LDAP when enabled.
				self::getOrCreateResolvedUsers($srcid, $meta);

				$shadowObject = BizContentSource::createShadowObject( $srcid, $destObject );
				$shadowObject = self::createObject( $shadowObject, $user, false /*lock*/, empty($shadowObject->MetaData->BasicMetaData->Name) /*$autonaming*/ );

				require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObjectResponse.class.php';
				return new WflCopyObjectResponse( $shadowObject->MetaData, $targets );
			}
		}

		require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
		//TODO shouldn't we do self::getObject with rendition='none'?
		list( $publishSystem, $templateId ) = self::getPublishSystemAndTemplateId( $srcid );
		$objProps = BizQuery::queryObjectRow($srcid, null, $publishSystem, $templateId );
		if( !$objProps ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', "$srcid" );
		}

		// check authorizations on source object for read
		BizAccess::checkRightsForObjectProps( $user, 'R', BizAccess::THROW_ON_DENIED, $objProps );

		// Validate destination data

		// If type not filled in, put source type info dest, needed for validation
		if( !$meta->BasicMetaData->Type ) {
			$meta->BasicMetaData->Type = $objProps['Type'];
		}

		// If shadow object call content source to copy object BZ#11509
		$shadowObject = null;
		if ( trim($objProps['ContentSource']) ) {
			// create source object, probably self::getObject is better (see comment above) but we don't
			// want to read the object twice
			$srcMeta = self::queryRow2MetaData( $objProps, true, null, null, true );
			$srcObject = new Object( $srcMeta );

			$destObject = new Object( $meta, null, $pages, null, null, null, $targets );
			$shadowObject = BizContentSource::copyShadowObject( trim($objProps['ContentSource']), trim($objProps['DocumentID']), $srcObject, $destObject );
			// copy metadata, targets and pages from content source, it may have been changed
			$meta = $shadowObject->MetaData;
			$targets = $shadowObject->Targets;
			$pages = $shadowObject->Pages;
		}

		// Validate targets count, layouts can be assigned to one issue only
		BizWorkflow::validateMultipleTargets( $meta, $targets );

		// Validate (and correct,fill in) workflow properties of destination
		BizWorkflow::validateWorkflowData( $meta, $targets, $user );

		// Validate meta data
		self::validateMetaDataAndTargets( $user, $meta, $targets, $relations, false );

		// Get flat metadata to save
		$arr = self::getFlatMetaData( $meta, $srcid );

		// @TODO: Call the form and dossier validator.
		// validate Publish Form and Dossier
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		$obj = new Object();
		$obj->Relations = $relations;
		if ( BizPublishForm::isPublishForm( $obj )) {
			BizRelation::validateFormContainedByDossier( null, $targets, $relations );
		}
		if( $arr['type'] == 'Dossier' ) {
			BizRelation::validateDossierContainsForms( null, $targets, $relations );
		}
		if ( $obj->Relations ) {
			// EN-36057 - Loop through all the relations, when it is new parent dossier object relation,
			// validate the dossier object name, if exist, stop the creation of the child and parent dossier object.
			foreach( $obj->Relations as $relation ) {
				if( $relation->Parent == -1 && $relation->Type == 'Contained' ) {
					self::nameValidation( $user, $meta, null, $meta->BasicMetaData->Name, 'Dossier', $targets, null, false );
				}
			}
		}

		// Create new object in DB:

		// Set RouteTo field as destination data RouteTo
		if( isset($objProps['RouteTo']) && $meta->WorkflowMetaData ) {
			$objProps['RouteTo'] = $meta->WorkflowMetaData->RouteTo;
		}

		// Convert objProp to db row:
		$objRow = BizProperty::objPropToRowValues( $objProps );

		//BZ#10709 Also convert custom properties read from db...
		foreach ($objProps as $propName => $propValue) {
			if (DBProperty::isCustomPropertyName($propName)) {
				$lowercasekey = strtolower($propName);
				if (!isset($objRow[$lowercasekey])) {
					$objRow[$lowercasekey] = $propValue;
				}
			}
		}

		// Create array of meta data for destination object:
		if ($arr) foreach (array_keys($arr) as $key)
			$objRow[$key] = $arr[$key];

		// remove system values:
		unset( $objRow['id'] );
		unset( $objRow['created'] );
		unset( $objRow['creator'] );
		unset( $objRow['modified'] );
		unset( $objRow['modifier'] );
		unset( $objRow['lockedby'] );
		unset( $objRow['storename'] );
		unset( $objRow['indexed'] );
		unset( $objRow['version'] );
		unset( $objRow['majorversion'] );
		unset( $objRow['minorversion'] ) ;


		$objformat = $objRow['format'];

		// Check authorizations on destination
		$rights = 'W'; // check 'Write' access (W)
		if( $arr['type'] == 'Dossier' || $arr['type'] == 'DossierTemplate' ) {
			$rights .= 'd'; // check the 'Create Dossier' access (d) (BZ#17051)
		} else if( $arr['type'] == 'Task' ) {
			$rights .= 't'; // check 'Create Task' access (t) (BZ#17119)
		}
		BizAccess::checkRightsForMetaDataAndTargets(
			$user, $rights, BizAccess::THROW_ON_DENIED,
			$meta, $targets ); // BZ#17119

		// If possible (depends on DB) we get id for new object beforehand:
		$dbDriver = DBDriverFactory::gen();
		$id = $dbDriver->newid(DBPREFIX."objects",false);
		if ($id) {
			$storename = StorageFactory::storename($id, $objProps);
		} else {
			$storename = '';
		}

		// v4.2.4 patch for #5700, Save current time be able to send this later as modified time
		$now = date('Y-m-d\TH:i:s');

		// determine new version nr for the new object
		require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$status = BizAdmStatus::getStatusWithId( $objRow['state'] );
		$newVerNr = BizVersion::determineNextVersionNr( $status, $objRow );
		$arr['version'] = $newVerNr;

		// Create object record in DB:
		$sth = DBObject::createObject( $storename, $id, $user, $now, $objRow, $now );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// If we did not get an id from DB beforehand, we get it now and update storename
		// that is derived from object id
		if (!$id) {
			$id = $dbDriver->newid(DBPREFIX."objects",true);
			// now we know id: generate storage name and store it
			$storename = StorageFactory::storename($id, $objRow);
			/*$sth = */DBObject::updateObject( $id, null, array(), '', $storename );
		}
		if (!$id) {
			throw new BizException( 'ERR_DATABASE', 'Server', 'No ID' );
		}
		$meta->BasicMetaData->ID = $id;

		$issueid = BizTarget::getDefaultIssueId($arr['publication'],$targets);
		$arr['issue'] = $issueid;

		$issueids = BizTarget::getIssueIds($targets);

		// Save extended meta data (not in smart_objects table):
		BizTarget::saveTargets( $user, $id, $targets, $meta );

		// ==== Handle deadline
		$issueIdsDL = $issueids;

		require_once BASEDIR.'/server/bizclasses/BizDeadlines.class.php';
		if ( !$issueIdsDL && ( BizDeadlines::canInheritParentDeadline( $arr['type'])) ) { // BZ#21218
			$issueIdsDL = BizTarget::getRelationalTargetIssuesForChildObject( $id );
		}

		// Determine if it is normal brand or overruleIssue.
		$overruleIssueId = 0;
		if( count( $issueIdsDL ) == 1 ) { // When there are more than 1 issue targeted to an Object,
			// it's definitely not an overruleIssue, so don't need to check.
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$overruleIssueId = DBIssue::isOverruleIssue( $issueIdsDL[0] ) ? $issueIdsDL[0] : 0;
		}

		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$pubId = $meta->BasicMetaData->Publication->Id;
		if( BizPublication::isCalculateDeadlinesEnabled( $pubId, $overruleIssueId ) ) {
			DBObject::objectSetDeadline( $id, $issueIdsDL, $arr['section'], $arr['state'] );
		}

		// Validate meta data and targets (including validation done by Server Plug-ins)
		self::validateMetaDataAndTargets( $user, $meta, $targets, $relations, false );

		require_once BASEDIR.'/server/bizclasses/BizObjectLabels.class.php';
		$newLabels = null;
		// If the type of the object is an enabled parent object label type, copy all the labels
		if ( in_array( $arr['type'], BizObjectLabels::getObjectLabelsEnabledParentObjectTypes() ) ) {
			$newLabels = BizObjectLabels::copyObjectLabelsForParentObject( $srcid, $id );
		}

		// Copy files
		$replaceguids = array();
		$verNr = $objProps['Version'];

		// if content source has provided files in shadow object, save them else let Enterprise handle it
		if ( ! is_null($shadowObject) && ! is_null($shadowObject->Files) ){
			self::saveFiles( $storename, $id, $shadowObject->Files, $newVerNr );
		} else {
			$types = unserialize($objProps['Types']);

			$formats = array(
				'application/incopy' => true,
				'application/incopyinx' => true,
				'application/incopyicml' => true,
				'application/incopyicmt' => true,
				'text/wwea' => true );

			foreach (array_keys($types) as $tp) {
				$attachobj = StorageFactory::gen( $objProps['StoreName'], $srcid, $tp, $types[$tp], $verNr );
				if( $tp == 'native' && $types[$tp] == $objformat && isset($formats[$objformat]) ) {
					$succes = $attachobj->copyFile($newVerNr, $id, $storename, null, null, $replaceguids, $types[$tp]);
				}
				else {
					$dummy = null;
					$succes = $attachobj->copyFile($newVerNr, $id, $storename, null, null, $dummy, $types[$tp]);
				}
				if (!$succes) {
					throw new BizException( 'ERR_ATTACHMENT', 'Server', $attachobj->getError() );
				}
			}
		}

		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		if( is_null( $pages ) ) { // no pages given; just copy the pages

			$sth = DBPage::getPages( $srcid, 'Production' );

			$orgEditions = array();
			$lowEdition = '1e10';				// first edition (not being the empty one)

			// get all current pages in prows
			$prows = array();
			while (($prow = $dbDriver->fetch($sth)) ) {
				$thisedition = $prow['edition'];
				$prow['oldedition'] = $thisedition;
				$orgEditions[$thisedition][] = $prow;
				if ($thisedition && $thisedition < $lowEdition) {
					$lowEdition = $thisedition;
				}
				$prows[] = $prow;
			}

			// make list of prowsNew in case of editions
			$hasEditions = false;
			$prowsNew = array();

			if( !empty($targets) ) foreach( $targets as $target ) {
				if( !empty($target->Editions) ) foreach( $target->Editions as $edition ) {
					$hasEditions = true;
					$thisedition = $edition->Id;
					if (isset($orgEditions[$thisedition]) && is_array($orgEditions[$thisedition])) {
						// handle same edition
						foreach ($orgEditions[$thisedition] as $prow) {
							$prowsNew[] = $prow;
						}
						// add generic pages (if thisedition is not generic)
						if ($thisedition && isset($orgEditions[0]) && is_array($orgEditions[0])) foreach ($orgEditions[0] as $prow) {
							$prow['edition'] = $thisedition;
							$prowsNew[] = $prow;
						}
					}
					elseif (isset($orgEditions[$lowEdition]) && is_array($orgEditions[$lowEdition])) {
						// handle lowest edition
						foreach ($orgEditions[$lowEdition] as $prow) {
							$prow['edition'] = $thisedition;
							$prowsNew[] = $prow;
						}
						// add generic pages
						if (isset($orgEditions[0]) && is_array($orgEditions[0])) {
							foreach ($orgEditions[0] as $prow) {
								$prow['edition'] = $thisedition;
								$prowsNew[] = $prow;
							}
						}
					}
				}
				//BZ#6294: pages with edition 0 were not copied... now they are.
				//	foreach ($prows as $pagerow) {
				//		if (empty($pagerow['edition'])) {
				//			$prowsNew[] = $pagerow;
				//		}
				//	}
			}
			if (!$hasEditions) {
				// add lowest edition for all
				if( isset($orgEditions[$lowEdition]) && is_array($orgEditions[$lowEdition])) foreach ($orgEditions[$lowEdition] as $prow) {
					$prow['edition'] = 0;
					$prowsNew[] = $prow;
				}
				// add generic pages
				if( isset($orgEditions[0]) && is_array($orgEditions[0])) foreach ($orgEditions[0] as $prow) {
					$prow['edition'] = 0;
					$prowsNew[] = $prow;
				}
			}

			foreach ($prowsNew as $prow) {
				$sthins = DBPage::insertPage($id, $prow['width'], $prow['height'], $prow['pagenumber'], $prow['pageorder'], $prow['pagesequence'],
					$prow['edition'], $prow['master'], $prow['instance'], $prow['nr'], $prow['types'], $prow['orientation']);
				if (!$sthins) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}

				// copy all pages
				for ($nr=1; $nr <= $prow['nr']; $nr++)
				{
					// if the orientation is set use that as part of the page name. otherwise stick to the original filename.
					$pagenrString =  (!is_null($prow['orientation']) && !empty($prow['orientation']))
						? '-' . $nr . '-' . $prow['orientation']
						: '-' . $nr;

					// copy all attach-types
					foreach (unserialize($prow['types']) as $tp) {
						$pagenrval = preg_replace('/[*"<>?\\\\|:]/i', '', $prow['pagenumber']);
						$pageobj = StorageFactory::gen( $objProps['StoreName'], $srcid, 'page', $tp[2], $verNr, $pagenrval.$pagenrString, $prow['oldedition']);
						$dummy = null;
						if( !$pageobj->copyFile( $newVerNr, $id, $storename, $pagenrval."-$nr", $prow['edition'], $dummy ) ) {
							throw new BizException( 'ERR_ATTACHMENT', 'Server', $pageobj->getError() );
						}
					}
				}
			}
		} else { // Pages given, typically done to create layout based on layout templates (planning interface)
			// Update pagerange, copy layout from template uses this to influence pages in new object:
			$pagenumberarray = array();
			if( isset($pages) ) foreach( $pages as $new_pag ) {
				$pagenumberarray[] = $new_pag->PageOrder;
			}
			$range = BizPage::calcPageRange($pages);
			DBObject::updatePageRange( $id, $range, 'Planning' ); //Pages created here are supposed to be planned

			// get all source pages
			$sth = DBPage::getPages( $srcid, 'Production' );
			$prows = array();
			while (($prow = $dbDriver->fetch($sth)) ) {
				$prows[] = $prow;
			}

			// transform single page object into array of one page object to smooth code after this point
			if( gettype( $pages ) == 'object' && isset( $pages->Page ) && gettype( $pages->Page ) == 'object' ) {
				$pages = array( $pages->Page );
			}
			// copy page renditions and find lowest pagenumber
			for( $i = 0; $i < count($pages); $i++ ) {
				$r = min( $i, count($prows)-1 );
				$prow = $prows[$r];

				$iPageOrder = $pages[$i]->PageOrder;
				if( isset( $pages[$i]->PageNumber ) && $pages[$i]->PageNumber ) { // not defined for plannings interface!
					$sPageNumber = $pages[$i]->PageNumber;
				} else {
					$sPageNumber = $iPageOrder;
				}
				$editionId = isset($pages[$i]->Edition->Id) ? $pages[$i]->Edition->Id : 0;
				$sthins = DBPage::insertPage($id, $prow['width'], $prow['height'], $sPageNumber, $iPageOrder, $pages[$i]->PageSequence,
					$editionId, $prow['master'], $prow['instance'], $prow['nr'], $prow['types'], $prow['orientation'] );
				if (!$sthins) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}

				$destEditionId = $editionId ? $editionId : null; // BZ#33887 - Copy file with editionId, when it was specified
				// copy all pages
				for ($nr=1; $nr <= $prow['nr']; $nr++)
				{
					// copy all attach-types
					foreach (unserialize($prow['types']) as $tp) {
						$pagenrval = preg_replace('/[*"<>?\\\\|:]/i', '', $prow['pagenumber']);

						// if the orientation is set use that as part of the page name. otherwise stick to the original filename.
						$pagenrString =  (!is_null($prow['orientation']) && !empty($prow['orientation']))
							? '-' . $nr . '-' . $prow['orientation']
							: '-' . $nr;

						$pageobj = StorageFactory::gen( $objProps['StoreName'], $srcid, 'page', $tp[2], $verNr, $pagenrval.$pagenrString, $prow['edition']);
						$dummy = null;
						if( !$pageobj->copyFile( $newVerNr, $id, $storename, "$iPageOrder-$nr", $destEditionId, $dummy, null ) ) {
							throw new BizException( 'ERR_ATTACHMENT', 'Server', $pageobj->getError() );
						}
					}
				}
			}
		}

		// Create a new dossier for this newly copied object or assign it to a selected dossier (If applicable)
		// @TODO: Is this fragment of code in used? Needs to be revised.
		if (! is_null($relations)) {
			foreach ($relations as $relation){
				//Since it is Dossier - Object relation, the Object will always be the child while the Dossier being the Parent.
				if (empty($relation->Child)){
					$relation->Child = $id;
				}

				$object = null;
				if ($relation->Parent == -1 && $relation->Type == 'Contained'){
					$object = new Object( 	$meta,				// meta data
						array(), null,		// relations, pages
						null, 			// Files array of attachment
						null, null, $targets	// messages, elements, targets
					);
					$dossierObject = self::createDossierFromObject($object);
					if (! is_null($dossierObject)){
						$relation->Parent = $dossierObject->MetaData->BasicMetaData->ID;
					}
				}

				// Don't delete object targets for overruled issues
				$overruledIssue = isset($object->Targets[0]->Issue) && $object->Targets[0]->Issue->OverrulePublication;
				if( !$overruledIssue ) {
					// BZ#16567 Delete object targets (if not a layout)
					// BZ#17915 Article created in dossier should remain without issues
					// BZ#18405 Delete object targets only if there are targets
					if ($relation->Parent > 0 && $relation->Child == $id && $relation->Type == 'Contained' &&
						isset( $object->MetaData->BasicMetaData->Type ) &&
						$object->MetaData->BasicMetaData->Type != 'Layout' &&
						$object->MetaData->BasicMetaData->Type != 'PublishForm' &&
						isset($object->Targets) && count($object->Targets) > 0 ) {
						//See BZ#17852 After creating Dossier from Layout (Create) checkin Issue information on layout is wrong.
						BizTarget::deleteTargets($user, $id, $object->Targets);
					}
				}

				// Copy the labels when needed
				if ( $newLabels ) {
					self::copyObjectLabelsForRelation($relation->Parent, $relation->Child, $newLabels);
				}
			}

			BizRelation::createObjectRelations( $relations, $user, $id, false );
		}

		// Copy also all relations to image-objects

		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$childRelations = DBObjectRelation::getObjectRelations( $srcid, 'childs' );
		$allowCopyForms = true; // Only allow copy PublishForm when CopyObject is called from internal.
		self::handleChildRelations( $dbDriver, $childRelations, $arr['state'], $user, $id, $targets, $meta, $newLabels );

		// Copy elements
		require_once BASEDIR.'/server/dbclasses/DBElement.class.php';
		$elements = DBElement::getElements($srcid); // get elements from source object
		if ( is_array( $elements ) ) { // typically for articles in IC format or HTML format
			// make sure GUIDs are unique
			if (count($replaceguids) > 0) {
				foreach ($elements as &$element) {
					$oldguid = $element->ID;
					if (isset($replaceguids[$oldguid])) {
						$element->ID = $replaceguids[$oldguid];
					}
				}
			}
			// complete the copy (by saving alle elements for the destination object)
			DBElement::saveElements($id, $elements );
		}

		// Copy the InDesign Articles (and their placements) set for layout objects.
		require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
		$idArticles = DBInDesignArticle::getInDesignArticles( $srcid );
		if( $idArticles ) {
			DBInDesignArticle::createInDesignArticles( $id, $idArticles );
		}
		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		$placements = DBPlacements::getPlacements( $srcid, 0, 'Placed' );
		foreach( $placements as $placement ) {
			DBPlacements::insertPlacement( $id, 0, 'Placed', $placement );
		}

		// Delete the Object Operations set for layout objects.
		// Those are about to get processed on a future version, while the user
		// makes a copy of the current version. Therefore they should be removed.
		require_once BASEDIR.'/server/bizclasses/BizObjectOperation.class.php';
		BizObjectOperation::deleteOperations( $id );

		// Retrieve fresh object from DB to make sure we return correct data (instead of mutated client data!)
		// Relations are needed because otherwise relational targets get lost during re-indexing (BZ#18050)
		$newObject = self::getObject( $id, $user, false, null, array( 'Targets', 'Relations' ) ); // no lock, no rendition

		// Copy object sticky note message
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		$newObject->MessageList = BizMessage::getMessagesForObject( $srcid );
		if( $newObject->MessageList ) {
			foreach( $newObject->MessageList->Messages as $key => $message ) {
				if( $message->StickyInfo ) { // Reset the following property before copy the source stickyinfo
					$message->Id = null;
					$message->TimeStamp = date('Y-m-d\TH:i:s');
					$message->MessageID = null;
					$message->ObjectVersion = null;
					$message->ObjectID = $id; // Set the ObjectID to the new copied object id
				} else {
					unset( $newObject->MessageList->Messages[$key] );
				}
			}
			BizMessage::sendMessagesForObject( $newObject );
		}

		// Add to search index:
		require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
		BizSearch::indexObjects( array( $newObject ), true/*$suppressExceptions*/, array('Workflow')/*$areas*/, true );

		// Do n-casting.
		require_once BASEDIR.'/server/smartevent.php';
		$userfull = BizUser::resolveFullUserName( $user );
		new smartevent_createobjectEx( BizSession::getTicket(), $userfull, $newObject );

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createObjectEvent( $newObject->MetaData->BasicMetaData->ID, 'create' );

		require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';
		BizEmail::sendNotification( 'copy object', $newObject, $objProps['Types'], null);

		if( MTP_SERVER_DEF_ID != '' ) {
			require_once BASEDIR.'/server/MadeToPrintDispatcher.class.php';
			MadeToPrintDispatcher::doPrint( $id, BizSession::getTicket() );
		}

		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			// Create object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
			require_once BASEDIR . '/server/bizclasses/BizLinkFiles.class.php';
			BizLinkFiles::createLinkFilesObj( $newObject, $storename );
		}

		require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObjectResponse.class.php';
		return new WflCopyObjectResponse( $newObject->MetaData, $newObject->Relations, $newObject->Targets );
	}

	/**
	 * Validates the overruled publication of an object if used.
	 *
	 * The following rules are applied:
	 * 1. Cannot have more than one overruled publication.
	 * 2. Cannot mix an overruled publication with other issues.
	 * 3. When removing the overruled publication, a new valid status and category must be passed.
	 *    Same applies vica versa.
	 *
	 * An error is thrown when the new metadata and targets do not satisfy these conditions.
	 *
	 * @param string $user User making the change
	 * @param MetaData $meta New object metadata
	 * @param Target[] $targets List of new Object targets
	 * @throws BizException Validation failed
	 */
	public static function validateOverruledPublications( $user, MetaData &$meta, $targets )
	{
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';

		$id = $meta->BasicMetaData->ID;

		if ( $id && is_null($targets) ) {
			// Get the targets when they aren't available (see BZ#35774)
			require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
			$targets = DBTarget::getTargetsByObjectId($id);
		}

		// Fix OverrulePublication setting in issue objects of new metadata
		// Because it's not really part of the metadata, clients don't send this setting
		foreach( $targets as $target ) {
			$target->Issue->OverrulePublication = DBIssue::isOverruleIssue( $target->Issue->Id );
		}

		// Check if targets do not contain multiple overruled brands and are not mixed
		// with other regular issues as object targets
		$numOverruleIssues = 0;
		if ($targets) foreach( $targets as $target ) {
			if( $target->Issue->OverrulePublication ) {
				$numOverruleIssues += 1;
			}
		}
		if( $numOverruleIssues > 1 ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'Found multiple overrule issues on object' . PHP_EOL . 'id=' . $id . ', numOverruleIssues=' . $numOverruleIssues );
		} else if( $numOverruleIssues == 1 && count( $targets ) > 1 ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client',
				'Issue with overrule brand setting is used with other issues on object' . PHP_EOL . 'id=' . $id );
		}

		$pubId = $meta->BasicMetaData->Publication->Id;
		$catId = $meta->BasicMetaData->Category->Id;
		$stateId = $meta->WorkflowMetaData->State->Id;

		// Check if new object uses an issue with overruled brand
		$newOverruledIssue = count($targets) == 1 && $targets[0]->Issue->OverrulePublication;
		$foundState = false;
		$foundCategory = false;

		// Object targets should stay having just one overruled object target
		// Note that it can be changed to a different overruled issue
		if( $newOverruledIssue ) {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$categories = BizPublication::getSections( $user, $pubId, $targets[0]->Issue->Id, 'full', false );
			$states	= BizWorkflow::getStates( $user, $pubId, $targets[0]->Issue->Id, null, $meta->BasicMetaData->Type, false, true );
		} else {
			// Get states and categories for publication
			$pubInfos = BizPublication::getPublications( $user, 'full', $pubId );
			$pubInfo = $pubInfos[0];
			$states = $pubInfo->States;
			$categories = $pubInfo->Categories;
		}

		// Find matching status and category
		foreach( $states as $state ) {
			if( $state->Id == $stateId ) {
				$foundState = true;
				break;
			}
		}
		if( !$foundState ) {
			$errMsg = 'Found an invalid State ' . $meta->WorkflowMetaData->State->Name . ' for ';
			if( $numOverruleIssues == 1 ) {
				$errMsg .= 'Overrule Issue ' . $targets[0]->Issue->Name;
			} else {
				$errMsg .= 'Brand ' . $meta->BasicMetaData->Publication->Name;
			}
			throw new BizException( 'ERR_INVALIDSTATE', 'Client',
				$errMsg . PHP_EOL . 'id=' . $id, null, array( $meta->WorkflowMetaData->State->Name ) );
		}

		foreach( $categories as $section ) {
			if( $section->Id == $catId ) {
				$foundCategory = true;
				break;
			}
		}
		if( !$foundCategory ) {
			$errMsg = 'Found an invalid Category ' . $meta->BasicMetaData->Category->Name . ' for ';
			if( $numOverruleIssues == 1 ) {
				$errMsg .= 'Overrule Issue ' . $targets[0]->Issue->Name;
			} else {
				$errMsg .= 'Brand ' . $meta->BasicMetaData->Publication->Name;
			}
			throw new BizException( 'ERR_INVALIDCATEGORY', 'Client',
				$errMsg . PHP_EOL . 'id=' . $id, null, array( $meta->BasicMetaData->Category->Name ) );
		}
	}

	/**
	 * Validates changed object meta data and its new targets.
	 * It runs the connectors to allow them doing validation as well.
	 * It resolves the RouteTo and it can apply a new name (autonaming).
	 * Validation means that the data could change so $meta and $targets are passed by reference!
	 * It returns all meta data in flattened structure.
	 *
	 * @param string $user Short name (=id) of user.
	 * @param MetaData $meta The MetaData structure of an object.
	 * @param array $targets List of Targets to be applied to object
	 * 	(list that has been sent by client app, which does not have to be complete!).
	 * @param Relation[] $relations Relations of the object.
	 * @param boolean $restore The object is restored from the Trash Can.
	 * @throws BizException when validation failed
	 */
	public static function validateMetaDataAndTargets( $user, MetaData &$meta, &$targets, $relations = null, $restore = false )
	{
		$id 		= $meta->BasicMetaData->ID;
		$type 		= $meta->BasicMetaData->Type;
		$name		= $meta->BasicMetaData->Name;

		$meta->BasicMetaData->Name = self::nameValidation(
			$user,
			$meta,
			$id,
			$name,
			$type,
			$targets,
			$relations,
			$restore );
		// Give the connectors the opportunity
		$connRetVals = array(); // not used
		BizServerPlugin::runDefaultConnectors(
			'NameValidation',
			null,
			'validateMetaDataAndTargets',
			array($user, &$meta, &$targets),
			$connRetVals );

		if( isset( $meta->WorkflowMetaData->RouteTo ) ) {
			$routeto 	= $meta->WorkflowMetaData->RouteTo;
		} else {
			$routeto 	= null;
		}

		// if routeto-field is not empty, be sure to always set it to the 'short' username, see BZ#4866.
		if (!empty($routeto)) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$routetouserrow = DBUser::findUser(0, $routeto, $routeto);
			if ($routetouserrow) {
				$meta->WorkflowMetaData->RouteTo = $routetouserrow['fullname'];
			}
			else {
				require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
				$routetogrouprow = BizUser::findUserGroup($routeto);
				if ($routetogrouprow) {
					$meta->WorkflowMetaData->RouteTo = $routeto;
				}
			}
		}

		// Handle high-res path; Make it relative to high-res store.
		// This is done by removing HighResStore[Mac/Win] base path setting (for adverts)
		// or by removing HighResImageStore[Mac/Win] base path settings (for images)
		// from the HighResFile object property before storing it into db.
		$highresfile = isset($meta->ContentMetaData->HighResFile) ? trim($meta->ContentMetaData->HighResFile) : '';
		if( $highresfile != '' ) {
			require_once BASEDIR.'/server/bizclasses/HighResHandler.class.php';
			$highresfile = HighResHandler::stripHighResBasePath( $highresfile, $type );
			$meta->ContentMetaData->HighResFile = $highresfile;
		}

		// Make sure that the Orientation is in range [1...8] or null.
		self::validateAndRepairOrientation( $meta );

		// Derive the Dimensions property value from Width, Height and Orientation.
		self::determineDimensions( $meta );

		// Validate overruled publications
		self::validateOverruledPublications( $user, $meta, $targets );
	}

	/**
	 * Make sure that the Orientation is in range [1...8] or null.
	 *
	 * When not in range, null is assigned to tell callers (e.g. GetObjects) there is no Orientation set
	 * for the object, or to tell the database (e.g. SaveObjects) that the orientation should not be stored.
	 *
	 * Note that when there is no Orientation, there is a zero (0) stored in the database. Unlike other properties,
	 * this should be converted to null when read from DB to tell clients there is no Orientation set for the object.
	 * Also note that only for images (and adverts?) the Orientation property makes sense.
	 *
	 * @since 10.1.0
	 * @param MetaData $meta
	 */
	private static function validateAndRepairOrientation( MetaData $meta )
	{
		if( isset( $meta->ContentMetaData->Orientation ) ) {
			if( !ctype_digit( strval( $meta->ContentMetaData->Orientation ) ) ||
				$meta->ContentMetaData->Orientation < 1 ||
				$meta->ContentMetaData->Orientation > 8 ) {
				$meta->ContentMetaData->Orientation = null; // repair
			}
		}
	}

	/**
	 * Determines the Dimensions property value, which is not(!) stored in DB, readonly, and for UI purposes only.
	 *
	 * When Width and Height are set, the Dimensions will get value "Width x Height" unless the Orientation tells that
	 * the image should be rotated 90 degrees, it will get value "Height x Width". When Width and Height are not set
	 * the Dimensions will remain undetermined (unset).
	 *
	 * IMPORTANT: Please keep this function in-sync with BizQueryBase::determineDimensions() !
	 *
	 * @since 10.1.0
	 * @param MetaData $meta
	 */
	private static function determineDimensions( MetaData $meta )
	{
		if( isset( $meta->ContentMetaData->Width ) && $meta->ContentMetaData->Width > 0 &&
			isset( $meta->ContentMetaData->Height ) && $meta->ContentMetaData->Height > 0 ) {
			if( isset( $meta->ContentMetaData->Orientation ) && $meta->ContentMetaData->Orientation >= 5 ) { // 90 degrees rotated?
				$lhs = $meta->ContentMetaData->Height;
				$rhs = $meta->ContentMetaData->Width;
			} else {
				$lhs = $meta->ContentMetaData->Width;
				$rhs = $meta->ContentMetaData->Height;
			}
			$meta->ContentMetaData->Dimensions = "$lhs x $rhs";
		}
	}

	/**
	 * Validates the name of an object.
	 * The name of a dossier, layout or layout module must be unique within the issues it is used.
	 * If the user enters a name an exception is thrown if it is not unique. During restoring of an object from the
	 * Trash Can the system can provide a unique name if the original name is use.
	 * Objects contained by a dossier or task by default should have unique names. This means:
	 * - If an object is added to a dossier/task check the names of other objects in the dossier of the same type. If
	 * 	there is an object with same name make the name of the added object unique. Except when:
	 *  	- The added object is already contained by another dossier/task.
	 * 		- A plug-in has indicated that the autonaming must not be applied.
	 * 		- Publishforms are named like the dossier they belong. Two publishforms within the same dossier have the
	 * 			same name.
	 * @staticvar array $uniqueIssueTypes Object types that must have unique names within their issues.
	 * @param string $user Short user name
	 * @param null|Metadata $meta Metadata of the object.
	 * @param integer $id Object id or null in case of a not yet created object.
	 * @param string $proposedName Name as passed by the client application.
	 * @param string $type Object type.
	 * @param Target[] $targets Object targets.
	 * @param Relation[] $relations Relations (and relational targets).
	 * @param boolean $restore Object is restored from the TrashCan.
	 * @return string (changed) name.
	 * @throws BizException
	 */
	public static function nameValidation( $user, $meta, $id, $proposedName, $type, $targets, $relations, $restore )
	{
		// Check the length and invalid characters.
		$proposedName = trim($proposedName);
		if( empty($proposedName) ) {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client', $id );
		}
		if (!self::validName($proposedName)) {
			throw new BizException( 'ERR_NAME_INVALID', 'Client', $proposedName );
		}

		$applyAutoNaming = null; // Default is null to let the core decide.
		if( BizSession::isAppInTicket( null, 'mover-' ) ) { // Smart Mover client, always apply auto naming
			$applyAutoNaming = true;
		} else {
			// Apply custom validation for name conventions and meta data filtering
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array(); // not used
			BizServerPlugin::runDefaultConnectors(
				'NameValidation',
				null,
				'applyAutoNamingRule',
				array( $user, $meta, $targets, $relations ),
				$connRetVals );
			if ( $connRetVals ) foreach ( $connRetVals as $retVal ) {
				if ( !is_null( $retVal)) {
					$applyAutoNaming = $retVal;
					break;
				}
			}
		}

		// Check if name (for given type) is unique for this publication and issue(s)
		static $uniqueIssueTypes = array(
									'Dossier' => true,
									'DossierTemplate' => true,
									'Layout' => true,
									'LayoutTemplate' => true,
									'LayoutModule' => true,
									'LayoutModuleTemplate' => true,
									'Library' => true,
									'ArticleTemplate' => true,
									);
		if( isset($uniqueIssueTypes[$type])) { // Names must be unique within in the issue (for certain types). 
			if ( $targets ) { // Names must be unique on (target)issue level.
				$issueIds = array();
				foreach( $targets as $target ) { // preparation: collect the issue ids
					if( isset( $target->Issue->Id ) && $target->Issue->Id ) {
						$issueIds[] = $target->Issue->Id;
					}
				}
				if( $issueIds ) { 
					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					if( self::objectNameExists( $issueIds, $proposedName, $type, $id ) ) {
						if( $restore || $applyAutoNaming === true ) { // When autonaming is true or restoring an object, unique name will be generated.
							$proposedName = self::getUniqueObjectName( $id, $proposedName, $issueIds, $type, $restore );
						} else {
							throw new BizException( 'ERR_NAME_EXISTS', 'Client', $proposedName );
						}	
					}
				}
			}
		} elseif ( $applyAutoNaming !== false && $type != 'PublishForm' ) {
			// Newly created files in Dossier (Template) or Task must get a unique name (except for PublishForms).
			if ( !$id || $restore ) { // New object, or object is restored from Trash and new to the Workflow.
				if ( $relations ) foreach ( $relations as $relation ) {
					if ( $relation->Type == 'Contained' ) {
						$proposedName = DBObject::getUniqueNameForChildren( $relation->Parent, $proposedName, $type, $id );
					}
				}		
			} else { // Existing object
				require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
				if ( !DBObjectRelation::getObjectRelations( $id, 'parents', null, false )) {
					if ( $relations ) foreach ( $relations as $relation ) {
						$proposedName = DBObject::getUniqueNameForChildren( $relation->Parent, $proposedName, $type, $id );
					}
				}
			}
		}

		return $proposedName;
	}

	/**
	 * Validates changed object meta data.
	 *
	 * It runs the name validation connectors to allow them doing validation as well.
	 * Validation means that the data could change, so $changedMetaDataValues is passed by reference!
	 *
	 * @param string $user Short name (=id) of user.
	 * @param MetaData $invokedMetaData The essential MetaData of an object.
	 * @param array $changedMetaDataValues The Metadata values to be changed. Can be modified to add or remove properties.
	 * @throws BizException When validation failed for this object.
	 */
	public static function validateMetaDataInMultiMode( $user, MetaData $invokedMetaData, array &$changedMetaDataValues )
	{
		// Apply custom validation for name conventions and meta data filtering.
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array(); // Not used.
		BizServerPlugin::runDefaultConnectors( 'NameValidation', null, 'validateMetaDataInMultiMode',
			array($user, $invokedMetaData, &$changedMetaDataValues), $connRetVals );
	}

	/**
	 * Returns true when specified object name already exists in database.
	 *
	 * Can be used for new and existing objects.
	 *
	 * @param array $issueIds List of issue ids to check for object name uniqueness
	 * @param string $name Object name
	 * @param string $type Object type
	 * @param int $id Object id if object already exists
	 *
	 * @return int object id if name exists
	 */
	public static function objectNameExists( $issueIds, $name, $type, $id=null)
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		return DBObject::objectNameExists( $issueIds, $name, $type, $id );
	}

	/**
	 * Locks objects in the DB. Throws S1021 when object was locked already.
	 *
	 * The version of objects should be provided. When mismatches, the S1140 error
	 * is thrown to let caller get latest object and try again.
	 *
	 * The function return the object ids of successfully locked objects only.
	 * Caller should enable the reporting feature to catch errors.
	 *
	 * It does not check for read/write access rights; Those are covered by
	 * GetObjects and SaveObjects services, which are needed to edit content.
	 *
	 * @param string $user
	 * @param ObjectVersion[] $haveVersions
	 * @return integer[] The object ids of successfully locked objects.
	 * @throws BizException
	 */
	public static function lockObjects( $user, array $haveVersions )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		if( !$user || !$haveVersions ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Lock the objects, so that the version can no longer change by others in the meantime.
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		$haveObjectIds = array_map( function( $hv ) { return $hv->ID; }, $haveVersions );
		$lockedObjectIds = DBObjectLock::lockObjects( $haveObjectIds, $user );

		// Grab the latest object versions from DB (for those object that could be locked).
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		if( $lockedObjectIds ) {
			$currentVersions = DBObject::getObjectVersions( $lockedObjectIds );
		} else {
			$currentVersions = array();
		}

		// Make fast lookup map for locked objects: keys = object ids, values = true.
		if( $lockedObjectIds ) {
			$trueValues = array_fill( 0, count($lockedObjectIds), true );
			$lockedObjectIds = array_combine( $lockedObjectIds, $trueValues );
		}

		// Error when object not found or when current version mismatches with caller's 'have version'.
		foreach( $haveVersions as $haveVersion ) {
			try {
				if( !isset( $lockedObjectIds[$haveVersion->ID] ) ) {
					if( self::objectExists( $haveVersion->ID, 'Workflow' ) ) {
						throw new BizException( 'ERR_LOCKED', 'Client', $haveVersion->ID );
					} else {
						throw new BizException( 'ERR_NOTFOUND', 'Client', $haveVersion->ID );
					}
				}
				if( $currentVersions[$haveVersion->ID] != $haveVersion->Version ) {
					DBObjectLock::unlockObjects( array($haveVersion->ID), $user ); // undo our lock
					unset( $lockedObjectIds[$haveVersion->ID] );
					throw new BizException( 'ERR_OBJ_VERSION_MISMATCH', 'Client', $haveVersion->ID );
				}
			} catch( BizException $e ) {
				self::reportError( 'Object', $haveVersion->ID, null, $e );
			}
		}

		// Resolve the full name of the acting user for whom we're creating the locks.
		// For all invoked object ids, N-cast the new LockedBy value and notify event plugins.
		if( $lockedObjectIds ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$lockedBy = DBUser::getFullName( $user );

			require_once BASEDIR.'/server/smartevent.php';
			$eventProps = array( 'LockedBy' => $lockedBy );
			new smartevent_setPropertiesForMultipleObjects( array_keys( $lockedObjectIds ), $eventProps );

			require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
			$metaDataValues = array();
			$metaDataValues[0] = new MetaDataValue();
			$metaDataValues[0]->Property = 'LockedBy';
			$metaDataValues[0]->PropertyValues = array();
			$metaDataValues[0]->PropertyValues[0] = new PropertyValue();
			$metaDataValues[0]->PropertyValues[0]->Value = $lockedBy;
			BizEnterpriseEvent::createMultiObjectEvent( $lockedObjectIds, $metaDataValues );
		}

		// Return the ids of those objects that we locked by us and for which the caller 
		// has the latest version.
		return array_keys( $lockedObjectIds );
	}

	// =============== PRIVATE FUNCTIONS:
	private static function Lock( $id, $user )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		DBObjectFlag::lockObjectFlags( $id );
		DBObjectLock::lockObject( $id, $user );

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createObjectEvent( $id, 'update' );
	}

	private static function validateForSave($user, /** @noinspection PhpLanguageLevelInspection */
	                                        Object $object, $currRow )
	{
		// Block callers from overruling the Orientation; This is extracted from the native file by the ExifTool integration.
		// Shadow objects do not have a native, so we need them to tell us their orientation.
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		if( !BizContentSource::isShadowObject( $object ) ) {
			if ( isset( $object->MetaData->ContentMetaData->Orientation ) ) {
				$object->MetaData->ContentMetaData->Orientation = null;
			}
		}

		// Enrich object MetaData with any embedded metadata from file
		require_once BASEDIR.'/server/bizclasses/BizMetaDataPreview.class.php';
		$bizMetaPreview = new BizMetaDataPreview();
		$bizMetaPreview->readMetaData( $object );
		$bizMetaPreview->generatePreviewNow( $object );
		$bizMetaPreview->finaliseMetaData( $object );

		// For SaveObjects only: Protect the DocumentID and ContentSource properties from
		// accidentally changing for shadow objects. Or else it could break its link with the
		// content source store. This implies the DocumentID of Adobe is ignored, and
		// therefore this is a temporary fix.
		if( $object->MetaData->BasicMetaData->ID && $currRow ) { // save?
			require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
			BizContentSource::protectShadowFromBreakingLink( $object->MetaData,
				$currRow['contentsource'], $currRow['documentid'] );

			// If we are saving a PublishForm, ensure that the slugline is updated too.
			require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
			if ( BizPublishForm::isPublishForm( $object ) ) {
				$newSlugLine = self::getSluglineForPublishForm( $object );
				$object->MetaData->ContentMetaData->Slugline = $newSlugLine;
			}
		}

		// Validate meta data and targets
		self::validateMetaDataAndTargets( $user, $object->MetaData, $object->Targets, $object->Relations, false );

		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		$isShadowObject = BizContentSource::isShadowObject( $object );
		$arr = self::getFlatMetaData( $object->MetaData, $object->MetaData->BasicMetaData->ID, $object->Relations, $isShadowObject );

		// Validate files: like native not empty for layout/article
		self::validateFiles( $object );

		// Serialize the types of renditions that this object has.
		// This is added to flattened meta data only, it's not a property of class Object, but goes with object's db record
		$arr['types'] = self::serializeFileTypes( $object->Files );

		return $arr;
	}

	/**
	 * Adds the first level of data structure to a given MetaData tree.
	 *
	 * @since 10.2.0
	 * @param MetaData $metadata
	 */
	public static function completeMetaDataStructure( MetaData $metadata )
	{
		if( !isset($metadata->BasicMetaData) ) {
			$metadata->BasicMetaData = new BasicMetaData();
		}
		if( !isset($metadata->RightsMetaData) ) {
			$metadata->RightsMetaData = new RightsMetaData();
		}
		if( !isset($metadata->SourceMetaData) ) {
			$metadata->SourceMetaData = new SourceMetaData();
		}
		if( !isset($metadata->ContentMetaData) ) {
			$metadata->ContentMetaData = new ContentMetaData();
		}
		if( !isset($metadata->WorkflowMetaData) ) {
			$metadata->WorkflowMetaData = new WorkflowMetaData();
		}
		if( !isset($metadata->ExtraMetaData) ) {
			$metadata->ExtraMetaData = array();
		}
	}

	/**
	 * This function validates if the native file is available in the filestore for the following
	 * object types:
	 * - Article
	 * - Layout
	 * - ArticleTemplate
	 * - LayoutTemplate
	 * - AdvertTemplate
	 * - LayoutModule
	 * - LayoutModuleTemplate
	 * For the following object types there should be a output, trailer and native or high res file available:
	 * - Audio
	 * - Video
	 * When the given object is a shadow object, the function returns null.
	 *
	 * @param Object $object
	 * @throws BizException in case of error
	 * @return null
	 */
	private static function validateFiles( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		$md = $object->MetaData;
		$type = $md->BasicMetaData->Type;
		$name = $md->BasicMetaData->Name;

		// A shadow object doesn't need to have a native file rendition. It is up to the connector
		// to decide where the files should be saved. This can be in the Enterprise system, but
		// also in an external system. Always return null (no error) in this case.
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		if ( BizContentSource::isShadowObject($object) ) {
			return;
		}

		$nativefile = self::getRendition( $object->Files, 'native');

		if( $type == 'Article' || $type == 'Spreadsheet' || $type == 'Layout' || $type == 'ArticleTemplate' || $type == 'LayoutTemplate' ||
			$type == 'AdvertTemplate' || $type == 'LayoutModule' || $type == 'LayoutModuleTemplate' ) {
			if( !file_exists($nativefile->FilePath) &&
				$nativefile->Type != 'text/plain' ) { // BZ#19901 plain text can be zero bytes
				LogHandler::Log('bizobjects', 'ERROR', 'Attached file "'.$nativefile->FilePath.'" does not exist for object "'.$name.'"' );
				throw new BizException('ERR_UPLOAD_FILE_ATT', 'Client', $name );
			}
		} elseif ($type == 'PublishForm' || $type == 'PublishFormTemplate') {
			// Placeholder, validation for files.
		} elseif( $type == 'Audio' || $type == 'Video' ) {
			$outputfile  = self::getRendition( $object->Files, 'output');
			$trailer     = self::getRendition( $object->Files, 'trailer');
			$highresfile = isset($object->MetaData->ContentMetaData->HighResFile) ? trim($object->MetaData->ContentMetaData->HighResFile) : '';
			if( !file_exists($nativefile->FilePath) && !file_exists($outputfile->FilePath)&& empty($highresfile) && !file_exists($trailer->FilePath) ) {
				LogHandler::Log('bizobjects', 'ERROR', 'No file given for audio or video object "'.$name.'"' );
				throw new BizException('ERR_UPLOAD_FILE_ATT', 'Client', $name );
			}
		} // dossier(template), tasks and hyperlinks don't have content. Images and adverts can be planned, so they could have NO content
	}

	private static function validName( $name )
	{
		$sDangerousCharacters = "`~!@#$%^*\\|;:'<>/?";
		$sDangerousCharacters .= '"'; // Add double quote to dangerous characters.

		$sSubstringStartingWithInvalidCharacter = strpbrk($sDangerousCharacters, $name);
		return empty($sSubstringStartingWithInvalidCharacter); // true if no invalid character
	}

	/**
	 * Cleans up an Object before save operations are to be called.
	 *
	 * Cleans the Object's flags in the database.
	 *
	 * @param Object $object The Object to be cleaned up.
	 * @param array $row New database properties of the object.
	 * @param integer $prevState Previous status id of the object.
	 */
	private static function cleanUpBeforeSave( $object, $row, $prevState )
	{
		$id = $object->MetaData->BasicMetaData->ID;

		// Delete object flags
		require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
		DBObjectFlag::deleteObjectFlags( $id );

		// Keep MtP in the loop:
		if( MTP_SERVER_DEF_ID != '' ) {
			require_once BASEDIR.'/server/MadeToPrintDispatcher.class.php';
			MadeToPrintDispatcher::clearSentObject( $id, $row['publication'], $row['state'], $prevState );
		}

	}

	private static function saveExtended($id, /** @noinspection PhpLanguageLevelInspection */
	                                     Object $object, $user, $create )
	{
		// Save object's elements:
		$objectelements = array();
		if (isset($object->Elements)) {
			if (is_array($object->Elements)) {
				$objectelements = $object->Elements;
			}
		}
		require_once BASEDIR.'/server/dbclasses/DBElement.class.php';
		DBElement::saveElements( $id, $objectelements );

		// Before delete+create object relations, make sure to remove all InDesignArticles
		// since this does a cascade delete of their object->placements, which may be 
		// referenced through the relation->placements as well; Doing this after would 
		// destroy the InDesignArticle placements set through the relations.
		if( !$create && !is_null($object->InDesignArticles) ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
			DBInDesignArticle::deleteInDesignArticles( $id );
		}

		// Delete all InDesignArticle placements (v9.7)
		// Needs to be done BEFORE saveObjectPlacedRelations() since that is ALSO creating
		// InDesignArticle placements (the ones that are also relational placements).
		if( !is_null($object->Placements) ) {
			require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
			DBPlacements::deletePlacements( $id, 0, 'Placed' );
		}

		// Create / Update object relations
		self::saveObjectPlacedRelations( $user, $id, $object->Relations, $create, true );

		// Save edition/device specific renditions (types)
		require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
		$version = $object->MetaData->WorkflowMetaData->Version;
		if( $object->Files ) foreach( $object->Files as $file ) {
			if( $file->EditionId ) {
				if( !self::isStorageRendition( $file->Rendition ) ) {
					LogHandler::Log( 'bizobject', 'ERROR', 'Saving unsupported file rendition "'.$file->Rendition.'".' );
				}
				DBObjectRenditions::saveEditionRendition(
					$id, $file->EditionId, $file->Rendition, $file->Type, $version );
			}
		}

		// Save object messages
		if( $object->MessageList ) {
			if( $create ) { // When create, we need to resolve the message ObjectID with created object id
				if (isset($object->MessageList->Messages)) foreach( $object->MessageList->Messages as $message ) {
					if( is_null( $message->ObjectID ) ) {
						$message->ObjectID = $id;
					}
				}
			}
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			BizMessage::sendMessagesForObject( $object );
		}

		// Note that the DB updates below are guarded by BizObject::createSemaphoreForSaveLayout()
		// to avoid SaveObjects being executed in the same time as PreviewArticle(s)AtWorkspace (EN-86722).

		// Save the Indesign Articles for the layout object (v9.7).
		if( !is_null($object->InDesignArticles) ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticle.class.php';
			DBInDesignArticle::createInDesignArticles( $id, $object->InDesignArticles );
		}

		// Save the Indesign Article Placements for the layout object (v9.7).
		if( !is_null($object->Placements) ) {
			foreach( $object->Placements as $placement ) {
				DBPlacements::insertPlacement( $id, 0, 'Placed', $placement );
			}
		}

		// Save the Object Operations for the layout object (9.7).
		// Typically used for instantiate template service, internally calling createObject().
		require_once BASEDIR.'/server/bizclasses/BizObjectOperation.class.php';
		if( !$create ) { // on save, assume all operations are processed on layout
			BizObjectOperation::deleteOperations( $id );
		}
		if( !is_null($object->Operations) ) {
			BizObjectOperation::createOperations( $user, $id, $version, $object->Operations );
		}
	}

	/**
	 * Stores the given object relations (of type Placed) in the DB.
	 *
	 * Assumed is that the give set of relations is complete;
	 * All relations are removed and created in the DB.
	 *
	 * @param string $user
	 * @param integer $id
	 * @param Relation[]|null $relations
	 * @param boolean $checkAccess Check if the user has the right to update the relation. See EN-87518.
	 * @param bool $create TRUE when creating a new object, FALSE when saving existing object.
	 */
	public static function saveObjectPlacedRelations( $user, $id, $relations, $create, $checkAccess )
	{
		require_once BASEDIR."/server/bizclasses/BizRelation.class.php";

		// Retrieve the arrived 'Placed' relation.
		// These relations are either to be inserted or updated (which will be handled later).
		$relationsToBeUpdated = array(); // To collect relations to be updated(update and delete).
		$newRelations = array(); // To collect relations to be added.
		if ( isset( $relations )) {
			// BZ#19923 - When it is non create action, filtered relations to get only the "Placed" relation.
			// There are no changes that need to be done on non placed relations, therefore skip it,
			// to avoid the DB duplicate entry error in objectrelations table.
			if( $create ) {
				$newRelations = $relations;
			} else {
				foreach( $relations as $relation ) {
					if( $relation->Type == 'Placed' ) {
						$newRelations[] = $relation;
					}
				}
			}
		}

		// For Save (!create) we first delete all placed relations
		// If the current relations is null do not delete placed relations (BZ #17159)
		if( !$create && !is_null($relations) ) {
			// BZ#18888 - Get object childs relation only without parents relation, else parent relation will lost
			$placedOldDeletedRelations = BizRelation::getDeletedPlacedRelations( $id, $relations, 'childs' );
			//First delete 'placed' relations that has been removed.
			if( !empty($placedOldDeletedRelations) ) {
				// This will also take care of removing objects with no content which are not
				// related to other objects.
				BizRelation::deleteObjectRelations( $user, $placedOldDeletedRelations, true );
			}

			self::handleObjectRelations( $id, $newRelations, $relationsToBeUpdated );
		}
		if( $relationsToBeUpdated ) {
			BizRelation::updateObjectRelations( $user, $relationsToBeUpdated, false /* See BZ#36045 */, $checkAccess );
		}
		if( $newRelations ) {
			// => Added $create param => BZ#15317 Broadcast event when implicitly creating
			//    dossier for CreateObjects... (do we need this for SaveObjects too?)
			BizRelation::createObjectRelations( $newRelations, $user, $id, $create );
		}
	}

	/**
	 * Identify every relation in $newRelations whether it is a new Relation or a Relation
	 * already exists in the Database.
	 *
	 * For a new relation, it is left untouched in $newRelations.
	 * If the relation in $newRelations is found to be already exists in the database:
	 * 1. The relation's Target is updated with the data from DB.
	 *      - The data updated are PublishedDate and PublishedVersion and ExternalId
	 * 2. The relation is removed from $newRelations and shifted to $relationsToBeUpdated.
	 *
	 * @param int $id Object Id to handle its object relations.
	 * @param array &$newRelations List of new relations to be inserted into DB. Read above *.
	 * @param array &$relationsToBeUpdated List of relations to be filled in by the function.
	 */
	static private function handleObjectRelations( $id, &$newRelations, &$relationsToBeUpdated )
	{
		// These 'new' relations are either to be inserted or updated. (Deletion is handled above)
		$existingRelations = BizRelation::getObjectRelations( $id, false, false, 'childs' );
		$relationIndex = 0;
		if( $newRelations ) foreach( $newRelations as $newRelation ) {
			$currRelationFoundInDb = false; // Assume the relation is a new one ( not yet stored in DB before)
			if( $existingRelations ) foreach( $existingRelations as $existingRelation ) {
				if( ( $newRelation->Parent == $existingRelation->Parent ) &&
					$newRelation->Child == $existingRelation->Child &&
					$newRelation->Type == $existingRelation->Type && $newRelation->Type == 'Placed' ) { // The arrival data(Placed Relation) does exists in DB already.

					// Find for the corresponding Target and enriched the Target with the data from DB.
					$newTargets = $newRelation->Targets;
					$existingTargets = $existingRelation->Targets;
					if( $newTargets && $existingTargets ) { // Only adjust the arrival Targets when they are found both in arrival and DB.
						foreach( $newTargets as $newTarget ) {
							foreach( $existingTargets as $existingTarget ) {
								if( $newTarget->PubChannel->Id == $existingTarget->PubChannel->Id &&
									$newTarget->Issue->Id == $existingTarget->Issue->Id ) {
									$newTarget->PublishedDate = $existingTarget->PublishedDate;
									$newTarget->PublishedVersion = $existingTarget->PublishedVersion;
									$newTarget->ExternalId = $existingTarget->ExternalId;
									break; // Found the corresponding existing target with the arrival target, so go on to the next arrival target.
								}
							}
						}
					}
					$currRelationFoundInDb = true;
					break; // Found the arrival Relation correspond with the Relation from DB, break here and continue with the next arrival Relation.
				}
			}
			if( $currRelationFoundInDb ) {
				$relationsToBeUpdated[] = $newRelation; // It is equivalent to $newRelations[$relationIndex]
				unset( $newRelations[$relationIndex] ); // It is not a new Relation, so remove from here.
			}
			$relationIndex++;
		}
	}
	/**
	 * Saves all given object files at file storage.
	 *
	 * @param string $storeName   Object storage name used at file store
	 * @param string $objId       Object ID
	 * @param array $files        Collection of Attachment objects (files / renditions)
	 * @param string $objVersion  Object version in major.minor notation
	 * @throws BizException
	 */
	public static function saveFiles( $storeName, $objId, $files, $objVersion )
	{
		if( $files ) foreach( $files as $file ) {
			if( self::isStorageRendition( $file->Rendition ) ) {
				$storage = StorageFactory::gen( $storeName, $objId, $file->Rendition, $file->Type,
					$objVersion, null, $file->EditionId, true );
				if( !$storage->saveFile( $file->FilePath ) ) {
					throw new BizException( 'ERR_ATTACHMENT', 'Server', $storage->getError() );
				}
			} else {
				LogHandler::Log( 'bizobject', 'ERROR', 'Tried to save unsupported file rendition "'.$file->Rendition.'".' );
			}
			clearstatcache(); // Make sure unlink calls above are reflected!
		}
	}

	/**
	 * Derives a rendition-format map from given object files and serializes it.
	 * This is typically stored at the 'types' field of smart_object table to be able
	 * to lookup files (renditions) in the filestore that belong to the stored object.
	 *
	 * @param array $files List of Attachment objects.
	 * @return string Serialized rendition-format map.
	 */
	public static function serializeFileTypes( $files )
	{
		$types = array();
		if( $files ) foreach( $files as $file ) {
			if( self::isStorageRendition( $file->Rendition ) ) {
				if( !$file->EditionId ) {
					$types[ $file->Rendition ] = $file->Type;
				}
			} else {
				LogHandler::Log( 'bizobject', 'ERROR', 'Tried to serialize unsupported file rendition "'.$file->Rendition.'".' );
			}
		}
		return serialize( $types );
	}

	/**
	 * Searches through given attachments for a certain rendition.
	 *
	 * @param array $files List of Attachment objects to search through.
	 * @param string $rendition Rendition to lookup in $files.
	 * @return Attachment Returns null when rendition was not found or when unsupported rendition was requested.
	 */
	public static function getRendition( $files, $rendition )
	{
		if( self::isStorageRendition( $rendition ) ) {
			if( $files ) foreach( $files as $file ) {
				if( is_object( $file ) && $file->Rendition == $rendition ) {
					return $file;
				}
			}
		} else {
			LogHandler::Log( 'bizobject', 'ERROR', 'Requested for unsupported file rendition "'.$rendition.'".' );
		}
		return null;
	}

	/**
	 * Tells if the given object file rendition is used for file storage.
	 * Note that 'none' and 'placement' are valid renditions at WSDL, but NOT stored at DB,
	 * for which FALSE is returned by this function.
	 *
	 * @param string $rendition
	 * @return boolean
	 */
	private static function isStorageRendition( $rendition )
	{
		$renditions = array( 'thumb', 'preview', 'native', 'output', 'trailer' );
		return in_array( $rendition, $renditions );
	}

	/**
	 * Returns the given metadata information in a flat array structure.
	 *
	 * The object id can be the id of the object itself or the parent object
	 * when copying.
	 *
	 * @param MetaData $meta
	 * @param integer $objID
	 * @param array $relations
	 * @param boolean $isShadowObject
	 * @return array
	 */
	private static function getFlatMetaData( MetaData $meta, $objID = null, $relations = null, $isShadowObject = false )
	{
		// Get all property paths used in MetaData and object fields used in DB
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$objFields = BizProperty::getMetaDataObjFields();
		$propPaths = BizProperty::getMetaDataPaths();

		// Walk through all DB object fields and take over the values provided by MetaData tree
		$arr = array();
		$systemDeterminedFields = array( 'id', 'created', 'creator', 'modified', 'modifier', 'lockedby', 'majorversion', 'minorversion' );
		if ( $isShadowObject ) {
			$systemDeterminedFields = array( 'id', 'created', 'majorversion', 'minorversion' );
		}
		foreach( $objFields as $propName => $objField ) {
			$propPath = $propPaths[$propName];
			if( !is_null($objField) && !is_null($propPath) &&
				// Don't accept system determined fields from outside world
				!in_array( $objField, $systemDeterminedFields )) {
				eval( 'if( isset( $meta->'.$propPath.' ) && ($meta->'.$propPath.' !== null) ) $arr["'.$objField.'"] = $meta->'.$propPath.';');
			}
		}

		$contentMetaData = $meta->ContentMetaData;
		if (isset($contentMetaData->Keywords)) {
			$kw = $contentMetaData->Keywords;
			if(is_object($kw)){
				$kw = $kw->String;
			}
			if(!is_array($kw)){
				$kw = array($kw);
			}
			if( count( $kw ) > 0 ) {
				$arr['keywords'] = implode ("/", $kw);
			}
		}

		// handle extra metadata
		$extraMetaData = $meta->ExtraMetaData;
		if ($extraMetaData /*&& isset($extraMetaData->ExtraMetaData)*/) { // BZ#6704
			// Object type might be specified with incoming meta data. If not, get it from DB
			@$objType = $arr['type'];
			if( !$objType || $objType == "" ) {
				// get obj type from DB:
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$objType = DBObject::getObjectType( $objID );
			}

			// Only resolve the PublishSystem and TemplateId when the objectid or relations are given.
			// When both aren't given (could be when creating a new PublishFormTemplate for example)
			// don't filter.
			$publishSystem = $templateId = null;
			if ( $objID || $relations ) {
				list( $publishSystem, $templateId ) = self::getPublishSystemAndTemplateId( $objID, $relations );
			}

			require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
			$extradata = DBProperty::getProperties( $meta->BasicMetaData->Publication->Id, $objType, true, $publishSystem, $templateId );
			if( $extradata ) foreach ( $extradata as $extra ) {
				foreach( $extraMetaData as $md ) {
					// Note: Single ExtraMetaData elements are given as objects,
					// but multiple elements are given as array!
					// Because ExtraMetaData elements have parent with very same name
					// (also "ExtraMetaData"), due to the WW SOAP hack in PEAR lib,
					// BOTH elements will have Property and Values defined (see BizDataClasses).
					// So, for the parent (container), we have to skip Name and Values
					// elements which mean nothing.

					// look for corresponding extrametadata
					// >>> Bugfix: Some clients give bad namespaces and so a single value array can become an object (PEAR issue)
					$mdNodes = array();
					if( is_object($md) ) { // change single object into array of one object
						$mdNodes[] = $md;
					} elseif( is_array( $md ) ) {
						$mdNodes = $md;
					} // <<< else, skip Values and Property elements of parent/container

					if (count($mdNodes) > 0 ) foreach( $mdNodes as $mdNode ) {
						// Bugfix: some clients give mixed case (instead of uppercase), so now using strcasecmp
						if( strcasecmp( $mdNode->Property, $extra->Name ) == 0 ) { // configured?
							// >>> Bugfix: Some clients give bad namespaces and so a single value array can become an object (PEAR issue)
							$mdValues = array();
							if( is_object( $mdNode->Values ) ) { // change single object into array of one object
								$mdValues[] = $mdNode->Values->String;
							} elseif( is_array( $mdNode->Values ) ) {
								$mdValues = $mdNode->Values;
							} else {
								$mdValues[] = $mdNode->Values;
							}// <<<
							if( substr($extra->Type, 0, 5) == 'multi' && $extra->Type != 'multiline' && is_array($mdValues)) { // BZ#13545 exclude multiline!
								$value = implode( BizProperty::MULTIVALUE_SEPARATOR, $mdValues ); // typically for multilist and multistring
							} else { // single value
								$value = $mdValues[0];
							}
							//BZ#10854 always lowercase in DB fields
							$arr[strtolower($extra->Name)] = $value;
						}
					}
				}
			}
		}

		//BZ#10272 $arr should always contain short user names!
		$userkeys = array('creator','modifier','routeto','deletor');
		foreach ($userkeys as $userkey) {
			if (array_key_exists($userkey, $arr) && $arr[$userkey]) {
				$userrow = DBUser::getUser($arr[$userkey]);
				if ($userrow) {
					$arr[$userkey] = $userrow['user'];
				}
			}
		}
		return $arr;
	}

	// TO DO for v7: this is application stuff, has nothing to do with business logic
	// needs to move to apps folder:
	public static function getTypeIcon($typename)
	{

		$icondir = '../../config/images/';
		switch ($typename)
		{
			case 'Article':
			{$result = 'Obj_Article.gif';
				break;}
			case 'ArticleTemplate':
			{$result = 'Obj_ArticleTemplate.gif';
				break;}
			case 'Layout':
			{$result = 'Obj_Layout.gif';
				break;}
			case 'LayoutTemplate':
			{$result = 'Obj_LayoutTemplate.gif';
				break;}
			case 'Video':
			{$result = 'Obj_Video.gif';
				break;}
			case 'Audio':
			{$result = 'Obj_Audio.gif';
				break;}
			case 'Library':
			{$result = 'Obj_Library.gif';
				break;}
			case 'PublishForm':
			case 'Dossier':
			{$result = 'Obj_Dossier.gif';
				break;}
			case 'PublishFormTemplate':
			case 'DossierTemplate':
			{$result = 'Obj_DossierTemplate.gif';
				break;}
			case 'Task':
			{$result = 'Obj_Task.gif';
				break;}
			case 'Hyperlink':
			{$result = 'Obj_Hyperlink.gif';
				break;}
			case 'LayoutModule':
			{$result = 'Obj_LayoutModule.gif';
				break;}
			case 'LayoutModuleTemplate':
			{$result = 'Obj_LayoutModuleTemplate.gif';
				break;}
			case 'Spreadsheet':
				$result = 'Obj_Spreadsheet.png';
				break;
			case 'Image':
			case 'Advert':
			case 'AdvertTemplate':
			case 'Plan':
			default:
			{$result = 'Obj_Image.gif'; break;}

		}
		return $icondir . $result;
	}

	/**
	 * Tries to lock the given object when the given user (still) has no longer the lock.
	 *
	 * @param string $user short user name
	 * @param string $objId Unique object ID
	 * @param bool $checkAccess check user access right
	 * @return bool User has lock.
	 * @throws BizException when locked by someone else or lock fails
	 */
	public static function restoreLock( $user, $objId, $checkAccess = true)
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';

		$lockUser = DBObjectLock::checkLock( $objId );
		if( !$lockUser ) { // no-one else has the lock, so we try taking it back...
			self::getObject( $objId, $user, true, 'none', null, null, $checkAccess ); // lock, no content, BZ#17253 - checkAccess false when article created from template
			// Note: We use getObject to trigger lock events, etc
		} else {
			if( strtolower($lockUser) != strtolower($user) ) { // someone else has the lock
				throw new BizException( 'OBJ_LOCKED_BY', 'Client',  $lockUser['usr'] );
			}
		}
	}

	/**
	 * Resolve the Object properties into MetaData object and returns it.
	 *
	 * When $fullMetaData is set to false, only BasicMetadata->ID, Name and Type will be resolved,
	 * the rest is set to null.
	 *
	 * @param array $row List of object properties name and its values.
	 * @param bool $customProps Whether or not to resolve the custom properties into ExtraMetaData.
	 * @param null|string $publishSystem
	 * @param null|int $templateId
	 * @param bool $fullMetaData Whether or not the function should return full Metadata.Refer to header above.
	 * @return MetaData
	 */
	private static function queryRow2MetaData( $row, $customProps = true, $publishSystem = null, $templateId = null, $fullMetaData = true )
	{
		if( !$fullMetaData ) {
			$meta = new MetaData();
			$meta->BasicMetaData = new BasicMetaData();
			$meta->BasicMetaData->ID = $row['ID'];
			$meta->BasicMetaData->Name = $row['Name'];
			$meta->BasicMetaData->Type = $row['Type'];
		} else { // Getting complete MetaData
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			// make keywords into array of strings
			if( array_key_exists( 'Keywords', $row ) ) {
				$keywords = strlen($row['Keywords']) == 0 ? array() : explode("/", $row['Keywords']);
				// L> We use strlen instead of empty since empty('0') is true
				// Also note that explode -always- returns 1 element, so check for emptyness. (BZ#29159)
			} else {
				$keywords = null;
			}

			// handle extra metadata
			$extramd = array();
			if ( $customProps ) {
				require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
				$extradata = DBProperty::getProperties( $row['PublicationId'], $row['Type'], true, $publishSystem, $templateId );
				if ($extradata){
					foreach ($extradata as $extra) {
						$name = $extra->Name;
						if ( isset( $row[$name] )) {
							if( substr( $extra->Type, 0, 5 ) == 'multi' && $extra->Type != 'multiline' ) { // BZ#13545 exclude multiline!
								// typically for multilist and multistring
								$values = explode( BizProperty::MULTIVALUE_SEPARATOR, $row[$name]);
							} else {
								$theVal = $row[$name];
								settype( $theVal, 'string' ); // BZ#4930: Force <String> elems (to avoid <item> for booleans)
								$values = array( $theVal );
							}
							// since v6 we return custom props including "c_" prefix!
							$extramd[] = new ExtraMetaData( $name, $values );
						}
					}
				}
			}

			// Get the complete collection of standard property path for MetaData struct
			$propPaths = BizProperty::getMetaDataPaths();
			$meta = new MetaData();
			// Build full MetaData tree and fill in with data from $row
			foreach( $propPaths as $propName => $propPath ) {

				$proptype = BizProperty::getStandardPropertyType($propName);

				switch ($proptype) {
					case 'datetime':
					case 'int':
					case 'list':
					case 'date':
					case 'double':
					{
						if (array_key_exists($propName, $row) && trim($row[$propName]) == '') {
							$row[$propName] = null;
						}
						break;
					}
					case 'bool':
					{
						if (array_key_exists($propName, $row)){
							$trimVal = trim(strtolower($row[$propName]));
							if ($trimVal == 'on' || // Indexed, Closed, Flag, LockForOffline (on/<empty>)
								$trimVal == 'y' || // DeadlineChanged (Y/N)
								$trimVal == 'true' || // CopyrightMarked (true/false) -> Fixed for BZ#10541
								$trimVal == '1' ) { // repair old boolean fields that were badly casted/stored in the past
								$row[$propName] = true;
							}
							else {
								$row[$propName] = false;
							}
						}
						break;
					}
				}


				if( !is_null($propPath) && array_key_exists($propName, $row)) { // >>> BZ#31129 instead of isset() use array_key_exists.
					// Oracle returns empty strings as null. PHP regards an array element as not set if it has the value null.
					// So the combination of Oracle and isset() makes that all row elements with value null where skipped.
					// Notice that array_key_exists() solves this problem but that there is still a difference between Oracle and
					// the other two databases. In case of Oracle the metadata property is set to null and for Mysql/Mssql it is
					// set to an empty string.
					// <<<
					// build MetaData tree on-the-fly (only intermediate* nodes are created)
					$pathParts = explode( '->', $propPath );
					array_pop( $pathParts ); // remove leafs, see*
					$path = '';
					foreach( $pathParts as $pathPart ) {
						$path .= $pathPart;
						/** @noinspection PhpUnusedLocalVariableInspection */
						$class = ($pathPart == 'Section') ? 'Category' : $pathPart;
						eval( 'if( !isset( $meta->'.$path.' ) ) {
									$meta->'.$path.' = new $class();
								}');
						$path .= '->';
					}
					eval('$placeholder = &$meta->'.$propPath.';'); // Creating reference to array element
					/** @noinspection PhpUnusedLocalVariableInspection */
					$placeholder = $row[$propName]; // Filling the reference and thereby the array element
				}
			}
			// Complete the MetaData tree
			$meta->ContentMetaData->Keywords = $keywords;
			$meta->ContentMetaData->PlainContent = strval($row['PlainContent']); // Oracle fix: strval() converts NULL to empty string
			$meta->WorkflowMetaData->State->Type = $meta->BasicMetaData->Type;
			if( is_null($meta->WorkflowMetaData->LockedBy) ) { // null means: no idea
				$meta->WorkflowMetaData->LockedBy = ''; // empty means: not locked
			}
			if( is_null($meta->ContentMetaData->Description) ) {
				$meta->ContentMetaData->Description = '';
			}
			$meta->ExtraMetaData = $extramd;

			// Make sure that the Orientation is in range [1...8] or null.
			self::validateAndRepairOrientation( $meta );

			// Derive the Dimensions property value from Width, Height and Orientation.
			self::determineDimensions( $meta );
		}
		return $meta;
	}

	/**
	 * Creates comma separated strings of all issues and editions of a given list of targets.
	 * This is done for ids and names, which is typically used for logging and broadcasting
	 * to inform users and admins about the current targets of the object that undertakes action.
	 *
	 * @param array  $targets      List of Target objects
	 * @param string $issueIds     Comma separated list of issue ids
	 * @param string $issueNames   Comma separated list of issue names
	 * @param string $editionIds   Comma separated list of edition ids
	 * @param string $editionNames Comma separated list of edition names
	 */
	static private function listIssuesEditions( $targets, &$issueIds, &$issueNames, &$editionIds, &$editionNames )
	{
		$arrIssueIds = array();
		$arrIssueNames = array();
		$arrEditionIds = array();
		$arrEditionNames = array();
		if( !empty($targets) ) foreach( $targets as $target ) {
			if( !empty($target->Issue) ) {
				$arrIssueIds[] = $target->Issue->Id;
				$arrIssueNames[] = $target->Issue->Name;
			}
			if( !empty($target->Editions) ) foreach( $target->Editions as $edition ) {
				$arrEditionIds[] = $edition->Id;
				$arrEditionNames[] = $edition->Name;
			}
		}
		$issueIds     = join(',', $arrIssueIds);
		$issueNames   = join(',', $arrIssueNames);
		$editionIds   = join(',', $arrEditionIds);
		$editionNames = join(',', $arrEditionNames);
	}

	/**
	 * Create a Dossier object from an other object.
	 * Personal State and autonaming are not supported.
	 *
	 * @param Object $object
	 * @return Object
	 */
	static protected function createDossierFromObject($object)
	{
		$dossierObject = null;
		// only support one target
		if (isset($object->Targets[0]->Issue) && $object->Targets[0]->Issue->OverrulePublication){
			// overrule brand
			// $states are ordered on code ( = order in UI)
			$states = DBWorkflow::listStatesCached($object->MetaData->BasicMetaData->Publication->Id, $object->Targets[0]->Issue->Id, 0, 'Dossier');
			// we get all publication issues, so select the first correct one
			foreach ($states as $state){
				if ($state['issue'] ==  $object->Targets[0]->Issue->Id){
					break;
				}
			}
		} else {
			// $states are ordered on code ( = order in UI)
			$states = DBWorkflow::listStatesCached($object->MetaData->BasicMetaData->Publication->Id, 0, 0, 'Dossier');
			// First dossier state is default state (BZ#14644)
			$state = $states[0];
		}

		/** @noinspection PhpUndefinedVariableInspection */
		if ($state){
			// BZ #29555: A newly created Dossier for a placed Article on a Layout no longer contains a target for the
			// Issue, a change in SmartConnection to add such a Target as part of the Relations was added, this Target
			// needs to be taken for the newly created Object (if it is an Article that is placed) as the Object Target
			// if it does not already exist. The relational target then needs to be cleared. This ensures that a Target
			// is created for the newly created Dossier. This is a hackish solution but the only way to get the info in
			// there at this moment. The same is true for Spreadsheets and Images.
			if ($object->Relations)	foreach ($object->Relations as $relation) {
				$objType = $object->MetaData->BasicMetaData->Type;
				if ( $relation->Parent === '-1' &&
					( $objType === 'Article' || $objType === 'Spreadsheet' || $objType == 'Image' )) {
					// Safety: check if the issue and channel are already being set in the Object Targets.
					$object->Targets = $relation->Targets;
					$add = true;
					if ($object->Targets) foreach ($object->Targets as $target){
						if ($target->Issue->Id === $relation->Targets[0]->Issue->Id) {
							$add = false;
						}
					}

					if ($add) {
						$object->Targets = $relation->Targets;
					}
					unset($relation->Targets); // Clear Relational Targets as they are no longer needed for this Object type.
				}
			}

			$basicMD = new BasicMetaData(null, '', $object->MetaData->BasicMetaData->Name, 'Dossier', $object->MetaData->BasicMetaData->Publication, $object->MetaData->BasicMetaData->Category, '');
			$workflowMD = new WorkflowMetaData();
			// the first state should always be default state
			$workflowMD->State = new State($state['id']);
			$workflowMD->RouteTo = $object->MetaData->WorkflowMetaData->RouteTo; // BZ#17368
			//TODO extra metadata
			$md = new MetaData();
			$md->BasicMetaData = $basicMD;
			$md->WorkflowMetaData = $workflowMD;

			$dossierObject = new Object($md, null, null, null, null, null, $object->Targets);
			LogHandler::Log(__CLASS__, 'DEBUG', 'Create new dossier');
			// Watchout, recursion!
			// call service layer
			require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
			$request = new WflCreateObjectsRequest(BizSession::getTicket(), false, array($dossierObject), null, null);
			$service = new WflCreateObjectsService();
			//TODO Should we catch exceptions?
			$response = $service->execute($request);
			if (isset($response->Objects[0])){
				$dossierObject = $response->Objects[0];
			}
			LogHandler::Log(__CLASS__, 'DEBUG', 'Created new dossier with id: ' . $dossierObject->MetaData->BasicMetaData->ID);
		} else {
			LogHandler::Log(__CLASS__, 'ERROR', 'Could not find first dossier state!');
			// No BizException, all other things should be created
		}

		return $dossierObject;
	}

	/**
	 * Checks if user has read and edit (only when locking an object) rights
	 * on an object.
	 *
	 * @param boolean $lock Object is locked
	 * @param string $user Short User Name
	 * @param array $objectProps Route User(group)
	 * @throws BizException on authorization error.
	 */
	static private function checkAccessRights( $lock, $user, array $objectProps )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';

		// For personal state of this user we don't have to check authorization
		// BZ#5754 neither if routeto=user, so removed the personal condition
		$routetouser = false;
		$routeto = $objectProps['RouteTo'];
		$usergroups = DBUser::getMemberships(BizSession::getUserInfo('id'));
		foreach ($usergroups as $usergroup) {
			$groupname = $usergroup['name'];
			if (strtolower(trim($routeto)) == strtolower(trim($groupname))) {
				$routetouser = true;
				break;
			}
		}
		//BZ#6468 user needs 'E'-access to lock
		if ($lock === true) {
			// Do not check authorization in case of 'Personal' state and user is 'route to
			// user' or in the 'route to group'.
			if (! (($objectProps['StateId'] == - 1) && ($routeto == $user || $routetouser))) {
				//If user has not 'Open for Edit' rights, they could still have 'Open for Edit (Unplaced)' rights.
				if( !BizAccess::checkRightsForObjectProps( $user, 'E', BizAccess::DONT_THROW_ON_DENIED, $objectProps ) ) {

					// Check if the user has access to unplaced files only. If not, it means the user does not have any edit rights.
					if( BizAccess::checkRightsForObjectProps( $user, 'O', BizAccess::THROW_ON_DENIED, $objectProps )
						 && BizRelation::hasRelationOfType( $objectProps['ID'], 'Placed', 'parents' ) ) {
						//If object to be accessed is placed, throw error since user has only access to unplaced objects.
						throw new BizException( 'ERR_AUTHORIZATION', 'Client',
							'User does not have sufficient rights to edit object ('.$objectProps['ID'].').');
					}
				}
			}
		} else if ($routeto == $user || $routetouser) {
			;
		} else {
			BizAccess::checkRightsForObjectProps( $user, 'R', BizAccess::THROW_ON_DENIED, $objectProps );
		}
	}

	/**
	 * Returns all issues (relational and object) of the object needed for deadline calculation.
	 * First look if an object has an object-target (always the case for layout/dossier).
	 * If the object is an image or article and has no object-target the issues of relational-targets
	 * are returned.
	 * Duplicate issues are removed.
	 * @param Object $object
	 * @return array with the unique issues of the object to calculate the deadline.
	 */
	static private function getTargetIssuesForDeadline(/** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizDeadlines.class.php';

		$type = $object->MetaData->BasicMetaData->Type;
		$issueIds = BizTarget::getIssueIds($object->Targets); // Object-target issues

		if ( !$issueIds && ( BizDeadlines::canInheritParentDeadline( $type) )) { //BZ#21218
			if ( $object->Relations) foreach ($object->Relations as $relation ) {
				$issueIds = array_merge( $issueIds, BizTarget::getIssueIds($relation->Targets )); // Relational-target issues
			}
		}

		return array_unique( $issueIds ); // Remove double entries
	}


	/**
	 * To be picked up by the server job (to run in the background).
	 * This function updates the object (basically parent of child objects),
	 * the updates include:
	 * 	1) Update modifier/modified of parent object.
	 * 	2) Update the indexing.
	 * 	3) Do a broadcast after the above updates.
	 *
	 * @param ServerJob $job
	 * @return ServerJobStatus  $jobStatusId
	 */
	public static function updateParentObject( $job )
	{
		require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		require_once BASEDIR . '/server/dbclasses/DBDeletedObject.class.php';
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		require_once BASEDIR. '/server/smartevent.php';

		// First find the object's area (where the object is residing)
		$jobData = $job->JobData;
		$objId = $jobData['objectid'];
		$area = 'Workflow'; // default area is Workflow
		$objName = DBObject::getObjectName($objId);
		$table = 'objects';
		if( !$objName ){ // objectName not found in Workflow area
			$objName = DBDeletedObject::getObjectName($objId); // so find in Trash area
			$table = 'deletedobjects';
			$area = 'Trash';
		}

		try{
			if( $objName ){ // objectName found indicates this object still exists in the system.
				$jobActinguser = $job->ActingUser; // user shortname
				$userfull = BizUser::resolveFullUserName($jobActinguser);

				// Retrieve the object from workflow / Trash depending on where this object resides.
				$obj = self::getObject( $objId, $jobActinguser /*user*/, false /* lock */, 'none', array('MetaData','Relations') /* requestInfo */,
					null /* haveVersion */, false /* checkRights */, array($area));

				// modifier/modified or deletor/deleted values for parents' modifier/modified
				$where = " `id` = '" . $objId. "'";
				$fieldnames = ( $area === 'Workflow') ? array('modifier', 'modified') : array('deletor','deleted');
				$row = DBBase::getRow( $table, $where,  $fieldnames);

				// initialization
				$updatedParents = array();
				$parentObj =null;
				$serviceName = $job->ServiceName;
				$jobContext = $job->Context;

				/******
				 * determine parents modifier/modified values
				 * incase of Restore,Create/Update/Delete objectRelations took place, we will get the current timestamp and
				 * job's active user to fill in the parent's modifier/modified fields, this is due to for these few services, the child
				 * never gets updated on modifier/modified, so useless to get from the child.
				 *  otherwise we will get it from the child's modifier/modified Or deletor/deleted values.
				 *******/
				$modifierField = ( $area === 'Workflow') ? $row['modifier'] : $row['deletor'];
				$modifiedField = ( $area === 'Workflow') ? $row['modified'] : $row['deleted'];
				$modified = ( $serviceName == 'WflRestoreObjects' || $serviceName == 'WflCreateObjectRelations' ||
					$serviceName == 'WflDeleteObjectRelations' || $serviceName == 'WflUpdateObjectRelations' ) ?
					$job->QueueTime : $modifiedField;
				$modifier = ( $serviceName == 'WflRestoreObjects' || $serviceName == 'WflCreateObjectRelations' ||
					$serviceName == 'WflDeleteObjectRelations' || $serviceName == 'WflUpdateObjectRelations' ) ?
					$jobActinguser : $modifierField;

				// update parent directly
				if( $jobContext == 'parent' ){
					if ( self::updateParentModifierAndModified( $objId /* parentId */, null /* childId */, null /* relation */, $modifier, $modified) ){
						if( !isset($updatedParents[$objId]) ){
							$updatedParents[$objId] = true;
						}
					}
				}

				// need to find the parent(s) of the child and update the parent(s)
				if( $jobContext == 'child' ){
					$objBasicMD = $obj->MetaData->BasicMetaData;
					if( $obj->Relations) foreach ($obj->Relations as $relation){
						if ( self::updateParentModifierAndModified( null /* parentId */, $objBasicMD->ID /* childId */, $relation, $modifier, $modified )){
							if( !isset($updatedParents[$relation->Parent]) ){
								$updatedParents[$relation->Parent] = true;
							}
						}
					}
				}

				// We have finished updating the two fields on the object(parent) itselfs, now we need to see whether there's any parent(s) for this object
				// that need to be re-indexed and broadcasted.
				if( $updatedParents) foreach(  array_keys($updatedParents) as $updatedParent ) {
					// parent could be in workflow or trash.
					$parentArea = ( !is_null(DBObject::getObjectName( $updatedParent )) ) ? 'Workflow' : 'Trash';
					$parentObj = self::getObject( $updatedParent, $jobActinguser, false /* lock */, 'none', null /* requestInfo */,
						null /* haveVersion */, false /* checkRights */, array($parentArea));

					// Re-indexing for parent
					BizSearch::updateObjects( array( $parentObj ) , true, array($area) );

					// Broadcasting for parent
					new smartevent_setobjectpropertiesEx( $job->TicketSeal, $userfull, $parentObj, $parentObj->MetaData->WorkflowMetaData->RouteTo );

					// Notify event plugins
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					BizEnterpriseEvent::createObjectEvent( $parentObj->MetaData->BasicMetaData->ID, 'update' );
				}
			}
			// Tell caller we have completed the job
			// (In case there's no object(parent) to get updated, we assume the parent has been purged by the time bgJob is executed and will not raise any error)
			$jobStatusId = ServerJobStatus::COMPLETED;
		}catch( BizException $e ){
			// Tell caller the job has failed but there's no harm and will not try again.
			$jobStatusId = ServerJobStatus::WARNING;
		}

		return $jobStatusId;
	}

	/**
	 * @since v8.0.0 (BZ#12742)
	 * This function will be called in background job to update the parents (Dossier/Dossier Template/Task)
	 * 'modifier' and 'modified' field.
	 *
	 * When an object / objectRelations go through any of the 7 services mentioned below:
	 * Create/Save/Delete/Restore Objects and Create/Update/Delete objectRelations services,
	 * We need to check whether the object with Relation has the following:
	 * 	- Having 'Contained' relation.
	 * 	- Object is being the child in its Relation. ($relation->child)
	 * If it fulfills the above, the parent's 'modifier' and 'modified' field should be updated.
	 * However, updating the parents during workflow service execution(foreground), it takes up a lot of resources
	 * hence resulting in slow performance; therefore, we need to bring this update parents job into
	 * background job (create this background job via ServerJob connector).
	 *
	 * When the ServerJob picks up this job to update the parents in the background, either of these
	 * two sets are passed in:
	 * 1) $parentId, $modifier, $modified OR
	 * 2) $childId, $relation, $modifier, $modified
	 *
	 * $parentId: (Choice 1)
	 * $parentId is thte DB id of the parent object.
	 * When parentId is passed, $childId and $relation is ignored.
	 * This happened when child is alerady detached from the parent by the time the background job
	 * executes. For an example, for deleteObject relation, when the child is removed from a Dossier(parent),
	 * the Dossier's fields need to be updated but we cannot rely on the child to search for its parents as
	 * child will be detached from the parents by then, so we pass in the parentId directly.
	 *
	 * $childId & $relation: (Choice 2)
	 * $childId is the DB id of the object's $relation being passed over.
	 * For this case, the object should always be the child, and we will get the parents
	 * that has 'Contained' relationship with this child to do the updates.
	 * When this $childId parameter is passed, $relation should be filled in as well.
	 *
	 * We don't use Choice 1 for all cases as passing in all the parents in serverJob is expensive as there could be
	 * many parents at any one time and thus causing many serverjob entries created.
	 *
	 *
	 * @param int $parentId Refer to above.
	 * @param int $childId  Refer to above.
	 * @param Relation $relation  The object relation
	 * @param string $modifier User ID (short name).
	 * @param string $modified Time the object is modified in Timestamp format.
	 * @return bool
	 */
	private static function updateParentModifierAndModified( $parentId=null, $childId=null, $relation=null, $modifier, $modified )
	{
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';

		$isChildRel = isset($relation) && ($relation->Type == 'Contained' || $relation->Type == 'DeletedContained')  && $childId == $relation->Child;
		if( $parentId || $isChildRel ) {

			if( isset($relation) && !$parentId ){ // only applicable when childId is passed in.
				$parentId = $relation->Parent;
			}
			$childMsg = (!is_null($childId)) ? $childId : 'NA';
			$logMessage = 'Parent Id::' . $parentId . PHP_EOL .
				'Child Id::'. $childMsg . PHP_EOL .
				'Modified::'. $modified . PHP_EOL .
				'Modifier::' . $modifier. PHP_EOL;

			try{
				DBObject::updateObjectModifierAndModified( $parentId, $modifier, $modified );
				LogHandler::Log('BizObject','DEBUG','Updating parent modifier and modified fields.' . PHP_EOL . $logMessage );
			}catch (BizException $e){
				LogHandler::Log('BizObject','ERROR',__METHOD__. ':Could not update parents modifier and modified field.' . PHP_EOL . $logMessage);
				return false;
			}
		}
		return true;
	}

	/**
	 * Determines if an Object is a Layout and if it has an alternate Layout (both Orientations)
	 *
	 * @param int $objectId The id of the Layout object to be retrieved.
	 * @param string $user The user to use to get the object.
	 * @return bool Whether or not the Layout has Portrait and Landscape Orientations.
	 */
	public static function isAlternateLayout($objectId, $user) {

		// Get the Object pages, check that its ObjectType is Layout.
		try {
			require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
			$objectsPages = BizPage::GetPages( BizSession::getTicket(), $user, null, array( $objectId ), null, true,
				null, null, null );
			// Get the first object
			$objPage = isset( $objectsPages[0] ) ? $objectsPages[0] : null;
		} catch (BizException $e) {
			LogHandler::Log('BizObject', 'ERROR', 'Could not retrieve a Layout with ID: ' . $objectId);
			return false;
		}

		if (is_null($objPage) || !$objPage->Pages || !isset($objPage->MetaData) ||
			!isset($objPage->MetaData->BasicMetaData) || $objPage->MetaData->BasicMetaData->Type != 'Layout' ) {
			return false;
		}

		$hasLandscapeOrientation = false;
		$hasPortraitOrientation = false;

		/** @var Page $page */
		foreach ($objPage->Pages as $page) {
			if ($page->Orientation == 'landscape') {
				$hasLandscapeOrientation = true;
			}

			if ($page->Orientation == 'portrait') {
				$hasPortraitOrientation = true;
			}
		}

		// Not both Horizontal and Vertical, return false.
		if (!$hasLandscapeOrientation && !$hasPortraitOrientation) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if the object type given can contain pages.
	 *
	 * @param string $objectType
	 * @return bool True when the object type can contain pages; False otherwise.
	 */
	public static function canObjectContainPages( $objectType )
	{
		return self:: isObjectAnyKindOfLayout( $objectType );
	}

	/**
	 * Checks if the object type given is in the 'category' of layout,
	 * which includes 'Layout', 'LayoutTemplate', 'LayoutModule', 'LayoutModuleTemplate'.
	 *
	 * @param string $objectType
	 * @return bool
	 */
	private static function isObjectAnyKindOfLayout( $objectType )
	{
		return
			$objectType == 'Layout' ||
			$objectType == 'LayoutTemplate' ||
			$objectType == 'LayoutModule' ||
			$objectType == 'LayoutModuleTemplate';
	}

	/**
	 * Checks if the Object exists in an area.
	 *
	 * Area can be 'Workflow' or 'Trash'.
	 *
	 * Returns true if there is an object by the specified ID in the smart_objects table (area=Workflow) or in the
	 * smart_deletedobjects table (area=Trash), and false otherwise.
	 *
	 * @static
	 * @param integer $id The Object Id for which to check if it exists.
	 * @param string $area The Object table to search in. 'Workflow' or 'Trash'.
	 * @return bool Whether or not an Object for the specified id exists in the object table.
	 */
	public static function objectExists($id, $area)
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$exists = DBObject::objectExists($id, $area);
		return $exists;
	}

	/**
	 * Returns the object type for the object with the given id.
	 *
	 * Area can be 'Workflow' or 'Trash'.
	 *
	 * @static
	 * @param integer $id
	 * @param string $area The Object table to search in. 'Workflow' or 'Trash'.
	 * @return string|null Returns the type when found, otherwise null
	 */
	public static function getObjectType($id, $area)
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		return DBObject::getObjectType($id, $area);
	}

	/**
	 * Handles all the child objects in the Relations in which the parent is fixed in every Relation.
	 *
	 * The relations belongs to the source Object where in each relation, the Parent is the source while the
	 * Child could be any object type that has relation with the source.
	 * Each and every relation is copied over to the new copied Object (target).
	 * For every relation, the child object's type is determined and is handled accordingly:
	 * - In the case of article, it is checked if it needs to include article relations/placements.
	 * - Each child object is referenced to the new copied object (if applicable).
	 *      - When there's relational target from the source, it gets updated in the newly copied object.
	 * - Each child object's placement is copied to the new copied object (if applicable).
	 * - Child object that cannot be referenced (like PublishForm),
	 * for those object, a new one is created (deep copy) instead of referenced.
	 *
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @param array $srcRelations All child relations of the source parent.
	 * @param int $trgtParentState State of the copied object (target).
	 * @param string $user User performing the copy action.
	 * @param int $newCopiedParentId Object Id of the new copied object (target).
	 * @param Target[] $trgtParentTargets The targets of the copied object (target).
	 * @param MetaData $meta
	 * @param array $newLabels
	 * @throws BizException
	 */
	private static function handleChildRelations(
		$dbDriver, $srcRelations, $trgtParentState, $user, $newCopiedParentId,  $trgtParentTargets, $meta, $newLabels )
	{
		if ( !$srcRelations ) { return; }
		foreach( $srcRelations as $srcRelation) {
			if ( self::childRelationCanBeCopied( $srcRelation, $trgtParentState, $user, $dbDriver  ) ) {
				require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
				$objRelId = DBObjectRelation::createObjectRelation(
					$newCopiedParentId, $srcRelation['child'], $srcRelation['type'], $srcRelation['subid'],
					$srcRelation['pagerange'], $srcRelation['rating'], $srcRelation['parenttype'] );
				if ( is_null( $objRelId ) ) { throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() ); }
				if ( $trgtParentTargets ) {
					BizTarget::createObjectRelationTargets( $user, $objRelId, $trgtParentTargets );
				}
				if ( !DBPlacements::copyPlacements( $srcRelation['parent'], $srcRelation['child'], $newCopiedParentId ) ) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
				require_once BASEDIR . '/server/bizclasses/BizEnterpriseEvent.class.php';
				BizEnterpriseEvent::createObjectEvent( $srcRelation['child'], 'update' );
			}
			if ( self::needsDeepCopy( $srcRelation ) &&
				self::targetsCanBeCopied( $srcRelation, $trgtParentTargets, $user ) ) {
				self::copyNewForm( $srcRelation['child'], $user, $meta, $newCopiedParentId, $dbDriver );
			}
			//copy the labels when needed
			if ( $newLabels ) {
				self::copyObjectLabelsForRelation( $srcRelation['parent'], $srcRelation['child'], $newLabels );
			}
		}
	}

	/**
	 * If an object (with children) is copied, the new object can either have a reference to these children or the
	 * children must be copied themselves. This function checks if a reference can be created or if the child has to be
	 * copied.
	 *
	 * @param array $relation DB-row of the relation between the source parent and the child.
	 * @return bool treu if a copy is needed else false.
	 */
	static private function needsDeepCopy( $relation)
	{
		return (($relation['parenttype'] == 'Dossier' || $relation['parenttype'] == 'DossierTemplate') &&
			$relation['subid'] == 'PublishForm' && $relation['type'] == 'Contained' );
	}

	/**
	 * Some children cannot be referenced (such as PublishForm), therefore a deep-copy of the source-child
	 * (PublishForm) is done.
	 * A copy is made if the relational target of the original parent/child will also be a target for
	 * the new parent/child combination. So if a new dossier is instantiated from a template and a target is re-
	 * moved (to which a publish form is related) the new dossier will not contain a publish form. BZ#35340.
	 *
	 * @param $relation DB-row of the source object relation.
	 * @param Target[] $trgtParentTargets Targets of the copy.
	 * @param string $user User performing the copy action.
	 * @return bool true if targets match else false.
	 */
	static private function targetsCanBeCopied( $relation, $trgtParentTargets, $user )
	{
		$objTargetsRemoved = array();
		$objTargetsAdded = array();
		self::getObjectTargetsModified(
			$user, $relation['parent'], $trgtParentTargets, $objTargetsRemoved, $objTargetsAdded );
		if ( !$objTargetsRemoved ) { return true;}
		require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
		$childRelTargets = BizRelation::getObjectRelationTargets( $relation['parent'], $relation['child'], $relation['type'] );
		// $childRelTargets contains the the original source parent/source child relational targets.
		require_once BASEDIR . '/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		// Object properties that will not be compared
		foreach ( $objTargetsRemoved as $objTargetRemoved ) {
			if ( $childRelTargets ) foreach ( $childRelTargets as $childRelTarget ) {
				$phpCompare->initCompare( array(
					'Target->PublishedDate' => true,
					'Target->PublishedVersion' => true,
					'Target->ExternalId' => true,
				) );
				if ( $phpCompare->compareTwoObjects( $childRelTarget, $objTargetRemoved ) ) {
					return  false;
					break 2;
				}
			}
		}
		return true;
	}

	/**
	 * Checks if a parent-child relation for the copy can be created by making a new reference from the copy to
	 * the child.
	 * @param array $relation DB-row of the source object-relation.
	 * @param int $parentState Stat of the copy (target).
	 * @param string $user User performing the copy action.
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @return bool reference is allowed (true) else false.
	 * @throws BizException
	 */
	static private function childRelationCanBeCopied( $relation, $parentState, $user, $dbDriver )
	{
		$copyAllowed = false;
		if ( self::isPlacedArticle( $relation ) ) {
			if ( $parentState == -1 ) { return true; } // Parent is in personal state. No need to check rights.
			$copyAllowed = self::checkAccessOnArticle( $relation['child'], $user, $dbDriver);
		}
		if ( !$copyAllowed ) {
			$copyAllowed = self::containedNoPublishForm( $relation );
		}
		if ( !$copyAllowed ) {
			$allowForTypes = array( 'Image','Spreadsheet', 'LayoutModule' );
			$copyAllowed = in_array( $relation['subid'], $allowForTypes);
		}
		return $copyAllowed;
	}

	/**
	 * Checks if the relation is about a contained Publish Form.
	 *
	 * @param array $relation DB-row of the source object-relation.
	 * @return bool true if the contained relation is not about a Publish Form, else false.
	 */
	static private function containedNoPublishForm ( $relation )
	{
		return $relation['type'] == 'Contained' && $relation['subid'] != 'PublishForm';
	}

	/**
	 * Checks if the relation is about a placed Article.
	 *
	 * @param array $relation  DB-row of the source object-relation.
	 * @return bool Is a placed article relation then true, else false.
	 */
	static private function isPlacedArticle( $relation )
	{
		return  ( $relation['subid'] == 'Article' && $relation['type'] == 'Placed' );
	}

	/**
	 * User should have proper access rights on the article if a new relation is created. Proper means that the user
	 * must have the 'ALLOW MULTIPLE ARTICLE PLACEMENTS' right as creating a second relation means that the article
	 * is placed multiple times.
	 *
	 * @param $articleId Object Id of the article.
	 * @param string $user User performing the copy action.
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @return bool User has right then true, else false.
	 * @throws BizException
	 */
	static private function checkAccessOnArticle( $articleId, $user, $dbDriver )
	{
		$sth = DBObject::getObject( $articleId );
		if ( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$childRow = $dbDriver->fetch( $sth );
		require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
		$childTargets = DBTarget::getTargetsByObjectId( $articleId );
		$childIssueId = $childTargets && count( $childTargets ) ? $childTargets[0]->Issue->Id : 0;
		require_once BASEDIR . '/server/bizclasses/BizAccess.class.php';
		$allowed = BizAccess::checkRightsForObjectRow(
			$user, 'M', BizAccess::DONT_THROW_ON_DENIED, $childRow, $childIssueId );

		return $allowed;
	}

	/**
	 * Copy the labels for the object. All the labels set for the relation are copied
	 *
	 * @param integer $parentId
	 * @param integer $childId
	 * @param ObjectLabel[] $labels
	 */
	private static function copyObjectLabelsForRelation( $parentId, $childId, $labels)
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectLabels.class.php';
		$oldLabels = DBObjectLabels::getLabelsForRelation( $parentId, $childId );
		foreach ( $oldLabels  as $oldLabel ) {
			if ( isset($labels[$oldLabel->Id]) ) {
				$newLabel = $labels[$oldLabel->Id];
				DBObjectLabels::addLabel( $childId, $newLabel );
			}
		}
	}

	/**
	 * Handles all the parent objects in the Relations in which the child is fixed in every Relation.
	 *
	 * The relations belongs to the source Object where in each relation, the Child is the source while the
	 * Parent could be any object type that has relation with the source.
	 * Each and every relation is copied over to the new copied Object.
	 * This function is to update the parent relations of the newly copied PublishForm. It can only be
	 * called after copy of a PublishForm is completed, this is because during the Copy operation, lack
	 * of data makes it not possible to update during the Copy operation.
	 *
	 * @param array $relations
	 * @param int $newCopiedParent
	 * @param int  $newCopiedChild
	 * @param string $newCopiedChildType
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @param array $objTargetsAdded
	 * @param array $objTargetsRemoved
	 * @param string $user
	 * @throws BizException
	 */
	public static function handleParentRelations( $relations, $newCopiedParent, $newCopiedChild, $newCopiedChildType,
	                                              $dbDriver, $objTargetsAdded, $objTargetsRemoved, $user  )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		if( $relations ) foreach( $relations as $relation ) {
			// Handle Object relations.
			$objRelId = null;
			if( $relation['type'] == 'InstanceOf' ) {
				$objRelId = DBObjectRelation::createObjectRelation( $relation['parent'], $newCopiedChild, $relation['type'],
					$newCopiedChildType, $relation['pagerange'], $relation['rating'], $relation['parenttype'] );
				if( is_null($objRelId) ) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
			} else if( $relation['type'] == 'Contained' && $relation['subid'] == 'PublishForm' ) {
				// will not do anything here!
				$objRelId = DBObjectRelation::createObjectRelation( $newCopiedParent, $newCopiedChild, $relation['type'],
					$newCopiedChildType, $relation['pagerange'], $relation['rating'], 'Dossier' );
				if( is_null($objRelId) ) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
			}

			// Handle Object relation targets
			if( $relation['type'] == 'Contained' && $relation['subid'] == 'PublishForm' ) {
				self::updateRelationTargetsForCopiedObj( $relation['parent'], $relation['id'], $objTargetsAdded,
					$objTargetsRemoved, $user, $objRelId, true );
			}
		}
	}

	/**
	 * Update relational Targets for the newly copied object.
	 *
	 * @param int $parentId
	 * @param int $existingRelationId
	 * @param array $objTargetsAdded
	 * @param array $objTargetsRemoved
	 * @param string $user
	 * @param int $objRelId
	 * @param bool $clearPublishedData
	 */
	private static function updateRelationTargetsForCopiedObj( $parentId, $existingRelationId, $objTargetsAdded,
	                                                           $objTargetsRemoved, $user, $objRelId, $clearPublishedData = false )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$parObjType = DBObject::getObjectType( $parentId );
		if( $parObjType == 'DossierTemplate' || $parObjType == 'Dossier' ) {
			// >>> BZ#20917 When obj target is removed, repair with rel target with one that is added by user
			require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
			$orgRelTargets = DBTarget::getTargetsbyObjectrelationId( $existingRelationId );
			$newRelTargets = array();

			if( $orgRelTargets ) foreach( $orgRelTargets as $orgRelTarget ) {
				if( isset( $objTargetsRemoved[$orgRelTarget->Issue->Id] ) ) {
					if( count( $objTargetsAdded ) > 0 ) {
						$newRelTargets[] = reset($objTargetsAdded); // just take first (to keep it simple)
					} // else: forget the rel target, since we have nothing to repair it
				} else {
					$newRelTargets[] = $orgRelTarget;
				}
			} // <<<
			if( $newRelTargets ) {
				if( $clearPublishedData ) foreach( $newRelTargets as &$newRelTarget ) {
					$newRelTarget->PublishedDate = null;
					$newRelTarget->PublishedVersion = null;
					$newRelTarget->ExternalId = null;
				}
				BizTarget::createObjectRelationTargets( $user, $objRelId, $newRelTargets );
			}
		}
	}

	/**
	 * Instead of making a reference of the child object inside the dossier,
	 * the child is deep-copied. This is needed to avoid the two dossiers( original and the copied ones)
	 * to share the same child.
	 *
	 * @param int $srcChildId
	 * @param string $user
	 * @param MetaData $meta
	 * @param int $newCopiedDosserId
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @throws BizException
	 */
	private static function copyNewForm( $srcChildId, $user, $meta, $newCopiedDosserId, $dbDriver )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizObjectComposer.class.php';
		$srcFormObj = self::getObject( $srcChildId, $user, false, 'none', array( 'Relations', 'Targets' ) );
		$srcFormId = $srcFormObj->MetaData->BasicMetaData->ID;
		$srcFormObj->MetaData->BasicMetaData->Name = $meta->BasicMetaData->Name;
		$newForm = self::copyObject( $srcChildId, $srcFormObj->MetaData, $user, $srcFormObj->Targets, null, null );

		$formTargetsRemoved = array();
		$formTargetsAdded = array();
		self::getObjectTargetsModified( $user, $srcFormId, $srcFormObj->Targets, $formTargetsRemoved, $formTargetsAdded );

		$parentRelations = DBObjectRelation::getObjectRelations( $srcFormId, 'parents' );
		self::handleParentRelations( $parentRelations, $newCopiedDosserId, $newForm->MetaData->BasicMetaData->ID, 'PublishForm',
			$dbDriver, $formTargetsAdded, $formTargetsRemoved, $user );
	}

	/**
	 * To determine which object targets have been removed and which have been added.
	 *
	 * @param string $user
	 * @param int $srcid
	 * @param array $targets
	 * @param array &$objTargetsRemoved Targets that have been removed which will be filled in by the function.
	 * @param array &$objTargetsAdded Targets that have been added which will be filled in by the function.
	 */
	private static function getObjectTargetsModified( $user, $srcid, $targets, &$objTargetsRemoved, &$objTargetsAdded )
	{
		// >>> BZ#20917 Determine which object targets are removed and which are added

		$orgObjTargets = BizTarget::getTargets( $user, $srcid );
		foreach( $orgObjTargets as $orgObjTarget ) {
			$targetFound = false;
			foreach( $targets as $newObjTarget ) {
				if( $orgObjTarget->Issue->Id == $newObjTarget->Issue->Id ) {
					$targetFound = true;
					break;
				}
			}
			if( !$targetFound ) {
				$objTargetsRemoved[$orgObjTarget->Issue->Id] = $orgObjTarget;
			}
		}

		foreach( $targets as $newObjTarget ) {
			$targetFound = false;
			foreach( $orgObjTargets as $orgObjTarget ) {
				if( $orgObjTarget->Issue->Id == $newObjTarget->Issue->Id ) {
					$targetFound = true;
					break;
				}
			}
			if( !$targetFound ) {
				$objTargetsAdded[$newObjTarget->Issue->Id] = $newObjTarget;
			}
		} // <<<
	}


	/**
	 * If the object is placed on the PublishForm, the PublishForm's version needs to be updated.
	 *
	 * The function finds out if the object being passed is being placed on any PublishForm.
	 * If it is, it will call modifyPublishFormOnSavingPlacements() to update the version, slugline
	 * and modified properties of its PublishForm.
	 *
	 * @param Object $childObj
	 */
	public static function updateVersionOfParentObject( $childObj )
	{
		if( $childObj->Relations ) foreach( $childObj->Relations as $relation ) {
			if( $relation->Child == $childObj->MetaData->BasicMetaData->ID &&
				$relation->Type == 'Placed' && $relation->ParentInfo->Type == 'PublishForm' ) {

				$publishFormId = $relation->ParentInfo->ID;
				$modified = $childObj->MetaData->WorkflowMetaData->Modified;
				$slugline = self::getSluglineForPublishFormId( intval( $publishFormId ));
				self::modifyPublishFormOnSavingPlacements( $publishFormId, $modified, $slugline );
			}
		}
	}

	/**
	 * Update PublishForm's properties when its placement object is being modified.
	 *
	 * The function first tries to get the SavePublishForm semaphore, when it fails to
	 * get one, it is assumed that the save PublishForm operation is being executed and therefore
	 * this function does not need to do take care of the save PublishForm operation anymore.
	 *
	 * When it manages to get the semaphore, the PublishForm's version, modified and slugline properties
	 * will be updated.
	 *
	 * @param int $publishFormId
	 * @param string $modified
	 * @param string $slugline
	 * @throws BizException
	 */
	private static function modifyPublishFormOnSavingPlacements( $publishFormId, $modified, $slugline )
	{
		try {
			// Creates semaphore
			$semaphoreId = null;
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			$bizSemaphore = new BizSemaphore();
			$semaphoreName = 'SavePublishForm_'.$publishFormId;
			$bizSemaphore->setLifeTime( 1 ); // 1 second.
			// Set a number of attempts, after roughly waiting for one minute, wait for 250 ms per interval.
			// Roughly try up to 3 seconds at a maximum before bailing out.
			$bizSemaphore->setAttempts(
				array( 1, 2, 5, 10, 15, 25, 50, 50, 125, 250, 250, 250, 250, 250, 250, 250, 250, 250, 250, 250 )
			);

			// createSemaphore( xxx, false): False: Do not want to see it as Error when semaphore cannot be created.
			// When semaphore cannot be created, it is assumed that the PublishForm is holding the semaphore and
			// therefore it's fine to let the parent(PublishForm) do the object saving than the child(placementObject of the Form)
			$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName, false );

			// Only do the fields update when semaphore was granted, otherwise the parent(PublishForm) will take care
			// of the updates by itself.
			if( $semaphoreId ) {
				// Update the Version
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';
				$publishFormRow = DBObject::getObjectRows( $publishFormId );
				$workflowMD = null;
				BizVersion::createVersionIfNeeded( $publishFormId, $publishFormRow, $publishFormRow, $workflowMD, true );

				// Update Database fields
				$version = $publishFormRow['version'];
				if( $version ) {
					require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
					$values = array();
					DBVersion::splitMajorMinorVersion( $version, $values ); // Fill up $values['majorversion'], $values['minorversion']
					$values['modified'] = $modified;
					$values['slugline'] = $slugline;

					$where = '`id` = ? ';
					$params = array( $publishFormId );
					DBObject::updateRow( 'objects', $values, $where, $params );

					// Update the search index, and surpress errors if any.
					require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
					require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
					try {
						$objectsToIndex = array();
						$user = BizSession::getShortUserName();
						$objectsToIndex[] = BizObject::getObject($publishFormId, $user, false, 'none',
							array('Targets','MetaData', 'Relations'), null, false, array('Workflow'));
						BizSearch::updateObjects( $objectsToIndex, true, array('Workflow') );
					} catch ( BizException $e ) {
						// suppress the errors.
					}
				}
			} else {
				// If we cannot get a lock, we should at least report the failure in the log, this will mean that
				// there wasn't a new version created, nor was the slugline updated.
				LogHandler::Log(__CLASS__, 'ERROR', 'Unable to lock/modify PublishForm: ' . $publishFormId);
			}
		} catch( BizException $e ) {
			/** @noinspection PhpUndefinedVariableInspection */
			if( $semaphoreId ) {
				BizSemaphore::releaseSemaphore( $semaphoreId );
			}
			throw $e;
		}
		if( $semaphoreId ) {
			BizSemaphore::releaseSemaphore( $semaphoreId );
		}
	}

	/**
	 * Retrieves an updated PublishForm slugline by PublishForm Object.
	 *
	 * @static
	 * @param Object $publishForm The PublishForm for which to get the updated Slugline.
	 * @return string The updated slugline.
	 */
	private static function getSluglineForPublishForm( $publishForm )
	{
		$slugline = '';
		if ( BizPublishForm::isPublishForm( $publishForm ) ) {
			$publishFormId = $publishForm->MetaData->BasicMetaData->ID;
			$publishFormRow = array();
			foreach ( $publishForm->MetaData->ExtraMetaData as $extraMetaData ) {
				$key =  $extraMetaData->Property;
				$publishFormRow[$key] = ( isset( $extraMetaData->Values ) && isset( $extraMetaData->Values[0] ) )
					? $extraMetaData->Values[0]
					: '';
			}
			$relations = $publishForm->Relations;

			$slugline = self::getPublishFormSlugLine( $publishFormId, $publishFormRow, $relations );
		}
		return $slugline;
	}

	/**
	 * Retrieves an updated PublishForm slugline by PublishForm ID.
	 *
	 * @param int $publishFormId The PublishFormId for which to get the updated Slugline.
	 * @return string The updated slugline.
	 */
	private static function getSluglineForPublishFormId( $publishFormId )
	{
		$slugline = '';
		if ( $publishFormId ) {
			$publishFormRow = DBObject::getObjectRows( $publishFormId, array('Workflow') );
			$relations = BizRelation::getObjectRelations( $publishFormId, false, false);
			$slugline = self::getPublishFormSlugLine( $publishFormId, $publishFormRow, $relations );
		}
		return $slugline;
	}

	/**
	 * Retrieves an updated slugline before a PublishForm is to be saved.
	 *
	 * In case of a PublishForm, the slugline field should be updated with the content of the PublishForm, where the
	 * string fields, multiline fields and the article components should be used to compose the text.
	 *
	 * For the $publishFormRow param, the array should contain at least all the custom properties of type string,
	 * multiline and articlecomponentselector of the PublishForm as these will be used in generating the slugline. The
	 * array itself consists of key value pairs, where the key is the value name, and the value is a string.
	 *
	 * @param integer $publishFormId The PublishFormId for which we wish to retrieve the slugline.
	 * @param array $publishFormRow A flattened object row, containing at least the custom properties.
	 * @param Relation[] $relations An array of relation objects belonging to the PublishForm.
	 * @return string The new slugline.
	 */
	private static function getPublishFormSlugLine( $publishFormId, $publishFormRow, $relations )
	{
		require_once BASEDIR.'/server/bizclasses/BizPublishForm.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBElement.class.php';
		require_once BASEDIR.'/server/utils/UtfString.class.php';

		$newSlugLine = '';

		if ( !is_null( $publishFormId ) ) {
			list( $publishSystem, $templateId ) = self::getPublishSystemAndTemplateId( $publishFormId );

			// Get the Properties.
			$properties = BizProperty::getProperties( 0, 'PublishForm', $publishSystem, $templateId, false);

			// Get the PropertyUsages.
			$documentId = DBObject::getColumnValueByName( $templateId, 'Workflow', 'documentid' );
			$wiwiwUsages = array(); // widget in widget in widget usages.
			$propertyUsages = BizProperty::getPropertyUsages(0,'PublishFormTemplate','SetPublishProperties',
				false, false, $documentId, $wiwiwUsages, false );

			if ( $propertyUsages ) foreach ( $propertyUsages as $propertyUsage ) {
				// We can only handle a max of 250 characters in the field.
				if (strlen($newSlugLine) >= 250 ) {
					break;
				}
				$newSlugLine = self::retrieveSluglineFromPropertyValue( $propertyUsage->Name, $properties,
					$newSlugLine, $relations, $publishFormRow );
			}

			if (strlen($newSlugLine) < 250 ) { // When slugline has not reach 250, continue searching in the fields from wiwiw.
				if( $wiwiwUsages ) foreach( $wiwiwUsages as /*$mainPropName => */$wiwUsages ) {
					foreach( $wiwUsages as /*$wiwPropName => */$wiwiwUsageArray ) {
						foreach( array_keys( $wiwiwUsageArray ) as $wiwiwPropName ) {
							if (strlen($newSlugLine) >= 250 ) {
								break;
							}
							$newSlugLine = self::retrieveSluglineFromPropertyValue( $wiwiwPropName, $properties,
								$newSlugLine, $relations, $publishFormRow );
						}
					}
				}
			}
		}

		$newSlugLine = UtfString::truncateMultiByteValue( $newSlugLine, 250 );
		return $newSlugLine;
	}

	/**
	 * Retrieve values from property's value to compose a slugline.
	 * Refer to getPublishFormSlugLine() header for more info.
	 *
	 * @param string $propName The property name where the slugline value will be retrieved from.
	 * @param array $properties List of Key-value entries (key=property name, value=propertyInfo) to get the property Type.
	 * @param string $newSlugLine To add (on) the values retrieved from the fields and to be returned end of the function.
	 * @param array $relations To get the placement element values to be added to the Slugline.
	 * @param array $publishFormRow A flattened object row, containing at least the custom properties.
	 * @return string The slugline from concatenation of several property's values.
	 */
	private static function retrieveSluglineFromPropertyValue( $propName, $properties, $newSlugLine, $relations, $publishFormRow )
	{
		$allowedFields = array('multiline', 'string', 'articlecomponentselector');
		// Check if we can determine the fields value, in which case if it is an allowed value we add it to
		// the new slugline.
		if ( array_key_exists( $propName, $properties )
			&& in_array( $properties[$propName]->Type, $allowedFields ) ) {
			if (strlen($newSlugLine) > 0) {
				$newSlugLine .= ' '; // Add a space prior to adding the new data.
			}

			// If we have an article component, proceed with retrieving the snippet for the used Element.
			if ( $properties[$propName]->Type == 'articlecomponentselector' ) {
				if ($relations) foreach ($relations as $relation) {
					if ( $relation->Type == 'Placed' ) {
						if ( $relation->Placements ) foreach ($relation->Placements as $placement ) {
							if ( $placement->FormWidgetId == $propName ) {
								$elements = DBElement::getByGuid( $placement->ElementID );
								if ( $elements ) foreach ( $elements as $element ) {
									$newSlugLine .= $element->Snippet;
								}
							}
						}
					}
				}
			} else {
				$key = $propName;

				// Get the actual field value from the extra metadata of the Object.
				if ( isset( $publishFormRow[$key] ) ) {
					$newSlugLine .= $publishFormRow[$key];
				}
			}
		}

		return $newSlugLine;
	}

	/**
	 * Updates contained Objects in case the provided Object is a Dossier.
	 *
	 * This function provides a hook for the SaveObjects to update underlying objects.
	 *
	 * For PublishForms, when the containing dossier name is changed, the PublishForm name is updated as well.
	 * The newly updated PublishForm is also set to be updated in the search indexes.
	 *
	 * @static
	 * @param Object $object The Object for which we want to update the children.
	 * @throws BizException Throws an exception if the contained Objects cannot be updated.
	 * @return void
	 */
	private static function updateContainedObjects( $object )
	{
		if ( !is_null( $object ) && $object->MetaData->BasicMetaData->Type == 'Dossier' ) {
			require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

			// Update contained PublishForms.
			$objectsToIndex = array();
			$objectsInTrashToIndex = array();
			$semaphoreId = null;
			$user = BizSession::getShortUserName();
			$dossierRelations = BizRelation::getObjectRelations( $object->MetaData->BasicMetaData->ID, false, false, null, true );
			if( $dossierRelations ) foreach( $dossierRelations as $dossierRelation ) {
				if ( ( $dossierRelation->Type == 'Contained' || $dossierRelation->Type == 'DeletedContained' ) &&
					$dossierRelation->ParentInfo->Name != $dossierRelation->ChildInfo->Name // The Form name and the Dossier name is still not the same
					&& $dossierRelation->ChildInfo->Type == 'PublishForm' ) {
					try {
						// Set Semaphore.
						$semaphoreName = 'SavePublishForm_' . $dossierRelation->Child;
						$bizSemaphore = new BizSemaphore();
						$bizSemaphore->setLifeTime( 1 );
						$bizSemaphore->setAttempts( array( 1 ) );
						$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName, false );

						if( $semaphoreId ) {
							// Update the Object.
							if( $dossierRelation->Type == 'Contained' ) {
								DBObject::updateRowValues( $dossierRelation->Child,
									array( 'name' => $dossierRelation->ParentInfo->Name ), 'Workflow' );
							} else {
								DBObject::updateRowValues( $dossierRelation->Child,
									array( 'name' => $dossierRelation->ParentInfo->Name ), 'Trash' );
							}
						}

						// Release the Semaphore.
						BizSemaphore::releaseSemaphore( $semaphoreId );

						// Retrieve the Object from DB and store to be indexed.
						if( $dossierRelation->Type == 'Contained' ) {
							$objectsToIndex[] = BizObject::getObject($dossierRelation->Child, $user, false, 'none',
								array('Targets','MetaData', 'Relations'), null, false, array( 'Workflow' ) );
						} else { // Relation type == 'DeletedContained'
							$objectsInTrashToIndex[] = BizObject::getObject($dossierRelation->Child, $user, false, 'none',
								array('Targets','MetaData', 'Relations'), null, false, array( 'Trash' ) );
						}

					} catch( BizException $e ) {
						if( $semaphoreId ) {
							BizSemaphore::releaseSemaphore( $semaphoreId );
						}
						throw $e;
					}
				}
			}

			// Update the search indexes.
			try {
				require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
				if ( count( $objectsToIndex ) > 0 ) {
					BizSearch::updateObjects( $objectsToIndex, true, array('Workflow') );
				}
				if ( count( $objectsInTrashToIndex ) > 0 ) {
					BizSearch::updateObjects( $objectsInTrashToIndex, true, array('Trash') );
				}
			} catch( BizException $e ) {
				// suppress the errors.
			}

			// BZ#33527
			// Broadcast (Only broadcast for PublishForm when the name is updated.)
			if( $objectsToIndex ) {
				$ticket = BizSession::getTicket();
				$fullUserName = BizUser::resolveFullUserName( $user) ;
				foreach( $objectsToIndex as $object ) {
					// $oldRouteTo is not really applicable here as RouteTo was never updated for PublishForm
					// (Only the Name of the PublishForm was updated), so getting it from updated(instead of old)
					// $object->MetaData->WorkflowMetaData->RouteTo is fine.
					$oldRouteTo = BizUser::resolveFullUserName($object->MetaData->WorkflowMetaData->RouteTo );
					new smartevent_setobjectpropertiesEx( $ticket, $fullUserName, $object, $oldRouteTo );

					// Notify event plugins
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					BizEnterpriseEvent::createObjectEvent( $object->MetaData->BasicMetaData->ID, 'update' );
				}
			}
			if( $objectsInTrashToIndex ) {
				$ticket = BizSession::getTicket();
				$fullUserName = BizUser::resolveFullUserName( $user) ;
				foreach( $objectsInTrashToIndex as $object ) {
					// Same as above $oldRouteTo.
					$oldRouteTo = BizUser::resolveFullUserName($object->MetaData->WorkflowMetaData->RouteTo );
					new smartevent_setobjectpropertiesEx( $ticket, $fullUserName, $object, $oldRouteTo );

					// Notify event plugins
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					BizEnterpriseEvent::createObjectEvent( $object->MetaData->BasicMetaData->ID, 'update' );
				}
			}
		}
	}

	/**
	 * Update object relational targets based on different relation target business rules
	 *
	 * For Parent=Dossier, Child=Layout, Type=Contained, when layout object target removed, same relation target get removed.
	 *
	 *
	 * @param string $user Short user name
	 * @param object $object
	 */
	private static function updateObjectRelationTargets( $user, $object )
	{
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';

		if( $object->MetaData->BasicMetaData->Type == 'Layout' ) {
			if( $object->Relations ) foreach( $object->Relations as $relation ) {
				// Relation: Layout in a dossier
				if( $relation->Child == $object->MetaData->BasicMetaData->ID && $relation->Type == 'Contained' ) {
					$objectRelationId = DBObjectRelation::getObjectRelationId( $relation->Parent, $relation->Child, $relation->Type );
					// Update existing object relation targets
					BizTarget::updateObjectRelationTargets( $user, $objectRelationId, $object->Targets );
					// EA-486: Notify event plugins that the relations for the parent are changed
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					BizEnterpriseEvent::createObjectEvent( $relation->Parent, 'update' );
				}
			}
		}
	}

	/**
	 * Rename the PublishForm after restoration.
	 *
	 * PublishForm shares the same name as Dossier, as a result of this, when the PublishForm
	 * is restored, it gets a -N after the name where N is >=1. For example xxx-N ( where xxx
	 * is the DossierName ). As the server finds xxx(DossierName) already exists, it will
	 * automatically adds a -N postfix to avoid duplicate names. However, for PublishForm and
	 * Dossier, this is intended and so duplicate names should be allowed, here the function
	 * restores the PublishForm name by assigning Dossier name to PublishForm's name.
	 *
	 * @param int $id The PublishForm object id.
	 * @return bool True when the name has successfully renamed, False otherwise.
	 */
	public static function restorePublishFormName( $id )
	{
		$restoreSuccess = true;
		try {
			$publishFormRelations = BizRelation::getObjectRelations( $id, false, false, null, false );
			if( $publishFormRelations ) foreach( $publishFormRelations as $publishFormRelation ) {
				if( $publishFormRelation->Type == 'Contained' &&
					$publishFormRelation->Child == $id &&
					$publishFormRelation->ChildInfo->Type == 'PublishForm' &&
					$publishFormRelation->ParentInfo->Type == 'Dossier' &&
					$publishFormRelation->ParentInfo->Name != $publishFormRelation->ChildInfo->Name ) {
					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					// There's no need to set semaphore as this function is called during PublishForm restoration.
					DBObject::updateRowValues( $id, array( 'name' => $publishFormRelation->ParentInfo->Name ), 'Workflow' );
					break; // Found the Dossier, so quit here.
				}
			}
		} catch( BizException $e ) {
			$restoreSuccess = false;
		}
		return $restoreSuccess;
	}

	/**
	 * Retrieves the ID of the Object matching the requested DocumentId.
	 *
	 * Returns null if no matching Object can be found in either the Workflow or the Trash area.
	 *
	 * @param string $documentId The DocumentId for which to find the Object ID.
	 * @param string $area The area in which the object was found.
	 * @param string $objectType Default: 'PublishFormTemplate'.
	 * @return int|null The ID of the Object, or null if not found.
	 */
	public static function getObjectIdByDocumentId( $documentId, &$area, $objectType = 'PublishFormTemplate' )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		// Check if the Object exists in the Workflow table.
		$area = 'Workflow';
		$objectId = DBObject::getObjectIdByDocumentId( $area, $documentId, $objectType );

		// If not found in the Workflow area, attempt to find it in the trash.
		if (is_null( $objectId ) ) {
			$area = 'Trash';
			$objectId = DBObject::getObjectIdByDocumentId( $area, $documentId, $objectType );
		}

		return $objectId;
	}

	/**
	 * Prepares objects that can used by the search engine to (re)build its index. The returned objects contain the
	 * standard properties plus those properties (especially custom properties) that need to be indexed according to
	 * the search engine(s).
	 *
	 * @param array $objectIds Object ids for which an Object is build.
	 * @param array $areas Either Workflow of TrashCan
	 * @param array $propertiesToIndex Properties returned by the search engine(s) needed to build the index.
	 * @return array of Objects
	 */
	public static function getObjectsToIndexForObjectIds( array $objectIds, array $areas, array $propertiesToIndex )
	{
		// Query DB for MetaData and Targets (for multiple objects at once).
		$metaDatas = array();
		$targets = array();
		self::getMetaDatasAndTargetsToIndexForObjectIds( $objectIds, $areas, $propertiesToIndex, $metaDatas, $targets );

		// Compose list of Objects (from search results).
		$objects = array();
		foreach( $objectIds as $objectId ) {
			$object = new Object();
			$object->MetaData = $metaDatas[ $objectId ];
			$object->Targets = $targets[ $objectId ];
			$objects[] = $object;
		}
		return $objects;
	}

	/**
	 * Builds objects meta data suitable for the search engine to be indexed.
	 *
	 * @param integer[] $objectIds
	 * @param string[] $areas Either 'Workflow' or 'Trash'
	 * @param string[] $propertiesToIndex Properties returned by the search engine(s) needed to build the index.
	 * @param array $metaDatas
	 * @param array $targets
	 * @return MetaData[] List of MetaData, with object ids as keys plus a list of Targets, with object ids as key.
	 */
	private static function getMetaDatasAndTargetsToIndexForObjectIds(
		array $objectIds, array $areas, array $propertiesToIndex, array &$metaDatas, array &$targets )
	{
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';

		// Add the standard properties to the properties returned by the search engine(s).
		$stdProperties = BizProperty::getPropertyInfos();
		$stdProperties = array_keys( $stdProperties );
		$stdProperties = array_flip( $stdProperties );
		$propertiesToIndex = array_flip( $propertiesToIndex );
		// Add fields defined by the Search engine to the standard properties. Filters duplicates at teh same time.
		$propertiesToIndex = array_merge( $propertiesToIndex, $stdProperties );
		$propertiesToIgnore = BizProperty::getIgnorePropsForFlatTreeConv();
		$propertiesToIndex = array_diff_key( $propertiesToIndex, $propertiesToIgnore );
		$propertiesToIndex[ 'Targets' ] = true; // This is an exception, we need the targets to fill issues, editions

		// Use QueryObjects to search for objects (based on object ids).
		$childRows = $componentColumns = $components = array(); // not used
		$rows = BizQuery::queryObjectRows(
			$objectIds,
			'', // mode
			array(), // minimalProps
			array_keys( $propertiesToIndex ), // requestProps,
			false, // hierarchical
			$childRows, $componentColumns, $components,
			$areas,
			null ); // queryOrder

		// Compose MetaData and Targets (based on the found DB rows).
		if( $rows ) foreach( $rows as $row ) {
			$objectId = $row['ID'];
			$targets[$objectId] = isset( $row['Targets'] ) ? array_values($row['Targets']) : null;
			unset( $row['Targets'] );
			$customProps = ( $areas && in_array('Trash', $areas) ) ? false : true;
			$metaDatas[$objectId] = self::queryRow2MetaData( $row, $customProps );
		}
	}

	/**
	 * Collect all users from the MetaData $fields
	 *
	 * @param MetaData $meta
	 * @param array $fields
	 *
	 * @return array $users
	 * */
	public static function getUserFieldsFromMetaData( MetaData $meta, $fields = array() )
	{
		$users = array();

		foreach ($fields as $field) {
			if (isset($meta->WorkflowMetaData->$field) && $meta->WorkflowMetaData->$field != '') {
				$users[strtolower($field)] = $meta->WorkflowMetaData->$field;
			}
		}

		return $users;
	}

	/**
	 * Consolidate the users that appear multiple times in the array
	 *
	 * @param $users
	 * @return array
	 */
	public static function getConsolidatedUsersToCheck($users)
	{
		$usersToCheck = array();
		foreach ($users as $user) {
			if (!in_array($user, $usersToCheck)) {
				$usersToCheck[] = $user;
			}
		}
		return $usersToCheck;
	}

	/**
	 * Funnel out all the usernames from the MetaData and create the users that are unknown.
	 * The content source could provide more user details before we create the user.
	 *
	 * @param string $alienId ID of the alien object. Needed to resolve the content source
	 * @param MetaData $metaData MetaData tree to find all the usernames.
	 * @return array
	 */
	public static function getOrCreateResolvedUsers($alienId, $metaData)
	{
		// Catch the values of the following MetaData fields
		$fields = array('Creator', 'Deletor', 'LockedBy', 'Modifier', 'RouteTo');
		$result = array();

		$usersFromMetaData = BizObject::getUserFieldsFromMetaData($metaData, $fields);
		$usersToCheck = BizObject::getConsolidatedUsersToCheck($usersFromMetaData);

		if (is_array($usersToCheck) && count($usersToCheck) > 0) {
			foreach ($usersToCheck as $userToCheck) {
				$user = self::getOrCreateResolvedUser( $alienId, $userToCheck );
				if ( $user ) {
					$result[] = $user;
				}
			}
		}

		return $result;
	}

	/**
	 * Funnel out all the usernames/fullnames from the MetaData and create the users that are unknown.
	 * The content source could provide more user details before we create the user.
	 *
	 * @param string $alienId
	 * @param string $userToCheck
	 * @return AdmUser
	 * @throws BizException in case of an error
	 */
	private static function getOrCreateResolvedUser( $alienId, $userToCheck )
	{
		require_once BASEDIR . '/server/bizclasses/BizLDAP.class.php';
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

		// The user is not found (check for username or fullname)
		if ( is_null( DBUser::findUser( null, $userToCheck, $userToCheck) ) ) {
			if( BizLDAP::isInstalled() ) {
				$userObj = self::completeUserFromContentSource($alienId, $userToCheck);
				return DBUser::createUserObj($userObj);
			} else {
				throw new BizException('ERR_INVALID_PROPERTY', 'Client', 'Unknown user: ' . $userToCheck);
			}
		}
		return null;
	}

	/**
	 * Request more user information from the content source
	 *
	 * @param string $alienId
	 * @param string $userToCheck
	 * @return AdmUser
	 */
	private static function completeUserFromContentSource($alienId, $userToCheck)
	{
		require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

		$userObj = new AdmUser();
		$userObj->Name = $userToCheck;
		$userObj->FullName = $userToCheck;
		$userObj->ImportOnLogon = true;
		$userObj = BizContentSource::completeUser($alienId, $userObj);

		return $userObj;
	}

	/**
	 * Replaces the element GUIDs for all text components of a given InCopy article.
	 *
	 * This is typically needed when an InCopy article is uploaded by Content Station.
	 * Else, uploading the article twice would result in duplicate GUIDs for the different
	 * elements, which would confuse Smart Connection, and therefore is not allowed.
	 *
	 * Aside to the native InCopy file itself, the GUIDs of the Object's Elements are also
	 * replaced in memory so that they get stored correcly in the DB later and can be
	 * mapped correctly onto the InCopy file by Smart Connection (and Content Station).
	 *
	 * @param Object $object
	 */
	static private function replaceGuidOfArticle( /** @noinspection PhpLanguageLevelInspection */
		Object $object )
	{
		$formats = array(
			'application/incopy' => true,
			'application/incopyinx' => true,
			'application/incopyicml' => true,
			'application/incopyicmt' => true );
		if ( $object->Files ) foreach ( $object->Files as $attachment ) {
			if ( $attachment->Rendition == 'native' && isset( $formats[ $attachment->Type ] )) {

				// Replace the GUIDs in the native InCopy file (that resides in the transfer server folder).
				require_once BASEDIR . '/server/appservices/textconverters/InCopyTextUtils.php';
				$domDoc = new DOMDocument();
				$domDoc->load( $attachment->FilePath );
				$replaceGuids = array(); // Must be declared as it is passed by reference.
				InCopyUtils::replaceGUIDs($domDoc, $replaceGuids, $attachment->Type );
				$domDoc->save( $attachment->FilePath );

				// Replace the GUIDs in the Object's Elements as well.
				if( $replaceGuids && isset($object->Elements) ) {
					if( $object->Elements ) foreach( $object->Elements as $element ) {
						if( isset( $replaceGuids[$element->ID] ) ) {
							$element->ID = $replaceGuids[$element->ID];
						}
					}
				}
			}
		}
	}

	/**
	 * Plug-ins of the type Content Source can say if the version of the external system must be used.
	 * @param string $contentSourceId Identifier of the content source.
	 * @return  bool Use the version of the content source.
	 * @throws BizException
	 */
	static private function useContentSourceVersion( $contentSourceId )
	{
		try {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			$connectors = BizServerPlugin::searchConnectors('Version', null);
			if ($connectors) foreach ( $connectors as $connClass => $connector ){
				LogHandler::Log('BizVersion', 'DEBUG', 'Connector '.$connClass.' executes method useContentSourceVersion');
				$connRetVals[$connClass] = call_user_func_array( array(&$connector, 'useContentSourceVersion'), array());
				LogHandler::Log('BizVersion', 'DEBUG', 'Connector completed.' );
				if ( $connRetVals ) foreach ( $connRetVals as $retVal ) {
					if ( $retVal ) foreach ( $retVal as $key => $value ) {
						if ( $key == $contentSourceId ) {
							return $value;
						}
					}
				}
			}
		} catch( BizException $e ) {
			throw $e;
		}

		return false;
	}

	/**
	 * Creates a semaphore for the SaveObjects operation of a layout.
	 *
	 * @since 9.8.0
	 * @param integer $layoutId
	 * @return string semaphore id
	 */
	static public function createSemaphoreForSaveLayout( $layoutId )
	{
		// Take 2 minutes because layouts can be quite large and they need to be copied 
		// from the transfer server folder to the filestore. Note that the (DIME) file upload 
		// time should NOT be taken into account here since that is already completed
		// at the time the services is executed.
		$lifeTime = 120;

		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$bizSemaphore = new BizSemaphore();
		$semaphoreName = 'SaveLayout_'.$layoutId;
		$bizSemaphore->setLifeTime( $lifeTime );
		$bizSemaphore->setAttempts( array_fill( 0, 4 * $lifeTime, 250 ) ); // 4*120 attampts x 250ms wait = 120s max total wait
		$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );
		$bizSemaphore->updateLifeTime( $semaphoreId, $lifeTime );
		return $semaphoreId;
	}

	/**
	 * Get an unique object, when restoring an object or when 'apply auto-naming' is true.
	 *
	 * Different logic or algorithm will be apply differently for both actions, restoring object or 'apply auto-naming' is true.
	 * When restoring an object, if the object name already exists in DB, 'auto naming' numbering will be append at the end of object name,
	 * For example:
	 * Original name                Unique name
	 * =============                ===========
	 * abc                          abc_0001
	 * abc_0001                     abc_0001_0001
	 *
	 * When 'apply auto-naming' is true, if the object name already exist in DB, auto-naming numbering will be append at the end of object name,
	 * when the object contains the auto-naming format[abc_0001], incremental auto-naming numbering from the last number[abc_0002].
	 * For example:
	 * Original name                Unique name
	 * =============                ===========
	 * abc                          abc_0001
	 * abc_0001                     abc_0002
	 *
	 * @param int $id The object Id
	 * @param string $proposedName The object name
	 * @param array $issueIds Array of issue id
	 * @param string $type The object type
	 * @param bool $restore Status whether it is restoring object action
	 * @return string $proposedName Return unique object name
	 */
	private static function getUniqueObjectName( $id, $proposedName, $issueIds, $type, $restore )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$existingNames[$proposedName] = true;
		/** @noinspection PhpUnusedLocalVariableInspection */
		// Initialization of a flag before entering the do-while loop to avoid undefined variable
		// in the case of the code execution didn't go as expected.
		$nameFound = false;
		$iterations = 0;
		$maxSuffix = intval( str_repeat( '9', AUTONAMING_NUMDIGITS ) );
		do {
			if( !$restore ) {
				if( preg_match('/^(.*?)_(\d+)$/', $proposedName, $matches ) > 0 ) {
					if(strlen($matches[2]) == AUTONAMING_NUMDIGITS ) {
						$existingNames[$proposedName] = true;
						$proposedName = $matches[1];
					}
				}
			}
			$proposedName = DBObject::makeNameUnique( $existingNames, $proposedName );
			$existingNames[$proposedName] = true;
			$nameFound = self::objectNameExists( $issueIds, $proposedName, $type, $id );
			$iterations += 1;
		} while ( $nameFound && $iterations < $maxSuffix );

		return $proposedName;
	}
}
