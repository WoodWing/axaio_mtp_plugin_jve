<?php

/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

class ContentStationOverruleCompatibility_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'Content Station Overrule Compatibility';
		$info->Version     = getProductVersion(__DIR__);
		$info->Description = 'Makes the overrule option compatible with Content Station';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array(
			'WflCopyObject_EnterpriseConnector',
			'WflCreateObjects_EnterpriseConnector',
			'WflGetDialog2_EnterpriseConnector',
			'WflGetObjects_EnterpriseConnector',
			'WflLogOn_EnterpriseConnector',
			'WflNamedQuery_EnterpriseConnector',
			'WflQueryObjects_EnterpriseConnector',
			'WflSaveObjects_EnterpriseConnector',
			'WflSetObjectProperties_EnterpriseConnector',
			'WflMultiSetObjectProperties_EnterpriseConnector'
		);
	}
	
	/**
	 * For first time installation, disable this plug-in.
	 * See EnterprisePlugin class for more details.
	 *
	 * @since 9.0.0
	 * @return boolean
	 */
	public function isActivatedByDefault()
	{
		return false;
	}
}
