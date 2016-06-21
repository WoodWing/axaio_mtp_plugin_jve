<?php
/**
 * AMQP client to communicate with RabbitMQ queues.
 *
 * Wraps the classes provided by the PhpAmqpLib library.
 *
 * @package     Enterprise
 * @subpackage  Utils
 * @since       v10.0.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class WW_Utils_RabbitMQ_AMQP_Client
{
	/** @var MessageQueueConnection $connection The connection configuration to the RabbitMQ server. */
	private $connection = null;

	/** @var string[]|null $urlParts Contains parsed URL parts read from $connection->Url. */
	private $urlParts = null;

	/** @var AMQPSSLConnection|AMQPStreamConnection|null $amqpConnection The AMQP connection to the RabbitMQ server.  */
	private $amqpConnection = null;

	/** @var AMQPChannel[]|null $queueChannels List of channels in use, indexed by queue name.  */
	private $queueChannels = array();

	/** @var callable[] $processMessageCallback Callback function to receive messages from the subscribed queue. Indexed by queue name. */
	private $processMessageCallback = array();

	/**
	 * Constructor.
	 *
	 * @param MessageQueueConnection $connection The connection configuration for the RabbitMQ instance and the REST protocol.
	 * @throws BizException Bails out when a connection property is not specified or has bad value.
	 */
	public function __construct( MessageQueueConnection $connection )
	{
		$this->validateString( '__construct', 'connection->Url', $connection->Url );
		$this->validateString( '__construct', 'connection->VirtualHost', $connection->VirtualHost );
		$this->validateString( '__construct', 'connection->User', $connection->User );
		$this->validateString( '__construct', 'connection->Password', $connection->Password );
		$this->validateString( '__construct', 'connection->Protocol', $connection->Protocol );
		$this->validateString( '__construct', 'connection->Instance', $connection->Instance );

		if( $connection->Instance !== 'RabbitMQ' ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'The connection->Instance should be set to RabbitMQ.' );
		}
		if( $connection->Protocol !== 'AMQP' ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'The connection->Protocol should be set to AMQP.' );
		}
		$this->urlParts = parse_url( $connection->Url );
		if( $this->urlParts === false ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Could not parse the connection->Url.' );
		}
		if( $this->urlParts['scheme'] !== 'amqp' && $this->urlParts['scheme'] !== 'amqps' ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
				'Wrong scheme used for connection->Url. Expected amqp:// or amqps://' );
		}

		$this->connection = $connection;
	}

	/**
	 * Connects to the RabbitMQ server via the AMQP protocol.
	 *
	 * Uses the entry point and the credentials as specified in the constructor.
	 *
	 * @return bool Whether not not logged on successfully.
	 * @throws BizException On any error in the connection
	 */
	public function logOn()
	{
		$this->amqpConnection = null;
		try {
			LogHandler::Log( 'MessageQueue', 'DEBUG', 'AMQP connect: ' .
				$this->urlParts['scheme'] . '://' . $this->urlParts['host'] . ':' . $this->urlParts['port'] .
				'@' . $this->connection->User . ' vhost: ' . $this->connection->VirtualHost );
				// L> don't log $this->connection->Password for security reasons
			if( $this->urlParts['scheme'] == 'amqps' ) {
				$sslOptions = array(
					'cafile' => BASEDIR . '/config/encryptkeys/rabbitmq/cacert.pem',
					//'local_cert' => BASEDIR . '/config/encryptkeys/rabbitmq/cert.pem',
					'verify_peer' => true,
					'allow_self_signed' => true
				);
				$this->amqpConnection = new AMQPSSLConnection( $this->urlParts['host'], $this->urlParts['port'],
					$this->connection->User, $this->connection->Password, $this->connection->VirtualHost, $sslOptions );
			} else {
				$this->amqpConnection = new AMQPStreamConnection( $this->urlParts['host'], $this->urlParts['port'],
					$this->connection->User, $this->connection->Password, $this->connection->VirtualHost );
			}
		} catch( Exception $e ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $e->getMessage(), null, ['RabbitMQ'] );
		}
		return (bool)$this->amqpConnection;
	}

	/**
	 * Disconnects from the RabbitMQ server.
	 *
	 * @throws BizException On any error in the connection
	 */
	public function logOff()
	{
		try {
			if( $this->amqpConnection ) {
				$this->amqpConnection->close(); // this implicitly closes the channels too
				$this->amqpConnection = null;
			}
		} catch( Exception $e ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $e->getMessage(), null, ['RabbitMQ'] );
		}
	}

	/**
	 * Whether or not an AMQP connection to RabbitMQ is currently established.
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->amqpConnection ? $this->amqpConnection->isConnected() : false;
	}

	/**
	 * Publishes a message in the given message queue.
	 *
	 * @param string $exchangeName
	 * @param array $headers Event descriptor fields, for client to recognize the message type.
	 * @param array $fields The message itself which consists of fields (key-value pairs).
	 * @throws BizException On any error in the connection
	 */
	public function publishMessage( $exchangeName, array $headers, array $fields ) // exchange
	{
		try {
			$channel = $this->amqpConnection->channel();
			$messageBody = array( 'EventHeaders' => $headers, 'EventData' => $fields );
			$message = new AMQPMessage( json_encode( $messageBody ),
				array(
					'content_type' => 'application/json',
					'expiration' => '3000', // TTL = 3 seconds
					'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT // recover messages after RabbitMQ reboot
				)
			);

			$channel->basic_publish( $message, $exchangeName );

			// Note about RabbitMQ permissions to its resources:
			// - queue_bind() requires:
			//    - read access on the exchange
			//    - write access to the queue
			// - basic_publish() requires:
			//    - write access on the exchange
			// See https://www.rabbitmq.com/access-control.html

		} catch( Exception $e ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $e->getMessage(), null, ['RabbitMQ'] );
		}
	}

	/**
	 * Subscribes to a message queue. When messages arrive, the provided callback function is called.
	 *
	 * @param string $queueName
	 * @param callable $processMessageCallback Callback function should define three parameters: ( array $headers, array $fields, $exchangeName )
	 * @throws BizException On any error in the connection
	 */
	public function subscribeForMessageQueue( $queueName, $processMessageCallback )
	{
		LogHandler::Log( 'MessageQueue', 'DEBUG', 'Subscribing for message queue "'.$queueName.'".' );
		try {
			if( !isset($this->queueChannels[$queueName]) ) {
				$this->queueChannels[$queueName] = $this->amqpConnection->channel();
			}
			$channel = $this->queueChannels[$queueName];

			$this->processMessageCallback[$queueName] = $processMessageCallback;
			$channel->basic_consume( $queueName,
				'',    // consumer tag - Identifier for the consumer, valid within the current channel.
				       //                An arbitrary name given to the consumer. If this field is empty the server will generate a unique tag.
				false, // no local     - The server will not send messages to the connection that published them.
			          //                This is an obscure parameter, if activated, the server will not deliver its own messages.
				true,  // no ack       - Send a proper acknowledgment from the worker, once we're done with a task.
			          //                Will automatically acknowledge that the consumer received the message, so we do not have to manually do so.
				false, // exclusive    - queues may only be accessed by the current connection
				false, // no wait      - The server will not respond to the method. The client should not wait for a reply method.
			          //                If set, the server will not wait for the process in the consumer to complete.
				array( $this, 'subscriberCallback' ) // callback - Method that will receive the message.
			);

			// Note about RabbitMQ permissions to its resources:
			// - queue_bind() requires:
			//    - read access on the exchange
			//    - write access to the queue
			// - basic_consume() requires:
			//    - read access on the queue
			// See https://www.rabbitmq.com/access-control.html

		} catch( Exception $e ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $e->getMessage(), null, ['RabbitMQ'] );
		}
	}

	/**
	 * Called by library when message arrives from subscribed queue. It acknowledges and unpacks the message and
	 * does callback the waiting consumer to process the message data.
	 *
	 * @param AMQPMessage $message
	 */
	public function subscriberCallback( AMQPMessage $message )
	{
		$queueName = null;
		$msgChannelId = $message->delivery_info['channel']->getChannelId();
		/** @var AMQPChannel $channel */
		if( $this->queueChannels ) foreach( $this->queueChannels as $iterQueueName => $channel ) {
			if( $msgChannelId == $channel->getChannelId() ) {
				$queueName = $iterQueueName;
				break;
			}
		}
		$messageBody = json_decode( $message->body, true );
		LogHandler::Log( 'MessageQueue', 'DEBUG', 'Subscriber callback for message queue "'.$queueName.'".' );
		$exchangeName = $message->delivery_info['exchange'];
		call_user_func( $this->processMessageCallback[$queueName], $messageBody['EventHeaders'], $messageBody['EventData'], $exchangeName );
	}

	/**
	 * Checks if there are subscribed messages arrived.
	 *
	 * @param int $timeOut Max time to wait in seconds.
	 * @param string $queueName
	 * @throws BizException On any error in the connection
	 */
	public function waitAndConsumeMessages( $timeOut, $queueName )
	{
		try {
			$channel = $this->queueChannels[$queueName];
			$channel->wait( null, false, $timeOut );
		} catch( Exception $e ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $e->getMessage(), null, ['RabbitMQ'] );
		}
	}

	/**
	 * Validates if a given parameter is a string and not empty.
	 *
	 * @param string $methodName The method that calls this validation function.
	 * @param string $paramName The name of the parameter within the method, minus the $ sign.
	 * @param string $paramValue The value of the parameter to validate.
	 * @throws BizException Thrown when the provided parameter is not valid.
	 */
	private function validateString( $methodName, $paramName, $paramValue ) 
	{
		if( !is_string( $paramValue ) || empty( $paramValue ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
				$methodName . '() got bad value '.print_r($paramValue,true).' for $' . $paramName . ' param.' );
		}
	}
}