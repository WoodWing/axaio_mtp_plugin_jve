<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';

class DataSourceWorkflowService_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflCopyObjectRequest &$req ) {}
	
	final public function runAfter( WflCopyObjectRequest $req, WflCopyObjectResponse &$resp ) 
	{
		require_once BASEDIR.'/server/dbclasses/DBDatasource.class.php';
		
		// get the id of the OLD object (from the request)
		$sid = $req->SourceID;
		// get the placement of the OLD object
		$placement = DBDatasource::getQueryPlacement( $sid );
		
		if( is_array($placement) )
		{
			// we have a placement id, proceed to check if there is a valid response
			if( property_exists($resp->MetaData->BasicMetaData,"ID") )
			{
				// get the id of the NEW object
				$tid = $resp->MetaData->BasicMetaData->ID;
				// copy the placement to the NEW object
				$npid = DBDatasource::newQueryPlacement( $tid, $placement["datasourceid"] );
				
				// get the placed families of the OLD object
				$families = DBDatasource::getFamilyValues( $sid );
				foreach( $families as $family )
				{
					// copy the placed family to the NEW object
					DBDatasource::newFamilyValue( $npid, $family["familyfield"], $family["familyvalue"] );
				}
				
				// get the update relations of the OLD object
				$updaterelations = DBDatasource::getUpdateRelation('',$sid);
				foreach( $updaterelations as $updaterelation )
				{
					// copy the update relation to the NEW object
					DBDatasource::storeUpdateObjectRelation( $tid, $updaterelation["updateid"] );
				}
				
				LogHandler::Log( 'Datasource', 'INFO', 'Copied all relations of document '.$sid.' to document '.$tid );	
			}
		
		}
	}
	
	final public function runOverruled( WflCopyObjectRequest $req ) {}
}
