<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Creates and updates folio files to test the Automated Print Workflow features.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_SkipIDSA_ToggleSkipIDSA_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var BizTransferServer $transferServer */
	private $transferServer = null; //

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

	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannelObj = null;

	/** @var int[] $createdObjectIds */
	private $createdObjecIds = array();

	/** @var AdmStatus $templateStatus */
	private $templateStatus = null;

	/** @var AdmStatus $runTimeStatus */
	private $runTimeStatus = null;

	/** @var array runTimeStatusIds */
	private $runTimeStatusIds;

	public function getDisplayName()
	{
		return 'Toggle InDesign Server Automation setting.';
	}

	public function getTestGoals()
	{
		return 'Creates a layout template. Toggles the SkipIDSA of the status and checks if a IDSA job is created.';
	}

	public function getTestMethods()
	{
		return
			'Does the following steps:
		 <ol>
		 	<li>Create a new layout template with a status with the \'Skip InDesign Server Automation\' setting is true.</li>
		 	<li>Create a new layout template with a status with the \'Skip InDesign Server Automation\' setting is false.</li>
		 	<li>Statuses are created on the fly because the statuses are cached and changed settings are not populated. </li>
		 </ol> ';
	}

	public function getPrio()
	{
		return 110;
	}

	final public function runTest()
	{
		try {
			$this->setupTestData();
			$skipIdsaMode = array( true, false );
			foreach( $skipIdsaMode as $skipIdsa ) {
				$this->createRunTimeStatus( $skipIdsa );
				$this->createTemplate();
			}
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
		$this->globalUtils = new WW_Utils_TestSuite();

		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		$this->ticket = @$vars['BuildTest_SkipIDSA']['ticket'];
		$this->assertNotNull( $this->ticket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		$testOptions = ( defined( 'TESTSUITE' ) ) ? unserialize( TESTSUITE ) : array();
		$this->user = $testOptions['User'];
		$this->assertNotNull( $this->user );

		$this->pubObj = @$vars['BuildTest_SkipIDSA']['brand'];
		$this->assertInstanceOf( 'PublicationInfo', $this->pubObj );

		$pubChannel = @$vars['BuildTest_SkipIDSA']['pubChannel'];
		$this->assertInstanceOf( 'AdmPubChannel', $pubChannel );
		$this->pubChannelObj = new PubChannel( $pubChannel->Id, $pubChannel->Name ); // convert adm to wfl

		$this->issueObj = @$vars['BuildTest_SkipIDSA']['issue'];
		$this->assertInstanceOf( 'AdmIssue', $this->issueObj );

		$this->templateStatus = @$vars['BuildTest_SkipIDSA']['layoutTemplateStatus'];
		$this->templateStatus->Produce = null; // exclude from compare
		$this->assertInstanceOf( 'State', $this->templateStatus );

		$this->categoryObj = @$vars['BuildTest_SkipIDSA']['category'];
		$this->assertInstanceOf( 'CategoryInfo', $this->categoryObj );


	}

	/**
	 * Permanently deletes the layout templates that are created in this test case.
	 */
	private function tearDownTestData()
	{
		$this->unlockObjects();
		$this->deleteObjects();
		$this->deleteRunTimeStatus();
	}

	/**
	 * Unlocks objects locked by this test.
	 */
	private function unlockObjects()
	{
		try {
			// When object was created only (but save failed), unlock it first.
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$service = new WflUnlockObjectsService();
			$request = new WflUnlockObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs = $this->createdObjecIds;
			$service->execute( $request );
		} catch( BizException $e ) {
		}
	}

	/**
	 * Deletes object created by this test.
	 */
	private function deleteObjects()
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs = $this->createdObjecIds;
			$request->Permanent = true;

			$stepInfo = 'Delete an object (that was used for this test).';
			$response = $this->globalUtils->callService( $this, $request, $stepInfo );

			if( $response && $response->Reports ) { // Introduced in v8.0
				$errMsg = '';
				foreach( $response->Reports as $report ) {
					foreach( $report->Entries as $reportEntry ) {
						$errMsg .= $reportEntry->Message.PHP_EOL;
					}
				}
				if( $errMsg ) {
					$this->throwError( 'DeleteObjects: failed: "'.$errMsg.'"' );
				}
			}
		} catch( BizException $e ) {
		}
	}

	/**
	 * Creates a Layout template object with a status of which the 'SkipIDSA' option is set to the $skipIdsa parameter.
	 *
	 * In case $skipIdsa is true no job should be created. If $skipIdsa is false there must be an InDesign Server job
	 * created.
	 *
	 */
	private function createTemplate()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = $this->composeCreateTemplateRequest();
		$stepInfo = 'Creating the layout template object.';
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );

		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$id = intval( @$response->Objects[0]->MetaData->BasicMetaData->ID );
		$this->assertGreaterThan( 0, $id );
		$this->createdObjecIds[] = $id;
		require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
		$jobCreated = DBInDesignServerJob::jobExistsForObject( $id );
		$this->assertEquals( !$this->runTimeStatus->SkipIdsa, $jobCreated );
		/* $result = */DBInDesignServerJob::deleteJobsForObject( $id );
	}

	private function composeCreateTemplateRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:512a0eb7-2367-433d-acbe-5410bfffe15c';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'LayoutTemplate '.$this->getTimeStamp();
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'LayoutTemplate';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
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
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1003520;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->ContentMetaData->Dimensions = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2015-10-05T09:32:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->runTimeStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->user;
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 612;
		$request->Objects[0]->Pages[0]->Height = 792;
		$request->Objects[0]->Pages[0]->PageNumber = '2';
		$request->Objects[0]->Pages[0]->PageOrder = 2;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 612;
		$request->Objects[0]->Pages[1]->Height = 792;
		$request->Objects[0]->Pages[1]->PageNumber = '3';
		$request->Objects[0]->Pages[1]->PageOrder = 3;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[1]->Orientation = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname( __FILE__ ).'/testdata/rec#006_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->composeTarget();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		$request->ReplaceGUIDs = null;

		return $request;
	}

	/**
	 * Returns the current date time stamp.
	 *
	 * The format returned is:
	 * YrMthDay HrMinSec MiliSec
	 * For example:
	 * 140707 173315 176
	 *
	 * @return string
	 */
	private function getTimeStamp()
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round( $microTime[0] * 1000 ) );
		return date( 'ymd His', $microTime[1] ).' '.$miliSec;
	}

	/*
	* Composes a Publication for test object to assign to.
	*
	* @return Publication
	*/
	private function composePublication()
	{
		$publication = new Publication();
		$publication->Id = $this->pubObj->Id;
		$publication->Name = $this->pubObj->Name;
		return $publication;
	}

	/*
	 * Composes a Category for test object to assign to.
	 *
	 * @return Category
	 */
	private function composeCategory()
	{
		$category = new Category();
		$category->Id = $this->categoryObj->Id;
		$category->Name = $this->categoryObj->Name;
		return $category;
	}

	/**
	 * Composes a Target for a layout (or layout template) object.
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

		return $target;
	}

	/**
	 * Creates a status. Status is based on the status defined for layout templates of the build test brand.
	 *
	 * @param bool $skipIdsa Whether or not the status should have the 'SkipIdsa' property set.
	 */
	private function createRunTimeStatus( $skipIdsa )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$status = BizAdmStatus::getStatusWithId( $this->templateStatus->Id );
		$status->Name = $this->getTimeStamp();
		$status->SkipIdsa = $skipIdsa;
		$status->Produce = false;
		$this->runTimeStatus = BizAdmStatus::createStatus( $status );
		$this->runTimeStatusIds[] = $this->runTimeStatus->Id;
		BizAdmStatus::restructureMetaDataStatusColor( $this->runTimeStatus->Id, $this->runTimeStatus->Color );
	}

	private function deleteRunTimeStatus()
	{
		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		foreach( $this->runTimeStatusIds as $runTimeStatusId ) {
			BizCascadePub::deleteStatus( $runTimeStatusId );
		}
	}
}
