<?php
/**
 * Because of bug that was fix by EN-85534, the table smart_placements and placementtiles can contain a lot of
 * duplicate placements. On the one hand this can have a severe negative impact on performance on the other hand this
 * results in plain data corruption. This script removes the duplicates.
 *
 * @package 	Enterprise
 * @subpackage 	dbscripts
 * @since 		v9.4.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/DbUpgradeModule.class.php';

class DBUpgradeRemoveDuplicatePlacements extends DbUpgradeModule
{
	const NAME = 'RemoveDuplicatePlacements';

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
			LogHandler::Log( 'DbUpgradeModule', 'ERROR', 'Failed updating flag '.$this->getUpdateFlag() );
		}
		return $stored;
	}

	/**
	 * The flag name that must be used to flag a DB conversion. This flag name is stored
	 * as field name in the smart_config table and therefore it should be system wide unique.
	 *
	 * @return string Flag name
	 */
	protected function getUpdateFlag()
	{
		return 'dbadmin_duplicate_placements_removed'; // Important: never change this name
	}

	/**
	 * Runs the DB migration procedure.
	 *
	 * @static
	 */
	public function run()
	{
		$duplicates = $this->checkDuplicates();

		if ( $duplicates ) {
			return $this->removeDuplicates();
		}

		return true;
	}

	public function introduced()
	{
		return '9.4';
	}

	/**
	 * Checks if the placement table contains duplicate placements. Duplicate placements occur when two records
	 * (placements) have the same parent/frameid/edition.
	 *
	 * @return bool duplicates found (true/false)
	 * @throws BizException
	 */
	private function checkDuplicates()
	{
		$dbDriver = DBDriverFactory::gen();
		$sql = 'SELECT COUNT(1) as `total` FROM '.$dbDriver->tablename( 'placements' ).' GROUP BY `parent`, `child`, `frameid`, `edition`, `type` HAVING COUNT(1) > 1 ';
		$sql = $dbDriver->limitquery( $sql, 0, 1 );

		$sth = $dbDriver->query( $sql );

		return $dbDriver->fetch( $sth ) ? true : false;
	}

	/**
	 * Duplicate placements are selected and then deleted. To select the duplicates a copy of the placements table is
	 * made first. This is needed because of performance reasons. Both tables also get an extra index for the same
	 * reason. After the placements are deleted the placementtiles related to those deleted placements are also cleaned
	 * up. Finally the copy of the placement table is dropped and also the extra index.
	 * The commands per database type differ so per DBMS the commands can differ.
	 *
	 * @throws BizException
	 */
	private function removeDuplicates()
	{
		$dbDriver = DBDriverFactory::gen();

		// Copy table and add indexes.
		$success = $this->prepareDatabase( $dbDriver );

		// Delete data can only be done if preparation was successful.
		if ( $success ) {
			$success = $this->deleteData( $dbDriver );
		}

		// Always try to drop the copy of the smart_placements table and the added index.
		$success = $this->correctDatabase( $dbDriver ) && $success;

		return $success;
	}

	/**
	 * Add a copy of the placements table and add an extra index.
	 *
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 */
	private function prepareDatabase( $dbDriver )
	{
		$success = $this->addIndex( 'placements', 'pafridedid', $dbDriver );

		if ( $success ) {
			$success = $this->copyTable( 'placements', 'placements2', $dbDriver );
			if ( !$success ) {
				LogHandler::Log( self::NAME, 'ERROR', 'Making a copy of the `smart_placements` table has failed.' );
				return $success;
			}
		} else {
			LogHandler::Log( self::NAME, 'ERROR', 'Adding index to `smart_placements` table has failed.' );
			return $success;
		}

		return $success;
	}

	/**
	 * Delete the duplicate placements and the corresponding tiles.
	 *
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 */
	private function deleteData( $dbDriver )
	{
		$success = $this->deleteDuplPlacement( 'placements', 'placements2', $dbDriver );

		if ( $success ) {
			$success = $this->deleteDuplTiles( 'placementtiles', 'placements', $dbDriver  );
			if ( !$success ) {
				LogHandler::Log( self::NAME, 'ERROR', 'Deleting the redundant tiles  of the `smart_placementtiles` table has failed.' );
				return $success;
			}
		} else {
			LogHandler::Log( self::NAME, 'ERROR', 'Deleting the duplicates  of the `smart_placements` table has failed.' );
			return $success;
		}

		return $success;
	}

	/**
	 * Delete the copy of the placements table and drop the index.
	 *
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 */
	private function correctDatabase( $dbDriver )
	{
		$success = true;
		if ( !$this->dropTable( 'placements2', $dbDriver ) ) {
			LogHandler::Log( self::NAME, 'WARN', 'Dropping of the smart_placements2` table has failed.' );
			$success = false;
		}

		if ( !$this->dropIndex( 'placements', $dbDriver ) ) {
			LogHandler::Log( self::NAME, 'WARN', 'Dropping of the index `pafridedid` of the table `smart_placements` table has failed.' );
			$success = false;
		}

		return $success;
	}

	/**
	 * Adds an index to the placements table or its copy. On Oracle the name of an index must be unique on database level.
	 *
	 * @param string $tableName Name of the table to add the index.
	 * @param string $indexName Name of the index.
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 * @throws BizException
	 */
	private function addIndex( $tableName, $indexName, $dbDriver )
	{
		$tableName = $dbDriver->tablename( $tableName );
		$sql = 'CREATE  INDEX `'.$indexName.'` ON '.$tableName.' (`parent`, `child`, `frameid`, `edition`, `type`, `id`) ';
		return $dbDriver->query( $sql ) ? true : false;
	}

	/**
	 * Copies a table to new table.
	 *
	 * @param string $fromTable Table to be copied.
	 * @param string $toTable Name of the new table (does not exist in the database yet).
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 * @throws BizException
	 */
	private function copyTable( $fromTable, $toTable, $dbDriver )
	{
		$fromTable = $dbDriver->tablename( $fromTable );
		$toTable = $dbDriver->tablename( $toTable );
		$result = false;
		switch ( DBTYPE ) {
			case 'mysql':
				$sql = 'CREATE TABLE '.$toTable.' LIKE '.$fromTable;
				$result = $dbDriver->query( $sql ) ? true : false;
				if ( $result ) { // Copy data only is possible if the new table is created.
					/* result = */ $dbDriver->query( 'FLUSH TABLES' ); // Make sure Mysql 'sees' the new table.
					$sql = 'INSERT INTO '.$toTable.' SELECT * FROM '.$fromTable;
					$result = $dbDriver->query( $sql ) ? true : false;
				}
				break;
			case 'mssql':
				$sql = 'SELECT * INTO '.$toTable.' FROM '.$fromTable; // Creates table with data but not the indexes.
				$result = $dbDriver->query( $sql ) ? true : false;
				if ( $result ) {
					$result = $this->addIndex( 'placements2', 'pafridedid',  $dbDriver ) ? true : false;
				}
				break;
		}

		return $result;
	}

	/**
	 * Removes duplicate placements from the placements table. Duplicate placements occur when two records
	 * (placements) have the same parent/frameid/edition. Because the placements table is used to find (select) the
	 * duplicates and this table is also updated a copy of this table is needed for the select. Technically also an
	 * alias could be used but it turned out that the performance is than very bad.
	 *
	 * @param string $deleteTable Table to be updated.
	 * @param string $selectTable Table used to find the duplicates.
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 * @throws BizException
	 */
	private function deleteDuplPlacement( $deleteTable, $selectTable, $dbDriver )
	{
		$deleteTable = $dbDriver->tablename( $deleteTable );
		$selectTable = $dbDriver->tablename( $selectTable );
		$sql = '';

		switch ( DBTYPE ) {
			case 'mysql':
				$sql .= 'DELETE '.
						'FROM P1 USING '.$deleteTable.' AS P1 '.
						'WHERE EXISTS '.
							'( SELECT P2.`id` '.
							  'FROM '.$selectTable.' AS P2 '.
							  'WHERE P2.`parent` = P1.`parent` '.
							  'AND P2.`child` = P1.`child` '.
							  'AND P2.`frameid` = P1.`frameid` '.
				              'AND P2.`edition` = P1.`edition` '.
							  'AND P2.`type` = P1.`type` '.
				              'AND P1.`id` > P2.`id` ) ';
				break;
			case 'mssql':
				$sql .= 'DELETE '.
						'FROM '.$deleteTable.' '.
						'WHERE EXISTS '.
							'( SELECT P2.`id` '.
							'FROM '.$selectTable.' AS P2 '.
							'WHERE P2.`parent` = '.$deleteTable.'.`parent` '.
							'AND P2.`child` = '.$deleteTable.'.`child` '.
							'AND P2.`frameid` = '.$deleteTable.'.`frameid` '.
							'AND P2.`edition` = '.$deleteTable.'.`edition` '.
							'AND P2.`type` = '.$deleteTable.'.`type` '.
							'AND '.$deleteTable.'.`id` > P2.`id` )';
				break;
		}

		return $dbDriver->query( $sql ) ? true : false;
	}

	/**
	 * After the duplicate placements are deleted the related tiles must be cleaned up. This is done by deleting those
	 * tiles that have no reference to any placement.
	 *
	 * @param string $deleteTable Table to be updated (placementtiles).
	 * @param string $selectTable Table used to find the redundant tiles (placements).
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 * @throws BizException
	 */
	private function deleteDuplTiles( $deleteTable, $selectTable, $dbDriver )
	{
		$deleteTable = $dbDriver->tablename( $deleteTable );
		$selectTable = $dbDriver->tablename( $selectTable );
		$sql = '';

		switch ( DBTYPE ) {
			case 'mysql':
				$sql .= 'DELETE '.
						'FROM P1 USING '.$deleteTable.' AS P1 '.
						'WHERE NOT EXISTS '.
						'( SELECT P2.`id` '.
						'FROM '.$selectTable.' AS P2 '.
						'WHERE P2.`id` = P1.`placementid` ) ';
				break;
			case 'mssql':
				$sql .= 'DELETE '.
						'FROM '.$deleteTable.' '.
						'WHERE NOT EXISTS '.
						'( SELECT P2.`id` '.
						'FROM '.$selectTable.' AS P2 '.
						'WHERE P2.`id` = '.$deleteTable.'.`placementid` )';

				break;
		}

		return $dbDriver->query( $sql ) ? true : false;
	}

	/**
	 * Drops a table (copy of the placements).
	 *
	 * @param string $droppedTable Name of the table to be dropped (placements2).
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 * @throws BizException
	 */
	private function dropTable( $droppedTable, $dbDriver )
	{
		$droppedTable = $dbDriver->tablename( $droppedTable );
		$sql = 'DROP TABLE '.$droppedTable;
		return $dbDriver->query( $sql ) ? true : false;
	}

	/**
	 * @param string $tableName Table of which the index is dropped.
	 * @param WW_DbDrivers_DriverBase $dbDriver.
	 * @return bool success (true/false).
	 * @throws BizException
	 */
	private function dropIndex( $tableName, $dbDriver )
	{
		$tableName = $dbDriver->tablename( $tableName );
		$sql = 'DROP INDEX `pafridedid` ON '.$tableName;
		return $dbDriver->query( $sql ) ? true : false;
	}
}