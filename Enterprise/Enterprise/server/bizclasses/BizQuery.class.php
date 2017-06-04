<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR . '/server/dbclasses/DBQuery.class.php';
require_once BASEDIR . '/server/bizclasses/BizQueryBase.class.php';
require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';

class BizQuery extends BizQueryBase
{
	private static $issueClauses = array();
	/**
	  * Determines the query order as preparation to {@link: queryObjects} function.
	  * @param string $orderBy  The property to sort on
	  * @param string $sortDir  'asc' or 'desc' or empty for none.
	  * @return QueryOrder[]|null List of one QueryOrder for sorting, or null for no sorting
	**/
	public static function getQueryOrder( $orderBy, $sortDir )
	{
		$queryOrder = null;
		if( !empty($orderBy) ) {
			if( !empty($sortDir) ) {
					$direction = ($sortDir == 'asc') ? true : false;
			} else {
					$direction = null;
			}
			$queryOrder = array( new QueryOrder( $orderBy, $direction ));
		}
		return $queryOrder;
	}

	/**
	 * @param string $ticket Ticket assigned to user in this session
	 * @param string $user Username of the current user
	 * @param array $params List of query-parameters in fact containing where-statements
	 * @param int $firstEntry Where to start fetching records
	 * @param int $maxEntries How many records to fetch
	 * @param bool $deletedobjects Whether or not also to search in the Trash Can
	 * @param string $forceapp Application name to use the properties defined for that application or null for generic,
	 *        determines the $mode if set.
	 * @param bool $hierarchical When true return the objects as a tree instead of in a list
	 * @param array $queryOrder The order in which the result set is returned.
	 * @param string[] $minimalProps Which properties needs to be returned for sure (independent of config)
	 * @param string[] $requestProps Complete list of props to return, overrules $requestProps as well as configured fields!
	 * @param string[] $areas 'Workflow' or 'Trash'
	 * @param int $accessRight Access right applicable for the current search:
	 *          0 = no check on access rights
	 *          1 = Listed in Search Results (View) right
	 *          2 = Read right
	 *          11 = List in Publication Overview
	 * @return WflQueryObjectsResponse|WflNamedQueryResponse
	 * @throws BizException
	 * @deprecated since 10.2.0 Please use queryObjects2 instead.
	 */
	static public function queryObjects( $ticket, $user, $params, $firstEntry = null, $maxEntries = null, $deletedobjects = false,
	                                     $forceapp = null, $hierarchical = false, $queryOrder = null, $minimalProps = null,
	                                     $requestProps = null, $areas = null, $accessRight = 1 )
	{
		if( $deletedobjects == true ) {
			if( !in_array( 'Trash', $areas ) ) {
				$areas[] = 'Trash';
			}
		}
		if( $forceapp !== null ) {
			LogHandler::Log( 'BizQuery', 'WARN', __METHOD__.': Parameter $forceapp is no longer supported.' );
		}
	   LogHandler::Log( 'BizQuery', 'WARN', __METHOD__.' is deprecated. Use BizQuery::queryObjects2() instead of it.' );
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $ticket;
		$request->Params = $params;
		$request->FirstEntry = $firstEntry;
		$request->MaxEntries = $maxEntries;
		$request->Hierarchical = $hierarchical;
		$request->Order = $queryOrder;
		$request->MinimalProps = $minimalProps;
		$request->RequestProps = $requestProps;
		$request->Areas = $areas;

		return self::queryObjects2( $request, $user, $accessRight );
	}

	/**
	 * Queries either the search integration or the database.
	 *
	 * The request ($request) contains the filter parameters for the query. Also the number of results and where to start
	 * are part of the request. Furthermore the area (Trash/Workflow) and the needed properties of the objects are all
	 * handled by the request. Version 7 client does not understand $request->Areas, therefore $request->Areas = null
	 * is indicating 'Workflow' area.
	 * The (short) user name ($user) is needed to check access rights.
	 * The search engine (Solr) is only capable of checking the 'Read' right (1). In case this is not the appropriate
	 * the $accessRight can be set to a different value than 1. In that case the search is directly done on the database.
	 *
	 * @param WflQueryObjectsRequest $request
	 * @param string $user Username of the current user
	 * @param int $accessRight Access right applicable for the current search:
	 *          0 = no check on access rights.
	 *          1 = Listed in Search Results (View) right.
	 *          2 = Read right.
	 *          11 = List in Publication Overview.
	 * @return WflQueryObjectsResponse|WflNamedQueryResponse
	 * @throws BizException
	 * @since 10.2.0
	 */
	static public function queryObjects2( WflQueryObjectsRequest $request, $user, $accessRight = 1 )
	{
		if( is_null( $request->Areas ) ) {
			$request->Areas = array( 'Workflow' );
		}
		$mode = self::determineMode( $request );
		self::determineStartSequence( $request );
		self::determineMaximumNumberOfResults( $request );
		if( isset( $request->Params ) ) {
			$request->Params = self::resolvePublicationNameParams( $request->Params );
			$request->Params = self::resolveSpecialParams( $request->Params );
		}

		$queryResult = null;
		$searchEngineSuccess = false;
		if( $accessRight == 1 ) {
			$searchEngineSuccess = self::executeSearchEngineQuery( '_QueryObjects_', $request, $mode, $queryResult );
		}
		if ( !$searchEngineSuccess || $accessRight !== 1 ) {
			$queryResult = self::runDatabaseUserQuery(
				$user,
				$request->Params,
				$request->FirstEntry,
				$request->MaxEntries,
				$request->Order,
				$mode,
				$request->MinimalProps,
				$request->RequestProps,
				$request->Areas,
				$accessRight,
				$request->Hierarchical );
		}
		if( self::resolveFacetsForDossier( $request ) && $queryResult->Rows ) {
			$request->Params = self::getParametersForDossierItemsFacets( $queryResult );
			/*$success =*/ self::executeSearchEngineQuery( '_FacetsOnly_', $request, $mode, $queryResult->Facets );
		}

		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( $user, 'QueryObjects' );
		return $queryResult;
	}

	/**
	 * Determines the query mode of the request.
	 *
	 * The columns returned in the result set depend on the client doing the request. Per client (InDesign, InCopy, etc)
	 * different sets of columns can be defined. The application is resolved from the ticket. But a request can contain
	 * a parameter 'View' that overrules this.
	 *
	 * @param WflQueryObjectsRequest $request
	 * @return string mode
	 */
	static private function determineMode( $request )
	{
		$forceMode = null;
		if ( $request->Params) foreach( $request->Params as $paramKey => $param ) {
			if( $param->Property == 'View' ) {
				$forceMode = $param->Value;
				unset( $request->Params[ $paramKey ] ); // Remove from the Params to prevent query errors.
			}
		}

		return self::getQueryMode( $request->Ticket, $forceMode );
	}

	/**
	 * Parses the query to the  search engines to let it executed by the engine after it engine has checked if it is
	 * able to do the query.
	 * Note that the method can throw an exception and at the same time has a return value 'success'. The reason is that
	 * if Solr is not available the caller must be able to continue. In that case an error is logged and the return
	 * value is false. Other errors are just rethrown.
	 *
	 * @param string $queryName
	 * @param WflQueryObjectsRequest $request
	 * @param string $mode Query mode (influences the columns returned)
	 * @param $queryResult
	 * @return bool Search engine was able to do the query.
	 * @throws BizException
	 */
	static private function executeSearchEngineQuery( $queryName, $request, $mode, &$queryResult )
	{
		$success = false;
		require_once BASEDIR.'/server/bizclasses/BizSearch.class.php';
		if( BizSearch::handleSearch(
			$queryName,
			$request->Params,
			$request->FirstEntry,
			$request->MaxEntries,
			$mode,
			$request->Hierarchical,
			$request->Order,
			$request->MinimalProps,
			$request->RequestProps )
		) {
			try {
				$queryResult = BizSearch::search(
					$queryName,
					$request->Params,
					$request->FirstEntry,
					$request->MaxEntries,
					$mode,
					$request->Hierarchical,
					$request->Order,
					$request->MinimalProps,
					$request->RequestProps,
					$request->Areas );
				$success = true;
			} catch( BizException $e ) {
				if( $e->getMessageKey() == 'ERR_SOLR_SEARCH' ) {
					LogHandler::Log( 'Solr', 'ERROR', $e->getMessage() );
				} else {
					throw $e;
				}
			}
		}

		return $success;
	}

	/**
	 * Returns extra clause for selecting objects in DBQuery::createAuthorizedObjectsView
	 * @see DBQuery::createAuthorizedObjectsView
	 * 
	 * @param array $params array with QueryParam objects
	 * @return string where clause
	 */
	static public function getObjectsWhere($params)
	{
		static $objectQueryProperties = array(
			'Type' => true,
			'Name' => true,
			'ID' => true,
		);
		// only select object query params
		$objectQueryParams = array();
		if (isset($params)){
			foreach ($params as $param){
				if (isset($objectQueryProperties[$param->Property])){
					$objectQueryParams[] = $param;
				}
			}
		}
		$where = self::buildWhere($objectQueryParams);
		// remove WHERE
		return preg_replace('/^WHERE */', '', $where);
	}

