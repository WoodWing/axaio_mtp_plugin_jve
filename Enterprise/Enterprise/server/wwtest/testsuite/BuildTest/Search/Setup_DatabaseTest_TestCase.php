<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_Setup_DatabaseTest_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName()
	{
		return 'Setup Database test';
	}

	public function getTestGoals()
	{
		return 'Deactive Solr server plugin for Database only test';
	}

	public function getTestMethods() { return
		'Deactivates Solr server plugin';
	}

	public function getPrio()
	{
		return 19;
	}

	final public function runTest()
	{
		parent::runTest();
		if( $this->hasError() ) {
			return;
		}

		// Deactivate Solr plugin for Database only search tests
		$deactivatedSolrPlugin = $this->utils->deactivatePluginByName( $this, 'SolrSearch' );
		if( is_null( $deactivatedSolrPlugin ) ) {
			return;
		}
	}
}
