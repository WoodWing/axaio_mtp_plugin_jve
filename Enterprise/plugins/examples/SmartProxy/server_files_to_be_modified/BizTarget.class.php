<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';

class BizTarget
{
    public static function createTargets( $user, $objectId, $targets )
    {
    	$user = $user; // make code analyzer happy
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

		return $retTargets;        
    }
    
   public static function createObjectrelationTargets( $user, $objectrelationId, $targets )
    {
    	$user = $user; // make code analyzer happy
    	self::fixPearSoapTargets( $targets ); // PEAR SOAP collection fixes

    	self::checkForOverruleIssues($targets);
    	   	
    	if( !$objectrelationId || trim($objectrelationId) == '' ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		if( !$targets || !sizeof( $targets ) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
		}
        self::addObjectrelationTargets( $objectrelationId, $targets );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	
    }    
    
    public static function updateTargets( $user, $objectId, $targets )
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
		BizObject::validateMetaDataAndTargets( $user, $metaData, $targets );

		$retTargets = DBTarget::getTargetsByObjectId( $objectId );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	
		
		self::updateDeadlines($objectId, $retTargets);
		
		return $retTargets;        
    }
    
    public static function deleteTargets( $user, $objectId, $targets )
    {
    	$user = $user; // make code analyzer happy
    	self::fixPearSoapTargets( $targets ); // PEAR SOAP collection fixes
		// params validation
        if( !$objectId || trim($objectId) == '' ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{OBJECT}' ) );
		}
		if( !$targets || !sizeof( $targets ) ) {
			throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNEL}' ) );
		}
		// perform deletion
		$userfull = BizUser::resolveFullUserName($user);
		self::removeTargets( $userfull, $objectId, $targets );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}	

		// Delete object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
		if (ATTACHSTORAGE == 'FILE') {
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
		}
		
		$newTargets = DBTarget::getTargetsByObjectId( $objectId );
		if( DBTarget::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
		}
		
		self::updateDeadlines($objectId, $newTargets);
		
    }
    
    /**
     * Removes given targets from an object.
     *
     * @param string $userFullname
     * @param int $objectId
     * @param array $targets to delete
    **/
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
            DBTarget::removeSomeTargetsByObject( $objectId, $channelId, $issueId );
            // send DeleteObjectTarget event
    		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			if (DBObject::getObjectType($objectId) == 'Dossier') {
				DBTarget::removeTargetsOfContainedObjects($objectId, $channelId, $issueId);
			}            
            new smartevent_deleteobjecttarget(BizSession::getTicket(), $userFullname, $objectId, $target);
        }
    }

    public static function getTargets( $user, $objectId )
    {
    	$user = $user; // make code analyzer happy
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
    	$issueIds = array();
    	$issueIds = DBTarget::getRelationalTargetIssuesForChildObject( $objectId );
    	return $issueIds;
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
     *  @param int $objectId  ID of object to add the targets to.
     *  @param array $targets Array of Target to add to object.
    **/
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
        }

		// Update object's 'link' files (htm) in <FileStore>/_BRANDS_ folder
		if (ATTACHSTORAGE == 'FILE') {
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
            $publisheddate = $target->PublishedDate;
            $version = isset($target->Version) ? $target->Version : '';
            DBTarget::addObjectRelationTarget( $objectrelationId, $channelId, $issueId, $editions, $publisheddate, $version);
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
	 * @param array $metaData Optional. The MetaData of the object. Pass null if you don't have it; this function will get it when needed.
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

		// Prepare hidden feature; Let placed objects move along with their layouts (being moved).
		if( defined('MOVE_PLACEMENTS') && strtoupper(MOVE_PLACEMENTS) =='ON' ) {
			if( $metaData->BasicMetaData->Type == 'Layout' ) {
				self::movePlacedObjects( $user, $objectId, $targets );
			}
		}
		
		/* Commented out; does have nothing to do with saving targets... maybe we might need it elsewhere.
		// Resolve publication name based on id
		$pubId = $basicMD->Publication->Id;
		if( !is_numeric($pubId) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'PublicationId (for object ID='.$objectId.')' );
		}
		$pubName = DBPublication::getPublicationName( $pubId );
		if( empty($pubName) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'PublicationId ('.$pubId.')' );
		}
		$basicMD->Publication->Name = $pubName;
		
		// Resolve category/section name based on id
		$catId = $basicMD->Category->Id;
		if( !is_numeric($catId) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'CategoryId / SectionId (for object ID='.$objectId.')' );
		}
		$catName = DBPublication::getSectionName( $catId );
		if( empty($catName) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', 'CategoryId / SectionId ('.$catId.')' );
		}
		$basicMD->Category->Name = $catName;
		*/
		
		// get current targets before targets are added
		$dbTargets = self::getTargets( '', $objectId );
		
		require_once BASEDIR . '/server/smartevent.php';
		$userfull = BizUser::resolveFullUserName($user);
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

			// Replace editions per given isssue; the arrived set of editions overrules the editions stored in database.
			if( $issueId ) {
				DBTargetEdition::removeSomeTargetEditionsByObject( $objectId, $channelId, $issueId, null ); // all editions for the issue 
			}
			
			// Store the validated target at DB for this object
			DBTarget::addTarget( $objectId, $channelId, $issueId, $target->Editions );
			if( DBTarget::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBTarget::getError() );
			}
			// send UpdateObjectTarget event
			new smartevent_updateobjecttarget(BizSession::getTicket(), $userfull, $objectId, $target);
		}
		
		// automatic target assignment, before orig targets are deleted
		self::automaticTargetAssignments($dbTargets, $targets, $metaData);
		
		// Remove object targets that are in db, but not given by client app.
		// Assumed is that client app provides *all* object targets.
		if( !empty($dbTargets) ) foreach( $dbTargets as $dbTarget ) {
			$sameChannel = false;
			$sameIssue = false;
			if( !empty($targets) ) foreach( $targets as $objTarget ) {
				$sameChannel = ( !isset($target->PubChannel->Id) && !isset($dbTarget->PubChannel->Id) ) ||
					( isset($objTarget->PubChannel->Id) && isset($dbTarget->PubChannel->Id) && $objTarget->PubChannel->Id == $dbTarget->PubChannel->Id );
				$sameIssue = ( !isset($target->Issue->Id) && !isset($dbTarget->Issue->Id) ) ||
					( isset($objTarget->Issue->Id) && isset($dbTarget->Issue->Id) && $objTarget->Issue->Id == $dbTarget->Issue->Id );
				if( $sameChannel && $sameIssue ) {
					break; // found
				}
			}
			if( !$sameChannel || !$sameIssue ) {
				$channelId = isset($dbTarget->PubChannel->Id) ? $dbTarget->PubChannel->Id : 0;
				$issueId = isset($dbTarget->Issue->Id) ? $dbTarget->Issue->Id : 0;
				DBTarget::removeSomeTargetsByObject( $objectId, $channelId, $issueId, null );
				// send DeleteObjectTarget event
            	new smartevent_deleteobjecttarget(BizSession::getTicket(), $userfull, $objectId, $dbTarget);
			}
		}
		// >>> Moved validation calls to calling functions fix BZ#13522
		// Validate meta data and targets (including validation done by Server Plug-ins)
		//BizObject::validateMetaDataAndTargets( $user, $metaData, $targets );
		// <<<
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
	 * When the issue of the layout is changed, this function changes the issues for the placed objects.
	 * This only happens when the given $id is a layout and if the MOVE_PLACEMENTS option is enabled.
	 *
	 * @param string $user Short user name (=id)
	 * @param string $id Object id
	 * @param array $targets List of new Target objects
	 */
	private static function movePlacedObjects( $user, $id, $targets )
	{
		// Only support single targets to ease checksum if layout has moved to different issue
		if( count($targets) == 1 ) { // should always be 1 for layouts
			$newIssue = $targets[0]->Issue->Id; // new issue selected by user
			if( $newIssue ) {
				$curTargets = self::getTargets( '', $id ); // get current layout issue (from db)
				if( count($curTargets) == 1 ) { // should always be 1 for layouts
					$curIssue = $curTargets[0]->Issue->Id;
					if( $curIssue != $newIssue ) { // layout is about to move to different issue?
						$singlePlacements = self::getSinglePlacedChilds( $id, $curIssue, $newIssue );
						if ( count( $singlePlacements ) > 0 ) {
							self::updateChilds( $user, $curIssue, $newIssue, $singlePlacements );
						}
					}
				}
			}
		}
	}	

	/*
	 * Get objects of given layout that are only placed one time. <br/>
	 * Throws exception when one of those placements is currently locked. <br/>
	 * Result can be passed to (@link updateChilds) function. <br/>
	 *
	 * @param string $id Unique id of layout (=parent) <br/>
	 * @param $curIssue Current layout issue id <br/>
	 * @param $newIssue Newly choosen layout issue id <br/>
	 * @return array of placement ids (=childs) <br/>
	 */
	private static function getSinglePlacedChilds( $id, $curIssue, $newIssue )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';

		// Get all placed objects (=childs) of the layout (ignore planned/contained/deleted relations)
		$childs = array();
		$dbDriver = DBDriverFactory::gen();
		$sth = DBObjectRelation::getObjectRelation( $id, true, 'Placed' );
		while (($child = $dbDriver->fetch($sth)) ) {
		
			$childid = $child['child'];
			// check if this child in unique for this parent
			$sthc = DBObjectRelation::getObjectRelation( $childid, false, 'Placed' );
			$onlyme = true;
			while (($otherparent = $dbDriver->fetch($sthc)) ) {
				if ($otherparent['parent'] != $id) {
					$onlyme = false;					
					break;
				}
			}
			
			// Remember to update child if our layout is the only parent. So for example images 
			// that are places twice won't move when any of its parents move.
			if ($onlyme) {
				// log child move candidates
				LogHandler::Log('objectservices', 'INFO', 'Implicitly moving placed object: '.
					"layout id [$id], placed object id [$childid], ".
					"current issue id [$curIssue], new issue id [$newIssue]");
				$childs[] = $childid;
			}
		}
		return $childs;
	}
	
	/*
	 * Moves placed objects along with their layout by updating targets with a new given issue. <br/>
	 *
	 * @param string $user Short user name (=id)
	 * @param $curIssue Layout's current issue id <br/>
	 * @param $newIssue Layout's newly assigned issue id <br/>
	 * @param $childs array of placement ids (=childs) to move together with layout.<br/>
	 */
	private static function updateChilds( $user, $curIssue, $newIssue, $childs )
	{
		foreach( $childs as $childid ) {
			// Get all targets of the placed object
			$targets = BizTarget::getTargets( '', $childid );
			// Lookup matching issues with layout and update them with new issue to let
			// placed object move along with layout.
			$targetsChanged = false;
			
			if( count($targets) == 1 ) { // rule: when there is only one target, we move childs anyway
				$targetsChanged = true; 
				$targets[0]->Issue = new Issue( $newIssue );
			} else { // rule: when there are many targets, we only move when one of the issues matches with parent layout!
				foreach( $targets as $target ) { 
					if( isset($target->Issue) && $target->Issue->Id == $curIssue ) {
						$target->Issue = new Issue( $newIssue );
						$targetsChanged = true;
						break;
					}
				}
			}
			// Update all targets with
			if( $targetsChanged ) {
				$metaData = null;
				self::saveTargets( $user, $childid, $targets, $metaData ); // this is recursion that stops because the childs are not layouts

				// Validate meta data and targets (including validation done by Server Plug-ins)
				BizObject::validateMetaDataAndTargets( $user, $metaData, $targets );
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
	 * @return true if ok, throws a BizException if the rule is violated 
	**/
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
	
	private static function updateDeadlines($objectId, $newTargets)
	{
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		$row = DBObject::getObjectRows($objectId);
		$categoryId = $row['section'];
		$stateId = $row['state'];
		$issueIds = self::getIssueIds($newTargets);
		DBObject::objectSetDeadline($objectId, $issueIds, $categoryId, $stateId);
	}
	
	/**
	 * Automatically change object/relation targets
	 * See "Improved Dossier Usability" spec
	 * 
	 * When a Dossiers Issue of a specific channel is replaced with another Issue of that same channel all objects within the
	 * dossier assigned to that Issue will be assigned to the new Issue instead. 
	 * In the case of a Layout the Issue will also be applied directly to the Layout in case the previous dossier Issue assignment is 
	 * the same as the Layouts issue.
	 *
	 * @param array $origTargets
	 * @param array $newTargets
	 * @param MetaData $objectMetatData
	 */
	protected static function automaticTargetAssignments(array $origTargets, array $newTargets, MetaData $objectMetatData)
	{
		// when a Dossiers Issue of a specific channel is replaced with another Issue of that same channel all objects within the
		// dossier assigned to that Issue will be assigned to the new Issue instead
		if ($objectMetatData->BasicMetaData->Type == 'Dossier'){
			require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
			
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
			// detect if an issue has been replaced (in the same channel)
			foreach ($newChannels as $newChannelId => $issues){
				// count of issues must be the same
				if (isset($origChannels[$newChannelId]) && count($issues) == count($origChannels[$newChannelId])){
					$diffNew = array_diff_key($issues, $origChannels[$newChannelId]);
					LogHandler::Log(__CLASS__, 'DEBUG', "PubChannel $newChannelId has the following different issue ids: " . implode(',',array_keys($diffNew)));
					if (count($diffNew) == 1){
						// we can only handle one difference
						$diffOrig = array_diff_key($origChannels[$newChannelId], $issues);
						$origTarget = current($diffOrig);
						$newTarget = current($diffNew);
						LogHandler::Log(__CLASS__, 'DEBUG', "Issue id " . $origTarget->Issue->Id . " has been replaced by " . $newTarget->Issue->Id);
						// now replace all related targets
						$objectRelations = DBObjectRelation::getObjectRelations($objectMetatData->BasicMetaData->ID, 'childs', 'Contained', true);
						$objectRelationIds = array_keys($objectRelations);
						DBTarget::updateRelationTargets($objectRelationIds, $origTarget, $newTarget);
						// in case of a layout, change the object target too
						$user = BizSession::getShortUserName();
						foreach ($objectRelations as $objectRelation){
							if ($objectRelation['subid'] == 'Layout'){
								$layoutTargets = self::getTargets('', $objectRelation['child']);
								$newLayoutTargets = array();
								foreach ($layoutTargets as $layoutTarget){
									if ($layoutTarget->PubChannel->Id == $origTarget->PubChannel->Id && $layoutTarget->Issue->Id == $origTarget->Issue->Id){
										$newLayoutTargets[] = $newTarget;
									} else {
										$newLayoutTargets[] = $layoutTarget;
									}
								}
								self::updateTargets($user, $objectRelation['child'], $newLayoutTargets);
							}
						}
					}
				}
			}
		}
		
	}
}
