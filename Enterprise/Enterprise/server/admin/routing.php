<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR."/server/apps/functions.php";
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('publadmin');

// database stuff
$dbh = DBDriverFactory::gen();
$dbr = $dbh->tablename('routing');
$dbp = $dbh->tablename('publications');
$dbs = $dbh->tablename('publsections');
$dbi = $dbh->tablename('issues');
$dbst = $dbh->tablename('states');
$dbg = $dbh->tablename('groups');
$dbu = $dbh->tablename('users');
$dba = $dbh->tablename('authorizations');
$dbx = $dbh->tablename('usrgrp');

// determine incoming mode
$publ  = isset($_REQUEST['publ'])  ? intval($_REQUEST['publ'])  : 0;
$issue = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; 
$selsection = isset($_REQUEST['selsection']) ? intval($_REQUEST['selsection']) : 0;
$records    = isset($_REQUEST['recs'])       ? intval($_REQUEST['recs']) : 0;
$insert     = isset($_REQUEST['insert'])     ? (bool)$_REQUEST['insert'] : false;

if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = 'update';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['add']) && $_REQUEST['add']) {
	$mode = 'add';
} else {
	$mode = 'view';
}

// check publication rights
checkPublAdmin($publ);

// handle request
if ($records > 0) {
	for ($i=0; $i < $records; $i++) {
		$id = isset($_REQUEST["id$i"]) ? intval($_REQUEST["id$i"]) : 0;
		$section = isset($_REQUEST["section$i"]) ? intval($_REQUEST["section$i"]) : 0;
		$state   = isset($_REQUEST["state$i"])   ? intval($_REQUEST["state$i"])   : 0;
		$routeto = isset($_REQUEST["routeto$i"]) ? $_REQUEST["routeto$i"] : '';
		$sql = "UPDATE $dbr set `publication`=$publ, `issue`=$issue, `section`=$section, ".
				"`state`=$state, `routeto`='".$dbh->toDBString($routeto)."' WHERE `id` = $id";
		$sth = $dbh->query($sql);
	}
}
if ($insert === true) {
	$section = isset($_REQUEST['section']) ? intval($_REQUEST['section']) : 0;
	$state   = isset($_REQUEST['state'])   ? intval($_REQUEST['state'])   : 0;
	$routeto = isset($_REQUEST['routeto']) ? $_REQUEST['routeto'] : '';
	if ($routeto) {
		$sql = "INSERT INTO $dbr (`publication`, `issue`, `section`, `state`, `routeto`) ".
				"VALUES ($publ, $issue, $section, $state, '".$dbh->toDBString($routeto)."')";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql);
		$id = $dbh->newid($dbr, true);
	}
}
if ($mode == 'delete'){
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id > 0) {
		$sql = "delete from $dbr where `id` = $id";
		$sth = $dbh->query($sql);
	}
}

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'routing.htm' );
$sql = "select `publication` from $dbp where `id` = $publ";
$sth = $dbh->query($sql);
$row = $dbh->fetch($sth);

$txt = str_replace('<!--VAR:PUBL-->', formvar($row['publication']).inputvar('publ',$publ,'hidden'), $txt);

$overrulePub = false;
if ($issue > 0) {
	$sql = "select `name`, `overrulepub` from $dbi where `id` = $issue";
	$sth = $dbh->query($sql);
	$rowi = $dbh->fetch($sth);

	$overrulePub = ($rowi['overrulepub'] == 'on');
	$txt = str_replace('<!--VAR:ISSUE-->', formvar($rowi['name']).inputvar('issue',$issue,'hidden'), $txt);
} else {
	$txt = preg_replace('/<!--IF:STATE-->.*<!--ENDIF-->/is', '', $txt);
}
$whereIssue = $overrulePub ? $issue : 0;
$sql = "select `id`, `section` from $dbs where `publication` = $publ and `issue` = $whereIssue order by `code`";
$sth = $dbh->query($sql);
$sectiondomain = array();
$sAll = BizResources::localize('LIS_ALL');
$seltxt = '<select name="selsection" onChange="this.form.submit()">';
$seltxt .= '<option value="0">&lt;'.$sAll.'&gt;</option>';
while (($row = $dbh->fetch($sth))) {
	$sectiondomain[$row['id']] = $row['section'];
	$selected = ($selsection == $row['id']) ? 'selected="selected"' : '';
	$seltxt .= '<option value="'.$row['id'].'" '.$selected.'>'.formvar($row['section']).'</option>';
}
$seltxt .= '</select>';

$txt = str_replace('<!--VAR:SELSECTION-->', $seltxt, $txt);

// generate lower part
$detailtxt = '';

