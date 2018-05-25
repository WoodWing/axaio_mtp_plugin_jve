<?php
/**
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflDeleteObjects_TestCase extends TestCase
{
	// Article test object details
	private $articleId;
	private $articleName;
	
	// Properties to use for a test layout
	private $publicationInfo;
	private $issueInfo;
	
	private $ticket;
	private $user;
		
	public function getDisplayName() { return 'Delete Objects'; }
	public function getTestGoals()   { return 'Checks if Objects can be deleted and purged successfully'; }
	public function getTestMethods() { return 'Lock an Object and tries to delete it, Unlock the same object and delete the object again.'; }
	public function getPrio()        { return 9; }
	
	final public function runTest()
	{
		// Getting session variables
		// get ticket ( retrieved from wflLogon Test )
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}
		
		// Apply the ticket to current session to avoid invalid ticket error during object lock,
		// which creates a EnterpriseEvent, which requires a ticket for that server job.
		BizSession::checkTicket( $this->ticket );
		
		// get objectIds for object deletion
		if( is_null( $vars['BuildTest_WebServices_WflServices']['objIds']) ){
			$this->setResult( 'ERROR',  'There are no testing objects to be deleted.', 'Please enable the WflCreateObject test.' );
			return;
		}
		$objIds = $vars['BuildTest_WebServices_WflServices']['objIds'];
		$this->getUser();

		foreach( $objIds as $objId ) {
			LogHandler::Log( 'DeleteObjectTest', 'INFO', 'objId:' . $objId );
			LogHandler::Log( 'DeleteObjectTest', 'INFO', 'STEP#01: Try to delete a locked object (from Workflow into TrashCan) which should fail.'.
				'Then get it from Workflow should work, but get it from TrashCan should fail.' );
			$this->getObject( $objId, true/*lock*/, array('Workflow'), true/*expectObj*/ );
			$this->deleteObject( $objId, false/*permanent*/, array('Workflow'), false/*expectDeleted*/, 'S1021' );
			$this->getObject( $objId, false/*lock*/, array('Workflow'), true/*expectObj*/ ); // should be found in Workflow
			$this->getObject( $objId, false/*lock*/, array('Trash'), false/*expectObj*/, 'S1029' ); // should NOT be found in Trash
			$this->unLock( $objId ); // unlock object
			
			LogHandler::Log( 'DeleteObjectTest', 'INFO', 'STEP#02: Delete an unlocked object (from Workflow into TrashCan) which should work. '.
				'Then getting it from Workflow should fail, but getting it from TrashCan should work.' );
			$this->deleteObject($objId, false/*permanent*/, array('Workflow'), true/*expectDeleted*/); // delete $objId
			$this->getObject( $objId, false/*lock*/, array('Workflow'), false/*expectObj*/, 'S1029' ); // should NOT be found in Workflow
			$this->getObject( $objId, false/*lock*/, array('Trash'), true/*expectObj*/ ); // should be found in Trash
			
			LogHandler::Log( 'DeleteObjectTest', 'INFO', 'STEP#03: Restore deleted object (from TrashCan into Workflow) whould should work. '.
				'Then get it from Workflow should work, but get it from TrashCan should fail.' );
			$this->restoreObject( $objId );
			$this->getObject( $objId, false/*lock*/, array('Workflow'), true/*expectObj*/ ); // should be found in Workflow
			$this->getObject( $objId, false/*lock*/, array('Trash'), false/*expectObj*/, 'S1029' ); // should NOT be found in Trash
			
			// Delete ALL Objects Into TrashCan
			LogHandler::Log( 'DeleteObjectTest', 'INFO', 'STEP#04: Delete restored object (from Workflow into TrashCan), which should work.'.
				'Then getting it from Workflow should fail, but getting it from TrashCan should work.' );
			$this->deleteObject( $objId, false/*permanent*/, array('Workflow'), true/*expectDeleted*/);
			$this->getObject( $objId, false/*lock*/, array('Workflow'), false/*expectObj*/, 'S1029' ); // should NOT be found in Workflow
			$this->getObject( $objId, false/*lock*/, array('Trash'), true/*expectObj*/ ); // should be found in Trash
			
			LogHandler::Log( 'DeleteObjectTest', 'INFO', 'STEP#05: Purge deleted object (from TrashCan), which should work.'.
				'Then getting it from both Workflow and TrashCan should fail.' );
			$this->deleteObject( $objId, true/*permanent*/, array('Trash'), true/*expectDeleted*/);
			$this->getObject( $objId, false/*lock*/, array('Workflow'), false/*expectObj*/, 'S1029' ); // should NOT be found in Workflow
			$this->getObject( $objId, false/*lock*/, array('Trash'), false/*expectObj*/, 'S1029' ); // should NOT be found in Trash
		}
	}
	
	/**
	 * Delete object with the given objectId and area (workflow / trash).
	 * permanent = False; areas = Workflow	: Deleting workflow objects into trashCan. (can be restored back into workflow)
	 * permanent = True; areas = Workflow	: Deleting workflow objects out from Enterprise. (Purging and not recoverable)
	 * permanent = False; areas = Trash		: Not possible!
	 * permanent = True; areas = Trash		: Purging deleted objects (from trashCan) out from Enterprise. (not recoverable)
	 * 
	 * When $expectDeleted = true
	 * 	We expect the object to be successfully deleted, but when it doesn't, 
	 * 	we are having problem, this test failed; we raise error and log it
	 * 
	 * When $expectDeleted = false
	 * 	We expect the deleteObject to be failed, but when it manage to delete, 
	 * 	we are having problem, this test failed; we raise error and log it
	 * 
	 * @param int $objId
	 * @param bool $permanent
	 * @param array $areas
	 * @param bool $expectDeleted
	 * @param string $expectedErrorCode Server error code (S-code) to supress at logging.
	 */
	private function deleteObject( $objId, $permanent, array $areas, $expectDeleted, $expectedErrorCode = '' )
	{
		$error = true; // Assume unexpected behavior
		if( $expectedErrorCode ) { // when object is checked out
			$map = new BizExceptionSeverityMap( array( $expectedErrorCode => 'INFO' ) );
		}
		try {
			$action = $permanent ? 'purge' : 'delete';
			$goal = __FUNCTION__.': Trying to '.$action.' object '.$objId .' from ' . $areas['0'] . ' area';
			$this->logServiceReqGoal( $goal );
			
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$service = new WflDeleteObjectsService();
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs = array($objId);
			$request->Permanent = $permanent;
			$request->Areas = $areas;
			$response = $service->execute($request);

			$action = $permanent ? 'purged' : 'deleted';
			if( $expectDeleted ) {
				if( $response->Reports ) { 
					// We expect the object to be deleted but we got error.
					$title = __FUNCTION__.': Unexpected result.';
					$message = 'ObjectID '. $objId .' should be ' .$action. ' from ' . $areas[0] . ' area but is now not ' .$action . '!';
				} else {
					$title = __FUNCTION__.': Expected result.';
					$message = 'ObjectID ' . $objId . ' is ' .$action. ' from ' . $areas['0'] . ' area.';
					$error = false; // expected behavior
				}
			} else {
				if( $response->Reports ) { 
					$title = __FUNCTION__.': Expected result.';
					$message = 'ObjectID ' . $objId . ' is not ' .$action. ' from ' . $areas['0'] . ' area.';
					$error = false; // expected behavior
				} else {
					// We expect the object NOT to be deleted for which we should receive an error.
					// However, we received no error which is WRONG, and so we raise an error.
					$title = __FUNCTION__.': Unexpected result.';
					$message =  'ObjectID '. $objId .' should not be ' .$action. ' from ' . $areas[0] . ' area but is now ' .$action. '!';
				}
			}
		} catch( BizException $e ) {
			$title = __FUNCTION__.': '.$e->getMessage();
			$message = $e->getDetail();
		}
		$this->logServiceResp( $title, $message, $error );
	}
	
	/**
	 * Restore the given objectId from TrashCan back into Workflow area.
	 * Raise error and log it when we encounter problem restoring.
	 *
	 * @param int $objId deletedObject DB Id
	 */
	private function restoreObject( $objId )
	{
		$goal = __FUNCTION__.': Trying to restore object ' .$objId.  ' from trashCan to workflow area.';
		$this->logServiceReqGoal( $goal );
		$error = false; // we don't want any unexpected behavior to occur.
		try{
			require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreObjectsResponse.class.php';
			require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';
			$service = new WflRestoreObjectsService();
			$request = new WflRestoreObjectsRequest( $this->ticket, array( $objId) );
			$response = $service->execute($request);
			if( $response->Reports ) { // Introduced in v8.0
				$errMsg = '';
				$title = '';
				$message = '';
				foreach( $response->Reports as $report ){
					$objId = $report->BelongsTo->ID;
					foreach( $report->Entries as $reportEntry ) {
						$errMsg .= $reportEntry->Message . PHP_EOL;
					}					
					$title = __FUNCTION__.': Unexpected result.';
					$message = 'ObjectID ' . $objId . ' should be restored from trashCan to workflow area but it is now not restored!.' .
						$errMsg;
					$error = true; // Error occured, which is not expected so flag it and raise error				
				}
			} else {
				$title = __FUNCTION__.': Expected result.';
				$message = 'ObjectID ' . $objId . ' is restored from trashCan to workflow area.';
			}
		} catch ( BizException $e ){
			$sCode = $e->getErrorCode();
			$title = __FUNCTION__.': Unexpected result.';
			$message = 'ObjectID ' . $objId . ' should be restored from trashCan to workflow area but it is now not restored!.' .
				$sCode . ':' . $e->getDetail();
			$error = true; // Error occured, which is not expected so flag it and raise error
		}
		$this->logServiceResp( $title, $message, $error );
	}
	
	/**
	 * Get the user for testing specified in configserver.php
	 *
	 */
	private function getUser()
	{
		$suiteOpts = unserialize( TESTSUITE );
		$this->user = $suiteOpts['User'];
	}
	
	/**
	 * Unlock an object given the DB Id of the object.
	 * Raise error and log it when we encounter problem un-locking the object
	 *
	 * @param int $id DB id of the object to be unlocked.
	 */
	private function unLock( $id )
	{
		$goal = __FUNCTION__.': Trying to unlock object ' .$id;
		$this->logServiceReqGoal( $goal );
		$error = false; // we don't want any unexpected behavior to occur.
		try{
			require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsResponse.class.php';
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$service = new WflUnlockObjectsService();
			$request = new WflUnlockObjectsRequest( $this->ticket, array($id));
			/*$response =*/ $service->execute($request);
			$title = __FUNCTION__.': Expected result.';
			$message = 'ObjectID ' . $id . ' is unlocked.';
		}catch ( BizException $e ){
			$sCode = $e->getErrorCode();
			$title = __FUNCTION__.': Unexpected result.';
			$message = 'Encounter error while unlocking objectID ' . $id . '.' .
					$sCode . ':'. $e->getDetail();
			$error = true; // Error occured, which is not expected so flag it and raise error
		}
		$this->logServiceResp( $title, $message, $error );
	}
	
	/**
	 * Gets the object in the specified area.
	 * The object can be workflow object (Found in Workflow area) or
	 * deleted object (Found in Trash area).
	 * 
	 *  **ErrorCode: Server error number prefixed with 'S'.
	 *      Errors in 1xxx range are workflow errors.
	 * 
	 * When $expectObj = true:
	 * 	We expect an object returned from getobject(), if it doesn't, 
	 * 	we have some problem, meaning test failed; we raise error and log it
	 * 
	 * When $expectObj = false;
	 * 	We don't expect an object to be returned (meaning, we are expecting 'Object not found' error).
	 * 	If it returned an object, we have problem, this test failed, we raise error and log it
	 *
	 * @param int $objID DB id of the object
	 * @param bool $lock
	 * @param array $areas Workflow or Trash
	 * @param bool $expectObj
	 * @param string $expectedErrorCode Server error code (S-code) to supress at logging.
	 */
	private function getObject( $objID, $lock, $areas, $expectObj, $expectedErrorCode = '' )
	{
		if( $expectedErrorCode ) {
			$map = new BizExceptionSeverityMap( array( $expectedErrorCode => 'INFO' ) );
		}
		$error = false; // we don't want any unexpected behavior to occur.
		$title = '';
		$message = '';
		try {
			$lockMsg = $lock ? ' and lock ' : ' ';
			$goal = __FUNCTION__.': Trying to get' . $lockMsg . 'object ' . $objID .' from '  . $areas['0'] . ' area.';
			$this->logServiceReqGoal( $goal );
			
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			if( BizObject::getObject( $objID, $this->user, $lock, 'none', null/*requestInfo*/,null/*haveVersion*/,false/*checkRights*/,$areas) ){
				if( $expectObj ){
					$title = __FUNCTION__.': Expected result.';
					$message = 'ObjectID '. $objID .' found in ' . $areas[0] . ' area.';	
				} else { // $expectObj = false, we didn't expect an object but it returned! which is wrong, so raise error
					$title = __FUNCTION__.': Unexpected result.';
					$message = 'ObjectID '. $objID .' should not be found in ' . $areas[0] . ' area.';
					$error = true; // Error occured, which is not expected so flag it and raise error
				}
			}
		} catch( BizException $e ) {
			$sCode = $e->getErrorCode();
			if( !$expectObj && $sCode == 'S1029' ){ 
				$title = __FUNCTION__.': Expected result.';
				$message = 'ObjectID '. $objID .' not found in ' . $areas[0] . ' area.';
			} else { // $expectObj=true, we expected an object but it didn't return object, which is wrong, so raise error
				$title = __FUNCTION__.': Unexpected result.';
				$message = 'ObjectID '. $objID .' should be found in ' . $areas[0] . ' area but is now not found!';
				$error = true; // Error occured, which is not expected so flag it and raise error
			}
		}
		
		$this->logServiceResp( $title, $message, $error );
	}

	/**
	 * Logging on each service being called.
	 *
	 * @param string $message
	 */
	private function logServiceReqGoal( $message )
	{
		LogHandler::Log( 'DeleteObjectTest', 'INFO', $message );	
	}
	
	/**
	 * It does logging and raise error on the BuildTest if there's error.
	 *
	 * @param string $title A brief idea on what is the Message about.
	 * @param string $message Tip or detailed message of the info/error message.
	 * @param bool $error Determines whether to raise error on the BuildTest.
	 */
	private function logServiceResp( $title, $message, $error )
	{
		$logLevel = $error ? 'ERROR' : 'INFO';
		if( $error ) { 
			// Errors are implicitly logged, so just setting result is enough.
			// And, INFO should not be logged onto screen, so we skip those.
			$this->setResult( 'ERROR', $title .'<br/>Details: '. $message );
		} else {
			LogHandler::Log('DeleteObjectTest',$logLevel, "{$title}\r\nDetails: {$message}" );
		}
 	}
}