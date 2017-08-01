<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Check for brand access rights.
global $isadmin;
global $ispubladmin;
checkSecure('publadmin');

// Load HTML template.
$tpl = HtmlDocument::loadTemplate( 'serverapps.htm' );

// For brand admins, hide system admin icons.
if( $isadmin && $ispubladmin ) {
	$tpl = str_replace( '<!--PAR:SHOW_FOR_SYS_ADMIN_ONLY-->', '', $tpl );
} else {
	$tpl = str_replace( '<!--PAR:SHOW_FOR_SYS_ADMIN_ONLY-->', 'display:none; ', $tpl );
}

$webApps = array();

// RabbitMQ is not a plugin, but it also needs an app icon (that refers to its admin pages).
require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
if( BizMessageQueue::isInstalled() ) {
	$connection = BizMessageQueue::getConnection( 'RabbitMQ', 'REST', false );
	if( $connection ) {
		$webApps[] = array(
			'url' => $connection->Url,
			'icon' => '../../config/images/rabbitmq.png',
			'title' => 'RabbitMQ',
			'target' => '_blank'
		);
	}
}

// Collect admin web apps (icons) provided by server plug-in.
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
$connRetVals = array();
BizServerPlugin::runDefaultConnectors( 'WebApps', null, 'getWebApps', array(), $connRetVals, false );
require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';
$webAppDefs = array();
foreach( $connRetVals as $connName => $connRetVal ) {
	/** @var WebAppDefinition $webAppDef */
	foreach( $connRetVal as $webAppDef ) {
		$pluginName = BizServerPlugin::getPluginUniqueNameForConnector( $connName );
		$pluginObj = BizServerPlugin::getPluginForConnector( $connName );
		$pluginType = $pluginObj->IsSystem ? 'server' : 'config';
		if( $pluginObj->IsActive || $webAppDef->ShowWhenUnplugged ) {
			if( !$isadmin && $webAppDef->AccessType == 'admin' ) {
				continue; // for brand admins, hide system admins apps EN-89484
			}
			$params = 'webappid=' . $webAppDef->WebAppId . '&plugintype=' . $pluginType . '&pluginname=' . $pluginName;
			$webAppUrl = '../../server/admin/webappindex.php?' . $params;
			if( strpos( $webAppDef->IconUrl, 'data:' ) === 0 ) {
				$iconUrl = $webAppDef->IconUrl;
			} else {
				$iconUrl = '../../' . $pluginType . '/plugins/' . $pluginName . '/' . $webAppDef->IconUrl;
			}

			$webApps[] = array(
				'url' => $webAppUrl,
				'icon' => $iconUrl,
				'title' => $webAppDef->IconCaption,
				'target' => '_self'
			);
		}
	}
}

// Show admin web apps (icons) provided by server plug-in.
if( $webApps ) {
	
	// Read web app icon table row definition from template.
	$rowSectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'ADMINWEBAPPROW' );
	$rowSectionTpl = $rowSectionObj->getSection( $tpl );

	// Read web app icon definition from template.
	$appSectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'ADMINWEBAPP' );
	$appSectionTpl = $appSectionObj->getSection( $tpl );
	
	// Iterate through all custom web apps and add them dynamically to the HTML page.
	$allTxt = ''; // all table rows (of web app icons)
	$rowTxt = ''; // web apps on a table row
	foreach( $webApps as $index => $webApp ) {
		
		// Fill in the web app icon attributes.
		$appTxt = $appSectionTpl;
		$appTxt = str_replace( '<!--PAR:WEBAPP_WEBAPPURL-->', $webApp['url'], $appTxt );
		$appTxt = str_replace( '<!--PAR:WEBAPP_ICONURL-->', $webApp['icon'], $appTxt );
		$appTxt = str_replace( '<!--PAR:WEBAPP_ICONCAPTION-->', $webApp['title'], $appTxt );
		$appTxt = str_replace( '<!--PAR:WEBAPP_URLTARGET-->', $webApp['target'], $appTxt );

		
		// Add web app icon to table row.
		$rowTxt .= $appTxt;
		
		// Start new table row for each 4th web app icon.
		if( $index == count($webApps)-1 || // last app of all?
			($index+1) % 4 == 0) { // last app on row?
			$allTxt .= $appSectionObj->replaceSection( $rowSectionTpl, $rowTxt );
			$rowTxt = '';
		}
	}
	
	// Put whole table with web app icons in the HTML doc.
	$tpl = $rowSectionObj->replaceSection( $tpl, $allTxt );
	
} else { // when no web apps found, hide the entire HTML table row
	$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'ADMINWEBAPPROW' );
	$tpl = $sectionObj->replaceSection( $tpl, '' );
}

// Show built HTML page to admin user.
print HtmlDocument::buildDocument( $tpl );
