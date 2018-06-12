<?php

/**
 * Utils to compare versions.
 *
 * @package Enterprise
 * @subpackage utils
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class VersionUtils
{

	/**
	 * By default the PHP native version_compare function returns that 8.0 and 8.0.0
	 * aren't the same versions. 8.0 is smaller then 8.0.0.
	 *
	 * This function adds .0 to the version info that has less version numbers.
	 * So in this case 8.0 will become 8.0.0 when it is compared to 8.0.0.
	 *
	 * @param string $versionOne
	 * @param string $versionTwo
	 * @param null   $operator
	 *
	 * @return mixed By default, version_compare returns
	 * -1 if the first version is lower than the second,
	 * 0 if they are equal, and
	 * 1 if the second is lower.
	 *
	 * When using the optional operator argument, the
	 * function will return true if the relationship is the one specified
	 * by the operator, false otherwise.
	 */
	public static function versionCompare( $versionOne, $versionTwo, $operator = null )
	{
		// Standardise versions
		$v1 = explode('.', trim($versionOne));
		$v2 = explode('.', trim($versionTwo));

		// Add 0 to the smaller version info
		if ( count($v1) > count($v2) ) {
			$v2 = array_merge( $v2, array_fill(count($v2), count($v1) - count($v2), '0') );
		} else if ( count($v2) > count($v1) ) {
			$v1 = array_merge( $v1, array_fill(count($v1), count($v2) - count($v1), '0') );
		}

		return version_compare( implode('.', $v1), implode('.', $v2), $operator );
	}
	
	/**
	 * Retrieves the major version from a given version string.
	 * Format of given string should be: <major>.<minor>[.<patch>][ Build <nr>]
	 * So only major- and minor version are mandatory.
	 *
	 * @since v9.0.0
	 * @param string $version
	 * @return array|bool List with parsed version info: major, minor, patch, build
	 */
	public static function getVersionInfo( $version )
	{
		$versionInfo = array();
		$versionSegments = explode( ' ', $version );
		if( count($versionSegments) == 0 ) {
			$versionSegments = array( $version );
		}
		if( count($versionSegments) > 0 ) {
			$versionDigits = explode( '.', $versionSegments[0] );
			if( count( $versionDigits ) > 0 ) {
				$versionInfo['major'] = intval( $versionDigits[0] );
			} else {
				$versionInfo = false; // major version is mandatory
			}
			if( count( $versionDigits ) > 1 ) {
				$versionInfo['minor'] = intval( $versionDigits[1] );
			} else {
				$versionInfo = false; // minor version is mandatory
			}
			if( count( $versionDigits ) > 2 ) {
				$versionInfo['patch'] = intval( $versionDigits[2] );
			}
		} else {
			$versionInfo = false;
		}
		if( count($versionSegments) > 2 ) { // 3 segments: 4.5.6 Build 789
			$versionInfo['build'] = intval( $versionSegments[2] );
		}
		return $versionInfo;
	}

	/**
	 * Compose a N-digit formatted version, given a human readable version string.
	 *
	 * For example, when requested for 2 digits, a <major>.<minor> string is returned.
	 *
	 * @since 10.5.0
	 * @param string $version Human readable version. Format of given string should be: <major>.<minor>[.<patch>][ Build <nr>]
	 * @param int $digits Number of digits to return in the range of 1-4.
	 * @return string <major>[.<minor>[.<patch>[.<build>]]]
	 */
	public static function getVersionDigits( $version, $digits )
	{
		$versionInfo = self::getVersionInfo( SERVERVERSION );
		$version = $versionInfo['major'];
		if( $digits >= 2 ) {
			$version .= '.'.$versionInfo['minor'];
		}
		if( $digits >= 3 ) {
			$version .= '.'.$versionInfo['patch'];
		}
		if( $digits >= 4 ) {
			$version .= '.'.$versionInfo['build'];
		}
		return $version;
	}
}