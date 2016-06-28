<?php
/**
 * Provides Server Info (in JSON format) of the Proxy Stub
 *
 * @package     ProxyForSC
 * @subpackage  Apps
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(dirname(__FILE__)).'/config.php';
print json_encode( array(
	'version' => PRODUCT_VERSION
) );	