	/**
	 * Determines the start sequence of the result set for an QueryObjects request.
	 *
	 * As Solr and the databases are zero based internally the start position is the requested start position minus 1.
	 * If the start position is omitted it is set to the first one which is 0 (see above).
	 *
	 * @param WflQueryObjectsRequest $request
	 */
	static private function determineStartSequence( WflQueryObjectsRequest $request )
	{
		if( empty( $request->FirstEntry ) ) {
			$request->FirstEntry = 0;
		}
		if( $request->FirstEntry > 0 ) {
			$request->FirstEntry--;
		}
	}

	/**
	 * Determines the maximum number of results for an QueryObjects request.
	 *
	 * According to the WSDL: <!-- v4.2 Max count of requested objects (zero for all, nil for server defined default) -->
	 * So if null is passed the number of results is set to DBMAXQUERY. If an empty value is passed in the maximum
	 * number of results is set to 0. This meaning that there is no upper limit and all objects are returned.
	 *
	 * @param WflQueryObjectsRequest $request
	 */
	static private function determineMaximumNumberOfResults( WflQueryObjectsRequest $request )
	{
		// From WSDL MaxEntries: <!-- v4.2 Max count of requested objects (zero for all, nil for server defined default) -->
		if( is_null( $request->MaxEntries ) ) {
			$request->MaxEntries = DBMAXQUERY;
		}
		if( empty( $request->MaxEntries ) ) {
			$request->MaxEntries = 0;
		}
	}

	/**
	 * Must facets be resolved for the items in a dossier.
	 *
	 * If an dossier is opened and the query is about getting all the items in dossier, facets can be returned to reflect
	 * the items stored in that dossier. This can be turned on by a server feature.
	 *
	 * @param WflQueryObjectsRequest $request
	 * @return bool True, show the facets for the items in a dossier, else False.
	 */
	static private function resolveFacetsForDossier( $request )
	{
		$dossierFacets = false;
		if( $request->Params ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			foreach( $request->Params as $paramKey => $param ) {
				if( BizSettings::isFeatureEnabled( 'FacetsInDossier' ) && ( $param->Property == 'ParentId' ) &&
					( DBObject::getObjectType( $param->Value ) == 'Dossier' ) ) {
					$dossierFacets = true;
				}
			}
		}

		return $dossierFacets;
	}

	/**
	 * This method handles the (hierarchical) custom queries and browse queries. Based on the passed parameters sql statements are
	 * generated for the select/join/where/order by clauses. Missing tables are added, columns not suitable for ordering
	 * are handled etc. After the rows are selected they are enriched by adding targets, placed on information etc.
	 * Also the children belonging to the rows on top level are resolved. These child rows are also enriched with extra
	 * information. The enriching process (processResultRows) is limited to objects that are not placed many, many times.
	 * Examples of many placed objects are images used as icon on each layout or an article with some default text used in
	 * each issue. The reason is that the resolving of the issues, editions etc takes a lot of time without adding any
	 * useful information. To resolve the user authorizations temporary tables are used with the ids of objects the user
	 * is entitled to.
	 * Entitled to can mean having View rights or having Read rights. Normally the View right is checked but it is also
	 * possible to run the query as if it is a GetObjects call (View right). In some cases no check at all on access rights
	 * is needed (e.g. for technical internal server queries).
	 * possible to run the query as if it is a GetObjects call.
	 *
	 * @param string $shortusername User who is running the query. Needed to resolve the authorization.
	 * @param array $params Contains QueryParam objects to build the where statement.
	 * @param int $firstEntry Specifies the offset of the first row to return (used for paging).
	 * @param int $maxEntries Specifies the maximum number of rows to return (on top level).
	 * @param array $queryOrder Contains QueryOrder objects to specify the column(s) and direction of the sorting.
	 * @param string $mode Specifies how the query was initiated (InDesign, Content Station etc).
	 * @param array $minimalProps The minimal set of properties needed by the client.
	 * @param array $requestProps Other properties requested
	 * @param array $areas 'Workflow' or 'Trash'
	 * @param int $accessRight - access right applicable for the current search:
	 *                         0 = no check on access rights
	 *                         1 = Listed in Search Results (View) right
	 *                         2 = Read right
	 *                         11 = List in Publication Overview
	 * @param bool $hierarchical Add child objects to the top level objects
	 * @return WflQueryObjectsResponse Response containing all requested information (rows)
	 * @throws BizException
	 * @internal param bool $deletedObjects When true search the deletedobjects-table in stead of objects
	 * @see BizQuery::queryObjectRows
	 */
	static private function runDatabaseUserQuery(
		$shortusername, $params, $firstEntry, $maxEntries, $queryOrder, $mode, $minimalProps, $requestProps, $areas, $accessRight, $hierarchical )
	{
		// Prepare the sql
		$queryOrder = self::resolveQueryOrder( $queryOrder, $areas );
		$requestedPropNames = self::getPropNames( $mode, $minimalProps, $requestProps, $areas );
		if( is_array($areas) && in_array('Trash',$areas) ){
			$deletedObjects = true;
		} else {
			$deletedObjects = false;
		}
		$sqlStruct = self::buildSQLArray( $requestedPropNames, $params, $queryOrder, $deletedObjects );
		if ( empty( $sqlStruct ) )  {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Invalid sql in query');
		}

		try {
			//create a view containing all object-id's for which $user is authorized
			$objectsWhere = self::getObjectsWhere( $params );
			if($accessRight > 0 ) {
				DBQuery::createAuthorizedObjectsView(
					$shortusername, $deletedObjects, $params, false, true, $objectsWhere, $accessRight );
			}
			$topCount = 0;
			$topView = DBQuery::createTopView( $sqlStruct, $topCount, $firstEntry, $maxEntries, $accessRight > 0 );
			// Top level objects with a limit number of placements.
			$limitPlacedTopView = DBQuery::createLimitTopView( $topView );
			//Gets the top level objects by joining with the topView and using the select from the query
			$topRows = DBQuery::getTopObjects( $sqlStruct, false );
			$componentRows = array();
			self::enrichRows( $topRows, $componentRows, $topView, $limitPlacedTopView, $requestedPropNames );

			$allChildRows = array();
			if ( $hierarchical ) {
				$childComponentRows = array();
				$allChildRows = self::addChildren(
					$childComponentRows, $sqlStruct, $topView, $deletedObjects, $accessRight > 0, $shortusername,
					$requestedPropNames, $params, $queryOrder );
				$componentRows = self::mergeComponentRows( $childComponentRows, $componentRows );
			}
			DBQuery::dropRegisteredViews();
		}
		catch (BizException $e)
		{
			//Drop the created views (essential to not get a lot of views in the database!)
			DBQuery::dropRegisteredViews();
			throw($e);
		}

		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsResponse.class.php';
		if ( count( $topRows ) ) {
			$queryresponse = new WflQueryObjectsResponse(
				self::getColumns($topRows),
				self::getRows($topRows),
				$hierarchical ? self::getChildColumns($topRows) : null ,
				$hierarchical ? self::getChildRows($allChildRows) : null,
				self::getComponentColumns( $componentRows ),
				self::getComponents( $componentRows ),
				$firstEntry + 1,
				count( $topRows ),
				$topCount,
				null,
				null,
				null);
		}
		else {
			$queryresponse = new WflQueryObjectsResponse(
				self::getColumns( array( array_flip( $requestedPropNames ) ) ),
				array(),
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null);
		}
		return $queryresponse;
	}

	/**
	 * Enriches rows with target data and placement data. Also element components data is added.
	 *
	 * @param array $rows the database objects
	 * @param string $allView identifier of the temporary table containing all object ids.
	 * @param string $limitPlacedView identifier for subselect on temporary table with limited placed objects.
	 * @param array $requestedPropNames properties requested
	 * @param array $componentRows element information
	 */
	static private function enrichRows( &$rows, &$componentRows, $allView, $limitPlacedView, $requestedPropNames )
	{
		$tempComponentRows = array();
		$rows = self::processResultRows(
			$rows, $requestedPropNames, $limitPlacedView, $allView, $tempComponentRows );
		// Filter out duplicate $tempComponentRows
		foreach ( $tempComponentRows as $key => $value ) {
			$componentRows[$key] = $value;
		}
		if ( count( $rows ) ) {
			$rows = self::reorderColumns( $rows, $requestedPropNames );
		}
	}

