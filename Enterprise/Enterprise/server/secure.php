<?php

// Implements security model for web applications.
// Maintains cookie of web user and checks if user has access rights.

require_once BASEDIR.'/server/serverinfo.php';

// Set user language
global $sLanguage_code;
require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
$sLanguage_code = BizUser::validUserLanguage( getOptionalCookie('language') );

define ('COOKIETIMEOUT', 86400); // =3600*24
define ('LOGINPHP', SERVERURL_ROOT.INETROOT.'/server/apps/login.php');		// must be absolute (for apps in subdirs)
define ('NORIGHT', SERVERURL_ROOT.INETROOT.'/server/apps/noright.php');

function checkSecure($app = null, $userPwdExpir = null, $redir=true, $ticket=null)
{
	global $ispubladmin;
	global $isadmin;
	global $globUser;

	$dbDriver = DBDriverFactory::gen();

	if( empty($userPwdExpir) ) {
		$ticket = $ticket != null ? $ticket : getLogCookie('ticket',$redir);
		try {
			$user = BizSession::checkTicket( $ticket );
		} catch( BizException $e ) {
			$user = '';

			// The admin user might have prepared a clean database with an empty table space.
			// In that case, we detect and redirect to the dbadmin.php module to setup the DB.
			try {
				if( !$dbDriver->tableExists( 'config' ) ) { // Just pick the smart_config table.
					throw new BizException( 'ERR_COULD_NOT_CONNECT_TO_DATEBASE', 'Server', '' );
				}
			} catch( BizException $e ) {
				echo $e->getMessage() . '<br/>';
				echo 'Please check if your database is running.<br/>';
				echo 'Or, click <a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/dbadmin.php'.'">here</a> to check your database setup.<br/>';
				exit();
			}
		}
	} else {
		$user = $userPwdExpir;
	}

	// no user means: invalid ticket (eg logged out) or expired ticket
	if (!$user) {
		if( $redir ) {
			header( 'Location: '.LOGINPHP.'?redir=true' );
		} else {
			header( 'Location: '.LOGINPHP );
		}
		exit();
	}

	$globUser = $user;

	$ispubladmin = false;
	$isadmin = hasRights( $dbDriver, $user, $app );
	$ispubladmin = publRights( $dbDriver, $user );

	// When password expired, we continue without ticket to allow user to change password
	if( !empty($userPwdExpir) ) {
		return ''; 
	}

	switch ($app) {
		case null:
			return $ticket;
		case 'admin':
			if ($isadmin)
				return $ticket;
			break;
		case 'publadmin':
			if ($isadmin)
				return $ticket;
			if ($ispubladmin)
				return $ticket;
			break;
	}
	// no access
	header("Location: ".NORIGHT);
	exit();
}

function getOptionalCookie( $key )
{
	if(array_key_exists($key, $_COOKIE)){
		return $_COOKIE[$key]; // cookie may not exist
	}
	return null;
}

function getLogCookie( $cookie, $redir=true )
{
	@$key = getOptionalCookie($cookie);
	if (!$key) {
		if( $redir ) {
			header( 'Location: '.LOGINPHP.'?redir=true' );
		} else {
			header( 'Location: '.LOGINPHP );
		}
		exit();
	}

	// refresh cookie
	setLogCookie($cookie, $key);

	return $key;
}

function setLogCookie( $cookie, $key )
{
	$tm = time()+COOKIETIMEOUT;
	setcookie( $cookie, $key, $tm, INETROOT, null, COOKIES_OVER_SECURE_CONNECTIONS_ONLY, true );
}

function webauthorization($feature)
{
	global $globUser;

	$dbDriver = DBDriverFactory::gen();
	$sth = getauthorizations( $dbDriver, $globUser, $feature );
	$row = $dbDriver->fetch( $sth );
	if (!$row) {
		header("Location: index.php");
		exit();
	}
}

/**
 * Query user authorizations.
 *
 * @param WW_DbDrivers_DriverBase $dbh
 * @param string $userShort
 * @param int $feature The feature id to query authorizations for. Zero (0) to query authorizations for all features.
 * @return resource|null DB handle that can be used to fetch results. Null when SQL failed.
 */
function getauthorizations( $dbh, $userShort, $feature = 0 )
{
	$dbu = $dbh->tablename( 'users' );
	$dbx = $dbh->tablename( 'usrgrp' );
	$dba = $dbh->tablename( 'authorizations' );
	$dbpv = $dbh->tablename( 'profilefeatures' );

	$sql =
		'SELECT pv.`feature` AS `feature` '.
		"FROM $dbu u, $dbx x, $dba a, $dbpv pv ".
		'WHERE u.`id` = x.`usrid` AND x.`grpid` = a.`grpid` AND a.`profile` = pv.`profile` AND u.`user` = ? ';
	$params = array( strval( $userShort ) );
	if( $feature ) {
		$sql .= " AND pv.`feature` = ?";
		$params[] = intval( $feature );
		$sql = $dbh->limitquery( $sql, 0, 1 );
	} else {
		// GROUP BY is needed when no feature is selected and so multiple features could be returned.
		// GROUP BY has a performance drawback. BZ#22116.
		$sql .= " GROUP BY pv.`feature`";
	}
	$sth = $dbh->query( $sql, $params );

	return $sth;
}

/**
 * Tells whether or not a user has system administration rights.
 *
 * @param WW_DbDrivers_DriverBase|null $dbdr Not used. Deprecated since 10.5.0.
 * @param string $userShort
 * @param string|null $app Not used. Deprecated since 10.5.0.
 * @return bool
 */
