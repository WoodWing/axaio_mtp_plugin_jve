<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v9.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * XmlRpc client that connects to Drupal.
 */
require_once BASEDIR . '/server/utils/EnterpriseXmlRpcClient.class.php';

class DrupalXmlRpcClient extends WW_Utils_XmlRpcClient
{
	/**
	 * @var string|null
	 */
	public $consumerKey = null;
	/**
	 * @var string|null
	 */
	public $consumerSecret = null;
	/**
	 * @var string|null
	 */
	public $certificate = null;
	/**
	 * @var string|null
	 */
	public $drupalDatabaseInstanceId = null;
	/**
	 * @var string|Object
	 */
	public $publishTarget = null;

	/**
	 * Default constructor.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	public function __construct( $publishTarget )
	{
		$this->resolveChannelData( $publishTarget );

		$httpClient = $this->createOauthHttpClient( $this->url.'xmlrpc.php', $this->certificate,
			$this->consumerKey, $this->consumerSecret, $this->drupalDatabaseInstanceId );
		parent::__construct($this->url, $httpClient);
	}

	/**
	 * Creates the Zend Http Client to talk to a remote site.
	 * The token is generated for the oauth security.
	 * Also deals with HTTPS / SSL connections too for which the certificate option must be set.
	 *
	 * @param string $uri The URL to a remote site.
	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
	 * @param string $consumerKey
	 * @param string $consumerSecret
	 * @param string $drupalDatabaseInstanceId The Drupal Database Instance Id as recorded on the channel data.
	 * @throws BizException When HTTPS uri given used without certificate.
	 * @return Zend_Http_Client
	 */
	public function createOauthHttpClient( $uri, $localCert, $consumerKey, $consumerSecret, $drupalDatabaseInstanceId)
	{
		$errorMessage = '. Given URL appears to be invalid: ';
		try {
			require_once 'Zend/Uri.php';
			$uri = Zend_Uri::factory( $uri );
			$isHttps = $uri && $uri->getScheme() == 'https';

		} catch( Zend_Http_Client_Exception $e ) {
			throw new BizException( null, 'Server', null, $e->getMessage() . $errorMessage  . $uri );
		} catch( Zend_Uri_Exception $e ) {
			throw new BizException( null, 'Server', null, $e->getMessage() . $errorMessage  . $uri );
		}

		require_once 'Zend/Oauth.php';
		$config = array(
			'version' => '1.0',
			'signatureMethod' => 'HMAC-SHA256',
			'consumerKey' => $consumerKey,
			'consumerSecret' => $consumerSecret,
			'requestMethod' => Zend_Oauth::GET,
			'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER
		);

		require_once 'Zend/Oauth/Token/Access.php';
		$token = new Zend_Oauth_Token_Access(); // 2-legged doesn't require a token but Zend_Oauth needs it!
		$httpClient = $token->getHttpClient($config, $uri);

		$httpClient->setConfig(array('timeout' => 3600));
		$httpClient->setParameterGet('DrupalDatabaseInstanceId', $drupalDatabaseInstanceId);

		// Because the Zend_XmlRpc_Client class supports SSL, but does not validate certificates / hosts / peers (yet),
		// its connections are NOT safe! Therefore we use CURL by passing the Zend_Http_Client_Adapter_Curl
		// adapter into the Zend_Http_Client class for which we set the secure options and certificate.
		if( $localCert ) {
			if( !file_exists($localCert) ) {
				throw new BizException( null, 'Server', null, 'The file "'.$localCert.'" does not exists.' );
			}
			if( $isHttps ) {
				$curlSslOptions = $this->getCurlOptionsForSsl( $localCert );
				$httpClient->setConfig(	array('adapter' => 'Zend_Http_Client_Adapter_Curl', 'curloptions' => $curlSslOptions,
					'timeout' => 3600,));
			}
		} else {
			if( $isHttps ) {
				throw new BizException( null, 'Server', null,'Using HTTPS, but no certificate configured.' );
			}
		}
		return $httpClient;
	}

	/**
	 * Returns a list of options to set to Curl to make HTTP secure (HTTPS).
	 *
	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
	 * @return array An array of Curl options for SSL.
	 */
	private function getCurlOptionsForSsl( $localCert )
	{
		return array(
			//	CURLOPT_SSLVERSION => 2, Let php determine itself. Otherwise 'unknow SSL-protocol' error.
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_CAINFO => $localCert
		);
	}

