<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

// Zend framework:
require_once dirname(__FILE__) . '/WW/Service/Flickr.php';

class Flickr
{
	private $flickr=null;
	
	public $apiKey = null;
	
	public $apiSecret = null;
	
	public $token  = null;
	
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
	 * getQueryColumns
	 *
	 * Columns as returned by search
	 */
	static final public function getColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 			'ID', 			'string' 	); // Required as 1st
		$cols[] = new Property( 'Type', 		'Type', 		'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name', 		'Name', 		'string' 	); // 
		$cols[] = new Property( 'OwnerName', 	'OwnerName', 	'string' 	); // 
		$cols[] = new Property( 'Created',  	'Created', 		'datetime'	);
        
        if( self::calledByContentStation() ) {
			$cols[] = new Property( 'Format', 		'Format', 		'string' 	);	// Required by Content Station
    	    $cols[] = new Property( 'PublicationId','PublicationId','string' 	);	// Required by Content Station
	        $cols[] = new Property( 'thumbUrl',		'thumbUrl',		'string' 	);	// Thumb URL for Content Station
	    }

		return $cols;
	}

	/**
	 * search
	 *
	 * Execute Flickr search
	 * 
	 * @param string	$searchBy		Search by category
	 * @param string	$searchPhrase	Phrase to search on
	 */
    public function search( $searchBy, $searchPhrase, &$firstEntry, &$totalEntries )
    {
    	$rows = array();
    	
    	if( empty($searchPhrase) ) {  // Flickr does not accept empty tag, so return empty array in that case
      		if( $searchBy == FLICKRSEARCH_SEARCH_BY_USER && defined( 'FLICKRSEARCH_USR_ACC') ) {
        		$searchPhrase = FLICKRSEARCH_USR_ACC;	// By default, it will be the user own account if nothing specify
        	}
        	else {
        		return $rows;
        	}
        }

        // User options
        // We're not asking for URLs, because that costs another .2 sec. It's cheaper to build to URL ourselves
        $options = array('per_page'	=> FLICKRSEARCH_ITEMS_PER_PAGE,
        				 'page'		=> round(($firstEntry+1)/FLICKRSEARCH_ITEMS_PER_PAGE)+1, // first page is 1
        				 'extras' 	=> 'date_taken,owner_name' );

		PerformanceProfiler::startProfile( 'Flickr - Search', 3 );
        // Perform search in Flickr
        if( $searchBy == FLICKRSEARCH_SEARCH_BY_TAG ) {
        	$results = $this->flickr->tagSearch( $searchPhrase, $options );
        }
        else {
        	$results = $this->flickr->userSearch( $searchPhrase, $options );
        }
		PerformanceProfiler::stopProfile( 'Flickr - Search', 3 );
        
        $firstEntry 	= $results->firstResultPosition;
        $totalEntries 	= $results->totalResultsAvailable;

		// To reduce the number of calls for GetDialog and getting preview, we also put the following
		// properties in the ID: farm, server, secret
        foreach ($results as $result) {
        	$row   = array();
        	$date  = self::convertDate($result->datetaken);	// The date the photo taken
        	$row[] = FS_CONTENTSOURCEPREFIX.urlencode($result->id.','.$result->farm.','.$result->server.','.$result->secret.','.$result->title.','.$date);		// ID: _FS_+<FSid> 
        	$row[] = 'Image';
			$row[] = $result->title;							// Title
			$row[] = $result->ownername;
			$row[] = $date;
	        if( self::calledByContentStation() ) {
				$row[] = 'image/jpeg';
				$row[] = 1;//$pubId;// ID
				//  Constructing URLs is documented: http://www.flickr.com/services/api/misc.urls.html
				$row[] = 'http://farm'.$result->farm.'.static.flickr.com/'.$result->server.'/'.$result->id.'_'.$result->secret.'_t.jpg';
			}

			$rows[] = $row;
        }
  		return $rows;
    }

    /**
	 * getImage
	 *
	 * Get Image file stream
	 * 
	 * @param string	$id				Image id
	 * @param string	$rendition		The request rendition
	 * @param string	$content		Content of the image stream
	 * @param string	$returnURL		Return URL to rendition
	 */
    public function getImage( $id, $imageProps, $rendition, &$content, $returnURL )
    {
		$url = null;
    	$img = null;
    	$info = null;

    	$farm 	= $imageProps[1];
    	$server = $imageProps[2];
    	$secret = $imageProps[3];
    	$name 	= $imageProps[4];
    	$date 	= $imageProps[5];

    	// When preview or thumb is requested we don't ask any metadata
    	// It's too expensive, we can create the URL ourselves
    	// For native we don't know the format, so we'll have to ask image details first
    	if( $rendition != 'preview' && $rendition != 'thumb' ) {
			$types['thumb'] 	= 'Thumbnail';
			$types['preview'] 	= 'Medium';
			$types['native'] 	= 'Original';
		
			PerformanceProfiler::startProfile( 'Flickr - getImageDetails', 3 );
			$image = $this->flickr->getImageDetails( $id );
			PerformanceProfiler::stopProfile( 'Flickr - getImageDetails', 3 );
			
			if( empty($image['Original']->uri) ) {
				if( empty($image['Large']->uri) ) {
					$types['native'] = 'Medium';
				} else {
					$types['native'] = 'Large';
				}
			}
			$img = $image[$types['native']];
	
			if( $rendition != 'none' || $returnURL ) {
				$url = $image[$types[$rendition]]->uri;
			}
			
			// Get meta data:
			PerformanceProfiler::startProfile( 'Flickr - getImageInfo', 3 );
			$info =  $this->flickr->getImageInfo( $id );
			PerformanceProfiler::stopProfile( 'Flickr - getImageInfo', 3 );
		} else {
			// create thumb/preview URL
			$url = "http://farm{$farm}.static.flickr.com/{$server}/{$id}_{$secret}";
			if( $rendition == 'thumb' ) {
				$url .= '_t.jpg';
			} else {
				$url .= '.jpg';
			}
		}
			
		if( $url ) {
			if( $returnURL ) {
				$content = $url;
			} else {
				$content = self::httpRequest( $url, null, 'image/jpeg' );
			}
		}

		return $this->getMetaData( $id, $info, $img, $name, $date );
    }

	public function __construct()
	{
		if( defined( 'FLICKRSEARCH_API_KEY' ) ) {
    		$this->apiKey 		= FLICKRSEARCH_API_KEY;
    	}
        if( defined( 'FLICKRSEARCH_API_SECRET' ) ) {
            $this->apiSecret 	= FLICKRSEARCH_API_SECRET;
        }
        if( defined( 'FLICKRSEARCH_TOKEN' ) ) {
        	$this->token 		= FLICKRSEARCH_TOKEN;
        }
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
	static final private function getMetaData( $id, $info, $img, $name=null, $date=null )
    {
    	require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
    	$metadata = array();

		$metadata['Id']			= (string) $id;
		$metadata['Type']		= 'Image';
		if( $info ) {
			$metadata['Name']		= $info->title;
			$metadata['Caption']	= $info->description;
    		$metadata['Created']	= self::convertDate( $info->datetaken );
    	} else {
			$metadata['Name']		= $name;
    		$metadata['Created']	= $date;
    	}
    	
    	if( $img ) {
	   		$metadata['Format']		= MimeTypeHandler::filePath2MimeType( $img->uri );
   			$metadata['Width']		= $img->width;
   			$metadata['Height']		= $img->height;
   		} else {
	   		$metadata['Format']		= 'image/jpeg';
		}
		$sDangerousCharacters 	= "`~!@#$%^*\\|;:'<>/?\"";	// remove dangerous characters to prevent problems when creating object
		$metadata['Name'] 		= ereg_replace("[$sDangerousCharacters]", "", $metadata['Name']);
		$metadata['Name'] 		= substr( $metadata['Name'],0,63); 
 
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
	 * calledByContentStation
	 *
	 * Returns true if the client is Content Station
	 */
    static final public function calledByContentStation( )
    {
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		
		$app = DBTicket::DBappticket( BizSession::getTicket() );
		
		return stristr($app, 'content station');
    }

    /**
	 * @throws BizException on error
	 */
	static final private function httpRequest( $url, $params, $expContentType )
	{
		if( $params ) {
			$urlParams = http_build_query( $params );
		}
		//LogHandler::Log('FlickrSearch', 'DEBUG', "Flickr request: $url");

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
			
			PerformanceProfiler::startProfile( "Flickr - $url", 3 );
			$response = $http->request( Zend_Http_Client::POST );
			PerformanceProfiler::stopProfile( "Flickr - $url", 3 );
			if( $response->isSuccessful() ) {
				$gotContentType = self::getContentType( $response );
				if( $gotContentType == $expContentType ||
					($gotContentType == '' && $expContentType == 'image/jpeg') ) {
					$retVal = $response->getBody();
				} else { // error on unhandled content
					if( $gotContentType == 'text/html' && $expContentType == 'image/jpeg' ) {
						$respBody = $response->getBody();
						$msg = self::getErrorFromHtmlPage( $respBody );
						$msg = 'FlickrSearch error: '.$msg;
				   	    LogHandler::Log('FlickrSearch', 'ERROR',  $respBody ); // dump entire HTML page
						throw new BizException( '', 'Server', $msg, $msg );
					} else {
						$msg = "Unexpected content type. Received: $gotContentType. Expected: $expContentType.";
						LogHandler::Log('FlickrSearch', 'ERROR', $msg .'. First 100 bytes: '. substr( $response->getBody(), 0, 100) );
						throw new BizException( '', 'Server', $msg, $msg );
					}
				}
			} else {
				self::handleHttpError( $response );
			}
		} catch (Zend_Http_Client_Exception $e) {
			throw new BizException( '', 'Server', 'FlickrSearch::httpRequest failed.', 'FlickrSearch error: '.$e->getMessage() );
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
			$msg = 'FlickrSearch error: '.$msg;
	   	    LogHandler::Log('FlickrSearch', 'ERROR',  $respBody ); // dump entire HTML page
			throw new BizException( '', 'Server', $msg, $msg );
    	}
		$msg = "Flickr connection problem: $respStatusText (HTTP code: $respStatusCode)";
		LogHandler::Log('FlickrSearch', 'ERROR',  $respBody ); // dump entire HTML page
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
    	//LogHandler::Log( 'FlickrSearch', 'DEBUG', 'getAuthUrl' );
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
