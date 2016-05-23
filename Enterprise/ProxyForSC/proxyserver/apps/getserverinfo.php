<?php

/**
 * Provides Server Info (in JSON format) of the Proxy Server and Proxy Stub (combined).
 *
 * @package     ProxyForSC
 * @subpackage  Apps
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(dirname(__FILE__)).'/config.php';
$proxyStubInfo = json_decode( file_get_contents( PROXYSTUB_URL.'proxystub/apps/getserverinfo.php' ) );
print json_encode( array(
	'proxystub' => $proxyStubInfo,
	'proxyserver' => array(
		'version' => PRODUCT_VERSION,
		'file_compression' => ENTERPRISEPROXY_COMPRESSION,
		'file_transfer' => ENTERPRISEPROXY_TRANSFER_PROTOCOL,
		'use_proxystub' => ENTERPRISEPROXY_USEPROXYSTUB
	)
) );

