<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

class BizAdmPublication
{
	/**
	 * Checks if an user has admin access to the publication. System admins have access to all pubs.
	 *
	 * @param string Short name of user.
	 * @param integer Publication id. Null to check if user is admin in some or more publications (just any).
	 * @throws BizException When user has no access.
	 */
	static private function checkPubAdminAccess( $usr, $pubId )
	{
		$dbDriver = DBDriverFactory::gen();
		if( !self::hasPubAdminAccess( $usr, $pubId ) ) {
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}
	}

	/**
	 * Checks if an user has admin access to the publication. System admins have access to all pubs.
	 *
	 * @param string Short name of user.
	 * @param integer Publication id. Null to check if user is admin in some or more publications (just any pub).
	 * @return boolean Whether or not the user has access.
	 */
	static private function hasPubAdminAccess( $usr, $pubId )
	{
		$dbDriver = DBDriverFactory::gen();
		if( is_null($pubId) ) {
			return hasRights( $dbDriver, $usr ) || // system admin?
					( publRights( $dbDriver, $usr ) ); // any pub admin?
		} else {
			return hasRights( $dbDriver, $usr ) || // system admin?
					( publRights( $dbDriver, $usr ) && checkPublAdmin( $pubId, false ) ); // explicit pub admin?
		}
	}

	/**
	 * Checks if an user has admin access to the entire system.
	 *
	 * @param string Short name of user.
	 * @throws BizException When user has no access.
	 */
	static private function checkSysAdminAccess( $usr )
	{
		$dbDriver = DBDriverFactory::gen();
		if( !hasRights( $dbDriver, $usr ) ) { // not a system admin?
			throw new BizException( 'ERR_AUTHORIZATION', 'Server', $dbDriver->error() );
		}
	}

	/**
	 * Validates a new pub/issue/section/edition name to be created.
	 *
	 * @param string $newName The name to be validated
	 * @throws BizException when name not valid
	 */
	public static function validateNewName( $newName )
	{
		// check not empty
		if( trim($newName) == '' ) {
			throw new BizException( 'ERR_NOT_EMPTY', 'client', null, null);
		}

		// check max char count
		//
		// Note#001: If we have used HTML input fields with max 255 human readable characters.
		// However, charaters might be 2-byte of 3-byte, especially for Asian languages.
		// The database has max storage field set to 255, which are bytes, not characters!
		// So we have to check if the type string is not exceeding the db limit.
		// If we don't, string are tuncated by db, which might be non-Unicode.
		// If unlucky, the db could then accidentally cut the last char into pieces.
		// Then, users might not be able to login anymore because SOAP response contains bad UTF-8!
		//
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		if( UtfString::byteCount( $newName ) > 255 ) {
			throw new BizException( 'ERR_NAME_INVALID', 'client', null, null);
		}
	}

	// ------------------------------
	// --- PUBLICATION OPERATIONS ---
	// ------------------------------

