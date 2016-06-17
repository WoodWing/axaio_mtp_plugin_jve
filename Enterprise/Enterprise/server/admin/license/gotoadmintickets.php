<?php

	/*
		This page can be requested in case the admin user has been logged on, and the SCE server license limit
		has not been reached, but a client license limit has.
		Instead of the having the admin user log on again (which is done in case the SCE server license limit 
		has been reached), the admin user can go to the admintickets page immediately via this intermediate page.
		This page will prepare the session parameters that will be checked by the admintickets page.
	*/


	require_once dirname(__FILE__).'/../../../config/config.php';
	include_once( BASEDIR . '/server/utils/license/license.class.php' );
	require_once BASEDIR.'/server/secure.php';

	$lic = new License();

	//If no license installed yet: no reason to request this page
	$hasLicense = $lic->hasLicense();
	//Some license installed
	if ( !$hasLicense ) {
		print "Error: no license installed yet.";
		exit;
	}
		
	$productArr = $lic->getProductcodes();
	//Error, or no products: no reason to request this page
	if ( !$productArr ) {
		//Note that the link to this page should not be displayed in case NO products have been installed
		//So this error should not occur.
		print "Error: license error, or no products installed yet.";
		exit;
	}
	
	$clientAppUserLimit = false;
	foreach( $productArr as $productcode )
	{
		//Ignore main SCE Server (it is handled by another link in the status overview)
		if ( $productcode == PRODUCTKEY )
			continue;

		$errorMessage = '';
		$info = Array();
		$serial = $lic->getSerial( $productcode );
		$licenseStatus = $lic->getLicenseStatus( $productcode, $serial, $info, $errorMessage );
		if ( $licenseStatus == WW_LICENSE_OK_USERLIMIT )
			$clientAppUserLimit = true;
	}

	if ( !$clientAppUserLimit ) {
		//Note that the link to this page should not be displayed in case NO client license limit has been reached.
		//So this error should not occur.
		print "Error: no client license that has reached the limit.";
		exit;
	}

	//License OK, then the admin user should be able to logon normally
	require_once BASEDIR.'/server/secure.php';
	$ticket = checkSecure( 'admin' ); // Security: should be admin user
	if ( !$ticket ) {
		//Note that the link to this page should only be displayed in case of admin users.
		//So this error should not occur.
		print "Error: no administrator.";
		exit;
	}

	//In case of error, there is no 'secure' admin session (the user can't logon!)
	//But these admin pages should be able to be fetched, 
	//and need to verify that only admin users are requesting them

	/* set the cache limiter to 'private' */
	session_cache_limiter('private');
//	$cache_limiter = session_cache_limiter();
	
	/* set the cache expire to 15 minutes */
	session_cache_expire(15);
//	$cache_expire = session_cache_expire();

	$sessionname = 'ww_userlimit_admin_session';
	session_name( $sessionname );
	session_start();
	global $globUser;
	$user = $globUser;
	$_SESSION[ 'adminUser' ] = $user;
	$_SESSION[ 'hash' ] = md5( $user . "bla" );
	$_SESSION[ 'start' ] = time();

//	echo "The cache limiter is now set to $cache_limiter<br />";
//	echo "The cached session pages expire after $cache_expire minutes";

	header('Location: '.SERVERURL_ROOT.INETROOT.'/server/admin/license/admintickets.php?adminUser='.$user.'&' . $sessionname . '=' . session_id() );
	//After setting the header, always quit: don't send extra data to the browser
	exit;
		
?>