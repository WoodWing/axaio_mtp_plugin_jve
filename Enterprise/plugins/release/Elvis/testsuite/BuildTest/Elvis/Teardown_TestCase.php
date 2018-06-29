<?php
/**
 * Elvis TestCase class that belongs to the BuildTest TestSuite of wwtest.
 *
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Elvis_Teardown_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries tearing down /clear up the testing environment. '; }
	public function getTestMethods() { return
		'<ol>
		 	<li>Activate the IdsAutomation and AutomatedPrintWorkflow server plug-ins (if the Setup has de-activated them).</li>
		 	<li>Logoff the user from Enterprise. (LogOff)</li>
		 </ol>'; }
	public function getPrio()        { return 1000; }

	final public function runTest()
	{
		// Initialize
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$utils->initTest( 'JSON' );

		// Read session data.
		$vars = $this->getSessionVariables();

		// Activate the IdsAutomation plugin when we did deactivate before.
		$deactivatedIdsAutomationPlugin = @$vars['BuildTest_Elvis']['deactivatedIdsAutomationPlugin'];
		if( $deactivatedIdsAutomationPlugin ) {
			$utils->activatePluginByName( $this, 'IdsAutomation' );
		}

		// Activate the IdsAutomation plugin when we did deactivate before.
		$deactivatedAutomatedPrintWorkflowPlugin = @$vars['BuildTest_Elvis']['deactivatedAutomatedPrintWorkflowPlugin'];
		if( $deactivatedAutomatedPrintWorkflowPlugin ) {
			$utils->activatePluginByName( $this, 'AutomatedPrintWorkflow' );
		}

		// LogOff when we did LogOn before.
		$ticket = @$vars['BuildTest_Elvis']['ticket'];
		if( $ticket ) {
			$utils->wflLogOff( $this, $ticket );
		}
	}
}