<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('publadmin');

$dbh = DBDriverFactory::gen();
$dbp = $dbh->tablename('publications');
$dbc = $dbh->tablename('channels');
$dbi = $dbh->tablename('issues');

$recs = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;
if ($recs > 0) {
	for ($i = 1; $i < $recs; $i++) {
		$id = intval($_REQUEST["order$i"]);
		$cd = intval($_REQUEST["code$i"]);
		$sql = "update $dbp set `code` = $cd where `id` = $id";
		$sth = $dbh->query($sql);
	}
}

$txt = '';
$sql = "select * from $dbp order by `code`, `publication`";
$sth = $dbh->query($sql);
$cnt = 1;
while (($row = $dbh->fetch($sth))) {
	$p = $row['id'];

	if (!checkPublAdmin($p, false)) continue;		// check rights for this publication

	$sql = "SELECT count(*) as `c` from $dbi iss ".
			"INNER JOIN $dbc chn ON ( chn.`id` = iss.`channelid` ) ".
			"INNER JOIN $dbp pub ON ( pub.`id` = chn.`publicationid` ) ".
			"WHERE pub.`id` = $p";
	$sth2 = $dbh->query($sql);
	$r2 = $dbh->fetch($sth2);
	$ci = $r2['c'];

	$publication = $row['publication'];
	$bx = inputvar("code$cnt", $row['code'], 'small').inputvar("order$cnt",$p,'hidden');
	$txt .= '<tr><td><a href="hppublications.php?id='.$p.'">'.formvar($publication).'</a></td>'.
			'<td>'.$ci.'</td><td>'.$bx.'</td></tr>'."\r\n";
	
	$cnt++;
}
$txt .= inputvar( 'recs', $cnt, 'hidden' );

// generate page
$txt = str_replace('<!--ROWS-->', $txt, HtmlDocument::loadTemplate( 'publications.htm' ) );
print HtmlDocument::buildDocument($txt);
