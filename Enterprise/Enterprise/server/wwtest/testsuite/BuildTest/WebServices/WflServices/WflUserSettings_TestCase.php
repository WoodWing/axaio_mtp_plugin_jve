<?php
/**
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

	/** @var string[] $tickets Ticket per client app. */
	private $tickets = array();

	/** @var WW_Utils_TestSuite $utils */
	private $utils;

	/** @var Setting[][] $settings Collection of settings per client app. */
	private $settings = array();

	/** @var string $clientAppName Currently acting client app. */
	private $clientAppName;

	/**
	 * @inheritdoc
	 */
	public function runTest()
	{
		try {
			$this->setUpTestData();

			$this->testRoundtripSettingsOverLogOnLogOff();
			$this->testUserQueriesForMoverOverLogOnLogOff();
			$this->testCleanSettingsInDatabaseOverLogOnLogOff();

			$this->testRoundtripSettingsOverSaveAndGet();
			$this->testQuerySettingsOverSaveAndGet();
			$this->testCleanSettingsOverSaveAndGet();

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
		if( $this->tickets ) foreach( $this->tickets as $clientAppName => $ticket ) {
			$this->utils->wflLogOff( $this, $ticket );
			unset( $this->tickets[$clientAppName] );
		}
	}

	/**
	 * Call LogOn and LogOff and validate the user settings round-tripped (saved+received) through these web services.
	 */
	private function testRoundtripSettingsOverLogOnLogOff()
	{
		// Start with new client application have no settings.
		$this->clientAppName = 'WflUserSettings_App1_'.self::composeTimestampWithMs();
		$this->logOn( array( 'Settings' ) );
		$this->assertCount( 0, $this->settings[$this->clientAppName] );

		// Insert some settings for the current user who is now working with the client application.
		$settings = array();
		$settings[] = new Setting( 'Aap1', 'abc' );
		$settings[] = new Setting( 'Noot1', '123' );
		$settings[] = new Setting( 'Mies1', '' );
		// Simulate SC having duplicate settings (such as 'QueryPanels'):
		$settings[] = new Setting( 'Vuur1', 'foo' );
		$settings[] = new Setting( 'Vuur1', 'bar' );
		// Simulate SC saving user queries, as preparation to the succeeding Smart Mover test:
		$settings[] = new Setting( 'UserQuery_foo', 'foo' ); // must have UserQuery prefix
		$settings[] = new Setting( 'UserQuery2_bar', 'bar' );
		$this->settings[$this->clientAppName] = $settings;
		$this->logOff();

		$this->logOn( array( 'Settings' ) );
		$expected = array(
			new Setting( 'Aap1', 'abc' ),
			new Setting( 'Noot1', '123' ),
			new Setting( 'Mies1', '' ),
			new Setting( 'Vuur1', 'foo' ),
			new Setting( 'Vuur1', 'bar' ),
			new Setting( 'UserQuery_foo', 'foo' ),
			new Setting( 'UserQuery2_bar', 'bar' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );
	}

	/**
	 * Call LogOn and LogOff for Smart Mover and validate if the UserQuery user settings are taken from other clients.
	 */
	private function testUserQueriesForMoverOverLogOnLogOff()
	{
		$otherClientAppName = $this->clientAppName; // temporary switch to Mover
		$this->clientAppName = 'Mover-WflUserSettings_'.self::composeTimestampWithMs(); // must have Mover prefix
		$this->logOn( array( 'Settings' ) );
		// Don't assertCount() here because the TESTSUITE user could be a real user having UserQuery settings for InDesign/InCopy.

		// The core postfixes the settings with the client application name.
		$expected = array(
			new Setting( "UserQuery_foo-{$otherClientAppName}", 'foo' ),
			new Setting( "UserQuery2_bar-{$otherClientAppName}", 'bar' )
		);
		$matches = $this->countEqualSettings( $expected, $this->settings[$this->clientAppName] );
		$this->assertEquals( 2, $matches );

		$this->logOff( false ); // Smart Mover does not save user settings.
		$this->clientAppName = $otherClientAppName; // restore temporary switch
	}

	/**
	 * Remove the user settings for the current client application.
	 */
	private function testCleanSettingsInDatabaseOverLogOnLogOff()
	{
		$this->settings[$this->clientAppName] = array();
		$this->logOff();
		$this->logOn( array( 'Settings' ) );
		$this->assertCount( 0, $this->settings[$this->clientAppName] );
		$this->logOff();
	}

	/**
	 * Call the LogOn service and retrieve the user settings.
	 *
	 * @param string[] $requestInfo
	 */
	private function logOn( $requestInfo )
	{
		$this->utils->setRequestComposer(
			function( WflLogOnRequest $req ) use ( $requestInfo ) {
				$req->ClientAppName = $this->clientAppName;
				$req->RequestInfo = $requestInfo;
			}
		);
		$response = $this->utils->wflLogOn( $this );
		$this->assertInstanceOf( 'WflLogOnResponse', $response );

		$this->assertFalse( empty( $response->Ticket ) );
		$this->tickets[$this->clientAppName] = $response->Ticket;

		$this->settings[$this->clientAppName] = $response->Settings;
	}

	/**
	 * Call the LogOff service and save the user settings.
	 *
	 * @param bool $saveSettings
	 */
	private function logOff( $saveSettings = true )
	{
		if( $saveSettings ) {
			$this->utils->setRequestComposer(
				function( WflLogOffRequest $req ) {
					$req->SaveSettings = true;
					$req->Settings = $this->settings[ $this->clientAppName ];
				}
			);
		}
		$this->utils->wflLogOff( $this, $this->tickets[$this->clientAppName] );
		unset( $this->tickets[$this->clientAppName] );
	}

	/**
	 * Call GetUserSettings and SaveUserSettings and validate the user settings round-tripped through these web services.
	 */
	private function testRoundtripSettingsOverSaveAndGet()
	{
		// Start with new client application have no settings.
		$this->clientAppName = 'WflUserSettings_App2_'.self::composeTimestampWithMs();
		$this->logOn( array( 'Settings', 'PreferNoSettings' ) );
		$this->assertNull( $this->settings[$this->clientAppName] );

		$this->getSettings();
		$this->assertCount( 0, $this->settings[$this->clientAppName] );

		// Insert 3 settings for the current user who is now working with the client application.
		$settings = array();
		$settings[] = new Setting( 'Aap2', 'def' );
		$settings[] = new Setting( 'Noot2', '456' );
		$settings[] = new Setting( 'Mies2', '' );
		$settings[] = new Setting( 'UserQuery_bar', 'bar' ); // must have UserQuery prefix
		$settings[] = new Setting( 'UserQuery3_foo', 'foo' );
		$this->settings[$this->clientAppName] = $settings;
		$this->saveSettings();

		$this->getSettings();
		$expected = array(
			new Setting( 'Aap2', 'def' ),
			new Setting( 'Noot2', '456' ),
			new Setting( 'Mies2', '' ),
			new Setting( 'UserQuery_bar', 'bar' ),
			new Setting( 'UserQuery3_foo', 'foo' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );

		// Update only one of the settings that is changed by the current user.
		$settings = array();
		$settings[] = new Setting( 'Noot2', '789' );
		$this->settings[$this->clientAppName] = $settings;
		$this->saveSettings();

		$this->getSettings();
		$expected = array(
			new Setting( 'Aap2', 'def' ),
			new Setting( 'Noot2', '789' ),
			new Setting( 'Mies2', '' ),
			new Setting( 'UserQuery_bar', 'bar' ),
			new Setting( 'UserQuery3_foo', 'foo' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );
	}

	/**
	 * Call GetUserSettings with all kind of search parameters and validate the results.
	 */
	private function testQuerySettingsOverSaveAndGet()
	{
		// One setting.
		$this->getSettings( array( 'Noot2' ) );
		$expected = array(
			new Setting( 'Noot2', '789' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );

		// Multiple settings.
		$this->getSettings( array( 'Aap2', 'Mies2' ) );
		$expected = array(
			new Setting( 'Aap2', 'def' ),
			new Setting( 'Mies2', '' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );

		// Wildcard search (LIKE).
		$this->getSettings( array( 'UserQuery%' ) );
		$expected = array(
			new Setting( 'UserQuery_bar', 'bar' ),
			new Setting( 'UserQuery3_foo', 'foo' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );

		// Mix of exact matches and wildcard.
		$this->getSettings( array( 'UserQuery%', 'Aap2', 'Noot2', 'Mies2' ) );
		$expected = array(
			new Setting( 'Aap2', 'def' ),
			new Setting( 'Noot2', '789' ),
			new Setting( 'Mies2', '' ),
			new Setting( 'UserQuery_bar', 'bar' ),
			new Setting( 'UserQuery3_foo', 'foo' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );
	}

	/**
	 * Call DeleteUserSettings and GetUserSettings and validate the user settings round-tripped through these web services.
	 */
	private function testCleanSettingsOverSaveAndGet()
	{
		$this->deleteSettings( array( 'Aap2' ) );
		$this->getSettings();
		$expected = array(
			new Setting( 'Noot2', '789' ),
			new Setting( 'Mies2', '' ),
			new Setting( 'UserQuery_bar', 'bar' ),
			new Setting( 'UserQuery3_foo', 'foo' )
		);
		$this->assertSettingsEquals( $expected, $this->settings[$this->clientAppName] );

		$this->deleteSettings( array( 'Noot2', 'Mies2', 'UserQuery_bar', 'UserQuery3_foo' ) );
		$this->getSettings();
		$this->assertCount( 0, $this->settings[$this->clientAppName] );
	}

	/**
	 * Call the GetUserSettings service and retrieve the user settings.
	 *
	 * @param string[]|null
	 */
	private function getSettings( $settingNames = null )
	{
		require_once BASEDIR . '/server/services/wfl/WflGetUserSettingsService.class.php';
		$request = new WflGetUserSettingsRequest();
		$request->Ticket = $this->tickets[$this->clientAppName];
		$request->Settings = $settingNames;
		/** @var WflGetUserSettingsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Get user settings' );
		$this->assertInstanceOf( 'WflGetUserSettingsResponse', $response );
		$this->settings[$this->clientAppName] = $response->Settings;
	}

	/**
	 * Call the SaveUserSettings service and retrieve the user settings.
	 */
	private function saveSettings()
	{
		require_once BASEDIR . '/server/services/wfl/WflSaveUserSettingsService.class.php';
		$request = new WflSaveUserSettingsRequest();
		$request->Ticket = $this->tickets[$this->clientAppName];
		$request->Settings = $this->settings[$this->clientAppName];
		/** @var WflSaveUserSettingsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Save user settings' );
		$this->assertInstanceOf( 'WflSaveUserSettingsResponse', $response );
	}

	/**
	 * Call the DeleteUserSettings service to remove the user settings.
	 *
	 * @param string[] $settingNames
	 */
	private function deleteSettings( $settingNames )
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteUserSettingsService.class.php';
		$request = new WflDeleteUserSettingsRequest();
		$request->Ticket = $this->tickets[$this->clientAppName];
		$request->Settings = $settingNames;
		/** @var WflSaveUserSettingsResponse $response */
		$response = $this->utils->callService( $this, $request, 'Delete user settings' );
		$this->assertInstanceOf( 'WflDeleteUserSettingsResponse', $response );
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

	/**
	 * Asserts that two collections of settings are equal.
	 *
	 * @param Setting[] $expected
	 * @param Setting[] $actual
	 */
	private function assertSettingsEquals( $expected, $actual )
	{
		$this->assertInternalType( 'array', $expected );
		$this->assertInternalType( 'array', $actual );
		$this->assertEquals( count( $expected ), count( $actual ) );
		$matches = $this->countEqualSettings( $expected, $actual );
		$this->assertEquals( count( $expected ), $matches, 'The two collections of Settings are not equal.' );
	}

	/**
	 * Tells for two collections of user settings how many are equal (having the same name and value).
	 *
	 * @param Setting[] $settingsA
	 * @param Setting[] $settingsB
	 * @return int Number of equal settings.
	 */
	private function countEqualSettings( $settingsA, $settingsB )
	{
		$matches = 0;
		foreach( $settingsA as $settingA ) {
			foreach( $settingsB as $settingB ) {
				if( $settingA->Setting === $settingB->Setting ) {
					if( $settingA->Value === $settingB->Value ) {
						$matches += 1;
					}
				}
			}
		}
		return $matches;
	}
}