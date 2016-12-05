<?php

class ElvisRESTClient
{
	/**
	 * Calls an Elvis web service over the REST JSON interface.
	 *
	 * It does log the request and response data in DEBUG mode.
	 *
	 * @param string $service Service name of the Elvis API.
	 * @param string[]|null $post Optionally. List of HTTP POST parameters to send along with the request.
	 * @return mixed Returned
	 * @throws BizException
	 */
	private static function send( $service, $post = null )
	{
		require_once dirname( __FILE__ ).'/../util/ElvisUtils.class.php';
		$url = ElvisUtils::getServiceUrl( $service );
		$isDebug = LogHandler::debugMode();
		if( $isDebug ) {
			LogHandler::Log( 'ELVIS', 'DEBUG', 'RESTClient calling Elvis web service '.$service );
			$logRequest = 'URL: '.$url.PHP_EOL.'DATA: '.print_r( $post, true );
			LogHandler::logService( 'Elvis_'.$service, $logRequest, true, 'JSON' );
		}
		$response = self::sendUrl( $service, $url, $post );
		if( $isDebug ) {
			if( isset( $response->errorcode ) ) {
				LogHandler::logService( 'Elvis_'.$service, print_r( $response, true ), null, 'JSON' );
			} else {
				LogHandler::logService( 'Elvis_'.$service, print_r( $response, true ), false, 'JSON' );
			}
		}
		if( isset( $response->errorcode ) ) {
			$detail = 'Calling Elvis web service '.$service.' failed. '.
				'Error code: '.$response->errorcode.'; Message: '.$response->message;
			self::throwExceptionForElvisCommunicationFailure( $detail );
		}
		return $response;
	}

	/**
	 * Calls an Elvis web service over the REST JSON interface.
	 *
	 * @param string $service Service name of the Elvis API.
	 * @param string $url Request URL (JSON REST)
	 * @param string[]|null $post Optionally. List of HTTP POST parameters to send along with the request.
	 * @return mixed
	 * @throws BizException
	 */
	private static function sendUrl( $service, $url, $post = null )
	{
		$ch = curl_init();
		if( !$ch ) {
			$detail = 'Elvis '.$service.' failed. '.
				'Failed to create a CURL handle for url: '.$url;
			self::throwExceptionForElvisCommunicationFailure( $detail );
		}

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		if( defined( 'ELVIS_CURL_OPTIONS') ) { // hidden option
			$options = unserialize( ELVIS_CURL_OPTIONS );
			if( $options ) foreach( $options as $key => $value ) {
				curl_setopt( $ch, $key, $value );
			}
		}

		if( isset( $post ) ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
		}

		$result = curl_exec( $ch );
		if( $result === false ) {
			$detail = 'Elvis '.$service.' failed. '.
				'CURL failed with error code '.curl_errno( $ch ).' for url: '.$url;
			self::throwExceptionForElvisCommunicationFailure( $detail );
		}

		curl_close( $ch );

		return json_decode( $result );
	}

	/**
	 * Throws BizException for low level communication errors with Elvis Server.
	 *
	 * For ES 10.0 or later it throws a S1144 error else it throws S1069.
	 *
	 * @since 10.1.1
	 * @param string $detail
	 * @throws BizException
	 */
	private static function throwExceptionForElvisCommunicationFailure( $detail )
	{
		require_once BASEDIR . '/server/utils/VersionUtils.class.php';
		$serverVer = explode( ' ', SERVERVERSION ); // split '9.2.0' from 'build 123'
		if( VersionUtils::versionCompare( $serverVer[0], '10.0.0', '>=' ) ) {
			throw new BizException( 'ERR_CONNECT', 'Server', $detail, null, array( 'Elvis' ) );
		} else {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', $detail );
		}
	}


	/**
	 * Performs REST update for provided metadata and file (if any).
	 *
	 * @param string $elvisId Id of asset
	 * @param array $metadata Changed metadata
	 * @param Attachment|null $file
	 * @throws BizException
	 */
	public static function update( $elvisId, $metadata, $file = null )
	{
		$post = array();
		$post['id'] = $elvisId;
		if( !empty( $metadata ) ) {
			$post['metadata'] = json_encode( $metadata );
		}

		if( isset( $file ) ) {
			//This class replaces the deprecated "@" syntax of sending files through curl. 
			//It is available from PHP 5.5 and onwards, so the old option should be maintained for backwards compatibility.
			if( class_exists( 'CURLFile' ) ) {
				$post['Filedata'] = new CURLFile( $file->FilePath, $file->Type );
			} else {
				$post['Filedata'] = '@'.$file->FilePath;
			}
		}

		self::send( 'update', $post );
	}

	/**
	 * Performs a bulk update for provided metadata.
	 *
	 * Calls the updatebulk web service over the Elvis JSON REST interface.
	 *
	 * @param string[] $elvisIds Ids of assets
	 * @param MetaData|MetaDataValue[] $metadata Changed metadata
	 * @throws BizException
	 */
	public static function updateBulk( $elvisIds, $metadata )
	{
		$post = array();

		// Build query for ids
		$post['q'] = '';
		foreach( $elvisIds as $elvisId ) {
			if( !empty( $post['q'] ) ) {
				$post['q'] .= ' OR ';
			}
			$post['q'] .= 'id:'.$elvisId;
		}

		if( !empty( $metadata ) ) {
			$post['metadata'] = json_encode( $metadata );
		}

		self::send( 'updatebulk', $post );
	}

	/**
	 * Performs REST logout of the acting Enterprise user from Elvis.
	 *
	 * Calls the logout web service over the Elvis JSON REST interface.
	 *
	 * @throws BizException
	 */
	public static function logout()
	{
		require_once __DIR__.'/../util/ElvisSessionUtil.php';
		if( ElvisSessionUtil::getSessionId() ) {
			self::logoutSession();
		}
	}

	/**
	 * Does logout of the acting Enterprise user from Elvis.
	 *
	 * Calls the logout web service over the Elvis JSON REST interface.
	 *
	 * @throws BizException
	 */
	private static function logoutSession()
	{
		return self::send( 'logout' );
	}

	/**
	 * Calls the fieldinfo web service over the Elvis JSON REST interface.
	 *
	 * @return mixed
	 * @throws BizException
	 */
	public static function fieldInfo()
	{
		return self::send( 'fieldinfo' );
	}

	/**
	 * Pings the Elvis Server and retrieves some basic information.
	 *
	 * @return object Info object with properties state, version, available and server.
	 */
	public function getElvisServerInfo()
	{
		// The Elvis ping service returns a JSON structure like this:
		//     {"state":"running","version":"5.15.2.9","available":true,"server":"Elvis"}
		return self::send( 'ping' );
	}
}
