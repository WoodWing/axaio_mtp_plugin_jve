<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // inputvar(), formvar()
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';

// Special debug/test option:
//    on = production/safe mode, showing only overrule issues and print channel issues
//    off = debug/test mode, showing all issues
if( !defined( 'MTP_ISSUE_FILTER' ) ) {
	define( 'MTP_ISSUE_FILTER', 'on' ); 
}

$ticket = checkSecure('publadmin');
global $globUser;  // set by checkSecure()
$user = $globUser;
$tpl = HtmlDocument::loadTemplate( 'mtpsetup.htm' );

$PublicationID = isset($_REQUEST['Publication']) ? intval($_REQUEST['Publication']) : 0;
$IssueID = isset($_REQUEST['Issue']) ? intval($_REQUEST['Issue']) : 0;
$add = isset($_REQUEST['addbutt']) ? (bool)trim($_REQUEST['addbutt']) : false;
$save = isset($_REQUEST['savebutt']) ? (bool)trim($_REQUEST['savebutt']) : false;

$dbDriver = DBDriverFactory::gen();
$mtptab = $dbDriver->tablename("mtp");

$layoutbefore = isset($_REQUEST['layout']) ? intval($_REQUEST['layout']) : 0;
$layoutafter = isset($_REQUEST['layoutafter']) ? intval($_REQUEST['layoutafter']) : 0;
$imagebefore = isset($_REQUEST['image']) ? intval($_REQUEST['image']) : 0;
$imageafter = isset($_REQUEST['imageafter']) ? intval($_REQUEST['imageafter']) : 0;
$articlebefore = isset($_REQUEST['state']) ? intval($_REQUEST['state']) : 0;
$articleafter = isset($_REQUEST['stateafter']) ? intval($_REQUEST['stateafter']) : 0;

$callas = isset($_POST['callas']) ? trim($_POST['callas']) : '';
$delete = isset($_REQUEST['del']) ? (bool)trim($_REQUEST['del']) : false;
$edit = isset($_REQUEST['edit']) ? (bool)trim($_REQUEST['edit']) : false;
$iss = isset($_REQUEST['iss']) ? intval($_REQUEST['iss']) : 0;
$pub = isset($_REQUEST['pub']) ? intval($_REQUEST['pub']) : 0;
$dellayout = isset($_REQUEST['dellayout']) ? intval($_REQUEST['dellayout']) : 0;
$editlayout = isset($_REQUEST['editlayout']) ? intval($_REQUEST['editlayout']) : 0;

// Check the MtP configuration
$error = testMtpConfigServer();

if( !$error ) {
	// Checks if db has obsoleted data structures, typically from migrated databases
	$badIssueIds = array();
	$error .= detectBadIssues( $badIssueIds );
	//echo print_r($badIssueIds,true);
}

///case save
if($save === true){
	if( trim(MTP_JOB_NAME) == '' && trim($callas) == '' ) {
		// Error when *both* global job name and specific job name are empty.
		$error .= BizResources::localize('ERR_NOT_EMPTY');
		$pub = $PublicationID;
		$iss = $IssueID;
	} else {
		if($edit === true){
			$sql = 'UPDATE '.$mtptab.' SET `arttriggerstate`='.$articlebefore.', `imgtriggerstate`='.$imagebefore
					.', `layprogstate`='.$layoutafter.', `artprogstate`='.$articleafter.', `imgprogstate`='.$imageafter.', `mtptext`=\''.$dbDriver->toDBString($callas).'\' '
					.'WHERE `publid`= '.$PublicationID.' AND `issueid`= '.$IssueID.' AND `laytriggerstate`= '.$editlayout;
			$retval = $dbDriver->query ($sql);
			$edit = false;
			$editlayout = 0;
		}else{
			$sql = 'INSERT INTO '.$mtptab.' (`publid`, `issueid`, `laytriggerstate`, `arttriggerstate`, `imgtriggerstate`, '
						.'`layprogstate`, `artprogstate`, `imgprogstate`, `mtptext`) '
					.'VALUES('.$PublicationID.', '.$IssueID.', '.$layoutbefore.', '.$articlebefore.', '.$imagebefore.', '
						.$layoutafter.', '.$articleafter.', '.$imageafter.', \''.$dbDriver->toDBString($callas).'\')';
			//$sql = $dbDriver->autoincrement($sql);
			$retval = $dbDriver->query ($sql);
		}
	}
}

