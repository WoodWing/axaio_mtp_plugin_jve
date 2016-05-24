<?php
require_once "osconfig.php";

// BASEDIR:
//    BASEDIR is the operating system file path of the EnterpriseProxy folder.
//
define ('BASEDIR',	dirname(dirname(__FILE__)));  // DO NOT end with a separator, use forward slashes

// SERVERURL_ROOT:
//    SERVERURL_ROOT is the root HTTP location on which the application server runs
//    from remote point of view (optional).
//
//define ('SERVERURL_ROOT', '' );  // DO NOT end with a separator, use forward slashes

// -------------------------------------------------------------------------------------------------
// Proxy Server / Proxy Stub
// -------------------------------------------------------------------------------------------------

// PROXYSTUB_URL:
//    The URL to the remote Enterprise Server root folder (that runs the Proxy Stub).
//    End with a separator and use forward slashes.
//    Default value: 'http://127.0.0.1/EnterpriseProxy/'
//
define( 'PROXYSTUB_URL', 'http://127.0.0.1/EnterpriseProxy/' );

// ENTERPRISE_URL:
//    The URL to the Enterprise Server root folder.
//    End with a separator and use forward slashes.
//    Default value: 'http://127.0.0.1/Enterprise/'
//
define ('ENTERPRISE_URL', 'http://127.0.0.1/Enterprise/');

// PROXYSTUB_TRANSFER_PATH:
//    File transfer folder that temporary holds request/response data for proxy-stub traffic.
//    The folder resides at the proxy stub.
//    Make sure the INET/www user of the Proxy Stub machine has read+write access.
//    Default value: '/private/var/tmp'
//    In case Aspera is used this folder is used by Aspera client to store files at the Aspera Server side.
//    This means that the Aspera client must have read and write access to this folder.
//    In case of Aspera this setting must be the same for Proxy Server and Proxy Stub.
//
define( 'PROXYSTUB_TRANSFER_PATH', '/private/var/tmp' ); // DO NOT end with a separator, use forward slashes

// ENTERPRISEPROXY_TRANSFER_PROTOCOL:
//    File transfer method of HTTP requests and responses between Proxy Server and Proxy Stub:
//    - 'None'  : Files are temporarily stored on the file system. Used for demo only. No performance gain.
//                Works only when proxy server and proxy stub are running on the same machine.
//    - 'SSH'   : Files are simply copied over SSH. Used for testing only. No performance gain.
//                Works when proxy server and proxy stub are running on the different machine.
//    - 'Aspera': Files are copied by Aspera over UDP. Should be used for production.
//    Default value: 'None'
//
define( 'ENTERPRISEPROXY_TRANSFER_PROTOCOL', 'None' );

// ENTERPRISEPROXY_TIMEOUT:
//    The timeout in seconds applied to the HTTP connections between Proxy Server - Proxy Stub and
//    between Proxy Stub - Enterprise Server. Since there can be large files to transfer, for production
//    it is recommended to use 1 hour (or more). For debugging small files, you might want to decrease
//    this setting to 30 seconds or so.
//    Default value: 3600
//
define( 'ENTERPRISEPROXY_TIMEOUT', 3600 ); // 3600 sec = 1 hour

// PROXYSERVER_CACHE_PATH:
//    Folder at the proxy server where downloaded/uploaded files are cached.
//    Default value: ''
//
define( 'PROXYSERVER_CACHE_PATH', '' );

// -------------------------------------------------------------------------------------------------
// SSH integration (for testing only)
// -------------------------------------------------------------------------------------------------

// SSHFILECOPY:
//    When the ENTERPRISEPROXY_TRANSFER_PROTOCOL option is set to 'SSH', a hostname (or ip)
//    of the Proxy Stub server should be specified here, as well a user account. That host
//    should allow remote login over SSL for the configured user account.
//    To test if your SSL connection works, you could run the scp from command line:
//       scp /localfile user@host:/remotefile
//
define( 'SSH_STUBHOST', '' );
define( 'SSH_USERNAME', '' );
define( 'SSH_PASSWORD', '' );

// -------------------------------------------------------------------------------------------------
// Aspera integration (for production)
// -------------------------------------------------------------------------------------------------

