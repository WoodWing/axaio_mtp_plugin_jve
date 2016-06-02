<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

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
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class NYTimes_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'New York Times';
		$info->Version     = '7.0 20091020'; // don't use PRODUCTVERSION
		$info->Description = "Implements a Content Source using the NY Times API's. The Article Search and Newswire API's are available as searches";
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'ContentSource_EnterpriseConnector' ); 
	}
	
	public function isInstalled()
	{
		require_once dirname(__FILE__) . '/NYTimes_ContentSource.class.php';
		$conn = new NYTimes_ContentSource();
		return $conn->isInstalled();
	}
	
	public function runInstallation()
	{
		require_once dirname(__FILE__) . '/NYTimes_ContentSource.class.php';
		$conn = new NYTimes_ContentSource();
		$conn->runInstallation();
	}
}