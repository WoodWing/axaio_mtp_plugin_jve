<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('admin');

// database stuff
$dbh = DBDriverFactory::gen();
$dbu = $dbh->tablename('users');
$dbg = $dbh->tablename('groups');
$dbx = $dbh->tablename('usrgrp');

// determine incoming mode
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) { 
	$mode = 'delete';
} else if (isset($_REQUEST['deldet']) && $_REQUEST['deldet']) {
	$mode = 'deldet';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}

// get param's
$name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
$descr = isset($_REQUEST['descr']) ? $_REQUEST['descr'] : '';
$admin = isset($_REQUEST['admin']) && trim($_REQUEST['admin']) ? 'on' : '';
$routing = isset($_REQUEST['routing']) && trim($_REQUEST['routing']) ? 'on' : '';
$del = isset($_REQUEST['del']) ? intval($_REQUEST['del']) : 0;

$errors = array();

BizSession::startSession( $ticket );

try {
	// handle request
	switch ($mode) {
		case 'update':
			require_once BASEDIR . '/server/services/adm/AdmModifyUserGroupsService.class.php';
			$groupObjs = array( new AdmUserGroup( $id, $name, $descr, $admin == 'on', $routing == 'on', null) );
			$service = new AdmModifyUserGroupsService();
			$request = new AdmModifyUserGroupsRequest($ticket, array(), $groupObjs);
			$response = $service->execute($request);
			break;
		case 'insert':
			require_once BASEDIR . '/server/services/adm/AdmCreateUserGroupsService.class.php';
			$groupObjs = array( new AdmUserGroup( null, $name, $descr, $admin == 'on', $routing == 'on', null) );
			$service = new AdmCreateUserGroupsService();
			$request = new AdmCreateUserGroupsRequest($ticket, array(), $groupObjs);
			$response = $service->execute($request);
			$id = $response->UserGroups[0]->Id;
			break;
		case 'delete':
			BizAdmUser::deleteUserGroup( $id ); // TODO - Should call DeleteUserGroupsService, in the future
			break;
		case 'deldet':
			$sql = "delete from $dbx where `id` = $del";
			$sth = $dbh->query($sql);
			break;
	
	}
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

BizSession::endSession();

// delete: back to overview
if ($mode == 'delete') {
	header("Location:groups.php");
	exit();
}
// generate upper part (edit fields)
if ($mode == 'error') {
	$row = array ('name' => $name, 'descr' => $descr, 'admin' => $admin, 'routing' => $routing);
} elseif ($mode != "new") {
	$sql = "select * from $dbg where `id` = $id";
	$sth = $dbh->query($sql);
	$row = $dbh->fetch($sth);
} else {
	$row = array ('name' => '', 'descr' => '', 'admin' => '', 'routing' => 'on');
}
$txt = HtmlDocument::loadTemplate( 'hpgroups.htm' );

// error handling
$err = '';
foreach ($errors as $error) {
	$err .= "$error<br>";
}
$txt = str_replace("<!--ERROR-->", $err, $txt);

// fields
$txt = str_replace('<!--VAR:NAME-->', '<input maxlength="100" name="name" value="'.formvar($row['name']).'"/>', $txt );
$txt = str_replace('<!--VAR:DESCR-->', '<input name="descr" value="'.formvar($row['descr']).'"/>', $txt );
$txt = str_replace('<!--VAR:ADMIN-->', '<input type="checkbox" name="admin" '.(trim($row['admin'])?'checked="checked"':'').'/>', $txt );
$txt = str_replace('<!--VAR:ROUTING-->', '<input type="checkbox" name="routing" '.(trim($row['routing'])?'checked="checked"':'').'/>', $txt );
$txt = str_replace('<!--VAR:HIDDEN-->', inputvar( 'id', $id, 'hidden' ), $txt );

// generate lower part (details)
$detailtxt = '';
if ($mode != "new" && $mode != "error") {
	$detail = '';
	if ($id > 0) {
		$fuser = $dbh->quoteIdentifier("user");
		$sql = "SELECT x.`id` as `ix`, u.`id`, u.`user`, u.`fullname` ".
				"FROM $dbu u, $dbx x ".
				"WHERE x.`grpid` = $id and x.`usrid` = u.`id` ".
				"ORDER BY u.`user`";
		$sth = $dbh->query($sql);
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		while( ($row = $dbh->fetch($sth)) ) {
			$clr = $color[$flip];
			$detail .= '<tr'.$clr.'><td><a href="hpusers.php?id='.$row['id'].'">'.formvar($row['user']).'</a></td>'.
						'<td>'.formvar($row['fullname']).'</td>'.
						'<td><a href="hpgroups.php?deldet=1&id='.$id.'&del='.$row['ix'].'" onclick="return myconfirm(\'deluser\')">'.BizResources::localize('BUT_DELETE').'</a></td><tr>';
			$flip = 1- $flip;
		}
	}
	$detailtxt = str_replace("<!--ROWS-->", $detail, HtmlDocument::loadTemplate( 'hpgroupsdet.htm' ) );
	$detailtxt = str_replace("<!--ID-->", $id, $detailtxt);
}

// generate total page
$txt = str_replace("<!--DETAILS-->", $detailtxt, $txt);

$txt .= '<script language="javascript">document.forms[0].name.focus();</script>';

print HtmlDocument::buildDocument($txt);
?>
