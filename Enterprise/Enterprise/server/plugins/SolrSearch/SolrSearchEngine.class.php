<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	2008-2009 WoodWing Software bv. All Rights Reserved.
 *
 * Search integration - Library class implementing Solr search
 */
// Solr Library:
require_once BASEDIR .'/server/bizclasses/BizQuery.class.php';

class SolrSearchEngine extends BizQuery
{
	const TODAY = 'Today';
	const YESTERDAY = 'Yesterday';
	const THISWEEK = 'This_Week';
	const THISMONTH = 'This_Month';
	const THISYEAR = 'This_Year';
	const BEYONDTHISYEAR = '>_This_Year';

	// Solarium Client
	protected $index = null;

	// Search parameters
	protected $firstEntry = 0;
	protected $maxEntries = 999999;
	protected $searchParams = array();
	protected $order = array();
	protected $facetFields = array();
	protected $fieldsToIndex = array();
	protected $minimalProps = null;
	protected $requestProps = null;
	protected $queryMode = null;
	protected $hierarchical = false;
	protected $facetQueries = array();

	// Index/Unindex results
	protected $handledObjectIds = array();

	// Search results
	protected $results = array();
	protected $childRows = null;
	protected $facets = array();
	protected $resultFirstEntry	= 0;
	protected $resultListedEntries	= 0;
	protected $resultTotalEntries = 0;
	protected $columns = array();
	protected $resultComponentColumns = null;
	protected $resultComponents = null;

	/**
	 * Constructor
	 *
	 * Establish and test Solr connection and sets up the default search parameters shared by all search functions
	 * supported by this engine (search, facetSearch and inboxSearch).
	 */
	public function __construct( $searchParams = array(), $firstEntry = 0, $maxEntries = 0, $queryMode = null, $hierarchical = false, $order = array(), $minimalProps = null, $requestProps = null )
	{
		require_once BASEDIR .'/server/plugins/SolrSearch/SolariumClient.class.php';

		// Solarium client.
		$this->index = new SolariumClient();

		if ( !$this->index->pingSolrHost() ) {
			throw new BizException( 'ERR_SOLR_SEARCH', 'Server', null, null, array('Error connecting to Solr'), 'ERROR' );
		}
		LogHandler::Log( 'Solr', 'DEBUG', 'Connected to Solr' );

		$this->firstEntry = $firstEntry;
		// 0 means all, in Solr 'rows' is set to an extreme value
		if ($maxEntries == 0) {
			$this->maxEntries = 999999;
		} else {
			$this->maxEntries = $maxEntries;
		}
		$this->queryMode = $queryMode;
		$this->order = $order;
		$this->minimalProps = $minimalProps;
		$this->requestProps = $requestProps;
		$this->hierarchical = $hierarchical;

		// If search is done on one specific object type, we'll use object type specific facets
		// So check if search is done on one single object type
		$searchOneType = false;
		$objectType = '';
		if( !empty($searchParams) ) {
			foreach( $searchParams as $key => $param ) {
				if( $param->Property == 'Type' ) {
					if( $searchOneType ) {
						// Oops, we already had a type in the param list. So not one single type
						$searchOneType 	= false;
						$objectType 	= '';
						break;
					} else {
						$searchOneType = true;
						$objectType 	= $param->Value;
					}
					if ($param->Value == 'DossierItems') {
						// 'Fake' type used for facets for items within dossier.
						// Must be removed from query params because this type is unknown within Solr.
						unset($searchParams[$key]);
					}
				}
			}
		}
		$this->searchParams = $searchParams;
		if( $searchOneType ) {
			switch( $objectType ) {
				case 'Dossier':
					$this->facetFields = unserialize(SOLR_DOSSIER_FACETS);
					break;
				case 'Image':
					$this->facetFields = unserialize(SOLR_IMAGE_FACETS);
					break;
				case 'Article':
					$this->facetFields = unserialize(SOLR_ARTICLE_FACETS);
					break;
				case 'Spreadsheet':
					$this->facetFields = unserialize(SOLR_SPREADSHEET_FACETS);
					break;
				case 'Video':
					$this->facetFields = unserialize(SOLR_VIDEO_FACETS);
					break;
				case 'Audio':
					$this->facetFields = unserialize(SOLR_AUDIO_FACETS);
					break;
				case 'Layout':
					$this->facetFields = unserialize(SOLR_LAYOUT_FACETS);
					break;
				case 'DossierItems':
					$this->facetFields = unserialize(SOLR_DOSSIERITEMS_FACETS);
					break;
			}
		}
		if ( empty($this->facetFields) && defined('SOLR_GENERAL_FACETS')) {
			$this->facetFields = unserialize(SOLR_GENERAL_FACETS);
		}

		if (defined('SOLR_INDEX_FIELDS')) {
			$this->fieldsToIndex = unserialize(SOLR_INDEX_FIELDS);
		}
	}

	/**
	 * Search on user entered phrase. See Optimize for performance notes.
	 *
	 * @param array $areas Area to which the search is restricted (e.g. Workflow)
	 */
	public function search( $areas = array('Workflow') )
	{
		if( is_null( $this->index ) ) {
			return; // quit when bad config / no access / Solr server down
		}

		PerformanceProfiler::startProfile( 'Solr search', 3 );

		// Create a new Query object
		$query = $this->index->createSelect( array(
			'start'         => $this->firstEntry,
			'rows'          => $this->maxEntries,
			'fields'        => array('ID'),  // Fields returned by Solr.
		));
		if( !is_null($query) ) {
			// Add sorting
			$this->addSortingToQuery( $query );

			// Add facet fields/queries to query object
			$this->addFacetsFieldsSearchParams( $query );

			// Add query parameters (search query term & filter queries)
			$this->addQueryParamsToQuery( $query );

			// Add authorizations of user as a filter query
			$this->addAuthorizationToQuery( $query );

			// Execute query and parse the results
			$resultSet = $this->index->executeSelect( $query );

			if( !is_null($resultSet) ) {
				$this->parseQueryResult( $resultSet, $areas );
			}
		}

		PerformanceProfiler::stopProfile( 'Solr search', 3 );
	}

	/**
	 * Handles the Inbox search (showing all objects routed to the user or the groups
	 * of the user).
	 */
	public function inboxSearch()
	{
		if( is_null($this->index) ) {
			return; // quit when bad config / no access / Solr server down
		}

		PerformanceProfiler::startProfile( 'Solr inbox search', 3 );

		// Create a new Query object
		$query = $this->index->createSelect( array(
			'start'         => $this->firstEntry,
			'rows'          => $this->maxEntries,
			'fields'        => array('ID'),  // Fields returned by Solr. Inbox search only needs the object ID
		));
		if( !is_null($query) ) {
			// Minimal properties needed for "Inbox' query. BZ#16495
			$minimalPropsInbox = array('ID', 'Type', 'Name', 'State', 'Format', 'LockedBy', 'Category' ,'PublicationId', 'SectionId', 'StateId', 'PubChannelIds');
			if ($this->minimalProps == null || empty($this->minimalProps)) {
				$this->minimalProps = $minimalPropsInbox;
			} else {
				$this->minimalProps = array_merge($this->minimalProps, $minimalPropsInbox);
			}

			// Add sorting
			$this->addSortingToQuery( $query );

			// Add query parameters (search query term & filter queries)
			$this->addQueryParamsToQuery( $query );

			// Add filter query for inbox
			$this->addSearchOnInboxFilter( $query );

			// Execute query and parse the results
			$resultSet = $this->index->executeSelect( $query );

			if( !is_null($resultSet) ) {
				$this->parseQueryResult( $resultSet, array('Workflow'), false /* facetsOnly */ );
			}
		}

		PerformanceProfiler::stopProfile( 'Solr inbox search', 3 );
	}

	/**
	 * Searches for facets only.
	 *
	 * After the query is executed by Solr only the Facets are retrieved from the
	 * Solr response. No rows are build and returned.
	 * Because no rows are returned the area (Workflow/Trash) does not matter.
	 *
	 * This search is used for the feature "FacetsInDossier".
	 */
	public function facetOnlySearch()
	{
		if( is_null($this->index) ) {
			return; // quit when bad config / no access / Solr server down
		}

		PerformanceProfiler::startProfile( 'Solr facet only search', 3 );

		// Create a new Query object
		$query = $this->index->createSelect( array(
			'start'         => 0,
			'rows'          => 0, // Facets only, don't care about documents search result
			'fields'        => array('ID'),  // Fields returned by Solr.
		));
		if( !is_null($query) ) {
			// Add facet fields/queries to query object
			$this->addFacetsFieldsSearchParams( $query );

			// Add query parameters (search query term & filter queries)
			$this->addQueryParamsToQuery( $query );

			// Add authorizations of user as a filter query
			$this->addAuthorizationToQuery( $query );

			// Execute query and parse the results
			$resultSet = $this->index->executeSelect( $query );
			if( !is_null($resultSet) ) {
				$this->parseQueryResult( $resultSet, array('Workflow'), true /* facetsOnly */ );
			}
		}

		PerformanceProfiler::stopProfile( 'Solr facet only search', 3 );
	}

