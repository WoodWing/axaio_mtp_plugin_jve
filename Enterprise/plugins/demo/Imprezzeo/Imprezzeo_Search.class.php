<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

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
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 *
 * Imprezzeo Search Connector
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/Search_EnterpriseConnector.class.php';

// Plugin config file:
require_once dirname(__FILE__) . '/config.php';

class Imprezzeo_Search extends Search_EnterpriseConnector
{
	/**
	 * Adds objects to Imprezzeo index
	 *
	 * @param array with objects $objects
	 * @param array $areas
	 * @return array with ids of indexed objects
	 */
	public function indexObjects( $objects, $areas = array('Workflow') )
	{
		require_once dirname(__FILE__) .'/ImprezzeoSearchEngine.class.php';
		$searchEngine = new ImprezzeoSearchEngine;
		$searchEngine->indexObjects( $objects, $areas );
		return $searchEngine->getHandledObjectIds();
	}

	/**
	 * Updates Imprezzeo index.
	 *
	 * @param array of objects $objects
	 * @param array $areas
	 * @return array with ids of updated objects.
	 */
	public function updateObjects( $objects, $areas = array('Workflow') )
	{
		return self::indexObjects( $objects, $areas );
	}

	/**
	 * Removed objects from Imprezzeo index.
	 *
	 * @param array $objectIds
	 * @param boolean $deletedObject (objects reside in smart_objects or smart_
	 * deletedobjects).
	 * @return array with ids of removed objects.
	 */
	public function unIndexObjects( $objectIds, $deletedObject )
	{
		require_once dirname(__FILE__) .'/ImprezzeoSearchEngine.class.php';
		$searchEngine = new ImprezzeoSearchEngine;
		$searchEngine->unindexObjects( $objectIds, $deletedObject);
		return $searchEngine->getHandledObjectIds();
	}

	/**
	 * handleSearch
	 * See Search_EnterpriseConnector for comments
	 */
	public function canHandleSearch( $query, $args, $firstEntry, $maxEntries, $mode, $hierarchical, $queryOrder, $minimalProps, $requestProps  )
	{
		// Make analyzer smile:
		$firstEntry=$firstEntry; $maxEntries=$maxEntries; $mode=$mode; $hierarchical=$hierarchical; $queryOrder=$queryOrder; $minimalProps=$minimalProps; $requestProps=$requestProps;
		// Imprezzeo only offers MLT for images, so check if this is MLTSearch and all IDs are images
		// Default we say no:
		$resp = 0;
		if( $query == 'MLTSearch' ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

			foreach ($args as $arg) {
				if ($arg->Property == 'ID') {
					$objectId = $arg->Value;		
					if( DBObject::getObjectType($objectId) == 'Image' ) {
						$resp = 9;
					} else {
						// If there is any non-image in the list, the answer is no...
						return 0;
					}
				}
			}
		}
		return $resp;
	}

	/**
	 * doSearch
	 * See Search_EnterpriseConnector for comments
	 */
	public function doSearch( $query, $args, $firstEntry, $maxEntries, $mode, $hierarchical, $queryOrder, $minimalProps, $requestProps, $areas = array('Workflow') )
	{
		// Make analyzer smile:
		$hierarchical=$hierarchical; $mode=$mode; $minimalProps=$minimalProps; $requestProps=$requestProps;
		if( $query == 'MLTSearch' ) {
			require_once dirname(__FILE__) .'/ImprezzeoSearchEngine.class.php';
			$searchEngine = new ImprezzeoSearchEngine($args, $firstEntry, $maxEntries, $mode, $queryOrder, $minimalProps, $requestProps);
			$searchEngine->mltSearch( $areas );
				
			require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsResponse.class.php';
			return new WflQueryObjectsResponse( 
					$searchEngine->getResultColumns(),
					$searchEngine->getResults(),
					null,
					null,
					null,
					null,
					$searchEngine->getResultFirstEntry(),
					$searchEngine->getResultListedEntries(),
					strval($searchEngine->getResultTotalEntries()),
					null,
					$searchEngine->getFacets(),
					array( new Feature( 'MLTSearch' )) );
		} else {
			// May never happen, server error
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Imprezzeo cannot handle ' . $query);
		}
	}
	
	/**
	 * Returns objects type that can be handled by search engine.
	 *
	 * @param string $objectType
	 * @return boolean true if supported else false.
	 */
	public static function supportedObjectTypes($objectType)
	{
		if ($objectType == 'Image') {
			return true;		
		}
		
		return false;
	}
}
