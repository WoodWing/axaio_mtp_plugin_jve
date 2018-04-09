<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_WidgetInWidgetInWidget_TestCase extends TestCase
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
	private $wflImage1 = null;
	private $wflImage2 = null;
	private $wflImage3 = null;
	private $wflImage4 = null;
	private $wflCopiedDossierId = null;
	private $wflCopiedFormId = null;
	private $wflCopiedImage4Id = null;

	// Objects of RelationTest to setup/teardown:
	private $placedObjIdsInSequence = null;

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
	const WIDGET_ID = 'C_MCPSAMPLE_MULTI_IMAGES';
	
	public function getDisplayName()
	{
		return 'Widget In Widget In Widget';
	}

	public function getTestGoals()
	{
		return 'Checks if Widget In Widget In Widget works properly.';
	}

	public function getTestMethods()
	{
		return 'Performs CreateObjectRelations, GetDialog2, SaveObjects, GetObjects, UpdateObjectRelations, DeleteObjects to check if the data are round-tripped.';
	}

	public function getPrio()
	{
		return 45;
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
				$templateName = 'Widget in widget Template';
				$pubChannelIds = array( $pubChannelObj->Id, $this->pubChannel->Id );
				foreach( $pubChannelIds as $pubChannelId ) {
					if( !$this->getPublishFormTemplates( array( $templateName ), $pubChannelId, $this->templates ) ) {
						$continue = false;
						break;
					}
				}

				// Run the tests, for each Publish Form Template.
				if( $continue ) {
					$this->tipMsg = 'Error occurred while testing widget in widget in widget '.
										'in context of Publish Form Template "'.$templateName.'".';
					$this->runWiwiwTest( $templateName, $pubChannelObj );
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
	 * @param array $templates The tempate objects found. Two keys are used: $template[Name][PubChannelId] = Object
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
	 * Carries out several service calls by dealing with the placement FrameOrder and FormWidgetId.
	 *
	 * @param string $templateName The template name where the PublishForm will be initiated from.
	 * @param PubChannel pubChannelObj PubChannel object to get the correct template.
	 */
	private function runWiwiwTest( $templateName, $pubChannelObj )
	{
		if( $this->setupWiwiwTest( $templateName, $pubChannelObj ) ) {
			do {
				if( !$this->createObjectRelationsTest() ) { break; }

				$response = $this->getDialog2Test( 'SetPublishProperties', 'PublishForm' );
				if( !$response ) { break; }

				if( !$this->saveObjectTest( $response->MetaData, $response->Relations )) { break; }

				if( !$this->getObjectsTest() ) { break; }

				if( !$this->imagePlacedTwiceTest() ) { break; }

				if( !$this->copyDossierAndItsForm() ) { break; }

				if( !$this->copyImageOnTheForm() ) { break; }

				if( !$this->deleteObjectsTest() ) { break; }

			} while( false );
		}
		$this->tearDownWiwiwTest();
	}

	/**
	 * Creates several objects to prepare for the BuildTest.
	 *
	 * @param string $templateName The template name where the PublishForm will be initiated from.
	 * @param PubChannelInfo $pubChannelObj PubChannel object to get the correct template.
	 * @return bool True when all the necessary objects have been successfully created; False otherwise.
	 */
	private function setupWiwiwTest( $templateName, $pubChannelObj )
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
		$this->wflFormLocked = false;
		if( $this->wflDossier ) {
			$template = $this->templates[$templateName][$pubChannelObj->Id];
			$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
			$this->wflForm = $this->mcpUtils->createPublishFormObject( $template, $this->wflDossier, $stepInfo );
			if( is_null( $this->wflForm ) ) {
				$this->setResult( 'ERROR',  'Could not create the Publish Form.', $this->tipMsg );
				$retVal = false;
			}
		}

		// Create the Image1 object (to be placed later).
		$stepInfo = 'Create the first Image object.';
		$this->wflImage1 = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo );
		if( is_null( $this->wflImage1 ) ) {
			$this->setResult( 'ERROR',  'Could not create the first Image.', $this->tipMsg );
			$retVal = false;
		}

		// Create the Image2 object (to be placed later).
		$stepInfo = 'Create the second Image object.';
		$this->wflImage2 = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo );
		if( is_null( $this->wflImage2 ) ) {
			$this->setResult( 'ERROR', 'Could not create the 2nd Image.', $this->tipMsg );
		}

		// Create the Image3 object (to be placed later).
		$stepInfo = 'Create the third Image object.';
		$this->wflImage3 = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo );
		if( is_null( $this->wflImage3 ) ) {
			$this->setResult( 'ERROR', 'Could not create the 3rd Image.', $this->tipMsg );
		}

		// Create the Image4 object (to be placed later).
		$stepInfo = 'Create the third Image object.';
		$this->wflImage4 = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo );
		if( is_null( $this->wflImage4 ) ) {
			$this->setResult( 'ERROR', 'Could not create the 4th Image.', $this->tipMsg );
		}

		// Place the four images above into the Dossier. (Contained by Dossier)
		$stepInfo = '';
		$parentId = $this->wflDossier->MetaData->BasicMetaData->ID;

		$childId = $this->wflImage1->MetaData->BasicMetaData->ID;
		$this->mcpUtils->createRelationObject( $stepInfo, $parentId, $childId, 'Contained', null );

		$childId = $this->wflImage2->MetaData->BasicMetaData->ID;
		$this->mcpUtils->createRelationObject( $stepInfo, $parentId, $childId, 'Contained', null );

		$childId = $this->wflImage3->MetaData->BasicMetaData->ID;
		$this->mcpUtils->createRelationObject( $stepInfo, $parentId, $childId, 'Contained', null );

		$childId = $this->wflImage4->MetaData->BasicMetaData->ID;
		$this->mcpUtils->createRelationObject( $stepInfo, $parentId, $childId, 'Contained', null );

		return $retVal;
	}

	/**
	 * Performs a CreateObjectRelations call and validate its response.
	 *
	 * @return bool True when the response is fine; False otherwise.
	 */
	private function createObjectRelationsTest()
	{
		$retVal = true;
		// Get the WidgetId where we want to place the images on.
		$response = $this->callGetDialog2( 'SetPublishProperties', 'PublishForm' );
		if( !$response ) {
			$message = 'GetDialog2 service call was not successful and therefore it was not possible to get the FormWidgetId.'.
				'CreateObjectRelations test cannot be continued.';
			$this->setResult( 'ERROR', $message, $this->tipMsg );
			$retVal = false;
		}

		// Checks if the widget we want to use is in the returned GetDialog2 response.
		if( !$retVal ) {
			$widgetFound = false;
			foreach( $response->Dialog->Tabs as $tab ) {
				foreach( $tab->Widgets as $mainWidget ) {
					if( $mainWidget->PropertyInfo->Name == self::WIDGET_ID ) {
						$widgetFound = true;
						break; // Found the widget we are looking for, break here.
					}
				}
			}
			if( !$widgetFound ) {
				$message = 'GetDialog2 response did not return the widget "'.self::WIDGET_ID.'".'.
					'CreateObjectRelations test cannot be continued.';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
			}
		}


		if( $retVal ) {
			// Place images on the form's widget.
			if( $this->wflForm && $this->wflImage1 && $this->wflImage2 && $this->wflImage3 && $this->wflImage4 ) {
				$stepInfo = 'Place the first and second Image on the Publish Form.';
				$this->placedObjIdsInSequence = array( $this->wflImage4->MetaData->BasicMetaData->ID,
														$this->wflImage3->MetaData->BasicMetaData->ID,
														$this->wflImage2->MetaData->BasicMetaData->ID,
														$this->wflImage1->MetaData->BasicMetaData->ID );

				$composedRelations = array();
				foreach( $this->placedObjIdsInSequence as $frameOrderId => $placementObjId ) {
					$composedRelations[] = $this->mcpUtils->composePlacedRelation( $this->wflForm->MetaData->BasicMetaData->ID,
														$placementObjId, $frameOrderId, self::WIDGET_ID );
				}

				$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, $composedRelations );
				$retVal = $this->validatePlacementRelations( $relations, 'Placing images on the Form', 'CreateObjectRelations' );

			}
		}
		return $retVal;
	}

	/**
	 * Performs a getDialog2 service call and return its response.
	 * The response is not validated.
	 *
	 * @param string $action To be set on the GetDialog2 request.
	 * @param string $objType For error message when there's an error.
	 * @return WflGetDialog2Response|null
	 */
	private function callGetDialog2( $action, $objType )
	{
		// constructing getDialog2 request structure.
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket = $this->ticket;
		$request->Action = $action;
		$request->MetaData = array(
			new MetaDataValue( 'ID', null, array( new PropertyValue( $this->wflForm->MetaData->BasicMetaData->ID ) ) ),
			new MetaDataValue( 'Issue', null, array( new PropertyValue( $this->webIssueId ) ) )
		);

		// Call the getDialog2 service via soap call
		$stepInfo = 'Retrieve the SetPublishProperties dialog (through GetDialog2) for the '.$objType.' object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return $response;
	}

	/**
	 * Calls the getDialog2 service with the specific action and validates the response.
	 *
	 * @param string $action The action type for the getDialog2 service: 'SetPublishProperties'(built-in).
	 * @param string $objType
	 * @return WflGetDialog2Response|null The valid respionse. NULL on error or when not valid.
	 */
	private function getDialog2Test( $action, $objType )
	{
		$response = $this->callGetDialog2( $action, $objType );
		if( !$this->validatePlacementRelations( $response->Relations, 'SetPublishProperties in GetDialog', 'GetDialog2' ) ) {
			$response = null;
		}
		return $response;
	}

	/**
	 * To validate the Relations returned by several type of service calls.
	 *
	 * The FrameOrder and the FormWidgetId of the placement in the Placed Relation are validated.
	 *
	 * @param array $relations
	 * @param string $testCase Used for error message if there's any.
	 * @param string $serviceName Used for error message if there's any.
	 * @return bool True the validation was fine; False otherwise.
	 */
	private function validatePlacementRelations( $relations, $testCase, $serviceName )
	{
		$result = true;
		$placementObjCount = 0;
		do {
			if( !$relations ) {
				$message = 'Empty or no Relations returned which is not expected.Error occurred in test case "'.
									$testCase.'('.$serviceName.')".';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$result = false;
				break;
			}

			$frameOrders = array();
			foreach( $relations as $relation ) {
				if( $relation->Type == 'Placed' ) {
					foreach( $relation->Placements as $placement ) {
						$returnedFrameOrder = $placement->FrameOrder;
						// Make sure the FrameOrders returned are valid.
						if( !isset( $this->placedObjIdsInSequence[$returnedFrameOrder] ) ) {
							$message = 'FrameOrder "'.$returnedFrameOrder.'" returned for placement '.$relation->ChildInfo->Type.' ' .
								'"'.$relation->ChildInfo->Name.'"(id='.$relation->ChildInfo->ID.') is invalid.' .
								'It is not a FrameOrder for any placement objects.';
							$this->setResult( 'ERROR', $message, $this->tipMsg );
							$result = false;
							break;
						}

						$expectedPlacementObjId = $this->placedObjIdsInSequence[$returnedFrameOrder];
						// Make sure that each of the PlacementObject has correct FrameOrder.
						if( $relation->Child != $expectedPlacementObjId ) {
							$message = 'The returned FrameOrder "'.$returnedFrameOrder.'" for placement '.$relation->ChildInfo->Type.' ' .
								'"'.$relation->ChildInfo->Name.'"(id='.$relation->ChildInfo->ID.') is incorrect.' .
								'It belongs to FrameOrder for placement object id "'.$expectedPlacementObjId.'".';
							$this->setResult( 'ERROR', $message, $this->tipMsg );
							$result = false;
							break;
						}

						if( $placement->FormWidgetId != self::WIDGET_ID ) {
							$message = 'The FormWidgetId in the Relation Placement is expected to be "'.self::WIDGET_ID.'", ' .
								'"'.$placement->FormWidgetId .'" is returned in test case "'.$testCase.'('.$serviceName. ')" ';
							$this->setResult( 'ERROR', $message, $this->tipMsg );
							$result = false;
							break;
						}

						$placementObjCount++;
						$frameOrders[] = $placement->FrameOrder;
					}

				}
			}
			if( !$result ) {
				break;
			}
			// Make sure the total Placement Relations returned are correct.
			if( $placementObjCount != count( $this->placedObjIdsInSequence ) ) {
				$message = 'The total Placement Relations returned by the getDialog2 service call is not '.
					'the same as the total Placement Relations expected. "'. count( $this->placedObjIdsInSequence ).
					'" Placement Relations expected but "'.$placementObjCount.'" Placement Relations returned.';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$result = false;
				break;
			}

			// Make sure there's no duplicate FrameOrders.
			$uniqueFrameOrders = array_unique( $frameOrders );
			if( count( $uniqueFrameOrders ) != count( $frameOrders )) {
				$message = 'There are duplicates FrameOrder in the Placement relations, which is incorrect. '.
					'Please check in the "'.$serviceName.'" service call.';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$result = false;
				break;
			}
		} while( false );
		return $result;
	}


	/**
	 * Changes the FrameOrder of the placement images and saves the PublishForm.
	 *
	 * @param MetaData $metaData
	 * @param Relation[] $relations
	 * @return bool When the changes of placement order is saved correctly; False otherwise.
	 */
	private function saveObjectTest( $metaData, $relations )
	{
		$changedForm = $this->changePlacementsFrameOrderInForm( $metaData, $relations );
		if( !$this->lockForm( $metaData->BasicMetaData->ID ) ) {
			$retVal = false;
			$this->wflFormLocked = false;
			$this->setResult( 'ERROR', 'Cannot lock the PublishForm, SaveObjects service call cannot be carried out.',
					$this->tipMsg );
		} else {
			$this->wflFormLocked = true;

			// constructing saveObjects request structure.
			require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
			$request = new WflSaveObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->CreateVersion = true;
			$request->ForceCheckIn = false;
			$request->Unlock = true;
			$request->Objects = array( $changedForm );
			$request->ReadMessageIDs = null;
			$request->Messages = null;

			$stepInfo = 'Save the Publish Form.';
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( $response ) {
				$retVal = $this->validatePlacementRelations( $response->Objects[0]->Relations,
														'Reverse the placement objects in the widget', 'SaveObjects' );
			} else {
				$retVal = false;
			}

		}
		return $retVal;
	}

	/**
	 * Composes a Publish Form and:
	 *  - change the placement orders on the PublishForm.
	 *
	 * @param MetaData $metaData
	 * @param Relation[] $relations
	 * @return Object The composed Publish Form object with modifications.
	 */
	private function changePlacementsFrameOrderInForm( $metaData, $relations )
	{
		$this->placedObjIdsInSequence = array_reverse( $this->placedObjIdsInSequence ); // Change the Frame Order of the placement objects.
		// Compose a new Publish Form object.
		$object = new Object();
		$object->MetaData = $metaData;
		$object->Relations = $relations;
		$changedForm = unserialize( serialize( $object ) ); // deep clone

		// Change the FormWidgetId of the object relation: Publish Form - 1st Image.
		foreach( $changedForm->Relations as $iterRelation ) {
			if( $iterRelation->Type == 'Placed' ) {
				foreach( $this->placedObjIdsInSequence as $frameOrder => $placementObjId ) {
					if( $placementObjId == $iterRelation->Child ) {
						$iterRelation->Placements[0]->FrameOrder = $frameOrder;
						break;
					}
				}
			}
		}
		return $changedForm;
	}

	/**
	 * Locks a given Publish Form.
	 *
	 * @param int $formId The object id for the Publish Form to lock.
	 * @return bool Whether or not the lock was successful.
	 */
	private function lockForm( $formId )
	{
		// Lock the Publish Form (by calling GetObjects with lock).
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $formId );
		$request->Lock = true;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'MetaData' );
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		$stepInfo = 'Lock the Publish Form.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return (bool)$response;
	}

	/**
	 * Performs a GetObjects service call and validate its Relations in the response.
	 * @return bool Whether the GetObjects service call went fine.
	 */
	private function getObjectsTest()
	{
		$stepInfo = 'Retrieving the Publish Form.';
		$response = $this->mcpUtils->getObjects( $stepInfo, array($this->wflForm->MetaData->BasicMetaData->ID),
			false, 'none', array( 'Relations' ), null, array('Workflow') );
		$retVal = $this->validatePlacementRelations( $response->Objects[0]->Relations, 'Get PublishForm after saving the PublishForm', 'GetObjects' );
		return $retVal;
	}

	/**
	 * To place the same image twice within a widget and saves it.
	 * The response of the SaveObjects service call is validated.
	 *
	 * @return bool Whether the operations went successful.
	 */
	private function imagePlacedTwiceTest()
	{
		$retVal = true;
		$response = $this->callGetDialog2( 'SetPublishProperties', 'PublishForm' );
		if( !$response ) {
			$message = 'GetDialog2 service call was not successful and therefore it was not possible to get the widgets of '.
				'the PublishForm. Saving of PublishForm if not possible, testing on placement object placed twice cannot be continued.';
			$this->setResult( 'ERROR', $message, $this->tipMsg );
			$retVal = false;
		}

		if( $retVal ) {
			do {
				$metaData = $response->MetaData;
				$relations = $response->Relations;
				$changedForm = $this->placeSameImageTwiceOnForm( $metaData, $relations );
				if( is_null( $changedForm ) ) {
					$retVal = false;
					$message = 'Image1 cannot be placed twice in the Form. Testing on Placement object being placed twice ' .
						'cannot be continued.';
					$this->setResult( 'ERROR', $message, $this->tipMsg );
					break;
				}
				if( !$this->lockForm( $metaData->BasicMetaData->ID ) ) {
					$retVal = false;
					$this->wflFormLocked = false;
					$this->setResult( 'ERROR', 'Cannot lock the PublishForm, testing on same placement placed twice cannot be carried out.',
						$this->tipMsg );
					break;
				}

				$this->wflFormLocked = true;
				// constructing saveObjects request structure.
				require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
				$request = new WflSaveObjectsRequest();
				$request->Ticket = $this->ticket;
				$request->CreateVersion = true;
				$request->ForceCheckIn = false;
				$request->Unlock = true;
				$request->Objects = array( $changedForm );
				$request->ReadMessageIDs = null;
				$request->Messages = null;

				$stepInfo = 'Save the Publish Form.';
				$response = $this->utils->callService( $this, $request, $stepInfo );
				if( $response ) {
					$retVal = $this->validatePlacementRelations( $response->Objects[0]->Relations, 'Placing same object twice', 'SaveObjects' );
				} else {
					$retVal = false;
					break;
				}
			} while( false );
		}
		return $retVal;
	}

	/**
	 * To get an image and place it twice within a widget in a PublishForm.
	 * The updated relations are saved in the Database by calling UpdateObjectRelations service call.
	 * An updated PublishForm object is composed and return by this function.
	 *
	 * @param MetaData $metaData
	 * @param array $relations
	 * @return Object|null The updated PublishForm object with its placement's FrameOrder changed.
	 */
	private function placeSameImageTwiceOnForm( $metaData, $relations )
	{
		$changedForm = null;
		$firstImageId = $this->placedObjIdsInSequence[0]; // To be placed twice.
		array_push( $this->placedObjIdsInSequence, $firstImageId ); // Now image1 is placed twice in the widget.
		end( $this->placedObjIdsInSequence ); // Make sure the pointer is at the end of the array to get the FrameOrder for the image1 just added.
		$frameOrder = key( $this->placedObjIdsInSequence ); // Get the key(which is the FrameOrder) for image1 that is placed second time within a widget.

		foreach( $relations as $relation ) {
			if( $relation->Type == 'Placed' && $relation->Child == $firstImageId ) {
				$relation->Placements[] = $this->mcpUtils->composePlacementObject( $frameOrder, self::WIDGET_ID );
			}
		}

		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';
		$request = new WflUpdateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = unserialize( serialize( $relations ));
		$stepInfo = 'Update object Relations to place same image twice in the widget.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( is_null( $response )) {
			$message = 'UpdateObjectRelations service call has failed, image cannot be placed twice in the widget.' .
				'Testing on the same placement in one widget cannot be continued.';
			$this->setResult( 'ERROR', $message, $this->tipMsg );
		} else {
			$object = new Object();
			$object->MetaData = $metaData;
			$object->Relations = $relations;
			$changedForm = unserialize( serialize( $object ) ); // deep clone
		}

		return $changedForm;
	}

	/**
	 * Copies a Dossier that contains a PublishForm.
	 *
	 * Both the Dossier and its PublishForm should be copied(but is not the interest of this test).
	 * The test will focus on the the Relation of the copied PublishForm, the Relations are verified
	 * to ensure that the FrameOrder and FormWidgetId are correct.
	 *
	 * @return bool When the test runs successfully; False otherwise.
	 */
	private function copyDossierAndItsForm()
	{
		do {
			$dossierToBeCopied = unserialize( serialize( $this->wflDossier ) ); // Deep clone.
			$dossierToBeCopied->MetaData->BasicMetaData->Name = 'CopiedDossier'; // Give it a new name for the new to be copied Dossier.
			require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
			$request = new WflCopyObjectRequest();
			$request->Ticket = $this->ticket;
			$request->SourceID = $this->wflDossier->MetaData->BasicMetaData->ID;
			$request->MetaData = $dossierToBeCopied->MetaData;
			$request->Relations = $dossierToBeCopied->Relations;
			$request->Targets = $dossierToBeCopied->Targets;
			$stepInfo = 'Copy Dossier that contains a PublishForm.';
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( is_null( $response )) {
				$message = 'Cannot copy the Dossier(and its Form), the CopyObject test cannot be continued. ';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
				break;
			}

			// Get the Copied Dossier and Form for further testing and deletion later.
			$this->wflCopiedDossierId = $response->MetaData->BasicMetaData->ID;
			if( $response->Relations ) foreach( $response->Relations as $relation ) {
				if( $relation->Type == 'Contained' &&
					$relation->Parent == $this->wflCopiedDossierId &&
					$relation->ChildInfo->Type == 'PublishForm' ) {
					$this->wflCopiedFormId = $relation->Child; // Keep track of the child to delete it later.
					break;
				}
			}

			// Cannot find the CopiedDossier or CopiedForm, can't validate further, so bail out.
			if( !$this->wflCopiedDossierId || !$this->wflCopiedFormId ) {
				$message = 'Cannot retrieve the copiedDossier ID or the copied PublishForm ID, the CopyObject test cannot be continued. ';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
				break;
			}

			$copiedForm = $this->mcpUtils->getObjects( $stepInfo, array($this->wflCopiedFormId), false, 'none',
									array( 'Relations' ), null, array('Workflow') );
			$copiedFormRelations = $copiedForm->Objects[0]->Relations;
			$retVal = $this->validatePlacementRelations( $copiedFormRelations, 'Copying a Dossier and its PublishForm', 'GetObjects' );
		} while( false );

		return $retVal;
	}

	/**
	 * Copies an Image that is contained by a Dossier and placed on a PublishForm.
	 * The copied image is validated to ensure that there's no Placement relations copied over from the source image.
	 *
	 * @return bool When the test runs successfully; False otherwise.
	 */
	private function copyImageOnTheForm()
	{
		do {
			$retVal = true;
			$imageToBeCopied = unserialize( serialize( $this->wflImage4 ) ); // Deep clone.
			$imageToBeCopied->MetaData->BasicMetaData->Name = 'CopiedImage4'; // Give it a new name for the new to be copied Dossier.
			require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
			$request = new WflCopyObjectRequest();
			$request->Ticket = $this->ticket;
			$request->SourceID = $this->wflImage4->MetaData->BasicMetaData->ID;
			$request->MetaData = $imageToBeCopied->MetaData;
			$request->Relations = $imageToBeCopied->Relations;
			$request->Targets = $imageToBeCopied->Targets;
			$stepInfo = 'Copy Image4 that is contained by a Dossier and placed on PublishForm.';
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( is_null( $response )) {
				$message = 'Cannot copy Image4 that is placed on a PublishForm and contained by a Dossier. ';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
				break;
			}
			$this->wflCopiedImage4Id = $response->MetaData->BasicMetaData->ID; // For deletion later.
			if( $response->Relations ) { // Not expected.
				$message = 'The copied image4 contains Relations which is not expected. The relations of the source' .
					'Image should not be carried over to the copied object.';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
				break;
			}

		} while( false );

		return $retVal;
	}

	/**
	 * Delete an image that is placed on the Form's widget and perform a getObject of the Form.
	 * The relations returned by the GetObjects call is validated.
	 *
	 * @return bool Whether or not the operation was successful.
	 */
	private function deleteObjectsTest()
	{
		$retVal = null;

		$lastIndex = count( $this->placedObjIdsInSequence ) - 1;
		$firstOrderImage = $this->placedObjIdsInSequence[0];
		$lastOrderImage = $this->placedObjIdsInSequence[$lastIndex];

		if( $firstOrderImage == $lastOrderImage ) { // Just to make sure we delete the same placement object id.
			unset( $this->placedObjIdsInSequence[0] );
			unset( $this->placedObjIdsInSequence[$lastIndex] );
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->ticket;
			$request->IDs = array( $firstOrderImage );
			$request->Permanent = true;
			$request->Areas = array( 'Workflow' );
			$stepInfo = 'Deleting image1 that is placed on the first and last position within a widget.';
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( is_null( $response ) ) {
				$message = '';
				$this->setResult( 'ERROR', $message, $this->tipMsg );
				$retVal = false;
			} else {
				// Clear the memory for the image that has been deleted.
				switch( $firstOrderImage ) {
					case $this->wflImage1->MetaData->BasicMetaData->ID:
						$this->wflImage1 = null;
						break;
					case $this->wflImage2->MetaData->BasicMetaData->ID:
						$this->wflImage2 = null;
						break;
					case $this->wflImage3->MetaData->BasicMetaData->ID:
						$this->wflImage3 = null;
						break;
					case $this->wflImage4->MetaData->BasicMetaData->ID:
						$this->wflImage4 = null;
						break;
				}

				$stepInfo = 'Getting the PublishForm( GetObject ) after deleting one placement image that is placed on the Form.';
				$response = $this->mcpUtils->getObjects( $stepInfo, array($this->wflForm->MetaData->BasicMetaData->ID),
					false, 'none', array( 'Relations' ), null, array('Workflow') );
				$retVal = $this->validatePlacementRelations( $response->Objects[0]->Relations, 'Get the Form after deleting one placement object', 'GetObjects' );

			}
		} else { // Should not happen.
			$message = 'Deletion of PublishForm placement object was not able to be carried out. Please check in the DeleteObject TestCase.';
			$this->setResult( 'ERROR', $message, $this->tipMsg );
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Tears down the objects created in the {@link: setupWiwiwTest()} function.
	 *
	 * @return bool
	 */
	private function tearDownWiwiwTest()
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

		// Permanent delete Image1.
		if( $this->wflImage1 ) {
			$id = $this->wflImage1->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the 1st Image object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image1 object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflImage1 = null;
		}

		// Permanent delete Image2.
		if( $this->wflImage2 ) {
			$id = $this->wflImage2->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the 2nd Image object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image2 object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflImage2 = null;
		}

		// Permanent delete Image3.
		if( $this->wflImage3 ) {
			$id = $this->wflImage3->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Image3 object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image3 object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflImage3 = null;
		}

		// Permanent delete Image4.
		if( $this->wflImage4 ) {
			$id = $this->wflImage4->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Image4 object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image4 object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflImage4 = null;
		}

		// Permanent delete the Publish Form.
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

		// Permanent delete the Dossier.
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

		if( $this->wflCopiedImage4Id ) {
			$id = $this->wflCopiedImage4Id;
			$errorReport = '';
			$stepInfo = 'Tear down the copied Image4 object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down copied Image4 object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflCopiedImage4Id = null;
		}

		if( $this->wflCopiedFormId ) {
			$id = $this->wflCopiedFormId;
			$errorReport = '';
			$stepInfo = 'Tear down the copied Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down copied Form object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflCopiedFormId = null;
		}

		if( $this->wflCopiedDossierId ) {
			$id = $this->wflCopiedDossierId;
			$errorReport = '';
			$stepInfo = 'Tear down the copied Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down copied Dossier object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflCopiedDossierId = null;
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
