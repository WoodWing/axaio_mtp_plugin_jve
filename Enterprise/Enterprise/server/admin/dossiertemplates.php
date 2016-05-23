<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmPubObject.class.php';

$ticket = checkSecure('publadmin');

// determine incoming mode
if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = 'update';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['add']) && $_REQUEST['add']) {
	$mode = 'add';
} else {
	$mode = 'view';
}
// get param's
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$publ = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$issue = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; 
$object = isset($_REQUEST['objid']) ? intval($_REQUEST['objid']) : 0;
$selDossierTemplate = isset($_REQUEST['seldossiertemplate']) ? intval($_REQUEST['seldossiertemplate']) : 0;
$dossierTemplateId = $object > 0 ? $object : $selDossierTemplate;
$group = isset($_REQUEST['group']) ? intval($_REQUEST['group']) : 0;
$insert = isset($_REQUEST['insert']) ? (bool)$_REQUEST['insert'] : false;
$records    = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;

// check publication rights
checkPublAdmin($publ);

// handle request
switch ($mode) {
	case 'update':
		if ($insert === true) {
			$objectId = isset($_REQUEST['dossiertemplate']) ? intval($_REQUEST['dossiertemplate']) : 0;
			// create PubObject
			if( $objectId != 0 && ($selDossierTemplate == $objectId || $object == $objectId) ) {
				BizAdmPubObject::createPubObject($publ, $issue, $objectId, $group);
			}
		}
		if ($records > 0) {
			for ($i=0; $i < $records; $i++) {
				$id = isset($_REQUEST["id$i"]) ? intval($_REQUEST["id$i"]) : 0;
				$objectId = isset($_REQUEST["object$i"]) ? intval($_REQUEST["object$i"]) : 0;
				$groupId  = isset($_REQUEST["group$i"]) ? intval($_REQUEST["group$i"]) : 0;
				BizAdmPubObject::modifyPubObjects( $id, $publ, $issue, $objectId, $groupId );
			}
		}
		break;
	case 'add':
		if ($insert === true) {
			$objectId = isset($_REQUEST['dossiertemplate']) ? intval($_REQUEST['dossiertemplate']) : 0;
			// create PubObject
			if( $objectId != 0 && ($selDossierTemplate == $objectId || $object == $objectId) ) {
				BizAdmPubObject::createPubObject($publ, $issue, $objectId, $group);	
			}
		}
		break;
	case 'delete':
		BizAdmPubObject::deletePubObjects( $id, $object );
		break;
}

if( $mode == 'delete' ) {
	if( $object > 0) {
		if ($issue) {
			header("Location:hppublissues.php?id=$issue");
			exit();
		} else {
			header("Location:hppublications.php?id=$publ");
			exit();
		}
	}
}

$txt = HtmlDocument::loadTemplate( 'dossiertemplates.htm' );

$error = '';
// Check whether Plugin installed and activated
$error = BizAdmPubObject::checkPluginError( $issue );
$txt = str_replace("<!--ERROR-->", $error, $txt);

// fields
//	Get all publications
require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php';
$service = new AdmGetPublicationsService();

$request = new AdmGetPublicationsRequest( $ticket, array('GetPublications'), array( $publ ) );
$response = $service->execute($request);
$pubObj = $response->Publications[0];

if ($issue) {
	require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
	require_once BASEDIR.'/server/interfaces/services/adm/AdmGetIssuesRequest.class.php';
	$service = new AdmGetIssuesService();
	$request = new AdmGetIssuesRequest( $ticket, null, $publ, null, array( $issue) );
	$response = $service->execute($request);
	$issueObj = $response->Issues[0];

	$txt = str_replace("<!--VAR:ISSUE-->", formvar($issueObj->Name).inputvar('issue',$issue,'hidden'), $txt);
} else {
	$txt = preg_replace("/<!--IF:ISSUE-->.*?<!--ENDIF:ISSUE-->/s",'', $txt);
}

$dossierTemplateCombo= BizAdmPubObject::listDossierTemplatesIdName( $publ );
if( $object > 0 ){
	$dossierTemplate = formvar($dossierTemplateCombo[$object]).inputvar('objid',$object,'hidden');
} elseif( $selDossierTemplate > 0 ){
	$dossierTemplate = formvar($dossierTemplateCombo[$selDossierTemplate]).inputvar('objid',$selDossierTemplate,'hidden');
} else {
	$dossierTemplate = '<select name="seldossiertemplate" onChange="this.form.submit()">';
	foreach (array_keys($dossierTemplateCombo) as $objId) {
		if( $dossierTemplateId == 0 ) {
			$dossierTemplateId = $objId; // Get the first Object Id from the combo
		}
		$selected = ($selDossierTemplate == $objId) ? 'selected="selected"' : '';
		$dossierTemplate .= '<option value="'.$objId.'" '.$selected.'>'.formvar($dossierTemplateCombo[$objId]).'</option>';
	}
	$dossierTemplate .= '</select>';
	
	if($dossierTemplateId != 0) {
		$dossierTemplate .= inputvar('dossiertemplate', $dossierTemplateId, 'hidden');	
	}
	$dossierTemplate .= inputvar('add', '1', 'hidden');
}

