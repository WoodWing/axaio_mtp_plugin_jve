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

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

class FlickrPublish
{
	public $apiKey = null;
	
	public $apiSecret = null;
	
	public $token  = null;
	
	/**
     * Base URI for the REST client
     */
    const URI_BASE = 'http://www.flickr.com';
    /**
     * The URL that photo uploads should be POSTed to.
     *
     * @var string
     */
    const UPLOAD_URL = 'http://api.flickr.com/services/upload/';
    /**
     * The URL that photo replace should be POSTed to.
     *
     * @var string
     */
    const REPLACE_URL = 'http://api.flickr.com/services/replace/';
	/**
     * Reference to REST client object
     *
     * @var Zend_Rest_Client
     */
    protected $_restClient = null;
	
    /**
     * Should photos be visible to everyone?
     *
     * @var boolean
     */
    protected $_forPublic = FLICKRPUBLISH_PUBLIC;
    /**
     * Should photos be visible to friend?
     *
     * @var boolean
     */
    protected $_forFriends = FLICKRPUBLISH_FRIEND;
    /**
     * Should photos be visible to family?
     *
     * @var boolean
     */
    protected $_forFamily = FLICKRPUBLISH_FAMILY;
    /**
     * Array of tags that will be added to the photo.
     *
     * @var array
     */
    protected $_tags = array();
    /**
     * Number of seconds to wait for an upload to complete.
     * 
     * @var integer
     */
    const TIMEOUT_TOTAL = 200;
    const TIMEOUT_CONNECTION = 20;

    /**
     * Constructor.
     *
     */
    public function __construct() {
    	if( defined( 'FLICKRPUBLISH_API_KEY' ) ) {
    		$this->apiKey 	= FLICKRPUBLISH_API_KEY;
    	}
        if( defined( 'FLICKRPUBLISH_API_SECRET' ) ) {
            $this->apiSecret 	= FLICKRPUBLISH_API_SECRET;
        }
        if( defined( 'FLICKRPUBLISH_TOKEN' ) ) {
        	$this->token 	= FLICKRPUBLISH_TOKEN;
        }
    }

    /**
     * Upload a photo to Flickr.
     *
     *
     * @param   string $path  Full path and file name of the photo.
     * @param   string $title Photo title.
     * @param   string $desc Photo description.
     * @param   string|array $tags A space separated list of tags to add to the photo.
     *          These will be added to those listed in getTags().
     * @return  string id of the new photo
     */
    public function uploadImage($path, $title = '', $desc = '', $tags = '')
    {
        // concat the class's tags with this photos.
        if (is_array($tags)) {
            $tags = '"' . implode('" "', $this->_tags + $tags) . '"';
        } elseif ($tags) {
            $tags = '"' . implode('" "', $this->_tags) . '" ' . (string) $tags;
        } else {
            $tags = '';
        }

        $options = array('api_key'		=> $this->apiKey,
        				'auth_token' 	=> $this->token,
         				'title' 		=> $title,
                		'description' 	=> $desc,
                		'tags' 			=> $tags,
                		'is_public' 	=> (integer) $this->_forPublic,
                		'is_friend' 	=> (integer) $this->_forFriends,
                		'is_family' 	=> (integer) $this->_forFamily
        );
        $options['api_sig'] = $this->signParams($options);

        // contents
        $options['photo'] = '@' . $path;
        $url = 'http://www.flickr.com/services/upload/';
        $response = self::submitHttpPost(self::UPLOAD_URL, $options, 200);
        
        $dom = new DOMDocument();
        $dom->loadXML($response);
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
		$photoid = $xpath->query('//photoid/text()')->item(0);
		return (string) $photoid->data;
    }
    
    /**
     * Perform deletion based on Flickr photo id
     *
     * @param 	string 	$id Flickr photo id
     * @return  boolean	Throw exception if false else return true
     */
    public function deleteImage( $id )
    {
    	static $method = 'flickr.photos.delete';
    	$options = array('api_key' => $this->apiKey, 'method' => $method, 'photo_id' => $id, 'auth_token' => $this->token);
		$options['api_sig'] = $this->signParams($options);

    	$restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

         if ($response->isError()) {
         	$msg = 'An error occurred sending request. Status code: ' . $response->getStatus();
         	throw new BizException( '', 'Server', $msg, $msg );
        }
        return true;
    }
    
    /**
     * Set image meta of title and description
     *
     *
     * @param 	string 	$id				Flickr photo id
     * @param  	string	$title			Photo title
     * @param 	string	$description	Photo description
     * @return 	boolean	Throw exception if false else return true
     */
    public function setMeta( $id, $title, $description )
    {
    	static $method = 'flickr.photos.setMeta';
    	$options = array('api_key' => $this->apiKey, 'method' => $method, 'photo_id' => $id, 'title' => $title, 'description' => $description, 'auth_token' => $this->token);
		$options['api_sig'] = $this->signParams($options);

    	$restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

         if ($response->isError()) {
            $msg = 'An error occurred sending request. Status code: ' . $response->getStatus();
         	throw new BizException( '', 'Server', $msg, $msg );
        }
        return true;
    }

