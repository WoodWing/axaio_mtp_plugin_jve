<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Analytics_Setup_TestCase extends TestCase
{
	private $utils = null;
	private $anaUtils = null;
	private $testOptions = null;
	private $publication = null;
	private $ticket = null;
	private $webPubChannel = null;
	private $webIssue = null;

	public function getDisplayName() { return 'Setup Analytics test data'; }
	public function getTestGoals()   { return 'Checks if the user (as configured at TESTSUITE option) can logon to Enterprise. '; }
	public function getTestMethods() { return 'Does logon through workflow services at application server. '; }
	public function getPrio()        { return 1; }

	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$response = $this->utils->wflLogOn( $this );

		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/Analytics/AnalyticsUtils.class.php';
		$this->anaUtils = new AnalyticsUtils();

		$suiteOpts = unserialize( TESTSUITE );
		$userGroup = null;
		$testCategory = null;

		$articleStatus = null;
		$dossierStatus = null;

		if( !is_null($response) ) {
			$this->ticket = $response->Ticket;

			// Store first user group from user
			$userGroup = !empty( $response->UserGroups ) ? $response->UserGroups[0] : null;

			// Determine the brand to work with
			if( count($response->Publications) > 0 ) {
				foreach( $response->Publications as $pub ) {
					if( $pub->Name == $suiteOpts['Brand'] ) {
						$this->publication = $pub;
						break;
					}
				}
			}

			$this->testOptions = $this->utils->parseTestSuiteOptions( $this, $response );
			$this->testOptions['ticket'] = $this->ticket;

			if( $this->publication ) {
				// Simply pick the first Category of the Brand
				$testCategory = count( $this->publication->Categories ) > 0  ? $this->publication->Categories[0] : null;
				if( !$testCategory ) {
					$this->setResult( 'ERROR', 'Brand "'.$suiteOpts['Brand'].'" has no Category to work with.',
						'Please check the Brand setup and configure one.' );
					return;
				}

				// Lookup the first Print channel and lookup the configured issue/editions inside.
				$printTarget = $this->buildPrintTargetFromBrandSetup( $this->publication, $suiteOpts['Issue'] );
				if( is_null( $printTarget )) {
					return;
				}

				// Create a PubChannel with an Issue to let successor test cases work on it.
				if( !$this->setupPubChannelAndIssue() ) {
					return;
				}

				// Pick a status for Articles and Dossiers.
				$imageStatus   = $this->pickObjectTypeStatus( $this->publication, 'Image' );
				$articleStatus = $this->pickObjectTypeStatus( $this->publication, 'Article' );
				$dossierStatus = $this->pickObjectTypeStatus( $this->publication, 'Dossier' );

			} else {
				$this->setResult( 'ERROR', 'Could not find the test Brand: '.$suiteOpts['Brand'],
					'Please check the TESTSUITE setting in configserver.php.' );
				return;
			}
		}
		require_once BASEDIR . '/server/bizclasses/BizServerJobConfig.class.php';

		$serverJobConfig = new BizServerJobConfig();
		$enterpriseEventConfig = $serverJobConfig->findJobConfig( 'EnterpriseEvent', 'Enterprise');

		if (!$enterpriseEventConfig instanceof ServerJobConfig) {
			$this->setResult( 'ERROR', 'ServerJob configuration "EnterpriseEvent" '.
				'is not found', 'Please configure the "EnterpriseEvent" ServerJob' );
			return;
		}
		elseif ($enterpriseEventConfig->Active !== true) {
			$this->setResult( 'ERROR', 'ServerJob configuration "EnterpriseEvent" '.
				'is not active', 'Please activate the "EnterpriseEvent" ServerJob configuration' );
			return;
		}

		// Make sure the AnalyticsTest plugin is active (enabled).
		$activatedAnaTestPlugin = $this->utils->activatePluginByName( $this, 'AnalyticsTest' );
		if( is_null( $activatedAnaTestPlugin ) ) { // Error during activation of the plugin, bail out.
			return;
		}

		// Make sure that the Analytics plugin is -disabled-.
		$deactivatedAnaPlugin = $this->utils->deactivatePluginByName( $this, 'Analytics' );
		if ( is_null( $deactivatedAnaPlugin ) ) { // Couldn't deactivate the plugin, bail out.
			return;
		}

		// Make sure the AnalyticsTest plugin is active (enabled).
		$activatedPublishingTestPlugin = $this->utils->activatePluginByName( $this, 'PublishingTest' );
		if( is_null( $activatedPublishingTestPlugin ) ) { // Error during activation of the plugin, bail out.
			return;
		}

		// There should at least be one analytics connector for this build test (i.e. AnalyticsTest)
		$connectors = BizServerPlugin::searchConnectors('ObjectEvent', null);
		if( count($connectors) < 1 ) {
			$this->setResult( 'ERROR', 'Something wrong with amount of connectors found',
				'Please enable at least one Analytics connector before running this testcase.' );
			return;
		}

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this Analytics TestSuite.
		$vars = array();
		$vars['BuildTest_Analytics'] = $this->testOptions;
		$vars['BuildTest_Analytics']['ticket'] = $this->ticket;
		$vars['BuildTest_Analytics']['userGroup'] = $userGroup;

		$vars['BuildTest_Analytics']['publication'] = $this->publication;
		$vars['BuildTest_Analytics']['category'] = $testCategory;
		$vars['BuildTest_Analytics']['issue'] = $suiteOpts['Issue'];
		$vars['BuildTest_Analytics']['printTarget'] = $printTarget;
		$vars['BuildTest_Analytics']['webPubChannel'] = $this->webPubChannel;
		$vars['BuildTest_Analytics']['webIssue'] = $this->webIssue;

		$vars['BuildTest_Analytics']['imageStatus'] = $imageStatus;
		$vars['BuildTest_Analytics']['articleStatus'] = $articleStatus;
		$vars['BuildTest_Analytics']['dossierStatus'] = $dossierStatus;
		$vars['BuildTest_Analytics']['activatedAnaTestPlugin'] = $activatedAnaTestPlugin;
		$vars['BuildTest_Analytics']['deactivatedAnaPlugin'] = $deactivatedAnaPlugin;
		$vars['BuildTest_Analytics']['activatedPublishingTestPlugin'] = $activatedPublishingTestPlugin;
		$this->setSessionVariables( $vars );
	}

	/**
	 * Picks a status for a given object type that is configured for a given brand ($pubInfo).
	 * It prefers picking a non-personal status, but when none found and the Personal Status
	 * feature is enabled, that status is used as fall back. When none found an error is logged.
	 *
	 * @param PublicationInfo $pubInfo
	 * @param string $objType
	 * @return State|null Picked status, or NULL when none found.
	 */
	private function pickObjectTypeStatus( PublicationInfo $pubInfo, $objType )
	{
		$objStatus = null;
		if( $pubInfo->States ) foreach( $pubInfo->States as $status ) {
			if( $status->Type == $objType ) {
				$objStatus = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$objStatus ) {
			$this->setResult( 'ERROR',
				'Brand "'.$pubInfo->Name.'" has no '.$objType.' Status to work with.',
				'Please check the Brand Maintenance page and configure one.' );
		}
		return $objStatus;
	}

	/**
	 * Takes the first Print channel from the given brand setup (PublicationInfo)
	 * and looks up the given issue name. When found, it composes and returns a
	 * print target.
	 *
	 * @param PublicationInfo $testPub
	 * @param string $issueName
	 * @return Target
	 */
	private function buildPrintTargetFromBrandSetup( PublicationInfo $testPub, $issueName )
	{
		$printTarget = null;
		$testChannel = null;
		$testIssue = null;

		// Lookup the first Print channel and lookup the configured issue/editions inside.
		foreach( $testPub->PubChannels as $pubChannelInfo ) {
			if( $pubChannelInfo->Name == 'Print' ) {
				foreach( $pubChannelInfo->Issues as $issInfo ) {
					if( $issInfo->Name == $issueName )	{
						$testIssue = $issInfo;
						break;
					}
				}
				$testChannel = $pubChannelInfo;
				break;
			}
		}
		if( !$testChannel ) {
			$this->setResult( 'ERROR', 'Brand "'.$testPub->Name.'" has no '.
				'Print channel to work with.',
				'Please check the Brand setup and configure one.' );
		}
		if( !$testIssue ) {
			$this->setResult( 'ERROR', 'Brand "'.$testPub->Name.'" has no '.
				'Issue "'.$issueName.'" for the first Print channel to work with.',
				'Please check the Brand setup and configure one, '.
				'or check the TESTSUITE setting in configserver.php.' );
		}
		if( $testChannel && $testIssue ) {
			$printTarget = $this->composeTarget( $testChannel, $testIssue );
		}
		return $printTarget;
	}

	/**
	 * Builds a Target from given channel, issue and editions.
	 *
	 * @param PubChannelInfo $chanInfo
	 * @param IssueInfo $issueInfo
	 * @return Target $target
	 */
	private function composeTarget( PubChannelInfo $chanInfo, IssueInfo $issueInfo )
	{
		$pubChannel = new PubChannel();
		$pubChannel->Id = $chanInfo->Id;
		$pubChannel->Name = $chanInfo->Name;

		$issue = new Issue();
		$issue->Id   = $issueInfo->Id;
		$issue->Name = $issueInfo->Name;
		$issue->OverrulePublication = $issueInfo->OverrulePublication;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = $chanInfo->Editions;

		return $target;
	}

	/**
	 * Creates a PubChannel and Issue for the publish system "PublishingTest" (A testing plugin).
	 *
	 * @return bool Whether or not the creations were successful.
	 */
	private function setupPubChannelAndIssue()
	{
		$retVal = true;

		// Compose postfix for issue/channel names.
		$postfix = $this->anaUtils->getUniqueTimeStamp();

		// Create a PubChannel.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$admPubChannel = new AdmPubChannel();
		$admPubChannel->Name = 'PubChannel '.$postfix;
		$admPubChannel->Description = 'Created by Build Test class: '.__CLASS__;
		$admPubChannel->Type = 'web';
		$admPubChannel->PublishSystem = 'PublishingTest';
		$pubChannelResp = $this->utils->createNewPubChannel( $this, $this->ticket, $this->publication->Id, $admPubChannel );
		$this->webPubChannel = null;
		if( isset( $pubChannelResp->PubChannels[0] ) ) {
			$this->webPubChannel = $pubChannelResp->PubChannels[0];
		} else {
			$retVal = false;
		}

		// Create an Issue for the PubChannel.
		$this->webIssue = null;
		if( $this->webPubChannel ) {
			$admIssue = new AdmIssue();
			$admIssue->Name = 'Issue '.$postfix;
			$admIssue->Description = 'Created by Build Test class: '.__CLASS__;
			$admIssue->Activated = true;
			$issueResp = $this->utils->createNewIssue( $this, $this->ticket,
				$this->publication->Id, $this->webPubChannel->Id, $admIssue );
			if( isset( $issueResp->Issues[0] ) ) {
				require_once BASEDIR. '/server/interfaces/services/adm/DataClasses.php';
				$this->webIssue = $issueResp->Issues[0];
			} else {
				$retVal = false;
			}
		}
		return $retVal;
	}
}