<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WwcxToWcml_ArticleConversion_TestCase extends TestCase
{
	private $ticket 	= null;		// User ticket of current PHP session.
	private $suiteOpts	= null;
	private $pubInfo	= null;
	private $wwcxContent= null;
	private $wcmlContent= null;
	private $objId		= null;
	
	public function getDisplayName() { return 'Content Station CS4 Article Conversion.'; }
	public function getTestGoals()   { return 'Check if articles in WWCX format can be converted to WCML format.'; }
	public function getTestMethods() { return 'Convert an article from WWCX to WCML format and compares the contents.'; }
    public function getPrio()        { return 1; }
    
    final public function runTest()
	{
		// Make sure the WwcxToWcmlConversion plugin is active (enabled).
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$didActivate = $utils->activatePluginByName( $this, 'WwcxToWcmlConversion' );
		if( is_null($didActivate) ) { // NULL indicates error.
			return; // Errors are already handled in enableRequiredServerPlugin().
		}
			
		// Run the test.
		$this->suiteOpts = unserialize(TESTSUITE);
		$this->Logon();
		$this->createWwcxArticle();
		$wcmlObj = $this->getConvertedWCMLArticle();
		$this->validateWcmlArticle( $wcmlObj );
		$this->unlockArticle();
		$this->deleteWwcxArticle();
		$this->logOff();
		
		// De-activate the WwcxToWcmlConversion plugin again (but only when we did activate).
		if( $didActivate ) {
			$utils->deactivatePluginByName( $this, 'WwcxToWcmlConversion' );
		}
	}
	
	private function createWwcxArticle()
	{
		$this->getTestArticleXML();

		// Build MetaData
		$basicMD         = new BasicMetaData();
		$basicMD->Name   = 'WWCX_'.date('Y-m-d_H-i-s');
		$basicMD->Type   = 'Article';
		$basicMD->Publication = new Publication( $this->pubInfo->Id, $this->pubInfo->Name );
		$basicMD->Category    = $this->pubInfo->Categories[0];
		
		$wflMD           = new WorkflowMetaData();
		$wflMD->State    = new State( $this->getStatusId( 'Article' ) );
		
		$contentMD           = new ContentMetaData();
		$contentMD->Format   = 'application/incopy';
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
		$fileAttachment->Type      = 'application/incopy';
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer( $this->content, $fileAttachment );
		
		// Create article object
		$object = new Object();
		$object->MetaData = $metaData;

		// Fix some properties for the validator
		$object->MetaData->BasicMetaData->Category = new Category( $object->MetaData->BasicMetaData->Category->Id,  $object->MetaData->BasicMetaData->Category->Name );
		$object->MetaData->WorkflowMetaData->State->Name = "";

		$object->Relations = array();
		$object->Files     = array( $fileAttachment );
		
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$service = new WflCreateObjectsService();
		$req          = new WflCreateObjectsRequest();
		$req->Ticket  = $this->ticket;
		$req->Lock    = false;
		$req->Objects = array( $object );
		$response = $service->execute( $req );
		$this->objId = $response->Objects[0]->MetaData->BasicMetaData->ID;
		LogHandler::Log( 'WwcxToWcmlConversion', 'DEBUG', 'Article Id Created: '.$this->objId );
	}

	private function getConvertedWCMLArticle()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$service      = new WflGetObjectsService();
		$req          = new WflGetObjectsRequest();
		$req->Ticket  = $this->ticket;
		$req->IDs     = array( $this->objId );
		$req->Lock    = true;
		$req->Rendition = 'native';
		$resp = $service->execute( $req );
		$object = $resp->Objects[0];

		return $object;
	}

	/**
	 * Validate the converted article format, and compare the snippet content with original wwcx article
	 *
	 * @param object $object
	 */
	private function validateWcmlArticle( $object )
	{
		if( $object->MetaData->ContentMetaData->Format == 'application/incopyicml' ) {
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$content = $transferServer->getContent( $object->Files[0] );
			$wwcxSnippet = $this->getStorySnippet( $this->content, 'application/incopy', 0 );
			$wcmlSnippet = $this->getStorySnippet( $content, $object->MetaData->ContentMetaData->Format, 0 );
			if( $wwcxSnippet != $wcmlSnippet ) {
				$this->setResult( 'ERROR', 'Converted WCML article content not the same with original WWCX article.', '' );
			}
		}
		else {
			$this->setResult( 'ERROR', 'Failed to convert article to WCML format.', '' );
		}
	}

	/**
	 * Unlock article
	 *
	 */
	private function unlockArticle()
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$service = new WflUnlockObjectsService();
			$req            = new WflUnlockObjectsRequest();
			$req->Ticket    = $this->ticket;
			$req->IDs       = array( $this->objId );
			$service->execute( $req );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not unlock article object: '.$e->getMessage(), '' );
		}
	}

	/**
	 * Delete article
	 *
	 */
	private function deleteWwcxArticle()
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$service = new WflDeleteObjectsService();
			$req            = new WflDeleteObjectsRequest();
			$req->Ticket    = $this->ticket;
			$req->IDs       = array( $this->objId );
			$req->Areas		= array( "Workflow" );
			$req->Permanent = true;
			$resp = $service->execute( $req );
			
			if( $resp->Reports ) { // Introduced since v8.0
				$message = '';
				foreach( $resp->Reports as $report ) {
					foreach( $report->Entries as $reportEntry ) {
						$message .= $reportEntry->Message . PHP_EOL;
					}
				}
				$this->setResult( 'ERROR', 'Could not delete article object: '.$message, '' );
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not delete article object: '.$e->getMessage(), '' );
		}
	}
	
	/**
	 * Logon to system, get the ticket and publication
	 */
	private function logOn()
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
			$service = new WflLogOnService();
			$req 			= new WflLogOnRequest();
			$req->User 		= $this->suiteOpts['User'];
			$req->Password	= $this->suiteOpts['Password'];
			$req->Server	= 'Enterprise Server';
			$req->ClientAppName	= 'BuildTest_WwcxToWcml';
			$req->ClientAppVersion = 'v'.SERVERVERSION;
			
			require_once BASEDIR.'/server/utils/UrlUtils.php';
			$clientip = WW_Utils_UrlUtils::getClientIP();
			$req->ClientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
			if( empty($req->ClientName) ) {
				$req->ClientName = $clientip;
			}

			$resp = $service->execute($req);

			LogHandler::Log( 'WwcxToWcmlConversion', 'DEBUG', 'Logon successful.' );
			$this->ticket = $resp->Ticket;
			
			$testPub = null;
			// Get the test publication
			if( count($resp->Publications) > 0 ) {
				foreach( $resp->Publications as $pub ) {
					if( $pub->Name == $this->suiteOpts['Brand'] ) {
						$testPub = $pub;
						break;
					}
				}
			}
			if( !$testPub ) {
				$this->setResult( 'ERROR', 'Could not find the test Brand: '.$this->suiteOpts['Brand'], 
					'Please check the TESTSUITE setting in configserver.php.' );
			}
			$this->pubInfo = $testPub;
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not logon test user: '.$e->getMessage(), 'Please check the TESTSUITE setting in configserver.php.' );
		}
		return null;
	}
	
	/**
	 * Logoff user from the system
	 *
	 */
	private function logOff()
	{
		try{
			require_once BASEDIR . '/server/services/wfl/WflLogOffService.class.php';
			$service = new WflLogOffService();
			$req = new WflLogOffRequest();
			$req->Ticket = $this->ticket;
			$service->execute($req);
			LogHandler::Log( 'WwcxToWcmlConversion', 'DEBUG', 'Logoff successful.' );
		} catch ( BizException $e){
			$this->setResult( 'ERROR', 'Could not logOff test user: '.$e->getDetail(),  $e->getMessage() );
		}
	}

	/**
	 * Return the first status found for the object type.
	 * in publicationInfo.
	 *
	 * @param string $objecType
	 * @return integer statusId
	 */
	private function getStatusId( $objecType )
	{
		$statusId = null;
		$statuses = $this->pubInfo->States;
		if( $statuses )foreach( $statuses as $status ){
			if( $status->Type == $objecType ){
				$statusId = $status->Id;
				break; // found {objectType} status
			}
		}
		return $statusId;
	}

	/**
	 * Get test article XML content
	 *
	 */
	private function getTestArticleXML()
	{
		$fileContentPath = dirname(__FILE__).'/testdata/test_article.wwcx';
		
		$icDoc = new DOMDocument();
		$icDoc->loadXML( file_get_contents( $fileContentPath )); 
		$this->content = $icDoc->saveXML();
	}

	/**
	 * Extract Nth story ($nthStory) from given article content ($content).
	 *
	 * @param string $content Article content
	 * @param string $format Article format
	 * @param int $nthStory Nth story with first story starts from index 0.
	 * @return string the $nthStory story (with Content in embedded XML format).
	 */
	private function getStorySnippet( $content, $format, $nthStory = null)
	{
		$icDoc = new DOMDocument();
		$icDoc->loadXML( $content );
		$xpath = new DOMXPath($icDoc);

		if( $format == 'application/incopy' ) { // WWCX
			$icStories = $xpath->query( '//Stories/Story/StoryInfo/SI_Snippet' );
			$icStory = $icStories->item( $nthStory );
		} else { // WCML
			$xpath->registerNamespace('ea', "urn:EnterpriseArticle");
			$icStories = $xpath->query( '//ea:Stories/ea:Story/ea:StoryInfo/ea:SI_Snippet' );
			$icStory = $icStories->item( $nthStory );
		}
		return $icStory->nodeValue;
	}
}
