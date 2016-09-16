<?php

require_once BASEDIR . '/server/utils/EnterpriseXmlRpcClient.class.php';
require_once dirname(__FILE__) . '/config.php';
require_once 'Zend/Http/Client.php';
require_once 'Zend/Http/Client/Adapter/Curl.php';
require_once dirname(__FILE__). '/ExternalUtils/class-IXR.php';

class WordPressXmlRpcClient
{
	private $userName;
	private $password;
	private $url;

	public function __construct( $publishTarget = null, $username = null, $password = null, $url = null )
	{
		if( $publishTarget ){
			require_once dirname(__FILE__) . '/WordPress_Utils.class.php';
			$wordpressUtils = new WordPress_Utils();

			$credentials = $wordpressUtils->getConnectionInfo( $publishTarget );
			$this->userName = $credentials['username'];
			$this->password = $credentials['password'];
			$this->url = $credentials['connectionUrl'];
		}else if( $username && $password && $url ){
			$this->userName = $username;
			$this->password = $password;
			$this->url = $url . '/xmlrpc.php';
		}
	}

	/**
	 * Sends a message to a XML-RPC server using the Zend_XmlRpc classes.
	 *
	 * @param string $url
	 * @param string $action
	 * @param array $params
	 *
	 * @return mixed - If answer is received the object will be returned otherwise null is returned.
	 *
	 * @throws BizException Throws a BizException in case of errors.
	 */
	public function rpcService( $url, $action, $params )
	{
		$zendCurlAdapter = new Zend_Http_Client_Adapter_Curl();
		$zendHttpClient = new Zend_Http_Client($url);
		$zendHttpClient->setAdapter( $zendCurlAdapter );
		$xmlRpcClient = new WW_Utils_XmlRpcClient($url);
		$xmlRpcClient->setXmlRpcClient($url, $zendHttpClient);
		try {
			$retVal = $xmlRpcClient->callRpcService( $action, $params, array( 'WordPress_Utils', 'obfuscatePasswordInRequest' ) );
		} catch( BizException $e ) {
			if( strpos( $e->getDetail(), 'permission' ) && strpos( $e->getDetail(), '(403)' ) ) { // given when the user has no the correct rights
				$errMsg = $e->getDetail();
				$messageKey = 'WORDPRESS_ERROR_INCORRECT_ROLE';
			} else if( strpos($e->getDetail(), '(403)') ) { // given if authentication failed with the current settings
				$errMsg = $e->getDetail();
				$messageKey = 'WORDPRESS_ERROR_INCORRECT_CONFIG';
			} else if( $action == 'ngg.newGallery' || $action == 'ngg.uploadImage' ) { // given when the plug-in is not enabled or if uploading fails
				$errMsg = $e->getDetail();
				$messageKey = 'WORDPRESS_ERROR_UPLOAD_IMAGES';
			} else { // given for all other scenario's
				$errMsg = $e->getDetail();
				$messageKey = 'WORDPRESS_ERROR_XMLRPC';
			}
			throw new BizException( $messageKey, 'Server', $errMsg );
		}
		return $retVal;
	}

	/**
	 * Set WordPress url.
	 *
	 * Set the connection url when you want it to be different
	 *
	 * @param $url
	 */
	function setConnectionUrl( $url )
	{
		$this->url = $url;
	}

	/**
	 * Set WordPress password.
	 *
	 * Set the connection password when you want it to be different
	 *
	 * @param $password
	 */
	function setConnectionPassword( $password )
	{
		$this->password = $password;
	}

	/**
	 * Set WordPress username.
	 *
	 * Set the connection username when you want it to be different
	 *
	 * @param $username
	 */
	function setConnectionUserName( $username )
	{
		$this->userName = $username;
	}

