<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjects_EnterpriseConnector.class.php';

class AdobeDps_WflDeleteObjects extends WflDeleteObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflDeleteObjectsRequest &$req )
	{
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket       = $req->Ticket;
		$request->IDs          = $req->IDs;
		$request->Lock         = false;
		$request->Rendition    = 'none';
		$request->RequestInfo  = array( 'MetaData', 'Targets' );
		$request->HaveVersions = null;
		$request->Areas        = $req->Areas;
		
		$service = new WflGetObjectsService();
		try {
			$response = $service->execute( $request );
		} catch ( BizException $e) {
			// Just log the error and let the core continue.
			LogHandler::Log( basename(__FILE__), 'INFO', $e->getMessage() );
			$response = null;
		}

		// For dossiers targetted to DPS channels, collect the deleted dossiers (ids) for dossier re-ordering.
		if ( $response && $response->Objects ) foreach ( $response->Objects as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'Dossier' ) {
				if ($object->Targets) foreach( $object->Targets as $target ) {
					$pubChannel = DBChannel::getPubChannelObj( $target->PubChannel->Id );
					if( $pubChannel->Type == 'dps' ) {
						// Let some data travel with the request to pick-up at the runAfter().
						// Note that we use the prefix of our plugin so separate this member from 
						// other DeleteObjects connectors, such as the one shipped at DM.
						// This is to avoid interfering data within different connectors.
						if( !isset( $req->adobeDps ) ) {
							$req->adobeDps = new stdClass();
						}
						if( !isset( $req->adobeDps->dossierIdsByIssue ) ) {
							$req->adobeDps->dossierIdsByIssue = array();
						}
						$req->adobeDps->dossierIdsByIssue[$target->Issue->Id][] = $object->MetaData->BasicMetaData->ID;
					}
				}
			}
		}
	}

	final public function runAfter( WflDeleteObjectsRequest $req, WflDeleteObjectsResponse &$resp )
	{
		$resp = $resp; // keep analyzer happy

		// For dossiers targetted to DPS channels, remove the dossiers (ids) from the dossier ordering.
		if( isset($req->adobeDps->dossierIdsByIssue) ) {
			foreach( $req->adobeDps->dossierIdsByIssue as $issueId => $dossierIds ) {
				include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
				$bizPubIssue = new BizPubIssue();
				$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
				foreach( $dossierIds as $dossierId ) {
					$bizPubIssue->removeDossierFromOrder( $issueId, $dossierId );
				}
			}
			if( isset( $req->adobeDps ) ) {
				unset($req->adobeDps);
			}
		}
	}

	final public function runOverruled( WflDeleteObjectsRequest $req )
	{
		// not called
		$req = $req; // keep analyzer happy
	}
}
