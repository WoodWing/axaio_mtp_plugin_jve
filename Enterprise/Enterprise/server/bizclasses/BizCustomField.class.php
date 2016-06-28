<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v7.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Maintains custom object properties that are reflected to the standard shipped DB model.
 * Therefore DB columns are dynamically added/updated/removed for the object DB tables
 * (smart_objects and smart_deletedobjects). Having that in place, custom workflow objects 
 * can carry extra custom object properties. 
 * Those properties are setup (1) manually, through MetaData Setup page by system admin
 * users, or (2) automatically, through custom Server Plug-ins that implement the
 * CustomObjectMetaData_EnterpriseConnector connector interface.
 */

class BizCustomField
{
	/**
	 * Inserts a custom field at a given DB table.
	 *
	 * @param string $table Name of DB table, excluding "smart_" prefix.
	 * @param string $name Name of the custom field to be updated to table.
	 * @param string $type Type of the custom field.
	 * @throws BizException Throws a BizException if the column cannot be added.
	 */
	public static function insertFieldAtModel( $table, $name, $type )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';

		if( !BizProperty::isCustomPropertyName( $name ) ) {
			throw new BizException( null, 'Client', null, 'Not a custom property given for '.$name.
				'. Custom properties must have "C_" prefix: ' ); // TODO: change message ???
		}

