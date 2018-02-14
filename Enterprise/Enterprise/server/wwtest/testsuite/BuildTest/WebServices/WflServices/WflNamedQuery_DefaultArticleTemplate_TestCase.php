<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.4.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

/**
 * @property mixed art
 */
class WW_TestSuite_BuildTest_WebServices_WflServices_WflNamedQuery_DefaultArticleTemplate_TestCase extends TestCase
{
	private $ticket = null;
	private $publication = null;
	private $category = null;
	private $printTarget = null;
	private $articleTemplateId = null;
	private $articleTemplateName = null;
	private $articleTemplateStatus = null;
	private $articleTemplateRating = null;
	private $articleTemplateDescription = null;
	const NAMEDQUERY = 'DefaultArticleTemplate';

	public function getDisplayName() { return 'NamedQuery - ' . self::NAMEDQUERY; }
	public function getTestGoals()   { return 'Checks if ' . self::NAMEDQUERY . ' named query is works well.'; }
	public function getTestMethods() { return 'Perform named query and check whether it returns the correct article template ID.'; }
    public function getPrio()        { return 106; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the data that has been determined by "Setup test data" TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket      = $vars['BuildTest_WebServices_WflServices']['ticket'];
   		$publicationInfo   = $vars['BuildTest_WebServices_WflServices']['publication'];
   		$categoryInfo      = $vars['BuildTest_WebServices_WflServices']['category'];
   		$artTplStatusInfo  = $vars['BuildTest_WebServices_WflServices']['articleTemplateStatus'];
   		$this->printTarget = $vars['BuildTest_WebServices_WflServices']['printTarget'];
		if( !$this->ticket || !$publicationInfo || !$categoryInfo || !$artTplStatusInfo ) {
			$this->setResult( 'ERROR', 'Could not find test data to work on.', 
								'Please enable the "Setup test data" entry and try again.' );
			return;
		}

		// Prepare brand, catergory and status to be used later for image object creation.
		$this->publication = new Publication( $publicationInfo->Id, $publicationInfo->Name );
		$this->category = new Category( $categoryInfo->Id, $categoryInfo->Name );
		$this->articleTemplateStatus = new State( $artTplStatusInfo->Id, $artTplStatusInfo->Name );
		$this->articleTemplateRating = 127;
		$this->articleTemplateDescription = __CLASS__.' Description';
		
		// Create a CS6 article template object in database.
		$response = $this->createAticleTemplate();
		$basicMetaData = @$response->Objects[0]->MetaData->BasicMetaData;
		if( $basicMetaData ) {
			$this->articleTemplateId = $basicMetaData->ID;
			$this->articleTemplateName = $basicMetaData->Name;
		}
		if( !$this->articleTemplateId || !$this->articleTemplateName ) {
			LogHandler::Log( 'WflNamedQuery', 'ERROR', 
							'Could not create article template object. Check the server logging.' );
		}
		
		// Search for any article templates in the system.
		$stepInfo = 'Running NamedQuery "' . self::NAMEDQUERY .'" to search for any article template.';
		$resp = $this->namedQuery( $stepInfo, null );
		if( $this->validateNamedQueryResp( $resp ) ) {
			LogHandler::Log( 'WflNamedQuery', 'DEBUG', 
				'Article template with Id: "' . $this->articleTemplateId . '" found in the search result.');
		} else {
			LogHandler::Log( 'WflNamedQuery', 'ERROR', 
				'Article template with Id: "' . $this->articleTemplateId . '" not found in the search result.');
			$this->setResult( 'ERROR', 'No article template record found.', 
								'Please enable the "Setup test data" entry and try again.' );
		}

		// Search the system (by name) for only that article template we've created.
		$stepInfo = 'Running NamedQuery "' . self::NAMEDQUERY .'" to search for article template with Name: "' . $this->articleTemplateName . '".';
		$resp = $this->namedQuery( $stepInfo, $this->articleTemplateName );
		if( $this->validateNamedQueryResp( $resp ) ) {
			LogHandler::Log( 'WflNamedQuery', 'DEBUG', 
				'Article template with Name: "' . $this->articleTemplateName . '" found in the search result.');
		} else {
			LogHandler::Log( 'WflNamedQuery', 'ERROR', 
				'Article template with Name: "' . $this->articleTemplateName . '" not found in the search result.');
			$this->setResult( 'ERROR', 'No article template with name: ' . $this->articleTemplateName . ' found.', 
								'Please enable the "Setup test data" entry and try again.' );
		}
		
		// Permanently delete the article template object (we created before).
		$this->cleanupTestData();
	}

