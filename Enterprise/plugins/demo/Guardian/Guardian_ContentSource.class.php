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
class Guardian_ContentSource extends ContentSource_EnterpriseConnector
{
	private $pubId = null;

	final public function getContentSourceId( )
	{
		return GNL_CONTENTSOURCEID;
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
	
		$queries[] = new NamedQueryType( GNL_QUERY_NAME, array($queryParamSearch) );

		return $queries;
	}
	
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// keep code analyzer happy for unused params:
		// maxEntries is ignored because the API just returns 10 or 20 entries per call
		$maxEntries=$maxEntries; $order=$order;
		
		LogHandler::Log('Guardian', 'DEBUG', 'doNamedQuery called for search: '.$query );
		PerformanceProfiler::startProfile( 'Guardian - Search', 3 );

		// Create array with column definitions
		$cols = $this->getSearchColumns();
	
		// Call Guardian API
		$totalEntries = 0;
		$facets ='';
		$rows = $this->doSearch( $params, $firstEntry, $totalEntries, $facets);

		PerformanceProfiler::stopProfile( 'Guardian - Search', 3 );
		
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, $firstEntry, count($rows), $totalEntries, null, $facets );
	}
	
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		LogHandler::Log('Guardian', 'DEBUG', "getAlienObject called for $alienID - $rendition" );
		PerformanceProfiler::startProfile( 'Guardian - getAlienObject', 3 );
		$lock=$lock ; // we don't use this argument, keep analyzer happy

		$id 	= substr( urldecode($alienID), strlen(GNL_CONTENTSOURCEPREFIX) ); // Remove prefix from alienID

		$metaVars 	= explode ( ',', $id );
		$gnlID		= $metaVars[0];
		$name		= $metaVars[1];
		$date		= $metaVars[2];
		$meta = new MetaData();

		// We may only call GNL once per second. Content Station will do 2 calls to get a preview:
		// GetObject (for preview rendition) and GetDialog (which results in getObject for rendition none)
		// We can only do one of them, that's why we don't call GNL for rendition none, instead we use the info 
		// from the alien id
		if( $rendition == 'none' ) {
			$files = array();
			$this->fillMetaData( $alienID, $gnlID, $meta, $name, $date, null, null, null, 0 );
		} else {
			$url = "http://api.guardianapis.com/content/item/{$gnlID}?api_key=".GNL_API_KEY;
	
			$response = $this->gnlRequest( $url, 'Item request' );
			$content = new SimpleXMLElement($response);
			$body = $content->{'type-specific'}->body;
			$html 	 = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			$html 	.= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">'."\n";
			$html 	.= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
			$html 	.= '<head><title>'.$content->headline."</title></head>\n";
			$html 	.= '<body>'.$body.'</body></html>';
			
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$attachment = new Attachment('native', 'text/html');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($html, $attachment);
			$files = array($attachment);

			$this->fillMetaData( $alienID, $gnlID, $meta, $content->headline, $content->{'publication-date'}, $content->{'type-specific'}->body, $content->{'link-text'}, $content->byline, strlen($html) );
		}
		$object = new Object( 	$meta,				// meta data
								array(), null,		// relations, pages
								$files, 			// Files array of attachment
								null, null, null );	// messages, elements, targets

		PerformanceProfiler::stopProfile( 'Guardian - getAlienObject', 3 );
		return $object;
	}
	
	final public function deleteAlienObject( $alienID )
	{
		$alienID = $alienID; // Make analyzer happy
		$msg = "Cannot delete articles from Guardian";
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
		LogHandler::Log('Guardian', 'DEBUG', "createShadowObject called for $alienID" );
		PerformanceProfiler::startProfile( 'Guardian - createShadowObject', 3 );

		$id 	= substr( urldecode($alienID), strlen(GNL_CONTENTSOURCEPREFIX) ); // Remove prefix from alienID
		$metaVars 	= explode ( ',', $id );
		$gnlID		= $metaVars[0];
//		$name		= $metaVars[1];
//		$date		= $metaVars[2];

		$url = "http://api.guardianapis.com/content/item/{$gnlID}?api_key=".GNL_API_KEY;
	
		$response = $this->gnlRequest( $url, 'Item request' );
		$content = new SimpleXMLElement($response);
		$body = $content->{'type-specific'}->body;
		$html 	 = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$html 	.= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "DTD/xhtml1-strict.dtd">'."\n";
		$html 	.= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
		$html 	.= '<head><title>'.$content->headline."</title></head>\n";
		$html 	.= '<body>'.$body.'</body></html>';
		
		require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
		$attachment = new Attachment('native', 'text/html');
		$transferServer = new BizTransferServer();
		$transferServer->writeContentToFileTransferServer($html, $attachment);
		$files = array($attachment);

		// In case of a copy the user already filled in an object, we use that
		// For a real-life content source this would be further filled with metadata from the content source
		if( $destObject ) {
			$meta = $destObject->MetaData;
		} else {
			$meta = new MetaData();
			$destObject = new Object( $meta, array(), null, null, null, null, null );
		}
		$this->fillMetaData( $alienID, $gnlID, $meta, $content->headline, $content->{'publication-date'}, $content->{'type-specific'}->body, $content->{'link-text'}, $content->byline, strlen($html) );
		$destObject->Files = $files;
		PerformanceProfiler::stopProfile( 'Guardian - createShadowObject', 3 );
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
		$cols[] = new Property( 'By', 				'By', 				'string' 	); 
		$cols[] = new Property( 'GNLSection',		'Section', 			'string' 	); 
		$cols[] = new Property( 'Published',	 	'Published', 		'datetime'	);
		$cols[] = new Property( 'Slugline', 		'Snippet', 			'string'	);
		$cols[] = new Property( 'Format', 			'Format',	 		'string'	);	// Needed to make double click open new tab
		$cols[] = new Property( 'PublicationId', 	'PublicationId', 	'string' 	);	// Required by Content Station
        $cols[] = new Property( 'IssueId',			'IssueId',			'string' 	);	// Required by Content Station
		return $cols;
	}
	
	private function doSearch( $params, &$firstEntry, &$totalEntries, &$enterpriseFacets )
	{
		$search = urlencode($params[0]->Value);
		// Facets to narrow are passed as params, so get them into the search string
		for( $i=1; $i < count($params); ++$i ) {
			$search .= '&filter='.urlencode($params[$i]->Value);
		}
		$rows = array();

		$url = "http://api.guardianapis.com/content/search?q={$search}&api_key=".GNL_API_KEY.'&count='.GNL_ITEMS_PER_PAGE.'&start-index='.$firstEntry;

		$response = $this->gnlRequest( $url, 'Search request' );
		
		$xml = new SimpleXMLElement($response);
		$results	= $xml->results;
		foreach( $results->content as $content ) {
			$row = array();

			// The Guardian API only allows 1 request per second. Content Station fires 2 requests for a preview: GetDialog & GetObject
			// To make sure we can do GetDialog without calling the Guardian API we pack some meta data into the alien ID, comma separated
			// Alien id is urlencoded, as it's used by Smart Connection to generate local file names
			$row[] = GNL_CONTENTSOURCEPREFIX.urlencode($content['id'].','.$content->headline.','.$content->{'publication-date'});		// REQUIRED, first ID.
			$row[] = 'Article'; 			// REQUIRED, second Type
			$row[] = $content->headline; 	// REQUIRED, Third Name
			$row[] = $content->byline;
			$row[] = html_entity_decode( $content->{'section-name'}, ENT_COMPAT, 'UTF-8');
			$row[] = $content->{'publication-date'};
			$row[] = substr( strip_tags( $content->{'type-specific'}->body ), 0, 250 );		// Use first 250 characters from body as slugline.
			$row[] = 'text/html';
			$row[] = ''.$this->getPublication();  // Publication required by Content Station
			$row[] = 0;// IssueId
			
			$rows[]=$row;
		}
		
		// GNL has one set of facets called filter
		$filters = $xml->filters;
		$enterpriseFacets = array();
		$facetItems = array();
		foreach( $filters->tag as $tag ) {
			$facetItems[] = new FacetItem( $tag['filter'], $tag['name'], $tag['count'] );
		}
		$enterpriseFacets[] = new Facet( 'Filters', 'Filters', $facetItems );
		
		$firstEntry 	= $xml['start-index']+1;
		$results 		= count($results->content);
		$totalEntries 	= $xml['count'];

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

		if( Zend_Registry::isRegistered( 'GNL-Publication' ) ) {
			$publication = Zend_Registry::get( 'GNL-Publication' );
		} else {
			$pubs = BizPublication::getPublications( $username );
			// Default to first, look next if we can find one with the configured name:
			$pubFound = $pubs[0];
			foreach( $pubs as $pub ) {
				if( $pub->Name == GNL_BRAND ) {
					$pubFound = $pub;
					break;
				}
			}
			$publication 	= new Publication($pubFound->Id);
			Zend_Registry::set( 'GNL-Publication', $publication );
		}

		if( Zend_Registry::isRegistered( 'GNL-Category' ) ) {
			$category = Zend_Registry::get( 'GNL-Category' );
		} else {
			$categories = BizPublication::getSections( $username, $publication->Id );
			// Default to first, look next if we can find one with the configured name:
			$catFound = $categories[0];
			foreach( $categories as $cat ) {
				if( $cat->Name == GNL_CATEGORY ){
					$catFound = $cat;
					break;
				}
			}
			$category 	= new Publication($catFound->Id);
			Zend_Registry::set( 'GNL-Category', $category );
		}

		if( Zend_Registry::isRegistered( 'GNL-Status' ) ) {
			$category = Zend_Registry::get( 'GNL-Status' );
		} else {
			require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
			$states=BizWorkflow::getStates($username, $publication->Id, null /*issue*/, $category->Id, 'Article' );
			// Default to first, look next if we can find one with the configured name:
			$statFound = $states[0];
			foreach( $states as $stat ) {
				if( $stat->Name == GNL_STATUS) {
					$statFound = $stat;
					break;
				}
			}
			$status 	= new State($statFound->Id);
			Zend_Registry::set( 'GNL-Status', $status );
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

	private function fillMetaData( $alienID, $gnlID, &$meta, $name, $date, $body, $linkText, $byLine, $fileSize )
	{		
		// Get default Pub, Category and Status
		$publication = ''; $category = ''; $status = '';
		$this->getEnterpriseContext( $publication, $category, $status );
		
		if( !$meta->BasicMetaData ) $meta->BasicMetaData = new BasicMetaData();
		$meta->BasicMetaData->ID 			= $alienID;
		$meta->BasicMetaData->DocumentID 	= $gnlID;
		$meta->BasicMetaData->Type			= 'Article';
		$meta->BasicMetaData->ContentSource	= GNL_CONTENTSOURCEID;  // If you don't fill this, the created object is not a 'shadow', meaning that it has no link to the contentsource.
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
		
		$plainContent = $body ? strip_tags($body) : '';
		if( !$meta->ContentMetaData ) {
			$meta->ContentMetaData =  new ContentMetaData(  
									$linkText ? $linkText : '', $byLine ? $byLine : '',		// description (string), description author (string)
									null,					// keywords (array of string) 
								 	substr( $plainContent, 0, 250 ),	// Slugline (string)
								 	null,					// Format (mimestype string), for demo we assume it's always jpg
								 	null,					// columns (int)
								 	null,					// width (double), height (double)
								 	null,					// Dpi (unsignedint)
								 	str_word_count($plainContent), strlen($plainContent), $body ? substr_count ( $body, '<p>') : 0, null,	// 4 Length (unsigned int): words, chars, para's, lines
								 	null,					// Plain content (string)
								 	null,					// File size (unsignedint)
								 	null,					// colorspace (string)
								 	null,					// HighResFile for ads, not support in this context (string)
								 	null, null, null, null, null	// Video/Audio Encoding (string), Compression (string), KeyFrameEveryFrames (unsigned int), Channels (string), AspectRatio (string)
								 	);	
		}
		// ensure the following content fields are set, also when contentmeta data already available:
		$meta->ContentMetaData->Format		 = 'text/html';
		$meta->ContentMetaData->FileSize	 = $fileSize;
		$meta->ContentMetaData->PlainContent = $plainContent;
		
		if( !$meta->RightsMetaData ) $meta->RightsMetaData = new RightsMetaData( true,	'Guardian News and Media Limited', 'guardian.co.uk' );	// copyright marked (boolean), copyright (string), copyright url (string)
		
		if( !$meta->SourceMetaData ) $meta->SourceMetaData = new SourceMetaData( null,	null, null );	// credit (string), source (string), author (string)
		if( empty($meta->SourceMetaData->Source) ) {
			$meta->SourceMetaData->Credit = 'Guardian News and Media Limited';
			$meta->SourceMetaData->Source = 'Guardian News and Media Limited';
			if( $byLine ) {
				$meta->SourceMetaData->Author = $byLine;
			}
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

	/**
		- gnlRequest -
		
		Executes the GNL request and passes back the response.
		
		The reqDescription is used to profile performance.
		
		Throws a BizException in case of error
	*/
	private function gnlRequest( $url, $reqDescription )
	{
		LogHandler::Log('Guardian', 'DEBUG', 'Guardian '.$reqDescription.': '.$url );
		require_once 'Zend/Http/Client.php';
		$http = new Zend_Http_Client( $url );
		PerformanceProfiler::startProfile( 'Guardian - '.$reqDescription, 3 );
		$response = $http->request( Zend_Http_Client::GET );
		PerformanceProfiler::stopProfile( 'Guardian - '.$reqDescription, 3 );
		if ($response->isSuccessful()) {
			return $response->getBody();
		} else {
			$msg = $response->getBody();
			if( strstr( $msg, '403 Developer Over Qps') !== FALSE ) {
				$msg = "Please slow-down...\n\nThe Guardian only allows 1 request per second.";
			}
			throw new BizException( $msg, 'Server', $msg, $msg );		
		}
	}


	public function isInstalled()
	{
		return GNL_API_KEY != '';
	}
	
	public function runInstallation()
	{
		if( !$this->isInstalled() ) {
			$msg = 'Guardian API key not defined in plugin config.php';
			throw new BizException( $msg, 'Server', $msg, $msg );
		}
	}
}
