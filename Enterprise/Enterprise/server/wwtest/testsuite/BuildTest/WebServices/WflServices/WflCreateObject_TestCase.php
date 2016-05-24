<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflCreateObject_TestCase extends TestCase
{
	// Article properties used for testing
	private $articleStatusInfo = null;
	private $publicationInfo = null;
	private $categoryInfo = null;
	private $vars = null;
	private $objIDs = array(); // To remember the object Ids for deletion in the DeleteObjects_TestCase

	// Session related stuff	
	private $ticket = null;
		
	public function getDisplayName() { return 'Create Objects'; }
	public function getTestGoals()   { return 'Checks if Objects can be created successfully'; }
	public function getTestMethods() { return 'Call createObject service and see whether it returns newly created Object.'; }
	public function getPrio()        { return 7; }
	
	final public function runTest()
	{
		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$this->vars = $this->getSessionVariables();
   		$this->ticket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}

		$this->testCreateObject();
		$this->testCreateObjectWithoutStatus();
	}

	/**
	 * Test creating normal workflow objects.
	 * It remembers the object Id(s) created and store in the
	 * session [BuildTest_WebServices_WflServices][objIds] so that it gets
	 * deleted in the DeleteObjects test case.
	 */
	private function testCreateObject()
	{
		// Build two Article objects to test with. (Also used by WflDeleteObject test.)
		$articleObjs = array();
		for( $counter=0; $counter<2; $counter++ ) { 
			$articleName = 'Article _'. $counter . ' _' .date("m d H i s");
			$articleObj = $this->buildArticleObject( null, $articleName );
			if ( is_null( $articleObj )) {
				return; // error handled above
			}
			$articleObjs[] = $articleObj;
		}

		$this->uploadObjToTransferServer( $articleObjs );

		$objects = $this->callCreateObjectService( $articleObjs );

		$this->collectObjIdsForDeletion( $objects );
	}

	/**
	 * Same as testCreateObject() but this time, the Object to be created
	 * has no workflow status defined; The core is expected to 'repair' the
	 * workflow status given the object.
	 */
	private function testCreateObjectWithoutStatus()
	{
		// Build two Article objects to test with. (Also used by WflDeleteObject test.)
		$articleObjs = array();
		$articleName = 'Article _NoStatus_' .date("m d H i s");
		$articleObj = $this->buildArticleObject( null, $articleName );
		if ( is_null( $articleObj )) {
			return; // error handled above
		} else {
			$articleObj->MetaData->WorkflowMetaData->State = null;
		}
		$articleObjs[] = $articleObj;

		$this->uploadObjToTransferServer( $articleObjs );

		$objects = $this->callCreateObjectService( $articleObjs );

		$this->collectObjIdsForDeletion( $objects );
	}


	/**
	 * Upload article content to Transfer Server (no longer using DIME attachments).
	 * @param array $articleObjs List of objects where its file attachment are to be uploaded to the TransferServer.
	 */
	private function uploadObjToTransferServer( $articleObjs )
	{
		foreach( $articleObjs as $articleObj ) {
			$attachment = $articleObj->Files[0];
			$content = $attachment->Content;
			$attachment->Content = null;
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( !$transferServer->writeContentToFileTransferServer( $content, $attachment ) ) {
				$articleName = $articleObj->MetaData->BasicMetaData->Name;
				$this->setResult( 'ERROR', 'Failed uploading native file for article "'.$articleName.'".', 
					'Check the Transfer Server settings at the configserver.php file.' );
				return;
			}
	
			/* Commented out; We do not use the SoapClient, and so we should not use the TransferClient either! (=> Use both or use none.)
			require_once BASEDIR.'/server/utils/TransferClient.class.php';
			$transferClient = new WW_Utils_TransferClient();
			if( !$transferClient->uploadFile($attachment) ) {
				$articleName = $articleObj->MetaData->BasicMetaData->Name;
				$this->setResult( 'ERROR',  'Failed uploading native file for article "'.$articleName.'".', 'Check if all the Transfer Server settings are set in configserver.php.' );
				return;
			}*/
		}
	}

	/**
	 * Create object via CreateObejcts service call.
	 *
	 * @param array $articleObjs List of objects to be created via CreateObjects service call.
	 */
	private function callCreateObjectService( $articleObjs )
	{
		// Create the article objects at Enterprise DB
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
		$service = new WflCreateObjectsService();
		$request = new WflCreateObjectsRequest();
		$request->Ticket	= $this->ticket;
		$request->Lock		= false;
		$request->Objects	= $articleObjs;
		$objects = $service->execute( $request );

		return $objects;
	}

	/**
	 * Collect the object Ids created during test and store in the session 
	 * ['BuildTest_WebServices_WflServices']['objIds'] so that these objects
	 * get deleted (and so tested implicitly) during DeteleObjects_TestCase.
	 *
	 * @param array List of Objects to retrieve its objectId for deletion.
	 */
	private function collectObjIdsForDeletion( $objects )
	{
		// Collect object ids and temporary store it at the session.
		// This data is picked up by successor TestCase modules within this WflServices TestSuite.
		foreach ( $objects->Objects as $object ) {
			$this->objIDs[] = $object->MetaData->BasicMetaData->ID;
		}
		$this->vars['BuildTest_WebServices_WflServices']['objIds'] = $this->objIDs;
		$this->setSessionVariables( $this->vars );
	}
	
	
	/**
	 * Builds workflow object for an article.
	 *
	 * @param int $articleId
	 * @param string $articleName
	 * @return Object. Null on error. 
	 */
	private function buildArticleObject( $articleId, $articleName )
	{
		// Setup an attachment for the article that holds some plain text content (in memory)
		$content = 'To temos aut explabo. Ipsunte plat. Em accae eatur? Ihiliqui oditatem. Ro ipicid '.
			'quiam ex et quis consequae occae nihictur? Giantia sim alic te volum harum, audionseque '.
			'rem vite nobitas perrum faccuptias sunt fugit eliquatint velit a aut milicia consecum '.
			'veribus auda ides ut quia commosa quam et moles iscil mo conseque magnim quis ex ex eaquamet '.
			'ut adi dolor mo odis magnihi ligendit ut lam reperibusam quatumquam labor renis pe con eos '.
			'magnima gnatiur sitaepeles quatia namus ni aut adit at ad quundem laudia qui ut ratempe '.
			'rnatestorro te por alis acidunt volore nobit harciminum re eatus repudiatem ame prati bere '.
			'cus minveliquis serum, ute velecus cipiciur, occum nulpario quat fugitatur, nihillu ptatqui '.
			'ventibus doluptatur? Dus alique nonectoribus inciend elenim di sunt que mollis autempo ribus. '.
			'Totatent peliam aut facipsuntur aut pra quam es rem abo.';
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'text/plain';
		$attachment->Content = $content;

		// Build the article object (in memory)
		$fileSize = strlen($content);
		$meta = $this->buildArticleMetaData( $articleId, $articleName, $fileSize );
		if( !$meta ) {
			return null; // error handled above
		}
		$articleObj = new Object();
		$articleObj->MetaData = $meta;
		$articleObj->Files = array( $attachment );
		return $articleObj;
	}
	
	/**
	 * Builds workflow MetaData
	 *
	 * @param int $articleId
	 * @param string $articleName
	 * @param int $fileSize
	 * @return MetaData. Null on error.
	 */
	private function buildArticleMetaData( $articleId, $articleName, $fileSize )
	{
		// infos
		if( !$this->determinePublicationCategoryState() ) {
			return null; // error handled above
		}
		
		$publ = new Publication( $this->publicationInfo->Id, $this->publicationInfo->Name );
		$category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );

		// retrieve user (shortname) of the logOn test user.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->ticket );
		
		// build metadata
		$basMD = new BasicMetaData();
		$basMD->ID = $articleId;
		$basMD->DocumentID = null;
		$basMD->Name = $articleName;
		$basMD->Type = 'Article';
		$basMD->Publication = $publ;
		$basMD->Category = $category;

		$srcMD = new SourceMetaData();
		$srcMD->Author = $user;
		$rigMD = new RightsMetaData();
		$rigMD->Copyright = 'copyright';
		$cntMD = new ContentMetaData();
		$cntMD->Keywords = array("Key", "word");	
		$cntMD->Slugline = 'slug';
		$cntMD->Width = 123;
		$cntMD->Height = 45;
		$cntMD->Format = 'text/plain';
		$cntMD->FileSize = $fileSize;
		$cntMD->Columns = 4;
		$cntMD->LengthWords = 300;
		$cntMD->LengthChars = 1200;
		$cntMD->LengthParas = 4;
		$cntMD->LengthLines = 12;
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline = date('Y-m-d\TH:i:s'); 
		$wflMD->Urgency = 'Top';
		$wflMD->State = new State( $this->articleStatusInfo->Id, $this->articleStatusInfo->Name );
		$wflMD->RouteTo = $user;
		$wflMD->Comment = 'Creating Object for BuildTest';
		$extMD = array();

		$md = new MetaData();
		$md->BasicMetaData    = $basMD;
		$md->RightsMetaData   = $rigMD;
		$md->SourceMetaData   = $srcMD;
		$md->ContentMetaData  = $cntMD;
		$md->WorkflowMetaData = $wflMD;
		$md->ExtraMetaData    = $extMD;		
		return $md;
	}
	
	/**
	 * Based on the LogOn response, the returned Brand that matches the configured TESTSUITE['Brand']
	 * at configserver.php has been set in the session variable.
	 * This function searches through that Brand for a Category and Status that can be used for the article test.
	 *
	 * @return bool Whether or not all data could be determined.
	 */
	private function determinePublicationCategoryState()
	{
		// Init
		$this->publicationInfo = null;
		$this->categoryInfo = null;
		$this->articleStatusInfo = null;

		// Retrieve the Brand that has been determined by WflLogOn TestCase.
		$vars = $this->getSessionVariables();
		$pubInfo = @$vars['BuildTest_WebServices_WflServices']['publication'];
		if( !$pubInfo ) {
			$this->setResult( 'ERROR', 'Brand not determined (not set at test session).', 
				'Please enable the WflLogon test and make sure it runs successfully.' );
			return false;
		}

		// Simply pick the first Category of the Brand
		$categoryInfo = count( $pubInfo->Categories ) > 0  ? $pubInfo->Categories[0] : null;
		if( !$categoryInfo ) {
			$this->setResult( 'ERROR', 'Brand "'.$pubInfo->Name.'" has no Category to work with.', 
				'Please check the Brand Maintenance page and configure one.' );
			return false;
		}
	
		// Determine article status
		$articleStatusInfo = null;
		if( $pubInfo->States ) foreach( $pubInfo->States as $status ) {
			if( $status->Type == 'Article' ) {
				$articleStatusInfo = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$articleStatusInfo ) {
			$this->setResult( 'ERROR', 'Brand "'.$pubInfo->Name.'" has no Article Status to work with.', 
				'Please check the Brand Maintenance page and configure one.' );
			return false;
		}

		// All found; Init and tell caller.
		$this->publicationInfo = $pubInfo;
		$this->categoryInfo = $categoryInfo;
		$this->articleStatusInfo = $articleStatusInfo;
		return true;
	}
}