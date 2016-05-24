<?php
/**
 * Maintains flags set to objects at database level.
 *
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBObjectFlag extends DBBase
{
	const TABLENAME = 'objectflags';
	
	static public function setObjectFlag( $objid, $origin, $flag, $severity, $message = null )
	{
		$params = array( $objid, $origin, $flag );
		$where = "`objid` = ? AND `flagorigin`= ? AND `flag`= ? "; 
		$result = self::getRow(self::TABLENAME, $where, array( 'locked' ), $params);
		if( empty($result) ) {
			self::insertRow(self::TABLENAME, array( 'objid' => $objid, 'flagorigin' => $origin, 'flag' => $flag, 'severity' => $severity, 'message' => $message ), false );
		} elseif( isset($result[0]) && $result[0] == 1 ) { // locked?
			$params = array( $objid, $origin, $flag );
			$where = '`objid`= ? AND `flagorigin`= ? AND `flag`= ? '; 
			self::updateRow( self::TABLENAME, array( 'locked' => 0), $where, $params);
		} /* else if($result[0] == 0) { // unlocked?
			// nothing to do.... record already exists and is set correct
		}*/
	}

	// Note: called for unlock objects
	static public function unlockObjectFlags( $id )
	{ 
		$params = array( $id );
		$where = '`objid`= ? ';
		self::updateRow( self::TABLENAME, array( 'locked' => 0), $where, $params);
	}

	// Note: called for get objects with lock
	static public function lockObjectFlags( $id )
	{ 
		$params = array( $id );
		$where = '`objid`= ? ';
		self::updateRow( self::TABLENAME, array( 'locked' => 1), $where, $params);
	}

	// Note: called for save objects (no matter the lock)
	static public function deleteObjectFlags( $id )
	{ 
		$params = array( $id, 1 );
		$where = '`objid` = ? AND `locked` = ? ';
		self::deleteRows(self::TABLENAME, $where, $params);
	}

	// Note: called for save objects (no matter the lock)
	static public function deleteObjectFlagsByObjId( $id )
	{ 
		$params = array( $id );
		$where = '`objid` = ? ';
		self::deleteRows(self::TABLENAME, $where, $params);
	}
	
	/**
	 * Returns the default column name of the auto-increment field. 
	 */
	static public function getAutoincrementColumn()
	{
		return '';
	}	
}