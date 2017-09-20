<?php
/**
 * Updates Object and Property tables on MySQL.
 *
 * Database update script to update a MySQL database to use TINYTEXT instead of VARCHAR  
 * for custom properties of type string/list. This update is only executed once and only 
 * on MySQL databases. Not applicable for MSSQL and Oracle.
 *
 * The following tables are updated:
 * - smart_objects
 * - smart_deletedobjects
 *
 * A flag defined in the {@link getUpdateFlag()} function is stored in smart_config to  
 * specify whether or not the conversion was already done correctly before.
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v8.2.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/dbupgrades/Module.class.php';

class WW_DbScripts_DbUpgrades_VarCharToTinyText extends WW_DbScripts_DbUpgrades_Module
{
	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name 
	 */
	protected function getUpdateFlag()
	{
		return 'dbadmin_string_to_tinytext'; // Important: never change this name
	}
	
	/**
	 * The string/list custom properties are stored as VARCHAR in the smart_objects and 
	 * smart_deletedobjects tables. This function converts those fields into TINYTEXT.
	 * This update only needed (and fixed) for MySQL. Not applicable for MSSQL and Oracle.
	 *
	 * @return bool Whether or not the conversion was succesful.
	 */
	public function run()
	{
		$retVal = true;
		
		// Only deal with MySQL, since this upgrade is not applicable for MSSQL/Oracle.
		if( DBTYPE == 'mysql' ) {

			// Get a list of all the custom object properties available (configured).
			require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
			require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
			$customProperties = DBProperty::listCustomPropertyTypes();

			// Change the field type of string/list properties from VARCHAR to TINYTEXT.
			if( $customProperties ) foreach( $customProperties as $propName => $propType ) {
				if( BizProperty::isCustomPropertyName( $propName ) && ( $propType === 'string' || $propType === 'list' ) ) {
					// Handle workflow objects.
					$sql = 'ALTER TABLE `smart_objects` MODIFY `' . $propName . '` TINYTEXT; ';
					if( !self::updateTable( $sql , 'smart_objects')) {
						$retVal = false;
					}
					// Handle objects in Trash Can.
					$sql = 'ALTER TABLE `smart_deletedobjects` MODIFY `' . $propName . '` TINYTEXT; ';
					if( !self::updateTable( $sql, 'smart_deletedobjects') ) {
						$retVal = false;
					}
				}
			}
		}
		return $retVal;
	}

	public function introduced()
	{
		return '9.0';
	}

	/**
	 * Executes a given SQL query against the database.
	 *
	 * @static
	 * @param string $query
	 * @param string $tableName DB table name (used for logging only).
	 * @return bool Whether or not the query was succesful.
	 */
	static private function updateTable( $query, $tableName )
	{
		// Generate a DB handle.
		if ( !$dbh = DBDriverFactory::gen() ) {
			LogHandler::Log( __CLASS__, 'ERROR',
				'General MySQL error occured when updating table `' . $tableName . '`' );
			return false;
		}

		// Execute the query to update a table.
		$sth = $dbh->query( $query );
		if ( !$sth ) {
			LogHandler::Log( __CLASS__, 'ERROR',
				'Custom properties in table `' . $tableName . '` could not be modified.' );
			return false;
		}

		// Succesful completion, log and return.
		LogHandler::Log( __CLASS__, 'INFO',
			'Table `' . $tableName . '` modified succesfully.' );
		return true;
	}
}
