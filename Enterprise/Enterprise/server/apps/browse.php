<?php
/**
 * @deprecated 10.2.0 Code can be removed without further notice.
 */
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/apps/browse_inc.php';
require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
require_once BASEDIR.'/server/bizclasses/PubMgr.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';

$ticket = checkSecure();
global $globUser;  // set by checkSecure()

// application dependent
webauthorization( BizAccessFeatureProfiles::ACCESS_QUERY_BROWSE );
$tpl = HtmlDocument::loadTemplate( 'browse.htm' );

$objmap = getObjectTypeMap();
asort($objmap);

$inPub    = isset($_POST['Publication']) ? intval($_POST['Publication']) : 0;
$inIssue  = isset($_POST['Issue'])    ? intval($_POST['Issue'])    : 0;
$inSection= isset($_POST['Section'])  ? intval($_POST['Section'])  : 0;
$inState  = isset($_POST['State'])    ? intval($_POST['State'])    : 0;
$Name     = isset($_POST['Name'])     ? $_POST['Name']     : '';
$Type     = isset($_POST['Type'])     ? $_POST['Type']     : '';
if( !array_key_exists($Type, $objmap) ) $Type = '';

$Modified = isset($_POST['Modified']) ? $_POST['Modified'] : '';
$Creator  = isset($_POST['Creator'])  ? $_POST['Creator']  : '';
$RouteTo  = isset($_POST['RouteTo'])  ? $_POST['RouteTo']  : '';
$Created  = isset($_POST['Created'])  ? $_POST['Created']  : '';
$Modifier = isset($_POST['Modifier']) ? $_POST['Modifier'] : '';
$Lockedby = isset($_POST['Locked'])   ? $_POST['Locked']   : '';
$Content  = isset($_POST['Content'])  ? $_POST['Content']  : '';

$deadlinefrom_date = isset($_REQUEST['deadlinefrom_date']) ? $_REQUEST['deadlinefrom_date'] : '';
$deadlinefrom_time = isset($_REQUEST['deadlinefrom_time']) ? $_REQUEST['deadlinefrom_time'] : '';
$deadlinefrom      = $deadlinefrom_date . ' ' . $deadlinefrom_time;
$deadlinetill_date = isset($_REQUEST['deadlinetill_date']) ? $_REQUEST['deadlinetill_date'] : '';
$deadlinetill_time = isset($_REQUEST['deadlinetill_time']) ? $_REQUEST['deadlinetill_time'] : '';
$deadlinetill      = $deadlinetill_date . ' ' . $deadlinetill_time;

$emptySearch       = isset($_REQUEST['emptySearch']) ? $_REQUEST['emptySearch'] : '';
$newStartPos       = isset($_REQUEST['startPos']) ? $_REQUEST['startPos']: '';
$Number_of_Results = isset($_REQUEST['numRes']) ? $_REQUEST['numRes'] : '';

$Thumbnail_view    = isset($_REQUEST['Thumbnail']) ? $_REQUEST['Thumbnail']: '';
$HierarchialView   = isset($_REQUEST['HierarchialView']) ? $_REQUEST['HierarchialView'] : '';
$nQuery            = isset($_REQUEST['nq']) ? $_REQUEST['nq'] : '';

$sort              = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
$ord               = isset($_REQUEST['ord']) ? $_REQUEST['ord'] : '';

// Cookie function can hold 7 values, so use a few cookies:
$dum='';
cookieMonster( "browsertt", $inPub == '',
				$inPub,		$inIssue,	$inSection,	$inState,	$Name,				$Type,			 $Modified,			$Creator,	$RouteTo,
				$Created, 	$Modifier,	$Lockedby, 	$Content, 	$Number_of_Results, $Thumbnail_view, $HierarchialView, 	$sort, $ord, $dum, $dum );

// use of cookieMonster is insecure, parse the same variables as at the beginning
$inPub    = intval($inPub);
$inIssue  = intval($inIssue);
$inSection= intval($inSection);
$inState  = intval($inState);
if( !array_key_exists($Type, $objmap) ) $Type = '';

if( empty( $newStartPos ) || !is_numeric( $newStartPos ) ) $newStartPos = 1;
if( empty($Number_of_Results) ) $Number_of_Results = 20;
$sort     = empty($sort) ? "desc" : $sort; // asc(ending) or desc(ending) sort; default desc(ending)
$ord      = empty($ord) ? "Modified" : $ord; // property to sort on; default: Modified

// $delID and $UnlockID can have multiple ids e.g. "3,6,8"
$delID    = isset($_POST['del']) ? $_POST['del'] : '';
$UnlockID = isset($_POST['unlock']) ? $_POST['unlock'] : '';