	/**
	 * This method adds the child objects to the top level objects of a user query.
	 * These child rows are also enriched with extra information.
	 * The enriching process (processResultRows) is limited to objects that are not placed many, many times.
	 * Examples of many placed objects are images used as icon on each layout or an article with some default text used in
	 * each issue. The reason is that the resolving of the issues, editions etc takes a lot of time without adding any
	 * useful information.
	 * To resolve the user authorizations temporary tables are used with the ids of objects the user is entitled to.
	 * Entitled to means having View rights or having Read rights. Normally the View right is checked but it is also
	 * possible to run the query as if it is a GetObjects call (View right). In some cases no check on access rights
	 * is needed (e.g. for technical internal server queries).
	 *
	 * This method is closely related to runDatabaseUserQuery()
	 *
	 * @param array $componentRows Array key/value set to object id/element information.
	 * @param array $sqlStruct Array with the differen sql-statements of the user query.
	 * @param string $topView view containing the object ids of the top level objects.
	 * @param bool $deletedObjects When true search the deletedobjects-table in stead of objects
	 * @param integer $accessRight - access right applicable for the current search:
	 * @param string $shortUserName 	User who is running the query. Needed to resolve the authorization.
	 * @param array $requestedPropNames	The names of properties requested by the client.
	 * @param array	$params 		Contains QueryParam objects to build the where statement.
	 * @param array $queryOrder		Contains QueryOrder objects to specify the column(s) and direction of the sorting.
	 * @param $accessRight int - access right applicable for the current search:
	 * 			0 = no check on access rights
	 * 			1 = Listed in Search Results (View) right
	 * 			2 = Read right
	 * 			11 = List in Publication Overview
	 * @return array Child rows
	 * @throws BizException
	 * @throws Exception
	 */
	static private function addChildren(
		&$componentRows, $sqlStruct, $topView, $deletedObjects, $accessRight, $shortUserName, $requestedPropNames,
		$params, $queryOrder )
	{
		// Get all (nested) children of the parents.
		$allChildren = DBQuery::getAllChildren( $topView, $deletedObjects );

		if( $accessRight ) {
			self::addObjectsToAuthorizedView( $shortUserName, $allChildren );
		}
		//creates a view containing all object-id's of children of the top level objects for which $user is authorized.
		$authorizedChildrenView = DBQuery::createAllChildrenView( $allChildren, $accessRight );
		// Get all the children to be able to do additional sorting.
		$authorizedChildrenIds = DBQuery::getIdsByView( $authorizedChildrenView );
		// Children with a limit number of placements.
		$limitPlacedChildrenView = DBQuery::createLimitPlacedChildrenView( $authorizedChildrenIds );
		// Children with a lot of placements (in fact special objects uses as e.g. logo's).
		$multiPlacedChildrenView = DBQuery::createMultiPlacedChildrenView( $authorizedChildrenView, $limitPlacedChildrenView );
		//Gets the topchildren-objects by joining with the topchildrenview and using the select from the namedquery
		$allChildRows = DBQuery::getAllChildrenObjects($sqlStruct);
		// Parents of children with a limit number of placements are handled different from
		// children with a lot of placements.
		$parents = DBQuery::getParentsByView( $limitPlacedChildrenView );
		$parents2 = DBQuery::getParentsOfMultiPlacedChildren( $multiPlacedChildrenView, $topView );
		foreach ( $parents2 as $key => $value ) {
			$parents[$key] = $value;
		}

		foreach ( $allChildRows as &$childrow ) {
			if ( isset($parents[$childrow['ID']] ) ) {
				$childrow['smart_parents'] = $parents[$childrow['ID']];
			}
		}

		// Only process children with a limit number of placements.
		$allChildRows = self::processResultRows(
			$allChildRows,
			$requestedPropNames,
			$limitPlacedChildrenView,
			$authorizedChildrenView,
			$componentRows );

		if ( count( $allChildRows ) ) {
			// authorizedChildrenIds contains the children without authorization checking, allChildRows after authorization checking.
			$allChildRows = self::reorderChildren(
								$authorizedChildrenIds, $queryOrder, $requestedPropNames, $params, $allChildRows, $deletedObjects );
			$allChildRows = self::reorderColumns( $allChildRows, $requestedPropNames );
		}

		return $allChildRows;
	}

	/**
	 * Merges two sets of component rows by adding the $fromComponents to the $toComponents
	 * if and only if when the from key is not present.
	 *
	 * @param array $fromComponents
	 * @param array $toComponents
	 * @return array merged components
	 */
	static private function mergeComponentRows( $fromComponents, $toComponents )
	{
		if ( $fromComponents ) foreach ( $fromComponents as $key => $value ) {
			if ( !array_key_exists( $key, $toComponents ) ) {
				$toComponents[$key] = $value;
			}
		}

		return $toComponents;
	}


    /**
	 * Reorders and replaces the children in the childRows section.
     *
	 * @param int[] $sortedIds List of children ids.
	 * @param array $queryOrder The list containing the sorting options.
	 * @param array $requestedPropNames List of request property names.
	 * @param array $params List of request parameters
	 * @param array $allchildrows List of original record set
     * @param bool $deletedObjects Whether the objects reside in workflow(false) or trash(true).
	 * @return array The re-ordered result set.
     */
	static function reorderChildren($sortedIds, $queryOrder, $requestedPropNames, $params, $allchildrows, $deletedObjects)
	{
		if (count($sortedIds) == 0){
			return $allchildrows;
		}

		// Build the WHERE clause of the criteria to sort the items properly.
		$inChildren = 'WHERE o.`id` IN (' . implode(',',$sortedIds) . ')';
		$sqlComponentArray = self::buildSQLArray( $requestedPropNames, $params, $queryOrder, $deletedObjects, $inChildren);
		$rows = DBQuery::getRowsBySqlArray($sqlComponentArray);
			
		// Sort the array values.
		$newOrder = array();
		if ( $rows) foreach ($rows as $row){
			$id = $row['id'];
			$newOrder[$id] = $allchildrows[$id];
		}
		
		return $newOrder;
	}

    
    static private function buildSQLArray($requestedpropnames, $params, $queryorder, $deletedobjects = false, $where=null)
    {
        $dbdriver = DBDriverFactory::gen();
		$objectstable = $deletedobjects ? $dbdriver->tablename('deletedobjects') : $dbdriver->tablename('objects');
		
        $sqlarray = array();
        $sqlarray['from'] = "/*FROM*/ " . "FROM $objectstable o ";
        $sqlarray['select'] = "/*SELECT*/ " . self::buildSelect($requestedpropnames);
	    $sqlarray['where'] = (is_null($where))
	        ? "/*WHERE*/ " . self::buildWhere($params)
	        : "/*WHERE*/ " . $where;
	    //As order by needs joins, first create order by, last create the joins...
    	$sqlarray['orderby'] = "/*ORDERBY*/ " . self::queryorder2SQL($queryorder, "");
        $sqlarray['joins'] = "/*JOINS*/ " . self::buildJoins();
        $sqlarray['joins4where'] = "/*JOINS4WHERE*/ " . self::buildJoins4Where();
        return $sqlarray;
    }

	static protected function getPropNames($mode, $minimalProps, $requestProps, $areas)
	{
		// If $requestProps set, this is the complete list of properties, so if no empty return this
		if( !empty( $requestProps ) ) {
			return $requestProps;
		}

		//All these props require each other, let us make sure they are always both selected when the other is selected.
      $reqprops = array();
		$reqprops['Publication'] = 'PublicationId';
		$reqprops['PublicationId'] = 'Publication';
		$reqprops['Category'] 	 = 'CategoryId';
		$reqprops['CategoryId']  = 'Category';
		$reqprops['Section'] 	 = 'SectionId';
		$reqprops['SectionId'] 	 = 'Section';
		$reqprops['Issue'] 	 	 = 'IssueId';
		$reqprops['IssueId'] 	 = 'Issue';
		$reqprops['State']	 	 = 'StateId';
		$reqprops['StateId'] 	 = 'State';

		$propnames = self::getQueryProperties($mode, $areas);

		$reqpropnames = array();
		foreach ($propnames as $propname) {
			$reqpropnames[] = $propname;
			if (isset($reqprops[$propname])) {
				$reqpropnames[] = $reqprops[$propname];
			}
		}

		// Merge required props into configured list:
		if( !empty($minimalProps) ) {
			$reqpropnames = array_merge( $reqpropnames, $minimalProps );
		}

		return array_unique($reqpropnames);
	}

