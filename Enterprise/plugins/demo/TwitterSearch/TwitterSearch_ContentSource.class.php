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

/*
	Exposes Twitter as Content Source.
	
	Two searches are listed:
	1. Search Twitter with selected language
	2. Show current Twitter trends
*/

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';

require_once dirname(__FILE__) . '/config.php';

class TwitterSearch_ContentSource extends ContentSource_EnterpriseConnector
{
	private $pubId = null;

	final public function getContentSourceId( )
	{
		return TWS_CONTENTSOURCEID;
	}
	
	final public function getQueries( )
	{
		$queries = array();
		
		if( TWS_QUERY_SEARCH != '' ) {
			$queryParamSearch = new PropertyInfo( 
				'Search', 'Search', // Name, Display Name
				null,				// Category, not used
				'string',			// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
				'',					// Default value
				null,				// value list
				null, null, null,	// min value, max value,max length
				null, null			// parent value (not used), dependent property (not used)
				);
			$queryParams = array($queryParamSearch);
				
			$langArray = unserialize(TWS_LANGUAGES);
			$langArrayKeys = array_keys($langArray);
			if( !empty( $langArray ) ) {
				$queryParamLang = new PropertyInfo( 
					'Language', 'Language', // Name, Display Name
					null,				// Category, not used
					'list',				// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
					$langArrayKeys[0],	// Default value
					$langArrayKeys,		// value list
					null, null, null,	// min value, max value,max length
					null, null			// parent value (not used), dependent property (not used)
					);
				$queryParams[] = $queryParamLang;
			}

			$queries[] = new NamedQueryType( TWS_QUERY_SEARCH, $queryParams );
		}

		if( TWS_QUERY_TRENDS != '' ) {
			$queries[] = new NamedQueryType( TWS_QUERY_TRENDS, array() );
		}

		return $queries;
	}
	
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// keep code analyzer happy for unused params:
		// maxEntries is ignored because the API just returns 10 or 20 entries per call
		$query=$query; $maxEntries=$maxEntries; $order=$order;
		
		LogHandler::Log('TwitterSearch', 'DEBUG', 'doNamedQuery called for search: '.$query );

