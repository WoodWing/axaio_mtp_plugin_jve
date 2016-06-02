<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * 	Integrates ImageMagick to support extra formats for preview generation/
 *  ImageMagick is not that strong in metadata reading, v6.4.4 for example
 *  cannot read XMP from PNG or PSD. Our PHP code can do it better, so 
 *  we have not implemented a Metadata Connector for ImageMagick.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class ImageMagick_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'ImageMagick Preview and Metadata';
		$info->Version     = '10.0.0 Build 1194'; // don't use PRODUCTVERSION
		$info->Description = 'Uses ImageMagick to support extra file formats for preview generation. ';

		// Append ImageMagick & Ghostscript version numbers:
		require_once dirname(__FILE__) . '/ImageMagick.class.php';
		$info->Description .= ImageMagick::getVersions();
		// getVersions() can result in too long description, so limit it to
		// maximum of database field (255 characters)		
		$info->Description = substr($info->Description, 0, 255);
		
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
				'Preview_EnterpriseConnector',
				'MetaData_EnterpriseConnector' );
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