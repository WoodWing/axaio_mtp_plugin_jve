<?php

require_once BASEDIR . '/server/dbclasses/DBQuery.class.php';
require_once BASEDIR . '/server/bizclasses/BizQueryBase.class.php';
require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';

class BizNamedQuery extends BizQueryBase
{
	/** Publish Status filter possible values:   **/
	const PUBLISH_MANAGER_FILTER_NOT_PUBLISHED = 'NotPublished'; // Publish Status filter for non-published Dossier/Form.
	const PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING = 'ReadyForPublishing'; // Publish Status filter for Dossier/Form ready to be published.
	const PUBLISH_MANAGER_FILTER_PUBLISHED = 'Published'; // Publish Status filter for published Dossier/Form.
	const PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE = 'UpdateAvailable'; // Publish Status filter for Dossier/Form ready to be updated.

	/**
	 * Perform a named query depending on the query($query) passed in.
	 *
	 * @param string  $ticket       Ticket assigned to user in this session
	 * @param string  $user         Username of the current user
	 * @param string  $query  		Query name
	 * @param array   $args       	List of QueryParam objects
	 * @param integer $firstEntry   Where to start fetching records
	 * @param integer $maxEntries   How many records to fetch
	 * @param bool    $hierarchical When true return the objects as a tree instead of in a list
	 * @param array|null   $queryOrder   On which columns to sort, in which direction
	 * @throws BizException
	 * @return WflNamedQueryResponse|null
	**/
	static public function namedQuery( $ticket, $user, $query, $args, $firstEntry = null, $maxEntries = null, 
										$hierarchical = false, $queryOrder = null )
	{
		$queryOrder = self::resolveQueryOrderDirection( $queryOrder ); // Ensure the Direction property is a boolean.

        if (empty($firstEntry)) {
            $firstEntry = 0;
        }
        if ($firstEntry > 0) {
            $firstEntry--;
        }
		
        // From WSDL MaxEntries: <!-- v4.2 Max count of requested objects (zero for all, nil for server defined default) -->
        if( is_null( $maxEntries ) ) {
        	$maxEntries = DBMAXQUERY;
        }
        if( empty( $maxEntries )) {
	       	$maxEntries = 0;
        }
        
        $minimalProps = null;
        // Set several settings dedicated for PublishFormTemplates to be pased on to the query search.
		if( $query == 'PublishFormTemplates' ) {
			$minimalProps = self::getMinimalPropsForPublishTemplates();			
			self::enrichPublishTemplatesQueryParams( $args );
			$hierarchicalSearch = $hierarchical; // respect the caller (and it should be false)
		} else {
			// In the past, -ALL- namedQueries were always hierarchical, and therefore, here we overrule
			// the $hierarchical sent in by the caller, always set it to True.
			$hierarchicalSearch = true; 
		}

		// Check if it's a NQ from our DB, if not pass on to content sources:
		//get the named query by $queryname
		$namedquery = DBQuery::getNamedQueryByName($query);
		$inbox = self::getInboxQueryName();
		$defaultArticleTemplate = self::getDefaultArticleTemplateQueryName();
		$builtInQueries = array( 'Inbox', $inbox, $defaultArticleTemplate, 'PublishFormTemplates', 'PublishManager' );
		$ret = null;
		if (empty($namedquery) && !in_array( $query, $builtInQueries ) ) { // Exclude system queries that are handled as a named query.
			require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
			$ret = BizContentSource::doNamedQuery($query, $args, $firstEntry, $maxEntries, $queryOrder);
		} else { // Handle built-in system queries and named queries.
			if( $query == 'PublishFormTemplates' ) { // requires PubChannelId param
				self::validatePublishingTemplatePubChannelId( $args );
			}

			if ( $query == 'PublishManager' ) { // Requires the PublishStatus param.
				self::validatePublishManagerQuery( $args );
			}

			$searchSucces = false; //If search by (Solr) search engine is succesful skip database search.		
			$mode = self::getQueryMode($ticket, 0, false);
			require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
			if (BizSearch::handleSearch( $query, $args, $firstEntry, $maxEntries, $mode, $hierarchicalSearch, $queryOrder, $minimalProps, null ) ) {
				try {
					$ret = BizSearch::search($query, $args, $firstEntry, $maxEntries, $mode, $hierarchicalSearch, $queryOrder, $minimalProps, null );
					$searchSucces = true;
				} catch (BizException $e){
					if ($e->getMessageKey() != 'ERR_SOLR_SEARCH') {
						throw $e;
					}
				}						
			}
			if (!$searchSucces) {
				switch( $query ) {
					case 'Inbox':
					case $inbox:
						$ret = self::runInboxDBQuery($mode, $ticket, $user, $firstEntry, $maxEntries, $queryOrder);
						break;
					case $defaultArticleTemplate:
						$ret = self::runDefaultArticleTemplateQuery($mode, $ticket, $user, $args, $hierarchical, $firstEntry, $maxEntries, $queryOrder);
						break;
					case 'PublishFormTemplates':
						$ret = self::queryPublishingTemplates( $ticket, $user, $args, $queryOrder, $hierarchical, $firstEntry, $maxEntries );
						break;
					case 'PublishManager':
						$ret = self::queryObjectRelations( $ticket, $user, $args, $firstEntry, $maxEntries, $queryOrder );
						break;
					default:
						$ret = self::runHierarchicalNamedQuery($user, $query, $args, $queryOrder, $firstEntry, $maxEntries);
				}
			}
		}
		
		require_once BASEDIR . '/server/dbclasses/DBLog.class.php';
		DBlog::logService($user, 'NamedQuery');
		return $ret;
	}
		 
 	/**
 	 * The function retrieves the Publishing Template given the Channel Id.
	 * When no template is found, it creates a new template on-the-fly and
	 * returns the result to the caller.
	 * BizException is thrown when no Channel Id is provided.
	 *
	 * @param string $ticket The logon session ticket
 	 * @param string $user The user shortname of the current user.
 	 * @param array $args List of QueryParam objects
 	 * @param array $queryOrder On which column to sort.
 	 * @param boolean $hierarchical When true return the objects as a tree instead of in a list
	 * @param integer $firstEntry   Where to start fetching records
	 * @param integer $maxEntries   How many records to fetch
	 * @return WflNamedQueryResponse
 	 */
 	static private function queryPublishingTemplates( $ticket, $user, $args, $queryOrder, $hierarchical, $firstEntry, $maxEntries )
 	{
		require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR . '/server/utils/PHPClass.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';

		$minimalProps = self::getMinimalPropsForPublishTemplates();
		$ret = BizQuery::queryObjects( $ticket, $user, $args, $firstEntry, $maxEntries, 0, 
										null, $hierarchical, $queryOrder, $minimalProps, null );

		// QueryObjects returns a WflQueryObjectsResponse, but a WflNamedQueryResponse is needed.
		return WW_Utils_PHPClass::typeCast( $ret, 'WflNamedQueryResponse' );
	}

