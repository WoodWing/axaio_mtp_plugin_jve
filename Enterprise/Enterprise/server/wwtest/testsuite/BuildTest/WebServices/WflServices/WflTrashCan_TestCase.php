<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflTrashCan_TestCase extends TestCase
{
	private $ticket = null; // Session ticket
	private $transferServer = null; // BizTransferServer
	
	// Image object details:
	private $imageName = null;
	private $imageId = null;
	private $imagePublication = null;
	private $imageCategory = null;
	private $imageStatus = null;
	private $imageDeleted = null;
	
	public function getDisplayName() { return 'Trash Can'; }
	public function getTestGoals()   { return 'Tests if an image object can be deleted, restored and deleted permanently. When the image is in the Trash Can, it checks if versions can be listed and retrieved.'; }
	public function getTestMethods() { return 'Scenario:<ol>
		<li>000: CS: Upload new image from Home tab and Check Out the image again. (WflCreateObjects)</li>
		<li>002: CS: Check-in the image. (WflSaveObjects)</li>
		<li>004: CS: Delete the image. (WflDeleteObjects)</li>
		<li>006: CS: Query the image in the Trash Can. (WflQueryObjects)</li>
		<li>007: CS: Query the image in the Trash Can with between operation. (WflQueryObjects)</li>
		<li>008: CS: List Versions of the image at the Trash Can. (WflListVersions)</li>
		<li>010: CS: View the last version of the image. (WflGetVersion)</li>
		<li>012: CS: Restore the image from the Trash Can. (WflRestoreObjects)</li>
		<li>014: CS: Search image in workflow. (WflQueryObjects)</li>
		<li>016: CS: Delete image permanently from Trash Can. (WflDeleteObjects)</li>
		<li>018: CS: Query image at Trash Can to check if no longer shown. (WflQueryObjects)</li>
		<li>020: CS: Search image at workflow to check if no longer shown. (WflQueryObjects)</li>
		</ol>'; }
	public function getPrio()        { return 10; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		
		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}
		
		// Retrieve the user
		$this->suiteOpts = unserialize( TESTSUITE );
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		$this->userFullname = BizUser::resolveFullUserName( $this->suiteOpts['User'] );
		
		// Determine the brand; Take the one from the session variables.
		$publicationInfo = @$vars['BuildTest_WebServices_WflServices']['publication'];
		if( !$publicationInfo ) {
			$this->setResult( 'ERROR', 'Brand not determined (not set at test session).', 
				'Please enable the WflLogon test and make sure it runs successfully.' );
			return;
		}
		$this->imagePublication = new Publication( $publicationInfo->Id, $publicationInfo->Name );

		// Simply pick the first Category of the Brand
		$categoryInfo = count( $publicationInfo->Categories ) > 0  ? $publicationInfo->Categories[0] : null;
		if( !$categoryInfo ) {
			$this->setResult( 'ERROR', 'Brand "'.$this->pubInfo->Name.'" has no Category to work with.', 
				'Please check the Brand Maintenance page and configure one.' );
			return;
		}
		$this->imageCategory = new Category( $categoryInfo->Id, $categoryInfo->Name );

		// Pick an image status from the brand.
		$this->imageStatus = null;
		if( $publicationInfo->States ) foreach( $publicationInfo->States as $status ) {
			if( $status->Type == 'Image' ) {
				$this->imageStatus = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$this->imageStatus ) {
			$this->setResult( 'ERROR', 'Brand "'.$this->imagePublication->Name.'" has no Image Status to work with.', 
				'Please check the Brand Maintenance page and configure one.' );
			return;
		}
		
		// Determine the image name.
		$this->imageName = 'TrashImg_'.date('m d H i s');
		
		// Run the test script.
		$this->testService000();
		$this->testService002();
		$this->testService004();
		$this->testService006();
		$this->testService007();
		$this->testService008();
		$this->testService010();
		$this->testService012();
		$this->testService014();
		$this->testService016();
		$this->testService018();
		$this->testService020();
	}

	// - - - - - - - - - - - - - - - - - - 
	// 000: CS: Upload new image from Home tab and Check Out the image again.
	// - - - - - - - - - - - - - - - - - - 

	private function testService000()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->getRecordedRequest000();
		$recResp = $this->getRecordedResponse000();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#000');
		$this->imageId = $curResp->Objects[0]->MetaData->BasicMetaData->ID;

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$ignorePaths = array( 
			'WflCreateObjectsResponse->Objects[0]->MetaData->ExtraMetaData' => true, // differs per DB
			'WflCreateObjectsResponse->Objects[0]->MetaData->BasicMetaData->ID' => true, // to be determined
			'WflCreateObjectsResponse->Objects[0]->MetaData->WorkflowMetaData->State->Produce' => true, // server bug?
		);
		$phpCompare->initCompare( $ignorePaths, $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '000' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '000' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateObjects response.');
			return;
		}
	}

	private function getRecordedRequest000()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->imageName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->imagePublication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->imageCategory;
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
		$request->Objects[0]->MetaData->ContentMetaData->Columns = null;
		$request->Objects[0]->MetaData->ContentMetaData->Width = '24';
		$request->Objects[0]->MetaData->ContentMetaData->Height = '24';
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = '72';
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = null;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1385;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values[0] = '';
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		return $request;
	}
	
	private function getRecordedResponse000()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = null; // TBD
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = '';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->imageName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = $this->imagePublication;
		$response->Objects[0]->MetaData->BasicMetaData->Category = $this->imageCategory;
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
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '24';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '24';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '72';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '1385';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->userFullname;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2012-06-28T17:18:11';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->userFullname;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2012-06-28T17:18:11';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = $this->userFullname;
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Reports = array();
		return $response;
	}

	// - - - - - - - - - - - - - - - - - - 
	// 002: CS: Check-in the image.
	// - - - - - - - - - - - - - - - - - - 
	
	private function testService002()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getRecordedRequest002();
		$recResp = $this->getRecordedResponse002();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#002');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$ignorePaths = array(
			'WflSaveObjectsResponse->Objects[0]->MetaData->ExtraMetaData' => true, // differs per DB
			'WflSaveObjectsResponse->Objects[0]->MetaData->WorkflowMetaData->State->Produce' => true, // server bug?
		);
		$phpCompare->initCompare( $ignorePaths, $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '002' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '002' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflSaveObjects response.');
			return;
		}
	
	}

	private function getRecordedRequest002()
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
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->imageId;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->imageName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->imagePublication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->imageCategory;
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
		$request->Objects[0]->MetaData->ContentMetaData->Columns = null;
		$request->Objects[0]->MetaData->ContentMetaData->Width = '24';
		$request->Objects[0]->MetaData->ContentMetaData->Height = '24';
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = '72';
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = null;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 1385;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->ContentMetaData->Orientation = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = ',';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->InDesignArticles = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}
	
	private function getRecordedResponse002()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = $this->imageId;
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = '';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->imageName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = $this->imagePublication;
		$response->Objects[0]->MetaData->BasicMetaData->Category = $this->imageCategory;
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
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '24';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '24';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '72';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '1385';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->ContentMetaData->Orientation = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = $this->userFullname;
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2012-06-28T17:21:35';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = $this->userFullname;
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2012-06-28T17:18:11';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = ',';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.2';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Reports = array();
		return $response;
	}

	// - - - - - - - - - - - - - - - - - - 
	// 004: CS: Delete the image.
	// - - - - - - - - - - - - - - - - - - 
	
	private function testService004()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->getRecordedRequest004();
		$recResp = $this->getRecordedResponse004();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#004');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '004' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '004' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteObjects response.');
			return;
		}
	
	}

	private function getRecordedRequest004()
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->imageId;
		$request->Permanent = false;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		return $request;
	}

	private function getRecordedResponse004()
	{
		$response = new WflDeleteObjectsResponse();
		$response->IDs = array();
		$response->IDs[0] = $this->imageId;
		$response->Reports = array();
		return $response;
	}
	
	// - - - - - - - - - - - - - - - - - - 
	// 006: CS: Query the image in the Trash Can.
	// - - - - - - - - - - - - - - - - - - 

	private function testService006()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req = $this->getRecordedRequest_QueryImage( false ); // From Trash
		//$recResp = $this->getRecordedResponse006();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#006');

		if( $curResp->Rows[0][0] != $this->imageId ) {
			$errorMsg = 'Could not find the image (id='.$this->imageId.') in Trash Can.';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflQueryObjects response.');
		} else {
			// Get deleted datetime value
			foreach( $curResp->Columns as $key => $column ) {
				if( $column->Name == 'Deleted' ) {
					$this->imageDeleted = $curResp->Rows[0][$key];
					break;
				}
			}
		}
	}


	// - - - - - - - - - - - - - - - - - - 
	// 007: CS: Query the Trash Can with between operation
	// - - - - - - - - - - - - - - - - - - 

	private function testService007()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req = $this->getRecordedRequest007();
		//$recResp = $this->getRecordedResponse007();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#007');

		if( $curResp->Rows[0][0] != $this->imageId ) {
			$errorMsg = 'Could not find the image (id='.$this->imageId.') in Trash Can.';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflQueryObjects response.');
		}
	}

	private function getRecordedRequest007()
	{
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Params = array();
		$request->Params[0] = new QueryParam();
		$request->Params[0]->Property = 'ID';
		$request->Params[0]->Operation = '=';
		$request->Params[0]->Value = $this->imageId;
		$request->Params[0]->Special = null;
		$request->Params[1] = new QueryParam();
		$request->Params[1]->Property = 'Deleted';
		$request->Params[1]->Operation = 'between';
		$request->Params[1]->Value = $this->imageDeleted;
		$request->Params[1]->Special = null;
		$request->Params[1]->Value2 = $this->imageDeleted;
		$request->FirstEntry = 1;
		$request->MaxEntries = null;
		$request->Hierarchical = false;
		$request->Order = null;
		$request->MinimalProps = array();
		$request->MinimalProps[0] = 'PublicationId';
		$request->MinimalProps[1] = 'StateId';
		$request->MinimalProps[2] = 'State';
		$request->MinimalProps[3] = 'Format';
		$request->MinimalProps[4] = 'DocumentID';
		$request->MinimalProps[5] = 'LockedBy';
		$request->MinimalProps[6] = 'Slugline';
		$request->RequestProps = null;
		$request->Areas = array();
		$request->Areas[0] = 'Trash';
		return $request;
	}

	// - - - - - - - - - - - - - - - - - - 
	// 008: CS: List Versions of the image at the Trash Can.
	// - - - - - - - - - - - - - - - - - - 
	
	private function testService008()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflListVersionsService.class.php';
		$req = $this->getRecordedRequest008();
		$recResp = $this->getRecordedResponse008();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#008');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '008' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '008' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflListVersions response.');
			return;
		}
	
	}

	private function getRecordedRequest008()
	{
		$request = new WflListVersionsRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $this->imageId;
		$request->Rendition = 'thumb';
		$request->Areas = array();
		$request->Areas[0] = 'Trash';
		return $request;
	}
	
	private function getRecordedResponse008()
	{
		$response = new WflListVersionsResponse();
		$response->Versions = array();
		$response->Versions[0] = new VersionInfo();
		$response->Versions[0]->Version = '0.1';
		$response->Versions[0]->User = $this->userFullname;
		$response->Versions[0]->Comment = '';
		$response->Versions[0]->Slugline = '';
		$response->Versions[0]->Created = '2012-06-28T17:18:11';
		$response->Versions[0]->Object = $this->imageName;
		$response->Versions[0]->State = $this->imageStatus;
		$response->Versions[0]->File = new Attachment();
		$response->Versions[0]->File->Rendition = 'thumb';
		$response->Versions[0]->File->Type = 'image/jpeg';
		$response->Versions[0]->File->Content = null;
		$response->Versions[0]->File->FilePath = '';
		$response->Versions[0]->File->FileUrl = null;
		$response->Versions[0]->File->EditionId = null;
		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->Versions[0]->File );
		$response->Versions[1] = new VersionInfo();
		$response->Versions[1]->Version = '0.2';
		$response->Versions[1]->User = $this->userFullname;
		$response->Versions[1]->Comment = ',';
		$response->Versions[1]->Slugline = '';
		$response->Versions[1]->Created = '2012-06-28T17:21:35';
		$response->Versions[1]->Object = $this->imageName;
		$response->Versions[1]->State = $this->imageStatus;
		$response->Versions[1]->File = new Attachment();
		$response->Versions[1]->File->Rendition = 'thumb';
		$response->Versions[1]->File->Type = 'image/jpeg';
		$response->Versions[1]->File->Content = null;
		$response->Versions[1]->File->FilePath = '';
		$response->Versions[1]->File->FileUrl = null;
		$response->Versions[1]->File->EditionId = null;
		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->Versions[1]->File );
		return $response;
	}
	
	// - - - - - - - - - - - - - - - - - - 
	// 010: CS: View the last version of the image.
	// - - - - - - - - - - - - - - - - - - 

	private function testService010()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetVersionService.class.php';
		$req = $this->getRecordedRequest010();
		$recResp = $this->getRecordedResponse010();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#010');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '010' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '010' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetVersion response.');
			return;
		}
	
	}

	private function getRecordedRequest010()
	{
		$request = new WflGetVersionRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $this->imageId;
		$request->Version = '0.1';
		$request->Rendition = 'preview';
		$request->Areas = array();
		$request->Areas[0] = 'Trash';
		return $request;
	}
	
	private function getRecordedResponse010()
	{
		$response = new WflGetVersionResponse();
		$response->VersionInfo = new VersionInfo();
		$response->VersionInfo->Version = '0.1';
		$response->VersionInfo->User = $this->userFullname;
		$response->VersionInfo->Comment = '';
		$response->VersionInfo->Slugline = '';
		$response->VersionInfo->Created = '2012-06-28T17:18:11';
		$response->VersionInfo->Object = $this->imageName;
		$response->VersionInfo->State = $this->imageStatus;
		$response->VersionInfo->File = new Attachment();
		$response->VersionInfo->File->Rendition = 'preview';
		$response->VersionInfo->File->Type = 'image/jpeg';
		$response->VersionInfo->File->Content = null;
		$response->VersionInfo->File->FilePath = '';
		$response->VersionInfo->File->FileUrl = null;
		$response->VersionInfo->File->EditionId = null;
		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $response->VersionInfo->File );
		return $response;
	}

	// - - - - - - - - - - - - - - - - - - 
	// 012: CS: Restore the image from the Trash Can.
	// - - - - - - - - - - - - - - - - - - 

	private function testService012()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';
		$req = $this->getRecordedRequest012();
		$recResp = $this->getRecordedResponse012();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#012');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '012' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '012' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflQueryObjects response.');
			return;
		}
	
	}

	private function getRecordedRequest012()
	{
		$request = new WflRestoreObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->imageId;
		return $request;
	}
	
	private function getRecordedResponse012()
	{
		$response = new WflRestoreObjectsResponse();
		$response->IDs = array();
		$response->IDs[0] = $this->imageId;
		$response->Reports = array();
		return $response;
	}
	
	// - - - - - - - - - - - - - - - - - - 
	// 014: CS: Search image in workflow.
	// - - - - - - - - - - - - - - - - - - 

	private function testService014()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req = $this->getRecordedRequest_QueryImage( true );
		//$recResp = $this->getRecordedResponse014();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#014');

		if( $curResp->Rows[0][0] != $this->imageId ) {
			$errorMsg = 'Could not find the image (id='.$this->imageId.') in Search results.';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflQueryObjects response.');
		}
	}

	private function getRecordedRequest_QueryImage( $atWorkflow )
	{
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Params = array();
		$request->Params[0] = new QueryParam();
		$request->Params[0]->Property = 'ID';
		$request->Params[0]->Operation = '=';
		$request->Params[0]->Value = $this->imageId;
		$request->Params[0]->Special = null;
		$request->FirstEntry = 1;
		$request->MaxEntries = null;
		$request->Hierarchical = false;
		$request->Order = null;
		$request->MinimalProps = array();
		$request->MinimalProps[0] = 'PublicationId';
		$request->MinimalProps[1] = 'StateId';
		$request->MinimalProps[2] = 'State';
		$request->MinimalProps[3] = 'Format';
		$request->MinimalProps[4] = 'LockedBy';
		$request->RequestProps = null;
		$request->Areas = array();
		$request->Areas[0] = $atWorkflow ? 'Workflow' : 'Trash';
		return $request;
	}

	// - - - - - - - - - - - - - - - - - - 
	// 016: CS: Delete image permanently from Trash Can.
	// - - - - - - - - - - - - - - - - - - 
	
	private function testService016()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->getRecordedRequest016();
		$recResp = $this->getRecordedResponse016();
	
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#016');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '016' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '016' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteObjects response.');
			return;
		}
	
	}

	private function getRecordedRequest016()
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->imageId;
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		return $request;
	}
	
	private function getRecordedResponse016()
	{
		$response = new WflDeleteObjectsResponse();
		$response->IDs = array();
		$response->IDs[0] = $this->imageId;
		$response->Reports = array();
		return $response;
	}

	// - - - - - - - - - - - - - - - - - - 
	// 018: CS: Query image at Trash Can to check if no longer shown.
	// - - - - - - - - - - - - - - - - - - 
	
	private function testService018()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req = $this->getRecordedRequest_QueryImage( false ); // at trash?

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();
		$curResp = $testSuitUtils->callService( $this, $req, 'testService#018');

		if( count( $curResp->Rows ) > 0 ) {
			$errorMsg = 'Image (id='.$this->imageId.') can still be found at Trash Can.';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflQueryObjects response.');
		}
	}

	// - - - - - - - - - - - - - - - - - - 
	// 020: CS: Search image at workflow and check if no longer shown.
	// - - - - - - - - - - - - - - - - - - 
	
	private function testService020()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req = $this->getRecordedRequest_QueryImage( true ); // at workflow?

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$testSuitUtils = new WW_Utils_TestSuite();

		$curResp = $testSuitUtils->callService( $this, $req, 'testService#020');
		if( count( $curResp->Rows ) > 0 ) {
			$errorMsg = 'Image (id='.$this->imageId.') can still be found at Trash Can.';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflQueryObjects response.');
		}
	}


	private function getCommonPropDiff()
	{
		// ColorSpace is excluded because different image preview app gives different ColorSpace value
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true, 
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true, 'ColorSpace' => true, 'Placements' => true,
		);
	}
}
