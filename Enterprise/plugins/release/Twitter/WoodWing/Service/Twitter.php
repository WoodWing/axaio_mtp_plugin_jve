<?php
/**
 * @see Zend_Oauth_Consumer
 */
require_once 'Zend/Service/Twitter.php';

/**
 * Class WoodWing_Service_Twitter This Class is used to override some functions of the api.
 * This is needed to make it possible to upload images along with a tweet.
 */
class WoodWing_Service_Twitter extends Zend_Service_Twitter
{
	/**
	 * Base URI for upload API calls
	 */
	const API_UPLOAD_URI = 'https://upload.twitter.com/1.1/';

	/**
	 * Update user's current status
	 *
	 * @param  string $status
	 * @param  null|int $inReplyToStatusId
	 * @param  null|string $mediaIds The ids of the media inserted into a string (comma seperated)
	 * @throws Exception\OutOfRangeException if message is too long
	 * @throws Exception\InvalidArgumentException if message is empty
	 * @throws Exception\DomainException if unable to decode JSON payload
	 * @throws Zend_Http_Client_Exception if HTTP request fails or times out
	 * @throws
	 * @return Zend_Service_Twitter_Response
	 */
	public function statusesUpdate($status, $inReplyToStatusId = null, $mediaIds = null )
	{
		$this->init();
		$path = 'statuses/update';
		$len = iconv_strlen(htmlspecialchars($status, ENT_QUOTES, 'UTF-8'), 'UTF-8');
		if ($len > self::STATUS_MAX_CHARACTERS) {
			require_once 'Zend/Service/Twitter/Exception.php';
			throw new Zend_Service_Twitter_Exception(
				'Status must be no more than '
				. self::STATUS_MAX_CHARACTERS
				. ' characters in length'
			);
		} elseif (0 == $len) {
			require_once 'Zend/Service/Twitter/Exception.php';
			throw new Zend_Service_Twitter_Exception(
				'Status must contain at least one character'
			);
		}

		$params = array('status' => $status);
		$inReplyToStatusId = $this->validInteger($inReplyToStatusId);
		if ($inReplyToStatusId) {
			$params['in_reply_to_status_id'] = $inReplyToStatusId;
		}

		if($mediaIds){
			$params['media_ids'] = $mediaIds;
		}

		$response = $this->post($path, $params);
		return new Zend_Service_Twitter_Response($response);
	}

	/**
	 * Uploads media to twitter. This is done by sending a base64_encoded string to twitter.
	 *
	 * @param null|string $media The url to the media file
	 * @throws Exception\DomainException if unable to decode JSON payload
	 * @return Zend_Service_Twitter_Response
	 */
	public function statusesUploadMedia( $media = null )
	{
		$this->init();
		$path = 'media/upload';
		$params = array();

		if( $media ){
			$params = array('media' => base64_encode(file_get_contents($media)));
		}

		$response = new Zend_Service_Twitter_Response( $this->post( $path, $params ) );
		$responseValues = $response->toValue();

		return $responseValues->media_id;
	}

	/**
	 * Call a remote REST web service URI
	 *
	 * @param  string $path The path to append to the URI
	 * @param  Zend_Http_Client $client
	 * @throws Zend_Http_Client_Exception
	 * @return void
	 */
	protected function prepare($path, Zend_Http_Client $client)
	{
		$baseUrl = ( $path == 'media/upload' ? self::API_UPLOAD_URI : self::API_BASE_URI );
		$client->setUri( $baseUrl . $path . '.json');

		/**
		 * Do this each time to ensure oauth calls do not inject new params
		 */
		$client->resetParameters();
	}
}