$namedQueries = array();
$aantal =0;
$interface ="";
$namedQuery="";
$dateError = "";

try{
	$nqs = BizNamedQuery::getNamedQueries();
} catch( BizException $e ) {
}

$comboBoxNamesQueries = '<select name="nq" style="width:135px" onchange="submit();">'."\n";
$comboBoxNamesQueries .= '<option value="">'.BizResources::localize('LIS_NONE').'</option>'."\n";
$comboBoxNamesQueries .= '<option value="Browse" selected="selected">'.BizResources::localize('ACT_BROWSE').'</option>'."\n";

foreach ($nqs as $nq) {
	$namedQuery = $nq->Name;
	if($namedQuery != $nQuery) {
		$comboBoxNamesQueries .= '<option value="'.formvar($namedQuery).'">'.formvar($namedQuery).'</option>'."\n";
	} else {
		$comboBoxNamesQueries .= '<option value="'.formvar($namedQuery).'" selected="selected">'.formvar($namedQuery).'</option>'."\n";
	}
	if($nQuery == $namedQuery) {
		header( 'Location: nqbrowse.php?nq='.$nQuery.'&Thumbnail='.$Thumbnail_view );
		exit();
	}
}
$comboBoxNamesQueries .= "</select>";
$tpl = str_replace ("<!--NAMEDQUERIES-->",$comboBoxNamesQueries, $tpl);

/////////////////////// *** "Delete Selected" command *** ///////////////////////
$succeed = true;
$message = "";
if(!empty($delID)){
	$dds=explode(",",$delID);
	if(count($dds)>1)
	{
		array_pop($dds);
	}
	for($counter=0;$counter<count($dds);$counter++)
	{
		$ids = array($dds[$counter]); // can be alien object id (=string!)
		$permanent = false;
		if (!empty($ids)) {
			try {
				require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
				$service = new WflDeleteObjectsService();
				$deleteObjectsReq = new WflDeleteObjectsRequest();
				$deleteObjectsReq->Ticket = $ticket;
				$deleteObjectsReq->IDs = $ids;
				$deleteObjectsReq->Permanent = $permanent;
				$resp = $service->execute( $deleteObjectsReq );
				
				if( $resp->Reports ) { // Introduced since v8.0
					$message = '';
					$succeed = false;
					foreach( $resp->Reports as $report ) {
						foreach( $report->Entries as $reportEntry ) {
							$message .= $reportEntry->Message . PHP_EOL;
						}
					}
				}
			} catch( BizException $e ) {
				$message = $e->getMessage();
				$succeed = false;
			}
		}
	}
	$delID="";
}

