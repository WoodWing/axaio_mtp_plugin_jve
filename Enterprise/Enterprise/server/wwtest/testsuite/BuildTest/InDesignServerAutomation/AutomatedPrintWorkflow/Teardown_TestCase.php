<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Teardown_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Utils $localUtils */
	private $localUtils = null;

	/** @var string $ticket */
	private $ticket = null;

	/** @var Publication $publication */
	private $publication = null;
	
	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannel = null;

	/** @var AdmIssue $issueObj */
	private $issue = null;

	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries logoff the user from Enterprise. '; }
	public function getTestMethods() { return 'Calls the LogOff workflow service at application server. '; }
	public function getPrio()        { return 500; }

	final public function runTest()
	{
		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->globalUtils = new WW_Utils_TestSuite();

		require_once BASEDIR. '/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();

		$this->ticket      = @$vars['BuildTest_AutomatedPrintWorkflow']['ticket'];
		$this->publication = @$vars['BuildTest_AutomatedPrintWorkflow']['brand'];
		$this->pubChannel  = @$vars['BuildTest_AutomatedPrintWorkflow']['pubChannel'];
		$this->issue       = @$vars['BuildTest_AutomatedPrintWorkflow']['issue'];
		$editions          = @$vars['BuildTest_AutomatedPrintWorkflow']['editions'];

		// Delete Editions that were created for this build test.
		if( $this->publication ) {
			if( isset($editions[0]) ) {
				$this->tearDownEditions( $this->publication->Id, $editions[0]->Id );
			}
			if( isset($editions[1]) ) {
				$this->tearDownEditions( $this->publication->Id, $editions[1]->Id );
			}
		}

		// Tear down the PubChannel and Issue that was created by the Setup test case.
		$this->tearDownPubChannelAndIssue();

		// Deactivate the IdsAutomation plugin when we did activate before.
		$activatedIdsAutomationPlugin = @$vars['BuildTest_AutomatedPrintWorkflow']['activatedIdsAutomationPlugin'];
		if( $activatedIdsAutomationPlugin ) {
			$this->globalUtils->deactivatePluginByName( $this, 'IdsAutomation' );
		}
		
		// Deactivate the IdsAutomation plugin when we did activate before.
		$activatedAutomatedPrintWorkflowPlugin = @$vars['BuildTest_AutomatedPrintWorkflow']['activatedAutomatedPrintWorkflowPlugin'];
		if( $activatedAutomatedPrintWorkflowPlugin ) {
			$this->globalUtils->deactivatePluginByName( $this, 'AutomatedPrintWorkflow' );
		}
		
		// Log off
		$this->globalUtils->wflLogOff( $this, $this->ticket );
	}

	/**
	 * Delete editions through the DeleteEditions admin web service.
	 *
	 * @param integer $publicationId
	 * @param integer $editionId Id of edition to be deleted
	 */
	public function tearDownEditions( $publicationId, $editionId )
	{
		$stepInfo = 'Deleting Edition for Automated Print Workflow';
		
		require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';
		$request = new AdmDeleteEditionsRequest();
		$request->Ticket         = $this->ticket;
		$request->PublicationId  = $publicationId;
		$request->EditionIds     = array( $editionId );
		$this->globalUtils->callService( $this, $request, $stepInfo );
	}


	/**
	 * Removes the PubChannel and Issue created at
	 * {@link: WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Setup_TestCase::setupPubChannelAndIssue()}.
	 */
	private function tearDownPubChannelAndIssue()
	{
		if( !$this->publication ) {
			return;
		}
		// Delete the Issue.
		if( $this->issue ) {
			$this->globalUtils->removeIssue( $this, $this->ticket,
				$this->publication->Id, $this->issue->Id );
		}

		// Delete the Publication Channel.
		if( $this->pubChannel ) {
			$this->globalUtils->removePubChannel( $this, $this->ticket,
				$this->publication->Id, $this->pubChannel->Id );
		}
	}
}