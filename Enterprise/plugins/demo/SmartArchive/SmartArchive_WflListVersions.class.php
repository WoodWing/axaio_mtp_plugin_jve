<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflListVersions_EnterpriseConnector.class.php';
require_once BASEDIR .'/server/bizclasses/BizContentSource.class.php';
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/SmartArchive.class.php';

class SmartArchive_WflListVersions extends WflListVersions_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_BEFORE; }

	final public function runAfter (WflListVersionsRequest $req, WflListVersionsResponse &$resp) 
	{
	} 
	
	final public function runBefore (WflListVersionsRequest &$req)	
	{

		if( BizContentSource::isAlienObject( $req->ID ) ) { // any alien object?
			
			/*$externalObjId =*/ 
			if( SmartArchive::isContentSourceID($req->ID)){
			// Note: in the future we might need $externalObjId representing the archive object id
			
				//Raise Error when user tries to 'show versions'
				throw new BizException( 'ERR_AUTHORIZATION', 'Client', ''  );
			}
		}
	} 
	
	final public function runOverruled (WflListVersionsRequest $req) 	
	{
	} 
}
