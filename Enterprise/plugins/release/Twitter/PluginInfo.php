<?php
/****************************************************************************
   Copyright 2008-2013 WoodWing Software BV

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

require_once BASEDIR . '/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR . '/server/interfaces/plugins/PluginInfoData.class.php';
 
class Twitter_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Twitter Publishing Connector';
		$info->Version     = '10.0.0 Build 1194';
		$info->Description = 'Publishing service to Twitter';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'CustomObjectMetaData_EnterpriseConnector', 'PubPublishing_EnterpriseConnector', 'WebApps_EnterpriseConnector', 'AdminProperties_EnterpriseConnector'
		);
	}

	/**
	 * Required version that is needed to use the plugin
	 *
	 * Note: Doesn't matter which build it is, at the time of writing the build number doesn't get checked.
	 *
	 * @return string
	 */
	public function requiredServerVersion()
	{
		return '9.0.0 Build 0';
	}
}
