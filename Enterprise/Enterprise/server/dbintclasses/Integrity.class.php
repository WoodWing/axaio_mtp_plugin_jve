<?php
/**
 * @package     Enterprise
 * @subpackage  Database Integrity.class
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * 
 * This class is used to define interfaces to safeguard the database integrity. 
 * For each of the three database actions, insert, delete, update a before and 
 * after method is defined.
 * These methods are called from the DB-layer classes. So DBINTTarget.class.php 's 
 * methods are called from, and only from DBTarget.class.php method's.
 * The before<Action> method must be called just before the action on the main
 * table is actually excuted. The after<Action> method must be called right after
 * the main table is changed.
 * The methods are of the type void because no business logic is involved and the
 * database errors must be handled within the DB-layer or in the dbdriver. 
**/

abstract class WW_DBIntegrity_Integrity 
{
	abstract protected function beforeInsert();
	abstract protected function afterInsert();
	abstract protected function beforeDelete();
	abstract protected function afterDelete();	
	abstract protected function beforeUpdate();
	abstract protected function afterUpdate();
	
	private $ids = array(); // Ids of records of parent table to updated/deleted.
							// These Ids are the foreign keys in the child table(s)
	private $updateValues = array(); // Contains column/value pairs that are used to update 
									 // the parent table. 
	private $insertValues = array(); // Contains column/value pairs that are used to add
									 // a record to the parent table 
	
	public function setIDs( array $ids ) { $this->ids = $ids; }
	public function getIDs() { return $this->ids; }
	public function setUpdateValues( array $updateValues) { $this->updateValues = $updateValues; }
	public function getUpdateValues() { return $this->updateValues; }
	public function setInsertValues( array $insertValues ) { $this->insertValues = $insertValues; }
	public function getInsertValues() { return $this->insertValues; }		
}
