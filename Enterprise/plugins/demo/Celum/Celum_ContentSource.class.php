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

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';

require_once dirname(__FILE__) . '/config.php';
class Celum_ContentSource extends ContentSource_EnterpriseConnector
{
	private $pubId = null;

	final public function getContentSourceId( )
	{
		return CELUM_CONTENTSOURCEID;
	}
	
	final public function getQueries( )
	{
		$queries = array();
		
		$queryParamSearch = new PropertyInfo( 
			'Search', 'Search', // Name, Display Name
			null,				// Category, not used
			'string',			// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			'',					// Default value
			null,				// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
			);
	
		$queries[] = new NamedQueryType( CELUM_QUERY_NAME, array($queryParamSearch) );

		return $queries;
	}
	
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// keep code analyzer happy for unused params:
		// maxEntries is ignored because the API just returns 10 or 20 entries per call
		$maxEntries=$maxEntries; $order=$order;
		
		LogHandler::Log('Celum', 'DEBUG', 'doNamedQuery called for search: '.$query );
		PerformanceProfiler::startProfile( 'Celum - Search', 3 );

		// Create array with column definitions
		$cols = $this->getSearchColumns();
	
		// Call Celum API
		$totalEntries = $maxEntries;
		$rows = $this->doSearch( $params, $firstEntry, $totalEntries, $order );