    static private function buildSelect( $propertyNames )
    {
		// Get the complete map of Property <-> smart_objects
        require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
        $objFields = BizProperty::getMetaDataObjFields();
		$joinProps = BizProperty::getJoinProps();
		$jfldProps = BizProperty::getJFldProps();

		// Walk through the requested properties
        $selects = array();
    
	    foreach( $propertyNames as $propertyName ) {

			// Debug: Fail when property is unknown (but respect custom props)
			if( LogHandler::debugMode() ) {
				if( !array_key_exists( $propertyName, $objFields ) && stripos( $propertyName, 'c_' ) !== 0 ) {
					throw new BizException( '', 'Server', '', __METHOD__.' - Querying for unknown property: '.$propertyName );
				}
			}

			// Determine SQL SELECT snippets and required tables to JOIN with
            switch( $propertyName )
            {
				case 'Version':
					{
			        $dbdriver = DBDriverFactory::gen();
                    $selects['Version'] = $dbdriver->concatFields(array("o.`majorversion`", "'.'", "o.`minorversion`")) . " as \"Version\" ";
                    break;
                }

				case 'RouteTo':
					{
					$selects['RouteTo'] = "o.`routeto` as \"RouteTo\" ";
					$selects['RouteToUser'] = "rtu.`fullname` as \"RouteToUser\" ";
					BizQueryBase::requireJoin('rtu');
					$selects['RouteToGroup'] = "rtg.`name` as \"RouteToGroup\" ";
					BizQueryBase::requireJoin('rtg');
					break;
				}
	         case 'Dimensions':
				case 'HasChildren':
				case 'PlacedOn':
				case 'PlacedOnPage':
					// Dimensions, HasChildren, PlacedOn and PlacedOnPage will be resolved later
					break;

				default:
					{ // props that need no special treatments and custom props, most of them now.

					if ( DBProperty::isCustomPropertyName( $propertyName ) ) { // custom prop?
	                    $objField = $propertyName;
	                    $selects[$propertyName] = "o.`$objField` as \"$propertyName\"";
						} else { // built-in prop:
	                    $objField = $objFields[$propertyName];
						$alias = $joinProps[$propertyName];
						$joinfieldname = $jfldProps[$propertyName];
						
						if (empty($joinfieldname)) { // no join found for this property, so try to find by objField
							if (!empty($objField)) { // use objField
								$selects[$propertyName] = "o.`$objField` as \"$propertyName\"";
								} else {
								;	// do nothing, as this prop has no field in the objects-table AND has no join.
									// It will probably be calculated after having executed the query
							}
							} else { // join found: use alias and joinfieldname to select. Also require the join.
							$selects[$propertyName] = "$alias.`$joinfieldname` as \"$propertyName\"";
							BizQueryBase::requireJoin($alias);
						}
                    }
                }
            }                
        }
        
        // Build the SQL select statement (out of collected snippets)
        $sql = ' SELECT ';
        $comma = ' ';
        foreach ($selects as $select) {
            $sql .= $comma . $select;
            $comma = ', ';
        }
        return $sql;
    }

	static private function requireWhere( $wherestring = null )
	{
		static $requiredwheres;
		if( !isset( $requiredwheres ) ) {
			$requiredwheres = array();
		}
		if( !empty( $wherestring ) ) {
			$requiredwheres[] = $wherestring;
		}
		return $requiredwheres;
	}

	/**
	 * Builds a sql where-clause based on the query parameters.
	 *
	 * @param QueryParam[] $params
	 * @return string
	 */
	static public function buildWhere( $params )
	{
		$paramsPerProperty = array();
		$wheresPerProperty = array();
		if( $params ) foreach( $params as $param ) {
			$whereSql = self::buildWhereParam( $param );
			if( $whereSql ) {
				$paramsPerProperty[$param->Property][] = $param;
				$wheresPerProperty[$param->Property][] = $whereSql;
			}
		}
		$wheres = array();
		foreach( $wheresPerProperty as $property => $propertyWheres ) {
			$operator = self::isAndOperatorNeededForQueryParams( $paramsPerProperty[$property] ) ? 'AND' : 'OR';
			$wheres[] = '('.implode( ") $operator (", $propertyWheres ).')';
		}
		return $wheres ? 'WHERE ('.implode( ') AND (', $wheres ).')' : 'WHERE (1 = 1)';
	}

