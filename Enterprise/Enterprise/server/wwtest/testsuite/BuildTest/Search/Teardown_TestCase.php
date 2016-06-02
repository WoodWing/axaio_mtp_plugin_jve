<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_Teardown_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries logoff the user from Enterprise. '; }
	public function getTestMethods() { return 'Calls the LogOff workflow service at application server. '; }
	public function getPrio()        { return 500; }

	final public function runTest()
	{
		parent::runTest();
		if( $this->hasError() ) {
			return;
		}

		// Put Solr back in original activated or deactivated state
		$wasSolrPluginActivated = $this->vars['BuildTest_Search']['wasSolrPluginActivated'];
		if( !$wasSolrPluginActivated ) {
			$deactivatedSolrPlugin = $this->utils->deactivatePluginByName( $this, 'SolrSearch' );
			if( is_null( $deactivatedSolrPlugin ) ) {
				return;
			}
		}
		else {
			$activatedSolrPlugin = $this->utils->activatePluginByName( $this, 'SolrSearch' );
			if( is_null( $activatedSolrPlugin ) ) {
				return;
			}
		}

		// Log off
		$this->utils->wflLogOff( $this, $this->ticket );
	}
}