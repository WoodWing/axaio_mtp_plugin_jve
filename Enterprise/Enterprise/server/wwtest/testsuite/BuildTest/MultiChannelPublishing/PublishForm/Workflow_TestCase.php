<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_Workflow_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils
	private $buildTestSessionRow = null;
	
	// Objects of WorkflowTest to setup/teardown:
	private $wflDossier = null;
	private $wflForm = null;
	private $wflFormLocked = false;
	private $wflImage1 = null;
	private $wflImage2 = null;
	private $wflArticle = null;
	private $formForNewPubChannel = null;
	
	// Objects of RelationTest to setup/teardown:
	private $relDossier1 = null;
	private $relDossier2 = null;
	private $relForm = null;
	
	// Admin entities to setup/teardown:
	private $pubChannel = null;
	private $issue = null;
	
	// Templates objects:
	private $templates = null; // imported by this test script
	private $foreignTemplates = null; // templates found that are -not- created by this script
	
	// Given admin entities to work:
	private $publicationId = null;
	private $webIssueId = null;

	private $changedFormWidgetId = 'CHANGED_FORMWIDGETID';
	private $placementImgIdOfChangedFormWidget = null; // To remember which placement where the formWidget is changed.
	private $tipMsg = null;
	
	const PUBLISH_PLUGIN = 'MultiChannelPublishingSample';
	
	public function getDisplayName()
	{
		return 'Publish Workflow';
	}

	public function getTestGoals()
	{
		return 'Checks if PublishFormWorkflow can be performed correctly.';
	}

	public function getTestMethods()
	{
		return 'Performs GetDialog2 with action set to "SetPublishProperties,CheckIn,Create,CopyTo", SaveObjects and GetObjects to check if the data are round-tripped.';
	}

	public function getPrio()
	{
		return 40;
	}

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
				$templateNames = array(
					'All Widgets Sample Template',
					'File Selector Template',
					'Article Component Selector Template' );
				$pubChannelIds = array( $pubChannelObj->Id, $this->pubChannel->Id );
				foreach( $pubChannelIds as $pubChannelId ) {
					if( !$this->getPublishFormTemplates( $templateNames, $pubChannelId, $this->templates ) ) {
						$continue = false;
						break;
					}
				}

				// Run the tests, for each Publish Form Template.
				if( $continue ) {
					foreach( $templateNames as $templateName ) {
		
						$this->tipMsg = 'Error occurred while testing dossier-form relations '.
										'in context of Publish Form Template "'.$templateName.'".';
						$this->runRelationTest( $templateName, $pubChannelObj );
		
						$this->tipMsg = 'Error occurred while testing workflow operations '.
										'in context of Publish Form Template "'.$templateName.'".';
						$this->runWorkflowTest( $templateName, $pubChannelObj );
					}
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
	 * Requests all kind of workflow dialogs (GetDialog2) for the template, form and article.
	 * The responses are validated.
	 *
	 * @param string $templateName
	 * @param PubChannelInfo $pubChannelObj
	 */
	private function runWorkflowTest( $templateName, $pubChannelObj )
	{
		if( $this->setupWorkflowTest( $templateName, $pubChannelObj ) ) {
			do {
				$template = $this->templates[$templateName][$pubChannelObj->Id];
				$response = $this->callGetDialog2Service( $template, 'SetPublishProperties', 'PublishForm', $pubChannelObj );
				if( !$response ) { break; }
				if( !$this->callSaveObjectsService( $response->MetaData, $response->Relations ) ) { break; }
				if( !$this->retrieveFormAndValidateRelations() ) { break; }

				// Test if Targets, Editions, Issue/Issues, etc are returned correctly...

				// PublishFormTemplate
				if( !$this->callGetDialog2Service( $template, 'Create', 'PublishFormTemplate' ) )  { break; }
				if( !$this->callGetDialog2Service( $template, 'CopyTo', 'PublishFormTemplate' ) )  { break; }
				if( !$this->callGetDialog2Service( $template, 'SetProperties', 'PublishFormTemplate' ) ) { break; }
				if( !$this->callGetDialog2Service( $template, 'CheckIn', 'PublishFormTemplate' ) ) { break; }

				// PublishForm
				if( !$this->callGetDialog2Service( $template, 'Create', 'PublishForm' ) )        { break; }
				if( !$this->callGetDialog2Service( $template, 'CopyTo', 'PublishForm' ) )        { break; }
				if( !$this->callGetDialog2Service( $template, 'SetProperties', 'PublishForm' ) ) { break; }
				if( !$this->callGetDialog2Service( $template, 'CheckIn', 'PublishForm' ) )       { break; }

				// Test on a non-publish form/ template
				if( !$this->callGetDialog2Service( $template, 'Create', 'Article' ) )        { break; }
				if( !$this->callGetDialog2Service( $template, 'CopyTo', 'Article' ) )        { break; }
				if( !$this->callGetDialog2Service( $template, 'SetProperties', 'Article' ) ) { break; }
				if( !$this->callGetDialog2Service( $template, 'CheckIn', 'Article' ) )       { break; }

				// Test on Dossier that targeted to PubChannel that supports PublishForm.
				$this->fakeTheClient();
				if( !$this->callGetDialog2Service( $template, 'Create', 'Dossier' ) )  { break; }
				if( !$this->callGetDialog2Service( $template, 'CopyTo', 'Dossier' ) )  { break; }
				if( !$this->callGetDialog2Service( $template, 'SetProperties', 'Dossier' ) )  { break; }

				// Test on Dossier that targeted to one Print channel and one channel that supports PublishForm.
				$originalDosserTargets = unserialize( serialize( $this->wflDossier->Targets ));
				if( $this->assignPrintTarget() ) { // Add one more Target to the Dossier, the PubChannel added doesn't support PublishForm.
					if( !$this->callGetDialog2Service( $template, 'Create', 'Dossier' ) )  { break; }
					if( !$this->callGetDialog2Service( $template, 'CopyTo', 'Dossier' ) )  { break; }
					if( !$this->callGetDialog2Service( $template, 'SetProperties', 'Dossier' ) )  { break; }
					if( !$this->restoreOriginalTarget( $originalDosserTargets ) )  {
						$this->setResult( 'ERROR', 'Failed restoring the original Targets to Dossier.Test cannot be continued.' );
						break;
					}
				} else {
					$this->setResult( 'ERROR', 'Failed assigning Print Target to Dossier. GetDialog2 service call for ' .
						'Dossier with Print Target cannot be tested.' );
					// Don't have to break here as the test can be continued.
				}
				$this->restoreTheBuildTestClient();

				// Testing for Object Relations
				$this->testPublishFormRelations( $templateName, $pubChannelObj );

			} while( false );
		}
		$this->tearDownWorkflowTest( );
	}

	/**
	 * Sets up the test structure. Creates states, dossier and publish form (templates).
	 *
	 * @param string $templateName
	 * @param PubChannelInfo $pubChannelObj
	 * @return bool
	 */
	private function setupWorkflowTest( $templateName, $pubChannelObj )
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
			} else {
				// Lock the Publish Form.
				if( $this->lockForm( $this->wflForm->MetaData->BasicMetaData->ID ) ) {
					$this->wflFormLocked = true;
				} else {
					// Error is already reported in lockForm().
					$retVal = false;
				}
			}
		}
		
		// Create the Image object (to be placed later).
		$stepInfo = 'Create the first Image object.';
		$this->wflImage1 = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo );
		if( is_null( $this->wflImage1 ) ) {
			$this->setResult( 'ERROR',  'Could not create the first Image.', $this->tipMsg );
			$retVal = false;
		}

		// Place the first image on the form.
		if( $this->wflForm && $this->wflImage1 ) {
			$stepInfo = 'Place the first Image on the Publish Form.';
			$composedRelation = $this->mcpUtils->composePlacedRelation( $this->wflForm->MetaData->BasicMetaData->ID,
												$this->wflImage1->MetaData->BasicMetaData->ID, 0, null );
			$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ) );
			if( is_null( $relations ) || !isset( $relations[0] )) {
				$this->setResult( 'ERROR',  'Could not place the first Image on the Publish Form.', $this->tipMsg );
				$retVal = false;
			}
		}
		
		// Create the Article.
		$stepInfo = 'Create an Article object.';
		$this->wflArticle = $this->mcpUtils->createArticle( $stepInfo );
		if( is_null( $this->wflArticle ) ) {
			$this->setResult( 'ERROR',  'Could not create the Article.', $this->tipMsg );
			$retVal = false;
		}

		return $retVal;
	}

	/**
	 * Tears down the objects created in the {@link: setupWorkflowTest()} function.
	 *
	 * @return bool
	 * @throws BizException
	 */
	private function tearDownWorkflowTest( )
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
		
		// Permanent delete the 1st Image.
		if( $this->wflImage1 ) {
			$id = $this->wflImage1->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the 1st Image object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down the 1st Image object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflImage1 = null;
		}
		
		// Permanent delete the 2nd Image.
		if( $this->wflImage2 ) {
			$id = $this->wflImage2->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the 2nd Image object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down the 2nd Image object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflImage2 = null;
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
		
		// *****  STARTS: Delete Forms created for PublishFormRelations testing ******/
		// To delete the second Form that was partially successfully created. (The Form was created but it failed
		// at the CreateObjectRelations, so there was no Object Id being returned from the CreateObject service.)
		// Therefore, has to retrieve the object via the Form name.
		$formToTestRelationsName = $this->wflDossier->MetaData->BasicMetaData->Name;
		$sth = DBObject::checkNameObject( $this->publicationId, 0, $formToTestRelationsName, 'PublishForm' );
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBObject::getError() );
		}
		$dbdriver = DBDriverFactory::gen();
		while( ($row = $dbdriver->fetch($sth)) ) { // Delete all -orphaned- PublishForm that was partially successfully created.
			if( isset( $row['id']) && $row['id'] != $this->formForNewPubChannel->MetaData->BasicMetaData->ID ) {
				$errorReport = null;
				$stepInfo = 'Tear down the Publish Form object.';
				$ok = $this->mcpUtils->deleteObject(  $row['id'], $stepInfo, $errorReport );
				if (!$ok) {
					$this->setResult( 'ERROR',  'Could not remove the Publish Form that used for Relations testing. '.$errorReport, $this->tipMsg );
				}
			}
		}
		if( $this->formForNewPubChannel ) {
			$errorReport = null;
			$id = $this->formForNewPubChannel->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not remove the Publish Form created for Form Relations testing. '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->formForNewPubChannel = null;
		}
		// *****  END: Delete Forms created for PublishFormRelations testing ****** /

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

		// Permanent delete the Article.
		if( $this->wflArticle ) {
			$id = $this->wflArticle->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Article object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport, $this->tipMsg );
				$result = false;
			}
			$this->wflArticle = null;
		}
		
		return $result;
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
					require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
					$request = new WflGetObjectsRequest();
					$request->Ticket = $this->ticket;
					$request->IDs = array( $templateId );
					$request->Lock = false;
					$request->Rendition = 'none';
					$request->RequestInfo = null;
					$request->HaveVersions = null;
					$request->Areas = null;
					$request->EditionId = null;
					$stepInfo = 'Retrieving a Publish Form Template.';
					$response = $this->utils->callService( $this, $request, $stepInfo );
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
	 * To test the relation between Dossier and its Form.
	 *
	 * Form is only allowed to be contained by One Dossier.
	 * This test tries to create a Form in first Dossier(allowed),
	 * and tries to place this same Form into second Dossier(failed)
	 * by calling SaveObjects, CreateObjectRelations and UpdateObjectRelations
	 * service calls.
	 * CreateObjects and CopyObject are not tested as the Form has not been created
	 * yet, therefore these two service calls are not applicable for this test.
	 *
	 * @TODO Test with RestoreObjects service call when applicable.
	 * @param string $templateName
	 * @param PubChannelInfo $pubChannelObj
	 */
	private function runRelationTest( $templateName, $pubChannelObj )
	{
		$tipMsg = 'Error occurred while testing Publish Form Template "'.$templateName.'".';
		$template = null;
		
		if( $this->setupRelationTest( $templateName, $pubChannelObj, $tipMsg ) ) {
			do {
				// Note: At this point, the Publish Form is already assigned to the 1st Dossier.
				$template = $this->templates[$templateName][$pubChannelObj->Id];
				
				// Try to move that form into the 2nd Dossier => All service calls should fail.
				$relation = new Relation();
				$relation->Parent  = $this->relDossier2->MetaData->BasicMetaData->ID;
				$relation->Child   = $this->relForm->MetaData->BasicMetaData->ID; // Try to insert the Form created into another Dossier.
				$relation->Type    = 'Contained';
				$relation->Targets = array( $template->Targets[0] );
	
				// GetObjects and lock.
				$this->relForm->Relations[] = $relation;
				$stepInfo = 'Retrieving the Publish Form with lock.';
				if( !$this->getAndLockObjectForDossierFormRelationTest( $this->relForm, $stepInfo ) ) {
					$this->setResult( 'ERROR', 'Could not retrieve and lock the Publish Form.', $tipMsg );
					break; // abort this test
				}
	
				// SaveObjects and unlock.
				$stepInfo = 'Try to move Publish Form into 2nd Dossier through SaveObjects service, which should fail.';
				if( $this->unlockAndSaveObjectForDossierFormRelationTest( $this->relForm, $stepInfo, '(S1000)' ) ) { // Should fail!
					$this->setResult( 'ERROR', 'Two same Forms were allowed to placed in two different dossiers '.
						'during SaveObjects service call, which is wrong! ' .
						'Each Form can only be contained in one Dossier.', $tipMsg );
					break; // abort this test
				}
				
				// The Publish Form could not be unlocked in the above SaveObjects call.
				$this->unlockForm( $this->relForm->MetaData->BasicMetaData->ID );
	
				// CreateObjectRelations
				$stepInfo = 'Try to move Publish Form into 2nd Dossier through CreateObjectRelations service, which should fail.';
				if( $this->callCreateObjectRelationsService( array( $relation ), $stepInfo, '(S1000)' ) ) { // Should fail!
					$this->setResult( 'ERROR', 'Two same Forms were allowed to placed in two different dossiers '.
						'during CreateObjectRelations service call, which is wrong! ' .
						'Each Form can only be contained in one Dossier.', $tipMsg );
					break; // abort this test
				}
				
				// UpdateObjectRelations
				$stepInfo = 'Try to move Publish Form into 2nd Dossier through UpdateObjectRelations service, which should fail.';
				if( $this->callUpdateObjectRelationsService( array( $relation ), $stepInfo, '(S1000)' ) ) { // Should fail!
					$this->setResult( 'ERROR', 'Two same Forms were allowed to placed in two different dossiers '.
						'during UpdateObjectRelations service call, which is wrong! ' .
						'Each Form can only be contained in one Dossier.', $tipMsg );
					break; // abort this test
				}
			} while( false );
		}
		$this->tearDownRelationTest( $tipMsg );
	}

	/**
 	 * Creates a Publish Form and two Dossiers. Assigns the form to the 1st dossier.
	 *
	 * @param string $templateName
	 * @param PubChannelInfo $pubChannelObj
	 * @param string $tipMsg To be used in the error message if there's any error.
	 * @return bool Whether or not the setup was successful.
	 */
	private function setupRelationTest( $templateName, $pubChannelObj, $tipMsg )
	{
		$retVal = true;
		
		// Compose postfix for dossier names.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		
		// Create the 1st Dossier.
		$stepInfo = 'Create the first Dossier object.';
		$this->relDossier1 = $this->mcpUtils->createDossier( $stepInfo, 'FirstDossier '.$postfix );
		if( is_null( $this->relDossier1 ) ) {
			$this->setResult( 'ERROR',  'Could not create the 1st Dossier.', $tipMsg );
			$retVal = false;
		}

		// Create the 2nd Dossier.
		$stepInfo = 'Create the second Dossier object.';
		$this->relDossier2 = $this->mcpUtils->createDossier( $stepInfo, 'SecondDossier '.$postfix );
		if( is_null( $this->relDossier2 ) ) {
			$this->setResult( 'ERROR',  'Could not create the 2nd Dossier.', $tipMsg );
			$retVal = false;
		}

		// Create the Publish Form and assign it to the 1st Dossier (FirstPublishFormDossier).
		if( $this->relDossier1 ) {
			$template = $this->templates[$templateName][$pubChannelObj->Id];
			$stepInfo = 'Create the Publish Form object and assign to the first Dossier.';
			$this->relForm = $this->mcpUtils->createPublishFormObject( $template, $this->relDossier1, $stepInfo );
			if( is_null( $this->relForm ) ) {
				$this->setResult( 'ERROR', 'The Publish Form could not be created in the first Dossier.', $tipMsg );
				$retVal = false;
			}
		}
		
		return $retVal;
	}

	/**
	 * Tears down the objects created in the {@link: setupRelationTest} function.
	 *
	 * @param string $tipMsg
	 */
	private function tearDownRelationTest( $tipMsg )
	{
		// Remove the Publish Form.
		if( !is_null( $this->relForm ) ) {
			$errorReport = null;
			$id = $this->relForm->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not tear down the Publish Form.'.$errorReport, $tipMsg );
			}
			$this->relForm = null;
		}

		// Remove the 1st Dossier.
		if( !is_null( $this->relDossier1 ) ) {
			$errorReport = null;
			$id = $this->relDossier1->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down 1st Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not tear down the 1st Dossier.'.$errorReport, $tipMsg );
			}
		}

		// Remove the 2nd Dossier.
		if( !is_null( $this->relDossier2 ) ) {
			$errorReport = null;
			$id = $this->relDossier2->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down 2nd Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not tear down the 2nd Dossier.'.$errorReport, $tipMsg );
			}
		}
	}

	/**
	 * Composes a Publish Form and:
	 *  - modifies the FormWidgetId of the object relation: Publish Form - 1st Image.
	 *  - places a newly created Image object on the form.
	 * 
	 * @param MetaData $metaData
	 * @param Relation[] $relations
	 * @return Object The composed Publish Form object with some modifications.
	 */
	private function makeChangesToTheForm( $metaData, $relations )
	{
		// Compose a new Publish Form object.
		$object = new Object();
		$object->MetaData = $metaData;
		$object->Relations = $relations;
		$changedForm = unserialize( serialize( $object ) ); // deep clone

		// Change the FormWidgetId of the object relation: Publish Form - 1st Image.
		foreach( $changedForm->Relations as $iterRelation ) {
			$iterRelation->Geometry = null; // TODO: Currently service validator is raising error on empty Geometry. Geometry should be null, to be investigated.
			if( $iterRelation->Type == 'Placed' && $iterRelation->Child == $this->wflImage1->MetaData->BasicMetaData->ID ) { // try to adjust the placement image1
				$this->placementImgIdOfChangedFormWidget = $iterRelation->Child; // remember the image where the formWidget is changed.				
				$iterRelation->Placements[0]->FormWidgetId = $this->changedFormWidgetId; // modify the formWidgetId
			}
		}

		// Create the 2nd Image.
		$stepInfo = 'Create the second Image object.';
		$this->wflImage2 = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo );
		if( is_null( $this->wflImage2 ) ) {
			$this->setResult( 'ERROR', 'Could not create the 2nd Image.', $this->tipMsg );
		}
		
		// Place the 2nd Image on to the Publish Form.
		if( $this->wflForm && $this->wflImage2 ) {
			require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';		
			$relation = MultiChannelPublishingUtils::composePlacedRelation(
					$this->wflForm->MetaData->BasicMetaData->ID, $this->wflImage2->MetaData->BasicMetaData->ID );
			if( $relation ) {
				$changedForm->Relations[] = $relation; // new change on the Form.
			} else {
				$this->setResult( 'ERROR', 'Could not place the 2nd Image on the Publish Form.', $this->tipMsg );
			}
		}

		return $changedForm;
	}

	/**
	 * To test several scenarios on PublishForm - Dossier relations.
	 *
	 * @param string $templateName
	 * @param PubChannelInfo $pubChannelObj
	 */
	private function testPublishFormRelations( $templateName, $pubChannelObj )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';

		// Create second form which is targeted to the same Issue. (Should not be successfully created.)
		$template = $this->templates[$templateName][$pubChannelObj->Id];
		$relationErrMsg = 'Test failed at PublishForm - Dossier relations testing.';
		$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
		$this->mcpUtils->setExpectedError('(S1000)');
		$formToTestRelations = $this->mcpUtils->createPublishFormObject( $template, $this->wflDossier, $stepInfo );
		if( !is_null( $formToTestRelations ) ) { // The Form is successfully created, which is wrong.
			$this->setResult( 'ERROR',  'Only one Form can be assigned per Dossier Target which was not the case. '.
				'Two Forms were allowed to be assigned to the same Dossier Target which is wrong.' . $relationErrMsg,
				$this->tipMsg  );
		}

		// Add one more Object Target to the existing Dossier.
		$newTarget = $this->composeTarget();
		$this->wflDossier->Targets[] = $newTarget;

		// Try to setObjectProperties, this time it should not error as
		// the Form Relational Target Issue is the Dossier Targeted Issue.
		$stepInfo = 'Setting properties for the Dossier with a Relational Target that matches the Publish Form (should work).';
		if( !self::callSetObjectPropertiesService( $stepInfo, null ) ) { // should work
			$this->setResult( 'ERROR',  'Error while testing on the Dossier-Form Relations.' .
				'BizException is thrown even though Form relational Target issue is a dossier Targeted Issue, '.
				'this is not expected.', $this->tipMsg );
		}

		$pubChannelId = $newTarget->PubChannel->Id;
		$templateForNewPubChannel = $this->templates[$templateName][$pubChannelId];
		$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
		$this->formForNewPubChannel = $this->mcpUtils->createPublishFormObject( $templateForNewPubChannel,
			$this->wflDossier, $stepInfo, MultiChannelPublishingUtils::RELATION_NORMAL, null,
			array( $this->wflDossier->Targets[1] ) );

		if( is_null( $this->formForNewPubChannel )) { // The Form should be successfully created.
			$this->setResult( 'ERROR',  'The Form was not successfully created which should not be '.
				'the case because the Form Relational Target Issue is one of the Dossier Target Issue.',
				$this->tipMsg );
		}
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

	/**
	 * Composes a new Target based on the created PubChannel and Issue.
	 *
	 * @return Target
	 */
	private function composeTarget()
	{
		$pubChannel = new PubChannel();
		$pubChannel->Id = $this->pubChannel->Id;
		$pubChannel->Name = $this->pubChannel->Name;

		$issue = new Issue();
		$issue->Id = $this->issue->Id;
		$issue->Name = $this->issue->Name;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = array();

		return $target;
	}

	/**
	 * Calls the getDialog2 service with the specific action and validates the response.
	 *
	 * @param Object $template Publish Form Template
	 * @param string $action The action type for the getDialog2 service, e.g 'SetPublishProperties'(built-in),'SetProperties'.
	 * @param string $objType
	 * @param PubChannelInfo|null $pubChannelObj
	 * @return WflGetDialog2Response|null The valid respionse. NULL on error or when not valid.
	 */
	private function callGetDialog2Service( $template, $action, $objType, $pubChannelObj=null )
	{
		// constructing getDialog2 request structure.
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$request = new WflGetDialog2Request();
		$request->Ticket = $this->ticket;
		$request->Action = $action;

		// Preparing the getDialog2 request
		switch( $action ) {
			case 'SetPublishProperties':
				$request->MetaData = array(
					new MetaDataValue( 'ID', null, array( new PropertyValue( $this->wflForm->MetaData->BasicMetaData->ID ) ) ),
					new MetaDataValue( 'Issue', null, array( new PropertyValue( $this->webIssueId ) ) )
				);
				break;
			case 'SetProperties':
				if( $objType == 'PublishFormTemplate' ) {
					$request->MetaData = array(
						new MetaDataValue( 'ID', null, array( new PropertyValue( $template->MetaData->BasicMetaData->ID ) ) ),
						new MetaDataValue( 'Type', null, array( new PropertyValue( $objType ) ) ),
					);
				} else if( $objType == 'PublishForm' ) {
					$request->MetaData = array(
						new MetaDataValue( 'ID', null, array( new PropertyValue( $this->wflForm->MetaData->BasicMetaData->ID ) ) ),
						new MetaDataValue( 'Type', null, array( new PropertyValue( $objType ) ) ),
					);
				} else if( $objType == 'Article' ) {
					$request->MetaData = array(
						new MetaDataValue( 'ID', null, array( new PropertyValue( $this->wflArticle->MetaData->BasicMetaData->ID ) ) ),
						new MetaDataValue( 'Type', null, array( new PropertyValue( $objType ) ) ),
					);
				} else if( $objType == 'Dossier' ) {
					$request->MetaData = array(
						new MetaDataValue( 'ID', null, array( new PropertyValue( $this->wflDossier->MetaData->BasicMetaData->ID ) ) ),
						new MetaDataValue( 'Type', null, array( new PropertyValue( $objType ) ) ),
					);
				}
				break;
			case 'Create':
				require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
				$user = BizSession::checkTicket( $this->ticket );
				$category = BizObjectComposer::getFirstCategory( $user, $this->publicationId );
				$request->MetaData = array(
					new MetaDataValue( 'Publication', null, array( new PropertyValue( $this->publicationId ) ) ),
					new MetaDataValue( 'Category', null, array( new PropertyValue( $category->Id ) ) ),
					new MetaDataValue( 'Type', null, array( new PropertyValue( $objType ) ) ),
				);
				break;
			case 'CopyTo':
				require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
				$user = BizSession::checkTicket( $this->ticket );
				$category = BizObjectComposer::getFirstCategory( $user, $this->publicationId );
				$request->MetaData = array(
					new MetaDataValue( 'ID', null, array( new PropertyValue( $template->MetaData->BasicMetaData->ID ) ) ),
					new MetaDataValue( 'Category', null, array( new PropertyValue( $category->Id ) ) ),
					new MetaDataValue( 'Type', null, array( new PropertyValue( $objType ) ) ),
				);
				break;
			case 'CheckIn':
				require_once BASEDIR . '/server/bizclasses/BizObjectComposer.class.php';
				$user = BizSession::checkTicket( $this->ticket );
				$category = BizObjectComposer::getFirstCategory( $user, $this->publicationId );
				$request->MetaData = array(
					new MetaDataValue( 'ID', null, array( new PropertyValue( $template->MetaData->BasicMetaData->ID ) ) ),
					new MetaDataValue( 'Category', null, array( new PropertyValue( $category->Id ) ) ),
					new MetaDataValue( 'Type', null, array( new PropertyValue( $objType ) ) ),
				);
				break;
		}

		// Call the getDialog2 service and validate them.
		$response = null;
		switch( $action ) {
			case 'SetPublishProperties':
				// Call the getDialog2 service via soap call
				// This is to get the FileUrl in the Attachment of Placement object, otherwise we will only
				// get Attachment->FilePath.
				$stepInfo = 'Retrieve the SetPublishProperties dialog (through GetDialog2) for the '.$objType.' object.';
				$response = $this->utils->callService( $this, $request, $stepInfo );

				if( !$this->validateGetDialog2RespDialog( $template, $response, $pubChannelObj ) ) {
					$response = null;
				}

				if( !$this->validateGetDialog2RespRelations( $template, $response ) ) {
					$response = null;
				}
				break;
			case 'SetProperties':
			case 'Create':
			case 'CopyTo':
			case 'CheckIn':
				// validate the response before returning back to the caller.
				$stepInfo = 'Retrieve the '.$action.' dialog (through GetDialog2) for the '.$objType.' object.';
				$response = $this->utils->callService( $this, $request, $stepInfo );
				if( !$this->validateGetDialog2BrandIssEdition( $response, $objType, $action )) {
					$response = null;
				}
				break;
		}
		return $response;
	}

	/**
	 * Calls the SaveObjects service.
	 *
	 * The response returned by the getDialog2 is used as the base
	 * to construct the object to be passed into the SaveObjects service call.
	 * The object will be modified in makeChangesToTheForm() before the calling
	 * SaveObjects service.
	 *
	 * @param MetaData $metaData
	 * @param Relation[] $relations
	 * @return bool
	 */
	private function callSaveObjectsService( $metaData, $relations )
	{
		$changedForm = $this->makeChangesToTheForm( $metaData, $relations );
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
			$retVal = $this->validateResponseRelations( $response->Objects[0]->Relations, 'SaveObjects' );
		} else {
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Locks the $object by calling GetObjects service.
	 *
	 * @param Object $object
	 * @param string $stepInfo Extra log info.
	 * @return WflGetObjectsResponse|null
	 */
	private function getAndLockObjectForDossierFormRelationTest( $object, $stepInfo )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $object->MetaData->BasicMetaData->ID );
		$request->Lock = true;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'MetaData' );
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		return $this->utils->callService( $this, $request, $stepInfo );
	}
	
	/**
	 *  Unlocks the given Object by calling SaveObjects service.
	 *
	 * @param Object $object
	 * @param string $stepInfo Extra log info.
	 * @param string|null $expectedError
	 * @return WflSaveObjectsResponse|null
	 */
	private function unlockAndSaveObjectForDossierFormRelationTest( $object, $stepInfo, $expectedError )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array( $object );
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $this->utils->callService( $this, $request, $stepInfo, $expectedError );
	}

	/**
	 * Retrieves the Publish Form from DB (by calling the GetObjects service) and validates 
	 * the object relations of the response.
	 *
	 * @return bool Whether or not the service was successful and the response was valid.
	 */
	private function retrieveFormAndValidateRelations()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->wflForm->MetaData->BasicMetaData->ID );
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->RequestInfo = null;
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		
		$stepInfo = 'Retrieve the Publish Form from DB.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( $response ) {
			if( !$this->validateResponseRelations( $response->Objects[0]->Relations, 'GetObjects' ) ) {
				$response = null;
			}
		}
		return (bool)$response;
	}

	/**
	 * Call the SetObjectProperties service.
	 *
	 * @param string $stepInfo Extra logging.
	 * @param string|null $expectedError
	 * @return WflSetObjectPropertiesResponse|null
	 */
	private function callSetObjectPropertiesService( $stepInfo, $expectedError )
	{
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $this->wflDossier->MetaData->BasicMetaData->ID;
		$request->MetaData = $this->wflDossier->MetaData;
		$request->Targets = $this->wflDossier->Targets;
		return $this->utils->callService( $this, $request, $stepInfo, $expectedError );
	}

	/**
	 * Call the CreateObjectRelations service.
	 *
	 * @param Relation[] $relations List of relations to be created.
	 * @param string $stepInfo Extra logging.
	 * @param string|null $expectedError
	 * @return WflCreateObjectRelationsResponse|null
	 */
	private function callCreateObjectRelationsService( $relations, $stepInfo, $expectedError )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = $relations;
		return $this->utils->callService( $this, $request, $stepInfo, $expectedError );
	}

	/**
	 * Call the UpdateObjectRelations service.
	 *
	 * @param Relation[] $relations List of relations to be updated.
	 * @param string $stepInfo Extra logging.
	 * @param string|null $expectedError
	 * @return WflUpdateObjectRelationsResponse|null
	 */
	private function callUpdateObjectRelationsService( $relations, $stepInfo, $expectedError )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';
		$request = new WflUpdateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = $relations;
		return $this->utils->callService( $this, $request, $stepInfo, $expectedError );
	}

	/**
	 * To validate the getDialog2 response if the Relations are returned
	 * and the Relations are valid.
	 * Placement defined in the Relation is also verified.
	 *     L> It has to be an image.
	 *     L> The image Object and its attachment is found in $response->Objects.
	 *
	 * @param Object $template Publish Form Template
	 * @param WflGetDialog2Response $response
	 * @return bool True when the validation is fine; False when the Relations have invalid data.
	 * @throw BizException when the Relations are not valid(Such as Form targetted to the wrong dossier).
	 */
	private function validateGetDialog2RespRelations( $template, $response )
	{
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		if( !empty( $response->Targets ) ) { // Form's targets should always be empty.
			$this->setResult( 'ERROR',  'Target found in Publish Form which is invalid (publish form should not have Targets):',
				'Please check in the getDialog2Resp->Targets. '. $this->tipMsg  );
			return false;
		}

		$instanceOfCount = 0;
		$containedCount = 0;
		$image1PlacementFound = false; // to search for the placement(which is an image) on the form.
		$image1PlacementParentFound = false; // to check if the parent(form) of the placement image is correct.
		
		foreach( $response->Relations as $relation ) {
			if( $relation->Type == 'InstanceOf' ) {
				$instanceOfCount++;
				if( $relation->Parent != $template->MetaData->BasicMetaData->ID ) {
					$this->setResult( 'ERROR',  'Publish Form has an "InstancOf" relation with the wrong Publish Form Template.',
										'Please check in the getDialog2Resp->Relations. '. $this->tipMsg );
					return false;
				}
			}

			if( $relation->Type == 'Contained' ) {
				$containedCount++;
				if( $relation->Parent != $this->wflDossier->MetaData->BasicMetaData->ID ) {
					$this->setResult( 'ERROR',  'Publish Form has a "Contained" relation with the wrong Dossier.',
										'Please check in the getDialog2Resp->Relations. ' . $this->tipMsg );
					return false;
				}
			}

			if( $relation->Type == 'Placed' ) {
				$childObjType = DBObject::getObjectType( $relation->Child );
				if( $childObjType == 'Image' ) {
					if( $relation->Child == $this->wflImage1->MetaData->BasicMetaData->ID ) {
						$image1PlacementFound = true; // image placed is correct.
						if( $relation->Parent == $this->wflForm->MetaData->BasicMetaData->ID ) { // next check if the parent is correct
							$image1PlacementParentFound = true; // parent of the image is also correct.							
						}
					}
				}
			}
		}

		if( $instanceOfCount != 1 || $containedCount != 1) {
			$this->setResult( 'ERROR',  'Publish Form should only contain one "InstanceOf" and one ' .
							'"Contained" relations.', 'Please check in the getDialog2Resp->Relations. ' .$this->tipMsg  );
			return false;
		}

		if( !$image1PlacementFound || !$image1PlacementParentFound ) {
			$errMsg = '';
			if( !$image1PlacementFound ) {
				$errMsg .= 'Publish Form is expected to have one image placement, but not found.';
			}
			if( !$image1PlacementParentFound ) {
				$errMsg .= 'Publish Form has a "Placed" relation with an image but the Parent Id is not the form itselfs.';
			}
			$this->setResult( 'ERROR', $errMsg, 'Please check in the getDialog2Resp->Relations. '. $this->tipMsg );
			return false;
		}			

		// >>>>>>> Commented out. Since 9.1, the GetDialog2Response->Objects is taken out for optimum performance.
		//// Check if the thumbnail of the placement(image) is found in the response.
		//// when there's image placement found, the Image Object should be in the response->Objects
		//$imagePlacementThumbFound = false; // imagePlacement - the placement object and the relation with the form.
		//if( $response->Objects ) {
		//	foreach( $response->Objects as $placementObj ) {
		//		if( $placementObj->MetaData->BasicMetaData->ID == $this->wflImage1->MetaData->BasicMetaData->ID ) {
		//			$placementObjAttachment = $placementObj->Files[0]; // can assume there's only one attachment.
		//			if( $this->checkAttachment( $placementObjAttachment ) ) {
		//				$imagePlacementThumbFound = true;
		//			}
		//			break;
		//		}
		//	}
		//}
		//if( !$imagePlacementThumbFound ) {
		//	$this->setResult( 'ERROR',  'Expected to have one image placement in the Publish Form,' .
		//					'but none found.', 'Please check in the getDialog2Resp->Objects[n]. '. $this->tipMsg );
		//	return false;
		//} <<<<<<<<<<
		return true;
	}

	/**
	 * Validate the Dialog returned in getDialog2 response.
	 *
	 * @param Object $template Publish Form Template
	 * @param GetDialog2Response $response The response to be validated.
	 * @param PubChannelInfo $pubChannelObj
	 * @return bool True when the Dialog object is validated fine; False otherwise.
	 */
	private function validateGetDialog2RespDialog( $template, $response, $pubChannelObj )
    {
	    require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
        $result = true;

        if ( !$response->Dialog ) {
            $this->setResult( 'ERROR', 'The MultiChannelPublishingSample plugin didn\'t return a Dialog object.', $this->tipMsg );
            $result = false;
        }
        if ( !is_array($response->Dialog->Tabs) || count($response->Dialog->Tabs) != 1 ) {
            $this->setResult( 'ERROR', 'The MultiChannelPublishingSample plugin should return one Dialog Tab.', $this->tipMsg );
            $result = false;
        }
        if (!is_array($response->Dialog->Tabs[0]->Widgets) || count($response->Dialog->Tabs[0]->Widgets) < 1) {
            $this->setResult( 'ERROR', 'The MultiChannelPublishingSample plugin should return at least one Widget on the Dialog Tab.', $this->tipMsg );
            $result = false;
        }

	    // Get the dialog definition from the connector.
	    $dialog = BizServerPlugin::runChannelConnector( $pubChannelObj->Id, 'getDialogForSetPublishPropertiesAction', array( $template ) );

	    require_once BASEDIR.'/server/utils/PhpCompare.class.php';
	    $phpCompare = new WW_Utils_PhpCompare();
	    foreach( $response->Dialog->Tabs as $respDialogTab ) { // DialogTab from the response
			if( $respDialogTab->Title ) foreach( $respDialogTab->Widgets as $respDialogTabWidget ) { // DialogTab->Widget from the response
	            foreach( $dialog->Tabs as $tab ) { // from the definition
		            if( $tab->Title ) foreach( $tab->Widgets as $widget ) { // from the definition

			            if( $respDialogTab->Title == $tab->Title &&
							$respDialogTabWidget->PropertyInfo->Name == $widget->PropertyInfo->Name ) { // found the prop name in the definitions

							// Check for the Widget->PropertyInfo

				            // Adjust the expectedPropInfo (some data are not filled in yet during getDialogForSetPublishPropertiesAction()).
				            // so here adjust it as how the core server will fill in the data.
							$expectedPropInfo = unserialize( serialize( $widget->PropertyInfo ) );
							$expectedPropInfo->Category = $tab->Title;
							if( $expectedPropInfo->Widgets ) foreach( $expectedPropInfo->Widgets as $expectedWidgetInWidget ) {
								$expectedWidgetInWidget->PropertyInfo->Category = $tab->Title;
							}

							$respDialogWidgetPropInfoAdjusted = $this->adjustPropInfo( $expectedPropInfo, $respDialogTabWidget->PropertyInfo );
							$phpCompare->initCompare( array(
								// Object properties that will not be compared
								'PropertyInfo->AutocompleteProvider' => true, // It's not the subject to test here, ignore.
								'PropertyInfo->SuggestionProvider' => true, // It's not the subject to test here, ignore.
								'PropertyInfo->Widgets[0]->PropertyUsage->MultipleObjects' => true, // It's not the subject to test here, ignore.
							));
							if( !$phpCompare->compareTwoObjects( $expectedPropInfo, $respDialogWidgetPropInfoAdjusted ) ) {
								$this->setResult( 'ERROR', 'The returned PropertyInfo in the GetDialog2 is invalid. ' .
									print_r( $phpCompare->getErrors(),1), $this->tipMsg );
								$result = false;
							}

							// Check for the Widget->PropertyUsage
							$phpCompare->initCompare( array(
								'PropertyInfo->Widgets[0]->PropertyUsage->MultipleObjects' => true,
								'PropertyUsage->MultipleObjects' => true ));
							if( !$phpCompare->compareTwoObjects( $widget->PropertyUsage, $respDialogTabWidget->PropertyUsage ) ) {
								$this->setResult( 'ERROR', 'The returned PropertyUsage in the GetDialog2 is invalid. ' .
									print_r( $phpCompare->getErrors(),1), $this->tipMsg );
								$result = false;
							}
							continue;
						}
		            }
	            }
			}
	    }

		// Check the newly introduced ButtonBar
		if ( is_null($response->Dialog->ButtonBar) ) {
			$this->setResult( 'ERROR', 'The Dialog for the \'SetPublishProperties\' action should return a ButtonBar.' );
			$result = false;
		} else {
			// By default the GetDialog2 call return 4 buttons (Publish, UnPublish, Update and Preview)
			if ( count($response->Dialog->ButtonBar) != 4 ) {
				$this->setResult( 'ERROR', 'The Dialog for the \'SetPublishProperties\' action should return 4 buttons in the ButtonBar.' );
				$result = false;
			}
		}

        return $result;
    }

	/**
	 * Validate Brand, Issue/Issues, Editions returned in getDialog2 response's widget and metadata.
	 *
	 * The following rules are checked:
	 * For PublishFormTemplate(Create, CopyTo):
	 * L> 1. Brand widget should be editable.
	 * L> 2. Publish In(PubChannels) widget should be editable.
	 * L> 3. There should be PubChannels widgets+metadata.
	 * L> 4. There should not be Targets and Editions in the widgets+metadata.
	 * L> 5. There should not be Issue/Issues in the widgets+metadata.
	 *
	 * For PublishFormTemplate(Save,SetProperties):
	 * L> 6.  Brand widget should be read-only.
	 * L> 7.  Publish In(PubChannels) widget should be read-only.
	 * L> 8.  There should be PubChannels widgets+metadata.
	 * L> 9.  There should not be Targets and Editions in the widgets+metadata.
	 * L> 10. There should not be Issue/Issues in the widgets+metadata.
	 *
	 * For PublishForm(Create, CopyTo):
	 * L> 11. Brand widget should be editable.
	 * L> 12. Publish In(Targets,Issue/Issues) widget should be editable.(Targets is taken out, so only Issue/Issues to check)
	 * L> 13. There should not be Targets and Editions in the widgets+metadata.
	 * L> 14. There should be PubChannels widgets+metadata, but it is editable.
	 *
	 * For PublishForm(Save,SetProperties):
	 * L> 15. Brand widget should be read-only.
	 * L> 16. Publish In(Targets,Issue/Issues) widget should be read-only.(Targets is taken out, so only Issue/Issues to check)
	 * L> 17. There should not be Targets and Editions in the widgets+metadata.
	 * L> 18. There should be PubChannels widgets+metadata, but it is read-only.
	 *
	 * For Article:
	 * L> 19. Brand, Issue/Issues, Targets and Editions widget should be exists and editable.
	 * L> 20. There should not be PubChannels in widgets+metadata.
	 *
	 * For Dossier that is targeted to PubChannels that supports PublishForm(Create, CopyTo):
	 * L> 21. Brand widget should be editable.
	 * L> 22. Publish In(Targets,Issue/Issues) widget should be editable.
	 *
	 * For Dossier that is targeted to PubChannels that supports PublishForm(SetProperties):
	 * L> 23. Brand widget should be read-only.
	 * L> 24. Publish In(Targets,Issue/Issues) widget should be read-only.
	 *
	 * @param GetDialog2Response $response The response to be validated.
	 * @param string $objType
	 * @param string $dialogAction The action parameter of the GetDialog2 request.
	 * @return bool True when the Dialog object is validated fine; False otherwise.
	 */
	private function validateGetDialog2BrandIssEdition( $response, $objType, $dialogAction )
	{
		$result = true;
		$this->validateDialogTabWidgets( $response, $objType, $result, $dialogAction );
		$this->validateDialogMetaData( $response, $objType, $result, $dialogAction );
		return $result;
	}

	/**
	 * Refer to validateGetDialog2BrandIssEdition function header.
	 *
	 * @param GetDialog2Response $response The response to be validated.
	 * @param string $objType
	 * @param string $dialogAction The action parameter of the GetDialog2 request.
	 * @param bool &$result True when the Dialog Tab widget is validated fine; False otherwise.
	 */
	private function validateDialogTabWidgets( $response, $objType, &$result, $dialogAction )
	{
		// checking for the widgets.
		$templatePubChannelsFound = false;
		$formPubChannelsFound = false;
		$articleTargetsFound = false;
		$articleEditionsFound = false;
		if( $response->Dialog->Tabs ) foreach( $response->Dialog->Tabs as $respDialogTab ) {
			if( $respDialogTab->Widgets ) foreach( $respDialogTab->Widgets as $respDialogTabWidget ) {
				$propName = $respDialogTabWidget->PropertyInfo->Name;
				if( $objType == 'PublishFormTemplate' ) {
					if( $dialogAction == 'Create' || $dialogAction == 'CopyTo' ) {
						switch( $propName ) {
							case 'Publication': // Rule 1.
							case 'PubChannels': // Rule 2.
								if( !$respDialogTabWidget->PropertyUsage->Editable ) {
									$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
										'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
										'widget set to read-only."'.$propName.'" widget is expected to be editable.',
										'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
											$this->tipMsg );
									$result = false;
								}
								if( $propName == 'PubChannels' ) {
									$templatePubChannelsFound = true; // Rule 3.
								}
								break;
							case 'Targets': // Rule 4.
							case 'Editions': // Rule 4.
							case 'Issue': // Rule 5.
							case 'Issues': // Rule 5.
								$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
									'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
									'returned in the widget which is not expected(It should not return "'.$propName.'").',
									'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
										$this->tipMsg );
								$result = false;
								break;
						}
					} else if( $dialogAction == 'SetProperties' || $dialogAction == 'CheckIn' ) {
						switch ( $propName ) {
							case 'Publication': // Rule 6.
							case 'PubChannels': // Rule 7.
								if( $respDialogTabWidget->PropertyUsage->Editable ) {
									$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
										'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
										'widget set to editable."'.$propName.'" widget is expected to be read-only.',
										'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
											$this->tipMsg );
									$result = false;
								}
								if( $propName == 'PubChannels' ) {
									$templatePubChannelsFound = true; // Rule 8.
								}
								break;
							case 'Targets': // Rule 9.
							case 'Editions': // Rule 9.
							case 'Issue': // Rule 10.
							case 'Issues': // Rule 10.
								$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
									'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
									'returned in the widget which is not expected(It should not return "'.$propName.'").',
									'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
										$this->tipMsg );
								$result = false;
								break;
						}
					}
				} else if( $objType ==  'PublishForm' ){
					if( $dialogAction == 'Create' || $dialogAction == 'CopyTo' ) {
						switch( $propName ) {
							case 'Publication': // Rule 11.
							case 'Issue': // Rule 12.
							case 'Issues': // Rule 12.
							case 'PubChannels': // Rule 14.
								if( !$respDialogTabWidget->PropertyUsage->Editable ) {
									$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
										'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
										'widget set to read-only."'.$propName.'" widget is expected to be editable.',
										'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n].'.
											$this->tipMsg );
									$result = false;
								}
								if( $propName == 'PubChannels' ) {
									$formPubChannelsFound = true; // Rule 14.
								}
								break;
							case 'Targets': // Rule 13.
							case 'Editions': // Rule 13.
								$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
									'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
									'returned in the widget which is not expected(It should not return "'.$propName.'").',
									'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
										$this->tipMsg );
								$result = false;
								break;
						}
					} else if( $dialogAction == 'SetProperties' || $dialogAction == 'CheckIn' ) {
						switch ( $propName ) {
							case 'Publication': // Rule 15.
							case 'Issue': // Rule 16.
							case 'Issues': // Rule 16.
							case 'PubChannels': // Rule 18.
								if( $respDialogTabWidget->PropertyUsage->Editable ) {
									$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
										'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" widget set '.
										'to editable. "'.$propName.'" widget is expected to be read-only.',
										'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
											$this->tipMsg );
									$result = false;
								}
								if( $propName == 'PubChannels' ) {
									$formPubChannelsFound = true; // Rule 18.
								}
								break;
							case 'Targets': // Rule 17.
							case 'Editions': // Rule 17.
								$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
									'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
									'returned in the widget which is not expected(It should not return "'.$propName.'").',
									'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
										$this->tipMsg );
								$result = false;
								break;
						}
					}
				} else if( $objType == 'Article' ) {
					switch ( $propName ) {
						case 'Publication': // Rule 19.
						case 'Issue': // Rule 19
						case 'Issues': // Rule 19
						case 'Targets': // Rule 19
						case 'Editions': // Rule 19
							if( !$respDialogTabWidget->PropertyUsage->Editable ) {
								$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
									'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" widget set '.
									'to be non-editable. "'.$propName.'" widget is expected to be editable.',
									'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
										$this->tipMsg );
								$result = false;
							}
							if( $propName == 'Targets' ) {
								$articleTargetsFound = true; // Rule 19.
							}
							if( $propName == 'Editions' ) {
								$articleEditionsFound = true; // Rule 19.
							}
							break;
						case 'PubChannels': // Rule 20.
							$this->setResult( 'ERROR', 'Found "Pubchannels" returned in the GetDialog2 widget for '.$objType.', '.
								'it should not be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n].',
								'Please check in the getDialog2 service call. ' . $this->tipMsg );
							$result = false;
							break;
					}

				} else if( $objType == 'Dossier' ) {
					if( $dialogAction == 'Create' || $dialogAction == 'CopyTo' ) {
						switch( $propName ) {
							case 'Publication': // Rule 21.
							case 'Issue': // Rule 22.
							case 'Issues': // Rule 22.
							case 'Targets': // Rule 22
								if( !$respDialogTabWidget->PropertyUsage->Editable ) {
									$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
										'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
										'widget set to read-only."'.$propName.'" widget is expected to be editable.',
										'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n].'.
											$this->tipMsg );
									$result = false;
								}
								break;
						}
					} else if( $dialogAction == 'SetProperties' ) {
						switch ( $propName ) {
							case 'Publication': // Rule 23.
							case 'Issue': // Rule 24.
							case 'Issues': // Rule 24.
							case 'Targets': // Rule 24.
								if( $respDialogTabWidget->PropertyUsage->Editable ) {
									$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
										'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" widget set '.
										'to editable. "'.$propName.'" widget is expected to be read-only.',
										'Please check in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n]. '.
											$this->tipMsg );
									$result = false;
								}
								break;
						}
					}
				}
			}
		}
		if( $objType == 'PublishFormTemplate' && !$templatePubChannelsFound ) { // Rule 3, 8.
			$this->setResult( 'ERROR', 'No "Pubchannels" returned in the GetDialog2 widget(action="'.$dialogAction.'") '.
				'for '.$objType.', it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n].'.
				'Please check in the getDialog2 service call. ' . $this->tipMsg );
			$result = false;
		}
		if( $objType == 'PublishForm' && !$formPubChannelsFound  ) { // Rule 14, 18.
			$this->setResult( 'ERROR', 'No "Pubchannels" returned in the GetDialog2 widget(action="'.$dialogAction.'") '.
				'for '.$objType.', it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n].'.
				'Please check in the getDialog2 service call. ' . $this->tipMsg );
			$result = false;
		}
		if( $objType == 'Article' && !$articleTargetsFound ) { // Rule 19.
			$this->setResult( 'ERROR', 'No "Targets" returned in the GetDialog2 widget(action="'.$dialogAction.'") '.
				'for '.$objType.', it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n].'.
				'Please check in the getDialog2 service call. ' . $this->tipMsg );
			$result = false;
		}
		if( $objType == 'Article' && !$articleEditionsFound ) { // Rule 19.
			$this->setResult( 'ERROR', 'No "Editions" returned in the GetDialog2 widget(action="'.$dialogAction.'") '.
				'for '.$objType.', it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->Widgets[n].'.
				'Please check in the getDialog2 service call. ' . $this->tipMsg );
			$result = false;
		}

		if ( !is_null($response->Dialog->ButtonBar) ) {
			$this->setResult( 'ERROR', 'For every action other than \'SetPublishProperties\' the ButtonBar of the Dialog should be null.' );
			$result = false;
		}
	}

	/**
	 * Refer to validateGetDialog2BrandIssEdition function header.
	 *
	 * @param GetDialog2Response $response The response to be validated.
	 * @param string $objType
	 * @param bool &$result True when the Dialog Tab widget is validated fine; False otherwise.
	 * @param string $dialogAction The action parameter of the GetDialog2 request.
	 */
	private function validateDialogMetaData( $response, $objType, &$result, $dialogAction )
	{
		// checking for the MetaData.
		$templatePubChannelsFound = false;
		$formPubChannelsFound = false;
		$articleIssueFound = false;
		$articleIssuesFound = false;
		$articleTargetsFound = false;
		$articleEditionsFound = false;
		if( $response->Dialog->MetaData ) foreach( $response->Dialog->MetaData as $respDialogMetaData ) {
			$propName = $respDialogMetaData->Property;
			if( $objType == 'PublishFormTemplate' ) {
				if( $dialogAction == 'Create' || $dialogAction == 'CopyTo' ) {
					switch( $propName ) {
						case 'PubChannels': // Rule 3.
							$templatePubChannelsFound = true;
							break;
						case 'Targets': // Rule 4.
						case 'Editions': // Rule 4.
						case 'Issue': // Rule 5.
						case 'Issues': // Rule 5.
							$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
								'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
								'returned in the MetaData which is not expected(It should not return "'.$propName.'").',
								'Please check in the getDialog2->Response->Dialog->MetaData[n]. '.
									$this->tipMsg );
							$result = false;
							break;
					}
				} else if( $dialogAction == 'SetProperties' || $dialogAction == 'CheckIn' ) {
					switch( $propName ) {
						case 'PubChannels': // Rule 8.
							$templatePubChannelsFound = true;
							break;
						case 'Targets': // Rule 9.
						case 'Editions': // Rule 9.
						case 'Issue': // Rule 10.
						case 'Issues': // Rule 10.
							$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
								'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
								'returned in the MetaData which is not expected(It should not return "'.$propName.'").',
								'Please check in the getDialog2->Response->Dialog->MetaData[n]. '.
									$this->tipMsg );
							$result = false;
							break;
					}
				}
			} else if( $objType ==  'PublishForm' ){
				if( $dialogAction == 'Create' || $dialogAction == 'CopyTo' ) {
					switch( $propName ) {
						case 'Targets': // Rule 13
						case 'Editions': // Rule 13.
							$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
								'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
								'returned in the MetaData which is not expected(It should not return "'.$propName.'").',
								'Please check in the getDialog2->Response->Dialog->MetaData[n]. '.
									$this->tipMsg );
							$result = false;
							break;
						case 'PubChannels': // Rule 14.
							$formPubChannelsFound = true;
							break;
					}
				} else if( $dialogAction == 'SetProperties' || $dialogAction == 'CheckIn' ) {
					switch( $propName ) {
						case 'Targets': // Rule 17..
						case 'Editions': // Rule 17..
							$this->setResult( 'ERROR',  'Response returned by getDialog2 with '.
								'action="'.$dialogAction.'" and object type = "'.$objType.'" has "'.$propName.'" '.
								'returned in the MetaData which is not expected(It should not return "'.$propName.'").',
								'Please check in the getDialog2->Response->Dialog->MetaData[n]. '.
									$this->tipMsg );
							$result = false;
							break;
						case 'PubChannels': // Rule 18.
							$formPubChannelsFound = true;
							break;
					}
				}
			} else if( $objType == 'Article' ) {
				switch( $propName ) {
					case 'PubChannels': // Rule 20.
						$this->setResult( 'ERROR', 'Found "Pubchannels" returned in the GetDialog2 MetaData for '.$objType.', '.
							'it should not be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->MetaData[n].',
							'Please check in the getDialog2 service call. ' . $this->tipMsg );
						$result = false;
						break;
					case 'Issue':
						$articleIssueFound = true; // Rule 19.
						break;
					case 'Issues':
						$articleIssuesFound = true; // Rule 19.
						break;
					case 'Targets':
						$articleTargetsFound = true; // Rule 19.
						break;
					case 'Editions':
						$articleEditionsFound = true; // Rule 19.
						break;
				}
			}
		}
		if( $objType == 'PublishFormTemplate' && !$templatePubChannelsFound ) { // Rule 3, 8.
			$this->setResult( 'ERROR', 'No "Pubchannels" returned in the GetDialog2 MetaData for '.$objType.', '.
				'it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->MetaData[n].',
				'Please check in the getDialog2 service call. ' . $this->tipMsg );
			$result = false;
		}
		if( $objType == 'PublishForm' && !$formPubChannelsFound ) { // Rule 18.
			$this->setResult( 'ERROR', 'No "Pubchannels" returned in the GetDialog2 MetaData for '.$objType.', '.
				'it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->MetaData[n].',
				'Please check in the getDialog2 service call. ' . $this->tipMsg );
			$result = false;
		}
		if( $objType == 'Article' ) {
			if( !$articleIssueFound && !$articleIssuesFound ) { // Rule 19.
				$this->setResult( 'ERROR', 'No "Issue/Issues" returned in the GetDialog2 MetaData for '.$objType.', '.
					'it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->MetaData[n].',
					'Please check in the getDialog2 service call. ' . $this->tipMsg );
				$result = false;
			}
			if( !$articleTargetsFound ) { // Rule 19.
				$this->setResult( 'ERROR', 'No "Targets" returned in the GetDialog2 MetaData for '.$objType.', '.
					'it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->MetaData[n].',
					'Please check in the getDialog2 service call. ' . $this->tipMsg );
				$result = false;
			}
			if( !$articleEditionsFound ) { // Rule 19.
				$this->setResult( 'ERROR', 'No "Editions" returned in the GetDialog2 MetaData for '.$objType.', '.
					'it should be returned in the getDialog2->Response->Dialog->Tabs[n]->DialogTab->MetaData[n].',
					'Please check in the getDialog2 service call. ' . $this->tipMsg );
				$result = false;
			}
		}
	}

	/**
	 * To adjust the PropertyInfo in such a way that Empty value will be set to Null.
	 *
	 * @param PropertyInfo $origProps Original properties 
	 * @param PropertyInfo $adjustProps Properties to be adjusted
	 * @return PropertyInfo PropertyInfo that has been adjusted.
	 */
	private function adjustPropInfo( $origProps, $adjustProps )
	{
		foreach( get_object_vars( $adjustProps ) as $adjustPropName => $adjustPropValues ) {
			if( is_array( $adjustPropValues ) && count( $adjustPropValues ) > 0) {
				$index = 0;
				foreach( $adjustPropValues as $adjustPropValue ) {
					if( $adjustPropValue ) {
						if( $adjustPropName == 'Widgets') {
							$adjustProps->Widgets[$index]->PropertyInfo = $this->adjustPropInfo( $origProps->Widgets[$index]->PropertyInfo, $adjustPropValue->PropertyInfo );
							// @TODO: Use variables instead of putting the fix name like 'Widgets' as used above.
							//$adjustProps->$adjustPropName[$index]->PropertyInfo =
							//		$this->adjustPropInfo( $origProps->Widgets[$index]->PropertyInfo, $adjustPropValues->PropertyInfo );
						}
					}
					$index++;
				}
			} else if( !$adjustPropValues ) {
				if( !isset($origProps->$adjustPropName) ) {
					$origProps->$adjustPropName = null; // some props are dynamically added e.g. PublishSystem, TemplateId, AdminUI
				}
				$adjustProps->$adjustPropName = $origProps->$adjustPropName;
			}
		}

		return $adjustProps;
	}

	/**
	 * Validate the Relations returned by the $service response.
	 * Checks if the Form's Relations are returned correctly.
	 * Checks if the Form has Target (it should not have)
	 * 
	 * @param array $respRelations Response from $service to be validated.
	 * @param string $service The service name of the response where the Relation is retrieved from.
	 * @return bool True when the relations are valid; False when errors found.
	 */
	private function validateResponseRelations( $respRelations, $service )
	{
		if( !$respRelations || empty( $respRelations )	) {
			$this->setResult( 'ERROR',  'Response returned by saveObjects has no Relations.' .
							'Relations expected for Object type "Form".',
							'Please check in the '.$service.'Response->Objects[n]->Relations. '. $this->tipMsg );
			return false;
		}
		$image1Found = false; // find for the first placement on the form.
		$image2Found = false; // find for the second placement on the form.
		foreach( $respRelations as $relation ) {
			// validate for FormWidgetId(whether it is round-tripped)
			if( $relation->Child == $this->placementImgIdOfChangedFormWidget ) { // find which image where the FormWidgetId was changed
				if( $relation->Placements[0]->FormWidgetId != $this->changedFormWidgetId ) { // FormWidgetId supposed to be round-tripped
					$this->setResult( 'ERROR',  'The FormWidgetId is not round-tripped during the '.$service.' service call.' .
							'Expected FormWidgetId to be "'.$this->changedFormWidgetId.'" but "'.
							$relation->Placements[0]->FormWidgetId.'" found.',
							'Please check in the '.$service.'Response->Relations[n]->Placements. '. $this->tipMsg );
					return false;
				}
			}

			// check if the placements (image1 and image2)  on the forms are found.
			if( $relation->Child == $this->wflImage1->MetaData->BasicMetaData->ID ) {
				$image1Found = true;
			}
			if( $relation->Child == $this->wflImage2->MetaData->BasicMetaData->ID ) {
				$image2Found = true;
			}			
		}
		if( !$image1Found || !$image2Found ) {			
			$this->setResult( 'ERROR',  $service.' response did not return complete Placements.'.
					'The placements(images) on the form are not complete. Expected to have two.',
					'Please check in the '.$service.'Response->Relations[n]->Placements. ' .$this->tipMsg  );
			return false;
		}
		return true;
	}
	
	/**
	 * Checks if the file is prensent in the Transfer Server Folder.
	 *
	 * @param Attachment $attachment
	 * @return bool Whether or not present.
	 */
	private function checkAttachment( $attachment )
	{
		$retVal = true;
		if( !$attachment->FilePath || !filesize($attachment->FilePath) ) {
			$this->setResult( 'ERROR', 'The placement image has no FilePath set in the attachment.',
							'Please check in the getDialog2Resp->Objects[n]->Files. '. $this->tipMsg  );
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Compose a Print Target from a BuildTest session variables.
	 *
	 * @return Target
	 */
	private function getPrintTarget()
	{
		$pubChannel = $this->vars['BuildTest_MultiChannelPublishing']['printPubChannel'];
		$issue = $this->vars['BuildTest_MultiChannelPublishing']['printIssue'];
		$printTarget = new Target(); // Target that doesn't support PublishForm.
		$printTarget->PubChannel = new PubChannel( $pubChannel->Id, $pubChannel->Name );
		$printTarget->Issue = new Issue( $issue->Id, $issue->Name, $issue->OverrulePublication );

		return $printTarget;
	}

	/**
	 * Assign a Print channel to the Dossier.
	 *
	 * @return bool
	 */
	private function assignPrintTarget()
	{
		$printTarget = $this->getPrintTarget();

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';
		$request = new WflCreateObjectTargetsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->wflDossier->MetaData->BasicMetaData->ID );
		$request->Targets = array( $printTarget );
		$stepInfo = 'Add one more Target that does not support PublishForm to Dossier';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( !is_null( $response ) ) {
			$result = true;
			$this->wflDossier->Targets = $response->Targets;
		} else {
			$result = false;
		}

		return $result;
	}

	/**
	 * Restore the original Object Targets of the Dossier by removing the Print target assigned earlier on.
	 * $originalDosserTargets is to be restored on the this->wflDossier->Targets.
	 *
	 * @param array $originalDosserTargets The Object Targets to be restored.
	 * @return bool
	 */
	private function restoreOriginalTarget( $originalDosserTargets )
	{
		$printTarget = $this->getPrintTarget();

		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectTargetsService.class.php';
		$request = new WflDeleteObjectTargetsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->wflDossier->MetaData->BasicMetaData->ID );
		$request->Targets = array( $printTarget );
		$stepInfo = 'Delete Print Target from the Dossier';
		$response =  $this->utils->callService( $this, $request, $stepInfo );
		if( !is_null( $response ) ) {
			$this->wflDossier->Targets = $originalDosserTargets; // Instead of doing GetObjects call, just restore the original Targets
			$result = true;
		} else {
			$result = false;
		}

		return $result;
	}

	/**
	 * To fake the Client calling the service calls.
	 *
	 * A Content Station v9.0 below or Smart Connections clients are needed
	 * to simulate certain properties in the dialog returned by the server are
	 * disabled (By default, the server will enable all properties in the dialog).
	 * However, to avoid taking a license seat during the BuildTest execution,
	 * this function just fake the client app and version in the database row
	 * right before the testing starts.
	 */
	private function fakeTheClient()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$where = '`ticketid` = ? ';
		$params = array( $this->ticket );
		$fieldsName = array( 'usr', 'appname', 'appversion');
		$this->buildTestSessionRow = DBBase::getRow( 'tickets', $where, $fieldsName, $params );

		$values = array();
		$values['appname'] = 'Content Station';
		$values['appversion'] = 'v8.0.0 Build 1';

		$where = 'ticketid = ? and usr = ? ';
		$params = array();
		$params[] = $this->ticket;
		$params[] = $this->buildTestSessionRow['usr'];
		DBBase::updateRow( 'tickets', $values, $where, $params);
	}

	/**
	 * Restore the app name and the version in the database set previously at {@link: fakeTheClient()}.
	 */
	private function restoreTheBuildTestClient()
	{
		$values = array();
		$values['appname'] = $this->buildTestSessionRow['appname'];
		$values['appversion'] = $this->buildTestSessionRow['appversion'];

		$where = 'ticketid = ? and usr = ? ';
		$params = array();
		$params[] = $this->ticket;
		$params[] = $this->buildTestSessionRow['usr'];
		DBBase::updateRow( 'tickets', $values, $where, $params);
	}
}
