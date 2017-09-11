<?php
/**
 * Solr Solarium Client.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
class SolariumClient
{

	private $configuration = array();
	private $client = null;

	const LOG_IDENTIFIER = 'SolariumClient';

	/**
	 * Creates a new SolariumClient object.
	 *
	 * Parses the solr_config.php for the settings and wraps a new Solarium Client.
	 */
	public function __construct()
	{
		// Retrieve the configuration to setup the Solr endpoint.
		$this->parseSolrConfiguration();

		// Construct a new Solarium client.
		try {
			$this->client = new Solarium\Client($this->configuration);
		} catch (Exception $e) {
			LogHandler::Log( self::LOG_IDENTIFIER, 'ERROR', $e->getMessage() );
		}
	}

	/**
	 * Parses the config_solr settings and creates the Solarium client configuration.
	 *
	 * Reads out the SOLR_SERVER_URL and uses the data to construct the endpoint configuration for the Solarium client.
	 */
	private function parseSolrConfiguration()
	{
		$configuration = array();

		// Read out the Solr configuration string from configsolr to be used in the endpoint.
		// The scheme is not used by Solarium (for example: http:// / https://).
		$urlParts = parse_url(SOLR_SERVER_URL);
		$host = $urlParts['host'];
		$port = $urlParts['port'];
		$path = $urlParts['path'];

		// Note: SSL support is not yet present, if we wish to support it then we need to create a new Adapter that can
		// handle SSL requests. Out of the box this is not supported with the default adapters.
		// When implementing SSL support the scheme (HTTP / HTTPS) can be read out with: $path = $urlParts['scheme'];

		// Validate parsed information.
		if ( !empty( $host ) && !empty( $path ) && !empty( $port ) ) {
			// Construct a valid endpoint configuration array.
			$configuration = array();
			$configuration['endpoint'] = array();
			$configuration['endpoint'][$host] = array();
			$configuration['endpoint'][$host]['host'] = $host;
			$configuration['endpoint'][$host]['port'] = $port;
			$configuration['endpoint'][$host]['path'] = $path;
			$configuration['endpoint'][$host]['core'] = SOLR_CORE;
			$configuration['endpoint'][$host]['timeout'] = SOLR_TIMEOUT;
		}

		$this->configuration = $configuration;
	}

	/**
	 * Ping the Solr host to see if it is reachable.
	 *
	 * This function uses the configuration settings for the Solr instance (config_solr.php) to try and
	 * ping the host, it returns a boolean signifying whether or not the machine could be reached.
	 *
	 * @return bool Whether or not the Solr host was reachable.
	 */
	public function pingSolrHost()
	{
		$result = false;

		// Ping the host and retrieve the result.
		try {
			$pingRequest = $this->client->createPing();
			/** @noinspection PhpInternalEntityUsedInspection */
			$pingResponse = $this->client->ping( $pingRequest );
			$data = $pingResponse->getData();

			// Check the result
			if ($data && isset($data['status']) && $data['status'] == 'OK') {
				$result = true;
			}
		} catch (Exception $e ) {
			LogHandler::Log(self::LOG_IDENTIFIER, 'ERROR', $e->getMessage());
		}
		return $result;
	}

	/**
	 * Indexes the provided documents.
	 *
	 * Indexes the provided documents and directly commits them if $directCommit is true.
	 * Otherwise it will lean on the autocommit setting in the solr configuration.
	 *
	 * @param array $documents An array of object representations, each containing key => value pairs to be indexed.
	 * @param bool $directCommit Whether or not to directly commit the created documents, default false.
	 * @throws BizException
	 * @return bool True when the indexing is successful, false otherwise.
	 */
	public function indexObjects( array $documents, $directCommit = false )
	{
		// Create an update Query Instance.
		$update = $this->client->createUpdate();

		$docs = array();
		foreach( $documents as $fields ) {
			$document = $update->createDocument();
			$document->setKey( 'ID' );

			foreach( $fields as $key => $value ) {
				// Skip null values. These don't need to indexed.
				if( is_null( $value ) ) {
					continue;
				}
				$document->addField( $key, $value );
			}

			$docs[] = $document;
		}

		// Add the documents and a commit command to the update query.
		$update->addDocuments( $docs );

		// If DirectCommit, add the commit line to it.
		if ( $directCommit ) {
			$update->addCommit();
		}

		// Execute the query.
		$resultStatus = $this->executeUpdate( $update );

		return $resultStatus;
	}

	/**
	 * Unindexes the supplied documents by their id from Solr.
	 *
	 * Removes the documents matching the supplied ids from the Solr index.
	 *
	 * @param int[]|null $ids List of ids to be removed, or empty to empty out the whole index.
	 * @throws BizException
	 */
	public function unindexObjects( $ids )
	{
		// get an update query instance
		$update = $this->client->createUpdate();

		if (count( $ids ) == 0) {
			$update->addDeleteQuery( '*:*' );
		} else {
			$update->addDeleteByIds( $ids );
		}

		$update->addCommit();
		$this->executeUpdate( $update );
	}

	/**
	 * Optimizes the Solr index.
	 *
	 * Calls the default Optimize on the Solr index.
	 * @throws BizException
	 */
	public function optimize()
	{
		// Get an update query instance.
		// See Solr documentation for used options: http://wiki.apache.org/solr/UpdateXmlMessages
		$update = $this->client->createUpdate();
		$update->addOptimize(true /* softCommit */, false /* waitSearcher */, 5 /* maxSegments */);
		$this->executeUpdate( $update );
	}

	/**
	 * Updates a set of fields for documents in Solr index.
	 *
	 * @param array $objectIDs array of objectIDs
	 * @param array $fields key/value array of fields, containing the updated properties
	 * @param boolean $directCommit Optional indicates if the change should be directly committed
	 * @return bool True when the indexing is successful, false otherwise.
	 */
	public function updateObjectsFields( array $objectIDs, array $fields, $directCommit = false )
	{
		$result = null;

		if( empty( $fields ) ) {
			$errMsg = 'updateObjectsFields: Trying to update objects without specifying changed fields';
			throw new BizException( 'ERR_SOLR_SEARCH', 'Server', null, null, array($errMsg), 'ERROR' );
		}
		$update = $this->client->createUpdate();

		$docs = array();
		foreach( $objectIDs as $id ) {
			$document = $update->createDocument();
			$document->ID = $id;

			// The key must be set when updating with 'atomic updates'
			$document->setKey( 'ID' );

			foreach( $fields as $key => $value ) {
				$document->setField( $key, $value, null /* boost */, 'set' /* modifier */ );
			}

			$docs[] = $document;
		}

		// Add the documents and a commit command to the update query.
		$update->addDocuments( $docs );

		// If DirectCommit, add the commit line to it.
		if ( $directCommit ) {
			$update->addCommit();
		}

		// this executes the query and returns the result
		$result = $this->executeUpdate( $update );

		return $result;
	}

	/**
	 * Creates a new select and returns the query object.
	 *
	 * @param array|null $options Array with initial options for select
	 * @return Solarium/QueryType/Select/Query/Query|null $query
	 */
	public function createSelect( $options = null )
	{
		$query = null;
		try {
			$query = $this->client->createSelect( $options );

			$query->setResponseWriter( $query::WT_JSON );
			$query->addParam( 'qt', 'WoodWing' ); // Default request handler
		} catch (Exception $e ) {
			LogHandler::Log(self::LOG_IDENTIFIER, 'ERROR', $e->getMessage());
		}

		return $query;
	}

	/**
	 * Executes a select and returns the result set.
	 *
	 * @param Solarium/QueryType/Select/Query/Query $query
	 * @throws BizException
	 * @return Solarium/QueryType/Select/Result|null $resultSet
	 */
	public function executeSelect( $query )
	{
		$resultSet = null;

		try {
			$debugMode = LogHandler::debugMode();
			$area = 'Solr'; // Service log name

			if( $debugMode ) {
				// Log query as Solr request
				$debugRequest = print_r( $query, true );
				LogHandler::logService( $area, $debugRequest, true, 'txt', 'txt' );

				// Add Debug component
				// TODO: decide if we should always add an explain query in $debugMode (response might get too large?)
				$debug = $query->getDebug();
				$debug->setExplainOther( 'ID:MA*' );
			}

			// Execute the Query.
			/** @noinspection PhpInternalEntityUsedInspection */
			$resultSet = $this->client->select( $query );

			if( $debugMode ) {
				// Log Solr response
				$debugResponse = json_decode( $resultSet->getResponse()->getBody(), true );
				$debugResponse = print_r( $debugResponse, true );
				LogHandler::logService( $area, $debugResponse, false, 'txt', 'txt' );
			}
		} catch ( Exception $e ) {
			throw new BizException( 'ERR_SOLR_SEARCH', 'Server', null, null, array( $e->getMessage() ), 'ERROR' );
		}

		return $resultSet;
	}

	/**
	 * Executes an update and returns the result.
	 *
	 * This function will log the update request and response objects.
	 *
	 * @param Solarium/QueryType/Update/Query/Query $update
	 * @throws BizException
	 * @return bool True when the update was successful, false otherwise.
	 */
	private function executeUpdate( $update )
	{
		$updateStatus = false;
		$area = 'Solr'; // Service log name

		try {
			$debugMode = LogHandler::debugMode();

			if( $debugMode ) {
				// Log query as Solr request
				$debugRequest = print_r( $update, true );
				LogHandler::logService( $area, $debugRequest, true, 'txt', 'txt' );
			}
			$resultSet = $this->client->update( $update );
			if( !is_null( $resultSet ) && $resultSet instanceof Solarium\QueryType\Update\Result ) {
				$updateStatus = $resultSet->getStatus() == 0 && // 0 indicates successful.
					$resultSet->getResponse()->getStatusCode() == "200"; // HTTP status code for OK
			}
			if( $debugMode ) {
				// Log Solr response
				$debugResponse = json_decode( $resultSet->getResponse()->getBody(), true );
				$debugResponse = print_r( $debugResponse, true );
				LogHandler::logService( $area, $debugResponse, false, 'txt', 'txt' );
			}
		} catch ( Solarium\Exception\HttpException $e ) {
			$errorMessageJson = json_decode( $e->getBody() );
			$errorMessage = $errorMessageJson->error->msg;
			throw new BizException ( null, 'Server', '', '[Solr] ' . $errorMessage, null, 'ERROR' );
		} catch (Exception $e ) {
			throw new BizException ( null, 'Server', '', $e->getMessage(), null, 'ERROR' );
		}
		return $updateStatus;
	}
}
