<?php
/**
 * @since v9.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Creates and updates folio files to test the Adobe DPS integration.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps2_Folio_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;

	/** @var string $ticket */
	private $ticket = null;

	/** @var string $user */
	private $user = null;

	/** @var Publication $pubObj */
	private $pubObj = null;

	/** @var CategoryInfo $categoryObj */
	private $categoryObj = null;

	/** @var AdmIssue $issueObj */
	private $issueObj = null;

	/** @var AdmEdition[] $editionObjs */
	private $editionObjs = null;

	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannelObj = null;

	/** @var Object $layoutObj */
	private $layoutObj = null;

	/** @var string $docIdLayObj */
	private $docIdLayObj = null;

	/** @var Object $layoutObj */
	private $layoutObj2 = null;

	/** @var string $docIdLayObj2 */
	private $docIdLayObj2 = null;

	/** @var State $layoutStatus */
	private $layoutStatus = null;

	/** @var State $readyToPublishLayoutStatus */
	private $readyToPublishLayoutStatus = null;
	
	/** @var BizServerJob $bizServerJob */
	private $bizServerJob = null;

	/** @var AdobeDps2_Utils  $dpsUtils */
	private $dpsUtils = null;

	public function getDisplayName() { return 'Adobe DPS folios'; }
	public function getTestGoals()   { return 'Creates and updates articles (folio files) to test the Adobe DPS integration.'; }
	public function getTestMethods() { return
		'Does the following steps:
		 <ol>
		 	<li>Create a new layout with an article folio file, an article image file and a social image file.</li>
		 	<li>Save the Layout object with -non- \'Ready to be Published\' status.</li>
		 	<li>Save the Layout object with \'Ready to be Published\' status.</li>
		 	<li>Simulate a Adobe DPS article deletion and test if Enterprise recovers from that.</li>
		 	<li>Update the layout properties by calling SetObjectProperties service call.</li>
		 	<li>Create another layout object for succeeding tests.</li>
		 	<li>Call SendToNext for the two layouts that have \'Layout\' Status.</li>
		 	<li>Call MultisetProperties from \'Layout\' status to \'Ready to be Published\'.</li>
		 	<li>Call MultisetProperties from \'Ready to be Published\' status to \'Layout\' status.</li>
		 </ol> '; }
    public function getPrio()        { return 100; }
	
	final public function runTest()
	{
		try {
			$this->setupTestData();

			// Clear the queue from pending AdobeDps2 jobs.
			$this->deletePendingJobs();

			// Layout create- and save operations.
			$this->createLayoutObject();
			$this->saveLayoutWithLayoutStatus();
			$this->saveLayoutWithReadyPublishLayoutStatus();
			$this->deleteAdobeDpsArticleAndSaveLayoutWithReadyPublishLayoutStatus( true ); // unlock layout

			// Layout property tests.
			$this->setLayoutProperties();
			$this->createObjectForMultiSetTests();
			$this->sendToNextStatus();
			$this->multisetLayoutProperties();

		} catch( BizException $e ) {
		}

		$this->tearDownTestData();
	}
	
	/**
	 * Grabs all the test data that was setup by the Setup_TestCase in the testsuite.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		
		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$this->bizServerJob = new BizServerJob();

		require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/AdobeDps2/Utils.class.php';
		$this->dpsUtils = new AdobeDps2_Utils( $this );

		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();
		
		$this->ticket = @$vars['BuildTest_AdobeDps2']['ticket'];
		$this->assertNotNull( $this->ticket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		$testOptions = (defined('TESTSUITE')) ? unserialize( TESTSUITE ) : array();
		$this->user = $testOptions['User'];
		$this->assertNotNull( $this->user );

		$this->pubObj = @$vars['BuildTest_AdobeDps2']['brand'];
		$this->assertInstanceOf( 'PublicationInfo', $this->pubObj );

		$pubChannel = @$vars['BuildTest_AdobeDps2']['apChannel'];
		$this->assertInstanceOf( 'AdmPubChannel', $pubChannel );
		$this->pubChannelObj = new PubChannel( $pubChannel->Id, $pubChannel->Name ); // convert adm to wfl

		$this->issueObj = @$vars['BuildTest_AdobeDps2']['apIssue'];
		$this->assertInstanceOf( 'AdmIssue', $this->issueObj );
		
		$this->editionObjs = @$vars['BuildTest_AdobeDps2']['editions'];
		$this->assertCount( 2, $this->editionObjs );
		$this->assertInstanceOf( 'AdmEdition', $this->editionObjs[0] );
		$this->assertInstanceOf( 'AdmEdition', $this->editionObjs[1] );
		$this->editionObjs = array( $this->editionObjs[0] ); // for now just one edition is good enough

		$this->layoutStatus = @$vars['BuildTest_AdobeDps2']['layoutStatus'];
		$this->assertInstanceOf( 'State', $this->layoutStatus );

		$this->readyToPublishLayoutStatus = @$vars['BuildTest_AdobeDps2']['readyToPublishLayoutStatus'];
		$this->assertInstanceOf( 'State', $this->readyToPublishLayoutStatus );

		$this->categoryObj = @$vars['BuildTest_AdobeDps2']['category'];
		$this->assertInstanceOf( 'CategoryInfo', $this->categoryObj );
	}
	
	/**
	 * Permanently deletes the layout that was created in this testcase.
	 * Deletes any AdobeDps2 server jobs from the queue created in context of this test. 
	 */
	private function tearDownTestData()
	{
		// When layout was created only (but save failed), unlock the layout first.
		$layoutId = $this->layoutObj ? $this->layoutObj->MetaData->BasicMetaData->ID : null;
		$this->deleteObject( $layoutId );

		$layoutId = $this->layoutObj2 ? $this->layoutObj2->MetaData->BasicMetaData->ID : null;
		$this->deleteObject( $layoutId );

		// Delete server jobs created by this test.
		try {
			$this->deletePendingJobs();
		} catch( BizException $e ) {
		}
	}

	/**
	 * Deletes object created by this test.
	 *
	 * @param int $layoutId
	 */
	private function deleteObject( $layoutId )
	{
		try {
			if( $layoutId ) {
				require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
				$service = new WflUnlockObjectsService();
				$request = new WflUnlockObjectsRequest( $this->ticket, array( $layoutId ) );
				$request->Ticket = $this->ticket;
				$request->IDs    = array( $layoutId );
				$service->execute( $request );
			}
		} catch( BizException $e ) {
		}

		// Delete the layout.
		try {
			if( $layoutId ) {
				require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
				$request = new WflDeleteObjectsRequest();
				$request->Ticket    = $this->ticket;
				$request->IDs       = array( $layoutId );
				$request->Permanent = true;

				$stepInfo = 'Delete the layout and article objects.';
				$response = $this->utils->callService( $this, $request, $stepInfo );

				if( $response && $response->Reports ) { // Introduced in v8.0
					$errMsg = '';
					foreach( $response->Reports as $report ){
						foreach( $report->Entries as $reportEntry ) {
							$errMsg .= $reportEntry->Message . PHP_EOL;
						}
					}
					if( $errMsg ) {
						$this->throwError( 'DeleteObjects: failed: "'.$errMsg.'"' );
					}
				}
			}
		} catch( BizException $e ) {
		}
	}
	
	/**
	 * Deletes any AdobeDps2 server jobs from the queue to avoid disturbing the tests. 
	 * Those jobs could be still pending from preceding test runs that ended unexpectedly.
	 *
	 * @throws BizException on failure
	 */
	private function deletePendingJobs()
	{
		// Deletes all jobs from the queue.
		$this->dpsUtils->emptyServerJobsQueue();

		// Check if the jobs are really deleted from the queue.
		$jobs = $this->bizServerJob->listJobs();
		$this->assertCount( 0, $jobs );
	}

	/**
	 * Creates a Layout object
	 *
	 * @throws BizException on failure
	 */
	private function createLayoutObject()
	{
		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		// Compose the layout object.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$layoutName = 'LayTest1 '.date("m d H i s");
		$this->docIdLayObj = 'ee853833-78db-11db-9ff9-fad6fcecde77';
		$layoutObj 	= $this->buildLayoutObject( null, $layoutName, $this->docIdLayObj );
		
		// Compose the service request.
		$request = new WflCreateObjectsRequest();
		$request->Ticket 	= $this->ticket;
		$request->Lock 		= true;
		$request->Objects 	= array( $layoutObj );
		
		// Create the layout in DB.
		$stepInfo = 'Creating the layout object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		
		// Validate the response and grab the layout object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj = $response->Objects[0];
		
		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );
		
		$target = @$this->layoutObj->Targets[0];
		$this->assertInstanceOf( 'Target', $target );

		// Error when job count was not incremented.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 1, $dpsJobs );
		
		// Process our job synchronously and validate the job status.
		$this->dpsUtils->runServerJobs( 5, 1 );
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$dpsJob = reset($dpsJobs);
		$this->assertEquals( ServerJobStatus::COMPLETED, $dpsJob->JobStatus->getStatus() );
		
		// Resolve the properties of the acting user.
		$userRow = DBUser::getUser( $this->user );
		$this->assertEquals( $this->user, $userRow['user'] );
		
		// Get entire publish history of the layout.
		require_once BASEDIR . '/server/dbclasses/DBPublishHistory.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';
		$layoutHistoryRows = DBPublishHistory::getPublishHistoryDossier( $id, 
			$target->PubChannel->Id, $target->Issue->Id, $target->Editions[0]->Id, false );
		$this->assertCount( 1, $layoutHistoryRows );
		$this->assertGreaterThan( 0, $layoutHistoryRows[0]['id'] );
		$this->assertEquals( $userRow['fullname'], $layoutHistoryRows[0]['user'] );
		$this->assertEquals( 'uploadArticle', $layoutHistoryRows[0]['action'] );

		foreach( $layoutHistoryRows as $layoutHistoryRow ) {
			$objectHistoryRows = DBPublishedObjectsHist::getPublishedObjectsHist( $layoutHistoryRow['id'] );
			$this->assertCount( 1, $objectHistoryRows );
			$this->assertEquals( $id, $objectHistoryRows[0]['objectid'] );
			$this->assertEquals( 0, $objectHistoryRows[0]['majorversion'] );
			$this->assertEquals( 1, $objectHistoryRows[0]['minorversion'] );
			$this->assertEquals( $this->layoutObj->MetaData->BasicMetaData->Name, $objectHistoryRows[0]['name'] );
			$this->assertEquals( 'Layout', $objectHistoryRows[0]['type'] );
			$this->assertEquals( 'application/indesign', $objectHistoryRows[0]['format'] );
		}
		
		// Clear the job queue to avoid any bad aside effects on successor tests.
		$this->deletePendingJobs();
	}

	/**
	 * Saves a Layout object with Ready to be published layout status.
	 *
	 * @param boolean $unlock Whether or not to unlock the layout.
	 * @throws BizException on failure
	 */
	private function saveLayoutWithReadyPublishLayoutStatus( $unlock = false )
	{
		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );
		
		// Compose the layout object.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$layoutId 	= $this->layoutObj->MetaData->BasicMetaData->ID;
		$layoutName = $this->layoutObj->MetaData->BasicMetaData->Name;
		$layoutObj 	= $this->buildLayoutObject( $layoutId, $layoutName, $this->docIdLayObj );
		$layoutObj->Relations = $this->layoutObj->Relations;

		// Compose the service request.
		$request = new WflSaveObjectsRequest();
		$request->Ticket 		= $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn  = true;
		$request->Unlock 		= $unlock;
		$request->Objects 		= array( $layoutObj );
		
		// Save the layout.
		$stepInfo = 'Saving the layout object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		
		// Validate the service response and grab the layout object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );
		
		// Error when job count was not incremented.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 1, $dpsJobs );

		$this->dpsUtils->runServerJobs( 5, 1 );
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$dpsJob = reset($dpsJobs);
		$this->assertEquals( ServerJobStatus::COMPLETED, $dpsJob->JobStatus->getStatus() );

		// Clear the job queue to avoid any bad aside effects on successor tests.
		$this->deletePendingJobs();
	}
	
	/**
	 * Simulates a Adobe DPS article deletion by badly changing the ExternalId in publish history
	 * in Enterprise DB. The broken reference will be noted by Adobe DPS and a HTTP 404 will be returned.
	 * However, Enterprise should be able to recover from this situation by creating a new
	 * Adobe DPS article on-the-fly and update/repair the ExternalId. That is what is tested here.
	 *
	 * @param boolean $unlock Whether or not to unlock the layout.
	 * @throws BizException on failure
	 */
	private function deleteAdobeDpsArticleAndSaveLayoutWithReadyPublishLayoutStatus( $unlock = false )
	{
		// Break the ExternalId (by generating a new GUID and updating the DB).
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$target = $this->layoutObj->Targets[0];
		$layoutId = $this->layoutObj->MetaData->BasicMetaData->ID;
		foreach( $this->editionObjs as $edition ) {
			
			// Get the ExternalId from DB for the published layout.
			require_once BASEDIR . '/server/dbclasses/DBPublishedObjectsHist.class.php';
			$oldExternalId = DBPublishedObjectsHist::getObjectExternalId( $layoutId, $layoutId, 
						$target->PubChannel->Id, $target->Issue->Id, $edition->Id );
			LogHandler::Log( 'AdobeDps2', 'INFO', "Resolved ExternalId '$oldExternalId' for layout id '$layoutId' and edition id '{$edition->Id}'." );
			$this->assertFalse( empty($oldExternalId) ); // must be set
			
			// Genereate new ExternalId and update publish history to break the link
			// between the layout and the Adobe DPS article.
			$newExternalId = NumberUtils::createGUID();
			$where = '`externalid` = ?';
			$params = array( $oldExternalId );
			$values = array( 'externalid' => $newExternalId );
			DBBase::updateRow( 'publishhistory', $values, $where, $params );
			DBBase::updateRow( 'publishedobjectshist', $values, $where, $params );
		}
				
		// Save the layout again to test auto repair of ExternalId.
		$this->saveLayoutWithReadyPublishLayoutStatus( $unlock );
	}

	/**
	 * Saves a Layout object with -non- Ready to be published layout status.
	 *
	 * @param boolean $unlock Whether or not to unlock the layout.
	 * @throws BizException on failure
	 */
	private function saveLayoutWithLayoutStatus( $unlock = false )
	{
		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		// Compose the layout object.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$layoutId 	= $this->layoutObj->MetaData->BasicMetaData->ID;
		$layoutName = $this->layoutObj->MetaData->BasicMetaData->Name;
		$layoutObj 	= $this->buildLayoutObject( $layoutId, $layoutName, $this->docIdLayObj );

		//  -non- Ready to be published layout status.
		$layoutStatus = new State();
		$layoutStatus->Id   = $this->layoutStatus->Id;
		$layoutStatus->Name = $this->layoutStatus->Name;
		$layoutObj->MetaData->WorkflowMetaData->State = $layoutStatus;

		$layoutObj->Relations = $this->layoutObj->Relations;

		// Compose the service request.
		$request = new WflSaveObjectsRequest();
		$request->Ticket 		= $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn  = false;
		$request->Unlock 		= $unlock;
		$request->Objects 		= array( $layoutObj );

		// Save the layout.
		$stepInfo = 'Saving the layout object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		// Validate the service response and grab the layout object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Error when job count was incremented ( Since the status is not Ready To be published, no job should be created )
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		// Clear the job queue to avoid any bad aside effects on successor tests.
		$this->deletePendingJobs();
	}

	/**
	 * Retrieves a Layout object
	 *
	 * @param string $layoutId
	 * @throws BizException on failure
	 * @return Object
	 */
	private function getLayoutObject( $layoutId )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		require_once BASEDIR. '/config/plugins/AdobeDps2/utils/Folio.class.php';

		$request = new WflGetObjectsRequest();
		$request->Ticket= $this->ticket;
		$request->IDs	= array( $layoutId );
		$request->Lock	= false;
		$request->Rendition = AdobeDps2_Utils_Folio::RENDITION;
		
		$stepInfo = 'Getting the layout object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		return $response->Objects[0];
	}

	/**
	 * Update layout properties by calling SetObjectProperties service call.
	 */
	private function setLayoutProperties()
	{
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
		require_once BASEDIR. '/config/plugins/AdobeDps2/utils/Folio.class.php';

		$layoutId = $this->layoutObj->MetaData->BasicMetaData->ID;
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $layoutId;

		/******** -Non- Ready to be published status STARTS *******/
		// New status: Set to -non- Ready to be published status.
		$layoutStatus = new State();
		$layoutStatus->Id = $this->layoutStatus->Id;
		$layoutStatus->Name = $this->layoutStatus->Name;
		$this->layoutObj->MetaData->WorkflowMetaData->State = $layoutStatus;

		$request->MetaData = $this->layoutObj->MetaData;
		$request->Targets = $this->layoutObj->Targets;

		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		$stepInfo = 'Setting layout to "Layout" status for layout object.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		// Error when job count was incremented ( no job should be created)
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		$this->deletePendingJobs(); // Just to be sure.

		/******** -Non- Ready to be published status ENDS *******/

		/******** Ready to be published status STARTS ***********/
		// New status: Set to Ready to be published status.
		$layoutStatus = new State();
		$layoutStatus->Id = $this->readyToPublishLayoutStatus->Id;
		$layoutStatus->Name = $this->readyToPublishLayoutStatus->Name;
		$this->layoutObj->MetaData->WorkflowMetaData->State = $layoutStatus;

		$request->MetaData = $this->layoutObj->MetaData;
		$request->Targets = $this->layoutObj->Targets;

		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		$stepInfo = 'Setting layout to "Ready to be published" status for layout object.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		// Error when job count was not incremented.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 1, $dpsJobs );

		$this->dpsUtils->runServerJobs( 5, 1 );
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$dpsJob = reset($dpsJobs);
		$this->assertEquals( ServerJobStatus::COMPLETED, $dpsJob->JobStatus->getStatus() );

		// Clear the job queue to avoid any bad aside effects on successor tests.
		$this->deletePendingJobs();
		/******** Ready to be published status ENDS ***********/
	}

	/**
	 * Creates new layout object for multisetproperties tests.
	 */
	private function createObjectForMultiSetTests()
	{
		// Compose the layout object.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$layoutName = 'LayTest2 '.date("m d H i s");
		$this->docIdLayObj2 = '2B6FC7323C22681188C698CAFE7CB124';
		$layoutObj 	= $this->buildLayoutObject( null, $layoutName, $this->docIdLayObj2 );

		// Compose the service request.
		$request = new WflCreateObjectsRequest();
		$request->Ticket 	= $this->ticket;
		$request->Lock 		= false;
		$request->Objects 	= array( $layoutObj );

		// Create the layout in DB.
		$stepInfo = 'Creating the layout object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		// Validate the response and grab the layout object.
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj2 = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		// Clear the job queue to avoid any bad aside effects on successor tests.
		$this->deletePendingJobs();
	}

	/**
	 * Setting two layouts to 'Layout' ( non-ready to be published ) status.
	 */
	private function setLayoutToToNonReadyPublishStatus()
	{
		$layoutStatus = new State();
		$layoutStatus->Id = $this->layoutStatus->Id;
		$layoutStatus->Name = $this->layoutStatus->Name;

		// LayoutObject 1
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$this->layoutObj->MetaData->WorkflowMetaData->State = $layoutStatus;
		$request->ID = $this->layoutObj->MetaData->BasicMetaData->ID;
		$request->MetaData = $this->layoutObj->MetaData;
		$request->Targets = $this->layoutObj->Targets;

		$stepInfo = 'Setting layout1 to "Layout" status for layout object.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		// LayoutObject 2
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$this->layoutObj2->MetaData->WorkflowMetaData->State = $layoutStatus;
		$request->ID = $this->layoutObj2->MetaData->BasicMetaData->ID;
		$request->MetaData = $this->layoutObj2->MetaData;
		$request->Targets = $this->layoutObj2->Targets;

		$stepInfo = 'Setting layout2 to "Layout" status for layout object.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		$this->deletePendingJobs(); // No jobs should be created, but just to be sure.

		// Update in memory
		$this->layoutObj = $this->getLayoutObject( $this->layoutObj->MetaData->BasicMetaData->ID );
		$this->layoutObj2 = $this->getLayoutObject( $this->layoutObj2->MetaData->BasicMetaData->ID );
	}

	/**
	 * Calling SendToNext for two layouts that have 'Layout' Status.
	 *
	 * The SendToNext should send the two layouts that have 'Layout' status to 'Ready To Publish' status.
	 * Once it is in the 'Ready to Publish' status, the SendToNext call is triggered again, this time,
	 * nothing should happen.
	 */
	private function sendToNextStatus()
	{
		// Set to "Layout" status for both layouts,
		// so that when SendToNext action is triggered, the status will be changed to 'Ready To be Published' status.
		$this->setLayoutToToNonReadyPublishStatus();

		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		// Calling SendToNext (should now set to 'Ready to be published' status).
		require_once BASEDIR.'/server/services/wfl/WflSendToNextService.class.php';
		$request = new WflSendToNextRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->layoutObj->MetaData->BasicMetaData->ID,
							$this->layoutObj2->MetaData->BasicMetaData->ID ,
						);

		$stepInfo = 'Triggering "Send To Next" action to set two layouts to "Ready to be Published." status.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		$this->layoutObj = $this->getLayoutObject( $this->layoutObj->MetaData->BasicMetaData->ID );
		$this->assertEquals( $this->readyToPublishLayoutStatus->Id, $this->layoutObj->MetaData->WorkflowMetaData->State->Id );

		$this->layoutObj2 = $this->getLayoutObject( $this->layoutObj2->MetaData->BasicMetaData->ID );
		$this->assertEquals( $this->readyToPublishLayoutStatus->Id, $this->layoutObj2->MetaData->WorkflowMetaData->State->Id );

		// Error when job count was not incremented.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 2, $dpsJobs );

		$this->deletePendingJobs();

		// Calling SendToNext again. (nothing should happen).
		$request = new WflSendToNextRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->layoutObj->MetaData->BasicMetaData->ID,
			$this->layoutObj2->MetaData->BasicMetaData->ID ,
		);

		$stepInfo = 'Triggering "Send To Next" action for two layouts that are already in "Ready to be Published." status.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		// Error when job count was incremented.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		$this->deletePendingJobs(); // No job should be created, but just to be sure.
	}

	/**
	 * Call MultiSetProperties service call.
	 *
	 * This covers 'MultisetProperties' and 'SendTo' actions.
	 */
	private function multisetLayoutProperties()
	{
		/******** MultisetProperties from "layout" status to "Ready to be Published" status STARTS *******/
		// Set to "Layout" status for both layouts.
		$this->setLayoutToToNonReadyPublishStatus();

		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		// ReadyToBePublished layout status.
		$propValue = new PropertyValue();
		$propValue->Value = $this->readyToPublishLayoutStatus->Id;
		$propValue->Display = null;
		$propValue->Entity  = null;

		$multiSetMetaData = new MetaDataValue();
		$multiSetMetaData->Property = 'StateId';
		$multiSetMetaData->Values = null;
		$multiSetMetaData->PropertyValues = array( $propValue );

		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array(
			$this->layoutObj->MetaData->BasicMetaData->ID,
			$this->layoutObj2->MetaData->BasicMetaData->ID ,
		);
		$request->MetaData = array( $multiSetMetaData );

		$stepInfo = 'Multisetproperties for two layouts from "Layout" status to "Ready to be Published" status.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		// Error when job count was not incremented.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 2, $dpsJobs );

		$this->deletePendingJobs();

		$this->layoutObj = $this->getLayoutObject( $this->layoutObj->MetaData->BasicMetaData->ID );
		$this->assertEquals( $this->readyToPublishLayoutStatus->Id, $this->layoutObj->MetaData->WorkflowMetaData->State->Id );

		$this->layoutObj2 = $this->getLayoutObject( $this->layoutObj2->MetaData->BasicMetaData->ID );
		$this->assertEquals( $this->readyToPublishLayoutStatus->Id, $this->layoutObj2->MetaData->WorkflowMetaData->State->Id );

		/******** MultisetProperties from "layout" status to "Ready to be Published" status ENDS *******/

		/******** MultisetProperties from "Ready to be Published" status to "Layout" status STARTS *******/
		// Determine the original job count, before service call.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		// Non-readyToBePublished layout status.
		$propValue = new PropertyValue();
		$propValue->Value = $this->layoutStatus->Id;
		$propValue->Display = null;
		$propValue->Entity  = null;

		$multiSetMetaData = new MetaDataValue();
		$multiSetMetaData->Property = 'StateId';
		$multiSetMetaData->Values = null;
		$multiSetMetaData->PropertyValues = array( $propValue );

		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array(
			$this->layoutObj->MetaData->BasicMetaData->ID,
			$this->layoutObj2->MetaData->BasicMetaData->ID ,
		);
		$request->MetaData = array( $multiSetMetaData );

		$stepInfo = 'Multisetproperties for two layouts from "Ready to be Published" status to "Layout" status.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );

		// Error when job count was incremented (No job should be created since it is setting to non-ReadyToBePublished status.
		$dpsJobs = $this->bizServerJob->listJobs( array( 'jobtype' => 'AdobeDps2' ) );
		$this->assertCount( 0, $dpsJobs );

		$this->deletePendingJobs(); // No job should be created, but just to be sure.

		$this->layoutObj = $this->getLayoutObject( $this->layoutObj->MetaData->BasicMetaData->ID );
		$this->assertEquals( $this->layoutStatus->Id, $this->layoutObj->MetaData->WorkflowMetaData->State->Id );

		$this->layoutObj2 = $this->getLayoutObject( $this->layoutObj2->MetaData->BasicMetaData->ID );
		$this->assertEquals( $this->layoutStatus->Id, $this->layoutObj2->MetaData->WorkflowMetaData->State->Id );

		/******** MultisetProperties from "Ready to be Published" status to "Layout" status ENDS *******/
	}

	/**
	 * Composes a Layout object
	 *
	 * @param integer $layoutId
	 * @param string $layoutName
	 * @param string $documentId
	 * @return Object $object Layout object
	 */
	private function buildLayoutObject( $layoutId, $layoutName, $documentId )
	{
		require_once BASEDIR .'/server/bizclasses/BizTransferServer.class.php';
		
		$nativeFilePath   = dirname(__FILE__) . '/testdata/native1.indd';
		$preview1FilePath = dirname(__FILE__) . '/testdata/preview1page1.jpg';
		$preview2FilePath = dirname(__FILE__) . '/testdata/preview1page2.jpg';
		$fileSize = filesize( $nativeFilePath );

		// Layout
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/indesign';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $nativeFilePath, $attachment );
		$files = array( $attachment );
		foreach( $this->editionObjs as $editionObj ) {
			$files[] = $this->createFolioAttachment( $editionObj->Id );
			$files[] = $this->createArticleImageAttachment( $editionObj->Id );
			$files[] = $this->createSocialImageAttachment( $editionObj->Id );
		}
	
		// Page1
		$attachment = new Attachment();
		$attachment->Rendition = 'preview';
		$attachment->Type = 'image/jpeg';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $preview1FilePath, $attachment );

		$page1 = new Page();
		$page1->Width = 400;
		$page1->Height = 300;
		$page1->PageNumber = 'pag1';
		$page1->PageOrder = 1;
		$page1->PageSequence = 1;
		$page1->Files = array( $attachment );
		$page1->Master = 'Master';
		$page1->Instance = 'Production';
		
		// Page2
		$attachment = new Attachment();
		$attachment->Rendition = 'preview';
		$attachment->Type = 'image/jpeg';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $preview2FilePath, $attachment );

		$page2 = new Page();
		$page2->Width = 400;
		$page2->Height = 300;
		$page2->PageNumber = 'pag2';
		$page2->PageOrder = 2;
		$page2->PageSequence = 2;
		$page2->Files = array( $attachment );
		$page2->Master = 'Master';
		$page2->Instance = 'Production';
		
		$pages 	= array( $page1, $page2 );
		$meta = $this->buildLayoutMetaData( $layoutName, $fileSize, $layoutId, $documentId );
		$target = $this->composeTarget();
		$object = new Object();
		$object->MetaData	= $meta;
		$object->Files		= $files;
		$object->Relations  = array();
		$object->Pages		= $pages;
		$object->Targets	= array( $target );

		return $object;
	}
	
	/**
	 * Composes a MetaData object for a Layout object.
	 *
	 * @param string $layoutName
	 * @param integer $fileSize
	 * @param integer|null $layoutId
	 * @param string $documentId
	 * @return MetaData $metaData MetaData object
	 */
	private function buildLayoutMetaData( $layoutName, $fileSize, $layoutId=null, $documentId )
	{
		// build metadata
		$basicMD = new BasicMetaData();
		$basicMD->ID 	= $layoutId;
		$basicMD->Name 	= $layoutName;
		$basicMD->Type 	= 'Layout';
		$basicMD->DocumentID = $documentId;
		$basicMD->Publication 	= new Publication( $this->pubObj->Id, $this->pubObj->Name );
		$basicMD->Category 		= new Category( $this->categoryObj->Id, $this->categoryObj->Name );
		$cntMD = new ContentMetaData();	
		$cntMD->Format 	= 'application/indesign';
		$cntMD->FileSize= $fileSize;
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline= date('Y-m-d\TH:i:s'); 
		$wflMD->State 	= new State( $this->readyToPublishLayoutStatus->Id, $this->readyToPublishLayoutStatus->Name );
		$wflMD->Comment = 'Created by Build Test class: '.__CLASS__;
		$wflMD->RouteTo = $this->user;

		$metaData = new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $cntMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();

		return $metaData;
	}
	
	/**
	 * Composes a Target for a Layout object. 
	 *
	 * The target is based on the created pubchannel/issue/editions during setup.
	 *
	 * @return Target
	 */
	private function composeTarget()
	{
		$target = new Target();
		$target->PubChannel = new PubChannel( $this->pubChannelObj->Id, $this->pubChannelObj->Name ); // convert adm to wfl
		$target->Issue = new Issue( $this->issueObj->Id, $this->issueObj->Name ); // convert adm to wfl
		$target->Editions = array();
		foreach( $this->editionObjs as $edition ) {
			$target->Editions[] = new Edition( $edition->Id, $edition->Name ); // convert adm to wfl
		}
		return $target;
	}

	/**
	 * Creates an Folio file attachment for a given edition.
	 *
	 * The folio file contains dummy data and is copied to the Transfer Server folder.
	 *
	 * @param int $editionId
	 * @return Attachment
	 */
	private function createFolioAttachment( $editionId )
	{
		// Compose attachment object.
		require_once BASEDIR. '/config/plugins/AdobeDps2/utils/Folio.class.php';
		$fileAttachment = new Attachment();
		$fileAttachment->Rendition 	= AdobeDps2_Utils_Folio::RENDITION;
		$fileAttachment->Type      	= AdobeDps2_Utils_Folio::CONTENTTYPE;
		$fileAttachment->EditionId 	= $editionId;
		
		// Copy attachment file from test folder to Transfer Folder.
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$fileName = dirname(__FILE__) . '/testdata/design_v.article';
		$bizTransfer = new BizTransferServer();
		$bizTransfer->copyToFileTransferServer( $fileName, $fileAttachment );

		return $fileAttachment;
	}
	
	/**
	 * Creates an article image file attachment for a given edition.
	 *
	 * The folio file contains dummy data and is copied to the Transfer Server folder.
	 *
	 * @param int $editionId
	 * @return Attachment
	 */
	private function createArticleImageAttachment( $editionId )
	{
		// Compose attachment object.
		$fileAttachment = new Attachment();
		$fileAttachment->Rendition 	= 'output';
		$fileAttachment->Type      	= 'image/png|application/adobedps-article-image';
		$fileAttachment->EditionId 	= $editionId;
		
		// Copy attachment file from test folder to Transfer Folder.
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$fileName = dirname(__FILE__) . '/testdata/article_image.png';
		$bizTransfer = new BizTransferServer();
		$bizTransfer->copyToFileTransferServer( $fileName, $fileAttachment );

		return $fileAttachment;
	}
	
	/**
	 * Creates an social image file attachment for a given edition.
	 *
	 * The folio file contains dummy data and is copied to the Transfer Server folder.
	 *
	 * @param int $editionId
	 * @return Attachment
	 */
	private function createSocialImageAttachment( $editionId )
	{
		// Compose attachment object.
		$fileAttachment = new Attachment();
		$fileAttachment->Rendition 	= 'output';
		$fileAttachment->Type      	= 'image/png|application/adobedps-social-image';
		$fileAttachment->EditionId 	= $editionId;
		
		// Copy attachment file from test folder to Transfer Folder.
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$fileName = dirname(__FILE__) . '/testdata/social_image.png';
		$bizTransfer = new BizTransferServer();
		$bizTransfer->copyToFileTransferServer( $fileName, $fileAttachment );

		return $fileAttachment;
	}
}
