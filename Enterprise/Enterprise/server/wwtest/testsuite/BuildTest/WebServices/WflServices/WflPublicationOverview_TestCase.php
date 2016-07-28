<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.3
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflPublicationOverview_TestCase extends TestCase
{
	private $ticket = null; // Session ticket
	private $transferServer = null; // BizTransferServer
	private $suiteOpts = null; //  TestSuite value
	private $pubObj = null;
	private $issueObj = null;
	private $editions = array();
	private $statuses = array();
	private $pubChannelObj = null;
	private $category = null;
	private $objectIds = null; // array( 'Layouts' => ids, 'Articles' => ids, 'Images' => ids )
	private $testService017_saveResp = null;  // testService017 saveObject response
	
	// Step#01: Fill in the TestGoals, TestMethods and Prio...
	public function getDisplayName() { return 'Publication Overview'; }
	public function getTestGoals()   { return 'Checks if the Publication Overview service works correctly.'; }
	public function getTestMethods() { return 'Scenario:<ol>
		<li>000: SC: Create Issue (AdmCreateIssues)</li>
		<li>001: SC: Create Layout#1 (WflCreateObjects)</li>
		<li>002: SC: Create Article#1 (WflCreateObjects)</li>
		<li>003: SC: Place Article#1 on the Layout#1 (WflCreateObjectRelations)</li>
		<li>004: SC: Save Layout#1 (WflSaveObjects)</li>
		<li>005: SC: Unlock Layout#1 (WflUnlockObjects)</li>
		<li>006: SC: Create Layout#2 (WflCreateObjects)</li>
		<li>007: SC: Create Image (WflCreateObjects)</li>
		<li>008: SC: Place the Image on Layout#2 (WflCreateObjectRelations)</li>
		<li>009: SC: Create Article#2 (WflCreateObjects)</li>
		<li>010: SC: Place Article #2 on Layout#2 (WflCreateObjectRelations)</li>
		<li>011: SC: Save Layout#2 (WflSaveObjects)</li>
		<li>012: SC: Unlock Layout#2 (WflUnlockObjects)</li>
		<li>013: SC: Create Layout#3 (WflCreateObjects)</li>
		<li>014: SC: Create Article#3 (WflCreateObjects)</li>
		<li>015: SC: Place Article#3 on page 1 of Layout#3 (WflCreateObjectRelations)</li>
		<li>016: SC: Place Article#3 on page 2 of Layout#3 (WflCreateObjectRelations)</li>
		<li>017: SC: Save Layout#3 (WflSaveObjects)</li>
		<li>018: SC: Unlock Layout#3 (WflUnlockObjects)</li>
		<li>019: CS: Publication Overview on First Edition (WflGetPagesInfo)</li>
		<li>020: CS: Get Page Thumbnail (WflGetPages)</li>
		<li>021: CS: Publication Overview on First Edition (WflGetPagesInfo)</li>
		<li>022: CS: Get Page Thumbnail (WflGetPages)</li>
		<li>023: CS: Delete objects created in this test (WflDeleteObjects)</li>
		<li>024: CS: Delete the issue in this test (AdmDeleteIssues)</li>
		</ol>'; }
	public function getPrio()        { return 150; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		if( !$this->wflServicesUtils->initTest( $this ) ) {
			return;
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		$this->suiteOpts = unserialize( TESTSUITE );

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}

		if( !$this->resolveBrandSetup() ) {
			return;
		}
		
		try {
			$this->setupTestData();

			// First Layout
			$this->testService001(); // WflCreateObjects
			$this->testService002();  // WflCreateObjects
			$this->testService003();  // WflCreateObjectRelations
			$this->testService004();  // WflSaveObjects
			$this->testService005();  // WflUnlockObjects
	
			// Second layout
			$this->testService006();  // WflCreateObjects
			$this->testService007();  // WflCreateObjects
			$this->testService008();  // WflCreateObjectRelations
			$this->testService009();  // WflCreateObjects
			$this->testService010();  // WflCreateObjectRelations
			$this->testService011();  // WflSaveObjects
			$this->testService012();  // WflUnlockObjects
		
			// Third layout
			$this->testService013();  // WflCreateObjects
			$this->testService014();  // WflCreateObjects
			$this->testService015();  // WflCreateObjectRelations
			$this->testService016();  // WflCreateObjectRelations
			$this->testService017();  // WflSaveObjects
			$this->testService018();  // WflUnlockObjects
	
			// Publication Overview Test
			$this->testService019();  // WflGetPagesInfo
			$this->testService020();  // WflGetPages
			$this->testService021();  // WflGetPagesInfo
			$this->testService022();  // WflGetPages
		}
		catch( BizException $e ) {
			$e = $e; // keep analyzer happy
		}
		
		// Remove all the test data objects and the test issue.
		$this->tearDownTestData();		
	}
	
	/**
	 * @throws BizException on unexpected service response.
	 */
	private function testService001()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest001();
	
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#001');
		$this->objectIds['Layouts'][] = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
	}

	private function getRecordedRequest001()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:1FA4D359B25CE3119B98DBF03AC804DA';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 409600;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2013-12-04T15:10:13';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2013-12-04T15:05:37';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
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
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#001_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#001_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '2';
		$request->Objects[0]->Pages[1]->PageOrder = 2;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#001_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#001_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#001_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#001_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#001_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editions[0]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editions[0]->Name;
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = $this->editions[1]->Id;
		$request->Objects[0]->Targets[0]->Editions[1]->Name = $this->editions[1]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}
	
	/**
	 * @throws BizException on unexpected service response.
	 */
	private function testService002()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest002();
	
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#002');
		$this->objectIds['Articles'][] = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
	}

	private function getRecordedRequest002()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Article_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 3;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 199.84252;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 39.685039;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 3;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 102596;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2013-12-04T15:11:45';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2013-12-04T15:11:45';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Article']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Article']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#002_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'd08118b0-b1e6-4e58-889b-052dc0053788';
		$request->Objects[0]->Elements[0]->Name = 'body';
		$request->Objects[0]->Elements[0]->LengthWords = 0;
		$request->Objects[0]->Elements[0]->LengthChars = 0;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = '';
		$request->Objects[0]->Elements[0]->Version = '4709152f-8f83-4597-8826-11146eefa60c';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = '27ad54db-4ad7-47fb-a9fc-1a79421eb519';
		$request->Objects[0]->Elements[1]->Name = 'body';
		$request->Objects[0]->Elements[1]->LengthWords = 0;
		$request->Objects[0]->Elements[1]->LengthChars = 0;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 1;
		$request->Objects[0]->Elements[1]->Snippet = '';
		$request->Objects[0]->Elements[1]->Version = '16025433-7f98-48a5-a96e-131470fd9a7b';
		$request->Objects[0]->Elements[1]->Content = null;
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = '8d4e352c-efa2-4519-a86b-4aece182817c';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 0;
		$request->Objects[0]->Elements[2]->LengthChars = 0;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 1;
		$request->Objects[0]->Elements[2]->Snippet = '';
		$request->Objects[0]->Elements[2]->Version = 'd88bd1dc-c9b5-4eec-b328-3c9f189253ac';
		$request->Objects[0]->Elements[2]->Content = null;
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}

	/**
	 * @throws BizException on unexpected service response.
	 */
	private function testService003()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$req = $this->getRecordedRequest003();
		$this->wflServicesUtils->callService( $req, 'testService#003');
	}

	private function getRecordedRequest003()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objectIds['Layouts'][0];
		$request->Relations[0]->Child = $this->objectIds['Articles'][0];
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = 1;
		$request->Relations[0]->Placements[0]->Element = 'body';
		$request->Relations[0]->Placements[0]->ElementID = 'd08118b0-b1e6-4e58-889b-052dc0053788';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '240';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = -197.118643;
		$request->Relations[0]->Placements[0]->OversetChars = -73;
		$request->Relations[0]->Placements[0]->OversetLines = -2;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->Placements[1] = new Placement();
		$request->Relations[0]->Placements[1]->Page = 1;
		$request->Relations[0]->Placements[1]->Element = 'body';
		$request->Relations[0]->Placements[1]->ElementID = '27ad54db-4ad7-47fb-a9fc-1a79421eb519';
		$request->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Relations[0]->Placements[1]->FrameID = '264';
		$request->Relations[0]->Placements[1]->Left = 0;
		$request->Relations[0]->Placements[1]->Top = 0;
		$request->Relations[0]->Placements[1]->Width = 0;
		$request->Relations[0]->Placements[1]->Height = 0;
		$request->Relations[0]->Placements[1]->Overset = -197.118643;
		$request->Relations[0]->Placements[1]->OversetChars = -73;
		$request->Relations[0]->Placements[1]->OversetLines = -2;
		$request->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[1]->Content = '';
		$request->Relations[0]->Placements[1]->Edition = null;
		$request->Relations[0]->Placements[1]->ContentDx = null;
		$request->Relations[0]->Placements[1]->ContentDy = null;
		$request->Relations[0]->Placements[1]->ScaleX = null;
		$request->Relations[0]->Placements[1]->ScaleY = null;
		$request->Relations[0]->Placements[1]->PageSequence = 1;
		$request->Relations[0]->Placements[1]->PageNumber = '1';
		$request->Relations[0]->Placements[1]->Tiles = array();
		$request->Relations[0]->Placements[2] = new Placement();
		$request->Relations[0]->Placements[2]->Page = 1;
		$request->Relations[0]->Placements[2]->Element = 'body';
		$request->Relations[0]->Placements[2]->ElementID = '8d4e352c-efa2-4519-a86b-4aece182817c';
		$request->Relations[0]->Placements[2]->FrameOrder = 0;
		$request->Relations[0]->Placements[2]->FrameID = '288';
		$request->Relations[0]->Placements[2]->Left = 0;
		$request->Relations[0]->Placements[2]->Top = 0;
		$request->Relations[0]->Placements[2]->Width = 0;
		$request->Relations[0]->Placements[2]->Height = 0;
		$request->Relations[0]->Placements[2]->Overset = -197.118643;
		$request->Relations[0]->Placements[2]->OversetChars = -73;
		$request->Relations[0]->Placements[2]->OversetLines = -2;
		$request->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[2]->Content = '';
		$request->Relations[0]->Placements[2]->Edition = null;
		$request->Relations[0]->Placements[2]->ContentDx = null;
		$request->Relations[0]->Placements[2]->ContentDy = null;
		$request->Relations[0]->Placements[2]->ScaleX = null;
		$request->Relations[0]->Placements[2]->ScaleY = null;
		$request->Relations[0]->Placements[2]->PageSequence = 1;
		$request->Relations[0]->Placements[2]->PageNumber = '1';
		$request->Relations[0]->Placements[2]->Tiles = array();
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		return $request;
	}

	/**
	 * @throws BizException on unexpected service response.
	 */
	private function testService004()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getRecordedRequest004();
		$this->wflServicesUtils->callService( $req, 'testService#004');
	}

	private function getRecordedRequest004()
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
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][0];
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:B8BAF61FB35CE3119B98DBF03AC804DA';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 335872;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Layouts'][0];
		$request->Objects[0]->Relations[0]->Child = $this->objectIds['Articles'][0];
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = 'd08118b0-b1e6-4e58-889b-052dc0053788';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '240';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 120;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 102;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 199;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 48;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -197.118643;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = -73;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = -2;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[1] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[1]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[1]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[1]->ElementID = '27ad54db-4ad7-47fb-a9fc-1a79421eb519';
		$request->Objects[0]->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->FrameID = '264';
		$request->Objects[0]->Relations[0]->Placements[1]->Left = 120;
		$request->Objects[0]->Relations[0]->Placements[1]->Top = 170;
		$request->Objects[0]->Relations[0]->Placements[1]->Width = 199;
		$request->Objects[0]->Relations[0]->Placements[1]->Height = 43;
		$request->Objects[0]->Relations[0]->Placements[1]->Overset = -197.118643;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetChars = -73;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetLines = -2;
		$request->Objects[0]->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[1]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[1]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[1]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[1]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[1]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[2] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[2]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[2]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[2]->ElementID = '8d4e352c-efa2-4519-a86b-4aece182817c';
		$request->Objects[0]->Relations[0]->Placements[2]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[2]->FrameID = '288';
		$request->Objects[0]->Relations[0]->Placements[2]->Left = 120;
		$request->Objects[0]->Relations[0]->Placements[2]->Top = 233;
		$request->Objects[0]->Relations[0]->Placements[2]->Width = 199;
		$request->Objects[0]->Relations[0]->Placements[2]->Height = 39;
		$request->Objects[0]->Relations[0]->Placements[2]->Overset = -197.118643;
		$request->Objects[0]->Relations[0]->Placements[2]->OversetChars = -73;
		$request->Objects[0]->Relations[0]->Placements[2]->OversetLines = -2;
		$request->Objects[0]->Relations[0]->Placements[2]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[2]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[2]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[2]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[2]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[2]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[2]->Tiles = array();
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#004_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#004_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '2';
		$request->Objects[0]->Pages[1]->PageOrder = 2;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#004_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#004_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#004_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#004_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#004_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editions[0]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editions[0]->Name;
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = $this->editions[1]->Id;
		$request->Objects[0]->Targets[0]->Editions[1]->Name = $this->editions[1]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}
	
	/**
	 * @throws BizException on unexpected service response.
	 */
	private function testService005()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$req = $this->getRecordedRequest005();
		$this->wflServicesUtils->callService( $req, 'testService#005');
	}

	private function getRecordedRequest005()
	{
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objectIds['Layouts'][0];
		$request->ReadMessageIDs = null;
		$request->MessageList = null;
		return $request;
	}

	/**
	 * @throws BizException on unexpected service response.
	 */
	private function testService006()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest006();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#006');
		$this->objectIds['Layouts'][] = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
	}

	private function getRecordedRequest006()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:20A4D359B25CE3119B98DBF03AC804DA';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_2';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 409600;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2013-12-04T15:10:43';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2013-12-04T15:05:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
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
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#006_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#006_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '2';
		$request->Objects[0]->Pages[1]->PageOrder = 2;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#006_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#006_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#006_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#006_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#006_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editions[0]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editions[0]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}

	
	private function testService007()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest007();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#007');
		$this->objectIds['Images'][] = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		return !$this->hasError();
	}

	private function getRecordedRequest007()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Image_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 443;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 386;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 96;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 3430;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Image']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Image']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
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
		$request->Objects[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#007_att#000_native.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#007_att#001_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#007_att#002_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}

	private function testService008()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$req = $this->getRecordedRequest008();
		$this->wflServicesUtils->callService( $req, 'testService#008');
		return !$this->hasError();
	}

	private function getRecordedRequest008()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objectIds['Layouts'][1];
		$request->Relations[0]->Child = $this->objectIds['Images'][0];
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = 1;
		$request->Relations[0]->Placements[0]->Element = 'graphic';
		$request->Relations[0]->Placements[0]->ElementID = '';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '224';
		$request->Relations[0]->Placements[0]->Left = 177;
		$request->Relations[0]->Placements[0]->Top = 164;
		$request->Relations[0]->Placements[0]->Width = 232;
		$request->Relations[0]->Placements[0]->Height = 153;
		$request->Relations[0]->Placements[0]->Overset = null;
		$request->Relations[0]->Placements[0]->OversetChars = null;
		$request->Relations[0]->Placements[0]->OversetLines = null;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = 0;
		$request->Relations[0]->Placements[0]->ContentDy = 0;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		return $request;
	}

	private function testService009()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest009();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#009');
		$this->objectIds['Articles'][] = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		return !$this->hasError();
	}

	private function getRecordedRequest009()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Article_2';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 1;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 266.456693;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 189.92126;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 1;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 1;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 46964;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2013-12-04T15:15:13';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2013-12-04T15:15:13';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Article']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Article']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#009_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'e3d939ea-2503-48c8-a62d-cf8972ec960c';
		$request->Objects[0]->Elements[0]->Name = 'body';
		$request->Objects[0]->Elements[0]->LengthWords = 0;
		$request->Objects[0]->Elements[0]->LengthChars = 0;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = '';
		$request->Objects[0]->Elements[0]->Version = '2a14a34f-a709-4d02-a89e-120813e73da6';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}

	private function testService010()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$req = $this->getRecordedRequest010();
		$this->wflServicesUtils->callService( $req, 'testService#010');
		return !$this->hasError();
	}

	private function getRecordedRequest010()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objectIds['Layouts'][1];
		$request->Relations[0]->Child = $this->objectIds['Articles'][1];
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = null;
		$request->Relations[0]->Placements[0]->Element = 'body';
		$request->Relations[0]->Placements[0]->ElementID = 'e3d939ea-2503-48c8-a62d-cf8972ec960c';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '248';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = -263.732816;
		$request->Relations[0]->Placements[0]->OversetChars = -97;
		$request->Relations[0]->Placements[0]->OversetLines = -12;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 0;
		$request->Relations[0]->Placements[0]->PageNumber = '';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		return $request;
	}

	private function testService011()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getRecordedRequest011();
		$this->wflServicesUtils->callService( $req, 'testService#011');
		return !$this->hasError();
	}

	private function getRecordedRequest011()
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
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][1];
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:BDBAF61FB35CE3119B98DBF03AC804DA';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_2';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 327680;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Layouts'][1];
		$request->Objects[0]->Relations[0]->Child = $this->objectIds['Images'][0];
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'graphic';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = '';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '224';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 177;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 164;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 232;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 153;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = null;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = null;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = null;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[1] = new Relation();
		$request->Objects[0]->Relations[1]->Parent = $this->objectIds['Layouts'][1];
		$request->Objects[0]->Relations[1]->Child = $this->objectIds['Articles'][1];
		$request->Objects[0]->Relations[1]->Type = 'Placed';
		$request->Objects[0]->Relations[1]->Placements = array();
		$request->Objects[0]->Relations[1]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[1]->Placements[0]->Page = null;
		$request->Objects[0]->Relations[1]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[1]->Placements[0]->ElementID = 'e3d939ea-2503-48c8-a62d-cf8972ec960c';
		$request->Objects[0]->Relations[1]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->FrameID = '248';
		$request->Objects[0]->Relations[1]->Placements[0]->Left = 612;
		$request->Objects[0]->Relations[1]->Placements[0]->Top = 52;
		$request->Objects[0]->Relations[1]->Placements[0]->Width = 266;
		$request->Objects[0]->Relations[1]->Placements[0]->Height = 189;
		$request->Objects[0]->Relations[1]->Placements[0]->Overset = -263.732816;
		$request->Objects[0]->Relations[1]->Placements[0]->OversetChars = -97;
		$request->Objects[0]->Relations[1]->Placements[0]->OversetLines = -12;
		$request->Objects[0]->Relations[1]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[1]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[1]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[1]->Placements[0]->PageSequence = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->PageNumber = '';
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[1]->ParentVersion = null;
		$request->Objects[0]->Relations[1]->ChildVersion = null;
		$request->Objects[0]->Relations[1]->Geometry = null;
		$request->Objects[0]->Relations[1]->Rating = null;
		$request->Objects[0]->Relations[1]->Targets = null;
		$request->Objects[0]->Relations[1]->ParentInfo = null;
		$request->Objects[0]->Relations[1]->ChildInfo = null;
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#011_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#011_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '2';
		$request->Objects[0]->Pages[1]->PageOrder = 2;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#011_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#011_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#011_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#011_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#011_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editions[0]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editions[0]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}
	
	private function testService012()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$req = $this->getRecordedRequest012();
		$this->wflServicesUtils->callService( $req, 'testService#012');
		return !$this->hasError();
	}

	private function getRecordedRequest012()
	{
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objectIds['Layouts'][1];
		$request->ReadMessageIDs = null;
		$request->MessageList = null;
		return $request;
	}

	private function testService013()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest013();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#013');
		$this->objectIds['Layouts'][] = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		return !$this->hasError();
	}

	private function getRecordedRequest013()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:21A4D359B25CE3119B98DBF03AC804DA';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_3';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 409600;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2013-12-04T15:11:31';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2013-12-04T15:05:42';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
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
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#013_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#013_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '2';
		$request->Objects[0]->Pages[1]->PageOrder = 2;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#013_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#013_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#013_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#013_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#013_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editions[1]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editions[1]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}
	

	private function testService014()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest014();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#014');
		$this->objectIds['Articles'][] = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		return !$this->hasError();
	}

	private function getRecordedRequest014()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Article_3';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 1;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 307.559055;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 99.212598;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 1;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 1;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 45910;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2013-12-04T15:13:53';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2013-12-04T15:13:53';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Article']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Article']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#014_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'a02b92f1-9bf5-4288-8933-60f4023c65d6';
		$request->Objects[0]->Elements[0]->Name = 'body';
		$request->Objects[0]->Elements[0]->LengthWords = 0;
		$request->Objects[0]->Elements[0]->LengthChars = 0;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = '';
		$request->Objects[0]->Elements[0]->Version = '7fbae3a6-f278-401a-ac7f-ab39b41e7098';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}
	
	private function testService015()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$req = $this->getRecordedRequest015();
		$this->wflServicesUtils->callService( $req, 'testService#015');
		return !$this->hasError();
	}

	private function getRecordedRequest015()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objectIds['Layouts'][2];
		$request->Relations[0]->Child = $this->objectIds['Articles'][2];
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = 1;
		$request->Relations[0]->Placements[0]->Element = 'body';
		$request->Relations[0]->Placements[0]->ElementID = 'a02b92f1-9bf5-4288-8933-60f4023c65d6';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '240';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = -304.835178;
		$request->Relations[0]->Placements[0]->OversetChars = -112;
		$request->Relations[0]->Placements[0]->OversetLines = -6;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		return $request;
	}
	
	private function testService016()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$req = $this->getRecordedRequest016();
		$this->wflServicesUtils->callService( $req, 'testService#016');
		return !$this->hasError();
	}

	private function getRecordedRequest016()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objectIds['Layouts'][2];
		$request->Relations[0]->Child = $this->objectIds['Articles'][2];
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = 1;
		$request->Relations[0]->Placements[0]->Element = 'body';
		$request->Relations[0]->Placements[0]->ElementID = 'a02b92f1-9bf5-4288-8933-60f4023c65d6';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '240';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = -304.835178;
		$request->Relations[0]->Placements[0]->OversetChars = -112;
		$request->Relations[0]->Placements[0]->OversetLines = -6;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->Placements[1] = new Placement();
		$request->Relations[0]->Placements[1]->Page = 2;
		$request->Relations[0]->Placements[1]->Element = 'body';
		$request->Relations[0]->Placements[1]->ElementID = 'a02b92f1-9bf5-4288-8933-60f4023c65d6';
		$request->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Relations[0]->Placements[1]->FrameID = '273';
		$request->Relations[0]->Placements[1]->Left = 0;
		$request->Relations[0]->Placements[1]->Top = 0;
		$request->Relations[0]->Placements[1]->Width = 0;
		$request->Relations[0]->Placements[1]->Height = 0;
		$request->Relations[0]->Placements[1]->Overset = null;
		$request->Relations[0]->Placements[1]->OversetChars = 1;
		$request->Relations[0]->Placements[1]->OversetLines = 1;
		$request->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[1]->Content = '';
		$request->Relations[0]->Placements[1]->Edition = null;
		$request->Relations[0]->Placements[1]->ContentDx = null;
		$request->Relations[0]->Placements[1]->ContentDy = null;
		$request->Relations[0]->Placements[1]->ScaleX = null;
		$request->Relations[0]->Placements[1]->ScaleY = null;
		$request->Relations[0]->Placements[1]->PageSequence = 2;
		$request->Relations[0]->Placements[1]->PageNumber = '2';
		$request->Relations[0]->Placements[1]->Tiles = array();
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		return $request;
	}
	
	private function testService017()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getRecordedRequest017();
		// Get the saveObject response, where it contains duplicate placement messages,
		// which will be use in later GetPages service recorded response for comparison purposes.
		$this->testService017_saveResp = $this->wflServicesUtils->callService( $req, 'testService#017');
		return !$this->hasError();
	}

	private function getRecordedRequest017()
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
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][2];
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:C2BAF61FB35CE3119B98DBF03AC804DA';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_3';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 323584;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Layouts'][2];
		$request->Objects[0]->Relations[0]->Child = $this->objectIds['Articles'][2];
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = 'a02b92f1-9bf5-4288-8933-60f4023c65d6';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '240';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 137;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 668;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 307;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 99;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -304.835178;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = -112;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = -6;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[1] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[1]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[1]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[1]->ElementID = 'a02b92f1-9bf5-4288-8933-60f4023c65d6';
		$request->Objects[0]->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->FrameID = '273';
		$request->Objects[0]->Relations[0]->Placements[1]->Left = 36;
		$request->Objects[0]->Relations[0]->Placements[1]->Top = 146;
		$request->Objects[0]->Relations[0]->Placements[1]->Width = 523;
		$request->Objects[0]->Relations[0]->Placements[1]->Height = 659;
		$request->Objects[0]->Relations[0]->Placements[1]->Overset = -520.551714;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetChars = -192;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetLines = -45;
		$request->Objects[0]->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[1]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[1]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[1]->PageSequence = 2;
		$request->Objects[0]->Relations[0]->Placements[1]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[1]->Tiles = array();
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#017_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#017_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '2';
		$request->Objects[0]->Pages[1]->PageOrder = 2;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#017_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#017_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
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
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#017_att#004_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#017_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#017_att#006_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = $this->editions[1]->Id;
		$request->Objects[0]->Targets[0]->Editions[1]->Name = $this->editions[1]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}
	
	private function testService018()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$req = $this->getRecordedRequest018();
		$this->wflServicesUtils->callService( $req, 'testService#018');
		return !$this->hasError();
	}

	private function getRecordedRequest018()
	{
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objectIds['Layouts'][2];
		$request->ReadMessageIDs = null;
		$request->MessageList = new MessageList();
		$request->MessageList->Messages = null;
		$request->MessageList->ReadMessageIDs = null;
		$request->MessageList->DeleteMessageIDs = array();
		$request->MessageList->DeleteMessageIDs[0] = '2025401f-c62c-d59a-a765-fd46c1c78322';
		return $request;
	}

	private function testService019()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetPagesInfoService.class.php';
		$req = $this->getRecordedRequest019();
		$recResp = $this->getRecordedResponse019();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#019');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '019' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '019' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetPagesInfo response.');
		}
		return !$this->hasError();
	}

	private function getRecordedRequest019()
	{
		$request = new WflGetPagesInfoRequest();
		$request->Ticket = $this->ticket;
		$request->Issue = new Issue();
		$request->Issue->Id = $this->issueObj->Id;
		$request->Issue->Name = $this->issueObj->Name;
		$request->Issue->OverrulePublication = 'false';
		$request->IDs = null;
		$request->Edition = new Edition();
		$request->Edition->Id = $this->editions[0]->Id;
		$request->Edition->Name = $this->editions[0]->Name;
		return $request;
	}
	
	private function getRecordedResponse019()
	{
		require_once BASEDIR.'/server/bizclasses/BizPageInfo.class.php';
		$response = new WflGetPagesInfoResponse();
		$response->ReversedReadingOrder = false;
		$response->ExpectedPages = 32;
		$response->PageOrderMethod = 'PageOrdered';
		$response->EditionsPages = array();
		$response->EditionsPages[0] = new EditionPages();
		$response->EditionsPages[0]->Edition = new Edition();
		$response->EditionsPages[0]->Edition->Id = $this->editions[0]->Id;
		$response->EditionsPages[0]->Edition->Name = $this->editions[0]->Name;
		$response->EditionsPages[0]->PageObjects = array();
		$response->EditionsPages[0]->PageObjects[0] = new PageObject();
		$response->EditionsPages[0]->PageObjects[0]->IssuePagePosition = 1;
		$response->EditionsPages[0]->PageObjects[0]->PageOrder = 1;
		$response->EditionsPages[0]->PageObjects[0]->PageNumber = '1';
		$response->EditionsPages[0]->PageObjects[0]->PageSequence = 1;
		$response->EditionsPages[0]->PageObjects[0]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[0]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[0]->ParentLayoutId = $this->objectIds['Layouts'][1];
		$response->EditionsPages[0]->PageObjects[0]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Id = $this->objectIds['Images'][0];
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Left = 177;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Top = 164;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Width = 232;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Height = 153;
		$response->EditionsPages[0]->PageObjects[0]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[0]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[0]->ppn->RealPageNumber = 1;
		$response->EditionsPages[0]->PageObjects[0]->ppn->DisplayPageNumber = '1';
		$response->EditionsPages[0]->PageObjects[0]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[0]->ppn->SortOrder = 3000000;
		$response->EditionsPages[0]->PageObjects[1] = new PageObject();
		$response->EditionsPages[0]->PageObjects[1]->IssuePagePosition = 2;
		$response->EditionsPages[0]->PageObjects[1]->PageOrder = 2;
		$response->EditionsPages[0]->PageObjects[1]->PageNumber = '2';
		$response->EditionsPages[0]->PageObjects[1]->PageSequence = 2;
		$response->EditionsPages[0]->PageObjects[1]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[1]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[1]->ParentLayoutId = $this->objectIds['Layouts'][1];
		$response->EditionsPages[0]->PageObjects[1]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[1]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[1]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[1]->ppn->RealPageNumber = 2;
		$response->EditionsPages[0]->PageObjects[1]->ppn->DisplayPageNumber = '2';
		$response->EditionsPages[0]->PageObjects[1]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[1]->ppn->SortOrder = 3000000;
		$response->EditionsPages[0]->PageObjects[2] = new PageObject();
		$response->EditionsPages[0]->PageObjects[2]->IssuePagePosition = 3;
		$response->EditionsPages[0]->PageObjects[2]->PageOrder = 1;
		$response->EditionsPages[0]->PageObjects[2]->PageNumber = '1';
		$response->EditionsPages[0]->PageObjects[2]->PageSequence = 1;
		$response->EditionsPages[0]->PageObjects[2]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[2]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[2]->ParentLayoutId = $this->objectIds['Layouts'][0];
		$response->EditionsPages[0]->PageObjects[2]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Id = $this->objectIds['Articles'][0];
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Left = 120;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Top = 102;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Width = 199;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Height = 48;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Id = $this->objectIds['Articles'][0];
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Left = 120;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Top = 170;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Width = 199;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Height = 43;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Id = $this->objectIds['Articles'][0];
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Left = 120;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Top = 233;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Width = 199;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Height = 39;
		$response->EditionsPages[0]->PageObjects[2]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[2]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[2]->ppn->RealPageNumber = 1;
		$response->EditionsPages[0]->PageObjects[2]->ppn->DisplayPageNumber = '1';
		$response->EditionsPages[0]->PageObjects[2]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[2]->ppn->SortOrder = 3000000;
		$response->EditionsPages[0]->PageObjects[3] = new PageObject();
		$response->EditionsPages[0]->PageObjects[3]->IssuePagePosition = 4;
		$response->EditionsPages[0]->PageObjects[3]->PageOrder = 2;
		$response->EditionsPages[0]->PageObjects[3]->PageNumber = '2';
		$response->EditionsPages[0]->PageObjects[3]->PageSequence = 2;
		$response->EditionsPages[0]->PageObjects[3]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[3]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[3]->ParentLayoutId = $this->objectIds['Layouts'][0];
		$response->EditionsPages[0]->PageObjects[3]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[3]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[3]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[3]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[3]->ppn->RealPageNumber = 1;
		$response->EditionsPages[0]->PageObjects[3]->ppn->DisplayPageNumber = '1';
		$response->EditionsPages[0]->PageObjects[3]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[3]->ppn->SortOrder = 3000000;
		$response->LayoutObjects = array();
		$response->LayoutObjects[0] = new LayoutObject();
		$response->LayoutObjects[0]->Id = $this->objectIds['Layouts'][1];
		$response->LayoutObjects[0]->Name = 'PubOverview_BuildTest_Layout_2';
		$response->LayoutObjects[0]->Category = new Category();
		$response->LayoutObjects[0]->Category->Id = $this->category->Id;
		$response->LayoutObjects[0]->Category->Name = $this->category->Name;
		$response->LayoutObjects[0]->State = new State();
		$response->LayoutObjects[0]->State->Id = $this->statuses['Layout']->Id;
		$response->LayoutObjects[0]->State->Name = $this->statuses['Layout']->Name;
		$response->LayoutObjects[0]->State->Type = 'Layout';
		$response->LayoutObjects[0]->State->Produce = null;
		$response->LayoutObjects[0]->State->Color = $this->statuses['Layout']->Color;
		$response->LayoutObjects[0]->State->DefaultRouteTo = null;
		$response->LayoutObjects[0]->Version = '0.2';
		$response->LayoutObjects[0]->LockedBy = '';
		$response->LayoutObjects[0]->Modified = '2013-12-04T15:15:44';
		$response->LayoutObjects[1] = new LayoutObject();
		$response->LayoutObjects[1]->Id = $this->objectIds['Layouts'][0];
		$response->LayoutObjects[1]->Name = 'PubOverview_BuildTest_Layout_1';
		$response->LayoutObjects[1]->Category = new Category();
		$response->LayoutObjects[1]->Category->Id = $this->category->Id;
		$response->LayoutObjects[1]->Category->Name = $this->category->Name;
		$response->LayoutObjects[1]->State = new State();
		$response->LayoutObjects[1]->State->Id = $this->statuses['Layout']->Id;
		$response->LayoutObjects[1]->State->Name = $this->statuses['Layout']->Name;
		$response->LayoutObjects[1]->State->Type = 'Layout';
		$response->LayoutObjects[1]->State->Produce = null;
		$response->LayoutObjects[1]->State->Color = $this->statuses['Layout']->Color;
		$response->LayoutObjects[1]->State->DefaultRouteTo = null;
		$response->LayoutObjects[1]->Version = '0.2';
		$response->LayoutObjects[1]->LockedBy = '';
		$response->LayoutObjects[1]->Modified = '2013-12-04T15:14:53';
		$response->PlacedObjects = array();
		$response->PlacedObjects[0] = new PlacedObject();
		$response->PlacedObjects[0]->Id = $this->objectIds['Articles'][0];
		$response->PlacedObjects[0]->Name = 'PubOverview_BuildTest_Article_1';
		$response->PlacedObjects[0]->Type = 'Article';
		$response->PlacedObjects[0]->State = new State();
		$response->PlacedObjects[0]->State->Id = $this->statuses['Article']->Id;
		$response->PlacedObjects[0]->State->Name = $this->statuses['Article']->Name;
		$response->PlacedObjects[0]->State->Type = 'Article';
		$response->PlacedObjects[0]->State->Produce = null;
		$response->PlacedObjects[0]->State->Color = $this->statuses['Article']->Color;
		$response->PlacedObjects[0]->State->DefaultRouteTo = null;
		$response->PlacedObjects[0]->Version = '0.1';
		$response->PlacedObjects[0]->LockedBy = '';
		$response->PlacedObjects[0]->Format = 'application/incopyicml';
		$response->PlacedObjects[1] = new PlacedObject();
		$response->PlacedObjects[1]->Id = $this->objectIds['Images'][0];
		$response->PlacedObjects[1]->Name = 'PubOverview_BuildTest_Image_1';
		$response->PlacedObjects[1]->Type = 'Image';
		$response->PlacedObjects[1]->State = new State();
		$response->PlacedObjects[1]->State->Id = $this->statuses['Image']->Id;
		$response->PlacedObjects[1]->State->Name = $this->statuses['Image']->Name;
		$response->PlacedObjects[1]->State->Type = 'Image';
		$response->PlacedObjects[1]->State->Produce = null;
		$response->PlacedObjects[1]->State->Color = $this->statuses['Image']->Color;
		$response->PlacedObjects[1]->State->DefaultRouteTo = null;
		$response->PlacedObjects[1]->Version = '0.1';
		$response->PlacedObjects[1]->LockedBy = '';
		$response->PlacedObjects[1]->Format = 'image/jpeg';
		$response->PlacedObjects[2] = new PlacedObject();
		$response->PlacedObjects[2]->Id = $this->objectIds['Articles'][1];
		$response->PlacedObjects[2]->Name = 'PubOverview_BuildTest_Article_2';
		$response->PlacedObjects[2]->Type = 'Article';
		$response->PlacedObjects[2]->State = new State();
		$response->PlacedObjects[2]->State->Id = $this->statuses['Article']->Id;
		$response->PlacedObjects[2]->State->Name = $this->statuses['Article']->Name;
		$response->PlacedObjects[2]->State->Type = 'Article';
		$response->PlacedObjects[2]->State->Produce = null;
		$response->PlacedObjects[2]->State->Color = $this->statuses['Article']->Color;
		$response->PlacedObjects[2]->State->DefaultRouteTo = null;
		$response->PlacedObjects[2]->Version = '0.1';
		$response->PlacedObjects[2]->LockedBy = '';
		$response->PlacedObjects[2]->Format = 'application/incopyicml';
		return $response;
	}
	
	private function testService020()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetPagesService.class.php';
		$req = $this->getRecordedRequest020();
		$recResp = $this->getRecordedResponse020();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#020');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$ignorePaths = array(
			'WflGetPagesResponse->ObjectPageInfos[2]->MessageList->Messages[0]->MessageStatus' => true,
			'WflGetPagesResponse->ObjectPageInfos[2]->MessageList->Messages[0]->ThreadMessageID' => true,
			'WflGetPagesResponse->ObjectPageInfos[2]->MessageList->Messages[0]->ReplyToMessageID' => true,
		);
		$phpCompare->initCompare( $ignorePaths, $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '020' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '020' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetPages response.');
		}
		return !$this->hasError();
	}

	private function getRecordedRequest020()
	{
		$request = new WflGetPagesRequest();
		$request->Ticket = $this->ticket;
		$request->Params = null;
		$request->IDs = array();
		$request->IDs[0] = $this->objectIds['Layouts'][0];
		$request->IDs[1] = $this->objectIds['Layouts'][1];
		$request->IDs[2] = $this->objectIds['Layouts'][2];
		$request->PageOrders = null;
		$request->PageSequences = null;
		$request->Edition = new Edition();
		$request->Edition->Id = $this->editions[0]->Id;
		$request->Edition->Name = $this->editions[0]->Name;
		$request->Renditions = array();
		$request->Renditions[0] = 'thumb';
		$request->RequestMetaData = null;
		$request->RequestFiles = null;
		return $request;
	}
	
	private function getRecordedResponse020()
	{
		$response = new WflGetPagesResponse();
		$response->ObjectPageInfos = array();
		$response->ObjectPageInfos[0] = new ObjectPageInfo();
		$response->ObjectPageInfos[0]->MetaData = new MetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][0];
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:B8BAF61FB35CE3119B98DBF03AC804DA';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_1';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->StoreName = '41/4101';
		$response->ObjectPageInfos[0]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Urgency = null;
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modifier = WW_Utils_TestSuiteOptions::getUser();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modified = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Comment = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Color = $this->statuses['Layout']->Color;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Version = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[0]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[0]->Pages = array();
		$response->ObjectPageInfos[0]->Pages[0] = new Page();
		$response->ObjectPageInfos[0]->Pages[0]->Width = 595.275591;
		$response->ObjectPageInfos[0]->Pages[0]->Height = 841.889764;
		$response->ObjectPageInfos[0]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[0]->Pages[0]->PageOrder = 1;
		$response->ObjectPageInfos[0]->Pages[0]->Files = array();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#020_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[0]->Pages[0]->Files[0] );
		$response->ObjectPageInfos[0]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[0]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[0]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[0]->Pages[0]->PageSequence = 1;
		$response->ObjectPageInfos[0]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[0]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[0]->Pages[1] = new Page();
		$response->ObjectPageInfos[0]->Pages[1]->Width = 595.275591;
		$response->ObjectPageInfos[0]->Pages[1]->Height = 841.889764;
		$response->ObjectPageInfos[0]->Pages[1]->PageNumber = '2';
		$response->ObjectPageInfos[0]->Pages[1]->PageOrder = 2;
		$response->ObjectPageInfos[0]->Pages[1]->Files = array();
		$response->ObjectPageInfos[0]->Pages[1]->Files[0] = new Attachment();
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->Content = null;
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#020_att#001_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[0]->Pages[1]->Files[0] );
		$response->ObjectPageInfos[0]->Pages[1]->Edition = null;
		$response->ObjectPageInfos[0]->Pages[1]->Master = 'Master';
		$response->ObjectPageInfos[0]->Pages[1]->Instance = 'Production';
		$response->ObjectPageInfos[0]->Pages[1]->PageSequence = 2;
		$response->ObjectPageInfos[0]->Pages[1]->Renditions = array();
		$response->ObjectPageInfos[0]->Pages[1]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[0]->Pages[1]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[0]->Pages[1]->Orientation = '';
		$response->ObjectPageInfos[0]->Messages = null;
		$response->ObjectPageInfos[0]->MessageList = null;
		$response->ObjectPageInfos[1] = new ObjectPageInfo();
		$response->ObjectPageInfos[1]->MetaData = new MetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][1];
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->DocumentID = 'xmp.did:BDBAF61FB35CE3119B98DBF03AC804DA';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_2';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->StoreName = '41/4102';
		$response->ObjectPageInfos[1]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Urgency = null;
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modifier = WW_Utils_TestSuiteOptions::getUser();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modified = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Comment = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Color = $this->statuses['Layout']->Color;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Version = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[1]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[1]->Pages = array();
		$response->ObjectPageInfos[1]->Pages[0] = new Page();
		$response->ObjectPageInfos[1]->Pages[0]->Width = 595.275591;
		$response->ObjectPageInfos[1]->Pages[0]->Height = 841.889764;
		$response->ObjectPageInfos[1]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[1]->Pages[0]->PageOrder = 1;
		$response->ObjectPageInfos[1]->Pages[0]->Files = array();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#020_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[1]->Pages[0]->Files[0] );
		$response->ObjectPageInfos[1]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[1]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[1]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[1]->Pages[0]->PageSequence = 1;
		$response->ObjectPageInfos[1]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[1]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[1]->Pages[1] = new Page();
		$response->ObjectPageInfos[1]->Pages[1]->Width = 595.275591;
		$response->ObjectPageInfos[1]->Pages[1]->Height = 841.889764;
		$response->ObjectPageInfos[1]->Pages[1]->PageNumber = '2';
		$response->ObjectPageInfos[1]->Pages[1]->PageOrder = 2;
		$response->ObjectPageInfos[1]->Pages[1]->Files = array();
		$response->ObjectPageInfos[1]->Pages[1]->Files[0] = new Attachment();
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->Content = null;
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#020_att#003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[1]->Pages[1]->Files[0] );
		$response->ObjectPageInfos[1]->Pages[1]->Edition = null;
		$response->ObjectPageInfos[1]->Pages[1]->Master = 'Master';
		$response->ObjectPageInfos[1]->Pages[1]->Instance = 'Production';
		$response->ObjectPageInfos[1]->Pages[1]->PageSequence = 2;
		$response->ObjectPageInfos[1]->Pages[1]->Renditions = array();
		$response->ObjectPageInfos[1]->Pages[1]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[1]->Pages[1]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[1]->Pages[1]->Orientation = '';
		$response->ObjectPageInfos[1]->Messages = null;
		$response->ObjectPageInfos[1]->MessageList = null;
		$response->ObjectPageInfos[2] = new ObjectPageInfo();
		$response->ObjectPageInfos[2]->MetaData = new MetaData();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][2];
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->DocumentID = 'xmp.did:C2BAF61FB35CE3119B98DBF03AC804DA';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_3';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->StoreName = '41/4103';
		$response->ObjectPageInfos[2]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[2]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[2]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Urgency = null;
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Modifier = WW_Utils_TestSuiteOptions::getUser();
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Modified = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Comment = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Color = $this->statuses['Layout']->Color;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Version = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[2]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[2]->Pages = array();
		$response->ObjectPageInfos[2]->Pages[0] = new Page();
		$response->ObjectPageInfos[2]->Pages[0]->Width = 595.275591;
		$response->ObjectPageInfos[2]->Pages[0]->Height = 841.889764;
		$response->ObjectPageInfos[2]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[2]->Pages[0]->PageOrder = 1;
		$response->ObjectPageInfos[2]->Pages[0]->Files = array();
		$response->ObjectPageInfos[2]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#020_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[2]->Pages[0]->Files[0] );
		$response->ObjectPageInfos[2]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[2]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[2]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[2]->Pages[0]->PageSequence = 1;
		$response->ObjectPageInfos[2]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[2]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[2]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[2]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[2]->Pages[1] = new Page();
		$response->ObjectPageInfos[2]->Pages[1]->Width = 595.275591;
		$response->ObjectPageInfos[2]->Pages[1]->Height = 841.889764;
		$response->ObjectPageInfos[2]->Pages[1]->PageNumber = '2';
		$response->ObjectPageInfos[2]->Pages[1]->PageOrder = 2;
		$response->ObjectPageInfos[2]->Pages[1]->Files = array();
		$response->ObjectPageInfos[2]->Pages[1]->Files[0] = new Attachment();
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->Content = null;
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#020_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[2]->Pages[1]->Files[0] );
		$response->ObjectPageInfos[2]->Pages[1]->Edition = null;
		$response->ObjectPageInfos[2]->Pages[1]->Master = 'Master';
		$response->ObjectPageInfos[2]->Pages[1]->Instance = 'Production';
		$response->ObjectPageInfos[2]->Pages[1]->PageSequence = 2;
		$response->ObjectPageInfos[2]->Pages[1]->Renditions = array();
		$response->ObjectPageInfos[2]->Pages[1]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[2]->Pages[1]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[2]->Pages[1]->Orientation = '';
		$response->ObjectPageInfos[2]->Messages = null;
		$response->ObjectPageInfos[2]->MessageList = $this->testService017_saveResp->Objects[0]->MessageList;
		return $response;
	}
	
	private function testService021()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetPagesInfoService.class.php';
		$req = $this->getRecordedRequest021();
		$recResp = $this->getRecordedResponse021();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#021');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '021' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '021' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetPagesInfo response.');
		}
		return !$this->hasError();
	}

	private function getRecordedRequest021()
	{
		$request = new WflGetPagesInfoRequest();
		$request->Ticket = $this->ticket;
		$request->Issue = new Issue();
		$request->Issue->Id = $this->issueObj->Id;
		$request->Issue->Name = $this->issueObj->Name;
		$request->Issue->OverrulePublication = 'false';
		$request->IDs = null;
		$request->Edition = new Edition();
		$request->Edition->Id = $this->editions[1]->Id;
		$request->Edition->Name = $this->editions[1]->Name;
		return $request;
	}
	
	private function getRecordedResponse021()
	{
		$response = new WflGetPagesInfoResponse();
		$response->ReversedReadingOrder = false;
		$response->ExpectedPages = 32;
		$response->PageOrderMethod = 'PageOrdered';
		$response->EditionsPages = array();
		$response->EditionsPages[0] = new EditionPages();
		$response->EditionsPages[0]->Edition = new Edition();
		$response->EditionsPages[0]->Edition->Id = $this->editions[1]->Id;
		$response->EditionsPages[0]->Edition->Name = $this->editions[1]->Name;
		$response->EditionsPages[0]->PageObjects = array();
		$response->EditionsPages[0]->PageObjects[0] = new PageObject();
		$response->EditionsPages[0]->PageObjects[0]->IssuePagePosition = 1;
		$response->EditionsPages[0]->PageObjects[0]->PageOrder = 1;
		$response->EditionsPages[0]->PageObjects[0]->PageNumber = '1';
		$response->EditionsPages[0]->PageObjects[0]->PageSequence = 1;
		$response->EditionsPages[0]->PageObjects[0]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[0]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[0]->ParentLayoutId = $this->objectIds['Layouts'][2];
		$response->EditionsPages[0]->PageObjects[0]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Id = $this->objectIds['Articles'][2];
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Left = 137;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Top = 668;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Width = 307;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos[0]->Height = 99;
		$response->EditionsPages[0]->PageObjects[0]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[0]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[0]->ppn->RealPageNumber = 1;
		$response->EditionsPages[0]->PageObjects[0]->ppn->DisplayPageNumber = '1';
		$response->EditionsPages[0]->PageObjects[0]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[0]->ppn->SortOrder = 3000000;
		$response->EditionsPages[0]->PageObjects[1] = new PageObject();
		$response->EditionsPages[0]->PageObjects[1]->IssuePagePosition = 2;
		$response->EditionsPages[0]->PageObjects[1]->PageOrder = 2;
		$response->EditionsPages[0]->PageObjects[1]->PageNumber = '2';
		$response->EditionsPages[0]->PageObjects[1]->PageSequence = 2;
		$response->EditionsPages[0]->PageObjects[1]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[1]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[1]->ParentLayoutId = $this->objectIds['Layouts'][2];
		$response->EditionsPages[0]->PageObjects[1]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos[0] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos[0]->Id = $this->objectIds['Articles'][2];
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos[0]->Left = 36;
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos[0]->Top = 146;
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos[0]->Width = 523;
		$response->EditionsPages[0]->PageObjects[1]->PlacementInfos[0]->Height = 659;
		$response->EditionsPages[0]->PageObjects[1]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[1]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[1]->ppn->RealPageNumber = 2;
		$response->EditionsPages[0]->PageObjects[1]->ppn->DisplayPageNumber = '2';
		$response->EditionsPages[0]->PageObjects[1]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[1]->ppn->SortOrder = 3000000;
		$response->EditionsPages[0]->PageObjects[2] = new PageObject();
		$response->EditionsPages[0]->PageObjects[2]->IssuePagePosition = 3;
		$response->EditionsPages[0]->PageObjects[2]->PageOrder = 1;
		$response->EditionsPages[0]->PageObjects[2]->PageNumber = '1';
		$response->EditionsPages[0]->PageObjects[2]->PageSequence = 1;
		$response->EditionsPages[0]->PageObjects[2]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[2]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[2]->ParentLayoutId = $this->objectIds['Layouts'][0];
		$response->EditionsPages[0]->PageObjects[2]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Id = $this->objectIds['Articles'][0];
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Left = 120;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Top = 102;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Width = 199;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[0]->Height = 48;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Id = $this->objectIds['Articles'][0];
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Left = 120;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Top = 170;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Width = 199;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[1]->Height = 43;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2] = new PlacementInfo();
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Id = $this->objectIds['Articles'][0];
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Left = 120;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Top = 233;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Width = 199;
		$response->EditionsPages[0]->PageObjects[2]->PlacementInfos[2]->Height = 39;
		$response->EditionsPages[0]->PageObjects[2]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[2]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[2]->ppn->RealPageNumber = 1;
		$response->EditionsPages[0]->PageObjects[2]->ppn->DisplayPageNumber = '1';
		$response->EditionsPages[0]->PageObjects[2]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[2]->ppn->SortOrder = 3000000;
		$response->EditionsPages[0]->PageObjects[3] = new PageObject();
		$response->EditionsPages[0]->PageObjects[3]->IssuePagePosition = 4;
		$response->EditionsPages[0]->PageObjects[3]->PageOrder = 2;
		$response->EditionsPages[0]->PageObjects[3]->PageNumber = '2';
		$response->EditionsPages[0]->PageObjects[3]->PageSequence = 2;
		$response->EditionsPages[0]->PageObjects[3]->Height = 841.889764;
		$response->EditionsPages[0]->PageObjects[3]->Width = 595.275591;
		$response->EditionsPages[0]->PageObjects[3]->ParentLayoutId = $this->objectIds['Layouts'][0];
		$response->EditionsPages[0]->PageObjects[3]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[3]->PlacementInfos = array();
		$response->EditionsPages[0]->PageObjects[3]->ppn = new PageNumInfo();
		$response->EditionsPages[0]->PageObjects[3]->ppn->NumberingSystem = 'arabic';
		$response->EditionsPages[0]->PageObjects[3]->ppn->RealPageNumber = 1;
		$response->EditionsPages[0]->PageObjects[3]->ppn->DisplayPageNumber = '1';
		$response->EditionsPages[0]->PageObjects[3]->ppn->PagePrefix = '';
		$response->EditionsPages[0]->PageObjects[3]->ppn->SortOrder = 3000000;
		$response->LayoutObjects = array();
		$response->LayoutObjects[0] = new LayoutObject();
		$response->LayoutObjects[0]->Id = $this->objectIds['Layouts'][2];
		$response->LayoutObjects[0]->Name = 'PubOverview_BuildTest_Layout_3';
		$response->LayoutObjects[0]->Category = new Category();
		$response->LayoutObjects[0]->Category->Id = $this->category->Id;
		$response->LayoutObjects[0]->Category->Name = $this->category->Name;
		$response->LayoutObjects[0]->State = new State();
		$response->LayoutObjects[0]->State->Id = $this->statuses['Layout']->Id;
		$response->LayoutObjects[0]->State->Name = $this->statuses['Layout']->Name;
		$response->LayoutObjects[0]->State->Type = 'Layout';
		$response->LayoutObjects[0]->State->Produce = null;
		$response->LayoutObjects[0]->State->Color = $this->statuses['Layout']->Color;
		$response->LayoutObjects[0]->State->DefaultRouteTo = null;
		$response->LayoutObjects[0]->Version = '0.2';
		$response->LayoutObjects[0]->LockedBy = '';
		$response->LayoutObjects[0]->Modified = '2013-12-04T15:16:30';
		$response->LayoutObjects[1] = new LayoutObject();
		$response->LayoutObjects[1]->Id = $this->objectIds['Layouts'][0];
		$response->LayoutObjects[1]->Name = 'PubOverview_BuildTest_Layout_1';
		$response->LayoutObjects[1]->Category = new Category();
		$response->LayoutObjects[1]->Category->Id = $this->category->Id;
		$response->LayoutObjects[1]->Category->Name = $this->category->Name;
		$response->LayoutObjects[1]->State = new State();
		$response->LayoutObjects[1]->State->Id = $this->statuses['Layout']->Id;
		$response->LayoutObjects[1]->State->Name = $this->statuses['Layout']->Name;
		$response->LayoutObjects[1]->State->Type = 'Layout';
		$response->LayoutObjects[1]->State->Produce = null;
		$response->LayoutObjects[1]->State->Color = $this->statuses['Layout']->Color;
		$response->LayoutObjects[1]->State->DefaultRouteTo = null;
		$response->LayoutObjects[1]->Version = '0.2';
		$response->LayoutObjects[1]->LockedBy = '';
		$response->LayoutObjects[1]->Modified = '2013-12-04T15:15:44';
		$response->PlacedObjects = array();
		$response->PlacedObjects[0] = new PlacedObject();
		$response->PlacedObjects[0]->Id = $this->objectIds['Articles'][0];
		$response->PlacedObjects[0]->Name = 'PubOverview_BuildTest_Article_1';
		$response->PlacedObjects[0]->Type = 'Article';
		$response->PlacedObjects[0]->State = new State();
		$response->PlacedObjects[0]->State->Id = $this->statuses['Article']->Id;
		$response->PlacedObjects[0]->State->Name = $this->statuses['Article']->Name;
		$response->PlacedObjects[0]->State->Type = 'Article';
		$response->PlacedObjects[0]->State->Produce = null;
		$response->PlacedObjects[0]->State->Color = $this->statuses['Article']->Color;
		$response->PlacedObjects[0]->State->DefaultRouteTo = null;
		$response->PlacedObjects[0]->Version = '0.1';
		$response->PlacedObjects[0]->LockedBy = '';
		$response->PlacedObjects[0]->Format = 'application/incopyicml';
		$response->PlacedObjects[1] = new PlacedObject();
		$response->PlacedObjects[1]->Id = $this->objectIds['Articles'][2];
		$response->PlacedObjects[1]->Name = 'PubOverview_BuildTest_Article_3';
		$response->PlacedObjects[1]->Type = 'Article';
		$response->PlacedObjects[1]->State = new State();
		$response->PlacedObjects[1]->State->Id = $this->statuses['Article']->Id;
		$response->PlacedObjects[1]->State->Name = $this->statuses['Article']->Name;
		$response->PlacedObjects[1]->State->Type = 'Article';
		$response->PlacedObjects[1]->State->Produce = null;
		$response->PlacedObjects[1]->State->Color = $this->statuses['Article']->Color;
		$response->PlacedObjects[1]->State->DefaultRouteTo = null;
		$response->PlacedObjects[1]->Version = '0.1';
		$response->PlacedObjects[1]->LockedBy = '';
		$response->PlacedObjects[1]->Format = 'application/incopyicml';
		return $response;
	}
	
	private function testService022()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetPagesService.class.php';
		$req = $this->getRecordedRequest022();
		$recResp = $this->getRecordedResponse022();
		$curResp = $this->wflServicesUtils->callService( $req, 'testService#022');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$ignorePaths = array(
			'WflGetPagesResponse->ObjectPageInfos[2]->MessageList->Messages[0]->MessageStatus' => true,
			'WflGetPagesResponse->ObjectPageInfos[2]->MessageList->Messages[0]->ThreadMessageID' => true,
			'WflGetPagesResponse->ObjectPageInfos[2]->MessageList->Messages[0]->ReplyToMessageID' => true,
		);
		$phpCompare->initCompare( $ignorePaths, $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '022' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '022' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetPages response.');
		}
		return !$this->hasError();
	}

	private function getRecordedRequest022()
	{
		$request = new WflGetPagesRequest();
		$request->Ticket = $this->ticket;
		$request->Params = null;
		$request->IDs = array();	
		$request->IDs[0] = $this->objectIds['Layouts'][0];
		$request->IDs[1] = $this->objectIds['Layouts'][1];
		$request->IDs[2] = $this->objectIds['Layouts'][2];
		$request->PageOrders = null;
		$request->PageSequences = null;
		$request->Edition = new Edition();
		$request->Edition->Id = $this->editions[1]->Id;
		$request->Edition->Name = $this->editions[1]->Name;
		$request->Renditions = array();
		$request->Renditions[0] = 'thumb';
		$request->RequestMetaData = null;
		$request->RequestFiles = null;
		
		return $request;
	}
	
	private function getRecordedResponse022()
	{
		$response = new WflGetPagesResponse();
		$response->ObjectPageInfos = array();
		$response->ObjectPageInfos[0] = new ObjectPageInfo();
		$response->ObjectPageInfos[0]->MetaData = new MetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][0];
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:B8BAF61FB35CE3119B98DBF03AC804DA';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_1';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->StoreName = '41/4101';
		$response->ObjectPageInfos[0]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Urgency = null;
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modifier = WW_Utils_TestSuiteOptions::getUser();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modified = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Comment = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Color = $this->statuses['Layout']->Color;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Version = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[0]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[0]->Pages = array();
		$response->ObjectPageInfos[0]->Pages[0] = new Page();
		$response->ObjectPageInfos[0]->Pages[0]->Width = 595.275591;
		$response->ObjectPageInfos[0]->Pages[0]->Height = 841.889764;
		$response->ObjectPageInfos[0]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[0]->Pages[0]->PageOrder = 1;
		$response->ObjectPageInfos[0]->Pages[0]->Files = array();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#022_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[0]->Pages[0]->Files[0] );
		$response->ObjectPageInfos[0]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[0]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[0]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[0]->Pages[0]->PageSequence = 1;
		$response->ObjectPageInfos[0]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[0]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[0]->Pages[1] = new Page();
		$response->ObjectPageInfos[0]->Pages[1]->Width = 595.275591;
		$response->ObjectPageInfos[0]->Pages[1]->Height = 841.889764;
		$response->ObjectPageInfos[0]->Pages[1]->PageNumber = '2';
		$response->ObjectPageInfos[0]->Pages[1]->PageOrder = 2;
		$response->ObjectPageInfos[0]->Pages[1]->Files = array();
		$response->ObjectPageInfos[0]->Pages[1]->Files[0] = new Attachment();
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->Content = null;
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[0]->Pages[1]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#022_att#001_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[0]->Pages[1]->Files[0] );
		$response->ObjectPageInfos[0]->Pages[1]->Edition = null;
		$response->ObjectPageInfos[0]->Pages[1]->Master = 'Master';
		$response->ObjectPageInfos[0]->Pages[1]->Instance = 'Production';
		$response->ObjectPageInfos[0]->Pages[1]->PageSequence = 2;
		$response->ObjectPageInfos[0]->Pages[1]->Renditions = array();
		$response->ObjectPageInfos[0]->Pages[1]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[0]->Pages[1]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[0]->Pages[1]->Orientation = '';
		$response->ObjectPageInfos[0]->Messages = null;
		$response->ObjectPageInfos[0]->MessageList = null;
		$response->ObjectPageInfos[1] = new ObjectPageInfo();
		$response->ObjectPageInfos[1]->MetaData = new MetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][1];
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->DocumentID = 'xmp.did:BDBAF61FB35CE3119B98DBF03AC804DA';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_2';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->StoreName = '41/4102';
		$response->ObjectPageInfos[1]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Urgency = null;
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modifier = WW_Utils_TestSuiteOptions::getUser();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modified = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Comment = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Color = $this->statuses['Layout']->Color;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Version = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[1]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[1]->Pages = array();
		$response->ObjectPageInfos[1]->Pages[0] = new Page();
		$response->ObjectPageInfos[1]->Pages[0]->Width = 595.275591;
		$response->ObjectPageInfos[1]->Pages[0]->Height = 841.889764;
		$response->ObjectPageInfos[1]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[1]->Pages[0]->PageOrder = 1;
		$response->ObjectPageInfos[1]->Pages[0]->Files = array();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#022_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[1]->Pages[0]->Files[0] );
		$response->ObjectPageInfos[1]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[1]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[1]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[1]->Pages[0]->PageSequence = 1;
		$response->ObjectPageInfos[1]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[1]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[1]->Pages[1] = new Page();
		$response->ObjectPageInfos[1]->Pages[1]->Width = 595.275591;
		$response->ObjectPageInfos[1]->Pages[1]->Height = 841.889764;
		$response->ObjectPageInfos[1]->Pages[1]->PageNumber = '2';
		$response->ObjectPageInfos[1]->Pages[1]->PageOrder = 2;
		$response->ObjectPageInfos[1]->Pages[1]->Files = array();
		$response->ObjectPageInfos[1]->Pages[1]->Files[0] = new Attachment();
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->Content = null;
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[1]->Pages[1]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#022_att#003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[1]->Pages[1]->Files[0] );
		$response->ObjectPageInfos[1]->Pages[1]->Edition = null;
		$response->ObjectPageInfos[1]->Pages[1]->Master = 'Master';
		$response->ObjectPageInfos[1]->Pages[1]->Instance = 'Production';
		$response->ObjectPageInfos[1]->Pages[1]->PageSequence = 2;
		$response->ObjectPageInfos[1]->Pages[1]->Renditions = array();
		$response->ObjectPageInfos[1]->Pages[1]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[1]->Pages[1]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[1]->Pages[1]->Orientation = '';
		$response->ObjectPageInfos[1]->Messages = null;
		$response->ObjectPageInfos[1]->MessageList = null;
		$response->ObjectPageInfos[2] = new ObjectPageInfo();
		$response->ObjectPageInfos[2]->MetaData = new MetaData();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][2];
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->DocumentID = 'xmp.did:C2BAF61FB35CE3119B98DBF03AC804DA';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Name = 'PubOverview_BuildTest_Layout_3';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[2]->MetaData->BasicMetaData->StoreName = '41/4103';
		$response->ObjectPageInfos[2]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[2]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[2]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Urgency = null;
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Modifier = WW_Utils_TestSuiteOptions::getUser();
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Modified = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Comment = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Id = $this->statuses['Layout']->Id;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Name = $this->statuses['Layout']->Name;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->Color = $this->statuses['Layout']->Color;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Version = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[2]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[2]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[2]->Pages = array();
		$response->ObjectPageInfos[2]->Pages[0] = new Page();
		$response->ObjectPageInfos[2]->Pages[0]->Width = 595.275591;
		$response->ObjectPageInfos[2]->Pages[0]->Height = 841.889764;
		$response->ObjectPageInfos[2]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[2]->Pages[0]->PageOrder = 1;
		$response->ObjectPageInfos[2]->Pages[0]->Files = array();
		$response->ObjectPageInfos[2]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[2]->Pages[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#022_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[2]->Pages[0]->Files[0] );
		$response->ObjectPageInfos[2]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[2]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[2]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[2]->Pages[0]->PageSequence = 1;
		$response->ObjectPageInfos[2]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[2]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[2]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[2]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[2]->Pages[1] = new Page();
		$response->ObjectPageInfos[2]->Pages[1]->Width = 595.275591;
		$response->ObjectPageInfos[2]->Pages[1]->Height = 841.889764;
		$response->ObjectPageInfos[2]->Pages[1]->PageNumber = '2';
		$response->ObjectPageInfos[2]->Pages[1]->PageOrder = 2;
		$response->ObjectPageInfos[2]->Pages[1]->Files = array();
		$response->ObjectPageInfos[2]->Pages[1]->Files[0] = new Attachment();
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->Content = null;
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[2]->Pages[1]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/WflPublicationOverview_TestData/rec#022_att#005_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->ObjectPageInfos[2]->Pages[1]->Files[0] );
		$response->ObjectPageInfos[2]->Pages[1]->Edition = null;
		$response->ObjectPageInfos[2]->Pages[1]->Master = 'Master';
		$response->ObjectPageInfos[2]->Pages[1]->Instance = 'Production';
		$response->ObjectPageInfos[2]->Pages[1]->PageSequence = 2;
		$response->ObjectPageInfos[2]->Pages[1]->Renditions = array();
		$response->ObjectPageInfos[2]->Pages[1]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[2]->Pages[1]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[2]->Pages[1]->Orientation = '';
		$response->ObjectPageInfos[2]->Messages = null;
		$response->ObjectPageInfos[2]->MessageList = $this->testService017_saveResp->Objects[0]->MessageList;
		return $response;
	}

	private function getRecordedRequest023()
	{
		// Collect all used object ids.
		$objectIds = array();
		foreach( $this->objectIds as $objTypeIds ) {
			$objectIds = array_merge( $objectIds, $objTypeIds );
		}
		
		// Compose request.
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $objectIds;
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->Context = null;
		return $request;
	}
	
	private function getRecordedResponse023()
	{
		// Collect all used object ids.
		$objectIds = array();
		foreach( $this->objectIds as $objTypeIds ) {
			$objectIds = array_merge( $objectIds, $objTypeIds );
		}

		// Compose response.
		$response = new WflDeleteObjectsResponse();
		$response->IDs = $objectIds;
		$response->Reports = array();
		return $response;
	}

	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true, 
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true
		);
	}

	/**
	 * Resolves Brand/Issue/Pubchannel/Category/Editions and statuses for Article/Layout/Image objects.
	 *
	 * @return bool Whether or not all admin entities under the brand are configured as assumed/expected.
	 */
	private function resolveBrandSetup()
	{
   		$vars = $this->getSessionVariables();
   		
   		// Check if the TESTSUITE['Brand'] could be found in the brand setup.
		$this->pubObj = @$vars['BuildTest_WebServices_WflServices']['publication'];
		if( !$this->pubObj ) {
			$this->setResult( 'ERROR', 'Could not find the test Brand named "'.$this->suiteOpts['Brand'].'". '. 
								'Please check the TESTSUITE setting in the configserver.php file.' );
			return false;
		}
		
		// Lookup the pub channel in the brand setup under which the TESTSUITE['Issue'] is setup.
		$otherIssueName = @$vars['BuildTest_WebServices_WflServices']['issue'];
		if( $this->pubObj->PubChannels ) foreach( $this->pubObj->PubChannels as $pubChannelInfo ) {
			if( $pubChannelInfo->Type == 'print' ) {
				if( $pubChannelInfo->Issues ) foreach( $pubChannelInfo->Issues as $issueInfo ) {
					if( $issueInfo->Name == $otherIssueName ) {
						$this->pubChannelObj = $pubChannelInfo;
						$this->editions = $pubChannelInfo->Editions;
						break;
					}
				}
			}
		}
		
		// Error when the channel could not be found, or when it has less than two editions.
		$pleaseCheck = 'Please check the configuration under the test Brand named "'.$this->pubObj->Name.'". '.
						'Also check the TESTSUITE option in the configserver.php file. '.
						'The TESTSUITE options should correspond with your actual brand setup. ';
		if( !$this->pubChannelObj ) {
			$this->setResult( 'ERROR', 'Could not find TESTSUITE Issue "'.$otherIssueName.'". '.$pleaseCheck );
			return false;
		}
		if( !$this->editions || count($this->editions) < 2 ) {
			$this->setResult( 'ERROR', 'Could find TESTSUITE Issue named "'.$otherIssueName.'", '.
								'but its publication channel should have at least two editions configured. '.$pleaseCheck );
			return false;
		}
		
		// Lookup status configurations for Article/Layout/Image objects in the brand setup.
		if( $this->pubObj->States ) foreach( $this->pubObj->States as $status ) {
			if( $status->Id != -1 ) { // prefer non-personal status
				switch( $status->Type ) {
					case 'Article':
					case 'Layout':
					case 'Image':
						$this->statuses[$status->Type] = $status;
						break;
				}
			}
		}
		
		// Error when any of the status configurations could not be found.
		foreach( array( 'Article', 'Layout', 'Image' ) as $objType ) {
			if( !isset($this->statuses[$objType]) ) {
				$this->setResult( 'ERROR', 'No statuses configured for '.$objType.' objects. '.$pleaseCheck );
				return false;
			}
		}
		
		// 
		$this->category = count( $this->pubObj->Categories ) > 0  ? $this->pubObj->Categories[0] : null;
		if( !$this->category ) {
			$this->setResult( 'ERROR', 'Could find the test Brand named "'.$this->suiteOpts['Brand'].'", '. 
								'but there are no categories configured for that brand. '.$pleaseCheck );
			return false;
		}

		return true;
	}

	/**
 	 * Creates an issue under the print channel of the TESTSUITE Brand to test with.
	 *
	 * @return bool Whether or not the setup was successful.
	 */
	private function setupTestData()
	{
		$issueName = 'PubOvrVwIss_' . date('dmy_his');
		$stepInfo = 'Creating issue "'.$issueName.'" under the print channel of the TESTSUITE brand.';
		$this->issueObj = $this->wflServicesUtils->createIssue( $stepInfo, 
									$this->pubObj->Id, $this->pubChannelObj->Id, $issueName );
		return true;
	}


	/**
	 * Tears down the stuff created in the {@link: setupTestData()} function.
	 * Also removes the objects created within all the workflow service calls.
	 */
	private function tearDownTestData()
	{
		// Remove the created objects.
		if( $this->objectIds ) {
			require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$req = $this->getRecordedRequest023();
			$recResp = $this->getRecordedResponse023();
			$curResp = $this->wflServicesUtils->callService( $req, 'testService#023');
	
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteObjects response.');
			}
			$this->objectIds = null;
		}
		
		// Remove the test issue.
		if( $this->issueObj ) {
			$stepInfo = 'Removing issue "'.$this->issueObj->Name.'" under the print channel of the TESTSUITE brand.';
			$this->wflServicesUtils->deleteIssue( $stepInfo, $this->pubObj->Id, $this->issueObj->Id );
		}
		$this->issueObj = null;
	}	
}
