<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business logics and rules that can handle admin template object access rule operations.
 * This is all about the configuration of template object access rules in the workflow definition.
 *
 * This class provides functions for validation and validates the user access and the user input that is sent
 * in a request. Only if everything is valid an operation will be performed on the data.
 */

class BizAdmTemplateObject
{
	/**
	 * Checks if an user has admin access to the publication. System
	 * admins have access to all pubs.
	 *
	 * @param integer|null $pubId The publication id. Null to check if user is admin in some or more publications (just any).
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
	 * Validates an template object access rule.
	 *
	 * All attributes of a rule are tested to see if they have valid values. If they don;t, a BizException is thrown.
	 *
	 * @param AdmTemplateObjectAccess $templateObject The template object access rule to be validated.
	 * @throws BizException when anything is wrong with the template object access rule.
	 */
	private static function validateTemplateObject( AdmTemplateObjectAccess $templateObject )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmTemplateObject.class.php';

		if( !$templateObject->TemplateObjectId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'A template object id should be given.' );
		} elseif( $templateObject->TemplateObjectId <= 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'The template object id should be a positive number.' );
		}
		if( !DBAdmTemplateObject::getTemplateObjectsByObjectId( array( $templateObject->TemplateObjectId ) ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given object does not exist.', null,
				array( '{DOSSIER_TEMPLATE}', $templateObject->TemplateObjectId ) );
		}

		if( !isset( $templateObject->UserGroupId ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'A user group id should be given.' );
		} elseif( $templateObject->UserGroupId < 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'The user group id should be a positive number.' );
		} elseif( $templateObject->UserGroupId > 0 ) { //A user group id of 0 means 'all' user groups.
			$params = array( intval( $templateObject->UserGroupId ) );
			if( !DBBase::getRow( 'groups', '`id` = ?', 'id', $params ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given user group does not exist.', null,
					array( '{GRP_GROUP}', $templateObject->UserGroupId ) );
			}
		}

		if( DBAdmTemplateObject::templateObjectExists( $templateObject ) ) {
			throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client', '', null,
				array( '{DOSSIER}', $templateObject->TemplateObjectId.' '.$templateObject->UserGroupId ) );
		}
	}

	/**
     * Gives end-users authority to perform operations on a template.
     *
     * @param AdmTemplateObjectAccess[] $templateObjects
     */
	public static function addTemplateObjects( array $templateObjects )
	{
		self::checkPubAdminAccess( $templateObjects[0]->PublicationId );
		require_once BASEDIR.'/server/dbclasses/DBAdmTemplateObject.class.php';
		foreach( $templateObjects as $templateObject ) {
			self::validateTemplateObject( $templateObject );
			DBAdmTemplateObject::addTemplateObject( $templateObject );
		}
	}

	/**
	 * Requests template object access rules based on an object id, user group, publication and/or issue.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $issueId The issue id.
	 * @param integer $objectId The object id.
	 * @param integer $groupId The user group id.
	 * @return AdmTemplateObjectAccess[]
	 */
	public static function getTemplateObjects( $pubId, $issueId, $objectId, $groupId )
	{
		self::checkPubAdminAccess( $pubId );
		require_once BASEDIR.'/server/dbclasses/DBAdmTemplateObject.class.php';
		return DBAdmTemplateObject::getTemplateObjects( $pubId, $issueId, $objectId, $groupId );
	}

	/**
     * Removes access from a template for a certain user group.
     *
     * @param AdmTemplateObjectAccess[] $templateObjects A list of template object access rules to be deleted.
     */
	public static function removeTemplateObjects( array $templateObjects )
	{
		self::checkPubAdminAccess( $templateObjects[0]->PublicationId );
		require_once BASEDIR . '/server/dbclasses/DBAdmTemplateObject.class.php';
		foreach( $templateObjects as $templateObject ) {
			DBAdmTemplateObject::removeTemplateObject( $templateObject->TemplateObjectId, $templateObject->UserGroupId );
		}
	}

	/**
	 * Retrieves the information of template objects and returns this as RequestModes information.
	 *
	 * @param integer[] $templateObjectIds The list of object ids.
	 * @return AdmObjectInfo[] An array of ObjectInfo objects. If none can be found the array is empty.
	 */
	public static function getObjectInfos( array $templateObjectIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmTemplateObject.class.php';
		return DBAdmTemplateObject::getTemplateObjectsByObjectId( $templateObjectIds );
	}

	/**
	 * Retrieves information of objects by a type.
	 *
	 * @param integer $pubId The publication id.
	 * @param integer $issueId The issue id.
	 * @param string $type The type of the requested objects
	 * @return AdmIdName[] A list of the found object ids and their names.
	 */
	public static function listObjectsIdNameByType( $pubId, $issueId, $type )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmTemplateObject.class.php';
		return DBAdmTemplateObject::getObjectsByType( $pubId, $issueId, $type );
	}
}