//case delete
if($delete){
	$sql = 'DELETE FROM '.$mtptab.' WHERE `publid`= '.$pub.' AND `issueid`= '.$iss.' AND `laytriggerstate`= '.$dellayout;
	$retval = $dbDriver->query ($sql);
	$PublicationID = $pub;
	$IssueID = $iss;
}
if($edit === true){
	$PublicationID = $pub;
	$IssueID = $iss;
}

/////////////////// PUB_INPUT ///////////////////
if( $edit === true || $add === true ) { // disable pub combo when handling edit/add row of settings
	$pub_Input = '<input type="hidden" name="Publication" value="'.$PublicationID.'"/>'; 
	$pub_Input .= '<select name="Publication_disabled" style="width:100%" onChange="submit()" tabindex="2" disabled="disabled">';
} else {
	$pub_Input = '<select name="Publication" style="width:100%" onChange="submit()" tabindex="2">';
}

$pubs = BizPublication::getPublications( $user );
foreach( $pubs as $pub ) {
	if($pub->Id == $PublicationID || $PublicationID == 0 ) {
		$PublicationID = $pub->Id; // take first pub when none selected before
		$pub_Input .= '<option value="'.$pub->Id.'" selected="selected">'.formvar($pub->Name).'</option>';
	} else {
		$pub_Input .= '<option value="'.$pub->Id.'">'.formvar($pub->Name).'</option>';
	}
}
$pub_Input .= '</select>';
$tpl = str_replace ("<!--PUB_INPUT-->", $pub_Input, $tpl);

/////////////////// ISS_INPUT ///////////////////
if( $edit === true || $add === true || $PublicationID == 0 ) { // disable issue combo when handling edit/add row of settings
	$comboBoxIss = '<input type="hidden" name="Issue" value="'.$IssueID.'"/>'; 
	$comboBoxIss .= '<select name="Issue_disabled" style="width:100%" onChange="submit()" tabindex="3" disabled="disabled">';
} else {
	$comboBoxIss = '<select name="Issue" style="width:100%" onChange="submit()" tabindex="3">';
}

if( $PublicationID ) {
	$issues = BizPublication::getIssues( $user, $PublicationID );
	$printIssues = array();
	
	// Hide non-print issues (makes no sense to MtP)
	require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
	$chanTab = $dbDriver->tablename( 'channels' );
	foreach( $issues as $iss ) {
		$chanId = DBIssue::getChannelId( $iss->Id );
		$sql = 'SELECT `type` FROM '.$chanTab.' WHERE `id`= '.$chanId;
		$sth = $dbDriver->query ($sql);
		$row = $dbDriver->fetch( $sth );
		if( $row['type'] == 'print' || MTP_ISSUE_FILTER == 'off' || isset($badIssueIds[$iss->Id]) ) {
			$printIssues[] = $iss;
		}
	}	
	if( count($printIssues) > 0 || MTP_ISSUE_FILTER == 'off') {

		// Mark publication issues ('Select All' entry) if there are MtP configurations (to ease admin users to look up)
		$sql = 'SELECT COUNT(*) as `CfgCount` FROM '.$mtptab.' WHERE `publid`= '.$PublicationID.' AND `issueid`= 0';
		$sth = $dbDriver->query ($sql);
		$row = $dbDriver->fetch( $sth );
		$issueMark = $row && isset($row['CfgCount']) && $row['CfgCount'] > 0 ? '*' : '';

		foreach( $printIssues as $iss ) {
			// Add 'select all' entry when there are publication issues (not only overrule issues)
			if( !$iss->OverrulePublication || MTP_ISSUE_FILTER == 'off' ) {
				$comboBoxIss .= '<option value="">('.BizResources::localize('ACT_SELECT_ALL').$issueMark.')</option>';
				break;
			}
		}
		foreach( $printIssues as $iss ) {
			// We only support *one* level of MtP configitations per workflow.
			// So that is at publication level (= all its issues) OR at overruled issue level (= 1 issue).
			// That is why we skip publication issues and allow overruled issues here.
			if( $iss->OverrulePublication || MTP_ISSUE_FILTER == 'off' || isset($badIssueIds[$iss->Id]) ) { 

				// Mark overrule issues that have MtP configuration (to ease admin users to look up)
				$sql = 'SELECT COUNT(*) as `CfgCount` FROM '.$mtptab.' WHERE `publid`= '.$PublicationID.' AND `issueid`= '.$iss->Id;
				$sth = $dbDriver->query ($sql);
				$row = $dbDriver->fetch( $sth );
				$issueMark = $row && isset($row['CfgCount']) && $row['CfgCount'] > 0 ? '*' : '';

				if( $iss->Id != $IssueID ) {
					$comboBoxIss .= '<option value="'.$iss->Id.'">'.formvar($iss->Name).$issueMark.'</option>';
				} else {
					$comboBoxIss .= '<option value="'.$iss->Id.'" selected="selected">'.formvar($iss->Name).$issueMark.'</option>';
				}
			}
		}
	} else {
		$PublicationID = 0; // if publication has no issues, so there is no configuration possible
	}
}
$comboBoxIss .= '</select>';
$tpl = str_replace ("<!--ISS_INPUT-->",$comboBoxIss, $tpl);

