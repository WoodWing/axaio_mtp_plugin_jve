<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_SkipIDSA_Setup_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Utils $localUtils */
	private $localUtils = null;

	/** @var string $ticket */
	private $ticket = null;

	/** @var array $testOptions */
	private $testOptions = null;

	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannel = null;

	/** @var PublicationInfo publication */
	private $publication = null;

	/** @var AdmIssue $issueObj */
	private $issue = null;

	public function getDisplayName()
	{
		return 'Setup test data';
	}

	public function getTestGoals()
	{
		return 'Checks if the user (as configured at TESTSUITE option) can logon to Enterprise. ';
	}

	public function getTestMethods()
	{
		return 'Does logon through workflow services at application server. ';
	}

	public function getPrio()
	{
		return 1;
	}

	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();
		$response = $this->globalUtils->wflLogOn( $this );

		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/InDesignServerAutomation/AutomatedPrintWorkflow/Utils.class.php';
		$this->localUtils = new WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Utils();

		$suiteOpts = unserialize( TESTSUITE );
		$category = null;
		$editions = array();
		$layoutStatus = null;
		$layoutTemplateStatus = null;
		$imageStatus = null;
		$articleStatus = null;
		$spreadsheetStatus = null;
		$dossierStatus = null;

		if( !is_null( $response ) ) {
			$this->ticket = $response->Ticket;

			// Determine the brand to work with
			if( count( $response->Publications ) > 0 ) {
				foreach( $response->Publications as $pub ) {
					if( $pub->Name == $suiteOpts['Brand'] ) {
						$this->publication = $pub;
						break;
					}
				}
			}

			$this->testOptions = $this->globalUtils->parseTestSuiteOptions( $this, $response );
			$this->testOptions['ticket'] = $this->ticket;

			if( $this->publication ) {
				// Simply pick the first Category of the Brand
				$category = count( $this->publication->Categories ) > 0 ? $this->publication->Categories[0] : null;
				if( !$category ) {
					$this->setResult( 'ERROR', 'Brand "'.$suiteOpts['Brand'].'" has no Category to work with.',
						'Please check the Brand setup and configure one.' );
					return;
				}

				// Create a PubChannel with an Issue to let successor test cases work on it.
				if( !$this->setupPubChannelAndIssue() ) {
					return;
				}

				// Pick a status for objects to work with.
				$layoutStatus = $this->pickObjectTypeStatus( $this->publication, 'Layout' );
				$layoutTemplateStatus = $this->pickObjectTypeStatus( $this->publication, 'LayoutTemplate' );
				$imageStatus = $this->pickObjectTypeStatus( $this->publication, 'Image' );
				$articleStatus = $this->pickObjectTypeStatus( $this->publication, 'Article' );

			} else {
				$this->setResult( 'ERROR', 'Could not find the test Brand: '.$suiteOpts['Brand'],
					'Please check the TESTSUITE setting in configserver.php.' );
				return;
			}
		}

		// Make sure the IdsAutomation plugin is active (enabled).
		$activatedIdsAutomationPlugin = $this->globalUtils->activatePluginByName( $this, 'IdsAutomation' );
		if( is_null( $activatedIdsAutomationPlugin ) ) { // Error during activation of the plugin, bail out.
			return;
		}

		// Make sure the AutomatedPrintWorkflow plugin is active (enabled).
		$activatedAutomatedPrintWorkflowPlugin = $this->globalUtils->activatePluginByName( $this, 'AutomatedPrintWorkflow' );
		if( is_null( $activatedAutomatedPrintWorkflowPlugin ) ) { // Error during activation of the plugin, bail out.
			return;
		}

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this TestSuite.
		$vars = array();
		$vars['BuildTest_SkipIDSA'] = $this->testOptions;
		$vars['BuildTest_SkipIDSA']['ticket'] = $this->ticket;

		$vars['BuildTest_SkipIDSA']['brand'] = $this->publication;
		$vars['BuildTest_SkipIDSA']['category'] = $category;
		$vars['BuildTest_SkipIDSA']['pubChannel'] = $this->pubChannel;
		$vars['BuildTest_SkipIDSA']['issue'] = $this->issue;
		$vars['BuildTest_SkipIDSA']['editions'] = $editions;

		$vars['BuildTest_SkipIDSA']['layoutStatus'] = $layoutStatus;
		$vars['BuildTest_SkipIDSA']['layoutTemplateStatus'] = $layoutTemplateStatus;
		$vars['BuildTest_SkipIDSA']['imageStatus'] = $imageStatus;
		$vars['BuildTest_SkipIDSA']['articleStatus'] = $articleStatus;
		$vars['BuildTest_SkipIDSA']['activatedIdsAutomationPlugin'] = $activatedIdsAutomationPlugin;
		$vars['BuildTest_SkipIDSA']['activatedAutomatedPrintWorkflowPlugin'] = $activatedAutomatedPrintWorkflowPlugin;
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
	 * Creates a PubChannel and Issue for print.
	 *
	 * @return bool Whether or not the creations were successful.
	 */
	private function setupPubChannelAndIssue()
	{
		$retVal = true;

		// Compose postfix for issue/channel names.
		$postfix = $this->localUtils->getTimeStamp();

		// Create a PubChannel.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$admPubChannel = new AdmPubChannel();
		$admPubChannel->Name = 'PubChannel '.$postfix;
		$admPubChannel->Description = 'Created by Build Test class: '.__CLASS__;
		$admPubChannel->Type = 'print';
		$admPubChannel->PublishSystem = 'Enterprise';
		$pubChannelResp = $this->globalUtils->createNewPubChannel( $this, $this->ticket, $this->publication->Id, $admPubChannel );
		$this->pubChannel = null;
		if( isset( $pubChannelResp->PubChannels[0] ) ) {
			$this->pubChannel = $pubChannelResp->PubChannels[0];
		} else {
			$retVal = false;
		}

		// Create an Issue for the PubChannel.
		$this->issue = null;
		if( $this->pubChannel ) {
			$admIssue = new AdmIssue();
			$admIssue->Name = 'Issue '.$postfix;
			$admIssue->Description = 'Created by Build Test class: '.__CLASS__;
			$admIssue->Activated = true;
			$issueResp = $this->globalUtils->createNewIssue( $this, $this->ticket,
				$this->publication->Id, $this->pubChannel->Id, $admIssue );
			if( isset( $issueResp->Issues[0] ) ) {
				require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
				$this->issue = $issueResp->Issues[0];
			} else {
				$retVal = false;
			}
		}
		return $retVal;
	}

}