<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

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
 * @package 	Enterprise
 * @subpackage 	ServerPlugin
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Facade to Tika Server for plain content extraction.
 *
**/

class TikaServerProxy
{
	/**
	 * Requests Tika Server to extract plain content from file ($buffer).
	 *
	 * @param string $fileContents File contents (typically formatted / rich text)
	 * @return Plain content. Emtpy when none. Null on error.
	 */
	static public function extractPlainContent( $fileContents )
	{
		require_once dirname(__FILE__) . '/config.php';
		require_once 'Zend/Http/Client.php';
		
		PerformanceProfiler::startProfile( 'Calling Tika Server', 3 );

		$url = TIKA_SERVER_URL.'getplaintext.php';
		LogHandler::Log('Tika', 'DEBUG', 'Calling Tika Server for content extraction through URL: ['. $url .']' );

    	$retVal = null;
		try {
			$http = new Zend_Http_Client();
			$http->setUri( $url );
			$http->setFileUpload( 'file.txt', 'upload', $fileContents );
			$http->setConfig( array( 'timeout' => TIKA_CONNECTION_TIMEOUT) );
			$response = $http->request( Zend_Http_Client::POST );
			if( $response->isSuccessful() ) {
				$gotContentType = self::getContentType( $response );
				$expContentType = 'text/xml';
				if($gotContentType == $expContentType) {
					$xmlDoc = new DOMDocument();
					$xmlDoc->loadXML( $response->getBody() );
					$xpath = new DOMXPath( $xmlDoc );
					$entries = $xpath->query( '/File/plaincontent' );
					if( $entries->length > 0 ) {
						$retVal = $entries->item(0)->nodeValue;
					} else {
						LogHandler::Log('Tika', 'ERROR', 'Failed retrieving plain content from XML response: '.$xmlDoc->saveXML() );
					}
				}
				else {
					$msg = "Unexpected content type. Received: $gotContentType. Expected: $expContentType.";
					LogHandler::Log('Tika', 'ERROR', $msg .'. First 100 bytes: '. substr( $response->getBody(), 0, 100) );
				}
			} else {
				self::logHttpError( $response );
			}
		} catch (Zend_Http_Client_Exception $e) {
			LogHandler::Log('Tika', 'ERROR', 'Tika::httpRequest failed: '.$e->getMessage() );
		}

		PerformanceProfiler::stopProfile( 'Calling Tika Server', 3 );
		return $retVal;
	}

	/**
	 * Returns the content-type paramters of the given http response.
	 *
	 * @param Zend_Http_Response $response
	 * @return string The content type
	 */
	static private function getContentType( $response )
	{
		$responseHeaders = $response->getHeaders();
		$contentType = $responseHeaders['Content-type'];
		// Strip other params that might follow, like "charset: windows-1252"
		$chuncks = explode( ';', $contentType );
		return $chuncks[0]; 
	}
	
	/**
	 * Logs communication errors.
	 * Assumed is that response is an error.
	 * 
	 * @param Zend_Http_Response $response
	 */
	static private function logHttpError( $response )
	{
		$responseHeaders = $response->getHeaders();
		$contentType = $responseHeaders['Content-type'];
		$respBody = $response->getBody();
		$respStatusCode = $response->getStatus();
		$respStatusText = $response->responseCodeAsText( $respStatusCode );
		
		$msg = "Tika Server error: $respStatusText (HTTP code: $respStatusCode)";
		LogHandler::Log('Tika', 'ERROR',  $respBody );
	}
}
