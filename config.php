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

// include the server config file
require_once dirname(__FILE__) . '/../../config.php';

// ----------------------------------------------------------------------------
// MadeToPrint settings for login
// ----------------------------------------------------------------------------

/**
 * Server name used to log-in to the Enterprise Server (see: WWSettings.xml)
 */
define('AXAIO_MTP_SERVER_DEF_ID',		'Woodwing_Server');

/**
 * User name to log-in to the Enterprise Server
 * Must be a dedicated user (only to be used by MadeToPrint)
 */
define('AXAIO_MTP_USER',				'MadeToPrint');

/**
 * Users password to log-in to the Enterprise Server
 */
define('AXAIO_MTP_PASSWORD',			'ww');

/**
 * define if multiple workstations run MadeToPrint to connect to woodwing
 * @example define('AXAIO_MTP_ENABLE_MULTI_USERS', true);<br>define('AXAIO_MTP_ENABLE_MULTI_USERS', false);
 */
define('AXAIO_MTP_ENABLE_MULTI_USERS',  false);

/**
 * Provide an array of multiple usernames (with the default password set up above)
 * so multiple MadeToPrint instances can log in at the same time (bound to IP/hostname)
 * Needed if AXAIO_MTP_ENABLE_MULTI_USERS is enabled
 */
define('AXAIO_MTP_MULTI_USERS', 		serialize(array( // serialised array of "client IP or client hostname" and "username to login" (password see above)
											'192.168.0.10' => "MadeToPrintUser_1",
											'192.168.0.11' => "MadeToPrintUser_2",
											'MTPsvr.local' => "MadeToPrintUser_3"
                                        )));

// ----------------------------------------------------------------------------
// MadeToPrint token settings
// ----------------------------------------------------------------------------

define('AXAIO_MTP_TOKEN_BEGIN',			'«'); // the start of an MTP token
define('AXAIO_MTP_TOKEN_END',			'»'); // the end of an MTP token

// ----------------------------------------------------------------------------
// MadeToPrint hotfolder information
// ----------------------------------------------------------------------------

define('AXAIO_MTP_SERVER_FOLDER_IN',	'/Volumes/MadeToPrint/WW_Hotfolder/IN/'); // MTP input folder from woodwing server perspective
define('AXAIO_MTP_AXAIO_FOLDER_IN',     '/WW_Hotfolder/IN/'); // MTP input folder from MadeToPrint perspective (as set up in the MadeToPrint auto job)
define('AXAIO_MTP_AXAIO_FOLDER_OUT',	'/WW_Hotfolder/OUT/'); // MTP output folder from MadeToPrint perspective (as set up in the MadeToPrint auto job)

// ----------------------------------------------------------------------------
// Whether to create trigger files only when the layout status changed on checkin
// ----------------------------------------------------------------------------

define('AXAIO_MTP_TRIGGER_ON_CHANGE_ONLY', false);

// ----------------------------------------------------------------------------
// Whether to ignore layout flags. When true, layouts with flag are also processed
// ----------------------------------------------------------------------------

define('AXAIO_MTP_IGNORE_LAYOUT_FLAGS', false);

// ----------------------------------------------------------------------------
// MadeToPrint job name default setting. Real value is set up in admin panel
// ----------------------------------------------------------------------------

define('AXAIO_MTP_JOB_NAME',			'Sample PDF'); //Default MadeToPrint-job value for the setup.

// ----------------------------------------------------------------------------
// MadeToPrint php file locations, no need to change (in most cases)
// ----------------------------------------------------------------------------

define('AXAIO_MTP_PREPROCESS_LOC',      SERVERURL_ROOT.INETROOT.'/config/plugins/AxaioMadeToPrint/AxaioMadeToPrintPreProcess.php'); //location of the File to get the Username from
define('AXAIO_MTP_POSTPROCESS_LOC',		SERVERURL_ROOT.INETROOT.'/config/plugins/AxaioMadeToPrint/AxaioMadeToPrintPostProcess.php'); //location of the post processing file
