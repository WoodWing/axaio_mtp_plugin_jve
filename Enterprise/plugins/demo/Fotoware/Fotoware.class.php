<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

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
 * @since 		v6.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
**/

class Fotoware
{     
	/**
	 * getArchives
	 *
	 * Returns Fotoware archives that are available for searching
	 */
    static public function getArchives()
    {
		$xml = self::requestArchives();
       	if( !$xml ) return null;
    	
        foreach ($xml->Archive as $archive) {   
             $attrs = $archive->attributes();
             $archives[] =  (string)$attrs['Name'];
        }
       	 
       	return $archives;
    }    
        
	/**
	 * getQueryColumns
	 *
	 * Columns as returned by runQuery
	 */
	static public function getQueryColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 		'ID', 		'string' 		); // Required as 1st
		$cols[] = new Property( 'Type', 	'Type', 	'string' 		); // Required as 2nd
		$cols[] = new Property( 'Name', 	'Name', 	'string' 		); // Required as 3rd
		
		$cols[] = new Property( 'Modified',  	'Modified', 		'datetime'	);
		$cols[] = new Property( 'Slugline', 	'Description', 		'string' 	);
		$cols[] = new Property( 'FileSize', 	'Size', 			'int' 		);
		$cols[] = new Property( 'Created',  	'Created', 			'datetime'	);
		$cols[] = new Property( 'Format', 		'Format', 			'string' 	);
        $cols[] = new Property( 'PublicationId','PublicationId',	'string' 	);	// Required by Content Station
        $cols[] = new Property( 'IssueId',		'IssueId',			'string' 	);	// Required by Content Station
		return $cols;
	}
    
	/**
	 * runQuery
	 *
	 * Execute Fotoware query and return query result rows
	 * @throws BizException on error
	 */
    static public function runQuery( $archive, $keyword )
    {
        LogHandler::Log('Fotoware', 'DEBUG', "runQuery: $archive $keyword");
        $rows     = array();
        if( empty($keyword) ) {  // Fotoware does not accept empty keyword, so return empty array in that case
            $msg = 'Please enter a keyword to search for';
			throw new BizException( '', 'Server', $msg, $msg );
        }
        
        // Perform search in Fotoware
        $archiveId ='';
		$xml = self::requestSearch( $archive, $keyword, $archiveId );
        
		foreach ($xml->File as $file) {
			$row   = array();
			
			$metadata = self::getMetaData( $file, $archiveId );

			$row[] = FOTOWARE_CONTENTSOURCEPREFIX.$metadata['Id'];	// ID: _FW_<ArchiveID>+<FWid>
			$row[] = $metadata['Type'];								// Type
			$row[] = $metadata['Name'];								// Name
			$row[] = $metadata['Modified'];
			$row[] = array_key_exists('Headline',$metadata) ? $metadata['Headline'] : '';
			$row[] = $metadata['FileSize'];
			$row[] = $metadata['Created'];
			$row[] = $metadata['Format'];
			$row[] = 1;// PublicationId
			$row[] = 1;// IssueId
			
			$rows[] = $row;
		}
  		return $rows;
    }
    
	/**
	 * Retrieves a file (and metadata) from Fotoware server.
	 * 
	 * @param $file string Fotoware document id (alien id)
	 * @param $rendition string 'preview', 'thumb' or 'native'
	 * @param $content string The file content
	 * @return array with meta data (key-value pairs)
	 * @throws BizException on error
	 */
     static public function getFile( $file, $rendition, &$content )
     {
        LogHandler::Log('Fotoware', 'DEBUG', "getFile: $rendition of $file" );
        // Split id into archive and asset id:
        $alienInfo = explode( '+', $file );
        $archive 	= $alienInfo[0];
        $id 		= $alienInfo[1];

		$previewSize = 0;
		if ($rendition == 'preview' || $rendition == 'thumb' ) {
			$previewSize = $rendition=='preview' ? 600 : 100;
		} else if ($rendition == 'native' ) {
			$params = array( 'Id' => $id );
			// Download requires logon; Add username & password to request
    		if( strlen(FOTOWARE_USERNAME) ){
    			$params['username'] = FOTOWARE_USERNAME;
	    		$params['password'] = FOTOWARE_PASSWORD;
    		}
			$content = self::httpRequest( FOTOWARE_FULLURL . $archive . '/Download', $params, 'image/jpeg' );
		}

		$xml = self::requestFileInfo( $archive, $id, $previewSize ); 
		$file = $xml->File;		
		if( $previewSize > 0 ) {
			$previewURL = (string)$file->PreviewLinks->PreviewUrl;
			$content = self::httpRequest( $previewURL, array(), 'image/jpeg' );
		}
		return self::getMetaData( $file, $archive );
     }


    // -------------------------
    // - PRIVATE METHODS
    // -------------------------
    static private function getIdFromArchives( $name)
    {
    	$xml = self::requestArchives();
       	if( !$xml ) return null;
    
        foreach ($xml->Archive as $archive) {   
             $attrs = $archive->attributes();
             if( (string)$attrs['Name'] == $name ) {   
                 return (string)$attrs['Id'];
             }
        }
       	return null;
    }

    static private function getMetaData( $file, $archiveId )
    {
    	$metadata = array();

		$attrs = $file->attributes();

		$objName = (string)$attrs['Name'];
		// remove dangerous characters to prevent problems when creating object
		$sDangerousCharacters = "`~!@#$%^*\\|;:'<>/?\"";
		$objName = ereg_replace("[$sDangerousCharacters]", "", $objName);
		// limit objname length on this point to 27 -9 = 18 chars
		$objName = substr( $objName,0,27);		
		$metadata['Id']			= $archiveId.'+'.(string)$attrs['Id'];
		$metadata['Name']		= $objName;

		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$mimeType = (string)$file->FileInfo->MimeType;
		$metadata['Type']	= MimeTypeHandler::filename2ObjType( $mimeType, $metadata['Name'], false );

    	$metadata['Created']	= self::convertDate((string)$file->FileInfo->Created);
    	$metadata['Modified']	= self::convertDate((string)$file->FileInfo->LastModified);
		$metadata['FileSize']		= (string)$file->FileInfo->FileSize;
    	$metadata['Format']		= $mimeType;
    	
    	$metadata['Width']		= (string)$file->MetaData->PixelWidth;
    	$metadata['Height']		= (string)$file->MetaData->PixelHeight;
    	$metadata['Dpi']		= (string)$file->MetaData->Resolution;
    	$metadata['ColorSpace']	= (string)$file->MetaData->ColorSpace;

		// Get the Text fields:		
		foreach ($file->MetaData->Text->Field as $field) {
			$attrs = $field->attributes();
			$metadata[(string)$attrs['Name']] = (string)$field;
		}
		return $metadata;
    }
    
	/**
	 * @throws BizException on error
	 */
    static private function httpRequestXML( $url, $params )
    {
    	$xmlString = self::httpRequest( FOTOWARE_FULLURL . $url, $params, 'text/xml' );

        // remove possible 'Content-type: text/xml
        $xmlString = substr( $xmlString, strpos( $xmlString, '<' ) );
        
        Try {
            $xml  = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
        }  
        catch (Exception $e) {
            LogHandler::Log('Fotoware', 'ERROR','Caught exception: ',  $e);
            $msg = "Fotoware returned invalid data: $e";
			throw new BizException( '', 'Server', $msg, $msg );
        }
        if( !$xml ) {
            $msg = "Invalid Fotoware response: $xmlString";
            LogHandler::Log( 'Fotoware', 'ERROR', $msg );
			throw new BizException( '', 'Server', $msg, $msg );
        }
        
        return $xml;
	}

	/**
	 * @throws BizException on error
	 */
	static private function httpRequest( $url, $params, $expContentType )
	{
		$urlParams = http_build_query( $params );
		LogHandler::Log('Fotoware', 'DEBUG', "Fotoware request: $url?$urlParams");

    	$retVal = '';
		require_once 'Zend/Http/Client.php';
		try {
			$http = new Zend_Http_Client();
			$http->setUri( $url );
			foreach( $params as $parKey => $parValue ) {
				$http->setParameterGet( $parKey, $parValue );
			}

			if( FOTOWARE_PROXY != '' ) {
				$config = array( 'http' => array( 'proxy' => FOTOWARE_PROXY ) );
				$http->setConfig( $config );
			}
			
			$response = $http->request( Zend_Http_Client::POST );
			if( $response->isSuccessful() ) {
				$gotContentType = self::getContentType( $response );
				if( $gotContentType == $expContentType ||
					($gotContentType == '' && $expContentType == 'image/jpeg') ) { // asking for native, Fotoware returns empty type
					$retVal = $response->getBody();
				} else { // error on unhandled content
					if( $gotContentType == 'text/html' && $expContentType == 'image/jpeg' ) {
						$respBody = $response->getBody();
						$msg = self::getErrorFromHtmlPage( $respBody );
						$msg = 'Fotoware error: '.$msg;
				   	    LogHandler::Log('Fotoware', 'ERROR',  $respBody ); // dump entire HTML page
						throw new BizException( '', 'Server', $msg, $msg );
					} else {
						$msg = "Unexpected content type. Received: $gotContentType. Expected: $expContentType.";
						LogHandler::Log('Fotoware', 'ERROR', $msg .'. First 100 bytes: '. substr( $response->getBody(), 0, 100) );
						throw new BizException( '', 'Server', $msg, $msg );
					}
				}
			} else {
				self::handleHttpError( $response );
			}
		} catch (Zend_Http_Client_Exception $e) {
			throw new BizException( '', 'Server', 'Fotoware::httpRequest failed.', 'Fotoware error: '.$e->getMessage() );
		}
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
		
		if( $contentType == 'text/html' && 
   			$respStatusCode == 500 && 
  			($msg = self::getErrorFromHtmlPage($respBody)) ) {
			$msg = 'Fotoware error: '.$msg;
	   	    LogHandler::Log('Fotoware', 'ERROR',  $respBody ); // dump entire HTML page
			throw new BizException( '', 'Server', $msg, $msg );
    	}
		$msg = "Fotoware connection problem: $respStatusText (HTTP code: $respStatusCode)";
		LogHandler::Log('Fotoware', 'ERROR',  $respBody ); // dump entire HTML page
		throw new BizException( '', 'Server', $msg, $msg );
	}
	
	/**
	 * Assumes that the given response is an HTML page describing an error. 
	 * It takes out an header or title in the hope it tells us the error details.
	 * This typically happens when parameters are missing (programming errors).
	 * 
	 * @param $respBody string HTML page
	 * @return string The error details. Empty when none found.
 	 */
	static private function getErrorFromHtmlPage( $respBody )
	{
	   	$htmDoc = new DOMDocument();
       	$htmDoc->loadHTML( $respBody );
    	$xpath = new DOMXPath($htmDoc);
		$msgs = $xpath->query('//body/h2/text()'); // try h2 (most detailed)
		$msg = $msgs->length > 0 ? trim($msgs->item(0)->textContent) : '';
		if( empty($msg) ) { // try h1
			$msgs = $xpath->query('//body/h1/text()');
			$msg = $msgs->length > 0 ? trim($msgs->item(0)->textContent) : '';
		}
		if( empty($msg) ) { // try title
			$msgs = $xpath->query('//head/title/text()');
			$msg = $msgs->length > 0 ? trim($msgs->item(0)->textContent) : '';
		}
		return $msg;
	}

	/**
	 * @throws BizException on error
	 */
	static private function requestArchives( ) 
	{
		return self::httpRequestXML( '', array() ); // empty request gets archives
	}

	/**
	 * @throws BizException on error
	 */
	static private function requestSearch( $archive, $keyword, &$archiveId ) 
	{
        $archiveId = self::getIdFromArchives( $archive );
		return self::httpRequestXML( $archiveId . '/Search', array( 'Search' => $keyword ) );
	}

	/**
	 * @throws BizException on error
	 */
	static private function requestFileInfo( $archiveId, $id, $maxSize ) 
	{
		$params = array( 'Id' => $id );
		if( $maxSize ) { // Only ask for preview if we need it
			$params['PreviewSize'] = $maxSize;
		}
		return self::httpRequestXML( $archiveId . '/FileInfo', $params );
	}

	/**
	 * convertDate
	 *
	 * Convert Fotoware date (format like Thu, 04 Oct 2007 19:08:04 GMT) 
	 * into standard data string (like 2007-10-04T19:08:04)
	 */
    static private function convertDate( $date)
    {
		$datesplit = explode( ' ' ,$date);
		
		$month = $datesplit[2];
		$day   = $datesplit[1];
		switch ($month) {
		  case "Jan": $monthnr="01";break;
		  case "Feb": $monthnr="02";break;
		  case "Mar": $monthnr="03";break;
		  case "Apr": $monthnr="04";break;
		  case "May": $monthnr="05";break;
		  case "Jun": $monthnr="06";break;
		  case "Jul": $monthnr="07";break;
		  case "Aug": $monthnr="08";break;
		  case "Sep": $monthnr="09";break;
		  case "Oct": $monthnr="10";break;
		  case "Nov": $monthnr="11";break;
		  case "Dec": $monthnr="12";break;
		}
		
		$stdDate = $datesplit[3].'-'.$monthnr.'-'.$day.'T'.$datesplit[4];
		return $stdDate;
    }
}
