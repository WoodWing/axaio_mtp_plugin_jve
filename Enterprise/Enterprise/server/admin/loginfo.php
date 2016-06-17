<?php
// loginfo.php: Shows detailed log information

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar(), inputvar()
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('admin'); // BUG fix: admin was not checked!

$ID = intval(@$_REQUEST['id']);
$objectmap = getObjectTypeMap();
$dbDriver = DBDriverFactory::gen();
global $globUser;

$tpl = HtmlDocument::loadTemplate( 'loginfo.htm' );

$dbl = $dbDriver->tablename('log');
$dbp = $dbDriver->tablename('publications');
$dbi = $dbDriver->tablename('issues');
$dbs = $dbDriver->tablename('publsections');
$dbst = $dbDriver->tablename('states');
$dbo = $dbDriver->tablename('objects');

$sqlfrom = "from $dbl l
	left join $dbp p on p.`id` = l.`publication`
	left join $dbi i on i.`id` = l.`issue`
	left join $dbs s on s.`id` = l.`section`
	left join $dbst st on st.`id` = l.`state`
	left join $dbo o on o.`id` = l.`objectid`
	left join $dbo op on op.`id` = l.`parent`";
	
$sqlselect = 'select l.`id`, l.`user`, l.`service`, l.`ip`, l.`date`,
				l.`lock`, l.`rendition`, l.`type`, l.`routeto`, l.`edition`,
				'.$dbDriver->concatFields( array( 'l.`majorversion`', "'.'", 'l.`minorversion`' )).' as "version",
				p.`publication`, i.`name` as `issuename`, s.`section`, st.`state`, l.`state` as `stateid`,
				o.`id` as `oid`, o.`name` as `oname`,
				op.`id` as `opid`, op.`name` as `opname` ';

$sql = $sqlselect.$sqlfrom." where l.`id` = $ID";
$sth = $dbDriver->query($sql);
$row = $dbDriver->fetch($sth);

$tpl = str_replace ('<!--OBJNAME-->', $row['oname'] ? formvar($row['oname']) : '&nbsp;', $tpl);

$tpl = str_replace ('<!--OBJPUB-->', $row['publication'] ? formvar($row['publication']) : '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJISS-->', $row['issuename'] ? formvar($row['issuename']) : '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJSEC-->', $row['section'] ? formvar($row['section']) : '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJEDITION-->', $row['edition'] ? formvar($row['edition']) : '&nbsp;', $tpl);

if( $row['stateid'] == '-1' ) {
	$status = BizResources::localize('PERSONAL_STATE');
} else {
	$status = $row['state'] ? formvar($row['state']) : '&nbsp;';
}
$tpl = str_replace ('<!--OBJSTA-->', $status, $tpl);

$tpl = str_replace ('<!--OBJID-->', $row['oid'] ? formvar($row['oid']) : '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJTYPE-->', $row['type'] ? formvar($objectmap[$row['type']]) : '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJVERSION-->', $row['version'] ? formvar($row['version']) : '&nbsp;', $tpl);

$tpl = str_replace ('<!--OBJROUTE-->',$row['routeto'] ? formvar($row['routeto']): '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJPARENT-->',$row['opname'] ? formvar($row['opname']) : '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJRENDITION-->',$row['rendition'] ? formvar($row['rendition']) : '&nbsp;', $tpl);
$tpl = str_replace ('<!--OBJLOCK-->',$row['lock'] ? formvar($row['lock']) : '&nbsp;', $tpl);

print HtmlDocument::buildDocument($tpl, true);