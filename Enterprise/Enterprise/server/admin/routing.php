<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR."/server/apps/functions.php";
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

$ticket = checkSecure('publadmin');

// determine incoming mode
$pubId  = isset($_REQUEST['publ'])  ? intval($_REQUEST['publ'])  : 0;
$issueId = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; 
$sectionId = isset($_REQUEST['selsection']) ? intval($_REQUEST['selsection']) : 0;
$records    = isset($_REQUEST['recs'])       ? intval($_REQUEST['recs']) : 0;
$insert     = isset($_REQUEST['insert'])     ? (bool)$_REQUEST['insert'] : false;

if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = 'update';
} elseif (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} elseif (isset($_REQUEST['add']) && $_REQUEST['add']) {
	$mode = 'add';
} else {
	$mode = 'view';
}

$errors = array();

// check publication rights
checkPublAdmin($pubId);

// handle request
if( $records > 0 ) {
	for( $i=0; $i < $records; $i++ ) {
		$id = isset($_REQUEST["id$i"]) ? intval($_REQUEST["id$i"]) : 0;
		$section = isset($_REQUEST["section$i"]) ? intval($_REQUEST["section$i"]) : 0;
		$status   = isset($_REQUEST["state$i"])   ? intval($_REQUEST["state$i"])   : 0;
		$routeTo = isset($_REQUEST["routeto$i"]) ? $_REQUEST["routeto$i"] : '';
		if( $id > 0 ) {
			$routing = new AdmRouting;
			$routing->Id = $id;
			$routing->StatusId = $status;
			$routing->SectionId = $section;
			$routing->RouteTo = $routeTo;

			try{
				require_once BASEDIR.'/server/services/adm/AdmModifyRoutingsService.class.php';
				$request = new AdmModifyRoutingsRequest();
				$request->Ticket = $ticket;
				$request->PublicationId = $pubId;
				$request->IssueId = $issueId;
				$request->Routings = array( $routing );
				$service = new AdmModifyRoutingsService();
				$service->execute( $request );
			} catch(BizException $e) {
				$errors[] = $e->getMessage();
			}
		}
	}
}
if( $insert === true ) {
	$section = isset($_REQUEST['section']) ? intval($_REQUEST['section']) : 0;
	$status   = isset($_REQUEST['state'])   ? intval($_REQUEST['state'])   : 0;
	$routeTo = isset($_REQUEST['routeto']) ? $_REQUEST['routeto'] : '';

	$routing = new AdmRouting;
	$routing->StatusId = $status;
	$routing->SectionId = $section;
	$routing->RouteTo = $routeTo;

	try {
		require_once BASEDIR.'/server/services/adm/AdmCreateRoutingsService.class.php';
		$request = new AdmCreateRoutingsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $pubId;
		$request->IssueId = $issueId;
		$request->Routings = array( $routing );
		$service = new AdmCreateRoutingsService();
		$response = $service->execute( $request );
		$id = reset( $response->Routings )->Id;
	} catch(BizException $e) {
		$errors[] = $e->getMessage();
	}
}
if( $mode == 'delete' ) {
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if( $id > 0 ) {
		try {
			require_once BASEDIR.'/server/services/adm/AdmDeleteRoutingsService.class.php';
			$request = new AdmDeleteRoutingsRequest();
			$request->Ticket = $ticket;
			$request->RoutingIds = array( $id );
			$service = new AdmDeleteRoutingsService();
			$response = $service->execute( $request );
		} catch(BizException $e) {
			$errors[] = $e->getMessage();
		}
	}
}

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'routing.htm' );

try {
	require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
	$request = new AdmGetPublicationsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$request->PublicationIds = array( $pubId );
	$service = new AdmGetPublicationsService();
	$response = $service->execute( $request );
	$pubName = reset( $response->Publications )->Name;
	$txt = str_replace('<!--VAR:PUBL-->', formvar($pubName).inputvar('publ',$pubId,'hidden'), $txt);
} catch(BizException $e) {
	$errors[] = $e->getMessage();
}

$overrulePub = false;
if ($issueId > 0) {
	require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
	$overrulePub = DBIssue::isOverruleIssue( $issueId );
	$issueName = DBIssue::getIssueName( $issueId );
	$txt = str_replace('<!--VAR:ISSUE-->', formvar($issueName).inputvar('issue',$issueId,'hidden'), $txt);
} else {
	$txt = preg_replace('/<!--IF:STATE-->.*<!--ENDIF-->/is', '', $txt);
}
$whereIssue = $overrulePub ? $issueId : 0;

$sectionDomain = array();
$sAll = BizResources::localize('LIS_ALL');
$selTxt = '<select name="selsection" onChange="this.form.submit()">';
$selTxt .= '<option value="0">&lt;'.$sAll.'&gt;</option>';

try {
	require_once BASEDIR.'/server/services/adm/AdmGetSectionsService.class.php';
	$request = new AdmGetSectionsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$request->PublicationId = $pubId;
	$request->IssueId = $whereIssue;
	$service = new AdmGetSectionsService();
	$response = $service->execute( $request );
	$sections = $response->Sections;
	//order by code?
	if( $sections ) foreach ( $sections as $section ) {
		$sectionDomain[$section->Id] = $section->Name;
		$selected = ($sectionId == $section->Id) ? 'selected="selected"' : '';
		$selTxt .= '<option value="'.$section->Id.'" '.$selected.'>'.formvar($section->Name).'</option>';
	}
} catch(BizException $e) {
	$errors[] = $e->getMessage();
}

$selTxt .= '</select>';
$txt = str_replace('<!--VAR:SELSECTION-->', $selTxt, $txt);

// generate lower part
$detailTxt = '';

