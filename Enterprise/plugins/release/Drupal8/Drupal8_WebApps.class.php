<?php
/**
 * Admin web application to configure this plugin.
 *
 * Called by the core once the application icon on the Integrations admin page is clicked.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/WebApps_EnterpriseConnector.class.php';

class Drupal8_WebApps extends WebApps_EnterpriseConnector
{
	/**
	 * Tells which web apps are shipped with the Drupal8 Plug-in.
	 *
	 * @see WebApps_EnterpriseConnector.class.php
	 * @return WebAppDefinition[] An array of WebAppDefinition data objects.
	 */
	final public function getWebApps()
	{
		$apps = array();
		
		$importApp = new WebAppDefinition();
		$importApp->IconUrl = 'webapps/importdefs_32.gif';
		$importApp->IconCaption = 'Drupal 8';
		$importApp->WebAppId  = 'ImportDefinitions';
		$importApp->ShowWhenUnplugged = false;
		$apps[] = $importApp;
		
		return $apps;
	}
}
