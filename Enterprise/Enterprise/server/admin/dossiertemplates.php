<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmTemplateObject.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

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
$pubId = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$issueId = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0;
$object = isset($_REQUEST['objid']) ? intval($_REQUEST['objid']) : 0;
$seldossiertemplate = isset($_REQUEST['seldossiertemplate']) ? intval($_REQUEST['seldossiertemplate']) : 0;
$dossierTemplateId = $object > 0 ? $object : $seldossiertemplate;
$groupId = isset($_REQUEST['group']) ? intval($_REQUEST['group']) : 0;
$insert = isset($_REQUEST['insert']) ? (bool)$_REQUEST['insert'] : false;
$records = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;

$errors = array();
// check publication rights
checkPublAdmin($pubId);

// handle request
switch( $mode ) {
	case 'update':
		if( $insert === true ) {
			$objectId = isset ($_REQUEST['dossiertemplate']) ? intval( $_REQUEST['dossiertemplate'] ) : 0;
			// create template object
			if( $objectId != 0 && ($seldossiertemplate == $objectId || $object == $objectId ) ) {
				$templateObject = new AdmTemplateObjectAccess();
				$templateObject->TemplateObjectId = $objectId;
				$templateObject->UserGroupId = $groupId;

				try {
					require_once BASEDIR.'/server/services/adm/AdmAddTemplateObjectsService.class.php';
					$request = new AdmAddTemplateObjectsRequest();
					$request->Ticket = $ticket;
					$request->RequestModes = array();
					$request->PublicationId = $pubId;
					$request->IssueId = $issueId;
					$request->TemplateObjects = array( $templateObject );
					$service = new AdmAddTemplateObjectsService();
					$service->execute( $request );
				} catch( BizException $e ) {
					$errors[] = $e->getMessage();
					$mode = 'error';
				}
			}
		}
		break;
	case 'add':
		if( $insert === true ) {
			$objectId = isset( $_REQUEST['dossiertemplate'] ) ? intval( $_REQUEST['dossiertemplate'] ) : 0;
			// create PubObject
			if( $objectId != 0 && ($seldossiertemplate == $objectId || $object == $objectId) ) {
				$templateObject = new AdmTemplateObjectAccess();
				$templateObject->TemplateObjectId = $objectId;
				$templateObject->UserGroupId = $groupId;

				try {
					require_once BASEDIR.'/server/services/adm/AdmAddTemplateObjectsService.class.php';
					$request = new AdmAddTemplateObjectsRequest();
					$request->Ticket = $ticket;
					$request->RequestModes = array();
					$request->PublicationId = $pubId;
					$request->IssueId = $issueId;
					$request->TemplateObjects = array( $templateObject );
					$service = new AdmAddTemplateObjectsService();
					$service->execute( $request );
				} catch( BizException $e ) {
					$errors[] = $e->getMessage();
					$mode = 'error';
				}
			}
		}
		break;
	case 'delete':
		if( !$groupId ) { //no group id is given when delete is called from publication overview
			$templateObjects = array();
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';
				$request = new AdmGetTemplateObjectsRequest();
				$request->Ticket = $ticket;
				$request->RequestModes = array();
				$request->PublicationId = $pubId;
				$request->IssueId = $issueId;
				$request->TemplateObjectId = $object;
				$request->UserGroupId = null;
				$service = new AdmGetTemplateObjectsService();
				$response = $service->execute( $request );
				$templateObjects = $response->TemplateObjects;
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
				$mode = 'error';
			}
			$userGroupIds = array();
			if( $templateObjects ) foreach( $templateObjects as $templateObject ) {
				$userGroupIds[] = $templateObject->UserGroupId;
			}
			$templateObjects = array();
			if( $userGroupIds ) foreach( $userGroupIds as $userGroupId ) {
				$templateObject = new AdmTemplateObjectAccess();
				$templateObject->TemplateObjectId = $object;
				$templateObject->UserGroupId = $userGroupId;
				$templateObjects[] = $templateObject;
			}
		} else { //delete called from the dossiertemplates overview
			$templateObject = new AdmTemplateObjectAccess();
			$templateObject->TemplateObjectId = $object;
			$templateObject->UserGroupId = $groupId;
			$templateObjects[] = $templateObject;
		}

		try {
			require_once BASEDIR.'/server/services/adm/AdmRemoveTemplateObjectsService.class.php';
			$request = new AdmRemoveTemplateObjectsRequest();
			$request->Ticket = $ticket;
			$request->PublicationId = $pubId;
			$request->IssueId = $issueId;
			$request->TemplateObjects = $templateObjects;
			$service = new AdmRemoveTemplateObjectsService();
			$service->execute( $request );
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
			$mode = 'error';
		}
		break;
}

