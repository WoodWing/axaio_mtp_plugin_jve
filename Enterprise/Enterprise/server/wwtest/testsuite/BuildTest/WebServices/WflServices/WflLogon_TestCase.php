<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflLogon_TestCase extends TestCase
{
	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflServicesUtils */
	private $wflServicesUtils = null;

	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if the user (as configured at TESTSUITE option) can logon to Enterprise. It tries to resolve entities from the brand setup (as configured at TESTSUITE option). '; }
	public function getTestMethods() { return 'Does logon through workflow services at application server. The service response is used to lookup the Brand, Channel, Issue, Category and some object statuses. '; }
    public function getPrio()        { return 1; }
	
	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$response = $utils->wflLogOn( $this );

		$suiteOpts = unserialize( TESTSUITE );
		$ticket = null;
		$currentUser = null;
		$userGroup = null;

		$testPub = null;
		$testCategory = null;

		$printPubChannel = null;
		$printIssue = null;
		$printTarget = null;

		$imageStatus = null;
		$articleStatus = null;
		$dossierStatus = null;
		$layoutStatus = null;
		$articleTemplateStatus = null;
		
		if( !is_null($response) ) {
			$ticket = $response->Ticket;

			$currentUser = $response->CurrentUser;

			// Store first user group from user
			$userGroup = !empty( $response->UserGroups ) ? $response->UserGroups[0] : null;

			$testPub = $this->lookupPublicationInfo( $response, $suiteOpts['Brand'] );
			if( $testPub ) {
				$printPubChannel = $this->lookupPubChannelInfo( $testPub, 'Print' );
				if( $printPubChannel ) {
					$printIssue = $this->lookupIssueInfo( $printPubChannel, $suiteOpts['Issue'] );
					if( $printIssue ) {
						$printTarget = $this->composeTargetByLogOnInfo( $printPubChannel, $printIssue );
					}
				}

				// Simply pick the first Category of the Brand
				$testCategory = count( $testPub->Categories ) > 0  ? $testPub->Categories[0] : null;
				if( !$testCategory ) {
					$this->setResult( 'ERROR', 'Brand "'.$suiteOpts['Brand'].'" has no Category to work with.', 
						'Please check the Brand setup and configure one.' );
				}

				// Pick a status for Images, Articles and Dossiers.
				$imageStatus   = $this->pickObjectTypeStatus( $testPub, 'Image' );
				$articleStatus = $this->pickObjectTypeStatus( $testPub, 'Article' );
				$dossierStatus = $this->pickObjectTypeStatus( $testPub, 'Dossier' );
				$layoutStatus = $this->pickObjectTypeStatus( $testPub, 'Layout' );
				$articleTemplateStatus = $this->pickObjectTypeStatus( $testPub, 'ArticleTemplate' );
			}
		}

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this WflServices TestSuite.
		$vars = array();
		$vars['BuildTest_WebServices_WflServices']['ticket'] = $ticket;
		$vars['BuildTest_WebServices_WflServices']['currentUser'] = $currentUser;
		$vars['BuildTest_WebServices_WflServices']['userGroup'] = $userGroup;

		$vars['BuildTest_WebServices_WflServices']['publication'] = $testPub;
		$vars['BuildTest_WebServices_WflServices']['category'] = $testCategory;

		$vars['BuildTest_WebServices_WflServices']['printPubChannel'] = $printPubChannel;
		$vars['BuildTest_WebServices_WflServices']['printIssue'] = $printIssue;
		$vars['BuildTest_WebServices_WflServices']['printTarget'] = $printTarget;

		$vars['BuildTest_WebServices_WflServices']['imageStatus'] = $imageStatus;
		$vars['BuildTest_WebServices_WflServices']['articleStatus'] = $articleStatus;
		$vars['BuildTest_WebServices_WflServices']['dossierStatus'] = $dossierStatus;
		$vars['BuildTest_WebServices_WflServices']['layoutStatus'] = $layoutStatus;
		$vars['BuildTest_WebServices_WflServices']['articleTemplateStatus'] = $articleTemplateStatus;
		$this->setSessionVariables( $vars );

		// Create a web channel and issue, and compose a target from those.
		$activatedMcpPlugin = null;
		$webPubChannel = null;
		$webIssue = null;
		$webTarget = null;
		$activatedMcpPlugin = $utils->activatePluginByName( $this, 'MultiChannelPublishingSample' );
		if( !is_null( $activatedMcpPlugin ) ) {
			require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
			$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
			if( $this->wflServicesUtils->initTest( $this, 'BT', null, false ) ) {
				$webPubChannel = $this->createAdmPubChannel( $testPub->Id );
				if( $webPubChannel ) {
					$webIssue = $this->createAdmIssue( $testPub->Id, $webPubChannel->Id );
					if( $webIssue ) {
						$webTarget = $this->composeTargetByAdminInfo( $webPubChannel, $webIssue );
					}
				}
			}
		}
		$vars['BuildTest_WebServices_WflServices']['activatedMcpPlugin'] = $activatedMcpPlugin;
		$vars['BuildTest_WebServices_WflServices']['webPubChannel'] = $webPubChannel;
		$vars['BuildTest_WebServices_WflServices']['webIssue'] = $webIssue;
		$vars['BuildTest_WebServices_WflServices']['webTarget'] = $webTarget;
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
	 * Lookup a brand by name that is returned in the LogOn response.
	 *
	 * @param WflLogOnResponse $response
	 * @param string $brandName
	 * @return PublicationInfo|null
	 */
	private function lookupPublicationInfo( WflLogOnResponse $response, $brandName )
	{
		$foundInfo = null;
		if( $response->Publications ) foreach( $response->Publications as $publicationInfo ) {
			if( $publicationInfo->Name == $brandName ) {
				$foundInfo = $publicationInfo;
				break;
			}
		}
		if( !$foundInfo ) {
			$this->setResult( 'ERROR', 'Could not find the test Brand "'.$brandName.'".',
				'Please check the TESTSUITE setting in configserver.php.' );
		}
		return $foundInfo;
	}

	/**
	 * Lookup a PubChannelInfo by name that is configured for a given brand.
	 *
	 * @param PublicationInfo $publicationInfo
	 * @param string $pubChannelName
	 * @return PubChannelInfo|null
	 */
	private function lookupPubChannelInfo( PublicationInfo $publicationInfo, $pubChannelName )
	{
		$foundInfo = null;
		if( $publicationInfo->PubChannels ) foreach( $publicationInfo->PubChannels as $pubChannelInfo ) {
			if( $pubChannelInfo->Name == $pubChannelName ) {
				$foundInfo = $pubChannelInfo;
				break;
			}
		}
		if( !$foundInfo ) {
			$this->setResult( 'ERROR', 'Brand "'.$publicationInfo->Name.'" has no '.
				'Publication Channel named "'.$pubChannelName.'" to work with.',
				'Please check the Brand setup and configure one.' );
		}
		return $foundInfo;
	}

	/**
	 * Lookup a IssueInfo by name that is configured for a given channel.
	 *
	 * @param PubChannelInfo $pubChannelInfo
	 * @param string $issueName
	 * @return IssueInfo|null
	 */
	private function lookupIssueInfo( PubChannelInfo $pubChannelInfo, $issueName )
	{
		$foundInfo = null;
		if( $pubChannelInfo->Issues ) foreach( $pubChannelInfo->Issues as $issueInfo ) {
			if( $issueInfo->Name == $issueName )	{
				$foundInfo = $issueInfo;
				break;
			}
		}
		if( !$foundInfo ) {
			$this->setResult( 'ERROR', 'Publication Channel "'.$pubChannelInfo->Name.'" has no '.
				'Issue "'.$issueName.'" for the first Print channel to work with.',
				'Please check the Brand setup and configure one, '.
				'or check the TESTSUITE setting in configserver.php.' );
		}
		return $foundInfo;
	}

	/**
	 * Builds a Target from given channel, issue and editions.
	 *
	 * @param PubChannelInfo $chanInfo
	 * @param IssueInfo $issueInfo
	 * @return Target $target
	 */
	private function composeTargetByLogOnInfo( PubChannelInfo $chanInfo, IssueInfo $issueInfo )
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
	 * Creates publication channel for testing.
	 *
	 * @throws BizException on failure
	 * @param int $publicationId
	 * @return AdmPubChannel
	 */
	private function createAdmPubChannel( $publicationId )
	{
		$this->wflServicesUtils->setRequestComposer(
			function( AdmCreatePubChannelsRequest $req ) {
				$pubChannel = reset( $req->PubChannels );
				$pubChannel->Type = 'web';
				$pubChannel->PublishSystem = 'MultiChannelPublishingSample';
			}
		);
		$stepInfo = 'Creating channel for WflServices tests.';
		return $this->wflServicesUtils->createPubChannel( $stepInfo, $publicationId );
	}

	/**
	 * Creates publication channel for testing.
	 *
	 * @throws BizException on failure
	 * @param int $publicationId
	 * @param int $pubChannelId
	 * @return AdmIssue
	 */
	private function createAdmIssue( $publicationId, $pubChannelId )
	{
		$stepInfo = 'Creating channel for WflServices tests.';
		return $this->wflServicesUtils->createIssue( $stepInfo, $publicationId, $pubChannelId );
	}

	/**
	 * Builds a Target from given channel and issue.
	 *
	 * @param AdmPubChannel $admPubChannel
	 * @param AdmIssue $admIssue
	 * @return Target $target
	 */
	private function composeTargetByAdminInfo( AdmPubChannel $admPubChannel, AdmIssue $admIssue )
	{
		$pubChannel = new PubChannel();
		$pubChannel->Id = $admPubChannel->Id;
		$pubChannel->Name = $admPubChannel->Name;

		$issue = new Issue();
		$issue->Id   = $admIssue->Id;
		$issue->Name = $admIssue->Name;
		$issue->OverrulePublication = $admIssue->OverrulePublication;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = null;

		return $target;
	}
}