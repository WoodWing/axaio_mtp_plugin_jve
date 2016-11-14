<?php
/**
 * @package 	Enterprise
 * @subpackage	ServerPlugins
 * @since 		v7.0
 * @copyright	2008-2009 WoodWing Software bv. All Rights Reserved.
 *
 * Solr Search integration
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/Search_EnterpriseConnector.class.php';
require_once BASEDIR.'/config/config_solr.php';

class SolrSearch_Search extends Search_EnterpriseConnector
{
	/**
	 * See Search_EnterpriseConnector for comments
	 * @inheritdoc
	 */
	public function indexObjects( $objects, $areas = array('Workflow') )
	{
		self::setClosedProperty($objects, $areas);

		require_once BASEDIR .'/server/plugins/SolrSearch/SolrSearchEngine.class.php';
		$searchEngine = new SolrSearchEngine();
		$searchEngine->indexObjects( $objects, $areas, $this->directCommit );
		return $searchEngine->getHandledObjectIds();
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * @inheritdoc
	 */
	public function updateObjects( $objects, $areas = array('Workflow') )
	{
		return self::indexObjects( $objects, $areas ); // indexing existing objects is no problem for Solr
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * @inheritdoc
	 */
	public function updateObjectProperties( $objectIDs, $metaDataValues )
	{
		require_once BASEDIR .'/server/plugins/SolrSearch/SolrSearchEngine.class.php';
		$searchEngine = new SolrSearchEngine();
		$searchEngine->updateObjectProperties( $objectIDs, $metaDataValues, $this->directCommit );
		return $searchEngine->getHandledObjectIds();
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * @inheritdoc
	 */
	public function unIndexObjects( $objectIds, $deletedObject )
	{
		require_once BASEDIR .'/server/plugins/SolrSearch/SolrSearchEngine.class.php';
		$searchEngine = new SolrSearchEngine();
		$searchEngine->unindexObjects( $objectIds );
		return $searchEngine->getHandledObjectIds();
	}
	
	/**
	 * See Search_EnterpriseConnector for comments
	 * @inheritdoc
	 */
	public function optimizeIndexes()
	{
		require_once BASEDIR .'/server/plugins/SolrSearch/SolrSearchEngine.class.php';
		$searchEngine = new SolrSearchEngine();
		$searchEngine->optimize();
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * @inheritdoc
	 */
	public function canHandleSearch( $query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps  )
	{
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
		$inbox = BizNamedQuery::getInboxQueryName();
		$retVal = 0;

		// Check if Solr supports the order by columns. 
		if ( !$this->supportedSortByFields( $queryOrder )) {
			$retVal = 0;
			return $retVal;
		}
		
		switch( $query ) {
			case '_QueryObjects_':
			case 'PublishFormTemplates':
				// Check if args are part of index. If so, we handle QueryObjects:
				$solrProps = unserialize(SOLR_INDEX_FIELDS);

				// Check if all query params are in Solr Index. If so, we can handle this
				if( !empty($params) ) {
					foreach( $params as $param ) {
						if( $param->Property != 'Search' && !in_array( $param->Property, $solrProps ) ) {
							LogHandler::Log( 'Solr', 'DEBUG', 'Search parameter not supported: '. $param->Property );
							break 2; // jump out: foreach + switch!
						}
						if( $param->Operation != '=' ) {
							LogHandler::Log( 'Solr', 'DEBUG', 'Search operation not supported: '. $param->Operation );
							break 2; // jump out: foreach + switch!
						}
					}
				}
				// All query params found, so we can handle this query,.
				$retVal += 6;  // Good and fast
				break;
				
			case 'Inbox':
			case $inbox:
				// We can do Inbox 
				$retVal += 6;  // Good and fast
				break;
			case '_FacetsOnly_':
				$retVal += 6;  // Good and fast
				break;
			default:
				break;
		}
		
		if( $retVal > 0 ) {
			LogHandler::Log( 'Solr', 'DEBUG', 'Query "'.$query.'" is supported.' );
		}
		return $retVal;
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * @inheritdoc
	 */
	public function doSearch( $query, $params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps, $areas = array('Workflow'))
	{
		self::setClosedParam($params);
		self::setAreasParam($params, $areas);
		
		require_once BASEDIR .'/server/plugins/SolrSearch/SolrSearchEngine.class.php';
		$searchEngine = new SolrSearchEngine($params, $firstEntry, $maxEntries, $queryMode, $hierarchical, $queryOrder, $minimalProps, $requestProps);
		
		require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
		$inbox = BizNamedQuery::getInboxQueryName();

		LogHandler::Log( 'Solr', 'DEBUG', 'Handling query "'.$query.'"...' );
		switch( $query ) {
			case '_QueryObjects_':
			case 'PublishFormTemplates':
				$searchEngine->search( $areas ); 
				break;
			case 'Inbox':
			case $inbox:
				$searchEngine->inboxSearch();
				break;
			case '_FacetsOnly_':
				$searchEngine->facetOnlySearch();
				break;
		}

		$response = null;
		if( $query == $inbox || $query == 'Inbox' ) {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
			$response = new WflNamedQueryResponse();
		}  elseif ( $query == '_FacetsOnly_' ) {
			// Used to return the facets of the items within a dossier.
			return $searchEngine->getFacets();
		} else if( $query == 'PublishFormTemplates' ) {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
			$response = new WflNamedQueryResponse();			
		} else {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsResponse.class.php';
			$response = new WflQueryObjectsResponse();
		}

		$response->Columns = $searchEngine->getResultColumns();
		$response->Rows = $searchEngine->getResults();
		$response->ChildColumns = $searchEngine->getChildResultColumns();
		$response->ChildRows = $searchEngine->getChildResults();
		$response->ComponentColumns = $searchEngine->getResultComponentColumns();
		$response->ComponentRows = $searchEngine->getResultComponents();
		$response->FirstEntry = $searchEngine-> getResultFirstEntry();
		$response->ListedEntries = $searchEngine->getResultListedEntries();
		$response->TotalEntries = strval( $searchEngine->getResultTotalEntries() );
		$response->Facets = $searchEngine->getFacets();
		$response->SearchFeatures = array();

		return $response;
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * 
	 * @inheritdoc
	 */
	public function isPropertySearchable( $propertyName )
	{
		require_once BASEDIR .'/server/plugins/SolrSearch/SolrSearchEngine.class.php';
		$searchEngine = new SolrSearchEngine();
		return $searchEngine->isPropertySearchable( $propertyName );
	}

	/**
	 * See Search_EnterpriseConnector for comments
	 * 
	 * @return array with properties that must be indexed.
	 */
	public function  getPropertiesToIndex()
	{
		require_once BASEDIR .'/server/plugins/SolrSearch/SolrSearchEngine.class.php';
		$searchEngine = new SolrSearchEngine();
		return $searchEngine->getPropertiesToIndex();
	}

	/**
	 * Set the _Closed property on an Enterprise Object.
	 * 
	 * If you search without Solr, closed objects aren't included. If we don't add
	 * closed objects to Solr, we cannot find the closed objects at all. So we
	 * must index a closed property.
	 * 
	 * To have Solr index the database "closed" attribute, the property "_Closed"
	 * has to be added to the object because this property isn't available as a 
	 * standard property and all interfaces work with Enterprise objects.
	 * 
	 * See BZ#17707
	 *
	 * @param array $objects array of Object
	 * @param array $areas
	 */
	protected static function setClosedProperty($objects, $areas = array('Workflow'))
	{
		LogHandler::Log(__CLASS__, 'DEBUG', 'Set _Closed property');
		$objectIds = array();
		foreach ($objects as $object){
			$objectIds[intval($object->MetaData->BasicMetaData->ID)] = $object;
		}
		
		if (count($objectIds) > 0){
			require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
			$dbo = in_array('Workflow',$areas) ? 'objects' : 'deletedobjects';
			$rows = DBBase::listRows($dbo, 'id', '', 'id IN (' . implode(',', array_keys($objectIds)) . ')', array('id', 'closed'), array());
			
			foreach ($rows as $objectId => $row){
				// add _Closed property to object
				$objectIds[$objectId]->_Closed = $row['closed'] == 'on' ? true : false;
			}
		}
	}
	
	/**
	 * Add a "Closed" = "false" parameter to the query parameters if it
	 * isn't queried yet.
	 * 
	 * To search for closed and not closed objects, you have to use two
	 * parameters: "Closed" = "false" and "Closed" = "true"
	 *
	 * @param array $params array of QueryParam
	 */
	protected static function setClosedParam(&$params)
	{
		// check if Closed param is given
		$closedParam = false;
		if (is_array($params)) { // In case of no parameters $params is passed as null
			foreach ($params as $param){
				if ($param->Property == 'Closed'){
					$closedParam = true;
					break;
				}
			}
		}
		if ( ! $closedParam ) {
			// add default: closed = false
			LogHandler::Log(__CLASS__, 'DEBUG', 'Add default Closed = false parameter');
			$params[] = new QueryParam('Closed', '=', 'false', false); 
		}
		
	}
	
	
	
	/**
	 * Add a $Area param for Solr Search.
	 * $Area could be 'Workflow' or 'Trash'
	 * Areas:"Workflow" or Areas:"Trash"
	 *
	 * @param  array $params array of QueryParam
	 * @param array $areas array of area: Could be Workflow or Trash
	 */
	protected static function setAreasParam(&$params, $areas = array('Workflow'))
	{
		// check if Areas param is given
		$areasParam = false;
		if (is_array($params)) { // In case of no parameters $params is passed as null
			foreach ($params as $param){
				if ($param->Property == 'Areas'){
					$areasParam = true;
					break;
				}
			}
		}
		
		if ( ! $areasParam ) {
			// add default: Areas:"Workflow" OR Areas:"Trash"
			LogHandler::Log(__CLASS__, 'DEBUG', 'Add default Areas = Workflow/Trash parameter');
			$params[] = new QueryParam('Areas','=',$areas['0'],false);
		}
	}

	/**
	 * Checks if the sort by can be handled by Solr.
	 * @param QueryOrder[] $queryOrder
	 * @return bool Can be handled true/false
	 */
	private static function supportedSortByFields( $queryOrder )
	{
		$solrProps = unserialize( SOLR_INDEX_FIELDS );
		if ( $queryOrder ) foreach ( $queryOrder as $order ) {
			if ( $order->Property === 'State' && (defined( 'SORT_ON_STATE_ORDER' ) && SORT_ON_STATE_ORDER === true ) ) {
				return false;
			} elseif ( !in_array( $order->Property, $solrProps ) ) {
				return false;
			}
		}

		return true;
	}
}
