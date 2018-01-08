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
	private $customPropertyType = 'string';
	private $admPropertyInfos = array();
	private $stringCustomProperty = null;
	private $customPropertiesCreated = array();
	const CUSTOM_PROP_STRING = 'C_SETPROPERTY_CUSTOM_STRING';
	const CUSTOM_PROP_MULTI_BYTE_STRING = 'C_SETPROPERTY_CUSTOM_MBSTRING'; // MultiByteString
	
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
		 	<li>#101 Change a string custom proeprty of Dossier1 to a non-multi-byte string. (SetObjectProperties)</li>
		 	<li>Validate response and check if the properties are changed accordingly.</li>
		 	<li>#102 Change a string custom proeprty of Dossier1 to a multi-byte string. (SetObjectProperties)</li>
		 	<li>Validate response and check if the properties are changed accordingly.</li>
		 	<li>#103 Change Name property of Dossier1 to a very long multi-byte string name that is not allowed in Enterprise. (SetObjectProperties)</li>
		 	<li>Validate response and check if the name property is correctly truncated to 63 characters.</li>
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
		do {
			// Create custom properties in the object table.
			if( !$this->setupAdmPropertyInfo() ) {
				$retVal = false;
				break;
			}

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
					break 2; // quit from the for-loop and the do-while-loop
				}
			}
		} while ( false );

		return $retVal;
	}

	/**
	 * Creates custom properties.
	 *
	 * @return bool Whether or not the custom properties were successfully created.
	 */
	private function setupAdmPropertyInfo()
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmProperty.class.php';
		$table = 'objects';

		// 1. Custom Property: SELF::CUSTOM_PROP_STRING
		$admPropertyInfo = new AdmPropertyInfo();
		$admPropertyInfo->PublicationId = null;
		$admPropertyInfo->ObjectType = null;
		$admPropertyInfo->Name = self::CUSTOM_PROP_STRING;
		$admPropertyInfo->DisplayName = self::CUSTOM_PROP_STRING;
		$admPropertyInfo->Category = null;
		$admPropertyInfo->Type = $this->customPropertyType;
		$admPropertyInfo->DefaultValue = null;
		$admPropertyInfo->ValueList = null;
		$admPropertyInfo->MinValue = null;
		$admPropertyInfo->MaxValue = null;
		$admPropertyInfo->MaxLength = null;
		$admPropertyInfo->DBUpdated = true;
		$admPropertyInfo->DependentProperties = null; // future
		$admPropertyInfo->PluginName = null;
		$admPropertyInfo->Entity = 'Object';

		// Create the Property.
		$createdAdmPropertyInfo = $this->createAdmProperty( $admPropertyInfo, $table );
		if( !$createdAdmPropertyInfo ) {
			return false;
		} else {
			$this->customPropertiesCreated[] = self::CUSTOM_PROP_STRING; // For deletion during tear down test data.
			$this->admPropertyInfos[] = $createdAdmPropertyInfo;
		}

		// 2. Custom Property: SELF::CUSTOM_PROP_MULTI_BYTE_STRING
		$admPropertyInfo = new AdmPropertyInfo();
		$admPropertyInfo->PublicationId = null;
		$admPropertyInfo->ObjectType = null;
		$admPropertyInfo->Name = self::CUSTOM_PROP_MULTI_BYTE_STRING;
		$admPropertyInfo->DisplayName = self::CUSTOM_PROP_MULTI_BYTE_STRING;
		$admPropertyInfo->Category = null;
		$admPropertyInfo->Type = $this->customPropertyType;
		$admPropertyInfo->DefaultValue = null;
		$admPropertyInfo->ValueList = null;
		$admPropertyInfo->MinValue = null;
		$admPropertyInfo->MaxValue = null;
		$admPropertyInfo->MaxLength = null;
		$admPropertyInfo->DBUpdated = true;
		$admPropertyInfo->DependentProperties = null; // future
		$admPropertyInfo->PluginName = null;
		$admPropertyInfo->Entity = 'Object';

		// Create the Property.
		$createdAdmPropertyInfo = $this->createAdmProperty( $admPropertyInfo, $table );
		if( !$createdAdmPropertyInfo ) {
			return false;
		} else {
			$this->customPropertiesCreated[] = self::CUSTOM_PROP_MULTI_BYTE_STRING; // For deletion during tear down test data.
			$this->admPropertyInfos[] = $createdAdmPropertyInfo;
		}
		return true;
	}

	/**
	 * Creates a custom property in the database given the AdmPropertyInfo.
	 *
	 * @param AdmPropertyInfo $admPropertyInfo
	 * @param string $table
	 * @return AdmPropertyInfo|bool
	 */
	private function createAdmProperty( $admPropertyInfo, $table )
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';

		$propertyName = $admPropertyInfo->Name;
		$createdAdmPropertyInfo = BizAdmProperty::insertAdmPropertyInfo( $admPropertyInfo );
		if( is_null( $createdAdmPropertyInfo ) ) {
			$this->setResult( 'ERROR',  "Could not create an AdmPropertyInfo object for {$propertyName}" );
			return false;
		}

		// Update the model.
		try {
			BizCustomField::insertFieldAtModel( $table, $propertyName, 'string' );
			$objectFields = BizCustomField::getFieldsAtModel( $table );
			if( !isset($objectFields[$propertyName]) ) {
				$this->setResult( 'ERROR',  'Field does not exist in table: ' . $table . ' for propertyName: "' .
											$propertyName.'", type: "' . $this->customPropertyType . '"');
				return false;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR',  'Adding field to ' . $table . ' table error for propertyName: "' .
											$propertyName.'", type: "' . $this->customPropertyType . '"');
			return false;
		}

		return $createdAdmPropertyInfo;
	}

	/**
	 * Removes the created custom property.
	 *
	 * @return bool Whether or not removing the custom property was successful.
	 */
	private function cleanupAdmPropertyInfo()
	{
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		$retVal = true;
		$table = 'objects';

		if( $this->admPropertyInfos ) foreach( $this->admPropertyInfos as $index => $admPropertyInfo ) {
			$result = BizAdmProperty::deleteAdmPropertyInfo( $admPropertyInfo );
			if( !$result ) {
				$this->setResult( 'ERROR', 'Could not remove the AdmPropertyInfo while testing for '.
					'property: "'.$admPropertyInfo->Name .', "type: "' . $this->customPropertyType . '"');
				$retVal = false;
			}
			unset( $this->admPropertyInfos[$index] ); // clear cache
		}

		if( $this->customPropertiesCreated ) foreach( $this->customPropertiesCreated as $customProp ) {
			// Delete the custom field from the Objects table.
			try {
				BizCustomField::deleteFieldAtModel( $table, $customProp );
			} catch( BizException $e ) {
				LogHandler::Log( 'CustPropTest', 'ERROR', 'Deleting field from "'.$table.'" '.
					'table error, while testing for field: "'.$customProp.'", type: "'.$this->customPropertyType .'"');
				$retVal = false;
			}

			// Attempt retrieval of the field.
			$objectFields = BizCustomField::getFieldsAtModel( $table );
			if( isset($objectFields[$customProp]) ) {
				LogHandler::Log( 'CustPropTest', 'ERROR', 'Field still exists in table "'.$table.'" '.
					'for field: "'.$customProp.'" type: "'.$this->customPropertyType . '"');
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Removes dossiers created at {@link: setupTestData()}.
	 *
	 * @param string $tipMsg To be used in the error message if there's any error.
	 * @return bool Whether or not the deletions were successful.
	 */
	private function tearDownTestData( $tipMsg )
	{
		$retVal = true;
		// Remove the dossiers.
		$i = 1;
		if( $this->dossiers ) foreach( $this->dossiers as $dossier ) {
			$errorReport = null;
			$id = $dossier->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down dossier object #'.$i.'.';
			if( !$this->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not tear down dossier object #'.$i.'.'.$errorReport, $tipMsg );
				$retVal = false;
			}
			$i++;
		}
		$this->dossiers = array(); // clear cache

		if( !$this->cleanupAdmPropertyInfo() ) {
			$retVal = false;
		}

		return $retVal;
	}	

	/**
	 * Tests the SetObjectProperties service.
	 */
	private function testSetObjectProperties()
	{
		$retVal = true;
		do {
			// ---- Positive tests ----
			if( !$this->positiveTestAdjustCopyrightProperties( 100 ) ) {
				$retVal = false;
				break;
			}

			if( !$this->positiveTestNonTextBlobProperty( 101 )) {
				$retVal = false;
				break;
			}

			if( !$this->positiveTestStringCustomProperty( 102 ) ) {
				$retVal = false;
				break;
			}

			if( !$this->positiveTestMultyByteStringCustomProperty( 103 )) {
				$retVal = false;
				break;
			}

			if( !$this->positiveTestAdjustNameFieldToLongMultiByteName( 104 )) {
				$retVal = false;
				break;
			}

			// ... other positive tests

			// ---- Negative tests ----
			// Try to update properties of a non-existing dossier.
			// Expected error: "Record not found (S1029)"
			if( !$this->negativeTestUpdatePropertiesOnNonExistDossier( 150 ) ) {
				$retVal = false;
				break;
			}

			// ... other negative tests

		} while( false );
		return $retVal;
	}

	/**
	 * To setObjectProperties on a standard property 'copyright' of dossier1 and validate its response.
	 *
	 * The property 'Copyright' is inserted with 256 multi-byte characters, while the allowed limit is 255.
	 * It is expected that the Copyright field will be truncated to 255 multi-byte characters.
	 *
	 * @param int $stepInfoNumber The numbering of the test to be added in the stepInfo (extra logging info).
	 * @return bool True when copyright is successfully adjusted, false otherwise.
	 */
	private function positiveTestAdjustCopyrightProperties( $stepInfoNumber )
	{
		$retVal = true;
		// Adjust copyright properties of dossier1.
		$stepInfo = "#{$stepInfoNumber}: Changing the copyright info properties by calling SetObjectProperties service.";
		$this->dossiers[0]->MetaData->RightsMetaData->CopyrightMarked = true;
		$this->dossiers[0]->MetaData->RightsMetaData->CopyrightURL = 'http://www.woodwing.com';
		$expectedDossierObject = unserialize( serialize( $this->dossiers[0]));

		$this->dossiers[0]->MetaData->RightsMetaData->Copyright = $this->composeMultibyteStrings( 256 ); // contains 256 multi-byte characters.

		$expectedCopyright = $this->composeMultibyteStrings( 255 );
		$expectedDossierObject->MetaData->RightsMetaData->Copyright = $expectedCopyright; // the allowed 255 multi-byte characters.

		$changedPropPaths = array(
			'RightsMetaData->Copyright' => $expectedCopyright,
			'RightsMetaData->CopyrightMarked' => true,
			'RightsMetaData->CopyrightURL' => 'http://www.woodwing.com'
		);

		do {
			$response = $this->setObjectProperties( $this->dossiers[0], $stepInfo, null );
			if( !$this->validateSetObjectPropertiesResponse( $expectedDossierObject, $response, $changedPropPaths )){
				$retVal = false;
				break;
			}
			$this->dossiers[0]->MetaData->RightsMetaData->Copyright = $expectedCopyright; // DB is updated, now, also update in the memory.
		} while ( false );

		return $retVal;
	}

	/**
	 * To setObjectProperties on a non-text blob field property PlainContent of dossier1 and validate its response.
	 *
	 * Since this field is stored as Blob, the max character 255 is not applicable.
	 * In other words, it will not be truncated to 255 characters.
	 *
	 * @param int $stepInfoNumber The numbering of the test to be added in the stepInfo (extra logging info).
	 * @return bool
	 */
	private function positiveTestNonTextBlobProperty( $stepInfoNumber )
	{
		$retVal = true;
		$stepInfo = "#{$stepInfoNumber}: Changing the PlainContent property by calling SetObjectProperties service.";
		$plainContent256MultiByteChar = $this->composeMultibyteStrings( 256 );
		$this->dossiers[0]->MetaData->ContentMetaData->PlainContent = $plainContent256MultiByteChar;
		$changedPropPaths = array(
			'ContentMetaData->PlainContent' => $plainContent256MultiByteChar,
		);

		do {
			$response = $this->setObjectProperties( $this->dossiers[0], $stepInfo, null );
			if( !$this->validateSetObjectPropertiesResponse( $this->dossiers[0], $response, $changedPropPaths )){
				$retVal = false;
				break;
			}
		} while ( false );

		return $retVal;
	}

	/**
	 * To setObjectProperties on a custom property CUSTOM_PROP_STRING of dossier1 and validate its response.
	 *
	 * @param int $stepInfoNumber The numbering of the test to be added in the stepInfo (extra logging info).
	 * @return bool
	 */
	private function positiveTestStringCustomProperty( $stepInfoNumber )
	{
		$retVal = true;
		$stepInfo = "#{$stepInfoNumber}: Changing the custom property '". self::CUSTOM_PROP_STRING ."' info properties by calling SetObjectProperties service.";
		if( $this->dossiers[0]->MetaData->ExtraMetaData ) foreach( $this->dossiers[0]->MetaData->ExtraMetaData as $extraMD ) {
			if( $extraMD->Property == self::CUSTOM_PROP_STRING ) {
				$this->stringCustomProperty = "012345678901234567890123456789012345678901234567890123456789012";
				$extraMD->Values = array( $this->stringCustomProperty );
				break; // found the corresponding custom property.
			}
		}

		do {
			$response = $this->setObjectProperties( $this->dossiers[0], $stepInfo, null );
			if( !$this->validateSetObjectPropertiesResponse( $this->dossiers[0], $response, array() )){
				$retVal = false;
				break;
			}
		} while ( false );

		return $retVal;
	}

	/**
	 * To setObjectProperties on a custom property CUSTOM_PROP_MULTI_BYTE_STRING of dossier1 and validate its response.
	 *
	 * @param int $stepInfoNumber The numbering of the test to be added in the stepInfo (extra logging info).
	 * @return bool
	 */
	private function positiveTestMultyByteStringCustomProperty( $stepInfoNumber )
	{
		$retVal = true;
		$stepInfo = "#{$stepInfoNumber}: Changing the custom property '". self::CUSTOM_PROP_MULTI_BYTE_STRING ."' info properties by calling SetObjectProperties service.";
		if( $this->dossiers[0]->MetaData->ExtraMetaData ) foreach( $this->dossiers[0]->MetaData->ExtraMetaData as $extraMD ) {
			if( $extraMD->Property == self::CUSTOM_PROP_MULTI_BYTE_STRING ) {
				$this->stringCustomProperty = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2);
				$extraMD->Values = array( $this->stringCustomProperty );
				break; // found the corresponding custom property.
			}
		}

		do {
			$response = $this->setObjectProperties( $this->dossiers[0], $stepInfo, null );
			if( !$this->validateSetObjectPropertiesResponse( $this->dossiers[0], $response, array() )){
				$retVal = false;
				break;
			}
		} while ( false );

		return $retVal;
	}

	/**
	 * To setObjectProperties on a normal property 'Name' of dossier1 and validate its response.
	 *
	 * The name will be adjusted to a 128 multi-byte characters but it is expected to be truncated to 63 characters.
	 * Note that the 128 characters is just a random number ( as long as it is more than 63 characters ).
	 *
	 * @param int $stepInfoNumber The numbering of the test to be added in the stepInfo (extra logging info).
	 * @return bool
	 */
	private function positiveTestAdjustNameFieldToLongMultiByteName( $stepInfoNumber )
	{
		$retVal = true;
		$stepInfo = "#{$stepInfoNumber}: Changing the property 'Name' to have a very long name by calling SetObjectProperties service.";
		$expectedDossierObject = unserialize( serialize( $this->dossiers[0]));
		$adjustedDossierName = $this->composeMultibyteStrings( 128 );
		$this->dossiers[0]->MetaData->BasicMetaData->Name = $adjustedDossierName; // contains 128 multi-byte characters.

		$expectedDossierName = $this->composeMultibyteStrings( 63 );
		$expectedDossierObject->MetaData->BasicMetaData->Name = $expectedDossierName; // the allowed 63 multi-byte characters.
		$changedPropPaths = array(
			'BasicMetaData->Name' => $expectedDossierName,
		);

		do {
			$response = $this->setObjectProperties( $this->dossiers[0], $stepInfo, null );
			if( !$this->validateSetObjectPropertiesResponse( $expectedDossierObject, $response, $changedPropPaths )){
				$retVal = false;
				break;
			}
			$this->dossiers[0]->MetaData->BasicMetaData->Name = $expectedDossierName; // DB is updated, now, also update in the memory.
		} while ( false );

		return $retVal;
	}

	/**
	 * Updates properties of a non-existing dossier by calling setObjectProperties.
	 * It is expected to have error: "Record not found (S1029)", which means the test is passed.
	 *
	 * @param int $stepInfoNumber The numbering of the test to be added in the stepInfo (extra logging info).
	 * @return bool True when update did fail on a non-existing dossier, false otherwise.
	 */
	private function negativeTestUpdatePropertiesOnNonExistDossier( $stepInfoNumber )
	{
		$retVal = true;
		$stepInfo = "#{$stepInfoNumber}: Attempt changing properties of non-existing object by calling SetObjectProperties service.";
		$tmpDossier = unserialize( serialize( $this->dossiers[0] ) ); // deep clone
		$tmpDossier->MetaData->BasicMetaData->ID = PHP_INT_MAX - 1;

		do {
			$response = $this->setObjectProperties( $tmpDossier, $stepInfo, '(S1029)' );
			if( !$this->validateSetObjectPropertiesResponse( $tmpDossier, $response, array() )){
				$retVal = false;
				break;
			}
		} while ( false );

		return $retVal;
	}

	/**
	 * Updates an object with given metadata by calling the SetObjectProperties service.
	 *
	 * @since 10.1.5 This function has been split in two. Previously it calls the service and validates its response. Now
	 *               it only calls the service. The response validation is done by validateSetObjectPropertiesResponse().
	 * @param Object $object Object properties an targets to update. On success, it gets updated with latest info from DB.
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @return WflSetObjectPropertiesResponse|null WflSetObjectPropertiesResponse when the request is successful, null otherwise.
	 */
	private function setObjectProperties( /** @noinspection PhpLanguageLevelInspection */ Object $object, $stepInfo, $expectedError )
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

		return $responseOk ? $response : null;
	}

	/**
	 * Validates SetObjectProperties response returned by setObjectProperties().
	 *
	 * @since 10.1.5 This function is the 'second part' of setObjectProperties(), see setObjectProperties() for more information.
	 * @param Object $expectedObject The expected Object to be validated again SetObjectProperties response $response.
	 * @param WflSetObjectPropertiesResponse $response
	 * @param array $changedPropPaths List of changed metadata properties, expected to be different.
	 * @return bool
	 */
	private function validateSetObjectPropertiesResponse( $expectedObject, $response, array $changedPropPaths )
	{
		if( is_null( $response )) {
			return false;
		}

		$compareOk = true;
		// Validate MetaData and Targets; Compare the original ones with the ones found in service response.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();

		$phpCompare->initCompare( $changedPropPaths, array() );
		if( !$phpCompare->compareTwoProps( $expectedObject->MetaData, $response->MetaData ) ) {
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$this->setResult( 'ERROR', $errorMsg, 'Problem detected in MetaData of SetObjectProperties response.' );
			$compareOk = false;
		}
		foreach( $changedPropPaths as $changedPropPath => $expPropValue ) {
			$retPropValue = null;
			eval( '$retPropValue = $response->MetaData->'.$changedPropPath.';' );
			if( $retPropValue != $expPropValue ) {
				$errorMsg = 'The returned MetaData->'.$changedPropPath.' is set to "'.
					$retPropValue.'" but should be set "'.$expPropValue.'".';
				$this->setResult( 'ERROR', $errorMsg, 'Problem detected in MetaData of SetObjectProperties response.' );
				$compareOk = false;
			}
		}
		$phpCompare->initCompare( array(), array() );
		if( !$phpCompare->compareTwoProps( $expectedObject->Targets, $response->Targets ) ) {
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$this->setResult( 'ERROR', $errorMsg, 'Problem detected in Targets of SetObjectProperties response.' );
			$compareOk = false;
		}

		// Update the original/cached object with response data.
		$expectedObject->MetaData = $response->MetaData;
		$expectedObject->Targets = $response->Targets;
		return $compareOk;
	}

	/**
	 * Compose multi-byte character(s) and return the string.
	 *
	 * @since 10.1.5
	 * @param int $numberOfMultiByteCharacter
	 * @return string
	 */
	private function composeMultibyteStrings( $numberOfMultiByteCharacter )
	{
		$multiByteCharacters = '';
		for( $i=1; $i <= $numberOfMultiByteCharacter; $i++ ) {
			$multiByteCharacters .= chr(0xE6).chr(0x98).chr(0x9F);
		}
		return $multiByteCharacters;
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
}