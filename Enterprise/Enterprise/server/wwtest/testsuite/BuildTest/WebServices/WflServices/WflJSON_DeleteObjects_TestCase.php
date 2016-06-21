<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/vendor/autoload.php';
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
require_once BASEDIR.'/server/protocols/json/Client.php';
require_once BASEDIR.'/server/protocols/json/Services.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflJSON_DeleteObjects_TestCase extends TestCase
{
	// Article test object details
	private $articleId;
	private $articleName;
	private $client = null;
	private $incrementalID = null;
	
	// Properties to use for a test layout
	private $publicationInfo;
	private $issueInfo;
	
	private $ticket;
	private $user;
		
	public function getDisplayName() { return 'JSON-RPC: Delete Objects'; }
	public function getTestGoals()   { return 'Checks if Objects can be deleted and purged successfully by using JSON-RPC'; }
	public function getTestMethods() { return 'Lock an Object and tries to delete it, Unlock the same object and delete the object again.'; }
	public function getPrio()        { return 421; }
	
	final public function runTest()
	{
		$this->client = new WW_JSON_Client(LOCALURL_ROOT.INETROOT.'/index.php?protocol=JSON');
		$this->incrementalID = 1;

		// Getting session variables
		// get ticket ( retrieved from wflLogon Test )
   		$vars = $this->getSessionVariables();
   		$this->ticket = $vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}
		
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
			$map = $map; // keep analyzer happy
		}
		try {
			$action = $permanent ? 'purge' : 'delete';
			$goal = __FUNCTION__.': Trying to '.$action.' object '.$objId .' from ' . $areas['0'] . ' area';
			$this->logServiceReqGoal( $goal );
			
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
			$DeleteObjectsRequest = new WflDeleteObjectsRequest();
			$DeleteObjectsRequest->Ticket = $this->ticket;
			$DeleteObjectsRequest->IDs = array($objId);
			$DeleteObjectsRequest->Permanent = $permanent;
			$DeleteObjectsRequest->Areas = $areas;

			$request = $this->client->request('DeleteObjects', (string) $this->incrementalID, array('req' => $DeleteObjectsRequest));
			if ( $expectedErrorCode ) {
				// Add the expected error code to the GET parameters so it becomes a 'INFO' log entry
				$request->getQuery()->add( 'expectedError', $expectedErrorCode );
			}
			$response = $request->send();

			if (!$response instanceof \Graze\Guzzle\JsonRpc\Message\ErrorResponse) {

				$services = new WW_JSON_Services;

				$result = $response->getResult();
				$result = $services->arraysToObjects($result);
				$result = WW_JSON_Services::restructureObjects($result);

				$action = $permanent ? 'purged' : 'deleted';
				if( $expectDeleted ) {
					if( count($result->Reports) > 0 ) {
						// We expect the object to be deleted but we got error.
						$title = __FUNCTION__.': Unexpected result.';
						$message = 'ObjectID '. $objId .' should be ' .$action. ' from ' . $areas[0] . ' area but is now not ' .$action . '!';
					} else {
						$title = __FUNCTION__.': Expected result.';
						$message = 'ObjectID ' . $objId . ' is ' .$action. ' from ' . $areas['0'] . ' area.';
						$error = false; // expected behavior
					}
				} else {
					if( count($result->Reports) > 0 ) {
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
			}
		}
		catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			LogHandler::Log( 'JSON-RPC', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch (Exception $e) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
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
			$RestoreObjectsRequest = new WflRestoreObjectsRequest( $this->ticket, array( $objId) );

			$request = $this->client->request('RestoreObjects', (string) $this->incrementalID, array('req' => $RestoreObjectsRequest));
			$response = $request->send();

			if (!$response instanceof \Graze\Guzzle\JsonRpc\Message\ErrorResponse) {

				$services = new WW_JSON_Services;

				$result = $response->getResult();
				$result = $services->arraysToObjects($result);
				$result = WW_JSON_Services::restructureObjects($result);

				if( count($result->Reports) == 0 ) { // Introduced in v8.0
					$title = __FUNCTION__.': Expected result.';
					$message = 'ObjectID ' . $objId . ' is restored from trashCan to workflow area.';
				}
				else { // Introduced in v8.0
					$errMsg = '';
					foreach( $result->Reports as $report ){
						$objId = $report->BelongsTo->ID;
						foreach( $report->Entries as $reportEntry ) {
							$errMsg .= $reportEntry->Message . PHP_EOL;
						}
						$title = __FUNCTION__.': Unexpected result.';
						$message = 'ObjectID ' . $objId . ' should be restored from trashCan to workflow area but it is now not restored!.' .
							$errMsg;
						$error = true; // Error occured, which is not expected so flag it and raise error
					}
				}
			}
		}
		catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			LogHandler::Log( 'JSON-RPC', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch (Exception $e) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
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
			$UnlockObjectsRequest = new WflUnlockObjectsRequest( $this->ticket, array($id));

			$request = $this->client->request('UnlockObjects', (string) $this->incrementalID, array('req' => $UnlockObjectsRequest));
			$response = $request->send();

			if (!$response instanceof \Graze\Guzzle\JsonRpc\Message\ErrorResponse) {

				$services = new WW_JSON_Services;

				$result = $response->getResult();
				$result = $services->arraysToObjects($result);
				$result = WW_JSON_Services::restructureObjects($result);

				if ( count($result->Reports) > 0 ) {

					$title = __FUNCTION__.': Unexpected result.';
					$message = 'ObjectID ' . $id . ' is NOT unlocked.';
					$error = true;
				}
				else {
					$title = __FUNCTION__.': Expected result.';
					$message = 'ObjectID ' . $id . ' is unlocked.';
				}
			}
		}
		catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			LogHandler::Log( 'JSON-RPC', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch (Exception $e) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
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
			$map = $map; // keep analyzer happy
		}
		$error = false; // we don't want any unexpected behavior to occur.
		$title = '';
		$message = '';
		try {
			$lockMsg = $lock ? ' and lock ' : ' ';
			$goal = __FUNCTION__.': Trying to get' . $lockMsg . 'object ' . $objID .' from '  . $areas['0'] . ' area.';
			$this->logServiceReqGoal( $goal );
			
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsResponse.class.php';

			$GetObjectsRequest = new WflGetObjectsRequest( $this->ticket );
			$GetObjectsRequest->IDs = array( $objID );
			$GetObjectsRequest->Lock = $lock;
			$GetObjectsRequest->Rendition = 'none';
			$GetObjectsRequest->RequestInfo = null;
			$GetObjectsRequest->HaveVersions = null;
			$GetObjectsRequest->Areas = $areas;
			$GetObjectsRequest->EditionId = null;

			$request = $this->client->request('GetObjects', (string) $this->incrementalID, array('req' => $GetObjectsRequest));
			if ( $expectedErrorCode ) {
				// Add the expected error code to the GET parameters so it becomes a 'INFO' log entry
				$request->getQuery()->add( 'expectedError', $expectedErrorCode );
			}
			$response = $request->send();

			if (!$response instanceof \Graze\Guzzle\JsonRpc\Message\ErrorResponse) {

				$services = new WW_JSON_Services;

				$result = $response->getResult();
				$result = $services->arraysToObjects($result);
				$result = WW_JSON_Services::restructureObjects($result);

				if( $expectObj ){
					$title = __FUNCTION__.': Expected result.';
					$message = 'ObjectID '. $objID .' found in ' . $areas[0] . ' area.';
				} else { // $expectObj = false, we didn't expect an object but it returned! which is wrong, so raise error
					$title = __FUNCTION__.': Unexpected result.';
					$message = 'ObjectID '. $objID .' should not be found in ' . $areas[0] . ' area.';
					$error = true; // Error occured, which is not expected so flag it and raise error
				}
			}
		}
		catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			LogHandler::Log( 'JSON-RPC', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		catch (Exception $e) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
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
			LogHandler::Log('DeleteObjectTest',$logLevel, $title .'<br/>Details: '. $message );
		}
 	}
}