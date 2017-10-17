<?php

define('DB_ERROR',                     -1); // Unkown error
define('DB_ERROR_SYNTAX',              -2); // Syntax error
define('DB_ERROR_CONSTRAINT',          -3); // Tried to insert a duplicate value into a primary or unique index
define('DB_ERROR_NOT_FOUND',           -4); // An identifier in the query refers to a non-existant object
define('DB_ERROR_ALREADY_EXISTS',      -5); // Tried to create a duplicate object
define('DB_ERROR_UNSUPPORTED',         -6); // The current driver does not support the action you attempted
define('DB_ERROR_MISMATCH',            -7); // The number of parameters does not match the number of placeholders
define('DB_ERROR_INVALID',             -8); // A literal submitted did not match the data type expected
define('DB_ERROR_NOT_CAPABLE',         -9); // The current DBMS does not support the action you attempted
define('DB_ERROR_TRUNCATED',          -10); // A literal submitted was too long so the end of it was removed
define('DB_ERROR_INVALID_NUMBER',     -11); // A literal number submitted did not match the data type expected
define('DB_ERROR_INVALID_DATE',       -12); // A literal date submitted did not match the data type expected
define('DB_ERROR_DIVZERO',            -13); // Attempt to divide something by zero
define('DB_ERROR_NODBSELECTED',       -14); // A database needs to be selected
define('DB_ERROR_CANNOT_CREATE',      -15); // Could not create the object requested
define('DB_ERROR_CANNOT_DROP',        -17); // Could not drop the database requested because it does not exist
define('DB_ERROR_NOSUCHTABLE',        -18); // An identifier in the query refers to a non-existant table
define('DB_ERROR_NOSUCHFIELD',        -19); // An identifier in the query refers to a non-existant column
define('DB_ERROR_NEED_MORE_DATA',     -20); // The data submitted to the method was inappropriate
define('DB_ERROR_NOT_LOCKED',         -21); // The attempt to lock the table failed
define('DB_ERROR_VALUE_COUNT_ON_ROW', -22); // The number of columns doesn't match the number of values
define('DB_ERROR_INVALID_DSN',        -23); // The DSN submitted has problems
define('DB_ERROR_CONNECT_FAILED',     -24); // Could not connect to the database
define('DB_ERROR_EXTENSION_NOT_FOUND',-25); // The PHP extension needed for this DBMS could not be found
define('DB_ERROR_ACCESS_VIOLATION',   -26); // The present user has inadequate permissions to perform the task requestd
define('DB_ERROR_NOSUCHDB',           -27); // The database requested does not exist
define('DB_ERROR_CONSTRAINT_NOT_NULL',-29); // Tried to insert a null value into a column that doesn't allow nulls
define('DB_ERROR_DEADLOCK_FOUND',     -30); // Deadlock found when trying to get lock

abstract class WW_DbDrivers_DriverBase
{
	/**
	 * Writes the given SQL statement to the server log file.
	 * It adds on log line with the caller of the dbdriver (class and function)
	 * to give an indication of the context to ease lookup PHP code that composed the SQL.
	 *
	 * @since 9.7.0
	 * @param string $area Logging area: mysql, mssql or oracle
	 * @param string $sql SQL statement to be logged. Exclude blob data.
	 * @param integer|null $rowCnt Number of rows (on select) or number of affected rows (on update, delete, etc). NULL to skip log.
	 * @param string $class The calling class name. Should be __CLASS__
	 * @param string $function The calling function name. Should be __FUNCTION__
	 * @param float $execDuration Since 10.2. Time it took to execute the SQL in microseconds.
	 */
	protected function logSql( $area, $sql, $rowCnt, $class, $function, $execDuration )
	{
		PerformanceProfiler::startProfile( 'query logging', 5 );

		// Add SQL to log.
		$log = "SQL: {$sql}\r\n";
		
		// Add caller (context) to log.
		require_once BASEDIR.'/server/utils/PHPClass.class.php';
		$plies = defined('LOGSQL_CALLERS') ? LOGSQL_CALLERS : 1;
		if( $plies ) {
			$skipClasses = array( 'DBBase', 'WW_DbDrivers_DriverBase', 'mysqlidriver', 'mssqldriver' );
			$stackEntries = WW_Utils_PHPClass::getCallers( $class, $function, 
							$skipClasses, $plies );
			if( $stackEntries ) foreach( $stackEntries as $stackEntry ) {
				$log .= '=> Context: ';
				if( isset($stackEntry['class']) ) {
					$log .= $stackEntry['class'];
				}
				if( isset($stackEntry['type']) ) {
					$log .= $stackEntry['type'];
				}
				if( isset($stackEntry['function']) ) {
					$log .= $stackEntry['function'];
				}
				$log .= "(...)\r\n";
			}
		}
		
		// Add row count to log.
		if( !is_null($rowCnt) ) {
			$log .= "=> Number of selected/affected rows: {$rowCnt}\r\n";
		}
		$log .= '=> Execution time: '.sprintf( '%.3f', $execDuration * 1000 ).'ms'."\r\n";

		// Log SQL, caller and row count at once.
		LogHandler::Log( $area, 'INFO', $log );

		PerformanceProfiler::stopProfile( 'query logging', 5 );
	}

