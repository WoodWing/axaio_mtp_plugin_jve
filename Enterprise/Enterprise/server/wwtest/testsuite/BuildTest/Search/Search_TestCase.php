<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_Search_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName()
	{
		return 'Search in Solr';
	}

	public function getTestGoals()
	{
		return 'Checks if able to find objects in Solr';
	}

	public function getTestMethods() { return
		'Call QueryObjectsService and validate responses.
		<ol>
			<li>Search on Object name (QueryObjects)</li>
			<li>Search on Object content (QueryObjects)</li>
		 </ol>';
	}

	public function getPrio()
	{
		return 3;
	}

	final public function runTest()
	{
		parent::runTest();
		if( $this->hasError() ) {
			return;
		}

		if( !$this->validateSearch( ) ) {
			return;
		}
	}

	public function __construct()
	{
	}

	public function validateSearch( )
	{
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';

		$articleName = $this->vars['BuildTest_Search']['ArticleName'];
		$articleID = $this->vars['BuildTest_Search']['ArticleID'];

		// Test searching on name in Solr
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article', true ) ) {
			return false;
		}

		$isSolrSearch = BizServerPlugin::isPluginActivated( 'SolrSearch' );

		// Test searching on content in Solr. In case of database search only no result is expected.
		if( !$this->testSearch( $articleID, 'some searchable content', 'searching content', $isSolrSearch ) ) {
			return false;
		}

		return true;
	}
}
