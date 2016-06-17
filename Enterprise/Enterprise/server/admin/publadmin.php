<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('publadmin');

// database stuff
$dbh = DBDriverFactory::gen();
$dbp = $dbh->tablename('publications');
$dba = $dbh->tablename('publadmin');
$dbg = $dbh->tablename('groups');

// determine incoming mode
$publ = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$grp = isset($_REQUEST['grp']) ? intval($_REQUEST['grp']) : 0;
$mode = ($grp > 0) ? 'insert' : 'addgrp';

// check publication rights
checkPublAdmin($publ);

// handle request
switch ($mode) {
	case 'insert':
		// handle autoincrement for non-mysql
		$id = $dbh->newid($dba,false);
		if ($id > 0) {
			$sql = "INSERT INTO $dba (`id`, `publication`, `grpid`) VALUES ($id, $publ, $grp)";
		} else {
			$sql = "INSERT INTO $dba (`publication`, `grpid`) VALUES ($publ, $grp)";
		}
		$sth = $dbh->query($sql);
		if ($id === 0) $id = $dbh->newid($dba, true);

		// return to publ page
		header("Location:hppublications.php?id=$publ");
		exit;
}

// generate upper part (edit fields)
$fuser = $dbh->quoteIdentifier("user");
$sql = "select `publication` from $dbp where `id` = $publ";
$sth = $dbh->query($sql);
$row = $dbh->fetch($sth);
$name = $row['publication'];

$sql = "SELECT g.`id`, g.`name` from $dbg g ".
		"LEFT JOIN $dba a on (a.`grpid` = g.`id` and a.`publication` = $publ) ".
		"WHERE a.`id` is null ".
		"ORDER BY `name`";
$sth = $dbh->query($sql);
$combo = '<select name="grp">';
while ( ($row = $dbh->fetch($sth)) ) {
	$combo .= '<option value="'.$row['id'].'">'.formvar($row['name']).'</option>';
}
$combo .= '</select>';
$combo .= inputvar( 'publ', $publ, 'hidden' );
$txt = HtmlDocument::loadTemplate( 'publadmin.htm' );

// Pre-translate the ACT_GRANT_ADMIN_RIGHTS key to fill in the publication name
$msg = BizResources::localize( 'ACT_GRANT_ADMIN_RIGHTS' );
$msg = str_replace( '%', $name, $msg );
$txt = str_replace( '<!--RES:ACT_GRANT_ADMIN_RIGHTS-->', formvar($msg), $txt );

$txt = str_replace("<!--COMBO-->", $combo, $txt );
$txt = str_replace("<!--ID-->", $publ, $txt);

// generate total page
print HtmlDocument::buildDocument($txt);
?>