	/**
	 * Retrieves the Pub Channel Id from the queryParam with Property 'PubChannelId'
	 * @param array $params An array of QueryParams that consists PubChannelId property
	 * @throws BizException Throws BizException when PubChannelId is not found.
	 * @return integer PubChannelId retrieves from the QueryParams.
	 */
	static private function validatePublishingTemplatePubChannelId( $params )
	{
		// search for the PubChannelId to be passed to retrieves the PublishForm templates.
		$pubChannelId = null;
		foreach ( $params as $param ) {
			// TODO: Should support multiple PubChannelId params
			if( $param->Property == 'PubChannelId' ) {
				$pubChannelId =  $param->Value;
				break;
			}
		}
		if( !$pubChannelId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No PubChannelId search param provided in NamedQuery.' );
		}
		return $pubChannelId;
	}
	
	/*
	 * Returns the minimal props needed for querying PublishiTemplates.
	 * @return array An array of property fields.
	 */
	static public function getMinimalPropsForPublishTemplates()
	{
		$minimalProps = array('ID', 'Type', 'Name', 'Rating', 'Description', 'DocumentID', 'PublicationId', 'IssueId' );
		return $minimalProps;
	}

	/**
	 * Enrich the passed in Query Params array by 
	 * adding one more QueryParam to query for PublishingForm Templates.
	 * @param array $args An array of Query Params.
	 */
	static private function enrichPublishTemplatesQueryParams( &$args )
	{
		$queryParam = new QueryParam();
		$queryParam->Property = 'Type';
		$queryParam->Operation = '=';
		$queryParam->Value = 'PublishFormTemplate';
		$args[] = $queryParam;
	}

	/**
	 * Executes a NamedQuery against the smart_objectrelations table.
	 *
	 * Executes the PublishManager named query to return a WflNamedQueryResponse matching the
	 * requested fields and rows.
	 *
	 * AccessRight can be one of the following:
	 *  0 = no check on access rights
	 * 	1 = Listed in Search Results (View) right
	 * 	2 = Read right
	 * 	11 = List in Publication Overview
	 *
	 * @param string $ticket Ticket assigned to user in this session
	 * @param string $user Username of the current user
	 * @param array $params List of query-parameters in fact containing where-statements
	 * @param integer $firstEntry Where to start fetching records
	 * @param integer $maxEntries How many records to fetch
	 * @param array $queryOrder the order in which the result set is returned.
	 * @param string[] $requestProps Complete list of props to return, overrules $requestProps as well as configured fields!
	 * @param integer $accessRight Access rights applicable for the current search.
	 * @return WflNamedQueryResponse The call Response.
	 */
	static private function queryObjectRelations( $ticket, $user, $params, $firstEntry = null, $maxEntries = null,
	                                              $queryOrder = null, $requestProps = null, $accessRight = 1 )
	{
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php'; // Used for logging the Response Object.
		$mode = self::getQueryMode( $ticket, false, false );

		if (isset($params)) {
			$params = self::resolvePublicationNameParams( $params );
			$params = self::resolveSpecialIssueParams( $params );
			$params = self::resolveIssueNameParams( $params );
		}

		$ret = self::runPublishManagerQuery( $user, $params, $firstEntry, $maxEntries, $queryOrder, $mode, $requestProps, $accessRight );
		DBlog::logService( $user, 'QueryObjectRelations'); // Log the Publish Manager Named Query.

		return $ret;
	}

	/**
	 * Executes the PublishManager Named Query.
	 *
	 * This method handles the actual querying of the ObjectRelations table and other related tables to
	 * retrieve the requested dataset.
	 *
	 * AccesRight can be one of the following:
	 *			0 = no check on access rights
	 * 			1 = Listed in Search Results (View) right
	 * 			2 = Read right
	 * 			11 = List in Publication Overview
	 *
	 * @param string $shortusername	User who is running the query. Needed to resolve the authorization.
	 * @param array $params Contains QueryParam objects to build the where statement.
	 * @param integer $firstEntry Specifies the offset of the first row to return (used for paging).
	 * @param integer $maxEntries Specifies the maximum number of rows to return (on top level).
	 * @param array $queryOrder	Contains QueryOrder objects to specify the column(s) and direction of the sorting.
	 * @param string $mode Specifies how the query was initiated (InDesign, Content Station etc).
	 * @param array $requestedPropertyNames	Complete list of props to return, overrules $requestProps as well as configured fields.
	 * @param integer $accessRight Access right applicable for the current search.
	 * @return WflQueryObjectsResponse	Response containing all requested information (rows)
	 * @throws BizException
	 */
	static private function runPublishManagerQuery( $shortusername, $params, $firstEntry, $maxEntries, $queryOrder,
		/** @noinspection PhpUnusedParameterInspection */ $mode,
	    $requestedPropertyNames, $accessRight )
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';

		// Get the Optional Query Parameters.
		$publishStatusFilter = '';
		$pubChannelIdsFilter = '';
		$usedWhereJoins = array();

		/** @var QueryParam $param */
		foreach ($params as $param) {
			if ( $param->Property == 'PublishStatus' ) {
				$publishStatusFilter = $param->Value;
			}

			if ($param->Property == 'PubChannelIds' ) {
				$pubChannelIdsFilter = $param->Value;
			}
		}

		// Get all join and property information so we can use them in our Named Query.
		$propertyNames = self::getPublishManagerPropertyNames($requestedPropertyNames); // All required Property Names.
		$joinProperties = self::getPublishManagerJoinProperties(); // All required Join aliases.
		$joinFieldProperties = self::getPublishManagerJoinFieldProperties(); // All required Field names.
		$availableJoins = self::getPublishManagerJoins(); // All required Joins.
		$objectProperties = BizProperty::getMetaDataObjFields();  // All Object Field names.

		// Determine basic data for the query.
		$dbdriver = DBDriverFactory::gen();
		$objectRelationsTable = $dbdriver->tablename('objectrelations');
		$objectRelationsTableAlias = 'obr';
		$objectsTable = $dbdriver->tablename('objects');
		$objectsTableAlias = 'o';

		// Construct the basic SQL Parts..
		$selectJoins = array( $objectsTableAlias => $availableJoins[$objectsTableAlias] ); // Join ObjectRelations and the Objects table.
		$selectFields = array();
		$selectPart = '/*SELECT*/ SELECT ';
		$fromPart = "/*FROM*/ FROM $objectRelationsTable $objectRelationsTableAlias ";
		$wherePart = "/*WHERE*/ WHERE " . $objectRelationsTableAlias . ".`parenttype` = 'Dossier'"
			. " AND " . $objectRelationsTableAlias . ".`subid` = 'PublishForm' ";
		$joinPart = '';
		$joins4where = "/*JOINS4WHERE*/ " . ' JOIN '. $objectsTable .' '.$objectsTableAlias.' ON o.`id` = obr.`child` ';
		$usedWhereJoins[] = $objectsTable;


		// Gather all details from the properties.
		foreach ( $propertyNames as $name ) {
			// Check if it is an Object table property.
			if (isset($objectProperties[$name]) && !empty($objectProperties[$name]) && $name != 'IssueId') {
				$selectFields[] = $objectsTableAlias . '.`' . $objectProperties[$name] . '` as "' . $name . '"';
			} else {
				// Join in the table(s).
				if (!array_key_exists($joinProperties[$name], $selectJoins) && $joinProperties[$name] != $objectRelationsTableAlias && $name != 'LockedBy') {
					$selectJoins[$joinProperties[$name]] = $availableJoins[$joinProperties[$name]];
				}
				// Select the value from the non-object table.
				if ($name == 'PublishedVersion') {
					$selectFields[] = $dbdriver->concatFields(array("tar.`publishedmajorversion`", "'.'", "tar.`publishedminorversion`")) . " as \"PublishedVersion\" ";
				} elseif ($name == 'LockedBy') {
					$selectJoins['lcb'] = $availableJoins['lcb'];
					$selectJoins['lcc'] = $availableJoins['lcc'];
					$selectFields[] = 'lcc.`fullname` as "' . $name . '"';
				} else {
					$selectFields[] = $joinProperties[$name] . '.`' . $joinFieldProperties[$name] . '` as "' . $name . '"';
				}

			}
		}

