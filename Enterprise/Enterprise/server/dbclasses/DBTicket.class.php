<?php

/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v5.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This first part of this module was part of BizSession.class.php before.
 *
 * The BizSession module only needed the encrypted smartdb for a new ticket (DBNewTicket).
 * To avoid that malevolent/malicious clients implement there own version of DBNewTicket using the old "open" version 4 code of smartdb,
 * we are generating special version 5 tickets. These tickets are validated in the Logon class (near SOAP entry point). 
 * So in case the old version 4 code of smartdb would be used, the WoodWing clients can not logon.
 *	
 * The first part of the ticket is a part of a hashed string, based on the logon parameters.
 *	
 * The second part of this module was part of smartdb.php before.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBTicket extends DBBase
{
	static private $ServerJob = null; // Use to check tickets in context of background/async server job processing.
	
	const TABLENAME = 'tickets';
	
	/**
	 * @var array $ticketCache Cache session data (per ticket) that is frequently asked.
	 * @since 10.2.0
	 */
	private static $ticketCache = array();
	
	/**
	 * The first part of ticket is based on the hashed value of the logon parameters.
	 *
	 * [2007-4-6] The client with the concurrent license should verify the ticket by generating a hash based on the logon parameters.
	 * Idealy, the clientip should also be part of the hash: in case someone logs on to the same application on different machine, 
	 * a different hash will be generated. When someone inspects the tickets in the tickets table, they will not see that two tickets have
	 * the same part.
	 * However, it is sometimes difficult for a client application (InDesign/InCopy) to determine its own IP-address, so we don't check on clientip...
	 * Probably using the 'clientname' will be different on the different machines...
	 *
	 * @param string $clientip
	 * @param string $user
	 * @param string $clientname
	 * @param string $appname
	 * @param string $appserial
	 * @param string $appproductcode
	 * @return string The hash
	 */
	public static function makeTicketHashPart( $clientip='', $user='', $clientname='', $appname='', $appserial='', $appproductcode='' )
	{
		//"bla" = extra confusion for third parties who try to regenerate our hash...
		//	$str = $clientip . $user . $clientname . $appname . $appproductcode . $appserial . "bla";
		$str = $user . $clientname . $appname . $appserial . $appproductcode . "bla";
		return substr( md5( $str ), 0, 8 );
	}

	/**
	 * @param string $clientip
	 * @param string $user
	 * @param string $clientname
	 * @param string $appname
	 * @param string $appserial
	 * @param string $appproductcode
	 * @param integer $sz
	 * @return string
	 */
	private static function generateHashTicket($clientip, $user, $clientname, $appname, $appserial, $appproductcode, $sz = 20)
	{
		//The first part of ticket is based on the clientip!
		//The rest is random
		$key = self::makeTicketHashPart( $clientip, $user, $clientname, $appname, $appserial, $appproductcode );
		$keylen = strlen( $key );
		
		mt_srand((double)microtime()*1000000);
		$seed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		for ($i=$keylen;$i<$sz;$i++) {
			$it = mt_rand(0,61);
			$key .= $seed[$it];
		}
		return $key;
	}

	/**
	 * Check whether the ticket is valid.
	 * It is valid if the first part matches the hash of the logon parameters
	 *
	 * @param string $ticket
	 * @param string $clientip
	 * @param string $user
	 * @param string $clientname
	 * @param string $appname
	 * @param string $appserial
	 * @param string $appproductcode
	 * @return boolean
	 */
	public static function checkTicketHash($ticket, $clientip = '', $user='', $clientname='', $appname='', $appserial='', $appproductcode='')
	{
		if( !$clientip ) {
			require_once BASEDIR.'/server/utils/UrlUtils.php';
			$clientip = WW_Utils_UrlUtils::getClientIP();
		}
		$mykey = self::makeTicketHashPart( $clientip, $user, $clientname, $appname, $appserial, $appproductcode );
		$keylen = strlen( $mykey );
		return ( substr( $ticket, 0, $keylen ) == $mykey );
	}
	
	/**
	 * Generate a new unique ticket in the new version 5 format: using a hash.
	 *
	 * @param string $orguser: either the 'usr' or the 'fullname' from the smart_users table (as entered by the end user)
	 * @param string $shortuser: the 'usr' from the smart_users table
	 * @param string $server Server to logon to as returned from GetServers (or empty if not supported)
	 * @param string $clientname
	 * @param string $appname
	 * @param string $appversion
	 * @param string $appserial
	 * @param string $appproductcode
	 * @param string $usageLimitReached: in case false is returned, 'userLimit' is true in case the max number of concurrent users has been reached.
	 * @param string $errorMessage: in case false is returned, the errorMessage is set.
	 * @param string $masterTicket [9.7] In case the same application does logon twice (e.g. IDS for DPS) this refers to the ticket of the first logon.
	 * @return string ticketid or false
	 * @throws BizException In case of database connection error.
	 */
	public static function genTicket( $orguser, $shortuser, $server, $clientname, 
		$appname, $appversion, $appserial, $appproductcode, &$usageLimitReached, 
		&$errorMessage, $masterTicket = '' )
	{
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientip = WW_Utils_UrlUtils::getClientIP();
		$ticketid = '';

		// create new ticket
		while( true ) {
			$ticketid = self::generateHashTicket( $clientip, $orguser, $clientname, $appname, 
							$appserial, $appproductcode, 40 );
			if( self::checkUniqueTicket( $ticketid ) ) {
				break;
			}
		}
	
		if( !self::DBnewticket( $ticketid, $shortuser, $server, $clientname, $clientip, 
					$appname, $appversion, $appserial, $appproductcode, $usageLimitReached, 
					$errorMessage, $masterTicket ) ) {
			return false;
		}
	
		return $ticketid;
	}

	/**
	 * Calculates the new session expiration time for given client.
	 * 
	 * For 'Web' applications the EXPIREWEB option is used. For others EXPIREDEFAULT is used.
	 *
	 * @param string $app  Name of client application; 'Web', 'InCopy' or 'InDesign'
	 * @return string      Current time (SOAP datetime format) plus configured expiration
	 */
	private static function _expire( $app )
	{
        $time = time() + self::getExpireTime( $app );

		return date( 'Y-m-d\TH:i:s', $time );
	}

    /**
     * Get the expire time based on the app
     * For 'Web' applications the EXPIREWEB option is used. For others EXPIREDEFAULT is used.
     *
     * @param $app string Name of client application; 'Web', 'InCopy' or 'InDesign'
     * @return int the offset
     */
    public static function getExpireTime( $app )
    {
	    if( $app == 'Web' ) {
		    $time = EXPIREWEB;
	    } else {
		    $time = EXPIREDEFAULT;
	    }
	    return $time;
    }

	/**
	 * Check whether the ticket is NOT present in the tickets table
	 *
	 * @param string $ticket 
	 * @return boolean unique (true=not found)
	 * @throws BizException In case of database connection error.
	 */
	public static function checkUniqueTicket( $ticket )
	{
		$where = '`ticketid` = ?';
		$params = array( strval( $ticket ) );
		$row = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		return !isset( $row['id'] );
	}

	/**
	 * Create new ticket in the database. <br>
	 * Initialize ticket expiration time. <br>
	 *
	 * @param string $ticketid   Unique ticket; gives user access to the system with given client application
	 * @param string $usr        User id (short name)
	 * @param string $database   Server to logon to as returned from GetServers (or empty if not supported)
	 * @param string $clientname Client machine name
	 * @param string $clientip   OS domain IP number
	 * @param string $appname    Client application name, for example: Web, InDesign, InCopy, PhotoShop, Illustrator
	 * @param string $appversion Client application version number
	 * @param string $appserial  Client application serial number
	 * @param string $appproductcode
	 * @param string $usageLimitReached: in case false is returned, 'userLimit' is true in case the max number of concurrent users has been reached.
	 * @param string $errorMessage: in case false is returned, the errorMessage is set.
	 * @param string $masterTicket [9.7] In case the same application does logon twice (e.g. IDS for DPS) this refers to the ticket of the first logon.
	 * @return boolean True on success else false.
	 * 	- userLimit: in case false is returned, 'userLimit' is true in case the max number of concurrent users has been reached.
	 *  - errorMessage: in case false is returned, the errorMessage is set.
	 * @throws BizException In case of database connection error.
	 */
	public static function DBnewticket( $ticketid, $usr, $database, $clientname, $clientip, 
		$appname, $appversion, $appserial, $appproductcode, &$usageLimitReached, 
		&$errorMessage, $masterTicket )
	{
		//The last valid logon is saved in the license check,
		//and checks later on will compare the tickets (and optionally the users table) against this logon time.
		//To avoid small time differences, be sure to use the same date/time for both the license check and the tickets!
		//So use the variable $now for both calling 'getLicenseStatus' and inserting rows in the tickets table.
		
		include_once( BASEDIR . '/server/utils/license/license.class.php' );
		$lic = new License();
		
		//Instead of using the time of this AS, we use the time of the DS.
		//Because this time is used to enter values in the database that are checked later by the license module,
		//the (small) time differences between the AS and DS should be avoided.
		$dbtime = $lic->time();
		if ( $dbtime === false ) {
			$errorMessage = "Fatal system error: can not obtain system time.";
			return false;
		}
		$now = date('Y-m-d\TH:i:s', $dbtime );

		$installTicketID = $lic->getInstallTicketID();

		if ( self::licenseCheckIsNeeded( $usr, $installTicketID, $ticketid, $appserial, $appname ) ) {
			$errorMessage = '';
			$info = Array();

			//Check the license and number of concurrent users for this application
			//On success, use '$now' as last valid logon time
			//Be sure to use this $now also to insert a ticket in the database below!
			$licenseStatus = $lic->getLicenseStatus( $appproductcode, $appserial, $info, $errorMessage, $now,
				$usr, $appname, $appversion );
			if ( !$lic->canLogonStatus( $licenseStatus ) )
			{
				$usageLimitReached = 
					($licenseStatus == WW_LICENSE_OK_USERLIMIT ) && $info[ 'usageLimitReached' ];
				return false;
			}

			//Check the license and number of concurrent connections for SCE Server
			//In case no license has been installed yet, appserial will be false and getLicenseStatus() will handle that
			$appserialSCE = $lic->getSerial( PRODUCTKEY );
			$tmpErrorMessage = $errorMessage;
			//On success, use '$now' as last valid logon time
			//Be sure to use this $now also to insert a ticket in the database below!
			$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $appserialSCE, $info, $errorMessage, $now,
				$usr, $appname, $appversion );
			if ( !$lic->canLogonStatus( $licenseStatus ) )
			{
				$usageLimitReached = 
					(($licenseStatus == WW_LICENSE_OK_USERLIMIT ) && $info[ 'usageLimitReached' ]);
				if ( $tmpErrorMessage && ($errorMessage != $tmpErrorMessage ))
					$errorMessage = $tmpErrorMessage . " " . $errorMessage;
				return false;
			}
		}

		$values = array(
			'ticketid' => strval( $ticketid ),
			'usr' => strval( $usr ),
			'db' => strval( $database ),
			'clientname' => strval( $clientname ),
			'clientip' => strval( $clientip ),
			'appname' => strval( $appname ),
			'appversion' => strval( $appversion ),
			'appserial' => strval( $appserial ),
			'appproductcode' => strval( $appproductcode ),
			'expire' => self::_expire($appname),
			'logon' => strval( $now ),
			'masterticketid' => strval( $masterTicket )
		);
		if( self::insertRow( self::TABLENAME, $values ) === false ) {
			return false;
		}

		// cache ticket data
		self::$ticketCache[ strval( $ticketid ) ] = array(
			'usr' => strval( $usr ),
			'appname' => strval( $appname ),
			'appversion' => strval( $appversion ),
			'masterticketid' => strval( $masterTicket )
		);

		$values = array( 'lastlogondate' => strval( $now ) );
		$where = '`user`= ?';
		$params = array( strval( $usr ) );
		return self::updateRow( 'users', $values, $where, $params );
	}

	/**
	 * Checks if the license status must be checked before a ticket is issued.
	 *
	 * In case of InDesign Server the license is checked randomly. Checking the license status for each newly created
	 * ticket to serve InDesign Server jobs has a too serious performance drawback.
	 * Secondly always allow the _install_ user to leave a footprint in the tickets table; do not check the license for that.
	 * To distinguish our _install_ user from a real user named _install_, also check the application serial.
	 *
	 * @param string $user
	 * @param string $installTicketID
	 * @param string $ticketId
	 * @param string $appSerial
	 * @param string $appName
	 * @return bool License status must be checked.
	 */
	static private function licenseCheckIsNeeded( $user, $installTicketID, $ticketId, $appSerial, $appName )
	{
		$check = false;

		if( $appName == 'InDesign Server' ) {
			$random = mt_rand( 0, 1600 );
			if( $random % 40 == 0 ) {
				$check = true;
			}
		} elseif( ($user != $installTicketID) || ( crc32( $ticketId ) != $appSerial ) ) {
			$check = true;
		}

		return $check;
	}

	/**
	 * Search for user's ticket in the database. <br>
	 * Required: user id (short name) and client application name. <br>
	 *
	 * @param string $usr        User id (short name)
	 * @param string $database   Server to logon to as returned from GetServers (or empty if not supported)
	 * @param string $clientname Client machine name
	 * @param string $clientip   OS domain IP number
	 * @param string $appname    Client application name, for example: Web, InDesign, InCopy, PhotoShop, Illustrator
	 * @param string $appversion Client application version number
	 * @param string $appserial  Client application serial number
	 * @return string            ticket; gives user access to the system with given client application
	 * @throws BizException In case of database connection error.
	 */
	public static function DBfindticket( $usr, $database, $clientname, $clientip, $appname, $appversion, $appserial )
	{
		LogHandler::Log('dbticket', 'DEBUG', $usr.' '.$database.' '.$clientname.' '.$clientip.' '.$appname.' '.$appversion.' '.$appserial);

		$wheres = array( '`usr` = ?', '`appname` = ?' );
		$params = array( strval( $usr ), strval( $appname ) );
		if( $database ) {
			$wheres[] = '`db` = ?';
			$params[] = strval( $database );
		}
		if( $clientname ) {
			$wheres[] = '`clientname` = ?';
			$params[] = strval( $clientname );
		}
		if( $clientip ) {
			$wheres[] = '`clientip` = ?';
			$params[] = strval( $clientip );
		}
		if( $appversion ) {
			$wheres[] = '`appversion` = ?';
			$params[] = strval( $appversion );
		}
		if( $appserial ) {
			$wheres[] = '`appserial` = ?';
			$params[] = strval( $appserial );
		}

		$fields = array( 'ticketid' );
		$where = implode( ' AND ', $wheres );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['ticketid'] : false;
	}
	
	/**
	 * Retrieves the master ticket for a given (slave) ticket.
	 *
	 * @param string $ticket Ticket of the subapp (slave)
	 * @return string|bool The client ticket (master). EMPTY when given ticket has no master. FALSE when not found.
	 */
	public static function getMasterTicket( $ticket )
	{
		if( array_key_exists( $ticket, self::$ticketCache ) ) {
			return self::$ticketCache[ $ticket ][ 'masterticketid' ];
		}
		$fields = array( 'masterticketid' );
		$where = '`ticketid` = ?';
		$params = array( strval( $ticket ) );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['masterticketid'] : false;
	}
	
	/**
	 * Delete all expired tickets from the database. <br>
	 * Works indepently of current user or client application. <br>
	 * @deprecated Since 10.2.1. Use \BizTicket::deleteExpiredTicketsAndAffiliatedStructures instead.
	 * @throws BizException In case of database connection error.
	 */
	public static function DBpurgetickets()
	{
		require_once BASEDIR.'/server/bizclasses/BizTicket.class.php';
		$bizTicket = new BizTicket();
		$bizTicket->deleteExpiredTicketsAndAffiliatedStructures();
	}

	/**
	 * Returns the tickets that are expired.
	 *
	 * @return array Array with record id ('id' field) as key and ticket ('ticketid' field) as value.
	 * @throws  BizException In case of database connection error.
	 */
	static public function getExpiredTicketsIndexedById(): array
	{
		include_once( BASEDIR.'/server/utils/license/license.class.php' );
		$lic = new License();

		$fields = array( 'id', 'ticketid' );
		$where = '`expire` < ? OR `appname`= ?';
		$params = array( strval( date( 'Y-m-d\TH:i:s' ) ), strval( $lic->getInstallTicketID() ) );
		$rows = self::listRows( self::TABLENAME, '', '', $where, $fields, $params,
			null, null, null, null, false );

		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[ $row['id'] ] = $row['ticketid'];
		}
		return $map;
	}

	/**
	 * Removes all ticket records with the specified 'id'.
	 *
	 * @param array $ticketRowIds
	 * @throws BizException In case of database connection error.
	 */
	static public function deleteTicketsById( array $ticketRowIds ): void
	{
		if( $ticketRowIds ) {
			$dbDriver = DBDriverFactory::gen();
			$tickets = $dbDriver->tablename( self::TABLENAME );
			$where = self::addIntArrayToWhereClause( 'id', $ticketRowIds );
			if( $where ) {
				/* $success = */ self::deleteRows( self::TABLENAME, $where, array(), false );
			}
		}
	}
	
	/**
	 * Retrieves the whole ticket DB record (row) for a given ticket.
	 * Ticket is not validated.
	 *
	 * @param $ticket string   Unique ticket; gives user access to the system with given client application
	 * @return array|bool      Ticket row. Returns FALSE when (ticket) not found.
	 */
	public static function getTicket( $ticket )
	{
		$fields = '*';
		$where = '`ticketid` = ?';
		$params = array( $ticket );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row : false;
	}	
	
	/**
	 * Retrieves client application name that is logged in for a given ticket.
	 * Ticket is not validated.
	 *
	 * @param string $ticket   Unique ticket; gives user access to the system with given client application
	 * @return string|bool     Client application name, for example: Web, InDesign, InCopy, PhotoShop, Illustrator. Returns FALSE when (ticket) not found.
	 */
	public static function DBappticket( $ticket )
	{
		if( array_key_exists( $ticket, self::$ticketCache ) ) {
			return self::$ticketCache[ $ticket ][ 'appname' ];
		}
		$fields = array( 'appname' );
		$where = '`ticketid` = ?';
		$params = array( strval( $ticket ) );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['appname'] : false;
	}

	/**
	 * Retrieves originating client application name that is logged in for a given ticket.
	 *
	 * Normally the application name of the passed in ticket is returned. But there is an exception.
	 * When Smart Connection (SC) for InDesign Server (IDS) does login while the DPS tools are enabled, SC does another login.
	 * The first time login is for "InDesign Server" while the second time is for "Digital Publishing Tools InDesign Server".
	 * From then on, SC will use the first ticket and second ticket one by one to make sure both tickets won't expire and
	 * the DPS seat can not be taken away by another user. But for both tickets the originating application is "InDesign Server".
	 * The tickets are linked together by the `masterticketid`.
	 * Ticket is not validated.
	 *
	 * @since 10.1.6
	 * @param string $ticket  Unique ticket; gives user access to the system with given client application
	 * @return string  Client application name, for example: Web, InDesign, InCopy, PhotoShop, Illustrator.
	 *                 Returns empty string when (ticket) not found.
	 */
	public static function getOriginatingApplicationName( $ticket )
	{
		static $holdAppname = '';
		static $holdTicket = '';

		if ( $ticket == $holdTicket ) {
			return $holdAppname;
		}

		$dbDriver = DBDriverFactory::gen();
		$ticketsTable = $dbDriver->tablename( self::TABLENAME );

		$sql = 'SELECT tickets1.`appname` as `appname`, tickets2.`appname` as `masterappname` '.
			"FROM {$ticketsTable} tickets1 ".
			"LEFT JOIN {$ticketsTable} tickets2 ON (tickets2.`ticketid` = tickets1.`masterticketid` ) ".
			'WHERE tickets1.`ticketid` = ?';
		$params = array( strval( $ticket ) );
		$sth = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch( $sth );

		if( $row ) {
			$holdAppname = !is_null( $row['masterappname'] ) ? $row['masterappname'] : $row['appname'];
		} else {
			$holdAppname = '';
		}
		$holdTicket = $ticket;

		return $holdAppname;
	}

	/**
	 * Retrieves client application version that is logged in for a given ticket.
	 * Ticket is not validated.
	 *
	 * @param string $ticket   Unique ticket; gives user access to the system with given client application
	 * @return string|null     Client application version, for example: "v7.6.0 build 123". Returns NULL when (ticket) not found.
	 */
	public static function getClientAppVersion( $ticket )
	{
		if( array_key_exists( $ticket, self::$ticketCache ) ) {
			return self::$ticketCache[ $ticket ][ 'appversion' ];
		}
		$fields = array( 'appversion' );
		$where = '`ticketid` = ?';
		$params = array( strval( $ticket ) );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['appversion'] : null;
	}
	
	/**
	 * Retrieves user id (short name) that is logged in for a given ticket.
	 * Ticket is not validated.
	 *
	 * @param string $ticket   Unique ticket; gives user access to the system with given client application
	 * @return string|bool     User id (short name). Returns FALSE when (ticket) not found.
	 */
	public static function DBuserticket( $ticket )
	{
		if( array_key_exists( $ticket, self::$ticketCache ) ) {
			return self::$ticketCache[ $ticket ][ 'usr' ];
		}
		$fields = array( 'usr' );
		$where = '`ticketid` = ?';
		$params = array( strval( $ticket ) );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['usr'] : false;
	}

	/**
	 * Check if ticket exists in database and is not expired yet.
	 *
	 * This function must be called whenever the server is requested for this user to avoid the ticket from expiring.
	 * When the ticket is not expired, it postpones the current expiration time with a new (configured) time interval.
	 * When the ticket is expired, the user has lost his/her seat and has to re-logon to Enterprise to obtain a new one.
	 *
	 * @param string $ticket   Unique ticket; gives user access to the system with given client application
	 * @param array|null $ticketRow  The DB row from the smart_tickets table having the following fields resolved: usr, appname, appversion, expire, and masterticketid
	 * @param bool $extend     Since 10.2. Whether or not the ticket lifetime should be implicitly extended (when valid).
	 *                         Pass FALSE when e.g. frequently called and so the expensive DB update could be skipped.
	 * @return string|bool     Short user name, or FALSE when the ticket does not exist or has been expired.
	 * @throws BizException In case of database connection error.
	 */
	public static function checkTicket( $ticket, $ticketRow = null, $extend = true )
	{
		// Special treatment for background/async server job processing, for which no seat must be taken.
		if( self::$ServerJob && self::$ServerJob->TicketSeal == $ticket ) {
			return self::$ServerJob->ActingUser;
		}

		// when cached, assume ticket is still valid and does not need to extend
		if( array_key_exists( $ticket, self::$ticketCache ) ) {
			return self::$ticketCache[ $ticket ][ 'usr' ];
		}

		if( !$ticketRow || !is_array( $ticketRow ) ) { // check for array type because before 10.5.0 the 2nd function param used to be $service of type string
			// check ticket existence
			$fields = array( 'usr', 'appname', 'appversion', 'expire', 'masterticketid' );
			$where = '`ticketid` = ?';
			$params = array( strval( $ticket ) );
			$ticketRow = self::getRow( self::TABLENAME, $where, $fields, $params );
			if( !$ticketRow ) {
				return false;
			}
		}

		// check expiration
		$now = date( 'Y-m-d\TH:i:s' );
		$expire = trim( $ticketRow['expire'] );
		if( !empty( $expire ) && strncmp( $expire, $now, 19 ) < 0 ) {
			return false; // ticket expired
		}

		// cache ticket data
		self::$ticketCache[ $ticket ] = array(
			'usr' => $ticketRow['usr'],
			'appname' => $ticketRow['appname'],
			'appversion' => $ticketRow['appversion'],
			'masterticketid' => $ticketRow['masterticketid']
		);

		// user touched server, so postpone expiration
		if( $extend ) {
			$expire = self::_expire( $ticketRow['appname'] );
			$values = array( 'expire' => strval( $expire ) );
			$where = '`ticketid` = ?';
			$params = array( strval( $ticket ) );
			self::updateRow( self::TABLENAME, $values, $where, $params );
		}

		return trim( $ticketRow['usr'] );
	}

	/**
	 * Remove ticket from database.
	 *
	 * @param string $ticket Unique ticket; gives user access to the system with given client application
	 * @throws  BizException In case of database connection error.
	 */
	public static function DBendticket( $ticket )
	{
		if( $ticket ) {
			unset( self::$ticketCache[ $ticket ] );
			$where = '`ticketid` = ?';
			$params = array( strval( $ticket ) );
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}

	/**
	 * Remove tickets by user
	 * 
	 * @param string $user
	 * @throws BizException In case of database connection error.
	 */
	public static function DbPurgeTicketsByUser( $user )
	{
		if( $user ) {
			foreach( self::$ticketCache as $ticket => $ticketData ) {
				if( $ticketData['usr'] == $user ) {
					unset( self::$ticketCache[$ticket] );
				}
			}
			$where = '`usr` = ?';
			$params = array( strval( $user ) );
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}

	/**
	 * Resolves the user names and the client applications of online users, given a list of client IPs.
	 *
	 * @since 10.1.4
	 * @param string[] $clientIps
	 * @return array
	 * @throws BizException In case of database connection error.
	 */
	public static function resolveOnlineUsersFromClientIps( $clientIps )
	{
		$rows = array();
		if( $clientIps ) {
			$fields = array( 'ticketid', 'usr', 'clientip', 'appname', 'appversion' );
			$where = "`clientip` IN ('".implode( "','", $clientIps )."')";
			$rows = self::listRows( self::TABLENAME, null, null, $where, $fields );
		}
		return $rows;
	}

	/**
	 * Returns the Id of the user linked to the ticket.
	 *
	 * @since 10.2.1
	 * @param string $ticket
	 * @return int
	 * @throws BizException In case of database connection error.
	 */
	static public function getUserIdByTicket( string $ticket ): int
	{
		$dbdriver = DBDriverFactory::gen();
		$dbTickets = $dbdriver->tablename(self::TABLENAME);
		$dbUsers = $dbdriver->tablename('users');
		$userId = 0;

		$sql =   'SELECT users.`id` '.
					"FROM {$dbTickets} tickets ".
					"INNER JOIN {$dbUsers} users ON ( tickets.`usr` = users.`user` ) ".
					'WHERE tickets.`ticketid` = ?';
		$params = array( strval( $ticket ) );
		$sth = $dbdriver->query( $sql, $params );
		$row = $dbdriver->fetch( $sth );

		if ( $row ) {
			$userId = intval( $row['id'] );
		}

		return $userId;
	}

	/**
	 * Returns all tickets of the specified user.
	 *
	 * @since 10.2.1
	 * @param string $user Short user name.
	 * @return string[]
	 * @throws BizException In case of database connection error.
	 */
	static public function getTicketsByUser( string $user ): array
	{
		$tickets = array();
		if( $user ) {
			$where = '`usr` = ? ';
			$params = array( strval( $user ) );
			$rows = self::listRows( self::TABLENAME, '', '', $where, array( 'ticketid' ), $params );
			if( $rows ) {
				$tickets = array_map( function( $ticket ) { return $ticket['ticketid']; }, $rows);
			}
		}
		return $tickets;
	}

	// ------------------------------------------------------------------------
	// Called ASYNCHRONOUS
	// ------------------------------------------------------------------------

	/**
	 * @see ServerJobProcessor::bizBuddyCB().
	 * @param string $input The magical question
	 * @param object $caller The calling instance
	 * @param ServerJob $job
	 * @return string The magical answer
	 */
	final static public function dbBuddy( $input, $caller, $job )
	{ // L> Anti-hack: Function is made FINAL to block any subclass abusing this function!

		// Anti hack: Check if the calling ServerJob business class is ours.
		$salt = '$1$EntBiZlr$'; // salt for biz layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		$output = $caller && method_exists( $caller, 'dbBuddyCB' )
			? $caller->dbBuddyCB( $input ) : '';
		// L> Anti-hack: Be silent when caller does not exists or has no 'buddy' function (hide what we are doing at PHP logging!)

		$buddySecure = ( $output && $output == $public );
		if( !$buddySecure ) {
			echo __METHOD__.': Hey, I do not deal with service hijackers!<br/>'; // TODO: report
			return ''; // error
		}

		// Remember the ticket seal for checkTicket checksum later.
		self::$ServerJob = $job;

		// Anti hack: Return caller who we are
		$salt = '$1$EntDblYr$'; // salt for DB layer
		$private = crypt( $input, $salt );
		$public = substr( $private, strlen($salt) ); // remove salt (at prefix)
		return $public;
	}
}
