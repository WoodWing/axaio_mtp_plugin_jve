<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectProperties_EnterpriseConnector.class.php';

class AdobeDps_WflSetObjectProperties extends WflSetObjectProperties_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflSetObjectPropertiesRequest &$req )
	{
		// For dossiers targetted to DPS channels, collect the dossiers (ids) for dossier ordering.
		if( $req->MetaData->BasicMetaData->Type == 'Dossier' ) {
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest( $req->Ticket, array($req->ID), false, 'none', array('MetaData', 'Targets') );
			$service = new WflGetObjectsService();
			$response = $service->execute( $request );
			$object = $response->Objects[0];

			require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
			if ( isset( $req->Targets ) && !is_null( $req->Targets ) ) foreach( $object->Targets as $oldTarget ) { 
			// BZ#33993 Request contains targets. Check if they are changed or removed (empty array). Null means ignore. 
				$pubChannel = DBChannel::getPubChannelObj( $oldTarget->PubChannel->Id );
				if( $pubChannel->Type == 'dps' ) {
					$inNewTarget = false;
					foreach( $req->Targets as $newTarget ) {
						if( $newTarget->Issue->Id == $oldTarget->Issue->Id && 
							$newTarget->PubChannel->Id == $oldTarget->PubChannel->Id ) {
							$inNewTarget = true;
							$oldEditions = array();
							foreach( $oldTarget->Editions as $oldEdition ) {
								$oldEditions[$oldEdition->Id] = $oldEdition->Name;
							}
							$newEditions = array();
							foreach( $newTarget->Editions as $newEdition ) {
								$newEditions[$newEdition->Id] = $newEdition->Name;
							}
							$deleteEditions = array_diff( $oldEditions, $newEditions );
							// BZ#32403 - When the removed edition had published before, block the removal of edition for the dossier
							if( $deleteEditions ) foreach( $deleteEditions as $deleteEditionId => $deleteEditionName ) {
								$isPublished = DBPublishHistory::isDossierPublished( $req->ID, $oldTarget->PubChannel->Id, $oldTarget->Issue->Id, $deleteEditionId );
								if ( $isPublished ) {
									$params = array( $deleteEditionName );
									throw new BizException( 'ERR_DELETE_PUBLISHED_DEVICE', 'Client', '', null, $params );
								}
							}
						}
					}
					if( !$inNewTarget ) {
						$req->deleteTarget[$pubChannel->Type][] = $oldTarget->Issue->Id;
					}
				}
			}
		}
	}

	final public function runAfter( WflSetObjectPropertiesRequest $req, WflSetObjectPropertiesResponse &$resp )
	{
		if( $req->MetaData->BasicMetaData->Type == 'Dossier' ) {
			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest( $req->Ticket, array($req->ID), false, 'none', array('MetaData') );
			$service = new WflGetObjectsService();
			$response = $service->execute( $request );
			$object = $response->Objects[0];
		
			// For dossiers targetted to DPS channels, add/remove the dossiers (ids) to/from the dossier ordering.
			$dossierId = $object->MetaData->BasicMetaData->ID;
			if( isset($req->deleteTarget) ) {
				foreach( $req->deleteTarget as $channelType => $issueIds ) {
					if( $channelType == 'dps' ) {
						foreach( $issueIds as $issueId ) {
							include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
							$bizPubIssue = new BizPubIssue();
							$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
							$bizPubIssue->removeDossierFromOrder( $issueId, $dossierId );
						}
					}
				}
			}
			foreach( $resp->Targets as $target ) {
				require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
				$pubChannel = DBChannel::getPubChannelObj( $target->PubChannel->Id );
				if( $pubChannel->Type == 'dps' ) {
					include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
					$bizPubIssue = new BizPubIssue();
					$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
					$bizPubIssue->addDossierToOrder( $target->Issue->Id, $dossierId );

					// Fix then order of sections when needed
					require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
					AdobeDpsUtils::fixSectionDossierOrder( $target->Issue->Id );
				}
			}
		}
	}

	final public function runOverruled( WflSetObjectPropertiesRequest $req ) {}
}
