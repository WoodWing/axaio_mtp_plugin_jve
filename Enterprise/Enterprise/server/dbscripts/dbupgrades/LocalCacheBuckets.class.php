<?php
/**
 * Create a new 'local_cache_buckets' entry when it not exists in the smart_config table.
 *
 * The following tables are updated:
 * - smart_config
 *
 * Insert a new row with name = 'local_cache_buckets' and value = array() in the smart_config table.
 * 
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/dbupgrades/Module.class.php';

class WW_DbScripts_DbUpgrades_LocalCacheBuckets extends WW_DbScripts_DbUpgrades_Module
{
	/**
	 * @inheritdoc
	 */
	protected function getUpdateFlag()
	{
		// Global unique identifier used to version check locally cached data.
		return 'local_cache_buckets'; // Important: never change this name
	}

	/**
	 * Check if the 'local_cache_buckets' entry exists in config table.
	 *
	 * @return bool Whether or not the field exists.
	 */
	public function isUpdated()
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$flagValue = DBConfig::getValue( $this->getUpdateFlag(), true );
		return !is_null( $flagValue );
	}

	/**
	 * Insert a new 'local_cache_buckets' entry in smart_config table.
	 *
	 * It does not update nor clear this entry if it already exists.
	 *
	 * @return bool Whether or not the update was successful.
	 */
	public function setUpdated()
	{
		if( self::isUpdated() ) {
			return true;
		}
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$stored = DBConfig::storeValue( $this->getUpdateFlag(), serialize( array() ) );
		if( !$stored ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Failed updating flag '.$this->getUpdateFlag() );
		}
		return $stored;
	}

	/**
	 * Perform the DB upgrade, however the upgrade is already done in the setUpdated() function so there is nothing to do.
	 *
	 * @return bool Whether or not the DB upgrade was successful.
	 */
	public function run()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function introduced()
	{
		return '10.5'; // since 10.5.0 (to be exact)
	}
}
