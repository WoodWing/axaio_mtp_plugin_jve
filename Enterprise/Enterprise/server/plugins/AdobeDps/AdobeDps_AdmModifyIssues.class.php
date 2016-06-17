<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/services/adm/AdmModifyIssues_EnterpriseConnector.class.php';

class AdobeDps_AdmModifyIssues extends AdmModifyIssues_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( AdmModifyIssuesRequest &$req )
	{
		require_once dirname(__FILE__).'/Utils/AdobeDpsAdminUtils.class.php';
		
		$pubChannelId = $req->PubChannelId;
		if( !$pubChannelId ){ 
			// if no PubChannelId specified, we must get the channelId assigned for the issue.
			require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
			$channel = BizPublication::getChannelForIssue( $req->Issues[0]->Id ); // Assume there's always at least one issue.
			$pubChannelId = isset($channel->Id) ? $channel->Id : null;
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
				$adminUtils = new AdobeDpsAdminUtils();
				if( $req->Issues ) foreach( $req->Issues as $issue ){
					if( !isset($issue->ExtraMetaData) ) { // If there's metadata, we do extraMD validation and nothing else
						$adminUtils->addPropertiesToIssue( $issue, $pubChannelId );
					}
				}
				// BZ#30641 Before DPS Product ID is stored a check is done on uniqueness.
				if( $req->Issues ) foreach( $req->Issues as $issue ){
					$adminUtils->validateProperties( $issue, $pubChannelId );
				}		
			}
		}

		require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
		if( $req->Issues ) foreach( $req->Issues as $issue ) {
			if( $issue->ExtraMetaData ) foreach( $issue->ExtraMetaData as $issExtraMD ) {
				if( $issExtraMD->Property == 'C_DPS_TARGET_VIEWER_VERSION' ) {
					if( $issExtraMD->Values[0] <= 25 ) {
						$freeArticleDossiers = AdobeDpsUtils::queryArticleAccessFreeDossier( $issue->Id );
						if( $freeArticleDossiers ) {
							$dossierNames = '';
							foreach( $freeArticleDossiers as $dossierId => $freeArticleDossier ) {
								// Dossier that is set to HTMLResources is not a folio, so exclude here.
								// The article_access level has no connection/effect with a HTMLResources dossier.
								if( strtolower( $freeArticleDossier['dossierIntent'] ) == 'htmlresources' ) {
									unset( $freeArticleDossiers[$dossierId] );
								} else {
									$dossierNames .= $freeArticleDossier['name'] . "\n";
								}
							}

							if( $dossierNames && $freeArticleDossiers ) {
								$detail = 'Dossier Ids that have Article_Access level set to "Free":'.implode( ",", array_keys( $freeArticleDossiers ));
								throw new BizException( 'ERR_DPS_INCOMPATIBLE_VIEWERVERSION', 'Client', $detail, null,
									array( $dossierNames ) );
							}
						}
					}
				}
			}
		}
	}

	final public function runAfter( AdmModifyIssuesRequest $req, AdmModifyIssuesResponse &$resp )
	{
		$req = $req; $resp = $resp; // To make analyzer happy.
	}
	
	final public function runOverruled( AdmModifyIssuesRequest $req ) 
	{
		$req = $req; // To make analyzer happy.
	}
}
