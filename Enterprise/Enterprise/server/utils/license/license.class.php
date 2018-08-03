<?php
/**
 * SCE License class.
 *
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 
 *
 * This module handles the installation, checks and management of SCE Server licenses
 * When a user logs on to SCE
 * - the number of concurrent connections of ALL applications to the SCE Server is checked.
 * - if the application has a "concurrent user license", the number of concurrent users for that application is checked.
 *
 * The first part contains some "primitive" functions to handle the reading and writing to the database and filestore.
 * The Licensestring helper class is used to encrypt and decrypt strings (installation code and license information)
 *

 * ===================== IMPORTANT =================
 * If you change this file, you may also have to change this file in the SmartReg package!!!
 * ===================== IMPORTANT =================

*/
	define ('PRODUCTKEY', 'EntServer1000' ); //Moved this from serverinfo.php to encrypted area...

	include_once BASEDIR.'/server/utils/license/licensestring.class.php';
	include_once BASEDIR.'/server/utils/license/license_codes.inc.php';

	//Determines the number of days after a license will become invalid in case a "configuration change" has taken place
	//Note: these are business days, on thirsday, friday and saturday, 1 or 2 extra demo days will be added
	define('DEMODAYS',	'10');  

	define('TRYRENEW_DAYSBEFOREEXPIRATION',	'14');  

	//Location of the license information in the Filestore	
	define('FSLICENSEDIR', WOODWINGSYSTEMDIRECTORY . '/ProductInfo' );
	define('FSLICENSEDIRTIMESTAMP', FSLICENSEDIR. '/_system_' );

	define( 'DB_CONTACT_PREFIX', 'contactinfo_' );
	define( 'DB_PROXY_PREFIX', 'proxyinfo_' );

	include_once BASEDIR.'/server/utils/license/post.inc.php';
	include_once BASEDIR.'/server/regserver.inc.php';


class License
{
	/**
	 * @var int
	 * Contains error information in case a certain function returns false
	 */
	private $mWWLError;
	
	/**
	 * @var object
	 * The helper object to encrypt and decrypt strings
	 */
	private $mLicenseString;

	/**
	 * @var object
	 * The database driver to execute SQL statements
	 */
	private $mDBDriver;

	/**
	 * @var boolean
	 * In case the user limit of a certain application has been reached,
	 * may we delete old tickets (for this application) automatically?
	 * If so, note that only tickets that logged on more than 'mKeepAliveMinutes' (see below) ago will be deleted.
	 */

	private $mAutoCleanTicketsForApps = false; //To do: make it configurable...

	/**
	 * @var boolean
	 * In case the connection limit of all applications has been reached,
	 * may we delete old tickets automatically?
	 * If so, note that only tickets that logged on more than 'mKeepAliveMinutes' (see below) ago will be deleted.
	 */

	private $mAutoCleanTicketsForConnections = false; //To do: make it configurable...

	/**
	 * @var integer
	 * In case of 'mAutoCleanTicketsForApps' or 'mAutoCleanTicketsForConnections', 
	 * never delete the tickets that have logged on in the last x ('keep alive' ) minutes.
	 */

	private $mKeepAliveMinutes = 30;


	/**
	 * The name of the "secret system user" that will leave a temporary footprint in the tickets table after installing a license
	 * @var string
	 */

	private $mInstallTicketID = '_install_'; 

	/**
	 * @var boolean
	 * for security reasons, only log when hidden SECRETLICENSELOG option is defined (default disabled)
	 */

	private $mLicLog = false; 


	/**
	 * @var integer
	 * bit flags: which anti-hack tests should be done?
	 * Initially: no tests at all. However, the license can contain test flags! 
	 * This can be set in SmartReg per license!
	 * See license_codes.inc.php for the possible bitflags!
	 */

	private $mTestFlags = 0; 

	/**
	 * @var boolean
	 * Avoid recursion in auto renew function
	 */

	private $mInAutoRenew = false;

	/** @var bool $defaultSemaId Set when a semaphore is created with the 'default' postfix. */
	private $defaultSemaId = null;

	/**
	 * Construct the License.
	 * Initialize the helpers...
	 *
	 */
	public function __construct()
	{
		$this->mWWLError = 0;
		$this->mLicenseString = new LicenseString();
		$this->mDBDriver = DBDriverFactory::gen();
		srand( time() );
		if ( defined( 'SECRETLICENSELOG' )) {
			$this->mLicLog = true;
		}
	}
	
	/**
	 * Add a zero in case the number is less than 10
	 *
	 * @param string $str
	 * @return string
	 */
	private function make2( $str )
	{
		if ( strlen( $str ) == 1 ) {
			return "0" . $str;
		} else {
			return $str;
		}
	}
	
	/**
	 * Return the id and creation date of the last object in the objects (or deletedobjects) table
	 *
	 * @param string $created
	 * @return int max id
	 *
	 */
	private function lo_getMaxObj( &$created )
	{
		$created = 0;

		//== NORMAL: objects =============
		$dbo = $this->mDBDriver->tablename('objects');

		$sql = "SELECT max(`id`) AS `maxid` FROM $dbo";
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog); //false: do not write in the log file
		if ( !$sth )
			return 0;
		$row = $this->mDBDriver->fetch($sth);
		if( !$row )
			return 0;

		$maxid = 0;
		if ( isset( $row['maxid'] ) && $row['maxid'] )
			$maxid = $row['maxid'];

		//== DELETED: deletedobjects =============
		$maxid_deleted = 0;
		$dbdo = $this->mDBDriver->tablename('deletedobjects');

