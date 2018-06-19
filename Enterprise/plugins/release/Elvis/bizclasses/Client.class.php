<?php
/**
 * Client class providing Elvis web services. It talks with Elvis server over the REST API.
 *
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.*
 */

class Elvis_BizClasses_Client
{
	/** @var string|null */
	protected $shortUserName;

	/**
	 * Constructor.
	 *
	 * @param string|null $shortUserName For who requests should be authorized. NULL to use unauthorized requests.
	 */
	public function __construct( ?string $shortUserName )
	{
		$this->shortUserName = $shortUserName;
	}

	/**
	 * Create a new asset at the Elvis server.
	 *
	 * @param stdClass $metadata Metadata to be updated in Elvis
	 * @param string[] $metadataToReturn
	 * @param Attachment|null $fileToUpload
	 * @return stdClass representation of ElvisEntHit
	 */
	public function create( stdClass $metadata, array $metadataToReturn, $fileToUpload ) : stdClass
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'services/create', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->addPostParamAsJson( 'metadata', $metadata );
		$request->addCsvPostParam( 'metadataToReturn', $metadataToReturn );
		$request->setHttpPostMethod();
		$request->setExpectJson();
		if( !is_null( $fileToUpload ) ) {
			$request->addFileToUpload( $fileToUpload );
		}

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Update an asset at the Elvis server.
	 *
	 * @param string $assetId
	 * @param stdClass $metadata Metadata to be updated in Elvis
	 * @param string[] $metadataToReturn
	 * @param Attachment|null $fileToUpload
	 * @param bool $undoCheckout
	 * @return stdClass representation of ElvisEntHit
	 */
	public function update( string $assetId, stdClass $metadata, array $metadataToReturn, ?Attachment $fileToUpload, bool $undoCheckout ) : stdClass
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'services/update', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addPostParam( 'id', $assetId );
		$request->addPostParamAsJson( 'metadata', $metadata );
		$request->addPostParam( 'clearCheckoutState', $undoCheckout ? 'true' : 'false' );
		$request->addCsvPostParam( 'metadataToReturn', $metadataToReturn );
		$request->setHttpPostMethod();
		$request->setExpectJson();
		if( !is_null( $fileToUpload ) ) {
			$request->addFileToUpload( $fileToUpload );
		}

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Update multiple assets in Elvis server for the provided metadata.
	 *
	 * @param string[] $assetIds
	 * @param array $metadata Changed asset metadata
	 */
	public function updateBulk( array $assetIds, array $metadata )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'services/updatebulk', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECTS' ) );
		$request->addSearchPostParam( 'q', 'id', $assetIds );
		$request->addPostParamAsJson( 'metadata', $metadata );
		$request->setHttpPostMethod();
		$request->setExpectJson();

