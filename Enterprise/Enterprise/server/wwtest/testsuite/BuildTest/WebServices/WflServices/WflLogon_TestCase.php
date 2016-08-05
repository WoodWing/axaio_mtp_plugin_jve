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
		$userGroup = null;
		$testPub = null;
		$testCategory = null;
		
		$imageStatus = null;
		$articleStatus = null;
		$dossierStatus = null;
		$articleTemplateStatus = null;
		
		if( !is_null($response) ) {
			$ticket = $response->Ticket;

			// Store first user group from user
			$userGroup = !empty( $response->UserGroups ) ? $response->UserGroups[0] : null;

			// Determine the brand to work with
			if( count($response->Publications) > 0 ) {
				foreach( $response->Publications as $pub ) {
					if( $pub->Name == $suiteOpts['Brand'] ) {
						$testPub = $pub;
						break;
					}
				}
			}
			
			if( $testPub ) {
				
				// Simply pick the first Category of the Brand
				$testCategory = count( $testPub->Categories ) > 0  ? $testPub->Categories[0] : null;
				if( !$testCategory ) {
					$this->setResult( 'ERROR', 'Brand "'.$suiteOpts['Brand'].'" has no Category to work with.', 
						'Please check the Brand setup and configure one.' );
				}

				// Lookup the first Print channel and lookup the configured issue/editions inside.
				$printTarget = $this->buildPrintTargetFromBrandSetup( $testPub, $suiteOpts['Issue'] );
				
				// Pick a status for Images, Articles and Dossiers.
				$imageStatus   = $this->pickObjectTypeStatus( $testPub, 'Image' );
				$articleStatus = $this->pickObjectTypeStatus( $testPub, 'Article' );
				$dossierStatus = $this->pickObjectTypeStatus( $testPub, 'Dossier' );
				$articleTemplateStatus = $this->pickObjectTypeStatus( $testPub, 'ArticleTemplate' );
		
			} else {
				$this->setResult( 'ERROR', 'Could not find the test Brand: '.$suiteOpts['Brand'], 
					'Please check the TESTSUITE setting in configserver.php.' );
			}
		}

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this WflServices TestSuite.
		$vars = array();
		$vars['BuildTest_WebServices_WflServices']['ticket'] = $ticket;
		$vars['BuildTest_WebServices_WflServices']['userGroup'] = $userGroup;
		
		$vars['BuildTest_WebServices_WflServices']['publication'] = $testPub;
		$vars['BuildTest_WebServices_WflServices']['category'] = $testCategory;
		$vars['BuildTest_WebServices_WflServices']['issue'] = $suiteOpts['Issue'];
		$vars['BuildTest_WebServices_WflServices']['printTarget'] = $printTarget;
		
		$vars['BuildTest_WebServices_WflServices']['imageStatus'] = $imageStatus;
		$vars['BuildTest_WebServices_WflServices']['articleStatus'] = $articleStatus;
		$vars['BuildTest_WebServices_WflServices']['dossierStatus'] = $dossierStatus;
		$vars['BuildTest_WebServices_WflServices']['articleTemplateStatus'] = $articleTemplateStatus;
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
}