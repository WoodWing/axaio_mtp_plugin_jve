<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectTargets_EnterpriseConnector.class.php';

class AdobeDps_WflDeleteObjectTargets extends WflDeleteObjectTargets_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflDeleteObjectTargetsRequest &$req )
	{
		// not called
		$req = $req; // keep analyzer happy
	}

	final public function runAfter( WflDeleteObjectTargetsRequest $req, WflDeleteObjectTargetsResponse &$resp )
	{
		$resp = $resp; // keep analyzer happy

		require_once dirname(__FILE__).'/Utils/AdobeDpsAdminUtils.class.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest( $req->Ticket, $req->IDs, false, 'none', array('MetaData') );
		$service = new WflGetObjectsService();
		$response= $service->execute( $request );

		// For dossiers targetted to DPS channels, remove the dossiers (ids) from the dossier ordering.
		foreach( $response->Objects as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'Dossier' ) {
				$dossierId = $object->MetaData->BasicMetaData->ID;
				foreach( $req->Targets as $target ) {
					require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
					$pubChannel = DBChannel::getPubChannelObj( $target->PubChannel->Id );
					if( $pubChannel->Type == 'dps' ) {
						include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
						$bizPubIssue = new BizPubIssue();
						$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
						$bizPubIssue->removeDossierFromOrder( $target->Issue->Id, $dossierId );
					}
				}
			}
		}
	}

	final public function runOverruled( WflDeleteObjectTargetsRequest $req )
	{
		// not called
		$req = $req; // keep analyzer happy
	}
}
