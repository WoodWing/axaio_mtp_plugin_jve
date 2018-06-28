<?php

/**
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Proxy server that accepts an Elvis request from an Enterprise client and pass it on to Elvis Server.
 *
 * See header of the restproxyindex.php module for details how to user the proxy server.
 */
class Elvis_BizClasses_ProxyServer
{
	/** @var array $httpParams HTTP input parameters (taken from URL or Cookie). */
	private $httpParams;

	/** @var MetaData $objectMetaData Some essential properties resolved for the invoked Enterprise object. */
	private $objectMetaData;

	/** @var string $elvisAssetId The DocumentID of the invoked shadow object, which equals the Elvis asset id. */
	private $elvisAssetId;

	/**
	 * Dispatch the incoming HTTP request.
	 */
	public function handle()
	{
		$httpMethod = $_SERVER['REQUEST_METHOD'];
		LogHandler::Log( 'ElvisRestProxyIndex', 'CONTEXT', "Incoming HTTP {$httpMethod} request." );
		PerformanceProfiler::startProfile( 'ElvisRestProxyIndex', 1 );

		try {
			try {
				$this->allowCrossHeaders();
				$this->parseHttpParams();
				$this->dispatchRequest( $httpMethod );
			} catch( BizException $e ) {
				throw Elvis_BizClasses_ProxyServerHttpException::createFromBizException( $e );
			}
		} catch( Elvis_BizClasses_ProxyServerHttpException $e ) {
			header( 'HTTP/1.1 '.$e->getCode().' '.$e->getReasonPhrase() );
			header( 'Status: '.$e->getStatusMessage() );
			LogHandler::Log( __CLASS__, $e->getSeverity(), $e->getStatusMessage() );
		}

		PerformanceProfiler::stopProfile( 'ElvisRestProxyIndex', 1 );
		LogHandler::Log( 'ElvisRestProxyIndex', 'CONTEXT', "Outgoing HTTP {$httpMethod} response." );
	}

	/**
	 * Add Cross Origin headers needed by Javascript applications
	 */
	private function allowCrossHeaders()
	{
		require_once BASEDIR.'/server/utils/CrossOriginHeaderUtil.class.php';
		WW_Utils_CrossOriginHeaderUtil::addCrossOriginHeaders();
	}

	/**
	 * Validate the HTTP request params and populate $this->httpParams.
	 */
	private function parseHttpParams()
	{
		$requestParams = WW_Utils_HttpRequest::getHttpParams( 'GP' ); // GET and POST only, no cookies

		$this->httpParams = array( 'ticket' => null );

		// Accept the ticket param.
		if( isset( $requestParams['ticket'] ) ) {
			$this->httpParams['ticket'] = $requestParams['ticket'];
		} elseif( isset( $requestParams['ww-app'] ) ) {
			// Support cookie enabled sessions. When the client has no ticket provided in the URL params, try to grab the ticket
			// from the HTTP cookies. This is to support JSON clients that run multiple web applications which need to share the
			// same ticket. Client side this can be implemented by simply letting the web browser round-trip cookies. [EN-88910]
			$this->httpParams['ticket'] = BizSession::getTicketForClientIdentifier();
		}

		// Accept the objectid param (Enterprise object id).
		if( isset( $requestParams['objectid'] ) ) {
			$this->httpParams['objectid'] = intval( $requestParams['objectid'] );
		}

		// Accept the assetid param (Elvis asset id).
		if( isset( $requestParams['assetid'] ) ) {
			$this->httpParams['assetid'] = $requestParams['assetid'];
		}

		// Accept the rendition param (file rendition).
		if( isset( $requestParams['rendition'] ) ) {
			$this->httpParams['rendition'] = $requestParams['rendition'];
		}

		// Accept the preview-args param (preview arguments).
		if( isset( $requestParams['preview-args'] ) ) {
			$this->httpParams['preview-args'] = $requestParams['preview-args'];
		}

		// Log the incoming parameters for debugging purposes.
		if( LogHandler::debugMode() ) {
			$msg = 'Incoming HTTP params:'.PHP_EOL;
			foreach( $this->httpParams as $key => $value ) {
				$msg .= "- {$key} = '{$value}'".PHP_EOL;
			}
			LogHandler::Log( 'ElvisRestProxyIndex', 'DEBUG', $msg );
		}
	}