	/**
	 * Create WordPress post or Update WordPress post.
	 *
	 * This function is used to create a post on WordPress when posting shortcodes can be used to display images or slideshows.
	 * This function uses the XML-RPC API.
	 *
	 * @param array $postContent
	 * @param int $externalId
	 * @param boolean $preview
	 *
	 * @return mixed
	 */
	function uploadPost( $postContent, $externalId = null, $preview = false )
	{
		require_once 'Zend/Json.php';
		$userName = $this->userName;
		$password = $this->password;
		$url = $this->url;

		$allowComments = $postContent['allowComments'];
		$format = $postContent['format'];
		$visibility = null;

		if( $allowComments == 0 ){
			$allowComments = 'closed';
		}else if( $allowComments == 1 ){
			$allowComments = 'open';
		}

		if( isset( $postContent['visibility'] ) && strtolower( $postContent['visibility'] ) != 'public' ){ // if public no visibility is needed
			$visibility = strtolower($postContent['visibility']);
		}

		$content = array(
			'title' =>  $postContent['title'],
			'description' =>  $postContent['body'],
			'post_type' => 'post',
			'post_status' => $visibility,
			'wp_post_format' => $format,
			'mt_allow_comments' => $allowComments,
			'sticky' => $postContent['sticky'],
			'mt_keywords' => $postContent['tags']
		);

		if( isset( $postContent['publishDate'] ) && $postContent['publishDate']!= '' ) {
			$dateAndTimeGmt = gmdate( 'Y-m-d H:i:s', strtotime( $postContent['publishDate'] ));
			$content['date_created_gmt'] = new DateTime( $dateAndTimeGmt );
		}

		if( isset( $postContent['categories'] ) ) {
			$content['categories'] = $postContent['categories'];
		} else {
			$content['categories'] = array( 'Uncategorized' );
		}

		if( isset( $postContent['excerpt'] ) && $postContent['excerpt'] != '' ) {
			$content['mt_excerpt'] = $postContent['excerpt'];
		}

		if( isset($postContent['slug']) && $postContent['slug'] != '' ) {
			$content['wp_slug'] = $postContent['slug'];
		}

		if( $preview ) {
			if( isset( $content['wp_slug'] ) ) {
				$content['wp_slug'] = 'preview-'.$content['wp_slug'];
			} else {
				$content['wp_slug'] = 'preview-'.$content['title'];
			}
		}

		if( isset($postContent['authorId']) && $postContent['authorId'] != '' ) {
			$content['wp_author_id'] = $postContent['authorId'];
		}

		if( isset( $postContent['inlineImages'] ) ) {
			$inlineImagesCustomField = array(
				'value' => json_encode($postContent['inlineImages']['inline-ids'] ),
				'key' => 'Enterprise-inline-images'
			);
			if( isset( $postContent['inlineImages']['customFieldId'] ) ) {
				$inlineImagesCustomField['id'] = $postContent['inlineImages']['customFieldId'];
			}
			$content['custom_fields'][] = $inlineImagesCustomField;
		}

		if( isset( $postContent['gallProps'] ) ) {
			$galleriesCustomField = array(
				'value' => Zend_Json::encode( $postContent['gallProps']['galleries'] ),
				'key' => 'Enterprise-nextgen-galleries'
			);
			if( isset( $postContent['gallProps']['customFieldId'] ) ) {
				$galleriesCustomField['id'] = $postContent['gallProps']['customFieldId'];
			}
			$content['custom_fields'][] = $galleriesCustomField;
		}

		if( isset( $postContent['featuredImage'] ) ) {
			$content['wp_post_thumbnail'] = $postContent['featuredImage']['featured-image'];
			$featuredImagesCustomField = array(
                'value' => json_encode($postContent['featuredImage']['featured-image'] ),
                'key' => 'Enterprise-featured-images'
			);
			if( isset( $postContent['featuredImage']['customFieldId'] ) ) {
				$featuredImagesCustomField['id'] = $postContent['featuredImage']['customFieldId'];
			}
			$content['custom_fields'][] = $featuredImagesCustomField;
		}

		if( !$preview ) {
			if( !$externalId ) {
				$params = array( 0, $userName, $password, $content, true );
				return $this->rpcService( $url, 'metaWeblog.newPost', $params );
			} else {
				// WordPress API uses integers for the external id. Need to make sure this type is set correctly.
				$params = array( (int) $externalId, $userName, $password, $content, true );
				return $this->rpcService( $url, 'metaWeblog.editPost', $params );
			}
		} else {
			$content['post_status'] = 'draft';
			$params = array( 0, $userName, $password, $content, true );
			return $this->rpcService( $url, 'woodwing.GetPreviewUrl', $params );
		}
	}

