<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
 
class SmartArchive_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		$info = new PluginInfoData(); 
		$info->DisplayName = 'Smart Archive';
		$info->Version     = 'v8.0 20100823'; 
		$info->Description = 'Exposes content from remote archive server to this production server. Like the production server, the archive server is also an Enterprise Server instance.';
		$info->Copyright   = COPYRIGHT_WOODWING;
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{
		return array(	'ContentSource_EnterpriseConnector', 
						'WflLogOn_EnterpriseConnector', 
						'WflLogOff_EnterpriseConnector', 
						'WflGetDialog2_EnterpriseConnector',
						'WflListVersions_EnterpriseConnector' );
	}

	public function isInstalled()
	{
		try {
			$this->checkInstallation();
			$this->checkPropertyName();
			return true;
		} catch( BizException $e ) {
			return false;
		}
	}
	
	public function runInstallation() 
	{
		$this->checkInstallation(); 
		$this->checkPropertyName();
	}
	
	private function checkInstallation()
	{
		require_once dirname(__FILE__).'/config.php';
		$tip = 'Check your SMARTARCHIVE_FILTER_FIELDS setting at config.php of Smart Archive server plug-in.';
		$filters = array_flip( unserialize(SMARTARCHIVE_FILTER_FIELDS) );
		if( isset($filters['Editions']) || isset($filters['Issues']) ||
			isset($filters['Edition']) || isset($filters['Issue'])  ) {
			throw new BizException( null, 'Server', 'Issues, Issue, Editions and Edition search filters are not supported. '.$tip );
		}
		if( isset($filters['PublicationId']) || isset($filters['IssueId']) || isset($filters['EditionId']) 
			|| isset($filters['SectionId']) || isset($filters['CategoryId']) || isset($filters['StateId']) 
			|| isset($filters['IssueIds']) || isset($filters['EditionIds']) ) {
			throw new BizException( null, 'Server', 'Id-based search filters are not supported. Please use name-based filters instead. '.$tip );
		}
		
	}

	private function checkPropertyName()
	{
		require_once dirname(__FILE__).'/config.php';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		
		$tip = 'Check your SMARTARCHIVE_FILTER_FIELDS setting at config.php of Smart Archive server plug-in.';
		$filters =  unserialize(SMARTARCHIVE_FILTER_FIELDS);
		
		$props = BizProperty::getPropertyInfos();
		
		foreach($filters as $filter){
			if($filter == 'Search'){
				continue; // special Solr search field = ok
			}
			if( array_key_exists( $filter, $props ) ){
				continue; // built-in prop = ok
			}
			if( BizProperty::isCustomPropertyName($filter) ){
				continue; // custom prop = ok
			}
			throw new BizException( null, 'Server', 'invalid Property name:'. $filter . '. '.$tip );
		}
	}
	
}