/////////////////////// *** "Unlock Selected" command *** ///////////////////////
if(!empty($UnlockID)){
	$uids=explode(",",$UnlockID);
	if(count($uids)>1) {
		array_pop($uids);
	}

	require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
	try {
		$unlockReq = new WflUnlockObjectsRequest( $ticket, $uids, null );
		$unlockService = new WflUnlockObjectsService();
		$unlockService->execute( $unlockReq );
	} catch (BizException $e ) {
		$message = $e->getMessage();
		$succeed = false;
	}
	unset( $UnlockID );
}

 /////////////////////// *** Publication combo *** ///////////////////////
 	$comboBoxPub = '<select name="Publication" style="width:135px" onchange="javascript:NewPublication();">'."\n";
	$arrayOfPublications = PubMgr::getPublications($globUser,false, false );
	if( !$inPub && !empty($arrayOfPublications) )
		$inPub = $arrayOfPublications[0]->Id;
	if ($arrayOfPublications) foreach ($arrayOfPublications as $pub) {
		$pubID = $pub->Id;
		$pubName = $pub->Name;
		if($pubID!= $inPub) {
			$comboBoxPub .= '<option value="'.$pubID.'">'.formvar($pubName).'</option>'."\n";
		} else {
			$comboBoxPub .= '<option value="'.$pubID.'" selected="selected">'.formvar($pubName).'</option>'."\n";
		}
	}
	$comboBoxPub .= '</select>'."\n";
	$tpl = str_replace ('<!--COMBOPUB-->',$comboBoxPub, $tpl);

 /////////////////////// *** Issue combo *** ///////////////////////
 	$comboBoxIss = '';
 	$comboBoxIss = startComboBox($comboBoxIss, 'Issue');
	$comboBoxIss = addAllToComboBox($comboBoxIss);

	// List items for the combo box
	$arrayOfIssues = PubMgr::getIssues( $globUser, $inPub, false, false );
	$pcn_issues = getListOfPrevCurrNextIssues($inPub, $arrayOfIssues);

	// Before finishing the combo box the selected item has to be set
	if (!selectedItemInList($inIssue, $arrayOfIssues, $pcn_issues)) {
		$inIssue = defaultSelectedIssue($pcn_issues, $arrayOfIssues);
	}

	// Add items to the combo box
	$comboBoxIss = addPrevCurrNextToComboBoxIss($comboBoxIss, $pcn_issues, $inIssue);
	$comboBoxIss = addListOfIssuesToComboBoxIss($comboBoxIss, $arrayOfIssues, $inIssue);
	$comboBoxIss = EndComboBox($comboBoxIss);

	$tpl = str_replace ('<!--COMBOISS-->',$comboBoxIss, $tpl);

	$selectedIssueID = getSelectedIssue($inIssue, $pcn_issues);

 /////////////////////// *** Section combo *** ///////////////////////
	$comboBoxSec = '<select name="Section" style="width:135px" onchange="javascript:EmptySearch();">'."\n";
	$comboBoxSec .= '<option value="-1">'.BizResources::localize('ACT_SELECT_ALL').'</option>'."\n";

	$arrayOfSections = PubMgr::getSections( $globUser, $inPub, $inIssue != -1 ? $selectedIssueID : null, false, false );
	// If we have a value to select, check if it does exist in list of sections
	if( $inSection && $inSection != -1 && !empty($arrayOfSections) ) {
		$foundSection = false;
		foreach ($arrayOfSections as $section) {
			if( $section->Id == $inSection ) $foundSection = true;
		}
		// Not found, clear $inSection
		if( !$foundSection ) $inSection=0;
	}
	if( !empty($arrayOfSections) && !$inSection ) {
		$inSection = $arrayOfSections[0]->Id;
	}
	if ($arrayOfSections) foreach ($arrayOfSections as $section) {
		$secID = $section->Id;
		$secName = $section->Name;
		if( $secID != $inSection ) {
			$comboBoxSec .= '<option value="'.$secID.'">'.formvar($secName).'</option>'."\n";
		} else {
			$comboBoxSec .= '<option value="'.$secID.'" selected="selected">'.formvar($secName).'</option>'."\n";
		}
	}
	$comboBoxSec .= '</select>'."\n";
	$tpl = str_replace ('<!--COMBOSEC-->',$comboBoxSec, $tpl);

 /////////////////////// *** State combo *** ///////////////////////
	$comboBoxSta = '<select name="State" style="width:135px" onchange="javascript:RefinedSearch();">'."\n";
	$comboBoxSta .= '<option value="-1">'.BizResources::localize('ACT_SELECT_ALL').'</option>'."\n";

	$arrayOfStates = PubMgr::getStates( $globUser, $inPub, $inIssue != -1 ? $selectedIssueID : null, null, false );
	$seenstates = array();
	if ($arrayOfStates) foreach ($arrayOfStates as $state) {
		$stateID = $state->Id == -1 ? -2 : $state->Id; //As allstates and personal state both are defined as -1, the fix: make personal state temporarily -2.
		$stateName = $state->Name;

		if(in_array($stateID, $seenstates)){
			$skip = true;
		}
		else $skip = false;
		array_push($seenstates, $stateID);
		if($skip === true){
		}else{
			if( $stateID != $inState ){
				$comboBoxSta .= '<option value="'.$stateID.'">'.formvar($stateName).'</option>'."\n";
			}else{
				$comboBoxSta .= '<option value="'.$stateID.'" selected="selected">'.formvar($stateName).'</option>'."\n";
			}
		}
	}
	$comboBoxSta .= '</select>'."\n";
	$tpl = str_replace ('<!--COMBOSTA-->',$comboBoxSta, $tpl);

