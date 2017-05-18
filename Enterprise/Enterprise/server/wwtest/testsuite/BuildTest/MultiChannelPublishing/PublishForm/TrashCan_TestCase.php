<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * @TODO: Doublecheck all the docblocks prior to checkin.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_TrashCan_TestCase extends TestCase
{
	// Session related data.
	private $ticket = null;
	private $vars = null;
	private $mcpUtils = null; // MultiChannelPublishingUtils

	// Objects and test data.
	private $dossier = null;
	private $template = null;
	private $form = null;
	
	// Indication where test data resides: Workflow or Trash
	private $dossierInTrashCan = false;
	private $templateInTrashCan = false;
	private $formInTrashCan = false;

	public function getDisplayName()
	{
		return 'Forms in Trash Can';
	}

	public function getTestGoals()
	{
		return 'Checks if Publish Forms and Templates can be / are deleted and restored correctly.';
	}

	public function getTestMethods() { return 'Scenario\'s:<ol>
		<li>01: Template Test: Create a new Publish Form Template, Create a Publish Form and attempt to remove the Template.</li>
		<li>02: Template Test: Create a new Publish Form Template, Create a Publish Form, put it in the trash and attempt to remove the Template.</li>
		<li>03: Template Test: Create a new Publish Form Template, Create a Publish Form, remove permanently and attempt to remove the Template.</li>
		<li>04: Publish Form Test: Create Template, Dossier, Form and Article, Test that placements are removed at the right time.</li>
		<li>05: Dossier Test: Create Template, Dossier, Form, Test that the Form is removed in the correct way along with the Dossier.</li>
		<li>06: Object Deletion Test: When deleting an object that was placed permanently there should be no placement on the Publish Form.</li>
		<li>07: Publish Form Restoration: When restoring a Publish Form, test that this is only possible if the related Dossier is active.</li>
		<li>08: Publish Form Restoration: When restoring a Publish Form, test that there are no other active Publish Forms.</li>
		<li>09: Publish Form Restoration: When restoring a Publish Form, test that there are channel available.</li>
		<li>10: Publish Form Deletion Test: A Publish Form may not be removed if it is still published.</li>
		</ol>'; }

	public function getPrio()
	{
		return 30;
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

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
		$this->vars = $this->getSessionVariables();

		// Test the ticket.
		$this->ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		
		// Scenario 01: Attempt to create a Template, Form, Dossier and attempt to remove 
		// the template. (should not be possible.)
		$this->testScenario01();

		// Scenario 02: Attempt to create a Template, Form, Dossier, move the Form to the
		// Trash and attempt to remove the template. (should not be possible.)
		$this->testScenario02();

		// Scenario 03: Attempt to create a Template, Form, Dossier, permanently remove
		// the Form and attempt to remove the template. (should be possible.)
		$this->testScenario03();

		// Scenario 04: Form Test: Create Template, Dossier, Form and Article, Test that
		// placements are removed at the right time.
		$this->testScenario04();

		// Scenario 05: Dossier Tests: Test the behaviour when removing dossiers.
		$this->testScenario05();

		// Scenario 06: Object Deletion Scenario: When permanently removing an Object, make
		// sure that any placements and Placed / DeletedPlaced relations are also removed
		// for that object.
		$this->testScenario06();

		// Scenario 07: When restoring a Publish Form, test that this is only possible if
		// the related Dossier is active.
		$this->testScenario07();

		// Scenario 08: When restoring a Publish Form, test that this is only possible if
		// there is not already an active Publish Form in the Dossier.
		$this->testScenario08();

		// Scenario 09: When restoring a Publish Form, test that this is only possible if
		// there's channel(Target) available for the PublishForm.
		$this->testScenario09();

		// Scenario 10: When trying to remove a Publish Form that is still Published should
		// not be possible, test the business rules so that we can only remove the Form when 
		// it is not Published.
		$this->testScenario10();
	}

	/**
	 * Test 01: Create a Template with a Publish Form and attempt to remove the Template.
	 *
	 * This test should fail as a Template may only be removed if there are no Publish Forms 
	 * attached to it.
	 */
	private function testScenario01()
	{
		$errorReport = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 1' ) ) {
				break;
			}
			
			// Attempt to remove the Template, if successful print an error message.
			$id = $this->template->MetaData->BasicMetaData->ID;
			$this->mcpUtils->setExpectedError( 'ERR_PUBLISHFORMTEMPLATE_IN_USE' );
			$stepInfo = 'Scenario 1: Attempt to remove the Template, while there is an active form (Not Allowed).';
			if( $this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array('Workflow') ) ) {
				$this->setResult( 'ERROR', 'Scenario 1: Should not be able to remove a Publish Form Template while there are still Forms for that Template.' );
				$this->template = null;
				break;
			}
		} while( false );
		
		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 1' );
	}

	/**
	 * Test 02: Create a Template with a Publish Form, move the Form to the Trash and attempt 
	 * to remove the Template.
	 *
	 * This test should fail as a Template may only be removed if there are no Publish Forms 
	 * attached to it, even if that Form resides in the Trashcan.
	 */
	private function testScenario02()
	{
		$errorReport = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 2' ) ) {
				break;
			}
			
			// Move the Form to the Trash Can.
			$id = $this->form->MetaData->BasicMetaData->ID;
			$stepInfo = 'Move the Publish Form to the Trash Can.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Could not move the Publish Form to the Trash Can.' );
				break;
			}
			$this->formInTrashCan = true;
	
			// Attempt to move the Template to the Trash Can, if succesful print an error message.
			$id = $this->template->MetaData->BasicMetaData->ID;
			$stepInfo = 'Scenario 2: Move Publish Form Template to Trash Can (Not Allowed).';
			$this->mcpUtils->setExpectedError( 'ERR_PUBLISHFORMTEMPLATE_IN_USE' );
			if( $this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, false, array('Workflow') ) ) {
				$this->setResult( 'ERROR', 'Scenario 2: Should not be able to remove a '.
					'Publish Form Template while there are still Forms for that Template.' );
				$this->templateInTrashCan = true;
				break;
			}
		} while( false );

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 2' );
	}

	/**
	 * Test 03: Create a Template with a Publish Form, remove the Form and attempt to 
	 * remove the Template.
	 *
	 * This test should fail as a Template may only be removed if there are no Publish Forms 
	 * attached to it, even if that Form resides in the Trashcan.
	 */
	private function testScenario03()
	{
		$errorReport = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 3' ) ) {
				break;
			}

			// Permanently remove the Form.
			$id = $this->form->MetaData->BasicMetaData->ID;
			$stepInfo = 'Scenario 3: Permanently remove the Publish Form.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Scenario 3: Unable Permanently remove the Test Form.' );
				break;
			}
			$this->form = null;
	
			// Move the Template to the Trash Can.
			$id = $this->template->MetaData->BasicMetaData->ID;
			$stepInfo = 'Scenario 3: Permanently remove the Publish Form Template (Not Allowed).';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, false, array('Workflow') ) ) {
				$this->setResult( 'ERROR',  'Scenario 3: Should be able to remove the '.
								'Publish Form Template if there are no more Publish Forms on it.' );
				break;
			}
			$this->templateInTrashCan = true;
		} while( false );

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 3' );
	}

	/**
	 * Test 04: Attempt actions on the Publish Form. When Permanently removing a Publish Form, 
	 * also ensure that the placements for the objects were removed.
	 *
	 * This test should fail as a Template may only be removed if there are no Publish Forms 
	 * attached to it, even if that Form resides in the Trashcan.
	 */
	private function testScenario04()
	{
		$errorReport = false;
		$article = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 4' ) ) {
				break;
			}

			// Create an Article to place on the Form.
			$stepInfo = 'Scenario 4: Create an article to be placed on the Form.';
			$article = $this->mcpUtils->createArticle( $stepInfo );
			if( !$article ) {
				$this->setResult( 'ERROR', 'Scenario 4: Unable to create an article to be placed on the Form.' );
				break;
			}

			// Create a Placed Relation between the Form and the Article.
			$formId = $this->form->MetaData->BasicMetaData->ID;
			$articleId = $article->MetaData->BasicMetaData->ID;
			$stepInfo = 'Place the Article on the Publish Form.';
			$composedRelation = $this->mcpUtils->composePlacedRelation( $formId, $articleId, 0, null );
			$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ) );
			if( is_null( $relations ) || !$relations[0] ) {
				$this->setResult( 'ERROR', 'Scenario 4: Unable to create a relation for the form and article.' );
				break;
			}
	
			// Move the Publish Form to Trash Can.
			$stepInfo = 'Move the Publish Form to Trash Can.';
			if( !$this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 4: Unable to delete the Publish Form.' );
				break;
			}
			$this->formInTrashCan = true;
	
			// Check that the relation still exists.
			if( !$this->doesRelationExist( $formId, $articleId, 'DeletedPlaced' ) ) {
				$this->setResult( 'ERROR', 'Scenario 4: The object Relation was removed, which was unexpected.' );
				break;
			}

			// Check that the placement still exists.
			if( !$this->doesPlacementExist( $formId, $articleId, 'DeletedPlaced' ) ) {
				$this->setResult( 'ERROR', 'Scenario 4: The object Placement was removed, which was unexpected.' );
				break;
			}
	
			// Permanently remove the form.
			$stepInfo = 'Scenario 4: Permanently remove Publish Form.';
			if( !$this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, true, array('Trash') ) ) {
				$this->setResult( 'ERROR', 'Scenario 4: Unable to permanently remove the Publish Form.' );
				break;
			}
			$this->form = null;
	
			// Check that the relation no longer exists.
			if( $this->doesRelationExist( $formId, $articleId, 'DeletedPlaced' ) ) {
				$this->setResult( 'ERROR', 'Scenario 4: The object Relation was not removed, which was unexpected.' );
				break;
			}
			
			// Check that the placement no longer exists.
			if( $this->doesPlacementExist( $formId, $articleId, 'DeletedPlaced' ) ) {
				$this->setResult( 'ERROR', 'Scenario 4: The object Placement was not removed, which was unexpected.' );
				break;
			}
		} while( false );
		
		// Tear down the article.
		if( $article ) {
			$id = $article->MetaData->BasicMetaData->ID;
			$stepInfo = 'Scenario 4: Tear down the Article object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array('Workflow') ) ) {
				$this->setResult( 'ERROR', 'Scenario 4: Unable to delete the Article.' );
			}
		}

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 4' );
	}

	/**
	 * Test 05: Attempt actions on the Dossier.
	 *
	 * When Permanently removing a Dossier, also ensure that the Publish Form was removed.
	 */
	private function testScenario05()
	{
		$errorReport = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 5' ) ) {
				break;
			}
		
			$formId = $this->form->MetaData->BasicMetaData->ID;
			$dossierId = $this->dossier->MetaData->BasicMetaData->ID;

			// Move the Dossier to the TrashCan.
			$stepInfo = 'Scenario 5: Move the Dossier to the TrashCan.';
			if( !$this->mcpUtils->deleteObject( $dossierId, $stepInfo, $errorReport, false, array('Workflow') ) ) {
				$this->setResult( 'ERROR', 'Scenario 5: The Dossier could not be removed which was unexpected.' );
				break;
			}
			$this->dossierInTrashCan = true;
	
			// Check that the Dossier was moved to the Trashcan.
			if( !$this->doesObjectExist( $dossierId, array('Trash') ) ) {
				$this->setResult( 'ERROR', 'Scenario 5: The Dossier was not moved to the Trash, which was unexpected.' );
				break;
			}
	
			// Check that the Publish Form was moved to the Trashcan.
			if( !$this->doesObjectExist( $formId, array('Trash') ) ) {
				$this->setResult( 'ERROR', 'Scenario 5: The Publish Form was not moved to the Trash, which was unexpected.' );
				break;
			}
	
			// Permanently remove the Dossier (from the Trash Can).
			// The server implicitly should remove the Publish Form as well.
			$stepInfo = 'Scenario 5: Permanently remove the Dossier (from the Trash Can).';
			if( !$this->mcpUtils->deleteObject( $dossierId, $stepInfo, $errorReport, true, array('Trash') ) ) {
				$this->setResult( 'ERROR', 'Scenario 5: The Dossier could not be permanently removed which was unexpected.' );
				break;
			}
			$this->dossier = null;
			$this->form = null;
	
			// Check that the Publish Form was permanently removed.
			if( $this->doesObjectExist( $formId, array('Trash') ) ) {
				$this->setResult( 'ERROR', 'Scenario 5: The Publish Form was not permanently removed, which was unexpected.' );
				break;
			}
	
			// Check that the Dossier was permanently removed.
			if( $this->doesObjectExist( $dossierId, array('Trash') ) ) {
				$this->setResult( 'ERROR', 'Scenario 5: The Dossier was not permanently removed, which was unexpected.' );
				break;
			}
		} while( false );

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 5' );
	}

	/**
	 * Test 06: Check that when we remove an object that was Placed on a form that the 
	 * placement is also removed from the Form.
	 */
	private function testScenario06()
	{
		$errorReport = null;
		$article = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 6' ) ) {
				break;
			}

			// Create an Article to place on the Form.
			$stepInfo = 'Create an Article to be placed on the Publish Form.';
			$article = $this->mcpUtils->createArticle( $stepInfo );
			if( !$article ) {
				$this->setResult( 'ERROR', 'Scenario 6: Unable to create an article to be placed on the Form.' );
				break;
			}
	
			// Create a Placed Relation between the Form and the Article.
			$formId = $this->form->MetaData->BasicMetaData->ID;
			$articleId = $article->MetaData->BasicMetaData->ID;
			$stepInfo = 'Place the Article on the Publish Form.';
			$composedRelation = $this->mcpUtils->composePlacedRelation( $formId, $articleId, 0, null );
			$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ) );
			if( is_null( $relations ) || !$relations[0]  ) {
				$this->setResult( 'ERROR', 'Scenario 6: Unable to create a relation for the Form and Article.' );
				break;
			}
	
			// Permanently remove the Article.
			$stepInfo = 'Scenario 6: Permanently remove the Article.';
			if( !$this->mcpUtils->deleteObject( $articleId, $stepInfo, $errorReport, true, array('Workflow') ) ) {
				$this->setResult( 'ERROR', 'Scenario 6: Unable to permanently remove the Article.' );
				break;
			}
			$article = null;
	
			// Check that the article was removed permanently.
			if( $this->doesObjectExist( $articleId, array('Trash', 'Workflow') ) ) {
				$this->setResult( 'ERROR', 'Scenario 6: Article was not removed permanently, which was unexpected.' );
				break;
			}
	
			// Check that the relation no longer exists between the form and the object.
			if( $this->doesRelationExist( $formId, $articleId, 'Placed') ) {
				$this->setResult( 'ERROR', 'Scenario 6: The object Relation was not removed, which was unexpected.' );
				break;
			}
	
			// Check that the placement no longer exists.
			if( $this->doesPlacementExist( $formId, $articleId, 'Placed') ) {
				$this->setResult( 'ERROR', 'Scenario 6: The object Placement was not removed, which was unexpected.' );
				break;
			}
		} while( false );

		// Tear down the article.
		if( $article ) {
			$id = $article->MetaData->BasicMetaData->ID;
			$stepInfo = 'Scenario 6: Tear down the Article object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Scenario 6: Unable to delete the Article.' );
			}
		}

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 6' );
	}

	/**
	 * Restoration scenario. Only allow restoration of a Publish Form if the dossier that 
	 * contains the Form is active.
	 *
	 * Test that a Publish Form can only be restored if the dossier that contains said 
	 * Publish Form is also already made active for the current Publish Form.
	 */
	private function testScenario07()
	{
		$errorReport = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 7' ) ) {
				break;
			}

			$formId = $this->form->MetaData->BasicMetaData->ID;
			$dossierId = $this->dossier->MetaData->BasicMetaData->ID;
			
			// Move the Publish Form to the Trash Can.
			$stepInfo = 'Scenario 7: Move the Publish Form to the Trash Can.';
			if( !$this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 7: The Publish Form could not be deleted, which was unexpected.' );
				break;
			}
			$this->formInTrashCan = true;
	
			// Move the Dossier the Trash Can.
			$stepInfo = 'Scenario 7: Move the Dossier the Trash Can.';
			if( !$this->mcpUtils->deleteObject( $dossierId, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 7: The Dossier could not be deleted, which was unexpected.' );
				break;
			}
			$this->dossierInTrashCan = true;
	
			// Test Set is prepared, attempt to restore the Publish Form while the Dossier is still in the trash.
			$stepInfo = 'Scenario 7: Restore the Publish Form while the Dossier is still in the Trash Can (Not Allowed).';
			$map = new BizExceptionSeverityMap( array( 'ERR_PUBLISHFORM_RESTORE_DOSSIER' => 'INFO' ) );
			if( $this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Scenario 7: The Publish Form was restored while its Dossier was in the Trash, this was unexpected.' );
				break;
			}
			unset( $map );
	
			// So far so good, now restore the Dossier and attempt the test again.
			$stepInfo = 'Scenario 7: Restore the Dossier object.';
			if( !$this->mcpUtils->restoreObject( $dossierId, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Scenario 7: The Dossier was not restored, this was unexpected.' );
				break;
			}
			$this->dossierInTrashCan = false;
	
			// The dossier was restored, attempt to restore the form again.
			$stepInfo = 'Scenario 7: Restore the Publish Form object.';
			if( !$this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Scenario 7: The Publish Form was not restored while its Dossier was active, this was unexpected.' );
				break;
			}
			$this->formInTrashCan = false;

		} while( false );

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 7' );
	}

	/**
	 * Restoration scenario. Only allow restoration of a Publish Form if the dossier does 
	 * not contain an active Publish Form.
	 *
	 * Test that a Publish Form can only be restored if the dossier that contains said 
	 * Publish Form does not already contain an active Publish Form. A dossier may only have 
	 * a single Publish Form in an active state at any single time.
	 */
	private function testScenario08()
	{
		$errorReport = null;
		$form2 = null;
		$form2InTrashCan = false;

		do {
			// Create the basic setup.
			LogHandler::Log('PublishFormDeletion_TestCase', 'INFO', 'Scenario 8: Set up basic test data.' );
			if( !$this->setupTestData( 'Scenario 8' ) ) {
				break;
			}

			$formId = $this->form->MetaData->BasicMetaData->ID;
			
			// Move the Publish Form to the Trash Can.
			$stepInfo = 'Scenario 8: Move the Publish Form to the Trash Can.';
			if( !$this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 8: The Publish Form could not be deleted, which was unexpected.' );
				break;
			}
			$this->formInTrashCan = true;
	
			// Create a the second Publish Form.
			$stepInfo = 'Scenario 8: Create the second Publish Form.';
			$form2 = $this->mcpUtils->createPublishFormObject( $this->template, $this->dossier, $stepInfo );
			if( is_null($form2) ) {
				$this->setResult( 'ERROR',  'Scenario 8: Could not create the second Publish Form object which was unexpected.' );
				break;
			}
	
			// Attempt to restore the first Publish Form (should not be possible).
			$stepInfo = 'Scenario 8: Attempt to restore the first Publish Form object (Not Allowed).';
			$map = new BizExceptionSeverityMap( array( 'ERR_PUBLISHFORM_RESTORE_MULTIPLE' => 'INFO' ) );
			if( $this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport) ) {
				$this->setResult( 'ERROR', 'Scenario 8: The first Publish Form was restored while the Dossier already had an active Publish Form.' );
				break;
			}
			unset( $map );

			// Move the second Publish Form to the Trash Can.
			$stepInfo = 'Scenario 8: Move the second Publish Form to the Trash Can.';
			if( !$this->mcpUtils->deleteObject( $form2->MetaData->BasicMetaData->ID, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 8: The second Publish Form could not be moved to the Trashcan.' );
				break;
			}
			$form2InTrashCan = true;
	
			// Attempt to restore the first Form (should be possible as the second one is in the trash).
			$stepInfo = 'Scenario 8: Restore the first Publish Form object.';
			if( !$this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Scenario 8: The first Publish Form was not restored while it should be possible.' );
				break;
			}
			$this->formInTrashCan = false;
		} while( false );
		
		// Tear down the second Publish Form.
		if( $form2 ) {
			$id = $form2->MetaData->BasicMetaData->ID;
			$area = $form2InTrashCan ? 'Trash' : 'Workflow';
			$stepInfo = 'Scenario 8: Tear down the second Publish Form.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array($area) ) ) {
				$this->setResult( 'ERROR', 'Scenario 8: Failed to tear down the second Publish Form object.' );
			}
		}

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 8' );
	}

	/**
	 * Restoration scenario. Only allow restoration of a Publish Form if the dossier has the
	 * correct channel assigned (where the PublishForm will be tied to).
	 *
	 * Test that a Publish Form can only be restored if the dossier is still assigned with the
	 * original Channel(Target). If the channel is removed from the Dossier, the PublishForm
	 * cannot be restored and an error is expected.
	 *
	 */
	private function testScenario09()
	{
		$errorReport = null;

		do {
			// Create the basic setup.
			LogHandler::Log('PublishFormDeletion_TestCase', 'INFO', 'Scenario 9: Set up basic test data.' );
			if( !$this->setupTestData( 'Scenario 9' ) ) {
				break;
			}

			$formId = $this->form->MetaData->BasicMetaData->ID;

			// Scenario a: Dossier is assigned with only one pub channel-Issue.
			// Move the Publish Form to the Trash Can.
			$stepInfo = 'Scenario 9: Move the Publish Form to the Trash Can.';
			if( !$this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The Publish Form could not be deleted, which was unexpected.' );
				break;
			}
			$this->formInTrashCan = true;

			// Remove the Target(channel) from the Dossier.
			$dossierTargets = $this->dossier->Targets; // Remember the original targets of the Dossier before removing it.
			if( !$this->removeTargets( $dossierTargets ) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The Target of the Dossier cannot be removed, which was unexpected.');
				break;
			}

			// Attempt to restore the Publish Form (should not be possible as the Channel is no longer available).
			$stepInfo = 'Scenario 9: Attempt to restore the Publish Form object (Not Allowed because no Channel available).';
			$map = new BizExceptionSeverityMap( array( 'ERR_PUBLISHFORM_RESTORE_NO_CHANNEL' => 'INFO' ) );
			if( $this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The Publish Form was restored while there is no channel '.
					'available for the PublishForm, this is not expected.');
				break;
			}
			unset( $map );

			// Adds back the Target(channel) to the Dossier.
			if( !$this->addTarget( $dossierTargets ) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The Target of the Dossier cannot be added, which was unexpected.');
				break;
			}

			// Attempt to restore the Form (should be possible as the channel is re-assigned to the Dossier).
			$stepInfo = 'Scenario 9: Restore the Publish Form object.';
			if( !$this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The Publish Form was not restored while it should be possible.' );
				break;
			}

			// Scenario b: Dossier is assigned with two pub channel-Issues, both of them under same Publication Channel.
			// Remove the Target(channel) from the Dossier again.
			$dossierTargets = $this->dossier->Targets; // Remember the original targets of the Dossier before removing it.
			if( !$this->removeTargets( $dossierTargets ) ) { // Remove all the Targets belong to the Dossier.
				$this->setResult( 'ERROR', 'Scenario 9: The Target of the Dossier cannot be removed, which was unexpected.');
				break;
			}

			// Add new Target to the Dossier with the newly created Issue.
			$dossierNewTarget = $this->getNewTarget(); // Create one more Issue under same Pub Channel.
			if( !$this->addTarget( array( $dossierNewTarget ) ) ) { // Dossier only has one Target (the new Target)
				$this->setResult( 'ERROR', 'Scenario 9: The Target of the Dossier cannot be added, which was unexpected.');
				break;
			}

			// Get the dossier so that its Targets are up-to-date.
			$dossierId = $this->dossier->MetaData->BasicMetaData->ID;
			$dossier = $this->getLatestObject( $dossierId, 'Scenario 9' );
			if( !$dossier ) {
				$this->setResult( 'ERROR', 'Scenario 9: Could not get the latest state of Dossier object.' );
				break;
			}
			$this->dossier = $dossier; // Getting the latest state of the Dossier after updating its Target.

			// Create new PublishForm with the new Target of the Dossier.
			// This PublishForm will be created under the new Target of the Dossier.
			$this->form2 = $this->mcpUtils->createPublishFormObject( $this->template, $this->dossier, $stepInfo,
				MultiChannelPublishingUtils::RELATION_NORMAL, null, $this->dossier->Targets );

			if( is_null( $this->form2 ) ) {
				$this->setResult( 'ERROR', 'Scenario 9: Could not create second Publish Form object.' );
				break;
			}

			// Add the original Target to the Dossier. (Dossier will now have the original Target and the newly created Target).
			// Note that the PublishForm is created under the new Target but not under the original Target.
			if( !$this->addTarget( $dossierTargets ) ) { // Dossier has two Targets (the original and the new one).
				$this->setResult( 'ERROR', 'Scenario 9: The Target of the Dossier cannot be added, which was unexpected.');
				break;
			}

			// Remove the Target where the Form is instantiated/created. The Form that is tied to this Target should be deleted too.
			if( !$this->removeTargets( array( $dossierNewTarget ) ) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The new Target of the Dossier cannot be removed, which was unexpected.');
				break;
			}

			// Attempt to restore the Publish Form (should not be possible as the new channel-issue is no longer available).
			// Even though there's another channel-issue(the original issue) that is under the same pub channel, but the
			// Form doesn't belong to that original channel-issue, so should not be restored under this channel, so error is expected.
			$stepInfo = 'Scenario 9: Attempt to restore the Publish Form object (Not Allowed because no Channel available).';
			$formId = $this->form2->MetaData->BasicMetaData->ID;
			$map = new BizExceptionSeverityMap( array( 'ERR_PUBLISHFORM_RESTORE_NO_CHANNEL' => 'INFO' ) );
			if( $this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The Publish Form was restored while there is no channel '.
					'available for the PublishForm, this is not expected.');
				break;
			}
			unset( $map );

			if( !$this->addTarget( array($dossierNewTarget) ) ) { // Add back the new Target to the Dossier.
				$this->setResult( 'ERROR', 'Scenario 9: The new Target of the Dossier cannot be added, which was unexpected.');
				break;
			}

			// Attempt to restore the Form (should be possible as the new channel-issue is re-assigned to the Dossier).
			$stepInfo = 'Scenario 9: Restore the Publish Form object.';
			$formId = $this->form2->MetaData->BasicMetaData->ID;
			if( !$this->mcpUtils->restoreObject( $formId, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Scenario 9: The Publish Form was not restored while it should be possible.' );
				break;
			}

		} while( false );

		// Delete the second PublishForm which is created only by this function.
		if( $this->form2 ) {
			$formId = $this->form2->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the second Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, true, array( 'Workflow' ) ) ) {
				$this->setResult( 'ERROR', 'Scenario 9'.': Could not tear down the second Publish Form object: '.$errorReport );
			}
			$this->form2 = null;
		}

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 9' );
	}

	/**
	 * Test Scenario 10: Removing a Publish Form while it is still Published.
	 *
	 * Test the removal function of a Publish Form while it is still Published. If this is 
	 * the case then an Exception should be thrown. When the Publish Form is not Published 
	 * we should be able to remove the Publish Form.
	 */
	private function testScenario10()
	{
		$errorReport = null;

		do {
			// Create the basic setup.
			if( !$this->setupTestData( 'Scenario 10' ) ) {
				break;
			}

			$formId = $this->form->MetaData->BasicMetaData->ID;
			
			// Simulate that the Publish Form is Published by setting the PublishedDate on the Target.
			if( !self::setPublishFormPublicationStatus( $formId, true ) ) {
				$this->setResult( 'ERROR',  'Scenario 10: Unable to set the Target PublishDate.' );
				break;
			}
	
			// Attempt to move the Publish Form to Trash Can, which should not be possible.
			$stepInfo = 'Scenario 10: Move the Publish Form to Trash Can (Now Allowed).';
			$this->mcpUtils->setExpectedError( 'ERR_PUBLISHFORM_PUBLISHED' );
			if( $this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 10: The Publish Form was deleted, which was unexpected.' );
				break;
			}
			$this->formInTrashCan = true;
	
			// Restore the Targets to an unpublished state.
			if( !self::setPublishFormPublicationStatus( $formId, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 10: Unable to unset the Target PublishDate.' );
				break;
			}
	
			// Move the Publish Form to Trash Can.
			$stepInfo = 'Scenario 10: Move the Publish Form to Trash Can.';
			if( !$this->mcpUtils->deleteObject( $formId, $stepInfo, $errorReport, false ) ) {
				$this->setResult( 'ERROR', 'Scenario 10: The Publish Form could not be deleted, which was unexpected.' );
				break;
			}
			$this->formInTrashCan = true;
	
		} while( false );

		// Delete the basic setup.
		$this->tearDownTestData( 'Scenario 10' );
	}

	/**
	 * Set up the basic MultiChannelPublishing objects for this test.
	 *
	 * @param string $scenario Name of the scenario used for error logging.
	 * @return bool Whether or not the creations were successful.
	 */
	private function setupTestData( $scenario )
	{
		$retVal = true;

		// Create the Publish Form Template
		$this->templateInTrashCan = false;
		$stepInfo = 'Create the Publish Form Template.';
		$this->template = $this->mcpUtils->createPublishFormTemplateObject( $stepInfo );
		if( is_null( $this->template ) ) {
			$this->setResult( 'ERROR',  $scenario.': Could not create the Publish Form Template object.' );
			$retVal = false;
		}
		
		// Create the Dossier.
		$this->dossierInTrashCan = false;
		$stepInfo = $scenario.': Create the Dossier object.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo );
		if( is_null( $this->dossier ) ) {
			$this->setResult( 'ERROR', $scenario.': Could not create the Dossier object.' );
			$retVal = false;
		}

		// Create the Publish Form.
		$this->formInTrashCan = false;
		if( $this->template && $this->dossier ) {
			$stepInfo = 'Create the Publish Form object.';
			$this->form = $this->mcpUtils->createPublishFormObject( 
										$this->template, $this->dossier, $stepInfo );
			if( is_null( $this->form ) ) {
				$this->setResult( 'ERROR', $scenario.': Could not create Publish Form object.' );
				$retVal = false;
			}
		}

		return $retVal;
	}
	
	/**
	 * Remove the objects that were created through function {@link: setupTestData()}.
	 *
	 * @param string $scenario Name of the scenario used for error logging.
	 */
	private function tearDownTestData( $scenario )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';

		// Permanently delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$area = $this->dossierInTrashCan ? 'Trash' : 'Workflow';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array($area) ) ) {
				$this->setResult( 'ERROR', $scenario.': Could not tear down the Dossier object: '.$errorReport );
			}
			$this->dossier = null;

			if( $this->form ) {
				$formId = $this->form->MetaData->BasicMetaData->ID;
				// When deleting a Dossier, Form will be cascade deleted, therefore check if the Form still exists.
				$formExists = BizObject::objectExists( $formId, 'Workflow' );
				if( !$formExists ) { // Don't exists in the Workflow, double check in Trash.
					$formExists = BizObject::objectExists( $formId, 'Trash' );
				}
				if( !$formExists ) { // Don't exists in both area, so safe to set it to Null.
					$this->form = null;
				}
			}
		}
		$this->dossierInTrashCan = false;

		// Permanently delete the Publish Form.
		if( $this->form ) {
			$id = $this->form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$area = $this->formInTrashCan ? 'Trash' : 'Workflow';
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array($area) ) ) {
				$this->setResult( 'ERROR', $scenario.': Could not tear down the Publish Form object: '.$errorReport );
			}
			$this->form = null;
		}
		$this->formInTrashCan = false;

		// Permanently delete the Publish Form Template.
		if( $this->template ) {
			$id = $this->template->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$area = $this->templateInTrashCan ? 'Trash' : 'Workflow';
			$stepInfo = 'Tear down the Publish Form Template object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array($area) ) ) {
				$this->setResult( 'ERROR', $scenario.': Could not tear down the Publish Form Template object: '.$errorReport );
			}
			$this->template = null;
		}
		$this->templateInTrashCan = false;
	}

	/**
	 * Updates the Target for the Contained relation of the Publish Form in the Dossier to 
	 * simulate that it is Published.
	 *
	 * By setting the PublishedDate we can fool the system into thinking that the Publish Form 
	 * was Published when trying to delete the Publish Form.
	 *
	 * @param integer $publishFormId The Publish Form Object ID.
	 * @param bool $setPublished Whether or not to set the Publish Form as Published in the Target. Default true.
	 * @return bool
	 */
	private function setPublishFormPublicationStatus( $publishFormId, $setPublished=true )
	{
		require_once BASEDIR.'/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR.'/server/bizclasses/BizRelation.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';

		$updated = null;
		$relations = BizRelation::getObjectRelations( $publishFormId, true, true, null);

		if( $relations ) foreach( $relations as $relation ) {
			$parentInfo = $relation->ParentInfo; // ObjectInfo
			$childInfo = $relation->ChildInfo; // ObjectInfo

			if( $parentInfo ) {
				if( $childInfo && $childInfo->ID == $publishFormId ) {
					// Only update the Contained Relation.
					if( $childInfo->Type == 'PublishForm' && $relation->Type == 'Contained' ) {
						// Check the Target(s).
						$relationId = BizRelation::getObjectRelationId($parentInfo->ID, $childInfo->ID, $relation->Type);

						if ($relationId) {
							$publishedDate = ($setPublished) ? date('Y-m-dTH:i:s') : '';
							$whereParams = array('objectrelationid' => array($relationId));
							$updated = DBTarget::update($whereParams, array('publisheddate' => $publishedDate) );
						}
					}
				}
			}
		}
		return !is_null($updated);
	}

	/**
	 * Checks if a Relation exists between a Form and a placed object for a specified 
	 * type of Relation. Queries the DB to check if a Relation of a specific type exists.
	 *
	 * @param integer $formId The ID of the Form object to check for.
	 * @param integer $placementObjectId The ID of the placed object for which to test the relation.
	 * @param string $type The Type of relation to check for.
	 * @return bool Whether or not a relation of $type exists.
	 */
	private function doesRelationExist( $formId, $placementObjectId, $type )
	{
		require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
		$relation = DBObjectRelation::getObjectRelationId( $formId, $placementObjectId, $type );
		return (!is_null($relation));
	}

	/**
	 * Tests if a placement exists between a Form and another Object. Checks if a placement 
	 * of $type exists between a Form and an Object.
	 *
	 * @param integer $formId The parent object to test against.
	 * @param integer $placementObjectId The child object to test against.
	 * @param string $type The Type of placement to test for.
	 * @return bool Whether or not a Placement exists.
	 */
	private function doesPlacementExist( $formId, $placementObjectId, $type )
	{
		require_once BASEDIR . '/server/dbclasses/DBPlacements.class.php';
		$placements = DBPlacements::getPlacements( $formId, $placementObjectId, $type);
		return (is_array($placements) && count($placements) > 0);
	}

	/**
	 * Tests if the object specified by the $objectId exists in the specified area(s).
	 *
	 * @param integer $objectId The Object ID to check for.
	 * @param array $areas The areas to check in.
	 * @return bool Whether or not an object exists in the specified areas.
	 * @throws BizException
	 */
	private function doesObjectExist( $objectId, $areas )
	{
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		$user = $this->vars['BuildTest_MultiChannelPublishing']['testOptions']['User'];
		try{
			$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO' ) );
			$object = BizObject::getObject( $objectId, $user, false, 'none', array(), null, true, $areas );
		} catch (BizException $e) {
			if ($e->getErrorCode() == 'S1029') { // "Record not found"
				return false;
			} else {
				throw $e; // Error out. In this case something else went wrong and we want to know what it is.
			}
		}
		return (!is_null($object));
	}

	/**
	 * Remove a Target (channel) of a Dossier.
	 *
	 * @param array $targets The list of targets to be removed.
	 * @return bool Whether or not removing the Target was successful.
	 */
	private function removeTargets( $targets )
	{
		$id = $this->dossier->MetaData->BasicMetaData->ID;
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectTargetsService.class.php';
		$request = new WflDeleteObjectTargetsRequest();
		$request->Ticket  = $this->ticket;
		$request->IDs     = array( $id );
		$request->Targets = $targets;
		$stepInfo = 'Removing a Target from the Dossier.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		return !is_null( $response ) ? true : false;
	}

	/**
	 * Creates Targets (Channel) of a Dossier.
	 *
	 * @param array $targets List of Targets of a Dossier to be created.
	 * @return bool Whether or not the Targets creation was successful.
	 */
	private function addTarget( $targets )
	{
		$id = $this->dossier->MetaData->BasicMetaData->ID;
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';
		$request = new WflCreateObjectTargetsRequest();
		$request->Ticket               = $this->ticket;
		$request->IDs                  = array( $id );
		$request->Targets              = $targets;
		$stepInfo = 'Adds Targets for Dossier.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		return !is_null( $response ) ? true : false;
	}

	/**
	 * Creates a new Issue and compose a new Target based on this newly created Issue.
	 *
	 * @return Target
	 */
	private function getNewTarget()
	{
		$publication = $this->vars['BuildTest_MultiChannelPublishing']['publication'];
		$pubChannelId = $this->dossier->Targets[0]->PubChannel->Id;
		$pubChannelName = $this->dossier->Targets[0]->PubChannel->Name;
		$newIssue = new AdmIssue();
		$newIssue->Name = 'FormInTrashCan_TestCase_Issue';
		$stepInfo = 'Creating a new Issue under pub channel '. $pubChannelName;
		$newIssues = $this->mcpUtils->createIssues( $stepInfo, $publication->Id, $pubChannelId, array( $newIssue ) );

		$dossierTarget = new Target();
		$dossierTarget->PubChannel = new PubChannel( $pubChannelId, $pubChannelName );
		$dossierTarget->Issue = new Issue( $newIssues->Issues[0]->Id, $newIssues->Issues[0]->Name );

		return $dossierTarget;
	}

	/**
	 * Do a GetObject to retrieve the latest state of an object.
	 *
	 * @param int $objId The Object id of the object to be retrieved.
	 * @param string $scenario
	 * @return Object|null The object retrieved when successful; null otherwise.
	 */
	private function getLatestObject( $objId, $scenario )
	{
		$object = null;
		try{
			$user = $this->vars['BuildTest_MultiChannelPublishing']['testOptions']['User'];
			$object = BizObject::getObject( $objId, $user, false, 'none', array( 'Relations', 'Targets'),
											null, true, array( 'Workflow' ) );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $scenario . ':Failed getting object with object id: ' . $objId . ' ' .
							$e->getMessage() . ' ' . $e->getDetail() );
		}
		return $object;
	}

}