<?php
/**
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Search_Setup_TestCase extends TestCase
{
	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if the user (as configured at TESTSUITE option) can logon to Enterprise. '; }
	public function getTestMethods() { return 'Does logon through workflow services at application server. '; }
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

		// Remember if the Solr plugin was initially activated or not
		// This is used for restoring the activated state of the plugin in the Teardown test case
		$wasSolrPluginActivated = BizServerPlugin::isPluginActivated( 'SolrSearch' );

		// Activate Solr Search plugin (in case it was not activated already)
		$activatedSolrPlugin = $this->utils->activatePluginByName( $this, 'SolrSearch' );
		if( is_null( $activatedSolrPlugin ) ) {
			return;
		}

		// There should only be one search connector for this build test (i.e. Solr)
		$connectors = BizServerPlugin::searchConnectors('Search', null);
		if( count($connectors) != 1 ) {
			$this->setResult( 'ERROR', 'Found more than one activated search connectors',
				'Please disable all Search server plugins before running this testcase.' );
			return;
		}

		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this WflServices TestSuite.
		$vars = array();
		$vars['BuildTest_Search'] = $this->testOptions;
		$vars['BuildTest_Search']['ticket'] = $ticket;
		$vars['BuildTest_Search']['wasSolrPluginActivated'] = $wasSolrPluginActivated;

		$this->setSessionVariables( $vars );
	}
}