/////////////////////// *** Show more query params *** ///////////////////////
	// Object Type combo
	$OBJECT_Type = '<select name="Type" style="width:110px">'."\n";
	$OBJECT_Type .= '<option selected="selected" value="-1"></option>'."\n";
	foreach ($objmap as $k => $sDisplayType) {
		if( !empty($Type) && $k == $Type ) {
			$OBJECT_Type .= '<option value="'.formvar($k).'" selected="selected">'.formvar($objmap[$k]).'</option>'."\n";
		} else {
			$OBJECT_Type .= '<option value="'.formvar($k).'">'.formvar($objmap[$k]).'</option>'."\n";
		}
	}
	$OBJECT_Type .= '</select>'."\n";
	$tpl = str_replace ("<!--TYPE-->",$OBJECT_Type, $tpl);

	$Object_Modifier = '<input style="width: 110px;" type="text" name="Modifier" value="'.formvar($Modifier).'"/>';
	$tpl = str_replace ('<!--MODIFIER-->',$Object_Modifier, $tpl);
	
	$Object_Name = '<input  style="width: 110px;"type="text" name="Name" value="'.formvar($Name).'"/>';
	$tpl = str_replace ('<!--NAME-->',$Object_Name, $tpl);

	$Object_Modified = inputvar('Modified', formvar($Modified), 'date', null, true);	
	$tpl = str_replace ('<!--MODIFIED-->',$Object_Modified, $tpl);

	$Object_RouteTo = '<input style="width: 110px;" type="text" name="RouteTo" value="'.formvar($RouteTo).'"/>';
	$tpl = str_replace ('<!--ROUTETO-->',$Object_RouteTo, $tpl);

	$Object_Creator = '<input style="width: 110px;" type="text" name="Creator" value="'.formvar($Creator).'"/>';
	$tpl = str_replace ('<!--CREATOR-->',$Object_Creator, $tpl);

	$Object_Content = '<input style="width: 110px;" type="text" name="Content" value="'.formvar($Content).'"/>';
	$tpl = str_replace ('<!--CONTENT-->',$Object_Content, $tpl);

	$Object_Created = inputvar('Created', formvar($Created), 'date', null, true);
	$tpl = str_replace ('<!--CREATED-->',$Object_Created, $tpl);

	//$tmp_deadlinefrom = "<input type=text name=deadlinefrom value='$deadlinefrom'>";
	$dategif = '../../config/images/cal_16.gif';

	$isofrom = DateTimeFunctions::validDate($deadlinefrom);
	$isoarray = DateTimeFunctions::iso2dateArray( $isofrom );
	if ($isoarray) {
		$deadlinefrom_date = $isoarray['mday'] . '-' . $isoarray['mon'] . '-' . $isoarray['year'];
		$deadlinefrom_time = $isoarray['hours'] . ':' . $isoarray['minutes'];
	}
	else {
		$deadlinefrom_date = '';
		$deadlinefrom_time = '';
	}
	$tmp_deadlinefrom  = '';

	$langpatdate = LANGPATDATE;
	$dateformat = $langpatdate{0} . $langpatdate{2} . $langpatdate{4};
	$datesep = $langpatdate{1};

	$tmp_deadlinefrom .= '<input name="deadlinefrom_date" value="'.formvar($deadlinefrom_date).'" size="10"/>'."\n";
	$tmp_deadlinefrom .= '<a href="javascript:displayDatePicker(\'deadlinefrom_date\',false,\''.$dateformat.'\',\''.$datesep.'\')"><img src="'.$dategif.'"/></a>'."\n";
	$tmp_deadlinefrom .= '<input name="deadlinefrom_time" value="'.formvar($deadlinefrom_time).'" size="6">'."\n";
	$tpl = str_replace ('<!--DEADLINEFROM-->',$tmp_deadlinefrom, $tpl);

	$isotill = DateTimeFunctions::validDate($deadlinetill);
	$isoarray = DateTimeFunctions::iso2dateArray( $isotill );
	if ($isoarray) {
		$deadlinetill_date = $isoarray['mday'] . '-' . $isoarray['mon'] . '-' . $isoarray['year'];
		$deadlinetill_time = $isoarray['hours'] . ':' . $isoarray['minutes'];
	}
	else {
		$deadlinetill_date = '';
		$deadlinetill_time = '';
	}
	$tmp_deadlinetill  = '';
	$tmp_deadlinetill .= '<input name="deadlinetill_date" value="'.formvar($deadlinetill_date).'" size="10"/>'."\n";
	$tmp_deadlinetill .= '<a href="javascript:displayDatePicker(\'deadlinetill_date\',false,\''.$dateformat.'\',\''.$datesep.'\')"><img src="'.$dategif.'"/></a>'."\n";
	$tmp_deadlinetill .= '<input name="deadlinetill_time" value="'.formvar($deadlinetill_time).'" size="6">'."\n";
	$tpl = str_replace ("<!--DEADLINETILL-->",$tmp_deadlinetill, $tpl);

	$selected = ($Thumbnail_view != '') ? 'checked="checked"' : '';
	$Object_Thumnail = '<input type="checkbox" name="Thumbnail" '.$selected.'/>'."\n";
	$tpl = str_replace ('<!--THUMB-->',$Object_Thumnail, $tpl);

	$selected = ($HierarchialView != '') ? 'checked="checked"' : '';
	$Object_Hierarchial = '<input type="checkbox" name="HierarchialView" '.$selected.'/>'."\n";
	$tpl = str_replace ('<!--HIERARCHIALVIEW-->',$Object_Hierarchial, $tpl);

	$selected = ($Lockedby != '') ? 'checked="checked"' : '';
	$Object_Lock = '<input type="checkbox" name="Locked" '.$selected.'/>'."\n";
	$tpl = str_replace ('<!--LOCK-->',$Object_Lock, $tpl);

	$numberOfRES =  '<select name="numRes" style="width:90px">'."\n";
	if( $Number_of_Results == -1 ) { // -1 means select all
		$numberOfRES .= '<option value="-1" selected="selected">'.BizResources::localize('ACT_SELECT_ALL').'</option>'."\n";
	} else {
		$numberOfRES .= '<option value="-1">'.BizResources::localize('ACT_SELECT_ALL').'</option>'."\n";
	}
	$numbers = array (5,10,20,30,50,100);
	for ($i = 0 ; $i < count ( $numbers ) ; $i++) {
		if ($numbers[$i] == $Number_of_Results) {
			$numberOfRES .= '<option value="'.$numbers[$i].'" selected="selected">'.formvar($numbers[$i]).'</option>'."\n";
		} else {
			$numberOfRES .= '<option value="'.$numbers[$i].'">'.formvar($numbers[$i]).'</option>'."\n";
		}
	}
	$numberOfRES .= '</select>'."\n";
	$tpl = str_replace ('<!--NUMBEROFRES-->',$numberOfRES, $tpl);

 /////////////////////// *** Result selection combo *** ///////////////////////

