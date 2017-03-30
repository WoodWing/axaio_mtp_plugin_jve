<?php
/**
 * CreateObjects workflow business service.
 *
 * @package Enterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflCreateObjectsService extends EnterpriseService
{
	/**
	 * @inheritdoc
	 * @since 10.2.0
	 */
	protected function restructureRequest( &$request )
	{
		// Clients may provide very little structure, such as the Digital Editor (CSDE).
		// Let's be friendly and don't bother the core and the connectors with that. [EN-88915]
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		if( $request->Objects ) foreach( $request->Objects as &$object ) {
			BizObject::completeMetaDataStructure( $object->MetaData );
		}
	}

	public function execute( WflCreateObjectsRequest $req )
	{
		$this->enableReporting();
		// Restructure messages from 7.x (or older) to 8.0 (or newer), 
		// to make the core server and server plugins happy.
		$oldClient = !is_null( $req->Messages ); // 8.0 clients should make this null.
		if( $oldClient ) {
			if( $req->Objects && count($req->Objects) == 1 ) { // In practise, only one object is saved by SC.
				$object = $req->Objects[0];
				$object->MessageList = new MessageList();
				$object->MessageList->Messages = $req->Messages;
				if( $req->ReadMessageIDs ) { // The meaning has changed since 8.0: Now there is read and delete.
					$object->MessageList->DeleteMessageIDs = $req->ReadMessageIDs; // In 7.x (or older) read means delete !
				}
			}
			$req->Messages = null;
		}

		// Run the services
		$resp = $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflCreateObjects', 	
			true,  		// check ticket
			false	   	// don't use transaction at request level, we'll do transaction per object
		);

		// Restructure messages from 8.0 (or newer) to 7.x (or older), to make old clients happy.
		if( $oldClient ) {
			if( $resp->Objects && count($resp->Objects) == 1 ) { // In practise, only one object is saved by SC.
				$object = $resp->Objects[0];
				if( $object->MessageList ) {
					$object->Messages = $object->MessageList->Messages;
					$object->MessageList = null;
				}
			}
		}
		return $resp;
	}

	public function runCallback( WflCreateObjectsRequest $req )
	{
		// Create objects one by one.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$retObjects = array();
		if( $req->Objects ) foreach( $req->Objects as $object ) {
			BizSession::startTransaction(); // Create transaction per object
			try {
				// Create one object and collect it to return caller.
				$retObjects[] = BizObject::createObject( $object, $this->User/* from super class*/, $req->Lock, $req->AutoNaming, $req->ReplaceGUIDs );
			} catch ( BizException $e ) {
				// Cancel session and re-throw exception to stop the service:
				BizSession::cancelTransaction();
				throw( $e );
			}
			BizSession::endTransaction();
		}
		
		// Return response to caller.
		$resp = new WflCreateObjectsResponse();
		$resp->Objects = $retObjects;
		$resp->Reports = BizErrorReport::getReports();
		return $resp;
	}
}
