<?php
/**
 * Hooks into the Copy Objects workflow web service.
 * Called when an end-user copies a file (typically using SC or CS).
 *
 * @since      4.4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';

class Elvis_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	final public function runBefore( WflCopyObjectRequest &$req )
	{
	}

	final public function runAfter( WflCopyObjectRequest $req, WflCopyObjectResponse &$resp )
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading

		$copiedObject = new Object();
		$copiedObject->MetaData = $resp->MetaData;
		$copiedObject->Relations = $resp->Relations;
		$copiedObject->Targets = $resp->Targets;

		$respObjects = array( $copiedObject );
		// Get object shadow relations from the response objects
		$newShadowRelations = Elvis_BizClasses_ObjectRelation::getPlacedShadowRelationsFromParentObjects( $respObjects );

		// If array contains anything, it means the copied object has shadow relations and needs to send an update to Elvis
		if( !empty( $newShadowRelations ) ) {
			Elvis_BizClasses_AssetRelationsService::updateOrDeleteAssetRelations( $respObjects, $newShadowRelations );
		}
	}
	
	// Not called.
	final public function runOverruled( WflCopyObjectRequest $req )
	{
	} 
}