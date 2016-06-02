<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('admin');

// determine incoming mode
$usrId = isset($_REQUEST['usr']) ? intval($_REQUEST['usr']) : 0;
$grpId = isset($_REQUEST['grp']) ? intval($_REQUEST['grp']) : 0;

if ($usrId === 0) {
	$mode = 'adduser';
} else if ($grpId === 0) {
	$mode = 'addgrp';
} else {
	$mode = 'insert';
}

// this is set by insert
$orgmode = isset($_REQUEST['orgmode']) ? $_REQUEST['orgmode'] : '';
$orgid   = isset($_REQUEST['orgid'])   ? intval($_REQUEST['orgid']) : 0;

// insert: back to orgmode
BizSession::startSession( $ticket );
try {
	switch ($orgmode) {
		case 'adduser':
			$usrIds = isset($_REQUEST['usrs']) ? $_REQUEST['usrs'] : array();
			require_once BASEDIR . '/server/services/adm/AdmAddUsersToGroupService.class.php';
			require_once BASEDIR . '/server/interfaces/services/adm/AdmAddUsersToGroupRequest.class.php';
			$service = new AdmAddUsersToGroupService();
			$request = new AdmAddUsersToGroupRequest( $ticket, $usrIds, $grpId );
			$service->execute( $request );
			header("Location:hpgroups.php?id=$orgid");
			exit;
		case 'addgrp':
			$grpIds = isset($_REQUEST['grps']) ? $_REQUEST['grps'] : array();
			require_once BASEDIR . '/server/services/adm/AdmAddGroupsToUserService.class.php';
			require_once BASEDIR . '/server/interfaces/services/adm/AdmAddGroupsToUserRequest.class.php';
			$service = new AdmAddGroupsToUserService();
			$request = new AdmAddGroupsToUserRequest( $ticket, $grpIds, $usrId );
			$service->execute( $request );
			header("Location:hpusers.php?id=$orgid");
			exit;
	}
} catch( BizException $e ) {
	echo '<font color="red">'.$e->getMessage().'</font>';
}
BizSession::endSession();

// generate upper part (edit fields)
if ($mode == 'adduser') {
	$orgid = $grpId;
	require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
	$group = BizAdmUser::getUserGroupObjWithNonMemberUsers( $grpId ); // todo: add this service to WSDL
	$combo = '<select name="usrs[]" multiple="multiple" size="25">';
	foreach( $group->Users as $user ) {
		$combo .= '<option value="'.$user->Id.'">'.formvar($user->FullName).'</option>';
	}
	$combo .= "</select>";
	$combo .= inputvar( 'grp', $grpId, 'hidden' );
	$txt = HtmlDocument::loadTemplate( 'hpadduser.htm' );
	$txt = str_replace( '<!--NAME-->', formvar($group->Name), $txt );
} else { // addgrp
	$orgid = $usrId;
	require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
	$user = BizAdmUser::getUserObjWithNonMemberGroups( $usrId ); // todo: add this service to WSDL
	$combo = '<select name="grps[]" multiple="multiple" size="25">';
	foreach( $user->UserGroups as $group ) {
		$combo .= '<option value="'.$group->Id.'">'.formvar($group->Name).'</option>';
	}
	$combo .= '</select>';
	$combo .= inputvar( 'usr', $usrId, 'hidden' );
	$txt = HtmlDocument::loadTemplate( 'hpaddgroup.htm' );

	// Pre-translate the ACT_SUBSCRIBE_TO_GROUP key to fill in the user name
	$msg = BizResources::localize( 'ACT_SUBSCRIBE_TO_GROUP' );
	$msg = str_replace( '%', $user->FullName, $msg );
	$txt = str_replace( '<!--RES:ACT_SUBSCRIBE_TO_GROUP-->', formvar($msg), $txt );
	$txt = str_replace( '<!--NAME-->', formvar($user->FullName), $txt );
}
$combo .= inputvar( 'orgmode', $mode, 'hidden' );
$combo .= inputvar( 'orgid', $orgid, 'hidden' );
$txt = str_replace("<!--COMBO-->", $combo, $txt );
$txt = str_replace("<!--ID-->", $orgid, $txt);

// generate total page
print HtmlDocument::buildDocument($txt);
