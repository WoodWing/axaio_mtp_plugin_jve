<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('publadmin');

// database stuff
$dbh = DBDriverFactory::gen();
$dba = $dbh->tablename("authorizations");
$dbp = $dbh->tablename("publications");
$dbi = $dbh->tablename("issues");
$dbg = $dbh->tablename("groups");
$dbs = $dbh->tablename("publsections");
$dbst = $dbh->tablename("states");

// determine incoming mode
$publ    = isset($_REQUEST['publ'])   ? intval($_REQUEST['publ'])  : 0; // Publication id. Zero not allowed.
$issue   = isset($_REQUEST['issue'])  ? intval($_REQUEST['issue']) : 0; // Issue id. Zero for all.
$grp     = isset($_REQUEST['grp'])    ? intval($_REQUEST['grp'])   : 0; // User group id. Zero for all, in special mode in which user should pick one first.
$records = isset($_REQUEST['recs'])   ? intval($_REQUEST['recs'])  : 0; // Number of records posted.
$insert  = isset($_REQUEST['insert']) ? (bool)$_REQUEST['insert']  : false; // Whether or not in insertion mode.

// check publication rights
checkPublAdmin($publ);


$profiles = profiles($dbh);

//////////////////////////////////////////
// print report
//////////////////////////////////////////
if (isset($_REQUEST['report']) && $_REQUEST['report']) {

	$txt = '<html><head><title>WoodWing InDesign and InCopy Solutions</title>';
	$txt .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	$txt .= '<link rel="stylesheet" href="../../config/templates/woodwingmain.css" type="text/css" />';
	$txt .= '<link rel="icon" href="../../config/images/favicon.ico" type="image/x-icon" />';
	$txt .= '<link rel="shortcut icon" href="../../config/images/favicon.ico" type="image/x-icon" />';	
	$txt .= '</head>';
	$txt .= '<body style="background-color: #FFFFFF">';

	// header
	$sql = "select * from $dbp where `id` = $publ";
	$sth = $dbh->query($sql);
	$row = $dbh->fetch($sth);
	$tmp = formvar($row['publication']);

	if ($issue > 0) {
		$sql = "select * from $dbi where `id` = $issue";
		$sth = $dbh->query($sql);
		$rowi = $dbh->fetch($sth);
		$tmp .= ' / '.formvar($rowi['name']);
	}
	$txt .= '<h1><img src="../../config/images/woodwing95.gif"/> <img src="../../config/images/lock.gif"/> '
			.BizResources::localize('OBJ_AUTHORIZATIONS_FOR').' '.$tmp.'</h1>';
	$txt .= '<table class="text" width="700">';

	$sql = "SELECT g.`name` as `grp`, s.`section` as `section`, a.`state` as `state`, st.`type` as `type`, ".
				"st.`state` as `statename`, a.`profile` as `profile` ".
			"FROM $dbg g, $dba a ".
			"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
			"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
			"WHERE a.`grpid` = g.`id` and a.`publication` = $publ and a.`issue` = $issue ".
			"ORDER BY g.`name`, s.`section`, st.`type`, st.`state`";
	$sth = $dbh->query($sql);
	$color = array (" bgcolor='#eeeeee'", '');
	$flip = 0;
	$grpName = '';
	while (($row = $dbh->fetch($sth))) {
		// break group
		if ($row['grp'] != $grpName) {
			$grpName = $row['grp'];
			$txt .= '<tr><td>&nbsp;</td></tr>';
			$txt .= '<tr><td><h3><img src="../../config/images/groups_small.gif"> '
					.BizResources::localize('GRP_GROUP').': '.formvar($grpName).'</h3></td></tr>';
			$txt .= '<tr>'
				.'<th>'.BizResources::localize('SECTION').'</th>'
				.'<th>'.BizResources::localize('STATE').'</th>'
				.'<th>'.BizResources::localize('ACT_PROFILE').'</th>'
				.'</tr>';
			$flip = 0;
		}

		$clr = $color[$flip];
		$flip = 1- $flip;
		if( $row['section'] ) {
			$txt .= "<tr$clr><td>".formvar($row['section']).'</td>';
		} else {
			$txt .= "<tr$clr><td>&lt;".BizResources::localize('LIS_ALL').'&gt;</td>';
		}
		if( $row['state'] ) {
			$txt .= '<td>'.formvar($row['type']).'/'.formvar($row['statename']).'</td>';
		} else {
			$txt .= '<td>&lt;'.BizResources::localize('LIS_ALL').'&gt;</td>';
		}
		$txt .= '<td>'.formvar($profiles[$row['profile']]).'</td>';
		$txt .= '</tr>';
	}
	$txt .= '</table><br/><br/>';
	$txt .= '<form><input type="button" value="'.BizResources::localize('ACT_PRINT_THIS_PAGE').'" onclick="window.print()"></form>';
  	$txt .= '<a href="javascript:history.back(-1)"><img src="../../config/images/back_32.gif" border="0"></a>';
	$txt .= '</body></html>';
	print HtmlDocument::buildDocument($txt, false);
	exit;
}