	/**
	 * Checks if the query parameters for the same property must 'glued' by an 'AND'.
	 * Check is done on the '!=' (is not) usage and the 'in between' usage.
	 *
	 * @param array $propertyQueryParams array with query params for a single property.
	 * @return bool True if the 'AND' is applicable.
	 */
	static private function isAndOperatorNeededForQueryParams( array $propertyQueryParams )
	{
		if( self::isNotOperatorDefinedByQueryParams( $propertyQueryParams ) ) {
			return true;
		}

		if( self::isSingleRangeDefinedByQueryParams( $propertyQueryParams ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if a query param for a property contains the '!=' operation.
	 *
	 * @param array $propertyQueryParams array with query params for a single property.
	 * @return bool The '!=' is used.
	 */
	static private function isNotOperatorDefinedByQueryParams( array $propertyQueryParams )
	{
		$result = false;
		if( $propertyQueryParams ) foreach( $propertyQueryParams as $propertyQueryParam ) {
			if( $propertyQueryParam->Operation == '!=' ) {
				$result = true;
				break;
			}
		}

		return $result;
	}

	/**
	 * If query params for the same property contain the 'less than' and 'greater than' ('<', '>') it depends on the
	 * values if a 'between' is meant. E.g.:
	 * LengthLines < 100, LengthLines > 200 => LengthLines < 100 OR LengthLines > 200
	 * LengthLines > 100, LengthLines < 200 => LengthLines > 100 AND LengthLines < 200. (between)
	 * Only if  both the '<' and the '>' are used and no other params the above logic is use. E.g. the following
	 * LengthLines > 100, LengthLines < 200, LengthLines > 300, LengthLines < 400 will not be resolved.
	 *
	 * @param array $propertyQueryParams Structure with information for each property to build the where statement.
	 * @return bool
	 */
	static private function isSingleRangeDefinedByQueryParams( $propertyQueryParams )
	{
		$result = false;
		if( count( $propertyQueryParams ) == 2 ) {
			$lessThanValue = null;
			$greaterThanValue = null;
			if( $propertyQueryParams ) foreach( $propertyQueryParams as $propertyQueryParam ) {
				if( $propertyQueryParam->Operation == '<' ) {
					$lessThanValue = $propertyQueryParam->Value;
				}
				if( $propertyQueryParam->Operation == '>' ) {
					$greaterThanValue = $propertyQueryParam->Value;
				}
			}

			if( !is_null( $lessThanValue ) && !is_null( $greaterThanValue )  ) {
				if ( is_numeric( $lessThanValue ) && is_numeric( $greaterThanValue ) ) {
					if ( $lessThanValue > $greaterThanValue ) {
						$result = true;
					}
				} else {
					if ( strcmp( $lessThanValue, $greaterThanValue ) > 0 ) {
						$result = true;
					}
				}
			}
		}

		return $result;
	}

	static private function buildWhereParam( $param )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$propName = $param->Property;

		// Debug: Fail when property is unknown (but respect custom props)
		/*
		if( LogHandler::debugMode() ) {
			if( !isset($objFields[$propName]) && stripos( 'c_', $propName ) !== 0 ) {
				throw new BizException( '', 'Server', '', __METHOD__.' - Querying for unknown property: '.$propName );
			}
		}
		*/

		$dbdriver = DBDriverFactory::gen();
		$operation = $param->Operation;
		$paramvalue = $dbdriver->toDBString( $param->Value );

		$sql = "";

		switch( $propName ) {
			case 'Version': {
				LogHandler::Log( 'bizquery', 'DEBUG', 'Querying on Version not supported' );
				break;
			}
			case 'PublicationId': {
				$sql = "o.`publication` $operation $paramvalue ";
				break;
			}
			case 'ChannelId':
			case 'PubChannelId': // Should use this instead of ChannelId, but will not take out ChannelId to avoid breaking the current solution.
			{
				BizQueryBase::requireJoin4Where( 'tar' );
				BizQueryBase::requireJoin4Where( 'cha' );
				$sql = "cha.`id` $operation $paramvalue";
				break;
			}
			case 'CategoryId':
			case 'SectionId': {
				$sql = "o.`section` $operation $paramvalue";
				break;
			}
			case 'StateId': {
				$sql = "o.`state` $operation $paramvalue";
				break;
			}
			case 'IssueId': {
				self::$issueClauses[] = "iss.`id` $operation $paramvalue";
				break;
			}
			case 'IssueIds': {
				//BZ#10724 Joining with smart_targets to find out what objects do NOT have an issue assigned
				if( trim( $operation ) == '=' && empty( $paramvalue ) ) {
					BizQueryBase::requireJoin4Where( 'tar2' );
					$sql .= " tar2.`id` IS NULL ";
				}
				break;
			}
			case 'PubChannelIds': {
				if( trim( $operation ) == '=' && empty( $paramvalue ) ) {
					BizQueryBase::requireJoin4Where( 'tar' );
					$sql .= "tar.`channelid` IS NULL ";
				}
				break;
			}
			case 'Issue': {
				self::$issueClauses[] = self::buildWhereStringParam( $param, 'iss', 'name' );
				break;
			}
			case 'Publication': {
				BizQueryBase::requireJoin4Where( 'pub' );
				$sql = self::buildWhereStringParam( $param, 'pub', 'publication' );
				break;
			}
			case 'Category':
			case 'Section': {
				BizQueryBase::requireJoin4Where( 'tar' );
				BizQueryBase::requireJoin4Where( 'sec' );
				$sql = self::buildWhereStringParam( $param, 'sec', 'section' );
				break;
			}
			case 'State': {
				if( $paramvalue == BizResources::localize( 'PERSONAL_STATE' ) ) {
					$sql = "o.`state` $operation -1 ";
				} else {
					BizQueryBase::requireJoin4Where( 'sta' );
					$sql = self::buildWhereStringParam( $param, 'sta', 'state' );
				}
				break;
			}
			case 'Channel': {
				BizQueryBase::requireJoin4Where( 'tar' );
				BizQueryBase::requireJoin4Where( 'cha' );
				$sql = self::buildWhereStringParam( $param, 'cha', 'name' );
				break;
			}
			case 'Edition':
			case 'EditionId': {
				$sql = self::buildQueryForEdtion( $propName, $operation, $paramvalue, $param );
				break;
			}
			case 'RouteTo': {
				BizQueryBase::requireJoin4Where( 'rtg' );
				$sql = self::buildWhereStringParam( $param, 'rtg', 'name' );
				$sql .= ' OR ';
				$sql .= self::buildWhereUserString( $param, $propName, $operation, $paramvalue, 'rtu' );
				break;
			}
			case 'Modifier': {
				$sql = self::buildWhereUserString( $param, $propName, $operation, $paramvalue, 'mdf' );
				break;
			}
			case 'Creator': {
				$sql = self::buildWhereUserString( $param, $propName, $operation, $paramvalue, 'crt' );
				break;
			}
			case 'Deletor': {
				$sql = self::buildWhereUserString( $param, $propName, $operation, $paramvalue, 'dlu' );
				break;
			}
			case 'LockedBy': {
				// Define the sql here because it is different from buildWhereUserString()
				BizQueryBase::requireJoin4Where( 'lcc' );
				if( $operation != '!=' ) {
					$sql = self::buildWhereStringParam( $param, 'lcc', 'fullname' );
					$sql .= ' OR '; // Support search on short name
					BizQueryBase::requireJoin4Where( 'lcb' );
					$sql .= self::buildWhereStringParam( $param, 'lcb', 'usr' );
				} else {
					// Handle the IS NOT
					$sql .= self::buildWhereStringParam( $param, 'lcc', 'fullname' );
					$sql .= ' AND ';
					$sql .= self::buildWhereStringParam( $param, 'lcc', 'user' );
					if( $paramvalue != '' ) {
						$sql .= ' OR (lcc.`fullname` IS NULL)';
					}
				}
				break;
			}
			case 'PlacedOn': {
				if( $paramvalue == '' ) {
					$objectrelationstable = $dbdriver->tablename( "objectrelations" );
					$objectstable = $dbdriver->tablename( "objects" );
					if( $operation == '!=' ) { // All placed objects (BZ#17511).
						$sql .= " o.`id` IN (SELECT chi2.`child` FROM $objectrelationstable chi2, $objectstable obj2 WHERE chi2.`child` = obj2.`id` AND chi2.`type` = 'Placed') ";
					} else { // Not placed means object, has as a child, no placed relation (BZ#17511).
						$sql .= " o.`id` NOT IN (SELECT chi2.`child` FROM $objectrelationstable chi2, $objectstable obj2 WHERE chi2.`child` = obj2.`id` AND chi2.`type` = 'Placed') ";
					}

				} else { // Seaching for placed childs with parent with name like ....
					BizQueryBase::requireJoin4Where( 'chi' );
					BizQueryBase::requireJoin4Where( 'par' );
					$sql = self::buildWhereStringParam( $param, 'par', 'name' );
					$sql .= "AND chi.`type` = 'Placed' ";
					// Placed means parent relation of type 'placed' (BZ#17511).
				}
				break;
			}
			case 'PlacedOnPage': {
				BizQueryBase::requireJoin4Where( 'chi' );
				BizQueryBase::requireJoin4Where( 'par' );
				$sql = self::buildWhereStringParam( $param, 'par', 'pagerange' );
				break;
			}
			case 'ChildId': // object given is child, so asking for all its parents! (who have this as a child)
			{
				BizQueryBase::requireJoin4Where( 'chi2' );
				BizQueryBase::requireJoin4Where( 'par2' );
				$sql = self::buildWhereStringParam( $param, 'par2', 'child' );
				break;
			}
			case 'ChildRelationType': {
				BizQueryBase::requireJoin4Where( 'chi2' );
				BizQueryBase::requireJoin4Where( 'par2' );
				$sql = self::buildWhereStringParam( $param, 'par2', 'type' );
				break;
			}
			case 'ParentId': // object is given parent, so asking for all its childs! (who have this as a parent)
			{
				BizQueryBase::requireJoin4Where( 'chi' );
				BizQueryBase::requireJoin4Where( 'par' );
				$sql = self::buildWhereStringParam( $param, 'chi', 'parent' );
				break;
			}
			case 'ParentRelationType': {
				BizQueryBase::requireJoin4Where( 'chi' );
				BizQueryBase::requireJoin4Where( 'par' );
				$sql = self::buildWhereStringParam( $param, 'chi', 'type' );
				break;
			}
			case 'Keywords': {
				$sql = self::buildDefaultWhereString( $param, $propName, $operation, $paramvalue );
				//Keywords are passed like 'Dorian,Gray'. Suppose we have a record with Keywords 'Dorian,Edgar,Gray'.
				//A search on 'Dorian,Gray' will return an empty result set. To make the search a little bit better
				//a ',' is replaced by a '%'. So it doesn't matter if there are other words between the keywords in the
				//search. A search on 'Gray,Dorian' still gives an empty result. To prevent that you have to make all
				//possible permutations and that is too much.
				$sql = str_replace( ',', '%', $sql );
				break;
			}
			case 'Search': {
				// Search is a Solr property but if Solr is down do a search on name (BZ#18354)
				$param->Property = 'Name';
				$param->Operation = 'contains';
				$sql = self::buildDefaultWhereString( $param, $param->Property, $param->Operation, $paramvalue );
				break;
			}
			case 'ElementName': {
				BizQueryBase::requireJoin4Where( 'elm' );
				$sql = self::buildWhereStringParam( $param, 'elm', 'name' );
				$sql .= "AND o.`type` = 'Article' ";
				break;
			}
			default: {
				$sql = self::buildDefaultWhereString( $param, $propName, $operation, $paramvalue );
			}
		}

		return $sql;
	}

    /**
	 * Builds the query for Edition name or Id.
	 *
	 * @param string $propName The property name to build the edition query for.
	 * @param string $operation The operation to perform in SQL
	 * @param string $paramvalue The value of the param to be used.
	 * @param QueryParam $param
	 * @return string
	 */
	private static function buildQueryForEdtion($propName, $operation, $paramvalue, $param=null)
	{
		$dbdriver = DBDriverFactory::gen();
		$objectrelationstable = $dbdriver->tablename("objectrelations");
		$objectstable = $dbdriver->tablename("objects");
		$targetstable = $dbdriver->tablename("targets");
		$editionstable = $dbdriver->tablename("editions");
		$targeteditionstable = $dbdriver->tablename("targeteditions");

		$field = null;
		switch($propName) {
			case "EditionId" :
				$field = "e.id $operation $paramvalue";
				break;
			case "Edition" :
				$field = self::buildWhereStringParam( $param, 'e', 'name' );
				break;
		}

		// Match the relational target.
		$sql = "o.`id` IN
	        (SELECT ore.`child` FROM
		        $objectrelationstable ore,
	            $objectstable ob,
	            $targetstable t,
	            $targeteditionstable te,
	            $editionstable e
	            WHERE ore.`child` = ob.`id` AND ore.`id` = t.`objectrelationid` AND t.id = te.targetid AND te.editionid = e.id AND $field)";

		// Match the object target.
		$sql .= "OR o.`id` IN
	        (SELECT t.`objectid` FROM
	            $objectstable ob,
	            $targetstable t,
	            $targeteditionstable te,
	            $editionstable e
	            WHERE t.`objectid` = ob.`id` AND t.id = te.targetid AND te.editionid = e.id AND $field)";

		return $sql;
	}

    /**
    * Build the where statement for a search on user. The search supports both the full name and short name.
    * This statement is used to build the where for RouteTo, Modifier, Creator and Deletor.
    *
    * @param QueryParam $param contains query parameters
    * @param string $propName name of the property
    * @param string $operation the operator of the query
    * @param string $paramvalue the 'escaped' value of the parameter
    * @param string $userAlias the alias used for smart_users table
    * @return string where clause
    */
    static private function buildWhereUserString($param, $propName, $operation, $paramvalue, $userAlias )
    {
    	BizQueryBase::requireJoin4Where($userAlias);
    	if ( $operation != '!=' ) {
    		$sql = self::buildWhereStringParam( $param, $userAlias, 'fullname' );
    		$sql .= ' OR '; // Support search on short name
    		$sql .= self::buildDefaultWhereString($param, $propName, $operation, $paramvalue);
		} else {
    		// Handle the IS NOT
    		$sql = self::buildWhereStringParam( $param, $userAlias, 'fullname' );
    		$sql .= ' AND ';
    		$sql .= self::buildWhereStringParam( $param, $userAlias, 'user' );		// Support search on short name

    		if ( $paramvalue != '' ) {
   				// When searching on a value IS NOT should also return empty values
   				$sql .= ' OR ';
   				$param->Operation = '=';
   				$param->Value = '';
   				$sql .= self::buildWhereStringParam($param, 'o', strtolower($propName));
    		}
      }

      return $sql;
    }
    
    static private function buildDefaultWhereString($param, $propName, $operation, $paramvalue)
    {
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
    	require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
    	
        $objFields = BizProperty::getMetaDataObjFields();
        $sql = '';
        
		$propertyDBType = BizProperty::getDBTypeProperty($propName);
        if( DBProperty::isCustomPropertyName( $propName ) ) { // custom prop?
			$paramname = $propName;
			$paramtype = BizProperty::getCustomPropertyType($propName);
		} else { // built-in prop
			$paramname = $objFields[$propName];
			// we are working with Enterprise types here (see switch), so don't use getDBTypeProperty
			$paramtype = BizProperty::getStandardPropertyType( $propName );
			// in case of strings with db type = int we force type to be "int" (e.g. Id), so hidden "in" feauture works
			if ($paramtype == 'string' && $propertyDBType == 'int'){
				$paramtype = 'int';
			}
		}
		
        switch ($paramtype)
        {
            case 'bool':
            case 'int':
			case 'double':
				{
            	// BZ#17057 hidden feature: only support "in" with numbers
            	if ($operation == 'in'){
            		// "in" can only have numbers separated by commas, remove others
            		$paramvalue = preg_replace('/[^0-9,-.]/', '', $paramvalue);
            		if (!empty($paramvalue)) {
            			$sql = "o.`$paramname` IN ($paramvalue)";
            		}	
            	} else {
                	$sql = "o.`$paramname` $operation $paramvalue";
            	}
                break;
            }
			case 'list':
				{
            	// in case of string operations build string where clause
            	// else take the given operation
            	switch ($operation)
            	{
            		case 'contains':
            		case 'starts':
						case 'ends':
							{
            			$sql = self::buildWhereStringParam($param, 'o', $paramname);
            			break;
            		}
						default:
							{
            			$sql = "o.`$paramname` $operation '$paramvalue'";
            		}
            	}
				break;
            }
			case 'multistring':
				{
                $sql = self::buildWhereStringParam($param, 'o', $paramname ); // BZ#26198
                break;
            }
			case 'multilist':
				{
                $sql = self::buildWhereStringParam($param, 'o', $paramname);
                break;
            }
            case 'date':
			case 'datetime':
				{
                $sql = self::buildWhereDateParam($param, 'o', $paramname);
                break;
            }
            default:
            case 'multiline': 
			case 'string':
				{
            	if ($propertyDBType == 'blob' || $propertyDBType == 'clob' || $propertyDBType == 'text'){
            		$sql = self::buildWhereBlobParam($param, 'o', $paramname);
					} else {
                	$sql = self::buildWhereStringParam($param, 'o', $paramname);
        		}
                break;
            }
        } 

        return $sql;
    }

	/**
	 * Compose a sql query to be added into 'where' clause.
	 *
	 * @param QueryParam $param
	 * @param string $tablePrefix
	 * @param string $paramname
	 * @return string
	 */
	static private function buildWhereStringParam( $param, $tablePrefix, $paramname)
    {
		$dbdriver = DBDriverFactory::gen();
		
        if (DBTYPE == 'oracle') {
            return self::buildWhereStringParam4Oracle( $param, $tablePrefix, $paramname );
        }
        
        $operation = $param->Operation;
        $paramvalue = $dbdriver->toDBString($param->Value);
        
        $sql = '';
        
        switch ($operation)
        {
            case '=':
            {
                $sql = "$tablePrefix.`$paramname` $operation '$paramvalue'";
                break;
            }
            case '!=': 
            {
                $sql = "$tablePrefix.`$paramname` <> '$paramvalue'";
                break;
            }
            case 'contains':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` LIKE '%$escaped%'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'starts':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` LIKE '$escaped%'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'ends':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` LIKE '%$escaped'";
                $sql .= " ESCAPE '|' ";
                break;                
            }
        }
        return $sql;
    }
    
    static private function buildWhereBlobParam($param, $tablePrefix, $paramname)
    {
		$dbdriver = DBDriverFactory::gen();
		
        if (DBTYPE == 'oracle') {
            return self::buildWhereBlobParam4Oracle( $param, $tablePrefix, $paramname );
        } else if (DBTYPE == 'mysql'){
        	return self::buildWhereBlobParam4MySQL( $param, $tablePrefix, $paramname );
        }
        
        $operation = $param->Operation;
        $paramvalue = $dbdriver->toDBString($param->Value);
        
        $sql = '';
        
        switch ($operation)
        {
            case '=':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` LIKE '$escaped'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case '!=': 
            {
               $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` NOT LIKE '$escaped'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'contains':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` LIKE '%$escaped%'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'starts':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` LIKE '$escaped%'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'ends':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "$tablePrefix.`$paramname` LIKE '%$escaped'";
                $sql .= " ESCAPE '|' ";
                break;                
            }
        }
        return $sql;    	
    }

    static private function buildWhereStringParam4Oracle( $param, $tablePrefix, $paramname)
    {
		$dbdriver = DBDriverFactory::gen();
        $operation = $param->Operation;
        $paramvalue = $dbdriver->toDBString($param->Value);
        
        $sql = '';
        
        switch ($operation) {
            case '=':
            {
            	if ($paramvalue == '') {
                	$sql = "( UPPER($tablePrefix.`$paramname`) = UPPER('$paramvalue') OR $tablePrefix.`$paramname` IS NULL ) ";
            	}
				else {
                	$sql = "UPPER($tablePrefix.`$paramname`) = UPPER('$paramvalue') ";
				}
                break;
            }
            case '!=': 
            {
            	if ($paramvalue == '') {
                	$sql = " ( UPPER($tablePrefix.`$paramname`) <> UPPER('$paramvalue') OR $tablePrefix.`$paramname` IS NOT NULL ) ";
            	}
				else {
                	$sql = "UPPER($tablePrefix.`$paramname`) <> UPPER('$paramvalue') ";
				}
				break;
            }
            case 'contains':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "UPPER($tablePrefix.`$paramname`) LIKE UPPER('%$escaped%') ESCAPE '|' ";
                break;
            }
            case 'starts':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "UPPER($tablePrefix.`$paramname`) LIKE UPPER('$escaped%') ESCAPE '|' ";
                break;
            }
            case 'ends':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "UPPER($tablePrefix.`$paramname`) LIKE UPPER('%$escaped') ESCAPE '|' ";
                break;
            }
        }
        return $sql;
    }
    
   static private function buildWhereBlobParam4Oracle( $param, $tablePrefix, $paramname)
    {
		$dbdriver = DBDriverFactory::gen();
        $operation = $param->Operation;
        $paramvalue = $dbdriver->toDBString($param->Value);
        
        $sql = '';
        
        switch ($operation) {
            case '=':
            {
            	$escaped = DBQuery::escape4like($paramvalue, '|');
            	if ($paramvalue == '') {
                	$sql = "(UPPER($tablePrefix.`$paramname`) LIKE UPPER('$escaped') ESCAPE '|') OR $tablePrefix.`$paramname` IS NULL ";
            	}
				else {
                	$sql = "UPPER($tablePrefix.`$paramname`) LIKE UPPER('$escaped') ESCAPE '|' ";
				}
                break;
            }
            case '!=': 
            {
            	$escaped = DBQuery::escape4like($paramvalue, '|');
            	if ($paramvalue == '') {
                	$sql = "(UPPER($tablePrefix.`$paramname`) NOT LIKE UPPER('$escaped') ESCAPE '|') OR $tablePrefix.`$paramname` IS NOT NULL ";
            	}
				else {
                	$sql = "UPPER($tablePrefix.`$paramname`) NOT LIKE UPPER('$escaped') ESCAPE '|' ";
				}
                break;
            }
            case 'contains':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "UPPER($tablePrefix.`$paramname`) LIKE UPPER('%$escaped%') ESCAPE '|' ";
                break;
            }
            case 'starts':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "UPPER($tablePrefix.`$paramname`) LIKE UPPER('$escaped%') ESCAPE '|' ";
                break;
            }
            case 'ends':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "UPPER($tablePrefix.`$paramname`) LIKE UPPER('%$escaped') ESCAPE '|' ";
                break;
            }
        }
        return $sql;
    }    
    
   /**
     * Same as buildWhereBlobParam only specific for MySQL.
     * 
     * This function should be moved DBQuery or someting generic should be made in
     * the db drivers.
     * 
     * @see buildWhereBlobParam
     *
     * @param QueryParam $param
     * @param string $tablePrefix
     * @param string $paramname
     * @return string
     */
    static private function buildWhereBlobParam4MySQL(QueryParam $param, $tablePrefix, $paramname)
    {
		$dbdriver = DBDriverFactory::gen();
        $operation = $param->Operation;
        $paramvalue = $dbdriver->toDBString($param->Value);
        
        $sql = '';
        
        switch ($operation)
        {
            case '=':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "CONVERT( $tablePrefix.`$paramname` USING utf8) COLLATE utf8_general_ci LIKE '$escaped'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case '!=': 
            {
               $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "CONVERT( $tablePrefix.`$paramname` USING utf8) COLLATE utf8_general_ci NOT LIKE '$escaped'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'contains':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "CONVERT( $tablePrefix.`$paramname` USING utf8) COLLATE utf8_general_ci LIKE '%$escaped%'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'starts':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "CONVERT( $tablePrefix.`$paramname` USING utf8) COLLATE utf8_general_ci LIKE '$escaped%'";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'ends':
            {
                $escaped = DBQuery::escape4like($paramvalue, '|');
                $sql = "CONVERT( $tablePrefix.`$paramname` USING utf8) COLLATE utf8_general_ci LIKE '%$escaped'";
                $sql .= " ESCAPE '|' ";
                break;                
            }
        }
        return $sql;    	
    }    
    
    static private function buildWhereDateParam( $param, $tablePrefix, $paramname)
    {
		$dbdriver = DBDriverFactory::gen();
        require_once BASEDIR . '/server/utils/DateTimeFunctions.class.php';
        $operation = $param->Operation;
        $paramvalue = $dbdriver->toDBString($param->Value);

        $sql = '';
        switch ($operation)
        {
            case '=':
            {
				if (stristr($paramvalue, 'T') === false) {
            		$sql = "$tablePrefix.`$paramname` >= '$paramvalue" . "T00:00:00' AND ";
            		$sql .= "$tablePrefix.`$paramname` <= '$paramvalue" . "T23:59:59' ";
				}
				else {
            		$sql = "$tablePrefix.`$paramname` = '$paramvalue' ";
				}
            	break;
            }
            case '!=':
            {
				if (stristr($paramvalue, 'T') === false) {
	            	$sql = "$tablePrefix.`$paramname` < '$paramvalue" . "T00:00:00' OR ";
    	        	$sql .= "$tablePrefix.`$paramname` > '$paramvalue" . "T23:59:59' ";
				}
				else {
            		$sql = "$tablePrefix.`$paramname` <> '$paramvalue' ";
				}
            	break;
            }
            case '<':
            {
            	$sql = "$tablePrefix.`$paramname` < '$paramvalue'";
            	break;
            }
            case '>':
            {
                if (stristr($paramvalue, 'T') === false) {
            		$sql = "$tablePrefix.`$paramname` > '$paramvalue" . "T23:59:59' ";
				}
				else {
            		$sql = "$tablePrefix.`$paramname` > '$paramvalue' ";
				}            	
            	break;
            }
            case 'within':
            case 'starts':
            {
                $sql = self::createPeriodRange($operation, $paramvalue, "$tablePrefix.`$paramname`");
                break;
            }
            case 'contains': // E.g. web apps asking for certain day. See also BZ#8003, Modified field
            	$sql = self::buildWhereStringParam( $param, $tablePrefix, $paramname );
            	break;
			case 'between':
				{
					// The BETWEEN operator is inclusive, so the boundaries are included.
					// Therefore, when for the left hand side ($paramvalue) no time is specified,
					// the whole day must be included, so from the very beginning of the day.
					if( stristr( $paramvalue, 'T' ) === false ) {
						$paramvalue .= 'T00:00:00';
        }

					// When no time specified for the right hand side ($paramValue2),
					// again the whole day must be included, so taking the very end of the day.
					$paramValue2 = $dbdriver->toDBString( $param->Value2 );
					if( stristr( $paramValue2, 'T' ) === false ) {
						$paramValue2 .= 'T23:59:59';
					}

					$sql = "$tablePrefix.`$paramname` BETWEEN '$paramvalue' AND '$paramValue2'";
					break;
				}
		}
        return $sql;
    }

    static private function buildJoins4Where()
    {
        $availablejoins = BizQueryBase::listAvailableJoins(); 
        $requiredjoins4where = BizQueryBase::requireJoin4Where();
        $requiredjoins4where = array_merge($requiredjoins4where, BizQueryBase::requireJoin4Order());

        $sql = '';
        foreach ($availablejoins as $joinname => $joinsql) {
        	if (in_array($joinname, $requiredjoins4where)) {
        		//TODO can't we use INNER JOIN here? (except for tar2 and requireJoin4Order)
            	$sql .= " LEFT JOIN " . $joinsql . " ";
        	}                
        }
        
        // only select objects that meet the given issue parameters on their own object target and their parent relation target
        if (! empty(self::$issueClauses)){
        	$sql .= ' INNER JOIN (' . DBQuery::getIssueSubSelect(self::$issueClauses) . ') alltar ON (o.`id` = alltar.`objectid`) ';
        }
        
        return $sql;
    }

    static private function buildJoins()
    {
        $availablejoins = BizQueryBase::listAvailableJoins();
        $requiredjoins = BizQueryBase::requireJoin();
        $requiredjoins = array_merge($requiredjoins, BizQueryBase::requireJoin4Order());

        $sql = '';
        foreach ($availablejoins as $joinname => $joinsql) {
        	// Check if join is required, this works because BizQueryBase::requireJoin also checks for extra dependencies
        	if (in_array($joinname, $requiredjoins)) {
            	$sql .= " LEFT JOIN " . $joinsql . " ";
        	}                
        }
        return $sql;
    }
    
	/**
	 * Calculates the beginning, and the end of a period range.
	 *
	 * Validates the format of the interval definition, and then depending on the operation,
	 * 'starts' or 'within', calculates the beginning and the end of that period range.
	 *
	 * @param string $operation Type of operation, 'starts' or ' 'within'.
	 * @param string $value The Interval definition.
	 * @param string $field Property.
	 * @throws BizException
	 * @return string The selection string.
	 */
	private static function createPeriodRange( $operation, $value, $field )
	{
		// Check Interval definition
		if ( self::isProperDateTimeFormat($operation, $value) == false) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', $operation . " " . $value);
		}

		if (substr($value, 0, 1) === '-') {
			$operator = '-';
		}
		else {
			$operator = '+';
		}
		$entity = substr($value, -1, 1);
		//Extract number of minutes/hours/days/months
		$registers = array();
		preg_match("/(^[^0-9]*)([0-9]+)([^0-9]*)/", $value, $registers);
		$number = (int) $registers[2];
		$dateIndicator = (boolean) (stripos($value, 'T') === false);
		$timestamp = time();

		if ($operation == 'starts') {
			$periodTimestamps = self::startsPeriodRange(
									$operator,
									$entity,
									$number,
									$dateIndicator,
									$timestamp);
		} else {
			$periodTimestamps = self::withinPeriodRange(
									$operator,
									$entity,
									$number,
									$dateIndicator,
									$timestamp);

		}

		$startPeriod = date('Y-m-d\TH:i:s', $periodTimestamps["start"]);
		$endPeriod = date('Y-m-d\TH:i:s', $periodTimestamps["end"]);

		$periodRange = "$field >= '$startPeriod' and $field <= '$endPeriod'";
		
		return $periodRange;
	}

	/**
	 * Checks if the value format is allowed for the specified operation.
	 *
	 * For more information on the format, please see:
	 *   http://www.w3.org/TR/2004/REC-xmlschema-2-20041028/datatypes.html#duration
	 *
	 * @param string $operation The type of operation.
	 * @param string $value The interval definition.
	 * @return bool Whether or not the definition has the proper format.
	*/
	private static function isProperDateTimeFormat( $operation, $value )
	{
		if ($operation == 'starts') {
			if (preg_match("/(^[-]?)([P])([017])([D])/", $value) == 0) {
				return false;
			}
		}
		else {
			if (preg_match("/(^[-]?)([P])([T]?)([0-9]+)([DMH])/", $value) == 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns an array with a start and end timestamp, for a specific range.
	 *
	 * If the operation is 'starts', then the following logic will have to be adhered to when the value matches.
	 *  '-P7D' - The last FULL week is used for the result, if the '-' is omitted it will take the
	 *    results for the next FULL week. This will take the FIRST_DAY_OF_WEEK as a starting point.
	 *  '-P0D' - Everything created Today, the '-' can be omitted and will result in the same set.
	 *  '-P1D' - Everything created Yesterday, or Tomorrow if the '-' is omitted.
	 *
	 * A specification of the date formatting can be found here:
	 *   http://www.w3.org/TR/2004/REC-xmlschema-2-20041028/datatypes.html#duration
	 *
	 * @param $operator	'-' When searching in the past, else empty.
	 * @param string $entity Date/time entity (day/month etc..)
	 * @param int $number The number of days/hours, etc to add or deduct.
	 * @param string $dateIndicator The entity, date related or time related.
	 * @param string $timestamp The current time.
	 * @return string[] Array of timestamps of start and end date of the selected period.
	*/
	private static function startsPeriodRange( $operator, $entity, $number, $dateIndicator, $timestamp )
	{
		if ($number == '7') {
			$today = getdate($timestamp);
			if	($operator == '-') {
				$daysToStartDay = DateTimeFunctions::getDaysToPrevFirstDay( FIRST_DAY_OF_WEEK, $today['wday'] );
				$daysToEndDay = $daysToStartDay - 6;
			} else {
				$daysToStartDay = DateTimeFunctions::getDaysToNextFirstDay( FIRST_DAY_OF_WEEK, $today['wday'] );
				$daysToEndDay = $daysToStartDay + 6;
			}
		} else {
			$daysToStartDay = $number;
			$daysToEndDay = $number;
		}

		$timestampStart = DateTimeFunctions::calculateDateTime(
							$operator,
							$entity,
							$daysToStartDay,
							$dateIndicator,
							$timestamp);
		$timestampEnd = DateTimeFunctions::calculateDateTime(
							$operator,
							$entity,
							$daysToEndDay,
							$dateIndicator,
							$timestamp);

		$timestampStart = strtotime("00:00:00", $timestampStart);
		$timestampEnd = strtotime("23:59:59", $timestampEnd);

		$periodRange = array("start" => $timestampStart, "end" => $timestampEnd);

		return $periodRange;
	}

	/** Returns a start and end date for the 'within' operation.
	 *
	 * One week is defined as 7 days. So -P7D means from now minus 7 days (same time as now).
	 *
	 * A specification of the date formatting can be found here:
	 *   http://www.w3.org/TR/2004/REC-xmlschema-2-20041028/datatypes.html#duration
	 *
	 * @param string $operator '-' in case of dates in the past, else empty.
	 * @param string $entity Date/time entity (day/month etc..).
	 * @param int $number Number of days/hours etc to add or deduct.
	 * @param string $dateIndicator Entity is date related (true) or time related (false).
	 * @param string $timestamp (Current) time to start calculation.
	 * @return string[] An array of timestamps with the start and end of the selected period.
	*/
	private static function withinPeriodRange( $operator, $entity, $number, $dateIndicator, $timestamp )
	{
		if ($operator == '-') {
			$timestampEnd = $timestamp;
			$timestampStart = DateTimeFunctions::calculateDateTime(
								$operator,
								$entity,
								$number,
								$dateIndicator,
								$timestamp);
		} else {
			$timestampStart = $timestamp;
			$timestampEnd = DateTimeFunctions::calculateDateTime(
								$operator,
								$entity,
								$number,
								$dateIndicator,
								$timestamp);
		}

		$periodRange = array("start" => $timestampStart, "end" => $timestampEnd);

		return $periodRange;
	}

	/**
	 * Performs QueryObjects operation to get all properties of one object.
	 *
	 * This includes the custom properties. The $publishSystem and $templateId parameters are used
	 * to limit the amount of custom properties.
	 *
	 * @param string $objectId ID of object to get.
	 * @param array $areas
	 * @param string $publishSystem
	 * @param integer $templateId
	 * @return array Single QueryObjects Row containing object's property values.
	 */
	static public function queryObjectRow( $objectId, $areas = array('Workflow') , $publishSystem = null, $templateId = null )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$params = array( new QueryParam( 'ID', '=', $objectId, false ) );

		$row = array();
		$reqPropIds = array_keys( BizProperty::getPropertiesForObject( $objectId, $publishSystem, $templateId ) );
		if( $reqPropIds ) { // object found?
			if( is_null( $areas ) || in_array( 'Workflow', $areas ) ) { // null is the same as $areas=array('workflow') Refer to WSDL
				$sqlArray = self::buildSQLArray( $reqPropIds, $params, null, false ); //false = look in workflow
				$row = DBQuery::getObjectRow( $objectId, $sqlArray );
			}

			if( !$row && !is_null( $areas ) && in_array( 'Trash', $areas ) ) { // When Object not found in 'Workflow' above, look in the 'Trash' but only when it is asked, i.e $area= array('Trash');
				$sqlArray = self::buildSQLArray( $reqPropIds, $params, null, true ); //true = look in trash
				$row = DBQuery::getObjectRow( $objectId, $sqlArray );
			}
		}

		if( $row ) {
			$rows = array( $row );
			self::resolvePersonalStatusesAndFixColors( $rows );
			$row = $rows[0];
		}
		return $row;
	}

	/**
	 * This method handles the retrieval of enriched objects based on the passed object ids.
	 * This functions is called after an alternative search engine like Solr has done the primary filtering.
	 * No authorization is checked as this is already done by the search engine.
	 * After the rows are selected they are enriched by adding targets, placed on information etc.
	 * Also the children belonging to the rows on top level are resolved. These child rows are also enriched with extra
	 * information. The enriching process (processResultRows) is limitied to objects that are not placed many, many times.
	 * Examples of many placed objects are images used as icon on each layout or an article with some default text used in
	 * each issue. The reason is that the resolving of the issues, editions etc takes a lot of time without adding any
	 * useful information.
	 *
	 * @param array $objectIds Contains all object ids retrieved by the search engine.
	 * @param string $mode Specifies how the query was initiated (InDesign, Content Station etc).
	 * @param array $minimalProps The minimal set of properties needed by the client.
	 * @param array $requestProps Other properties requested
	 * @param boolean $hierarchical Specifies if child objects must be resolved.
	 * @param array $childRows Contains the ChildRow objects in case of hierarchical view.
	 * @param array $componentColumns List of Properties used as columns for element components .
	 * @param array $components List of element components info.
	 * @param array $areas Either 'Workflow' or 'Trash'
	 * @param array $queryOrder Contains QueryOrder objects to specify the column(s) and direction of the sorting.
	 * @return array with the top level objects.
	 * @throws BizException
	 * @throws Exception
	 * @see BizQuery::runDatabaseUserQuery
	 */
	static public function queryObjectRows(
		$objectIds, $mode, $minimalProps, $requestProps, $hierarchical, &$childRows, &$componentColumns, &$components,
		$areas = array( 'Workflow' ), $queryOrder = null )
	{
		$requestedPropNames = self::getPropNames( $mode, $minimalProps, $requestProps, $areas );
		$params = array();

		// BZ#17057 hidden feature: use operation "in" because many QueryParams can cost about 0.2s
		$params[] = new QueryParam( 'ID', 'in', implode( ', ', $objectIds ), false );
		$rowids = array();
		foreach( $objectIds as $objectId ) {
			$rowids[] = array( 'id' => $objectId );
		}

		$deletedObjects = in_array( 'Trash', $areas ) ? true : false;
		$sqlStruct = self::buildSQLArray( $requestedPropNames, $params, null, $deletedObjects );
		$topRows = DBQuery::getRows( $sqlStruct, 'ID' );
		$topView = DBQuery::createTopviewWithRowIDs( $rowids );
		// Top level objects with a limit number of placements.
		$limitPlacedTopView = DBQuery::createLimitTopView( $topView );
		$componentRows = array();
		self::enrichRows( $topRows, $componentRows, $topView, $limitPlacedTopView, $requestedPropNames );

		if( $hierarchical ) {
			$childComponentRows = array();
			$shortusername = BizSession::getShortUserName();
			$allChildRows = self::addChildren(
				$childComponentRows, $sqlStruct, $topView, $deletedObjects, true, $shortusername,
				$requestedPropNames, $params, $queryOrder );
			$componentRows = self::mergeComponentRows( $childComponentRows, $componentRows );
			$componentColumns = self::getComponentColumns( $componentRows );
			$components = self::getComponents( $componentRows );
			$childRows = self::getChildRows( $allChildRows );
		}

		DBQuery::dropRegisteredViews();

		return $topRows;
	}

	/**
	 * Validates objects found by search engine against db objects.
	 * $areas indicates whether to get the Objects from non-deleted objects or deleted objects.
	 * $areas = array('Workflow') => Non-Deleted Objects
	 * $areas = array('Trash') => Deleted Objects
	 *
	 * @param array $objectIds found by search engine
	 * @param string $searchEngine
	 * @param array $areas
	 * @return array objects present in db. Missing objects are logged.
	 */
	static public function validateSearchResultsAgainsDB($objectIds, $searchEngine, array $areas=null )
	{
		if( in_array('Workflow',$areas) || is_null($areas)){
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objectIdsDB = DBObject::getAttributeOfObjects($objectIds, 'ID');
		}else {
			$objectIdsDB = array();
		}

		if( in_array('Trash',$areas)){
			require_once BASEDIR.'/server/dbclasses/DBDeletedObject.class.php';
			$objectIdsDB = array_merge( $objectIdsDB, DBDeletedObject::getAttributeOfDeletedObjects( $objectIds, 'ID') );
		}

		//Check if all objects are in DB
		if (count($objectIds) > count($objectIdsDB)) {
			$missingRows = array_diff_key($objectIds, $objectIdsDB);
			$missingRows = array_flip($missingRows);
			LogHandler::Log($searchEngine, 'WARN', $searchEngine .' entries not found in database. Missing objects: '. implode(', ', $missingRows));
			//Never return/process rows that where not found in the Enterprise DB 
			$objectIds = array_intersect_key($objectIds, $objectIdsDB);
		}		
		
		return $objectIds;
	}

	/**
	 * Based on the response, containing all items within a dossier, the parameters
	 * for a Solr search are composed. The query will be on all IDs of the objects
	 * within a dossier. Next to these parameters a 'Type' parameter is added to trigger
	 * the proper facet define from config_solr.
	 * @param WflQueryObjectsResponse $response
	 * @return QueryParam[] Array with query parameters.
	 */
	static public function getParametersForDossierItemsFacets(WflQueryObjectsResponse $response )
	{
		$index  = 0; // Index of the array element containing the object ids.
		foreach ($response->Columns as $property) {
			if ( $property->Name == 'ID' ) {
				break;
			}
			++$index;
		}
		$params = array();
		foreach ( $response->Rows as $row ) {
			$params[] = new QueryParam( 'ID', '=', $row[$index], false);
		}
		// 'Fake' type to get the proper facets from the define in config_solr.php
		$params[] = new QueryParam('Type', '=', 'DossierItems', false);

		return $params;
	}
	
	/**
	 * Allow the IssueClauses to be cleared.
	 *
	 * When executing multiple QueryObject calls from within a single session, it is needed to clear the IssueClauses.
	 *
	 * @static
	 * @return void.
	 */
	static public function clearIssueClauses()
	{
		self::$issueClauses = array();
	}	
}
