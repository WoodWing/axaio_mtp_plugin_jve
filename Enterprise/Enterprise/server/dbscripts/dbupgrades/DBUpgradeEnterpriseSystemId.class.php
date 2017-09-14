<?php
/**
 * Create new Enterprise System Id when it not exists in the smart_config table.
 *
 * The following tables are updated:
 * - smart_config
 *
 * Insert a new row with name = 'enterprise_system_id' and value = GUID in the smart_config table.
 * 
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.2.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/DbUpgradeModule.class.php';

class DBUpgradeEnterpriseSystemId extends DbUpgradeModule
{
	const NAME = 'EnterpriseSystemId';

	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name 
	 */
	protected function getUpdateFlag()
	{
		 // Global unique identifier of an Enterprise system installation.
		 return 'enterprise_system_id'; // Important: never change this name
	}

	/**
	 * Check if the enterprise system id exists in config table.
	 *
	 * @return bool Whether or not the field exists.
	 */
	public function isUpdated()
	{
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		require_once BASEDIR . '/server/utils/NumberUtils.class.php';
		$flagValue = DBConfig::getValue( $this->getUpdateFlag(), true );
		return !empty($flagValue) && NumberUtils::validateGUID($flagValue);
	}

	/**
	 * Insert a new Enterprise System Id in smart_config table.
	 * The insert is only done if no Enterprise System Id is ever created before. (See EN-87314).
	 *
	 * @return bool Whether or not the update was successful.
	 */
	public function setUpdated()
	{
		$stored = false;
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		require_once BASEDIR . '/server/utils/NumberUtils.class.php';
		if ( self::isUpdated() ) {
			$stored = true;
		}
		if ( !$stored ) {
			$stored = DBConfig::storeValue( $this->getUpdateFlag(), NumberUtils::createGUID() );
			if( !$stored ) {
				LogHandler::Log( self::NAME, 'ERROR', 'Failed updating flag '.$this->getUpdateFlag() );
			}
		}

		return $stored;
	}

	/**
	 * The real update or insert is done in the setUpdated function.
	 * Since it didn't involve complicate update,therefore keep run() function simple and return true.
	 *
	 * @return bool Whether or not the conversion was succesful.
	 */
	public function run()
	{
		return true;
	}

	public function introduced()
	{
		return '8.0';
	}

}
