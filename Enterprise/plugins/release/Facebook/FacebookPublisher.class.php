<?php

require_once dirname(__FILE__) . '/sdk/facebook.php';
require_once dirname(__FILE__) . '/WW_Facebook.class.php';
/**
 * Wrapper class for handling Facebook publishing.
 *
 * Note: some methods for un-publishing are missing, this is because not all the un-publish steps are supported by the
 * Facebook Graph API at the time of this writing.
 *
 * @package	 Enterprise
 * @subpackage	 ServerPlugins
 * @since		 v7.6
 * @copyright	 WoodWing Software bv. All Rights Reserved.
 */
class FacebookPublisher
{
	private $pageAccessToken = null;
	private $facebook = null; //The Facebook object.
    public $userAccessToken = null;
    private $channelId = null;
    private $appSecret = null;
    public $appId = null;
    public $pageId = null;

    /**
	 * The construct function of Facebook publisher
	 *
	 * This can be used to initialize a Facebook connector by giving an optional channelId, this only works when
	 * the matching access token file exists.
	 *
     * @param int $channelId
     */
    public function __construct($channelId = null)
	{
        if($channelId != null){
        	require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
       		BaseFacebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;

        	$this->channelId = $channelId;
        	$this->getConfig($channelId);

			$config = array(
				'appId' => $this->appId,
				'secret' => $this->appSecret,
			);

			$this->facebook = new WW_Facebook($config);
			$this->facebook->setFileUploadSupport(true); // Needed to be able to upload to Facebook.
			$isLogin = $this->setUserAccessToken($this->channelId);

			// Set page access token if logged in.
			if($isLogin){
				$this->facebook->setAccessToken($this->getPageAccessToken($this->userAccessToken, $this->pageId));
			}
		}
	}

    /**
	 * Setting the User access-token
	 *
     * Sets the user access-token with the access-token in the .bin file or else it saves the user token
	 *
     * @param int $channelId
     * @return bool
     */
    public function setUserAccessToken( $channelId )
	{
		$result = false;
        if($this->getAccessToken( $channelId )){
            $this->userAccessToken = $this->getAccessToken( $channelId );
            $result = true;
        }else if($this->facebook->getUserAccessTokenForSaving()){
            $this->userAccessToken = $this->facebook->getUserAccessTokenForSaving();
            $result = true;
        }
		return $result;
    }

    /**
	 * Redirect to Facebook login
	 *
	 * This function redirect the user to Facebook, here you'll need to login and after that he'll be redirected to
	 * The Facebook maintenance page.
	 *
     * @param int $channelId
     */
    public function loginToFacebook ($channelId)
	{
        $returnUrl = SERVERURL_ROOT . INETROOT . '/config/plugins/Facebook/callback.php?channel=' . $channelId ;
        $permissions = 'manage_pages, publish_actions, publish_pages';

		$config = array(
			'appId' => $this->appId,
			'secret' => $this->appSecret,
		);

        $this->facebook = new WW_Facebook($config);
        $fbLoginUrl = $this->facebook->getLoginUrl(array('redirect_uri'=>$returnUrl, 'scope'=>$permissions));

        header('Location:' .$fbLoginUrl);
    }

    /**
	 * Get config fields
	 *
	 * Get the Facebook config fields that are needed for the Facebook login.
	 *
     * @param int $channelId
     */
    public function getConfig($channelId)
	{
        require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
        require_once BASEDIR . '/server/utils/PublishingUtils.class.php';

        $channelObj = WW_Utils_PublishingUtils::getAdmChannelById($channelId);
        $this->appId = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_FPF_CHANNEL_APPLICATION_ID' );
        $this->appSecret = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_FPF_CHANNEL_APP_SECRET' );
        $this->pageId = BizAdmProperty::getCustomPropVal( $channelObj->ExtraMetaData, 'C_FPF_CHANNEL_PAGE_ID' );
    }

    /**
	 * Get User access-token from code
	 *
	 * After the login the user is redirected with a code, this needs to be converted to an access-token
	 * After the convert the user get's logged off from Facebook
	 *
     * @param $reqToken
     */
    public function retrieveCodeFromRedirection($reqToken)
	{
        $code = $reqToken["code"];
        $channelId = $reqToken["channel"];

        $this->getConfig($channelId);
        $this->channelId = $channelId;

		$config = array(
			'appId' => $this->appId,
			'secret' => $this->appSecret,
		);

        $this->facebook = new WW_Facebook($config);
        $accessToken = $this->facebook->getAccessTokenFromFbCode($code);
        $this->saveAccessToken($accessToken, $channelId);

		if( !$accessToken ) {
			LogHandler::Log( 'Facebook', 'ERROR', 'Failed retrieving access token.' . PHP_EOL .
				'Code:' . $code . PHP_EOL .
				'ChannelId:' . $channelId );
		}

		// Access token is already retrieved above, so code and state are no longer needed.
		// Remove them to avoid "CSRF state token does not match one provided." error.
		unset( $_REQUEST['code'] );
		unset( $_REQUEST['state'] );
		$logOutUri = $this->facebook->getLogoutUrl( array(
			'access_token' => $accessToken,
			'next' => SERVERURL_ROOT.INETROOT.'/server/admin/webappindex.php?webappid=ImportDefinitions&plugintype=config&pluginname=Facebook' ) );

        header('Location: ' . $logOutUri);
    }

