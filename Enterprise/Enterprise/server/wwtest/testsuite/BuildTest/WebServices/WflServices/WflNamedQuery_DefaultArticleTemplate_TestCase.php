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
	private $vars = null;
	private $ticket = null;
	private $publication = null;
	private $publicationInfo = null;
	private $category = null;
	private $categoryInfo = null;
	private $printTarget = null;
	private $articleTemplateId = null;
	private $articleTemplateName = null;
	private $articleTemplateStatus = null;
	private $articleTemplateRating = null;
	private $articleTemplateDescription = null;

	public function getDisplayName() { return 'NamedQuery - [DefaultArticleTemplate]'; }
	public function getTestGoals()   { return 'Checks if NamedQuery works well.'; }
	public function getTestMethods() { return 'Perform NamedQuery for DefaultArticleTemplate and verify its reponse.'; }
	public function getPrio()        { return 106; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		do {
			if( !$this->setupTestData()) {
				break;
			}
			$this->runDefaultArticleTemplateNamedQueryTest();
		} while ( false );

		// Permanently delete the article template object (we created before).
		$this->cleanupTestData();
	}

	/**
	 * Setup the generic test data that can be used for all NamedQuery tests.
	 *
	 * @since 10.4.2
	 * @return bool
	 */
	private function setupTestData()
	{
		// Retrieve the data that has been determined by "Setup test data" TestCase.
		$this->vars = $this->getSessionVariables();
		$this->ticket      = $this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->publicationInfo   = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->categoryInfo      = $this->vars['BuildTest_WebServices_WflServices']['category'];
		if( !$this->ticket || !$this->publicationInfo || !$this->categoryInfo  ) {
			$this->setResult( 'ERROR', 'Could not find test data to work on.',
				'Please enable the "Setup test data" entry and try again.' );
			return false;
		}
		return true;
	}

	/**
	 * Tests and validates 'DefaultArticleTemplate' NamedQuery service call.
	 *
	 * @since 10.4.2
	 */
	private function runDefaultArticleTemplateNamedQueryTest()
	{
		$nameQuery = 'DefaultArticleTemplate';
		do {
			$artTplStatusInfo  = $this->vars['BuildTest_WebServices_WflServices']['articleTemplateStatus'];
			$this->printTarget = $this->vars['BuildTest_WebServices_WflServices']['printTarget'];
			if( !$artTplStatusInfo || !$this->printTarget ) {
				$this->setResult( 'ERROR', 'Could not find test data to test "'.$nameQuery.'" NamedQuery.',
					'Please enable the "Setup test data" entry and try again.' );
				break;
			}

			// Prepare brand, catergory and status to be used later for image object creation.
			$this->publication = new Publication( $this->publicationInfo->Id, $this->publicationInfo->Name );
			$this->category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );
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
				break;
			}

			// Search for any article templates in the system.
			$stepInfo = 'Running NamedQuery "' . $nameQuery .'" to search for any article template.';
			$resp = $this->callNamedQueryService( $stepInfo, $nameQuery, array() );
			if( $this->validateDefaultArticleTemplateNamedQueryResp( $resp ) ) {
				LogHandler::Log( 'WflNamedQuery', 'DEBUG',
					'Article template with Id: "' . $this->articleTemplateId . '" found in the search result.');
			} else {
				LogHandler::Log( 'WflNamedQuery', 'ERROR',
					'Article template with Id: "' . $this->articleTemplateId . '" not found in the search result.');
				$this->setResult( 'ERROR', 'No article template record found.',
					'Please enable the "Setup test data" entry and try again.' );
				break;
			}

			// Search the system (by name) for only that article template we've created.
			$stepInfo = 'Running NamedQuery "' . $nameQuery .'" to search for article template with Name: "' . $this->articleTemplateName . '".';

			$queryParams = array( $this->composeQueryParam( 'Name', '=', $this->articleTemplateName ) );
			$resp = $this->callNamedQueryService( $stepInfo, $nameQuery, $queryParams );
			if( $this->validateDefaultArticleTemplateNamedQueryResp( $resp ) ) {
				LogHandler::Log( 'WflNamedQuery', 'DEBUG',
					'Article template with Name: "' . $this->articleTemplateName . '" found in the search result.');
			} else {
				LogHandler::Log( 'WflNamedQuery', 'ERROR',
					'Article template with Name: "' . $this->articleTemplateName . '" not found in the search result.');
				$this->setResult( 'ERROR', 'No article template with name: ' . $this->articleTemplateName . ' found.',
					'Please enable the "Setup test data" entry and try again.' );
				break;
			}

		} while ( false );
	}

	/**
	 * Compose QueryParam and returns it.
	 *
	 * @since 10.4.2
	 * @param string $property
	 * @param string $operation
	 * @param string $value
	 * @return QueryParam
	 */
	private function composeQueryParam( string $property, string $operation, string $value ):QueryParam
	{
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';
		$queryParam = new QueryParam();
		$queryParam->Property = $property;
		$queryParam->Operation = $operation;
		$queryParam->Value = $value;
		return $queryParam;
	}

	/**
	 * Calls the workflow interface NamedQuery service.
	 *
	 * @since 10.4.2 Renamed the function name from ﻿namedQuery to callNamedQueryService and parameters are adjusted.
	 * @param string $stepInfo Extra info to log.
	 * @param string $queryName  The NamedQuery service call 'Query' parameter.
	 * @param array $queryParams List of QueryParam or empty array when no Params needed.
	 * @param int|null $firstEntry The starting number of the rows to fetch.
	 * @param int|null $maxEntries The total number of rows to fetch.
	 * @param bool|null $hierarchical True to returns tree list (including children columns and rows) instead of a list.
	 * @param array|null $order On which column to sort.
	 * @return WflNamedQueryResponse|null Response on succes. NULL on error.
	 */
	private function callNamedQueryService( string $stepInfo, string $queryName, array $queryParams,
	                                        ?int $firstEntry=null, ?int $maxEntries=null, ?bool  $hierarchical=null, ?array $order=null )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		$request = new WflNamedQueryRequest();
		$request->Ticket = $this->ticket;
		$request->Query  = $queryName;
		$request->Params = $queryParams;
		$request->FirstEntry = $firstEntry;
		$request->MaxEntries = $maxEntries;
		$request->Hierarchical = $hierarchical;
		$request->Order = $order;
		return $this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * Validate the WflNamedQueryResponse for NamedQuery 'DefaultArticleTemplateNamed'.
	 *
	 * @since 10.4.2 Renamed the function from validateNamedQueryResp to validateDefaultArticleTemplateNamedQueryResp.
	 * @param WflNamedQueryResponse $response
	 * @return boolean 
	 */
	private function validateDefaultArticleTemplateNamedQueryResp( $response )
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