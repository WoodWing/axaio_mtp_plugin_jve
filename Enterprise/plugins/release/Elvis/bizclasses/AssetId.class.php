<?php
/**
 * Elvis asset id - Enterprise shadow object id mapping.
 *
 * @since      10.5.0 Class functions originate from util/ElvisUtils.class.php
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_AssetId
{
	/**
	 * Extracts Elvis asset id from alien id provided by enterprise
	 *
	 * @since 10.5.0 Renamed function from getElvisId into getAssetIdFromAlienId
	 * @param string $alienId Enterprise alien object id (including _<ContentSourceId>_ prefix)
	 * @return string Elvis asset id
	 */
	public static function getAssetIdFromAlienId( $alienId )
	{
		// Note that str_replace() is used because sometimes Enterprise prefixes the $alienId with two prefixes.
		// Bad example: _ELVIS__ELVIS_<ASSETID>
		// How it should be: _ELVIS_<ASSETID>
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return str_replace( ELVIS_CONTENTSOURCEPREFIX, '', $alienId );
	}

	/**
	 * Tells whether a given Enterprise alien object id belongs to the Elvis content source.
	 *
	 * @since 10.5.0 Renamed function from isElvisId into isElvisAssetId
	 * @param string $alienId
	 * @return bool
	 */
	public static function isElvisAssetId( $alienId )
	{
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return strpos( $alienId, ELVIS_CONTENTSOURCEPREFIX ) !== false;
	}

	/**
	 * Compose an Enterprise alien object id for a given Elvis asset id.
	 *
	 * @since 10.5.0 Renamed function from getAlienId into getAlienIdFromAssetId
	 * @param string $assetId
	 * @return string Alien id
	 */
	public static function getAlienIdFromAssetId( $assetId )
	{
		require_once __DIR__.'/../config.php'; // ELVIS_CONTENTSOURCEPREFIX
		return ELVIS_CONTENTSOURCEPREFIX.$assetId;
	}
}