    /**
	 * Save access-token to .bin file
	 *
	 * Here an access-token gets stored in a .bin file
	 *
     * @param $accToken
     * @param $channelId
     */
    public function saveAccessToken($accToken, $channelId)
    {
        if($channelId){
            if ( $accToken ) {
                $tokenFile = $this->getAccessTokenFilePath($channelId);
                file_put_contents($tokenFile, serialize($accToken));
            }
        }
    }

    /**
	 * Get access-token from save
	 *
	 * Here an access-token can be loaded from a saved access-token.
	 *
     * @param $channelId
     * @return mixed|null
     */
    public function getAccessToken($channelId)
    {
        require_once 'Zend/Oauth/Token/Access.php'; // Needed to serialize this class below.
        $tokenFile = $this->getAccessTokenFilePath($channelId);
		$token = null;

        if ( file_exists($tokenFile) ) {
            $token = unserialize(file_get_contents($tokenFile));
        }

        return $token;
    }

    /**
	 * Delete access-token save
	 *
	 * Here the .bin file from a saved access-token gets deleted
	 *
     * @param $channelId
     */
    public function releaseAccessToken($channelId)
    {
        $tokenFile = $this->getAccessTokenFilePath($channelId);
        if ( file_exists($tokenFile) ) {
            unlink($tokenFile);
        }
    }

    /**
	 * The directory for saving and loading access-tokens
	 *
     * @param $channelId
     * @return string
     */
    private function getAccessTokenFilePath($channelId)
    {
        return WOODWINGSYSTEMDIRECTORY . '/facebook_token_' . $channelId . '.bin';
    }

    /**
	 * Return the userAccessToken
	 *
     * @return null
     */
    public function getUserAccessToken()
	{
        return  $this->userAccessToken;
    }

    /**
	 * Return the Facebook user's name
	 *
	 * Before getting the user name be sure to set the user access-token
	 *
     * @param $channelId
     * @return mixed
     */
    public function getUserName($channelId)
	{
        $this->facebook->setAccessToken($this->getAccessToken($channelId));

		$user = $this->checkUser();
		$userName = null;
		if($user['name'] != null){
			$userName = $user['name'];
		}

		return $userName;
    }

    /**
	 * Check user and return his account
	 *
     * @return mixed
     */
    public function checkUser()
	{
        try{
			$user = $this->facebook->api('/me','GET');
		}catch (FacebookApiException $e){
			$e = $e;
			$user = null;
		}
        return $user;
    }

	/**
	 * Retrieves the access token for a Facebook page based on the user's access token and the page id.
	 *
	 * Note: An exception is thrown if the account data can not be retrieved from Facebook. NULL is returned if the
	 * page cannot be found in the user's account data.
	 *
	 * @param string $userAccessToken The access token for the user, as defined in the config.php
	 * @param string $pageId The page id for the Facebook page, as defined in the config.php
	 * @return null|string The found page access token or null, if not found.
	 * @throws Exception Throws an exception if the account data for the user can not be retrieved.
	 */
	public function getPageAccessToken($userAccessToken, $pageId)
        {
            // If we have a valid page access token, return it.
            if (!is_null($this->pageAccessToken)) {
                return $this->pageAccessToken;
            }

            $connectString = 'https://graph.facebook.com/me/accounts?access_token=' . $userAccessToken;
            $accounts = json_decode(file_get_contents($connectString));

            if (empty($accounts)) {
                throw new Exception('Could not retrieve the account information.');
            }

            foreach ($accounts->data as $account) {
                if ($account->id == $pageId) {
                    $this->pageAccessToken = $account->access_token;
                }
            }

            return $this->pageAccessToken;
        }

	/**
	 * Retrieves the album id based on the album name, and the access token.
	 *
	 * Note: Retrieves the Album ID based on the page id and the name of the album.
	 *
	 * @param string $pageId The mandatory page id for which to retrieve the album id.
	 * @param string $name The mandatory name of the album for which to retrieve the id.
     * @return null
     * @throws Exception
	 */
    private function getAlbumIdByPageAndName($pageId, $name)
	{
		if (empty($pageId)) {
			throw new Exception('Parameter $pageId may not be empty.');
		}
		if (empty($name)) {
			throw new Exception('Parameter $name may not be empty.');
		}

		$connectString = 'https://graph.facebook.com/' . $pageId . '/albums?access_token='
		. $this->getPageAccessToken($this->userAccessToken, $pageId);

		$albums = json_decode(file_get_contents($connectString));
		$albumId = null;

		if (!empty($albums)) {
			foreach ($albums->data as $album) {
				if ($album->name == $name) {
					$albumId = $album->id;
				}
			}
		}
		return $albumId;
	}



