<?php

// ServerInfo
define ('SERVERNAME',		    'Enterprise');
define ('SERVERDEVELOPER',	    'WoodWing');			// DO NOT CHANGE!
define ('SERVERIMPLEMENTATION',	'SmartConnection');		// DO NOT CHANGE!
define ('SERVERTECHNOLOGY',	    'PHP');					// DO NOT CHANGE!
define ('SERVERVERSION',	    '10.0.0 Build 82');
define ('SERVERVERSION_EXTRAINFO',	'');					// To be used for labels such as Prerelease and Daily

// For internal use, to validate configurations:
define ('SCENT_DBVERSION',	    '10.0' );

$supportedPhpVersions = array(
    // format   >>> meaning
    // '1.2.3'  >>> php version 1.2.3 is supported
    // '1.2.3+' >>> php version 1.2.3...1.2.x is supported (so 1.2.x with patch >= 3)
    // '-1.2.3' >>> php version 1.2.3 NOT supported
    '5.5.16+',
    '5.6.14+'
);
if( OS == 'LINUX' ) { // PHP 5.4.16+ supported on LINUX platform only
   $supportedPhpVersions[] = '5.4.16+';
}
define ('SCENT_PHPVERSIONS',	serialize($supportedPhpVersions) );

define ('ADOBE_VERSIONS',		serialize( array( // used to detect if installed IDS is supported by Enterprise
	// Major/minor version, oldest version first, latest as last one.
	'CS6' => '8.0',
	'CC2014' => '10.0',
	'CC2015' => '11.0',
)));
define ('ADOBE_VERSIONS_ALL',		serialize( array( // used to detect documents versions, including older versions
	// Major/minor version, oldest version first, latest as last one.
	'CS5' => '7.0',
	'CS5.5' => '7.5',
	'CS6' => '8.0',
	'CC' => '9.0',
	'CC2014' => '10.0',
	'CC2015' => '11.0',
)));

// For online-help articles:
define ('ONLINEHELP_SERVER_MAJOR_VERSION', '10');

// For License:
define ('PRODUCTMAJORVERSION', '10' );
define ('PRODUCTMINORVERSION', '0' );
define ('PRODUCTNAME', SERVERNAME . ' v' . PRODUCTMAJORVERSION);
define ('PRODUCTVERSION', 'v' . SERVERVERSION );

$copyRightYears = ini_get('date.timezone') ? '1998-'.date('Y') : '1998'; // Avoid bad error at PHP output; The error handler is not set at this stage.
define( 'COPYRIGHT_WOODWING', '(c) '.$copyRightYears.' WoodWing Software bv. All rights reserved.' );
