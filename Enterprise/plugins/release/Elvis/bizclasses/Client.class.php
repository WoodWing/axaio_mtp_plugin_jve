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
	private $shortUserName;

	/**
	 * Constructor.
	 *
	 * @param string|null $shortUserName For who requests should be authorized. NULL to use unauthorized requests.
	 */
	public function __construct( $shortUserName )
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
		$request = new Elvis_BizClasses_ClientRequest( 'services/create' );
		$request->setUserShortName( $this->shortUserName );
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
	public function update( string $assetId, stdClass $metadata, array $metadataToReturn, $fileToUpload, bool $undoCheckout ) : stdClass
	{
		$request = new Elvis_BizClasses_ClientRequest( 'services/update' );
		$request->setUserShortName( $this->shortUserName );
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
	public function updateBulk( array $assetIds, $metadata )
	{
		$request = new Elvis_BizClasses_ClientRequest( 'services/updatebulk' );
		$request->setUserShortName( $this->shortUserName );
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
		$request = new Elvis_BizClasses_ClientRequest( 'services/checkout' );
		$request->setUserShortName( $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addPathParam( $assetId );

		$this->execute( $request );
	}

	/**
	 * Undo checkout an asset at the Elvis server.
	 *
	 * @param string $assetId
	 */
	public function undoCheckout( string $assetId )
	{
		$request = new Elvis_BizClasses_ClientRequest( 'services/undocheckout' );
		$request->setUserShortName( $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addPathParam( $assetId );

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
		$request = new Elvis_BizClasses_ClientRequest( 'private-api/contentsource/retrieve' );
		$request->setUserShortName( $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addQueryParam( 'assetId', $assetId );
		$request->addQueryParam( 'checkout', $checkOut ? 'true' : 'false' );
		$request->addCsvQueryParam( 'metadataToReturn', $metadataToReturn );
		$request->setExpectJson();

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Deletes given assets.
	 *
	 * @param ElvisDeleteObjectOperation[] $deleteOperations
	 */
	public function deleteObjects( array $deleteOperations )
	{
		$request = new Elvis_BizClasses_ClientRequest( 'private-api/contentsource/deleteObjects' );
		$request->setUserShortName( $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize('OBJECTS' ) );
		$request->addQueryParamAsJson( 'deleteOperations', $deleteOperations );

		$this->execute( $request );
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
		$request = new Elvis_BizClasses_ClientRequest( 'services/asset/history' );
		$request->setUserShortName( $this->shortUserName );
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
		$request = new Elvis_BizClasses_ClientRequest( 'private-api/contentsource/retrieveVersion' );
		$request->setUserShortName( $this->shortUserName );
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
		$request = new Elvis_BizClasses_ClientRequest( 'private-api/contentsource/promoteVersion' );
		$request->setUserShortName( $this->shortUserName );
		$request->setSubjectEntity( BizResources::localize( 'OBJECT' ) );
		$request->setSubjectId( $assetId );
		$request->addQueryParam( 'assetId', $assetId );
		$request->addQueryParam( 'version', $version );

		$response = $this->execute( $request );
	}

	/**
	 * Pings the Elvis Server and retrieves some basic information.
	 *
	 * @return stdClass Info object with properties state, version, available and server.
	 */
	public function getElvisServerInfo()
	{
		$request = new Elvis_BizClasses_ClientRequest( 'services/ping' );
		$request->setSubjectEntity( 'Elvis server version info' );
		$request->setNotFoundErrorAsSevere(); // error on HTTP 404 (could happen for Elvis 4 that has no ping service)
		$request->setExpectJson();

		// The Elvis ping service returns a JSON structure like this:
		//     {"state":"running","version":"5.15.2.9","available":true,"server":"Elvis"}

		$response = $this->execute( $request );
		return $response->jsonBody();
	}

	/**
	 * Execute a service request against Elvis server for the session user or ELVIS_SUPER_USER.
	 *
	 * @param Elvis_BizClasses_ClientRequest $request
	 * @return Elvis_BizClasses_ClientResponse
	 * @throws BizException
	 */
	private function execute( Elvis_BizClasses_ClientRequest $request ) : Elvis_BizClasses_ClientResponse
	{
		$logMessage = 'Calling REST API '.$request->composeServicePath();
		if( $request->getSubjectId() ) {
			$logMessage .= ' for id: '.$request->getSubjectId();
		}
		LogHandler::Log( 'ELVIS', 'DEBUG', $logMessage );

		$client = new Elvis_BizClasses_CurlClient();
		$response = $client->execute( $request );
		if( $response->isError() ) {
			$detail = $response->getErrorMessage();
			if( $response->isAuthenticationError() ) { // HTTP 401
				if( $request->getUserShortName() !== ELVIS_SUPER_USER ) {
					$request->setUserShortName( ELVIS_SUPER_USER );
					$response = $this->execute( $request );
				} else {
					throw new BizException( 'ERR_AUTHORIZATION', 'Client', $detail,
						null, null, 'INFO' );
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
					throw new BizException( 'ERR_NOT_FOUND', 'Client', $detail, // S1029
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
			if( $response->isClientProgrammaticError() ) { // all HTTP 4xx codes except the ones listed above
				throw new Elvis_BizClasses_Exception( $detail, 'ERROR' );
			}
		}
		return $response;
	}
}