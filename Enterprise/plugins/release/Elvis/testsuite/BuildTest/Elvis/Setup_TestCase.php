<?php
/**
 * Elvis TestCase class that belongs to the BuildTest TestSuite of wwtest.
 *
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Elvis_Setup_TestCase extends TestCase
{
	/** @var string|null */
	private $ticket;

	public function getDisplayName() { return 'Setup test data'; }
	public function getTestGoals()   { return 'Checks if the basic environment can be setup properly.'; }
	public function getTestMethods() { return
		'Perform multiple services to setup the test environment.
		 <ol>
		 	<li>Logon user configured at TESTSUITE option in configserver.php.(LogOn)</li>
		 	<li>Retrieve all the necessary settings and set in the session variables.</li>
		 </ol> '; }
	public function getPrio()        { return 1; }

	final public function runTest()
	{
		// Test if Elvis is compatible with Enterprise.
		require_once __DIR__.'/../../../config.php';
		$this->testElvisServerConnection();

		// Check if the ELVIS_SUPER_USER option is configured and whether this user exists in Elvis.
		$this->checkIfFallbackUserIsEnabledInElvis();

		// Check if the test user exists in Elvis.
		$suiteOpts = unserialize( TESTSUITE );
		$this->assertTrue( isset($suiteOpts['ElvisUser']) && $suiteOpts['ElvisUser'] );
		$this->assertTrue( isset( $suiteOpts['ElvisPassword'] ) );
		$this->checkIfTestUserIsEnabledInElvis( $suiteOpts['ElvisUser'] );

		// Check if the TESTSUITE['User'] option is the same as ELVIS_SUPER_USER, as required by this test script.
		$this->assertEquals( ELVIS_SUPER_USER, $suiteOpts['User'],
			'Make sure the TESTSUITE["User"] option matches the ELVIS_SUPER_USER option.');

		// LogOn test user through workflow interface
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		$utils->initTest( 'JSON' );
		$utils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->ClientAppName = 'WW_TestSuite_BuildTest_Elvis';
				$req->ClientAppVersion = '1.0.0 build 0';
				$req->RequestInfo = array( 'Publications', 'ServerInfo' );
			}
		);
		$response = $utils->wflLogOn( $this );

		// Save the retrieved ticket and brand info into session data.
		// This data is picked up by successor sibling TestCase modules (within the parental TestSuite).
		if( !is_null( $response ) ) {
			$this->ticket = $response->Ticket;

			$vars = array();
			$vars['BuildTest_Elvis']['ticket'] = $response->Ticket;
			$this->setSessionVariables( $vars );
		}
	}

	/**
	 * Check if Elvis server is running and has minimum required version.
	 */
	private function testElvisServerConnection()
	{
		$client = new Elvis_BizClasses_Client( null );
		$info = $client->getElvisServerInfo();
		$this->assertEquals( 'running', $info->state );
		$this->assertVersionGreaterThanOrEqual( ELVIS_MINVERSION, $info->version );
		$this->assertTrue( $info->available );
		$this->assertEquals( 'Elvis', $info->server );
	}

	/**
	 * Check if the configured ELVIS_SUPER_USER is enabled in Elvis.
	 */
	private function checkIfFallbackUserIsEnabledInElvis()
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		require_once __DIR__.'/../../../config.php'; // ELVIS_SUPER_USER

		$service = new ElvisContentSourceService();
		$userDetails = $service->getUserDetails( ELVIS_SUPER_USER );
		$this->assertTrue( $userDetails->enabled );
	}

	/**
	 * Check if the given Elvis test is enabled in Elvis.
	 *
	 * @param string $username
	 */
	private function checkIfTestUserIsEnabledInElvis( $username )
	{
		require_once __DIR__.'/../../../logic/ElvisContentSourceService.php';
		require_once __DIR__.'/../../../config.php'; // ELVIS_SUPER_USER

		$service = new ElvisContentSourceService();
		$userDetails = $service->getUserDetails( $username );
		$this->assertTrue( $userDetails->enabled );
	}
}