<?php
/**
 * SendMessages workflow business service.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSendMessagesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSendMessagesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSendMessagesService extends EnterpriseService
{
	public function execute( WflSendMessagesRequest $req )
	{
		$this->enableReporting();
		// Restructure messages from 7.x (or older) to 8.0 (or newer), 
		// to make the core server and server plugins happy.
		$oldClient = !is_null( $req->Messages ); // 8.0 clients should make this null.
		if( $oldClient ) {
			$deleteMessageIds = array();
			$messages = array();
		
			$req->MessageList = new MessageList();
			foreach( $req->Messages as $message ) {
				if( $message->UserID == '0' && $message->ObjectID == '0' ) { // old way of message deletions
					$deleteMessageIds[] = $message->MessageID;
				} else {
					$messages[] = $message;
				}
			}
			if( count( $deleteMessageIds ) > 0 ) {
				$req->MessageList->DeleteMessageIDs = $deleteMessageIds;
			}
			if( count( $messages ) > 0 ) {
				$req->MessageList->Messages = $messages;	
			}
			$req->Messages = null;
		}

		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflSendMessages', 	
			true,  		// check ticket
			true   	// use transaction
			);
	}

	public function runCallback( WflSendMessagesRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		BizMessage::sendMessages( $req->MessageList );
		$resp = new WflSendMessagesResponse();
		$resp->MessageList	= $req->MessageList;
		$resp->Reports 		= BizErrorReport::getReports();
		return $resp;
	}
}
