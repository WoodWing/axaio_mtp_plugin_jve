<?php
/**
 * @package Enterprise
 * @subpackage Utils
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_DigitalPublishingSuiteClient
{
	private $ticket 		= null;
	private $accountId		= null;
	private $url			= null;
	private $curlOptions 	= null;
	private $httpCode       = null;
	private $dpsClientVersion = null;
	private $dpsSessionId   = null;
	
	// Members for parallel uploads:
	private $httpClientMulti = null;
	private $processNextArticleCB = null;
	private $processedArticleCB = null;
	private $requestPool = null;

	/**
	 * Constructor.
	 *
	 * @param string $url Connection URL to the Adobe DPS server.
	 * @param string $dpsAccount Adobe DPS email account to be used as DPS session id.
	 */
	public function __construct( $url, $dpsAccount=null )
	{
		$this->url = $url;
		$this->dpsClientVersion = $this->getDpsClientVersion();
		$this->dpsSessionId = '';
		if( !is_null( $dpsAccount ) ) {
			$this->dpsSessionId = $this->getDpsSessionId( $dpsAccount );
		}
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'DPS Url:' . $url . '<br/>' . 
											  'DPSClientVersion:' . $this->dpsClientVersion . '<br/>'.
											  'DPSSessionId:' . $this->dpsSessionId .' ('.$dpsAccount.')');
	}

	/**
	 * Returns a reference to the HTTP client, instantiating it if necessary
	 *
	 * @param string $path
	 * @return Zend_Http_Client
	 * @throws BizException on connection errors.
	 */
	private function createHttpClient( $path )
	{

		try {
			require_once 'Zend/Http/Client.php';
			$configs = 	defined('ENTERPRISE_PROXY') && ENTERPRISE_PROXY != '' ?
					unserialize( ENTERPRISE_PROXY ) : array();					

			$this->curlOptions = array();
			if ( $configs ) {
				if ( isset($configs['proxy_host']) ) {
					$this->curlOptions [CURLOPT_PROXY] = $configs['proxy_host'];
				}
				if ( isset($configs['proxy_port']) ) {
					$this->curlOptions [CURLOPT_PROXYPORT] = $configs['proxy_port'];
				}
				if ( isset($configs['proxy_user']) && isset($configs['proxy_pass']) ) {
					$this->curlOptions [CURLOPT_PROXYUSERPWD] = $configs['proxy_user'] . ":" . $configs['proxy_pass'];
				}
			}

			// BZ#29338: Always check the certificate, when set to false this could enable a man in the middle attack.
			$this->curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
			// This should always be a real path. When the file doesn't exist the Adobe DPS healthcheck will fix this.
			$caPath = realpath(ENTERPRISE_CA_BUNDLE);
			$this->curlOptions[CURLOPT_CAINFO] = $caPath;

			// BZ#29335: The connections should use TLSv1. The PHP documentations doesn't list this option, but
			// since it is passed directly to libcurl we can use it. (Checked in the PHP source code)
			$this->curlOptions[CURLOPT_SSLVERSION] = 1 ;
				// BZ#31584: Set the CURLOPT_CONNECTTIMEOUT = 10, same the httpClientMulti option value
				$this->curlOptions[CURLOPT_CONNECTTIMEOUT] = 10; 

			$curlConfig = array( 'curloptions' => $this->curlOptions );
			if( $this->httpClientMulti ) {
				$httpClient = $this->httpClientMulti;
				$httpClient->setUri( $this->url.$path );
				$httpClient->setConfig( $curlConfig );
			} else {
				$curlConfig['adapter'] = 'WoodWing_Http_Client_Adapter_PublishCurl';
				$httpClient = new Zend_Http_Client( $this->url.$path, $curlConfig );
			}			
		} catch( Exception $e ) { // catches Zend_Validate_Exception, Zend_Uri_Exception, etc (BZ#27253)
			$message = BizResources::localize( 'DPS_ERR_CANT_CONNECT_DPS_SERVER' );
			$message .= ' ' . BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );
			$detail = 'Connection to Adobe Distribution Server failed:'. $e->getMessage();
			throw new BizException( null, 'ERROR', $detail, $message );
		}
		return $httpClient;
	}

	/**
	 * Check if the given Adobe DPS service url can be reached.
	 *
	 * @param integer $curlErrNr
	 * @return bool
	 */
	public function connectionCheck( &$curlErrNr = null )
	{
		$httpClient = $this->createHttpClient( '' );
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
	
	/**
	 * Sets the Enterprise Server version with build number 
	 * with the following notation:
	 * WW-ENT-7.6.4.589
	 * @return string Dps client version with WW-ENT prefix and Enterprise server version.
	 */
	private function getDpsClientVersion()
	{
		list( $serverVer,,$serverBuild ) = explode( ' ', SERVERVERSION ); // $serverVer = 7.6.4, $serverBuild = 589
		return 'WW-ENT-'. $serverVer . '.' . $serverBuild;
	}
	
	/**
	 * Returns the DPS session id with the following notation:
	 * WW-XXXXXXXXX
	 * where XXXXXXXXX is the hashed $dpsId.
	 * @param string $dpsAccount DPS account username.
	 * @return string Dps session id with WW- prefix and hashed $dpsId.
	 */
	private function getDpsSessionId( $dpsAccount )
	{
		$hashedDpsId = hash( 'sha256', $dpsAccount );
		return 'WW-' . $hashedDpsId;
	}

	/**
	 * Sign the user into the Distribution Service using their Adobe ID and password. This call
	 * catches the session ticket to be used in subsequent calls and the Distribution Service account id.
	 *
	 * @param string $emailAddress
	 * @param string $pwd
	 * @throws BizException
	 * @throws Exception
	 */
	public function signIn( $emailAddress, $pwd )
	{
		$data = array(
			'emailAddress' => $emailAddress,
			'password' => $pwd,
			'sessionId' => $this->dpsSessionId,
			'clientVersion' => $this->dpsClientVersion
		);

		$httpClient = $this->createHttpClient( '/ddp/issueServer/signInWithCredentials' );
		$httpClient->setParameterPost( $data );
		$httpClient->setMethod( Zend_Http_Client::POST );

		$resultStatus = null;
		$httpCode = null;

		try{
			$xpath = $this->callService( $httpClient, 'signIn', $resultStatus, $httpCode );
		} catch( Exception $e ){
			// Set the httpCode if available, then rethrow the Exception.
			if (false !== strpos($e->getDetail(), "cURL request: Couldn't resolve host")){ // Host cannot be resolved through Socket.
				$httpCode = 404;
			}

			$this->httpCode = $httpCode;
			throw $e;
		}

		// When a sign in is performed and the server is running in debug mode
		// get the curl info for better support
		if ( LogHandler::debugMode() ) {
			$adapter = $httpClient->getAdapter();
			if ( $adapter instanceof Zend_Http_Client_Adapter_Curl ) {
				LogHandler::Log( 'AdobeDps', 'DEBUG', "cURL version info: " . print_r(curl_version(), true) );
			}
		}

		$this->ticket = (string)$xpath->query('/results/ticket/text()')->item(0)->nodeValue;
		$this->accountId = (string)$xpath->query('/results/accountId/text()')->item(0)->nodeValue;
	}

	/**
	 * Return the obtained ticket and accountId from Adobe DPS server.
	 * Should be called after signIn(). Can be used make session persistent (e.g. admin pages).
	 *
	 * @param string $ticket Returns the ticket.
	 * @param string $accountId Returns the accountId.
	 */
	public function getSignInInfo( &$ticket, &$accountId )
	{
		$ticket = $this->ticket;
		$accountId = $this->accountId;
	}
	
	/**
	 * Set ticket and accountId (as obtained earlier) to be used for Adobe DPS server.
	 * Should be called instead of signIn(). Can be used make session persistent (e.g. admin pages).
	 *
	 * @param string $ticket
	 * @param string $accountId
	 */
	public function setSignInInfo( $ticket, $accountId )
	{
		$this->ticket = $ticket;
		$this->accountId = $accountId;
	}

	/**
	 * Create a new issue at Adobe DPS.
	 *
	 * @param array  $brokers   List of brokers through which the issue can be purchased.
	 * @param string $productId Product id of this issue on the store.
	 * @param string $dpsFilter Keyword to filter on titles to show only selected issues in the DPS viewer's store.
	 * @return string Identifier of the issue.
	 */
	public function createIssue( $brokers = null, $productId = null, $dpsFilter = '' )
	{
		$data = array( 'ticket' => $this->ticket, 
			          'sessionId' => $this->dpsSessionId,
			          'clientVersion' => $this->dpsClientVersion );
		
		$data['filter'] = $dpsFilter;

		$brokersString = '';
		$comma = '';
		if( $brokers ) foreach( $brokers as $broker) {
			if ( $broker ) {
				$brokersString .= $comma.$broker;	
				$comma = ',';
			}
		}

		If ( $brokersString ) {
			$data['brokers'] = $brokersString;
		}	

		if( $productId ) {
			$data['productId'] 	= $productId;
		}

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues' );
		$httpClient->setParameterPost( $data );
		$httpClient->setMethod( Zend_Http_Client::POST );
		$xpath = $this->callService( $httpClient, 'createIssue' );

		$issueId = (string)$xpath->query('/results/issueId/text()')->item(0)->nodeValue;
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'Retrieved Issues Id:' . $issueId );
		return $issueId;
	}

	/**
	 * Returns the list of available issues (issue ids only) that are online at Adobe DPS.
	 * @since v7.5
	 *
	 * If a dimension value is provided, then include only issues with the specified target dimension. 
	 * If "all", then include all dimensions. If parameter is not provided, then only issues with 
	 * default iPad dimension ("1024x768") are included.
	 *
	 * @param boolean $allIssues       TRUE to get test and production issues. FALSE to get production issues only.
	 * @param string  $title           Magazine title. If specified the list is restricted to issues matching that publication.
	 * @param boolean $includeDisabled TRUE to include disabled issues also. Only relevant when $allIssues is TRUE.
	 * @param boolean $includeTest     TRUE to include 'test' issues also. Only relevant when $allIssues is FALSE.
	 * @param boolean $targetDimension See header above.
	 * @return array of issue ids
	 */
	public function getIssues( $allIssues = false, $title = null, 
		$includeDisabled = false, $includeTest = false, $targetDimension = false )
	{
		$data = array( 'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );		
		

		if( $allIssues ) {
			$data['ticket'] 		= $this->ticket;
		} else {
			$data['accountId'] 		= $this->accountId;
		}
		if( $title ) {
			$data['magazineTitle'] 	= $title;
		}
		if( $includeDisabled ) {
			$data['includeDisabled']= 'true';
		}
		if( $includeTest ) {
			$data['includeTest'] 	= 'true';
		}
		if( $targetDimension ) {
			$data['targetDimension']= $targetDimension;
		}
		
		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues' );
		$httpClient->setMethod( Zend_Http_Client::GET );
		$httpClient->setParameterGet( $data );
		$xpath = $this->callService( $httpClient, 'getIssues' );

		$issueIds = array();
 		$issues	= $xpath->query('/results/issues/issue');
		if( $issues->length > 0 ) foreach( $issues as $issue ) {
			if( !$issue->getAttribute( 'subpath' ) ) { // Exclude the issue created from Folio producer, since we can't do any operation on it
				$issueIds[] = $issue->getAttribute( 'id' );
			}
		}
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'Retrieved Issues Ids:' . print_r($issueIds,true) );
		return $issueIds;
	}

	/**
	 * Returns the list of available issues (with detailed info) that are online at Adobe DPS.
	 * @since v7.6
	 *
	 * If a dimension value is provided, then include only issues with the specified target dimension. 
	 * If "all", then include all dimensions. If parameter is not provided, then only issues with 
	 * default iPad dimension ("1024x768") are included.
	 *
	 * @param boolean $allIssues       TRUE to get test and production issues. FALSE to get production issues only.
	 * @param string  $title           Magazine title. If specified the list is restricted to issues matching that publication.
	 * @param boolean $includeDisabled TRUE to include disabled issues also. Only relevant when $allIssues is TRUE.
	 * @param boolean $includeTest     TRUE to include 'test' issues also. Only relevant when $allIssues is FALSE.
	 * @param boolean $targetDimension See header above.
	 * @return array of DPS issue infos. Index = DPS issue id, values = issue info (array).
	 */
	public function getIssueInfos( $allIssues = false, $title = null, 
		$includeDisabled = false, $includeTest = false, $targetDimension = false )
	{
		$data = array( 'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );

		if( $allIssues ) {
			$data['ticket'] 		= $this->ticket;
		} else {
			$data['accountId'] 		= $this->accountId;
		}
		if( $title ) {
			$data['magazineTitle'] 	= $title;
		}
		if( $includeDisabled ) {
			$data['includeDisabled']= 'true';
		}
		if( $includeTest ) {
			$data['includeTest'] 	= 'true';
		}
		if( $targetDimension ) {
			$data['targetDimension']= $targetDimension;
		}
		
		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues' );
		$httpClient->setMethod( Zend_Http_Client::GET );
		$httpClient->setParameterGet( $data );
		$xpath = $this->callService( $httpClient, 'getIssues' );

		/* Example response:
			<issue id="e5eda470-0373-481d-9f0d-7d458a71b5ca" productId="dps1 prod id" formatVersion="1.9.0" version="9" subpath="">
			  <magazineTitle>dps1 prod title</magazineTitle>
			  <issueNumber>dps1 volume nr</issueNumber>
			  <targetDimensions>
				<targetDimension>1024x768</targetDimension>
			  </targetDimensions>
			  <description>dps1 description</description>
			  <manifestXRef>dps1 prod id.3</manifestXRef>
			  <state>test</state>
			  <libraryPreviewUrl landscapeVersion="8" portraitVersion="8">http://edge.adobe-dcfs.com/ddp/issueServer/issues/e5eda470-0373-481d-9f0d-7d458a71b5ca/libraryPreview</libraryPreviewUrl>
			  <brokers>
				<broker>noChargeStore</broker>
			  </brokers>
			</issue>
		*/

		$issueInfos = array();
 		$issues	= $xpath->query('/results/issues/issue');
		if( $issues->length > 0 ) foreach( $issues as $issue ) {
			if( !$issue->getAttribute( 'subpath' ) ) { // Exclude the issue created from Folio producer, since we can't do any operation on it
				$issueId = $issue->getAttribute( 'id' );
				$issueInfos[$issueId]['version']     = $issue->getAttribute( 'version' );
				$issueInfos[$issueId]['productId']     = $issue->getAttribute( 'productId' );
				$issueInfos[$issueId]['magazineTitle'] = $this->getNodeText( $xpath, 'magazineTitle', $issue );
				$issueInfos[$issueId]['issueNumber'] = $this->getNodeText( $xpath, 'issueNumber', $issue );
				$issueInfos[$issueId]['targetDimensions'] = array();
				$targetDimensions = $xpath->query( 'targetDimensions/targetDimension', $issue );
				if( $targetDimensions->length > 0 ) {
					foreach( $targetDimensions as $targetDimension ) {
						$issueInfos[$issueId]['targetDimensions'][] = $this->getNodeText( $xpath, '', $targetDimension );
					}
				}
				$issueInfos[$issueId]['description']   = $this->getNodeText( $xpath, 'description', $issue );
				$issueInfos[$issueId]['manifestXRef']  = $this->getNodeText( $xpath, 'manifestXRef', $issue );
				$issueInfos[$issueId]['state']         = $this->getNodeText( $xpath, 'state', $issue );
				$libraryPreviewUrl = $xpath->query('libraryPreviewUrl', $issue );
				$libraryPreviewUrl = $libraryPreviewUrl->length > 0 ? $libraryPreviewUrl->item(0) : null;
				if( $libraryPreviewUrl ) {
					$issueInfos[$issueId]['libraryPreviewUrl'] = $this->getNodeText( $xpath, '', $libraryPreviewUrl );
					$issueInfos[$issueId]['landscapeVersion'] = (string)$libraryPreviewUrl->getAttribute('landscapeVersion');
					$issueInfos[$issueId]['portraitVersion'] = (string)$libraryPreviewUrl->getAttribute('portraitVersion');
				} else {
					$issueInfos[$issueId]['libraryPreviewUrl'] = '';
					$issueInfos[$issueId]['landscapeVersion'] = '';
					$issueInfos[$issueId]['portraitVersion'] = '';
				}
				$issueInfos[$issueId]['broker'] = $this->getNodeText( $xpath, 'brokers/broker', $issue );
				$issueInfos[$issueId]['publicationDate'] = $this->getNodeText( $xpath, 'publicationDate', $issue );
			}
		}
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'Retrieved Issues Ids:' . print_r(array_keys($issueInfos),true) );
		return $issueInfos;
	}

	/**
	 * Modifies an issue at Adobe DPS.
	 *
	 * @param string $issueId   Identifier of the issue.
	 * @param array  $brokers   List of brokers through which the issue can be purchased.
	 * @param string $state     Status of the issue: 'disabled', 'test' or 'production'.
	 * @param string $productId Product id of this issue on the store.
 	 * @param string $dpsFilter Keyword to filter on titles to show only selected issues in the DPS viewer's store.	 
	 */
	public function updateIssue( $issueId, $brokers = null, $state = null, $productId = null, $dpsFilter = '' )
	{
		$data = array( 'ticket' => $this->ticket,
			   		   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );

		// Filter is optional, and if it is unknown what the filter value was we should be able to skip this optional
		// parameter altogether.
		if (!is_null($dpsFilter)) {
			$data['filter'] = $dpsFilter;
		}
	
		$brokersString = '';
		$comma = '';
		if( $brokers ) foreach( $brokers as $broker) {
			if ( $broker ) {
				$brokersString .= $comma.$broker;	
				$comma = ',';
			}
		}

		If ( $brokersString ) {
			$data['brokers'] = $brokersString;
		}	
		
		if( $state ) {
			$data['state'] 		= $state;
		}
		if( $productId ) {
			$data['productId'] 	= $productId;
		}

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId );
		$httpClient->setMethod( Zend_Http_Client::POST ); // Adobe specifies "PUT" method, but that fails for Zend
		$httpClient->setParameterPost( $data );
		/*$xpath =*/$this->callService( $httpClient, 'updateIssue' );
	}

	/**
	 * Delete an issue from Adobe DPS.
	 *
	 * @param string $issueId  Identifier of the issue.
	 * @return bool
	 * @throws BizException
	 */
	public function deleteIssue( $issueId )
	{
		$data = array( 'ticket' => $this->ticket,
					   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'?'.http_build_query($data) );
		$httpClient->setMethod( Zend_Http_Client::DELETE );
		$statusCode = null;
		try {
			$this->callService( $httpClient, 'deleteIssue', $statusCode );
		} catch ( BizException $e ) {
			// When there is a status code and the status code is UNKNOWN_ERROR
			// we assume the issue is already removed from the DPS servers. Don't
			// throw the error so the server is in sync again with the DPS server.				
			if( $statusCode != 'UNKNOWN_ERROR' ) {
				throw $e;
			}	
		}
		return true;
	}

	/**
	 * Upload an issue library preview to Adobe DPS.
	 *
	 * @param string $issueId  Identifier of the issue.
	 * @param string $filePath Full path to the preview file (JPG/PNG format).
	 * @param string $contentType The mime type of the body of the request
	 * @param bool $landscape Indicates if the preview has landscape dimensions.
	 * 
	 */
	public function uploadIssueLibraryPreview( $issueId, $filePath, $contentType, $landscape = false )
	{
		$data = array( 'ticket' => $this->ticket,
					   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );
		$orientation = $landscape ? 'landscape' : 'portrait';

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/libraryPreview/'.$orientation );
		$this->setHandleForProgressBar( $httpClient, $filePath );
		$httpClient->setMethod( Zend_Http_Client::POST );
		$httpClient->setParameterPost( $data );
		$httpClient->setFileUpload( $filePath, 'filedata', null, $contentType );
		/*$xpath =*/$this->callService( $httpClient, 'uploadIssueLibraryPreview' );
	}
	
	/**
	 * Upload section cover to Adobe DPS.
	 *
	 * @since v7.6.7
	 * @param string $issueId  Identifier of the issue.
	 * @param string $filePath Full path to the preview file (JPG/PNG format).
	 * @param string $contentType The mime type of the body of the request
	 * @param string $articleId  Identifier of the article.
	 * @param bool $landscape Indicates if the preview has landscape dimensions.
	 * 
	 */
	public function uploadSectionCover( $issueId, $filePath, $contentType, $articleId, $landscape = false )
	{
		$data = array( 'ticket' => $this->ticket,
					   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );
		$orientation = $landscape ? 'landscape' : 'portrait';
		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/articles/'.$articleId.'/thumbnail/'.$orientation );		
		$this->setHandleForProgressBar( $httpClient, $filePath );
		// resetParameters() is needed when Parallel upload is enabled.
		// During the upload of cover, the first request will be uploaded correctly;
		// but for the following request(s), the new cover will be appended in the previous
		// request, thus resulting in wrong upload (uploading multiple covers within a request!).
		$httpClient->resetParameters( true );
		$httpClient->setMethod( Zend_Http_Client::POST );
		$httpClient->setParameterPost( $data );
		$httpClient->setFileUpload( $filePath, 'filedata', null, $contentType );
		/*$xpath =*/$this->callService( $httpClient, 'uploadSectionCover' );
	}
	

	/**
	 * Create a new issue manifest at Adobe DPS.
	 *
	 * @param string $issueId  Identifier of the issue.
	 * @param string $filePath Full path to the issue manifest file (XML format).
	 */
	public function uploadIssueManifest( $issueId, $filePath )
	{
		$data = array( 'ticket' => $this->ticket,
					   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/manifest' );
		$httpClient->setMethod( Zend_Http_Client::POST );
		$httpClient->setParameterPost( $data );
		$httpClient->setFileUpload( $filePath, 'filedata', null, 'text/xml' );
		/*$xpath =*/$this->callService( $httpClient, 'uploadIssueManifest' );
	}

	/**
	 * Modify an issue manifest at Adobe DPS.
	 *
	 * @param string $issueId  Identifier of the issue.
	 * @param string $filePath Full path to the issue manifest file (XML format).
	 */
	public function updateIssueManifest( $issueId, $filePath )
	{
		$data = array( 'ticket' => $this->ticket,
					   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/manifest' );
		// @TODO: The DPS connector side is not ready, it should keep track of the total upload size
		// of the issue manifest.
//		$this->setHandleForProgressBar( $httpClient, $filePath );
		
		$httpClient->setMethod( Zend_Http_Client::POST ); // Adobe specifies "PUT" method, but that fails for Zend
		$httpClient->setParameterPost( $data );
		$httpClient->setFileUpload( $filePath, 'filedata', null, 'text/xml' );
		/*$xpath =*/$this->callService( $httpClient, 'updateIssueManifest' );
	}

	/**
	 * Create a new article at Adobe DPS.
	 *
	 * The manifestXref needs to match the "id" attribute on the root "Folio" element in the article manifest. 
	 * These should both match the "id" attribute in the "contentStack" element on the overall folio manifest.
	 *
	 * The article id returned by the fulfillment server is a guid generated by the fulfillment server. It is 
	 * used by client's of the fulfillment server whenever future operation are performed on that article, 
	 * whether updating or downloading the article.
	 *
	 * Since 7.6.13 / 8.3.1 / 9.1.0 the $protected parameter has been renamed to $articleAccess and the supported
	 * values have been changed. The value indicates how the article can be accessed using social sharing.
	 * Possible values are:
	 * - 'Metered'(default): Article is available to readers who have not purchased the folio only up to the limit set by the publisher. (Formerly set to false).
	 * - 'Protected': Article is only available for readers who have paid for it. (Formerly set to true)
	 * - 'Free': Article is available for all readers. (Formerly not supported)
	 *
	 * @param string $issueId      Identifier of the issue.
	 * @param string $filePath     Full path to the article manifest file (XML format).
	 * @param string $manifestXref See header above.
	 * @param string $articleAccess  See header above.
	 * @return string Identifier of the article.
	*/
	public function uploadArticle( $issueId, $filePath, $manifestXref, $articleAccess = 'Metered' )
	{
		$dpsAccess = array( 'Metered' => 'open', 'Protected' => 'closed', 'Free' => 'free' );

		$data = array(	'ticket'           => $this->ticket,
						'uncompressedSize' => filesize($filePath),
						'md5Checksum'      => md5_file($filePath),
						'manifestXref'     => $manifestXref,
						'access'           => $dpsAccess[$articleAccess],
						'sessionId'        => $this->dpsSessionId,
			            'clientVersion'    => $this->dpsClientVersion
				);

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/articles' );
		$this->setHandleForProgressBar( $httpClient, $filePath );		
		$httpClient->resetParameters( true );
		$httpClient->setMethod( Zend_Http_Client::POST );
		$httpClient->setParameterPost( $data );
		$httpClient->setFileUpload( $filePath, 'filedata', null, 'application/vnd.adobe.folio+zip' );
		$xpath = $this->callService( $httpClient, 'uploadArticle' );

		if( !$this->inParallelMode() ) {
			$articleId = (string)$xpath->query('/results/articleId')->item(0)->nodeValue;
			LogHandler::Log( 'AdobeDps', 'DEBUG', 'Retrieved Article Id:' . $articleId );
			return $articleId;
		}
		return null;
	}

	/**
	 * Modify an article at Adobe DPS.
	 *
	 * Since 7.6.13 / 8.3.1 / 9.1.0 the $protected parameter has been renamed to $articleAccess and the supported
	 * values have been changed. The value indicates how the article can be accessed using social sharing.
	 * Possible values are:
	 * - 'Metered'(default): Article is available to readers who have not purchased the folio only up to the limit set by the publisher. (Formerly set to false).
	 * - 'Protected': Article is only available for readers who have paid for it. (Formerly set to true)
	 * - 'Free': Article is available for all readers. (Formerly not supported)
	 *
	 * @param string $articleId
	 * @param string $issueId      Identifier of the issue.
	 * @param string $issueId      Identifier of the article.
	 * @param string $filePath     Full path to the article manifest file (XML format).
	 * @param string $manifestXref See header of uploadArticle() function.
	 * @param string $articleAccess See header above.
	 * @return void
	*/
	public function updateArticle( $issueId, $articleId, $filePath, $manifestXref, $articleAccess = 'Metered' )
	{
		$dpsAccess = array( 'Metered' => 'open', 'Protected' => 'closed', 'Free' => 'free' );
		$data = array(	'ticket'           => $this->ticket,
						'uncompressedSize' => filesize($filePath),
						'md5Checksum'      => md5_file($filePath),
						'manifestXref'     => $manifestXref,
						'access'           => $dpsAccess[$articleAccess],
						'sessionId' => $this->dpsSessionId,
			            'clientVersion' => $this->dpsClientVersion
		);

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/articles/'.$articleId );
		$this->setHandleForProgressBar( $httpClient, $filePath );
		$httpClient->resetParameters( true );
		$httpClient->setMethod( Zend_Http_Client::POST );
		$httpClient->setFileUpload( $filePath, 'filedata', null, 'application/vnd.adobe.folio+zip' );
		$httpClient->setParameterPost( $data );
		/*$xpath =*/$this->callService( $httpClient, 'updateArticle' );
	}

	/**
	 * Delete an article from Adobe DPS.
	 *
	 * @param string $issueId   Identifier of the issue.
	 * @param string $articleId Identifier of the article.
	 * @return bool
	 * @throws BizException
	 */
	public function deleteArticle( $issueId, $articleId )
	{
		$data = array( 'ticket' => $this->ticket,
					   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/articles/'.$articleId.'?'.http_build_query($data) );
		$httpClient->setMethod( Zend_Http_Client::DELETE );

		$statusCode = null;
		try {
			$this->callService( $httpClient, 'deleteArticle', $statusCode );
		} catch ( BizException $e ) {
			// When there is a status code and the status code is UNKNOWN_ERROR
			// we assume the article is already removed from the DPS servers. Don't
			// throw the error so the server is in sync again with the DPS server.
			if( $statusCode != 'UNKNOWN_ERROR' ) {
				throw $e;
			}	
		}
		return true;
	}

	/**
	 * This call is used to retrieve the issue catalog for a given issue. 
	 * This service is called by Akamai edge servers.
	 *
	 * @param string $issueId   Identifier of the issue.
	 */
	public function downloadIssueCatalog( $issueId )
	{
		$data = array( 'ticket' => $this->ticket,
					   'sessionId' => $this->dpsSessionId,
			           'clientVersion' => $this->dpsClientVersion );
		
		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/catalog' );
		$httpClient->setMethod( Zend_Http_Client::GET );
		$httpClient->setParameterGet( $data );
		$xpath = $this->callService( $httpClient, 'downloadIssueCatalog' );

 		$articles = $xpath->query('/results/issue/articles/article');

 		$articleIds = array();
 		if( $articles->length > 0 ) foreach( $articles as $article ) {
			$articleIds[] = $article->getAttribute( 'id' );
		}
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'Retrieved Article Ids:' . print_r($articleIds,true) );
	}

	/**
	 * This call is used to retrieve the issue bundle for a given issue and version.
	 *
	 * @param string $issueId        Identifier of the issue.
	 * @param string $issueVersion   Version of the issue.
	 * @return string Filepath to the downloaded file
	 * @throws BizException
	 */
	public function downloadIssueBundle( $issueId, $issueVersion )
	{
		$data = array( 'ticket' => $this->ticket );

		$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/140aac58-69d7-4dcb-b72f-21e6f2017586/50/bundle?ticket=VGp1TXZtcUFGR2V4dWdiVk1OTFcyVDJWaTFrMmRGcUx0RDRoenhtU0VldlNoZjVGM2lheDdVVjBWVnptUzBOSzNBdzJvcXFLUmhiZWZOV2RkcW14aHR3VmZOUU5DbkdNMUxwMmpQU2FVTC9DZXgxK3QzbThwNnNyejlCdk9lMFJpSklmdmIrcUZHUFlTcmNiZEplR1lrdDA2SGNoWG1QdUErOTN4TGNqV1prPSoqb3E2YjIzaTFvbHZhaTcwaXBucDAqKmIzZGEyNjUwMWNiYjQ4NGZhYWM4NTRiYzBhMThjYmFjKipCN0E4OTZEQzRGNjNGRTQ3MDEwMTkzNjJGNjgyRjNERTg0NTFEQzYxMTYwM0U3N0ExRjAwQjA5N0ExNjhEN0IzQTI5QUUzNkJFNDJCMDc2NTFBMkUwNzg2MjIxOTAwMjIzOTZGNTZBRTc3M0ZGN0YyMTEwM0EyRDRCQzE0MjE0Q0JFQ0NDQjVERTM1MDcyMDA5Mjk3RDI5QUQ0RDg4NjJGMTY2QzhDOEY1QzQxRTdFQUE1MUZEM0UwMUE3OTk5NTEwQzM4Rjg3MjY0RjE0OUY3RDdGOEEwMkE0NjJGMjZCOUQ2QUY0RUI3QUNCQTYxMTQ3MTcxQzk3QjFFQUU5MkE1NjVGMkIwMjg5MkJDMEQ4MjhGQjZEMjA3NTkwODI3NjI3Qzc3Qzg2NERDMTRGMUEzQkRCOTczMkRFQTYzM0M1QUNGOEExMzk5MUM0RjEwRTBBRDVFOTdCNTNCNjZCM0Q3NzQ1NEMzREM3RUNCQUM0NTAyRDhEMjUyREQzMzJBMzcwM0I4MzU1QzVDODZCNUUzMkZCQjJFOEE3QzJBQ0MzQkI5MUMwMjdFKipCMzZENTI1MDM5MjMyMjdBOTVCQjdEMjBBREIxNTYzMDJENkI3RDg5QTgxMTQ2RkYwQkEyNTkwMkI1RTM4OUQwQkY3QkRBMTU4MkJCREExMjA1MjNERDJBOEIwMDA1N0Q4ODc3Mjg1NDE0Q0M4RTFDNkNFQjdBM0NCOUJGMTRCMzAwMjFDOUYyMzRCMUI5MkM5NDVFQjM3OTQ4QThGNEQ3RjM4RUZENkQ5MDExN0I5OEU2QkQwMTYxRDVGRDdCOTU0MTkxRjZCRTQwQ0UwRUQwNEEzRDE3MzE4NTk0NDkzMEE2OTFFNDUxNERDMUU0NjhCOTU2NzQwMkJGN0NGMzMxQTYyRkE5OUVCNDE0ODMyQzY0OTIxMDNDMUE2QTVEQTY1RkQ4MzE5QjgxMEJEMjQ4RjUyRTgxNTMzQ0U2NDk2MEM3RUNDRjg4RDEzMUE4QkZDOTVENzVCQTRCMEYyOTBBOTJCRDREMEE5NkRDOTI3RTE1RDk4QjdERDkzM0RDNDkzNjhEQzEyNzk0OTQwQ0JEQTU5NkE2OUE1QzQzQ0MyQUVGMjYwQjBBKipyZW5nYSpuYTFyKjEzN2RjMTM2YTQxKlRNUEo0UlQwNDkyM0Q0TlNLWUpIR0E5Vkg0&sessionId=6265345D-34F1-227D-B826-DC137B68B0AB&clientVersion=wc-690053' );

		//$httpClient = $this->createHttpClient( '/ddp/issueServer/issues/'.$issueId.'/'.$issueVersion.'/bundle' );
		$httpClient->setMethod( Zend_Http_Client::GET );
		$httpClient->setParameterGet( $data );
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'Retrieving folio bundle: id=['.$issueId.'], version=['.$issueVersion.']' );

		$filePath = tempnam(TEMPDIRECTORY, "dps_bundle_");
		// redirect the bundle content directly to the temporary file
		$httpClient->setStream( $filePath );

		$httpCode = 0;
		$resultStatus = null;

		try {
			$this->callService( $httpClient, 'downloadIssueBundle', $resultStatus, $httpCode );
		} catch ( BizException $e ) {
			// An attempt to clean the file
			@unlink($filePath);
			throw $e;
		}

		return $filePath;
	}

	/**
	 * Fire a push notification request (to the Adobe Distribution Server) for a given issue id.
	 * 
	 * @since 7.6
	 * @param string $issueId Identifier of the issue.
	 * @param string $notificationType: 'ALL', 'NEWSSTAND' or 'REGULAR'
	 * @throws BizException
	 */
	public function pushNotificationRequest( $issueId, $notificationType )
	{
		// Inform the end user in case of error, by raising the problem.
		if ( !$this->sendPushNotificationRequest( $issueId, $notificationType ) ) {
			$message = BizResources::localize( 'DPS_ERR_COULD_NOT_SEND_PUSH_NOTIFICATION' );
			$detail = BizResources::localize( 'DPS_ERR_COULD_NOT_SEND_PUSH_NOTIFICATION_REASON' );
			throw new BizException( null, 'ERROR', $detail, $message );
		}
	}

	/**
	 * Returns the HTTP code set on this DigitalPublishingSuiteClient.
	 *
	 * @return null|int The numeric httpCode if set, or null if not set.
	 */
	public function getHttpCode()
	{
		return $this->httpCode;
	}

	/**
	 * Sends out a push notification request for the given notification type.
	 * The Adobe Service API states that there are 3 notification types. ALL, REGULAR
	 * and NEWSSTAND. Where ALL sends both the REGULAR and NEWSSTAND push notification.
	 * After a long discussion with Adobe it turns out that they didn't test the ALL
	 * type. It doesn't seem to work properly.
	 *
	 * @since 7.6
	 * @param string $issueId
	 * @param string $notificationType: 'ALL', 'NEWSSTAND' or 'REGULAR'
	 * @return bool
	 */
	private function sendPushNotificationRequest( $issueId, $notificationType )
	{
		$data = array(
			'ticket'		   => $this->ticket,
			'issueId'	       => $issueId,
			'notificationType' => $notificationType,
		    'sessionId' => $this->dpsSessionId,
            'clientVersion' => $this->dpsClientVersion			
		);

		$retVal = false; // assume failure.
		$resultStatus = null;
		$xpath = null;

		try {
			$httpClient = $this->createHttpClient( '/ddp/issueServer/notification' );
			$httpClient->setMethod( Zend_Http_Client::POST );
			$httpClient->setParameterPost( $data );
			$xpath = $this->callService( $httpClient, 'pushNotificationRequest', $resultStatus, $this->httpCode );
		} catch( BizException $e ) {
		}

		if ($xpath) { // if there is a xpath the notification is sent.
			$retVal = true;
		} else { // update was unsuccessful, notify the user.
			$message = 'An error was thrown while sending the '.$notificationType.' push notification.';
			LogHandler::Log( 'AdobeDps', 'ERROR', $message );
		}
		return $retVal;
	}

	/**
	 * Return the text from a child element.
	 *
	 * @param DOMXPath $xPath
	 * @param string $propName Child element name.
	 * @param DOMNode $parentNode
	 * @return string Text read from child node. Empty when child node does not exist.
	 */
	private function getNodeText( $xPath, $propName, $parentNode )
	{
		if( $propName ) {
			$propName .= '/';
		}
		$textNode = $xPath->query( $propName.'text()', $parentNode );
		return $textNode->length > 0 ? $textNode->item(0)->nodeValue : '';
	}
	
	/**
	 * Runs a service request at Adobe DPS (REST server) and returns the response.
	 * Logs the request and response at Enterprise Server logging folder.
	 * 
	 * @param Zend_Http_Client $httpClient  Client connected to Adobe DPS.
	 * @param string $serviceName           Service to run at Adobe DPS.
	 * @param string $resultStatus          Retrieves the result status from Adobe server when no HTTP error occurs.
	 * @param int $httpCode                 Retrieves the HTTP code from communication with Adobe server.
	 * @param int $retry                    Used for calling this function recursively
	 * @return DOMXPath XPath object that can be used to query details from the server XML response.
	 * @throws BizException on communication errors.
	 */
	private function callService( $httpClient, $serviceName, &$resultStatus = null, &$httpCode = null, $retry = 0 )
	{
		if( $this->inParallelMode() ) {
			$connId = $httpClient->getCurrentConnectionId();
			PerformanceProfiler::startProfile( 'Calling Adobe DPS #'.$connId, 1 );
			$poolData = array( 'serviceName' => $serviceName );
			$this->requestPool->saveData( 'callService', $connId, $poolData );
			$httpClient->request();
			$xpath = null;
		} else {
			// Call the remote Adobe DPS service and monitor profiling
			PerformanceProfiler::startProfile( 'Calling Adobe DPS', 1 );
			$e = null;
			try {
				$response = $httpClient->request();
			} catch( Exception $e ) { // BizException, Zend_Http_Client_Exception, Zend_Http_Client_Adapter_Exception, etc
				$response = null;
			}
			PerformanceProfiler::stopProfile( 'Calling Adobe DPS', 1 );
			
			$xpath = $this->handleResponse( $httpClient, $serviceName, $response, $e, $resultStatus, $httpCode, $retry );
		}
		return $xpath;
	}
	
	private function handleResponse( $httpClient, $serviceName, $response, $e, &$resultStatus, &$httpCode, $retry )
	{
		// Log request and response (or error)
		LogHandler::Log( 'AdobeDps', 'DEBUG', 'Called Adobe DPS service '.$serviceName );
		if( $response && defined('LOG_DPS_SERVICES') && LOG_DPS_SERVICES ) {
			$adobeDpsServiceName = 'AdobeDPS_'.$serviceName;
			LogHandler::logService( $adobeDpsServiceName, $httpClient->getLastRequest(), true, 'REST', null, true );
			if( $response->isError() ) {
				LogHandler::logService( $adobeDpsServiceName, $response->asString(), null, 'REST', null, true );
			} else {
				LogHandler::logService( $adobeDpsServiceName, $response->asString(), false, 'REST', null, true );
			}
		}
		
		// Get HTTP response data.
		if( $response ) {
			$httpCode = $response->getStatus();
			$responseBody = $response->getBody();
		} else {
			$httpCode = null;
			$responseBody = null;
		}
		
		// Parse the XML response from REST server's response.
		$dom = new DOMDocument();
		if( $responseBody && $serviceName != 'downloadIssueBundle' ) {
			$dom->loadXML( $responseBody );
			$xpath = new DOMXPath( $dom );
			$results = $xpath->query( '/results' )->item(0);
		} else {
			$xpath = null;
			$results = null;
		}
		if( $results ) {
			$resultStatus = $results->getAttribute( 'status' );
			$resultMessage = $results->getAttribute( 'message' );
		} else {
			$resultStatus = null;
			$resultMessage = null;
		}
		
		// Raise exception on service error
		if( !$response ||                  // Fatal comm error
			$response->isError() ||        // HTTP 4xx or 5xx
			$httpCode == 204 ||            // Uberbundle isnt available for download
			($resultStatus != 'SUCCESS' && $serviceName != 'downloadIssueBundle') ) { // Adobe error, or empty response body

			// When there is no response, this could be a curl error
			if ( !$response ) {
				$curl = null;
				if( $httpClient instanceof Zend_Http_Client ) {
					$adapter = $httpClient->getAdapter();
					if ( $adapter instanceof Zend_Http_Client_Adapter_Curl ) {
						$curl = $adapter->getHandle();
					}
				} else if( $httpClient instanceof WW_Utils_HttpClientMultiCurl ) {
					$curl = $httpClient->getCurlHandle();
				}
				if( $curl ) {
					$errno = curl_errno($curl);
					$error = curl_error($curl);
					// Log the curl error
					LogHandler::Log( 'AdobeDps', 'ERROR', 'Error while calling '.$serviceName.'. cURL error: '.$error.'. cURL error number: '.$errno );

					// BZ#29336 - Check for the ssl error 104, if this is the case retry 3 times before giving an error.
					if ( ($errno == CURLE_RECV_ERROR || $errno == CURLE_GOT_NOTHING)
						&& ( strpos($error, 'SSL') !== false && strpos($error, 'errno 104') !== false )
						&& ( $retry < 3 )
					) {

						$retry++;
						LogHandler::Log('AdobeDps', 'INFO', 'Retry ('.$retry.') to call service '.$serviceName);
						return $this->callService($httpClient, $serviceName, $resultStatus, $httpCode, $retry);
					}
				}
			}

			// Build error message.
			if( $e ) {
				$message = BizResources::localize( 'DPS_ERR_FAILED_COMMUNICATION_WITH_DPS_SERVER' );
			} else {
				$message = BizResources::localize( 'DPS_ERR_DPS_SERVER_ERROR' );
			}
			$message .= ' '.BizResources::localize( 'ERR_PLEASE_CONTACT_YOUR_ADMIN' );

			// Build error detail.
			$detail = 'Adobe Distribution Server returned error while calling "'.$serviceName.'". ';
			if( $httpCode ) {
				$detail .= 'HTTP code: '.$httpCode.'. ';
			}
			if( $e ) { // Generic HTTP problem:
				$detail .= 'HTTP message: "'.$e->getMessage().'". ';
			} else { // Adobe Server specific HTTP problem:
				$detail .= 'HTTP message: "'.$this->getMessageForHttpCode( $httpCode ).'". ';
			}
			
			// Add Adobe results to error detail.
			if( $resultMessage ) {
				$detail .= 'Adobe results message: "'.$resultMessage.'". ';
			}
			if( $resultStatus ) {
				$detail .= 'Adobe results status: "'.$resultStatus.'". ';
			}
			throw new BizException( null, 'ERROR', $detail, $message );
		}
		return $xpath;
	}

	/**
	 * Determines the Adobe DPS message for a given HTTP (error) code.
	 *
	 * @param int $httpCode
	 * @return string The error message (English only).
	 */
	private function getMessageForHttpCode( $httpCode )
	{
		switch( $httpCode ) {
			case 200: 
				$msg = 'Success: Successfully queued up send notification requests to be sent by Apple.'; 
				break;
			case 204:
				$msg = 'Error: Uberbundle isn\'t available for download.';
				break;
			case 304:
				$msg = 'Error: Not modfied.';
				break;
			case 400:
				$msg = 'Error: Generic error.';
				break;
			case 409:
				$msg = 'Error: User has already queued up notification for issueId specified.';
				break;
			case 412:
				$msg = 'Error: Folio is invalid to send notifications - dimensions don\'t match an Apple folio, etc.';
				break;
			case 503:
				$msg = 'Error: User account has not been setup correctly with required certificates.';
				break;
			default:
				$msg = 'Unknown error.';
				break;
		}
		return $msg;
	}
	
	/**
	
	/**
	 * PHP does not provide the cURL handle for the file upload progress callback function.
	 * That causes a problem measuring the progress in case of parallel uploads.
	 * Therefore we create a helper and let the HttpClientMultiCurl set the handle.
	 *
	 * @param Zend_Http_Client $httpClient  Client connected to Adobe DPS.
	 * @param string $filePath The location of the file where the progressbar should handle on.
	 */
	private function setHandleForProgressBar( &$httpClient, $filePath )
	{

		// PHP does not provide the cURL handle for the file upload progress callback function.
		// That causes a problem measuring the progress in case of parallel uploads.
		// Therefore we create a helper and let the HttpClientMultiCurl set the handle.
		require_once BASEDIR.'/server/utils/UploadProgressPerFile.class.php';
		$multiProgress = new WW_Utils_UploadProgressPerFile( $filePath );

		// Set cURL options.
		$curlOptions = $this->curlOptions;
		$curlOptions[CURLOPT_NOPROGRESS] = false;
		$curlOptions[CURLOPT_PROGRESSFUNCTION] = array($multiProgress, 'curlProgressCallback');
		$curlOptions[CURLOPT_BUFFERSIZE] = 65536; // * See below.
		$httpClient->setConfig( array( 'curloptions' => $curlOptions ) );
		
		// * The callback freqency of the CURLOPT_PROGRESSFUNCTION depends on the CURLOPT_BUFFERSIZE.
		// Assumed is to have at least a 1 Mbit/s upstream connection. That can upload with
		// a speed of 128 KB/s. We want to have a progress update roughly every 1/2 second,
		// which should happen with a buffer size of 64 KB (=65536 bytes).
	}
	
	/**
	 * Tells if the DPS client can handle parallel article uploads.
	 * This feature is implemented with multi-curl and therefore cURL is required.
	 *
	 * @return bool
	 */
	public function canHandleParallelUploads()
	{
		// The following hidden option is introduced in case of troubles introducing
		// the new parallel upload feature (since 7.6.7). Customers can then suppress the 
		// whole feature by adding the option to the config_dps.php file and set it to false.
		// Since it is hidden, when the option is missing, the feature is assumed to be enabled.
		$enabled = !defined( 'PARALLEL_PUBLISHING_ENABLED' ) || PARALLEL_PUBLISHING_ENABLED == true;

		// Additional checking on max connection option, it must have value greater than 1
		// When value less than 1, the parallel upload feature will be disable
		$enabled = ( $enabled && defined( 'PARALLEL_PUBLISHING_MAX_CONNECTIONS' ) && PARALLEL_PUBLISHING_MAX_CONNECTIONS > 1 ) ? true : false;
		return $enabled;
	}

	/**
	 * Get issue manifest properties value
	 *
	 * @param object $issue Issue object
	 * @param integer $deviceId Device Id also is an Edition Id
	 * @return array $issueProps
	 */
	public static function getIssueProps( $issue, $deviceId )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';

		$issueProps = array();
		// Leave out the device id in the id of the folio. Otherwise the iPad 1/2 and iPad 3 issues, that are the same will appear twice on an iPad 3
		$issueProps['id']				= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_PRODUCTID' );
		$issueProps['productId']		= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_PRODUCTID' );
		$issueProps['orientation']		= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_PAGE_ORIENTATION' );
		$issueProps['navigation']		= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_NAVIGATION' );
		$issueProps['bindingDirection']	= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_READINGDIRECTION' );
		$issueProps['magazineTitle']	= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_PUBLICATION_TITLE' );
		$issueProps['description']		= $issue->Description;
		$issueProps['folioNumber']		= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_VOLUMENUMBER' );
		$issueProps['date']				= $issue->PublicationDate;
		$issueProps['dpsFilter']		= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_FILTER' );
		$issueProps['targetViewer']	    = BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_TARGET_VIEWER_VERSION' );
		$issueProps['coverDate']		= BizAdmProperty::getCustomPropVal( $issue->ExtraMetaData, 'C_DPS_COVER_DATE' );
		
		return $issueProps;
	}

	/**
	 * Get dossier properties value
	 *
	 * @param object $dossier Dossier object
	 * @return array $dossierProps
	 */
	public static function getDossierProps( $dossier )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';

		$dossierProps = array();
		$dossierProps['title']		            = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_READER_LABEL' );
		$dossierProps['author']		            = $dossier->MetaData->SourceMetaData->Author;
		$dossierProps['kicker']		            = self::getKickerContent( $dossier );
		$dossierProps['description']            = $dossier->MetaData->ContentMetaData->Description;
		$dossierProps['tags'] 		            = $dossier->MetaData->ContentMetaData->Keywords;
		$dossierProps['isAdvertisement']        = (bool) BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_DOSSIER_IS_AD' );
		$dossierProps['alwaysDisplayOverlays']  = (bool) BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_OVERLAYS_IN_BROWSE' );
        $dossierProps['intent']                 = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_DOSSIER_INTENT' );
		$dossierProps['hideFromTOC']            = (bool) BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_HIDE_FROM_TOC');
		$dossierProps['section']                = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_DPS_SECTION');
		$dossierProps['articleNavigation']      = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_DOSSIER_NAVIGATION');
		return $dossierProps;
	}

	/**
	 * From version 9.7.0 onwards the kicker information can be stored in a separate custom property C_KICKER. Until
	 * version 9.7.0 the kicker information was stored in the standard property 'slugline'. If the kicker info of the
	 * 'slugline' is migrated to the new custom property then that one is picked up. If not, C_KICKER is not set or is
	 * empty, and the 'slugline' is filled the kicker info is retrieved from the 'slugline'.
	 *
	 * @param Object $dossier
	 * @return string The kicker info
	 * @since 9.7.0
	 */
	static private function getKickerContent( $dossier )
	{
		$kicker = BizAdmProperty::getCustomPropVal( $dossier->MetaData->ExtraMetaData, 'C_KICKER' );
		if( !is_null( $kicker ) && !empty( $kicker ) ) {
			return $kicker;
		} elseif( !empty( $dossier->MetaData->ContentMetaData->Slugline ) ) {
			return ( $dossier->MetaData->ContentMetaData->Slugline );
		}

		return '';
	}

	// - - - - - - - - - - - - - PARALLEL UPLOAD - - - - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Skip the article and call the processedArticle callback function.
	 *
	 * @param $connId
	 * @param $articleId
	 * @return void
	 */
	public function skipArticle( $connId, $articleId )
	{
		call_user_func_array( $this->processedArticleCB, array( $connId, $articleId ) );
	}

	/**
	 * Called once to trigger parallel upload for all articles. It accepts callback functions. 
	 * It calls back when there is a slot free in the multi-curl connection pool to fire 
	 * another network request or when a response came back from the network.
	 *
	 * @param array $processNextArticleCB Called back when it is time to fire the next upload request.
	 * @param array $processedArticleCB Called back when a response arrived (from upload request).
	 */
	public function publishArticlesParallel( $processNextArticleCB, $processedArticleCB )
	{
		// Remember callback functions of the waiting connector class.
		$this->processNextArticleCB = $processNextArticleCB;
		$this->processedArticleCB = $processedArticleCB;

		// Init request pool that helps us finding back original request data.
		// There are many outstanding requests which are identified by connection id.
		require_once BASEDIR.'/server/utils/ParallelCallbackCache.class.php';
		$this->requestPool = new WW_Utils_ParallelCallbackCache();
		
		// Init the multi-curl client and let it loop while calling back for requests.
		// This is the main loop as long as there are articles to be published.
		// Once completed, the response data is assumed to be cached by the calling classes.
		require_once BASEDIR.'/server/utils/HttpClientMultiCurl.class.php';
		$this->httpClientMulti = new WW_Utils_HttpClientMultiCurl();
		$this->httpClientMulti->requestMulti( PARALLEL_PUBLISHING_MAX_CONNECTIONS, 
			array( $this, 'processNextArticle' ), array( $this, 'processedArticle' ) );
			
		// Once come back from call above, everything is done, so time to clear memory.
		$this->requestPool->clearCache();
		$this->httpClientMulti = null;
	}
	
	/**
	 * Called back by the multi-curl adapter (httpClientMulti) when it is time to fire
	 * another uploadArticle / updateArticle request. This could happen when it is initializing
	 * the request pool, or when a response was completely handled and a slot in the pool 
	 * became available for reusage.
	 * It calls back the waiting DPS connector (through its callback function as registered
	 * at the publishArticlesParallel() function) to let it fire the next request.
	 * That function should return TRUE when the next request was fired or FALSE when no more to fire.
	 *
	 * @param string $connId Connection id for this request (at the request pool).
	 * @return bool Returns TRUE when the next request was fired or FALSE when no more to fire.
	 */
	public function processNextArticle( $connId )
	{
		$this->requestPool->clearData( 'callService', $connId );
		return call_user_func_array( $this->processNextArticleCB, array( $connId ) );
	}
	
	/**
	 * Called back by the multi-curl adapter (httpClientMulti) when a response has arrived
	 * for any of the pending uploadArticle / updateArticle requests.
	 * It calls back the waiting DPS connector (through its callback function as registered
	 * at the publishArticlesParallel() function) to let it cache the arrived response data.
	 *
	 * @param string $connId Connection id for this request (at the request pool).
	 * @param string $response Raw HTTP response data.
	 * @param object $exception BizException object thrown from httpClientMulti
	 */
	public function processedArticle( $connId, $response, $exception = null )
	{
		// For each connection id in the request pool, DPS requests are profiled.
		// (With only one profile we would have no idea when to start/stop profiling.)
		PerformanceProfiler::stopProfile( 'Calling Adobe DPS #'.$connId, 1 );

		// For logging purposes, retrieve the service name from the request pool.
		$poolData = $this->requestPool->loadData( 'callService', $connId );
		$serviceName = $poolData['serviceName'];
		
		// Convert the raw HTTP response into an xPath in a XML DOM.
		$resultStatus = null; $httpCode = null; $retry = 0; $e = null; $dpsArticleId = null;
		if( !$exception ) {
			try {
				$xpath = $this->handleResponse( $this->httpClientMulti, $serviceName, $response, $e, $resultStatus, $httpCode, $retry );
				// Retrieve the external id from the DPS response for the created/updated DPS article.
				if( $serviceName == 'uploadArticle' ) {
					$dpsArticleId = (string)$xpath->query('/results/articleId')->item(0)->nodeValue;
					LogHandler::Log( 'AdobeDps', 'DEBUG', 'Retrieved DPS article id:' . $dpsArticleId );
				}	
			} catch ( BizException $exception2 ) {
				$e = $exception2;
			}
		} else {
			$e = $exception;
		}
		
		// Callback the waiting DPS connector to let it cache the arrived response data.
		call_user_func_array( $this->processedArticleCB, array( $connId, $dpsArticleId, $e ) );
	}

	public function uploadSectionCoversParallel( $processNextSectionCoverCB, $processedSectionCoverCB )
	{
		// Remember callback functions of the waiting connector class.
		$this->processNextSectionCoverCB = $processNextSectionCoverCB;
		$this->processedSectionCoverCB = $processedSectionCoverCB;
		
		// Init request pool that helps us finding back original request data.
		// There are many outstanding requests which are identified by connection id.
		require_once BASEDIR.'/server/utils/ParallelCallbackCache.class.php';
		$this->requestPool = new WW_Utils_ParallelCallbackCache();

		// Init the multi-curl client and let it loop while calling back for requests.
		// This is the main loop as long as there are section covers to be uploaded.
		// Once completed, the response data is assumed to be cached by the calling classes.
		require_once BASEDIR.'/server/utils/HttpClientMultiCurl.class.php';
		$this->httpClientMulti = new WW_Utils_HttpClientMultiCurl();
		$this->httpClientMulti->requestMulti( PARALLEL_PUBLISHING_MAX_CONNECTIONS, 
			array( $this, 'processNextSectionCover' ), array( $this, 'processedSectionCover' ) );
			
		// Once come back from call above, everything is done, so time to clear memory.
		$this->requestPool->clearCache();
		$this->httpClientMulti = null;		
		
	}

	public function processNextSectionCover( $connId )
	{
		$this->requestPool->clearData( 'callService', $connId );
		return call_user_func_array( $this->processNextSectionCoverCB, array( $connId ) );
	}

	public function processedSectionCover( $connId, $response )
	{
		// For each connection id in the request pool, DPS requests are profiled.
		// (With only one profile we would have no idea when to start/stop profiling.)
		PerformanceProfiler::stopProfile( 'Calling Adobe DPS #'.$connId, 1 );

		// For logging purposes, retrieve the service name from the sectionCoverRequest pool.
		$poolData = $this->requestPool->loadData( 'callService', $connId );
		$serviceName = $poolData['serviceName'];
		
		// Convert the raw HTTP response into an xPath in a XML DOM.
		$resultStatus = null; $httpCode = null; $retry = 0; $e = null;
		$this->handleResponse( $this->httpClientMulti, $serviceName, $response, $e, $resultStatus, $httpCode, $retry );
		
		// Callback the waiting DPS connector to let it cache the arrived response data.
		call_user_func_array( $this->processedSectionCoverCB, array( $connId ) );
	}

	/**
	 * Tells if the connector is running in parallel upload mode. That is, when the multi-curl
	 * network pool is initiated and all uploadArticle / updateArticle requests are handled
	 * in parallel. This is not the same as the canHandleParallelUploads() function that tells
	 * if the connector supports parallel uploads at all.
	 *
	 * @return bool TRUE when running in parallel mode. Else FALSE.
	 */
	public function inParallelMode()
	{
		return $this->httpClientMulti ? $this->httpClientMulti->inParallelMode() : false;
	}
	
	/**
	 * Can be used in parallel mode (see inParallelMode() function) to find out which
	 * connection in the multi-curl request pool is that active one. There can be only
	 * one active since we have only one execution point in PHP. The id returned is either
	 * the request that is about to get sent out or the response that just has arrived.
	 *
	 * @return int Connection id.
	 */
	public function getCurrentConnectionId()
	{
		return $this->httpClientMulti ? $this->httpClientMulti->getCurrentConnectionId() : 0;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
}