	/**
	 * Get and set the variables that holds the credentials. The config array
	 * in config_drupal7.php is first checked if there is an overruled site. Otherwise
	 * get the default site credentials. The corresponding mapping and options are also set.
	 *
	 * @param PubPublishTarget $publishTarget
	 */
	private function resolveChannelData( $publishTarget )
	{
		// If any of the credentials / required data are not set we need to attempt to fetch them from the channel.
		if ( !isset($this->url) || !isset($this->certificate) || !isset($this->consumerKey) || !isset($this->consumerSecret) ) {
			// Retrieve the Oauth credentials and store them.
			require_once BASEDIR . '/server/utils/PublishingUtils.class.php';
			require_once dirname(__FILE__) . '/Utils.class.php';
			require_once BASEDIR . '/server/bizclasses/BizAdmPublication.class.php';
			$publicationChannel = WW_Utils_PublishingUtils::getAdmChannelById($publishTarget->PubChannelID);
			$this->url = WW_Utils_PublishingUtils::getAdmPropertyValue($publicationChannel, WW_Plugins_Drupal7_Utils::CHANNEL_SITE_URL);
			$this->consumerKey = WW_Utils_PublishingUtils::getAdmPropertyValue($publicationChannel, WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_KEY);
			$this->consumerSecret = WW_Utils_PublishingUtils::getAdmPropertyValue($publicationChannel, WW_Plugins_Drupal7_Utils::CHANNEL_CONSUMER_SECRET);
			$this->certificate = WW_Utils_PublishingUtils::getAdmPropertyValue($publicationChannel, WW_Plugins_Drupal7_Utils::CHANNEL_CERTIFICATE);
			$this->drupalDatabaseInstanceId = BizAdmPublication::getPublishSystemIdForChannel( $publicationChannel->Id );
			$this->publishTarget = $publishTarget;
		}
	}

	/**
	 * Verifies the Channel's DrupalDatabaseInstanceId against the one returned from Drupal.
	 *
	 * This only happens for those calls added to the listing. The returned value has to match
	 * the recorded value.
	 * The calls (service call) include 'getContentTypes','getVocabulary','getVocabularyNames' and 'getFields'.
	 *
	 * If no recorded value is found on the Channel, this function attempts to set the one returned
	 * from Drupal instead to link this Enterprise channel to the Drupal DB instance with which
	 * is being communicated.
	 *
	 * @param string $service
	 * @param mixed $response Response returned from service(refer to function header) call to Drupal7.
	 */
	private function saveDrupalDatabaseInstanceId( $service, $response )
	{
		switch ( $service ) {
			case 'enterprise.getContentTypes':
			case 'enterprise.getVocabulary':
			case 'enterprise.getVocabularyNames':
			case 'enterprise.getFields':
				require_once BASEDIR . '/server/bizclasses/BizAdmPublication.class.php';
				$this->drupalDatabaseInstanceId = $response['DrupalDatabaseInstanceId'];
				$channelId = $this->publishTarget->PubChannelID;
				BizAdmPublication::savePublishSystemIdForChannel( $channelId, $this->drupalDatabaseInstanceId );
				break;
			default:
				// For non recorded service calls, do nothing and ignore the DrupalDatabaseInstanceId for now.
				$this->drupalDatabaseInstanceId = '';
				break;
		}
	}

	/**
	 * Test configuration by calling enterprise.testConfig in Drupal.
	 *
	 * @param AdmPubChannel $pubChannel
	 * @return array with keys "Errors", 'Warnings' and "Version"
	 */
	static public function testConfig( $pubChannel )
	{
		$result = array('Errors' => array(), 'Warnings' => array(), 'Version' => array());

		try {
			require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
			$publishTarget = new PubPublishTarget();
			$publishTarget->PubChannelID = $pubChannel->Id;

			$rpcClient = new DrupalXmlRpcClient( $publishTarget );
			$valueArray = $rpcClient->callRpcService( 'enterprise.testConfig',
				array()
			);
		} catch( BizException $e ) {
			$result['Errors'][] = $e->getMessage();
		}

		if ( isset($valueArray['Version']) ) {
			$version = $valueArray['Version'];
			if ( SERVERVERSION != $version ) {
				$result['Version'][] = 'The Drupal module version (version ' . $version . ') does not equal '
					. 'the Enterprise Server version (version ' . SERVERVERSION . '). Please install the latest Drupal module.';
			}
		} else {
			$result['Version'][] = 'The version of the Drupal module could not be obtained. '
					. 'This indicates that an old Drupal module is installed. Please install the latest Drupal module.';
		}

		return $result;
	}

