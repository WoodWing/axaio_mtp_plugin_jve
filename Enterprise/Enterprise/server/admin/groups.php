<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/bizclasses/BizLDAP.class.php';
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('admin');

// Request for all user groups (system wide).
$groups = null;
try {
	require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
	$service = new AdmGetUserGroupsService();
	$request = new AdmGetUserGroupsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$response = $service->execute( $request );
	$groups = $response->UserGroups;
} catch( BizException $e ) {
	// no error handling; having access, nothing could go wrong while listing groups
}

// Build HTML table for the retrieved user groups.
$htmlTableRows = '';
if( $groups ) foreach( $groups as $group ) {

	$admin = $group->Admin ? CHECKIMAGE : '&nbsp;';
	$routing = $group->Routing ?  CHECKIMAGE : '&nbsp;';
	$userCount = DBUser::countUsersInGroup( $group->Id ); 
	// L> TBD: Instead of calling DB layer, the user count could be requested through
	//         the RequestModes parameter and returned through the response (WSDL change).

	$htmlTableRows .= 
		'<tr>'.
			'<td><a href="hpgroups.php?id='.$group->Id.'">'.formvar($group->Name).'</a></td>'.
			'<td>'.formvar($group->Description).'</td><td>'.$admin.'</td>'.
			'<td>'.$routing.'</td><td>'.$userCount.'</td>'.
		'</tr>'."\r\n";
}

$ldap = BizLDAP::isInstalled();

// Compose the HTML page.
$txt = HtmlDocument::loadTemplate( 'groups.htm' );
$txt = str_replace( '<!--ROWS-->', $htmlTableRows, $txt );
$txt = str_replace( '<!--SHOW_IMPORT_BTN-->', $ldap ? 'true' : 'false', $txt );
print HtmlDocument::buildDocument($txt);
