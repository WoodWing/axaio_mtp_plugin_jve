<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	2009 WoodWing Software bv. All Rights Reserved.
 *
 * Class with static functions to integrated search engines
 * 
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class Search_EnterpriseConnector extends DefaultConnector
{
	protected $directCommit = false; // Whether to force the search engine to do a direct commit or not (only if supported).

	/**
	 * Adds information taken from given objects to the indexes of the Search Server.
	 * During production: The objects are about to get created at Enterprise Server.
	 * During system admin: The system admin user is about to re-index all Enterprise objects.
	 * 
	 * Since v8.0, property Areas is introduced;
	 * This is to cover indexing for workflow objects (Areas = Workflow) and also deleted objects (Areas = Trash)
	 * In other words, when the object is deleted, the indexing for this deleted object doesn't get removed.
	 * It still stays in the Search Server but it is distinguished by its Areas.
	 *
	 * When any problem occurs during indexing process, it is safe(!) to throw BizException because
	 * it will be catched by core server. This is done to let production continue, even when the 
	 * search server is down or has indexing troubles. Instead, exceptions are logged.
	 * Unlike for production applications, for system administration applications, thrown exceptions 
	 * are displayed on screen to let admin user solve problems.
	 *
	 * @param array $objects List of Object objects
	 * @param array $areas 
	 * @return void
	 * @throws BizException When objects could not be indexed.
	 */
	abstract public function indexObjects( $objects, $areas = array('Workflow') );

	/**
	 * Updates information taken from given objects to the indexes of the Search Server.
	 * The objects are about to get updated at Enterprise Server.
	 * 
	 * For $areas, refer to IndexObjects() Function Header for more info.
	 * 
	 * When any problem occurs during indexing process, it is safe(!) to throw BizException because
	 * it will be catched by core server. This is done to let production continue, even when the 
	 * search server is down or has indexing troubles. Instead, exceptions are logged.
	 * Unlike for production applications, for system administration applications, thrown exceptions 
	 * are displayed on screen to let admin user solve problems.
	 *
	 * @param array $objects List of Object objects
	 * @param array $areas
	 * @return mixed
	 * @throws BizException When objects could not be indexed.
	 */
	abstract public function updateObjects( $objects, $areas = array('Workflow') );


	/**
	 * Updates a set of properties for given objects to the indexes of the Search Server.
	 *
	 * This function is intended to update a set of changed properties for
	 * a potentially large list of objects and should execute fast as a result.
	 * It should be able to handle updating many objects at once in a fast manner.
	 *
	 * When any problem occurs during indexing process, it is safe(!) to throw BizException because
	 * it will be catched by core server. This is done to let production continue, even when the
	 * search server is down or has indexing troubles. Instead, exceptions are logged.
	 * Unlike for production applications, for system administration applications, thrown exceptions
	 * are displayed on screen to let admin user solve problems.
	 *
	 * @since v9.2
	 * @param array $objectIDs List of Object IDs.
	 * @param array $metaDataValues List of changed metadata property/values.
	 * @return bool true if properties of all objects were successfully updated.
	 * @throws BizException When objects could not be indexed.
	 */
	abstract public function updateObjectProperties( $objectIDs, $metaDataValues );

	/**
	 * Removes all information for the given object ids from the indexes of the Search Server.
	 * IMPORTANT: When no ids are given, all objects are unindexed at once!
	 * During production: The objects are about to get removed at Enterprise Server.
	 * During system admin: The system admin user is about to re-index all Enterprise objects.
	 * 
	 * When any problem occurs during indexing process, it is safe(!) to throw BizException because
	 * it will be catched by core server. This is done to let production continue, even when the 
	 * search server is down or has indexing troubles. Instead, exceptions are logged.
	 * Unlike for production applications, for system administration applications, thrown exceptions 
	 * are displayed on screen to let admin user solve problems.
	 *
	 * @param integer[] $objectsIds Objects Ids of objects to unindex. Set to null to unindex all objects at once!
	 * @param boolean $deletedObject True if the objects are deleted (so they reside at smart_deleteobjects table). 
	 *                               False for normal objects (so they reside at smart_objects table). 
	 * @return void
	 * @throws BizException When objects could not be unindexed.
	 */
	abstract public function unIndexObjects( $objectsIds, $deletedObject );

	/**
	 * [OPTIONAL] Adding many indexes could lead into less optimal searches. This function is called manually
	 * (by system admin user) or automatically (when setup scheduled batch files, such as crontab) whenever it
	 * is time to optimize the index structure (indexed objects). This implementation is optional; The connector
	 * could leave out the entire implementation, so a warning is raised to admin user informing this search
	 * engine is not optimized. Then he/she can decide to do that manually. But, when there is no need to
	 * optimize (e.g. indexes are always optimal), the connector could add an empty implementation.
	 *
	 * @throws BizException When indexes could not be optimized.
	 */
	/*abstract*/ public function optimizeIndexes() {}

	/**
	 * [OPTIONAL] Force the changes to committed to the search engine immediately, instead of relying on performance
	 * enhancements like Solr's AutoCommit functionality. The connector may or may not rely on such mechanisms. Solr
	 * uses this implementation when reindexing objects, in some special cases it might be necessary to forego the
	 * AutoCommit functionality and instead do a direct commit, for example in the cases where a race condition might
	 * occur.
	 *
	 * @param bool $directCommit
	 */
	/*abstract*/ public function setDirectCommit( $directCommit )
	{
		$this->directCommit = $directCommit;
	}
	
	/**
	 * Tells if the search connector is able to handle the search request.
	 * if so, it gives an indication how good/fast it can execute.
	 * This way, the core server determines which search connector does the best job.
	 * The best connector is then called through the doSearch function.
	 * 
	 * @return int		Return if and how well the format is supported.
	 * 				 	 0 - Not supported
	 * 					 1 - Could give it a try
	 * 					 2 - Reasonable
	 * 					 3 - Pretty Good, but slow
	 * 					 4 - Pretty Good and fast 
	 * 					 5 - Good, but slow
	 * 					 6 - Good and fast
	 * 					 8 - Very good, but slow
	 * 					 9 - Very good and fast
	 * 					10 - perfect and lightening fast
	 * 					11 - over the top to overrule it all
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
	abstract public function canHandleSearch( $query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps  );

	/**
	 * Performs the search request.
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
	 * @param array $areas
	 * @return mixed WflQueryObjectsResponse or WflNamedQueryResponse
	 */
	abstract public function doSearch( $query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps, $areas=array());
			
	/**
	 * Checks if a property is indexed, searchable, by at least one search engine.
	 *
	 * @param string $propertyName the property to be checked
	 * @return bool true if searchable else false.
	 */
	abstract public function  isPropertySearchable ( $propertyName );

	/**
	 * Returns the properties that, according to the search engine, must be indexed. 
	 * 
	 * @return string[] Properties that are needed to (re)build the index.
	 */
	public function getPropertiesToIndex()
	{
		return array();
	}	

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