	/**
	 * Dispatch (proxy) the incoming REST service request to Elvis Server.
	 *
	 * @param string $httpMethod
	 * @throws Elvis_BizClasses_ProxyServerHttpException
	 * @throws BizException
	 */
	private function dispatchRequest( $httpMethod )
	{
		// The OPTIONS call is send by a web browser as a pre-flight for a CORS request.
		// This request doesn't send or receive any information. There is no need to validate the ticket,
		// and when the OPTIONS calls returns an error the error can't be validated within an application.
		// This is a restriction by web browsers.
		switch( $httpMethod ) {
			case 'OPTIONS':
				throw new Elvis_BizClasses_ProxyServerHttpException( '', 200 );
			case 'GET':
			case 'POST':
				$this->preparePhpForStreaming();
				$this->validateTicketAndStartSession();
				if( isset( $this->httpParams['assetid'] ) ) {
					$this->elvisAssetId = $this->httpParams['assetid'];
				} else {
					$this->checkObjectReadAccess();
					$this->resolveElvisAssetId();
				}
				$this->proxyRequestToElvisServer();
				break;
			default:
				$message = 'Unknown HTTP method "'.$_SERVER['REQUEST_METHOD'].'" is used which is not supported.';
				throw new Elvis_BizClasses_ProxyServerHttpException( $message, 405 );
		}
	}

	/**
	 * Set global options for the PHP environment to allow streaming without interference.
	 */
	private function preparePhpForStreaming()
	{
		// Abort after one hour download without streaming activity.
		set_time_limit( 3600 );

		// The following option could corrupt archive files, so disable it
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#49671
		ini_set( "zlib.output_compression", "Off" );

		// This lets a user download a file while still being able to browse your site.
		// -> http://nl3.php.net/manual/en/function.fpassthru.php#48244
		session_write_close();
	}

	/**
	 * Check if the ticket (provided by client) is valid and starts an Enterprise Server session.
	 *
	 * @throws BizException
	 * @throws Elvis_BizClasses_ProxyServerHttpException
	 */
	private function validateTicketAndStartSession()
	{
		if( !array_key_exists( 'ticket', $this->httpParams ) ) {
			$message = 'Please specify a "ticket" param at the URL, or provide web cookies and set the "ww-app" param.';
			throw new Elvis_BizClasses_ProxyServerHttpException( $message, 400 );
		}
		$user = BizSession::checkTicket( $this->httpParams['ticket'] );
		BizSession::setServiceName( 'ElvisRestProxyIndex' );
		BizSession::startSession( $this->httpParams['ticket'] );
		BizSession::setTicketCookieForClientIdentifier( $this->httpParams['ticket'] );
	}

	/**
	 * Check if the session user has Read access to the invoked object.
	 *
	 * @throws Elvis_BizClasses_ProxyServerHttpException
	 */
	private function checkObjectReadAccess()
	{
		// Validate the objectid param.
		if( !isset($this->httpParams['objectid']) || !$this->httpParams['objectid'] ) {
			$message = 'Please specify a "objectid" param at the URL.';
			throw new Elvis_BizClasses_ProxyServerHttpException( $message, 400 );
		}
		$objectId = $this->httpParams['objectid'];

		// Get some object properties required by BizAccess::checkRightsForObjectProps().
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$metaDatasPerObject = DBObject::getMultipleObjectsProperties( array( $objectId ) );

		// Bail out when object does not exist in DB.
		if( !array_key_exists( $objectId, $metaDatasPerObject ) ) {
			$message = 'The object could not be found.';
			throw new Elvis_BizClasses_ProxyServerHttpException( $message, 404 );
		}
		$this->objectMetaData = $metaDatasPerObject[ $objectId ];

		// Resolve the overrule issue the object is assigned to. For normal issues, leave it zero.
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$overruleIssue = DBIssue::getOverruleIssueIdsFromObjectIds( array( $objectId ) );
		$issueId = isset( $overruleIssue[$objectId] ) ? $overruleIssue[$objectId] : 0;

		// Check if user has Read access to the object.
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		if( !BizAccess::checkRightsForMetaDataAndIssue( BizSession::getShortUserName(), 'R',
			BizAccess::DONT_THROW_ON_DENIED, $this->objectMetaData, $issueId ) ) {
			$message = 'The user has no Read access to the object.';
			throw new Elvis_BizClasses_ProxyServerHttpException( $message, 403 );
		}
	}

