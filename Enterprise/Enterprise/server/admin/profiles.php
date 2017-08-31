<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

$ticket = checkSecure('admin');

$records = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;
if( $records > 0 ) {
	$accessProfiles = array();
	for ($i = 1; $i < $records; $i++) {
		$accessProfile = new AdmAccessProfile();
		$accessProfile->Id = intval( $_REQUEST["order$i"] );
		$accessProfile->SortOrder = intval( $_REQUEST["code$i"] );
		$accessProfiles[] = $accessProfile;
	}
	try {
		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';
		$request = new AdmModifyAccessProfilesRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->AccessProfiles = $accessProfiles;
		$service = new AdmModifyAccessProfilesService();
		$service->execute( $request );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
}

$txt = '';
$cnt = 1;
$accessProfiles = array();
try {
	require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';
	$request = new AdmGetAccessProfilesRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$request->AccessProfileIds = null;
	$service = new AdmGetAccessProfilesService();
	$response = $service->execute( $request );
	$accessProfiles = $response->AccessProfiles;
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

foreach( $accessProfiles as $accessProfile ) {
	//echo '<pre>'; print_r( $accessProfile ); echo '</pre>';
	$bx = inputvar("code$cnt", $accessProfile->SortOrder, 'small').inputvar( "order$cnt", $accessProfile->Id, 'hidden');
	$txt .= '<tr><td><a href="hpprofiles.php?id='.$accessProfile->Id.'">'.formvar( $accessProfile->Name ).'</a></td>'.
			'<td>'.$bx.'</td><td>'.formvar( $accessProfile->Description ).'</td></tr>'."\r\n";
	$cnt++;
}
$txt .= inputvar( 'recs', $cnt, 'hidden' );

//generate page
$txt = str_replace('<!--ROWS-->', $txt, HtmlDocument::loadTemplate( 'profiles.htm' ));
$txt = str_replace('<!--HEADER-->', '<th>'.BizResources::localize('OBJ_DESCRIPTION').'</th>', $txt);
print HtmlDocument::buildDocument($txt);
