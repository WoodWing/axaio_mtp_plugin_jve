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

class PrintChannelFilter_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Print Channel Filter';
		$info->Version     = '9.0 20130216'; // don't use PRODUCTVERSION
		$info->Description = 'Filters out issues in workflow dialogs for Layout objects. '.
			'Only Issues are listed of type Print. '.
			'And more filtering options are possible, as written in the readme.txt file of this plug-in.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(
			// 'WflChangeOnlineStatus_EnterpriseConnector',
			// 'WflChangePassword_EnterpriseConnector',
			// 'WflCheckSpelling_EnterpriseConnector',
			// 'WflCheckSpellingAndSuggest_EnterpriseConnector',
			// 'WflCopyObject_EnterpriseConnector',
			// 'WflCreateArticleWorkspace_EnterpriseConnector',
			// 'WflCreateObjectRelations_EnterpriseConnector',
			// 'WflCreateObjects_EnterpriseConnector',
			// 'WflCreateObjectTargets_EnterpriseConnector',
			// 'WflDeleteArticleWorkspace_EnterpriseConnector',
			// 'WflDeleteObjectRelations_EnterpriseConnector',
			// 'WflDeleteObjects_EnterpriseConnector',
			// 'WflDeleteObjectTargets_EnterpriseConnector',
			// 'WflGetArticleFromWorkspace_EnterpriseConnector',
			'WflGetDialog2_EnterpriseConnector',
			// 'WflGetObjectRelations_EnterpriseConnector',
			// 'WflGetObjects_EnterpriseConnector',
			// 'WflGetPages_EnterpriseConnector',
			// 'WflGetPagesInfo_EnterpriseConnector',
			// 'WflGetServers_EnterpriseConnector',
			// 'WflGetStates_EnterpriseConnector',
			// 'WflGetSuggestions_EnterpriseConnector',
			// 'WflGetVersion_EnterpriseConnector',
			// 'WflListArticleWorkspaces_EnterpriseConnector',
			// 'WflListVersions_EnterpriseConnector',
			// 'WflLogOff_EnterpriseConnector',
			// 'WflLogOn_EnterpriseConnector',
			// 'WflNamedQuery_EnterpriseConnector',
			// 'WflPreviewArticleAtWorkspace_EnterpriseConnector',
			// 'WflQueryObjects_EnterpriseConnector',
			// 'WflRestoreObjects_EnterpriseConnector',
			// 'WflRestoreVersion_EnterpriseConnector',
			// 'WflSaveArticleInWorkspace_EnterpriseConnector',
			// 'WflSaveObjects_EnterpriseConnector',
			// 'WflSendMessages_EnterpriseConnector',
			// 'WflSendTo_EnterpriseConnector',
			// 'WflSetObjectProperties_EnterpriseConnector',
			// 'WflUnlockObjects_EnterpriseConnector',
			// 'WflUpdateObjectRelations_EnterpriseConnector',
			// 'WflUpdateObjectTargets_EnterpriseConnector',

		);
	}
}