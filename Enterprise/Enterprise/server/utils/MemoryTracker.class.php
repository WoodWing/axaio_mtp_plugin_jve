<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	Utils
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.

	Utility to debug memory usage 

 */

class MemoryTracker
{
	/**
	 * Logs current memory usage and peak to debug log
	 *
	 * @param string	$location 	Code location of caller to be included in log-file
	 * 
	 * @return no return
	 */	
	public static function log( $location )
	{
		// If logging turned off bail out to execute as least code as possible
   		if( !LogHandler::debugMode() ) {
   			return;
   		}
   		
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
   		$info = '';
   		// Note that we support PHP v5.2.0 installations, which could have no '--enable-memory-limit' option.
   		// In that case the memory_get_usage and memory_get_peak_usage function are not defined!
		if( function_exists( 'memory_get_usage' ) ) { // since php v4.3.2
			$info .= 	NumberUtils::getByteString(memory_get_usage(),2).' / '.
						NumberUtils::getByteString(memory_get_usage(true),2);
		}
		if( function_exists( 'memory_get_peak_usage' ) ) { // since php v5.0
			$info .=	', peak: '.
						NumberUtils::getByteString(memory_get_peak_usage(),2).' / '.
						NumberUtils::getByteString(memory_get_peak_usage(true),2);
		}
		if( $info != '' ) {
			LogHandler::Log( 'memory', 'DEBUG', $location.' - Memory usage, virtual/real: '.$info );
		}
	}
}