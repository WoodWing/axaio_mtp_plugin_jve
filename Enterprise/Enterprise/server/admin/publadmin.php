<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('publadmin');

$errors = array();

// determine incoming mode
$pubId = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$grp = isset($_REQUEST['grp']) ? intval($_REQUEST['grp']) : 0;
$mode = ($grp > 0) ? 'insert' : 'addgrp';

// check publication rights
checkPublAdmin($pubId);

// handle request
switch( $mode ) {
	case 'insert':
		try {
			require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';
			$request = new AdmCreatePublicationAdminAuthorizationsRequest();
			$request->Ticket = $ticket;
			$request->PublicationId = $pubId;
			$request->UserGroupIds = array( $grp );
			$service = new AdmCreatePublicationAdminAuthorizationsService();
			$service->execute( $request );
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
		}

		// return to publ page
		if( !$errors ) {
			header("Location:hppublications.php?id=$pubId");
			exit;
		}
}

// generate upper part (edit fields)
try {
	require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
	$request = new AdmGetPublicationsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$request->PublicationIds = array( $pubId );
	$service = new AdmGetPublicationsService();
	$response = $service->execute( $request );
	$pubName = $response->Publications[0]->Name;
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
}

$groupNames = listAdminUserGroupNamesWithNoAccessToPubId( $ticket, $pubId );
$combo = '<select name="grp">';
if( $groupNames ) foreach( $groupNames as $groupId => $groupName ) {
	$combo .= '<option value="'.$groupId.'">'.formvar($groupName).'</option>';
}
$combo .= '</select>';
$combo .= inputvar( 'publ', $pubId, 'hidden' );
$txt = HtmlDocument::loadTemplate( 'publadmin.htm' );

// Pre-translate the ACT_GRANT_ADMIN_RIGHTS key to fill in the publication name
$msg = BizResources::localize( 'ACT_GRANT_ADMIN_RIGHTS' );
$msg = str_replace( '%', $pubName, $msg );
$txt = str_replace( '<!--RES:ACT_GRANT_ADMIN_RIGHTS-->', formvar($msg), $txt );
$txt = str_replace('<!--COMBO-->', $combo, $txt );
$txt = str_replace('<!--ID-->', $pubId, $txt);

$err = '';
if( $errors ) foreach( $errors as $error ) {
	$err .= $error.'<br/>';
}
$txt = str_replace( '<!--ERROR-->', $err, $txt );

// generate total page
print HtmlDocument::buildDocument($txt);

/**
 * Lists the admin user group names that have no access to a given brand.
 *
 * @param string $ticket User session ticket.
 * @param integer $pubId The brand to collect groups for.
 * @return array User group names, sorted by name, indexed by group id.
 */
function listAdminUserGroupNamesWithNoAccessToPubId( $ticket, $pubId )
{
	$groupNames = array();
	try {
		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
		$request = new AdmGetUserGroupsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$service = new AdmGetUserGroupsService();
		$response = $service->execute( $request );
		$userGroups = $response->UserGroups;

		require_once BASEDIR.'/server/services/adm/AdmGetPublicationAdminAuthorizationsService.class.php';
		$request = new AdmGetPublicationAdminAuthorizationsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationId = $pubId;
		$service = new AdmGetPublicationAdminAuthorizationsService();
		$response = $service->execute( $request );
		$pubAdminUserGroupIds = $response->UserGroupIds;

		$groupNames = array();
		foreach( $userGroups as $userGroup ) {
			if( !in_array( $userGroup->Id, $pubAdminUserGroupIds ) ) {
				$groupNames[$userGroup->Id] = $userGroup->Name;
			}
		}
	} catch( BizException $e ) {
		$this->errors[] = $e->getMessage();
	}

	return $groupNames;
}
