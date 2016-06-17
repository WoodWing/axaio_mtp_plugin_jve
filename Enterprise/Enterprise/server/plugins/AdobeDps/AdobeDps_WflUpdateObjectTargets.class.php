<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectTargets_EnterpriseConnector.class.php';

class AdobeDps_WflUpdateObjectTargets extends WflUpdateObjectTargets_EnterpriseConnector
{
	private static $currentObjectTargets;

	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflUpdateObjectTargetsRequest &$req )
	{
		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		
		if ( $req->IDs && is_array($req->IDs) ) {
			foreach( $req->IDs as $objectId ) {
				$targets = BizTarget::getTargets('', $objectId);
				foreach( $targets as $target ) {
					// Save all the issue ids of object where it's targetted for. We need this in the runAfter function.
					self::$currentObjectTargets[$objectId][] = $target->Issue->Id;
				}
			}
		}
	}

	final public function runAfter( WflUpdateObjectTargetsRequest $req, WflUpdateObjectTargetsResponse &$resp )
	{
		$resp = $resp; // keep analyzer happy

		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest( $req->Ticket, $req->IDs, false, 'none', array('MetaData') );
		$service = new WflGetObjectsService();
		$response = $service->execute( $request );

		foreach( $response->Objects as $object ) {
			if( $object->MetaData->BasicMetaData->Type == 'Dossier' ) {
				$issueIds = $oldIssueIds = array();

				$dossierId = $object->MetaData->BasicMetaData->ID;

				// Get the old issue ids if available
				if ( isset(self::$currentObjectTargets[$dossierId]) ) {
					$oldIssueIds = self::$currentObjectTargets[$dossierId];
				}

				foreach( $req->Targets as $target ) {
					require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
					$pubChannel = DBChannel::getPubChannelObj( $target->PubChannel->Id );
					if( $pubChannel->Type == 'dps' ) {
						$issueIds[] = $target->Issue->Id;
					}
				}

				// New issues are added. So not available in the old issues ids array.
				// Calculated the difference and add it to the order
				$addedIssueIds = array_diff($issueIds, $oldIssueIds);
				foreach( $addedIssueIds as $addedIssueId ) {
					include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
					$bizPubIssue = new BizPubIssue();
					$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
					$bizPubIssue->addDossierToOrder( $addedIssueId, $dossierId );

					// Fix then order of sections when needed
					require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
					AdobeDpsUtils::fixSectionDossierOrder( $addedIssueId );
				}
				
				// All the old issue ids that aren't targeted anymore we need to remove.
				$issueIdsToRemove = array_diff($oldIssueIds, $issueIds);
				foreach( $issueIdsToRemove as $issueId ) {
					include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
					$bizPubIssue = new BizPubIssue();
					$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
					$bizPubIssue->removeDossierFromOrder( $issueId, $dossierId );
				}
			}
		}
	}

	final public function runOverruled( WflUpdateObjectTargetsRequest $req )
	{
		// not called
		$req = $req; // keep analyzer happy
	}
}