$submit = '';
$row = '';
$view = '';

/////////////////// ROWS/VIEW ///////////////////
if($PublicationID > 0){
	// Draw table header
	$row .= '<tr><th>|</th><th colspan="3">'.BizResources::localize('BEFORE').'</th><th>|</th><th colspan="3">'.BizResources::localize('AFTER').'</th><th>|</th><th>Axaio</th><th>|</th><th>&nbsp;</th><th>&nbsp;</th></tr>';
	$row .= '<tr><th>|</th><th>'.BizResources::localize('OBJ_STATUS_LAYOUTS').'</th><th>'.BizResources::localize('OBJ_STATUS_ARTICLES').'</th>';
	$row .= '<th>'.BizResources::localize('OBJ_STATUS_IMAGES').'</th><th>|</th><th>'.BizResources::localize('OBJ_STATUS_LAYOUTS').'</th>';
	$row .= '<th>'.BizResources::localize('OBJ_STATUS_ARTICLES').'</th><th>'.BizResources::localize('OBJ_STATUS_IMAGES').'</th><th>|</th>';
	$row .= '<th>'.BizResources::localize('MTP_SETT').'</th><th>|</th><th>'.BizResources::localize('ACT_DEL').'</th><th>'.BizResources::localize('ACT_EDIT').'</th></tr>';

	$statuses = array();
	$sql = 'SELECT `issueid`, `laytriggerstate` FROM '.$mtptab.' WHERE `publid`= '.$PublicationID;
	$sth = $dbDriver->query($sql);
	while(($res = $dbDriver->fetch($sth))) {
		array_push($statuses, array($res['issueid'] => $res['laytriggerstate']));
	}
	if($add === true){
		$row .= buildRowOfCombos( $user, $PublicationID, $IssueID, null, $statuses );
	}

	$sql = 'SELECT * FROM '.$mtptab.' WHERE `publid`= '.$PublicationID.' AND `issueid`= '.$IssueID;
	$sth = $dbDriver->query ($sql);
	$addnew = true;
	$configRows = array();
	while (($result = $dbDriver->fetch($sth))) {
		$configRows[] = $result;
	}
	$show = false;
	$view .= buildConfigView( $configRows, $user, $editlayout, $statuses, $show );
	$tosave = false;
	if($edit === true){
		$laytrigger = '';
		$tosave = true;
		$sql = 'SELECT * FROM '.$mtptab.' WHERE `publid`= '.$PublicationID.' AND `issueid`= '.$IssueID.' AND `laytriggerstate`= '.$editlayout;
		$sth = $dbDriver->query ($sql);
		$result = $dbDriver->fetch($sth);
		$row .= buildRowOfCombos( $user, $PublicationID, $IssueID, $result, null );
	}
	if($add === true || $tosave){
		$submit = '<input type="submit" name="savebutt" value="'.BizResources::localize('ACT_SAVE').'"/>&nbsp;&nbsp;';
		if($edit === true){
			$submit .= '<input type="hidden" name="edit" value="true"/>';
			$submit .= '<input type="hidden" name="editlayout" value="'.$editlayout.'"/>';
		}
	}else if($addnew){
		if( $show || empty($statuses) ){
			$submit = '<input type="submit" name="addbutt" value="'.BizResources::localize('ACT_ADD').'"/>&nbsp;&nbsp;';
		}
	}
}

