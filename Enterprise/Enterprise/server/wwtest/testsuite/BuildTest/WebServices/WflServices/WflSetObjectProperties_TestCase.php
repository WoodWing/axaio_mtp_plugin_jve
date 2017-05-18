<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflSetObjectProperties_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;
	private $vars = null;
	private $publication = null; // PublicationInfo
	private $category = null;
	private $dossierStatus = null;
	private $printTarget = null; // Target
	
	private $utils = null; // WW_Utils_TestSuite

	// Objects used for testing
	private $dossiers = array();
	const MAX_DOSSIERS = 1;
		
	public function getDisplayName() { return 'Set Object Properties'; }
	public function getTestGoals()   { return 'Checks if object properties can be successfully updated'; }
	public function getPrio()        { return 11; }
	public function getTestMethods() { return
		 'Call SetObjectProperties service and validate responses.
		 <ol>
		 	<li>Create '.self::MAX_DOSSIERS.' dossiers named "DossierN SetProps ymd His". (CreateObjects)</li>

		 	<li>#100 Change copyright properties of Dossier1 to WoodWing. (SetObjectProperties)</li>
		 	<li>Validate response and check if the properties are changed accordingly.</li>
		 	<li>#150 Change copyright properties of a non-existing dossier. (SetObjectProperties)</li>
		 	<li>Validate against expected error: "Record not found (S1029)"</li>

		 	<li>Delete the '.self::MAX_DOSSIERS.' dossiers. (DeleteObjects)</li>
		 </ol>'; 
	}
	
	final public function runTest()
	{
		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$this->vars = $this->getSessionVariables();
   		$this->ticket =       @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
   		$this->publication =  @$this->vars['BuildTest_WebServices_WflServices']['publication'];
   		$this->category =     @$this->vars['BuildTest_WebServices_WflServices']['category'];
   		$this->dossierStatus =@$this->vars['BuildTest_WebServices_WflServices']['dossierStatus'];
   		$this->printTarget =  @$this->vars['BuildTest_WebServices_WflServices']['printTarget'];

		if( !$this->ticket || !$this->publication || !$this->category || 
			!$this->dossierStatus || !$this->printTarget ) {
			$this->setResult( 'ERROR',  'Could not find data to test with.', 'Please enable the WflLogon test.' );
			return;
		}

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		
		do {
			$tipMsg = 'Setting up the test data.';
			if( !$this->setupTestData( $tipMsg ) ) {
				break;
			}
			if( !$this->testSetObjectProperties() ) {
				break;
			}
		} while( false );
		
		$tipMsg = 'Tearing down the test data.';
		$this->tearDownTestData( $tipMsg );
	}

	/**
 	 * Creates two dossiers.
	 *
	 * @param string $tipMsg To be used in the error message if there's any error.
	 * @return bool Whether or not the setup was successful.
	 */
	private function setupTestData( $tipMsg )
	{
		$retVal = true;
		
		// Compose postfix for dossier names.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = 'SetProps '.date( 'ymd His', $microTime[1] ).' '.$miliSec;
		
		// Create the dossiers.
		for( $i = 1; $i <= self::MAX_DOSSIERS; $i++ ) {
			$stepInfo = 'Create Dossier object #'.$i.'.';
			$dossier = $this->composeDossier( 'Dossier'.$i.' '.$postfix );
			if( $this->createObject( $dossier, $stepInfo ) ) {
				$this->dossiers[] = $dossier;
			} else {
				$this->setResult( 'ERROR',  'Could not create Dossier object '.$i.'.', $tipMsg );
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Removes dossiers created at {@link: setupTestData()}.
	 *
	 * @param string $tipMsg To be used in the error message if there's any error.
	 */
	private function tearDownTestData( $tipMsg )
	{
		// Remove the dossiers.
		$i = 1;
		if( $this->dossiers ) foreach( $this->dossiers as $dossier ) {
			$errorReport = null;
			$id = $dossier->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down dossier object #'.$i.'.';
			if( !$this->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not tear down dossier object #'.$i.'.'.$errorReport, $tipMsg );
			}
			$i++;
		}
		$this->dossiers = array(); // clear cache
	}	

	/**
	 * Tests the SetObjectProperties service.
	 */
	private function testSetObjectProperties()
	{
		$retVal = true;
		do {
			// ---- Positive tests ----
			// Adjust copyright properties of dossier1.
			$stepInfo = '#100: Changing the copyright info properties by calling SetObjectProperties service.';
			$this->dossiers[0]->MetaData->RightsMetaData->Copyright = 'WoodWing Software (c)';
			$this->dossiers[0]->MetaData->RightsMetaData->CopyrightMarked = true;
			$this->dossiers[0]->MetaData->RightsMetaData->CopyrightURL = 'http://www.woodwing.com';
			$changedPropPaths = array( 
				'RightsMetaData->Copyright' => 'WoodWing Software (c)',
				'RightsMetaData->CopyrightMarked' => true, 
				'RightsMetaData->CopyrightURL' => 'http://www.woodwing.com'
			);
			if( !$this->setObjectProperties( $this->dossiers[0], $stepInfo, null, $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// ... other positive tests

			// ---- Negative tests ----
			// Try to update properties of a non-existing dossier.
			// Expected error: "Record not found (S1029)"
			$stepInfo = '#150: Attempt changing properties of non-existing object by calling SetObjectProperties service.';
			$tmpDossier = unserialize( serialize( $this->dossiers[0] ) ); // deep clone
			$tmpDossier->MetaData->BasicMetaData->ID = PHP_INT_MAX - 1;
			if( !$this->setObjectProperties( $tmpDossier, $stepInfo, '(S1029)', array() ) ) {
				$retVal = false;
				break;
			}

			// ... other negative tests

		} while( false );
		return $retVal;
	}

	/**
	 * Updates an object with given metadata by calling the SetObjectProperties service.
	 *
	 * @param Object $object Object properties an targets to update. On success, it gets updated with latest info from DB.
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @param array $changedPropPaths List of changed metadata properties, expected to be different.
	 * @return bool
	 */
	private function setObjectProperties( Object $object, $stepInfo, $expectedError, array $changedPropPaths )
	{
		// Call the SetObjectProperties service.
		require_once BASEDIR . '/server/services/wfl/WflSetObjectPropertiesService.class.php';
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket	= $this->ticket;
		$request->ID        = $object->MetaData->BasicMetaData->ID;
		$request->MetaData  = $object->MetaData;
		$request->Targets   = $object->Targets;
		$response = $this->utils->callService( $this, $request, $stepInfo, $expectedError );
		$responseOk = ($response && !$expectedError) || (!$response && $expectedError);
		
		$compareOk = true;
		if( !is_null($response) ) {
		
			// Validate MetaData and Targets; Compare the original ones with the ones found in service response.
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();

			$phpCompare->initCompare( $changedPropPaths, array() );
			if( !$phpCompare->compareTwoProps( $object->MetaData, $response->MetaData ) ) {
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$this->setResult( 'ERROR', $errorMsg, 'Problem detected in MetaData of SetObjectProperties response.');
				$compareOk = false;
			}
			foreach( $changedPropPaths as $changedPropPath => $expPropValue ) {
				$retPropValue = null;
				eval( '$retPropValue = $response->MetaData->'.$changedPropPath.';' );
				if( $retPropValue != $expPropValue ) {
					$errorMsg = 'The returned MetaData->'.$changedPropPath.' is set to "'.
								$retPropValue.'" but should be set "'.$expPropValue.'".';
					$this->setResult( 'ERROR', $errorMsg, 'Problem detected in MetaData of SetObjectProperties response.');
					$compareOk = false;
				}
			}
			$phpCompare->initCompare( array(), array() );
			if( !$phpCompare->compareTwoProps( $object->Targets, $response->Targets ) ) {
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$this->setResult( 'ERROR', $errorMsg, 'Problem detected in Targets of SetObjectProperties response.');
				$compareOk = false;
			}
			
			// Update the orignal/cached object with response data.
			$object->MetaData = $response->MetaData;
			$object->Targets  = $response->Targets;
		}
		return $compareOk && $responseOk;
	}

	/**
	 * Composes a dossier object in memory.
	 *
	 * @param string $dossierName
	 * @return Object Dossier object.
	 */
	private function composeDossier( $dossierName )
	{
		// Transform PublicationInfo into Publication object.
		$publication = new Publication();
		$publication->Id = $this->publication->Id;
		$publication->Name = $this->publication->Name;

		// Transform CategoryInfo into Category object.
		$category = new Category();
		$category->Id = $this->category->Id;
		$category->Name = $this->category->Name;
		
		// Compose empty MetaData structure.
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->ExtraMetaData = array();
		
		// Fill in dossier properties.
		$metaData->BasicMetaData->Name = $dossierName;
		$metaData->BasicMetaData->Type = 'Dossier';
		$metaData->BasicMetaData->Publication = $publication;
		$metaData->BasicMetaData->Category = $category;
		$metaData->ContentMetaData->Description = 
			'Temporary dossier created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData->State = $this->dossierStatus;
		
		// Compose the dossier object.
		$dossier = new Object();
		$dossier->MetaData = $metaData;
		$dossier->Targets = array( $this->printTarget );
		
		return $dossier;
	}

	/**
	 * Creates an object in the database.
	 *
	 * @param Object $object The object to be created. On success, it gets updated with latest info from DB.
	 * @param string $stepInfo Extra logging info.
	 * @param bool $lock Whether or not the lock the object.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @return bool Whether or not service response was according to given expectations ($expectedError).
	 */
	private function createObject( Object &$object, $stepInfo, $lock = false, $expectedError = null )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket  = $this->ticket;
		$request->Lock    = $lock;
		$request->Objects = array( $object );

		$response = $this->utils->callService( $this, $request, $stepInfo, $expectedError );
		if( isset($response->Objects[0]) ) {
			$object = $response->Objects[0];
		}
		return ($response && !$expectedError) || (!$response && $expectedError);
	}

	/**
	 * Deletes a given object from the database by calling the DeleteObjects service.
	 *
	 * @param int $objId The id of the object to be removed.
	 * @param string $stepInfo Extra logging info.
	 * @param string &$errorReport To fill in the error message if there's any during the delete operation.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @param bool $permanent Whether or not to delete the object permanently.
	 * @param array $areas The areas to test against.
	 * @return bool Whether or not service response was according to given expectations ($expectedError).
	 */
	public function deleteObject( $objId, $stepInfo, &$errorReport, $expectedError = null, $permanent=true, $areas=array('Workflow'))
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket    = $this->ticket;
		$request->IDs       = array($objId);
		$request->Permanent = $permanent;
		$request->Areas     = $areas;
		
		$response = $this->utils->callService( $this, $request, $stepInfo, $expectedError );
		if( is_null( $response ) ) {
			return false;
		}
		
		$deleteSuccessful = true;
		if( $response->Reports && count( $response->Reports ) > 0 ) {
			foreach( $response->Reports as $report ) {
				$errorReport .= 'Failed deleted ObjectID:"' . $report->BelongsTo->ID . '" </br>';
				$errorReport .= 'Reason:';
				if( $report->Entries ) foreach( $report->Entries as $reportEntry ) {
					$errorReport .= $reportEntry->Message . '</br>';
				}
				$errorReport .= '</br>';
			}
			$deleteSuccessful = false;
		}
		return $deleteSuccessful;
	}
}