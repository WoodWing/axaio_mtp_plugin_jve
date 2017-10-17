<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php'; // AdmSection

$ticket = checkSecure('publadmin');

// get section identification (from URL or form)
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// determine incoming mode
if (isset($_REQUEST['vupdate']) && $_REQUEST['vupdate']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}

// get parent ids (from URL or form)
$pubId     = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$channelId = isset($_REQUEST['channelid']) ? intval($_REQUEST['channelid'])  : 0;
$issueId   = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; 

// compose Section data class (from URL or form)
$section = new AdmSection();
$section->Id            = $id ? $id : null;
$section->Name          = isset($_REQUEST['sname']) ? trim($_REQUEST['sname']) : '';
$section->Description   = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
$section->Deadline      = isset($_REQUEST['deadline']) ? $_REQUEST['deadline'] : '';
$section->ExpectedPages = isset($_REQUEST['pages']) ? intval($_REQUEST['pages']) : 0;

// check publication rights
checkPublAdmin($pubId);

// handle request
$errors = array();
try {
	switch ($mode) {
		case 'edit':
			require_once BASEDIR.'/server/services/adm/AdmGetSectionsService.class.php';
			$service = new AdmGetSectionsService();
			$request = new AdmGetSectionsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationId = $pubId;
			$request->IssueId = $issueId;
			$request->SectionIds = array( $id );
			$response = $service->execute( $request );
			$section = $response->Sections[0];
			break;
		case 'update':
			require_once BASEDIR.'/server/services/adm/AdmModifySectionsService.class.php';
			$service = new AdmModifySectionsService();
			$request = new AdmModifySectionsRequest();
			$request->Ticket = $ticket;
			$request->PublicationId = $pubId;
			$request->IssueId = $issueId;
			$request->Sections = array( $section );
			$response = $service->execute( $request );
			$section = $response->Sections[0];
			break;
		case 'insert':
			require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';
			$service = new AdmCreateSectionsService();
			$request = new AdmCreateSectionsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationId = $pubId;
			$request->IssueId = $issueId;
			$request->Sections = array( $section );
			$response = $service->execute( $request );
			$section = $response->Sections[0];
			$id = $section->Id;
			break;
		case 'delete':
			die( 'ERROR: Delete section is handled at hppublications.' );
			break;
	}
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

// delete: back to overview
if ($mode == 'delete' || $mode == 'update' || $mode == 'insert') {
	if ($issueId) {
		header("Location:hppublissues.php?id=$issueId");
		exit();
	} else {
		header("Location:hppublications.php?id=$pubId");
		exit();
	}
}

// generate upper part (edit fields)
$txt = HtmlDocument::loadTemplate( 'hppublsections.htm' );

// error handling
$err = '';
foreach ($errors as $error) {
	$err .= "$error<br>";
}
$txt = str_replace("<!--ERROR-->", $err, $txt);

// Resolve brand's name. TBD: Could be made part of response (WSDL change).
require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
$pubName = DBPublication::getPublicationName( $pubId );

// Resolve issue's name. TBD: Could be made part of response (WSDL change).
if( $issueId > 0 ) { // overrule issue?
	require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
	$issueName = DBIssue::getIssueName( $issueId );
	$txt = str_replace("<!--VAR:ISSUE-->", formvar($issueName).inputvar( 'issue', $issueId, 'hidden' ), $txt);
} else {
	$txt = preg_replace("/<!--IF:ISSUE-->.*?<!--ENDIF:ISSUE-->/s",'', $txt);
}

// Resolve channel's name. TBD: Could be made part of response (WSDL change).
if( $channelId ) {
	require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
	$channel = DBChannel::getPubChannelObj( $channelId );
	$txt = str_replace('<!--VAR:CHANNEL-->', formvar($channel->Name).inputvar('channelid',$channelId,'hidden'), $txt );
} else {
	$txt = preg_replace('/<!--IF:CHANNEL-->.*?<!--ENDIF:CHANNEL-->/s','', $txt);
}

// fields
$txt = str_replace('<!--VAR:NAME-->', '<input maxlength="255" name="sname" value="'.formvar($section->Name).'"/>', $txt );
$txt = str_replace('<!--VAR:PUBL-->', formvar($pubName).inputvar('publ',$pubId,'hidden'), $txt );
$txt = str_replace('<!--VAR:DESCRIPTION-->', inputvar('description', $section->Description, 'area'), $txt );
$txt = str_replace('<!--VAR:PAGES-->', inputvar('pages', $section->ExpectedPages, 'small'), $txt );
$txt = str_replace('<!--VAR:HIDDEN-->', inputvar('id',$id,'hidden'), $txt );

if( $issueId > 0 ) {
	$back = "hppublissues.php?id=$issueId";
} else {
	$back = "hppublications.php?id=$pubId";
}
$txt = str_replace("<!--BACK-->", $back, $txt);

//set focus to the first field
$txt .= "<script language='javascript'>document.forms[0].sname.focus();</script>";

// generate total page
print HtmlDocument::buildDocument($txt);
