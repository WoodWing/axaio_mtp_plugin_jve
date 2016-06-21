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

class AdobeDps_Version extends Version_EnterpriseConnector
{
	// Description: see parant class.
	final public function createVersion( $objectId, $sourceVersion, $nextVersion, $storename, $setObjPropMode )
	{
		if( $setObjPropMode ) { // If setObjPropMode is true[CreatePermanentVersion is enable], create new version
			require_once BASEDIR.'/server/bizclasses/BizStorage.php';
			require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
			$type = 'output';
			$editionIds = DBObjectRenditions::getEditionIds( $objectId, $type, $sourceVersion );
			if ( $editionIds ) foreach( $editionIds as $editionId ) {
				$attachobj = StorageFactory::gen( $storename, $objectId, $type, null, $sourceVersion, null, $editionId );
				$dummy = null;
				if( $attachobj->copyFile( $nextVersion, null, null, null, null, $dummy, null ) ) {
					require_once BASEDIR . '/server/dbclasses/DBObjectRenditions.class.php';
					$format = DBObjectRenditions::getEditionRenditionFormat( $objectId, $editionId, $type ); // Retrieve rendition format
					DBObjectRenditions::saveEditionRendition( $objectId, $editionId, $type, $format, $nextVersion ); // Save the edition rendition
				}
			}
		}	
	}

	// Description: see parant class.
	final public function deleteVersion( $objectId, $version, $storename )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectRenditions.class.php';
		$type = 'output';
		$editionIds = DBObjectRenditions::getEditionIds( $objectId, $type, $version );
		if ( $editionIds ) foreach( $editionIds as $editionId ) { 
			// Dps output files are always linked to an edition (device).
			$attachobj = StorageFactory::gen( $storename, $objectId, $type, null, $version, null, $editionId );
			$attachobj->deleteFile();
			// No errors or warnings are logged. We don't know if a layout is also stored as output rendition.
			// This depends on if the layout is targeted to a DPS channel. Just try to delete and continue.
		}	
	}

	// Generic connector methods that can be overruled by a Version implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }
	
	// Generic connector methods that cannot be overruled by a content source implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!	
}