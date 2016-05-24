<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_TargetHandling_Setup_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var WW_TestSuite_BuildTest_TargetHandling_Utils $localUtils */
	private $localUtils = null;

	/** @var array $testOptions */
	private $testOptions = null;

	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannel = null;

	/** @var AdmIssue[] $issueObj */
	private $issues = null;

	/** @var AdmEdition[] $editionObj */
	private $editions = null;

	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if the user (as configured at TESTSUITE option) can logon to Enterprise. '; }
	public function getTestMethods() { return 'Does logon through workflow services at application server. '; }
	public function getPrio()        { return 1; }

	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();
		$response = $this->globalUtils->wflLogOn( $this );
		if ( is_null( $response ) ) { return; }

		$this->testOptions = $this->globalUtils->parseTestSuiteOptions( $this, $response );
		$this->testOptions['ticket'] = $response->Ticket;
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/TargetHandling/Utils.class.php';
		$this->localUtils = new WW_TestSuite_BuildTest_TargetHandling_Utils();

		// Create a PubChannel with an Issue to let successor test cases work on it.
		if( !$this->setupPubChannel() ) { return; }
		if ( !$this->setupIssues() ) { return; }
		try {
			$this->setUpEditions();
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR',
				$e->getMessage(),
				'Please check the TestSuite settings in the configserver.php file.' );
		}

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this TestSuite.
		$vars = array();
		$vars['TargetHandling'] = $this->testOptions;
		$vars['TargetHandling']['ticket'] = $this->localUtils->getTicketFromTestOptions( $this->testOptions);
		$vars['TargetHandling']['brand'] = $this->localUtils->getPublFromTestOptions( $this->testOptions );
		$vars['TargetHandling']['category'] = $this->localUtils->getCategoryFromTestOptions( $this->testOptions );
		$vars['TargetHandling']['pubChannel'] = $this->pubChannel;
		$vars['TargetHandling']['issues'] = $this->issues;
		$vars['TargetHandling']['editions'] = $this->editions;
		$vars['TargetHandling']['layoutStatus'] = $this->pickObjectTypeStatus( 'Layout' );
		$vars['TargetHandling']['articleStatus'] = $this->pickObjectTypeStatus( 'Article' );
		$vars['TargetHandling']['dossierStatus'] = $this->pickObjectTypeStatus( 'Dossier' );
		$this->setSessionVariables( $vars );
	}

	/**
	 * Picks a status for a given object type that is configured for a given brand ($pubInfo).
	 * It prefers picking a non-personal status, but when none found and the Personal Status
	 * feature is enabled, that status is used as fall back. When none found an error is logged.
	 *
	 * @param string $objType
	 * @return State|null Picked status, or NULL when none found.
	 */
	private function pickObjectTypeStatus( $objType )
	{
		$pubInfo = $this->localUtils->getPublFromTestOptions( $this->testOptions );
		$objStatus = null;
		if ( $pubInfo->States ) foreach ( $pubInfo->States as $status ) {
			if ( $status->Type == $objType ) {
				$objStatus = $status;
				if ( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if ( !$objStatus ) {
			$this->setResult( 'ERROR',
				'Brand "'.$pubInfo->Name.'" has no '.$objType.' Status to work with.',
				'Please check the Brand Maintenance page and configure one.' );
		}
		return $objStatus;
	}

	/**
	 * Creates a PubChannel and Issue for print.
	 *
	 * @return bool Whether or not the creations were successful.
	 */
	private function setupPubChannel()
	{
		// Create a PubChannel.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$admPubChannel = new AdmPubChannel();
		$admPubChannel->Name = 'PubChannel '.$this->localUtils->getTimeStamp();
		$admPubChannel->Description = 'Created by Build Test class: '.__CLASS__;
		$admPubChannel->Type = 'print';
		$admPubChannel->PublishSystem = 'Enterprise';
		$pubChannelResp = $this->globalUtils->createNewPubChannel(
							$this,
							$this->localUtils->getTicketFromTestOptions( $this->testOptions ),
							$this->localUtils->getPublicationIdFromTestOptions( $this->testOptions ),
							$admPubChannel );
		if( isset( $pubChannelResp->PubChannels[0] ) ) {
			$this->pubChannel = $pubChannelResp->PubChannels[0];
			return true;
		}
		return false;
	}

	/*
	 * Creates two different issues for the same channel.
	 *
	 * @return bool Whether or not the creations were successful.
	 */
	private function setupIssues()
	{
		for ( $i = 1; $i < 3; $i++ ) {
			$issue = $this->setUpIssue( $i );
			if ( $issue ) {
				$this->issues[] = $issue;
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Creates an issue for the channel.
	 * Pre-requisite: channel is created.
	 *
	 * @param int $seqNr Used to make the issue name unique
	 * @return AdmIssue|bool
	 * @throws BizException
	 */
	private function setUpIssue( $seqNr )
	{
		if ( !$this->pubChannel ) { return false; }
		$admIssue = new AdmIssue();
		$admIssue->Name = 'Issue'.$seqNr.'_'. $this->localUtils->getTimeStamp();
		$admIssue->Description = 'Created by Build Test class: ' . __CLASS__;
		$admIssue->Activated = true;
		$issueResp = $this->globalUtils->createNewIssue(
			$this,
			$this->localUtils->getTicketFromTestOptions( $this->testOptions ),
			$this->localUtils->getPublicationIdFromTestOptions( $this->testOptions ),
			$this->pubChannel->Id,
			$admIssue );
		if ( isset($issueResp->Issues[0]) ) {
			require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
			return $issueResp->Issues[0];
		} else {
			return false;
		}
	}

	/**
	 * Creates two different editions for the same channel.
	 */
	private function setUpEditions()
	{
		$this->editions[] = $this->setupEdition(
			$this->localUtils->getPublicationIdFromTestOptions( $this->testOptions ),
			$this->pubChannel->Id,
			'Singapore' );
		$this->editions[] = $this->setupEdition(
			$this->localUtils->getPublicationIdFromTestOptions( $this->testOptions ),
			$this->pubChannel->Id,
			'Bangkok' );
	}

	/**
	 * Creates an edition.
	 *
	 * @param integer $publicationId
	 * @param integer $pubChannelId
	 * @param string $editionName
	 * @return AdmEdition
	 * @throws BizException Throws BizException on failure.
	 */
	private function setupEdition( $publicationId, $pubChannelId, $editionName )
	{
		$stepInfo = 'Creating editions for Automated Print Workflow.';
		$edition = new AdmEdition();
		$edition->Name = $editionName;
		$edition->Description = 'Created by BuildTest class '.__CLASS__;
		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';
		$request = new AdmCreateEditionsRequest();
		$request->Ticket = $this->localUtils->getTicketFromTestOptions( $this->testOptions );
		$request->PublicationId = $publicationId;
		$request->PubChannelId = $pubChannelId;
		$request->Editions = array( $edition );
		$request->IssueId = 0;
		$response = $this->globalUtils->callService( $this, $request, $stepInfo );
		$this->assertAttributeInternalType( 'array', 'Editions', $response );
		$this->assertAttributeCount( 1, 'Editions', $response ); // check $response->Editions[0]
		$this->assertInstanceOf( 'stdClass', $response->Editions[0] ); // TODO: should be AdmEdition
		return $response->Editions[0];
	}
}
