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
	 * @param object $hit - hit which is used for url extraction
	 * @param string $rendition - to be extracted
	 * @param bool $returnFileUrls When true, only file links to the content source are returned, otherwise the complete file cpmtemt/
	 * @return Attachment - if could be restored or null
	 * @throws BizException
	 */
	public static function getAttachment($hit, $rendition, $returnFileUrls)
	{
		$file = null;
		$url = ElvisUtils::getUrlFromRendition($hit, $rendition);

		if (!is_null($url)) {
			$type = self::getMimeType($hit);

			if( !$returnFileUrls ) {
				require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				$attachment = new Attachment($rendition, $type);
				if ( !$transferServer->copyToFileTransferServer($url, $attachment) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server', null, null, array( '{RENDITION}', $rendition ) );
				}
				$file = $attachment;
			} else {
				$file = new Attachment($rendition, $type, null, null, null, null, $url);
			}
		}

		return $file;
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
	 * @param object $hit
	 * @param string $rendition
	 * @return string|null download url if a url is set for a rendition, else null.
	 */
	public static function getUrlFromRendition( $hit, $rendition )
	{
		$result = null;
		switch( $rendition ) {
			case 'thumb' :
				if( $hit->thumbnailUrl ) {
					$result = self::appendSessionId( $hit->thumbnailUrl );
				}
			case 'preview' :
				if( $hit->previewUrl ) {
					$result = self::appendSessionId( $hit->previewUrl );
				}
			case 'native' :
			case 'placement' :
				if( $hit->originalUrl ) {
					$result = self::appendSessionId( $hit->originalUrl );
				}
		}
		return $result;
	}
	
	/**
	 * Appends ;jssesionid=[sessionId] to the given url
	 *
	 * @param string $url
	 * @return string url
	 */
	private static function appendSessionId($url)
	{
		require_once dirname(__FILE__) . '/ElvisSessionUtil.php';
		$jsessionId = ';jsessionid=' . ElvisSessionUtil::getSessionId();
		
		// Put ;jsessionid BEFORE querystring, otherwise it doesn't work
		$idx = strpos($url, '?');
		if ($idx === false) {
			$url .= $jsessionId;
		} else {					
			$url = substr($url, 0, $idx) . $jsessionId . substr($url, $idx);
		}
		return $url;
	}

	/**
	 * Extracts the mime type from the hit
	 *
	 * @param object $hit Elvis hit object
	 * @return string mime type
	 */
	public static function getMimeType($hit)
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		return MimeTypeHandler::filePath2MimeType($hit->metadata['filename']);
	}
	
	/**
	 * Returns true if caller is Content Station
	 *
	 * @returns boolean
	 */
	static public function isContentStation()
	{
		return ElvisUtils::isClient('content station');
	}
	
	/**
	 * Returns true if caller is Smart Connection
	 *
	 * @returns boolean
	 */
	static public function isSmartConnection()
	{
		return ElvisUtils::isClient('InDesign') || ElvisUtils::isClient('InCopy');
	}
	
	/**
	 * Returns true if caller is Smart Connection
	 *
	 * @returns boolean
	 */
	static public function isInDesignServer()
	{
		return ElvisUtils::isClient('InDesign Server');
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

	/**
	 * @param $serviceName
	 * @return string
	 */
	public static function getServiceUrl($serviceName)
	{
		return self::appendSessionId(ELVIS_URL . "/services/" . $serviceName);
	}
}
