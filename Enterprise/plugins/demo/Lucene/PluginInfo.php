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

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Lucene Search integration - The Server Plug-in class
 *
 * @todo Implement the following features:
 *  - Apply access rights to find results.
 *  - Limit number of query results
 *  - Sort on columns
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class Lucene_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Lucene';
		$info->Version     = '6.2 (Mar 31, 2009)'; // don't use PRODUCTVERSION
		$info->Description = 'Integrates Lucene search engine.';
		$info->Copyright   = 'Copyright (c) 2005-2008, Zend Technologies USA, Inc., '.COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 	'ContentSource_EnterpriseConnector',
						'WflCreateObjects_EnterpriseConnector', 
						'WflSaveObjects_EnterpriseConnector',
						'WflCopyObject_EnterpriseConnector',
						'WflDeleteObjects_EnterpriseConnector' ); 
	}
}