function hasRights( $dbdr, $userShort, $app=null )
{
	require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
	$select = array( 'u' => array( 'disable' ), 'g' => array( 'admin' ) );
	$from = array( 'u' => 'users', 'x' => 'usrgrp', 'g' => 'groups' );
	$where = 'u.`user` = ? AND u.`id` = x.`usrid` AND g.`id` = x.`grpid` AND u.`disable` = ? AND g.`admin` != ?';
	$params = array( strval( $userShort ), '', '' );
	return (bool) DBBase::getRow( $from, $where, $select, $params );
}

/**
 * Tells whether or not a user has brand administration rights.
 *
 * @param WW_DbDrivers_DriverBase|null $dbdr Not used. Deprecated since 10.5.0.
 * @param string $user Short name of user
 * @return bool
 */
function publRights( $dbdr, $user )
{
	global $adminforpubl;
	global $isadmin;

	// If user has system administration rights, he/she is implicitly brand admin for all brands.
	if( is_null( $isadmin ) ) {
		$isadmin = hasRights( null, $user );
	}
	if( $isadmin ) {
		return true;
	}

	// Retrieve all brand ids for which the user is admin for.
	require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
	$select = array( 'pa' => array( 'publ' => 'publication' ) );
	$from = array( 'u' => 'users', 'x' => 'usrgrp', 'g' => 'groups', 'pa' => 'publadmin' );
	$where = 'u.`user` = ? AND u.`id` = x.`usrid` AND g.`id` = x.`grpid` AND g.`id` = pa.`grpid` AND u.`disable` = ? ';
	$params = array( strval( $user ), '' );
	$rows = DBBase::listRows( $from, '', '', $where, $select, $params );

	// Compose global list of brand ids the user is admin for.
	$adminforpubl = array();
	if( $rows ) foreach( $rows as $row ) {
		$adminforpubl[] = $row['publ'];
	}
	return sizeof( $adminforpubl ) > 0;
}

/**
 * Validate whether the user passed into the publRights() function is brand admin for the given publication.
 *
 * @param integer $publicationId
 * @param bool $redirectAndExitWhenAccessDenied Whether to exit and redirect to the Access Denied page in case no access.
 * @return bool When $redirectAndExitWhenAccessDenied is FALSE, the return value indicates whether the user has access.
 */
function checkPublAdmin( $publicationId, $redirectAndExitWhenAccessDenied = true )
{
	global $adminforpubl;
	global $isadmin;

	// If user has system administration rights, he/she is implicitly brand admin for all brands.
	if( $isadmin ) {
		return true;
	}

	$ret = in_array( $publicationId, $adminforpubl );

	if( $redirectAndExitWhenAccessDenied && !$ret ) {
		header( "Location: ".NORIGHT );
		exit();
	}

	return $ret;
}

/**
 * Return the user short name and ticket expiration for a given ticket.
 *
 * @deprecated 10.5.0
 * @param WW_DbDrivers_DriverBase $dbdr
 * @param string $ticket
 * @param string $expire Returns ticket expiration in ISO datetime notation.
 * @return string|null
 */
function getuser( $dbdr, $ticket, &$expire )
{
	LogHandler::log( __METHOD__, 'DEPRECATED',
		'This function is no longer supported since 10.5.0 and may be removed in future versions.'.
		'Please use DBTicket::DBuserticket() instead.'
	);
	if($dbdr == null){
		$dbdr = DBDriverFactory::gen();
	}
	$db = $dbdr->tablename('tickets');
	$sql = "SELECT `usr`, `expire` from $db where `ticketid`='$ticket'";
	$sth = $dbdr->query($sql);
	if (!$sth) return null;
	$row = $dbdr->fetch($sth);
	if (!$row) return null;

	$expire = trim($row['expire']); // return ticket expiration
	return $row['usr'];
}

/**
 * WoodWing wrapper function of the PHP crypt function, providing salt when not given.
 * Specific salt is given to force selection of the encryption algorithm based on different hash type.
 * Until version 9.1.0 Standard DES hash was used to encrypt password,
 * but from version 9.1.0 onwards, more secure SHA-512 hash will be use.
 * By calling ww_crypt instead of crypt we keep passwords compatible.
 * See PHP documentation of the crypt() function for details of parameters and return value.
 *
 * @param string $str Password string
 * @param string $salt Optional salt string
 * @param boolean $sha512 Indicator whether to use SHA512 hash type
 * @return string The hashed string
 */
function ww_crypt( $str, $salt=null, $sha512 = null )
{
	if( is_null($salt) ) {
		$seed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		if( $sha512 && (defined('CRYPT_SHA512') && CRYPT_SHA512) ) { // Check if PHP Support SHA-512 hash type
			// Salt for SHA-512 hash with a sixteen character salt prefixed with $6$
			// For more informtion on SHA-512 implementation, go to, http://www.akkadia.org/drepper/SHA-crypt.txt
			for ( $i = 0; $i < 16; $i++ ) {
				$salt .= $seed[mt_rand(0,61)];
			}
			// The default number of rounds is 5000, there is a minimum of 1000 and a maximum of 999,999,999.
			// The more rounds are performed the higher the CPU requirements are.
			$rounds = '5000';
			$salt = '$6$rounds=' . $rounds . '$' . $salt;
		} else {
			// Salt for Standard DES consist of two chars out of the following options:
			$salt = $seed[mt_rand(0,61)];
			$salt .= $seed[mt_rand(0,61)];
		}

	}
	return crypt( $str, $salt );
}

// for debug purposes
function dump($subject, $sHeader="Dump")
{
	echo "<h1>$sHeader</h1>";
	echo "<pre>";
	print_r($subject);
	echo "</pre>";
}