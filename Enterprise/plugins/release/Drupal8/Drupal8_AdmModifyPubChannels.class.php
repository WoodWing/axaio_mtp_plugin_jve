<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/adm/AdmModifyPubChannels_EnterpriseConnector.class.php';

class Drupal8_AdmModifyPubChannels extends AdmModifyPubChannels_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFORE; }

	final public function runBefore( AdmModifyPubChannelsRequest &$req )
	{
	}

	// Not called.
	final public function runAfter( AdmModifyPubChannelsRequest $req, AdmModifyPubChannelsResponse &$resp )
	{
	}
	
	// Not called.
	final public function runOverruled( AdmModifyPubChannelsRequest $req )
	{
	}
}