	/**
	 * Parses the result from Solarium.
	 *
	 * @param Solarium/QueryType/Select/Result/Result $searchResult result set returned by Solarium
	 * @param array $areas
	 * @param Bool $facetsOnly Stop as soon as facets are parsed. Ignore other results.
	 * @throws BizException
	 */
	private function parseQueryResult( $searchResult, $areas = array('Workflow'), $facetsOnly = false )
	{
		if ( $searchResult->getResponse()->getStatusCode() != "200" ) {
			throw new BizException( 'ERR_SOLR_SEARCH', 'Server', null, null, array($searchResult->getResponse()->getStatusMessage()), 'ERROR' );
		}

		$facetsInfo = $searchResult->getFacetSet();
		if ( $facetsInfo ) {
			$facetsInfo = $searchResult->getFacetSet();
			$this->buildFacets($facetsInfo);
		}

		// Facets are ready. So return if searching on facets only.
		if ( $facetsOnly ) {
			return;
		}

		$documents = array(); //documents found for Keyword search
		if ($searchResult->getNumFound() > 0) {
			$documents = $searchResult->getDocuments();
		}

		if (!empty($documents)) {
			$objectIds = array();
			foreach ($documents as $doc) {
				$docFields = $doc->getFields();
				$id = $docFields['ID'];
				$objectIds[$id] = $id;
			}
			// Read objects
			$this->results = $this->buildRows( $objectIds, $areas );
		}

		$this->resultFirstEntry		= $searchResult->getQuery()->getOption('start') + 1;
		$this->resultListedEntries	= $searchResult->count();
		$this->resultTotalEntries	= $searchResult->getNumFound();

		if (!empty($this->results)) {
			$this->columns = self::getColumns($this->results);
		} else {
			$requestedPropNames = self::getPropNames( $this->queryMode, $this->minimalProps, $this->requestProps, $areas );
			$this->columns = self::getColumns(array(array_flip($requestedPropNames)));
		}
		$this->results = self::getRows($this->results);
	}

	/**
	 * Adds Enterprise objects to the index.
	 *
	 * @param array of Object	$object		Enterprise objects to index
	 * @param array $areas
	 * @param Bool $directCommit
	 * @throws BizException
	 */
	public function indexObjects( $objects, $areas = array('Workflow'), $directCommit=false )
	{
		$this->handledObjectIds = array();
		$objIds = array();
		if( is_null($this->index) ) {
			return; // quit when bad config / no access / Solr server down
		}
		foreach( $objects as $object ) {
			$objIds[] = $object->MetaData->BasicMetaData->ID;
		}

		PerformanceProfiler::startProfile( 'Solr index', 3 );
		try {
			// Gather the documents to be indexed.
			$documents = array();
			$specialDataByObjId = BizProperty::updateIndexFieldWithSpecialProperties( $objIds, $this->fieldsToIndex );
			foreach( $objects as $object ) {
				$objId = $object->MetaData->BasicMetaData->ID;
				$specialData = array_key_exists($objId, $specialDataByObjId) ? $specialDataByObjId[ $objId ] : array(); 
				$document = $this->formatObjectForIndexing( $object, $areas, $specialData );
				$documents[ $object->MetaData->BasicMetaData->ID ] = $document;
				// Mark Objects as handled.
			}

			// Index the documents.
			$resultStatus = $this->commitToIndex( $documents, $directCommit );
			if( $resultStatus ) {
				$this->handledObjectIds = $objIds; // For the caller to access this data.
			}
		} catch( BizException $e ) {
			PerformanceProfiler::stopProfile( 'Solr index', 3 );
			throw $e;
		}
		PerformanceProfiler::stopProfile( 'Solr index', 3 );
	}

	/**
	 * Remove given Enterprise objects from index.
	 *
	 * The objects are assumed to be deleted first (=> so they reside at smart_deletedobjects table).
	 *
	 * @param int[] $ids List of object ids to be unindexed.
	 * @throws BizException
	 */
	public function unindexObjects( $ids )
	{
		if( is_null($this->index) ) {
			return; // quit when bad config / no access / Solr server down
		}

		PerformanceProfiler::startProfile( 'Solr unindex', 3 );
		try {
			$this->index->unindexObjects( $ids );
			if (count($ids) > 0 ) {
				$this->handledObjectIds +=  $ids ;
			}
		} catch( BizException $e ) {
			PerformanceProfiler::stopProfile( 'Solr unindex', 3 );
			throw $e;
		}
		PerformanceProfiler::stopProfile( 'Solr unindex', 3 );
	}

	/**
	 * Optimizes indexes. EXPENSIVE operation!
	 *
	 * Optimizing the index should be done periodical.
	 * With 1300 objects in index, a search operation (that were never optimized) took 0.2 sec.
	 * Running optimize took 9.9 seconds after which the same search operation took 0.02 sec.
	 * Adding another document to the index, the optimize took again 9 sec.
	 * Optimize on 5000 docs (that were never optimized) took 50 sec.
	 *
	 * @throws BizException
	 */
	public function optimize( )
	{
		if( is_null($this->index) ) {
			return; // quit when bad config / no access / Solr server down
		}

		PerformanceProfiler::startProfile( 'Solr optimize', 3 );
		try {
			$this->index->optimize();
		} catch ( BizException $e ) {
			PerformanceProfiler::stopProfile( 'Solr optimize', 3 );
			throw $e;
		}
		PerformanceProfiler::stopProfile( 'Solr optimize', 3 );
	}

	/**
	 * Updates a set of metaDataValues for a list of Object IDs.
	 *
	 * @param array $ids Enterprise object ids to update
	 * @param array $metaDataValues MetaDataValues to be updated
	 * @param Bool $directCommit Direct commit setting for Solr
	 * @throws BizException
	 */
	public function updateObjectProperties( $objectIDs, $metaDataValues, $directCommit=false  )
	{
		if( is_null($this->index) ) {
			return; // quit when bad config / no access / Solr server down
		}

		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';

		PerformanceProfiler::startProfile( 'Solr update fields', 3 );

		// Add Category and State names if needed. Normally only the Ids are sent.
		$metaDataCategoryId = $this->findMetaDataByField( 'CategoryId', $metaDataValues );
		if( $metaDataCategoryId != null && $this->findMetaDataByField( 'Category', $metaDataValues ) == null ) {
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'Category';
			$propValue = new PropertyValue();
			$propValue->Value = DBSection::getSectionName( $this->getFirstMetaDataValue( $metaDataCategoryId ) );
			$mdValue->PropertyValues = array( $propValue );
			$metaDataValues[] = $mdValue;
		}

		$metaDataStateId = $this->findMetaDataByField( 'StateId', $metaDataValues );
		if( $metaDataStateId != null && $this->findMetaDataByField( 'State', $metaDataValues ) == null ) {
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'State';
			$propValue = new PropertyValue();
			$propValue->Value = DBWorkflow::getStatusName( $this->getFirstMetaDataValue( $metaDataStateId ) );
			$mdValue->PropertyValues = array( $propValue );
			$metaDataValues[] = $mdValue;
		}

		try {
			$indexValues = $this->formatMetaDataValuesForIndexing( $metaDataValues );

			if( !empty( $indexValues ) ) {
				// Update fields of indexed documents.
				$result = $this->index->updateObjectsFields( $objectIDs, $indexValues, $directCommit );
			}
		} catch( BizException $e ) {
			PerformanceProfiler::stopProfile( 'Solr index', 3 );
			throw $e;
		}
		PerformanceProfiler::stopProfile( 'Solr update fields', 3 );
	}

	/**
	 * Returns the query result facets
	 *
	 * @return array Array of FacetItem instances
	 */
	public function getFacets()
	{
		return $this->facets;
	}

	/**
	 * Returns the first result entry index number.
	 *
	 * @return int
	 */
	public function getResultFirstEntry()
	{
		return $this->resultFirstEntry;
	}

	/**
	 * Returns the number of fetched documents.
	 *
	 * @return int
	 */
	public function getResultListedEntries()
	{
		return $this->resultListedEntries;
	}

	/**
	 * Returns the number of results found matching the base query.
	 *
	 * @return int
	 */
	public function getResultTotalEntries()
	{
		return $this->resultTotalEntries;
	}

	public function getResultColumns()
	{
		return $this->columns;
	}

	/**
	 * Returns found Objects/documents.
	 *
	 * @return array Array containing the Objects
	 */
	public function getResults()
	{
		return $this->results;
	}

	/**
	 * Returns array with handled ObjectIds after calling the index or unindex function.
	 *
	 * @return array Array containing the ObjectIds
	 */
	public function getHandledObjectIds()
	{
		return $this->handledObjectIds;
	}