		$dbType = BizProperty::convertCustomPropertyTypeToDB( $type , $table );
		$ok = DBCustomField::insertFieldAtModel( $table, $name, $dbType );
		if( $ok ) {
			// keep smart_deletedobjects table in sync with smart_objects table
			if( $table == 'objects' ) {
				$table = 'deletedobjects';
				$ok = DBCustomField::insertFieldAtModel( $table, $name, $dbType );
			}
		}
		if( !$ok ) {
			throw new BizException( 'ERR_PROP_DB_ERROR', 'Server', 'Cannot insert a new field "'.$name.
									'" into table "'.$table.'"' );
		}
	}

	/**
	 * Adds multiple custom fields to given DB tables.
	 *
	 * @param array $tableInfos of DB tables, indexed by the name of the table (excluding "smart_" prefix) plus
	 * fields to add, e.g. ['objects'] =>
	 *                          ['Name' => 'ColumnName', 'Type' => data type ]
	 * @throws BizException in case the columns cannot be added.
	 */
	public static function insertMultipleFieldsAtModel( $tableInfos )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';

		$dbTableInfo = $tableInfos;
		if ( $tableInfos ) foreach ( $tableInfos as $tableName => $fieldInfos) {
			if ( $fieldInfos ) foreach ( $fieldInfos as $key => $fieldInfo ) {
				if( !BizProperty::isCustomPropertyName( $fieldInfo['Name'] ) ) {
					throw new BizException( null, 'Client', null, 'Not a custom property given for '.$fieldInfo['Name'].
					'. Custom properties must have "C_" prefix: ' ); // TODO: change message ???
				}
				$dbType = BizProperty::convertCustomPropertyTypeToDB( $fieldInfo['Type'], $tableName);
				$dbTableInfo[$tableName][$key]['Type'] = $dbType;
			}
			$ok = DBCustomField::insertFieldsAtModel( $tableName, $dbTableInfo[$tableName] );
			if( $ok ) {
				if( $tableName == 'objects' ) {
					$tableName = 'deletedobjects';
					// Keep smart_deletedobjects table in sync with smart_objects table.
					$ok = DBCustomField::insertFieldsAtModel( $tableName, $dbTableInfo['objects'] );
				}
			}
			if( !$ok ) {
				throw new BizException( 'ERR_PROP_DB_ERROR', 'Server', 'Adding fields to table '.$tableName.' failed.' );
			}
        }
	}

	/**
	 * Updates a custom field at a given DB table.
	 *
	 * @param string $table Name of DB table, excluding "smart_" prefix.
	 * @param string $name Name of the custom field to be updated to table.
	 * @param string $type Type of the custom field.
	 * @throws BizException Throws BizException when the update fails.
	 */
	public static function updateFieldAtModel( $table, $name, $type )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';

		if( !BizProperty::isCustomPropertyName( $name ) ) {
			throw new BizException( null, 'Client', null, 'Not a custom property given for '.$name.
				'. Custom properties must have "C_" prefix: ' );
		}

		$dbType = BizProperty::convertCustomPropertyTypeToDB( $type, $table );
		$ok = DBCustomField::updateFieldAtModel( $table, $name, $dbType );
		if( $ok ) {
			// keep smart_deletedobjects table in sync with smart_objects table
			if( $table == 'objects' ) {
				$table = 'deletedobjects';
				$ok = DBCustomField::updateFieldAtModel( $table, $name, $dbType );
			}
		}
		if( !$ok ) {
			//self::insertFieldAtModel( $table, $name, $type );
			throw new BizException( 'ERR_PROP_DB_ERROR', 'Server', 'Cannot update existing field "'.$name.
									'" in table "'.$table.'"' );
		}
	}

	/**
	 * Updates multiple custom fields to given DB tables. At this moment only mysql can change multiple columns in one
	 * statement. Mssql doesn't support it all, Oracle has support for it but not for columns of the type clob.
	 *
	 * @param array $tableInfos of DB tables, indexed by the name of the table (excluding "smart_" prefix) plus
	 * fields to add, e.g. ['objects'] =>
	 *                          ['Name' => 'ColumnName', 'Type' => data type ]
	 * @throws BizException in case the columns cannot be updated.
	 */
	public static function updateMultipleFieldsAtModel( $tableInfos )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';

		$dbTableInfo = $tableInfos;
		if ( $tableInfos ) foreach ( $tableInfos as $tableName => $fieldInfos) {
			if ( $fieldInfos ) foreach ( $fieldInfos as $key => $fieldInfo ) {
				if( !BizProperty::isCustomPropertyName( $fieldInfo['Name'] ) ) {
					throw new BizException( null, 'Client', null, 'Not a custom property given for '.$fieldInfo['Name'].
						'. Custom properties must have "C_" prefix: ' ); // TODO: change message ???
				}
				$dbType = BizProperty::convertCustomPropertyTypeToDB( $fieldInfo['Type'], $tableName);
				$dbTableInfo[$tableName][$key]['Type'] = $dbType;
			}
			$ok = DBCustomField::updateFieldsAtModel( $tableName, $dbTableInfo[$tableName] );
			if( $ok ) {
				if( $tableName == 'objects' ) {
					$tableName = 'deletedobjects';
					// Keep smart_deletedobjects table in sync with smart_objects table.
					$ok = DBCustomField::updateFieldsAtModel( $tableName, $dbTableInfo['objects'] );
				}
			}
			if( !$ok ) {
				throw new BizException( 'ERR_PROP_DB_ERROR', 'Server', 'Adding fields to table '.$tableName.' failed.' );
			}
		}
	}


	/**
	 * Deletes a custom field from a given DB table.
	 *
	 * @param string $table Name of DB table, excluding "smart_" prefix.
	 * @param string $name Name of the custom field to be removed from table.
	 * @throws BizException Throws BizException when the delete fails.
	 */
	public static function deleteFieldAtModel( $table, $name )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';

		if( !BizProperty::isCustomPropertyName( $name ) ) {
			throw new BizException( null, 'Client', null, 'Not a custom property given for '.$name.
				'. Custom properties must have "C_" prefix: ' );
		}

		$ok = DBCustomField::deleteFieldAtModel( $table, $name );
		if( $ok ) {
			// keep smart_deletedobjects table in sync with smart_objects table
			if( $table == 'objects' ) {
				$table = 'deletedobjects';
				$ok = DBCustomField::deleteFieldAtModel( $table, $name );
			}
		}
		if( !$ok ) {
			throw new BizException( 'ERR_PROP_DB_ERROR', 'Server', 'Cannot delete existing field "'.$name.
									'" in table "'.$table.'"' );
		}
	}

	/**
	 * Deletes multiple custom fields from a given DB table.
	 *
	 * @param string $table Name of DB table, excluding "smart_" prefix.
	 * @param array $columnNames Array of name of the custom fields to be removed from table.
	 * @throws BizException Throws BizException when the delete fails.
	 * @return bool true in case of a successful delete of the properties.
	 */
	public static function deleteFieldsAtModel( $table, $columnNames )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';
		if( $columnNames ) foreach( $columnNames as $columnName ) {
			if( !BizProperty::isCustomPropertyName( $columnName ) ) {
				throw new BizException( null, 'Client', null, 'Not a custom property given for '.$columnName.
					'. Custom properties must have a "C_" prefix.' );
			}
		}

		$ok = DBCustomField::deleteFieldsAtModel( $table, $columnNames );
		if( !$ok ) {
			throw new BizException( 'ERR_PROP_DB_ERROR', 'Server', 'Cannot delete existing fields "'.implode('","',$columnNames).
				'" of table "'.$table.'"' );
		}
		return true;
	}

	/**
	 * Checks if custom field exists at given DB table. If so, it returns its (DB) type.
	 *
	 * @param string $table Name of DB table, excluding "smart_" prefix.
	 * @return array with "name" and "type".
	 */
	static public function getFieldsAtModel( $table )
	{
		require_once BASEDIR.'/server/dbclasses/DBCustomField.class.php';
		return DBCustomField::getFieldsAtModel( $table );
	}
	
	/**
	 * Returns an array of widget types that should be excluded when updating the model.
	 *
	 * @static
	 * @return string[] An array of types to be excluded from the model.
	 */
	public static function getExcludedObjectFields()
	{
		// Fields of type `file` or `articlecomponent` or the selectors should not be inserted into the model.
		return array('file', 'articlecomponent', 'fileselector', 'articlecomponentselector');
	}
}