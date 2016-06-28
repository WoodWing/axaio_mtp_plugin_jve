<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.4
 * @copyright	2008-2011 WoodWing Software bv. All Rights Reserved.
 *
 * Aspell spelling and suggestions integration (via command shell) - The Server Plug-in class
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
 
class AspellShellSpelling_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Aspell Spelling';
		$info->Version     = '7.4.0 Build 0';
		$info->Description = 'Aspell spelling and suggestions integration via command shell.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'Spelling_EnterpriseConnector' ); 
	}
	
	public function isInstalled()
	{
		return $this->checkInstallation( false );
	}
	
	public function runInstallation() 
	{
		$this->checkInstallation( true );

	}
	
	private function checkInstallation( $raiseException )
	{
		// Server v7.4.0 is required. Check version.
		$serverVer = explode( ' ', SERVERVERSION ); // split '7.4.0' from 'build 123'
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		if( VersionUtils::versionCompare( $serverVer[0], '7.4.0', '<' ) ) {
			if( $raiseException ) {
				throw new BizException( null, 'Server', 'Enterprise Server version v7.4 (or higher) is required for this plug-in. ',
					'Server plug-in not installed.' );
			} else {
				return false;
			}
		}

		// Validate spelling configuration against spelling installation
		$className = get_class( $this );
		$pluginName = explode( '_', $className );
		$pluginName = $pluginName[0];
		try {
			$bizSpelling = new BizSpelling();
			$bizSpelling->validateSpellingConfiguration( $pluginName, false /* $showInstalledOnly */ );
		} catch( BizException $e ) {
			if( $raiseException ) {
				throw new BizException ( null, 'Server', 
				$e->getDetail(),
				'Server plug-in not installed.' );
			} else { 
				return false; 
			}
		}
		return true;
	}
}