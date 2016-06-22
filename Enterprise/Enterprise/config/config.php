<?php
// Paranoid check defines that are essential to locate include files.
if( !defined('DIRECTORY_SEPARATOR') || !defined('PATH_SEPARATOR') ) {
	die( 'The PHP constants DIRECTORY_SEPARATOR or PATH_SEPARATOR are undefined.' );
	// Note: The PATH_SEPARATOR was introduced with PHP 4.3.0-RC2.
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

// ----------------------------------------------------------------------------
// Application Server details
//
// BASEDIR is the operating system file path of the Enterprise folder. Make sure
// you edit the correct BASEDIR setting of your operating system.
// INETROOT is the path to the Enterprise root folder relative from the Web root
// SERVERURL_ROOT is the root HTTP location on which the application server runs
// from remote point of view (optional).
// LOCALURL_ROOT is the root HTTP location on which the application server runs 
// from local point of view. Needed for internal server calls (especially WWtest).
// ----------------------------------------------------------------------------
if( !defined('BASEDIR') ) {
	define( 'BASEDIR', dirname( dirname( __FILE__ ) ) ); // DO NOT end with a separator, use forward slashes
}

require_once( BASEDIR.'/config/config_overrule.php' );

if( !defined('INETROOT') ) {
	define( 'INETROOT', '/Enterprise' ); // DO NOT end with a separator, use forward slashes
}

// The SERVERURL_ROOT setting is calculated at runtime (see bottom of this file).
// You are allowed to overrule/hard-code this setting, but normally there is no need.
// If you do, switch on the following definitions by removing the "//" characters 
// from the start of the line. Do not use a slash at the end.
// For example: 'https://www.mydomain.com:481'.
//define ('SERVERURL_ROOT', 'http://localhost' );

if( !defined('LOCALURL_ROOT') ) {
	define( 'LOCALURL_ROOT', 'http://127.0.0.1' );
}

// ----------------------------------------------------------------------------
// Database details
//
// Database Name, username and password
//
// Note: advanced Database settings can be found in the configserver.php file
// ----------------------------------------------------------------------------

if( !defined('DBSELECT') ) {
	define( 'DBSELECT', 'Enterprise' );   // Database name
}
if( !defined('DBUSER') ) {
	define( 'DBUSER', 'root' );   // Database user to be used by the Application Server.
											// Note: this is the single database user that is used by the application
											// server to access the database.
											// This database user account needs SELECT, INSERT, UPDATE, and DELETE privileges.
											// Additionally, the database user also requires ALTER TABLE privileges for creation and deletion of custom properties.
											// Default is 'root'. For MSSQL Server you could use 'sa' as default.
}
if( !defined('DBPASS') ) {
	define( 'DBPASS', '' );       // Password for the database user identified by DBUSER.
}

// ----------------------------------------------------------------------------
// File Server details
//
// By default, the application server stores its object files at a file server. The file server 
// can be the same machine (which is the default setting) or a different machine. It does NOT need to be accessible from the 
// clients. Obviously, the application server DOES need to have access. The ATTACHMENTDIRECTORY 
// setting (listed below) points to the file store located in the file server.
// Note: Advanced file server settings can be found in the configserver.php file.
//
// ATTACHMENTDIRECTORY     - File store; Root folder of Enterprise to store all its files
// WOODWINGSYSTEMDIRECTORY - System folder; Storage of temporary system files (must be placed under the ATTACHMENTDIRECTORY)
// TEMPDIRECTORY           - Temp folder; Storage of temporary files
// EXPORTDIRECTORY         - Export folder; Location where all exported files are downloaded
// ----------------------------------------------------------------------------

if( !defined('ATTACHMENTDIRECTORY') ) {
	if( OS == 'WIN' )
		define( 'ATTACHMENTDIRECTORY', 'c:/FileStore' );
	else // Mac OSX & UNIX:
		define( 'ATTACHMENTDIRECTORY', '/FileStore' );
}

if( !defined('WOODWINGSYSTEMDIRECTORY') ) {
	define( 'WOODWINGSYSTEMDIRECTORY', ATTACHMENTDIRECTORY.'/_SYSTEM_' ); // no ending '/'
}
if( !defined('TEMPDIRECTORY') ) {
	define( 'TEMPDIRECTORY', WOODWINGSYSTEMDIRECTORY.'/Temp' );     // no ending '/'
}
if( !defined('SESSIONWORKSPACE') ) {
	define( 'SESSIONWORKSPACE', WOODWINGSYSTEMDIRECTORY.'/SessionWorkspace' );  // no ending '/'
}
if( !defined('EXPORTDIRECTORY') ) {
	define( 'EXPORTDIRECTORY', WOODWINGSYSTEMDIRECTORY.'/Export/' );  // including ending '/'
}
if( !defined('PERSISTENTDIRECTORY') ) {
	define( 'PERSISTENTDIRECTORY', WOODWINGSYSTEMDIRECTORY.'/Persistent' );  // no ending '/'
}

if( !defined('AUTOCOMPLETEDIRECTORY') ) {
	define( 'AUTOCOMPLETEDIRECTORY', WOODWINGSYSTEMDIRECTORY.'/TermsFiles' );
}

// ----------------------------------------------------------------------------
// File name encoding
//
// FILENAME_ENCODING - Encoding for file names when creating new files, dependant on the system language.
// Only needs to be defined when on the Windows server the export function produces "garbled" file names.
// The following values are supported:
// - 'cp1251'    	Russian
// - 'big5'      	Traditional Chinese
// - 'gb2312'    	Simplified Chinese
// - 'shift_jis' 	Japanese
// - 'euc-kr'    	Korean
// - 'ISO-8859-1'	Latin
// ----------------------------------------------------------------------------

// if( !defined('FILENAME_ENCODING') ) {
//    define ('FILENAME_ENCODING', 'cp1251'); // example of how to configure a Russian system
// }

// The setlocale() function call below sets the internal locale of PHP itself which should be US English.
// When it would not call this function, PHP takes over the locale setting from your OS or HTTP server.
// It picks US English to avoid PHP functions containing bad side effects of foreign character encodings
// and to avoid commas used for decimal separators in precision numbers (which should be periods).
// Note that the locale is not related to the user/company language configured for Enterprise Server.
// In case of an error, none of the listed options match with the ones installed in your OS. It could be
// that your OS uses an alternative name for the US English language. In that case, add the alternative
// locale name to the three listed options. When there is no US English locale installed in your OS, please install it.
// See the PHP manual for more details about setlocale(): http://nl3.php.net/manual/en/function.setlocale.php
//
// The following locale codes should set the locale correctly for all supported OS flavors,
// Macintosh = en_US.UTF-8
// Linux = en_US.UTF8
// Windows = us
//
// if problems are experienced, it might be needed to change the locale, see below for pointers on how to do this.
//
// To find available locales under Macintosh or Linux, use the command locale -m for a list of installed locales or use
// locale -a to see all available locales.
//
// To find a list of the supported locales under Windows please refer to the following webpage:
// http://msdn.microsoft.com/en-us/library/39cwe7zf%28vs.71%29.aspx

// Attempt to set the locale to US/English, if this fails display an error.
if( setlocale( LC_ALL, array('en_US.UTF-8', 'en_US.UTF8', 'us')) === false ) { // Macintosh, Linux, Windows
    echo 'ERROR: The locale could not be set to US English.<br/>';
    echo 'Please check your config.php file for instructions.<br/>';
    die();
}

// ----------------------------------------------------------------------------
// ===> DO NOT MAKE CHANGES BELOW

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
	if( !defined('SERVERURL_PROTOCOL') ) {
		define ('SERVERURL_PROTOCOL', 'https://' );
	}
	if( !defined('SERVERURL_PORT') ) {
		if( $_SERVER['SERVER_PORT'] != '443' ) {
			define( 'SERVERURL_PORT', ':'.$_SERVER['SERVER_PORT'] );
		} else {
			define( 'SERVERURL_PORT', '' );
		}
	}
} else {
	if( !defined('SERVERURL_PROTOCOL') ) {
		define ('SERVERURL_PROTOCOL', 'http://' );
	}
	if( !defined('SERVERURL_PORT') ) {
		if( isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!='80' ) {
			define ('SERVERURL_PORT', ':' . $_SERVER['SERVER_PORT'] );
		} else {
			define ('SERVERURL_PORT', '' );
		}
	}
}
if( !defined('SERVERURL_ROOT') ) {
	if( isset($_SERVER['HTTP_HOST']) ) {
		define ('SERVERURL_ROOT', SERVERURL_PROTOCOL.$_SERVER['HTTP_HOST'] );
	} else {
		$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		define ('SERVERURL_ROOT', $serverName.SERVERURL_PORT );
	}
}
if( !defined('SERVERURL_SCRIPT') ) {
	if(isset($_SERVER['REQUEST_URI'])) {
		define ('SERVERURL_SCRIPT', SERVERURL_ROOT.$_SERVER['REQUEST_URI'] );
	} else {
		if(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']>' ') {
			define ('SERVERURL_SCRIPT', SERVERURL_ROOT.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'] );
		} else {
			define ('SERVERURL_SCRIPT',SERVERURL_ROOT.$_SERVER['PHP_SELF'] );
		}
	}
}

if (!file_exists(BASEDIR.'/config/configserver.php')){
	exit( '<h1>File not found: '.BASEDIR.'/config/configserver.php'.'<br/>Please check BASEDIR setting in config.php<br/></h1>');
}
require_once BASEDIR.'/config/configserver.php';