	/**
	 * Create Publication Object
	 *
	 * Returns new created publication object
	 *
	 * @param string $usr Short name of user.
	 * @param array $subReq RequestModes
	 * @param array $pubs array of new publications that will create
	 * @throws BizException Throws BizException when there's error during publication object creation.
	 * @return array of new created Publication objects
	 */
	public static function createPublicationsObj( $usr, /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubs )
	{
		self::checkSysAdminAccess( $usr ); // Check if user is system(!) admin

		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';

		foreach( $pubs as $pub ) {
			$pub->Name = trim( $pub->Name ); //BZ#12402
			self::validateNewName( $pub->Name );
		}

		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
		$newpubs = DBAdmPublication::createPublicationsObj( $pubs, $typeMap );
		if( DBAdmPublication::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmPublication::getError() );
		}
		return $newpubs;
	}

	/**
	 * Checks all SortOrder fields and updates when list is damaged.
	 *
	 * If a publication has a sort order, this order will be respected. If it has no order, the order will be set to
	 * current highest one plus 1 etc.
	 * Publications without order could happen after record removal, DB migration, or any corruption.
	 * The passed list of publications must be complete(!) and will be repaired (returned).
	 *
	 * @param AdmPublication Publication
	 */
	private static function repairPublicationsSortOrder( &$pubs )
	{
		$highestOrder = 0;
		$damagedPubs = array();
		foreach( $pubs as $pub ) {
			if( !$pub->SortOrder ) {
				$damagedPub = new AdmPublication();
				$damagedPub->Id = $pub->Id;
				$damagedPubs[] = $damagedPub;
			} else {
				if( $pub->SortOrder > $highestOrder ) {
					$highestOrder = $pub->SortOrder;
				}
			}
		}

		if( $damagedPubs ) foreach( $damagedPubs as $damagedPub ) {
			$highestOrder++;
			$damagedPub->SortOrder = $highestOrder;
		}

		if( $damagedPubs ) { // save repairs
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
			$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
			DBAdmPublication::modifyPublicationsObj( $damagedPubs, $typeMap );
		}
	}

	/**
	 * List Publication Objects
	 *
	 * Returns publication objects for the all/specified publication
	 *
	 * @param string $usr Short name of user.
	 * @param array $subReq RequestModes
	 * @param array $pubIds List of publication ids.
	 * @throws BizException Throws BizException on failure.
	 * @return AdmPublication[]
	 */
	public static function listPublicationsObj( $usr, /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubIds )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';

		self::checkPubAdminAccess( $usr, null ); // Check if user is admin to any pub
		$pubs = array();
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
		if( is_null($pubIds) ) {
			$pubs = DBAdmPublication::listPublicationsObj( $typeMap );
			if ( is_null($pubs) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{PUBLICATIONS}' ) );
			}
			self::repairPublicationsSortOrder( $pubs ); // Reorder when damaged
			// Suppress the publications for which admin user has no rights
			$touchedPubs = false;
			foreach( $pubs as $key => $pub ) {
				if( !self::hasPubAdminAccess( $usr, $pub->Id ) ){
					unset( $pubs[$key] );
					$touchedPubs = true;
				}
			}
			// Renumber all keys (to avoid SOAP problem; returning nothing)
			if( $touchedPubs ) {
				$newKeys = range( 0, count($pubs)-1 );
				$pubs = array_combine( $newKeys, array_values( $pubs ) );
			}
		}
		else {
			foreach( $pubIds as $pubId ) {
				if( !$pubId ) { // client programming error
					throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
				}
				self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub
				$pub = DBAdmPublication::getPublicationObj( $pubId, $typeMap );
				if ( is_null($pub) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
				}
				$pubs[] = $pub;
			}
		}
		return $pubs;
	}

	/**
	 * Modify Publication Object
	 *
	 * Returns modified publication object
	 *
	 * @param string $usr Short name of user.
	 * @param array $subReq RequestModes.
	 * @param AdmPublication[] $pubs List of publications that needs to be modified.
	 * @throws BizException Throws BizException on failure
	 * @return AdmPublication[] Modified Publication objects.
	 */
	public static function modifyPublicationsObj( $usr, $subReq, $pubs )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';

		self::checkPubAdminAccess( $usr, null ); // Check if user is admin to any pub

		foreach( $pubs as $pub ) {
			if( !$pub->Id ) { // client programming error
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
			}
			self::checkPubAdminAccess( $usr, $pub->Id ); // Check if user has admin access to this pub
			$pub->Name = trim( $pub->Name ); //BZ#12402
			self::validateNewName( $pub->Name );
		}

		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
		$modifypubs = DBAdmPublication::modifyPublicationsObj( $pubs, $typeMap );
		if( DBAdmPublication::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmPublication::getError() );
		}
		return $modifypubs;
	}

	/**
	 * Delete Publication
	 * 
	 * @param string $usr shortusername who should have authorization to delete these publications
	 * @param int[] $pubIds Array of publication id that needs to be deleted
	 * @throws BizException
	 */
	public static function deletePublicationsObj( $usr, $pubIds )
	{
		if( !$pubIds ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		} else {
			foreach( $pubIds as $pubId ) {
				self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub
				require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
				BizCascadePub::deletePublication( $pubId );
			}
		}
	}


	// -----------------------------
	// ---	ISSUE OPERATIONS   ---
	// -----------------------------

	/**
	 * Create Issue Object
	 *
	 * Returns new created issues object
	 *
	 * @param  string $usr shortusername who should have authorization to create these issues
	 * @param  array $subReq RequestModes
	 * @param  string $pubId publication that new issue will belongs to
	 * @param  string $pubChannelId pubchannel that new issue belongs to
	 * @param  array $issues array of new issues that will created
	 * @throws BizException Throws BizException on failure
	 * @return array of new created issue objects
	 */
	public static function createIssuesObj( $usr, /** @noinspection PhpUnusedParameterInspection */ $subReq,
	                                        $pubId, $pubChannelId, $issues )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		// Validate the issue data
		foreach( $issues as $issue ) {
			$issue->Name = trim( $issue->Name ); //BZ#12402
			self::validateNewName( $issue->Name );

			if(!is_null($issue->Deadline) && !empty($issue->Deadline) && !DateTimeFunctions::validSoapDateTime($issue->Deadline)){
				throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('ISSUE_DEADLINE'));
			}

			if(!is_null($issue->PublicationDate) && !empty($issue->PublicationDate) && !DateTimeFunctions::validSoapDateTime($issue->PublicationDate)){
				throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('PUBLICATION_DATE'));
			}
		}

		// if no pubChannelId passed in, get the publication details to determine the default channel to take for the new issue
		if( is_null($pubChannelId) ){
			$pubRow = DBPublication::getPublication( $pubId );
			if( !$pubRow ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
			}
			$pubChannelId = $pubRow['defaultchannelid'];
		}

		$pubChannel = DBAdmPubChannel::getPubChannelObj($pubChannelId);
		if( !$pubChannel ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) );
		}

		if ($pubChannel->Type != 'print' && $pubChannel->Type != 'dps' && $pubChannel->Type != 'dps2' ) {
			foreach ($issues as $curissue) {
				if ($curissue->OverrulePublication != false) {
					throw new BizException('ERR_INVALID_OPERATION', 'Client', 'Overrule issue can not be in non-Print-channel');
				}
			}
		}

		// Create the issue
		$newIssues = DBAdmIssue::createIssuesObj( $pubChannelId, $issues );
		if( DBAdmIssue::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmIssue::getError() );
		}

		// Recalculate new deadlines for categories, statuses and objects. (BZ#17883)
		self::updateRecalcIssuesDeadlines( $pubId, $newIssues, array() );

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		foreach( $newIssues as $newIssue ) {
			BizEnterpriseEvent::createIssueEvent( $newIssue->Id, 'create' );
		}

		return $newIssues;
	}

	/**
	 * List Issue Objects
	 *
	 * Returns issue objects for the specified publication id and specified/all issues id
	 *
	 * @param  string $usr shortusername who should have authorization to get these issues
	 * @param  array  $subReq RequestModes
	 * @param  string $pubId Publication that Issue belongs to
	 * @param  string $pubChannelId PubChannel that Issue belongs to
	 * @param  array  $issueIds array of issue id
	 * @throws BizException Throws BizException on failure
	 * @return array of Issue objects
	 */
	public static function listIssuesObj( /** @noinspection PhpUnusedParameterInspection */ $usr,
		/** @noinspection PhpUnusedParameterInspection */ $subReq, $pubId, $pubChannelId, $issueIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}

		$issues = array();
		if ( is_null($issueIds) ) {
			if( is_null($pubChannelId) ) {
				$pubRow = DBPublication::getPublication( $pubId );
				if( !$pubRow ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
				}
				$pubChannelId = $pubRow['defaultchannelid'];
			}
			else {
				$pubchannel = DBAdmPubChannel::getPubChannelObj($pubChannelId);
				if( !$pubchannel ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) );
				}
			}
			$issues = DBAdmIssue::listChannelIssuesObj( $pubChannelId );
			if ( is_null($issues) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{ISSUES}' ) );
			}
		}
		else {
			foreach( $issueIds as $issueId ) {
				$issue = DBAdmIssue::getIssueObj( $issueId );
				if ( is_null($issue) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{ISSUE}', $issueId ) );
				}
				$issues[] = $issue;
			}
		}
		return $issues;
	}

	/**
	 * Modify Issue Object
	 *
	 * Returns modified issue object
	 *
	 * @param  string $usr shortusername who should have authorization to modify these issues
	 * @param  array $subReq RequestModes
	 * @param  string $pubId Publication that issue belongs to
	 * @param  string $pubChannelId PubChannel that Issue belongs to
	 * @param  array $issues array of issues that need to modify
	 * @throws BizException Throws BizException on failure
	 * @return array of modified Issue objects
	 */
	public static function modifyIssuesObj( $usr, /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubId, $pubChannelId, $issues )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		$anyNewDeadline = false;
		foreach( $issues as $iss ) {
			$iss->Name = trim( $iss->Name ); //BZ#12402
			self::validateNewName( $iss->Name );

			if(!is_null($iss->Deadline) && !empty($iss->Deadline) && !DateTimeFunctions::validSoapDateTime($iss->Deadline)){
				throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('ISSUE_DEADLINE'));
			}
			if( !is_null($iss->Deadline) ) {
				$anyNewDeadline = true;
			}

			if(!is_null($iss->PublicationDate) && !empty($iss->PublicationDate) && !DateTimeFunctions::validSoapDateTime($iss->PublicationDate)){
				throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('PUBLICATION_DATE'));
			}
		}
		if( is_null($pubChannelId) ) {
			$pubRow = DBPublication::getPublication( $pubId );
			if( !$pubRow ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
			}
			$pubChannelId = $pubRow['defaultchannelid'];
		}

		$pubChannel = DBAdmPubChannel::getPubChannelObj($pubChannelId);
		if( !$pubChannel ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) );
		}

		//BZ#9152 Build in check for issues having overrule issues in non-print channels: NOT ALLOWED!
		foreach ($issues as $curissue) {
			if ($curissue->OverrulePublication != false) {
				if ($pubChannel->Type != 'print' && $pubChannel->Type != 'dps' && $pubChannel->Type != 'dps2' ) {
					throw new BizException('ERR_INVALID_OPERATION','Client','Overrule issue can not be in non-Print-channel');
				}
			}
		}

		// Collect original issue deadlines
		$orgDeadlines = array();
		if( $anyNewDeadline ) {
			foreach( $issues as $issue ) {
				$orgIssue = DBAdmIssue::getIssueObj( $issue->Id );
				$orgDeadlines[ $orgIssue->Id ] = $orgIssue->Deadline;
			}
		}

		$modifyIssues = DBAdmIssue::modifyIssuesObj( $pubChannelId, $issues );
		if( DBAdmIssue::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmIssue::getError() );
		}

		// Recalculate new deadlines for categories, statuses and objects.
		self::updateRecalcIssuesDeadlines( $pubId, $modifyIssues, $orgDeadlines );

		// Notify event plugins
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		foreach( $modifyIssues as $modIssue ) {
			BizEnterpriseEvent::createIssueEvent( $modIssue->Id, 'update' );
		}

		return $modifyIssues;
	}

	/**
	 * Delete Issue Object
	 * 
	 * @param string $usr shortusername who should have authorization to delete these issues
	 * @param string $pubId Publication that issue belongs to
	 * @param array  $issueIds Array of issue id that needs to be delete
	 * @throws BizException
	 */
	public static function deleteIssuesObj( $usr, $pubId, $issueIds )
	{
		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		foreach( $issueIds as $issueId ) {
			BizCascadePub::deleteIssue( $issueId );
		}
	}

	/**
	 * After creating or updating issue properties, the issues deadlines (categories, statuses and objects)
	 * needs to be recalculated. This function takes care of that for the given issues.
	 *
	 * Issue properties (including deadlines) can be set through the admin web pages, but also through
	 * Content Station's Planning application calling admin web services.
	 *
	 * It needs $orgDeadlines to detect if the issue deadline has actually been changed.
	 * The $orgDeadlines must only be set in update mode (not for creation). The keys represent the issue ids and
	 * the values represent the deadlines as they were stored in the DB before the update took place.
	 * The given $issues->Deadline properties are the new deadlines as already updated in DB.
	 * When the deadline is not changed, it does not update to avoid avoid clearing all admin
	 * customizations done to the issues deadlines in case client applications just round-trips the 
	 * issue deadline while the user did not change/touch the value at the issue property dialog. 
	 * 
	 * When the $issues->Deadline is null (in SOAP terms, xsi:nil="true") the deadline is not touched at all.
	 * But when it is left empty, all issue deadlines are removed! Client applications must be careful here;
	 * There is a huge difference between null (=no update) and empty (=clear all).
	 *
	 * @param integer $pubId
	 * @param AdmIssue[] $issues
	 * @param array $orgDeadlines
	 * @since v7.0.11
	 */
	static private function updateRecalcIssuesDeadlines( $pubId, $issues, $orgDeadlines )
	{
		require_once BASEDIR.'/server/bizclasses/BizDeadlines.class.php';
		if( $issues ) foreach( $issues as $issue ) {
			if( !is_null( $issue->Deadline ) ) { // null means no update was taken place.
				if( empty( $issue->Deadline ) ) {
					// Empty means delete/clear all issue deadlines. Also see function header.
					BizDeadlines::deleteDeadlines( $issue->Id );
				} elseif ( self::isCalculateDeadlinesNeeded( $pubId, $issue ) ) {
					if( !isset( $orgDeadlines[ $issue->Id ] ) || $orgDeadlines[ $issue->Id ] != $issue->Deadline ) {
						// Filled and changed, so recalculate and update all issue deadlines. Also see function header.
						if( isset( $orgDeadlines[ $issue->Id ] ) ) {
							LogHandler::Log( __CLASS__, 'INFO', 'About to start issue deadlines recalculation since the original issue deadline "'.$orgDeadlines[ $issue->Id ].'" differs from the new deadline "'.$issue->Deadline.'".' );
						} else {
							LogHandler::Log( __CLASS__, 'INFO', 'About to start issue deadlines recalculation since the issue deadline "'.$issue->Deadline.'" is set for the first time.' );
						}
						BizDeadlines::updateRecalcIssueDeadlines( $pubId, $issue->Id, $issue->Deadline );
					} else {
						LogHandler::Log( __CLASS__, 'INFO', 'Skipped issue deadlines recalculation since the issue deadline "'.$issue->Deadline.'" has not changed.' );
					}
				}
			}
		}
	}

	/**
	 * Checks if calculating (relative) deadlines is needed and enabled on brand level or overrule brand level.
	 *
	 * @param int $publId
	 * @param AdmIssue $issueObj
	 * @return bool
	 */
	static private function isCalculateDeadlinesNeeded( $publId, $issueObj )
	{
		$calculateDeadlines = false;
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		if( $issueObj->OverrulePublication && $issueObj->CalculateDeadlines ) {
			$calculateDeadlines = true;
		} elseif( BizPublication::isCalculateDeadlinesEnabled( $publId, 0 ) ) {
			$calculateDeadlines = true;
		}

		return $calculateDeadlines;
	}

	// -----------------------------
	// ---   SECTION OPERATIONS  ---
	// -----------------------------

	/**
	 * Create Section Object
	 *
	 * Returns new created sections object
	 *
	 * @param string $usr Short username who should have authorization to create sections.
	 * @param array $subReq RequestModes
	 * @param string $pubId publication that new section belongs to
	 * @param string $issueId Issue that new section belongs to
	 * @param array $sections List of new sections(categories) that will be created.
	 * @throws BizException Throws BizException on failure
	 * @return Section[]
	 */
	public static function createSectionsObj( $usr, /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubId, $issueId, $sections )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		foreach( $sections as $section ) {
			$section->Name = trim( $section->Name ); //BZ#12402
			self::validateNewName( $section->Name );

			if(!is_null( $section->Deadline ) &&
				!empty( $section->Deadline ) && // empty is allowed; see WSDL type: dateTimeOrEmpty
				!DateTimeFunctions::validSoapDateTime( $section->Deadline ) ){
				throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('SECTION_DEADLINE'));
			}
		}
		$newsections = DBSection::createSectionsObj($pubId, $issueId, $sections);
		if( DBSection::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBSection::getError() );
		}

		return $newsections;
	}

	/**
	 * List Section Objects
	 *
	 * Returns section objects for the specified publication id, issues id and all/specified section id
	 *
	 * @param string $usr Short username.
	 * @param array $subReq RequestModes.
	 * @param string $pubId publication id.
	 * @param string $issueId issue id.
	 * @param array $sectionIds List of section ids.
	 * @throws BizException Throws BizException on failure.
	 * @return Section[]
	 */
	public static function listSectionsObj( $usr,  /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubId, $issueId, $sectionIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		$sections 	= array();
		if ( is_null( $sectionIds ) ) {
			$sections = DBSection::listSectionsObj( $pubId, $issueId );
			if ( is_null($sections) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{SECTIONS}' ) );
			}
		}
		else {
			foreach( $sectionIds as $sectionId ) {
				$section = DBSection::getSectionObj( $sectionId );
				if ( is_null($section) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{SECTION}', $sectionId ) );
				}
				$sections[] = $section;
			}
		}
		return $sections;
	}

	/**
	 * Modify Section Object
	 *
	 * Returns modified section object
	 *
	 * @param string $usr Short username.
	 * @param string $pubId Publication that Section belongs to
	 * @param string $issueId Issue that Section belongs to
	 * @param AdmSection[] $sections List of Sections.
	 * @throws BizException Throws BizException on failure.
	 * @return Section[]
	 */
	public static function modifySectionsObj( $usr, $pubId, $issueId, $sections )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		foreach( $sections as $section) {
			$section->Name = trim( $section->Name ); //BZ#12402
			self::validateNewName( $section->Name );

			// check valid deadline date
			if(!is_null( $section->Deadline ) &&
			!empty( $section->Deadline ) && // empty is allowed; see WSDL type: dateTimeOrEmpty
			!DateTimeFunctions::validSoapDateTime( $section->Deadline ) ){
				throw new BizException( 'INVALID_DATE', 'Client', BizResources::localize('SECTION_DEADLINE'));
			}
		}

		$modifysections = DBSection::modifySectionsObj( $pubId, $issueId, $sections );
		if( DBSection::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBSection::getError() );
		}
		return $modifysections;
	}

	/**
	 * Delete Section Object
	 * 
	 * @param string $usr shortusername who should have authorization to delete these sections
	 * @param string $pubId Publication that sections belongs to
	 * @param string $issueId Issue that sections belongs to
	 * @param array  $sectionIds Array of section id that needs to be delete
	 * @throws BizException
	 */
	public static function deleteSectionsObj( $usr, $pubId, /** @noinspection PhpUnusedParameterInspection */ $issueId,
	                                          $sectionIds )
	{
		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		foreach( $sectionIds as $sectionId ) {
			BizCascadePub::deleteSection( $sectionId );
		}
	}


	// -----------------------------
	// ---   EDITION OPERATIONS  ---
	// -----------------------------

	/**
	 * Create Edition Objects
	 *
	 * Returns new created editions objects
	 *
	 * @param string $usr Short username who should have authorization to create these editions
	 * @param string $pubId Publication that new section belongs to
	 * @param string $pubChannelId Pubchannel that new edition belongs to
	 * @param string $issueId Issue that new section belongs to
	 * @param AdmEdition[] $editions List of AdmEditions
	 * @throws BizException Throws BizException on failure
	 * @return stdClass[] List of Edition objects
	 */
	public static function createEditionsObj( $usr, $pubId, $pubChannelId, $issueId, $editions )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		foreach( $editions as $edition ) {
			$edition->Name = trim( $edition->Name ); //BZ#12402
			self::validateNewName( $edition->Name );
		}

		if( is_null($pubChannelId) ) {
			$pubRow = DBPublication::getPublication( $pubId );
			if( !$pubRow ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
			}
			$pubChannelId = $pubRow['defaultchannelid'];
		}
		else {
			$pubChannel = DBAdmPubChannel::getPubChannelObj($pubChannelId);
			if( !$pubChannel ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) );
			}
		}
		if( $issueId > 0 ) {
			$issue = DBAdmIssue::getIssueObj( $issueId );
			if ( is_null($issue) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{ISSUE}', $issueId ) );
			}

			if( $issue->OverrulePublication != true ) {
				throw new BizException('ERR_INVALID_OPERATION', 'Client', 'Overrule issue not found');
			}
		}
		return DBEdition::createEditionsObj( $pubChannelId, $issueId, $editions );
	}

	/**
	 * Get Edition Object
	 *
	 * Returns Edition objects for the specified publication id, issue id and specified/all editions id
	 *
	 * @param string $usr Short username who should have authorization to get these editions
	 * @param string $pubId Publication that Edition belongs to
	 * @param string $pubChannelId PubChannel that Edition belongs to
	 * @param string $issueId issue id
	 * @param array  $editionIds array of edition id
	 * @throws BizException Throws BizException on failure
	 * @return array of Edition objects
	 */
	public static function listEditionsObj( $usr, $pubId, $pubChannelId, $issueId, $editionIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		$editions = array();
		if ( is_null($editionIds) ) {
			if( !empty($issueId) ) {
				$editions = DBEdition::listIssueEditionsObj( $issueId );
				if ( is_null($editions) ) {
					throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{EDITIONS}' ) );
				}
			} else {
				require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
				require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
				if( is_null($pubChannelId) ) {
					$pubRow = DBPublication::getPublication( $pubId );
					if( !$pubRow ) {
						throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
					}
					$pubChannelId = $pubRow['defaultchannelid'];
				}
				else {
					$pubchannel = DBAdmPubChannel::getPubChannelObj($pubChannelId);
					if( !$pubchannel ) {
						throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) );
					}
				}
				$editions = DBEdition::listChannelEditionsObj( $pubChannelId );
				if ( is_null($editions) ) {
					throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{EDITIONS}' ) );
				}
			}
		} else {
			foreach( $editionIds as $editionId ) {
				$edition = DBEdition::getEditionObj($editionId );
				if ( is_null($edition) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{EDITION}', $editionId ) );
				}
				$editions[] = $edition;
			}
		}
		return $editions;
	}

	/**
	 * @param string $usr Short username who should have authorization to modify these editions
	 * @param string $pubId Publication that Edition belongs to
	 * @param string $pubChannelId Pubchannel that modify edition belongs to
	 * @param int $issueId Issue that Edition belongs to
	 * @param AdmEdition[] $editions List of AdmEditions.
	 * @throws BizException Throws BizException on failure
	 * @return Edition[] List of modified Edition objects
	 */
	public static function modifyEditionsObj( $usr, $pubId, $pubChannelId, $issueId, $editions )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		foreach( $editions as $edition) {
			$edition->Name = trim( $edition->Name ); //BZ#12402
			self::validateNewName( $edition->Name );
		}
		if( is_null($pubChannelId) ) {
			$pubRow = DBPublication::getPublication( $pubId );
			if( !$pubRow ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
			}
			$pubChannelId = $pubRow['defaultchannelid'];
		}
		else {
			$pubchannel = DBAdmPubChannel::getPubChannelObj($pubChannelId);
			if( !$pubchannel ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) );
			}
		}
		if( $issueId > 0 ) {
			$issue = DBAdmIssue::getIssueObj( $issueId );
			if ( is_null($issue) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{ISSUE}', $issueId ) );
			}

			if( $issue->OverrulePublication != true ) {
				throw new BizException('ERR_INVALID_OPERATION', 'Client', 'Overrule issue not found');
			}
		}
		$modifyEditions = DBEdition::modifyChannelEditionsObj( $pubChannelId, $issueId, $editions );
		if( DBEdition::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBEdition::getError() );
		}
		return $modifyEditions;
	}

	/**
	 * Delete Edition Object
	 * 
	 * @param string $usr shortusername who should have authorization to delete these editions
	 * @param string $pubId Publication that editions belongs to
	 * @param string $pubChannelId PubChannel that editions belongs to
	 * @param string $issueId Issue that editions belongs to
	 * @param array  $editionIds Array of edition id that needs to be delete
	 * @throws BizException
	 */
	public static function deleteEditionsObj( $usr, $pubId,
											/** @noinspection PhpUnusedParameterInspection */ $pubChannelId,
											/** @noinspection PhpUnusedParameterInspection */ $issueId,
											$editionIds )
	{
		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		foreach( $editionIds as $editionId ) {
			BizCascadePub::deleteEdition( $editionId );
		}
	}

	
	// --------------------------------
	// ---   PUBCHANNEL OPERATIONS  ---
	// --------------------------------

	/**
	 * Create PubChannel Objects
	 *
	 * Returns new created pubchannel objects
	 *
	 * @param string $usr Short username who should have authorization to create these PubChannels
	 * @param array $subReq RequestModes
	 * @param string $pubId publication that new PubChannel belongs to
	 * @param array $pubChannels List of new PubChannels that will be created
	 * @throws BizException Throws BizException on failure.
	 * @return AdmPubChannel[] List of newly created PubChannels.
	 */
	public static function createPubChannelsObj( $usr,  /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubId, $pubChannels )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		$pubRow = DBPublication::getPublication( $pubId );
		if( !$pubRow ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
		}

		foreach( $pubChannels as $pubChannel ) {
			$pubChannel->Name = trim( $pubChannel->Name ); //BZ#12402
			self::validateNewName( $pubChannel->Name );
		}

		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
		$newPubChannels = DBAdmPubChannel::createPubChannelsObj( $pubId, $pubChannels, $typeMap );
		if( DBAdmPubChannel::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmPubChannel::getError() );
		}
		self::enrichPubChannelObjsWithRuntimeValues( $newPubChannels );
		return $newPubChannels;
	}

	/**
	 * Get PubChannel Object
	 *
	 * Returns PubChannel objects for the specified publication id, and specified/all pubchannel id
	 *
	 * @param  string $usr shortusername who should have authorization to get these pubchannels
	 * @param  array  $subReq RequestModes
	 * @param  string $pubId publication id
	 * @param  array  $pubChannelIds array of pubchannel id
	 * @throws BizException Throws BizException on failure
	 * @return array of PubChannel objects
	 */
	public static function listPubChannelsObj( $usr,  /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubId, $pubChannelIds )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
		$pubChannels = array();
		if ( is_null($pubChannelIds) ) {
			$pubRow = DBPublication::getPublication( $pubId );
			if( !$pubRow ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
			}
			$pubChannels = DBAdmPubChannel::listPubChannelsObj( $pubId, $typeMap );
			if( is_null($pubChannels) ) {
				throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', null, null, array( '{CHANNELS}' ) );
			}
			self::enrichPubChannelObjsWithRuntimeValues( $pubChannels );
		}
		else {
			foreach( $pubChannelIds as $pubChannelId ) {
				$pubChannel = DBAdmPubChannel::getPubChannelObj( $pubChannelId, $typeMap );
				if ( is_null($pubChannel) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) );
				}
				self::enrichPubChannelObjWithRuntimeValues( $pubChannel );
				$pubChannels[] = $pubChannel;
			}
		}
		return $pubChannels;
	}

	/**
	 * Modify PubChannel Object
	 *
	 * Returns modified pubchannel object
	 *
	 * @param  string $usr shortusername who should have authorization to modify these pubchannels
	 * @param  array  $subReq RequestModes
	 * @param  string $pubId Publication that PubChannel belongs to
	 * @param  array  $pubChannels array of PubChannel that need to modify
	 * @throws BizException Throws BizException on failure
	 * @return array of modified PubChannel objects
	 */
	public static function modifyPubChannelsObj( $usr,  /** @noinspection PhpUnusedParameterInspection */ $subReq, $pubId, $pubChannels )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';

		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		foreach( $pubChannels as $pubChannel ) {
			$pubChannel->Name = trim( $pubChannel->Name ); //BZ#12402
			self::validateNewName( $pubChannel->Name );
		}
		$pubRow = DBPublication::getPublication( $pubId );
		if( !$pubRow ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
		}
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'PubChannel' );
		$modifyPubChannels = DBAdmPubChannel::modifyPubChannelsObj( $pubId, $pubChannels, $typeMap );
		if( DBAdmPubChannel::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBAdmPubChannel::getError() );
		}
		self::enrichPubChannelObjsWithRuntimeValues( $modifyPubChannels );
		return $modifyPubChannels;
	}

	/**
	 * It retrieves the deafult publication channel given the Publicaion id.
	 *
	 * @param int $pubId
	 * @throws BizException
	 * @return AdmPubChannel Default Publication Channel of publication $pubId
	 */
	static public function getDefaultPubChannel( $pubId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$pubRow = DBPublication::getPublication( $pubId );
		if( !$pubRow ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{PUBLICATION}', $pubId ) );
		}
		$pubChannelId = $pubRow['defaultchannelid'];
		if( !$pubChannelId ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', null, null, array( '{CHANNEL}', $pubChannelId ) ); // BZ#27819
		}
		$pubChannel = DBAdmPubChannel::getPubChannelObj( $pubChannelId );
		self::enrichPubChannelObjWithRuntimeValues( $pubChannel );
		return $pubChannel;
	}

	/**
	 * Delete PubChannel Object
	 * 
	 * @param string $usr shortusername who should have authorization to delete these pubchannels
	 * @param string $pubId Publication that pubchannel belongs to
	 * @param array  $pubChannelIds Array of pubchannel id that needs to be delete
	 * @throws BizException
	 */
	public static function deletePubChannelsObj( $usr, $pubId, $pubChannelIds )
	{
		if( !$pubId ) { // client programming error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Publication id is mandatory.' );
		}
		self::checkPubAdminAccess( $usr, $pubId ); // Check if user has admin access to this pub

		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		foreach( $pubChannelIds as $pubChannelId ) {
			BizCascadePub::deleteChannel( $pubChannelId );
		}
	}
	
	/**
	 * Some standard properties are calculated run-time (not read from DB).
	 * This function determines the DirectPublish, SupportsForms and SupportsCropping properties.
	 *
	 * @param array List of AdmPubChannel (input/output)
	 */
	static private function enrichPubChannelObjsWithRuntimeValues( &$pubChannels )
	{
		if( $pubChannels ) foreach( $pubChannels as &$pubChannel ) {
			self::enrichPubChannelObjWithRuntimeValues( $pubChannel );
		}
	}

	/**
	 * Some standard properties are calculated run-time (not read from DB).
	 * This function determines the DirectPublish, SupportsForms and SupportsCropping properties.
	 *
	 * @param array AdmPubChannel (input/output)
	 */
	static private function enrichPubChannelObjWithRuntimeValues( &$pubChannel )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$pubChannel->DirectPublish = !empty($pubChannel->PublishSystem) ? true : false;

		$supportsForms = BizServerPlugin::runChannelConnector( $pubChannel->Id, 
			'doesSupportPublishForms', array(), false/*suppress Error*/ );
		// $supportsForms is null, most likely there was an error thrown but we suppress it.
		$pubChannel->SupportsForms = is_null( $supportsForms ) ? false : $supportsForms;

		$supportsCropping = BizServerPlugin::runChannelConnector( $pubChannel->Id,
			'doesSupportCropping', array(), false/*suppress Error*/ );
		// $supportsCropping is null, most likely there was an error thrown but we suppress it.
		$pubChannel->SupportsCropping = is_null( $supportsCropping ) ? false : $supportsCropping;
	}

	/**
	 * Returns the publication channels that belong to a publish system and
	 * to which the current brand admin user has access.
	 *
	 * @param string $publishSystem
	 * @return PubChannelInfo[]
	 */
	static public function getPubChannelInfosForPublishSystem( $publishSystem )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

		// Get brands (ids) the admin user has access to.
		$accessToPubIds = array();
		$userId = BizSession::getShortUserName();
		$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
		$pubs = DBAdmPublication::listPublicationsObj( $typeMap );
		foreach( $pubs as $pub ) {
			if( self::hasPubAdminAccess( $userId, $pub->Id ) ){
				$accessToPubIds[$pub->Id] = true;
			}
		}

		// Get the pub channels this server plugin is configured to publish for.
		// TODO: Use DBAdmPubChannel instead of DBChannel.
		$pubChannelInfos = DBChannel::getChannelsByPublishSystem( $publishSystem );
		$infosWithAccess = array();
		if( $pubChannelInfos ) foreach( $pubChannelInfos as $pubChannelInfo ) {
			$pubId = DBChannel::getPublicationId( $pubChannelInfo->Id );
			
			// Only take the pub channels to which the admin user has access.
			if( array_key_exists( $pubId, $accessToPubIds ) ) {
				$infosWithAccess[] = $pubChannelInfo;
			}
		}
		return $infosWithAccess;
	}

	/**
	 * Returns the Publish System Id stored set at the channel level.
	 *
	 * @param int $channelId The channel db id where the publish system id is stored at.
	 * @return null|string The publish system id of the requested channel, null when no publish system id is set.
	 */
	static public function getPublishSystemIdForChannel( $channelId )
	{
		require_once BASEDIR .'/server/dbclasses/DBAdmPubChannel.class.php';
		$publishSystemId = DBAdmPubChannel::getPublishSystemIdForChannel( $channelId );

		return $publishSystemId;
	}

	/**
	 * Validates and save the publish system id into channels data.
	 *
	 * If the publish system id already exists, function checks if the publish system id is the same as the one
	 * already set in the database. Throws a BizException when the publish system id are not the same.
	 * If the publish system id does not exists yet,
	 *
	 * @param int $channelId
	 * @param string $publishSystemId
	 * @throws BizException Throws a BizException when the validation fails.
	 */
	static public function savePublishSystemIdForChannel( $channelId, $publishSystemId )
	{
		$publishSystemIdFromDB = self::getPublishSystemIdForChannel( $channelId );
		if( $publishSystemIdFromDB ) { // when already exists, need to verify with the incoming publish system id.
			if( $publishSystemIdFromDB != $publishSystemId ) {
				require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
				$row = DBChannel::getChannel( $channelId );
				$detail = 'The incoming publish system ID "'.$publishSystemId.'" does not match the publish system ID '.
					'"'.$publishSystemIdFromDB.'" which is currently set for publish system "'.$row['publishsystem']. '"'.
					' in Publication Channel "'.$row['name'].'".';
				throw new BizException( 'ERR_DIFF_PUBSYSID', 'Server', $detail );
			}
		} else { // Publish system id has never been set in Enterprise DB, set now.
			// Verify first.
			require_once BASEDIR .'/server/utils/NumberUtils.class.php';
			if( !NumberUtils::validateGUID( $publishSystemId ) ) {
				$row = DBChannel::getChannel( $channelId );
				$detail = 'Invalid publish system ID "'.$publishSystemId.
					'" found for publish system "'.$row['publishsystem'].'" in Publication Channel "'.$row['name'].'".';
				throw new BizException( 'ERR_INVALID_PUBSYSID', 'Server', $detail );
			}

			// Save publishsystemid into database.
			require_once BASEDIR .'/server/dbclasses/DBAdmPubChannel.class.php';
			DBAdmPubChannel::savePublishSystemIdForChannel( $channelId, $publishSystemId );

		}
	}
}