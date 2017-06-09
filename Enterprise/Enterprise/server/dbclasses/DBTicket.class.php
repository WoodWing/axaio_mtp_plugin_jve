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
	 * Array that holds the purged ticket ids
	 * 
	 * @var array
	 */
	private static $purgedTickets = array();
	
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
	 */
	public static function genTicket( $orguser, $shortuser, $server, $clientname, 
		$appname, $appversion, $appserial, $appproductcode, &$usageLimitReached, 
		&$errorMessage, $masterTicket = '' )
	{
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientip = WW_Utils_UrlUtils::getClientIP();
		$ticketid = '';

		// purge old tickets
		self::DBpurgetickets();

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
	 * Calculates the new session expiration time for given client. <br>
	 * For 'Web' applications the EXPIREWEB option is used. For others EXPIREDEFAULT is used. <br>
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
        if( $app == 'Web' || $app == 'WebEditor' ) {
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
	 */
	public static function checkUniqueTicket($ticket)
	{
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		$sql = 'SELECT * FROM '.$db.' WHERE `ticketid`=\''.$ticket.'\'';
		$sth = $dbdriver->query($sql);
		$result = $dbdriver->fetch($sth);
		if( $result ) {
			return false;
		}
		return true;
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
	 */
	public static function DBnewticket( $ticketid, $usr, $database, $clientname, $clientip, 
		$appname, $appversion, $appserial, $appproductcode, &$usageLimitReached, 
		&$errorMessage, $masterTicket )
	{
		$dbdriver = DBDriverFactory::gen();

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

		$db_tickets = $dbdriver->tablename(self::TABLENAME);
		$installTicketID = $lic->getInstallTicketID();

		if ( self::licenseCheckIsNeeded( $usr, $installTicketID, $ticketid, $appserial, $appname ) ) {
			$errorMessage = '';
			$info = Array();

			//Check the license and number of concurrent users for this application
			//On success, use '$now' as last valid logon time
			//Be sure to use this $now also to insert a ticket in the database below!
			$licenseStatus = $lic->getLicenseStatus( $appproductcode, $appserial, $info, $errorMessage, $now, $usr, $appname );
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
			$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $appserialSCE, $info, $errorMessage, $now, $usr, $appname );
			if ( !$lic->canLogonStatus( $licenseStatus ) )
			{
				$usageLimitReached = 
					(($licenseStatus == WW_LICENSE_OK_USERLIMIT ) && $info[ 'usageLimitReached' ]);
				if ( $tmpErrorMessage && ($errorMessage != $tmpErrorMessage ))
					$errorMessage = $tmpErrorMessage . " " . $errorMessage;
				return false;
			}
		}

		$usr = $dbdriver->toDBString($usr);
		$database = $dbdriver->toDBString($database);
		$clientname = $dbdriver->toDBString($clientname);
		$clientip = $dbdriver->toDBString($clientip);
		$appname = $dbdriver->toDBString($appname);
		$appversion = $dbdriver->toDBString($appversion);
		$appserial = $dbdriver->toDBString($appserial);

		$expire = self::_expire($appname);

		$sql = "INSERT INTO $db_tickets (`ticketid`, `usr`, `db`, `clientname`, `clientip`, ".
					"`appname`, `appversion`, `appserial`, `appproductcode`, `expire`, ".
					"`logon`, `masterticketid`) VALUES ".
				"('$ticketid', '$usr', '$database', '$clientname', '$clientip', ".
					"'$appname', '$appversion', '$appserial', '$appproductcode', '$expire', ".
					"'$now', '$masterTicket')";
		$sql = $dbdriver->autoincrement($sql);
		$sth = $dbdriver->query($sql);

		if ( !$sth )
		{
			$errorMessage = $dbdriver->error();
			return false;
		}

		$db_users = $dbdriver->tablename("users");
		$sql = "UPDATE $db_users SET `lastlogondate`='$now' WHERE `user`='$usr'";
		$sth = $dbdriver->query($sql);
		if ( !$sth )
		{
			$errorMessage = $dbdriver->error();
			return false;
		}

		return true;
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
			if( rand( 0, 40 ) === 0 ) {
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
	 */
	public static function DBfindticket( $usr, $database, $clientname, $clientip, $appname, $appversion, $appserial )
	{
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		LogHandler::Log('dbticket', 'DEBUG', $usr.' '.$database.' '.$clientname.' '.$clientip.' '.$appname.' '.$appversion.' '.$appserial);

		$usr = $dbdriver->toDBString($usr);
		$database =	$dbdriver->toDBString($database);
		$clientname = $dbdriver->toDBString($clientname);
		$clientip =	$dbdriver->toDBString($clientip);
		$appname =	$dbdriver->toDBString($appname);
		$appversion = $dbdriver->toDBString($appversion);
		$appserial = $dbdriver->toDBString($appserial);

		$sql  = "SELECT * FROM $db WHERE `usr`='$usr' ";
		$sql .= "AND `appname`= '$appname' ";
		$sql .= $database ? "AND `db`= '$database' " : '';
		$sql .= $clientname ? "AND `clientname`= '$clientname' " : '';
		$sql .= $clientip ? "AND `clientip`= '$clientip' " : '';
	 	$sql .= $appversion ? " AND `appversion`= '$appversion' " : '';
		$sql .= $appserial ? "AND `appserial`= '$appserial' " : '';
		$sth = $dbdriver->query($sql);
		if (!$sth) return false;
		$row = $dbdriver->fetch($sth);
		if (!$row) return false;
		return $row['ticketid'];
	}
	
	/**
	 * Retrieves the master ticket for a given (slave) ticket.
	 *
	 * @param string $ticket Ticket of the subapp (slave)
	 * @return string|bool The client ticket (master). EMPTY when given ticket has no master. FALSE when not found.
	 */
	public static function getMasterTicket( $ticket )
	{
		$where = '`ticketid` = ?';
		$params = array( $ticket );
		$fields = array( 'masterticketid' );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['masterticketid'] : false;
	}
	
	/**
	 * Delete all expired tickets from the database. <br>
	 * Works indepently of current user or client application. <br>
	 *
	 * @return resource       Database connection or FALSE on error
	 */
	public static function DBpurgetickets()
	{
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		$expire = date('Y-m-d\TH:i:s');

		include_once( BASEDIR . '/server/utils/license/license.class.php' );
		$lic = new License();
		$installTicketID = $lic->getInstallTicketID(); 

		// Get all the tickets that need to be purged
		$sql = "SELECT `id`, `ticketid` FROM $db WHERE `expire` < '$expire' OR `appname`='$installTicketID'";
		$sth = $dbdriver->query($sql, array(), null, false); //Don't write in log
		
		$tickets = array();
		while( ( $row = $dbdriver->fetch( $sth ) ) ) {
			$tickets[$row['id']] = $row['ticketid'];
		}
		
		// Remember the tickets for later reference in this session
		self::$purgedTickets = array_values($tickets);
		
		// Delete the expired tickets from the database
		if( $tickets ) {
			$purgeRowIds = implode(',', array_keys($tickets));
			$sql = "DELETE FROM $db WHERE `id` IN ($purgeRowIds)";
			$sth = $dbdriver->query($sql, array(), null, false); //Don't write in log
		}

		return $sth;
	}
	
	/**
	 * Returns the tickets that are purged
	 *
	 * @return array
	 */
	public static function getPurgedTickets()
	{
		return self::$purgedTickets;
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
		$where = '`ticketid` = ?';
		$params = array( $ticket );
		$fields = '*';
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
		$where = '`ticketid` = ?';
		$params = array( $ticket );
		$fields = array( 'appname' );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['appname'] : false;
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
		$where = '`ticketid` = ?';
		$params = array( $ticket );
		$fields = array( 'appversion' );
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
		$where = '`ticketid` = ?';
		$params = array( $ticket );
		$fields = array( 'usr' );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params );
		return $row ? $row['usr'] : false;
	}
	
	/**
	 * Check if ticket exists in database and is not expired yet.
	 * Since function must be called whenever user 'touched' the server.
	 * When not expired, it postpones the current expiration time with new (configured) time interval. <br>
	 *
	 * @param string $ticket   Unique ticket; gives user access to the system with given client application
	 * @param string $service  Not used
	 * @return string          User id (short name) or FALSE when ticket not exists or expired.
	 */
	public static function checkTicket( $ticket, $service = '' )
	{
		// Special treatment for background/async server job processing, for which no seat must be taken.
		if( self::$ServerJob && self::$ServerJob->TicketSeal == $ticket ) {
			return self::$ServerJob->ActingUser;
		}
		
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		
		// check ticket existence
		$params = array( $ticket );
		$sql = "SELECT `usr`, `appname`, `expire` FROM $db WHERE `ticketid` = ?";
		$sth = $dbdriver->query( $sql, $params );
		if (!$sth) return false;
		$row = $dbdriver->fetch($sth);
		if (!$row) return false;

		// check expiration
		$now = date('Y-m-d\TH:i:s');
		$expire = trim($row['expire']);
		if( !empty($expire) && strncmp( $expire, $now, 19 ) < 0 ) {
			return false; // ticket expired
		}

		$user = $row['usr'];
		$user = trim($user);

		// user touched server, so postpone expiration
		$expire = self::_expire($row['appname']);
		$params = array( $expire, $ticket );
		$sql = "UPDATE $db SET `expire` = ? WHERE `ticketid` = ?";
		$sth = $dbdriver->query( $sql, $params );

		// Auto-postpone WebEditor ticket when Web(App) goes along, or vice versa(!).
		// This is to avoid any logon dialogs while user works a while at one of them and then starts using the other one again.
		if( ($otherTicket  = self::getOtherTicket($row['appname'],$user)) ) {
			$params = array( $expire, $otherTicket );
			$sql = "UPDATE $db SET `expire` = ? WHERE `ticketid` = ?";
			$sth = $dbdriver->query( $sql, $params );
		}
		
		// do some automatic logging
		//if ($service) $this->DBlog($user, $service); // EKL: let's not do this since it gives duplicate messages with empty data!

		return $user;
	}
	
	/**
	 * Remove ticket from database.
	 *
	 * @param string $ticket   Unique ticket; gives user access to the system with given client application
	 * @return resource        Database connection or FALSE on error
	 */
	public static function DBendticket( $ticket )
	{
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);

		// Auto-remove WebEditor ticket on Web(App) logoff (but NOT* vice versa!)
		// This is to avoid any pending web tickets when user explicitly logs off.
		// * Note that WebEditor supports silent logon based on Web(App) ticket and they logoff on any doc close.
		if( ($appname = self::DBappticket( $ticket )) && $appname == 'Web' ) {
			if( ($user = self::DBuserticket( $ticket )) ) {
				if( ($otherTicket = self::getOtherTicket($appname,$user)) ) {
					$sql = "DELETE FROM $db WHERE `ticketid` = '$otherTicket'";
					$dbdriver->query($sql);
				}
			}
		}
		// remove the given ticket
		$sql = "DELETE FROM $db WHERE `ticketid` = '$ticket'";
		$sth = $dbdriver->query($sql);
		return $sth;
	}

	/**
	 * Returns WebEditor ticket when Web(App) is passed or vice versa.
	 *
	 * @param string $appname
	 * @param string $user
	 * @return string The other ticket.
	 */
	private static function getOtherTicket( $appname, $user )
	{
		$dbdriver = DBDriverFactory::gen();
		$db = $dbdriver->tablename(self::TABLENAME);
		
		if( $appname == 'Web' ) {
			$otherApp = 'WebEditor';
		} else if( $appname == 'WebEditor' ) {
			$otherApp = 'Web';
		} else {
			$otherApp = null;
		}
		if( $otherApp ) {
			$sql = "SELECT `ticketid` FROM $db WHERE `usr`='$user' AND `appname`='$otherApp'";
			$sth = $dbdriver->query($sql);
			$row = $sth ? $dbdriver->fetch($sth) : null;
			$otherTicket = $row ? $row['ticketid'] : null;
		} else {
			$otherTicket = null;
		}
		return $otherTicket;
	}

	/**
	 * Remove tickets by user
	 * 
	 * @param string $user
	 */
	public static function DbPurgeTicketsByUser( $user = null )
	{
		if( $user ) {
			$where = '`usr` = ?';
			$params = array( $user );
			self::deleteRows( self::TABLENAME, $where, $params );
		}
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
