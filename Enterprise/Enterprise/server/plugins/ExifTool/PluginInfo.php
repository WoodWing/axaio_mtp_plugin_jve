<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v10.1
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This plugin integrates the ExifTool to extract embedded metadata properties
 * from saved or uploaded files. This info is used to enrich object properties.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

class ExifTool_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{
		$info = new PluginInfoData();
		$info->DisplayName = 'ExifTool Metadata';
		$info->Version     = getProductVersion(__DIR__);
		$info->Description = 'Integrates the ExifTool to extract embedded metadata properties '.
			'from saved or uploaded files. This info is used to enrich object properties. ';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}

	final public function getConnectorInterfaces()
	{
		return array( 'MetaData_EnterpriseConnector' );
	}
}
