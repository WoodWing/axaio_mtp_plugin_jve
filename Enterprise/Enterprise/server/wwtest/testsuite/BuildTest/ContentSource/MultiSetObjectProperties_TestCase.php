<?php
/**
 * @since v9.2.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_ContentSource_MultiSetObjectProperties_TestCase extends TestCase
{
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $type = 'string';

	// Objects used for testing
	const MAX_SHADOW_OBJECTS = 3;
	const CUSTOM_PROPERTY = 'C_MULTI_SET_PROPERTY';
	const SFS_FOLDER_NAME = 'Folder1';
	private $shadowObjects = null;
	private $sfsFolderName = null;

	public function getDisplayName() { return 'Set Properties For Multiple Shadow Objects ( MultisetObjectProperties )'; }
	public function getTestGoals()   { return 'Checks if shadow object properties can be successfully updated for multiple objects simultaneously.'; }
	public function getPrio()        { return 100; }
	public function getTestMethods() { return
		 'Call SetMultipleObjectProperties service and validate the responses.
		 <ol>
		 	<li>Create '.self::MAX_SHADOW_OBJECTS.' shadow objects named "imageN_SetProps ymd His". (CreateObjects)</li>
		 	<li>Change copyright properties of all shadow objects to Foo. (MultiSetObjectProperties)</li>
		 	<li>Retrieve the copyright properties from DB and check if the properties are really changed accordingly. (GetObjects)</li>

		 	<li>Change custom property of all shadow objects to test. (MultiSetObjectProperties)</li>
		 	<li>Retrieve the custom property from DB and check if the properties are really changed accordingly. (GetObjects)</li>

		 	<li>Change copyright properties of all shadow objects to Bar while trying also for a non-existing shadow object. (MultiSetObjectProperties)</li>
		 	<li>Validate against expected error: "Record not found / alien objects (S1029)"</li>

		 	<li>Delete the '.self::MAX_SHADOW_OBJECTS.' shadow objects. (DeleteObjects) and created custom property.</li>
		 </ol>';
	}
	
	final public function runTest()
	{
		do {
			// Use the ContentSource Utils.
			require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/ContentSource/ContentSourceUtils.class.php';
			$this->contentsourceUtils = new ContentSourceUtils();
			if( !$this->contentsourceUtils->initTest( $this ) ) {
				break;
			}

			// Retrieve the Ticket that has been determined by WflLogOn TestCase.
			$this->vars          = $this->getSessionVariables();
			$this->ticket        = @$this->vars['BuildTest_SFS']['ticket'];

			require_once BASEDIR.'/server/utils/TestSuite.php';
			$this->utils = new WW_Utils_TestSuite();

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
 	 * Creates one custom property and two shadow objects.
	 *
	 * Function uses SimpleFileSystem content source plugin to retrieve two alien objects and
	 * convert them into shadow objects.
	 *
	 * @param string $tipMsg To be used in the error message if there's any error.
	 * @return bool Whether or not the setup was successful.
	 */
	private function setupTestData( $tipMsg )
	{
		$retVal = true;
		do {
			if( !$this->prepareDataInSimpleFileSystem() ) {
				$this->setResult( 'ERROR', 'Failed to setup folder for SimpleFileSystem.Test cannot be continued.',
					'Please check in the SimpleFileSystem plugin config file and make sure the option '.
					'"SFS_LOCALCONTENTFOLDER" is defined.');
				$retVal = false;
				break;
			}

			// Create a custom property in the object table.
			$this->setupAdmPropertyInfo();

			// Retrieving alien objects from SimpleFileSystem content source plugin.
			$params = array();
			$param = new QueryParam();
			$param->Property             = 'Folder';
			$param->Operation            = '=';
			$param->Value                = self::SFS_FOLDER_NAME;
			$params[] = $param;
			$alienObjectIds = $this->contentsourceUtils->queryObjectsFromSimpleFileSystem( $params );
			if( !$alienObjectIds ) {
				$this->setResult( 'ERROR',  'Failed retrieving alien objects.', $tipMsg );
				$retVal = false;
				break;
			}

			if( !$this->createShadowObjects( $alienObjectIds ) )  {
				$this->setResult( 'ERROR',  'Failed creating shadow objects.', $tipMsg );
				$retVal = false;
				break;
			}
		} while( false );

		return $retVal;
	}

	/**
	 * Removes dossiers created at {@link: setupTestData()}.
	 *
	 * @param string $tipMsg To be used in the error message if there's any error.
	 */
	private function tearDownTestData( $tipMsg )
	{
		// Remove shadow objects.
		if( $this->shadowObjects ) foreach( $this->shadowObjects as $shadowObj ) {
			$errorReport = '';
			$id = $shadowObj->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down shadow object with object with id '. $id.'.';
			if( !$this->contentsourceUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not tear down shadow object with id '.$id.'.'.$errorReport, $tipMsg );
			}
		}
		$this->shadowObjects = array(); // clear cache

		$this->cleanupAdmPropertyInfo();

		$this->cleanUpSimpleFileSystemFolder();
	}

	/**
	 * Creates shadow objects given the list of alien object ids.
	 *
	 * @param string[] $alienObjectIds List of alien object ids.
	 * @return bool
	 */
	private function createShadowObjects( $alienObjectIds )
	{
		require_once BASEDIR .'/server/bizclasses/BizContentSource.class.php';
		require_once BASEDIR .'/server/bizclasses/BizObject.class.php';

		$retVal = true;
		$user = BizSession::checkTicket( $this->ticket );
		$shadowObjects = array();
		$counter = 0;

		// Compose postfix for shadow object names.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = '_SetProps '.date( 'ymd His', $microTime[1] ).' '.$miliSec;

		if( $alienObjectIds ) foreach( $alienObjectIds as $alienObjectId ) {
			if( BizContentSource::isAlienObject( $alienObjectId ) ) { // Double check.
				$alienObject = BizContentSource::getAlienObject( $alienObjectId, 'none', false /*lock*/ );

				$newObject = new Object();
				$newObject->MetaData = $alienObject->MetaData;
				$newObject->MetaData->BasicMetaData->Name = $newObject->MetaData->BasicMetaData->Name . $postfix;
				$shadowObject = BizContentSource::createShadowObject( $alienObjectId, $newObject );
				$autonaming = empty( $shadowObject->MetaData->BasicMetaData->Name );
				$shadowObjects[] = BizObject::createObject( $shadowObject, $user, false /*lock*/, $autonaming );
			}
			$counter++;
			if( $counter == self::MAX_SHADOW_OBJECTS ) {
				break; // Enough shadow objects created, bail out here.
			}
		}

		$this->shadowObjects = $shadowObjects;
		$totalShadowObjects = count( $this->shadowObjects );
		if( $totalShadowObjects < 3 ) {
			$this->setResult( 'ERROR', self::MAX_SHADOW_OBJECTS . ' shadow objects needed for testing but only ' .
				$totalShadowObjects . ' created.', 'Please check in the folder (defined in SimpleFileSystem plugin ' .
				'config.php), make sure there are at least '.self::MAX_SHADOW_OBJECTS.' images in that folder.');
			$retVal = false;
		}

		return $retVal;
	}

	/**
	 * Tests the MultiSetObjectProperties service.
	 */
	private function testMultiSetObjectProperties()
	{
		$retVal = true;
		do {
			// ---- Positive tests ----
			// Adjust copyright properties of shadow object.
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
			if( $this->shadowObjects ) foreach( $this->shadowObjects as $shadowObject ) {
				$expectedErrors[$shadowObject->MetaData->BasicMetaData->ID] = null; // no error
			}

			if( !$this->multiSetObjectProperties( $this->shadowObjects, $stepInfo, $expectedErrors, $updateProps, $changedPropPaths, $cmdPropValue->Value ) ) {
				$retVal = false;
				break;
			}

			// ... other positive tests

			// ---- Negative tests ----
			// Try to update properties of a non-existing shadow object.
			// Expected error: "Record not found (S1029)"
			$stepInfo = '#250: Attempt changing properties of non-existing object by calling MultiSetObjectProperties service.';
			$tmpShadowObject = unserialize( serialize( $this->shadowObjects[0] ) ); // deep clone
			$tmpShadowObject->MetaData->BasicMetaData->ID = PHP_INT_MAX - 1;
			$shadowObjects = array_merge( array($tmpShadowObject), $this->shadowObjects ); // start with bad shadowObject, followed by good ones

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

			if( $shadowObjects ) foreach( $shadowObjects as $shadowObject ) {
				$expectedErrors[$shadowObject->MetaData->BasicMetaData->ID] = null; // no error
			}
			$expectedErrors[PHP_INT_MAX - 1] = '(S1128)'; // Unable to set properties
			if( !$this->multiSetObjectProperties( $shadowObjects, $stepInfo, $expectedErrors, $updateProps, $changedPropPaths, $cmdPropValue->Value ) ) {
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
	 * @param array $expectedErrors S-codes when error expected. NULL when no error expected.
	 * @param MetaDataValue[] $updateProps List of metadata properties to update.
	 * @param string[] $changedPropPaths List of changed metadata properties, expected to be different.
	 * @param string $expectedCustomPropVal The expected custom property value.
	 * @return bool|null
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
	 * Creates folder and prepare data in the folder.
	 *
	 * Function creates a folder called 'Folder1' in the directory defined in SimpleFileSystem plugin config file.
	 * The directory is defined in option 'SFS_LOCALCONTENTFOLDER'.
	 * Once the directory is created, the sample images are copied over to the directory so that the build test can
	 * test it.
	 *
	 * @return bool
	 */
	private function prepareDataInSimpleFileSystem()
	{
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		require_once BASEDIR . '/config/plugins/SimpleFileSystem/config.php';
		$this->sfsFolderName = SFS_LOCALCONTENTFOLDER .  self::SFS_FOLDER_NAME;
		$folderUtils = new FolderUtils();

		// Some cleaning
		if( is_dir( $this->sfsFolderName ) && !$folderUtils->isEmptyDirectory( $this->sfsFolderName ) ) {
			// Just in case the folder was not cleaned from the previous test.
			$folderUtils->cleanDirRecursive( $this->sfsFolderName );
		}

		// Prepare data(images)
		$folderUtils->mkFullDir( $this->sfsFolderName );
		$sourceFolder = dirname(__FILE__) . '/testdata/SimpleFileSystem/';
		$folderUtils->copyDirectoryRecursively( $sourceFolder, $this->sfsFolderName );

		// Checks if the data for testing do exists.
		$retVal = true;
		$image1Exists = file_exists( $this->sfsFolderName . '/simpleFileSystem1.jpg' );
		$image2Exists = file_exists( $this->sfsFolderName . '/simpleFileSystem2.jpg' );
		$image3Exists = file_exists( $this->sfsFolderName . '/simpleFileSystem3.jpg' );
		if( !$image1Exists || !$image2Exists || !$image3Exists ) {
			$retVal = false;
		}
		return $retVal;
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
	 * @return bool Whether or not removing the custom property was successful.
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

	/**
	 * Clean the test data created in {@link:prepareDataInSimpleFileSystem()}
	 */
	private function cleanUpSimpleFileSystemFolder()
	{
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		$folderUtils = new FolderUtils();
		// Some cleaning
		if( $this->sfsFolderName ) {
			$folderUtils->cleanDirRecursive( $this->sfsFolderName );
			$this->sfsFolderName = null;
		}
	}
}