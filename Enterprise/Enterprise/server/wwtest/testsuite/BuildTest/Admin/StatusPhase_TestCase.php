<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Admin_StatusPhase_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite

	// Variables for setup/teardown
	private $objIds;
	private $objNames;
	private $statusIds;
	private $statuses;

	// BizTransferServer to transfer test documents
	private $transferServer = null;

	/**
	 * Return the name of the test as seen on the build test page.
	 *
	 * @return string
	 */
	public function getDisplayName()
	{
		return 'Test the use of Status Phase';
	}

	/**
	 * Return the goals of the test as seen on the build test page.
	 *
	 * @return string
	 */
	public function getTestGoals()
	{
		return 'Checks if the phase of a status can be set or updated';
	}

	/**
	 * Return the methods of the test as seen on the build test page.
	 *
	 * @return string
	 */
	public function getTestMethods()
	{
		return 'Scenario:<ol>
			<li>001: Define statuses for this test (createTestingStatuses)</li>
			<li>002: Create an object for this test with a default status phase (wflCreateObjectWithDefaultStatusPhase)</li>
			<li>003: Retrieve the stored object (wflGetObjects)</li>
			<li>004: Put the object in a different status phase (setStatusPhaseToCompleted)</li>
			<li>005: Unlock the object after saving (wflUnlockObjects)</li>
			<li>006: Clean up the object (wflDeleteObjects)</li>
			<li>007: Delete the temporary statuses (deleteTestingStatuses)</li>
			</ol>';
	}

	/**
	 * Return the priority of the test.
	 *
	 * @return integer
	 */
	public function getPrio()
	{
		return 2;
	}

	/**
	 * Runs the testcases for this TestSuite.
	 *
	 * @return bool Whether or not the operation was succesful.
	 */
	final public function runTest()
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		$this->vars = $this->getSessionVariables();
		$this->ticket = @$this->vars['BuildTest_Admin']['ticket'];

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		BizSession::checkTicket( $this->ticket );

		// Start testing the Status Phase implementation
		$resultOK = true;
		if ( $this->setupTestData() ) {
			$resultOK = false;
			do {
				if (!$this->wflCreateObjectWithDefaultStatusPhase()) {
					break;
				}
				if (!$this->wflGetObjects()) {
					break;
				}
				if (!$this->setStatusPhaseToCompleted()) {
					break;
				}
				$resultOK = true;
			} while( false );
		}

		// Remove the created statuses
		$this->tearDownTestData();

		BizSession::endSession();

		return $resultOK;
	}

	/**
	 * Change stdClass type status returned from database to State object.
	 *
	 * @param stdClass $status Status information received from the database as stdClass.
	 *
	 * @return State new State object to work with in this test.
	 */
	private function composeStatusFromResult( stdClass $status )
	{
		$tmpStatus = new State();
		$tmpStatus->Id = $status->Id;
		$tmpStatus->Name = $status->Name;
		$tmpStatus->Type = $status->Type;
		$tmpStatus->Produce = null;
		$tmpStatus->Color = null;
		$tmpStatus->DefaultRouteTo = null;
		$tmpStatus->Phase = $status->Phase;

		return $tmpStatus;
	}

	/**
	 * Setting up test data before running the test.
	 *
	 * @return boolean
	 */
	private function setupTestData()
	{
		$resultOK = true;

		if (!$this->setupTestStatuses()) {
			$resultOK = false;
		}

		return $resultOK;
	}

	/**
	 * Define different default statuses for testing purposes.
	 *
	 * These statuses are being created:
	 * - Status Phase Draft.
	 * - Status Phase Progress.
	 * - Status Phase Finished.
	 *
	 * @return boolean
	 */
	private function setupTestStatuses()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';

		$status = new stdClass();
		$status->Id = null;
		$status->PublicationId = $this->vars['BuildTest_Admin']['publication']->Id;
		$status->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$status->Phase = '';
		$status->Name = '';
		$status->Produce = false;
		$status->Color = '#A0A0A0';
		$status->NextStatusId = '';
		$status->SortOrder = '';
		$status->IssueId = 0; // It is not an overrule issue publication, so we leave this 0
		$status->SectionId = '';
		$status->DeadlineStatusId = '';
		$status->DeadlineRelative = '';
		$status->CreatePermanentVersion = false;
		$status->RemoveIntermediateVersions = false;
		$status->AutomaticallySendToNext = false;
		$status->ReadyForPublishing = false;
		$status->SkipIdsa = false;

		$microTime = explode( ' ', microtime() );
		$milliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$milliSec;

		$resultOK = true;
		try {
			// Create a draft status in the selection phase
			$draftStatus = $status;
			$draftStatus->Name = 'Status Phase Draft '.$postfix;
			$draftStatus->Phase = 'Selection';
			$status = BizAdmStatus::createStatus( $draftStatus );
			$this->statuses['Status Phase Draft'] = $this->composeStatusFromResult($status);
			$this->statusIds['Status Phase Draft'] = $status->Id;

			// Create a progress status in the production phase
			$progressStatus = $status;
			$progressStatus->Name = 'Status Phase Progress '.$postfix;
			$progressStatus->Phase = 'Production';
			$status = BizAdmStatus::createStatus( $progressStatus );
			$this->statuses['Status Phase Progress'] = $this->composeStatusFromResult($status);
			$this->statusIds['Status Phase Progress'] = $status->Id;

			// Create a finished status in the completed phase
			$finishedStatus = $status;
			$finishedStatus->Name = 'Status Phase Finished '.$postfix;
			$finishedStatus->Phase = 'Completed';
			$status = BizAdmStatus::createStatus( $finishedStatus );
			$this->statuses['Status Phase Finished'] = $this->composeStatusFromResult($status);
			$this->statusIds['Status Phase Finished'] = $status->Id;
		}
		catch (BizException $e) {
			$this->setResult( 'ERROR',  'Failed to create Statuses. '.$e->getMessage() );
			$resultOK = false;
		}

		return $resultOK;
	}

	/**
	 * Tears down the objects created.
	 *
	 * @return void
	 */
	private function tearDownTestData()
	{
		$this->wflUnlockObjects();
		$this->wflDeleteObjects();
		$this->deleteTestingStatuses();
	}

	/**
	 * Clean up the statuses after we are done testing them.
	 *
	 * @return boolean
	 */
	private function deleteTestingStatuses()
	{
		$resultOK = true;
		if (count($this->statusIds) > 0) {
			require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
			try {
				$resultOK = true;
				foreach ($this->statusIds as $statusId) {
					BizCascadePub::deleteStatus($statusId);
				}
			} catch (BizException $e) {
				$this->setResult( 'ERROR', $e->getMessage(), 'Error occurred in DeleteTestingStatuses response.');
				$resultOK = false;
			}
		}
		return $resultOK;
	}

	/**
	 * Access the WflCreateObjects service and compare the response.
	 *
	 * @return boolean
	 */
	private function wflCreateObjectWithDefaultStatusPhase()
	{
		$resultOK = false;
		$microTime = explode( ' ', microtime() );
		$milliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$milliSec;
		$this->objectName = 'StatusPhase_TestCase_Dossier_Article_1 '.$postfix;

		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->wflCreateObjectsRequest();
		$recResp = $this->wflCreateObjectsResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflCreateObjects');

		if ( $curResp ) {
			if ( isset($curResp->Objects) && count($curResp->Objects) > 0) {
				// Keep track of created artifacts so we can tear down the process
				$objectId = (int) $curResp->Objects[0]->MetaData->BasicMetaData->ID;
				$this->objIds['WflCreateObjects'][0] = $objectId;
				$this->objNames[$objectId] = $curResp->Objects[0]->MetaData->BasicMetaData->Name;

				if ( isset($curResp->Objects[0]->Relations) && count($curResp->Objects[0]->Relations) > 0 ) {
					$objectId = (int) $curResp->Objects[0]->Relations[0]->ParentInfo->ID;
					$this->objIds['WflCreateObjects'][1] = $objectId;
					$this->objNames[$objectId] = $curResp->Objects[0]->Relations[0]->ParentInfo->Name;
				}
			}

			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			$resultOK = true;
			if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '021' );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '021' );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occurred in WflCreateObjects response.');
				$resultOK = false;
			}
		}

		return $resultOK;
	}

	/**
	 * Compose WflCreateObjectsRequest object.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function wflCreateObjectsRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->objectName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->vars['BuildTest_Admin']['publication']->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->vars['BuildTest_Admin']['publication']->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->vars['BuildTest_Admin']['category']->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->vars['BuildTest_Admin']['category']->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 160967;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = $this->vars['BuildTest_Admin']['Channels'][0]->Name;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->ContentMetaData->Dimensions = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Status Phase Progress']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Status Phase Progress']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = $this->statuses['Status Phase Progress']->Type;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'A0A0A0';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Phase = $this->statuses['Status Phase Progress']->Phase;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values[0] = '-1';
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = '-1';
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->Targets[0] = $this->vars['BuildTest_Admin']['printTarget'];
		$request->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/StatusPhase_TestData/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'ae52eb7d-e6bc-3228-f726-1dca5d90d087';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 0;
		$request->Objects[0]->Elements[0]->LengthChars = 0;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 0;
		$request->Objects[0]->Elements[0]->Snippet = '';
		$request->Objects[0]->Elements[0]->Version = 'cde39910-55b1-0db0-18fa-bb09d1457a03';
		$request->Objects[0]->Elements[0]->Content = '';
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = '98b08032-8fa8-71a6-1ac2-f9e0882dc135';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 0;
		$request->Objects[0]->Elements[1]->LengthChars = 0;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 0;
		$request->Objects[0]->Elements[1]->Snippet = '';
		$request->Objects[0]->Elements[1]->Version = '6ac3c0f1-c95b-2ceb-d4dc-2eab24f16757';
		$request->Objects[0]->Elements[1]->Content = '';
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = '8b2be265-a0c8-4d49-d6a7-b2c3fd60d41f';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 0;
		$request->Objects[0]->Elements[2]->LengthChars = 0;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 0;
		$request->Objects[0]->Elements[2]->Snippet = '';
		$request->Objects[0]->Elements[2]->Version = 'cf2d4be4-7cbf-3132-63ca-519e394309a0';
		$request->Objects[0]->Elements[2]->Content = '';
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->vars['BuildTest_Admin']['printTarget'];
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		return $request;
	}

	/**
	 * Compose WflCreateObjectsResponse object to compare the test response.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function wflCreateObjectsResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = null;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:c639ec8c-43fb-4812-96ae-52f1874ee8dc';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->objectName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->vars['BuildTest_Admin']['publication']->Id;
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->vars['BuildTest_Admin']['publication']->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->vars['BuildTest_Admin']['category']->Id;
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->vars['BuildTest_Admin']['category']->Name;
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = $this->vars['BuildTest_Admin']['Channels'][0]->Name;
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$response->Objects[0]->MetaData->ContentMetaData->Dimensions = null;
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Status Phase Progress']->Id;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Status Phase Progress']->Name;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = $this->statuses['Status Phase Progress']->Type;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'A0A0A0';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Phase = $this->statuses['Status Phase Progress']->Phase;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = null;
		$response->Objects[0]->Relations[0]->Child = null;
		$response->Objects[0]->Relations[0]->Type = 'Contained';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = $this->vars['BuildTest_Admin']['printTarget'];
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = 180108503;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->objectName;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = $this->vars['BuildTest_Admin']['dossierStatus']->Type;
		$response->Objects[0]->Relations[0]->ParentInfo->Format = '';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = 180108502;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = $this->objectName;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = 'ae52eb7d-e6bc-3228-f726-1dca5d90d087';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '0';
		$response->Objects[0]->Elements[0]->LengthChars = '0';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '0';
		$response->Objects[0]->Elements[0]->Snippet = '';
		$response->Objects[0]->Elements[0]->Version = 'cde39910-55b1-0db0-18fa-bb09d1457a03';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = '98b08032-8fa8-71a6-1ac2-f9e0882dc135';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '0';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = '6ac3c0f1-c95b-2ceb-d4dc-2eab24f16757';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = '8b2be265-a0c8-4d49-d6a7-b2c3fd60d41f';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '0';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = 'cf2d4be4-7cbf-3132-63ca-519e394309a0';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = array();
		$response->Objects[0]->_Closed = false;
		$response->Reports = array();
		return $response;
	}


	/**
	 * Access the WflGetObjects service and compare the response.
	 *
	 * @return boolean
	 */
	private function wflGetObjects()
	{
		$resultOK = false;

		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = $this->wflGetObjectsRequest();
		$recResp = $this->wflGetObjectsResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflGetObjects');

		if ( $curResp ) {

			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			$resultOK = true;
			if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '060' );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '060' );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occurred in WflGetObjects response.');
				$resultOK = false;
			}
		}

		return $resultOK;
	}

	/**
	 * Compose WflGetObjectsRequest object.
	 *
	 * @return WflGetObjectsRequest
	 */
	private function wflGetObjectsRequest()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objIds['WflCreateObjects'][0];
		$request->Lock = true;
		$request->Rendition = 'none';
		$request->RequestInfo = null;
		$request->HaveVersions = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->EditionId = null;
		return $request;
	}

	/**
	 * Compose WflGetObjectsResponse object to compare the test response.
	 *
	 * @return WflGetObjectsResponse
	 */
	private function wflGetObjectsResponse()
	{
		$response = new WflGetObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = null;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:82c0a9ae-e7e1-443d-8c45-c4371def07a3';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->objectName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->vars['BuildTest_Admin']['publication']->Id;
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->vars['BuildTest_Admin']['publication']->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->vars['BuildTest_Admin']['category']->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->vars['BuildTest_Admin']['category']->Name;
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = $this->vars['BuildTest_Admin']['Channels'][0]->Name;
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$response->Objects[0]->MetaData->ContentMetaData->Dimensions = null;
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Status Phase Progress']->Id;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Status Phase Progress']->Name;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = $this->statuses['Status Phase Progress']->Type;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'A0A0A0';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Phase = $this->statuses['Status Phase Progress']->Phase;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = null;
		$response->Objects[0]->Relations[0]->Child = null;
		$response->Objects[0]->Relations[0]->Type = 'Contained';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = $this->vars['BuildTest_Admin']['printTarget'];
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = 180105501;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->objectName;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = $this->vars['BuildTest_Admin']['dossierStatus']->Type;
		$response->Objects[0]->Relations[0]->ParentInfo->Format = '';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = 180105500;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = $this->objectName;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = null;
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = 'b5e5b56a-bad0-7328-f838-24c1fffe0cd8';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '0';
		$response->Objects[0]->Elements[0]->LengthChars = '0';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '0';
		$response->Objects[0]->Elements[0]->Snippet = '';
		$response->Objects[0]->Elements[0]->Version = '32bbf96d-db56-cc15-de4d-a2c45adb3590';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = '898ad947-9cd5-a48a-6c74-8a5db0ba11cf';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '0';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = 'cf0b7b5e-94c3-3247-58d1-8d08ff937403';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = 'b018d8c6-147b-655a-113b-8e3498801ca4';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '0';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = '0875ea7a-0164-9f72-997d-2bdf3bca089a';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		return $response;
	}


	/**
	 * Access the WflDeleteObjects service and compare the response.
	 *
	 * @return boolean
	 */
	private function wflDeleteObjects()
	{
		$resultOK = true;
		if (count($this->objIds) > 0) {
			require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$req = $this->wflDeleteObjectsRequest();
			$curResp = $this->utils->callService( $this, $req, 'WflDeleteObjects');
			if ( $curResp ) {
				$resultOK = true;
			}
			else {
				$this->setResult( 'ERROR',  'Could not delete the object.' );
				$resultOK = false;
			}
		}
		return $resultOK;
	}

	/**
	 * Compose WflDeleteObjectsRequest.
	 *
	 * @return WflDeleteObjectsRequest
	 */
	private function wflDeleteObjectsRequest()
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $this->objIds['WflCreateObjects'];
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->Context = null;
		return $request;
	}

	/**
	 * Access the WflSaveObjects service and compare the response.
	 *
	 * @return boolean
	 */
	private function setStatusPhaseToCompleted()
	{
		$resultOK = false;

		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->wflSaveObjectsRequest();
		$recResp = $this->wflSaveObjectsResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflSaveObjects');

		if ( $curResp ) {
			if ( isset($curResp->Objects) && count($curResp->Objects) > 0) foreach ($curResp->Objects as $object) {
				// Keep track of created artifacts so we can tear down the process
				$objectId = (int) $object->MetaData->BasicMetaData->ID;
				$this->objIds['WflSaveObjects'][] = $objectId;
				$this->objNames[$objectId] = $object->MetaData->BasicMetaData->Name;
			}

			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			$resultOK = true;
			if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '006' );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '006' );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occurred in WflSaveObjects response.');
				$resultOK = false;
			}
		}

		return $resultOK;
	}

	/**
	 * Compose WflSaveObjectsRequest object.
	 *
	 * @return WflSaveObjectsRequest
	 */
	private function wflSaveObjectsRequest()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->objIds['WflCreateObjects'][0];
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->objectName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->vars['BuildTest_Admin']['publication']->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->vars['BuildTest_Admin']['publication']->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->vars['BuildTest_Admin']['category']->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->vars['BuildTest_Admin']['category']->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = null;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Hello';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 3;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 523.275591;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 769.889764;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 1;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 5;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 3;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Hello';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 170241;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = $this->vars['BuildTest_Admin']['Channels'][0]->Name;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->ContentMetaData->Dimensions = '523.275591 x 769.889764';
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Status Phase Finished']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Status Phase Finished']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = $this->statuses['Status Phase Finished']->Type;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'A0A0A0';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Phase = $this->statuses['Status Phase Finished']->Phase;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/StatusPhase_TestData/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'FA912053-451C-489E-A879-8595BDA523CD';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 1;
		$request->Objects[0]->Elements[0]->LengthChars = 5;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = 'Hello';
		$request->Objects[0]->Elements[0]->Version = '1CCDF155-0CB1-49F4-88BF-32A22527532A';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = '39D82118-64BC-4CE7-B9BB-83750B52BACF';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 0;
		$request->Objects[0]->Elements[1]->LengthChars = 0;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 1;
		$request->Objects[0]->Elements[1]->Snippet = '';
		$request->Objects[0]->Elements[1]->Version = '98BF97D0-20BE-468A-ABC9-BD68CB788354';
		$request->Objects[0]->Elements[1]->Content = null;
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = '7315E765-E299-49E2-8093-410564A6F168';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 0;
		$request->Objects[0]->Elements[2]->LengthChars = 0;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 1;
		$request->Objects[0]->Elements[2]->Snippet = '';
		$request->Objects[0]->Elements[2]->Version = '8FED3EEE-8148-4AE0-84C8-DFE172500364';
		$request->Objects[0]->Elements[2]->Content = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}

	/**
	 * Setup Compose WflSaveObjectsResponse object.
	 *
	 * @return WflSaveObjectsResponse
	 */
	private function wflSaveObjectsResponse()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = null;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:500e1efa-56ae-4950-8a93-ad93faa46bd9';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->objectName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->vars['BuildTest_Admin']['publication']->Id;
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->vars['BuildTest_Admin']['publication']->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->vars['BuildTest_Admin']['category']->Id;
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->vars['BuildTest_Admin']['category']->Name;
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = 'Hello';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '3';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '523.275591';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '769.889764';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '1';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '5';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '3';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Hello';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '170241';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = $this->vars['BuildTest_Admin']['Channels'][0]->Name;
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$response->Objects[0]->MetaData->ContentMetaData->Dimensions = '523.275591 x 769.889764';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Status Phase Finished']->Id;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Status Phase Finished']->Name;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = $this->statuses['Status Phase Finished']->Type;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'A0A0A0';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Phase = $this->statuses['Status Phase Finished']->Phase;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.2';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = '260106500';
		$response->Objects[0]->Relations[0]->Child = '260200400';
		$response->Objects[0]->Relations[0]->Type = 'Contained';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.2';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = $this->vars['BuildTest_Admin']['printTarget'];
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = 260106500;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->objectName;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = $this->vars['BuildTest_Admin']['dossierStatus']->Type;
		$response->Objects[0]->Relations[0]->ParentInfo->Format = '';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = 260200400;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = $this->objectName;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = $this->vars['BuildTest_Admin']['articleStatus']->Type;
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = 'FA912053-451C-489E-A879-8595BDA523CD';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '1';
		$response->Objects[0]->Elements[0]->LengthChars = '5';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '1';
		$response->Objects[0]->Elements[0]->Snippet = 'Hello';
		$response->Objects[0]->Elements[0]->Version = '1CCDF155-0CB1-49F4-88BF-32A22527532A';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = '39D82118-64BC-4CE7-B9BB-83750B52BACF';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '1';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = '98BF97D0-20BE-468A-ABC9-BD68CB788354';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = '7315E765-E299-49E2-8093-410564A6F168';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '1';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = '8FED3EEE-8148-4AE0-84C8-DFE172500364';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = array();
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflUnlockObjects service and compare the response.
	 *
	 * @return boolean
	 */
	private function wflUnlockObjects()
	{
		$resultOK = true;
		if (count($this->objIds) > 0) {
			require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$req = $this->wflUnlockObjectsRequest();
			$curResp = $this->utils->callService( $this, $req, 'WflUnlockObjects');
			if ( $curResp ) {
				$resultOK = true;
			}
			else {
				$this->setResult( 'ERROR',  'Could not unlock the object.' );
				$resultOK = false;
			}
		}
		return $resultOK;
	}

	/**
	 * Compose WflUnlockObjectsRequest object.
	 *
	 * @return WflUnlockObjectsRequest
	 */
	private function wflUnlockObjectsRequest()
	{
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objIds['WflCreateObjects'][0];
		$request->ReadMessageIDs = null;
		$request->MessageList = null;
		return $request;
	}

	/**
	 * Properties that needs to be ignored in WW_Utils_PhpCompare::compareTwoProps() should be declared here.
	 *
	 * @return array
	 */
	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true,
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true, 'Id' => true, 'ID' => true, 'IDs' => true,
			'IDs[0]' => true, 'Parent' => true, 'Child' => true,
			'DocumentID' => true, 'Description' => true,
			'ParentInfo' => true, 'ChildInfo' => true, 'ExtraMetaData' => true,
			'Modifier' => true, 'Creator' => true, 'LockedBy' => true,
		);
	}
}