$ArrayOfQueryParams = array();
$numParams = 0;

if($inPub != -1 && $inPub != ""){
	$P_pub = new QueryParam ('PublicationId', '=', $inPub);
	$ArrayOfQueryParams[$numParams] = $P_pub;
	$numParams++;
}

if ($selectedIssueID <> 0){
	$P_iss = new QueryParam ('IssueId', '=', $selectedIssueID);
	$ArrayOfQueryParams[$numParams] = $P_iss;
	$numParams++;
}

if($inSection !=-1 && $inSection != ""){
	$P_sec = new QueryParam ('SectionId', '=', $inSection);
	$ArrayOfQueryParams[$numParams] = $P_sec;
	$numParams++;
}

if($inState != -1 && $inState != ""){
	if ($inState == -2) { //-2 = Personal state, needs to be converted back to -1
		$P_sta = new QueryParam ('StateId', '=', -1);
		$ArrayOfQueryParams[$numParams] = $P_sta;
		$numParams++;
	}
	else {
		$P_sta = new QueryParam ('StateId', '=', $inState);
		$ArrayOfQueryParams[$numParams] = $P_sta;
		$numParams++;
	}
}

if($Type != -1 && $Type != ""){
	$P_type = new QueryParam ('Type', '=', $Type);
	$ArrayOfQueryParams[$numParams] = $P_type;
	$numParams++;
}

if($Lockedby != ""){
	$P_lockedby = new QueryParam ('LockedBy', '!=',"");
	$ArrayOfQueryParams[$numParams] = $P_lockedby;
	$numParams++;
}

if($Name != ""){
	$P_name = new QueryParam ('Name', 'contains', $Name);
	$ArrayOfQueryParams[$numParams] = $P_name;
	$numParams++;
}

if($RouteTo != ""){
	$P_RouteTo = new QueryParam ('RouteTo', 'contains', $RouteTo);
	$ArrayOfQueryParams[$numParams] = $P_RouteTo;
	$numParams++;
}

if($Content != ""){
	$P_Content = new QueryParam ('PlainContent', 'contains', $Content);
	$ArrayOfQueryParams[$numParams] = $P_Content;
	$numParams++;
}

if($Modifier != ""){
	$P_modifier = new QueryParam ('Modifier', 'contains', $Modifier);
	$ArrayOfQueryParams[$numParams] = $P_modifier;
	$numParams++;
}

if($Modified != ""){
	$v_Modified = DateTimeFunctions::validDate($Modified, false);
	if ($v_Modified === false) {
		$dateError = "";
		$dateError = BizResources::localize("INVALID_DATE");
		$v_Modified = $Modified; //Force empty result
		$tpl = str_replace ("<!--ERROR1-->", $dateError, $tpl);
	}
	else {
		cutTimeFromDate($v_Modified, $v_Modified);
	}	
	$P_modified = new QueryParam ('Modified', 'contains', $v_Modified);
	$ArrayOfQueryParams[$numParams] = $P_modified;
	$numParams++;
}

