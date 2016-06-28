<?php
/**
 * @package 	Enterprise
 * @subpackage 	Utils
 * @since 		v9.4
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Cross Origin Header util for Enterprise.
 */
class WW_Utils_CrossOriginHeaderUtil
{

	/**
	 * This function adds Cross Origin headers needed for Javascript applications.
	 *
	 * By default a Javascript application running on a different URL can't access the
	 * Enterprise Server API. By configuring the CROSS_ORIGIN_HEADERS option in
	 * configserver.php you can add the necessary headers to allow communication.
	 *
	 * When the CROSS_ORIGIN_HEADERS option is enabled the headers are send back for every
	 * request. This doesn't break any current implementations.
	 */
	public static function addCrossOriginHeaders() {
		if ( defined('CROSS_ORIGIN_HEADERS') ) {
			$allowedOrigins = unserialize(CROSS_ORIGIN_HEADERS);
			if( count($allowedOrigins) > 0 ) {
				// Get the origin of the request to determine the headers to set.
				$httpOrigin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
				
				$crossOriginInfo = null;
				if (isset($allowedOrigins[$httpOrigin])) {
					LogHandler::Log('CrossOriginHeader', 'DEBUG', 'Cross Origin headers from the following origin are used: ' . $httpOrigin);
					$crossOriginInfo = $allowedOrigins[$httpOrigin];
				} else {
					// Fallback, for example Content Station doesn't send a HTTP_ORIGIN header
					$crossOriginInfo = reset($allowedOrigins); // Get the first configured option
					$httpOrigins = array_keys($allowedOrigins); // Get the keys
					$httpOrigin = reset($httpOrigins); // Get the corresponding key
					LogHandler::Log('CrossOriginHeader', 'DEBUG', 'Origin not found. Using the settings from the following origin: ' . $httpOrigin);
				}

				header("Access-Control-Allow-Origin: " . $httpOrigin);

				if($crossOriginInfo) foreach( $crossOriginInfo as $headerName => $headerValue ) {
					LogHandler::Log('CrossOriginHeader', 'DEBUG', 'Extra cross origin header added: ' . $headerName . ': ' . $headerValue);
					header($headerName . ': ' . $headerValue);
				}
			}
		}
	}
}	