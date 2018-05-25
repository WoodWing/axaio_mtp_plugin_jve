<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the workflow copy operation. It stop the core from copying the custom property 
 * "Upload status" of a layout that was saved into an AP channel (which does not make sense).
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';

class AdobeDps2_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFORE; }

	final public function runBefore( WflCopyObjectRequest &$req )
	{
		// Add an empty custom property "Upload status" to stop the core making a copy in the DB.
		// When this property is configured for a workflow dialog, it arrives here.
		// In that case we don't add, but simply clear the value.
		
		$found = false;
		if( !isset($req->MetaData->ExtraMetaData) ) {
			$req->MetaData->ExtraMetaData = array();
		}
		
		foreach( $req->MetaData->ExtraMetaData as $extraMetaData ) {
			if( $extraMetaData->Property == 'C_DPS2_UPLOADSTATUS' ) {
				$extraMetaData->Values = array('');
				$found = true;
				break;
			}
		}
		if( !$found ) {
			$extraMetaData = new ExtraMetaData();
			$extraMetaData->Property = 'C_DPS2_UPLOADSTATUS';
			$extraMetaData->Values = array('');
			$req->MetaData->ExtraMetaData[] = $extraMetaData;
		}
	}

	// Not called.
	final public function runAfter( WflCopyObjectRequest $req, WflCopyObjectResponse &$resp )
	{
	}
	
	// Not called.
	final public function runOverruled( WflCopyObjectRequest $req )
	{
	}
}
