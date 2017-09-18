<?php

/**
 * Abstract class that defines an interface for a provider of a DB model definition.
 *
 * @package    Enterprise
 * @subpackage DbModel
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

abstract class WW_DbModel_Provider
{
	/** @var array DB model definition. */
	protected $struct;

	/** @var array Patches on the DB model. */
	protected $patches;

	/**
	 * Constructor.
	 *
	 * @param array $struct
	 * @param array $patches
	 */
	public function __construct( $struct, $patches )
	{
		$this->struct = $struct;
		$this->patches = $patches;
	}

	/**
	 * Provides the DB model definition.
	 *
	 * @return array
	 */
	public function getStruct()
	{
		return $this->struct;
	}

	/**
	 * Provides patches on the DB model.
	 *
	 * @return array
	 */
	public function getPatches()
	{
		return $this->patches;
	}

	/**
	 * Retrieve the table name prefix used by all tables in the DB model.
	 *
	 * @return string
	 */
	abstract public function getTablePrefix();

	/**
	 * All DB model versions ever made in history.
	 *
	 * The versions are sorted from oldest to latest.
	 * The last version is assumed to be the current/latest/wanted DB model version.
	 *
	 * IMPORTANT: Add new versions at the -end- of list!
	 *
	 * @return string[] with versions.
	 */
	abstract public function getVersions();

	/**
	 * Retrieve the current/latest/wanted DB model version.
	 *
	 * This is the lastest version provided by the model (not to be confused with the latest installed version).
	 *
	 * @return string version in 'major.minor' notation
	 */
	public function getVersion()
	{
		$versions = $this->getVersions();
		return end( $versions );
	}

	/**
	 * Those DB model versions for which SQL scripts are provided to upgrade to the current/latest/wanted version.
	 *
	 * This does NOT include:
	 * - too old versions for which the SQL upgrade scripts are no longer shipped in the dbscripts folder
	 * - the current/latest/wanted DB model version itself
	 *
	 * @return string[] the versions in 'major.minor' notation.
	 */
	public function getDbMigrationVersions()
	{
		$versions = $this->getVersions();
		array_pop( $versions ); // pop off the last entry which is assumed to be the lastest version
		return $versions;
	}

	// TODO: headers below here ...

	public function getTablesWithoutAutoIncrement()
	{
		return array();
	}

	public function getCategorizedTableNames()
	{
		return array();
	}
}