// ASPERA_USER:
//    Aspera transfer products use the system accounts for connection authentication.
//    The user accounts need to be added and configured for Aspera transfers.
//    More information can be found in the Aspera manuals.
//	  Not applicable for Proxy Stub.
//    Default value: ''
//
define( 'ASPERA_USER', '');

// ASPERA_CERTIFICATE:
//    The Aspera client communicates uses the ssh protocol. The client needs a private key to
//    communicate with the Aspera server. This define holds the path to the certificate used by
//    the Aspera client to set up coomunication with the Aspera Server. Normally the certificate
//    is located in the .ssh directory in the home directory of the Aspera user.
//	  Not applicable for Proxy Stub.
//    Default value: ''
//
define( 'ASPERA_CERTIFICATE', '');

// ASPERA_SERVER:
//    Address of the Aspera Server, from Proxy Server perspective.
//	  Not applicable for Proxy Stub.
//    Default value: '127.0.0.1'
//
define('ASPERA_SERVER', '127.0.0.1');

// ASPERA_OPTIONS:
//    Options passed to Aspera (minimum speed, target speed etc)
//	  Not applicable for Proxy Stub.
//	  Default value: '-TQ -l 100000 -m 1000 --ignore-host-key '
//
define('ASPERA_OPTIONS', '-P 33001 -T -q --policy=high -l 100m -m 0 --ignore-host-key ');

// -------------------------------------------------------------------------------------------------
// Logging and Profiling
// -------------------------------------------------------------------------------------------------

// OUTPUTDIRECTORY:
//    The path to write log files to (including '/'), e.g. "c:/proxylog/output/".
//    Empty to disable. Default value: ''.
//
define ('OUTPUTDIRECTORY', '');

// DEBUGLEVELS:
//    Enables low-level debugging. Possible values: NONE, ERROR, WARN, INFO, DEBUG. Default value: 'NONE'.
//    The amount of information gets richer from left to right. NONE disables the low-level logging.
//    The option FATAL has been removed since Enterprise 6. Fatal errors caught by PHP are now logged
//    in the php.log file. When there are no (catchable) fatal errors, this file does not exist.
//    Since Enterprise 8, DEBUGLEVEL is renamed to DEBUGLEVELS and allows to specify level per client IP.
//    For the keys, fill in the client IPs and for the values, fill in the  debug levels.
//    There must be one item named 'default' which is used for all clients that are not explicitly configured.
//
define ('DEBUGLEVELS', serialize( array(
	// CLIENT IP => DEBUGLEVEL
	'default' => 'INFO', // 'default' entry is mandatory
)));

// LOGFILE_FORMAT:
//    The log file always contains UTF-8 characters, but aside to that, there are two formats
//    supported: 'html' and 'plain'. The HTML format is better readable in web browser. The plain
//    text format can be easier searched through using command line tools like grep.
//
define ('LOGFILE_FORMAT', 'html');

// PROFILELEVEL:
//    Used for profiling PHP code. Default value: 1. Requires DEBUGLEVELS to be set to 'INFO' or 'DEBUG'
//    in order to work, else the value of profile level is ignored. Possible settings are: 0 to 5:
//       0: No profiling
//       1: Web Service => Handling one service call of client application (excl network traffic)
//       2: PHP Service => Handling one service call without SOAP/AMF/JSON wrapping/unwrapping.
//       3: Ext Service => Call to external system, search engine, integrated system, shell scripts, etc
//       4: Data Store  => SQL calls to DB, file store, etc
//       5: PHP Script  => Potential expensive PHP operations, such as loops, regular expressions, etc
//
define ('PROFILELEVEL', 1);

// -------------------------------------------------------------------------------------------------
// System internals
// ===> DO NOT MAKE CHANGES TO THE FOLLOWING SECTION
// -------------------------------------------------------------------------------------------------

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

// PEAR and Zend framework requires the library folder to be in php path:
ini_set('include_path', BASEDIR.'/server/ZendFramework/library'.PATH_SEPARATOR.ini_get('include_path'));

require_once BASEDIR.'/server/utils/LogHandler.class.php';
LogHandler::init();

require_once BASEDIR.'/server/utils/PerformanceProfiler.class.php';