	/**
	 * Replaces placeholders (question marks) by their values in a given SQL statement.
	 *
	 * @since 9.7.0 Moved to this parent class and added {@link:preSerializeParam()} support.
	 * @param string $sql SQL statement with placeholders (?)
	 * @param array $params Parameter values to replace
	 * @return string SQL statement with replaced values. 
	 * @throws BizException when bad arguments provided by caller.
	 */
	public function substituteParams( $sql, $params )
	{
		// Bail out when no parameter replacement needed.
		if( count($params) == 0 ) {
			return $sql;
		}
		
		// Error when given parameter collection has bad format.
		if( !is_array($params) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server',
				'Array expected for $param parameter.' );
		}
		
		// Error when placeholders and parameters mismatch.
		if( substr_count( $sql, '?' ) !== count($params) ) {
			$detail = 'The number of parameters does not match the number of placeholders.';
			if( LogHandler::debugMode() ) { // reveal more details in debug mode
				$detail .= " => SQL: [$sql] PARAMS: [".implode( ',', $params )."]";
			}
			throw new BizException( 'ERR_ARGUMENT', 'Server', $detail );
		}
		
		// Iterate through the provided parameters and substitute one by one.
		$sqlParts = explode( '?', $sql.' ' ); // add space to make sure there is one more sql part than '?' place holders
		$substSql = '';
		$key = 0;
		foreach( $params as $key => $value ) {

			// Allow driver to overrule the parameter substitution or do something extra before.
			$serialized = $this->preSerializeParam( $key, $value, $sql, $sqlParts[$key], $substSql ); // updates $substSql
			
			// Do the generic SQL parameter substitution.
			if( !$serialized ) { // not overruled / already done?
				if( is_string( $value ) ) {
					$value = "'" . $this->toDBString($value) . "'";
				} else {
					if( is_object($value) || is_array($value) ) {
						throw new BizException( 'ERR_ARGUMENT', 'Server', 
							'A literal submitted did not match the data type expected.' );
					}
					$value = strval( $value );
				}
				$substSql .= $sqlParts[$key].$value;
			}

			$key += 1;
		}
		$substSql .= $sqlParts[$key];
		return $substSql;
	}

	/**
	 * Quotes an identifier (e.g. the name of a column or table) in Enterprise sql-style. So this style is DBMS
	 * independent.
	 *
	 * @param string $name Unquoted identifier.
	 * @return string quoted identifier.
	 */
	private function quoteIdentifierDBInDependent( $name )
	{
		return '`'.$name.'`';
	}

	/**
	 * Composes a string of quoted column names. The column are quoted in Enterprise sql-style and separated by commas.
	 *
	 * @param string[] $columns Names of columns.
	 * @return string String with comma separated and quoted column names.
	 */
	protected function quoteColumnNames( array $columns )
	{
		return implode( ', ', array_map( array( $this, 'quoteIdentifierDBInDependent' ), $columns) );
	}

	/**
	 * Allows subclass to serialize a key-value parameter in SQL before the parent class does.
	 *
	 * @since 9.7.0
	 * @param integer $key The index of the parameter.
	 * @param string|integer|float|array $value
	 * @param string $sql The full SQL statement (executed after param subst).
	 * @param string $sqlLhs The SQL fragment Left Hand Side of the placeholder that is about to get subst, including the operator, if any.
	 * @param string $substSql The SQL statement with (partly) already subst params so far.
	 * @return bool Whether or not the SQL param subst is overruled (already done).
	 */
	protected function preSerializeParam(/** @noinspection PhpUnusedParameterInspection */ $key, $value, $sql, $sqlLhs, &$substSql )
	{
		return false;
	}

	/**
	 * Drops a database table with all its data.
	 *
	 * @param string $tableName Name of the table (without prefix).
	 */
	public function dropTable( $tableName )
	{
		PerformanceProfiler::startProfile( 'dropTable', 4 );
		$sql = $this->composeDropTableStatement( $tableName );
		$this->query( $sql );
		PerformanceProfiler::stopProfile( 'dropTable', 4 );
	}

	/**
	 * Drops a temporary database table with all its data.
	 *
	 * @param string $tableName Name of the temporary table.
	 */
	public function dropTempTable( $tableName )
	{
		PerformanceProfiler::startProfile( 'dropTable', 4 );
		$sql = $this->composeDropTempTableStatement( $tableName );
		$this->query( $sql );
		PerformanceProfiler::stopProfile( 'dropTable', 4 );
	}

	/**
	 * Escapes a string for parameter substitution in SQL statements.
	 *
	 * @param string $str String value to escape.
	 * @return string The escaped string value.
	 */
	abstract public function toDBString( $str );

	/**
	 * Returns a sql-statement to create an index for the specified table. The index consists of the specified columns.
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @param string $indexName Name of the new index.
	 * @param string[] $columns Names of the columns to create an index.
	 * @return string sql-statement.
	 */
	abstract public function composeCreateIndexStatement( $tableName, $indexName, array $columns );

	/**
	 * Returns a sql-statement to drop the specified table.
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @return string sql-statement.
	 */
	abstract public function composeDropTableStatement( $tableName );

	/**
	 * Returns a statement to drop the index of a specified table.
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @param string $indexName Name of the new index.
	 * @return string sql-statement.
	 */
	abstract public function composeDropIndexStatement( $tableName, $indexName );

	/**
	 * Creates a new table based on the table definition of the 'from' table. Also all data is copied.
	 *
	 * @param string $fromTable Name of the table acting as source.
	 * @param string $toTable Name of the new table.
	 * @return boolean true on success else false.
	 */
	abstract public function copyTable( $fromTable, $toTable );

	/**
	 * Returns an identifier with the right 'quotes' (format) for a specific DBMS.
	 *
	 * @param string $identifier
	 * @return string Identifier 'quoted' in the right format.
	 */
	abstract public function quoteIdentifier( $identifier );

	/**
	 * Creates a statement to drop a temporary table.
	 *
	 * @param string $tableName Name of the temporary table.
	 * @return string sql-statement.
	 */
	abstract public function composeDropTempTableStatement( $tableName );

	/**
	 * Checks if a table exists in the database.
	 *
	 * @param string $tableName Name of the table
	 * @param boolean $addPrefix Add the WoodWing table prefix to the table name.
	 * @return boolean
	 */
	abstract public function tableExists( $tableName, $addPrefix = true );

	/**
	 * If a deadlock is encountered repeat the transaction again.
	 * The transaction is repeated after waiting $milliseconds milliseconds.
	 *
	 * @since 10.1.4
	 * @param string|resource $sql
	 * @param int $milliseconds number of milliseconds before retrying.
	 * @return mixed
	 */
	abstract protected function retryAfterLockError( $sql, $milliseconds = 50 );

	/**
	 * Performs a given SQL query at the database.
	 *
	 * @param string $sql   The SQL statement.
	 * @param array $params Parameters for substitution
	 * @param mixed $blob   Chunk of data to store at the database. One blob can be passed or multiple. If multiple blobs
	 * are passed $blob is an array.
	 * @param boolean $writeLog In case of license related queries, this option can be used to not write in the log file.
	 * @param boolean $logExistsErr Suppress 'already exists' errors. Used for specific insert operations for which this error is fine.
	 * @return resource|bool|null DB handle that can be used to fetch results. Null when SQL failed.
	 * False when record already exists, in case $logExistsErr is set false.
	 */
	abstract public function query( $sql, $params=array(), $blob=null, $writeLog=true, $logExistsErr=true );

	/**
	 * Fetch a result row as an associative array from the database.
	 *
	 * @param resource $sth The result resource that is being evaluated. This result comes from a call to {@link: query()}.
	 * @return array Associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
	 */
	abstract public function fetch( $sth );

	/**
	 * Adds prefix to the the table name and puts DBMS specific quotes around it.
	 *
	 * @param string $tableNameNoPrefix
	 * @return string Table name with prefix and quoted.
	 */
	abstract	public function tablename( $tableNameNoPrefix );

	/**
	 * Composes a SQL fragment that can be used to get multiple columns as a concatenated string from the database.
	 *
	 * @param string[] $arguments List of arguments (mainly field names) that should be concatenated.
	 * @return string SQL fragment
	**/
	abstract public function concatFields( $arguments );

	/**
	 * The string datatypes used by the DBMS are multibyte aware.
	 *
	 * Returns true if the length of a string datatype refers to number of multibyte characters that can be stored
	 * instead of the number of bytes that can be stored. So a length of 4 means in case of multibyte support that a
	 * field can contain 4 multibyte characters. If multibyte is not supported it can store 4 bytes which is only
	 * 2 characters in case each character takes 2 bytes.
	 *
	 * @return boolean True if DBMS is multibyte aware, else false.
	 */
	abstract public function hasMultibyteSupport();

	/**
	 * Changes the SQL ($slq) in such a way that it returns $count rows starting from $start row onwards.
	 *
	 * @param string $sql SQL statement before the limit clause is added.
	 * @param int $start The offset of the first row to return.
	 * @param int $count The maximum number of rows to return.
	 * @return string The SQL statement with the limit clause added.
	 */
	abstract public function limitquery($sql, $start, $count);

	/**
	 * Returns the last error message returned by the DBMS.
	 *
	 * @return string
	 */
	abstract public function error();

	/**
	 * Returns the last error code returned by the DBMS.
	 *
	 * Translates certain DBMS specific errors to DBMS agnostic error codes. For all other error codes the DBMS specific
	 * code is returned.
	 *
	 * @return int
	 */
	abstract public function errorcode();

	/**
	 * Updates, if needed, the autoincrement value of a specific table by modifying the SQL statement.
	 *
	 * @param string $sql
	 * @return string Modified SQL-statement or the original statement when no action is needed.
	 */
	abstract public function autoincrement($sql);

	/**
	 * Returns the (next) autoincrement value.
	 *
	 * If a record is added to a table with an autoincrement column this method returns the autoincrement value of the
	 * added record. This is the case that the method is called directly after ($after = true) the record is added.
	 * In case the method is called before a record is added ($after = false) this method returns the next autoincrement
	 * value. This second case is not supported by all DBMS.
	 *
	 * @param string $table
	 * @param bool $after
	 * @return bool|int New id or else false.
	 */
	abstract public function newid($table, $after);


}