	/**
	 * Publishes a PublishForm to a Drupal node.
	 *
	 * @throws BizException Throws an exception if the node id is set on a Publish action, or unset on an Update action.
	 * @param $dossier
	 * @param $publishForm
	 * @param $values
	 * @param $attachments
	 * @return mixed
	 */
	public function saveNode ( $dossier, $values, $attachments, $action='Publish', $preview=false )
	{
		$externalId = isset($dossier->ExternalId) ? $dossier->ExternalId : null;

		if ($action == 'Publish' && !empty($externalId)) {
			throw new BizException(null, 'Server', null, 'This Dossier has already been published.');
		}

		if ($action == 'Update' && is_null($externalId)) {
			throw new BizException(null, 'Server', null, 'This Dossier has not been published yet and therefore cannot be updated.');
		}

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';

		$valueArray = $this->callRpcService( 'enterprise.saveNode'
			, array(
			       array(
				       'ID' => $dossier->MetaData->BasicMetaData->ID,
				       'Name' => $dossier->MetaData->BasicMetaData->Name,
				       'Category' => $dossier->MetaData->BasicMetaData->Category->Name,
				       'Description' => $dossier->MetaData->ContentMetaData->Description,
				       'Action' => $action,
				       'Preview' => $preview,
				       'ExternalId' => $externalId,
					   'Username' => BizSession::getShortUserName()
			       ),
			       $values,
			       $attachments,
			  )
		);
		return $valueArray;
	}

	public function previewNode ($dossier, $values, $attachments )
	{
		// Preview is basically the same call as a publish action, but with a preview flag sent along
		// to Drupal.
		return $this->saveNode( $dossier, $values, $attachments, 'Preview', true );
	}

	public function updateNode ($dossier, $values, $attachments )
	{
		return $this->saveNode( $dossier, $values, $attachments, 'Update', false);
	}


	/**
	 * Removes/unpublishes a published dossier from Drupal
	 * using the $dossier->ExternalId to identify the dosier to Drupal.
	 *
	 * @param Object $dossier
	 * @return array of PubFields containing information from Drupal
	 */
	public function removeNode( $dossier )
	{
		if (!isset($dossier->ExternalId)) {
			throw new BizException(null, 'Server', null, 'The Dossier could not be removed.');
		}

		$result = $this->callRpcService( 'enterprise.removeNode',
			array(
		        intval( $dossier->ExternalId ),
		    )
		);
		return $result;
	}

	/**
	 * Gets and returns the contenttypes from the Drupal side.
	 *
	 * @throws Throws a BizException if the response GUID is invalid or does not match the channel's GUID.
	 * @return array with contenttypes
	 */
	public function getContentTypes( )
	{
		// Retrieve the content types from Drupal.
		$service = 'enterprise.getContentTypes';

		try {
			$response = $this->callRpcService( $service, array() );
			$this->saveDrupalDatabaseInstanceId( $service, $response );
			// A field that is only needed for specific services, so unset it once finished using it.
			unset ( $response['DrupalDatabaseInstanceId'] );

		} catch (BizException $e ) {
			LogHandler::Log( 'Drupal7', 'ERROR', 'A communication error with Drupal occurred. Reported by module: '
				. $e->getMessage() );
			return array();
		}

		$contentTypes = array();

		if( $response ) foreach( $response as $type ) {
			$contentTypes[] = array(
				'type'        => $type['type'],
				'name'        => $type['name'],
				'description' => $type['description'],
				'original'    => $type['orig_type'],
			);
		}
		return $contentTypes;
	}