//////////////////////////////////////////
// normal operations
//////////////////////////////////////////
if (!$grp) {
	$mode = 'select';
} else if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = 'update';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['add']) && $_REQUEST['add']) {
	$mode = 'add';
} else {
	$mode = 'view';
}

// handle request
if ($records > 0) {
	for ($i=0; $i < $records; $i++) {
		$id      = isset($_REQUEST["id$i"])      ? intval($_REQUEST["id$i"])      : 0; // Record id
		$section = isset($_REQUEST["section$i"]) ? intval($_REQUEST["section$i"]) : 0; // Category id
		$state   = isset($_REQUEST["state$i"])   ? intval($_REQUEST["state$i"])   : 0; // Status id
		$profile = isset($_REQUEST["profile$i"]) ? intval($_REQUEST["profile$i"]) : 0; // Profile id
		//echo 'DEBUG: id=['. $id .'] section=['. $section .'] state=['. $state .'] profile=['. $profile .']</br>';
		if ($profile > 0) {
			$sql = "UPDATE $dba SET `publication`=$publ, `issue` = $issue, `grpid`=$grp, ".
						"`section`=$section, `state`=$state, `profile`=$profile ".
					"WHERE `id` = $id";
			$sth = $dbh->query($sql);
		}
	}
}
if ($insert === true) {
	$section = isset($_REQUEST['section']) ? intval($_REQUEST['section']) : 0; // Category id
	$state   = isset($_REQUEST['state'])   ? intval($_REQUEST['state'])   : 0; // Status id
	$profile = isset($_REQUEST['profile']) ? intval($_REQUEST['profile']) : 0; // Profile id
	//echo 'DEBUG: section=['. $section .'] state=['. $state .'] profile=['. $profile .']</br>';
	if ($profile > 0) {
		// handle autoincrement for non-mysql
		$sql = "INSERT INTO $dba (`publication`, `issue`, `grpid`, `section`, `state`, `profile`) ".
				"VALUES ($publ, $issue, $grp, $section, $state, $profile)";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql);
		$id = $dbh->newid($dba, true);
	}
}
if ($mode == 'delete'){
	$id = intval($_REQUEST['id']);
	if ($id > 0) {
		$sql = "DELETE FROM $dba WHERE `id` = $id";
		$sth = $dbh->query($sql);
	}
}

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'authorizations.htm' );

if ($mode == 'select') {
	$sql = "select `id`, `name` from $dbg order by `name`";
	$sth = $dbh->query($sql);
	$grptxt = '<select name="grp" onChange="this.form.submit()">';
	while (($row = $dbh->fetch($sth))) {
		$grptxt .= '<option value="'.$row['id'].'">'.formvar($row['name']).'</option>';
	}
	$grptxt .= '</select>';
	$grptxt .= inputvar( 'add', '1', 'hidden' );
} else {
	$sql = "select `id`, `name` from $dbg where `id` = $grp";
	$sth = $dbh->query($sql);
	$row = $dbh->fetch($sth);
	$grptxt = formvar($row['name']).inputvar( 'grp', $grp, 'hidden' );
}

$sql = "select `publication` from $dbp where `id` = $publ";
$sth = $dbh->query($sql);
$row = $dbh->fetch($sth);

$txt = str_replace('<!--VAR:PUBL-->', formvar($row['publication']).inputvar( 'publ', $publ, 'hidden' ), $txt);
$txt = str_replace('<!--VAR:GROUP-->', $grptxt, $txt);

if ($issue > 0) {
	$sql = "select `name` from $dbi where `id` = $issue";
	$sth = $dbh->query($sql);
	$rowi = $dbh->fetch($sth);

	$txt = str_replace('<!--VAR:ISSUE-->', formvar($rowi['name']).inputvar( 'issue', $issue, 'hidden' ), $txt);
} else {
	$txt = preg_replace('/<!--IF:STATE-->.*<!--ENDIF-->/is', '', $txt);
}

