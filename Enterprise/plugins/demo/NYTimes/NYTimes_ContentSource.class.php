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
	The NYT Article Search API provides access to all NYT articles published since 1/1/1981.
	
	The API allows to search on keyword and provides faceted search. The API doest not provide
	the ability to get the content of an article, all it provides is sometime like a slugline (called body) 
	and a URL to the article on the nytimes.com.
	
	Because we cannot get the article content, we present the NYT articles as hyperlinks. This way 
	Content Station will show the Article on the web in the preview. D&D to a dossier will create a
	hyperlink inside the Enterprise database.
*/

require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';

require_once dirname(__FILE__) . '/config.php';

class NYTimes_ContentSource extends ContentSource_EnterpriseConnector
{
	private $pubId = null;

	final public function getContentSourceId( )
	{
		return NYT_CONTENTSOURCEID;
	}
	
	final public function getQueries( )
	{
		$queries = array();
		
		if( NYT_API_NEWSWIRE_KEY != '' ) {
			$queries[] = new NamedQueryType( NYT_QUERY_NEWSWIRE_NAME, array() );
		}
		if( NYT_API_ARTICLESEARCH_KEY != '' ) {
			$queryParamSearch = new PropertyInfo( 
				'Search', 'Search', // Name, Display Name
				null,				// Category, not used
				'string',			// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
				'',					// Default value
				null,				// value list
				null, null, null,	// min value, max value,max length
				null, null			// parent value (not used), dependent property (not used)
				);
	
			$queries[] = new NamedQueryType( NYT_QUERY_ARTICLESEARCH_NAME, array($queryParamSearch) );
		}

		return $queries;
	}
	
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// keep code analyzer happy for unused params:
		// maxEntries is ignored because the API just returns 10 or 20 entries per call
		$query=$query; $maxEntries=$maxEntries; $order=$order;
		
		LogHandler::Log('NYTimes', 'DEBUG', 'doNamedQuery called for search: '.$query );

