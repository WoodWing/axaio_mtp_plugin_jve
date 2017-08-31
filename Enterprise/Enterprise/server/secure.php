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


	try {
		$dbDriver = DBDriverFactory::gen();
		// The admin user might have prepared a clean database with an empty table space.
		// In that case, we detect and redirect to the dbadmin.php module to setup the DB.
		if( !$dbDriver->tableExists( 'config' ) ) { // Just pick the smart_config table.
			throw new BizException( 'ERR_COULD_NOT_CONNECT_TO_DATEBASE', 'Server', '' );
		}
	} catch( BizException $e ) {
		echo $e->getMessage() . '<br/>';
		echo 'Please check if your database is running.<br/>';
		echo 'Or, click <a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/dbadmin.php'.'">here</a> to check your database setup.<br/>';
		exit();
	}

	if( empty($userPwdExpir) ) {
		$ticket = $ticket != null ? $ticket : getLogCookie('ticket',$redir);
		try {
			$user = BizSession::checkTicket( $ticket );
		} catch( BizException $e ) {
			$user = '';
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
	setcookie( $cookie, $key, $tm, INETROOT );
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

function getauthorizations($dbh, $user, $feature = 0)
{
	$dbu = $dbh->tablename("users");
	$dbx = $dbh->tablename("usrgrp");
	$dba = $dbh->tablename("authorizations");
	$dbpv = $dbh->tablename("profilefeatures");

	$user = $dbh->toDBString($user);

	$sql = "select pv.`feature` as `feature` from $dbu u, $dbx x, $dba a, $dbpv pv where u.`id` = x.`usrid` and x.`grpid` = a.`grpid` and a.`profile` = pv.`profile` and u.`user` = '$user' ";
	if ($feature) $sql .= " and pv.`feature` = $feature";
	if (!$feature) {
		$sql .= " group by pv.`feature`"; // Group by is needed when more than one feature is selected. Group by has a performance drawback. BZ#22116.
	} else {
		$sql = $dbh->limitquery($sql, 0, 1);
	}
	$sth = $dbh->query($sql);

	return $sth;
}

function hasRights($dbdr, $user, $app=null)
{
	$db1 = $dbdr->tablename("users");
	$db2 = $dbdr->tablename("usrgrp");
	$db3 = $dbdr->tablename("groups");

	$user = $dbdr->toDBString($user);

	$sql = "select u.`disable` as `disable`, g.`admin` as `admin` from $db1 u, $db2 x, $db3 g where u.`user` = '$user' and u.`id` = x.`usrid` and g.`id` = x.`grpid`";

	$sth = $dbdr->query($sql);
	if (!$sth) return false;

	// check each row
	while( ($row = $dbdr->fetch($sth)) ) {
		if (trim($row['disable']) == '' && trim($row['admin']) != '') return true;
	}
	return false;
}

function publRights($dbdr, $user)
{
	global $adminforpubl;
	$isadmin = hasRights( $dbdr, $user);
	if ($isadmin) return true;			// check only if user is not global admin

	$db1 = $dbdr->tablename("users");
	$db2 = $dbdr->tablename("usrgrp");
	$db3 = $dbdr->tablename("groups");
	$db4 = $dbdr->tablename("publadmin");

	$user = $dbdr->toDBString($user);

	$sql = "select u.`disable` as `disable`, pa.`publication` as `publ` from $db1 u, $db2 x, $db3 g, $db4 pa where u.`user` = '$user' and u.`id` = x.`usrid` and g.`id` = x.`grpid` and g.`id` = pa.`grpid`";

	$sth = $dbdr->query($sql);
	if (!$sth) return false;

	// check each row
	$adminforpubl = array();
	while( ($row = $dbdr->fetch($sth)) ) {
		if (trim($row['disable']) == '') {
			$adminforpubl[] = $row['publ'];
		}
	}
	return sizeof($adminforpubl);
}

function checkPublAdmin($publ, $keepaway = true)
{
	global $adminforpubl;
	global $isadmin;

	if ($isadmin) return true;			// check only if user is not global admin

	$ret = in_array($publ, $adminforpubl);

	if ($keepaway && !$ret) {
		header("Location: ".NORIGHT);
		exit();
	}

	return $ret;
}

function getuser( $dbdr, $ticket, &$expire )
{
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