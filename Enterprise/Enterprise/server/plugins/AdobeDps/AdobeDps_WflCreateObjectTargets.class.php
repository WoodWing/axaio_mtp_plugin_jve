<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectTargets_EnterpriseConnector.class.php';

class AdobeDps_WflCreateObjectTargets extends WflCreateObjectTargets_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflCreateObjectTargetsRequest &$req ) {}

	final public function runAfter( WflCreateObjectTargetsRequest $req, WflCreateObjectTargetsResponse &$resp )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest( $req->Ticket, $resp->IDs, false, 'none', array('MetaData') );
		$service = new WflGetObjectsService();
		$response = $service->execute( $request );

		// For dossiers targetted to DPS channels, add the dossier (id) to the dossier ordering.
		foreach( $response->Objects as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'Dossier' ) {
				if ( $object->Targets ) foreach( $object->Targets as $target ) {
					require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
					$pubChannel = DBChannel::getPubChannelObj( $target->PubChannel->Id );
					if( $pubChannel->Type == 'dps' ) {
						include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
						$bizPubIssue = new BizPubIssue();
						$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
						$dossierId = $object->MetaData->BasicMetaData->ID;
						$bizPubIssue->addDossierToOrder( $target->Issue->Id, $dossierId );

						// Fix then order of sections when needed
						require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
						AdobeDpsUtils::fixSectionDossierOrder( $target->Issue->Id );
					}
				}
			}
		}
	}

	final public function runOverruled( WflCreateObjectTargetsRequest $req ) {}
}
