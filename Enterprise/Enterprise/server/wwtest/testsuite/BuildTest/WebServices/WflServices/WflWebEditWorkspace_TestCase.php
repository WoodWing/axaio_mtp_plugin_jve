<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflWebEditWorkspace_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;      // User ticket of current PHP session.
	private $publication = null;   // Brand that owns the article.
	private $category = null;      // Category wherein the article will be put.
	private $articleStatus = null; // Workflow status of the article.
	private $printTarget = null;   // Object target the article is assigned to.

	//scenario 1
	private $createdArticle = null;
	private $createdWorkspace = null;

	private $workspaceId = null; // Current workspace GUID used to store article being edit.
	private $content = null;     // Article WCML content (XML).
	private $elements = null;    // List of article WCML stories (array of strings).
	private $articleId = null;   // Object ID of article being edit. NULL to indicate article does not exist in DB (yet).
	private $format = null;      // Article file format. Supported are 'application/incopy' (WWCX) and 'application/incopyicml' (WCML)
	private $nthStory = 0;       // To get the story at index 0 from /ea:stories/ea:story
	private $editionId = null;

	//scenario 2
	private $layouts = array();
	private $articles = array();

	private $multipleArticlesWorkspaceId = null;
	private $transferServer = null; // BizTransferServer
	private $articleFormat = 'application/incopyicml';
	private $currentResponsePreview = null;

	public function getDisplayName() { return 'Web Edit Workspace'; }
	public function getTestGoals()   { return 'Checks if the workspace services are works well.'; }
	public function getTestMethods() { return
		'Call all workspace services and see whether it returns a good data structure. <br><br>

	   Scenario 1:
	   <ul>
			<li>CS: Create new article</li>
			<li>CS: Save article</li>
			<li>CS: Preview article</li>
			<li>CS: Edit article</li>
			<li>CS: Preview article</li>
			<li>CS: Get PDF version of preview</li>
	   </ul>

		Scenario 2:
		<ul>
			<li>ID: Create layout, place two articles and checkin all.</li>
			<li>CS: Open both articles for editing.</li>
			<li>CS: Change content of both articles (but no save).</li>
			<li>CS: Preview => Changes of both articles should be shown.</li>
			<li>CS: Save article 1.</li>
			<li>CS: Preview => Changes of both articles should be shown.</li>
			<li>CS: Save article 2.</li>
			<li>CS: Preview => Changes of both articles should be shown.</li>
		</ul>

		Scenario 3:
		<ul>
			<li>Create new article A1 in workspace</li>
			<li>Save A1 in database and workspace</li>
			<li>Compose article A2 (no create, no save)</li>
			<li>Try to save article A2 in workspace</li>
			<li>=> Should raise error "..."</li>
			<li>Create new layout LA and checkin</li>
			<li>Place A1 on layout LA</li>
			<li>Try to save article A2 in workspace</li>
			<li>=> Should raise error "..."</li>
		</ul>

		'
		; }
	public function getPrio() { return 107; }

	/**
	 * Entry point of this TestCase called by the core server to run the test.
	 */
	final public function runTest()
	{
		// Bail out when there are no InDesign Servers configured at all.
		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Search for available InDesign Servers.' );
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		$idServers = BizInDesignServer::listInDesignServers();
		if( $idServers ) {
			$idsFound = false;
			foreach( $idServers as $idServer ) {
				if( $idServer->Active ) {
					$idsFound = true;
					break;
				}
			}
			if( !$idsFound ) {
				$this->setResult( 'ERROR', 'None of the configured InDesign Servers is set Active.',
					'Please check the InDesign Servers admin page.' );
				return;
			}
		} else {
			$this->setResult( 'ERROR', 'There are no InDesign Servers configured.',
				'Please check the InDesign Server admin page.' );
			return;
		}

		do {
			if( !$this->setupTestData() ) {
				break;
			}
			if( !$this->scenario_01() ) {
				break;
			}
			// Disabled these tests on Work1 because the test server does not have the correct IDServer installed TODO: Enable with CC2017
//			if( !$this->scenario_02() ) {
//				break;
//			}
//			if( !$this->scenario_03() ) {
//				break;
//			}
		} while( false ); // only once

		$this->tearDownTestData();
	}

	/**
	 * Setup all the general needed data for this test.
	 *
	 * @return bool
	 */
	private function setupTestData()
	{
		require_once BASEDIR . '/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		// Retrieve the ticket and config / test data that has been determined by the LogOn TestCase.
		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Init session variables.' );
		$vars = $this->getSessionVariables();
		$this->ticket = @$vars['BuildTest_WebServices_WflServices']['ticket'];
		$publicationInfo   = $vars['BuildTest_WebServices_WflServices']['publication'];
		$categoryInfo      = $vars['BuildTest_WebServices_WflServices']['category'];
		$artStatusInfo     = $vars['BuildTest_WebServices_WflServices']['articleStatus'];
		$this->printTarget = $vars['BuildTest_WebServices_WflServices']['printTarget'];
		if( !$this->ticket || !$publicationInfo || !$categoryInfo || !$artStatusInfo || !$this->printTarget ) {
			$this->setResult( 'ERROR', 'Could not find test data to work on.',
				'Please enable the "Setup test data" entry and try again.' );
			return false;
		}

		// Prepare brand, catergory and status to be used later for image object creation.
		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Setup mocked data.' );
		$this->publication = new Publication( $publicationInfo->Id, $publicationInfo->Name );
		$this->category = new Category( $categoryInfo->Id, $categoryInfo->Name );
		$this->articleStatus = new State( $artStatusInfo->Id, $artStatusInfo->Name );

		return true;
	}

	/**
	 * Tear down all the data generated in this test.
	 */
	private function tearDownTestData()
	{
		// Scenario 1
		if( $this->createdArticle ) {
			LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Delete article from workspace.' );
			$this->deleteArticleAtDB(); // call deleteObjects service.
		}

		if( $this->createdWorkspace ) {
			LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Delete article workspace.' );
			$this->deleteArticleWorkspace();
		}

		//Clear data for scenario 2 when failed or scenario 3;
		$this->deleteScenarioFiles();
	}

	/**
	 * Run scenario 1: Single article preview
	 */
	private function scenario_01()
	{
		$this->format = $this->articleFormat; // WCML (CS5)
		$this->articleId = null;
		$this->content = $this->getTestFileXmlContent( 'article_02');
		$this->elements = null;

		// Create article in workspace.
		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Create + Get article content #02 at workspace.' );
		$this->createdWorkspace = is_null( $this->workspaceId );
		if( $this->createdWorkspace ) {
			$this->createArticleWorkspace();
		}
		$this->getArticleFromWorkspace(); // get the created article

		// Create the article in the DB for preview / pdf operations to work. The article file in the
		// 'testdata' folder is with geometrical information (created from article template placed on layout),
		// so that this testscript doesn't need to provide a layout for IDS to do preview/pdf creation.
		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Create article content #02 into database.' );
		$this->createdArticle = is_null($this->articleId);
		if( $this->createdArticle ) {
			$this->createArticleAtDB(); // call createObjects service
		}

		// Testing whole story
		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Save + Get article content #02 at workspace.' );
		$this->editionId = $this->printTarget->Editions[0] ? $this->printTarget->Editions[0]->Id : null;
		$this->saveArticleInWorkspace();
		$this->getArticleFromWorkspace(); // get the saved article

		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Preview article content #02 at workspace.' );
		$this->previewArticleAtWorkspace( 'Preview', true ); // true for passing 'Contents' into preview service call.
		$this->getArticleFromWorkspace(); // get previewed

		// Testing Elements
		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Save + Get article content #03 into workspace.' );
		$this->editionId = $this->printTarget->Editions[1] ? $this->printTarget->Editions[1]->Id : null;
		$this->content = null;
		$this->elements = array( $this->getStoryContent( 'article_03', $this->nthStory ) );
		$this->saveArticleInWorkspace();
		$this->getArticleFromWorkspace(); // get the saved article (implicitly through Preview)

		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'Preview article content #03 at workspace.' );
		$this->previewArticleAtWorkspace( 'PDF', false ); // false for passing 'Elements' into preview service call.
		$this->getArticleFromWorkspace(); // get the saved article (implicitly through PDF)

		LogHandler::Log( 'WebEditWorkspace', 'INFO', 'List article workspaces.' );
		$this->listArticleWorkspaces();

		return true;
	}

	/**
	 * Run the script for scenario 2 described in the test methods.
	 *
	 * @return bool
	 */
	private function scenario_02()
	{
		if( !$this->createLayOut( 0 ) ){
			return false;
		}

		// Create an article
		if( !$this->createArticle( 0 ) ){
			return false;
		}

		// Create a second article
		if( !$this->createArticle( 1 ) ){
			return false;
		}

		// Place the first article on the layout
		if( !$this->createObjectRelationsArticle( 0, 0 ) ){
			return false;
		}

		// Place the second article on the layout
		if( !$this->createObjectRelationsArticle( 1, 0 ) ){
			return false;
		}

		if( !$this->saveLayout( 0 )){
			return false;
		}

		// Create workspace and edit the 2 articles (but no save), then preview them.
		if( !$this->editAndPreviewArticles() ){
			return false;
		}

		return true;
	}

	/**
	 * Run the script for scenario 3 described in the test methods.
	 *
	 * @return bool
	 */
	private function scenario_03()
	{
		$this->deleteScenarioFiles(); // Delete all the files for scenario 2 when succeeded

		if( !$this->testUnsavedArticle() ){
			return false;
		}

		return true;
	}


	/**
	 * Tests the CreateArticleWorkspace workflow service
	 */
	private function createArticleWorkspace()
	{
		require_once BASEDIR . '/server/services/wfl/WflCreateArticleWorkspaceService.class.php';
		// Prepare the request data
		$request = new WflCreateArticleWorkspaceRequest();
		$request->Ticket      = $this->ticket;
		$request->ID          = $this->articleId;
		$request->Format      = $this->format;
		$request->Content     = $this->content;
		$response = $this->runService( $request, 'Create article at workspace.' );

		// Validate the response
		$this->workspaceId = null;
		if( isset($response->WorkspaceId) ) {
			require_once BASEDIR . '/server/utils/NumberUtils.class.php';
			if( NumberUtils::validateGUID( $response->WorkspaceId ) ) {
				$this->workspaceId = $response->WorkspaceId;
			} else {
				$this->setResult( 'ERROR',
					'CreateArticleWorkspace: Invalid WorkspaceId returned: "'.$response->WorkspaceId.'".' );
			}
		}
		if( !$this->workspaceId ) {
			$this->setResult( 'ERROR', 'CreateArticleWorkspace: No WorkspaceId returned.' );
		}
	}

	/**
	 * Tests the SaveArticleInWorkspace workflow service
	 */
	private function saveArticleInWorkspace()
	{
		require_once BASEDIR . '/server/services/wfl/WflSaveArticleInWorkspaceService.class.php';
		// Prepare the request data
		$request = new WflSaveArticleInWorkspaceRequest();
		$request->Ticket      = $this->ticket;
		$request->WorkspaceId = $this->workspaceId;
		$request->ID          = $this->articleId;
		$request->Format      = $this->format;
		$request->Elements    = $this->elements;
		$request->Content     = $this->content;
		$this->runService( $request, 'Save article at workspace.' );
	}

	/**
	 * Tests the PreviewArticleAtWorkspace workflow service
	 *
	 * @param string $action
	 * @param bool $fullStory
	 */
	private function previewArticleAtWorkspace( $action, $fullStory )
	{
		require_once BASEDIR . '/server/services/wfl/WflPreviewArticleAtWorkspaceService.class.php';
		// Prepare the request data
		$request = new WflPreviewArticleAtWorkspaceRequest();
		$request->Ticket      = $this->ticket;
		$request->WorkspaceId = $this->workspaceId;
		$request->ID          = $this->articleId;
		$request->Format      = $this->format;
		$request->Elements    = $fullStory ? null : $this->elements;
		$request->Content     = $fullStory ? $this->content : null;
		$request->Action      = $action;
		$request->LayoutId    = null;
		$request->EditionId   = $this->editionId;
		$request->PreviewType = 'page'; // v7.6 feature: 'page' or 'spread'
		/*$response =*/ $this->runService( $request, 'Preview article at workspace.' );
	}

	/**
	 * Tests the ListArticleWorkspaces workflow service
	 */
	private function listArticleWorkspaces()
	{
		// Call the service.
		require_once BASEDIR . '/server/services/wfl/WflListArticleWorkspacesService.class.php';
		$request = new WflListArticleWorkspacesRequest();
		$request->Ticket = $this->ticket;
		$response = $this->runService( $request, 'List article workspace.' );

		// Validate the response.
		$found = false;
		require_once BASEDIR . '/server/utils/NumberUtils.class.php';
		if( isset($response->Workspaces) && $response->Workspaces ) {
			foreach( $response->Workspaces as $workspaceId ) {
				if( NumberUtils::validateGUID( $workspaceId ) ) {
					if( $this->workspaceId == $workspaceId ) {
						$found = true;
					}
				} else {
					$this->setResult( 'ERROR',
						'ListArticleWorkspaces: Invalid WorkspaceId returned: "'.$request->WorkspaceId.'".' );
				}
			}
		} else {
			$this->setResult( 'ERROR', 'ListArticleWorkspaces: No WorkspaceIds returned.' );
		}
		if( !$found ) {
			$this->setResult( 'ERROR',
				'ListArticleWorkspaces: Could not find WorkspaceId at response: "'.$this->workspaceId.'".' );
		}
	}

	/**
	 * Tests the GetArticleFromWorkspace workflow service
	 */
	private function getArticleFromWorkspace()
	{
		// Prepare the request data
		require_once BASEDIR . '/server/services/wfl/WflGetArticleFromWorkspaceService.class.php';
		$request = new WflGetArticleFromWorkspaceRequest();
		$request->Ticket      = $this->ticket;
		$request->WorkspaceId = $this->workspaceId;
		$response = $this->runService( $request, 'Get article from workspace.' );

		// Validate the response
		if( $response->ID != $this->articleId ) {
			$this->setResult( 'ERROR', 'GetArticleFromWorkspace: '.
				'Bad object id returned: "'.$response->ID.'", expected: "'.$this->articleId.'".' );
		}
		if( $response->Format != $this->format ) {
			$this->setResult( 'ERROR', 'GetArticleFromWorkspace: '.
				'Format returned: "'.$response->Format.'", expected: "'.$this->format.'".' );
		}
		// When  $this->content and the response content differs,
		// check on the changed element in the response instead.
		if( $response->Content != $this->content ) {
			$icDoc = new DOMDocument();
			$icDoc->loadXML( $response->Content );
			$xpath = new DOMXPath($icDoc);
			$xpath->registerNamespace('ea', 'urn:EnterpriseArticle');
			$icStories = $xpath->query( '//ea:Stories/ea:Story' );
			$icStory = $icStories->item( $this->nthStory );
			$icStory->setAttribute( 'xmlns:ea', 'urn:SmartConnection_v3' );

			// Raise error when the content differs.
			if( $this->elements[0]->Content != $icDoc->saveXML( $icStory ) ){
				$this->setResult( 'ERROR', 'GetArticleFromWorkspace: '.
					' Content returned: "'.htmlentities($response->Content).
					'", expected: "'.htmlentities($this->content).'".' );
			}
		}
	}

	/**
	 * Tests the DeleteArticleWorkspace workflow service
	 */
	private function deleteArticleWorkspace()
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteArticleWorkspaceService.class.php';
		// Prepare the request data
		$request = new WflDeleteArticleWorkspaceRequest();
		$request->Ticket      = $this->ticket;
		$request->WorkspaceId = $this->workspaceId;
		$this->runService( $request, 'Delete article from workspace.' );
	}

	/**
	 * Load the file contents as XML, and return contents using saveXML
	 * In this case, the file content that will use for comparison later will found same
	 *
	 * @param string $fileName The file base name at testdata folder.
	 * @return string XML content of the file.
	 */
	private function getTestFileXmlContent( $fileName )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$fileExt = MimeTypeHandler::mimeType2FileExt( $this->format, 'Article' );
		$filePath = dirname(__FILE__).'/testdata/' . $fileName .$fileExt;

		$icDoc = new DOMDocument();
		$icDoc->loadXML( file_get_contents( $filePath ) );
		return $icDoc->saveXML();
	}

	/**
	 * Extract Nth story ($nthStory) from given article content ($fileName).
	 *
	 * @param string $fileName The file base name at testdata folder.
	 * @param int $nthStory Nth story with first story starts from index 0.
	 * @return object Element of $nthStory story (with Content in embedded XML format).
	 */
	private function getStoryContent( $fileName, $nthStory )
	{
		$icDoc = new DOMDocument();
		$icDoc->loadXML( $this->getTestFileXmlContent( $fileName ) );
		$xpath = new DOMXPath($icDoc);

		$element = new Element();
		$xpath->registerNamespace('ea', "urn:EnterpriseArticle");
		$icStories = $xpath->query( '//ea:Stories/ea:Story' );
		$icStory = $icStories->item( $nthStory );
		$icStory->setAttribute( 'xmlns:ea', "urn:SmartConnection_v3" );
		$element->ID = $icStory->getAttribute( 'ea:GUID' );
		$element->Content = $icDoc->saveXML( $icStory );
		return $element;
	}

	/**
	 * Create object by getting the file content from InCopy document. ($this->content)
	 * This is needed for IDS to generate image/PDF for preview/PDF download of the article.
	 * IDS won't be able to do for non saved article.
	 */
	private function createArticleAtDB()
	{
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		// Compose object name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$objectName = 'WEW article CS5 '.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;

		// Build MetaData
		$basicMD         = new BasicMetaData();
		$basicMD->Name   = $objectName;
		$basicMD->Type   = 'Article';
		$basicMD->Publication = $this->publication;
		$basicMD->Category    = $this->category;

		$wflMD           = new WorkflowMetaData();
		$wflMD->State    = $this->articleStatus;

		$contentMD           = new ContentMetaData();
		$contentMD->Format   = $this->format;
		$contentMD->FileSize = strlen( $this->content );

		$metaData        = new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $contentMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();

		// Create file content
		$fileAttachment = new Attachment();
		$fileAttachment->Rendition = 'native';
		$fileAttachment->Type      = $this->format;

		$transferServer = new BizTransferServer();
		$fileAttachment = new Attachment( 'native', $this->format );
		$transferServer->writeContentToFileTransferServer( $this->content, $fileAttachment );

		// Compose article object
		$object = new Object();
		$object->MetaData = $metaData;
		$object->Relations = array();
		$object->Files     = array( $fileAttachment );

		// Create article object.
		$request          = new WflCreateObjectsRequest();
		$request->Ticket  = $this->ticket;
		$request->Lock    = false;
		$request->Objects = array( $object );
		$response = $this->runService( $request, 'Create article in database.' );
		$this->articleId = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		if( $this->articleId ) {
			LogHandler::Log( 'WebEditWorkspace', 'DEBUG', 'Article Id Created:'.$this->articleId );
		}
	}

	/**
	 * Delete the object created via createArticleAtDB().
	 *
	 */
	private function deleteArticleAtDB()
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';
		$request            = new WflDeleteObjectsRequest();
		$request->Ticket    = $this->ticket;
		$request->IDs       = array( $this->articleId );
		$request->Permanent = true;
		$this->runService( $request , 'Delete article from database.' );
	}

	/**
	 * Calls a web service.
	 *
	 * @param object $request
	 * @param string $stepInfo Additional info to log.
	 * @param string $expectedError an expected error can be suppressed.
	 * @return object Response data object
	 */
	private function runService( $request, $stepInfo, $expectedError = null )
	{
		$response = $this->utils->callService( $this, $request, $stepInfo, $expectedError );
		if( isset($response->Reports[0]) ) { // Introduced in v8.0 (only certain services support ErrorReports)
			$errMsg = '';
			foreach( $response->Reports as $report ){
				foreach( $report->Entries as $reportEntry ) {
					$errMsg .= $reportEntry->Message . PHP_EOL;
				}
			}
			$serviceName = get_class( $request ); // e.g. returns 'WflDeleteObjectsRequest'
			$serviceName = substr( $serviceName, strlen('Wfl'), strlen($serviceName) - strlen('Wfl') - strlen('Request') );
			$this->setResult( 'ERROR', $serviceName.': failed: "'.$errMsg.'"' );
		}
		return $response;
	}

	/**
	 * Delete all the files that are generated in scenario 2 or 3
	 */
	private function deleteScenarioFiles()
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';
		require_once BASEDIR . '/server/services/wfl/WflDeleteArticleWorkspaceService.class.php';
		$ids = array();

		if( $this->articles ) foreach( $this->articles as $article ){
			$ids[] = $article->MetaData->BasicMetaData->ID;
		}

		$this->articles = array();

		if( $this->layouts ) foreach( $this->layouts as $layout ){
			$ids[] = $layout->MetaData->BasicMetaData->ID;
		}

		$this->layouts = array();

		if( $ids ){
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs = $ids;
			$request->Permanent = true;
			$this->runService( $request, 'WflWebEditWorkspace - delete articles and layout' );
		}

		if( $this->multipleArticlesWorkspaceId ){
			$request = new WflDeleteArticleWorkspaceRequest();
			$request->Ticket = $this->ticket;
			$request->WorkspaceId = $this->multipleArticlesWorkspaceId;
			$this->runService( $request, 'WflWebEditWorkspace -  delete workspace' );
			$this->multipleArticlesWorkspaceId = null;
		}
	}

	/**
	 * This function contains the biggest part of the test for scenario 3.
	 *
	 * @return bool
	 */
	private function testUnsavedArticle()
	{
		require_once BASEDIR . '/server/services/wfl/WflCreateArticleWorkspaceService.class.php';
		require_once BASEDIR . '/server/services/wfl/WflSaveArticleInWorkspaceService.class.php';

		$articleContent = file_get_contents( dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario3_CreateUnsavedArticle_native.wcml' );

		if( !$this->createArticle( 0 ) ){
			return false;
		}

		// first we need to create a workspace
		$request = new WflCreateArticleWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->Format = $this->articleFormat;
		$request->Content = $articleContent;
		$request->ID = $this->articles[0]->MetaData->BasicMetaData->ID;
		$workspaceResponse = $this->runService( $request, 'WflWebEditWorkspace - Scenario 3 - create workspace with an saved article' );
		$this->multipleArticlesWorkspaceId = $workspaceResponse->WorkspaceId;

		if( !$workspaceResponse ){
			return false;
		}

		// Test if a second article can be added, this is not possible so raise an error.
		$request = new WflSaveArticleInWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->Format = $this->articleFormat;
		$request->WorkspaceId = $this->multipleArticlesWorkspaceId;
		$request->ID = '';
		$request->Content = $articleContent;
		$workspaceResponse = $this->runService( $request, 'WflWebEditWorkspace - Scenario 3 - add second article to workspace', '(S1019)' );

		if( $workspaceResponse ){ // If this succeeds something is wrong.
			return false;
		}

		// second part of scenario 3

		// create the first layout
		if( !$this->createLayOut( 0 ) ){
			return false;
		}

		if( !$this->createObjectRelationsArticle( 0, 0 ) ){
			return false;
		}

		// Save the layout with the new relations
		if( !$this->saveLayout( 0 ) ){
			return false;
		}

		// first we need to create a workspace
		$request = new WflSaveArticleInWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->Format = $this->articleFormat;
		$request->Content = $articleContent;
		$request->WorkspaceId = $this->multipleArticlesWorkspaceId;
		$request->ID = $this->articles[0]->MetaData->BasicMetaData->ID;
		$workspaceResponse = $this->runService( $request, 'WflWebEditWorkspace - Scenario 3 - create workspace with an saved article' );

		if( !$workspaceResponse ){
			return false;
		}

		// Test if a second article can be added, this shouldn't be possible and raise an error.
		$request = new WflSaveArticleInWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->Format = $this->articleFormat;
		$request->WorkspaceId = $this->multipleArticlesWorkspaceId;
		$request->ID = '';
		$request->Content = $articleContent;
		$workspaceResponse = $this->runService( $request, 'WflWebEditWorkspace - Scenario 3 - add second article (no save) to workspace', '(S1019)' );

		if( $workspaceResponse ){ // If this succeeds something is wrong.
			return false;
		}

		return true;
	}

	/**
	 * Create all the needed data first. (create a workspace and put the articles in the workspace)
	 * Edit the articles but do not save them. Preview the edited articles.
	 *
	 * @return bool
	 */
	private function editAndPreviewArticles()
	{
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticlesAtWorkspaceService.class.php';
		require_once BASEDIR . '/server/services/wfl/WflCreateArticleWorkspaceService.class.php';
		require_once BASEDIR . '/server/services/wfl/WflSaveArticleInWorkspaceService.class.php';

		$articleContent = file_get_contents( dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateArticle0_native.wcml' );
		$article2Content = file_get_contents( dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateArticle1_native.wcml' );

		if( !$articleContent || !$article2Content ){
			$this->setResult( 'ERROR', 'No content found in test files', 'Error occured in previewing articles');
			return false;
		}

		// first we need to create a workspace
		$request = new WflCreateArticleWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->Format = $this->articleFormat;
		$request->Content = $articleContent;
		$request->ID = $this->articles[0]->MetaData->BasicMetaData->ID;
		$workspaceResponse = $this->runService( $request, 'WflWebEditWorkspace - create workspace with article 1' );
		$this->multipleArticlesWorkspaceId = $workspaceResponse->WorkspaceId;

		if( !$workspaceResponse ){
			return false;
		}

		$request = new WflSaveArticleInWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->Format = $this->articleFormat;
		$request->WorkspaceId = $this->multipleArticlesWorkspaceId;
		$request->ID = $this->articles[1]->MetaData->BasicMetaData->ID;
		$request->Content = $article2Content;
		$workspaceResponse = $this->runService( $request, 'WflWebEditWorkspace -  add article 2 to workspace' );

		if( !$workspaceResponse ){
			return false;
		}

		$mutatedArticleContent = file_get_contents( dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_EditArticle0_native.wcml' );
		$mutatedArticle2Content = file_get_contents( dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_EditArticle1_native.wcml' );

		if( !$mutatedArticleContent || !$mutatedArticle2Content ){
			$this->setResult( 'ERROR', 'No content found in test files', 'Error occurred in previewing articles');
			return false;
		}

		if( !$this->multipleArticlePreview( $mutatedArticleContent, $mutatedArticle2Content )){
			return false;
		}

		if( !$this->saveArticle( 0 ) ){
			return false;
		}

		if( !$this->multipleArticlePreview( null, $mutatedArticle2Content )){
			return false;
		}

		if( !$this->saveArticle( 1 ) ){
			return false;
		}

		if( !$this->multipleArticlePreview( null, null )){
			return false;
		}

		return true;
	}

	/**
	 * Preview multiple articles
	 *
	 * @param $mutatedArticleContent
	 * @param $mutatedArticle2Content
	 * @return bool
	 */
	private function multipleArticlePreview( $mutatedArticleContent, $mutatedArticle2Content )
	{
		$articleAtWorkspace = new ArticleAtWorkspace();
		$articleAtWorkspace->ID = $this->articles[0]->MetaData->BasicMetaData->ID;
		$articleAtWorkspace->Format = $this->articleFormat;
		$articleAtWorkspace->Content = $mutatedArticleContent;
		$articleAtWorkspace->Elements = null;

		$article2AtWorkspace = new ArticleAtWorkspace();
		$article2AtWorkspace->ID = $this->articles[1]->MetaData->BasicMetaData->ID;
		$article2AtWorkspace->Format = $this->articleFormat;
		$article2AtWorkspace->Content = $mutatedArticle2Content;
		$article2AtWorkspace->Elements = null;

		$request = new WflPreviewArticlesAtWorkspaceRequest();
		$request->Ticket = $this->ticket;
		$request->WorkspaceId = $this->multipleArticlesWorkspaceId;
		$request->Articles = array( $articleAtWorkspace, $article2AtWorkspace );
		$request->Action = 'Preview';
		$request->LayoutId = $this->layouts[0]->MetaData->BasicMetaData->ID;
		$request->EditionId = '1';
		$request->PreviewType = 'page';

		$response = $this->runService( $request, 'WflWebEditWorkspace - preview articles' );

		if( $response ){
			$this->currentResponsePreview = $response;
		}

		//Compare results with recorded results
		if( !$this->compareResponses() ){
			return false;
		}

		return isset( $response );
	}

	/**
	 * Compare the results of the recorded response and of the generated response.
	 * When there are differences show them on the page.
	 *
	 * @return bool
	 */
	private function compareResponses()
	{
		$recResp = $this->getRecordedResponsePreview();
		$curResp = $this->currentResponsePreview;
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() );
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'Scenario 2' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'Scenario 2' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflWebEditWorkspace response.');
			return false;
		}

		return true;
	}

	/**
	 * Get the list of properties that don't need to be compared.
	 *
	 * @return array
	 */
	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Created' => true, 'Modified' => true,
			'Deleted' => true, 'FilePath' => true, 'FileUrl' => true
		);
	}

	/**
	 * First checkout the article then save the edited content of the article.
	 *
	 * @param int $articleIndex needed if you want to create a multiple layouts.
	 *
	 * @return bool
	 */
	private function saveArticle( $articleIndex )
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->articles[$articleIndex]->MetaData->BasicMetaData->ID;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->RequestInfo = null;
		$request->HaveVersions = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->EditionId = null;

		$response = $this->runService( $request, 'WflWebEditWorkspace - check out article ' . $articleIndex );

		if( !$response ){
			return false;
		}

		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array();
		$request->Objects[0] = $this->articles[$articleIndex];
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		switch( $articleIndex ){
			case 0:
				$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 160;
				$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 974;
				$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 4;
				$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 29;
				$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Porem explit volor aut omnihici id qui odi cor si quide nonsequos volore, cus essim sum volo eostior aut min res sit laudipsam que pre, sin ex et alibus et pa cus magnime vellupt iuriassus sunte quunt qui unt pero voluptur sit rem alic tor soluptibus incia duntur, ut ut postiisi rest perumquae. Itaturiatem dio to occum quas eserenda non placcae storror eritaec tescium liquidu citame voloriam sam, venditi consequae ipis aut etur modionsero illab ium et perrumeni odia dolendi omniant et eossimintor aut aliquia ducias eaqui odit praes et quo temporeris escimin temolupta quuntem laborio volor moluptae. Et eos velit, que siminciis ma adiciae. Name imillic atemquiaerum et quatem vollit volum quatur sumet, offic tem quat.
Ga. Quiandi gnimus utet el maximeturis velia debiscium viduntis doluptat etur?
Icatur? Erum rat fugit dolo ipsum duciaspienis ea idis nat.
Nem quia dust maio est vit magnam entiis num cum quatibus, con core nones et od ut magnisci cum Article 1 Mutated';
				$request->Objects[0]->MetaData->ContentMetaData->FileSize = 81320;
				$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_EditArticle0_native.wcml';
				$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
				$request->Objects[0]->Elements[0]->ID = '3AD42988-1EA3-4E04-A518-F31A41E88319';
				$request->Objects[0]->Elements[0]->Name = 'head';
				$request->Objects[0]->Elements[0]->LengthWords = 160;
				$request->Objects[0]->Elements[0]->LengthChars = 974;
				$request->Objects[0]->Elements[0]->LengthParas = 4;
				$request->Objects[0]->Elements[0]->LengthLines = 29;
				$request->Objects[0]->Elements[0]->Snippet = 'Porem explit volor aut omnihici id qui odi cor si quide nonsequos volore, cus essim sum volo eostior aut min res sit laudipsam que pre, sin ex et alibus et pa cus magnime vellupt iuriassus sunte quunt qui unt pero voluptur sit rem alic tor soluptibus';
				$request->Objects[0]->Elements[0]->Version = '257e0563-48b6-952c-404d-3eacf4b64bb4';
				$request->Objects[0]->Elements[0]->Content = 'Porem explit volor aut omnihici id qui odi cor si quide nonsequos volore, cus essim sum volo eostior aut min res sit laudipsam que pre, sin ex et alibus et pa cus magnime vellupt iuriassus sunte quunt qui unt pero voluptur sit rem alic tor soluptibus incia duntur, ut ut postiisi rest perumquae. Itaturiatem dio to occum quas eserenda non placcae storror eritaec tescium liquidu citame voloriam sam, venditi consequae ipis aut etur modionsero illab ium et perrumeni odia dolendi omniant et eossimintor aut aliquia ducias eaqui odit praes et quo temporeris escimin temolupta quuntem laborio volor moluptae. Et eos velit, que siminciis ma adiciae. Name imillic atemquiaerum et quatem vollit volum quatur sumet, offic tem quat.
Ga. Quiandi gnimus utet el maximeturis velia debiscium viduntis doluptat etur?
Icatur? Erum rat fugit dolo ipsum duciaspienis ea idis nat.
Nem quia dust maio est vit magnam entiis num cum quatibus, con core nones et od ut magnisci cum Article 1 Mutated';
				break;

			case 1:
				$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 129;
				$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 898;
				$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 2;
				$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 12;
				$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Cullam quatur aut evelles sequam vel ium natemperibus est asperro idenden dissitatur, si dem estrum volupta tiissum hariore nduciae. Anditasit ut voluptat aut que sus doluptat faci cus estiur molorpo remperro quias voluptatur? Qui alitae autecum idus dus.
Oluptas pisinct eniatus doluptatiat aut occus molorem coressi nctaepr orrunt voluptate none estis dolor sit aliquisci dolorehenit quiati temollam quibusa pernatem quisciendion pro essed expeles citatinverci volorroratis dem eost latur magniandit, ullaborro teste cuptata tiasit, ut ullab in pere mint untessu sciatet asime natectaque natur asim atiis ma cusdaesequi to de simincia voloreperios erionectotas eicti tem volorib usantem fugiae es esti qui veliquas dernatur rerum qui res dellaudam que conse sedias aut paris experro repudis dere poria venis quam quis et ipid expernati dolorempori ullesti onsedigendae plantia sa Article 2 Mutated';
				$request->Objects[0]->MetaData->ContentMetaData->FileSize = 79532;
				$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_EditArticle1_native.wcml';
				$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
				$request->Objects[0]->Elements[0]->LengthWords = 129;
				$request->Objects[0]->Elements[0]->LengthChars = 898;
				$request->Objects[0]->Elements[0]->LengthParas = 2;
				$request->Objects[0]->Elements[0]->LengthLines = 12;
				$request->Objects[0]->Elements[0]->Snippet = 'Cullam quatur aut evelles sequam vel ium natemperibus est asperro idenden dissitatur, si dem estrum volupta tiissum hariore nduciae. Anditasit ut voluptat aut que sus doluptat faci cus estiur molorpo remperro quias voluptatur? Qui alitae autecum idus';
				$request->Objects[0]->Elements[0]->Version = '4220d943-1e1d-80e0-56c9-321f5e3bcf65';
				$request->Objects[0]->Elements[0]->Content = 'Cullam quatur aut evelles sequam vel ium natemperibus est asperro idenden dissitatur, si dem estrum volupta tiissum hariore nduciae. Anditasit ut voluptat aut que sus doluptat faci cus estiur molorpo remperro quias voluptatur? Qui alitae autecum idus dus.
Oluptas pisinct eniatus doluptatiat aut occus molorem coressi nctaepr orrunt voluptate none estis dolor sit aliquisci dolorehenit quiati temollam quibusa pernatem quisciendion pro essed expeles citatinverci volorroratis dem eost latur magniandit, ullaborro teste cuptata tiasit, ut ullab in pere mint untessu sciatet asime natectaque natur asim atiis ma cusdaesequi to de simincia voloreperios erionectotas eicti tem volorib usantem fugiae es esti qui veliquas dernatur rerum qui res dellaudam que conse sedias aut paris experro repudis dere poria venis quam quis et ipid expernati dolorempori ullesti onsedigendae plantia sa Article 2 Mutated';
				break;
		}

		$response = $this->runService( $request, 'WflWebEditWorkspace - save article ' . $articleIndex );

		return (bool) $response;
	}

	/**
	 * Create a layout in Enterprise Server.
	 *
	 * @param int $layoutIndex needed if you want to create a multiple layouts.
	 * @return bool true if the response has an ID.
	 */
	private function createLayout( $layoutIndex )
	{
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';

		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:F77F1174072068118083B37140CB17A8';

		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'test_layout_' . $layoutIndex . $timestamp;

		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->publication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->category;
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
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 446464;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-07-16T14:43:26';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-07-16T14:39:55';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '3';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Layouts';
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
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateLayout0_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateLayout1_preview.jpg';
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
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateLayout2_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateLayout3_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateLayout4_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets[0] = $this->printTarget;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = null;

		$curResp = $this->runService( $request, 'WflWebEditWorkspace - scenario_02 - Create Layout');

		if( isset( $curResp->Objects[0] )){
			$this->layouts[$layoutIndex] = $curResp->Objects[0];
		} else {
			$this->setResult( 'ERROR', 'No layout object returned in CreateObjectsResponse.' );
		}

		return isset( $this->layouts[$layoutIndex] );
	}

	/**
	 * Create the first article.
	 *
	 * @param integer $articleIndex
	 * @return bool
	 */
	private function createArticle( $articleIndex )
	{
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';

		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;

		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'test_article_' . $articleIndex . $timestamp;

		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->publication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->category;
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
		$request->Objects[0]->MetaData->ContentMetaData->Format = $this->articleFormat;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-07-16T14:40:30';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-07-16T14:40:30';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
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
		$request->Objects[0]->Files[0]->Type = $this->articleFormat;
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Targets = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = null;

		switch( $articleIndex ){
			case 0:
				$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Porem explit volor aut omnihici id qui odi cor si quide nonsequos volore, cus essim sum volo eostior aut min res sit laudipsam que pre, sin ex et alibus et pa cus magnime vellupt iuriassus sunte quunt qui unt pero voluptur sit rem alic tor soluptibus';
				$request->Objects[0]->MetaData->ContentMetaData->Columns = 1;
				$request->Objects[0]->MetaData->ContentMetaData->Width = 153.070866;
				$request->Objects[0]->MetaData->ContentMetaData->Height = 222.519685;
				$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
				$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 160;
				$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 952;
				$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 4;
				$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 29;
				$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Porem explit volor aut omnihici id qui odi cor si quide nonsequos volore, cus essim sum volo eostior aut min res sit laudipsam que pre, sin ex et alibus et pa cus magnime vellupt iuriassus sunte quunt qui unt pero voluptur sit rem alic tor soluptibus incia duntur, ut ut postiisi rest perumquae. Itaturiatem dio to occum quas eserenda non placcae storror eritaec tescium liquidu citame voloriam sam, venditi consequae ipis aut etur modionsero illab ium et perrumeni odia dolendi omniant et eossimintor aut aliquia ducias eaqui odit praes et quo temporeris escimin temolupta quuntem laborio volor moluptae. Et eos velit, que siminciis ma adiciae. Name imillic atemquiaerum et quatem vollit volum quatur sumet, offic tem quat.
Ga. Quiandi gnimus utet el maximeturis velia debiscium viduntis doluptat etur?
Icatur? Erum rat fugit dolo ipsum duciaspienis ea idis nat.
Nem quia dust maio est vit magnam entiis num cum quatibus, con core nones et od ut magnisci cum dolore id modipsant.';
				$request->Objects[0]->MetaData->ContentMetaData->FileSize = 70852;
				$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateArticle0_native.wcml';
				$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
				$request->Objects[0]->Elements[0]->ID = '3AD42988-1EA3-4E04-A518-F31A41E88319';
				$request->Objects[0]->Elements[0]->Name = 'head';
				$request->Objects[0]->Elements[0]->LengthWords = 160;
				$request->Objects[0]->Elements[0]->LengthChars = 952;
				$request->Objects[0]->Elements[0]->LengthParas = 4;
				$request->Objects[0]->Elements[0]->LengthLines = 29;
				$request->Objects[0]->Elements[0]->Snippet = 'Porem explit volor aut omnihici id qui odi cor si quide nonsequos volore, cus essim sum volo eostior aut min res sit laudipsam que pre, sin ex et alibus et pa cus magnime vellupt iuriassus sunte quunt qui unt pero voluptur sit rem alic tor soluptibus';
				$request->Objects[0]->Elements[0]->Version = '59B2AD3F-7757-4CF3-ABAE-B5B3AF88A89C';
				$request->Objects[0]->Elements[0]->Content = null;
				break;

			case 1:
				$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Cullam quatur aut evelles sequam vel ium natemperibus est asperro idenden dissitatur, si dem estrum volupta tiissum hariore nduciae. Anditasit ut voluptat aut que sus doluptat faci cus estiur molorpo remperro quias voluptatur? Qui alitae autecum idus';
				$request->Objects[0]->MetaData->ContentMetaData->Format = $this->articleFormat;
				$request->Objects[0]->MetaData->ContentMetaData->Columns = 1;
				$request->Objects[0]->MetaData->ContentMetaData->Width = 394.015748;
				$request->Objects[0]->MetaData->ContentMetaData->Height = 168.661417;
				$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
				$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 129;
				$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 889;
				$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 2;
				$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 12;
				$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Cullam quatur aut evelles sequam vel ium natemperibus est asperro idenden dissitatur, si dem estrum volupta tiissum hariore nduciae. Anditasit ut voluptat aut que sus doluptat faci cus estiur molorpo remperro quias voluptatur? Qui alitae autecum idus dus.
Oluptas pisinct eniatus doluptatiat aut occus molorem coressi nctaepr orrunt voluptate none estis dolor sit aliquisci dolorehenit quiati temollam quibusa pernatem quisciendion pro essed expeles citatinverci volorroratis dem eost latur magniandit, ullaborro teste cuptata tiasit, ut ullab in pere mint untessu sciatet asime natectaque natur asim atiis ma cusdaesequi to de simincia voloreperios erionectotas eicti tem volorib usantem fugiae es esti qui veliquas dernatur rerum qui res dellaudam que conse sedias aut paris experro repudis dere poria venis quam quis et ipid expernati dolorempori ullesti onsedigendae plantia sa deniet est hitiund';
				$request->Objects[0]->MetaData->ContentMetaData->FileSize = 69094;
				$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_CreateArticle1_native.wcml';
				$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
				$request->Objects[0]->Elements[0]->ID = '136BCFCE-49D0-49C0-8FFF-95EF48972150';
				$request->Objects[0]->Elements[0]->Name = 'body';
				$request->Objects[0]->Elements[0]->LengthWords = 129;
				$request->Objects[0]->Elements[0]->LengthChars = 889;
				$request->Objects[0]->Elements[0]->LengthParas = 2;
				$request->Objects[0]->Elements[0]->LengthLines = 12;
				$request->Objects[0]->Elements[0]->Snippet = 'Cullam quatur aut evelles sequam vel ium natemperibus est asperro idenden dissitatur, si dem estrum volupta tiissum hariore nduciae. Anditasit ut voluptat aut que sus doluptat faci cus estiur molorpo remperro quias voluptatur? Qui alitae autecum idus';
				$request->Objects[0]->Elements[0]->Version = '155804FF-ABCA-4A63-80AD-A73D9139EC72';
				$request->Objects[0]->Elements[0]->Content = null;
				break;
		}

		$response = $this->runService( $request, 'WflWebEditWorkspace - scenario_02 - Create Article '. $articleIndex);

		if( isset( $response->Objects[0] )){
			$this->articles[$articleIndex] = $response->Objects[0];
		} else {
			$this->setResult( 'ERROR', 'No article object returned in CreateObjectsResponse.' );
		}

		return isset( $this->articles[$articleIndex] );
	}

	/**
	 * Create the relation with the layout for the first article.
	 *
	 * @param integer $articleIndex
	 * @param integer $layoutIndex
	 * @return bool
	 */
	private function createObjectRelationsArticle( $articleIndex, $layoutIndex )
	{
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectRelationsService.class.php';

		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->layouts[$layoutIndex]->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Child = $this->articles[$articleIndex]->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();

		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->Placements[0]->FormWidgetId = null;

		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;

		switch( $articleIndex ){
			case 0:
				$request->Relations[0]->Placements[0]->Page = 1;
				$request->Relations[0]->Placements[0]->Element = 'head';
				$request->Relations[0]->Placements[0]->ElementID = '3AD42988-1EA3-4E04-A518-F31A41E88319';
				$request->Relations[0]->Placements[0]->FrameOrder = 0;
				$request->Relations[0]->Placements[0]->FrameID = '234';
				$request->Relations[0]->Placements[0]->Left = 0;
				$request->Relations[0]->Placements[0]->Top = 0;
				$request->Relations[0]->Placements[0]->Width = 0;
				$request->Relations[0]->Placements[0]->Height = 0;
				$request->Relations[0]->Placements[0]->Overset = 0;
				$request->Relations[0]->Placements[0]->OversetChars = 0;
				$request->Relations[0]->Placements[0]->OversetLines = 0;
				$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
				$request->Relations[0]->Placements[0]->Content = '';
				$request->Relations[0]->Placements[0]->PageSequence = 1;
				$request->Relations[0]->Placements[0]->PageNumber = '1';
				$request->Relations[0]->Placements[1] = new Placement();
				$request->Relations[0]->Placements[1]->Page = 1;
				$request->Relations[0]->Placements[1]->Element = 'head';
				$request->Relations[0]->Placements[1]->ElementID = '3AD42988-1EA3-4E04-A518-F31A41E88319';
				$request->Relations[0]->Placements[1]->FrameOrder = 1;
				$request->Relations[0]->Placements[1]->FrameID = '258';
				$request->Relations[0]->Placements[1]->Left = 0;
				$request->Relations[0]->Placements[1]->Top = 0;
				$request->Relations[0]->Placements[1]->Width = 0;
				$request->Relations[0]->Placements[1]->Height = 0;
				$request->Relations[0]->Placements[1]->Overset = -18.889715;
				$request->Relations[0]->Placements[1]->OversetChars = -4;
				$request->Relations[0]->Placements[1]->OversetLines = 0;
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
				$request->Relations[0]->Placements[1]->FormWidgetId = null;

				break;

			case 1:
				$request->Relations[0]->Placements[0]->Page = 1;
				$request->Relations[0]->Placements[0]->Element = 'body';
				$request->Relations[0]->Placements[0]->ElementID = '136BCFCE-49D0-49C0-8FFF-95EF48972150';
				$request->Relations[0]->Placements[0]->FrameOrder = 0;
				$request->Relations[0]->Placements[0]->FrameID = '282';
				$request->Relations[0]->Placements[0]->Left = 0;
				$request->Relations[0]->Placements[0]->Top = 0;
				$request->Relations[0]->Placements[0]->Width = 0;
				$request->Relations[0]->Placements[0]->Height = 0;
				$request->Relations[0]->Placements[0]->Overset = -26.427674;
				$request->Relations[0]->Placements[0]->OversetChars = -6;
				$request->Relations[0]->Placements[0]->OversetLines = 0;
				$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
				$request->Relations[0]->Placements[0]->Content = '';
				$request->Relations[0]->Placements[0]->PageSequence = 1;
				$request->Relations[0]->Placements[0]->PageNumber = '1';

				break;
		}

		$response = $this->runService( $request, 'WflWebEditWorkspace - scenario_02 - Article 1 create relations');

		if( $response->Relations[0] ){
			$this->layouts[$layoutIndex]->Relations[] = $response->Relations[0];
		}

		return (bool) $response;
	}

	/**
	 * Save the changes that are made in the layout.
	 *
	 * @param int $layoutIndex
	 * @return bool
	 */
	private function saveLayout( $layoutIndex )
	{
		require_once BASEDIR . '/server/services/wfl/WflSaveObjectsService.class.php';

		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array();
		$request->Objects[0] = $this->layouts[$layoutIndex];
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_SaveLayout0_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_SaveLayout1_preview.jpg';
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
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_SaveLayout2_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_SaveLayout3_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/wflWebEditWorkspace_TestData_Scenario2_SaveLayout4_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		$response = $this->runService( $request, 'WflWebEditWorkspace - scenario_02 - Save layout ' . $layoutIndex );

		return (bool) $response;
	}

	/**
	 * Get the recorded response of a preview, this is used to compare with a newly generated response.
	 *
	 * @return WflPreviewArticlesAtWorkspaceResponse
	 */
	private function getRecordedResponsePreview()
	{
		$response = new WflPreviewArticlesAtWorkspaceResponse();
		$response->Placements = array();
		$response->Placements[0] = new Placement();
		$response->Placements[0]->Page = null;
		$response->Placements[0]->Element = 'head';
		$response->Placements[0]->ElementID = '3AD42988-1EA3-4E04-A518-F31A41E88319';
		$response->Placements[0]->FrameOrder = '0';
		$response->Placements[0]->FrameID = '234';
		$response->Placements[0]->Left = '56.6929133858268';
		$response->Placements[0]->Top = '59.5275590543543';
		$response->Placements[0]->Width = '153.070866141732';
		$response->Placements[0]->Height = '222.51968503937';
		$response->Placements[0]->Overset = null;
		$response->Placements[0]->OversetChars = null;
		$response->Placements[0]->OversetLines = 0;
		$response->Placements[0]->Layer = 'Layer 1';
		$response->Placements[0]->Content = null;
		$response->Placements[0]->Edition = null;
		$response->Placements[0]->ContentDx = null;
		$response->Placements[0]->ContentDy = null;
		$response->Placements[0]->ScaleX = null;
		$response->Placements[0]->ScaleY = null;
		$response->Placements[0]->PageSequence = '1';
		$response->Placements[0]->PageNumber = '1';
		$response->Placements[0]->Tiles = null;
		$response->Placements[0]->FormWidgetId = null;
		$response->Placements[0]->InDesignArticleIds = array();
		$response->Placements[0]->FrameType = 'text';
		$response->Placements[0]->SplineID = '234';
		$response->Placements[1] = new Placement();
		$response->Placements[1]->Page = null;
		$response->Placements[1]->Element = 'head';
		$response->Placements[1]->ElementID = '3AD42988-1EA3-4E04-A518-F31A41E88319';
		$response->Placements[1]->FrameOrder = '1';
		$response->Placements[1]->FrameID = '258';
		$response->Placements[1]->Left = '232.44094488189';
		$response->Placements[1]->Top = '324.566929133095';
		$response->Placements[1]->Width = '204.094488188976';
		$response->Placements[1]->Height = '202.677165354331';
		$response->Placements[1]->Overset = null;
		$response->Placements[1]->OversetChars = '0';
		$response->Placements[1]->OversetLines = 0;
		$response->Placements[1]->Layer = 'Layer 1';
		$response->Placements[1]->Content = null;
		$response->Placements[1]->Edition = null;
		$response->Placements[1]->ContentDx = null;
		$response->Placements[1]->ContentDy = null;
		$response->Placements[1]->ScaleX = null;
		$response->Placements[1]->ScaleY = null;
		$response->Placements[1]->PageSequence = '1';
		$response->Placements[1]->PageNumber = '1';
		$response->Placements[1]->Tiles = null;
		$response->Placements[1]->FormWidgetId = null;
		$response->Placements[1]->InDesignArticleIds = array();
		$response->Placements[1]->FrameType = 'text';
		$response->Placements[1]->SplineID = '258';
		$response->Placements[2] = new Placement();
		$response->Placements[2]->Page = null;
		$response->Placements[2]->Element = 'body';
		$response->Placements[2]->ElementID = '136BCFCE-49D0-49C0-8FFF-95EF48972150';
		$response->Placements[2]->FrameOrder = '0';
		$response->Placements[2]->FrameID = '282';
		$response->Placements[2]->Left = '103.464566929134';
		$response->Placements[2]->Top = '591.02362204648';
		$response->Placements[2]->Width = '394.015748031496';
		$response->Placements[2]->Height = '168.661417322835';
		$response->Placements[2]->Overset = null;
		$response->Placements[2]->OversetChars = '0';
		$response->Placements[2]->OversetLines = 0;
		$response->Placements[2]->Layer = 'Layer 1';
		$response->Placements[2]->Content = null;
		$response->Placements[2]->Edition = null;
		$response->Placements[2]->ContentDx = null;
		$response->Placements[2]->ContentDy = null;
		$response->Placements[2]->ScaleX = null;
		$response->Placements[2]->ScaleY = null;
		$response->Placements[2]->PageSequence = '1';
		$response->Placements[2]->PageNumber = '1';
		$response->Placements[2]->Tiles = null;
		$response->Placements[2]->FormWidgetId = null;
		$response->Placements[2]->InDesignArticleIds = array();
		$response->Placements[2]->FrameType = 'text';
		$response->Placements[2]->SplineID = '282';
		$response->Elements = array();
		$response->Elements[0] = new Element();
		$response->Elements[0]->ID = '3AD42988-1EA3-4E04-A518-F31A41E88319';
		$response->Elements[0]->Name = 'head';
		$response->Elements[0]->LengthWords = '160';
		$response->Elements[0]->LengthChars = '977';
		$response->Elements[0]->LengthParas = '4';
		$response->Elements[0]->LengthLines = '29';
		$response->Elements[0]->Snippet = null;
		$response->Elements[0]->Version = null;
		$response->Elements[0]->Content = null;
		$response->Elements[1] = new Element();
		$response->Elements[1]->ID = '136BCFCE-49D0-49C0-8FFF-95EF48972150';
		$response->Elements[1]->Name = 'body';
		$response->Elements[1]->LengthWords = '129';
		$response->Elements[1]->LengthChars = '899';
		$response->Elements[1]->LengthParas = '2';
		$response->Elements[1]->LengthLines = '12';
		$response->Elements[1]->Snippet = null;
		$response->Elements[1]->Version = null;
		$response->Elements[1]->Content = null;
		$response->Pages = array();
		$response->Pages[0] = new Page();
		$response->Pages[0]->Width = '595.275590551';
		$response->Pages[0]->Height = '841.889763778';
		$response->Pages[0]->PageNumber = '1';
		$response->Pages[0]->PageOrder = '1';
		$response->Pages[0]->Files = array();
		$response->Pages[0]->Files[0] = new Attachment();
		$response->Pages[0]->Files[0]->Rendition = 'preview';
		$response->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->Pages[0]->Files[0]->Content = null;
		$response->Pages[0]->Files[0]->FilePath = '';
		$response->Pages[0]->Files[0]->FileUrl = '';
		$response->Pages[0]->Files[0]->EditionId = null;
		$response->Pages[0]->Edition = null;
		$response->Pages[0]->Master = '';
		$response->Pages[0]->Instance = 'Production';
		$response->Pages[0]->PageSequence = '1';
		$response->Pages[0]->Renditions = null;
		$response->Pages[0]->Orientation = null;
		$response->LayoutVersion = '0.2';
		return $response;
	}
}