$sql = "SELECT `id`, `state`, `type` from $dbst ".
		"WHERE `publication` = $publ and `issue` = $whereIssue ".
		"ORDER BY `type`, `code`";
$sth = $dbh->query($sql);
$statedomain = array();
while (($row = $dbh->fetch($sth))) {
	$statedomain[$row['id']] = $row['type'].'/'.$row['state'];
}
$routedomain = array();
$arrayOfRoute = listrouteto( $ticket, $publ, $overrulePub ? $issue : null );
if ($arrayOfRoute) foreach ($arrayOfRoute as $route) {
	$routedomain[$route] = $route;
}
$sAll = BizResources::localize('LIS_ALL');
switch ($mode) {
	case 'view':
	case 'update':
	case 'delete':
		$sql = "SELECT r.`id`, r.`section`, r.`state`, r.`routeto` from $dbr r ".
				"LEFT JOIN $dbst st on (r.`state` = st.`id`) ".
				"WHERE r.`publication` = $publ and r.`issue` = $whereIssue";
		if ($selsection > 0) $sql .= " and r.`section` = $selsection";
		$sql .= " ORDER BY r.`section`, st.`type`, st.`code`";
		
		$sth = $dbh->query($sql);
		$i = 0;
		$color = array (' bgcolor="#eeeeee"', '');
		$flip = 0;
		while (($row = $dbh->fetch($sth))) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$deltxt = '<a href="routing.php?publ='.$publ.'&issue='.$issue.'&delete=1&id='.$row['id'].'" onclick="return myconfirm(\'delroute\')">'.BizResources::localize('ACT_DEL').'</a>';
			$detailtxt .= "<tr$clr>";
			if ($selsection > 0) {
				$detailtxt .= '<td>'.formvar($sectiondomain[$selsection]).inputvar("section$i",$selsection,'hidden').'</td>';
			} else {
				$detailtxt .= '<td>'.inputvar("section$i", $row['section'], 'combo', $sectiondomain, $sAll).'</td>';
			}
			$detailtxt .= '<td>'.inputvar("state$i", $row['state'], 'combo', $statedomain, $sAll).'</td>';
			$detailtxt .= "<td>".inputvar("routeto$i", $row['routeto'], 'combo', $routedomain).'</td>';
			$detailtxt .= '<td>'.$deltxt.'</td></tr>';
			$detailtxt .= inputvar( "id$i", $row['id'], 'hidden' );
			$i++;
		}
		$detailtxt .= inputvar( 'recs', $i, 'hidden' );
		break;
	case 'add':
		// 1 row to enter new record
		$detailtxt .= '<tr>';
		if ($selsection > 0) {
			$detailtxt .= '<td>'.formvar($sectiondomain[$selsection]).inputvar('section',$selsection,'hidden').'</td>';
		} else {
			$detailtxt .= '<td>'.inputvar('section', '', 'combo', $sectiondomain, $sAll).'</td>';
		}
		$detailtxt .= '<td>'.inputvar('state','', 'combo', $statedomain, $sAll).'</td>';
		$detailtxt .= '<td>'.inputvar('routeto', '', 'combo', $routedomain).'</td>';
		$detailtxt .= '<td></td></tr>';
		$detailtxt .= inputvar( 'insert', '1', 'hidden' );

		// show other authorizations as info
		$sql = "SELECT r.`id`, r.`section`, r.`state`, r.`routeto` from $dbr r ".
				"LEFT JOIN $dbst st on (r.`state` = st.`id`) ".
				"WHERE r.`publication` = $publ and r.`issue` = $issue ";
		if ($selsection > 0) $sql .= "and r.`section` = $selsection ";
		$sql .= "ORDER BY r.`section`, st.`type`, st.`code` ";
		$sth = $dbh->query($sql);
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		while (($row = $dbh->fetch($sth))) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$sectionDetails = $row['section'] ? $sectiondomain[$row['section']] : '<'.$sAll.'>';
			$statusDetails = $row['state'] ? $statedomain[$row['state']] : '<'.$sAll.'>';
			$detailtxt .= "<tr$clr><td>".formvar($sectionDetails).'</td>';
			$detailtxt .= '<td>'.formvar($statusDetails).'</td>';
			$detailtxt .= '<td>'.formvar($row['routeto']).'</td>';
			$detailtxt .= '<td></td></tr>';
		}
		break;
}

// generate total page
$txt = str_replace("<!--ROWS-->", $detailtxt, $txt);
if ($issue > 0) {
	$back = "hppublissues.php?id=$issue";
} else {
	$back = "hppublications.php?id=$publ";
}
$txt = str_replace("<!--BACK-->", $back, $txt);
print HtmlDocument::buildDocument($txt);
?>