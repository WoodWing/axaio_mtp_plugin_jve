<?php
/**
 * Index entry point for the Proxy Server
 *
 * @package     ProxyForSC
 * @subpackage  Index
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

// To deal with large upload, avoid script abortion due to low max_execution_time setting
@ignore_user_abort(1); // Disallow clients to stop server (PHP script) execution.
@set_time_limit(0);    // Run server (PHP script) forever.

require_once dirname(__FILE__).'/config.php';
require_once BASEDIR . '/bizclasses/BizProxyServer.class.php';
$server = new BizProxyServer();
$server->handle( 'adminindex.php' );