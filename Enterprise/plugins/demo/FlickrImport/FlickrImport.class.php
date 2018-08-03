<?php

/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 */

// Plug-in config file
require_once dirname(__FILE__) . '/configImport.php';

// Zend framework:
require_once dirname(__FILE__) . '/WW/Service/Flickr.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';

class FlickrImport
{
	private $flickr = null;
	private $apiKey = null;
	private $apiSecret = null;
	private $token = null;
	private $ticket = null;
	private $publicationId = null;
	private $sectionId = null;
	private $stateId = null;
	
	/**
     * Reference to REST client object
     *
     * @var Zend_Rest_Client
     */
    protected $_restClient = null;
	/**
     * Base URI for the REST client
     */
    const URI_BASE = 'http://www.flickr.com';

	/**
	 * search
	 *
	 * Execute Flickr search
	 * 
	 * @param string	$min_upload_date	Minimum upload date
	 */
    public function search( $min_upload_date )
    {
    	$rows = array();
    	$min_unix_timestamp = strtotime( $min_upload_date ); // Convert the min_upload_date to unix timestamp

    	// User options
        $options = array('per_page' => FLICKR_MAX_IMPORT, 'min_upload_date' => $min_unix_timestamp, 'sort'  => 'date-posted-asc' );

    	if( defined( 'FLICKRIMPORT_USR_ACC' ) ) {
    		// Get Flickr User Id
    		if( strchr(FLICKRIMPORT_USR_ACC, '@') ) {
    			$options['user_id'] = $this->flickr->getIdByEmail(FLICKRIMPORT_USR_ACC);
    		}
			else {
            	$options['user_id'] = $this->flickr->getIdByUsername(FLICKRIMPORT_USR_ACC);
			}
    	}
    	else {
    		$msg = 'Flickr User Account Name not defined';
    		throw new BizException( '', 'Server', $msg, $msg );
    	}

    	$results = $this->flickr->tagSearch( '', $options );
    	LogHandler::Log('FlickrImport', 'INFO', "TOTALROWS:" . count($results));
        foreach ($results as $result) {
        	$exist = $this->checkExists($result->id);
        	if(!$exist) {
        		$this->import($result->id);
        	}
        }
        
    }

 	/**
	 * checkExists
	 *
	 * Check whether the image has been imported to Enterprise
	 * 
	 * @param string	$imgId		Image id
	 * @return boolean	Return true if found else false
	 */
    public function checkExists( $imgId )
    {
		$queryParams = array();
		$queryParams[] = new QueryParam ('DocumentID', 		'=', $imgId);
		$queryParams[] = new QueryParam ('PublicationId', 	'=', $this->publicationId);
		$queryParams[] = new QueryParam ('SectionId', 		'=', $this->sectionId);
		$queryParams[] = new QueryParam ('Source', 			'=', FLICKR_SOURCEID);
		$queryParams[] = new QueryParam ('Type', 			'=', 'Image');

		$reqProps = array('ID');

		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$service = new WflQueryObjectsService();
		$resp = $service->execute( new WflQueryObjectsRequest( 
								$this->ticket, 
								$queryParams, 
								null,
								0,
								null,
								null,
								null,
								$reqProps) );

		if( isset($resp->Rows) && count($resp->Rows)> 0) {
			return true;
		}
		else {
			return false;
		}
    }

