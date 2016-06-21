<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	2008-2009 WoodWing Software bv. All Rights Reserved.
 *
 * Solr Search integration - The Server Plug-in class
 *
 * @todo Implement the following features:
 *  - Apply access rights to find results.
 *  - Limit number of query results
 *  - Sort on columns
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
require_once BASEDIR.'/config/config_solr.php';
 
class SolrSearch_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Solr Search';
		$info->Version     = file_get_contents(__DIR__.'/_productversion.txt');
		$info->Description = 'Integrates Solr search engine.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array( 	'Search_EnterpriseConnector' ); 
	}
}