	/**
	 * Get the field definitions setup for a given content type (or for all content types).
	 *
	 * @throws Throws a BizException if the GUID is invalid or does not match the GUID for the channel data.
	 * @param string $contentType Filter fields per content type. Pass NULL to get all.
	 * @return array The field definitions.
	 */
	public function getFields( $contentType = null )
	{
		// Cache the response results to speed up retrieving ContentType fields.
		static $getFieldsResponseCache;
		$fields = array();
		$service = 'enterprise.getFields';

		// Retrieve the field definitions from Drupal.
		if (!isset($getFieldsResponseCache[$this->consumerKey])) {
			$getFieldsResponseCache[$this->consumerKey] = $this->callRpcService( $service, array() );
			$this->saveDrupalDatabaseInstanceId( $service, $getFieldsResponseCache[$this->consumerKey] );
			// A field that is only needed for specific services, so unset it once finished using it.
			unset ( $getFieldsResponseCache['DrupalDatabaseInstanceId'] );

		}

		if( $getFieldsResponseCache[$this->consumerKey] ) {
			if( is_null( $contentType ) ) {
				// If no specific ContentType is given, merge all values.
				foreach( $getFieldsResponseCache[$this->consumerKey] as $fieldsOfContentType ) {
					$fields = array_merge( $fields, $fieldsOfContentType );
				}
			} else {
				// If a specific ContentType is provided return the fields for that type only.
				if ( isset( $getFieldsResponseCache[$this->consumerKey][ $contentType ] )) {
					$fields = $getFieldsResponseCache[$this->consumerKey][ $contentType ];
				}
			}
		}
		return $fields;
	}

	/**
	 * Returns a Field object from a xmlrpc value object
	 *
	 * @param string $key
	 * @param string $type
	 * @param mixed $value
	 * @return Field or null if $value couldn't be converted
	 */
	public static function getField( $key, $type, $value )
	{
		$result = null;
		$result = $result; // Keep analyzer happy
		if( !is_null( $value ) ) {
			switch( $type ) {
				case 'int':
					$value = array(intval($value));
					break;
				case 'double':
					$value = array(doubleval($value));
					break;
				case 'string':
					$value = array(strval($value));
					break;
				case 'multistring':
					// Create a string with double break rules.. between them, to display correctly in CS
					$value = strval(implode("<br /><br />", $value));
					$value = array(nl2br($value));
					$type = 'string';
				default:
					break;
			}
			$result = new PubField( $key, $type, $value );
		}

		//TODO BizException if $fieldValue is null?

		return $result;
	}

	/**
	 * Requests fieldvalues from an external publishing system
	 * using the $dossier->ExternalId to identify the dosier to the publishing system.
	 * Called by the core (BizPublishing.class.php).
	 *
	 * @param Object $dossier
	 * @return array Raw response array.
	 */
	public function nodeGetInfo ( $dossier )
	{
		return $this->callRpcService( 'enterprise.nodeGetInfo', array( intval( $dossier->ExternalId )));
	}

	/**
	 * Requests dossier URL from an external publishing system using the $dossier->ExternalId to identify the dosier to
	 * the publishing system.
	 *
	 * @param Object $dossier
	 * @return string url The url to the the dossier.
	 */
	public function getUrl( $dossier )
	{
		$url = $this->callRpcService( 'enterprise.getUrl', array(intval( $dossier->ExternalId )));
		if(empty($url)) {
			$url = $this->url . '?q=node/' . $dossier->ExternalId;
		}
		return $url;
	}

	/**
	 * Returns a file id for the specified data.
	 *
	 * @param $filename
	 * @param $contentType
	 * @param $publishedVersion
	 * @param $dossier
	 * @return mixed
	 */
    public function getFileId($filename, $contentType, $publishedVersion, $dossier)
    {
	    $response = $this->callRpcService( 'enterprise.getFileId',
		    array(
		         array(
			         'filename'    => $filename,
			         'contentType' => $contentType,
			         'version'     => $publishedVersion,
			         'nodeId'      => intval( $dossier->ExternalId )
		         )
		    )
	    );
	    return $response;
    }

	/**
	 * Retrieves a vocabulary based on the vocabulary ID.
	 *
	 * The vocabulary data consists of an array with the ID of the term as the key and the name of the term as a value.
	 *
	 * @throws Throws a BizException if the GUID is invalid or does not match the Channel's GUID.
	 * @param int $vocabularyId The Id of the vocabulary to be retrieved.
	 * @return array An array of vocabulary data.
	 */
	public function getVocabulary( $vocabularyId )
	{
		$service = 'enterprise.getVocabulary';
		$result = $this->callRpcService( $service, array( intval($vocabularyId) ) );
		$this->saveDrupalDatabaseInstanceId( $service, $result );
		// A field that is only needed for specific services, so unset it once finished using it.
		unset ( $result['DrupalDatabaseInstanceId'] );

		return $result;
	}

