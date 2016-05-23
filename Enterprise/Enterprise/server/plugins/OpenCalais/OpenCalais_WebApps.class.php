<?php
/**
 * Admin web application to configure this plugin.
 *
 * Called by the core once the application icon on the Integrations admin page is clicked.
 *
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/WebApps_EnterpriseConnector.class.php';

class OpenCalais_WebApps extends WebApps_EnterpriseConnector
{
	/**
	 * Shows the WebApp to use for configuring the OpenCalais plugin.
	 *
	 * @see WebApps_EnterpriseConnector.class.php
	 * @return WebAppDefinition[] An array of WebAppDefinition data objects.
	 */
	final public function getWebApps()
	{
		$apps = array();
		
		$configApp = new WebAppDefinition();
		$configApp->IconUrl = 'webapps/32x32.png';
		$configApp->IconCaption = 'OpenCalais';
		$configApp->WebAppId  = 'Configuration';
		$configApp->ShowWhenUnplugged = false;
		$apps[] = $configApp;
		
		return $apps;
	}
}