	/**
	 * Delete all old published previews.
	 *
	 * Delete all the expired previews from WordPress
	 * This function uses the XML-RPC API.
	 *
	 * @return mixed
	 */
	function deleteOldPublishedPreviews()
	{
		$params = array( $this->userName, $this->password );
		return $this->rpcService( $this->url, 'woodwing.DeleteOldPreviews', $params );
	}

	/**
	 * get a post from WordPress.
	 *
	 * This function gets a post from WordPress
	 * This function uses the XML-RPC API.
	 *
	 * @param int $externalId
	 *
	 * @return mixed
	 */
	function getPost( $externalId )
	{
		// WordPress API uses integers for the external id. Need to make sure this type is set correctly.
		$params = array( (int) $externalId, $this->userName, $this->password );
		return $this->rpcService( $this->url, 'metaWeblog.getPost', $params );
	}

	/**
	 * Delete a post from WordPress.
	 *
	 * This function deletes a post from WordPress with all the content.(Inline images and galleries)
	 * This function uses the XML-RPC API.
	 *
	 * @param int $externalId
	 *
	 * @return mixed
	 */
	function deletePostAndContent( $externalId )
	{
		$params = array( $this->userName, $this->password, $externalId );
		return $this->rpcService( $this->url, 'woodwing.DeletePostAndContent', $params );
	}

	/**
	 * Check NextGen plugin.
	 *
	 * This function check if the NextGen plug-in is installed and activated on the wordpress site.
	 * This function uses the XML-RPC API.
	 *
	 * @return mixed
	 */
	function checkNextGenEnabled()
	{
		$params = array( $this->userName, $this->password );
		return $this->rpcService( $this->url, 'woodwing.CheckNextGenEnabled', $params );
	}

	/**
	 * Update WordPress media library image metadata.
	 *
	 * This function updates the metadata of a image that is in the WordPress media library.
	 * It uses the ww_enterprise plugin to do this.
	 *
	 * @param $externalId
	 * @param $name
	 * @param $description
	 * @param $alternativeText
	 * @return Bool|Exception
	 */
	function updateMediaLibraryImageMetaData( $externalId, $name, $description, $alternativeText )
	{
		$caption = ''; // We do not support caption yet.
		$params = array( $this->userName, $this->password, $externalId, $name, $description, $alternativeText, $caption );
		return $this->rpcService( $this->url, 'woodwing.UpdateImageMetaData', $params );
	}

	/**
	 * Delete WordPress inline and featured images for a post.
	 *
	 * This function deletes images that are in the WordPress media library.
	 * It uses the ww_enterprise plugin to do this.
	 *
	 * @param $externalId
	 * @return Bool|Exception
	 */
	function deleteInlineAndFeaturedImages( $externalId )
	{
		$params = array( $this->userName, $this->password, $externalId );
		return $this->rpcService( $this->url, 'woodwing.DeleteInlineAndFeaturedImages', $params );
	}

