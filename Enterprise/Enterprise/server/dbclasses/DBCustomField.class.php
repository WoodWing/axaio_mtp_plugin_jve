<?php
/**
 * @since 		v6.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Maintains custom fields that are reflected to the standard shipped DB model.
 * DB fields (columns) can be dynamically added/updated/removed for any DB table.
 * Typically used for custom workflow object properties and custom admin properties.
 * Object properties are setup through MetaData Setup page and admin properties through
 * custom Server Plug-ins.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBCustomField extends DBBase
{
	/**
	 * Inserts a (custom) field at a given DB table.
	 *
	 * @param string $table Name of DB table, excluding "smart_" prefix.
	 * @param string $columnName Name of the  field to be added to table.
	 * @param string $dbtype Data type of the field.
	 * @return bool success
	 */
	public static function insertFieldAtModel( $table, $columnName, $dbtype )
	{
		$dbDriver = DBDriverFactory::gen();
		$fieldInfos = array( array( 'Name' => $columnName, 'Type' => $dbtype ));
		return $dbDriver->addColumnsToTable( $table, $fieldInfos );
	}

	/**
	 * Inserts (custom) fields at a given DB table.
	 *
	 * @param string $tableName of DB table, excluding "smart_" prefix.
	 * @param array $fieldInfos Names of the  fields to be added and their types.
	 * @return bool success
	 */
	public static function insertFieldsAtModel( $tableName, $fieldInfos )
	{
		$dbDriver = DBDriverFactory::gen();
		return $dbDriver->addColumnsToTable( $tableName, $fieldInfos);
	}

	/**
	 * Updates a (custom) field definitions at a given DB table.
	 *
	 * @param string $tableName Name of DB table, excluding "smart_" prefix.
	 * @param string $columnName Name of the field to be updated at table.
	 * @param string $dbType Data type of the field.
	 * @return bool true when successful else false
	 */
	public static function updateFieldAtModel( $tableName, $columnName, $dbType )
	{
		$dbDriver = DBDriverFactory::gen();
		return $dbDriver->updateColumnDefinition( $tableName, $columnName, $dbType);
	}

	/**
	 * Updates custom fields definitions at a given DB table. Only applicable for mysql.
	 *
	 * @param string $tableName of DB table, excluding "smart_" prefix.
	 * @param array $fieldInfos Names of the custom fields to be added and their types.
	 * @return bool success
	 * @throws BizException
	 */
	public static function updateFieldsAtModel( $tableName, $fieldInfos )
	{
		$dbDriver = DBDriverFactory::gen();
		return $dbDriver->updateMultipleColumnDefinition( $tableName, $fieldInfos );
	}

	/**
	 * Deletes a custom field at a given DB table.
	 *
	 * @param string $tableName Name of DB table, excluding "smart_" prefix.
	 * @param string $columnName Name of the custom field to be removed from table.
	 * @return bool true when successful else false
	 */
	public static function deleteFieldAtModel( $tableName, $columnName )
	{
		$dbDriver = DBDriverFactory::gen();
		return $dbDriver->deleteColumnsFromTable( $tableName, array( $columnName ));
	}

	/**
	 * Deletes multiple custom fields at a given DB table.
	 *
	 * @param string $tableName Name of DB table, excluding "smart_" prefix.
	 * @param array $columnNames Array of names of the custom fields to be removed from table.
	 * @return bool true when successful else false
	 */
	public static function deleteFieldsAtModel( $tableName, $columnNames )
	{
		$dbDriver = DBDriverFactory::gen();
		return $dbDriver->deleteColumnsFromTable( $tableName, $columnNames );
	}

	/**
	 * Checks if custom field exists at given DB table. If so, it returns its (DB) type.
	 *
	 * @param string $table Name of DB table, excluding "smart_" prefix.
	 * @return array with "name" and "type".
	 */
	static public function getFieldsAtModel( $table )
	{
		$dbDriver = DBDriverFactory::gen();
		$table = $dbDriver->tablename( $table );

		$sql = "SELECT * FROM $table WHERE 1=0"; // Do a trick to get no rows, but all columns
		$sth = $dbDriver->query( $sql );
		if( !$sth ) {
			return null;
		}
		// Retrieve name+type info of all columns
		$fields = $dbDriver->tableInfo( $sth );
		$retFields = array();
		foreach( $fields as $field ) {
			$name = $field['name'];
			$retFields[$name] = $field;
		}
		return $retFields;
	}
}
