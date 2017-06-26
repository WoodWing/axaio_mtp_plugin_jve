<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.1.3
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_IDSPreview_TestCase extends TestCase
{
	private $globalUtils = null;
	private $transferServer = null;
	private $ticket = null;
	private $user = null;

	// General properties
	private $pubObj = null;
	private $pubChannelObj = null;
	private $issueObj = null;
	private $editionObjs = array();
	private $layoutStatus = null;
	private $layoutStatusInResp = null;
	private $articleStatus = null;
	private $imageStatus = null;
	private $dossierStatus = null;
	private $categoryObj = null;

	// Properties used for the tests.
	private $objectIds = array(); // array( 'Dossiers' => ids, 'Layouts' => ids, 'Articles' => ids, 'Images' => ids )
	private $workspaceId = null;
	private $indesignArticleId = null;
	
	public function getDisplayName() { return 'IDS Preview with placements on the layout.'; }
	public function getTestGoals()   { return 'To make sure that all article and image placements are returned and they are valid.'; }
	public function getTestMethods() { return 'Scenario:<ol>
		<li>001: Create a new dossier. (WflCreateObjects)</li>
		<li>002: Create image1 in this dossier which be will placed on a layout. (WflCreateObjects)</li>
		<li>003: Create image2 in this dossier which be will placed on a layout. (WflCreateObjects)</li>
		<li>004: Create image3 in this dossier which be will placed on a layout. (WflCreateObjects)</li>
		<li>005: Create a new layout with InDesign Article that contains 1 article and 3 graphic frames. (WflCreateObjects)</li>
		<li>006: Create an article. (WflCreateObjects)</li>
		<li>007: Save the layout. (WflSaveObjects)</li>
		<li>008: Unlock the layout - Checkin layout. (WflUnlockObjects)</li>
		<li>009: Place images and article onto the layout. (ObjectOperations)</li>
		<li>010: Call jobindex.php to run indesign server. (cronjob - idsjobindex.php)</li>
		<li>011: Open article in Content Station. (WflGetObjects)</li>
		<li>012: Create article workspace. (WflCreateArticleWorkspace)</li>
		<li>013: Preview article in Content Station. (WflPreviewArticlesAtWorkspace)</li>
		<li>014: Checkin article in Content Station. (WflSaveObjects)</li>
		<li>015: Delete article workspace. (WflDeleteArticleWorkspace)</li>
		</ol>'; }
	public function getPrio()        { return 115; }
	
	final public function runTest()
	{
		try {
			$this->setupTestData();

			$this->testService001(); // WflCreateObjects
			$this->testService002(); // WflCreateObjects
			$this->testService003(); // WflCreateObjects
			$this->testService004(); // WflCreateObjects
			$this->testService005(); // WflCreateObjects
			$this->testService006(); // WflCreateObjects
			$this->testService007(); // WflSaveObjects
			$this->testService008(); // WflUnlockObjects
			$this->testService009(); // ObjectOperations
			$this->testService010(); // Jobindex.php
			$this->testService011(); // WflGetObjects
			$this->testService012(); // WflCreateArticleWorkspace
			$this->testService013(); // WflPreviewArticlesAtWorkspace
			$this->testService014(); // WflSaveObjects
			$this->testService015(); // WflDeleteArticleWorkspace
		} catch( BizException $e ) {
		}
		// Remove all the test data objects and the test issue.
		$this->tearDownTestData();

	}

	private function testService001()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest001();

		$stepInfo = 'testService#001:Creating the Dossier object.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$objId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $objId );
		$this->objectIds['Dossiers'][] = $objId;
	}

	private function getRecordedRequest001()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Dossier_IDSPreview_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Dossier';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = '';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 0;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->dossierStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = null;
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
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
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$request->Objects[0]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Objects[0]->Operations = null;
		$request->Messages = null;
		$request->AutoNaming = false;
		$request->ReplaceGUIDs = null;
		return $request;
	}
	
	private function testService002()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest002();

		$stepInfo = 'testService#002:Creating the Image object.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );
		$objId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;

		$this->assertGreaterThan( 0, $objId );
		$this->objectIds['Images'][] = $objId;
	
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Image_IDSPreview_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 132271;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Dossiers'][0];
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$request->Objects[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#002_att#000_native.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Objects[0]->Operations = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		$request->ReplaceGUIDs = null;
		return $request;
	}
	
	private function testService003()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest003();

		$stepInfo = 'testService#003:Creating the Image object.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$objId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $objId );
		$this->objectIds['Images'][] = $objId;
	}

	private function getRecordedRequest003()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Image_IDSPreview_2';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 424162;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Dossiers'][0];
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$request->Objects[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#003_att#001_native.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Objects[0]->Operations = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		$request->ReplaceGUIDs = null;
		return $request;
	}
	
	private function testService004()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest004();

		$stepInfo = 'testService#004:Creating the Image object.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$objId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $objId );
		$this->objectIds['Images'][] = $objId;
	}

	private function getRecordedRequest004()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Image_IDSPreview_3';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 16400;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Dossiers'][0];
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$request->Objects[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#004_att#002_native.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Objects[0]->Operations = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		$request->ReplaceGUIDs = null;
		return $request;
	}

	private function testService005()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest005();

		$stepInfo = 'testService#005:Creating the Layout object.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$objId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $objId );
		$this->objectIds['Layouts'][] = $objId;

		$this->indesignArticleId = $curResp->Objects[0]->InDesignArticles[0]->Id;
	}

	private function getRecordedRequest005()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:c594d7ff-efae-4781-9b22-e556805e5964';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Layout_IDSPreview_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1167360;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2017-06-07T15:27:28';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2017-06-07T15:27:28';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
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
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Dossiers'][0];
		$request->Objects[0]->Relations[0]->Child = '';
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 612;
		$request->Objects[0]->Pages[0]->Height = 792;
		$request->Objects[0]->Pages[0]->PageNumber = '2';
		$request->Objects[0]->Pages[0]->PageOrder = 2;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$request->Objects[0]->Pages[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#005_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
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
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$request->Objects[0]->Pages[1]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#005_att#001_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
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
		$request->Objects[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#005_att#002_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$request->Objects[0]->Files[1]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#005_att#003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
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
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$request->Objects[0]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = array();
		$request->Objects[0]->InDesignArticles[0] = new InDesignArticle();
		$request->Objects[0]->InDesignArticles[0]->Id = '253';
		$request->Objects[0]->InDesignArticles[0]->Name = 'Article 1';
		$request->Objects[0]->Placements = array();
		$request->Objects[0]->Placements[0] = new Placement();
		$request->Objects[0]->Placements[0]->Page = 3;
		$request->Objects[0]->Placements[0]->Element = 'body';
		$request->Objects[0]->Placements[0]->ElementID = '';
		$request->Objects[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Placements[0]->FrameID = '239';
		$request->Objects[0]->Placements[0]->Left = -237.6;
		$request->Objects[0]->Placements[0]->Top = 85.2;
		$request->Objects[0]->Placements[0]->Width = 484.8;
		$request->Objects[0]->Placements[0]->Height = 218.4;
		$request->Objects[0]->Placements[0]->Overset = 0;
		$request->Objects[0]->Placements[0]->OversetChars = 0;
		$request->Objects[0]->Placements[0]->OversetLines = -15;
		$request->Objects[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Placements[0]->Content = '';
		$request->Objects[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Placements[0]->PageSequence = 2;
		$request->Objects[0]->Placements[0]->PageNumber = '3';
		$request->Objects[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Placements[0]->Tiles[0] = new PlacementTile();
		$request->Objects[0]->Placements[0]->Tiles[0]->PageSequence = 1;
		$request->Objects[0]->Placements[0]->Tiles[0]->Left = 374.4;
		$request->Objects[0]->Placements[0]->Tiles[0]->Top = 85.2;
		$request->Objects[0]->Placements[0]->Tiles[0]->Width = 237.6;
		$request->Objects[0]->Placements[0]->Tiles[0]->Height = 218.4;
		$request->Objects[0]->Placements[0]->Tiles[1] = new PlacementTile();
		$request->Objects[0]->Placements[0]->Tiles[1]->PageSequence = 2;
		$request->Objects[0]->Placements[0]->Tiles[1]->Left = 0;
		$request->Objects[0]->Placements[0]->Tiles[1]->Top = 85.2;
		$request->Objects[0]->Placements[0]->Tiles[1]->Width = 247.2;
		$request->Objects[0]->Placements[0]->Tiles[1]->Height = 218.4;
		$request->Objects[0]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Placements[0]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Placements[0]->FrameType = 'text';
		$request->Objects[0]->Placements[0]->SplineID = '239';
		$request->Objects[0]->Placements[1] = new Placement();
		$request->Objects[0]->Placements[1]->Page = 2;
		$request->Objects[0]->Placements[1]->Element = 'graphic';
		$request->Objects[0]->Placements[1]->ElementID = '';
		$request->Objects[0]->Placements[1]->FrameOrder = 0;
		$request->Objects[0]->Placements[1]->FrameID = '250';
		$request->Objects[0]->Placements[1]->Left = 75.6;
		$request->Objects[0]->Placements[1]->Top = 96;
		$request->Objects[0]->Placements[1]->Width = 264;
		$request->Objects[0]->Placements[1]->Height = 207.6;
		$request->Objects[0]->Placements[1]->Overset = null;
		$request->Objects[0]->Placements[1]->OversetChars = null;
		$request->Objects[0]->Placements[1]->OversetLines = null;
		$request->Objects[0]->Placements[1]->Layer = 'Layer 1';
		$request->Objects[0]->Placements[1]->Content = '';
		$request->Objects[0]->Placements[1]->Edition = null;
		$request->Objects[0]->Placements[1]->ContentDx = null;
		$request->Objects[0]->Placements[1]->ContentDy = null;
		$request->Objects[0]->Placements[1]->ScaleX = null;
		$request->Objects[0]->Placements[1]->ScaleY = null;
		$request->Objects[0]->Placements[1]->PageSequence = 1;
		$request->Objects[0]->Placements[1]->PageNumber = '2';
		$request->Objects[0]->Placements[1]->Tiles = array();
		$request->Objects[0]->Placements[1]->FormWidgetId = null;
		$request->Objects[0]->Placements[1]->InDesignArticleIds = array();
		$request->Objects[0]->Placements[1]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Placements[1]->FrameType = 'graphic';
		$request->Objects[0]->Placements[1]->SplineID = '250';
		$request->Objects[0]->Placements[2] = new Placement();
		$request->Objects[0]->Placements[2]->Page = 3;
		$request->Objects[0]->Placements[2]->Element = 'graphic';
		$request->Objects[0]->Placements[2]->ElementID = '';
		$request->Objects[0]->Placements[2]->FrameOrder = 0;
		$request->Objects[0]->Placements[2]->FrameID = '251';
		$request->Objects[0]->Placements[2]->Left = -199.2;
		$request->Objects[0]->Placements[2]->Top = 396;
		$request->Objects[0]->Placements[2]->Width = 408;
		$request->Objects[0]->Placements[2]->Height = 267.6;
		$request->Objects[0]->Placements[2]->Overset = null;
		$request->Objects[0]->Placements[2]->OversetChars = null;
		$request->Objects[0]->Placements[2]->OversetLines = null;
		$request->Objects[0]->Placements[2]->Layer = 'Layer 1';
		$request->Objects[0]->Placements[2]->Content = '';
		$request->Objects[0]->Placements[2]->Edition = null;
		$request->Objects[0]->Placements[2]->ContentDx = null;
		$request->Objects[0]->Placements[2]->ContentDy = null;
		$request->Objects[0]->Placements[2]->ScaleX = null;
		$request->Objects[0]->Placements[2]->ScaleY = null;
		$request->Objects[0]->Placements[2]->PageSequence = 2;
		$request->Objects[0]->Placements[2]->PageNumber = '3';
		$request->Objects[0]->Placements[2]->Tiles = array();
		$request->Objects[0]->Placements[2]->Tiles[0] = new PlacementTile();
		$request->Objects[0]->Placements[2]->Tiles[0]->PageSequence = 1;
		$request->Objects[0]->Placements[2]->Tiles[0]->Left = 412.8;
		$request->Objects[0]->Placements[2]->Tiles[0]->Top = 396;
		$request->Objects[0]->Placements[2]->Tiles[0]->Width = 199.2;
		$request->Objects[0]->Placements[2]->Tiles[0]->Height = 267.6;
		$request->Objects[0]->Placements[2]->Tiles[1] = new PlacementTile();
		$request->Objects[0]->Placements[2]->Tiles[1]->PageSequence = 2;
		$request->Objects[0]->Placements[2]->Tiles[1]->Left = 0;
		$request->Objects[0]->Placements[2]->Tiles[1]->Top = 396;
		$request->Objects[0]->Placements[2]->Tiles[1]->Width = 208.8;
		$request->Objects[0]->Placements[2]->Tiles[1]->Height = 267.6;
		$request->Objects[0]->Placements[2]->FormWidgetId = null;
		$request->Objects[0]->Placements[2]->InDesignArticleIds = array();
		$request->Objects[0]->Placements[2]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Placements[2]->FrameType = 'graphic';
		$request->Objects[0]->Placements[2]->SplineID = '251';
		$request->Objects[0]->Placements[3] = new Placement();
		$request->Objects[0]->Placements[3]->Page = 3;
		$request->Objects[0]->Placements[3]->Element = 'graphic';
		$request->Objects[0]->Placements[3]->ElementID = '';
		$request->Objects[0]->Placements[3]->FrameOrder = 0;
		$request->Objects[0]->Placements[3]->FrameID = '252';
		$request->Objects[0]->Placements[3]->Left = 276;
		$request->Objects[0]->Placements[3]->Top = 85.2;
		$request->Objects[0]->Placements[3]->Width = 204;
		$request->Objects[0]->Placements[3]->Height = 218.4;
		$request->Objects[0]->Placements[3]->Overset = null;
		$request->Objects[0]->Placements[3]->OversetChars = null;
		$request->Objects[0]->Placements[3]->OversetLines = null;
		$request->Objects[0]->Placements[3]->Layer = 'Layer 1';
		$request->Objects[0]->Placements[3]->Content = '';
		$request->Objects[0]->Placements[3]->Edition = null;
		$request->Objects[0]->Placements[3]->ContentDx = null;
		$request->Objects[0]->Placements[3]->ContentDy = null;
		$request->Objects[0]->Placements[3]->ScaleX = null;
		$request->Objects[0]->Placements[3]->ScaleY = null;
		$request->Objects[0]->Placements[3]->PageSequence = 2;
		$request->Objects[0]->Placements[3]->PageNumber = '3';
		$request->Objects[0]->Placements[3]->Tiles = array();
		$request->Objects[0]->Placements[3]->FormWidgetId = null;
		$request->Objects[0]->Placements[3]->InDesignArticleIds = array();
		$request->Objects[0]->Placements[3]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Placements[3]->FrameType = 'graphic';
		$request->Objects[0]->Placements[3]->SplineID = '252';
		$request->Objects[0]->Operations = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		$request->ReplaceGUIDs = null;
		return $request;
	}

	private function testService006()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest006();

		$stepInfo = 'testService#006:Creating the Article object.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$objId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $objId );
		$this->objectIds['Articles'][] = $objId;
	}

	private function getRecordedRequest006()
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
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Article_IDSPreview_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Nequisciam inciumquis endaerchit exera volupie nestem essit in nemquam, offic tota con num aut occusa nossed mi, quo volor asitatisquam atius voluptia sam incipsam, cum repudae nem et acearchic to enducimus dolorib usantia turissi ntibus ut lautempor';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 1;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 484.8;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 218.4;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 220;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 1476;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 1;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 15;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Nequisciam inciumquis endaerchit exera volupie nestem essit in nemquam, offic tota con num aut occusa nossed mi, quo volor asitatisquam atius voluptia sam incipsam, cum repudae nem et acearchic to enducimus dolorib usantia turissi ntibus ut lautempor am evelit aut lit as ad qui volesequas a autesti a voluptaquia dit dolorro blaut quam, voluptae dolendu citatur ad quidipietus rest, id molupta tiossim incium quam, cus, sit videstemqui dolorpor si ducimagnat quiassi taquis aut est, aut licit, in cullenisquas volor as volendios ea volum evero offic tem quamus corem non natus aliat licias molentota pro quo incto qui reni dus deratus similiquas quo voluptatae vendit doluptibus dolore sinvene ctiumquias destio dicipistet perci volesti officient, coribus et re nonsentur se aut est aut quo te velestota autenit faceperum se secupti sae inctur rempor re nit aut volupta voluptis reptur maximpostio beriorehento totatibusam corentem eum quas sitem simagnis ipidus earum dolut qui alita del exceatur moditaecepe nonse cumquatatium facepro rissectur autem evellab oreprat omnimagnam quatus sitis nam reriossit estiis et acea dere, venimus, voluptat autatam aut inumquia volore omnis abo. Et im eum, sum laborer ioreribus, cora conectionem necte quaerio mo ellacese di omnisit, que restio. Harum rate voluptas elit, odisimet ullecae ped ut verfero dolestest, ommo blantem quatendis nonecum volupta essitiamus simincte omnimus quid molenia dolum re volorpori aut et poriam eventis';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 57187;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2017-06-07T15:27:32';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2017-06-07T15:27:32';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->articleStatus;
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
		$request->Objects[0]->Relations[0]->Parent = $this->objectIds['Dossiers'][0];
		$request->Objects[0]->Relations[0]->Child = '';
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->Targets[0] = new Target();
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Relations[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Relations[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$request->Objects[0]->Relations[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Relations[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Relations[1] = new Relation();
		$request->Objects[0]->Relations[1]->Parent = $this->objectIds['Layouts'][0];
		$request->Objects[0]->Relations[1]->Child = '';
		$request->Objects[0]->Relations[1]->Type = 'Placed';
		$request->Objects[0]->Relations[1]->Placements = array();
		$request->Objects[0]->Relations[1]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[1]->Placements[0]->Page = 3;
		$request->Objects[0]->Relations[1]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[1]->Placements[0]->ElementID = 'FBA64985-970D-4AF5-842F-179E826F5508';
		$request->Objects[0]->Relations[1]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->FrameID = '239';
		$request->Objects[0]->Relations[1]->Placements[0]->Left = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->Top = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->Width = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->Height = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->Overset = -18.962086;
		$request->Objects[0]->Relations[1]->Placements[0]->OversetChars = -3;
		$request->Objects[0]->Relations[1]->Placements[0]->OversetLines = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[1]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[1]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[1]->Placements[0]->PageSequence = 2;
		$request->Objects[0]->Relations[1]->Placements[0]->PageNumber = '3';
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[1]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[1]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[1]->Placements[0]->FrameType = 'text';
		$request->Objects[0]->Relations[1]->Placements[0]->SplineID = '239';
		$request->Objects[0]->Relations[1]->ParentVersion = null;
		$request->Objects[0]->Relations[1]->ChildVersion = null;
		$request->Objects[0]->Relations[1]->Geometry = null;
		$request->Objects[0]->Relations[1]->Rating = null;
		$request->Objects[0]->Relations[1]->Targets = array();
		$request->Objects[0]->Relations[1]->Targets[0] = new Target();
		$request->Objects[0]->Relations[1]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Relations[1]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Relations[1]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Relations[1]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Relations[1]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Relations[1]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Relations[1]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Relations[1]->Targets[0]->Editions = array();
		$request->Objects[0]->Relations[1]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Relations[1]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$request->Objects[0]->Relations[1]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$request->Objects[0]->Relations[1]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Relations[1]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$request->Objects[0]->Relations[1]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$request->Objects[0]->Relations[1]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Relations[1]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Relations[1]->ParentInfo = null;
		$request->Objects[0]->Relations[1]->ChildInfo = null;
		$request->Objects[0]->Relations[1]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$request->Objects[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#006_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'FBA64985-970D-4AF5-842F-179E826F5508';
		$request->Objects[0]->Elements[0]->Name = 'body';
		$request->Objects[0]->Elements[0]->LengthWords = 220;
		$request->Objects[0]->Elements[0]->LengthChars = 1476;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 15;
		$request->Objects[0]->Elements[0]->Snippet = 'Nequisciam inciumquis endaerchit exera volupie nestem essit in nemquam, offic tota con num aut occusa nossed mi, quo volor asitatisquam atius voluptia sam incipsam, cum repudae nem et acearchic to enducimus dolorib usantia turissi ntibus ut lautempor';
		$request->Objects[0]->Elements[0]->Version = '02DE90FE-D3DC-4C74-B5CC-ACFE20466228';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Objects[0]->Operations = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		$request->ReplaceGUIDs = null;
		return $request;
	}

	private function testService007()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getRecordedRequest007();

		// Compose expected response from recordings and validate against actual response.
		$expResp = $this->getRecordedResponse007();
		$this->globalUtils->sortObjectDataForCompare( $expResp->Objects[0] );

		$stepInfo = 'testService#007:Saving the layout.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );
		// Server does not guarantee order for certain object data items, so we sort here.
		$this->globalUtils->sortObjectDataForCompare( $curResp->Objects[0] );

		$id = @$curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		$this->validateRoundtrip(
			$expResp->Objects[0]->Relations, $curResp->Objects[0]->Relations,
			$expResp, $curResp,
			'Objects[0]->Relations', 'SaveObjects for Layout' );

		$this->validateRoundtrip(
			$expResp->Objects[0]->InDesignArticles, $curResp->Objects[0]->InDesignArticles,
			$expResp, $curResp,
			'Objects[0]->InDesignArticles', 'SaveObjects for Layout' );

		$this->validateRoundtrip(
			$expResp->Objects[0]->Placements, $curResp->Objects[0]->Placements,
			$expResp, $curResp,
			'Objects[0]->Placements', 'SaveObjects for Layout' );
	}

	private function getRecordedRequest007()
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
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:c594d7ff-efae-4781-9b22-e556805e5964';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Layout_IDSPreview_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1634304;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
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
		$request->Objects[0]->Relations[0]->Child = $this->objectIds['Images'][0];
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 2;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'graphic';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = '';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '258';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 75.6;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 96;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 264;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 207.6;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = null;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = null;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = null;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = -10;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = 15.3;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '2';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[0]->Placements[0]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameType = 'graphic';
		$request->Objects[0]->Relations[0]->Placements[0]->SplineID = '250';
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Relations[1] = new Relation();
		$request->Objects[0]->Relations[1]->Parent = $this->objectIds['Layouts'][0];
		$request->Objects[0]->Relations[1]->Child = $this->objectIds['Images'][1];
		$request->Objects[0]->Relations[1]->Type = 'Placed';
		$request->Objects[0]->Relations[1]->Placements = array();
		$request->Objects[0]->Relations[1]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[1]->Placements[0]->Page = 3;
		$request->Objects[0]->Relations[1]->Placements[0]->Element = 'graphic';
		$request->Objects[0]->Relations[1]->Placements[0]->ElementID = '';
		$request->Objects[0]->Relations[1]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->FrameID = '266';
		$request->Objects[0]->Relations[1]->Placements[0]->Left = -199.2;
		$request->Objects[0]->Relations[1]->Placements[0]->Top = 396;
		$request->Objects[0]->Relations[1]->Placements[0]->Width = 408;
		$request->Objects[0]->Relations[1]->Placements[0]->Height = 267.6;
		$request->Objects[0]->Relations[1]->Placements[0]->Overset = null;
		$request->Objects[0]->Relations[1]->Placements[0]->OversetChars = null;
		$request->Objects[0]->Relations[1]->Placements[0]->OversetLines = null;
		$request->Objects[0]->Relations[1]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[1]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[1]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[1]->Placements[0]->ContentDx = -26.4;
		$request->Objects[0]->Relations[1]->Placements[0]->ContentDy = -10.2;
		$request->Objects[0]->Relations[1]->Placements[0]->ScaleX = 1;
		$request->Objects[0]->Relations[1]->Placements[0]->ScaleY = 1;
		$request->Objects[0]->Relations[1]->Placements[0]->PageSequence = 2;
		$request->Objects[0]->Relations[1]->Placements[0]->PageNumber = '3';
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[0] = new PlacementTile();
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->PageSequence = 1;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Left = 412.8;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Top = 396;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Width = 199.2;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Height = 267.6;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[1] = new PlacementTile();
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->PageSequence = 2;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Left = 0;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Top = 396;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Width = 208.8;
		$request->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Height = 267.6;
		$request->Objects[0]->Relations[1]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[1]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[1]->Placements[0]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Relations[1]->Placements[0]->FrameType = 'graphic';
		$request->Objects[0]->Relations[1]->Placements[0]->SplineID = '251';
		$request->Objects[0]->Relations[1]->ParentVersion = null;
		$request->Objects[0]->Relations[1]->ChildVersion = null;
		$request->Objects[0]->Relations[1]->Geometry = null;
		$request->Objects[0]->Relations[1]->Rating = null;
		$request->Objects[0]->Relations[1]->Targets = null;
		$request->Objects[0]->Relations[1]->ParentInfo = null;
		$request->Objects[0]->Relations[1]->ChildInfo = null;
		$request->Objects[0]->Relations[1]->ObjectLabels = null;
		$request->Objects[0]->Relations[2] = new Relation();
		$request->Objects[0]->Relations[2]->Parent = $this->objectIds['Layouts'][0];
		$request->Objects[0]->Relations[2]->Child = $this->objectIds['Images'][2];
		$request->Objects[0]->Relations[2]->Type = 'Placed';
		$request->Objects[0]->Relations[2]->Placements = array();
		$request->Objects[0]->Relations[2]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[2]->Placements[0]->Page = 3;
		$request->Objects[0]->Relations[2]->Placements[0]->Element = 'graphic';
		$request->Objects[0]->Relations[2]->Placements[0]->ElementID = '';
		$request->Objects[0]->Relations[2]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->FrameID = '275';
		$request->Objects[0]->Relations[2]->Placements[0]->Left = 276;
		$request->Objects[0]->Relations[2]->Placements[0]->Top = 85.2;
		$request->Objects[0]->Relations[2]->Placements[0]->Width = 204;
		$request->Objects[0]->Relations[2]->Placements[0]->Height = 218.4;
		$request->Objects[0]->Relations[2]->Placements[0]->Overset = null;
		$request->Objects[0]->Relations[2]->Placements[0]->OversetChars = null;
		$request->Objects[0]->Relations[2]->Placements[0]->OversetLines = null;
		$request->Objects[0]->Relations[2]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[2]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[2]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[2]->Placements[0]->ContentDx = -618;
		$request->Objects[0]->Relations[2]->Placements[0]->ContentDy = -295.8;
		$request->Objects[0]->Relations[2]->Placements[0]->ScaleX = 1;
		$request->Objects[0]->Relations[2]->Placements[0]->ScaleY = 1;
		$request->Objects[0]->Relations[2]->Placements[0]->PageSequence = 2;
		$request->Objects[0]->Relations[2]->Placements[0]->PageNumber = '3';
		$request->Objects[0]->Relations[2]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[2]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[2]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[2]->Placements[0]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Relations[2]->Placements[0]->FrameType = 'graphic';
		$request->Objects[0]->Relations[2]->Placements[0]->SplineID = '252';
		$request->Objects[0]->Relations[2]->ParentVersion = null;
		$request->Objects[0]->Relations[2]->ChildVersion = null;
		$request->Objects[0]->Relations[2]->Geometry = null;
		$request->Objects[0]->Relations[2]->Rating = null;
		$request->Objects[0]->Relations[2]->Targets = null;
		$request->Objects[0]->Relations[2]->ParentInfo = null;
		$request->Objects[0]->Relations[2]->ChildInfo = null;
		$request->Objects[0]->Relations[2]->ObjectLabels = null;
		$request->Objects[0]->Relations[3] = new Relation();
		$request->Objects[0]->Relations[3]->Parent = $this->objectIds['Layouts'][0];
		$request->Objects[0]->Relations[3]->Child = $this->objectIds['Articles'][0];
		$request->Objects[0]->Relations[3]->Type = 'Placed';
		$request->Objects[0]->Relations[3]->Placements = array();
		$request->Objects[0]->Relations[3]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[3]->Placements[0]->Page = 3;
		$request->Objects[0]->Relations[3]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[3]->Placements[0]->ElementID = 'FBA64985-970D-4AF5-842F-179E826F5508';
		$request->Objects[0]->Relations[3]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[3]->Placements[0]->FrameID = '239';
		$request->Objects[0]->Relations[3]->Placements[0]->Left = -237.6;
		$request->Objects[0]->Relations[3]->Placements[0]->Top = 85.2;
		$request->Objects[0]->Relations[3]->Placements[0]->Width = 484.8;
		$request->Objects[0]->Relations[3]->Placements[0]->Height = 218.4;
		$request->Objects[0]->Relations[3]->Placements[0]->Overset = -18.962086;
		$request->Objects[0]->Relations[3]->Placements[0]->OversetChars = -3;
		$request->Objects[0]->Relations[3]->Placements[0]->OversetLines = 0;
		$request->Objects[0]->Relations[3]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[3]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[3]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[3]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[3]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[3]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[3]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[3]->Placements[0]->PageSequence = 2;
		$request->Objects[0]->Relations[3]->Placements[0]->PageNumber = '3';
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[0] = new PlacementTile();
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->PageSequence = 1;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Left = 374.4;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Top = 85.2;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Width = 237.6;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Height = 218.4;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[1] = new PlacementTile();
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->PageSequence = 2;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Left = 0;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Top = 85.2;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Width = 247.2;
		$request->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Height = 218.4;
		$request->Objects[0]->Relations[3]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[3]->Placements[0]->InDesignArticleIds = array();
		$request->Objects[0]->Relations[3]->Placements[0]->InDesignArticleIds[0] = '253';
		$request->Objects[0]->Relations[3]->Placements[0]->FrameType = 'text';
		$request->Objects[0]->Relations[3]->Placements[0]->SplineID = '239';
		$request->Objects[0]->Relations[3]->ParentVersion = null;
		$request->Objects[0]->Relations[3]->ChildVersion = null;
		$request->Objects[0]->Relations[3]->Geometry = null;
		$request->Objects[0]->Relations[3]->Rating = null;
		$request->Objects[0]->Relations[3]->Targets = null;
		$request->Objects[0]->Relations[3]->ParentInfo = null;
		$request->Objects[0]->Relations[3]->ChildInfo = null;
		$request->Objects[0]->Relations[3]->ObjectLabels = null;
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 612;
		$request->Objects[0]->Pages[0]->Height = 792;
		$request->Objects[0]->Pages[0]->PageNumber = '2';
		$request->Objects[0]->Pages[0]->PageOrder = 2;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$request->Objects[0]->Pages[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#007_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
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
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$request->Objects[0]->Pages[1]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#007_att#001_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
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
		$request->Objects[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#007_att#002_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$request->Objects[0]->Files[1]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#007_att#003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
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
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$request->Objects[0]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = array();
		$request->Objects[0]->InDesignArticles[0] = new InDesignArticle();
		$request->Objects[0]->InDesignArticles[0]->Id = '253';
		$request->Objects[0]->InDesignArticles[0]->Name = 'Article 1';
		$request->Objects[0]->Placements = array();
		$request->Objects[0]->Operations = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}

	private function getRecordedResponse007()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->objectIds['Layouts'][0];
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:c594d7ff-efae-4781-9b22-e556805e5964';
		$response->Objects[0]->MetaData->BasicMetaData->Name = 'Layout_IDSPreview_1';
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
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
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '1634304';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = '';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$response->Objects[0]->MetaData->ContentMetaData->Dimensions = null;
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2017-06-07T15:30:57';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2017-06-07T15:28:29';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatusInResp;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = $this->user;
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.2';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = $this->objectIds['Dossiers'][0];
		$response->Objects[0]->Relations[0]->Child = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[0]->Type = 'Contained';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.2';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = new Target();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$response->Objects[0]->Relations[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[0]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = $this->objectIds['Dossiers'][0];
		$response->Objects[0]->Relations[0]->ParentInfo->Name = 'Dossier_IDSPreview_1';
		$response->Objects[0]->Relations[0]->ParentInfo->Type = 'Dossier';
		$response->Objects[0]->Relations[0]->ParentInfo->Format = '';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[0]->ChildInfo->Name = 'Layout_IDSPreview_1';
		$response->Objects[0]->Relations[0]->ChildInfo->Type = 'Layout';
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Relations[1] = new Relation();
		$response->Objects[0]->Relations[1]->Parent = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[1]->Child = $this->objectIds['Articles'][0];
		$response->Objects[0]->Relations[1]->Type = 'Placed';
		$response->Objects[0]->Relations[1]->Placements = array();
		$response->Objects[0]->Relations[1]->Placements[0] = new Placement();
		$response->Objects[0]->Relations[1]->Placements[0]->Page = '3';
		$response->Objects[0]->Relations[1]->Placements[0]->Element = 'body';
		$response->Objects[0]->Relations[1]->Placements[0]->ElementID = 'FBA64985-970D-4AF5-842F-179E826F5508';
		$response->Objects[0]->Relations[1]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Relations[1]->Placements[0]->FrameID = '239';
		$response->Objects[0]->Relations[1]->Placements[0]->Left = '-237.6';
		$response->Objects[0]->Relations[1]->Placements[0]->Top = '85.2';
		$response->Objects[0]->Relations[1]->Placements[0]->Width = '484.8';
		$response->Objects[0]->Relations[1]->Placements[0]->Height = '218.4';
		$response->Objects[0]->Relations[1]->Placements[0]->Overset = '-18.962086';
		$response->Objects[0]->Relations[1]->Placements[0]->OversetChars = '-3';
		$response->Objects[0]->Relations[1]->Placements[0]->OversetLines = '0';
		$response->Objects[0]->Relations[1]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[1]->Placements[0]->Content = '';
		$response->Objects[0]->Relations[1]->Placements[0]->Edition = null;
		$response->Objects[0]->Relations[1]->Placements[0]->ContentDx = '0';
		$response->Objects[0]->Relations[1]->Placements[0]->ContentDy = '0';
		$response->Objects[0]->Relations[1]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Relations[1]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Relations[1]->Placements[0]->PageSequence = '2';
		$response->Objects[0]->Relations[1]->Placements[0]->PageNumber = '3';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles = array();
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[0] = new PlacementTile();
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->PageSequence = '1';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Left = '374.4';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Top = '85.2';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Width = '237.6';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[0]->Height = '218.4';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[1] = new PlacementTile();
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->PageSequence = '2';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Left = '0';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Top = '85.2';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Width = '247.2';
		$response->Objects[0]->Relations[1]->Placements[0]->Tiles[1]->Height = '218.4';
		$response->Objects[0]->Relations[1]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Relations[1]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[1]->Placements[0]->FrameType = 'text';
		$response->Objects[0]->Relations[1]->Placements[0]->SplineID = '239';
		$response->Objects[0]->Relations[1]->ParentVersion = '0.2';
		$response->Objects[0]->Relations[1]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[1]->Geometry = null;
		$response->Objects[0]->Relations[1]->Rating = '0';
		$response->Objects[0]->Relations[1]->Targets = array();
		$response->Objects[0]->Relations[1]->Targets[0] = new Target();
		$response->Objects[0]->Relations[1]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[1]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$response->Objects[0]->Relations[1]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$response->Objects[0]->Relations[1]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[1]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$response->Objects[0]->Relations[1]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$response->Objects[0]->Relations[1]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[1]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[1]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[1]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$response->Objects[0]->Relations[1]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$response->Objects[0]->Relations[1]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[1]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$response->Objects[0]->Relations[1]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$response->Objects[0]->Relations[1]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[1]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[1]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[1]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[1]->ParentInfo->ID = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[1]->ParentInfo->Name = 'Layout_IDSPreview_1';
		$response->Objects[0]->Relations[1]->ParentInfo->Type = 'Layout';
		$response->Objects[0]->Relations[1]->ParentInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[1]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[1]->ChildInfo->ID = $this->objectIds['Articles'][0];
		$response->Objects[0]->Relations[1]->ChildInfo->Name = 'Article_IDSPreview_1';
		$response->Objects[0]->Relations[1]->ChildInfo->Type = 'Article';
		$response->Objects[0]->Relations[1]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[1]->ObjectLabels = null;
		$response->Objects[0]->Relations[2] = new Relation();
		$response->Objects[0]->Relations[2]->Parent = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[2]->Child = $this->objectIds['Images'][0];
		$response->Objects[0]->Relations[2]->Type = 'Placed';
		$response->Objects[0]->Relations[2]->Placements = array();
		$response->Objects[0]->Relations[2]->Placements[0] = new Placement();
		$response->Objects[0]->Relations[2]->Placements[0]->Page = '2';
		$response->Objects[0]->Relations[2]->Placements[0]->Element = 'graphic';
		$response->Objects[0]->Relations[2]->Placements[0]->ElementID = '';
		$response->Objects[0]->Relations[2]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Relations[2]->Placements[0]->FrameID = '258';
		$response->Objects[0]->Relations[2]->Placements[0]->Left = '75.6';
		$response->Objects[0]->Relations[2]->Placements[0]->Top = '96';
		$response->Objects[0]->Relations[2]->Placements[0]->Width = '264';
		$response->Objects[0]->Relations[2]->Placements[0]->Height = '207.6';
		$response->Objects[0]->Relations[2]->Placements[0]->Overset = '0';
		$response->Objects[0]->Relations[2]->Placements[0]->OversetChars = '0';
		$response->Objects[0]->Relations[2]->Placements[0]->OversetLines = '0';
		$response->Objects[0]->Relations[2]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[2]->Placements[0]->Content = '';
		$response->Objects[0]->Relations[2]->Placements[0]->Edition = null;
		$response->Objects[0]->Relations[2]->Placements[0]->ContentDx = '-10';
		$response->Objects[0]->Relations[2]->Placements[0]->ContentDy = '15.3';
		$response->Objects[0]->Relations[2]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Relations[2]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Relations[2]->Placements[0]->PageSequence = '1';
		$response->Objects[0]->Relations[2]->Placements[0]->PageNumber = '2';
		$response->Objects[0]->Relations[2]->Placements[0]->Tiles = array();
		$response->Objects[0]->Relations[2]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Relations[2]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[2]->Placements[0]->FrameType = 'graphic';
		$response->Objects[0]->Relations[2]->Placements[0]->SplineID = '250';
		$response->Objects[0]->Relations[2]->ParentVersion = '0.2';
		$response->Objects[0]->Relations[2]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[2]->Geometry = null;
		$response->Objects[0]->Relations[2]->Rating = '0';
		$response->Objects[0]->Relations[2]->Targets = array();
		$response->Objects[0]->Relations[2]->Targets[0] = new Target();
		$response->Objects[0]->Relations[2]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[2]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$response->Objects[0]->Relations[2]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$response->Objects[0]->Relations[2]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[2]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$response->Objects[0]->Relations[2]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$response->Objects[0]->Relations[2]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[2]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[2]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[2]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$response->Objects[0]->Relations[2]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$response->Objects[0]->Relations[2]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[2]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$response->Objects[0]->Relations[2]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$response->Objects[0]->Relations[2]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[2]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[2]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[2]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[2]->ParentInfo->ID = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[2]->ParentInfo->Name = 'Layout_IDSPreview_1';
		$response->Objects[0]->Relations[2]->ParentInfo->Type = 'Layout';
		$response->Objects[0]->Relations[2]->ParentInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[2]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[2]->ChildInfo->ID = $this->objectIds['Images'][0];
		$response->Objects[0]->Relations[2]->ChildInfo->Name = 'Image_IDSPreview_1';
		$response->Objects[0]->Relations[2]->ChildInfo->Type = 'Image';
		$response->Objects[0]->Relations[2]->ChildInfo->Format = 'image/jpeg';
		$response->Objects[0]->Relations[2]->ObjectLabels = null;
		$response->Objects[0]->Relations[3] = new Relation();
		$response->Objects[0]->Relations[3]->Parent = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[3]->Child = $this->objectIds['Images'][1];
		$response->Objects[0]->Relations[3]->Type = 'Placed';
		$response->Objects[0]->Relations[3]->Placements = array();
		$response->Objects[0]->Relations[3]->Placements[0] = new Placement();
		$response->Objects[0]->Relations[3]->Placements[0]->Page = '3';
		$response->Objects[0]->Relations[3]->Placements[0]->Element = 'graphic';
		$response->Objects[0]->Relations[3]->Placements[0]->ElementID = '';
		$response->Objects[0]->Relations[3]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Relations[3]->Placements[0]->FrameID = '266';
		$response->Objects[0]->Relations[3]->Placements[0]->Left = '-199.2';
		$response->Objects[0]->Relations[3]->Placements[0]->Top = '396';
		$response->Objects[0]->Relations[3]->Placements[0]->Width = '408';
		$response->Objects[0]->Relations[3]->Placements[0]->Height = '267.6';
		$response->Objects[0]->Relations[3]->Placements[0]->Overset = '0';
		$response->Objects[0]->Relations[3]->Placements[0]->OversetChars = '0';
		$response->Objects[0]->Relations[3]->Placements[0]->OversetLines = '0';
		$response->Objects[0]->Relations[3]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[3]->Placements[0]->Content = '';
		$response->Objects[0]->Relations[3]->Placements[0]->Edition = null;
		$response->Objects[0]->Relations[3]->Placements[0]->ContentDx = '-26.4';
		$response->Objects[0]->Relations[3]->Placements[0]->ContentDy = '-10.2';
		$response->Objects[0]->Relations[3]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Relations[3]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Relations[3]->Placements[0]->PageSequence = '2';
		$response->Objects[0]->Relations[3]->Placements[0]->PageNumber = '3';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles = array();
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[0] = new PlacementTile();
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->PageSequence = '1';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Left = '412.8';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Top = '396';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Width = '199.2';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[0]->Height = '267.6';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[1] = new PlacementTile();
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->PageSequence = '2';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Left = '0';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Top = '396';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Width = '208.8';
		$response->Objects[0]->Relations[3]->Placements[0]->Tiles[1]->Height = '267.6';
		$response->Objects[0]->Relations[3]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Relations[3]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[3]->Placements[0]->FrameType = 'graphic';
		$response->Objects[0]->Relations[3]->Placements[0]->SplineID = '251';
		$response->Objects[0]->Relations[3]->ParentVersion = '0.2';
		$response->Objects[0]->Relations[3]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[3]->Geometry = null;
		$response->Objects[0]->Relations[3]->Rating = '0';
		$response->Objects[0]->Relations[3]->Targets = array();
		$response->Objects[0]->Relations[3]->Targets[0] = new Target();
		$response->Objects[0]->Relations[3]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[3]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$response->Objects[0]->Relations[3]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$response->Objects[0]->Relations[3]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[3]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$response->Objects[0]->Relations[3]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$response->Objects[0]->Relations[3]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[3]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[3]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[3]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$response->Objects[0]->Relations[3]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$response->Objects[0]->Relations[3]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[3]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$response->Objects[0]->Relations[3]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$response->Objects[0]->Relations[3]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[3]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[3]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[3]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[3]->ParentInfo->ID = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[3]->ParentInfo->Name = 'Layout_IDSPreview_1';
		$response->Objects[0]->Relations[3]->ParentInfo->Type = 'Layout';
		$response->Objects[0]->Relations[3]->ParentInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[3]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[3]->ChildInfo->ID = $this->objectIds['Images'][1];
		$response->Objects[0]->Relations[3]->ChildInfo->Name = 'Image_IDSPreview_2';
		$response->Objects[0]->Relations[3]->ChildInfo->Type = 'Image';
		$response->Objects[0]->Relations[3]->ChildInfo->Format = 'image/jpeg';
		$response->Objects[0]->Relations[3]->ObjectLabels = null;
		$response->Objects[0]->Relations[4] = new Relation();
		$response->Objects[0]->Relations[4]->Parent = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[4]->Child = $this->objectIds['Images'][2];
		$response->Objects[0]->Relations[4]->Type = 'Placed';
		$response->Objects[0]->Relations[4]->Placements = array();
		$response->Objects[0]->Relations[4]->Placements[0] = new Placement();
		$response->Objects[0]->Relations[4]->Placements[0]->Page = '3';
		$response->Objects[0]->Relations[4]->Placements[0]->Element = 'graphic';
		$response->Objects[0]->Relations[4]->Placements[0]->ElementID = '';
		$response->Objects[0]->Relations[4]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Relations[4]->Placements[0]->FrameID = '275';
		$response->Objects[0]->Relations[4]->Placements[0]->Left = '276';
		$response->Objects[0]->Relations[4]->Placements[0]->Top = '85.2';
		$response->Objects[0]->Relations[4]->Placements[0]->Width = '204';
		$response->Objects[0]->Relations[4]->Placements[0]->Height = '218.4';
		$response->Objects[0]->Relations[4]->Placements[0]->Overset = '0';
		$response->Objects[0]->Relations[4]->Placements[0]->OversetChars = '0';
		$response->Objects[0]->Relations[4]->Placements[0]->OversetLines = '0';
		$response->Objects[0]->Relations[4]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Relations[4]->Placements[0]->Content = '';
		$response->Objects[0]->Relations[4]->Placements[0]->Edition = null;
		$response->Objects[0]->Relations[4]->Placements[0]->ContentDx = '-618';
		$response->Objects[0]->Relations[4]->Placements[0]->ContentDy = '-295.8';
		$response->Objects[0]->Relations[4]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Relations[4]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Relations[4]->Placements[0]->PageSequence = '2';
		$response->Objects[0]->Relations[4]->Placements[0]->PageNumber = '3';
		$response->Objects[0]->Relations[4]->Placements[0]->Tiles = array();
		$response->Objects[0]->Relations[4]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Relations[4]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Relations[4]->Placements[0]->FrameType = 'graphic';
		$response->Objects[0]->Relations[4]->Placements[0]->SplineID = '252';
		$response->Objects[0]->Relations[4]->ParentVersion = '0.2';
		$response->Objects[0]->Relations[4]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[4]->Geometry = null;
		$response->Objects[0]->Relations[4]->Rating = '0';
		$response->Objects[0]->Relations[4]->Targets = array();
		$response->Objects[0]->Relations[4]->Targets[0] = new Target();
		$response->Objects[0]->Relations[4]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[4]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$response->Objects[0]->Relations[4]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$response->Objects[0]->Relations[4]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[4]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$response->Objects[0]->Relations[4]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$response->Objects[0]->Relations[4]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[4]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[4]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[4]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$response->Objects[0]->Relations[4]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$response->Objects[0]->Relations[4]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[4]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$response->Objects[0]->Relations[4]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$response->Objects[0]->Relations[4]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[4]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[4]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[4]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[4]->ParentInfo->ID = $this->objectIds['Layouts'][0];
		$response->Objects[0]->Relations[4]->ParentInfo->Name = 'Layout_IDSPreview_1';
		$response->Objects[0]->Relations[4]->ParentInfo->Type = 'Layout';
		$response->Objects[0]->Relations[4]->ParentInfo->Format = 'application/indesign';
		$response->Objects[0]->Relations[4]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[4]->ChildInfo->ID = $this->objectIds['Images'][2];
		$response->Objects[0]->Relations[4]->ChildInfo->Name = 'Image_IDSPreview_3';
		$response->Objects[0]->Relations[4]->ChildInfo->Type = 'Image';
		$response->Objects[0]->Relations[4]->ChildInfo->Format = 'image/jpeg';
		$response->Objects[0]->Relations[4]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Pages[0] = new Page();
		$response->Objects[0]->Pages[0]->Width = '612';
		$response->Objects[0]->Pages[0]->Height = '792';
		$response->Objects[0]->Pages[0]->PageNumber = '2';
		$response->Objects[0]->Pages[0]->PageOrder = '2';
		$response->Objects[0]->Pages[0]->Files = array();
		$response->Objects[0]->Pages[0]->Edition = null;
		$response->Objects[0]->Pages[0]->Master = 'Master';
		$response->Objects[0]->Pages[0]->Instance = 'Production';
		$response->Objects[0]->Pages[0]->PageSequence = '1';
		$response->Objects[0]->Pages[0]->Renditions = null;
		$response->Objects[0]->Pages[0]->Orientation = '';
		$response->Objects[0]->Pages[1] = new Page();
		$response->Objects[0]->Pages[1]->Width = '612';
		$response->Objects[0]->Pages[1]->Height = '792';
		$response->Objects[0]->Pages[1]->PageNumber = '3';
		$response->Objects[0]->Pages[1]->PageOrder = '3';
		$response->Objects[0]->Pages[1]->Files = array();
		$response->Objects[0]->Pages[1]->Edition = null;
		$response->Objects[0]->Pages[1]->Master = 'Master';
		$response->Objects[0]->Pages[1]->Instance = 'Production';
		$response->Objects[0]->Pages[1]->PageSequence = '2';
		$response->Objects[0]->Pages[1]->Renditions = null;
		$response->Objects[0]->Pages[1]->Orientation = '';
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = new Target();
		$response->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$response->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$response->Objects[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$response->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$response->Objects[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObjs[0]->Id;
		$response->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObjs[0]->Name;
		$response->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[1]->Id = $this->editionObjs[1]->Id;
		$response->Objects[0]->Targets[0]->Editions[1]->Name = $this->editionObjs[1]->Name;
		$response->Objects[0]->Targets[0]->PublishedDate = null;
		$response->Objects[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->InDesignArticles[0] = new InDesignArticle();
		$response->Objects[0]->InDesignArticles[0]->Id = '253';
		$response->Objects[0]->InDesignArticles[0]->Name = 'Article 1';
		$response->Objects[0]->Placements = array();
		$response->Objects[0]->Placements[0] = new Placement();
		$response->Objects[0]->Placements[0]->Page = '3';
		$response->Objects[0]->Placements[0]->Element = 'body';
		$response->Objects[0]->Placements[0]->ElementID = '';
		$response->Objects[0]->Placements[0]->FrameOrder = '0';
		$response->Objects[0]->Placements[0]->FrameID = '239';
		$response->Objects[0]->Placements[0]->Left = '-237.6';
		$response->Objects[0]->Placements[0]->Top = '85.2';
		$response->Objects[0]->Placements[0]->Width = '484.8';
		$response->Objects[0]->Placements[0]->Height = '218.4';
		$response->Objects[0]->Placements[0]->Overset = '-18.962086';
		$response->Objects[0]->Placements[0]->OversetChars = '-3';
		$response->Objects[0]->Placements[0]->OversetLines = '0';
		$response->Objects[0]->Placements[0]->Layer = 'Layer 1';
		$response->Objects[0]->Placements[0]->Content = '';
		$response->Objects[0]->Placements[0]->Edition = null;
		$response->Objects[0]->Placements[0]->ContentDx = '0';
		$response->Objects[0]->Placements[0]->ContentDy = '0';
		$response->Objects[0]->Placements[0]->ScaleX = '1';
		$response->Objects[0]->Placements[0]->ScaleY = '1';
		$response->Objects[0]->Placements[0]->PageSequence = '2';
		$response->Objects[0]->Placements[0]->PageNumber = '3';
		$response->Objects[0]->Placements[0]->Tiles = array();
		$response->Objects[0]->Placements[0]->FormWidgetId = '';
		$response->Objects[0]->Placements[0]->InDesignArticleIds = array();
		$response->Objects[0]->Placements[0]->InDesignArticleIds[0] = '253';
		$response->Objects[0]->Placements[0]->FrameType = 'text';
		$response->Objects[0]->Placements[0]->SplineID = '239';
		$response->Objects[0]->Placements[1] = new Placement();
		$response->Objects[0]->Placements[1]->Page = '2';
		$response->Objects[0]->Placements[1]->Element = 'graphic';
		$response->Objects[0]->Placements[1]->ElementID = '';
		$response->Objects[0]->Placements[1]->FrameOrder = '0';
		$response->Objects[0]->Placements[1]->FrameID = '258';
		$response->Objects[0]->Placements[1]->Left = '75.6';
		$response->Objects[0]->Placements[1]->Top = '96';
		$response->Objects[0]->Placements[1]->Width = '264';
		$response->Objects[0]->Placements[1]->Height = '207.6';
		$response->Objects[0]->Placements[1]->Overset = '0';
		$response->Objects[0]->Placements[1]->OversetChars = '0';
		$response->Objects[0]->Placements[1]->OversetLines = '0';
		$response->Objects[0]->Placements[1]->Layer = 'Layer 1';
		$response->Objects[0]->Placements[1]->Content = '';
		$response->Objects[0]->Placements[1]->Edition = null;
		$response->Objects[0]->Placements[1]->ContentDx = '-10';
		$response->Objects[0]->Placements[1]->ContentDy = '15.3';
		$response->Objects[0]->Placements[1]->ScaleX = '1';
		$response->Objects[0]->Placements[1]->ScaleY = '1';
		$response->Objects[0]->Placements[1]->PageSequence = '1';
		$response->Objects[0]->Placements[1]->PageNumber = '2';
		$response->Objects[0]->Placements[1]->Tiles = array();
		$response->Objects[0]->Placements[1]->FormWidgetId = '';
		$response->Objects[0]->Placements[1]->InDesignArticleIds = array();
		$response->Objects[0]->Placements[1]->InDesignArticleIds[0] = '253';
		$response->Objects[0]->Placements[1]->FrameType = 'graphic';
		$response->Objects[0]->Placements[1]->SplineID = '250';
		$response->Objects[0]->Placements[2] = new Placement();
		$response->Objects[0]->Placements[2]->Page = '3';
		$response->Objects[0]->Placements[2]->Element = 'graphic';
		$response->Objects[0]->Placements[2]->ElementID = '';
		$response->Objects[0]->Placements[2]->FrameOrder = '0';
		$response->Objects[0]->Placements[2]->FrameID = '266';
		$response->Objects[0]->Placements[2]->Left = '-199.2';
		$response->Objects[0]->Placements[2]->Top = '396';
		$response->Objects[0]->Placements[2]->Width = '408';
		$response->Objects[0]->Placements[2]->Height = '267.6';
		$response->Objects[0]->Placements[2]->Overset = '0';
		$response->Objects[0]->Placements[2]->OversetChars = '0';
		$response->Objects[0]->Placements[2]->OversetLines = '0';
		$response->Objects[0]->Placements[2]->Layer = 'Layer 1';
		$response->Objects[0]->Placements[2]->Content = '';
		$response->Objects[0]->Placements[2]->Edition = null;
		$response->Objects[0]->Placements[2]->ContentDx = '-26.4';
		$response->Objects[0]->Placements[2]->ContentDy = '-10.2';
		$response->Objects[0]->Placements[2]->ScaleX = '1';
		$response->Objects[0]->Placements[2]->ScaleY = '1';
		$response->Objects[0]->Placements[2]->PageSequence = '2';
		$response->Objects[0]->Placements[2]->PageNumber = '3';
		$response->Objects[0]->Placements[2]->Tiles = array();
		$response->Objects[0]->Placements[2]->FormWidgetId = '';
		$response->Objects[0]->Placements[2]->InDesignArticleIds = array();
		$response->Objects[0]->Placements[2]->InDesignArticleIds[0] = '253';
		$response->Objects[0]->Placements[2]->FrameType = 'graphic';
		$response->Objects[0]->Placements[2]->SplineID = '251';
		$response->Objects[0]->Placements[3] = new Placement();
		$response->Objects[0]->Placements[3]->Page = '3';
		$response->Objects[0]->Placements[3]->Element = 'graphic';
		$response->Objects[0]->Placements[3]->ElementID = '';
		$response->Objects[0]->Placements[3]->FrameOrder = '0';
		$response->Objects[0]->Placements[3]->FrameID = '275';
		$response->Objects[0]->Placements[3]->Left = '276';
		$response->Objects[0]->Placements[3]->Top = '85.2';
		$response->Objects[0]->Placements[3]->Width = '204';
		$response->Objects[0]->Placements[3]->Height = '218.4';
		$response->Objects[0]->Placements[3]->Overset = '0';
		$response->Objects[0]->Placements[3]->OversetChars = '0';
		$response->Objects[0]->Placements[3]->OversetLines = '0';
		$response->Objects[0]->Placements[3]->Layer = 'Layer 1';
		$response->Objects[0]->Placements[3]->Content = '';
		$response->Objects[0]->Placements[3]->Edition = null;
		$response->Objects[0]->Placements[3]->ContentDx = '-618';
		$response->Objects[0]->Placements[3]->ContentDy = '-295.8';
		$response->Objects[0]->Placements[3]->ScaleX = '1';
		$response->Objects[0]->Placements[3]->ScaleY = '1';
		$response->Objects[0]->Placements[3]->PageSequence = '2';
		$response->Objects[0]->Placements[3]->PageNumber = '3';
		$response->Objects[0]->Placements[3]->Tiles = array();
		$response->Objects[0]->Placements[3]->FormWidgetId = '';
		$response->Objects[0]->Placements[3]->InDesignArticleIds = array();
		$response->Objects[0]->Placements[3]->InDesignArticleIds[0] = '253';
		$response->Objects[0]->Placements[3]->FrameType = 'graphic';
		$response->Objects[0]->Placements[3]->SplineID = '252';
		$response->Objects[0]->Operations = null;
		$response->Reports = array();
		return $response;
	}

	private function testService008()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$req = $this->getRecordedRequest008();

		$stepInfo = 'testService#008:Unlocking the layout.';
		$this->globalUtils->callService( $this, $req, $stepInfo );
	
	}

	private function getRecordedRequest008()
	{
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objectIds['Layouts'][0];
		$request->ReadMessageIDs = null;
		$request->MessageList = null;
		return $request;
	}

	private function testService009()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectOperationsService.class.php';
		$req = $this->getRecordedRequest009();

		$stepInfo = 'testService009:Object Operations.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );
	}

	private function getRecordedRequest009()
	{
		$dossierId = $this->objectIds['Dossiers'][0];
		$layoutId = $this->objectIds['Layouts'][0];
		$images = $this->objectIds['Images'];
		$issueId = $this->issueObj->Id;
		$inDesignArticleId = $this->indesignArticleId;
		$editionId = 0; // All

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectOperationsService.class.php';
		$request = new WflCreateObjectOperationsRequest();
		$request->Ticket = $this->ticket;
		$request->HaveVersion = $this->composeObjectVersion( $layoutId );
		$request->Operations = $this->composePlacements( $inDesignArticleId, $issueId, $editionId, $dossierId, $layoutId, $images );

		return $request;
	}

	/**
	 * Compose and returns the ObjectVersion object.
	 *
	 * @param int $id
	 * @return ObjectVersion
	 */
	private function composeObjectVersion( $id )
	{
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		$objVersion = new ObjectVersion();
		$objVersion->ID = $id;
		$objVersion->Version = DBObject::getObjectVersion( $id );

		return $objVersion;
	}

	/**
	 * Compose list of object operations to be in the CreateObjectOperations request.
	 *
	 * @param string $inDesignArticleId
	 * @param integer $issueId
	 * @param integer $editionId
	 * @param integer $dossierId
	 * @param integer $layoutId
	 * @param integer[] $images
	 * @return ObjectOperation[]
	 */
	private function composePlacements( $inDesignArticleId, $issueId, $editionId, $dossierId, $layoutId, $images=array() )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
		$iaPlacementIds = DBInDesignArticlePlacement::getPlacementIdsByInDesignArticleId( $layoutId, $inDesignArticleId, $editionId );

		$resolvedOperations = array();
		if( $iaPlacementIds ) {
			require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
			$iaPlacements = DBPlacements::getPlacementBasicsByIds( $iaPlacementIds, true );

			// Always assume there's no duplicates. The idea is to just place the images on any graphic frame available.
			foreach( $iaPlacements as $iaPlacement ) {
				$iaPlacement->IsDuplicate = false;
			}

			if( $iaPlacements ) {
				require_once BASEDIR.'/server/plugins/AutomatedPrintWorkflow/AutomatedPrintWorkflow_AutomatedPrintWorkflow.class.php';
				$iaFrameLabels = array();
				$iaFrameTypes = array();
				$this->determineUsedFrameTypesAndLabels( $iaPlacements, $iaFrameLabels, $iaFrameTypes );

				$childTypes = array( 'Image', 'Article' );
				$invokedObjects = AutomatedPrintWorkflow_AutomatedPrintWorkflow::getDossierChildrenMetaData( $issueId, $editionId, $dossierId, $childTypes );
				$articleElements = AutomatedPrintWorkflow_AutomatedPrintWorkflow::getArticleElements( $invokedObjects, $iaFrameLabels );

				$elementsToClear = array(); // Assume there's no placements to clear, so just set it to empty array.
				if( $images ) {
					$imgCtr = 0;
					// It is assumed that total of IDArticle placements = total of article(only body) and image(s).
					if( $iaPlacements ) foreach( $iaPlacements as $iaPlacementId => $iaPlacement ) {
						$resolvedOperation = array();
						if( $iaPlacement->Element == 'graphic' ) { // Image placement
							$resolvedOperation = AutomatedPrintWorkflow_AutomatedPrintWorkflow::composeOperations(
								array( $iaPlacement ), $elementsToClear, $articleElements, $images[$imgCtr], $editionId );
							$imgCtr++;
						} else if( $iaPlacement->Element == 'body' ) { // Article placement
							$resolvedOperation = AutomatedPrintWorkflow_AutomatedPrintWorkflow::composeOperations(
								array( $iaPlacement ), $elementsToClear, $articleElements, null, $editionId );
						}
						$resolvedOperations = array_merge( $resolvedOperations, $resolvedOperation );
					}
				} else { // Only article frame/placement.
					$resolvedOperations = AutomatedPrintWorkflow_AutomatedPrintWorkflow::composeOperations(
						$iaPlacements, $elementsToClear, $articleElements, null, $editionId );
				}
			}
		}

		return $resolvedOperations;
	}

	/**
	 * Composes a collection with all possible frame types and label from the resolved IdArt frames.
	 *
	 * @param Placement[] $iaPlacements [input] Resolved IdArt frames.
	 * @param string[] $iaFrameLabels [output] All possible frame labels.
	 * @param string[] $iaFrameTypes [output] All possible frame types.
	 */
	private function determineUsedFrameTypesAndLabels( array $iaPlacements, array &$iaFrameLabels, array &$iaFrameTypes )
	{
		$iaFrameTypes = array();
		$iaFrameLabels = array();

		foreach( $iaPlacements as $iaPlacement ) {
			if( !$iaPlacement->IsDuplicate ) {
				$iaFrameTypes[$iaPlacement->FrameType] = true;
				$iaFrameLabels[$iaPlacement->Element] = true;
			}
		}

		$iaFrameTypes = array_keys( $iaFrameTypes );
		$iaFrameLabels = array_keys( $iaFrameLabels );
	}

	private function testService010()
	{
		$this->callRunServerJobs();
		sleep( 10 ); // To make sure that the server job is really ended.
	}

	/**
	 * Run the job scheduler by calling the jobindex.php.
	 *
	 * @param int $maxExecTime The max execution time of jobindex.php in seconds.
	 * @param int $maxJobProcesses The maximum number of jobs that the job processor is allowed to pick up at any one time.
	 */
	public function callRunServerJobs( $maxExecTime = 5, $maxJobProcesses = 3 )
	{
		try {
			require_once 'Zend/Http/Client.php';
			$url = LOCALURL_ROOT.INETROOT.'/idsjobindex.php';
			$client = new Zend_Http_Client();
			$client->setUri( $url );
			$client->setParameterGet( 'maxexectime', $maxExecTime );
			$client->setParameterGet( 'maxjobprocesses', $maxJobProcesses );
			$client->setConfig( array( 'timeout' => $maxExecTime + 30 ) ); // before breaking connection, let's give the job processor 30s more to complete
			$response = $client->request( Zend_Http_Client::GET );

			if( !$response->isSuccessful() ) {
				$this->setResult( 'ERROR', 'Failed calling jobindex.php: '.$response->getHeadersAsString( true, '<br/>' ) );
			}
		} catch ( Zend_Http_Client_Exception $e ) {
			$this->setResult( 'ERROR', 'Failed calling jobindex.php: '.$e->getMessage() );
		}
	}

	private function testService011()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = $this->getRecordedRequest011();

		$stepInfo = 'testService#011:Check out the article.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$id = @$curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );
	}

	private function getRecordedRequest011()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objectIds['Articles'][0];
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->RequestInfo = array();
		$request->RequestInfo[0] = 'Relations';
		$request->RequestInfo[1] = 'Messages';
		$request->RequestInfo[2] = 'Elements';
		$request->RequestInfo[3] = 'Targets';
		$request->RequestInfo[4] = 'ObjectLabels';
		$request->HaveVersions = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->EditionId = null;
		$request->SupportedContentSources = null;
		return $request;
	}
	
	private function testService012()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateArticleWorkspaceService.class.php';
		$req = $this->getRecordedRequest012();

		$stepInfo = 'testService#012:Creating Article Workspace.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );
		$this->workspaceId = $curResp->WorkspaceId;
		$this->assertNotNull( $this->workspaceId, 'WorkspaceId should not be null but a valid GUID.' );

		require_once BASEDIR .'/server/utils/NumberUtils.class.php';
		if( !NumberUtils::validateGUID( $this->workspaceId ) ) {
			$this->throwError( 'CreateArticleWorkSpace: WorkspaceId does not have a valid GUID.' );
		}
	}

	private function getRecordedRequest012()
	{
		$request = new WflCreateArticleWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $this->objectIds['Articles'][0];
		$request->Format = 'application/incopyicml';
		$request->Content = null;
		return $request;
	}
	
	private function testService013()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticlesAtWorkspaceService.class.php';
		$req = $this->getRecordedRequest013();
		$expResp = $this->getRecordedResponse013();

		$stepInfo = 'testService#013:Preview Articles At Workspace.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$ignorePaths = array(
			'WflPreviewArticlesAtWorkspaceResponse->Pages[0]->Files[0]->FileUrl' => true,
			'WflPreviewArticlesAtWorkspaceResponse->Pages[1]->Files[0]->FileUrl' => true,
		);
		$this->validateRoundtrip(
			$expResp, $curResp,
			$expResp, $curResp,
			'WflPreviewArticlesAtWorkspaceResponse', 'Preview Articles at Workspace',
			$ignorePaths );

	}

	private function getRecordedRequest013()
	{
		$request = new WflPreviewArticlesAtWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->WorkspaceId = $this->workspaceId;
		$request->Articles = array();
		$request->Articles[0] = new ArticleAtWorkspace();
		$request->Articles[0]->ID = $this->objectIds['Articles'][0];
		$request->Articles[0]->Format = 'application/incopyicml';
		$request->Articles[0]->Content = null;
		$request->Articles[0]->Elements = null;
		$request->Action = 'Preview';
		$request->LayoutId = $this->objectIds['Layouts'][0];
		$request->EditionId = $this->editionObjs[0]->Id;
		$request->PreviewType = 'spread';
		$request->RequestInfo = array();
		$request->RequestInfo[0] = 'Relations';
		$request->RequestInfo[1] = 'InDesignArticles';
		return $request;
	}

	private function getRecordedResponse013()
	{
		$response = new WflPreviewArticlesAtWorkspaceResponse();
		$response->Placements = array();
		$response->Placements[0] = new Placement();
		$response->Placements[0]->Page = null;
		$response->Placements[0]->Element = 'body';
		$response->Placements[0]->ElementID = 'FBA64985-970D-4AF5-842F-179E826F5508';
		$response->Placements[0]->FrameOrder = '0';
		$response->Placements[0]->FrameID = '239';
		$response->Placements[0]->Left = '-237.6';
		$response->Placements[0]->Top = '85.2';
		$response->Placements[0]->Width = '484.8';
		$response->Placements[0]->Height = '218.4';
		$response->Placements[0]->Overset = null;
		$response->Placements[0]->OversetChars = '0';
		$response->Placements[0]->OversetLines = 0;
		$response->Placements[0]->Layer = 'Layer 1';
		$response->Placements[0]->Content = null;
		$response->Placements[0]->Edition = null;
		$response->Placements[0]->ContentDx = null;
		$response->Placements[0]->ContentDy = null;
		$response->Placements[0]->ScaleX = null;
		$response->Placements[0]->ScaleY = null;
		$response->Placements[0]->PageSequence = '2';
		$response->Placements[0]->PageNumber = '3';
		$response->Placements[0]->Tiles = array();
		$response->Placements[0]->Tiles[0] = new PlacementTile();
		$response->Placements[0]->Tiles[0]->PageSequence = '1';
		$response->Placements[0]->Tiles[0]->Left = '374.4';
		$response->Placements[0]->Tiles[0]->Top = '85.2';
		$response->Placements[0]->Tiles[0]->Width = '237.6';
		$response->Placements[0]->Tiles[0]->Height = '218.4';
		$response->Placements[0]->Tiles[1] = new PlacementTile();
		$response->Placements[0]->Tiles[1]->PageSequence = '2';
		$response->Placements[0]->Tiles[1]->Left = '0';
		$response->Placements[0]->Tiles[1]->Top = '85.2';
		$response->Placements[0]->Tiles[1]->Width = '247.2';
		$response->Placements[0]->Tiles[1]->Height = '218.4';
		$response->Placements[0]->FormWidgetId = null;
		$response->Placements[0]->InDesignArticleIds = array();
		$response->Placements[0]->InDesignArticleIds[0] = '253';
		$response->Placements[0]->FrameType = 'text';
		$response->Placements[0]->SplineID = '239';
		$response->Elements = array();
		$response->Elements[0] = new Element();
		$response->Elements[0]->ID = 'FBA64985-970D-4AF5-842F-179E826F5508';
		$response->Elements[0]->Name = 'body';
		$response->Elements[0]->LengthWords = '220';
		$response->Elements[0]->LengthChars = '1476';
		$response->Elements[0]->LengthParas = '1';
		$response->Elements[0]->LengthLines = '15';
		$response->Elements[0]->Snippet = null;
		$response->Elements[0]->Version = null;
		$response->Elements[0]->Content = null;
		$response->Pages = array();
		$response->Pages[0] = new Page();
		$response->Pages[0]->Width = '612';
		$response->Pages[0]->Height = '792';
		$response->Pages[0]->PageNumber = '2';
		$response->Pages[0]->PageOrder = '2';
		$response->Pages[0]->Files = array();
		$response->Pages[0]->Files[0] = new Attachment();
		$response->Pages[0]->Files[0]->Rendition = 'preview';
		$response->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->Pages[0]->Files[0]->Content = null;
		$response->Pages[0]->Files[0]->FilePath = '';
		$response->Pages[0]->Files[0]->FileUrl = 'http://127.0.0.1:8006/Ent101x/previewindex.php?ticket=eccd6330uLYEKH6wAS6U2dKbLadqb7kleOp8jlBw&workspaceid=eda09593-1007-c55a-c6ce-497c8e2ab8d5&action=Preview&layoutid=180101201&editionid=1&pagesequence=1';
		$response->Pages[0]->Files[0]->EditionId = null;
		$response->Pages[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#013_att#000_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->Pages[0]->Files[0] );
		$response->Pages[0]->Edition = null;
		$response->Pages[0]->Master = '';
		$response->Pages[0]->Instance = 'Production';
		$response->Pages[0]->PageSequence = '1';
		$response->Pages[0]->Renditions = null;
		$response->Pages[0]->Orientation = null;
		$response->Pages[1] = new Page();
		$response->Pages[1]->Width = '612';
		$response->Pages[1]->Height = '792';
		$response->Pages[1]->PageNumber = '3';
		$response->Pages[1]->PageOrder = '3';
		$response->Pages[1]->Files = array();
		$response->Pages[1]->Files[0] = new Attachment();
		$response->Pages[1]->Files[0]->Rendition = 'preview';
		$response->Pages[1]->Files[0]->Type = 'image/jpeg';
		$response->Pages[1]->Files[0]->Content = null;
		$response->Pages[1]->Files[0]->FilePath = '';
		$response->Pages[1]->Files[0]->FileUrl = 'http://127.0.0.1:8006/Ent101x/previewindex.php?ticket=eccd6330uLYEKH6wAS6U2dKbLadqb7kleOp8jlBw&workspaceid=eda09593-1007-c55a-c6ce-497c8e2ab8d5&action=Preview&layoutid=180101201&editionid=1&pagesequence=2';
		$response->Pages[1]->Files[0]->EditionId = null;
		$response->Pages[1]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#013_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->Pages[1]->Files[0] );
		$response->Pages[1]->Edition = null;
		$response->Pages[1]->Master = '';
		$response->Pages[1]->Instance = 'Production';
		$response->Pages[1]->PageSequence = '2';
		$response->Pages[1]->Renditions = null;
		$response->Pages[1]->Orientation = null;
		$response->LayoutVersion = '0.3';
		$response->InDesignArticles = array();
		$response->InDesignArticles[0] = new InDesignArticle();
		$response->InDesignArticles[0]->Id = '253';
		$response->InDesignArticles[0]->Name = 'Article 1';
		$response->Relations = array();
		$response->Relations[0] = new Relation();
		$response->Relations[0]->Parent = $this->objectIds['Layouts'][0];
		$response->Relations[0]->Child = $this->objectIds['Images'][0];
		$response->Relations[0]->Type = 'Placed';
		$response->Relations[0]->Placements = array();
		$response->Relations[0]->Placements[0] = new Placement();
		$response->Relations[0]->Placements[0]->Page = '2';
		$response->Relations[0]->Placements[0]->Element = 'graphic';
		$response->Relations[0]->Placements[0]->ElementID = '';
		$response->Relations[0]->Placements[0]->FrameOrder = '0';
		$response->Relations[0]->Placements[0]->FrameID = '334';
		$response->Relations[0]->Placements[0]->Left = '75.600000';
		$response->Relations[0]->Placements[0]->Top = '96.000000';
		$response->Relations[0]->Placements[0]->Width = '264.000000';
		$response->Relations[0]->Placements[0]->Height = '207.600000';
		$response->Relations[0]->Placements[0]->Overset = null;
		$response->Relations[0]->Placements[0]->OversetChars = null;
		$response->Relations[0]->Placements[0]->OversetLines = null;
		$response->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$response->Relations[0]->Placements[0]->Content = '';
		$response->Relations[0]->Placements[0]->Edition = null;
		$response->Relations[0]->Placements[0]->ContentDx = '0.000000';
		$response->Relations[0]->Placements[0]->ContentDy = '29.550000';
		$response->Relations[0]->Placements[0]->ScaleX = '0.183333';
		$response->Relations[0]->Placements[0]->ScaleY = '0.183333';
		$response->Relations[0]->Placements[0]->PageSequence = '1';
		$response->Relations[0]->Placements[0]->PageNumber = '2';
		$response->Relations[0]->Placements[0]->Tiles = array();
		$response->Relations[0]->Placements[0]->FormWidgetId = null;
		$response->Relations[0]->Placements[0]->InDesignArticleIds = array();
		$response->Relations[0]->Placements[0]->InDesignArticleIds[0] = '253';
		$response->Relations[0]->Placements[0]->FrameType = 'graphic';
		$response->Relations[0]->Placements[0]->SplineID = '250';
		$response->Relations[0]->ParentVersion = null;
		$response->Relations[0]->ChildVersion = null;
		$response->Relations[0]->Geometry = null;
		$response->Relations[0]->Rating = null;
		$response->Relations[0]->Targets = null;
		$response->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Relations[0]->ParentInfo->ID = $this->objectIds['Layouts'][0];
		$response->Relations[0]->ParentInfo->Name = 'Layout_IDSPreview_1';
		$response->Relations[0]->ParentInfo->Type = 'Layout';
		$response->Relations[0]->ParentInfo->Format = 'application/indesign';
		$response->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Relations[0]->ChildInfo->ID = $this->objectIds['Images'][0];
		$response->Relations[0]->ChildInfo->Name = 'Image_IDSPreview_1';
		$response->Relations[0]->ChildInfo->Type = 'Image';
		$response->Relations[0]->ChildInfo->Format = 'image/jpeg';
		$response->Relations[0]->ObjectLabels = null;
		$response->Relations[1] = new Relation();
		$response->Relations[1]->Parent = $this->objectIds['Layouts'][0];
		$response->Relations[1]->Child = $this->objectIds['Images'][1];
		$response->Relations[1]->Type = 'Placed';
		$response->Relations[1]->Placements = array();
		$response->Relations[1]->Placements[0] = new Placement();
		$response->Relations[1]->Placements[0]->Page = '3';
		$response->Relations[1]->Placements[0]->Element = 'graphic';
		$response->Relations[1]->Placements[0]->ElementID = '';
		$response->Relations[1]->Placements[0]->FrameOrder = '0';
		$response->Relations[1]->Placements[0]->FrameID = '343';
		$response->Relations[1]->Placements[0]->Left = '-199.200000';
		$response->Relations[1]->Placements[0]->Top = '396.000000';
		$response->Relations[1]->Placements[0]->Width = '408.000000';
		$response->Relations[1]->Placements[0]->Height = '267.600000';
		$response->Relations[1]->Placements[0]->Overset = null;
		$response->Relations[1]->Placements[0]->OversetChars = null;
		$response->Relations[1]->Placements[0]->OversetLines = null;
		$response->Relations[1]->Placements[0]->Layer = 'Layer 1';
		$response->Relations[1]->Placements[0]->Content = '';
		$response->Relations[1]->Placements[0]->Edition = null;
		$response->Relations[1]->Placements[0]->ContentDx = '-0.000000';
		$response->Relations[1]->Placements[0]->ContentDy = '6.300000';
		$response->Relations[1]->Placements[0]->ScaleX = '0.885417';
		$response->Relations[1]->Placements[0]->ScaleY = '0.885417';
		$response->Relations[1]->Placements[0]->PageSequence = '2';
		$response->Relations[1]->Placements[0]->PageNumber = '3';
		$response->Relations[1]->Placements[0]->Tiles = array();
//      EN-89220 STARTS: When this ticket is solved, the below needs to be uncommented.
//		$response->Relations[1]->Placements[0]->Tiles[0] = new PlacementTile();
//		$response->Relations[1]->Placements[0]->Tiles[0]->PageSequence = '1';
//		$response->Relations[1]->Placements[0]->Tiles[0]->Left = '412.800000';
//		$response->Relations[1]->Placements[0]->Tiles[0]->Top = '396.000000';
//		$response->Relations[1]->Placements[0]->Tiles[0]->Width = '199.200000';
//		$response->Relations[1]->Placements[0]->Tiles[0]->Height = '267.600000';
//		$response->Relations[1]->Placements[0]->Tiles[1] = new PlacementTile();
//		$response->Relations[1]->Placements[0]->Tiles[1]->PageSequence = '2';
//		$response->Relations[1]->Placements[0]->Tiles[1]->Left = '0.000000';
//		$response->Relations[1]->Placements[0]->Tiles[1]->Top = '396.000000';
//		$response->Relations[1]->Placements[0]->Tiles[1]->Width = '208.800000';
//		$response->Relations[1]->Placements[0]->Tiles[1]->Height = '267.600000';
//      EN-89220 ENDS
		$response->Relations[1]->Placements[0]->FormWidgetId = null;
		$response->Relations[1]->Placements[0]->InDesignArticleIds = array();
		$response->Relations[1]->Placements[0]->InDesignArticleIds[0] = '253';
		$response->Relations[1]->Placements[0]->FrameType = 'graphic';
		$response->Relations[1]->Placements[0]->SplineID = '251';
		$response->Relations[1]->ParentVersion = null;
		$response->Relations[1]->ChildVersion = null;
		$response->Relations[1]->Geometry = null;
		$response->Relations[1]->Rating = null;
		$response->Relations[1]->Targets = null;
		$response->Relations[1]->ParentInfo = new ObjectInfo();
		$response->Relations[1]->ParentInfo->ID = $this->objectIds['Layouts'][0];
		$response->Relations[1]->ParentInfo->Name = 'Layout_IDSPreview_1';
		$response->Relations[1]->ParentInfo->Type = 'Layout';
		$response->Relations[1]->ParentInfo->Format = 'application/indesign';
		$response->Relations[1]->ChildInfo = new ObjectInfo();
		$response->Relations[1]->ChildInfo->ID = $this->objectIds['Images'][1];
		$response->Relations[1]->ChildInfo->Name = 'Image_IDSPreview_2';
		$response->Relations[1]->ChildInfo->Type = 'Image';
		$response->Relations[1]->ChildInfo->Format = 'image/jpeg';
		$response->Relations[1]->ObjectLabels = null;
		$response->Relations[2] = new Relation();
		$response->Relations[2]->Parent = $this->objectIds['Layouts'][0];
		$response->Relations[2]->Child = $this->objectIds['Images'][2];
		$response->Relations[2]->Type = 'Placed';
		$response->Relations[2]->Placements = array();
		$response->Relations[2]->Placements[0] = new Placement();
		$response->Relations[2]->Placements[0]->Page = '3';
		$response->Relations[2]->Placements[0]->Element = 'graphic';
		$response->Relations[2]->Placements[0]->ElementID = '';
		$response->Relations[2]->Placements[0]->FrameOrder = '0';
		$response->Relations[2]->Placements[0]->FrameID = '352';
		$response->Relations[2]->Placements[0]->Left = '276.000000';
		$response->Relations[2]->Placements[0]->Top = '85.200000';
		$response->Relations[2]->Placements[0]->Width = '204.000000';
		$response->Relations[2]->Placements[0]->Height = '218.400000';
		$response->Relations[2]->Placements[0]->Overset = null;
		$response->Relations[2]->Placements[0]->OversetChars = null;
		$response->Relations[2]->Placements[0]->OversetLines = null;
		$response->Relations[2]->Placements[0]->Layer = 'Layer 1';
		$response->Relations[2]->Placements[0]->Content = '';
		$response->Relations[2]->Placements[0]->Edition = null;
		$response->Relations[2]->Placements[0]->ContentDx = '0.000000';
		$response->Relations[2]->Placements[0]->ContentDy = '45.629577';
		$response->Relations[2]->Placements[0]->ScaleX = '0.718310';
		$response->Relations[2]->Placements[0]->ScaleY = '0.718310';
		$response->Relations[2]->Placements[0]->PageSequence = '2';
		$response->Relations[2]->Placements[0]->PageNumber = '3';
		$response->Relations[2]->Placements[0]->Tiles = array();
		$response->Relations[2]->Placements[0]->FormWidgetId = null;
		$response->Relations[2]->Placements[0]->InDesignArticleIds = array();
		$response->Relations[2]->Placements[0]->InDesignArticleIds[0] = '253';
		$response->Relations[2]->Placements[0]->FrameType = 'graphic';
		$response->Relations[2]->Placements[0]->SplineID = '252';
		$response->Relations[2]->ParentVersion = null;
		$response->Relations[2]->ChildVersion = null;
		$response->Relations[2]->Geometry = null;
		$response->Relations[2]->Rating = null;
		$response->Relations[2]->Targets = null;
		$response->Relations[2]->ParentInfo = new ObjectInfo();
		$response->Relations[2]->ParentInfo->ID = $this->objectIds['Layouts'][0];
		$response->Relations[2]->ParentInfo->Name = 'Layout_IDSPreview_1';
		$response->Relations[2]->ParentInfo->Type = 'Layout';
		$response->Relations[2]->ParentInfo->Format = 'application/indesign';
		$response->Relations[2]->ChildInfo = new ObjectInfo();
		$response->Relations[2]->ChildInfo->ID = $this->objectIds['Images'][2];
		$response->Relations[2]->ChildInfo->Name = 'Image_IDSPreview_3';
		$response->Relations[2]->ChildInfo->Type = 'Image';
		$response->Relations[2]->ChildInfo->Format = 'image/jpeg';
		$response->Relations[2]->ObjectLabels = null;
		return $response;
	}
	
	private function testService014()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getRecordedRequest014();

		$stepInfo = 'testService#014:Checkin the article.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );

		$objId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $objId );
	}

	private function getRecordedRequest014()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->objectIds['Articles'][0];
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:bacc0531-ba31-456c-a843-be77afec6fe5';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Article_IDSPreview_1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->categoryObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->categoryObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$request->Objects[0]->MetaData->SourceMetaData->Source = '';
		$request->Objects[0]->MetaData->SourceMetaData->Author = '';
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Keywords[0] = '';
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Nequisciam inciumquis endaerchit exera volupie nestem essit in nemquam, offic tota con num aut occusa nossed mi, quo volor asitatisquam atius voluptia sam incipsam, cum repudae nem et acearchic to enducimus dolorib usantia turissi ntibus ut lautempor';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 220;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 1476;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 1;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 15;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Nequisciam inciumquis endaerchit exera volupie nestem essit in nemquam, offic tota con num aut occusa nossed mi, quo volor asitatisquam atius voluptia sam incipsam, cum repudae nem et acearchic to enducimus dolorib usantia turissi ntibus ut lautempor am evelit aut lit as ad qui volesequas a autesti a voluptaquia dit dolorro blaut quam, voluptae dolendu citatur ad quidipietus rest, id molupta tiossim incium quam, cus, sit videstemqui dolorpor si ducimagnat quiassi taquis aut est, aut licit, in cullenisquas volor as volendios ea volum evero offic tem quamus corem non natus aliat licias molentota pro quo incto qui reni dus deratus similiquas quo voluptatae vendit doluptibus dolore sinvene ctiumquias destio dicipistet perci volesti officient, coribus et re nonsentur se aut est aut quo te velestota autenit faceperum se secupti sae inctur rempor re nit aut volupta voluptis reptur maximpostio beriorehento totatibusam corentem eum quas sitem simagnis ipidus earum dolut qui alita del exceatur moditaecepe nonse cumquatatium facepro rissectur autem evellab oreprat omnimagnam quatus sitis nam reriossit estiis et acea dere, venimus, voluptat autatam aut inumquia volore omnis abo. Et im eum, sum laborer ioreribus, cora conectionem necte quaerio mo ellacese di omnisit, que restio. Harum rate voluptas elit, odisimet ullecae ped ut verfero dolestest, ommo blantem quatendis nonecum volupta essitiamus simincte omnimus quid molenia dolum re volorpori aut et poriam eventis';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 67695;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->ContentMetaData->Dimensions = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2017-06-07T15:29:40';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->articleStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$request->Objects[0]->Files[0]->ContentSourceFileLink = null;
		$inputPath = dirname(__FILE__).'/IDSPreview_TestData/rec#014_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'FBA64985-970D-4AF5-842F-179E826F5508';
		$request->Objects[0]->Elements[0]->Name = 'body';
		$request->Objects[0]->Elements[0]->LengthWords = 220;
		$request->Objects[0]->Elements[0]->LengthChars = 1476;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 15;
		$request->Objects[0]->Elements[0]->Snippet = 'Nequisciam inciumquis endaerchit exera volupie nestem essit in nemquam, offic tota con num aut occusa nossed mi, quo volor asitatisquam atius voluptia sam incipsam, cum repudae nem et acearchic to enducimus dolorib usantia turissi ntibus ut lautempor';
		$request->Objects[0]->Elements[0]->Version = '02DE90FE-D3DC-4C74-B5CC-ACFE20466228';
		$request->Objects[0]->Elements[0]->Content = 'Nequisciam inciumquis endaerchit exera volupie nestem essit in nemquam, offic tota con num aut occusa nossed mi, quo volor asitatisquam atius voluptia sam incipsam, cum repudae nem et acearchic to enducimus dolorib usantia turissi ntibus ut lautempor am evelit aut lit as ad qui volesequas a autesti a voluptaquia dit dolorro blaut quam, voluptae dolendu citatur ad quidipietus rest, id molupta tiossim incium quam, cus, sit videstemqui dolorpor si ducimagnat quiassi taquis aut est, aut licit, in cullenisquas volor as volendios ea volum evero offic tem quamus corem non natus aliat licias molentota pro quo incto qui reni dus deratus similiquas quo voluptatae vendit doluptibus dolore sinvene ctiumquias destio dicipistet perci volesti officient, coribus et re nonsentur se aut est aut quo te velestota autenit faceperum se secupti sae inctur rempor re nit aut volupta voluptis reptur maximpostio beriorehento totatibusam corentem eum quas sitem simagnis ipidus earum dolut qui alita del exceatur moditaecepe nonse cumquatatium facepro rissectur autem evellab oreprat omnimagnam quatus sitis nam reriossit estiis et acea dere, venimus, voluptat autatam aut inumquia volore omnis abo. Et im eum, sum laborer ioreribus, cora conectionem necte quaerio mo ellacese di omnisit, que restio. Harum rate voluptas elit, odisimet ullecae ped ut verfero dolestest, ommo blantem quatendis nonecum volupta essitiamus simincte omnimus quid molenia dolum re volorpori aut et poriam eventis';
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Objects[0]->Placements = null;
		$request->Objects[0]->Operations = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}
	
	private function testService015()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteArticleWorkspaceService.class.php';
		$req = $this->getRecordedRequest015();

		$stepInfo = 'testService#015:Deleting Article Workspace.';
		$curResp = $this->globalUtils->callService( $this, $req, $stepInfo );
	}

	private function getRecordedRequest015()
	{
		$request = new WflDeleteArticleWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->WorkspaceId = $this->workspaceId;
		return $request;
	}

	private function getRecordedRequest016()
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

	/**
	 * Returns list of properties of which its value is not fixed.
	 *
	 * @return array List of Key(the property)=>Value(true) pairs.
	 */
	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true,
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true
		);
	}

	/**
	 * Grabs all the test data that was setup by the Setup_TestCase in the TestSuite.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();

		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		$this->ticket = @$vars['BuildTest_AutomatedPrintWorkflow']['ticket'];
		$this->assertNotNull( $this->ticket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		$testOptions = (defined('TESTSUITE')) ? unserialize( TESTSUITE ) : array();
		$this->user = $testOptions['User'];
		$this->assertNotNull( $this->user );

		$this->pubObj = @$vars['BuildTest_AutomatedPrintWorkflow']['brand'];
		$this->assertInstanceOf( 'PublicationInfo', $this->pubObj );

		$pubChannel = @$vars['BuildTest_AutomatedPrintWorkflow']['pubChannel'];
		$this->assertInstanceOf( 'AdmPubChannel', $pubChannel );
		$this->pubChannelObj = new PubChannel( $pubChannel->Id, $pubChannel->Name ); // convert adm to wfl

		$this->issueObj = @$vars['BuildTest_AutomatedPrintWorkflow']['issue'];
		$this->assertInstanceOf( 'AdmIssue', $this->issueObj );

		$this->editionObjs = @$vars['BuildTest_AutomatedPrintWorkflow']['editions'];
		$this->assertCount( 2, $this->editionObjs );
		$this->assertInstanceOf( 'stdClass', $this->editionObjs[0] );
		$this->assertInstanceOf( 'stdClass', $this->editionObjs[1] );

		$this->layoutStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['layoutStatus'];
		$this->assertInstanceOf( 'State', $this->layoutStatus );

		$this->layoutStatusInResp = unserialize( serialize( $this->layoutStatus ));
		$this->layoutStatusInResp->Produce = null; // In Response, null is returned instead of boolean.
		$this->assertInstanceOf( 'State', $this->layoutStatusInResp );

		$this->articleStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['articleStatus'];
		$this->assertInstanceOf( 'State', $this->articleStatus );

		$this->imageStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['imageStatus'];
		$this->assertInstanceOf( 'State', $this->imageStatus );

		$this->dossierStatus = @$vars['BuildTest_AutomatedPrintWorkflow']['dossierStatus'];
		$this->assertInstanceOf( 'State', $this->dossierStatus );

		$this->categoryObj = @$vars['BuildTest_AutomatedPrintWorkflow']['category'];
		$this->assertInstanceOf( 'CategoryInfo', $this->categoryObj );
	}

	/**
	 * Determines whether or not certain data has been round-tripped correctly through a web service.
	 *
	 * @param mixed $expectedData Expected data. Is part of $expectedResp to be compared.
	 * @param mixed $currentData Actual data. Is part of $currentResp to be compared.
	 * @param mixed $expectedCall Request sent to Ent Server, or recorded response returned by Ent Server.
	 * @param mixed $currentCall Actual response returned by Ent Server.
	 * @param string $dataPathInfo Path in the data tree where to find the expected data.
	 * @param string $serviceName Web service called (for debugging purpose only).
	 * @param array $ignorePaths Array of key and value: Key=Property path, Value=true. Eg. array('BasicMetaData/ID'=>true)
	 * @param array $ignoreNames Array of key and value: Key=Property name, Value=true. Eg. array('Id'=>true)
	 */
	private function validateRoundtrip(
		$expectedData, $currentData,
		$expectedCall, $currentCall,
		$dataPathInfo, $serviceName,
		array $ignorePaths = array(), array $ignoreNames = array() )
	{
		// Compare recorded Pages with currently created Pages.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$ignoreNames = array_merge( $ignoreNames, $this->getCommonPropDiff() );
		$phpCompare->initCompare( $ignorePaths, $ignoreNames );
		if( !$phpCompare->compareTwoProps( $expectedData, $currentData ) ) { // 'original', 'modified'
			$expectedFile = LogHandler::logPhpObject( $expectedCall, 'pretty_print', '000' );
			$currentFile = LogHandler::logPhpObject( $currentCall, 'pretty_print', '000' );
			$errorMsg = 'Error occured in data roundtrip at '.$dataPathInfo.' for '.$serviceName.'. ';
			$errorMsg .= implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Expected call: '.$expectedFile.'<br/>';
			$errorMsg .= 'Current call: '.$currentFile.'<br/>';
			$this->throwError( $errorMsg );
		}
	}

	/**
	 * The order of some data items under the Object data structure are not preserved in the DB.
	 * So after a round-trip through DB (e.g. SaveObjects followed by GetObjects) some data
	 * might appear in different order. This function puts all data in a fixed order, so that,
	 * after calling, the whole Object can be compared with another Object.
	 *
	 * @param Object $object
	 */
	public function sortObjectDataForCompare( /** @noinspection PhpLanguageLevelInspection */ Object $object )
	{
		if( $object->Placements ) {
			$this->sortPlacementsForCompare( $object->Placements );
		}
		if( $object->Relations ) {
			$this->sortObjectRelationsForCompare( $object->Relations );
		}
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
			$req = $this->getRecordedRequest016();
			$stepInfo = 'testService#051:Deleting all the test objects.';
			$response = $this->globalUtils->callService( $this, $req, $stepInfo );

			$this->objectIds = null;
		}
	}
}
