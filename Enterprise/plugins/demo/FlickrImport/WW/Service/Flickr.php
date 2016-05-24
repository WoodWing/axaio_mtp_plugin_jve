<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Flickr
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Flickr.php 21948 2010-04-19 12:12:32Z alexander $
 */


/**
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Flickr
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class WW_Service_Flickr
{
    /**
     * Base URI for the REST client
     */
    const URI_BASE = 'http://www.flickr.com';

    /**
     * Your Flickr API key
     *
     * @var string
     */
    public $apiKey;

    /**
     * Your Flickr API Secret
     *
     * @var string
     */
    public $apiSecret;

    /**
     * Your Flickr Access Token
     *
     * @var string
     */
 	public $token;

    /**
     * Reference to REST client object
     *
     * @var Zend_Rest_Client
     */
    protected $_restClient = null;


    /**
     * Performs object initializations
     *
     *  # Sets up character encoding
     *  # Saves the API key
     *
     * @param  string $apiKey Your Flickr API key
     * @param  string $secret Your Flickr API secret
     * @param  string $token  Your Flickr API token
     * @return void
     */
    public function __construct($apiKey, $secret, $token)
    {
        iconv_set_encoding('output_encoding', 'UTF-8');
        iconv_set_encoding('input_encoding', 'UTF-8');
        iconv_set_encoding('internal_encoding', 'UTF-8');

        $this->apiKey = (string) $apiKey;
        $this->apiSecret = (string) $secret;
        if($token) {
        	$this->token  = (string) $token;
        }
    }


    /**
     * Find Flickr photos by tag.
     *
     * Query options include:
     *
     *  # per_page:        how many results to return per query
     *  # page:            the starting page offset.  first result will be (page - 1) * per_page + 1
     *  # tag_mode:        Either 'any' for an OR combination of tags,
     *                     or 'all' for an AND combination. Default is 'any'.
     *  # min_upload_date: Minimum upload date to search on.  Date should be a unix timestamp.
     *  # max_upload_date: Maximum upload date to search on.  Date should be a unix timestamp.
     *  # min_taken_date:  Minimum upload date to search on.  Date should be a MySQL datetime.
     *  # max_taken_date:  Maximum upload date to search on.  Date should be a MySQL datetime.
     *
     * @param  string|array $query   A single tag or an array of tags.
     * @param  array        $options Additional parameters to refine your query.
     * @return WW_Service_Flickr_ResultSet
     * @throws BizException
     */
    public function tagSearch($query, array $options = array())
    {
        static $method = 'flickr.photos.search';
        static $defaultOptions = array('per_page' => 10,
                                       'page'     => 1,
                                       'tag_mode' => 'or',
                                       'extras'   => 'license, date_upload, date_taken, owner_name, icon_server');

        $options['tags'] = is_array($query) ? implode(',', $query) : $query;

        $options = $this->_prepareOptions($method, $options, $defaultOptions);

        $this->_validateTagSearch($options);

        if( $this->token ) {
        	$options['auth_token'] 	= $this->token;
        	$options['api_sig'] 	= $this->signParams($options);
        }

        // now search for photos
        $restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        if ($response->isError()) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'An error occurred sending request. Status code: '
                                           . $response->getStatus() );
        }

        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());

        self::_checkErrors($dom);

        /**
         * @see WW_Service_Flickr_ResultSet
         */
        require_once dirname(__FILE__) . '/Flickr/ResultSet.php';
        return new WW_Service_Flickr_ResultSet($dom, $this);
    }


    /**
     * Finds photos by a user's username or email, including private photos.
     *
     * Additional query options include:
     *
     *  # per_page:        how many results to return per query
     *  # page:            the starting page offset.  first result will be (page - 1) * per_page + 1
     *  # min_upload_date: Minimum upload date to search on.  Date should be a unix timestamp.
     *  # max_upload_date: Maximum upload date to search on.  Date should be a unix timestamp.
     *  # min_taken_date:  Minimum upload date to search on.  Date should be a MySQL datetime.
     *  # max_taken_date:  Maximum upload date to search on.  Date should be a MySQL datetime.
     *
     * @param  string $query   username or email
     * @param  array  $options Additional parameters to refine your query.
     * @return WW_Service_Flickr_ResultSet
     * @throws BizException
     */
    public function userSearch($query, array $options = null)
    {
        static $method = 'flickr.photos.search';
        static $defaultOptions = array('per_page' => 10,
                                       'page'     => 1,
                                       'extras'   => 'license, date_upload, date_taken, owner_name, icon_server');


        // can't access by username, must get ID first
        if (strchr($query, '@')) {
            // optimistically hope this is an email
            $options['user_id'] = $this->getIdByEmail($query);
        } else {
            // we can safely ignore this exception here
            $options['user_id'] = $this->getIdByUsername($query);
        }

        $options = $this->_prepareOptions($method, $options, $defaultOptions);
        $this->_validateTagSearch($options);

        if( $this->token ) {
        	$options['auth_token'] 	= $this->token;
        	$options['api_sig'] 	= $this->signParams($options);
        }

        // now search for photos
        $restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        if ($response->isError()) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'An error occurred sending request. Status code: '
                                           . $response->getStatus() );
        }

        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());

        self::_checkErrors($dom);

        /**
         * @see WW_Service_Flickr_ResultSet
         */
        require_once dirname(__FILE__) . '/Flickr/ResultSet.php';
        return new WW_Service_Flickr_ResultSet($dom, $this);
    }

    /**
     * Finds photos in a group's pool.
     *
     * @param  string $query   group id
     * @param  array  $options Additional parameters to refine your query.
     * @return WW_Service_Flickr_ResultSet
     * @throws BizException
     */
    public function groupPoolGetPhotos($query, array $options = array())
    {
        static $method = 'flickr.groups.pools.getPhotos';
        static $defaultOptions = array('per_page' => 10,
                                       'page'     => 1,
                                       'extras'   => 'license, date_upload, date_taken, owner_name, icon_server');

        if (empty($query) || !is_string($query)) {
             /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'You must supply a group id' );
        }

        $options['group_id'] = $query;

        $options = $this->_prepareOptions($method, $options, $defaultOptions);

        $this->_validateGroupPoolGetPhotos($options);

        // now search for photos
        $restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        if ($response->isError()) {
           	/**
            * @see BizException
            */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'An error occurred sending request. Status code: '
                                           . $response->getStatus() );
        }

        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());

        self::_checkErrors($dom);

       	/**
        * @see WW_Service_Flickr_ResultSet
        */
        require_once dirname(__FILE__) . '/Flickr/ResultSet.php';
        return new WW_Service_Flickr_ResultSet($dom, $this);
    }



    /**
     * Utility function to find Flickr User IDs for usernames.
     *
     * (You can only find a user's photo with their NSID.)
     *
     * @param  string $username the username
     * @return string the NSID (userid)
     * @throws BizException
     */
    public function getIdByUsername($username)
    {
        static $method = 'flickr.people.findByUsername';

        $options = array('api_key' => $this->apiKey, 'method' => $method, 'username' => (string) $username);

        if (empty($username)) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'You must supply a username' );
        }

        $restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        if ($response->isError()) {
             /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'An error occurred sending request. Status code: '
                                           . $response->getStatus() );
        }

        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
        return (string) $xpath->query('//user')->item(0)->getAttribute('id');
    }


    /**
     * Utility function to find Flickr User IDs for emails.
     *
     * (You can only find a user's photo with their NSID.)
     *
     * @param  string $email the email
     * @return string the NSID (userid)
     * @throws BizException
     */
    public function getIdByEmail($email)
    {
        static $method = 'flickr.people.findByEmail';

        if (empty($email)) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'You must supply an e-mail address' );
        }

        $options = array('api_key' => $this->apiKey, 'method' => $method, 'find_email' => (string) $email);

        $restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        if ($response->isError()) {
             /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'YAn error occurred sending request. Status code: '
                                           . $response->getStatus() );
        }

        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        self::_checkErrors($dom);
        $xpath = new DOMXPath($dom);
        return (string) $xpath->query('//user')->item(0)->getAttribute('id');
    }


    /**
     * Returns Flickr photo details by for the given photo ID
     *
     * @param  string $id the NSID
     * @return array of Zend_Service_Flickr_Image, details for the specified image
     * @throws BizException
     */
    public function getImageDetails($id)
    {
        static $method = 'flickr.photos.getSizes';

        if (empty($id)) {
             /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'You must supply a photo ID' );
        }

        $options = array('api_key' => $this->apiKey, 'method' => $method, 'photo_id' => $id);

        if($this->token) {
        	$options['auth_token'] = $this->token;
        	$options['api_sig'] = $this->signParams($options);
        }

        $restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        $xpath = new DOMXPath($dom);
        self::_checkErrors($dom);
        $retval = array();
        /**
         * @see Zend_Service_Flickr_Image
         */
        require_once 'Zend/Service/Flickr/Image.php';
        foreach ($xpath->query('//size') as $size) {
            $label = (string) $size->getAttribute('label');
            $retval[$label] = new Zend_Service_Flickr_Image($size);
        }

        return $retval;
    }

    /**
     * Returns Flickr photo informations by for the given photo ID
     * By WoodWing
     *
     * @param  string $id the NSID
     * @return array of Zend_Service_Flickr_Image, details for the specified image
     * @throws BizException
     */
    public function getImageInfo($id)
    {
        static $method = 'flickr.photos.getInfo';

        if (empty($id)) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'You must supply a photo ID' );
        }

        $options = array('api_key' => $this->apiKey, 'method' => $method, 'photo_id' => $id);
        if( $this->token ) {
        	$options['auth_token'] 	= $this->token;
        	$options['api_sig'] 	= $this->signParams($options);
        }

        $restClient = $this->getRestClient();
        $restClient->getHttpClient()->resetParameters();
        $response = $restClient->restGet('/services/rest/', $options);

        $dom = new DOMDocument();
        $dom->loadXML($response->getBody());
        $xpath = new DOMXPath($dom);
        self::_checkErrors($dom);
        $retval = array();
        /**
         * @see WW_Service_Flickr_Image
         */
        require_once dirname(__FILE__) . '/Flickr/ImageInfo.php';
        $retval = new WW_Service_Flickr_ImageInfo($xpath);

        return $retval;
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
     * Validate User Search Options
     *
     * @param  array $options
     * @return void
     * @throws BizException
     */
    protected function _validateUserSearch(array $options)
    {
        $validOptions = array('api_key', 'method', 'user_id', 'per_page', 'page', 'extras', 'min_upload_date',
                              'min_taken_date', 'max_upload_date', 'max_taken_date', 'safe_search');

        $this->_compareOptions($options, $validOptions);

        /**
         * @see Zend_Validate_Between
         */
        require_once 'Zend/Validate/Between.php';
        $between = new Zend_Validate_Between(1, 500, true);
        if (!$between->isValid($options['per_page'])) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', $options['per_page'] . ' is not valid for the "per_page" option' );
        }

        /**
         * @see Zend_Validate_Int
         */
        require_once 'Zend/Validate/Int.php';
        $int = new Zend_Validate_Int();
        if (!$int->isValid($options['page'])) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', $options['page'] . ' is not valid for the "page" option' );
        }

        // validate extras, which are delivered in csv format
        if ($options['extras']) {
            $extras = explode(',', $options['extras']);
            $validExtras = array('license', 'date_upload', 'date_taken', 'owner_name', 'icon_server');
            foreach($extras as $extra) {
                /**
                 * @todo The following does not do anything [yet], so it is commented out.
                 */
                //in_array(trim($extra), $validExtras);
            }
        }
    }


    /**
     * Validate Tag Search Options
     *
     * @param  array $options
     * @return void
     * @throws BizException
     */
    protected function _validateTagSearch(array $options)
    {
        $validOptions = array('method', 'api_key', 'user_id', 'tags', 'tag_mode', 'text', 'min_upload_date',
                              'max_upload_date', 'min_taken_date', 'max_taken_date', 'license', 'sort',
                              'privacy_filter', 'bbox', 'accuracy', 'safe_search', 'content_type', 'machine_tags',
                              'machine_tag_mode', 'group_id', 'contacts', 'woe_id', 'place_id', 'media', 'has_geo',
                              'geo_context', 'lat', 'lon', 'radius', 'radius_units', 'is_commons', 'is_gallery',
                              'extras', 'per_page', 'page', 'auth_token', 'api_sig');

        $this->_compareOptions($options, $validOptions);

        /**
         * @see Zend_Validate_Between
         */
        require_once 'Zend/Validate/Between.php';
        $between = new Zend_Validate_Between(1, 500, true);
        if (!$between->isValid($options['per_page'])) {
            /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', $options['per_page'] . ' is not valid for the "per_page" option' );
        }

        /**
         * @see Zend_Validate_Int
         */
        require_once 'Zend/Validate/Int.php';
        $int = new Zend_Validate_Int();
        if (!$int->isValid($options['page'])) {
             /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', $options['page'] . ' is not valid for the "page" option' );
        }

        // validate extras, which are delivered in csv format
        if ($options['extras']) {
            $extras = explode(',', $options['extras']);
            $validExtras = array('license', 'date_upload', 'date_taken', 'owner_name', 'icon_server');
            foreach($extras as $extra) {
                /**
                 * @todo The following does not do anything [yet], so it is commented out.
                 */
                //in_array(trim($extra), $validExtras);
            }
        }

    }


    /**
    * Validate Group Search Options
    *
    * @param  array $options
    * @throws BizException
    * @return void
    */
    protected function _validateGroupPoolGetPhotos(array $options)
    {
        $validOptions = array('api_key', 'tags', 'method', 'group_id', 'per_page', 'page', 'extras', 'user_id');

        $this->_compareOptions($options, $validOptions);

        /**
        * @see Zend_Validate_Between
        */
        require_once 'Zend/Validate/Between.php';
        $between = new Zend_Validate_Between(1, 500, true);
        if (!$between->isValid($options['per_page'])) {
            /**
            * @see BizException
            */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', $options['per_page'] . ' is not valid for the "per_page" option' );
        }

        /**
        * @see Zend_Validate_Int
        */
        require_once 'Zend/Validate/Int.php';
        $int = new Zend_Validate_Int();

        if (!$int->isValid($options['page'])) {
            /**
            * @see BizException
            */
			throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', $options['page'] . ' is not valid for the "page" option' );
        }

        // validate extras, which are delivered in csv format
        if (isset($options['extras'])) {
            $extras = explode(',', $options['extras']);
            $validExtras = array('license', 'date_upload', 'date_taken', 'owner_name', 'icon_server');
            foreach($extras as $extra) {
                /**
                * @todo The following does not do anything [yet], so it is commented out.
                */
                //in_array(trim($extra), $validExtras);
            }
        }
    }


    /**
     * Throws an exception if and only if the response status indicates a failure
     *
     * @param  DOMDocument $dom
     * @return void
     * @throws BizException
     */
    protected static function _checkErrors(DOMDocument $dom)
    {
        if ($dom->documentElement->getAttribute('stat') === 'fail') {
            $xpath = new DOMXPath($dom);
            $err = $xpath->query('//err')->item(0);
            /**
             * @see BizException
             */
            throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'FlickrSearch::httpRequest failed.', 'Search failed due to error: ' . $err->getAttribute('msg')
                                           . ' (error #' . $err->getAttribute('code') . ')' );
        }
    }


    /**
     * Prepare options for the request
     *
     * @param  string $method         Flickr Method to call
     * @param  array  $options        User Options
     * @param  array  $defaultOptions Default Options
     * @return array Merged array of user and default/required options
     */
    protected function _prepareOptions($method, array $options, array $defaultOptions)
    {
        $options['method']  = (string) $method;
        $options['api_key'] = $this->apiKey;

        return array_merge($defaultOptions, $options);
    }


    /**
     * Throws an exception if and only if any user options are invalid
     *
     * @param  array $options      User options
     * @param  array $validOptions Valid options
     * @return void
     * @throws BizException
     */
    protected function _compareOptions(array $options, array $validOptions)
    {
        $difference = array_diff(array_keys($options), $validOptions);
        if ($difference) {
             /**
             * @see BizException
             */
        	throw new BizException( 'FLICKR_SEARCH_ERROR', 'Server', 'The following parameters are invalid: ' . implode(',', $difference) );
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
    protected function signParams( $params )
    {
    	ksort($params);
        $signing = '';
        foreach($params as $key => $value) {
            $signing .= $key . $value;
        }
        return md5($this->apiSecret . $signing);
    }
}
