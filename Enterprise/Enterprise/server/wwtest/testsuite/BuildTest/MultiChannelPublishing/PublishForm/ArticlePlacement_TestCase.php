<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_ArticlePlacement_TestCase extends TestCase
{
	public function getDisplayName() { return 'Article placement on Form'; }
	public function getTestGoals()   { return 'Ensure that the same Article placed on Layout and Form will not raise duplicate placement warning.'; }
	public function getTestMethods() { return 'Place one article on Layout and Form. Opening the layout should not get any duplicate placement warning.'; }
	public function getPrio()        { return 100; }

	// Session data:
	private $ticket = null;
	private $vars = null;
	private $pubChannel = null;
	private $issue = null;
	private $initialFormVersion = null;
	private $initialArticleVersion = null;

	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils
	private $transferServer = null; // BizTransferServer

	// Templates objects:
	private $templates = null; // imported by this test script
	private $foreignTemplates = null; // templates found that are -not- created by this script
	
	// Objects to test with:
	private $dossier = null;
	private $form = null;
	private $article = null;
	private $layout = null;

	const PUBLISH_PLUGIN = 'MultiChannelPublishingSample';
	const PUBLISH_TEMPLATE = 'Article Component Selector Template';

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

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
		$this->vars = $this->getSessionVariables();
		$this->ticket     = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		$this->pubChannel = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel'];
		$this->issue      = $this->vars['BuildTest_MultiChannelPublishing']['webIssue'];
		
		// Start the session for the BuildTest to get the definitions.
		BizSession::checkTicket( $this->ticket );

		// Remember which templates are already in DB, before the script creates more templates.
		// This is needed to skip those templates when cleaning up the DB, see removeDefinitions().
		$this->foreignTemplates = array();
		require_once BASEDIR.'/server/bizclasses/BizAdmPublication.class.php';
		$pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( self::PUBLISH_PLUGIN );
		if( $pubChannelInfos ) foreach( $pubChannelInfos as $pubChannelInfo ) {
			$this->getPublishFormTemplates( $pubChannelInfo->Id, $this->foreignTemplates );
		}

		// Import the custom properties, Publish Form Templates and dialog of the 
		// server plug-in "MultiChannelPublishingSample".
		if( $this->importDefinitions() ) {
		
			do {
				// Create test data.
				if( !$this->setupTestData() ) {
					break;
				}
	
				// Retrieve the Publish Form from DB while locking the form.
				if( !$this->lockTheForm() ) {
					break;
				}

				// Save the Publish Form with placed Article at DB while unlocking the form.
				if( !$this->placeArticleOnFormAndCheckIn() ) {
					break;
				}

				// Retrieve the Layout from DB and lock it for editing.
				if( !$this->getLayoutAndCheckOut() ) {
					break;
				}
	
				// Place an article (that is already placed in the Form) on the Layout.
				if( !$this->placeArticleOntoLayout() ) {
					break;
				}
				
				// Save the Layout and release the lock.
				$saveLayoutResponse = $this->checkinLayout();
				if( !$saveLayoutResponse ) {
					break;
				}
				
				 // After the operations, the SaveObjects response is checked to ensure
				 // there was no DuplicatePlacement warning raised. When DuplicatePlacement
				 // warning is found, error is shown in the BuildTest.
				if( !$this->checksForDuplicateWarning( $saveLayoutResponse ) ) {
					break;
				}

				// Version of the PublishForm should be updated each time the placement object on the PublishForm is modified.
				if( !$this->savePlacementObject() ) {
					break;
				}

				if( !$this->validateFormAndArticleVersions( array( 'Workflow' ),  'SavePlacement' )) {
					break;
				}

				if( !$this->validateFormSlugline( 'SavePlacement' ) ) {
					break;
				}

				if( !$this->deletePlacementObject( false, array('Workflow') ) ) {
					break;
				}

				if( !$this->validateFormAndArticleVersions( array( 'Trash' ), 'DeletePlacement' )) {
					break;
				}

				if( !$this->validateFormSlugline( 'DeletePlacement' )) {
					break;
				}

				if( !$this->restorePlacementObject() ) {
					break;
				}

				if( !$this->validateFormAndArticleVersions( array( 'Workflow' ), 'RestorePlacement' )) {
					break;
				}

				if( !$this->validateFormSlugline( 'RestorePlacement' )) {
					break;
				}

				if( !$this->deletePlacementObject( false, array('Workflow') ) ) { // Move to TrashCan.
					break;
				}

				if( !$this->deletePlacementObject( true, array('Trash') ) ) { // Purge permanently from the TrashCan.
					break;
				}

				if( !$this->validateFormAndArticleVersions( null, 'DeletePlacementPermanent' )) {
					break;
				}

				if( !$this->validateFormSlugLine('DeletePlacementPermanent' )) {
					break;
				}
			} while( false );
	
			// Remove test data.
			$this->tearDownTestData();
			
			// Remove the definitions (imported for the Publish Form Templates) from DB 
			// and remove the templates too.
			$this->removeDefinitions();
		}
				
		// End session nicely.
		BizSession::endSession();
	}

	/**
	 * Sets up the test structure.
	 *
	 * Creates testing objects, and imports definitions.
	 *
	 * @return bool
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		$retVal = true;

		// Collect all Publish Form Template objects imported by the plug-in.
		$pubChannelInfos = BizAdmPublication::getPubChannelInfosForPublishSystem( self::PUBLISH_PLUGIN );
		if( $pubChannelInfos ) foreach( $pubChannelInfos as $pubChannelInfo ) {
			$this->getPublishFormTemplates( $pubChannelInfo->Id, $this->templates );
		}
		
		// Check if our template was not found.
		if( isset( $this->templates[self::PUBLISH_TEMPLATE][$this->pubChannel->Id] ) ) {
			$template = $this->templates[self::PUBLISH_TEMPLATE][$this->pubChannel->Id];
		} else {
			$this->setResult( 'ERROR', 'Could not find Publish Form Template "'.self::PUBLISH_TEMPLATE.'".' );
			$retVal = false;
			$template = null;
		}

		// Create the Dossier.
		$stepInfo = 'Create the Dossier object.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo );
		if( is_null($this->dossier) ) {
			$this->setResult( 'ERROR', 'Could not create the Dossier object.' );
			$retVal = false;
		}

		// Create the Publish Form.
		$this->form = null;
		if( $template && $this->dossier ) {
			$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
			$this->form = $this->mcpUtils->createPublishFormObject( $template, $this->dossier, $stepInfo );
			if( is_null($this->form) ) {
				$this->setResult( 'ERROR', 'Could not create the Publish Form object.' );
				$retVal = false;
			}
		}

		// Create the Article.
		$stepInfo = 'Create the Article object.';
		$this->article = $this->mcpUtils->createArticle( $stepInfo );
		if( is_null($this->article) ) {
			$this->setResult( 'ERROR',  'Could not create the Article object.' );
			$retVal = false;
		}
		
		// Assign the Article to the Dossier.
		if( $this->dossier && $this->article ) {
			$dossierId = $this->dossier->MetaData->BasicMetaData->ID;
			$articleId = $this->article->MetaData->BasicMetaData->ID;
			$relation = MultiChannelPublishingUtils::composeContainedRelation( $dossierId, $articleId );
			if( is_null( $relation )) {
				$this->setResult( 'ERROR', 'Could not create Contained relation between Article and Dossier.' );
				$retVal = false;
			}
		}
		
		// Create the Layout object.
		$stepInfo = 'Create the Layout object.';
		$this->layout = $this->mcpUtils->createLayout( $stepInfo, null, false, 'web' ); // true = to lock the layout.
		if ( is_null( $this->layout )) {
			$this->setResult( 'ERROR', 'Could not create the Layout object.' );
			$retVal = false;
		}

		// Assign the Layout to the Dossier.
		if( $this->dossier && $this->article ) {
			$dossierId = $this->dossier->MetaData->BasicMetaData->ID;
			$layoutId = $this->layout->MetaData->BasicMetaData->ID;
			$stepInfo = 'Assign the Layout to the Dossier.';
			$relation = $this->mcpUtils->createRelationObject( $stepInfo, 
				$dossierId, $layoutId, 'Contained', $this->dossier->Targets );
			if( is_null( $relation )) {
				$this->setResult( 'ERROR', 'Could not create Contained relation between article and layout.' );
				$retVal = false;
			}
		}
		return $retVal;
	}

	/**
	 * Tears down the objects, remove definitions created in the {@link: setupTestData()} function.
	 *
	 * @return bool
	 */
	private function tearDownTestData()
	{
		$result = true;

		// Permanently delete the Article.
		if( $this->article ) {
			$errorReport = null;
			$id = $this->article->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Article object.';
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			// The article should be deleted permanently from the server, but if the BuildTest was not successful
			// chances are the article could still be in the Workflow or Trash area, so check both areas and
			// delete it if the article still exists.
			if( DBObject::objectExists( $id, 'Trash' ) ) {
				if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array('Trash') ) ) {
					$this->setResult( 'ERROR', 'Could not tear down Publish Form object from the TrashCan: '.$errorReport );
					$result = false;
				}
			} else if( DBObject::objectExists( $id, 'Workflow' ) ) {
				if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array('Workflow') ) ) { // Deleting permanently from TrashCan instead of from Workflow.
					$this->setResult( 'ERROR', 'Could not tear down Publish Form object from the Workflow area: '.$errorReport );
					$result = false;
				}
			}
			$this->article = null;
		}

		// Permanently delete the Layout.
		if( $this->layout ) {
			$errorReport = null;
			$id = $this->layout->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Layout object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not remove the Layout. object '.$errorReport );
				$result = false;
			}
			$this->layout = null;
		}

		// Permanently delete the Publish Form.
		if( $this->form ) {
			$errorReport = null;
			$id = $this->form->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not remove the Publish Form object. '.$errorReport );
				$result = false;
			}
			$this->form = null;
		}

		// Permanently delete the Dossier.
		if( $this->dossier ) {
			$errorReport = null;
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not remove the Dossier object. '.$errorReport );
				$result = false;
			}
			$this->dossier = null;
		}

		return $result;
	}

	/**
	 *  Imports the custom properties, Publish Form Templates and dialog of the server plug-in
	 *  named "MultiChannelPublishingSample".
	 *
	 * @return bool Whether or not the imports were successful.
	 */
	private function importDefinitions()
	{
		$result = true;

		// Compose the application class name, for example: AdminWebAppsSample_App2_EnterpriseWebApp
		$appClassName = self::PUBLISH_PLUGIN . '_ImportDefinitions_EnterpriseWebApp';
		$pluginPath = BASEDIR.'/config/plugins/'.self::PUBLISH_PLUGIN;
		$includePath = $pluginPath.'/webapps/'.$appClassName.'.class.php';

		if( file_exists( $includePath ) ) {
			require_once $includePath;
			$webApp = new $appClassName;

			try {
				$webApp->importDefinitions();
			} catch( BizException $e ) {
				$this->setResult( 'ERROR', 'Failed to import Definitions. '.$e->getMessage() );
				$result = false;
			}
		} else {
			$this->setResult( 'ERROR',  'No such PHP module available:' . $includePath,
				'Please check in the "'.self::PUBLISH_PLUGIN.'" plugin.</br>' );
			$result = false;
		}

		return $result;
	}
	
	/**
	 * Retrieve Publish Form Templates via NamedQuery and GetObjects service calls.
	 *
	 * @param int $pubChannelId
	 * @param array $templates The tempate objects found. Two keys are used: $template[Name][PubChannelId] = Object
	 */
	private function getPublishFormTemplates( $pubChannelId, &$templates )
	{
		do {
			// Search for all Publish Form Template for the given channel.
			require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
			$request = new WflNamedQueryRequest();
			$request->Ticket	= $this->ticket;
			$request->Query		= 'PublishFormTemplates';
			$queryParam = new QueryParam();
			$queryParam->Property = 'PubChannelId';
			$queryParam->Operation = '=';
			$queryParam->Value = $pubChannelId;
			$request->Params = array( $queryParam );
	
			$stepInfo = 'Search for Publish Form Templates within a channel.';
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( !$response ) {
				break; // error already reported.
			}
	
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
			
			// Lookup the template we expect are searching for.
			foreach( $response->Rows as $row ) {
				$templateId    = $row[$indexes['ID']];
				$templateName  = $row[$indexes['Name']];
			
				// Retrieve the Publish Form Template from DB.
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
		
				$stepInfo = 'Retrieve the Publish Form Template "'.$templateName.'".';
				$response = $this->utils->callService( $this, $request, $stepInfo );
				
				// Skip when the template could not be retrieved.
				if( !isset($response->Objects[0]) ) {
					continue; // error is already logged
				}
				$templates[$templateName][$pubChannelId] = $response->Objects[0];
			}
		} while( False );
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
				$stepInfo = 'Tear down the Publish Form Template object.';
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
	 * Lock the Form by calling GetObjects service call.
	 *
	 * @return bool Whether or not the the lock was successful.
 	 */
	private function lockTheForm()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->form->MetaData->BasicMetaData->ID );
		$request->Lock = true;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'MetaData' );
		$request->HaveVersions = null;
		$request->Areas = array( 'Workflow' );
		$request->EditionId = null;
		
		$stepInfo = 'Lock the Publish Form object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return (bool)$response;
	}

	/**
	 * Places an article on the Form and save the Form by calling SaveObjects service.
	 * During the save operation, the form is unlocked.
	 *
	 * @return bool Whether or not the save/unlock operation was successful.
	 */
	private function placeArticleOnFormAndCheckIn()
	{
		$template = $this->templates[self::PUBLISH_TEMPLATE][$this->pubChannel->Id];
		
		$target = new Target();
		$target->PubChannel = new PubChannel();
		$target->PubChannel->Id = $this->pubChannel->Id;
		$target->PubChannel->Name = $this->pubChannel->Name;
		$target->Issue = new Issue();
		$target->Issue->Id = $this->issue->Id;
		$target->Issue->Name = $this->issue->Name;
		$target->Issue->OverrulePublication = null;
		$target->Editions = null;
		$target->PublishedDate = null;
		$target->PublishedVersion = null;

		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->form->MetaData->BasicMetaData->ID;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->form->MetaData->BasicMetaData->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'PublishForm';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->form->MetaData->BasicMetaData->Publication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->form->MetaData->BasicMetaData->Category;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$request->Objects[0]->MetaData->SourceMetaData->Source = '';
		$request->Objects[0]->MetaData->SourceMetaData->Author = '';
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = null;
		$request->Objects[0]->MetaData->ContentMetaData->Columns = null;
		$request->Objects[0]->MetaData->ContentMetaData->Width = null;
		$request->Objects[0]->MetaData->ContentMetaData->Height = null;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = null;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = null;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = null;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2013-04-18T21:53:43';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->form->MetaData->WorkflowMetaData->State;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = $this->form->MetaData->ExtraMetaData;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $template->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Child = $this->form->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Type = 'InstanceOf';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;

		$request->Objects[0]->Relations[1] = new Relation();
		$request->Objects[0]->Relations[1]->Parent = $this->dossier->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[1]->Child = $this->form->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[1]->Type = 'Contained';
		$request->Objects[0]->Relations[1]->Placements = array();
		$request->Objects[0]->Relations[1]->ParentVersion = null;
		$request->Objects[0]->Relations[1]->ChildVersion = null;
		$request->Objects[0]->Relations[1]->Rating = null;
		$request->Objects[0]->Relations[1]->Targets = array( $target );
		$request->Objects[0]->Relations[1]->ParentInfo = null;
		$request->Objects[0]->Relations[1]->ChildInfo = null;

		$request->Objects[0]->Relations[2] = new Relation();
		$request->Objects[0]->Relations[2]->Parent = $this->form->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[2]->Child = $this->article->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[2]->Type = 'Placed';
		$request->Objects[0]->Relations[2]->Placements = array();
		$request->Objects[0]->Relations[2]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[2]->Placements[0]->Page = null;
		$request->Objects[0]->Relations[2]->Placements[0]->Element = null;
		$request->Objects[0]->Relations[2]->Placements[0]->ElementID = '60ad0453-345f-b396-2d9d-45f06d32562d';
		$request->Objects[0]->Relations[2]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->FrameID = '';
		$request->Objects[0]->Relations[2]->Placements[0]->Left = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->Top = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->Width = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->Height = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->Overset = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->OversetChars = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->OversetLines = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->Layer = '';
		$request->Objects[0]->Relations[2]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[2]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[2]->Placements[0]->ContentDx = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->ContentDy = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->ScaleX = 1;
		$request->Objects[0]->Relations[2]->Placements[0]->ScaleY = 1;
		$request->Objects[0]->Relations[2]->Placements[0]->PageSequence = 0;
		$request->Objects[0]->Relations[2]->Placements[0]->PageNumber = '';
		$request->Objects[0]->Relations[2]->Placements[0]->Tiles = null;
		$request->Objects[0]->Relations[2]->Placements[0]->FormWidgetId = 'C_MCPSAMPLE_BODYTEXT';
		$request->Objects[0]->Relations[2]->ParentVersion = null;
		$request->Objects[0]->Relations[2]->ChildVersion = null;
		$request->Objects[0]->Relations[2]->Rating = null;
		$request->Objects[0]->Relations[2]->Targets = array( $target );
		$request->Objects[0]->Relations[2]->ParentInfo = null;
		$request->Objects[0]->Relations[2]->ChildInfo = null;

		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = '';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/../testdata/rec#001_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;

		$element = new Element();
		$element->Snippet = 'the body';
		$element->ID = '60ad0453-345f-b396-2d9d-45f06d32562d';
		$element->Name = 'body';
		$element->Version = 'dd406ee8-0e0f-e147-6857-75da780f2b58';
		$element->LengthChars = 8;
		$element->LengthLines = 0;
		$element->LengthParas = 1;
		$element->LengthWords = 2;

		$request->Objects[0]->Elements = array( $element );
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		$stepInfo = 'Place Article on Publish Form object.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return (bool)$response;
	}

	/**
	 * Opens the layout and lock the layout by calling GetObjects service call.
	 *
	 * @return bool Whether or the operation was successful.
	 */
	private function getLayoutAndCheckOut()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $this->layout->MetaData->BasicMetaData->ID );
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->RequestInfo = array();
		$request->RequestInfo[0] = 'Relations';
		$request->RequestInfo[1] = 'Targets';
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;

		$stepInfo = 'Retrieve the Layout from DB and lock for editing.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return (bool)$response;
	}

	/**
	 * Place an Article on a Layout by calling CreateObjectRelations service call.
	 *
	 * @return bool Whether or the operation was successful.
 	 */
	private function placeArticleOntoLayout()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->layout->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Child = $this->article->MetaData->BasicMetaData->ID;
		$request->Relations[0]->Type = 'Placed';
		$request->Relations[0]->Placements = array();
		$request->Relations[0]->Placements[0] = new Placement();
		$request->Relations[0]->Placements[0]->Page = 1;
		$request->Relations[0]->Placements[0]->Element = 'body';
		$request->Relations[0]->Placements[0]->ElementID = '60ad0453-345f-b396-2d9d-45f06d32562d';
		$request->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Relations[0]->Placements[0]->FrameID = '241';
		$request->Relations[0]->Placements[0]->Left = 0;
		$request->Relations[0]->Placements[0]->Top = 0;
		$request->Relations[0]->Placements[0]->Width = 0;
		$request->Relations[0]->Placements[0]->Height = 0;
		$request->Relations[0]->Placements[0]->Overset = null;
		$request->Relations[0]->Placements[0]->OversetChars = 8;
		$request->Relations[0]->Placements[0]->OversetLines = 1;
		$request->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Relations[0]->Placements[0]->Content = '';
		$request->Relations[0]->Placements[0]->Edition = null;
		$request->Relations[0]->Placements[0]->ContentDx = null;
		$request->Relations[0]->Placements[0]->ContentDy = null;
		$request->Relations[0]->Placements[0]->ScaleX = null;
		$request->Relations[0]->Placements[0]->ScaleY = null;
		$request->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Relations[0]->Placements[0]->Tiles = array();
		$request->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;

		$stepInfo = 'Place an Article on the Layout.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return (bool)$response;
	}

	/**
	 * Saves the Layout by calling SaveObjects service call.
	 *
	 * @return wflResponse
	 */
	private function checkinLayout()
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layout->MetaData->BasicMetaData->ID;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:A970C7BF66206811A531935ADCC0E2BE';
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->layout->MetaData->BasicMetaData->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = $this->layout->MetaData->BasicMetaData->Publication;
		$request->Objects[0]->MetaData->BasicMetaData->Category = $this->layout->MetaData->BasicMetaData->Category;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 303104;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->layout->MetaData->WorkflowMetaData->State;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->layout->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Child = $this->article->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Type = 'Placed';
		$request->Objects[0]->Relations[0]->Placements = array();
		$request->Objects[0]->Relations[0]->Placements[0] = new Placement();
		$request->Objects[0]->Relations[0]->Placements[0]->Page = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->Element = 'body';
		$request->Objects[0]->Relations[0]->Placements[0]->ElementID = '60ad0453-345f-b396-2d9d-45f06d32562d';
		$request->Objects[0]->Relations[0]->Placements[0]->FrameOrder = 0;
		$request->Objects[0]->Relations[0]->Placements[0]->FrameID = '241';
		$request->Objects[0]->Relations[0]->Placements[0]->Left = 36;
		$request->Objects[0]->Relations[0]->Placements[0]->Top = 124;
		$request->Objects[0]->Relations[0]->Placements[0]->Width = 523;
		$request->Objects[0]->Relations[0]->Placements[0]->Height = 681;
		$request->Objects[0]->Relations[0]->Placements[0]->Overset = -482.200273;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetChars = -94;
		$request->Objects[0]->Relations[0]->Placements[0]->OversetLines = -46;
		$request->Objects[0]->Relations[0]->Placements[0]->Layer = 'Layer 1';
		$request->Objects[0]->Relations[0]->Placements[0]->Content = '';
		$request->Objects[0]->Relations[0]->Placements[0]->Edition = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDx = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ContentDy = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleX = null;
		$request->Objects[0]->Relations[0]->Placements[0]->ScaleY = null;
		$request->Objects[0]->Relations[0]->Placements[0]->PageSequence = 1;
		$request->Objects[0]->Relations[0]->Placements[0]->PageNumber = '1';
		$request->Objects[0]->Relations[0]->Placements[0]->Tiles = array();
		$request->Objects[0]->Relations[0]->Placements[0]->FormWidgetId = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '1';
		$request->Objects[0]->Pages[0]->PageOrder = 1;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#009_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#009_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[0]->Orientation = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#009_att#002_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#009_att#003_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#009_att#004_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;

		// Target
		$target = new Target();
		$target->PubChannel = new PubChannel();
		$target->PubChannel->Id = $this->pubChannel->Id;
		$target->PubChannel->Name = $this->pubChannel->Name;
		$target->Issue = new Issue();
		$target->Issue->Id = $this->issue->Id;
		$target->Issue->Name = $this->issue->Name;
		$target->Issue->OverrulePublication = null;
		$target->Editions = null;
		$target->PublishedDate = null;
		$target->PublishedVersion = null;

		$request->Objects[0]->Targets = array( $target );
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		$stepInfo = 'Checkin Layout.';
		return $this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * Checks if the SaveObjects response do return a DuplicatePlacement warning.
	 * Shows Error on BuildTest when DuplicatePlacement warning is found.
	 *
	 * @param $saveLayoutResponse
	 * @return bool TRUE when ok (no Duplicate Placement found), else FALSE.
	 */
	private function checksForDuplicateWarning( $saveLayoutResponse )
	{
		$retVal = true;
		$id = $this->layout->MetaData->BasicMetaData->ID;
		if( isset( $saveLayoutResponse->Objects[0] ) ) {
			$layout = $saveLayoutResponse->Objects[0];
			if( isset( $layout->MessageList ) && $layout->MessageList->Messages ) {
				foreach( $layout->MessageList->Messages as $message ) {
					if( $message->MessageTypeDetail == 'DuplicatePlacement' ) {
						$this->setResult( 'ERROR', 'Duplicate placement warning was raised during ' .
							'save Layout. An article is placed on the Layout and the Publish Form '.
							'but this should not be seen as duplicate placement, which is unexpected. ' .
							'Please check on the SaveObjects service call for layout (id="'.$id.'")' );
						$retVal = false;
						break;
					}
				}
			}
		} else {
			$this->setResult( 'ERROR', 'There is nothing to validate on Duplicate warnings. '.
				'No response found in SaveObjects response.',
				'Please check SaveObjects service call for layout (id="'.$id.'")' );
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Save the placement object on the PublishForm.
	 *
	 * Checks out the placement object(article), modify and check it in again.
	 * @return bool
	 */
	private function savePlacementObject()
	{
		$result = true;

		$this->recordInitialObjectVersion();

		// Lock the article
		$user = $this->vars['BuildTest_MultiChannelPublishing']['testOptions']['User'];
		$this->article = BizObject::getObject( $this->article->MetaData->BasicMetaData->ID, $user, true, 'none', array( 'Elements', 'Targets') );

		// Modify the placement object(article)
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = '';
		$attachment->Content = null;
		$attachment->FilePath = '';
		$attachment->FileUrl = null;
		$attachment->EditionId = null;
		$inputPath = dirname(__FILE__).'/../testdata/rec#001_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $attachment );
		$this->article->Files = array( $attachment );

		// Save the placement object(article)
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array( $this->article );
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		$stepInfo = 'Save the placement object - The article.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( !$response ) {
			$result = false;
			$this->setResult( 'ERROR', 'Failed to save the placement object(article) on the PublishForm.' );
		}
		return $result;
	}

	/**
	 * Validates the version of the PublishForm and its placement object(article).
	 *
	 * @param null|array $areasWhereArticleResides 'Workflow' or 'Trash'
	 * @param string $context In which context should the validation be carried out.
	 * @return bool Whether or not the Publish Form- and Article versions are valid.
	 */
	private function validateFormAndArticleVersions( $areasWhereArticleResides=null, $context )
	{
		$result = true;
		$user = $this->vars['BuildTest_MultiChannelPublishing']['testOptions']['User'];

		$this->form = BizObject::getObject( $this->form->MetaData->BasicMetaData->ID, $user, false, 'none' );
		$latestFormVersion = $this->form->MetaData->WorkflowMetaData->Version;
		$latestArticleVersion = null;

		if( !is_null( $areasWhereArticleResides )) {
			$this->article = BizObject::getObject( $this->article->MetaData->BasicMetaData->ID, $user, false, 'none',
				array( 'Elements', 'Targets'), null, true, $areasWhereArticleResides );
			$latestArticleVersion = $this->article->MetaData->WorkflowMetaData->Version;
		}

		switch( $context ) {
			case 'SavePlacement':
				if( $latestFormVersion <= $this->initialFormVersion ) {
					$this->setResult( 'ERROR', 'SavePlacement:PublishForm version has not been updated after its '.
						'placement object(article) has been modified.' );
					$result = false;
				}
				if( $latestArticleVersion <= $this->initialArticleVersion ) {
					$this->setResult( 'ERROR', 'SavePlacement:Article version has not been updated after '.
						'the article has been modified.' );
					$result = false;
				}
				break;
			case 'DeletePlacement':
				if( $latestFormVersion <= $this->initialFormVersion ) {
					$this->setResult( 'ERROR', 'DeletePlacement:PublishForm version has not been updated after its '.
						'placement object(article) has been deleted(moved to the TrashCan).' );
					$result = false;
				}
				if( $latestArticleVersion != $this->initialArticleVersion ) {
					$this->setResult( 'ERROR', 'DeletePlacement:Article version has been changed which should not be '.
						'the case as the article has only been moved to the TrashCan.' );
					$result = false;
				}
				break;
			case 'RestorePlacement':
				if( $latestFormVersion <= $this->initialFormVersion ) {
					$this->setResult( 'ERROR', 'RestorePlacement:PublishForm version has not been updated after its '.
						'placement object(article) has been restored from the TrashCan.' );
					$result = false;
				}
				if( $latestArticleVersion != $this->initialArticleVersion ) {
					$this->setResult( 'ERROR', 'RestorePlacementArticle version has been changed which should not be '.
						'the case as the article has only been restored from the TrashCan.' );
					$result = false;
				}
				break;
			case 'DeletePlacementPermanent':
				if( $latestFormVersion != $this->initialFormVersion ) {
					$this->setResult( 'ERROR', 'DeletePlacementPermanent:PublishForm version has been updated after its '.
						'placement object(article) has been deleted permanently from the TrashCan. When the placement ' .
						'object on the PublishForm is purged permanently from the TrashCan, no new version should be ' .
						'created for the PublishForm as the new version has already been created when the placement ' .
						'object is moved to the TrashCan.' );
					$result = false;
				}
				// The Article has been permanently deleted, so nothing to check.
				break;
		}
		return $result;
	}

	/**
	 * Validates the slugline of the PublishForm.
	 *
	 * The slugline of the PublishForm is updated when we create a new version, this function validates that the slug-
	 * line was correctly set when there is a change in the placed object(s).
	 *
	 * @param string $context In which context should the validation be carried out.
	 * @return bool Whether or not the slugline is valid.
	 */
	private function validateFormSlugline( $context )
	{
		$result = true;
		$user = $this->vars['BuildTest_MultiChannelPublishing']['testOptions']['User'];

		$this->form = BizObject::getObject( $this->form->MetaData->BasicMetaData->ID, $user, false, 'none' );

		switch( $context ) {
			case 'DeletePlacement':
			case 'DeletePlacementPermanent':
				// The slugline should no longer contain the text from the article.
				$expectedSlugLine = '';
				break;
			case 'RestorePlacement':
			case 'SavePlacement':
				// The slugline should contain the text from the article slugline.
				$expectedSlugLine = 'the body';
				break;
			default :
				$expectedSlugLine = $this->form->MetaData->ContentMetaData->Slugline;
		}

		if ($this->form->MetaData->ContentMetaData->Slugline != $expectedSlugLine ) {
			$this->setResult( 'ERROR', $context . ': PublishForm slugline `' . $this->form->MetaData->ContentMetaData->Slugline .  '` does not match the expected slugline: '
				. $expectedSlugLine );
			$result = false;
		}

		return $result;
	}

	/**
	 * Delete the placement object(article) on the PublishForm.
	 *
	 * @param bool $permanent Whether to delete the article permanently.
	 * @param array $areas
	 * @return bool
	 */
	private function deletePlacementObject( $permanent, $areas )
	{
		$this->recordInitialObjectVersion( $areas );

		$result = true;
		$errorReport = null;
		$id = $this->article->MetaData->BasicMetaData->ID;
		$stepInfo = 'Remove the placement object(Article) from the PublishForm.';
		if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, $permanent, $areas ) ) {
			if( $permanent ) {
				$this->setResult( 'ERROR',  'Could not delete the Article permanently. '.$errorReport );
			} else {
				$this->setResult( 'ERROR',  'Could not move the Article object on the Form into the TrashCan. '.$errorReport );
			}
			$result = false;
		}
		return $result;
	}

	/**
	 * Restore the deleted placement object(article) from the TrashCan.
	 *
	 * @return bool
	 */
	private function restorePlacementObject()
	{
		$result = true;
		$this->recordInitialObjectVersion( array( 'Trash' ) );
		$articleId = $this->article->MetaData->BasicMetaData->ID;
		require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';
		$request = new WflRestoreObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $articleId );

		$stepInfo = 'Restore the placement object( article ) from the TrashCan.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( !$response ) {
			$result = false;
			$this->setResult( 'ERROR', 'Could not restore the placement object(article) from the TrashCan.' );
		}
		return $result;

	}

	/**
	 * To record/remember the initial PublishForm and the placement object(article) version.
	 *
	 * @param array $areasWhereArticleReside 'Workflow' or 'Trash' where the article can be found.
	 */
	private function recordInitialObjectVersion( $areasWhereArticleReside = array( 'Workflow') )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$user = $this->vars['BuildTest_MultiChannelPublishing']['testOptions']['User'];

		// Record the initial version of the PublishForm.
		$this->form = BizObject::getObject( $this->form->MetaData->BasicMetaData->ID, $user, false, 'none' );
		$this->initialFormVersion = $this->form->MetaData->WorkflowMetaData->Version;

		// Record the initial version of the Article.
		$this->article = BizObject::getObject( $this->article->MetaData->BasicMetaData->ID, $user, false, 'none',
			array( 'Elements', 'Targets'), null, true, $areasWhereArticleReside );
		$this->initialArticleVersion = $this->article->MetaData->WorkflowMetaData->Version;
	}

}

