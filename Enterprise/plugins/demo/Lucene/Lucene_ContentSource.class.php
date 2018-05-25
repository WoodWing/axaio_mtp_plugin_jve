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
 *
 * Lucene Search integration - The Content Source connector
 *
 * This Content Source connector uses a folder (LUCENE_DIRECTORY) as the content repository.
 * All sub-folders are presented as query options. 
 */

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

// Enterprise includes:
require_once BASEDIR.'/server/interfaces/plugins/connectors/ContentSource_EnterpriseConnector.class.php';
require_once BASEDIR.'/server/interfaces/services/BizDataClasses.php';
require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';

class Lucene_ContentSource extends ContentSource_EnterpriseConnector
{
	final public function getContentSourceId( )
	{
		// Actually we don't need this because we will return native Enterprise id's
		// we just return dummy ID
		return 'Lucene';
	}
	
	final public function getQueries( )
	{
		$queries = array();

		// Get list of publications and turn into array of publication names
		$username = BizSession::getShortUserName();
		$publications = BizPublication::getPublications( $username );
		$brands = array( BizResources::localize('ACT_ALL') );
		foreach( $publications as $pub ) {
			$brands[] = $pub->Name;
		}
		
		$showObjTypes = array_values( $this->getQueryObjectTypes() );
		
		$queryParamBrand = new PropertyInfo( 
			'Brand', 'Brand', 	// Brand, Brand
			null,				// Category, not used
			'list',				// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			$brands[0],			// Default value
			$brands,			// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
			);
		$queryParamType = new PropertyInfo( 
			'Type', 'Type', 	// Name, Display Name
			null,				// Category, not used
			'list',				// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
			$showObjTypes[0],	// Default value
			$showObjTypes,		// value list
			null, null, null,	// min value, max value,max length
			null, null			// parent value (not used), dependent property (not used)
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
			
		$queries[] = new NamedQueryType( LUCENE_NAMEDQUERY, array($queryParamBrand, $queryParamType, $queryParamSearch) );

		return $queries;
	}
	
	final public function doNamedQuery( $query, $params, $firstEntry, $maxEntries, $order )
	{
		// Possible enhancement: add paging to this example
		
		LogHandler::Log('Lucene', 'DEBUG', 'Lucene::queryObjects called for: '.$params[0]->Value );
		
		// Create array with column definitions
		$cols = $this->getColumns();

		// Get all files from the subfolder (selected parameter) of our local content folder
		$rows = $this->search( $params );
		
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		return new WflNamedQueryResponse( $cols, $rows,	null, null, null, null, 1, count($rows), count($rows), null );
	}
	
	final public function getAlienObject( $alienID, $rendition, $lock )
	{
		// We use native enterprise ids, so this is never called
		LogHandler::Log('Lucene', 'ERROR', "Lucene::getAlienObject called for $alienID - $rendition" );
	}
	
	final public function createShadowObject( $alienID, $destObject )
	{
		// We use native enterprise ids, so this is never called
		LogHandler::Log('Lucene', 'ERROR', "Lucene::createShadowObject called for $alienID" );
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - 
	// Below private implementation methods:
	// - - - - - - - - - - - - - - - - - - - - - - 
	
	private function getColumns()
	{
		$cols = array();
		$cols[] = new Property( 'ID', 			 'ID', 				'string' ); // Required as 1st
		$cols[] = new Property( 'Type', 		 'Type', 			'string' ); // Required as 2nd
		$cols[] = new Property( 'Name', 		 'Name', 			'string' ); // Required as 3rd
		$cols[] = new Property( 'Publication', 	 'Brand', 			'string' );
		$cols[] = new Property( 'Category', 	 'Category', 		'string' );
		$cols[] = new Property( 'Slugline', 	 'Snippet', 		'string' );
		$cols[] = new Property( 'Modified', 	 'Modified', 		'datetime');
		$cols[] = new Property( 'Score', 		 'Score', 			'string' );
		$cols[] = new Property( 'Format', 		 'Format', 			'string' );	// Required by Content Station
		$cols[] = new Property( 'PublicationId', 'PublicationId',	'string' );	// Required by Content Station
		$cols[] = new Property( 'IssueId',		 'IssueId',			'string' );	// Required by Content Station
		return $cols;
	}
	
	private function search( $searchParams )
	{
		require_once dirname(__FILE__) . '/Lucene.class.php';
		
		$rows = array();
		$lucene = new Lucene;
		
		// brand includes an 'All Brands' entry, filter that
		$brand = $searchParams[0]->Value == BizResources::localize('ACT_ALL') ? null : $searchParams[0]->Value;
		
		// In UI we use settings to translate to object types
		$types = array_flip( $this->getQueryObjectTypes() );
		$type = $searchParams[1]->Value == BizResources::localize('ACT_ALL') ? null : $searchParams[1]->Value;
	
		$hits = $lucene->search( $brand, $type, $searchParams[2]->Value );

		foreach( $hits as $hit ) {
			$row = array();
			$row[] = $hit->entid;
			$row[] = $hit->type;
			$row[] = $hit->name;
			$row[] = $hit->publication;
			$row[] = $hit->category;
			$row[] = $hit->slugline;
			$row[] = $hit->modified;
			$row[] = round($hit->score*100).'%';
			$row[] = $hit->format;
			$row[] = $hit->pubid;
			$row[] = 0; // fake issue id
			$rows[] = $row;
		}
		return $rows;
	}
	
	private function getQueryObjectTypes()
	{
		$objTypeMap = getObjectTypeMap();
		$showObjTypes = array_intersect_key( $objTypeMap, array('Article' => true, 'Image' => true, 'Layout' => true, 'Video' => true, 'Audio' => true ) );
		$showObjTypes = array_merge( array( '' => BizResources::localize('ACT_ALL') ), $showObjTypes ); // Insert 'All' entry on top
		return $showObjTypes;
	}
}