	/**
	 * Returns the vocabulary names.
	 *
	 * Returns an array with the system_name of the Vocabulary as a key, and an array consisting of a 'vid' (Vocabulary
	 * Id) and the 'ww_term_entity' (Term Entity) for that Vocabulary.
	 *
	 * @return array
	 */
	public function getVocabularyNames()
	{
		$service = 'enterprise.getVocabularyNames';
		$result = $this->callRpcService( $service, array( array() ) );
		$this->saveDrupalDatabaseInstanceId( $service, $result );
		// A field that is only needed for specific services, so unset it once finished using it.
		unset ( $result['DrupalDatabaseInstanceId'] );
		return $result;
	}

	/**
	 * Uploads an attachment to Drupal.
	 *
	 * @Todo This function is not operational, fix it.
	 *
	 * @param Attachment $attachment The attachment to be uploaded.
	 * @param string $fileName The Objects name.
	 * @param string $drupalFieldId The Id of the field we are uploading a file to in Drupal.
	 * @param string $contentType The ContentType for which we are uploading a file.
	 * @return array|int|string
	 * @throws BizException When I/O with Drupal failed e.g. On HTTP error
	 */
	public function uploadAttachment( $attachment, $fileName, $drupalFieldId, $contentType )
	{
		// Determine the file Content Type.
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$extension = MimeTypeHandler::mimeType2FileExt($attachment->Type);

		// Determine the filename from the Path. (FileName with extension).
		$fileName = $fileName . $extension;

		// Todo: Determine if a file already exists by calling Drupal and requesting the content by filename. This might not be the best way of handling things. Maybe determine a hash to
		// Todo: determine changes?. or use the versioning??

		// Get the file content from the path.
		$content = file_get_contents($attachment->FilePath);

		$action = 'uploadAttachment';
		PerformanceProfiler::startProfile( 'Drupal7 - '.$action, 3 );
		$debugMode = LogHandler::debugMode();
		if( $debugMode ) {
			LogHandler::Log( 'Drupal', 'DEBUG', 'File does not exists at Drupal yet or '.
				'Enterprise has a newer version, so sending the file to Drupal.' );
		}
		$client = null;
		$fileId = 0;
		try {
			// Now it's time to upload the file to Drupal ...
			require_once 'Zend/Http/Client.php';
			$url = $this->url . 'index.php';
			$client = $this->createOauthHttpClient( $url, $this->certificate, $this->consumerKey, $this->consumerSecret,
				$this->drupalDatabaseInstanceId );

			// Set these three parameters as GET parameters. Otherwise they are not calculated correctly in the oauth signature.
			// We need to send them as GET parameters, because Zend is including them in the signature when the enctype is set to
			// 'multipart/form-data' and Drupal doesn't.
			$client->setParameterGet( 'q', 'ww_enterprise/upload');
			$client->setParameterGet( 'content_type', $contentType);
			$client->setParameterGet( 'field_id', $drupalFieldId );

			$client->setFileUpload( $fileName, 'files[upload]', $content, $attachment->Type );

			$response = $client->request( Zend_Http_Client::POST );

			$dom = new DOMDocument();
			$dom->loadXML( $response->getBody() );
			$xpath = new DOMXPath( $dom );
			$fidNode = $xpath->query('//fid')->item(0);
			$fileId = $fidNode ? $fidNode->nodeValue : 0;
			LogHandler::Log( 'Drupal', 'DEBUG', 'File sent to drupal. File Id: ' . $fileId );

		} catch( Exception $e ) { // any kind of Zend exception !!
		}

		// Log request and response (or fault) as plain text
		if( $debugMode ) { // check here since saveXML() calls below are expensive
			if( isset($client ) ) {
				LogHandler::logService( $action, $client->getLastRequest(), true, 'http_upload', 'txt' );
				$lastResponse = $client->getLastResponse();

				if( $lastResponse ) {
					if( $lastResponse->isError() ) {
						LogHandler::logService( $action, (string)$lastResponse, null, 'http_upload', 'txt' );
					} else {
						LogHandler::logService( $action, (string)$lastResponse, false, 'http_upload', 'txt' );
					}
				}
			} else { // HTTP error
				$message = isset($e) ? $e->getMessage() : 'unknown error';
				LogHandler::logService( $action, $message, null, 'http_upload', 'txt' );
			}
		}

		// Leave a trail in the server log once we came back from Drupal
		if( $debugMode ) {
			LogHandler::Log( 'Drupal', 'DEBUG', 'Received "'.$action.'" service response.' );
		}

		PerformanceProfiler::stopProfile( 'Drupal7 - '.$action, 3 );

		// Now the service I/O is logged above, throw exception in case of a fault.
		$lastResponse = $client->getLastResponse();
		if ( $lastResponse && $lastResponse->isError() ) {
			$errMsg = $lastResponse->getMessage().' (HTTP '.$lastResponse->getStatus().')';
		} else if( isset($e) ) {
			$errMsg = $e->getMessage();
		} else {
			$errMsg = null;
			// check if we have a fid, in case we don't, we expect an error.
			if ($fileId == 0) {
				$dom = new DOMDocument();
				$dom->loadXML( $lastResponse->getBody() );
				$xpath = new DOMXPath( $dom );
				$errorNode = $xpath->query('//error')->item(0);
				$errMsg = $errorNode->nodeValue;
			}
		}

		$message = BizResources::localize('ERR_DRUPAL_UPLOAD_FAILED');
		if ( $lastResponse->getStatus() == 401 ) {
			// Keep the same as in callService
			$message = 'Could not authenticate. Check the settings for the publication channel.';
		}

		if( $errMsg ) {
			LogHandler::Log( 'Drupal', 'ERROR', 'HTTP UPLOAD "'.$action.'" failed at URL "'.$this->url.'".' );
			throw new BizException( 'ERR_PUBLISH', 'Server', $errMsg,
				null, array('Drupal', $message));
		}
		return $fileId;
	}

