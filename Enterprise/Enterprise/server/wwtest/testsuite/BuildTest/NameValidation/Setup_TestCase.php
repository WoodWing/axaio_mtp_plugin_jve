<?php
/**
 * @since v9.5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_NameValidation_Setup_TestCase extends TestCase
{
	// Brand setup entities to work on, taken from LogOn response:
	private $testOptions = null;

	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if the basic environment can be setup properly.'; }
	public function getTestMethods() { return
		'Perform multiple services to setup the test environment.
		 <ol>
		 	<li>Make sure CopyrightValidationDemo plugin is installed and enabled.</li>
		 	<li>Logon user configured at TESTSUITE option in configserver.php.(LogOn)</li>
		 	<li>Retrieve all the necessary settings and set in the session variables.</li>
		 </ol> '; }
    public function getPrio()        { return 1; }
	
	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$response = $this->utils->wflLogOn( $this );

		$ticket = null;

		if( !is_null($response) ) {
			$ticket = $response->Ticket;
			$this->testOptions = $this->utils->parseTestSuiteOptions( $this, $response );
			$this->testOptions['ticket'] = $ticket;
		}

		// Activate NameValidationDemo Search plugin (in case it was not activated already)
		$activatedNVPlugin = $this->utils->activatePluginByName( $this, 'CopyrightValidationDemo' );
		if( is_null( $activatedNVPlugin ) ) {
			return;
		}

		// Activate AutoTargetingTest plugin (in case it was not activated already)
		$activatedAttPlugin = $this->utils->activatePluginByName( $this, 'AutoTargetingTest' );
		if( is_null( $activatedAttPlugin ) ) {
			return;
		}

		// Activate AutoNamingTest plugin (in case it was not activated already)
		$activatedAntPlugin = $this->utils->activatePluginByName( $this, 'AutoNamingTest' );
		if( is_null( $activatedAntPlugin ) ) {
			return;
		}

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this WflServices TestSuite.
		$vars = array();
		$vars['BuildTest_NV'] = $this->testOptions;
		$vars['BuildTest_NV']['ticket'] = $ticket;
		$vars['BuildTest_NV']['activatedNVPlugin'] = $activatedNVPlugin;
		$vars['BuildTest_NV']['activatedAttPlugin'] = $activatedAttPlugin;
		$vars['BuildTest_NV']['activatedAntPlugin'] = $activatedAntPlugin;

		$this->setSessionVariables( $vars );
	}
}
