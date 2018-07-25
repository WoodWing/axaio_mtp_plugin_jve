<?php
/**
 * Helper class to checks Zend OPcache configurations and clears the cache.
 *
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_ZendOpcache
{
	/**
	 * Checks if Zend OPcache is installed.
	 *
	 * @return bool
	 */
	public static function isOpcacheInstalled()
	{
		return extension_loaded('Zend OPcache');
	}

	/**
	 * Clears Zend OPcache when the module is installed.
	 *
	 * @return null|bool Null when module is not installed. True when successfully cleared the cache, False otherwise.
	 */
	public static function clearOPcache()
	{
		$result = null;
		if( self::isOpcacheInstalled() ) {
			$result = opcache_reset();
			if( $result ) {
				LogHandler::Log('OPcache utils', 'INFO', 'The Zend OPcache was successfully cleared.' );
			} else {
				LogHandler::Log('OPcache utils', 'ERROR', 'The Zend OPcache could not be cleared. ' );
			}
		}
		return $result;
	}
}
