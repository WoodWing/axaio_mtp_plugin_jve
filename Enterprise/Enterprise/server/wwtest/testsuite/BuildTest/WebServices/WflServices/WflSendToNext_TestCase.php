<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflSendToNext_TestCase extends TestCase
{
	private $ticket = null;
	private $vars = null;
	private $publication = null;
	private $pubChannel = null;
	private $printTarget = null; // Target
	private $utils = null; // WW_Utils_TestSuite
	private $wflServicesUtils = null;

	// Objects used for testing
	private $createdObjects = array();
	private $userGroup = null;
	private $users = array();
	private $categories = array();
	private $states = array();
	private $routing = array();
	private $issues = array();
	private $editions = array();

	public function getDisplayName() { return 'Send To Next'; }
	public function getTestGoals()   { return 'Checks if the Send To Next is used to send object(s) to their next status.'; }
	public function getPrio()        { return 170; }
	public function getTestMethods() { return
		 'Call SendToNext service and validate the responses.
		 <h3>Scenario 1</h3>
		 <ol>
		 	<li>Dossier 1 in category News with status Draft should go to status Process and route to user 4</li>
		 	<li>Dossier 2 in category News with status Process should go to status Finished and thus not route to anyone</li>
		 </ol>
		 <h3>Scenario 2</h3>
		 <ol>
		 	<li>Dossier 1 in category News with status Draft should go to status Process but not route to anyone</li>
		 	<li>Dossier 2 in category News with status Process should go to status Finished and thus not route to anyone</li>
		 </ol>
		 <h3>Scenario 3</h3>
		 <ol>
		 	<li>Dossier 1 in category News with personal status should not route to anyone</li>
		 	<li>Dossier 2 in category Sport with personal status should not route to anyone</li>
		 </ol>
		 ';
	}

	final public function runTest()
	{
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		if( !$this->wflServicesUtils->initTest( $this, 'STN' ) ) {
			return;
		}

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
		$this->vars = $this->getSessionVariables();
		$this->ticket = $this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->publication = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->printTarget = $this->vars['BuildTest_WebServices_WflServices']['printTarget'];

		if( !$this->ticket || !$this->publication || !$this->printTarget ) {
			$this->setResult( 'ERROR',  'Could not find data to test with.', 'Please enable the WflLogon test.' );
			return;
		}

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		try {
			$this->setupTestData();

			// Scenario 1
			$this->testRouteObjectsToUser1();

			// Scenario 2
			$this->testRouteObjectsToUser2();

			// Scenario 3
			$this->testRouteObjectsHavingPersonalState();
		} 
		catch( BizException $e ) {
			$e = $e; // keep analyzer happy
		}

		$this->tearDownTestData();

		$this->setSessionVariables( $this->vars );
	}

	/**
 	 * Creates issues with overruled brands.
	 *
	 * @return bool Whether or not the setup was successful.
	 */
	private function setupTestData()
	{
		$this->setupAdmPublication();
		$this->setupAdmPubChannel();
		$this->setupAdmGroups();
		$this->setupAdmUsers();
		$this->setupAdmCategories();
		$this->setupAdmStates();
		$this->setupAdmIssues();
		$this->setupAdmEditions();
		$this->setupAdmAuthorization();
	}

	/**
	 * Removes created objects during testing and removes issues with overruled brands.
	 *
	 * @return bool Whether or not the deletions were successful.
	 */
	private function tearDownTestData()
	{
		$this->cleanupObjects( false );
		$this->cleanupAdmAuthorization();
		$this->cleanupAdmEditions();
		$this->cleanupAdmIssues();
		$this->cleanupAdmRouting( false );
		$this->cleanupAdmStates();
		$this->cleanupAdmCategories();
		$this->cleanupAdmUsers();
		$this->cleanupAdmGroups();
		$this->cleanupAdmPubChannel();
		$this->cleanupAdmPublication();
	}

	/**
	 * Creates categories for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmCategories()
	{
		$stepInfo = 'Creating category 1 for Send To Next.';
		$this->categories[] = $this->wflServicesUtils->createCategory( $this->publication->Id, $stepInfo );
		
		$stepInfo = 'Creating category 2 for Send To Next.';
		$this->categories[] = $this->wflServicesUtils->createCategory( $this->publication->Id, $stepInfo );
	}
	
	/**
	 * Removes the categories that were used for testing.
	 */
	private function cleanupAdmCategories()
	{
		if( $this->categories ) foreach( $this->categories as $key => $category ) {
			try {
				$stepInfo = 'Creating category #'.($key+1).' for Send To Next.';
				$this->wflServicesUtils->deleteCategory( $stepInfo, $this->publication->Id, $category->Id );
			}
			catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
		}
		$this->categories = array();
	}

	/**
	 * Creates statuses for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmStates()
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;
	
		$this->states['STN Finished'] = $this->wflServicesUtils->createStatus( 
			'STN Finished '.$postfix, 'Dossier', $this->publication->Id, 0 );

		$this->states['STN Process'] = $this->wflServicesUtils->createStatus( 
			'STN Process '.$postfix, 'Dossier', $this->publication->Id, $this->states['STN Finished']->Id );

		$this->states['STN Draft'] = $this->wflServicesUtils->createStatus( 
			'STN Draft '.$postfix, 'Dossier', $this->publication->Id, $this->states['STN Process']->Id );
	}

	/**
	 * Removes the statuses that were used for testing.
	 */
	private function cleanupAdmStates()
	{
		if( $this->states ) foreach( $this->states as $state ) {
			try {
				$this->wflServicesUtils->deleteStatus( $state->Id );
			}
			catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
		}
		$this->states = array();
	}

	/**
	 * Creates users for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmUsers()
	{
		// We create more users than we use (only 1 and 4)
		for( $i = 1; $i <= 4; $i++ ) {

			$stepInfo = 'Creating user for Send To Next.';
			$user = $this->wflServicesUtils->createUser( $stepInfo );
			$this->users[] = $user;
			$this->wflServicesUtils->addUsersToGroup( $stepInfo, $this->userGroup->Id, array($user->Id) );
		}
	}

	/**
	 * Removes the users that were used for testing.
	 */
	private function cleanupAdmUsers()
	{
		if( $this->users ) foreach( $this->users as $user ) {
			try {
				$stepInfo = 'Remove user from user group.';
				$this->wflServicesUtils->removeUsersFromGroup( $stepInfo, $this->userGroup->Id, array($user->Id) );
			}
			catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
			try {
				$stepInfo = 'Deleting user for Send To Next.';
				$this->wflServicesUtils->deleteUser( $stepInfo, $user->Id );
			}
			catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
		}
		$this->users = array();
	}

	/**
	 * Finds user group for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmGroups()
	{
		$userGroup = $this->vars['BuildTest_WebServices_WflServices']['userGroup'];

		// Get our user group
		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
		$request = new AdmGetUserGroupsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$stepInfo = 'Getting test suite user group';
		$response = $this->utils->callService( $this, $request, $stepInfo, null, null, true );

		$this->userGroup = null;
		foreach( $response->UserGroups as $userGroupObj ) {
			if( $userGroupObj->Name == $userGroup->Name ) {
				$this->userGroup = $userGroupObj;
				break;
			}
		}
		$this->assertNotNull( $this->userGroup,
				'Could not find the test user group "'.$userGroup->Name.'". '.
				'Please check the TESTSUITE setting in configserver.php and the user groups.' );
	}
	
	/**
	 * Forgets the user group that was used for testing.
	 */
	private function cleanupAdmGroups()
	{
		$this->userGroup = null;
	}

	/**
	 * Finds a brand for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmPublication()
	{
		$this->publication = $this->wflServicesUtils->getPublication();
	}

	/**
	 * Forgets the brand that was used for testing.
	 */
	private function cleanupAdmPublication()
	{
		$this->publication = null;
	}

	/**
	 * Creates routing profiles for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmRouting( $scenario )
	{
		$publicationID = $this->publication->Id;

		$categoryNewsID = $this->categories[0]->Id;
		$categorySportID = $this->categories[1]->Id;

		$stateDraftID = $this->states['STN Draft']->Id;
		$stateProcessID = $this->states['STN Process']->Id;

		$routeTo2 = $this->users[1]->Name;
		$routeTo4 = $this->users[3]->Name;

		switch ($scenario) {
			case 1:
				LogHandler::Log( 'TestSuite', 'DEBUG', '<b>Test: Setup routing profile for state "STN Draft" to '.$routeTo4.'</b>' );
				$this->routing[] = $this->wflServicesUtils->createRoutingProfile( $publicationID, $categoryNewsID, $stateDraftID, $routeTo4 );

				LogHandler::Log( 'TestSuite', 'DEBUG', '<b>Test: Setup routing profile for state "STN Process" to '.$routeTo4.'</b>' );
				$this->routing[] = $this->wflServicesUtils->createRoutingProfile( $publicationID, $categoryNewsID, $stateProcessID, $routeTo4 );
			break;
			
			case 2:
				LogHandler::Log( 'TestSuite', 'DEBUG', '<b>Test: Setup routing profile for state "STN Draft" to '.$routeTo2.'</b>' );
				$this->routing[] = $this->wflServicesUtils->createRoutingProfile( $publicationID, $categoryNewsID, $stateDraftID, $routeTo2 );

				LogHandler::Log( 'TestSuite', 'DEBUG', '<b>Test: Setup routing profile for state "STN Process" to no user</b>' );
				$this->routing[] = $this->wflServicesUtils->createRoutingProfile( $publicationID, $categoryNewsID, $stateProcessID, '' );
			break;
			
			case 3:
				LogHandler::Log( 'TestSuite', 'DEBUG', '<b>Test: Setup routing profile for state "STN Draft" to '.$routeTo4.'</b>' );
				$this->routing[] = $this->wflServicesUtils->createRoutingProfile( $publicationID, $categoryNewsID, $stateDraftID, $routeTo4 );

				LogHandler::Log( 'TestSuite', 'DEBUG', '<b>Test: Setup routing profile for state "STN Process" to no user</b>' );
				$this->routing[] = $this->wflServicesUtils->createRoutingProfile( $publicationID, $categorySportID, $stateProcessID, '' );
			break;
		}
	}

	/**
	 * Removes routing profiles that were used for testing.
	 *
	 * @bool $throwBizException Whether or not to throw BizException on failure.
	 * @throws BizException on failure when $throwBizException is true
	 */
	private function cleanupAdmRouting( $throwBizException )
	{
		LogHandler::Log( 'TestSuite', 'DEBUG', '<b>Test: Cleanup routing profiles</b>' );
		if( $this->routing ) foreach( $this->routing as $routingId ) {
			try {
				$this->wflServicesUtils->deleteRoutingProfile( $routingId );
			}
			catch( BizException $e ) {
				if( $throwBizException ) {
					throw $e;
				}
			}
		}
		$this->routing = array();
		
		try {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			BizWorkflow::clearRouteToCache();
		}
		catch( BizException $e ) {
			if( $throwBizException ) {
				throw $e;
			}
		}
	}

	/**
	 * Create the dossier object with the output file attachments
	 *
	 * @param string $dossierName
	 * @param Issue $issue
	 * @param Edition[] $editions
	 * @param AdmState $admState
	 * @param Category $category
	 * @param string $routeTo
	 * @throws BizException on failure
	 */
	private function createDossier( $dossierName, $issue, $editions, $admState, $category, $routeTo )
	{
		$metaData = $this->wflServicesUtils->buildEmptyMetaData();
		
		$metaData->BasicMetaData->Name           = $dossierName;
		$metaData->BasicMetaData->Type           = 'Dossier';
		$metaData->BasicMetaData->Publication    = $this->publication;
		$metaData->BasicMetaData->Category       = $category;
		
		$metaData->WorkflowMetaData->State = new State();
		$metaData->WorkflowMetaData->State->Id   = $admState->Id;
		$metaData->WorkflowMetaData->State->Name = $admState->Name;
		$metaData->WorkflowMetaData->RouteTo	 = $routeTo;
		
		$metaData->ContentMetaData->Format       = '';
		$metaData->ContentMetaData->FileSize     = 0;

		$pubChannel = new PubChannel;
		$pubChannel->Id = $this->pubChannel->Id;
		$pubChannel->Name = $this->pubChannel->Name;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = $editions;

		// Create dossier object
		$object = new Object();
		$object->MetaData 	= $metaData;
		$object->Targets	= array( $target );

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request          = new WflCreateObjectsRequest();
		$request->Ticket  = $this->ticket;
		$request->Lock    = false;
		$request->Messages = null;
		$request->ReadMessageIDs = false;
		$request->AutoNaming = false;
		$request->Objects = array( $object );
		
		$stepInfo = 'Create new dossier for Send To Next service.';
		$response = $this->utils->callService( $this, $request, $stepInfo, null, null, true );
		$this->assertAttributeCount( 1, 'Objects', $response ); // check $response->Objects[0]
		$this->assertInstanceOf( 'Object', $response->Objects[0] );

		return $response->Objects[0];
	}
	
	/**
	 * Deletes the object that were used for testing.
	 *
	 * @bool $throwBizException Whether or not to throw BizException on failure.
	 * @throws BizException on failure when $throwBizException is true
	 */
	private function cleanupObjects( $throwBizException )
	{
		if( $this->createdObjects ) foreach( $this->createdObjects as $createdObject ) {
			try {
				$ID = $createdObject->MetaData->BasicMetaData->ID;
				$errorReport = '';
				$this->wflServicesUtils->deleteObject( $ID, 'Delete Dossier '.$ID, $errorReport );
			}
			catch( BizException $e ) {
				if( $throwBizException ) {
					throw $e;
				}
			}
		}
		$this->createdObjects = array();
	}

	/**
	 * Tests the SendToNext service.
	 *
	 * @throws BizException on failure
	 */
	private function testRouteObjectsToUser1()
	{
		$user1 = $this->users[0];
		$user4 = $this->users[3];
		$categoryNews = $this->categories[0];
		$issue = $this->issues[0];

		$stateDraft = $this->states['STN Draft'];
		$stateProcess = $this->states['STN Process'];

		$this->createdObjects['Dossier 1'] = $this->createDossier( 'STN Scenario 1 Dossier 1', $issue, $this->editions, $stateDraft, $categoryNews, $user1->Name );
		$this->createdObjects['Dossier 2'] = $this->createDossier( 'STN Scenario 1 Dossier 2', $issue, $this->editions, $stateProcess, $categoryNews, $user1->Name );

		// Dossier 1: in News and Draft, 'Route To' to user 4
		// Dossier 2: in News and Process 'Route To' to user 4
		$IDs[] = $this->createdObjects['Dossier 1']->MetaData->BasicMetaData->ID;
		$IDs[] = $this->createdObjects['Dossier 2']->MetaData->BasicMetaData->ID;

		$this->setupAdmRouting(1);

		$stepInfo = 'Test if the objects will be routed to user 1';
		$response = $this->sendToNext( $stepInfo, $IDs );
		$this->assertAttributeInternalType( 'array', 'RoutingMetaDatas', $response );

		$expectedStatusIDs = array( $this->states['STN Process']->Id, $this->states['STN Finished']->Id );
		$expectedDefaultRouteTos = array( $user4->Name, '' );
		$expectedRouteTos = array( $user4->Name, '' );

		foreach( $response->RoutingMetaDatas as $responseRoutingMetaData ) {
			$this->assertContains( $responseRoutingMetaData->State->Id, $expectedStatusIDs,
					'Scenario 1: Response State ID '.$responseRoutingMetaData->State->Id.
					' doesn\'t match expected status ID ('.implode(',',$expectedStatusIDs).')' );
			$this->assertContains( $responseRoutingMetaData->State->DefaultRouteTo, $expectedDefaultRouteTos,
					'Scenario 1: Response State Default Route To '.$responseRoutingMetaData->State->DefaultRouteTo.
					' doesn\'t match expected default route to ('.implode(',',$expectedDefaultRouteTos).')' );
			$this->assertContains( $responseRoutingMetaData->RouteTo, $expectedRouteTos,
					'Scenario 1: Response Route To '.$responseRoutingMetaData->RouteTo.
					' doesn\'t match expected route to ('.implode(',',$expectedRouteTos).')' );
		}

		$this->cleanupObjects( true );
		$this->cleanupAdmRouting( true );
	}

	/**
	 * Tests the SendToNext service.
	 *
	 * @throws BizException on failure
	 */
	private function testRouteObjectsToUser2()
	{
		$user1 = $this->users[0];
		$categoryNews = $this->categories[0];
		$issue = $this->issues[0];

		$stateDraft = $this->states['STN Draft'];
		$stateProcess = $this->states['STN Process'];

		$this->createdObjects['Dossier 1'] = $this->createDossier( 'STN Scenario 2 Dossier 1', $issue, $this->editions, $stateDraft, $categoryNews, $user1->Name );
		$this->createdObjects['Dossier 2'] = $this->createDossier( 'STN Scenario 2 Dossier 2', $issue, $this->editions, $stateProcess, $categoryNews, $user1->Name );

		// Dossier 1: in News and Draft, 'Route To' to no one
		// Dossier 2: in News and Process 'Route To' to no one
		$IDs[] = $this->createdObjects['Dossier 1']->MetaData->BasicMetaData->ID;
		$IDs[] = $this->createdObjects['Dossier 2']->MetaData->BasicMetaData->ID;

		$this->setupAdmRouting(2);

		$stepInfo = 'Test if the objects will be routed to no one';
		$response = $this->sendToNext( $stepInfo, $IDs );
		$this->assertAttributeInternalType( 'array', 'RoutingMetaDatas', $response );

		// From Draft to Process it shouldn't have a Route To
		$expectedStatusIDs = array( $this->states['STN Process']->Id, $this->states['STN Finished']->Id );
		$expectedDefaultRouteTos = array( '' ); // Stays the same as Draft Default Route To
		$expectedRouteTos = array( '' ); // Stays the same as Draft Route To

		foreach( $response->RoutingMetaDatas as $responseRoutingMetaData ) {
			$this->assertContains( $responseRoutingMetaData->State->Id, $expectedStatusIDs,
					'Scenario 2: Response State ID '.$responseRoutingMetaData->State->Id.
					' doesn\'t match expected status ID' );
			$this->assertContains( $responseRoutingMetaData->State->DefaultRouteTo, $expectedDefaultRouteTos,
					'Scenario 2: Response State Default Route To '.$responseRoutingMetaData->State->DefaultRouteTo.
					' doesn\'t match expected default route to' );
			$this->assertContains( $responseRoutingMetaData->RouteTo, $expectedRouteTos,
					'Scenario 2: Response Route To '.$responseRoutingMetaData->RouteTo.
					' doesn\'t match expected route to' );
		}

		$this->cleanupObjects( true );
		$this->cleanupAdmRouting( true );
	}

	/**
	 * Tests the SendToNext service.
	 *
	 * @throws BizException on failure
	 */
	private function testRouteObjectsHavingPersonalState()
	{
		$this->assertEquals( PERSONAL_STATE, 'ON', 
				'The PERSONAL_STATE option in the configserver.php file should be "ON".' );

		$user1 = $this->users[0];
		$categoryNews = $this->categories[0];
		$categorySport = $this->categories[1];
		$issue = $this->issues[0];

		$personalState = new AdmStatus();
		$personalState->Id = -1;
		$personalState->Name = 'Personal';

		$this->createdObjects['Dossier 1'] = $this->createDossier( 'STN Scenario 3 Dossier 1', $issue, $this->editions, $personalState, $categoryNews, $user1->Name );
		$this->createdObjects['Dossier 2'] = $this->createDossier( 'STN Scenario 3 Dossier 2', $issue, $this->editions, $personalState, $categorySport, $user1->Name );

		// Dossier 1: in News and Personal state, shouldn't allow state change.
		// Dossier 2: in Sport and Personal state, shouldn't allow state change.
		$IDs[] = $this->createdObjects['Dossier 1']->MetaData->BasicMetaData->ID;
		$IDs[] = $this->createdObjects['Dossier 2']->MetaData->BasicMetaData->ID;

		$this->setupAdmRouting(3);

		$stepInfo = 'Test if the objects will not be routed due to their personal state';
		$response = $this->sendToNext( $stepInfo, $IDs );

		$this->assertAttributeInternalType( 'array', 'RoutingMetaDatas', $response );
		$this->assertCount( 0, $response->RoutingMetaDatas );

		$this->assertAttributeInternalType( 'array', 'Reports', $response );
		$this->assertCount( 0, $response->Reports );

		$this->cleanupObjects( true );
		$this->cleanupAdmRouting( true );
	}

	/**
	 * Calls the SendToNext web service.
	 *
	 * @param string $stepInfo
	 * @param integer[] $IDs
	 * @return WflSendToNextResponse
	 */
	private function sendToNext( $stepInfo, $IDs )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendToNextService.class.php';

		$request = new WflSendToNextRequest;
		$request->Ticket = $this->ticket;
		$request->IDs = $IDs;
		$response = $this->utils->callService( $this, $request, $stepInfo, null, null, true );
		return $response;
	}

	/**
	 * Creates authorizations for each combination of statuses and categories.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmAuthorization()
	{
		foreach( $this->categories as $category ) {
			foreach( $this->states as $status ) {
				$result = $this->wflServicesUtils->addAuthorization( $this->publication->Id, 
					$this->issues[0]->Id, $this->userGroup->Id, $category->Id, $status->Id, 1, 'VRWDCKSF');
				$this->assertInternalType( 'integer', $result );
			}
		}
	}

	/**
	 * Removes authorizations that were created for testing.
	 */
	private function cleanupAdmAuthorization()
	{
		if( $this->categories ) foreach( $this->categories as $category ) {
			if( $this->states ) foreach( $this->states as $status ) {
				try {
					$this->wflServicesUtils->removeAuthorization( $this->publication->Id, 
						$this->issues[0]->Id, $this->userGroup->Id, $category->Id, $status->Id );
				}
				catch( BizException $e ) {
					$e = $e; // keep analyzer happy
				}
			}
		}
	}

	/**
	 * Creates an issue for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmIssues()
	{
		$stepInfo = 'Creating issue for Send To Next.';
		$admIssue = $this->wflServicesUtils->createIssue( $stepInfo, 
							$this->publication->Id, $this->pubChannel->Id );

		$issue = new Issue();
		$issue->Id                 	 = $admIssue->Id;
		$issue->Name                 = $admIssue->Name;
		$issue->Description          = $admIssue->Description;
		$issue->SortOrder            = $admIssue->SortOrder;
		$issue->EmailNotify          = $admIssue->EmailNotify;
		$issue->ReversedRead         = $admIssue->ReversedRead;
		$issue->OverrulePublication  = $admIssue->OverrulePublication;
		$issue->Deadline             = $admIssue->Deadline;
		$issue->PublicationDate      = $admIssue->PublicationDate;
		$issue->ExpectedPages        = $admIssue->ExpectedPages;
		$issue->Subject              = $admIssue->Subject;
		$issue->Activated            = $admIssue->Activated;
		$issue->ExtraMetaData        = $admIssue->ExtraMetaData;

		$this->issues[] = $issue;
	}

	/**
	 * Deletes the issue that was used for testing.
	 */
	private function cleanupAdmIssues()
	{
		if( $this->issues ) foreach( $this->issues as $issue ) {
			try {
				$stepInfo = 'Deleting issue for Send To Next.';
				$this->wflServicesUtils->deleteIssue( $stepInfo, $this->publication->Id, $issue->Id );
			}
			catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
		}
		$this->issues = array();
	}

	/**
	 * Creates four editions for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmEditions()
	{
		for( $i = 1; $i <= 4; $i++ ) {
			$stepInfo = 'Creating edition for Send To Next.';
			$this->editions[] = $this->wflServicesUtils->createEdition( $stepInfo, 
									$this->publication->Id, $this->pubChannel->Id );
		}
	}

	/**
	 * Deletes the editions that were used for testing.
	 */
	private function cleanupAdmEditions()
	{
		if( $this->editions ) foreach( $this->editions as $edition ) {
			try {
				$stepInfo = 'Deleting edition for Send To Next.';
				$this->wflServicesUtils->deleteEdition( $stepInfo, 
										$this->publication->Id, $edition->Id );
			}
			catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
		}
		$this->editions = array();
	}

	/**
	 * Creates publication channel for testing.
	 *
	 * @throws BizException on failure
	 */
	private function setupAdmPubChannel()
	{
		// Example how to overrule request composition:
		//   $this->wflServicesUtils->setRequestComposer( 
		//	    function( $req ) { $req->PubChannels[0]->Name = 'Foo'; } 
		//   );
		$stepInfo = 'Creating channel for Send To Next.';
		$admPubChannel = $this->wflServicesUtils->createPubChannel( $stepInfo, 
									$this->publication->Id );
		$this->pubChannel = $admPubChannel;
	}

	/**
	 * Deletes the publication channel that was used for testing.
	 */
	private function cleanupAdmPubChannel()
	{
		if( $this->pubChannel ) {
			try {
				$stepInfo = 'Deleting channel for Send To Next.';
				$this->wflServicesUtils->deletePubChannel( $stepInfo, 
										$this->publication->Id, $this->pubChannel->Id );
			}
			catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
		}
		$this->pubChannel = array();
	}
}
