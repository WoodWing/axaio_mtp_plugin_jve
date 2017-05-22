<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_Basics_TestCase extends TestCase
{
	public function getDisplayName() { return 'Basic Operations'; }
	public function getTestGoals()   { return 'Checks if a PublishForm can be created in database and retrieved again.'; }
	public function getTestMethods() { return 'Scenario\'s:<ol>
		<li>00: Performs create, get and delete operations on PublishForm objects.</li>
		<li>01: Tests that upon changing the containing Dossier name that the Publish Form name is changed as well.</li>
		</ol>'; }
	public function getPrio()        { return 1; }
	
	// Session data:
	/** @var string $ticket */
	private $ticket = null;
	/** @var array $vars */
	private $vars = null;

	// Objects used for testing:
	/** @var Object $template */
	private $template = null;
	/** @var Object $form */
	private $form = null;
	/** @var Object $dossier */
	private $dossier = null;

	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	/** @var MultiChannelPublishingUtils $mcpUtils  */
	private $mcpUtils = null;

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
		$this->ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];

		do {
			// Create a Publish Form Template and a Dossier.
			if( !$this->setupTestData() ) {
				break;
			}

			// Scenario 00: Test the basics
			if( !$this->testScenario00() ) {
				break;
			}

			// Scenario 00: Test PublishForm inheritance on changing the Dossier name.
			if( !$this->testScenario01() ) {
				break;
			}
		} while ( false );

		
		// Teardown the test set.
		$this->tearDownTestData();
	}

	/**
	 * Performs create, get and delete operations on PublishForm objects.
	 *
	 * @return bool
	 */
	private function testScenario00()
	{
		$retVal = true;
		do {
			// Test creation of a faulty PublishForm, lacking a relation, which should return an null Object.
			$stepInfo = 'Try to create a faulty Publish Form that has no object relation.';
			$this->mcpUtils->setExpectedError( '(S1012)' );
			$this->form = $this->mcpUtils->createPublishFormObject( $this->template,
				$this->dossier, $stepInfo, MultiChannelPublishingUtils::RELATION_MISSING_ERROR );
			if( !is_null($this->form) ) {
				$this->setResult( 'ERROR', 'Succeeded in creating a PublishForm with a missing '.
					'relation which should not be possible.' );
				$retVal = false;
			}

			// Test creation of a faulty PublishForm, lacking a relational target, which should return an null Object.
			$stepInfo = 'Try to create a faulty Publish Form that has no object relation.';
			$this->mcpUtils->setExpectedError( '(S1012)' );
			$this->form = $this->mcpUtils->createPublishFormObject( $this->template,
				$this->dossier, $stepInfo, MultiChannelPublishingUtils::RELATION_TARGET_ERROR );
			if( !is_null($this->form) ) {
				$this->setResult( 'ERROR', 'Succeeded in creating a PublishForm with a missing '.
					'relational target which should not be possible.' );
				$retVal = false;
			}

			// Create a Publish Form (based on the template) and assign it to the dossier.
			$stepInfo = 'Create a Publish Form and assign it to the dossier.';
			$this->form = $this->mcpUtils->createPublishFormObject( $this->template, $this->dossier, $stepInfo );
			if( is_null($this->form) ) {
				$this->setResult( 'ERROR', 'Could not create Publish Form object.' );
				$retVal = false;
			}
		} while( false );
		return $retVal;
	}

	/**
	 * Tests that upon changing the containing Dossier name that the Publish Form name is changed as well.
	 *
	 * @return bool
	 */
	private function testScenario01()
	{
		$retVal = true;
		do {
			// Use the created Form, to see the updated result when we update the dossier.
			require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

			$result = false;
			$name = 'BasicsBuildTest';
			$scenarioString = 'Scenario 01: ';

			try {
				$this->dossier->MetaData->BasicMetaData->Name = $name;
				$request = new WflSetObjectPropertiesRequest();
				$request->ID = $this->dossier->MetaData->BasicMetaData->ID;
				$request->MetaData = $this->dossier->MetaData;
				$request->Targets = $this->dossier->Targets;

				$service = new WflSetObjectPropertiesService();
				$response = $service->execute( $request );

				if ( !is_null( $response ) ) {
					// Retrieve the PublishForm again from the database to see if the name is the same as the Dossier.
					$user = BizSession::getShortUserName();
					$object = BizObject::getObject($this->form->MetaData->BasicMetaData->ID, $user, false, 'none',
						array('Targets','MetaData', 'Relations'), null, false, array('Workflow'));
					if ( !is_null( $object ) ) {
						$result = ($object->Metadata->BasicMetaData->Name == $name);
					}
				}
			} catch ( BizException $e ) {
				$this->setResult( 'ERROR', $scenarioString . 'There was an error during the execution of the services ' .
					'while testing the changed PublishForm name.');
				$retVal = false;
				break;
			}

			if ( !$result ) {
				$this->setResult( 'ERROR', $scenarioString . 'The PublishForm name does not match the Dossier name.');
				$retVal = false;
			}
		} while( false );
		return $result;
	}

	/**
	 * Creates the objects to be used in this test: a Publish Form Template and a Dossier
	 *
	 * @return bool
	 */
	private function setupTestData()
	{
		$retVal = true;

		// Create a Publish Form Template.
		$stepInfo = 'Creating PublishForm Template object.';
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

		return $retVal;
	}

	/**
	 * Permanently deletes objects used in this test.
	 */
	private function tearDownTestData()
	{
		// Permanent delete the Publish Form.
		if( $this->form ) {
			$id = $this->form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Deleting PublishForm object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Publish Form object: '.$errorReport );
			}
			$this->form = null;
		}
		
		// Permanent delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Deleting Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
			}
			$this->dossier = null;
		}
		
		// Permanent delete the Publish Form Template.
		if( $this->template ) {
			$id = $this->template->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Deleting Template object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport );
			}
			$this->template = null;
		}
	}
}