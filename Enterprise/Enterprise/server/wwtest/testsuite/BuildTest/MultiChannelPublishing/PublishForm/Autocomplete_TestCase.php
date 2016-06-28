<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_Autocomplete_TestCase extends TestCase
{
	// Session related stuff.
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite.
	private $mcpUtils = null; // MultiChannelPublishingUtils.

	// Objects of WorkflowTest to setup/teardown:
	private $wflDossier = null;
	private $wflForm = null;
	private $wflFormLocked = false;

	// Admin entities to setup/teardown:
	private $pubChannel = null;
	private $issue = null;
	
	// Templates objects:
	private $templates = null; // Imported by this test script.
	private $foreignTemplates = null; // Templates found that are -not- created by this script.
	
	// Given admin entities to work:
	private $publicationId = null;
	private $webIssueId = null;

	private $tipMsg = null;
	private $publishSystemId = null;

	const PUBLISH_PLUGIN = 'MultiChannelPublishingSample';
	const AUTOCOMPLETE_PROVIDER = 'MultiChannelPublishingSample';
	const MAX_SUGGESTIONS = 6;

	public function getDisplayName()
	{
		return 'Autocomplete';
	}

	public function getTestGoals()
	{
		return 'Checks if the Autocomplete service works correctly.';
	}

	public function getTestMethods()
	{
		return 'Performs an Autocomplete GetDialog2 call to check if the data is round-tripped correctly.';
	}

	public function getPrio()
	{
		return 105;
	}

	/**
	 * Runs the test cases for this TestSuite.
	 */
	final public function runTest()
	{
		// Use the publishing Utils.
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';
		$this->mcpUtils = new MultiChannelPublishingUtils();
		if( !$this->mcpUtils->initTest( $this ) ) {
			return;
		}

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the data that has been determined by the "Setup test data" TestCase.
		$this->vars = $this->getSessionVariables();
		$this->ticket        = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$this->publicationId = $this->vars['BuildTest_MultiChannelPublishing']['publication']->Id;
		$pubChannelObj       = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'];
		$this->webIssueId    = $this->vars['BuildTest_MultiChannelPublishing']['webIssue']->Id;

		BizSession::checkTicket( $this->ticket );
		
		// Remember which templates are already in the DB, before the script creates more templates.
		// This is needed to skip those templates when cleaning up the DB, see removeDefinitions().
		$this->foreignTemplates = array();
		$this->getPublishFormTemplates( array(), $pubChannelObj->Id, $this->foreignTemplates );
		
		// Create a pub channel and an issue. When definitions are imported (see below)
		// then for this pub channel the templates are imported too.
		if( $this->setupPubChannelAndIssue() ) {
		
			// Import the custom properties, Publish Form Templates and dialog of the 
			// server plug-in "MultiChannelPublishingSample".
			if( $this->importDefinitions() ) {
				
				// Search the DB for the created templates and check if we can find all.
				// Note that for each pub channel a template should have been created.
				$continue = true;
				$this->templates = array();
				$templateName = 'All Widgets Sample Template';
				$pubChannelIds = array( $pubChannelObj->Id, $this->pubChannel->Id );
				foreach( $pubChannelIds as $pubChannelId ) {
					if( !$this->getPublishFormTemplates( array( $templateName ), $pubChannelId, $this->templates ) ) {
						$continue = false;
						break;
					}
				}

				// Run the tests, for each Publish Form Template.
				if( $continue ) {
					$this->tipMsg = 'Error occurred while testing Autocomplete '.
										'in context of Publish Form Template "'.$templateName.'".';
					$this->runAutocompleteTest( $templateName, $pubChannelObj );
				}
			}
			
			// Remove the definitions (imported for the Publish Form Templates) from DB 
			// and remove the templates too.
			$this->removeDefinitions();
		}
		
		// Remove the pub channel and issue.
		$this->tearDownPubChannelAndIssue();
		
		BizSession::endSession();
	}

	/**
	 *  Retrieve Publish Form Templates via NamedQuery and GetObjects service calls.
	 *
	 * @param string[] $templateNames List of template names that are expected to be present in DB for the given channel.
	 * @param int $pubChannelId The PubChannelId to be used in the QueryParam.
	 * @param array $templates The template objects found. Two keys are used: $template[Name][PubChannelId] = Object.
	 * @return bool Whether or not all templates could be found.
	 */
	private function getPublishFormTemplates( $templateNames, $pubChannelId, &$templates )
	{
		$allFound = false;

		// Search for all Publish Form Templates within the given channel.
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		$request = new WflNamedQueryRequest();
		$request->Ticket	= $this->ticket;
		$request->Query		= 'PublishFormTemplates';
		$queryParam = new QueryParam();
		$queryParam->Property  = 'PubChannelId';
		$queryParam->Operation = '=';
		$queryParam->Value     = $pubChannelId;
		$request->Params = array( $queryParam );

		$stepInfo = 'Searching for all Publish Form Templates within a channel.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( $response ) {

			// Determine column indexes to work with.
			$colNames = array( 'ID', 'Name' );
			foreach( $colNames as $colName ) {
				foreach( $response->Columns as $index => $column ) {
					if( $column->Name == $colName ) {
						$indexes[$colName] = $index;
						break; // Found.
					}
				}
			}

			// Lookup the given Publish Form Template.
			foreach( $response->Rows as $row ) {
				$templateId = $row[$indexes['ID']];
				$templateName = $row[$indexes['Name']];

				// Retrieve the Publish Form Template from the DB.
				if( $templateId ) {
					$stepInfo = 'Retrieving a Publish Form Template.';
					$response = $this->mcpUtils->getObjects( $stepInfo, array( $templateId ), false, 'none',
						null, null, null );
					if( $response ) {
						$templates[$templateName][$pubChannelId] = $response->Objects[0];
					}
				} else {
					$this->setResult( 'ERROR', 'Could not find Publish Form Template "'.$templateName.'".' );
				}
			}

			// Check if we could find all expected templates in the response.
			$allFound = true;
			foreach( $templateNames as $templateName ) {
				if( !isset( $templates[$templateName][$pubChannelId] ) ) {
					$this->setResult( 'ERROR', 'Could not find Publish Form Template "'.$templateName.'".' );
					$allFound = false;
					break;
				}
			}
		}
		return $allFound;
	}

	/**
	 * Creates a PubChannel and Issue for the publish system "MultiChannelPublishingSample".
	 *
	 * @return bool Whether or not the creations were successful.
	 */
	private function setupPubChannelAndIssue()
	{
		$retVal = true;

		// Compose postfix for issue/channel names.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		// Create a PubChannel.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$admPubChannel = new AdmPubChannel();
		$admPubChannel->Name = 'PubChannel '.$postfix;
		$admPubChannel->Description = 'Created by Build Test class: '.__CLASS__;
		$admPubChannel->Type = 'web';
		$admPubChannel->PublishSystem = self::PUBLISH_PLUGIN;
		$pubChannelResp = $this->utils->createNewPubChannel( $this, $this->ticket,
			$this->publicationId, $admPubChannel );
		if( isset( $pubChannelResp->PubChannels[0] ) ) {
			$this->pubChannel = $pubChannelResp->PubChannels[0];
		} else {
			$retVal = false;
			$this->pubChannel = null;
		}

		// Create an Issue for the PubChannel.
		$this->issue = null;
		if( $this->pubChannel ) {
			$admIssue = new AdmIssue();
			$admIssue->Name = 'Issue '.$postfix;
			$admIssue->Description = 'Created by Build Test class: '.__CLASS__;
			$issueResp = $this->utils->createNewIssue( $this, $this->ticket,
				$this->publicationId, $this->pubChannel->Id, $admIssue );
			if( isset( $issueResp->Issues[0] ) ) {
				$this->issue = $issueResp->Issues[0];
			} else {
				$retVal = false;
				$this->issue = null;
			}
		}
		return $retVal;
	}

	/**
	 *  Imports the Custom properties, Templates and Dialog(s) of the server plug-in named "MultiChannelPublishingSample".
	 *
	 * @return bool Whether or not the imports were successful.
	 */
	private function importDefinitions()
	{
		// Compose the application class name, for example: AdminWebAppsSample_App2_EnterpriseWebApp.
		$appClassName = self::PUBLISH_PLUGIN . '_ImportDefinitions_EnterpriseWebApp';
		$pluginPath = BASEDIR.'/config/plugins/'.self::PUBLISH_PLUGIN;
		$includePath = $pluginPath.'/webapps/'.$appClassName.'.class.php';

		$result = true;
		if( file_exists( $includePath ) ) {
			require_once $includePath;
			$webApp = new $appClassName;

			try {
				$webApp->importDefinitions();
			} catch( BizException $e ) {
				$this->setResult( 'ERROR',  'Failed to import Definitions. '.$e->getMessage(), $this->tipMsg );
				$result = false;
			}
		} else {
			$this->setResult( 'ERROR',  'No such PHP module available:' . $includePath,
				'Please check in the "'.self::PUBLISH_PLUGIN.'" plugin. ' . $this->tipMsg );
			$result = false;
		}
		return $result;
	}

	/**
	 * Removes the definitions which were imported for this testcase.
	 *
	 * Remove the custom object properties, templates and dialog definitions of the
	 * MultiChannelPublishingSample plugin which were imported by {@link: importDefinitions()}.
	 *
	 * @return bool Whether or not the definitions could be removed.
	 */
	private function removeDefinitions()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmActionProperty.class.php';
		$retVal = true;
		$templateName = '';

		foreach( $this->templates as $templateName => $templates ) {
			foreach( $templates as $pubChannelId => $template ) {

				// Skip templates that are not ours.
				if( isset( $this->foreignTemplates[$templateName][$pubChannelId] ) ) {
					continue;
				}

				// Remove the Publish Form Template from the DB.
				$errorReport = null;
				$id = $template->MetaData->BasicMetaData->ID;
				$stepInfo = 'Tear down Publish Form Template object.';
				if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
					$this->setResult( 'ERROR',  'Could not remove Publish Form Template '.
						'"'.$templateName.'" (assigned to pub channel id='.$pubChannelId.') '.
						'that was imported by plugin "'.self::PUBLISH_PLUGIN.'". '.$errorReport );
					$retVal = false;
				}

				// Remove the Dialog definition from the DB (as imported for the template).
				$action = 'SetPublishProperties';
				$documentId = $template->MetaData->BasicMetaData->DocumentID;
				if( !BizAdmActionProperty::deleteAdmPropertyUsageByActionAndDocumentId( $action, $documentId ) ) {
					$this->setResult( 'ERROR',  'Could not remove the Dialogs for '.
						'Publish Form Template "'.$templateName.'" that were imported by '.
						'server plug-in "'.self::PUBLISH_PLUGIN.'".' );
					$retVal = false;
				}
			}
		}

		// Remove the custom object properties (as imported for the template).
		if( !BizProperty::removeCustomProperties( self::PUBLISH_PLUGIN ) ) {
			$this->setResult( 'ERROR',  'Could not remove custom properties for '.
				'Publish Form Template "'.$templateName.'" that were imported by '.
				'server plug-in "' .self::PUBLISH_PLUGIN.'".' );
			$retVal = false;
		}
		try {
			$this->mcpUtils->clearAutocompleteTermEntitiesAndTerms( self::PUBLISH_PLUGIN );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Cannot delete Term Entities or Terms.', $e->getMessage() . '. ' . $e->getDetail() );
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Removes the PubChannel and Issue created at {@link: setupPubChannelAndIssue()}.
	 *
	 * @return bool Whether or not the deletions were successful.
	 */
	private function tearDownPubChannelAndIssue()
	{
		$result = true;

		// Delete the Issue.
		if( $this->issue ) {
			if( !$this->utils->removeIssue( $this, $this->ticket,
				$this->publicationId, $this->issue->Id ) ) {
				$result = false;
			}
		}

		// Delete the Publication Channel.
		if( $this->pubChannel ) {
			if( !$this->utils->removePubChannel( $this, $this->ticket,
				$this->publicationId, $this->pubChannel->Id ) ) {
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Carries out several service calls to check if the Autocomplete service is executed correctly.
	 *
	 * @param string $templateName The template name where the PublishForm will be initiated from.
	 * @param PubChannel pubChannelObj PubChannel object to get the correct template.
	 */
	private function runAutocompleteTest( $templateName, $pubChannelObj )
	{
		if( $this->setupAutocompleteTest( $templateName, $pubChannelObj ) ) {
			do {
				if( !$this->runAutocompleteTestOnFormField() ) {
					break;
				}

				if( !$this->runAutocompleteServiceTest() ) {
					break;
				}

				// Trigger an autocomplete on a standalone dictionary and verify the results.
				if( !$this->runAutocompleteServiceTest( 'StandaloneAutocompleteSample', array('Abcoude'), 'Ams') ) {
					break;
				}


			} while( false );
		}
		$this->tearDownAutocompleteTest();
	}

	/**
	 * Creates several objects to prepare for the BuildTest.
	 *
	 * @param string $templateName The template name where the PublishForm will be initiated from.
	 * @param PubChannelInfo $pubChannelObj PubChannel object to get the correct template.
	 * @return bool True when all the necessary objects have been successfully created; False otherwise.
	 */
	private function setupAutocompleteTest( $templateName, $pubChannelObj )
	{
		$retVal = true;
		// Create dossier (to place the form in the dossier)
		$stepInfo = 'Create the Dossier object.';
		$this->wflDossier = $this->mcpUtils->createDossier( $stepInfo );
		if( is_null( $this->wflDossier ) ) {
			$this->setResult( 'ERROR',  'Could not create the Dossier.', $this->tipMsg );
			$retVal = false;
		}

		// Create the Publish Form.
		$this->wflForm = null;
		if( $this->wflDossier ) {
			$template = $this->templates[$templateName][$pubChannelObj->Id];
			$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
			$this->wflForm = $this->mcpUtils->createPublishFormObject( $template, $this->wflDossier, $stepInfo );
			if( is_null( $this->wflForm ) ) {
				$this->setResult( 'ERROR',  'Could not create the Publish Form.', $this->tipMsg );
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Calls Autocomplete service call and verify its response.
	 *
	 * @return bool Whether or not the test was successful.
	 */
	private function runAutocompleteServiceTest( $provider=null, $ignoreValues=array( 'amsabang', 'amsamka' ), $searchValue='ams' )
	{
		require_once BASEDIR . '/server/services/wfl/WflAutocompleteService.class.php';
		$request = new WflAutocompleteRequest();
		$request->Ticket = $this->ticket;
		$request->AutocompleteProvider = (is_null($provider)) ? self::AUTOCOMPLETE_PROVIDER : $provider;
		$request->PublishSystemId = $this->publishSystemId; // Taken from the GetDialog2Response.
		$request->ObjectId = null;

		$autoSuggestProperty = new AutoSuggestProperty();
		$autoSuggestProperty->Name = 'C_MCPSAMPLE_TOURISMCITIES';
		$autoSuggestProperty->Entity = 'City';
		$autoSuggestProperty->IgnoreValues = $ignoreValues;
		$request->Property = $autoSuggestProperty;
		$request->TypedValue = $searchValue;
		$stepInfo = 'Calling Autocomplete service.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$result = $this->validateAutocompleteResponse( $response, $searchValue, $ignoreValues );

		return $result;
	}

	/**
	 * Validate the response returned by the Autocomplete service.
	 *
	 * @param WflAutocompleteResponse $response The Response object to be validated.
	 * @param string $searchValue The original searched for value.
	 * @param string[] $ignoreValues An array of names of TermEntity Objects to ignore.
	 * @return bool Whether or not the response is valid.
	 */
	private function validateAutocompleteResponse( $response, $searchValue, $ignoreValues )
	{
		$result = true;
		do {
			if( !$response->Tags ) {
				$this->setResult( 'ERROR', 'Autocomplete service did not return any tags, which is not expected. ' .
					'Please check in the Autocomplete service call.', $this->tipMsg );
				$result = false;
				break;
			}
			if( count( $response->Tags ) > self::MAX_SUGGESTIONS ) {
				$this->setResult( 'ERROR', 'Autocomplete service returned "' . count( $response->Tags ) . '" suggestions, ' .
					'while the maximum return suggestions should be up to "'.self::MAX_SUGGESTIONS.'" only. ' .
					'Please check in the Autocomplete service response.', $this->tipMsg );
				$result = false;
				break;
			}

			$ignoreValues = array_flip( $ignoreValues );
			foreach( $response->Tags as $tag ) {
				$value = $tag->Value;
				if( array_key_exists( $value, $ignoreValues )){
					$this->setResult( 'ERROR', '"'.$value .'" is set to be ignore value, but it is returned in the Autocomplete ' .
						'response, which is wrong. Please check in the Autocomplete service call.', $this->tipMsg );
					$result = false;
					break 2; // Break the foreach loop and the do-while loop.
				} else {
					if( strpos( $value, $searchValue ) === false ){
						$this->setResult( 'ERROR', '"'.$value .'" returned by the Autocomplete does not contain in the '.
							'search value "'.$searchValue.'", which is wrong. Please check in the Autocomplete service call.', $this->tipMsg );
						$result = false;
						break 2; // Break the foreach loop and the do-while loop.
					}
				}
			}
		} while ( false );
		return $result;
	}

	/**
	 * Calls GetDialog2 service call and validate the Dialog widget in the response.
	 *
	 * @return bool Whether or not the test was successful.
	 */
	private function runAutocompleteTestOnFormField()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket               = $this->ticket;
		$request->Action               = 'SetPublishProperties';
		$request->MetaData             = $this->composeGetDialog2ReqMetaData();
		$request->Areas                = array( 'Workflow' );

		$stepInfo = 'Calling GetDialog2 service call.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$result = $this->validateGetDialog2Resp( $response );

		return $result;
	}

	/**
	 * Compose a list of MetaDataValue object.
	 *
	 * @return MetaDataValue[] The MetaDataValue objects to place in the request.
	 */
	private function composeGetDialog2ReqMetaData()
	{
		$metaDataValues = array();
		// ID.
		$mdValue= new MetaDataValue();
		$mdValue->Property = 'ID';
		$mdValue->Values = null;
		$propValue = new PropertyValue();
		$propValue->Value = $this->wflForm->MetaData->BasicMetaData->ID;
		$mdValue->PropertyValues = array( $propValue );
		$metaDataValues[] = $mdValue;

		// Issue.
		$mdValue= new MetaDataValue();
		$mdValue->Property = 'Issue';
		$mdValue->Values = null;
		$propValue = new PropertyValue();
		$propValue->Value = $this->webIssueId;
		$mdValue->PropertyValues = array( $propValue );
		$metaDataValues[] = $mdValue;

		// Type.
		$mdValue= new MetaDataValue();
		$mdValue->Property = 'Type';
		$mdValue->Values = null;
		$propValue = new PropertyValue();
		$propValue->Value = $this->wflForm->MetaData->BasicMetaData->Type;
		$mdValue->PropertyValues = array( $propValue );
		$metaDataValues[] = $mdValue;

		return $metaDataValues;
	}

	/**
	 * Validates the TermEntity and AutocompleteProvider in the widget returned by getDialog2 response.
	 *
	 * @param WflGetDialog2Response $response The Response to be validated.
	 * @return bool Whether or not validation was successful.
	 */
	private function validateGetDialog2Resp( $response )
	{
		$result = true;
		$termEntityFound = false;
		$autocompleteProviderFound = false;
		$publishSystemIdFound = false;
		if( $response->Dialog->Tabs ) foreach( $response->Dialog->Tabs as $tab ) {
			if( $tab->Widgets ) foreach( $tab->Widgets as $widget ) {
				if( $widget->PropertyInfo->Name == 'C_MCPSAMPLE_TOURISMCITIES' ) {
					if( $widget->PropertyInfo->TermEntity != 'City' ) {
						$this->setResult( 'ERROR', 'The TermEntity returned in the Dialog widget is expected to be ' .
							'"City" but "'.$widget->PropertyInfo->TermEntity.'" is returned, which is wrong. ' .
							'Please check in the Dialog widget for "C_MCPSAMPLE_TOURISMCITIES".', $this->tipMsg );
						$result = false;
					} else {
						$termEntityFound = true;
					}

					if( $widget->PropertyInfo->AutocompleteProvider != self::AUTOCOMPLETE_PROVIDER ) {
						$this->setResult( 'ERROR', 'The AutocompleteProvider returned in the Dialog widget is expected to be "'.
							self::AUTOCOMPLETE_PROVIDER .'" but "'.$widget->PropertyInfo->AutocompleteProvider.'" is '.
							'returned, which is wrong. Please check in the Dialog widget for "C_MCPSAMPLE_TOURISMCITIES".',
							$this->tipMsg );
						$result = false;
					} else {
						$autocompleteProviderFound = true;
					}

					if( $widget->PropertyInfo->PublishSystemId != '' ) {
						$this->setResult( 'ERROR', 'The PublishSystemId returned in the Dialog widget is expected to be "'.
							'empty \'\' but "'.$widget->PropertyInfo->PublishSystemId .'" is returned, '.
							'which is wrong. Please check in the Dialog widget for "C_MCPSAMPLE_TOURISMCITIES".' );
						$result = false;
					} else {
						$this->publishSystemId = $widget->PropertyInfo->PublishSystemId; // To be used in the AutocompleteRequest.
						$publishSystemIdFound = true;
					}
					break; // Found the widget to be verified, quit here.
				}
			}
		}
		if( !$termEntityFound ) {
			$this->setResult( 'ERROR', '"C_MCPSAMPLE_TOURISMCITIES" property is expected to have its TermEntity '.
				'returned in the Dialog widget but it was not found.Please check in the Dialog widget for "C_MCPSAMPLE_TOURISMCITIES".',
				$this->tipMsg );
			$result = false;
		}

		if( !$autocompleteProviderFound ) {
			$this->setResult( 'ERROR', '"C_MCPSAMPLE_TOURISMCITIES" property is expected to have its AutocompleteProvider '.
				'returned in the Dialog widget but it was not found.Please check in the Dialog widget for "C_MCPSAMPLE_TOURISMCITIES".',
				$this->tipMsg );
			$result = false;
		}

		if( !$publishSystemIdFound ) {
			$this->setResult( 'ERROR', '"C_MCPSAMPLE_TOURISMCITIES" property is expected to have its PublishSystemId  '.
				'returned with an empty value in the Dialog widget but it was not found.Please check in the Dialog '.
				'widget for "C_MCPSAMPLE_TOURISMCITIES".', $this->tipMsg );
			$result = false;
		}
		return $result;
	}

	/**
	 * Tears down the objects created in the {@link: setupAutocompleteTest()} function.
	 *
	 * @return bool Whether or not the teardown was successful.
	 */
	private function tearDownAutocompleteTest()
	{
		$result = true;

		// Release the lock of the form. (most likely already released during saveObjects call, but just to be sure.)
		if( $this->wflFormLocked ) {
			if( $this->unlockForm( $this->wflForm->MetaData->BasicMetaData->ID ) ) {
				$this->wflFormLocked = false;
			} else {
				$this->setResult( 'ERROR',  'Could not unlock the Form.', $this->tipMsg );
				$result = false;
			}
		}

		// Permanently delete the Publish Form.
		if( $this->wflForm ) {
			$id = $this->wflForm->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down the Publish Form object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflForm = null;
		}

		// Permanently delete the Dossier.
		if( $this->wflDossier ) {
			$id = $this->wflDossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down the Dossier object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflDossier = null;
		}

		return $result;
	}

	/**
	 * Locks or unlocks a given Publish Form.
	 *
	 * @param int $formId The object id for the Publish Form to unlock.
	 * @return bool Whether or not the unlock was successful.
	 */
	private function unlockForm( $formId )
	{
		// Unlock the Publish Form (by calling UnlockObjects).
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $formId );
		$stepInfo = 'Unlock the Publish Form.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return (bool)$response;
	}
}