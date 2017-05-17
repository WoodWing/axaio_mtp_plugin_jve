<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 **/
require_once BASEDIR . "/server/dbclasses/DBBase.class.php";
class DBQuery extends DBBase
{
	private static $RegViews;

	/**
	 * After the views have been used they must be dropped...
	 */
	static public function dropRegisteredViews()
	{
		$dbdriver = DBDriverFactory::gen();

		if (is_array(self::$RegViews)) {
			//Views must be dropped in the opposite order as they were created... ->
			//Not necessary when dropped in one statement...
			$reversed_regviews = array_reverse(self::$RegViews);
			$dbdriver->dropViews($reversed_regviews);
			self::$RegViews = array(); // clear view cache since we have dropped them
		}

		DBBase::clearTempTables();
	}

	/**
	 * Registers a view with the purpose of dropping it when finished.
	 * @param string $viewname Name of the view to register.
	 * When empty no view is added, but it is still valid to get the array of views.
	 * @return array of registered views.
	 */
	static private function registerView($viewname = '')
	{
		if (!isset(self::$RegViews)) {
			self::$RegViews = array();
		}
		if (!empty($viewname)) {
			if (!in_array($viewname, self::$RegViews)) {
				self::$RegViews[] = $viewname;
			}
		}
		return self::$RegViews;
	}

	static public function getObjectRow($objectid, $sqlarray)
	{
		$dbdriver = DBDriverFactory::gen();
		
		$sql  = $sqlarray['select'];
		$sql .= $sqlarray['from'];
		$sql .= $sqlarray['joins'];
		$sql .= " WHERE o.`id` = $objectid ";

		$sth  = $dbdriver->query($sql);
		$row  = $dbdriver->fetch($sth);
		return $row;
	}
	
	
	/**
	 * Get all rows as defined in the sql array. The sql array contains statements
	 * for the different sections of a sql statement. The sql array is normally
	 * used within BizQuery and BizNamedQuery. You can pass $keycolumn to indicate 
	 * which values of the select must be used as array keys. 
	 * @param array $sqlarray. Array containing the different sections of a sql statement.
	 * @param string $keyColumn. The column which values will be used as array keys.
	 * @return array $rows. Array with the fetched table rows.
	 */
	static public function getRows($sqlarray, $keyColumn)
	{
		$dbdriver = DBDriverFactory::gen();
		
		$sql  = $sqlarray['select'];
		$sql .= $sqlarray['from'];
		$sql .= $sqlarray['joins'];
		$sql .= $sqlarray['where'];

		$sth  = $dbdriver->query($sql);
		$rows = self::fetchResults($sth, $keyColumn);
		
		return $rows;
	}

	/**
	 * Executes a 'normal' namedQuery on the smart_objects table specified by $sqlarray.
	 * The query is joined with the smart_authorizedobjects_view to only return the objects
	 * which the user is authorized for when checkaccess is true
	 *
	 * @param array $sqlarray List of the different supported parts of an sql-statement.
	 * @param bool $checkAccess Whether or not to check if user has view access for objects (= more expensive)
	 * @return int Number of objects found.
	 */
	static public function getTopCount($sqlarray, $checkAccess)
	{		
		$dbdriver = DBDriverFactory::gen();
		$tempaov = '';
		if($checkAccess) {
			$tempaov = self::getTempIds('aov');
		}
		
		$sql  = "SELECT COUNT(DISTINCT o.`id`) AS c ";
		$sql .= $sqlarray['from'];
		$sql .= isset($sqlarray['joins4where']) ? $sqlarray['joins4where'] : $sqlarray['joins'];
		if($checkAccess) {
			$sql .= " INNER JOIN $tempaov aov ON (aov.`id` = o.`id`) ";
		}
		$sql .= $sqlarray['where'];

		$sth  = $dbdriver->query($sql);
		$rows = self::fetchResults($sth);
		$count = 0;
		if (isset($rows[0]) && isset($rows[0]['c'])){
			$count = $rows[0]['c'];
		}
		
		return $count;
	}
	
	/**
	 * Returns the SELECT and ORDER BY columns for createTopView
	 * @see createTopView
	 * 
	 * MS SQL requires to select all ORDER BY columns and if SELECT DISTINCT selects a joined
	 * table, it's possible to get duplicate o.`id` rows (BZ#16880). To solve this, GROUP BY is used with aggregate fuction MIN
	 * because MS SQL requires to use an aggregate function with GROUP BY if more is selected than the GROUP BY columns
	 * 
	 * We use the MS SQL rules for all database flavors to get the same results on all and so improve quality.
	 *
	 * @param array $selectColumns
	 * @param array $orderByColumns
	 * @param string $orderBy
	 * @param int $limitCount
	 */
	static protected function getSelectAndOrderByColumns(array &$selectColumns, array &$orderByColumns, $orderBy, $limitCount)
	{
		// set default values
		$selectColumns = array("o.`id`");
		$orderByColumns = array();
				
		if ($limitCount > 0) { //$limitCount means use limitquery()
			$orderbyfields = array();
			// match column and order (DESC or ASC/empty)
			preg_match_all('/([A-z]*\.[^ ]*) +(desc)?/i', $orderBy, $orderbyfields);
			// filter out order by fields
			$count = count($orderbyfields[1]);
			if ($count > 0) {
				$orderByColumns = array();
				for ($i = 0; $i < $count; $i++) {
					$orderbyfield = $orderbyfields[1][$i];
					$order = stripos($orderbyfields[2][$i], 'desc') === 0 ? ' DESC' : ' ASC';
					if ($orderbyfield !== 'o.`id`') {
						// Fields used for ordering must added to select otherwise
						// limitquery will fail.
						$columnName = $orderbyfield;
						
						$matches = array();				
						// Check if the columnName is in the following format: <alias>.`<column>`
						// Resolves: BZ#16661	
						if(preg_match('/^([a-zA-Z0-9]+)\.`([a-zA-Z0-9]+)`/', $columnName, $matches)) {
							// It is in the format, so create a new column name as: `<alias>_<column>`
							$newAlias = $matches[1] . '_' . $matches[2];
							$columnName = '`' . $newAlias . '`';	
						} else {
							// if not found remove everything untill "." e.g. iss.`name` becomes `name`
							$columnName = preg_replace('/^.+\./', '', $columnName);
						}						 
						
						// BZ#16880 use aggregate function for GROUP BY
						$selectColumns[] = "MIN($orderbyfield) AS $columnName";
						$orderByColumns[] = $columnName . $order;
					} else {
						$orderByColumns[] = $orderbyfield . $order;
					}
				}
			}
		}
	}

