<?php
/**
 * @since 		v8.3
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with static functions to control create/update/delete actions on object versions. 
 * 
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class Version_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Give a connector control over the creation of new version of an object.
	 *
	 * @param int $objectId Id of the object
	 * @param string $sourceVersion (<major>.<minor>)
	 * @param string $nextVersion (<major>.<minor>)
	 * @param string $storename Reference to the filestore.
	 * @param boolean $setObjPropMode Optional. Special case for SetObjectProperties context, conditionally creating versions.
	 */
	public function createVersion( $objectId, $sourceVersion, $nextVersion, $storename, $setObjPropMode )
	{
	}

	/**
	 * Give a connector control over the delete of a version of an object.
	 *
	 * @param int $objectId Id of the object
	 * @param string $version (<major>.<minor>)
	 * @param string $storeName Reference to the filestore.
	 */
	public function deleteVersion( $objectId, $version, $storeName )
	{
	}

	/**
	 * Enable a Content Source plug-in to overwrite the object version numbers of Enterprise.
	 *
	 * This method should only be implemented by Content Source plug-ins.
	 *
	 * @return array with index the content source id and value true/false.
	 */
	public function useContentSourceVersion()
	{
		return array();
	}
}