<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business logic and rules that can handle publication admin authorization operations.
 * This is all about the configuration of publication admin authorization rules in the admin definition.
 *
 * This class provides functions for validation and validates the user access and the user input sent in a request.
 * Only if everything is valid an operation will be performed on the data.
 */

class BizAdmPublicationAdminAuthorization
{
	/**
	 * Checks if an user has admin access to the publication. System admins have access to all pubs.
	 *
	 * @param integer $pubId The publication id. Null to check if user is admin in some or more publications (just any).
	 * @throws BizException When user has no access.
	 */
	private static function checkPubAdminAccess( $pubId )
	{
		$user = BizSession::getShortUserName();
		$dbDriver = DBDriverFactory::gen();
		$isPubAdmin = hasRights( $dbDriver, $user ) || // system admin?
			( publRights( $dbDriver, $user ) && checkPublAdmin( $pubId, false ) ); // explicit pub admin?

		if( !$isPubAdmin ) {
			throw new BizException( 'ERR_AUTHORIZATION', 'Client', null );
		}
	}

	/**
	 * Validate a PublicationAdminAuthorization rule.
	 *
	 * It is tested if the rule already exists in the database or if there are any problems with the user group id.
	 *
	 * @param integer $publicationId The publication id.
	 * @param integer $userGroupId The user group id.
	 * @throws BizException when any of the properties is invalid.
	 */
	private static function validatePublicationAdminAuthorization( $publicationId, $userGroupId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPublicationAdminAuthorization.class.php';
		if( DBAdmPublicationAdminAuthorization::publicationAdminAuthorizationExists( $publicationId, $userGroupId ) ) {
			throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client', 'An authorization rule with these settings already exists.',
				null, array( '{AUT_ADMIN_AUTHORIZATIONS}', $publicationId . ' ' . $userGroupId ) );
		}

		if( $userGroupId < 1 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'The user group id should be positive.' );
		}
		$params = array( intval( $userGroupId ) );
		if( !DBBase::getRow( 'groups', '`id` = ?', 'id', $params ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given user group does not exist.',
				null, array( '{GRP_GROUP}', $userGroupId ) );
		}
	}

	/**
	 * Authorises end-users that are member of a given group to a given publication.
	 *
	 * @param integer $publicationId The publication id
	 * @param integer[] $userGroupIds List of user group ids.
	 */
	public static function createPublicationAdminAuthorizations( $publicationId, array $userGroupIds )
	{
		self::checkPubAdminAccess( $publicationId );

		require_once BASEDIR.'/server/dbclasses/DBAdmPublicationAdminAuthorization.class.php';
		foreach( $userGroupIds as $userGroupId ) {
			self::validatePublicationAdminAuthorization( $publicationId, $userGroupId );
			DBAdmPublicationAdminAuthorization::createPublicationAdminAuthorization( $publicationId, $userGroupId );
		}
	}

	/**
	 * Request publication admin authorization rules by publication id.
	 *
	 * @param integer $publicationId The publication id.
	 * @return integer[] The list of user group ids belonging to the publication.
	 */
	public static function getPublicationAdminAuthorizations( $publicationId )
	{
		self::checkPubAdminAccess( $publicationId );

		require_once BASEDIR.'/server/dbclasses/DBAdmPublicationAdminAuthorization.class.php';
		return DBAdmPublicationAdminAuthorization::getPublicationAdminAuthorizations( $publicationId );
	}

	/**
	 * Delete publication admin authorization rules by publication and user group ids.
	 *
	 * Deletion happens by publication, but the selection can be narrowed by providing user
	 * group ids within that publication.
	 *
	 * @param integer $publicationId The publication id.
	 * @param integer[]|null $userGroupIds The list of user group ids.
	 */
	public static function deletePublicationAdminAuthorizations( $publicationId, $userGroupIds )
	{
		self::checkPubAdminAccess( $publicationId );

		require_once BASEDIR.'/server/dbclasses/DBAdmPublicationAdminAuthorization.class.php';
		DBAdmPublicationAdminAuthorization::deletePublicationAdminAuthorizations( $publicationId, $userGroupIds );
	}
} 