	/**
	 * Creates a view named smart_top_view on the smart_objects table specified by $sqlarray.
	 * The query is joined with the smart_authorizedobjects_view to only return the id's of the objects
	 * which the user is authorized for when checkaccess is true, else return all
	 * This view contains object-id's only.
	 *
	 * @param array $sqlarray List of the different supported parts of an sql-statement.
	 * @param int $totalcount parameter which receives the total count of topobjects which satisy the where.
	 * @param int $limitstart where to start LIMIT.
	 * @param int $limitcount count of LIMIT.
	 * @param bool $checkAccess Whether or not to check if user has view access for objects (= more expensive)
	 * @return string
	 */
	static public function createTopView($sqlarray, &$totalcount, $limitstart = 0, $limitcount = DBMAXQUERY, $checkAccess = true)
	{
		$totalcount = self::getTopCount($sqlarray, $checkAccess);

		$dbdriver = DBDriverFactory::gen();
		$tempaov = '';
		if($checkAccess) {
			$tempaov = self::getTempIds('aov');
		}
		$temptvi = self::getTempIds('tvi');

		// we need to use GROUP BY (not DISTINCT, see BZ#16880) here, because a named query can
		// return the same object ids and that will cause the insert
		// into the temp table to fail
		$selectColumns = array();
		$orderByColumns = array();
		self::getSelectAndOrderByColumns($selectColumns, $orderByColumns, $sqlarray['orderby'], $limitcount);

		$sql = 'SELECT ' . implode(', ', $selectColumns);
		$sql .= $sqlarray['from'];
		$sql .= isset($sqlarray['joins4where']) ? $sqlarray['joins4where'] : $sqlarray['joins'];
		if($checkAccess) {
			$sql .= " INNER JOIN $tempaov aov ON (aov.`id` = o.`id`) ";
		}
		$sql .= $sqlarray['where'];
		// BZ#16880 always use GROUP BY
		$sql .= ' GROUP BY o.`id`';
		
		if ($limitcount > 0) { // Zero means 'show all'
			// only need to order by when we have a limit
			if (count($orderByColumns) > 0) {
				$sql .= ' ORDER BY ' . implode(', ', $orderByColumns);
			}
			$sql = self::filterTagsFromSQL($sql); //Comment tags confuses find/replace mechanisme of limitquery()
			$sql = $dbdriver->limitquery($sql, $limitstart, $limitcount);
		}
				
		$sth = $dbdriver->query($sql);
		$rows = DBQuery::fetchResults($sth, null, true);

		if (count($rows) > 0) {
			// Insert id's into temporary table
			$insertsql = $dbdriver->composeMultiInsertTempTable($temptvi, $rows);
			$dbdriver->query($insertsql);
		}
		
		return "tvi";
	}	
	
	/**
	 * Creates a topview with the ids of th passed rows. So checking on access 
	 * rights is already done.
	 *
	 * @param array $rows contains the object ids
	 * @return string identifier
	 */
	static public function createTopviewWithRowIDs($rows)
	{
		$dbdriver = DBDriverFactory::gen();
		$temptvi = self::getTempIds('tvi');
		
		if (count($rows) > 0) {
			// Insert id's into temporary table
			$insertsql = $dbdriver->composeMultiInsertTempTable($temptvi, $rows);
			$dbdriver->query($insertsql);
		}
		
		return "tvi";		
	}

	/**
	 * Filters out special tags from a sql-statement string. Tags are keywords used within comments.
	 *
	 * @param string $sql string containing special tags (e.g. sql keywords used as comment)
	 * @return string statement without special tags
	 */
	static private function filterTagsFromSQL($sql)
	{
		$tags = array('/*SELECT*/', '/*FROM*/', '/*WHERE*/', '/*ORDERBY*/', '/*JOINS4WHERE*/');
		$resultSQL = str_replace($tags, '', $sql);

		return $resultSQL;
	}
		
	/**
	 * Queries the smart_objects table specified by $sqlarray.
	 * The query is joined with the smart_top_view in which only returns the objects found with the 'where' clause.
	 *
	 * @since v9.0.0
	 * When the Publish Manager is introduced, the query does not only involve smart_objects table but also
	 * smart_objectrelations table. Thus, to ensure that the correct/intended fields are retrieved, $where
	 * parameter is introduced to filter further on the query. This parameter($where) is however not used by
	 * default unless specified by the caller.
	 *
	 * @param array $sqlarray List of the different supported parts of an sql-statement.
	 * @param bool $where Whether to use 'where' clause in the query. By default is False(not used).
	 * @return array List of rows, containing all the selected fields.
	 * @throws BizException
	 */
	static public function getTopObjects( $sqlarray, $where=false )
	{
		$dbdriver = DBDriverFactory::gen();
		$temptvi = self::getTempIds('tvi');

		$sql  = $sqlarray['select'];
		$sql .= $sqlarray['from'];
		$sql .= $sqlarray['joins'];
		$sql .= " INNER JOIN $temptvi tvi ON (tvi.`id` = o.`id`) ";
		if( $where ) {
			$sql .= $sqlarray['where'];
		}
		$sql .= $sqlarray['orderby'];
		
		$sth = $dbdriver->query($sql);
		if ( !$sth ) {
			// In some exceptional cases the query fails. We need to error in this case. Otherwise
			// an empty array is send back to the user.
			// This happens when for example a QueryObjects is executed and a custom
			// property isn't defined.
			// When the query doesn't return any rows, the $sth parameter is actually set
			// with an empty resultset. Therefore we can check this way.
			// This solves BZ#28864 .
			throw new BizException('ERR_SYSTEM_PROBLEM', 'server', $dbdriver->error());
		}
		return self::fetchResults($sth, 'ID');
	}

	/**
	 * Adds all childen objects to the temporary table. Depending on $checkAccess
	 * the authorization of the child objects is checked. This done by the intersection
	 * of the child objects and the objects stored in the 'authorization view'.
	 * @param array $childRows contains all children related to the parents (topview)
	 * @param boolean $checkAccess indicates if the access rights of the child
	 * objects have to be checked.
	 * @return string Identifier of the temporary table.
	 */
	static public function createAllChildrenView($childRows, $checkAccess = true)
	{
		$dbdriver = DBDriverFactory::gen();
		$authChildView = self::getTempIds('cv9');

		if ($checkAccess) {
			$authorizedChildren = DBQuery::getObjectsFromAuthorizationView($childRows);
		}
		else {
			$authorizedChildren = $childRows;
		}
		
		if (!empty($authorizedChildren)) {
			$sql = $dbdriver->composeMultiInsertTempTable($authChildView, $authorizedChildren);
			/*$sth =*/ $dbdriver->query($sql);
		} 
		return "cv9";
	}

	/**
	 * Adds all childen objects, with a limit number of placements, to the temporary table.
	 * This view can be used to resolve placed on information. Access rights are already
	 * checked for the passed child id's.
	 * @param array $allChildrenIds contains all children related to the parents (topview)
	 * @return string Identifier of the temporary table.
	 */
	static public function createLimitPlacedChildrenView( $allChildrenIds )
	{
		$dbdriver = DBDriverFactory::gen();
		$limitchildids = self::getTempIds('cv10');
		$objrel = $dbdriver->tablename('objectrelations'); 
		
		if ( $allChildrenIds ) {
			$inClause = implode( ',', $allChildrenIds );
			$sql  = "INSERT INTO $limitchildids ";
			$sql .= 	"SELECT `child` ";
			$sql .= 	"FROM $objrel ";
			$sql .= 	"WHERE `child` IN ($inClause) ";
			$sql .= 	"AND `type` <> 'Related'";
			$sql .= 	"GROUP BY `child` ";
			$sql .= 	"HAVING COUNT(1) < 50 ";
			/*$sth = */$dbdriver->query($sql);
		}	
		return "cv10";	
	}
	
