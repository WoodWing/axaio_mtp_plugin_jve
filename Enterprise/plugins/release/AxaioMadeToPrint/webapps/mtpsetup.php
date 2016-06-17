<?php
if( file_exists('../../../../config/config.php') ) {
	require_once '../../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../../Enterprise/config/config.php';
}
require_once dirname(__FILE__).'/../config.php';
require_once dirname(__FILE__).'/AxaioMadeToPrint_AxaioMadeToPrint_EnterpriseWebApp.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // inputvar(), formvar()
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
// Special debug/test option:
//    on = production/safe mode, showing only overrule issues and print channel issues
//    off = debug/test mode, showing all issues
if( !defined( 'AXAIO_MTP_ISSUE_FILTER' ) ) {
    define( 'AXAIO_MTP_ISSUE_FILTER', 'off' ); 
}

$ticket = checkSecure('publadmin');
global $globUser;  // set by checkSecure()
$user = $globUser;
$tpl = file_get_contents(dirname(__FILE__).'/mtpsetup.htm' );

$dbDriver = DBDriverFactory::gen();
$mtptab = "axaio_mtp_trigger";
$statustab = $dbDriver->tablename('states');

$PublicationID  = isset($_REQUEST['Publication']) ? intval($_REQUEST['Publication'])  : 0;
$IssueID        = isset($_REQUEST['Issue'])       ? intval($_REQUEST['Issue'])        : 0;
$add            = isset($_REQUEST['act_add'])     ? (bool)trim($_REQUEST['act_add'])  : false;
$save           = isset($_REQUEST['act_save'])    ? (bool)trim($_REQUEST['act_save']) : false;
$cancel         = isset($_REQUEST['act_cancel'])  ? (bool)trim($_REQUEST['act_cancel']):false;

$layoutbefore   = isset($_REQUEST['layout'])      ? intval($_REQUEST['layout'])       : 0;
$layoutafter    = isset($_REQUEST['layoutafter']) ? intval($_REQUEST['layoutafter'])  : 0;
$layouterror    = isset($_REQUEST['layouterror']) ? intval($_REQUEST['layouterror'])  : 0;
$imagebefore    = isset($_REQUEST['image'])       ? intval($_REQUEST['image'])        : 0;
$imageafter     = isset($_REQUEST['imageafter'])  ? intval($_REQUEST['imageafter'])   : 0;
$articlebefore  = isset($_REQUEST['state'])       ? intval($_REQUEST['state'])        : 0;
$articleafter   = isset($_REQUEST['stateafter'])  ? intval($_REQUEST['stateafter'])   : 0;

$mtp_jobname    = isset($_POST['mtp_jobname'])    ? trim($_POST['mtp_jobname'])       : '';
$delete         = isset($_REQUEST['act_del'])     ? (bool)trim($_REQUEST['act_del'])  : false;
$edit           = isset($_REQUEST['act_edit'])    ? (bool)trim($_REQUEST['act_edit']) : false;
$iss            = isset($_REQUEST['iss'])         ? intval($_REQUEST['iss'])          : 0;
$pub            = isset($_REQUEST['pub'])         ? intval($_REQUEST['pub'])          : 0;
$dellayout      = isset($_REQUEST['dellayout'])   ? intval($_REQUEST['dellayout'])    : 0;
$editlayout     = isset($_REQUEST['editlayout'])  ? intval($_REQUEST['editlayout'])   : 0;
$quietmode      = isset($_REQUEST['quietmode'])   ? intval($_REQUEST['quietmode'])    : 0;
$priority       = isset($_REQUEST['priority'])    ? intval($_REQUEST['priority'])     : 2; // lowest: 0; highest: 4

// Check the MtP configuration
$error = "";//AxaioMadeToPrint_AxaioMadeToPrint_EnterpriseWebApp::testMtpConfigServer();

if( !$error ) {
    // Checks if db has obsoleted data structures, typically from migrated databases
    $badIssueIds = array();
    $error .= AxaioMadeToPrint_AxaioMadeToPrint_EnterpriseWebApp::detectBadIssues( $badIssueIds );
    //echo print_r($badIssueIds,true);
}

