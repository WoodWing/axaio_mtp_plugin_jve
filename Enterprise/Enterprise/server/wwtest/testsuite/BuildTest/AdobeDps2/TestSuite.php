<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Description class of the Adobe DPS test suite.
 */
 
 require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps2_TestSuite extends TestSuite
{
	public function getDisplayName() 
	{
		$configFile = BASEDIR.'/config/plugins/AdobeDps2/config.php';
		$displayName = 'Adobe DPS (?)';
		if( file_exists( $configFile ) ) {
			require_once BASEDIR.'/config/plugins/AdobeDps2/config.php'; // DPS2_PLUGIN_DISPLAYNAME
			$displayName = DPS2_PLUGIN_DISPLAYNAME;
		}
		return $displayName;
	}
	
	public function getTestGoals()   { return 'Checks if the Adobe DPS integration works fine.'; }
	public function getTestMethods() { return ''; }
    public function getPrio()        { return 5150; }
}