<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflArticlePlacements_TestCase extends TestCase
{
	private $ticket = null;
	private $articleResponse = null;
	private $layoutResponse = null;
	private $transferServer = null;
	private $issueInfo = null;
	private $publicationInfo = null;
	private $articleState = null;
	private $layoutState = null;
	private $pubChannelInfo = null;
	private $dossierId = null;
	private $articleName = null;
	private $layoutName = null;

	const NAME = 'WflPlacements TestCase';

	public function getDisplayName() { return 'Layout Article Placements'; }
	public function getTestGoals()   { return 'Checks if Articles or their components can be correctly placed on a Layout'; }
	public function getTestMethods() { return 'Call createObject to see if the placement(s) are allowed.'; }
	public function getPrio()        { return 112; }

	/**
	 * Runs the test cases for this test.
	 *
	 * @return bool
	 */
	final public function runTest()
	{
		$this->articleName = 'WflArticlePlacements_Article_'.date("m d H i s");
		$this->layoutName = 'WflArticlePlacements_Layout_'.date("m d H i s");
		
		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$vars = $this->getSessionVariables();

		// Test the health of this test case.
		if (!$this->checkHealth($vars)) {
			return false;
		}

		// Build article object to test with + components
		if (!$this->createArticle($vars)) {
			return false;
		}

		// Run contained Test Case 1: Normal Layout with a single placement.
		if (!$this->testCase1()) {

			$this->removeArticle();
			return false;
		}

		// Run contained Test Case 2: Normal Layout with a duplicate placement.
		if (!$this->testCase2()) {
			$this->removeArticle();
			return false;
		}

		// Run contained Test Case 3: Alternate Layout with single placement on both.
		if (!$this->testCase3()) {
			$this->removeArticle();
			return false;
		}

		// Run contained Test Case 4: Alternate Layout with duplicate placement on one.
		if (!$this->testCase4()) {
			$this->removeArticle();
			return false;
		}

		// Remove the article and the dossier.
		$this->removeArticle();
		return true;
	}

	/**
	 * Create / Store an Article for the tests.
	 *
	 * Creates a Dossier as part of the test as well.
	 *
	 * @param mixed $vars The parameters/variables to used when creating an article.
	 * @return bool Whether creation of the article was succesful or not.
	 */
	private function createArticle($vars)
	{
		// Build the request.
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';

		$service = new WflCreateObjectsService();
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->articleName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->publicationInfo->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->publicationInfo->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->publicationInfo->Categories[0]->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->publicationInfo->Categories[0]->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Body 9999999Body 00000000';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 2;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 670;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 330;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 4;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 25;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 2;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 2;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Body 9999999Body 00000000';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 71969;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2012-11-14T11:43:26';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2012-11-14T11:43:26';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->articleState->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->articleState->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = '-1';
		$request->Objects[0]->Relations[0]->Child = '';
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->Targets[0] = new Target();
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel->Id = $this->pubChannelInfo->Id;
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel->Name = $this->pubChannelInfo->Name;
		$request->Objects[0]->Relations[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->Id = $this->issueInfo->Id;
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->Name = $this->issueInfo->Name;
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Relations[0]->Targets[0]->Editions = array();
		$count = 0;
		foreach ($this->pubChannelInfo->Editions as $edition) {
			$request->Objects[0]->Relations[0]->Targets[0]->Editions[$count] = new Edition();
			$request->Objects[0]->Relations[0]->Targets[0]->Editions[$count]->Id = $edition->Id;
			$request->Objects[0]->Relations[0]->Targets[0]->Editions[$count]->Name = $edition->Name;
			$count++;
		}
		$request->Objects[0]->Relations[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Relations[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = '0B3D88E9-C023-4975-813B-BE4E628D45A9';
		$request->Objects[0]->Elements[0]->Name = 'body';
		$request->Objects[0]->Elements[0]->LengthWords = 2;
		$request->Objects[0]->Elements[0]->LengthChars = 12;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = 'Body 9999999';
		$request->Objects[0]->Elements[0]->Version = '2BDCB61B-2F8B-40C0-A109-9A8963628237';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = 'C6721548-CFB5-432C-B68B-C82EF7D75F25';
		$request->Objects[0]->Elements[1]->Name = 'body';
		$request->Objects[0]->Elements[1]->LengthWords = 2;
		$request->Objects[0]->Elements[1]->LengthChars = 13;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 1;
		$request->Objects[0]->Elements[1]->Snippet = 'Body 00000000';
		$request->Objects[0]->Elements[1]->Version = '7EF755F3-7CB3-4A5D-8804-4301259D4E60';
		$request->Objects[0]->Elements[1]->Content = null;
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;

		// Now fire the request to create the article.
		$this->articleResponse = $service->execute( $request );

		if (is_null($this->articleResponse)) {
			$message = 'Article could not be created succesfully.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Analyze the other response data, there should be no report messages.
		if (isset($this->articleResponse->Reports) && count($this->articleResponse->Reports) > 0) {
			$message = 'Unexpected report when creating an Article.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', var_export($this->articleResponse->Reports[0], true));
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Retrieve the dossier ID.
		$dossierId = null;
		foreach( $this->articleResponse->Objects[0]->Relations as $relation) {
			if ($relation->Type == 'Contained') {
				$dossierId = $relation->Parent;
			}
		}

		if ( is_null($dossierId) ) {
			$message = 'Dossier ID could not be determined succesfully.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Set the dossier ID for reference in the test cases.
		$this->dossierId = $dossierId;
		return true;
	}

	/**
	 * Creates a Layout with a single orientation or with both an horizontal and vertical orientation.
	 *
	 * @param string $testCase The label to use when logging messages.
	 * @param bool $lock Whether to make the request lock the layout or not.
	 * @param bool $altLayout Whether to create a layout with an alternate or not.
	 * @return bool Whether or not the creation of the layout was succesful.
	 */
	private function createLayout($testCase, $lock = false, $altLayout = false)
	{
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';

		$documentId = ($altLayout) ? '6EA8FC83292068119109D18F61321DE2' : 'F77F1174072068118083D0FE7A9CFC36';
		$fileSize = ($altLayout) ? 450560 : 417792;

		$service = new WflCreateObjectsService();
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = $lock;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:' . $documentId;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->layoutName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->publicationInfo->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->publicationInfo->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->publicationInfo->Categories[0]->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->publicationInfo->Categories[0]->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = $fileSize;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2012-11-15T14:57:56';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2012-11-15T14:55:33';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutState->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutState->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->dossierId;
		$request->Objects[0]->Relations[0]->Child = '';
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;

		// Pages to be handled differently for alt Layout
		$request->Objects[0]->Pages = ($altLayout) ? $this->getPagesForAlternateLayout() : $this->getPagesForSingleLayout();
		$request->Objects[0]->Files = ($altLayout) ? $this->getFilesForAlternateLayout() : $this->getFilesForSingleLayout();

		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelInfo->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelInfo->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueInfo->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueInfo->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$count = 0;
		foreach ($this->pubChannelInfo->Editions as $edition) {
			$request->Objects[0]->Targets[0]->Editions[$count] = new Edition();
			$request->Objects[0]->Targets[0]->Editions[$count]->Id = $edition->Id;
			$request->Objects[0]->Targets[0]->Editions[$count]->Name = $edition->Name;
			$count++;
		}
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Messages = null;
		$request->AutoNaming = null;

		// Now fire the request to create the Layout.
		$this->layoutResponse = $service->execute( $request );

		if (is_null($this->layoutResponse)) {
			$message = 'Layout could not be created succesfully for test case:' . $testCase;
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Analyze the other response data, there should be no report messages.
		if (isset($this->layoutResponse->Reports) && count($this->layoutResponse->Reports) > 0) {
			$message = 'Unexpected report when creating an Layout.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', var_export($this->layoutResponse->Reports[0], true));
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the pages component for an alternate layout.
	 *
	 * @return Page[] $pages Retuns an array of Page objects.
	 */
	private function getPagesForAlternateLayout()
	{
		$pages = array();
		$pages[0] = new Page();
		$pages[0]->Width = 595.275591;
		$pages[0]->Height = 841.889764;
		$pages[0]->PageNumber = '1';
		$pages[0]->PageOrder = 1;
		$pages[0]->Files = array();
		$pages[0]->Files[0] = new Attachment();
		$pages[0]->Files[0]->Rendition = 'thumb';
		$pages[0]->Files[0]->Type = 'image/jpeg';
		$pages[0]->Files[0]->Content = null;
		$pages[0]->Files[0]->FilePath = '';
		$pages[0]->Files[0]->FileUrl = null;
		$pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[0]->Files[0] );
		$pages[0]->Files[1] = new Attachment();
		$pages[0]->Files[1]->Rendition = 'preview';
		$pages[0]->Files[1]->Type = 'image/jpeg';
		$pages[0]->Files[1]->Content = null;
		$pages[0]->Files[1]->FilePath = '';
		$pages[0]->Files[1]->FileUrl = null;
		$pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[0]->Files[1] );
		$pages[0]->Edition = null;
		$pages[0]->Master = 'Master';
		$pages[0]->Instance = 'Production';
		$pages[0]->PageSequence = 1;
		$pages[0]->Renditions = null;
		$pages[0]->Orientation = 'portrait';
		$pages[1] = new Page();
		$pages[1]->Width = 595.275591;
		$pages[1]->Height = 841.889764;
		$pages[1]->PageNumber = '2';
		$pages[1]->PageOrder = 2;
		$pages[1]->Files = array();
		$pages[1]->Files[0] = new Attachment();
		$pages[1]->Files[0]->Rendition = 'thumb';
		$pages[1]->Files[0]->Type = 'image/jpeg';
		$pages[1]->Files[0]->Content = null;
		$pages[1]->Files[0]->FilePath = '';
		$pages[1]->Files[0]->FileUrl = null;
		$pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[1]->Files[0] );
		$pages[1]->Files[1] = new Attachment();
		$pages[1]->Files[1]->Rendition = 'preview';
		$pages[1]->Files[1]->Type = 'image/jpeg';
		$pages[1]->Files[1]->Content = null;
		$pages[1]->Files[1]->FilePath = '';
		$pages[1]->Files[1]->FileUrl = null;
		$pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[1]->Files[1] );
		$pages[1]->Edition = null;
		$pages[1]->Master = 'Master';
		$pages[1]->Instance = 'Production';
		$pages[1]->PageSequence = 2;
		$pages[1]->Renditions = null;
		$pages[1]->Orientation = 'portrait';

		$pages[2] = new Page();
		$pages[2]->Width = 841.889764;
		$pages[2]->Height = 595.275591;
		$pages[2]->PageNumber = '1';
		$pages[2]->PageOrder = 1;
		$pages[2]->Files = array();
		$pages[2]->Files[0] = new Attachment();
		$pages[2]->Files[0]->Rendition = 'thumb';
		$pages[2]->Files[0]->Type = 'image/jpeg';
		$pages[2]->Files[0]->Content = null;
		$pages[2]->Files[0]->FilePath = '';
		$pages[2]->Files[0]->FileUrl = null;
		$pages[2]->Files[0]->EditionId = '';
		//$inputPath = dirname(__FILE__).'/WflArticlePlacements1_TestData/rec#003_att#004_thumb.jpg';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[2]->Files[0] );
		$pages[2]->Files[1] = new Attachment();
		$pages[2]->Files[1]->Rendition = 'preview';
		$pages[2]->Files[1]->Type = 'image/jpeg';
		$pages[2]->Files[1]->Content = null;
		$pages[2]->Files[1]->FilePath = '';
		$pages[2]->Files[1]->FileUrl = null;
		$pages[2]->Files[1]->EditionId = '';
		//$inputPath = dirname(__FILE__).'/WflArticlePlacements1_TestData/rec#003_att#005_preview.jpg';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[2]->Files[1] );
		$pages[2]->Edition = null;
		$pages[2]->Master = 'Master A4 H';
		$pages[2]->Instance = 'Production';
		$pages[2]->PageSequence = 3;
		$pages[2]->Renditions = null;
		$pages[2]->Orientation = 'landscape';
		$pages[3] = new Page();
		$pages[3]->Width = 841.889764;
		$pages[3]->Height = 595.275591;
		$pages[3]->PageNumber = '2';
		$pages[3]->PageOrder = 2;
		$pages[3]->Files = array();
		$pages[3]->Files[0] = new Attachment();
		$pages[3]->Files[0]->Rendition = 'thumb';
		$pages[3]->Files[0]->Type = 'image/jpeg';
		$pages[3]->Files[0]->Content = null;
		$pages[3]->Files[0]->FilePath = '';
		$pages[3]->Files[0]->FileUrl = null;
		$pages[3]->Files[0]->EditionId = '';
		//$inputPath = dirname(__FILE__).'/WflArticlePlacements1_TestData/rec#003_att#006_thumb.jpg';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[3]->Files[0] );
		$pages[3]->Files[1] = new Attachment();
		$pages[3]->Files[1]->Rendition = 'preview';
		$pages[3]->Files[1]->Type = 'image/jpeg';
		$pages[3]->Files[1]->Content = null;
		$pages[3]->Files[1]->FilePath = '';
		$pages[3]->Files[1]->FileUrl = null;
		$pages[3]->Files[1]->EditionId = '';
		//$inputPath = dirname(__FILE__).'/WflArticlePlacements1_TestData/rec#003_att#007_preview.jpg';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[3]->Files[1] );
		$pages[3]->Edition = null;
		$pages[3]->Master = 'Master A4 H';
		$pages[3]->Instance = 'Production';
		$pages[3]->PageSequence = 4;
		$pages[3]->Renditions = null;
		$pages[3]->Orientation = 'landscape';
		$pages[4] = new Page();
		$pages[4]->Width = 841.889764;
		$pages[4]->Height = 595.275591;
		$pages[4]->PageNumber = '3';
		$pages[4]->PageOrder = 3;
		$pages[4]->Files = array();
		$pages[4]->Files[0] = new Attachment();
		$pages[4]->Files[0]->Rendition = 'thumb';
		$pages[4]->Files[0]->Type = 'image/jpeg';
		$pages[4]->Files[0]->Content = null;
		$pages[4]->Files[0]->FilePath = '';
		$pages[4]->Files[0]->FileUrl = null;
		$pages[4]->Files[0]->EditionId = '';
		//$inputPath = dirname(__FILE__).'/WflArticlePlacements1_TestData/rec#003_att#008_thumb.jpg';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[4]->Files[0] );
		$pages[4]->Files[1] = new Attachment();
		$pages[4]->Files[1]->Rendition = 'preview';
		$pages[4]->Files[1]->Type = 'image/jpeg';
		$pages[4]->Files[1]->Content = null;
		$pages[4]->Files[1]->FilePath = '';
		$pages[4]->Files[1]->FileUrl = null;
		$pages[4]->Files[1]->EditionId = '';
		//$inputPath = dirname(__FILE__).'/WflArticlePlacements1_TestData/rec#003_att#009_preview.jpg';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Alt_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[4]->Files[1] );
		$pages[4]->Edition = null;
		$pages[4]->Master = 'Master A4 H';
		$pages[4]->Instance = 'Production';
		$pages[4]->PageSequence = 5;
		$pages[4]->Renditions = null;
		$pages[4]->Orientation = 'landscape';
		return $pages;
	}

	/**
	 * Returns the attachments for an alternate Layout request.
	 *
	 * @return Attachment[] $files The created attachments.
	 */
	private function getFilesForAlternateLayout()
	{
		$files = array();

		$files[0] = new Attachment();
		$files[0]->Rendition = 'native';
		$files[0]->Type = 'application/indesign';
		$files[0]->Content = null;
		$files[0]->FilePath = '';
		$files[0]->FileUrl = null;
		$files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $files[0] );
		$files[1] = new Attachment();
		$files[1]->Rendition = 'thumb';
		$files[1]->Type = 'image/jpeg';
		$files[1]->Content = null;
		$files[1]->FilePath = '';
		$files[1]->FileUrl = null;
		$files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single_003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $files[1] );
		$files[2] = new Attachment();
		$files[2]->Rendition = 'preview';
		$files[2]->Type = 'image/jpeg';
		$files[2]->Content = null;
		$files[2]->FilePath = '';
		$files[2]->FileUrl = null;
		$files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single_004_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $files[2] );
		return $files;
	}

	/**
	 * Returns the attachments for a single layout request.
	 *
	 * @return Attachment[] $files The created attachments.
	 */
	private function getFilesForSingleLayout()
	{
		$files = array();
		$files[0] = new Attachment();
		$files[0]->Rendition = 'native';
		$files[0]->Type = 'application/indesign';
		$files[0]->Content = null;
		$files[0]->FilePath = '';
		$files[0]->FileUrl = null;
		$files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single_002_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $files[0] );
		$files[1] = new Attachment();
		$files[1]->Rendition = 'thumb';
		$files[1]->Type = 'image/jpeg';
		$files[1]->Content = null;
		$files[1]->FilePath = '';
		$files[1]->FileUrl = null;
		$files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single_003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $files[1] );
		$files[2] = new Attachment();
		$files[2]->Rendition = 'preview';
		$files[2]->Type = 'image/jpeg';
		$files[2]->Content = null;
		$files[2]->FilePath = '';
		$files[2]->FileUrl = null;
		$files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single_004_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $files[2] );
		return $files;
	}

	/**
	 * Retrieves the pages component for a single layout.
	 *
	 * @return Page[] $pages Retuns an array of Page objects.
	 */
	private function getPagesForSingleLayout()
	{
		$pages = array();
		$pages[0] = new Page();
		$pages[0]->Width = 595.275591;
		$pages[0]->Height = 841.889764;
		$pages[0]->PageNumber = '1';
		$pages[0]->PageOrder = 1;
		$pages[0]->Files = array();
		$pages[0]->Files[0] = new Attachment();
		$pages[0]->Files[0]->Rendition = 'thumb';
		$pages[0]->Files[0]->Type = 'image/jpeg';
		$pages[0]->Files[0]->Content = null;
		$pages[0]->Files[0]->FilePath = '';
		$pages[0]->Files[0]->FileUrl = null;
		$pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[0]->Files[0] );
		$pages[0]->Files[1] = new Attachment();
		$pages[0]->Files[1]->Rendition = 'preview';
		$pages[0]->Files[1]->Type = 'image/jpeg';
		$pages[0]->Files[1]->Content = null;
		$pages[0]->Files[1]->FilePath = '';
		$pages[0]->Files[1]->FileUrl = null;
		$pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $pages[0]->Files[1] );
		$pages[0]->Edition = null;
		$pages[0]->Master = 'Master';
		$pages[0]->Instance = 'Production';
		$pages[0]->PageSequence = 1;
		$pages[0]->Renditions = null;
		$pages[0]->Orientation = null;
		return $pages;
	}

	/**
	 * Places a single article on a layout.
	 *
	 * @param string $testCase The label used for logging errors / warnings.
	 * @return bool Whether the placement was succesful or not.
	 */
	private function placeArticleSingle($testCase)
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjectRelationsRequest.class.php';

		$service = new WflCreateObjectRelationsService();
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->layoutResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Child = $this->articleResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = 1;
		$request->Relations[0]->Placements[0]->Element = 'body';
		$request->Relations[0]->Placements[0]->ElementID = '0B3D88E9-C023-4975-813B-BE4E628D45A9';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '241';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = null;
		$request->Relations[0]->Placements[0]->OversetChars = 13;
		$request->Relations[0]->Placements[0]->OversetLines = 1;
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
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;

		// Now fire the request to create the Layout.
		$placementResponse = $service->execute( $request );

		if (is_null($placementResponse)) {
			$message = 'Placement could not be created succesfully for testCase: ' . $testCase;
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Analyze the other response data, there should be no report messages.
		if (isset($placementResponse->Reports) && count($placementResponse->Reports) > 0) {
			$message = 'Unexpected report when creating an Placement.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', var_export($placementResponse->Reports[0], true));
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		return true;
	}

	/**
	 * Attempts to place a duplicate placement on an Layout.
	 *
	 * @param string $testCase The label to use for logging errors/warnings.
	 * @return bool Whether or not the placement test was succesful.
	 */
	private function placeArticleDuplicateSingleLayout($testCase)
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflSaveObjectsRequest.class.php';

		$service = new WflSaveObjectsService();
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:F77F1174072068118083D0FE7A9CFC36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->layoutName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->publicationInfo->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->publicationInfo->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->publicationInfo->Categories[0]->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->publicationInfo->Categories[0]->Name;;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 319488;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutState->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutState->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->layoutResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Child = $this->articleResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = 'C6721548-CFB5-432C-B68B-C82EF7D75F25';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '270';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 62;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 153;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 523;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 718;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -446.464494;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = -82;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = -49;
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
		$request->Objects[0]->Relations[0]->Placements[1]->ElementID = 'C6721548-CFB5-432C-B68B-C82EF7D75F25';
		$request->Objects[0]->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->FrameID = '297';
		$request->Objects[0]->Relations[0]->Placements[1]->Left = 45;
		$request->Objects[0]->Relations[0]->Placements[1]->Top = 45;
		$request->Objects[0]->Relations[0]->Placements[1]->Width = 523;
		$request->Objects[0]->Relations[0]->Placements[1]->Height = 769;
		$request->Objects[0]->Relations[0]->Placements[1]->Overset = -446.464494;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetChars = -82;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetLines = -52;
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
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
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
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_002_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_004_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelInfo->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelInfo->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueInfo->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueInfo->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$count = 0;
		foreach ($this->pubChannelInfo->Editions as $edition) {
			$request->Objects[0]->Targets[0]->Editions[$count] = new Edition();
			$request->Objects[0]->Targets[0]->Editions[$count]->Id = $edition->Id;
			$request->Objects[0]->Targets[0]->Editions[$count]->Name = $edition->Name;
			$count++;
		}
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		// Now fire the request to create the duplicate placement.
		$saveObjectsResponse = $service->execute( $request );

		if (is_null($saveObjectsResponse)) {
			$message = 'Placements could not be created succesfully for test case:' . $testCase;
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Analyze the other response data, there should be no report messages.
		if (isset($saveObjectsResponse->Reports) && count($saveObjectsResponse->Reports) > 0) {
			$message = 'Unexpected report when creating an Placement.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', var_export($saveObjectsResponse->Reports[0], true));
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Analyze the messages, a duplicate placement message is expected now.
		$error = true;
		if (isset($saveObjectsResponse->Objects[0]->MessageList->Messages) && count($saveObjectsResponse->Objects[0]->MessageList->Messages) > 0) {
			/** @var Message $mess */
			foreach ($saveObjectsResponse->Objects[0]->MessageList->Messages as $mess) {
				if ($mess->MessageTypeDetail == 'DuplicatePlacement') {
					$error = false;
				}
			}
		}

		if ($error) {
			$message = 'Expected a duplicate placement warning, but received none for testCase: ' . $testCase;
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}
		return true;
	}

	/**
	 * Places a single article on both orientations of an alternate layout.
	 *
	 * Depending on the $duplicate setting, this test may attempt to place a duplicate and catch it when
	 * checking the response from the server.
	 *
	 * @param string $testCase String used when logging warnings/errors.
	 * @param bool $duplicate Whether or not to attempt a duplicate placement with this test, default false.
	 * @return bool Whether the test was succesful or not.
	 */
	private function placeArticleSingleAlternateLayout($testCase, $duplicate=false) {
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflSaveObjectsRequest.class.php';

		$service = new WflSaveObjectsService();
		$request = new WflSaveObjectsRequest();

		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:6EA8FC83292068119109D18F61321DE2';
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->layoutName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->publicationInfo->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->publicationInfo->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->publicationInfo->Categories[0]->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->publicationInfo->Categories[0]->Name;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 352256;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutState->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutState->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;

		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->layoutResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Child = $this->articleResponse->Objects[0]->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = '0B3D88E9-C023-4975-813B-BE4E628D45A9';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '276';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 36;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 181;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 523;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 624;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -452.224443;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = -83;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = -42;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';

		$edition = new Edition();
		$edition->Id = $this->pubChannelInfo->Editions[0]->Id;
		$edition->Name = $this->pubChannelInfo->Editions[0]->Name;

		$edition2 = new Edition();
		$edition2->Id = $this->pubChannelInfo->Editions[1]->Id;
		$edition2->Name = $this->pubChannelInfo->Editions[1]->Name;

		$request->Objects[0]->Relations[0]->Placements[0]->Edition = $edition;
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
		$request->Objects[0]->Relations[0]->Placements[1]->ElementID = '0B3D88E9-C023-4975-813B-BE4E628D45A9';
		$request->Objects[0]->Relations[0]->Placements[1]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[1]->FrameID = '305';
		$request->Objects[0]->Relations[0]->Placements[1]->Left = 36;
		$request->Objects[0]->Relations[0]->Placements[1]->Top = 88;
		$request->Objects[0]->Relations[0]->Placements[1]->Width = 769;
		$request->Objects[0]->Relations[0]->Placements[1]->Height = 471;
		$request->Objects[0]->Relations[0]->Placements[1]->Overset = -698.838616;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetChars = -128;
		$request->Objects[0]->Relations[0]->Placements[1]->OversetLines = -32;
		$request->Objects[0]->Relations[0]->Placements[1]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[1]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[1]->Edition = $edition;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[1]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[1]->PageSequence = 3;
		$request->Objects[0]->Relations[0]->Placements[1]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[1]->Tiles = array();

		if ( $duplicate ) {
			$request->Objects[0]->Relations[0]->Placements[2] = new Placement();
			$request->Objects[0]->Relations[0]->Placements[2]->Page = 3;
			$request->Objects[0]->Relations[0]->Placements[2]->Element = 'body';
			$request->Objects[0]->Relations[0]->Placements[2]->ElementID = '0B3D88E9-C023-4975-813B-BE4E628D45A9';
			$request->Objects[0]->Relations[0]->Placements[2]->FrameOrder = 0;
			$request->Objects[0]->Relations[0]->Placements[2]->FrameID = '332';
			$request->Objects[0]->Relations[0]->Placements[2]->Left = 36;
			$request->Objects[0]->Relations[0]->Placements[2]->Top = 178;
			$request->Objects[0]->Relations[0]->Placements[2]->Width = 769;
			$request->Objects[0]->Relations[0]->Placements[2]->Height = 380;
			$request->Objects[0]->Relations[0]->Placements[2]->Overset = -698.838616;
			$request->Objects[0]->Relations[0]->Placements[2]->OversetChars = -128;
			$request->Objects[0]->Relations[0]->Placements[2]->OversetLines = -25;
			$request->Objects[0]->Relations[0]->Placements[2]->Layer = 'Layer 1';
			$request->Objects[0]->Relations[0]->Placements[2]->Content = '';
			$request->Objects[0]->Relations[0]->Placements[2]->Edition = $edition;
			$request->Objects[0]->Relations[0]->Placements[2]->ContentDx = null;
			$request->Objects[0]->Relations[0]->Placements[2]->ContentDy = null;
			$request->Objects[0]->Relations[0]->Placements[2]->ScaleX = null;
			$request->Objects[0]->Relations[0]->Placements[2]->ScaleY = null;
			$request->Objects[0]->Relations[0]->Placements[2]->PageSequence = 5;
			$request->Objects[0]->Relations[0]->Placements[2]->PageNumber = '3';
			$request->Objects[0]->Relations[0]->Placements[2]->Tiles = array();
		}

		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
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
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = $edition;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = 'portrait';
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '1';
		$request->Objects[0]->Pages[1]->PageOrder = 1;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = $edition2;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 1;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[1]->Orientation = 'portrait';
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '2';
		$request->Objects[0]->Pages[2]->PageOrder = 2;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 2;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[2]->Orientation = 'portrait';
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 841.889764;
		$request->Objects[0]->Pages[3]->Height = 595.275591;
		$request->Objects[0]->Pages[3]->PageNumber = '1';
		$request->Objects[0]->Pages[3]->PageOrder = 1;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = $edition;
		$request->Objects[0]->Pages[3]->Master = 'Master A4 H';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 3;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[3]->Orientation = 'landscape';
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 841.889764;
		$request->Objects[0]->Pages[4]->Height = 595.275591;
		$request->Objects[0]->Pages[4]->PageNumber = '1';
		$request->Objects[0]->Pages[4]->PageOrder = 1;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = $edition2;
		$request->Objects[0]->Pages[4]->Master = 'Master A4 H';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 3;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Pages[4]->Orientation = 'landscape';
		$request->Objects[0]->Pages[5] = new Page();
		$request->Objects[0]->Pages[5]->Width = 841.889764;
		$request->Objects[0]->Pages[5]->Height = 595.275591;
		$request->Objects[0]->Pages[5]->PageNumber = '2';
		$request->Objects[0]->Pages[5]->PageOrder = 2;
		$request->Objects[0]->Pages[5]->Files = array();
		$request->Objects[0]->Pages[5]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[5]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[5]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[5]->Files[0]->Content = null;
		$request->Objects[0]->Pages[5]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[5]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[5]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[5]->Files[0] );
		$request->Objects[0]->Pages[5]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[5]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[5]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[5]->Files[1]->Content = null;
		$request->Objects[0]->Pages[5]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[5]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[5]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[5]->Files[1] );
		$request->Objects[0]->Pages[5]->Edition = null;
		$request->Objects[0]->Pages[5]->Master = 'Master A4 H';
		$request->Objects[0]->Pages[5]->Instance = 'Production';
		$request->Objects[0]->Pages[5]->PageSequence = 4;
		$request->Objects[0]->Pages[5]->Renditions = null;
		$request->Objects[0]->Pages[5]->Orientation = 'landscape';
		$request->Objects[0]->Pages[6] = new Page();
		$request->Objects[0]->Pages[6]->Width = 841.889764;
		$request->Objects[0]->Pages[6]->Height = 595.275591;
		$request->Objects[0]->Pages[6]->PageNumber = '3';
		$request->Objects[0]->Pages[6]->PageOrder = 3;
		$request->Objects[0]->Pages[6]->Files = array();
		$request->Objects[0]->Pages[6]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[6]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[6]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[6]->Files[0]->Content = null;
		$request->Objects[0]->Pages[6]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[6]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[6]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[6]->Files[0] );
		$request->Objects[0]->Pages[6]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[6]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[6]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[6]->Files[1]->Content = null;
		$request->Objects[0]->Pages[6]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[6]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[6]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[6]->Files[1] );
		$request->Objects[0]->Pages[6]->Edition = $edition;
		$request->Objects[0]->Pages[6]->Master = 'Master A4 H';
		$request->Objects[0]->Pages[6]->Instance = 'Production';
		$request->Objects[0]->Pages[6]->PageSequence = 5;
		$request->Objects[0]->Pages[6]->Renditions = null;
		$request->Objects[0]->Pages[6]->Orientation = 'landscape';
		$request->Objects[0]->Pages[7] = new Page();
		$request->Objects[0]->Pages[7]->Width = 841.889764;
		$request->Objects[0]->Pages[7]->Height = 595.275591;
		$request->Objects[0]->Pages[7]->PageNumber = '3';
		$request->Objects[0]->Pages[7]->PageOrder = 3;
		$request->Objects[0]->Pages[7]->Files = array();
		$request->Objects[0]->Pages[7]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[7]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[7]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[7]->Files[0]->Content = null;
		$request->Objects[0]->Pages[7]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[7]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[7]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[7]->Files[0] );
		$request->Objects[0]->Pages[7]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[7]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[7]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[7]->Files[1]->Content = null;
		$request->Objects[0]->Pages[7]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[7]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[7]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[7]->Files[1] );
		$request->Objects[0]->Pages[7]->Edition = $edition2;
		$request->Objects[0]->Pages[7]->Master = 'Master A4 H';
		$request->Objects[0]->Pages[7]->Instance = 'Production';
		$request->Objects[0]->Pages[7]->PageSequence = 5;
		$request->Objects[0]->Pages[7]->Renditions = null;
		$request->Objects[0]->Pages[7]->Orientation = 'landscape';
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_Single011_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/WflArticlePlacements_TestData_SingleDup_001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelInfo->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelInfo->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueInfo->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueInfo->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$count = 0;
		foreach ($this->pubChannelInfo->Editions as $edition) {
			$request->Objects[0]->Targets[0]->Editions[$count] = new Edition();
			$request->Objects[0]->Targets[0]->Editions[$count]->Id = $edition->Id;
			$request->Objects[0]->Targets[0]->Editions[$count]->Name = $edition->Name;
			$count++;
		}
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		// Now fire the request to create the duplicate placement.
		$saveObjectsResponse = $service->execute( $request );

		if (is_null($saveObjectsResponse)) {
			$message = 'Placements could not be created succesfully for test case:' . $testCase;
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Analyze the other response data, there should be no report messages.
		if (isset($saveObjectsResponse->Reports) && count($saveObjectsResponse->Reports) > 0) {
			$message = 'Unexpected report when creating an Placement.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', var_export($saveObjectsResponse->Reports[0], true));
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Analyze the messages, a duplicate placement message is expected now.
		$error =  true;
		if (isset($saveObjectsResponse->Objects[0]->MessageList->Messages) && count($saveObjectsResponse->Objects[0]->MessageList->Messages) > 0) {
			/** @var Message $mess */
			foreach ($saveObjectsResponse->Objects[0]->MessageList->Messages as $mess) {
				if ($mess->MessageTypeDetail == 'DuplicatePlacement') {
					$error = false;
				}
			}
		}

		if ($error) {
			if ($duplicate) {
				$message = 'Expected a duplicate placement warning, but received none for testCase: ' . $testCase;
				$help = 'Please see the logs for more information.';
				LogHandler::Log(self::NAME, 'ERROR', $message );
				$this->setResult( 'ERROR',  $message, $help );
				return false;
			}
		}
		return true;
	}

	/**
	 * Destroys an Article for the tests.
	 *
	 * @return bool Whether the deletion was succesful or not.
	 */
	private function removeArticle()
	{
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$service = new WflDeleteObjectsService();
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->ticket;

			$request->IDs = array($this->articleResponse->Objects[0]->MetaData->BasicMetaData->ID, $this->dossierId);

			$request->Permanent = true;
			$request->Areas = array('Workflow');
			$service->execute($request);
		} catch( BizException $e ) {
			$message = 'Article and Dossier could not be deleted.';
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}
		$this->articleResponse = null;
		return true;
	}

	/**
	 * Destroys an Article for the tests.
	 *
	 * @param string $testCase Label used when logging warnings/errors.
	 * @return bool Whether succesful or not.
	 */
	private function removeLayout($testCase)
	{
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$service = new WflDeleteObjectsService();
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->ticket;

			$request->IDs = array($this->layoutResponse->Objects[0]->MetaData->BasicMetaData->ID);

			$request->Permanent = true;
			$request->Areas = array('Workflow');
			$service->execute($request);
		} catch( BizException $e ) {
			$message = 'Layout could not be deleted for: ' . $testCase;
			$help = 'Please see the logs for more information.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}
		$this->layoutResponse = null;
		return true;
	}

	/**
	 * Does various health checks before attempting the test cases.
	 *
	 * @param array $vars The operational parameters
	 * @return bool Whether the check was succesful or not.
	 */
	private function checkHealth($vars)
	{
		// Check if the objects do not already exist in the system, if they do then warn and error out.
		LogHandler::Log(self::NAME, 'INFO', 'Testing the health of the WflArticlePlacements_TestCase');

		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$message = 'Could not find ticket to test with.';
			$help = 'Please enable the "Setup test data" test (WflLogOn_TestCase).';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}

		$this->publicationInfo = $vars['BuildTest_WebServices_WflServices']['publication'];
		$this->pubChannelInfo = $vars['BuildTest_WebServices_WflServices']['printPubChannel'];
		$this->issueInfo = $vars['BuildTest_WebServices_WflServices']['printIssue'];
		if( !$this->publicationInfo || !$this->pubChannelInfo || !$this->issueInfo ) {
			$message = 'Publication/PubChannel/Issue could not be retrieved from the session variables.';
			$help = 'Please enable the "Setup test data" entry (WflLogOn_TestCase.php) and try again.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Test if the object(s) exist already.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$exists = BizObject::objectNameExists( array($this->issueInfo->Id), $this->articleName, 'Article');
		if( $exists ) {
			$message = 'Article does already exist with name: ' . $this->articleName;
			$help = 'Please remove the Article and run the test again.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$exists = BizObject::objectNameExists( array($this->issueInfo->Id), $this->layoutName, 'Layout');
		if( $exists ) {
			$message = 'Layout does already exist with name: ' . $this->layoutName;
			$help = 'Please remove the Layout and run the test again.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Get an Article state for the publication.
		foreach ($this->publicationInfo->States as $state) {
			if ($state->Type == 'Article') {
				$this->articleState = $state;
				continue 1;
			}
		}

		if (is_null($this->articleState)) {
			$message = 'Could not find a usable state for an Article in Publication: ' . $this->publicationInfo->Id;
			$help = 'Please create a state or change the Publication.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Get an Layout state for the publication.
		foreach ($this->publicationInfo->States as $state) {
			if ($state->Type == 'Layout') {
				$this->layoutState = $state;
				continue 1;
			}
		}

		if (is_null($this->layoutState)) {
			$message = 'Could not find a usable state for an Layout in Publication: ' . $this->publicationInfo->Id;
			$help = 'Please create a state or change the Publication.';
			LogHandler::Log(self::NAME, 'ERROR', $message );
			$this->setResult( 'ERROR',  $message, $help );
			return false;
		}

		// Transfer server needed to store the Article(s) for the Placement tests.
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		LogHandler::Log(self::NAME, 'INFO', 'Testing the health of the WflArticlePlacements_TestCase completed succesfully');
		return true;
	}

	/**
	 * TestCase 1: Test placing a single article component on a single layout.
	 *
	 * @return bool Whether succesful or not.
	 */
	private function testCase1()
	{
		$case = 'testCase1';
		if (!$this->createLayout($case, false)) {
			return false;
		}

		if (!$this->placeArticleSingle($case)) {
			$this->removeLayout($case);
			return false;
		}

		// Dismantle the test data.
		return $this->removeLayout($case);
	}

	/**
	 * TestCase 2: Test attempting to place a duplicate placement on a single layout.
	 *
	 * @return bool Whether succesful or not.
	 */
	private function testCase2()
	{
		$case = 'testCase2';
		if (!$this->createLayout($case, true, false)) {
			return false;
		}

		// Test a duplicate article placement on the single layout.
		if (!$this->placeArticleDuplicateSingleLayout($case)) {
			$this->removeLayout($case);
			return false;
		}

		// Dismantle the test data.
		return $this->removeLayout($case);
	}

	/**
	 * Test Case 3: Attempt placing an article on both orientations of an alternate layout.
	 *
	 * @return bool Whether the test was succesful or not.
	 */
	private function testCase3()
	{
		$case = 'testCase3';

		// Create a layout with a main alternate page.
		if (!$this->createLayout($case, true, true)) {
			return false;
		}
		// Attempt to place a single article on both pages once.
		if (!$this->placeArticleSingleAlternateLayout($case, false)) {
			$this->removeLayout($case);
			return false;
		}

		// Dismantle the test data.
		return $this->removeLayout($case);
	}

	/**
	 * TestCase 4: Attempt placing a duplicate placement on an alternate layout orientation.
	 *
	 * @return bool Whether the test was succesful or not.
	 */
	private function testCase4()
	{
		$case = 'testCase4';

		// Create a layout with a main alternate page.
		if (!$this->createLayout($case, true, true)) {
			return false;
		}
		// Attempt to place a single article on both pages once.
		if (!$this->placeArticleSingleAlternateLayout($case, true)) {
			$this->removeLayout($case);
			return false;
		}

		// Dismantle the test data.
		return $this->removeLayout($case);
	}
}