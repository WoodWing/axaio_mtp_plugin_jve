<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
require_once BASEDIR.'/server/utils/TestSuite.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class WW_TestSuite_BuildTest_WebServices_WflServices_WflSmartEventQueue_TestCase extends TestCase
{
	// Session related stuff
	/** @var string $ticket  */
	private $ticket = null;
	/** @var string[] $vars  */
	private $vars = null;
	/** @var WW_Utils_TestSuite $util */
	private $util = null;
	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflUtil */
	private $wflUtil = null;
	
	// Constant variables.
	/** @var array $validQueueInstances  */
	private static $validQueueInstances = [ 'RabbitMQ' ];
	/** @var array $validQueueProtocols  */
	private static $validQueueProtocols = ['AMQP', 'STOMPWS'];

	// General Build test initialization data
	/** @var PublicationInfo $publication  */
	private $publication = null;
	
	// Testcase-specific data.
	/** @var AdmPublication $overrulePublication */
	private $overrulePublication = null;
	/** @var AdmPubChannel */
	private $overrulePubChannel = null;
	/** @var AdmIssue $overruleIssue */
	private $overruleIssue = null;
	/** @var AdmUserGroup $userGroup  */
	private $userGroup = null;
	/** @var AdmStatus  */
	private $overruleStatus = null;
	/** @var AdmSection */
	private $overruleCategory = null;
	/** @var AdmUser $userA  */
	private $userA = null;
	/** @var WflLogOnResponse $logonResponseA  */
	private $logonResponseA = null;
	/** @var AdmUser $userB  */
	private $userB = null;
	/** @var WflLogOnResponse $logonResponseB  */
	private $logonResponseB = null;

	/** @var Object $dossierA1 */
	private $dossierA1 = null;
	/** @var Object $dossierA2 */
	private $dossierA2 = null;

	// Message notification sent to us (AMQP consumer).
	/** @var WW_Utils_RabbitMQ_AMQP_Client[] $amqpClients */
	private $amqpClients = null;
	/** @var array $messagesForUserA Messages arrived for user A. Indexed by exchange name.  */
	private $messagesForUserA = array();
	/** @var array $messagesForUserB Messages arrived for user B. Indexed by exchange name. */
	private $messagesForUserB = array();

	public function getDisplayName() { return 'Enterprise events over RabbitMQ'; }
	public function getTestGoals()   { return 'Checks if RabbitMQ messages are sent and received when performing workflow operations.'; }
	public function getPrio()        { return 430; }

	public function getTestMethods()
	{
		return
			'Use RabbitMQ in order to produce and consume messages.
			Setup test data: <ol>
				<li>Create a user group, brand, channel and overrule issue.</li>
				<li>For the brand and overrule issue, create a status and category.</li>
				<li>Add the user group to the brand- and overrule issue authorization.</li>
				<li>Create User A and B and add them to the user group.</li>
			</ol>
			Test case 1:<ol>
				<li>Log on User B to Enterprise with RequestInfo containing "MessageQueueConnections".</li>
				<li>Verify that the LogOnResponse contains valid data.</li>
				<li>Log on user B to RabbitMQ using the connection information found in the LogOnResponse.</li>
				<li>Verify that the connection is made by querying the RabbitMQ connections.</li>
				<li>Subscribe user B to the system-, brand- and overrule issue exchanges (found in the LogOnResponse) .</li>
			</ol>
			Test case 2:<ol>
				<li>Log on User A to Enterprise with RequestInfo containing "MessageQueueConnections".</li>
				<li>Verify that the LogOnResponse contains valid data.</li>
				<li>Log on user A to RabbitMQ using the connection information found in the LogOnResponse.</li>
				<li>Verify that the connection is made by querying the RabbitMQ connections.</li>
				<li>Check if user B has received a message about the user A logon.</li>
			</ol>
			Test case 3:<ol>
				<li>User A create a dossier in the normal brand.</li>
				<li>Check if user B has received a message about the object creation.</li>
			</ol>
			Test case 4:<ol>
				<li>User A create a dossier in the overrule issue.</li>
				<li>Check if user B has received a message about the object creation.</li>
			</ol>
			Tear down test data:<ol>
				<li>Remove user A and B from RabbitMQ.</li>
				<li>Remove the brand- and overrule issue exchanges from RabbitMQ.</li>
				<li>Log off User A and B.</li>
				<li>Delete User A and B.</li>
				<li>Remove the user group from the brand- and overrule issue authorization.</li>
				<li>For the brand and overrule issue, delete the status and category.</li>
				<li>Delete the user group, overrrule issue, channel and brand.</li>
			</ol>';
	}
	
	public function runTest()
	{
		try {
			$this->initTest();
			$this->setUpTestData();

			// Test case 1:
			$this->logonResponseB = $this->logonWithQueues( 'Log on User B.', $this->userB );
			$this->validateLogOnResponse( $this->logonResponseB, $this->userB );
			$this->logOnRabbitMQ( $this->logonResponseB, $this->userB );
			$this->subscribeForMessages( $this->logonResponseB, array( $this, 'processMessageForUserB' ) );

			// Test case 2:
			$this->logonResponseA = $this->logonWithQueues( 'Log on User A.', $this->userA );
			$this->validateLogOnResponse( $this->logonResponseA, $this->userA );
			$this->logOnRabbitMQ( $this->logonResponseA, $this->userA );
			$this->checkIfLogOnMessageHasArrived( $this->logonResponseB->Ticket,
				$this->messagesForUserB[ BizMessageQueue::composeExchangeNameForSystem() ],
				$this->userA->Name,
				$this->logonResponseB->MessageQueue,
				'User B should have received a LogOn message for the event of user A who did log on to Enterprise.' );

			// Test case 3:
			$this->letUserACreateDossierA1();
			$this->checkIfCreateObjectsMessageHasArrived( $this->logonResponseB->Ticket,
				$this->messagesForUserB[ BizMessageQueue::composeExchangeNameForPublication( $this->publication->Id ) ],
				$this->dossierA1->MetaData->BasicMetaData->ID,
				$this->logonResponseB->MessageQueue,
				'User B should have received CreateObjects message for the event of user A creating Dossier A1 in the brand.' );

			// Test case 4:
			$this->letUserACreateOverruleDossierA2();
			$this->checkIfCreateObjectsMessageHasArrived( $this->logonResponseB->Ticket,
				$this->messagesForUserB[ BizMessageQueue::composeExchangeNameForOverruleIssue( $this->overruleIssue->Id ) ],
				$this->dossierA2->MetaData->BasicMetaData->ID,
				$this->logonResponseB->MessageQueue,
				'User B should have received CreateObjects message for the event of user A creating Dossier A1 in the overrule issue.' );
		}
		catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		$this->tearDownTestData();
	}

	/**
	 * Initialise utils classes and validate options used for testing.
	 *
	 * @throws BizException Bails out when initialisation has failed. The script result is then set already.
	 */
	private function initTest()
	{
		$this->vars = $this->getSessionVariables();
		$this->publication = $this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->util = new WW_Utils_TestSuite();
		$this->util->initTest( 'SOAP' );
		$this->wflUtil = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		$this->assertTrue( $this->wflUtil->initTest( $this, 'RMQ', 'SOAP' ) );

		// Validate the MESSAGE_QUEUE_CONNECIONS property configured in configserver.php
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		if( !BizMessageQueue::isInstalled() ) {
			$message = 'The MESSAGE_QUEUE_CONNECTIONS option in configserver.php is not properly configured.';
			$map = new BizExceptionSeverityMap( array( $message => 'INFO' ));
			$this->setResult( 'NOTINSTALLED', $message );
			throw new BizException( null, 'Server', null, $message );
		}
		$connection = BizMessageQueue::getConnection( 'RabbitMQ', 'AMQP', false );
		$this->assertInstanceOf( 'MessageQueueConnection', $connection,
			'Please check the MESSAGE_QUEUE_CONNECTIONS option in the configserver.php file. '.
			'It should have a MessageQueueConnection( \'RabbitMQ\', \'AMQP\', ... ) configured.' );
		LogHandler::Log( 'TestSuite', 'DEBUG', 'Using the MESSAGE_QUEUE_CONNECTIONS property configured in configserver.' );
	}

	/**
	 * Create all testcase-specific data. 
	 */
	private function setUpTestData()
	{
		$this->userGroup = $this->wflUtil->createUserGroup( 'Creating a user group.' );
		
		$profileId = 1; // Using the 'Full Control' access profile id hard-coded  as there is no good way of
		                // retrieving this id at the moment.
		LogHandler::Log( 'TestSuite', 'DEBUG', 'Setup: Adding the usergroup to the brand authorizations.' );
		$this->wflUtil->addAuthorization( $this->publication->Id, 0, $this->userGroup->Id, 0, 0, $profileId );
		
		$this->userA = $this->wflUtil->createUser( 'Setup: Creating User A.' );
		$this->userA->Password = 'ww';
		$this->assertInstanceOf( 'AdmUser', $this->userA );
		$this->wflUtil->addUserGroupToUser( 'Setup: Adding User A to the general user group.', $this->userA->Id,
			[$this->userGroup->Id] );
		
		$this->userB = $this->wflUtil->createUser( 'Creating User B.' );
		$this->userB->Password = 'ww';
		$this->assertInstanceOf( 'AdmUser', $this->userB );
		$this->wflUtil->addUserGroupToUser( 'Setup: Adding User B to the general user group.', $this->userB->Id,
			[$this->userGroup->Id] );

		$this->overrulePublication = $this->wflUtil->createAdmPublication( 
			'Setup: Create publication for overrule issue environment.', null );

		$this->overrulePubChannel = $this->wflUtil->createPubChannel(
			'Setup: Create publication channel for overrule issue environment.', $this->overrulePublication->Id );

		$this->overruleIssue = $this->wflUtil->createIssue(
			'Setup: Create an overule issue.', $this->overrulePublication->Id, $this->overrulePubChannel->Id, null, true );

		LogHandler::Log( 'TestSuite', 'DEBUG', 'Setup: Create a workflow status for Dossiers for the overrule issue.' );
		$this->overruleStatus = $this->wflUtil->createStatus( 'Draft', 'Dossier', $this->overrulePublication->Id, 0,
			$this->overruleIssue->Id );

		$this->overruleCategory = $this->wflUtil->createCategory( $this->overrulePublication->Id,
			'Setup: Create a Category for the overrule issue.', null, $this->overruleIssue->Id );

		LogHandler::Log( 'TestSuite', 'DEBUG', 'Setup: Adding the usergroup to the overrule issue authorizations.' );
		$this->wflUtil->addAuthorization( $this->overrulePublication->Id, $this->overruleIssue->Id, $this->userGroup->Id,
			0, 0, $profileId );
	}
	
	/**
	 * Remove all test-specific data.
	 */
	private function tearDownTestData()
	{
		// Remove the objects from brand and overrule issue.
		/** @var Object $deleteObject */
		foreach( array( $this->dossierA1, $this->dossierA2 ) as $deleteObject ) {
			try {
				if( !is_null( $deleteObject ) ) {
					$this->wflUtil->setRequestComposer(
						function( WflDeleteObjectsRequest $req ) {
							$req->Ticket = $this->logonResponseA->Ticket;
						}
					);
					$errorReport = null;
					$this->wflUtil->deleteObject( $deleteObject->MetaData->BasicMetaData->ID,
						'Teardown: Deleting dossier object.', $errorReport );
				}
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}
		$this->dossierA1 = null;
		$this->dossierA2 = null;

		// Remove exchanges from RabbitMQ.
		// Note that Enterprise Server does create them, but not auto clean them which is by design.
		// Enterprise Server does auto clean users for security reasons.
		// (The Health Check can be used to remove orphan resources.)
		require_once BASEDIR.'/server/utils/rabbitmq/restapi/Client.class.php';
		$connection = BizMessageQueue::getConnection( 'RabbitMQ', 'REST', false );
		if( $connection ) {
			$restClient = new WW_Utils_RabbitMQ_RestAPI_Client( $connection );
			if( $this->logonResponseA || $this->logonResponseB ) {
				if( $this->overruleIssue ) {
					try {
						$restClient->deleteExchange( BizMessageQueue::composeExchangeNameForOverruleIssue( $this->overruleIssue->Id ) );
					} catch( BizException $e ) {}
				}
			}
		}

		// Log off user A.
		if( $this->logonResponseA ) {
			try {
				$amqpClient = @$this->amqpClients[$this->logonResponseA->Ticket];
				if( isset( $amqpClient ) ) {
					$amqpClient->logOff();
				}
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
			try {
				require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
				$request = new WflLogOffRequest();
				$request->Ticket = $this->logonResponseA->Ticket;
				$this->util->callService( $this, $request, 'Teardown: Log off User A' );
				$this->logonResponseA = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Log off user B
		if( $this->logonResponseB ) {
			try {
				$amqpClient = @$this->amqpClients[$this->logonResponseB->Ticket];
				if( isset( $amqpClient ) ) {
					$amqpClient->logOff();
				}
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
			try {
				require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
				$request = new WflLogOffRequest();
				$request->Ticket = $this->logonResponseB->Ticket;
				$this->util->callService( $this, $request, 'Teardown: Log off User B' );
				$this->logonResponseB = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Delete user A.
		if( $this->userA ) {
			try {
				$this->wflUtil->deleteUser( 'Teardown: Deleting User A.', $this->userA->Id );
				$this->userA = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Delete user B.
		if( $this->userB ) {
			try {
				$this->wflUtil->deleteUser( 'Teardown: Deleting User B.', $this->userB->Id );
				$this->userB = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Remove group from brand and overrule issue.
		if( $this->userGroup ) {
			try {
				$this->wflUtil->removeAuthorization( $this->publication->Id, 0, $this->userGroup->Id, 0, 0 );
				$this->wflUtil->removeAuthorization( $this->overrulePublication->Id, $this->overruleIssue->Id,
					$this->userGroup->Id, 0, 0 );
			} catch( BizException $e ) {} // Exception $e is already thrown.
			try {
				$this->wflUtil->deleteUserGroup( $this->userGroup->Id );
				$this->userGroup = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Remove the status from the overrule issue.
		if( $this->overruleStatus ) {
			try {
				$this->wflUtil->deleteStatus( $this->overruleStatus->Id );
				$this->overruleStatus = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Remove the category from the overrule issue.
		if( $this->overruleCategory) {
			try {
				$this->wflUtil->deleteCategory( 'Teardown: Delete the category from the overrule issue.',
					$this->overrulePublication->Id, $this->overruleCategory->Id, $this->overruleIssue->Id );
				$this->overruleCategory = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Remove the overrule issue.
		if( $this->overruleIssue ) {
			try {
				$this->wflUtil->deleteIssue( 'Teardown: Delete the overrule issue.', $this->overrulePublication->Id,
					$this->overruleIssue->Id );
				$this->overruleIssue = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Remove the publication channel.
		if( $this->overrulePubChannel ) {
			try {
				$this->wflUtil->deletePubChannel(
					'Teardown: Delete the overrule publication channel.', $this->overrulePublication->Id, $this->overrulePubChannel->Id );
				$this->overrulePubChannel = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}

		// Remove the brand.
		if( $this->overrulePublication ) {
			try {
				$this->wflUtil->deletePublications( 'Teardown: Delete the overrule publication.', [ $this->overrulePublication->Id ] );
				$this->overrulePublication = null;
			} catch( BizException $e ) {} // Exception $e was already thrown and handled before.
		}
	}

	/**
	 * Logs on a user, and requests for MessageQueueConnections in the RequestInfo.
	 * 
	 * @param string $stepInfo Extra information on what step is being done.
	 * @param AdmUser $user The user logging on.
	 * @return WflLogOnResponse
	 * @throws BizException
	 */
	private function logonWithQueues( $stepInfo, AdmUser $user )
	{
		// Determine client app name
		require_once BASEDIR . '/server/utils/UrlUtils.php';
		$clientIP = WW_Utils_UrlUtils::getClientIP();
		$clientName = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '';
		// >>> BZ#6359 Let's use ip since gethostbyaddr could be extremely expensive!
		if (empty($clientName)) {
			$clientName = $clientIP;
		}
		// if ( !$clientName || ($clientName == $clientIP )) { $clientName = gethostbyaddr($clientIP); }
		// <<<

		require_once BASEDIR . '/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User = $user->Name;
		$request->Password = $user->Password;
		$request->Ticket = '';
		$request->Server = 'Enterprise Server';
		$request->ClientName = $clientName;
		$request->Domain = '';
		$request->ClientAppName = 'TestSuite-Wfl';
		$request->ClientAppVersion = 'v' . SERVERVERSION;
		$request->ClientAppSerial = '';
		$request->ClientAppProductKey = '';
		$request->RequestInfo = array('Publications', 'MessageQueueConnections');

		/** @var WflLogOnResponse $response */
		return $this->util->callService( $this, $request, $stepInfo, null, true );
	}

	/**
	 * Validates the message queue definitions in the logon response for a given user.
	 *
	 * @param WflLogOnResponse $response
	 * @param AdmUser $user The user logging on.
	 * @throws BizException
	 */
	private function validateLogOnResponse( WflLogOnResponse $response, AdmUser $user )
	{
		// Verify the LogonResponse->MessageQueueConnections
		$mqConnections = $response->MessageQueueConnections;
		$this->assertFalse( empty( $mqConnections ),
			'The LogOnResponse did not contain any MessageQueueConnections despite asking for them.' );
		if( $mqConnections ) foreach( $mqConnections as $mqConnection ) {
			$this->assertInstanceOf('MessageQueueConnection', $mqConnection );
			$this->assertTrue( in_array( $mqConnection->Instance, self::$validQueueInstances ),
				'Unsupported MessageQueueConnection Instance found in logon response: '.$mqConnection->Instance );
			$this->assertTrue( in_array( $mqConnection->Protocol, self::$validQueueProtocols ),
				'Unsupported MessageQueueConnection Protocol found in logon response: '.$mqConnection->Protocol );
		}

		// Verify the LogOnResponse->MessageQueue.
		$this->assertFalse( empty( $response->MessageQueue ), 'The MessageQueue was not set in logon response' );
	}

	/**
	 * Log on to RabbitMQ.
	 *
	 * @param WflLogOnResponse $response
	 * @param AdmUser $user The user logging on.
	 * @throws BizException
	 */
	private function logOnRabbitMQ( WflLogOnResponse $response, AdmUser $user )
	{
		$amqpClient = null;
		$mqConnections = $response->MessageQueueConnections;
		if( $mqConnections ) foreach ( $mqConnections as $connection ) {
			if( $connection->Instance == 'RabbitMQ' && $connection->Protocol == 'AMQP' ) {
				$this->assertNull( $amqpClient,
					'Detected more than one AMQP configuration for RabbitMQ in LogOnResponse, which is unexpected.' );
				$this->assertTrue( $connection->Public,
					'LogOnResponse should not reveal private connections for RabbitMQ AMQP.' );
				try {
					require_once BASEDIR . '/server/utils/rabbitmq/amqp/Client.class.php';
					$amqpClient = new WW_Utils_RabbitMQ_AMQP_Client( $connection );
					$this->assertTrue( $amqpClient->logOn() );
				} catch( BizException $e ) {
					$this->throwError( $e->getMessage() );
				}
				$this->assertTrue( $amqpClient->isConnected() );
			}
		}
		$this->assertNotNull( $amqpClient,
			'No AMQP configuration for RabbitMQ detected in LogOnResponse, which is unexpected.' );
		$this->amqpClients[ $response->Ticket ] = $amqpClient;
	}

	/**
	 * Subscribes for the message queue returned in the give logon response. The subscriptions are setup per exchange
	 * to validate whether or not the messages arrive through the expected exchanges. Note that production client should
	 * subscribe on message queue level, which is much easier.
	 *
	 * @param WflLogOnResponse $response
	 * @param callable $processMessageCallback Callback function should define three parameters: ( array $headers, array $fields, $exchangeName )
	 * @throws BizException
	 */
	private function subscribeForMessages( WflLogOnResponse $response, $processMessageCallback )
	{
		$this->assertFalse( empty( $response->MessageQueue ),
			'Could not find the message queue for system events the logon response.' );
		try {
			$amqpClient = $this->amqpClients[$response->Ticket];
			$amqpClient->subscribeForMessageQueue( $response->MessageQueue, $processMessageCallback );
		} catch( Exception $e ) {
			$this->throwError( $e->getMessage() );
		}
	}

	/**
	 * Let user A create a new dossier in the normal brand.
	 */
	private function letUserACreateDossierA1()
	{
		$this->wflUtil->setRequestComposer(
			function( WflCreateObjectsRequest $req ) {
				$req->Ticket = $this->logonResponseA->Ticket;
			}
		);
		$this->dossierA1 = $this->wflUtil->createDossierObject( 'User A creates a new Dossier A1 in the normal brand.' );
	}

	/**
	 * Let user A create a new dossier in the overrule issue.
	 */
	private function letUserACreateOverruleDossierA2()
	{
		$this->wflUtil->setRequestComposer(
			function( WflCreateObjectsRequest $req ) {
				$req->Ticket = $this->logonResponseA->Ticket;
				$req->Objects[0]->MetaData->BasicMetaData->Publication = new Publication( $this->overrulePublication->Id );
				$req->Objects[0]->MetaData->BasicMetaData->Category = new Category( $this->overruleCategory->Id );
				$req->Objects[0]->MetaData->WorkflowMetaData->State = new State( $this->overruleStatus->Id );
				$req->Objects[0]->Targets = array();
				$req->Objects[0]->Targets[] = new Target();
				$req->Objects[0]->Targets[0]->PubChannel = new PubChannel( $this->overrulePubChannel->Id );
				$req->Objects[0]->Targets[0]->Issue = new Issue( $this->overruleIssue->Id );
			}
		);
		$this->dossierA2 = $this->wflUtil->createDossierObject( 'User A creates a new Dossier A2 in the overrule issue.' );
	}

	/**
	 * Validates whether or not the user did receive the CreateObjects event.
	 *
	 * @param string $ticket The ticket for the user for which the message should arrive.
	 * @param object[] $messages The messages collected for the user so far.
	 * @param int $objectId The object id for which the CreateObjects event is expected.
	 * @param string $queueName Queue wherein the message should arrive.
	 * @param string $expected Debug information reported when the expected message was not found.
	 */
	private function checkIfCreateObjectsMessageHasArrived( $ticket, &$messages, $objectId, $queueName, $expected )
	{
		require_once BASEDIR.'/server/smartevent.php'; // EVENT_CREATEOBJECT
		$found = false;
		$started = microtime( true );
		while( !$found && microtime( true ) - $started < 2.0 ) { // max 2 seconds wait
			$this->amqpClients[$ticket]->waitAndConsumeMessages( 2, $queueName );  // max 2 seconds wait
			if( $messages ) foreach( $messages as $message ) {
				if( $message['headers']['EventId'] == EVENT_CREATEOBJECT &&
					$message['fields']['ID'] == $objectId ) {
					$found = true;
					break 2;
				}
				usleep( 25000 ); // nap for 25ms
			}
		}
		$this->assertFalse( empty( $messages ), $expected.' But the user did not receive any events at all.' );
		$this->assertTrue( $found, $expected.' Events were received but not the CreateObjects event.' );
	}

	/**
	 * Validates whether or not the user did receive the LogOn event.
	 *
	 * @param string $ticket The ticket for the user for which the message should arrive.
	 * @param object[] $messages The messages collected for the user so far.
	 * @param int $userShortName The user for which the LogOn event is expected.
	 * @param string $queueName Queue wherein the message should arrive.
	 * @param string $expected Debug information reported when the expected message was not found.
	 */
	private function checkIfLogOnMessageHasArrived( $ticket, &$messages, $userShortName, $queueName, $expected )
	{
		require_once BASEDIR.'/server/smartevent.php'; // EVENT_LOGON
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		$found = false;
		$started = microtime( true );
		while( !$found && microtime( true ) - $started < 2.0 ) { // max 2 seconds wait
			$this->amqpClients[$ticket]->waitAndConsumeMessages( 2, $queueName );  // max 2 seconds wait
			if( $messages ) foreach( $messages as $message ) {
				if( $message['headers']['EventId'] == EVENT_LOGON &&
					$message['fields']['UserID'] == $userShortName ) {
					$found = true;
					break 2;
				}
				usleep( 25000 ); // nap for 25ms
			}
		}
		$this->assertFalse( empty( $messages ), $expected.' But the user did not receive any events at all.' );
		$this->assertTrue( $found, $expected.' Events were received but not the LogOn event.' );
	}

	/**
	 * Called back when user A receives message notifications from RabbitMQ.
	 *
	 * @param string[] $headers
	 * @param string[] $fields
	 * @param string $exchangeName
	 */
	public function processMessageForUserA( array $headers, array $fields, $exchangeName )
	{
		$messageBody = array( 'headers' => $headers, 'fields' => $fields );
		$this->messagesForUserA[$exchangeName][] = $messageBody;
		LogHandler::Log( 'TestSuite', 'DEBUG', 'Through the "'.$exchangeName.'" exchange the processMessageForUserA() callback '.
			'has received the following message: '.print_r($messageBody,true) );
	}

	/**
	 * Called back when user B receives message notifications from RabbitMQ.
	 *
	 * @param string[] $headers
	 * @param string[] $fields
	 * @param string $exchangeName
	 */
	public function processMessageForUserB( array $headers, array $fields, $exchangeName )
	{
		$messageBody = array( 'headers' => $headers, 'fields' => $fields );
		$this->messagesForUserB[$exchangeName][] = $messageBody;
		LogHandler::Log( 'TestSuite', 'DEBUG', 'Through the "'.$exchangeName.'" exchange the processMessageForUserB() callback '.
			'has received the following message: '.print_r($messageBody,true) );
	}
}