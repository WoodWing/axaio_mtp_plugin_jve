<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/adm/AdmModifyPubChannels_EnterpriseConnector.class.php';

class Drupal7_AdmModifyPubChannels extends AdmModifyPubChannels_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFORE; }

	final public function runBefore( AdmModifyPubChannelsRequest &$req )
	{
		LogHandler::Log( 'Drupal7', 'DEBUG', 'Called: Drupal7_AdmModifyPubChannels->runBefore()' );

		require_once dirname(__FILE__).'/Utils.class.php';

		if ($req->PubChannels) foreach ( $req->PubChannels as $channel ) {
			if ( $channel->PublishSystem == WW_Plugins_Drupal7_Utils::DRUPAL7_PLUGIN_NAME ) {
				if ( $channel->ExtraMetaData ) foreach ($channel->ExtraMetaData as $admExtraMetaData ) {
					// Ensure there is a trailing slash on the URL field.
					if ($admExtraMetaData->Property == 'C_DPF_CHANNEL_SITE_URL' ) {
						$admExtraMetaData->Values[0] = rtrim($admExtraMetaData->Values[0], '/') . '/';
					}
				}
			}
		}
		LogHandler::Log( 'Drupal7', 'DEBUG', 'Returns: Drupal7_AdmModifyPubChannels->runBefore()' );
	} 

	// Not called.
	final public function runAfter( AdmModifyPubChannelsRequest $req, AdmModifyPubChannelsResponse &$resp )
	{
		$req = $req;   // keep code analyzer happy.
		$resp = $resp; // keep code analyzer happy.
	} 
	
	// Not called.
	final public function runOverruled( AdmModifyPubChannelsRequest $req )
	{
		$req = $req; // keep code analyzer happy.
	} 
}
