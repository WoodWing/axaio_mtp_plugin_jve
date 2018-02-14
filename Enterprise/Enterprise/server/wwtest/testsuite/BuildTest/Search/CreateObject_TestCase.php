<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_CreateObject_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName()
	{
		return 'Create an Object for Solr Search';
	}

	public function getTestGoals()
	{
		return 'Creates an object and validates if the object is indexed by Solr Search';
	}

	public function getTestMethods()
	{
		return 'Creates an object using CreateObjects and checks if the index flag is set.';
	}

	public function getPrio()
	{
		return 2;
	}

	final public function runTest()
	{
		parent::runTest();
		if( $this->hasError() ) {
			return;
		}

		if( !$this->validateCreateObject() ) {
			return;
		}

		// Update session vars for next test cases
		$this->setSessionVariables( $this->vars );
	}

	public function __construct()
	{
	}

	/**
	 * Test creating an object.
	 * Validate the object was indexed in Solr.
	 */
	public function validateCreateObject()
	{
		// Create a test article object
		$articleName = 'Article_' .date("m d H i s");
		$this->vars['BuildTest_Search']['ArticleName'] = $articleName;
		$articleObj = $this->buildArticleObject( null, $articleName );
		$createObjects = array($articleObj);
		if( !$this->utils->uploadObjectsToTransferServer( $this, $createObjects ) ) {
			return false;
		}
		$objects = $this->utils->callCreateObjectService( $this, $this->vars['BuildTest_Search']['ticket'], $createObjects );
		if( is_null($objects) ) {
			return false;
		}

		$this->vars['BuildTest_Search']['ArticleID'] = $objects->Objects[0]->MetaData->BasicMetaData->ID;
		$this->vars['BuildTest_Search']['Article'] = $objects->Objects[0];

		// Validate the Article was index (i.e. flagged)
		// Note: when there are no search connectors, the object is not flagged as "indexed"
		$isSolrSearch = BizServerPlugin::isPluginActivated( 'SolrSearch' );
		$articleID = $this->vars['BuildTest_Search']['ArticleID'];
		$prevObjectID = (string)( ((int)$articleID)-1 ); // Note: getIndexedObjects gets the next object, so get prev id

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objects = DBObject::getIndexedObjects( $prevObjectID, 1);
		if( $isSolrSearch ) {
			if( empty($objects) || $objects[0] != $articleID ) {
				$this->setResult( 'ERROR', 'Created object is not indexed',
					'Check the Object in Solr/database' );
				return false;
			}
		} else {
			if( !empty($objects) && $objects[0] == $articleID ) {
				$this->setResult( 'ERROR', 'Created object is indexed, but Solr is not activated',
					'Check the Object in Solr/database' );
				return false;
			}
		}

		return true;
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
		$pubInfo = $this->vars['BuildTest_Search']['Brand'];
		$categoryInfo = $this->vars['BuildTest_Search']['Category'];

		$publ = new Publication( $pubInfo->Id, $pubInfo->Name );
		$category = new Category( $categoryInfo->Id, $categoryInfo->Name );

		$articleStatusInfo = $this->utils->getFirstStatusInfoForType( $this, $pubInfo, 'Article' );
		if( is_null($articleStatusInfo) ) {
			return null;
		}

		// retrieve user (shortname) of the logOn test user.
		$user = BizSession::checkTicket( $this->vars['BuildTest_Search']['ticket'] );

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
		$cntMD->PlainContent = 'some searchable content';
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline = date('Y-m-d\TH:i:s');
		$wflMD->Urgency = 'Top';
		$wflMD->State = new State( $articleStatusInfo->Id, $articleStatusInfo->Name );
		$wflMD->RouteTo = $user;
		$wflMD->Comment = 'Creating Object for Solr BuildTest';
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
}