class DBDriverFactory
{
	/**
	 * Database driver factory.
	 * Creates one of the following database driver objects: mysqldriver, mssqldriver or oracledriver.
	 * Always use this factory to access a database; Never create a specific driver yourself.
	 *
	 * @param $dbType   string  Database type. 'mysql' or 'mssql'. Default value: DBTYPE config option.
	 * @param $dbServer string  Server machine name that run the database. Default value: DBSERVER config option.
	 * @param $dbUser   string  Database user name used for for DB connection. Default value: DBUSER config option.
	 * @param $dbPass   string  Database user password used for for DB connection. Default value: DBPASS config option.
	 * @param $dbSelect string  Database name to connect with. Default value: DBSELECT config option.
	 * @param $throwException bool Whether or not to throw BizException on connection error.
	 * @return  WW_DbDrivers_DriverBase Database driver object.
	 * @throws BizException On DB configuration error or DB connection error (since v8.0).
	 */
	static public function gen( $dbType = DBTYPE, $dbServer = DBSERVER, 
		$dbUser = DBUSER, $dbPass = DBPASS, $dbSelect = DBSELECT, $throwException = true )
	{
		static $thisDbDriver; // DB driver cache
		// Note: The cache was made multi-dimensional (since v7.0) to allow multiple DB connections. 
		//       This is way, the factory became reusable to connector to non-Enterprise databases too, 
		//       which is typically useful for DataSource integrations.
		if (isset($thisDbDriver[$dbType][$dbServer][$dbUser][$dbSelect])) {
			return $thisDbDriver[$dbType][$dbServer][$dbUser][$dbSelect];
		}
	
		switch( $dbType ) {
			case 'mysql':
				require_once BASEDIR.'/server/dbdrivers/mysqlidriver.php';
				$dbDriver = new mysqlidriver( $dbServer, $dbUser, $dbPass, $dbSelect );
				break;
			case 'mssql':
				require_once BASEDIR.'/server/dbdrivers/mssqldriver.php';
				$dbDriver = new mssqldriver( $dbServer, $dbUser, $dbPass, $dbSelect );
				break;
			default:
				throw new BizException( 'ERR_DATABASE', 'Server', 'Invalid database type: "'.$dbType.'".' );
				break;
		}
		
		// Cache the created driver object to re-use for all queries within the same session (service request).
		if( $dbDriver->isConnected() ) {
			$thisDbDriver[$dbType][$dbServer][$dbUser][$dbSelect] = $dbDriver;
		} elseif( $throwException ) { // DB connection error
			// Note: This could happen in extreme cases when there is a query running for another
			//       user that is keeping the DB for 99% busy. In that case, avoid throwing an
			//       invalid ticket error (which would happen) since that makes clients think their
			//       ticket needs to be renewed and so raising the logon dialog.
			$code = trim($dbDriver->errorcode());
			$error = trim($dbDriver->error());
			$detail = $error ? $error : 'Database error';
			$detail .= $code ? ' ('.$code.')' : '';
			throw new BizException( 'ERR_COULD_NOT_CONNECT_TO_DATEBASE', 'Server', $detail );
		}
		return $dbDriver;
	}

	/**
	 * Compose a list of internal names of DB drivers supported by Enterprise Server.
	 *
	 * Note that since 10.2.0 Oracle support has dropped.
	 *
	 * @since 10.2.0
	 * @return string[]
	 */
	static public function getSupportedDrivers()
	{
		return array( 'mysql', 'mssql' );
	}
}