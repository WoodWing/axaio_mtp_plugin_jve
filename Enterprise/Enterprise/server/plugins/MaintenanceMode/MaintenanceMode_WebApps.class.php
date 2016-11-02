<?php
/**
 * @package 	Enterprise
 * @subpackage MaintenanceMode
 * @since 		v10.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Gives a system administrator the opportunity to disable the log on of normal, non-system, users.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/WebApps_EnterpriseConnector.class.php';

class MaintenanceMode_WebApps extends WebApps_EnterpriseConnector
{
	/**
	 * Tells which web apps are shipped within the server plug-in.
	 *
	 * @return array of WebAppDefinition data objects.
	 */
	public function getWebApps() 
	{
		$appDefinition = new WebAppDefinition();
		$appDefinition->IconUrl = 'webapps/woodwing.png'; //Todo Need a different icon?
		$appDefinition->IconCaption = 'Maintenance Mode';
		$appDefinition->WebAppId  = 'Configure';
		$appDefinition->ShowWhenUnplugged = true;
		$apps[] = $appDefinition;

		return $apps;		
	}

	public function getPrio() { return self::PRIO_DEFAULT; }
}
