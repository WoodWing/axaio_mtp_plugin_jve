<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_ContentSource_Teardown_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries tearing down /clear up the testing environment. '; }
	public function getTestMethods() { return
		'<ol>
			<li>Set back the original state of the SimpleFileSystem plugin. </li>
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
		$this->ticket        = @$vars['BuildTest_SFS']['ticket'];
		$activatedSFSPlugin  = @$vars['BuildTest_SFS']['activatedSFSPlugin'];

		// Deactivate the SimpleFileSystem plugin when we did activate before.
		if( $activatedSFSPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'SimpleFileSystem' );
		}

		// LogOff when we did LogOn before.
		if( $this->ticket ) {
			$this->utils->wflLogOff( $this, $this->ticket );
		}

	}
}