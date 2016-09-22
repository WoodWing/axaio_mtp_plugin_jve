<?php

/**
 * @package 	Enterprise
 * @subpackage ServerPlugins
 * @since 		v8.3
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Implements the object version control.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/Version_EnterpriseConnector.class.php';

class Elvis_Version extends Version_EnterpriseConnector
{
	/**
	 * Content Sources are free to set their own version.
	 *
	 * @return bool Use Content Source version.
	 */
	public function useContentSourceVersion()
	{
		require_once dirname(__FILE__).'/Elvis_ContentSource.class.php';
		$contentSource = new Elvis_ContentSource();
		$contentSourceId = $contentSource->getContentSourceId();

		return array( $contentSourceId => true );
	}

	// Generic connector methods that can be overruled by a Version implementation:
	public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}
	
	/**
	 * Overrides createVersion in Version_EnterpriseConnector for backwards compatibility
	 * 
	 * Gives a connector control over the creation of new version of an object.
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
	 * Overrides deleteVersion in Version_EnterpriseConnector for backwards compatibility
	 *
	 * Gives a connector control over the delete of a version of an object.
	 * @param int $objectId Id of the object
	 * @param string $version (<major>.<minor>)
	 * @param string $storeName Reference to the filestore.
	 */
	public function deleteVersion( $objectId, $version, $storeName )
	{
	}
	
	// Generic connector methods that cannot be overruled by a content source implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!	
}