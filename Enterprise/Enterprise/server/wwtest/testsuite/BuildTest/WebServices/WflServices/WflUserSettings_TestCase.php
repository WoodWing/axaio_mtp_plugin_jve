<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.3
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflUserSettings_TestCase extends TestCase
{
	public function getDisplayName() { return 'User Settings'; }
	public function getTestGoals()   { return 'Validate whether a client application can store settings per users.'; }
	public function getTestMethods() { return 'Call LogOn/LogOff and GetUserSettings/SaveUserSettings workflow web services.'; }
	public function getPrio()        { return 200; }
	public function isSelfCleaning() { return true; }

	/** @var string $ticket */
	private $ticket;

	/** @var WW_Utils_TestSuite $utils */
	private $utils;

	/**
	 * @inheritdoc
	 */
	public function runTest()
	{
		try {
			$this->setUpTestData();

			$this->testRoundtripSettingsOverLogOnLogOff();
			$this->cleanSettingsInDatabase();

			$this->testRoundtripSettingsOverSaveAndGet();
			$this->cleanSettingsInDatabase();
			
		} catch( BizException $e ) {}
		$this->tearDownTestData();
	}

	/**
	 * Prepare data used by this test script.
	 */
	private function setUpTestData()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );
	}

	/**
	 * Breakdown data used by this test script.
	 */
	private function tearDownTestData()
	{
		if( $this->ticket ) {
			$this->utils->wflLogOff( $this, $this->ticket );
		}
	}

	/**
	 * Call LogOn and LogOff and validate the user settings round-tripped (saved+received) through these web services.
	 */
	private function testRoundtripSettingsOverLogOnLogOff()
	{
		$this->clientAppName = 'WflUserSettings_App1_'.self::composeTimestampWithMs();
		$this->logOn();
		$this->assertCount( 0, $this->settings );

		$this->settings = array();
		$this->settings[] = new Setting( 'Aap', 'abc' );
		$this->settings[] = new Setting( 'Noot', '123' );
		$this->settings[] = new Setting( 'Mies', '' );
		$this->logOff();

		$this->logOn();
		$this->assertCount( 3, $this->settings );
	}

	/**
	 * Call the LogOn service and retrieve the user settings.
	 */
	private function logOn()
	{
		$this->utils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->ClientAppName = $this->clientAppName;
				$req->RequestInfo = array( 'Settings' );
			}
		);
		$response = $this->utils->wflLogOn( $this );
		$this->assertInstanceOf( 'WflLogOnResponse', $response );

		$this->assertFalse( empty( $response->Ticket ) );
		$this->ticket = $response->Ticket;

		$this->settings = $response->Settings;
	}

	/**
	 * Call the LogOff service and save the user settings.
	 */
	private function logOff()
	{
		$this->utils->setRequestComposer(
			function( WflLogOffRequest $req ) {
				$req->SaveSettings = true;
				$req->Settings = $this->settings;
			}
		);
		$this->utils->wflLogOff( $this, $this->ticket );
		$this->ticket = null;
	}

	/**
	 * Remove the user settings for the current client application.
	 */
	private function cleanSettingsInDatabase()
	{
		$this->settings = array();
		$this->logOff();
		$this->logOn();
		$this->assertCount( 0, $this->settings );
		$this->logOff();
	}

	/**
	 * Call GetUserSettings and SaveUserSettings and validate the user settings round-tripped through these web services.
	 */
	private function testRoundtripSettingsOverSaveAndGet()
	{
		$this->clientAppName = 'WflUserSettings_App2_'.self::composeTimestampWithMs();
		$this->logOn();
		$this->assertCount( 0, $this->settings );

		$this->getSettings();
		$this->assertCount( 0, $this->settings );

		$this->settings = array();
		$this->settings[] = new Setting( 'Aap', 'abc' );
		$this->settings[] = new Setting( 'Noot', '123' );
		$this->settings[] = new Setting( 'Mies', '' );
		$this->saveSettings();

		$this->getSettings();
		$this->assertCount( 3, $this->settings );
	}

	/**
	 * Call the GetUserSettings service and retrieve the user settings.
	 */
	private function getSettings()
	{
		require_once BASEDIR . '/server/services/wfl/WflGetUserSettingsService.class.php';
		$request = new WflGetUserSettingsRequest();
		$request->Ticket = $this->ticket;
		/** @var WflGetUserSettingsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Get user settings' );
		$this->assertInstanceOf( 'WflGetUserSettingsResponse', $response );
		$this->settings = $response->Settings;
	}

	/**
	 * Call the SaveUserSettings service and retrieve the user settings.
	 */
	private function saveSettings()
	{
		require_once BASEDIR . '/server/services/wfl/WflSaveUserSettingsService.class.php';
		$request = new WflSaveUserSettingsRequest();
		$request->Ticket = $this->ticket;
		$request->Settings = $this->settings;
		/** @var WflSaveUserSettingsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Save user settings' );
		$this->assertInstanceOf( 'WflSaveUserSettingsResponse', $response );
	}

	/**
	 * Composes a formatted timestamp with milliseconds.
	 *
	 * @return string Formatted timestamp
	 */
	static private function composeTimestampWithMs()
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		return date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;
	}
}