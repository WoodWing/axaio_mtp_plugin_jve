<?php

//This module defines the URLs of the SmartReg server
//These definitions might be overruled in exceptional cases in config.php

if ( !defined('WWREGSERVERPORT') ) {
	define( 'WWREGSERVERPORT', '80' );
}
if ( !defined('WWREGSERVER') ) {
	if( WWREGSERVERPORT != '80' ) {
		define( 'WWREGSERVER', 'www.woodwing.net:' . WWREGSERVERPORT );
	} else {
		define( 'WWREGSERVER', 'www.woodwing.net' );
	}
}
if ( !defined('ACTIVATEURL') ) {
	if ( !defined('USEALTERNATIVEACTIVATEURL') ) {
		define( 'ACTIVATEURL', "http://" . WWREGSERVER . "/smartreg/SCEserver/registerSCE.php" );
	} else {
		define( 'ACTIVATEURL', "http://" . WWREGSERVER . "/smartreg/SCEserver/registerSCE2.php" );
	}
}
if ( !defined('SUPPORTURL') ) {
	define( 'SUPPORTURL', 'http://' . WWREGSERVER . '/smartreg/SCEserver/support.php' );
}
if ( !defined('TESTIMAGEURL') ) {
	define( 'TESTIMAGEURL', "http://" . WWREGSERVER . "/smartreg/SCEserver/images/areyouthere.gif" );
}
if ( !defined('CONFIRMIMAGEURL') ) {
	define( 'CONFIRMIMAGEURL', "http://" . WWREGSERVER . "/smartreg/confirm.php" );
}
if ( !defined('PINGURL') ) {
	define( 'PINGURL', "http://" . WWREGSERVER . "/smartreg/ping.php" );
}
define( 'WWREGCONNECTTIMEOUT', '5' );
define( 'WWREGTIMEOUT', '10' );