		if( $query == TWS_QUERY_SEARCH ) {	
			// Create array with column definitions
			$cols = $this->getSearchColumns();
	
			$totalEntries = 0;
			$rows = $this->doSearch( $params, $firstEntry, $totalEntries );
		} else { // TWS_QUERY_TRENDS
			// Create array with column definitions
			$cols = $this->getTrendsColumns();
	
			$totalEntries = 0;
			$rows = $this->doTrendsSearch( $firstEntry, $totalEntries );
		}
		
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, $firstEntry, count($rows), $totalEntries, null, null );
	}
	
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		PerformanceProfiler::startProfile( 'Twitter Search - getAlienObject', 5 );
		$lock=$lock ; // we don't use this argument, keep analyzer happy
		LogHandler::Log('Twitter Search', 'DEBUG', "getAlienObject called for $alienID - $rendition" );

		$files = array();
		$meta = new MetaData();
		$this->fillMetaData( $meta, $alienID );
		$object = new Object( 	$meta,				// meta data
							 	array(), null,		// relations, pages
			 					$files, 			// Files array of attachment
 								null, null, null	// messages, elements, targets
							);

		PerformanceProfiler::stopProfile( 'Twitter Search - getAlienObject', 5 );
		return $object;
	}
	
	final public function deleteAlienObject( $alienID )
	{
		$msg = "Cannot delete articles from Twitter";
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
		$files = array();

		// In case of a copy the user already filled in an object, we use that
		// For a real-life content source this would be further filled with metadata from the content source
		if( $destObject ) {
			$meta = $destObject->MetaData;
		} else {
			$meta = new MetaData();
			$destObject = new Object( $meta, array(), null, null, null, null, null );
		}
		$this->fillMetaData( $meta, $alienID );
		$destObject->Files = $files;
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
		$cols[] = new Property( 'Author', 			'From', 			'string' 	); 
		$cols[] = new Property( 'Modified', 		'Modified', 		'datetime'	);
		$cols[] = new Property( 'Source', 			'Source', 			'string'	);
		$cols[] = new Property( 'DocumentID', 		'DocumentID', 		'string'	);
		$cols[] = new Property( 'Format', 			'Format',	 		'string'	);	// Needed to make double click open new tab
		$cols[] = new Property( 'PublicationId', 	'PublicationId', 	'string' 	);	// Required by Content Station
        $cols[] = new Property( 'IssueId',			'IssueId',			'string' 	);	// Required by Content Station
		return $cols;
	}
	
	private function doSearch( $params, &$firstEntry, &$totalEntries )
	{
		PerformanceProfiler::startProfile( 'Twitter Search - doSearch', 3 );
		$search = $params[0]->Value;
		$page = round(($firstEntry+1)/100);	// We use pages of 100
		$twitterParams = array( 'rpp' => 100 );
		if( count($params) > 1 ) {
			// Language specified		
			$langArray = unserialize(TWS_LANGUAGES);
			$lang = $langArray[$params[1]->Value];
			if( $lang != '' ) {
				$twitterParams['lang'] = $lang;
			}
		}

		require_once 'Zend/Service/Twitter/Search.php';
		$twitter_search = new Zend_Service_Twitter_Search('json');
		$search_results = $twitter_search->search( $search, $twitterParams );
		$results = $search_results['results'];
		
		// The API does not return the total number of entries. So we do a little trick. If we 
		// get the maximum of return results (100) we assume there is another page, so we say total 
		// entries is one higher than the last item we got. Getting that next page could lead to yet
		// another extra page etc.
		$firstEntry 	+= 1; 	
		$numResults 	= count($results);
		$totalEntries 	= ($numResults == 100) ? $firstEntry + $numResults : $firstEntry + $numResults - 1 ;

		$rows = array();
		foreach( $results as $result ) {
			$row = array();

			// We use id + created + from + text + source as the id separate by _|_
			// We need to do this because the Twitter API does not offer functionality to get info of a specific
			// tweet, just searching on keywords. So all metadata that we have, should be put in the 
			// id so that we get it again when are called for GetAlien or CreateShadow
			// The complete ID is URL encoded to prevent problems with local files in Smart Connection
			$sep = '_|_';
			$dateArray = date_parse($result['created_at']);
			$date 	= date('Y-m-d\TH:i:s', mktime( $dateArray['hour'], $dateArray['minute'], $dateArray['second'], $dateArray['month'], $dateArray['day'], $dateArray['year'] ) );
			$source = strip_tags( html_entity_decode( $result['source'], ENT_COMPAT, 'UTF-8' ) );
			$name 	= html_entity_decode( $result['text'], ENT_COMPAT, 'UTF-8' );
			$alienId = TWS_CONTENTSOURCEPREFIX.urlencode( $result['id'].$sep.$date.$sep.$result['from_user'].$sep.$name.$sep.$source );

			$row[] = $alienId;			// REQUIRED, first ID.
			$row[] = 'Hyperlink'; 		// REQUIRED, second Type
			$row[] = $name; 			// REQUIRED, Third Name
			$row[] = $result['from_user'];
			$row[] = $date;
			$row[] = $source;
			$row[] = $this->getTweetURL( $result['from_user'], $result['id'] );
			$row[] = 'text/hyperlink';
			$row[] = ''.$this->getPublication();  // Publication required by Lucina
			$row[] = 0;// IssueId
			
			$rows[]=$row;
		}
		PerformanceProfiler::stopProfile( 'Twitter Search - doSearch', 3 );
		return $rows;
	}
	
	private function getTweetURL( $from, $tweetID )
	{
		$tweetID = number_format( $tweetID, 0, '', '' ); // remove dots and commas
		return "http://www.twitter.com/$from/statuses/$tweetID";
	}

	private function getTrendsColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 				'ID', 				'string' 	); // Required as 1st
		$cols[] = new Property( 'Type', 			'Type', 			'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name', 			'Name', 			'string' 	); // Required as 3rd
		$cols[] = new Property( 'Description', 		'Description',		'string'	);
		$cols[] = new Property( 'DocumentID', 		'DocumentID', 		'string'	);
		$cols[] = new Property( 'Format', 			'Format',	 		'string'	);	// Needed to make double click open new tab
		$cols[] = new Property( 'PublicationId', 	'PublicationId', 	'string' 	);	// Required by Content Station
        $cols[] = new Property( 'IssueId',			'IssueId',			'string' 	);	// Required by Content Station
		return $cols;
	}

	private function doTrendsSearch( &$firstEntry, &$totalEntries )
	{
		PerformanceProfiler::startProfile( 'Twitter Search - doTrendsSearch', 3 );
		require_once 'Zend/Service/Twitter/Search.php';
		$twitter_search = new Zend_Service_Twitter_Search('json');
		$search_results = $twitter_search->trends();
		$results = $search_results['trends'];

		// API returns top 10, that's all, so no paging.		
		$firstEntry 	= 1; 	
		$numResults 	= count($results);
		$totalEntries 	= count($results);

		$rows = array();
		$i = 1;
		$sep = '_|_';
		foreach( $results as $result ) {
			$row = array();

			// We use name + url +trend separate by _|_ That's all we got...
			// The complete ID is URL encoded to prevent problems with local files in Smart Connection
			$ourName = $i++.'. Twitter Trend - '.date('D, j M Y H.i');
			$alienId = TWS_CONTENTSOURCEPREFIX.urlencode( $ourName.$sep.$result['url'].$sep.$result['name'] );

			$row[] = $alienId;			// REQUIRED, first ID.
			$row[] = 'Hyperlink'; 		// REQUIRED, second Type
			$row[] = $ourName;			// REQUIRED, Third Name
			$row[] = $result['name'];
			$row[] = $result['url'];
			$row[] = 'text/hyperlink';
			$row[] = ''.$this->getPublication();  // Publication required by Lucina
			$row[] = 0;// IssueId
			
			$rows[]=$row;
		}
		PerformanceProfiler::stopProfile( 'Twitter Search - doTrendsSearch', 3 );
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

		if( Zend_Registry::isRegistered( 'TWS-Publication' ) ) {
			$publication = Zend_Registry::get( 'TWS-Publication' );
		} else {
			$pubs = BizPublication::getPublications( $username );
			// Default to first, look next if we can find one with the configured name:
			$pubFound = $pubs[0];
			foreach( $pubs as $pub ) {
				if( $pub->Name == TWS_BRAND ) {
					$pubFound = $pub;
					break;
				}
			}
			$publication 	= new Publication($pubFound->Id);
			Zend_Registry::set( 'TWS-Publication', $publication );
		}

		if( Zend_Registry::isRegistered( 'TWS-Category' ) ) {
			$category = Zend_Registry::get( 'TWS-Category' );
		} else {
			$categories = BizPublication::getSections( $username, $publication->Id );
			// Default to first, look next if we can find one with the configured name:
			$catFound = $categories[0];
			foreach( $categories as $cat ) {
				if( $cat->Name == TWS_CATEGORY ){
					$catFound = $cat;
					break;
				}
			}
			$category 	= new Publication($catFound->Id);
			Zend_Registry::set( 'TWS-Category', $category );
		}

		if( Zend_Registry::isRegistered( 'TWS-Status' ) ) {
			$category = Zend_Registry::get( 'TWS-Status' );
		} else {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$states=BizWorkflow::getStates($username, $publication->Id, null /*issue*/, $category->Id, 'Hyperlink' );
			// Default to first, look next if we can find one with the configured name:
			$statFound = $states[0];
			foreach( $states as $stat ) {
				if( $stat->Name == TWS_STATUS) {
					$statFound = $stat;
					break;
				}
			}
			$status 	= new State($statFound->Id);
			Zend_Registry::set( 'TWS-Status', $status );
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

	private function fillMetaData( &$meta, $alienID )
	{		
		// All meta data that we have it encapsulated in the id, split it here:
		$sep = '_|_';
		$id 		= substr( urldecode($alienID), strlen(TWS_CONTENTSOURCEPREFIX) ); // Remove prefix from alienID
		$metaVars 	= explode ( $sep, $id );
		
		if( count($metaVars) == 3 ) {
			// it's a trend item:
			$rawName	= $metaVars[0];
			$url		= $metaVars[1];
			$trend		= $metaVars[2];
			$date 		= '';
			$from 		= '';
			$tweetID	= '';
			$source	= '';
		} else {
			// It's a tweet from search
			$tweetID	= $metaVars[0];
			$date		= $metaVars[1];
			$from		= $metaVars[2];
			$rawName	= $metaVars[3];
			$source		= $metaVars[4];
			$url = $this->getTweetURL( $from, $tweetID );
			$trend = '';
		}
		// Remove all invalid characters from name:
		$sDangerousCharacters = "`~!@#$%^*\\|;:'<>/?";
		$sDangerousCharacters .= '"'; // add double quote to dangerous charaters
		$name = '';
		for( $i=0; $i < strlen($rawName); ++$i ) {
			if( strpos($sDangerousCharacters,$rawName[$i]) === FALSE ) {
				// valid character, add to name:
				$name .= $rawName[$i];
			}
		}

		// Get defult Pub, Category and Status
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext( $publication, $category, $status );
		
		if( !$meta->BasicMetaData ) $meta->BasicMetaData = new BasicMetaData();
		$meta->BasicMetaData->ID 			= $alienID;
		$meta->BasicMetaData->DocumentID 	= $url;
		$meta->BasicMetaData->Type			= 'Hyperlink';
		$meta->BasicMetaData->ContentSource	= TWS_CONTENTSOURCEID;  // If you don't fill this, the created object is not a 'shadow', meaning that it has no link to the contentsource.
		if( !$meta->BasicMetaData->Name ) {
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
			$meta->ContentMetaData =  new ContentMetaData(  
									null, null,				// description (string), description author (string)
									null,					// keywords (array of string) 
								 	null,					// Slugline (string)
								 	null,					// Format (mimestype string), for demo we assume it's always jpg
								 	null,					// columns (int)
								 	null,					// width (double), height (double)
								 	null,					// Dpi (unsignedint)
								 	null, null, null, null,	// 4 Length (unsigned int): words, chars, para's, lines
								 	null,					// Plain content (string)
								 	null,					// File size (unsignedint)
								 	null,					// colorspace (string)
								 	null,					// HighResFile for ads, not support in this context (string)
								 	null, null, null, null, null	// Video/Audio Encoding (string), Compression (string), KeyFrameEveryFrames (unsigned int), Channels (string), AspectRatio (string)
								 	);	
		}
		// ensure the following content fields are set, also when contentmeta data already available:
		$meta->ContentMetaData->Format		 = 'text/hyperlink';
		if( $trend != '' ) {
			$meta->ContentMetaData->Description	 = $trend;
		}
		$meta->ContentMetaData->FileSize	 = 0;
		$meta->ContentMetaData->Width		 = null;
		$meta->ContentMetaData->Height		 = null;
		$meta->ContentMetaData->PlainContent = null;
		
		if( !$meta->RightsMetaData ) $meta->RightsMetaData = new RightsMetaData( null,	null, null );	// copyright marked (boolean), copyright (string), copyright url (string)
		
		if( !$meta->SourceMetaData ) $meta->SourceMetaData = new SourceMetaData( null,	null, null );	// credit (string), source (string), author (string)
		if( empty($meta->SourceMetaData->Source) && $source != '') {
			$meta->SourceMetaData->Source = $source;
		}
		if( empty($meta->SourceMetaData->Author) && $from != '' ) {
			$meta->SourceMetaData->Author = $from;
		}
		
		if( !$meta->WorkflowMetaData ) {
			$meta->WorkflowMetaData = new WorkflowMetaData( 
									null, null,			// Deadline (datetime), Urgency (string)
									null, null,	// Modifier (string), Modified (datetime)
									null, null,			// Creator (string), Created (datetime)
								  	null, 				// Version specific comment (string)
								  	null, null, 		// Workflow status, routeto (string)
								  	null, 				// lockedby (string)
								  	null,				// version (string)
								  	null,				// Soft deadline for pre-notify warning (datetime)
								  	null );				// rating
		}
		// ensure the following content fields are set, also when contentmeta data already available:
		if( $date != '' ) {
			$meta->WorkflowMetaData->Modified	= $date;
		}
		if( empty($meta->WorkflowMetaData->State) ) {
			$meta->WorkflowMetaData->State	= $status;
		}
	}

	public function isInstalled()
	{
		return true;
	}
	
	public function runInstallation()
	{
	}
}
