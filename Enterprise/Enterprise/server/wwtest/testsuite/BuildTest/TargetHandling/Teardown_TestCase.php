<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_TargetHandling_Teardown_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $utils */
	private $globalUtils = null;

	/** @var string $ticket */
	private $ticket = null;

	/** @var Publication $publication */
	private $publication = null;
	
	/** @var AdmPubChannel $pubChannelObj */
	private $pubChannel = null;

	/** @var AdmIssue $issueObj */
	private $issues = null;

	/** @var AdmEdition $editions */
	private $editions = null;

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

		$this->ticket      = @$vars['TargetHandling']['ticket'];
		$this->publication = @$vars['TargetHandling']['brand'];
		$this->pubChannel  = @$vars['TargetHandling']['pubChannel'];
		$this->issues      = @$vars['TargetHandling']['issue'];
		$this->editions    = @$vars['TargetHandling']['editions'];

		// Tear down the Editions that where created by the Setup test case.
		$this->tearDownEditions();
		// Tear down the PubChannel and Issue that where created by the Setup test case.
		$this->tearDownPubChannelAndIssue();
		// Log off
		$this->globalUtils->wflLogOff( $this, $this->ticket );
	}

	private function tearDownEditions()
	{
		if( $this->publication ) {
			if( isset( $this->editions[0] ) ) {
				$this->tearDownEdition( $this->publication->Id, $this->editions[0]->Id );
			}
			if( isset($this->editions[1]) ) {
				$this->tearDownEdition( $this->publication->Id, $this->editions[1]->Id );
			}
		}
	}
	/**
	 * Delete editions through the DeleteEditions admin web service.
	 *
	 * @param integer $publicationId
	 * @param integer $editionId Id of edition to be deleted
	 */
	private function tearDownEdition( $publicationId, $editionId )
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
		if( $this->issues ) {
			foreach ( $this->issues as $issueObj ) {
				$this->globalUtils->removeIssue( $this, $this->ticket, $this->publication->Id, $issueObj->Id );
			}
		}

		// Delete the Publication Channel.
		if( $this->pubChannel ) {
			$this->globalUtils->removePubChannel( $this, $this->ticket, $this->publication->Id, $this->pubChannel->Id );
		}
	}
}