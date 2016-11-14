<?php

class ElvisRESTClient
{
	
	public static function send($service, $post)
	{
		require_once dirname(__FILE__) . '/../util/ElvisUtils.class.php';
		$url = ElvisUtils::getServiceUrl($service);
		return self::sendUrl($url, $post);
	}
	
	private static function sendUrl($url, $post, $contentType = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);

		if( $contentType ) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:'.$contentType));
		}
		
		if( isset($post) ) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		return json_decode($result);
	}

	/**
	 * Performs REST update for provided metadata and file (if any).
	 *
	 * @param string $elvisId Id of asset
	 * @param array $metadata Changed metadata
	 * @param Attachment|null $file
	 * @throws BizException
	 */
	public static function update($elvisId, $metadata, $file=NULL)
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'RESTClient - update for elvisId: ' . $elvisId);
		
		$post = array();
		$post['id'] = $elvisId;
		if (!empty($metadata)) {
			$post['metadata'] = json_encode($metadata);
		}
		
		$contentType = '';
		if (isset($file)) {
			//This class replaces the deprecated "@" syntax of sending files through curl. 
			//It is available from PHP 5.5 and onwards, so the old option should be maintained for backwards compatibility.
			if(class_exists('CURLFile')) {
				$post['Filedata'] = new CURLFile($file->FilePath);
				$contentType = 'multipart/form-data';
			} else {
				$post['Filedata'] = '@' . $file->FilePath;
			}
		}
	
		$jsonResponse = self::send('update', $post, $contentType);
		if (isset($jsonResponse->errorcode)) {
			$message = 'Updating Elvis failed. Elvis id: ' . $elvisId . '; Error code: ' . $jsonResponse->errorcode . '; Message: ' . $jsonResponse->message;
			throw new BizException('ERR_INVALID_OPERATION', 'Server', $message, $message);
		}
	}

	/**
	 * Performs REST bulk update for provided metadata.
	 *
	 * @param string[] $elvisIds Ids of assets
	 * @param MetaData|MetaDataValue[] $metadata Changed metadata
	 * @throws BizException
	 */
	public static function updateBulk($elvisIds, $metadata)
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'RESTClient - updateBulk for elvisIds');

		$post = array();

		// Build query for ids
		$post['q'] = '';
		foreach( $elvisIds as $elvisId ) {
			if( !empty( $post['q'] ) ) {
				$post['q'] .= ' OR ';
			}
			$post['q'] .= 'id:'.$elvisId;
		}

		if (!empty($metadata)) {
			$post['metadata'] = json_encode($metadata);
		}

		$jsonResponse = self::send('updatebulk', $post);
		if (isset($jsonResponse->errorcode)) {
			$message = 'Updating Elvis failed. Query: ' . $post['q'] . '; Error code: ' . $jsonResponse->errorcode . '; Message: ' . $jsonResponse->message;
			throw new BizException('ERR_INVALID_OPERATION', 'Server', $message, $message);
		}
	}
	
	//TODO: Remove this and create AMF call in ContentSourceService
	public static function logout() 
	{
		if (ElvisSessionUtil::getSessionId()) {
			self::logoutSession(ElvisSessionUtil::getSessionId());
		}
	}

	//TODO: Remove this and create AMF call in ContentSourceService
	public static function logoutSession($sessionId)
	{
		$url = ELVIS_URL . '/services/logout' . ';jsessionid=' . $sessionId;
	
		LogHandler::Log('ELVIS', 'DEBUG', 'RESTClient - logout - url:' . $url);
	
		$ch = curl_init($url);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Direct output gives a warning in the HealthCheck.
		if (!$ch) {
			$message = 'Elvis logout failed';
			$detail = 'Elvis logout failed, failed to create a curl handle with url: ' . $url;
			throw new BizException(null, 'Server', $detail, $message);
		}
		$success = curl_exec($ch);
		if ( $success === false ) {
			$errno = curl_errno($ch);
			$message = 'Elvis logout failed';
			$detail = 'Elvis logout failed, curl_exec failed with error code: ' . $errno . ' for url: ' . $url;
			throw new BizException(null, 'Server', $detail, $message);
		}
		curl_close($ch);
	}

	public static function fieldInfo()
	{
		LogHandler::Log('ELVIS', 'DEBUG', 'RESTClient - fieldinfo');

		$jsonResponse = self::send('fieldinfo', null);
		if (isset($jsonResponse->errorcode)) {
			$message = 'Query Elvis for field info failed. Error code: ' . $jsonResponse->errorcode . '; Message: ' . $jsonResponse->message;
			throw new BizException('ERR_INVALID_OPERATION', 'Server', $message, $message);
		}

		return $jsonResponse;
	}

}
