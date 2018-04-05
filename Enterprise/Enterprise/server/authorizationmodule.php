<?php

class authorizationmodule
{
	private $rights;
	private $user;
	private $userFullName;

	/**
	 * Caches access rights of user based on group membership, profiles and workflow. The authorizations are
	 * defined either on Brand level or issue level in case the issue is an overrule brand issue. The definition of
	 * the rights can be very granular. Access rights are defined by access profiles. Access profiles consist of
	 * groups of features. A part of the features (rights) are enforced server side. These rights are handled here.
	 * The other features are passed to the clients and are enforced at that side. On brand (or overrule brand
	 * issue level) the workflow states are defined. Next, combinations of profiles and and workflow statuses are
	 * linked to groups of users. This linking can even be done on category level.
	 * Resolving the rights of a user is quite time consuming. To improve performance all rights of the user are cached.
	 * These cached rights are used to filter the rights at a specific /brand/workflow stateId/category level.
	 *
	 * @param string $user short user name.
	 * @param integer $publ The Brand Id ( 0 means all Brands)
	 * @param integer $issue The Overrule brand issue Id ( 0 means all issues )
	 * @param integer $categoryId The Category Id, formerly know as section ( 0 means all Categories ).
	 * @param string $type The object type of the workflow status.
	 * @param integer $stateId The workflow status Id ( 0 means all states ).
	 * @param bool $isSessionUser Used to indicate a different user. No longer needed because rights are cached per user.
	 */
	public function getRights( $user, $publ = null, $issue = null, $categoryId= null, $type = null, $stateId = null, $isSessionUser = true )
	{
		if( $isSessionUser == false ) {
			LogHandler::Log( __FUNCTION__, 'WARN',
				'Parameter $isSessionUser is obsolete since version 9.9.0. Caller must not pass the parameter.' );
		}

		$this->rights = $this->getRightsCached( $user, $publ, $issue, $categoryId, $type, $stateId, $isSessionUser );
		$this->user = $user;
		$this->userFullName = DBUser::getCachedUserFullName( $user );
	}

	/**
	 * Returns an array with the authorizations a user has, based on the group(s) the user belongs to and the access
	 * profiles. Authorisations can be set up for specific workflows.
	 *
	 * @param string $user short user name.
	 * @param integer $publ The Brand Id ( 0 means all Brands)
	 * @param integer $issue The Overrule brand issue Id ( 0 means all issues )
	 * @param integer $categoryId The Category Id, formerly know as section ( 0 means all Categories ).
	 * @param string $type The object type of the workflow status.
	 * @param integer $stateId The workflow status Id ( 0 means all states ).
	 * @param bool $isSessionUser Used to indicate a different user. No longer needed because rights are cached per user.
	 * @return array Authorizations of the user on the specified /brand/workflow stateId/category level.
	 * @since 9.9.0 (Moved from DBUser.class.php)
	 */
	public function getRightsCached( $user, $publ, $issue, $categoryId, $type, $stateId, $isSessionUser = true )
	{
		static $cachedRightsByUsers = array(); // cache for Rights

		if( !array_key_exists( $user, $cachedRightsByUsers ) ) {
			$rightsByUser = array();
			$authorizationRows = $this->getAuthorizationsByUser( $user );
			$uniqueProfiles = $this->getProfilesFromAuthorizationRows( $authorizationRows );

			if( $uniqueProfiles ) {
				require_once BASEDIR . '/server/dbclasses/DBFeature.class.php';
				$workflowFeaturesByProfiles = DBFeature::getEnabledWorkflowFeaturesByProfiles( array_keys( $uniqueProfiles ) );
				$rightsByProfiles = $this->addFeatureFlagToProfiles( $workflowFeaturesByProfiles, $uniqueProfiles );
				$rightsByUser = $this->addRightsToAuthorizationRows( $authorizationRows, $rightsByProfiles );
			}
			$cachedRightsByUsers[$user] = $rightsByUser;
		}

		$result = array();
		$cachedRightsByUser = $cachedRightsByUsers[ $user ];
		if( $cachedRightsByUser ) foreach( $cachedRightsByUser as $cachedRightByUser ) {
			$add = $this->matchedRight( $cachedRightByUser, $publ, $issue, $categoryId, $stateId, $type );
			if( $add === true ) {
				$result[] = $cachedRightByUser;
			}
		}

		return $result;
	}

