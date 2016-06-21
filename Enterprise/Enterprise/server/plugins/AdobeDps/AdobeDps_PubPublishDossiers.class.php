<?php
	/**
	 * @package 	Enterprise
	 * @subpackage 	ServerPlugins
	 * @since 		v7.6
	 * @copyright	WoodWing Software bv. All Rights Reserved.
	 */

	require_once BASEDIR . '/server/interfaces/services/pub/PubPublishDossiers_EnterpriseConnector.class.php';

class AdobeDps_PubPublishDossiers extends PubPublishDossiers_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( PubPublishDossiersRequest &$req )
	{
		// In theory the different PublishedDossiers can have different target.
		// Build a list of targets.
		$targets = array();
		if ( $req->PublishedDossiers ) foreach( $req->PublishedDossiers as $publishedDossier ) {
			$target = $publishedDossier->Target;
            require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
            $pubChannel = DBChannel::getPubChannelObj( $target->PubChannelID );
            if( $pubChannel->Type == 'dps' ) {
            	$key = $target->PubChannelID . '-' . $target->IssueID;
				if ( $target->EditionID ) {
					$key .= '-' . $target->EditionID;
				}
                $targets[$key] = $publishedDossier->Target;
            }
		}

		if ( $targets ) foreach ( $targets as $target ) {
			require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
			$htmlResourcesDossier = AdobeDpsUtils::getHTMLResourcesDossiersInIssue( $target );
			if ( !$htmlResourcesDossier ) {
				return;
			}

			// Check if the dossier is already in the list of dossiers to publish
			$found = false;
			if ( $req->PublishedDossiers ) foreach( $req->PublishedDossiers as $publishedDossier ) {
				if ( $publishedDossier->DossierID == $htmlResourcesDossier->MetaData->BasicMetaData->ID ) {
					$found = true;
					break;
				}
			}
			if ( !$found ) {
				// Only add it when it is dirty
				if ( AdobeDpsUtils::isHTMLResourcesDossierDirty($htmlResourcesDossier, $target) ) {
					$publishedDossier = new PubPublishedDossier();
					$publishedDossier->DossierID = $htmlResourcesDossier->MetaData->BasicMetaData->ID;
					$publishedDossier->Target    = $target;
					$req->PublishedDossiers[] = $publishedDossier;
				}
			}
		}
	}

	final public function runAfter( PubPublishDossiersRequest $req, PubPublishDossiersResponse &$resp )
	{
		$req = $req; $resp = $resp; // Keep analyzer happy
	}

	final public function runOverruled( PubPublishDossiersRequest $req )
	{
		$req = $req; // Keep analyzer happy
	}
}