<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Demos
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * PHP sample code for the Google Calendar data API.  Utilizes the 
 * Zend Framework Gdata components to communicate with the Google API.
 * 
 * Requires the Zend Framework Gdata components and PHP >= 5.1.4
 *
 * You can run this sample both from the command line (CLI) and also
 * from a web browser.  When running through a web browser, only
 * AuthSub and outputting a list of calendars is demonstrated.  When
 * running via CLI, all functionality except AuthSub is available and dependent
 * upon the command line options passed.  Run this script without any
 * command line options to see usage, eg:
 *     /usr/local/bin/php -f Calendar.php
 *
 * More information on the Command Line Interface is available at:
 *     http://www.php.net/features.commandline
 *
 * NOTE: You must ensure that the Zend Framework is in your PHP include
 * path.  You can do this via php.ini settings, or by modifying the 
 * argument to set_include_path in the code below.
 *
 * NOTE: As this is sample code, not all of the functions do full error
 * handling.  Please see getEvent for an example of how errors could
 * be handled and the online code samples for additional information.
 */

/**
 * @see Zend_Loader
 */
require_once 'Zend/Loader.php';

/**
 * @see Zend_Gdata
 */
Zend_Loader::loadClass('Zend_Gdata');

/**
 * @see Zend_Gdata_AuthSub
 */
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

/**
 * @see Zend_Gdata_ClientLogin
 */
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

/**
 * @see Zend_Gdata_HttpClient
 */
Zend_Loader::loadClass('Zend_Gdata_HttpClient');

/**
 * @see Zend_Gdata_Calendar
 */
Zend_Loader::loadClass('Zend_Gdata_Calendar');