		$sql = "SELECT max(`id`) AS `maxid` FROM $dbdo";
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog); //false: do not write in the log file
		if ( $sth )
		{
			$row = $this->mDBDriver->fetch($sth);
			if( $row )
			{
				if ( isset( $row['maxid'] ) && $row['maxid'] )
					$maxid_deleted = $row['maxid'];
			}
		}

		//Assume the maximum is found in the normal objects table		
		$maxTable = $dbo;
		//If the maximum is found in the deleted objects table, use that id and creation date (below)
		if ( $maxid_deleted > $maxid )
		{
			$maxTable = $dbdo;
			$maxid = $maxid_deleted;
		}
		
		//If no maxid, created stays 0.
		if ( $maxid )
		{
			//Find the creation date of the object with id 'maxid'
			$sql = "SELECT `created` FROM $maxTable WHERE `id`=$maxid";
			$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
			if ( $sth )
			{
				$row = $this->mDBDriver->fetch($sth);
				if( $row )
				{
					$maxunix = strtotime( $row['created'] );
					if ( $maxunix !== false )  {
						$created = $this->mLicenseString->unix2WWTimeStr( $maxunix );
					}
				}
			}
		}
		return $maxid;
	}
	
	private function initDirectories()
	{
		$dir = WOODWINGSYSTEMDIRECTORY;
		if ( !is_dir( $dir ))
			@mkdir( $dir );
		if ( !is_dir( $dir ))
		{
			$this->mWWLError = WWL_ERR_FILESTORE_SYSDIR;
			return false;
		}

		$dir = FSLICENSEDIR;
		if ( !is_dir( $dir ))
			@mkdir( $dir );
		if ( !is_dir( $dir ))
		{
			$this->mWWLError = WWL_ERR_FILESTORE_SYSDIR;
			return false;
		}

		$dir = FSLICENSEDIRTIMESTAMP;
		if ( !is_dir( $dir ))
			@mkdir( $dir );
		if ( !is_dir( $dir ))
		{
			$this->mWWLError = WWL_ERR_FILESTORE_SYSDIR;
			return false;
		}
		return true;
	}

	
	/**
	 * Return the currrent "configuration parameters" of this particular installation.
	 *
	 * There are three keys:
	 *
	 * 1: return a string that represents this installation to be used as "hardware ID" (hostid in SmartReg)
	 *    It is based on the creation dates of two filestore directories and a database table
	 *
	 * 2: return a string that represents the current "usage" of the system
	 *    This may be used by SmartReg when reclaiming a license: 
	 *    in case the current usage is less than the usage during the last registration,
	 *    the system might be a copy...
	 *	  Returned are:
	 *      smart_objects (max id and number of objects)
	 *      file store harddisk (size and usage)
	 *      time and creation time of last object
	 *
	 * 3: return a string that represents the system configuration
	 *    This may be used by SmartReg when reclaiming a license: 
	 *    in case the current configuration differs a lot from the configuration during the last registration,
	 *    the system might be a copy...
	 *	  Returned are:
	 *	    OS, DBTYPE, DBSELECT, DBSERVER, HOSTNAME, FILESTORE, COMPUTERNAME
	 *
	 * @param int $key
	 * @return string key source
	 *
	 */
	public function getKeySource( $key ) 
	{
		$this->mWWLError = 0;
		switch( $key )
		{
			case 1:
			{
				$this->mWWLError = WWL_ERR_KEY1_DB;

				$dbstamp = '';
				//$fstampRoot = '';
				$fstampSys = '';
				$dbstampunix = '';
				
				//To keep de sql driver classes 'open'/'uncrypted', 
				//AND to avoid the driver classes to LOG our secret statements, 
				//we have some hardcoded statements here...
				$dbserver = DBSERVER;
				$dbuser = DBUSER;
				$dbpass = DBPASS;
				$dbselect = DBSELECT;
				switch( strtolower(DBTYPE) )
				{
					case 'mysql':
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
						$dbh = new mysqli( $dbserver, $dbuser, $dbpass, $dbselect, $port, $socket );

						if( $dbh->connect_error ) {
							return null;
						}
						$sql = "SELECT table_name, create_time FROM information_schema.tables ".
								"WHERE table_name = 'smart_objects' AND table_type = 'BASE TABLE' ".
								"AND table_schema = '$dbselect'";
						$sth = $dbh->query( $sql );
						if( $sth ) {
							while(( $row = $sth->fetch_array() )) {
								$table_name = $row[ 'table_name' ];
								if( $table_name == 'smart_objects' ) {
									$dbstamp = $row[ 'create_time' ];
									if( $dbstamp ) {
										$sql = "SELECT UNIX_TIMESTAMP(\"$dbstamp\")";
										$sth = $dbh->query( $sql );
										if( $sth ) {
											$row = $sth->fetch_array();
											if( $row ) {
										$dbstampunix = $row[0];
									}
										}
									}
									break;
								}
							}
						}
						break;
						
					case 'mssql':
						$connectionInfo = array( "UID"=>$dbuser,
                        				 		 "PWD"=>$dbpass,
                         		 				 "Database"=>$dbselect);
						$dbh = sqlsrv_connect( $dbserver, $connectionInfo);
						
						if( !isset($dbh) || is_resource($dbh) === false ) {
							return false;
						}	
						$sql = "SELECT name, crdate, DATEPART(YEAR,crdate) AS year, DATEPART(MONTH,crdate) AS month, DATEPART(DAY,crdate) AS day, DATEPART(hour,crdate) AS hour, DATEPART(minute,crdate) AS minute, DATEPART(second,crdate) AS second FROM master.dbo.sysdatabases where name='$dbselect'"; // (SCEnt=dbname)";
		
						$sth= @sqlsrv_query($dbh, $sql);
						if (!$sth)
							return false;
						$row = @sqlsrv_fetch_array($sth);
						
						$dbstamp = $row[ 'crdate' ];
		
						$year = $row[ 'year' ];
						$month = $this->make2($row[ 'month' ]);
						$day = $this->make2($row[ 'day' ]);
						$hour = $row[ 'hour' ];
						$minute = $this->make2($row[ 'minute' ]);
						$second = $this->make2($row[ 'second' ]);
						$str = "$year-$month-$day $hour:$minute:$second";
						$dbstampunix = strtotime( $str );
						if ( $dbstampunix === false ) {
							$dbstampunix = '';
						}
						break;

					default:
						//3rd party interface to an unknown DB??
						break;
				}
				
				if ( !$dbstamp ) {
					$dbstamp = '';
				} else if ( $dbstampunix ) {
					$dbstamp = $this->mLicenseString->unix2WWTimeStr( $dbstampunix );
				}
		/*
				else
				{
					//Try to convert to the UNIX time
					//Note: this can go wrong: see test.php; 1-12-2007 can be december or january?
					$dbstampunix = strtotime( $dbstamp );
					if ( $dbstampunix !== false ) {
						$dbstamp = $dbstampunix;
					}
				}
		*/

/*
				//As discussed with Flip, we ignore the creation date of the root filestore
				//Also because of the fact that the creation date doesn't really exists in Unix... :-((

				$dir = ATTACHMENTDIRECTORY;
				$fstampRoot = filectime( $dir );
				if ( !$fstampRoot )
					$fstampRoot = '';
				else
					$fstampRoot = $this->mLicenseString->unix2WWTimeStr( $fstampRoot );
*/

				if ( !$this->initDirectories() )
					return false;
				
				//Warning: the filectime returns the CREATION date in Windows, and the MODIFICATION date in Unix.
				//Therefor, create a subdirectory (/_system/_system) and never write in it.
				//The modification date will be the creation date...
				//However, in Unix, changing the permissions will also change the filectime(). 
				//The filemtime() still returns the real modification date in that case.
				if ( OS == 'WIN' )
					$fstampSys = filectime( FSLICENSEDIRTIMESTAMP ); //the real creation date
				else
					$fstampSys = filemtime( FSLICENSEDIRTIMESTAMP ); //the modification date, and ignore changes of the permissions
				if ( !$fstampSys ) {
					$fstampSys = '';
				} else {
					$fstampSys = $this->mLicenseString->unix2WWTimeStr( $fstampSys );
				}
		
				$key1 = "$dbstamp|$fstampSys";
				$this->mWWLError = 0;
				return $key1;
				break;
			}
			case 2:
			{
				/*
					Return: max id (smart_objects) | 
							current number of smart_objects | 
							Filestore harddisk size | 
							current Filestore usage | 
							current time | 
							creation time of last smart object | 
							mac addresses [,]
				*/
				$key2Arr = Array();
		
				//Max id smart_objects
				$maxobjcreated  = 0;
				$maxid = $this->lo_getMaxObj( $maxobjcreated );
				if ( $maxid ) {
					$key2Arr[ 0 ] = dechex( $maxid );
				} else {
					$key2Arr[ 0 ] = '';
				}
		
				//Number of smart_objects
				$key2Arr[ 1 ] = '';
				$dbo = $this->mDBDriver->tablename('objects');
				$sql = "SELECT count(*) AS `c` FROM $dbo";
				$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
				if ( $sth )
				{
					$row = $this->mDBDriver->fetch($sth);
					if( $row )
						$key2Arr[ 1 ] = dechex($row['c']);
				}
		
				$totalspace = disk_total_space( ATTACHMENTDIRECTORY );
				$freespace = disk_free_space( ATTACHMENTDIRECTORY );
				$usage = $totalspace - $freespace;
				$key2Arr[ 2 ] = dechex( intval( $totalspace / (1024 * 1024) ) ); //Mb
				$key2Arr[ 3 ] = dechex( intval( $usage / (1024 * 1024) ) ); //Mb

				//times:
				$thistime = time(); //AS time, doesn't need to be the DB time
				$key2Arr[ 4 ] = $this->mLicenseString->unix2WWTimeStr( $thistime );
				if ( $maxid ) {
					$key2Arr[ 5 ] = $maxobjcreated;
				} else {
					$key2Arr[ 5 ] = '';
				}
				
				//mac address:
				$key2Arr[ 6 ] = '';
				$macstr = '';
				/* 
				//SourceGuardian specific
				if ( function_exists( 'sg_get_mac_addresses' ))
				{
					$macs = sg_get_mac_addresses();
					$n = count( $macs );
					for ( $i=0; $i<$n; $i++ )
					{
						if ( $i > 0 )
							$macstr .= ',';
						$m = $macs[ $i ];
						$m = str_replace( ':', '', $m );
						$m = str_replace( chr(0), '', $m );
						$macstr .= $m;
					}
				}
				*/

				/*
				//Ioncube specific:
				//However, the ioncube_server_data is unhandy, and can not be used to specify the mac address.
				if ( function_exists( 'ioncube_server_data' ))
				{
					$macstr = ioncube_server_data();
				}
				*/

				$key2Arr[ 6 ] = $macstr;
		
				return implode( '|', $key2Arr );
				break;
			}
			case 3:
			{
				$key3Arr = Array();

				$code = 0;
				switch( OS )
				{
					case 'WIN': $code = 0; break;
					case 'LINUX': $code = 1; break;
					case 'UNIX': $code = 2; break;
				}
				$key3Arr[ 0 ] = $code;

				$code = 0;
				switch( strtolower(DBTYPE) )
				{
					case 'mysql': $code = 0; break;
					case 'mssql': $code = 1; break;
				}
				$key3Arr[ 1 ] = $code;

				$key3Arr[ 2 ] = DBSELECT;
				
				$key3Arr[ 3 ] = DBSERVER;
				
				$key3Arr[ 4 ] = $this->getHostName();

				$key3Arr[ 5 ] = ATTACHMENTDIRECTORY;

				$key3Arr[ 6 ] = $this->getServerNameOrAddr();
				
				//Operation system: php_uname()

//				print "<br>x=" . implode( '|', $key3Arr );
				return implode( '|', $key3Arr );
				break;
			}
		}
		return false;
	}

	/**
	 * To make reading and writing the license info in both the DB and FS an 'atomic' action, we use a semaphore.
	 * Be sure that the user (web browser) can not stop the execution half way: call ignore_user_abort() before!
	 * The interval between two attempts should be long enough to ensure that a second or third attempt is successful.
	 * So instead of using intervals of 1 or 2 milliseconds, the intervals are set to at least more than 10 milliseconds.
	 *
	 * @param string $semaPostfix to distinguish more semaphores
	 * @param int $maxAttempts Maximum number of tries to get the semaphore. Maximum is 20.
	 * @return integer|null Semaphore (id) when created, NULL when failed.
	 * a semaphore.
	 *
	 */
	private function lo_getSema( $semaPostfix = 'default', $maxAttempts = 20 )
	{
		$semaId = null;
		if( $semaPostfix === 'default' && $this->defaultSemaId ) {
			$semaId = $this->defaultSemaId;
		} else {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			// During testing it became clear that the intervals between attempts must not be too short as this will lead
			// to too many failing attempts on MSSQL.
			$attempts = array( 20, 30, 50, 90, 260, 420, 740, 745, 425, 265, 95, 55, 35, 25, 205, 305, 505, 905, 505, 305 );

			if( $maxAttempts < 20 ) {
				$attempts = array_slice( $attempts, 0, $maxAttempts );
			}
			$bizSema = new BizSemaphore();
			$bizSema->setAttempts( $attempts );
			$bizSema->setLifeTime( 120 );
			$semaEntityId = 'license_'.$semaPostfix;
			$previousLogSqlState = BizSemaphore::suppressSqlLogging();
			$semaId = $bizSema->createSemaphore( $semaEntityId );
			BizSemaphore::restoreSqlLogging( $previousLogSqlState );

			if( !$semaId ) {
				$this->mWWLError = WWL_ERR_SET_SEMAPHORE;
			} elseif( $semaPostfix === 'default' ) {
				$this->defaultSemaId = $semaId;
			}
		}

		return $semaId;
	}

	/**
	 * Deletes the semaphore from the database.
	 *
	 * @param int $semaId to distinguish semaphores.
	 */
	private function lo_releaseSema( $semaId )
	{
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$previousLogSqlState = BizSemaphore::suppressSqlLogging();
		BizSemaphore::releaseSemaphore( $semaId );
		BizSemaphore::restoreSqlLogging( $previousLogSqlState );
		if( $semaId === $this->defaultSemaId ){
			$this->defaultSemaId = null;
		}
	}


	/**
	 * return the value of the given field from the database
	 *
	 * @param string field
	 * @return string value or false on failure
	 *
	 */
	private function lo_getFieldDB( $field ) {
		$dbo = $this->mDBDriver->tablename('config');
		$sql = "SELECT `value` FROM " . $dbo . " WHERE `name`='$field'";
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
		if ( !$sth )
		{
			$this->mWWLError = WWL_ERR_DB;
			return false;
		}
		$row = $this->mDBDriver->fetch($sth);
		if( !$row )
		{
			$this->mWWLError = WWL_ERR_DB_FIELDNOTEXIST;
			return false;
		}
		return $row[ 'value' ];
	}

	/**
	 * return the value of the given field from the filestore
	 *
	 * @param string field
	 * @return string value or false on failure
	 *
	 */
	private function lo_getFieldFS( $field ) 
	{
		if ( !$this->initDirectories() )
			return false;
			
		$filename = FSLICENSEDIR . "/" . $field;
		if ( !file_exists( $filename ))
		{
			$this->mWWLError = WWL_ERR_FILESTORE_FILE;
			return false;
		}
		$l = filesize( $filename );
		if ( $l == 0 )
			return '';
		$fh = @fopen( $filename, "r" );
		if ( !$fh )
		{
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'DEBUG', "Can't open $filename for read" );
			}
			$this->mWWLError = WWL_ERR_FILESTORE_FILE;
			return false;
		}
		$val = fread( $fh, $l );
		fclose( $fh );
		return $val;
	}
	
	/**
	 * Return the value of the given field.
	 *
	 * The value is retrieved from both the database and filestore, and these values should match. 
	 * If not, someone is trying to fool us, or someone restored backups that are different for FS and DB
	 *
	 * @param string $field
	 * @return bool|string value or false on failure
	 */
	public function getLicenseField( $field ) 
	{
		$this->mWWLError = 0;
		if( !$this->initDirectories() ) {
			return false;
		}
		ignore_user_abort();

		if( $this->defaultSemaId ) {
			$result = $this->getLicenseFieldWithSemaphoreInHand( $field );
		} else {
			$result = $this->getLicenseFieldWithoutSemaphoreInHand( $field );
		}

		return $result;
	}

	/**
	 * Return the value of the given field.
	 *
	 * The value is retrieved from both the database and filestore, and these values should match.
	 * To ensure that both read actions are done in a consistent way a semaphore is set around the read actions.
	 *
	 * @param string $field
	 * @return bool|string value or false on failure
	 */
	private function getLicenseFieldWithoutSemaphoreInHand( $field )
	{
		$semaId = $this->lo_getSema();
		if( !$semaId ) {
			return false;
		}

		$result = $this->getLicenseFieldWithSemaphoreInHand( $field );

		$this->lo_releaseSema( $semaId );

		return $result;
	}

	/**
	 * Return the value of the given field.
	 *
	 * The value is retrieved from both the database and filestore, and these values should match.
	 * To ensure that both read actions are done in a consistent way a semaphore is set around the read actions.
	 * This method can be called if the semaphore is already set and will be released by the caller.
	 *
	 * @param string $field
	 * @return bool|string value or false on failure
	 */
	private function getLicenseFieldWithSemaphoreInHand( $field )
	{
		$val = $this->lo_getFieldDB( $field );
		$result = $val;
		if( $val !== false ) {
			$val2 = $this->lo_getFieldFS( $field );
			if( $val2 === false ) {
				$result = false;
			} elseif( $val != $val2 ) {
				$this->mWWLError = WWL_ERR_FILESTORE_DB_MISMATCH;
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * save the value of the given field to the database
	 *
	 * @param string field
	 * @param string value
	 * @return boolean success
	 *
	 */
	private function lo_setFieldDB( $field, $value )
	{
		$dbo = $this->mDBDriver->tablename('config');
		$sql = "SELECT `id` FROM $dbo WHERE `name`='$field'";
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
		if ( !$sth )
		{
			$this->mWWLError = WWL_ERR_DB;
			return false;
		}
		$row = $this->mDBDriver->fetch($sth);
		if ( !$row || !isset( $row[ 'id' ] )) {
			$sql = "INSERT INTO $dbo (`name`,`value`) VALUES ('$field','" . $this->mDBDriver->toDBString($value) . "')";
			$sql = $this->mDBDriver->autoincrement($sql);
		} else {
			$sql = "UPDATE $dbo SET `value`='" . $this->mDBDriver->toDBString($value) . "' WHERE `id`=" . $row[ 'id' ];
		}
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
		if ( !$sth )
		{
			$this->mWWLError = WWL_ERR_DB;
			return false;
		}
		return true;
	}

	/**
	 * save the value of the given field to the filestore
	 *
	 * @param string field
	 * @param string value
	 * @return boolean success
	 *
	 */
	private function lo_setFieldFS( $field, $value )
	{
		$filename = FSLICENSEDIR . "/" . $field;
		$fh = @fopen( $filename, "w" );
		if ( !$fh )
		{
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'DEBUG', "Can't write to $filename" );
			}
			$this->mWWLError = WWL_ERR_FILESTORE_FILE;
			return false;
		}
		fwrite( $fh, $value );
		fclose( $fh );
		return true;
	}
	
	/**
	 * remove the field from the database
	 *
	 * @param string field
	 * @return boolean success
	 *
	 */
	private function lo_removeFieldDB( $field )
	{
		$dbo = $this->mDBDriver->tablename('config');
		$sql = "DELETE FROM $dbo WHERE `name`='$field'";
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
		if ( !$sth )
		{
			$this->mWWLError = WWL_ERR_DB;
			return false;
		}
		return true;
	}

	/**
	 * remove the field from the filestore
	 *
	 * @param string field
	 * @return boolean success
	 *
	 */
	private function lo_removeFieldFS( $field )
	{
		if ( !is_dir( FSLICENSEDIR ))
			return true;
		$filename = FSLICENSEDIR . "/" . $field;
		if ( !file_exists( $filename ))
			return true;
		$ret = unlink(  $filename );
		if ( $ret === false )
		{
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'DEBUG', "Can't delete $filename" );
			}
			$this->mWWLError = WWL_ERR_FILESTORE_FILE;
		}
		return $ret;
	}
	
	/**
	 * Save the value for the given field in both filestore and database
	 * Use a semaphore to make this an 'atomic' action: 
	 * the value in the filestore and database should always match!
	 *
	 * @param string field
	 * @param string value
	 * @return boolean success
	 *
	 */
	public function setLicenseField( $field, $val ) 
	{
		$this->mWWLError = 0;

		if ( !$this->initDirectories() )
			return false;

		ignore_user_abort();

		$semaId = $this->lo_getSema();
		if ( !$semaId )
			return false;
			
		$curval = $this->lo_getFieldFS( $field );
		if ( !$this->lo_setFieldFS( $field, $val ))
		{
			$this->lo_releaseSema( $semaId );
			return false;
		}
		if ( !$this->lo_setFieldDB( $field, $val ))
		{
			//restore old FS value
			$this->lo_setFieldFS( $field, $curval );
			$this->lo_releaseSema( $semaId );
			return false;
		}
		$this->lo_releaseSema( $semaId );
		return true;
	}

	/**
	 * Remove the given field from both filestore and database
	 * Use a semaphore to make this an 'atomic' action: 
	 * the information in the filestore and database should always match!
	 *
	 * @param string field
	 * @return boolean success
	 */
	public function removeLicenseField( $field ) 
	{
		$this->mWWLError = 0;

		ignore_user_abort();

		$semaId = $this->lo_getSema();
		if ( !$semaId )
			return false;
			
		if ( !$this->lo_removeFieldDB( $field ))
		{
			$this->lo_releaseSema( $semaId );
			return false;
		}
		if ( !$this->lo_removeFieldFS( $field ))
		{
			$this->lo_releaseSema( $semaId );
			return false;
		}
		$this->lo_releaseSema( $semaId );
		return true;
	}
	
	/**
	 * Returns the error that is set by one of the functions
	 *
	 * @return int error code 
	 */
	public function getErrorCode()
	{
		return $this->mWWLError;
	}

	/**
	 * Remove dangerous character when using the name in a filesystem or a database
	 *
	 * @param string $name
	 * @return string
	 */
	private function safeName( $name )
	{
		$sDangerousCharacters = '`~!@#$%^*\\|;:\'<>/?';
		$emptyString = str_repeat( '-', strlen($sDangerousCharacters ) );
		$name = strtr( $name, $sDangerousCharacters, $emptyString );
		return $name;
	}
	
	/**
	 * Return an array of productcodes (strings) that are currently installed.
	 * The product codes are stored in the "productcodes" field
	 *
	 * @return array
	 */
	public function getProductcodes()
	{
		$productcodes = $this->getLicenseField( "productcodes" );
		if ( !$productcodes )
			return Array();
		return explode( '|', $productcodes );
	}

	/**
	 * Return the license string for the given productcode
	 * The license string is part of the value(string) that is saved in the filestore and database,
	 * so "unpack" it first.
	 * The caller has to decrypt the string if necessary
 	 *
	 * @param integer $productcode
	 * @return string
	 */
	public function getLicense( $productcode ) {
		if ( !$productcode )
			return false;
		$productInfo = $this->getLicenseField( $productcode );
		if ( $productInfo === false )
			return false;
		$productname = '';
		$productserial = '';
		$productlicense = '';
		if ( !$this->mLicenseString->getProductInfo( $productInfo, $productname, $productserial, $productlicense ))
			return false;
		return $productlicense;
	}
	
	/**
	 * Return the serial number for the given productcode
	 * The serial number is part of the value(string) that is saved in the filestore and database,
	 * so "unpack" it first.
	 * The caller has to decrypt the string if necessary
 	 *
	 * @param string $productcode
	 * @return string
	 */
	public function getSerial( $productcode ) {
		if ( !$productcode )
			return false;
		$productInfo = $this->getLicenseField( $productcode );
		if ( $productInfo === false )
			return false;
		$productname = '';
		$productserial = '';
		$productlicense = '';
		if ( !$this->mLicenseString->getProductInfo( $productInfo, $productname, $productserial, $productlicense ))
			return false;
		return $this->mLicenseString->wwl_decrypt( $productserial );
	}

	/**
	 * Return the product name for the given productcode
	 * The product name is part of the value(string) that is saved in the filestore and database,
	 * so "unpack" it first.
 	 *
	 * @param string $productcode
	 * @return string
	 */
	public function getName( $productcode ) {
		if ( !$productcode )
			return false;
		$productInfo = $this->getLicenseField( $productcode );
		if ( $productInfo === false )
			return false;
		$productname = '';
		$productserial = '';
		$productlicense = '';
		if ( !$this->mLicenseString->getProductInfo( $productInfo, $productname, $productserial, $productlicense ))
			return false;
		return $productname;
	}


	/**
	 * Save the given license information in both the filestore and database
	 * First check whether the productcode is already known. 
	 * If not, add it to the "productcodes" (a field).
	 * Then build the productinfo (a string containing the productname, serial and license);
	 * Save this product info string to the filestore and database, using the productcode as a key (field)
	 * Use a semaphore to make all this an 'atomic action'.
 	 *
	 * @param string productcode
	 * @param string productname
	 * @param string serial
	 * @param string license
	 * @return boolean success
	 */
	public function setLicense( $productcode, $productname, $serial, $license ) 
	{
		$this->mWWLError = 0;

		$productcode = $this->safeName( $productcode );
		$productname = $this->safeName( $productname );

		ignore_user_abort();
		$semaPostfix = 'set';
		$semaId = $this->lo_getSema( $semaPostfix );
		if ( !$semaId )
			return false;

		//First, check whether the product is already known
		$bNeedToAddProduct = true;
		$productcodes = $this->getLicenseField( "productcodes" );
		if ( $productcodes )
		{
			$productcodesArr = explode( '|', $productcodes );
			foreach( $productcodesArr as $pc )
			{
				if ( $pc == $productcode )
				{
					$bNeedToAddProduct = false;
					break;
				}
			}
		}
		if ( $bNeedToAddProduct )
		{
			if ( $productcodes )
				$productcodes .= '|';
			$productcodes .= $productcode;
			
			if ( !$this->setLicenseField( "productcodes", $productcodes ))
			{
				$this->lo_releaseSema( $semaId );
				return false;
			}
		}
		
		if ( !$productname )
			$productname = $this->getName( $productcode );
		
		$enc_serial = $this->mLicenseString->wwl_encrypt( $serial );
		$productinfo = $this->mLicenseString->makeProductInfo( $productname, $enc_serial, $license );
		if ( !$this->setLicenseField( $productcode, $productinfo ))
		{
			$this->lo_releaseSema( $semaId );
			return false;
		}
		$this->lo_releaseSema( $semaId );
		return true;
	}
	
	/**
	 * Remove the license information for the given product code:
	 *   Remove the productcode field
	 *   Remove the productcode from the "productcodes" field
	 * If the productcode is empty, remove all license information.
 	 *
	 * @param string productcode
	 * @return boolean success
	 */
	public function removeLicense( $productcode = '' )
	{
		$this->mWWLError = 0;

		ignore_user_abort();
		$bRemoveAll = false;
		if ( $productcode )
		{
			$productcodes = $this->getLicenseField( "productcodes" );
			if ( $productcodes )
			{
				$productcodesArr = explode( '|', $productcodes );
				$found = false;
				foreach( $productcodesArr as $i => $pc )
				{
					if ( $pc == $productcode )
					{
						unset( $productcodesArr[ $i ] );
						$found = true;
						break;
					}
				}
				if ( $found )
				{
					$productcodes = implode( '|', $productcodesArr );
					if ( !$this->setLicenseField( "productcodes", $productcodes ))
						return false;

					//No more products left; even SCE Server is removed?
					//Then be sure to remove the 'local' field 
					//to avoid the 'Using an old license' problem when installing a new license later
					if ( !$productcodes )
						$bRemoveAll = true;
				}
			}

			if ( !$this->removeLicenseField( $productcode ))
				return false;
		}
		else //remove ALL
		{
			$bRemoveAll = true;
		}
		
		if ( $bRemoveAll )
		{
			if ( !$this->removeLicenseField( "key1" ))
				return false;
			if ( !$this->removeLicenseField( "key2" ))
				return false;
			if ( !$this->removeLicenseField( "key3" ))
				return false;
			if ( !$this->removeLicenseField( "local" ))
				return false;

			$productcodesArr = $this->getProductcodes();
			foreach( $productcodesArr as $pc )
			{
				if ( !$this->removeLicenseField( $pc ))
					return false;
			}
	
			if ( !$this->removeLicenseField( "productcodes" ))
				return false;
		}
		return true;
	}

	/**
	 * Generate a random string containing num characters
 	 *
	 * @param int number of characters
	 * @return string random string
	 */
	private function random( $num )
	{
	    $Use = "1234567890qwrtyuiopasdfghjklzxcvbnm";
	    $Pass = "";
	    for ($i=0; $i<$num; $i++)
	    {
	      $Pass .= $Use[ rand(0,strlen($Use)-1) ];
	    }
	    return $Pass;
	}

	/**
	 *	Save information of the last valid situation in the 'local' field:
	 *	- timestamp (last user logon date or installation date)
	 *	- maxobj id (max object id of smart_objects)
	 *	- creation date of maxobj id (creation date of last row in smart_objects)
	 *
	 *	Two exceptional situations are supported:
	 *	- summertime: the time is set back 1 hour
	 *	- in case the server is busy and process B started later than process A, but may set the local verifieddate sooner
	 *	be sure to update the date only if it is newer than the current date.
 	 *
	 * @param string logon date of a certain user (format YYYY-mm-ddTH:i:s)
	 * @return boolean success
	 */
	private function setLastLocalVerifiedDate( $logonTime )
	{
		$now = strtotime( $logonTime );
		if ( $now === false ) {
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'DEBUG', 'setLastLocalVerifiedDate: error converting $logonTime.' );
			}
			$this->mWWLError = WWL_ERR_TIME_CONVERSION;
			return false;
		}
		$nowstr = $this->mLicenseString->unix2WWTimeStr( $now );
		$created = 0;
		$maxid = $this->lo_getMaxObj( $created );
		if ( !$maxid )
			$created = '';
		//Add some random text for confusion...
		$nowstr = $this->random(4) . '|' . $nowstr. '|' . dechex( $maxid ) . '|' . $created . '|' . $this->random(4);
		$now_enc = $this->mLicenseString->wwl_encrypt( $nowstr );
		if ( !$now_enc )
		{
			$this->mWWLError = $this->mLicenseString->getError();
			return false;
		}
			
		ignore_user_abort();

		$now_enc = $now . '-' . $now_enc; //Handy for comparing timestamps: put the current time in front as a prefix
		$semaPostfix = 'set';
		$semaId = $this->lo_getSema( $semaPostfix );
		if ( !$semaId )
			return false;

		//Check whether the current value is less;
		//If so, update the DB and FS
		//If not, another process was quicker, or the summertime has ended
		$curtime = $this->lo_getFieldDB( 'local' );
		$bSet = false;
		if ( $curtime == false )
		{
			//Very first time?
			if ( $this->mWWLError == WWL_ERR_DB_FIELDNOTEXIST )
			{
				$bSet = true;
			}
		}
		else
		{
			$bSet = $curtime < $now;
		}
		if ( $bSet )
		{
			if ( !$this->setLicenseField( 'local', $now_enc ))
			{
				$this->lo_releaseSema( $semaId );
				return false;
			}
		}
		$this->lo_releaseSema( $semaId );
		return true;
	}

	/**
	 *	Return information of the last valid situation in the 'local' field:
	 * last user logon date or installation date
	 * maxobj max obj id of smart_objects
	 * creation date of last row in smart_objects
	 * 
	 * @param string last user logon date or installation date
	 * @param string maxobj max obj id of smart_objects
	 * @param string creation date of last row in smart_objects
	 * @return boolean success
	 */
	private function getLastLocalVerifiedDate( &$timestamp, &$maxobj, &$created )
	{
		$d = $this->getLicenseField( 'local' );
		if ( $d === false )
			return false;
			
		$minpos = strpos( $d, '-' );
		if ( $minpos === false )
			return false;
		$d = substr( $d, $minpos+1 );
			
		$d = $this->mLicenseString->wwl_decrypt( $d );
		if ( $d === false )
		{
			$this->mWWLError = $this->mLicenseString->getError();
			return false;
		}
		$arr = explode( '|', $d );
		if ( count( $arr ) != 5 )
			return false;
		$timestamp = $this->mLicenseString->WWTimeStr2Unix($arr[1]);
		$maxobj = hexdec($arr[2]);
		$created = $this->mLicenseString->WWTimeStr2Unix($arr[3]);
		return true;
	}
	
	/**
	 *	Return true if the given appproductcode and serial belong to a concurrent user license
	 * @param string appproductcode
	 * @param string appserial
	 * @return boolean success
	 */
	private function IsConcurrent( $appproductcode, $appserial )
	{
		//Non-concurrent clients should NOT send the appserial and appproductcode
		//Only concurrent clients should sent both the appserial and appproductcode
		return $appserial && $appproductcode;
	}
	

	/**
	 * This method is called in case the maximum number of connections is reached.
	 * However, in case the user that want to log on, is already logged on to some application, he may still logon.
	 * Return 1 if the user is already logged on to some application, and thus may logon.
	 * In case the user is already logged on to the requested application, that ticket will be removed.
	 * (A user may only logon once to a certain application.)
	 * Return 0 if the user is not logged on yet; the user may not log on.
	 * Return false on error
	 * 
	 * @param string $logonUser user that logs on
	 * @param string $logonApp application to logon on to
	 * @param string $productcode fill in if it's allowed to logon as 1 user with multiple apps on 1 product code (like Content Station & WebEditor)
	 * @return integer (0 or 1) or false
	 */
	private function userCanStillLogOn( $logonUser, $logonApp, $productcode=null )
	{
		$db = $this->mDBDriver->tablename("tickets");
		$sql = "SELECT `id`, `appname` FROM $db WHERE `usr` = '$logonUser'";
		
		// For some products (like Content Station) it's allowed to loo
		if( $productcode ) {
			$sql .= " AND `appproductcode` = '$productcode'";
		}

		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
		if (!$sth) 
			return false;

		$userAlreadyLoggedOn = false;
		while(($row = $this->mDBDriver->fetch($sth))) {

			//Although you may return 1 here already, continue to find an application match (to remove that ticket)
			$userAlreadyLoggedOn = true;

			$appname = $row[ 'appname' ];
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'DEBUG', "userCanStillLogOn: $logonApp == $appname" );
			}
			if ( $logonApp == $appname )
			{
				// We allow InDesign Server to logon as many times as needed.
				// Example: when user stores layout, it could trigger background IDS jobs to generate PDFs.
				// There could be many jobs running at the same time for that single user. Those jobs
				// can run at the same IDS machine, or even dispatched over different machines.
				// We do not want to clear those ticket or else one job could break other running job half way!
				if( stripos( $appname, 'indesign server' ) === false && // no IDS?
					!BizSession::isSmartMover( $appname ) ) { // no Smart Mover? (It operates under "Mover" name followed by some process id.)
					$id = $row[ 'id' ];
					$sql = "DELETE FROM $db WHERE `id`=$id";
					if( $this->mLicLog ) {
						LogHandler::Log('license', 'DEBUG', 'userCanStillLogOn: already logged on to the requested application. ' );
					}
					$sth2 = $this->mDBDriver->query($sql, array(), null, $this->mLicLog);
					if ( !$sth2 )
						return false;
					return 1;
				}
			}
		}
		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', 'userCanStillLogOn: return ' . ($userAlreadyLoggedOn ? '1' : '0') );
		}
		return ( $userAlreadyLoggedOn ) ? 1 : 0;
	}

	/**
	 *	Return the number of concurrent users for the given productcode. If the productcode is empty, return the number
	 * of connections (all applications).
	 *
	 *	If a user is logged on twice to the same application, the oldest ticket will be deleted.
	 *
	 * Exception example:
	 *    ID/IC have the same appproductcode, however, the appname will be different.
	 *    A user that is logged on to both applications is counted as 1, without one of the tickets being deleted.
	 *
	 *	If a user is about to log on to certain application and an old ticket for that user and application is still
	 * present in the tickets table, that ticket will be deleted, and thus doesn't count.
	 *
	 * @param string $appProductCode
	 * @param string $logonUser
	 * @param string $logonAppName
	 * @param string $logonAppVersion [Since 10.1.6/10.3.0]
	 * @return int number of users/connections
	 */
	private function getNumConnections( $appProductCode='', $logonUser='', $logonAppName='', $logonAppVersion='' )
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

		// Ignore the footprint of the installation. Will be removed as soon as 'purgeTickets' is called.
		$where = "`usr` <> ? AND `appname` <> ? ";
		$params = array( strval( $this->mInstallTicketID ), strval( $this->mInstallTicketID ) );
		if( $appProductCode ) {
			$where .= "AND `appproductcode` = ? ";
			$params[] = strval( $appProductCode );
		}

		// Compose SQL fragment such as: "ORDER BY `usr`, `appname`, `expire` DESC"
		$orderBy = array( 'usr' => false );
		if( $appProductCode ) {
			$orderBy['appproductcode'] = false;
		} else {
			$orderBy['appname'] = false;
		}
		$orderBy['expire'] = false;

		$fields = array( 'usr', 'appname', 'appversion', 'expire', 'id', 'appserial', 'appproductcode' );
		$rows = DBBase::listRows( 'tickets', '', '', $where, $fields, $params, $orderBy,
			null, null, null, $this->mLicLog );

		// Multiple connections of 1 user count as 1 as long as the application name/productcode differs
		$count = 0;
		$prevUser = '';
		$prevAppName = '';
		$prevAppVersion = '';
		if( $rows ) foreach( $rows as $row ) {
			$iterUser = $row[ 'usr' ];
			$iterAppName = $row[ 'appname' ];
			$iterAppVersion = $row[ 'appversion' ];

			$deleteTicket = false;
			$deleteReason = '';
			if ( $logonUser && ($logonUser == $iterUser) &&
				$logonAppName && $this->isSameApplication( $logonAppName, $logonAppVersion, $iterAppName, $iterAppVersion ) ) {
				// In case the user wants to logon for a certain application (except IDS), and an old ticket is still
				// present for that user and application, remove that old ticket. This way it doesn't count.
				$deleteTicket = true;
				$deleteReason = "Deleting old ticket for the user + application that logs on now.";
			}

			if( !$deleteTicket ) {
				// Only update the counter (n) in case the user differs from the previous user
				if( $iterUser != $prevUser ) {
					$count++;
					$prevUser = $iterUser;
					$prevAppName = '';
					$prevAppVersion = '';
				}
	
				if( $this->mLicLog ) {
					$productCode = $row[ 'appproductcode' ];
					LogHandler::Log('license', 'DEBUG',  "Testing user {$iterUser} ({$count}), ".
						"app name {$iterAppName}, app version {$iterAppVersion} (code $productCode)..." );
				}
	
				if( !$this->isSameApplication( $prevAppName, $prevAppVersion, $iterAppName, $iterAppVersion ) ) {
					$prevAppName = $iterAppName;
					$prevAppVersion = $iterAppVersion;
				} else {
					// In case the user is logged on twice (or more) for the same application, remove tickets and remain only
					// the first (for this application). Because the row are ordered by `expire` DESC, the first one is the
					// most recent one, and the older ones can be removed.
					$deleteTicket = true;
					$deleteReason = "Deleting old ticket for the same application (logged on twice or more to the same application).";
				}
			}
			
			if( $deleteTicket ) {
				// We allow InDesign Server to logon as many times as needed.
				// Example: when user stores layout, it could trigger background IDS jobs to generate PDFs.
				// There could be many jobs running at the same time for that single user. Those jobs
				// can run at the same IDS machine, or even dispatched over different machines.
				// We do not want to clear those ticket or else one job could break other running job half way!
				if( stripos( $iterAppName, 'indesign server' ) === false && // no IDS?
					!BizSession::isSmartMover( $iterAppName ) ) { // no Smart Mover? (It operates under "Mover" name followed by some process id.)
					if( $this->mLicLog ) {
						LogHandler::Log('license', 'INFO', $deleteReason );
					}
					$where = '`id` = ?';
					$params = array( intval( $row[ 'id' ] ) );
					if( !DBBase::deleteRows( 'tickets', $where, $params, $this->mLicLog ) ) {
						return false;
					}
				}
			}
		}
		return $count;
	}

	/**
	 * Tells whether two client applications are equal.
	 *
	 * CS9 is technically a totally different production than CS10. Even the license of CS9 differs from CS10.
	 * CS10 is not yet a full replacement of CS9 and so they may be used side by side during transition phases.
	 * All this makes that a CS9 seat should NOT be cleared automatically when that user takes a CS10 seat.
	 * In other words a CS9 (or before) client must be seen as a different than a CS10 (or later) client.
	 *
	 * @since 10.1.6/10.3.0
	 * @param string $appNameA
	 * @param string $appVersionA The client application version of $appNameA.
	 * @param string $appNameB
	 * @param string $appVersionB The client application version of $appNameB.
	 * @return bool
	 */
	private function isSameApplication( $appNameA, $appVersionA, $appNameB, $appVersionB )
	{
		$same = false;
		if( $appNameA == $appNameB ) {
			if( $appNameA == 'Content Station' ) {
				$csMajorVersionA = intval( BizSession::formatClientVersion( $appVersionA, 1 ) );
				$csMajorVersionB = intval( BizSession::formatClientVersion( $appVersionB, 1 ) );
				$same = ($csMajorVersionA <= 9) === ($csMajorVersionB <= 9);
			} else {
				$same = true;
			}
		}
		return $same;
	}

	/**
	 *	Delete old tickets one by one, that are older than "mKeepAliveMinutes" minutes.
	 *  After every deletion, check whether the number of concurrent users (or connections) 
	 *  has be decreased to 'newNewConnections'. If so, return. If not, continue deleting.
	 *	If the productcode is specified, only delete ticket for that application/productcode.
	 * 
	 * @param int desired number of connection/users
	 * @param string product code
	 * @return int new number of users/connections, or false on error
	 */
	private function cleanupTickets( $newNumConnections, $appproductcode='' )
	{
		$thistime = $this->time();
		if ( $thistime === false )
			return false;

		//Never delete the tickets that have logged on in the last x ('keep alive') minutes
		$logon = date("Y-m-d\\TH:i:s", $thistime - 60 * $this->mKeepAliveMinutes);

		$db = $this->mDBDriver->tablename("tickets");

		$sql = "SELECT `id` from $db WHERE `logon` < '$logon'";
		if ( $appproductcode )
			$sql .= " AND `appproductcode`='$appproductcode'";

		//Delete the tickets in the order of expiration: First delete the tickets that expire soon
		//In other words: the expiration occurs a little bit earlier now by deleting them...
		//Note that this can be a problem when only webapps expire after 1 hour being idle, 
		//and other applications expire after 1 day being idle....
		//$sql .= " ORDER BY `expire` ASC";

		//Delete the tickets in the order of logon: First delete the tickets that have been logged for the longest time
		$sql .= " ORDER BY `logon` ASC";
		
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog);
		if (!$sth) {
			return false;
		}

		while(( $row = $this->mDBDriver->fetch($sth) )) {
			$id = $row[ 'id' ];
			$sql = "DELETE FROM $db WHERE `id`=$id";
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'INFO', 'Logged on more than [' . $this->mKeepAliveMinutes . '] minutes ago.' );
			}
			$sth2 = $this->mDBDriver->query($sql, array(), null, $this->mLicLog);
			if ( !$sth2 )
				return false;
			$curNumConnections = $this->getNumConnections( $appproductcode );
			if ( $curNumConnections <= $newNumConnections )
				return $curNumConnections;
		}
		return false;
	}

	/**
	 * Check whether someone has been messing with the system time.
	 * Inspect the creation dates of objects in the smart_objects table.
	 * Ignore objects that have been created while the license was valid: 
	 * only inspect objects that have been created after an error situation started (or after the previous valid logon time). 
	 * Objects that have been created after the error situation (or after the previous logon time) have an id higher than 'startobjid'.
	 * If the creation date of a NEWER object is before the creation date at the error moment, the client has set the time to the past...
	 * Note to allow a difference for about 2 hours for summertime. However, a "summertime gap" may only occur once a year...
	 * 
	 * @param int $starttime The creation date of the last smart object at the moment of an error situation (e.g. license expired)
	 * @param int $startobjid start checking objects with an id higher than this
	 * @param int $thistime
	 * @return boolean success (no problem)
	 */
	private function checkCreationDates( $starttime, $startobjid, $thistime )
	{
		$this->mWWLError = 0;
		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', "checkCreationDates: start=" . date( 'H:i:s', $starttime ) );
		}
		$summertimeMargin = 60 * 60 * 2;  //summer time or winter time: a difference of about 2 hours is allowed.
		$starttime -= $summertimeMargin; 

		//TEST 1:
		$doTest = ( $this->mTestFlags & WWL_TEST_SYSTIME_ERRORTIME_FLAG ) > 0;
		if ( $doTest )
		{
			if ( $thistime < $starttime )
			{
				$this->mWWLError = WWL_ERR_TIME;
				return false;
			}
		}

		//TEST 2 (and last test in this method):
		$doTest = ( $this->mTestFlags & WWL_TEST_SYSTIME_OBJECTS_FLAG ) > 0;
		if ( !$doTest )
		{
			return true;
		}
			
		$dbo = $this->mDBDriver->tablename('objects');

		$sql = "SELECT `id`, `created` FROM $dbo WHERE `id` > $startobjid ORDER BY `id`";
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
		if ( !$sth )
		{
			$this->mWWLError = WWL_ERR_DB;
			return false;
		}
			
		$summertimegapfound = Array();
		$prevtime = 0;
		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', 'checkCreationDates: start=' . date( 'H:i:s', $starttime ));
		}
		while(( $row = $this->mDBDriver->fetch($sth))) {
			$t = strtotime( $row['created'] );
			if ( $t === false ) {
				//This error probably never occurs because our own code builds the 'created' field, 
				//according to the format YYYY-mm-ddThh:mm:ss.
				$this->mWWLError = WWL_ERR_TIME_DB1;
				return false;
			}
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'DEBUG', 'checkCreationDates: created='.$t.' prevtime='.$prevtime.' starttime='.$starttime.' summertimeMargin='.$summertimeMargin );
			}

			//All next entries should be newer than ($starttime - summertimemargin).
			if ( $t < $starttime )
			{
				$this->mWWLError = WWL_ERR_TIME_DB1;
				return false;
			}

			//Second or further entries found?
			if ( $prevtime )
			{
				//Compare entry n to entry n-1
				//Entry n should be "at most the summertime margin older or newer" than entry n-1
				if ( $t - $prevtime < - $summertimeMargin )
				{
					if( $this->mLicLog ) {
						LogHandler::Log('license', 'ERROR', 'Time conflict near ' . $row['created'] );
					}
					$this->mWWLError = WWL_ERR_TIME_DB2;
					return false;
				}
	
				//Entry n may be at most the "summertime margin/1 hour" less than entry n-1, and only once per year
				if ( $t - $prevtime < 0 )
				{
					$y = date( 'Y', $t );

					//A small gap in time found? It's probably the summertime gap
					if ($prevtime - $t < $summertimeMargin )
					{
						//A summer time gap twice a year is no good.
						if ( isset( $summertimegapfound[ $y ] ) && $summertimegapfound[ $y ] )
						{
							if( $this->mLicLog ) {
								LogHandler::Log('license', 'ERROR', 'Time conflict near ' . $row['created'] );
							}
							$this->mWWLError = WWL_ERR_TIME_DB3;
							return false;
						}
						else 
						{
							$summertimegapfound[ $y ] = true;
						}
					}
					else
					{
						if( $this->mLicLog ) {
							LogHandler::Log('license', 'ERROR', 'Time conflict near ' . $row['created'] );
						}
						$this->mWWLError = WWL_ERR_TIME_DB4;
						return false;
					}
				}
			}
				
			$prevtime = $t;
		}

		return true;
	}
	
	/**
	 * Check whether someone has logged on AFTER the given timestamp by inspecting
	 * both the tickets table (logon field) AND the users table (lastlogondate field).
	 * If so, someone tries to fool us by only copying old license information over a
	 * newer database. This may also happen if someone only recovers certain tables
	 * from a backup; one should recover the complete database, not only certain tables.
	 * 
	 * @param string timestamp 
	 * @return boolean success (no problem)
	 */
	private function checkUsingOldLicense( $timestamp )
	{
		$db = $this->mDBDriver->tablename("tickets");
		$t = date("Y-m-d\\TH:i:s", $timestamp );

		$sql = "SELECT `id` FROM $db WHERE `logon` > '$t'";

		//Ignore the installation footprint, only check the logon of a real user
		$user = $this->mInstallTicketID;
		$appname = $this->mInstallTicketID;
		$sql .= " AND `usr` != '$user' AND `appname` != '$appname'";

		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog );
		if ( $sth ) {
			$row = $this->mDBDriver->fetch($sth);
			if( $row ) {
				$id = $row['id'];
				if ( $id ) {
					if( $this->mLicLog ) {
						$errorMessage = 'Ticket id ['.$id.'] too old';
						LogHandler::Log('license', 'ERROR', $errorMessage);
					}
					return false;
				}
			}
		}

		$db = $this->mDBDriver->tablename("users");
		$sql = "SELECT `id` FROM $db WHERE `lastlogondate` > '$t'";
		$sth = $this->mDBDriver->query($sql, array(), null, $this->mLicLog ); //false: do not write in the log file
		if ( $sth ) {
			$row = $this->mDBDriver->fetch($sth);
			if( $row ) {
				$id = $row['id'];
				if ( $id ) {
					if( $this->mLicLog ) {
						$errorMessage = 'User id ['.$id.'] logged on later';
						LogHandler::Log('license', 'ERROR', $errorMessage);
					}
					return false;
				}
			}
		}

		return true;
	}
	
	/**
	 * Remove tickets from the tickets table 
	 * If productcode and/or appserial are not empty: use these fields to filter.
	 * 
	 * @param string productcode
	 * @param string appserial
	 * @return boolean success
	 */
	private function logOffAllUsers( $productcode='', $appserial='' )
	{
		$db = $this->mDBDriver->tablename("tickets");
		$sql = "DELETE FROM $db";
		$where = '';
		if ( $productcode )
			$where .= " WHERE `appproductcode`='$productcode'";
		if ( $appserial )
		{
			if ( !$where ) {
				$where .= " WHERE";
			} else {
				$where .= " AND";
			}
			$where .= " `appserial`='$appserial'";
		}
		$sth = $this->mDBDriver->query($sql.$where, array(), null, $this->mLicLog);
		return $sth;
	}
	
	private function handleError( $code, &$errorMessage, $str='', $logoff=false, $logoffProdcode='', $logoffAppserial='' )
	{
		$status = '';
		$color = '';
		$flags = '';
		//Convert code into a string (containing a small explanation, optionally translated)
		$this->getLicenseStatusInfo( $code, $color, $status, $flags );

		$errorMessage = $status;

		//optional detail message present?
		if ( $str ) {
			$errorMessage .= ' ' . $str;
		}

		if( $this->mLicLog ) {
			LogHandler::Log('license', 'ERROR', 'Error message: '.$errorMessage);
		}
		if ( $logoff && ( $this->mWWLError != WWL_ERR_SET_SEMAPHORE ) ) { // Sometimes a check fails because a semaphore
			// could not be set. Such a technical issue should not result in a log off of all users.
			$this->logOffAllUsers( $logoffProdcode, $logoffAppserial );
		}
		return $code;
	}
	
	/**
	 * Get the license status for the given productcode and serial
	 * Extra information is returned via the info array
	 * 	curusage: the number of concurrent users/connections at this moment
	 * 	maxusage: the maximum number of concurrent users/connections allowed according to the license
	 *  usageLimitReached: true if the maxusage limit has been reached
	 *  expires: if set, the expiration date
	 *  renew: if set, the date of renewal
	 * In case InDesign Server is the client application the license status is checked randomly. To know when a check is
	 * done a log statement is added. This registers the time needed to do the check.
	 * 
	 * @param string $productcode
	 * @param string $appserial
	 * @param array $info
	 * @param string $errorMessage
	 * @param string $logonTime Optional.
	 * @param string $logonUser Optional.
	 * @param string $logonApp  Optional.
	 * @param string $logonAppVersion Optional. [Since 10.1.6/10.3.0]
	 * @return string license status, see codes at the start of this module
	 */
	public function getLicenseStatus( $productcode, $appserial, &$info, &$errorMessage, $logonTime = '',
	                                  $logonUser='', $logonApp='', $logonAppVersion='' )
	{
		$semaId = $this->lo_getSema();
		$status = false;
		if( $semaId ){
			$startTime = microtime( true );
			$status = $this->getLicenseStatusNoTiming( $productcode, $appserial, $info, $errorMessage, $logonTime,
				$logonUser, $logonApp, $logonAppVersion );
			$this->lo_releaseSema( $semaId ); // Note that the default semaphore can already be released by a call to lo_releaseSema() in between.
			$endTime = microtime( true );
			LogHandler::Log('license', 'DEBUG', sprintf( 'Execution time for detecting the license status: %.4f seconds.', $endTime - $startTime ) );
		}

		return $status;
	}

	/**
	 * @see getLicenseStatus()
	 * @since 10.1.3
	 *
	 * @param string $productcode
	 * @param string $appserial
	 * @param array $info
	 * @param string $errorMessage
	 * @param string $logonTime
	 * @param string $logonUser
	 * @param string $logonApp
	 * @param string $logonAppVersion [Since 10.1.6/10.3.0]
	 * @return string
	 */
	private function getLicenseStatusNoTiming( $productcode, $appserial, &$info, &$errorMessage, $logonTime = '',
	                                           $logonUser='', $logonApp='', $logonAppVersion='' )
	{
		$info = Array( 'curusage' => -1, 
						'maxusage' => -1,
						'usageLimitReached' => false,
						'expires' => -1,
						'renew' => -1 );

		$errorMessage = '';
		$warningMessage = '';

		//Obtained via getSerial() while no license is installed?
		if ( $appserial === false )
		{
			$mainErrorCode = WW_LICENSE_ERR_INVALID_DATA;

			switch( $this->mWWLError )
			{
				case WWL_ERR_DB_FIELDNOTEXIST:
					//Very first time and no database field found yet?
					$mainErrorCode = WW_LICENSE_ERR_NOLICENSE6;
					break;
				
				case WWL_ERR_DB:
					//Also show the database error text in the error message
					$errorMessage = BizResources::localize('ERR_DATABASE' );
					break;

				//Semaphore directory not writable at once?
				//case WWL_ERR_FILESTORE_SYSDIR:
				//default:
			}

			return $this->handleError(	$mainErrorCode, 
										$errorMessage,
										'(S' . $this->mWWLError . ')',
										true );  //logoff all users
		}

		//Non-concurrent clients should NOT send the appserial
		//Only concurrent clients should sent the appserial
		if ( !$this->IsConcurrent( $productcode, $appserial ) )
			return WW_LICENSE_OK;

		//Generic test for Enterprise Server: the 'hardware ID' should match the 'hardware ID'(key1) in the license
		$key1db = $this->getLicenseField( "key1" );
//		print "key1db=$key1db";
		if ( $key1db === false )
		{
			return $this->handleError(	WW_LICENSE_ERR_NOLICENSE1, 
										$errorMessage,
										'(S' . $this->mWWLError . ')',
										true );  //logoff all users
		}

		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', "getLicenseStatus: key1db = $key1db");
		}
		$key1db = $this->mLicenseString->wwl_decrypt( $key1db );
		if ( $key1db === FALSE  )
		{
			return $this->handleError(	WW_LICENSE_ERR_ENCRYPT, 
										$errorMessage,
										$this->mLicenseString->getError()
										 );
		}
		
		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', "getLicenseStatus: key1db = $key1db");
		}
		$key1src = $this->getKeySource( 1 );
		if ( $key1src === false )
		{
			return $this->handleError(	WW_LICENSE_ERR_SYSTEM, 
										$errorMessage,
										'(S' . $this->mWWLError . ').' );
		}
		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', "getLicenseStatus: key1src = $key1src");
		}
		
		$productcode = $this->safeName( $productcode );

		//Check whether this concurrent productcode is already known (registered on Enterprise Server)
		$productcodes = $this->getLicenseField( "productcodes" );
		if ( $productcodes === false || !$productcodes )
		{
			if ( ( $this->mWWLError == WWL_ERR_DB_FIELDNOTEXIST ) ||  //The very first time, the field will not exist yet
				 ( ( $this->mWWLError == 0 ) && !$productcodes ) )		//When all licenses have been removed, the field exists and is empty
			{
				return $this->handleError(	WW_LICENSE_ERR_NOLICENSE3, 
											$errorMessage,
											'(S' . $this->mWWLError . ').',
											true ); //logoff all users
			}
			else if ( $this->mWWLError == WWL_ERR_DB ) //Also show the database error text in the error message
			{
				$str = BizResources::localize('ERR_DATABASE' );
				$str .= ' (S' . $this->mWWLError . ').';
				return $this->handleError(	WW_LICENSE_ERR_INVALID_DATA, 
											$errorMessage,
											$str,
											true ); //logoff all users
			}
			else
			{
				return $this->handleError(	WW_LICENSE_ERR_INVALID_DATA, 
											$errorMessage,
											'(S' . $this->mWWLError . ').',
											true ); //logoff all users
			}
		}
		$knownConcurrentProduct = false;
		if ( $productcodes )
		{
			$productcodesArr = explode( '|', $productcodes );
			foreach( $productcodesArr as $pc )
			{
				if ( $pc == $productcode )
				{
					$knownConcurrentProduct = true;
					break;
				}
			}
		}
		if ( !$knownConcurrentProduct )
		{
			return $this->handleError(	WW_LICENSE_ERR_UNKNOWNPRODUCT, 
										$errorMessage,
										'', true, $productcode, $appserial );
		}

		$productinfo = $this->getLicenseField( $productcode );
		if ( $productinfo === false )
		{
			return $this->handleError(	WW_LICENSE_ERR_NOLICENSE4, 
										$errorMessage,
										'(S' . $this->mWWLError . ').',
										true, $productcode, $appserial );
		}
		$productname = '';
		$serial = '';
		$license = '';
		if ( !$this->mLicenseString->getProductInfo( $productinfo, $productname, $serial, $license ))
		{
			return $this->handleError(	WW_LICENSE_ERR_NOLICENSE5, 
										$errorMessage,
										'(S' . $this->mWWLError . ').',
										true, $productcode, $appserial );
		}
		$serial = $this->mLicenseString->wwl_decrypt( $serial );
		if ( !$serial )
		{
			return $this->handleError(	WW_LICENSE_ERR_SYSTEM, 
										$errorMessage,
										'(' . $this->mLicenseString->getError() . ')' );
		}

		if ( !$license )
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_NOLICENSE2, 
										$errorMessage,
										'', true, $productcode, $appserial );
		}
		$arr = $this->mLicenseString->getLicenseInfo( $license );
		if ( $arr === false )
		{
			return $this->handleError(	WW_LICENSE_ERR_ENCRYPT, 
										$errorMessage );
		}
		
		$serialchecksum = $arr[ 'serialchecksum' ];
		$licproductcode = $arr[ 'productcode' ];
		$maxusage = $arr[ 'maxusage' ];
		$key1 = $arr[ 'key1' ];
		$starttime = $arr[ 'starttime' ];
		if ( $starttime )
			$starttime = $this->mLicenseString->WWTimeStr2Unix( $starttime );
		$expiretime = $arr[ 'expiretime' ];
		if ( $expiretime )
			$expiretime = $this->mLicenseString->WWTimeStr2Unix( $expiretime );
		$info[ 'expires' ] = $expiretime;
		$renewtime = $arr[ 'renewtime' ];
		if ( $renewtime )
			$renewtime = $this->mLicenseString->WWTimeStr2Unix( $renewtime );
		$info[ 'renew' ] = $renewtime;
		$expiremaxobj = $arr[ 'expiremaxobj' ];
		$errorstart = $arr[ 'errorstart' ];
		if ( $errorstart )
			$errorstart = $this->mLicenseString->WWTimeStr2Unix( $errorstart );
		$errormaxobj = $arr[ 'errormaxobj' ];
		
		//AAA 2007-7
		//Obtain the testflags from the license if possible
		if ( isset( $arr[ 'testflags' ] ))
			$this->mTestFlags = $arr[ 'testflags' ];
		if( $this->mLicLog ) {
			$thelog  = "- license=$license\r\n";
			$thelog .= "- serialchecksum=$serialchecksum\r\n";
			$thelog .= "- productcode=$licproductcode\r\n";
			$thelog .= "- maxusage=$maxusage\r\n";
			$thelog .= "- key1=$key1\r\n";
			$thelog .= "- key1src=$key1src\r\n";
			if ( $starttime )
				$thelog .= "- starttime=$starttime (" . date( "Y-m-d H:i:s", intval($starttime) ) . ")\r\n";
			if ( $expiretime )
				$thelog .= "- expiretime=$expiretime (" . date( "Y-m-d H:i:s", intval($expiretime) ) . ")\r\n";
			if ( $renewtime )
				$thelog .= "- renewtime=$renewtime (" . date( "Y-m-d H:i:s", intval($renewtime) ) . ")\r\n";
			$thelog .= "- expiremaxobj=$expiremaxobj\r\n";
			$thelog .= "- errorstart=$errorstart\r\n";
			$thelog .= "- testflags=" . $this->mTestFlags . "\r\n";
			LogHandler::Log('license', 'DEBUG', "getLicenseStatus: \r\n".$thelog );
		}

		$tmpErrorCode = 0;
		$mismatchFlags = 0;
	
		//Configuration changed?
		//E.g. database or filestore recreated?
		$doTest = ( $this->mTestFlags & WWL_TEST_DBFS_COPY_FLAG ) > 0;
		if ( $doTest && ($key1src != $key1db ))
		{
			//$key1 = "$dbstamp|$fstamp";
			$keyssrc = explode( '|', $key1src );
			$keysdb = explode( '|', $key1db );
			
			//DB stamp
			$dbMismatch = $keyssrc[0] != $keysdb[0];
			if ( $dbMismatch )
				$mismatchFlags += 1; //Switch to demo mode: set/update expire date below.

			//Filestore stamp
			$fsMismatch = $keyssrc[1] != $keysdb[1];
			if ( $fsMismatch )
				$mismatchFlags += 2; //Switch to demo mode: set/update expire date below.
			
			$tmpErrorCode = WWL_ERR_KEY1_MISMATCH1;
		}
		
		// AAA 2007-7
		// Let clients logon that have a different 'appserial' than the serial on the Enterprise Server (for a certain product)
		// Scenario: an InDesign client C1 with serial A
		//	 SCE Server S1 with serial A
		//   another SCE Server (S2) with serial B
		//   ID client C1 with serial A should also be allowed to logon to SCE Server S2, even if SCE Server S2 has another serial (B).
		$clientSerialShouldMatchServerSerial = false;
		if ( $clientSerialShouldMatchServerSerial && ($serial != $appserial))
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_SERIAL, 
										$errorMessage, 
										'', true, $productcode, $appserial );
		}

		if ( $serialchecksum != dechex(crc32($serial)) )
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_SERIAL2, 
										$errorMessage, 
										'', true, $productcode, $appserial );
		}

		if ( $licproductcode != $productcode )
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_PRODKEY, 
										$errorMessage, 
										'', true, $productcode, $appserial );
		}

		//License for another installation?
		$doTest = ( $this->mTestFlags & WWL_TEST_DBFS_COPY_FLAG ) > 0;
		if ( $doTest && ($key1 != $key1src ))
		{
			$tmpErrorCode = WWL_ERR_KEY1_MISMATCH2;
			$mismatchFlags += 4;  //Switch to demo mode: set/update expire date below.
		}

		$thistime = $this->time();
		if ( $thistime === false )
		{
			return $this->handleError(	WW_LICENSE_ERR_SYSTIME, 
										$errorMessage, 
										'(S' . $this->getErrorCode() . ')',
										true, $productcode, $appserial );
		}

		if ( !defined( 'IGNORE_DS_TIMEDIFF' )) 
		{
			//Time difference between AS and DS?
			$diffMinutes = $this->ASDSTimeDiff( $thistime );
			if ( $diffMinutes )
			{
				if ( $diffMinutes >= 15 ) {
					return $this->handleError(	WW_LICENSE_ERR_SYSTIME, 
												$errorMessage, 
												'(S' . WWL_ERR_ASDS_TIMEDIFF . ')',
												true, $productcode, $appserial );
				}
				else
					$warningMessage = BizResources::localize("LIC_ADMIN_TIME_SYNCH");
			}
		}

		if ( $starttime && ( $thistime < $starttime ))
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_STARTTIME, 
										$errorMessage, 
										'', true, $productcode, $appserial );
		}

		// NFR subscription may have an expire but no renew
		$renewOrExpireTime = $renewtime;
		if ( !$renewOrExpireTime && $expiretime ) {
			$renewOrExpireTime = $expiretime;
		}

		//Subscription?
		if ( $renewOrExpireTime )
		{
			$togoSeconds = $renewOrExpireTime - $thistime;
			$days = intval( $togoSeconds / (60 * 60 * 24) );
			//Try to renew a few weeks before expiration
			if ( $days < TRYRENEW_DAYSBEFOREEXPIRATION ) 
			{
				//Avoid recursion: 
				//1) When auto renew is installing the license, this method is called again to check the update of the license.
				//2) After auto renew has updated the license, this method is called again to obtain the new status.
				if ( $this->mInAutoRenew )
				{
					LogHandler::Log('license', 'DEBUG', "Auto renew: already busy" );
				}
				else
				{
					$this->mInAutoRenew = true;
		
					$newLicenseStatus = 0;
					$newErrorMessage = '';
					//Normally, the auto renew will only try once a day.
					//If already expired, retry it every logon call...
					$force = ($togoSeconds < 0);
					$bRenewed = $this->tryAutoRenew( $productcode, $force, $newLicenseStatus, $newErrorMessage );
	
					//If the auto renew has updated the license
					//Return the most recent data (recursive call)
					if ( $bRenewed )
					{
						LogHandler::Log('license', 'DEBUG', "Autorenew: returning new status (recursively)." );
						//In case a forced auto renew would be necessary again (which is not normal), the recursion proctection will avoid a loop.
						$newLicenseStatus = $this->getLicenseStatus( $productcode, $appserial, $info, $errorMessage, $logonTime,
							$logonUser, $logonApp, $logonAppVersion );
						$this->mInAutoRenew = false;
						return $newLicenseStatus;
					}
					$this->mInAutoRenew = false;
				}
			}
		}

		$bExpiredTimeExceeded = ( $expiretime && ( $thistime > $expiretime ));
		$bRenewTimeExceeded = ( $renewtime && ( $thistime > $renewtime ));

		$bExpiredMaxObj = false;
		if ( $expiremaxobj )
		{
			$created = '';
			$maxid = $this->lo_getMaxObj( $created );
			if ( $maxid )
			{
				if ( $maxid > $expiremaxobj )
					$bExpiredMaxObj = true;
			}
		}

		if ( $bExpiredTimeExceeded || $bRenewTimeExceeded || $bExpiredMaxObj )
		{
//			print "EXP $bExpiredTimeExceeded || $bExpiredMaxObj";
			$tmpErrorCode = WWL_ERR_EXPIRED;
		}