$tpl = str_replace ("<!--ERROR_MSG-->",$error, $tpl);
$tpl = str_replace ("<!--ROWS-->", $row, $tpl);
$tpl = str_replace ("<!--VIEW-->", $view, $tpl);
$tpl = str_replace ("<!--SUBMIT-->", $submit, $tpl);
print HtmlDocument::buildDocument($tpl, true, '');

function buildRowOfCombos( $user, $PublicationID, $IssueID, $result, $statuses )
{
	$row = '';
	$lay_Input = '<select name="layout" tabindex="4">';
	$arrayOfState = BizWorkflow::getStates( $user, $PublicationID, $IssueID, null, 'Layout' );
	if ($arrayOfState) foreach ($arrayOfState as $state) {
		if( $state->Id != -1 ) { // ingore personal statuses
			if( $result ) {
				if( $state->Id == $result['laytriggerstate']){
					$lay_Input = $state->Name;
				}
			} else {
				$show = true;
				foreach( $statuses as /*$index =>*/ $isslayarr ) {
					foreach( $isslayarr as $issue => $lay ) {
						if( $lay == $state->Id && $IssueID == $issue ) {
							$show = false;
						}
					}
				}
				if($show === true) {
					$selected = $result && $state->Id == $result['laytriggerstate'] ? 'selected="selected"' : '';
					$lay_Input .= '<option value="'.$state->Id.'" '.$selected.'>'.formvar($state->Name).'</option>';
				}
			}
		}
	}
	$lay_Input .= "</select>";
	$row .= '<tr><td>|</td><td>'.$lay_Input.'</td>';

	$sta_Input = '<select name="state" tabindex="5">';
	$sta_Input .= '<option value="0">('.BizResources::localize('ACT_SELECT_ALL').')</option>';
	$arrayOfState = BizWorkflow::getStates( $user, $PublicationID, $IssueID, null, 'Article' );
	if ($arrayOfState) foreach ($arrayOfState as $state) {
		if( $state->Id != -1 ) { // ingore personal statuses
			$selected = $result && $state->Id == $result['arttriggerstate'] ? 'selected="selected"' : '';
			$sta_Input .= '<option value="'.$state->Id.'" '.$selected.'>'.formvar($state->Name).'</option>';
		}
	}
	$sta_Input .= "</select>";
	$row .= '<td>'.$sta_Input.'</td>';

	$img_Input = '<select name="image" tabindex="6">';
	$img_Input .= '<option value="0">('.BizResources::localize('ACT_SELECT_ALL').')</option>';
	$arrayOfState = BizWorkflow::getStates( $user, $PublicationID, $IssueID, null, 'Image' );
	if ($arrayOfState) foreach ($arrayOfState as $state) {
		if( $state->Id != -1 ) { // ingore personal statuses
			$selected = $result && $state->Id == $result['imgtriggerstate'] ? 'selected="selected"' : '';
			$img_Input .= '<option value="'.$state->Id.'" '.$selected.'>'.formvar($state->Name).'</option>';
		}
	}
	$img_Input .= "</select>";
	$row .= '<td>'.$img_Input.'</td>';
	$row .= '<td>|</td>';

	$lay_Input = '<select name="layoutafter" tabindex="7">';
	$lay_Input .= '<option value="0">('.BizResources::localize('LIC_CURRENT').')</option>';
	$arrayOfState = BizWorkflow::getStates( $user, $PublicationID, $IssueID, null, 'Layout' );
	if ($arrayOfState) foreach ($arrayOfState as $state) {
		if( $state->Id != -1 ) { // ingore personal statuses
			$selected = $result && $state->Id == $result['layprogstate'] ? 'selected="selected"' : '';
			$lay_Input .= '<option value="'.$state->Id.'" '.$selected.'>'.formvar($state->Name).'</option>';
		}
	}
	$lay_Input .= "</select>";
	$row .= '<td>'.$lay_Input.'</td>';

	$sta_Input = '<select name="stateafter" tabindex="8">';
	$sta_Input .= '<option value="0">('.BizResources::localize('LIC_CURRENT').')</option>';
	$arrayOfState = BizWorkflow::getStates( $user, $PublicationID, $IssueID, null, 'Article' );
	if ($arrayOfState) foreach ($arrayOfState as $state) {
		if( $state->Id != -1 ) { // ingore personal statuses
			$selected = $result && $state->Id == $result['artprogstate'] ? 'selected="selected"' : '';
			$sta_Input .= '<option value="'.$state->Id.'" '.$selected.'>'.formvar($state->Name).'</option>';
		}
	}
	$sta_Input .= "</select>";
	$row .= '<td>'.$sta_Input.'</td>';

	$img_Input = '<select name="imageafter" tabindex="9">';
	$img_Input .= '<option value="0">('.BizResources::localize('LIC_CURRENT').')</option>';
	$arrayOfState = BizWorkflow::getStates( $user, $PublicationID, $IssueID, null, 'Image' );
	if ($arrayOfState) foreach ($arrayOfState as $state) {
		if( $state->Id != -1 ) { // ingore personal statuses
			$selected = $result && $state->Id == $result['imgprogstate'] ? 'selected="selected"' : '';
			$img_Input .= '<option value="'.$state->Id.'" '.$selected.'>'.formvar($state->Name).'</option>';
		}
	}
	$img_Input .= "</select>";
	$row .= '<td>'.$img_Input.'</td>';

	$row .= '<td>|</td>';
	if( $result ) {
		$row .= '<td><input type="text" value="'.formvar($result['mtptext']).'" name="callas"/></td>';
	} else {
		$row .= '<td><input type="text" value="" name="callas"/></td>';
	}
	$row .= '<td>|</td></tr>';
	return $row;
}

