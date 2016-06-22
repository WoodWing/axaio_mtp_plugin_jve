<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR . '/server/interfaces/services/BizException.class.php';

class BizPublication
{
	/**
	 * Retrieves all publication definitions from DB for which given user has access.
	 * Per publication, issue definitions are retrieved that are marked Active.
	 *
	 * For GetDialog service, the $extraIssueIds contains those issues that are assigned to
	 * a certain object. Those needs to be shown at dialog, even when set non-Active.
	 * For this situation, $mode is set to 'browse'.
	 *
	 * Deprecated since 9.7, please use getPublicationInfosByRequestInfo instead for better performance.
	 *
	 * @param string $user    User Id. Used to determine access rights.
	 * @param string $mode    Kind of information wanted:
	 *   - 'flat'   Gives Publication objects providing names and ids. (Default)
	 *   - 'full'   Gives PublicationInfo objects providing full details, and same for its children/elements.
	 *   - 'browse' Gives PublicationInfo objects providing names and ids, and same for its channels (with issues and editions) and categories.
	 * @param string $pubId   Publication id (optional). In this mode it returns only one pub instead of all. 
	 * @param array $extraIssueIds Additional issues that should be included, no matter Active or not. Default none.
	 * @return PublicationInfo[]|Publication[] When $mode is 'flat' Publication is returned, else PublicationInfo
	 * @throws BizException on failure
	 */
	public static function getPublications( $user, $mode='flat', $pubId=null, $extraIssueIds = array() )
	{
		if( empty($pubId) ) {
			return self::getPublicationInfos( $user, $mode, $extraIssueIds );
		} else {
			require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
			global $globAuth;
			if( !isset($globAuth) ) {
				require_once BASEDIR.'/server/authorizationmodule.php';
				$globAuth = new authorizationmodule( );
			}
			$globAuth->getRights($user);
			$pubRow = DBPublication::getPublication( $pubId );
			return array( self::getPublicationInfo( $globAuth->getCachedRights(), $user, $pubRow, $mode, $extraIssueIds ) );
		}
	}

	/**
	 * Retrieves all publication definitions from DB for which given user has access.
	 * -> See getPublications() function for more details!
	 *
	 * Deprecated since 9.7, please use getPublicationInfosByRequestInfo instead for better performance.
	 *
	 * @param string $userName
	 * @param string $mode
	 * @param array $extraIssueIds
	 * @return PublicationInfo[]|Publication[] When $mode is 'flat' Publication is returned, else PublicationInfo
	 */
	public static function getPublicationInfos( $userName, $mode='full', $extraIssueIds = array() )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		$result = array();

		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
		$globAuth->getRights( $userName );

		if( $mode == 'flat' ) {
			$pubRows = DBPublication::listPublications(array('id','publication'));
		} else {
			$pubRows = DBPublication::listPublications();
		}

		$rights = $globAuth->getCachedRights();
		$pubRights = array();
		if ( $rights ) foreach( $rights as $right ) {
			$pubRights[$right['publication']][] = $right; // Store the rights per publication
		}

