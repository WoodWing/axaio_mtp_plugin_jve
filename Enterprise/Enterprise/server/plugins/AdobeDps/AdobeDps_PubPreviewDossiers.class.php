<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/pub/PubPreviewDossiers_EnterpriseConnector.class.php';

class AdobeDps_PubPreviewDossiers extends PubPreviewDossiers_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( PubPreviewDossiersRequest &$req )
	{
		// The PreviewDossiers call is still using the old way of handling published dossiers
		// so Targets and DossierIDs are given.
		if ( $req->Targets ) foreach ( $req->Targets as $target ) {
			require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
            $pubChannel = DBChannel::getPubChannelObj( $target->PubChannelID );
            if( $pubChannel->Type != 'dps' ) {
              continue;
            }
			require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
			// When there is no HTMLResources dossier we can skip it.
			$htmlResourcesDossier = AdobeDpsUtils::getHTMLResourcesDossiersInIssue( $target );
			if ( !$htmlResourcesDossier ) {
				return;
			}

			// For previews the HTMLResources dossier should always be added
			$found = false;
			if ( $req->DossierIDs ) foreach( $req->DossierIDs as $dossierId ) {
				if ( $dossierId == $htmlResourcesDossier->MetaData->BasicMetaData->ID ) {
					$found = true;
					break;
				}
			}
			// When not found, we add it to the list of dossiers
			if ( !$found ) {
				$req->DossierIDs[] = $htmlResourcesDossier->MetaData->BasicMetaData->ID;
			}
		}
	}

	final public function runAfter( PubPreviewDossiersRequest $req, PubPreviewDossiersResponse &$resp ) {}

	final public function runOverruled( PubPreviewDossiersRequest $req ) {}
}
