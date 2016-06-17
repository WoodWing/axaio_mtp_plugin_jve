<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/EnterpriseConnector.class.php';

abstract class ServiceConnector extends EnterpriseConnector 
{
	const RUNMODE_BEFORE      = 'Before';
	const RUNMODE_AFTER       = 'After';
	const RUNMODE_BEFOREAFTER = 'BeforeAfter';
	const RUNMODE_OVERRULE    = 'Overrule';
	
	final public function getRunModes() 
	{
		return array( self::RUNMODE_BEFORE,  self::RUNMODE_AFTER, self::RUNMODE_BEFOREAFTER, self::RUNMODE_OVERRULE ); 
	}
}