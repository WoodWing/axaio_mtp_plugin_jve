<?php
/**
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflMultiSetObjectProperties_TestCase extends TestCase
{
	private $ticket = null;
	private $vars = null;
	private $publication = null;
	private $category = null;
	private $dossierStatus = null;
	private $printTarget = null; // Target
	private $utils = null; // WW_Utils_TestSuite
	private $type = 'string';

	// Objects used for testing
	private $dossiers = array();
	const MAX_DOSSIERS = 3;
	const CUSTOM_PROPERTY = 'C_MULTI_SET_PROPERTY';
		
	public function getDisplayName() { return 'Set Properties For Multiple Objects'; }
	public function getTestGoals()   { return 'Checks if object properties can be successfully updated for multiple objects simultaneously.'; }
	public function getPrio()        { return 12; }
	public function getTestMethods() { return
		 'Call SetMultipleObjectProperties service and validate the responses.
		 <ol>
		 	<li>Create '.self::MAX_DOSSIERS.' dossiers named "DossierN SetProps ymd His". (CreateObjects)</li>

		 	<li>Change copyright properties of all dossiers to Foo. (MultiSetObjectProperties)</li>
		 	<li>Retrieve the copyright properties from DB and check if the properties are really changed accordingly. (GetObjects)</li>

		 	<li>Change custom property of all dossiers to test. (MultiSetObjectProperties)</li>
		 	<li>Retrieve the custom property from DB and check if the properties are really changed accordingly. (GetObjects)</li>

		 	<li>Change copyright properties of all dossiers to Bar while trying also for a non-existing dossier. (MultiSetObjectProperties)</li>
		 	<li>Validate against expected error: " Unable to set properties / alien objects (S1128)"</li>

		 	<li>Delete the '.self::MAX_DOSSIERS.' dossiers. (DeleteObjects) and created custom property.</li>
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
			// Test setting standard properties on existing objects.
			if( !$this->testMultiSetObjectProperties() ) {
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

		// Create a custom property in the object table.
		$this->setupAdmPropertyInfo();

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

		$this->cleanupAdmPropertyInfo();
	}	

	/**
	 * Tests the MultiSetObjectProperties service.
	 */
	private function testMultiSetObjectProperties()
	{
		$retVal = true;
		do {
			// ---- Positive tests ----
			// Adjust copyright properties of dossier1.
			$stepInfo = '#200 Changing the copyright info properties by calling MultiSetObjectProperties service.';

			$updateProps = array();
			// Copyright
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'Copyright';
			$propValue = new PropertyValue();
			$propValue->Value = 'Foo Software (c)';
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			// CopyriCopyrightMarkedght
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'CopyrightMarked';
			$propValue = new PropertyValue();
			$propValue->Value = true;
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			// CopyrightURL
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'CopyrightURL';
			$propValue = new PropertyValue();
			$propValue->Value = 'http://www.foo.com';
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			// Custom property.
			$cmdValue = new MetaDataValue();
			$cmdValue->Property = self::CUSTOM_PROPERTY;
			$cmdPropValue = new PropertyValue();
			$cmdPropValue->Value = 'test';
			$cmdValue->PropertyValues = array( $cmdPropValue );
			$updateProps[] = $cmdValue;

			$changedPropPaths = array(
				'MetaData->RightsMetaData->Copyright' => 'Foo Software (c)',
				'MetaData->RightsMetaData->CopyrightMarked' => true,
				'MetaData->RightsMetaData->CopyrightURL' => 'http://www.foo.com',
			);

			$expectedErrors = array();
			if( $this->dossiers ) foreach( $this->dossiers as $dossier ) {
				$expectedErrors[$dossier->MetaData->BasicMetaData->ID] = null; // no error
			}

			if( !$this->multiSetObjectProperties( $this->dossiers, $stepInfo, $expectedErrors, $updateProps, $changedPropPaths, $cmdPropValue->Value ) ) {
				$retVal = false;
				break;
			}

			// ... other positive tests


			// ---- Negative tests ----
			// Try to update properties of a non-existing dossier.
			// Expected error: "Unable to set properties (S1128)"
			$stepInfo = '#250: Attempt changing properties of non-existing object by calling MultiSetObjectProperties service.';
			$tmpDossier = unserialize( serialize( $this->dossiers[0] ) ); // deep clone
			$tmpDossier->MetaData->BasicMetaData->ID = PHP_INT_MAX - 1;
			$dossiers = array_merge( array($tmpDossier), $this->dossiers ); // start with bad dossier, followed by good ones

			$updateProps = array();
			// Copyright
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'Copyright';
			$propValue = new PropertyValue();
			$propValue->Value = 'Bar Software (c)';
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			// CopyriCopyrightMarkedght
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'CopyrightMarked';
			$propValue = new PropertyValue();
			$propValue->Value = true;
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			// CopyrightURL
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'CopyrightURL';
			$propValue = new PropertyValue();
			$propValue->Value = 'http://www.bar.com';
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			// Custom property.
			$cmdValue = new MetaDataValue();
			$cmdValue->Property = self::CUSTOM_PROPERTY;
			$cmdPropValue = new PropertyValue();
			$cmdPropValue->Value = 'Changed';
			$cmdValue->PropertyValues = array( $cmdPropValue );
			$updateProps[] = $cmdValue;

			$changedPropPaths = array(
				'MetaData->RightsMetaData->Copyright' => 'Bar Software (c)',
				'MetaData->RightsMetaData->CopyrightMarked' => true,
				'MetaData->RightsMetaData->CopyrightURL' => 'http://www.bar.com',
			);

			if( $dossiers ) foreach( $dossiers as $dossier ) {
				$expectedErrors[$dossier->MetaData->BasicMetaData->ID] = null; // no error
			}
			$expectedErrors[PHP_INT_MAX - 1] = '(S1128)'; // Unable to set properties
			if( !$this->multiSetObjectProperties( $dossiers, $stepInfo, $expectedErrors, $updateProps, $changedPropPaths, $cmdPropValue->Value ) ) {
				$retVal = false;
				break;
			}
			
			// ... other negative tests

		} while( false );
		return $retVal;
	}

	/**
	 * Updates an object with given metadata by calling the MultiSetObjectProperties service.
	 *
	 * @param Object[] $objects Objects properties to update. On success, they get updated with latest info from DB.
	 * @param string $stepInfo Extra logging info.
	 * @param string[]|null $expectedErrors S-code when error expected. NULL when no error expected.
	 * @param MetaDataValue[] $updateProps List of metadata properties to update.
	 * @param string[] $changedPropPaths List of changed metadata properties, expected to be different.
	 * @param string $expectedCustomPropVal The expected custom property value.
	 * @return bool
	 */
	private function multiSetObjectProperties( 
		$objects, $stepInfo, array $expectedErrors, 
		array $updateProps, array $changedPropPaths,
		$expectedCustomPropVal )
	{
		// Collect object ids.
		$objectIds = array();
		foreach( $objects as $object ) {
			$objectIds[] = $object->MetaData->BasicMetaData->ID;
		}

		// Suppress errors that are expected.
		$severityMap = array();
		foreach( $objectIds as $objectId ) {
			$expectedError = $expectedErrors[$objectId];
			if( !is_null($expectedError) ) {
				$expectedError = trim( $expectedError,'()' ); // remove () brackets
				$severityMap[$expectedError] = 'INFO';
			}
		}
		$severityMapHandle = new BizExceptionSeverityMap( $severityMap );

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
			foreach ($orgObject->MetaData->ExtraMetaData as $extra ) {
				if ($extra->Property == self::CUSTOM_PROPERTY ) {
					$extra->Values[0] = $expectedCustomPropVal;
					break;
				}
			}


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
			
			// Update the orignal/cached object with response data.
			$orgObject->MetaData = $respObject->MetaData;
		}
		return $compareOk;
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
	private function createObject( /** @noinspection PhpLanguageLevelInspection */ Object &$object,
		$stepInfo, $lock = false, $expectedError = null )
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

	/**
	 * Creates a custom property.
	 *
	 * @return bool Whether or not the custom property was created.
	 */
	private function setupAdmPropertyInfo()
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR . '/server/dbclasses/DBAdmProperty.class.php';
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		$table = 'objects';

		// Create a custom property.
		$admPropertyInfo = new AdmPropertyInfo();
		$admPropertyInfo->PublicationId = null;
		$admPropertyInfo->ObjectType = null;
		$admPropertyInfo->Name = self::CUSTOM_PROPERTY;
		$admPropertyInfo->DisplayName = self::CUSTOM_PROPERTY;
		$admPropertyInfo->Category = null;
		$admPropertyInfo->Type = $this->type;
		$admPropertyInfo->DefaultValue = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2);
		$admPropertyInfo->ValueList = null;
		$admPropertyInfo->MinValue = null;
		$admPropertyInfo->MaxValue = null;
		$admPropertyInfo->MaxLength = null;
		$admPropertyInfo->DBUpdated = true;
		$admPropertyInfo->DependentProperties = null; // future
		$admPropertyInfo->PluginName = null;
		$admPropertyInfo->Entity = 'Object';

		// Create the Property.
		$this->admPropertyInfo = BizAdmProperty::insertAdmPropertyInfo( $admPropertyInfo );
		if( is_null($this->admPropertyInfo) ) {
			$this->setResult( 'ERROR',  'Could not create an AdmPropertyInfo object.');
			return false;
		}

		// Add dialog option / Remove dialog option.




		// Update the model.
		try {
			BizCustomField::insertFieldAtModel( $table, self::CUSTOM_PROPERTY, 'string' );
			$objectFields = BizCustomField::getFieldsAtModel( $table );
			if( !isset($objectFields[self::CUSTOM_PROPERTY]) ) {
				$this->setResult( 'ERROR',  'Field does not exist in table: ' . $table . ' for type: ' . $this->type );
				return false;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR',  'Adding field to ' . $table . ' table error for type: ' . $this->type );
			return false;
		}
		return true;
	}

	/**
	 * Removes the created custom property.
	 *
	 * @return bool Whether or not removing the custom property was succesful.
	 */
	private function cleanupAdmPropertyInfo()
	{
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		$retVal = true;
		$table = 'objects';

		if( $this->admPropertyInfo ) {
			require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
			$result = BizAdmProperty::deleteAdmPropertyInfo( $this->admPropertyInfo );
			if( !$result ) {
				$this->setResult( 'ERROR', 'Could not remove the AdmPropertyInfo while testing for type: ' . $this->type );
				$retVal = false;
			}
			$this->admPropertyInfo = null;
		}

		// Delete the custom field from the Objects table.
		try {
			BizCustomField::deleteFieldAtModel( $table, self::CUSTOM_PROPERTY );
		} catch( BizException $e ) {
			LogHandler::Log( 'CustPropTest', 'ERROR', 'Deleting field from "'.$table.'" '.
				'table error, while testing for type: '.$this->type );
			$retVal = false;
		}

		// Attempt retrieval of the field.
		$objectFields = BizCustomField::getFieldsAtModel( $table );
		if( isset($objectFields[self::CUSTOM_PROPERTY]) ) {
			LogHandler::Log( 'CustPropTest', 'ERROR', 'Field still exists in table "'.$table.'" '.
				'for type: '.$this->type );
			$retVal = false;
		}
		return $retVal;
	}
}