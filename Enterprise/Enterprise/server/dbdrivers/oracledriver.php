<?php
/**
 * Translates DBMS independent calls to Oracle specific calls.
 *
 * @package 	Enterprise
 * @subpackage 	DBDrivers
 * @since 		v3.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class oracledriver extends WW_DbDrivers_DriverBase
{
	public $dbh = null; // handle to the database (TODO: make it private)
	private $lastid;
	private $last_stmt;
	private $theerrorcode = null;
	private $errormessage = null;
	
	private $scent2ora = array("user" => "USERNAME", "date" => "DATED", "comment" => "COMMENTS",
								"_left" => "LEFTPOS", "lock" => "LOCKED", "_columns" => "NRCOLUMNS");
	private $ora2scent = array("USERNAME" => "user", "DATED" => "date", "COMMENTS" => "comment",
								"LEFTPOS" => "_left", "LOCKED" => "lock", "NRCOLUMNS" => "_columns");
								

	public function __construct( $dbserver = DBSERVER, $dbuser = DBUSER, $dbpass = DBPASS, $dbselect = DBSELECT )
	{
		PerformanceProfiler::startProfile( 'db connect (oracle)', 4 );
		if( $this->isPhpDriverExtensionLoaded() ) {
			PerformanceProfiler::startProfile( 'oci_connect', 5 );
			$this->dbh = oci_connect( $dbuser, $dbpass, $dbserver, 'UTF8' );
			PerformanceProfiler::stopProfile( 'oci_connect', 5 );
	
			if( $this->isConnected() ) {
				if (defined('ORACLE_NLS_SORT')) {
					$sql = 'alter session set NLS_SORT = ' . ORACLE_NLS_SORT;
					$this->query($sql);
				}
				$sql = "ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'";
				$this->query($sql);
				//Make sure that the period is the decimal separator and the comma the group separator.
				//Scope of the ALTER SESSION statement: The statement stays in effect until you disconnect from the database.			
			} else {
				$this->setError();
				$this->dbh = null;
			}
	
			// ORACLE does not use DBSELECT, this should be set as default tablespace for user
			$dbselect = $dbselect; // keep analyzer happy
		}
		PerformanceProfiler::stopProfile( 'db connect (oracle)', 4 );
	}

	public function __destruct()
	{
		PerformanceProfiler::startProfile( 'db connect (oracle)', 4 );
		if( $this->isConnected() ) {
			PerformanceProfiler::startProfile( 'oci_close', 5 );
			oci_close( $this->dbh );
			PerformanceProfiler::stopProfile( 'oci_close', 5 );
		}
		PerformanceProfiler::stopProfile( 'db connect (oracle)', 4 );
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
	 * Note that there are different extensions for Oracle e.g. oci8, oci8_11g and oci8_12c. If one of these extensions
	 * is installed true is returned. This is checked by only looking for extension 'oci8'. This check will also
	 * return true if 'oci8' is not installed but 'oci8_11g' is. A check on 'oci8_11g' fails although it is installed.
	 *
	 * @return bool TRUE when loaded, else FALSE.
	 */
	public function isPhpDriverExtensionLoaded()
	{
		return extension_loaded('oci8' ) && function_exists( 'oci_connect' );
	}
	
	private function _dbindep($sql)
	{
		// Before: mixed-case fieldnames where quoted differently in quoteIdentifier.
		// As we don't want that kind of behavior:
		// Case does not matter anymore.
		// Added the following to make sure the following statements are all quoted in the same way.
		// We need to replace: ' `fieldname` as `Fieldname` ' with ' FIELDNAME as "Fieldname" '
		// So first we replace ' ` as ` ' by ' ` as " ' to separate first part from second part.
		//n The replace the second part.

		$sql = str_replace('` as `', '` as "', $sql);
		$arr = array();
		if (preg_match_all('/"([_a-z0-9]+)`/is', $sql, $arr) ) {
			foreach ($arr[1] as $p) {
				$sql = str_replace("\"$p`", "\"$p\"", $sql);
			}
		}
	
		// And after that we replace the first part.
		if (preg_match_all('/`([_a-z0-9]+)`/is', $sql, $arr) ) {
			foreach ($arr[1] as $p) {
				$sql = str_replace("`$p`", $this->quoteIdentifier($p), $sql);
			}
		}

		$sql = preg_replace("/`([^`#]*)`/is", $this->quoteIdentifier('\1'), $sql);	// replace ` by local indetifier quote

		$sql = str_replace("##`##", '`', $sql);	// unescape `, see toDBString() below

		/*		
			AAA 2006-3-3
			Don't replace \' by '' anymore
			Reason: example: a slugline or blob ends with a \
			After addslashes(\) in toDBString(), the slugline is \\
			After building the SQL statement, the sql string is 	...set `slugline`='\\'...
			After preg_replace("@\\\\'@is", "''", $sql), the sql string is	...set `slugline`='''...
				which is an invalid SQL statement
			In case the given $sql parameter is well prepared (not to much slashes and quotes added),
			there is no reason to do the replacement!
			The addition of slashes and quotes is reduced by the caller of this function now (2006-3-3)
		*/
		//$sql = preg_replace("@\\\\'@is", "''", $sql);	// replace \' by '' // See note AAA
		//$sql = preg_replace('/"plaincontent"/is', 'plaincontent', $sql);

		// handle is (not) null. Except for insert statements (namedqueries!)
		$sql = $this->replaceEmptyString($sql);
		$sql = $this->handleNullsInOrderBy($sql);
		
		return $sql;
	}
	
	/**
	 * Replaces empty string in SQL by 'is (not) null' except for INSERT statements.
	 *
	 * For example, the following SQL fragment:
	 *    SELECT ... WHERE `name` = ''
	 * is replaced with:
	 *    SELECT ... WHERE `name` = is null
	 *
	 * Supported operators:
	 * = '' => is null
	 * != '' => is not null
	 * <> '' => is not null
	 * Note that this function has a limitation (for an unknown reason): It only replaces the last instance.
	 *
	 * @param string $sql
	 * @return string
	 */
	private function replaceEmptyString( $sql )
	{
		if( strtolower( substr( trim( $sql ), 0, 6 ) ) != "insert" && ( strstr( $sql, "where" ) || strstr( $sql, "WHERE" ) ) ) {
			$sql = str_replace( "WHERE", "where", $sql );
			$arr = explode( "where", $sql );
			$last = sizeof( $arr ) - 1;
			$phaseA = preg_replace( "/!=[ ]*(\\'\\')/is", " is not null ", $arr[ $last ] );
			$phaseB = preg_replace( "/<>[ ]*(\\'\\')/is", " is not null ", $phaseA );
			$arr[ $last ] = preg_replace( "/=[ ]*(\\'\\')/is", " is null ", $phaseB );
			$sql = implode( "where", $arr );
		}

		return $sql;
	}

	/**
	 * Adds the 'NULLS LAST' and 'NULLS FIRST' to an order by clause. This is needed
	 * because Oracle treats a NULL as a very large value. The consequence is that
	 * empty strings, which are stored as nulls by Oracle, are sorted different than in Mysql
	 * or Mssql. When sorting is ascending nulls are sorted as last by Oracle while
	 * empty strings are sorted first by the other two DBMS.   
	 * @param string $sql SQL-statement
	 * @return string statement with adjusted order by clause (if needed).  
	 */
	private function handleNullsInOrderBy( $sql ) 
	{
		/* 
		 * Before keyword asc /desc there is always a white space (\s). The keyword
		 * can be followed by either a white space or a ',' or 'end of string' ($).
		 */
		$sql = preg_replace('/[\s]+(asc)([\s,]|$)/i', ' ${1} NULLS FIRST${2} ', $sql );
		$sql = preg_replace('/[\s]+(desc)([\s,]|$)/i', ' ${1} NULLS LAST${2} ', $sql );

		return $sql;
	}	

	/**
	 * Performs a given SQL query at the DB.
	 *
	 * @param string $sql   The SQL statement.
	 * @param array $params Parameters for replacement
	 * @param mixed $blob   Chunck of data to store at DB. It will be converted to a DB string here.
	 * 				   One blob can be passed or multiple. If muliple are passed $blob is an array.
	 * @param boolean $writeLog In case of license related queries, this option can be used to not write in the log file.
	 * @param boolean $logExistsErr Suppress 'already exists' errors. Used for specific insert operations for which this error is fine.
	 * @return resource|bool DB handle that can be used to fetch results.  Null when SQL failed. False when record already exists, in case $logExistsErr is set false.
	 */
	public function query( $sql, $params=array(), $blob=null, $writeLog=true, $logExistsErr=true )
	{
		PerformanceProfiler::startProfile( 'db query (oracle)', 4 );
		
		try {
			$sql = self::substituteParams($sql, $params);
		}
		catch (Exception $e) {
			LogHandler::Log('oracle', 'ERROR', $e->getMessage());
			return null;
		}	
				
		$sql = $this->_dbindep($sql); // See note AAA

		$cleanSql = $sql; // remember for logging (before adding blob data)
		$logSQL = $writeLog && ( LogHandler::debugMode() || LOGSQL == true ); // BZ#14442
		
		$this->last_stmt = null;
		
		// handle blobs
		if ( !is_null( $blob ) ) { //$blob is array or string
			if (!is_array( $blob )) {
				$blob = array( $blob); // After adding support for multiple blobs a single blob is converted to an array.
			} 
			// Log the SQL
			if( $logSQL ) {
				PerformanceProfiler::startProfile( 'query blob logging', 5 );
				foreach ( $blob as $blobItem ) {
					$postfix = strlen( $blobItem ) > 250 ? '...' : '';
					$blobLog = substr( $blobItem, 0, 250 );
					$blobLog = (LOGFILE_FORMAT == 'html') ? htmlentities( $blobLog ) : $blobLog;
					// SQL logging is only outputted on DEBUG or INFO level
					LogHandler::Log('oracle', 'INFO', 'BLOB: ['.$blobLog.$postfix.']' );
				}
				PerformanceProfiler::stopProfile( 'query blob logging', 5 );
			}
		} else { //$blob is null
			$blob = array($blob); //Make sure update/insert does not fail in case of #BLOB# and $blob equals null
		}

		// insert blob into sql (or clear blob from sql when $blob==null !)
		// and perform query
		if (strstr($sql, '#BLOB#')) {
			$length = strlen('#BLOB#');
			$element = 0;
			foreach ( $blob as $key => $blobItem ) {
				$startPos = strpos( $sql, '#BLOB#' );
				if( $blob[$key] !== '' ) {
					$sql = substr_replace($sql, ':bind' . strval($element), $startPos, $length);
					$element += 1;
				} else {
					$sql = substr_replace($sql, 'empty_clob()', $startPos, $length);
					unset($blob[$key]);
				}
			}
			PerformanceProfiler::startProfile( 'oci_parse', 5 );
			$sth = oci_parse($this->dbh, $sql);
			PerformanceProfiler::stopProfile( 'oci_parse', 5 );
			$this->result = $this->_oracleexecute($sth, $blob, false);
		} elseif (strstr($sql, '#LARGEBLOB#')) { // @TODO We do not expect more than one LARGEBLOB. Something for version 8.0?
			if( $blob[0] !== '' ) {
				$sql = str_replace("#LARGEBLOB#", ":bind0", $sql);
			} else {
				$sql = substr_replace("#LARGEBLOB#", "empty_blob()", $sql);
				unset($blob[0]);
			}
			PerformanceProfiler::startProfile( 'oci_parse', 5 );
			$sth = oci_parse($this->dbh, $sql);
			PerformanceProfiler::stopProfile( 'oci_parse', 5 );
			$this->result = $this->_oracleexecute($sth, $blob, true);
		} else { // no blobs
			PerformanceProfiler::startProfile( 'oci_parse', 5 );
			$sth = oci_parse($this->dbh, $sql);
			PerformanceProfiler::stopProfile( 'oci_parse', 5 );
			$this->result = $sth;
			PerformanceProfiler::startProfile( 'oci_execute', 5 );
			$ret = @oci_execute($sth, OCI_COMMIT_ON_SUCCESS);
			PerformanceProfiler::stopProfile( 'oci_execute', 5 );
			$this->last_stmt = $sth;
			if (!$ret) {
				$this->result = null;
			}	
		}
		
		// Capture the error -before- calling oci_num_rows() below, or else that
		// would clear any problems that have occurred above.
		if( !$this->result ) {
			$this->setError();
		}

		// Log SQL (without blob data).
		if ( $logSQL ) {
			$rowCnt = ( $sth ) ? oci_num_rows( $sth ) : 0; // Note: for SELECT statements this does not work
			$this->logSql( 'oracle',
				LOGFILE_FORMAT == 'html' ? htmlentities( $cleanSql ) : $cleanSql,
				$rowCnt,
				__CLASS__,
				__FUNCTION__ );
		}

		PerformanceProfiler::stopProfile( 'db query (oracle)', 4 );

		// Handle errors.
		if( !$this->result ) {
			$this->setError();
			if( !$logExistsErr && // suppress already exists errors ?
				($this->errorcode() == DB_ERROR_ALREADY_EXISTS || 
				 $this->errorcode() == DB_ERROR_CONSTRAINT )) {
				return false;
			}
			if ( $writeLog ) {
				PerformanceProfiler::startProfile( 'query logging', 5 );
				LogHandler::Log('oracle', 'ERROR', $this->errorcode().':'.$this->error());
				PerformanceProfiler::stopProfile( 'query logging', 5 );
			}
			return null;
		}

		PerformanceProfiler::startProfile( 'ocisetprefetch', 5 );
		@ocisetprefetch($this->result, 100);
		PerformanceProfiler::stopProfile( 'ocisetprefetch', 5 );

		return $this->result;
	}

	/*
     * Starts DB transaction
     *
	 * @return resource         Database resource or FALSE on error
     */
	public function beginTransaction( )
	{
// To be implemented
		return false;
	}
	
	/*
     * Commits DB transaction
     *
	 * @return resource         Database resource or FALSE on error
     */
	public function commit()
	{
//		LogHandler::Log('oracle', 'DEBUG', 'Commit!');
//		return oci_commit($this->dbh);
		return false;
	}

	/*
     * Rolls back DB transaction
     *
	 * @return resource         Database resource or FALSE on error
     */
	public function rollback()
	{
//		LogHandler::Log('oracle', 'DEBUG', 'Rollback!');
//		return oci_rollback($this->dbh);
		return false;
	}

	/**
	 * Fetch a result row as an associative array.
	 *
	 * @param resource $sth The result resource that is being evaluated. This result comes from a call to {@link: query()}.
	 * @return array Associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
	 */
	public function fetch( $sth )
	{
		//PerformanceProfiler::startProfile( 'db fetch (oracle)', 4 );
		if( !$sth ) return false;

		//PerformanceProfiler::startProfile( 'oci_fetch_array', 5 );
		$row = oci_fetch_array($sth, OCI_ASSOC | OCI_RETURN_NULLS ); // OCI_RETURN_LOBS*
		// * Leave out OCI_RETURN_LOBS as we don't want the blobs to be returned as PHP string.
		//   This is to revert the "\0" hack as applied in the query() function for empty blobs.
		//PerformanceProfiler::stopProfile( 'oci_fetch_array', 5 );
		
		if( !is_array($row) ) {
			//PerformanceProfiler::stopProfile( 'db fetch (oracle)', 4 );
			return false;
		}

		//PerformanceProfiler::startProfile( 'lowercasing', 5 );
		// Copy the row to return which has the same values but has adjusted keys
		$retRow = array();
		$lowerCaseCtr = 0;
		$camelCaseCtr = 0;
		$numField = 1;
		foreach( $row as $fieldName => $fieldValue ) {
			$columnType = oci_field_type($sth, $numField);
			$columnName = oci_field_name($sth, $numField);
			if ( $columnName !== $fieldName) {
				LogHandler::Log( 'oracle', 'DEBUG', "Mismatch: Internal name = $columnName, returned name = $fieldName.");	
			}		
			
			// Empty BLOB fields in Oracle are filled with "\0" which is unwanted
			// since strlen("\0") returns 1 and empty("\0") returns false.
			// Therefore we set those returned field values to empty on-the-fly.
			if ( is_object( $fieldValue )) { // is blob?
				$size = $fieldValue->size();
				$blobValue = $size > 0 ? $fieldValue->read( $size ) : '';
				$fieldValue->free();
				if( $blobValue == "\0" ) { // marked as empty?
					$blobValue = '';
				}
				$fieldValue = $blobValue; // convert blob to string: 1. To 
			}
			
			// When a string value is null, we make it empty because null would imply
			// that we did not request the DB for this field (but we just did!).
			// This also avoids problems with the use of isset($row['field']) function
			// that returns false, while you might expect true (even though it is better
			// to use the array_key_exists( 'field', $row) function instead).
			if ( $columnType === 'VARCHAR2' && is_null($row[$fieldName] )) {
				$fieldValue = '';
			}			
			if( array_key_exists( $fieldName, $this->ora2scent) ) {
				// The field name is a special Oracle one, use the one from the translation array
				$retRow[$this->ora2scent[$fieldName]] = $fieldValue;
			} elseif( strtoupper($fieldName) == $fieldName ) {
				// $fieldName = 'ID' will always come into this loop  as it is always 'ID'(all upper case) instead of
				// 'ID'(upper case) or 'Id'(camel case). (read more below in 'To repair the case where field name = 'ID'.' )
				// Entering here might not be always correct as if we want camel case notation('ID'), we do not want to
				// have lower case id ('id') as being done here. However, we let the conversion to lower case happen
				// first, and we repair it later if needed.
				//
				// The field name is all uppercase, lowercase it

				// **Don't count the custom properties(with C_ prefix)
				// Custom properties are -always- in upper case( therefore it will come into this loop to be transformed
				// into lower case). And since they are always upper case (regardless of the two notations), we actually
				// won't know if the current request is for the upper case notation or the camel case notation by checking
				// the custom properties, therefore we exclude here from determining the notations. Otherwise it could
				// go wrong when we have more custom properties than the standard built in properties when the notation is
				// supposed to be 'camel case' notation.
				// For an example:
				// Let say a query has 5 built-in properties and 10 custom properties and the notation is 'camel case'.
				// If we don't exclude the custom properties here, we will have more custom props = upper case(transform into lower case)
				// than the camel case, and the system thinks the query wants a upper case notation (which is wrong).
				$isCustom = isset($fieldName[1]) && $fieldName[1] == '_' && ( $fieldName[0] == 'C' || $fieldName[0] == 'c' );
				if( !$isCustom  ) { // **Don't count the custom properties(with C_ prefix)
					$retRow[strtolower($fieldName)] = $fieldValue; // Non custom property should not be in upper case.
					$lowerCaseCtr++; // To repair $fieldName = 'ID', Read below for the case when $fieldName = 'ID'
				} else {
					$retRow[$fieldName] = $fieldValue; // Retain custom property as it is (upper case).
				}
			} else {
				// The field name is camel case, keep it as is
				$retRow[$fieldName] = $fieldValue;
				$camelCaseCtr++; // To repair $fieldName = 'ID', Read below for the case when $fieldName = 'ID'
			}
			$numField +=1;
		}

		// >>> Commented out:
		//     Cannot simply add 'ID' here without any checking.
		//     Enterprise uses 'ID' to find out whether the $retRow is list of property names or list of DB field names.
		//     When 'ID' is found in $retRow, it means $retRow is a list of property names (ID, DocumentId, Type, Comment etc).
		//     When 'id' is found in $retRow, it means $retRow is a list of DB field names.(id, documentid, type, comment etc)
		//     Here, when 'ID' always exists, the checking goes wrong when the $retRow is supposed to be DB fields.
		//
		// Keep ID (for full object query) ...
		//if( array_key_exists( 'ID', $row ) ) {
		//	$retRow['ID'] = $row['ID'];
		//}

		// To repair the case where field name = 'ID'.
		// When the field names are all in upper case (as always returned by Oracle), the function will transform all into
		// lower case (e.g PUBLICATIONID to publicationid ); when the field names are in camel case (as requested in the
		// query), the function will retain as it is (leave it as camel case, e.g PublicationId ).
		//
		// For database field names such as 'publicationid', 'type', etc, when it is in upper case notation, they
		// would be 'PUBLICATIONID', 'TYPE', etc; and when it is in camel case notation, they would be
		// 'PublicationId', 'Type', etc. So there's no issue in distinguishing whether it should be converted into
		// lower case or just retain as it is (camel case).
		//
		// But for the 'ID' field in smart_objects and smart_deletedobjects table, whether it is in upper case or camel
		// case notation, it is always 'ID' instead of 'ID' and 'Id'.
		// Due to this behavior, it is hard to distinguish whether the caller is asking for upper case or camel case notation
		// i.e. whether the function should transform the ID into lower case or retain the ID as it is (camel case).
		//
		// The (inelegant) code fragment below finds out all the field names in the $retRow:
		// When there are more camel case fields than lower case (already transformed from upper to lower case) in $retRow,
		// it is assumed that the context is in camel case notation thus function returns the id as 'ID' (camel case notation
		// for id); otherwise function returns 'id' (the upper case notation that is already transformed into lower case).
		if( $camelCaseCtr > $lowerCaseCtr ) { // Camel case notation?
			if( array_key_exists( 'id', $retRow) ) {
				$holdID = array( 'ID' => $retRow['id'] );
				unset( $retRow['id']);
				$retRow = array_merge( $holdID, $retRow ); // Make sure that the 'ID' column is the first one returned.
				// The SC clients expect 'ID', 'Type', 'Name' to be the first three properties returned. 
			}
		}
		//PerformanceProfiler::stopProfile( 'lowercasing', 5 );
		//PerformanceProfiler::stopProfile( 'db fetch (oracle)', 4 );
		return $retRow;
	}
	
	/*
	Future implementation: no replacing of fieldnames, very confusing that, but at the moment can not implement it that way.
	function fetch_assoc($sth)
	{
		if( $sth ) {
			return oci_fetch_array($sth, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS );
		} else {
			return false;
		}
	}
	*/

	/**
	 * Checks if a given database table exists in the current table space.
	 *
	 * @param string $tableName
	 * @return boolean Whether or not the table exists.
	 */
	public function tableExists( $tableName )
	{
		$sql = 'SELECT table_name FROM user_tables WHERE table_name=\''.strtoupper(DBPREFIX.$tableName).'\'';
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
		$sql = 'SELECT 1 ';
		$sql .= 'FROM user_tab_columns ';
		$sql .= 'WHERE table_name = \''.strtoupper( DBPREFIX.$tableName ).'\' ';
		$sql .= 'AND column_name = \''.strtoupper( $fieldName ).'\' ';

		$sth = $this->query( $sql );
		$row = $this->fetch( $sth );

		return $row ? true : false;
	}

	public function newid($table, $after)
	{
		if ($after) return $this->lastid;

		$this->lastid = $this->nextId($table);

		return $this->lastid;
	}

	// See mssqldriver->copyid for comments
	public function copyid( $table, $after )
	{
		$table = $table; $after = $after; // keep analyzer happy	
		// nothing to do
	}
	
	private function setError()
	{
		$error = null;
		if (is_resource($this->last_stmt)) {
            $error = oci_error($this->last_stmt);
        } else {
			if (is_resource($this->dbh)) {
            $error = oci_error($this->dbh);
			} else {
				$error = oci_error();
        }
		}
         if (is_array($error)) {
         	$this->errormessage = $error['message'];
         	$this->theerrorcode = $error['code'];
         }
	}

	public function error(){
		/**
        if (is_resource($this->last_stmt)) {
            $error = oci_error($this->last_stmt);
        } else {
            $error = oci_error($this->dbh);
        }

            return $error['message'];
        }
		*/
		return $this->errormessage;
	}

	public function errorcode()
	{
		switch( $this->theerrorcode ) {
			case 955:
				return DB_ERROR_ALREADY_EXISTS;
			case 1:
				return DB_ERROR_CONSTRAINT;
			default:
				return "ORASQL: ".$this->theerrorcode;
		}
	}
	
	public function quoteIdentifier($name)
	{
		// handle renames
		if (array_key_exists($name, $this->scent2ora)) {
			return $this->scent2ora[$name];
		}		
		// default
		return strtoupper($name);
	}

	public function limitquery($sql, $start, $count)
	{
		//return $sql;

		$min = $start + 1;
		$max = $start + $count;
		
		$result =  'SELECT * FROM (';
		$result .= 'SELECT limited.*, ROWNUM rnum FROM (';
		// BZ#16337 '' wasn't replaced by IS NULL
		$result .= $this->replaceEmptyString($sql);
		$result .= ') limited WHERE ROWNUM <= ' . $max;
		$result .= ') WHERE rnum >= ' . $min;

		return $result;
	}

	public function tablename($str)
	{
		return $this->quoteIdentifier(DBPREFIX.$str);
	}

	public function getSequenceName($seqname)
	{
		return preg_replace('/[^a-z0-9_.]/is', '', $seqname)."_seq";
	}

	
	/**
	 * Return the identifier to use for the autoincrement for insert into statements<br>
	 * for Oracle auto increments work like:
	 * INSERT INTO suppliers (supplier_id, supplier_name)
	 * VALUES (supplier_seq.nextval, 'WoodWing');
	 * So for table supplier, the nextVal is supplier_seq.nextval<br>
	 *
	 * @param $table string     table we need the nextval identifier for
	 * @return string           nextval string
	 */
	public function GetNextVal( $table )
	{
		return preg_replace('/[^a-z0-9_.]/is', '', $table)."_seq.nextval";
	}

    public function nextId($seqname, $ondemand = true)
    {
    	$ondemand = $ondemand; // keep analyzer happy
        $seqname = $this->getSequenceName($seqname);
        $sth = $this->query("SELECT ${seqname}.nextval FROM dual");
        $arr = $this->fetch($sth);
        return $arr["nextval"];			// auto lowercased (!)
    }

    public function createSequence($seq_name)
    {
        return $this->query('CREATE SEQUENCE '
                            . $this->getSequenceName($seq_name));
    }

    public function dropSequence($seq_name)
    {
        return $this->query('DROP SEQUENCE '
                            . $this->getSequenceName($seq_name));
    }

	/**
	 * Handles statements involving blob or large blob inserts/updates. In case of large blob
	 * only one large blob per statement is supported. In case of blobs multiple blobs can be
	 * inserted/updated.
	 * @param $stmt Prepared statement with bind variables
	 * @param $data Array of blob data to be inserted/updated
	 * @param $largeblob data is of type large blob.
	 */
	private function _oracleexecute($stmt, $data = array(), $largeblob = false)
	{
		PerformanceProfiler::startProfile( '_oracleexecute', 5 );
		$this->last_stmt = $stmt;

		if ($largeblob) {
			if ( $data ) { // The variable $data can be an empty array if empty_blob() has been called.  
				$blobDescriptor = oci_new_descriptor($this->dbh, OCI_D_LOB);
				if (!oci_bind_by_name($stmt, ':bind' . 0, $blobDescriptor, -1, OCI_B_BLOB)) {
					return null;
				}	
				$blobDescriptor->WriteTemporary($data[0], OCI_TEMP_BLOB );
			}
		} else {
			$element = 0;
			foreach ( $data as $blobItem ) {
				$blobDescriptor = "blobDescriptor" . strval( $element );
				$$blobDescriptor = oci_new_descriptor( $this->dbh, OCI_D_LOB ); // foreach blob we need an unique descriptor
				if ( !oci_bind_by_name( $stmt, ":bind" . strval( $element ), $$blobDescriptor, -1, OCI_B_CLOB ) ) {
					return null;
				}	
				$$blobDescriptor->WriteTemporary( $blobItem, OCI_TEMP_CLOB );
				$element += 1;
			}
		}

		PerformanceProfiler::startProfile( 'oci_execute', 5 );
		$success = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
		
		// Close descriptors
		if ( $largeblob ) {
			$blobDescriptor->close();
		} else {
			$element = 0;
			$numberOfDescriptors = count( $data );
			for ( $element = 0; $element < $numberOfDescriptors; $element++ ) {
				$blobDescriptor = "blobDescriptor" . strval( $element );
				$$blobDescriptor->close();				
			}
		}
		PerformanceProfiler::stopProfile( 'oci_execute', 5 );

        if (!$success) {
			return null;
        }	

		PerformanceProfiler::stopProfile( '_oracleexecute', 5 );
		return $stmt;
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

		$res = array();
	    PerformanceProfiler::startProfile( 'tableInfo', 4 );

		$count = @OCINumCols($sth);
		for ($i = 0; $i < $count; $i++) {
			$fieldName = @OCIColumnName($sth, $i+1);
			$isCustom = isset($fieldName[1]) && $fieldName[1] == '_' && ( $fieldName[0] == 'C' || $fieldName[0] == 'c' );
			if( !$isCustom ) {
				$fieldName =  strtolower( $fieldName );
			}
			$res[$i] = array(
				//'table' => '',
				'name'  => $fieldName,
				'type'  => @OCIColumnType($sth, $i+1),
				//'len'   => @OCIColumnSize($sth, $i+1),
				//'flags' => '',
			);
		}
	    PerformanceProfiler::stopProfile( 'tableInfo', 4 );
        return $res;
    }

	public function listcolumns($sth)
	{
		$columns = array();
		$colcount = ($sth);
		for ($i = 0; $i < $colcount; $i++) {
			$colname = @OCIColumnName($sth, $i);
			$column = array();
			$column['name'] = $colname;
			$column['index'] = $i;
			$column['dbtype'] = @OCIColumnType($sth, $i);
			$column['len'] =  @OCIColumnSize($sth, $i);
			$column['flags'] = null;
			$column['qouted'] = true;
			$column['isblob'] = false;
			$columns[strtolower($colname)] = $column;
		}
		return $columns;
	}


	public function autoincrement($sql)
	{
		$r = array();
		if (preg_match ('/^insert into ([a-z0-9_`"]*)/is', $sql , $r) > 0) {			// including escape chars for db
			$id = $this->newid($r[1], false);
			$sql = preg_replace('/^insert into ([a-z0-9_`"]*) \((.*)\) *values *\((.*)\)/is',
								'insert into \1 (`id`, \2) values ('.$id.', \3)', $sql);
		}

		return $sql;
	}

	public function toDBString( $str )
	{
		//Don't interfere with glyphs around DB column name: avoid `-conflict in _dbindep later
		$str = str_replace('`', "##`##", $str);	// escape `, will be unescaped in _dbindep above

		//Escape ' for this database
		return str_replace("'", "''", $str);
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
		foreach ($this->scent2ora as $propkey => $columname) {
			if (strtolower($propkey) == strtolower($propname)) {
				return $columname;
			}
		}
		return $propname;
	}

	/**
	 * Returns 'now' (time stamp)
	 * @return string 
	**/
	public function nowStamp()
	{
		return 'SYSTIMESTAMP';
	}

	/**
	 * Returns a field concatenation
	 * @param array $fieldNames List of field names to be concatenated
	 * @return string Concatenation
	**/
	public function concatFields( $fieldNames )
	{
		$sql = '';
		$sep = '';
		foreach( $fieldNames as $fieldName ) {
			$sql .= $sep.$fieldName;
			$sep = ' || ';
		}
		return $sql;
	}

	public function createViewSQL()
	{
        return 'CREATE OR REPLACE VIEW ';
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

		// Table already exist, so just truncate will do
		self::truncateTable($tablename);
		PerformanceProfiler::stopProfile( 'createAuthorizedTempTable', 4 );
		return $tablename;
	}

	/**
	 * Method creates a copy of the passed temporary table ($source).
	 * First the structure is copied and next all records of the original table
	 * are inserted into the copy.
	 * @param string $tablename name of the copy
	 * @param string $source name of the source table
	 * @throws Exception
	 */
	public function createCopyTempTable($tablename, $source)
	{		
		throw new Exception("Temporary table $source cannot be copied to $tablename (not supported for DBMS Oracle).");		
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
//		$sql  = "CREATE GLOBAL TEMPORARY TABLE $tablename ( ";
//		$sql .= "`id` INT NOT NULL, ";
//		$sql .= "PRIMARY KEY (`id`) ) ";
//		$sql .= "ON COMMIT PRESERVE ROWS ";
//		self::query($sql);
		
		//because table may already exist, truncate to be sure no values left in temp table...
		self::truncateTable($tablename);
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
		self::query($sql);
		PerformanceProfiler::stopProfile( 'truncateTable', 4 );
	}	

	/**
	 * For Oracle empty string comparissons are treated in special way.
	 * Therefore this function is called just before SQL parameter substitution
	 * to detect if we are dealing with empty string comparissons. If so, it replaces
	 *    `foo` = '' with `foo` IS NULL
	 *    `foo` != '' with `foo` IS NOT NULL.
	 *
	 * @since 9.7.0
	 * @param integer $key The index of the parameter.
	 * @param string|integer|float|array $value
	 * @param string $sql The full SQL statement (executed after param subst).
	 * @param string $sqlLhs The SQL fragment Left Hand Side of the placeholder that is about to get subst, including the operator, if any.
	 * @param string $substSql The SQL statement with (partly) already subst params so far.
	 * @return bool Whether or not the SQL param subst is overruled (already done).
	 */
	protected function preSerializeParam( $key, $value, $sql, $sqlLhs, &$substSql )
	{
		// ------
		// There are two places to expect a "column = value" expression, that is:
		// after a SET-clause:
		//    SET col1=val1, col2=val2, ...
		// or after a WHERE-clause:
		//    WHERE col1=val1 AND col2=val2 ...
		// Only for the WHERE-clause, a "col = ''" needs to be replaced with a "col IS NULL".
		//
		// Note that:
		// - WHERE clauses appear for SELECT, UPDATE and DELETE statements, but
		//   not for INSERT statements.
		// - there can be multiple/nested statements in SQL, such as UNION SELECT or
		//   SELECT in SELECT each having their own WHERE-clauses.
		// ------

		// Find out which parameters are part of the WHERE/HAVING/JOIN-clause only.
		// For each parameter this function is called, but we need to compose the WHERE params
		// only once, so we do so when the first param is requested only.
		static $searchParamIds;
		if( $key == 0 ) {
			$searchParamIds = $this->getSearchParamIds( $sql );
			$searchParamIds = array_flip( $searchParamIds );
		}
		
		// Now replace:
		//  `foo` = '' with `foo` IS NULL
		//  `foo` != '' with `foo` IS NOT NULL
		$serialized = false;
		if( array_key_exists( $key, $searchParamIds ) && // is it a WHERE param?
			$value === '' ) { // empty string param subst?
			$pos = 0;
			$operator = $this->readOperatorFromEol( $sqlLhs, $pos );
			if( $operator ) {
				if( $operator == '!=' || $operator == '<>' ) {
					$substSql .= substr( $sqlLhs, 0, $pos ).' IS NOT NULL ';
					$serialized = true;
				} else if( $operator == '=' ) {
					$substSql .= substr( $sqlLhs, 0, $pos ).' IS NULL ';
					$serialized = true;
				}
			}
		}
		return $serialized;
	}
	
	/**
	 * For a given SQL statement, this function determines which ? placeholders
	 * are part of the WHERE/HAVING/JOIN-clause. The placeholders are indexed [0...n-1].
	 *
	 * Example 1:
	 *   UPDATE SMART_TICKETS SET `expire` = ? WHERE `ticketid` = ?
	 * The first ? placeholder is part of the SET-clause and the second of the WHERE-clause.
	 * In this example the function returns array(1).
	 *
	 * Example 2:
	 *   SELECT `ticketid` FROM SMART_TICKETS WHERE `usr` = ? AND `appname` = ?
	 * Both ? placeholders are part of the WHERE clause.
	 * In this example the function returns array(0,1).
	 *
	 * Example 3:
	 *   SELECT `id` FROM SMART_USERS u LEFT JOIN SMART_USRGRP x ON (x.`usrid` = u.`id` AND x.`grpid` = ?)
	 * Both ? placeholders are part of the LEFT JOIN clause.
	 * In this example the function returns array(0,1).
	 *
	 * Example 4:
	 *   SELECT `child` FROM SMART_OBJECTRELATIONS WHERE `child` = ? GROUP BY `child` HAVING COUNT(1) > ?
	 * Both ? placeholders are part of the WHERE- and HAVING clauses.
	 * In this example the function returns array(0,1).
	 *
	 * @since 9.7.0
	 * @param string $sql
	 * @return integer[] Indexes of ? placeholders used in all WHERE/HAVING/JOIN-clauses only.
	 */
	private function getSearchParamIds( $sql )
	{
		// Performance optimization: Quick check if the SQL might contain a WHERE/HAVING/JOIN clauses.
		if( !stristr( $sql, 'WHERE' ) && !stristr( $sql, 'HAVING' ) && !stristr( $sql, 'JOIN' ) ) {
			return array();
		}

		// Wrap all fields, table names, SQL keywords, etc between [] brackets.
		// (This is to simplify search & replace later.)
		// For example: 
		//    UPDATE SMART_TICKETS SET `expire` = ? WHERE `ticketid` = ?
		// becomes:
		//    [UPDATE] [SMART_TICKETS] [SET] `[expire]` = ? [WHERE] `[ticketid]` = ?
		$sql = preg_replace( '/([_a-z0-9]+)/is', '[${1}]', $sql );
		
		// Postfix all ? placeholders with a sequence number.
		// For example: 
		//    [UPDATE] [SMART_TICKETS] [SET] `[expire]` = ? [WHERE] `[ticketid]` = ?
		// becomes:
		//    [UPDATE] [SMART_TICKETS] [SET] `[expire]` = ?000 [WHERE] `[ticketid]` = ?001
		$sqlParts = explode( '?', $sql );
		$sql = '';
		foreach( $sqlParts as $paramId => $sqlPart ) {
			$paramId = str_pad( $paramId, 3, '0', STR_PAD_LEFT );
			if( $paramId == count($sqlParts)-1 ) {
				$sql .= $sqlPart;
			} else {
				$sql .= $sqlPart.'?'.$paramId;
			}
		}

		// Prefix important SQL keywords with a special marker ("\0"),
		// and remove the [] brackets again. On purpose, only the keywords
		// are listed that change clause context. For example AND/OR are left
		// out because e.g. you are still in the same WHERE clause.
		// For example: 
		//    [UPDATE] [SMART_TICKETS] [SET] `[expire]` = ?000 [WHERE] `[ticketid]` = ?001
		// becomes:
		//    \0UPDATE [SMART_TICKETS] \0SET `[expire]` = ?000 \0SPLIT `[ticketid]` = ?001
		$sql = str_ireplace( 
			array ( 
				'[SELECT]',  '[UPDATE]',     '[DELETE]',   '[INSERT]', 
				'[INTO]',    '[VALUES]',     '[FROM]',     '[JOIN]',
				'[WHERE]',   '[SET]', 
				'[LIMIT]',   '[ORDER] [BY]', '[GROUP] [BY]', '[HAVING]'
			),
			array ( 
				"\0SELECT",  "\0UPDATE",     "\0DELETE",   "\0INSERT", 
				"\0INTO",    "\0VALUES",     "\0FROM",     "\0SPLIT", 
				"\0SPLIT",   "\0SET", 
				"\0LIMIT",   "\0ORDER BY",   "\0GROUP BY", "\0SPLIT"  
			),
			$sql
		);

		// Split the SQL into WHERE/HAVING/JOIN clauses.
		// For example: 
		//    \0UPDATE [SMART_TICKETS] \0SET `[expire]` = ?000 \0WHERE `[ticketid]` = ?001
		// becomes:
		//    array( "\0UPDATE [SMART_TICKETS] \0SET `[expire]` = ?000", "`[ticketid]` = ?001" )
		$searchClauses = '';
		$searchSplits = explode( "\0SPLIT", $sql );
		if( $searchSplits ) {
			array_shift($searchSplits); // first one is not a WHERE/HAVING/JOIN part
			foreach( $searchSplits as $searchSplit ) {
				$searchChunks = explode( "\0", $searchSplit );
				// Now, the first item represents the WHERE/HAVING/JOIN clause only. All the following
				// clauses are non-WHERE/HAVING/JOIN clauses and needs to be ignored / skipped.
				$searchClauses .= array_shift($searchChunks); // only first item is the WHERE/HAVING/JOIN clause
			}
		}
		
		// Parse the sequence numbers of the ? placeholders (as injected above)
		// for the ones that appear in the WHERE/HAVING/JOIN clauses only.
		$searchParamIds = array();
		$searchParams = explode( '?', $searchClauses );
		if( $searchParams ) {
			array_shift( $searchParams ); // first one is no param
			foreach( $searchParams as $searchParam ) {
				$searchParamIds[] = intval(substr( $searchParam, 0, 3 ));
			}
		}
		return $searchParamIds;
	}
	
	/**
	 * Reads the given SQL string backwards (for end to start) to see if the 
	 * very last text fragment is a parameter comparisson operator. Only white spaces
	 * are ignored. When anything else is found, an empty string is returned.
	 * When an operator is found, that operator is returned.
	 * Returned operators are: =, !=, <, >, >=, etc
	 *
	 * @since 9.7.0
	 * @param string $sqlPart The SQL fragment to analyze.
	 * @param integer $pos The character pos in $sqlPart where the operator is found.
	 * @return string The found operator. Empty when none found at the far end of string.
	 */
	private function readOperatorFromEol( $sqlPart, &$pos )
	{
		static $operators = array( '=', '!', '<', '>', '+', '-', '*', '/' );
		$operator = '';
		$sqlPart = rtrim( $sqlPart ); // get rid of white spaces at end of string
		for( $pos = strlen( $sqlPart ) - 1; $pos >= 0; $pos-- ) {
			$char = $sqlPart[$pos];
			if( in_array( $char, $operators ) ) {
				$operator = $char.$operator;
			} else {
				break; // quit search
			}
		}
		return $operator;
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
		$insertsql = " INSERT ALL ";
		foreach ($rows as $row) {
			$id = $row['id'];
			$insertsql .= " INTO $temptvi (id) VALUES ($id)";
		}
		$insertsql .= " SELECT * FROM dual";		
		
		return  $insertsql;
	}	

	/**
	 * @retun array Database specific information for displaying purposes.
	 */
	public function getClientServerInfo()
	{
		$info = array();
		if( $this->isConnected() ) {
			$info['Database version'] = oci_server_version( $this->dbh );
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
	 */
	public function makeTempTableName($name)
	{
		return $this->quoteIdentifier($name);
	}	

	/**
	 * Retrieves the DB version and checks if this DB driver is compatible with that.
	 *
	 * @param string $help Installation notes how to solve a problem (in case an exception is thrown).
	 * @thows BizException When unsupported DB version is detected.
	 */
	public function checkDbVersion( &$help )
	{
		$help = '';
		// TODO: check for DB version
	}
	
	/**
	 * Retrieves the DB settings and checks if this DB driver is compatible with those.
	 * Checks if the character encoding is set correctly.
	 *
	 * @param string $help Installation notes how to solve a problem (in case an exception is thrown).
	 * @thows BizException When unsupported DB settings are detected.
	 */
	public function checkDbSettings( &$help )
	{
		$help = '';
		$sql = "SELECT USERENV ('LANGUAGE') as CLIENT FROM DUAL";
		$sth = $this->query( $sql );
		$row = $this->fetch( $sth );
		if( isset($row['CLIENT']) ) {
			$clientLang = $row['CLIENT'];
		} else if( isset($row['client']) ) {
			$clientLang = $row['client'];
		} else {
			$clientLang = '';
		}
		if( !stristr( $clientLang, 'AL32UTF8' ) ) {
			$help = 'Change the database character set with: ALTER DATABASE [db_name] CHARACTER SET AL32UTF8;';
			$detail = 'Client language incorrect: "'.$clientLang.'". Should be AL32UTF8.';
			throw new BizException( null, 'Server', $detail, 'Invalid Configuration' );
		}
		
		/*
		 * From version 10 onwards Enterprise should support UTF8 for all databases. Until that moment we can
		 * skip the test on NCHAR_CHARACTERSET (see also BZ#32997).  
		$sql = 'SELECT * FROM NLS_DATABASE_PARAMETERS';
		$sth = $this->query( $sql );
		$parameter = strtolower('PARAMETER'); // Fetch returns columns in lower case.
		$value = strtolower('VALUE'); 
		while( ($row = $this->fetch( $sth )) ) {
			if( strstr($row[$parameter], 'NCHAR_CHARACTERSET') ) {
				if( !stristr($row[$value], 'UTF8') ) {
					$help = 'Change the database national character set with: ALTER DATABASE [db_name] NATIONAL CHARACTER SET UTF8;';
					$detail = $row[$parameter].' is incorrect: "'.$row[$value].'". Should be UTF8.';
					throw new BizException( null, 'Server', $detail, 'Invalid Configuration' );
				}
			}
		}
		 */
		
		// See if there are Enterprise tables installed (by counting SMART_* tables).
		$sql = 'SELECT COUNT(TABLE_NAME) AS ENTTABCNT '.
				'FROM ALL_TABLES WHERE TABLE_NAME LIKE \''.DBPREFIX.'_%\'';
		$sth = $this->query( $sql );
		$row = $this->fetch( $sth );
		$isEntDbInstalled = (bool)(isset($row['ENTTABCNT']) && $row['ENTTABCNT'] > 0);
		
		// BZ#18223 - Test on existing v7 installation, applicable between v7 to v7.0.3
		if( $isEntDbInstalled ) { // Avoid throwing the error below for new DB installations.
			$sql = "SELECT * FROM TEMP_AV";
			$sth = $this->query( $sql );
			if( !$sth ) {
				$help = 'Please see the release notes of v7.0.3 to see how to create the table.';
				$detail = 'Table TEMP_AV does not exist in the database.';
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
		$tableName = $tableName; $autoIncrementValue = $autoIncrementValue;
		// TODO: Reset the auto increment for Oracle
	}

	/**
	 * Forces the optimizer to join the tables in the order in which they are listed in the FROM clause. In case of 
	 * multiple selects within one query (nested queries) pass the separate select statements (if the 'forced' join
	 * has to be applied on the different select statements). Not yet supported for Oracle.
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
		$sql  = "SELECT INDEX_NAME " ;
		$sql .=	"FROM USER_IND_COLUMNS ";
		$sql .= "WHERE TABLE_NAME = '$tableName' "; 
		$sql .= "GROUP BY INDEX_NAME ";
		$sth = $this->query($sql);
		$index = array();
		$field = strtolower('INDEX_NAME'); // fetch returns fieldnames in lower case. 
		while(($row = $this->fetch($sth))){
			$index[] = strtolower($row[$field]);
		}

		return ($index);
	}	

	/**
	 * Closes all open tables, forces all tables in use to be closed, and flushes the query cache.
	 * Not yet implemented for Oracle. 
	 */	
	public function flush()
	{
		return;
	}

	/**
	 * Returns boolean false to indicate that Oracle doesn't support 
	 * multibyte data storage.	 
	 * @return boolean False.
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
	 * Function will handle the autoincrement id and add into the composed sql.
	 *
	 * Example:
	 * Incoming parameters:
	 * $fieldNames = array( a,b,c,d )   $fieldValues = array( array(1,2,3,4), array(5,6,7,8), array(9,10,11,12)  )
	 *
	 * Composed sql:
	 * INSERT ALL INTO SMART_OBJECTLOCKS (ID, a, b, c, d ) VALUES ( SMART_OBJECTLOCKS_seq.nextval, val1, val2, val3, val4 )
	 * SELECT 1 AS val1, '2' AS val2, 3 AS val3, '4' AS val4 FROM dual
	 * UNION
	 * SELECT 5 AS val1, '6' AS val2, 7 AS val3, '8' AS val4 FROM dual
	 * UNION
	 * SELECT 9 AS val1, '10' AS val2, 11 AS val3, '12' AS val4 FROM dual
	 *
	 * ============
	 * @TODO:
	 * Currently when the multi-insertion is done, no new ids are returned, perhaps we want to support this in the future.
	 * This is the sql syntax to get the new ids.
	 * // For debugging only.
	 * 'DROP TABLE new_ids_view';
	 *
	 * // Installation time only:
	 * CREATE GLOBAL TEMPORARY TABLE new_ids_view (id INTEGER) ON COMMIT DELETE ROWS;
	 *
	 * DEFINE val0 = SMART_OBJECTLOCKS_seq.nextval
	 *
	 * // --- data injection: ---- //
	 * INSERT ALL INTO SMART_OBJECTLOCKS (ID, OBJECT, USR, TIMESTAMP, IP) VALUES ( &&val0, val1, val2, val3, val4 )
	 * INTO new_ids_view (id) VALUES ( &&val0 ) // This sql is to store all the new ids.
	 *
	 * // ---- data retrieval: --- //
	 * SELECT 268 AS val1, 'woodwing' AS val2, SYSTIMESTAMP AS val3, '192.168.33.1' AS val4 FROM dual
	 * UNION
	 * SELECT 267 AS val1, 'woodwing' AS val2, SYSTIMESTAMP AS val3, '192.168.33.1' AS val4 FROM dual
	 * UNION
	 * SELECT 201 AS val1, 'woodwing' AS val2, SYSTIMESTAMP AS val3, '192.168.33.1' AS val4 FROM dual
	 *
	 * SELECT * FROM new_ids_view; // contains the inserted ids
	 *
	 * COMMIT; // deletes all rows from the temp table
	 * ============
	 *
	 * @param string $tableName Table name without the 'smart_' prefix.
	 * @param string[] $fieldNames List of DB field names.
	 * @param array[] $fieldValues List of array list which contains the database values. Refer to function header.
	 * @param bool $autoincrement Whether the table to be inserted has autoincrement db field.
	 * @return string The composed sql string.
	 */
	public function composeMultiInsertSql( $tableName, array $fieldNames, array $fieldValues, $autoincrement )
	{
		$sql = '';

		// During a multi-set-props:
		// The .nextval and .currval are dangerous in a multi-user environment such as Enterprise.
		// You can not simply use .nextval and then .nextval+1 for the next record. The reason is that
		// the next value is determined only once for the entire INSERT ALL and so other users inserting
		// records into the same table in the meantime would lead into duplicate key violations.
		// And, for the same reason, .nextval can not be used to determined which ids were inserted by
		// this INSERT ALL statement without the risc being interfered by other users that are inserting too.
		// For that, a temporary table is used to capture the created record ids. Note that the temporary table
		// can only be seen by the current session, and so won't interfere with other users.

		$dbTableName = $this->tablename( $tableName );

		/* --- data injection: ---- */
		$numberOfFieldNames = count( $fieldNames );
		if( $autoincrement ) {
			// $sql .= 'DEFINE val0 = SMART_OBJECTLOCKS_seq.nextval '.PHP_EOL; // Need to investigate further, seems not supported when executed from script.
			array_unshift( $fieldNames, 'ID' );
			$numberOfFieldNames += 1;
		}
		for( $ctr=0; $ctr<$numberOfFieldNames; $ctr++ ) {
			if( $autoincrement && $ctr == 0 ) {
				// $fieldNamesVariables[] = '&&val0'; // Same for this, need to investigate further.
				$fieldNamesVariables[] = $dbTableName.'_seq.nextval';
			} else {
				$fieldNamesVariables[] = 'val' . $ctr;
			}
		}

		// INSERT ALL INTO table_name ( ID, a,b,c,d) VALUES ( SMART_OBJECTLOCKS_seq.nextval, val1, val2, val3, val4 )
		$sql .= 'INSERT ALL ';
		$sql .= 'INTO '.$dbTableName.' ';
		$sql .= '(`' . implode( '`, `', $fieldNames ).'`) ';
		$sql .= 'VALUES ( '. implode( ', ', $fieldNamesVariables ).' ) ' . PHP_EOL;

		$dataValueSqls = array();
		foreach( $fieldValues as $fieldValuesString ) {
			$dataValues = array();
			$ctr = $autoincrement ? 1 : 0;
			$fieldValuesRow = $fieldValuesString;
			foreach( $fieldValuesRow as $fieldIndex => $fieldValue ) {
				$fieldName = $fieldNames[$autoincrement ? $fieldIndex+1 : $fieldIndex];
				if( is_string( $fieldValue ) &&
					$fieldName != 'timestamp' ) { // TODO: Enterprise v10 use 'timestamp' Type in the $params.
					$fieldValue = "'" . $this->toDBString( $fieldValue ) . "'";
				} else {
					$fieldValue = strval( $fieldValue );
				}

				$dataValues[] = $fieldValue . ' AS ' . $fieldNamesVariables[$ctr];
				$ctr++;
			}
			$dataValueSqls[] = 'SELECT ' . implode( ', ', $dataValues) .' FROM dual ' .PHP_EOL;
		}

		$dataValueSql = implode( 'UNION '.PHP_EOL, $dataValueSqls );
		$sql = $sql . $dataValueSql;
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
	 * @param array $limit, keys: 'min'/ 'max', TRUE=ASC, FALSE=DESC.
	 * @param array $values Values used for parameter substitution.
	 * @return string $sql The composed sql statement.
	 */
	public function composePagedQuerySql( $tableName, $keyCol, $where, $orderBySQL, $limit, &$values )
	{
		// Keep Analyzer Happy.
		$tableName = $tableName;
		$keyCol = $keyCol;
		$where = $where;
		$orderBySQL = $orderBySQL;
		$limit = $limit;
		$values = $values;

		// There is no actual updated query for paging Oracle SQL results.
		return false;
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
			$sql = 'ALTER TABLE '.$tableName.' ADD ';
			$comma = '';
			$addFieldsSql = '';

			foreach ( $fieldInfos as $fieldInfo ) {
				$addFieldsSql .= $comma.'`'.strtoupper( $fieldInfo['Name'] ).'` '.$fieldInfo['Type'].' ';
				$comma = ',';
			}

			$sql .= '('.$addFieldsSql.')';

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

		$uppercasename = strtoupper($columnName);
		// BZ#15458 Oracle cannot modify from or to a clob field so rename, add, convert and drop column
		$tmpUppsercaseName = 'O_' . substr( $uppercasename, 2 ); // have to make sure that $tmpUppercaseName is less than 30 chars.
		$sth = $this->query("ALTER TABLE $tableName RENAME COLUMN `$uppercasename` TO `$tmpUppsercaseName`");
		$sth = (bool)$sth ? $this->query("ALTER TABLE $tableName ADD (`$uppercasename` $dbtype)") : false;
		$sth = (bool)$sth ? $this->query("UPDATE $tableName SET `$uppercasename` = `$tmpUppsercaseName`") : false;
		$sth = (bool)$sth ? $this->query("ALTER TABLE $tableName DROP COLUMN `$tmpUppsercaseName`") : false;

		return (bool)$sth;

	}

	/**
	 * Changes the definition of multiple columns a table definition at once. At this moment not supported for Oracle.
	 *
	 * @param string $tableName The name of the table (whitout prefix).
	 * @param array $fieldInfos Array of arrays containing the name and data type for each column to add.
	 * @return bool true on success or else false.
	 * @throws BizException
	 */
	public function updateMultipleColumnDefinition( /** @noinspection PhpUnusedParameterInspection */
			$tableName, $fieldInfos )
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
		$table = $this->tablename( $tableName );

		$sql = 'ALTER TABLE '.$table.' DROP (`'.implode( "`, `", $columnNames ).'`)';
		$sth = $this->query($sql);

		return (bool)$sth;
	}

	/**
	 * Before sending a Data Definition statement to the DBMS the statement has to be in line with the rules of the
	 * DBMS.
	 * An anonymous block contains statements that are separated by ';'. The block starts with 'BEGIN ' and is closed
	 * by ' END;' Everything in between has to be concatenated as one statement.
	 * Note that sometimes a line starts or ends with '\r\n'. So we scan the first or last characters of the keyword
	 * plus 2 extra.
	 *
	 * @see \OraGenerator::silentRemoveSequence
	 * @param string $statement
	 * @return boolean Statement is complete and can be send to DBMS.
	 */
	public function isCompleteStatement( &$statement )
	{
		if ( strpos( substr( $statement, 0, 8 ), 'BEGIN ' ) !== false ) {
			$statement .= ';';
			if ( strpos( substr( $statement, -7 ), ' END;' ) !== false ) {
				return true;
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a sql-statement to drop the specified table.
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @param boolean $prefix Add prefix to the name of the table.
	 * @return string sql-statement.
	 */
	public function composeDropTableStatement( $tableName, $prefix = true )
	{
		$droppedTable = $prefix ? $this->tablename( $tableName ) : $tableName;
		$statement = 'DROP TABLE '.$droppedTable.' PURGE';
		return $statement;
	}

	/**
	 * Returns a statement to drop the index of a specified table.
	 * On Oracle indexes are unique (two tables cannot have an index with the same name).
	 *
	 * @param string $tableName Name of the database table (without prefix).
	 * @param string $indexName Name of the new index.
	 * @return string sql-statement.
	 */
	public function composeDropIndexStatement( $tableName, $indexName )
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		$tableName = $tableName;
		$statement = 'DROP INDEX `'.$indexName.'`';
		return $statement;
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
		$sql= 'CREATE TABLE '.$toTable.' AS SELECT * FROM '.$fromTable; // Creates table with data but not the indexes.
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
	 * Creates a statement to drop a temporary table.
	 *
	 * @param string $tableName Name of the temporary table.
	 * @return string sql-statement.
	 */
	public function composeDropTempTableStatement( $tableName )
	{
		return 'TRUNCATE TABLE '.$tableName;
	}
}
