<?php
/**
 * SendTo workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSendToRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSendToResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSendToService extends EnterpriseService
{
	public $Ticket;
	public $IDs;
	public $WorkflowMetaData;

	public function execute( WflSendToRequest $req )
	{
		// Since v4.2 SendTo does basically the same thing as SetObjectProperties.
		// Since v6.0 SendTo is DEPRICATED! Therefor it can be used the old way only (targets not supported).
		// v6.0 clients should use the SetObjectProperties instead!
		// Also overruling via connectors is not supported, to do this on SetObjectProperties.
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';

		//BZ#4450 First test if object id's are in different publications		
		$publications = DBObject::listPublications($req->IDs);
		if (count($publications) > 1) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Can not set metadata in different publications' );
		}

		//BZ#4450 Then test if object id's are in a overrule issue AND any other issue
		//throw BizException if this is the case
		$overruleIssueIds = DBIssue::listAllOverruleIssues();
		if (count($overruleIssueIds) > 0) {
			$error = false;
			$inoverruleissueid = 0;
			$innooverruleissue = false;
			$alltargets = array();
			foreach ($req->IDs as $objectid) {
				// BZ#16338 don't get targets for alien objects
				require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
				if (! BizContentSource::isAlienObject( $objectid ) ){
					$objecttargets = DBTarget::getTargetsByObjectId($objectid);
					$alltargets = array_merge($alltargets, $objecttargets);
				}
			}
			foreach ($alltargets as $target) {
				if ($target->Issue) {
					if (in_array($target->Issue->Id, $overruleIssueIds)) {
						if ($innooverruleissue == true) {
							$error = true;
							break;
						}
						if ($inoverruleissueid == 0) {
							$inoverruleissueid = $target->Issue->Id;
						}
						else {
							if ($inoverruleissueid != $target->Issue->Id) {
								$error = true;
								break;
							}
						}
					}
					else {
						$innooverruleissue = true;
						if ($inoverruleissueid != 0) {
							$error = true;
							break;
						}
					}
				}
				else {
					$innooverruleissue = true;
						if ($inoverruleissueid != 0) {
							$error = true;
							break;
						}
				}
			}	
			if ($error) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Can not set metadata in overrule issues an others at the same time' );				
			}
		}

		$retVal = null;
		foreach( $req->IDs as $id ) {
			// Backwards compatibility: create full MetaData object out of id and wfmeta
			$meta = new MetaData(); 
		
			// Make sure object id is put into meta data (robustness)
			$meta->BasicMetaData = new BasicMetaData; 
			$meta->BasicMetaData->ID = $id;

			// Take over workflow data from request
			$meta->WorkflowMetaData = new WorkflowMetaData;
			$meta->WorkflowMetaData	= clone $req->WorkflowMetaData; // Clone required! (BZ#11181)

			// Redirect SendTo service to SetProperties service, don't update targets (=null)
			$targets = null;
			$sopReq = new WflSetObjectPropertiesRequest( $req->Ticket, $id, $meta, $targets );
			$sopService = new WflSetObjectPropertiesService();
			$retVal = $sopService->execute( $sopReq );
		}
		return new WflSendToResponse( $retVal->MetaData->WorkflowMetaData );
	}

	public function runCallback( WflSendToRequest $req )
	{
		// not implemented, because call is redirected to SetObjectProperties
	}
}