	/**
	 * Retrieves the childRows and repairs the child Results based on the ChildResultColumns if necessary.
	 *
	 * @return object[] The child results.
	 */
	public function getChildResults()
	{
		return $this->childRows;
	}

	/**
	 * Returns array with result child columns.
	 *
	 * @return array Array containing the child columns
	 */
	public function getChildResultColumns()
	{
		if ($this->hierarchical ) {
			return $this->getResultColumns();
		}
		return null;
	}

	/**
	 * Returns array with result component columns.
	 *
	 * @return array Array containing the component columns
	 */
	public function getResultComponentColumns()
	{
		if ($this->hierarchical) {
			return $this->resultComponentColumns;
		}

		return null;
	}

	/**
	 * Returns array with result columns.
	 *
	 * @return array Array containing the columns
	 */
	public function getResultComponents()
	{
		if ($this->hierarchical) {
			return $this->resultComponents;
		}

		return null;
	}

	/**
	 * Checks if property is indexed/searchable by Solr.
	 *
	 * @param string $propertyName
	 * @return boolean true if property searchable else false.
	 */
	public function isPropertySearchable ( $propertyName )
	{
		$result = false;
		if ((array_search($propertyName, $this->fieldsToIndex) !== false ) || ($propertyName === 'score' )) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Returns the properties that, according to the search engine, must be indexed. 
	 * Some properties are calculated by the search engine and are not 'real' Enterprise properties.
	 * These properties are filleterd out.
	 * 
	 * @return array with properties that must be indexed.
	 */
	public function  getPropertiesToIndex()
	{
		$result = array_diff( $this->fieldsToIndex, array( 'Orientation', 'Areas', 'PubChannelId' ));
		return $result;
	}	

	/**
	 * Maps the index field to a object property and return the value of this
	 * property.
	 *
	 * @param string $fieldToIndex Name of the solr index field
	 * @param object $object Enterprise object
	 * @return value of the property
	 */
	private function mapIndexFieldToProperty($fieldToIndex, $object)
	{
		//@todo add custom field handling
		$value = null;
		if ($fieldToIndex == 'Keywords') {
				if( !empty($object->MetaData->ContentMetaData->Keywords) ){
					$value = implode(',',$object->MetaData->ContentMetaData->Keywords);
				}
		} elseif ( $fieldToIndex == 'Orientation' ) {
			$w = $object->MetaData->ContentMetaData->Width;
			$h = $object->MetaData->ContentMetaData->Height;

			if( $w > $h ) {
				$value = 'LANDSCAPE';
			} else if ( $h > $w ) {
				$value = 'PORTRAIT';
			} else {
				$value = 'SQUARE';
			}
		} else {
			$objectMetaData = $object->MetaData;
			$metaDataPaths = BizProperty::getMetaDataPaths();
			if (array_key_exists($fieldToIndex, $metaDataPaths)) {
				$path = $metaDataPaths[$fieldToIndex];
				if (!empty($path)) {
					eval('isset($objectMetaData->' . $path .') ? $value = $objectMetaData->' . $path . " : '';");
				}
			}
		}
		return $value;
	}

	/**
	 * Commits the added Solr documents to the index and marks the Enterprise object as indexed.
	 *
	 * Optional parameter $directCommit can force solr to forego the autocommit functionality. This option
	 * may be useful in the case a race condition might occur, but should be used with caution, as overuse
	 * of this option might affect performance negatively.
	 *
	 * @param array $documents A list of documents to be indexed.
	 * @param bool $directCommit Whether to forego the autocommit functionality or not.
	 * @throws BizException
	 * @return bool True when the indexing is successful, false otherwise.
	 */
	private function commitToIndex($documents, $directCommit=false)
	{
		LogHandler::Log( 'Solr', 'DEBUG', 'Commit to index with directCommit set to: ' . var_export($directCommit, true) );
		try {
			$resultStatus = false;
			$commit = $directCommit;
			if (!defined( 'SOLR_AUTOCOMMIT' ) || !SOLR_AUTOCOMMIT || $directCommit ) {
				$commit = true;
			}

			PerformanceProfiler::startProfile( 'Solr Add Documents', 3 );
			$resultStatus = $this->index->indexObjects( $documents, $commit );
			PerformanceProfiler::stopProfile( 'Solr Add Documents', 3 );
		} catch ( BizException $e ) {
			PerformanceProfiler::stopProfile( 'Solr Add Documents', 3 );
			throw $e;
		}

		return $resultStatus;
	}

	/**
	 * Build a filter query to search Inbox results.
	 *
	 * The filter query object is returned for further modification if desired.
	 *
	 * @param Solarium/QueryType/Select/Query $query the query object being build
	 * @return Solarium/QueryType/Select/Query/FilterQuery
	 */
	private function addSearchOnInboxFilter($query)
	{
		require_once BASEDIR . '/server/bizclasses/BizUser.class.php';
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';

		$shortUserName = BizSession::getShortUserName();
		$userRow = DBUser::getUser($shortUserName);
		$userId = $userRow['id'];
		$userFullName = $userRow['fullname'];
		$groups = BizUser::getMemberships($userId);

		$routeParams = array();
		$routetoFilter = '(';
		$routeParams[] = $userFullName;
		$routetoFilter .= 'RouteTo:%P'.(count($routeParams)).'%';
		$routeParams[] = $shortUserName;
		$routetoFilter .= 'RouteTo:%P'.(count($routeParams)).'%';
		// Adding the short username to query is needed because CS sends in case of the MultiSetProperties request,
		// the short username to the Server. This is incorrect but for 9.3.0 it is to risky to make the change at
		// CS side. A new issue is created to solve this correctly in the next version. See BZ#36538 for more info.
		
		foreach ($groups as $group) {
			$routetoFilter .= ' OR ';
			$routeParams[] = $group->Name;
			$routetoFilter .= 'RouteTo:%P'.(count($routeParams)).'%';
		}
		$routetoFilter .= ')';

		return $query->createFilterQuery("_".count($query->getFilterQueries()))->setQuery($routetoFilter, $routeParams);
	}

	/**
	 * Adds the facet parameters to the query sent Solr.
	 *
	 * Facets are defined in config_solr. If a facet is used as search parameter
	 * it will not end up the list of facets (E.g. if a specific Brand is used
	 * as search parameter Brands will not show up in the facets).
	 *
	 * @param Solarium/QueryType/Select/Query $query Query select object being build
	 *
	 */
	private function addFacetsFieldsSearchParams( $query )
	{
		if( !empty($this->facetFields) ) {
			// get the facetset component
			$facetSet = $query->getFacetSet();
			$facetSet->setMinCount( 1 ); // Return only facet values with more than one hit
			$facetSet->setSort( 'lex' ); // Sort facet items alphabetically

			foreach ($this->facetFields as $facetField) {
				$type = $this->getFieldType($facetField);
				if ($type == 'date' || $type == 'datetime') {
					$this->addDateFacet($facetSet, $facetField);
				} elseif ( $type == 'int' ) {
					// Note: this adds multiple facet queries and not facet fields!
					$this->addIntegerFacet($facetSet, $facetField);
				} else {

					$facetSet->createFacetField(array('key'=>$facetField, 'field'=>$facetField ));
				}
			}
		}

		// Other things that would be good:
		// - facets per date modified category: last 24 hours, last week, last month, last quarter, last year, more than 1 year.
		// - For images: orientation (Landscape/Portrait/Square). We could save this in index calculated from width/depth
		// - For images: colorspace
		// - For articles: number of words and number of characters (in ranges?)
		// - Credit
		// - Source
		// - modifier
		// - Author
		// - Status name
		// - Rating
		// Requires these fields to be saved in index. (this will require that SetObjectProperties also triggers a re-index)
		// Question: the content facets also include short and simple words, can we somehow exclude these? Might be matter of correct type in schema.xml
		// Note: facets with just one field can be suppressed in output (nothing to filter on)
	}

	/**
	 * Adds the main query and filter queries to the Query object being build.
	 *
	 * Based on the Search Params a query build. The parameter 'Search' contains
	 * the keywords that will be used to search within the CATCHALL. If no keyword is
	 * passed we return everything.
	 *
	 * @param Solarium/QueryType/Select/Query $query Query select object being build
	 */
	private function addQueryParamsToQuery( $query )
	{
		$catchAllSearch = '';
		$searchFields = array();
		if (! empty($this->searchParams)) {
			foreach ($this->searchParams as $searchParam) {
				if ($searchParam->Property == 'Search') {
					if (! empty($searchParam->Value)) {
						$catchAllSearch = $this->handleSearchTerms($searchParam->Value);
					}
				} else {
					if ($searchParam->Value !== BizResources::localize('ACT_ALL')) {
						$searchProperty = $this->convertSearchProperty($searchParam->Property);
						$searchFields[$searchProperty][] = $searchParam->Value;
					}
				}
			}
		}

		// If Search parameter is not set we ignore it by asking for all objects.
		// This is handled by the q.alt parameter in solrconfig.xml
		if( mb_strpos($catchAllSearch, ':') !== false ) { //Found
			$query->addParam( "qt", 'standard' );
			//Use standard requestHandler instead of WoodWing requestHandler (power users only)
		}

		$query->SetQuery( $catchAllSearch );

		$this->addFilterQuery($query, $searchFields);
	}

	/**
	 * Based on the query parameters next to the 'Search' parameter a filter is
	 * composed.
	 *
	 * Each parameter can have multiple values. So we can get:
	 * fq=(Brand:WW News OR Brand:MyNews) AND (Type:Layout). In case of 'IssueId
	 * and EditionId the filter is extended to 'Issues' and 'Editions'.
	 *
	 * @param Solarium/QueryType/Select/Query $query the query object being build
	 * @param array $searchFields contains for each field the values to filter on.
	 */
	private function addFilterQuery($query, $searchFields)
	{
		$and = '';
		$fq_filter = '';
		$fq_params = array();
		foreach ($searchFields as $fieldName => $searchValues) {
			$type = $this->getFieldType($fieldName);
			$fq_filter .= "$and(";
			$or = '';
			foreach ($searchValues as $searchValue) {
				$fq_filter .= "$or$fieldName:";
				if ($type == 'date' || $type == 'datetime') {
					$fq_filter .= $this->addDateSearch($searchValue);
				} else {
					if ($type == 'int' || $type  == 'double') {
						$fq_filter .= $searchValue;
					} else {
						if (empty($searchValue)) {
							// Just searching on '' doesn't work. Range does.
							$fq_filter .= "[* TO '']";
						} else {
							$fq_params[] = $searchValue;
							$fq_filter .= '%P'.(count($fq_params)).'%';
						}
					}
				}
				$or = ' OR ';
			}
			$fq_filter .= ')';
			$and = ' AND ';
		}
		if (! empty($fq_filter)) {
			$fq = $query->createFilterQuery("_".count($query->getFilterQueries()))->setQuery($fq_filter, $fq_params);
			LogHandler::Log( 'Solr', 'INFO', 'Search filter: '.$fq->getQuery() );
		}
	}

	/**
	 * Add the access rights to the query.
	 *
	 * The user needs 'List in Search Result' and 'Read' rights. These rights are defined
	 * on 'Brand' level or 'Issue' level in case of 'overrule Brand'. The authorisations
	 * are sorted on Brand/Category/Section. User is always authorized for objects routed
	 * to him (incl. personal state). Furthermore the user is entitled to see all objects
	 * of Brands for which he is admin.
	 *
	 * @param Solarium/QueryType/Select/Query $query the query object being build
	 */
	private function addAuthorizationToQuery( $query )
	{
		$user = BizSession::getShortUserName();
		// >>> Get Brand Admin rights
		$authPublAdmin = DBUser::getListBrandsByPubAdmin( $user );
		$handled = array();
		$authorizationFilterPublAdm = $this->composeAuthBrandAdmin( $authPublAdmin, $handled ); // <<<
	
		// >>> Get User authorizations on Brand and Overrule Brand Issue level.
		$listRight = (BizSession::getServiceName() == 'WflGetPagesInfo') ? 11 : 1;
		$authorizations = DBUser::getListReadAccessBrandLevel( $user, $listRight ); // Authorizations on Brand level.
		$authorizationFilterPubl = $this->composeAuthorization( $authorizations, 'publication', $handled );
		$authorizations = DBUser::getListReadAccessIssueLevel( $user, $listRight ); // Authorizations on Overrule Brand level.
		$authorizationFilterIssue = $this->composeAuthorization( $authorizations, 'issue', array() );
		$authorizationFilterPublIssue = $authorizationFilterPubl;
		if ( !empty( $authorizationFilterPublIssue ) && !empty( $authorizationFilterIssue )) {
			$authorizationFilterPublIssue = '('.$authorizationFilterPublIssue.' OR '.$authorizationFilterIssue.')';
		} else {
			$authorizationFilterPublIssue .= $authorizationFilterIssue;
		}
		if ( !empty( $authorizationFilterPublIssue ) ) {
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$isAdmin = DBUser::isAdminUser( $user );
			$authorizationFilterPersonalStatus = ( $isAdmin ) ? '' : 'StateId:[0 TO *]';
			// Filter out 'personal status' (status -1) of other users if user is not an admin.
			// Objects with 'personal status' of the user himself are handled by searchOnInboxFilter().
			// Admins are entitled to see all. See BZ#20808
			if ( !empty( $authorizationFilterPersonalStatus ) ) {
				$authorizationFilterPublIssue = '('.$authorizationFilterPersonalStatus.' AND '.$authorizationFilterPublIssue.')';
			}
		} // <<<
	
		// User is always entitled to objects in the 'Inbox'. 
		$authorizationFilterObject = $this->addSearchOnInboxFilter( $query );
		$authorizationFilter = $authorizationFilterObject->GetQuery();
		// Add Publication Admin rights.
		if ( !empty( $authorizationFilterPublAdm ) ) {
			$authorizationFilter .= ' OR '.$authorizationFilterPublAdm;
		}

		// Add workflow rights (brand level and overrule brand issue level).
		if ( !empty( $authorizationFilterPublIssue ) ) {
			$authorizationFilter .= ' OR '.$authorizationFilterPublIssue;
		}

		$authorizationFilterObject->SetQuery( $authorizationFilter );
	}

	/**
	 * Adds a sorting parameter to the search engine query.
	 * Sorting can only be done on fields that are indexed or if sorting is done on 'Score' (best match).
	 * In case no sorting is requested but a search string is entered the sorting will be on 'Score'. The best match
	 * (highest 'Score') will be returned first.
	 * In case no sorting is requested and no specific search string is entered the last modified object will returned
	 * first. See also EN-86683.
	 *
	 * @param Solarium/QueryType/Select/Query $query the query object being build
	 */
	private function addSortingToQuery( $query )
	{
		$sorts = array();
		if ($this->order ) foreach ( $this->order as $order ) {
			$orderBy = $order->Property == 'Score' ? 'score' : $order->Property;
			if ($this->canBeSorted($orderBy)) {
				$sorts[$orderBy] = $order->Direction ? 'asc' : 'desc';
			}
		} elseif ( $this->hasQuerySearch() ) {
			$sorts['score'] = 'desc';
		}

		if ( empty( $sorts ) )  {
			$sorts['Modified'] = 'desc';
		}

		$query->addSorts( $sorts );
	}

	/**
	 * Check if the 'Search' query parameter is set and not empty. The 'Search' query parameter is set when the user
	 * has entered some string to search on.
	 *
	 * @return bool Search is set as query parameter.
	 */
	private function hasQuerySearch()
	{
		if ($this->searchParams ) foreach ( $this->searchParams as $param ) {
			if ( $param->Property === 'Search' && !empty( $param->Value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the input property can be used for sorting.
	 *
	 * If a property can be used for sorting depends on several factors.
	 * The property must be indexed by Solr.
	 * If the property is a custom field sorting can not be done on 'multilist' or
	 * 'multistring' fields because these are multiValued and cannot be used for sorting.
	 *
	 * @param string $property property to sort on.
	 * @return boolean if can be used to sort on else false.
	 */
	private function canBeSorted($property)
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';

		$result = true;
		if (BizProperty::isCustomPropertyName($property)) {
			$customType = BizProperty::getCustomPropertyType($property);
			if ($customType == 'multilist' || $customType == 'multistring') {
				$result = false;
			}
		}
		else  {
			$result = $this->isPropertySearchable($property);
		}

		return $result;
	}

	/**
	 * Compose a query string with Brand(Issue)/Category/Section combinations to
	 * get only results for which the user is authorized.
	 *
	 * Because state and category ids are unique on brand level (or issue level
	 * in case of 'overrule') the id of the Category and/or State is sufficient.
	 * If an user has no authorization what so ever on publication or issue level
	 * the id is set to -1. This is an invalid id and the search result will become empty.
	 *
	 * @param array $authorizations Array of Brand(Issue)/Category/Section for which
	 * the user has List/Read access.
	 * @param string $level Brand(publication)/Issue(issue)
	 * @param array $handled Already handled Brands (user has Brand Admin rights)
	 * @return string $authQuery Contains the PublicationId/CategoryId/StateId to which
	 * an user has access.
	 */
	private function composeAuthorization( $authorizations, $level, $handled )
	{
		$authQuery = '';
		$holdLevel = -1;
		$brands = array();
		if ($level == 'publication') {
			$searchField = 'PublicationId'; // Name of index field at Solr-side
		} else {
			$searchField = 'IssueIds';
		}

		$added = false; // To true if authorization is added
		if (count($authorizations) > 0) {
			$topQuery = '' ;
			$or = '';
			foreach ($authorizations as $authorization) {
				if ( isset( $handled[$authorization[$level]] ) ) {
					continue;
				}
				if (intval($authorization[$level]) > $holdLevel) {
					$added = true;
					if ( $level == 'publication' ) { // Needed to get all overrule brand issues of the involved brands.
						$brands[$authorization[$level]] = intval($authorization[$level]);
					}
					if ($authorization[$level] !== '0' && $authorization['section'] == '0' && $authorization['state'] == '0' ) {
						// All rights on top level, go to next brand/issue
						$topQuery .= $or . '(' . $searchField . ':"' . $authorization[$level] . '")';
						$holdLevel = intval($authorization[$level]);
					} elseif ( $authorization[$level] !== '0' && $authorization['section'] !== '0' && $authorization['state'] == '0' ) {
						$topQuery .= $or . '(CategoryId:"' . $authorization['section'] . '")';
					} elseif ( $authorization[$level] !== '0' && $authorization['section'] == '0' && $authorization['state'] !== '0' ) {
						$topQuery .= $or . '(StateId:"' . $authorization['state']. '")';
					} else {
						$topQuery .=  $or . '(StateId:"' . $authorization['state']. '" AND CategoryId:"' . $authorization['section'] . '")';
					}
					$or = ' OR ';
				}
			}
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			if ( strpos( $topQuery, 'OR') > 0 ) { // Group multiple authorizations by enclosing them with braces.
				$topQuery = '('.$topQuery.')';
			}
			if ( $level == 'publication' && DBIssue::listOverruleIssueIds($brands) ) {
				// If the non-overrule issues are not added explicitly then also the over-
				// rule brand issues of the brand pop up in the result set. BZ#33659
				$subQuery = $this->addBrandIssues( $brands );
				if ( $subQuery ) {
					$authQuery = '('.$topQuery.' AND '.$subQuery.')';
				} else {
					$authQuery = $topQuery;
				}
			} else {
				$authQuery = $topQuery;
			}
		} else { // User has no authorizations
			$authQuery = '(' . $searchField . ':"-1")'; // -1 is an invalid Publication/Issue Id. See BZ#28507.
		}

		return ($added ? $authQuery : '');
	}

	/**
	 * Returns a query with all non-overrule issues for specified brands.
	 *
	 * @param array $brands Filter on the brands of the non-overrule issues.
	 * @return string The query
	 */
	private function addBrandIssues( $brands )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$issues = array();
		$authQuery = '';

		if  ( $brands ) foreach ( $brands as $brandId ) {
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$issueBrand = DBIssue::listNonOverruleIssuesByBrand( $brandId );
			$issues = array_merge($issues, $issueBrand);
		}
		if ( $issues ) {
			$authQuery = ' IssueIds:(';
			$or = '';
			foreach( $issues as $issue ){
				$authQuery .= $or.'"'. $issue .'" ';
				$or = ' OR ';
			}
			// Empty issue in case object has no issue or issue(s) are not indexed.
			$authQuery .= ' OR "" )';
		}

		return $authQuery;
	}

	/**
	 * Returns a filter on brands to which objects must belong.
	 *
	 * @param array $authorizations Brands to which one is entitled.
	 * @param array $handled Brands added to the filter.
	 * @return string Authorization query
	 */
	private function composeAuthBrandAdmin( $authorizations, &$handled )
	{
		$authQuery = '';
		$searchField = 'PublicationId'; // Name of index field at Solr-side

		if ( count( $authorizations ) > 0 ) {
			$authQuery .= ' (';
			$or = '';
			foreach ( $authorizations as $authorization ) {
				$authQuery .= $or . '(' . $searchField . ':"' . $authorization['publication'] . '")';
				$handled[$authorization['publication']] = $authorization['publication'];
				$or = ' OR ';
			}
			$authQuery .= ')';
		}

		return $authQuery;
	}

	/**
	 * Build FacetItems for *IssueIds specifically.
	 *
	 * This is faster then to query a complete issue with custom properties for each issue id.
	 * See BZ#17057
	 *
	 * *BZ#1856:
	 * System admin can see ALL(active/inactive) issues, however a
	 * normal user can only see active issues being listed on the facet, the
	 * non-active issues will be hidden from normal user.
	 *
	 * @param array $facet
	 * @return array of FacetItem
	 */
	private function buildFacetItemsForIssueIds( $facet )
	{
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';

		$resultFacetItems = array();
		$issueIds = array();
		$user = BizSession::getShortUserName();
		$isAdmin = DBUser::isAdminUser( $user );

		foreach ($facet as $facetItemName => $facetItemNumber) {
			$issueId = intval($facetItemName);
			if ($issueId > 0){
				$issueIds[] = $issueId;
			}
		}

		// Get names of issue ids (do we want such a function in DBIssue?).
		if ( !empty( $issueIds ) ) {
			$where = 'id IN (' . implode(',', $issueIds) . ') ';
			$params = array();
			if( !$isAdmin ) {
				// BZ#1856:Hide objects from query results that are assigned to inactive issues
				$where .= 'AND `active` = ? ';
				$params[] = 'on';
			}
			$select = array( 'id', 'name' );
			$issueRows = DBBase::listRows('issues', 'id', '', $where, $select, $params );
		}

		foreach( $facet as $facetItemName => $facetItemNumber ) {
			if ( $facetItemName == '_empty_' ) {
				$facetItemName = '';
				$issueId = -1;
			} else {
				$issueId = intval($facetItemName);
			}
			if( isset($issueRows[$issueId]['name'])){
				$facetItemDisplayName = $issueRows[$issueId]['name'];
			} else {
				$facetItemDisplayName = '<'.BizResources::localize('OBJ_UNASSIGNED').'>';
			}
			$resultFacetItem = new FacetItem($facetItemName, $facetItemDisplayName, $facetItemNumber);
			$resultFacetItems[] = $resultFacetItem;
		}

		return $resultFacetItems;
	}

	/**
	 * Based on the search result facet info is composed
	 *
	 * @param Solarium\QueryType\Select\Result\FacetSet $facetsInfo  Contains the facet info from the search result
	 * @return void
	 */
	private function buildFacets($facetsInfo)
	{
		$this->buildFieldFacets( $facetsInfo );
		$this->buildNumericFacets( $facetsInfo );

		$this->sortFacets();
	}

	/**
	 * Returns facets in the same order as they are defined in config_solr.
	 *
	 * By moving up or down a facet field in the SOLR_..._FACETS array the customer
	 * can prioritize the facet.
	 */
	private function sortFacets()
	{
		$sortedFacets = array();
		foreach ( $this->facets as $facet ) {
			$facetName = $facet->Name;
			$order = array_search($facetName, $this->facetFields);
			$sortedFacets[$order] = $facet;
		}
		ksort($sortedFacets, SORT_NUMERIC);
		$this->facets = $sortedFacets;
	}

	/**
	 * Parse returned field facets (both regular and range/date).
	 *
	 * @param Solarium\QueryType\Select\Result\FacetSet $facetsInfo
	 */
	private function buildFieldFacets($facetsInfo)
	{
		foreach( $facetsInfo as $facetName => $facet ) {
			// Facet query, parsed separately in buildNumericFacets
			if( array_key_exists( $facetName, $this->facetQueries ) ) {
				continue;
			}

			$type = $this->getFieldType($facetName);
			$displayName = $this->getDisplayNameFacet($facetName);
			$resultFacet = new Facet($facetName, $displayName);
			$resultFacetItems = array();
			// BZ#17057 getting issue names for each issue id is slow, so do it an other way
			if ($facetName == 'IssueIds'){
				$resultFacetItems = $this->buildFacetItemsForIssueIds($facet);
			} else {
				if ( $type == "date" || $type == "datetime" ) {
					$facet = $this->convertDateFacets($facet);
				}
				foreach ($facet as $facetItemName => $facetItemNumber) {
					if( $facetItemName == '_empty_' ) $facetItemName = '';
					$facetItemDisplayName = $this->getDisplayNameFacetItem($facetName, $facetItemName);
					$resultFacetItem = new FacetItem($facetItemName, $facetItemDisplayName, $facetItemNumber);
					$resultFacetItems[] = $resultFacetItem;
				}
			}
			$resultFacet->FacetItems = $resultFacetItems;
			$this->facets[] = $resultFacet;
		}
	}

	/**
	 * Builds numeric facet fields from the result FacetInfo object.
	 *
	 * Numeric (integer) fields used as facets are represented as facet queries.
	 * Solr returns a field multiple times. Once for every facet query.
	 * E.g Rating:[0 To 2], Rating:[3 To 5]. Only facets with hits are returned.
	 * If a facet has only one range it will not be returned.
	 *
	 * @param Solarium\QueryType\Select\Result\FacetSet $facetsInfo
	 */
	private function buildNumericFacets($facetsInfo)
	{
		$holdName = null;
		$resultFacetItems = array();

		foreach( $facetsInfo as $facetName => $facetHits ) {
			// Facet field, parsed in buildFieldFacets
			if( !array_key_exists( $facetName, $this->facetQueries ) ) {
				continue;
			}

			$facetKey = $this->facetQueries[$facetName];
			$posSeperator = strpos($facetKey, ':'); //fieldname:range
			$currentName = substr($facetKey, 0, $posSeperator);

			if( $holdName != $currentName ) {
				if( $holdName != null && count($resultFacetItems) > 1 ) {
					$displayName = $this->getDisplayNameFacet($holdName);
					$resultFacet = new Facet($holdName, $displayName);
					$resultFacet->FacetItems = $resultFacetItems;
					$this->facets[] = $resultFacet;
				}
				$resultFacetItems = array();
				$holdName = $currentName;
			}

			$range = substr($facetKey, $posSeperator + 1);
			$resultFacetItems[] = new FacetItem($range, $range, $facetHits->getValue());
		}

		if( !empty( $resultFacetItems ) ) {
			//Add the last field to the facets
			$displayName = $this->getDisplayNameFacet($currentName);
			$resultFacet = new Facet($currentName, $displayName);
			$resultFacet->FacetItems = $resultFacetItems;
			$this->facets[] = $resultFacet;
		}
	}

	/**
	 * Returns the hits per defined date ranges.
	 *
	 * The facet items contains the hits per interval plus some other
	 * information like the gap/before etc. The day intervals have the format
	 * 2012-01-07T00:00:00Z. So we can check on the position of the 'T' and 'Z'.
	 *
	 * @param array $facetItems Date facets returned by Solr
	 * @return array with date ranges and the number of hits.
	 */
	private function convertDateFacets($facetItems)
	{
		//Last three entries contain [before], [end], [gap]
		$monthBorder = $this->getPeriodBorder('1 month');
		$weekBorder = $this->getPeriodBorder('1 week');
		$yesterdayBorder = $this->getPeriodBorder('1 day');
		$todayBorder = $this->getPeriodBorder('0 day');
		$yearTotal = 0;
		$monthTotal = 0;
		$weekTotal = 0;
		$yesterdayTotal = 0;
		$todayTotal = 0;

		foreach ($facetItems->getValues() as $date => $number) {
			if ((strpos($date,'T') === 10 && strpos($date,'Z') === 19) && ( $number > 0)) {
				$yearTotal = $yearTotal + $number;
				if ($date >= $monthBorder ) {
					$monthTotal = $monthTotal + $number;
				}
				if ($date >= $weekBorder) {
					$weekTotal = $weekTotal + $number;
				}
				if ($date == $yesterdayBorder) {
					$yesterdayTotal = $yesterdayTotal + $number;
				}
				if ($date == $todayBorder) {
					$todayTotal = $todayTotal + $number;
				}
			}
		}

		return array(
			self::TODAY          => $todayTotal,
			self::YESTERDAY      => $yesterdayTotal,
			self::THISWEEK       => $weekTotal,
			self::THISMONTH      => $monthTotal,
			self::THISYEAR       => $yearTotal,
			self::BEYONDTHISYEAR => $facetItems->getBefore());
	}

	/**
	 * Returns the beginning of a period in 'Y-m-d\TH:i:sZ' format
	 *
	 * @param string $period E.g. '1 day', '1 month'
	 * @return begin of period in'Y-m-d\TH:i:sZ' format
	 */
	private function getPeriodBorder($period)
	{
		$timestampPeriod = strtotime("now - $period");
		$timeParts = getdate($timestampPeriod);
		$timestampPeriod = mktime(0, 0, 0, $timeParts['mon'], $timeParts['mday'], $timeParts['year']);
		$dateString = date('Y-m-d\TH:i:s', $timestampPeriod) . 'Z';

		return $dateString;
	}

	/**
	 * Based on the search result object rows are composed. The search result
	 * contains the documents. If needed Keywords are merged into a string.
	 *
	 * @param array $objectIds object ids
	 * @param array $areas
	 * @return array of object rows
	 */
	private function buildRows( $objectIds, array $areas )
	{
		if (empty($objectIds)) {
			return array();
		}

		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		$sortedRows = self::validateSearchResultsAgainsDB( $objectIds, 'Solr', $areas );

		if (empty($sortedRows)) {
			// Could happen if objects are not cleaned up in DB but not in Solr
			return array();
		}

		$objectRows = self::queryObjectRows($sortedRows,
											$this->queryMode,
											$this->minimalProps,
											$this->requestProps,
											$this->hierarchical,
											$this->childRows,
											$this->resultComponentColumns,
											$this->resultComponents,
											$areas,
											$this->order);

		// Restore original sorting
		foreach ($objectRows as &$objectRow) {
			$id = $objectRow['ID'];
			$sortedRows[$id] = $objectRow;
		}

		return $sortedRows;
	}

	/**
	 * Formats a single field for indexing. Adds it to $fieldDefinition.
	 *
	 * @param array $fieldDefinition Formatted field definition for Solr
	 * @param string $fieldToIndex Name of field to be indexed.
	 * @param $indexValue the unformatted index value
	 */
	private function formatFieldForIndexing( &$fieldDefinition, $fieldToIndex, $indexValue )
	{
		switch( $fieldToIndex ) {
			case 'Rating':
				$indexValue = is_null($indexValue) ? 0 : $indexValue;
				$fieldDefinition[$fieldToIndex] = $indexValue;
				break;
			default:
				$indexValue = $this->convertToIndexFormat( $fieldToIndex, $indexValue );
				$fieldDefinition[$fieldToIndex] = $indexValue;
		}
	}

	/**
	 * Reads out the Object fields and formats them to be added to Solr.
	 *
	 * Fields defined as multiValued are passed as an array.
	 *
	 * @param Object $object    Enterprise objects to index
	 * @param array $areas      Area added to Solr (only if part of index fields)
	 * @param array $specialData Extra data, like PlacedOn, UnreadMessageCount that must also be indexed. 
	 * @return array Formatted field definition for Solr
	 */
	private function formatObjectForIndexing( $object, array $areas, $specialData )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$pubChannelIdsValues = array();
		$issueIdsValues = array();
		$issueNamesValues = array();
		$editionIdsValues = array();
		$editionNamesValues = array();
		$this->createPubChannelIssueEditionValues( $object, $pubChannelIdsValues, $issueIdsValues, $issueNamesValues, $editionIdsValues, $editionNamesValues );

		$fieldDefinition = array();
		foreach ( $this->fieldsToIndex as $fieldToIndex ) {
			if( !in_array('Trash', $areas) && BizProperty::isCustomPropertyName( $fieldToIndex ) ) { // Index custom properties
				// The rows from the database won't contain custom properties when in the Trash.
				$customData = $this->findMetaDataByField( $fieldToIndex, $object->MetaData->ExtraMetaData);
				$fieldDefinition[$fieldToIndex] = $this->createCustomValue( $customData );
			}
			else // Standard properties
			{
				switch( $fieldToIndex ) {
					// Target related properties
					case 'PubChannelIds':
						$fieldDefinition[$fieldToIndex] = $pubChannelIdsValues;
						break;
					case 'IssueIds':
						$fieldDefinition[$fieldToIndex] = $issueIdsValues;
						break;
					case 'EditionIds':
						$fieldDefinition[$fieldToIndex] = $editionIdsValues;
						break;
					case 'Issues':
						$fieldDefinition[$fieldToIndex] = $issueNamesValues;
						break;
					case 'Editions':
						$fieldDefinition[$fieldToIndex] = $editionNamesValues;
						break; // Handled by 'IssueIds'
					// Closed isn't a object property but we set _Closed in the calling function

					// Internal properties
					case 'Closed':
						if (property_exists($object, '_Closed')){
							$fieldDefinition[$fieldToIndex] = $object->_Closed;
						}
						break;
					case 'Areas':
						$fieldDefinition[$fieldToIndex] = $areas[0];
						break;
					default:
						if( array_key_exists( $fieldToIndex, $specialData ) ) { //Pages, unReadMessages
							$fieldDefinition[$fieldToIndex] = $specialData[$fieldToIndex];
						} else {
							$indexValue = $this->mapIndexFieldToProperty( $fieldToIndex, $object );
							$this->formatFieldForIndexing( $fieldDefinition, $fieldToIndex, $indexValue );
						}

				}
			}
		}

		return $fieldDefinition;
	}

	/**
	 * Formats an array of metaData values for Solr.
	 *
	 * Note: this does not support all metaData properties. In this
	 * case formatObjectForIndexing should be used, because the object
	 * information is required for some properties.
	 *
	 * @param array $metaDataValues
	 * @return array Formatted field definition for Solr
	 */
	private function formatMetaDataValuesForIndexing( array $metaDataValues )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';

		$fieldDefinition = array();

		// Build a list of fields and values to be indexed by Solr
		foreach( $metaDataValues as $metaValue ) {
			$fieldToIndex = $metaValue->Property;

			// Skip changed metaDataValues which don't need to be indexed
			if( !in_array($fieldToIndex, $this->fieldsToIndex) ) {
				continue;
			}

			if( BizProperty::isCustomPropertyName($fieldToIndex) ) { // Index custom properties
				$fieldDefinition[$fieldToIndex] = $this->createCustomValue( $metaValue );
			}
			else // Standard properties
			{
				$indexValue = $metaValue->PropertyValues[0]->Value;
				$this->formatFieldForIndexing( $fieldDefinition, $fieldToIndex, $indexValue );
			}
		}

		return $fieldDefinition;
	}

	/**
	 * Before a field is indexed it can be necessary to change the format of the value.
	 *
	 * E.g. to index a datetime field in Solr the format must be
	 * yyyy-mm-ddThh:mm:ssZ. The 'Z' is missing in Enterprise and must be added.
	 *
	 * @param string $fieldToIndex Name of the field (property).
	 * @param string $indexValue value to be indexed.
	 * @return converted value.
	 */
	private function convertToIndexFormat($fieldToIndex, $indexValue)
	{
		$type = $this->getFieldType($fieldToIndex);
		$convIndexValue = $indexValue;

		if ($type == 'date' || $type == 'datetime') {
			// On old Mssql installations an 'empty' string is stored as a space.
			$convIndexValue = trim( $convIndexValue ); 
			if (!empty($convIndexValue)) {
				$convIndexValue =$this->convertToDate($convIndexValue);
			} else {
				// Use NULL to indicate this field should not be added/cleared
				$convIndexValue = NULL;
			}
		}

		return $convIndexValue;
	}

	/**
	 * Converts the date string to Solr format
	 *
	 * @param string $date Date as stored within Enterprise
	 * @return string formatted date
	 */
	private function convertToDate($date)
	{
		$convDate = $date;
		$convDate .= 'Z';

		return $convDate;
	}

	/**
	 * Returns the type (date/datetime/string..) of an object property.
	 *
	 * Types are cached to improve performance.
	 *
	 * @param string $property
	 * @return string type of the property
	 */
	private function getFieldType($property)
	{
		static $fieldType = array();

		if (isset($fieldType[$property])) {
			$type = $fieldType[$property];
		} else {
			require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			if (DBProperty::isCustomPropertyName($property)) { // custom prop?
				$type = BizProperty::getCustomPropertyType($property);
			} else { // built-in prop
				$type = BizProperty::getStandardPropertyType($property);
			}
			$fieldType[$property] = $type;
		}

		return $type;
	}

	/**
	 * Composes search strings for date queries. Searches can be done on keywords like 'Today', 'Yesterday' etc.
	 * In case a specific date is passed e.g. 2014-12-23, the range is set to the begin of the day until the end of the
	 * day.
	 *
	 * @param string $searchValue keyword
	 * @return Solr query value
	 */
	private function addDateSearch($searchValue)
	{
		$searchDate = '[';

		switch ($searchValue) {
			case self::TODAY:
				$searchDate .= 'NOW/DAY TO NOW+1DAY';
			break;
			case self::YESTERDAY:
				$searchDate .= 'NOW/DAY-1DAY TO NOW/DAY';
			break;
			case self::THISWEEK:
				$searchDate .= 'NOW/DAY-7DAY TO NOW+1DAY';
			break;
			case self::THISMONTH:
				$searchDate .= 'NOW/DAY-1MONTH TO NOW+1DAY';
			break;
			case self::THISYEAR:
				$searchDate .= 'NOW/DAY-1YEAR TO NOW+1DAY';
			break;
			case self::BEYONDTHISYEAR:
				$searchDate .= '* TO NOW/DAY-1YEAR';
			break;
			default: // Specific date.
				$searchDate .= $searchValue.'T00:00:00Z'.' TO '.$searchValue.'T23:59:59Z';
			break;
		}

		$searchDate .= ']';

		return $searchDate;
	}

	/**
	 * Sets specific parameters to enable date faceting.
	 *
	 * @param Solarium/QueryType/Select/Query/Components/FacetSet $facetSet Query Facet Set being build
	 * @param string $facetField Facet field name
	 */
	private function addDateFacet($facetSet, $facetField)
	{
		$facet = $facetSet->createFacetRange($facetField);
		$facet->setField($facetField);
		$facet->setStart('NOW-1YEAR/DAY');
		$facet->SetEnd('NOW+1DAY/DAY');
		$facet->setGap('+1DAY');
		$facet->setOther('before');
	}

	/**
	 * Adds an Integer facet field as multiple facet queries.
	 *
	 * Integer facets are returned as a range like ... To ... Per integer facet
	 * lower en upper limits can be set in config_solr. These limits are used
	 * to generate the facet items. If no specific setting is found for a property
	 * the default range is used.
	 *
	 * @param Solarium/QueryType/Select/Query/Components/FacetSet $facetSet Query Facet Set being build
	 * @param string $facetField Facet field name
	 */
	private function addIntegerFacet($facetSet, $facetField)
	{
		$constant = 'SOLR_'.strtoupper($facetField).'_RANGE';
		$ranges = array();
		if ( defined( $constant ) ) {
			$ranges = unserialize(constant($constant));
		} else {
			$ranges = unserialize(SOLR_INTEGER_RANGE);
		}

		$from = '*';
		$i = 1;
		foreach ($ranges as $i => $range) {
			$key = "$facetField:$i";
			$fq = $facetSet->createFacetQuery($key)->setQuery("$facetField:[$from TO " . strval($range)."]");
			$this->facetQueries[$key] = $fq->GetQuery();
			$from = strval($range) + 1;
		}
		// Add last range 'greater than'
		$key = "$facetField:".count($ranges);
		$fq = $facetSet->createFacetQuery($key)->setQuery("$facetField:[$from TO *]");
		$this->facetQueries[$key] = $fq->GetQuery();
	}

	/**
	 * Returns the display name of a facet or facet item. Normally a facet item
	 * needs not to be translated but there are exceptions.
	 *
	 * @param string $property property to be translated
	 * @return string translation
	 */
	private function getDisplayNameFacet($property)
	{
		$displayName = '';

		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		switch ($property) {
			case 'ORIENTATION':
			case 'LANDSCAPE':
			case 'PORTRAIT':
			case 'SQUARE':
				$displayName = BizResources::localize(strtoupper($property));
				break;
			case 'IssueIds':
				$displayName = BizResources::localize('ISSUE');
				break;
			case 'EditionIds':
				$displayName = BizResources::localize('EDITION');
				break;
			default:
				$displayName = BizProperty::getPropertyDisplayName($property);
				break;
		}
		return $displayName;
	}

	/**
	 * Returns the display name for a given Facet Item (property value).
	 *
	 * Mostly, the display name is the same as the given item ($item).
	 * However, for some special properties, this is localized (such as object types and date values).
	 * But also, issue/edition ids are mapped to their real names.
	 * And, special cases, such as empty values, are localized to <Empty> or <Unassigned>.
	 *
	 * @param string $facet Facet name (property name)
	 * @param string $item Facet item (property value)
	 * @return string Localized name
	 */
	private function getDisplayNameFacetItem($facet, $item)
	{
		$displayName = '';

		switch ($facet) {
			case 'IssueIds':
				$id = intval($item);
				if( $id > 0 ) {
					require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
					$issue = DBAdmIssue::getIssueObj( $id );
					$displayName = $issue->Name;
				} else {
					$displayName = '<'.BizResources::localize('OBJ_UNASSIGNED').'>';
				}
				break;
			case 'EditionIds':
				$id = intval($item);
				if( $id > 0 ) {
					require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
					$edition = DBEdition::getEditionObj( $id );
					$displayName = $edition->Name;
				} else {
					$displayName = '<'.BizResources::localize('OBJ_UNASSIGNED').'>';
				}
				break;
			case 'Type': // object type
				static $objTypeMap = null;
				if( is_null($objTypeMap) ) {
					$objTypeMap = getObjectTypeMap();
				}
				$displayName = $objTypeMap[ $item ];
				break;
			default:
				$type = $this->getFieldType($facet);
				if ($type == 'date' || $type == 'datetime') {
					switch( $item ) {
						case self::TODAY:
							$displayName = BizResources::localize('TIME_TODAY');
							break;
						case self::YESTERDAY:
							$displayName = BizResources::localize('TIME_YESTERDAY');
							break;
						case self::THISWEEK:
							$displayName = BizResources::localize('TIME_THIS_WEEK');
							break;
						case self::THISMONTH:
							$displayName = BizResources::localize('TIME_THIS_MONTH');
							break;
						case self::THISYEAR:
							$displayName = BizResources::localize('TIME_THIS_YEAR');
							break;
						case self::BEYONDTHISYEAR:
							$displayName = BizResources::localize('TIME_BEYOND_THIS_YEAR');
							break;
						default:
							$displayName = $item;
							break;
					}
				} else {
					$displayName = $item;
					if( empty($displayName) ) {
						$displayName = '<'.BizResources::localize('EMPTY').'>';
					}
				}
				break;
		}

		return $displayName;
	}

	/**
	 * Convert an Enterprise parameter to a suitable solr parameter
	 *
	 * @param string $searchParam Enterprise search parameter property
	 * @return string converted parameter
	 */
	private function convertSearchProperty($searchParam)
	{
		$result = '';

		switch ($searchParam) {
			case 'IssueId':
				$result = 'IssueIds';
				break;
			case 'EditionId':
				$result = 'EditionIds';
				break;
			case 'PubChannelId':
				$result = 'PubChannelIds';
				break;
			default:
				$result = $searchParam;
		}

		return $result;
	}

	/**
	 * Converts an array to a sorted comma separated string.
	 *
	 * @param array $array
	 * @return string sorted string
	 */
	private function sortToCommaSeparatedString($array)
	{
		sort($array);
		$commaSeparatedString = implode(',', $array);

		return $commaSeparatedString;
	}

	/**
	 * Indexing of IssueIds is mandatory. While the issue ids are handled (of object as of relational targets)
	 * the names of the issues are also handled. The same is true for edition ids and names. If these are
	 * added to the Solr index is based on the settings of config_solr. They are handled all at once because
	 * of performance reasons.
	 *
	 * @since v9.0.0 PubChannelIds is also being indexed. This is needed to retrieve for PublishFormTemplates
	 *             which does not have issues.
	 * @param Object $object The input object of which we are collecting the pubChannels, issues and editions
	 * @param array $pubChannelIdsValues Result list of pubChannel ids
	 * @param array $issueIdsValues Result list of issue ids
	 * @param array $issueNamesValues Result list of issue names
	 * @param array $editionIdsValues Result list of edition ids
	 * @param array $editionNamesValues Result list of edition names
	 */
	private function createPubChannelIssueEditionValues($object, &$pubChannelIdsValues, &$issueIdsValues, &$issueNamesValues, &$editionIdsValues, &$editionNamesValues)
	{
		$issueIds = array();
		$issueNames = array();
		$pubChannelIds = array();
		$editionIds = array();
		$editionNames = array();

		if (isset($object->Targets)) {
			foreach ($object->Targets as $target) {
				if( !isset( $pubChannelIds[$target->PubChannel->Id])) { // check if already added.
					$pubChannelIdsValues[] = $target->PubChannel->Id;
					$pubChannelIds[$target->PubChannel->Id] = 1;
				}
				if (! isset($issueIds[$target->Issue->Id])) { //Check if already added.
					$issueIdsValues[] = $target->Issue->Id;
					$issueIds[$target->Issue->Id] = 1;
					$issueNames[$target->Issue->Id] = $target->Issue->Name;
				}
				if (isset($target->Editions)) {
					foreach ($target->Editions as $edition) {
						if (! isset($editionIds[$edition->Id])) {
							$editionIdsValues[] = $edition->Id;
							$editionIds[$edition->Id] = 1;
							$editionNames[$edition->Id] = $edition->Name;
						}
					}
				}
			}
		}
		// Add related issues
		if (isset($object->Relations)) {
			foreach ($object->Relations as $relation) {
				if ( $relation->Child == $object->MetaData->BasicMetaData->ID ) {
					// Only if the object is the child of the relation, the relational targets are scanned.
					// If the object is the parent only the object targets are taken into account (above).
					// The relational targets of a parent object are always a subset of its object targets.
					if ( is_array( $relation->Targets ) ) {
						foreach ( $relation->Targets as $target ) {
							if ( !isset($issueIds[$target->Issue->Id]) ) {
								$issueIdsValues[] = $target->Issue->Id;
								$issueIds[$target->Issue->Id] = 1;
								$issueNames[$target->Issue->Id] = $target->Issue->Name;
							}
							if ( isset($target->Editions) ) {
								foreach ( $target->Editions as $edition ) {
									if ( !isset($editionIds[$edition->Id]) ) {
										$editionIdsValues[] = $edition->Id;
										$editionIds[$edition->Id] = 1;
										$editionNames[$edition->Id] = $edition->Name;
									}
								}
							}
						}
					}
				}
			}
		}
		if( empty($pubChannelIds) ) { // Object has no pubChannelIds
			$pubChannelIdsValues[] = '' ; // Unassigned.
		}
		if (empty($issueIds)) { // Object has no issues
			$issueIdsValues[] = ''; // Unassigned
		}
		$issueNamesString = $this->sortToCommaSeparatedString($issueNames);
		$issueNamesValues[] = $issueNamesString;
		if (empty($editionIds)) { // Object has no editions
			$editionIdsValues[] = ''; // Unassigned
		}
		$editionNamesString = $this->sortToCommaSeparatedString($editionNames);
		$editionNamesValues[] = $editionNamesString;
	}

	/**
	 * Helper function to get the list of values from a metaDataValue.
	 *
	 * @param object $metaDataValue Metadata value structure of which the first value needs to be retrieved
	 * @return The metadata value
	 */
	private function getFirstMetaDataValue($metaDataValue)
	{
		if( !is_null($metaDataValue->Values) ) {
			return $metaDataValue->Values[0];
		} else {
			return $metaDataValue->PropertyValues[0]->Value;
		}
	}

	/**
	 * Finds metadata for a given field.
	 *
	 * @param string $fieldToIndex Name of the field
	 * @param object $metaData contains the extra meta data of the object
	 * @return metaData found metaData if any. Null if not found.
	 */
	private function findMetaDataByField( $fieldToIndex, $metaData )
	{
		foreach ($metaData as $customData) {
			if ($customData->Property == $fieldToIndex) {
				return $customData;
			}
		}
		return null;
	}

	/**
	 * Handles the indexing of custom fields.
	 *
	 * @param object $customData contains the metaData value
	 * @return array field value ready to be indexed.
	 */
	private function createCustomValue($customData)
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';

		$value = null;
		$fieldToIndex = $customData->Property;
		$customType = BizProperty::getCustomPropertyType($fieldToIndex);

		switch( $customType ) {
			case 'multistring':
			case 'multilist':
				$value = '';
				if( !is_null($customData->Values) ) {
					foreach( $customData->Values as $listValue ) {
						if( !empty( $value ) ) $value .= ',';
						$value .= $listValue;
					}
				} else {
					foreach( $customData->PropertyValues as $listValue ) {
						if( !empty( $value ) ) $value .= ',';
						$value .= $listValue->Value;
					}
				}
				break;
			case 'date':
			case 'datetime':
				$value = $this->getFirstMetaDataValue( $customData );
				// On old Mssql installations an 'empty' string is stored as a space.
				$value = trim( $value );
				if( !empty( $value ) ) { // BZ#34511 - Add to index only when value is not empty, to avoid Solr Invalid Date String error.
					$value = $this->convertToDate( $value );
				} else {
					$value = null; // Clears the field in Solr
				}
				break;
			case 'bool':
				$value = $this->getFirstMetaDataValue( $customData );
				empty($value) ? $value = '' : $value = 'true';
				break;
			default;
				$value = $this->getFirstMetaDataValue( $customData );
				break;
		}

		return $value;
	}

