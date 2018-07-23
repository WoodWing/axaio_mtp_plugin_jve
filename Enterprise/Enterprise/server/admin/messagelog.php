<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once( BASEDIR.'/server/admin/global_inc.php' );
require_once( BASEDIR.'/server/secure.php' );
require_once( BASEDIR.'/server/utils/HtmlPageNavigator.class.php' );
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure( 'admin' );
$nav = new HtmlPageNavigator( '/server/admin/messagelog.php' );

if (!$dbh = DBDriverFactory::gen()) die (BizResources::localize("ERR_DATABASE"));	
$msg_db = $dbh->tablename('messagelog');
$obj_db = $dbh->tablename('objects');
$usr_db = $dbh->tablename('users');

if (@$_REQUEST['ClearAll']) { // delete all messagelog rows?
	$sql = 'delete from '.$msg_db;
	$sth = $dbh->query( $sql );
	$res = $dbh->fetch( $sth );
}

// query all messagelog rows
$sql = 'select m.*, o.`name` as `objname`, u.`user` as `usrname` from '.$msg_db.' m ';
$sql .= 'left join '.$obj_db.' o on (m.`objid` = o.`id`) ';
$sql .= 'left join '.$usr_db.' u on (m.`userid` = u.`id`) ';
$sth = $dbh->query( $sql );

// Show title and buttons
$html = '<h2>Message Log</h2>';
$html .= '<form action="'.$nav->GetURL().'" method="post">'.
			'<input type="submit" name="ClearAll" value="Clear All"/>'.
			'<input type="submit" name="Refresh" value="Refresh"/>'.
			$nav->GetBackPressButton().
		'</form>';

// show all messagelog rows
$html .= '<table class="text"><tr><td><b>Object</b></td><td><b>User</b></td><td><b>Date</b></td>'.
			'<td><b>Type</b></td><td><b>Detail</b></td><td><b>Message</b></td>'.
			'<td><b>Level</b></td><td><b>From</b></td></tr>';
if( $sth ) while( ($row = $dbh->fetch( $sth )) ) {
	$html .= '<tr><td>'.formvar($row['objname']).'</td>'.
				'<td>'.formvar($row['usrname']).'</td>'.
				'<td>'.formvar($row['date']).'</td>'.
				'<td>'.formvar($row['messagetype']).'</td>'.
				'<td>'.formvar($row['messagetypedetail']).'</td>'.
				'<td>'.formvar($row['message']).'</td>'.
				'<td>'.formvar($row['messagelevel']).'</td>'.
				'<td>'.formvar($row['fromuser']).'</td>'.
			'</tr>';
}
$html .= '</table>';

print HtmlDocument::buildDocument( $html );

?>