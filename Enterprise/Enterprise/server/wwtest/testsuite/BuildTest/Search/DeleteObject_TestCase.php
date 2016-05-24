<?php

require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Search/Search_TestCaseBase.php';

class WW_TestSuite_BuildTest_Search_DeleteObject_TestCase extends WW_TestSuite_BuildTest_Search_Base
{
	public function getDisplayName()
	{
		return 'Delete Object Solr';
	}

	public function getTestGoals()
	{
		return 'Delete Object and test if object is no longer indexed and searchable in Solr';
	}

	public function getTestMethods()
	{
		return 'Delete Object using DeleteObjects, check if the index flag is no longer set ' .
			   'and check if the object is no longer searchable';
	}

	public function getPrio()
	{
		return 6;
	}

	final public function runTest()
	{
		parent::runTest();
		if( $this->hasError() ) {
			return;
		}

		if( !$this->validateDeleteObject() ) {
			return;
		}
	}

	public function __construct()
	{
	}

	/**
	 * Test deleting an object.
	 *
	 * Validate the object is no longer indexed and no longer searchable.
	 */
	public function validateDeleteObject()
	{
		$articleName = $this->vars['BuildTest_Search']['ArticleName'];
		$articleID = $this->vars['BuildTest_Search']['ArticleID'];
		$ticket = $this->vars['BuildTest_Search']['ticket'];

		// Delete Object from Enterprise
		$errorReport = null;
		if( !$this->utils->deleteObject( $this, $ticket, $articleID, 'Delete article object from Database and Solr', $errorReport ) ) {
			return false;
		}

		// No longer indexed
		$articleID = $this->vars['BuildTest_Search']['ArticleID'];
		$prevObjectID = (string)(((int)$articleID)-1); // Note: getIndexedObjects gets the next object, so get prev id

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objects = DBObject::getIndexedObjects( $prevObjectID, 1);
		if( !empty($objects) && $objects[0] == $articleID ) {
			$this->setResult( 'ERROR', 'Created object is still indexed',
				'Check the Object in Solr/database' );
			return false;
		}

		// Search on Name. Should return false for both Solr and Database search.
		if( !$this->testSearch( $articleID, $articleName, 'Checking if deleted object is no longer searchable', false ) ) {
			return false;
		}

		return true;
	}
}
