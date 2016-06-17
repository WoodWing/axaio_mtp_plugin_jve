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

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
require_once dirname(__FILE__) . '/config.php';
 
class DuplicateImageWarning_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = basename(dirname(__FILE__));
		$info->Version     = "v1.0"; // don't use PRODUCTVERSION
		$info->Description = basename(dirname(__FILE__));
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 	
			// 'WflChangeOnlineStatus_EnterpriseConnector',
			// 'WflChangePassword_EnterpriseConnector',
			// 'WflCopyObject_EnterpriseConnector',
			 'WflCreateObjectRelations_EnterpriseConnector',
			// 'WflCreateObjects_EnterpriseConnector',
			// 'WflCreateObjectTargets_EnterpriseConnector',
			// 'WflDeleteObjectRelations_EnterpriseConnector',
			// 'WflDeleteObjects_EnterpriseConnector',
			// 'WflDeleteObjectTargets_EnterpriseConnector',
			// 'WflGetDialog_EnterpriseConnector',
			// 'WflGetObjectRelations_EnterpriseConnector',
			// 'WflGetObjects_EnterpriseConnector',
			// 'WflGetPages_EnterpriseConnector',
			// 'WflGetPagesInfo_EnterpriseConnector',
			// 'WflGetServers_EnterpriseConnector',
			// 'WflGetStates_EnterpriseConnector',
			// 'WflGetVersion_EnterpriseConnector',
			// 'WflListVersions_EnterpriseConnector',
			// 'WflLogOff_EnterpriseConnector',
			// 'WflLogOn_EnterpriseConnector',
			// 'WflNamedQuery_EnterpriseConnector',
			// 'WflQueryObjects_EnterpriseConnector',
			// 'WflRestoreVersion_EnterpriseConnector',
			 'WflSaveObjects_EnterpriseConnector',
			// 'WflSendMessages_EnterpriseConnector',
			// 'WflSendTo_EnterpriseConnector',
			// 'WflSetObjectProperties_EnterpriseConnector',
			// 'WflUnlockObjects_EnterpriseConnector',
			// 'WflUpdateObjectRelations_EnterpriseConnector',
			// 'WflUpdateObjectTargets_EnterpriseConnector',

		);
	}
}

//
// 	Create implementations
//	


foreach (DuplicateImageWarning_EnterprisePlugin::getConnectorInterfaces() as $service)
{
	$template = file_get_contents(dirname(__FILE__) . '/Connector.template.php');
	$name = str_replace(' ', '', DuplicateImageWarning_EnterprisePlugin::getPluginInfo()->DisplayName);
	$bareservice = str_replace('Wfl', '', $service);
	$bareservice = str_replace('_EnterpriseConnector', '', $bareservice);
	$template = str_replace('%service%', $bareservice, $template);
	$template = str_replace('%plugin%', $name, $template);
	if (!file_exists(dirname(__FILE__) . "/".$name."_Wfl".$bareservice.".class.php"))
		file_put_contents(dirname(__FILE__) . "/".$name."_Wfl".$bareservice.".class.php", $template);
}
