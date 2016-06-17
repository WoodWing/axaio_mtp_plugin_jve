<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This is the callback module that plays role in the Analytics authorization procedure.
 * After landing here it converts the code and redirects to the Analytics maintenance page
 */

if( file_exists('../../../config/config.php') ) {
    require_once '../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
    require_once '../../../Enterprise/config/config.php';
}

require_once BASEDIR . '/server/secure.php';
$ticket = checkSecure('admin');
BizSession::startSession( $ticket );

include_once dirname(__FILE__).'/AnalyticsRestClient.class.php';
require_once dirname(__FILE__).'/Analytics_Utils.class.php';
$errorMsg = null;
try {
	// Get the Access Token from Analytics.
	$client = new AnalyticsRestClient();
	$client->getAccessTokenGivenCode( $_GET );
	Analytics_Utils::storeIsRegistered( true );
} catch( BizException $e ) {
	$errorMsg = $e->getMessage();
	// L> Note: We don't display details here for security reason.
	Analytics_Utils::storeIsRegistered( false );
}

// Redirecting to Analytics webapp page.
$url = SERVERURL_ROOT . INETROOT .'/server/admin/webappindex.php?webappid=IssueExportDefinitions&plugintype=config&pluginname=Analytics';
if( $errorMsg ) {
	$url .= '&register_errormsg='.urlencode($errorMsg);
} else {
	$url .= '&register_infomsg='.urlencode( BizResources::localize("REGISTRATION_SUCCESSFUL") );
}
LogHandler::Log( 'AnalyticsRestClient', 'INFO', 'Redirecting to Analytics admin page of Enterprise Server:' . $url );
header('Location: ' . $url );
