<?php
/**
 * PublicationManager provides access to information about our publication structure.
 * For ease of use, all fucntions can be suppressed from throwing exceptions
 * 
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class PubMgr
{
	/**
	 * Return publications for specified user
	 *
	 * @param string $user
	 * @param boolean $full return full info (PublicationInfo) or only Punlication
	 * @param boolean $throwExceptions should exception be thrown in case of error
	 * @throws BizException
	 * @return array of Publication or PublicationInfo (depending on $full), null in case of error with $throwExceptions=false
	 */
	public static function getPublications( $user, $full, $throwExceptions=true)
	{
		require_once BASEDIR."/server/bizclasses/BizPublication.class.php";
		try{
			return BizPublication::getPublications( $user, $full ? 'full' : 'flat' );
		} catch(BizException $e) {
			if( $throwExceptions ) {
				throw($e);
			}
		}
		return null;
	}
	
	/**
	 * Return issues for specified pub
	 *
	 * @param string $user
	 * @param string $publication id
	 * @param boolean $full return full info (SectionInfo) or only Section
	 * @param boolean $throwExceptions should exception be thrown in case of error
	 * @throws BizException
	 * @return array of Issue or IssueInfo (depending on $full), null in case of error with $throwExceptions=false
	 */
	public static function getIssues( $user, $publication, $full, $throwExceptions=true )
	{
		require_once BASEDIR."/server/bizclasses/BizPublication.class.php";
		try{
			return BizPublication::getIssues($user, $publication, $full ? 'full' : 'flat');
		} catch(BizException $e) {
			if( $throwExceptions ) {
				throw($e);
			}
		}
		return null;
	}

	/**
	 * Return editions for specified pub/issue. Caller does not have to worry about overrule pubs
	 *
	 * @param string $publication id
	 * @param string $issue if
	 * @param boolean $throwExceptions should exception be thrown in case of error
	 * @throws BizException
	 * @return array of Edition, null in case of error with $throwExceptions=false
	 */
	public static function getEditions( $publication, $issue, $throwExceptions=true )
	{
		require_once BASEDIR."/server/bizclasses/BizPublication.class.php";
		try{
			return BizPublication::getEditions( $publication, $issue );
		} catch(BizException $e) {
			if( $throwExceptions ) {
				throw($e);
			}
		}
		return null;
	}

	/**
	 * Return sections for specified pub/issue. Caller does not have to worry about overrule pubs
	 *
	 * @param string $user
	 * @param string $publication id
	 * @param string $issue if
	 * @param boolean $full return full info (SectionInfo) or only Section
	 * @param boolean $throwExceptions should exception be thrown in case of error
	 * @throws BizException
	 * @return array of Section or SectionInfo (depending on $full), null in case of error with $throwExceptions=false
	 */
	public static function getSections( $user, $publication, $issue, $full, $throwExceptions=true )
	{
		require_once BASEDIR."/server/bizclasses/BizPublication.class.php";
		try{
			return BizPublication::getSections( $user, $publication, $issue, $full ? 'full' : 'flat', true );
		} catch(BizException $e) {
			if( $throwExceptions ) {
				throw($e);
			}
		}
		return null;
	}
	
	/**
	 * Returns states for specified pub/issue. Caller does not have to worry about overrule issues.
	 *
	 * @param unknown_type $user
	 * @param unknown_type $publication id
	 * @param unknown_type $issue id
	 * @param unknown_type $type object type to get states for
	 * @param boolean $throwExceptions should exception be thrown in case of error
	 * @throws BizException
	 * @return array of State, null in case of error with $throwExceptions=false
	 */
	public static function getStates( $user, $publication, $issue, $type, $throwExceptions=true )
	{
		require_once BASEDIR."/server/bizclasses/BizWorkflow.class.php";
		try{
			return BizWorkflow::getStates( $user, $publication, $issue, null, $type );
		} catch(BizException $e) {
			if( $throwExceptions ) {
				throw($e);
			}
		}
		return null;
	
	}
}
