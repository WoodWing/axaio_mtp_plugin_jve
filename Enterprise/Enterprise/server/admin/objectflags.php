<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/HtmlPageNavigator.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure( 'admin' );
$nav = new HtmlPageNavigator( '/server/admin/objectflags.php' );

if (!$dbh = DBDriverFactory::gen()) die (BizResources::localize("ERR_DATABASE"));	
$flg_db = $dbh->tablename('objectflags');
$obj_db = $dbh->tablename('objects');

if (@$_REQUEST['ClearAll']) { // delete all objectflag rows?
	$sql = 'delete from '.$flg_db;
	$sth = $dbh->query( $sql );
	$res = $dbh->fetch( $sth );
}

// query all messagelog rows
$sql = 'select f.*, o.`name` as `objname` from '.$flg_db.' f ';
$sql .= 'left join '.$obj_db.' o on (f.`objid` = o.`id`) ';
$sth = $dbh->query( $sql );

// Show title and buttons
$html = '<h2>Object Flags</h2>';
$html .= '<form action="'.$nav->GetURL().'" method="post">';
$html .= '<input type="submit" name="ClearAll" value="Clear All"/>';
$html .= '<input type="submit" name="Refresh" value="Refresh"/>';
$html .= $nav->GetBackPressButton().'</form>';

// show all messagelog rows
$html .= '<table><tr><td><b>Object</b></td><td><b>Origin</b></td><td><b>Flag</b></td><td><b>Severity</b></td><td><b>Message</b></td><td><b>Locked</b></td></tr>';
if( $sth ) while( ($row = $dbh->fetch( $sth )) ) {
	$html .= '<tr><td>'.formvar($row['objname']).'</td>'.
			'<td>'.formvar($row['flagorigin']).'</td>'.
			'<td>'.$row['flag'].'</td>'.
			'<td>'.$row['severity'].'</td>'.
			'<td>'.formvar($row['message']).'</td>'.
			'<td>'.$row['locked'].'</td></tr>';	
}
$html .= '</table>';

print HtmlDocument::buildDocument( $html );

?>