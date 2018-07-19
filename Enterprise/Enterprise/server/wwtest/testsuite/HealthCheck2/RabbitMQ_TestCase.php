<?php
/**
 * RabbitMQ TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_RabbitMQ_TestCase extends TestCase
{
	public function getDisplayName() { return 'RabbitMQ'; }
	public function getTestGoals()   { return 'Checks if the RabbitMQ integration is configured correctly.'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 30; }

	/** @var WW_Utils_RabbitMQ_RestAPI_Client $client */
	private $restClient = null;

	/** @var WW_Utils_RabbitMQ_AMQP_Client $client */
	private $amqpClient = null;

	/** @var MessageQueueConnection $amqpConnection */
	private $amqpConnection = null;

	/** @var MessageQueueConnection $restConnection */
	private $restConnection = null;

	/** @var MessageQueueConnection $stompwsConnection */
	private $stompwsConnection = null;

	/** @var string $username */
	private $username = null;

	const PLEASE_CHECK_CONFIGSERVER = 'Please check the MESSAGE_QUEUE_CONNECTIONS option in the configserver.php file. ';
	const PLEASE_CHECK_RABBITMQ_USER_SETUP = 'Please check the user setup on the RabbitMQ admin pages. ';
	const PLEASE_CHECK_RABBITMQ_INSTALLATION = 'Please check the RabbitMQ installation guide. ';
	
	final public function runTest()
	{
		do {
			if( !$this->validateConnectionConfiguration() ) {
				break;
			}
			if( !$this->validateConfiguredUrls() ) {
				break;
			}
			if( !$this->validateOpenSSLVersion() ) {
				break;
			}
			if( !$this->validateSslCertificateFile() ) {
				break;
			}
			if( !$this->testRestClientConnection() ) {
				break;
			}
			if( !$this->validateRabbitMQListeners() ) {
				break;
			}
			if( !$this->testAdminPermissions() ) {
				break;
			}
			if( !$this->testRestClientAliveness() ) {
				break;
			}
			if( !$this->testAmqpClientConnection() ) {
				break;
			}
			$this->cleanupOrphanResourcesInRabbitMQ();
		} while( false ); // no iteration
	}

	/**
	 * Checks whether or not the MESSAGE_QUEUE_CONNECTIONS option in configserver.php contains RabbitMQ configuration
	 * for AMQP, REST API and STOMP over WebSockets. When found, it checks if the individual properties of the
	 * configured connections are set.
	 *
	 * It populates the $this->restConnection, $this->amqpConnection and $this->stompwsConnection class members.
	 *
	 * @return bool TRUE when all ok. FALSE when not installed or when bad config found.
	 */
	private function validateConnectionConfiguration()
	{
		// Validate the MESSAGE_QUEUE_CONNECIONS property configured in configserver.php
		require_once BASEDIR . '/server/bizclasses/BizMessageQueue.class.php';
		if( !BizMessageQueue::isInstalled() ) {
			$this->setResult( 'NOTINSTALLED',
				'The MESSAGE_QUEUE_CONNECTIONS option in configserver.php is not configured.',
				self::PLEASE_CHECK_CONFIGSERVER );
			return false;
		}
		$connections = BizMessageQueue::getConnections();
		$rmqConnections = array();
		foreach( $connections as $connection ) {
			if( $connection->Instance == 'RabbitMQ' ) {
				if( isset($rmqConnections[$connection->Protocol][$connection->Public]) ) {
					$this->setResult( 'ERROR',
						'The RabbitMQ ' . $connection->Protocol . ' configuration is defined more than once. ',
						self::PLEASE_CHECK_CONFIGSERVER.
						'Make sure the following entry is listed only once:<br/>'.$this->composeMessageQueueConnectionInHtml($connection->Protocol) );
					return false;
				}
				$rmqConnections[$connection->Protocol][$connection->Public] = true;
			}
		}
		$this->restConnection = BizMessageQueue::getConnection( 'RabbitMQ', 'REST', false );
		if( !$this->restConnection ) {
			$this->setResult( 'NOTINSTALLED',
				'The RabbitMQ REST configuration is missing. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the following entry exists:<br/>'.$this->composeMessageQueueConnectionInHtml('REST') );
			return false;
		}
		$this->amqpConnection = BizMessageQueue::getConnection( 'RabbitMQ', 'AMQP', false );
		if( !$this->amqpConnection ) {
			$this->setResult( 'NOTINSTALLED',
				'The RabbitMQ AMQP configuration is missing. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the following entry exists:<br/>'.$this->composeMessageQueueConnectionInHtml('AMQP') );
			return false;
		}
		$this->stompwsConnection = BizMessageQueue::getConnection( 'RabbitMQ', 'STOMPWS' );
		if( !$this->stompwsConnection ) {
			$this->setResult( 'NOTINSTALLED',
				'The RabbitMQ STOMPWS configuration is missing. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the following entry exists:<br/>'.$this->composeMessageQueueConnectionInHtml('STOMPWS') );
			return false;
		}
		// Error on properties left empty for the AMQP, REST API and STOMP over WebSocket connections.
		/** @var MessageQueueConnection $connection */
		foreach( array( $this->amqpConnection, $this->restConnection, $this->stompwsConnection ) as $connection ) {
			foreach( array_keys( get_class_vars( 'MessageQueueConnection' ) ) as $property ) {
				if( !isset($connection->$property) ) {
					$this->setResult( 'ERROR',
						'The ' . $property . ' property of the RabbitMQ ' . $connection->Protocol . ' configuration is not set. ',
						self::PLEASE_CHECK_CONFIGSERVER.
						'Make sure the '.$property.' property is set for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml($connection->Protocol) );
				}
			}
		}
		if( $this->hasError() ) {
			return false;
		}
		return true;
	}

	/**
	 * Returns a HTML example of a MessageQueueConnection entry.
	 *
	 * Typically used as TIP to help admin users setting up the RabbitMQ connections in Enterprise.
	 *
	 * @param string $protocol The name of protocol to be filled in for the example.
	 * @return string The composed HTML representation.
	 */
	private function composeMessageQueueConnectionInHtml( $protocol )
	{
		return '<code>new MessageQueueConnection( \'RabbitMQ\', \''.$protocol.'\', '.
			'\'&lt;Url&gt;\', \'&lt;Public&gt;\', \'&lt;User&gt;\', \'&lt;Password&gt;\' ),</code>';
	}

	/**
	 * Validates whether or not the URLs configured for AMQP, REST API and STOMP over WebSockets can be parsed.
	 *
	 * @return bool
	 */
	private function validateConfiguredUrls()
	{
		// Error on bad AMQP URL.
		$this->amqpUrlParts = parse_url( $this->amqpConnection->Url );
		if( $this->amqpUrlParts === false || !isset( $this->amqpUrlParts['scheme'] ) ) {
			$this->setResult( 'ERROR',
				'The Url property of the RabbitMQ AMQP configuration is not valid. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the Url property is valid for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('AMQP').'<br/>'.
				'The Url property should have the following notation: \'<code>amqp[s]://&lt;ip_address_or_host_name&gt;[:&lt;port_nr&gt;]</code>\'. '.
				'Note that the information between [ ] brackets is optional. ');
		} elseif( $this->amqpUrlParts['scheme'] != 'amqp' && $this->amqpUrlParts['scheme'] != 'amqps' ) {
			$this->setResult( 'ERROR',
				'The Url property of the RabbitMQ AMQP configuration is not valid. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the Url property is valid for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('AMQP').'<br/>'.
				'The Url property should have a <code>amqp://</code> or <code>amqps://</code> prefix. ' );
		}
		if( $this->hasError() ) {
			return false;
		}

		// Error on bad REST API URL.
		$this->restUrlParts = parse_url( $this->restConnection->Url );
		if( $this->restUrlParts === false || !isset( $this->restUrlParts['scheme'] ) ) {
			$this->setResult( 'ERROR',
				'The Url property of the RabbitMQ REST configuration is not valid. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the Url property is valid for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('REST').'<br/>'.
				'The Url property should have the following notation: \'<code>http[s]://&lt;ip_address_or_host_name&gt;[:&lt;port_nr&gt;]</code>\''.
				'Note that the information between [ ] brackets is optional. ');
		} elseif( $this->restUrlParts['scheme'] != 'http' && $this->restUrlParts['scheme'] != 'https' ) {
			$this->setResult( 'ERROR',
				'The Url property of the RabbitMQ REST configuration is not valid. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the Url property is valid for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('REST').'<br/>'.
				'The Url property should have a <code>http://</code> or <code>https://</code> prefix. ' );
		}

		// Error on bad STOMP API URL
		$this->stompwsUrlParts = parse_url( $this->stompwsConnection->Url );
		if( $this->stompwsUrlParts === false || !isset( $this->stompwsUrlParts['scheme'])) {
			$this->setResult( 'ERROR',
				'The Url property of the RabbitMQ STOMP configuration is not valid. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the Url property is valid for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('STOMPWS').'<br/>'.
				'The Url property should have the following notation: \'<code>w[s]://&lt;ip_address_or_host_name&gt;[:&lt;port_nr&gt;]/ws</code>\''.
				'Note that the information between [ ] brackets is optional. ');
		} elseif( $this->stompwsUrlParts['scheme'] != 'ws' && $this->stompwsUrlParts['scheme'] != 'wss' ) {
			$this->setResult( 'ERROR',
				'The Url property of the RabbitMQ STOMP configuration is not valid. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'Make sure the Url property is valid for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('STOMPWS').'<br/>'.
				'The Url property should have a <code>ws://</code> or <code>wss://</code> prefix. ' );
		}
		if( $this->hasError() ) {
			return false;
		}

		return true;
	}

	/**
	 * If SSL connections are configured, we want to ensure that the correct OpenSSL version is installed.
	 * There are compatibility issues when using an OpenSSL version older than 1.0.1.
	 *
	 * @return bool TRUE version is compatible. FALSE when version is too old.
	 */
	private function validateOpenSSLVersion() {
		// Source: http://stackoverflow.com/questions/9693614/how-to-check-if-installed-openssl-version-is-0-9-8k
		// OpenSSL version matrix: http://blog.techstacks.com/2013/04/an-openssl-version-matrix.html
		if( $this->amqpUrlParts['scheme'] == 'amqps' || // AMQP over SSL
			$this->restUrlParts['scheme'] == 'https' || // REST over SSL
			$this->stompwsUrlParts['scheme'] == 'wss' ) { // STOMP over WebSockets over SSL) )
			if( OPENSSL_VERSION_NUMBER < 0x1000100f ) { // OpenSSL 1.0.1
				$this->setResult('ERROR', 'The OpenSSL version should be 1.0.1 or higher. '.
					'Please install a newer version of OpenSSL in order to use RabbitMQ.');
				return false;
			}
		}
		return true;
	}

	/**
	 * Validates the presence of the local RabbitMQ certificate file (when SSL is enabled for AMQP, REST API or STOMP over WebSockets).
	 *
	 * @return bool
	 */
	private function validateSslCertificateFile()
	{
		$localCert = BASEDIR.'/config/encryptkeys/rabbitmq/cacert.pem'; // for HTTPS / SSL only

		// Check presence of local certificate file when SSL enabled for AMQP.
		if( $this->amqpUrlParts['scheme'] == 'amqps' ) {
			if( !file_exists($localCert) ) {
				$this->setResult( 'ERROR',
					'SSL seems to be enabled for the RabbitMQ AMQP configuration. '.
					'However, the SSL certificate file "'.$localCert.'" cannot be found. ',
					'Please check file presence and access rights for the internet user. '.
					'Note that SSL is enabled for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('AMQP').'<br/>'.
					self::PLEASE_CHECK_CONFIGSERVER );
				return false;
			}
		}

		// Check presence of local certificate file when SSL enabled for REST API.
		if( $this->restUrlParts['scheme'] == 'https' ) {
			if( !file_exists($localCert) ) {
				$this->setResult( 'ERROR',
					'SSL seems to be enabled for the RabbitMQ REST configuration. '.
					'However, the SSL certificate file "'.$localCert.'" cannot be found. ',
					'Please check if the file is present and has access rights for the Internet user. '.
					'Note that SSL is enabled for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('REST').'<br/>'.
					self::PLEASE_CHECK_CONFIGSERVER );
				return false;
			}
		}

		// Check presence of local certificate file when SSL enabled for STOMPWS.
		if( $this->stompwsUrlParts['scheme'] == 'wss' ) {
			if( !file_exists($localCert) ) {
				$this->setResult( 'ERROR',
					'SSL seems to be enabled for the RabbitMQ STOMPWS configuration. '.
					'However, the SSL certificate file "'.$localCert.'" cannot be found. ',
					'Please check if the file is present and has access rights for the Internet user. '.
					'Note that SSL is enabled for the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('STOMPWS').'<br/>'.
					self::PLEASE_CHECK_CONFIGSERVER );
				return false;
			}
		}

		return true;
	}

	/**
	 * Creates a REST API client and tries to connect to RabbitMQ and does a aliveness test.
	 * It retrieves the RabbitMQ version and errors when not compatible with Enterprise Server.
	 * When the virtual host is missing, this function tries to create one (auto repair).
	 *
	 * @return bool
	 */
	private function testRestClientConnection()
	{
		try {
			require_once BASEDIR.'/server/utils/rabbitmq/restapi/Client.class.php';
			$this->restClient = new WW_Utils_RabbitMQ_RestAPI_Client( $this->restConnection );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage().' '.$e->getDetail() ); // should not happen (all validated before)
			return false;
		}
		$curlErrNr = 0;
		$curlErrMsg = '';
		if( !$this->restClient->checkConnection( $curlErrNr, $curlErrMsg ) ) {
			$this->setResult( 'ERROR',
				'Could not connect through RabbitMQ REST API using "'.$this->restConnection->Url.'". '.
				$curlErrMsg.' (cURL error '.$curlErrNr.'). ',
				'Please check the RabbitMQ installation. '.
				'Note that the Url is taken from the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml( 'REST' ).'<br/>'.
				self::PLEASE_CHECK_CONFIGSERVER );
			return false;
		}
		try {
			$rmqVersion = $this->restClient->getVersion();
			$detectedVersionParts = explode( '.', $rmqVersion );
			$detectedVersion = $detectedVersionParts[0].'.'.$detectedVersionParts[1]; // keep major.minor only, remove the 3rd digit (patch level).
			$supportedVersions = $this->supportedVersions();
			$versionSupported = false;
			foreach( $supportedVersions as $supportedVersion ) {
				if( version_compare( $detectedVersion, $supportedVersion ) == 0 ) { // expected exact match
					$versionSupported = true;
					break;
				}
			}
			if( !$versionSupported ) {
				$supportedVersionsString = $this->supportedVersionsAsString();
				$this->setResult( 'ERROR',
					'Detected RabbitMQ '.$detectedVersion.' which is not supported by Enterprise. '.
					'Enterprise integrates with RabbitMQ '.$supportedVersionsString.' only. '.
					'Please check the RabbitMQ installation. ' );
				return false;
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not detect version of RabbitMQ. '.$e->getMessage().' '.$e->getDetail() );
			return false;
		}
		try {
			if( !$this->restClient->hasVirtualHost() ) {
				$this->restClient->createVirtualHost();
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not create virtual host in RabbitMQ. '.$e->getMessage().' '.$e->getDetail() );
			return false;
		}
		return true;
	}

	/**
	 * Compares the listeners as configured on RabbitMQ with the MESSAGEQUEUE_CONNECTIONS set in configserver.php.
	 * When the relevant ports do not match, or when a protocol is not enabled on RabbitMQ while it is configured
	 * in Enterprise, an error will be thrown.
	 *
	 * @return bool
	 */
	private function validateRabbitMQListeners()
	{
		try {
			require_once BASEDIR . '/server/utils/rabbitmq/restapi/Client.class.php';
			$this->restClient = new WW_Utils_RabbitMQ_RestAPI_Client( $this->restConnection );
			$rmqListeners = $this->restClient->getListeners();
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage() . ' ' . $e->getDetail() ); // should not happen (all validated before)
			return false;
		}

		// Restructure array for easier lookup
		$listeners = array();
		if( $rmqListeners ) foreach( $rmqListeners as $listener ) {
			$listeners[$listener->protocol] = $listener;
		}

		// Verify the AMQP Connection
		if( $this->amqpUrlParts['scheme'] == 'amqp' ) {
			if( !isset( $listeners['amqp'] ) ) {
				$this->setResult( 'ERROR',
					'The AMQP protocol has not been enabled on your RabbitMQ installation.',
					self::PLEASE_CHECK_RABBITMQ_INSTALLATION );
				return false;
			}
			$amqpListener = $listeners['amqp'];
		} elseif( $this->amqpUrlParts['scheme'] == 'amqps' ) {
			if( !isset( $listeners['amqp/ssl'] ) ) {
				$this->setResult( 'ERROR',
					'The AMQP SSL protocol has not been enabled in your RabbitMQ installation. '.
					'However, SSL is enabled in the Enterprise configuration. ',
					self::PLEASE_CHECK_RABBITMQ_INSTALLATION );
				return false;
			}
			$amqpListener = $listeners['amqp/ssl'];
		}
		// Note that, at this point, $amqpListener always exists. If it would not exist, that would
		// mean the amqpUrlPart validation already should have thrown an error.
		/** @noinspection PhpUndefinedVariableInspection */
		if( $amqpListener->port != $this->amqpUrlParts['port'] ) {
			$this->setResult( 'ERROR',
				'The configured port for your AMQP configuration does not match the actual port on RabbitMQ. ',
				self::PLEASE_CHECK_CONFIGSERVER.
				'The AMQP port on your RabbitMQ server is "'.$amqpListener->port.'" while the configured port is "'.$this->amqpUrlParts['port'].'".');
			return false;
		}

		// Verify the STOMPWS connection
		// Validation can only be done on the general STOMP protocol, since Stomp over WebSockets does
		// not configure a listener in RabbitMQ.
		if( $this->stompwsUrlParts['scheme'] == 'ws' ) {
			if( !isset( $listeners['stomp'] ) ) {
				$this->setResult( 'ERROR',
					'The STOMP protocol has not been enabled in your RabbitMQ installation. '.
					'This protocol is needed for the STOMPWS configuration.',
					self::PLEASE_CHECK_RABBITMQ_INSTALLATION );
				return false;
			}
		} elseif( $this->stompwsUrlParts['scheme'] == 'wss' ) {
			if( !isset( $listeners['stomp/ssl'] ) ) {
				$this->setResult( 'ERROR',
					'The STOMP SSL protocol has not been enabled in your RabbitMQ installation. '.
					'This protocol is needed for the STOMPWS configuration.'.
					'However, SSL is enabled in the Enterprise configuration. ',
					self::PLEASE_CHECK_RABBITMQ_INSTALLATION );
				return false;
			}
		}
		return true;
	}

	/**
	 * Creates a AMQP client and tries to connect to RabbitMQ and does logOn and logOff operations.
	 *
	 * @return bool
	 */
	private function testAmqpClientConnection()
	{
		try {
			require_once BASEDIR . '/server/utils/rabbitmq/amqp/Client.class.php';
			$this->amqpClient = new WW_Utils_RabbitMQ_AMQP_Client( $this->amqpConnection );
			$this->amqpClient->logOn();
			$this->amqpClient->logOff();
		} catch( BizException $e ) {
			$this->setResult( 'ERROR',
				'Could not connect through RabbitMQ AMQP using "' . $this->amqpConnection->Url . '". ' .
				$e->getMessage().' '.$e->getDetail(),
				'Please check the RabbitMQ installation. ' .
				'Note that the Url is taken from the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('AMQP').'<br/>'.
				self::PLEASE_CHECK_CONFIGSERVER );
			return false;
		}
		$this->amqpClient->logOff();
		return true;
	}

	/**
	 * Checks whether or not the configured user for the REST API connection is an administrator and has read/write/config rights.
	 *
	 * @return bool
	 */
	private function testAdminPermissions()
	{
		try {
			$restUser = $this->restClient->getUser( $this->restConnection->User );
			if( !$restUser->Tags || !in_array( 'administrator', $restUser->Tags ) ) {
				$this->setResult( 'ERROR',
					'RabbitMQ user "' . $restUser->Name . '" has no administration permissions.',
					self::PLEASE_CHECK_RABBITMQ_USER_SETUP.
					'Note that the User is taken from the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('REST').'<br/>'.
					self::PLEASE_CHECK_CONFIGSERVER );
				return false;
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not find RabbitMQ user "' . $this->restConnection->User . '"".' );
			return false;
		}

		try {
			$permissions = $this->restClient->getUserPermissions( $this->restConnection->User );
			if( !$permissions ) {
				$this->setResult( 'ERROR',
					'RabbitMQ user "' . $restUser->Name . '" has no permissions.',
					self::PLEASE_CHECK_RABBITMQ_USER_SETUP.
					'Note that the User is taken from the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('REST').'<br/>'.
					self::PLEASE_CHECK_CONFIGSERVER );
				return false;
			}
			$hasRights = false;
			$entSystemId = BizSession::getEnterpriseSystemId();
			if( $permissions ) foreach( $permissions as $permission ) {
				if( $permission->VirtualHost == $entSystemId ) {
					$hasRights = $permission->hasFullAccess();
					break;
				}
			}
			if( !$hasRights ) { // try to auto repair
				$permission = new WW_Utils_RabbitMQ_RestAPI_Permissions();
				$permission->setFullAccess();
				$this->restClient->setUserPermissions( $this->restConnection->User, $permission );
				$hasRights = true;
			}
			if( !$hasRights ) {
				$this->setResult( 'ERROR',
					'RabbitMQ user "' . $this->restConnection->User . '" has no full read/write/config permissions '.
					'to Virtual Host "'.$entSystemId.'".',
					self::PLEASE_CHECK_RABBITMQ_USER_SETUP.
					'Note that the User is taken from the following entry:<br/>'.$this->composeMessageQueueConnectionInHtml('REST').'<br/>'.
					self::PLEASE_CHECK_CONFIGSERVER );
				return false;
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not determine permissions for RabbitMQ user "' . $this->restConnection->User . '".' );
			return false;
		}
		return true;
	}

	/**
	 * Performs aliveness test in RabbitMQ; Declares a test queue, then publishes and consumes a message.
	 *
	 * @return bool
	 */
	private function testRestClientAliveness()
	{
		if( !$this->restClient->testAliveness() ) {
			$this->setResult( 'ERROR',
				'The RabbitMQ implementation did not pass the aliveness test. '.
				'Please verify your RabbitMQ installation and configuration. ' );
			return false;
		}
		try {
			$this->restClient->deleteQueue( 'aliveness-test' );
		} catch( BizException $e ) {
			$this->setResult( 'WARN', 'Could not remove the "aliveness-test" queue from RabbitMQ.' );
		}
		return true;
	}

	/**
	 * Removes exchanges, queues and users from RabbitMQ for which no longer references exist in Enterprise.
	 */
	private function cleanupOrphanResourcesInRabbitMQ()
	{
		// Create an Enterprise session for the user defined by TESTSUITE setting in configserver.php.
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
		if( $suiteOpts ) {
			$logOnResponse = $this->logOn( $suiteOpts['User'], $suiteOpts['Password'] ); // Creates a RabbitMQ testsuite user.
			if( $logOnResponse ) {
				$this->username = $suiteOpts['User'];
				try {
					BizSession::checkTicket( $logOnResponse->Ticket );
					$this->cleanExchanges( $logOnResponse );
					$this->cleanUsers();
					$this->cleanQueues();
				} catch( BizException $e ) {
					$this->setResult( 'ERROR',
						'Could not cleanup users and queues in RabbitMQ. '.$e->getMessage().' '.$e->getDetail() );
				}
				try {
					BizSession::endSession();
					$this->logOff( $logOnResponse->Ticket );
				} catch( BizException $e ) {}
			}
		} else {
			$this->setResult( 'ERROR', 'No TESTSUITE option defined in the configserver.php file.' );
		}
	}

	/**
	 * Logon with user and password.
	 *
	 * @param string $user
	 * @param string $password
	 * @return WflLogOnResponse on success. NULL on error
	 */
	private function logOn( $user, $password )
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
			$request = new WflLogOnRequest();
			$request->User          = $user;
			$request->Password      = $password;
			$request->Ticket = '';
			$request->Server = 'Enterprise Server';
			$request->Domain = '';
			$request->ClientAppName = 'RabbitMQHealthCheck';
			$request->ClientAppVersion = 'v' . SERVERVERSION;
			$request->ClientAppSerial = '';
			$request->ClientAppProductKey = '';
			$request->RequestInfo = array('Publications', 'MessageQueueConnections');

			require_once BASEDIR.'/server/utils/UrlUtils.php';
			$clientip = WW_Utils_UrlUtils::getClientIP();
			$request->ClientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
			// >>> BZ#6359 Let's use ip since gethostbyaddr could be extremely expensive! which also risks throwing "Maximum execution time of 11 seconds exceeded"
			if( empty($request->ClientName) ) { $request->ClientName = $clientip; }

			$service = new WflLogOnService();
			$response = $service->execute($request);
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'Failed to log in to check the RabbitMQ configuration',
				'Please check the TESTSUITE setting in configserver.php if the test user and password are set correctly.' );
			return null;
		}
		return $response;
	}

	/**
	 * Logoff a user by given ticket.
	 * 
	 * @param $ticket
	 */
	private function logOff( $ticket )
	{
		try {
			require_once BASEDIR . '/server/services/wfl/WflLogOffService.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = $ticket;
			$service = new WflLogOffService();
			$response = $service->execute ($request );
		} catch ( BizException $e ) {
		}
	}

	/**
	 * Clean up the exchanges in RabbitMQ.
	 *
	 * Deletes all exchanges from RabbitMQ (within this Virtual Host) for which no publication or overrule issue
	 * exist anymore in Enterprise.
	 *
	 * @param WflLogOnResponse $logOnResponse
	 */
	private function cleanExchanges( WflLogOnResponse $logOnResponse )
	{
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		try {
			$rmqExchanges = $this->restClient->listExchanges();
			if( $rmqExchanges ) {
				if( LogHandler::debugMode() ) {
					LogHandler::Log( 'HealthCheck_RabbitMQ', 'DEBUG', 'List of exchanges found: ' . print_r( $rmqExchanges, true ) );
				}
				
				$entExchanges = array();
				if( $logOnResponse->Publications ) foreach( $logOnResponse->Publications as $publication ) {
					$entExchanges[] = BizMessageQueue::composeExchangeNameForPublication( $publication->Id );
					if( $publication->PubChannels ) foreach( $publication->PubChannels as $pubChannel ) {
						if( $pubChannel->Issues ) foreach( $pubChannel->Issues as $issue ) {
							if( $issue->OverrulePublication ) {
								$entExchanges[] = BizMessageQueue::composeExchangeNameForOverruleIssue( $issue->Id );
							}
						}
					}
				}
				if( $entExchanges ) { // when no exchanges in response, we suspect something went wrong, and so we don't clean
					$entExchanges[] = BizMessageQueue::composeExchangeNameForSystem();
					$entExchanges = array_flip( $entExchanges ); // prepare for fast lookup
					foreach( $rmqExchanges as $rmqExchange ) {
						if( !array_key_exists( $rmqExchange, $entExchanges ) &&
							BizMessageQueue::isOurExchange( $rmqExchange )  ) {
							$this->restClient->deleteExchange( $rmqExchange );
						}
					}
				}
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not clean exchanges in RabbitMQ. '.$e->getMessage().' '.$e->getDetail() );
		}
	}

	/**
	 * Clean up the users in RabbitMQ.
	 *
	 * Deletes all users from RabbitMQ (within this Virtual Host only) that do not exist anymore in Enterprise.
	 * For security reasons, that also includes users who are Deactivated in Enterprise.
	 */
	private function cleanUsers()
	{
		try {
			$permissions = $this->restClient->listPermissions();
			if( $permissions ) {
				$rmqUsers = array_keys( $permissions );
				if( $rmqUsers ) {
					if( LogHandler::debugMode() ) {
						LogHandler::Log( 'HealthCheck_RabbitMQ', 'DEBUG', 'List of RabbitMQ users found: ' . print_r( $rmqUsers, true ) );
					}

					require_once BASEDIR . '/server/bizclasses/BizAdmUser.class.php';
					require_once BASEDIR . '/server/bizclasses/BizMessageQueue.class.php';
					$entUserObjs = BizAdmUser::listUsersObj( $this->username, null, null, null );
					$entUsers = array();
					if( $entUserObjs ) foreach( $entUserObjs as $entUserObj ) {
						if( !$entUserObj->Deactivated ) { // security check, see function header
							$entUsers[BizMessageQueue::composeSessionUserName( $entUserObj->Id )] = true;
						}
					}

					// Enterprise Server will auto clean RabbitMQ users, but as a safety fallback, we should keep this
					// check here in case any of them are missed.
					foreach( $rmqUsers as $rmqUser ) {
						if( !array_key_exists( $rmqUser, $entUsers ) &&
							BizMessageQueue::getUserDbIdFromUserName( $rmqUser ) &&
							$rmqUser != $this->restConnection->User ) {
							LogHandler::Log( 'HealthCheck_RabbitMQ', 'DEBUG', 'Deleting RabbitMQ user "' . $rmqUser . '".' );
							$this->restClient->deleteUser( $rmqUser );
						}
					}
				}
			}
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not clean users in RabbitMQ. '.$e->getMessage().' '.$e->getDetail() );
		}
	}

	/**
	 * Clean up the users in RabbitMQ.
	 *
	 * Deletes all users from RabbitMQ (within this Virtual Host only) for which not ticket exist anymore in Enterprise.
	 */
	private function cleanQueues()
	{
		require_once BASEDIR . '/server/bizclasses/BizMessageQueue.class.php';
		BizMessageQueue::removeOrphanQueues();
	}

	/**
	 * Returns the support RabbitMQ versions.
	 *
	 * @since 10.1.8
	 * @return string[]
	 */
	private function supportedVersions()
	{
		return array( '3.7' );
	}

	/**
	 * Returns the supported versions as a string. String can be used in messages.
	 *
	 * @since 10.1.8
	 * @return string
	 */
	private function supportedVersionsAsString()
	{
		$supportVersions = $this->supportedVersions();
		return implode( ' or ', $supportVersions );
	}
}