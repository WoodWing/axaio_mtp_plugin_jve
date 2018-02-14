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
 * @package 	Enterprise Demo Plugins
 * @subpackage 	GettyImages
 * @since 		v8.2.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__) . '/config.php';	// Plug-in config file
require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';
require_once dirname(__FILE__) . '/GettyImages.class.php';

define ('GI_CONTENTSOURCEID'     , 'GI');		// Unique content source ID
define ('GI_CONTENTSOURCEPREFIX' , '_GI_');		// Content source prefix

class GettyImages_ContentSource extends ContentSource_EnterpriseConnector
{
	/**
	 * Get Content Source Id
	 *
	 * @return string GI_CONTENTSOURCEID
	 */
	final public function getContentSourceId( )
	{
		return GI_CONTENTSOURCEID;
	}

	/**
	 * Construct the queries parameters
	 *
	 * @return array $queries
	 */
	final public function getQueries( )
	{
		$queries = array();
		$searchParam = new PropertyInfo( 
			'Search', 'Search', // Name, Display Name
			null,				// Category, not used
			'string',			// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			'',					// Default value
			null,				// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
			);
		$queries[] = new NamedQueryType( GETTYIMAGES_NAMEDQUERY, array($searchParam) );

		return $queries;
	}

	/**
	 * Perform named query. Based on the passed query parameters images are searched from the
	 * Getty Image system.
	 *
	 * @param string $query Name of the query. Not used in this context.
	 * @param array $params The search criteria.
	 * @param integer $firstEntry Offset for the result set.
	 * @param integer $maxEntries Number of returned images. Not used in this context.
	 * @param string $order The way the result is sorted. Not used in this context.
	 * @return object WflNamedQueryResponse
	 */
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{		
		LogHandler::Log('GettyImages', 'DEBUG', 'GettyImages::queryObjects called for: '.$params[0]->Value );

		// Create array with column definitions
		$cols = GettyImages::getColumns();

		// Perform search and return in rows
		$totalEntries = 0;
		$facets = array();
		$rows = $this->search( $params, $firstEntry, $totalEntries, $facets );

		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, $firstEntry+1, count($rows), $totalEntries, null, $facets );
	}

	/**
	 * Get alien object
	 *
	 * @param string $alienID The alient object id
	 * @param string $rendition The rendition of the alien object
	 * @param boolean $lock The lock indicator. Not used in this context.
	 * @return object $object
	 */
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		LogHandler::Log('GettyImages', 'DEBUG', "GettyImages::getAlienObject called for $alienID - $rendition" );

		PerformanceProfiler::startProfile( 'GettyImages - getAlienObject', 3 );

		$imgId = substr( $alienID, strlen(GI_CONTENTSOURCEPREFIX) );
		$getty = new GettyImages();
        $image = $getty->getImage( $imgId, $rendition );

		if( empty($image->Content) ) {
			$files = array();
		} else {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer		= new BizTransferServer();
			$attachment 		= new Attachment( $rendition );
			$attachment->Type 	= $image->MetaData['Format'];
			$transferServer->writeContentToFileTransferServer( $image->Content, $attachment );
			$files = array( $attachment );
		}

		PerformanceProfiler::startProfile( 'GettyImages - fillMetaData', 5 );
		$metaData = new MetaData();
		$this->fillMetaData( $metaData, $alienID, $image->MetaData );
		PerformanceProfiler::stopProfile( 'GettyImages - fillMetaData', 5 );
		
		$object = new Object( 	$metaData,			// meta data
							 	array(), null,		// relations, pages
			 					$files, 			// Files array of attachment
 								null, null, null	// messages, elements, targets
							);
		PerformanceProfiler::stopProfile( 'GettyImages - getAlienObject', 3 );

		return $object;
	}

	/**
	 * Create shadow object
	 *
	 * @param string $alienID The alien object id
	 * @param object $destObject The create destination object
	 * @return object $destObject
	 */
	final public function createShadowObject( $alienID, $destObject )
	{
		LogHandler::Log('GettyImages', 'DEBUG', "GettyImages::createShadowObject called for $alienID" );
		
		$getty = new GettyImages();
		$imgId = substr( $alienID, strlen(GI_CONTENTSOURCEPREFIX) );
        $image = $getty->getImage( $imgId, 'native' );

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$native = new Attachment( 'native', $image->MetaData['Format'] );
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer( $image->Content, $native );
		$files = array( $native );

		if( $destObject ) {
			$meta = $destObject->MetaData;
		} else {
			$meta = new MetaData();
			$destObject = new Object( $meta, array(), null, null, null, null, null );
		}
		$this->fillMetaData( $meta, $alienID, $image->MetaData );
		$destObject->Files = $files;

		return $destObject;
	}

	/**
	 * Call getty images search
	 *
	 * @param array $searchParams Array of search params
	 * @param integer $firstEntry First number of the search items
	 * @param integer $totalEntries Total number of the search items
	 * @param array $facets Array of facets object
	 * @return array $rows Array of result rows
	 */
	private function search( $searchParams, $firstEntry, &$totalEntries, &$facets )
	{
		$rows 	= array();
		$getty 	= new gettyImages();
		$rows	= $getty->search( $searchParams, $firstEntry, $totalEntries, $facets );

		return $rows;
	}

	/**
	 * Get Enterprise publication, caregory and state
	 *
	 * @param object $publication Reference publication object
	 * @param object $category Reference category object
	 * @param object $status Reference status object
	 */
	private function getEnterpriseContext( &$publication, &$category, &$status )
	{
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
		$userName 	= BizSession::getShortUserName();
		$pubInfos 	= BizPublication::getPublicationInfos( $userName );
		$defaultPub	= $pubInfos[0]; // default to first pub

		$publication 	= new Publication( $defaultPub->Id, $defaultPub->Name );
		$category 		= new Category( $defaultPub->Categories[0]->Id, $defaultPub->Categories[0]->Name );
		foreach( $defaultPub->States as $state ) {
			if( $state->Type == 'Image' ) {
				$status = $state;
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
		LogHandler::Log( 'GettyImages', 'DEBUG', 'GettyImages MetaData: ' . print_r($metaData, 1) );
		
		// Get defult Pub, Category and Status
		$publication= null;
		$category 	= null; 
		$status		= null;
		$this->getEnterpriseContext( $publication, $category, $status );
		
		// BasicMetaData
		if( !$meta->BasicMetaData ) {
			$meta->BasicMetaData = new BasicMetaData();
		}
		$meta->BasicMetaData->ID    		= $alienID;
		$meta->BasicMetaData->DocumentID    = $metaData['Id'];
		$meta->BasicMetaData->Type			= $metaData['Type'];
		$meta->BasicMetaData->ContentSource	= GI_CONTENTSOURCEID;
		$meta->BasicMetaData->Name 			= $metaData['Name'];
		$meta->BasicMetaData->Publication 	= $publication;
		$meta->BasicMetaData->Category 		= $category;

		// ContentMetaData
		if( !$meta->ContentMetaData ) {
			$meta->ContentMetaData =  new ContentMetaData();
		}
		$meta->ContentMetaData->Format		= $metaData['Format'] ;
		$meta->ContentMetaData->Description = array_key_exists('Caption', $metaData) ? $metaData['Caption'] :'';

		// RightsMetaData
		if( !$meta->RightsMetaData ) {
			$meta->RightsMetaData = new RightsMetaData();
		}
		$meta->RightsMetaData->Copyright		= array_key_exists('Copyright', $metaData) ? $metaData['Copyright'] : '';

		// SourceMetaData
		if( !$meta->SourceMetaData ) {
			$meta->SourceMetaData = new SourceMetaData();
		}
		$meta->SourceMetaData->Credit	= array_key_exists('Credit', $metaData) ? $metaData['Credit'] : '';
		$meta->SourceMetaData->Source	= array_key_exists('Source', $metaData) ? $metaData['Source'] : '';
		$meta->SourceMetaData->Author	= array_key_exists('Author', $metaData) ? $metaData['Author'] : '';

		// WorkflowMetaData
		if( !$meta->WorkflowMetaData ) {
			$meta->WorkflowMetaData = new WorkflowMetaData();
		}
		$meta->WorkflowMetaData->Created= array_key_exists('Created', $metaData) ? $metaData['Created'] : '';
		$meta->WorkflowMetaData->State	= $meta->WorkflowMetaData->State ? $meta->WorkflowMetaData->State : $status;
	}
}
