<?php
/**
 * Translates DBMS independent calls to Mssql specific calls.
 *
 * @package 	Enterprise
 * @subpackage 	DBDrivers
 * @since 		v3.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class mssqldriver extends WW_DbDrivers_DriverBase
{
	public $dbh = null; // handle to the database (TODO: make it private)
	private $errormessage = null;
	private $theerrorcode = null;

	public function __construct( $dbserver = DBSERVER, $dbuser = DBUSER, $dbpass = DBPASS, $dbselect = DBSELECT )
	{
		PerformanceProfiler::startProfile( 'db connect (mssql)', 4 );
		if( $this->isPhpDriverExtensionLoaded() ) {
			$connectionInfo = array(
				'UID'      => $dbuser,
				'PWD'      => $dbpass,
				'Database' => $dbselect );
			$this->dbh = sqlsrv_connect( $dbserver, $connectionInfo);
			if( $this->isConnected() ) {
				// Warnings are not treated as errors
				sqlsrv_configure("WarningsReturnAsErrors", 0);
			} else { // error
				$this->setError();
				$this->dbh = null;
			}
		}
		PerformanceProfiler::stopProfile( 'db connect (mssql)', 4 );
	}

	public function __destruct()
	{
		PerformanceProfiler::startProfile( 'db disconnect (mssql)', 4 );
		if( $this->isConnected() ) {
			sqlsrv_close( $this->dbh );
		}
		PerformanceProfiler::stopProfile( 'db disconnect (mssql)', 4 );
	}

	/**
	 * Tells whether the database could be connected during class constructor.
	 *
	 * @return bool TRUE when connected, else FALSE.
	 */
	public function isConnected()
	{
		return isset($this->dbh) && is_resource($this->dbh);
	}
	
	/**
	 * Tells whether the PHP extension for the DB is loaded.
	 *
	 * @return bool TRUE when loaded, else FALSE.
	 */
	public function isPhpDriverExtensionLoaded()
	{
		return (extension_loaded('sqlsrv') || extension_loaded('sqlsrv_ts')) &&
			function_exists( 'sqlsrv_connect' );
	}

	/*
		AAA 2006-3-3
		Don't replace \' by '' anymore
		Reason: example: a slugline or blob ends with a \
		After addslashes(\), the slugline is \\
		After building the SQL statement, the sql string is 	...set `slugline`='\\'...
		After preg_replace("@\\\\'@is", "''", $sql), the sql string is	...set `slugline`='''...
			which is an invalid SQL statement
		In case the given $sql parameter is well prepared (not to much slashes and quotes added),
		there is no reason to do the replacement!
		The addition of slashes and quotes is reduced by the caller of this function now (2006-3-3)
	*/
	private function _dbindep($sql)
	{
		$sql = preg_replace("/`([^`#]*)`/is", $this->quoteIdentifier('\1'), $sql);	// replace ` by local indetifier quote

		$sql = str_replace("##`##", '`', $sql);	// unescape `, see toDBString() below
		$sql = str_replace("##[##", '[', $sql);	// unescape [, see toDBString() below
		$sql = str_replace("##]##", ']', $sql);	// unescape [, see toDBString() below

		return $sql;
	}

	/**
	 * @inheritDoc
	 */
	public function query( $sql, $params=array(), $blob=null, $writeLog=true, $logExistsErr=true )
	{
		PerformanceProfiler::startProfile( 'db query (mssql)', 4 );

		$logSQL = $writeLog && ( LogHandler::debugMode() || LOGSQL == true ); // BZ#14442
		$startTime = $logSQL ? microtime( true ) : null;

		try {
			$sql = self::substituteParams($sql, $params);
		}
		catch (Exception $e) {
			LogHandler::Log('mssql', 'ERROR', $e->getMessage());
			return null;
		}		
		
		$sql = $this->_dbindep($sql); // See note AAA
		$cleanSql = $sql; // remember for logging (before adding blob data)

		// handle blobs
		if( is_null($blob) ) { // Make sure insert/update does not fail in case of #BLOB# and $blob equals null
			$blobPlaceHolder = false;
			if( strstr($sql, '#BLOB#') ) {
				$sql = str_replace("#BLOB#", "''", $sql);
				$blobPlaceHolder = true;
			} elseif( strstr($sql, '#LARGEBLOB#') ) {
				$data = unpack("H*hex", $blob);
				$blob = "0x".$data['hex'];
				$sql = str_replace("#LARGEBLOB#", $blob, $sql);
				$blobPlaceHolder = true;
			}
			if( LogHandler::debugMode() && $blobPlaceHolder ) {
				LogHandler::Log( 'mssql', 'ERROR', 'BLOB placeholder found at SQL, but no BLOB param provided.' );
			}
		} else {
			if( !is_array( $blob ) ) {
				$blob = array( $blob );
			} 
			if( is_array( $blob ) ) {
				foreach ( $blob as $key => $blobItem ) {
					$blobstr = str_replace("'", "''", $blobItem); // BZ#4834: don't use toDBString(), or else some chars are converted, e.g: [ are converted to ##[##
					// log blob, only in debug mode
					if( $logSQL ) {
						$postfix = strlen( $blobstr ) > 250 ? '...' : '';
						$blobLog = substr( $blobstr, 0, 250 );
						LogHandler::Log('mssql', 'INFO', 'BLOB: ['.$blobLog.$postfix.']' );
					}
					$blob[$key] = "'$blobstr'"; 
				}
			}
			if( strstr( $sql, '#BLOB#' ) ) {
				// Replace the #BLOB" placeholder by the actual values.
				$sqlParts = explode('#BLOB#', $sql);
				$placeHoldersCount = count($sqlParts) - 1; // 3 #BLOB# placeholders result into 4 SQL parts
				if( count( $blob ) != $placeHoldersCount ) {
					LogHandler::Log('mssql', 'ERROR', 'The number of blob parameters '.count( $blob ).
						' does not match the number of #BLOB# placeholders '.$placeHoldersCount.' at SQL.');
				}
				foreach ( $blob as $key => $blobItem ) {
					$sqlParts[$key] .= $blobItem;
				}
				$sql = implode( ' ', $sqlParts );			
			} elseif( strstr( $sql, '#LARGEBLOB#' ) ) {
				$data = unpack( "H*hex", $blob[0] );
				$blob[0] = "0x" . $data['hex'];
				$sql = str_replace( "#LARGEBLOB#", $blob[0], $sql );
				if( count($blob) > 1 ) {
					LogHandler::Log( 'mssql', 'ERROR', 'Multiple LARGE BLOBs not supported.' );
				}
			} else {
				LogHandler::Log( 'mssql', 'ERROR', 'BLOB param given, but no BLOB placeholder found at SQL.' );
			}
		}
		
		// Perform the query.
		PerformanceProfiler::startProfile( 'mssql_query', 5 );
		$result = @sqlsrv_query( $this->dbh, $sql );
		PerformanceProfiler::stopProfile( 'mssql_query', 5 );

		// Log SQL (without blob data).
		if ( $logSQL ) {
			$rowCnt = ( $result ) ? sqlsrv_num_rows( $result ) : 0;
			if( !$rowCnt ) {
				$rowCnt = ( $result ) ? sqlsrv_rows_affected( $result ) : 0;
			}
			$this->logSql( 'mssql',
				$cleanSql,
				$rowCnt,
				__CLASS__,
				__FUNCTION__,
				microtime( true ) - $startTime );
		}

		PerformanceProfiler::stopProfile( 'db query (mssql)', 4 );

		// Handle errors.
		if (!$result) {
			$this->setError();
			if( !$logExistsErr && // suppress already exists errors ?
				($this->errorcode() == DB_ERROR_ALREADY_EXISTS || 
				 $this->errorcode() == DB_ERROR_CONSTRAINT )) {
				return false;
			} elseif( $this->errorcode() == DB_ERROR_DEADLOCK_FOUND || $this->errorcode() == DB_ERROR_NOT_LOCKED ) {
				return $this->retryAfterLockError( $sql );
			}
			if ( $writeLog ) {
				LogHandler::Log('mssql', 'ERROR', $this->errorcode().':'.$this->error());
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
		LogHandler::Log( 'mssql', 'INFO', '(Dead)lock error encountered: Execute statement again.' );
		if( LogHandler::debugMode() ) {
			LogHandler::Log('mssql', 'DEBUG', $this->theerrorcode.': '.$this->error());
		}
		$result = null;
		$maxRetries = 3;
		for( $retry = 1; $retry <= $maxRetries; $retry++ ) {
			LogHandler::Log( 'mssql', 'INFO', "(Dead)lock: Retry attempt {$retry} of {$maxRetries}." );
			usleep( $milliseconds * 1000 );
			$result = @sqlsrv_query( $this->dbh, $sql );
			if( !$result ) {
				LogHandler::Log( 'mssql', 'WARN', '(Dead)lock: Retry of statement failed once again.' );
				$result = null;
			} else {
				LogHandler::Log( 'mssql', 'INFO', '(Dead)lock: Retry of statement was successful.' );
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
		//PerformanceProfiler::startProfile( 'db fetch (mssql)', 4 );
		if( $sth ) {
			$ret = sqlsrv_fetch_array( $sth, SQLSRV_FETCH_ASSOC );	
			// Note that sqlsrv_fetch_array() returns an array on success, NULL if there are 
			// no more rows to return, and FALSE if an error occurs. 
			// That is different than our fetch() interface so we have to change NULL into FALSE.
			// This fix is important for code that checks on isset($row).
			if( is_null($ret) ) {
				$ret = false;
			}
		} else {
			$ret = false;
		}
		//PerformanceProfiler::stopProfile( 'db fetch (mssql)', 4 );
		return $ret;
	}

	private function setError()
	{
		$this->errormessage = '';
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
        if ($errors != null) {
        	$firsterror = array_shift($errors); //Get the first error
        	$this->errormessage = $firsterror['message'];
        	$this->theerrorcode = $firsterror['code'];
        }
        else {
        	$this->theerrorcode = -1234;
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
		switch( $this->theerrorcode ) {
			case 2627:
				return DB_ERROR_CONSTRAINT;
			case 2601:
			case 2714:
				return DB_ERROR_ALREADY_EXISTS;
			case 1203:
			case 1204:
			case 1205:
				return DB_ERROR_DEADLOCK_FOUND;
			case 1222:
				return DB_ERROR_NOT_LOCKED;
			default:
				return "MSSQL: ".$this->theerrorcode;
		}
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
		$sql = 'SELECT 1 FROM sysobjects WHERE name = \''.$tableName.'\'';
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
		$sql = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS ';
		$sql .= 'WHERE TABLE_CATALOG = \''.DBSELECT.'\' ';
		$sql .= 'AND TABLE_NAME = \''.DBPREFIX.$tableName .'\' ';
		$sql .= 'AND  COLUMN_NAME = \''.$fieldName .'\' ';

		$sth = $this->query( $sql );
		$row = $this->fetch( $sth );

		return $row ? true : false;
	}

	/**
	 * @inheritDoc
	 */
	public function newid($table, $after)
	{
		if ($after){
			// We use SCOPE_IDENTIY, this returns most recent ID in our scope. Event trigger that has create
			// rows won't effect this. Event triggers are not used by us, but clients might do this.
			// We do not use @@IDENTITY because triggers could influence this and we chose not to 
			// use IDENT_CURRENT(table) because that can be influenced by others.
	        $sth = $this->query("Select SCOPE_IDENTITY() as id");
	        $row = $this->fetch($sth);
	        return $row['id'];
		} else {
			// we use identity columns, so we don't supply new ids beforehand
			return false;
		}
	}

	/*
	 * Allows to inserting your own record id into a table that has autoincrement/identity enabled.<br/>
	 *
	 * When copying a record from one to another table, while the target table has autoincrement, <br/>
	 * there is a conflict situation. The database wants to take care of the new id, while the SQL <br/>
	 * statement actually wants to do that as well. In that case, you need to call this function <br/>
	 * before and after the actual SQL statement. For example, this is needed to insert a removed <br/>
	 * object from smart_objects table into the smart_deletedobjects table and vice versa! <br/>
	 * This is a problem for MSSQL, which works with identity properties on tables that needs to <br/>
	 * be switched on before you can determine the id yourself. After that, you have to make very sure <br/>
	 * that you switch it back to off again, or else, the table can not be used again for insertions! <br/>
	 * Introduced to solve BZ#4341.
	 *
	 * @param $table string Table name
	 * @param $after boolean Needs to be set false in first call (before) and true for second call (after)
	 */
	public function copyid( $table, $after )
	{
		$sql = 'SET IDENTITY_INSERT '.$table;
		$sql .= $after ? ' OFF' : ' ON';
	    $sth = $this->query( $sql );
	    return $this->fetch( $sth );
	}

	public function quoteIdentifier($name)
	{
		return "[$name]";
	}

	/**
	 * @inheritDoc
	 */
	public function limitquery($sql, $start, $count)
	{
		/* MSSQL has no LIMIT clause as available for MySQL.
		   Instead, we use the TOP clause, but that has no start parameter.
		   We are forced to do another trick, with following semantics:
		   SELECT * FROM
				( SELECT TOP 5 *  FROM
					( SELECT TOP 100 * FROM table_name ORDER BY ID ASC )
				AS internal ORDER BY internal.ID DESC )
			AS external ORDER BY external.ID ASC
		   
		   This does the same as the following done for MySQL:
				SELECT * FROM table_name LIMIT 5, 100
			which returns the first 5 records, starting with 100th.
			
			Note: MSSQL 2000 does not like the syntax "TOP(100)", but MSSQL 2005 does!
			      Now we use "TOP 100" (no brackets) to make both versions happy.
		*/
		$selectPos = stripos( $sql, 'SELECT ' );
		$orderPos = strripos( $sql, 'ORDER BY ' );
		$retSql = $sql;
		if( $selectPos !== FALSE && $orderPos !== FALSE ) {
			// Eat ORDER BY clause
			$orderByStr = trim(substr( $sql, $orderPos ));
			$sql = substr( $sql, 0, $orderPos ); 
			
			// Determine reverse order
			$extOrderBy = '';
			$revOrderBy ='';
			$comma = ''; // only first item has no comma separation
			$orderByList = explode( ',', $orderByStr );
			foreach( $orderByList as $orderByItem ) {
				$ascPos = strripos( $orderByItem, ' ASC' );
				$descPos = strripos( $orderByItem, ' DESC' );
				$extOrderBy .= $comma.$orderByItem;
				if( $ascPos !== FALSE && ($ascPos + strlen(' ASC')) == strlen($orderByItem) ) {
					$revOrderBy .= $comma.substr( $orderByItem, 0, $ascPos ).' DESC';
				} elseif( $descPos !== FALSE && ($descPos + strlen(' DESC')) == strlen($orderByItem) ) {
					$revOrderBy .= $comma.substr( $orderByItem, 0, $descPos ).' ASC';
				} elseif( $ascPos === FALSE && $descPos === FALSE ) {
					// take ascending as default
					$extOrderBy .= ' ASC'; 
					$revOrderBy .= $comma.$orderByItem.' DESC'; 
				}
				$comma = ', ';
			}

			// Build inner SQL with reverse sort returning $start+$count records
			// and build outer SQL with normal sort returning only $count records.
			// So for example, if you ask for 5 records ($count), starting with 100th ($start)
			// the inner query results 105, 104, ..., 002, 001 and the outer SQL returns
			// the rows 101, 102, 103, 104, 105 from it.
			$max = $start + $count;
			$orderBy = $extOrderBy;
			$extOrderBy = preg_replace( '/[a-z0-9_]*\\./is', 'tmpext.', $extOrderBy );
			$revOrderBy = preg_replace( '/[a-z0-9_]*\\./is', 'tmpint.', $revOrderBy );
			// SELECT TOP xx DISTINCT is invalid, it must be SELECT DISTINCT TOP xx
			$distinct = '';
			$distinctPos =  stripos($sql, 'DISTINCT ');
			$fromPos =  stripos($sql, 'FROM ');
			// distinct is in main select (not in a subquery)
			if ( $distinctPos !== FALSE && ($distinctPos > $selectPos) && ($distinctPos < $fromPos) ) {
				$distinct = 'DISTINCT ';
				// remove DISTINCT from sql
				$sql = substr_replace($sql, '', $distinctPos, 9);
			}
			$retSql = 'SELECT ' . $distinct . 'TOP '.$max.' '.substr( $sql, $selectPos+strlen('SELECT ')).$orderBy;
			$retSql = 'SELECT TOP '.$count.' * FROM ( '.$retSql.' ) AS tmpint '.$revOrderBy;
			$retSql = 'SELECT * FROM ('.$retSql.') AS tmpext '.$extOrderBy;
		}
		return $retSql;
	}

	/**
	 * @inheritDoc
	 */
	public function tablename( $tableNameNoPrefix )
	{
		$prefix = DBPREFIX;
		return "[{$prefix}{$tableNameNoPrefix}]";
		
		// Replaced solution below (60ms) with solution above (3ms) to make it 20x faster.
		//return $this->quoteIdentifier(DBPREFIX.$tableNameNoPrefix);
	}

	/**
	 * Returns list of DB fields and its DB field type.
	 *
	 * @param resource $sth DBdriver handler to fetch DB row results.
	 * @return array List of Db column and its Db type.
	 */
    public function tableInfo($sth)
    {
		if (!$sth) return array();

        $metadataFields = @sqlsrv_field_metadata($sth);
        $res   = array();

        foreach ($metadataFields as $metadataField) {
        	$res[] = array('name' => $metadataField['Name'], 'type' => $metadataField['Type']);
        }
        

        return $res;
    }

	public function listcolumns($sth)
	{
		$columns = array();
		$metadataFields = @sqlsrv_field_metadata($sth);
		$index = 0;
		
	    foreach ($metadataFields as $metadataField) {
        	$columns[$metadataField['Name']] = array('name' 	=> $metadataField['Name'],
        	 				  						'index' => $index,
        	 				  						'dbtype'=> $metadataField['Type'],
        	 				  						'len' 	=> $metadataField['Size'],
        	 				  						'flags' => null,
        	 				  						'quoted'=> true,
        	 				  						'isblob'=> false );
	    	++$index;
        }	
        	
		return $columns;
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
		//Don't interfere with glyphs around DB column name: avoid `-conflict in _dbindep later
		$str = str_replace('`', "##`##", $str);	// escape `, will be unescaped in _dbindep above

		//_indep() assumes []-characters are used for database fields
		$str = str_replace('[', "##[##", $str);	// escape [, will be unescaped in _dbindep above
		$str = str_replace(']', "##]##", $str);	// escape ], will be unescaped in _dbindep above

		//Escape ' for this database
		return str_replace("'", "''", $str);
	}

	/*
     * Starts DB transaction
     *
	 * @return resource         Database resource or FALSE on error
     */
	public function beginTransaction( )
	{
		return false;
	}
	
	/*
     * Commits DB transaction
     *
	 * @return resource         Database resource or FALSE on error
     */
	public function commit()
	{
		return false;
	}
	
	/*
     * Rolls back DB transaction
     *
	 * @return resource         Database resource or FALSE on error
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
		return 'NULL';
	}

	/**
	 * @inheritDoc
	 **/
	public function concatFields( $arguments )
	{
		$sql = '';
		$sep = '';
		foreach( $arguments as $argument ) {
			$sql .= "$sep cast($argument as varchar)";
			$sep = ' + ';
		}
		return $sql;
	}
	
	public function createViewSQL()
	{
        return 'CREATE VIEW ';
	}

	public function dropViews($viewnames)
	{
		PerformanceProfiler::startProfile( 'dropViews', 4 );
		foreach ($viewnames as $viewname) {
			
			$sql = "DROP VIEW $viewname";
			self::query($sql);
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
		$sql  = "CREATE TABLE $tablename ( ";
		$sql .= "`publication` INT NOT NULL, `issue` INT NOT NULL, `section` INT NOT NULL, `state` INT NOT NULL, ";
		$sql .= "PRIMARY KEY (`publication`, `issue`, `section`, `state`) ) ";
		self::query($sql);
		$sql = "CREATE INDEX ispusest ON $tablename(`issue`, `publication`, `section`, `state`)";
		self::query($sql);
		PerformanceProfiler::stopProfile( 'createAuthorizedTempTable', 4 );
		return $tablename;
	}

	/**
	 * Method creates a copy of the passed temporary table ($source).
	 * @param string $tablename name of the copy
	 * @param string $source name of the source table
	 * @return string name of the copy 
	 */
	public function createCopyTempTable($tablename, $source)
	{
		$tablename = $this->makeTempTableName($tablename);
		$sql  = "SELECT * ";
		$sql .= "INTO $tablename ";
		$sql .= "FROM $source ";
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
		$sql  = "CREATE TABLE $tablename ( ";
		$sql .= "`id` INT NOT NULL, ";
		$sql .= "PRIMARY KEY (`id`) ) ";
		self::query($sql);
		PerformanceProfiler::stopProfile( 'createTempIdsTable', 4 );
		return $tablename;
	}
	
	/**
	 * Truncates table named $tablename. Truncate is faster than 'DELETE'. 
	 * @param string $tablename name of the table
	 */
	public function truncateTable($tablename)
	{
		PerformanceProfiler::startProfile( 'truncateTable', 4 );
		$sql  = "TRUNCATE TABLE $tablename";
		self::query($sql);
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
		$insertsql = " INSERT INTO $temptvi (`id`) ";
		$comma = ' SELECT ';
		foreach ($rows as $row) {
			$insertsql .= $comma;
			$id = $row['id'];
			$insertsql .= " $id ";
			$comma = ' UNION ALL SELECT ';
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
			$info += (array)sqlsrv_server_info( $this->dbh );
			$info += (array)sqlsrv_client_info( $this->dbh );
		}
		return $info;
	}
	
	/**
	 * DBMS supports multiple references to temporary tables.
	 * @return true/false
	 */
	public function supportMultipleReferencesTempTable()
	{
		return true;
	}	
	
	/**
	 * Returns proper name for temporary tables
	 * @param string $name of the temporary table
	 * @return string The proper name of the temporary table.
	 */
	public function makeTempTableName($name)
	{
		return $this->quoteIdentifier('#' . $name);
	}	

	/**
	 * Retrieves the DB version and checks if this DB driver is compatible with that.
	 *
	 * @param string $help Installation notes how to solve a problem (in case an exception is thrown).
	 * @throws BizException When unsupported DB version is detected.
	 */
	public function checkDbVersion( &$help )
	{
		// Check the Microsoft Driver version for PHP.
		// Note that the dbdriver must be connected (BZ#16885)
		//    => Too old versions could cause empty SOAP responses (such as BZ#16193) ... !
		//       This is pretty unpredictable, but seem to happen when an SQL error has occurred before.
		$mssqlInfo = $this->getClientServerInfo();
		$extensionVersion = implode( '.', array_slice( explode( '.', $mssqlInfo['ExtensionVer'] ), 0, 2 ) ); // take out "major.minor" only!
		if( version_compare( $extensionVersion, '4.3' ) !== 0 ) {
			$help = 'Install Microsoft Driver 4.3 for PHP for SQL Server.'; // returned by reference
			$detail = 'Unsupported version of Microsoft Driver for PHP for SQL Server. '.
				'Found v'.$mssqlInfo['ExtensionVer'].' which is not supported.';
			$e = new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}

		// Check the Microsoft ODBC Driver version.
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Microsoft Driver version for PHP => Microsoft ODBC Driver version:
		//  3.1 => 11.0
		//  3.2 => 11.0
		//  4.0 => 11.0 or 13.0
		//  4.3 => 11.0 or 13.1
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Check revision version too! In case of an older revision you cannot read "real" data types from SQL Server.
		$driverVersion = implode( '.', array_slice( explode( '.', $mssqlInfo['DriverVer'] ), 0, 2 ) ); // take out "major.minor" only!
		if( version_compare( $mssqlInfo['DriverVer'], '11.00.2100' ) !== -1 || // lower?
			( version_compare( $driverVersion, '11.0' ) !== 0 && version_compare( $driverVersion, '13.1' ) !== 0 ) ) {
			$help = 'Install Microsoft ODBC Driver 11 for SQL Server or Microsoft ODBC Driver 13.1 for SQL Server.'; // returned by reference
			$detail = 'Unsupported version of Microsoft ODBC Driver for SQL Server. '.
				'Found v'.$mssqlInfo['DriverVer'].' which is not supported.';
			$e = new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}

		// Check the SQL Server version.
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Internal SQL Server version => SQL Server version:
		//  10.0 => SQL Server 2008
		//  10.5 => SQL Server 2008 R2
		//  11.0 => SQL Server 2012
		//  12.0 => SQL Server 2014
		//  13.0 => SQL Server 2016
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$serverVersion = implode( '.', array_slice( explode( '.', $mssqlInfo['SQLServerVersion'] ), 0, 1 ) ); // take out "major" only!
		if( version_compare( $serverVersion, '12.0' ) !== 0 && version_compare( $serverVersion, '13.0' ) !== 0 ) {
			$help = 'Install SQL Server 2014 or SQL Server 2016.'; // returned by reference
			$detail = 'Unsupported version of Microsoft SQL Server. '.
				'Found v'.$mssqlInfo['SQLServerVersion'].' which is not supported.';
			$e = new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}

		// Log all DB client/server information.
		$infoList = '';
		if( $mssqlInfo ) foreach( $mssqlInfo as $key => $value ) {
			$infoList .= "$key = $value\r\n";
		}
		LogHandler::Log( 'mssql', 'INFO', "MSSQL driver information:\r\n{$infoList}" );

		// Raise error in case unsupported versions were detected above.
		if( isset( $e ) ) {
			throw $e;
		}
	}
	
	/**
	 * Retrieves the DB settings and checks if this DB driver is compatible with those.
	 *
	 * @param string $help Installation notes how to solve a problem (in case an exception is thrown).
	 * @thows BizException When unsupported DB settings are detected.
	 */
	public function checkDbSettings( &$help )
	{
		$help = '';
		// TODO: check for DB encoding settings (and other?)
	}

	/**
	 * Set(Reset) the $tableName auto increment value to $autoIncrementValue.
	 * @param string $tableName DB table name with 'smart_' prefix.
	 * @param integer $autoIncrementValue The value to be reset to in the auto increment.
	 */
	public function resetAutoIncreament( $tableName, $autoIncrementValue )
	{
		// TODO: Reset the auto increment for MSSQL
	}
	
	/**
	 * Forces the optimizer to join the tables in the order in which they are listed in the FROM clause. In case of 
	 * multiple selects within one query (nested queries) pass the separate select statements (if the 'forced' join
	 * has to be applied on the different select statements). Not yet supported for Mssql.
	 * @param string $sql Select statement.
	 * @return string Select statement extended with the 'forced join'.
	 */
	public function setJoinOrder( $sql )
	{
		return $sql;
	}

	/**
	 * Returns the names of the indexes of a table.
	 * @param type $tableName (incl. pre-fix).
	 * @return array with the names of indexes.
	 */
	public function listIndexOnTable( $tableName )
	{
		$sql = "EXEC sp_helpindex $tableName";
		$sth = $this->query($sql);
		$index = array();
		while(($row = $this->fetch($sth))){
			$index[] = $row['index_name'];
		}

		return array_unique($index);
	}	
	
	/**
	 * Closes all open tables, forces all tables in use to be closed, and flushes the query cache.
	 * Not yet implemented for Mssql. 
	 */	
	public function flush()
	{
		return;
	}

	/**
	 * @inheritDoc
	 */
	public function hasMultibyteSupport()
	{
		return false;
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
		// INSERT INTO table_name (a,b,c,d) VALUES (1,2,3,4), (5,6,7,8)
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
	 * @param array $values, the values to be used for parameter substitution.
	 * @return string $sql The composed sql statement.
	 */
	public function composePagedQuerySql( $tableName, $keyCol, $where, $orderBySQL, $limit, &$values )
	{
		// Add the beginning of the where clause.
		$and = ($where != '') ? ' AND ' . $where : '';
		$where = ($where != '') ? ' WHERE ' . $where : '';

		// We need a double substitution since the where part is used twice.
		$values = array_merge( $values, $values );

		// Get the prefixed tablename.
		$tableName = $this->tablename( $tableName );

		$sql = 'SELECT TOP ' . $limit['max'] . ' * FROM ' . $tableName
			. ' WHERE ' . $keyCol . ' NOT IN ( SELECT TOP ' . $limit['min']
			. ' ' . $keyCol . ' FROM ' . $tableName . $where . $orderBySQL . ' )' . $and . $orderBySQL;

		return $sql;
	}

	/**
	 * DBMS supports the update of multiple column definitions with one 'ALTER' statement.
	 *
	 * @return bool
	 */
	public function supportUpdateMultipleColumns()
	{
		return false;
	}

	/**
	 * Adds multiple columns at once to a table definition.
	 *
	 * @param string $tableName The name of the table (whitout prefix).
	 * @param array $fieldInfos Array of arrays containing the name and the data type for each column to add.
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
			$sql .= $addFieldsSql;

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

		$dbtype = preg_replace('/default [a-z0-9]*[.]*[0-9]*[\']*/i', '', $dbtype);
		$sql = 'ALTER TABLE '.$tableName.' ALTER COLUMN `'.$columnName.'` '.$dbtype;
		$sth = $this->query( $sql );

		return (bool)$sth;
	}

	/**
	 * Adds multiple columns at once to a table definition. At this moment not supported for mssql.
	 *
	 * @param string $tableName The name of the table (whitout prefix).
	 * @param array $fieldInfos Array of arrays containing the name and data type for each column to add.
	 * @return bool true on success or else false.
	 * @throws BizException
	 */
	public function updateMultipleColumnDefinition( $tableName, $fieldInfos )
	{
		throw new BizException( null, 'Server', null, 'Update multiple columns in one statement is not supported '.
			'for DBMS: '.DBTYPE );

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
		$namesPrefix = array();
		$constraints = array();

		if( $columnNames ) foreach( $columnNames as $name ) {
			// Check if prefix is not the same, to avoid running the same query multiple times
			if( !in_array( substr( $name,2,3 ), $namesPrefix )) {
				$namesPrefix[] = substr( $name, 2, 3 );
				// Note that _ with LIKE means <any character>, so we escape this with []
				$sql = "SELECT name FROM sysobjects WHERE name LIKE 'DF[_][_]".substr( $tableName,1, 9 )."%"."c[_]".substr( $name, 2 , 3 )."%'";
				$sth = $this->query( $sql );
				while (( $row = $this->fetch( $sth ))) {
					$constraints[] = $row['name'];
				}
			}
		}
		if( $constraints ) {
			$sql = 'ALTER TABLE '.$tableName.' DROP CONSTRAINT '.implode(',', $constraints );
			$this->query( $sql );
		}

		$sql = 'ALTER TABLE '.$tableName.' DROP COLUMN `'.implode( "`, `", $columnNames ) . '`';

		$sth = $this->query( $sql );
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
		return true;
	}

	/**
	 * Creates a new table based on the table definition of the 'from' table. Also all data is copied.
	 * Indexes have to be added separately.
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
		$sql = 'SELECT * INTO '.$toTable.' FROM '.$fromTable; // Creates table with data but not the indexes.
		$result = $this->query( $sql ) ? true : false;

		return $result;
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
	 * @return string sql-statement.
	 */
	public function composeDropTempTableStatement( $tableName )
	{
		return 'DROP TABLE '.$tableName;
	}
}