	/**
	 * Delete WordPress media library image.
	 *
	 * This function uploads a image to the WordPress media library.
	 * this function uses the XMP-RPC API.
	 *
	 * @param string $imageName
	 * @param string $filePath
	 * @param string $extension
	 *
	 * @throws BizException
	 *
	 * @return mixed
	 */
	function uploadMediaLibraryImage( $imageName, $filePath, $extension)
	{
		$imageFile = file_get_contents( $filePath );
		$data = array(
			'name'  => $imageName,
			'type'  => $extension,
			'bits'  => new IXR_Base64( $imageFile ), // The image convert to base 64 is needed for WordPress
			'overwrite' => false
		);

		$rpc = new IXR_Client( $this->url );
		$status = $rpc->query( 'metaWeblog.newMediaObject', 0, $this->userName,	$this->password, $data );
		if( !$status ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', $rpc->getErrorMessage() );
		}

		return $rpc->getResponse();
	}

	/**
	 * Update NextGen image metadata.
	 *
	 * This function updates the metadata of a image that is in a NextGen Gallery.
	 * It uses the NextGen plugin to do this.
	 *
	 * @param $externalId
	 * @param $name
	 * @param $description
	 * @return mixed
	 */
	function updateImageMetaData( $externalId, $name, $description )
	{
		$params = array( 0, $this->userName, $this->password, $externalId, $name, $description, 0 );
		return $this->rpcService( $this->url, 'ngg.editImage', $params );
	}

	/**
	 * Delete NextGen WordPress image.
	 *
	 * This function is used to delete an image from the gallery it is in.
	 * This function uses the XML-RPC API.
	 *
	 * @param $externalId
	 *
	 * @return mixed
	 */
	function deleteImage( $externalId )
	{
		$params = array( $this->userName, $this->password, $externalId );
		return $this->rpcService( $this->url, 'woodwing.DeleteNextgenImage', $params );
	}

	/**
	 * Upload image to WordPress.
	 *
	 * Here a single image get's uploaded to WordPress and the image is converted to base64.
	 * After uploading the image, this function will also do an update if the image has an description.
	 * This function uses the XML-RPC API.
	 *
	 * @param string $imageName
	 * @param string $filePath
	 * @param int $galleryId
	 * @param string $extension
	 *
	 * @return mixed
	 *
	 * @throws Exception BizException
	 */
	function uploadImage( $imageName, $filePath, $extension, $galleryId )
	{
		$imageFile = file_get_contents( $filePath );
		$data = array(
			'name'  => $imageName,
			'type'  => $extension,
			'bits'  => new IXR_Base64( $imageFile ), // The image convert to base 64 is needed for WordPress
			'gallery' => $galleryId
		);

		$rpc = new IXR_Client( $this->url );
		$status = $rpc->query( 'ngg.uploadImage', 0, $this->userName,	$this->password, $data );
		if( !$status ) {
			throw new BizException( 'WORDPRESS_ERROR_UPLOAD_IMAGE', 'Server', $rpc->getErrorMessage() );
		}

		return $rpc->getResponse();
	}

	/**
	 * Update NextGen image, updating an image from WordPress is done by deleting an image and uploading it again.
	 *
	 * This function is used to update an image source.
	 * This function uses the XML-RPC API.
	 *
	 * @param string $imageName
	 * @param string $filePath
	 * @param string $extension
	 * @param int $galleryId
	 * @param string $externalId
	 *
	 * @return mixed
	 */
	function updateImage( $imageName, $filePath, $extension, $galleryId, $externalId )
	{
		$this->deleteImage( $externalId );
		$result = $this->uploadImage( $imageName, $filePath, $extension, $galleryId );

		return $result;
	}

	/**
	 * Create a WordPress gallery.
	 *
	 * This function is used to create a gallery in the WordPress plug-in 'NextGen Gallery', this gallery is used to put in the images from a Publish Form.
	 * This function uses the XML-RPC API.
	 *
	 * @param $galleryName
	 * @param string $encoding
	 *
	 * @return mixed
	 */
	function createGallery( $galleryName, $encoding = 'UTF-8' )
	{
		$galleryName = htmlentities( $galleryName, ENT_NOQUOTES, $encoding );
		$params = array( 0, $this->userName, $this->password, $galleryName );

		return $this->rpcService( $this->url, 'ngg.newGallery', $params );
	}

