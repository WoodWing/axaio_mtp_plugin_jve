<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('admin');

// Get params
$inPub = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0; // Publication id of the copy source
$inNewName = isset($_REQUEST['newName']) ? trim($_REQUEST['newName']) : ''; // Publication name of the copy destination
$inIssue = isset($_REQUEST['issue']) && $_REQUEST['issue'] == 'on'; // Whether or not to copy issues

// Hidden multicopy feature: get params to copy multiple publications at once
$startIdx = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 1; // prefix number for destination pub names
$countIdx = isset($_REQUEST['count']) ? intval($_REQUEST['count']) : 1; // number of pubs to copy (based on source pub)
if( $countIdx > 1 ) { set_time_limit(3600); }
$prefix = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : ''; // name prefix to use for all copied items inside pub, such as issues, editions, etc

// Duplicate publication and its entire workflow definition
$err = '';
if( $inPub > 0 && isset($_REQUEST['newName']) ) {
	try {
		for( $currIdx = $startIdx; $currIdx < ($startIdx + $countIdx); $currIdx++ ) {

			// Hidden multicopy feature: add postfixes to given name
			$prefixPlusIdx = $prefix;
			$inNewNamePlusIdx = $inNewName;
			if( $countIdx > 1 ) {
				$prefixPlusIdx = $prefixPlusIdx.sprintf('%03d',$currIdx).' ';
				$inNewNamePlusIdx = $prefixPlusIdx.$inNewNamePlusIdx; // optionally add postfix number
			}
			// Perform the copy operation
			$copyPubId = BizCascadePub::copyPublication( $inPub, $inNewNamePlusIdx, $inIssue, $prefixPlusIdx );

			// For last iteration, do redirection
			if( $currIdx == ($startIdx + $countIdx - 1)) {
				if( $countIdx > 1 ) { // multi-copy; go to overview of publications
					header("Location: publications.php");
					exit();
				} else { // single copy; go to maintenance page of copied publication
					header("Location: hppublications.php?id=$copyPubId");
					exit();
				}
			}
		}
	} catch( BizException $e ) {
		$err .= $e->getMessage().'<br/>'.$e->getDetail();
	}
}

//require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
//$pubs = BizPublication::getPublications( $user );
//BZ#11007/11005 getPublications only returns those brands where 'User Authorization' is defined for $user.
// As $user is sysadmin we want all brands 
require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
$pubsdb = DBPublication::listPublications();
foreach ($pubsdb as $pubdb) {
	$pubs[] = new Publication($pubdb['id'], $pubdb['publication']);
}

// Build publication combo
$comboBoxPub = '<select name="ID" style="width:150px">'."\n";
foreach( $pubs as $pub ) {
	$selected = ($inPub == $pub->Id) ? 'selected="selected" ' : '';
	$comboBoxPub .= '<option value="'.$pub->Id.'" '.$selected.'>'.formvar($pub->Name).'</option>'."\n";
}
$comboBoxPub .= '</select>'."\n";

// Build "duplicate issues" checkbox and "new publication" text field
$html = 
	'<tr>'.
		'<td>'.$comboBoxPub.'</td>'."\n".
		'<td>'.inputvar('issue','','checkbox').'</td>'."\n".
		'<td>'.inputvar('newName', $inNewName, null).'</td>'."\n".
	'</tr>'."\n";
$html .= '<font color="#ff0000">'.$err.'</font>';

// Build html document
$tpl = HtmlDocument::loadTemplate( 'duplicate_pub.htm' );
$tpl = str_replace( '<!--PUBLICATIONS-->', $html, $tpl );
print HtmlDocument::buildDocument( $tpl );
