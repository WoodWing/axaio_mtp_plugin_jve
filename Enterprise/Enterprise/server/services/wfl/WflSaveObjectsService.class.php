<?php
/**
 * SaveObjects workflow business service.
 *
 * @package Enterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflSaveObjectsService extends EnterpriseService
{
	public function execute( WflSaveObjectsRequest $req )
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
		
		// Run the service
		$resp = $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflSaveObjects', 	
			true,  		// check ticket
			false   	// don't use transaction at request level, we'll do transaction per object
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

	public function runCallback( WflSaveObjectsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$retObjects = array();
		if ($req->Objects) foreach ($req->Objects as $object) {
			// We do a transaction per object
			BizSession::startTransaction();
			$semaphoreId = '';
			try {
				switch( $object->MetaData->BasicMetaData->Type ) {
					case 'PublishForm':
						require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
						$bizSemaphore = new BizSemaphore();
						$objId = $object->MetaData->BasicMetaData->ID;
						$semaphoreName = 'SavePublishForm_'.$objId;
						$bizSemaphore->setLifeTime( 10 ); // 10 seconds.
						$attempts = array( 1, 2, 5, 10, 15, 25, 50, 125, 250, 500, 500, 500 ); // in milliseconds (roughly 2secs wait in total)
						$bizSemaphore->setAttempts( $attempts );
						$semaphoreId = $bizSemaphore->createSemaphore( $semaphoreName );
					break;
					case 'Layout':
					case 'LayoutTemplate':
					case 'LayoutModule':
					case 'LayoutModuleTemplate':
						require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
						$objId = $object->MetaData->BasicMetaData->ID;
						$semaphoreId = BizObject::createSemaphoreForSaveLayout( $objId );
					break;
				}
				// Create object
				$retObjects[] = BizObject::saveObject( $object, $this->User /* from super class*/, $req->CreateVersion, $req->Unlock );
			} catch ( BizException $e ) {
				if( $semaphoreId ) {
					BizSemaphore::releaseSemaphore( $semaphoreId );
				}
				// Cancel session and re-throw exception to stop the service:
				BizSession::cancelTransaction();
				throw( $e );
			}

			if( $semaphoreId ) {
				BizSemaphore::releaseSemaphore( $semaphoreId );
			}
			BizSession::endTransaction();
		}

		$resp = new WflSaveObjectsResponse();
		$resp->Objects = $retObjects;
		$resp->Reports = BizErrorReport::getReports();
		return $resp;
	}
}
