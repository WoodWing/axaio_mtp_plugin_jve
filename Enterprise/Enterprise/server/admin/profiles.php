<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('admin');

$dbh = DBDriverFactory::gen();
$dbp = $dbh->tablename('profiles');
$dbpf = $dbh->tablename('profilefeatures');

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
$sql = "select * from $dbp order by `code`, `profile`";
$sth = $dbh->query($sql);
$cnt = 1;
while (($row = $dbh->fetch($sth))) {
	$p = $row["id"];
	$profile = $row['profile'];
	$desc = $row['description'];
	$bx = inputvar("code$cnt", $row['code'], 'small').inputvar( "order$cnt", $p, 'hidden');
	$txt .= '<tr><td><a href="hpprofiles.php?id='.$p.'">'.formvar($profile).'</a></td>'.
			'<td>'.$bx.'</td><td>'.formvar($desc).'</td></tr>'."\r\n";
	$cnt++;
}
$txt .= inputvar( 'recs', $cnt, 'hidden' );

// generate page
$txt = str_replace('<!--ROWS-->', $txt, HtmlDocument::loadTemplate( 'profiles.htm' ));
$txt = str_replace('<!--HEADER-->', '<th>'.BizResources::localize('OBJ_DESCRIPTION').'</th>', $txt);
print HtmlDocument::buildDocument($txt);

?>