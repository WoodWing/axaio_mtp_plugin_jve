<?php
/**
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This class makes it easy to setup a brand with user authorizations and workflow objects.
 * Al it requires is a home brewed data structure (to be provided) to specify the workflow setup.
 * Functions are available to retrieve configuration helper classes for all three areas mentioned.
 * It offers a tear down function to delete everything it has created before.
 */

require_once BASEDIR.'/server/wwtest/testsuite/Setup/AbstractConfig.class.php';

class WW_TestSuite_Setup_WorkflowFactory
{
	/** @var WW_TestSuite_Setup_PublicationConfig */
	private $publicationConfig;
	/** @var WW_TestSuite_Setup_AuthorizationConfig */
	private $authConfig;
	/** @var WW_TestSuite_Setup_ObjectConfig */
	private $objectConfig;

	/**
	 * @param TestCase $testCase
	 * @param string $ticket
	 * @param WW_Utils_TestSuite $testSuiteUtils
	 */
	public function __construct( TestCase $testCase, $ticket, WW_Utils_TestSuite $testSuiteUtils )
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round( $microTime[0] * 1000 ) );
		$timeStamp = date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;

		require_once BASEDIR.'/server/wwtest/testsuite/Setup/PublicationConfig.class.php';
		$this->publicationConfig = new WW_TestSuite_Setup_PublicationConfig( $testCase, $ticket, $timeStamp, $testSuiteUtils );

		require_once BASEDIR.'/server/wwtest/testsuite/Setup/AuthorizationConfig.class.php';
		$this->authConfig = new WW_TestSuite_Setup_AuthorizationConfig( $testCase, $ticket, $timeStamp, $testSuiteUtils );

		require_once BASEDIR.'/server/wwtest/testsuite/Setup/ObjectConfig.class.php';
		$this->objectConfig = new WW_TestSuite_Setup_ObjectConfig( $testCase, $ticket, $timeStamp, $testSuiteUtils );
	}

	/**
	 * @param stdClass $config
	 */
	public function setConfig( $config )
	{
		foreach( array( 'Publications' ) as $key ) {
			if( isset( $config->{$key} ) ) {
				$this->publicationConfig->addConfigOption( $key, $config->{$key} );
			}
		}
		foreach( array( 'Users', 'UserGroups', 'Memberships', 'AccessProfiles', 'UserAuthorizations', 'AdminAuthorizations' ) as $key ) {
			if( isset( $config->{$key} ) ) {
				$this->authConfig->addConfigOption( $key, $config->{$key} );
			}
		}
		foreach( array( 'Objects' ) as $key ) {
			if( isset( $config->{$key} ) ) {
				$this->objectConfig->addConfigOption( $key, $config->{$key} );
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setupTestData()
	{
		$this->publicationConfig->setupTestData();

		$this->authConfig->setPublicationConfig( $this->publicationConfig );
		$this->authConfig->setupTestData();

		$this->objectConfig->setPublicationConfig( $this->publicationConfig );
		$this->objectConfig->setupTestData();
	}

	/**
	 * @inheritdoc
	 */
	public function teardownTestData()
	{
		$this->objectConfig->teardownTestData();
		$this->authConfig->teardownTestData();
		$this->publicationConfig->teardownTestData();
	}

	/**
	 * @return WW_TestSuite_Setup_PublicationConfig
	 */
	public function getPublicationConfig()
	{
		return $this->publicationConfig;
	}

	/**
	 * @return WW_TestSuite_Setup_AuthorizationConfig
	 */
	public function getAuthorizationConfig()
	{
		return $this->authConfig;
	}

	/**
	 * @return WW_TestSuite_Setup_ObjectConfig
	 */
	public function getObjectConfig()
	{
		return $this->objectConfig;
	}
}