		// Expand the Query with required fields for the status filter.
		switch ( $publishStatusFilter ) {
			case self::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED :
				// Determine the fields for the PublishedDate Field.
				$publishedDateTableAlias = $joinProperties['PublishedDate'];
				$publishedDateFieldName = $joinFieldProperties['PublishedDate'];

				// publisheddate in the smart_targets table will be cleared(empty) when it is not published.
				$wherePart .= "AND " . $publishedDateTableAlias . ".`" . $publishedDateFieldName . "` = '' "; // AND tar.`publisheddate` = ''

				// We need to add the targets table to the joins4where.
				$joinType = self::getJoinType($publishedDateTableAlias);
				$join = $availableJoins[$publishedDateTableAlias];
				$joins4where .= $joinType . $join . ' '; // JOIN smart_targets tar ON (obr.`id` = tar.`objectrelationid`)
				$usedWhereJoins[] = $publishedDateTableAlias;
				break;
			case self::PUBLISH_MANAGER_FILTER_PUBLISHED :
				// Determine the fields for the PublishedDate Field.
				$publishedDateTableAlias = $joinProperties['PublishedDate'];
				$publishedDateFieldName = $joinFieldProperties['PublishedDate'];

				// publisheddate in the smart_targets table will be set (not empty) when it is published.
				$wherePart .= "AND " . $publishedDateTableAlias . ".`" . $publishedDateFieldName . "` != '' "; // AND tar.`publisheddate` != ''

				// We need to add the targets table to the joins4where.
				$joinType = self::getJoinType($publishedDateTableAlias);
				$join = $availableJoins[$publishedDateTableAlias];
				$joins4where .= $joinType . $join . ' '; // JOIN smart_targets tar ON (obr.`id` = tar.`objectrelationid`)
				$usedWhereJoins[] = $publishedDateTableAlias;
				break;
			case self::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING :
				// A PublishForm is ready for Publishing if the ReadyForPublishing flag is set, and the PublishDate is
				// Empty.

				// Get the field definition.
				$statesTableAlias = $joinProperties['State'];
				$readyForPublishingField = 'readyforpublishing';

				// Get The Targets definiton.
				$publishedDateTableAlias = $joinProperties['PublishedDate'];
				$publishedDateFieldName = $joinFieldProperties['PublishedDate'];

				// Determine the fields for the Modified Field.
				$ModifiedTableAlias = $objectsTableAlias;
				$ModifiedFieldName = 'modified';

				// Condition 1
				$subWherePart = $publishedDateTableAlias . ".`" . $publishedDateFieldName . "` = '' "; // tar.`publisheddate` = ''
				// Add the restriction on the state to the Query.
				$subWherePart .= "AND " . $statesTableAlias . ".`" . $readyForPublishingField . "` = 'on' "; // AND sta.readyforpublishing = 'on'

				$condition1 = '( ' . $subWherePart .' ) '; // ( tar.`publisheddate` = '' AND sta.readyforpublishing = 'on' )

				// Condition 2
				// Exclude any of the records where the PublishedDate is left empty, after all if it is not Published,
				// It should not show up as being ready to be updated.
				$subWherePart = $publishedDateTableAlias . ".`" . $publishedDateFieldName . "` != '' "; // tar.`publisheddate` != ''
				// Include any of the records where the modified date is greater than the publisheddate.
				$subWherePart .= "AND " . $ModifiedTableAlias . ".`" . $ModifiedFieldName . "` > " .
					$publishedDateTableAlias . ".`" . $publishedDateFieldName . "` "; // AND o.`modified` > tar.`publisheddate`

				$condition2 = '( ' . $subWherePart . ' ) '; // ( tar.`publisheddate` != '' AND o.`modified` > tar.`publisheddate` )

				// Combine condition1 and condition2.
				$wherePart .= 'AND (' . $condition1 . ' OR '. $condition2 . ') '; // AND ( ( tar.`publisheddate` = '' AND sta.readyforpublishing = 'on' ) OR ( tar.`publisheddate` != '' AND o.`modified` > tar.`publisheddate` ) )

				// We need to add the states table to the joins4where.
				$joinType = self::getJoinType($statesTableAlias);
				$join = $availableJoins[$statesTableAlias];
				$joins4where .= $joinType . $join . ' ';
				$usedWhereJoins[] = $statesTableAlias;

				// We need to add the targets table to the joins4where.
				$joinType = self::getJoinType($publishedDateTableAlias);
				$join = $availableJoins[$publishedDateTableAlias];
				$joins4where .= $joinType . $join . ' ';
				$usedWhereJoins[] = $publishedDateTableAlias;
				break;
			case self::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE :
				// When resolving the filter 'UpdateAvailable' we need to return only those PublishForms that have a
				// Modified date that is newer than the PublishedDate.

				// Determine the fields for the PublishedDate Field.
				$publishedDateTableAlias = $joinProperties['PublishedDate'];
				$publishedDateFieldName = $joinFieldProperties['PublishedDate'];

				// Determine the fields for the Modified Field.
				$modifiedTableAlias = $objectsTableAlias;
				$modifiedFieldName = 'modified';

				// Exclude any of the records where the PublishedDate is left empty, after all if it is not Published,
				// It should not show up as being ready to be updated.
				$wherePart .= "AND " . $publishedDateTableAlias . ".`" . $publishedDateFieldName . "` != '' ";

				// Include any of the records where the modified date is greater than the publisheddate.
				$wherePart .= "AND " . $modifiedTableAlias . ".`" . $modifiedFieldName . "` > " .
					$publishedDateTableAlias . ".`" . $publishedDateFieldName . "` ";

				// We need to add the targets table to the joins4where.
				$joinType = self::getJoinType($publishedDateTableAlias);
				$join = $availableJoins[$publishedDateTableAlias];
				$joins4where .= $joinType . $join . ' ';
				$usedWhereJoins[] = $publishedDateTableAlias;
				break;
			default :
				break;
		}

		// Handle the optional PubChannelIds filter if not left empty.
		if ( !empty( $pubChannelIdsFilter ) ) {
			$targetTableAlias = $joinProperties['PubChannelId'];
			$pubChannelIdsFilter = trim( $pubChannelIdsFilter, ',' ); // Trim pre and post ',')

			// Build the Where join if needed.
			if ( !in_array( $targetTableAlias, $usedWhereJoins ) ) {
				$joinType = self::getJoinType( $targetTableAlias ); // Get the JoinType for the targets table.
				$join = $availableJoins[$targetTableAlias];
				$joins4where .= $joinType . $join . ' ';
				$usedWhereJoins[] = $targetTableAlias;
			}
			$wherePart .= "AND tar.`channelid` IN ( " . $pubChannelIdsFilter . ") ";
		}

