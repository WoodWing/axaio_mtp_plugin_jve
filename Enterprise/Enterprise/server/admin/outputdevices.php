<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizAdmOutputDevice.class.php';
require_once BASEDIR.'/server/dataclasses/OutputDevice.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Start the session to save the ticket in BizSession
$ticket = checkSecure('admin'); // system admin
BizSession::startSession( $ticket );

$bizDevice = new BizAdmOutputDevice();

// Handle re-order of devices
$recs = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;
if( $recs > 0 ) {
	$deviceCodes = array();
	for( $i = 1; $i <= $recs; $i++ ) {
		$devideId = intval($_REQUEST['device_id_'.$i]);
		$deviceSort = intval($_REQUEST['device_sort_'.$i]);
		$deviceCodes[$devideId] = $deviceSort;
	}
	$bizDevice->reorderDevices( $deviceCodes );
}

$delId = isset($_REQUEST['del']) ? intval($_REQUEST['del']) : 0;
if( $delId > 0 ) {
	$bizDevice->deleteDevices( array( $delId ) );
}

$txt = '';

// Show devices
$devices = $bizDevice->getDevices();
if( $devices ) {
	$cnt = 1;
	$colors = array (" bgcolor='#eeeeee'", '');
	foreach( $devices as $device ) {
		$clr = $colors[$cnt%2];
		$box = inputvar( 'device_id_'.$cnt, intval( $device->Id ), 'hidden' );
		$box .= inputvar( 'device_sort_'.$cnt, intval( $device->SortOrder ), 'small' );
		$txt .= '<tr'.$clr.'><td><a href="outputdevice.php?id='.intval( $device->Id ).'">'.formvar( $device->Name ).'</a></td>'.
				'<td>'.formvar( $device->Description ).'</td><td>'.$box.'</td>'.
				'<td><a href="outputdevices.php?del='.intval( $device->Id ).'" onClick="return confirmDelete()">'.
					'<img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize("ACT_DELETE").'"/></a>'.
				'</td><tr>'."\r\n";
		$cnt++;
	}
}
$txt .= inputvar( 'recs', count($devices), 'hidden' );

$txt = str_replace('<!--ROWS-->', $txt, HtmlDocument::loadTemplate( 'outputdevices.htm' ) );
print HtmlDocument::buildDocument($txt);
