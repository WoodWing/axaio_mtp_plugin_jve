<?php
/**
 * A temporary solution for CS to do restoration operation.
 * This script is triggered from CS by adding extra menu in the Context Menu.
 * 1. Restore.
 */
if( file_exists('../../../config/config.php') ) {
	require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../Enterprise/config/config.php';
}
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

$ticket = $_REQUEST['ticket'];

// init authorization
global $globAuth;
if (! isset( $globAuth )) {
	require_once BASEDIR . '/server/authorizationmodule.php';
	$globAuth = new authorizationmodule( );
}
$ids = explode(',',$_REQUEST['ids']);
try{
	require_once BASEDIR . '/server/services/wfl/WflRestoreObjectsService.class.php';
	require_once BASEDIR . '/server/interfaces/services/wfl/WflRestoreObjectsRequest.class.php';
	$service = new WflRestoreObjectsService();

	$request = new WflRestoreObjectsRequest( $ticket, $ids);
	$queryResp = $service->execute($request);
	if( $queryResp->Reports ) {
		$errMsg = '';
		foreach( $queryResp->Reports as $report ){
			$objId = $report->BelongsTo->ID;
			foreach( $report->Entries as $reportEntry ) {
				$errMsg .= $reportEntry->Message . PHP_EOL;
			}
			LogHandler::Log('TrashCan Connector','ERROR', $errMsg . 'ObjID:' . $objId );
		}
		echo "Error occured while restoring";
	}
	
	if( $queryResp->IDs ){
		echo "Successfully Restored";
	}

}catch( BizException $e){
	echo $e->getMessage() . PHP_EOL .
			$e->getDetail();
}

	
					
