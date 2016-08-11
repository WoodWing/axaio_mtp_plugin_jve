<?php

// ServerInfo
define ('SERVERNAME',		        'Enterprise');
define ('SERVERDEVELOPER',	        'WoodWing');          // DO NOT CHANGE!
define ('SERVERIMPLEMENTATION',    'SmartConnection');   // DO NOT CHANGE!
define ('SERVERTECHNOLOGY',        'PHP');               // DO NOT CHANGE!
define ('SERVERVERSION',	        getProductVersion(__DIR__));
define ('SERVERVERSION_EXTRAINFO', getServerVersionExtraInfo(__DIR__)); // To be used for labels such as Prerelease and Daily

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

// Minimum and maximum supported version for MySQL
define ('SCENT_MYSQLDB_MINVERSION', '5.6');
define ('SCENT_MYSQLDB_MAXVERSION', '5.7');

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

/**
 * Resolves the product version file _productversion.txt read from a given product folder.
 *
 * This function is called by the server plugins and by the core server.
 * The file is present for shipped versions. For development installations the file is missing.
 * Developers could add define( 'DEFAULT_PRODUCT_VERSION', 'x.y.0 Build 0' ); to config_overrule.php
 * (which is used as a fallback value when file does not exists) to have a representative version.
 *
 * @since 10.1.0
 * @param string $folder
 * @return string The plugin version.
 */
function getProductVersion( $folder )
{
	$file = $folder.'/_productversion.txt';
	if( file_exists( $file ) ) {
		$version = file_get_contents( $file );
	} elseif( defined( 'DEFAULT_PRODUCT_VERSION' ) ) {
		$version = DEFAULT_PRODUCT_VERSION;
	} else {
		exit( 'ERROR: Product version undefined. '.
			'For release versions, please provide a _productversion.txt file in '.$folder.'. '.
			'For development version, define DEFAULT_PRODUCT_VERSION in config_overrule.php' );
	}
	return trim($version);
}

/**
 * Resolves extra server version info from the file _productversionextra.txt read from a given product folder.
 *
 * This function is called by the core server.
 * The file is present for shipped versions. For development installations the file is missing.
 * When missing 'Development' is returned.
 *
 * @since 10.1.0
 * @param string $folder
 * @return string The extra product version info.
 */
function getServerVersionExtraInfo( $folder )
{
	$info = 'Development';
	$file = $folder.'/_productversionextra.txt';
	if( file_exists( $file ) ) {
		$info = file_get_contents( $file );
	}
	return trim($info);
}