		if ( $pubRows ) foreach( $pubRows as $pubId => $pubRow ) {
			if( isset($pubRights[$pubId]) ) { // Retrieve when there is publication right
				$result[] = self::getPublicationInfo( $pubRights[$pubId], $userName, $pubRow, $mode, $extraIssueIds );
			}
		}
		return $result;
	}

	/**
	 * Retrieves one publication definition from DB.
	 * -> See getPublications() function for more details!
	 *
	 * Deprecated since 9.7, please use getPublicationInfosByRequestInfo instead for better performance.
	 *
	 * @param array $userRights
	 * @param string $userName
	 * @param array $pubRow
	 * @param string $mode
	 * @param array $extraIssueIds
	 * @return PublicationInfo[]|Publication[] When $mode is 'flat' Publication is returned, else PublicationInfo
	 */
	public static function getPublicationInfo( $userRights, $userName, $pubRow, $mode='full', $extraIssueIds = array() )
	{
		switch( $mode ) {
			case 'browse':
			{
				require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
				$retPub = new PublicationInfo();
				$retPub->Id           = $pubRow['id'];
				$retPub->Name         = $pubRow['publication'];
				$retPub->Issues       = null;
				$retPub->States       = null;
				$retPub->ObjectTypeProperties = null;
				$retPub->ActionProperties     = null;
				$retPub->Editions             = null;
				$retPub->FeatureAccessList    = null;
				$retPub->CurrentIssue = null;
				$retPub->PubChannels  = self::getChannelInfos( $userRights, $pubRow, $mode, $extraIssueIds );
				$retPub->Categories   = self::getCategoryInfos( $userRights, $pubRow );
				$retPub->Dictionaries = BizSpelling::getDictionariesForPublication( $pubRow['id'] );
				$retPub->ReversedRead = self::readingOrderReversed( $pubRow['id'] ); // Added since v7.6.0
				break;
			}
			case 'full':
			{
				require_once BASEDIR.'/server/dbclasses/DBMetaData.class.php';
				require_once BASEDIR.'/server/dbclasses/DBFeature.class.php';
				require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
				require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';				
				
				$ticket = BizSession::getTicket();
				$clientMajorVersion = null;
				if( $ticket ) {
					$clientMajorVersion = intval( BizSession::getClientVersion( null, null, 1 ) );
				}	
				if( ( $clientMajorVersion && $clientMajorVersion <= 7 ) || // 7.x (or older) => still using the obsoleted retPub->Issues and ->Editions
						!$ticket ) { // cannot determine the client version, better be safe, so fill in obsoleted issues and editions.
					$issues = self::getPublIssueInfos( $userRights, $pubRow );
					$editions = self::getPublEditionInfos( $userRights, $pubRow );
				} else { // using client 8 and above, so respect the latest WSDL
					$issues = null;
					$editions = null;
				}
				$defaultChanRow = DBChannel::getChannel( $pubRow['defaultchannelid'] );
				
				$retPub = new PublicationInfo();
				$retPub->Id           = $pubRow['id'];
				$retPub->Name         = $pubRow['publication'];
				$retPub->Issues       = $issues; // obsoleted; use ChannelInfo instead
				$retPub->States       = self::getStateInfos( $userRights, $pubRow );
				$retPub->ObjectTypeProperties = DBMetaData::getObjectProperties( $pubRow['id'] );
				$retPub->ActionProperties     = DBMetaData::getActionProperties( $pubRow['id'] );
				$retPub->Editions             = $editions; // obsoleted; use ChannelInfo instead
				$retPub->FeatureAccessList    = DBFeature::getFeatureAccess( $userName, $pubRow['id'] );
				$retPub->CurrentIssue = !empty($defaultChanRow['currentissueid']) ? $defaultChanRow['currentissueid'] : null; // obsoleted; use ChannelInfo instead
				$retPub->PubChannels  = self::getChannelInfos( $userRights, $pubRow, $mode, $extraIssueIds );
				$retPub->Categories   = self::getCategoryInfos( $userRights, $pubRow );
				$retPub->Dictionaries = BizSpelling::getDictionariesForPublication( $pubRow['id'] );
				$retPub->ReversedRead = self::readingOrderReversed( $pubRow['id'] ); // Added since v7.6.0
				break;
			}
			case 'flat':
			default:
				$retPub = new Publication();
				$retPub->Id   = $pubRow['id'];
				$retPub->Name = $pubRow['publication'];
				break;
		}
		return $retPub;
	}
	
	/**
	 * Returns brand setup information for brands the given user has access to.
	 *
	 * @param string $userName
	 * @param string[]|null $requestInfo One or more of the PublicationInfo properties of caller's interest, e.g. 'PubChannels'
	 * @param integer|null $pubId Get info for this user brand only, NULL to get for all user brands.
	 * @return PublicationInfo[]
	 */
	public static function getPublicationInfosByRequestInfo( $userName, $requestInfo=null, $pubId=null )
	{
		if( is_null($requestInfo) ) {
			$requestInfo = array( 
				'States', 'ObjectTypeProperties', 'ActionProperties', 'FeatureAccessList', 
				'CurrentIssue', 'PubChannels', 'Categories', 'Dictionaries'
			);
		}
		
		// Determine to which brands the user has access.
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
		if( $pubId ) { // one brand only
			$globAuth->getRights( $userName, $pubId );
		} else { // all brands
			$globAuth->getRights( $userName );
		}
		$rights = $globAuth->getCachedRights();
		$userRights = array();
		if( $rights ) foreach( $rights as $right ) {
			$userRights[$right['publication']][] = $right; // Store the rights per publication
		}
		
		// For only those brands the user has access to, get only those properties the caller is interested in.
		$pubInfos = array();
		$pubIds = array_keys( $userRights );
		if( $pubIds ) {
			require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
			$pubFields = array( 'id', 'publication', 'defaultchannelid', 'readingorderrev' );
			$pubRows = DBPublication::listPublications( $pubFields, $pubIds );
			
			$featureAccessList = null;
			if( in_array( 'FeatureAccessList', $requestInfo ) ) {
				require_once BASEDIR.'/server/dbclasses/DBFeature.class.php';
				$featureAccessList = DBFeature::getFeatureAccess( $userName, $pubIds );
			}
			$objectTypeProperties = null;
			if( in_array( 'ObjectTypeProperties', $requestInfo ) ) {
				require_once BASEDIR.'/server/dbclasses/DBMetaData.class.php';
				$objectTypeProperties = DBMetaData::getObjectProperties( $pubIds );
			}
			$actionProperties = null;
			if( in_array( 'ActionProperties', $requestInfo ) ) {
				require_once BASEDIR.'/server/dbclasses/DBMetaData.class.php';
				$actionProperties = DBMetaData::getActionProperties( $pubIds );
			}
			
			// TODO: optimize SQL for the other PublicationInfo properties, like done for FeatureAccessList above
			
			if( $pubRows ) foreach( $pubRows as $pubId => $pubRow ) {
				$pubInfo = new PublicationInfo();
				$pubInfo->Id = $pubId;
				$pubInfo->Name = $pubRow['publication'];
				$pubInfo->Issues = null; // obsoleted; use ChannelInfo instead
				if( in_array( 'States', $requestInfo ) ) {
					$pubInfo->States = self::getStateInfos( $userRights[$pubId], $pubRow );
				}
				if( in_array( 'ObjectTypeProperties', $requestInfo ) ) {
					$pubInfo->ObjectTypeProperties = isset($objectTypeProperties[$pubId]) ? $objectTypeProperties[$pubId] : array();
				}
				if( in_array( 'ActionProperties', $requestInfo ) ) {
					$pubInfo->ActionProperties = isset($actionProperties[$pubId]) ? $actionProperties[$pubId] : array();
				}
				$pubInfo->Editions = null; // obsoleted; use ChannelInfo instead
				if( in_array( 'FeatureAccessList', $requestInfo ) ) {
					$pubInfo->FeatureAccessList = isset($featureAccessList[$pubId]) ? $featureAccessList[$pubId] : array();
				}
				if( in_array( 'CurrentIssue', $requestInfo ) ) { // current issue of the default channel
					require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
					$defaultChanRow = DBChannel::getChannel( $pubRow['defaultchannelid'] );
					$pubInfo->CurrentIssue = !empty($defaultChanRow['currentissueid']) ? $defaultChanRow['currentissueid'] : null;
				}
				if( in_array( 'PubChannels', $requestInfo ) ) {
					$pubInfo->PubChannels = self::getChannelInfos( $userRights[$pubId], $pubRow );
				}
				if( in_array( 'Categories', $requestInfo ) ) {
					$pubInfo->Categories = self::getCategoryInfos( $userRights[$pubId], $pubRow );
				}
				if( in_array( 'Dictionaries', $requestInfo ) ) {
					require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
					$pubInfo->Dictionaries = BizSpelling::getDictionariesForPublication( $pubId );
				}
				$pubInfo->ReversedRead = $pubRow['readingorderrev'] === 'on'; // Added since v7.6.0
				$pubInfos[] = $pubInfo;
			}
		}
		return $pubInfos;
	}

	/**
	 * Retrieves all publication channel definitions from DB made for given publication.
	 * -> See getPublications() function for more details!
	 *
	 * @param array $userRights
	 * @param array $pubRow
	 * @param string $mode
	 * @param array $extraIssueIds
	 * @return array of PubChannelInfo objects
	 */
	public static function getChannelInfos( $userRights, $pubRow, $mode='full', $extraIssueIds = array() )
	{
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		$result = array();
		$channels = DBChannel::listChannels($pubRow['id']);

		foreach ($channels as $channel) {
			$result[] = self::getChannelInfo( $userRights, $channel, $mode, $extraIssueIds );
		}
		return $result;
	}
	
	/**
	 * Retrieves one publication channel definition from DB.
	 * -> See getPublications() function for more details!
	 *
	 * @param array $userRights
	 * @param array $channel
	 * @param string $mode
	 * @param array $extraIssueIds
	 * @return PubChannelInfo
	 */
	public static function getChannelInfo( $userRights, $channel, $mode='full', $extraIssueIds = array() )
	{
		$issues = self::getChannelIssueInfos( $userRights, $channel, $mode, $extraIssueIds );
		$editions = self::getChannelEditionInfos( $userRights, $channel );
		$directpublish = !empty($channel['publishsystem']) ? true : false;
		$pubChannelInfo = new PubChannelInfo();
		$pubChannelInfo->Id            = $channel['id'];
		$pubChannelInfo->Name          = $channel['name'];
		$pubChannelInfo->Issues        = $issues;
		$pubChannelInfo->Editions      = $editions;
		$pubChannelInfo->CurrentIssue  = $channel['currentissueid'];
		$pubChannelInfo->Type          = $channel['type'];
		$pubChannelInfo->DirectPublish = $directpublish;
		
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$supportsForms = BizServerPlugin::runChannelConnector( $channel['id'], 'doesSupportPublishForms', array(), false/*supress Error*/ );
		// $supportsForms is null, most likely there was an error thrown but we supress it.
		$pubChannelInfo->SupportsForms = is_null( $supportsForms ) ? false : $supportsForms;
		
		return $pubChannelInfo;
	}	

	/**
	 * Retrieves all issue definitions from DB for a given publication channel.
	 * -> See getPublications() function for more details!
	 *
	 * @param array $userRights
	 * @param array $channel
	 * @param string $mode
	 * @param array $extraIssueIds
	 * @return array of IssueInfo
	 */
	public static function getChannelIssueInfos( $userRights, $channel, $mode='full', $extraIssueIds = array() )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$result = array();
		$issues = DBIssue::listChannelIssues( $channel['id'] );
		foreach( $issues as $issueId => $issue ) {
			// Skip non-Active issues, except when explicitly requested
			if( $issue['active'] != 'on' && !isset($extraIssueIds[$issueId]) ) {
				continue;	
			}
			$authorized = false;
			if( $issue['overrulepub'] == 'on' ) {
				foreach( $userRights as $right ) {
					if( $right['issue'] == $issueId ) {
						$authorized = true;
						break;
					}
				}				
			} else {
				$authorized = true;
			}
			
			if( $authorized === true ) {
				$result[] = self::getIssueInfo( $userRights, $issue, $mode );
			}
		}
		return $result;
	}
	
	// Obsoleted; use getChannelIssueInfos() instead
	public static function getPublIssueInfos($userRights, $publication)
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$result = array();
		$issues = DBIssue::listPublicationIssues( $publication['id'] );

		foreach ($issues as $issueid => $issue)
		{
			if ($issue['active'] != 'on')
			{
				continue;	
			}

			$authorized = false;
			if ($issue['overrulepub'] == 'on')	
			{
				foreach ($userRights as $right)
				{
					if ($right['issue'] == $issueid)	
					{
						$authorized = true;
					}
				}				
			}
			else
			{
				foreach ($userRights as $right)
				{
					if ($right['issue'] == 0 && $right['publication'] == $publication['id'])	
					{
						$authorized = true;
					}
				}				
			}
			
			if ($authorized === true)
			{
				$result[] = self::getIssueInfo($userRights, $issue);
			}
		}
		return $result;
	}

	/**
	 * Retrieves one issue definitions from DB.
	 * -> See getPublications() function for more details!
	 *
	 * @param array $userRights
	 * @param array $issue
	 * @param string $mode
	 * @return IssueInfo
	 */
	public static function getIssueInfo( $userRights, $issue, $mode='full' )
	{
		$result = new IssueInfo();
		$result->Id   = $issue['id'];
		$result->Name = $issue['name'];

		if ($issue['overrulepub'] == 'on') {
			$result->OverrulePublication = true;
			$result->Sections = self::getSectionInfos($userRights, null, $issue);
			$result->Editions = self::getIssueEditionInfos($userRights, $issue);
			if( $mode == 'full' ) {
				$result->States = self::getStateInfos($userRights, null, $issue);
			}
		} else {
			$result->OverrulePublication = false;
		}
		$result->Description = $issue['description'];
		$result->Subject = $issue['subject'];
		$result->PublicationDate = trim($issue['publdate']);
		if( empty($result->PublicationDate) ) { 
			$result->PublicationDate = null;  // empty dates are invalid
		}
	
		$result->ReversedRead = $issue['readingorderrev'] === 'on' ? true : false; // Added since v7.6.0
		return $result;
	}

	public static function getSectionInfos($userRights, $publication, $overrulingissue = null)
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		$result = array();	
		if ($publication)
		{
			$sections = DBSection::listPublSectionDefs($publication['id']);
			foreach ($sections as $sectionid => $section)
			{
				$authorized = false;
				foreach ($userRights as $right)
				{
					if ($right['publication'] == $publication['id'] && $right['issue'] == 0)
					{
						if ($right['section'] == 0 || $right['section'] == $sectionid)
						{
							$authorized = true;	
							break;
						}	
					}
				}
				
				if ($authorized === true)
				{
					$result[] = new CategoryInfo($sectionid, $section['section']);
				}
			}
		}
		else if ($overrulingissue) // Allways overruled, it is not allowed to get SectionInfos for a non-overruling issue.
		{
			$sections = DBSection::listIssueSectionDefs($overrulingissue['id'],'*',true);
			foreach ($sections as $sectionid => $section)
			{
				$authorized = false;
				foreach ($userRights as $right)
				{
					if ($right['issue'] == 	$overrulingissue['id'])
					{
						if ($right['section'] == 0 || $right['section'] == $sectionid)
						{
							$authorized = true;	
							break;
						}	
					}
				}
				
				if ($authorized === true)
				{
					$result[] = new CategoryInfo($sectionid, $section['section']);
				}
			}
		}
		return $result;
	}

	// Note: for v6.0 the Category (only at Pub level) is same as Section
	public static function getCategoryInfos($userRights, $publication,
						/** @noinspection PhpUnusedParameterInspection */ $overrulingissue = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		$result = array();
		if ($publication)
		{
			$sections = DBSection::listPublSectionDefs($publication['id']);
			foreach ($sections as $sectionid => $section)
			{
				$authorized = false;
				foreach ($userRights as $right)
				{
					if ($right['publication'] == $publication['id'] && $right['issue'] == 0)
					{
						if ($right['section'] == 0 || $right['section'] == $sectionid)
						{
							$authorized = true;	
							break;
						}	
					}
				}
				
				if ($authorized === true)
				{
					$result[] = new CategoryInfo($sectionid, $section['section']);
				}
			}
		}
		return $result;
	}

	// Obsoleted; use getChannelEditionInfos() instead
	public static function getPublEditionInfos( /** @noinspection PhpUnusedParameterInspection */ $userRights,
												$publication )
	{
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$result = array();
		
		$editions = DBEdition::listChannelEditions( $publication['defaultchannelid'] );
		foreach ($editions as $editionid => $edition) {
			$result[] = new Edition($editionid, $edition['name']);
		}
		
		return $result;
	}	
	
	public static function getChannelEditionInfos( /** @noinspection PhpUnusedParameterInspection */ $userRights,
													$channel )
	{
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$result = array();
		
		$editions = DBEdition::listChannelEditions( $channel['id'] );
		foreach ($editions as $editionid => $edition) {
			$result[] = new Edition($editionid, $edition['name']);
		}
		return $result;
	}	

	public static function getIssueEditionInfos( $userRights, $overrulingIssue )
	{
		$result = array();
		$authorized = false;
		$editions = null;
		
		if( $overrulingIssue ) {
			foreach ($userRights as $right) {
				if( $right['issue'] == $overrulingIssue['id'] ) {
					$authorized = true;
					break;
				}
			}
			if( $authorized ) {
				require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
				$editions = DBIssue::listIssueEditionDefs( $overrulingIssue['id'] );
			}
		}
		if( $authorized ) {
			foreach( $editions as $editionid => $edition ) {
				$result[] = new Edition($editionid, $edition['name']);
			}
		}	
		return $result;
	}

	/*
     * Get Edition
     *
     * Returns specific edition object based on the edition Id.
     *
     * @param string $editionId		edition id
     * @return Object of Edition
     */
	public static function getEditionInfo( $editionId )
	{
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$result = DBEdition::getEdition( $editionId );

		return $result;
	}

	public static function getStateInfos($userRights, $publication, $overrulingIssue = null)
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		$result = array();

		if (strtolower(PERSONAL_STATE) == strtolower('ON')) {
			$objecttypes = getObjectTypeMap();
			foreach( array_keys($objecttypes) as $objecttype ) {
				$result[] = new State(-1, BizResources::localize('PERSONAL_STATE'), $objecttype, false, substr(PERSONAL_STATE_COLOR, 1), null);
			}
		}

		if ($publication)
		{
			$states = DBWorkflow::listPublStateDefs($publication['id']);
			foreach ($states as $stateid => $state)
			{
				$authorized = false;
				foreach ($userRights as $right)
				{
					if ($right['publication'] == $publication['id'] && $right['issue'] == 0 && ($right['state'] == $stateid || $right['state'] == 0))
					{
						$authorized = true;							
					}
				}
				if ($authorized)
				{
					$result[] = new State($stateid, $state['state'], $state['type'], trim($state['produce']) != '', substr($state['color'],1), null);
				}
			}
		}
		else if ($overrulingIssue)
		{
			$states = DBWorkflow::listIssueStateDefs($overrulingIssue['id']);
			foreach ($states as $stateid => $state)
			{
				$authorized = false;
				foreach ($userRights as $right)
				{
					if ($right['issue'] == $overrulingIssue['id'] && ($right['state'] == $stateid || $right['state'] == 0))
					{
						$authorized = true;							
					}
				}
				if ($authorized)
				{
					$result[] = new State($stateid, $state['state'], $state['type'], trim($state['produce']) != '', substr($state['color'],1), null);
				}
			}
		}
		return $result;
	}
	
	/**
	 * Retrieves a list of issue objects from the database for the given publication.
	 * When $overruleIssOnly is set to True, only overrule issue will be returned; when set to
	 * False (the default), ALL issues will be returned.
	 *
	 * @param string $user        User Id. Used to determine access rights.
	 * @param string $publication Publication id. May not be null.
	 * @param string $mode        Kind of information wanted:
	 *   - 'flat' gives Issue objects providing names and ids. (Default)
	 *   - 'browse' gives Issue objects providing names and ids, and same for its sections and editions.
	 *   - 'full' gives IssueInfo objects providing full details, and same for its children/elements.
	 * @param string $objType     Object type (optional). Get only type specific statusses. Null means all.
	 * @param boolean $overruleIssOnly Set to True when only overrule issue is needed; false(default) when ALL issues needed.
	 * @return mixed              Array of IssueInfo or array of Issue - throws BizException on failure
	 */

	public static function getIssues( $user, $publication, $mode='flat', $objType=null, $overruleIssOnly=false )
	{		
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php'; 
		$ret = array();
		
		$rows = DBIssue::listPublicationIssues( $publication, $overruleIssOnly );
		foreach ($rows as $row)
		{
			$item = null;
			$issue = $row['id'];

			switch( $mode )
			{
				case 'browse':
				case 'full':
				{
					require_once BASEDIR.'/server/dbclasses/DBEdition.class.php'; 
					require_once BASEDIR."/server/bizclasses/BizWorkflow.class.php";
					if( BizAccess::checkRightsForBrandContext( 
										$user, '', BizAccess::DONT_THROW_ON_DENIED, 
										$publication, $issue ) 
					) {
						$item = new IssueInfo( $issue, $row['name'] );
						if( $row['overrulepub'] )
						{
							$item->OverrulePublication = true;
							$item->Sections = self::getSections( $user, $publication, $issue, $mode, false );
							$item->Editions = DBEdition::listEditions( $publication, $issue ); // don't use self here, this would call DB to see if we have overrule issue
							$item->States 	= BizWorkflow::getStates($user, $publication, $issue, null, $objType, false, true);
						}
						$ret[] = $item;
					}
					break;
				}
				case 'flat':
				default:
				{
					if( BizAccess::checkRightsForBrandContext( 
										$user, '', BizAccess::DONT_THROW_ON_DENIED, 
										$publication, $issue ) 
					) {
						$item = new Issue( $issue, $row['name'] );
						if( $row['overrulepub'] ) {
							$item->OverrulePublication = true;
						}
						$ret[] = $item;
					}
					break;
				}
			}
		}
		
		return $ret;
	}

	/*
	 * Retrieves a list of sections objects from the database for the given publication/issue.
     *
     * Returns sections for the specified publication/issue. The caller does not have to worry about overrule
     * issues, you'll get whatever is valid for the specified pub/issue
     *
	 * @param string  $user         User Id. Used to determine access rights.
	 * @param int  $publication  Publication id. May not be null.
     * @param int  $issue 		Issue id. Null means for all issues.
	 * @param string  $mode         Kind of information wanted:
	 *   - 'flat'   gives Section objects providing names and ids. (Default)
	 *   - 'browse' gives Section objects providing names and ids.
	 *   - 'full'   gives SectionInfo objects providing full details.
     * @param boolean $checkForOverruleIssue if you know that the issue you pass in is overrule, pass in false to save a DB call.
	 * @throws BizException Throws BizException on failure
     * @return Category[]|CategoryInfo[] List of Section or SectionInfo
     */
	public static function getSections( $user, $publication, $issue = null, $mode='flat', $checkForOverruleIssue=true )
	{
		// If issue is specified we need to see if this is an overrule issue
		// if not we should get the publication's editions
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		if( $checkForOverruleIssue && !is_null($issue) && !DBIssue::isOverruleIssue( $issue ) ) {
			$issue = null;
		}
		
		// call DB
		$dbDriver = DBDriverFactory::gen();
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		$sth = DBSection::listSections( $publication, $issue );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		// get authorizations
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
		$globAuth->getRights($user, $publication, $issue);

		// fetch into array
		$ret = array();
		while( ($row = $dbDriver->fetch($sth)) )
		{
			$section = $row['id'];
			$tempiss = $row['issue'];
			if ($globAuth->checkright('', $publication, $tempiss, $section))
			{
				$getit = true;
				if( $issue && ($issue != $row['issue'] ) )
					$getit = false;

				if( $getit ) // get section?
				{
					switch( $mode )
					{
						case 'browse':
						case 'full':
						{
							$ret[] = new CategoryInfo( $section, $row['section'] );
							break;
						}
						default:
						case 'flat':
						{
							$ret[] = new Category( $section, $row['section'] );
							break;
						}
					}
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Get Editions
     *
     * Returns editions for the specified publication/issue. The caller does not have to worry about overrule
     * issues, you'll get whatever is valid for the specified pub/issue
     *
     * @param integer $pubId	publication id
     * @param integer $issueId 	issue id
     * @return array of Edition - throws BizException on failure
     */
	public static function getEditions( $pubId, $issueId = null )
	{
		// Check if issue is Overrule issue,
		// if yes, get the overrule issue default channel editions,
		// if no, get the issue channel editions
		if( !is_null($issueId) ) {
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			if( !DBIssue::isOverruleIssue( $issueId ) ) { // When it is normal issue, return the channel editions.
				$channelId = DBIssue::getChannelId( $issueId );
				return self::getChannelEditionInfos( null, array( 'id' => $channelId ) );	
			} else { // When it is Overrule issue, return the issue editions.
				require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
				return DBEdition::listIssueEditionsObj( $issueId );
			}
		} else { // When no issue specified, get the default publication channel editions.
			require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
			return DBEdition::listEditions( $pubId );
		}
	}
	
	public static function listPrevCurrentNextIssues($publid)
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

		$publrow = DBPublication::getPublication($publid);
		$defaultchannelid = $publrow['defaultchannelid'];
		
		if (empty($defaultchannelid)) {
			return null;
		}
		$rows = DBChannel::listPrevCurrentNextIssues($defaultchannelid);
		if (is_array($rows)) {
			foreach ($rows as &$row) {
				$row['issue'] = $row['name'];
			}
		}
		return $rows;
	}

	/**
	 * Determines if the (page) reading order is reversed for given publication/issue.
	 * If no issue is given or issue does not overrule pub, the publication determines the reading order.
	 *
     * @param string $pubId	  Publication id
     * @param string $issueId Issue id: Leave this empty if publication should leads the reading order.
     * @return boolean True for reversed read; False otherwise.
	 */
	public static function readingOrderReversed( $pubId, $issueId='' )
	{
		$issueObj = null;
		if( $issueId ) {
			require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php'; 
			$issueObj = DBAdmIssue::getIssueObj( $issueId );
		}
		if( $issueObj && $issueObj->OverrulePublication ) {
			$revRead = $issueObj->ReversedRead;
		} else {
			require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php'; 
			$pubObj = DBAdmPublication::getPublicationObj( $pubId );
			$revRead = $pubObj->ReversedRead;
		}
		return $revRead;
	}

	/**
	 * Retrieves a PubChannel object from DB for a given issue id.
	 *
	 * @param int $issueId
	 * @return object PubChannel
	 */
	public static function getChannelForIssue( $issueId )
	{
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$channelId = DBIssue::getChannelId( $issueId );
		$channelRow = DBChannel::getChannel( $channelId );
		
		$pubChannel = new PubChannel();
		$pubChannel->Id = $channelRow['id'];
		$pubChannel->Name = $channelRow['name'];
		return $pubChannel;
	}
	
	/**
	 * @since v7.6.0
	 * Returns publication channel given the edition id.
	 *
	 * @param int $editionId Db id of edition
	 * @return PubChannel|null $pubChannel publication channel of the edition| Null when it is not found.
	 */
	public static function getChannelForEdition( $editionId )
	{
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		
		$pubChannel = null;
		$channelId = DBEdition::getChannelIdViaEditionId( $editionId );
		if( $channelId ) {
			$channelRow = DBChannel::getChannel( $channelId );
			
			$pubChannel = new PubChannel();
			$pubChannel->Id = $channelRow['id'];
			$pubChannel->Name = $channelRow['name'];
		}	
		return $pubChannel;
	}

	/**
	 * To check if the given publication / overruleissue has DeadlineCalculations enabled.
	 *
	 * @param int $pubId Publication id.
	 * @param int $issueId Issue id, used to find out if it is a overrule issue, when this is not given, $pubId will be used.
	 * @return bool True when CalculateDeadlines is enabled, false otherwise.
	 */
	public static function isCalculateDeadlinesEnabled( $pubId, $issueId=0 )
	{
		$issueObj = null;
		if( $issueId ) {
			require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
			$issueObj = DBAdmIssue::getIssueObj( $issueId );
		}

		if( $issueObj && $issueObj->OverrulePublication ) {
			$calculateDeadlines = $issueObj->CalculateDeadlines;
		} else {
			require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
			$pubObj = DBAdmPublication::getPublicationObj( $pubId );
			$calculateDeadlines = $pubObj->CalculateDeadlines;
		}
		return $calculateDeadlines;
	}
}