		if( $query == NYT_QUERY_ARTICLESEARCH_NAME ) {	
			// Create array with column definitions
			$cols = $this->getArticleSearchColumns();
	
			// Call NYT API
			$totalEntries = 0;
			$facets = null;
			$rows = $this->doArticleSearch( $params, $firstEntry, $totalEntries, $facets );
		} else { // NYT_QUERY_NEWSWIRE_NAME
			// Create array with column definitions
			$cols = $this->getNewswireColumns();
	
			// Call NYT API
			$totalEntries = 0;
			$facets = null;
			$rows = $this->doNewswireSearch( $firstEntry, $totalEntries );
		}
		
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, $firstEntry, count($rows), $totalEntries, null, $facets );
	}
	
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		PerformanceProfiler::startProfile( 'NYTimes - getAlienObject', 3 );
		$lock=$lock ; // we don't use this argument, keep analyzer happy
		LogHandler::Log('NYTimes', 'DEBUG', "getAlienObject called for $alienID - $rendition" );

		$files = array();
		$meta = new MetaData();
		$this->fillMetaData( $meta, $alienID );
		$object = new Object( 	$meta,				// meta data
							 	array(), null,		// relations, pages
			 					$files, 			// Files array of attachment
 								null, null, null	// messages, elements, targets
							);

		PerformanceProfiler::stopProfile( 'NYTimes - getAlienObject', 3 );
		return $object;
	}
	
	final public function deleteAlienObject( $alienID )
	{
		$msg = "Cannot delete articles from NY Times";
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
		
	private function getArticleSearchColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 				'ID', 				'string' 	); // Required as 1st
		$cols[] = new Property( 'Type', 			'Type', 			'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name', 			'Name', 			'string' 	); // Required as 3rd
		$cols[] = new Property( 'By', 				'By', 				'string' 	); 
		$cols[] = new Property( 'Modified', 		'Modified', 		'datetime'	);
		$cols[] = new Property( 'Source', 			'Source', 			'string'	);
		$cols[] = new Property( 'Words', 			'Words', 			'int' 		);
		$cols[] = new Property( 'Slugline', 		'Abstract', 		'string'	);
		$cols[] = new Property( 'DocumentID', 		'DocumentID', 		'string'	);
		$cols[] = new Property( 'Format', 			'Format',	 		'string'	);	// Needed to make double click open new tab
		$cols[] = new Property( 'PublicationId', 	'PublicationId', 	'string' 	);	// Required by Content Station
        $cols[] = new Property( 'IssueId',			'IssueId',			'string' 	);	// Required by Content Station
		return $cols;
	}
	
	private function doArticleSearch( $params, &$firstEntry, &$totalEntries, &$enterpriseFacets )
	{
		$search = $params[0]->Value;
		
		// Facets to narrow are passed as params, so get them into the search string
		for( $i=1; $i < count($params); ++$i ) {
			$search .= ' '.strtolower($params[$i]->Property).':['.$params[$i]->Value.']';
		}
		$search = urlencode( $search );
		
		$rows = array();

		require_once 'Zend/Http/Client.php';
		require_once 'Zend/Json.php';

		$apiKey = NYT_API_ARTICLESEARCH_KEY;
		$reqfacets = 'nytd_des_facet,nytd_org_facet,nytd_section_facet'; // other facets possible for organizations: nytd_org_facet and people: nytd_per_facet
		$fields = 'body,byline,date,title,url,word_count,source_facet';
		$firstEntry = round(($firstEntry+1)/10);	// API works in batches of 10

		$url = "http://api.nytimes.com/svc/search/v1/article?format=json&query=$search&offset=$firstEntry&facets=$reqfacets&api-key=$apiKey&fields=$fields";
		LogHandler::Log('NYTimes', 'DEBUG', 'NYT Article Search API call: '.$url );
		
		$http = new Zend_Http_Client( $url );
		$response = $http->request( Zend_Http_Client::GET );
		if ($response->isSuccessful()) {
			$respArray = Zend_Json::decode($response->getBody());
			$returnFacets 	= $respArray['facets'];
			// Walk thru facets to create into Enterprise facets
			$enterpriseFacets = array();
			foreach( $returnFacets as $facetKey => $facetValues ) {
				$facetItems = array();
				// Walk thru $facetValues
				foreach( $facetValues as $facetValue ) {
					$facetItems[] = new FacetItem( $facetValue['term'], $facetValue['term'], $facetValue['count'] );
				}
				switch( $facetKey ) {
					case 'nytd_des_facet':
						$facetName = 'Description';
						break;
					case 'nytd_geo_facet':
						$facetName = 'Geography';
						break;
					case 'nytd_org_facet':
						$facetName = 'Organization';
						break;
					case 'nytd_per_facet':
						$facetName = 'Person';
						break;
					case 'nytd_section_facet':
						$facetName = 'Section';
						break;
					}
				$enterpriseFacets[] = new Facet( $facetKey, $facetName, $facetItems );
			}
			
			$firstEntry 	= $respArray['offset']*10+1; 	// API works in batches of 10
			$results 		= $respArray['results'];
			$totalEntries 	= $respArray['total'];
			
			// Walk thru results and turn them into rows:
			foreach( $results as $result ) {
				$row = array();
	
				// We use URL + title + date + source as the id separate by _|_
				// We need to do this because the NYT API does not offer functionality to get a specific
				// article, just searching on keywords. So all metadata that we have, should be put in the 
				// id so that we get it again when are called for GetAlien or CreateShadow
				// The complete ID is URL encoded to prevent problems with local files in Smart Connection
				$sep = '_|_';
				$date = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, substr( $result['date'], 4, 2 ), substr( $result['date'], 6, 2 ), substr( $result['date'], 0, 4 ) ) );
				$alienId = NYT_CONTENTSOURCEPREFIX.urlencode( $result['url'].$sep.$result['title'].$sep.$date.$sep.$result['source_facet'] );
	
				$row[] = $alienId;			// REQUIRED, first ID.
				$row[] = 'Hyperlink'; 		// REQUIRED, second Type
				$row[] = $result['title']; 	// REQUIRED, Third Name
				$row[] = array_key_exists( 'byline', $result) ? $result['byline'] : '';	// Not alyways available
				$row[] = $date;
				$row[] = $result['source_facet'];
				$row[] = $result['word_count'];
				$row[] = html_entity_decode($result['body'], ENT_COMPAT, 'UTF-8');	// TODO - this does't decode correct, m-dashes get lost...
				$row[] = $result['url'];
				$row[] = 'text/hyperlink';
				$row[] = ''.$this->getPublication();  // Publication required by Lucina
				$row[] = 0;// IssueId
				
				$rows[]=$row;
			}
		} else {
			$msg = $response->getBody();
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
		return $rows;
	}

	private function getNewswireColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 				'ID', 				'string' 	); // Required as 1st
		$cols[] = new Property( 'Type', 			'Type', 			'string' 	); // Required as 2nd
		$cols[] = new Property( 'Name', 			'Name', 			'string' 	); // Required as 3rd
		$cols[] = new Property( 'By', 				'By', 				'string' 	); 
		$cols[] = new Property( 'NYTSection',		'Section', 			'string' 	); 
		$cols[] = new Property( 'SubSection', 		'SubSection', 		'string' 	); 
		$cols[] = new Property( 'Created',	 		'Created', 			'datetime'	);
		$cols[] = new Property( 'Source', 			'Source', 			'string'	);
		$cols[] = new Property( 'Slugline', 		'Abstract', 		'string'	);
		$cols[] = new Property( 'ArticleType', 		'Type', 			'string' 	);
		$cols[] = new Property( 'Modified', 		'Modified', 		'datetime'	);
		$cols[] = new Property( 'DocumentID', 		'DocumentID', 		'string'	);
		$cols[] = new Property( 'Format', 			'Format',	 		'string'	);	// Needed to make double click open new tab
		$cols[] = new Property( 'PublicationId', 	'PublicationId', 	'string' 	);	// Required by Content Station
        $cols[] = new Property( 'IssueId',			'IssueId',			'string' 	);	// Required by Content Station
		return $cols;
	}

	private function doNewswireSearch( &$firstEntry, &$totalEntries )
	{
		$rows = array();

		require_once 'Zend/Http/Client.php';

		$apiKey = NYT_API_NEWSWIRE_KEY;

		$reqFirstEntry = round(($firstEntry+1)/20); // API works in batches of up to 20
		$url = "http://api.nytimes.com/svc/news/v2/all/recent.sphp?api-key=$apiKey&offset=$reqFirstEntry";
		LogHandler::Log('NewYorkTimes', 'DEBUG', 'NYT Newswire API call: '.$url );
		
		$http = new Zend_Http_Client( $url );
		$response = $http->request( Zend_Http_Client::GET );
		if ($response->isSuccessful()) {
			// The Newswire API offers 3 response formats: JSON, XML and serialized PHP.
			// The latter is obviously easiest for us, so guess what we're using...
			$respArray = unserialize($response->getBody());

			// The API does not return the total number of entries. So we do a little trick. If we 
			// get the maximum of return results (20) we assume there is another page, so we say total 
			// entries is one higher than the last item we got. Getting that next page could lead to yet
			// another extra page etc.
			$firstEntry 	+= 1; 	
			$numResults 	= $respArray['num_results'];
			$totalEntries 	= ($numResults == 20) ? $firstEntry + $numResults : $firstEntry + $numResults - 1 ;
			$results 		= $respArray['results'];

			// Walk thru results and turn them into rows:
			foreach( $results as $result ) {
				$row = array();
		
				// We use URL + title + date + source as the id separate by _|_
				// This API does support to get metadata for a specific article, but this way we keep 
				// logic same as with Article Search API
				$sep = '_|_';
				$alienId = NYT_CONTENTSOURCEPREFIX. urlencode($result['url'].$sep.$result['headline'].$sep.str_replace ( ' ' , 'T' , $result['updated'] ).$sep.$result['source']);
	
				$row[] = $alienId;			// REQUIRED, first ID.
				$row[] = 'Hyperlink'; 			// REQUIRED, second Type
				$row[] = $result['headline']; 	// REQUIRED, Third Name
				$row[] = $result['byline'];
				$row[] = $result['section'];
				$row[] = $result['subsection'];
				$row[] = str_replace ( ' ' , 'T' , $result['created'] );
				$row[] = $result['source'];
				$row[] = $result['summary'];
				$row[] = $result['type'];
				$row[] = str_replace ( ' ' , 'T' , $result['updated'] );
				$row[] = $result['url'];
				$row[] = 'text/hyperlink';
				$row[] = ''.$this->getPublication();  // Publication required by Lucina
				$row[] = 0;// IssueId
				
				$rows[]=$row;
			}
		} else {
			$msg = $response->getBody();
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
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

		if( Zend_Registry::isRegistered( 'NYT-Publication' ) ) {
			$publication = Zend_Registry::get( 'NYT-Publication' );
		} else {
			$pubs = BizPublication::getPublications( $username );
			// Default to first, look next if we can find one with the configured name:
			$pubFound = $pubs[0];
			foreach( $pubs as $pub ) {
				if( $pub->Name == NYT_BRAND ) {
					$pubFound = $pub;
					break;
				}
			}
			$publication 	= new Publication($pubFound->Id);
			Zend_Registry::set( 'NYT-Publication', $publication );
		}

		if( Zend_Registry::isRegistered( 'NYT-Category' ) ) {
			$category = Zend_Registry::get( 'NYT-Category' );
		} else {
			$categories = BizPublication::getSections( $username, $publication->Id );
			// Default to first, look next if we can find one with the configured name:
			$catFound = $categories[0];
			foreach( $categories as $cat ) {
				if( $cat->Name == NYT_CATEGORY ){
					$catFound = $cat;
					break;
				}
			}
			$category 	= new Publication($catFound->Id);
			Zend_Registry::set( 'NYT-Category', $category );
		}

		if( Zend_Registry::isRegistered( 'NYT-Status' ) ) {
			$category = Zend_Registry::get( 'NYT-Status' );
		} else {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$states=BizWorkflow::getStates($username, $publication->Id, null /*issue*/, $category->Id, 'Hyperlink' );
			// Default to first, look next if we can find one with the configured name:
			$statFound = $states[0];
			foreach( $states as $stat ) {
				if( $stat->Name == NYT_STATUS) {
					$statFound = $stat;
					break;
				}
			}
			$status 	= new State($statFound->Id);
			Zend_Registry::set( 'NYT-Status', $status );
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
		$id 		= substr( urldecode($alienID), strlen(NYT_CONTENTSOURCEPREFIX) ); // Remove prefix from alienID
		$metaVars 	= explode ( $sep, $id );
		$url		= $metaVars[0];
		$rawName	= $metaVars[1];
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

		$date		= $metaVars[2];
		$source		= $metaVars[3];

		// Get defult Pub, Category and Status
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext( $publication, $category, $status );
		
		if( !$meta->BasicMetaData ) $meta->BasicMetaData = new BasicMetaData();
		$meta->BasicMetaData->ID 	= $alienID;
		$meta->BasicMetaData->DocumentID 	= $url;
		$meta->BasicMetaData->Type			= 'Hyperlink';
		$meta->BasicMetaData->ContentSource	= NYT_CONTENTSOURCEID;  // If you don't fill this, the created object is not a 'shadow', meaning that it has no link to the contentsource.
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
		$meta->ContentMetaData->FileSize	 = 0;
		$meta->ContentMetaData->Width		 = null;
		$meta->ContentMetaData->Height		 = null;
		$meta->ContentMetaData->PlainContent = null;
		
		if( !$meta->RightsMetaData ) $meta->RightsMetaData = new RightsMetaData( null,	null, null );	// copyright marked (boolean), copyright (string), copyright url (string)
		
		if( !$meta->SourceMetaData ) $meta->SourceMetaData = new SourceMetaData( null,	null, null );	// credit (string), source (string), author (string)
		if( empty($meta->SourceMetaData->Source) ) {
			$meta->SourceMetaData->Source = $source;
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
		$meta->WorkflowMetaData->Modified	= $date;
		if( empty($meta->WorkflowMetaData->State) ) {
			$meta->WorkflowMetaData->State		= $status;
		}
	}

	public function isInstalled()
	{
		return NYT_API_ARTICLESEARCH_KEY != '' || NYT_API_NEWSWIRE_KEY != '';
	}
	
	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			$msg = 'NY Times API key not defined in plugin config.php';
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
	}
}
