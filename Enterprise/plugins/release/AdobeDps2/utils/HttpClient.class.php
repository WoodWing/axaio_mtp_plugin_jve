<?php
/**
 * HTTP client for RESTful communication with the Adobe DPS API.
 *
 * Wraps a curl based adapter within a Zend http client.
 *
 * @since       v9.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * TODO: Collection Support, Authentication.
 */

class AdobeDps2_Utils_HttpClient
{
	/** @var string $authenticationUrl The base URL used to connect to the Adobe DPS authentication server. */
	private $authenticationUrl = null;

	/** @var string $authorizationUrl The base URL used to connect to the Adobe DPS authorization server. */
	private $authorizationUrl = null;

	/** @var string $producerUrl The base URL used to connect to the Adobe DPS producer server. */
	private $producerUrl = null;

	/** @var string $ingestionUrl The base URL used to connect to the Adobe DPS ingestion server. */
	private $ingestionUrl = null;

	/** @var string $sessionId Used for HTTP headers */
	private $sessionId = null;

	/** @var string $requestId Used for HTTP headers */
	private $requestId = null;
	
	/** @var string $accessToken Used for HTTP headers */
	private $accessToken = null;
	
	/** @var string $consumerKey Used for HTTP headers */
	private $consumerKey = null;
	
	/** @var string $consumerSecret Used to obtain an Access Token. */
	private $consumerSecret = null;
	
	/**
	 * Constructor.
	 *
	 * @param string $authenticationUrl The base URL used to connect to the Adobe DPS authentication server.
	 * @param string $authorizationUrl The base URL used to connect to the Adobe DPS authorization server.
	 * @param string $producerUrl The base URL used to connect to the Adobe DPS producer server.
	 * @param string $ingestionUrl The base URL used to connect to the Adobe DPS ingestion server.
	 * @param string $consumerKey
	 * @param string $consumerSecret
	 */
	public function __construct( $authenticationUrl, $authorizationUrl, $producerUrl, $ingestionUrl,
		$consumerKey, $consumerSecret )
	{
		$this->authenticationUrl = $authenticationUrl;
		$this->authorizationUrl = $authorizationUrl;
		$this->producerUrl = $producerUrl;
		$this->ingestionUrl = $ingestionUrl;

		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		
		// Generate an unique id for the Adobe DPS session. (Can be reused during a salvo of requests.)
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$this->sessionId = NumberUtils::createGUID();
	}
	