$txt = HtmlDocument::loadTemplate( 'dossiertemplates.htm' );

// fields
//	Get all publications
$pubObj = null;
try {
	require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
	$request = new AdmGetPublicationsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array( 'GetPublications' );
	$request->PublicationIds = array( $pubId );
	$service = new AdmGetPublicationsService();
	$response = $service->execute($request);
	$pubObj = $response->Publications[0];
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

if( $issueId ) {
	try {
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
		$request = new AdmGetIssuesRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationId = $pubId;
		$request->IssueIds = array( $issueId );
		$service = new AdmGetIssuesService();
		$response = $service->execute($request);
		$issueObj = $response->Issues[0];
		$txt = str_replace( "<!--VAR:ISSUE-->", formvar( $issueObj->Name ).inputvar( 'issue',$issueId,'hidden' ), $txt );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
} else {
	$txt = preg_replace( "/<!--IF:ISSUE-->.*?<!--ENDIF:ISSUE-->/s",'', $txt );
}

$dossierTemplates = BizAdmTemplateObject::listObjectsIdNameByType( $pubId, $issueId, 'DossierTemplate' );
$dossierTemplateCombo = array();
if( $dossierTemplates ) foreach( $dossierTemplates as $dossierTemplate ) {
	$dossierTemplateCombo[$dossierTemplate->Id] = $dossierTemplate;
}
if( $object > 0 ) {
	$dossierTemplate = formvar( $dossierTemplateCombo[$object]->Name ).inputvar( 'objid', $object, 'hidden' );
} elseif( $seldossiertemplate > 0 ) {
	$dossierTemplate = formvar($dossierTemplateCombo[$seldossiertemplate]->Name).inputvar( 'objid', $seldossiertemplate, 'hidden' );
} else {
	$dossierTemplate = '<select name="seldossiertemplate" onChange="this.form.submit()">';
	foreach( array_keys( $dossierTemplateCombo ) as $objId ) {
		if( $dossierTemplateId == 0 ) {
			$dossierTemplateId = $objId; // Get the first Object Id from the combo
		}
		$selected = ( $seldossiertemplate == $objId ) ? 'selected="selected"' : '';
		$dossierTemplate .= '<option value="'.$objId.'" '.$selected.'>'.formvar( $dossierTemplateCombo[$objId]->Name ).'</option>';
	}
	$dossierTemplate .= '</select>';
	
	if( $dossierTemplateId != 0 ) {
		$dossierTemplate .= inputvar( 'dossiertemplate', $dossierTemplateId, 'hidden' );
	}
	$dossierTemplate .= inputvar( 'add', '1', 'hidden' );
}

$txt = str_replace( '<!--VAR:PUBL-->', formvar( $pubObj->Name ).inputvar( 'publ',$pubId,'hidden' ), $txt );
$txt = str_replace( '<!--VAR:DOSSIER_TEMPLATE-->', $dossierTemplate, $txt );

// generate lower part
$detailtxt = '';
$sAll = BizResources::localize( 'LIS_ALL' );

$usergroupsCombo = array();
try {
	require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
	$request = new AdmGetUserGroupsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array( 'GetUserGroups' );
	$service = new AdmGetUserGroupsService();
	$response = $service->execute($request);
	$usergroups = $response->UserGroups;

	$usergroupsCombo[0] = '<'.$sAll.'>';
	if( $usergroups ) foreach( $usergroups as $usergroup ) {
		$usergroupsCombo[$usergroup->Id]= $usergroup->Name;
	}
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

switch( $mode ) {
	case 'view':
	case 'update':
	case 'delete':
	case 'error':
		try {
			require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';
			$request = new AdmGetTemplateObjectsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationId = $pubId;
			$request->IssueId = $issueId;
			$request->TemplateObjectId = $dossierTemplateId;
			$request->UserGroupId = null;
			$service = new AdmGetTemplateObjectsService();
			$response = $service->execute( $request );
			$templateObjects = $response->TemplateObjects;

			$i = 0;
			$color = array (' bgcolor="#eeeeee"', '');
			$flip = 0;
			if( $templateObjects ) foreach( $templateObjects as $templateObject ) {
				$clr = $color[$flip];
				$flip = 1- $flip;
				$objectId = ($object > 0) ? $object : $seldossiertemplate;
				$deltxt = '<a href="dossiertemplates.php?publ='.$pubId.'&issue='.$issueId.'&delete=1&seldossiertemplate='.$objectId.'&objid='.$templateObject->TemplateObjectId.'&group='.$templateObject->UserGroupId.'" onclick="return myconfirm(\'delpubobject\')"><img src="../../config/images/remov_16.gif" border="0" width="16" height="16" title="'.BizResources::localize('ACT_DEL').'"/></a>';
				$detailtxt .= "<tr$clr>";
				$detailtxt .= "<td>";
				if($object > 0) {
					$detailtxt .= inputvar("object$i",$object,'hidden');
				} else {
					$detailtxt .= inputvar("object$i",$seldossiertemplate,'hidden');
				}
				$detailtxt .= formvar( $usergroupsCombo[$templateObject->UserGroupId] );
				$detailtxt .= inputvar( "groups$i", $templateObject->UserGroupId, 'hidden' );
				$detailtxt .= '<td>'.$deltxt.'</td></tr>';
				$i++;
			}
			$detailtxt .= inputvar( 'recs', $i, 'hidden' );
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
		}
		break;
	case 'add':
		// 1 row to enter new record
		$i = 0;
		$detailtxt .= '<tr>';
		$detailtxt .= "<td>";
		if($object > 0) {
			$detailtxt .= inputvar('dossiertemplate',$object,'hidden');
		} else {
			$detailtxt .= inputvar("dossiertemplate",$dossierTemplateId,'hidden');
			
		}
		$detailtxt .= inputvar("group", '', 'combo', $usergroupsCombo, false).'</td>';
		$detailtxt .= '<td></td></tr>';
		$detailtxt .= inputvar( 'insert', '1', 'hidden' );

		// show other pubobjects as info
		if( $dossierTemplateId > 0 ) {
			try {
				require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';
				$request = new AdmGetTemplateObjectsRequest();
				$request->Ticket = $ticket;
				$request->RequestModes = array( 'GetObjectInfos' );
				$request->PublicationId = $pubId;
				$request->IssueId = $issueId;
				$request->TemplateObjectId = $dossierTemplateId;
				$request->UserGroupId = null;
				$service = new AdmGetTemplateObjectsService();
				$response = $service->execute( $request );
				$templateObjects = $response->TemplateObjects;
				$resObjectInfos = $response->ObjectInfos;

				$objectInfos = array();
				if( $resObjectInfos ) foreach( $resObjectInfos as $objectInfo ) {
					$objectInfos[$objectInfo->ID] = $objectInfo;
				}

				$color = array (" bgcolor='#eeeeee'", '');
				$flip = 0;
				if( $templateObjects ) foreach( $templateObjects as $templateObject ) {
					$clr = $color[$flip];
					$flip = 1- $flip;
					$dossierTemplate = $objectInfos[$templateObject->TemplateObjectId]->Name;
					$detailtxt .= "<tr$clr>";
					$detailtxt .= '<td>'.formvar($usergroupsCombo[$templateObject->UserGroupId]).'</td></tr>';
					$detailtxt .= '<td></td></tr>';
				}
			} catch( BizException $e ) {
				$errors[] = $e->getMessage();
			}
		}
		break;
}

$err = '';
if( $errors ) foreach( $errors as $error ) {
	$err .= $error.'<br/>';
}
$txt = str_replace( '<!--ERROR-->', $err, $txt );

// generate total page
$txt = str_replace("<!--ROWS-->", $detailtxt, $txt);

if( $issueId > 0 ) {
	$back = "hppublissues.php?id=$issueId";
} else {
	$back = "hppublications.php?id=$pubId";
}
$txt = str_replace("<!--BACK-->", $back, $txt);

// generate total page
print HtmlDocument::buildDocument($txt);
