<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_Search_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName()
	{
		return 'Search by using Solr Search.';
	}

	public function getTestGoals()
	{
		return 'Checks if object(s) can be found by using by Solr Search.';
	}

	public function getTestMethods() { return
		'Call QueryObjectsService and validate responses.
		<ol>
			<li>Search Object on "Name" property (QueryObjects)</li>
			<li>Search Object on "Content" property (QueryObjects)</li>
			<li>Search Object on "Placed on" property. (QueryObjects)</li>
			<li>Search Object on "Route to" property. (QueryObjects)</li>
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

		// Test searching on name and placed on in Solr.
		$queryParam = array( new QueryParam( 'PlacedOn', '=', '' ) );
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article, not placed.', true, $queryParam ) ) {
			return false;
		}
		
		// Test searching on route to (short name) in Solr.
		$userShortName = BizSession::checkTicket( $this->vars['BuildTest_Search']['ticket'] );
		$queryParam = array( new QueryParam( 'RouteTo', '=', $userShortName ) );
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article on RouteTo (short name)', true, $queryParam ) ) {
			return false;
		}

		// Test searching on route to (full name) in Solr.
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$userFullName = DBUser::getFullName( $userShortName );
		$queryParam = array( new QueryParam( 'RouteTo', '=', $userFullName ) );
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article on RouteTo (full name)', true, $queryParam ) ) {
			return false;
		}

		return true;
	}

}
