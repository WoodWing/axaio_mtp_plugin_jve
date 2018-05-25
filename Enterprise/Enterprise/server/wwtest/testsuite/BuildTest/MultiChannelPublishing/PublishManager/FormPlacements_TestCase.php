<?php
/**
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishManager_FormPlacements_TestCase extends TestCase
{
	// Session related data.
	private $ticket = null;
	private $vars = null;
	private $mcpUtils = null; // MultiChannelPublishingUtils

	// Objects and test data.
	private $dossier = null;
	private $template = null;
	private $form = null;
	private $article = null;

	public function getDisplayName()
	{
		return 'Form Placements and Relations';
	}

	public function getTestGoals()
	{
		return 'Checks if an article can be correctly placed on a form and relational data is preserved after updates.';
	}

	public function getTestMethods()
	{
		return 'Scenario\'s:<ol>
			<li>01: ObjectRelation creation test, verify that the ParentType is set on the table.</li>
			<li>02: ObjectRelation update test, verify that the ParentType is still set after an update.</li>
			</ol>';
	}

	public function getPrio()
	{
		return 1;
	}

	/**
	 * Runs the testcases for this TestSuite.
	 */
	final public function runTest()
	{
		// Use the publishing Utils.
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';
		$this->mcpUtils = new MultiChannelPublishingUtils();
		if( !$this->mcpUtils->initTest( $this ) ) {
			return;
		}
		
		// Retrieve the Ticket that has been determined by "Setup test data" TestCase.
		$this->vars = $this->getSessionVariables();
		$this->ticket = @$this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 
				'Please enable the "Setup test data" entry and try again.' );
			return;
		}

		// Scenario 01: Attempt to create an ObjectRelation and check that the ParentType is filled in.
		if( !$this->testScenario01() ) {
			return;
		}

		// Scenario 02: Attempt an update of an ObjectRelation and check that the ParentType is filled in.
		if( !$this->testScenario02() ) {
			return;
		}
	}

	/**
	 * Test 01: Test that ObjectRelation creation also fills in the ParentType column.
	 *
	 * The ParentType field should be set when creating a new ObjectRelation.
	 *
	 * @return bool Whether or not the test was succesful.
	 */
	private function testScenario01()
	{
		$retVal = true;

		// Create the basic setup.
		if( !$this->setupObjects() ) {
			$this->setResult( 'ERROR',  'Scenario 1: Unable to set up the basic test data.');
			$retVal = false;
		}
		
		if( $retVal ) {
			// Place the article on the form. (Create a Placed object relation between both.)
			$formId = $this->form->MetaData->BasicMetaData->ID;
			$articleId = $this->article->MetaData->BasicMetaData->ID;
			$stepInfo = 'Place the Article on the Publish Form.';
			$composedRelation = $this->mcpUtils->composePlacedRelation( $formId, $articleId, 0, null );
			$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ) );
			$relation = isset( $relations[0] ) ? $relations[0] : null;
			if( !is_null( $relation ) && $relation ) {
				// Test that the ParentType was set on the relation.
				if( !$this->isObjectRelationParentTypeSet() ) {
					$this->setResult( 'ERROR', 'Scenario 1: The created ObjectRelation did not have '.
						'its ParentType set correctly or is not of the type \'PublishForm\'' );
					$retVal = false;
				}
			} else {
				$this->setResult( 'ERROR', 'Scenario 1: Unable to create a relation for the form and article.');
				$retVal = false;
			}
		}
		
		// Teardown the test set.
		if( !$this->tearDownObjects() ) {
			$this->setResult( 'ERROR',  'Scenario 1: Unable to tear down the test data.');
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Test 02: Test that ObjectRelation updates leave the ParentType intact in the ObjectRelation table.
	 *
	 * The ParentType field should remain when updating an ObjectRelation.
	 *
	 * @return bool Whether or not the test was succesful.
	 */
	private function testScenario02()
	{
		$retVal = true;

		// Create the basic setup.
		if( !$this->setupObjects() ) {
			$this->setResult( 'ERROR', 'Scenario 2: Unable to set up the basic test data.' );
			$retVal = false;
		}

		// Place the article on the form. (Create a Placed object relation between both.)
		$formId = $this->form->MetaData->BasicMetaData->ID;
		$articleId = $this->article->MetaData->BasicMetaData->ID;
		$stepInfo = 'Place the Article on the Publish Form.';
		$composedRelation = $this->mcpUtils->composePlacedRelation( $formId, $articleId, 0, null );
		$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ) );
		$relation = isset( $relations[0] ) ? $relations[0] : null;
		if( !is_null( $relation ) && $relation ) {
			// Update the Relation and test that it is still set correctly.
			try {
				require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';
				$service = new WflUpdateObjectRelationsService();
				$request = new WflUpdateObjectRelationsRequest();
				$request->Ticket = $this->ticket;
				$request->Relations = array( $relation );
				$service->execute( $request );
			} catch( BizException $e ) {
				$this->setResult( 'ERROR', 'Scenario 2: Unable to update the ObjectRelation, which is unexpected.' );
				$retVal = false;
			}
	
			// Test that the ParentType was set on the relation.
			if( !$this->isObjectRelationParentTypeSet() ) {
				$this->setResult( 'ERROR', 'Scenario 2: The created ObjectRelation did not have ' .
					'its ParentType set correctly or is not of the type \'PublishForm\'');
				$retVal = false;
			}
		} else {
			$this->setResult( 'ERROR', 'Scenario 2: Unable to create a relation for the form and article.' );
			$retVal = false;
		}

		// Teardown the test set.
		if( !$this->tearDownObjects() ) {
			$this->setResult( 'ERROR',  'Scenario 2: Unable to tear down the test data.');
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Creates the objects to be used in this test.
	 *
	 * @return bool
	 */
	private function setupObjects()
	{
		$retVal = true;

		// Create a Publish Form Template.
		$stepInfo = 'Create the Publish Form Template.';
		$this->template = $this->mcpUtils->createPublishFormTemplateObject( $stepInfo );
		if( is_null($this->template) ) {
			$this->setResult( 'ERROR', 'Could not create the Publish Form Template.');
			$retVal = false;
		}

		// Create a Dossier.
		$stepInfo = 'Create the Dossier object.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo );
		if( is_null($this->dossier) ) {
			$this->setResult( 'ERROR', 'Could not create the Dossier.' );
			$retVal = false;
		}

		// Create a Publish Form (based on the template) and assign it to the dossier.
		$this->form = null;
		if( $this->template && $this->dossier ) {
			$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
			$this->form = $this->mcpUtils->createPublishFormObject( $this->template, $this->dossier, $stepInfo );
			if( is_null($this->form) ) {
				$this->setResult( 'ERROR', 'Could not create the Publish Form object.' );
				$retVal = false;
			}
		}
		
		// Create an article object.
		$stepInfo = 'Create the Article object.';
		$this->article = $this->mcpUtils->createArticle( $stepInfo );
		if( is_null($this->article) ) {
			$this->setResult( 'ERROR', 'Could not create the Article object.' );
			$retVal = false;
		}

		return $retVal;
	}

	/**
	 * Permanently deletes objects used in this test.
	 *
	 * @return bool
	 */
	private function tearDownObjects()
	{
		$retVal = true;
		
		// Permanent delete the Article.
		if( $this->article ) {
			$id = $this->article->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Article object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport );
				$retVal = false;
			}
			$this->article = null;
		}

		// Permanent delete the Publish Form.
		if( $this->form ) {
			$id = $this->form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Publish Form object: '.$errorReport );
				$retVal = false;
			}
			$this->form = null;
		}
		
		// Permanent delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->dossier = null;
		}
		
		// Permanent delete the Publish Form Template.
		if( $this->template ) {
			$id = $this->template->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Publish Form Template object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Publish Form Template object: '.$errorReport );
				$retVal = false;
			}
			$this->template = null;
		}
		return $retVal;
	}
	
	/**
	 * Checks if there was an ObjectRelation set up, and that the ParentType of that Relation is set correctly.
	 *
	 * Retrieves the Placed relation for which our created PublishForm is the parent (and the article would be the
	 * child) as a raw row from the database and tests that the ParentType is set for this row, and that it matches
	 * the parent type: PublishForm.
	 *
	 * @return bool Whether or not the ObjectRelation contains a correct ParentType.
	 */
	private function isObjectRelationParentTypeSet()
	{
		require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
		$result = false;
		$parentId = $this->form->MetaData->BasicMetaData->ID;

		// Retrieve the raw rows for the ObjectRelations from the database.
		try {
			$relationRows = DBOBjectRelation::getObjectRelations( $parentId, 'childs', 'Placed', false);

			// Check if the ParentType was set, and was set correctly.
			if ($relationRows) foreach ($relationRows as $row) {
				if (!empty($row['parenttype']) && $row['parenttype'] == 'PublishForm') {
					$result = true;
				}
			}
		} catch ( BizException $e ) {
			$result = false;
		}
		return $result;
	}
}
