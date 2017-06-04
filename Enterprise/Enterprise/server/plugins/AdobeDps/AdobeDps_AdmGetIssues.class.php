<?php
/**
 * @package 	Enterprise
 * @subpackage ServerPlugins
 * @since 		v7.6.11
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetIssues_EnterpriseConnector.class.php';

class AdobeDps_AdmGetIssues extends AdmGetIssues_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( AdmGetIssuesRequest &$req ) {}

	final public function runAfter( AdmGetIssuesRequest $req, AdmGetIssuesResponse &$resp )
	{
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		$pubChannelId = $resp->PubChannelId;
		if( !$pubChannelId ){ 
			// if no PubChannelId specified, we must get the channelId assigned for the issue.
			require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
			$channel = BizPublication::getChannelForIssue( $resp->Issues[0]->Id ); // Assume there's always at least one issue.
			$pubChannelId = isset($channel->Id) ? $channel->Id : null;
		}		
		$pubChannel = DBChannel::getPubChannelObj( $pubChannelId );
		if( $pubChannel->Type == 'dps' ) {
			$clientVersion = BizSession::getClientVersion( null, null, 3 );
			$appName = BizSession::getClientName();

			// Since 7.6.11 / 8.2.1 the server is no longer returning Free/Paid flag. 
			// However, clients older than 7.6.11 expect a Free/Paid flag as issue property.
			// The same is true for clients older than 8.0.0 and before 8.2.1.
			// From 7.6.11 onwards it is a publishfield (DpsStore).
			// The fragement below can be removed when 8.2 is no longer supported.
			if( $appName == 'Content Station' && ( version_compare( $clientVersion, '7.6.11', '<' ) ||
				( version_compare( $clientVersion, '8.0.0', '>=' ) && version_compare( $clientVersion, '8.2.1', '<' )))) {
				if( $resp->Issues ) foreach( $resp->Issues as $issue ) {
					$extraMetaData = new MetaDataValue();
					$extraMetaData->Property = 'C_DPS_IS_FREE';
					$extraMetaData->Values = array(false);
					$issue->ExtraMetaData[] = $extraMetaData;
				}	
			}
		}
	}

	final public function runOverruled( AdmGetIssuesRequest $req ) {}
}
