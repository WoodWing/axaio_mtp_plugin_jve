<?php

/****************************************************************************
   Copyright 2013 WoodWing Software BV

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

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class AddSubApplication_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Add Sub Application';
		$info->Version     = '9.0 20130221'; // don't use PRODUCTVERSION
		$info->Description = 'Shows how to add a sub application to Content Station.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			'SysGetSubApplications_EnterpriseConnector',
		);
	}
}
