<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Copyright 2009 WoodWing Software BV Licensed under the Apache
 * License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *  
 *    http://www.apache.org/licenses/LICENSE-2.0
 *  
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
**/

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class EZPublish_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'eZ Publish Connector';
		$info->Version     = 'v8.0 20100818';
		$info->Description = 'Publishing service to eZ Publish';
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
		if (defined('EZPUBLISH_URL') && defined('EZPUBLISH_USERNAME') && defined('EZPUBLISH_PASSWORD'))
		{
			if (strlen(EZPUBLISH_USERNAME) > 0 && strlen(EZPUBLISH_PASSWORD) > 0 && strlen(EZPUBLISH_URL) > 0 && strlen(EZPUBLISH_EXTERNAL_URL) > 0){
				$installed = true;
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