<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v6.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

class BizAdmPubObject
{
	/**
	 * Checks server plugin error
	 *
	 * @param string $issueId Issue id
	 * @return string of error messages
	**/
	static function checkPluginError( $issueId )
	{
		$error = '';
		try {
			$pluginUniqueName = 'ContentStationListDossiers';	// Content Station List Dossiers
			self::checkActivePlugin( $pluginUniqueName );
		} catch( BizException $e ) {
			$error .= $e->getMessage();
		}
		if( $issueId > 0 ) {
			try {
				$pluginUniqueName = 'ContentStationOverruleCompatibility'; // Content Station Overrule Compability
				self::checkActivePlugin( $pluginUniqueName );
			} catch( BizException $e ) {
				$error .= '<br>' . $e->getMessage();
			}
		}
		return $error;
	}

	/**
	 * Checks if the server plugin is active
	 *
	 * @param string $uniqueName Unique name of the server plugin
	 * @throws BizException When server plugin is not active
	**/
	static function checkActivePlugin( $uniqueName )
	{
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		$info = new PluginInfoData(); 
		$info->UniqueName = $uniqueName;
		$info = DBServerPlugin::getPlugin( $info );
		if( !$info->IsActive ) {
			throw new BizException( 'ERR_NONACTIVE_PLUGIN', 'client', null, null, array($info->DisplayName) );
		}
	}

	/**
     * Create Pub Object
     *
     * @param string $pubId Publication id
     * @param string $issueId Issue id
     * @param string $objectId Object Id
     * @param string $groupId User group Id
    **/
	public static function createPubObject( $pubId, $issueId, $objectId, $groupId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPubObject.class.php';
		$pubObject = self::listPubObjects( $pubId, $issueId, $objectId, $groupId );
		$newPubObjId = null;
		if( !$pubObject ) {
			$newPubObjId = DBAdmPubObject::createPubObject( $pubId, $issueId, $objectId, $groupId );
		}
		self::checkDelete( $newPubObjId, $pubId, $issueId, $objectId, $groupId );
	}

	/**
     * Modify Pub Object
     *
	 * @param string $id The db id of the publobjects table.
	 * @param string $pubId Publication id.
	 * @param string $issueId Issue id.
	 * @param string $objectId Object id that belongs to the publication $pubId and issue $issueId
	 * @param string $groupId Usergroup id
	 */
	public static function modifyPubObjects( $id, $pubId, $issueId, $objectId, $groupId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPubObject.class.php';
		$pubExist = DBAdmPubObject::getPubObject( $id );
		$modifyPubObjectId = null;
		if( $pubExist ) {
			$modifyPubObjectRow = DBAdmPubObject::modifyPubObject( $id, $objectId, $groupId );
			$modifyPubObjectId = $modifyPubObjectRow['id'];
		}
		self::checkDelete( $modifyPubObjectId, $pubId, $issueId, $objectId, $groupId );
	}

	/**
     * Check and delete publication object when groupid = 0(ALL)
     *
     * @param string $pubObjectId DB id of the publobjects table.
     * @param string $pubId Id of publication
     * @param string $issueId Id of Issue
     * @param string $objectId Object id
     * @param string $groupId Group Id
    **/
	public static function checkDelete( $pubObjectId, $pubId, $issueId, $objectId, $groupId )
	{
		if( $pubObjectId && $groupId == 0 ) {
			$deletePubObjects = self::listPubObjects( $pubId, $issueId, $objectId );
			if( count($deletePubObjects) > 1 ) {
				foreach( $deletePubObjects as $deletePubObject ) {
					if( $deletePubObject->GroupId != 0 ) {
						self::deletePubObjects($deletePubObject->Id);
					}
				}
			}
		}
	}

	/**
     * Delete Pub Object
     *
     * @param string $id Id of publication object
     * @param string $objectId Object Id
    **/
	public static function deletePubObjects( $id, $objectId = '0' )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPubObject.class.php';
		if( $id > 0 ) {
			DBAdmPubObject::deletePubObjectById( $id );
		}
		if( $objectId > 0 ) {
			DBAdmPubObject::deletePubObjectsByObject( $objectId );
		}
	}

	/**
     * List Pub Objects
     *
     * @param string $pubId publication id
     * @param string $issueId Issue Id
     * @param string $objectId Object Id
     * @param string|null $groupId Group Id
     * @return array of Pub Objects
    **/
	public static function listPubObjects( $pubId, $issueId = '0', $objectId = '0', $groupId = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPubObject.class.php';
    	$pubObjects = DBAdmPubObject::getPubObjects( $pubId, $issueId, $objectId, $groupId );
 
    	return $pubObjects;
	}

	
	/**
     * List Dossier Templates
     *
     * @param string $pubId publication id
     * @param string $issueId Issue Id
     * @return array of Dossier Templates
    **/
	public static function listDossierTemplates( $pubId, $issueId = '0' )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPubObject.class.php';
    	$dossierTemplates = DBAdmPubObject::getDossierTemplates( $pubId, $issueId );
 
    	return $dossierTemplates;
	}
	
	/**
     * List Dossier Templates
     *
     * @param string $pubId publication id
     * @param string $issueId Issue Id
     * @return array of Dossier Templates
    **/
	public static function listDossierTemplatesIdName( $pubId, $issueId = '0' )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmPubObject.class.php';
		$dossierTemplatesIdName = array();
    	$dossierTemplates = self::listDossierTemplates( $pubId, $issueId );
 		foreach( $dossierTemplates as $dossierTemplate ){
 			$dossierTemplatesIdName[$dossierTemplate['id']] = $dossierTemplate['name'];
 		}
    	return $dossierTemplatesIdName;
	}
}