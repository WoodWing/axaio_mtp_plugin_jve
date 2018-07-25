<?php
/**
 * @since v9.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Does tear down some basic environment after testing the DPS Next integration.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeDps2_Teardown_TestCase extends TestCase
{
	private $utils = null;
	private $ticket = null;
	private $publicationId = null;
	private $issue = null;
	private $pubChannel = null;
	private $editions = null;

	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Tries tearing down /clear up the testing environment. '; }
	public function getTestMethods() { return
		'Does tear down the environment as follows:
		<ol>
		 	<li>Delete the Publication Channel with its Issue and iPad/iPhone editions.</li>
		 	<li>Deactivate the AdobeDps2 plugin.</li>
		 	<li>LogOff the test user.</li>
		 </ol>'; 
	}
    public function getPrio()        { return 1000; }
	
	final public function runTest()
	{
		// Initialize.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Read session data.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();
		$this->ticket        = @$vars['BuildTest_AdobeDps2']['ticket'];
		$activatedPlugin     = @$vars['BuildTest_AdobeDps2']['activatedPlugin'];
		$this->publicationId = @$vars['BuildTest_AdobeDps2']['Brand']->Id;
		$this->editions      = @$vars['BuildTest_AdobeDps2']['editions'];
		$this->issue         = @$vars['BuildTest_AdobeDps2']['apIssue'];
		$this->pubChannel    = @$vars['BuildTest_AdobeDps2']['apChannel'];
		$layoutStatus        = @$vars['BuildTest_AdobeDps2']['layoutStatus'];
		$readyToPublishLayoutStatus  = @$vars['BuildTest_AdobeDps2']['readyToPublishLayoutStatus'];
		
		if( $this->ticket ) {
		
			// Delete Editions that were created for this build test.
			$this->tearDownEditions( 'Deleting Edition for Adobe DPS', $this->publicationId, $this->editions[0]->Id );
			$this->tearDownEditions( 'Deleting Edition for Adobe DPS', $this->publicationId, $this->editions[1]->Id );

			// Delete PubChannel and Issue that were created for this build test.
			$this->tearDownPubChannelAndIssue();

			$this->tearDownStatus( $layoutStatus->Id );
			$this->tearDownStatus( $readyToPublishLayoutStatus->Id );
		}
		
		// Deactivate the AdobeDps2 plugin (only when we did activate before).
		if( $activatedPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'AdobeDps2' );
		}
		
		// LogOff (only when we did LogOn before).
		if( $this->ticket ) {
			$this->utils->wflLogOff( $this, $this->ticket );
		}
	}

	/**
	 * Delete editions through the DeleteEditions admin web service.
	 *
	 * @param string $stepInfo Extra logging info.
	 * @param integer $publicationId
	 * @param integer $editionId Id of edition to be deleted
	 */
	public function tearDownEditions( $stepInfo, $publicationId, $editionId )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';
		$request = new AdmDeleteEditionsRequest();
		$request->Ticket         = $this->ticket;
		$request->PublicationId  = $publicationId;
		$request->EditionIds     = array( $editionId );
		$this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * Removes the PubChannel and Issue created at {@link: WW_TestSuite_BuildTest_AdobeDps2_Setup_TestCase::setupPubChannelAndIssue()}.
	 */
	private function tearDownPubChannelAndIssue()
	{
		// Delete the Issue.
		if( $this->issue ) {
			$this->utils->removeIssue( $this, $this->ticket, $this->publicationId, $this->issue->Id );
		}

		// Delete the Publication Channel.
		if( $this->pubChannel ) {
			$this->utils->removePubChannel( $this, $this->ticket, $this->publicationId, $this->pubChannel->Id );
		}
	}

	/**
	 * Removes the workflow status created at {@link: WW_TestSuite_BuildTest_AdobeDps2_Setup_TestCase::setupLayoutStatus()}.
	 * @param int $statusId
	 */
	private function tearDownStatus( $statusId )
	{
		try{
			if( $statusId ) {
				require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
				BizCascadePub::deleteStatus( $statusId );
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Failed deleting \'Ready to be published\' Layout status.' . $e->getMessage() );
		}
	}
}