if($Created != ""){
	$v_Created = DateTimeFunctions::validDate($Created, false);
	if ($v_Created === false) {
		$dateError = "";
		$dateError = BizResources::localize("INVALID_DATE");
		$v_Created = $Created; //Force empty result
		$tpl = str_replace ("<!--ERROR2-->", $dateError, $tpl);
	}
	else {
		cutTimeFromDate($v_Created, $v_Created);
	}	
	$P_created = new QueryParam ('Created', 'contains', $v_Created );
	$ArrayOfQueryParams[$numParams] = $P_created;
	$numParams++;
}

if($Creator != ""){
	$P_creator = new QueryParam ('Creator', 'contains', $Creator);
	$ArrayOfQueryParams[$numParams] = $P_creator;
	$numParams++;
}

$v_deadlinefrom = DateTimeFunctions::validDate($deadlinefrom);
$v_deadlinetill = DateTimeFunctions::validDate($deadlinetill);

if ($v_deadlinefrom) {
	$P_deadlinefrom = new QueryParam ('Deadline', '>=', $v_deadlinefrom);
	$ArrayOfQueryParams[$numParams] = $P_deadlinefrom;
	$numParams++;
}

if ($v_deadlinetill) {
	$P_deadlinetill = new QueryParam ('Deadline', '<=', $v_deadlinetill);
	$ArrayOfQueryParams[$numParams] = $P_deadlinetill;
	$numParams++;
}

// handle export to CSV file
if (isset($_REQUEST["butCSV"]) && $_REQUEST["butCSV"]) {
	require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
	$request = new WflQueryObjectsRequest();
	$request->Ticket = $ticket;
	$request->Params = $ArrayOfQueryParams;
	$request->MaxEntries = 0;
	$request->Hierarchical = $HierarchialView;
	$request->Order = BizQuery::getQueryOrder( $ord, $sort );
	$request->MinimalProps = array( 'Format' );
	$ArrayOfRow = BizQuery::queryObjects2( $request, $globUser );

	$txt = '';
	$komma = '';
	$nr = 0;
	require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
	$exclude = BizProperty::getIdentifierPropIds();
	$dispColumn = array();
	foreach( $ArrayOfRow->Columns as $header ) {
		$found = false;
		foreach ($exclude as $ex) {
			if ($ex == $header->Name) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			$dispColumn[$nr] = true;
			$displayName = str_replace( '"', '""', $header->DisplayName ); // Bug fix: Escape double quotes for Excel support
			$txt .= $komma.'"'.$displayName.'"';
			$komma = "\t";
		}
		$nr++;
	}
	$txt .= "\r\n";

	foreach( $ArrayOfRow->Rows as $row ) {
		$komma = '';
		$nr = 0;
		foreach( $row as $field ) {
			if (isset($dispColumn[$nr]) && $dispColumn[$nr]) {
				$field = str_replace( '"', '""', $field ); // Bug fix: Escape double quotes for Excel support
				$txt .= $komma.'"'.$field.'"';
				$komma = "\t";
			}
			$nr++;
		}
		$txt .= "\r\n";
	}

	header("Content-type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=query" . date("Y-m-d") . ".xls");
	header("Content-Description: PHP Generated Data");
	
	$txt =  iconv ( 'UTF-8', 'UTF-16LE//IGNORE', $txt ); // Bug fix: Convert to UTF-16 for Excel support
	print chr(255).chr(254).$txt; // Bug fix: Add prefix for Excel support
	exit;
}

$num_results = 0;
$ObjectTitels = "";
$queryResp = null;
try {
	if ( $emptySearch == 1) {
		$queryResp = null;
	} else if( $Number_of_Results != -1 ) {
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $ticket;
		$request->Params = $ArrayOfQueryParams;
		$request->FirstEntry = $newStartPos;
		$request->MaxEntries = $Number_of_Results;
		$request->Hierarchical = $HierarchialView;
		$request->Order = BizQuery::getQueryOrder( $ord, $sort );
		$request->MinimalProps = array( 'Format' );
		$queryResp = BizQuery::queryObjects2( $request, $globUser );
	} else {
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $ticket;
		$request->Params = $ArrayOfQueryParams;
		$request->MaxEntries = 0;
		$request->Hierarchical = $HierarchialView;
		$request->Order = BizQuery::getQueryOrder( $ord, $sort );
		$request->MinimalProps = array( 'Format' );
		$queryResp = BizQuery::queryObjects2( $request, $globUser );
	}
} catch( BizException $e ) {
	echo '<font color="red">'.$e->getMessage().'</font><br/>'; // BZ#9890: No more showing $e->getDetail() to avoid sql injection
}
if( $queryResp ) {
	$ObjectRes = ShowQueryResults( $queryResp, $ord, $sort, $Thumbnail_view, $ObjectTitels, $num_results );
} else {
	$ObjectRes = '';
}
if( $queryResp ) {
	$maxnum_results = $queryResp->TotalEntries;
} else {
	$maxnum_results = 0;
}
if( $num_results > $Number_of_Results ) { // if server does not support LIMIT, we show ALL rows and hide the navigation bar
	$Number_of_Results = -1;
	$newStartPos = 1;
}