	/**
	 * Check if the file already exists in Drupal
	 *
	 * @param int	 $objectId    Enterprise object id
	 * @param string $fileName    filename
	 * @param string $content     file contents
	 * @param string $contentType file content type
	 * @param string $version     file version in Enterprise
	 * @param string $type        file type in Enterprise
	 * @return array              file information
	 */
	private function checkFileExists( $dossier, $publishTarget, $objectId, $filename, $contentType, $version )
	{
		$publishedVersion = '';
		if(!is_null($dossier)) {
			foreach( $dossier->Relations as $relation ) {
				foreach( $relation->Targets as $target ) {
					if( $target->PubChannel->Id == $publishTarget->PubChannelID &&
						$target->Issue->Id == $publishTarget->IssueID &&
						$relation->Child == $objectId ) {
						$publishedVersion = $target->PublishedVersion;
						break 2; // Break once found the correct target
					}
				}
			}
		}

		LogHandler::Log( 'Drupal', 'DEBUG', "Current version: $version published version: $publishedVersion" );
		$fileId = null;
		if(!empty($publishedVersion) && (trim($publishedVersion) == trim($version))) {
			LogHandler::Log( 'Drupal', 'DEBUG', 'Look for the file in Drupal.' );

			$response = $this->getFileId($filename, $contentType, $publishedVersion, $dossier);
			if( isset($response[0]['fid']) ) {
				$fileId = $response[0]['fid'];
			}

			// Add debugging information.
			if( LogHandler::debugMode() ) {
				$details = "objectId: $objectId, filename: $filename, contentType: $contentType, version: $version";
				$message = (empty($fileId)) ? 'Did not find the file in Drupal for ' : 'Found file id \''. $fileId
					.'\' in Drupal for ';
				LogHandler::Log( 'Drupal', 'DEBUG', $message . $details );
			}
		}
		return $fileId;
	}

	/**
	 * {@inheritdoc}
	 */
	public function callRpcService( $action, $params, $obfuscatePasswordForLogs = null )
	{
		try {
			$retVal = parent::callRpcService( $action, $params, $obfuscatePasswordForLogs );
		} catch( BizException $e ) {
			$httpClient = $this->rpcClient->getHttpClient();
			$lastResponse = $httpClient->getLastResponse();
			if ( $lastResponse && $lastResponse->getStatus() == 401 ) {
				$errMsg = $lastResponse->getMessage().' (HTTP '.$lastResponse->getStatus().')';
				//$message = BizResources::localize('ERR_DRUPAL_COULD_NOT_AUTHENTICATE');
				// Keep the same as in uploadAttachments
				$message = 'Could not authenticate. Check the settings on the Publication Channel Maintenance page.';
				throw new BizException( 'ERR_PUBLISH', 'Server', $errMsg,
					null, array('Drupal', $message));
			}

			throw $e;
		}

		return $retVal;
	}
}