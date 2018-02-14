<?php

/**
 * @package    	Enterprise
 * @subpackage  DBClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBConfig extends DBBase
{
	const TABLENAME = 'config';
	
	/**
	 * This method returns the version of the Enterprise Server database model is stored 
	 * in the smart_config table. This version was formerly called SCE, which stands
	 * for Smart Connection Enterprise.
	 *
	 * @return string|null Version string when found, or null when not found.
	 */
	static public function getSCEVersion()
	{
		return self::getValue( 'version', true );
	}

	/**
	 * This function returns a setting stored in the smart_config table.
	 *
	 * @param string Property name to get value for.
	 * @param bool $checkTableSpace Whether or not to check if the DB table space exists.
	 * @return string|null Value string when found, or null when not found.
	 */
	static public function getValue( $property, $checkTableSpace = false )
	{
		if( $checkTableSpace ) {
			$dbDriver = DBDriverFactory::gen();
			$continue = $dbDriver->tableExists( self::TABLENAME );
		} else {
			$continue = true;
		}
		$value = null;
		if( $continue ) {
			$where = '`name` = ?';
			$params = array( $property );
			$row = self::getRow( self::TABLENAME, $where, array('value'), $params );
			if( $row ) {
				$value = $row['value'];
			}
		}
		return $value;
	}

	/**
	 * Returns customer contact info (Name and address particulars).
	 * @return array Array with the rows containing the contact info.
	 * @todo Add row to object.
	 */
	static public function getContactInfo()
	{
		require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';
		self::clearError();
		$escaped = DBQuery::escape4like('contactinfo_', '|');
		$where = "`name` LIKE '%$escaped%' ESCAPE '|' ";
		$contactInfoRows = self::listRows(self::TABLENAME, '', '', $where, '*');

		return $contactInfoRows;
	}	

	/**
	 * Stores a DBConfig value in the database.
	 *
	 * Inserts or updates the database value for a specific
	 * DbConfig variable.
	 *
	 * @param string $name The name of the DBConfig setting.
	 * @param string $value The value to store for the DBConfig setting.
	 * @return bool whether the insert/update was successful or not.
	 */
	static public function storeValue( $name, $value )
	{
		$where = '`name` = ?';
		$params = array( strval( $name ) );
		$row = self::getRow(self::TABLENAME, $where, array( 'id' ), $params);
		if (!$row) {
			$result = self::insertRow(self::TABLENAME, array( 'name' => $name, 'value' => '#BLOB#'), true, $value );
		} else {
			$result = self::updateRow(self::TABLENAME, array('value' => '#BLOB#'), $where, $params, $value );
		}
		return $result;
	}	
}