    /**
	 * import
	 *
	 * Import photo to Enterprise
	 * 
	 * @param string	$imgId				Image id
	 * 
	 */
    public function import( $imgId )
    {
		$thumbContent = '';
		$previewContent = '';
		$nativeContent = '';
		$modifiedDate = null;
		$createdDate = null;
		$metaData = $this->getImage($imgId, 'native', $nativeContent);
		$this->getImage($imgId, 'preview', $previewContent);
		$this->getImage($imgId, 'thumb', $thumbContent);
		require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
		$native = new Attachment('native', $metaData['Format']);
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer($nativeContent, $native);
		$files = array($native);
		if (! empty($previewContent)) {
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$preview = new Attachment('preview', 'image/jpg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($previewContent, $preview);
			$files[] = $preview;
		}
		if( !empty($thumbContent) ) {
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$thumb = new Attachment('thumb', 'image/jpg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($thumbContent, $thumb);			
			$files[] = $thumb;
		}

		$meta = new MetaData();
		$this->fillMetaData( $meta, $imgId, $metaData );
			
		$newPhoto = array();
		$newPhoto[] = new Object( $meta, array(), null, $files, null, null, null );
		$modifiedDate = $newPhoto[0]->MetaData->WorkflowMetaData->Modified;
		$createdDate  = $newPhoto[0]->MetaData->WorkflowMetaData->Created;
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		try {
			$req = new WflCreateObjectsRequest( $this->ticket, false, $newPhoto, null , true );
			$service = new WflCreateObjectsService();
			$resp = $service->execute( $req );
			if ( $resp ) {
				require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

				$id 		= $resp->Objects[0]->MetaData->BasicMetaData->ID;
				$newRow 	= array();
				$modified 	= $modifiedDate;
				$newRow['created'] = $createdDate;

				$sth = DBObject::updateObject($id, FLICKR_WW_USERNAME, $newRow, $modified);
				if (!$sth)	{
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
			}
		} catch( BizException $e ) {
			$message 		= $e->getMessage();
		}
    }

    /**
	 * getImage
	 *
	 * Get Image file stream
	 * 
	 * @param string	$id				Image id
	 * @param string	$rendition		The request rendition
	 * @param string	$content		Content of the image stream
	 */
    public function getImage( $id, $rendition, &$content )
    {
    	$url = null;

    	$types['thumb'] 	= 'Thumbnail';
		$types['preview'] 	= 'Medium';
		$types['native'] 	= 'Original';

    	$image = $this->flickr->getImageDetails( $id );
    	if( empty($image['Original']->uri) ) {
	    	$types['native'] = 'Large';
	    }
	    $imgSize = $image[$types['native']];
   	
    	$url = $image[$types[$rendition]]->uri;
	    if( $url ) {
    		$content = self::httpRequest( $url, null, 'image/jpeg' );
	    }
	    if( $rendition == 'native') {
    		$imgInfo =  $this->flickr->getImageInfo( $id );
			return $this->getMetaData( $id, $imgInfo, $imgSize );
	    }
	    return;
    }

	public function __construct($ticket = null, $publicationId = null, $sectionId = null, $stateId = null)
	{
		if( defined( 'FLICKRIMPORT_API_KEY' ) ) {
    		$this->apiKey 		= FLICKRIMPORT_API_KEY;
    	}
        if( defined( 'FLICKRIMPORT_API_SECRET' ) ) {
            $this->apiSecret 	= FLICKRIMPORT_API_SECRET;
        }
        if( defined( 'FLICKRIMPORT_TOKEN' ) ) {
        	$this->token 		= FLICKRIMPORT_TOKEN;
        }
        $this->ticket 			= $ticket;
        $this->publicationId 	= $publicationId;
        $this->sectionId		= $sectionId;
        $this->stateId			= $stateId;
        require_once BASEDIR.'/server/authorizationmodule.php';
		global $globAuth;
		$globAuth = new authorizationmodule();
		$this->flickr = new WW_Service_Flickr( $this->apiKey, $this->apiSecret, $this->token );
	}

	/**
	 * getMetaData
	 *
	 * Get Image metadata
	 * 
	 * @param string	$id				Image id
	 * @param object	$info			Image informations object
	 * @param object	$img			Image details objects
	 */
	static final private function getMetaData( $id, $imgInfo, $imgSize )
    {
    	LogHandler::Log('FlickrImport', 'DEBUG', "FlickrImport:: Get image metadata");
    	require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
    	$metadata = array();

		$objName 				= $imgInfo->title;
		$sDangerousCharacters 	= "`~!@#$%^*\\|;:'<>/?\"";	// remove dangerous characters to prevent problems when creating object
		$objName 				= ereg_replace("[$sDangerousCharacters]", "", $objName);
		$objName 				= substr( $objName,0,27); 	// limit objname length on this point to 27 -9 = 18 chars
		$metadata['Id']			= (string) $id;
		$metadata['Name']		= $objName;
		$metadata['Type']		= 'Image';
		$metadata['Caption']	= $imgInfo->description;
    	$metadata['Created']	= self::convertDate( $imgInfo->datetaken );
    	$metadata['Modified']	= self::convertDate(date('Y-m-d H:i:s', $imgInfo->dateupload));
    	$metadata['Format']		= MimeTypeHandler::filePath2MimeType( $imgSize->uri );
    	$metadata['Source']		= FLICKR_SOURCEID;
    	$metadata['Width']		= $imgSize->width;
    	$metadata['Height']		= $imgSize->height;

		return $metadata;
    }

    /**
	 * convertDate
	 *
	 * Convert Flickr date (format like 2007-01-13 12:22:15) 
	 * into standard data string (like 2007-01-13T12:22:15)
	 */
    static final public function convertDate( $date )
    {
		$datesplit 	= explode( ' ' ,$date);
		$stdDate 	= $datesplit[0].'T'.$datesplit[1];

		return $stdDate;
    }

    /**
	 * fillMetaData
	 *
	 * Fill in the MetaData for the Alien Object
	 * 
	 * @param Object	$meta			Object of MetaData that will filled
	 * @param string	$imageID		Id of the Flickr photo
	 * @param string	$metaData		Object metadata that get from content source
	 */
	private function fillMetaData( &$meta, $imageID, $metaData )
	{
		// BasicMetaData
		$meta->BasicMetaData 				= new BasicMetaData();
		$meta->BasicMetaData->ID    		= $imageID;
		$meta->BasicMetaData->DocumentID    = $metaData['Id'];
		$meta->BasicMetaData->Type			= $metaData['Type'];
		$meta->BasicMetaData->ContentSource	= '';
		$meta->BasicMetaData->Name 			= $metaData['Name'];
		$meta->BasicMetaData->Publication 	= new Publication($this->publicationId);
		$meta->BasicMetaData->Category 		= new Category($this->sectionId);
		
		// ContentMetaData
		$meta->ContentMetaData 				= new ContentMetaData();
		$meta->ContentMetaData->Format		= $metaData['Format'] ;
		$meta->ContentMetaData->Width		= $metaData['Width'] ;
		$meta->ContentMetaData->Height		= $metaData['Height'] ;
		$meta->ContentMetaData->FileSize	= '';
		$meta->ContentMetaData->Dpi			= array_key_exists('Dpi', $metaData) ? $metaData['Dpi'] : '72'; // default to 72 dpi if not known
		$meta->ContentMetaData->ColorSpace	= array_key_exists('ColorSpace', $metaData) ? strtoupper($metaData['ColorSpace']) : '';
		$meta->ContentMetaData->Description = $metaData['Caption'];

		// RightsMetaData
		$meta->RightsMetaData 					= new RightsMetaData();
		$meta->RightsMetaData->Copyright		= array_key_exists('Copyright', $metaData) ? $metaData['Copyright'] : '';
		$meta->RightsMetaData->CopyrightMarked	= array_key_exists('CopyrightMarked', $metaData) ? $metaData['CopyrightMarked'] : 'false';
		$meta->RightsMetaData->CopyrightURL		= array_key_exists('CopyrightURL', $metaData) ? $metaData['CopyrightURL'] : '';

		// SourceMetaData
		$meta->SourceMetaData 				= new SourceMetaData();
		$meta->SourceMetaData->Credit		= array_key_exists('Credit', $metaData) ? $metaData['Credit'] : '';
		$meta->SourceMetaData->Source		= array_key_exists('Source', $metaData) ? $metaData['Source'] : '';
		$meta->SourceMetaData->Author		= array_key_exists('Author', $metaData) ? $metaData['Byline'] : '';
		$meta->SourceMetaData->Urgency		= array_key_exists('Priority', $metaData) ? $metaData['Priority'] : '';

		// WorkflowMetaData
		$meta->WorkflowMetaData 			= new WorkflowMetaData();
		$meta->WorkflowMetaData->Modified	= $metaData['Modified'];
		$meta->WorkflowMetaData->Created	= $metaData['Created'];
		$meta->WorkflowMetaData->State		= new State( $this->stateId );
	}

    /**
	 * @throws BizException on error
	 */
	static final private function httpRequest( $url, $params, $expContentType )
	{
		if( $params ) {
			$urlParams = http_build_query( $params );
		}
		LogHandler::Log('FlickrImport', 'DEBUG', "Flickr request: $url");

    	$retVal = '';
		require_once 'Zend/Http/Client.php';
		try {
			$http = new Zend_Http_Client();
			$http->setUri( $url );
			if( $params ) {
				foreach( $params as $parKey => $parValue ) {
					$http->setParameterGet( $parKey, $parValue );
				}
			}
			
			$response = $http->request( Zend_Http_Client::POST );
			if( $response->isSuccessful() ) {
				$gotContentType = self::getContentType( $response );
				if( $gotContentType == $expContentType ||
					($gotContentType == '' && $expContentType == 'image/jpeg') ) {
					$retVal = $response->getBody();
				} else { // error on unhandled content
					if( $gotContentType == 'text/html' && $expContentType == 'image/jpeg' ) {
						$respBody = $response->getBody();
						$msg = self::getErrorFromHtmlPage( $respBody );
						$msg = 'FlickrImport error: '.$msg;
				   	    LogHandler::Log('FlickrImport', 'ERROR',  $respBody ); // dump entire HTML page
						throw new BizException( '', 'Server', $msg, $msg );
					} else {
						$msg = "Unexpected content type. Received: $gotContentType. Expected: $expContentType.";
						LogHandler::Log('FlickrImport', 'ERROR', $msg .'. First 100 bytes: '. substr( $response->getBody(), 0, 100) );
						throw new BizException( '', 'Server', $msg, $msg );
					}
				}
			} else {
				self::handleHttpError( $response );
			}
		} catch (Zend_Http_Client_Exception $e) {
			throw new BizException( '', 'Server', 'FlickrImport::httpRequest failed.', 'FlickrImport error: '.$e->getMessage() );
		}
		return $retVal;
	}

	/**
	 * Returns the content-type paramters of the given http response.
	 *
	 * @param Zend_Http_Response $response
	 * @return string The content type
	 */
	static private function getContentType( $response )
	{
		$responseHeaders = $response->getHeaders();
		$contentType = $responseHeaders['Content-type'];
		// Strip other params that might follow, like "charset: windows-1252"
		$chuncks = explode( ';', $contentType );
		return $chuncks[0]; 
	}

    /**
	 * Checks status and throws exception on communication errors.
	 * Assumed is that response is an error.
	 * 
	 * @param Zend_Http_Response $response
	 * @throws BizException on error
	 */
	static private function handleHttpError( $response )
	{
		$responseHeaders = $response->getHeaders();
		$contentType = $responseHeaders['Content-type'];
		$respBody = $response->getBody();
		$respStatusCode = $response->getStatus();
		$respStatusText = $response->responseCodeAsText( $respStatusCode );
		
		if( $contentType == 'text/html' && 
   			$respStatusCode == 500 && 
  			($msg = self::getErrorFromHtmlPage($respBody)) ) {
			$msg = 'FlickrImport error: '.$msg;
	   	    LogHandler::Log('FlickrImport', 'ERROR',  $respBody ); // dump entire HTML page
			throw new BizException( '', 'Server', $msg, $msg );
    	}
		$msg = "Flickr connection problem: $respStatusText (HTTP code: $respStatusCode)";
		LogHandler::Log('FlickrImport', 'ERROR',  $respBody ); // dump entire HTML page
		throw new BizException( '', 'Server', $msg, $msg );
	}

	/**
	 * Assumes that the given response is an HTML page describing an error. 
	 * It takes out an header or title in the hope it tells us the error details.
	 * This typically happens when parameters are missing (programming errors).
	 * 
	 * @param $respBody string HTML page
	 * @return string The error details. Empty when none found.
 	 */
	static private function getErrorFromHtmlPage( $respBody )
	{
	   	$htmDoc = new DOMDocument();
       	$htmDoc->loadHTML( $respBody );
    	$xpath = new DOMXPath($htmDoc);
		$msgs = $xpath->query('//body/h2/text()'); // try h2 (most detailed)
		$msg = $msgs->length > 0 ? trim($msgs->item(0)->textContent) : '';
		if( empty($msg) ) { // try h1
			$msgs = $xpath->query('//body/h1/text()');
			$msg = $msgs->length > 0 ? trim($msgs->item(0)->textContent) : '';
		}
		if( empty($msg) ) { // try title
			$msgs = $xpath->query('//head/title/text()');
			$msg = $msgs->length > 0 ? trim($msgs->item(0)->textContent) : '';
		}
		return $msg;
	}
	
	/**
     * Request a frob used to get a token.
     *
     * @return  string frob value
     */
     public function getFrob()
     {
    	static $method = 'flickr.auth.getFrob';

    	$options = array('api_key' => $this->apiKey, 'method' => $method);
    	
		$options['api_sig'] = $this->signParams($options);

    	$restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        if ($response->isError()) {
        	$msg = 'An error occurred sending request. Status code: ' . $response->getStatus();
         	throw new BizException( '', 'Server', $msg, $msg );
        }
        
        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
		$frob = $xpath->query('//frob/text()')->item(0);
		return (string) $frob->data;
    }

    /**
     * Request a token base on frob
     *
     * @param   string $frob frob value
     * @return  string token value
     */
    public function getToken( $frob )
    {
    	static $method = 'flickr.auth.getToken';
    	
    	$options = array('api_key' => $this->apiKey, 'method' => $method, 'frob' => (string) $frob);
		$options['api_sig'] = $this->signParams($options);

    	$restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

         if ($response->isError()) {
            $msg = 'An error occurred sending request. Status code: ' . $response->getStatus();
         	throw new BizException( '', 'Server', $msg, $msg );
        }
        
        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
		$token = $xpath->query('//token/text()')->item(0);
		return (string) $token->data;
    }

    /**
     * Get a URL to request a token.
     *
     * @param   string $perms The desired permissions 'read', 'write', or
     *          'delete'.
     * @param   string $frob optional Frob
     * @return  string $url URL to reuest for token
     */
    public function getAuthUrl($perms, $frob ='') {
    	LogHandler::Log( 'FlickrImport', 'DEBUG', 'getAuthUrl' );
        $options = array('api_key' => $this->apiKey, 'perms' => $perms);
        if ($frob != '') {
            $options['frob'] = (string) $frob;
        }
        $options['api_sig'] = $this->signParams($options);
        
        $values = array();
		foreach($options as $key => $value) {
            $values[] = $key . '=' . $value;
        }
        
        $url = 'http://flickr.com/services/auth/?'. implode('&', $values);
        return $url;
    }

    /**
     * Throws an exception if and only if the response status indicates a failure
     *
     * @param  DOMDocument $dom
     * @return void
     * @throws Zend_Service_Exception
     */
    protected static function _checkErrors(DOMDocument $dom)
    {
        if ($dom->documentElement->getAttribute('stat') === 'fail') {
            $xpath = new DOMXPath($dom);
            $err = $xpath->query('//err')->item(0);
            $msg = 'Search failed due to error: ' . $err->getAttribute('msg') . ' (error #' . $err->getAttribute('code') . ')';
         	throw new BizException( '', 'Server', $msg, $msg );
        }
    }

    /**
     * Returns a reference to the REST client, instantiating it if necessary
     *
     * @return Zend_Rest_Client
     */
    public function getRestClient()
    {
        if (null === $this->_restClient) {
            /**
             * @see Zend_Rest_Client
             */
            require_once 'Zend/Rest/Client.php';
            $this->_restClient = new Zend_Rest_Client(self::URI_BASE);
        }

        return $this->_restClient;
    }

    /**
     * Create a signed signature of the parameters.
     *
     * Return a parameter string that can be tacked onto the end of a URL.
     * Items will be sorted and an api_sig element will be on the end.
     *
     * @param   array   $params
     * @param 	string 	MD5 string
     */
    private function signParams( $params )
    {
    	ksort($params);
        $signing = '';
        foreach($params as $key => $value) {
            $signing .= $key . $value;
        }
        return md5($this->apiSecret . $signing);
    }
}
