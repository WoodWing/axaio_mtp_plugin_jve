<?php
/**
 * @since      v9.1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_Suggestions_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils

	// Objects of WorkflowTest to setup/teardown:
	private $wflDossier = null;
	private $wflForm = null;
	private $wflFormLocked = false;

	// Admin entities to setup/teardown:
	private $pubChannel = null;
	private $issue = null;
	
	// Templates objects:
	private $templates = null; // imported by this test script
	private $foreignTemplates = null; // templates found that are -not- created by this script
	
	// Given admin entities to work:
	private $publicationId = null;
	private $webIssueId = null;

	private $tipMsg = null;

	const PUBLISH_PLUGIN = 'MultiChannelPublishingSample';
	const SUGGESTION_PROVIDER = 'OpenCalais';

	public function getDisplayName()
	{
		return 'Suggestion Providers';
	}

	public function getTestGoals()
	{
		return 'Checks if Suggestions service works correctly.';
	}

	public function getTestMethods()
	{
		return 'Performs Suggestions service call to check if the response are returned correctly.';
	}

	public function getPrio()
	{
		return 106;
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
		
		// Remember which templates are already in DB, before the script creates more templates.
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
					$this->tipMsg = 'Error occurred while testing Suggestions '.
										'in context of Publish Form Template "'.$templateName.'".';

					// Run the suggestion services.
					$this->runSuggestionsTest( $templateName, $pubChannelObj );
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
	 * @param int $pubChannelId
	 * @param array $templates The template objects found. Two keys are used: $template[Name][PubChannelId] = Object
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

			// Determine column indexes to work with
			$colNames = array( 'ID', 'Name' );
			$indexes = array();
			foreach( $colNames as $colName ) {
				foreach( $response->Columns as $index => $column ) {
					if( $column->Name == $colName ) {
						$indexes[$colName] = $index;
						break; // found
					}
				}
			}

			// Lookup the given Publish Form Template.
			foreach( $response->Rows as $row ) {
				$templateId = $row[$indexes['ID']];
				$templateName = $row[$indexes['Name']];

				// Retrieve the Publish Form Template from DB.
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
		$admPubChannel->PublishSystem = 'MultiChannelPublishingSample';
		$admPubChannel->SuggestionProvider = self::SUGGESTION_PROVIDER;
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
	 *  Imports the custom properties, Publish Form Templates and dialog of the server plug-in
	 *  named "MultiChannelPublishingSample".
	 *
	 * @return bool Whether or not the imports were successful.
	 */
	private function importDefinitions()
	{
		// Compose the application class name, for example: AdminWebAppsSample_App2_EnterpriseWebApp
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
	 * Remove the custom object properties, templates and dialog definitions of the
	 * MultiChannelPublishingSample plugin which were imported by {@link: importDefinitions()}.
	 *
	 * @return bool Whether or not the definitions could be removed.
	 */
	private function removeDefinitions()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmActionProperty.class.php';
		$retVal = true;
		$templateName = null;

		foreach( $this->templates as $templateName => $templates ) {
			foreach( $templates as $pubChannelId => $template ) {

				// Skip templates that are not ours.
				if( isset( $this->foreignTemplates[$templateName][$pubChannelId] ) ) {
					continue;
				}

				// Remove the Publish Form Template from DB.
				$errorReport = null;
				$id = $template->MetaData->BasicMetaData->ID;
				$stepInfo = 'Tear down Publish Form Template object.';
				if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
					$this->setResult( 'ERROR',  'Could not remove Publish Form Template '.
						'"'.$templateName.'" (assigned to pub channel id='.$pubChannelId.') '.
						'that was imported by plugin "'.self::PUBLISH_PLUGIN.'". '.$errorReport );
					$retVal = false;
				}

				// Remove the Dialog definition from DB (as imported for the template).
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
	 * Performs different kind of calls to test the Suggestions service.
	 *
	 * Currently only OpenCalais is tested.
	 *
	 * @param string $templateName The template name where the PublishForm will be initiated from.
	 * @param PubChannel pubChannelObj PubChannel object to get the correct template.
	 */
	private function runSuggestionsTest( $templateName, $pubChannelObj )
	{
		if( $this->setupSuggestionProviderTest( $templateName, $pubChannelObj ) ) {
			do {
				if( !$this->runGetDialog2ServiceTest() ) {
					break;
				}

				if( !$this->runOpenCalaisServiceTest() ) {
					break;
				}

			} while( false );
		}
		$this->tearDownSuggestionProviderTest();
	}

	/**
	 * Call getDialog2 service and validates its response.
	 *
	 * @return bool
	 */
	private function runGetDialog2ServiceTest()
	{
		require_once BASEDIR . '/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket   = $this->ticket;
		$request->Action   = 'SetPublishProperties';
		$request->MetaData = $this->composeReqMetaData( 'GetDialog2' );
		$request->Areas    = array( 'Workflow' );

		$stepInfo = 'Calling GetDialog2 service.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$result = $this->validateGetDialog2Resp( $response );
		return $result;
	}

	/**
	 * Validates getDialog2 service call response.
	 *
	 * Checks if the TermEntity for required widgets are set, and for those widgets, if the
	 * SuggestionProvider is set.
	 * Also checks if the refresh suggestion button is returned in the response.
	 *
	 * @param WflGetDialog2Response $response Response to be validated.
	 * @return bool
	 */
	private function validateGetDialog2Resp( $response )
	{
		$retVal = true;
		$citySuggestionProvider = false;
		$countrySuggestionProvider = false;
		$foundRefreshSuggestionButton = false;

		do {
			if( !isset( $response->Dialog->Tabs )) {
				$this->setResult( 'ERROR', 'GetDialog2->Dialog->Tabs is empty in getDialog2 response , which is not '.
					'expected. Please check in the GetDialog2 service call.', $this->tipMsg );
				$retVal = false;
				break;
			}

			// These widgets should have TermEntity set.
			// <Widget property name> => <TermEntity>
			$widgetsWithTermEntitySet = array( 'C_MCPSAMPLE_TOURISMCITIES' => 'City',
											   'C_MCPSAMPLE_COUNTRIES' => 'Country' );
			foreach( $response->Dialog->Tabs as $tab ) {
				if( $tab->Title == 'GeneralFields' ) {
					if( $tab->Widgets ) foreach( $tab->Widgets as $widget ) {
						$widgetPropertyName = $widget->PropertyInfo->Name;

						if( array_key_exists( $widgetPropertyName, $widgetsWithTermEntitySet )) {
							$expectedTermEntity = $widgetsWithTermEntitySet[$widgetPropertyName];
							// Checks for widget's TermEntity
							if( $widget->PropertyInfo->TermEntity != $expectedTermEntity ) {
								$message = 'The expected TermEntity for widget "'.$widgetPropertyName.'" is '.
									'"'.$expectedTermEntity.'", but "'.$widget->PropertyInfo->TermEntity.'" is returned,'.
									'which is wrong. Please check in GetDialog2 service call.';
								$this->setResult( 'ERROR', $message, $this->tipMsg );
								$retVal = false;
								break;
							}

							// Checks for widget's SuggestionProvider.
							if( $widget->PropertyInfo->SuggestionProvider != self::SUGGESTION_PROVIDER ) {
								$message = 'The SuggestionProvider is not set for widget "'.$widgetPropertyName.
									'" which is wrong, please check in the GetDialog2 response.';
								$this->setResult( 'ERROR', $message, $this->tipMsg );
								$retVal = false;
								break;
							}

							// Checks if the required widgets are returned in the getDialog2 response.
							if( $widgetPropertyName == 'C_MCPSAMPLE_TOURISMCITIES' ) {
								$citySuggestionProvider = true;
							} else if( $widgetPropertyName == 'C_MCPSAMPLE_COUNTRIES' ) {
								$countrySuggestionProvider = true;
							}
						}
					}
				}
			}

			foreach( $response->Dialog->ButtonBar as $buttonBar ) {
				if( $buttonBar->PropertyInfo->Name == 'RefreshSuggestions' ) {
					$foundRefreshSuggestionButton = true;
					if( !$buttonBar->PropertyInfo->Notifications ) {
						$message = '"Notifications" is not returned in the getDialog2 ButtonBar->PropertyInfo which is invalid.' .
							'Please check in the getDialog2 service call.';
						$this->setResult( 'ERROR', $message, $this->tipMsg );
						$retVal = false;
						break 2; // break foreach loop and do-while loop.
					}

					foreach( $buttonBar->PropertyInfo->Notifications as $notification ) {
						if( $notification->Type != 'Info' ) {
							$message = 'Notification Type is not set to "Info" in the getDialog2 ButtonBar->PropertyInfo '.
								'which is invalid. Please check in the getDialog2 service call.';
							$this->setResult( 'ERROR', $message, $this->tipMsg );
							$retVal = false;
							break 2; // break foreach loop and do-while loop.
						}
						if( $notification->Message != 'Refresh all suggestions' ) {
							$message = 'Notification Message is not set to "Refresh all suggestions" in the getDialog2 ' .
								'ButtonBar->PropertyInfo which is invalid. Please check in the getDialog2 service call.';
							$this->setResult( 'ERROR', $message, $this->tipMsg );
							$retVal = false;
							break 2; // break foreach loop and do-while loop.
						}
					}

					break; // break the most outer foreach loop only.
				}
			}

		} while( false );

		if( $retVal ) { // Only validate further when there's no error so far.
			// Error when the required widgets are not returned.
			if( !$citySuggestionProvider ) {
				$message = 'C_MCPSAMPLE_TOURISMCITIES is not returned in the getDialog widgets, which is incorrect.' .
					'Please check in the getDialog2 service call.';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
			}
			if( !$countrySuggestionProvider ) {
				$message = 'C_MCPSAMPLE_COUNTRIES is not returned in the getDialog widgets, which is incorrect.' .
					'Please check in the getDialog2 service call.';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
			}

			// Error when required button is not returned.
			if( !$foundRefreshSuggestionButton ) {
				$message = 'RefreshSuggestions button is not returned in the getDialog2->ButtonBar, which is incorrect.'.
					'Please check in the getDialog2 service call.';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Calls Suggestions service and validate its response.
	 *
	 * This test case depends on a connection to OpenCalais, and relies on an API key. If the API key
	 * is not already set, then it will attempt to set the API Key automatically. OpenCalais allows up
	 * to 50000 requests a day in this manner, theoretically the key therefore could become invalid.
	 *
	 * Furthermore this case expects a set return value from OpenCalais. This test therefore is vulnerable
	 * to any changes in the OpenCalais webservices.
	 *
	 * @return bool Whether or not the test was succesful.
	 */
	private function runOpenCalaisServiceTest()
	{
		// Setup the API Key in the database if needed.
		require_once BASEDIR . '/server/plugins/OpenCalais/OpenCalais.class.php';
		if (is_null( OpenCalais::getApiKey() ) ) {
			OpenCalais::storeApiKey( 'pse4kjz38chsrcxtqefwskwt' );
		}

		require_once BASEDIR . '/server/services/wfl/WflSuggestionsService.class.php';
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		$request = new WflSuggestionsRequest();
		$request->Ticket               = $this->ticket;
		$request->SuggestionProvider   = DBChannel::getSuggestionProviderByChannelId( $this->pubChannel->Id );
		$request->ObjectId             = $this->wflForm->MetaData->BasicMetaData->ID;
		$request->MetaData             = $this->composeReqMetaData( 'Suggestions' );
		$request->SuggestForProperties = $this->composeOpenCalaisSuggestForProperties();

		$stepInfo = 'Calling Suggestions service.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		$result = $this->validateOpenCalaisSuggestions( $response );
		return $result;
	}

	/**
	 * Validates the OpenCalais response.
	 *
	 * Returns if the expected results for the suggestedProperties are present and contain the correct values.
	 *
	 * @param WflSuggestionsResponse $response The suggestions service response to be validated.
	 * @return bool Whether or not the suggestionsservice response is valid.
	 */
	private function validateOpenCalaisSuggestions( $response )
	{
		require_once BASEDIR . '/server/services/wfl/WflSuggestionsService.class.php';
		$facilityIsValid = false;
		$naturalFeatureIsValid = false;

		if ( $response instanceof WflSuggestionsResponse ) {
			if ($response->SuggestedTags) foreach ($response->SuggestedTags as $entityTags ) {
				if ( $entityTags->TermEntity == 'Facility' ) {
					$facilityIsValid = ( isset($entityTags->Tags[0]) && strval($entityTags->Tags[0]->Value) == 'Noordzee canal');

					if (!$facilityIsValid) {
						$this->setResult( 'ERROR',  'The OpenCalais Response did not match our expected result set '
							. '`Facility` did not match the expected value `Noordzee canal`, instead value `'
							. strval($entityTags->Tags[0]->Value) . '` was returned. ', $this->tipMsg );
					}

				} else if ( $entityTags->TermEntity == 'NaturalFeature' ) {
					$naturalFeatureIsValid = ( isset($entityTags->Tags[0]) && strval($entityTags->Tags[0]->Value) == 'Amstel river');

					if (!$naturalFeatureIsValid) {
						$this->setResult( 'ERROR',  'The OpenCalais Response did not match our expected result set '
							. '`NaturalFeature` did not match the expected value `Amstel river`, instead value `'
							. strval($entityTags->Tags[0]->Value) . '` was returned. ', $this->tipMsg );
					}
				}
			}
		} else {
			$this->setResult( 'ERROR',  'The OpenCalais Response was malformed or empty. ', $this->tipMsg );
		}

		return ($facilityIsValid && $naturalFeatureIsValid);
	}

	/**
	 * Creates several objects to prepare for the BuildTest.
	 *
	 * @param string $templateName The template name where the PublishForm will be initiated from.
	 * @param PubChannelInfo $pubChannelObj PubChannel object to get the correct template.
	 * @return bool True when all the necessary objects have been successfully created; False otherwise.
	 */
	private function setupSuggestionProviderTest( $templateName, $pubChannelObj )
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
	 * Compose a list of MetaDataValue object.
	 *
	 * @param string $serviceName
	 * @return MetaDataValue[]
	 */
	private function composeReqMetaData( $serviceName )
	{
		$metaDataValues = array();
		switch( $serviceName ) {
			case 'GetDialog2':
				// ID
				$mdValue= new MetaDataValue();
				$mdValue->Property = 'ID';
				$mdValue->Values = null;
				$propValue = new PropertyValue();
				$propValue->Value = $this->wflForm->MetaData->BasicMetaData->ID;
				$mdValue->PropertyValues = array( $propValue );
				$metaDataValues[] = $mdValue;

				// Issue
				$mdValue= new MetaDataValue();
				$mdValue->Property = 'Issue';
				$mdValue->Values = null;
				$propValue = new PropertyValue();
				$propValue->Value = $this->issue->Id;
				$mdValue->PropertyValues = array( $propValue );
				$metaDataValues[] = $mdValue;

				// Type
				$mdValue= new MetaDataValue();
				$mdValue->Property = 'Type';
				$mdValue->Values = null;
				$propValue = new PropertyValue();
				$propValue->Value = 'PublishForm';
				$mdValue->PropertyValues = array( $propValue );
				$metaDataValues[] = $mdValue;

				break;
			case 'Suggestions':
				$mdValue= new MetaDataValue();
				$mdValue->Property = 'C_MCPSAMPLE_STRING';
				$mdValue->Values = array( 'Facts about Amsterdam' );
				$propValue = new PropertyValue();
				$propValue->Value = 'Facts about Amsterdam';
				$mdValue->PropertyValues = array( $propValue );
				$metaDataValues[] = $mdValue;

				$mdValue= new MetaDataValue('The Amstel river and the Noordzee canal run through this city.' );
				$mdValue->Property = 'C_MCPSAMPLE_MULTILINE';
				$mdValue->Values = array( );
				$propValue = new PropertyValue();
				$propValue->Value = 'The Amstel river and the Noordzee canal run through this city.';
				$mdValue->PropertyValues = array( $propValue );
				$metaDataValues[] = $mdValue;
				break;
		}
		return $metaDataValues;
	}

	/**
	 * Compose a list of AutoSuggestProperty for OpenCalais.
	 *
	 * return AutoSuggestProperty[]
	 */
	private function composeOpenCalaisSuggestForProperties()
	{
		$autoSuggestProps = array();
		$prop = new AutoSuggestProperty();
		$prop->Name = 'C_MCPSAMPLE_TOURISMCITIES';
		$prop->Entity = 'Facility';
		$prop->IgnoreValues = null;
		$autoSuggestProps[] = $prop;

		$prop = new AutoSuggestProperty();
		$prop->Name = 'C_MCPSAMPLE_COUNTRIES';
		$prop->Entity = 'NaturalFeature';
		$prop->IgnoreValues = null;
		$autoSuggestProps[] = $prop;

		return $autoSuggestProps;
	}


	/**
	 * Tears down the objects created in the {@link: setupSuggestionProviderTest()} function.
	 *
	 * @return bool
	 */
	private function tearDownSuggestionProviderTest()
	{
		$result = true;

		// release the lock of the form. (most likely already released during saveObjects call, but just to be sure.)
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
				$this->setResult( 'ERROR', 'Could not tear down Publish Form object: '.$errorReport, $this->tipMsg );
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
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport, $this->tipMsg );
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