<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_NameValidation_Teardown_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries tearing down /clear up the testing environment. '; }
	public function getTestMethods() { return
		'<ol>
			<li>Set back the original state of the CopyrightValidationDemo plugin. </li>
		 	<li>Logoff the user from Enterprise. (LogOff)</li>
		 </ol>'; }
    public function getPrio()        { return 1000; }
	
	final public function runTest()
	{
		// Initialize
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Read session data.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();
		$this->ticket        = @$vars['BuildTest_NV']['ticket'];
		//$activatedNVPlugin  = @$vars['BuildTest_NV']['activatedNVPlugin'];
		//$activatedAttPlugin  = @$vars['BuildTest_NV']['activatedAttPlugin'];
		// L> Regardless whether or not this testsuite has activated those plugins,
		//    we ALWAYS will deactivate them. Or else, when this testcase causes a 
		//    PHP crash, those plugins would stay enabled forever, badly affecting
		//    other testsuites. Those cases are hard to find/solve.

		// Deactivate the NameValidationDemo plugin
		$this->utils->deactivatePluginByName( $this, 'CopyrightValidationDemo' );

		// Deactivate the AutoTargetingTest plugin
		$this->utils->deactivatePluginByName( $this, 'AutoTargetingTest' );

		// Deactivate the AutoNamingTest plugin
		$this->utils->deactivatePluginByName( $this, 'AutoNamingTest' );

		// LogOff when we did LogOn before.
		if( $this->ticket ) {
			$this->utils->wflLogOff( $this, $this->ticket );
		}

	}
}