// case save
if($save === true){
    if( trim(AXAIO_MTP_JOB_NAME) == '' && trim($mtp_jobname) == '' ) {
        // Error when *both* global job name and specific job name are empty.
        $error .= BizResources::localize('ERR_NOT_EMPTY');
        $pub = $PublicationID;
        $iss = $IssueID;
    } else {
        if($edit === true){
            $sql = "update {$mtptab} "
                 . " set "
                 . "    `state_trigger_article`={$articlebefore}, "
                 . "    `state_trigger_image`= {$imagebefore}, "
                 . "    `state_after_layout`={$layoutafter}, "
                 . "    `state_after_article`={$articleafter}, "
                 . "    `state_after_image`={$imageafter}, "
                 . "    `state_error_layout`={$layouterror}, "
                 . "    `mtp_jobname`='".$dbDriver->toDBString($mtp_jobname)."', "
                 . "    `quiet`={$quietmode}, "
                 . "    `prio`={$priority} "
                 . " where "
                 . "    `publication_id`={$PublicationID} and "
                 . "    `issue_id`={$IssueID} and "
                 . "    `state_trigger_layout`={$editlayout};";

            $retval = $dbDriver->query ($sql);
            $edit = false;
            $editlayout = 0;
        }else{
            $sql = "insert into {$mtptab} ("
                 . "    `publication_id`, `issue_id`, "
                 . "    `state_trigger_layout`, `state_trigger_article`, `state_trigger_image`, "
                 . "    `state_after_layout`,   `state_after_article`,   `state_after_image`, "
                 . "    `state_error_layout`, "
                 . "    `mtp_jobname`, "
                 . "    `quiet`, `prio` "
                 . ") values ("
                 . "    {$PublicationID}, {$IssueID}, "
                 . "    {$layoutbefore}, {$articlebefore}, {$imagebefore}, "
                 . "    {$layoutafter},  {$articleafter},  {$imageafter}, "
                 . "    {$layouterror}, "
                 . "    '" . $dbDriver->toDBString($mtp_jobname) . "', "
                 . "    {$quietmode}, {$priority} "
                 . ");";
            
            $retval = $dbDriver->query ($sql);
        }
        header('Location: '.INETROOT."/config/plugins/AxaioMadeToPrint/webapps/mtpsetup.php?Publication={$PublicationID}&Issue={$IssueID}");
    }
}

