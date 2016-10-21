<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v9.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/WebApps_EnterpriseConnector.class.php';

class WordPress_WebApps extends WebApps_EnterpriseConnector
{
	/**
	 * Tells which web apps are shipped within the server plug-in.
	 *
	 * @return array of WebAppDefinition data objects.
	 */
	final public function getWebApps()
	{
		$apps = array();

		$importApp = new WebAppDefinition();
		$importApp->IconUrl = 'pubchannelicons/32x32.png';
		$importApp->IconCaption = 'WordPress';
		$importApp->WebAppId = 'WordPressConfig';
		$importApp->ShowWhenUnplugged = false;
		$apps[] = $importApp;

		return $apps;
	}

	public function getPrio() { return self::PRIO_DEFAULT; }


}