	/**
	 * Resolve the Elvis asset id from the invoked Enterprise object id.
	 *
	 * @throws Elvis_BizClasses_ProxyServerHttpException
	 */
	private function resolveElvisAssetId()
	{
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEID

		$contentSource = $this->objectMetaData->BasicMetaData->ContentSource;
		$documentId = $this->objectMetaData->BasicMetaData->DocumentID;
		if( !$contentSource || !$documentId || $contentSource != ELVIS_CONTENTSOURCEID ||
			!BizContentSource::isShadowObjectBasedOnProps( $contentSource, $documentId ) ) {
			$message = 'The object is not an Elvis shadow object.';
			throw new Elvis_BizClasses_ProxyServerHttpException( $message, 404 );
		}
		$this->elvisAssetId = $documentId;
	}

	/**
	 * Dispatch (proxy) the incoming REST service request to Elvis Server.
	 *
	 * @throws BizException
	 * @throws Elvis_BizClasses_ProxyServerHttpException
	 */
	private function proxyRequestToElvisServer()
	{
		$service = $this->composeRestServiceForFileDownload();
		$client = new Elvis_BizClasses_ProxyClient( BizSession::getShortUserName(), $service );
		$client->proxy();
	}

	/**
	 * Compose the relative Elvis REST service URL for a file download operation.
	 *
	 * @return string The URL (without the absolute Elvis base path).
	 * @throws Elvis_BizClasses_ProxyServerHttpException
	 */
	private function composeRestServiceForFileDownload()
	{
		if( !isset( $this->httpParams['rendition'] ) ) {
			$message = 'Please specify "rendition" param at URL.';
			throw new Elvis_BizClasses_ProxyServerHttpException( $message, 400 );
		}
		switch( $this->httpParams['rendition'] ) {
			case 'thumb':
				$service = 'thumbnail/'.rawurlencode( $this->elvisAssetId );
				break;
			case 'preview':
				$service = 'preview/'.rawurlencode( $this->elvisAssetId ).$this->composePreviewUrlArguments();
				break;
			case 'native':
				$service = 'file/'.rawurlencode( $this->elvisAssetId );
				break;
			default:
				$message = 'The option provided for the"rendition" param is unsupported.';
				throw new Elvis_BizClasses_ProxyServerHttpException( $message, 400 );
		}
		return $service;
	}

	/**
	 * Compose the preview arguments that can be optionally added to the preview download URL.
	 *
	 * @return string
	 * @throws Elvis_BizClasses_ProxyServerHttpException
	 */
	private function composePreviewUrlArguments()
	{
		if( isset( $this->httpParams['preview-args'] ) ) {
			if( !$this->isValidPreviewArgsParam( $this->httpParams['preview-args'] ) ) {
				$message = 'The "preview-args" param is not valid.';
				throw new Elvis_BizClasses_ProxyServerHttpException( $message, 400 );
			}
			$arguments = '/previews/'.rawurlencode( $this->httpParams['preview-args'] );
		} else {
			$arguments = '';
		}
		return $arguments;
	}

	/**
	 * Check whether the given preview-args parameter value is valid.
	 *
	 * The param is valid when it is alphanumeric or contains dashes(-) or underscores (_) or dots (.).
	 * Example:
	 *    http://127.0.0.1/Enterprise/config/plugins/Elvis/restproxyindex.php?ww-app=Content%20Station&objectid=500101124&rendition=preview&preview-args=maxWidth_200_maxHeight_200.jpg
	 *
	 * @param string $previewArgs
	 * @return bool
	 */
	protected function isValidPreviewArgsParam( string $previewArgs )
	{
		// Do not accept % to avoid double encoding attacks https://www.owasp.org/index.php/Double_Encoding
		// Do not accept / to avoid accessing other assets through relative paths, for example:
		//    http://127.0.0.1/Enterprise/config/plugins/Elvis/restproxyindex.php?ww-app=Content%20Station&objectid=500101124&rendition=preview&preview-args=../../D1wjdC_3KpzAyeZQKFPYUQ
		//      L> Note that object id 500101124 is NOT the shadow of asset id D1wjdC_3KpzAyeZQKFPYUQ, so the hacker
		//         tries to by-pass the access rights validation at Enterprise for asset D1wjdC_3KpzAyeZQKFPYUQ (the
		//         hacker has no access rights for) by providing object id 500101124 (the hacker has access rights for).
		$allowedSymbols = array(
			'_', // used to separate arguments and values
			'.'  // used for file extension
		);
		return $previewArgs && ctype_alnum( str_replace( $allowedSymbols, '', $previewArgs ) );
	}
}
