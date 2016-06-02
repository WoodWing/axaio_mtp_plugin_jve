<?php
	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once BASEDIR.'/server/secure.php';

	//This is used by the 'retry reclaim' section of getlicense.php

	//When the customer waits for a few minutes or hours for the approval email,
	//the session would be lost (expiration after 1 hour?!)
	//So keep the session alive by asking for an invisible image via a php page

	$ticket = checkSecure( 'admin' ); // Security: should be admin user

	Header( "Content-type: image/gif");
	readfile( 'images/progress.gif' );
?>