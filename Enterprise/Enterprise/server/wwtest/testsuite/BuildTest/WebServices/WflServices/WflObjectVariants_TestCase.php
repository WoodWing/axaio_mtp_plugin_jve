<?php
/**
 * @since v10.4.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflObjectVariants_TestCase extends TestCase
{
	/** @var string */
	private $ticket = null;

	/** @var PublicationInfo  */
	private $publicationInfo = null;

	/** @var CategoryInfo */
	private $categoryInfo = null;

	/** @var State */
	private $articleStatus = null;

	/** @var WW_Utils_TestSuite */
	private $utils;

	/** @var BizTransferServer $transferServer  */
	private $transferServer = null;

	/** @var integer[] */
	private $articleIds = array();


	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	public function getDisplayName() { return 'Object Variants'; }
	public function getTestGoals()   { return 'Checks if the MasterId property is handled well by the workflow web services.'; }
	public function getPrio()        { return 450; }
	public function isSelfCleaning() { return true; }

	public function getTestMethods()
	{
		return
			'Validate the MasterId property after calling all kind of workflow services: <ul>'.
				'<li>Create an article to be used as the original/master object (CreateObjects).</li>'.
				'<li>Make a copy of that article and validate the MasterId (CopyObjects).</li>'.
				'<li>Attempt to adjust the MasterId which should be read-only (SetObjectProperties, MultiSetObjectProperties, SaveObjects)</li>'.
				'<li>Make a copy of the copied article (c-o-c) and validate the MasterId (CopyObjects).</li>'.
				'<li>Trash and restore the c-o-c article and validate the MasterId (DeleteObjects, RestoreObjects).</li>'.
				'<li>Search for the MasterId and validate whether all three articles are returned (NamedQuery).</li>'.
				'<li>Simulate a client performing a Save As of the article and validate the MasterId (GetObjects, CreateObjects).</li>'.
				'<li>Cleanup all the articles created/copied for this test.</li>'.
			'</ul>';
	}

	// - - - - - - - - - - - - - - - - - - - - TEST SCRIPTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	final public function runTest()
	{
		try {
			$this->setupTestData();

			// Create master.
			$newArticle = $this->createArticle( false, null );
			$masterId = $newArticle->MetaData->BasicMetaData->ID;
			$this->assertNotEquals( '0', $masterId );
			$newArticle = $this->getArticle( $masterId, false );
			$this->assertEquals( '0', $newArticle->MetaData->BasicMetaData->MasterId );

			// Make copy of master.
			$copyOfArticle = $this->copyArticle( $masterId );
			$this->assertNotEquals( '0', $copyOfArticle->MetaData->BasicMetaData->ID );
			$this->assertEquals( $masterId, $copyOfArticle->MetaData->BasicMetaData->MasterId );
			$copyOfArticle = $this->getArticle( $copyOfArticle->MetaData->BasicMetaData->ID, false );
			$this->assertEquals( $masterId, $copyOfArticle->MetaData->BasicMetaData->MasterId );

			// Attempt to save different master id, which should be ignore by ES.
			$this->testReadonlyMasterId( $masterId, $copyOfArticle->MetaData->BasicMetaData->ID );

			// Make copy of copy.
			$copyOfCopyOfArticle = $this->copyArticle( $masterId );
			$this->assertNotEquals( '0', $copyOfCopyOfArticle->MetaData->BasicMetaData->ID );
			$this->assertEquals( $masterId, $copyOfCopyOfArticle->MetaData->BasicMetaData->MasterId );
			$copyOfCopyOfArticle = $this->getArticle( $copyOfCopyOfArticle->MetaData->BasicMetaData->ID, false );
			$this->assertEquals( $masterId, $copyOfCopyOfArticle->MetaData->BasicMetaData->MasterId );

			// Trash and restore the article from the Trash Can to check master id from trash.
			$this->deleteArticle( $copyOfCopyOfArticle->MetaData->BasicMetaData->ID, false );
			$this->restoreArticle( $copyOfCopyOfArticle->MetaData->BasicMetaData->ID );
			$copyOfCopyOfArticle = $this->getArticle( $copyOfCopyOfArticle->MetaData->BasicMetaData->ID, false );
			$this->assertEquals( $masterId, $copyOfCopyOfArticle->MetaData->BasicMetaData->MasterId );

			// Search for master and its copies.
			$masterIds = $this->namedQuery( $masterId );
			$this->assertCount( 3, $masterIds );
			$this->assertEquals( 0, $masterIds[$masterId] );
			$this->assertEquals( $masterId, $masterIds[$copyOfArticle->MetaData->BasicMetaData->ID] );
			$this->assertEquals( $masterId, $masterIds[$copyOfCopyOfArticle->MetaData->BasicMetaData->ID] );

			// Simulate client application that does a Save-As operation.
			$saveAsArticle = $this->createArticle( false, $masterId );
			$this->assertNotEquals( '0', $saveAsArticle->MetaData->BasicMetaData->ID );
			$this->assertEquals( $masterId, $saveAsArticle->MetaData->BasicMetaData->MasterId );
			$saveAsArticle = $this->getArticle( $saveAsArticle->MetaData->BasicMetaData->ID, false );
			$this->assertEquals( $masterId, $saveAsArticle->MetaData->BasicMetaData->MasterId );

		} catch( BizException $e ) {
		}
		$this->tearDownTestData();
	}

	/**
	 * Construct data used by this test script.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		// Retrieve the data that has been determined by "Setup test data" TestCase.
		$vars = $this->getSessionVariables();
		$this->ticket          = $vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->publicationInfo = $vars['BuildTest_WebServices_WflServices']['publication'];
		$this->categoryInfo    = $vars['BuildTest_WebServices_WflServices']['category'];
		$this->articleStatus   = $vars['BuildTest_WebServices_WflServices']['articleStatus'];
		if( !$this->ticket || !$this->publicationInfo || !$this->categoryInfo || !$this->articleStatus ) {
			$this->throwError( 'Could not find test data to work on. Please enable the "Setup test data" entry and try again.' );
		}
	}

	/**
	 * Destruct data used by this test script.
	 */
	private function tearDownTestData()
	{
		if( $this->articleIds ) foreach( $this->articleIds as $articleId ) {
			try {
				$this->deleteArticle( $articleId, true );
			} catch( BizException $e ) {
			}
		}
	}

	/**
	 * Check if the MasterId property can not be changed through SaveObjects.
	 *
	 * @param string $masterId
	 * @param string $articleId
	 */
	private function testReadonlyMasterId( $masterId, $articleId )
	{
		$article = $this->getArticle( $articleId, true );
		$this->assertEquals( $masterId, $article->MetaData->BasicMetaData->MasterId );

		// Check readonly MasterId for SaveObjects.
		$article->MetaData->BasicMetaData->MasterId = strval( PHP_INT_MAX -1 );
		$article = $this->saveArticle( $article );
		$this->assertEquals( $masterId, $article->MetaData->BasicMetaData->MasterId );
		$article = $this->getArticle( $articleId, false );
		$this->assertEquals( $masterId, $article->MetaData->BasicMetaData->MasterId );
		$this->unlockArticle( $article->MetaData->BasicMetaData->ID );
	}

	// - - - - - - - - - - - - - - - - - - - - - SERVICE CALLS - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Create an article object with simple plain text file.
	 *
	 * @param boolean $lock
	 * @param string $masterId
	 * @return Object
	 */
	private function createArticle( $lock, $masterId )
	{
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';

		$attachment = $this->composeArticleAttachment();

		$article = new Object();
		$article->MetaData = $this->composeArticleMetaData( null, $this->composeArticleName(), $attachment );
		if( $masterId ) {
			$article->MetaData->BasicMetaData->MasterId = $masterId;
		}
		$article->Files = array( $attachment );

		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = $lock;
		$request->Objects = array( $article );

		/** @var WflCreateObjectsResponse $response */
		$this->transferServer->writeContentToFileTransferServer( $attachment->Content, $attachment );
		$response = $this->utils->callService( $this, $request, 'Create article' );
		$this->assertInstanceOf( 'WflCreateObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->articleIds[] = $response->Objects[0]->MetaData->BasicMetaData->ID;

		return $response->Objects[0];
	}

	/**
	 * Delete an article object.
	 *
	 * @param string $articleId
	 * @param boolean $permanent TRUE to delete permanently, FALSE to send to Trash Can
	 */
	private function deleteArticle( $articleId, $permanent )
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';

		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $articleId );
		$request->Permanent = $permanent;
		$request->Areas = array( 'Workflow' );

		/** @var WflDeleteObjectsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Delete article' );
		$this->assertInstanceOf( 'WflDeleteObjectsResponse', $response );
		$this->assertEquals( $articleId, $response->IDs[0] );
	}

	/**
	 * Restore an article object from the Trash Can.
	 *
	 * @param string $articleId
	 */
	private function restoreArticle( $articleId )
	{
		require_once BASEDIR . '/server/services/wfl/WflRestoreObjectsService.class.php';

		$request = new WflRestoreObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $articleId );

		/** @var WflRestoreObjectsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Restore article' );
		$this->assertInstanceOf( 'WflRestoreObjectsResponse', $response );
		$this->assertEquals( $articleId, $response->IDs[0] );
	}

	/**
	 * Make a copy of an article object.
	 *
	 * @param string $articleId The article object id to copy.
	 * @return Object The copied article.
	 */
	private function copyArticle( $articleId )
	{
		require_once BASEDIR . '/server/services/wfl/WflCopyObjectService.class.php';

		$request = new WflCopyObjectRequest();
		$request->Ticket = $this->ticket;
		$request->SourceID = $articleId;
		$request->MetaData = $this->composeArticleMetaData( null, $this->composeArticleName(), null );
		$request->Relations = null;
		$request->Targets = null;

		/** @var WflCopyObjectResponse $response */
		$response = $this->utils->callService( $this, $request, 'Copy article' );
		$this->assertInstanceOf( 'WflCopyObjectResponse', $response );
		$this->assertInstanceOf( 'MetaData', $response->MetaData );
		$this->articleIds[] = $response->MetaData->BasicMetaData->ID;

		$article = new Object();
		$article->MetaData = $response->MetaData;
		$article->Targets = $response->Targets;
		$article->Relations = $response->Relations;

		return $article;
	}

	/**
	 * Retrieve an article object.
	 *
	 * @param string $articleId
	 * @param boolean $lock Whether or not to lock for editing.
	 * @return Object
	 */
	private function getArticle( $articleId, $lock )
	{
		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';

		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $articleId );
		$request->RequestInfo = array( 'MetaData' );
		$request->Rendition = 'none';
		$request->Lock = $lock;

		/** @var WflGetObjectsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Get article' );
		$this->assertInstanceOf( 'WflGetObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );

		return $response->Objects[0];
	}

	/**
	 * Store an article object.
	 *
	 * @param Object $article
	 * @return Object
	 */
	private function saveArticle( Object $article )
	{
		require_once BASEDIR . '/server/services/wfl/WflSaveObjectsService.class.php';

		$attachment = $this->composeArticleAttachment();
		$article->Files = array( $attachment );

		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Objects = array( $article );
		$request->Unlock = false;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;

		/** @var WflSaveObjectsResponse $response */
		$this->transferServer->writeContentToFileTransferServer( $attachment->Content, $attachment );
		$response = $this->utils->callService( $this, $request, 'Save article' );
		$this->assertInstanceOf( 'WflSaveObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );

		return $response->Objects[0];
	}

	/**
	 * Release the lock for editing of an article object.
	 *
	 * @param string $articleId
	 */
	private function unlockArticle( $articleId )
	{
		require_once BASEDIR . '/server/services/wfl/WflUnlockObjectsService.class.php';

		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $articleId );

		/** @var WflUnlockObjectsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Unlock article' );
		$this->assertInstanceOf( 'WflUnlockObjectsResponse', $response );
	}

	/**
	 * Change properties for an article object (through SetObjectProperties).
	 *
	 * @param MetaData $articleMetaData Properties to change.
	 * @return MetaData Changed article properties.
	 */
	private function setArticleProperties( MetaData $articleMetaData )
	{
		require_once BASEDIR . '/server/services/wfl/WflSetObjectPropertiesService.class.php';

		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $articleMetaData->BasicMetaData->ID;
		$request->MetaData = $articleMetaData;
		$request->Targets = null;

		/** @var WflSetObjectPropertiesResponse $response */
		$response = $this->utils->callService( $this, $request, 'Set article properties' );
		$this->assertInstanceOf( 'WflSetObjectPropertiesResponse', $response );
		$this->assertInstanceOf( 'MetaData', $response->MetaData );

		return $response->MetaData;
	}

	/**
	 * Change properties for an article object (through MultiSetObjectProperties).
	 *
	 * @param string $articleId
	 * @param MetaDataValue[] $articleMetaDataValues
	 */
	private function multisetArticleProperties( $articleId, $articleMetaDataValues )
	{
		require_once BASEDIR . '/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';

		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->MetaData = $articleMetaDataValues;
		$request->IDs = array( $articleId );

		/** @var WflMultiSetObjectPropertiesResponse $response */
		$response = $this->utils->callService( $this, $request, 'Multiset article properties' );
		$this->assertInstanceOf( 'WflMultiSetObjectPropertiesResponse', $response );
	}

	/**
	 * Query articles objects having ID or MasterId set to provided $masterId (through NamedQuery).
	 *
	 * This way clients can find out all the variants created from a certain 'master' object including the master itself.
	 * Note that the Named Query is built into the core server (hard-coded).
	 *
	 * @param string $masterId
	 * @return array
	 */
	private function namedQuery( $masterId )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';

		$request = new WflNamedQueryRequest();
		$request->Ticket = $this->ticket;
		$request->Query  = 'OriginalObjectsAndVariants';
		$request->Params = array();
		$request->Params[0] = new QueryParam();
		$request->Params[0]->Property = 'MasterId';
		$request->Params[0]->Operation = '=';
		$request->Params[0]->Value = $masterId;

		/** @var WflNamedQueryResponse $response */
		$response = $this->utils->callService( $this, $request, 'Query articles' );
		$this->assertInstanceOf( 'WflNamedQueryResponse', $response );

		// Determine column indexes to work with, and map them.
		$minProps = array( 'ID', 'Type', 'Name', 'MasterId' );
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found
				}
			}
		}

		// Return ObjectId->MasterId mapping table.
		$masterIds = array();
		foreach( $response->Rows as $row ) {
			$masterIds[$row[$indexes['ID']]] = $row[$indexes['MasterId']];
		}
		return $masterIds;
	}

	// - - - - - - - - - - - - - - - - - - - - - DATA COMPOSERS - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Compose a unique name for an article object.
	 *
	 * @return string The article name.
	 */
	private function composeArticleName()
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$name = 'Article_'.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;
		return $name;
	}

	/**
	 * Build a MetaData for an article workflow object.
	 *
	 * @param int $articleId
	 * @param string $articleName
	 * @param Attachment|null $attachment
	 * @return MetaData
	 */
	private function composeArticleMetaData( $articleId, $articleName, $attachment )
	{
		$md = new MetaData();

		$md->BasicMetaData = new BasicMetaData();
		$md->BasicMetaData->ID = $articleId;
		$md->BasicMetaData->Name = $articleName;
		$md->BasicMetaData->Type = 'Article';
		$md->BasicMetaData->Publication = new Publication( $this->publicationInfo->Id, $this->publicationInfo->Name );;
		$md->BasicMetaData->Category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );;

		if( $attachment ) {
			$md->ContentMetaData = new ContentMetaData();
			$md->ContentMetaData->Format = 'text/plain';
			$md->ContentMetaData->FileSize = strlen( $attachment->Content );
			$md->ContentMetaData->PlainContent = $attachment->Content;
		}

		$md->WorkflowMetaData = new WorkflowMetaData();
		$md->WorkflowMetaData->State = new State( $this->articleStatus->Id, $this->articleStatus->Name );

		return $md;
	}

	/**
	 * Compose a simple native plain-text attachment data object for an article object.
	 *
	 * @return Attachment
	 */
	private function composeArticleAttachment()
	{
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'text/plain';
		$attachment->Content = 'To temos aut explabo. Ipsunte plat. Em accae eatur? Ihiliqui oditatem.';
		return $attachment;
	}
}