$statusDomain = array();
try {
	require_once BASEDIR.'/server/services/adm/AdmGetStatusesService.class.php';
	$request = new AdmGetStatusesRequest();
	$request->Ticket = $ticket;
	$request->PublicationId = $pubId;
	$request->IssueId = $whereIssue;
	$service = new AdmGetStatusesService();
	$response = $service->execute( $request );
	$statuses = $response->Statuses;

	if ( $statuses ) foreach ( $statuses as $status ) {
		$statusDomain[$status->Id] = $status->Type.'/'.$status->Name;
	}
	//sort by code?
} catch(BizException $e) {
	$errors[] = $e->getMessage();
}

$routeDomain = array();
$arrayOfRoute = listrouteto( $ticket, $pubId, $overrulePub ? $issueId : null );
if ($arrayOfRoute) foreach ($arrayOfRoute as $route) {
	$routeDomain[$route] = $route;
}

$sAll = BizResources::localize('LIS_ALL');
switch ($mode) {
	case 'view':
	case 'update':
	case 'delete':
		try {
			require_once BASEDIR.'/server/services/adm/AdmGetRoutingsService.class.php';
			$request = new AdmGetRoutingsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationId = $pubId;
			$request->IssueId = $whereIssue;
			if( $sectionId > 0 ) {
				$request->SectionId = $sectionId;
			}
			$service = new AdmGetRoutingsService();
			$response = $service->execute( $request );
			$routings = $response->Routings;
			//order by routingsection, statetype, statecode?

			$i = 0;
			$color = array (' bgcolor="#eeeeee"', '');
			$flip = 0;
			if( $routings ) foreach( $routings as $routing ) {
				$clr = $color[$flip];
				$flip = 1- $flip;
				$delTxt = '<a href="routing.php?publ='.$pubId.'&issue='.$issueId.'&delete=1&id='.$routing->Id.'" onclick="return myconfirm(\'delroute\')">'.BizResources::localize('ACT_DEL').'</a>';
				$detailTxt .= "<tr$clr>";
				if ($sectionId > 0) {
					$detailTxt .= '<td>'.formvar($sectionDomain[$sectionId]).inputvar("section$i",$sectionId,'hidden').'</td>';
				} else {
					$detailTxt .= '<td>'.inputvar("section$i", $routing->SectionId, 'combo', $sectionDomain, $sAll).'</td>';
				}
				$statusName = $routing->StatusId ? $statusDomain[$routing->StatusId] : '';
				$detailTxt .= '<td>'.inputvar("state$i", $routing->StatusId, 'combo', $statusDomain, $sAll).'</td>';
				$detailTxt .= "<td>".inputvar("routeto$i", $routing->RouteTo, 'combo', $routeDomain).'</td>';
				$detailTxt .= '<td>'.$delTxt.'</td></tr>';
				$detailTxt .= inputvar( "id$i", $routing->Id, 'hidden' );
				$i++;
			}
			$detailTxt .= inputvar( 'recs', $i, 'hidden' );
		} catch(BizException $e) {
			$errors[] = $e->getMessage();
		}
		break;
	case 'add':
		// 1 row to enter new record
		$detailTxt .= '<tr>';
		if ($sectionId > 0) {
			$detailTxt .= '<td>'.formvar($sectionDomain[$sectionId]).inputvar('section',$sectionId,'hidden').'</td>';
		} else {
			$detailTxt .= '<td>'.inputvar('section', '', 'combo', $sectionDomain, $sAll).'</td>';
		}
		$detailTxt .= '<td>'.inputvar('state','', 'combo', $statusDomain, $sAll).'</td>';
		$detailTxt .= '<td>'.inputvar('routeto', '', 'combo', $routeDomain).'</td>';
		$detailTxt .= '<td></td></tr>';
		$detailTxt .= inputvar( 'insert', '1', 'hidden' );

		// show other routings as info
		try {
			require_once BASEDIR.'/server/services/adm/AdmGetRoutingsService.class.php';
			$request = new AdmGetRoutingsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationId = $pubId;
			$request->IssueId = $whereIssue;
			if( $sectionId > 0 ) {
				$request->SectionId = $sectionId;
			}
			$service = new AdmGetRoutingsService();
			$response = $service->execute( $request );
			$routings = $response->Routings;
			//order by routingsection, statetype, statecode?

			$color = array (" bgcolor='#eeeeee'", '');
			$flip = 0;
			if( $routings ) foreach( $routings as $routing ) {
				$clr = $color[$flip];
				$flip = 1- $flip;
				$sectionDetails = $routing->SectionId ? $sectionDomain[$routing->SectionId] : '<'.$sAll.'>';
				$statusDetails = $routing->StatusId ? $statusDomain[$routing->StatusId] : '<'.$sAll.'>';
				$detailTxt .= "<tr$clr><td>".formvar($sectionDetails).'</td>';
				$detailTxt .= '<td>'.formvar($statusDetails).'</td>';
				$detailTxt .= '<td>'.formvar($routing->RouteTo).'</td>';
				$detailTxt .= '<td></td></tr>';
			}
		} catch(BizException $e) {
			$errors[] = $e->getMessage();
		}
		break;
}

// error handling
$err = '';
if( $errors ) foreach( $errors as $error ) {
	$err .= $error.'<br/>';
}
$txt = str_replace( '<!--ERROR-->', $err, $txt );

// generate total page
$txt = str_replace("<!--ROWS-->", $detailTxt, $txt);
if ($issueId > 0) {
	$back = "hppublissues.php?id=$issueId";
} else {
	$back = "hppublications.php?id=$pubId";
}
$txt = str_replace("<!--BACK-->", $back, $txt);
print HtmlDocument::buildDocument($txt);
