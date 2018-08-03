<?php
/**
 * "Enterprise Script deployment plug-in for Smart Connection" TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v9.5.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_EnterpriseScripts_TestCase extends TestCase
{
	public function getDisplayName() { return 'Enterprise Scripts'; }
	public function getTestGoals()   { return 'Checks if there is a zipped scripts package which can be made available for download.'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return ''; }

	final public function runTest()
	{
		// Define the URL to the downloadable zip package.
		$packageURL = SERVERURL_ROOT.INETROOT.'/server/plugins/EnterpriseScripts/SubApplication/EnterpriseScripts.zip';
		$packagePath = BASEDIR.'/server/plugins/EnterpriseScripts/SubApplication/EnterpriseScripts.zip';
		
		// Check if the zip package exists on the correct location.
		if( !file_exists($packagePath) ) {
			$help = 'Please add a correctly named zip file to the plug-in.';
			$this->setResult( 'ERROR',
				'Could not find the downloadable file EnterpriseScripts.zip to dispatch at '.$packageURL, $help );
		}
	}
}