		// Construct the full Select.
		$selectPart .= implode( ', ', $selectFields ) . ' ';

		// Add the From joins.
		foreach ($selectJoins as $key => $join) {
			$joinPart .= self::getJoinType($key) . $join . ' ';
		}

		// Build the SQL array.
		$sqlArray = array();
		$sqlArray['select'] = $selectPart;
		$sqlArray['from'] = $fromPart;
		$sqlArray['where'] = $wherePart;
		$sqlArray['joins'] = $joinPart;
		$sqlArray['joins4where'] = $joins4where;
		$sqlArray = self::resolvePublishManagerQueryOrder( $queryOrder, $sqlArray );

		try {
			// Create a view containing all object-id's for which $user is authorized.
			if ( $accessRight > 0 ) {
				$objectsWhere = self::buildWhereForPublishManagerParams( $params );
				DBQuery::createAuthorizedObjectsView($shortusername, false, $params, false, false, $objectsWhere, $accessRight );
				// Add the Join to the temp table.
				$sqlarray['joins'] = $sqlArray['joins'] . " INNER JOIN " . DBBase::getTempIds('aov') . " aov ON (aov.`id` = o.`id`) ";
				$sqlarray['where'] = $sqlArray['where'] . " AND (aov.`id` IS NOT NULL)";
			}

			$topcount = 0;
			$topView = DBQuery::createTopView($sqlArray, $topcount, $firstEntry, $maxEntries, $accessRight > 0 );
			// Top level objects with a limit number of placements.
			$limitTopview = DBQuery::createLimitTopView($topView);
			$topRows = DBQuery::getTopObjects( $sqlArray, true ); // true: to use the $where clause in the query.
			$dummy = array(); // Expected to be passed by Reference, even though we do not use it here.
			$topRows = self::processResultRows($topRows, $propertyNames, $limitTopview, $topView, $dummy, true, array('Workflow'), 'ObjectRelation');
			DBQuery::dropRegisteredViews(); // Drop the created Views.
		}
		catch (BizException $e) {
			DBQuery::dropRegisteredViews(); // Drop any of the created Views from the Database and rethrow the error.
			throw($e);
		}

		// Create a namedQuery Response.
		$response = new WflNamedQueryResponse();

