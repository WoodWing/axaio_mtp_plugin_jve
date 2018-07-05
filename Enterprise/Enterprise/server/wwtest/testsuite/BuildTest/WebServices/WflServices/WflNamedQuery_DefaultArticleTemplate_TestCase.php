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
	private $utils = null;
	private $ticket = null;
	private $logInUser = null;
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
	private $imageStatus = null;
	private $dossierId1 = null;
	private $imageId1 = null;

	public function getDisplayName() { return 'NamedQuery - [DefaultArticleTemplate, Inbox]'; }
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

			$this->runInboxNamedQueryTest();
		} while ( false );

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
		$this->vars              = $this->getSessionVariables();
		$this->ticket            = $this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->publicationInfo   = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->categoryInfo      = $this->vars['BuildTest_WebServices_WflServices']['category'];
		$this->printTarget       = $this->vars['BuildTest_WebServices_WflServices']['printTarget'];
		$this->logInUser         = $this->vars['BuildTest_WebServices_WflServices']['currentUser'];

		if( !$this->ticket || !$this->publicationInfo || !$this->categoryInfo  || !$this->printTarget || !$this->logInUser ) {
			$this->setResult( 'ERROR', 'Could not find test data to work on.',
				'Please enable the "Setup test data" entry and try again.' );
			return false;
		}
		$this->publication = new Publication( $this->publicationInfo->Id, $this->publicationInfo->Name );
		$this->category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );

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
			if( $this->setupTestDataForDefaultArticleTemplateNamedQueryTest()) {
				break;
			}

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
			try {
				$message = 'Article template with Id: "' . $this->articleTemplateId . '" not found in the search result.';
				$this->validateDefaultArticleTemplateNamedQueryResp( $resp, $message );
				LogHandler::Log( 'WflNamedQuery', 'DEBUG',
					'Article template with Id: "' . $this->articleTemplateId . '" found in the search result.');
			} catch( BizException $e ) {
				// Continue with the test.
			}

			// Search the system (by name) for only that article template we've created.
			$stepInfo = 'Running NamedQuery "' . $nameQuery .'" to search for article template with Name: "' . $this->articleTemplateName . '".';
			$queryParams = array( $this->composeQueryParam( 'Name', '=', $this->articleTemplateName ) );
			$resp = $this->callNamedQueryService( $stepInfo, $nameQuery, $queryParams );
			try {
				$message = 'No article template with name: ' . $this->articleTemplateName . ' found.';
				$this->validateDefaultArticleTemplateNamedQueryResp( $resp, $message );
			} catch( BizException $e ) {
				// Continue with the test.
			}
		} while ( false );
	}

	/**
	 * Tests and validates 'Inbox' NamedQuery service call.
	 *
	 * @since 10.4.2
	 */
	private function runInboxNamedQueryTest()
	{
		$stepInfo = 'Running NamedQuery "Inbox".';
		do {
			if( $this->setupTestDataForInboxNamedQueryTest()) {
				break;
			}
			
			try {
				$hierarchical = null;
				$resp = $this->callNamedQueryService( $stepInfo, 'Inbox', array(), null, null, $hierarchical );
				$result = $this->validateInboxNamedQueryResp( $resp, $hierarchical );
			} catch ( BizException $e ) {
				// Continue with the test.
			}

			try {
				$hierarchical = false;
				$resp = $this->callNamedQueryService( $stepInfo, 'Inbox', array(), null, null, $hierarchical );
				$result = $this->validateInboxNamedQueryResp( $resp, $hierarchical );
			} catch ( BizException $e ) {
				// Continue with the test.
			}


			try {
				$hierarchical = true;
				$resp = $this->callNamedQueryService( $stepInfo, 'Inbox', array(), null, null, $hierarchical );
				$result = $this->validateInboxNamedQueryResp( $resp, $hierarchical );
			} catch ( BizException $e ) {
				// Continue with the test.
			}
		} while( false );
	}

	/**
	 * Setup test data needed to test for DefaultArticleTemplate NamedQuery.
	 *
	 * @since 10.4.2
	 * @return bool
	 */
	private function setupTestDataForDefaultArticleTemplateNamedQueryTest()
	{
		$artTplStatusInfo  = $this->vars['BuildTest_WebServices_WflServices']['articleTemplateStatus'];
		if( !$artTplStatusInfo ) {
			$this->setResult( 'ERROR', 'Could not find test data to test "'.$nameQuery.'" NamedQuery.',
				'Please enable the "Setup test data" entry and try again.' );
			return false;
		}

		$this->articleTemplateStatus = new State( $artTplStatusInfo->Id, $artTplStatusInfo->Name );
		$this->articleTemplateRating = 127;
		$this->articleTemplateDescription = __CLASS__.' Description';

		return true;
	}


	/**
	 * Setup test data needed to test for Inbox NamedQuery.
	 *
	 * @since 10.4.2
	 */
	private function setupTestDataForInboxNamedQueryTest()
	{
		$this->imageStatus      = $this->vars['BuildTest_WebServices_WflServices']['imageStatus'];
		$this->createImageInDossier();
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
	 * Creates an image and implicitly requests server to create a dossier for it as well.
	 *
	 * The image and the dossier has RouteTo set to the user logged-in for this test.
	 * This is to trigger a message for the test user's Inbox.
	 *
	 * @since 10.4.2
	 */
	private function createImageInDossier()
	{
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'image/jpeg';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = null;

		$inputPath = dirname(__FILE__).'/testdata/trashcan.jpg'; // just pick an image

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $inputPath, $attachment );

		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$imageName = 'Image in Dossier '.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = $this->buildEmptyMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->Name = $imageName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Image';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->publication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->category;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'image/jpeg';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = filesize($inputPath);
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->imageStatus;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = $this->logInUser->UserID;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = -1; // create dossier
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
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

		$stepInfo = 'Create new image object in new dossier object at once.';
		$object =  $this->utils->callService( $this, $request, $stepInfo );
		$this->assertInstanceOf( 'WflCreateObjectsResponse', $object, 'Failed creating Image in a new dossier.' );
		$image = $object->Objects[0];
		$this->imageId1 = $image->MetaData->BasicMetaData->ID;
		$this->dossierId1 = $image->Relations[0]->ParentInfo->ID;
	}

	/**
	 * Creates an Object in DB.
	 *
	 * @since 10.4.2
	 * @param Object $object
	 * @return null|Object The created Object or null.
	 */
	private function createObject( $object )
	{
		$response = null;
		try {
			require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
			$request = new WflCreateObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->Lock = false;
			$request->Objects = array( $object );
			$service = new WflCreateObjectsService();
			$response = $service->execute( $request );
		} catch( BizException $e ) {
			self::setResult( 'ERROR', 'Creating object failed.'.$e->getMessage() );
		}
		return $response ? $response->Objects[0] : null;
	}

	/**
	 * Retrieves the minimal properties passed in from the WflNamedQueryResponse->Columns.
	 *
	 * @since 10.4.2
	 * @param WflNamedQueryResponse $response
	 * @param array $minimalProps
	 * @return array
	 */
	private function retrievePropertiesFromResponseColumn( WflNamedQueryResponse $response, array $minimalProps )
	{
		$indexes = array_combine( array_values( $minimalProps ), array_fill( 1, count( $minimalProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}
		return $indexes;
	}

	/**
	 * Validates the WflNamedQueryResponse for NamedQuery 'DefaultArticleTemplateNamed'.
	 *
	 * Checks if the minimal properties are returned in the response and if the article template is returned.
	 *
	 * @since 10.4.2 Renamed the function from validateNamedQueryResp to validateDefaultArticleTemplateNamedQueryResp.
	 * @param WflNamedQueryResponse $response
	 * @param string $message
	 * @throws BizException
	 */
	private function validateDefaultArticleTemplateNamedQueryResp( WflNamedQueryResponse $response, string $message )
	{
		// Prepare minimal columns we expect in the NamedQuery response.
		$minProps = array('ID', 'Name', 'Type', 'Format', 'Publication', 'PublicationId', 'Category', 'CategoryId',
			'PubChannels', 'PubChannelIds', 'State', 'Rating', 'Description');
		$indexes = $this->retrievePropertiesFromResponseColumn( $response, $minProps );
		$this->validateMinimalPropertiesForNamedQuery( 'DefaultArticleTemplate', $minProps, $indexes );

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
		$this->assertTrue( $rowsOk, $message );
	}

	/**
	 * Validates the WflNamedQueryResponse for NamedQuery 'Inbox'.
	 *
	 * Checks if the minimal properties are returned in the response and hierarchical flag is respected.
	 *
	 * @since 10.4.2
	 * @param WflNamedQueryResponse $response
	 * @param bool|null $hierarchical
	 * @throws BizException
	 */
	private function validateInboxNamedQueryResp( WflNamedQueryResponse $response, ?bool $hierarchical )
	{
		require_once BASEDIR .'/server/bizclasses/BizNamedQuery.class.php';
		$minimalProps = BizNamedQuery::getMinimalPropsForInbox( '' );
		$propertiesFromResponse = $this->retrievePropertiesFromResponseColumn( $response, $minimalProps );
		$this->validateMinimalPropertiesForNamedQuery( 'Inbox', $minimalProps, $propertiesFromResponse );
		$this->validateReturnedChildrenBasedOnHierachicalFlag( $response, $hierarchical );
	}

	/**
	 * Checks if the children columns and rows are returned accordingly based on the NamedQuery->Hierarchical parameter.
	 *
	 * @since 10.4.2
	 * @param WflNamedQueryResponse $response
	 * @param bool|null $hierarchical
	 * @throws BizException
	 */
	private function validateReturnedChildrenBasedOnHierachicalFlag( WflNamedQueryResponse $response, ?bool $hierarchical )
	{
		switch( $hierarchical ) {
			case false:
			case null:
				$message = 'When Hierarchical in the NamedQuery request is set to null or false, ChildColumns in the NamedQuery response is expected to be empty.';
				$this->assertCount( 0, $response->ChildColumns, $message );
				break;
			case true:
				$message = 'When Hierarchical in the NamedQuery request is set to "'.$hierarchical.'", ChildColumns in the NamedQuery response is expected to be populated.';
				$this->assertNotCount( 0, $response->ChildColumns, $message );
				break;
		}
	}

	/**
	 * Checks if the minimum properties requested in the NamedQuery are returned in the response.
	 *
	 * @since 10.4.2
	 * @param string $queryName
	 * @param array $minimalProps
	 * @param array $propertiesFromResponse
	 * @throws BizException
	 */
	private function validateMinimalPropertiesForNamedQuery( string $queryName, array $minimalProps, array $propertiesFromResponse )
	{
		// Check if all expected columns are returned.
		$columnsOk = true;
		if( $minimalProps ) foreach( $minimalProps as $minProp ) {
			if( $propertiesFromResponse[$minProp] == -1 ) {
				$this->setResult( 'ERROR',
					'Expected NamedQuery "'.$queryName.'" to return column "'.$minProp.'" but was not found.' );
				$columnsOk = false;
				// will not break here to continue searching if there's any more missing properties.
			}
		}
		$this->assertTrue( $columnsOk );
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
	 * Deletes objects permanently.
	 *
	 * @since 10.4.2
	 * @param array $ids
	 * @param string $stepInfo
	 * @return mixed
	 */
	private function callDeleteObjectsService( array $ids, string $stepInfo )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $ids;
		$request->Permanent = true;
		$request->Areas = array( 'Workflow' );
		return $this->utils->callService( $this, $request, $stepInfo );
	}
	
	/**
	 * Clear and deletes all test objects that were created in this Test Case.
	 */	
	private function cleanupTestData()
	{
		$totalObjectsToBeDeleted = 0;
		$objectIdsToBeDeleted = array();
		$objectsTypeToBeDeleted = array();
		if( $this->articleTemplateId ) {
			$objectIdsToBeDeleted[] = $this->articleTemplateId;
			$objectsTypeToBeDeleted[] = 'article template('.$this->articleTemplateId.')';
			$totalObjectsToBeDeleted++;
		}

		if( $this->dossierId1 ) {
			$objectIdsToBeDeleted[] = $this->dossierId1;
			$objectsTypeToBeDeleted[] = 'dossier('.$this->dossierId1.')';
			$totalObjectsToBeDeleted++;
		}

		if( $this->imageId1 ) {
			$objectIdsToBeDeleted[] = $this->imageId1;
			$objectsTypeToBeDeleted[] = 'image('.$this->imageId1.')';
			$totalObjectsToBeDeleted++;
		}

		if( $totalObjectsToBeDeleted ) {
			$stepInfo = 'Permanently deleting ' . implode( ',', $objectsTypeToBeDeleted ) . '.';
			$response = $this->callDeleteObjectsService( $objectIdsToBeDeleted, $stepInfo );
			$message = 'Test objects did not get cleaned up properly for NamedQuery BuildTest.';
			$this->assertCount( $totalObjectsToBeDeleted, $response->IDs, $message );
		}
		$this->articleTemplateId = null;
		$this->dossierId1 = null;
		$this->imageId1 = null;
	}
}