<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_RemoveChannel_TestCase extends TestCase
{
	// Session related stuff.
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils
	
	// Objects to work on:
	private $template = null;
	private $form = null;
	private $dossier = null;

	public function getDisplayName()
	{
		return 'Remove Channel - Drupal Channel';
	}

	public function getTestGoals()
	{
		return 'Checks if a PublishForm is deleted when the Channel of a Dossieris removed.';
	}

	public function getTestMethods()
	{
		return
			'Test with objects assigned to a web channel: <ul>'.
				'<li>01 Create a PublishFormTemplate, Dossier and, PublishForm object (CreateObjects).</li>'.
				'<li>02 Checks removing the Target(s) of a Dossier (DeleteObjectTargets).</li>'.
				'<li>03 Checks if the Dossier has empty Targets and Relations (DeleteObjectTargets).</li>'.
				'<li>04 Checks if the PublishForm that was contained in the Dossier is moved to the trash can (GetObjects).</li>'.
			'</ul>';
	}

	public function getPrio()
	{
		return 110;
	}

	/**
	 * Runs the TestCases for this TestSuite.
	 *
	 * @return void
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
		$this->ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		
		do {
			// Create objects for testing.
			if( !$this->setupTestData() ) {
				break;
			}
			if( !$this->validatePublishFormChannel() ) {
				break;
			}
			if( !$this->removeTarget() ) {
				break;
			}
		} while( false );
		
		// Remove the test objects.
		$this->tearDownTestData();
	}

	/**
	 * Prepare the test structure by creating objects for the tests.
	 *
	 * @return bool Whether or not the setup was successful.
	 */
	private function setupTestData()
	{
		$retVal = true;
		
		// Compose postfix for object name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		// Create the Publish Form Template.
		$stepInfo = 'Create the Publish Form Template.';
		$this->template = $this->mcpUtils->createPublishFormTemplateObject( $stepInfo );
		if( is_null($this->template) ) {
			$this->setResult( 'ERROR',  'Could not create the Publish Form Template.');
			$retVal = false;
		}

		// Create the Dossier.
		$stepInfo = 'Create the Dossier object for Web.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo, 'DossierForWeb '.$postfix, 'web' );
		if( is_null($this->dossier) ) {
			$this->setResult( 'ERROR',  'Could not create the Dossier.' );
			$retVal = false;
		}

		// Create the Publish Form.
		if( $this->dossier ) {
			$stepInfo = 'Create the Publish Form object and assign it to the Dossier.';
			$this->form = $this->mcpUtils->createPublishFormObject( $this->template, $this->dossier, $stepInfo );
			if( is_null($this->form) ) {
				$this->setResult( 'ERROR',  'Could not create the Publish Form object.' );
				$retVal = false;
			}
		}

		return $retVal;
	}

	/**
	 * Tear down the test environment setup in {@link: setupTestData()}.
	 *
	 * @return bool Whether or not the teardown was successful.
	 */
	private function tearDownTestData()
	{
		$retVal = true;
		
		// Permanently delete the Publish Form.
		if( $this->form ) {
			$id = $this->form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Publish Form object.';

			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			// If the BuildTest was not successful, chances are the PublishForm is still in the Workflow area.
			if( DBObject::objectExists( $id, 'Workflow' ) ) {
				if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array('Workflow') ) ) {
					$this->setResult( 'ERROR', 'Could not tear down Publish Form object from the Workflow area: '
						.$errorReport );
					$retVal = false;
				}
			} else { // If the Object does not exist in the Workflow area, permanently remove it.
				if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport, true, array('Trash') ) ) {
					$this->setResult( 'ERROR', 'Could not tear down Publish Form object from the TrashCan: '.$errorReport );
					$retVal = false;
				}
			}
			$this->form = null;
		}
		
		// Permanently delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down the Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->dossier = null;
		}
		
		// Permanently delete the Publish Form Template.
		if( $this->template ) {
			$id = $this->template->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the PublishFormTemplate object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down the PublishFormTemplate object: '.$errorReport );
				$retVal = false;
			}
			$this->template = null;
		}
		return $retVal;
	}

	/**
	 * Validate the Form Relational target.
	 *
	 * Checks for the Form 'InstanceOf' relation, whether it has relational targets
	 * and if the relational targets is the same as Dossier's targets.
	 */
	private function validatePublishFormChannel()
	{
		$validateResult = true;
		if( $this->form->Relations ) foreach( $this->form->Relations as $formRelation ) {
			if( $formRelation->Type == 'InstanceOf' ) {
				if( is_null( $formRelation->Targets ) || !$formRelation->Targets ) {
					$validateResult = false;
					break;
				}
				require_once BASEDIR.'/server/utils/PhpCompare.class.php';
				$phpCompare = new WW_Utils_PhpCompare();
				$phpCompare->initCompare( array( '[0]->PublishedDate' => true )); // Properties that will not be compared
				$dossierTargets = $this->dossier->Targets;
				$formRelTargets = $formRelation->Targets;
				if( !$phpCompare->compareTwoArrays( $dossierTargets, $formRelTargets )) {
					$this->setResult( 'ERROR', 'The PublishForm\'s Relational Targets is not the same as the Dossier Targets. ' .
						print_r( $phpCompare->getErrors(),1) );
					$validateResult = false;
				}
				break; // Only interested to find out on the InstanceOf relation, quit here when found.
			}
		}
		return $validateResult;
	}

	/**
	 * Remove a Target (channel) of a Dossier.
	 *
	 * @return bool Whether or not removing the Target was successful.
	 */
	private function removeTarget()
	{
		$removeSuccessful = false;
		$id = $this->dossier->MetaData->BasicMetaData->ID;
		$targets = $this->dossier->Targets;
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectTargetsService.class.php';
		$request = new WflDeleteObjectTargetsRequest();
		$request->Ticket  = $this->ticket;
		$request->IDs     = array( $id );
		$request->Targets = $targets;
		$stepInfo = 'Removing a Target from the Dossier.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( !is_null( $response )) {
			$removeSuccessful = $this->validateDossier();
		}

		return $removeSuccessful;
	}

	/**
	 * Validate a Dossier's properties and its content.
	 *
	 * When the Dossier's Target(Channel) is removed, the PublishForm (which belongs to the channel)
	 * should no longer be contained in the Dossier (empty Relations), and also should be moved
	 * to the TrashCan.
	 * Thus, the following is checked:
	 * - A Dossier's Relations and Targets should be empty.
	 * - The PublishForm is in the TrashCan.
	 *
	 * @return bool Whether or not the validation was successful.
	 */
	private function validateDossier()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$result = true;

		// Validate the Targets and Relations.
		$id = $this->dossier->MetaData->BasicMetaData->ID;
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $id );
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'Relations', 'Targets' );
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		$stepInfo = 'Retrieving a Dossier for validation.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( is_null($response )) {
			$result = false;
		} else {
			$dossier = $response->Objects[0];
			if( $dossier->Relations || $dossier->Targets ) {
				$result = false;
				$this->setResult( 'ERROR', 'Expected empty Relations and empty Targets for the Dossier.' .
					'A channel (Target) is removed from the Dossier, therefore the PublishForm should be deleted'.
					'as well.');
			}
		}

		// Validates that the PublishForm (which was in the Dossier) is removed when the Target of a Dossier is removed.
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$id = $this->form->MetaData->BasicMetaData->ID;
		try {
			$user = $this->vars['BuildTest_MultiChannelPublishing']['testOptions']['User'];
			// Retrieve the PublishForm from the TrashCan.
			$deletedObj = BizObject::getObject( $id, $user, false, 'none', null, null, false, array('Trash') );
			if( $deletedObj->MetaData->BasicMetaData->ID != $id ) {
				$result = false;
				$this->setResult( 'ERROR', 'The PublishForm is expected to be in the TrashCan, but it was not found, ' .
				'which is not expected. The channel the PublishForm belongs to has been removed from the ' .
				'Dossier, therefore the PublishForm should have been moved to the TrashCan as well.');
			}
		} catch( BizException $e ) {
			$result = false;
			$this->setResult( 'ERROR', 'The existence of the PublishForm could not be verified due to the following error: '
				. $e->getMessage() . $e->getDetail() );
		}
		return $result;
	}
}