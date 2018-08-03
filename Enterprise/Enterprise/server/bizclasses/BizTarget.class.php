<?php

/**
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';

class BizTarget
{
	/**
	 * Create object targets
	 *
	 * @param string $user
	 * @param int $objectId
	 * @param array $targets Target objects
	 * @param bool $indexObject index object in search engines, default false
	 * @throws BizException
	 * @return array with Target objects
	 */
	public static function createTargets( $user, $objectId, $targets, $indexObject = false )
	{
		self::fixPearSoapTargets( $targets ); // PEAR SOAP collection fixes

		self::checkForOverruleIssues($targets);

		if( !$objectId || trim($objectId) == '' ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		if( !$targets || !sizeof( $targets ) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
		}
		self::addTargets( $user, $objectId, $targets );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	
		$retTargets = DBTarget::getTargetsByObjectId( $objectId );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}
		
		self::updateDeadlines($objectId, $retTargets);

		// BZ#17866 Only index object if this function is called from the webservice
		if ($indexObject){
			require_once BASEDIR. '/server/bizclasses/BizSearch.class.php';
			BizSearch::indexObjectsByIds(array($objectId));
		}
		
		return $retTargets;        
	}

	/**
	 * Creates new relational Targets given the relation id and its Targets.
	 *
	 * @param string $user
	 * @param int $objectRelationId
	 * @param array $targets
	 * @throws BizException
	 */
	public static function createObjectRelationTargets( /** @noinspection PhpUnusedParameterInspection */ $user,
														$objectRelationId, $targets )
	{
		self::fixPearSoapTargets( $targets ); // PEAR SOAP collection fixes

		self::checkForOverruleIssues($targets);

		if( !$objectRelationId || trim($objectRelationId) == '' ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		if( !$targets || !sizeof( $targets ) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
		}
		self::addObjectrelationTargets( $objectRelationId, $targets );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	
	}

	/**
	 * Delete object relation targets and its Target-Editions.
	 *
	 * @param int $objectRelationId
	 * @param array $targets
	 * @throws BizException
	 */
	public static function deleteObjectRelationTargets( $objectRelationId, $targets )
	{
		if( $targets ) foreach( $targets as $target ) {
			if( !isset($target->PubChannel->Id) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
			}
			if( !isset($target->Issue->Id) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{ISSUE}' ) );
			}
			$channelId = $target->PubChannel->Id;
			$issueId = $target->Issue->Id;
			DBTarget::deleteObjectRelationTarget( $objectRelationId, $channelId, $issueId );
		}
	}
	
	/**
	 * Update object targets
	 *
	 * @param string $user
	 * @param int $objectId
	 * @param array $targets Target objects
	 * @param bool $indexObject index object in search engines, default false
	 * @throws BizException
	 * @return array with Target objects
	 */
	public static function updateTargets( $user, $objectId, $targets, $indexObject = false )
	{
		self::fixPearSoapTargets( $targets ); // PEAR SOAP collection fixes

		self::checkForOverruleIssues($targets);

		if( !$objectId || trim($objectId) == '' ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		if( !$targets || !sizeof( $targets ) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
		}
		$metaData = null;
		self::saveTargets( $user, $objectId, $targets, $metaData );

		// Validate meta data and targets (including validation done by Server Plug-ins)
		BizObject::validateMetaDataAndTargets( $user, $metaData, $targets, null, true );

		$retTargets = DBTarget::getTargetsByObjectId( $objectId );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	
		
		self::updateDeadlines($objectId, $retTargets);
		
		// BZ#17866 Only index object if this function is called from the webservice
		if ($indexObject){
			require_once BASEDIR. '/server/bizclasses/BizSearch.class.php';
			BizSearch::indexObjectsByIds(array($objectId));
		}
		
		return $retTargets;        
	}

	/**
	 * Creates new relational Targets given the relation id and its Targets.
	 *
	 * @param string $user
	 * @param int $objectRelationId
	 * @param array $targets
	 * @throws BizException
	 */
	public static function updateObjectRelationTargets( /** @noinspection PhpUnusedParameterInspection */ $user,
														$objectRelationId, $targets )
	{
		self::checkForOverruleIssues($targets);

		if( $targets ) foreach( $targets as $target ) {
			$channelId = $target->PubChannel->Id;
			$issueId = $target->Issue->Id;
			$editions = $target->Editions; // optional
			$targetExternalId = isset( $target->ExternalId ) ? $target->ExternalId : null;
			$updateResult = DBTarget::updateObjectRelationTarget( $objectRelationId, $channelId, $issueId, $editions,
				$targetExternalId, $target->PublishedDate, $target->PublishedVersion );
			if( !$updateResult ) {
				throw new BizException( 'ERR_DATABASE', 'Server',
					'Failed to update relational Targets for relation id "' . $objectRelationId. '"' );
			}
		}
	}

	/**
	 * Delete object targets
	 *
	 * @param string $user
	 * @param int $objectId
	 * @param array $targets Target objects
	 * @param bool $indexObject index object in search engines, default false
	 * @throws BizException
	 */
	public static function deleteTargets( $user, $objectId, $targets, $indexObject = false )
	{
		self::fixPearSoapTargets( $targets ); // PEAR SOAP collection fixes
		// params validation
		if( !$objectId || trim($objectId) == '' ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		if( !$targets || !sizeof( $targets ) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
		}
		self::canRemoveTargets( $targets );
		// perform deletion
		self::removePublishForm( $objectId, $targets, $user );
		$userfull = BizUser::resolveFullUserName($user);
		self::removeTargets( $userfull, $objectId, $targets );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	

		// Delete object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		$dbDriver = DBDriverFactory::gen();
		$sth = DBObject::getObject( $objectId ); // TODO: better use BizObject, but we need storename too
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$objRow = $dbDriver->fetch($sth);
		if (!$objRow) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $objectId );
		}
		$publId = $objRow['publication'];
		$publName = DBPublication::getPublicationName( $publId );
		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			require_once BASEDIR.'/server/bizclasses/BizLinkFiles.class.php';
			BizLinkFiles::deleteLinkFiles( $publId, $publName, $objectId, $objRow['name'], $targets );
		}
		
		$newTargets = DBTarget::getTargetsByObjectId( $objectId );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}
		
		self::updateDeadlines($objectId, $newTargets);
		
		// BZ#17866 Only index object if this function is called from the webservice
		if ($indexObject){
			require_once BASEDIR. '/server/bizclasses/BizSearch.class.php';
			BizSearch::indexObjectsByIds(array($objectId));
		}
	}

	/**
	 * Removes given targets from an object.
	 *
	 * @param string $userFullname
	 * @param int $objectId
	 * @param array $targets to delete
	 * @throws BizException
	 */
	private static function removeTargets( $userFullname, $objectId, $targets )
	{
		require_once BASEDIR . '/server/smartevent.php';
		foreach ($targets as $target) {
			if( !$target->PubChannel ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
			}
			if( !$target->Issue ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{ISSUE}' ) );
			}
			$channelId = $target->PubChannel->Id;
			$issueId = $target->Issue->Id;
			require_once BASEDIR. '/server/dbclasses/DBPublishHistory.class.php';
			if (DBPublishHistory::isDossierPublished( $objectId, $channelId, $issueId, null )) {
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
				$issueInfo = DBIssue::getIssue( $issueId ); 
				$objectName = DBObject::getObjectName( $objectId );				
				$params = array($issueInfo['name'], $objectName);
				throw new BizException( 'ERR_MOVE_DOSSIER', 'Client', '', null, $params);
			}			
			DBTarget::removeSomeTargetsByObject( $objectId, $channelId, $issueId );
			// send DeleteObjectTarget event
			new smartevent_deleteobjecttarget(BizSession::getTicket(), $userFullname, $objectId, $target);

			// Notify event plugins
			require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
			BizEnterpriseEvent::createObjectEvent( $objectId, 'update' );
		}
	}

	/**
	 * Returns the given object(s)' Object Target.
	 *
	 * @throws BizException Function throws BizException when there's error in the database query.
	 * @param string $user
	 * @param mixed $objectId One object ID (int) or list of object IDs (array of int).
	 * @return array List of object targets.
	 */
	public static function getTargets( /** @noinspection PhpUnusedParameterInspection */ $user,
										$objectId )
	{
		if( !$objectId || trim($objectId) == '' ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		$retTargets = DBTarget::getTargetsByObjectId( $objectId );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	
		return $retTargets;        
	}

	/**
	 * Returns all issues ids of the relational targets of an object.
	 * Duplicates are removed.
	 * @param integer $objectId
	 * @return array with the issue ids.
	 */
	public static function getRelationalTargetIssuesForChildObject( $objectId)
	{
		return DBTarget::getRelationalTargetIssuesForChildObject( $objectId );
	}

	/**
	 * Retrieves issue- and channel info from DB and builds an Target object from it.
	 *
	 * @param integer $issueId
	 * @return object Target. Null when failed.
	 * @throws BizException when issue (or its channel) could not be found at DB
	 */
	public static function buildTargetFromIssueId( $issueId )
	{
		// Resolve issue name based on id
		if( !empty($issueId) ) { // Issue is optional!
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$issueRow = DBIssue::getIssue( $issueId );
			if( !$issueRow ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'IssueId ('.$issueId.')' );
			}
			$channelId = $issueRow['channelid'];
			$issueObj = new Issue( $issueId, $issueRow['name'], ($issueRow['overrulepub'] == 'on') );
		} else {
			$channelId = '';
			$issueObj = null;
		}
		
		// Resolve channel name based on id
		if( !empty($channelId) ) {
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
			$channelRow = DBChannel::getChannel( $channelId );
			if( !$channelRow ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', 'ChannelId ('.$channelId.')' );
			}
			$channelObj = new PubChannel( $channelId, $channelRow['name'] ); // channel id possibly resolved by issue id
		} else {
			$channelObj = null;
		}
		if( $channelObj && $issueObj ) {
			return new Target( $channelObj, $issueObj, null );
		}
		return null; // failed
	}

	/**
	 * Fixes target structure that might have badly formed PEAR SOAP collections.
	 * Typically, when there is one element, PEAR gives an aggregate object instead of an object in an array.
	 * This function detects it and repairs.
	 *
	 * @param array $targets Array of Target to be repaired
	 */
	public static function fixPearSoapTargets( &$targets )
	{
		//BZ#9827 For now, if $targets == null -> $targets = array()
		if ($targets == null) {
			$targets = array();
			return;
		}
		
		if( $targets && empty($targets) ) {
			$targets = array(); // might be object, so convert to array
			return;
		}
		if( isset($targets->Target) ) {
			$targets = is_array($targets->Target) ? $targets->Target : array($targets->Target); 
		}
		if( !is_array($targets) ) {
			$targets = array();
			return;
		}
		foreach( $targets as &$target ) {
			if( isset( $target->Editions->Edition ) ) {
				$target->Editions = is_array($target->Editions->Edition) ? $target->Editions->Edition : array( $target->Editions->Edition );
			}
		}
	}
	
	/**
	 *  Add targets to an object.
	 *
	 * @param string $user
	 * @param int $objectId
	 * @param array $targets
	 * @throws BizException
	 */
	private static function addTargets( $user, $objectId, $targets )
	{
		$oldtargets = self::getTargets(null, $objectId);
		$alltargets = array_merge($oldtargets, $targets);
		self::checkForOverruleIssues($alltargets);

		if( !is_numeric($objectId) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		
		require_once BASEDIR . '/server/smartevent.php';
		$userfull = BizUser::resolveFullUserName($user);
		foreach( $targets as $target ) {
			if( !isset($target->PubChannel->Id) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
			}
			if( !isset($target->Issue->Id) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{ISSUE}' ) );
			}
			$channelId = $target->PubChannel->Id;
			$issueId = $target->Issue->Id;
			$editions = $target->Editions; // optional
			DBTarget::addTarget( $objectId, $channelId, $issueId, $editions );
			// send CreateObjectTarget event
			new smartevent_createobjecttarget(BizSession::getTicket(), $userfull, $objectId, $target);

			// Notify event plugins
			require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
			BizEnterpriseEvent::createObjectEvent( $objectId, 'update' );
		}

		// Update object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		$dbDriver = DBDriverFactory::gen();
		$sth = DBObject::getObject( $objectId ); // TODO: better use BizObject, but we need storename too
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$objRow = $dbDriver->fetch($sth);
		if (!$objRow) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $objectId );
		}
		$publId = $objRow['publication'];
		$publName = DBPublication::getPublicationName( $publId );
		if( defined( 'HTMLLINKFILES' ) && HTMLLINKFILES == true ) {
			require_once BASEDIR.'/server/bizclasses/BizLinkFiles.class.php';
			BizLinkFiles::createLinkFiles( $publId, $publName, $objectId, $objRow['name'], $objRow['type'], 
							$objRow['version'], $objRow['format'], $objRow['storename'], $targets );
		}
	}

   private static function addObjectrelationTargets( $objectrelationId, $targets )
	{
		self::checkForOverruleIssues($targets);

		if( !is_numeric($objectrelationId) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		foreach( $targets as $target ) {
			if( !isset($target->PubChannel->Id) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
			}
			if( !isset($target->Issue->Id) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{ISSUE}' ) );
			}
			$channelId = $target->PubChannel->Id;
			$issueId = $target->Issue->Id;
			$editions = $target->Editions; // optional
			$targetExternalId = isset( $target->ExternalId ) ? $target->ExternalId : null;
			DBTarget::addObjectRelationTarget( $objectrelationId, $channelId, $issueId, $editions, $targetExternalId,
				$target->PublishedDate, $target->PublishedVersion );
		}
	}

	/**
	 * Validates the (arrived) Targets for a given object and stores them at DB.
	 * When targets are valid, the pub/iss/sec/edition names are resolved using ids.
	 * The given targets are assumed to be complete!
	 *
	 * @param string $user Short user name (=id)
	 * @param string $objectId Object id
	 * @param array $targets List of Target objects, can be null
	 * @param MetaData $metaData Optional. The MetaData of the object. Pass null if you don't have it; this function will get it when needed.
	 * @throws BizException when any pub/iss/sec/edition id is not valid.
	 */
	public static function saveTargets( $user, $objectId, &$targets, &$metaData )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		
		// At least we need an object to save target for
		if( !is_numeric($objectId) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'ID' );
		}

		self::checkForOverruleIssues($targets);

		if( is_null($metaData) ) {
			$obj = BizObject::getObject( $objectId, $user, false, null ); // no lock, no rendition
			$metaData = $obj->MetaData;
		}

		// When the targets of a layout change, check the object targets of the placed objects also.
		if( $metaData->BasicMetaData->Type == 'Layout' ) {
			self::movePlacedObjects( $user, $objectId, $targets );
		}

		// get current targets before targets are added
		$dbTargets = self::getTargets( '', $objectId );

		// Validate target change: BZ#30518
		$targetsToBeRemoved = self::getTargetsToBeRemoved($dbTargets, $targets);	
		if( $metaData->BasicMetaData->Type == 'Dossier' ) {
			if ( $targetsToBeRemoved ) foreach ( $targetsToBeRemoved as $targetToBeRemoved ) {
				require_once BASEDIR. '/server/dbclasses/DBPublishHistory.class.php';
				if (DBPublishHistory::isDossierPublished( $objectId, $targetToBeRemoved->PubChannel->Id, $targetToBeRemoved->Issue->Id, null )) {
					require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
					require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
					$issueInfo = DBIssue::getIssue( $targetToBeRemoved->Issue->Id); 
					$objectName = DBObject::getObjectName( $objectId );
					$params = array( $issueInfo['name'], $objectName );
					throw new BizException( 'ERR_MOVE_DOSSIER', 'Client', '', null, $params);
				}
			}	
		}		
		
		require_once BASEDIR . '/server/smartevent.php';
		$userfull = BizUser::resolveFullUserName($user);
		$editions = null;
		$eventSent = false; // Track if SmartEvent is sent
		if( !empty($targets) ) foreach( $targets as &$target ) {
			if( !isset($target->PubChannel->Id) && !isset($target->Issue->Id) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'PubChannelId / IssueId (for object ID='.$objectId.')' );
			}
			// Check channel id
			$channelId = null;
			if( isset($target->PubChannel->Id) ) { // Channel is optional (but we might be able to derive it from a given issue)
				$channelId = $target->PubChannel->Id;
				if( !is_numeric($channelId) ) {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'PubChannelId (for object ID='.$objectId.')' );
				}
			}
			// Resolve edition names based on ids
			if( !empty($target->Editions) ) foreach( $target->Editions as &$edition ) {
				if( !isset($edition->Id) || !is_numeric($edition->Id) )  {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'EditionId (for object ID='.$objectId.')' );
				}
				$editionObj = DBEdition::getEdition( $edition->Id );
				if( !$editionObj ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', 'EditionId ('.$edition->Id.')' );
				}
				$edition->Name = $editionObj->Name; // resolve name based on id
			}
			// BZ#23337: In version#1, Case 2 
			// When Editions are not given for Dossier, server side should set ALL editions to the target.
			$parentType = $metaData->BasicMetaData->Type;
			if ( $parentType == 'Dossier' && empty($target->Editions)){
				$editions = DBEdition::listChannelEditions( $channelId );
				$target->Editions = array();
				if( $editions ) foreach( $editions as $edition ){
					$target->Editions[] = new Edition($edition['id'],$edition['name']);
				}
			}
			// BZ#23337: In version#1, Case 3
			// When Editions are not given for Layout, server should resolve the Editions.
			if ( ($parentType == 'Layout' || $parentType == 'PublishForm') && empty($target->Editions)){
				if( count($dbTargets) > 0){ // when there was previous Editions on the layout
					if( $dbTargets[0]->PubChannel->Id == $target->PubChannel->Id ){ // User only change issue within the pub channel
						$target->Editions = $dbTargets[0]->Editions; 
					}else{ // user changed pub channel
						// Collect the editions belong to the new pub channel selected
						$editions = DBEdition::listChannelEditions( $channelId );
						if( $editions ){
							$listOfEditions = array();
							foreach( $editions as $editionId => $editionInfo ){
								$listOfEditions[$editionInfo['name']] = $editionId;
							}
							// Resolve Editions name-based for the new pub channel selected
							$target->Editions = array();
							if( $dbTargets[0]->Editions ) foreach( $dbTargets[0]->Editions as $dbTargetEdition ){
								if( array_key_exists($dbTargetEdition->Name, $listOfEditions)){ // name-based resolving
									$target->Editions[] = new Edition( $listOfEditions[$dbTargetEdition->Name], $dbTargetEdition->Name );
								}else{
									// when any of the 'old' edition is not found in the new pubChannel's edition, 
									// server should assign ALL editions(done below) that belongs to the new pub channel.
									$target->Editions = array();
									break;
								}
							}
						}
					}
				}
				// If the 'repairing' above still didn't fill up the Editions, get ALL editions belonging to the selected issue
				if(!$target->Editions){
					$target->Editions = array();
					$editions = $editions ? $editions : DBEdition::listChannelEditions( $channelId );
					if( $editions ) foreach( $editions as $editionId => $editionInfo ){
						$target->Editions[] = new Edition( $editionId, $editionInfo['name']);
					}
				}
			}

			// Resolve issue name based on id
			$issueId = null;
			if( isset($target->Issue->Id) ) { // Issue is optional!
				$issueId = $target->Issue->Id;
				if( !is_numeric($issueId) ) {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'IssueId (for object ID='.$objectId.')' );
				}
				$issueRow = DBIssue::getIssue( $issueId );
				if( !$issueRow ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', 'IssueId ('.$issueId.')' );
				}
				$channelId = $issueRow['channelid']; // when differ, we do silent repair
				$target->Issue->Name = $issueRow['name']; // resolve name based on id
				$target->Issue->OverrulePublication = ($issueRow['overrulepub'] == 'on');
			}
			
			// Resolve channel name based on id
			if( $channelId ) {
				$channelRow = DBChannel::getChannel( $channelId );
				if( !$channelRow ) {
					throw new BizException( 'ERR_NOTFOUND', 'Client', 'ChannelId ('.$channelId.')' );
				}
				$target->PubChannel = new PubChannel( $channelId, $channelRow['name'] ); // channel id possibly resolved by issue id
			}

			$editionIdsToRemove = self::getEditionIdsToBeRemoved( $dbTargets, $target );
				// Replace editions per given isssue; the arrived set of editions overrules the editions stored in database.
			if( $issueId && $editionIdsToRemove ) {
				foreach( $editionIdsToRemove as $editionIdToRemove) {
					DBTargetEdition::removeSomeTargetEditionsByObject( $objectId, $channelId, $issueId, $editionIdToRemove );
				}
			}

			// Store the validated target at DB for this object.
			DBTarget::addTarget( $objectId, $channelId, $issueId, $target->Editions );
			if( DBTarget::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
			}
			// send UpdateObjectTarget event
			new smartevent_updateobjecttarget(BizSession::getTicket(), $userfull, $objectId, $target);
			$eventSent = true;
		}
		
		// automatic target assignment, before orig targets are deleted
		self::automaticTargetAssignments($dbTargets, $targets, $metaData);
		// Remove object targets that are in db, but not given by client app.
		// Assumed is that client app provides *all* object targets.
		if ( $targetsToBeRemoved ) foreach ( $targetsToBeRemoved as $targetToBeRemoved ) {
			DBTarget::removeSomeTargetsByObject( $objectId, $targetToBeRemoved->PubChannel->Id, $targetToBeRemoved->Issue->Id );
			// send DeleteObjectTarget event
			new smartevent_deleteobjecttarget(BizSession::getTicket(), $userfull, $objectId, $targetToBeRemoved);
			$eventSent = true;
		}
		
		// If a SmartEvent is sent, we create an EnterpriseEvent when we update the target directly. In other cases the
		// EnterpriseEvent is already created, so we avoid creating it multiple times.
		if ($eventSent && BizSession::getServiceName() == 'WflUpdateObjectTargets') {
			// Notify event plugins
			require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
			BizEnterpriseEvent::createObjectEvent( $objectId, 'update' );
		}
		
		// >>> Moved validation calls to calling functions fix BZ#13522
		// Validate meta data and targets (including validation done by Server Plug-ins)
		//BizObject::validateMetaDataAndTargets( $user, $metaData, $targets );
		// <<<
	}

	/**
	 * Returns the Edition ids from the original Target that are no longer in the new Target.
	 *
	 * Checks the difference in editions between two sets of targets.
	 * Editions in the $originalTargets but not in the $newTarget are marked as different.
	 *
	 * @param $originalTargets[] List of Targets from the database.
	 * @param Target $newTarget The arrival Target where the original Edition might be already removed.
	 * @return array List of Edition Ids that are not present in $newTarget
	 */
	static private function getEditionIdsToBeRemoved( $originalTargets, $newTarget )
	{
		$diffEditionIds = array();
		if ( $originalTargets ) foreach( $originalTargets as $oriTarget ) {
			if( $oriTarget->PubChannel->Id == $newTarget->PubChannel->Id &&
				$oriTarget->Issue->Id == $newTarget->Issue->Id ) {
				if ( $oriTarget->Editions )foreach( $oriTarget->Editions as $oriTargetEdition ) {
					$found = false;
					if( $newTarget && $newTarget->Editions ) foreach( $newTarget->Editions as $newTargetEdition ) {
						if( $newTargetEdition->Id == $oriTargetEdition->Id ) {
							$found = true; // The original Edition was found in the new Target, so do not mark it for removal.
							break;
						}	
					}
					if ( !$found ) { // The original Edition was not found in the new Target, this means it should be marked for removal.
						$diffEditionIds[] = $oriTargetEdition->Id;
					}
				}
				break; // Finish comparing the new Target with the original Target Editions, so quit here.
			}
		}		

		return $diffEditionIds;
	}	
	/**
	 * Compares the 'old' in the database stored targets with the new targets (complete list) and returns a 
	 * list of targets that must be removed.
	 *
	 * @param Target[] $oldTargets Targets of the object stored in the database.
	 * @param Target[]|null $newTargets New targets
	 * @return Target[] Targets with issue and channelid that are in the old but not in the new targets.
	 */
	static public function getTargetsToBeRemoved( array $oldTargets, $newTargets )
	{
		$targetsToBeRemoved = array();
		
		// Remove object targets that are in db, but not given by client app.
		// Assumed is that client app provides *all* object targets.
		if( !empty($oldTargets) ) foreach( $oldTargets as $oldTarget ) {
			$sameChannel = false;
			$sameIssue = false;
			if( !empty($newTargets) ) foreach( $newTargets as $newTarget ) {
				$sameChannel = ( !isset($newTarget->PubChannel->Id) && !isset($oldTarget->PubChannel->Id) ) ||
					( isset($newTarget->PubChannel->Id) && isset($oldTarget->PubChannel->Id) && $newTarget->PubChannel->Id == $oldTarget->PubChannel->Id );
				$sameIssue = ( !isset($newTarget->Issue->Id) && !isset($oldTarget->Issue->Id) ) ||
					( isset($newTarget->Issue->Id) && isset($oldTarget->Issue->Id) && $newTarget->Issue->Id == $oldTarget->Issue->Id );
				if( $sameChannel && $sameIssue ) {
					break; // found
				}
			}
			if( !$sameChannel || !$sameIssue ) {
				$targetToBeRemoved = new Target();
				$pubChannel = new PubChannel();
				$pubChannel->Id = isset($oldTarget->PubChannel->Id) ? $oldTarget->PubChannel->Id : 0;
				$targetToBeRemoved->PubChannel = $pubChannel;

				$issue = new Issue();
				$issue->Id = isset($oldTarget->Issue->Id) ? $oldTarget->Issue->Id : 0;
				$targetToBeRemoved->Issue = $issue;
				
				$targetsToBeRemoved[] = $targetToBeRemoved;
			}
		}

		return $targetsToBeRemoved;
	}	
	
	static public function getDefaultTarget($pubid, $targets)
	{
		if ($targets == null) {
			return null;
		}
		require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
		$pubrow = DBPublication::getPublication($pubid);
		$defaultchannelid = $pubrow['defaultchannelid'];
		foreach ($targets as $target) {
			if ($target->PubChannel) {
				if ($target->PubChannel->Id == $defaultchannelid) {
					return $target;
				}
			}
		}
		return null;
	}
	
	static public function getDefaultIssueId($pubid, $targets)
	{
		$defaulttarget = self::getDefaultTarget($pubid, $targets);
		if ($defaulttarget) {
			if ($defaulttarget->Issue) {
				return $defaulttarget->Issue->Id;
			}
		}
		return 0;
	}
	

	/**
	 * Moves placed objects along with moved layouts.
	 * The following rules apply when a Layout is moved to another issue or brand.
	 * 	=> If the object target of a placed file does not contain an entry for the new issue, this issue is added, except when
	 * 		-> the file does not have object targets 
	 * 		-> the file is in a Dossier; See "Print Layouts used in a dossier based workflow" for more information.
	 * 		-> the user does not have the rights to update the targets of the file
	 * 	=> If the same Editions are selected (map on name) for the new issue, the Editions that match are set for the object target of the placed file
	 * 	=> If only editions are updated the targets of the placed files are not updated
	 * 	=> If one of the issues in the object targets of a placed file matches the original Issue this issue is removed, except when
	 * 		-> the file is placed on another Layout which is still targeted for the original Issue.
	 * 	=> If a layout is moved to another brand the placed files are not moved to the other brand because the statuses differ
	 *
	 * @param string $user Short user name (=id)
	 * @param string $id Object id
	 * @param array $newTargets List of new Target objects
	 */
	private static function movePlacedObjects( $user, $id, $newTargets )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';

		$oriTargets = self::getTargets( '', $id );	// Original target
		$oriIssueId = $oriTargets ? $oriTargets[0]->Issue->Id : null; 	// Original target issue id
		$newIssueId = $newTargets ? $newTargets[0]->Issue->Id : null; 	// New target issue id

		if( $oriIssueId == $newIssueId ) { // Rule: If only editions are updated the targets of the placed files are not updated
			return;
		}

		if( is_null($newTargets[0]->Issue->OverrulePublication) ) {
			$isOverruleNewIssue = DBIssue::isOverruleIssue( $newIssueId );
		} else {
			$isOverruleNewIssue = $newTargets[0]->Issue->OverrulePublication;
		}
		if( ($oriTargets && $oriTargets[0]->Issue->OverrulePublication) || $isOverruleNewIssue ) { // Rule: Overrule Issue change, placed objects are not updated
			return;
		}

		// Get and compare the publication id
		$oriIssueRow = DBIssue::getIssue( $oriIssueId );
		$oriPubId = $oriIssueRow['publication'];
		$newIssueRow = DBIssue::getIssue( $newIssueId );
		$newPubId = $newIssueRow['publication'];
		if( $oriPubId != $newPubId ) { // Rule: Brand change, placed object's targets are not update
			return;
		}

		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		// take deleted parents into account, this is to prevent when deleted parent get restore, the placed object target is not tally.
		$containedRelations = DBObjectRelation::getObjectRelations( $id, 'parents', 'Contained', true );
		if( $containedRelations ) { // Rule: Layout contained in a dossier, placed objects are not update
			return;
		}

		$placementIds = self::getNonContainedPlacedChildIds( $id ); // Get all the placed childs id
		if( count($placementIds) > 0 ) {
			self::updateTargetOfPlacedChilds( $user, $id, $oriTargets[0], $newTargets[0], $placementIds );
		}
	}

	/**
	 * Get all placement child objects of a given layout that are not contained in any dossier.
	 *
	 * @param integer $layoutId Unique id of layout
	 * @return array of placement ids
	 */
	private static function getNonContainedPlacedChildIds( $layoutId )
	{
		$childs = array();

		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$childRelations = DBObjectRelation::getObjectRelations( $layoutId, 'childs', 'Placed', true );
		foreach( $childRelations as $childRelation ) {
			$containedRelations = DBObjectRelation::getObjectRelations( $childRelation['child'], 'parents', 'Contained', true );
			if( !$containedRelations ) { // Rule: Add/move object target when object is not contained in a dossier
				$childs[] = $childRelation['child'];
			}
		}
		return $childs;
	}

	/**
	 * Moves placed objects along with their layout by updating targets with a new given issue.
	 *
	 * @param string $user Short user name.
	 * @param integer $layoutId Layout object id.
	 * @param object $oriLayoutTarget Object of layout original object target.
	 * @param object $newLayoutTarget Object of layout new object target.
	 * @param array $childIds Array of placement childs id.
	 */
	private static function updateTargetOfPlacedChilds( $user, $layoutId, $oriLayoutTarget, $newLayoutTarget, $childIds )
	{
		foreach( $childIds as $childId ) {
			$childTargets = self::getTargets( '', $childId ); // Get all targets of the placed object
			if( empty($childTargets) ) { // Rule: Add/move object target only when file have object target
				continue;
			}
			$targetsChanged = false;
			$sameNewIssueFound = false;
			$parentRelations = DBObjectRelation::getObjectRelations( $childId, 'parents', 'Placed', true );

			foreach( $childTargets as $key => $childTarget ) {
				// Rule: Remove the placed object issue when it matches the layout original issue and
				//       it didn't placed on another Layout which is still targeted for the original issue
				if( $childTarget->Issue->Id == $oriLayoutTarget->Issue->Id ) {
					$sameOriginalIssueFound = false;
					if( $parentRelations ) foreach( $parentRelations as $parentRelation ) {
						if( $parentRelation['parent'] != $layoutId ) {
							$otherParentTargets = self::getTargets( '', $parentRelation['parent'] );
							foreach( $otherParentTargets as $otherParentTarget ) {
								if( $otherParentTarget->Issue->Id == $oriLayoutTarget->Issue->Id ) {
									$sameOriginalIssueFound = true;
									break 2;
								}
							}
						}
					}
					if( !$sameOriginalIssueFound ) { // When no issue found targeted on another layout, remove the issue
						unset( $childTargets[$key] );
						$targetsChanged = true;
					}
				} elseif( $childTarget->Issue->Id == $newLayoutTarget->Issue->Id ) { // When same issue found, further check on the edition differences
					$newEditions = array();
					if( $newLayoutTarget->Editions ) foreach( $newLayoutTarget->Editions as $newEdition ) {
						$sameEditionFound = false;
						if( $childTarget->Editions ) foreach( $childTarget->Editions as $childEdition ) {
							if( $childEdition->Name == $newEdition->Name ) {
								$sameEditionFound = true;
								break;
							}
						}
						if( !$sameEditionFound ) { // When new edition not found in placed object target, add it.
							$newEditions[] = $newEdition;
							$targetsChanged = true;
						}
					}
					$childTarget->Editions = array_merge( $childTarget->Editions, $newEditions );
					$sameNewIssueFound = true;
					break;
				}
			}
			if( !$sameNewIssueFound ) { // When new target issue not found in placed object targets, add the target
				$childTargets[] = $newLayoutTarget;
				$targetsChanged = true;
			}

			if( $targetsChanged ) {

				// Because we have used unset(), that could remove index 0 and the core 
				// server assumes that $targets[0] is always there when count($targets) > 0,
				// we repair the keys with a new [0...N-1] range.
				$newKeys = range( 0, count($childTargets)-1 );
				$childTargets = array_combine( $newKeys, array_values($childTargets) );
				
				// Update placed object targets
				$metaData = null;
				self::saveTargets( $user, $childId, $childTargets, $metaData );

				// Validate meta data and targets (including validation done by Server Plug-ins)
				BizObject::validateMetaDataAndTargets( $user, $metaData, $childTargets, null );
			}
		}
	}
	
	/**
	 * Returns an array of issue-id's from an array of $targets
	 *
	 * @param array $targets The array of targets to get the issue id's from.
	 * @return array The calculated array of issue ids
	 */
	
	public static function getIssueIds($targets)
	{
		$issueids = array();
		if (is_array($targets)) {
			foreach ($targets as $target) {
				if ($target->Issue) {
					$issueids[] = $target->Issue->Id;
				}
			}
		}
		return $issueids;
	}
	
	/**
	 * Checks if $targets contains an overrule issue.
	 * In that case there may only be exactly one target.
	 * The check on the number of $targets is performed first 
	 * for performance reasons
	 * 
	 * @param array $targets
	 * @throws BizException Throws BizException if the rule is violated
	 * @return bool
	 */
	private static function checkForOverruleIssues($targets)
	{
		if (empty($targets)) {
			return true;
		}
		
		if (!is_array($targets)) {
			return true;
		}
		
		if (count($targets)<=1) {
			return true;	
		}
		
		$issuelist = array();
		foreach ($targets as $target) {
			if ($target->Issue) {
				$issuelist[] = $target->Issue->Id;
			}
		}

		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		$overruleissueid = DBIssue::hasOverruleIssue($issuelist);
		if ($overruleissueid !== false) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'IssueId ('.$overruleissueid.') is overrule issue' );
		}
		
		return true;
	}
	
	/**
	 * Calculate the deadlines for the given object and set the object's deadline.
	 *
	 * @param int $objectId Object to determine its deadline.
	 * @param Target[] $newTargets List of targets of which the deadline will be determined from.
	 */
	private static function updateDeadlines($objectId, $newTargets)
	{
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		$row = DBObject::getObjectRows($objectId);
		$categoryId = $row['section'];
		$stateId = $row['state'];
		$issueIds = self::getIssueIds($newTargets);

		// Determine if it is normal brand or overruleIssue.
		$overruleIssueId = 0;
		if( count( $issueIds ) == 1 ) { // When there are more than 1 issue targeted to an Object, it's definitely not an overruleIssue, so don't need to check.
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$overruleIssueId = DBIssue::isOverruleIssue( $issueIds[0] ) ? $issueIds[0] : 0;
		}

		$pubId = $row['publication'];
		require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
		if( BizPublication::isCalculateDeadlinesEnabled( $pubId, $overruleIssueId ) ) {
		DBObject::objectSetDeadline($objectId, $issueIds, $categoryId, $stateId);
	}
	}
	
	/**
	 * Automatically change object/relation targets
	 * See "Improved Dossier Usability" spec
	 * 
	 * When a Dossiers Issue of a specfic channel is replaced with another Issue of that same channel all objects within the
	 * dossier assigned to that Issue will be assigned to the new Issue instead. 
	 * In the case of a Layout the Issue will also be applied directly to the Layout in case the previous dossier Issue assignment is 
	 * the same as the Layouts issue, except when the Layout is assigned to or from an overrule issue.
	 * In case a dossier is moved to a new channel (not common practise) the relational-targets will not be updated and the old one's
	 * will be removed.
	 * 
	 * If a Layout get's a new target always make sure that the relational-targets of the placed objects are updated. This will be done
	 * if the new issue belongs to the same channel as the old issue but also if the layout is moved to a new channel.
	 *
	 * @param array $origTargets
	 * @param array $newTargets
	 * @param MetaData $objectMetaData
	 */
	protected static function automaticTargetAssignments(array $origTargets, array $newTargets, MetaData $objectMetaData)
	{
		// when a Dossiers/Layout's Issue of a specific channel is replaced with another Issue of that same channel all objects within the
		// dossier (or placed on the layout) assigned to that Issue will be assigned to the new Issue instead
		$parentType = $objectMetaData->BasicMetaData->Type;
		if ($parentType == 'Dossier' || $parentType == 'Layout' || $parentType == 'PublishForm'|| $parentType == 'DossierTemplate'){
			require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
			require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
			
			// put orig targets in channel array
			$origChannels = array();
			foreach ($origTargets as $origTarget){
				if (! isset($origChannels[$origTarget->PubChannel->Id])){
					$origChannels[$origTarget->PubChannel->Id] = array();
				}
				$origChannels[$origTarget->PubChannel->Id][$origTarget->Issue->Id] = $origTarget;
			}
			// put new targets in channel array
			$newChannels = array();
			foreach ($newTargets as $newTarget){
				if (! isset($newChannels[$newTarget->PubChannel->Id])){
					$newChannels[$newTarget->PubChannel->Id] = array();
				}
				$newChannels[$newTarget->PubChannel->Id][$newTarget->Issue->Id] = $newTarget;
			}
			$childIds = array(); // Stores object ids of children who must be re-indexed.
			$childIdsInTrash = array(); // Same as $childIds except that these child ids reside in the Trash.
			// detect if an issue has been replaced (in the same channel)
			foreach ($newChannels as $newChannelId => $issues){
				// count of issues must be the same
				if (isset($origChannels[$newChannelId]) && count($issues) == count($origChannels[$newChannelId])) {
					$diffNew = array_diff_key($issues, $origChannels[$newChannelId]);
					LogHandler::Log(__CLASS__, 'DEBUG', "PubChannel $newChannelId has the following different issue ids: " . implode(',',array_keys($diffNew)));
					if (count($diffNew) == 1){
						// we can only handle one difference
						$diffOrig = array_diff_key($origChannels[$newChannelId], $issues);
						$origTarget = current($diffOrig);
						$newTarget = current($diffNew);
						//BZ#35719 If an object is moved to and/or from an overrule issue, object relations and targets will not change.
						if( !$origTarget->Issue->OverrulePublication && !$newTarget->Issue->OverrulePublication ) {
							LogHandler::Log(__CLASS__, 'DEBUG', "Issue id " . $origTarget->Issue->Id . " has been replaced by " . $newTarget->Issue->Id);
							// now replace all related targets
							if ( $parentType == 'Dossier' || $parentType == 'DossierTemplate' ) {
									$objectRelations = DBObjectRelation::getObjectRelations($objectMetaData->BasicMetaData->ID, 'childs', 'Contained', true);
							} else {
									$objectRelations = DBObjectRelation::getObjectRelations($objectMetaData->BasicMetaData->ID, 'childs', 'Placed', true);
							}
							$objectRelationIds = array_keys($objectRelations);
							DBTarget::updateRelationTargets($objectRelationIds, $origTarget, $newTarget);
								foreach ( $objectRelations as $objectRelation ) {
									self::sortObjectRelationChildrenByArea( $objectRelation, $childIds, $childIdsInTrash );
							}
							foreach ($objectRelations as $objectRelation) {
								self::reassignContainedObjects( $objectRelation, $origTarget, $newTarget);
								//updateTargets calls saveTargets, saveTargets calls automaticTargetAssignments
								// So the relational-targets of placed objects will also be updated.
							}
						}
					}
				// Layout is moved to new channel. The object-target of the layout is used to update the relational-targets.
				// A layout can have only one object-target so $origTargets and $newTargets will only contain one target each.
				} elseif (($parentType == 'Layout' || $parentType == 'PublishForm') && isset($origTargets[0]) && isset($newTargets[0])) {
					$objectRelations = DBObjectRelation::getObjectRelations($objectMetaData->BasicMetaData->ID, 'childs', 'Placed', true);
					$objectRelationIds = array_keys($objectRelations);
					DBTarget::updateRelationTargets($objectRelationIds, $origTargets[0], $newTargets[0]);
					foreach ( $objectRelations as $objectRelation ) {
						self::sortObjectRelationChildrenByArea( $objectRelation, $childIds, $childIdsInTrash );
					}
				// Dossier had no targets and is assigned to a target.
				// Create relational targets for the objects in the dossier. BZ#25493	
				} elseif (( $parentType == 'Dossier' || $parentType == 'DossierTemplate' ) && empty( $origTargets ) && !empty($newTargets)) {
					$objectRelations = DBObjectRelation::getObjectRelations($objectMetaData->BasicMetaData->ID, 'childs', 'Contained', true);
					if ( $objectRelations ) foreach ( $objectRelations as $objectRelationId => $objectRelation ) {
						foreach( $newTargets as $newTarget ) {
							self::reassignContainedObjects( $objectRelation, null, $newTarget );
							DBTarget::addObjectRelationTarget($objectRelationId, $newTarget->PubChannel->Id, $newTarget->Issue->Id, $newTarget->Editions );
						}
						self::sortObjectRelationChildrenByArea( $objectRelation, $childIds, $childIdsInTrash );
					}
				}
			}

			// Index the child Objects residing in the Workflow area.
			$childIdsToIndex = array();
			require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
			foreach( $childIds as $childId ) {
				if ( !BizRelation::manifoldPlacedChild( $childId )) {
					$childIdsToIndex[ $childId ] = $childId;
				}
			}
			// Make sure that children with new relational targets are re-indexed.
			if (!empty($childIdsToIndex)) {
				require_once BASEDIR. '/server/bizclasses/BizSearch.class.php';
				BizSearch::indexObjectsByIds($childIdsToIndex, true, array('Workflow'));
			}

			// Index the child Objects residing in the Trash area.
			$childIdsInTrashToIndex = array();
			require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
			foreach(  $childIdsInTrash as $childIdInTrash ) {
				if ( !BizRelation::manifoldPlacedChild( $childIdInTrash )) {
					$childIdsInTrashToIndex[ $childIdInTrash ] = $childIdInTrash;
				}
			}
			// Make sure that children in the Trash area with new relational targets are re-indexed.
			if (!empty($childIdsInTrashToIndex)) {
				require_once BASEDIR. '/server/bizclasses/BizSearch.class.php';
				BizSearch::indexObjectsByIds($childIdsInTrashToIndex, true, array('Trash') );
			}
		}
	}

	/**
	 * If the dossier is moved to another issue then the object targets of the children are replaced if the condition
	 * is met that the object was targeted for the original target.
	 * If the dossier is moved from 'unassigned' to an issue the object target of a child object is replaced if that
	 * child is only part of the involved dossier (EN-34271).
	 * @param array $relation A parent-child relation
	 * @param Target or null $origTarget Original target or null if there is no original target (unassigned)
	 * @param Target $newTarget The new target
	 */
	static private function reassignContainedObjects( $relation, $origTarget, $newTarget )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		$childTargets = self::getTargets('', $relation['child']);
		$newTargets = array();
		foreach ($childTargets as $childTarget){
			if ( $origTarget) {
				if ( $childTarget->PubChannel->Id == $origTarget->PubChannel->Id &&
					$childTarget->Issue->Id == $origTarget->Issue->Id){
					$newTargets[] = $newTarget;
				} else {
					$newTargets[] = $childTarget;
				}
			} else { // No original target ('unassigned')
				if ( BizRelation::inSingleDossier($relation['child'])) {
					$newTargets[] = $newTarget;
				}
			}
		}
		if( !empty($newTargets) ) { // EN-19188 - Avoid empty target to be updated.
			self::updateTargets(BizSession::getShortUserName(), $relation['child'], $newTargets);
		}
	}
	
	/**
	 * Sort ObjectRelation child Objects by their area and return the result.
	 *
	 * @param array $objectRelation Key-Value array where the key is the ObjectRelationID and the value is the Relation information.
	 * @param array &$childIds Child objects that found in the Workflow area will be collected here.
	 * @param array &$childIdsInTrash Child Objects that found in the Trash area will be collected here.
	 */
	static private function sortObjectRelationChildrenByArea( $objectRelation, &$childIds, &$childIdsInTrash )
	{
		if( (substr( $objectRelation['type'], 0, 7 ) == 'Deleted' )  && // When having Deletedxxxx relation, the child could be residing in the Trash area
				!DBObject::objectExists( $objectRelation['child'], 'Workflow' ) ) { // so, check here.
			$childIdsInTrash[$objectRelation['child']] = $objectRelation['child']; // Child Object residing in the Trash area.
		} else {
			$childIds[$objectRelation['child']] = $objectRelation['child']; // Child Object residing in the Workflow area.
		}
	}
	
	/**
	 * Add a target to an existing target array.
	 * This function is useful to prevent double targets.
	 *
	 * @param array $targets
	 * @param Target $srcTarget
	 */
	public static function addToTargetArray(array &$targets, Target $srcTarget)
	{
		// check if target doesn't exist already
		$exists = false;
		foreach ($targets as $target){
			if ($target->PubChannel->Id == $srcTarget->PubChannel->Id
				&& $target->Issue->Id == $srcTarget->Issue->Id){
				$exists = true;
				// merge Editions and leave PublishedDate and PublishedVersion
				// if Editions are null it means all editions, so skip merge editions
				if (! is_null($target->Editions)){
					foreach ($srcTarget->Editions as $srcEdition){
						$editionExists = false;
						foreach ($target->Editions as $edition){
							if ($edition->Id == $srcEdition->Id) {
								$editionExists = true;
								break;
							}
						}
						if (! $editionExists) {
							$target->Editions[] = $srcEdition;
						}
					}
				}
				break;
			}
		}
		// add to array if it doesn't exist
		if (! $exists){
			$targets[] = $srcTarget;
		}
	}

	/**
	 * Returns all the Targets for the specified ObjectRelation ID.
	 *
	 * @static
	 * @param integer $objectRelationId The ObjectRelation ID for which to retrieve the targets.
	 * @return Target[] An Array of Targets matching the ObjectRelation ID.
	 */
	public static function getTargetByObjectRelationId( $objectRelationId )
	{
		return DBTarget::getTargetsbyObjectrelationId( $objectRelationId );
	}

	/**
	 * Deletes the PublishForm that belongs to the Target to be deleted.
	 *
	 * Iterates through the Targets to be removed from a Dossier and checks if
	 * there's any PublishForm that belongs to this target contained in the Dossier.
	 * If the PublishForm is found, it will be moved to the TrashCan, otherwise this
	 * PublishForm will become an orphan object(PublishForm that has no publication channel).
	 *
	 * @param int $objId
	 * @param array $targetsToBeDeleted
	 * @param string $user
	 */
	public static function removePublishForm( $objId, $targetsToBeDeleted, $user )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/bizclasses/BizDeletedObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		$objType = DBObject::getObjectType( $objId );
		if( $objType == 'Dossier' ) {
			$dossierRelations = BizRelation::getObjectRelations( $objId, null, true, 'childs' );
			if( $dossierRelations ) foreach( $dossierRelations as $dossierRelation ) {
				if( $dossierRelation->ChildInfo->Type == 'PublishForm' ) {
					if( $dossierRelation->Targets ) foreach( $dossierRelation->Targets as $dossierRelationTarget ) {
						if( $targetsToBeDeleted ) foreach( $targetsToBeDeleted as $targetToBeDeleted ) {
							if( $dossierRelationTarget->PubChannel->Id == $targetToBeDeleted->PubChannel->Id &&
								$dossierRelationTarget->Issue->Id == $targetToBeDeleted->Issue->Id ) {
								// Delete the Form.
								/*$deleted = */BizDeletedObject::deleteObject( $user, $dossierRelation->ChildInfo->ID, false, array( 'Workflow') );
								break; // One Target can only have one Form instantiated, so when one is found, break the TargetsToBeDeleted loop.
							}
						}
					}
				}
			}
		}
	}

	/**
	 * To check if the client that is handling the target removal is aware of PublishForm.
	 *
	 * Not all clients are capable to remove target that supports PublishForm. For Content Station
	 * v8 and below, they are not aware of PublishForm, hence when a user attempts to remove a
	 * target that supports PublishForm via Content Station v8 and below, the function will throw
	 * BizException to stop the operation.
	 *
	 * @param array $objTargets List of object targets to be removed, to be validated if the client can handle the removal.
	 * @throws BizException When Content Station v8 and below tries to remove a target that supports PublishForm.
	 */
	public static function canRemoveTargets( $objTargets )
	{
		$clientVersion = BizSession::getClientVersion( null, null, 3 );
		$appName = BizSession::getClientName();

		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		if( $objTargets ) foreach( $objTargets as $objTarget ) {
			$canDossierHavePublishForm = BizServerPlugin::runChannelConnector( $objTarget->PubChannel->Id,
				'doesSupportPublishForms', array(), false );
			if( $canDossierHavePublishForm ) { // Channel that supports PublishForm, but can the client handle PublishForm? Check further.
				if( ( $appName == 'Content Station' && version_compare( $clientVersion, '9.0.0', '<' ) ) ) {
					// Clients that are not PublishForm aware, so cannot allow the to remove the target.
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Client does not support PublishForm enabled Targets.' );
				}
			}
		}
	}

	/**
	 * Checks if an object has a target. This can be an object target or a relational target. In case of the relational
	 * target the object will act as child. The reason is that a parent object always has an object target. So for the
	 * parent objects the check on object targets will always be positive.
	 *
	 * @param int $objectId
	 * @return bool True if a relational/object target is found.
	 */
	static public function hasTarget( $objectId )
	{
		if ( DBTarget::hasObjectTarget( $objectId )) {
			return true;
		}
		if ( DBTarget::hasRelationalTargetForChildObject( $objectId ) ) {
			return true;
		}
		return false;
	}
}
