<?php
/**
 * Admin web application to configure this plugin. Called by core once opened by admin user
 * through app icon shown at the the Integrations admin page.
 *
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/WebApps_EnterpriseConnector.class.php';

class MultiChannelPublishingSample_WebApps extends WebApps_EnterpriseConnector
{
	final public function getWebApps()
	{
		$apps = array();
		
		$importApp = new WebAppDefinition();
		$importApp->IconUrl = 'webapps/importdefs_32.gif';
		$importApp->IconCaption = 'Import Definitions';
		$importApp->WebAppId  = 'ImportDefinitions';
		$importApp->ShowWhenUnplugged = false;
		$apps[] = $importApp;
		
		return $apps;
	}
}