$listButtonBar = $num_results > 0 ? 'none' : 'hidden';


// show result navigation button bar; first, back, next, last
$search = "";
if( $Number_of_Results != -1 && $maxnum_results > 0 && $Number_of_Results < $maxnum_results )
{
	$pageCount = ceil( $maxnum_results / $Number_of_Results );
	$restPage = $maxnum_results % $Number_of_Results;
	$curPage = ceil( $newStartPos / $Number_of_Results );
	$backPos = $newStartPos - $Number_of_Results;
	$nextPos = $newStartPos + $Number_of_Results;
	$thisEndPos = $newStartPos + $num_results - 1;
	$lastPos = min($maxnum_results - $restPage + 1, $nextPos);

	$search .= "<td align=right width=33%>";
	if( $curPage > 1 ) {
		$search .= "<a href=\"javascript:ShowLimitedResults( '1' );\">";
		$search .= "<img src='../../config/images/rewnd_32.gif' border='0' title='".BizResources::localize("LIS_BEGIN")."'></a>&nbsp;&nbsp;&nbsp;";
		$search .= "<a href=\"javascript:ShowLimitedResults( '$backPos' );\">";
		$search .= "<img src='../../config/images/back_32.gif' border='0' title='".BizResources::localize("ACT_BACK")."'></a>";
	}
	$search .= "</td>\r\n";
	$search .= "<td align=center width=33%>$newStartPos - $thisEndPos / $maxnum_results</td>\r\n";
	$search .= "<td align=left width=33%>";
	if( $curPage < $pageCount ) {
		$search .= "<a href=\"javascript:ShowLimitedResults( '$nextPos' );\">";
		$search .= "<img src='../../config/images/forwd_32.gif' border='0' title='".BizResources::localize("LIS_NEXT")."'></a>&nbsp;&nbsp;&nbsp;";
		$search .= "<a href=\"javascript:ShowLimitedResults( '$lastPos' );\">";
		$search .= "<img src='../../config/images/fastf_32.gif' border='0' title='".BizResources::localize("LIS_LAST")."'></a></td>\r\n";
	}
	$search .= "</td>\r\n";
}
if( strlen( $search ) == 0 )
{
	if( $emptySearch == 1 ) {
		$search .= "<td align=center><i>".BizResources::localize('CLICK_SEARCH_TO_SHOW_RESULTS')."</i></td>";
	} else if( $num_results == 0 ) {
		$search .= "<td align=center><i>".BizResources::localize('NO_MATCH_FOUND')."</i></td>";
	} else if( $maxnum_results > 0 ) {
		$search .= "<td align=center>$maxnum_results / $maxnum_results</td>";
	}
}

$sHiddenFormParam = '
	<input type="hidden" name="ord" value="'.formvar($ord).'">
	<input type="hidden" name="sort" value="'.formvar($sort).'">
	<input type="hidden" name="startPos" value="$newStartPos">
	<input type="hidden" name="emptySearch" value="0">
	<input type="hidden" name="unlock" value="">
	<input type="hidden" name="del" value="">';

$tpl = str_replace ("<!--LISTBUTTONBAR-->",$listButtonBar, $tpl);
$tpl = str_replace ("<!--HIDDENFORMPARAMS-->",$sHiddenFormParam, $tpl);
$tpl = str_replace ("<!--RESULTTITELS-->",$ObjectTitels, $tpl);
$tpl = str_replace ("<!--RESULTOBJECTS-->",$ObjectRes, $tpl);
$tpl = str_replace ("<!--SEARCHBACK_NEXT-->",$search, $tpl);


//set focus to first field
$tpl .= "<script language='javascript'>document.forms[0].Type.focus();</script>";

print HtmlDocument::buildDocument($tpl, true, $succeed ? '' : "onLoad=\"javaScript:alert('$message !')\"", true );

//////////////////////// *** functions section *** /////////////////////////////
function startComboBox($comboBoxIss, $name)
{
	$comboBoxIss .= '<select name="'.$name.'" style="width:135px" onchange="javascript:EmptySearch();">';
	return $comboBoxIss;
}

