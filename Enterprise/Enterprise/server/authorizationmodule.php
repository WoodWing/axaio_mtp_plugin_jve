<?php

class authorizationmodule
{
	private $rights;
	private $user;
	
	public function __construct()
	{
	}
	
	public function getrights( $user, $publ = null, $issue = null, $sect = null, $type = null, $state = null, $issessionuser = true )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$this->rights = DBUser::getRightsCached( $user, $publ, $issue, $sect, $type, $state, $issessionuser );
		$this->user = $user;
	}
	
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
	 * @param string $check Access right to check. Pass one flag/char only. Pass EMTPY to check for presence of any access right.
	 * @param integer $publ Brand id.
	 * @param integer $issue Issue id. For object right checks, pass in the issue of the first target.
	 * @param integer $sect Category id.
	 * @param integer $type Object type.
	 * @param integer $state Status id.
	 * @param string $objectId
	 * @param string $contentSource
	 * @param string $documentId
	 * @return bool Whether or not access is gained.
	 * @throws BizException When bad function parameters are given.
	 */
	public function checkright(
		$check, $publ, $issue = null, $sect=null, $type = null, $state=null, 
		$objectId=null, $contentSource=null, $documentId=null )
	{
		if ( is_null( $publ ) ) { // $publ = 0 means all publications, so explicit check on null
			throw new BizException('ERR_ARGUMENT', 'Server', "Brand not specified");
		}
		
		if( strlen($check) > 1 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 
				'No support for checking multiple access rights at once: '.$check );
		}
		
		// BZ#4189 Logic is different if an issue overrules.
		// So to solve this we get the list of all overruling issues once in an array, then check if $issue != 0
		static $overruleIssues = null;
		if ( is_null( $overruleIssues ) && $issue ) {
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
			$hasAccess = BizContentSource::checkAccessForAlien( $this->user, $check,
							$publ, $overruleIssueId, $sect, $type, $state,
							$objectId, $contentSource, $documentId );
			// Note: $hasAccess can be TRUE (=allowed), FALSE (=disallowed) or NULL (=let core decide).
		} elseif( BizContentSource::isShadowObjectBasedOnProps( $contentSource, $documentId ) ) {
			$hasAccess = BizContentSource::checkAccessForShadow( $this->user, $check,
							$publ, $overruleIssueId, $sect, $type, $state,
							$objectId, $contentSource, $documentId );
			// Note: $hasAccess can be TRUE (=allowed), FALSE (=disallowed) or NULL (=let core decide).
		} else {
			$hasAccess = null;
		}
		
		// NULL means that the core server should do access rights checking.
		if( is_null($hasAccess) ) {
			$hasAccess = $this->coreCheckRight( $check, $publ, $overruleIssueId, $sect, $type, $state );
		}
		return $hasAccess;
	}
	
	/**
	 * Determines whether or not the user has access to a certain object, brand or
	 * overrule issue, by the rules of the core server.
	 * 
	 * When the object is in personal status, access is always gained. Else, it searches
	 * through the access rights setup within the brand (or overrule issue) from the most
	 * specific brand/category/objtype/status combination until the most global level
	 * (brand or overrule issue). At all levels, access rights profiles are checked to see
	 * if any of the rights matched the given $check. If so, access is gained.
	 *
	 * @param string $check Access right to check. Pass one flag/char only. Pass EMTPY to check for presence of any access right.
	 * @param integer $publ Brand id.
	 * @param integer $overruleIssueId Id of issue that overrules the brand. Zero for normal issues.
	 * @param integer $sect Category id.
	 * @param integer $type Object type.
	 * @param integer $state Status id.
	 * @return bool Whether or not access is gained.
	 */
	private function coreCheckRight( $check, $publ, $overruleIssueId, $sect, $type, $state )
	{
		$hasAccess = false;
		//EN-86861: User's were not able to perform any actions on objects in a Personal status when they
		//have authorization rules set on specific object statuses.
		//Since users own these objects, only them (and admin users) will be able to perform actions on them.
		//So at any time a right's check needs to be done for a Personal state, this will come from a permitted user.
		if( $state === -1 ) {
			$hasAccess = true;
		}
		// loop each authorization-record
		if( $this->rights ) foreach( $this->rights as $right ) {
			// check if record matches
			$match = true;
			if( $publ ) {
				$match = $match && ($publ == $right['publication'] || $right['publication'] == 0);
			}
			if( $overruleIssueId ) {
				$match = $match && ($overruleIssueId == $right['issue'] || $right['issue'] == 0);
			}
			if( $sect ) {
				$match = $match && ($sect == $right['section'] || $right['section'] == 0);
			}
			if( $type ) {
				$match = $match && ($type == $right['type'] || trim($right['type']) == '');
			}
			if( $state ) {
				$match = $match && ($state == $right['state'] || $right['state'] == 0);
			}
			if( $match ) {
				if( empty($check) || strstr($right['rights'], $check) ) {
					$hasAccess = true;
					break;
				}					
			}
		}
		return $hasAccess;
	}
}	