	/**
	 * Calls the workflow interface NamedQuery service.
	 *
	 * @param string $stepInfo Extra info to log.
	 * @param string|null $name Name of article templat to search for. NULL for all article templates.
	 * @return WflNamedQueryResponse|null Response on succes. NULL on error.
	 */
	private function namedQuery( $stepInfo, $name )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		$request = new WflNamedQueryRequest();
		$request->Ticket = $this->ticket;
		$request->User   = BizSession::getShortUserName();
		$request->Query  = self::NAMEDQUERY;
		$request->Params = array();
		if( $name ) {
			$request->Params = array( new QueryParam( 'Name', '=', $name ) );
		}
		return $this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * Validate the WflNamedQueryResponse result
	 *
	 * @param WflNamedQueryResponse $response
	 * @return boolean 
	 */
	private function validateNamedQueryResp( $response )
	{
		// Prepare minimal columns we expect in the NamedQuery response.
		$minProps = array('ID', 'Name', 'Type', 'Format', 'Publication', 'PublicationId', 'Category', 'CategoryId',
			'PubChannels', 'PubChannelIds', 'State', 'Rating', 'Description');
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}
		
		// Check if all expected columns are returned.
		$columnsOk = true;
		foreach( $minProps as $minProp ) {
			if( $indexes[$minProp] == -1 ) {
				$this->setResult( 'ERROR',
					'Expected NamedQuery to return column "'.$minProp.'" but was not found.' );
				$columnsOk = false;
			}
		}
		
		// Check if we can find our article template in the response.
		$rowsOk = false;
		foreach( $response->Rows as $row ) {
			if( $row[$indexes['ID']] == $this->articleTemplateId &&
				$row[$indexes['Type']] == 'ArticleTemplate' &&
				$row[$indexes['Name']] == $this->articleTemplateName &&
				$row[$indexes['Publication']] == $this->publication->Name &&
				$row[$indexes['PublicationId']] == $this->publication->Id &&
				$row[$indexes['Category']] == $this->category->Name &&
				$row[$indexes['CategoryId']] == $this->category->Id &&
				$row[$indexes['PubChannels']] == $this->printTarget->PubChannel->Name &&
				$row[$indexes['PubChannelIds']] == $this->printTarget->PubChannel->Id &&
				$row[$indexes['State']] == $this->articleTemplateStatus->Name &&
				$row[$indexes['Rating']] == $this->articleTemplateRating &&
				$row[$indexes['Description']] == $this->articleTemplateDescription ) {
				$rowsOk = true;
				break;
			}
		}
		
		return $columnsOk && $rowsOk;
	}

	/**
	 * Creates a complete but empty MetaData data tree in memory.
	 * This is to simplify adding properties to an Object's MetaData element.
	 *
	 * @return MetaData
	 */
	private function buildEmptyMetaData()
	{
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->ExtraMetaData = array();
		return $metaData;
	}
	
	/**
	 * Creates an Article Template object (CS6 format). The template object gets 
	 * targetted for a Print channel as configured through the TESTSUITE['Issue'] option.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function createAticleTemplate()
	{
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/incopyicmt';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = null;

		$inputPath = dirname(__FILE__).'/testdata/article_template_cs6.wcmt';
		
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );

		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$objectName = 'Article Template CS6 '.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';		
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = $this->buildEmptyMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->Name = $objectName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'ArticleTemplate';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->publication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->category;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicmt';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = filesize($inputPath);
		$request->Objects[0]->MetaData->ContentMetaData->Description = $this->articleTemplateDescription;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->articleTemplateStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = $this->articleTemplateRating;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = $attachment;
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array( $this->printTarget );
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Messages = null;
		$request->AutoNaming = true;

		$stepInfo = 'Create new article template (CS6).';
		return $this->utils->callService( $this, $request, $stepInfo );
	}
	
	/**
	 * Deletes the article template that was created in this Test Case.
	 */	
	private function cleanupTestData()
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->articleTemplateId );
		$request->Permanent = true;

		// Pass null to simulate old v7 client to trigged BizException instead of 
		// more complicated error report which we then would need to parse.
		$request->Areas = null; // array( 'Workflow' ); 
		
		$stepInfo = 'Permanently deleting image object and dossier object at once.';
		$this->utils->callService( $this, $request, $stepInfo );
	}
}