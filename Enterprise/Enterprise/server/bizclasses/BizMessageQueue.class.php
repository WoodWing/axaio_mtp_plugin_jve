<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v10.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizMessageQueue
{
	/**
	 * Composes an exchange name for system message.
	 *
	 * @return string Exchange name
	 */
	public static function composeExchangeNameForSystem()
	{
		return 'system.events';
	}

	/**
	 * Composes an exchange name for a given publication.
	 *
	 * @param integer $pubId Publication id
	 * @return string Exchange name
	 */
	public static function composeExchangeNameForPublication( $pubId )
	{
		return 'object.p' . $pubId . '.events';
	}

	/**
	 * Composes an exchange name for a given overrule issue.
	 *
	 * @param integer $issueId Issue id
	 * @return string Exchange name
	 */
	public static function composeExchangeNameForOverruleIssue( $issueId )
	{
		return 'object.i' . $issueId . '.events';
	}

	/**
	 * Tells whether or not a given exchange name is ours (managed by this biz class).
	 *
	 * @param string $exchangeName
	 * @return bool
	 */
	public static function isOurExchange( $exchangeName )
	{
		$isOurs = $exchangeName == self::composeExchangeNameForSystem();
		if( !$isOurs ) {
			$parts = explode( '.', $exchangeName );
			if( count( $parts ) == 3 && $parts[0] == 'object' && $parts[2] == 'events' ) {
				$prefixAndId = $parts[1];
				if( strlen( $prefixAndId ) > 1 ) {
					if( $prefixAndId[0] == 'p' || $prefixAndId[0] == 'i' ) {
						$id = substr( $prefixAndId, 1 );
						$isOurs = ctype_digit( $id );
					}
				}
			}
		}
		return $isOurs;
	}

	/**
	 * Composes a regular expression that defines user permissions to the RabbitMQ client queue.
	 *
	 * See also https://www.rabbitmq.com/access-control.html
	 *
	 * @param string $queueName
	 * @return string Permission in regular expression format.
	 */
	public static function composePermissionRegExpression( $queueName )
	{
		return '^'.$queueName.'$';
	}

	/**
	 * Composes the user name to log on to the message queue.
	 *
	 * @param integer|null $userId User DB id. NULL to take the user from the current session.
	 * @return string|null User name. NULL when no active session.
	 * @throws BizException
	 */
	public static function composeSessionUserName( $userId=null )
	{
		$userName = null;
		if( is_null($userId) ) {
			if( BizSession::isStarted() ) {
				$userId = BizSession::getUserInfo( 'id' );
			} else {
				LogHandler::Log( 'MessageQueue', 'WARN', 'Could not compose user name because no active session.' );
			}
		}
		if( $userId ) {
			$userName = BizSession::getEnterpriseSystemId() . '.' . $userId;
		}
		return $userName;
	}

	/**
	 * RabbitMQ users that are automatically created by Enterprise have the following format: <ent_system_id>.<ent_user_db_id>
	 * This function validates a given user name format and returns the Enterprise DB id of the user.
	 *
	 * @param string $userName RabbitMQ user name.
	 * @return integer Enterprise DB id of the user. Zero (0) when user name could not be parsed.
	 */
	public static function getUserDbIdFromUserName( $userName )
	{
		$userParts = explode( '.', $userName );
		return count( $userParts ) == 2 &&
			NumberUtils::validateGUID( $userParts[0] ) &&
			ctype_digit( $userParts[1] ) ? intval( $userParts[1] ) : 0;

	}

	/**
	 * Returns the name of the RabbitMQ message queue to be used by the client for Enterprise messaging.
	 *
	 * @param string $ticket
	 * @return string Exchange name
	 */
	public static function composeMessageQueueName( $ticket )
	{
		return 'ticket.'.$ticket;
	}

	/**
	 * Returns the ticket that was used to compose a given queue name.
	 *
	 * @param string $queueName
	 * @return string|null Ticket
	 */
	public static function deriveTicketFromMesageQueueName( $queueName )
	{
		$ticket = null;
		$prefix = 'ticket.';
		if( substr( $queueName, 0, strlen($prefix ) ) == $prefix ) {
			$ticket = substr( $queueName, strlen($prefix ) );
		}
		return $ticket;
	}

	/**
	 * Returns the type of the RabbitMQ exchange to be used for Enterprise messaging.
	 *
	 * @return string Exchange type
	 */
	public static function getExchangeType()
	{
		return 'fanout';
	}

	/**
	 * Tells whether or not any message queue is configured at all.
	 *
	 * @return bool TRUE when installed, else FALSE.
	 */
	public static function isInstalled()
	{
		$connections = self::unserializeConnections();
		return !empty( $connections );
	}
	
	/**
	 * Picks a connection from the MESSAGE_QUEUE_CONNECTIONS option with matching instance and protocol.
	 *
	 * @param string $instance
	 * @param string $protocol
	 * @return MessageQueueConnection|null The connection found in config. NULL when not found.
	 */
	public static function getConnection( $instance, $protocol )
	{
		$foundConnection = null;
		foreach( self::unserializeConnections() as $connection ) {
			if( $connection->Instance == $instance && $connection->Protocol == $protocol ) {
				self::resolveConnectionProperies( $connection );
				$foundConnection = $connection;
				break;
			}
		}
		return $foundConnection;
	}

	/**
	 * Returns all configured message queue connections.
	 *
	 * @return MessageQueueConnection[]
	 */
	public static function getConnections()
	{
		$connections = self::unserializeConnections();
		foreach( $connections as &$connection ) {
			self::resolveConnectionProperies( $connection );
		}
		return $connections;
	}

	/**
	 * Resolves the User property for a given connection by taking the session user.
	 * And, for RabbitMQ connections, it resolves the VirtualHost by taking the enterprise system id.
	 *
	 * @param MessageQueueConnection $connection The connection to be updated (input/output).
	 */
	private static function resolveConnectionProperies( &$connection )
	{
		if( is_null($connection->User) ) {
			$connection->User = self::composeSessionUserName();
		}
		if( $connection->Instance == 'RabbitMQ' ) {
			if( is_null($connection->VirtualHost) ) {
				$connection->VirtualHost = BizSession::getEnterpriseSystemId();
			}
		}
	}

	/**
	 * Returns the message queue connections as configured in the MESSAGE_QUEUE_CONNECTIONS option of configserver.php.
	 *
	 * @return MessageQueueConnection[]
	 */
	private static function unserializeConnections()
	{
		static $connections = null;
		if( is_null( $connections ) ) {
			if (defined('MESSAGE_QUEUE_CONNECTIONS')) {
				$connections = unserialize(MESSAGE_QUEUE_CONNECTIONS);
			} else {
				$connections = array();
			}
		}
		return $connections;
	}

	/**
	 * Populates the $response->MessageQueueConnections with the configured MESSAGE_QUEUE_CONNECTIONS,
	 * except for the one with Instance = RabbitMQ and Protocol = REST (for security reasons).
	 *
	 * If a configuration found for RabbitMQ REST, it populates the PublicationInfo->MessageQueue and
	 * IssueInfo->MessageQueue with the corresponding exchange names.
	 *
	 * For each Enterprise installation, a virtual host is created in RabbitMQ using the Enterprise System ID.
	 * So this happens only the first time calling.
	 *
	 * To make sure the user can log on to RabbitMQ, it creates or updates the user credentials in RabbitMQ.
	 *
	 * For each brand and overrule issue, a exchange is created (when not exists) in RabbitMQ and the user
	 * is given access to those.
	 *
	 * @param WflLogOnResponse $response
	 * @param integer $userId
	 * @param string $password User's Enterprise password.
	 */
	public static function setupMessageQueueConnectionsForLogOn( WflLogOnResponse $response, $userId, $password )
	{
		// Resolve the connections configured for MESSAGE_QUEUE_CONNECTIONS in configserver.php.
		// Hide the administration REST API of RabbitMQ from clients, because of security reasons.
		/** @var MessageQueueConnection $restApiConnection */
		$restApiConnection = null;
		$connections = unserialize( serialize( self::getConnections() ) );
			// L> Since $connections refers to our cached data and the code below changes properties
			//    in the collection, we make a deep clone to avoid side effects of other classes calling
			//    the getConnections() function hereafter. For deep cloning we use serialize() + unserialize().

		// Get the REST API connection in order to perform operations on RabbitMQ resources.
		if( $connections ) foreach( $connections as $connection ) {
			if( $connection->Instance == 'RabbitMQ' && $connection->Protocol == 'REST' ) {
				$restApiConnection = $connection;
				break;
			}
		}
		if( $restApiConnection && $password ) {
			require_once BASEDIR . '/server/utils/rabbitmq/restapi/Client.class.php';
			$restClient = new WW_Utils_RabbitMQ_RestAPI_Client( $restApiConnection );

			// Create a vhost in RabbitMQ to separate this Enterprise installation from others.
			// Note that this only works when we have access to the root vhost "/".
			if( !$restClient->hasVirtualHost() ) {
				if( $restClient->createVirtualHost() ) {
					// Let's try to give ourselves admin access to the new vhost.
					$adminPerms = new WW_Utils_RabbitMQ_RestAPI_Permissions();
					$adminPerms->setFullAccess();
					$restClient->setUserPermissions( $restApiConnection->User, $adminPerms );
				}
			}

			// Create queue dedicated for the client for which the messages arrive.
			$response->MessageQueue = self::composeMessageQueueName( $response->Ticket );
			$restClient->createQueue( $response->MessageQueue );

			// Create exchange for system events.
			$systemExchange = self::composeExchangeNameForSystem();
			$restClient->createExchange( $systemExchange, self::getExchangeType() );
			$restClient->createBindingBetweenQueueAndExchange( $response->MessageQueue, $systemExchange );

			// For each brand and overrule issue create an exchange in RabbitMQ.
			$restExchanges = $restClient->listExchanges();
			if( $response->Publications ) foreach( $response->Publications as $pubInfo ) {
				$brandHasRegularIssues = false;
				if( $pubInfo->PubChannels ) foreach( $pubInfo->PubChannels as $channelInfo ) {
					if( $channelInfo->Issues ) foreach( $channelInfo->Issues as $issueInfo ) {
						if( $issueInfo->OverrulePublication ) {
							$issueExchange = self::composeExchangeNameForOverruleIssue( $issueInfo->Id );
							if( !array_key_exists( $issueExchange, $restExchanges ) ) {
								$restClient->createExchange( $issueExchange, self::getExchangeType() );
								$restClient->createBindingBetweenQueueAndExchange( $response->MessageQueue, $issueExchange );
							}
						} else {
							// We need to track if a brand has regular issues, because in the situation that it DOESN'T
							// and all issues are overrule issues, we have to check if a user is authorized for the
							// brand of the issues.
							$brandHasRegularIssues = true;
						}
					}
				}

				// Check whether the user has access to the brand. In the case of overrule issues, the brand of the
				// overrule issue is added to the logon response regardless of whether the user is authorized to it.
				// For this case it needs to be verified if the user actually is authorized.
				$authorized = true;
				// The amount of access rights checks should be limited as it could be expensive and overrule issues
				// are an exceptional case in and of itself.
				if( !$brandHasRegularIssues ) {
					require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
					$authorized = BizAccess::isUserAuthorizedForBrandAndIssue( (integer)BizSession::getUserInfo('id'), $pubInfo->Id, 0 );
				}

				$pubExchange = self::composeExchangeNameForPublication( $pubInfo->Id );
				if( $authorized && !array_key_exists( $pubExchange, $restExchanges ) ) {
					$restClient->createExchange( $pubExchange, self::getExchangeType() );
					$restClient->createBindingBetweenQueueAndExchange( $response->MessageQueue, $pubExchange );
				}
			}

			$semaKey = self::getSemaphoreKey( $userId );
			$rmqPassword = self::encryptPassword( $userId, $semaKey );

			// Create or update user in RabbitMQ to make sure client can log on with same password.
			$restUser = new WW_Utils_RabbitMQ_RestAPI_User();
			$restUser->Name = self::composeSessionUserName();
			$restUser->Password = $rmqPassword;
			$restUser->Tags = array(); // normal end-user has no tags (no privileges)
			$restClient->createOrUpdateUser( $restUser );

			// Give user read access to the brand- and overrule issue exchanges in RabbitMQ.
			$userRights = self::composePermissionRegExpression( $response->MessageQueue );
			$restPerms = new WW_Utils_RabbitMQ_RestAPI_Permissions();
			$restPerms->Read = $userRights;
			//$restPerms->Write = $userRights; // queues are used for reading only
			$restPerms->Configure = $userRights; // our RabbitMQ monitor uses STOMPWS which seems to (re)define queues, which requires config rights
			$restClient->setUserPermissions( $restUser->Name, $restPerms );

			// Add all connections to the LogOnResponse.
			foreach( $connections as $connection ) {
				// For security reasons don't reveal the admin REST API connection of RabbitMQ to the outside world.
				if( $connection->Instance == 'RabbitMQ' &&
					( $connection->Protocol == 'AMQP' || $connection->Protocol == 'STOMPWS' ) ) {
					$connection->User = self::composeSessionUserName();
					$connection->Password = $rmqPassword;
					$response->MessageQueueConnections[] = $connection;
				}
			}
		} else {
			// Happens when on re-logon or RabbitMQ connection failure or when no REST client configured.
			// In the last two cases we do not want the client to connect to RabbitMQ as well.
			// In the case of a re-logon, the client is assumed to be logged on to RabbitMQ already.
			$response->MessageQueueConnections = null;
		}
	}

	/**
	 * Publishes a given message to a given exchange.
	 *
	 * It uses the AMQP protocol to publish the message to the RabbitMQ server.
	 *
	 * @param string $exchangeName
	 * @param array $headers
	 * @param array $fields
	 */
	public static function publishMessage( $exchangeName, array $headers, array $fields )
	{
		$client = null;
		$connection = self::getConnection( 'RabbitMQ', 'AMQP' );
		if( $connection ) {
			$map = new BizExceptionSeverityMap( ['S1144' => 'INFO'] );
			try {
				require_once BASEDIR . '/server/utils/rabbitmq/amqp/Client.class.php';
				$client = new WW_Utils_RabbitMQ_AMQP_Client( $connection );
				if( $client->logOn() ) {
					$client->publishMessage( $exchangeName, $headers, $fields );
				}
			} catch( BizException $e ) {
				// don't re-throw since errors are logged and this feature is no thread for production
			}
		} else {
			LogHandler::Log( 'MessageQueue', 'WARN', 'Could not publish message. '.
				'No configuration found in MESSAGE_QUEUE_CONNECTIONS for RabbitMQ over AMQP.' );
		}
		if( $client ) {
			$map = new BizExceptionSeverityMap( ['S1144' => 'INFO'] );
			try {
				$client->logOff();
			} catch( BizException $e ) {}
		}
	}

	/**
	 * For a given list of expired tickets, it removes corresponding queues from RabbitMQ (since they became orphan).
	 *
	 * @param string[] $expiredTickets
	 */
	public static function removeOrphanQueuesByTickets( $expiredTickets )
	{
		if( !$expiredTickets ) {
			return;
		}
		$restClient = self::getRestClient();
		if( !$restClient ) {
			return;
		}
		$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO', 'S1144' => 'INFO' ) );
			// L> Not all clients support RabbitMQ so HTTP 404 / "Record not (S1029)" found may happen.
		foreach( $expiredTickets as $ticket ) {
			$queueName = self::composeMessageQueueName( $ticket );
			try {
				$restClient->deleteQueue( $queueName );
			} catch( BizException $e ) {} // silently continue
		}
	}

	/**
	 * For all expired tickets in Enterprise it removes corresponding queues from RabbitMQ (since they became orphan).
	 */
	public static function removeOrphanQueues()
	{
		$restClient = self::getRestClient();
		if( !$restClient ) {
			return;
		}
		$queues = $restClient->listQueues();
		if( !$queues ) {
			return;
		}
		$expiredTickets = array();
		require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';
		foreach( $queues as $queue ) {
			$ticket = self::deriveTicketFromMesageQueueName( $queue );
			if( $ticket ) {
				$clientName = DBTicket::DBappticket( $ticket );
				if( $clientName === false ) {
					$expiredTickets[] = $ticket;
				}
			}
		}
		if( $expiredTickets ) {
			self::removeOrphanQueuesByTickets( $expiredTickets );
		}
	}

	/**
	 * Creates and caches a new rest client for RabbitMQ. For succeeding calls a cached client is returned.
	 *
	 * @return null|WW_Utils_RabbitMQ_RestAPI_Client
	 */
	private static function getRestClient()
	{
		static $restClient = null;
		if( $restClient ) {
			return $restClient;
		}
		if( !self::isInstalled() ) {
			return null;
		}
		require_once BASEDIR . '/server/utils/rabbitmq/restapi/Client.class.php';
		$restConnection = self::getConnection( 'RabbitMQ', 'REST' );
		if( !$restConnection ) {
			return null;
		}
		try {
			$restClient = new WW_Utils_RabbitMQ_RestAPI_Client( $restConnection );
		} catch( BizException $e ) {
			return null;
		}
		return $restClient;
	}

	/**
	 * RabbitMQ maintains its own semaphores for password encryption.
	 * First we blindly create a semaphore which will fail silently if it already exists, then the key is requested and returned.
	 *
	 * @param integer $userId
	 * @return string The semaphore key.
	 */
	private static function getSemaphoreKey( $userId )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$entityId = "rabbitmq_ticket_{$userId}";
		$semaphore = new BizSemaphore();
		$semaphore->setAttempts( array(1, 2, 5) );
		$semaphore->setLifeTime( 60*60*8 ); //8 hours

		// Do not log any errors as we only want to ensure that a semaphore exists.
		$semaphore->createSemaphore( $entityId, false );

		//Refresh the semaphore in case it already existed.
		BizSemaphore::refreshSemaphoreByEntityId($entityId);

		return BizSemaphore::getKey($entityId);
	}

	/**
	 * Returns a hashed password, taking the userid and semaphore key as base and the Enterprise system id as salt.
	 *
	 * @param integer $userId
	 * @param string $semaKey
	 * @return string|null Returns the hashed password, or NULL on failure.
	 */
	private static function encryptPassword( $userId, $semaKey )
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$hashedPwd = ww_crypt( $userId.$semaKey, BizSession::getEnterpriseSystemId(), true );
		return ($hashedPwd) ? $hashedPwd : null;
	}
}