<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.3
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Analytics_Teardown_TestCase extends TestCase
{
	private $vars = null;
	private $ticket = null;
	private $publication = null;
	private $webIssue = null;
	private $webPubChannel = null;

	public function getDisplayName() { return 'Tear down Analytics test data'; }
	public function getTestGoals()   { return 'Tries logoff the user from Enterprise. '; }
	public function getTestMethods() { return 'Calls the LogOff workflow service at application server. '; }
	public function getPrio()        { return 500; }

	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		require_once BASEDIR. '/server/interfaces/services/adm/DataClasses.php';
		$this->vars = $this->getSessionVariables();

		$this->ticket        = @$this->vars['BuildTest_Analytics']['ticket'];
		$this->publication   = @$this->vars['BuildTest_Analytics']['publication'];
		$this->webIssue      = @$this->vars['BuildTest_Analytics']['webIssue'];
		$this->webPubChannel = @$this->vars['BuildTest_Analytics']['webPubChannel'];

		// Tear down the PubChannel and Issue that was created by the Setup test case.
		$this->tearDownPubChannelAndIssue();

		// Put Analytics back in original activated or deactivated state
		$activatedAnaTestPlugin = $this->vars['BuildTest_Analytics']['activatedAnaTestPlugin'];
		$deactivatedAnaPlugin = $this->vars['BuildTest_Analytics']['deactivatedAnaPlugin'];
		$activatedPublishingTestPlugin = $this->vars['BuildTest_Analytics']['activatedPublishingTestPlugin'];

		// Deactivate the AnalyticsTest plugin when we did activate before.
		if( $activatedAnaTestPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'AnalyticsTest' );
		}

		// Activate the Analytics plugin when we deactivated before.
		if( $deactivatedAnaPlugin ) {
			$this->utils->activatePluginByName( $this, 'Analytics' );
		}

		// Deactivate the Publishing Test plugin when we activated before.
		if( $activatedPublishingTestPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'Publishing Test' );
		}

		// Log off
		$this->utils->wflLogOff( $this, $this->ticket );
	}

	/**
	 * Removes the PubChannel and Issue created at
	 * {@link: WW_TestSuite_BuildTest_Analytics_Setup_TestCase::setupPubChannelAndIssue()}.
	 */
	private function tearDownPubChannelAndIssue()
	{
		if( !$this->publication ) {
			return;
		}
		// Delete the Issue.
		if( $this->webIssue ) {
			$this->utils->removeIssue( $this, $this->ticket,
				$this->publication->Id, $this->webIssue->Id );
		}

		// Delete the Publication Channel.
		if( $this->webPubChannel ) {
			$this->utils->removePubChannel( $this, $this->ticket,
				$this->publication->Id, $this->webPubChannel->Id );
		}
	}
}