		// If there are Rows to be returned, add them to the Response Object.
		$topRowCount = count( $topRows );
		if ( $topRowCount > 0 ) {
			$topRows = self::reorderColumns($topRows, $propertyNames);
			$columns = self::doGetColumnsForPublishManagerQuery( $topRows );
			$response->Columns = $columns;
			$response->Rows = self::getRows($topRows);
			$response->FirstEntry = $firstEntry + 1;
			$response->ListedEntries = $topRowCount;
			$response->TotalEntries = $topcount;
		} else {
			// If there are no topRows, set a default list of Columns.
			$columns = self::doGetColumnsForPublishManagerQuery( array(array_flip($propertyNames)) );
			$response->Columns = $columns;
			$response->Rows = array();
		}
		return $response;
	}

    private static function runHierarchicalNamedQuery($shortusername, $queryname, $args, $queryorder, $limitstart, $limitcount)
    {	
        $namedquery = DBQuery::getNamedQueryByName($queryname);
        if (empty($namedquery)) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', 'Named query not found: ' . $queryname);
        }

        $sql = self::replaceArgs($namedquery['sql'], $namedquery['interface'], $args, $shortusername);
        $sqlarray = self::splitNamedQuerySQL($sql);

        if ($sqlarray === null) {
			throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Invalid sql in named query: ' . $queryname);
        }
        
        $sqlarray['orderby'] = self::queryorder2SQL($queryorder, $sqlarray['orderby']);
        $sqlarray['joins'] .= self::extendJoins();
        
        //when checkaccess is true, create a view containing all object-id's for which $user is authorized
        $checkAccess = ($namedquery['checkaccess'] == 'on' ? true : false);
        if($checkAccess) {
        	DBQuery::createAuthorizedObjectsView($shortusername,  false, null, false, true);
        }

        //Create a view containing all object-id's of objects found by the where-part in the sql.
        //Basically, the select-part of the sql is ignored and replaced by 'SELECT o.`id`'
        //Please note: when executing a named query without hierarchy we do not need to create a 'topView' but here we do
        //as we need it for querying the children.
        $totalcount = 0;
		$topview = DBQuery::createTopView($sqlarray, $totalcount, $limitstart, $limitcount, $checkAccess);
		// Get all (nested) children of the parents.
		$allChildren = DBQuery::getAllChildren($topview);
	    $allChildrenIds = array_keys($allChildren);

		if ($checkAccess) {
			self::addObjectsToAuthorizedView($shortusername, $allChildren);
		}	    
        //creates a view containing all object-id's and parentid's of ALL children of the topview (recursive!)
		//for which $user is authorized when checkaccess is true, else return all.
		$allchildrenview = DBQuery::createAllChildrenView($allChildren, $checkAccess);
        
        //Gets the top-objects by joining with the topview and using the select from the namedquery
        //Please note: it is assumed here that this is faster than using:
        //$toprows = DBQuery::getObjectRows($sql);
        //which should give the same resultset but does it in a different way.
        $toprows = DBQuery::getTopObjects($sqlarray);
        self::resolvePersonalStatusesAndFixColors( $toprows );
        if (strripos($sqlarray['select'], "RouteTo")) {
        	self::resolveRouteTo($toprows);
        }
        //Calculating IssueId for $topview
		$toptargets = DBTarget::getArrayOfTargetsByObjectViewId($topview);
		self::resolveTargets($toprows, $toptargets, array('IssueId'));

        //Gets the topchildren-objects by joining with the topchildrenview and using the select from the namedquery
        $allchildrows = DBQuery::getAllChildrenObjects($sqlarray);
	    $allchildrows = self::reorderChildren($allChildrenIds, $sqlarray, $allchildrows);
        self::resolvePersonalStatusesAndFixColors( $allchildrows );
        if (strripos($sqlarray['select'], "RouteTo")) {
        	self::resolveRouteTo($allchildrows);
        }
        //Calculating IssueId for $topview
		$childtargets = DBTarget::getArrayOfTargetsByObjectViewId($allchildrenview);
		self::resolveTargets($allchildrows, $childtargets, array('IssueId'));        
        
        $parents = DBQuery::getParentsByView($allchildrenview);
        foreach ($allchildrows as &$childrow) {
            $childrow['smart_parents'] = $parents[$childrow['ID']];
        }
        
        //Queries the components/elements from smart_elements for all parents
        $topcomponentrows = DBQuery::getElementsByView($topview);

        //Queries the components/elements from smart_elements for all found children
        $childcomponentrows = DBQuery::getElementsByView($allchildrenview);
                
        //Merge the different componentrows
        $componentrows = array();
        foreach ($topcomponentrows as $key => $value) {
            $componentrows[$key] = $value;
        }
        foreach ($childcomponentrows as $key => $value) {
            if (!array_key_exists($key, $componentrows)) {
                $componentrows[$key] = $value;
            }   
        }
        
        //Merge the different componentrows
        //$componentrows = array_merge($topcomponentrows, $childcomponentrows);

	    $emptyrow = array();
		if (!count($toprows)) {
			$emptyrow = DBQuery::listColNamesAsRow($sqlarray);
			//make sure IssueId as as columnname is added as well...
			$emptyrow['IssueId'] = null;
		}

        //Drop the created views (essential to not get a lot of views in the database!)
        DBQuery::dropRegisteredViews();

		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';

		if (!count($toprows)) {
			return new WflNamedQueryResponse(
				self::getColumns(array($emptyrow)),
				array(), // an empty array instead of NULL -> respect WSDL
				null,
				null,
				null,
				null,
				1,
				0,
				0,
				null,
				null);
		}
		else {
			return new WflNamedQueryResponse(
				self::getColumns($toprows),
				self::getRows($toprows),
				self::getChildColumns($toprows),
				self::getChildRows($allchildrows),
				self::getComponentColumns($componentrows),
				self::getComponents($componentrows),
				$limitstart + 1,
				count($toprows),
				$totalcount,
				null,
				null);
		}
    }
        
	/**
	 * Reorders and replaces the children in the childRows section.
	 *
	 * @static
	 * @param int[] $sortedIds Array of children ids.
	 * @param string[] $sqlArray Array of SQL part strings.
	 * @param mixed $allchildrows Array The original record set
	 * @return mixed $newOrder array The reorderded result set.
	 */
	static function reorderChildren($sortedIds, $sqlArray, $allchildrows){

		if (count($sortedIds) ==0){
			return $allchildrows;
		}

		// Build the WHERE clause of the criteria to sort the items properly.
		$inChildren = 'WHERE o.`id` IN (' . implode(',',$sortedIds) . ')';
		$sqlArray['where'] = $inChildren;

		// Create the component array and retrieve the records.
		$rows = DBQuery::getRowsBySqlArray($sqlArray);

		// Sort the array values.
		$newOrder = array();
		foreach ($rows as $row){
			$id = $row['id'];
			$newOrder[$id] = $allchildrows[$id];
		}

		return $newOrder;
	}

	/**
	 * Deprecated in v6!!!
	 *
	 * @param boolean $hidden
	 * @throws BizException
	 * @return NamedQueryType[]
	 */
	static public function getNamedQueries($hidden = true)
	{
		// fetch into array
		$ret = array();
		$ret[] = self::addInboxToNamedQueries();
		
		$dbdriver = DBDriverFactory::gen();
		
		$sth = DBQuery::getNamedQueries(null, $hidden);
		if (!$sth) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbdriver->error() );
		}

		while (($row = $dbdriver->fetch($sth)) ) {
			$ret[] = new NamedQueryType( $row['query'], self::queryInterface($row['interface'] ) );
		}

		// Get queries from content source providers:
		require_once BASEDIR . '/server/bizclasses/BizContentSource.class.php';
		$ret = array_merge( $ret, BizContentSource::getQueries() );
	
		return $ret;
	}
	
	/**
	 * Adds the 'Inbox' query to the named queries. From v7.0 onwards the 'Inbox'
	 * query is a system query like the 'Browse' query. For backwards compatibility
	 * the 'Inbox' query is added to the named queries list (as first entry). 
	 *
	 * @return object NamedQueryType
	 */
	static private function addInboxToNamedQueries()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		if (BizServerPlugin::isPluginActivated('SolrSearch')) {
			$queryParamSearch = array( new PropertyInfo( 
				'Search', 			//Name
			 	BizResources::localize('OBJ_SEARCH', true), 			//Display Name
				null,				// Category, not used
				'string',			// Type: string, multistring, multiline, bool, int, double, date, datetime, list or multilist
				'',					// Default value
				null,				// value list
				null, null, null,	// min value, max value,max length
				null, null			// parent value (not used), dependent property (not used)
				));
		} else {
			$queryParamSearch = array(); // null not allowed
		}
		return new NamedQueryType( self::getInboxQueryName(), $queryParamSearch);
	}
	
	/**
	 * Translates the interface of a named query to property info objects.
	 *
	 * @param string $input
	 * @return PropertyInfo[]
	 */
	private static function queryInterface( $input )
	{
		// Delimiter depends on OS.
		if ( strpos( $input, "\r\n" )) {
			$delimitor = "\r\n";
		} else {
			$delimitor = "\n";
		}
		$interface = array();
		$pars = explode( $delimitor, trim( $input ));
		foreach ( $pars as $par ) {
			if ( $par ) {
				$desc = explode( ",", $par );
				if ( !isset( $desc[1] )){ $desc[1] = ''; }
				if ( !isset( $desc[2] )){ $desc[2] = ''; }
				$list = array();
				if ( isset( $desc[3] )) {
					$list = explode( "/", trim($desc[3] ));
				}
				$desc[4] = isset( $desc[4] ) ? trim( $desc[4] ) : null;
				$desc[5] = isset( $desc[5] ) ? trim( $desc[5] ) : null;
				$desc[6] = isset( $desc[6] ) ? trim( $desc[6] ) : null;
				$interface[] = new PropertyInfo(
										trim( $desc[0] ), trim( $desc[0] ), '', trim( $desc[1] ), trim( $desc[2] ),
										$list, $desc[4], $desc[5], $desc[6] );
			}
		}
		return $interface;
	}

    private static function replaceArgs($sql, /** @noinspection PhpUnusedParameterInspection */ $interface,
                                        $args, $shortusername)
    {
		// TODO: Verify if interface and $args are in line
        if (!is_array($args)) {
	    	$sql = str_replace('$user', $shortusername, $sql);
            return $sql;
        }
        
        if (count($args) < 1) {
        	$sql = str_replace('$user', $shortusername, $sql);
	        return $sql;
        }

      	foreach ($args as $arg) {
			$val = strtr($arg->Value,",", " "); //anti-hack
			$val = str_replace("'", "''", $val);
	    	$sql = str_replace("'%$" . $arg->Property . "%'" , "'%" . DBQuery::escape4like($val, '|'). "%'" . " ESCAPE '|' ",  $sql);
    		$sql = str_replace("%$" . $arg->Property       , "%" . DBQuery::escape4like($val, '|') . " ESCAPE '|' ",  $sql);
    		$sql = str_replace("$"  . $arg->Property . "%" ,       DBQuery::escape4like($val, '|'). "%" . " ESCAPE '|' ",  $sql);
			$sql = str_replace('$'  . $arg->Property       ,       $val                        ,  $sql);
    	}
    	$sql = str_replace('$user', $shortusername, $sql);
    	return $sql;
    }
    
    private static function splitNamedQuerySQL($sql)
    {
        $result = array();
        $pos_select = stripos($sql, '/*SELECT*/', 0);
        $pos_from = stripos($sql, '/*FROM*/', 0);
        $pos_joins = stripos($sql, '/*JOINS*/', 0);
        $pos_where = stripos($sql, '/*WHERE*/', 0);
        $pos_orderby = stripos($sql, '/*ORDERBY*/', 0);
        
        if ($pos_select === false || $pos_from === false || $pos_joins == false || $pos_where === false) {
            return null;   
        }
        
        if ($pos_select > $pos_from ||
            $pos_from > $pos_joins ||
            $pos_joins > $pos_where ||
            ($pos_where > $pos_orderby && $pos_orderby !== false)) {
            return null;
        }
        
        $result['select'] = substr($sql, $pos_select, $pos_from - $pos_select);
        $result['from'] = substr($sql, $pos_from, $pos_joins - $pos_from);
        $result['joins'] = substr($sql, $pos_joins, $pos_where - $pos_joins);
        
        if ($pos_orderby !== false) {
            $result['where'] = substr($sql, $pos_where, $pos_orderby - $pos_where);
            $result['orderby'] = substr($sql, $pos_orderby);
        }
        else {
            $result['where'] = substr($sql, $pos_where);
            $result['orderby'] = '';
        }
        return $result;
    }

    /**
     * This method adds joins to the sql statement based on the tables needed
     * for sorting. The table must be availabe in the list of available joins.
     *
     * @return string containing join statements.
     */
    static private function extendJoins()
    {
        $availablejoins = BizQueryBase::listAvailableJoins();
        $requiredjoins = BizQueryBase::requireJoin();
        $requiredjoins = array_merge($requiredjoins, BizQueryBase::requireJoin4Order());

        $sql = '';
        foreach ($availablejoins as $joinname => $joinsql) {
        	if (in_array($joinname, $requiredjoins)) {
            	$sql .= " LEFT JOIN " . $joinsql . " ";
        	}                
        }
        return $sql;
    }
    
    /**
     * This method translates a short user name to its full name if applicable.
     * The routeto property contains a short user name or a group name.
     * Translation is only applicable when the object is routed to an user.
     * The route to property of the standard named queries is called 'RouteTo'.
     * @param array $rows rows containing objects and their properties
     */
    static public function resolveRouteTo(&$rows)
    {
    	require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
    	$shortNameToFullName = array();
    	
		foreach ($rows as &$row) {
			$routeto = $row['RouteTo'];
			if ($routeto) {
				if (array_key_exists($routeto, $shortNameToFullName)) {
					$row['RouteTo'] = $shortNameToFullName[$routeto];
				}
				else {
					// Try to get full name
					$fullname = DBUser::getFullName($routeto);	
					if ($fullname) {
						//Add short name and full name to array
						$shortNameToFullName[$routeto] = $fullname;
						$row['RouteTo'] = $fullname;
					}
					else {
						// If a short name has no full name or if routeto
						// refers to a group
						$shortNameToFullName[$routeto] = $routeto;
					}
				}				
			}
		}
	} 

	/**
	 * Build an array with parameters to search Inbox results. Used as a pseudo system query.
	 * Based on the (short) user name the query params for the 'RouteTo' property are generated. 
	 * Next the query is passed as a kind of custom query to queryObjecs. 
	 *
	 * @param string  $mode         Result taken from self::getQueryMode()
	 * @param string  $ticket       Ticket assigned to user in this session
	 * @param string  $user         Username of the current user
	 * @param integer $firstEntry   Where to start fetching records
	 * @param integer $maxEntries   How many records to fetch
	 * @param array   $queryOrder   On which columns to sort, in which direction
	 * @return WflNamedQueryResponse
	 */
	static private function runInboxDBQuery( $mode, $ticket, $user, $firstEntry, $maxEntries, $queryOrder )
	{
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
		$params = array();
		$userRow = DBUser::getUser($user);
		$userId = $userRow['id'];
		$groups = BizUser::getMemberships($userId);
		
		$params[] = new QueryParam('RouteTo', '=', $user); 
		foreach ($groups as $group) {
			$params[] = new QueryParam('RouteTo', '=', $group->Name);	
		}				
		
		// Minimal properties needed for "Inbox' query. BZ#16495
		$minimalProps = array('ID', 'Type', 'Name', 'State', 'Format', 'LockedBy', 'Category' ,'PublicationId', 'SectionId', 'StateId', 'PubChannelIds');
		$minimalProps = self::addMinPropForOverRuleIssue($mode, $minimalProps);	
		
		$result = BizQuery::queryObjects($ticket, $user, $params, $firstEntry, $maxEntries, 0, null, true, $queryOrder, $minimalProps, null);
		
		// QueryObjects returns a WflQueryObjectsResponse, but a WflNamedQueryResponse is needed. So parse the object. BZ#17257
		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		$result = WW_Utils_PHPClass::typeCast( $result, 'WflNamedQueryResponse' );
		
		return $result;
	}	

	/**
	 * Returns the name of the built-in inbox, which is localized for current user.
	 * For English users this is 'Inbox'.
	 *
	 * @return string
	 */
	public static function getInboxQueryName()
	{
		return BizResources::localize('OBJ_INBOX');
	}

	/**
	 * Build an array with parameters to search article template results.
	 * Next the query is passed as a kind of custom query to queryObjects.
	 *
	 * @param string $mode
	 * @param string $ticket
	 * @param string $user
	 * @param array $params
	 * @param bool $hierarchical
	 * @param int $firstEntry
	 * @param int $maxEntries
	 * @param array $queryOrder
	 * @return WflNamedQueryResponse
	 */
	private static function runDefaultArticleTemplateQuery($mode, $ticket, $user, $params, $hierarchical, $firstEntry, $maxEntries, $queryOrder )
	{
		$params[] = new QueryParam('Type', '=', 'ArticleTemplate'); // Article template
		$params[] = new QueryParam('Format', '=', 'application/incopyicmt'); // CS5 article template

		$minimalProps = array('ID', 'Name', 'Type', 'Format', 'Publication', 'PublicationId', 'Category', 'CategoryId',
			'PubChannels', 'PubChannelIds', 'State', 'Rating', 'Description');
		$minimalProps = self::addMinPropForOverRuleIssue($mode, $minimalProps);

		require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
		$result = BizQuery::queryObjects($ticket, $user, $params, $firstEntry, $maxEntries, 0, null, $hierarchical, $queryOrder, $minimalProps, null);

		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		$result = WW_Utils_PHPClass::typeCast( $result, 'WflNamedQueryResponse' );
		
		return $result;
	}

	/**
	 * Returns the name of the hidden default article template named query.
	 *
	 * @return string
	 */
	private static function getDefaultArticleTemplateQueryName()
	{
		return 'DefaultArticleTemplate';
	}

	private static function addMinPropForOverRuleIssue( $mode, $minimalProps )
	{
		if ($mode == 'contentstation') {
			require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';;
			$overIssues = DBIssue::listAllOverruleIssues();
			if (!empty($overIssues)) {
				$minimalProps = array_merge($minimalProps, array('IssueId'));
			}	
		}

		return $minimalProps;
	}

	// PUBLISH MANAGER SPECIFIC FUNCTIONS.

	/**
	 * Returns table aliases per join property to be used in joins focused on the ObjectRelations table.
	 *
	 * Merges the BizProperty Join Properties with specific field join Properties defined for the
	 * Publish Manager call. The result is a mapping for each of the Properties to the correct table
	 * alias to be used in the Queries.
	 *
	 * @static
	 * @return string[] An array of the properties.
	 */
	static private function getPublishManagerJoinProperties()
	{
		// Build a list of basic join properties.
		$joinProperties = array();
		$joinProperties['PublishedDate'] = 'tar'; // smart_targets.publisheddate.
		$joinProperties['PublishedVersion'] = 'tar'; // smart_targets.publishedmajorversion / minorversion.
		$joinProperties['ParentId'] = 'obr'; // smart_objectrelations.parent.
		$joinProperties['IssueId'] = 'tar'; // smart_targets.issueid.
		$joinProperties['PubChannelId'] = 'tar'; // smart_targets.channelid.
		$joinProperties['EditionId'] = 'ted'; // smart_targeteditions.editionid.

		// Get the default properties from BizProperty.
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$bizPropertyJoinProperties = BizProperty::getJoinProps();

		// Merge the results, ensuring the newly defined values overwrite any existing values and return.
		$joinProperties = array_merge($bizPropertyJoinProperties, $joinProperties);
		return $joinProperties;
	}

	/**
	 * Retrieves a list of Property fields to be used in Joins.
	 *
	 * Takes the BizProperty Join Fields listing and adds additional fields to be used for the PublishManager
	 * named query.
	 *
	 * @static
	 * @return string[] Returns an array of join property field names.
	 */
	static private function getPublishManagerJoinFieldProperties()
	{
		// Build a list of basic join field properties.
		$joinFieldProperties = array();
		$joinFieldProperties['PublishedDate'] = 'publisheddate'; // smart_targets.publisheddate.
		$joinFieldProperties['ParentId'] = 'parent';
		$joinFieldProperties['IssueId'] = 'issueid'; // smart_targets.issueid.
		$joinFieldProperties['PubChannelId'] = 'channelid'; // smart_targets.channelid.
		$joinFieldProperties['EditionId'] = 'editionid'; // smart_targeteditions.editionid.

		// Get the default properties from BizProperty.
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$bizPropertyJoinFieldProperties = BizProperty::getJFldProps();

		// Merge and return the results, ensuring the newly defined values overwrite any existing values.
		return array_merge($bizPropertyJoinFieldProperties, $joinFieldProperties);
	}

	/**
	 * Returns the Join type for the provided alias.
	 *
	 * In some cases we need a different type of join for the properties, depending on the alias it is determined
	 * what type of join is returned. The default join type is a normal (inner) join.
	 *
	 * @static
	 * @param string $alias The table alias for which to determine the join string.
	 * @return string The join string for this type, for example 'LEFT JOIN '.
	 */
	static private function getJoinType($alias)
	{
		switch ($alias) {
			case 'ted':
			case 'lcb':
			case 'lcc':
			case 'cha':
				$type = 'LEFT JOIN ';
				break;
			default :
				$type = 'JOIN ';
				break;
		}
		return $type;
	}

	/**
	 * Retrieves all available Joins as to be used when creating Queries with the ObjectRelationsTable as a base.
	 *
	 * Overrules some of the Joins defined in BizQueryBase::listAvailableJoins with Joins focused on the Object-
	 * Relations table, and adds all the normally available joins that were not overruled to the result set.
	 *
	 * @static
	 * @return string[] An Array of available joins.
	 * @see BizQueryBase::listAvailableJoins
	 */
	static private function getPublishManagerJoins()
	{
		// Construct the joins for the Publish Manager.
		$joins = array();
		$joins['o'] = "smart_objects o ON (obr.`child` = o.`id`)";
		$joins['tar'] = "smart_targets tar ON (obr.`id` = tar.`objectrelationid`)";

		// Get the available joins from BizQueryBase, which are normally for relations through the Objects table.
		$bizQueryBaseJoins = self::listAvailableJoins();

		// Merge and return the Joins making sure that the Publish Manager Joins overwrite any set in BizProperty.
		return array_merge($bizQueryBaseJoins, $joins);
	}

	/**
	 * Returns a complete list of Property names needed for the PublishManager query.
	 *
	 * Takes any requested Property names and joins them with both the minimum required Properties and any properties
	 * that are required but are not defined in the minimum properties.
	 *
	 * @static
	 * @param string[] $requestedPropertyNames An array of the property names.
	 * @return string[] A complete list of Property names needed for the PublishManager query.
	 */
	static public function getPublishManagerPropertyNames($requestedPropertyNames)
	{
		// Construct the extra properties.
		$extraProperties = array(); // Extra Properties not described in the Minimal Props.
		$extraProperties[] = 'Publication'; // smart_publications.publication (pub).
		$extraProperties[] = 'Section'; // smart_publsections.section (sec).
		$extraProperties[] = 'Modified'; // smart_objects.modified (o).
		$extraProperties[] = 'PublishedDate'; // smart_targets.publisheddate. (tar).
		$extraProperties[] = 'PublishedVersion'; // concat smart_targets.publishedmajorversion / MinorVersion (tar).
		$extraProperties[] = 'ParentId'; // smart_objectrelations.parent (obr).
		$extraProperties[] = 'IssueId'; // smart_targets.issueid (tar).
		$extraProperties[] = 'PubChannelId'; // smart_targets.channelid (tar).
		$extraProperties[] = 'EditionId'; // smart_targeteditions.editionid (ted).
		$extraProperties[] = 'LockedBy'; // smart_users.fullname (lcc).

		// Merge the extra property names with the minimal properties start with the minimal props so their order is
		// always the same. Starting with ID, Type and Name...
		$extraProperties = array_merge(self::getMinimalPropertiesForPublishManager(), $extraProperties);

		// Join our extra properties with any requested property names if provided.
		if ( is_array($requestedPropertyNames) ) {
			$extraProperties = array_merge( $extraProperties, $requestedPropertyNames );
		}

		return array_unique($extraProperties); // Return the Unique Property Names.
	}

	/*
	  * Returns the minimal props needed for querying for the PublishManager.
	  *
	  * @return string[] An array of property field names.
	  */
	static public function getMinimalPropertiesForPublishManager()
	{
		return array(
			'ID',
			'Type',
			'Name',
			'State',
			'StateId',
			'StateColor',
			'Created',
			'PublicationId',
		    'SectionId',
		    'DocumentID',
		    'Slugline',
		);
	}

	/**
	 * Validates the PublishManagerQuery.
	 *
	 * Validates the required filter parameters that should be sent along with the request, and in certain cases
	 * validates the contents of the QueryParams.
	 *
	 * @param QueryParam[] $params The parameters that were sent along with the Request.
	 * @throws BizException Throws a BizException when there are params that did not pass validation.
	 * @return bool Whether or not the Parameters pass validation.
	 */
	static private function validatePublishManagerQuery( $params )
	{
		$requiredParams = array('PublishStatus', 'PublicationId', 'Modified');
		$valid = true;

		// Restructure the Params for validation.
		$validationParams = array();
		if ( $params ) foreach ( $params as $param ) {
			$validationParams[$param->Property] = $param;
		}

		// Check that all the required params are present.
		$message = null;
		foreach ( $requiredParams as $paramName ) {
			if (!array_key_exists($paramName, $validationParams)) {
				$message = ( is_null( $message ) )
					? 'Missing parameter(s) provided in the Named Query: ' . $paramName
					: $message . ', ' . $paramName;
				$valid = false;
			}
		}

		// If all required Params are set, validate the PublishStatus field, which should always be present.
		if ($valid && is_null($validationParams['PublishStatus']->Value)) {
			$message = 'Missing parameter PublishStatus provided in the Named Query.';
			$valid = false;
		} else if ($valid ) {
			// value should be one of the predefined allowed values.
			switch ( $validationParams['PublishStatus']->Value ) {
				case self::PUBLISH_MANAGER_FILTER_READY_FOR_PUBLISHING :
				case self::PUBLISH_MANAGER_FILTER_NOT_PUBLISHED :
				case self::PUBLISH_MANAGER_FILTER_PUBLISHED :
				case self::PUBLISH_MANAGER_FILTER_UPDATE_AVAILABLE :
					break;
				default :
					$message = 'Invalid search parameter PublishStatus provided in the Named Query.';
					$valid = false;
					break;
			}
		}

		// Validate the Modified filter field.
		if ($valid && $validationParams['Modified']->Operation != 'starts' && $validationParams['Modified']->Operation != 'within') {
			$message = 'Missing or invalid operation provided for the Modified field in the Named Query.';
			$valid = false;
		}

		// Validate the optional PubChannelIds field.
		if ( $valid && isset( $validationParams['PubChannelIds'] ) ) {
			if ( preg_match("/^[0-9]{1,}[0-9,]{0,}$/", $validationParams['PubChannelIds']->Value) == false ) {
				$message = 'Invalid value provided for the PubChannelIds parameter in the Named Query, only numbers and commas are allowed.';
				$valid = false;
			}

		}

		// Throw the exception message.
		if( !$valid ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', $message );
		}
		return $valid;
	}

	/**
	 * Resolve the PropertyInfos for the PublishManager named query.
	 *
	 * Certain fields are either overruled or not present in BizProperty.class.php, because of this we need to ensure
	 * that the PropertyInfos constructed for the Query are updated with the correct names / types for the Columns.
	 *
	 * @static
	 * @param string[] $rows A list of Column Names.
	 * @return null|PropertyInfo[] An array of PropertyInfo objects or null if none of the Properties could be resolved.
	 */
	static private function doGetColumnsForPublishManagerQuery( $rows )
	{
		// Get the Column listing from the Parent.
		$propertyInfos = self::getColumns( $rows );

		// Fix entries in the Column listing that were not set through the BizProperty Column listing.
		foreach ($propertyInfos as $propertyInfo ) {
			// Fix the Type and the DisplayName of the PublishedDate if it is set in the Column listing.
			if ($propertyInfo->Name == 'PublishedDate') {
				$propertyInfo->DisplayName = BizResources::localize( 'PUBLICATION_DATE' );
				$propertyInfo->Type = 'datetime';
			}

			// Fix the DisplayName for the PublishedVersion Field if it is in the Column listing.
			if ($propertyInfo->Name == 'PublishedVersion') {
				$propertyInfo->DisplayName = BizResources::localize( 'OBJ_PUBLISHED_VERSION' );
			}
		}
		return $propertyInfos;
	}

	/**
	 * Builds the Where Clause including the filter parameters.
	 *
	 * Defines a list of filter fields that should be included in the query. If present it uses them when generating
	 * a where clause.
	 *
	 * @param QueryParam[] $params An array of Query Parameters.
	 * @return string the generated Where clause.
	 */
	static private function buildWhereForPublishManagerParams ($params )
	{
		$objectQueryProperties = array(
			'ID' => true,
			'Type' => true,
			'Name' => true,
			'Search' => true, // The same as name, does a contains on the Name field.
			'CategoryId' => true,
			'Modified' => true,
		);
		// Create a selection of the Object Query Properties.
		$objectQueryParams = array();

		if (isset($params)){
			foreach ($params as $param){
				if (isset($objectQueryProperties[$param->Property])){
					$objectQueryParams[] = $param;
				}
			}
		}
		$where = BizQuery::buildWhere($objectQueryParams);

		// Remove WHERE
		return preg_replace('/^WHERE */', '', $where);

	}

	/**
	 * Generates the Order By part of the Publish Manager named query.
	 *
	 * Takes the raw $queryOrder, cleans them up and prepares them for inclusion in the
	 * Order By for the sql array.
	 *
	 * @static
	 * @param QueryOrder[]|array|null $queryOrder An array of QueryOrder objects, to specify the query sorting.
	 * @param array $sqlArray The array of SQL parts.
	 * @return array The SQL array with the resolved ORDER BY statement.
	 *
	 */
	static public function resolvePublishManagerQueryOrder( $queryOrder, $sqlArray )
	{
		$queryOrder = self::resolveQueryOrderDirection( $queryOrder ); // Ensure the Direction property is a boolean.
		$queryOrder = ( is_null( $queryOrder ) ) ? array() : $queryOrder; // Ensure $queryOrder is an array.
		$sql = '';
		$orderByColumns = array();

		// Take the default Query Order, or expand it if we have QueryOrder objects.
		if ( count( $queryOrder ) >  0) {
			require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
			$joinFieldProperties = self::getPublishManagerJoinFieldProperties();
			$joinProperties = self::getPublishManagerJoinProperties();
			$objectProperties = BizProperty::getMetaDataObjFields();

			foreach ($queryOrder as $column) {
				$direction = ( $column->Direction === false ) ? 'DESC' : 'ASC'; // Determine the direction.

				// Determine the SQL for each of the fields.
				switch ( $column->Property ) {
					case 'Modified' :
					case 'PublishedDate' :
					case 'Name' : // See s
					case 'Slugline' :
						$columnName = ( isset( $joinFieldProperties[$column->Property] ) )
							? $joinFieldProperties[$column->Property]
							: '';
						$columnTable = ( isset( $joinProperties[$column->Property] ) )
							? $joinProperties[$column->Property]
							: '';

						// See if we can resolve the property from the Object table, if they are empty at this stage.
						if ( empty( $columnName ) && empty ( $columnTable ) ) {
							if ( isset( $objectProperties[$column->Property] ) ) {
								$columnTable = 'o';
								$columnName = $objectProperties[$column->Property];
							}
						}

						// If we were able to resolve the property, include it in the sorting options.
						if ( !empty( $columnName ) && !empty( $columnTable ) ) {
							$index = $columnTable . '.`' . $columnName . '`';
							$orderByColumns[$index] = $direction;
						} else {
							// The column could not be resolved correctly, log a message.
							LogHandler::Log('PublishManagerQuery', 'WARN', 'Warning: Requested sort column `'
								. $column->Property . '` could not be resolved, and will not be used for the query results.');
						}
						break;
					case 'PubChannel' :
						// Join in the smart_channels table.
						$availableJoins = self::getPublishManagerJoins();
						$sqlArray['joins'] = $sqlArray['joins'] . self::getJoinType('cha') . $availableJoins['cha'];
						$sqlArray['joins4where'] = $sqlArray['joins4where'] . self::getJoinType('cha') . $availableJoins['cha'];
						// Add both the code and the channel name to the sort options.
						$orderByColumns['cha.`code`'] = $direction;
						$orderByColumns['cha.`name`'] = $direction;
						break;
					default :
						// Unknown columns are invalid, ignore them and log a message.
						LogHandler::Log('PublishManagerQuery', 'INFO', 'Warning: Requested sort column `'
							. $column->Property . '` is not supported, and will not be used for the query results.');
						break;
				}
			}

			if( !empty( $orderByColumns ) ) {
				$sql = self::createOrderByStatement($orderByColumns);
			}
		}

		// If there were no QueryOrder elements defined, or no valid elements were passed, still apply a default sorting.
		$sql = empty($sql) ? self::queryorder2SQL('', '') : $sql;

		$sqlArray['orderby'] = '/*ORDERBY*/ ' . $sql;
		return $sqlArray;
	}

	// End of Publish Manager specific functions.
}