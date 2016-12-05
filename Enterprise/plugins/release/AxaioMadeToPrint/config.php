<?php
/**
 * This is the configuration file for axaio MadeToPrint EnterprisePlugin
 * 
 * @copyright (c) 2015, axaio software GmbH
 * @author René Treuber <support@axaio.com>
 * @package AxaioMadeToPrint
 */

/****************************************************************************
 * This is a sample config file which shows how this could look like. 
 * You need to set up your own values, of course.
 ***************************************************************************/

// ----------------------------------------------------------------------------
// MadeToPrint settings for login
// ----------------------------------------------------------------------------

/**
 * Server name used to log-in to the Enterprise Server (see: WWSettings.xml)
 */
if( !defined('AXAIO_MTP_SERVER_DEF_ID' ) ) {
	define('AXAIO_MTP_SERVER_DEF_ID',		'Woodwing_Server'); // server name used to log-in to the Enterprise Server (WWSettings.xml)
}
/**
 * User name to log-in to the Enterprise Server
 */
if( !defined('AXAIO_MTP_USER' ) ) {
	define('AXAIO_MTP_USER',				'MadeToPrint'); // the user name used to log-in in Enterprise
}
/**
 * Users password to log-in to the Enterprise Server
 */
if( !defined('AXAIO_MTP_PASSWORD' ) ) {
	define('AXAIO_MTP_PASSWORD',			'ww'); // the password used to log-in the user in Enterprise
}

/**
 * define if multiple workstations run MadeToPrint to connect to woodwing
 * @example define('AXAIO_MTP_ENABLE_MULTI_USERS', true);<br>define('AXAIO_MTP_ENABLE_MULTI_USERS', false);
 */
if( !defined('AXAIO_MTP_ENABLE_MULTI_USERS' ) ) {
	define('AXAIO_MTP_ENABLE_MULTI_USERS',  false);  // define if multiple workstations run MadeToPrint to connect to woodwing
}

/**
 * Provide an array of multiple usernames (with the default password set up above)
 * so multiple MadeToPrint instances can log in at the same time (bound to IP/hostname)
 * Needed if AXAIO_MTP_ENABLE_MULTI_USERS is enabled
 */
if( !defined('AXAIO_MTP_MULTI_USERS' ) ) {
	define('AXAIO_MTP_MULTI_USERS', 		serialize(array( // serialised array of "client IP or client hostname" and "username to login" (password see above)
		'192.168.0.10' => "MadeToPrint1",
		'192.168.0.11' => "MadeToPrint2",
		'MTPsvr.local' => "MadeToPrint3"
	)));
}

// ----------------------------------------------------------------------------
// MadeToPrint token settings
// ----------------------------------------------------------------------------

if( !defined('AXAIO_MTP_TOKEN_BEGIN' ) ) {
	define('AXAIO_MTP_TOKEN_BEGIN',			'«'); // the start of an MTP token
}
if( !defined('AXAIO_MTP_TOKEN_END' ) ) {
	define('AXAIO_MTP_TOKEN_END',			'»'); // the end of an MTP token
}

// ----------------------------------------------------------------------------
// MadeToPrint hotfolder information
// ----------------------------------------------------------------------------

if( !defined('AXAIO_MTP_SERVER_FOLDER_IN' ) ) {
	define('AXAIO_MTP_SERVER_FOLDER_IN',	'/Volumes/MadeToPrint/WW_Hotfolder/IN/'); // MTP input folder from woodwing server perspective
}
if( !defined('AXAIO_MTP_AXAIO_FOLDER_IN' ) ) {
	define('AXAIO_MTP_AXAIO_FOLDER_IN',     '/WW_Hotfolder/IN/'); // MTP input folder from MadeToPrint perspective (as set up in the MadeToPrint auto job)
}
if( !defined('AXAIO_MTP_AXAIO_FOLDER_OUT' ) ) {
	define('AXAIO_MTP_AXAIO_FOLDER_OUT',	'/WW_Hotfolder/OUT/'); // MTP output folder from MadeToPrint perspective (as set up in the MadeToPrint auto job)
}

// ----------------------------------------------------------------------------
// MadeToPrint job name default setting. Real value is set up in admin panel
// ----------------------------------------------------------------------------

if( !defined('AXAIO_MTP_JOB_NAME' ) ) {
	define('AXAIO_MTP_JOB_NAME',			'Sample PDF'); //Default MadeToPrint-job value for the setup.
}

// ----------------------------------------------------------------------------
// MadeToPrint php file locations, no need to change (in most cases)
// ----------------------------------------------------------------------------

if( !defined('AXAIO_MTP_PREPROCESS_LOC' ) ) {
	define('AXAIO_MTP_PREPROCESS_LOC',      SERVERURL_ROOT.INETROOT.'/config/plugins/AxaioMadeToPrint/AxaioMadeToPrintPreProcess.php'); //location of the File to get the Username from
}
if( !defined('AXAIO_MTP_POSTPROCESS_LOC' ) ) {
	define('AXAIO_MTP_POSTPROCESS_LOC',		SERVERURL_ROOT.INETROOT.'/config/plugins/AxaioMadeToPrint/AxaioMadeToPrintPostProcess.php'); //location of the post processing file
}