	/**
	 * Adds all top level objects, with a limit number of relations, to the temporary table.
	 * This is all about top level objects which pop up as child objects of other top level objects.
	 * Limit means less than 50 relations or no relations at all.
	 * This view can be used to resolve placed on information. Access rights are already
	 * checked for the objects on the top level.
	 * @param string $topview Identifier of the table containing the id's of the top level objects.
	 * @return string Identifier of the temporary table.
	 */	
	static public function createLimitTopView( $topview )
	{
		$dbdriver = DBDriverFactory::gen();
		$limittopids = self::getTempIds('cv12');
		$topids = self::getTempIds($topview);
		$objrel = $dbdriver->tablename('objectrelations'); 
		
		// Less than 50 relations
		$sql  = "INSERT INTO $limittopids ";
		$sql .= 	"SELECT `child` ";
		$sql .= 	"FROM $objrel objrel ";
		$sql .= 	"INNER JOIN $topids t ON (t.`id` = objrel.`child` ) ";
		$sql .= 	"GROUP BY `child` ";
		$sql .= 	"HAVING COUNT(1) < 50 ";
		/*$sth = */$dbdriver->query($sql);
	
		// No relations
		$sql  = "INSERT INTO $limittopids ";
		$sql .= 	"SELECT t.`id` ";
		$sql .= 	"FROM $topids t ";
		$sql .= 	"LEFT JOIN $objrel objrel ON (t.`id` = objrel.`child` ) ";
		$sql .= 	"WHERE objrel.`child` IS NULL ";
		/*$sth = */$dbdriver->query($sql);

		return "cv12";	
	}	

	/**
	 * Adds child objects, with a lot of placements, to the temporary table.
	 * This view can be used to resolve placed on information. Access rights are already
	 * checked for the objects on the top level.
	 * @param string $allchildrenview Table containing all children id's
	 * @param string $limitchildrenview Table containing children with a limit number of placements. 
	 * @return string Identifier of the temporary table.
	 */		
	static public function createMultiPlacedChildrenView( $allchildrenview, $limitchildrenview)
	{
		$dbdriver = DBDriverFactory::gen();
		$specialchildids = self::getTempIds('cv11');
		$allchildrenids = self::getTempIds($allchildrenview);
		$limitchildrenids = self::getTempIds($limitchildrenview);
		
		$sql  = "INSERT INTO $specialchildids ";
		$sql .= 	"SELECT allch.`id` ";
		$sql .= 	"FROM $allchildrenids allch ";
		$sql .= 	"WHERE NOT EXISTS ( ";
		$sql .=			"SELECT restch.`id` ";
		$sql .=			"FROM $limitchildrenids restch ";
		$sql .=			"WHERE restch.`id` = allch.`id` ) ";
		/*$sth = */$dbdriver->query($sql);
			
		return "cv11";	
	}

	/**
	 * Returns all children of a the parents stored in 'topview'. Also the children of the
	 * children are returned. Nesting is four deep. Children are returned only once.
	 * Note: this method has close resemblance to getAllChildrenForParent().
	 * 
	 * @param string $topview Identifier to the table containing the parents.
	 * @param bool $deletedobjects
	 * @return Array with children ids.
	 */
	static public function getAllChildren($topview,$deletedobjects=false)
	{
		$dbdriver = DBDriverFactory::gen();
		$parentids = self::getTempIds($topview);

		$dbo = $deletedobjects ?  'smart_deletedobjects' : 'smart_objects';
		$sql  =  self::getSQLRelatedChildsJoins( $dbo );
		$sql .= "INNER JOIN $parentids ON ($parentids.`id` = parent.`id`) ";
		$sql .= "WHERE    child01.`child` <> parent.`id` ";

		$sth = $dbdriver->query($sql);
		$rows = self::fetchResults($sth);
		
		$childsIds = array();
		foreach ($rows as $row) {
			if ( $row['child01'] != null) {
				$childsIds[$row['child01']] =  array('id' => $row['child01']);
			}  
			if ( $row['child02'] != null) {
				$childsIds[$row['child02']] =  array('id' => $row['child02']);
			}  
			if ( $row['child03'] != null) {
				$childsIds[$row['child03']] =  array('id' => $row['child03']);
			}  
			if ( $row['child04'] != null) {
				$childsIds[$row['child04']] =  array('id' => $row['child04']);
			}  
		}

		return $childsIds;
	}

	/**
	 * Creates and executes an SQL statement based on the SQL array components.
	 *
	 * @static
	 * @param $sqlarray
	 * @return mixed
	 */
	static public function getRowsBySqlArray($sqlarray)
	{
		$dbdriver = DBDriverFactory::gen();
		$sql = self::getBasicCriteriaFromSqlArray($sqlarray);
		$sth = $dbdriver->query($sql);
		$rows = DBQuery::fetchResults($sth, null, true);
		return $rows;
	}

	/**
	 * Creates a basic sql statement from the supplied SQL array.
	 *
	 * @todo add a switch to add the other join types.
	 * @static
	 * @param $sqlarray
	 * @return string
	 */
	static public function getBasicCriteriaFromSqlArray($sqlarray){
		$sql = $sqlarray['select'];
		$sql .= $sqlarray['from'];
		$sql .= $sqlarray['joins'];
		$sql .= $sqlarray['where'];
		$sql .= $sqlarray['orderby'];
		return $sql;
	}

	/**
	 * Gets the relations of a child object and returns the object ids of the parent objects.
	 *
	 * @param integer $childId The child object to retrieve the parents of.
	 * @param bool $deletedrelations Take relations into account that are marked as deleted.
	 * @return array The Ids of the parent objects
	 * @throws BizException
	 */
	static public function getAllParentsForChild( $childId, $deletedrelations = false )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectRelation.class.php';
		$relations = DBObjectRelation::getObjectRelations( $childId, 'parents', null, $deletedrelations );
		$parendIds = array();
		if( $relations ) foreach( $relations as $relation ) {
			$parendIds[$relation['parent']] = intval( $relation['parent'] );
		}

