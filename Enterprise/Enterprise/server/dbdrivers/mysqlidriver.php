<?php
/**
 * Translates DBMS independent calls to MySQL specific calls.
 *
 * @package 	Enterprise
 * @subpackage 	DBDrivers
 * @since 		v3.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class mysqlidriver extends WW_DbDrivers_DriverBase
{
	public $dbh = null; // handle to the database (TODO: make it private)
	private $errormessage = null;
	private $theerrorcode = null;
	
	public function __construct( $dbserver = DBSERVER, $dbuser = DBUSER, $dbpass = DBPASS, $dbselect = DBSELECT )
	{
		PerformanceProfiler::startProfile( 'db connect (mysql)', 4 );
		if( $this->isPhpDriverExtensionLoaded() ) {
			$parts = explode( ':', $dbserver );
			if( count( $parts ) > 1 ) {
				$dbserver = $parts[0];
				$port = (int)$parts[1];
				$socket = null;
			} else {
				if ( substr( $dbserver, 0, 1 ) == '/' ) { // Socket
					$socket = $dbserver;
					$dbserver = 'localhost';
					$port = null;
				} else {
					$socket = null;
					$port = (int)ini_get('mysqli.default_port');
				}
			}
			PerformanceProfiler::startProfile( 'mysql_connect', 5 );
			$this->dbh = new mysqli( $dbserver, $dbuser, $dbpass, $dbselect, $port, $socket );
			PerformanceProfiler::stopProfile( 'mysql_connect', 5 );
	
			if( $this->isConnected() ) {
				// Set in and out encoding to UTF-8, this also sets collation to DB collation:
				$sql = "SET CHARACTER SET 'utf8'";
				$this->dbh->query( $sql );
			} else { // error
				$this->setError();
				$this->dbh = null;
			}
		}
		PerformanceProfiler::stopProfile( 'db connect (mysql)', 4 );
	}

	public function __destruct()
	{
		PerformanceProfiler::startProfile( 'db close (mysql)', 4 );
		if( $this->isConnected() ) {
			PerformanceProfiler::startProfile( 'mysql_close', 5 );
			$this->dbh->close();
			PerformanceProfiler::stopProfile( 'mysql_close', 5 );
		}
		PerformanceProfiler::stopProfile( 'db close (mysql)', 4 );
	}
	
	/**
	 * Tells whether the database could be connected during class constructor.
	 *
	 * @return bool TRUE when connected, else FALSE.
	 */
	public function isConnected()
	{
		return $this->dbh && !$this->dbh->connect_error;
	}

	/**
	 * Tells whether the PHP extension for the DB is loaded.
	 *
	 * @return bool TRUE when loaded, else FALSE.
	 */
	public function isPhpDriverExtensionLoaded()
	{
		return extension_loaded( 'mysqli' ) && class_exists( 'mysqli' );
	}
	
	/**
	 * @inheritDoc
	 */
	public function query( $sql, $params=array(), $blob=null, $writeLog=true, $logExistsErr=true )
	{
		PerformanceProfiler::startProfile( 'db query (mysql)', 4 );
		
		try {
			$sql = self::substituteParams($sql, $params);
		}
		catch (Exception $e) {
			LogHandler::Log('mysql', 'ERROR', $e->getMessage());
			return null;
		}
		$cleanSql = $sql; // remember for logging (before adding blob data)
		$logSQL = $writeLog && ( LogHandler::debugMode() || LOGSQL == true ); // BZ#14442

		// handle blobs
		if( is_null($blob) ) {
			if( LogHandler::debugMode() ) {
				if( strstr( $sql, '#BLOB#' ) || strstr( $sql, '#LARGEBLOB#' ) ) {
					LogHandler::Log( 'mysql', 'ERROR', 'BLOB placeholder found at SQL, but no BLOB param provided.' );
				}
			}
		} else {
			if( !is_array( $blob ) ) {
				$blob = array( $blob );
			} 
			if( is_array( $blob ) ) {
				foreach ( $blob as $key => $blobItem ) {
					$blobstr = $this->dbh->real_escape_string( $blobItem ); // do expensive replacements only once
					// log blob, only in debug mode
					if( $logSQL ) {	
						$postfix = strlen( $blobstr ) > 250 ? '...' : '';
						$blobLog = substr( $blobstr, 0, 250 );
						$blobLog = (LOGFILE_FORMAT == 'html') ? htmlentities( $blobLog ) : $blobLog;
						LogHandler::Log('mysql', 'INFO', 'BLOB: ['.$blobLog.$postfix.']' );
					}
					$blob[$key] = "'$blobstr'"; 
				}
			}
			if( strstr( $sql, '#BLOB#' ) ) {
				// Replace the #BLOB" placeholder by the actual values.
				$sqlParts = explode('#BLOB#', $sql);
				$placeHoldersCount = count($sqlParts) - 1; // 3 #BLOB# placeholders result into 4 SQL parts
				if( count( $blob ) != $placeHoldersCount ) {
					LogHandler::Log('mysql', 'ERROR', 'The number of blob parameters '.count( $blob ).
						' does not match the number of #BLOB# placeholders '.$placeHoldersCount.' at SQL.');
				}
				foreach ( $blob as $key => $blobItem ) {
					$sqlParts[$key] .= $blobItem;
				}
				$sql = implode( ' ', $sqlParts );
			} else if( strstr( $sql, '#LARGEBLOB#' ) ) {
				$sql = str_replace( "#LARGEBLOB#", $blob[0], $sql );
				if( count($blob) > 1 ) {
					LogHandler::Log( 'mysql', 'ERROR', 'Multiple LARGE BLOBs not supported.' );
				}
			} else {
				LogHandler::Log( 'mysql', 'ERROR', 'BLOB param given, but no BLOB placeholder found at SQL.' );
			}			
		}
		
		// Perform the query.
		PerformanceProfiler::startProfile( 'mysql_query', 5 );
		$result = $this->dbh->query( $sql );
		PerformanceProfiler::stopProfile( 'mysql_query', 5 );

		// Log SQL (without blob data).
		if ( $logSQL ) {
			$rowCnt = isset($this->dbh->num_rows) ? $this->dbh->num_rows : 0;
			if( !$rowCnt ) {
				$rowCnt = isset($this->dbh->affected_rows) ? $this->dbh->affected_rows : 0;
			}
			$this->logSql( 'mysql',
				LOGFILE_FORMAT == 'html' ? htmlentities( $cleanSql ) : $cleanSql,
				$rowCnt,
				__CLASS__,
				__FUNCTION__ );
		}
		
		PerformanceProfiler::stopProfile( 'db query (mysql)', 4 );

		// Handle errors.
		if (!$result) {
			$this->setError();
			if( !$logExistsErr && // suppress already exists errors ?
				($this->errorcode() == DB_ERROR_ALREADY_EXISTS || 
				 $this->errorcode() == DB_ERROR_CONSTRAINT )) {
				return false;
			} elseif( $this->errorcode() == DB_ERROR_DEADLOCK_FOUND || $this->errorcode() == DB_ERROR_NOT_LOCKED ) {
				return $this->retryAfterLockError( $sql, 50 );
			}
			if ( $writeLog ) {
				LogHandler::Log('mysql', 'ERROR', $this->errorcode().':'.$this->error());
			}
			return null;
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	protected function retryAfterLockError( $sql, $milliseconds = 50 )
	{
		LogHandler::Log( 'mysql', 'INFO', '(Dead)lock error encountered: Execute statement again.' );
		if( LogHandler::debugMode() ) {
			LogHandler::Log( 'mysql', 'DEBUG', $this->theerrorcode.': '.$this->error() );
		}
		$maxRetries = 3;
		for( $retry = 1; $retry <= $maxRetries; $retry++ ) {
			LogHandler::Log( 'mysql', 'INFO', "(Dead)lock: Retry attempt {$retry} of {$maxRetries}." );
			usleep( $milliseconds * 1000 );
			$result = $this->dbh->query( $sql );
			if( !$result ) {
				LogHandler::Log( 'mysql', 'WARN', '(Dead)lock: Retry of statement failed once again.' );
				$result = null;
			} else {
				LogHandler::Log( 'mysql', 'INFO', '(Dead)lock: Retry of statement was successful.' );
				break;
			}
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function fetch( $sth )
	{
		//PerformanceProfiler::startProfile( 'db fetch (mysql)', 4 );
		if( $sth /*&& $sth->num_rows*/ ) { // Commented out the $sth->num_rows check since it is more robust to let fetch_assoc() fail when no more rows available.
			$ret = $sth->fetch_assoc();
			// Note that fetch_assoc() returns an associative array of strings on success 
			// or NULL if there are no more rows in resultset.
			// That is different than our fetch() interface so we have to change NULL into FALSE.
			// This fix is important for code that checks on isset($row).
			if( is_null($ret) ) {
				$ret = false;
			}
		} else {
			$ret = false;
		}
		//PerformanceProfiler::stopProfile( 'db fetch (mysql)', 4 );
		return $ret;
	}

	/**
	 * Returns list of DB fields and its DB field type.
	 *
	 * @param resource $sth DBdriver handler to fetch DB row results.
	 * @return array List of Db column and its Db type.
	 */
	public function tableInfo( $sth )
	{
		if (!$sth) return array();

		$cols = array();
		PerformanceProfiler::startProfile( 'mysql_tableInfo', 4 );
		while( ($fieldInfo = $sth->fetch_field()) ) {
			$cols[] = array(
				'name'  => $fieldInfo->name,
				'type'  => $fieldInfo->type,
			);
		}
		PerformanceProfiler::stopProfile( 'mysql_tableInfo', 4 );
		return $cols;
	}
	
	public function listcolumns( $sth )
	{
		$columns = array();
		$i = 0;
		while( ($fieldInfo = $sth->fetch_field()) ) {
			$colname = $fieldInfo->name;
			$column = array();
			$column['name'] = $colname;
			$column['index'] = $i;
			$column['dbtype'] = $fieldInfo->type;
			$column['len'] = $fieldInfo->length;
			$column['flags'] = $fieldInfo->flags;
			$column['qouted'] = true;
			$column['isblob'] = false;
			$columns[$colname] = $column;
			$i += 1;
		}
		return $columns;
	}
	
	/**
	 * Checks if a given database table exists in the current table space.
	 *
	 * @param string $tableName
	 * @param boolean $addPrefix Add the WoodWing prefix to the table name.
	 * @return boolean Whether or not the table exists.
	 */
	public function tableExists( $tableName, $addPrefix = true )
	{
		$tableName = $addPrefix ? DBPREFIX.$tableName : $tableName;
		$sql = 'SHOW TABLES LIKE \''.$tableName.'\'';
		$sth = $this->query( $sql );
		$row = $this->fetch( $sth );
		return (bool)$row;
	}

	/**
	 * Checks in database (defined in DBSELECT), if the field name exists in the passed in table name.
	 *
	 * @param string $tableName
	 * @param string $fieldName
	 * @return bool
	 */
	public function fieldExists( $tableName, $fieldName )
	{
		$sql = 'SELECT * FROM information_schema.COLUMNS ';
		$sql .= 'WHERE ';
		$sql .= 'TABLE_SCHEMA = \''.DBSELECT.'\' ';
		$sql .= 'AND TABLE_NAME = \''.DBPREFIX.$tableName .'\' ';
		$sql .= 'AND COLUMN_NAME = \''.$fieldName .'\' ';

		$sth = $this->query( $sql );
		$row = $this->fetch( $sth );

		return $row ? true : false;
	}

	/**
	 * @inheritDoc
	 */
	public function newid( $table, $after )
	{
		if( $after ) { // called AFTER insert statement
			return $this->dbh->insert_id;
		}
		return false;
	}

	// See mssqldriver->copyid for comments
	public function copyid( $table, $after )
	{
		// nothing to do
	}

	/**
	 * Sets internal error message and code
	 * Preferably, call this function before database connection is destroyed
	 *
	 */
	private function setError()
	{
		if( $this->dbh ) {
			if( $this->dbh->connect_errno ) {
				$this->errormessage = $this->dbh->connect_error;
				$this->theerrorcode = $this->dbh->connect_errno;
			} elseif( $this->dbh->errno ) {
				$this->errormessage = $this->dbh->error;
				$this->theerrorcode = $this->dbh->errno;
			}
		} else {
			$this->errormessage = '';
			$this->theerrorcode = '';
		}
	}

	/**
	 * @inheritDoc
	 */
	public function error()
	{
		return $this->errormessage;
	}

	/**
	 * @inheritDoc
	 */
	public function errorcode()
	{
		switch ( $this->theerrorcode ) {
			case 1062:
				return DB_ERROR_ALREADY_EXISTS;
			case 1213:
				return DB_ERROR_DEADLOCK_FOUND;
			default:
				return 'MySQL: '.$this->theerrorcode;
		}
	}

	public function quoteIdentifier($name)
	{
		return "`$name`";
	}

	/**
	 * @inheritDoc
	 */
	public function limitquery($sql, $start, $count)
	{
		return "$sql limit $start, $count";
	}

	/**
	 * @inheritDoc
	 */
	public function tablename( $tableNameNoPrefix )
	{
		$prefix = DBPREFIX;
		return "`{$prefix}{$tableNameNoPrefix}`";
	}

	/**
	 * @inheritDoc
	 */
	public function autoincrement($sql)
	{
		return $sql; // no auto increment needed
	}

	public function toDBString( $str )
	{
		return $this->dbh->real_escape_string( $str );
	}
	
	/*
     * Starts DB transaction, not supported by MySQL, so does nothing
     *
	 * @return FALSE
     */
	public function beginTransaction( )
	{
		return false;
	}
	
	/*
     * Commits DB transaction, not supported by MySQL, so does nothing
     *
	 * @return FALSE
     */
	public function commit()
	{
		return false;
	}
	
	/*
     * Rolls back DB transaction, not supported by MySQL, so does nothing
     *
	 * @return FALSE
     */
	public function rollback()
	{
		return false;
	}

	/**
	 * Returns the columnname by propname
	 * Normally these are the same but for Oracle this can differ so implemented (kind of) generic
	 *
	 * @param $propname
	 * @return string columnname
	**/
	
	public function getColumnName($propname)
	{
		return $propname;	
	}

	/**
	 * Returns 'now' (time stamp)
	 * @return string 
	**/
	public function nowStamp()
	{
		return 'now()';
	}

	/**
	 * @inheritDoc
	 **/
	public function concatFields( $arguments )
	{
		$sql = 'concat( ';
		$sep = '';
		foreach( $arguments as $argument ) {
			$sql .= $sep.$argument;
			$sep = ', ';
		}
		$sql .= ' ) ';
		return $sql;
	}
	
	public function createViewSQL()
	{
        return 'CREATE OR REPLACE ALGORITHM = TEMPTABLE VIEW ';
	}

	public function dropViews($viewnames)
	{
		PerformanceProfiler::startProfile( 'dropViews', 4 );
		foreach ($viewnames as $viewname) {
			
			$sql = "DROP VIEW $viewname";
			$this->query($sql);
		}
		PerformanceProfiler::stopProfile( 'dropViews', 4 );
	}

	/**
	 * Create authorized view temporary table base on temptable name
	 *
	 * @param string $tablename
	 * @return string $tablename of the created table
	 */
	public function createAuthorizedTempTable($tablename)
	{
		$tablename = $this->makeTempTableName($tablename);
		PerformanceProfiler::startProfile( 'createAuthorizedTempTable', 4 );
		$sql  = "CREATE TEMPORARY TABLE $tablename ( ";
		$sql .= "`publication` INT(11) NOT NULL, `issue` INT(11) NOT NULL, `section` INT(11) NOT NULL, `state` INT(11) NOT NULL, ";
		$sql .= "PRIMARY KEY (`publication`, `issue`, `section`, `state`) ) ";
		$this->query($sql);
		$sql = "CREATE INDEX ispusest ON $tablename(`issue`, `publication`, `section`, `state`) ";
		$this->query($sql);	
		
		PerformanceProfiler::stopProfile( 'createAuthorizedTempTable', 4 );
		return $tablename;
	}

	/**
	 * Method creates a copy of the passed temporary table ($source). 
	 * First the structure is copied and next all records of the original table
	 * are inserted into the copy.
	 * @param string $tablename name of the copy
	 * @param string $source name of the source table
	 * @return string name of the copy
	 */
	public function createCopyTempTable($tablename, $source)
	{
		$tablename = $this->makeTempTableName($tablename);
		$sql = "CREATE TEMPORARY TABLE $tablename LIKE $source";
		$this->query($sql);
		$sql = "INSERT $tablename SELECT * FROM $source";		
		$this->query($sql);
			
		return $tablename;		
	}	
	
	/**
	 * Returns a temporary table named $tablename which contains id's
	 * @param string $tablename name of the temporary table to create
	 * @return string $tablename of the created table
	**/
	public function createTempIdsTable($tablename)
	{
		$tablename = $this->makeTempTableName($tablename);
		PerformanceProfiler::startProfile( 'createTempIdsTable', 4 );
		$sql  = "CREATE TEMPORARY TABLE $tablename ( ";
		$sql .= "`id` INT(11) NOT NULL, ";
		$sql .= "PRIMARY KEY (`id`) ) ";
		$sql .= "ENGINE = MEMORY ";
		$this->query($sql);
		PerformanceProfiler::stopProfile( 'createTempIdsTable', 4 );
		return $tablename;
	}
	
	/**
	 * Truncates table named $tablename. Truncate is faster than 'DELETE'. 
	 * @param string $tablename name of the table
	 * @return nothing
	**/
	public function truncateTable($tablename)
	{
		PerformanceProfiler::startProfile( 'truncateTable', 4 );
		$sql  = "TRUNCATE TABLE $tablename";
		$this->query($sql);
		PerformanceProfiler::stopProfile( 'truncateTable', 4 );
	}

	/**
	 * Generates sql-statement to insert id's into temporary table.
	 * Expects rows with key 'id' and value id number.
	 *
	 * @param string $temptvi Identifier of temporary table.
	 * @param array $rows Array containing rows with the id's
	 * @return string SQL insert statement. 
	 */	
	public function composeMultiInsertTempTable($temptvi, $rows)
	{
		$insertsql = " INSERT INTO $temptvi (id) VALUES ";
		$comma = ' ';
		foreach ($rows as $row) {
			$insertsql .= $comma;
			$id = $row['id'];
			$insertsql .= "($id)";
			$comma = ',';
		}
		
		return $insertsql;
	}
	
	/**
	 * @retun array Database specific information for displaying purposes.
	 */
	public function getClientServerInfo()
	{
		$info = array();
		if( $this->isConnected() ) {
			$info['Database version'] = $this->dbh->get_server_info();
			$info['System status'] = $this->dbh->stat();
			$info['Client library version'] = $this->dbh->get_client_info();
			$info['MySQL protocol version'] = $this->dbh->protocol_version;
			$info['Connection info'] = $this->dbh->host_info;
		}
		return $info;
	}
	
	/**
	 * DBMS supports multiple references to temporary tables.
	 * @return true/false
	 */
	public function supportMultipleReferencesTempTable()
	{
		return false;
	}
	
	/**
	 * Returns proper name for temporary tables
	 * @param string $name of the temporary table
	 * @return string The proper name of the temporary table.
	 */
	private function makeTempTableName($name)
	{
		return $this->quoteIdentifier($name);
	}

	/**
	 * Retrieves the DB version and checks if this DB driver is compatible with that.
	 *
	 * @param string $help Installation notes how to solve a problem (in case an exception is thrown).
	 * @throws BizException When unsupported DB version is detected.
	 */
	public function checkDbVersion( &$help )
	{
		$help = '';
		$dbVersion = null;
		$sql = 'SELECT VERSION() as v;';
		$sth = $this->query( $sql );
		$row = $this->fetch( $sth );	
		if( $row && isset( $row['v'] ) ) {
			$dbVersion = $row['v'];
		}	
		if( !$dbVersion ) {
			$help = 'Please check your DB connection settings at the config.php file. '.
				'Also check the installed database version against the Compatibility Matrix.';
			$detail = 'Could not retrieve installed MySQL version.';
			throw new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}
		// convert version string to a number ( e.g. 5.0.27-community-nt becomes 500027 )
		$major = 0; $minor = 0; $revision = 0; $other = '';
		sscanf( $dbVersion, '%d.%d.%d%s', $major, $minor, $revision, $other );
		$dbVersion = $major.'.'.$minor.'.'.$revision;
		$minVersion = SCENT_MYSQLDB_MINVERSION;
		if( version_compare( $dbVersion, $minVersion, '<' ) ) {
			$help = 'Please upgrade your database and try again.';
			$detail = 'Minimum required version of MySQL is v'.$minVersion.'. Found installed version v'.$dbVersion.'.';
			throw new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}
		$dbVerMajMin = $major.'.'.$minor; // Note: $revision is excluded to compare major.minor only
		$maxVersion = SCENT_MYSQLDB_MAXVERSION; // supported: 5.7.x // since Enterprise 10.0.0, it supports Mysql 5.7 (EN-85866)
		if( version_compare( $dbVerMajMin, $maxVersion, '>' ) ) {
			$help = 'Please downgrade your database and try again.';
			$detail = 'Maximum supported MySQL version is v'.$maxVersion.'. Found installed version v'.$dbVersion.'.';
			throw new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}
	}

	/**
	 * Retrieves the DB settings and checks if this DB driver is compatible with those.
	 * It checks character encoding and strict SQL mode.
	 *
	 * @param string $help Installation notes how to solve a problem (in case an exception is thrown).
	 * @throws BizException When unsupported DB settings are detected.
	 */
	public function checkDbSettings( &$help )
	{
		// Request variable settings from DB
		$help = '';
		$charSet = null;
		$sqlMode = null;
		$sql = 'show variables';
		$sth = $this->query( $sql );
		while( ($row = $this->fetch( $sth )) ) {
			if($row['Variable_name'] == 'character_set_database'){
				$charSet = $row['Value'];
			}
			if ($row['Variable_name'] == 'sql_mode') {
				$sqlMode = $row['Value'];
			}
		}

		// Check character encoding settings
		if( $charSet != 'utf8' && $charSet != null ) {
			$help = 'Check the Collation setting at the database. '.
				'(See also the "character set database" option at the database Variables.)';
			$detail = 'The database character encoding "'.$charSet.'" is incorrect; Should be "utf8".';
			throw new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}
		
		// Check strict SQL mode (that is not allowed)
		if( $sqlMode != null ) {
			if( strpos($sqlMode, 'STRICT_ALL_TABLES') !== false || 
				strpos($sqlMode, 'STRICT_TRANS_TABLES') !== false ) {
				// sql_mode can be a comma separated list of values. BZ#22074
				$help = 'Open the my.ini file and remove the STRICT_ALL_TABLES and STRICT_TRANS_TABLES values from the "sql-mode" option. '.
					'(See also the "sql_mode" option at the database Variables.)';
				$detail = 'Database is running in Strict Mode.';
				throw new BizException( null, 'Server', $detail, 'Invalid Configuration' );
			}
		}
	}
	
	/**
	 * Set(Reset) the $tableName auto increment value to $autoIncrementValue.
	 * @param string $tableName DB table name with 'smart_' prefix.
	 * @param integer $autoIncrementValue The value to be reset to in the auto increment.
	 */
	public function resetAutoIncreament( $tableName, $autoIncrementValue )
	{
		$sql = 'ALTER TABLE ' . $tableName . ' AUTO_INCREMENT = ' . $autoIncrementValue;
		$this->dbh->query( $sql );
	}

	/**
	 * Forces the optimizer to join the tables in the order in which they are listed in the FROM clause. In case of 
	 * multiple selects within one query (nested queries) pass the separate select statements (if the 'forced' join
	 * has to be applied on the different select statements).
	 * @param string $sql Select statement.
	 * @return string Select statement extended with the 'forced join'.
	 */
	public function setJoinOrder( $sql )
	{
		$haystack = $sql;
		$count = 0;
		$replacement = str_ireplace('select ', 'SELECT STRAIGHT_JOIN ' , $haystack, $count);
		// STRAIGHT_JOIN is only supported for one select within the statement.
		if ( $count > 1 ) {
			$result = $sql;
		} else {
			$result = $replacement;
		}
		return $result;
	}	

	/**
	 * Returns the names of the indexes of a table.
	 * @param type $tableName (incl. pre-fix).
	 * @return array with the names of indexes.
	 */
	public function listIndexOnTable( $tableName )
	{
		$sql = "SHOW INDEX FROM $tableName";
		$sth = $this->query($sql);
		$index = array();
		while(($row = $this->fetch($sth))){
			$index[] = $row['Key_name'];
		}

		return array_unique($index);
	}	

	/**
	 * Closes all open tables, forces all tables in use to be closed, and flushes the query cache.
	 * FLUSH TABLES also removes all query results from the query cache, 
	 */
	public function flush()
	{
		$sql = 'FLUSH TABLES';
		$this->query($sql);
	}
	
	/**
	 * @inheritDoc
	 */
	public function hasMultibyteSupport()
	{
		return true;
	}

	/**
	 * Compose the sql based on the incoming database field names and values.
	 *
	 * It is assumed that the total passed in field names and field values are the same count.
	 * I.e When there are 4 fields passed in, function expects to have 4 values passed in. Otherwise it will lead into
	 * sql error at the later stage.
	 *
	 * $fieldValues structure looks like this:
	 * $fieldValues = array(
	 *              array( 1,2,3,4 ),
	 *              array( 5,6,7,8 ),
	 *              array( 9,10,11,12 )
	 *           );
	 *
	 * When autoincrement is set to true, the caller does not need to pass in database field 'id'.
	 *
	 * Example:
	 * Incoming parameters:
	 * $fieldNames = array( a,b,c,d )   $fieldValues = array( array(1,2,3,4), array(5,6,7,8) )
	 *
	 * Composed sql:
	 * INSERT INTO table_name (a,b,c,d) VALUES (1,2,3,4), (5,6,7,8)
	 *
	 * @param string $tableName Table name without the 'smart_' prefix.
	 * @param string[] $fieldNames List of DB field names.
	 * @param array[] $fieldValues List of array list which contains the database values. Refer to function header.
	 * @param bool $autoincrement Whether the table to be inserted has autoincrement db field.
	 * @return string The composed sql string.
	 */
	public function composeMultiInsertSql( $tableName, array $fieldNames, array $fieldValues, $autoincrement )
	{
		// $fieldNames = array( a,b,c,d )   $fieldValues = array( array(1,2,3,4), array(5,6,7,8) )
		// INSERT INTO table (a,b,c,d) VALUES (1,2,3,4), (5,6,7,8)
		$sql = 'INSERT INTO ' . $this->tablename( $tableName ) . ' ';
		$sql .= '(`' . implode( '`, `', $fieldNames ).'`) ';
		$sql .= 'VALUES ';
		$comma = '';
		if( $fieldValues ) foreach( $fieldValues as $fieldValuesRow ) {
			$sqlFieldValues = array();
			foreach( $fieldValuesRow as $fieldIndex => $fieldValue ) {
				$fieldName = $fieldNames[$fieldIndex];
				if( is_string( $fieldValue ) &&
					$fieldName != 'timestamp' ) { // TODO: Enterprise v10 use 'timestamp' Type in the $params.
					$sqlFieldValues[] = "'" . $this->toDBString( $fieldValue ) . "'";
				} else {
					$sqlFieldValues[] = strval( $fieldValue );
				}
			}

			$sql .= $comma . '('.implode( ', ', $sqlFieldValues ).')';
			$comma = ', ';
		}
		return $sql;
	}

	/**
	 * Returns an SQL statement for selecting a paged result from a table.
	 *
	 * This function was implemented here because it relies on database system specific code that did not fit in the
	 * DBBase class, as such this function creates a per db type query to be used for paging.
	 *
	 * @param string $tableName Table name without the 'smart_' prefix.
	 * @param string $keyCol The primary key column to search on.
	 * @param string $where The where sql clause part.
	 * @param string $orderBySQL The order by sql clause part.
	 * @param array $limit, keys: 'min'/ 'max', TRUE=ASC, FALSE=DESC.
	 * @param array $values The values used for parameter substitution.
	 * @return string $sql The composed sql statement.
	 */
	public function composePagedQuerySql( $tableName, $keyCol, $where, $orderBySQL, $limit, &$values )
	{
		// Add the beginning of the where clause.
		$where = ($where != '') ? ' WHERE ' . $where : '';

		// Get the prefixed tablename.
		$tableName = $this->tablename( $tableName );

		$sql = 'SELECT * FROM ( SELECT ' . $keyCol . ' AS tablekey FROM ' . $tableName . $where
			. $orderBySQL . ' LIMIT ' . $limit['min'] . ' , ' . $limit['max'] . ' ) q JOIN '
			. $tableName . ' ON ' . $keyCol . ' = q.`tablekey` ' . $orderBySQL; // EN-85328
		
		// Why not keeping this SQL this simple, like this:
		//    SELECT * FROM `smart_serverjobs` ORDER BY `queuetime` ASC LIMIT 0 , 25
		// Reason, for the short version of the query:
		// - MySQL 5.5 does a 'filesort' and a full table scan.
		// - MySQL 5.6 does it better and uses the index (no 'filesort').
		// - We still want to support MySQL 5.5.
		// TODO: After dropping MySQL 5.5 support, simplify the query.

		return $sql;
	}

	/**
	 * DBMS supports the update of multiple column definitions with one 'ALTER' statement.
	 *
	 * @return bool
	 */
	public function supportUpdateMultipleColumns()
	{
		return true;
	}

	/**
	 * Adds multiple columns at once to a table definition.
	 *
	 * @param string $tableName The name of the table (whitout prefix).
	 * @param array $fieldInfos Array of arrays containing the name and data type for each column to add.
	 * @return bool true on success or else false.
	 */
	public function addColumnsToTable( $tableName, $fieldInfos )
	{
		if ( $fieldInfos ) {
			$tableName = $this->tablename( $tableName );
			$sql = 'ALTER TABLE ' . $tableName . ' ADD ';
			$comma = '';
			$addFieldsSql = '';

			foreach ( $fieldInfos as $fieldInfo ) {
				$addFieldsSql .= $comma.'`'.$fieldInfo['Name'].'` '.$fieldInfo['Type'].' ';
				$comma = ',';
			}
			$sql .= '(' . $addFieldsSql . ')';
			$this->query( 'FLUSH TABLES' );
			$sth = $this->query( $sql );
			return (bool)$sth;
		} else {
			return true;
		}
	}

	/**
	 * Updates (changes) the definition of a column of a table.
	 * @param string $tableName The name of the table (whitout prefix).
	 * @param string $columnName The name of the column.
	 * @param string $dbtype The data type of the column.
	 * @return bool true on success else false.
	 */
	public function updateColumnDefinition( $tableName, $columnName, $dbtype )
	{
		$tableName = $this->tablename( $tableName );

		$sql = 'ALTER TABLE '.$tableName.' CHANGE `'.$columnName.'` `'.$columnName.'` '.$dbtype;
		$sth = $this->query( $sql );

		return (bool)$sth;
	}

	/**
	 * Changes the definition of multiple columns a table definition at once.
	 *
	 * @param string $tableName The name of the table (whitout prefix).
	 * @param array $fieldInfos Array of arrays containing the name and data type for each column to add.
	 * @return bool true on success or else false.
	 */
	public function updateMultipleColumnDefinition( $tableName, $fieldInfos )
	{
		if ( $fieldInfos ) {
			$tableName = $this->tablename( $tableName );
			$sql = 'ALTER TABLE ' . $tableName . ' '  ;
			$comma = '';
			$addFieldsSql = '';
			foreach ( $fieldInfos as $fieldInfo ) {
				$addFieldsSql .= $comma . ' CHANGE `'.$fieldInfo['Name'].'` `'.$fieldInfo['Name'].'` '.$fieldInfo['Type'].' ';
				$comma = ',';
			}
			$sql .= $addFieldsSql;
			$this->query( 'FLUSH TABLES' );
			$sth = $this->query( $sql );
			return (bool)$sth;
		} else {
			return true;
		}
	}

	/**
	 * Deletes the definition of multiple columns a table definition at once.
	 *
	 * @param string $tableName The name of the table (whitout prefix).
	 * @param array $columnNames Array of arrays containing the names of the columns.
	 * @return bool true on success or else false.
	 */
	public function deleteColumnsFromTable( $tableName, $columnNames )
	{
		$tableName = $this->tablename( $tableName );
		$sql = 'ALTER TABLE '.$tableName.' DROP COLUMN `'.implode("`, DROP COLUMN `", $columnNames).'`';

		$sth = $this->query($sql);
		return (bool)$sth;
	}

	/**
	 * Before sending a Data Definition statement to the DBMS the statement has to be in line with the rules of the
	 * DBMS.
	 *
	 * @param string $statement
	 * @return boolean Statement is complete and can be send to DBMS.
	 */
	public function isCompleteStatement( &$statement )
	{
		/** @noinspection PhpSillyAssignmentInspection */
		$statement = $statement;
		return true;
	}

	/**
	 * Returns a sql-statement to create an index for the specified table. The index consists of the specified columns.
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @param string $indexName Name of the new index.
	 * @param string[] $columns Names of the columns to create an index.
	 * @return string sql-statement.
	 */
	public function composeCreateIndexStatement( $tableName, $indexName, array $columns )
	{
		$changedTable = $this->tablename( $tableName );
		$columnString = $this->quoteColumnNames( $columns );
		$statement = 'CREATE  INDEX `'.$indexName.'` ON '.$changedTable.' ( '.$columnString.' )';
		return $statement;
	}

	/**
	 * Returns a sql-statement to drop the specified table.
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @return string sql-statement.
	 */
	public function composeDropTableStatement( $tableName )
	{
		$droppedTable =  $this->tablename( $tableName );
		$statement = 'DROP TABLE '.$droppedTable;
		return $statement;
	}

	/**
	 * Returns a statement to drop the index of a specified table.
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @param string $indexName Name of the new index.
	 * @return string sql-statement.
	 */
	public function composeDropIndexStatement( $tableName, $indexName )
	{
		$changedTable = $this->tablename( $tableName );
		$statement = 'DROP INDEX `'.$indexName.'` ON '.$changedTable;
		return $statement;
	}

	/**
	 * Creates a statement to drop a temporary table.
	 *
	 * @param string $tableName Name of the temporary table.
	 * @return string drop temporary table statement.
	 */
	public function composeDropTempTableStatement( $tableName )
	{
		return "DROP TEMPORARY TABLE $tableName";
	}

	/**
	 * Creates a new table based on the table definition of the 'from' table. Also all data is copied.
	 * In case of mysql indexes are replicated.
	 *
     * @see \WW_DbDrivers_DriverBase::copyTable
	 * @param string $fromTable Name of the table acting as source.
	 * @param string $toTable Name of the new table.
	 * @return boolean true on success else false.
	 */
	public function copyTable( $fromTable, $toTable )
	{
		$fromTable = $this->tablename( $fromTable );
		$toTable = $this->tablename( $toTable );
		$sql = 'CREATE TABLE '.$toTable.' LIKE '.$fromTable;
		$result = $this->query( $sql ) ? true : false;
		if ( $result ) { // Copy data only is possible if the new table is created.
			/* result = */ $this->query( 'FLUSH TABLES' ); // Make sure Mysql 'sees' the new table.
			$sql = 'INSERT INTO '.$toTable.' SELECT * FROM '.$fromTable;
			$result = $this->query( $sql ) ? true : false;
		}

		return $result;
	}
}
