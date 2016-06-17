<?php
/**
 * Health Check page for the Proxy Stub
 *
 * @package     ProxyForSC
 * @subpackage  Apps
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(dirname(__FILE__)).'/config.php';
require_once BASEDIR.'/bizclasses/BizHealthCheck.class.php';
$healthCheck = new BizHealthCheck();
$healthCheck->runTests();