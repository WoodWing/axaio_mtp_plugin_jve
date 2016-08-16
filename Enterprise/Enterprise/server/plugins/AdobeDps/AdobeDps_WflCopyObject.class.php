<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObject_EnterpriseConnector.class.php';

class AdobeDps_WflCopyObject extends WflCopyObject_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflCopyObjectRequest &$req ) {}

	final public function runAfter( WflCopyObjectRequest $req, WflCopyObjectResponse &$resp )
	{
		// For dossiers targetted to DPS channels, add the dossier (id) to the dossier ordering.
		if( $resp->MetaData->BasicMetaData->Type == 'Dossier' ) {
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
			foreach( $resp->Targets as $target ) {
				$pubChannel = DBChannel::getPubChannelObj( $target->PubChannel->Id );
				if( $pubChannel->Type == 'dps' ) {
					include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
					$bizPubIssue = new BizPubIssue();
					$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
					$dossierId = $resp->MetaData->BasicMetaData->ID;
					$bizPubIssue->addDossierToOrder( $target->Issue->Id, $dossierId );

					// Fix then order of sections when needed
					require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
					AdobeDpsUtils::fixSectionDossierOrder( $target->Issue->Id );
				}
			}
		}
	}

	final public function runOverruled( WflCopyObjectRequest $req ) {}
}
