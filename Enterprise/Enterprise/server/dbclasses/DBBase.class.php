<?php

/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class DBBase
{
	private static $dbErrorMsg;
	private static $isErrorSet;
	public static $ExceptionOnError = false;
	protected static $UniqueId = null;
	protected static $TempIdsTables = null;

	/**
	 * Each session new views are created, but as these views are not session-based we need 
	 * an unique identifier to create unique viewnames.
	 * An additional problem occurs because in Oracle a tablename may be not more then 30 characters!
	 * That's why the length of the uniqueid will be maximized to a length of 20 charcters.
	 * @param boolean $resetCache Does clear the id to generate new one NEXT time. Returns old/current id THIS time.
	 * @return string the id
	 */
	static protected function getUniqueId( $resetCache = false )
	{
		if( empty(DBBase::$UniqueId) ) {
			DBBase::$UniqueId = md5(uniqid(null, true));
			DBBase::$UniqueId = substr(self::$UniqueId, 0, 20);
		}
		if( $resetCache ) {
			$oldId = DBBase::$UniqueId;
			DBBase::$UniqueId = ''; // clear cache!
			return $oldId;
		}
		return DBBase::$UniqueId;
	}

	/**
	 * Creates a temporary table to store temporary created object-id.'s
	 * The table has "temp_" prefix.
	 *
	 * @param $viewid string three-letter id of the temporary table
	 * @return string created table name, typcially temp_abc
	 */
	static public function getTempIds($viewid)
	{
		if (!isset(DBBase::$TempIdsTables)) {
			DBBase::$TempIdsTables = array();
		}
			
		if( empty(DBBase::$TempIdsTables[$viewid]) ) {
			$dbdriver = DBDriverFactory::gen();
			$tempids = "temp_" . $viewid;
			switch ($viewid) {
				case 'av':
					$tempids = $dbdriver->createAuthorizedTempTable($tempids);
					break;
				case 'av2':
						$tempids = $dbdriver->createCopyTempTable($tempids, self::getTempIds('av'));
					break;
				default:
					$tempids = $dbdriver->createTempIdsTable($tempids);
					break;
			}
			DBBase::$TempIdsTables[$viewid] = $tempids;
		}

		return DBBase::$TempIdsTables[$viewid];
	}
	
	/**
	 *	Clears temporary tables which used when querying...
	 *  Depending on DBTYPE the table either needs to be truncated or dropped.
	 *  When we would only truncate the table on mysql and the queryObjects is called twice (or more)
	 *  we would get an SQL-error table already exists...
	 */
	static public function clearTempTables()
	{
		$dbdriver = DBDriverFactory::gen();
		foreach (DBBase::$TempIdsTables as $tempids) {
			$dbdriver->dropTempTable( $tempids );
		}
		DBBase::$TempIdsTables = array();
	}
	
	/**
	 * Raises a DB error.
	 *
	 * @param string $error
	 * @throws BizException Throws BizException when the flag to throw BizException is set to true.
	 */
	static protected function setError( $error )
	{
		self::$dbErrorMsg = $error;
		self::$isErrorSet = true;
		
		if (self::$ExceptionOnError) {
			throw new BizException('ERR_DATABASE', 'Server', $error);
		}
	}
	
	/**
	 * Clears the last raised DB error.
	 */
	static protected function clearError()
	{
		self::$dbErrorMsg = '';
		self::$isErrorSet = false;
	}

	/**
	 * Tells if a DB error was raised.
	 *
	 */
	static public function hasError()
	{
		return self::$isErrorSet;
	}

	/**
	 * Retrieves the last raised DB error.
	 *
	 * @return string
	 */
	static public function getError()
	{
		return self::$dbErrorMsg;
	}
	
	/**
	 * Wraps a given column (field) name with back quotes (`) so it can be used in SQL statements.
	 * Can also be called when column name was already wrapped, for which no action is taken.
	 * Column name can just be a name or can have an table alias as prefix. 
	 *
	 * @param string $colname
	 * @return string Wrapped column name.
	 */
	static public function toColname( $colname )
	{
		$alias = explode('.', $colname);
		$result = '';
		if (count( $alias ) === 2) { // alias is used e.g. o.id
			$result = $alias[0].'.';  
			$colname = $alias[1];
		}
		
		if( strrchr( $colname, '`' ) == false ) {
			$result .= '`' . $colname . '`';
		} else {
			$result .= $colname;	
		}
		return $result;
	}

	/**
	 * Add a 'limit by' clause to the sql statement.
	 *
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @param string $sql
	 * @param array $limit Contains two key/value pairs. $limit['min'] is the offset,
	 * $limit['max'] the maximum number of rows to return.
	 * @return string SQL statement extended with the 'limit by' clause. 
	 */
	static private function addLimitByClause( $dbDriver, $sql,  array $limit )
	{
		$offset = intval($limit['min']);
		$max = intval($limit['max']);
		
		$sql = $dbDriver->limitquery( $sql, $offset, $max );
		return $sql;
	}	

	/**
	 * Adds the ORDER BY clause to the given SQL.
	 *
	 * @param string $sql
	 * @param array $orderBy List of fields to order (in case of many results, whereby the first/last row is wanted).
	 *        Keys: DB fields. Values: TRUE for ASC or FALSE for DESC.
	 * @return string Updated SQL statement
	 */
	static private function addOrderByClause( $sql, array $orderBy )
	{
		$sql .= ' ORDER BY ';
		$comma = '';
		foreach( $orderBy as $orderField => $orderDirection ) {
			$sql .= $comma . self::toColname( $orderField ) . ' ';
			$sql .= $orderDirection ? 'ASC' : 'DESC';
			$comma = ', ';
		}

		$sql .= ' '; // Add trailing space.
		
		return $sql;
	}

	/**
	 * Adds the GROUP BY clause to the given SQL.
	 *
	 * @param string $sql
	 * @param array $groupBy List of fields to group the initial result set on.
	 *        Values: DB fields 
	 * @return string Updated SQL statement
	 */
	static private function addGroupByClause( $sql, array $groupBy)
	{
		$sql .= ' GROUP BY ';
		$comma = '';
		foreach( $groupBy as $groupByField ) {
			$sql .= $comma . self::toColname( $groupByField ) . ' ';
			$comma = ', ';
		}

		$sql .= ' '; // Add trailing space.
		
		return $sql;
	}	
	
	/**
	 * Updates a row in table $tablename with $params where $where. If a field is of the type blob
	 * the value must be passed by using the $blob parameter. The value of the blob field 
	 * in $params must be set to the keyword #BLOB#, $params[<fieldname>] = '#BLOB#'.
	 *
	 * @param string $tablename
	 * @param array $values
	 * @param string $where
	 * @param array $params Contains parameters to be substituted for the placeholders at WHERE clause.
	 * @param string|array $blob Chunk of data to store at DB. It will be converted to a DB string here.
	 *                           One blob can be passed or multiple. If muliple are passed $blob is an array.
	 * @return bool True if succeeded, False if an error occurred.
	 */
	static public function updateRow( $tablename, $values, $where, $params = array(), $blob = null )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$tablename = $dbDriver->tablename( $tablename );
		$autoIncrement = self::getAutoincrementColumn();
		$updateParams = array(); // Array to be used to store params created for the the update statement.

		// Do not pass the auto-increment field to update statement. This causes
		// an error in case MSSQL is used (Cannot update identity column)
		if( array_key_exists( $autoIncrement, $values ) ) {
			unset( $values[$autoIncrement] );				
		}			
		
		$sql = '';
		$sql .= "UPDATE $tablename SET ";
		$comma = '';

		// Go through the params, create the SQL part and add the field to the params for the update.
		foreach ($values as $fieldname => $value)	
		{
			// If the field is a BLOB value, then we should add it directly to the Query results
			if ($value == '#BLOB#') {
				$sql .= " $comma " . "`$fieldname` = $value";
			} else { // If it is not a BLOB field we should add it as a string replacement.
				$value = ( is_null($value) ) ? 'null' : strval($value);
				$sql .= " $comma " . "`$fieldname` = ?";
				$updateParams[] = $value; // Add value to the update params.
			}
			$comma = ',';
		}

		// Merge the params so they will be correctly replaced by the query function. Where the original params should
		// be added lastly.
		$params = array_merge($updateParams, $params);

		if( trim( $where ) != '' ) {
			$sql .= " WHERE $where ";
		}
		$queryresult = $dbDriver->query( $sql, $params, $blob );
		if( !$queryresult ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
		}
		return $queryresult ? true : false;
	}

	/**
	 * Inserts a row in table $tablename with $params. If a field is of the type blob
	 * the value must be passed by using the $blob parameter. The value of the blob field 
	 * in $params must be set to the keyword #BLOB#, $params[<fieldname>] = '#BLOB#'.
	 *
	 * @param string $tablename
	 * @param array $values
	 * @param boolean $autoincrement
	 * @param string|array $blob Chunck of data to store at DB. It will be converted to a DB string here.
	 *                           One blob can be passed or multiple. If muliple are passed $blob is an array.
	 * @param boolean $logAlreadyExists boolean Log 'already exists' errors. If set to false no error is logged for an insert operations for which this error is fine.
	 * @return integer|boolean New inserted row DB Id when record is successfully inserted; False otherwise.
	 */
	static public function insertRow( $tablename, $values, $autoincrement = true, $blob = null, $logAlreadyExists = true )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$tablename = $dbDriver->tablename( $tablename );
		
		if (isset($values['id'])) {
			unset($values['id']);
		}
		
		$sql = "INSERT INTO $tablename ";
		$comma = '';
		$fields_sql = '';
		$values_sql = '';
			
		foreach ($values as $fieldname => $value)
		{
			$fields_sql .= " $comma " . self::toColname($fieldname);
			if( is_string($value) && ($value != '#BLOB#' ) ) {
				$value = "'" . $dbDriver->toDBString($value) . "'";
			}	
			elseif (is_null( $value) ) {
				$value = 'null'; // string value of null is empty string				
			} else {
				$value = strval( $value );
			}
			$values_sql .= " $comma " . $value;
			$comma = ',';
		}
		$sql .= '(' . $fields_sql . ')' . ' VALUES ' . '(' . $values_sql . ')';
		
		if ($autoincrement) {
			$sql = $dbDriver->autoincrement( $sql );
		}
		$queryresult = $dbDriver->query( $sql, array(), $blob, true, $logAlreadyExists );
		if( !$queryresult ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
		}
		return $queryresult ? $dbDriver->newId($tablename, true) : false;
	}

	/**
	 * Do a multiple rows insertion into database table in one call.
	 *
	 * Calling this function instead of {@link: insertRow()} multiple times when dealing with multiple rows insertion,
	 * as this function does all the insertion in one go, and therefore it'll be much faster.
	 *
	 * $params structure looks like this:
	 * $params = array(
	 *              array( 1,2,3,4 ),
	 *              array( 5,6,7,8 ),
	 *              array( 9,10,11,12 )
	 *           );
	 *
	 * @param string $tableName Table name without the 'smart_' prefix.
	 * @param string[] $columns List of DB field names.
	 * @param array[] $values List of array list which contains the database params. Refer to function header.
	 * @param bool $autoincrement Whether the table to be inserted has autoincrement db field.
	 * @param bool $writeLog In case of license related queries, this option can be used to not write in the log file.
	 * @param bool $logExistsErr Suppress 'already exists' errors.
	 * @return bool Whether the insertion succeeded.
	 */
	public static function insertRows( $tableName, $columns, $values, $autoincrement = true, $writeLog=true, $logExistsErr=true  )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();

		$sql = $dbDriver->composeMultiInsertSql( $tableName, $columns, $values, $autoincrement );
		$queryResult = $dbDriver->query(  $sql, array(), null, $writeLog, $logExistsErr );
		if( !$queryResult ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
		}
		return $queryResult ? true : false;
	}

	/**
	 * Get the row with $fields in table $tablename where $where.
	 * If more rows are found returns the first row found.
	 *
	 * @param string $tablename
	 * @param string $where
	 * @param mixed $fieldnames. Either an array containing the fieldnames to get or '*' in which case all fields are returned.
	 * @param array $params, containing parameters to be substituted for the placeholders
	 *        of the where clause. 
	 * @param array $orderBy List of fields to order (in case of many results, whereby the first/last row is wanted).
	 *        Keys: DB fields. Values: TRUE for ASC or FALSE for DESC. NULL for no ordering.
	 * @param string|array $blob Chunck of data to store at DB. It will be converted to a DB string here.
	 *                           One blob can be passed or multiple. If muliple are passed $blob is an array.
	 * @return array with params or null if no row found.
	 */
	static public function getRow( $tablename, $where, $fieldnames = '*', $params = array(), $orderBy = null, $blob=null )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$tablename = $dbDriver->tablename( $tablename );
		
		$sql = '';
		if( is_array( $fieldnames ) ) {
			$sql .= " SELECT ";
			$comma = '';
			foreach( $fieldnames as $fieldname ) {
				$dbfield = self::toColname( $fieldname );
				$sql .= $comma . $dbfield;
				$comma = ', ';
			}
		} else {
			$sql .= " SELECT * ";
		}
		
		$sql .= " FROM $tablename ";
		if( trim( $where ) != '' ) {
			$sql .= " WHERE $where ";
		}
		if( !is_null( $orderBy ) ) {
			$sql = self::addOrderByClause( $sql, $orderBy );
			$sql = $dbDriver->limitquery( $sql, 0, 1 );
		}
		
		$queryresult = $dbDriver->query( $sql, $params, $blob );
		if( is_null( $queryresult ) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		
		$row = $dbDriver->fetch( $queryresult );
		if( !$row ) {
			return null;
		}
		
		if( is_array( $row ) ) {
			$row = array_change_key_case( $row );
		}
		return $row;
	}

	/**
	 * List all rows from $tablename where $where.
	 * What is exactly returned depends on the value of $fieldnames:
	 * - Either null or false -> returns an array of rows indexed by the value in $keycol, each containing the name ($namecol) of the row.
	 * - Either '*' or true -> returns an array of rows indexed by the value in $keycol, each containing an array with params.
	 * - An array with fieldnames -> returns an array of rows indexed by the value in $keycol, each containing an array with params in $fieldnames.
	 * - An array with field names at params and table var names at keys -> for example: array( 'm' => array( '*' ), 'u' => array( 'id', 'user' ) )
	 * @param string|array $tableNames One table name. Can also be a list of table names: array( 'u' => 'users', 'm' => 'messages' )
	 * @param string $keycol  Used as keys at the result array. Leave empty to use [0...N-1] array keys. 
	 * @param string $namecol
	 * @param string $where Indicates the condition or conditions that rows must satisfy to be selected.
	 * @param mixed  $fieldnames, see function description
	 * @param array  $params ,containing parameters to be substituted for the placeholders
	 *        of the where clause.
	 * @param array|null $orderBy List of fields to order (in case of many results, whereby the first/last row is wanted).
	 *        Keys: DB fields. Values: TRUE for ASC or FALSE for DESC. NULL for no ordering.
	 * @param array|null $limit Keys: 'min' specifies the offset of the first row to return,
	 * 		  'max' specifies the maximum number of rows to return. E.g. ('min' => 5, 'max' => 10) means
	 * 		  retrieve rows 6-15. The offset of the initial row is 0 (not 1).
	 * @param array|null $groupBy List of fields on which the result set is grouped.
	 * @param string|null $having Indicates the condition or conditions that the grouped by rows must satisfy to be selected.
	 * @return array of rows (see function description) or null on failure (use getError() for details)
	 */
	static public function listRows(
		$tableNames, $keycol, $namecol, $where, $fieldnames = '*', $params = array(), $orderBy = null, $limit = null, $groupBy = null, $having = null )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$sql = '';
		
		// Build the SELECT clause.
		if( $fieldnames === '*' || $fieldnames === true ) {
			$sql .= ' SELECT * ';
		} else if( empty($fieldnames) || $fieldnames === false ) {
			$sql .= ' SELECT ';
			$comma = '';
			if( !empty($keycol) ) {
				$sql .= self::toColname($keycol).' ';
				$comma = ', ';
			}
			if( !empty($namecol) ) {
				$sql .= $comma.' '.self::toColname($namecol).' ';
			}
		} else if( is_array($fieldnames) ) {
			$sql .= ' SELECT ';
			$comma = '';
			foreach( $fieldnames as $tableVar => $value ) {
				if( is_string( $tableVar ) && is_array( $value ) ) {
					foreach( $value as $fieldname ) {
						$dbfield = ($fieldname == '*') ? $tableVar.'.*' : $tableVar.'.'.self::toColname($fieldname);
						$sql .= $comma . $dbfield;
						$comma = ', ';
					}
				} else {
					$fieldname = $value;
				$dbfield = ($fieldname == '*') ? '*' : self::toColname($fieldname);
				$sql .= $comma . $dbfield;
				$comma = ', ';
			}
			}
		} else {
			self::setError( BizResources::localize('ERR_ARGUMENT') );
			return null;
		}
		
		// Build the FROM clause.
		if( is_array( $tableNames ) ) {
			$sql .= ' FROM ';
			$comma = '';
			foreach( $tableNames as $tableVar => $tableName ) {
				$tableName = $dbDriver->tablename( $tableName );
				$sql .= $comma.$tableName.' '.$tableVar;
				$comma = ', ';
			}
			$sql .= ' ';
		} else {
			$tableNames = $dbDriver->tablename( $tableNames );
			$sql .= " FROM $tableNames ";
		}
		
		// Build the WHERE clause.
		if( trim( $where ) != '' ) {
			$sql .= " WHERE $where ";
		}

		if ( !is_null ( $groupBy )) {
			$sql = self::addGroupByClause( $sql, $groupBy);
			if ( !is_null ( $having )) { // Having is only applicable if there is a group by
				$sql .= " HAVING $having ";
			}
		}

		if( !is_null( $orderBy ) ) {
			$sql = self::addOrderByClause( $sql, $orderBy );
		}

		// Do not extend the query after the limit is added.
		if ( !is_null( $limit ) ) {
			$sql = self::addLimitByClause( $dbDriver, $sql, $limit );
		}

		$sth = $dbDriver->query( $sql, $params );
		if( is_null( $sth ) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		return self::fetchResults( $sth, $keycol, true );
	}

	/**
	 * Queries a list of paged rows.
	 *
	 * Due to the inefficiency of the listRows function it was needed to provide a secondary
	 * query to be able to run paged queries where a subset of records is requested. On big
	 * datasets this turned out to be a slow operation.
	 *
	 * @param string $tableName Table name without the 'smart_' prefix.
	 * @param string $keyCol The primary key column to search on.
	 * @param string $where SQL-where clause.
	 * @param array $params The params to use in the query.
	 * @param array|null $orderBy Fields to sort on.
	 * @param array $limit The number of records to retrieve.
	 * @return array of rows.
	 */
	public static function listPagedRows( $tableName, $keyCol, $where, $params = array(), $orderBy = null, $limit )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();

		// Preconstruct the order by.
		$orderBySQL = ( !is_null( $orderBy ) ) ? self::addOrderByClause( '', $orderBy ) : '';

		// Retrieve the fully composed query for the paged resultset.
		$sql = $dbDriver->composePagedQuerySql( $tableName, $keyCol, $where, $orderBySQL, $limit, $params );

		// In case a driver does not support an updated query we have to run a normal listRows
		// to fetch the results, as is the case with the Oracle driver for example.
		if( false === $sql ) {
			$rows = self::listRows( $tableName, $keyCol, '', $where, '*', $params, $orderBy, $limit );
		} else {
			// Run the query, fetch the results and return the rows.
			$sth = $dbDriver->query( $sql, $params );
			if( is_null( $sth ) ) {
				$err = trim( $dbDriver->error() );
				self::setError( empty( $err ) ? BizResources::localize( 'ERR_DATABASE' ) : $err );
				$rows = null;
			} else {
				$rows = self::fetchResults( $sth, $keyCol, true );
			}
		}
		return $rows;
	}

	/**
	 *	Deletes one or more rows from table with $tablename where = $where
	 *	@param $tablename string Name of the table to delete records from
	 *  @param $where string What records to delete. Can contain placeholders (?).
	 *  @param $params array containing parameters to be substituted for the placeholders
	 *         of the where clause.
	 * @return boolean|null NULL in case of error, TRUE in case of success
	 */
	static public function deleteRows( $tablename, $where, $params = array() )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$tablename = $dbDriver->tablename($tablename);
		
		$sql = " DELETE FROM $tablename WHERE $where ";
		$queryresult = $dbDriver->query($sql, $params);
		if( is_null( $queryresult ) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}
		return true;
	}

	/**
	 * Set(Reset) the $tableName auto increment value to $autoIncrementValue.
	 * @param string $tableName DB table name with 'smart_' prefix.
	 * @param integer $autoIncrementValue The value to be reset to in the auto increment.
	 */
	static public function resetAutoIncreament( $tableName, $autoIncrementValue )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$dbDriver->resetAutoIncreament( $tableName, $autoIncrementValue );
	}

	/**
	 * Fetches all results (rows) of a query. 
	 * Should called after a call to dbdriver->query($sql).
	 *
	 * @param resource $sth       Result returned from the call to dbdriver->query($sql).
	 * @param string $keycol      Name of the col to use as a key, if empty the array of rows is returned non-associative
	 * @param bool $keyslowercase Whether fieldnames should automatically be in lowercase (preferable, but not yet default)
	 * @param WW_DbDrivers_DriverBase $dbDriver  Optional.
	 * @return array of rows
	 */
	static public function fetchResults( $sth, $keycol = null, $keyslowercase = false, $dbDriver = null )
	{
		if( is_null( $dbDriver ) ) {
			$dbDriver = DBDriverFactory::gen();
		}
		$result = array();
		if( $keycol ) {
			while( ( $row = $dbDriver->fetch( $sth ) ) ) {
				if( $keyslowercase) {
					$row = array_change_key_case( $row );
				}
				$result[$row[$keycol]] = $row;
			}
		} else {
			while( ( $row = $dbDriver->fetch( $sth ) ) ) {
				if ($keyslowercase) {
					$row = array_change_key_case( $row );
				}
				$result[] = $row;
			}
		}
		return $result;
	}
	
	/**
	 * Based on an array of column/params pairs a where clause is generated with
	 * the proper number of ?-s. The params are returned as substitutes which
	 * can later on be used to replace the ?-s. The column/params must be passed
	 * as an array with the columns as keys and the params as an array.
	 * E.g.
	 * [column1]	[1]
	 * 				[2]
	 * [column2]	[a]
	 * 		 		[b]
	 * [column3]	[foo]
	 * where-clause WHERE (`column1` IN (?, ?)) AND (`column2` IN (?, ?)) AND (`column3` = ?))
	 * substitutes [[1] [2] [a] [b] [foo]]
	 * If the number of passed array elements exceeds 1000 then more than one IN-clause is generated.
	 * This is because of Oracle limitations. This is a very exceptional case.
	 * @param array $columnValues
	 * @param array $substitutes
	 * @return string where-clause.
	 */
	static protected function makeWhereForSubstitutes(array $columnValues, array &$substitutes)
	{
        $sqlWhere = '';
        $and = '';
        $substitutes = array();

        foreach ($columnValues as $column => $values) {
        	$column = self::toColname($column);
            $sqlWhere .= $and;
            $and = ' AND ';
            $sqlWhere .= "($column ";
            $numberOfValues = count($values);
        	if ($numberOfValues === 1) {
				$sqlWhere .= "=  ? )";
			} elseif ( $numberOfValues < 1000 ) { // Oracle only supports upto 1000 literals in the IN clause (see BZ#27945).
				$sqlWhere .= " IN ( ?" . str_repeat(', ?', ($numberOfValues - 1)) . ')) ';
			} else {  // More than 1000 literals
				$or = '';
				for ( $offset = 0; $offset < $numberOfValues; $offset += 1000 ) {
					$subArray = array_slice( $values, $offset, 1000 );
					$subArrayCount = count( $subArray );
					$sqlWhere .= $or;
					$sqlWhere .= " IN ( ?" . str_repeat(', ?', ($subArrayCount - 1)) . ') ';
					$or = "OR $column ";
				}				
				$sqlWhere .= ') ';
			}
			$substitutes = array_merge($substitutes, $values);
		}
		
        if (!empty($sqlWhere)) {
        	$sqlWhere = '( '.$sqlWhere.' )';
		}
		
        return $sqlWhere;
	}
	
	/**
	 * Returns the default column name of the auto-increment field. 
	 */
	static public function getAutoincrementColumn()
	{
		return 'id';
	}

	/**
	 * Splits major.minor version property into two array items.
	 * This is used to convert objects to DB rows.
	 * When NULL, no action is taken.
	 *
	 * @param string $versionProp Version property in major.minor notation
	 * @param array $row Array with key/vlue pairs containing the major and minor version.
	 * @param string $fieldPrefix Prefix for "majorversion" and "minorversion" field names.
	 */
    static protected function splitMajorMinorVer( $versionProp, &$row, $fieldPrefix )
	{
		if( !is_null($versionProp) ) {
			$parts = explode( '.', $versionProp );
			$valid = $parts && count( $parts ) > 1;
			$major = $valid ? intval($parts[0]) : 0;
			$minor = $valid ? intval($parts[1]) : 0;
			if( $major < 0 || $minor < 0 ) {
				$major = 0;
				$minor = 0;
			}
			$row[$fieldPrefix.'majorversion'] = $major;
			$row[$fieldPrefix.'minorversion'] = $minor;
		}
	}
	
	/**
	 * Formats major.minor version
	 * When NULL, no action is taken.
	 *
	 * @param string $versionProp Returns joined version property in major.minor notation
	 * @param array $row DB row containing "majorversion" and "minorversion" key-params
	 * @param string $fieldPrefix Prefix for "majorversion" and "minorversion" field names.
	 */
    static protected function joinMajorMinorVer( &$versionProp, $row, $fieldPrefix )
    {
    	if( isset($row[$fieldPrefix.'majorversion']) && isset($row[$fieldPrefix.'minorversion']) ) {
    		$major = intval($row[$fieldPrefix.'majorversion']);
    		$minor = intval($row[$fieldPrefix.'minorversion']);
    		if( $major < 0 || $minor < 0 ) {
    			$major = 0;
    			$minor = 0;
    		}
    		$versionProp = $major.'.'.$minor;
    	}
    }
	
	/**
	 * Inserts records with the new params for passed columns.
	 * @param string $table table name
	 * @param string $dbIntClass DB Integrity class name
	 * @param array $newValues column/value pairs of the columns to be inserted.
	 * @param boolean $autoIncrement Apply auto increment for primary key (true/false).
	 * @param $logExistsErr boolean Log 'already exists' errors. If set to false no error is logged for an insert operations for which this error is fine.
	 * @return new id or else false.
	 */
	protected static function doInsert( $table, $dbIntClass, array $newValues, $autoIncrement, $logExistsErr=true )
	{
		self::clearError();
		
		$intDB = new $dbIntClass;
        $intDB->beforeInsert( $newValues );
        
		$result = self::insertRow( $table, $newValues, $autoIncrement, null, $logExistsErr );

        $intDB->afterInsert( $result, $newValues );

        return $result;
	}	    
    
	/**
	 * Deletes records .....
	 *
	 * @param array $whereParams column/array of value pairs for where clause
	 * @param string $table Table from which records are deleted.
	 * @param string $keyColumn  Used as keys at the result array. Leave empty to use [0...N-1] array keys.
	 * @param string $dbIntClass Class name to instantiate the integrity object from.
	 * @return number of records updated or null in case of error.
	 */	
	protected static function doDelete(array $whereParams, $table, $keyColumn, $dbIntClass)
	{
		self::clearError();
		
        $params = array();
        $where = self::makeWhereForSubstitutes($whereParams, $params);
        $rows = self::listRows($table, $keyColumn, '', $where, false, $params);
		
        if ($rows === null) {
        	return null;
        }
        elseif (empty($rows)) { 
        	return 0;
		}

		$rowIds = array_keys($rows);
		$intDB = new $dbIntClass;
		$intDB->setIDs($rowIds);
        $intDB->beforeDelete();
        
		$whereParams = array($keyColumn => $rowIds);
		$where = self::makeWhereForSubstitutes($whereParams, $params);
		self::deleteRows($table, $where, $params);

        $intDB->afterDelete();

        return count($rowIds);
	}

	/**
	 * Updates records with the new params for passed columns.
	 * @param array $whereParams column/array of value pairs for where clause
	 * @param string $table Table from which records are deleted.
	 * @param string $keyColumn  Used as keys at the result array. Leave empty to use [0...N-1] array keys.
	 * @param string $dbIntClass Class name to instantiate the integrity object from.
	 * @param array $newValues column/value pairs of the columns to be updated.
	 * @return number of records updated or null in case of error.
	 */
	protected static function doUpdate(array $whereParams, $table, $keyColumn, $dbIntClass, array $newValues)
	{
		self::clearError();
		
        $params = array();
        $where = self::makeWhereForSubstitutes($whereParams, $params); 
        $rows = self::listRows($table, $keyColumn, '', $where, false, $params);
		
        if ($rows === null) {
        	return null;
        }
        elseif (empty($rows)) { 
        	return 0;
        }
        	
		$rowIds = array_keys($rows);
		$intDB = new $dbIntClass;
		$intDB->setIDs( $rowIds );
		$intDB->setUpdateValues($newValues);
		$intDB->beforeUpdate();
        
		//Just past the key params of the records to be updated
		$whereParams = array($keyColumn => $rowIds);
		$where = self::makeWhereForSubstitutes($whereParams, $params);
		self::updateRow($table, $newValues, $where, $params);

        $intDB->afterUpdate();

        return count($rowIds);
	}

	/**
 	* Executes a SQL statement to count the number of records in the $table given.
 	*
 	* @param string $table DB table name  ( e.g objects,  deletedobjects )
 	* @param string $fieldName The DB field name (column name) to count the records.
 	* @return Number of records. Return 0 when there are no records.
 	*/
	static public function countRecordsInTable( $table, $fieldName )
	{
		$dbh = DBDriverFactory::gen();
		$sql = 'SELECT  COUNT( ' .$fieldName. ' ) as `c` FROM '.$dbh->tablename($table); // For MYSQL, MSSQL & ORACLE
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		return $row['c'];
	}

	/**
	 * This method converts an array with integer params to a where clause.
	 * If the array contains one element the '=' operator is used else the 'in'
	 * operator will be used. If the number of passed array elements exceeds 1000
	 * then more than one IN-clause is generated.This is because of Oracle limitations.
	 * This is a very exceptional case.
	 *
	 * @param string $fieldname database column name
 	 * @param array (integer) $arrayValues params to filter on
 	 * @param bool $not to negate the params
	 * @return string which can be added to where clause
	 */
	static public function addIntArrayToWhereClause( $fieldname, $arrayValues, $not )
	{
		$where = '';

		if( is_array($arrayValues) && !empty($arrayValues) ) {
			$count = count( $arrayValues );
			$alias = explode('.', $fieldname);
			if (count( $alias ) === 2) { // alias is used e.g. o.id
				$fieldname = $alias[0].'.'."`$alias[1]`";  
			} else {
				$fieldname = "`$fieldname`";  
			}			
			if( $count === 1 ) {
				$notOperator = $not ? '!' : ''; 
				$where = "$fieldname $notOperator= " . array_shift($arrayValues) . ' ' ;
			} elseif ($count < 1000 ) { // Oracle only supports upto 1000 literals in the IN clause (see BZ#27945).
				$notOperator = $not ? 'NOT' : ''; 
				$where = "$fieldname $notOperator IN (" . implode(',', $arrayValues) . ') ';
			} else { // More than 1000 literals
				$notOperator = $not ? 'NOT' : ''; 
				$or = '';
				for ( $offset = 0; $offset < $count; $offset += 1000 ) {
					$subArray = array_slice($arrayValues, $offset, 1000);
					$where .= $or;
					$where .= "$fieldname $notOperator IN (" . implode( ',', $subArray ) . ') ';
					$or = 'OR ';
				}
			}
		}
		
		return $where;
	}	

	/**
	 * Views contain only the ids of objects. This method returns the ids of $rhs view with are not in the $lhs view.
	 * 
	 * @param string Identifier of $rhsView.
	 * @param string Identifier of $lhsView.
	 * @return array Array with all object ids contained by the right hand view but not in the left hand view.
	 */
	static public function diffOfViews( $rhsView, $lhsView )
	{
		require_once BASEDIR . '/server/dbclasses/DBQuery.class.php';
        $dbDriver = DBDriverFactory::gen();
        $rhsIds = DBQuery::getTempIds( $rhsView );
        $lhsIds = DBQuery::getTempIds( $lhsView );

		$sql = 	'SELECT rhs.`id` as `rhsid` '.
				"FROM $rhsIds rhs ".
				"LEFT JOIN $lhsIds lhs ON ( lhs.`id` = rhs.`id` ) ".
				'WHERE lhs.`id` IS NULL ';
		$sth = $dbDriver->query( $sql ); 	
		$rows = DBBase::fetchResults( $sth, 'rhsid' );
		$objIds = array_keys( $rows );
		
		return $objIds;
	}	
}