<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class that acts as facade to search engines implemented by plug-ins.
 * 
 * [Note#001] When ANY connector has failed indexing or unindexing, BizException should be thrown by that 
 * connector. Doing so, the objects will NOT be flagged at Enterprise DB (marked as indexed or unindexed).
 * This is to detect the need of re-(un)indexing those objects that failed. Note that there can be
 * more than one search engine. The first search connector could be successful, but the second could fail.
 * From Enterprise Server perspective, those objects have failed, and won't get flagged. The system admin
 * user can solve the problem (like starting the second search server that went down) and re-index.
 * This means connectors should be resistent against re-indexing objects (that were indexed before).
 */

class BizSearch
{
	// - - - - - - - - - - - - - - - - - - - - INDEXING - - - - - - - - - - - - - - - - - - - - 
	/**
	 * See Search_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in(s)
	 *
	 * @param array $objects List of object to be indexed.
	 * @param bool $suppressExceptions Whether to suppress error if there's any.
	 * @param array $areas Either 'Workflow' or 'Trash'.
	 * @param bool|null $directCommit TRUE to directly reflect data. FALSE to do later (faster).
	 * @throws BizException When $suppressExceptions is set to false, function throws BizException when there's error.
	 */
	public static function indexObjects( $objects, $suppressExceptions = true, $areas = array('Workflow'), $directCommit=null )
	{
		try {
			// When no direct commit given, ask session setting. This is needed e,g, for test 
			// scripts that query objects -directly- after saving to check if all saved data 
			// is reflected.
			if( is_null($directCommit) ) {
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
				$directCommit = BizSession::getDirectCommit();
			}
			
			// Call the search connectors.
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			$connectors = BizServerPlugin::searchConnectors('Search', null);
			if ($connectors) foreach ( $connectors as $connClass => $connector ){
				LogHandler::Log('BizSearch', 'DEBUG', 'Connector '.$connClass.' executes method setDirectCommit with value:' . var_export($directCommit, true));
				$connRetVals[$connClass] = call_user_func_array( array(&$connector, 'setDirectCommit'), array($directCommit) );
				LogHandler::Log('BizSearch', 'DEBUG', 'Connector completed.');

				LogHandler::Log('BizSearch', 'DEBUG', 'Connector '.$connClass.' executes method indexObjects');
				$connRetVals[$connClass] = call_user_func_array( array(&$connector, 'indexObjects'), array($objects, $areas));
				LogHandler::Log('BizSearch', 'DEBUG', 'Connector completed.' );
			}
			// All connectors did index at this point, so we flag them at Enterprise DB. See Note#001!
			self::setIndexAndUnindexFlag( $objects, $connRetVals, $areas );
		} catch( BizException $e ) {
			// Objects that are supposed to be (re)indexed did not get (re)indexed, so un-indexed them.
			$unhandledObjectIds = self::filterUnhandledObjectIds( array(), $objects );
			self::setUnindexFlag( $unhandledObjectIds, $areas );
			if( $suppressExceptions ) {
				LogHandler::Log( 'Search', 'ERROR', 'Index error: '.$e->getMessage().' '.$e->getDetail() );
			} else {
				throw $e;
			}
		}
	}

	/**
	 * Indexes multiple objects by their ids.
	 *
	 * We assume that default a search engine is installed, that's why we don't check this first before doing the get.
	 * In the exceptional case that no search engine is installed these gets were a waste, but otherwise the plugin
	 * test (requiring DB access) would most of times be of waste.
	 * The object properties to be indexed are restricted to a minimum. This improves performance but also limits the
	 * the risk on failure. So no renditions are requested as files are not indexed. There is no need to access a
	 * content source as Solr should just reflect the data stored in the database (this limits the risk on failure when
	 * the content source is not available). Also no need to check access rights as the index process should always
	 * be able to access the object.
	 *
	 * @param string[] $objectIds List of object ids
	 * @param boolean $suppressExceptions Whether or not the suppress throwing exceptions
	 * @param string[] $areas Which area to search in: Workflow or Trash
	 */
	public static function indexObjectsByIds( $objectIds, $suppressExceptions = true, $areas = array('Workflow') ) 
	{	
		// Do not call expensive getObject if not needed.
		if ( self::searchConnectorIsImplemented() === false ) {
			return;
		}

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$objects = array();
		$user = BizSession::getShortUserName();
		foreach( $objectIds as $objectId ) {
			$objects[] = BizObject::getObject( $objectId, $user, false, 'none', array( 'Targets','MetaData', 'Relations' ),
				null, false, $areas, null, false );
			// By asking for Targets and Relations also child object targets are added.
		}
		self::indexObjects( $objects, $suppressExceptions, $areas );
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in(s)
	 *
	 * @param Object[] $objects List of object to reindex
	 * @param boolean $suppressExceptions Whether or not the suppress throwing exceptions
	 * @param string[] $areas Which area to search in: Workflow or Trash
	 * @throws BizException
	 */
	public static function updateObjects( $objects, $suppressExceptions = true, $areas = array('Workflow') ) 
	{
		try {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			BizServerPlugin::runDefaultConnectors( 'Search', null, 'updateObjects', array($objects, $areas), $connRetVals );
			// All connectors did index at this point, so we flag them at Enterprise DB. See Note#001!

			self::setIndexAndUnindexFlag( $objects, $connRetVals, $areas );
		} catch( BizException $e ) {
			// Objects that are supposed to be (re)indexed did not get (re)indexed, so un-indexed them.
			$unhandledObjectIds = self::filterUnhandledObjectIds( array(), $objects );
			self::setUnindexFlag( $unhandledObjectIds, $areas );
			if( $suppressExceptions ) {
				LogHandler::Log( 'Search', 'ERROR', 'Index error: '.$e->getMessage().' '.$e->getDetail() );
			} else {
				throw $e;
			}
		}
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 *
	 * This is a facade hiding the details of calling the method from the right plug-in(s)
	 *
	 * @param array $objectIDs List of Object IDs.
	 * @param array $metaDataValues List of changed metadata property/values.
	 * @param bool $suppressExceptions
	 * @param null $directCommit NULL to take default from BizSession. TRUE to directly reflect data. FALSE to do later (faster).
	 * @throws BizException
	 */
	public static function updateObjectProperties( $objectIDs, $metaDataValues, $suppressExceptions = true, $directCommit=null )
	{
		try {
			// When no direct commit given, ask session setting. This is needed e,g, for test
			// scripts that query objects -directly- after saving to check if all saved data
			// is reflected.
			if( is_null($directCommit) ) {
				require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
				$directCommit = BizSession::getDirectCommit();
			}

			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			BizServerPlugin::runDefaultConnectors( 'Search', null, 'setDirectCommit',
				array($directCommit), $connRetVals );
			$connRetVals = array();
			BizServerPlugin::runDefaultConnectors( 'Search', null, 'updateObjectProperties',
				array($objectIDs, $metaDataValues), $connRetVals );
		} catch( BizException $e ) {
			if( $suppressExceptions ) {
				LogHandler::Log( 'Search', 'ERROR', 'Update properties error: '.$e->getMessage().' '.$e->getDetail() );
			} else {
				throw $e;
			}
		}
	}

	/**
	 * Update object index by Ids.
	 *
	 * We assume that default a search engine is installed, that's why we don't check this first before doing the get.
	 * In the exceptional case that no search engine is installed these gets were a waste, but otherwise the plugin
	 * test (requiring DB access) would most of times be of waste.
	 *
	 * @param string[] $objectIds List of object ids to reindex
	 * @param boolean $suppressExceptions Whether or not the suppress throwing exceptions
	 * @param string[] $areas Which area to search in: Workflow or Trash
	 * @throws BizException
	 */
	public static function updateObjectsByIds( $objectIds, $suppressExceptions = true, $areas = array('Workflow') ) 
	{	
		// Do not call expensive getObject if not needed.
		if ( self::searchConnectorIsImplemented() === false ) {
			return;
		}

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$objects = array();
		$user = BizSession::getShortUserName();
		foreach( $objectIds as $objectId ) {
			$objects[] = BizObject::getObject($objectId, $user, false/*lock*/, 'none'/*rendition*/, array('Targets','MetaData', 'Relations')/*requestInfo */, 
				null/*haveVersion*/, false/*checkRights*/, $areas); // no lock, no rendition
			// By asking for Targets and Relations also child object targets are added.
		}
		self::updateObjects( $objects, $suppressExceptions, $areas );
	}
	/**
	 * See Search_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in(s)
	 * *
	 * @param string[] $objectsIds List of object ids to unindex, null in case to unindex all objects.
	 * @param string[] $areas Which area to search in: Workflow or Trash
	 * @param boolean $suppressExceptions Whether or not to suppress throwing exceptions
	 * @throws BizException
	 */
	public static function unIndexObjects( $objectsIds, array $areas, $suppressExceptions = true ) 
	{
		try {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			BizServerPlugin::runDefaultConnectors( 'Search', null, 'unIndexObjects', array($objectsIds, false/*$deletedObject= true/false also doesn't matter as it is not used*/), $connRetVals );
			// All connectors did unindex at this point, so we flag them at Enterprise DB. See Note#001!
			if( $connRetVals ) { // E.g. Solr is installed and running.
				if( is_null( $objectsIds ) ) {
					self::setUnindexFlagOnAllObjects( $areas );
				} else{
					$handledObjectIds = self::collectHandledObjectIds( $connRetVals );
					self::setUnindexFlag( $handledObjectIds, $areas );
				}
				self::setLastOptimized( '' );
			}
		} catch( BizException $e ) { // E.g. Solr plug-in is installed but Solr is not up and running.
			// BizException caught, no un-indexed needs to be done here.
			if( $suppressExceptions ) {
				LogHandler::Log( 'Search', 'ERROR', 'unIndexObjects error: '.$e->getMessage().' '.$e->getDetail() );
			} else {
				throw $e;
			}
		}
	}

	/**
	 * Gets objects from DB that are marked to be indexed and indexes them.
	 *
	 * The biz layer is bypassed on purpose; It reads directly from DB to bypass the access rights
	 * and to limit the number of SQL calls. Note that going through the biz layer would slow due to
	 * access rights checking and due to explosion of number of SQL calls; Test for 100 objects
	 * shows 28 vs 1800 SQL calls.
	 *
	 * @param int $lastObjId [In/Out] The last (max) object id that was indexed the previous time. Used for pagination.
	 * @param int $lastDeletedObjId [In/Out] The last (max) deletedobject id that was indexed the previous time. Used for pagination.
	 * @param int $maxCount Maximum number of object to index. Used for pagination.
	 * @param array $areas Whether it is Workflow or Trash area.
	 * @param int $totalObjectsIndexed [In/Out]Total numbers of objects that were successfully indexed.
	 * @throws BizException
	 */
	static public function indexObjectsFromDB( &$lastObjId, &$lastDeletedObjId, $maxCount, $areas, &$totalObjectsIndexed )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
		if( in_array('Trash', $areas ) ){
			$objectRows = DBDeletedObject::getDeletedObjectsToIndex( $lastDeletedObjId, $maxCount );
		}else{
			$objectRows = DBObject::getObjectsToIndex( $lastObjId, $maxCount );
		}
		if( !count( $objectRows ) ) {
			LogHandler::Log( 'Search', 'DEBUG', 'Nothing to index' );
		} else {
			require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
			PerformanceProfiler::startProfile( 'Search', 3 );
			$objectIds = array();
			if( $objectRows ) foreach( $objectRows as $row ) {
				$objectIds[] = $row['id'];
			}
			if( $objectIds ) {
				if( in_array( 'Workflow', $areas ) ) {
					$lastObjId = end( $objectIds );
				}
				if( in_array( 'Trash', $areas ) ) {
					$lastDeletedObjId = end( $objectIds );
				}
			}
			$propertiesToIndex = self::getPropertiesToIndex( false );
			$objects = BizObject::getObjectsToIndexForObjectIds( $objectIds, $areas, $propertiesToIndex );
			$totalObjectsIndexed = count( $objects );
			self::indexObjects( $objects, false, $areas );
			PerformanceProfiler::stopProfile( 'Search', 3 );
		}
	}
	
	/**
	 * Same as indexObjectsFromDB method, but then removing indexes.
	 *
	 * @param integer $lastObjId The last (max) object id that was unindexed the previous time. Used for pagination.
	 * @param integer $maxCount  Maximum number of object ids to return. Used for pagination.
	 * @return integer The number of objects that have been un-indexed during this function call.
	 */
	static public function unindexObjectsFromDB( &$lastObjId, $maxCount )
	{
		$i = 0;
	
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$ids = DBObject::getIndexedObjects( $lastObjId, $maxCount );
		
		if( !count( $ids ) ) {
			LogHandler::Log( 'Search', 'DEBUG', 'Nothing to unindex' );
		} else {
			$lastObjId = end($ids);
			PerformanceProfiler::startProfile( 'Search', 3 );
			self::unindexObjects( $ids, array('Workflow'), false ); // use smart_objects table!
				
			$i = count($ids);
			PerformanceProfiler::stopProfile( 'Search', 3 );
		}
	
		return $i;
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in(s)
	 *
	 * @param string $fieldName
	 * @param bool $suppressExceptions
	 * @throws BizException
	 * @return bool
	 */
	static public function isPropertySearchable ( $fieldName, $suppressExceptions = true )
	{
		$connRetVals = array();
		try {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			BizServerPlugin::runDefaultConnectors( 'Search', null, 'isPropertySearchable', array( $fieldName ), $connRetVals );
		} catch( BizException $e ) {
			if( $suppressExceptions ) {
				LogHandler::Log( 'Search', 'ERROR', 'Search error: '.$e->getMessage().' '.$e->getDetail() );
			} else {
				throw $e;
			}
		}

		$isSearchable = false;
		if ( $connRetVals ) foreach ($connRetVals as $connRetVal ) {
    		if ( $connRetVal === true ) {
				$isSearchable = true;
				break; // One found
			}
		}

		return $isSearchable;
	}

	/**
	 * Asks the different search engines which properties must be indexed.
	 * The properties returned by the engines are merged and duplicates are removed.
	 *
	 * @param bool $suppressExceptions
	 * @return array with the property names.
	 * @throws BizException
	 */
	static public function getPropertiesToIndex( $suppressExceptions = true )
	{
		$connRetVals = array();
		try {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			BizServerPlugin::runDefaultConnectors( 'Search', null, 'getPropertiesToIndex', array(), $connRetVals );
		} catch( BizException $e ) {
			if( $suppressExceptions ) {
				LogHandler::Log( 'Search', 'ERROR', 'Search error: '.$e->getMessage().' '.$e->getDetail() );
			} else {
				throw $e;
			}
		}

		$fieldsToIndex = array();
		if ( $connRetVals ) foreach ($connRetVals as $connRetVal ) {
			$fieldsToIndex = array_merge( $fieldsToIndex, $connRetVal );
		}

		return array_unique( $fieldsToIndex );

	}	
	
	// - - - - - - - - - - - - - - - - - - - - OPTIMIZING - - - - - - - - - - - - - - - - - - - - 
	/**
	 * See Search_EnterpriseConnector for comments
	 * This is a facade hiding the details of calling the method from the right plug-in(s)
	 *
	 * @return string Timestamp (now) which indicates when we've optimized for the last time.
	 */
	static public function optimizeIndexes()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array();
		BizServerPlugin::runDefaultConnectors( 'Search', null, 'optimizeIndexes', array(), $connRetVals );
		// All connectors did optimize at this point, so we save timestamp at Enterprise DB. See Note#001!
		return self::setLastOptimized( date( 'Y-m-d\TH:i:s', time() ) );
	}

	/**
	 * Creates and saves a timestamp (now) at Enterprise DB to remember when we did optimize indexes 
	 * for the last time. It returns the created timestamp.
	 *
	 * @param string $value
	 * @return string datetime
	 */
	static public function setLastOptimized( $value )
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
		if( DBUserSetting::hasSetting( '', 'LastOptimized', 'BizSearch' ) ) {
			DBUserSetting::updateSetting( '', 'LastOptimized', $value, 'BizSearch' );
		} else {
			DBUserSetting::addSetting( '', 'LastOptimized', $value, 'BizSearch' );
		}
		return $value;
	}

	/**
	 * Retrieves a timestamp from Enterprise DB which tells when we did optimize indexes for the last time.
	 *
	 * @return string datetime
	 */
	static public function getLastOptimized()
	{
		require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
		$settings = DBUserSetting::getSettings( '', 'BizSearch' );
		foreach( $settings as $setting ) {
			if( $setting->Setting == 'LastOptimized' ) {
				return $setting->Value;
			}
		}
		return '';
	}

	// - - - - - - - - - - - - - - - - - - - - SEARCHING - - - - - - - - - - - - - - - - - - - - 
	/**
	 * Returns true if the search is on of the built-in search concepts
	 *
	 * @param string $query
	 * @param array $params
	 * @param int $firstEntry
	 * @param int $maxEntries
	 * @param string $queryMode
	 * @param bool $hierarchical
	 * @param QueryOrder[] $queryOrder
	 * @param string[] $minimalProps
	 * @param string[] $requestProps
	 * @return bool
	 */
	public static function handleSearch( $query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps ) 
	{
		// QueryObjects could have param that should set query name, resolved that:
		self::resolveQueryName($query, $params );
		
		// Get all search connectors, iterate thru them to see if anyone can do this. If so, we can stop iterating and return true
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$searchConnectors = BizServerPlugin::searchConnectors( 'Search', null );
		foreach( $searchConnectors as $searchConnector ) {
			if( BizServerPlugin::runConnector( $searchConnector, 'canHandleSearch', array($query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps)) ) {
				return true;
			}
		}
		// Didn't find any Search Connector able to handle this search	
		return false;
	}
	
	/**
	 * Performs one of the built-in searches
	 *
	 * @param string $query
	 * @param array $params
	 * @param int $firstEntry
	 * @param int $maxEntries
	 * @param string $queryMode
	 * @param bool $hierarchical
	 * @param QueryOrder[] $queryOrder
	 * @param string[] $minimalProps
	 * @param string[] $requestProps
	 * @param string[] $areas
	 * @throws BizException
	 * @return mixed WflQueryObjectsResponse or WflNamedQueryResponse
	 */
	public static function search($query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps, $areas=array('Workflow') ) 
	{
		// Get all search connectors, iterate thru them to find the best for this format:
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$searchConnectors = BizServerPlugin::searchConnectors( 'Search', null );
		$highestQuality = 0;
		$bestConnector 	= null;
		// QueryObjects could have param that should set query name, resolved that:
		self::resolveQueryName($query, $params );		
		foreach( $searchConnectors as $searchConnector ) {
			$quality = BizServerPlugin::runConnector( $searchConnector, 'canHandleSearch', array($query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps) );
			if( $quality > $highestQuality ) {
				$highestQuality = $quality;
				$bestConnector	= $searchConnector;
			}
		}
		// If we have connector that is capable, use it:
		if ($bestConnector) {
			if ($queryMode == 'contentstation') {
				require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';
				$overIssues = DBIssue::listAllOverruleIssues();
				if (is_null($minimalProps)) {
					$minimalProps = array();
				}
				if (! empty($overIssues) && ! in_array('IssueId', $minimalProps)) {
					$minimalProps[] = 'IssueId';
				}
			}
			// Note: it's important to use Type of attachment, ContentMetaData->Data type is less reliable!
			return BizServerPlugin::runConnector($bestConnector, 'doSearch', array($query , $params , $firstEntry , $maxEntries , $queryMode , $hierarchical , $queryOrder , $minimalProps , $requestProps, $areas ));
		}
		// If we arrive here we have an unknown Search...
		throw new BizException('ERR_INVALID_OPERATION', 'Client', 'Named query not found: ' . $query);
	}

	/**
	 * Retrieves installed Search Server connectors from Enterprise DB and resolves their owner Server Plug-ins.
	 *
	 * @return array of PluginInfoData
	 */
	public static function installedSearchServerPlugins()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/dbclasses/DBServerConnector.class.php';
		$retPlugins = array();
		$connInfos = DBServerConnector::getConnectors( 'Search', null, true, true ); // active, installed
		//$searchConnectors = BizServerPlugin::searchConnectors( 'Search', null );
		foreach( $connInfos as $connInfo ) {
			$pluginInfo = BizServerPlugin::getPluginForConnector( $connInfo->ClassName );
			$retPlugins[$pluginInfo->UniqueName] = $pluginInfo;
		}
		return $retPlugins;
	}

	// - - - - - - - - - - - - - - - - - - - - PRIVATE - - - - - - - - - - - - - - - - - - - - 

	/**
	 * Checks if the Search connector is implemented by plug-ins.
	 * @staticvar null $pluginUsed Connector is implemented (true/false).
	 * @return boolean true if connector is implemented else false.
	 */
	private static function searchConnectorIsImplemented()
	{
		static $isImplemented = null;

		if ( is_null( $isImplemented )) {
			$plugins = self::installedSearchServerPlugins();
			if ( !empty($plugins)) {
				$isImplemented = true;
			} else {
				$isImplemented = false;
			}
		}

		return $isImplemented;
	}

	/**
	 * Flag objects as indexed in the database.
	 *
	 * @param int[] $objectIds Object ids to be flagged as indexed.
	 * @param array|null $areas
	 */
	private static function setIndexFlag( $objectIds, array $areas=null )
	{
		if( $objectIds ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$deletedObjects = in_array( 'Trash', $areas ) ? true : false;
			DBObject::setIndexed( $objectIds, $deletedObjects );
		}
	}

	/**
	 * Flag objects as not indexed in the database.
	 *
	 * @param int[] $objectIds Object ids to be flagged as not indexed.
	 * @param array $areas
	 */
	private static function setUnindexFlag( $objectIds, array $areas)
	{
		if( $objectIds ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			DBObject::setNonIndex( $objectIds, $areas );
		}
	}

	/**
	 * Flag all objects as not indexed in the database.
	 *
	 * @since 10.4.1
	 * @param array $areas
	 */
	private static function setUnindexFlagOnAllObjects( array $areas)
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		DBObject::setNonIndexOnAllObjects( $areas );
	}

	/**
	 * Collect the object ids that have been successfully handled by ALL Search connectors.
	 *
	 * @param array $inputArrays
	 * @return array
	 */
	private static function collectHandledObjectIds( $inputArrays )
	{
		$intersectionArray = array();
		if (!empty($inputArrays)) {
			$intersectionArray = array_shift($inputArrays); // Initalize with first array
			foreach ($inputArrays as $inputArray) {
				$intersectionArray =  array_intersect($intersectionArray, $inputArray);
			}
		}
		return  $intersectionArray;
	}

	/**
	 * Setting list of objects to indexed or not-indexed depending on if the reindexing was successful.
	 *
	 * @param Object[] $objects Full list of objects sent to all Search connectors for reindexing.
	 * @param int[] $objectIds List of Key(Search connector)=>Values(object ids) that were successfully handled by all Search connectors.
	 * @param string[] $areas Workflow or Trash, to determine which database table to set the indexed/not-indexed for the objects.
	 */
	private static function setIndexAndUnindexFlag( $objects, $objectIds, $areas )
	{
		$handledObjectIds = self::collectHandledObjectIds( $objectIds );
		$unhandledObjectIds = self::filterUnhandledObjectIds( $handledObjectIds, $objects );
		if( $handledObjectIds ) {
			self::setIndexFlag( $handledObjectIds, $areas );
		}
		if( $unhandledObjectIds ) {
			self::setUnindexFlag( $unhandledObjectIds, $areas );
		}
	}

	/**
	 * Filter the object ids that have not been successfully handled by Search connectors.
	 *
	 * Function iterates through the list of Objects and filter out the ids that are not
	 * present in the successfully handled object ids ( $handledObjectIds ).
	 * The filtered list will be returned to the caller.
	 *
	 * @param int[] $handledObjectIds List of object ids that have been successfully handled by all Search connectors.
	 * @param Object[] $objects List of Objects to be searched which has not been handled yet.
	 * @return int[]
	 */
	private static function filterUnhandledObjectIds( $handledObjectIds, $objects )
	{
		$notHandledObjectIds = array();
		if( count( $handledObjectIds ) != count( $objects )) {
			foreach( $objects as $object ) {
				$id = $object->MetaData->BasicMetaData->ID;
				if( !in_array( $id, $handledObjectIds ) ) {
					$notHandledObjectIds[] = $id;
				}
			}
		}
		return $notHandledObjectIds;
	}
	
	/**
	 * For QueryObjects $query is set to _QueryObjects_ and there could be a parameter to select a specific Search.
	 * Check that case and set $query to the rigt value, which is still _QueryObjects_ if not Query param set
	 * 
	 * Passed in $query and $params are changed if needed.
	 *
	 * @param string $query [in/out]
	 * @param array $params [in/out]
	 */
	private static function resolveQueryName( &$query, &$params )
	{
		$adapted = false;
		if( $query == '_QueryObjects_' && !empty($params) ) {
			$newParams = array();
			foreach( $params as $param ) {
				if( $param->Property == 'Query' ) {
					$query = $param->Value;
					$adapted = true;
				} else {
					$newParams[] = $param;
				}
			}
			if( $adapted ) {
				$params = $newParams;
			}
		}
	}
}
