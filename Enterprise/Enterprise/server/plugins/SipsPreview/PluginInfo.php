<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * 	Integrates Sips to support extra formats for preview generation/
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class SipsPreview_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Sips Preview';
		$info->Version     = file_get_contents(__DIR__.'/_productversion.txt');
		$info->Description = 'Use Sips command for preview generation';

		// Append Sips version information in description field
		require_once dirname(__FILE__) . '/SipsUtils.class.php';
		$info->Description .= SipsUtils::getVersions();	
		$info->Description = substr($info->Description, 0, 255);
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'Preview_EnterpriseConnector');
	}

	/**
	 * For first time installation, disable this plug-in for Linux and Windows
	 * but enable it for Mac OSX.
	 * See EnterprisePlugin class for more details.
	 *
	 * @since 9.0.0
	 * @return boolean
	 */
	public function isActivatedByDefault()
	{
		return OS == 'UNIX'; // Sips only available for Mac OSX.
	}

	/**
	 * Only allow admin user to enable plug-in for Mac OSX.
	 * See EnterprisePlugin class for more details.
	 *
	 * @since 9.0.0
	 * @throws BizException For Windows or Linux.
	 */
	public function beforeActivation()
	{
		if( OS != 'UNIX' ) { // // Sips only available for Mac OSX.
			throw new BizException( null, 'Client', null, 
				'This plug-in can be enabled for Mac OSX only.' );
		}
	}
}