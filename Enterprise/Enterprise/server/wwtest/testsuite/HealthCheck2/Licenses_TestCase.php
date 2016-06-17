<?php
/**
 * Licenses TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package SCEnterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/utils/license/license.class.php';
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Licenses_TestCase extends TestCase
{
	public function getDisplayName() { return 'Licenses'; }
	public function getTestGoals()   { return 'Checks if Enterprise product licenses are installed. '; }
	public function getTestMethods() { return '-'; } // no info for security reasons!
    public function getPrio()        { return 6; }
	
	final public function runTest()
	{
    	LogHandler::Log('wwtest', 'INFO', 'License...' );
    	$errorMessage = '';
    	$help = '';
    	$lic = new License();
    	$extended = true;
    	$warn = false;
    	if ( !$lic->wwTest( $errorMessage, $help, $warn, $extended ))
    	{
    		if( $warn ) {
				$this->setResult( 'WARN', $errorMessage, $help );
    		} else {
				$this->setResult( 'ERROR', $errorMessage, $help );
    		}
			return;
    	}

    	LogHandler::Log('wwtest', 'INFO', 'License successful.');
    }
}
