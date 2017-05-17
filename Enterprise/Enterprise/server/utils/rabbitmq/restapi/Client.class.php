<?php
/**
 * HTTP client for RESTful communication with RabbitMQ admin services.
 *
 * Wraps a curl based adapter within a Zend http client.
 *
 * @package     Enterprise
 * @subpackage  Utils
 * @since       v10.0.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/rabbitmq/restapi/User.class.php';
require_once BASEDIR.'/server/utils/rabbitmq/restapi/Permissions.class.php';

class WW_Utils_RabbitMQ_RestAPI_Client
{
	/** @var MessageQueueConnection $connection The connection configuration to the RabbitMQ server. */
	private $connection = null;

	/** @var array $apiOverviewResults The cached results of the /api/overview REST API call. */
	private $apiOverviewResults = null;

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
		if( $connection->Protocol !== 'REST' ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'The connection->Protocol should be set to REST.' );
		}

		$this->connection = $connection;
	}

	/**
	 * Composes the full REST API query URL by combining the RabbitMQ entry point (connection URL) with a given query path.
	 *
	 * @param string $path Relative query path
	 * @return string Full REST API query URL.
	 */
	private function composeUrl( $path )
	{
		return $this->connection->Url . $path;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Queue management API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Retrieves all queues configured in RabbitMQ.
	 *
	 * @return string[] List of queue names.
	 */
	public function listQueues()
	{
		// GET /api/queues/{vhost}
		$path = $this->composeUrl( '/api/queues/'.$this->connection->VirtualHost );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
		$queues = json_decode( $httpBody );

		$queueNames = array();
		if( $queues ) foreach( $queues as $queue ) {
			$queueNames[] = $queue->name;
		}
		return $queueNames;
	}

	/**
	 * Configures a queue in RabbitMQ.
	 *
	 * @param string $queueName
	 * @param bool $passive
	 * @param bool $durable
	 * @param bool $exclusive
	 * @param bool $autoDelete
	 * @param bool $nowait
	 * @param string[] $arguments
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function createQueue( $queueName, $passive = false, $durable = true, $exclusive = false, $autoDelete = false,
	                             $nowait = false, $arguments = array()  )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'queueName', $queueName );
		$this->validateBoolean( __FUNCTION__, 'passive', $passive );
		$this->validateBoolean( __FUNCTION__, 'durable', $durable );
		$this->validateBoolean( __FUNCTION__, 'exclusive', $exclusive );
		$this->validateBoolean( __FUNCTION__, 'autoDelete', $autoDelete );
		$this->validateBoolean( __FUNCTION__, 'nowait', $nowait );

		// PUT /api/queues/{vhost}/{name}
		$requestProps = array(
			'passive' => $passive,
			'durable' => $durable,
			'exclusive' => $exclusive,
			'auto_delete' => $autoDelete,
			'nowait' => $nowait,
			'arguments' => $arguments
		);
		$path = $this->composeUrl( '/api/queues/'.$this->connection->VirtualHost.'/'.$queueName );
		$headers = array( 'Content-Type' => 'application/json' );
		$request = $this->composeRequest( $path, json_encode($requestProps), Zend\Http\Request::METHOD_PUT, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	/**
	 * Removes a queue configured in RabbitMQ.
	 *
	 * @param string $queueName
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function deleteQueue( $queueName )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'queueName', $queueName );

		// DELETE /api/queues/{vhost}/{name}
		$path = $this->composeUrl( '/api/queues/'.$this->connection->VirtualHost.'/'.$queueName );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_DELETE );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Exchange management API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Retrieves all exchanges configured in RabbitMQ.
	 *
	 * @return string[] List of exchange names.
	 */
	public function listExchanges()
	{
		// GET /api/exchanges/{vhost}
		$path = $this->composeUrl( '/api/exchanges/'.$this->connection->VirtualHost );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
		$exchanges = json_decode( $httpBody );

		$exchangeNames = array();
		if( $exchanges ) foreach( $exchanges as $exchange ) {
			$exchangeNames[] = $exchange->name;
		}
		return $exchangeNames;
	}

	/**
	 * Configures an exchange in RabbitMQ.
	 *
	 * @param string $exchangeName
	 * @param string $exchangeType
	 * @param bool $passive
	 * @param bool $durable
	 * @param bool $autoDelete
	 * @param bool $internal
	 * @param bool $nowait
	 * @param string[] $arguments
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function createExchange( $exchangeName, $exchangeType, $passive = false, $durable = false, $autoDelete = false,
	                                $internal = false, $nowait = false, $arguments = array() )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'exchangeName', $exchangeName );
		$this->validateString( __FUNCTION__, 'exchangeType', $exchangeType );
		$this->validateBoolean( __FUNCTION__, 'passive', $passive );
		$this->validateBoolean( __FUNCTION__, 'durable', $durable );
		$this->validateBoolean( __FUNCTION__, 'autoDelete', $autoDelete );
		$this->validateBoolean( __FUNCTION__, 'internal', $internal );
		$this->validateBoolean( __FUNCTION__, 'nowait', $nowait );

		// PUT /api/exchanges/{vhost}/{name}
		$requestProps = array(
			'type' => $exchangeType,
			'passive' => $passive,
			'durable' => $durable,
			'auto_delete' => $autoDelete,
			'internal' => $internal,
			'nowait' => $nowait,
			'arguments' => $arguments
		);
		$path = $this->composeUrl( '/api/exchanges/'.$this->connection->VirtualHost.'/'.$exchangeName );
		$headers = array( 'Content-Type' => 'application/json' );
		$request = $this->composeRequest( $path, json_encode($requestProps), Zend\Http\Request::METHOD_PUT, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	/**
	 * Removes an exchange configured in RabbitMQ.
	 *
	 * @param string $exchangeName
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function deleteExchange( $exchangeName )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'exchangeName', $exchangeName );

		// DELETE /api/exchanges/{vhost}/{name}
		$path = $this->composeUrl( '/api/exchanges/'.$this->connection->VirtualHost.'/'.$exchangeName );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_DELETE );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Binding management API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Creates a binding between a given queue and an exchange.
	 *
	 * @param string $queueName
	 * @param string $exchangeName
	 * @param string $routingKey Optional
	 * @param string[] $arguments
	 * @throws BizException
	 */
	public function createBindingBetweenQueueAndExchange( $queueName, $exchangeName, $routingKey = '', $arguments = array() )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'queueName', $queueName );
		$this->validateString( __FUNCTION__, 'exchangeName', $exchangeName );

		// POST /api/bindings/{vhost}/e/{exchangeName}/q/{queueName}
		$requestProps = array(
			'routing_key' => $routingKey,
			"arguments" => $arguments
		);
		$path = $this->composeUrl( '/api/bindings/'.$this->connection->VirtualHost.'/e/'.$exchangeName.'/q/'.$queueName );
		$headers = array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, json_encode($requestProps), Zend\Http\Request::METHOD_POST, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// User management API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 


	// Note that a listUsers() function would return all users of other Enterprise 
	// installations as well, which is far too much and not secure. 
	// So we have implemented listPermissions() instead, which works on current vhost only.

	/**
	 * Retrieves a user configured in RabbitMQ.
	 *
	 * @param string $userName
	 * @return WW_Utils_RabbitMQ_RestAPI_User
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function getUser( $userName )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'userName', $userName );
		
		// GET /api/users/{name}
		$path = $this->composeUrl( '/api/users/'.$userName );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );

		// Compose user data class.
		$user = new WW_Utils_RabbitMQ_RestAPI_User();
		$user->setJson( json_decode( $httpBody ) ); // {"name":"john","password_hash":"..","tags":""}
		return $user;
	}

	/**
	 * Configures a user in RabbitMQ.
	 *
	 * @param WW_Utils_RabbitMQ_RestAPI_User $user
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function createOrUpdateUser( WW_Utils_RabbitMQ_RestAPI_User $user )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'user->Name', $user->Name );
		$this->validateString( __FUNCTION__, 'user->Password', $user->Password );
		$this->validateArrayOfString( __FUNCTION__, 'user->Tags', $user->Tags );
		
		// PUT /api/users/{name}
		$path = $this->composeUrl( '/api/users/'.$user->Name );
		$headers = array( 'Content-Type' => 'application/json' );
		$request = $this->composeRequest( $path, json_encode( $user->getJson() ), Zend\Http\Request::METHOD_PUT, $headers );
		
		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	/**
	 * Removes a user configured in RabbitMQ.
	 *
	 * @param string $userName
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function deleteUser( $userName )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'userName', $userName );

		// DELETE /api/users/{name}
		$path = $this->composeUrl( '/api/users/'.$userName );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_DELETE );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Permission management API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Retrieves the user permissions configured in RabbitMQ (for all users that have access to the vhost).
	 *
	 * @return WW_Utils_RabbitMQ_RestAPI_Permissions[] List of permissions indexed by user names.
	 */
	public function listPermissions()
	{
		// GET /api/vhosts/{name}/permissions
		$path = $this->composeUrl( '/api/vhosts/'.$this->connection->VirtualHost.'/permissions' );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );

		// Compose the permissions to return caller.
		$permissions = array();
		$mqPermissions = json_decode( $httpBody ); // {"user":"john","vhost":"...","configure":".*","write":".*","read":".*"}
		if( $mqPermissions ) foreach( $mqPermissions as $mqPermission ) {
			$permission = new WW_Utils_RabbitMQ_RestAPI_Permissions();
			$permission->setJson( $mqPermission );
			$permissions[$mqPermission->user] = $permission;
		}
		return $permissions;
	}

	/**
	 * Retrieves the permissions configured in RabbitMQ for a given user.
	 *
	 * @param string $userName
	 * @return WW_Utils_RabbitMQ_RestAPI_Permissions[]
	 */
	public function getUserPermissions( $userName )
	{
		// GET api/users/{user}/permissions
		$path = $this->composeUrl( '/api/users/'.$userName.'/permissions' );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
		$jsonPermissions = json_decode( $httpBody );

		// Compose permissions to return to caller.
		$permissions = array();
		if( $jsonPermissions ) foreach( $jsonPermissions as $jsonPermission ) {
			$permission = new WW_Utils_RabbitMQ_RestAPI_Permissions();
			$permission->setJson( $jsonPermission ); // {"configure":".*","write":".*","read":".*"}
			$permissions[] = $permission;
		}
		return $permissions;
	}

	/**
	 * Configures the permissions for a user in RabbitMQ.
	 *
	 * @param string $userName
	 * @param WW_Utils_RabbitMQ_RestAPI_Permissions $permissions
	 * @throws BizException Bails out when given params are not valid or when HTTP error raises.
	 */
	public function setUserPermissions( $userName, WW_Utils_RabbitMQ_RestAPI_Permissions $permissions )
	{
		// Validate given params.
		$this->validateString( __FUNCTION__, 'permissions->Read', $permissions->Read );
		$this->validateString( __FUNCTION__, 'permissions->Write', $permissions->Write );
		$this->validateString( __FUNCTION__, 'permissions->Configure', $permissions->Configure );

		// PUT /api/permissions/{vhost}/{user}
		$permissions->VirtualHost = $this->connection->VirtualHost;
		$path = $this->composeUrl( '/api/permissions/'.$this->connection->VirtualHost.'/'.$userName );
		$headers = array( 'Content-Type' => 'application/json' );
		$request = $this->composeRequest( $path, json_encode( $permissions->getJson() ), Zend\Http\Request::METHOD_PUT, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Virtual host management API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Tells whether or not the virtual host is configured in RabbitMQ.
	 *
	 * @return bool
	 * @throws BizException
	 */
	public function hasVirtualHost()
	{
		// GET /api/vhosts/{name}
		$path = $this->composeUrl( '/api/vhosts/'.$this->connection->VirtualHost );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO') );
		try {
			$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
		} catch( BizException $e ) {
			if( $e->getErrorCode() == 'S1029' ) { // ERR_NOTFOUND, happens on HTTP 404 (Not Found) or HTTP 410 (Gone)
				LogHandler::Log( 'RabbitMQ', 'INFO', "The virtual host '{$this->connection->VirtualHost}'' was not found in RabbitMQ." );
			} else {
				throw $e; // unexpected, re-throw
			}
		}
		return isset($httpBody) && json_decode( $httpBody ) !== false; // {"tracing":true}
	}

	/**
	 * Configures a virtual host in RabbitMQ.
	 *
	 * @param bool $tracing
	 * @return bool Whether or not the creation was successful.
	 */
	public function createVirtualHost( $tracing = true )
	{
		// PUT /api/vhosts/{name}
		$requestProps = array(
			'tracing' => $tracing,
		);
		$path = $this->composeUrl( '/api/vhosts/'.$this->connection->VirtualHost );
		$headers = array( 'Content-Type' => 'application/json' );
		$request = $this->composeRequest( $path, json_encode( $requestProps ), Zend\Http\Request::METHOD_PUT, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
		return isset($httpBody) && json_decode( $httpBody ) !== false;
	}

	/**
	 * Removes a virtual host configuration in RabbitMQ.
	 */
	public function deleteVirtualHost()
	{
		// DELETE /api/vhosts/{name}
		$path = $this->composeUrl( '/api/vhosts/'.$this->connection->VirtualHost );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_DELETE );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Health validation functions.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	/**
	 * Checks if the given REST service url can be reached.
	 *
	 * @param integer $curlErrNr
	 * @param string $curlErrMsg
	 * @return bool
	 */
	public function checkConnection( &$curlErrNr, &$curlErrMsg )
	{
		$httpClient = $this->createHttpClient( $this->connection->Url );
		try {
			$httpClient->send();
		} catch ( Exception $e ) {
			$adapter = $httpClient->getAdapter();
			if ( $adapter instanceof Zend\Http\Client\Adapter\Curl ) {
				$curl = $adapter->getHandle();
				$curlErrNr = curl_errno( $curl );
				$curlErrMsg = curl_error( $curl );
			}
			return false;
		}
		return true;
	}

	/**
	 * Tests if the RabbitMQ installation is working properly.
	 *
	 * @return bool
	 */
	public function testAliveness()
	{
		// GET /api/aliveness-test/{vhost}
		$path = $this->composeUrl( '/api/aliveness-test/'.$this->connection->VirtualHost );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
		$result = json_decode( $httpBody ); // {"status":"ok"}
		return $result->status == 'ok';
	}

	/**
	 * Request an overview of the RabbitMQ installation. This overview contains "various bits of information that
	 * describe the whole system" [RabbitMQ Management HTTP API]
	 * The overview is cached in $this->apiOverviewResults.
	 */
	private function getApiOverview()
	{
		// GET /api/overview
		$path = $this->composeUrl( '/api/overview' );
		$headers = array( 'Accept' => 'application/json' );
		$request = $this->composeRequest( $path, null, Zend\Http\Request::METHOD_GET, $headers );

		// Call the REST service.
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'handleCommonResponse', __FUNCTION__ );
		$this->apiOverviewResults = json_decode( $httpBody );
	}

	/**
	 * Returns the version of the RabbitMQ installation.
	 *
	 * @return string Version in "x.y.z" notation.
	 */
	public function getVersion()
	{
		if( !isset( $this->apiOverviewResults ) ) {
			$this->getApiOverview();
		}
		return $this->apiOverviewResults->rabbitmq_version;
	}

	/**
	 * Returns a list of listeners that are installed and configured on RabbitMQ.
	 *
	 * @return array List of RabbitMQ listeners.
	 */
	public function getListeners()
	{
		if( !isset( $this->apiOverviewResults ) ) {
			$this->getApiOverview();
		}
		return $this->apiOverviewResults->listeners;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Specific HTTP error handling functions.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	/**
	 * Handles general responses for the REST service.
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function handleCommonResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200:
			case 201: // e.g. successfully created
			case 204:
				break;
			case 401:
				$this->throwAccessDenied( $request, $httpCode, $responseBody );
				break;
			case 404:
			case 410:
				$this->throwNotFound( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}

	/**
	 * Handles an unexpected error. 
	 * 
	 * This could be a HTTP 500 error thrown by RabbitMQ. 
	 * Basically this is an integration error that should never happen.
	 *
	 * @param string[] $request List of request params.
	 * @param string $httpCode
	 * @param string $responseBody
	 * @throws BizException with (S1019) code
	 */
	private function throwUnexpected( $request, $httpCode, $responseBody )
	{
		$detail = 'Request parameters: '.print_r($request,true).
				' Returned HTTP code: '.$httpCode.
				' HTTP response body: '.print_r($responseBody,true);
		$errors = array( 
			BizResources::localize( 'ERR_INVALID_OPERATION' ), 
			'RabbitMQ returned unexpected error.', 
			'See Enterprise Server logging for more details.'
		);
		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
	}

	/**
	 * Handles a resource not found error. 
	 * 
	 * These are generally either a HTTP 404 (Not Found) or a 410 (Gone) code.
	 *
	 * @param string[] $request List of request params.
	 * @param string $httpCode
	 * @param string $responseBody
	 * @throws BizException with (S1029) code
	 */
	private function throwNotFound( $request, $httpCode, $responseBody ) 
	{
		$detail = 'Request parameters: '.print_r($request,true).
			' Returned HTTP code: '.$httpCode.
			' HTTP response body: '.print_r($responseBody,true);
		$errors = array(
			BizResources::localize( 'ERR_NOTFOUND' ),
			'RabbitMQ could not find resource.',
			'See Enterprise Server logging for more details.'
		);
		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
	}

	/**
	 * Handles a permission error (HTTP 401 error code).
	 *
	 * @param string[] $request List of request params.
	 * @param string $httpCode
	 * @param string $responseBody
	 * @throws BizException with (S1002) code
	 */
	private function throwAccessDenied( $request, $httpCode, $responseBody )
	{
		$detail = 'Request parameters: '.print_r($request,true).
			' Returned HTTP code: '.$httpCode.
			' HTTP response body: '.print_r($responseBody,true);
		$errors = array(
			BizResources::localize( 'ERR_AUTHORIZATION' ),
			'RabbitMQ user not authorized to access resource.',
			'See Enterprise Server logging for more details.'
		);
		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
	}

	/**
	 * Combines a list of error messages in one long error message string.
	 *
	 * In the list, empty strings may occur, or strings without a dot in the end.
	 * Those cases are handled.
	 *
	 * @param string[] $errors List of errors to combine.
	 * @return string The combined error string.
	 */
	private function combineErrorMessages( array $errors )
	{
		$errorsCleaned = array();
		foreach( $errors as $error ) {
			// Only handle messages that contain some text.
			$error = trim($error);
			if( $error ) {
				// Add dot at end of each line when missing.
				if( substr( $error, -1, 1 ) != '.' ) {
					$error = $error . '.';
				}
				$errorsCleaned[] = $error;
			}
		}
		return implode( ' ', $errorsCleaned );
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Internal functions. Those are made 'protected' to allow mocking.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	/**
	 * Constructs a request data class in memory.
	 *
	 * @param string $path
	 * @param string $body
	 * @param string $method
	 * @param array $headers Optional.
	 * @param array $curlOptions Optional. Extra parameters for the cURL adapter.
	 * @return string[] List of request params
	 */
	protected function composeRequest( $path, $body, $method, $headers = array(), $curlOptions = array() )
	{
		$request = array(
			'url' => $path,
			'body' => $body,
			'method' => $method,
			'headers' => $headers,
			'curloptions' => $curlOptions
		);
		return $request;
	}
	
	/**
	 * Constructs a HTTP client class in memory.
	 *
	 * @param array $request List of request params.
	 * @return Zend\Http\Client|null Client, or NULL when mocked.
	 */
	protected function composeClient( $request )
	{
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'RabbitMQ', 'DEBUG', 'Composing HTTP client with request data: '.print_r($request,true) );
		}
		$httpClient = $this->createHttpClient( $request['url'], $request['curloptions'] );
		$httpClient->setRawBody( $request['body'] );
		$httpClient->setMethod( $request['method'] );
		$httpClient->setHeaders( $request['headers'] );

		$httpClient->setAuth( $this->connection->User, $this->connection->Password );
		return $httpClient;
	}

	/**
	 * Returns a reference to a http client.
	 *
	 * @param string $path
	 * @param array $curlOptions Optional. Extra parameters for the cURL adapter.
	 * @return Zend\Http\Client
	 * @throws BizException on connection errors.
	 */
	protected function createHttpClient( $path, $curlOptions = array() )
	{
		try {
			// Determine HTTP or HTTPS.
			$isHttps = false;
			try {
				$testUri = Zend\Uri\UriFactory::factory( $path );
				$isHttps = $testUri && $testUri->getScheme() == 'https';
			} catch( Exception $e ) {
				throw new BizException( null, 'Server', 'URL to download test file does not seem to be valid: '
					.$e->getMessage(), 'Configuration error' );
			}

			// Resolve the enterprise proxy if configured. This is taken as is from the original
			// DigitalPublishingSuiteClient and has not been tested.
			$configurations = ( defined('ENTERPRISE_PROXY') && ENTERPRISE_PROXY != '' )
				? unserialize( ENTERPRISE_PROXY )
				: array();

			if ( $configurations ) {
				if ( isset($configurations['proxy_host']) ) {
					$curlOptions[CURLOPT_PROXY] = $configurations['proxy_host'];
				}
				if ( isset($configurations['proxy_port']) ) {
					$curlOptions[CURLOPT_PROXYPORT] = $configurations['proxy_port'];
				}
				if ( isset($configurations['proxy_user']) && isset($configurations['proxy_pass']) ) {
					$curlOptions[CURLOPT_PROXYUSERPWD] = $configurations['proxy_user'] . ":" . $configurations['proxy_pass'];
				}
			}

			$httpClient = new Zend\Http\Client( $path );

			if( $isHttps ) {
				$localCert = BASEDIR.'/config/encryptkeys/rabbitmq/cacert.pem'; // for HTTPS / SSL only
				if( !file_exists($localCert) ) {
					throw new BizException( null, 'Server', null,
						'The certificate file "'.$localCert.'" does not exists.' );
				}
				$httpClient->setOptions(
					array(
						'adapter' => 'Zend\Http\Client\Adapter\Curl',
						'curloptions' => $curlOptions + $this->getCurlOptionsForSsl( $localCert ) // prefer theirs ($curlOptions) over ours
					)
				);
			}
		} catch( Exception $e ) {
			$message = 'Could not connect to RabbitMQ.'; // for admin users only
			$detail = 'Error: '.$e->getMessage();
			throw new BizException( null, 'Server', $detail, $message );
		}
		return $httpClient;
	}

	/**
	 * Returns a list of options to set to Curl to make HTTP secure (HTTPS).
	 *
	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
	 * @return array
	 */
	private function getCurlOptionsForSsl( $localCert )
	{
		return array(
			//	CURLOPT_SSLVERSION => 2, Let php determine itself. Otherwise 'unknown SSL-protocol' error.
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => 1, // To prevent a man in the middle attack. (EN-29338).
			CURLOPT_CAINFO => $localCert
		);
	}

	/**
	 * Runs a service request at RabbitMQ (REST server) and returns the response.
	 * Logs the request and response at Enterprise Server logging folder.
	 * 
	 * @param Zend\Http\Client $httpClient Client connected to RabbitMQ.
	 * @param array $request Request data.
	 * @param callable $cbFunction Callback function to handle the response. Should accept $request, $httpCode and $responseBody params.
	 * @param string $serviceName Service to run at RabbitMQ. Used for logging only.
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function executeRequest( $httpClient, $request, $cbFunction, $serviceName )
	{
		// Retrieve the raw response object.
		$response = $this->callService( $httpClient, $serviceName, $request );

		$httpCode = null;
		$responseBody = null;

		// Get HTTP response data.
		if( $response ) {
			$httpCode = $response->getStatusCode();

			//EN-87488 RabbitMQ on CentOS sends responses with Content-Encoding set, but an empty body.
			//Zend\Http\Response does not check for empty bodies, and just tries to decode it which goes wrong.
			$content = (string) $response->getContent();
			if( !empty( $content ) ) {
				$responseBody = $response->getBody();
			}
		}

		// Callback the response handler.
		return call_user_func_array( array($this, $cbFunction), array( $request, $httpCode, $responseBody ) );
	}

	/**
	 * Retrieves the response from the HttpClient.
	 *
	 * @param Zend\Http\Client $httpClient Client connected to RabbitMQ.
	 * @param string $serviceName Service to run at RabbitMQ. Used for logging only.
	 * @param array $request Request data.
	 *
	 * @return null|Zend\Http\Response The response object from the HttpClient.
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function callService( Zend\Http\Client $httpClient, $serviceName, array $request )
	{
		// Call the remote RabbitMQ service and monitor profiling
		PerformanceProfiler::startProfile( 'Calling RabbitMQ', 1 );
		$e = null;
		try {
			$response = $httpClient->send();
		} catch( Exception $e ) {
			$response = null;
		}
		PerformanceProfiler::stopProfile( 'Calling RabbitMQ', 1 );

		// Log request and response (or error)
		LogHandler::Log( 'RabbitMQ', 'DEBUG', 'Called RabbitMQ service '.$serviceName );
		if( defined('LOG_RABBITMQ_SERVICES') && LOG_RABBITMQ_SERVICES ) {
			$rabbitMqServiceName = 'RabbitMQ_'.$serviceName;
			LogHandler::logService( $rabbitMqServiceName, $httpClient->getLastRawRequest(), true, 'REST', null, true );
			if( $response ) {
				if( $response->isSuccess() ) {
					LogHandler::logService( $rabbitMqServiceName, $response->toString(), false, 'REST', null, true );
				} else {
					LogHandler::logService( $rabbitMqServiceName, $response->toString(), null, 'REST', null, true );
				}
			}
		}
		
		// After logging, it is safe to raise any fatal problem.
		if( $e ) {
			$detail = 'Error: '.$e->getMessage();
			throw new BizException( 'ERR_CONNECT', 'Server', $detail, null, ['RabbitMQ'] );
		}
		return $response;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Validation functions.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

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

	/**
	 * Validates if a given parameter is a list of strings which are all not empty.
	 *
	 * @param string $methodName The method that calls this validation function.
	 * @param string $paramName The name of the parameter within the method, minus the $ sign.
	 * @param string[] $paramValues The value of the parameter to validate.
	 * @throws BizException Thrown when the provided parameter is not valid.
	 */
	private function validateArrayOfString( $methodName, $paramName, $paramValues )
	{
		if( !is_array( $paramValues ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
				$methodName . '() got bad value '.print_r($paramValues,true).' for $' . $paramName . ' param.' );
		}
		foreach( $paramValues as $paramValue ) {
			$this->validateString( $methodName, $paramName, $paramValue );
		}
	}

	/**
	 * Validates if a given parameter value is a boolean.
	 *
	 * @param string $methodName The method that calls this validation function.
	 * @param string $paramName The name of the parameter within the method, minus the $ sign.
	 * @param string $paramValue The value of the parameter to validate.
	 * @throws BizException Thrown when the provided parameter is not valid.
	 */
	private function validateBoolean( $methodName, $paramName, $paramValue ) 
	{
		if( !is_bool( $paramValue )  ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
				$methodName . '() got bad value '.print_r($paramValue,true).' for $' . $paramName . ' param.' );
		}
	}

	/**
	 * Validates if a given parameter value is an integer.
	 *
	 * @param string $methodName The method that calls this validation function.
	 * @param string $paramName The name of the parameter within the method, minus the $ sign.
	 * @param string $paramValue The value of the parameter to validate.
	 * @throws BizException Thrown when the provided parameter is not valid.
	 */
	private function validateInteger( $methodName, $paramName, $paramValue ) 
	{
		if( !is_int( $paramValue )  ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
				$methodName . '() got bad value '.print_r($paramValue,true).' for $' . $paramName . ' param.' );
		}
	}
}