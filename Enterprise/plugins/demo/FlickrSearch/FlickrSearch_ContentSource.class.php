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
 * @package 	Enterprise Demo Plugins
 * @subpackage 	FlickrSearch
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

// Enterprise includes:
require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';
require_once dirname(__FILE__) . '/Flickr.class.php';

// specify a Unique ID & prefix for this contentSource
define ('FS_CONTENTSOURCEID'     , 'FS');
define ('FS_CONTENTSOURCEPREFIX' , '_FS_');

class FlickrSearch_ContentSource extends ContentSource_EnterpriseConnector
{
	final public function getContentSourceId( )
	{
		return FS_CONTENTSOURCEID;
	}
	
	public function isInstalled()
	{
		$installed = false;
		if (defined('FLICKRSEARCH_API_KEY') && defined('FLICKRSEARCH_API_SECRET') && defined('FLICKRSEARCH_TOKEN')) {
			$installed = true;
		}
		return $installed;
	}
	
	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			$msg = 'Flickr API_Key, Secret and Token must be define in "' . dirname(__FILE__) . '/config.php' . '"';
			throw new BizException('' , 'Server', null, $msg);
		}
	}
	
	final public function getQueries( )
	{
		$queries = array();
		
		$queryParamSearchBy = new PropertyInfo( 
			'Search By', 'Search By', 	// Name, Display Name
			null,					// Category, not used
			'list',					// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			FLICKRSEARCH_SEARCH_BY_TAG,	// Default value
			array(FLICKRSEARCH_SEARCH_BY_TAG, FLICKRSEARCH_SEARCH_BY_USER),	// value list
			null, null, null,		// min value, max value,max length
			null, null				// parent value (not used), dependent property (not used)
			);
		$queryParamSearch = new PropertyInfo( 
			'Search', 'Search', // Name, Display Name
			null,				// Category, not used
			'string',			// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			'',					// Default value
			null,				// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
			);
			
		$queries[] = new NamedQueryType( FLICKRSEARCH_NAMEDQUERY, array($queryParamSearchBy, $queryParamSearch) );

		return $queries;
	}
	
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{		
		// Create array with column definitions
		$cols = Flickr::getColumns();
		// Perform search and return in rows
		$totalEntries = '';
		$rows = $this->search( $params, $firstEntry, $totalEntries );

		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, $firstEntry+1, count($rows), $totalEntries, null );
	}
	
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		PerformanceProfiler::startProfile( 'Flickr - getAlienObject', 3 );

		//LogHandler::Log('FlickrSearch', 'DEBUG', "FlickrSearch::getAlienObject called for $alienID - $rendition" );
		$Flickr = new Flickr();
		$fsID = substr( $alienID, strlen(FS_CONTENTSOURCEPREFIX) );
    	$imageProps = explode ( ',', urldecode($fsID) );
		$content = '';
		
		$getURL = false;
		if( Flickr::calledByContentStation() && ($rendition == 'preview' || $rendition == 'thumb')  ) {
			$getURL = true;
		}
		
        $metaData = $Flickr->getImage( $imageProps[0], $imageProps, $rendition, $content, $getURL );

        //@todo How to handle external url's? 
		if( empty($content) ) {
			$files = array();
		} else {
			$attachment = new Attachment($rendition);
			if( $getURL ) {
				$attachment->Type = 'text/hyperlink';
				$attachment->FilePath = '';
				$attachment->FileUrl = $content; // Contains url to external content
			}
			else {
				require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
				$transferServer = new BizTransferServer();
				$attachment->Type = $metaData['Format'];
				$transferServer->writeContentToFileTransferServer($content, $attachment);
			}
			$files = array( $attachment );
		}
		PerformanceProfiler::startProfile( 'Flickr - fillMetaData', 5 );
		$meta = new MetaData();
		$this->fillMetaData( $meta, $alienID, $metaData );
		PerformanceProfiler::stopProfile( 'Flickr - fillMetaData', 5 );
		
		$object = new Object( 	$meta,				// meta data
							 	array(), null,		// relations, pages
			 					$files, 			// Files array of attachment
 								null, null, null	// messages, elements, targets
							);
		PerformanceProfiler::stopProfile( 'Flickr - getAlienObject', 3 );
		return $object;
	}
	
	final public function createShadowObject( $alienID, $destObject )
	{
		//LogHandler::Log('FlickrSearch', 'DEBUG', "FlickrSearch::createShadowObject called for $alienID" );
		
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/utils/ImageUtils.class.php'; // ResizeJPEG
		
		$Flickr = new Flickr();
		$fsID = substr( $alienID, strlen(FS_CONTENTSOURCEPREFIX) );
    	$imageProps = explode ( ',', $fsID );
	
		$nativeContent = '';
        $metaData = $Flickr->getImage($imageProps[0], $imageProps, 'native', $nativeContent, false);
		$previewContent = '';
		$thumbContent = '';

		require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
		$native = new Attachment('native', $metaData['Format']);
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer($nativeContent, $native);
		$files = array( $native );

		if( !empty($previewContent) ) {
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$preview = new Attachment('preview', 'image/jpg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($previewContent, $preview);
			$files[] = $preview;
		}
		if( !empty($thumbContent) ) {
			require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
			$thumb = new Attachment('thumb', 'image/jpg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($thumbContent, $thumb);			
			$files[] = $thumb;
		}

		if( $destObject ) {
			$meta = $destObject->MetaData;
		} else {
			$meta = new MetaData();
			$destObject = new Object( $meta, array(), null, null, null, null, null );
		}
		$this->fillMetaData( $meta, $alienID, $metaData );
		$destObject->Files = $files;
		$destObject->Targets = array();
		return $destObject;
	}

	private function search( $searchParams, $firstEntry, &$totalEntries )
	{
		$rows = array();
		$Flickr = new Flickr();
	
		$rows = $Flickr->search( $searchParams[0]->Value, $searchParams[1]->Value, $firstEntry, $totalEntries );

		return $rows;
	}

	private function getEnterpriseContext( &$publication, &$category, &$status )
	{
		// Get list of publications from Enterpise.
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		
		$username = BizSession::getShortUserName();
		$pubs     = BizPublication::getPublicationInfos($username);
		$pubFound = $pubs[0]; // default to first pub

		$publication 	= new Publication($pubFound->Id);
		$category 		= new Category($pubFound->Sections[0]->Id);
		foreach( $pubFound->States as $state ) {
			if( $state->Type == ('Image' ) ) {
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
	 * @param string	$metaData		Object metadata that get from content source
	 */
	private function fillMetaData( &$meta, $alienID, $metaData )
	{
		//LogHandler::Log('RdGMetaData', 'DEBUG', print_r($metaData, 1));
		
		// Get defult Pub, Category and Status
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext( $publication, $category, $status );
		
		// BasicMetaData
		if( !$meta->BasicMetaData ) {
			$meta->BasicMetaData = new BasicMetaData();
		}
		$meta->BasicMetaData->ID    		= $alienID;
		$meta->BasicMetaData->DocumentID    = $metaData['Id'];
		$meta->BasicMetaData->Type			= $metaData['Type'];
		$meta->BasicMetaData->ContentSource	= FS_CONTENTSOURCEID;
		$meta->BasicMetaData->Name 			= $metaData['Name'];
		$meta->BasicMetaData->Publication 	= $publication;
		$meta->BasicMetaData->Category = $category;

		// ContentMetaData
		if( !$meta->ContentMetaData ) {
			$meta->ContentMetaData =  new ContentMetaData();
		}
		$meta->ContentMetaData->Format		= $metaData['Format'] ;
		$meta->ContentMetaData->Width		= array_key_exists('Width', $metaData) ? $metaData['Width'] : '0';
		$meta->ContentMetaData->Height		= array_key_exists('Height', $metaData) ? $metaData['Height'] : '0';
		$meta->ContentMetaData->FileSize	= array_key_exists('FileSize', $metaData) ? $metaData['FileSize'] : '0';
		$meta->ContentMetaData->Dpi			= array_key_exists('Dpi', $metaData) ? $metaData['Dpi'] : '72'; // default to 72 dpi if not known
		$meta->ContentMetaData->ColorSpace	= array_key_exists('ColorSpace', $metaData) ? strtoupper($metaData['ColorSpace']) : '';
		$meta->ContentMetaData->Description = array_key_exists('Caption', $metaData) ? $metaData['Caption'] :'';

		// RightsMetaData
		if( !$meta->RightsMetaData ) {
			$meta->RightsMetaData = new RightsMetaData();
		}
		$meta->RightsMetaData->Copyright		= array_key_exists('Copyright', $metaData) ? $metaData['Copyright'] : '';
		$meta->RightsMetaData->CopyrightMarked	= array_key_exists('CopyrightMarked', $metaData) ? $metaData['CopyrightMarked'] : 'false';
		$meta->RightsMetaData->CopyrightURL		= array_key_exists('CopyrightURL', $metaData) ? $metaData['CopyrightURL'] : '';

		// SourceMetaData
		if( !$meta->SourceMetaData ) {
			$meta->SourceMetaData = new SourceMetaData();
		}
		$meta->SourceMetaData->Credit		= array_key_exists('Credit', $metaData) ? $metaData['Credit'] : '';
		$meta->SourceMetaData->Source		= array_key_exists('Source', $metaData) ? $metaData['Source'] : '';
		$meta->SourceMetaData->Author		= array_key_exists('Author', $metaData) ? $metaData['Byline'] : '';
		$meta->SourceMetaData->Urgency		= array_key_exists('Priority', $metaData) ? $metaData['Priority'] : '';

		// WorkflowMetaData
		if( !$meta->WorkflowMetaData ) {
			$meta->WorkflowMetaData = new WorkflowMetaData();
		}
		$meta->WorkflowMetaData->Modified	= array_key_exists('Modified', $metaData) ? $metaData['Modified'] : '';
		$meta->WorkflowMetaData->Created	= $metaData['Created'];
		$meta->WorkflowMetaData->State		= $status;
	}
}
