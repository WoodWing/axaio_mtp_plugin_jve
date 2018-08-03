<?php
/****************************************************************************
   Copyright 2008-2013 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/**
 * @since 		v8.2.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

class GettyImages
{
	private $systemId = null;
	private $systemPassword = null;
	private $userName = null;
	private $userPassword = null;
	private $token = null;
	private $secureToken = null;
	private $firstEntry = 0;
	private $totalEntries = 0;
	private $itemStartNumber = 1;
	private $refinementOptions = null;
	private $callByContentStation = null;

	public function __construct()
	{
		if( defined( 'GETTYIMAGES_SYS_ID' ) ) {
    		$this->systemId = GETTYIMAGES_SYS_ID;
    	}
        if( defined( 'GETTYIMAGES_SYS_PWD' ) ) {
            $this->systemPassword = GETTYIMAGES_SYS_PWD;
        }
        if( defined( 'GETTYIMAGES_USER_NAME' ) ) {
        	$this->userName = GETTYIMAGES_USER_NAME;
        }
        if( defined( 'GETTYIMAGES_USER_PWD' ) ) {
        	$this->userPassword = GETTYIMAGES_USER_PWD;
        }

        $this->createSession();
        $this->callByContentStation = self::calledByContentStation();
	}

	/**
	 * Calling GettyImages Connect API to create session
	 *
	 */
	public function createSession()
	{
		// create array of data for request
		$createSessionArray = array(
			"RequestHeader" => array(
				"Token" => "",
				"CoordinationId" => ""
			),
			"CreateSessionRequestBody" => array(
				"SystemId" => $this->systemId,
				"SystemPassword" => $this->systemPassword,
				"UserName" => $this->userName,
				"UserPassword" => $this->userPassword,
				"RememberedUser" => "false"
			)
		);
		$endpoint = "https://connect.gettyimages.com/v1/session/CreateSession";
		$response = $this->callConnectApi( $createSessionArray, $endpoint );

		// evaluate for success response
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody());
			if ($body) {
				if( isset($body->CreateSessionResult) ) {
					$this->token = $body->CreateSessionResult->Token;
					$this->secureToken = $body->CreateSessionResult->SecureToken;
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'CreateSession API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages CreateSession failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'CreateSession API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages CreateSession failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		} else {
			LogHandler::Log('Getty', 'ERROR', 'CreateSession API, Status:"'.$response->getStatus().', Message:'.$response->getMessage().'"' );
			throw new BizException( '', 'Server', 'GettyImages CreateSession failed.', 'GettyImages error: '.$response->getMessage() );
		}
	}

	/**
	 * Calling GettyImages Connect API to renew session
	 *
	 */
	public function renewSession()
	{
		// create array of data for request
		$renewSessionArray = array(
			"RequestHeader" => array(
				"Token" => $this->token,
				"CoordinationId" => ""
			),
			"RenewTokenRequestBody" => array(
				"SystemId" => $this->systemId,
				"SystemPassword" => $this->systemPassword,
			)
		);
		$endpoint = "https://connect.gettyimages.com/v1/session/RenewSession";
		$response = $this->callConnectApi( $renewSessionArray, $endpoint );

		// evaluate for success response
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody()); // returns stdObject array
			if ($body) {
				if( isset($body->RenewSessionResult) ) {
					$this->token = $body->RenewSessionResult->Token;
					$this->secureToken = $body->RenewSessionResult->SecureToken;
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'RenewSession API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages RenewSession failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'RenewSession API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages RenewSession failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		} else {
			LogHandler::Log('Getty', 'ERROR', 'RenewSession API, Status:"'.$response->getStatus().', Message:'.$response->getMessage().'"' );
			throw new BizException( '', 'Server', 'GettyImages RenewSession failed.', 'GettyImages error: '.$response->getMessage() );
		}
	}

	/**
	 * Calling GettyImages Connect API to search images
	 *
	 * @param string $searchPhrase
	 * @param array $refinements Array of refinements filter
	 * @return array $images
	 */
	public function searchImages( $searchPhrase, $refinements )
	{
		// build array to query api for images
		$searchImagesArray = array (
			"RequestHeader" => array (
			"Token" => $this->token // Token received from a CreateSession/RenewSession API call
			),
			"SearchForImages2RequestBody" => array (
 				"Query" => array (
					"SearchPhrase" => $searchPhrase
 				),
	 			"Filter" => array(
	 				"Refinements" => $refinements,
	 			),
	 			"ResultOptions" => array (
					"IncludeKeywords" => "true",
 					"ItemCount" => DBMAXQUERY, // return no of items based on DBMAXQUERY set value
	 				"ItemStartNumber" => $this->itemStartNumber, // 1-based int, start on the 2nd page
 				)
			)
		);
		$endpoint = "http://connect.gettyimages.com/v1/search/SearchForImages";
		$response = $this->callConnectApi( $searchImagesArray, $endpoint );

		// evaluate for success response
		$images = array();
		$body = null;
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody()); // returns stdObject
			if ($body) {
				if( isset($body->SearchForImagesResult) ) {
					$this->itemStartNumber 	= intval($body->SearchForImagesResult->ItemStartNumber);
					$this->totalEntries 	= intval($body->SearchForImagesResult->ItemTotalCount);
					$this->refinementOptions= $body->SearchForImagesResult->RefinementOptions;
					// retrieves the image array of stdObjects
					$images = $body->SearchForImagesResult->Images;
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader->StatusList) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'SearchForImages API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages SearchForImages failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'SearchForImages API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages SearchForImages failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		} else {
			LogHandler::Log('Getty', 'ERROR', 'SearchForImages API, Status:"'.$response->getStatus().', Message:'.$response->getMessage().'"' );
			throw new BizException( '', 'Server', 'GettyImages SearchForImages failed.', 'GettyImages error: '.$response->getMessage() );
		}
		return $images;
	}

	/**
	 * Calling GettyImages Connect API to get image details
	 *
	 * @param string $imageId
	 * @return object $imageDetails
	 */
	public function getImageDetails( $imageId )
	{
		// build array to query api for images
		$imageDetailsArray = array (
			"RequestHeader" => array (
				"Token" => $this->token // Token received from a CreateSession/RenewSession API call
			),
			"GetImageDetailsRequestBody" => array (
 				"ImageIds" => array($imageId) // specify the image ID to get details from
 			)
		);
		$endpoint = "http://connect.gettyimages.com/v1/search/GetImageDetails";
		$response = $this->callConnectApi( $imageDetailsArray, $endpoint );

		// evaluate for success response
		$imageDetails = null;
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody()); // returns stdObject array
			if ($body) {
				if( isset($body->GetImageDetailsResult) ) {
					$imageDetails = $body->GetImageDetailsResult->Images[0];
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'GetImageDetails API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages GetImageDetails failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'GetImageDetails API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages GetImageDetails failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		} else {
			LogHandler::Log('Getty', 'ERROR', 'GetImageDetails API, Status:"'.$response->getStatus().', Message:'.$response->getMessage().'"' );
			throw new BizException( '', 'Server', 'GettyImages GetImageDetails failed.', 'GettyImages error: '.$response->getMessage() );
		}

		return $imageDetails;
	}

	/**
	 * Calling GettyImages Connect API to get event details
	 *
	 * @param string $eventId
	 * @return object $eventDetails
	 */
	private function getEventDetails( $eventId )
	{
		// build array to query api for images
		$eventDetailsArray = array (
			"RequestHeader" => array(
				"Token" => $this->token,
				"CoordinationId" => ""
			),
			"GetEventDetailsRequestBody" => array (
 				"EventIds" => array( $eventId ) // specify the event ID to get details from
 			)
		);
		$endpoint = 'http://connect.gettyimages.com/v1/search/GetEventDetails';
		$response = $this->callConnectApi( $eventDetailsArray, $endpoint );

		// evaluate for success response
		$eventDetails = null;
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody());
			if ($body) {
				if( isset($body->GetEventDetailsResult) ) {
					$eventDetails = $body->GetEventDetailsResult->EventResult[0];
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'GetEventDetails API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages GetEventDetails failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'GetEventDetails API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages GetEventDetails failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		} else {
			LogHandler::Log('Getty', 'ERROR', 'GetEventDetails API, Status:"'.$response->getStatus().', Message:'.$response->getMessage().'"' );
			throw new BizException( '', 'Server', 'GettyImages GetEventDetails failed.', 'GettyImages error: '.$response->getMessage() );
		}

		return $eventDetails;
	}

	/**
	 * Calling GettyImages Connect API to get image download authorizations
	 *
	 * @param string $imageId
	 * @param string $imageSizeKey
	 * @return object $authorizationObject
	 */
	private function getImageDownloadAuthorizations( $imageId, $imageSizeKey )
	{
		// build array to query api for images
		$imageAuthorizationArray = array (
			"RequestHeader" => array (
				"Token" => $this->token
			),
			"GetImageDownloadAuthorizationsRequestBody" => array (
		 		"ImageSizes" => array(array (
					"ImageId" => $imageId,
					"SizeKey" => $imageSizeKey
		 		))
			)
		);
		$endpoint = "http://connect.gettyimages.com/v1/download/GetImageDownloadAuthorizations";
		$response = $this->callConnectApi( $imageAuthorizationArray, $endpoint );

		// evaluate for success response, get the body of data returned for download authorization
		$body = null;
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody());
			if ($body) {
				if( isset($body->GetImageDownloadAuthorizationsResult) ) {
					$authorizationObject = $body->GetImageDownloadAuthorizationsResult->Images[0]->Authorizations[0];
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'GetImageDownloadAuthorizations API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages GetImageDownloadAuthorizations failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'GetImageDownloadAuthorizations API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages GetImageDownloadAuthorizations failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		}  else {
			LogHandler::Log('Getty', 'ERROR', 'GetImageDownloadAuthorizations API, Status:"'.$response->getStatus(). ', Message:' . $response->getMessage() . '"' );
			throw new BizException( '', 'Server', 'GettyImages GetImageDownloadAuthorizations failed.', 'GettyImages error: '.$response->getMessage() );
		}

		return $authorizationObject;
	}

	/**
	 * Calling GettyImages Connect API to get largest image download authorization
	 *
	 * @param string $imageId
	 * @return object $authorizationObject
	 */
	private function getLargestImageDownloadAuthorizations( $imageId )
	{
		// build array to query api for images
		$imageAuthorizationArray = array (
			"RequestHeader" => array (
				"Token" => $this->token
			),
			"GetLargestImageDownloadAuthorizationsRequestBody" => array (
 				"Images" => array( array( 'ImageId' => $imageId ) )
			)
		);
		$endpoint = "http://connect.gettyimages.com/v1/download/GetLargestImageDownloadAuthorizations";
		$response = $this->callConnectApi( $imageAuthorizationArray, $endpoint );

		// evaluate for success response, get the body of data returned for download authorization
		$authorizationObject = null;
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody());
			if ($body) {
				if( isset($body->GetLargestImageDownloadAuthorizationsResult) ) {
					$authorizationObject = $body->GetLargestImageDownloadAuthorizationsResult->Images[0]->Authorizations[0];
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'GetLargestImageDownloadAuthorizations API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages GetLargestImageDownloadAuthorizations failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'GetLargestImageDownloadAuthorizations API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages GetLargestImageDownloadAuthorizations failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		} else {
			LogHandler::Log('Getty', 'ERROR', 'GetLargestImageDownloadAuthorizations API, Status:"'.$response->getStatus(). ', Message:' . $response->getMessage() . '"' );
			throw new BizException( '', 'Server', 'GettyImages GetLargestImageDownloadAuthorizations failed.', 'GettyImages error: '.$response->getMessage() );
		}

		return $authorizationObject;
	}

	/**
	 * Calling GettyImages Connect API to create download request
	 *
	 * @param string $downloadToken
	 * @return string $url
	 */
	private function createDownloadRequest( $downloadToken )
	{
		// build array to query api for images
		$createDownloadArray = array (
			"RequestHeader" => array (
				"Token" => $this->secureToken // *NOTE: ensure a secure token is passed into this request
			),
			"CreateDownloadRequestBody" => array (
		 		"DownloadItems" => array(array (
					"DownloadToken" => $downloadToken
 				))
			)
		);
		$endpoint = "https://connect.gettyimages.com/v1/download/CreateDownloadRequest";
		$response = $this->callConnectApi( $createDownloadArray, $endpoint );

		// evaluate for success response
		$url = null;
		if ($response->isSuccessful()) {
			$body = json_decode($response->getBody()); // returns stdObject
			if ($body) {
				if( isset($body->CreateDownloadRequestResult) ) {
					$url = $body->CreateDownloadRequestResult->DownloadUrls[0]->UrlAttachment;
				} elseif( isset($body->ResponseHeader) ) {
					$errStatus = isset($body->ResponseHeader) ? $body->ResponseHeader->StatusList[0] : null;
					if( $errStatus ) {
						LogHandler::Log('Getty', 'ERROR', 'CreateDownloadRequest API '.$errStatus->Type.': "'.$errStatus->Code. ', '.$errStatus->Message.'"' );
						throw new BizException( '', 'Server', 'GettyImages CreateDownloadRequest failed.', 'GettyImages error: '.$errStatus->Message );
					} else {
						LogHandler::Log('Getty', 'ERROR', 'CreateDownloadRequest API Status: '. $body->ResponseHeader->Status );
						throw new BizException( '', 'Server', 'GettyImages CreateDownloadRequest failed.', 'GettyImages status: '.$body->ResponseHeader->Status );
					}
				}
			}
		} else {
			LogHandler::Log('Getty', 'ERROR', 'CreateDownloadRequest API, Status:"'.$response->getStatus().', Message:'.$response->getMessage().'"' );
			throw new BizException( '', 'Server', 'GettyImages CreateDownloadRequest failed.', 'GettyImages error: '.$response->getMessage() );
		}
		return $url;
	}

	/**
	 * Search images from Getty images
	 *
	 * @param array $searchParams Array of search params
	 * @param int $firstEntry The number of the firstentry of the page
	 * @param int $totalEntries Total result list numbers
	 * @param array $facets Array of facet
	 * @return array $rows Search result rows
	 */
    public function search( $searchParams, &$firstEntry, &$totalEntries, &$facets )
    {
    	$rows = array();
        $refinements = array();

        foreach( $searchParams as $searchParam ) {
        	if( $searchParam->Property == 'Search' ) {
        		$searchPhrase = $searchParam->Value;
        	} else {
        		$refinements[] = array( 'Category' => $searchParam->Property, 'Id' => $searchParam->Value );
        	}
        }

		if( empty($searchPhrase) ) {
        	return $rows;
        }

        $firstEntry++;
        $this->itemStartNumber = $firstEntry;

        // Perform search in Getty
		PerformanceProfiler::startProfile( 'Getty - SearchImages', 3 );
		$results = $this->searchImages( $searchPhrase, $refinements );
		PerformanceProfiler::stopProfile( 'Getty - SearchImages', 3 );

        $totalEntries 	= $this->totalEntries;
        $facets			= $this->getFacets();
        $publicationId  = $this->getFirstPubId();
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
        foreach ($results as $result) {
        	$row   = array();
        	$date  = DateTimeFunctions::parseJsonDate( $result->DateCreated, 'datetime' );
        	$row[] = GI_CONTENTSOURCEPREFIX.urlencode($result->ImageId);
        	$row[] = 'Image';
			$row[] = $result->Title;							
			$row[] = $result->Artist;
			$row[] = $date;
			$row[] = $result->Caption;
			$row[] = '';
	        if( $this->callByContentStation ) {
				$row[] = 'image/jpeg';
				$row[] = $publicationId;
				$row[] = 0; // Set IssueId = 0, to avoid error thrown in ContentStationOverruleCompatibility_WflNamedQuery plugin
				$row[] = $result->UrlThumb;
			}

			$rows[] = $row;
        }
  		return $rows;
    }

    /**
     * Get the first publication Id
     *
     * @return string $pubId
     */
    private function getFirstPubId()
    {
    	require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$userName 	= BizSession::getShortUserName();
		$pubs		= BizPublication::getPublications( $userName );
		$pubId		= $pubs[0]->Id; // Get first publication id
		return $pubId;
    }

	/**
	 * Get Getty Image file stream and metadata
	 * 
	 * @param string	$id				Image id
	 * @param string	$rendition		The request rendition
	 * @return object	$image			StdClass image object contain content and metadata
	 */
    public function getImage( $id, $rendition )
    {
    	$image = new stdClass();
		$url = null;
		$content = '';
		$imageDetail = null;

		/** Remark for now, the default is to get the comp free image instead of paid highres image.
		// First get the largset image from Getty, when it is not found, then get the layout composition size from ImageDetail 
		if( $rendition == 'native' ) {
			$url = $this->getLargestImage( $id );
			if( $url ) {
				PerformanceProfiler::startProfile( 'Getty - getImageDetails', 2 );
				$imageDetail = $this->getImageDetails( $id );
				PerformanceProfiler::stopProfile( 'Getty - getImageDetails', 2 );
			}
		} 

		if( !$url ) {
		*/
    		PerformanceProfiler::startProfile( 'Getty - getImageDetails', 2 );
			$imageDetail = $this->getImageDetails( $id );
			PerformanceProfiler::stopProfile( 'Getty - getImageDetails', 2 );
			if( $imageDetail ) {
				switch( $rendition ) {
					case 'native':
						$url = $imageDetail->UrlComp;	// Return UrlComp free image
						break;
					case 'thumb':
						if( $this->callByContentStation ) { // Return UrlThumb for CS, since no validation on the url
							$url = $imageDetail->UrlThumb;
						} else {
							$url = $imageDetail->UrlPreview; // Return UrlPreview for ID/IC, due to UrlThumb failed validation
						}
						break;
					case 'preview':
						$url = $imageDetail->UrlPreview;
						break;
				}
			}
		//}

		if( $url ) {
			$content = self::httpRequest( $url );
		}
		$metaData = $this->getMetaData( $id, $imageDetail );
		$image->Content = $content;
		$image->MetaData= $metaData;

		return $image;
    }

    /**
     * Get the largest image url
     * 
     * @param string $id Getty image id
     * @return string $url Url of the image
     */
    private function getLargestImage( $id )
    {
    	$url = null;
    	$authorizationObject = $this->getLargestImageDownloadAuthorizations( $id );
    	if( $authorizationObject ) {
    		$downloadToken = isset($authorizationObject->DownloadToken) ? $authorizationObject->DownloadToken : null;	
    		$url = $this->createDownloadRequest( $downloadToken );
    	}

    	return $url;
    }

	/**
	 * Perform get request
	 *
	 * @param string $url
	 * @return stream $retVal
	 */
	static final private function httpRequest( $url )
	{
		LogHandler::Log('Getty', 'DEBUG', "Getty request: $url");

    	$retVal = '';
		require_once 'Zend/Http/Client.php';
		try {
			$http = new Zend_Http_Client();
			$http->setUri( $url );

			PerformanceProfiler::startProfile( "Getty - $url", 2 );
			$response = $http->request( Zend_Http_Client::GET );
			PerformanceProfiler::stopProfile( "Getty - $url", 2 );

			if( $response->isSuccessful() ) {
				$retVal = $response->getBody();
			} else {
				self::handleHttpError( $response );
			}
		} catch (Zend_Http_Client_Exception $e) {
			throw new BizException( '', 'Server', 'GettyImages httpRequest failed.', 'GettyImages error: '.$e->getMessage() );
		}
		return $retVal;
	}

	/**
	 * Checks status and throws exception on communication errors.
	 * Assumed is that response is an error.
	 * 
	 * @param Zend_Http_Response $response
	 * @throws BizException on error
	 */
	static private function handleHttpError( $response )
	{
		$responseHeaders = $response->getHeaders();
		$contentType = $responseHeaders['Content-type'];
		$respBody = $response->getBody();
		$respStatusCode = $response->getStatus();
		$respStatusText = $response->responseCodeAsText( $respStatusCode );
		
		if( $contentType == 'text/html' && $respStatusCode == 500 && ($msg = self::getErrorFromHtmlPage($respBody)) ) {
			$msg = 'GettyImages error: '.$msg;
	   	    LogHandler::Log('Getty', 'ERROR',  $respBody ); // dump entire HTML page
			throw new BizException( '', 'Server', $msg, $msg );
    	}
		$msg = "GettyImages connection problem: $respStatusText (HTTP code: $respStatusCode)";
		LogHandler::Log('Getty', 'ERROR',  $respBody ); // dump entire HTML page
		throw new BizException( '', 'Server', $msg, $msg );
	}

	/**
	 * Get Image metadata from image detail object
	 * 
	 * @param string	$id				Image id
	 * @param object	$imageDetail	Image details object
	 * @return array	$metadata		Array of metadata
	 */
	private function getMetaData( $id, $imageDetail )
    {
    	require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		$metadata = array();
		$keywords = '';

		$metadata['Id']			= (string) $id;
		$metadata['Type']		= 'Image';
		$metadata['Name']		= $imageDetail->Title;
		$metadata['Caption']	= $imageDetail->Caption;
    	$metadata['Created']	= DateTimeFunctions::parseJsonDate( $imageDetail->DateCreated, 'datetime' );
	   	$metadata['Format']		= 'image/jpeg';
	   	$metadata['Author']		= $imageDetail->Artist;
	   	$metadata['Copyright']	= $imageDetail->Copyright;
	   	$metadata['Credit']		= $imageDetail->CreditLine;
	   	$metadata['Source']		= $imageDetail->EditorialSourceName;
	   	foreach( $imageDetail->Keywords as $keyword ) {
	   		$keywords .= $keyword->Text . ',';
	   	}
	   	$metadata['Keywords']	= substr($keywords, 0, strlen($keywords) - 1); // Remove the last ','
	   	
	   	$sDangerousCharacters	= array('`', '~', '!', '@', '#', '$', '%', '^', '*', '|', '<', '>', '/', '?', '"', ':', '\\', '\'', ';');
	   	$metadata['Name'] 		= str_replace( $sDangerousCharacters, '', $metadata['Name'] );
	   	$metadata['Name'] 		= substr( $metadata['Name'], 0, 63 );
		
		return $metadata;
    }

    /**
     * Get query columns
     *
     * @return array $cols Array of columns
     */
	static final public function getColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 			'ID', 			'string' 	); // Required as 1st
		$cols[] = new Property( 'Type', 		'Type', 		'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name',			'Name', 		'string' 	); 
		$cols[] = new Property( 'Author',		'Author', 		'string' 	);
		$cols[] = new Property( 'Created',  	'Created', 		'datetime'	);
		$cols[] = new Property( 'Caption',  	'Caption', 		'string'	);
		$cols[] = new property( 'Slugline',		'Slugline',		'string'	);

        if( self::calledByContentStation() ) {
			$cols[] = new Property( 'Format', 		'Format', 		'string' 	);	// Required by Content Station
    	    $cols[] = new Property( 'PublicationId','PublicationId','string' 	);	// Required by Content Station
    	    $cols[] = new Property( 'IssueId',		'IssueId',		'string' 	);	// Required by Content Station
	        $cols[] = new Property( 'thumbUrl',		'thumbUrl',		'string' 	);	// Thumb URL for Content Station
	    }

		return $cols;
	}

	/**
	 * Get the facets based on refinementoptions from GettyImages search result list
	 *
	 * @return $facets Array of facet
	 */
	private function getFacets()
	{
		$facets = array();
		$excludeRefinements = unserialize(EXCLUDE_REFINEMENTS);

		foreach( $this->refinementOptions as $refinementOption ) {
			if( !in_array($refinementOption->Category, $excludeRefinements) ) {
				if( $refinementOption->Category == 'Event' ) {
					$eventDetails = $this->getEventDetails( $refinementOption->Id );
					$refinementOption->Text = $eventDetails->Event->EventName;
				}
				if( isset($facets[$refinementOption->Category]) ) {
					$facetItem = new FacetItem($refinementOption->Id, $refinementOption->Text, $refinementOption->ImageCount );
					$facets[$refinementOption->Category]->FacetItems[] = $facetItem;
				} else {
					$facet = new Facet( $refinementOption->Category, $refinementOption->Category );
					$facetItem = new FacetItem($refinementOption->Id, $refinementOption->Text, $refinementOption->ImageCount );
					$facet->FacetItems[] = $facetItem;
					$facets[$refinementOption->Category] = $facet;
				}
			}
		}
		return $facets;
	}

	/**
	 * Check whether caller is Content Station
	 * 
	 *
	 * Returns true if the client is Content Station
	 */
    static public function calledByContentStation()
    {
		$app = BizSession::getClientName();
		return stristr($app, 'Content Station');
    }

    /**
     * Call GettyImages Connect API
     *
     * @param array $jsonArray
     * @param string $endpoint
     * @return object $response
     */
    private function callConnectApi( $jsonArray, $endpoint )
    {
    	// encode to json
		$json = json_encode($jsonArray);

		// create client and set json data and datatype
		require_once 'Zend/Http/Client.php';
		$httpClient = new Zend_Http_Client($endpoint);
		$httpClient->setRawData($json, 'application/json');
		$httpClient->setMethod(Zend_Http_Client::POST); // all getty api requests are POST

		// returns Zend_Http_Response
		$response = $httpClient->request();

		return $response;
    }
}