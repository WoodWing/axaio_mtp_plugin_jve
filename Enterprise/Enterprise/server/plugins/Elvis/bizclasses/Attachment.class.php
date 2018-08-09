<?php

/**
 * File handler for Elvis shadow objects.
 *
 * @since      10.5.0 Class functions originate from util/ElvisUtils.class.php
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_Attachment
{
	/**
	 * Extract corresponding URL to Elvis asset, based on provided rendition.
	 *
	 * If version specified, it will be taken into account
	 * Extracted url, if could be resolved then used to copy file to Enterprise Transfer server.
	 *
	 * While copying, the Elvis session id is sent through cookies to authorize the download URL.
	 * This is done through input stream options.
	 *
	 * @param Elvis_DataClasses_EntHit $hit Elvis search result (where the URL can be extracted from).
	 * @param string $rendition File rendition to download.
	 * @param string $fileLinkType 'FileUrl', 'ContentSourceFileLink' or 'ContentSourceProxyLink'
	 * @return Attachment|null The file attachment, or null when not found.
	 * @throws BizException
	 */
	public static function getAttachment( Elvis_DataClasses_EntHit $hit, string $rendition, string $fileLinkType )
	{
		$attachment = null;
		$url = self::getUrlFromRendition( $hit, $rendition );
		if( $url ) {
			$type = self::getMimeType( $hit, $url, $rendition );
			if( $type ) {
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
							$proxyUrl = self::composePrivateProxyFileDownloadUrlForAsset( $hit->metadata['id'], $rendition,
								$hit->metadata['versionNumber'], BizSession::getTicket() );
							//$proxyUrl .= '&XDEBUG_SESSION_START=PHPSTORM'; // uncomment to debug the proxy server
							$httpStatus = $transferServer->copyToFileTransferServer( $proxyUrl, $attachment );
							$retry = self::retryCopyToFileTransferServer( $attempt, $httpStatus );
						} while( $retry );
						if( intval( $httpStatus ) >= 500 ) {
							throw new Elvis_BizClasses_ClientException( 'Failed to copy '.$rendition.
								' file from Elvis server to Transfer Server folder.', 'ERROR' );
						}
						if( intval( $httpStatus ) >= 400 || $httpStatus === false ) { // false: simulate ES < 10.1.4 behaviour
							throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
								null, null, array( '{RENDITION}', $rendition ) );
						}
						break;
					case 'ContentSourceFileLink': // let client directly download from Elvis
						// EN-88634: We no longer add our jsessionid to the ContentSourceFileLink URL; This URL is requested by
						// SC which passes on the URL to the Elvis InDesign plugin. This plugin has its own session with Elvis
						// and so it has its own authorization.
						$attachment->ContentSourceFileLink = self::replaceUrlForClientsAccess( $url );
						break;
					case 'ContentSourceProxyLink': // let client download from our Elvis proxy server
						$objectId = isset( $hit->metadata['sceId'] ) ? $hit->metadata['sceId'] : null;
						if( $objectId ) {
							$attachment->ContentSourceProxyLink = self::composePublicProxyFileDownloadUrl( $objectId, $rendition );
						} else {
							throw new BizException( 'ERR_ARGUMENT', 'Client',
								'Request for ContentSourceProxyLink is not supported in Hard_Copy_To_Enterprise mode.' );
						}
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
	 * @param string $assetId
	 * @param string $rendition File rendition to download.
	 * @param string $version
	 * @param string $ticket
	 * @return string The proxy file download URL.
	 */
	private static function composePrivateProxyFileDownloadUrlForAsset( $assetId, $rendition, $version, $ticket )
	{
		return ELVIS_CONTENTSOURCE_PRIVATE_PROXYURL.
			'?assetid='.urlencode( $assetId ).
			'&rendition='.urlencode( $rendition ).
			'&assetversion='.urlencode( $version ).
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
	 * Returns a url from a Elvis hit given a specific rendition.
	 *
	 * @param Elvis_DataClasses_EntHit $hit
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
				} else {
					$result = ELVIS_CLIENT_URL.'/thumbnail/'.rawurlencode( $hit->metadata['id'] ).'/*/'.rawurlencode( $hit->metadata['name'] ).'?v='.intval( $hit->metadata['versionNumber'] );
				}
				break;
			case 'preview' :
				if( $hit->previewUrl ) {
					$result = $hit->previewUrl;
				} else {
					$result = ELVIS_CLIENT_URL.'/preview/'.rawurlencode( $hit->metadata['id'] ).'/*/'.rawurlencode( $hit->metadata['name'] ).'?v='.intval( $hit->metadata['versionNumber'] );
				}
				break;
			case 'native' :
			case 'placement' :
				if( $hit->originalUrl ) {
					$result = $hit->originalUrl;
				} else {
					$result = ELVIS_CLIENT_URL.'/file/'.rawurlencode( $hit->metadata['id'] ).'/*/'.rawurlencode( $hit->metadata['name'] ).'?v='.intval( $hit->metadata['versionNumber'] );
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
	 * @param Elvis_DataClasses_EntHit $hit
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
}