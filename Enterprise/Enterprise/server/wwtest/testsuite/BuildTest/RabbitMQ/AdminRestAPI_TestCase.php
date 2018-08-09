<?php
/**
 * @since      v10.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/utils/TestSuite.php';

class WW_TestSuite_BuildTest_RabbitMQ_AdminRestAPI_TestCase extends TestCase
{
	/** @var WW_Utils_RabbitMQ_RestAPI_Client $client */
	private $client;

	/** @var MessageQueueConnection $client */
	private $connection;

	public function getDisplayName() { return 'RabbitMQ - Administration REST API'; }
	public function getTestGoals()   { return 'Checks if RabbitMQ administration web services can be called from Enterprise Server.'; }
	public function getPrio()        { return 10; }

	public function getTestMethods()
	{
		return 
			'Queue management:<ol>
				<li>Create queue.</li>
				<li>List queues to confirm creation was successful.</li>
				<li>Delete queue.</li>
				<li>List queues to confirm deletion was successful.</li>
			</ol>
			Exchange management:<ol>
				<li>Create exchange.</li>
				<li>List exchanges to confirm creation was successful.</li>
				<li>Delete exchange.</li>
				<li>List exchanges to confirm deletion was successful.</li>
			</ol>
			User management:<ol>
				<li>Create user.</li>
			</ol>
			';
	}
	
	public function runTest()
	{
		try {
			$this->setUpTestData();
			$this->testQueueManagement();
			$this->testExchangeManagement();
			$this->testUserManagement();
		} 
		catch( BizException $e ) {
		}
		$this->tearDownTestData();
	}

	/**
	 * Create all testcase-specific data. 
	 */
	private function setUpTestData()
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		require_once BASEDIR.'/server/utils/rabbitmq/restapi/Client.class.php';

		// Bail out gently with 'NOT INSTALLED' when the RabbitMQ integration is not setup.
		if( !BizMessageQueue::isInstalled() ) {
			$message = 'The MESSAGE_QUEUE_CONNECTIONS option in configserver.php is not properly configured.';
			$this->setResult( 'NOTINSTALLED', $message );
			throw new BizException( null, 'Server', null, $message, null, 'NOTINSTALLED' );
		}

		// Pick and validate connection configuration.
		$this->connection = BizMessageQueue::getConnection( 'RabbitMQ', 'REST', false );
		$this->assertInstanceOf( 'MessageQueueConnection', $this->connection,
			'Please check the MESSAGE_QUEUE_CONNECTIONS option in the configserver.php file. '.
			'It should have a MessageQueueConnection( \'RabbitMQ\', \'REST\', ... ) configured.' );
		$this->connection->VirtualHost = NumberUtils::createGUID(); // pick different vhost than our enterprise system id
		try {
			$this->client = new WW_Utils_RabbitMQ_RestAPI_Client( $this->connection );
		} catch( BizException $e ) {
			$this->throwError( $e->getMessage() . ' ' . $e->getDetail() );
		}

		// Check if we can connect to RabbitMQ server.
		$curlErrNr = 0; $curlErrMsg = '';
		$this->client->checkConnection( $curlErrNr, $curlErrMsg );

		// Create vhost and user admin permissions to that.
		$this->client->createVirtualHost();
		$this->assertTrue( $this->client->hasVirtualHost() );

		$permissions = new WW_Utils_RabbitMQ_RestAPI_Permissions();
		$permissions->setFullAccess();
		$this->client->setUserPermissions( $this->connection->User, $permissions );

		// Let RabbitMQ perform an internal test.
		$this->client->testAliveness();
	}
	
	/**
	 * Test queue management services through REST API.
	 */
	private function testQueueManagement()
	{
		$queueName = 'test.queue123';
		$this->client->createQueue( $queueName );

		$queues = $this->client->listQueues();
		$this->assertTrue( in_array( $queueName, $queues ) );

		$this->client->deleteQueue( $queueName );

		$queues = $this->client->listQueues();
		$this->assertFalse( in_array( $queueName, $queues ) );
	}

	/**
	 * Test exchange management services through REST API.
	 */
	private function testExchangeManagement()
	{
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';

		$exchangeName = 'test.exchange123';
		$this->client->createExchange( $exchangeName, BizMessageQueue::getExchangeType() );

		$exchanges = $this->client->listExchanges();
		$this->assertTrue( in_array( $exchangeName, $exchanges ) );

		$this->client->deleteExchange( $exchangeName );

		$exchanges = $this->client->listExchanges();
		$this->assertFalse( in_array( $exchangeName, $exchanges ) );
	}

	/**
	 * Test user management services through REST API.
	 */
	private function testUserManagement()
	{
		$queueName = 'test.queue123';
		$exchangeName = 'test.exchange123';
		$rightExpression = '^('.$exchangeName.'|'.$queueName.')$';

		$userJohn = new WW_Utils_RabbitMQ_RestAPI_User();
		$userJohn->Name = $this->connection->VirtualHost.'.john';
		$userJohn->Password = 'ww';
		$userJohn->Tags = array( 'policymaker', 'monitoring' );
		$this->client->createOrUpdateUser( $userJohn );
		$this->assertInstanceOf( 'WW_Utils_RabbitMQ_RestAPI_User', $this->client->getUser( $userJohn->Name ) );

		$permissions = new WW_Utils_RabbitMQ_RestAPI_Permissions();
		$permissions->Write = $rightExpression;
		$permissions->Read = $rightExpression;
		$permissions->Configure = $rightExpression;
		$this->client->setUserPermissions( $userJohn->Name, $permissions );

		$userJill = new WW_Utils_RabbitMQ_RestAPI_User();
		$userJill->Name = $this->connection->VirtualHost.'.jill';
		$userJill->Password = 'ww';
		$userJill->Tags = array();
		$this->client->createOrUpdateUser( $userJill );
		$this->assertInstanceOf( 'WW_Utils_RabbitMQ_RestAPI_User', $this->client->getUser( $userJill->Name ) );

		$permissions = new WW_Utils_RabbitMQ_RestAPI_Permissions();
		$permissions->Write = $rightExpression;
		$permissions->Read = $rightExpression;
		$permissions->Configure = $rightExpression;
		$this->client->setUserPermissions( $userJill->Name, $permissions );

		$permissions = $this->client->listPermissions();
		$this->assertInstanceOf( 'WW_Utils_RabbitMQ_RestAPI_Permissions', $permissions[$userJohn->Name] );
		$this->assertInstanceOf( 'WW_Utils_RabbitMQ_RestAPI_Permissions', $permissions[$userJill->Name] );

		$this->client->deleteUser( $userJohn->Name );
		$this->client->deleteUser( $userJill->Name );
	}

	/**
	 * Remove all test-specific data.
	 */
	private function tearDownTestData()
	{
		if( $this->client ) {
			$this->client->deleteVirtualHost();
			$this->client = null;
		}
	}
}