/**
 * Builds and returns the MtP configuration table in view mode.
 *
 * @param array $configRows The rows from smart_mtp table to draw
 * @param string $user
 * @param int $editlayout The trigger layout status id being edit that must be skipped/ignored
 * @param boolean $show   Wether or not there there not all layout statuses are used and so the Add button needs to be drawn
 */
function buildConfigView( $configRows, $user, $editlayout, $statuses, &$show )
{
	$view = '';
	$laystatearray = array();
	foreach( $configRows as $result ) {
		$laytrigger = '';
		$layafter = '';
		$imgtrigger = '';
		$imgafter = '';
		$arttrigger = '';
		$artafter = '';
		$laytriggerid = 0;
		$toview = true;
		$arrayOfState = BizWorkflow::getStates( $user, $result['publid'], $result['issueid'], null, 'Article' );
		if ($arrayOfState) foreach ($arrayOfState as $state) {
			if( $state->Id != -1 ) { // ignore personal statuses
				if( $state->Id == $result['arttriggerstate']){
					$arttrigger = formvar($state->Name);
				}
				if( $state->Id == $result['artprogstate']){
					$artafter = formvar($state->Name);
				}
			}
		}
		$arrayOfState = BizWorkflow::getStates( $user, $result['publid'], $result['issueid'], null, 'Image' );
		if ($arrayOfState) foreach ($arrayOfState as $state) {
			if( $state->Id != -1 ) { // ignore personal statuses
				if( $state->Id == $result['imgtriggerstate']){
					$imgtrigger = formvar($state->Name);
				}
				if( $state->Id == $result['imgprogstate']){
					$imgafter = formvar($state->Name);
				}
			}
		}
		$arrayOfState = BizWorkflow::getStates( $user, $result['publid'], $result['issueid'], null, 'Layout' );
		if ($arrayOfState) foreach ($arrayOfState as $state) {
			if( $state->Id != -1 ) { // ignore personal statuses
				if($state->Id == $result['laytriggerstate'] && $state->Id == $editlayout){
					$toview = false;
				}
				if( $state->Id == $result['laytriggerstate']){
					$laytrigger = formvar($state->Name);
					$laytriggerid = $state->Id;
				}
				if( $state->Id == $result['layprogstate']){
					$layafter = formvar($state->Name);
				}
				$laystatearray[$state->Id] = 0;
				foreach($statuses as $isslayarr){
					foreach($isslayarr as $issue => $lay){
						if( $lay == $state->Id && $result['issueid'] == $issue ) {
							$laystatearray[$state->Id] = 1;
						}
					}
				}
			}
		}
		if($toview){
			// Draw row of settings for viewing (readonly)
			if( empty($arttrigger) ) $arttrigger = '<font color="#888888">('.BizResources::localize('ACT_SELECT_ALL').')</font>';
			if( empty($imgtrigger) ) $imgtrigger = '<font color="#888888">('.BizResources::localize('ACT_SELECT_ALL').')</font>';
			if( empty($layafter)   ) $layafter   = '<font color="#888888">('.BizResources::localize('LIC_CURRENT').')</font>';
			if( empty($artafter)   ) $artafter   = '<font color="#888888">('.BizResources::localize('LIC_CURRENT').')</font>';
			if( empty($imgafter)   ) $imgafter   = '<font color="#888888">('.BizResources::localize('LIC_CURRENT').')</font>';
			$mtpText = trim($result['mtptext']) == '' ? '<font color="#888888">('.MTP_JOB_NAME.')</font>' : formvar(trim($result['mtptext']));
			$view .= '<tr bgcolor="#DDDDDD"><td>|</td><td>'.$laytrigger.'</td><td>'.$arttrigger.'</td><td>'.$imgtrigger.'</td><td>|</td>'
					.'	<td>'.$layafter.'</td><td>'.$artafter.'</td><td>'.$imgafter.'</td><td>|</td><td>'.$mtpText.'</td><td>|</td>'
					.'	<td><a href="mtpsetup.php?del=true&pub='.$result['publid'].'&iss='.$result['issueid'].'&dellayout='.$laytriggerid.'">'
					.'		<img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize('ACT_DEL').'" /></a></td>'
					.'	<td><a href="mtpsetup.php?edit=true&pub='.$result['publid'].'&iss='.$result['issueid'].'&editlayout='.$laytriggerid.'">'
					.'		<img src="../../config/images/prefs_16.gif" border="0" title="'.BizResources::localize('ACT_EDIT').'" /></a></td>'
					.'</tr>';
		}
	}
	$show = false;
	foreach($laystatearray as $present){
		if($present == 0){
			$show = true;
		}
	}
	$show = $show || empty($laystatearray);
	return $view;
}

