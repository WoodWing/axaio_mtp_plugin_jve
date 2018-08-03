<?php
/**
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishManager_QueryPublishManager_TestCase extends TestCase
{
	private $ticket = null;
	private $query 	= 'PublishManager';
	private $publicationObj = null;
	private $pubChannelObj = null;
	private $suiteOpts = null;
	private $dossier = null;
	private $dossierForSortTest = null;
	private $publishForm = null;
	private $publishFormForSortTest = null;
	private $publishFormTemplate = null;
	private $mcpUtils = null; // MultiChannelPublishingUtils
	private $modified = '-P4D';

	public function getDisplayName() { return 'Test the Named Query for the Publish Manager.'; }
	public function getTestGoals()   { return 'Checks if the built-in named query \''.$this->query.'\' works.'; }
	public function getPrio()        { return 2; }
	public function getTestMethods() { return 'Scenario\'s:<ol>
		<li>00: Test the PublishStatus parameter, if it is missing or invalid an error should be thrown.</li>
		<li>01: Test the PublishManager named query with the PublishStatus set to NotPublished.</li>
		<li>02: Test the PublishManager named query with the PublishStatus set to Published.</li>
		<li>03: Test the PublishManager named query with the PublishStatus set to ReadyForPublishing.</li>
		<li>04: Test the PublishManager named query with the PublishStatus set to UpdateAvailable.</li>
		<li>05: Test the Publishmanager named query with the CategoryId provided.</li>
		<li>06: Test the PublishManager named query with the Modified filter.</li>
		<li>07: Test the PublishManager named query with the PubChannelIds filter.</li>
		<li>08: Test the PublishManager named query with the Search (PublishForm name) filter.</li>
		<li>09: Test the PublishManager named query sorting options.</li>
		</ol>'; }
	
	final public function runTest()
	{
		// Use the publishing Utils.
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';
		$this->mcpUtils = new MultiChannelPublishingUtils();
		if( !$this->mcpUtils->initTest( $this ) ) {
			return;
		}
		
		do {
			// Init class members with test data to work with.
			if( !$this->setupTestData() ) {
				break;
			}

			// Scenario 00: Test if the Filter is validated by triggering an error (not setting the filter) and test that
			// the query is accepted when the filter is set to a valid option.
			if( !$this->testScenario00() ) {
				break;
			}

			// Scenario 01: Test retrieving the PublishForms that have not yet been Published.
			if( !$this->testScenario01() ) {
				break;
			}

			// Scenario 02: Test retrieving the PublishForms that have been Published.
			if( !$this->testScenario02() ) {
				break;
			}

			// Scenario 03: Test retrieving the PublishForms that are ready to be Published.
			if( !$this->testScenario03() ) {
				break;
			}

			// Scenario 04: Test retrieving the PublishForms that have an update available..
			if( !$this->testScenario04() ) {
				break;
			}

			// Scenario 05: Test retrieving PublishForms that have a specific Category Id set (section id).
			if ( !$this->testScenario05() ) {
				break;
			}

			// Scenario 06: Test retrieving PublishForms that have a specific Modified filter set.
			if ( !$this->testScenario06() ) {
				break;
			}

			// Scenario 07: Test retrieving PublishForms with the optional PubChannelIds Query Parameter.
			if ( !$this->testScenario07() ) {
				break;
			}

			// Scenario 08: Test retrieving PublishForms with the optional Search Query Parameter.
			if ( !$this->testScenario08() ) {
				break;
			}

			// Scenario 09: Test the column ordering of the PublishManager query.
			if ( !$this->testSortingScenario() ) {
				break;
			}

		} while( false );

		$this->tearDownTestData();
	}

	/**
	 * Removes all test data that was created by the {@link: setupTestData()} function.
	 */
	private function tearDownTestData()
	{
		$errorReport = null;

		// Tear down the PublishForm.
		if( !is_null($this->publishForm) ) {
			$this->updateContainedTargetPublishedDate( '' );

			$id = $this->publishForm->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Failed to delete the PublishForm created for testing.'. $errorReport );
			}
			$this->publishForm = null;
		}

		// Tear down the PublishForm for the sorting test.
		if( !is_null($this->publishFormForSortTest) ) {
			$id = $this->publishFormForSortTest->MetaData->BasicMetaData->ID;
			if ( !is_null( $this->dossierForSortTest ) ) {
				$this->updateContainedTargetPublishedDate( '', $this->dossierForSortTest->MetaData->BasicMetaData->ID, $id );
			}

			$stepInfo = 'Tear down the Publish Form object for the sorting test.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Failed to delete the PublishForm created for the sort order testing.'. $errorReport );
			}
			$this->publishFormForSortTest = null;
		}

		// Tear down the Publish Form Template.
		if( !is_null($this->publishFormTemplate) ) {
			$id = $this->publishFormTemplate->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Publish Form Template object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Failed to delete the PublishFormTemplate created for testing.'. $errorReport );
			}
			$this->publishFormTemplate = null;
		}
		
		// Tear down the Dossier.
		if( !is_null($this->dossier) ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Failed to delete the Dossier created for testing.'. $errorReport );
			}
			$this->dossier = null;
		}

		// Tear down the Dossier for the sorting test.
		if( !is_null($this->dossierForSortTest) ) {
			$id = $this->dossierForSortTest->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down the Dossier object for the sorting test.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Failed to delete the Dossier created for the sort order testing.'. $errorReport );
			}
			$this->dossierForSortTest = null;
		}
	}

	/**
	 * Tests if a basic named query is succesful.
	 *
	 * @return bool Whether or not the query was succesful.
	 */
	private function testScenario00()
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';

		$scenarioString = 'Scenario 00: ';

		// Test that ommitting the Query Param 'PublishStatus' results in an error on the PublishManager Named Query.
		$this->namedQuery( $this->pubChannelObj->Id, null, true, $scenarioString, null, $this->modified, $this->publicationObj->Id );

		// Test that passing an illegal Query Param 'PublishStatus' results in an error on the PublishManager Named Query.
		$this->namedQuery( $this->pubChannelObj->Id, 'FaultyStatus', true, $scenarioString, null, $this->modified, $this->publicationObj->Id );

		// Test that passing a legal Query Param 'PublishStatus' results in a correct PublishManager Named Query Result.
		$this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING, false, $scenarioString, null, $this->modified, $this->publicationObj->Id );
		
		return true; // TODO: error management
	}

	/**
	 * Test the PublishManager Named Query, with the 'PublishStatus' Filter set to 'NotPublished'.
	 *
	 * @return bool
	 */
	private function testScenario01()
	{
		do {
			$testCaseName = 'Scenario 01: ';
			require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
			// Form is non-published state
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $testCaseName, null, $this->modified, $this->publicationObj->Id );
			$result = $this->validateNamedQueryResp($response, $testCaseName, true ); // true = Expecting the Form we added to be returned.
			if( !$result ) {
				break;
			}

			$date = date('Y-m-d\TH:i:s');
			$this->updateContainedTargetPublishedDate( $date ); // "Publish the Form"
			// Form is published state
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $testCaseName, null, $this->modified, $this->publicationObj->Id );
			$result = $this->validateNamedQueryResp($response, $testCaseName, false ); // false = Don't expect the Form we added to be returned.
			if( !$result ) {
				break;
			}

		} while( false );
		$this->updateContainedTargetPublishedDate( '' ); // Unpublish the Form (just to be sure otherwise the TearDown will fail).
		return $result;
	}

	/**
	 * Test the PublishManager Named Query, with the 'PublishStatus' Filter set to 'Published'.
	 *
	 * @return bool
	 */
	private function testScenario02()
	{
		do {
			$testCaseName = 'Scenario 02: ';
			$date = date('Y-m-d\TH:i:s');
			$this->updateContainedTargetPublishedDate( $date ); // Publish the Form.
			require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
			// Form is published state
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_PUBLISHED, false, $testCaseName, null, $this->modified, $this->publicationObj->Id );
			$result = $this->validateNamedQueryResp($response, $testCaseName, true ); // true = Expecting the Form we added to be returned.
			if( !$result ) {
				break;
			}

			$this->updateContainedTargetPublishedDate( '' ); // Unpublish the Form.
			// Form is non-published state
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_PUBLISHED, false, $testCaseName, null, $this->modified, $this->publicationObj->Id );
			$result = $this->validateNamedQueryResp($response, $testCaseName, false ); // false = Don't expect the Form we added to be returned.
			if( !$result ) {
				break;
			}

		} while( false );
		$this->updateContainedTargetPublishedDate( '' ); // Unpublish the Form (just to be sure otherwise the TearDown will fail).
		return $result;
	}

	/**
	 * Test the PublishManager Named Query, with the 'PublishStatus' Filter set to 'ReadyForPublishing'.
	 *
	 * @return bool
	 */
	private function testScenario03()
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
		do {
			$testCaseName = 'Test scenario 3';
			$publishFormId = $this->publishForm->MetaData->BasicMetaData->ID;
			// Form's state: publisheddate is not set AND NOT set to readyforpublishing => Form SHOULD NOT be returned in the NamedQuery result.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING, false, 'Scenario 03: ', null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && !$this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $testCaseName .': Form(id='.$publishFormId.') is returned in the NamedQuery, '.
					'which is not expected. The Form has no publisheddate set and not yet set to readyforpublishing, ' .
					'therefore it should not be returned in the NamedQuery when the filter is "'.
					BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING.'".');
				break;
			}

			$stateId = $this->publishForm->MetaData->WorkflowMetaData->State->Id;
			$this->updateStatus($stateId, true);
			// Form's state: publisheddate is not set AND set to readyforpublishing => Form SHOULD be returned in the NamedQuery result.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING, false, 'Scenario 03: ', null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $testCaseName .': Form(id='.$publishFormId.') is not returned in the NamedQuery, '.
					'which is not expected. The form status is set to "readyforpublishing", ' .
					'therefore it should be returned in the NamedQuery when the filter is "'.
					BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING.'".');
					break;
			}

			$stateId = $this->publishForm->MetaData->WorkflowMetaData->State->Id;
			$this->updateStatus($stateId, true);
			// Form's state: Form updated since published AND set to readyforpublishing => Form SHOULD be returned in the NamedQuery result.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING, false, 'Scenario 03: ', null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $testCaseName .': Form(id='.$publishFormId.') is not returned in the NamedQuery, '.
					'which is not expected. The Form has been modified since published and it has status set to ' .
					'"readyforpublishing", therefore it should be returned in the NamedQuery when the filter is "'.
					BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING.'".' );
				break;
			}

			// Set the publishedDate to be newer than the modified date and test that no object is returned.
			$date = date('Y-m-d\TH:i:s');
			$this->updateContainedTargetPublishedDate( $date );
			// Form's state: No updates since Form is published => Form SHOULD NOT be returned in the NamedQuery result.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING, false, 'Scenario 03: ', null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && !$this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $testCaseName .': Form(id='.$publishFormId.') is returned in the NamedQuery, '.
					'which is not expected. The Form has not been modified since published, ' .
					'therefore it should not be returned in the NamedQuery when the filter is "'.
					BizNamedQuery::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING.'".');
				break;
			}
		} while( false );

		// Restore the State and the PublishedDate.
		$this->updateContainedTargetPublishedDate( '' );
		if( isset( $stateId ) ) {
			$this->updateStatus($stateId, false);
		}

		return $result;
	}

	/**
	 * Test the PublishManager Named Query, with the 'PublishStatus' Filter set to 'UpdateAvailable'.
	 *
	 * @return bool
	 */
	private function testScenario04()
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';

		do {
			$testCaseName = 'Test scenario 4';
			$publishFormId = $this->publishForm->MetaData->BasicMetaData->ID;
			// Run the Query, where we initially do not expect it to return the record we are looking for as the publisheddate
			// is empty.
			// Validate the response object, verifying that our object indeed is not there.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE, false, 'Scenario 04: ', null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && !$this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $testCaseName .': Form(id='.$publishFormId.') is returned in the NamedQuery, '.
					'which is not expected. The Form has not been published, ' .
					'therefore it should not be returned in the NamedQuery when the filter is set to "'.
					BizNamedQuery::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE.'".');
				break;
			}

			// Set the Targets PublishedDate to simulate the next stage in testing.
			$date = date('Y-m-d\TH:i:s', strtotime("-2 days"));
			$this->updateContainedTargetPublishedDate( $date );

			// Modified date is > publisheddate, we expect a record now.
			$stateId = $this->publishForm->MetaData->WorkflowMetaData->State->Id;
			$this->updateStatus($stateId, true);
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE, false, 'Scenario 04: ', null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $testCaseName .': Form(id='.$publishFormId.') is not returned in the NamedQuery, '.
					'which is not expected. The Form has been modified since published, ' .
					'therefore it should be returned in the NamedQuery when the filter is set to "'.
					BizNamedQuery::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE.'".');
				break;
			}

			// Set the publishedDate to be newer than the modified date and test that no object is returned.
			$date = date('Y-m-d\TH:i:s');
			$this->updateContainedTargetPublishedDate( $date );
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE, false, 'Scenario 04: ', null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && !$this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $testCaseName .': Form(id='.$publishFormId.') is returned in the NamedQuery, '.
					'which is not expected. The Form has not been modified since published, ' .
					'therefore it should not be returned in the NamedQuery when the filter is set to "'.
					BizNamedQuery::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE.'".');
				break;
			}
		} while( false );

		// Restore the PublishedDate.
		$this->updateContainedTargetPublishedDate( '' );
		if( isset( $stateId ) ) {
			$this->updateStatus($stateId, false);
		}

		return $result;
	}

	/**
	 * Tests if the named query can be succesfully executed with and without the CategoryId query param sent along.
	 *
	 * @return bool Whether or not the query was succesful.
	 */
	private function testScenario05()
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';

		$publishFormId = $this->publishForm->MetaData->BasicMetaData->ID;
		$categoryId = $this->publishForm->MetaData->BasicMetaData->Category->Id;
		$scenarioString = 'Scenario 05: ';

		do {

			// Test: Omitting the CategoryId still returns our record (in fact all records should be returned).
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString, null, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected. Ommitting the CategoryId query parameter should return all '
					. ' Objects regardless of their category.');
				break;
			}

			// Test: Querying for the same category id as set on our PublishForm should still return our PublishForm through the NamedQuery.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString, $categoryId, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected. The Publish Form has categoryId: ' . $categoryId . ' and '
					. 'was not returned by the NamedQuery.');
				break;
			}

			// Test: Querying on a category that does not contain our PublishForm's category should not return it through the NamedQuery.
			$categoryId = (string) (intval($categoryId) + 1); // Up the category id by one to make sure it no longer matches that of our PublishForm.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString, $categoryId, $this->modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && !$this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was returned in '
					. 'the NamedQuery, which was unexpected. The Publish Form\'s category does not match the requested '
					. 'CategoryId.');
				break;
			}
		} while( false );

		return $result;
	}

	/**
	 * Tests if the named query can be succesfully executed with the Modified parameter provided.
	 *
	 * @return bool Whether or not the query was succesful.
	 */
	private function testScenario06()
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';

		$publishFormId = $this->publishForm->MetaData->BasicMetaData->ID;
		$scenarioString = 'Scenario 06: ';
		$modified = '-P1D';

		do {

			// Test: Omitting the Modified field will result in an error.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, true, $scenarioString, null, null, $this->publicationObj->Id );
			$result = ( is_null($response) );
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Validation for the Modified filter option succeeded, the field '
					. ' Modified was not present in the request but should always be present. ');
				break;
			}

			// Test: Querying for the form in the last day should return our Publish Form.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString, null, $modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected. The Publish Form was created within the time period specified '
				    . 'for the Modified parameter.');
				break;
			}

			// Test: Querying for the form with a Modified date in the future should not return our asset.
			// Wait a few seconds then ask for dates from the future (edge case where the creation of the item and the used date results in the same value, in which case
			// it WILL return our asset.
			sleep(1);
			$modified = 'P1D'; // Up the Modified date to the future, which should not have any results.
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString, null, $modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && !$this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was returned in '
					. 'the NamedQuery, which was unexpected. The Publish Form\'s modified date lies outside of the '
				    . 'requested period.');
				break;
			}

		} while( false );

		return $result;
	}

	/**
	 * Tests if the named query can be succesfully executed with the PubChannelIds parameter provided.
	 *
	 * @return bool Whether or not the query was succesful.
	 */
	private function testScenario07()
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';

		$publishFormId = $this->publishForm->MetaData->BasicMetaData->ID;
		$scenarioString = 'Scenario 07: ';
		$modified = '-P1D';

		do {

			// Test: Omitting the PubChannelId field will not result in an error.
			$response = $this->namedQuery( null, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString, null, $modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected.');
				break;
			}

			// Test: Querying for the form with an invalid PubChannelIds should not return any assets.
			$pubChannelId = $this->pubChannelObj->Id . ',A';
			$response = $this->namedQuery( $pubChannelId, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, true, $scenarioString, null, $modified, $this->publicationObj->Id );
			$result = ( is_null($response) );
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The named query returned a valid response, which was ' .
					'unexpected. An invalid PubChannelIds parameter should not pass server validation.');
				break;
			}

			// Test: Querying for the form with a valid PubChannelIds parameter should return our selected value.
			$response = $this->namedQuery($this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString, null, $modified, $this->publicationObj->Id );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected. The Publish Form matches the requested PubChannelIds.');
				break;
			}

		} while( false );

		return $result;
	}

	/**
	 * Tests the named query sorting option(s) to see if they influence the result set correctly.
	 *
	 * @return bool Whether or not the test was succesful.
	 */
	private function testSortingScenario()
	{
		$publishFormId = $this->publishForm->MetaData->BasicMetaData->ID;
		$publishFormForSortId = $this->publishFormForSortTest->MetaData->BasicMetaData->ID;
		$modified = '-P365D';

		do {
			// Modified Ascending: Expecting PublishFormSort first.
			$sortOrder = array( $publishFormForSortId, $publishFormId );
			$order = array( 'Modified' => 'true'); // Modified, Ascending order.
			$scenarioString = 'Sorting: Sort by Modified date Ascending.';

			// Update the Modified Date on the publishFormForTest by setting it to an earlier date.
			$date = date('Y-m-d\TH:i:s', strtotime("-2 days"));
			$this->updateObjectField( $publishFormForSortId, 'modified', $date );

			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}

			// Modified Descending: Test the Modified Date, in descending order, expecting PublishFormSortId first.
			$sortOrder = array_reverse( $sortOrder );
			$order = array( 'Modified' => 'false'); // Modified, Descending order.
			$scenarioString = 'Sorting: Sort by Modified date Descending.';
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}

			// PublishFormName Ascending.
			$sortOrder = array( $publishFormForSortId, $publishFormId );
			$order = array( 'Name' => 'true'); // PublishFormName, Ascending order.
			$scenarioString = 'Sorting: Sort by PublishFormName Ascending.';

			// Update the PublishFormName
			$this->updateObjectField( $publishFormForSortId, 'name', 'a' );
			$this->updateObjectField( $publishFormId, 'name', 'b' );

			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}

			// PublishFormName Descending.
			$sortOrder = array_reverse( $sortOrder );
			$order = array( 'Name' => 'false'); // PublishFormName, Descending order.
			$scenarioString = 'Sorting: Sort by PublishFormName Descending.';
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}

			// Slugline Ascending.
			$sortOrder = array( $publishFormForSortId, $publishFormId );
			$order = array( 'Slugline' => 'true'); // Slugline, Ascending order.
			$scenarioString = 'Sorting: Sort by Slugline Ascending.';

			// Update the Slugline.
			$this->updateObjectField( $publishFormForSortId, 'slugline', 'a' );
			$this->updateObjectField( $publishFormId, 'slugline', 'b' );

			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}

			// Slugline Descending.
			$sortOrder = array_reverse( $sortOrder );
			$order = array( 'Slugline' => 'false'); // Slugline, Descending order.
			$scenarioString = 'Sorting: Sort by Slugline Descending.';
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}

			// PublishedDate Ascending.
			$sortOrder = array( $publishFormForSortId, $publishFormId );
			$order = array( 'PublishedDate' => 'true'); // PublishedDate, Ascending order.
			$scenarioString = 'Sorting: Sort by PublishedDate date Ascending.';

			// Update the Published Dates for the test.
			$date = date('Y-m-d\TH:i:s', strtotime("-1 days"));
			$this->updateContainedTargetPublishedDate( $date );
			$date = date('Y-m-d\TH:i:s', strtotime("-2 days"));
			$this->updateContainedTargetPublishedDate( $date, $this->dossierForSortTest->MetaData->BasicMetaData->ID, $publishFormForSortId );

			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}

			// PublishedDate Descending.
			$sortOrder = array_reverse( $sortOrder );
			$order = array( 'PublishedDate' => 'false'); // PublishedDate, Descending order.
			$scenarioString = 'Sorting: Sort by PublishedDate Descending.';
			$response = $this->namedQuery( $this->pubChannelObj->Id, BizNamedQuery::PUBLISH_MANAGER_FILTER_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, $order );
			$result = ( !is_null($response) && $this->analyzeQueryResponseSorting($response, $sortOrder));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': The ordering of result set did not match the expected order.');
				break;
			}
		} while( false );

		return $result;

	}

	/**
	 * Tests if the named query can be succesfully executed with the Search parameter provided.
	 *
	 * @return bool Whether or not the query was succesful.
	 */
	private function testScenario08()
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';

		$publishFormId = $this->publishForm->MetaData->BasicMetaData->ID;
		$scenarioString = 'Scenario 08: ';
		$modified = '-P1D';

		do {

			// Test: Omitting the Search field will not result in an error.
			$search = null;
			$response = $this->namedQuery( null, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false, $scenarioString,
				null, $modified, $this->publicationObj->Id, null, $search );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected.');
				break;
			}

			// Test: Querying for the form with a non-matching search string should not return any assets.
			$search = 'ZZZZZ';
			$this->updateObjectField( $publishFormId, 'name', 'AAAAAA' );
			$response = $this->namedQuery( null, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED, false,
				$scenarioString, null, $modified, $this->publicationObj->Id, null, $search );
			$result = ( !is_null($response) && !$this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected.');
				break;
			}

			// Test: Querying for the form with a matching search string should return our selected value.
			$search = 'Within';
			$this->updateObjectField( $publishFormId, 'name', 'PublishFormWithinPublishForm' );
			$response = $this->namedQuery(null, BizNamedQuery::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED,
				false, $scenarioString, null, $modified, $this->publicationObj->Id, null, $search );
			$result = ( !is_null($response) && $this->responseContainsObject($response, $publishFormId ));
			if (!$result) {
				$this->setResult( 'ERROR', $scenarioString .': Publish Form (id='.$publishFormId.') was not returned in '
					. 'the NamedQuery, which was unexpected. The Publish Form matches the requested PubChannelIds.');
				break;
			}

		} while( false );

		return $result;
	}

	/**
	 * Resolves test data to work with. All resolved data is set to class members.
	 *
	 * @return bool Whether or not all data could be resolved.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
   		$this->vars = $this->getSessionVariables();

   		$this->ticket = @$this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return false;
		}
		
		$this->suiteOpts = unserialize( TESTSUITE ); // defined in configserver.php
		$this->user = $this->suiteOpts['User'];
		$this->publicationObj = $this->vars['BuildTest_MultiChannelPublishing']['publication']; // Was saved during LogOn
		$this->pubChannelObj = $this->vars['BuildTest_MultiChannelPublishing']['webPubChannel']; // Was saved during LogOn
		
		$this->publishFormTemplate = $this->mcpUtils->createPublishFormTemplateObject('Creating a PublishFormTemplate for the PublishManager test case', 'PublishManagerTestCase');
		if (is_null($this->publishFormTemplate)) {
			$this->setResult( 'ERROR',  'Could not create the PublishFormTemplate to test with.');
			return false;
		}

		// Create a Dossier to test with.
		$this->dossier = $this->mcpUtils->createDossier('Creating a Dossier for the PublishManager', 'QueryPublishManager_TestCase', 'web');
		if (is_null($this->dossier)) {
			$this->setResult( 'ERROR', 'Could not set up a Dossier to test with.' );
			return false;
		}

		// Test creation of a correct PublishForm.
		$stepInfo = 'Creating a Publish Form object for the PublishManager test case.';
		$this->publishForm = $this->mcpUtils->createPublishFormObject(  
									$this->publishFormTemplate, $this->dossier, $stepInfo );
		if (is_null($this->publishForm)) {
			$this->setResult( 'ERROR',  'Could not create PublishForm object.');
			return false;
		}

		// Create a Dossier for the sorting test.
		$this->dossierForSortTest = $this->mcpUtils->createDossier('Creating a Dossier for the PublishManager Sorting', 'QueryPublishManager_TestCase2', 'web');
		if (is_null($this->dossierForSortTest)) {
			$this->setResult( 'ERROR', 'Could not set up a Dossier for the Sorting test.' );
			return false;
		}

		// Test creating a PublishForm for the sorting test.
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';

		$stepInfo = 'Creating a Publish Form object for the PublishManager Sorting test case.';
		$metaData = new MetaData();
		$basicMetaData = new BasicMetaData();
		$basicMetaData->Name = 'adfafd';
		$metaData->BasicMetaData = $basicMetaData;
		$this->publishFormForSortTest = $this->mcpUtils->createPublishFormObject(
			$this->publishFormTemplate, $this->dossierForSortTest, $stepInfo, MultiChannelPublishingUtils::RELATION_NORMAL, $metaData );
		if (is_null($this->publishFormForSortTest)) {
			$this->setResult( 'ERROR',  'Could not create PublishForm object for the sorting test.');
			return false;
		}

		return true;
	}

	/**
	 * Calls the workflow interface NamedQuery service for the PublishManager.
	 *
	 * @param string $pubChannelId The PubchannelId to use for the query.
	 * @param string $filterParam The Publish Status filter to use.
	 * @param boolean $expectError Set to true when the service call is expected to fail; False otherwise.
	 * @param string $testCaseName The name of the test case for reporting.
	 * @param $categoryId Whether or not to filter on the Category, ommitted means all, filled in means filter on specific field.
	 * @param string $modified The modified date to use of the query.
	 * @param string $publicationId The PublicationId for the query.
	 * @param array $sortOrder An array of column names as key and direction as value which to use for sorting the result.
	 * @param string $search The PublishForm name to perform a search for.
	 * @return WflNamedQueryResponse on success. NULL on error.
	 */
	private function namedQuery( $pubChannelId, $filterParam, $expectError=false, $testCaseName='', $categoryId = null
		,$modified = null, $publicationId = null, $sortOrder = null, $search = null )
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';

			$queryParams = array();
			$req = new WflNamedQueryRequest();
			$req->Ticket	= $this->ticket;
			$req->User 		= $this->user;
			$req->Query		= $this->query;

			if ( !is_null( $publicationId ) ) {
				// Add the PubChannelId as a param.
				$queryParam = new QueryParam();
				$queryParam->Property = 'PublicationId';
				$queryParam->Operation = '=';
				$queryParam->Value = $publicationId;
				$queryParams[] = $queryParam;
			}

			if( !is_null( $pubChannelId ) ) {
				// Add the PubChannelId as a param.
				$queryParam = new QueryParam();
				$queryParam->Property = 'PubChannelIds';
				$queryParam->Operation = '=';
				$queryParam->Value = $pubChannelId;
				$queryParams[] = $queryParam;
			}

			// Set the PublishStatus if it is not null;
			if ( !is_null( $filterParam ) ) {
				$queryParam = new QueryParam();
				$queryParam->Property = 'PublishStatus';
				$queryParam->Operation = '=';
				$queryParam->Value = $filterParam;
				$queryParams[] = $queryParam;
			}

			// Construct the Category Id query param.
			if ( !is_null( $categoryId) ) {
				$queryParam = new QueryParam();
				$queryParam->Property = 'CategoryId';
				$queryParam->Operation = '=';
				$queryParam->Value = $categoryId;
				$queryParams[] = $queryParam;
			}

			// Construct the Modified query param.
			if ( !is_null( $modified ) ) {
				$queryParam = new QueryParam();
				$queryParam->Property = 'Modified';
				$queryParam->Operation = 'within';
				$queryParam->Value = $modified;
				$queryParams[] = $queryParam;
			}

			// Construct the Search query param.
			if ( !is_null( $search ) ) {
				$queryParam = new QueryParam();
				$queryParam->Property = 'Search';
				$queryParam->Operation = '=';
				$queryParam->Value = $search;
				$queryParams[] = $queryParam;
			}

			$req->Params = $queryParams;

			// Determine any Ordering of the Query that needs to be done.
			$sortOrderArray = array();
			if ( $sortOrder ) foreach ( $sortOrder as $property => $direction ) {

				$orderParam = new QueryOrder();
				$orderParam->Direction = $direction;
				$orderParam->Property = $property;
				$sortOrderArray[] = $orderParam;
			}

			// Add ordering, if it is set.
			if (!empty( $sortOrderArray ) ) {
				$req->Order = $sortOrderArray;
			}

			// If we expect an error, make sure it does not get logged in the server logging.
			$expectedError = ( $expectError ) ? '(S1000)' : '';

			// Execute the Query.
			require_once BASEDIR.'/server/utils/TestSuite.php';
			$this->utils = new WW_Utils_TestSuite();
			return $this->utils->callService( $this, $req, $testCaseName, $expectedError );
		} catch( BizException $e ) {
			if( !$expectError ) { // We don't expect an error here.
				$this->setResult( 'ERROR', $testCaseName . ' NamedQuery service: failed: '.$e->getMessage().'<br/>'.'Detail: '.$e->getDetail() );
			}	
		}
		return null;
	}

	/**
	 * Validate the WflNamedQueryResponse result
	 *
	 * @param WflNamedQueryResponse $response
	 * @param string $testCaseName
	 * @param bool $expectedFormAdded
	 * @return bool
	 */
	private function validateNamedQueryResp( $response, $testCaseName, $expectedFormAdded )
	{
		$return = true;

		// Determine column indexes to work with
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
		$requestedPropertyNames = array(); // Normally holds additional property names, in our case empty.
		$minProps = BizNamedQuery::getPublishManagerPropertyNames($requestedPropertyNames);
		$indexes = array_combine( array_values( $minProps ), array_fill( 1, count( $minProps ), -1 ) );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found.
				}
			}
		}
		
		// Check if all expected columns are returned.
		foreach( $minProps as $minProp ) {
			if( $indexes[$minProp] == -1 ) {
				$this->setResult( 'ERROR', $testCaseName . 'Expected NamedQuery to return column "'.$minProp.'" '. 'was not found.' );
				$return = false;
			}
		}

		// We expect at least a single row to be present which we just created.
		if (count($response->Rows) < 1 && $expectedFormAdded ) {
			$this->setResult( 'ERROR', $testCaseName. 'Expected NamedQuery to return at least one PublishForm, none were returned.' );
			return false;
		} else {
			// Check that we have a record for our added Form.
			$found = false;
			foreach( $response->Rows as $row ) {
				foreach( $minProps as $minProp ) {
					$propValue = $row[$indexes[$minProp]];
					switch( $minProp ){
		                case 'ID':
							if( $propValue == $this->publishForm->MetaData->BasicMetaData->ID ) {
								$found = true;
							}
							break;
					}
				}
			}

			if (!$found && $expectedFormAdded ) { // Only raise error when we expect the Form but the Form is not found.
				$this->setResult( 'ERROR', $testCaseName. 'Expected at least one record for the added Form for this test, it could not be found.');
				$return = false;
			}
		}

		return $return;
	}

	/**
	 * Check the Response object to see if we have an Object that matches the passed ID.
	 *
	 * @param WflNamedQueryResponse $response The Response Object
	 * @param int $objectId
	 * @return bool Whether or not the Object ID was found in the Response.
	 */
	private function responseContainsObject( WflNamedQueryResponse $response, $objectId )
	{
		$indexes = array( 'ID' => -1 );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found.
				}
			}
		}

		// Check that we have a record for our added publishForm.
		$found = false;
		foreach( $response->Rows as $row ) {
			$rowId = $row[$indexes['ID']];

			if( $rowId == $objectId ) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	/**
	 * Updates a State's readyforpublishing field to be set to 'on' or empty.
	 *
	 * @param int $stateId The Id of the State to be updated
	 * @param bool $readyForPublishing If true readyforpublishing is set to 'on', empty otherwise.
	 * @return bool Whether or not the operation was succesful.
	 */
	private function updateStatus($stateId, $readyForPublishing)
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
		$value = ($readyForPublishing) ? 'on' : '';
		$success = DBAdmStatus::updateRow('states', array('readyforpublishing' => $value), 'id = ?', array($stateId));
		return $success;
	}

	/**
	 * Updates the Contained Target between a Dossier and a PublishForm to the provided date.
	 *
	 * @param string $publishedDate A date in 'Y-m-dTH:i:s' format.
	 * @param string $parentId, the parent Dossier to be used, if null $this->dossier will be used.
	 * @param string $childId, the child PublishForm to be used, if null $this->publishForm will be used.
	 * @return bool Whether or not the operation was succesful.
	 */
	private function updateContainedTargetPublishedDate( $publishedDate, $parentId=null, $childId=null )
	{
		$success = false;
		$parent = ( is_null( $parentId ) ) ? $this->dossier->MetaData->BasicMetaData->ID : $parentId;
		$child = ( is_null( $childId ) ) ? $this->publishForm->MetaData->BasicMetaData->ID : $childId;

		$relationId = BizRelation::getObjectRelationId($parent, $child, 'Contained');
		if ($relationId) {
			// Update the Target.
			require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
			$success = DBTarget::updateRow('targets', array('publisheddate' => $publishedDate), 'objectrelationid = ?', array($relationId));
		}
		return $success;
	}

	/**
	 * Updates an Object field.
	 *
	 * To be used only for testing purposes, to quickly manipulate the test data.
	 *
	 * @param int $publishFormId The PublishFormId for which to update the smart_objects table.
	 * @param string $field The Column name for which to update the smart_objects table.
	 * @param string $value The value to set on the smart_objects table.
	 * @return bool Whether or not the operation was succesful.
	 */
	private function updateObjectField ( $publishFormId, $field, $value )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		return DBObject::updateRow('objects', array( $field => $value), 'id = ?', array( $publishFormId ) );
	}

	/**
	 * Analyzes if the order of the PublishForms matches the correct order.
	 *
	 * @param WflNamedQueryResponse $response The response object
	 * @param string[] $publishFormOrder An array of PublishFormIds in the expected order.
	 * @return bool Whether or not the order of the objects in the response match those in the $publishFormOrder.
	 */
	private function analyzeQueryResponseSorting( $response, $publishFormOrder ) {
		$indexes = array( 'ID' => -1 );
		foreach( array_keys( $indexes ) as $colName ) {
			foreach( $response->Columns as $index => $column ) {
				if( $column->Name == $colName ) {
					$indexes[$colName] = $index;
					break; // found.
				}
			}
		}

		// Check that we have a correct ordering for our records.
		$found = true;
		foreach ($publishFormOrder as $index => $order ) {
			$row = $response->Rows[$index];
			if ( $row && $row[$indexes['ID']] != $order ) {
				$found = false;
				break;
			}
		}
		return $found;
	}
}