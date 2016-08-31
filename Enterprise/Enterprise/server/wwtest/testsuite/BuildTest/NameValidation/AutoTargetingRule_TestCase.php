<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_NameValidation_AutoTargetingRule_TestCase extends TestCase
{
	private $namePrefix = 'AutoTargetingRule_';
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite

	private $articles = null; // Test objects

	private $dossier = null;
	private $publication = null;
	private $channel = null;
	private $issue = null;
	private $channel2 = null;
	private $issue2 = null;
	private $state = null;
	private $category = null;

	private $activatedFbPlugin = null;
	private $transferServer = null; // BizTransferServer

	public function getDisplayName() { return 'AutoTargeting Rule'; }
	public function getTestGoals()   { return 'Checks if AutoTargeting Rule is correctly working'; }
	public function getPrio()        { return 101; }
	public function getTestMethods() { return
		 'Invoke the applyAutoTargetingRule() function and check if the targets are correct.
		 <ol>
		 	<li>Create test objects.</li>
		 	<li>The first article is added, the AutoTargeting is disabled and Facebook is added to the
		 		targets by adding it to	the extraTargets functionality of applyAutoTargetingRule</li>
		 	<li>The response of creating the article is checked if the correct target is returned,
		 		this should only be Facebook</li>
		 	<li>The second article is added, the AutoTargeting is enabled and Facebook is added to the targets by adding it to
		 		the extraTargets functionality of applyAutoTargetingRule</li>
		 	<li>The response of creating the article is checked if the correct target is returned,
		 		this should be Facebook and Print</li>
		 	<li>Teardown test objects.</li>
		 </ol>';
	}

	/**
	 * Run the data setup, AutoTargeting Rule test and the data tear down.
	 */
	final public function runTest()
	{
		try { // make sure the tearDownTestData is run, even if the test crashes.
			do {
				require_once BASEDIR.'/server/utils/TestSuite.php';
				$this->utils = new WW_Utils_TestSuite();

				require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
				$this->transferServer = new BizTransferServer();

				if( !$this->setupTestData() ) {
					break; //If the setupTestData fails don't continue
				}

				// Test setting standard properties on existing objects.
				if( !$this->checkAutoTargetingRule() ) {
					break;
				}
			} while (false);
		} catch( BizException $e ){
			$this->setResult( 'ERROR', 'The test crashed: ' . $e );
		};

		$this->tearDownTestData();
	}

	/**
	 * Creates objects for testing, the articles are used in the checkAutoTargetingRule() function
	 * to check if the autoTargetingRule is working or not. The function creates the following objects:
	 * - 2 channels with each 1 issue, 1 Facebook channel and 1 Print channel.
	 * - 1 dossier linked with the 2 created channels.
	 * - 2 articles linked to the dossier.
	 *
	 * @return bool Whether the setup is successful.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$this->vars = $this->getSessionVariables();
		$this->ticket = $this->vars['BuildTest_NV']['ticket'];
		$this->publication = $this->vars['BuildTest_NV']['Brand'];
		$retVal = true;

		if( !$this->vars || !$this->ticket || !$this->publication ){
			$this->setResult( 'ERROR', 'Some setup data is missing, please make sure that the "Setup test data"
				option has run successfully.' );
			return false;
		}

		do {
			// Activate AutoTargetingTest plugin (in case it was not activated already)
			$this->activatedFbPlugin = $this->utils->activatePluginByName( $this, 'Facebook' );
			if( is_null( $this->activatedFbPlugin ) ) {
				$this->setResult( 'ERROR', 'Facebook plugin is not active and could not be activated. The Facebook
					plugin is mandatory for this test.' );
				$retVal = false;
				break;
			}

			// Create a Facebook channel.
			$stepInfo = 'Create the Channel object.';
			$response = $this->utils->callService( $this, $this->getCreateChannelRequest(), $stepInfo);
			$this->channel = isset($response->PubChannels) ? reset($response->PubChannels) : null;

			if( is_null($this->channel) ) {
				$this->setResult( 'ERROR', 'Could not create the channel.' );
				$retVal = false;
				break;
			}

			// Create a issue.
			$stepInfo = 'Create the Issue object.';
			$response = $this->utils->callService( $this, $this->getCreateIssueRequest(), $stepInfo);
			$this->issue = isset($response->Issues) ? reset($response->Issues) : null;

			if( is_null($this->issue) ) {
				$this->setResult( 'ERROR', 'Could not create the issue.' );
				$retVal = false;
				break;
			}

			// Get the dossier state
			require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
			$response = BizAdmStatus::getStatuses( $this->publication->Id, null, 'Dossier' );
			$this->state = reset( $response );

			BizAdmStatus::restructureMetaDataStatusColor( $this->state->Id ,$this->state->Color );
			$this->state->DefaultRouteTo = 'print';

			// Create a category
			$this->category = $this->createCategory( $this->publication->Id, 'Create Category' );

			// Create a Dossier.
			$stepInfo = 'Create the Dossier object.';
			$response = $this->utils->callService( $this, $this->getCreateDossierRequest(), $stepInfo);
			$this->dossier = isset($response->Objects) ? reset($response->Objects) : null;
			if( is_null($this->dossier) ) {
				$this->setResult( 'ERROR', 'Could not create the Dossier.' );
				$retVal = false;
				break;
			}

			// Scenario 1 - Only use the ExtraTargets, AutoTargeting is denied.
			$stepInfo = 'Create the relation between the dossier and the channel.';
			$response = $this->utils->callService( $this, $this->getCreateObjectTargetsRequest(), $stepInfo);
			if( is_null($response->IDs) ) {
				$this->setResult( 'ERROR', 'Could not create the target for the Dossier.' );
				$retVal = false;
				break;
			}

			// Create Article 1 in the created dossier
			$stepInfo = 'Create Article1';
			$response = $this->utils->callService( $this, $this->getCreateArticleRequest(), $stepInfo);
			$this->articles[] = isset($response->Objects) ? reset($response->Objects) : null;
			if( is_null($this->articles[0]) ) {
				$this->setResult( 'ERROR', 'Could not create the Article.' );
				$retVal = false;
				break;
			}

			// Unlock the just created article 1
			$stepInfo = 'Unlock Article1';
			$this->utils->callService( $this, $this->getUnlockArticleRequest(), $stepInfo);

			// Scenario 2 - Add extra targets to the article and also accept AutoTargeting
			// Create a channel.
			$stepInfo = 'Create the Channel object.';
			$response = $this->utils->callService( $this, $this->getCreateChannel2Request(), $stepInfo);
			$this->channel2 = isset($response->PubChannels) ? reset($response->PubChannels) : null;

			if( is_null($this->channel2) ) {
				$this->setResult( 'ERROR', 'Could not create the channel.' );
				$retVal = false;
				break;
			}

			// Create a issue.
			$stepInfo = 'Create the Issue object.';
			$response = $this->utils->callService( $this, $this->getCreateIssue2Request(), $stepInfo);
			$this->issue2 = isset($response->Issues) ? reset($response->Issues) : null;

			if( is_null($this->issue2) ) {
				$this->setResult( 'ERROR', 'Could not create the issue2.' );
				$retVal = false;
				break;
			}

			$stepInfo = 'Create the relation between the dossier and the channel.';
			$response = $this->utils->callService( $this, $this->getCreateObjectTargets2Request(), $stepInfo);
			if( is_null($response->IDs) ) {
				$this->setResult( 'ERROR', 'Could not create the Relation.' );
				$retVal = false;
				break;
			}

			// Create Article 2 in the created dossier
			$stepInfo = 'Create Article2';
			$response = $this->utils->callService( $this, $this->getCreateArticleRequest(2), $stepInfo);
			$this->articles[] = isset($response->Objects) ? reset($response->Objects) : null;
			if( is_null($this->articles[1]) ) {
				$this->setResult( 'ERROR', 'Could not create the Article.' );
				$retVal = false;
				break;
			}

			// Unlock the just created article 2
			$stepInfo = 'Unlock Article2';
			$this->utils->callService( $this, $this->getUnlockArticleRequest( 2 ), $stepInfo);
		} while ( false );

		return $retVal;
	}

	/**
	 * Removes objects used for testing.
	 */
	private function tearDownTestData()
	{
		// Delete the created articles
		if( $this->articles ) {
			foreach( $this->articles as $object ) {
				$errorReport = null;
				$id = $object->MetaData->BasicMetaData->ID;
				if( !$this->utils->deleteObject( $this, $this->ticket, $object->MetaData->BasicMetaData->ID,
					'Delete article object', $errorReport )) {
					$this->setResult( 'ERROR',  'Could not tear down object with id '.$id.'.'.$errorReport );
				}
			}
			$this->articles = null;
		}

		// Delete the dossier.
		if( $this->dossier->MetaData->BasicMetaData->ID ){
			if( !$this->utils->deleteObject( $this, $this->ticket, $this->dossier->MetaData->BasicMetaData->ID,
				'Delete dossier object', $errorReport ) ) {
				$this->setResult( 'ERROR',
					'Could not tear down object with id ' . $this->dossier->MetaData->BasicMetaData->ID . '.' . $errorReport );
			}
		}

		// Delete the first issue.
		if( $this->issue->Id ){
			if( !$this->utils->removeIssue( $this, $this->ticket, $this->publication->Id, $this->issue->Id) ) {
				$this->setResult( 'ERROR',  'Could not tear down issue with id ' . $this->issue->Id . '.' . $errorReport );
			}
		}

		// Delete the second issue.
		if( $this->issue2->Id ){
			if( !$this->utils->removeIssue( $this, $this->ticket, $this->publication->Id, $this->issue2->Id) ) {
				$this->setResult( 'ERROR',  'Could not tear down issue with id ' . $this->issue2->Id . '.' . $errorReport );
			}
		}

		// Delete the Facebook channel.
		if( $this->channel->Id ){
			if( !$this->utils->removePubChannel( $this, $this->ticket, $this->publication->Id, $this->channel->Id) ) {
				$this->setResult( 'ERROR',  'Could not tear down channel with id ' . $this->channel->Id . '.' . $errorReport );
			}
		}

		// Delete the Print channel.
		if( $this->channel2->Id ){
			if( !$this->utils->removePubChannel( $this, $this->ticket, $this->publication->Id, $this->channel2->Id) ) {
				$this->setResult( 'ERROR',  'Could not tear down channel with id ' . $this->channel2->Id . '.' . $errorReport );
			}
		}

		// Delete the created category.
		if( $this->category->Id ){
			$this->deleteCategory();
		}

		// Deactivate Facebook plugin if it was activated by this test.
		if( $this->activatedFbPlugin ){
			$this->utils->deactivatePluginByName( $this, 'Facebook' );
		}
	}

	/**
	 * Check if the expected targets are returned.
	 * For the first article also check if there are no unwanted targets added to the article.
	 * For the second article check if also the Print channel had been added as a target.
	 *
	 * @return bool
	 */
	private function checkAutoTargetingRule()
	{
		$retVal = true;
		$article = $this->articles[0];
		$errorFirstPart = false;

		// We only expect exact 1 target, if there is none or there is more than 1 it should raise a error
		if( count( $article->Relations[0]->Targets ) != 1 ){
			$errorFirstPart = true;
		}

		// Get the target and the according channel.
		$target = reset($article->Relations[0]->Targets);

		require_once BASEDIR . '/server/dbclasses/DBAdmPubChannel.class.php';
		$channel = DBAdmPubChannel::getPubChannelObj($target->PubChannel->Id);

		// Check the type of the Target, This should be web and the PublishSystem should be Facebook.
		if( $channel->Type != 'web' || $channel->PublishSystem != 'Facebook' ) {
			$errorFirstPart = true;
		}

		if( $errorFirstPart ){
			$retVal = false;
			$errorMsg = 'Please look at the first part of the AutoTargetingRule_TestCase';
			$errorContext = 'Problem detected in Reports of first part in AutoTargetingRule.';
			$this->setResult( 'ERROR', $errorMsg, $errorContext );
		}

		// Check if expected targets can be found in the returned targets of the article
		$foundExpected1 = false;
		$foundExpected2 = false;
		$unexpectedAmount = false;
		$article = $this->articles[1];

		// We expect exact 2 targets, if there are more/less than 2 targets it should raise a error
		if( count( $article->Relations[0]->Targets ) != 2 ){
			$unexpectedAmount = true;
		}

		//Check if we have the wanted targets, 1 Facebook and 1 Print
		foreach( $article->Relations[0]->Targets as $target ){
			$channel = DBAdmPubChannel::getPubChannelObj($target->PubChannel->Id);
			if( $channel->Type == 'web' && $channel->PublishSystem == 'Facebook' ) {
				$foundExpected1 = true;
			} else if( $channel->Type == 'print') {
				$foundExpected2 = true;
			}
		}

		// If any of these is not correct we give a error on the second part
		if( !$foundExpected1 || !$foundExpected2 || $unexpectedAmount ) {
			$retVal = false;
			$errorMsg = '';
			$errorContext = 'Problem detected in Reports of second part in AutoTargetingRule.';
			$this->setResult( 'ERROR', $errorMsg, $errorContext );
		}

		return $retVal;
	}

	/**
	 * The request for creating a Channel
	 *
	 * @param int channelNumber
	 *
	 * @return AdmCreatePubChannelsRequest
	 */
	private function getCreateChannelRequest()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';

		$request = new AdmCreatePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publication->Id;
		$request->PubChannels = array();
		$request->PubChannels[0] = new AdmPubChannel();
		$request->PubChannels[0]->Id = 0;
		$request->PubChannels[0]->Name = $this->namePrefix . 'TestChannelName1';
		$request->PubChannels[0]->Type = 'web';
		$request->PubChannels[0]->Description = '';
		$request->PubChannels[0]->PublishSystem = 'Facebook';
		$request->PubChannels[0]->PublishSystemId = null;
		$request->PubChannels[0]->CurrentIssueId = '0';
		$request->PubChannels[0]->SuggestionProvider = '';
		$request->PubChannels[0]->ExtraMetaData = array();
		$request->PubChannels[0]->DirectPublish = null;
		$request->PubChannels[0]->SupportsForms = null;
		$request->PubChannels[0]->Issues = null;
		$request->PubChannels[0]->Editions = null;
		$request->PubChannels[0]->SupportsCropping = null;
		return $request;
	}

	/**
	 * The request for creating a Channel
	 *
	 * @param int channelNumber
	 *
	 * @return AdmCreatePubChannelsRequest
	 */
	private function getCreateChannel2Request()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';

		$request = new AdmCreatePubChannelsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publication->Id;
		$request->PubChannels = array();
		$request->PubChannels[0] = new AdmPubChannel();
		$request->PubChannels[0]->Id = 0;
		$request->PubChannels[0]->Name = $this->namePrefix . 'TestChannelName2';
		$request->PubChannels[0]->Type = 'print';
		$request->PubChannels[0]->Description = '';
		$request->PubChannels[0]->PublishSystem = '';
		$request->PubChannels[0]->PublishSystemId = null;
		$request->PubChannels[0]->CurrentIssueId = '0';
		$request->PubChannels[0]->SuggestionProvider = '';
		$request->PubChannels[0]->ExtraMetaData = array();
		$request->PubChannels[0]->DirectPublish = null;
		$request->PubChannels[0]->SupportsForms = null;
		$request->PubChannels[0]->Issues = null;
		$request->PubChannels[0]->Editions = null;
		$request->PubChannels[0]->SupportsCropping = null;
		return $request;
	}

	/**
	 * The request for creating a Issue
	 *
	 * @return AdmCreateIssuesRequest
	 */
	private function getCreateIssueRequest()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';

		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publication->Id;;
		$request->PubChannelId = $this->channel->Id;
		$request->Issues = array();
		$request->Issues[0] = new AdmIssue();
		$request->Issues[0]->Id = 0;
		$request->Issues[0]->Name = $this->namePrefix . 'TestIssueName1';
		$request->Issues[0]->Description = '';
		$request->Issues[0]->SortOrder = null;
		$request->Issues[0]->EmailNotify = null;
		$request->Issues[0]->ReversedRead = false;
		$request->Issues[0]->OverrulePublication = false;
		$request->Issues[0]->Deadline = '';
		$request->Issues[0]->ExpectedPages = 0;
		$request->Issues[0]->Subject = '';
		$request->Issues[0]->Activated = true;
		$request->Issues[0]->PublicationDate = '';
		$request->Issues[0]->ExtraMetaData = array();
		$request->Issues[0]->Editions = null;
		$request->Issues[0]->Sections = null;
		$request->Issues[0]->Statuses = null;
		$request->Issues[0]->UserGroups = null;
		$request->Issues[0]->Workflows = null;
		$request->Issues[0]->Routings = null;
		$request->Issues[0]->CalculateDeadlines = false;
		return $request;
	}

	/**
	 * The request for creating a Issue
	 *
	 * @return AdmCreateIssuesRequest
	 */
	private function getCreateIssue2Request()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';

		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publication->Id;;
		$request->PubChannelId = $this->channel2->Id;
		$request->Issues = array();
		$request->Issues[0] = new AdmIssue();
		$request->Issues[0]->Id = 0;
		$request->Issues[0]->Name = $this->namePrefix . 'TestIssueName2';
		$request->Issues[0]->Description = '';
		$request->Issues[0]->SortOrder = null;
		$request->Issues[0]->EmailNotify = null;
		$request->Issues[0]->ReversedRead = false;
		$request->Issues[0]->OverrulePublication = false;
		$request->Issues[0]->Deadline = '';
		$request->Issues[0]->ExpectedPages = 0;
		$request->Issues[0]->Subject = '';
		$request->Issues[0]->Activated = true;
		$request->Issues[0]->PublicationDate = '';
		$request->Issues[0]->ExtraMetaData = array();
		$request->Issues[0]->Editions = null;
		$request->Issues[0]->Sections = null;
		$request->Issues[0]->Statuses = null;
		$request->Issues[0]->UserGroups = null;
		$request->Issues[0]->Workflows = null;
		$request->Issues[0]->Routings = null;
		$request->Issues[0]->CalculateDeadlines = false;
		return $request;
	}

	/**
	 * Creates a new category.
	 *
	 * @return Category|null Null on error.
	 */
	public function createCategory()
	{
		try {
			$section = new AdmSection();
			$section->Name = $this->namePrefix . 'TestCategoryName';
			$stepInfo = 'Create Category';

			require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';
			$request = new AdmCreateSectionsRequest();
			$request->Ticket = $this->ticket;
			$request->RequestModes = array();
			$request->PublicationId =  $this->publication->Id;
			$request->IssueId = 0;
			$request->Sections = array( $section );
			$response = $this->utils->callService( $this, $request, $stepInfo );

			if ( !$response instanceof AdmCreateSectionsResponse ) {
				throw new BizException('ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not create Category: '. $section->Name );
			}

			$section = $response->Sections[0];
			$category = new Category();
			$category->Id = $section->Id;
			$category->Name = $section->Name;
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}

		return $category;
	}

	/**
	 * Deletes a category.
	 *
	 * @return Category|null Null on error.
	 */
	public function deleteCategory()
	{
		try {
			$stepInfo = 'Delete Category';

			require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
			$request = new AdmDeleteSectionsRequest();
			$request->Ticket = $this->ticket;
			$request->PublicationId = $this->publication->Id;
			$request->IssueId = 0;
			$request->SectionIds = array( $this->category->Id );
			$response = $this->utils->callService( $this, $request, $stepInfo );

			if ( !$response instanceof AdmDeleteSectionsResponse ) {
				throw new BizException('ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not delete Category' );
			}
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}

		return $response;
	}

	/**
	 * The request for creating a Dossier
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function getCreateDossierRequest()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';

		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->namePrefix . 'TestDossierName';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Dossier';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->publication->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->publication->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = '';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 0;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = $this->channel->Name;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = $this->state;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = null;
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->channel->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->channel->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issue->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issue->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = false;
		return $request;
	}

	/**
	 * The request for creating a ObjectTarget
	 *
	 * @return WflCreateObjectTargetsRequest
	 */
	private function getCreateObjectTargetsRequest()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';

		$request = new WflCreateObjectTargetsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->dossier->MetaData->BasicMetaData->ID;
		$request->Targets = array();
		$request->Targets[0] = new Target();
		$request->Targets[0]->PubChannel = new PubChannel();
		$request->Targets[0]->PubChannel->Id = $this->channel->Id;
		$request->Targets[0]->PubChannel->Name = $this->channel->Name;
		$request->Targets[0]->Issue = new Issue();
		$request->Targets[0]->Issue->Id = $this->issue->Id;
		$request->Targets[0]->Issue->Name = $this->issue->Name;
		$request->Targets[0]->Issue->OverrulePublication = null;
		$request->Targets[0]->Editions = null;
		$request->Targets[0]->PublishedDate = null;
		$request->Targets[0]->PublishedVersion = null;
		return $request;
	}

	/**
	 * The request for creating a ObjectTarget
	 *
	 * @return WflCreateObjectTargetsRequest
	 */
	private function getCreateObjectTargets2Request()
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';

		$request = new WflCreateObjectTargetsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->dossier->MetaData->BasicMetaData->ID;
		$request->Targets = array();
		$request->Targets[0] = new Target();
		$request->Targets[0]->PubChannel = new PubChannel();
		$request->Targets[0]->PubChannel->Id = $this->channel2->Id;
		$request->Targets[0]->PubChannel->Name = $this->channel2->Name;
		$request->Targets[0]->Issue = new Issue();
		$request->Targets[0]->Issue->Id = $this->issue2->Id;
		$request->Targets[0]->Issue->Name = $this->issue2->Name;
		$request->Targets[0]->Issue->OverrulePublication = null;
		$request->Targets[0]->Editions = null;
		$request->Targets[0]->PublishedDate = null;
		$request->Targets[0]->PublishedVersion = null;
		return $request;
	}

	private function getCreateArticleRequest( $articleNumber = 1 )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';

		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->namePrefix . 'TestArticleName' . $articleNumber;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->publication->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->publication->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Test HeadTest IntroTest Body';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 6;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 28;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 3;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Test HeadTest IntroTest Body';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 226638;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
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
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = 'WoodWing Software';
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = $this->dossier->MetaData->BasicMetaData->ID;
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__) . '/testdata/AutoTargetingRule_TestArticleName.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = '7d467ce2-7924-fc11-b55a-3d2c42d904f3';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 2;
		$request->Objects[0]->Elements[0]->LengthChars = 9;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = 'Test Head';
		$request->Objects[0]->Elements[0]->Version = '9f94291e-da64-4fc1-20ba-5768d61a1691';
		$request->Objects[0]->Elements[0]->Content = 'Test Head';
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = 'f34bf1e4-7438-b7d1-6c80-7c84c7c3cfea';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 2;
		$request->Objects[0]->Elements[1]->LengthChars = 10;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 1;
		$request->Objects[0]->Elements[1]->Snippet = 'Test Intro';
		$request->Objects[0]->Elements[1]->Version = 'a469d82f-ed8c-d34d-1d80-f414138cb0e8';
		$request->Objects[0]->Elements[1]->Content = 'Test Intro';
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = 'b7257f64-f3e3-6c40-a7ca-c8520ebf6e1b';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 2;
		$request->Objects[0]->Elements[2]->LengthChars = 9;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 1;
		$request->Objects[0]->Elements[2]->Snippet = 'Test Body';
		$request->Objects[0]->Elements[2]->Version = 'b7f90b9b-e849-a97f-7ae5-d768a87a65e4';
		$request->Objects[0]->Elements[2]->Content = 'Test Body';
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		return $request;
	}

	private function getUnlockArticleRequest( $articleNumber = 1 )
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';

		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->articles[$articleNumber - 1]->MetaData->BasicMetaData->ID;
		$request->ReadMessageIDs = null;
		$request->MessageList = null;
		return $request;
	}
}