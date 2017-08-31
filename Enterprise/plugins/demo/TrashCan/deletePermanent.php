<?php
/**
 * A temporary solution for CS to do delete / purge all operation.
 * This script is triggered from CS by adding extra two menus in the Context Menu.
 * 1. Delete Permanent.
 * 2. Delete All.
 */
if( file_exists('../../../config/config.php') ) {
	require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}
require_once BASEDIR.'/server/secure.php';


$ticket = $_REQUEST['ticket'];
// init authorization
global $globAuth;
if (! isset( $globAuth )) {
	require_once BASEDIR . '/server/authorizationmodule.php';
	$globAuth = new authorizationmodule();
}
$ids = intval($_REQUEST['all']) ? null : explode(',',$_REQUEST['ids']);
try{
	require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';
	require_once BASEDIR . '/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
	$areas = array('Workflow');
	$service = new WflDeleteObjectsService();
	$request = new WflDeleteObjectsRequest();
	$request->Ticket = $ticket;
	$request->IDs = $ids;
	$request->Permanent = true;
	$request->Areas = $areas;
	
	$queryResp = $service->execute($request);	
		
	if( $queryResp->Reports ){
		$errMsg = '';
		foreach( $queryResp->Reports as $report ){
			$objId = $report->BelongsTo->ID;
			foreach( $report->Entries as $reportEntry ) {
				$errMsg .= $reportEntry->Message . PHP_EOL;
			}
			LogHandler::Log('TrashCan Connector','ERROR','Delete Permanent Error:' . $errMsg . PHP_EOL .
				'ObjID:' . $objId );
		}
		echo "Error occured while deleting permanently";
	}

	if( $queryResp->IDs ){
		echo "Successfully deleted permanently";
	}

	
}catch( BizException $e){
	echo $e->getMessage() . PHP_EOL .
		$e->getDetail();
}
	
					
