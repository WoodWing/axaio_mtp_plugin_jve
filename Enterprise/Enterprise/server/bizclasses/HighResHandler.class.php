<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v4.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
// Utility functions for the high-res folder.
// Configuration notes: http://kb.woodwing.net/article.php?id=090
class HighResHandler
{
	/**
	 * Returns the first found configured high-res folder that exists for externaly stored adverts.
	 * If high-res store is configured, but not does exists, it returns empty string.
	 *
	 * @return string  Full uniform path of the external advert high-res store.
	 */
	public static function getHighResAdvertStore()
	{
		$sHighResStore = BizSettings::isFeatureEnabled('HighResStoreWin') ? trim(BizSettings::getFeatureValue('HighResStoreWin')) : '';
		if( $sHighResStore == '' ) {
			$sHighResStore = defined('HIGHRES_STORE_WIN') ? trim(HIGHRES_STORE_WIN) : '';
		}
		if( $sHighResStore != '' ) {
			$dh = opendir( $sHighResStore ); // Note: don't use is_dir function since that does not work for mounted folders on Windows, such as: //MY_MACHINE/my_path/
			if( $dh ) {
				closedir( $dh );
			} else {
				$sHighResStore = ''; // forget since it does not exists
			}
		}
		if( $sHighResStore == '' ) {
			$sHighResStore = BizSettings::isFeatureEnabled('HighResStoreMac') ? trim(BizSettings::getFeatureValue('HighResStoreMac')) : '';
			if( $sHighResStore == '' ) {
				$sHighResStore = defined('HIGHRES_STORE_MAC') ? trim(HIGHRES_STORE_MAC) : '';
			}
			if( $sHighResStore != '' ) {
				$dh = opendir( $sHighResStore );
				if( $dh ) {
					closedir( $dh );
				} else {
					$sHighResStore = ''; // forget since it does not exists
				}
			}
		}
		return $sHighResStore;
	}

	/**
	 * Returns the first found configured high-res folder that exists for externaly stored images.
	 * If high-res store is configured, but not does exists, it returns empty string.
	 *
	 * @return string  Full uniform path of the external image high-res store.
	 */
	public static function getHighResImageStore()
	{
		$sHighResStore = BizSettings::isFeatureEnabled('HighResImageStoreWin') ? trim(BizSettings::getFeatureValue('HighResImageStoreWin')) : '';
		if( $sHighResStore == '' ) {
			$sHighResStore = defined('HIGHRES_IMAGE_STORE_WIN') ? trim(HIGHRES_IMAGE_STORE_WIN) : '';
		}
		if( $sHighResStore != '' ) {
			$dh = opendir( $sHighResStore ); // Note: don't use is_dir function since that does not work for mounted folders on Windows, such as: //MY_MACHINE/my_path/
			if( $dh ) {
				closedir( $dh );
			} else {
				$sHighResStore = ''; // forget since it does not exists
			}
		}
		if( $sHighResStore == '' ) {
			$sHighResStore = BizSettings::isFeatureEnabled('HighResImageStoreMac') ? trim(BizSettings::getFeatureValue('HighResImageStoreMac')) : '';
			if( $sHighResStore == '' ) {
				$sHighResStore = defined('HIGHRES_IMAGE_STORE_MAC') ? trim(HIGHRES_IMAGE_STORE_MAC) : '';
			}
			if( $sHighResStore != '' ) {
				$dh = opendir( $sHighResStore );
				if( $dh ) {
					closedir( $dh );
				} else {
					$sHighResStore = ''; // forget since it does not exists
				}
			}
		}
		return $sHighResStore;
	}

	/**
	 * Makes high-res relative to high-res store. <br/>
	 * This is done by removing HighResStore[Mac/Win] base path setting (for adverts) <br/>
	 * or by removing HighResImageStore[Mac/Win] base path settings (for images) <br/>
	 * from the HighResFile object property before storing it into db. <br/>
	 * Returns the first found configured high-res folder that exists for externaly stored images or adverts. <br/>
	 * If high-res store is configured, but not does exists, it returns empty string. <br/>
	 *
	 * @param $highresfile string  Absolute path to high-res file.
	 * @param $type string         Object type; Advert or Image
	 * @return string              Relative file path (without base folder).
	 */
	public static function stripHighResBasePath( $highresfile, $type )
	{
		switch( $type ) 
		{
			case 'Advert':
				$highresfile = self::stripHighResAdvertBasePath( $highresfile );
				break;
			case 'Image':
				$highresfile = self::stripHighResImageBasePath( $highresfile );
				break;
			default:
				LogHandler::Log( 'utilities', 'ERROR', 'stripHighResBasePath: unsupported type: '.$type );
				break;
		}
		return $highresfile;
	}

