<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_IdsAutomation_TestCase extends TestCase
{
	public function getDisplayName() { return 'IDS Automation configuration test'; }
	public function getTestGoals()   { return 'Checks if the "IdsAutomation" server plug-in is correctly configured. '; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 23; }

	final public function runTest()
	{
		$this->validateClientFeatureOptions();
	}

	/**
	 * Validate the CLIENTFEATURES setting option that required by Ids Automation
	 */
	private function validateClientFeatureOptions()
	{
		require_once BASEDIR.'/server/plugins/IdsAutomation/IdsAutomationUtils.class.php';
		$pageSyncDefaultsToNo = IdsAutomationUtils::isIdsClientFeatureValue( 'PageSyncDefaultsToNo' );
		if( !$pageSyncDefaultsToNo ) {
			$help = 'Please add the feature option to the CLIENTFEATURES settings in configserver.php.';
			$this->setResult( 'ERROR',
				'The CLIENTFEATURES->[\'InDesign Server\']->[\'IDS_AUTOMATION\']->{\'PageSyncDefaultsToNo\'} feature option is missing.',
				$help
				);
		}
	}
}

