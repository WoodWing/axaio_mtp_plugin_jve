<?php
/**
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_SysServices_SubApplications_TestCase extends TestCase
{
	public function getDisplayName() { return 'Sub Applications'; }
	public function getTestGoals()   { return 'Checks if registered sub applications can be retrieved from server. '; }
	public function getTestMethods() { return 'Calls system admin services in all various ways to hit all business logics.'; }
    public function getPrio()        { return 100; }

	private $utils = null; // WW_Utils_TestSuite
	private $ticket = null; // session ticket taken from SysInitData TestCase
	
	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by AdmInitData TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_SysServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the SysInitData test.' );
			return;
		}
		
		// Perform the test with sub apps plug-in activated.
		$didActivate = $this->utils->activatePluginByName( $this, 'AddSubApplication' );
		$this->testSubApplications( true );

		// Perform the test with sub apps plug-in deactivated.
		$this->utils->deactivatePluginByName( $this, 'AddSubApplication' );
		$this->testSubApplications( false );
		
		// Restore plugin activation.
		if( !$didActivate ) { // if we did not activate, it was activated before, so we restore by activation.
			$this->utils->activatePluginByName( $this, 'AddSubApplication' );
		}
	}

	// - - - - - - - - - - - - - - - - - - - - - USERS - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Call the SysGetSubApplications service in various ways.
	 *
	 * @param bool $pluginEnabled Whether or not the AddSubApplication is activated.
	 */
	private function testSubApplications( $pluginEnabled )
	{
		require_once BASEDIR.'/server/services/sys/SysGetSubApplicationsService.class.php';
		
		// Get all sub apps that are registered for all clients.
		$request = new SysGetSubApplicationsRequest();
		$request->Ticket = $this->ticket;
		$request->ClientAppName = null; // all clients
		$stepInfo = 'Retrieving all registered sub applications from Enterprise Server.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$this->checkSubAppListed( $response, $pluginEnabled );

		// Get all sub apps that are registered for the FooClient client.
		$request = new SysGetSubApplicationsRequest();
		$request->Ticket = $this->ticket;
		$request->ClientAppName = 'FooClient'; // one specific client
		$stepInfo = 'Retrieving sub applications from Enterprise Server that are registered for FooClient.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$this->checkSubAppListed( $response, $pluginEnabled );

		// Get all sub apps that are registered for a non-existing client.
		$request = new SysGetSubApplicationsRequest();
		$request->Ticket = $this->ticket;
		$request->ClientAppName = 'NonExistingClient'; // one specific client that does not exist
		$stepInfo = 'Retrieving sub applications from Enterprise Server that are registered for FooClient.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$this->checkSubAppListed( $response, false );
	}

	/**
	 * Validates if the 'FooSubApp' is listed in the response as expected.
	 *
	 * @param SysGetSubApplicationsResponse $response Service response to search through.
	 * @param bool $fooAppShouldBeListed Whether or not the 'FooSubApp' should be listed.
	 */
	private function checkSubAppListed( $response, $fooAppShouldBeListed )
	{
		// Search for the FooClient_FooSubApp in the response.
		$found = false;
		if( $response->SubApplications ) foreach( $response->SubApplications as $subApp ) {
			if( $subApp->ID == 'FooClient_FooSubApp' ) {
				if( $found ) {
					$this->setResult( 'ERROR', 'The sub application "FooClient_FooSubApp" '.
						'is found more than once.' );
				}
				$found = true;
			}
		}
		
		// Error when the sub app was found (or not found) unexpectedly in the response.
		$info = 'Tested with the AddSubApplication server plug-in '.
			($fooAppShouldBeListed ? 'activated' : 'deactivated').'.';
			
		if( $found && !$fooAppShouldBeListed ) {
			$this->setResult( 'ERROR', 'The sub application "FooClient_FooSubApp" is found '.
				'but should not be listed in the response. '.$info );
		}
		if( !$found && $fooAppShouldBeListed ) {
			$this->setResult( 'ERROR', 'The sub application "FooClient_FooSubApp" is not found '.
				'but should be listed in the response. '.$info );
		}
	}
}
