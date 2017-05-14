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
 * @package 	Enterprise Demo Plugins
 * @subpackage 	ElvisSearch
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

// Enterprise includes:
require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once dirname(__FILE__) . '/ElvisClient.php';

// specify a Unique ID & prefix for this contentSource
define ('ES_CONTENTSOURCEID'     , 'ES');
define ('ES_CONTENTSOURCEPREFIX' , '_ES_');

class ElvisSearch_ContentSource extends ContentSource_EnterpriseConnector
{
	/**
	 * getContentSourceId
	 * 
	 * Return unique identifier for this content source implementation. Each alien object id needs
	 * to start with _<this id>_
	 * 
	 * @return string	unique identifier for this content source, without underscores.
	 */
	final public function getContentSourceId( )
	{
		return ES_CONTENTSOURCEID;
	}
	
	/**
	 * Returns available queries for the content source. These will be shown as named queries.
	 * It's Ok to return an empty array, which means the content source is not visible in the 
	 * Enterprise (content) query user-interface.
	 *
	 * @return array of NamedQuery
	 */
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
			
		$queries[] = new NamedQueryType( ELVISSEARCH_NAMEDQUERY, array($queryParamSearch) );

		return $queries;
	}
	
	/**
	 * Execute query on content source.
	 *
	 * @param string 				$query		Query name as obtained from getQueries
	 * @param array of Property 	$params		Query parameters as filled in by user
	 * @param unsigned int			$firstEntry	Index of first requested object of total count (TotalEntries) 
	 * @param unsigned int			$maxEntries Max count of requested objects (zero for all, nil for default)
	 * @param array of QueryOrder	$order		
	 * 
	 * @return WflNamedQueryResponse
	 */
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{		
		// Create array with column definitions
		$cols = self::getColumns();
		// Perform search and return in rows
		$totalEntries = 0;
		$rows = $this->search( $params, $firstEntry, $maxEntries, $totalEntries );

		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, $firstEntry+1, count($rows), $totalEntries, null );
	}

	/**
	 * Returns a url from a Elvit hit given a specific rendition
	 *
	 * @param object $hit
	 * @param string $rendition
	 * @return string url
	 */
	private static function getUrlFromRendition($hit, $rendition)
	{
		$url = '';
		switch ($rendition){
			case 'thumb':
				$url = isset($hit->thumbnailUrl) ? $hit->thumbnailUrl : '';
				break;
			case 'preview':
				$url = isset($hit->previewUrl) ? $hit->previewUrl : '';
				break;
			case 'native':
				$url = isset($hit->originalUrl) ? $hit->originalUrl : '';
				break;
		}
		
		return $url;
	}
	
	/**
	 * Gets alien object. In case of rendition 'none' the lock param can be set to true, this is the 
	 * situation that Properties dialog is shown. If content source allows this, return the object
	 * on failure the dialog will be read-only. If Property dialog is ok-ed, a shadow object will 
	 * be created. The object is assumed NOT be locked, hence there is no unlock sent to content source.
	 *
	 * @param string	$alienId	Alien object id, so include the _<ContentSourceId>_ prefix
	 * @param string	$rendition	'none' (to get properties only), 'thumb', 'preview' or 'native'
	 * @param boolean	$lock		See method comment.
	 * 
	 * @return Object
	 */
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		$elvisID = substr( $alienID, strlen(ES_CONTENTSOURCEPREFIX) );
		
		$elvisClient = self::getElvisClient();
		$searchResult = $elvisClient->searchById($elvisID);
		
		$meta = new MetaData();
		$this->fillMetaData( $meta, $alienID, $searchResult->hit[0] );
		$files = array();
		$url = self::getUrlFromRendition($searchResult->hit[0], $rendition);
        //@todo How to handle external url's? 		
		if (! empty ($url)){
			$attachment = new Attachment($rendition);
			if (self::calledByContentStation() && ($rendition == 'preview' || $rendition == 'thumb')){
				$attachment->Type = 'text/hyperlink';
				$attachment->FilePath = '';
				$attachment->FileUrl = $url; // Contains url to external content
			} else {
				$attachment->Type = $meta->BasicMetaData->Type;
				$content = file_get_contents($url);
				require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer($content, $attachment);				
			}
			$files = array( $attachment );
		}
		
		$object = new Object( 	$meta,				// meta data
							 	array(), null,		// relations, pages
			 					$files, 			// Files array of attachment
 								null, null, null	// messages, elements, targets
							);
		
		return $object;
	}
	
	/**
	 * Create shadow object for specified alien object. The actual creation is done by Enterprise,
	 * the Content Sources needs to instantiate and fill in an object of class Object.
	 * When an empty name is filled in, autonaming will be used.
	 * It's up to the content source implementation if any renditions (like thumb/preview) are stored
	 * inside Enterprise. If any rendition is stored in Enterprise it's the content source implementation's
	 * responsibility to keep these up to date. This could for example be checked whenever the object 
	 * is requested via getShadowObject
	 *
	 * @param string	$alienId 		Alien object id, so include the _<ContentSourceId>_ prefix
	 * @param Object	$destObject		In saome cases (CopyObject, SendToNext, Create relatio) 
	 									this can be partly filled in by user, in other cases this is null.
	 * 									In some cases this is mostly empty, so be aware.
	 * 
	 * @return Object	filled in with all fields, the actual creation of the Enterprise object is done by Enterprise.
	 */
	final public function createShadowObject( $alienID, $destObject )
	{
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/utils/ImageUtils.class.php'; // ResizeJPEG
		
		$elvisID = substr( $alienID, strlen(ES_CONTENTSOURCEPREFIX) );
		
		$elvisClient = self::getElvisClient();
		$searchResult = $elvisClient->searchById($elvisID);
		
		if( $destObject ) {
			$meta = $destObject->MetaData;
		} else {
			$meta = new MetaData();
			$destObject = new Object( $meta, array(), null, null, null, null, null );
		}
		$this->fillMetaData( $meta, $alienID, $searchResult->hit[0] );
		$url = self::getUrlFromRendition($searchResult->hit[0], 'native');
		$files = array();
		if (! empty($url)) {
			$content = file_get_contents($url);
			$type = $destObject->MetaData->BasicMetaData->Type;
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$native = new Attachment('native', $type);
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($content, $native);
			$files[] = $native;
			$mimeType = $destObject->MetaData->ContentMetaData->Format;
			if ($mimeType == 'image/jpeg' || $mimeType == 'image/gif' || $mimeType == 'image/png') {
				//Image
				$previewImage = '';
				$thumbImage = null;
				// Generate preview
				if( !ImageUtils::ResizeJPEG( 600, $content, null, 75, null, null, $previewImage ) ) {
					$previewImage = null;
				}
				
				if( !empty($previewImage) ) {
					require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
					$preview = new Attachment('preview', 'image/jpeg');
					$transferServer = new BizTransferServer();
					$transferServer->writeContentToFileTransferServer($previewImage, $preview);
					$files[] = $preview;
				}
				
				//Generate thumb
				if( $previewImage ) { // if preview generation fails, there is no reason to try thumb either
					$thumbImage = '';
					// we now use the preview, this is much smaller than the original, so preview from memory is better than native from file
					if( !ImageUtils::ResizeJPEG( 100, $previewImage, null, 75, null, null, $thumbImage ) ) {
						$thumbImage = null;
					}
				}
	
				if( !empty($thumbImage) ) {
					require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
					$thumb = new Attachment('thumb', 'image/jpeg');
					$transferServer = new BizTransferServer();
					$transferServer->writeContentToFileTransferServer($thumbImage, $thumb);					
					$files[] = $thumb;
				}
			}
		}
		
		$destObject->Files = $files;
		$destObject->Targets = array();
	
		return $destObject;
	}
	
	/**
	 * Returns an Enterprise Object Type from a Elvis hit.
	 *
	 * @param object $hit Elvis hit object
	 * @return string Enterprise Object Type
	 */
	private static function getTypeFromHit($hit)
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		
		$mimeType = '';
		$result = MimeTypeHandler::filename2ObjType($mimeType, $hit->filename);
		if (! $result){
			$result = 'Other';
		}
		
		return $result;
	}
	
	/**
	 * Returns an Enterprise Object Format from a Elvis hit.
	 *
	 * @param object $hit Elvis hit object
	 * @return string Enterprise Object Format (mimetype)
	 */
	private static function getFormatFromHit($hit)
	{
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		
		$result = MimeTypeHandler::filePath2MimeType($hit->filename);
		
		return $result;
	}
	
	/**
	 * Returns an Elvis Metadata Field value
	 *
	 * @param array $fields
	 * @param string $name field name
	 * @return string
	 */
	private static function getMetaDataFieldValue($fields, $name)
	{
		$result = '';
		foreach ($fields as $field){
			if ($field->name == $name){
				$result = $field->value[0]->_;
				break;
			}
		}
		
		return $result;
	}
	
	/**
	 * Convert an Elvis hit to a query result row
	 *
	 * @param object $hit
	 * @return array
	 */
	private function convertHitToRow($hit)
	{
		$row = array();
		// ID
		$row[] = ES_CONTENTSOURCEPREFIX . $hit->id;
		// Type
		$row[] = self::getTypeFromHit($hit);
		// Name
		$row[] = $hit->name;
		// Creator
		$row[] = self::getMetaDataFieldValue($hit->metadata->field, 'assetCreator');
		// Created
		$row[] = date('Y-m-d\TH:i:s', self::getMetaDataFieldValue($hit->metadata->field, 'assetCreated'));
		// Modifier
		$row[] = self::getMetaDataFieldValue($hit->metadata->field, 'assetModifier');
		// Modified
		$row[] = date('Y-m-d\TH:i:s', self::getMetaDataFieldValue($hit->metadata->field, 'assetModified'));
        if( self::calledByContentStation() ) {
			// Format
			$row[] = self::getFormatFromHit($hit);
			// PublicationId
			$row[] = 1;
			// thumbUrl
			$row[] = isset($hit->thumbnailUrl) ? $hit->thumbnailUrl : '';
        }
		
		return $row;
	}

	/**
	 * Searches on an Elvis server and returns query result rows.
	 *
	 * @param array $searchParams Property array
	 * @param int $firstEntry
	 * @param int $totalEntries
	 * @return array
	 */
	private function search( $searchParams, $firstEntry, $maxEntries, &$totalEntries )
	{
		$rows = array();
	
		$elvisClient = self::getElvisClient();
		$queryString = '';
		if (isset($searchParams[0]->Value)){
			$queryString = $queryString;
		}
		$searchResult = $elvisClient->search($queryString, $firstEntry, $maxEntries);
		$totalEntries = $searchResult->totalHits;
		if (isset($searchResult->hit)){
			foreach ($searchResult->hit as $hit){
				$rows[] = $this->convertHitToRow($hit);
			}
		}

		return $rows;
	}
	
	/**
	 * Return an Elvis SOAP Client
	 *
	 * @return ElvisClient
	 */
	private static function getElvisClient()
	{
		$options = array();
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$elvisClient = new ElvisClient(ELVIS_URL, $options);
		$elvisClient->setUser(ELVIS_USERNAME, ELVIS_PASSWORD);
		
		return $elvisClient;
	}

	/**
	 * Gets default brand, category and state
	 *
	 * @param int $publication
	 * @param int $category
	 * @param int $status
	 * @param string $objectType
	 */
	private static function getEnterpriseContext( &$publication, &$category, &$status, $objectType )
	{
		// Get list of publications from Enterpise.
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		
		$username = BizSession::getShortUserName();
		$pubs     = BizPublication::getPublicationInfos($username);
		$pubFound = $pubs[0]; // default to first pub

		$publication 	= new Publication($pubFound->Id);
		$category 		= new Category($pubFound->Sections[0]->Id);
		foreach( $pubFound->States as $state ) {
			if( $state->Type == $objectType ) {
				$status	= new State($state->Id);
				break;
			}
		}
	}
	
	/**
	 * fillMetaData
	 *
	 * Fill in the MetaData for the Alien Object
	 * 
	 * @param Object	$meta			Object of MetaData that will filled
	 * @param string	$alienID		Id of the alien object
	 * @param string	$hit			Object metadata that get from content source
	 */
	private function fillMetaData( &$meta, $alienID, $hit )
	{
		// BasicMetaData
		if( !$meta->BasicMetaData ) {
			$meta->BasicMetaData = new BasicMetaData();
		}
		$meta->BasicMetaData->ID    		= $alienID;
		$meta->BasicMetaData->DocumentID    = $hit->id;
		$meta->BasicMetaData->Type			= self::getTypeFromHit($hit);
		$meta->BasicMetaData->ContentSource	= ES_CONTENTSOURCEID;
		$meta->BasicMetaData->Name 			= $hit->name;
		// pub, cat, state
		$publication = ''; $category = ''; $status = '';
		self::getEnterpriseContext( $publication, $category, $status, $meta->BasicMetaData->Type );
		$meta->BasicMetaData->Publication 	= $publication;
		$meta->BasicMetaData->Category      = $category;

		// ContentMetaData
		if( !$meta->ContentMetaData ) {
			$meta->ContentMetaData =  new ContentMetaData();
		}
		$meta->ContentMetaData->Format		= self::getFormatFromHit($hit);
		$meta->ContentMetaData->Width		= intval(self::getMetaDataFieldValue($hit->metadata->field, 'width'));
		$meta->ContentMetaData->Height		= intval(self::getMetaDataFieldValue($hit->metadata->field, 'height'));
		$meta->ContentMetaData->FileSize	= intval(self::getMetaDataFieldValue($hit->metadata->field, 'fileSize'));
		$meta->ContentMetaData->Dpi			= intval(self::getMetaDataFieldValue($hit->metadata->field, 'resolutionX'));
		if ($meta->ContentMetaData->Dpi == 0){
			$meta->ContentMetaData->Dpi = 72; // default to 72 DPI
		}
		// $meta->ContentMetaData->ColorSpace	= ;
		$meta->ContentMetaData->Description = self::getMetaDataFieldValue($hit->metadata->field, 'caption');

		// RightsMetaData
		if( !$meta->RightsMetaData ) {
			$meta->RightsMetaData = new RightsMetaData();
		}
		// $meta->RightsMetaData->Copyright		= ;
		// $meta->RightsMetaData->CopyrightMarked	= ;
		// $meta->RightsMetaData->CopyrightURL		= ;
		
		// SourceMetaData
		if( !$meta->SourceMetaData ) {
			$meta->SourceMetaData = new SourceMetaData();
		}
		
		// $meta->SourceMetaData->Credit		= ;
		// $meta->SourceMetaData->Source		= ;
		// $meta->SourceMetaData->Author		= ;
		// $meta->SourceMetaData->Urgency		= ;
		
		// WorkflowMetaData
		if( !$meta->WorkflowMetaData ) {
			$meta->WorkflowMetaData = new WorkflowMetaData();
		}
		$meta->WorkflowMetaData->Modified	= date('Y-m-d\TH:i:s', self::getMetaDataFieldValue($hit->metadata->field, 'assetModified'));
		$meta->WorkflowMetaData->Modifier	= self::getMetaDataFieldValue($hit->metadata->field, 'assetModifier');
		$meta->WorkflowMetaData->Created	= date('Y-m-d\TH:i:s', self::getMetaDataFieldValue($hit->metadata->field, 'assetCreated'));
		$meta->WorkflowMetaData->Creator	= self::getMetaDataFieldValue($hit->metadata->field, 'assetCreator');
		$meta->WorkflowMetaData->State		= $status;
		
		if( !$meta->ExtraMetaData ) {
			//function mapMetaDataToCustomProperties() in /server/bizclasses/BizMetaDataPreview.class.php is expecting an array for ExtraMetaData.
			$meta->ExtraMetaData = array();
		}
	}

	/**
	 * Returns query columns
	 *
	 * @return array
	 */
	static public function getColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 			'ID', 			'string' 	); // Required as 1st
		$cols[] = new Property( 'Type', 		'Type', 		'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name', 		'Name', 		'string' 	); // 
		$cols[] = new Property( 'Creator', 	'Creator', 	'string' 	); // 
		$cols[] = new Property( 'Created',  	'Created', 		'datetime'	);
		$cols[] = new Property( 'Modifier', 	'Modifier', 	'string' 	); // 
		$cols[] = new Property( 'Modified',  	'Modified', 		'datetime'	);
        
        if( self::calledByContentStation() ) {
			$cols[] = new Property( 'Format', 		'Format', 		'string' 	);	// Required by Content Station
    	    $cols[] = new Property( 'PublicationId','PublicationId','string' 	);	// Required by Content Station
	        $cols[] = new Property( 'thumbUrl',		'thumbUrl',		'string' 	);	// Thumb URL for Content Station
	    }

		return $cols;
	}

    /**
	 * calledByContentStation
	 *
	 * Returns true if the client is Content Station
	 */
    static public function calledByContentStation( )
    {
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		
		$app = DBTicket::DBappticket( BizSession::getTicket() );
		
		return stristr($app, 'content station');
    }
}
