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
 * @since 		v9.2.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/dbupgrades/Module.class.php';

class WW_DbScripts_DbUpgrades_PublishHistory extends WW_DbScripts_DbUpgrades_Module
{
	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name 
	 */
	protected function getUpdateFlag()
	{
		return 'dbadmin_convert_publish_history'; // Important: never change this name
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
		$updateNeeded = false;
		$dbDriver = DBDriverFactory::gen();
		$tableName = $dbDriver->tablename( 'publishhistory' );

		$sql = "SELECT * FROM ". $tableName . " WHERE 1=0"; // Do a trick to get no rows, but all colums
		$sth = self::excecuteQuery( $dbDriver, $sql );

		// Retrieve name+type info of all columns
		$fields = $dbDriver->tableInfo( $sth );

		foreach( $fields as $field ){
			if( strtolower( $field['name'] ) == 'userid' ){
				$updateNeeded = true;
			}
		}

		if( $updateNeeded ){ // if this is false there is no update needed
			$userTableName = $dbDriver->tablename( 'users' );
			$updateUserSql = 'UPDATE ' . $tableName . ' SET `user` = ( SELECT u.`fullname` FROM ' . $userTableName . ' u WHERE `userid` = u.`id` )'; // update the user column

			if( !self::excecuteQuery( $dbDriver, $updateUserSql )){
				return false;
			}

			require_once BASEDIR.'/server/dbmodel/Reader.class.php';
			require_once BASEDIR.'/server/dbmodel/Definition.class.php';

			$definition = new WW_DbModel_Definition();
			$reader = new WW_DbModel_Reader( $definition );
			$column = array( 'name' => 'userid' );
			$historyTable = $reader->getTable( 'smart_publishhistory' );

			$sqlStatements = null;
			require_once BASEDIR.'/server/dbscripts/generators/Factory.class.php';
			$generator = WW_DbScripts_Generators_Factory::createGenerator( DBTYPE );
			switch( DBTYPE ){
				case 'mysql':
					$generator->dropField( $historyTable, $column );

					$sqlStatements = explode( ';', $generator->txt() );
					array_pop( $sqlStatements ); // remove the last empty element ( after the ; )

					break;
				case 'mssql':
					$generator->upgradePre();
					$generator->dropField( $historyTable, $column );
					$generator->upgradePost();

					$sqlStatements = explode( ';', $generator->txt() );
					array_pop( $sqlStatements ); // remove the last empty element ( after the ; )

					break;
			}

			if( $sqlStatements ) foreach( $sqlStatements as $sqlStatement ) {
				if( !self::excecuteQuery( $dbDriver, $sqlStatement )){
					return false;
				}
			}

			$objectsTableName = $dbDriver->tablename( 'objects' );
			$deletedObjectsTableName = $dbDriver->tablename( 'deletedobjects' );
			$objectHistTableName = $dbDriver->tablename( 'publishedobjectshist' );
			$addObjectMetaSql = '';
			$addDeletedObjectMetaSql = '';

			switch( DBTYPE ){
				case 'mssql':
					// add the metadata to the published objects if they exist in smart_objects
					$addObjectMetaSql = 'UPDATE ' . $objectHistTableName . ' SET `objectname` = o.`name`, `objecttype` = o.`type`, `objectformat` = o.`format` FROM ' . $objectsTableName . ' o WHERE `objectid` = o.`id` ';

					// add the metadata to the published objects if they exist in smart_deletedobjects, if the objects are permanently deleted we won't be able to fill the metadata.
					$addDeletedObjectMetaSql = 'UPDATE ' . $objectHistTableName . ' SET `objectname` = o.`name`, `objecttype` = o.`type`, `objectformat` = o.`format` FROM ' . $deletedObjectsTableName . ' o WHERE `objectid` = o.`id` ';

					break;
				case 'mysql':
					// add the metadata to the published objects if they exist in smart_objects
					$addObjectMetaSql = 'UPDATE ' . $objectHistTableName . ' INNER JOIN ' . $objectsTableName . ' o ON  `objectid` =  o.`id` SET  `objectname` =  o.`name` , `objecttype` =  o.`type` , `objectformat` =  o.`format`';

					// add the metadata to the published objects if they exist in smart_deletedobjects, if the objects are permanently deleted we won't be able to fill the metadata.
					$addDeletedObjectMetaSql = 'UPDATE ' . $objectHistTableName . ' INNER JOIN ' . $deletedObjectsTableName . ' o ON  `objectid` =  o.`id` SET  `objectname` =  o.`name` , `objecttype` =  o.`type` , `objectformat` =  o.`format`';

					break;
			}

			if( !self::excecuteQuery( $dbDriver, $addObjectMetaSql )){
				return false;
			}

			if( !self::excecuteQuery( $dbDriver, $addDeletedObjectMetaSql )){
				return false;
			}
		}
		return true;
	}

	public function introduced()
	{
		return '9.0';
	}

	/**
	 * Executes the query provided.
	 *
	 * @param WW_DbDrivers_DriverBase $dbDriver
	 * @param string $sqlStatement
	 * @return bool
	 */
	public static function excecuteQuery( $dbDriver, $sqlStatement )
	{
		$sth = $dbDriver->query( $sqlStatement );
		if( !$sth ) {
			LogHandler::Log( __CLASS__, 'ERROR' ,$dbDriver->error().' ('.$dbDriver->errorcode().')');
			return false;
		}
		return $sth;
	}
}
