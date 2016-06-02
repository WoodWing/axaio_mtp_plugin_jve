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

//
//	If the 'real' PluginInfo class has not been generated,
//	create it from the template
//	Notice: requires write access to the config/plugins folder
//
if (!file_exists(dirname(__FILE__).'/PluginInfo.class.php'))
{
	$template = file_get_contents(dirname(__FILE__) . '/PluginInfo.template.php');
	$name = basename(dirname(__FILE__));
	$template = str_replace('%classname%', $name, $template);
	
	$WFLpath = BASEDIR . '/server/interfaces/services/wfl';
   	$dh  = opendir($WFLpath);

	$connectors = '';	
	$files = array(); 
	while (false !== ($filename = readdir($dh))) 
	{
	  	if ( strpos($filename,'_EnterpriseConnector') > 0 )
	  	{
	  		$filename = str_replace('.class.php', '', $filename);
		 	$connectors .= "\011\011\011// '".$filename . "',\015";
	  	}
	}
	
	$template = str_replace('%connectorlist%', $connectors, $template);
	file_put_contents(dirname(__FILE__).'/PluginInfo.class.php', $template);
}

//
//	Create an empty config.php
//	Notice: requires write access to the config/plugins folder
//
if (!file_exists(dirname(__FILE__).'/config.php'))
{
	file_put_contents(dirname(__FILE__).'/config.php', "");
}

//
//	Include the 'real' generated PluginInfo class
//	In this file, choose the connectors you want to implement
//	Connector implementations are automatically generated
//
require_once(dirname(__FILE__).'/PluginInfo.class.php');

