<?php

/**
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 **/

require_once BASEDIR.'/server/dbclasses/DBAdmActionProperty.class.php';

class BizAdmActionProperty
{
	/**
	 * Insert an AdmPropertyUsage into the database.
	 *
	 * @static
	 * @param AdmPropertyUsage $obj
	 * @return AdmPropertyUsage $obj The Created AdmPropertyUsage
	 */
	public static function insertAdmPropertyUsage( AdmPropertyUsage $obj )
	{
		DBAdmActionProperty::insertAdmPropertyUsage($obj);
		return $obj;
	}

	/**
	 * Updates an AdmPropertyUsage in the database.
	 *
	 * @static
	 * @param AdmPropertyUsage $obj
	 * @return AdmPropertyUsage
	 */
	static public function updateAdmPropertyUsage( AdmPropertyUsage $obj )
	{
		DBAdmActionProperty::updateAdmPropertyUsage($obj);
		return $obj;
	}

	/**
	 * Deletes an AdmPropertyUsage.
	 *
	 * @static
	 * @param AdmPropertyUsage $obj The object to be deleted.
	 * @return bool Whether or not the operation was succesful.
	 */
	public static function deleteAdmPropertyUsage( AdmPropertyUsage $obj )
	{
		return DBAdmActionProperty::deleteAdmPropertyUsage($obj);
	}

	/**
	 * Deletes all action properties based on a specific action and the document ID.
	 *
	 * @static
	 * @param string $action The action for which to delete the action properties.
	 * @param string $documentId The documentId for which to delete the action properties.
	 * @return bool Whether or not the operation was succesful.
	 */
	public static function deleteAdmPropertyUsageByActionAndDocumentId( $action, $documentId )
	{
		return DBAdmActionProperty::deleteAdmPropertyUsageByActionAndDocumentId( $action, $documentId );
	}

	/**
	 * Retrieve an existing record from smart_actionproperties table as an AdmPropertyUsage object.
	 *
	 * @param int $id Record id at DB.
	 * @return AdmPropertyUsage object
	 */
	static public function getAdmPropertyUsage( $id )
	{
		return DBAdmActionProperty::getAdmPropertyUsage( $id );
	}

	/**
	 * Retrieves all AdmPropertyUsages based on the supplied criteria.
	 *
	 * Leave $action null to retrieve records for all actions.
	 * Leave $documentId null to retrieve records for all documentIds.
	 *
	 * @param null|string $action The action to retrieve the usage objects for.
	 * @param null|string $documentId The Document Id to retrieve the usage objects for.
	 * @return AdmPropertyUsage[] An array of usage objects.
	 */
	static public function getAdmPropertyUsages($action=null, $documentId=null)
	{
		return DBAdmActionProperty::getAdmPropertyUsages($action, $documentId);
	}
}