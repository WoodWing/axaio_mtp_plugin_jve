<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateIssues_EnterpriseConnector.class.php';

class AdobeDps_AdmCreateIssues extends AdmCreateIssues_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( AdmCreateIssuesRequest &$req ) 
	{
		$pubChannelId = $req->PubChannelId;
		if( !$pubChannelId ){ 
			// if no PubChannelId specified, we must get the default PubChannelId
			require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
			$pubChannel = BizAdmPublication::getDefaultPubChannel( $req->PublicationId );
			$pubChannelId = $pubChannel->Id;
			$req->PubChannelId = $pubChannelId;
		}
		if( !$pubChannelId ){
			require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
			LogHandler::Log( 'AdobeDps-AdmService', 'WARN', __METHOD__ .
				': No pub channel id found for pub ' . DBPublication::getPublicationName( $req->PublicationId) .
				'. No checking and repairing done for ExtraMetaData.');
		}
		if( $pubChannelId ){
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
			$channelRow = DBChannel::getChannel( $pubChannelId );
			if( $channelRow['type'] == 'dps' ) {
				require_once dirname(__FILE__).'/Utils/AdobeDpsAdminUtils.class.php';
				$adminUtils = new AdobeDpsAdminUtils();
				if( $req->Issues ) foreach( $req->Issues as $issue ){
					if( !isset($issue->ExtraMetaData) ){
						$adminUtils->addPropertiesToIssue( $issue, $pubChannelId );
					}
				}
				// BZ#30641 Before DPS Product ID is stored a check is done on uniqueness.
				if( $req->Issues ) foreach( $req->Issues as $issue ){
					$adminUtils->validateProperties( $issue, $pubChannelId );
				}		
			}
		}
	}
	
	final public function runAfter( AdmCreateIssuesRequest $req, AdmCreateIssuesResponse &$resp ) {}
	
	final public function runOverruled( AdmCreateIssuesRequest $req ) {}
}