$txt = str_replace('<!--VAR:PUBL-->', formvar($pubObj->Name).inputvar('publ',$publ,'hidden'), $txt );
$txt = str_replace('<!--VAR:DOSSIER_TEMPLATE-->', $dossierTemplate, $txt );

// generate lower part
$detailTxt = '';
$sAll = BizResources::localize('LIS_ALL');

require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetUserGroupsRequest.class.php';
$service = new AdmGetUserGroupsService();
$request = new AdmGetUserGroupsRequest( $ticket, array('GetUserGroups'), null );
$response = $service->execute($request);
$userGroups = $response->UserGroups;
$userGroupsCombo = array();
$userGroupsCombo[0] = '<'.$sAll.'>';
foreach( $userGroups as $userGroup ) {
	$userGroupsCombo[$userGroup->Id]= $userGroup->Name;
}

switch ($mode) {
	case 'view':
	case 'update':
	case 'delete':
		$txt = str_replace("<!--EXTRA_HEADER-->", '<th width="5px"></th>', $txt); // Extra header column for delete button
		$pubObjects = array();
		$pubObjects = BizAdmPubObject::listPubObjects( $publ, $issue, $dossierTemplateId );
		$i = 0;
		$color = array (' bgcolor="#eeeeee"', '');
		$flip = 0;
		foreach( $pubObjects as $pubObject ) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$objectId = ($object > 0) ? $object : $selDossierTemplate;
			$delTxt = '<a href="dossiertemplates.php?publ='.$publ.'&issue='.$issue.'&delete=1&seldossiertemplate='.$objectId.'&id='.$pubObject->Id.'" onclick="return myconfirm(\'delpubobject\')"><img src="../../config/images/remov_16.gif" border="0" width="16" height="16" title="'.BizResources::localize('ACT_DEL').'"/></a>';
			$detailTxt .= "<tr$clr>";
			$detailTxt .= "<td>";
			if($object > 0) {
				$detailTxt .= inputvar("object$i",$object,'hidden');
			} else {
				$detailTxt .= inputvar("object$i",$selDossierTemplate,'hidden');
			}
			$detailTxt .= inputvar("group$i", $pubObject->GroupId, 'combo', $userGroupsCombo, false).'</td>';
			$detailTxt .= '<td>'.$delTxt.'</td></tr>';
			$detailTxt .= inputvar( "id$i", $pubObject->Id, 'hidden' );
			$i++;
		}
		$detailTxt .= inputvar( 'recs', $i, 'hidden' );
		break;
	case 'add':
		// 1 row to enter new record
		$detailTxt .= '<tr><td>';
		if( $object > 0 ) {
			$detailTxt .= inputvar('dossiertemplate',$object,'hidden');
		} else {
			$detailTxt .= inputvar("dossiertemplate",$dossierTemplateId,'hidden');
			
		}
		$detailTxt .= inputvar("group", '', 'combo', $userGroupsCombo, false).'</td></tr>';
		$detailTxt .= inputvar( 'insert', '1', 'hidden' );

		// show other pubobjects as info
		if( $dossierTemplateId > 0 ) {
			$pubObjects = array();
			$pubObjects = BizAdmPubObject::listPubObjects( $publ, $issue, $dossierTemplateId );
			$color = array (" bgcolor='#eeeeee'", '');
			$flip = 0;
			foreach( $pubObjects as $pubObject ) {
				$clr = $color[$flip];
				$flip = 1- $flip;
				$dossierTemplate = $pubObject->ObjectName;
				$detailTxt .= "<tr$clr>";
				$detailTxt .= '<td>'.formvar($userGroupsCombo[$pubObject->GroupId]).'</td></tr>';
			}
		}
		break;
}

// generate total page
$txt = str_replace("<!--ROWS-->", $detailTxt, $txt);

if( $issue > 0 ) {
	$back = "hppublissues.php?id=$issue";
} else {
	$back = "hppublications.php?id=$publ";
}
$txt = str_replace("<!--BACK-->", $back, $txt);

// generate total page
print HtmlDocument::buildDocument($txt);