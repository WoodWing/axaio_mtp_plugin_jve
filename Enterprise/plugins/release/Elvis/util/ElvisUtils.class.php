<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class ElvisUtils {

	private static $unlock = null;
	private static $toDelete = array();

	/**
	 * @param $unlock
	 */
	public static function setUnlock($unlock)
	{
		self::$unlock = $unlock;
	}

	/**
	 * Returns boolean to indicate if the SaveObject call releases the lock or retains the lock.
	 *
	 * True when the SaveObject call releases the lock(check-in),
	 * false when the SaveObject call retains the lock (remains checkout/save-version)
	 *
	 * @return bool
	 */
	public static function saveObjectsDoesReleaseObjectLock()
	{
		return self::$unlock === true;
	}

	/**
	 * Extracts Elvis asset id from alien id provided by enterprise
	 *
	 * @since 10.5.0 Renamed function from getElvisId into getAssetIdFromAlienId
	 * @param string $alienId Enterprise alien object id (including _<ContentSourceId>_ prefix)
	 * @return string Elvis asset id
	 */
	public static function getAssetIdFromAlienId( $alienId )
	{
		// Note that str_replace() is used because sometimes Enterprise prefixes the $alienId with two prefixes.
		// Bad example: _ELVIS__ELVIS_<ASSETID>
		// How it should be: _ELVIS_<ASSETID>
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return str_replace( ELVIS_CONTENTSOURCEPREFIX, '', $alienId );
	}

	/**
	 * Tells whether a given Enterprise alien object id belongs to the Elvis content source.
	 *
	 * @since 10.5.0 Renamed function from isElvisId into isElvisAssetId
	 * @param string $alienId
	 * @return bool
	 */
	public static function isElvisAssetId( $alienId )
	{
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return strpos( $alienId, ELVIS_CONTENTSOURCEPREFIX ) !== false;
	}

	/**
	 * Compose an Enterprise alien object id for a given Elvis asset id.
	 *
	 * @since 10.5.0 Renamed function from getAlienId into getAlienIdFromAssetId
	 * @param string $assetId
	 * @return string Alien id
	 */
	public static function getAlienIdFromAssetId( $assetId )
	{
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return ELVIS_CONTENTSOURCEPREFIX.$assetId;
	}

	/**
	 * Extract corresponding URL to Elvis asset, based on provided rendition.
	 *
	 * If version specified, it will be taken into account
	 * Extracted url, if could be resolved then used to copy file to Enterprise Transfer server.
	 *
	 * While copying, the Elvis session id is sent through cookies to authorize the download URL.
	 * This is done through input stream options.
	 *
	 * @param ElvisEntHit $hit Elvis search result (where the URL can be extracted from).
	 * @param string $rendition File rendition to download.
	 * @param string $fileLinkType 'FileUrl', 'ContentSourceFileLink' or 'ContentSourceProxyLink'
	 * @return Attachment|null The file attachment, or null when not found.
	 * @throws BizException
	 */
	public static function getAttachment( ElvisEntHit $hit, string $rendition, string $fileLinkType )
	{
		$attachment = null;
		$url = self::getUrlFromRendition( $hit, $rendition );
		if( $url ) {
			$type = self::getMimeType( $hit, $url, $rendition );
			if( $type ) {
				$objectId = $hit->metadata['sceId'];
				$attachment = new Attachment();
				$attachment->Rendition = $rendition;
				$attachment->Type = $type;
				switch( $fileLinkType ) {
					case 'FileUrl': // let client download from the File Transfer Server
						require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
						$transferServer = new BizTransferServer();
						$attempt = 0;
						do {
							$attempt += 1;
							$proxyUrl = self::composePrivateProxyFileDownloadUrl( $objectId, $rendition, BizSession::getTicket() );
							$httpStatus = $transferServer->copyToFileTransferServer( $proxyUrl, $attachment );
							$retry = self::retryCopyToFileTransferServer( $attempt, $httpStatus );
						} while( $retry );
						if( intval( $httpStatus ) >= 500 ) {
							ElvisAMFClient::throwExceptionForElvisCommunicationFailure(
								'Failed to copy '.$rendition.' file from Elvis server to Transfer Server folder.' );
						}
						if( intval( $httpStatus ) >= 400 || $httpStatus === false ) { // false: simulate ES < 10.1.4 behaviour
							throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server', null, null, array( '{RENDITION}', $rendition ) );
						}
						break;
					case 'ContentSourceFileLink': // let client directly download from Elvis
						// EN-88634: We no longer add our jsessionid to the ContentSourceFileLink URL; This URL is requested by
						// SC which passes on the URL to the Elvis InDesign plugin. This plugin has its own session with Elvis
						// and so it has its own authorization.
						$attachment->ContentSourceFileLink = self::replaceUrlForClientsAccess( $url );
						break;
					case 'ContentSourceProxyLink': // let client download from our Elvis proxy server
						$attachment->ContentSourceProxyLink = self::composePublicProxyFileDownloadUrl( $objectId, $rendition );
						break;
				}
			}
		}
		return $attachment;
	}

	/**
	 * Compose an URL for client applications to perform a file download over the Elvis proxy server.
	 *
	 * File downloads are authorized through the tickets in the cookie jar of the acting client application.
	 *
	 * @since 10.5.0
	 * @param string $objectId
	 * @param string $rendition File rendition to download.
	 * @return string The proxy file download URL.
	 */
	private static function composePublicProxyFileDownloadUrl( $objectId, $rendition )
	{
		return ELVIS_CONTENTSOURCE_PUBLIC_PROXYURL.
			'?objectid='.urlencode( $objectId ).
			'&rendition='.urlencode( $rendition );
	}

	/**
	 * Compose an URL for the server to perform a file download over the Elvis proxy server.
	 *
	 * File downloads are authorized through the provided ticket of the acting client application.
	 *
	 * @since 10.5.0
	 * @param string $objectId
	 * @param string $rendition File rendition to download.
	 * @param string $ticket
	 * @return string The proxy file download URL.
	 */
	private static function composePrivateProxyFileDownloadUrl( $objectId, $rendition, $ticket )
	{
		return ELVIS_CONTENTSOURCE_PRIVATE_PROXYURL.
			'?objectid='.urlencode( $objectId ).
			'&rendition='.urlencode( $rendition ).
			'&ticket='.urlencode( $ticket );
	}

	/**
	 * Whether or not the copy file to File TransferServer should be re-attempted.
	 *
	 * When the copy of a file to File TransferServer has failed, this function will
	 * determine if a re-attempt of the copy is logical.
	 *
	 * Re-attempt is logical when ...
	 * - the retry is for the first time
	 *
	 * AND when the status status code / boolean is one of the following
	 *
	 * - the copy fails due to network problem or Elvis node became unhealthy ( status code >= 500 ).
	 * - user was not logged in / session expired ( status code 401 ).
	 * - Elvis returns status code 409 due to several reasons:
	 *      L> request thumb or preview when asset previewState is set to 'failed'
	 *      L> processing has failed half-way due to unhealthy node
	 *      L> file is corrupt or could not be processed for some reason
	 *      L> caching for NFS share is enabled (should be disabled)
	 *      L> https://jira.woodwing.net/browse/LVS-9614
	 * - status code of 408, 421 and 429 were never really tested but they retry of the copy() does make sense for these statuses.
	 * - false: copy() failed and a boolean is returned in the case of Enterprise 10.1.3 and below is used.
	 *
	 * @param int $attempt The number of attempts that have been done so far, this will also determine if the reattempt should be done.
	 * @param bool|int $httpStatus HTTP status code that was returned from the copy()
	 * @return bool True when a retry is needed, False otherwise.
	 */
	private static function retryCopyToFileTransferServer( $attempt, $httpStatus )
	{
		$retry = $attempt <= 1 && (      // retry once only; the login() does the configured reattempts already
				intval($httpStatus) >= 500 || // fatal network problem or Elvis node unhealthy (e.g. LB returns 504)
				intval($httpStatus) == 401 || // user not logged in or session expired
				intval($httpStatus) == 408 || // request didn't finish on time, a re-try is possible. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
				intval($httpStatus) == 409 || // for Elvis, this can happen for several reasons, refer to function header.
				intval($httpStatus) == 421 || // misdirected request https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
				intval($httpStatus) == 429 || // too many requests in a given amount of time.
				$httpStatus === false); // in case Elvis plugin uses ES < 10.1.4 that returns true/false on any HTTP error

		return $retry;
	}

	/**
	 * Composes input stream options from Elvis session cookie.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @return array|null
	 */
	private static function composeSessionOptions()
	{
		$options = null;
		require_once __DIR__.'/ElvisSessionUtil.php';
		$cookies = ElvisSessionUtil::getSessionCookies();
		if( $cookies ) {
			$cookiesHeader = array();
			foreach( $cookies as $name => $value ) {
				$encodedValue = urlencode( $value );
				$cookiesHeader[] = "{$name}={$encodedValue}";
			}
			if( $cookiesHeader ) {
				$options = array( 'http' => array(
					'header' => "Cookie: ".implode('; ',$cookiesHeader ),
				) );
			}
		}
		return $options;
	}

	/**
	 * Restore Elvis version from enterprise version
	 *
	 * @param string $version - enterprise version
	 * @return string - Elvis version
	 */
	public static function getElvisVersionNumber($version)
	{
		require_once __DIR__.'/../config.php'; // ELVIS_ENTERPRISE_VERSIONPREFIX
		return substr($version, strlen(ELVIS_ENTERPRISE_VERSIONPREFIX));
	}

	/**
	 * Create enterprise version, base on Elvis version
	 *
	 * @param $version - Elvis version
	 * @return string - Enterprise version
	 */
	public static function getEnterpriseVersionNumber($version)
	{
		require_once __DIR__.'/../config.php'; // ELVIS_ENTERPRISE_VERSIONPREFIX
		return ELVIS_ENTERPRISE_VERSIONPREFIX.$version;
	}

	/**
	 * Removes the shadow object for the given Elvis id
	 * 
	 * @param string $elvisId
	 */
	public static function removeGhostShadowObject($elvisId)
	{
		// construct alienId
		$alienId = self::getAlienIdFromAssetId($elvisId);
		
		// ensure we invoke BizDeletedObject::deleteObject once (to avoid recursion)
		if (!in_array($alienId, self::$toDelete)) {
			self::$toDelete[] = $alienId;
			
			// Same check for shadow Id is done in deleteObject, but it will try to 
			// access Elvis as Alien object instead which we don't want
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			$shadowId = BizContentSource::getShadowObjectID($alienId);
			
			if ( $shadowId ) {
				LogHandler::Log('ELVIS', 'DEBUG', 'Elvis asset with id: "' . $elvisId . '" was removed'.
						', removing matching shadow object with id: "' . $shadowId .'"');
				// remove shadow object
				require_once BASEDIR . '/server/bizclasses/BizDeletedObject.class.php';
				$user = BizSession::getShortUserName();
				BizDeletedObject::deleteObject($user, $shadowId, true, array('Workflow', 'Trash'), null);
			}
		}
	}

	/**
	 * Enrich user AdmUser object.
	 *
	 * @param AdmUser $user
	 * @return AdmUser
	 */
	public static function enrichUser($user) {
		//require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
		require_once __DIR__.'/../logic/ElvisContentSourceService.php';
		
		$service = new ElvisContentSourceService();
		$userDetails = $service->getUserDetails($user->Name);
		
		if ($userDetails) {
			require_once __DIR__.'/../config.php'; // ELVIS_INTERNAL_USER_POSTFIX
			$user->FullName = $userDetails->fullName;
			$user->EmailAddress = $userDetails->email;
			if (!$userDetails->ldapUser) {
				$user->FullName .= ELVIS_INTERNAL_USER_POSTFIX;
				$user->Deactivated = true;
			}
		}
		
		return $user;
	}

	/**
	 * Returns a url from a Elvis hit given a specific rendition.
	 *
	 * @param ElvisEntHit $hit
	 * @param string $rendition
	 * @return string|null download url if a url is set for a rendition, else null.
	 */
	private static function getUrlFromRendition( $hit, $rendition )
	{
		$result = null;
		switch( $rendition ) {
			case 'thumb' :
				if( $hit->thumbnailUrl ) {
					$result = $hit->thumbnailUrl;
				}
				break;
			case 'preview' :
				if( $hit->previewUrl ) {
					$result = $hit->previewUrl;
				}
				break;
			case 'native' :
			case 'placement' :
				if( $hit->originalUrl ) {
					$result = $hit->originalUrl;
				}
				break;
		}
		return $result;
	}

	/**
	 * Replace the passed in $url ( that contains ELVIS_URL ) with ELVIS_CLIENT_URL.
	 *
	 * When a url is returned to the clients, the url that is pointing to Elvis has
	 * to be ELVIS_CLIENT_URL ( instead of ELVIS_URL ).
	 *
	 * From EN-88079:
	 * 1. When Enterprise Server sends in the request to Elvis, it uses ELVIS_URL to
	 * connect to Elvis.
	 * 2. When Elvis receives the request, it uses this very same url to compose the
	 * download URL for the Elvis object.
	 * 3. Download URL is returned to Enterprise Server.
	 * 4. Enterprise Server returns this download URL to the clients, but not all clients can
	 * access Elvis using this download URL. This is because ELVIS_URL that is used is
	 * meant for communication between Enterprise Server and Elvis ( which are often located
	 * in the same local network ).
	 *
	 * Therefore, the download URL ( $url ) needs to be replaced with ELVIS_CLIENT_URL
	 * in order for the clients to be able to access Elvis.
	 *
	 * @since 10.1.6
	 * @param string $url
	 * @return string
	 */
	private static function replaceUrlForClientsAccess( $url )
	{
		$count = 0;
		$replacedUrl = str_replace( ELVIS_URL, ELVIS_CLIENT_URL, $url, $count );
		return ( $count == 1 ) ? $replacedUrl : $url;
	}

	/**
	 * Extracts the mime type from the hit
	 *
	 * @param ElvisEntHit $hit
	 * @param string $url
	 * @param string $rendition
	 * @return string|null Resolved mime type, else NULL.
	 */
	private static function getMimeType( $hit, $url, $rendition )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$fileName = null;
		switch( $rendition ) {
			case 'preview':
			case 'thumb':
				$parts = parse_url( $url );
				if( isset($parts['path']) ) {
					$path = $parts['path'];
					$pathInfo = pathinfo( $path );
					if( isset($pathInfo['basename']) ) {
						$fileName = $pathInfo['basename'];
					}
				}
				break;
			case 'placement':
			case 'native':
				$fileName = $hit->metadata['filename'];
				break;
		}
		return $fileName? MimeTypeHandler::filePath2MimeType( $fileName ) : null;
	}

	/**
	 * Returns true if caller is Content Station
	 *
	 * @returns boolean
	 */
	static public function isContentStation()
	{
		return self::isClient('content station');
	}
	
	/**
	 * Returns true if caller is Smart Connection
	 *
	 * @returns boolean
	 */
	static public function isSmartConnection()
	{
		return self::isClient('InDesign') || self::isClient('InCopy');
	}
	
	/**
	 * Returns true if caller is Smart Connection
	 *
	 * @returns boolean
	 */
	static public function isInDesignServer()
	{
		return self::isClient('InDesign Server');
	}
	
	/**
	 * Returns true if caller is $clientName
	 *
	 * @param string $clientName
	 * @return boolean
	 */
	static private function isClient($clientName)
	{
		$activeClient = BizSession::getClientName();
		LogHandler::Log('ELVIS', 'DEBUG', 'client for ticket '. BizSession::getTicket() . ": ". $activeClient);
		return (bool)stristr($activeClient, $clientName);
	}
}