	/**
	 * Create a new photo album based on the name.
	 *
	 * Note: If it is desirable to create an album that already exists then, set the $duplicateIfExists param to true.
	 * otherwise no Album is created, but the id of an existing album is returned.
	 *
	 * @param $pageId The page id for which to create the new Album.
	 * @param $name The name of the album to be created.
	 * @param string $description The description of the album.
	 * @param bool $duplicateIfExists Whether to create a new Album if there already is an album with the same name.
	 * @return null|string The ID of the created album, or found album.
	 * @throws Exception Throws an Exception if the creation of the album or retrieval of album details fail.
	 */
    public function createAlbum($pageId, $name, $description = '', $duplicateIfExists = false)
	{
		if (empty($pageId)) {
			throw new Exception('Parameter $pageId may not be empty.');
		}
		if (empty($name)) {
			throw new Exception('Parameter $name may not be empty.');
		}

		$albumId = null;

		if (false == $duplicateIfExists) {
			// Check if there is an existing album, and return it's id if it does.
			$albumId = $this->getAlbumIdByPageAndName($pageId, $name);
			if (!is_null($albumId)) {
				return $albumId;
			}
		}

		// Create a new album
		$album_details = array(
			'message' => $description,
			'name' => $name,
		);

		// Return the album id of the newly created album.
		$createAlbum = $this->facebook->api('/' . $pageId . '/albums', 'POST', $album_details);
		return $createAlbum['id'];
	}

	/**
	 * Upload a picture to a Facebook Page.
	 *
	 * Uploads a picture to Facebook, to either an album or on the default album on the Page. If the specified
	 * album does not exist, it is created.
	 *
	 * @param string $pageId The id of the Page to which to upload the picture.
	 * @param string $file The absolute path to the file to be uploaded.
     * @param string $description The optional description for a picture
     * @param string $album_name The optional name for the album
	 * @param string $album_description The optional description for the album.
	 * @param string $albumId when we already have the albumId this can be used to upload.
     * @return mixed
	 * @throws Exception Throws an Exception if the creation / upload process fails.
	 */
    public function uploadPictureToPage($pageId, $file, $description = '', $album_name = '', $album_description = '', $albumId = null)
	{
        $this->facebook->setFileUploadSupport(true);
		if (empty($pageId)) {
			throw new Exception('Parameter $pageId may not be empty.');
		}
		if (empty($file)) {
			throw new Exception('Parameter $file may not be empty.');
		}

		$file = '@' . $file; // keep Facebook happy.
		// Create a new album as needed.
		$connectString = '/' . $pageId . '/photos';

		if (!empty($album_name)) {
			if(!$albumId || $albumId == ''){
				$albumId = $this->createAlbum($pageId, $album_name, $album_description);
			}
			$connectString = $albumId . '/photos';
		}
		// Upload the picture.
		try {
            $object = $this->facebook->api($connectString, 'POST', array(
			    'image' => $file ,
			    'message' => $description,
				)
			);
        } catch (FacebookApiException $e) {
            $e = $e;
        }

		$object['albumId'] = $albumId;

		return $object;
	}

	/**
	 * Post a message to the Page feed.
	 *
	 * @param string $pageId The ID of the page to which to post the message.
	 * @param string $message The message to post on Facebook.
	 * @param null|string $link Optional link to be posted with the message.
	 * @param null|string $caption Optional caption for the link to be posted.
	 * @param null|string $description Optional description for the link to be posted.
	 * @returns string $post_id The id of the message just posted.
	 * @throws FacebookApiException Throws an Exception if posting is not possible.
	 * @throws Exception Throws an exception if the parameters are incorrect.
	 */
	public function postMessageToPageFeed($pageId, $message, $link = null, $caption = null, $description = null)
	{
		if (empty($pageId)) {
			throw new Exception('Parameter $pageId may not be empty.');
		}
		if (empty($message)) {
			throw new Exception('Parameter $message may not be empty.');
		}

		$parameters = array('message' => $message);

		if (!is_null($link)) {
			$parameters['link'] = $link;
		}
		if (!is_null($caption)) {
			$parameters['caption'] = $caption;
		}
		if (!is_null($description)) {
			$parameters['description'] = $description;
		}

		$post_id = $this->facebook->api('/' . $pageId . '/feed', 'POST', $parameters);
		return $post_id['id'];
	}

	/**
	 * Delete a post from the page wall.
	 *
	 * @param $pageId String The ID of the page for which to delete a post.
	 * @param $message_id String The ID of the message to be deleted.
     * @return mixed
	 * @throws Exception Throws an exception if the deletion cannot be completed successfully.
	 */
    public function deleteMessageFromFeed($pageId, $message_id)
	{
		if (empty($pageId)) {
			throw new Exception('Parameter $pageId may not be empty.');
		}
		if (empty($message_id)) {
			throw new Exception('Parameter $message_id may not be empty.');
		}

		$connectString = '/' . $message_id;

		$response = $this->facebook->api($connectString, 'DELETE', array());

		if (!$response) {
			throw new Exception('The post was not deleted from the page wall.');
		}

		return $response;
	}

}