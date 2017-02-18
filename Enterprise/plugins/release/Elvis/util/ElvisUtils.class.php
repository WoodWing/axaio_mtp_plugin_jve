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
	 * Extracts elvis asset id from alien id provided by enterprise
	 * @param $alienId - id of enterprise object, including _<ContentSourceId>_ prefix
	 * @return string elvis asset id
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
		return str_replace(ELVIS_CONTENTSOURCEPREFIX, "", $alienId);
	}
	
	public static function isElvisId($alienId)
	{
		return strpos($alienId, ELVIS_CONTENTSOURCEPREFIX) !== false;
	}
	
	public static function getAlienId($elvisId)
	{
		return ELVIS_CONTENTSOURCEPREFIX . $elvisId;
	}

	/**
	 * Returns hit from server, base on provided elvis id
	 * @param $elvisId - for hit to be returned
	 * @param $lock - true if we have lock on Elvis server
	 * @return ElvisEntHit - hit from Elvis server
	 * @throws BizException - thrown if more then on hit found for specified elvis ID
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
	 * Extract corresponding URL to elvis asset, based on provided rendition.
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
					if( !$transferServer->copyToFileTransferServer( $url, $attachment, self::composeSessionOptions() ) ) {
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
			$cookiesHeader = '';
			foreach( $cookies as $name => $value ) {
				$cookiesHeader .= "Cookie: $name=$value\r\n";
			}
			if( $cookiesHeader ) {
				$options = array( 'http' => array(
					'header' => $cookiesHeader
				) );
			}
		}
		return $options;
	}

	/**
	 * Restore elvis version from enterprise version
	 *
	 * @param string $version - enterprise version
	 * @return string - Elvis version
	 */
	public static function getElvisVersionNumber($version)
	{
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
		return ELVIS_ENTERPRISE_VERSIONPREFIX.$version;
	}
	
	/**
	 * Removes the shadow object for the given elvis id
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