    /**
     * Replace a new version of photo to Flickr
     *
     * @param  	string	$id 	Flickr photo id
     * @param 	string	$path	Enterprise image path
     * @return  string	replaced photo id
     */
    public function replaceImage( $id, $path )
    {
        $options = array('api_key' => $this->apiKey, 'auth_token' => $this->token, 'photo_id' => $id);
        $options['api_sig'] = $this->signParams($options);
        // contents
        $options['photo'] = '@' . $path;
        $response = self::submitHttpPost(self::REPLACE_URL, $options, 200);
        
        $dom = new DOMDocument();
        $dom->loadXML($response);
        if($dom->documentElement->getAttribute('stat') === 'fail') {
            $xpath = new DOMXPath($dom);
            $err = $xpath->query('//err')->item(0);
            if($err->getAttribute('code') == '1') {
            	$msg = 'Can not update the image. You need a Pro account of Flickr.';
            	throw new BizException( '', 'Server', $msg, $msg );
            }
        }
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
		$photoid = $xpath->query('//photoid/text()')->item(0);
		return (string) $photoid->data;
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
     * Check token whether it is valid or not
     *
     * @return  string token value
     */
    public function checkToken()
    {
    	static $method = 'flickr.auth.checkToken';
    	
    	$options = array('api_key' => $this->apiKey, 'method' => $method, 'auth_token' => $this->token);
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
    	LogHandler::Log( 'FlickrPublish', 'DEBUG', 'getAuthUrl' );
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
     * Publish Image to Flickr
     *
     * @param string $imgPath Image full path.
     * @param string $imgName Image name.
     * @param string $imgDesc Image description
     * @param array	 $imgTags Images tags
     * @return string Photo Id from Flickr
     */
    public function publishImage( $imgPath, $imgName, $imgDesc, $imgTags )
    {
    	LogHandler::Log( 'FlickrPublish', 'DEBUG', 'Publish Images' );
    	$photoid = '';
 		$token = $this->checkToken();
 		if( $token == FLICKRPUBLISH_TOKEN ) {
  			$photoid = $this->uploadImage($imgPath, $imgName, $imgDesc, $imgTags );
 		}
 		return $photoid;
    }

    /**
     * Update image to Flickr
     *
     * @param string $imgId Image Id
     * @param string $imgPath Image path
     * @param string $imgtitle Image title
     * @param array	 $imgDesc Images description
     * @return string Photo Id from Flickr
     */
    public function updateImage( $imgId, $imgPath, $imgtitle, $imgDesc )
    {
    	LogHandler::Log( 'FlickrPublish', 'DEBUG', 'Update Images' );
 		$token = $this->checkToken();
 		if( $token == FLICKRPUBLISH_TOKEN ) {
 			$newImgId = $this->replaceImage($imgId, $imgPath);
 			if( $imgId != $newImgId ) {
 				$msg = "FlickrPublish::updateImage Failed.";
 				throw new BizException( '', 'Server', $msg, $msg );
 			}
  			$this->setMeta( $imgId, $imgtitle, $imgDesc );
 		}
    }
    
    /**
     * Unpublish photo from Flickr
     *
     * @return string Image Id
     */
    public function unpublishImage( $imgId )
    {	
    	LogHandler::Log( 'FlickrPublish', 'DEBUG', 'Unpublish Image' );
 		$token = $this->checkToken();
 		if( $token == FLICKRPUBLISH_TOKEN ) {
 			LogHandler::Log( 'Flickr', 'DEBUG', 'ImageID: ' . $imgId );
  			return $this->deleteImage($imgId );
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
	
	/**
     * Submit a POST request with to the specified URL with given parameters.
     *
     * @param   string $url
     * @param   array $params An optional array of parameter name/value
     *          pairs to include in the POST.
     * @param   integer $timeout The total number of seconds, including the
     *          wait for the initial connection, wait for a request to complete.
     * @return  string
     * @throws  Phlickr_ConnectionException
     * @uses    TIMEOUT_CONNECTION to determine how long to wait for a
     *          for a connection.
     * @uses    TIMEOUT_TOTAL to determine how long to wait for a request
     *          to complete.
     * @uses    set_time_limit() to ensure that PHP's script timer is five
     *          seconds longer than the sum of $timeout and TIMEOUT_CONNECTION.
     */
    static function submitHttpPost($url, $postParams = null, $timeout = TIMEOUT_TOTAL)
    {
        $ch = curl_init();

        // set up the request
        curl_setopt($ch, CURLOPT_URL, $url);
        // make sure we submit this as a post
        curl_setopt($ch, CURLOPT_POST, true);
        if (isset($postParams)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, "");        	
        }
        // make sure problems are caught
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        // return the output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // set the timeouts
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT_CONNECTION);
        curl_setopt($ch, CURLOPT_TIMEOUT,$timeout);
        // set the PHP script's timeout to be greater than CURL's
        set_time_limit(self::TIMEOUT_CONNECTION + $timeout + 5);

        $result = curl_exec($ch);
        // check for errors
        if (0 == curl_errno($ch)) {
            curl_close($ch);
            return $result;
        } else {
        	$msg = 'Request failed due to error' .  curl_error($ch) . ' (error ' . curl_errno($ch) .')';
            curl_close($ch);
            throw new BizException( '', 'Server', $msg, $msg );
        }
    }
}
