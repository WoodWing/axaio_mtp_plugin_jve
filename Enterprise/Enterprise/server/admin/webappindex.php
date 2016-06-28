<?php
/**
 * Loads and shows a web apps shipped with server plug-ins.
 * Accepts the following HTTP parameters:
 * - plugintype Where to find the server plug-in: 'config' or 'server'.
 * - webappid: The application id unique within the server plug-in.
 * - pluginname: Internal name of the server plug-in that ships the web app.
 *
 * @package 	Enterprise
 * @subpackage 	Core
 * @since 		v8.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once '../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Retrieve HTTP params and validate for security reasons.
$pluginType = @$_GET['plugintype'];
if( $pluginType != 'config' && $pluginType != 'server' ) {
	print '<font color="red">ERROR: </font>Invalid value for "plugintype" param given.';
	exit;
}
$webAppId = @$_GET['webappid'];
$safeWebAppId = preg_replace( '/[^a-zA-Z0-9_]+/', '', $webAppId );
if( $safeWebAppId != $webAppId ) {
	print '<font color="red">ERROR: </font>Invalid value for "webappid" param given.';
	exit;
}
$pluginName = @$_GET['pluginname'];
$safePluginName = preg_replace( '/[^a-zA-Z0-9_]+/', '', $pluginName );
if( $safePluginName != $pluginName ) {
	print '<font color="red">ERROR: </font>Invalid value for "pluginname" param given.';
	exit;
}

// TODO: start session (app convenience)

// Compose the application class name, for example: AdminWebAppsSample_App2_EnterpriseWebApp
$appClassName = $pluginName . '_' . $webAppId . '_EnterpriseWebApp';
$pluginPath = BASEDIR.'/'.$pluginType.'/plugins/'.$pluginName;
$includePath = $pluginPath.'/webapps/'.$appClassName.'.class.php';

// Initialize the application.
if( !file_exists( $includePath ) ) {
	print '<font color="red">ERROR: </font>No such PHP module available: '.$includePath;
	exit;
}
require_once $includePath;
$webApp = new $appClassName;

// Check if the plugin is enabled and get the web apps connector.
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
$pluginObj = BizServerPlugin::getPluginForConnector( $pluginName.'_WebApp' );
$errMsg = '';
$connector = null;
if ( !$pluginObj || !$pluginObj->IsInstalled ) {
	$errMsg = 'The server plug-in "'.$pluginName.'" is not installed! '; // should never happen
} else {
	$connector = BizServerPlugin::searchConnectorByPluginId( 'WebApps', null, $pluginObj->Id, false );
	if( !$connector ) {
		$errMsg = 'Connector not found in server plug-in "'.$pluginObj->DisplayName.'".';
	}
}
if( $errMsg ) {
	print '<font color="red">ERROR: </font>'.$errMsg;
	exit;
}

// Ask the connector for all its web apps and lookup the requested web app.
$webAppFound = false;
if( $connector ) {
	$webAppDefs = $connector->getWebApps();
	if( $webAppDefs ) foreach( $webAppDefs as $webAppDef ) {
		if( $webAppDef->WebAppId == $webAppId ) {
			if( $pluginObj->IsActive || $webAppDef->ShowWhenUnplugged ) {
				$webAppFound = true;
				break; // found
			} else {
				// redirect !
				header( 'Location: '.NORIGHT );
				exit();
			}
		}
	}
}
if( !$webAppFound ) {
	print '<font color="red">ERROR: </font>Could not find web app id "'.$webAppId.'".';
	exit;
}

// Check user access rights to page (redirect when no rights)
$accessType = $webApp->getAccessType();
if( $accessType != 'admin' && $accessType != 'publadmin' ) {
	print '<font color="red">ERROR: </font>'.$errMsg;
	exit;
}
checkSecure( $accessType );

// TODO: load resource strings shipped with the server plug-in (in user's language).

// Load generic HTML template for the web app.
$tpl = HtmlDocument::loadTemplate( 'webapptemplate.htm' );

// Get JS files to include (optional)
$jsUrls = array();
$jsIncludes = $webApp->getJavaScriptIncludes();
if( $jsIncludes ) foreach( $jsIncludes as $jsInclude ) {
	$jsUrls[] = '../../'.$pluginType.'/plugins/'.$pluginName.'/'.$jsInclude;

}

// Get StyleSheet files to include (optional)
$cssUrls = array();
$cssIncludes = $webApp->getStyleSheetIncludes();
if( $cssIncludes ) foreach( $cssIncludes as $cssInclude ) {
	$cssUrls[] = '../../'.$pluginType.'/plugins/'.$pluginName.'/'.$cssInclude;
}

// Add icon to web page.
if( $webAppDef->IconUrl ) {
	if( strpos( $webAppDef->IconUrl, 'data:' ) === 0 ) {
		$iconUrl = $webAppDef->IconUrl;
	} else {
		$iconUrl = '../../'.$pluginType.'/plugins/'.$pluginName.'/'.$webAppDef->IconUrl;
	}
	$tpl = str_replace ( '<!--SERVERPLUGIN_APPICON-->', $iconUrl, $tpl );
}

// Add title to web page.
$title = $webApp->getTitle();
if( $title ) {
	$tpl = str_replace ( '<!--SERVERPLUGIN_APPTITLE-->', $title, $tpl );
}

// Let the web app build the page body.
$tpl = str_replace ( '<!--SERVERPLUGIN_HTMLBODY-->', $webApp->getHtmlBody(), $tpl );

// Embed hidden web application identification required to round-trip form submits.
$hiddenParams = inputvar( 'webappid', $webAppId, 'hidden' );
$hiddenParams .= inputvar( 'plugintype', $pluginType, 'hidden' );
$hiddenParams .= inputvar( 'pluginname', $pluginName, 'hidden' );
$tpl = str_replace('<!--VAR:WEBAPPINDEX_HIDDENPARAMS-->', $hiddenParams, $tpl );

// Show the HTML page to user and optionally invoke main menu.
print HtmlDocument::buildDocument( $tpl, $webApp->isEmbedded(), null, false, false, false, $cssUrls, $jsUrls );
