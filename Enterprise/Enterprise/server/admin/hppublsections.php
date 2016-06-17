<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('publadmin');

// database stuff
$dbh = DBDriverFactory::gen();
$dbp = $dbh->tablename('publications');
$dbs = $dbh->tablename('publsections');
$dbi = $dbh->tablename('issues');

// determine incoming mode
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
if (isset($_REQUEST['vupdate']) && $_REQUEST['vupdate']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}

// get param's
$name = isset($_REQUEST['sname']) ? trim($_REQUEST['sname']) : '';
$publ = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$channelid = isset($_REQUEST['channelid']) ? intval($_REQUEST['channelid'])  : 0;
$description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
$deadline = isset($_REQUEST['deadline']) ? $_REQUEST['deadline'] : '';
$pages = isset($_REQUEST['pages']) ? intval($_REQUEST['pages']) : 0;
$issue = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; 

// check publication rights
checkPublAdmin($publ);

$errors = array();

// handle request
switch ($mode) {
	case 'update':
		// check not null
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $dbs where `section` = '" . $dbh->toDBString($name) . "' and `publication` = $publ and `issue` = $issue and `id` != $id";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}

		// update section at DB
		$sql = "update $dbs set `section`='" . $dbh->toDBString($name) . "', `issue` = $issue , ".
					"`description` = '" . $dbh->toDBString($description) . "', `deadline` = '" . $dbh->toDBString($deadline)."', `pages` = $pages where `id` = $id";
		$sth = $dbh->query($sql);
		break;
		
	case 'insert':
		// check not null
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $dbs where `section` = '" . $dbh->toDBString($name) . "' and `publication` = $publ and `issue` = $issue";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}

		// create section at DB
		$sql = "INSERT INTO $dbs (`issue`, `section`, `publication`, `description`, `deadline`, `pages`, `code`) ".
				"VALUES ($issue, '" . $dbh->toDBString($name) . "', $publ, '" . $dbh->toDBString($description) . "', ".
				"'" . $dbh->toDBString($deadline)."', $pages, 0)";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql);
		if ($id === 0) $id = $dbh->newid($dbs, true);
		break;

	case 'delete':
		die( 'ERROR: Delete section is handled at hppublications.' );
		break;
}

// delete: back to overview
if ($mode == 'delete' || $mode == 'update' || $mode == 'insert') {
	if ($issue) {
		header("Location:hppublissues.php?id=$issue");
		exit();
	} else {
		header("Location:hppublications.php?id=$publ");
		exit();
	}
}

// generate upper part (edit fields)
if ($mode == 'error') {
	$row = array ('section' => $name);
} elseif ($mode != "new") {
	$sql = "select * from $dbs where `id` = $id";
	$sth = $dbh->query($sql);
	$row = $dbh->fetch($sth);
} else {
	$row = array ('section' => '', 'description' => '', 'deadline' => '', 'pages' => '');
}
$sql = "select * from $dbp where `id` = $publ";
$sthp = $dbh->query($sql);
$rowp = $dbh->fetch($sthp);

$txt = HtmlDocument::loadTemplate( 'hppublsections.htm' );

// error handling
$err = '';
foreach ($errors as $error) {
	$err .= "$error<br>";
}
$txt = str_replace("<!--ERROR-->", $err, $txt);

if ($issue) {
	$sql = "select `name` from $dbi where `id` = $issue";
	$sth = $dbh->query($sql);
	$rowi = $dbh->fetch($sth);

	$txt = str_replace("<!--VAR:ISSUE-->", formvar($rowi['name']).inputvar('issue',$issue,'hidden'), $txt);
} else {
	$txt = preg_replace("/<!--IF:ISSUE-->.*?<!--ENDIF:ISSUE-->/s",'', $txt);
}

if( $channelid ) {
	require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
	$rowc = DBChannel::getChannel( $channelid );
	$txt = str_replace('<!--VAR:CHANNEL-->', formvar($rowc['name']).inputvar('channelid',$channelid,'hidden'), $txt );
} else {
	$txt = preg_replace('/<!--IF:CHANNEL-->.*?<!--ENDIF:CHANNEL-->/s','', $txt);
}

// fields
$txt = str_replace('<!--VAR:NAME-->', '<input maxlength="255" name="sname" value="'.formvar($row['section']).'"/>', $txt );
$txt = str_replace('<!--VAR:PUBL-->', formvar($rowp['publication']).inputvar('publ',$publ,'hidden'), $txt );
$txt = str_replace('<!--VAR:DESCRIPTION-->', inputvar('description', $row['description'], 'area'), $txt );
$txt = str_replace('<!--VAR:PAGES-->', inputvar('pages', $row['pages'], 'small'), $txt );
$txt = str_replace('<!--VAR:HIDDEN-->', inputvar('id',$id,'hidden'), $txt );

if( $issue > 0 ) {
	$back = "hppublissues.php?id=$issue";
} else {
	$back = "hppublications.php?id=$publ";
}
$txt = str_replace("<!--BACK-->", $back, $txt);

//set focus to the first field
$txt .= "<script language='javascript'>document.forms[0].sname.focus();</script>";

// generate total page
print HtmlDocument::buildDocument($txt);
?>