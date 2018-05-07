<?php
/**
 * Base class used to implement DB upgrade scripts.
 *
 * Once an upgrade script has ran successfully, this class can be called to flag the update with setUpdated().
 * Then isUpdated() can be called to determine whether or not the script was run before.
 *
 * IMPORTANT: DB upgrade modules should focus on -data- migration, rather than trying to migrate the -model- itself.
 *            To change the model please use definitions in the server/dbmodel/Definition.class.php module instead.
 *
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
 
abstract class WW_DbScripts_DbUpgrades_Module
{
	/**
	 * Returns whether or not the DB conversion has already been completed before or not.
	 *
	 * @return bool Whether or not the DB conversion has been completed.
	 */
	public function isUpdated()
	{
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		$flagValue = DBConfig::getValue( $this->getUpdateFlag(), true );
		return $flagValue == '1';
	}

	/**
	 * Stores a variable in the database to denote that the DB conversion was done succesfully.
	 *
	 * @return bool Whether or not the updated flag was set correctly.
	 */
	public function setUpdated()
	{
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		$stored = DBConfig::storeValue( $this->getUpdateFlag(), '1' );
		if( !$stored ) {
			LogHandler::Log( 'WW_DbScripts_DbUpgrades_Module', 'ERROR', 'Failed updating flag '.$this->getUpdateFlag() );
		}
		return $stored;
	}
	
	/**
	 * The flag name that must be used to flag a DB conversion. This flag name is stored
	 * as field name in the smart_config table and therefore it should be system wide unique.
	 *
	 * @return string Flag name
	 */
	abstract protected function getUpdateFlag();

	/**
	 * Runs the DB migration procedure.
	 *
	 * @static
	 */
	abstract public function run();

	/**
	 *
	 */
	abstract public function introduced();
}
