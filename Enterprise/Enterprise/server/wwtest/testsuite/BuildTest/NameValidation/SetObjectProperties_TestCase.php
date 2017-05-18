<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_NameValidation_SetObjectProperties_TestCase extends TestCase
{
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $articles = null; // Test objects

	public function getDisplayName() { return 'Set Properties for one or more Objects'; }
	public function getTestGoals()   { return 'Checks if NameValidationDemo connector is correctly called'; }
	public function getPrio()        { return 100; }
	public function getTestMethods() { return
		 'Call SetMultipleObjectProperties and SetObjectProperties services and validate the responses.
		 <ol>
		 	<li>Create test objects. Validate modified metadata by connector upon creation.</li>
		 	<li>Change copyright properties of all objects to Foo. (MultiSetObjectProperties and SetObjectProperties).
		 		The copyright demo connector will overrwrite this to Ham.</li>
		 	<li>Retrieve the copyright properties from DB and check if the properties are really changed accordingly to Ham. (GetObjects)</li>

		 	<li>Change copyright properties of all objects to Spam. (MultiSetObjectProperties and SetObjectProperties).</li>
		 	<li>The copyright demo connector will prevent this operation and set an error (S1128).</li>
		 	<li>Teardown test objects.</li>
		 </ol>';
	}
	
	final public function runTest()
	{

		do {
			// Retrieve the Ticket that has been determined by WflLogOn TestCase.
			$this->vars          = $this->getSessionVariables();
			$this->ticket        = @$this->vars['BuildTest_NV']['ticket'];

			require_once BASEDIR.'/server/utils/TestSuite.php';
			$this->utils = new WW_Utils_TestSuite();

			if( !$this->setupTestData() ) {
				break;
			}

			// Test setting standard properties on existing objects.
			if( !$this->testSetObjectProperties() ) {
				break;
			}
		} while( false );

		$this->tearDownTestData();
	}

	/*
	 * Creates objects for testing.
	 * @return bool Whether the setup is successful.
	 */
	private function setupTestData()
	{
		$retVal = true;
		do {
			// Create a test article object
			$createObjects = array();
			for( $i = 0; $i < 3; $i++ ) {
				$articleName = 'Article_' .date("m d H i s");
				$articleObj = $this->buildArticleObject( null, $articleName );
				$createObjects[] = $articleObj;
			}
			if( !$this->utils->uploadObjectsToTransferServer( $this, $createObjects ) ) {
				$retVal = false;
				break;
			}
			$response = $this->utils->callCreateObjectService( $this, $this->vars['BuildTest_NV']['ticket'], $createObjects );
			if( is_null($response) ) {
				$retVal = false;
				break;
			}

			// validateMetaDataAndTargets is called upon creating the object
			// The demo plugin changes the copyright to "Ham Software (c)"
			foreach( $response->Objects as $object ) {
				if( $object->MetaData->RightsMetaData->Copyright != 'Ham Software (c)' ) {
					$this->setResult( 'ERROR', 'Unexpected copyright string "' . $object->MetaData->RightsMetaData->Copyright . '"',
						'Make sure the NameValidationDemo plugin is correctly installed and called.' );
					$retVal = false;
					break;
				}
			}

			$this->articles = $response->Objects;
		} while ( false );

		return $retVal;
	}


	/*
	 * Removes objects used for testing.
	 */
	private function tearDownTestData()
	{
		$ticket = $this->vars['BuildTest_NV']['ticket'];

		// Delete Objects from Enterprise
		if( $this->articles ) {
			foreach( $this->articles as $object ) {
				$errorReport = null;
				$id = $object->MetaData->BasicMetaData->ID;
				if( !$this->utils->deleteObject( $this, $ticket, $object->MetaData->BasicMetaData->ID, 'Delete article object', $errorReport ) ) {
					$this->setResult( 'ERROR',  'Could not tear down object with id '.$id.'.'.$errorReport );
				}
			}
			$this->articles = null;
		}
	}

	/**
	 * Tests if name validation connectors are called by the (Multi)SetObjectProperties service.
	 */
	private function testSetObjectProperties()
	{
		$retVal = true;
		do {
			$ticket = $this->vars['BuildTest_NV']['ticket'];

			// ---- Positive tests ----
			// Adjust copyright properties of shadow object.
			// Request to change to "Ham Software (c)", but the name validation will adjust it to "Foo Software (c)".
			$stepInfo = '#200 Changing the copyright info properties by calling MultiSetObjectProperties service.';

			$copyrightHolder = 'Foo Software (c)';

			$updateProps = array();

			$mdValue = new MetaDataValue();
			$mdValue->Property = 'Copyright';
			$propValue = new PropertyValue();
			$propValue->Value = $copyrightHolder;
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			$changedPropPaths = array(
				'MetaData->RightsMetaData->Copyright' => 'Ham Software (c)',
			);

			$articles = $this->articles;

			$expectedErrors = array();
			if( $articles ) foreach( $articles as $articleObject ) {
				$expectedErrors[$articleObject->MetaData->BasicMetaData->ID] = null; // no error
			}

			// Test for multi-set object properties
			if( !$this->multiSetObjectProperties( $articles, $stepInfo, $expectedErrors, $updateProps, $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Test for single set object properties
			$changedPropPaths = array(
				'RightsMetaData->Copyright' => 'Ham Software (c)',
			);
			$firstArticle = reset( $articles );
			$firstArticle->MetaData->RightsMetaData->Copyright = $copyrightHolder;
			if( !$this->utils->setObjectProperties( $this, $ticket, $firstArticle, 'Changing Article Object Copyright', null, $changedPropPaths ) ) {
				return false;
			}

			// ---- Negative tests ----
			// Adjust copyright properties of shadow object.
			// Name validator demo does not like "Spam Software (c)", so will refuse.
			$stepInfo = '#200 Changing the copyright info properties by calling MultiSetObjectProperties service.';

			$copyrightHolder = 'Spam Software (c)';

			$updateProps = array();

			$mdValue = new MetaDataValue();
			$mdValue->Property = 'Copyright';
			$propValue = new PropertyValue();
			$propValue->Value = $copyrightHolder;
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			$changedPropPaths = array(
				'MetaData->RightsMetaData->Copyright' => $copyrightHolder,
			);

			$expectedErrors = array();
			if( $articles ) foreach( $articles as $articleObject ) {
				$expectedErrors[$articleObject->MetaData->BasicMetaData->ID] = '(S1128)'; // no error
			}

			// Test for multi-set object properties
			if( !$this->multiSetObjectProperties( $articles, $stepInfo, $expectedErrors, $updateProps, $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Test for single set object properties
			$changedPropPaths = array(
				'RightsMetaData->Copyright' => $copyrightHolder,
			);
			$firstArticle = reset( $articles );
			$firstArticle->MetaData->RightsMetaData->Copyright = $copyrightHolder;
			if( !$this->utils->setObjectProperties( $this, $ticket, $firstArticle, 'Changing Article Object Copyright', '(S1128)', $changedPropPaths ) ) {
				return false;
			}

		} while( false );
		return $retVal;
	}

	/**
	 * Updates an object with given metadata by calling the MultiSetObjectProperties service.
	 *
	 * @param Object[] $objects Object properties to update. On success, they are updated with the latest info from the DB.
	 * @param string $stepInfo Extra logging info.
	 * @param array|null $expectedErrors S-codes per Object when an error is expected, NULL when it isn't.
	 * @param MetaDataValue[] $updateProps List of metadata properties to update.
	 * @param string[] $changedPropPaths List of changed metadata properties, expected to be different.
	 * @return bool|null Whether the test was succesful, null when a service request fails.
	 */
	private function multiSetObjectProperties( 
		$objects, $stepInfo, array $expectedErrors, 
		array $updateProps, array $changedPropPaths )
	{
		// Collect object ids.
		$objectIds = array();
		foreach( $objects as $object ) {
			$objectIds[] = $object->MetaData->BasicMetaData->ID;
		}

		// Suppress errors that are expected.
		$serverityMap = array();
		foreach( $objectIds as $objectId ) {
			$expectedError = $expectedErrors[$objectId];
			if( !is_null($expectedError) ) {
				$expectedError = trim( $expectedError,'()' ); // remove () brackets
				$serverityMap[$expectedError] = 'INFO';
			}
		}
		$severityMapHandle = new BizExceptionSeverityMap( $serverityMap );

		// Call the SetObjectProperties service.
		require_once BASEDIR . '/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket	= $this->ticket;
		$request->IDs       = $objectIds;
		$request->MetaData  = $updateProps;
		$response = $this->utils->callService( $this, $request, $stepInfo, null );
		if( !$response ) {
			return null;
		}
		unset($severityMapHandle); // until here the errors are expected, so end it
		
		// Check if expected errors can be found in the returned error reports.
		$compareOk = true;
		foreach( $objectIds as $objectId ) {
			$expectedError = $expectedErrors[$objectId];
			if( !is_null($expectedError) ) {
				$foundExpected = false;
				foreach( $response->Reports as $report ) {
					$belongsTo = $report->BelongsTo;
					if( $belongsTo->Type == 'Object' && $belongsTo->ID == $objectId ) {
						foreach( $report->Entries as $entry ) {
							if( '('.$entry->ErrorCode.')' == $expectedError ) {
								$foundExpected = true;
								break 2; // quit both foreach loops at once
							}
						}
					}
				}
				if( !$foundExpected ) {
					$errorMsg = 'Expected to raise error "'.$expectedError.'" for '.
								'object id "'.$objectId.'" but it was not found in the error reports.';
					$errorContext = 'Problem detected in Reports of MultiSetObjectProperties.';
					$this->setResult( 'ERROR', $errorMsg, $errorContext );
					$compareOk = false;
				}
			}
		}
		
		// Don't get objects for which an error was expected.
		$getObjIds = array();
		foreach( $objectIds as $objectId ) {
			if( is_null( $expectedErrors[$objectId] ) ) {
				$getObjIds[] = $objectId;
			}
		}

		if ( !$getObjIds ) { // Bail out. Nothing can be requested.
			return null;
		}
		
		// Call GetObjects to retrieve all changed properties from database.
		require_once BASEDIR .'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $getObjIds;
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'MetaData', 'Targets' );
		$response = $this->utils->callService( $this, $request, $stepInfo, null );
		if( !$response ) {
			return null;
		}
		
		foreach( $response->Objects as $respObject ) {
			
			// Lookup the original/cached object for the object returned through web service response.
			$orgObject = null;
			foreach( $objects as $orgObject ) {
				if( $orgObject->MetaData->BasicMetaData->ID == $respObject->MetaData->BasicMetaData->ID ) {
					break; // found
				}
			}
			
			// Simulate the property updates in memory on the orignal/cached object.
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$flatMD = new stdClass();
			$flatMD->MetaDataValue = $updateProps;
			BizProperty::updateMetaDataTreeWithFlat( $orgObject->MetaData, $flatMD );
			
			// Validate MetaData and Targets; Compare the original ones with the ones found in service response.
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			
			$phpCompare->initCompare( $changedPropPaths, array() );

			// Validate ExtraMetaData.
			if( !$phpCompare->compareTwoProps( $orgObject->MetaData, $respObject->MetaData ) ) {
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorContext = 'Problem detected in MetaData of GetObjects response after calling MultiSetObjectProperties.';
				$this->setResult( 'ERROR', $errorMsg, $errorContext );
				$compareOk = false;
			}
			foreach( $changedPropPaths as $changedPropPath => $expPropValue ) {
				$retPropValue = null;
				eval( '$retPropValue = $respObject->'.$changedPropPath.';' );
				if( $retPropValue != $expPropValue ) {
					$errorMsg = 'The returned '.$changedPropPath.' is set to "'.
								$retPropValue.'" but should be set "'.$expPropValue.'".';
					$errorContext = 'Problem detected in MetaData of GetObjects response after calling MultiSetObjectProperties.';
					$this->setResult( 'ERROR', $errorMsg, $errorContext );
					$compareOk = false;
				}
			}
			
			// Update the original/cached object with response data.
			$orgObject->MetaData = $respObject->MetaData;
		}
		return $compareOk;
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
		$pubInfo = $this->vars['BuildTest_NV']['Brand'];
		$categoryInfo = $this->vars['BuildTest_NV']['Category'];

		$publ = new Publication( $pubInfo->Id, $pubInfo->Name );
		$category = new Category( $categoryInfo->Id, $categoryInfo->Name );

		$articleStatusInfo = $this->utils->getFirstStatusInfoForType( $this, $pubInfo, 'Article' );
		if( is_null($articleStatusInfo) ) {
			return null;
		}

		// retrieve user (shortname) of the logOn test user.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->vars['BuildTest_NV']['ticket'] );

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
		$wflMD->Comment = 'Creating Object for NameValidation BuildTest';
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