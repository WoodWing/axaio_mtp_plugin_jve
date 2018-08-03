<?php
/**
 * Implement the object version control for Elvis assets.
 *
 * @since 		8.3
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/Version_EnterpriseConnector.class.php';

class Elvis_Version extends Version_EnterpriseConnector
{
	/**
	 * @inheritdoc.
	 */
	public function useContentSourceVersion()
	{
		require_once __DIR__.'/Elvis_ContentSource.class.php';
		$contentSource = new Elvis_ContentSource();
		return array( $contentSource->getContentSourceId() => true );
	}

	// Generic connector methods that can be overruled by a Version implementation:
	public function getPrio()
	{
		return self::PRIO_DEFAULT;
	}
	
	/**
	 * @inheritdoc
	 */
	public function createVersion( $objectId, $sourceVersion, $nextVersion, $storename, $setObjPropMode )
	{
	}

	/**
	 * @inheritdoc
	 */
	public function deleteVersion( $objectId, $version, $storeName )
	{
	}
	
	// Generic connector methods that cannot be overruled by a content source implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!	
}