	/** Returns the authorizations of a user based on the user-groups the user belongs to.
	 *
	 * @param string $user short user name.
	 * @return array Authorization rows extended with profile names and the object type of the workflow stateId.
	 * @since 9.9.0
	 */
	private function getAuthorizationsByUser( $user )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$result  = DBUser::getRights( $user, null, null, null, null, null );

		return $result;
	}

	/** Filters the profiles from the authorizations rows. Duplicates are skipped.
	 *
	 * @param array $authorizationRows Authorization rows extended with profile names and the object type of the workflow stateId.
	 * @return array indexed by profile ids.
	 * @since 9.9.0
	 */
	private function getProfilesFromAuthorizationRows( array $authorizationRows )
	{
		$uniqueProfiles = array();
		foreach( $authorizationRows as $authorizationRow ) {
			if( !isset($uniqueProfiles[$authorizationRow['profile']]) ) {
				$uniqueProfiles[$authorizationRow['profile']] = '';
			}
		}

		return $uniqueProfiles;
	}

	/**
	 * Translates the features by profile to the right/access flags by profile. Features that are checked server-side
	 * are labelled by a flag (a character). E.g the feature with id = 1 refers to View right which has the flag 'V'.
	 *
	 * @param array $featuresByProfiles Array with arrays of feature and profile Ids pairs.
	 * @param array $profiles key/value pairs of profile Id and empty string.
	 * @return array key is the profile Id and the value is a concatenation of server-side right/access flags.
	 * @since 9.9.0
	 */
	private function addFeatureFlagToProfiles( array $featuresByProfiles, array $profiles )
	{
		require_once BASEDIR . '/server/bizclasses/BizAccessFeatureProfiles.class.php';
		$rightsByProfiles = $profiles;
		$features =
			BizAccessFeatureProfiles::getFileAccessProfiles() +
			BizAccessFeatureProfiles::getAnnotationsAccessProfiles() +
			BizAccessFeatureProfiles::getWorkflowAccessProfiles();

		foreach( $featuresByProfiles as $featuresByProfileRow ){
			$featureId = $featuresByProfileRow['feature'];
			$rightsByProfiles[$featuresByProfileRow['profile']] .= $features[$featureId]->Flag;
		}

		return $rightsByProfiles;
	}

	/**
	 * Adds the feature profile flags to the authorizations rows.
	 *
	 * @param array $authorizationRows Authorization rows extended with profile names and the object type of the workflow stateId.
	 * @param array $profiles Array with key the profile Id and value the access/rights flags of the profile.
	 * @return array $authorizationsRows extended with the access/rights flags.
	 * @since 9.9.0
	 */
	private function addRightsToAuthorizationRows( array $authorizationRows, array $profiles )
	{
		$rightsByAuthorizations = array();
		foreach ( $authorizationRows as $authorizationRow ) {
			$rightsByAuthorization = $authorizationRow;
			$rightsByAuthorization['rights'] = $profiles[$authorizationRow['profile']];
			$rightsByAuthorizations[] = $rightsByAuthorization;
		}

		return $rightsByAuthorizations;
	}

	/**
	 * Returns the access rights that are cached during the session. Rights are cached per user.
	 *
	 * @return array Access rights by user.
	 */
	public function getCachedRights()
	{
		return $this->rights;
	}

	/**
	 * Checks if the given user has access to a certain object or brand context.
	 *
	 * Aside to brands, it also deals with overrule issues. To check access to a specific object,
	 * just pass in the issue id of the 'first' target. That could be an overrule issue or a
	 * normal issue, which will be found out by this function.
	 *
	 * This function should be called after {@link:getrights()} which allows you to specify
	 * the user. Note that the other parameters should be matching with the two functions.
	 * However, for a better performance, for the getrights() you can pass in less parameters
	 * to prepare a more global context to search access rights for, and call checkright()
	 * many times with more specific parameters, but all should fit within the prepared context.
	 *
	 * Since 9.4 there is support for Content Source access right checking. When the shadow
	 * object was introduced by a certain Content Source, that connector is asked to do
	 * access right checking. However, the connector can still leave that for the core server.
	 *
	 * @param string $accessRight Access right to check. Pass one flag/char only. Pass EMPTY to check for presence of any access right.
	 * @param integer $publ Brand id.
	 * @param integer $issue Issue id. For object right checks, pass in the issue of the first target.
	 * @param integer $categoryId Category id, formerly known as section.
	 * @param integer $type Object type.
	 * @param integer $stateId Status id.
	 * @param string $objectId
	 * @param string $contentSource
	 * @param string $documentId
	 * @param string $routeTo User to which an object is routed or will be routed to.
	 * @return bool Whether or not access is gained.
	 * @throws BizException When bad function parameters are given.
	 */
	public function checkright(
		$accessRight, $publ, $issue = null, $categoryId=null, $type = null, $stateId = null,
		$objectId = null, $contentSource = null, $documentId = null, $routeTo = '' )
	{
		if( is_null( $publ ) ) { // $publ = 0 means all publications, so explicit check on null
			throw new BizException('ERR_ARGUMENT', 'Server', "Brand not specified");
		}

		if( strlen($accessRight) > 1 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server',
				'No support for checking multiple access rights at once: '.$accessRight );
		}

		// BZ#4189 Logic is different if an issue overrules a brand.
		// So to solve this we get the list of all overruling issues once in an array, then check if $issue != 0
		static $overruleIssues = null;
		if( is_null( $overruleIssues ) && $issue ) {
			require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
			$overruleIssues = DBIssue::listAllOverruleIssues();
		}
		$overruleIssueId = 0;
		if( $issue && !empty($overruleIssues) ) {
			if( in_array( $issue, $overruleIssues ) ) {
				$overruleIssueId = $issue;
			}
		}

		// When it is an alien or shadow object, ask the content source connector
		// to do access right checking.
		require_once BASEDIR.'/server/bizclasses/BizContentSource.class.php';
		if( $objectId && BizContentSource::isAlienObject( $objectId ) ) {
			$hasAccess = BizContentSource::checkAccessForAlien( $this->user, $accessRight,
				$publ, $overruleIssueId, $categoryId, $type, $stateId,
				$objectId, $contentSource, $documentId );
			// Note: $hasAccess can be TRUE (=allowed), FALSE (=disallowed) or NULL (=let core decide).
		} elseif( BizContentSource::isShadowObjectBasedOnProps( $contentSource, $documentId ) ) {
			$hasAccess = BizContentSource::checkAccessForShadow( $this->user, $accessRight,
				$publ, $overruleIssueId, $categoryId, $type, $stateId,
				$objectId, $contentSource, $documentId );
		} else {
			$hasAccess = null;
		}

		// Note: $hasAccess can be TRUE (=allowed), FALSE (=disallowed) or NULL (=let core decide).
		if( is_null($hasAccess) ) {
			$hasAccess = $this->coreCheckRight( $accessRight, $publ, $overruleIssueId, $categoryId, $type, $stateId, $routeTo );
		}

		return $hasAccess;
	}

	/**
	 * Determines whether or not the user has access to a certain object, brand or
	 * overrule issue, by the rules of the core server.
	 *
	 * When the object is in personal status, access is always gained. Else, it searches
	 * through the access rights setup within the brand (or overrule issue) from the most
	 * specific brand/category/object-type/status combination until the most global level
	 * (brand or overrule issue). At all levels, access rights profiles are checked to see
	 * if any of the rights matched the given $check. If so, access is gained.
	 * A special case is the Personal stateId ($stateId = -1). If an object is in personal stateId the it is routed to, or
	 * will be routed to, the user setting the stateId to 'Personal'. This user is always entitled to access the object.
	 * Since users own these objects, only them (and admin users?) will be able to perform actions on them.
	 *
	 * @param string $accessRight Access right to check. Pass one flag/char only. Pass EMPTY to check for presence of any access right.
	 * @param integer $publ Brand id.
	 * @param integer $overruleIssueId Id of issue that overrules the brand. Zero for normal issues.
	 * @param integer $categoryId Category id, formerly known as section.
	 * @param string $type Object type.
	 * @param integer $stateId Status id.
	 * @param string $routeToUser User to which an object is routed or will be routed to.
	 * @return bool Whether or not access is gained.
	 */
	private function coreCheckRight( $accessRight, $publ, $overruleIssueId, $categoryId, $type, $stateId, $routeToUser )
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		$hasAccess = $this->ownsPersonalState( $stateId, $routeToUser ) ||
			( DBTicket::getOriginatingApplicationName( BizSession::getTicket() ) == 'InDesign Server' );

		// loop each authorization-record
		if( !$hasAccess ) {
			if( $this->rights ) foreach ( $this->rights as $right ) {
				// check if record matches
				$match = $this->matchedRight( $right, $publ, $overruleIssueId, $categoryId, $stateId, $type );
				if( $match ) {
					if( empty( $accessRight ) || strstr( $right['rights'], $accessRight ) ) {
						$hasAccess = true;
						break;
					}
				}
			}
		}

		return $hasAccess;
	}

	/**
	 * Checks if an defined access right matches with a specific set of publication/overrule issue/ category etc.
	 *
	 * A match is found if for example the publication of the access right is the same as the one to be checked or if
	 * the publication of the access right is 0, meaning applicable for all publications. An access right on a lower level
	 * is only applicable if there is also a match on the higher level(s). So if the category matches also the
	 * publication must match.
	 *
	 * @param array $right Authorization on a specific /brand/workflow stateId/category level.
	 * @param integer $publ Brand id.
	 * @param integer $overruleIssueId Id of issue that overrules the brand. Zero for normal issues.
	 * @param integer $sect Category id.
	 * @param integer $stateId Status id.
	 * @param string $type type
	 * @return boolean matching right is found, true, else false.
	 */
	private function matchedRight( array $right, $publ, $overruleIssueId, $sect, $stateId, $type )
	{
		$match = true;
		if( $publ ) {
			$match = $match && ( $publ == $right['publication'] || $right['publication'] == 0 );
		}
		if( $overruleIssueId ) {
			$match = $match && ( $overruleIssueId == $right['issue'] || $right['issue'] == 0 );
		}
		if( $sect ) {
			$match = $match && ( $sect == $right['section'] || $right['section'] == 0 );
		}
		if( $type ) {
			$match = $match && ( $type == $right['type'] || trim( $right['type'] ) == '' );
		}
		if( $stateId ) {
			$match = $match && ( $stateId == $right['state'] || $right['state'] == 0 );
		}

		return $match;
	}

	/**
	 * Checks if the context of an operation is 'Personal State'. This means that an object is still outside the
	 * workflow area. If the acting user and the route to user (or the user that will become the route to user) are the
	 * same and the status is -1 this means the object is in personal stateId.
	 *
	 * @param integer $stateId Id of the workflow stateId.
	 * @param string $routeTo Short username of the Route To user.
	 * @return bool Personal stateId is applicable.
	 */
	private function ownsPersonalState( $stateId, $routeTo )
	{
		$ownsPersonalState = false;
		if( $stateId == -1 && ( $routeTo == $this->user || $routeTo = $this->userFullName ) ) {
			$ownsPersonalState = true;
		}

		return $ownsPersonalState;
	}
}	