		$this->execute( $request );
	}

	/**
	 * Checkout an asset at the Elvis server.
	 *
	 * @param string $assetId
	 */
	public function checkout( string $assetId )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'services/checkout', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->setHttpPostMethod();
		$request->addPathParam( $assetId );
		$request->setExpectJson();

		$this->execute( $request );
	}

	/**
	 * Undo checkout an asset at the Elvis server.
	 *
	 * @param string $assetId
	 */
	public function undoCheckout( string $assetId )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'services/undocheckout', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->setHttpPostMethod();
		$request->addPathParam( $assetId );
		$request->setExpectJson();

		$this->execute( $request );
	}

	/**
	 * Retrieve an asset from the Elvis server.
	 *
	 * @param string $assetId
	 * @param bool $checkOut
	 * @param string[] $metadataToReturn
	 * @return stdClass representation of ElvisEntHit
	 * @throws BizException
	 */
	public function retrieve( string $assetId, bool $checkOut, array $metadataToReturn ) : stdClass
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/retrieve', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->setHttpPostMethod();
		$request->addPostParam( 'assetId', $assetId );
		$request->addPostParam( 'checkout', $checkOut ? 'true' : 'false' );
		$request->addCsvPostParam( 'metadataToReturn', $metadataToReturn );
		$request->setExpectJson();

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Delete given assets.
	 *
	 * @param ElvisDeleteObjectOperation[] $deleteOperations
	 */
	public function deleteObjects( array $deleteOperations )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/deleteObjects', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECTS' ) );
		$request->addQueryParamAsJson( 'deleteOperations', $deleteOperations );

		$this->execute( $request );
	}

	/**
	 * Copy an asset.
	 *
	 * @param string $assetId
	 * @param string $name
	 * @return string Id of the copied asset.
	 */
	public function copy( string $assetId, string $name ) : string
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/copy', $this->shortUserName );
		$request->setSubjectId( $assetId );
		$request->setSubjectName( $name );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->addQueryParam( 'assetId', $assetId );
		$request->addQueryParam( 'name', $name );
		$request->setExpectRawData();

		$response = $this->execute( $request );
		return $response->body();
	}

	/**
	 * Copy an asset in Elvis to a pre-defined folder and return the copy to Enterprise.
	 *
	 * The copied asset is already registered as shadow object in Elvis.
	 *
	 * @param string $assetId Id of the original Elvis asset to be copied.
	 * @param string $destFolderPath Path on Elvis where the asset will be copied to.
	 * @param string|null $name The name of the asset. If not set, the value remains empty and Elvis uses the asset filename.
	 * @param string $entSystemId Enterprise system id.
	 * @return stdClass representation of an ElvisEntHit of the copied Elvis asset.
	 */
	public function copyTo( string $assetId, string $destFolderPath, string $name, string $entSystemId ) : stdClass
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/copyTo', $this->shortUserName );
		$request->setSubjectId( $assetId );
		$request->setSubjectName( $name );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->addQueryParam( 'assetId', $assetId );
		$request->addQueryParam( 'name', $name );
		$request->setExpectJson();

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Retrieve versions of an asset from the Elvis server.
	 *
	 * @param string $assetId
	 * @return stdClass[] representation of ElvisEntHit[]
	 * @throws BizException
	 */
	public function listVersions( string $assetId ) : array
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'services/asset/history', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addQueryParam( 'id', $assetId );
		$request->addQueryParam( 'detailLevel', 1 );
		$request->setExpectJson();

		$response = $this->execute( $request );
		$body = $response->jsonBody();
		if( !isset( $body->hits ) ) {
			throw new BizException( 'ERR_NOTFOUND', 'Server', 'Elvis assetId: ' . $assetId, null, null, 'INFO' );
		}
		return array_map( function ( $hit ) { return $hit->hit; }, $body->hits );
	}

	/**
	 * Retrieve a version of an asset from the Elvis server.
	 *
	 * @param string $assetId
	 * @param string $version
	 * @return stdClass representation of ElvisEntHit
	 */
	public function retrieveVersion( string $assetId, string $version ) : stdClass
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/retrieveVersion', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize( 'OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addQueryParam( 'assetId', $assetId );
		$request->addQueryParam( 'version', $version );
		$request->setExpectJson();

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Promote a version to the head version of an asset at the Elvis server.
	 *
	 * @param string $assetId
	 * @param string $version
	 */
	public function promoteVersion( string $assetId, string $version )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/promoteVersion', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize( 'OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addQueryParam( 'assetId', $assetId );
		$request->addQueryParam( 'version', $version );

		$response = $this->execute( $request );
	}

	/**
	 * Link a shadow object to an Elvis asset.
	 *
	 * @param Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity
	 */
	public function registerShadowObjects( Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity ): void
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/register-shadow-object', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize( 'OBJECT' ) );
		$request->setSubjectId( $shadowObjectIdentity->assetId );
		$request->setBody( json_encode( $shadowObjectIdentity ) );
		$request->setHttpPostMethod();
		$request->setHeader( 'Content-Type', 'application/json' );

		$response = $this->execute( $request );
	}

	/**
	 * Un-link a shadow object from an Elvis asset.
	 *
	 * @param Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity
	 */
	public function unregisterShadowObjects( Elvis_DataClasses_ShadowObjectIdentity $shadowObjectIdentity )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/unregister-shadow-object', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize( 'OBJECT' ) );
		$request->setSubjectId( $shadowObjectIdentity->assetId );
		$request->setBody( json_encode( $shadowObjectIdentity ) );
		$request->setHttpPostMethod();
		$request->setHeader( 'Content-Type', 'application/json' );

		$response = $this->execute( $request );
	}

	/**
	 * Retrieve detailed user information from Elvis Server.
	 *
	 * Can only be requested by users with admin permissions.
	 *
	 * @param string $username The username of the user to request the info for.
	 * @return stdClass representation of ElvisEntUserDetails that contains the detailed user information.
	 * @throws BizException
	 */
	public function getUserDetails( string $username ) : stdClass
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/getUserDetails', $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize( 'USR_USER' ) );
		$request->setSubjectName( $username );
		$request->addQueryParam( 'username', $username );
		$request->setExpectJson();

		$response = $this->execute( $request );

		// >>> Elvis returns HTTP 200 with empty body when user does not exist, but expected is HTTP 404 so here we detect.
		if( !$response->body() ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', '', // S1056
				null, array( $request->getSubjectEntity(), $request->getSubjectId(), 'INFO' ) );
		}
		// <<<

		return $response->jsonBody();
	}

	/**
	 * Ping the Elvis Server and retrieve some basic information.
	 *
	 * @return stdClass Info object with properties state, version, available and server.
	 */
	public function getElvisServerInfo()
	{
		$request = Elvis_BizClasses_ClientRequest::newUnauthorizedRequest( 'services/ping' );
		$request->setSubjectEntity( 'Elvis server version info' );
		$request->setNotFoundErrorAsSevere(); // error on HTTP 404 (could happen for Elvis 4 that has no ping service)
		$request->setExpectJson();

		// The Elvis ping service returns a JSON structure like this:
		//     {"state":"running","version":"5.15.2.9","available":true,"server":"Elvis"}

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Return asset updates that are waiting in the Elvis queue, using long-polling.
	 *
	 * @param string $enterpriseSystemId
	 * @param int $operationTimeout The operation timeout of the asset updates in seconds.
	 * @return stdClass[] representation of ElvisEntUpdate[]
	 */
	public function retrieveAssetUpdates( string $enterpriseSystemId, int $operationTimeout )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/retrieveAssetUpdates', $this->shortUserName );
		$request->addQueryParam( 'enterpriseSystemId', $enterpriseSystemId );
		$request->addQueryParam( 'timeout', strval( $operationTimeout ) );
		$request->setExpectJson();

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Confirm asset updates so they can be removed from the queue in Elvis.
	 *
	 * @param string $enterpriseSystemId
	 * @param string[] $updateIds List of update ids to confirm.
	 */
	public function confirmAssetUpdates( string $enterpriseSystemId, array $updateIds )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/confirmAssetUpdates', $this->shortUserName );
		$request->addQueryParam( 'enterpriseSystemId', $enterpriseSystemId );
		$request->addCsvQueryParam( 'updateIds', $updateIds );

		$this->execute( $request );
	}

	/**
	 * Configure which metadata fields the associated Enterprise server is interested in.
	 * Only updates for these fields will be send to Enterprise.
	 *
	 * @param string $enterpriseSystemId The id identifying the Enterprise server
	 * @param string[] List of Elvis field names
	 */
	public function configureMetadataFields( string $enterpriseSystemId, array $fields )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/configureMetadataFields', $this->shortUserName );
		$request->addQueryParam( 'enterpriseSystemId', $enterpriseSystemId );
		$request->addCsvQueryParam( 'fields', $fields );

		$this->execute( $request );
	}

	/**
	 * Update Enterprise specific workflow metadata of Elvis assets.
	 *
	 * @param array $assetIds indexed array with Elvis asset ids.
	 * @param array $metadata assosiative array with metadata field names and values
	 * @throws BizException
	 */
	public function updateWorkflowMetadata( array $assetIds, array $metadata )
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'private-api/contentsource/update-workflow-metadata', $this->shortUserName );
		$request->setHttpPutMethod();
		$request->setHeader( 'Content-Type', 'application/json' );
		$request->setBody( json_encode( array( 'assetIds' => $assetIds, 'metadata' => $metadata ) ) );

		$this->execute( $request );
	}

	/**
	 * Execute a service request against Elvis server for the session user or ELVIS_SUPER_USER.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @return Elvis_BizClasses_ClientResponse
	 * @throws BizException
	 */
	protected function execute( Elvis_BizClasses_ClientRequest $request ) : Elvis_BizClasses_ClientResponse
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'Calling REST API '.$request->getDescription() );

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		if( $response->isError() ) {
			$detail = $response->getErrorMessage();
			if( $response->isAuthenticationError() ) { // HTTP 401
				if( $request->getUserShortName() !== ELVIS_SUPER_USER ) {
					$request->setUserShortName( ELVIS_SUPER_USER );
					$response = $this->execute( $request );
				} else {
					throw new Elvis_BizClasses_Exception( $detail, 'ERROR' );
				}
			}
			if( $response->isForbiddenError() ) { // HTTP 403
				throw new BizException( 'ERR_AUTHORIZATION', 'Client', $detail,
					null, null, 'INFO' );
			}
			if( $response->isNotFoundError() || $response->isGoneError() ) { // HTTP 404 or 410
				$severity = $request->isNotFoundErrorSevere() ? 'ERROR' : 'INFO';
				if( $request->getSubjectEntity() ) {
					if( $request->getSubjectId() ) {
						throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', $detail, // S1056
							null, array( $request->getSubjectEntity(), $request->getSubjectId(), $severity ) );
					} else {
						throw new BizException( 'ERR_NO_SUBJECTS_FOUND', 'Client', $detail, // S1036
							null, array( $request->getSubjectEntity() ), $severity );
					}
				} else {
					throw new BizException( 'ERR_NOTFOUND', 'Client', $detail, // S1029
						null, null, $severity );
				}
			}
			if( $response->isRequestTimeoutError() ) { // HTTP 408
				if( $request->getAttempt() <= 3 ) {
					$request->nextAttempt();
					$response = $this->execute( $request );
				} else {
					throw new Elvis_BizClasses_Exception( $detail, 'ERROR' );
				}
			}
			if( $response->isConflictError() ) { // HTTP 409
				if( $request->getSubjectEntity() && $request->getSubjectName() ) {
					throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client', $detail, // S1038
						null, array( $request->getSubjectEntity(), $request->getSubjectName(), 'INFO' ) );
				} else {
					throw new Elvis_BizClasses_Exception( $detail, 'ERROR' );
				}
			}
			if( $response->isClientProgrammaticError() ) { // all HTTP 4xx codes except the ones listed above
				throw new Elvis_BizClasses_Exception( $detail, 'ERROR' );
			}
		}
		return $response;
	}
}
