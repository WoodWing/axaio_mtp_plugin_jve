<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizSettings
{
	private static $FeatureSet;
	private static $LdapServers;
	
	/*
	 * Determines whether a server feature is enabled or not. <br>
	 * Searches through the configured feature set. <br>
	 * Returns null when feature is disabled (not found). <br>
	 *
	 * @param $Key string Feature key to search for.
	 * @return object Feature.
	 */
	private static function findFeature( $key )
	{
		if( !isset( self::$FeatureSet ) ) {
			$featureSet = unserialize(SERVERFEATURES);
			foreach( $featureSet as $feature ) {
				self::$FeatureSet[$feature->Key] = $feature;
			}
		}
		return isset(self::$FeatureSet[$key]) ? self::$FeatureSet[$key] : null;
	}

	/*
	 * Returns the value of a configured server feature. <br>
	 * Typically used for key-value server features. <br>
	 * For key features use {@link isFeatureEnabled()} instead. <br>
	 * Returns null when feature is disabled (not found). <br>
	 *
	 * @param $Key string Feature key to search for.
	 * @return string Configured value of server feature.
	 */
	public static function getFeatureValue( $key )
	{
		$feature = self::findFeature( $key );
		return is_null( $feature ) ? null : $feature->Value;
	}

	/*
	 * Determines whether a server feature is configured or not. <br>
	 * Typically used for key server features. <br>
	 * For key-value features use {@link getFeatureValue()} instead. <br>
	 * Returns null when feature is disabled (not found). <br>
	 *
	 * @param $Key string Feature key to search for.
	 * @return boolean True when server feature configured.
	 */
	public static function isFeatureEnabled( $key )
	{
		$feature = self::findFeature( $key );
		return is_null( $feature ) ? false : true;
	}
	
	/*
	 * List of configured LDAP servers. <br>
	 * Optionally used for user access validation. <br>
	 * Returns null when not configured. <br>
	 *
	 * @return array LDAPServer objects.
	 */
	public static function getLdapServers()
	{
		if( defined( 'LDAP_SERVERS' ) ) {
			if( !isset( self::$LdapServers ) ) {
				self::$LdapServers = unserialize(LDAP_SERVERS);
			}
			return self::$LdapServers;
		} else {
			return null;
		}
	}
}