function addAllToComboBox($comboBoxIss)
{
	$comboBoxIss .= '<option value="-1">' . BizResources::localize('ACT_SELECT_ALL') . '</option>';
	return $comboBoxIss;
}

function getListOfPrevCurrNextIssues($publication, $allIssues)
{
	$pcn_issues = array();

	// Just get previous/current/next issues without checking authorization
	$pcn_issues = BizPublication::listPrevCurrentNextIssues($publication);

	// The $allIssues contains the issues checked for authorizations.
	// The previous/current/next must be in line with the list of issues.
	if( count($pcn_issues) ) foreach ($pcn_issues as $key_pcn_issue => $pcn_issue) {
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

function selectedItemInList($inIssue, $allIssues, $pcn_issues)
{
	if ($inIssue == -1) {
		return true; //All
	}

	// Check if one of the list is selected
	if ($inIssue > 0 && !empty($allIssues)) {
		foreach ($allIssues as $allIssue) {
			if ($inIssue == $allIssue->Id) {
				return true; // One of the list
			}
		}
	}

	// Check if one previous/current/next is selected
	if ($inIssue < -1 && !empty($pcn_issues)) {
		switch ($inIssue) {
			case -2:
				if (isset($pcn_issues['current'])) {
					return true;
				}
			case -3:
				if (isset($pcn_issues['prev'])) {
					return true;
				}
			case -4:
				if (isset($pcn_issues['next'])) {
					return true;
				}
			default:
				break;
		}
	}

	return false;
}

function defaultSelectedIssue($pcn_issues, $arrayOfIssues)
{
	if (isset($pcn_issues['current'])){
		$inIssue = -2; //current
	}
	elseif (!empty($arrayOfIssues)){
		$inIssue = $arrayOfIssues[0]->Id; //first of the list
	}
	else {
		$inIssue = -1;
	}

	return $inIssue;
}

function addPrevCurrNextToComboBoxIss($comboBoxIss, $pcn_issues, $inIssue)
{
	if (isset($pcn_issues['current'])) {
		$selected = ($inIssue == -2) ? 'selected="selected"' : '';
		$comboBoxIss .= '<option value="-2" '.$selected.'>' . BizResources::localize('CURRENT_ISSUE') . ' (' . formvar($pcn_issues['current']['issue']) . ')' . '</option>';
	}

	if (isset($pcn_issues['prev'])) {
		$selected = ($inIssue == -3) ? 'selected="selected"' : '';
		$comboBoxIss .= '<option value="-3" '.$selected.'>' . BizResources::localize('PREV_ISSUE') . ' (' . formvar($pcn_issues['prev']['issue']) . ')' . '</option>';
	}

	if (isset($pcn_issues['next'])) {
		$selected = ($inIssue == -4) ? 'selected="selected"' : '';
		$comboBoxIss .= '<option value="-4" '.$selected.'>' . BizResources::localize('NEXT_ISSUE') . ' (' . formvar($pcn_issues['next']['issue']) . ')' . '</option>';
	}

	return $comboBoxIss;
}

function addListOfIssuesToComboBoxIss($comboBoxIss, $allIssues, $inIssue)
{
	if (!empty($allIssues)){
		foreach ($allIssues as $issue) {
			if( $issue->Id != $inIssue ) {
				$comboBoxIss .= '<option value="'.$issue->Id.'">'.formvar($issue->Name).'</option>';
			} else {
				$comboBoxIss .= '<option value="'.$issue->Id.'" selected="selected">'.formvar($issue->Name).'</option>';
			}
		}
	}

	return $comboBoxIss;
}

function EndComboBox($comboBoxIss)
{
	$comboBoxIss .= '</select>'."\n";
	return $comboBoxIss;
}

function getSelectedIssue($inIssue, $pcn_issues)
{
	// Based on the name of the selected item the ID of the selected item is
	// determined.
	if ($inIssue > 0) {
		return $inIssue; // name is ID
	}

	if (isset($pcn_issues['current']) && ($inIssue == -2)) {
		return $pcn_issues['current']['id'];
	}

	if (isset($pcn_issues['prev']) && ($inIssue == -3)) {
		return $pcn_issues['prev']['id'];
	}

	if (isset($pcn_issues['next']) && ($inIssue == -4)) {
		return $pcn_issues['next']['id'];
	}

	return 0;
}

/**
 * Function removes the time component from a iso formatted datetime string.
 * 
 * @param string iso formatted datetime $date
 * @param string first 10 positions of an iso formatted datetime $normdate
 */
function cutTimeFromDate( $date, &$normdate )
{
	$normdate = substr($date, 0, 10);
}
?>