	/**
	 * Makes sure the given high-res file path can be accessed by application server.
	 * The provided file path is from client, which can be Mac or Windows notation.
	 * The resolved path is the location as seen from application point of view.
	 *
	 * @param $highresfileForeign string  Absolute file path from client point of view.
	 * @param $type string   Object type; Advert or Image
	 * @return string        Absolute file path from application point of view.
	 */
	public static function resolveHighResFile( $highresfileForeign, $type )
	{
		$highresfileStripped = self::stripHighResAdvertBasePath( $highresfileForeign );
		switch( $type ) 
		{
			case 'Advert':
				$highresfileResolved = self::getHighResAdvertStore().$highresfileStripped;
				break;
			case 'Image':
				$highresfileResolved = self::getHighResImageStore().$highresfileStripped;
				break;
			default:
				LogHandler::Log( 'utilities', 'ERROR', 'resolveHighResFile: unsupported type: '.$type );
				$highresfileResolved = '';
				break;
		}
		return $highresfileResolved;
	}
	
	/**
	 * Makes high-res relative to high-res advert store. <br/>
	 * See {@link stripHighResBasePath} for more details. <br/>
	 *
	 * @param $highresfile string  Absolute path to high-res advert file.
	 * @return string              Relative file path (without base folder).
	 */
	private static function stripHighResAdvertBasePath( $highresfile )
	{
		$pos = false;

		// Adverts on Windows		
		$sStoreWin = BizSettings::isFeatureEnabled('HighResStoreWin') ? trim(BizSettings::getFeatureValue('HighResStoreWin')) : '';
		if( $sStoreWin == '' ) {
			$sStoreWin = defined('HIGHRES_STORE_WIN') ? trim(HIGHRES_STORE_WIN) : '';
		}
		if( $sStoreWin != '' ) {
			$pos = stripos( $highresfile, $sStoreWin );
			if( $pos !== false ) {
				$highresfile = substr( $highresfile, strlen($sStoreWin) );
				LogHandler::Log( 'utilities', 'INFO', 'stripHighResAdvertBasePath: Stripped Windows high-res base path ['.$sStoreWin.'] from file ['.$highresfile.']' );
			}
		}
		
		// Adverts in Macintosh
		if( $pos === false ) {
			$sStoreMac = BizSettings::isFeatureEnabled('HighResStoreMac') ? trim(BizSettings::getFeatureValue('HighResStoreMac')) : '';
			if( $sStoreMac == '' ) {
				$sStoreMac = defined('HIGHRES_STORE_MAC') ? trim(HIGHRES_STORE_MAC) : '';
			}
		  if( $sStoreMac != '' ) {
				$pos = stripos( $highresfile, $sStoreMac );
				if( $pos !== false ) {
					$highresfile = substr( $highresfile, strlen($sStoreMac) );
					LogHandler::Log( 'utilities', 'INFO', 'stripHighResAdvertBasePath: Stripped Macintosh highres base path ['.$sStoreMac.'] from file ['.$highresfile.']' );
				}
			}
		}
		
		if( $pos === false ) {
			LogHandler::Log( 'utilities', 'WARN', 'stripHighResAdvertBasePath: file ['.$highresfile.'] is not stored at base path. (Check HighResStoreMac and HighResStoreWin settings)' );
		}
		return $highresfile;
	}
	
	/**
	 * Makes high-res relative to high-res image store. <br/>
	 * See {@link stripHighResBasePath} for more details. <br/>
	 *
	 * @param $highresfile string  Absolute path to high-res image file.
	 * @return string              Relative file path (without base folder).
	 */
	private static function stripHighResImageBasePath( $highresfile )
	{
		$pos = false;

		// Images on Windows
		$sStoreWin = BizSettings::isFeatureEnabled('HighResImageStoreWin') ? trim(BizSettings::getFeatureValue('HighResImageStoreWin')) : '';
		if( $sStoreWin == '' ) {
			$sStoreWin = defined('HIGHRES_IMAGE_STORE_WIN') ? trim(HIGHRES_IMAGE_STORE_WIN) : '';
		}
  		if( $sStoreWin != '' ) {
			$pos = stripos( $highresfile, $sStoreWin );
			if( $pos !== false ) {
				$highresfile = substr( $highresfile, strlen($sStoreWin) );
				LogHandler::Log( 'utilities', 'INFO', 'stripHighResImageBasePath: Stripped Windows high-res image base path ['.$sStoreWin.'] from file ['.$highresfile.']' );
			}
		}

		// Images on Macintosh
		if( $pos === false ) {
			$sStoreMac = BizSettings::isFeatureEnabled('HighResImageStoreMac') ? trim(BizSettings::getFeatureValue('HighResImageStoreMac')) : '';
			if( $sStoreMac == '' ) {
				$sStoreMac = defined('HIGHRES_IMAGE_STORE_MAC') ? trim(HIGHRES_IMAGE_STORE_MAC) : '';
			}
		  if( $sStoreMac != '' ) {
				$pos = stripos( $highresfile, $sStoreMac );
				if( $pos !== false ) {
					$highresfile = substr( $highresfile, strlen($sStoreMac) );
					LogHandler::Log( 'utilities', 'INFO', 'stripHighResImageBasePath: Stripped Macintosh high-res base image path ['.$sStoreMac.'] from file ['.$highresfile.']' );
				}
			}
		}
		
		if( $pos === false ) {
			LogHandler::Log( 'utilities', 'WARN', 'stripHighResImageBasePath: file ['.$highresfile.'] is not stored at base path. (Check HighResImageStoreMac and HighResImageStoreWin settings)');
		}
		return $highresfile;
	}
}