//case delete
if($delete) {
    $sql = "delete from {$mtptab} where `publication_id`={$pub} and `issue_id`={$iss} and `state_trigger_layout`={$dellayout};";
    $retval = $dbDriver->query ($sql);
    $PublicationID = $pub;
    $IssueID = $iss;
    
    header('Location: '.INETROOT."/config/plugins/AxaioMadeToPrint/webapps/mtpsetup.php?Publication={$PublicationID}&Issue={$IssueID}");
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
        $sql = 'select `type` from '.$chanTab.' where `id`='.$chanId;
        $sth = $dbDriver->query ($sql);
        $row = $dbDriver->fetch( $sth );
        if( $row['type'] == 'print' || AXAIO_MTP_ISSUE_FILTER == 'off' || isset($badIssueIds[$iss->Id]) ) {
            $printIssues[] = $iss;
        }
    }	
    if( count($printIssues) > 0 || AXAIO_MTP_ISSUE_FILTER == 'off') {

        // Mark publication issues ('Select All' entry) if there are MtP configurations (to ease admin users to look up)
        $sql = 'select count(*) as `CfgCount` from '.$mtptab.' where `publication_id`='.$PublicationID.' and `issue_id`=0';
        $sth = $dbDriver->query ($sql);
        $row = $dbDriver->fetch( $sth );
        $issueMark = $row && isset($row['CfgCount']) && $row['CfgCount'] > 0 ? '*' : '';

        foreach( $printIssues as $iss ) {
            // Add 'select all' entry when there are publication issues (not only overrule issues)
            if( !$iss->OverrulePublication || AXAIO_MTP_ISSUE_FILTER == 'off' ) {
                $comboBoxIss .= '<option value="">({{ACT_SELECT_ALL}}'.$issueMark.')</option>';
                break;
            }
        }
        foreach( $printIssues as $iss ) {
            // We only support *one* level of MtP configitations per workflow.
            // So that is at publication level (= all its issues) OR at overruled issue level (= 1 issue).
            // That is why we skip publication issues and allow overruled issues here.
            if( $iss->OverrulePublication || AXAIO_MTP_ISSUE_FILTER == 'off' || isset($badIssueIds[$iss->Id]) ) { 

                // Mark overrule issues that have MtP configuration (to ease admin users to look up)
                $sql = 'select count(*) as `CfgCount` from '.$mtptab.' where `publication_id`='.$PublicationID.' and `issue_id`='.$iss->Id;
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

$submit = '';
$row = '';
$view = '';

/////////////////// ROWS/VIEW ///////////////////
if($PublicationID > 0){
    // Draw table header
    $row .= '<tr>';
    $row .= '<th>|</th><th colspan="3">{{BEFORE}}</th>';
    $row .= '<th>|</th><th colspan="3">{{AFTER}}</th>';
    $row .= '<th>|</th><th colspan="1">{{ERROR}}</th>';
    $row .= '<th>|</th><th colspan="3">Axaio</th>';
    $row .= '<th>|</th><th colspan="2">&nbsp;</th>';
    // new row
    $row .= '</tr><tr>';
    $row .= '<th>|</th><th>{{OBJ_STATUS_LAYOUTS}}</th><th>{{OBJ_STATUS_ARTICLES}}</th><th>{{OBJ_STATUS_IMAGES}}</th>';
    $row .= '<th>|</th><th>{{OBJ_STATUS_LAYOUTS}}</th><th>{{OBJ_STATUS_ARTICLES}}</th><th>{{OBJ_STATUS_IMAGES}}</th>';
    $row .= '<th>|</th><th>{{OBJ_STATUS_LAYOUTS}}</th>';
    $row .= '<th>|</th><th>{{MTP_SETT}}</th><th>{{QUIET}}</th><th>{{WFL_PRIORITY}}</th>';
    $row .= '<th>|</th><th>{{ACT_EDIT}}</th>';
    // end header
    $row .= '</tr>';

    $statuses = array();
    $sql = 'select `issue_id`, `state_trigger_layout` from '.$mtptab.' where `publication_id`='.$PublicationID;
            $sth = $dbDriver->query($sql);
            while(($res = $dbDriver->fetch($sth))) {
                array_push($statuses, array($res['issue_id'] => $res['state_trigger_layout']));
            }
            if($add === true){
                $row .= AxaioMadeToPrint_AxaioMadeToPrint_EnterpriseWebApp::buildRowOfCombos( $user, $PublicationID, $IssueID, null, $statuses );
            }

    //$sql = 'select * from '.$mtptab.' where `publication_id`='.$PublicationID.' and `issue_id`='.$IssueID;
    $sql = "SELECT mtp.*
            FROM {$mtptab} mtp
            INNER JOIN {$statustab} sta
            ON sta.`id`=mtp.`state_trigger_layout`
            where mtp.`publication_id`={$PublicationID} and mtp.`issue_id`={$IssueID} 
            ORDER BY sta.`code`;
            ";
    $sth = $dbDriver->query ($sql);
    $addnew = true;
    $configRows = array();
    while (($result = $dbDriver->fetch($sth))) {
        $configRows[] = $result;
    }
    $show = false;
    $view .= AxaioMadeToPrint_AxaioMadeToPrint_EnterpriseWebApp::buildConfigView( $configRows, $user, $editlayout, $statuses, $show );
    $tosave = false;
    if($edit === true){
        $laytrigger = '';
        $tosave = true;
        //$sql = 'select * from '.$mtptab.' where `publication_id`='.$PublicationID.' and `issue_id`='.$IssueID.' and `state_trigger_layout`='.$editlayout;
        $sql = "SELECT mtp.*
                FROM {$mtptab} mtp
                INNER JOIN {$statustab} sta
                ON sta.`id`=mtp.`state_trigger_layout`
                where mtp.`publication_id`={$PublicationID} and mtp.`issue_id`=$IssueID and mtp.`state_trigger_layout`=$editlayout
                ORDER BY sta.`code`;
                ";
        
        $sth = $dbDriver->query ($sql);
        $result = $dbDriver->fetch($sth);
        $row .= AxaioMadeToPrint_AxaioMadeToPrint_EnterpriseWebApp::buildRowOfCombos( $user, $PublicationID, $IssueID, $result, null );
    }
    if($add === true || $tosave){
        $submit = '<input type="submit" name="act_save" value="{{ACT_SAVE}}"/>&nbsp;&nbsp;';
        $submit .= '<input type="submit" name="act_cancel" value="{{ACT_CANCEL}}" onClick="return confirm(\'{{ACT_QUIT_LOSING_CHANGES}}\');"/>&nbsp;&nbsp;';
        if($edit === true){
            $submit .= '<input type="hidden" name="act_edit" value="true"/>';
            $submit .= '<input type="hidden" name="editlayout" value="'.$editlayout.'"/>';
        }
    }else if($addnew){
        if( $show || empty($statuses) ){
            $submit = '<input type="submit" name="act_add" value="{{ACT_ADD}}"/>&nbsp;&nbsp;';
        }
    }
}

// replace variables in template
$tpl = str_replace ("<!--ERROR_MSG-->",$error, $tpl);
$tpl = str_replace ("<!--ROWS-->", $row, $tpl);
$tpl = str_replace ("<!--VIEW-->", $view, $tpl);
$tpl = str_replace ("<!--SUBMIT-->", $submit, $tpl);
$tpl = str_replace ("<!--RES:PUBLICATION-->", BizResources::localize('PUBLICATION'), $tpl);
$tpl = str_replace ("<!--PUB_INPUT-->", $pub_Input, $tpl);
$tpl = str_replace ("<!--RES:ISSUE-->", BizResources::localize('ISSUE'), $tpl);
$tpl = str_replace ("<!--ISS_INPUT-->", $comboBoxIss, $tpl);

$tpl = AxaioMadeToPrintResource::tr($tpl, BizSession::getUserLanguage());

// output template
echo $tpl;