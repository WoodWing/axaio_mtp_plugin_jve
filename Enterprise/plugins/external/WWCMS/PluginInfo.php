<?php

/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class Drupal_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Publish to DRUPAL';
		$info->Version     = '6.1.3 Build 141'; // don't use PRODUCTVERSION
		$info->Description = 'Publishing service to DRUPAL';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'PubPublishing_EnterpriseConnector');
	}
	
	/**
	 * Checks if this plug-in is configured/installed correctly.
	 * 
	 * @return boolean true if configuration is OK.
	 */
	public function isInstalled()
	{
		$installed = false;
		// load config
		require_once dirname(__FILE__) . '/config.php';
		// check if values have been entered
		if (defined('DRUPAL_URL') && defined('DRUPAL_USERNAME') && defined('DRUPAL_PASSWORD'))
		{
			// check url (start with http(s):// and end with /)
			$result = preg_match('|^https?://.*/$|', DRUPAL_URL);
			if ($result !== FALSE && $result == 1){
				if (strlen(DRUPAL_USERNAME) > 0 && strlen(DRUPAL_PASSWORD) > 0){
					$installed = true;
				}
			}
		}
		return $installed;
	}
	
	/**
	 * Checks if this plug-in is configured/installed correctly.
	 * Throws a BizException if it's not correct.
	 */
	public function runInstallation()
	{
		if (!$this->isInstalled()){
			$msg = 'Configuration of this plug-in is not done or not correct in "' . dirname(__FILE__) . '/config.php' . '"';
			throw new BizException('' , 'Server', null, $msg);
		}
	}
}