<?php
/**
 * Configuration options for the Proxy Stub.
 *
 * @package     ProxyForSC
 * @subpackage  Config
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
 
// ENTERPRISE_URL:
//    The URL to the Enterprise Server root folder.
//    End with a separator and use forward slashes.
//    Default value: 'http://127.0.0.1/Enterprise/'
//
define ('ENTERPRISE_URL', 'http://127.0.0.1/Enterprise/');

// PROXYSTUB_TRANSFER_PATH:
//    File transfer folder that temporary holds request/response data for proxy-stub traffic.
//    The folder resides at the Proxy Stub. End with a separator and use forward slashes.
//    Make sure the internet/ssh/aspera users have read+write+delete access to this folder.
//    Default value: '/ProxyStub/'
//    In case Aspera is used this folder is used by Aspera client to store files at the Aspera Server side.
//    This means that the Aspera client must have read and write access to this folder.
//
define( 'PROXYSTUB_TRANSFER_PATH', '/ProxyStub/' );

// ENTERPRISEPROXY_TRANSFER_PROTOCOL:
//    File transfer method of HTTP requests and responses between Proxy Server and Proxy Stub:
//    - 'cp'   : Files are temporarily stored on the file system. Used for demo only. No performance gain.
//               Works only when proxy server and proxy stub are running on the same machine.
//    - 'scp'  : Files are simply copied over ssh. Used for testing only. No performance gain.
//               Works when proxy server and proxy stub are running on the different machine.
//    - 'bbcp' : Files are copied with bbcp (ssh). Could be used for production.
//    - 'ascp' : Files are copied by Aspera over UDP. Could be used for production.
//    Default value: 'cp'
//
define( 'ENTERPRISEPROXY_TRANSFER_PROTOCOL', 'cp' );

// ENTERPRISEPROXY_TIMEOUT:
//    The timeout in seconds applied to the HTTP connections between Proxy Server and Proxy Stub.
//    Since there can be large files to transfer it is recommended to use 1 hour (or more).
//    Default value: 3600
//
define( 'ENTERPRISEPROXY_TIMEOUT', 3600 ); // 3600 sec = 1 hour

// ENTERPRISEPROXY_COMPRESSION:
//    The request- and response files can be compressed with the deflate algorithm (RFC 1950 - zlib)
//    before they are sent between Proxy Server and Proxy Stub. On one hand this reduces the
//    amount of data sent over the network but on the other hand, compression costs CPU processing
//    time. Whether or not this speeds up the end-user waiting times depends on the network and
//    processing power on both sides. Use the PROFILELEVEL option to determine if compression
//    should be enabled or disabled. 
//    Default value: true
//
define( 'ENTERPRISEPROXY_COMPRESSION', true ); // Fill in true to enable, or false to disable.

// -------------------------------------------------------------------------------------------------
// Logging and Profiling
// -------------------------------------------------------------------------------------------------

// OUTPUTDIRECTORY:
//    The path where to write log files into. Leave empty to disable logging. Default value: ''.
//    End with a separator and use forward slashes, for example: 'c:/logging/proxystub/'
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

// PROFILELEVEL:
//    Performance profiling option. Default value: 0. Requires OUTPUTDIRECTORY to be set and 
//    DEBUGLEVELS to be set to 'INFO' or 'DEBUG' in order to work, else the value of profile 
//    level is ignored. Profile files are written in the log folder as "..._profile.htm".  
//    Supported values are:
//       0: No profiling
//       1: Internal service => Total time of handling a service call (excl arrival network traffic, incl wait for ext systems).
//       2: External service => Waiting time for external system (Proxy Stub or Enterprise Server) handing a service call or file transfer.
//       3: Data compressing => Compressing time of requests and responses.
//       4: I/O processing => Read/write to disk, execute (ssh) shell scripts, etc.
//       5: PHP processing => Potential expensive PHP operations, such as loops, regular expressions, etc.
//
define ('PROFILELEVEL', 0);

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

// Define proxy name and version.
require_once BASEDIR.'/proxystub/serverinfo.php';

// Supported PHP versions.
define( 'PROXY_PHPVERSIONS', serialize( array( 
	// format   >>> meaning
	// '1.2.3'  >>> php version 1.2.3 is supported
	// '1.2.3+' >>> php version 1.2.3...1.2.x is supported (so 1.2.x with patch >= 3)
	// '-1.2.3' >>> php version 1.2.3 NOT supported
	'5.4.32+', 
	'5.5.16+'
)));

// Zend framework requires the library folder to be in php path:
ini_set('include_path', BASEDIR.'/library'.PATH_SEPARATOR.ini_get('include_path'));

// Init loghandler and profiler.
require_once BASEDIR.'/utils/LogHandler.class.php';
LogHandler::init();

require_once BASEDIR.'/utils/PerformanceProfiler.class.php';
