<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Does setup some basic environment before testing the DPS Next integration.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps2_Setup_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	
	/** @var string $ticket */
	private $ticket = null;
	
	/** @var array $testOptions */
	private $testOptions = null;

	/** @var AdmPubChannel $pubChannel */
	private $pubChannel = null;

	/** @var AdmIssue $issue */
	private $issue = null;

	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if the basic environment can be setup properly.'; }
	public function getTestMethods() { return
		'Does setup the test environment as follows:
		 <ol>
		 	<li>LogOn the test user.</li>
		 	<li>Grab Brand, Category and Layout Status from LogOnResponse.</li>
		 	<li>Activate the AdobeDps2 plugin.</li>
		 	<li>Create a dps2 Publication Channel with and Issue and an iPad and an iPhone edition.</li>
		 </ol> '; }
    public function getPrio()        { return 1; }

	final public function runTest()
	{
		$editions = array();
		$layoutStatus = null;
		$readyToPublishLayoutStatus = null;
		$category = null;

		// Initialize.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// LogOn test user through workflow interface
		$response = $this->utils->wflLogOn( $this );
		if( is_null($response) ) {
			return;
		}
		$this->ticket = $response->Ticket;
		$this->testOptions = $this->utils->parseTestSuiteOptions( $this, $response );
		$this->testOptions['ticket'] = $this->ticket;

		// Activate AdobeDps2 plugin (only when not activated already).
		$activatedPlugin = $this->utils->activatePluginByName( $this, 'AdobeDps2' );
		if( is_null( $activatedPlugin ) ) {
			return;
		}

		try {
			if( $this->testOptions['Brand'] ) {
				$this->assertInstanceOf( 'PublicationInfo', $this->testOptions['Brand'] );
				$this->setupPubChannelAndIssue();

				$stepInfo = 'Creating edition for Adobe DPS.';
				$editions[] = $this->setupEdition( $stepInfo, $this->testOptions['Brand']->Id, $this->pubChannel->Id, 'iPad' );
				$editions[] = $this->setupEdition( $stepInfo, $this->testOptions['Brand']->Id, $this->pubChannel->Id, 'iPhone' );

				$readyToPublishLayoutStatus = $this->setupLayoutStatus( 'readyPublish'.date("m d H i s"), true, 0 );
				$this->assertInstanceOf( 'State', $readyToPublishLayoutStatus );

				$layoutStatus = $this->setupLayoutStatus( 'Layout'.date("m d H i s"), false, $readyToPublishLayoutStatus->Id );
				$this->assertInstanceOf( 'State', $layoutStatus );

				$category = count( $this->testOptions['Brand']->Categories ) > 0  ? $this->testOptions['Brand']->Categories[0] : null;
				$this->assertInstanceOf( 'CategoryInfo', $category );
			}
		} catch( BizException $e ) {
			// Catch the error so that it doesn't bail out, want to continue so that the settings that have been
			// created so far can be deleted in the TearDown class.
			/** @noinspection PhpSillyAssignmentInspection */
			$e = $e;
		}

		// Save info about our setup into the session data.
		// This data is picked up by successor TestCase modules within the TestSuite.
		$vars = array();
		$vars['BuildTest_AdobeDps2'] = $this->testOptions;
		$vars['BuildTest_AdobeDps2']['ticket'] = $this->ticket;
		$vars['BuildTest_AdobeDps2']['brand'] = $this->testOptions['Brand'];
		$vars['BuildTest_AdobeDps2']['activatedPlugin'] = $activatedPlugin;
		$vars['BuildTest_AdobeDps2']['apChannel'] = $this->pubChannel;
		$vars['BuildTest_AdobeDps2']['editions'] = $editions;
		$vars['BuildTest_AdobeDps2']['apIssue'] = $this->issue;
		$vars['BuildTest_AdobeDps2']['layoutStatus'] = $layoutStatus;
		$vars['BuildTest_AdobeDps2']['readyToPublishLayoutStatus'] = $readyToPublishLayoutStatus;
		$vars['BuildTest_AdobeDps2']['category'] = $category;

		$this->setSessionVariables( $vars );
	}

	/**
	 * Creates a PubChannel and Issue for the publish system Adobe DPS.
	 *
	 * @throws BizException Throws BizException on failure.
	 */
	private function setupPubChannelAndIssue()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php'; // AdmExtraMetaData

		// Compose postfix for issue/channel names.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;
		
		// Prepare custom admin properties for the PubChannel.
		$projectRefProp = new AdmExtraMetaData();
		$projectRefProp->Property = 'C_DPS2_CHANNEL_PROJECT';
		$projectRefProp->Values = array( 'ww_enterprise_dev' );

		$projectIdProp = new AdmExtraMetaData();
		$projectIdProp->Property = 'C_DPS2_CHANNEL_PROJECT_ID';
		$projectIdProp->Values = array( '5b983135-0595-4b3b-9629-adc4227236d7' );

		$artAccessProp = new AdmExtraMetaData();
		$artAccessProp->Property = 'C_DPS2_CHANNEL_ART_ACCESS';
		$artAccessProp->Values = array( 0 );

		$createCollsProp = new AdmExtraMetaData();
		$createCollsProp->Property = 'C_DPS2_CHANNEL_CREATE_COLLS';
		$createCollsProp->Values = array( true );

		// Create a PubChannel.
		require_once BASEDIR.'/config/plugins/AdobeDps2/utils/Folio.class.php';
		require_once BASEDIR.'/config/plugins/AdobeDps2/config.php'; // DPS2_PLUGIN_DISPLAYNAME
		$admPubChannel = new AdmPubChannel();
		$admPubChannel->Name = 'PubChannel '.$postfix;
		$admPubChannel->Description = 'Created by Build Test class: '.__CLASS__;
		$admPubChannel->Type = AdobeDps2_Utils_Folio::CHANNELTYPE;
		$admPubChannel->PublishSystem = DPS2_PLUGIN_DISPLAYNAME;
		$admPubChannel->ExtraMetaData = array( $projectRefProp, $projectIdProp, $artAccessProp, $createCollsProp );
		
		$pubChannelResp = $this->utils->createNewPubChannel( $this, $this->ticket, $this->testOptions['Brand']->Id, $admPubChannel );
		$this->assertCount( 1, $pubChannelResp->PubChannels );
		$this->pubChannel = $pubChannelResp->PubChannels[0];
		$this->assertInstanceOf( 'AdmPubChannel', $this->pubChannel );
		
		// Create an Issue for the PubChannel.
		$admIssue = new AdmIssue();
		$admIssue->Name = 'Issue '.$postfix;
		$admIssue->Description = 'Created by Build Test class: '.__CLASS__;
		$issueResp = $this->utils->createNewIssue( $this, $this->ticket, $this->testOptions['Brand']->Id, $this->pubChannel->Id, $admIssue );
		$this->assertCount( 1, $issueResp->Issues );
		$this->issue = $issueResp->Issues[0];
		$this->assertInstanceOf( 'AdmIssue', $this->issue );
	}

	/**
	 * Creates an edition.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $publicationId
	 * @param integer $pubChannelId
	 * @param string $editionName
	 * @return AdmEdition
	 * @throws BizException Throws BizException on failure.
	 */
	private function setupEdition( $stepInfo, $publicationId, $pubChannelId, $editionName )
	{
		$edition = new AdmEdition();
		$edition->Name = $editionName;
		$edition->Description = 'Created by BuildTest class '.__CLASS__;

		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';
		$request = new AdmCreateEditionsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->PubChannelId = $pubChannelId;
		$request->Editions = array( $edition );
		$request->IssueId = 0;

		$response = $this->utils->callService( $this, $request, $stepInfo );

		$this->assertAttributeInternalType( 'array', 'Editions', $response );
		$this->assertAttributeCount( 1, 'Editions', $response ); // check $response->Editions[0]
		$this->assertInstanceOf( 'stdClass', $response->Editions[0] ); // TODO: should be AdmEdition
		return $response->Editions[0];
	}

	/**
	 * Creates a new workflow status.
	 *
	 * @param string $name The workflow status name.
	 * @param bool $readyForPublishing Whether or not the status should be set as 'Ready to be Published'.
	 * @param int $nextStatusId The next status it should go when user selects 'SendToNext'. Default 0.
	 * @return State
	 */
	private function setupLayoutStatus( $name, $readyForPublishing, $nextStatusId=0 )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php'; // AdmStatus

		$publicationId = $this->testOptions['Brand']->Id;
		$issueId = 0; // It is not an overrule issue publication, so we leave this 0

		$newStatus = new AdmStatus();
		$newStatus->Id = null;
		$newStatus->Type = 'Layout';
		$newStatus->Phase = 'Production';
		$newStatus->Name = $name;
		$newStatus->Produce = false;
		$newStatus->Color = 'A0A0A0';
		$newStatus->NextStatus = new AdmIdName( $nextStatusId );
		$newStatus->SortOrder = '';
		$newStatus->SectionId = '';
		$newStatus->DeadlineRelative = '';
		$newStatus->CreatePermanentVersion = false;
		$newStatus->RemoveIntermediateVersions = false;
		$newStatus->AutomaticallySendToNext = false;
		$newStatus->ReadyForPublishing = $readyForPublishing;
		$newStatus->SkipIdsa = false;
		$layoutStatusIds = BizAdmStatus::createStatuses( $publicationId, $issueId, array($newStatus) );
		$layoutStatus = BizAdmStatus::getStatusWithId( $layoutStatusIds[0] );

		// Recompose to please the BuildTest.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		$newState = new State();
		$newState->Id = $layoutStatus->Id;
		$newState->Name = $layoutStatus->Name;
		$newState->Type = $layoutStatus->Type;
		$newState->Produce = $layoutStatus->Produce;
		$newState->Color = $layoutStatus->Color;
		$newState->DefaultRouteTo = null;

		return $newState;
	}
}