		PerformanceProfiler::stopProfile( 'Celum - Search', 3 );
		
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, $firstEntry, count($rows), $totalEntries, null, null );
	}
	
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		LogHandler::Log('Celum', 'DEBUG', "getAlienObject called for $alienID - $rendition" );
		PerformanceProfiler::startProfile( 'Celum - getAlienObject', 3 );
		$alienID = substr( $alienID, strlen(CELUM_CONTENTSOURCEPREFIX) ); // Remove prefix from alienID
		$lock=$lock ; // we don't use this argument, keep analyzer happy

		$meta = new MetaData();

		// get meta data
		$response = $this->celumRequest( "documents.api?command=getDocument&id=$alienID&includeImageProperties=true", 'Get Documents' );
		$xml = new SimpleXMLElement($response);
		$files = array();		
		// Get file if needed:
		if( $rendition != 'none' ) {
			switch( $rendition ) {
				case 'thumb':
					$binaryCelumType = 'thmb';
					break;
				case 'preview':
					$binaryCelumType = 'prvw';
					break;
				case 'native':
					$binaryCelumType = 'orig';
					break;
			}
			$data = $this->celumRequest( "download.api?documentId=$alienID&binaryType=$binaryCelumType", 'Download request' );
			require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
			$attachment = new Attachment($rendition, 'image/jpeg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer( $data, $attachment);
			$files[] = $attachment;
		}
			
		$created 	= 	str_replace( ' ', 'T', ''.$xml->uploadDate );
		$modified	=	str_replace( ' ', 'T', ''.$xml->lastModified );
		$this->fillMetaData( $alienID, $meta, $xml->name, $created, $modified, ''.$xml->latestVersion, ''.$xml->fileExtension, ''.$xml->storageSize, ''.$xml->imageProperties->dpiY, ''.$xml->imageProperties->width, ''.$xml->imageProperties->height, ''.$xml->imageProperties->colorSpace );
	
		$object = new Object( 	$meta,				// meta data
								array(), null,		// relations, pages
								$files, 			// Files array of attachment
								null, null, null );	// messages, elements, targets

		PerformanceProfiler::stopProfile( 'Celum - getAlienObject', 3 );
		return $object;
	}
	
	final public function deleteAlienObject( $alienID )
	{
		$alienID = $alienID; // Make analyzer happy
		$msg = "Cannot delete documents from Celum";
		throw new BizException( 'ERR_AUTHORIZATION', 'Server', $msg, $msg );
	}

	/**
	 * createShadowObject
	 * 
	 * Create object record in Enterprise
	 *
	 * @param string	$id 		Alien object id, so include the _<ContentSourceID>_ prefix
	 * 
	 * @return Object
	 */
	final public function createShadowObject( $alienID, $destObject )
	{
		LogHandler::Log('Celum', 'DEBUG', "createShadowObject called for $alienID" );
		PerformanceProfiler::startProfile( 'Celum - createShadowObject', 3 );

		$alienID = substr( $alienID, strlen(CELUM_CONTENTSOURCEPREFIX) ); // Remove prefix from alienID

		// get meta data
		$response = $this->celumRequest( "documents.api?command=getDocument&id=$alienID&includeImageProperties=true", 'Get Documents' );
		$xml = new SimpleXMLElement($response);
		$created 	= 	str_replace( ' ', 'T', ''.$xml->uploadDate );
		$modified	=	str_replace( ' ', 'T', ''.$xml->lastModified );

		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$format	= MimeTypeHandler::fileExt2MimeType( '.'.$xml->fileExtension );

		$data = $this->celumRequest( "download.api?documentId=$alienID&binaryType=orig", 'Download request' );

		require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
		$attachment = new Attachment('native', $format);
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer($data, $attachment);
		$files = array( $attachment );

		// In case of a copy the user already filled in an object, we use that
		// For a real-life content source this would be further filled with metadata from the content source
		if( $destObject ) {
			$meta = $destObject->MetaData;
		} else {
			$meta = new MetaData();
			$destObject = new Object( $meta, array(), null, null, null, null, null );
		}
		$this->fillMetaData( $alienID, $meta, $xml->name, $created, $modified, ''.$xml->latestVersion, ''.$xml->fileExtension, ''.$xml->storageSize, ''.$xml->imageProperties->dpiY, ''.$xml->imageProperties->width, ''.$xml->imageProperties->height, ''.$xml->imageProperties->colorSpace );
		$destObject->Files = $files;
		PerformanceProfiler::stopProfile( 'Celum - createShadowObject', 3 );
		return $destObject;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - 
	// Below private implementation methods:
	// - - - - - - - - - - - - - - - - - - - - - - 
		
	private function getSearchColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 				'ID', 				'string' 	); // Required as 1st
		$cols[] = new Property( 'Type', 			'Type', 			'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name', 			'Name', 			'string' 	); // Required as 3rd
		$cols[] = new Property( 'Modified',	 		'Modified', 		'datetime'	);
		$cols[] = new Property( 'FileSize',			'Size',				'int'		);
		$cols[] = new Property( 'Score',			'Score',			'double'	);
		$cols[] = new Property( 'Uploaded',	 		'Uploaded', 		'datetime'	);
        if( self::calledByContentStation() ) {
			$cols[] = new Property( 'Format', 		'Format', 			'string' 	);	// Required by Content Station
    	    $cols[] = new Property( 'PublicationId','PublicationId',	'string' 	);	// Required by Content Station
	        $cols[] = new Property( 'IssueId',		'IssueId',			'string' 	);	// Required by Content Station
	        $cols[] = new Property( 'thumbUrl',		'thumbUrl',			'string' 	);	// Thumb URL for Content Station
	    }
		return $cols;
	}
	
	private function doSearch( $params, &$firstEntry, &$totalEntries, $order )
	{
		$calledByContentStation = $this->calledByContentStation();
		$rows = array();
		$search = urlencode($params[0]->Value);

		$orderCmd ='';
		if( $order && count($order) > 0 ) {
			switch( $order[0]->Property ) {
				case 'Name':
					$orderCmd = '&drf.orderBy=1';
					break;
				case 'FileSize':
					$orderCmd = '&drf.orderBy=2';
					break;
				case 'Uploaded':
					$orderCmd = '&drf.orderBy=3';
					break;
				case 'Score':
					$orderCmd = '&drf.orderBy=7';
					break;
			}
			if( !empty($orderCmd) ) {
				if( $order[0]->Direction ) {
					$orderCmd .= '&drf.orderSequence=0';
				} else {
					$orderCmd .= '&drf.orderSequence=1';
				}
			}
		}
				
		if( $firstEntry > 0 ) {
			$firstEntry -= 1;
		}
		$cmd = "search.api?command=search&text=$search&drf.firstResult=$firstEntry&drf.maxResults=$totalEntries".$orderCmd;

		$response = $this->celumRequest( $cmd, 'Search request' );
		
		$xml = new SimpleXMLElement($response);
		$documents	= $xml->documents;
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$skipped = 0;
		foreach( $documents->document as $doc ) {
			$row = array();
			
			$format 	= '';
			require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
			$objectType	= MimeTypeHandler::filename2ObjType( $format, 'dum.'.$doc->fileExtension );
			if( empty($format) && $doc->fileExtension == 'bmp' ) {	// BMP is used by Celum, but not known out of box by Enterprise
				$objectType = 'Image';
				$format 	= 'image/bmp';
			}
			
			if( !empty($format) ) { // Skip objects that we cannot classify, object type can be set to 'NotSupported'
				$modified = str_replace( ' ', 'T', $doc->lastModified );
				$uploaded = str_replace( ' ', 'T', $doc->uploadDate );
				$row[] = CELUM_CONTENTSOURCEPREFIX.$doc->id;		// REQUIRED, first ID.
				$row[] = $objectType;	// REQUIRED, second Type
				$row[] = $doc->name; 	// REQUIRED, Third Name
				$row[] = $modified;
				$row[] = $doc->originalFileSize;
				$row[] = $doc->score;
				$row[] = $uploaded;
				if( $calledByContentStation) {
					$row[] = $format;
					$row[] = ''.$this->getPublication();  // Publication required by Content Station
					$row[] = 0;// IssueId
					$row[] = $this->createCelumURL( 'download.api?documentId='.$doc->id.'&binaryType=thmb' );
				}
				
				$rows[]=$row;
			} else {
				++$skipped;
			}
		}
		
		$firstEntry 	= $firstEntry+1;
		$totalEntries 	= $xml->count->totalHits - $skipped;

		return $rows;
	}
	
	private function getEnterpriseContext( &$publication, &$category, &$status )
	{
		// Get list of publications from Enterpise. If available we use WW News
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$username = BizSession::getShortUserName();

		// Get all publication info is relatively expensive. In case a thumbnail overview is used this method is called
		// once per thumbnail, adding up to significant time. Hence we cache the results for the session:
		require_once 'Zend/Registry.php';

		if( Zend_Registry::isRegistered( 'CELUM-Publication' ) ) {
			$publication = Zend_Registry::get( 'CELUM-Publication' );
		} else {
			$pubs = BizPublication::getPublications( $username );
			// Default to first, look next if we can find one with the configured name:
			$pubFound = $pubs[0];
			foreach( $pubs as $pub ) {
				if( $pub->Name == CELUM_BRAND ) {
					$pubFound = $pub;
					break;
				}
			}
			$publication 	= new Publication($pubFound->Id);
			Zend_Registry::set( 'CELUM-Publication', $publication );
		}

		if( Zend_Registry::isRegistered( 'CELUM-Category' ) ) {
			$category = Zend_Registry::get( 'CELUM-Category' );
		} else {
			$categories = BizPublication::getSections( $username, $publication->Id );
			// Default to first, look next if we can find one with the configured name:
			$catFound = $categories[0];
			foreach( $categories as $cat ) {
				if( $cat->Name == CELUM_CATEGORY ){
					$catFound = $cat;
					break;
				}
			}
			$category 	= new Publication($catFound->Id);
			Zend_Registry::set( 'CELUM-Category', $category );
		}

		if( Zend_Registry::isRegistered( 'CELUM-Status' ) ) {
			$category = Zend_Registry::get( 'CELUM-Status' );
		} else {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$states=BizWorkflow::getStates($username, $publication->Id, null /*issue*/, $category->Id, 'Article' );
			// Default to first, look next if we can find one with the configured name:
			$statFound = $states[0];
			foreach( $states as $stat ) {
				if( $stat->Name == CELUM_STATUS) {
					$statFound = $stat;
					break;
				}
			}
			$status 	= new State($statFound->Id);
			Zend_Registry::set( 'CELUM-Status', $status );
		}
	}

	private function getPublication( )
	{
		// do we have pub id already cached?
		if( !$this->pubId) {
			$dum1=''; $dum2='';
			$this->getEnterpriseContext( $this->pubId, $dum1, $dum2, true );
		}

		return $this->pubId->Id;
	}

	private function fillMetaData( $alienID, &$meta, $name, $created, $modified, $version, $fileExtension, $size, $dpi, $width, $height, $colorSpace )
	{		
		// Get default Pub, Category and Status
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext( $publication, $category, $status );
		
		$format 	= '';
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$objectType	= MimeTypeHandler::filename2ObjType( $format, 'dum.'.$fileExtension );
		if( empty($format) && $fileExtension == 'bmp' ) {	// BMP is used by Celum, but not known out of box by Enterprise
			$objectType = 'Image';
			$format 	= 'image/bmp';
		}

		if( !$meta->BasicMetaData ) $meta->BasicMetaData = new BasicMetaData();
		$meta->BasicMetaData->ID 			= CELUM_CONTENTSOURCEPREFIX.$alienID;
		$meta->BasicMetaData->DocumentID 	= $alienID;
		$meta->BasicMetaData->Type			= $objectType;
		$meta->BasicMetaData->ContentSource	= CELUM_CONTENTSOURCEID;  // If you don't fill this, the created object is not a 'shadow', meaning that it has no link to the contentsource.
		if( !$meta->BasicMetaData->Name ) {
			$rawName = $name;
			// Remove all invalid characters from name:
			$sDangerousCharacters = "`~!@#$%^*\\|;:'<>/?".'"';
			$name = '';
			for( $i=0; $i < strlen($rawName); ++$i ) {
				if( strpos($sDangerousCharacters,$rawName[$i]) === FALSE ) {
					// valid character, add to name:
					$name .= $rawName[$i];
				}
			}
			// To get just the filename
			$meta->BasicMetaData->Name = $name;
		}
		if( !$meta->BasicMetaData->Publication ) {
			$meta->BasicMetaData->Publication = $publication;
		}
		if( !$meta->BasicMetaData->Category ) {
			$meta->BasicMetaData->Category = $category;
		}
		
		if( !$meta->ContentMetaData ) {
			$meta->ContentMetaData =  new ContentMetaData( );
		}
		// ensure the following content fields are set, also when contentmeta data already available:
		$meta->ContentMetaData->Format		 = $format;
		$meta->ContentMetaData->FileSize	 = $size;
		$meta->ContentMetaData->Width	 	 = $width;
		$meta->ContentMetaData->Height	 	 = $height;
		$meta->ContentMetaData->Dpi	 		 = $dpi;
		$meta->ContentMetaData->ColorSpace	 = $colorSpace;
		
		if( !$meta->RightsMetaData ) $meta->RightsMetaData = new RightsMetaData( );	// copyright marked (boolean), copyright (string), copyright url (string)
		
		if( !$meta->SourceMetaData ) $meta->SourceMetaData = new SourceMetaData( null,	null, null );	// credit (string), source (string), author (string)
		
		if( !$meta->WorkflowMetaData ) {
			$meta->WorkflowMetaData = new WorkflowMetaData( );
		}
		// ensure the following content fields are set, also when contentmeta data already available:
		$meta->WorkflowMetaData->Created	= $created;
		$meta->WorkflowMetaData->Modified	= $modified;
		$meta->WorkflowMetaData->Version	= $version;
		if( empty($meta->WorkflowMetaData->State) ) {
			$meta->WorkflowMetaData->State		= $status;
		}
	}

	/**
		- celumRequest -
		
		Executes the Celum request and passes back the response.
		
		The reqDescription is used to profile performance.
		
		Throws a BizException in case of error
	*/
	private function celumRequest( $cmd, $reqDescription )
	{
		$url = $this->createCelumURL($cmd);
		LogHandler::Log('Celum', 'DEBUG', 'Celum '.$reqDescription.': '.$url );
		require_once 'Zend/Http/Client.php';
		$http = new Zend_Http_Client( $url );
		PerformanceProfiler::startProfile( 'Celum - '.$reqDescription, 3 );
		$response = $http->request( Zend_Http_Client::GET );
		PerformanceProfiler::stopProfile( 'Celum - '.$reqDescription, 3 );
		if ($response->isSuccessful()) {
			return $response->getBody();
		} else {
			$msg = $response->getBody();
			throw new BizException( $msg, 'Server', $msg, $msg );		
		}
	}

    /**
	 * createCelumURL
	 *
	 * Creates Celum URL, starting with base URL with username and password and the passed in command string
	 * Returns celum URL
	 */
    private function createCelumURL( $cmd )
    {
    	return CELUM_URL . $cmd . '&username='.CELUM_USER.'&password='.CELUM_PASSWORD;
    }

    /**
	 * calledByContentStation
	 *
	 * Returns true if the client is Content Station
	 */
    private function calledByContentStation( )
    {
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		
		$app = DBTicket::DBappticket( BizSession::getTicket() );
		
		return stristr($app, 'content station');
    }

	public function isInstalled()
	{
		return CELUM_URL != '' && CELUM_USER != '';

	}
	
	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			$msg = "Celum plugin not configured, please do so in plugin's config.php";
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
	}
}
