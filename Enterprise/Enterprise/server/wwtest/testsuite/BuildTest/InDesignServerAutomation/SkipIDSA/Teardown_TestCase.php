<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_SkipIDSA_Teardown_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var string $ticket */
	private $ticket = null;

	/** @var Publication $publication */
	private $publication = null;

	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannel = null;

	/** @var AdmIssue $issueObj */
	private $issue = null;

	public function getDisplayName()
	{
		return 'Tear down test data';
	}

	public function getTestGoals()
	{
		return 'Tries logoff the user from Enterprise. ';
	}

	public function getTestMethods()
	{
		return 'Calls the LogOff workflow service at application server. ';
	}

	public function getPrio()
	{
		return 500;
	}

	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();

		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();

		$this->ticket = @$vars['BuildTest_SkipIDSA']['ticket'];
		$this->publication = @$vars['BuildTest_SkipIDSA']['brand'];
		$this->pubChannel = @$vars['BuildTest_SkipIDSA']['pubChannel'];
		$this->issue = @$vars['BuildTest_SkipIDSA']['issue'];

		// Tear down the PubChannel and Issue that was created by the Setup test case.
		$this->tearDownPubChannelAndIssue();

		// Deactivate the IdsAutomation plugin when we did activate before.
		$activatedIdsAutomationPlugin = @$vars['BuildTest_SkipIDSA']['activatedIdsAutomationPlugin'];
		if( $activatedIdsAutomationPlugin ) {
			$this->globalUtils->deactivatePluginByName( $this, 'IdsAutomation' );
		}

		// Deactivate the IdsAutomation plugin when we did activate before.
		$activatedAutomatedPrintWorkflowPlugin = @$vars['BuildTest_SkipIDSA']['activatedAutomatedPrintWorkflowPlugin'];
		if( $activatedAutomatedPrintWorkflowPlugin ) {
			$this->globalUtils->deactivatePluginByName( $this, 'AutomatedPrintWorkflow' );
		}

		// Log off
		$this->globalUtils->wflLogOff( $this, $this->ticket );
	}

	/**
	 * Removes the PubChannel and Issue created at
	 * {@link: WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Setup_TestCase::setupPubChannelAndIssue()}.
	 */
	private function tearDownPubChannelAndIssue()
	{
		if( !$this->publication ) {
			return;
		}

		if( $this->issue ) {
			$this->globalUtils->removeIssue( $this, $this->ticket,
				$this->publication->Id, $this->issue->Id );
		}

		if( $this->pubChannel ) {
			$this->globalUtils->removePubChannel( $this, $this->ticket,
				$this->publication->Id, $this->pubChannel->Id );
		}
	}
}