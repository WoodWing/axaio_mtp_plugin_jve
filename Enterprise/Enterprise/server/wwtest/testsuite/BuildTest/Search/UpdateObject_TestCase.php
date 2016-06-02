<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_UpdateObject_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName()
	{
		return 'Update Object Solr';
	}

	public function getTestGoals()
	{
		return 'Update Object properties and verify the updated values are searchable in Solr. ';
	}

	public function getTestMethods()
	{
		return 'Updates the properties of the object using SetObjectProperties and searches on the updated values ' .
			   'using QueryObjects. Name and content properties are changed and being searched on.';
	}

	public function getPrio()
	{
		return 4;
	}

	final public function runTest()
	{
		parent::runTest();
		if( $this->hasError() ) {
			return;
		}

		if( !$this->validateUpdateObject() ) {
			return;
		}

		// Update session vars for next test cases
		$this->setSessionVariables( $this->vars );
	}

	public function __construct()
	{
	}

	/**
	 * Test changing properties of an object.
	 *
	 * Validate the object is searchable using the new updated values.
	 */
	public function validateUpdateObject()
	{
		$ticket = $this->vars['BuildTest_Search']['ticket'];
		$articleObject = $this->vars['BuildTest_Search']['Article'];
		$oldArticleName = $this->vars['BuildTest_Search']['ArticleName'];
		$articleID = $this->vars['BuildTest_Search']['ArticleID'];

		// Change article name
		$articleOldContent = $articleObject->MetaData->ContentMetaData->PlainContent;
		$articleContent = 'new different body';
		$articleName = 'DifferentName';
		$articleObject->MetaData->BasicMetaData->Name = $articleName;
		$articleObject->MetaData->ContentMetaData->PlainContent = $articleContent;

		$changedPropPaths = array(
			'BasicMetaData->Name' => $articleName,
			'ContentMetaData->PlainContent' => $articleContent,
		);

		if( !$this->utils->setObjectProperties( $this, $ticket, $articleObject, 'Changing Article Object name', null, $changedPropPaths ) ) {
			return false;
		}

		$this->vars['BuildTest_Search']['ArticleName'] = $articleName;

		// Search on new name
		if( !$this->testSearch( $articleID, $articleName, 'Searching for article by renamed name', true ) ) {
			return false;
		}

		// Search on old name; nothing should be found
		if( !$this->testSearch( $articleID, $oldArticleName, 'Searching for article by old name', false ) ) {
			return false;
		}

		$isSolrSearch = BizServerPlugin::isPluginActivated( 'SolrSearch' );

		// Search on new content
		if( !$this->testSearch( $articleID, $articleContent, 'Searching for article on new content', $isSolrSearch ) ) {
			return false;
		}

		// Search on old content; not findable
		if( !$this->testSearch( $articleID, $articleOldContent, 'Searching for article on old content', false ) ) {
			return false;
		}

		return true;
	}
}
