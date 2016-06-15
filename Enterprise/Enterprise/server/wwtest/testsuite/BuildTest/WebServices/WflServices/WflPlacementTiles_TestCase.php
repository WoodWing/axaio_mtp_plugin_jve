<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflPlacementTiles_TestCase extends TestCase
{
	private $ticket 	= null;
	private $suiteOpts 	= null;
	private $wflServicesUtils = null; // WW_TestSuite_BuildTest_WebServices_WflServices_Utils
	private $pubObj 	= null;
	private $issueObj 	= null;
	private $editionObj	= null;
	private $pubChannelObj	= null;
	private $layoutObj	= null;
	private $articleObj	= null;
	private $layoutStatus	= null;
	private $articleStatus 	= null;
	private $placementTiles = null;
	
	public function getDisplayName() { return 'Placement Tiles'; }
	public function getTestGoals()   { return 'Checks if CreateObjects/SaveObjects/GetObjects/GetPagesInfo/DeleteObjects service call with placement tiles is running fine. '; }
	public function getTestMethods() { return 'Call CreateObjects/SaveObjects/GetObjects/GetPagesInfo/DeleteObjects service to check on placement tiles.'; }
    public function getPrio()        { return 108; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		if( !$this->wflServicesUtils->initTest( $this, 'PMT' ) ) {
			return;
		}

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = $vars['BuildTest_WebServices_WflServices']['ticket'];
   		$this->pubObj = $vars['BuildTest_WebServices_WflServices']['publication'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}
		$this->suiteOpts = unserialize( TESTSUITE );

		try {
			// Resolve the admin entities configured for the brand.
			$this->resolveBrandSetup();
			
			// Create article object.
			$this->createArticleObject();
			
			// Create Layout object with placement tiles.
			$this->createLayoutObject();
	
			// Save Layout object with updated placement tiles.
			$this->saveLayoutObject();
	
			// Get Layout object with placement tiles.
			$this->getLayoutObject();

			// Get pages info with placement info that's enriched with the placement tiles.
			$this->getPagesInfo();
		}
		catch( BizException $e ) {
			$e = $e; // keep analyzer happy
		}

		// Delete the layout and article objects.
		$this->tearDownTestData();
	}

	/**
	 * Create Layout object
	 *
	 * @throws BizException on failure
	 */
	public function createLayoutObject()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';

		// Create layout
		$layoutName = 'LayTest '.date("m d H i s");
		$layoutObj 	= $this->buildLayoutObject( null, $layoutName );
		$request = new WflCreateObjectsRequest();
		$request->Ticket 	= $this->ticket;
		$request->Lock 		= true;
		$request->Objects 	= array( $layoutObj );
		
		$stepInfo = 'Creating the layout object.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );

		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		$placementTiles = $response->Objects[0]->Relations[0]->Placements[0]->Tiles;
		$this->validatePlacementTiles( $placementTiles, 'CreateObjects' );	
	}

	/**
	 * Save Layout object
	 *
	 * @throws BizException on failure
	 */
	public function saveLayoutObject()
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';

		$layoutId 	= $this->layoutObj->MetaData->BasicMetaData->ID;
		$layoutName = $this->layoutObj->MetaData->BasicMetaData->Name;
		$layoutObj 	= $this->buildLayoutObject( $layoutId, $layoutName );
		$layoutObj->Relations = $this->layoutObj->Relations;
		$layoutObj->Relations[0]->Placements[0]->Tiles = $this->buildPlacementTiles(); // Update placement tiles

		$request = new WflSaveObjectsRequest();
		$request->Ticket 		= $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn  = true;
		$request->Unlock 		= true;
		$request->Objects 		= array( $layoutObj );
		
		$stepInfo = 'Saving the layout object.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );

		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		$placementTiles = $response->Objects[0]->Relations[0]->Placements[0]->Tiles;
		$this->validatePlacementTiles( $placementTiles, 'SaveObjects' );	
	}

	/**
	 * Get Layout object
	 *
	 * @throws BizException on failure
	 */
	private function getLayoutObject()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';

		$layoutId = $this->layoutObj->MetaData->BasicMetaData->ID;
		$request = new WflGetObjectsRequest();
		$request->Ticket= $this->ticket;
		$request->IDs	= array( $layoutId );
		$request->Lock	= false;
		$request->Rendition = 'none';
		
		$stepInfo = 'Getting the layout object.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );

		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->layoutObj = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );

		$placementTiles = $response->Objects[0]->Relations[0]->Placements[0]->Tiles;
		$this->validatePlacementTiles( $placementTiles, 'GetObjects' );	
	}

	/**
	 * Get Pages info
	 *
	 * @throws BizException on failure
	 */
	private function getPagesInfo()
	{
		$layoutId = $this->layoutObj->MetaData->BasicMetaData->ID;

		require_once BASEDIR.'/server/services/wfl/WflGetPagesInfoService.class.php';
		$request = new WflGetPagesInfoRequest();
		$request->Ticket 	= $this->ticket;
		$request->Issue		= $this->issueObj;
		$request->IDs 		= array( $layoutId );
		$request->Edition 	= $this->editionObj;

		$stepInfo = 'Getting page information.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		$this->validateGetPagesInfo( $response );
	}

	/**
	 * Delete all the test objects
	 */
	private function tearDownTestData()
	{
		$objIds = array();
		if( isset($this->layoutObj->MetaData->BasicMetaData->ID) ) {
			$objIds[] = $this->layoutObj->MetaData->BasicMetaData->ID;
		}
		if( isset($this->articleObj->MetaData->BasicMetaData->ID) ) {
			$objIds[] = $this->articleObj->MetaData->BasicMetaData->ID;
		}
		if( $objIds ) {
			try {
				require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
				$request = new WflDeleteObjectsRequest();
				$request->Ticket 	= $this->ticket;
				$request->IDs 		= $objIds;
				$request->Permanent = true;
		
				$stepInfo = 'Delete the layout and article objects.';
				$response = $this->wflServicesUtils->callService( $request, $stepInfo );
		
				if( $response && $response->Reports ) { // Introduced in v8.0
					$errMsg = '';
					foreach( $response->Reports as $report ){
						foreach( $report->Entries as $reportEntry ) {
							$errMsg .= $reportEntry->Message . PHP_EOL;
						}
					}
					if( $errMsg ) {
						$this->throwError( 'DeleteObjects: failed: "'.$errMsg.'"' );
					}
				}
			} catch( BizException $e ) {
				$e = $e; // keep analyzer happy
			}
		}
	}

	/**
	 * Create article object
	 *
	 * @throws BizException on failure
	 */
	private function createArticleObject()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';

		// Create article
		$articleName = 'ArtTest '.date("m d H i s");
		$articleObj  = $this->buildArticleObject( $articleName );
		$request = new WflCreateObjectsRequest();
		$request->Ticket 	= $this->ticket;
		$request->Lock 		= false;
		$request->Objects 	= array( $articleObj );
		
		$stepInfo = 'Creating the article object.';
		$response = $this->wflServicesUtils->callService( $request, $stepInfo );

		$this->assertInstanceOf( 'Object', $response->Objects[0] );
		$this->articleObj = $response->Objects[0];

		$id = @$response->Objects[0]->MetaData->BasicMetaData->ID;
		$this->assertGreaterThan( 0, $id );
	}

	/**
	 * Build workflow layout object
	 *
	 * @param integer $layoutId
	 * @param string $layoutName
	 * @return Object $object Layout object
	 */
	private function buildLayoutObject( $layoutId, $layoutName )
	{
		require_once BASEDIR .'/server/bizclasses/BizTransferServer.class.php';
		
		$nativeFilePath   = dirname(__FILE__) . '/testdata/native1.indd';
		$preview1FilePath = dirname(__FILE__) . '/testdata/preview1page1.jpg';
		$preview2FilePath = dirname(__FILE__) . '/testdata/preview1page2.jpg';
		$fileSize = filesize( $nativeFilePath );

		// Layout
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'application/indesign';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $nativeFilePath, $attachment );
		$files = array( $attachment );	
	
		// Page1
		$attachment = new Attachment();
		$attachment->Rendition = 'preview';
		$attachment->Type = 'image/jpeg';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $preview1FilePath, $attachment );
		$pag1Att = array( $attachment );	

		$page1 = new Page();
		$page1->Width = 400;
		$page1->Height = 300;
		$page1->PageNumber = 'pag1';
		$page1->PageOrder = 1;
		$page1->PageSequence = 1;
		$page1->Files = $pag1Att;
		$page1->Master = 'Master';
		$page1->Instance = 'Production';
		
		// Page2
		$attachment = new Attachment();
		$attachment->Rendition = 'preview';
		$attachment->Type = 'image/jpeg';
		$transferServer = new BizTransferServer();
		$transferServer->copyToFileTransferServer( $preview2FilePath, $attachment );
		$pag2Att = array( $attachment );

		$page2 = new Page();
		$page2->Width = 400;
		$page2->Height = 300;
		$page2->PageNumber = 'pag2';
		$page2->PageOrder = 2;
		$page2->PageSequence = 2;
		$page2->Files = $pag2Att;
		$page2->Master = 'Master';
		$page2->Instance = 'Production';
		
		$pages 	= array( $page1, $page2 );
		$meta = $this->buildLayoutMetaData( $layoutName, $fileSize, $layoutId );
		$target = new Target( $this->pubChannelObj, $this->issueObj, array( $this->editionObj) );
		$object = new Object();
		$object->MetaData	= $meta;
		$object->Files		= $files;
		$object->Relations  = array( $this->buildRelation() );
		$object->Pages		= $pages;
		$object->Targets	= array( $target );

		return $object;
	}

	/**
	 * Build layout metadata object
	 *
	 * @param string $layoutName
	 * @param integer $fileSize
	 * @param integer $layoutId
	 * @return Object $metaData MetaData object
	 */
	private function buildLayoutMetaData( $layoutName, $fileSize, $layoutId=null )
	{
		// build metadata
		$basicMD = new BasicMetaData();
		$basicMD->ID 	= $layoutId;
		$basicMD->Name 	= $layoutName;
		$basicMD->Type 	= 'Layout';
		$basicMD->Publication 	= new Publication( $this->pubObj->Id, $this->pubObj->Name );
		$basicMD->Category 		= new Category( $this->category->Id, $this->category->Name );
		$cntMD = new ContentMetaData();	
		$cntMD->Format 	= 'application/indesign';
		$cntMD->FileSize= $fileSize;
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline= date('Y-m-d\TH:i:s'); 
		$wflMD->State 	= new State( $this->layoutStatus->Id, $this->layoutStatus->Name );
		$wflMD->Comment = 'CREATE LAYOUT OBJECT';
		$wflMD->RouteTo = $this->suiteOpts['User'];

		$metaData = new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $cntMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();

		return $metaData;
	}

	/**
	 * Build article object 
	 *
	 * @param string $articleName
	 * @return Object $object 
	 */
	private function buildArticleObject( $articleName )
	{
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
		$fileSize = strlen($content);

		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'text/plain';
		$attachment->FilePath = null;

		require_once BASEDIR .'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer( $content, $attachment );

		$files = array( $attachment );		
		$meta 	= $this->buildArticleMetaData( $articleName, $fileSize );
		$object = new Object();
		$object->MetaData = $meta;
		$object->Files = $files;

		return $object;
	}

	/**
	 * Build workflow article metadata
	 *
	 * @param string $articleName
	 * @param integer $fileSize
	 * @return Object $metaData MetaData object
	 */
	private function buildArticleMetaData( $articleName, $fileSize )
	{
		// build metadata
		$basicMD = new BasicMetaData();
		$basicMD->Name = $articleName;
		$basicMD->Type = 'Article';
		$basicMD->Publication = new Publication( $this->pubObj->Id, $this->pubObj->Name );
		$basicMD->Category = new Category( $this->category->Id, $this->category->Name );
		$cntMD = new ContentMetaData();
		$cntMD->Keywords	= array('Key', 'word');	
		$cntMD->Slugline	= 'slug';
		$cntMD->Width 		= 123;
		$cntMD->Height 		= 45;
		$cntMD->Format 		= 'text/plain';
		$cntMD->FileSize	= $fileSize;
		$cntMD->Columns 	= 4;
		$cntMD->LengthWords = 300;
		$cntMD->LengthChars = 1200;
		$cntMD->LengthParas = 4;
		$cntMD->LengthLines = 12;
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline= date('Y-m-d\TH:i:s'); 
		$wflMD->State 	= new State( $this->articleStatus->Id, $this->articleStatus->Name );
		$wflMD->Comment = 'CREATE ARTICLE OBJECT';

		$metaData = new MetaData();
		$metaData->BasicMetaData    = $basicMD;
		$metaData->RightsMetaData   = new RightsMetaData();
		$metaData->SourceMetaData   = new SourceMetaData();
		$metaData->ContentMetaData  = $cntMD;
		$metaData->WorkflowMetaData = $wflMD;
		$metaData->ExtraMetaData    = array();
		
		return $metaData;
	}

	/**
	 * Resolve Issue/Status/Pubchannel/Category/Edition
	 *
	 * @throws BizException when not all entities could be resolved
	 */
	private function resolveBrandSetup()
	{
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
			$globAuth->getRights( $this->suiteOpts['User'] ); // Cache the rights, otherwise the issues aren't returned
		}

		// Determine the issue object
		require_once BASEDIR .'/server/bizclasses/BizPublication.class.php';
		$issues = BizPublication::getIssues($this->suiteOpts['User'], $this->pubObj->Id);
		foreach( $issues as $issue ) {
			if( $issue->Name == $this->suiteOpts['Issue'] ) {
				$iss = new Issue();
				$iss->Id = $issue->Id;
				$iss->Name = $issue->Name;
				$this->issueObj = $iss;
				break;
			}
		}
		$this->assertNotNull( $this->issueObj,
				'Could not find the test Issue: '.$this->suiteOpts['Issue'], 
				'Please check the TESTSUITE setting in configserver.php.' );

		// Determine the layout status object
		foreach( $this->pubObj->States as $status ) {
			if( $status->Type == 'Layout' ) {
				if( $status->Id != -1 ) { // prefer non-personal status
					$this->layoutStatus = $status;
					break;
				}
			}
		}
		$this->assertNotNull( $this->layoutStatus,
				'Could not find a Layout status configured for brand "'.$this->pubObj->Name.'".', 
				'Please check your brand setup (or the TESTSUITE setting in configserver.php).' );

		// Determine the article status object
		foreach( $this->pubObj->States as $status ) {
			if( $status->Type == 'Article' ) {
				if( $status->Id != -1 ) { // prefer non-personal status
					$this->articleStatus = $status;
					break;
				}
			}
		}	
		$this->assertNotNull( $this->articleStatus,
				'Could not find a Article status configured for brand "'.$this->pubObj->Name.'".', 
				'Please check your brand setup (or the TESTSUITE setting in configserver.php).' );

		// Determine pubchannel object
		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
		$setup = new WW_Utils_ResolveBrandSetup();
		$setup->resolveIssuePubChannelBrand( $this->issueObj->Id );
		$this->pubChannelObj = $setup->getPubChannel();
		$this->assertNotNull( $this->pubChannelObj,
				'Could not find a Publication Channel configured for brand "'.$this->pubObj->Name.'".', 
				'Please check your brand setup (or the TESTSUITE setting in configserver.php).' );
		
		// Determine the category object
		$this->category = count( $this->pubObj->Categories ) > 0  ? $this->pubObj->Categories[0] : null;
		$this->assertNotNull( $this->category,
				'Could not find a Category configured for brand "'.$this->pubObj->Name.'".', 
				'Please check your brand setup (or the TESTSUITE setting in configserver.php).' );
		
		// Determine the edition object
		require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
		$editions = DBEdition::listChannelEditionsObj( $this->pubChannelObj->Id );
		$this->editionObj = ( count($editions) > 0 ) ? $editions[0] : null;
		$this->assertNotNull( $this->editionObj,
				'Could not find an Edition configured for pub channel "'.$this->pubChannelObj->Name.'".', 
				'Please check your brand setup (or the TESTSUITE setting in configserver.php).' );
	}

	/**
	 * Create Relation object based on provided/existing object Ids.
	 *
	 * @return Object $relation Object Relation
	 */
	private function buildRelation()
	{
		$articleId = $this->articleObj->MetaData->BasicMetaData->ID;

		$relation = new Relation();
		$relation->Parent  = '';
		$relation->Child   = $articleId;
		$relation->Type    = 'Placed';
		$placement = $this->buildPlacement();
		$relation->Placements = array( $placement );

		return $relation;
	}

	/**
	 * Build Placement object
	 *
	 * @return Object $placement Object Placement
	 */
	private function buildPlacement()
	{
		$placement = new Placement();
		$placement->Page 		= 1;
		$placement->Element 	= 'body';
		$placement->ElementID 	= 'd9552aa4-7993-4932-8a1a-2fad1793ba0d';
		$placement->FrameOrder 	= 0;
		$placement->FrameID 	= '227';
		$placement->Left 		= 10;
		$placement->Top 		= 10;
		$placement->Width 		= 100;
		$placement->Height 		= 100;
		$placement->PageNumber 	= 1;
		$placement->PageSequence= 1;
		$placement->Tiles		= $this->buildPlacementTiles();

		return $placement;
	}

	/**
	 * Build PlacementTiles object
	 *
	 * @return array $this->placementTiles Array of placementtile objects
	 */
	private function buildPlacementTiles()
	{
		$tile1 = new PlacementTile();
		$tile1->PageSequence= 1;
		$tile1->Left		= 10;
		$tile1->Top			= 10;
		$tile1->Width		= rand( 50,60 );
		$tile1->Height		= rand( 90,100 );

		$tile2 = new PlacementTile();
		$tile2->PageSequence= 2;
		$tile2->Left		= 60;
		$tile2->Top			= 10;
		$tile2->Width		= rand( 50,60 );
		$tile2->Height		= rand( 90, 100 );

		$this->placementTiles = array( $tile1, $tile2 );

		return $this->placementTiles;
	}
	
	/**
	 * Validate the response on the placementtiles objects
	 * 
	 * @throws BizException on failure
	 * @param Object $placementTiles PlacementTiles
	 * @param string $service Service name
	 */
	private function validatePlacementTiles( array $placementTiles, $service )
	{
		$numberDefined = count($this->placementTiles);
		$numberStored = count($placementTiles);
		$this->assertEquals( $numberDefined, $numberStored,
				"Number of tiles do not match, $numberDefined defined, $numberStored stored.", 
				'Error occurred in ' . $service . ' response.' );
		
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array() );

		foreach( $placementTiles as /*$key => */$placementTile ) {
			$found = false;
			foreach ( $this->placementTiles as $definedPlacement ) {
				if ( $placementTile->PageSequence != $definedPlacement->PageSequence ) {
					continue;
				} else {
					$found = true;
					if ( !$phpCompare->compareTwoObjects( $definedPlacement, $placementTile ) ) {
						$this->throwError( implode( PHP_EOL, $phpCompare->getErrors() ) . 
											' Error occured in ' . $service . ' response.');
						return;
					}
				}
			}
			$this->assertTrue( $found,
				"Stored tile with page sequence {$placementTile->PageSequence} does not match any defined tile. ".
				'Error occurred in ' . $service . ' response.');
 		}
		LogHandler::Log( 'BuildTest', 'INFO', 'Completed validating ' . $service . ' response.' );
	}

	/**
	 * Validate the GetPagesInfo response on the PlacementInfos data objects.
	 * 
	 * @param WflGetPagesInfoResponse $response
	 */
	private function validateGetPagesInfo( $response )
	{
		// Check the basic response structure.
		$this->assertInstanceOf( 'PageObject', $response->EditionsPages[0]->PageObjects[0] );
		$this->assertCount( 1, $response->EditionsPages[0] );
		$this->assertCount( 2, $response->EditionsPages[0]->PageObjects );
		
		// Dive into the response structure.
		$layoutId = $this->layoutObj->MetaData->BasicMetaData->ID;
		foreach( $response->EditionsPages[0]->PageObjects as $pageIndex => $pageObject ) {
		
			// Check if all page data objects belong to our layout.
			$this->assertEquals( $pageObject->ParentLayoutId, $layoutId,
								'The PageObjects found in GetPageInfo response is not ours.' );
			
			// Check if the page data object has placement info at all.
			$placementInfos = $pageObject->PlacementInfos;
			$this->assertGreaterThan( 0, $placementInfos,
				'Wrong number of PlacementInfos found in GetPageInfo response.' );
			
			// Check if all properties of the placement info did round-trip.
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();	
			$phpCompare->initCompare( array( 'PlacementInfo->Id' => true ) );			
			$placementInfoFromTile = $this->composePlacementInfo( $this->placementTiles[$pageIndex] );
			if( !$phpCompare->compareTwoObjects( $placementInfoFromTile, $placementInfos[0] ) ) {
				$this->throwError( implode( PHP_EOL, $phpCompare->getErrors() ) .
									' Error occured in GetPagesInfo response.');
			}
		}
		LogHandler::Log( 'BuildTest', 'INFO', 'Completed validating GetPagesInfo response.' );
	}

	/**
	 * Composes a placement info data object (from a given placement tile).
	 *
	 * @param integer $id Child object id
	 * @param PlacementTile $tile
	 * @return PlacementInfo
	 */
	private function composePlacementInfo( $tile )
	{
		$pi = new PlacementInfo();
		$pi->Id 	= null;
		$pi->Height = $tile->Height;
		$pi->Left 	= $tile->Left;
		$pi->Top 	= $tile->Top;
		$pi->Width 	= $tile->Width;

		return $pi;
	}
}