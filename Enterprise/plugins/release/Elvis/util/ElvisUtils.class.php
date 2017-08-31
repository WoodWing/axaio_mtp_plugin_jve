<?php

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
	 * @return bool
	 */
	public static function saveObjectsDoesReleaseObjectLock()
	{
		return self::$unlock === false;
	}

	/**
	 * Extracts Elvis asset id from alien id provided by enterprise
	 * @param $alienId - id of enterprise object, including _<ContentSourceId>_ prefix
	 * @return string Elvis asset id
	 */
	public static function getElvisId($alienId)
	{
		/**
		 * _HACK__HACK_:
		 * We use str_replace, because sometimes Enterprise prefixes the $alienId with two prefixes.
		 * Bad example: _ELVIS__ELVIS_<ASSETID>
		 * How it should be: _ELVIS_<ASSETID>
		 * /_HACK__HACK__ 
		 */
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return str_replace(ELVIS_CONTENTSOURCEPREFIX, "", $alienId);
	}
	
	public static function isElvisId($alienId)
	{
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return strpos($alienId, ELVIS_CONTENTSOURCEPREFIX) !== false;
	}
	
	public static function getAlienId($elvisId)
	{
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return ELVIS_CONTENTSOURCEPREFIX . $elvisId;
	}

	/**
	 * Returns hit from server, base on provided Elvis id
	 * @param $elvisId - for hit to be returned
	 * @param $lock - true if we have lock on Elvis server
	 * @return ElvisEntHit - hit from Elvis server
	 * @throws BizException - thrown if more then on hit found for specified Elvis ID
	 */
	public static function getHit($elvisId, $lock = false)
	{

		require_once dirname(__FILE__) . '/../logic/ElvisContentSourceService.php';
		require_once dirname(__FILE__) . '/../model/MetadataHandler.class.php';
		require_once dirname(__FILE__) . '/../model/ElvisCSNotFoundException.php';
		
		$service = new ElvisContentSourceService();

		$metadataHandler = new MetadataHandler();
		$metadataToReturn = $metadataHandler->getMetadataToReturn();
		$metadataToReturn[] = 'filename'; // needed to determine mimetype on receive thumb/preview/origin
		$metadataToReturn[] = 'sceId';
		$metadataToReturn[] = 'sceSystemId';
		$metadataToReturn[] = 'resolutionUnit'; // required to convert Elvis resolutionX to Enterprise Dpi

		try {
			$hit = $service->retrieve($elvisId, $lock, $metadataToReturn);
		} catch (ElvisCSNotFoundException $e) {
			if ($e instanceof ElvisCSNotFoundException) {
				// notify clients that related object not found on Elvis side
				throw new BizException('ERR_NOTFOUND', 'Server', 'Elvis assetId: ' . $elvisId . ', ' . $e->getMessage());
			}

			// Turn any uncatched ElvisCSException into a BizException
			$detail = 'Elvis assetId: ' . $elvisId;
			throw new BizException(null, 'Server', $detail, $e->getMessage());
		}
			
		return $hit;
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
	 * @param bool $returnFileUrls TRUE to return a direct link (URL) to the content source, or FALSE to download the file to the transfer server folder and return the URL.
	 * @return Attachment|null The file attachment, or null when not found.
	 * @throws BizException
	 */
	public static function getAttachment( $hit, $rendition, $returnFileUrls )
	{
		$attachment = null;
		$url = self::getUrlFromRendition( $hit, $rendition );
		if( $url ) {
			$type = self::getMimeType( $hit, $url, $rendition );
			if( $type ) {
				if( !$returnFileUrls ) {
					require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
					$transferServer = new BizTransferServer();
					$attachment = new Attachment();
					$attachment->Rendition = $rendition;
					$attachment->Type = $type;
					$attempt = 0;
					do {
						$attempt += 1;
						$httpStatus = $transferServer->copyToFileTransferServer( $url, $attachment, self::composeSessionOptions() ); // $httpStatus can be a boolean when running with Server <= 10.1.3
						$retry = self::retryCopyToFileTransferServer( $attempt, $httpStatus );
						if( $retry ) {
							require_once __DIR__.'/../logic/ElvisAMFClient.php';
							ElvisAMFClient::login();
						}
					} while( $retry );
					if( intval($httpStatus) >= 500 ) {
						ElvisAMFClient::throwExceptionForElvisCommunicationFailure(
							'Failed to copy '.$rendition.' file from Elvis server to Transfer Server folder.' );
					}
					if( intval($httpStatus) >= 400 || $httpStatus === false ) { // false: simulate ES < 10.1.4 behaviour
						throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server', null, null, array( '{RENDITION}', $rendition ) );
					}
				} else {
					// EN-88634: We no longer add our jsessionid to the ContentSourceFileLink URL; This URL is requested by
					// SC which passes on the URL to the Elvis InDesign plugin. This plugin has its own session with Elvis
					// and so it has its own authorization.
					$attachment = new Attachment();
					$attachment->Rendition = $rendition;
					$attachment->Type = $type;
					$attachment->ContentSourceFileLink = $url;
				}
			}
		}
		return $attachment;
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
		$alienId = self::getAlienId($elvisId);
		
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
	 * @param AdmUser $user
	 */
	public static function enrichUser($user) {
		//require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';
		require_once dirname(__FILE__) . '/../logic/ElvisContentSourceService.php';
		
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
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$activeClient = BizSession::getClientName();
		LogHandler::Log('ELVIS', 'DEBUG', 'client for ticket '. BizSession::getTicket() . ": ". $activeClient);
		return (bool)stristr($activeClient, $clientName);
	}
}
