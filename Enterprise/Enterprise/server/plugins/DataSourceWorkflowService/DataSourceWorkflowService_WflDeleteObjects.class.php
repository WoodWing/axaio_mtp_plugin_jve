<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjects_EnterpriseConnector.class.php';

class DataSourceWorkflowService_WflDeleteObjects extends WflDeleteObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflDeleteObjectsRequest &$req ) // not called
	{
		$req = $req; // keep analyzer happy
	}
	
	final public function runAfter( WflDeleteObjectsRequest $req, WflDeleteObjectsResponse &$resp ) 
	{
		$resp = $resp; // keep analyzer happy

		require_once BASEDIR.'/server/dbclasses/DBDatasource.class.php';
		
		// Delete DataSource relations to Objects
		foreach( $req->IDs as $id ) {
			// get the placement id
			$placement = DBDatasource::getQueryPlacement( $id );
			if( is_array($placement) ) {
				// remove placement (query placement and family placements)
				DBDatasource::deleteQueryPlacement( $id );
				DBDatasource::deleteFamilyValues( $placement["id"] );
				
				// get all object / update relations
				$relations = DBDatasource::getUpdateRelation('',$id);
				foreach( $relations as $relation ) {
					// remove the relation
					$updateid = $relation["updateid"];
					DBDatasource::deleteUpdateObjectRelation( $id, $updateid );
					
					// if, by removing the relation (above), there is no more relation
					// to the update, remove the update
					$updaterelation = DBDatasource::getUpdateRelation( $updateid );
					if( !is_array($updaterelation) ) {
						$updaterelation = array();
					}
					
					if( count($updaterelation) < 1 ) {
						DBDatasource::deleteUpdate( $updateid );
					}
				}
				LogHandler::Log( 'Datasource', 'INFO', 'Deleted all relations to document: '.$id );	
			}
		}
	}
	
	final public function runOverruled( WflDeleteObjectsRequest $req ) // not called
	{
		$req = $req; // keep analyzer happy
	}
}
