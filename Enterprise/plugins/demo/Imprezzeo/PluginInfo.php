<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 *
 * Imprezzeo Search Connector
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

// Plugin config file:
require_once dirname(__FILE__) . '/config.php';
 
class Imprezzeo_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Imprezzeo';
		$info->Version     = '7.0 20091224'; // don't use PRODUCTVERSION
		$info->Description = 'Integrates Imprezzeo search engine.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'Search_EnterpriseConnector' ); 
	}
	
	public function isInstalled()
	{
		if (defined("IMPREZZEO_INSTALLED")) {
			return IMPREZZEO_INSTALLED;
		}
		
		return false;
	}
}