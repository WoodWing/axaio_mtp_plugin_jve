<?php
/**
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
//require_once BASEDIR.'/server/dbintclasses/<TableName>.class.php';

class DBTableName extends DBBase
{
	const TABLENAME = '<tablename>';
	const KEYCOLUMN = '<primary_key>'; //Normaly 'id'
	const DBINT_CLASS = 'WW_DBIntegrity_TableName';
	
	/**************************** Insert ******************************************/

	/**
	 * Inserts records with the new values for passed columns.  
	 * @param array $newValues column/value pairs of the columns to be inserted.
	 * @param $autoIncrement Apply auto increment for primary key (true/false).
	 * @return new id or else false.
	 */
	public static function insert(array $newValues, $autoIncrement)
	{
		return parent::insert(self::TABLENAME, self::DBINT_CLASS, $newValues, $autoIncrement);
	}		

	/**************************** Update ******************************************/
	/**
	 * Updates records with the new values for passed columns.  
	 * @param array $whereParams column/array of value pairs for where clause
	 * @param array $newValues column/value pairs of the columns to be updated.
	 * @return number of records updated or null in case of error.
	 */
	public static function update(array $whereParams, array $newValues)
	{
		return parent::update($whereParams, self::TABLENAME, self::KEYCOLUMN, self::DBINT_CLASS, $newValues);
	}	

	/**************************** Delete ******************************************/
	/**
	 * Deletes records .....  
	 * @param $whereParams column/array of value pairs for where clause
	 * @return number of records updated or null in case of error.
	 */	
	public static function delete($whereParams)
	{
		return parent::delete($whereParams, self::TABLENAME, self::KEYCOLUMN, self::DBINT_CLASS);
	}
	
	/**************************** Query *******************************************/
	
	/**************************** Other *******************************************/
}