	/**
	 * Get all WordPress galleries.
	 *
	 * This function is used to get all the galleries that are in the WordPress plug-in 'NextGen Gallery'.
	 * This function uses the XML-RPC API.
	 *
	 * @param $galleryName
	 *
	 * @return int|null
	 */
	function getGalleryIdByName( $galleryName )
	{
		$params = array( 0, $this->userName, $this->password );
		$galleries = $this->rpcService( $this->url, 'ngg.getGalleries', $params );

		$galleryId = null;
		if( $galleries ) foreach( $galleries as $gallery ) {
			if($galleryName == $gallery['title']){
				$galleryId = $gallery['gid'];
				break;
			}
		}

		return $galleryId;
	}

	/**
	 * Get all published WordPress galleries.
	 *
	 * This function gets all the published WordPress galleries.
	 * This function uses the XML-RPC API.
	 *
	 * @param $postId
	 *
	 * @return mixed
	 */
	function getGalleriesFromCustomField( $postId )
	{
		$params = array( $this->userName, $this->password, $postId );
		return $this->rpcService( $this->url, 'woodwing.GetGalleries',$params );
	}

	/**
	 * Get all WordPress categories.
	 *
	 * This function gets all the categories that are available in the WordPress site.
	 * This function uses the XML-RPC API.
	 *
	 * @return mixed
	 */
	function getCategories()
	{
		$params = array( 0, $this->userName, $this->password );
		return $this->rpcService( $this->url, 'wp.getCategories',$params );
	}

	/**
	 * Get all WordPress formats.
	 *
	 * This function gets all the formats that are available in the WordPress site.
	 * This function uses the XML-RPC API.
	 *
	 * @return mixed
	 */
	function getFormats()
	{
		$params = array( 0, $this->userName, $this->password, array( 'show-supported' => false ) );
		return $this->rpcService( $this->url, 'wp.getPostFormats',$params );
	}

	/**
	 * Get tags from WordPress.
	 *
	 * Get tags from WordPress which can be used for Autocomplete.
	 *
	 * @return mixed
	 */
	function getTags()
	{
		$params = array( 0, $this->userName, $this->password, 'post_tag' );
		return $this->rpcService( $this->url, 'wp.getTerms', $params );
	}

	/**
	 * Get WordPress users.
	 *
	 * This function is used to get all the users from WordPress
	 * This function uses the XML-RPC API.
	 *
	 * @param int $number // number of users you want to get
	 * @param int $offset // where to start searching in WordPress, needed if you have more users than you can get in one call
	 * @param string $fields // if 'import' then only get the user_id and username because this is much faster leave empty to get all fields
	 *
	 * @return mixed
	 */
	function getUsers( $number, $offset, $fields = null )
	{
		$filter = array(
			'who' => 'authors',
			'orderby' => 'username',
			'number' => $number,
		);

		if( $fields == 'import' ) {
			$fields = array( 'user_id', 'username' );
		}
		if( $offset ) {
			$filter['offset'] = $offset;
		}
		$params = array( 0, $this->userName, $this->password, $filter, $fields );

		return $this->rpcService( $this->url, 'wp.getUsers', $params );
	}

	/**
	 * WordPress plugin test
	 *
	 * This function calls the plugin test in the WoodWing plugin in WordPress,
	 * this test checks if al the other needed plugins are installed and activated.
	 *
	 * @param string $requiredVersion
	 *
	 * @return string|boolean returns true if correct else error string.
	 */
	function pluginTest( $requiredVersion )
	{
		$params = array( $this->userName, $this->password, $requiredVersion ) ;
		return $this->rpcService( $this->url, 'woodwing.PluginTest', $params );
	}
}