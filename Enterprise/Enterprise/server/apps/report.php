<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR."/server/secure.php";
require_once BASEDIR."/server/apps/functions.php";
require_once BASEDIR."/server/apps/graph.php";
require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // inputvar(), formvar()

// Allow caller (like Content Station) to overrule ticket and work with its own
$ticket = isset($_GET['ticket']) ? $_GET['ticket'] : '';
if( empty($ticket) ) { // take ticket from URL (first post only)
	$ticket = isset($_COOKIE['ticket']) ? $_COOKIE['ticket'] : '';
	$useTemplate = !isset($_REQUEST['use_template']) || $_REQUEST['use_template'] == 'true';
} else { // typically Content Station overruling the ticket...
	setLogCookie( "ticket", $ticket ); // make sure we set WebApps cookie, or else buildDocument redirects to logon page!
	$useTemplate = false;
}
$ticket = checkSecure( null, null, true, $ticket );
webauthorization( BizAccessFeatureProfiles::ACCESS_REPORTING );

// Utils class as courtesy for reports:
class scent_reportutils
{
    function getColorList( $inPub, $selectedIssueID, $objType, &$graph, &$colors )
	{
		require_once BASEDIR.'/server/dbclasses/DBStates.class.php';
		$totalByStates = DBStates::getObjectsPerState( $inPub, $selectedIssueID, $objType );

		$colors = '';
		$graph = array();
		if ( $totalByStates ) foreach ( $totalByStates as $totalByState )  {
			$graph[$totalByState['state']] = $totalByState['total'];
			if ($colors != '') $colors .= ',';
//			if( $row['id'] == '-1' ) { Not yet implemented, see DBStates::getObjectsPerState().
//				$colors .= trim(PERSONAL_STATE_COLOR,"#");
//			} else {
				$colors .= trim($totalByState['color'],"#");
//			}
		}
	}
}

if( $useTemplate ) {
	$tpl = HtmlDocument::loadTemplate( 'report.htm' );
} else { // No template, but tell navigator we do HTML and need UTF-8
	$tpl = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
</head>
<body>';
	$tpl .= HtmlDocument::loadTemplate( 'report.htm' );
}

require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
$user = DBTicket::checkTicket( $ticket );

$inPub = isset($_POST['Publication']) ? intval($_POST['Publication']) : 0;
$inIssue = isset($_POST['Issue']) ? intval($_POST['Issue']) : 0;
$dum = null;
$path = isset($_POST['Reports']) ? $_POST['Reports'] : '';
cookie("statistics", $inPub == '', $inPub, $inIssue, $path, $dum, $dum, $dum, $dum);

// Re-validate data retrieved from cookie! (XSS attacks)
$inPub = intval($inPub);
$inIssue = intval($inIssue);

/////////////////////// *** Publication combo *** ///////////////////////
//
	
$comboBoxPub = "<select name=Publication style=width:150px ONCHANGE = 'submit();'>";
$comboBoxPub .= "<option></option>";
$pubs = BizPublication::getPublications( $user );
foreach( $pubs as $pub ) {
	if( $pub->Id != $inPub ) {
		$comboBoxPub .= '<option value="'.$pub->Id.'">'.formvar($pub->Name).'</option>';
	} else {
		$comboBoxPub .= '<option value="'.$pub->Id.'" selected="selected">'.formvar($pub->Name).'</option>';
	}
}
$comboBoxPub .= "</select>";

$tpl = str_replace ("<!--COMBOPUB-->",$comboBoxPub, $tpl);
	
//
/////////////////////// *** Issue combo *** ///////////////////////
//
$comboBoxIss = '';
$comboBoxIss = startComboBox($comboBoxIss, 'Issue');
$comboBoxIss = addEmptyToComboBox($comboBoxIss);
$arrayOfIssues = BizPublication::getIssues( $user, $inPub );
$pcn_issues = getListOfPrevCurrNextIssues($inPub, $arrayOfIssues);
// Add items to the combo box
$comboBoxIss = addPrevCurrNextToComboBoxIss($comboBoxIss, $pcn_issues, $inIssue);
$comboBoxIss = addListOfIssuesToComboBoxIss($comboBoxIss, $arrayOfIssues, $inIssue);
$comboBoxIss = EndComboBox($comboBoxIss);

$tpl = str_replace ("<!--COMBOISS-->",$comboBoxIss, $tpl);
$selectedIssueID = getSelectedIssue($inIssue, $pcn_issues);

if ($inPub && $selectedIssueID) {
	$comboBoxRpt = "<select name=Reports style=width:150px onChange='submit();'>";
} else {
	$comboBoxRpt = "<select name=Reports style=width:150px>";
}
$comboBoxRpt .= "<option></option>";

// Read all report scripts from config/reports folder:
$reppath=BASEDIR."/config/reports/";
$handle = opendir($reppath);
if( $handle )
{
	while(( $file = readdir($handle))) 
  	{ 
       	if($file!="." && $file!="..")
		{
			$filenm  = explode('.', $file);
			if(!is_dir($filenm[0]))
			{
				$reportFiles[] = $reppath.$file;
				if( $path == $file ) {
					$comboBoxRpt .= '<option value="'.formvar($file).'" selected="selected">'.formvar($filenm[0]).'</option>';
				} else {
					if ($filenm[0] != ""){
					    // Hide hidden files that start with a '.', otherwise there will be empty values in the dropdown.
					    $comboBoxRpt .= '<option value="'.formvar($file).'">'.formvar($filenm[0]).'</option>';
					}
				}
			}
		}
	}
	closedir($handle);
}
$comboBoxRpt .= "</select>";