	/**
	 * Parses a given content URL and returns the content version.
	 *
	 * Example of a content URL: 
	 *    "/publication/my_pub/article/my_art/contents;contentVersion=1424947657789/"
	 *
	 * @param string $contentUrl The content URL to be parsed.
	 * @return string|null The found content version. NULL when not found.
	 */
	public function deriveContentVersionFromContentUrl( $contentUrl )
	{
		$contentVersion = null;
		$urlParts = explode( '/', $contentUrl );
		if( $urlParts ) foreach( $urlParts as $urlPart ) {
			if( strpos( $urlPart, 'contents;' ) === 0 ) {
				$contentParts = explode( ';', $urlPart );
				if( $contentParts ) foreach( $contentParts as $contentPart ) {
					if( strpos( $contentPart, 'contentVersion=' ) === 0 ) {
						list( $label, $contentVersion ) = explode( '=', $contentPart, 2 );
						break 2;
					}
				}
			}
		}
		return $contentVersion;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Article-Ingestion API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Uploads new content, or updates existing content, as a pending change to an entity's content bucket.
	 *
	 * PUT /{publicationID}/{entityType}/{entityName}[;{version}]/contents/{contentPath}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param AdobeDps2_DataClasses_EntityCommon $metadata Used to compose request.
	 * @param string $dpsUploadGuid A GUID to uniquely identify the uploaded files and to group multiple uploads together.
	 * @param string $localFilePath The local file path for the file to be uploaded.
	 * @param string $remoteFilePath The aimed at path at the Adobe DPS side.
	 * @param string $contentType The content type of the file to be uploaded.
	 * @param array $curlProgressCallback Optional. Callback function, frequently called during file upload. See curl_setopt() => CURLOPT_PROGRESSFUNCTION.
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
	public function uploadFullArticle(
		$dpsPublicationId, AdobeDps2_DataClasses_EntityCommon $metadata,
		$dpsUploadGuid, $localFilePath, $remoteFilePath, $contentType, $curlProgressCallback = null )
	{
		require_once 'Zend/Http/Client.php';

		// Validate the input.
		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
		$this->validateString( __FUNCTION__, 'metadata->entityType', $metadata->entityType );
		$this->validateString( __FUNCTION__, 'metadata->entityName', $metadata->entityName );
		$this->validateString( __FUNCTION__, 'metadata->version', $metadata->version );
		$this->validateGuid( __FUNCTION__, 'apUploadGuid', $dpsUploadGuid );
		$this->validateString( __FUNCTION__, 'localFilePath', $localFilePath );
		$this->validateString( __FUNCTION__, 'remoteFilePath', $remoteFilePath );
		$this->validateString( __FUNCTION__, 'contentType', $contentType );

		// Compose the REST path:
		$body = null;
		$path = 'publication/' . $dpsPublicationId . '/' . $metadata->entityType . '/' . $metadata->entityName .
				';version=' . $metadata->version . '/contents/' . $remoteFilePath;
		$path = $this->composeIngestionUrl( $path );
		$curlOptions = array();
		if( $curlProgressCallback ) {
			$curlOptions[CURLOPT_NOPROGRESS] = false;
			$curlOptions[CURLOPT_PROGRESSFUNCTION] = $curlProgressCallback;
			$curlOptions[CURLOPT_BUFFERSIZE] = 262144; // * See below.
			// * The callback freqency of the CURLOPT_PROGRESSFUNCTION depends on the CURLOPT_BUFFERSIZE.
			// Assumed is to have at least a 1 Mbit/s upstream connection. That can upload with
			// a speed of 128 KB/s. We want to have a progress update roughly every few seconds,
			// which should happen with a buffer size of 256 KB (=262144 bytes).
		}
		$request = $this->composeRequest( $path, $body, Zend_Http_Client::PUT, $curlOptions );

		// Call the REST service.
		$request['headers']['X-DPS-Upload-Id'] = $dpsUploadGuid;
		$request['headers']['Content-Type'] = $contentType;
		$httpClient = $this->composeClient( $request );
		$httpClient->setRawData( file_get_contents($localFilePath), $contentType ); // stream file content into HTTP body
		/*$responseBody =*/ $this->executeRequest( $httpClient, $request, 'uploadFullArticleHandleResponse', __FUNCTION__ );
	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by uploadFullArticle().
	 *
	 * Note that the upload is asynchroneous; The content is NOT directly pushed from ingestion 
	 * service to the producer service. After successfully uploading the .article file to the 
	 * ingestion service, a "HTTP 202 Accepted” will be returned. To find out if the article
	 * is ready to publish, you should:
	 * - Make a request to get the publishing status (see [PHP-snippets]/article/get_article_status.php)
	 * - Check the JSON response for the publishing status
	 * - There might be more than one publishing status in the response, so iterate and seek where 'aspect' === 'ingestion'
	 * - In the same level, if 'numerator' === 'denominator', then the article upload process is finished
	 * - Now, it is safe to publish the article	 
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 *
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function uploadFullArticleHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 202: // Uploaded to the ingestion service. (Note that upload to the producer service is done async.)
				break;
			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body.
				$this->throwBadRequest400( $request, $httpCode, $responseBody );
				break;
			case 403: // Forbidden - user's quota exceeded.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			case 409: // Version conflict - specified version is not the latest.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}


	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Content API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * {publicationID}: Unique Id that identifies the publication, needs to be unique in Adobe DPS. For example:
	 * com.woodwing.publ001.
	 *
	 * {entityType} The type of the entity:
	 *      article
	 *      view
	 *      collection (Not yet implemented by Adobe.)
	 *      publication (Not yet implemented by Adobe.)
	 *      layout (Not yet implemented by Adobe.)
	 *      cardTemplate (Not yet implemented by Adobe.)
	 *
	 *  {entityName} The name of the entity. For example article001.
	 */

	/**
	 * Uploads new content, or updates existing content, as a pending change to an entity's content bucket.
	 *
	 * PUT /{publicationID}/{entityType}/{entityName}/contents;contentVersion={contentVersion}/{contentPath}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param string $entityType The type of the entity, used in the REST path.
	 * @param string $entityName The name of the entity, used in the REST path.
	 * @param string $dpsUploadGuid A GUID to uniquely identify the uploaded files and to group multiple uploads together.
	 * @param string $localFilePath The local file path for the file to be uploaded.
	 * @param string $imageType The image type. Supported values: 'thumbnail', 'socialSharing'
	 * @param string $contentType The content type of the file to be uploaded.
	 * @param string $contentVersion The version of the content for which to perform the action.
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
	public function createOrUpdateContent(
		$dpsPublicationId, $entityType, $entityName, 
		$dpsUploadGuid, $localFilePath, $imageType, $contentType, $contentVersion )
	{
		require_once 'Zend/Http/Client.php';

		// Validate the input.
		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
		$this->validateString( __FUNCTION__, 'entityType', $entityType );
		$this->validateString( __FUNCTION__, 'entityName', $entityName );
		$this->validateGuid( __FUNCTION__, 'apUploadGuid', $dpsUploadGuid );
		$this->validateString( __FUNCTION__, 'localFilePath', $localFilePath );
		$this->validateString( __FUNCTION__, 'imageType', $imageType );
		$this->validateString( __FUNCTION__, 'contentType', $contentType );
		$this->validateString( __FUNCTION__, 'contentVersion', $contentVersion );

		// Compose the REST path:
		$body = null;
		$path = 'publication/' . $dpsPublicationId . '/' . $entityType . '/' . $entityName .
				'/contents;contentVersion=' . $contentVersion . '/images/' . $imageType;
		$path = $this->composeProducerUrl( $path );
		$request = $this->composeRequest( $path, $body, Zend_Http_Client::PUT );

		// Call the REST service.
		$request['headers']['X-DPS-Upload-Id'] = $dpsUploadGuid;
		$request['headers']['Content-Type'] = $contentType;
		$httpClient = $this->composeClient( $request );
		$httpClient->setRawData( file_get_contents($localFilePath), $contentType ); // stream file content into HTTP body
		$this->executeRequest( $httpClient, $request, 'createOrUpdateContentHandleResponse', __FUNCTION__ );
	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by createOrUpdateContent().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 *
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function createOrUpdateContentHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200:
				break;
			case 201: // Created - Successfully created a new entity; 'Location' response header will be the newly created entity.	
				break;
			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body.
				$this->throwBadRequest400( $request, $httpCode, $responseBody );
				break;
			case 403: // Forbidden - user's quota exceeded.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			case 409: // Version conflict - specified version is not the latest.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}
	
	/**
	 * Returns the list of content elements for a certain entity (typically a collection).
	 *
	 * GET /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/collection/demo_collection;version=1434736861468/contentElements
	 * 
	 * The given $contentElementsUrl is composed like this:
	 *    /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/collection/demo_collection;version=1434736861468/contentElements
	 * 
	 * The returned elements have the following structure:
	 * Array (
	 *    [0] => Array (
	 *       [href] => /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/article/demo_article_one;version=1434728207515
	 *    )
	 *    [1] => Array (
	 *       [href] => /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/article/demo_article_two;version=1434730793111
	 *    )
	 * )
	 *
	 * New elements can be added to this list and old elements can be removed by 
	 * changing the list in memory and calling updateEntityContentElements().
	 *
	 * @param string $contentElementsUrl
	 * @return array The content elements.
	 */
	public function getEntityContentElements( $contentElementsUrl )
	{
		require_once 'Zend/Http/Client.php';

		// Validate the input.
		$this->validateString( __FUNCTION__, 'contentElementsUrl', $contentElementsUrl );
		
		// Compose the REST path:
		$path = $this->composeProducerUrl( $contentElementsUrl );
		
		// Call the REST service.
		$body = null;
		$request = $this->composeRequest( $path, $body, Zend_Http_Client::GET );
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'getEntityContentElementsHandleResponse', __FUNCTION__ );
		
		$httpBody = json_decode( $httpBody );
		return $httpBody;
	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by getEntityContentElements().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 *
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function getEntityContentElementsHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200:
				break;
			case 409: // Version conflict - specified version is not the latest.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}
	
	/**
	 * Updates the list of content elements for a certain entity (typically a collection).
	 *
	 * PUT /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/collection/demo_collection;version=1434736861468/contentElements
	 *
	 * Appends other entities (article or collection) or remove existing ones on the list 
	 * by doing an update (similar to the current update method) to the dereferenced link.
	 * Typically used after calling getEntityContentElements() with an updated list.
	 * 
	 * The given $contentElementsUrl is composed like this:
	 *    /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/collection/demo_collection;version=1434736861468/contentElements
	 * 
	 * The given $elements should have a structure like this:
	 * Array (
	 *    [0] => Array (
	 *       [href] => /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/article/demo_article_one;version=1434728207515
	 *    )
	 *    [1] => Array (
	 *       [href] => /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/article/demo_article_two;version=1434730793111
	 *    )
	 *    [2] => Array (
	 *       [href] => /publication/1b7cab71-80c8-4ed1-b1f1-1d8529718421/article/demo_article_three;version=1434738492010
	 *    )
	 * )
	 *
	 * @param string $contentElementsUrl
	 * @param array $elements
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
	public function updateEntityContentElements( $contentElementsUrl, $elements )
	{
		require_once 'Zend/Http/Client.php';

		// Validate the input.
		$this->validateString( __FUNCTION__, 'contentElementsUrl', $contentElementsUrl );
		
		// Compose the REST path:
		$path = $this->composeProducerUrl( $contentElementsUrl );
		
		// Call the REST service.
		$request = $this->composeRequest( $path, json_encode($elements), Zend_Http_Client::PUT );
		$request['headers']['Content-Type'] = 'application/json; charset=utf-8';
		$httpClient = $this->composeClient( $request );
		$this->executeRequest( $httpClient, $request, 'updateEntityContentElementsHandleResponse', __FUNCTION__ );
	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by updateEntityContentElements().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 *
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function updateEntityContentElementsHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200:
				break;
			case 409: // Version conflict - specified version is not the latest.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}
	
	/**
	 * Retrieves the manifest of all committed assets associated with the specified version of content bucket for this entity.
	 *
	 * GET /{publicationID}/{entityType}/{entityName}[;{version}]/contents/
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param string $entityType The type of the entity, used in the REST path.
	 * @param string $entityName The name of the entity, used in the REST path.
	 * @param string $contentVersion The version of the content for which to perform the action.
	 * @return array Returns the HTTP body.
	 *
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
//	public function getContentManifest( $dpsPublicationId, $entityType, $entityName, $contentVersion )
//	{
//		require_once 'Zend/Http/Client.php';
//
//		// Validate the input.
//		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
//		$this->validateString( __FUNCTION__, 'entityType', $entityType );
//		$this->validateString( __FUNCTION__, 'entityName', $entityName );
//		$this->validateString( __FUNCTION__, 'contentVersion', $contentVersion );
//
//		// Compose the REST path:
//		$path = 'publication/' . $dpsPublicationId . '/' . $entityType . '/' . $entityName.
//				'/contents;contentVersion=' . $contentVersion . '/';
//		$path = $this->composeProducerUrl( $path );
//
//		// Call the REST service.
//		$body = null;
//		$request = $this->composeRequest( $path, $body, Zend_Http_Client::GET );
//		$httpClient = $this->composeClient( $request );
//		$httpBody = $this->executeRequest( $httpClient, $request, 'getContentManifestHandleResponse', __FUNCTION__ );
//
//		$httpBody = json_decode( $httpBody );
//		return $httpBody;
//	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by getContentManifest().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
//	protected function getContentManifestHandleResponse( $request, $httpCode, $responseBody )
//	{
//		switch( $httpCode ) {
//			case 200:
//				break;
//			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body.
//				$this->throwBadRequest400( $request, $httpCode, $responseBody );
//				break;
//			case 403: // Forbidden - user's quota exceeded.
//				// TODO: Set a proper error message.
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//			case 409: // Version conflict - specified version is not the latest.
//				// TODO: Set a proper error message.
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//		}
//		return $responseBody;
//	}

	/**
	 * Retrieves the committed asset specified by the content path.
	 *
	 * GET /{publicationID}/{entityType}/{entityName}[;{version}]/contents/{contentPath}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param string $entityType The type of the entity, used in the REST path.
	 * @param string $entityName The name of the entity, used in the REST path.
	 * @param string $remoteFilePath The aimed at path at the Adobe DPS side.
	 * @param string $contentVersion The version of the content for which to perform the action.
	 *
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
//	public function getEntityContent( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion )
//	{
//		require_once 'Zend/Http/Client.php';
//
//		// Validate the input.
//		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
//		$this->validateString( __FUNCTION__, 'entityType', $entityType );
//		$this->validateString( __FUNCTION__, 'entityName', $entityName );
//		$this->validateString( __FUNCTION__, 'remoteFilePath', $remoteFilePath );
//		$this->validateString( __FUNCTION__, 'contentVersion', $contentVersion );
//
//		// Compose the REST path:
//		$path = 'publication/' . $dpsPublicationId . '/' . $entityType . '/' . $entityName .
//				'/contents;contentVersion=' . $contentVersion . '/' . $remoteFilePath;
//		$path = $this->composeProducerUrl( $path );
//
//		// Call the REST service.
//		$body = null;
//		$request = $this->composeRequest( $path, $body, Zend_Http_Client::GET );
//		$httpClient = $this->composeClient( $request );
//		$this->executeRequest( $httpClient, $request, 'getEntityContentHandleResponse', __FUNCTION__ );
//	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by getEntityContent().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
//	protected function getEntityContentHandleResponse( $request, $httpCode, $responseBody )
//	{
//		switch( $httpCode ) {
//			case 200: // OK - Successfully retrieved metadata
//				break;
//			case 404: // Specified content was not found
//				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//	    }
//		return $responseBody;
//	}

	/**
	 * Deletes existing content as a pending change from an entity's content bucket.
	 *
	 * DELETE /{publicationID}/{entityType}/{entityName}[;{version}]/contents/{contentPath}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param string $entityType The type of the entity, used in the REST path.
	 * @param string $entityName The name of the entity, used in the REST path.
	 * @param string $remoteFilePath The aimed at path at the Adobe DPS side.
	 * @param string $contentVersion The version of the content for which to perform the action.
	 *
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
//	public function deleteContent( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion )
//	{
//		require_once 'Zend/Http/Client.php';
//
//		// Validate the input.
//		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
//		$this->validateString( __FUNCTION__, 'entityType', $entityType );
//		$this->validateString( __FUNCTION__, 'entityName', $entityName );
//		$this->validateString( __FUNCTION__, 'remoteFilePath', $remoteFilePath );
//		$this->validateString( __FUNCTION__, 'contentVersion', $contentVersion );
//
//		// Compose the REST path:
//		$path = 'publication/' . $dpsPublicationId . '/' . $entityType . '/' . $entityName .
//				'/contents;contentVersion=' . $contentVersion . '/' . $remoteFilePath;
//		$path = $this->composeProducerUrl( $path );
//
//		// Call the REST service.
//		$body = null;
//		$request = $this->composeRequest( $path, $body, Zend_Http_Client::DELETE );
//		$request['headers']['Content-Type'] = 'application/json; charset=utf-8';
//		$httpClient = $this->composeClient( $request );
//		$this->executeRequest( $httpClient, $request, 'deleteContentHandleResponse', __FUNCTION__ );
//	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by getEntityContent().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
//	protected function deleteContentHandleResponse( $request, $httpCode, $responseBody )
//	{
//		switch( $httpCode ) {
//			case 204: // OK - Successfully deleted content
//				break;
//			case 403: //Forbidden - ObjectLocked: another user has the entity locked
//				// TODO: handle error.
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//			case 404: // Not Found - specified content path does not exist
//				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
//				break;
//			case 409: //Version conflict - specified version is not the latest.
//				// TODO: handle error.
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//		}
//		return $responseBody;
//	}

	/**
	 * Retrieves the header information only of a committed asset specified by the content path.
	 *
	 * HEAD /{publicationID}/{entityType}/{entityName}[;{version}]/contents/{contentPath}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param string $entityType The type of the entity, used in the REST path.
	 * @param string $entityName The name of the entity, used in the REST path.
	 * @param string $remoteFilePath The aimed at path at the Adobe DPS side.
	 * @param string $contentVersion The version of the content for which to perform the action.
	 *
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
//	public function headEntityContent( $dpsPublicationId, $entityType, $entityName, $remoteFilePath, $contentVersion )
//	{
//		require_once 'Zend/Http/Client.php';
//
//		// Validate the input.
//		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
//		$this->validateString( __FUNCTION__, 'entityType', $entityType );
//		$this->validateString( __FUNCTION__, 'entityName', $entityName );
//		$this->validateString( __FUNCTION__, 'remoteFilePath', $remoteFilePath );
//		$this->validateString( __FUNCTION__, 'contentVersion', $contentVersion );
//
//		// Compose the REST path:
//		$path = 'publication/' . $dpsPublicationId . '/' . $entityType . '/' . $entityName .
//				'/contents;contentVersion=' . $contentVersion . '/' . $remoteFilePath;
//		$path = $this->composeProducerUrl( $path );
//
//		// Call the REST service.
//		$body = null;
//		$request = $this->composeRequest( $path, $body, Zend_Http_Client::HEAD );
//		$request['headers']['Content-Type'] = 'application/json; charset=utf-8';
//		$httpClient = $this->composeClient( $request );
//		$this->executeRequest( $httpClient, $request, 'headEntityContentHandleResponse', __FUNCTION__ );
//	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by headEntityContent().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
//	protected function headEntityContentHandleResponse( $request, $httpCode, $responseBody )
//	{
//		switch( $httpCode ) {
//			case 200: // Headers returned for the entity's specified content path.
//				break;
//			case 404: // Specified content was not found.
//				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//		}
//		return $responseBody;
//	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Entity API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Retrieves metadata for the publication entity at the specified version
	 *
	 * GET /{publicationID}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param string|null $version Publication version requested; head version if not specified.
	 * @throws BizException When operation could not be executed properly.
	 */
//	public function getPublicationMetadata( $dpsPublicationId, $entityVersion = null )
//	{
//		require_once 'Zend/Http/Client.php';
//
//		// Validate function parameters.
//		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
//		$this->validateStringOrNull( __FUNCTION__, 'entityVersion', $entityVersion );
//
//		// Compose the REST path.
//		$path = $dpsPublicationId;
//		if( $entityVersion ) {
//			$path .= ';version=' . $entityVersion;
//		}
//		$path = $this->composeProducerUrl( $path );
//
//		// Call the REST service.
//		$request = $this->composeRequest( $path, null, Zend_Http_Client::GET );
//		$httpClient = $this->composeClient( $request );
//		$this->executeRequest( $httpClient, $request, 'getPublicationMetadataHandleResponse', 'getPublicationMetadata' );
//	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by createOrUpdateEntity().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
//	protected function getPublicationMetadataHandleResponse( $request, $httpCode, $responseBody )
//	{
//		switch( $httpCode ) {
//			case 200: // OK - Successfully retrieved metadata
//				break;
//			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body
//				$this->throwBadRequest400( $request, $httpCode, $responseBody );
//				break;
//			case 404: // Not Found - Specified entity was not found
//				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
//				break;
//			case 410: // Gone - Specified entity was deleted
//				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//		}
//		return $responseBody;
//	}

	/**
	 * Retrieves a paged list of all entities of a given type in the specified publication.
	 *
	 * GET /{publicationID}/{entityType}
	 *
	 * @throws BizException When operation could not be executed properly.
	 */
//	public function getAllEntitiesMetadata()
//	{
//		// Only implement when needed.
//		throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Not implemented yet.' );
//	}

	/**
	 * Create a new entity or update an existing entity in a production-safe manner.
	 *
	 * It grabs the latest entity version through getEntityMetadata(). Then it creates or updates
	 * the entity by calling createOrUpdateEntity(). When the entity was already created a split
	 * second before, it re-uses that entity. When there is a version conflict due to other 
	 * Server Jobs that may have created version in the meantime, it retries the whole scenario.
	 * Only when if fails after 5 attempts, it will give up an bail out with the last error.
	 *
	 * For collections, you may want to provide the $callbackForContentElements parameter
	 * which triggers the safeCreateOrUpdateEntity() function to retrieve the content elements 
	 * for the collection as well. The provided callback function should add/remove wanted/unwanted 
	 * elements (e.g. articles) in memory. When it returns a list of elements (links), the 
	 * safeCreateOrUpdateEntity() function continues and updates the elements for the collection 
	 * in Adobe. When NULL is returned, this update is skipped (optimization).
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param AdobeDps2_DataClasses_EntityCommon $metadata Used to compose request. Gets updated with response data.
	 * @param callable $callbackForSetProps Called back when it is time to set the wanted entity properties.
	 * @param callable $callbackForContentElements Called back when it is time to update the content elements.
	 * @throws BizException When operation could not be executed properly.
	 */
	public function safeCreateOrUpdateEntity( $dpsPublicationId, AdobeDps2_DataClasses_EntityCommon $metadata, 
		$callbackForSetProps, $callbackForContentElements=null ) 
	{
		// It could happen that the below fails because we may not the very latest entity version
		// in our hands. In case of common production errors, those are solved automatically
		// and silently in the loop below. For example, when creating/updating Collections, 
		// there could be many Server Jobs working on the same Collection in parallel. 
		// When job A grabs the latest, a split second later, job B could grab the latest 
		// and update the Collection just before job A does. Then job B is successful but
		// job A ends up with a version conflict error (HTTP 409) thrown by Adobe DPS.
		// By waiting for a random time, grabbing the latest again, settting the wanted Collection
		// properties again and updating the Collection, we try to solve the problem. This
		// we try for 5 attempts before we bail out.
		//
		// Why not using a Semaphore? The problem with semaphores is that they are blocking.
		// During workflow production, a certain Issue will be 'hot'; Many people are working
		// on that Issue. That means, ALL uploads will be done under the very same Collection.
		// That Collection will be updated many times, which comes with a significant risk
		// creating an upload bottleneck. When jobs start to line-up waiting for each other
		// it becomes impossible to determine a max wait time in seconds before giving up.
		// And, in the eyes of the end-users, it would cause 'random' delays uploading articles. 
		$attempts = 1;
		do {
			// First, we retrieve the head/latest entity version from Adobe DPS. 
			// Exception: 
			// - When using predictable entity names, it could happen that another Server Job 
			//   has created the entity in the meantime. So even when we think to create a new
			//   entity, it could happen that it was created one split second ago. Therefore
			//   we still do a getEntityMetadata() and handle the HTTP 404 error as expected.
			$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO' )); // see catch below
			$exists = false;
			try {
				$metadata->version = null; // Make sure to always get the head version.
				$this->getEntityMetadata( $dpsPublicationId, $metadata ); // $metadata gets updated on success
				$exists = true;
				if( LogHandler::debugMode() ) {
					LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Returned by getEntityMetadata(): '.print_r($metadata,true) );
				}
			} catch( BizException $e ) {
				if( $e->getErrorCode() == 'S1029' ) { // ERR_NOTFOUND, happens on HTTP 404 (not found) or HTTP 410 (gone)
					LogHandler::Log( 'AdobeDps2', 'INFO', "The {$metadata->entityType} entity {$metadata->entityName} was not found in Adobe DPS." );
				} else {
					throw $e; // unexpected, re-throw
				}
			}
		
			// Callback the closure function of the caller to (re)populate the metadata properties.
			$createOrUpdate = $callbackForSetProps( $metadata, $exists ); // $metadata gets updated by callee

			// Create or update the entity in Adobe DPS to reflect the new property values. 
			// Exception:
			// - This may raise a version conflict (HTTP 409) in case we do not have the
			//   latest version. This will be solved in the next attempt.
			if( $createOrUpdate ) {
				$done = false; // something more to do, so not done yet
				$map = new BizExceptionSeverityMap( array( 'version conflict' => 'INFO' )); // see catch below
				try {
					$this->createOrUpdateEntity( $dpsPublicationId, $metadata ); // $metadata gets updated on success
					$done = true;
					if( LogHandler::debugMode() ) {
						LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Returned by createOrUpdateEntity(): '.print_r($metadata,true) );
					}
				} catch( BizException $e ) {
					if( $e->getErrorCode() == 'version conflict' ) { // HTTP 409
						LogHandler::Log( 'AdobeDps2', 'INFO', "It turned out that we did ".
							"not have the very latest version of {$metadata->entityType} entity ".
							"{$metadata->entityName}, so Adobe DPS raised a version conflict ".
							"error so we will try again after grabbing the head version." );
					} else {
						throw $e; // unexpected, re-throw
					}
				}
			} else {
				$done = true;
			}
			
			$elements = null;
			if( $done && $callbackForContentElements ) {
				$done = false; // something more to do, so not done yet
				$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO' )); // see catch below
				try {
					if( isset($metadata->_links->contentElements->href) ) {
						$elements = $this->getEntityContentElements( $metadata->_links->contentElements->href );
					} else {
						$elements = array();
					}
					$done = true;
					if( LogHandler::debugMode() ) {
						LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Returned by getEntityContentElements(): '.print_r($elements,true) );
					}
				} catch( BizException $e ) {
					if( $e->getErrorCode() == 'S1029' ) { // ERR_NOTFOUND, happens on HTTP 404 (not found) or HTTP 410 (gone)
						LogHandler::Log( 'AdobeDps2', 'INFO', "The {$metadata->entityType} entity {$metadata->entityName} was not found in Adobe DPS." );
					} else {
						throw $e; // unexpected, re-throw
					}
				}
				
				$elements = $callbackForContentElements( $metadata, $elements );
			}
			
			if( $done && !is_null($elements) ) {
				$done = false; // something more to do, so not done yet
				$map = new BizExceptionSeverityMap( array( 'version conflict' => 'INFO' )); // see catch below
				try {
					if( isset($metadata->_links->contentElements->href) ) {
						$this->updateEntityContentElements( $metadata->_links->contentElements->href, $elements );
					}
					$done = true;
				} catch( BizException $e ) {
					if( $e->getErrorCode() == 'version conflict' ) { // HTTP 409
						LogHandler::Log( 'AdobeDps2', 'INFO', "It turned out that we did ".
							"not have the very latest version of {$metadata->entityType} entity ".
							"{$metadata->entityName}, so Adobe DPS raised a version conflict ".
							"error so we will try again after grabbing the head version." );
					} else {
						throw $e; // unexpected, re-throw
					}
				}
			}
			
			// When failed, sleep 1-5 seconds before trying again in the next loop.
			// This is done to act different than another Server Job that may have
			// failed for the exact same reason; That increases the chance for success
			// in the next attempt.
			if( !$done ) {
				if( $attempts <= 5 ) {
					sleep( rand( 1, 5 ) ); 
					$attempts += 1;
				} else {
					if( isset($e) ) { // $e should always be set here
						throw $e;
					}
					break; // should never reach here
				}
			}
 		} while( !$done );
	}
	
	/**
	 * Create a new entity or update an existing entity.
	 *
	 * PUT /{publicationID}/{entityType}/{entityName}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param AdobeDps2_DataClasses_EntityCommon $metadata Used to compose request. Gets updated with response data.
	 * @throws BizException When operation could not be executed properly.
	 */
	public function createOrUpdateEntity( $dpsPublicationId, AdobeDps2_DataClasses_EntityCommon $metadata ) 
	{
		require_once 'Zend/Http/Client.php';

		// Validate function parameters.
		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
		$this->validateString( __FUNCTION__, 'metadata->entityType', $metadata->entityType );
		$this->validateString( __FUNCTION__, 'metadata->entityName', $metadata->entityName );
		$this->validateStringOrNull( __FUNCTION__, 'metadata->version', $metadata->version );
		
		// Compose the REST path (HTTP URL).
		$path = 'publication/' . $dpsPublicationId . '/' . $metadata->entityType . '/' . $metadata->entityName;
		if( $metadata->version ) {
			$path .= ';version=' . $metadata->version;
		}
		$path = $this->composeProducerUrl( $path );
		
		// Convert metadata into an array whereby nullified props are entirely left out.
		$requestProps = array();
		foreach( array_keys( get_class_vars( get_class($metadata) ) ) as $propName ) {
			if( !is_null($metadata->$propName) ) {
				$requestProps[$propName] = $metadata->$propName;
			}
		}
		
		// Call the REST service.
		$request = $this->composeRequest( $path, json_encode($requestProps), Zend_Http_Client::PUT );
		$request['headers']['Content-Type'] = 'application/json; charset=utf-8';
		$httpClient = $this->composeClient( $request );
		$responseBody = $this->executeRequest( $httpClient, $request, 'createOrUpdateEntityHandleResponse', __FUNCTION__ );
		
		// Update the given metadata structure with the response data.
		$responseProps = json_decode( $responseBody );
		foreach( array_keys( get_class_vars( get_class($metadata) ) ) as $propName ) {
			if( array_key_exists( $propName, $responseProps ) ) {
				$metadata->$propName = $responseProps->$propName;
			}
		}
	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by createOrUpdateEntity().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function createOrUpdateEntityHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200: // OK - Successfully updated an existing entity; 'Link' response header with rel="latest-version" will be the updated entity.	
				break;
			case 201: // Created - Successfully created a new entity; 'Location' response header will be the newly created entity.	
				break;
			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body	
				$this->throwBadRequest400( $request, $httpCode, $responseBody );
				break;
			case 403: // Forbidden - user's quota exceeded.	
				// TODO: implement correct exception.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			case 409: // Conflict - specified version is not the latest.
				$this->throwVersionConflict( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}

	/**
	 * Marks the head version of an entity as deleted.
	 *
	 * DELETE /{publicationID}/{entityType}/{entityName}
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param string $entityType The type of the entity, used in the REST path.
	 * @param string $entityName The name of the entity, used in the REST path.
	 * @param string $entityVersion An opaque string defining the entity version.
	 * @throws BizException When operation could not be executed properly.
	 */
//	public function deleteEntity( $dpsPublicationId, $entityType, $entityName, $entityVersion = null )
//	{
//		require_once 'Zend/Http/Client.php';
//
//		// Validate function parameters.
//		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
//		$this->validateString( __FUNCTION__, 'entityType', $entityType );
//		$this->validateString( __FUNCTION__, 'entityName', $entityName );
//		$this->validateString( __FUNCTION__, 'version', $entityVersion );
//
//		// Compose the REST path.
//		$path = 'publication/' . $dpsPublicationId . '/' . $entityType . '/' . $entityName;
//		if( $entityVersion ) {
//			$path .= ';version=' . $entityVersion;
//		}
//		$path = $this->composeProducerUrl($path );
//
//		// Call the REST service.
//		$request = $this->composeRequest( $path, null, Zend_Http_Client::DELETE );
//		$request['headers']['Content-Type'] = 'application/json; charset=utf-8';
//		$httpClient = $this->composeClient( $request );
//		$this->executeRequest( $httpClient, $request, 'deleteEntityHandleResponse', 'deleteEntity' );
//	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by deleteEntity().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
//	protected function deleteEntityHandleResponse( $request, $httpCode, $responseBody )
//	{
//		switch( $httpCode ) {
//			case 204: // OK - Successfully deleted entity
//				break;
//			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body
//				$this->throwBadRequest400( $request, $httpCode, $responseBody );
//				break;
//			case 404: // Not Found - specified entityName does not exist
//				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
//				break;
//			case 409: // Conflict - specified version is not the latest.
//				// TODO: implement correct exception.
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//		}
//		return $responseBody;
//	}

// Commented out. May be used by AdobeDps2_BizClasses_Publishing::sendEmailWhenLayoutMovesToOtherIssue() in future.
// 	/**
// 	 * Retrieves the latest version of an entity from Adobe DPS.
// 	 *
// 	 * @param string $dpsPublicationId
// 	 * @param AdobeDps2_DataClasses_EntityCommon $entity
// 	 * @return AdobeDps2_DataClasses_EntityCollection|null The entity, or NULL when not found.
// 	 * @throws BizException on unexpected error from Adobe DPS.
// 	 */
// 	public function safeGetEntityMetadata( $dpsPublicationId, $entity )
// 	{
// 		$map = new BizExceptionSeverityMap( array( 'S1029' => 'INFO' ) ); // see catch below
// 		$exists = false;
// 		try {
// 			$this->getEntityMetadata( $dpsPublicationId, $entity ); // $dpsArticle gets updated on success
// 			if( LogHandler::debugMode() ) {
// 				LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Entity returned by getEntityMetadata(): '.print_r($entity,true) );
// 			}
// 			$exists = true;
// 		} catch( BizException $e ) {
// 			if( $e->getErrorCode() == 'S1029' ) { // ERR_NOTFOUND, happens on HTTP 404 (not found) or HTTP 410 (gone)
// 				LogHandler::Log( 'AdobeDps2', 'INFO', 'The entity could no longer be found in Adobe DPS. ' );
// 			} else {
// 				throw $e; // unexpected, re-throw
// 			}
// 		}
// 		return $exists;
// 	}
	
	/**
	 * Retrieves metadata for the specified entity at the specified version. Takes the head version if no version specified.
	 *
	 * GET /{publicationID}/{entityType}/{entityName}[;{version}]
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param AdobeDps2_DataClasses_EntityCommon $metadata Used to compose request. Gets updated with response data.
	 * @throws BizException When operation could not be executed properly.
	 */
	public function getEntityMetadata( $dpsPublicationId, AdobeDps2_DataClasses_EntityCommon $metadata )
	{
		require_once 'Zend/Http/Client.php';

		// Validate function parameters.
		$this->validateString( __FUNCTION__, 'publicationID', $dpsPublicationId );
		$this->validateString( __FUNCTION__, 'metadata->entityType', $metadata->entityType );
		$this->validateString( __FUNCTION__, 'metadata->entityName', $metadata->entityName );
		$this->validateStringOrNull( __FUNCTION__, 'metadata->version', $metadata->version );
		
		// Compose the REST path.
		$path = 'publication/' . $dpsPublicationId . '/' . $metadata->entityType . '/' . $metadata->entityName;
		if( $metadata->version ) {
			$path .= ';version=' . $metadata->version;
		}
		$path = $this->composeProducerUrl($path );
		
		// Call the REST service.
		$request = $this->composeRequest( $path, null, Zend_Http_Client::GET );
		$httpClient = $this->composeClient( $request );
		$responseBody = $this->executeRequest( $httpClient, $request, 'getEntityMetadataHandleResponse', 'getEntityMetadata' );

		// Update the given metadata structure with the response data.
		$responseProps = json_decode( $responseBody );
		foreach( array_keys( get_class_vars( get_class($metadata) ) ) as $propName ) {
			if( array_key_exists( $propName, $responseProps ) ) {
				$metadata->$propName = $responseProps->$propName;
			}
		}
	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by getEntityMetadata().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function getEntityMetadataHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200: // OK - Successfully retrieved entity's metadata	
				break;
			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body	
				$this->throwBadRequest400( $request, $httpCode, $responseBody );
				break;
			case 404: // Not Found - Specified entity was not found	
				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
				break;
			case 410: // Gone - Specified entity was deleted	
				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}
	
	/**
	 * Commits a set of uploads to the entity's content bucket.
	 *
	 * PUT /{publicationID}/{entityType}/{entityName}[;{version}]/contents/
	 *
	 * See createOrUpdateContent() / uploadFullArticle() for uploading assets. This call 
	 * follows after the uploads to commit the content to the bucket.
	 *
	 * @param string $dpsPublicationId Reverse DNS publication identifier, used in the REST path.
	 * @param AdobeDps2_DataClasses_EntityCommon $metadata Used to compose request. Gets updated with response data.
	 * @param string $dpsUploadGuid A GUID to uniquely identify the uploaded files and to group multiple uploads together.
	 *
	 * @throws BizException Throws an exception if the input variables are invalid.
	 */
	public function commitEntityContents( $dpsPublicationId, AdobeDps2_DataClasses_EntityCommon $metadata, $dpsUploadGuid )
	{
		require_once 'Zend/Http/Client.php';

		// Validate the input.
		$this->validateString( __FUNCTION__, 'apPublicationId', $dpsPublicationId );
		$this->validateString( __FUNCTION__, 'metadata->entityType', $metadata->entityType );
		$this->validateString( __FUNCTION__, 'metadata->entityName', $metadata->entityName );
		$this->validateString( __FUNCTION__, 'metadata->version', $metadata->version );
		$this->validateGuid( __FUNCTION__, 'apUploadGuid', $dpsUploadGuid );

		// Compose the REST path:
		$path = 'publication/' . $dpsPublicationId . '/' . $metadata->entityType . '/' . 
				$metadata->entityName . ';version=' . $metadata->version . '/contents/';
		$path = $this->composeProducerUrl( $path );

		// Call the REST service.
		$body = null;
		$request = $this->composeRequest( $path, $body, Zend_Http_Client::PUT );
		$request['headers']['X-DPS-Upload-Id'] = $dpsUploadGuid;
		$request['headers']['Content-Type'] = 'application/json; charset=utf-8';
		$httpClient = $this->composeClient( $request );
		$responseBody = $this->executeRequest( $httpClient, $request, 'commitEntityContentsHandleResponse', __FUNCTION__ );
		
		// Update the given metadata structure with the response data.
		$responseProps = json_decode( $responseBody );
		foreach( array_keys( get_class_vars( get_class($metadata) ) ) as $propName ) {
			if( array_key_exists( $propName, $responseProps ) ) {
				$metadata->$propName = $responseProps->$propName;
			}
		}
	}

	/**
	 * Handles the response for the Adobe DPS service (REST) called by commitEntityContents().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function commitEntityContentsHandleResponse( $request, $httpCode, $responseBody )
	{
		// According to the API docs this call does not have a return value, checking if the file was uploaded correctly
		// should be done by fetching the manifest and checking if the files are present. So for now handle the 200 and
		// any unknown http codes.

		switch( $httpCode ) {
			case 200: // Presumably successful.
				break;
			case 400: // Bad Request - one of the parameters was invalid; more detail in the response body.
				$this->throwBadRequest400( $request, $httpCode, $responseBody );
				break;
			case 403: // Forbidden - user's quota exceeded.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			case 409: // Version conflict - specified version is not the latest.
				// TODO: Set a proper error message.
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// IMS/AMAS API calls (for Authentication/Autorization).
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Handles authentication for Adobe DPS.
	 *
	 * POST /ims/token/v1
	 *
	 * @param string $deviceToken The Device Token.
	 * @param string $deviceId The Device Id.
	 * @throws BizException When operation could not be executed properly.
	 */
	public function getToken( $deviceToken, $deviceId )
	{
		require_once 'Zend/Http/Client.php';
		
		$consumerKey = $this->getConsumerKey();
		$consumerSecret = $this->getConsumerSecret();
		
		// Validate parameters.
		$this->validateString( __FUNCTION__, 'consumerKey', $consumerKey );
		$this->validateString( __FUNCTION__, 'consumerSecret', $consumerSecret );
		$this->validateString( __FUNCTION__, 'deviceToken', $deviceToken );
		$this->validateString( __FUNCTION__, 'deviceId', $deviceId );

		// Compose the REST path.
		$path = 'ims/token/v1'.
				'?grant_type=device'.
				'&client_id='.urlencode($consumerKey).
				'&client_secret='.urlencode($consumerSecret).
				'&scope=openid'.
				'&device_token='.urlencode($deviceToken).
				'&device_id='.urlencode($deviceId);
		$path = $this->composeAuthenticationUrl( $path );

		// Call the REST service.
		$request = $this->composeRequest( $path, null, Zend_Http_Client::POST );
		$request['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
		
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'getTokenHandleResponse', 'getToken' );

		// A successful call will return the following response body with the Access Token
		// that can be used to make successing calls to the Producer- and Ingestion servers:
		// {"token_type":"bearer","expires_in":86399968,"access_token":"ACCESS_TOKEN"}
		$httpBodyJson = json_decode( $httpBody );
		if( is_null( $httpBodyJson ) ) {
			$this->throwUnexpected( $request, 200, $httpBody );
		}

		// Remember the Access Token for succeeding API calls.
		$this->accessToken = $httpBodyJson->access_token;
	}
	
	/**
	 * Handles the response for the Adobe DPS service (REST) called by getToken().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function getTokenHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200: // Successfully received Access Token.
				break;
			case 400: // Adobe seems to return 400, while 401 would make more sense, so catch both.
			case 401:
				$responseJson = json_decode( $responseBody );
				if( $responseJson && $responseJson->error == 'access_denied' ) {
					$this->throwAccessDenied( $request, $httpCode, $responseBody );
				} else {
					$this->throwUnexpected( $request, $httpCode, $responseBody );
				}
				break;
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}
	
	/**
	 * Handles authorization for Adobe DPS.
	 *
	 * GET /permissions
	 *
	 * @return object[] List of AP publications for which the user has add/edit rights. List is indexed by publication name.
	 * @throws BizException When operation could not be executed properly.
	 */
	public function getPermissions()
	{
		require_once 'Zend/Http/Client.php';

		// Compose the REST path.
		$path = 'permissions';
		$path = $this->composeAuthorizationUrl( $path );

		// Call the REST service.
		$request = $this->composeRequest( $path, null, Zend_Http_Client::GET );
		
		$httpClient = $this->composeClient( $request );
		$httpBody = $this->executeRequest( $httpClient, $request, 'getPermissionsHandleResponse', 'getPermissions' );
		
		// A successful call will return a response body that contains AP publications (projects)
		// for which the user has access rights. We collect the ids of only those AP publications 
		// for which the user is allowed to add/edit content.
		$publications = array();
		$httpBody = json_decode( $httpBody );
		if( !is_null( $httpBody ) ) {
			if( $httpBody->masters ) foreach( $httpBody->masters as $master ) {
				$hasMasterAccess = $this->hasEditPermission( $master->permissions );
				if( $master->publications ) foreach( $master->publications as $publication ) { // projects
					if( $hasMasterAccess || $this->hasEditPermission( $publication->permissions ) ) {
						$publications[$publication->name] = $publication;
					}
				}
			}
		}
		return $publications;
	}
	
	/**
	 * Whether or not a list of permissions gives the user add/edit content access.
	 *
	 * @param string[] $checkPerms Permissions to be checked.
	 * @return boolean TRUE when access allowed, FALSE when not.
	 */
	private function hasEditPermission( $checkPerms )
	{
		static $editPerms = array( 'producer_content_add', 'master_admin', 'publication_admin' );
		return 
			$checkPerms && is_array($checkPerms) && 
			count( array_intersect( $editPerms, $checkPerms ) ) > 0;
	}
	
	/**
	 * Handles the response for the Adobe DPS service (REST) called by getPermissions().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function getPermissionsHandleResponse( $request, $httpCode, $responseBody )
	{
		switch( $httpCode ) {
			case 200: // Successfully started or scheduled the publish
				break;
			case 400: // Bad Request - if any of the required parameters are missing.
				$this->throwBadRequest400( $request, $httpCode, $responseBody );
				break;
			case 401: // Unauthorized - if the provided token is expired or invalid.
				$this->throwAccessDenied( $request, $httpCode, $responseBody );
				break;
			// case 404: // Not Found - if the user is not a DPS user or does not have access to any resources (code - 4041).
			// case 503: // Service Unavailable - if any of the third party services are unavailable.
			default:
				$this->throwUnexpected( $request, $httpCode, $responseBody );
				break;
		}
		return $responseBody;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Publish API calls.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Publish an entity or set of entities
	 *
	 * POST /job
	 *
	 * @param stdClass[] $entities
	 * @param integer|null $scheduled Time at which to schedule publication of entities (in milliseconds (GMT))
	 * @return string The publish workflow id. Can be used to track progress of the publish operation.
	 * @throws BizException When operation could not be executed properly.
	 */
//	public function publishEntities( array $entities, $scheduled = null )
//	{
//		require_once 'Zend/Http/Client.php';
//
//		// Validate function parameters.
//		$this->validateIntegerOrNull( __FUNCTION__, 'scheduled', $scheduled );
//
//		foreach( $entities as $index => $entity ) {
//			$prefix = '$entities['.$index.']->';
//			$this->validateString( __FUNCTION__, $prefix.'publicationId', $entity->publicationId );
//			$this->validateString( __FUNCTION__, $prefix.'entityType', $entity->entityType );
//			$this->validateString( __FUNCTION__, $prefix.'entityName', $entity->entityName );
//			$this->validateString( __FUNCTION__, $prefix.'version', $entity->version );
//			$this->validateString( __FUNCTION__, $prefix.'relativeEntityUrl', $entity->relativeEntityUrl );
//		}
//
//		// Compose the HTTP body.
//		$body = new stdClass();
//		$body->workflowType = 'publish';
//		$body->scheduled = $scheduled ? strval($scheduled) : '';
//		$body->entities = $entities;
//
//		// Call the REST service.
//		$path = $this->composeProducerUrl( 'job' );
//		$request = $this->composeRequest( $path, json_encode($body), Zend_Http_Client::POST );
//		$request['headers']['Content-Type'] = 'application/json; charset=utf-8';
//		$httpClient = $this->composeClient( $request );
//		$httpBody = $this->executeRequest( $httpClient, $request, 'publishEntitiesHandleResponse', 'publishEntities' );
//
//		// A successful call will return the following response body with the workflowId
//		// that can be used to track the progress of the publish (the design for the API
//		// that provides progress of publish is going to be worked out post J33)
//		// { "publishWorkflowId": "9c8c0ce9-f1d3-438b-a563-2e343b10d23c" }
//		$httpBody = json_decode( $httpBody );
//		return ( !is_null( $httpBody ) ) ? $httpBody->publishWorkflowId : '';
//	}
	
	/**
	 * Handles the response for the Adobe DPS service (REST) called by publishEntities().
	 *
	 * @param string[] $request List of request params.
	 * @param integer $httpCode
	 * @param string $responseBody
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
//	protected function publishEntitiesHandleResponse( $request, $httpCode, $responseBody )
//	{
//		switch( $httpCode ) {
//			case 200: // Successfully started or scheduled the publish
//				break;
//			case 400: // Bad Request - one of the parameters in request body was invalid; more detail in the response body
//				$this->throwBadRequest400( $request, $httpCode, $responseBody );
//				break;
//			case 403: // Forbidden - user's quota exceeded
//				// TODO: implement correct exception.
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//			case 404: // Entity not found
//				$this->throwEntityNotFound( $request, $httpCode, $responseBody );
//				break;
//			case 409: // Version conflict - specified version is not the latest
//				// TODO: implement correct exception.
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//			default:
//				$this->throwUnexpected( $request, $httpCode, $responseBody );
//				break;
//		}
//		return $responseBody;
//	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Connection validation functions.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	/**
	 * Checks whether or not the authorization URL can be used to reach Adobe DPS web services.
	 *
	 * @param integer $curlErrNr 
	 * @return bool
	 */
	public function authenticationConnectionCheck( &$curlErrNr )
	{
		return $this->connectionCheck( $this->authenticationUrl, $curlErrNr );
	}

	/**
	 * Checks whether or not the authorization URL can be used to reach Adobe DPS web services.
	 *
	 * @param integer $curlErrNr 
	 * @return bool
	 */
	public function authorizationConnectionCheck( &$curlErrNr )
	{
		return $this->connectionCheck( $this->authorizationUrl, $curlErrNr );
	}

	/**
	 * Checks whether or not the producer URL can be used to reach Adobe DPS web services.
	 *
	 * @param integer $curlErrNr 
	 * @return bool
	 */
	public function producerConnectionCheck( &$curlErrNr )
	{
		return $this->connectionCheck( $this->producerUrl, $curlErrNr );
	}

	/**
	 * Checks whether or not the ingestion URL can be used to reach Adobe DPS web services.
	 *
	 * @param integer $curlErrNr
	 * @return bool
	 */
	public function ingestionConnectionCheck( &$curlErrNr )
	{
		return $this->connectionCheck( $this->ingestionUrl, $curlErrNr );
	}
	
	/**
	 * Check if the given Adobe DPS service url can be reached.
	 *
	 * @param string $url Connection to test.
	 * @param integer $curlErrNr
	 * @return bool
	 */
	private function connectionCheck( $url, &$curlErrNr )
	{
		$httpClient = $this->createHttpClient( $url );
		try {
			$httpClient->request();
		} catch ( Exception $e ) {
			$adapter = $httpClient->getAdapter();
			if ( $adapter instanceof Zend_Http_Client_Adapter_Curl ) {
				$curl = $adapter->getHandle();
				$curlErrNr = curl_errno($curl);
			}
			return false;
		}
		return true;
	}	

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Specific HTTP error handling functions.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	/**
	 * Handles the error "HTTP 400 Bad Request". 
	 *
	 * This is an integration error that should never happen.
	 *
	 * @param string[] $request List of request params.
	 * @param string $httpCode
	 * @param string $responseBody
	 * @throws BizException with (S1019) code
	 */
	private function throwBadRequest400( $request, $httpCode, $responseBody )
	{
		$detail = 'Request parameters: '.print_r($request,true).
				' Returned HTTP code: '.$httpCode.
				' HTTP response body: '.print_r($responseBody,true);
		$errors = array( 
			BizResources::localize( 'ERR_INVALID_OPERATION' ), 
			'Bad request sent to Adobe DPS.', 
			$this->getDpsErrorMessage( $responseBody ), 
			'See Enterprise Server logging for more details.'
		);
		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
	}
		
	/**
	 * Handles the "entity not found" error.
	 *
	 * This could happen when the entity is already deleted in Adobe DPS (but not in Enterprise)
	 * or due to corruption of the ExternalId (tracked in Enterprise) that is unknown to Adobe DPS.
	 *
	 * @param string[] $request List of request params.
	 * @param string $httpCode
	 * @param string $responseBody
	 * @throws BizException with (S1029) code
	 */
	private function throwEntityNotFound( $request, $httpCode, $responseBody )
	{
		$detail = 'Request parameters: '.print_r($request,true).
				' Returned HTTP code: '.$httpCode.
				' HTTP response body: '.print_r($responseBody,true);
		$errors = array( 
			BizResources::localize( 'ERR_NOTFOUND' ), 
			'Entity could not be found in Adobe DPS.', 
			$this->getDpsErrorMessage( $responseBody ), 
			'See Enterprise Server logging for more details.'
		);
		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
	}
		
	/**
	 * Handles the "entity version conflict" error.
	 *
	 * This could happen when the entity is already updated in Adobe DPS after
	 * getting the latest version.
	 *
	 * @param string[] $request List of request params.
	 * @param string $httpCode
	 * @param string $responseBody
	 * @throws BizException with ('version conflict') code
	 */
	private function throwVersionConflict( $request, $httpCode, $responseBody )
	{
		$intro = 'Entity version conflict in Adobe DPS.';
		$seeLog = 'See Enterprise Server logging for more details.';
		$detail = 'Request parameters: '.print_r($request,true).
				' Returned HTTP code: '.$httpCode.
				' HTTP response body: '.print_r($responseBody,true);
		$dpsError = $this->getDpsErrorMessage( $responseBody );
		LogHandler::Log( 'AdobeDps2', 'DEBUG', $intro.' '.$detail );
		throw new BizException( null, 'Server', $intro.' '.$dpsError.' '.$seeLog, 'version conflict' );
	}
		
	/**
	 * Handles an access denied error. 
	 * 
	 * This could be a HTTP 400 throw by Adobe DPS in case of obtaining the 
	 * access token.
	 *
	 * @param string[] $request List of request params.
	 * @param string $httpCode
	 * @param string $responseBody
	 * @throws BizException with (S1019) code
	 */
	private function throwAccessDenied( $request, $httpCode, $responseBody )
	{
		$detail = 'Request parameters: '.print_r($request,true).
				' Returned HTTP code: '.$httpCode.
				' HTTP response body: '.print_r($responseBody,true);
		$errors = array( 
			BizResources::localize( 'ERR_AUTHORIZATION' ), 
			'Access denied for Adobe DPS server.', 
			$this->getDpsErrorMessage( $responseBody ), 
			'See Enterprise Server logging for more details.'
		);
		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
	}
	
	/**
	 * Handles an expected error. 
	 * 
	 * This could be either a HTTP 500 throw by Adobe DPS or another HTTP code
	 * that is not specified for the web service by Adobe DPS. 
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
			'Adobe DPS returned unexpected error.', 
			$this->getDpsErrorMessage( $responseBody ), 
			'See Enterprise Server logging for more details.'
		);
		throw new BizException( null, 'Server', $detail, $this->combineErrorMessages( $errors ) );
	}
	
	/**
	 * Gets the error message from a given response, as returned by Adobe DPS services.
	 *
	 * @param string $responseBody
	 * @return string The error message or code.
	 */
	private function getDpsErrorMessage( $responseBody )
	{
		$details = '';
		$responseObj = json_decode( $responseBody );
		if( isset($responseObj->message) ) {
			$details = $responseObj->message;
		} elseif( isset($responseObj->error) ) {
			$details = $responseObj->error;
		} elseif( isset($responseObj->code) ) {
			$details = $responseObj->code;
		}
		return $details;
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
	 * @param array $curlOptions Optional. Extra parameters for the cURL adapter.
	 * @return string[] List of request params
	 */
	protected function composeRequest( $path, $body, $method, $curlOptions = array() )
	{
		// For every request, Adobe DPS expects an unique identifier.
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$this->requestId = NumberUtils::createGUID();
		
		// Compose the request.
		$request = array();
		$request['url'] = $path;
		$request['body'] = $body;
		$request['method'] = $method;
		$request['headers'] = $this->getCommonHeaders();
		$request['curloptions'] = $curlOptions;
		return $request;
	}
	
	/**
	 * Appends and returns the given REST request path to the authentication URL.
	 *
	 * @param string $path The REST path, relative to the authentication URL.
	 * @return string URL The full REST path, including the authentication URL.
	 */
	protected function composeAuthenticationUrl( $path )
	{
		$path = ltrim( $path, '/' );
		return $this->authenticationUrl.'/'.$path;
	}
	
	/**
	 * Appends and returns the given REST request path to the authorization URL.
	 *
	 * @param string $path The REST path, relative to the authorization URL.
	 * @return string URL The full REST path, including the authorization URL.
	 */
	protected function composeAuthorizationUrl( $path )
	{
		$path = ltrim( $path, '/' );
		return $this->authorizationUrl.'/'.$path;
	}
	
	/**
	 * Appends and returns the given REST request path to the producer URL.
	 *
	 * @param string $path The REST path, relative to the producer URL.
	 * @return string URL The full REST path, including the producer URL.
	 */
	protected function composeProducerUrl( $path )
	{
		$path = ltrim( $path, '/' );
		return $this->producerUrl.'/'.$path;
	}
	
	/**
	 * Appends and returns the given REST request path to the ingestion URL.
	 *
	 * @param string $path The REST path, relative to the ingestion URL.
	 * @return string URL The full REST path, including the ingestion URL.
	 */
	protected function composeIngestionUrl( $path )
	{
		$path = ltrim( $path, '/' );
		return $this->ingestionUrl.'/'.$path;
	}
	
	/**
	 * Constructs a HTTP client class in memory.
	 *
	 * @param string[] $request List of request params.
	 * @return Zend_Http_Client|null Client, or NULL when mocked.
	 */
	protected function composeClient( $request )
	{
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Composing HTTP client with request data: '.print_r($request,true) );
		}
		$httpClient = $this->createHttpClient( $request['url'], $request['curloptions'] );
		$httpClient->setRawData( $request['body'] );
		$httpClient->setMethod( $request['method'] );
		$httpClient->setHeaders( $request['headers'] );
		return $httpClient;
	}

	/**
	 * Returns a reference to a http client.
	 *
	 * @param string $path
	 * @param array $curlOptions Optional. Extra parameters for the cURL adapter.
	 * @return Zend_Http_Client
	 * @throws BizException on connection errors.
	 */
	protected function createHttpClient( $path, $curlOptions = array() )
	{
		try {
			require_once 'Zend/Http/Client.php';
			
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

			$curlOptions[CURLOPT_SSL_VERIFYPEER] = true; // To prevent a man in the middle attack. (EN-29338).

			// This should always be a real path. When the file doesn't exist the Adobe DPS healthcheck will fix
			// this.
			$curlOptions[CURLOPT_CAINFO] = realpath( ENTERPRISE_CA_BUNDLE );

			// The connections should use TLSv1. The PHP documentation doesn't list this option, but
			// since it is passed directly to libcurl we can use it. (Checked in the PHP source code) (EN-29335).
			$curlOptions[CURLOPT_SSLVERSION] = 1;
			$curlOptions[CURLOPT_CONNECTTIMEOUT] = 10; // To improve connectivity (EN-31584).

			$curlConfig = array( 'curloptions' => $curlOptions );
			$curlConfig['adapter'] = 'WoodWing_Http_Client_Adapter_PublishCurl';
			$httpClient = new Zend_Http_Client( $path, $curlConfig );
		} catch( Exception $e ) { // catches Zend_Validate_Exception, Zend_Uri_Exception, etc (EN-27253)
			$message = BizResources::localize( 'AdobeDps2.ERROR_CANT_CONNECT_TO_SERVER' );
			$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = 'Error: '.$e->getMessage();
			throw new BizException( null, 'Server', $detail, $message );
		}
		return $httpClient;
	}
	
	/**
	 * Composes the common HTTP headers to be sent along with all Adobe DPS requests.
	 *
	 * @return array List of headers.
	 */
	protected function getCommonHeaders()
	{
		$headers = array(
			'Accept' =>'application/json',
			'Accept-Charset' =>'utf-8',
			'X-DPS-Client-Version' => 'com-woodwing-server-enterprise_'.$this->getClientVersion()
		);
		$accessToken = $this->getAccessToken();
		if( $accessToken ) { // Don't send these headers for authentication
			$headers['X-DPS-Client-Session-Id'] = $this->getSessionId();
			$headers['X-DPS-Client-Request-Id'] = $this->getRequestId();
			$headers['X-DPS-Api-Key'] = $this->getConsumerKey();
			$headers['Authorization'] = ' bearer ' . $accessToken;
		}
		return $headers;
	}

	/**
	 * Returns the Enterprise Server version. For Adobe DPS services, this is the 'client' version.
	 *
	 * @return string Three digit version number (without the build number).
	 */
	protected function getClientVersion()
	{
		$serverVer = explode( ' ', SERVERVERSION ); // split '9.5.0' from 'build 123'
		return $serverVer[0];
	}
	
	/**
	 * Returns the configured Consumer Key (also called Client ID or API key).
	 *
	 * @return string The Consumer Key.
	 */
	protected function getConsumerKey()
	{
		return $this->consumerKey;
	}

	/**
	 * Returns the configured Consumer Secret (also called Client Secret).
	 *
	 * @return string The Consumer Secret.
	 */
	protected function getConsumerSecret()
	{
		return $this->consumerSecret;
	}

	/**
	 * Returns the current session id (GUID).
	 *
	 * @return string The session id (GUID).
	 */
	protected function getSessionId()
	{
		return $this->sessionId;
	}

	/**
	 * Returns the current request id (GUID).
	 *
	 * @return string The request id (GUID).
	 */
	protected function getRequestId()
	{
		return $this->requestId;
	}

	/**
	 * Returns the current Access Token.
	 *
	 * @return string The Access Token.
	 */
	protected function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * Runs a service request at Adobe DPS (REST server) and returns the response.
	 * Logs the request and response at Enterprise Server logging folder.
	 * 
	 * @param Zend_Http_Client $httpClient Client connected to Adobe DPS.
	 * @param array $request Request data.
	 * @param callable $cbFunction Callback function to handle the response. Should accept $request, $httpCode and $responseBody params.
	 * @param string $serviceName Service to run at Adobe DPS. Used for logging only.
	 * @return string The HTTP response body (on success).
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function executeRequest( $httpClient, $request, $cbFunction, $serviceName )
	{
		// Retrieve the raw response object.
		$response = $this->callService( $httpClient, $serviceName, $request );

		// Get HTTP response data.
		if( $response ) {
			$httpCode = $response->getStatus();
			$responseBody = $response->getBody();
		} else {
			$httpCode = null;
			$responseBody = null;
		}

		// Callback the response handler.
		return call_user_func_array( array($this, $cbFunction), array( $request, $httpCode, $responseBody ) );
	}

	/**
	 * Retrieves the response from the HttpClient.
	 *
	 * @param Zend_Http_Client $httpClient Client connected to Adobe DPS.
	 * @param string $serviceName Service to run at Adobe DPS. Used for logging only.
	 * @param array $request Request data.
	 *
	 * @return null|Zend_Http_Response The response object from the HttpClient.
	 * @throws BizException When operation could not be executed properly.
	 */
	protected function callService( Zend_Http_Client $httpClient, $serviceName, array $request )
	{
		// Call the remote Adobe DPS service and monitor profiling
		PerformanceProfiler::startProfile( 'Calling Adobe DPS', 1 );
		$e = null;
		try {
			$rawResponse = $httpClient->request();
		} catch( Exception $e ) { // BizException, Zend_Http_Client_Exception, Zend_Http_Client_Adapter_Exception, etc
			$rawResponse = null;
		}
		PerformanceProfiler::stopProfile( 'Calling Adobe DPS', 1 );

		// Log request and response (or error)
		LogHandler::Log( 'AdobeDps2', 'DEBUG', 'Called Adobe DPS service '.$serviceName );
		if( defined('LOG_DPS_SERVICES') && LOG_DPS_SERVICES ) {
			$adobeDpsServiceName = 'AdobeDps2_'.$serviceName;
			LogHandler::logService( $adobeDpsServiceName, $httpClient->getLastRequest(), true, 'REST', null, true );
			if( $rawResponse ) {
				if( $rawResponse->isError() ) {
					LogHandler::logService( $adobeDpsServiceName, $rawResponse->asString(), null, 'REST', null, true );
				} else {
					LogHandler::logService( $adobeDpsServiceName, $rawResponse->asString(), false, 'REST', null, true );
				}
			}
		}
		
		// After logging, it is safe to raise any fatal problem.
		if( $e ) {
			$message = BizResources::localize( 'AdobeDps2.ERROR_CANT_CONNECT_TO_SERVER' );
			$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = 'Error: '.$e->getMessage();
			throw new BizException( null, 'Server', $detail, $message );
		}
		return $rawResponse;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Validation functions.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Validates if a GUID parameter is correctly constructed.
	 *
	 * When the GUID is invalid an exception is thrown with a customized message 
	 * based on the supplied parameters.
	 *
	 * @param string $method The method that calls this validation function, i.e. commitEntityContents.
	 * @param string $paramName The name of the GUID variable within the method, minus the $. i.e. apUploadGuid.
	 * @param string $guid The GUID to validate.
	 * @throws BizException Throws an BizException if the GUID is not valid.
	 */
	private function validateGuid( $method, $paramName, $guid )
	{
		require_once BASEDIR .'/server/utils/NumberUtils.class.php';
		if( !NumberUtils::validateGUID( $guid ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 
				$method . '() got bad value '.print_r($guid,true).' for $' . $paramName . ' param.' );
		}
	}

	/**
	 * Validates if a string parameter is correctly constructed.
	 *
	 * When the string parameter is invalid an BizException is thrown with a customized 
	 * message based on the supplied parameters.
	 *
	 * @param string $method The method that calls this validation function, i.e. commitEntityContents.
	 * @param string $paramName The name of the variable within the method, minus the $. i.e. apUploadGuid.
	 * @param string $stringVar The string to validate.
	 * @throws BizException Throws an exception if the variable is not valid.
	 */
	private function validateString( $method, $paramName, $stringVar ) 
	{
		if( !is_string( $stringVar ) || empty( $stringVar ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 
				$method . '() got bad value '.print_r($stringVar,true).' for $' . $paramName . ' param.' );
		}
	}

	/**
	 * Validates if a string parameter is correctly constructed.
	 *
	 * When the string parameter is invalid an BizException is thrown with a customized 
	 * message based on the supplied parameters.
	 *
	 * @param string $method The method that calls this validation function, i.e. commitEntityContents.
	 * @param string $paramName The name of the variable within the method, minus the $. i.e. apUploadGuid.
	 * @param string $stringVar The string to validate.
	 * @throws BizException Throws an exception if the variable is not valid.
	 */
	private function validateStringOrNull( $method, $paramName, $stringVar ) 
	{
		if( !is_null( $stringVar ) && 
			(!is_string( $stringVar ) || empty( $stringVar )) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 
				$method . '() got bad value '.print_r($stringVar,true).' for $' . $paramName . ' param.' );
		}
	}

	/**
	 * Validates if an integer parameter is correctly constructed.
	 *
	 * When the integer parameter is invalid an BizException is thrown with a customized 
	 * message based on the supplied parameters.
	 *
	 * @param string $method The method that calls this validation function, i.e. commitEntityContents.
	 * @param string $paramName The name of the variable within the method, minus the $. i.e. apUploadGuid.
	 * @param integer $integerVar The integer to validate.
	 * @throws BizException Throws an exception if the variable is not valid.
	 */
	private function validateIntegerOrNull( $method, $paramName, $integerVar ) 
	{
		if( !is_null( $integerVar ) && !is_integer( $integerVar ) ) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 
				$method . '() got bad value '.print_r($integerVar,true).' for $' . $paramName . ' param.' );
		}
	}
}