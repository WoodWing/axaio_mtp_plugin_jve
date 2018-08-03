<?php
/**
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_CustomProperties_TestCase extends TestCase
{
	public function getDisplayName() { return 'Custom Properties'; }
	public function getTestGoals()   { return 'Validates if the creation of Custom Object Properties can be done correctly.'; }
	public function getTestMethods() { return 'Tests the creation of a TinyText custom object property for MySQL and tests roundtripping the field on an object.'; }
	public function getPrio()        { return 20; }

	private $ticket = null;
	private $vars = null;
	private $admPropertyInfo = null;
	private $mcpUtils = null; // MultiChannelPublishingUtils

	private $dossier = null;
	private $template = null;
	private $form = null;
	private $publicationId = null;
	private $didAdjustTable = false;

	const CUSTPROP_TINYTEXT = 'C_BUILDTEST_TINYTEXT';
	const PUBLISH_PLUGIN = 'MultiChannelPublishingSample';

	/**
	 * Runs the testcases for this TestSuite.
	 */
	final public function runTest()
	{
		// Use the publishing Utils.
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';
		$this->mcpUtils = new MultiChannelPublishingUtils();
		if( !$this->mcpUtils->initTest( $this ) ) {
			return;
		}

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
		if( !$this->validateSessionData() ) { 
			return;
		}

		// Test requesting a BizProperty type mapping.
		if( !$this->validateBizPropertyTypeMapping() ) { 
			return;
		}

		// Attempt creating a new Custom Field in the Objects table. This is only needed once.
		if( !$this->setupModelAdjustmentForTable('objects') ) {
			return;
		}

		$types = array('string', 'list');
		foreach( $types as $type ) {
			$this->type = $type;
			LogHandler::Log( 'CustPropTest', 'INFO', 'Testing custom property type "'.$type.'" ...' );
			
			// Attempt creating a BizAdmProperty.
			$setup = true;
			if( !$this->setupAdmPropertyInfo() ) {
				$setup = false;
			}

			// Build the basic dataset to add publish forms.
			if( !$this->setupPublishFormData() ) {
				$setup = false;
			}
			
			if( $setup ) {
				// Roundtrip test 1, multibyte string placement.
				$this->testPublishFormObject('1');
	
				// Roundtrip test 2, empty string placement.
				$this->testPublishFormObject('2');
	
				// Roundtrip test 3, normal string placement.
				$this->testPublishFormObject('3');
	
				// Roundtrip test 3, rely on the default value.
				$this->testPublishFormObject('4');
			}
			
			// Remove all the test data.
			$teardown = true;
			if( !$this->cleanupPublishFormData() ) {
				$teardown = false;
			}

			// Revert the changes in the tables.
			if( !$this->cleanupModelAdjustmentForTable('objects') ) {
				$teardown = false;
			}

			// Remove the ADM propertyINfo from the property table.
			if( !$this->cleanupAdmProperty() ) {
				$teardown = false;
			}
			
			// Avoid more problems next run when we could not cleanup this run.
			if( !$teardown ) {
				break;
			}
		}

		// Import the custom properties from the MultiChannel Sample plugin
		if ( $this->importDefinitions() ) {
			// Create a Dossier, Publish Form Template and Publish Form object in database for testing.
			if( $this->setupTestData() ) {
				$this->testPublishFormCustomProperties();
			}

			// First teardown the stucture
			$this->tearDownTestData();
			// And then remove the properties. Otherwise caching is still looking for properties that don't exist.
			$this->removeDefinitions();
		}
	}

	/**
	 * Tests creating a new BizAdmProperty
	 *
	 * @return bool Whether or not the operation was succesful.
	 */
	private function setupAdmPropertyInfo()
	{
		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR . '/server/dbclasses/DBAdmProperty.class.php';
		// DataClass definition does not exist for the AdmPropertyInfo yet.
		$admPropertyInfo = new AdmPropertyInfo();
		$admPropertyInfo->PublicationId = 0;
		$admPropertyInfo->ObjectType = '';
		$admPropertyInfo->Name = self::CUSTPROP_TINYTEXT;
		$admPropertyInfo->DisplayName = self::CUSTPROP_TINYTEXT;
		$admPropertyInfo->Category = null;
		$admPropertyInfo->Type = $this->type;
		$admPropertyInfo->DefaultValue = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2).chr(0xE6).chr(0x97).chr(0xA5).chr(0xE5).chr(0xA0).chr(0xB1);
		$admPropertyInfo->ValueList = null;
		$admPropertyInfo->MinValue = null;
		$admPropertyInfo->MaxValue = null;
		$admPropertyInfo->MaxLength = null;
		$admPropertyInfo->DBUpdated = false;
		$admPropertyInfo->DependentProperties = null; // future
		$admPropertyInfo->PluginName = null;
		$admPropertyInfo->Entity = null;

		// Create the Property.
		$this->admPropertyInfo = BizAdmProperty::insertAdmPropertyInfo( $admPropertyInfo );
		if( is_null($this->admPropertyInfo) ) {
			$this->setResult( 'ERROR',  'Could not create an AdmPropertyInfo object.');
			return false;
		}
		return true;
	}

	/**
	 * Retrieve the ticket and brand id from session data.
	 *
	 * @return bool Whether or not the data was available.
	 */
	private function validateSessionData()
	{
		// Retrieve the Ticket and Brand id that has been determined by "Setup test data" TestCase.
		$this->vars = $this->getSessionVariables();

		$this->ticket = @$this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 
				'Please enable the "Setup test data" entry and try again.' );
			return false;
		}

		$this->publicationId = $this->vars['BuildTest_MultiChannelPublishing']['publication']->Id;
		if (is_null($this->publicationId)) {
			$this->setResult( 'ERROR',  'Could not retrieve the publication id from the session data.',
				'Please enable the "Setup test data" entry and try again.' );
			return false;
		}
		return true;
	}

	/**
	 * Tests the BizProperty Mapping.
	 *
	 * @return bool Whether or not the test was succesful.
	 */
	private function validateBizPropertyTypeMapping()
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$dbtype = BizProperty::convertCustomPropertyTypeToDB( 'string' );

		// Only test the mapping for MySQL as the types for Oracle / MsSql are unknown
		if( DBTYPE == 'mysql' ) {
			if( $dbtype != 'tinytext' ) {
				LogHandler::Log( 'CustPropTest', 'ERROR', 'MySQL type mismatch.' );
				return false;
			}
		} else {
			if( $dbtype != "varchar(200) default ''" ) {
				LogHandler::Log( 'CustPropTest', 'ERROR', 'Oracle/MSSQL type mismatch.' );
				return false;
			}
		}
		return true;
	}

	/**
	 * Tests adjusting the database model for a table.
	 *
	 * @param string $table The table to be modified.
	 * @return bool Whether or not the operation was succesful.
	 */
	private function setupModelAdjustmentForTable( $table )
	{
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';

		// Create the custom field.
		try {
			$this->didAdjustTable = false;
			BizCustomField::insertFieldAtModel( $table, self::CUSTPROP_TINYTEXT, 'string' );
			$this->didAdjustTable = true;
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR',  'Adding field to ' . $table . ' table error for type: ' . $this->type );
			return false;
		}

		// Attempt retrieval of the field.
		$objectFields = BizCustomField::getFieldsAtModel( $table );
		if( !isset($objectFields[self::CUSTPROP_TINYTEXT]) ) {
			$this->setResult( 'ERROR',  'Field does not exist in table: ' . $table . ' for type: ' . $this->type );
			return false;
		}

		// Check the types => MYSQL
		if( DBTYPE == 'mysql' ) {
			if( $objectFields[self::CUSTPROP_TINYTEXT]['type'] != 252 ) {
				$this->setResult( 'ERROR',  'Field is not tinytext for table: ' . $table . ' for type: ' . $this->type );
				return false;
			}
		}
		return true;
	}

	/**
	 * Attempts / Tests the removal of fields from the data model.
	 *
	 * @param string $table The table to remove the fields from.
	 * @return bool Whether or not the operation was succesful.
	 */
	private function cleanupModelAdjustmentForTable( $table )
	{
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';

		// Delete the custom field from the Objects table.
		if( $this->didAdjustTable ) {
			$this->didAdjustTable = false;
			try {
				BizCustomField::deleteFieldAtModel( $table, self::CUSTPROP_TINYTEXT );
			} catch( BizException $e ) {
				LogHandler::Log( 'CustPropTest', 'ERROR', 'Deleting field from "'.$table.'" '.
								'table error, while testing for type: '.$this->type );
				return false;
			}
		}

		// Attempt retrieval of the field.
		$objectFields = BizCustomField::getFieldsAtModel( $table );
		if( isset($objectFields[self::CUSTPROP_TINYTEXT]) ) {
			LogHandler::Log( 'CustPropTest', 'ERROR', 'Field still exists in table "'.$table.'" '.
							'for type: '.$this->type );
			return false;
		}
		return true;
	}

	/**
	 * Tests manipulating a PublishForm Object.
	 *
	 * @param string $testCase The case to be ran, see the function body.
	 */
	private function testPublishFormObject( $testCase )
	{
		$property = null;
		switch( $testCase ) {
			case '1' : //Roundtrip test 1, multibyte string placement.
				$property = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2).
							chr(0xE6).chr(0x97).chr(0xA5).chr(0xE5).chr(0xA0).chr(0xB1) . 'a';
				break;
			case '2' :// Roundtrip test 2, empty string placement.
				$property = '';
				break;
			case '3' : // Roundtrip test 3, normal string placement.
				$property = 'normal string test.';
				break;
			case '4' : // Roundtrip test 3, rely on the default value.
				$property = null;
				break;
		}

		$metadata = null;
		if( !is_null($property) ) {
			$metadata = new MetaData();
			$emd = new ExtraMetaData( self::CUSTPROP_TINYTEXT, array($property) );
			$metadata->ExtraMetaData = array( $emd );
		}

		// Create a Publish Form (based on the template) and assign it to the dossier.
		$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
		$form = $this->mcpUtils->createPublishFormObject( $this->template, 
					$this->dossier, $stepInfo, MultiChannelPublishingUtils::RELATION_NORMAL, $metadata );

		// Find the field in the response.
		if( $form ) foreach( $form->MetaData->ExtraMetaData as $extra ) {
			if( $extra->Property == self::CUSTPROP_TINYTEXT ) {
				switch( $testCase ) {
					case '1' :
					case '2' :
					case '3' :
						if (!$property == $extra->Values[0]) {
							$this->setResult( 'ERROR',  'Custom property did not match expected value '.
								'for case "' . $testCase . '" while testing type: ' . $this->type .'".' );
						}
						break;
					case '4' :
						$testString = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2).
									chr(0xE6).chr(0x97).chr(0xA5).chr(0xE5).chr(0xA0).chr(0xB1);
						if (!$testString == $extra->Values[0]) {
							$this->setResult( 'ERROR',  'Custom property was not set to the default '.
								'for case "' . $testCase . '" while testing type "' . $this->type .'".' );
						}
						break;
				}
			}
		}

		// Permanent delete the Publish Form.
		if( $form ) {
			$id = $form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Publish Form object: '.$errorReport );
			}
		}
	}

	/**
	 * Sets up the required fields
	 *
	 * @return bool Whether or not the data has been set up properly.
	 */
	private function setupPublishFormData()
	{
		$retVal = true;

		// Create a Publish Form Template.
		$stepInfo = 'Create the Publish Form Template.';
		$this->template = $this->mcpUtils->createPublishFormTemplateObject( $stepInfo );
		if( is_null($this->template) ) {
			$this->setResult( 'ERROR', 'Could not create the Publish Form Template.');
			$retVal = false;
		}

		// Create a Dossier.
		$stepInfo = 'Create the Dossier object.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo );
		if( is_null($this->dossier) ) {
			$this->setResult( 'ERROR', 'Could not create the Dossier.' );
			$retVal = false;
		}
		
		return $retVal;
	}

	/**
	 * Cleans up the required data for testing PublishForms.
	 *
	 * @return bool Whether or not the operation was succesful.
	 */
	private function cleanupPublishFormData()
	{
		$retVal = true;
		
		// Permanent delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->dossier = null;
		}
		
		// Permanent delete the Publish Form Template.
		if( $this->template ) {
			$id = $this->template->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Article object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport );
				$retVal = false;
			}
			$this->template = null;
		}
		return $retVal;
	}

	/**
	 * Removes the created admProperty created for the testcases and
	 *
	 * @return bool Whether the operation was succesful or not.
	 */
	private function cleanupAdmProperty()
	{
		$retVal = true;
		if( $this->admPropertyInfo ) {
			require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
			$result = BizAdmProperty::deleteAdmPropertyInfo( $this->admPropertyInfo );
			if( !$result ) {
				$this->setResult( 'ERROR', 'Could not remove the AdmPropertyInfo while testing for type: ' . $this->type );
				$retVal = false;
			}
			$this->admPropertyInfo = null;
		}
		return $retVal;
	}

	/**
	 * Imports the custom properties from the publish plugin...
	 *
	 * @return bool
	 */
	private function importDefinitions()
	{
		$result = true;

		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$pluginErrs = null;
		BizProperty::validateAndInstallCustomProperties( self::PUBLISH_PLUGIN, $pluginErrs, false );

		return $result;
	}

	/**
	 * Remove the custom object properties of the
	 * MultiChannelPublishingSample plugin which were imported by {@link: importDefinitions()}.
	 *
	 * @return bool Whether or not the definitions could be removed.
	 */
	private function removeDefinitions()
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$retVal = true;

		// Remove the custom object properties (as imported for the template).
		if( !BizProperty::removeCustomProperties( self::PUBLISH_PLUGIN ) ) {
			$this->setResult( 'ERROR',  'Could not remove custom properties that were imported by '.
				'server plug-in "' .self::PUBLISH_PLUGIN.'".' );
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Create a Template, Dossier, Form object in database for testing.
	 *
	 * @return bool Whether or not all objects could be created.
	 */
	private function setupTestData()
	{
		$retVal = true;

		// Compose postfix for object name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		// Create the Publish Form Template.
		$stepInfo = 'Create the Publish Form Template.';
		$this->template = $this->mcpUtils->createPublishFormTemplateObject( $stepInfo );
		if( is_null($this->template) ) {
			$this->setResult( 'ERROR',  'Could not create the Publish Form Template.');
			$retVal = false;
		}

		// Create the Dossier.
		$stepInfo = 'Create the Dossier object for Web.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo, 'DossierForWeb '.$postfix, 'web' );
		if( is_null($this->dossier) ) {
			$this->setResult( 'ERROR',  'Could not create the Dossier.' );
			$retVal = false;
		}

		// Create the Publish Form.
		if( $this->dossier ) {
			$stepInfo = 'Create the Publish Form object.';
			$this->form = $this->mcpUtils->createPublishFormObject( $this->template, $this->dossier, $stepInfo );
			if( is_null($this->form) ) {
				$this->setResult( 'ERROR',  'Could not create Publish Form object.' );
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Tear down the Test environment setup in {@link: setupTestData()}.
	 */
	private function tearDownTestData()
	{
		$retVal = true;

		// Permanent delete the Publish Form.
		if( $this->form ) {
			$id = $this->form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Publish Form object: '.$errorReport );
				$retVal = false;
			}
			$this->form = null;
		}

		// Permanent delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->dossier = null;
		}

		// Permanent delete the Publish Form Template.
		if( $this->template ) {
			$id = $this->template->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Publish Form Template object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Template object: '.$errorReport );
				$retVal = false;
			}
			$this->template = null;
		}
		return $retVal;
	}

	/**
	 * This function tests if the properties are filtered out correctly.
	 *
	 * @return bool
	 */
	private function testPublishFormCustomProperties()
	{
		$formId = $this->form->MetaData->BasicMetaData->ID;

		try {
			require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs = array( $formId );
			$request->Lock = false;
			$request->Rendition = 'none';

			$service = new WflGetObjectsService();
			$response = $service->execute($request);
			if ( !$response->Objects ) {
				$this->setResult( 'ERROR', 'Could not retrieve the form object.' );
				return false;
			}
			$object = reset($response->Objects);
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not retrieve the form object: '.$e->getMessage() );
			return false;
		}

		$extraMetaData = $object->MetaData->ExtraMetaData;
		// The following properties should be available since the PublishSystem is not set or set correctly and
		// the template id is empty.
		$availableMetaData = array( 'C_MCPSAMPLE_PUBLISHFORM_BOOL', 'C_MCPSAMPLE_PUBLISHFORM_STRING' );
		foreach( $availableMetaData as $propertyName ) {
			$found = false;
			foreach( $extraMetaData as $metaData ) {
				if ( $metaData->Property == $propertyName ) {
					$found = true;
					break;
				}
			}
			if ( !$found ) {
				$this->setResult( 'ERROR', 'Property with name: "'.$propertyName.'" is not found in the list of custom properties for the Form. This property should be available.' );
				return false;
			}
		}

		// The following properties have the wrong template id assinged so should not be in the list.
		$notAvailableMetaData = array( 'C_MCPSAMPLE_PUBLISHFORM_INT', 'C_MCPSAMPLE_PUBLISHFORM_DOUBLE');
		foreach( $notAvailableMetaData as $propertyName ) {
			foreach( $extraMetaData as $metaData ) {
				if ( $metaData->Property == $propertyName ) {
					$this->setResult( 'ERROR', 'Property with name: "'.$propertyName.'" is found in the list of custom properties for the Form. This property shouldn\'t be available.' );
					return false;
				}
			}
		}

		return true;
	}
}