$tpl = str_replace ("<!--COMBOREP-->",$comboBoxRpt, $tpl);

// If we have a report to execute, check if the files still exists (it is kept in cookie):
if( $path != "" && in_array($reppath.$path, $reportFiles))
{
	// Include report .php and execute report:
	require_once($reppath.$path);
	$reportResult = SCEntReport( $inPub, $selectedIssueID );
} else {
	$reportResult = '';
}
$tpl = str_replace ("<!--REPTABLE-->", $reportResult, $tpl);

/*
$tpl .= "<a href=\"../apps/reportdeadlines.php?issueid=$inIssue\">" .
		"<img src=\"../../config/images/deadline_32.gif\" border=\"0\" width=\"32\" height=\"32\"></a>";
*/
$tpl = str_replace( '<!--USE_TEMPLATE-->', $useTemplate ? 'true' : 'false', $tpl );
$tpl = str_replace( '<!--TICKET-->', $ticket, $tpl );

print HtmlDocument::buildDocument( $tpl, $useTemplate );

//////////////////////// *** functions section *** /////////////////////////////
function startComboBox($comboBoxIss, $name)
{
	$comboBoxIss .= '<select name="' . formvar($name) . '" style=width:150px ONCHANGE = ' . "'submit();'>";

	return $comboBoxIss;
}

function addEmptyToComboBox($comboBoxIss)
{
	$comboBoxIss .= "<option></option>";

	return $comboBoxIss;
}

function getListOfPrevCurrNextIssues($publication, $allIssues)
{
	// Just get previous/current/next issues without checking authorization
	$pcn_issues = BizPublication::listPrevCurrentNextIssues($publication);

	// The $allIssues contains the issues checked for authorizations.
	// The previous/current/next must be in line with the list of issues.
	if($pcn_issues) foreach ($pcn_issues as $key_pcn_issue => $pcn_issue) {
		$found  = false;
		foreach ($allIssues as $allIssue) {
			if ($pcn_issue['id'] == $allIssue->Id) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			unset($pcn_issues[$key_pcn_issue]);
		}
	}

	return $pcn_issues;
}

function addPrevCurrNextToComboBoxIss($comboBoxIss, $pcn_issues, $inIssue)
{
	if (isset($pcn_issues['current']) && is_array($pcn_issues['current'])) {
		$selected = ($inIssue == -2) ? 'selected="selected"' : '';
		$comboBoxIss .= '<option value="-2" ' . $selected . '>' . BizResources::localize('CURRENT_ISSUE') . ' (' . formvar($pcn_issues['current']['issue']) . ')' . '</option>';
	}

	if (isset($pcn_issues['prev']) && is_array($pcn_issues['prev'])) {
		$selected = ($inIssue == -3) ? 'selected="selected"' : '';
		$comboBoxIss .= '<option value="-3" ' . $selected . '>' . BizResources::localize('PREV_ISSUE') . ' (' . formvar($pcn_issues['prev']['issue']) . ')' . '</option>';
	}

	if (isset($pcn_issues['next']) && is_array($pcn_issues['next'])) {
		$selected = ($inIssue == -4) ? 'selected="selected"' : '';
		$comboBoxIss .= '<option value="-4" ' . $selected . '>' . BizResources::localize('NEXT_ISSUE') . ' (' . formvar($pcn_issues['next']['issue']) . ')' . '</option>';
	}

	return $comboBoxIss;
}

function addListOfIssuesToComboBoxIss($comboBoxIss, $allIssues, $inIssue)
{
	if (!empty($allIssues)) {
		foreach ($allIssues as $allIssue) {
			if($allIssue->Id != $inIssue) {
				$comboBoxIss .= '<option value="'.$allIssue->Id.'">'.formvar($allIssue->Name).'</option>';
			} else {
				$comboBoxIss .= '<option value="'.$allIssue->Id.'" selected="selected">'.formvar($allIssue->Name).'</option>';
			}
		}
	}
	return $comboBoxIss;
}

function EndComboBox($comboBoxIss)
{
	$comboBoxIss .= "</select>\n";

	return $comboBoxIss;
}

function getSelectedIssue($inIssue, $pcn_issues)
{
	// Based on the name of the selected item the ID of the selected item is
	// determined.
	if ($inIssue > 0) {
		return $inIssue; // name is ID
	}

	if (isset($pcn_issues['current']) && is_array($pcn_issues['current']) && ($inIssue == -2)) {
		return $pcn_issues['current']['id'];
	}

	if (isset($pcn_issues['prev']) && is_array($pcn_issues['prev']) && ($inIssue == -3)) {
		return $pcn_issues['prev']['id'];
	}

	if (isset($pcn_issues['next']) && is_array($pcn_issues['next']) && ($inIssue == -4)) {
		return $pcn_issues['next']['id'];
	}

	return 0;
}
