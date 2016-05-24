<?php
/**
 * @package     Enterprise
 * @subpackage  Analytics
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Admin web application to configure this plugin.
 *
 * Called by the core once the application icon on the Integrations admin page is clicked.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/WebApps_EnterpriseConnector.class.php';

class Analytics_WebApps extends WebApps_EnterpriseConnector
{
	/**
	 * Tells which web apps are shipped with the Analytics Plug-in.
	 *
	 * @see WebApps_EnterpriseConnector.class.php
	 * @return WebAppDefinition[] An array of WebAppDefinition data objects.
	 */
	final public function getWebApps()
	{
		$apps = array();
		
		$exportApp = new WebAppDefinition();
		$exportApp->IconUrl = 'webapps/analytics_32.png';
		$exportApp->IconCaption = 'Analytics';
		$exportApp->WebAppId  = 'IssueExportDefinitions';
		$exportApp->ShowWhenUnplugged = false;
		$apps[] = $exportApp;
		
		return $apps;
	}
}