	/**
	 * Truncates the search term if longer than the maximum defined in config_solr.
	 *
	 * Take care of the maximum allowed search terms. If terms are used longer than
	 * the maximum defined in config_solr the term is truncated.
	 *
	 * NOTE: Solr expects UTF-8 encoded queries and will throw an error if not properly
	 * encoded. Functions like substr do not work correctly with multibyte strings.
	 * Instead we should use the "mb_" variants.
	 *
	 * @param $searchString String containing the search terms
	 * @return array Search terms
	 */
	private function handleSearchTerms($searchString)
	{
		$searchString = trim($searchString);
		if ($searchString[0] == '"' && $searchString[mb_strlen($searchString)-1] == '"') {
			return $searchString; //Phrase search
		}

		$resultTerms = array();
		if (defined('SOLR_NGRAM_SIZE')) {
			$NGramSizes = unserialize(SOLR_NGRAM_SIZE);
			$maxNGram = $NGramSizes[1];
		} else {
			$maxNGram = 15;
		}

		$searchTerms = explode(' ', $searchString);
		foreach ($searchTerms as $searchTerm) {
			if (!empty($searchTerm)) { //Skip spaces
				if (($searchTerm[0] == '+' || $searchTerm[0] == '-') && (mb_strlen($searchTerm) > ($maxNGram + 1))) {
					$resultTerms[] = mb_substr($searchTerm, 0, ($maxNGram + 1));
					// +/- have a special meaning.
				} elseif (mb_strlen($searchTerm) > $maxNGram) {
					$resultTerms[] = mb_substr($searchTerm, 0, $maxNGram);
					$resultTerms[] = $searchTerm;
				} else {
					$resultTerms[] = $searchTerm;
				}
			}
		}

		return implode(' ', $resultTerms);
	}

}