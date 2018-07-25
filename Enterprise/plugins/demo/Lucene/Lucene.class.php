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
 * Lucene Search integration - Library class
 */

// Plug-in config file
require_once dirname(__FILE__) . '/config.php';

// Zend framework:
require_once BASEDIR.'/server/ZendFramework/library/Zend/Search/Lucene.php';

class Lucene
{
	private $index=null;
	private $ids=array(); // keeps track of added objects, so we can mark them indexed at commit
	private $del_ids=array(); // keeps track of deleted objects

	/*
	 * indexObjects
	 * 
	 * Adds Enterprise objects to the Lucene index.
	 * 
	 * @param array of Object	$object		Enterprise objects to index
	 * @return void
	*/
	public function indexObjects( $objects )
	{
		if( $this->index ) {
			PerformanceProfiler::startProfile( 'Lucene', 3 );
			foreach( $objects as $object ) {
				if( !empty($object->MetaData->ContentMetaData->Keywords) ){
					$keywords = implode(',',$object->MetaData->ContentMetaData->Keywords);
				} else {
					$keywords = null;
				}

				$this->addToIndex(  $object->MetaData->BasicMetaData->ID, 
									$object->MetaData->BasicMetaData->Type,
									$object->MetaData->BasicMetaData->Name,
									$object->MetaData->BasicMetaData->Publication->Name,
									$object->MetaData->BasicMetaData->Publication->Id,
									$object->MetaData->BasicMetaData->Category->Name,
									$object->MetaData->ContentMetaData->Format,
									$object->MetaData->WorkflowMetaData->Modified,
									$object->MetaData->ContentMetaData->Slugline,
									$object->MetaData->ContentMetaData->Description,
									$object->MetaData->ContentMetaData->PlainContent,
									$keywords );
			}
		    $this->commitToIndex();
			PerformanceProfiler::stopProfile( 'Lucene', 3 );
		}
	}

	/*
	 * indexObjectsFromDB
	 * 
	 * Gets $count objects from DB that are marked to be indexed and indexes them at Lucene folder.
	 * 
	 * We read directly from DB, bypassing the biz layer. First of all because we need to bypass
	 * access rights, this is a system function. Furthermore we need to do a very specialize 
	 * SQL call, going thru biz layer will explode number of SQL calls. Test for 100 objects shows
	 * approx. 28 vs approx. 1800 sql calls.
	 * 
	 * @param integer	$count	maximum number of object to index
	 * @return integer the amount of indexed documents. Note: non-index documents (like dossiers) are included in this count.
	*/
	public function indexObjectsFromDB( $count )
	{
		$i = 0;
		if( $this->index ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$objectRows = DBObject::getObjectsToIndex( $count, 0);
			
			if( !count( $objectRows ) ) {
				LogHandler::Log( 'Lucene', 'DEBUG', 'Nothing to index' );
			} else {
				PerformanceProfiler::startProfile( 'Lucene', 3 );
				foreach( $objectRows as $row ) {
					$i++;
					$this->addToIndex(  $row['id'], 
										$row['type'],
										$row['name'],
										$row['publicationname'],
										$row['publication'],
										$row['sectionname'],  // category
										$row['format'],
										$row['modified'],
										$row['slugline'],
										$row['description'],
										$row['plaincontent'],
										$row['keywords'] );
				}
				$this->commitToIndex();
				PerformanceProfiler::stopProfile( 'Lucene', 3 );
			}
		}
		return $i;
	}
	
	/**
	 * Same as indexObjectsFromDB method, but then removing indexes.
	 */
	public function unindexObjectsFromDB( $count )
	{
		$i = 0;
		if( $this->index ) {
			// TODO: Move getObjectsToUnindex method to DBObject class
			//require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$ids = self::getObjectsToUnindex( $count );
			
			if( !count( $ids ) ) {
				LogHandler::Log( 'Lucene', 'DEBUG', 'Nothing to unindex' );
			} else {
				PerformanceProfiler::startProfile( 'Lucene', 3 );
				if( $this->unindexObjects( $ids, false ) ) { // use smart_objects table!
					$i = count($ids);
				}
				PerformanceProfiler::stopProfile( 'Lucene', 3 );
			}
		}
		return $i;
	}	

	/*
	 * search
	 * 
	 * Search on user entered phrase. See Optimize for performance notes.
	 * 
	 * @param string	$brand		Brand to search for, null for all brands
	 * @param string	$type		Object type to search for
	 * @param string	$search		Phrase to search on
	 * @return array of Lucene hits
	*/
	public function search( $brand, $type, $search )
	{
		LogHandler::Log( 'Lucene', 'DEBUG', "Search for $brand $type $search" );
		PerformanceProfiler::startProfile( 'Lucene', 3 );

		$query = new Zend_Search_Lucene_Search_Query_Boolean(); 
		$userQuery = Zend_Search_Lucene_Search_QueryParser::parse($search); 
		$query->addSubquery($userQuery, true /* required */); 

		if( $type ) {
			$typeTerm  = new Zend_Search_Lucene_Index_Term($type, 'type'); 
			$typeQuery = new Zend_Search_Lucene_Search_Query_Term($typeTerm); 
			$query->addSubquery($typeQuery, true /* required */); 
		}
		
		if( $brand ) {
			$brandTerm  = new Zend_Search_Lucene_Index_Term($brand, 'publication'); 
			$brandQuery = new Zend_Search_Lucene_Search_Query_Term($brandTerm); 
			$query->addSubquery($brandQuery, true /* required */); 
		}		

		$hits = $this->index->find($query); 
		PerformanceProfiler::stopProfile( 'Lucene', 3 );

		return $hits;
	}

