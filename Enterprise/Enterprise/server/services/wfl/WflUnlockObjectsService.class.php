<?php
/**
 * UnlockObjects workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflUnlockObjectsService extends EnterpriseService
{
	public function execute( WflUnlockObjectsRequest $req )
	{
		$this->enableReporting();
		// Restructure messages from 7.x (or older) to 8.0 (or newer), 
		// to make the core server and server plugins happy.
		$oldClient = !is_null( $req->ReadMessageIDs ); // 8.0 clients should make this null.
		if( $oldClient ) {
			$req->MessageList = new MessageList();
			$req->MessageList->DeleteMessageIDs = $req->ReadMessageIDs; // In 7.x (or older) read means delete !
			$req->ReadMessageIDs = null;
		}

		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflUnlockObjects', 	
			true,  		// check ticket
			true	   	// use transactions
			);
	}

	public function runCallback( WflUnlockObjectsRequest $req )
	{
		// Remove messages that are passed in as read
		if( $req->MessageList ) {
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			BizMessage::sendMessages( $req->MessageList );
		}
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		if($req->IDs) {
			foreach ($req->IDs as $id) {
				BizObject::unlockObject( $id, $this->User /* from super class */, true );
			}
		}
		$resp = new WflUnlockObjectsResponse();
		$resp->Reports = BizErrorReport::getReports();
		return $resp;
	}
}