/**
 * Checks all MadeToPrint options at the configserver.php file.
 * This check is not done at wwtest since the MadeToPrint integrations is optional.
 * Nevertheless, it needs to be checked when using MadeToPrint.
 *
 * @return string Error message or empty when no error.
 */
function testMtpConfigServer()
{
	// The application server name determines if MtP is enabled or not.
	if( trim(MTP_SERVER_DEF_ID) == '' ) {
		return 'MadeToPrint is disabled. Set options at configserver.php to enable. The MTP_SERVER_DEF_ID option tells if MadeToPrint is enabled or not.';
	}

	// Check if in/out folders are configured correctly and accessable	
	if( trim(MTP_SERVER_FOLDER_IN) == '' ) {
		return 'No MadeToPrint in-folder specified. Please check MTP_SERVER_FOLDER_IN option at configserver.php.';
	}
	if( strrpos(MTP_SERVER_FOLDER_IN,'/') != (strlen(MTP_SERVER_FOLDER_IN)-1) ) {
		return 'The specified MadeToPrint in-folder has no slash (/) at the end. Please check MTP_SERVER_FOLDER_IN option at configserver.php.';
	}
	if( !is_dir(MTP_SERVER_FOLDER_IN) ) {
		return 'The specified MadeToPrint in-folder does not exist at file system. Please check file system and MTP_SERVER_FOLDER_IN option at configserver.php.';
	}
	if(!is_writable(MTP_SERVER_FOLDER_IN)){
		return 'No write access to specified MadeToPrint in-folder. Please check file system and MTP_SERVER_FOLDER_IN option at configserver.php.';
	}
	
	if( trim(MTP_CALLAS_FOLDER_IN) == '' ) {
		return 'No MadeToPrint in-folder specified. Please check MTP_CALLAS_FOLDER_IN option at configserver.php.';
	}
	if( strrpos(MTP_CALLAS_FOLDER_IN,'/') != (strlen(MTP_CALLAS_FOLDER_IN)-1) ) {
		return 'The specified MadeToPrint in-folder has no slash (/) at the end. Please check MTP_CALLAS_FOLDER_IN option at configserver.php.';
	}
	
	if( trim(MTP_CALLAS_FOLDER_OUT) == '' ) {
		return 'No MadeToPrint out-folder specified. Please check MTP_CALLAS_FOLDER_OUT option at configserver.php.';
	}
	if( strrpos(MTP_CALLAS_FOLDER_OUT,'/') != (strlen(MTP_CALLAS_FOLDER_OUT)-1) ) {
		return 'The specified MadeToPrint out-folder has no slash (/) at the end. Please check MTP_CALLAS_FOLDER_IN option at configserver.php.';
	}

	// Check if post process is configured and can be ping-ed
	if( trim(MTP_POSTPROCESS_LOC) == '' ) {
		return 'No MadeToPrint post process specified. Please check MTP_POSTPROCESS_LOC option at configserver.php.';
	}
	$urlParts = @parse_url( MTP_POSTPROCESS_LOC );
	if( !$urlParts || !isset($urlParts["host"]) ) {
		return 'The specified MadeToPrint post process is not valid. Please check MTP_POSTPROCESS_LOC option at configserver.php.';
	}
	$host = $urlParts["host"];
	$port = isset($urlParts["port"]) ? $urlParts["port"] : 80;
	$errno = 0;
	$errstr = '';
	$socket = @fsockopen( $host, $port, $errno, $errstr, 5 );
	if( !$socket ) {
		return 'The specified MadeToPrint post process is not responsive ('.$errstr.'). Please check MTP_POSTPROCESS_LOC option at configserver.php.';
	}
	fclose( $socket );

	// Check if the MtP user is configured and try to logon/logoff 
	if( trim(MTP_USER) == '' ) {
		return 'No MadeToPrint user name specified. Please check MTP_USER option at configserver.php.';
	}
	if( trim(MTP_PASSWORD) == '' ) {
		return 'No MadeToPrint user password specified. Please check MTP_PASSWORD option at configserver.php.';
	}
	require_once BASEDIR.'/server/protocols/soap/WflClient.php';
	$client = new WW_SOAP_WflClient();
	try {
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$req = new WflLogOnRequest( MTP_USER, MTP_PASSWORD, null, null, '', null, 'MtP test', SERVERVERSION, null, null, true );
		$logOnResp = $client->LogOn( $req );
		$ticket = $logOnResp->Ticket;
	} catch( BizException $e ) {
		return 'Failed to logon the configured MadeToPrint user. Please check MTP_USER and MTP_PASSWORD options at configserver.php.'.
				'Error returned from server: '.$e->getMessage();
	}
	try {
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		$req = new WflLogOffRequest( $ticket, null, null, null );
		/* $logOffResp = */ $client->LogOff( $req );
	} catch( BizException $e ) {
		return 'Failed to logoff the configured MadeToPrint user. Please check MTP_USER and MTP_PASSWORD options at configserver.php.'.
				'Error returned from server: '.$e->getMessage();
	}
	
	return '';
}