	/*
	 * optimize
	 * 
	 * Optimized index, EXPENSIVE operation.
	 * Optimizing the index should be done periodical
	 * With 1300 objects in index, find without ever using optimize took 0.2 sec,
	 * running optimize took 9.9 seconds after whih find took 0.02. Add another document
	 * optimize takes again 9 seconds.
	 * Optimize on 5000 docs that were never optimized, took 50 sec
	 * 
	 * @return void
	*/
	public function optimize( )
	{
		PerformanceProfiler::startProfile( 'Lucene Optimize', 3 );
		$this->index->optimize();
		PerformanceProfiler::stopProfile( 'Lucene Optimize', 3 );
	}

	/*
	 * addToIndex
	 * 
	 * Adds object to the Lucene index, its individual props are passed.
	 * 
	 * @param various Enterprise properties
	 * @return void
	*/
	private function addToIndex( $id, $type, $name, $pubName, $pubId, $catName, $format, $modified, $slugline, $description, $plainContent, $keywords )
	{
		// Debug output:
		LogHandler::Log( 'Lucene', 'DEBUG', "Add to index:\r\nid: {$id}\r\ntype: {$type}\r\nname: {$name}\r\npubname: {$pubName}\r\npubid: {$pubId}\r\ncatName: {$catName}\r\nformat: {$format}\r\nmodified: {$modified}\r\nslug: {$slugline}\r\ndescr: {$description}\r\nplaincontent: {$plainContent}\r\nkeywords: {$keywords}" );
		
		$this->ids[] = $id;
		// We only index articles, image, audio and video.
		if( $type == 'Article' || $type == 'Image' || $type == 'Audio' || $type == 'Video' ) {
			// First remove this enterprise object from the index if it exists (Lucene cannot update documents)
			$this->deleteByID( $id );
			
			$doc = new Zend_Search_Lucene_Document();
			// Fields not indexed, but stored to return by query
			$doc->addField(Zend_Search_Lucene_Field::Keyword('entid', $id, 'utf-8' ));
			$doc->addField(Zend_Search_Lucene_Field::Keyword('type', $type, 'utf-8' ));
			$doc->addField(Zend_Search_Lucene_Field::Keyword('publication', $pubName, 'utf-8'));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('pubid', $pubId, 'utf-8'));
			$doc->addField(Zend_Search_Lucene_Field::Keyword('category', $catName, 'utf-8'));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('format', $format, 'utf-8'));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('modified', $modified, 'utf-8'));
			
			// Fields indexed and stored, slugline is duplicate of plaincontent, but a hit in the slugline (first chars)
			// make it a better hit, hence they are stored both
			$doc->addField(Zend_Search_Lucene_Field::Text('slugline', $slugline, 'utf-8'));
			$doc->addField(Zend_Search_Lucene_Field::Text('name', $name, 'utf-8' ));
			
			// Description and slug used for index, but not stored for query results
			$doc->addField(Zend_Search_Lucene_Field::UnStored('description', $description, 'utf-8'));
			$doc->addField(Zend_Search_Lucene_Field::UnStored('content', $plainContent, 'utf-8'));
			
			$doc->addField(Zend_Search_Lucene_Field::UnStored('keyword', $keywords, 'utf-8' ));
			
			$this->index->addDocument($doc);
		}
	}	
	
	/*
	 * commitToIndex
	 * 
	 * Commits the added Lucene documents to the index and marks the Enterprise object as indexed.
	 * 
	 * @param various Enterprise properties
	 * @return void
	*/
	private function commitToIndex()
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		LogHandler::Log( 'Lucene', 'DEBUG', 'Commit to index' );
		$this->index->commit();
		$this->index=null;
		
		// Mark objects as indexed.  We also include the object types that we skipped to prevent seeing them again and again in a growing list
		if( !empty( $this->ids) ) {
			DBObject::setIndexed( $this->ids );
			$this->ids = array();
		}
	}

	/*
	 * unindexObjects
	 * 
	 * Remove Lucene indexes for given Enterprise objects.
	 * The objects are assumed to be deleted first (=> so they reside at smart_deletedobjects table).
	 * 
	 * @param array of string	$ids	Enterprise object ids to unindex
	 * @param boolean $deletedObjects Use smart_deletedobjects table. False for using smart_objects instead.
	 * @return boolean Whether or not unindexing was successful
	*/
	public function unindexObjects( $ids, $deletedObjects )
	{
		$retVal = false;
		LogHandler::Log( 'Lucene', 'DEBUG', "remove index" );
		if( $this->index ) {
			PerformanceProfiler::startProfile( 'Lucene', 3 );
			foreach( $ids as $id ) {
				$this->del_ids[] = $id;
				$this->deleteByID( $id );
			}
		   	if( !empty($this->del_ids) ) {
		   		// TODO: Move the setNonIndex method to DBObjects class.
				self::setNonIndex( $this->del_ids, $deletedObjects );
				$this->del_ids = array();
				$retVal = true;
			}
			PerformanceProfiler::stopProfile( 'Lucene', 3 );
		}
		return $retVal;
	}

	/*
	 * deleteByID
	 * 
	 * Removes all Lucene document(s) for specified Enterprise object ID.
	 * 
	 * @param integer	$id		Enterprise object id
	 * @return boolean: true if Enterprise object found 
	*/
	private function deleteByID( $id )
	{
		$pathTerm  = new Zend_Search_Lucene_Index_Term($id, 'entid'); 
		$pathQuery = new Zend_Search_Lucene_Search_Query_Term($pathTerm); 
		$hits = $this->index->find($pathQuery); 

		foreach( $hits as $hit ) {
			$this->index->delete($hit->id);
		}
		return count($hits) > 0 ;
	}

	/**
	 * Constructor 
	 *
	 * Opens or creates index files. 
	 * Also creates the LUCENE_DIRECTORY folder if it doesn't exist yet.
	 */
	public function __construct ( )
	{
		if(	file_exists( LUCENE_DIRECTORY ) ) {
			// Folder exists, but could be empty, in that case we'll get an exception
			try{
				LogHandler::Log( 'Lucene', 'DEBUG', 'Opening index '.LUCENE_DIRECTORY );
				$this->index = Zend_Search_Lucene::open( LUCENE_DIRECTORY );
				LogHandler::Log( 'Lucene', 'DEBUG', 'Index opened' );
				return;
			} catch( Exception $e ) {
				LogHandler::Log( 'Lucene', 'DEBUG', 'Failed to open index: '.$e->getMessage() );
			}
		} 
		
		// Folder doesn't exists, or failed to open, let's try to create:
		LogHandler::Log( 'Lucene', 'DEBUG', 'Creating index '.LUCENE_DIRECTORY );
		$this->index = Zend_Search_Lucene::create( LUCENE_DIRECTORY );
		LogHandler::Log( 'Lucene', 'DEBUG', 'Index created' );
		
		if( !$this->index ) {
			LogHandler::Log( 'Lucene', 'ERROR', 'Cannot create or open Lucene index: '.LUCENE_DIRECTORY . 'Enterprise' );
		}
	}

	/**
	 * getObjectsToIndex
	 *
	 * Get objects that needs to be unindexed, up to specified maximum amount.
	 *
	 * @param integer $maxCount	maximum number of objects to return
	 * @return array of object ids
	 *
	 * @todo Move this method to DBObjects class.
	**/
	static private function getObjectsToUnindex( $maxCount )
	{
		// query DB
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename('objects');
		$sql = "SELECT `id` FROM $dbo WHERE `indexed`='on' ";
		if( $maxCount > 0 ) {
			$sql = $dbdriver->limitquery($sql, 0, $maxCount);
		}
		$sth = $dbdriver->query($sql);

		// collect ids
		$ids = array();
		while( ( $row = $dbdriver->fetch($sth) ) ) {
			$ids[] = $row['id'];
		}
		return $ids;
	}
	
	/**
	 * setNonIndex
	 *
	 * Marks specified objects as non index
	 *
	 * @param array $objectids: ids of objects.
	 * @param boolean $deletedObjects Use smart_deletedobjects table. False for using smart_objects instead.
	 * @return void
	 *
	 * @todo Move this method to DBObjects class (replacing its existing setNonIndex method).
	**/
	static public function setNonIndex( $objectIDs, $deletedObjects )
	{
		$dbdriver = DBDriverFactory::gen();
		$ids = implode(',',$objectIDs);
		$dbo = $dbdriver->tablename( $deletedObjects ? 'deletedobjects' : 'objects' );
		$sql = "UPDATE $dbo SET `indexed` = '' WHERE `id` IN ( $ids )";
		$dbdriver->query($sql);
	}

	/**
	 * countObjectsToIndex
	 *
	 * Counts the objects at smart_objects table that needs to be indexed (or needs to be un-indexed).
	 *
	 * @param boolean $index Whether to count objects to index or to un-index
	 * @return integer Object count.
	 *
	 * @todo Move this method to DBObjects class.
	**/
	static public function countObjectsToIndex( $toIndex )
	{
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename('objects');
		$sql = "SELECT count(*) as `c` FROM $dbo o ";
		if( $toIndex ) {
			$sql .= "WHERE o.`indexed`='' "; // un-indexed = needs to be indexed
		} else { // to un-index
			$sql .= "WHERE o.`indexed`='on' "; // indexed = needs to be un-indexed
		}
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		return intval($row['c']);
	}
}
