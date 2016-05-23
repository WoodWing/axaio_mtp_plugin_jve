<?php
/****************************************************************************
   Copyright 2007-2009 WoodWing Software BV

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
 
class SMS_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'SMS Publishing Connector';
		$info->Version     = '7.0 20091002';
		$info->Description = 'Demo publishing service to Mollie SMS gateway';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 'PubPublishing_EnterpriseConnector');
	}
	
	public function isInstalled()
	{
		require_once dirname(__FILE__).'/config.php';
		return WWSMS_USERNAME != '';
	}
	
	public function runInstallation()
	{
		require_once dirname(__FILE__).'/config.php';
		if( WWSMS_USERNAME == '' || WWSMS_PASSWORD == '' ) {
			$msg = 'Configuration error.';
			$details = 'Please configure SMS account by filling in WWSMS_USERNAME and WWSMS_PASSWORD in '.dirname(__FILE__).'/config.php';
			throw new BizException( null, 'Server', $details, $msg );
		}
	}	
}