<?php
/**
 * Configuration options for the Speed Test.
 *
 * @package     ProxyForSC
 * @subpackage  Config
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

// PROXYSERVER_URL:
//    The URL to the web root folder of the Proxy Server.
//    End with a separator and use forward slashes.
//    Default value: 'http://127.0.0.1/ProxyForSC/'
//
define( 'PROXYSERVER_URL', 'http://127.0.0.1/ProxyForSC/' );

// ENTERPRISE_URL:
//    The URL to the Enterprise Server root folder.
//    End with a separator and use forward slashes.
//    Default value: 'http://127.0.0.1/Enterprise/'
//
define ('ENTERPRISE_URL', 'http://127.0.0.1/Enterprise/');

// SPEEDTEST_USERNAME:
//    The user name to be used by the Speed Test to login at Enterprise Server.
//    Default value: 'woodwing'
//
define( 'SPEEDTEST_USERNAME', 'woodwing' );

// SPEEDTEST_PASSWORD:
//    The user password to be used by the Speed Test to login at Enterprise Server.
//    Default value: 'ww'
//
define( 'SPEEDTEST_PASSWORD', 'ww' );

// SPEEDTEST_BRANDNAME:
//    The Brand name to be used by the Speed Test to create objects in Enterprise Server.
//    Default value: 'WW News'
//
define( 'SPEEDTEST_BRANDNAME', 'WW News' );

// SPEEDTEST_PUBCHANNELNAME:
//    The Publication Channel name to be used by the Speed Test to create objects in Enterprise Server.
//    Default value: 'Print'
//
define( 'SPEEDTEST_PUBCHANNELNAME', 'Print' );

// SPEEDTEST_ISSUENAME:
//    The Issue name to be used by the Speed Test to create objects in Enterprise Server.
//    Default value: '2nd Issue'
//
define( 'SPEEDTEST_ISSUENAME', '2nd Issue' );

// -------------------------------------------------------------------------------------------------
// Logging and Profiling
// -------------------------------------------------------------------------------------------------

// OUTPUTDIRECTORY:
//    The path where to write log files into. Leave empty to disable logging. Default value: ''.
//    End with a separator and use forward slashes, for example: 'c:/logging/speedtest/'
//
define ('OUTPUTDIRECTORY', '');

// DEBUGLEVELS:
//    Enables server logging. Possible values: NONE, ERROR, WARN, INFO, DEBUG. Default value: 'NONE'.
//    The amount of information gets richer from left to right; NONE disables logging, while 
//    DEBUG gives most details. The option allows to specify a log level per client IP. For the 
//    keys, fill in the client IPs  and for the values, fill in the  debug levels. There must be 
//    one item named 'default' which is used for all clients that are not explicitly configured.
//
//    Note that fatal errors caught by PHP are logged in the php.log file. When there are 
//    no (catchable) fatal errors, this file does not exist. 
//
define ('DEBUGLEVELS', serialize( array(
	// CLIENT IP => DEBUGLEVEL
	'default' => 'NONE', // 'default' entry is mandatory
)));

// LOGFILE_FORMAT:
//    The log file format. There are two formats supported: 'html' and 'plain'. Default value: 'html'.
//    The HTML format is better readable in web browser. The plain text format can be easier 
//    searched through using command line tools (like grep).
//
define ('LOGFILE_FORMAT', 'html');

// -------------------------------------------------------------------------------------------------
// System internals ===> DO NOT MAKE CHANGES TO THIS SECTION
// -------------------------------------------------------------------------------------------------

// Operating system file path of the ProxyForSc folder.
define( 'BASEDIR', dirname(dirname(__FILE__)) ); // DO NOT end with a separator, use forward slashes

// Suppress errors in output.
ini_set('display_errors', '0'); // Debug option; should ALWAYS be zero for production!
if( defined('OUTPUTDIRECTORY') && OUTPUTDIRECTORY != '' ) {
	ini_set('log_errors', '1'); // use 'error_log'
	ini_set('error_log', OUTPUTDIRECTORY.'php.log'); // Log PHP Errors, Warnings and Noticed to file
}

// Determine attributes of our own URL.
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
	define ('SERVERURL_PROTOCOL', 'https://' );
	if($_SERVER['SERVER_PORT']!='443') {
		define ('SERVERURL_PORT', ':' . $_SERVER['SERVER_PORT'] );
	} else {
		define ('SERVERURL_PORT', '' );
	}
} else {
	define ('SERVERURL_PROTOCOL', 'http://' );
	if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!='80') {
		define ('SERVERURL_PORT', ':' . $_SERVER['SERVER_PORT'] );
	} else {
		define ('SERVERURL_PORT', '' );
	}
}
if( !defined('SERVERURL_ROOT') ) { // allow overrule
	if (isset($_SERVER['HTTP_HOST'])) {
		define ('SERVERURL_ROOT', SERVERURL_PROTOCOL.$_SERVER['HTTP_HOST'] );
	} else {
		$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		define ('SERVERURL_ROOT', $serverName.SERVERURL_PORT );
	}
}

// Determine which OS is running to use correct default path settings in config files.
if( DIRECTORY_SEPARATOR == '/' && PATH_SEPARATOR == ':' ) {
	$uname = php_uname();
	$parts = preg_split('/[[:space:]]+/', trim($uname));
	if($parts[0] == "Linux") {
		define( 'OS', 'LINUX' );
	} else {  // UNIX or Macintosh
		define( 'OS', 'UNIX' );
	}
} else { // Windows: DIRECTORY_SEPARATOR = '\' and PATH_SEPARATOR = ';'
	define( 'OS', 'WIN' );
}

// Zend framework requires the library folder to be in php path:
ini_set('include_path', BASEDIR.'/library'.PATH_SEPARATOR.ini_get('include_path'));

require_once BASEDIR.'/speedtest/serverinfo.php';
require_once BASEDIR.'/speedtest/dataclasses/wfl/DataClasses.php';

// Init loghandler and profiler.
require_once BASEDIR.'/utils/LogHandler.class.php';
LogHandler::init();
