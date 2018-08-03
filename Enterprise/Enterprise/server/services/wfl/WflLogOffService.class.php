<?php
/**
 * LogOff workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflLogOffService extends EnterpriseService
{
	public function execute( WflLogOffRequest $req )
	{
		// Restructure messages from 7.x (or older) to 8.0 (or newer), 
		// to make the core server and server plugins happy.
		$oldClient = !is_null( $req->ReadMessageIDs ); // 8.0 clients should make this null.
		if( $oldClient ) {
			if( $req->ReadMessageIDs ) { // The meaning has changed since 8.0: Now there is read and delete.
				$req->MessageList = new MessageList();
				$req->MessageList->DeleteMessageIDs = $req->ReadMessageIDs; // In 7.x (or older) read means delete !
			}
			$req->ReadMessageIDs = null;
		}

		// Run the logoff service.
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflLogOff', 	
			false,  		// don't check ticket
			true   		// use transactions
			);
	}

	public function runCallback( WflLogOffRequest $req )
	{
		BizSession::logOff( $req->Ticket, $req->SaveSettings, $req->Settings, $req->MessageList );
		return new WflLogOffResponse;
	}
}