/*
	//AAA, 2007-6-13
	//Don't check using an old license anymore
	//1) The hack to check is not very logical: reg 200, unreg 100, restore FS (Productinfo only) + DB config table only to use the old 200 license again
	//   To use the old 200 license, the hacker had better restore an old backup
	//2) The situation can also be caused by 'normal' usage somehow.
	
		$checkUsingOldLicense = ($productcode == PRODUCTKEY);
		if ( $checkUsingOldLicense )
		{
			$timestamp = 0;
			$maxobj = 0;
			$created = 0;
			//Get the obj id at the moment all was well (the last valid logon, or installation time) 
			if ( $this->getLastLocalVerifiedDate( $timestamp, $maxobj, $created ))
			{
				if ( $timestamp && !$this->checkUsingOldLicense( $timestamp ) )
				{
					//1) Relaxed: just enter demo mode by setting tmpErrorCode 
					//$tmpErrorCode = WWL_ERR_OLDLICENSE;

					//2) Strict: return an error and don't allow login...
					if ( $productcode == PRODUCTKEY )
					{
						$productcode = ''; //logoff all users;
						$appserial = '';//logoff all users;
					}
					return $this->handleError(	WW_LICENSE_ERR_OLDLICENSE, 
												$errorMessage, 
												'', true, $productcode, $appserial );
				}
			}
		}
*/
		

		//In case the mismatch situation is found for the very first time:
		// - Remember we have notified the error situation (the time of it)
		// - In case not expired (but in case of a configuration change): Update the expire date in the license.
		//   The client should update the license to resolve this situation.
		//   If not, the license will expire automatically whithin a few days...
		if ( $tmpErrorCode && !$errorstart )
		{
			if ( $mismatchFlags )
			{
				$numDemoDays = DEMODAYS;
				$weekDay = date( 'w' ); //0=sunday, 6=saturday
				if ( $weekDay >= 4 )
				{
					if ( $weekDay < 6 ) //Thirsday + Friday
					{
						$numDemoDays += 2;
					}
					else //Saturday
					{
						$numDemoDays += 1;
					}
				}
				$newexpiretime = $thistime + $numDemoDays * 60 * 60 * 24;
				//If expire time not set yet, set it now
				//If expire time already set, make it shorter (not longer)
				if ( !$expiretime || 
					 ( $expiretime && ( $newexpiretime < $expiretime )))
				{
					$arr[ 'expiretime' ] = $this->mLicenseString->unix2WWTimeStr( $newexpiretime );
					$expiretime = $newexpiretime;
					$info[ 'expires' ] = $expiretime;
				}
			}

			$arr[ 'errorstart' ] = $this->mLicenseString->unix2WWTimeStr( $thistime ); //remember that the error situation has been detected

			$created = 0;
			$maxobjid = $this->lo_getMaxObj( $created );
			$arr[ 'errormaxobj' ] = $maxobjid; //remember that the error situation has been detected

			$license = $this->mLicenseString->makeLicenseKey( $arr );
			if ( $license == false )
			{
				return $this->handleError(	WW_LICENSE_ERR_MAKEKEY, 
											$errorMessage,
											'(' . $this->mLicenseString->getError() . ')' 
											);
			}
//			print "<br>Setting new license: $license!<br>serial=$serial";

			$productname = '';
			if ( !$this->setLicense( $productcode, $productname, $serial, $license ))
			{
				return $this->handleError(	WW_LICENSE_ERR_SYSTEM, 
											$errorMessage, 
											'(S' . $this->getErrorCode() . ')' 
											);
			}
		}
		
		if ( $bExpiredTimeExceeded )
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_EXPIREDTIME, 
										$errorMessage, 
										'', true, $productcode, $appserial );
		}

		if ( $bRenewTimeExceeded )
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_RENEWTIME, 
										$errorMessage, 
										'', true, $productcode, $appserial );
		}

		if ( $bExpiredMaxObj )
		{
			if ( $productcode == PRODUCTKEY )
			{
				$productcode = ''; //logoff all users;
				$appserial = '';//logoff all users;
			}
			return $this->handleError(	WW_LICENSE_ERR_EXPIREDMAXOBJ, 
										$errorMessage, 
										'', true, $productcode, $appserial );
		}
		
		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', "getLicenseStatus: License set successfully" );
		}
		
		$errUserLimitStr = '';

		$info[ 'maxusage' ] = $maxusage;
		if ( $maxusage == '*' ) //no limit? May always logon
		{
			return WW_LICENSE_OK;
		}

		if ( $maxusage == '0' )
		{
			return WW_LICENSE_OK_USERLIMIT;
		}

		if ( $productcode == PRODUCTKEY )
		{
			//May be some connections can be killed automatically
			$bAutoCleanTickets = $this->mAutoCleanTicketsForConnections;
			//Check all connections; don't filter on appproductcode
			$checkProductcode = '';
		}
		else
		{
			//May be some connections can be killed automatically
			$bAutoCleanTickets = $this->mAutoCleanTicketsForApps;
			//Check only connections for this appproductcode
			$checkProductcode = $productcode;
		}
		$curusage = $this->getNumConnections( $checkProductcode, $logonUser, $logonApp, $logonAppVersion );
		$info[ 'curusage' ] = $curusage;
		$tooManyUsers = $curusage >= $maxusage;
		if ( $tooManyUsers )
		{
			//The user may still logon if he is already logged on to one or more applications.
			//Check whether this user is already present in the tickets table
			//Note that it makes no sense to check 'userCanStillLogon' in case of 
			//  a certain 'concurrent application' (not SCE Server itself):
			//  Whether that user would already be logged on or not, 
			//  that doesn't matter for the number of concurrent users for the application.
			//  Therefor, only test this for SCE Server itself: the total number of user connections.
			if ( ($productcode == PRODUCTKEY) && $logonUser && $logonApp )
			{
				$bCanLogon = $this->userCanStillLogOn( $logonUser, $logonApp );
				if ( $bCanLogon == 1 )
				{
					$tooManyUsers = false;
				}
			}
			
			// For Content Station (basic & pro) licenses it's allowed for the user to logon from 
			// 2 apps: Content Station and WebEditor. The license count is done per user, but if we have 
			// reached the limit and out user tries to logon via the other app we're arriving here,
			// so we need to detect that case:
			if( (strstr( $productcode, 'ContentStation') !== false) && $logonUser && $logonApp ) {
				$bCanLogon = $this->userCanStillLogOn( $logonUser, $logonApp, $productcode );
				if ( $bCanLogon == 1 )
				{
					$tooManyUsers = false;
				}
			}

			if ( $tooManyUsers && $bAutoCleanTickets )
			{
				//Cleanup until $maxusage-1 connections are left
				$numOnlineClean = $this->cleanupTickets( $maxusage-1, $checkProductcode );
				if ( $numOnlineClean )
				{
					$curusage = $numOnlineClean;
					$info[ 'curusage' ] = $curusage;
					$tooManyUsers = false;
				}
			}
			
			if ( $tooManyUsers )
			{
				$info[ 'usageLimitReached'] = true;
				if ( $productcode == PRODUCTKEY ) {
					$errUserLimitStr = BizResources::localize("LIC_LIMIT_CONNECTIONS_REACHED") . " $maxusage (S" . WW_LICENSE_OK_USERLIMIT . ")";
				} else {
					$errUserLimitStr = BizResources::localize("LIC_LIMIT_USERS_REACHED") . " $maxusage (S" . WW_LICENSE_OK_USERLIMIT . ")";
				}
			}
		}

		$checkTime = ($productcode == PRODUCTKEY);
		if ( $checkTime || $errorstart ) //Always or only if the license has been expired before?
		{
			/*
				The system time can not be (re)set before:
				- the installation date
				- the startdate of the license
				- the creation dates of the objects in the database that have been created since the last valid logon.
				  One can try to avoid this by restoring the license info in the filestore and database AND restoring the time repeatedly. 
				  However, in that case the 'working window' will become smaller and smaller. 
				  And once an error is made, the license is invalid until the new smart objects have been removed.

				In case the current time is set before the 'startdate' of the license, an error occurs (see above: WW_LICENSE_ERR_STARTTIME)
				In case the current time is set before the 'lastLocalVerifiedDate', an error occurs.
				The lastLocalVerifiedDate is set 
				- at the moment the license is installed
				- and, at the moment someone logs on successfully
				"Objects created after this 'verification moment' should have a creation date that is newer than the 'verification moment'."
				So in case an object has been created in the database AFTER the 'lastLocalVerifiedDate' has been set, 
				AND that object has a creation date before this 'lastLocalVerifiedDate', another error (code 6/7/8) is generated. 
				Use case to generate this:
				-	Obtain a license that expires
				-	Before expiration make a backup of the filestore and database.
				-	After expiration, set time backwards for at least two hours (but still after the moment of installation) and restore the filestore and database.
				-	Logon, and create an objects.
				-	Before the expiration, set time backwards again for at least two hours (but still after the moment of installation) and restore the filestore and database.
				-	Logoff and logon. A system time error will occur (code 6/7/8).
			*/
			
			
			//Get the obj id at the moment all was well.
			$timestamp = $errorstart;
			$maxobj = 0;
			$created = $errorstart;
			if ( !$this->getLastLocalVerifiedDate( $timestamp, $maxobj, $created ))
			{
				$timestamp = $errorstart;
				$maxobj = $errormaxobj;
				$created = $errorstart;
			}
			//The very first time (after installation), there is no 'lastlocalverifieddate', and also no errorstart yet, 
			//so only check if there is a timestamp.
			if ( $timestamp )
			{
				if ( !$this->checkCreationDates( $timestamp, $maxobj, $thistime ))
				{
					return $this->handleError(	WW_LICENSE_ERR_SYSTIME, 
												$errorMessage,
												'(S' . $this->getErrorCode() . ')',
												true );  //logoff all users
				}
			}
		}
		
		$togoStr = '';
		if ( $expiretime || $renewtime )
		{
			//Choose the time that expires first: either renew or expire
			if ( $expiretime && !$renewtime ) {
				$testtime = $expiretime;
			} else if ( !$expiretime && $renewtime ) {
				$testtime = $renewtime;
			} else if ( $expiretime < $renewtime ) {
				$testtime = $expiretime;
			} else {
				$testtime = $renewtime;
			}
			$togoSeconds = $testtime - $thistime;
			$days = intval( $togoSeconds / (60 * 60 * 24) );
			if ( $days <= 7 )
			{
				$arr = Array();
				if ( $days > 1 ) 
				{
					$arr[0] = '' . $days;
					$togoStr = BizResources::localize("LIC_WITHIN_X_DAYS", true, $arr );
				}
				else
				{
					$hours = intval( $togoSeconds / (60 * 60) );
					$arr[0] = '' . $hours;
					$togoStr = BizResources::localize("LIC_WITHIN_X_HOURS", true, $arr );
				}
			} else {
				$togoStr = '';
			}

			if ( $tmpErrorCode ) 
			{
				if ( $warningMessage ) {
					$warningMessage .= " ";
				}
				$warningMessage .= BizResources::localize("LIC_UPDATE_TEMPLICENSE") . " " . $togoStr . " " . BizResources::localize("LIC_NOTIFY_ADMIN") . " (S$tmpErrorCode)";
			} 
			else if ( $togoStr )
			{
				if ( $warningMessage ) {
					$warningMessage .= " ";
				}
				if ( $testtime == $renewtime ) {
					$warningMessage .= BizResources::localize("LIC_RENEW_LICENSE") . " " . $togoStr . " " . BizResources::localize("LIC_NOTIFY_ADMIN") . " (S" . WWL_WARNING_RENEW . ")";
				} else {
					$warningMessage .= BizResources::localize("LIC_LICENSE_WILL_EXPIRE") . " " . $togoStr . " " . BizResources::localize("LIC_NOTIFY_ADMIN") . " (S" . WWL_WARNING_EXPIRE . ")";
				}
			}
		}

		if ( $tmpErrorCode )
		{
			$errorMessage = $warningMessage;
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'ERROR', $errorMessage);
			}
			return WW_LICENSE_OK_TMPCONFIG;
		}

		if ( $errUserLimitStr )
		{
			$errorMessage = $errUserLimitStr;
			if ( $warningMessage ) {
				$errorMessage .= " " . $warningMessage;
			}
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'ERROR', $errorMessage);
			}
			return WW_LICENSE_OK_USERLIMIT;
		}
		
		if ( $logonTime )
		{
			//All licenses are valid:
			//Remember the last timestamp of this validation
			if ( !$this->setLastLocalVerifiedDate( $logonTime ) )
			{
				//Can be a tempy problem; do not logoff users
				return $this->handleError(	WW_LICENSE_ERR_UPDATE, 
											$errorMessage, 
											'(S' . $this->mWWLError . ')' );
			}
		}

		if ( $warningMessage )
		{
			$errorMessage = $warningMessage;
			return WW_LICENSE_OK_WARNING;
		}
		
		//Only check once during logon()
		if ( ( $productcode == PRODUCTKEY ) && $this->isOpen() )
		{
			$errorMessage = '** '.BizResources::localize('WW_LICENSE_OK_INTERNAL').' **';
			return WW_LICENSE_OK_INTERNAL;
		}
			
		return WW_LICENSE_OK;
	}
	
	/**
	 * Return status flags, color and status strings based on the given license status
	 * 
	 * @param int licenseStatus
	 * @param string color
	 * @param string status
	 * @param int flags
	 */
	public function getLicenseStatusInfo( $licenseStatus, &$color, &$status, &$flags )
	{
		$color = 'red';
		$status = '';
		$flags = 0;
		switch( $licenseStatus )
		{
			case WW_LICENSE_OK:
				$color = 'green';
				$status = 'OK';
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				break;
			case WW_LICENSE_OK_TMPCONFIG:
				$status = BizResources::localize("LIC_TEMPLICENSE_RECLAIM_OR_GET");
				$color = 'orange';
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_OK_WARNING:
				$status = BizResources::localize("LIC_LICENSE_EXPIRES");
				$color = 'orange';
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_OK_INTERNAL:
				$status = '** '.BizResources::localize('WW_LICENSE_OK_INTERNAL').' **';
				$color = 'green';
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_NOLICENSE1:
			case WW_LICENSE_ERR_NOLICENSE2:
			case WW_LICENSE_ERR_NOLICENSE3:
			case WW_LICENSE_ERR_NOLICENSE4:
			case WW_LICENSE_ERR_NOLICENSE5:
			case WW_LICENSE_ERR_NOLICENSE6:
				$status = BizResources::localize("LIC_NO_SCENT_LICENSE_INSTALLED");
				$flags += WW_LICENSE_GET;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_INVALID_DATA:
				$status = BizResources::localize("LIC_INVALID_DATA");
				$flags += WW_LICENSE_SUPPORT;
				$flags += WW_LICENSE_REMOVE;
				break;
			case WW_LICENSE_OK_USERLIMIT:
				$status = BizResources::localize("LIC_LIMIT_USERS_REACHED");
				$color = 'yellow';
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_TICKETS;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_STARTTIME:
				$status = BizResources::localize("LIC_LICENSE_NOT_VALID_YET");
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_EXPIREDTIME:
				$status = BizResources::localize("LIC_LICENSE_EXPIRED");
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_RENEWTIME:
				$status = BizResources::localize("LIC_RENEW_LICENSE");
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_EXPIREDMAXOBJ:
				$status = BizResources::localize("LIC_USAGE_LIMIT_REACHED");
				$flags += WW_LICENSE_ADDAPP;
				$flags += WW_LICENSE_LOGON;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_PRODKEY:
				$status = BizResources::localize("LIC_INVALID_PRODUCT_LICENSE");
				$flags += WW_LICENSE_GET;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_SERIAL:
				$status = BizResources::localize("LIC_CONFLICT_SERIAL");
				$flags += WW_LICENSE_GET;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_SERIAL2:
				$status = BizResources::localize("LIC_INVALID_SERIAL_LICENSE");
				$flags += WW_LICENSE_GET;
				$flags += WW_LICENSE_SUPPORT;
				break;
			case WW_LICENSE_ERR_UPDATE:
				$status = BizResources::localize("LIC_ERR_UPDATING_LICENSE");
//				$flags += WW_LICENSE_REMOVE;
				break;
			case WW_LICENSE_ERR_ENCRYPT:
				$status = BizResources::localize("LIC_ERR_PUBLIC_ENCRYPT");
//				$flags += WW_LICENSE_REMOVE;
				break;
			case WW_LICENSE_ERR_MAKEKEY:
				$status = BizResources::localize("LIC_ERR_UPDATING_LICENSE");
//				$flags += WW_LICENSE_REMOVE;
				break;
			case WW_LICENSE_ERR_SYSTEM:
				$status = BizResources::localize("LIC_ERR_NO_LICENSE_INFO");
				$flags += WW_LICENSE_SUPPORT;
//				$flags += WW_LICENSE_REMOVE;
				break;
			case WW_LICENSE_ERR_SYSTIME:
				$status = BizResources::localize("LIC_ERR_SYSTIME");
				$flags += WW_LICENSE_GET;
				$flags += WW_LICENSE_SUPPORT;
//				$flags += WW_LICENSE_REMOVE;
				break;
			case WW_LICENSE_ERR_UNKNOWNPRODUCT:
				$status = BizResources::localize("LIC_UNKNOWN_PRODUCT");
				$flags += WW_LICENSE_GET;
				$flags += WW_LICENSE_SUPPORT;
//				$flags += WW_LICENSE_REMOVE;
				break;
			case WW_LICENSE_ERR_OLDLICENSE:
				$status = BizResources::localize("LIC_OLD_LICENSE");
				$flags += WW_LICENSE_GET;
				$flags += WW_LICENSE_SUPPORT;
//				$flags += WW_LICENSE_REMOVE;
				break;
			default:
				$status = BizResources::localize("LIC_UNKNOWN_ERROR");
				$flags += WW_LICENSE_SUPPORT;
//				$flags += WW_LICENSE_REMOVE;
				break;
		}

		if ( $licenseStatus != WW_LICENSE_OK )
			$status .= ' (S' . $licenseStatus . ')';
	}
		
	/**
	 * Create a tempy ticket
	 * In case someone restores only the license info (in the DB and FS), 
	 * then we can test whether tickets or users exists with a logon time later than the 'lastlocalverified date' in the old license.
	 * If so, an error is generated.
	 * Normally, one should restore the complete database, and then newer logon times will not occur.
	 * 
	 * @param string productcode
	 * @param boolean success
	 * @return boolean
	 */
	public function setTicket( $productcode )
	{	
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		require_once BASEDIR.'/server/utils/UrlUtils.php';

        $usageLimitReached = false;
        $errorMessage = '';
		$clientip = WW_Utils_UrlUtils::getClientIP();
		
		$thistime = time(); //This doesn't need to be the database time; a little difference is OK.
		$ticketid= $thistime . $this->mInstallTicketID;
		$user = $this->mInstallTicketID;
		$appname = $this->mInstallTicketID;
		//To distinguish a real user '_install_' and our system "user".
		$appserial = crc32( $ticketid );
		if( !DBTicket::DBnewticket($ticketid, $user, '', '', $clientip, 
			$appname, '', $appserial, $productcode, $usageLimitReached, 
			$errorMessage, '' ) ) {
			$this->mWWLError = WWL_ERR_SET_TICKET;
			return false;
		}
		return true;
	}
	
	/**
	 * Instead of using the current value of key1, or the value from the database,
	 * use the key1 value as stored in the license informatiuon
	 *
	 * @param string $key
	 * @param string $productcode
	 * @param string $errorMessage
	 * @return string key code
	 */
	private function getKeyFromLicense( $key, $productcode, &$errorMessage )
	{
		$license = $this->getLicense( $productcode );
		if ( !$license ) {
			$errorMessage = BizResources::localize("LIC_NO_SCENT_LICENSE_INSTALLED");
			return false;
		}
		$arr = $this->mLicenseString->getLicenseInfo( $license );
		if ( $arr === false ) {
			$errorMessage = BizResources::localize("LIC_ERR_PUBLIC_ENCRYPT");
			return false;
		}
		
		$d = $arr[ "key$key" ];
		return $d;
	}

	/**
	 * Build an installation code for the registration process: 
	 * a string based on the keys key1, key2 and key3.
	 * If 'install' is true, also write the current key information to the database and filestore.
	 * 
	 * @param string errorMessage
	 * @param boolean install
	 * @param boolean addReclaim
	 * @param string $productcode
	 * @return string code
	 */
	public function getInstallationCode( &$errorMessage, $install=true, $addReclaim=false, $productcode='' )
	{
		$reclaimkeystr = '';
		//Just append the current fields?
		if ( $addReclaim )
		{
			for ( $key=1; $key<=3; $key++ )
			{
				if ( ($key == 1) && $productcode )
				{
					//Obtain from license instead of from the database
					//This is necessary in case the first product has been reclaimed, 
					//but the other product(s) have not been reclaimed yet.
					$d = $this->getKeyFromLicense( $key, $productcode, $errorMessage );
					if ( $d === false )
						return false;
				}
				else
				{
					$keydb = $this->getLicenseField( "key$key" );
					if ( !$keydb )
					{
						$wwl_error = $this->getErrorCode();
						$errorMessage = BizResources::localize("LIC_ERR_PREPARING_LICENSEINFO") . " (S$wwl_error)";
						return false;
					}

					$d = $this->mLicenseString->wwl_decrypt( $keydb );
					if ( $d === false )
					{
						$errorMessage = BizResources::localize("LIC_ERR_PRIVATE_DECRYPT");
						return false;
					}
				}
	
				if ( $reclaimkeystr )
					$reclaimkeystr .= "#";
				$reclaimkeystr .= $d;
			}
		}

		$keystr = '';			
		for ( $key=1; $key<=3; $key++ )
		{
			//2007-7 AAA In case key1src is allowed to change (=no testing), don't use the actual value, 
			//but try to use the value thas has been stored in the license or in the database.
			//Otherwise, SmartReg will not recognize this installation (e.g. in case of unregistering)
			//In other words: the first key1 will be always be used, even if it is changed later.
			//And because it will not be changed, no reclaims are necessary anymore.
			$doTest = ( $this->mTestFlags & WWL_TEST_DBFS_COPY_FLAG ) > 0;
			$useOrgKey = ($key==1) && !$doTest;

			$keydbenc = $this->getLicenseField( "key$key" );

			$keysrc = false;
			if ( $useOrgKey )
			{
				if ( $productcode ) 
				{
					$keysrc = $this->getKeyFromLicense( $key, $productcode, $errorMessage );
				}
				if ( !$keysrc && $keydbenc ) 
				{
					$keydb = $this->mLicenseString->wwl_decrypt( $keydbenc );
					if ( $keydb === false )
					{
						$errorMessage = BizResources::localize("LIC_ERR_PRIVATE_DECRYPT");
						return false;
					}
					$keysrc = $keydb;
				}
			}
			if ( !$keysrc )
			{
				$keysrc = $this->getKeySource( $key );
				if ( $keysrc === false )
				{
					$wwl_error = $this->getErrorCode();
					$errorMessage = BizResources::localize("LIC_ERR_PREPARING_LICENSEINFO") . " (S$wwl_error)";
					return false;
				}
			}

			if ( $install )
			{
				$keysrcenc = $this->mLicenseString->wwl_encrypt( $keysrc );
				if ( $keysrcenc === false )
				{
					$errorMessage = BizResources::localize("LIC_ERR_PUBLIC_ENCRYPT") . '(' . $this->mLicenseString->getError() . ')';
					return false;
				}
	
				if ( !$keydbenc )
				{
//					if ( $debug ) print "<br>setting $keysrcenc";
					if ( !$this->setLicenseField( "key$key", $keysrcenc ))
					{
						$wwl_error = $this->getErrorCode();
						$errorMessage = BizResources::localize("LIC_ERR_PREPARING_LICENSEINFO") . " (S$wwl_error)";
						return false;
					}
				}
	
				//In case the key from the database doesn't match, allow to update/refresh it.
				if ( $keydbenc )
				{
					$keydb = $this->mLicenseString->wwl_decrypt( $keydbenc );
					if ( $keydb === false )
					{
						$errorMessage = BizResources::localize("LIC_ERR_PRIVATE_DECRYPT");
						return false;
					}
					if ( $keysrc != $keydb )
					{
						//If the original value should be used, don't update the field
						if ( !$useOrgKey )
						{
							if ( !$this->setLicenseField( "key$key", $keysrcenc ))
							{
								$wwl_error = $this->getErrorCode();
								$errorMessage = BizResources::localize("LIC_ERR_PREPARING_LICENSEINFO") . " (S$wwl_error)";
								return false;
							}
						}
					}
				}
			}

//			if ( $debug ) print "key ($key) = $keysrc";
			if ( $keystr )
				$keystr .= "#";
			$keystr .= $keysrc;
		}

		$keystr = $this->mLicenseString->wwl_encrypt( $keystr, 2 ); //2: simple encryption to avoid long strings
		if ( $addReclaim && $reclaimkeystr )
		{
			$reclaimkeystr = $this->mLicenseString->wwl_encrypt( $reclaimkeystr, 2 );
			$keystr = $this->mLicenseString->mergeReclaimString( $keystr, $reclaimkeystr );
		}
		
		return $keystr;
	}

	/**
	 * Return the number of concurrent connections/users for the given productcode that
	 * are allowed according to the license
	 * 
	 * @param string productcode
	 * @param string errorMessage
	 * @return int number of concurrent users/connections
	 */
	public function getNumLicenses( $prodcode, &$errorMessage )
	{
		$license = $this->getLicense( $prodcode );
		if ( !$license ) {
			$errorMessage = BizResources::localize("LIC_NO_SCENT_LICENSE_INSTALLED");
			return false;
		}
		$arr = $this->mLicenseString->getLicenseInfo( $license );
		if ( $arr === false ) {
			$errorMessage = BizResources::localize("LIC_ERR_PUBLIC_ENCRYPT");
			return false;
		}
		
		return $arr[ 'maxusage' ];
	}
	
	/**
	 * Write the given license information to the database and filestore.
	 * 
	 * @param string productcode
	 * @param string productname
	 * @param string serial
	 * @param string license
	 * @param boolean manual
	 * @param string errorMessage
	 * @return int license status or false on error
	 */
	public function installLicense($productcode, $productname, $serial, $license, $manual, &$errorMessage )
	{
		$errorMessage = '';
		if ( $manual )
		{
			$license = $this->mLicenseString->stripManualLicense( $license );
			if ( $license === false )
			{
				$errorMessage = BizResources::localize("LIC_ERR_INSTALLING_LICENSE");
				return false;
			}
		}
//		print "<br>$serial";
//		print "<br>$license";

		if ( $license == '**REMOVE**' )
		{
			if ( !$this->removeLicense( $productcode ))
			{
				$errorMessage = BizResources::localize("LIC_ERR_REMOVING_LICENSE") . ' (S' . $this->getErrorCode() . ')';
				return false;
			}
			else
			{
				return WW_LICENSE_OK_REMOVED;
			}
		}

		if ( !$this->setLicense( $productcode, $productname, $serial, $license ))
		{
			$errorMessage = BizResources::localize("LIC_ERR_SETTING_LICENSE") . ' (S' . $this->getErrorCode() . ')';
			return false;
		}
			
		if ( !$this->setTicket( $productcode ) )
		{
			$errorMessage = BizResources::localize("LIC_ERR_SETTING_LICENSE") . ' (S' . $this->getErrorCode() . ')';
			return false;
		}

		$errorMessage = '';
		$info = Array();
		$licenseStatus = $this->getLicenseStatus( $productcode, $serial, $info, $errorMessage );
		return $licenseStatus;
	}

	/**
	 * Return true if at least one license has been installed.	
	 * 
	 * @return int Return true if at least one license has been installed.	
	 */
	public function hasLicense()
	{
		$productcodes = $this->getLicenseField( "productcodes" );
		if ( ( $productcodes === false ) || !$productcodes )
			return false;

		$productcodesArr = explode( '|', $productcodes );
		foreach( $productcodesArr as $pc )
		{
			$errorMessage = '';
			$info = Array();
			$serial = $this->getSerial( $pc );
//			$pcname = $this->getName( $pc );
//			print "<br>pc=$pc, name=$pcname, serial=$serial...";
			$licenseStatus = $this->getLicenseStatus( $pc, $serial, $info, $errorMessage );
			if ( $licenseStatus <= WW_LICENSE_OK_MAX )
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Return true if the given status is OK for a user to logon
	 * 
	 * @param int license status
	 * @return boolean 
	 */
	public function canLogonStatus( $licenseStatus )
	{
		return ( ( $licenseStatus == WW_LICENSE_OK ) || 
			 ( $licenseStatus == WW_LICENSE_OK_TMPCONFIG ) ||
			 ( $licenseStatus == WW_LICENSE_OK_WARNING ) ||
			 ( $licenseStatus == WW_LICENSE_OK_INTERNAL ) );
	}

	/**
	 * Return true if the given status is an error status, and the 'remove' button should be shown
	 * 
	 * @param int license status
	 * @param string $errorMessage
	 * @return boolean 
	 */
	private function isRemovableStatus( $licenseStatus, $errorMessage )
	{
		return ( $licenseStatus > WW_LICENSE_OK_MAX ) ||
				( $licenseStatus == WW_LICENSE_OK_TMPCONFIG ) || 
			 	( ( strpos( $errorMessage, WWL_WARNING_EXPIRE ) !== false ) || //Expires soon
			 	  ( strpos( $errorMessage, WWL_WARNING_RENEW ) !== false ) ); //Need to be renewed soon
	}

	/**
	 * Conditionally adds an error message to the current list of messages to be returned to user.
	 * When the error message is set, and the error is not in the list, it will be added.
	 * Messages added are marked with "LicenseNotification" to allow clients to recognize those and maybe 
	 * show them differently than normal messages.
	 * 
	 * @param string $userId Short user name (id)
	 * @param string $licenseStatus The current license status
	 * @param string $errorMessage Localized error message. Empty when no error.
	 * @param Message[] $collectedMessages  List of Message objects to show user
	 */
	public function addToUserMessages( $userId, $licenseStatus, $errorMessage, &$collectedMessages )
	{
		if( !empty($errorMessage) && $licenseStatus != WW_LICENSE_OK ) { // error/warning set?
			$msgFound = false;
			if( !is_null($collectedMessages) ) foreach( $collectedMessages as $message ) {
				if( $message->MessageID == 'S'.$licenseStatus && $message->MessageTypeDetail == 'LicenseNotification' ) {
					$msgFound = true;
					break;
				}
			}
			if( !$msgFound ) {
				if( is_null($collectedMessages) ) $collectedMessages = array();
				$collectedMessages[] = new Message( 
					null, $userId, // ObjectID, UserID -> we are sending to user (not to object)
					'S'.$licenseStatus, // MessageID -> Use 'S' prefix (Server) to make it unique (compared to DB ids), and to make it same as server error ids shown in SOAP faults
					'system', // MessageType -> generated by Enterprise system
					'LicenseNotification', // MessageTypeDetail -> allow clients to recognize (and maybe show them differently than normal messages)
					$errorMessage, date('Y-m-d\TH:i:s'), null,  // Message, TimeStamp, Expiration
					'Warning', null, null );  // MessageLevel, FromUser, StickyInfo
			}
		}
	}
	
	private function printGetLicense()
	{
		print "<br/><br/><a href='javascript:install()'>" . BizResources::localize("LIC_GET_LICENSE"). "</a>\n";
		print "<form method='POST' name='theForm' action='getlicense.php'>\n";
		print "<input type='hidden' name='productcode' value='" . PRODUCTKEY . "'>\n";
		print "<input type='hidden' name='productname' value='" . PRODUCTNAME . "'>\n";
		print "<input type='image' width='0' height='0' alt=''>\n";
		print "</form>\n";
		print "<script language='Javascript' type='text/Javascript'>\n";
		print "<!--\n";
		print "	function install()\n";
		print "	{\n";
		print "		document.forms.theForm.submit();\n";
		print "	}\n";	
		print "//-->\n";
		print "</script>\n";
	}
	
	private function printAutoLicense()
	{
		require_once BASEDIR.'/server/utils/license/StealthInstaller.class.php';
		$installer = new WW_Utils_License_StealthInstaller();
		if( $installer->canAutoActivate() ) {
			print '<br/><br/><hr/><b>Note:</b> '.
				'The "server/config/WWActivate.xml" file is installed which '.
				'preconfigures license keys and seats for multiple Enterprise products. '.
				'As an alternative to the manual activation, you can click one of the following '.
				'links to activate or deactivate all the preconfigured licenses at once. <br/><br/>'.
				'<a href="stealthinstaller.php?mode=activate">Activate pre-configured licenses</a><br/>'.
				'<a href="stealthinstaller.php?mode=deactivate">Deactivate pre-configured licenses</a><br/>';
		}
	}
	
	/**
	 * Outputs an HTML string that can be used to display the current license status
	 * 
	 * @param boolean inAdminTickets
	 */
	public function showStatusInHTML( $inAdminTickets=false )
	{
		$productcodes = $this->getLicenseField( "productcodes" );
		if ( $productcodes === false || !$productcodes)
		{
			if ( ( $this->mWWLError == WWL_ERR_DB_FIELDNOTEXIST ) ||  //The very first time, the field will not exist yet
				 ( ( $this->mWWLError == 0 ) && !$productcodes ) )		//When all licenses have been removed, the field exists and is empty
			{
				print "<h2>" . BizResources::localize("LIC_LICENSE_STATUS") . "</h2>\n";
				print BizResources::localize("LIC_NO_SCENT_LICENSE_INSTALLED") . "\n";
				$this->printGetLicense();
				$this->printAutoLicense();
				return;
			}
			else if ( $this->mWWLError == WWL_ERR_DB ) //Also show the database error text in the error message
			{
				print "<h2>" . BizResources::localize("LIC_LICENSE_STATUS") . "</h2>\n";
				print BizResources::localize("ERR_COULD_NOT_CONNECT_TO_DATEBASE") . ' (S' . $this->mWWLError . ')';
				$url = SERVERURL_ROOT.INETROOT.'/server/wwtest/index.htm';
				print "<br/><a href='$url' target='_top'>wwTest</a>";
				return;
			}
			else 
			{
				//An error occured after the "productcodes" field has been initialized before
				//This means: someone has been messing with e.g. the filestore and/or database.
				print "<h2>" . BizResources::localize("LIC_LICENSE_STATUS") . "</h2>\n";
				print BizResources::localize("LIC_INVALID_DATA") . ' (S' . $this->mWWLError . ')';
				$url = SERVERURL_ROOT.INETROOT.'/server/wwtest/index.htm';
				print "<br/><br/><a href='removelicense.php'>" . BizResources::localize("LIC_REMOVE_LICENSE") . "</a>";
				print "<br/><a href='$url' target='_top'>wwTest</a>";
				return;
			}
		}
	
		$productcodesArr = explode( '|', $productcodes );
		$limitsArr = Array();
		$i = 0;
		$mainFlags = 0;
		$SCEServerFound = false;
		foreach( $productcodesArr as $pc )
		{
			$errorMessage = '';
			$info = Array();
			$serial = $this->getSerial( $pc );
			$pcname = $this->getName( $pc );
			$licenseStatus = $this->getLicenseStatus( $pc, $serial, $info, $errorMessage );
			$limitsArr[ $i ] = $info;
			$limitsArr[ $i ]['serial' ] = $serial;
			$limitsArr[ $i ]['licensestatus' ] = $licenseStatus;
			
//			print "<br>$pc, $serial, $curusage, $maxusage, $usageLimitReached, $errorMessage, $expires";
			$limitsArr[ $i ]['pcode'] = $pc;
			if ( !$pcname )
				$pcname = '[' . $pc . ']';
			$limitsArr[ $i ]['name'] = $pcname;
			if ( !$errorMessage )
				$errorMessage = '&nbsp;';
			$limitsArr[ $i ]['error'] = $errorMessage;
			$expires = $limitsArr[ $i ]['expires'];
			$limitsArr[ $i ]['expires'] = '&nbsp;';
			if ( $expires && ( $expires != -1 ))
			{
				$expires = date( 'Y-m-d', $expires );
				$limitsArr[ $i ]['expires'] = $expires;
			}
			$renew= $limitsArr[ $i ]['renew'];
			$limitsArr[ $i ]['renew'] = '&nbsp;';
			if ( $renew && ( $renew != -1 ))
			{
				$renew = date( 'Y-m-d', $renew );
				$limitsArr[ $i ]['renew'] = $renew;
			}
	
			$color = 'red';
			$flags = 0;
			$status = '';
			$this->getLicenseStatusInfo( $licenseStatus, $color, $status, $flags );
			$limitsArr[ $i ]['color'] = $color;
			$limitsArr[ $i ]['status'] = $status;
			$limitsArr[ $i ]['flags'] = $flags;
			if ( $pc == PRODUCTKEY )
			{
				$mainFlags = $flags;
				$SCEServerFound = true;
			}
			$i++;
		}
	
		print "<h2>" . BizResources::localize("LIC_LICENSE_STATUS") . "</h2>";

		$tablebg = '#dddddd'; // Grey
		$clientAppUserLimit = false;
		if ( count( $limitsArr ) > 0 ) {
			print "<table class='text' width='100%'>";
			$maxmax = 0;
			$maxcur = 0;
			$hasNoLimit = false;
			foreach( $limitsArr as $idx => $limitArr2 )
			{
				$cur = $limitArr2[ 'curusage' ];
				if ( $cur > $maxcur )
					$maxcur = $cur;
				$max = $limitArr2[ 'maxusage' ];
				if ( $max == '*' ) {
					$hasNoLimit = true;
				}
				else
				{
					if ( $max > $maxmax )
						$maxmax = $max;
				}
			}
			$max = max( 1, max( $maxmax, $maxcur ));
			if ( $hasNoLimit )
			{
				$nolimit = intval( $max * 3/2 );
				foreach( $limitsArr as $idx => $limitArr2 )
				{
					if ( $limitsArr[$idx][ 'maxusage' ] == '*' )
						$limitsArr[$idx][ 'maxval' ] = $nolimit;
				}
				$max = $nolimit;
			}
			
			$tabwidth = 50000; // Just a very high random number.
			if ( $max ) {
				$scale = intval( $tabwidth/$max );
			} else {
				$scale = 1;
			}

			print "<tr bgcolor='#cccccc'>";
			print "<th class='text'>" . BizResources::localize('LIC_APPLICATION') . "</th>";
			print "<th class='text'>" . BizResources::localize('LIC_THE_SERIAL') . "</th>";
			print "<th class='text'>" . BizResources::localize('LIC_STATUS') . "</th>";
			print "<th class='text'>" . BizResources::localize('LIC_MESSAGE') . "</th>";
			print "<th class='text'>" . BizResources::localize('LIC_RENEW') . "</th>";
			print "<th class='text'>" . BizResources::localize('LIC_EXPIRES') . "</th>";
			print "<th class='text'>" . BizResources::localize('LIC_CURRENT') . "</th>";
			print "<th class='text' width='300'>" . BizResources::localize('LIC_USAGE') . "</th>";
			print "<th class='text'>" . BizResources::localize('LIC_LIMIT') . "</th>";
			print "<th class='text' width='10'>" . BizResources::localize('LIC_ACTION') . "</th>";
			print "</tr>";
			foreach( $limitsArr as $idx => $limitArr2 )
			{
				$cur = $limitArr2[ 'curusage' ];
				$max = $limitArr2[ 'maxusage' ];
				if ( $cur >= $max ) {
					$c1 = 'red'; // Reached the maximum usage.
				} else {
					$c1 = 'green'; // Currently used license
				}
				$w1 = $cur * $scale;

				$c2 = '#8A8A8A'; // dark grey // Un-used license
				if ( $max == '*' ) {
					$maxval = $limitsArr[ $idx ][ 'maxval' ];
				} else {
					$maxval = $max;
				}
				$w2 = $maxval * $scale - $w1;

				$c3 = $tablebg; // Grey // Background color - to filled up the space not drawn by the used/unused license bar
				$w3 = $tabwidth - $w2 - $w1;

				$name = $limitArr2[ 'name' ];
				$serial = $limitArr2[ 'serial' ];
				$pcode = $limitArr2[ 'pcode' ];
				
				if ( ($max == '0') && ($max != '*' ) ) {
					$fontcolor='red';
				} else {
					$fontcolor='black';
				}

				if ( $cur == -1 )
					$cur = '&nbsp;';
				if ( $max == -1 )
					$max = '&nbsp;';

				print "<tr bgcolor='$tablebg'>";
				print "<td><font color='$fontcolor'>$name</font></td>";
				print "<td><font color='$fontcolor'>$serial</font></td>";
				print "<td><img src='images/" . $limitArr2['color' ] . ".gif' width='10' height='10'>" . $limitArr2[ 'status' ] . "</td>";
				print "<td>" . $limitArr2[ 'error' ] . "</td>";
				print "<td>" . $limitArr2[ 'renew' ] . "</td>";
				print "<td>" . $limitArr2[ 'expires' ] . "</td>";
				print "<td align='center'>$cur</td>";
//				print "<td><img src='images/$c1.gif' width='$w1' height='10'><img src='images/$c2.gif' width='$w2' height='10'><img src='images/$c3.gif' width='$w3' height='10'></td>";
				print "<td><table cellpadding=0 cellspacing=0 border='0' bgcolor='#d3d3d3'><tr>";
				if ( $w1 )
					print "<td bgcolor='$c1' width='$w1' height='10' style=\"min-width:1px\"></td>";
				if ( $w2 )
					print "<td bgcolor='$c2' width='$w2' height='10' style=\"min-width:1px\"></td>";
				if ( $w3 )
					print "<td bgcolor='$c3' width='$w3' height='10' style=\"min-width:1px\"></td>";
				print "</tr></table></td>\n";
				print "<td align='center'><font color='$fontcolor'>$max</font></td>";
				
				$manageStr = '';
				//Only show action button in case the license is OK, or when user limit has been reached.
				if ( $limitArr2['color' ] != 'red' )
				{
					if ( $limitArr2[ 'licensestatus' ] != WW_LICENSE_OK_TMPCONFIG )
					{
						if ( ( $max != '0') && ($max != '*' ) && ( $max != '&nbsp;') )
						{
							$manageStr .= "<input type='button' value='" . BizResources::localize("LIC_REGISTER_MORE") . "' onClick=\"r('getlicense.php', '$pcode', '$name', '');\">";
							$manageStr .= "<input type='button' value='" . BizResources::localize("LIC_UNREGISTER") . "' onClick=\"r('unregister.php', '$pcode', '$name', '');\">";
						}
					}
				}

				if ( $limitArr2[ 'renew' ] != '&nbsp;' || $limitArr2[ 'expires' ] != '&nbsp;' )
					$manageStr .= "<input type='button' value='" . BizResources::localize("LIC_RENEW") . "' onClick=\"r('renew.php', '$pcode', '$name', 'renew');\">";

				if ( $limitArr2[ 'licensestatus' ] == WW_LICENSE_OK_TMPCONFIG )
					$manageStr .= "<input type='button' value='" . BizResources::localize("LIC_RECLAIM") . "' onClick=\"r('reclaim.php', '$pcode', '$name', '');\">";

				//In case no SCE Server has been found, may all entries are old products (e.g. alpha or beta products)
				//In that case, allow the administrator to remove the license.
				if ( $this->isRemovableStatus( $limitArr2[ 'licensestatus' ], $limitArr2[ 'error' ] ) || !$SCEServerFound)
					$manageStr .= "<input type='button' value='" . BizResources::localize("ACT_REMOVE") . "' onClick=\"r('removelicense.php', '$pcode', '$name', '');\">";

				if ( !$manageStr )
					$manageStr .= "&nbsp;";

				if ( $limitArr2[ 'licensestatus' ] == WW_LICENSE_OK_USERLIMIT ) {
					$clientAppUserLimit = true;
				}

				print "<td align='center'>$manageStr</td>";
				print "</tr>";
			}
			print "</table>";
		}
		
		if ( $SCEServerFound )
		{
			if ( $mainFlags & WW_LICENSE_GET ) {
				$this->printGetLicense();
			}
			if ( $mainFlags & WW_LICENSE_ADDAPP ) {
				print "<br/><a href='getlicense.php'>" . BizResources::localize("LIC_ADD_CONCURRENT_APPS") . "</a>";
			}
		}
		else
		{
			//If no SCE Server found, but products have been found,
			//Then may be these products are old products (e.g. alpha or beta test versions)
			//In that case, allow the administrator to remove these old licenses
			if ( count($productcodesArr) > 0 ) {
				print "<br/><a href='removelicense.php'>" . BizResources::localize("LIC_REMOVE_LICENSE") . "</a>";
			}

			//If no SCE Server found, allow to install it
			$this->printGetLicense();
		}
	
		if ( !$inAdminTickets )
		{
			//Force the admin user to logon again:
			//In case the limit was reached after installing a new license (with less users), 
			//the _install_ user should be removed (and the lastlogon timestamp needs to be updated) by logging on.
			//Don't go to the admintickets page directly!

			if ( $clientAppUserLimit ) 
			{
				//In case the SCE Server license limit has NOT been reached, but a client license has been reached,
				//allow the admin user to go to the admin tickets page without logging on again.
				//The intermediate page 'gotoadmintickets' will prepare the session parameters that will be checked by admintickets.php.
				print "<br/><a href='gotoadmintickets.php'>" . BizResources::localize("LIC_REMOVE_TICKETS_TO_LOGOFF") . "</a>";
			} 
			else if ( $mainFlags & WW_LICENSE_TICKETS ) 
			{
				print "<br/><a href='../../apps/login.php?logout=true'>" . BizResources::localize("LIC_REMOVE_TICKETS_TO_LOGOFF") . "</a>";
			}
		}
		if ( $mainFlags & WW_LICENSE_SUPPORT )
			print "<br/><a href='support.php'>" . BizResources::localize("LIC_SEND_LICENSEINFO_TO_SUPPORT") . "</a>";

		if ( 0 && ($mainFlags & WW_LICENSE_REMOVE ))
			print "<br/><a href='removelicense.php'>" . BizResources::localize("LIC_REMOVE_LICENSE") . "</a>";
	
		print "<form name='license' method='POST'>\n";
		print "<input type='hidden' name='productcode'>\n";
		print "<input type='hidden' name='productname'>\n";
		print "<input type='hidden' name='option'>\n";
		print "<input type='image' name='allowsubmit' width='0' height='0' alt=''>\n";
		print "</form>\n";
		print "<script language='Javascript' type='text/Javascript'>\n";
		print "<!--\n";
		print "	function r(a,c,n,option)\n";
		print "	{\n";
		print "	 var f=document.forms.license;\n";
		print "	 f.action=a; f.productcode.value=c;f.productname.value=n;f.option.value=option;\n";
		print "	 f.submit();\n";
		print "	}\n";
		print "//-->\n";
		print "</script>\n";

		$this->printAutoLicense();
	}
	
	/**
	 * Just test whether the 'local' file in the filestore can be updated (if present).
	 * The license status can be OK (= only read), while logging on may fail (=update 'local' file).
	 * Called by 'wwtest' and the main admin license page.
	 * 
	 * @return boolean success
	 */
	public function wwTestWritable()
	{
		//AAA 2007-8-31
		//May be new Filestore fields can be read and written, but may be the current files can not be written to.
		$field = 'local';
		$value = $this->lo_getFieldFS( $field );
		if ( $value !== false )
		{
			if ( !$this->lo_setFieldFS( $field, $value ))
			{
				return false;
			}
		}
		return true;
	}	

	/**
	 * Do some basic tests, to check whether the license functionality is working fine. 
	 * Called by 'wwtest'.
	 * On error, return an errorMessage and help string
	 * 
	 * @param string errorMessage
	 * @param string help
	 * @param boolean warn  When returned false, this flag indicates to show it as warning or error.
	 * @param boolean extended Does extra/advanced license status tests as well.
	 * @return boolean success
	 */
	public function wwTest( &$errorMessage, &$help, &$warn, $extended = false )
	{
		$errorMessage = '';
		$warn = false; // assume error

		//1) test the DB
		$field = 'test';
		$value = 'A long string containing more than 255 characters and a \slash somewhere. ';
		$value .= $value;
		$value .= $value;
		//Test writing the key value pair
		if ( !$this->lo_setFieldDB( $field, $value ))
		{
			//Can not READ from database too?
			if ( !$this->lo_getFieldDB( 'version' ))
			{
				//There is a database (connection) error.
				$errorMessage = BizResources::localize("ERR_COULD_NOT_CONNECT_TO_DATEBASE") . " (S" . $this->mWWLError . ")";
			}
			else
			{
				$errorMessage = BizResources::localize("LIC_ERR_WRITING_DATABASE") . " (S" . $this->mWWLError . ")";
			}
			$help = BizResources::localize("LIC_CHECK_SCENT_DATABASE");
			return false;
		}

		//Try to read it back
		$dbval = $this->lo_getFieldDB( $field );
		if ( ($dbval === false) || ( $dbval != $value ))
		{
			$errorMessage = BizResources::localize("LIC_ERR_READING_DATABASE") . " (S" . $this->mWWLError . ")";
			$help = BizResources::localize("LIC_CHECK_SCENT_DATABASE");
			return false;
		}

		if ( !$this->lo_removeFieldDB( $field ))
		{
			$errorMessage = BizResources::localize("LIC_ERR_REMOVE_FROM_DATABASE") . " (S" . $this->mWWLError . ")";
			$help = BizResources::localize("LIC_CHECK_SCENT_DATABASE");
			return false;
		}

		//2) test the FS
		if ( !$this->initDirectories() )
		{
			$errorMessage = BizResources::localize("LIC_ERR_CREATING_FILESTORE_DIRS") . " (S" . $this->mWWLError . ")";
			$help = BizResources::localize("LIC_CHECK_FILESTORE_ACCESS_RIGHTS");
			return false;
		}
			
		//Test writing the key value pair
		if ( !$this->lo_setFieldFS( $field, $value ))
		{
			$errorMessage = BizResources::localize("LIC_ERR_WRITING_FILESTORE") . " (S" . $this->mWWLError . ")";
			$help = BizResources::localize("LIC_CHECK_FILESTORE_ACCESS_RIGHTS");
			return false;
		}

		//Try to read it back
		$dbval = $this->lo_getFieldFS( $field );
		if ( $dbval === false || ( $dbval != $value ))
		{
			$errorMessage = BizResources::localize("LIC_ERR_READING_FILESTORE") . " (S" . $this->mWWLError . ")";
			$help = BizResources::localize("LIC_CHECK_FILESTORE_ACCESS_RIGHTS");
			return false;
		}

		if ( !$this->lo_removeFieldFS( $field ))
		{
			$errorMessage = BizResources::localize("LIC_ERR_REMOVE_FROM_FILESTORE") . " (S" . $this->mWWLError . ")";
			$help = BizResources::localize("LIC_CHECK_FILESTORE_ACCESS_RIGHTS");
			return false;
		}
		
		if ( !$this->wwTestWritable() )
		{
			$errorMessage = BizResources::localize("LIC_ERR_WRITING_FILESTORE") . " (S" . $this->mWWLError . ")";
			$help = BizResources::localize("LIC_CHECK_FILESTORE_ACCESS_RIGHTS");
			return false;
		}
		
		//3) test encryption and decryption (via openSSL)
    	$str = "Tessie";
		$enc_str = $this->mLicenseString->wwl_encrypt( $str);
		$str2 = $this->mLicenseString->wwl_decrypt( $enc_str );
		if ( $str2 != $str )
		{
			$help = BizResources::localize("LIC_CHECK_OPENSSL_SUPPORT");
			return false;
		}
		
		//4) Time difference between AS and DS?
		if ( !defined( 'IGNORE_DS_TIMEDIFF' )) 
		{
			$diffMinutes = $this->ASDSTimeDiff();
			if ( $diffMinutes )
			{
				$arr[0] = $diffMinutes;
				$errorMessage = BizResources::localize("LIC_ASDS_TIMEDIFF", true, $arr );
	
				$arr[0] = BizResources::localize("LIC_DS");
				$arr[1] = date( 'Y-m-d H:i:s', $this->time() );
				$errorMessage .= "<br/>" . BizResources::localize("LIC_ASDS_SERVER", true, $arr );
	
				$arr[0] = BizResources::localize("LIC_AS");
				$arr[1] = date( 'Y-m-d H:i:s', time() );
				$errorMessage .= "<br/>" . BizResources::localize("LIC_ASDS_SERVER", true, $arr );
	
				$arr[0] = BizResources::localize("LIC_DS");
				$help = BizResources::localize("LIC_ASDSTIME", true, $arr );
				$help .= "<br/>" . "<a href='http://www.php.net/manual/en/timezones.php' target='_blank'>date.timezone in PHP.ini</a>";
	
				return false;
			}
		}

		if ( $extended )
		{
			$warningMessage = '';
			$warningHelp = '';

			/*// Commented out for many reasons mentioned in BZ#17849
			//Logon will be slow on CGI or CLI systems
			//Give a warning for that.
		    $cgi_cli = ((strpos(php_sapi_name(),'cgi') !== false) ||
						(strpos(php_sapi_name(),'cli') !== false));
			if ( $cgi_cli ) {
				$warningMessage = BizResources::localize("LIC_CGICLI_SLOWLOGON_ERROR");
				$warningHelp = BizResources::localize("LIC_CGICLI_SLOWLOGON_HELP");
				$warningHelp .= "<br/><a href='http://www.php.net/install.windows' target='_blank'>www.php.net/install.windows</a>\n";
				$warningHelp .= "<br/><a href='http://www.tjitjing.com/blog/2006/05/php5-with-iis6-on-windows-server-2003.html' target='_blank'>php5-with-iis6-on-windows-server-2003</a>\n";
				$warningHelp .= "<br/><a href='http://www.visualwin.com/PHP-ISAPI/' target='_blank'>www.visualwin.com/PHP-ISAPI/</a>\n";
			}*/
			
			$productcodes = $this->getLicenseField( "productcodes" );
			if ( ($productcodes === false ) || !$productcodes ) 
			{
				if ( ( $this->mWWLError == WWL_ERR_DB_FIELDNOTEXIST ) ||  //The very first time, the field will not exist yet
					 ( ( $this->mWWLError == 0 ) && !$productcodes ) )		//When all licenses have been removed, the field exists and is empty
				{
					$errorMessage = BizResources::localize("LIC_NO_SCENT_LICENSE_INSTALLED");
					$warn = true;
				}
				else if ( $this->mWWLError == WWL_ERR_DB ) //Show the database error text in the error message
				{
					$errorMessage = BizResources::localize("ERR_DATABASE") . ' (S' . $this->mWWLError . ')';
				}
				else
				{
					$errorMessage = BizResources::localize("LIC_INVALID_DATA") . ' (S' . $this->mWWLError . ')';
				}
			} 
			else
			{
				$SCEAppserial = $this->getSerial( PRODUCTKEY );
				if ( $SCEAppserial === false ) 
				{
					$errorMessage = BizResources::localize("LIC_NO_SCENT_LICENSE_INSTALLED");
				} 
				else 
				{
					$info = array();
					$errMsg = '';
					$licenseStatus = $this->getLicenseStatus( PRODUCTKEY, $SCEAppserial, $info, $errMsg );
					if ( !$this->canLogonStatus( $licenseStatus )) {
						$errorMessage = $errMsg;
					}
				}
			}
			if ( $errorMessage )
			{
				$url = SERVERURL_ROOT.INETROOT.'/server/admin/license/index.php';
				$help = BizResources::localize("LIC_LOGON_AS_ADMIN") . '<br/>';
				$help .= ' ' . BizResources::localize("LIC_OR_GO_TO") . ' ';
				$help .= "<a href='$url' target='_top'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";

				if ( $warningMessage ) {
					$errorMessage .= "<br/>" . $warningMessage;
					$help .= "<br/><br/>" . $warningHelp;
				}
				return false;
			}
			
			if ( $warningMessage )
			{
				$errorMessage = $warningMessage;
				$help = $warningHelp;
				$warn = true;
				return false;
			}
		}

		//In case of a subscription license, do a connection test
		$productcode = PRODUCTKEY;
		$license = $this->getLicense( $productcode );
		if ( $license !== false )
		{
			$arr = $this->mLicenseString->getLicenseInfo( $license );
			//License already available?
			if ( $arr !== false )
			{
				$renewtime = $arr[ 'renewtime' ];
				//Subscription?
				if ( $renewtime )
				{
					$connectionMessage = '';
	
					//This will use the (optional) proxy settings:
					$bConnection = $this->SmartRegContact( $productcode, $connectionMessage );
					if ( !$bConnection ) {
						$errorMessage = BizResources::localize("LIC_NO_CONTACT" ) . '<br>' . $connectionMessage;
						
						$parameters = Array();
						$url = PINGURL;
						$proxyInfo = $this->getProxyParameters();
						//Using a proxy?
						if ( isset( $proxyInfo[ 'host'] ) && $proxyInfo[ 'host'] )
						{
							//This will NOT use proxy settings; go to the PINGURL without proxy:
							$proxyInfo = '';
							$response = post_it( $parameters, $url, $proxyInfo );
							if ( strpos( $response, 'error: ' ) !== false )
							{
								$errorMessage .= '<br>' . $response;
								$help = "Allow the SCE Server to connect to the internet (with or without a proxy server).";
							}
							else
							{
								$errorMessage .= "<br>Connection without proxy server succeeded!";
							}
						}
						//Beta testing: also try port 80 instead of 8080 (a router/switch/proxy may block port 8080??)
						LogHandler::Log('license', 'DEBUG', "Connect $url..." );
						if ( strpos( $url, ':8080' ) !== false )
						{
							$url = str_replace( $url, ':8080', '' ); //use port 80
							$response = post_it( $parameters, $url, $proxyInfo );
							LogHandler::Log('license', 'DEBUG', "Connect $url..." );
							if ( strpos( $response, 'error: ' ) !== false )
							{
								$errorMessage .= '<br>' . $response;
								$help = "Allow the SCE Server to connect to the internet (with or without a proxy server).";
							}
							else
							{
								$errorMessage .= "<br>Connection without proxy and to port 80 succeeded!";
							}
						}
						
						if ( $help )
						{
							$contactURL = SERVERURL_ROOT.INETROOT.'/server/admin/license/contactinfo.php';
							$help .= "<br><a href='$contactURL' target='_top'>Proxy server settings</a>";
							$testURL = SERVERURL_ROOT.INETROOT.'/server/wwtest/connectiontest.php';
							$help .= "<br><a href='$testURL' target='_top'>Connection test</a>";
						}
	
						return false;
					}
					else
					{
						$errorMessage = "<br>Connection succeeded";
					}
				}
			}
		}

		return true;
	}
	
	public function getInstallTicketID()
	{
		return $this->mInstallTicketID;
	}
	
	/**
	 * Check whether the files that need to be encrypted really are encrypted.
	 * If not encrypted, return true
	 * 
	 * @param int mode
	 * @return boolean success
	 */
	public function isOpen( $mode = 1 )
	{
		$files = Array();
		switch( $mode )
		{
			//Strict: compare whether all files and directories are encrypted
			case 3:
				//TO DO
				break;
				
			//Optimized: only check files that have been changed after the modification date of serverinfo.php
			//(To check for wrong updates)
			case 2:
				break;

			//Limited: only the two main license files
			case 1:
				$files[] = BASEDIR . '/server/utils/license/license.class.php';
				$files[] = BASEDIR . '/server/utils/license/licensestring.class.php';
				break;
		}
		
		/* TO DO: Add to files
		foreach( $dirs as $d )
		{
		}*/
		foreach( $files as $f )
		{
			$fh = @fopen( $f, 'r' );
			if ( !$fh )
				return true;
			//The string 'ionCube Loader' should occur in the first part of the file
			$data = fread( $fh, 1024 );
			if ( strpos( $data, "ionCube Loader" ) === false )
			{
				fclose( $fh );
				return true;
			}
			fclose( $fh );
		}
		return false;
	}
	
	/**
	 * Copy from the field from the database to the filestore or vice versa
	 * 
	 * @param string field
	 * @param string direction/target
	 * @return boolean success
	 */
	private function copyLicenseField( $field, $target )
	{
		if ( $target == 'FS' )
		{
			$src = $this->lo_getFieldDB( $field );
			if ( $src === false )
				return false;
			if( $this->mLicLog ) {
				$dest = $this->lo_getFieldFS( $field );
				LogHandler::Log('license', 'INFO', "Overwriting FS ($dest) with $src");
			}
			return $this->lo_setFieldFS( $field, $src );
		}
		else
		{
			$src = $this->lo_getFieldFS( $field );
			if ( $src === false )
				return false;
			if( $this->mLicLog ) {
				$dest = $this->lo_getFieldDB( $field );
				LogHandler::Log('license', 'INFO',"Overwriting DB ($dest) with $src");
			}
			return $this->lo_setFieldDB( $field, $src );
		}
	}
	
	/**
	 * Synchronize the filestore and database
	 * This should only be used in case someone restored only a database or a filestore from a backup,
	 * or in case someone restores both the database and filestore, but with a different timestamp
	 * This can be called by synch.php, which can be delivered by support on request.
	 * 
	 * @param string magic string to avoid abuse
	 * @param string error string
	 * @return boolean success
	 */
	public function synch( $magic, &$err )
	{
		//To avoid abuse of the API, the caller has to supply a magic key
		$magic2 = $this->getKeySource( 1 );
		$magic2 .= "pipo";
		$magic2 = md5( $magic2 );
		$err = 'Error (1)';
		if ( $magic != $magic2 )
			return false;
		
		//First, find out the most recent version: DB or FS?
		//FS:

		$d = $this->lo_getFieldFS( 'local' );
		if ( $d === false )
		{
			$err = 'Error (2)';
			return false;
		}

		//Add some random text for confusion...
		//$nowstr = timestamp - $this->random(4) . '|' . $nowstr. '|' . dechex( $maxid ) . '|' . $created . '|' . $this->random(4);
		$minpos = strpos( $d, '-' );
		if ( $minpos === false )
			return false;
		$d = substr( $d, $minpos+1 );
		$d = $this->mLicenseString->wwl_decrypt( $d );
		if ( $d === false )
			return false;
		$arr = explode( '|', $d );
		if ( count( $arr ) != 5 )
			return false;
		$timestampFS = $this->mLicenseString->WWTimeStr2Unix($arr[1]);

		$d = $this->lo_getFieldFS( 'local' );
		if ( $d === false )
			return false;
		//$nowstr = timestamp - $this->random(4) . '|' . $nowstr. '|' . dechex( $maxid ) . '|' . $created . '|' . $this->random(4);
		$minpos = strpos( $d, '-' );
		if ( $minpos === false )
			return false;
		$d = substr( $d, $minpos+1 );
		$d = $this->mLicenseString->wwl_decrypt( $d );
		if ( $d === false )
			return false;
		$arr = explode( '|', $d );
		if ( count( $arr ) != 5 )
			return false;
		$timestampDB = $this->mLicenseString->WWTimeStr2Unix($arr[1]);
		
//		print "$timestampFS < $timestampDB";
		if ( $timestampFS < $timestampDB ) {
			$target = 'FS'; 
		} else if ( $timestampFS > $timestampDB ) {
			$target = 'DB';
		} else {
			$err = "No synchronize necessary.";
			return false;
		}
		if ( !$this->copyLicenseField( "key1", $target ))
			return false;
		if ( !$this->copyLicenseField( "key2", $target ))
			return false;
		if ( !$this->copyLicenseField( "key3", $target ))
			return false;
		if ( !$this->copyLicenseField( "local", $target ))
			return false;

		$productcodesArr = $this->getProductcodes();
		foreach( $productcodesArr as $pc )
		{
			if ( !$this->copyLicenseField( $pc, $target ))
				return false;
		}

		if ( !$this->copyLicenseField( "productcodes", $target ))
			return false;

		$err = '';
		return true;
	}
	
	/**
	 * Return the current time according to the database server
	 * To avoid "license time errors", don't use the PHP function time(), but use this method.
	 * If one would use the PHP time() function, the local time of the AS is used, which may be (slightly) different from the DB server
	 *
	 * @return integer time in seconds since 1-1-1970 or false on error
	 */
	public function time()
	{
		$row = null;
		$error = '';
		$sql = '';

		//To keep de sql driver classes 'open'/'uncrypted', 
		//AND to avoid the driver classes to LOG our secret statements, 
		//we have some hardcoded statements here...
		$dbserver = DBSERVER;
		$dbuser = DBUSER;
		$dbpass = DBPASS;
		$dbselect = DBSELECT;
		$dbtime = 0;

		switch( strtolower(DBTYPE) ) {
			case 'mysql':
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
				$dbh = new mysqli( $dbserver, $dbuser, $dbpass, $dbselect, $port, $socket );
				if( $dbh->connect_error ) {					
					$error = 'connect error:' . $dbh->connect_error;
				} else {
					$sql = 'SELECT UNIX_TIMESTAMP() "UTCTIME"';
					$sth = @$dbh->query( $sql );
					if ( !$sth ) {
						$error = 'query error ' . $sql;
					} else {
						$row = $sth->fetch_assoc();
						$dbtime = $row['UTCTIME'];
					}
				}
				break;

			case 'mssql':
				$dbh = $connectionInfo = array( "UID"=>$dbuser,
                        				 		"PWD"=>$dbpass,
                         		 				"Database"=>$dbselect);
				$dbh = sqlsrv_connect( $dbserver, $connectionInfo);
				if( !isset($dbh) || is_resource($dbh) === false ) {
					$error = 'connect error';
				} else {
					//style 120 = yyyy-mm-dd hh:mi:ss "ODBC canonical", see http://doc.ddart.net/mssql/sql70/ca-co_1.htm
					$sql = 'SELECT CONVERT(CHAR(19), GETUTCDATE(), 120) "UTCTIME"';
					$sth = @sqlsrv_query($dbh, $sql);
					if (! $sth) {
						$error = 'query error ' . $sql;
					} else {
						$row = @sqlsrv_fetch_array($sth);
						$dbtime = strtotime( $row['UTCTIME'].' UTC' );
					}
				}
				break;
			default:
				//3rd party interface to an unknown DB??
				$error = ' DB unknown';
		}

		if ( $dbtime === 0 ) {
			if( $this->mLicLog ) {
				LogHandler::Log('license', 'DEBUG', "Could not get system time. Error=$error. SQL=$sql." );
			}
			$this->mWWLError = WWL_ERR_DSTIME_DB;
			return false;
		}

			if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', "System time: $sql => " . date( 'Y-m-d H:i:s', $dbtime ) );
		}
		
		return $dbtime;
	}
	
	/**
	 * Determine the system time difference between the database server and this AS.
	 * If the difference is more than a certain limit, the difference in minutes is returned.
	 * Otherwise, 0 is returned
	 *
	 * @param string $DStime optional
	 * @return integer difference in minutes (0 if OK)
	 */
	private function ASDSTimeDiff( $DStime = '' )
	{
		$AStime = time();
		if ( !$DStime )
			$DStime = $this->time();
		$diff = abs( $AStime - $DStime );
		$diffminutes = $diff / 60;
		$limit = 10;

		//Check whether the daylightsaving time changes this hour...
		$isDST1 = date( 'I', $AStime );
		$isDST2 = date( 'I', $AStime + 60 * 60  ); //1 hour later
		//If a change is detected, the limit may tempy be 1 hour extra
		if ( $isDST1 != $isDST2 ) {
			$limit += 60;
		}
		if( $this->mLicLog ) {
			LogHandler::Log('license', 'DEBUG', "Compare AS " . date( 'Y-m-d H:i:s', $AStime ) . " to DS " . date( 'Y-m-d H:i:s', $DStime ) . ". Diff=$diffminutes (limit $limit)." );
		}

		return ( $diffminutes > $limit ) ? $diffminutes : 0;
	}


	/* ====================================================================================================== */
	/* ======================================== AUTO RENEW SECTION ========================================== */
	/* ====================================================================================================== */
	
	/**
	 * Try to contact the SmartReg server using a direct POST (not using the browser)
	 *
	 * @param string $productcode (can be handy in the future to send extra info to SmartReg)
	 * @param string $errorMessage contains error message afterwards
	 * @return bool success
	 */
	public function SmartRegContact( $productcode, &$errorMessage )
	{
		$url = PINGURL;

		$parameters = Array();
		$proxyInfo = $this->getProxyParameters();
		$response = post_it( $parameters, $url, $proxyInfo );
		if ( strpos( $response, 'error: ' ) !== false )
		{
			$errorMessage = $response;
			LogHandler::Log('license', 'ERROR', 
					"Auto renew ping: connect error. " . 
					$errorMessage . 
					", url=$url" . 
					", host=" . $proxyInfo[ 'host' ] . 
					", port=" . $proxyInfo[ 'port' ] .  
					", user=" . $proxyInfo[ 'user' ] .  
					", pass=" . $proxyInfo[ 'pass' ] );
			return false;
		}
		
		return strpos( $response, "PONG" ) !== false;
	}

	/**
	 * Try to renew the license automatically
	 * Not using the browser, but by connection to the server and posting HTTP data.
	 * Remember the date and time of the last trial: only try it once a day...
	 *
	 * @param string $productcode
	 * @param boolean $force. If true, ignore the 'once a day' check, and always do it
	 * @param string $errorMessage, returns the errorMessage associated with the new licenseStatus
	 * @param integer $licenseStatus, returns the new licenseStatus
	 * @return bool success
	 */
	public function tryAutoRenew( $productcode, $force, &$licenseStatus, &$errorMessage )
	{
		$debug = 0;
		$semaPostfix = "autorenew";
		
		//No need to wait until it is free: If someone else is already updating, then we can ignore it now.
		$maxAttempts = 1;
		$semaId = $this->lo_getSema( $semaPostfix );
		if ( !$semaId )
		{
			//Someone else is already busy now...
			LogHandler::Log('license', 'DEBUG', "Auto renew: could not get sema" );
			return false;
		}
		LogHandler::Log('license', 'DEBUG', "Auto renew: got sema" );

		$now = $this->time();
		$dbkey = "lastautorenew";

		//Skip "once a day" check?
		if ( $force )
		{
			LogHandler::Log('license', 'DEBUG', "Ignoring last attempt; just trying auto renew..." );
		}
		else
		{
			$lastAttempt = $this->lo_getFieldDB( $dbkey );
			if ( $lastAttempt !== false )
			{
				$lastAttemptUnix = strtotime( $lastAttempt );
				$diff = $now - $lastAttemptUnix;
				//Only test once a day....
				if ( $diff < 60 * 60 * 24 )
				{
					LogHandler::Log('license', 'DEBUG', "last auto renew attempt already done in the last 24 hours ($diff seconds); returning." );
	
					if ( $debug ) {
						//In one logon call, the auto renew may be called twice. Ignore the second one.
						if ( $diff < 60 * 1 ) {
							$this->lo_releaseSema( $semaId );
							return false;
						} else {
							LogHandler::Log('license', 'DEBUG', 'TEST: CONTINUE NOW (not returning)' );
						}
					} else {
						$this->lo_releaseSema( $semaId );
						return false;
					}
				}
			}
		}

		LogHandler::Log('license', 'INFO', 'Attempting automatic license renewal for product \'' . $this->getName( $productcode ) . '\'' );

		$parameters = $this->getRenewParameters( $productcode );
		if ( $parameters === false ) {
			LogHandler::Log('license', 'ERROR', 'Error creating license renew parameters' );
			$this->lo_releaseSema( $semaId );
			return false;
		}

		$contactparameters = $this->getContactParameters();
		foreach( $contactparameters as $k => $v )
			$parameters[ $k ] = $v;
		
		LogHandler::Log('license', 'DEBUG', "Auto renew: posting..." );
		$response = $this->postRenew( $parameters, $errorMessage );
		if ( $response === false ) {
			LogHandler::Log('license', 'ERROR', 'Error in license renewal request to WoodWing registration server.' );
			$this->lo_releaseSema( $semaId );
			return false;
		}

		if ( !$response ) {
			LogHandler::Log('license', 'ERROR', 'No response from WoodWing registration server.' );
			$this->lo_releaseSema( $semaId );
			return false;
		}
		
		LogHandler::Log('license', 'DEBUG', "Auto renew: install license from response..." );
		$confirmParameters = $this->installLicenseFromResponse( $response );
		if ( $confirmParameters === false ) {
			LogHandler::Log('license', 'ERROR', 'Error handling license renew response from WoodWing registration server.' );
			$this->lo_releaseSema( $semaId );
			return false;
		}
		
		//Return values:
		$licenseStatus = $confirmParameters[ 'orgstatus' ];
		unset( $confirmParameters[ 'orgstatus' ] );
		$errorMessage = $confirmParameters[ 'message' ];

		LogHandler::Log('license', 'DEBUG', "Auto renew: post confirm..." );
		$ok = $this->postConfirmRenew( $confirmParameters );
		if ( $ok === false ) {
			LogHandler::Log('license', 'ERROR', 'Error confirming license renewal to WoodWing registration server.' );
			$this->lo_releaseSema( $semaId );
			return false;
		}
		
		$lastAttempt = date( 'Y-m-d\TH:i:s', $now );
		$this->lo_setFieldDB( $dbkey, $lastAttempt );
		
		$this->lo_releaseSema( $semaId );
		LogHandler::Log('license', 'INFO', 'License has been renewed automatically.' );
		return true;
	}
	
	/**
	 * Create the parameters for the HTTP renew post
	 *
	 * @param string $productcode
	 * @return string[]|boolean false, or Array of parameters
	 */
	private function getRenewParameters( $productcode )
	{
		$par = Array();
		$par[ 'mode' ] = 'register';
		$par[ 'autorenew' ] = '1';
		$par[ 'version' ] = PRODUCTVERSION;
		$par[ 'majorversion' ] = PRODUCTMAJORVERSION;
		$par[ 'minorversion' ] = PRODUCTMINORVERSION;
		$serial = $this->getSerial( $productcode );
		if ( $serial === false ) {
			LogHandler::Log('license', 'ERROR', "Auto license renew: error in serial of product $productcode. (S" . $this->mWWLError . ')' );
			return false;
		}
		$par[ 'serial' ] = $serial;

		$errorMessage = '';
		$installationcode = $this->getInstallationCode( $errorMessage );
		if ( $installationcode === false ) {
			LogHandler::Log('license', 'ERROR', "Auto license renew: error in installationcode." );
			return false;
		}

		$par[ 'installationcode' ] = $installationcode;
		$par[ 'concurrentseats' ] = 'renew';
		$par[ 'productcode' ] = $productcode;
		
		$productname = $this->getName( $productcode );
		if ( $productname === false ) {
			LogHandler::Log('license', 'ERROR', "Auto license renew: error in productname of $productcode." );
			return false;
		}

		$par[ 'productname' ] = $productname;
		$par[ 'localtime' ] = date( 'Ymd H:i:s' );

		$clientname = @$_ENV[ 'SERVER_NAME' ];
		if ( !$clientname )
		{
			$clientname = @$_ENV[ 'COMPUTERNAME' ];
			if ( !$clientname )
			{
				$clientname = @$_SERVER[ 'HTTP_HOST' ];
				if ( !$clientname )
					$clientname = @$_SERVER[ 'SERVER_ADDR' ];
			}
		}
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$par[ 'clientip' ] = WW_Utils_UrlUtils::getClientIP();
		$par[ 'clientname' ] = $clientname;

		return $par;
	}

	/**
	 * Post the HTTP renew post
	 *
	 * @param string[] $parameters
	 * @param string $errorMessage, contains errorMessage on error.
	 * @return string|boolean response or false on error
	 */
	private function postRenew( $parameters, &$errorMessage ) 
	{
		$url = ACTIVATEURL;

		$proxyInfo = $this->getProxyParameters();
		$response = post_it( $parameters, $url, $proxyInfo );
		if ( strpos( $response, 'error: ' ) !== false )
		{
			LogHandler::Log('license', 'ERROR', 
					"Auto renew (connection) error. " . 
					$response . 
					", url=$url" . 
					", host=" . isset($proxyInfo[ 'host' ]) ? $proxyInfo[ 'host' ] : '' . 
					", port=" . isset($proxyInfo[ 'port' ]) ? $proxyInfo[ 'port' ] : '' .  
					", user=" . isset($proxyInfo[ 'user' ]) ? $proxyInfo[ 'user' ] : '' .  
					", pass=" . isset($proxyInfo[ 'pass' ]) ? $proxyInfo[ 'pass' ] : '' );
			$errorMessage = $response;
			return false;
		}
		return $response;
	}
		
	/**
	 * Install the license returned by the HTTP renew post
	 *
	 * @param string $response
	 * @return string[]|boolean false, or an array with parameters (status, error, message)
	 */
	private function installLicenseFromResponse( $response )
	{
		$fields = Array( 'error', 'serial', 'license', 'ticket', 'concurrentseats', 'productcode', 'productname' );

		//SmartReg returns parameters line by line
		$lines = explode( "\n", $response );
		$par = Array();
		
		foreach( $lines as $line )
		{
			foreach( $fields as $f )
			{
				$n = strlen( $f );
				if ( substr( $line, 0, $n + 2 ) == $f . ': ' )
				{
					$par[ $f ] = substr( $line, $n + 2 );
				}
			}
		}
		
		if ( isset( $par[ 'error' ] ) && $par[ 'error' ] )
		{
			LogHandler::Log('license', 'ERROR', 'Auto license renew: error returned by WoodWing registration server: ' . $par[ 'error' ] );
			return false;
		}
		
		if ( !isset( $par[ 'productcode'] ) ||
				!isset( $par[ 'productname'] ) ||
				!isset( $par[ 'serial'] ) ||
				!isset( $par[ 'license'] ) )
		{
			LogHandler::Log('license', 'ERROR', 'Auto license renew: no or invalid data from returned by WoodWing registration server.' );
			return false;
		}
		
		$errorMessage = '';
		$licenseStatus =  $this->installLicense($par[ 'productcode'], 
									$par[ 'productname' ], 
									$par[ 'serial' ], 
									$par[ 'license' ], 
									0, 
									$errorMessage );

		if ( $licenseStatus === false ) {
			LogHandler::Log('license', 'ERROR', 'Error installing renewed license.' . $errorMessage );
			return false;
		}

		$msgSmartReg = '';
		$licenseStatusSmartReg = $licenseStatus;
		switch( $licenseStatus )
		{
			case WW_LICENSE_OK:
				$msgSmartReg = "License installed successfully.";
				break;
			case WW_LICENSE_OK_REMOVED:
				$msgSmartReg = "License removed successfully.";
				//When status passed to SmartReg to confirm the registration: don't specify an error code.
				$licenseStatusSmartReg = WW_LICENSE_OK;
				break;
			case WW_LICENSE_OK_USERLIMIT:
			{
				$msgSmartReg = "License installed successfully. License usage limit reached.";
				//When status passed to SmartReg to confirm the registration: don't specify an error code.
				$licenseStatusSmartReg = WW_LICENSE_OK;
				break;
			}
			case WW_LICENSE_OK_WARNING:
			case WW_LICENSE_OK_INTERNAL:
			{
				//The warning will be displayed too.
				$msgSmartReg = "License installed successfully.";
				//When status passed to SmartReg to confirm the registration: don't specify an error code.
				$licenseStatusSmartReg = WW_LICENSE_OK;
				break;
			}
		}
		
		LogHandler::Log('license', 'DEBUG', "Auto renew installation: status=$licenseStatus ($licenseStatusSmartReg), error=$errorMessage, message=$msgSmartReg." );
		$par[ 'orgstatus' ] = $licenseStatus;
		$par[ 'status' ] = $licenseStatusSmartReg;
		$par[ 'error' ] = $errorMessage;
		$par[ 'message' ] = $msgSmartReg;
		return $par;
	}
	
	/**
	 * Post the given parameters to the SmartReg server to confirm the renew
	 *
	 * @param array $par post parameters
	 * @return string|boolean response returned by the SmartReg server
	 */
	private function postConfirmRenew( $par )
	{
		$par[ 'mode' ] = 'confirmregister';
		$par[ 'autorenew' ] = '1';

		$par[ 'version' ] = PRODUCTVERSION;
		$par[ 'majorversion' ] = PRODUCTMAJORVERSION;
		$par[ 'minorversion' ] = PRODUCTMINORVERSION;

		$url = ACTIVATEURL;

		$response = post_it( $par, $url, $this->getProxyParameters() );
		return $response;
	}
	
	/**
	 * Return the array of fields that represent the contact info
	 *
	 * @return string[]
	 */
	public function getContactFields()
	{
		return Array(	'name'
						, 'email'
						, 'company'
						, 'address1'
						, 'address2'
						, 'zip'
						, 'city'
						, 'country'
						, 'phone'
						, 'fax'
						);
	}
	
	/**
	 * Read the contact info from the database and return them in an array
	 *
	 * @return string[] List of parameters
	 */
	public function getContactParameters()
	{
		$par = Array();
		$contactfields = $this->getContactFields();
		foreach( $contactfields as $f )
		{
			$dbvalue = $this->lo_getFieldDB( DB_CONTACT_PREFIX . $f );
			if ( $dbvalue !== false ) {
				$par[ $f ] = $dbvalue;
			}
		}
		return $par;
	}

	/**
	 * Save the given array with contact info parameters into the database
	 *
	 * @param string[] $arr List of contact info settings
	 */
	public function setContactParameters( $arr )
	{
		$contactfields = $this->getContactFields();
		foreach( $contactfields as $f )
		{
			if ( isset( $arr[ $f ] ) ) {
				$this->lo_setFieldDB( DB_CONTACT_PREFIX . $f, $arr[ $f ] );
			}
		}
	}

	/**
	 * Return the array of fields that represent the proxy server info
	 *
	 * @return string[]
	 */
	public function getProxyFields()
	{
		return Array(	'host'
						, 'port'
						, 'user'
						, 'pass'
					);
	}

	/**
	 * Return an array with the proxy settings that are stored in the config table of the database
	 *
	 * @return array with proxy settings
	 */
	public function getProxyParameters()
	{
		$par = Array();

		$proxyfields = $this->getProxyFields();
		foreach( $proxyfields as $f )
		{
			$dbvalue = $this->lo_getFieldDB( DB_PROXY_PREFIX . $f );
			if ( $dbvalue !== false ) {
				$par[ $f ] = $dbvalue;
			}
		}
		return $par;
	}
	
	/**
	 * Save the given array with proxy parameters into the database
	 *
	 * @param string[] array with proxy settings
	 */
	public function setProxyParameters( $arr )
	{
		$proxyfields = $this->getProxyFields();
		foreach( $proxyfields as $f )
		{
			if ( isset( $arr[ $f ] ) ) {
				$this->lo_setFieldDB( DB_PROXY_PREFIX . $f, $arr[ $f ] );
			}
		}
	}

	/**
     * Detect and return the current hostname
     *
     * @return string
     */
    private function getHostName()
    {
        if( isset($_SERVER['HTTP_HOST']) ) {
            $host = $_SERVER['HTTP_HOST'];
        } elseif( isset($_SERVER['SERVER_NAME']) ) {
            $host = $_SERVER['SERVER_NAME'];
        } else {
            $host = php_uname('n');
        }
        return $host;
    }
	
	/**
	 * Determines the name of the server from server/environment super globals.
	 *
	 * @return string Name of server (or IP when name not resolved). Returns empty string on failure.
	 */
	public function getServerNameOrAddr()
	{
		$name = isset($_SERVER[ 'SERVER_NAME' ]) ? $_SERVER[ 'SERVER_NAME' ] : '';
		if ( !$name ) {
			$name = isset($_ENV[ 'SERVER_NAME' ]) ? $_ENV[ 'SERVER_NAME' ] : '';
			if ( !$name ) {
				$name = isset($_ENV[ 'COMPUTERNAME' ]) ? $_ENV[ 'COMPUTERNAME' ] : '';
				if ( !$name ) {
					$name = isset($_SERVER[ 'HTTP_HOST' ]) ? $_SERVER[ 'HTTP_HOST' ] : '';
					if ( !$name ) {
						$name = isset($_SERVER[ 'SERVER_ADDR' ]) ? $_SERVER[ 'SERVER_ADDR' ] : '';
					}
				}
			}
		}
		return $name;
	}
}