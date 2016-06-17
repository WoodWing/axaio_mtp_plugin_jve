<?php
require_once dirname(__FILE__).'/../../config/config.php';

if(is_numeric($_REQUEST['id'])){
	$id = $_REQUEST['id'];
	$ticket = $_REQUEST['ticket'];
	require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
	try {
		$unlockReq = new WflUnlockObjectsRequest( $ticket, array($id), null );
		$unlockService = new WflUnlockObjectsService();
		$unlockService->execute( $unlockReq );
	} catch (BizException $e ) {
	}
}
?>
<script language="javascript">
	window.close();
</script>