/**
 * Detects if there are issues configured that are n longer supported.
 * This is when the issue has Overrule Publication flag disabled or when issue is in non-print channel.
 * In other terms, only print-issues and overrule-issues are configurable (and so they are listed at issue combo).
 * Print-issues with overrule flag disabled are configurable at publication level ("Select All" item).
 * This all implies that you can configure MtP only for entire workflow definitions at SCE.
 *
 * @param array $badIssueIds Returns the issues that are not supported (key=id, value=name)
 * @return string Error report (html) when there are unsupported (bad) issues found. Empty when all ok.
 */
function detectBadIssues( &$badIssueIds )
{
	$error = '';
	$dbDriver = DBDriverFactory::gen();
	$mtpTab = $dbDriver->tablename('mtp');
	$pubTab = $dbDriver->tablename('publications');
	$issTab = $dbDriver->tablename('issues');
	$chnTab = $dbDriver->tablename('channels');

	$sql = "SELECT iss.`name`, iss.`id`, pub.`publication` FROM $mtpTab mtp ".
			"LEFT JOIN $issTab iss ON ( iss.`id` = mtp.`issueid` ) ".
			"LEFT JOIN $chnTab chn ON ( chn.`id` = iss.`channelid` ) ".
			"LEFT JOIN $pubTab pub ON ( pub.`id` = chn.`publicationid` ) ".
			"WHERE iss.`overrulepub` <> 'on' OR chn.`type` <> 'print' ";
	$sth = $dbDriver->query ($sql);
	$configRows = array();
	while (($result = $dbDriver->fetch($sth))) {
		$configRows[] = $result;
	}
	if( count( $configRows ) > 0 ) {
		$error = 'There are configurations made for unsupported issue types. '.
			'Issues for non-print channels or issues with disabled Overrule Publication flag are not supported.'.
			'Please remove the MadeToPrint configurations for the following issues since they disturb the production process: <ul>';
		foreach( $configRows as $row ) {
			$error .= '<li>- '.formvar($row['name']).' (Publication: '.formvar($row['publication']).')'.'</li>';
			$badIssueIds[$row['id']] = $row['name'];
		}
		$error .= '</ul>';
	}

	return $error;
}