		return $parendIds;
	}

	/**
	 * Returns all children of a the parents stored in 'topview'. Also the children of the
	 * children are returned. Nesting is four deep. Children are returned only once.
	 * Note: this method has close resemblance to getAllChildren().
	 *
	 * @param integer $parentId Id of the parent object to retrieve the children of.
	 * @param bool $deletedobjects Search for deleted objects.
	 * @return Array with children ids.
	 */
	static public function getAllChildrenForParent( $parentId, $deletedobjects=false)
	{
		$dbdriver = DBDriverFactory::gen();

		$dbo = $deletedobjects ?  'smart_deletedobjects' : 'smart_objects';
		$sql  =  self::getSQLRelatedChildsJoins( $dbo );
		$sql .= "WHERE  parent.`id` = $parentId ";
		$sql .= "AND    child01.`child` <> parent.`id` ";

		$sth = $dbdriver->query($sql);
		$rows = self::fetchResults($sth);

		$childsIds = array();
		foreach ($rows as $row) {
			if ( $row['child01'] != null) {
				$childsIds[$row['child01']] = $row['child01'];
			}
			if ( $row['child02'] != null) {
				$childsIds[$row['child02']] =  $row['child02'];
			}
			if ( $row['child03'] != null) {
				$childsIds[$row['child03']] =  $row['child03'];
			}
			if ( $row['child04'] != null) {
				$childsIds[$row['child04']] =  $row['child04'];
			}
		}

		return $childsIds;
	}

	/**
	 * Returns sql statement to query on children of a parent. Takes care of the children
	 * of the children (four deep).
	 * Depending on the area the objects are retrieved either from smart_objects
	 * or smart_deletedobjects.
	 *
	 * @param string $dbo main table, either smart_objects or smart_deletedobjects
	 * @return string sql-statement
	 */
	static private function getSQLRelatedChildsJoins( $dbo )
	{
		$sql  = "SELECT	child01.`child` as `child01` , child02.`child` as `child02`, ";
		$sql .=	"child03.`child` as `child03`, child04.`child` as `child04` ";
		$sql .= "FROM `" . $dbo . "` parent ";
		$sql .= "LEFT JOIN 	`smart_objectrelations` child01 ON (child01.`parent` = parent.`id` AND child01.`type` <> 'Related') ";
		$sql .= "LEFT JOIN `smart_objectrelations` child02 ON (child02.`parent` = child01.`child` AND    child02.`type` <> 'Related') ";
		$sql .= "LEFT JOIN `smart_objectrelations` child03 ON (child03.`parent` = child02.`child` AND    child03.`type` <> 'Related') ";
		$sql .= "LEFT JOIN `smart_objectrelations` child04 ON (child04.`parent` = child03.`child` AND    child04.`type` <> 'Related') ";

		return $sql;
	}

	/**
	 * Returns the object ids already in the authorization view.
	 *
	 * @param array with the object ids as key.
	 * @returns array rows with object ids already in the authorization view.
	 */
	static public function getObjectsFromAuthorizationView($objectIds)
	{
		$result = array();
		
		if (empty($objectIds)) {
			return $result;
		}
		
		$dbdriver = DBDriverFactory::gen();
		$tempaov = self::getTempIds('aov');
		
		$flatObjectsIds = array_keys($objectIds);
		
		$objectIdsConcat = implode(',', $flatObjectsIds);
		
		$sql = "select aov.`id` ";
		$sql .= "from $tempaov aov ";
		$sql .= "where aov.`id` in ($objectIdsConcat) ";
		
		$sth = $dbdriver->query($sql);
		$result = self::fetchResults($sth, 'id');	
		
		return $result;
	}
		
	/**
	 * Queries the smart_objects table specified by $sqlarray.
	 * The query is joined with the smart_allchildren_view to only return the objects found in that view.
	 * One field named smart_parentid is added to the select to get the parentid. This propertyname is protected
	 * and may not be used in any other part of the query.
	 *
	 * @param array $sqlarray
	 * @return array List of rows, containing all selected fields.
	 */
	static public function getAllChildrenObjects($sqlarray)
	{
		$dbdriver = DBDriverFactory::gen();
		$allchildrenview = self::getTempIds('cv9');

		$sql  = $sqlarray['select'];
		$sql .= $sqlarray['from'];
		$sql .= $sqlarray['joins'];
		$sql .= " INNER JOIN $allchildrenview cv9 ON (cv9.`id` = o.`id`) ";

		$sth = $dbdriver->query($sql);
		
		return self::fetchResults($sth, 'ID');
		
	}

	/**
	 * Getting the Objects that have 'Placement' type relationship.
	 * $deletedObjects determines whether to get the currently 'Deleted' placement (Deleted by user but still laying in the Enterprise 'Trash' Area) or the 'Non-Deleted' placement(laying in 'Workflow' area)
	 *
	 * @param string $viewid
	 * @param bool $deletedObjects
	 * @return array $results
	 */
	static public function getPlacedOnRowsByView($viewid, $deletedObjects)
	{
		$dbdriver = DBDriverFactory::gen();
		$tempids = self::getTempIds($viewid);

		$objectstable = $deletedObjects ? $dbdriver->tablename('deletedobjects') : $dbdriver->tablename('objects');
		$objectrelationstable = $dbdriver->tablename('objectrelations');
		$sql  = "SELECT rel.`child`, po.`name`, rel.`pagerange` ";
		$sql .= "FROM $tempids v ";
		$sql .= "INNER JOIN $objectrelationstable rel ON (v.`id` = rel.`child` AND rel.`type` = 'Placed') ";
		$sql .= "INNER JOIN $objectstable po ON (po.`id` = rel.`parent`) ";
	
		$sth = $dbdriver->query($sql);
		$rows = self::fetchResults($sth);

		$results = array();
		foreach ($rows as $row) {
			if (!isset($results[$row['child']])) {
				$results[$row['child']] = array();
				$results[$row['child']]['PlacedOn'] = $row['name'];
				$results[$row['child']]['PlacedOnPage'] = $row['pagerange'];
			}
			else {
				$results[$row['child']]['PlacedOn'] .= ", " . $row['name'];
				$results[$row['child']]['PlacedOnPage'] .= ", " . $row['pagerange'];
			}
		}
		return $results;
	}

	/**
	 * Getting the parents object.
	 * $deletedobjects indicates whether to get the parents from the Workflow or Trash(Parent Object already get deleted by user but still laying in Enterprise) area.
	 *
	 * @param string $viewid
	 * @param bool $deletedobjects
	 * @return array $results
	 */
	static public function getParentsByView($viewid, $deletedobjects=false)
	{
		$dbdriver = DBDriverFactory::gen();
		$tempids = self::getTempIds($viewid);

		$objectstable = $deletedobjects ? $dbdriver->tablename('deletedobjects') : $dbdriver->tablename('objects');
		$objectrelationstable = $dbdriver->tablename('objectrelations');

		$sql  = "SELECT DISTINCT rel.`child`, rel.`parent`, po.`name` ";
		$sql .= "FROM $objectrelationstable rel ";
		$sql .= "INNER JOIN $objectstable po ON (po.`id` = rel.`parent`) ";
		$sql .= "INNER JOIN $tempids v ON (v.`id` = rel.`child`) ";
		$sql .= "WHERE rel.`type` <> 'Related' "; // BZ#13672 Exclude brother-sister relations
		$sth = $dbdriver->query($sql);
		$rows = self::fetchResults($sth);

		$results = array();
		foreach ($rows as $row) {
			if (!array_key_exists($row['child'],$results)) {
				$results[$row['child']] = array();
			}
			$results[$row['child']][] = array('id' => $row['parent'], 'name' => $row['name']);
		}
		return $results;
	}
	
	/**
	 * Returns the parent names of children in the temporary table passed by $multiPlacedChildrenView.
	 * This temporary table contains only children with a great number of placements. For this children only the parents
	 * are resolved that are returned on top level. Resolving all parents will have serious drawback on performance.
	 * Children with a lot of relations are, for example, database images used as logo on many layouts. 
	 *  
	 * @param string $multiPlacedChildrenView Subset of all children (only the one with many relations).
	 * @param string $topview View on objects returned on top level
	 * @return array Parent(names) per child.
	 */
	static public function getParentsOfMultiPlacedChildren( $multiPlacedChildrenView, $topview )
	{
		$dbdriver = DBDriverFactory::gen();
		$tempids = self::getTempIds( $multiPlacedChildrenView );
		$topids = self::getTempIds( $topview );

		$objectstable = $dbdriver->tablename( 'objects' );
		$objectrelationstable = $dbdriver->tablename( 'objectrelations' );

		$sql  = "SELECT rel.`child`, rel.`parent`, po.`name` ";
		$sql .= "FROM $topids t ";
		$sql .= "INNER JOIN $objectrelationstable rel ON (t.`id` = rel.`parent`) ";
		$sql .= "INNER JOIN $objectstable po ON (t.`id` = po.`id`) ";
		$sql .= "INNER JOIN $tempids v ON (v.`id` = rel.`child`) ";
		$sql .= "WHERE rel.`type` <> 'Related' "; // BZ#13672 Exclude brother-sister relations

		$sql = $dbdriver->setJoinOrder($sql);
		$sth = $dbdriver->query( $sql );
		$rows = self::fetchResults( $sth );

		$results = array();
		foreach ( $rows as $row ) {
			if ( !array_key_exists( $row['child'], $results ) ) {
				$results[$row['child']] = array();
			}
			$results[$row['child']][] = array('id' => $row['parent'], 'name' => $row['name']);
		}
		return $results;
	}

	/**
	 * Queries the smart_elements table to return all elements which have objid's in the given view $objectviewname.
	 *
	 * @param string $viewid name of the view for which to get the elements for, this view must
	 * at least contain an id-field with the objectid.
	 * @return array of rows, containing elements.
	 */
	static public function getElementsByView( $viewid )
	{
		$dbdriver = DBDriverFactory::gen();
		$tempids = self::getTempIds( $viewid );
		$elementstable = $dbdriver->tablename( 'elements' );
		$placementstable = $dbdriver->tablename( 'placements' );
		$editionstable = $dbdriver->tablename( 'editions' );

		$sql = 'SELECT ';
		$sql .= 'e.`guid` as "IDC", ';
		$sql .= 'e.`id` as "Id", ';
		$sql .= 'e.`name` as "Name", ';
		$sql .= 'e.`lengthwords` as "LengthWords", ';
		$sql .= 'e.`lengthchars` as "LengthChars", ';
		$sql .= 'e.`lengthparas` as "LengthParas", ';
		$sql .= 'e.`lengthlines` as "LengthLines", ';
		$sql .= 'e.`snippet` as "Snippet", ';
		$sql .= 'e.`version` as "Version", ';
		$sql .= 'e.`objid` as "Parent" ';
		$sql .= "FROM $elementstable e ";
		$sql .= "INNER JOIN $tempids ov ON (ov.`id` = e.`objid`) ";
		$sql .= 'ORDER BY e.`id` ASC ';
		$sth = $dbdriver->query( $sql );

		$rows = array();
		while( ( $row = $dbdriver->fetch( $sth ) ) ) {
			$id = $row['Id'];
			unset( $row['Id'] );
			$rows[ $id ] = $row;
			// BZ#14493 Page is necessary for the "Placed" icon next to an element
			// in the query panel
			$rows[ $id ]['Page'] = '';
			// default value for Editions
			$rows[ $id ]['Editions'] = '';
		}

		// select all element ids with a placement
		// this is a lot faster then a "LEFT JOIN smart_placements" in the previous SQL
		$sql = 'SELECT DISTINCT e.`id` as "Id"'
			.' FROM smart_elements e'
			.' INNER JOIN '.$tempids.' ov ON (ov.`id` = e.`objid`)'
			.' INNER JOIN smart_placements pl ON (pl.`elementid` = e.`guid`)'
			." WHERE e.`guid` != '' ";
		$sth = $dbdriver->query( $sql );
		while( ( $plRow = $dbdriver->fetch( $sth ) ) ) {
			// the value of Page isn't used, it only has to indicate if an element has been placed
			$rows[ $plRow['Id'] ]['Page'] = 1;
		}

		$sql = "SELECT e.`id`, pl.`edition`, edi.`name` ";
		$sql .= "FROM $placementstable pl ";
		$sql .= "INNER JOIN $elementstable e ON (pl.`elementid` = e.`guid`) ";
		$sql .= "INNER JOIN $editionstable edi ON (pl.`edition` = edi.`id`) ";
		$sql .= "INNER JOIN $tempids ov ON (e.`objid` = ov.`id`) ";
		$sql .= "WHERE e.`guid` != '' ";
		$sth = $dbdriver->query( $sql );

		while( ( $editionrow = $dbdriver->fetch( $sth ) ) ) {
			$id = $editionrow['id'];
			if( $rows[ $id ]['Editions'] == '' ) {
				$rows[ $id ]['Editions'] = $editionrow['name'];
			} else {
				$rows[ $id ]['Editions'] .= ", ".$editionrow['name'];
			}
		}
		return $rows;
	}

	/**
	 * Gets the named query named $queryname from the database.
	 * @param string $queryname
	 * @return array row containing the namedquery with $queryname
	 */
	static public function getNamedQueryByName($queryname)
	{
		$row = self::getRow("namedqueries", "`query` = '$queryname' ");
		return $row;
	}

	/**
	 * Creates a temporary table named 'temp_av' with all authorizations given to $shortusername with View-rights
	 * (via normal workflow statuses and via publication admin rights).
	 *
	 * @param string $shortusername short name of the user to get the authorizations for
	 * @param int $accessRight Database id of the access right for the objects, 1 = View, 2 = Read,
	 * List in Publication Overview = 11, 0 = Skip
	 * @param bool $brandAdmin True if the user is administrator of the brand.
	 * @return string temporary table name
	 */
	static public function createAuthorizationsView( $shortusername, $accessRight, &$brandAdmin )
	{
		if (isset(self::$TempIdsTables['av']) && !empty(self::$TempIdsTables['av'])) {
			return self::getTempIds('av'); //Already created See BZ#17870    
		}
		
 		$dbdriver = DBDriverFactory::gen();
		$tempav = self::getTempIds('av');
		$authorizationstable = $dbdriver->tablename('authorizations');
		
		// Publication admin rights
		$authPublAdmin = DBUser::getListBrandsByPubAdmin($shortusername);
		$handled = array();	// contains brands user has full authorization	
		foreach ($authPublAdmin as $authPublAdminRow) {
			$brandAdmin = true;
			$publication = intval($authPublAdminRow['publication']);
			$sql =  "INSERT INTO $tempav ";
			$sql .= "SELECT DISTINCT a.`publication`, a.`issue`, 0, 0 ";//BZ#35240 Brand admin user is also entitled to
			$sql .= "FROM $authorizationstable a ";						//'overrule brand' issues.
			$sql .= "WHERE a.`publication` = $publication ";
			$dbdriver->query($sql);
			$handled[$publication] = $publication;
		}
		
		$skipPublications = '';
		if (!empty($handled)) {
			$skipPublications = implode(',', $handled);
		}
		
		$shortusername = $dbdriver->toDBString($shortusername);
		$userstable = $dbdriver->tablename('users');
		$usergrouptable = $dbdriver->tablename('usrgrp');
		$profilefeaturestable = $dbdriver->tablename('profilefeatures');

		// Workflow rights
		$sql  = "INSERT INTO $tempav ";
		$sql .= "SELECT DISTINCT `publication`, `issue`, `section`, `state` ";
		$sql .= "FROM $userstable u, $usergrouptable ug, $authorizationstable a ";
		$sql .= "LEFT JOIN $profilefeaturestable pf on pf.`profile` = a.`profile` ";
		$sql .= "WHERE u.`user` = '$shortusername' ";
		if( $accessRight > 0 ) {
			$sql .= "AND pf.`feature` = $accessRight ";
		}

		$sql .= "AND u.`id` = ug.`usrid` AND ug.`grpid` = a.`grpid` ";
		if (!empty($skipPublications)) {
			$sql .= "AND a.`publication` NOT IN ($skipPublications) ";
		}
		
		$dbdriver->query($sql);

		return $tempav;
	}

	/**
	 * Check if we can optimize the Authorized Objects View by removing some
	 * checks for section and state. Returns the SQL clause for validating the
	 * section and state.
	 * 
	 * section = 0 on all publications/issues: user has rights on all sections
	 * section never 0 on all publications/issues: user has only rights on some sections
	 * state = 0 on all publications/issues: user has rights on all states except personal state(-1)
	 * state never 0 on all publications/issues: user has rights only some states
	 *
	 * @param string $tempav
	 * @param boolean $brandAdmin User is brand admin.
	 * @return string with SQL starting with "AND" or empty
	 */
	protected static function getAOVSectionStateSQLClause($tempav, $brandAdmin)
	{
		// include checks by default
		$removeState0Check = false;
		$removeStateCheck = false;
		$removeSection0Check = false;
		$removeSectionCheck = false;
		
		$dbdriver = DBDriverFactory::gen();
		$sth = $dbdriver->query("SELECT * FROM $tempav");
		$rows = self::fetchResults($sth);
		if (count($rows) > 0){
			// rights found, remove checks
			$removeState0Check = true;
			$removeStateCheck = true;
			$removeSection0Check = true;
			$removeSectionCheck = true;
			// add checks again depending on the user rights
			foreach ($rows as $row){
				if ($row['state'] == '0'){
					// at least one state 0 found
					$removeState0Check = false;
				} else {
					// at least one state<>0 found
					$removeStateCheck = false;
				}
				if ($row['section'] == '0'){
					// at least one section 0 found
					$removeSection0Check = false;
				} else {
					// at least one section<>0 found
					$removeSectionCheck = false;
				}
			}
		}
		
		LogHandler::Log(__CLASS__, 'DEBUG', 'Remove section 0 check: ' . ($removeSection0Check ? 'true' : 'false')
			. '; remove section check: ' . ($removeSectionCheck ? 'true' : 'false')
			. '; remove state 0 check: ' . ($removeState0Check ? 'true' : 'false')
			. '; remove state check: ' . ($removeStateCheck ? 'true' : 'false')
		);
		
		// build SQL Clause
		$sql = '';
		// if there's no av.`section` = 0 in table av, skip the check on this
		if (! $removeSectionCheck){
			$sql .= "AND ( o.`section` = av.`section`";
			if (! $removeSection0Check){
				// section = 0 exists, so include check
				$sql .= " OR av.`section` = 0";
			}
			$sql .= " ) ";
		}
		// if there's no av.`state` = 0 in table av, skip the check on this
		if (! $removeStateCheck){
			$sql .= "AND ( o.`state` = av.`state` ";
			if (! $removeState0Check && !$brandAdmin){
				// state = 0 exists, so include check
				$sql .= " OR ( av.`state` = 0 AND o.`state` <> -1 )";
			} else {
				$sql .= " OR ( av.`state` = 0 )"; // Brand admins are entitled to objects in personal state.
			}
			$sql .= " )";
		}
		elseif (!$brandAdmin) {  // Brand admins are entitled to objects in personal state.
			$sql .= " AND ( o.`state` <> -1 )"; // BZ#20226 - exclude object with personal state
		}
		
		return $sql;
	}
	
	/**
	 * Creates a view named smart_authorizedobjects_view with all object-id's which the user is authorized for.
	 * This includes the objects where the user is entitled to via the normal workflow and the objects of those
	 * brands the user has publication admin rights on.
	 *
	 * @param string $shortusername short name of the user to get the view for
	 * @param bool $deletedobjects
	 * @param array $params array with QueryParam objects
	 * @param bool $withclosed true if closed objects should be included otherwise false
	 * @param bool $hierarchical true if it's a hierarchical query otherwise false
	 * @param string $objectsWhere extra where clause for preselecting objects without WHERE itself
	 * @param int	$accessRight Access right to check (database id of the right or 0 when to skip).
	 */
	static public function createAuthorizedObjectsView($shortusername, $deletedobjects = false, $params = null,
													   $withclosed = false, $hierarchical = false, $objectsWhere = '',
													   $accessRight = 1/* View */)
	{
		$brandAdmin = false; // Has user brand admin rights
		$tempav = self::createAuthorizationsView($shortusername, $accessRight, $brandAdmin);
		$dbdriver = DBDriverFactory::gen();

		$publications = array();
		$issues = array();
		$parentIds = array();
		$objectIds = array();

		if ($params) {
			foreach ($params as $param) {
				if( !empty($param->Value) ) {
					$property = strtolower($param->Property);
					if( is_numeric($param->Value) ) {
						switch ($property) {
							case 'publicationid':
								$publications[] = $param->Value;
								break;
							case 'issueid':
								$issues[] = $param->Value;
								break;
							case 'parentid': // Get children of a parent object, limit the query to parent object and its children (BZ#22116)
								$objectIds[$param->Value] = intval( $param->Value );
								$children = self::getAllChildrenForParent( intval( $param->Value ), $deletedobjects );
								$objectIds = array_merge( $objectIds, $children );
								break;
							case 'childid':
								$objectIds[$param->Value] = intval( $param->Value );
								$parentIds = self::getAllParentsForChild( intval( $param->Value ), $deletedobjects );
								$objectIds = array_merge( $parentIds, $objectIds );
								break;
						}
					} else {
						if ( $property == 'issue' ) {
							require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
							$issueRows = DBIssue::getIssuesByName( $param->Value );
							if ( $issueRows ) foreach ( $issueRows as $issueRow ) {
								$issues[] = $issueRow[ 'id' ];
							}
						}
					}
				}
			}
		}

		require_once BASEDIR . "/server/dbclasses/DBIssue.class.php";;
		$overruleissues = DBIssue::listOverruleIssueIds($publications, $issues);

		$tempaov = self::getTempIds('aov');

		$shortusernameDBStr = $dbdriver->toDBString($shortusername);
		$objectstable = $deletedobjects ? $dbdriver->tablename('deletedobjects') : $dbdriver->tablename('objects');
		
		$isadmin = DBUser::isAdminUser($shortusername);
		
		// BZ#11479 insert directly into temp_aov
		$sql  = "INSERT INTO $tempaov ";
		
		$requiredPieces = array();
		if (! empty($publications)) {
			$requiredPieces[] = "o.`publication` IN (" . implode(',', $publications) . ")";
		}
		if (! $withclosed){
			$requiredPieces[] = "( o.`closed` <> 'on' OR o.`closed` IS NULL)";
		}
		if ( $objectIds ) {
			$requiredPieces[] = "o.`id` IN (" . implode(',', $objectIds) . ")";
		}
		// extra clause for selecting objects		
		if (! empty($objectsWhere)){
			$requiredPieces[] = $objectsWhere;
		}
		// $whereRequired must be filled
		if (empty($requiredPieces)){
			$requiredPieces[] = '1 = 1';
		}
		$whereRequired = implode(' AND ', $requiredPieces);
		
		// get optimized section and state sql part
		$sectionStateSQL = self::getAOVSectionStateSQLClause($tempav, $brandAdmin);

//		if (! empty($parentIds)){ BZ#20475 Content Station lists objects when no "Listed in Search Results" rights given.
//			// for queries on parent id, user has right to see all children
//			$sql .= self::getAOVParentSelect($parentIds, $overruleissues, $issues, $objectstable, $tempav, $sectionStateSQL, $shortusernameDBStr, $isadmin);
//		} else {
			// select object ids in brands
			$sql .= "/*NoOverrule*/\n" . self::getAOVNoOverruleSelect($overruleissues, $issues, $objectstable, $tempav, $whereRequired, $sectionStateSQL);
				// select object ids in overrule brand issues
			if (! empty($overruleissues)) {
				if ($dbdriver->supportMultipleReferencesTempTable()) {
					$tempav2 = $tempav;
				} else {
					$tempav2 = DBBase::getTempIds('av2');
				} //TODO we can skip this if we check given issues are present in overrule issues
				$sql .= "\nUNION /*Overrule*/\n" . self::getAOVOverruleSelect($overruleissues, $issues, $objectstable, $tempav2, $whereRequired, $sectionStateSQL);
			}
			// select exceptions
			$sql .= "\nUNION /*Exceptions*/\n" . self::getAOVExceptionsSelect($issues, $objectstable, $shortusernameDBStr, $isadmin, $whereRequired);
//		}

		$dbdriver->query($sql);
	}
	
	/**
	 * Help function to create SQL statement for Authorized Object View
	 *
	 * @param string $objectstable
	 * @param array $issuePieces
	 * @param string $joinOnRightsClause
	 * @param string $tempav
	 * @return string
	 */
	private static function getAOVSelect($objectstable, $issuePieces, $joinOnRightsClause, $tempav)
	{
		$sql = "SELECT DISTINCT o.`id` "
			. " FROM $objectstable o";
		if (! empty($issuePieces)){
			$sql .= " INNER JOIN (" . self::getIssueSubSelect($issuePieces) . ') tar ON (o.`id` = tar.`objectid`) ';
		}
		if (! empty($joinOnRightsClause)){
			$sql .= " INNER JOIN $tempav av ON ($joinOnRightsClause)";
		}
		return $sql;
	}
	
	/**
	 * Return SQL statement to select object ids which the user is authorized for and are not in overrule brand issues.
	 *
	 * @param array $overruleissues
	 * @param array $issues
	 * @param string $objectstable
	 * @param string $tempav
	 * @param string $whereRequired
	 * @param string $sectionStateSQL
	 * @return string
	 */
	private static function getAOVNoOverruleSelect($overruleissues, $issues, $objectstable, $tempav, $whereRequired, $sectionStateSQL)
	{
		$issuePieces = array();
		// don't add overrule issue check to $issuePieces (goes wrong with objects which don't have any issues assigned)
		if (! empty($issues)) {
			$issuePieces[] = "tar.`issueid` IN (" . implode(',', $issues) . ")";
		}
		$sql = self::getAOVSelect($objectstable, $issuePieces, "(o.`publication` = av.`publication`) AND ( av.`issue` = 0)", $tempav);
		$wheres = array();
		if (!empty($overruleissues)){
			// if tar isn't joined yet, do a left join (prevent error with objects which don't have any issues assgined)
			if (empty($issues)) {
				$issueClause[] = " 1 = 1 ";
				// Also take children into account (via relational targets) BZ#33659.
				$sql .= " LEFT JOIN (" . self::getIssueSubSelect($issueClause) . ') tar ON (o.`id` = tar.`objectid`) ';	
			}
			$wheres[] = '( tar.`issueid` NOT IN (' . implode(',', $overruleissues) . ') OR tar.`issueid` IS NULL )';
		}
		$wheres[] = $whereRequired;
		$sql .= ' WHERE ' . implode(' AND ', $wheres);
		$sql .= $sectionStateSQL;
		
		return $sql;
	}
	
	/**
	 * Return SQL statement to select object ids which the user is authorized for and are in overrule brand issues.
	 *
	 * @param array $overruleissues
	 * @param array $issues
	 * @param string $objectstable
	 * @param string $tempav
	 * @param string $whereRequired
	 * @param string $sectionStateSQL
	 * @return string
	 */
	private static function getAOVOverruleSelect($overruleissues, $issues, $objectstable, $tempav, $whereRequired, $sectionStateSQL)
	{
		$issuePieces = array();
		$issuePieces[] = "tar.`issueid` IN (" . implode(',', $overruleissues) . ")";
		if (! empty($issues)) {
			$issuePieces[] = "tar.`issueid` IN (" . implode(',', $issues) . ")";
		}
		$sql = self::getAOVSelect($objectstable, $issuePieces, "tar.`issueid` = av.`issue`", $tempav)
			. ' WHERE ' . $whereRequired
			. $sectionStateSQL;
		return $sql;
	}
	
	/**
	 * Return SQL statement to select extra object ids.
	 *
	 * @param array $issues
	 * @param string $objectstable
	 * @param string $shortusernameDBStr
	 * @param bool $isadmin
	 * @param string $whereRequired
	 * @return string
	 */
	private static function getAOVExceptionsSelect($issues, $objectstable, $shortusernameDBStr, $isadmin, $whereRequired)
	{
		$issuePieces = array();
		if (! empty($issues)) {
			$issuePieces[] = "tar.`issueid` IN (" . implode(',', $issues) . ")";
		}
		$sql = self::getAOVSelect($objectstable, $issuePieces, "", "")
			. " WHERE ( o.`routeto` = '$shortusernameDBStr'"
			. ( $isadmin ? " OR o.`state` = -1 " : "" )
			. ") AND (" . $whereRequired .")";
		return $sql;
	}
	
	/**
	 * Return SQL statement to select child object ids. No check on access rights on
	 * the child objects. 
	 *
	 * @param array $parentIds
	 * @param array $overruleissues
	 * @param array $issues
	 * @param string $objectstable
	 * @param string $tempav
	 * @param string $sectionStateSQL
	 * @param string $shortusernameDBStr
	 * @param bool $isadmin
	 * @return string
	 * @throws BizException
	 */
	private static function getAOVParentSelect($parentIds, $overruleissues, $issues, $objectstable, $tempav, $sectionStateSQL, $shortusernameDBStr, $isadmin)
	{
		$dbdriver = DBDriverFactory::gen();
		// check access on parent
		$parentsWhereClause = 'o.`id` IN (' . implode(',', $parentIds) . ')';
		$checkParentIdSQL = "/*CheckParentNoOverrule*/\n" . self::getAOVNoOverruleSelect($overruleissues, $issues, $objectstable, $tempav, $parentsWhereClause, $sectionStateSQL);
		if (! empty($overruleissues)){
			if ($dbdriver->supportMultipleReferencesTempTable()) {
				$tempav2 = $tempav;
			}
			else {
				$tempav2 = DBBase::getTempIds('av2');
			}	
			$checkParentIdSQL .= "\nUNION /*CheckParentOverrule*/\n" . self::getAOVOverruleSelect($overruleissues, $issues, $objectstable, $tempav2, $parentsWhereClause, $sectionStateSQL);
		}
		$checkParentIdSQL .= "\nUNION /*CheckParentExceptions*/\n" . self::getAOVExceptionsSelect($issues, $objectstable, $shortusernameDBStr, $isadmin, $parentsWhereClause);
		$sth = $dbdriver->query($checkParentIdSQL);
		$rows = self::fetchResults($sth);
		if (! isset($rows[0]) || count($rows) != count($parentIds)){
				throw new BizException('ERR_AUTHORIZATION', 'Client', 'Not allowed to query on parent ids: ' . implode(',', $parentIds));
		}

		// assume querying for ParentId hasn't additional query params because a user has access to all children
		$sql = self::getAOVSelect($objectstable, array(), "", "")
			. ' INNER JOIN ' . $dbdriver->tablename('objectrelations') . ' rel ON (o.`id` = rel.`child`)'
			. " WHERE rel.`parent` IN (" . implode(',', $parentIds) . ")";
		return $sql;
	}

	public static function escape4sql($instring)
	{
		$dbdriver = DBDriverFactory::gen();
		return $dbdriver->toDBString($instring);
	}
	
	/**
	 * This method escapes sql wildcards used in search strings.
	 * The used escape character itself has to be escaped if used in a like
	 * statement as the % (any character, zero or more times), _ (any single
	 * character)
	 *
	 * @param string $value string used in like statement
	 * @param string $escchar the used escape character
	 * @return string with escaped wildcharacters
	 * 
	 * Note: the sql statement must contain the ESCAPE 'escchar' to inform the
	 * DBMS which character is used as escape character
	 */
	public static function escape4like($value, $escchar)
	{
		$result = $value;
		$result = str_replace($escchar, $escchar . $escchar,$result);
		$result = str_replace('%', $escchar .'%',$result);
		$result = str_replace('_', $escchar . '_',$result);
		return $result;
	}
	
	/**
	 * Returns the Named Queries sorted on the name
	 *
	 * @param string $query Named Query name, if null no filtering on name
	 * @param boolean $hidden Return hidden Named Queries. Hidden means that the query name start with a '.'
	 * @return resource Query identifier
	 */
	static public function getNamedQueries( $query=null, $hidden = true )
	{
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename("namedqueries");
		$query = $dbdriver->toDBString($query);

		$sql = "SELECT * from $db";
		if ($query) {
			$sql .= " where `query`='$query'";
			if (!$hidden) {
				$sql .= " and `query` not like '.%'"; 
			}
		}
		else {
			if (!$hidden) {
				$sql .= " where `query` not like '.%'"; 
			}
		}
		$sql .= " order by `query`";
		$sth = $dbdriver->query($sql);
		return $sth;
	}

	static public function listColNamesAsRow($sqlarray)
	{
		$dbdriver = DBDriverFactory::gen();
		$sql = $sqlarray['select'];
		$sql .= $sqlarray['from'];
		$sql .= $sqlarray['joins'];
		$sql .= ' WHERE (1 = 0) ';
		$sth = $dbdriver->query($sql);
		$columns = $dbdriver->listcolumns($sth);
		$row = array();
		foreach ($columns as $column) {
			$row[$column['name']] = null;
		}
		return $row;
	}

	static public function queryPrevIssueId($publicationid)
	{
		$dbdriver = DBDriverFactory::gen();
		$issuestable = $dbdriver->tablename("issues");
		$channelstable = $dbdriver->tablename("channels");
		$publicationstable = $dbdriver->tablename("publications");
		
		$sql  = "SELECT iss.`id` ";
		$sql .= "FROM $issuestable iss ";
		$sql .= "INNER JOIN $publicationstable pub ON (pub.`id` = $publicationid) ";
		$sql .= "INNER JOIN $channelstable chn ON (pub.`defaultchannelid` = chn.`id`) ";
		$sql .= "INNER JOIN $issuestable cis ON (chn.`currentissueid` = cis.`id`) ";
		$sql .= "WHERE ";
		$sql .= "iss.`code` < cis.`code` AND iss.`channelid` = chn.`id`";
		$sql .= "ORDER BY iss.`code` DESC ";
		
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		if ($row) {
			return $row['id'];
		}
		else {
			return null;
		}
	}

	static public function queryNextIssueId($publicationid)
	{
		$dbdriver = DBDriverFactory::gen();
		$issuestable = $dbdriver->tablename("issues");
		$channelstable = $dbdriver->tablename("channels");
		$publicationstable = $dbdriver->tablename("publications");
		
		$sql  = "SELECT iss.`id` ";
		$sql .= "FROM $issuestable iss ";
		$sql .= "INNER JOIN $publicationstable pub ON (pub.`id` = $publicationid) ";
		$sql .= "INNER JOIN $channelstable chn ON (pub.`defaultchannelid` = chn.`id`) ";
		$sql .= "INNER JOIN $issuestable cis ON (chn.`currentissueid` = cis.`id`) ";
		$sql .= "WHERE ";
		$sql .= "iss.`code` >  cis.`code` AND iss.`channelid` = chn.`id` ";
		$sql .= "ORDER BY iss.`code` ASC ";
		
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		if ($row) {
			return $row['id'];
		}
		else {
			return null;
		}
	}

	static public function queryCurrentIssueId($publicationid)
	{
		$dbdriver = DBDriverFactory::gen();
		$channelstable = $dbdriver->tablename("channels");
		$publicationstable = $dbdriver->tablename("publications");
		
		$sql  = "SELECT chn.`currentissueid` ";
		$sql .= "FROM $channelstable chn ";
		$sql .= "INNER JOIN $publicationstable pub ON (pub.`id` = $publicationid) ";
		$sql .= "WHERE chn.`id` = pub.`defaultchannelid` ";
		
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		if ($row) {
			return $row['currentissueid'];
		}
		else {
			return null;
		}
	}
	
	/**
	 * Query if given parent have contained or placed relations with child objects.
	 * $deletedobjects indicates whether to query for already 'Deleted'(Laying in Trash Area) or 'Non-Deleted'(Laying in 'Workflow' Area) children Object.
	 *
	 * @param array $parentIds
	 * @param bool $deletedObjects
	 * @return array with parent ids that have child objects
	 */
	static public function queryHasChildren(array $parentIds, $deletedObjects)
	{
		$result = array();
		if (count($parentIds) > 0){
			$dbdriver = DBDriverFactory::gen();
			// get all parent ids
			$sql = 'SELECT DISTINCT `parent` AS "Parent"'
				. ' FROM ' . $dbdriver->tablename('objectrelations')
				. ' WHERE `parent` IN (' . implode(',', $parentIds) . ')'
				. ' AND `type` IN (?, ?)';
			$sth = $deletedObjects ? $dbdriver->query($sql, array('DeletedContained', 'DeletedPlaced')) : $dbdriver->query($sql, array('Contained', 'Placed'));
			$result = self::fetchResults($sth);
		}
		
		return  $result;
	}
	
	/**
	 * Returns a (sub) select statement to select object ids with given
	 * issue parameters.
	 * 
	 * When a user queries on a specific issue, the user gets to see objects
	 * with the given issue and objects that have been targetted for the given
	 * issue or placed on layout with the issue, see BZ#15845
	 *
	 * @param array $issueClauses
	 * @return string SQL
	 */
	public static function getIssueSubSelect(array $issueClauses = array())
	{
		$dbdriver = DBDriverFactory::gen();
		$targetsTable = $dbdriver->tablename("targets");
		$relationsTable = $dbdriver->tablename("objectrelations");
		$issuesTable = $dbdriver->tablename("issues");
		
		$issueWhere = implode(' OR ', $issueClauses);
		$sql = "\nSELECT tar.`objectid` AS objectid, tar.`issueid` AS issueid"
			. " FROM $targetsTable tar"
			. " INNER JOIN $issuesTable iss ON (tar.`issueid` = iss.`id`)"
			. " WHERE $issueWhere"
			. " AND tar.`objectrelationid` = 0"
			. " UNION /*ChildrenInIssues*/"
			. " SELECT rel.`child` AS objectid, tar.`issueid` AS issueid"
			. " FROM $targetsTable tar"
			. " INNER JOIN $issuesTable iss ON (tar.`issueid` = iss.`id`)"
			. " INNER JOIN $relationsTable rel ON ( rel.`id` = tar.`objectrelationid` )"
			. " WHERE $issueWhere\n";
		
		return $sql;
	}
	
	/**
	 * Determine if the view is already created
	 * 
	 * @param string $viewid
	 * @return boolean 
	 */
	private static function isViewCreated( $viewid )
	{		
		if ( !is_array( self::$RegViews) || !in_array( 'av_'.$viewid, self::$RegViews ) ) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Returns an array with all object ids stored by a view (temporary table).
	 * @param string $view The identifier of the view. 
	 * @return array with object ids.
	 */
	public static function getIdsByView( $view )
	{
		$dbdriver = DBDriverFactory::gen();
		$tempView = self::getTempIds( $view );
		$sql = 'SELECT `id` "Id" FROM '. $tempView;  		
		$sth = $dbdriver->query( $sql );
		$result = self::fetchResults($sth, 'Id' );
		return array_keys( $result );
	}	
}