// generate lower part
$detailtxt = '';
$sql = "select `id`, `section` from $dbs where `publication` = $publ and `issue` = $issue order by `section`";
$sth = $dbh->query($sql);
$sectiondomain = array();
$sAll = BizResources::localize("LIS_ALL");
while (($row = $dbh->fetch($sth))) {
	$sectiondomain[$row['id']] = $row['section'];
}
$sql = "SELECT `id`, `state`, `type`, `code` FROM $dbst ".
		"WHERE `publication` = $publ and `issue` = $issue ".
		"ORDER BY `type`, `code`, `id`";
$sth = $dbh->query($sql);
$statedomain = array();
while (($row = $dbh->fetch($sth))) {
	$statedomain[$row['id']] = $row['type']."/".$row['state'];
}

switch ($mode) {
	case 'view':
	case 'update':
	case 'delete':
		// Changed order by state to order by code
		$sql = "SELECT a.`id`, a.`section`, a.`state`, a.`profile` ".
				"FROM $dba a ".
				"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
				"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
				"WHERE a.`publication` = $publ and a.`issue` = $issue and a.`grpid` = $grp ".
				"ORDER BY s.`section`, st.`type`, st.`code`";
		$sth = $dbh->query($sql);
		$i = 0;
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		while (($row = $dbh->fetch($sth))) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$deltxt = "<a href='authorizations.php?publ=$publ&issue=$issue&grp=$grp&delete=1&id=".$row["id"]."' onClick='return myconfirm(\"delauthor\")'>".BizResources::localize("ACT_DEL")."</a>";
			$detailtxt .= "<tr$clr><td>".inputvar("section$i", $row['section'], 'combo', $sectiondomain, $sAll).'</td>';
			$detailtxt .= '<td>'.inputvar("state$i", $row['state'], 'combo', $statedomain, $sAll).'</td>';
			$detailtxt .= "<td>".inputvar("profile$i", $row["profile"], 'combo', $profiles)."</td>";
			$detailtxt .= "<td>$deltxt</td></tr>";
			$detailtxt .= inputvar( "id$i", $row['id'], 'hidden' );
			$i++;
		}
		$detailtxt .= inputvar( 'recs', $i, 'hidden' );
		break;
	case 'add':
		// 1 row to enter new record
		$detailtxt .= '<tr><td>'.inputvar('section', '', 'combo', $sectiondomain, $sAll).'</td>';
		$detailtxt .= '<td>'.inputvar('state','', 'combo', $statedomain, $sAll).'</td>';
		$detailtxt .= '<td>'.inputvar('profile', '', 'combo', $profiles);
		$detailtxt .= '<td></td></tr>';
		$detailtxt .= inputvar( 'insert', '1', 'hidden' );

		// show other authorizations as info
		// Changed order by state to order by code
		$sql = "SELECT a.`id`, s.`section`, a.`state`, st.`type`, ".
					"st.`state` as `statename`, a.`profile` FROM $dba a ".
				"LEFT JOIN $dbs s on (a.`section` = s.`id`) ".
				"LEFT JOIN $dbst st on (a.`state` = st.`id`) ".
				"WHERE a.`publication` = $publ and a.`issue` = $issue and a.`grpid` = $grp ".
				"ORDER BY s.`section`, st.`type`, st.`code`";
		$sth = $dbh->query($sql);
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		while (($row = $dbh->fetch($sth))) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			if ($row['section']) {
				$detailtxt .= "<tr$clr><td>". formvar($row['section']) .'</td><td>';
			} else {
				$detailtxt .= "<tr$clr><td>". $sAll .'</td><td>';
			}
			if ($row['state']) {
				$detailtxt .= formvar($row['type']).'/'.formvar($row['statename']);
			} else {	
				$detailtxt .= $sAll;
			}	
			$detailtxt .= '</td>';
			$detailtxt .= '<td>'.formvar($profiles[$row['profile']]).'</td>';
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

function profiles($dbh)
{
	$dbp = $dbh->tablename("profiles");
	
	$sql = "select * from $dbp order by `code`, `profile`";
	$sth = $dbh->query($sql);
	$arr = array();
	while (($row = $dbh->fetch($sth)) ) {
		$arr[$row["id"]] = $row["profile"];
	}
	return $arr;
}
