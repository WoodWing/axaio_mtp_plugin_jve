<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflGetRelatedPages_TestCase extends TestCase
{
	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflServicesUtils */
	private $wflServicesUtils;

	/** @var BizTransferServer $transferServer */
	private $transferServer;

	/** @var string $ticket  */
	private $ticket;

	/** @var string $ticket  */
	private $user;

	/** @var PublicationInfo $pubInfo */
	private $pubInfo;

	/** @var PubChannelInfo $pubChannelInfo */
	private $pubChannelInfo;

	/** @var IssueInfo $issueInfo */
	private $issueInfo;

	/** @var CategoryInfo $categoryInfo */
	private $categoryInfo;

	/** @var State $layoutStatus */
	private $layoutStatus;

	/** @var Edition $edition1 */
	private $edition1;

	/** @var Edition $edition2 */
	private $edition2;

	/** @var Object $layout */
	private $layout;

	/** @var Object $copiedLayout */
	private $copiedLayout;

	/** @var Attachment[] $transferServerFiles */
	private $transferServerFiles = array();

	public function getDisplayName() { return 'Related Pages'; }
	public function getTestGoals()   { return 'Checks if GetRelatedPages returns a valid response as requested.'; }
	public function getPrio()        { return '180'; }
	public function isSelfCleaning() { return true; }

	//TODO
	public function getTestMethods()
	{
		return '';
	}

	final public function runTest()
	{
		try {
			$this->setupTestData();
			$this->testGetRelatedPagesInfo();
			$this->testGetRelatedPages();
		} catch( BizException $e ) {}

		$this->teardownTestData();
	}

	/**
	 * Construct test data used in the script.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		if( !$this->wflServicesUtils->initTest( $this, 'GRP' ) ) {
			return;
		}

		$this->vars = $this->getSessionVariables();
		$this->ticket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->pubInfo = @$this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->pubChannelInfo = @$this->vars['BuildTest_WebServices_WflServices']['printPubChannel'];
		$this->issueInfo = @$this->vars['BuildTest_WebServices_WflServices']['printIssue'];
		$this->categoryInfo = @$this->vars['BuildTest_WebServices_WflServices']['category'];
		$this->layoutStatus = $this->vars['BuildTest_WebServices_WflServices']['layoutStatus'];

		if( !$this->ticket || !$this->pubInfo || !$this->pubChannelInfo || !$this->issueInfo || !$this->categoryInfo ) {
			$this->throwError( 'Could not find test data to work on. Please enable the "Setup test data" entry and try again.' );
		}

		$suiteOpts = unserialize( TESTSUITE );
		$this->user = $suiteOpts['User'];

		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		$admEdition1 = $this->wflServicesUtils->createEdition( 'Creating first edition for Get Related Pages.',
			$this->pubInfo->Id, $this->pubChannelInfo->Id );
		$this->edition1 = new Edition( $admEdition1->Id, $admEdition1->Name );

		$admEdition2 = $this->wflServicesUtils->createEdition( 'Creating second edition for Get Related Pages.',
			$this->pubInfo->Id, $this->pubChannelInfo->Id );
		$this->edition2 = new Edition( $admEdition2->Id, $admEdition2->Name );

		$this->layout = $this->createLayout();
		$this->copiedLayout = $this->copyLayout();
	}

	/**
	 * Remove all leftover test data used in the script.
	 */
	private function teardownTestData()
	{
		if( $this->layout ) {
			$errorReport = '';
			$this->wflServicesUtils->deleteObject( $this->layout->MetaData->BasicMetaData->ID,
				'Deleting layout for GetRelatedPages BuildTest.', $errorReport );
			unset( $this->layout );
		}
		if( $this->copiedLayout ) {
			$errorReport = '';
			$this->wflServicesUtils->deleteObject( $this->copiedLayout->MetaData->BasicMetaData->ID,
				'Deleting copied layout for GetRelatedPages BuildTest.', $errorReport );
			unset( $this->layout );
		}
		if( $this->edition1 ) {
			$this->wflServicesUtils->deleteEdition( 'Deleting first edition for Get Related Pages.', $this->pubInfo->Id, $this->edition1->Id );
			unset( $this->edition1 );
		}
		if( $this->edition2 ) {
			$this->wflServicesUtils->deleteEdition( 'Deleting second edition for Get Related Pages.', $this->pubInfo->Id, $this->edition2->Id );
			unset( $this->edition2 );
		}
		if( $this->transferServerFiles ) {
			foreach( $this->transferServerFiles as $file ) {
				$this->transferServer->deleteFile( $file->FilePath );
			}
			unset( $this->transferServerFiles );
		}
	}

	private function testGetRelatedPagesInfo()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesInfoService.class.php';
		$request = new WflGetRelatedPagesInfoRequest();
		$request->Ticket = $this->ticket;
		$request->LayoutId = $this->layout->MetaData->BasicMetaData->ID;
		$request->PageSequences = array( 1 );

		$service = new WflGetRelatedPagesInfoService();
		/** @var WflGetRelatedPagesInfoResponse $response */
		$response = $service->execute( $request );
		$this->assertInstanceOf( 'WflGetRelatedPagesInfoResponse', $response );

		$this->assertCount( 2, $response->LayoutObjects );

		$layoutsToBeFound = array(
			$this->layout->MetaData->BasicMetaData->ID => true,
			$this->copiedLayout->MetaData->BasicMetaData->ID => true,
		);

		foreach( $response->LayoutObjects as $layoutObject ) {
			$this->assertInstanceOf( 'LayoutObject', $layoutObject );
			$this->assertTrue( isset( $layoutsToBeFound[$layoutObject->Id] ) );
		}

	}

	private function testGetRelatedPages()
	{}

	/********************
	 * Utility functions
	 */

	/**
	 * @return Object
	 */
	private function createLayout()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Layout '.$this->wflServicesUtils->getTimeStamp();
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->composePublication();
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->composeCategory();
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1146880;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: '.__CLASS__;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();

		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595;
		$request->Objects[0]->Pages[0]->Height = 842;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_thumb.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_preview.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$this->transferServerFiles = array_merge( $this->transferServerFiles, $request->Objects[0]->Pages[0]->Files );

		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595;
		$request->Objects[0]->Pages[1]->Height = 842;
		$request->Objects[0]->Pages[1]->PageNumber = '2';
		$request->Objects[0]->Pages[1]->PageOrder = 2;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_thumb.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_preview.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$this->transferServerFiles = array_merge( $this->transferServerFiles, $request->Objects[0]->Pages[1]->Files );

		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/layout_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/layout_thumb.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/layout_preview.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$this->transferServerFiles = array_merge( $this->transferServerFiles, $request->Objects[0]->Files );

		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = $this->composeTarget();
		$request->Objects[0]->MessageList = new MessageList();

		$service = new WflCreateObjectsService();
		/** @var WflCreateObjectsResponse $response */
		$response = $service->execute( $request );
		return $response->Objects[0];
	}

	/**
	 * Composes a Publication object from PublicationInfo
	 *
	 * @return Publication
	 */
	private function composePublication()
	{
		$publication = new Publication();
		$publication->Id = $this->pubInfo->Id;
		$publication->Name = $this->pubInfo->Name;
		return $publication;
	}

	/**
	 * Composes a Category object from CategoryInfo
	 *
	 * @return Category
	 */
	private function composeCategory()
	{
		$category = new Category();
		$category->Id = $this->categoryInfo->Id;
		$category->Name = $this->categoryInfo->Name;
		return $category;
	}

	/**
	 * Composes a Target for a Layout object.
	 * The target is based on the created pubchannel/issue/editions during setup.
	 *
	 * @return Target
	 */
	private function composeTarget()
	{
		$target = new Target();
		$target->PubChannel = new PubChannel( $this->pubChannelInfo->Id, $this->pubChannelInfo->Name );
		$target->Issue = new Issue( $this->issueInfo->Id, $this->issueInfo->Name );
		$target->Editions[] = $this->edition1;
		return $target;
	}

	/**
	 * @return Object Results of WflCopyObject put in an Object.
	 */
	private function copyLayout()
	{
		require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
		$request = new WflCopyObjectRequest();
		$request->Ticket = $this->ticket;
		$request->SourceID = $this->layout->MetaData->BasicMetaData->ID;
		$request->MetaData = new MetaData();
		$request->MetaData->BasicMetaData = new BasicMetaData();
		$request->MetaData->BasicMetaData->Name = 'CopyOf'.$this->layout->MetaData->BasicMetaData->Name;
		$request->MetaData->BasicMetaData->Publication = new Publication( $this->pubInfo->Id, $this->pubInfo->Name );
		$request->MetaData->BasicMetaData->Category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );
		$request->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->MetaData->WorkflowMetaData->State = $this->layoutStatus;
		$request->Targets[0] = new Target();
		$request->Targets[0]->PubChannel = new PubChannel( $this->pubChannelInfo->Id, $this->pubChannelInfo->Name );
		$request->Targets[0]->Issue = new Issue( $this->issueInfo->Id, $this->issueInfo->Name, null );
		$request->Targets[0]->Editions[] = $this->edition2;

		$service = new WflCopyObjectService();
		/** @var WflCopyObjectResponse $response */
		$response = $service->execute( $request );
		$copiedLayout = new Object();
		$copiedLayout->MetaData = $response->MetaData;
		$copiedLayout->Targets = $response->Targets;
		$copiedLayout->Relations = $response->Relations;
		return $copiedLayout;
	}
}