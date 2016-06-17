<?php
/**
 * @package     Enterprise
 * @subpackage  DB Integrity
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * 
 * This template can be used to create a new integrity class. Copy the content and
 * remove superluous comments. Next replace general names by specific names.
 * 
 * This class is used to safeguard the database integrity. For each of the three
 * database actions, insert, delete, update a before and after method is defined.
 * These methods are called from the DB-layer classes. So DBINTTarget.class.php 's 
 * methods are called from, and only from DBTarget.class.php method's.
 * The before<Action> method must be called just before the action on the main
 * table is actually excuted. The after<Action> method must be called right after
 * the main table is changed.
 * The methods are of the type void because no business logic is involved and the
 * database errors must be handled within the DB-layer or in the dbdriver. 
**/
require_once BASEDIR.'/server/dbintclasses/Integrity.class.php';
class WW_DBIntegrity_Tablename extends WW_DBIntegrity_Integrity {
	//E.g WW_DBIntegrity_Targets
	
	/**
	 * Method called to update related records before main record(s) are updated.
	 */
	public function beforeUpdate()
	{
	}

	/**
	 * Method called to update related records after main record(s) are updated.
	 */
	public function afterUpdate()
	{
	}

	/** 
	 * Method called to update related records before main record is added.
	 */
	public function beforeInsert()
	{
	}

	/** 
	 * Method called to update related records after main record is added.
	 */
	public function afterInsert()
	{
	}

	/**
	 * Before (parent) records are deleted make sure related (child) records are
	 * handled. Normaly this means deleting the related records or clearing the
	 * foreign key value. Can also be used to recalculate totals or other derived
	 * values.
	 */
	public function beforeDelete()
	{
		//$whereParams = array('foreignkeycolumn' => $this->ids);
		//$newValueForeignColumn = array('foreignkeycolumn' => '');
		// Empty foreign key fields if parent is deleted 
		//DBChildTable::update($whereParams, $newValueForeignColumn);
		// Delete child records if parent is deleted.
		//DBChildTable2::delete($whereParams);
	}

	/**
	 * Before (parent) records are deleted make sure related (child) records are
	 * handled. Normaly this means deleting the related records or clearing the
	 * foreign key value. Can also be used to recalculate totals or other derived
	 * values. 
	 */
	public function afterDelete()
	{
	}	
}

