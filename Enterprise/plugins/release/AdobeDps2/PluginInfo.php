<?php
/**
 * @package     Enterprise
 * @subpackage  AdobeDps2
 * @since       v9.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Information class of the AdobeDps2 server plugin.
 */
require_once dirname(__FILE__).'/config.php'; // DPS2_PLUGIN_DISPLAYNAME
require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class AdobeDps2_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = DPS2_PLUGIN_DISPLAYNAME;
		$info->Version     = '10.0 Build 5209'; // don't use PRODUCTVERSION
		$info->Description = 'Integrates with '.DPS2_PLUGIN_DISPLAYNAME.'.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			// publishing:
			'PubPublishing_EnterpriseConnector',
			'ServerJob_EnterpriseConnector',
			'FileStore_EnterpriseConnector',
			
			// administration:
			'WebApps_EnterpriseConnector',
			'AdminProperties_EnterpriseConnector',
			'CustomObjectMetaData_EnterpriseConnector',

			// workflow hooks:
			'WflCreateObjects_EnterpriseConnector',
			'WflSaveObjects_EnterpriseConnector',
			'WflSetObjectProperties_EnterpriseConnector',
			'WflMultiSetObjectProperties_EnterpriseConnector',
			'WflGetDialog2_EnterpriseConnector',
			'WflCopyObject_EnterpriseConnector',
		);
	}

	/**
	 * Required Enterprise Server version that is needed to use the plugin.
	 *
	 * Note that the Server Jobs mechanism was improved and made ready to be used in context 
	 * of workflow services since 9.4. The AdobeDps2 plugin relies on this.
	 *
	 * @return string
	 */
	public function requiredServerVersion()
	{
		return '9.4.0 Build 0';
	}
}