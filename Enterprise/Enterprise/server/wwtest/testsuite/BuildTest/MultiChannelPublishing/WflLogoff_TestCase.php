<?php
/**
 * @since v8.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_WflLogoff_TestCase extends TestCase
{
	public function getDisplayName() { return 'Tear down test data'; }
	public function getTestGoals()   { return 'Make sure the user is LogOff after features testing.'; }
	public function getTestMethods() { return 'Calls the LogOff workflow service at application server. '; }
    public function getPrio()        { return 10000; }
    
    private $ticket = null;
    private $publication = null;
    private $webIssue = null;
    private $webPubChannel = null;
	
	final public function runTest()
	{
		// Initialize
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		
		// Read session data.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$vars = $this->getSessionVariables();
		$this->ticket        = @$vars['BuildTest_MultiChannelPublishing']['ticket'];
		$this->publication   = @$vars['BuildTest_MultiChannelPublishing']['publication'];
		$this->webIssue      = @$vars['BuildTest_MultiChannelPublishing']['webIssue'];
		$this->webPubChannel = @$vars['BuildTest_MultiChannelPublishing']['webPubChannel'];
		$activatedMcpPlugin  = @$vars['BuildTest_MultiChannelPublishing']['activatedMcpPlugin'];
		$activatedOpenCalaisPlugin  = @$vars['BuildTest_MultiChannelPublishing']['activatedOpenCalaisPlugin'];
		$activatedPreviewPlugin  = @$vars['BuildTest_MultiChannelPublishing']['activatedPreviewPlugin'];
		$activatedStandaloneAutocompletePlugin = @$vars['BuildTest_MultiChannelPublishing']['activatedStandaloneAutocompletePlugin'];

		// Tear down the PubChannel and Issue that was created by the WflLogon test case.
		$this->tearDownPubChannelAndIssue();
		
		// LogOff when we did LogOn before.
		if( $this->ticket ) {
			$this->utils->wflLogOff( $this, $this->ticket );
		}

		// Deactivate the MultiChannelPublishingSample plugin when we did activate before.
		if( $activatedMcpPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'MultiChannelPublishingSample' );
		}

		// Deactivate the OpenCalais plugin when we did activate before.
		if( $activatedOpenCalaisPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'OpenCalais' );
		}

		// Deactivate the Preview plugin when we did activate before.
		if( $activatedPreviewPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'PreviewMetaPHP' );
		}

		// Deactivate the Standalone Autocomplete plugin when we did activate it before.
		if( $activatedStandaloneAutocompletePlugin ) {
			$this->utils->deactivatePluginByName( $this, 'StandaloneAutocompleteSample' );
		}
	}

	/**
	 * Removes the PubChannel and Issue created at 
	 * {@link: WW_TestSuite_BuildTest_MultiChannelPublishing_WflLogon_TestCase::setupPubChannelAndIssue()}.
	 */
	private function tearDownPubChannelAndIssue()
	{
		if( !$this->publication ) {
			return;
		}
			
		// Delete the Issue.
		if( $this->webIssue ) {
			$this->utils->removeIssue( $this, $this->ticket, 
										$this->publication->Id, $this->webIssue->Id );
		}

		// Delete the Publication Channel.
		if( $this->webPubChannel ) {
			$this->utils->removePubChannel( $this, $this->ticket, 
										$this->publication->Id, $this->webPubChannel->Id );
		}
	}
}