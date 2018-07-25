<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/EnterpriseConnector.class.php';

abstract class DefaultConnector extends EnterpriseConnector 
{
	// DefaultConnector implementations can run synchronously with core system or run in background
	const RUNMODE_SYNCHRON    = 'Synchron';
	const RUNMODE_BACKGROUND  = 'Background';
	final public function getRunModes() 
	{
		return array( self::RUNMODE_SYNCHRON, self::RUNMODE_BACKGROUND );
	}

	// DefaultConnector implementations are free to use their own (custom) type. Default empty/none.
	public function getConnectorType()
	{ 
		return ''; 
	}
}