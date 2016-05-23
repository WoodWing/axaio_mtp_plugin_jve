<?php

/**
 * Fixes Object type problems occurring during migrations from Ent Server 6.x/7.x to version 8.0.
 *
 * Fixes conversion problems between 6.x, 7.x and Enterprise Server 8.0 where objects of type Other have been split
 * into types of Presentation, Archive and Other. To correctly update Presentation or Archive objects this script
 * searches for these objects and duplicates the workflow and authorizations for these objects, then converts the objects
 * and versions to the new types.
 *
 * Affected Tables:
 *
 * smart_actionproperties
 * smart_authorizations
 * smart_config
 * smart_deletedobjects
 * smart_log
 * smart_objects
 * smart_objectversions
 * smart_properties
 * smart_states
 *
 */
require_once BASEDIR.'/server/dbscripts/DbUpgradeModule.class.php';

abstract class ObjectConverter extends DbUpgradeModule
{
	const NAME = 'ObjectConverter';

	/**
	 * Changes the object type from $objectTypeFrom to $objectTypeTo for those objects
	 * that have one of the given file formats ($fileFormats) as native rendition.
	 *
	 * @param array $fileFormats
	 * @param string $objectTypeFrom
	 * @param string $objectTypeTo
	 * @return bool
	 */
	protected function convertObjectType( array $fileFormats, $objectTypeFrom, $objectTypeTo )
	{
		// Check the initial database state.
		if( self::doesObjectAndStateExists( $objectTypeTo ) ) {
			// when the object or state of type $objectTypeTo already exists, objects of type $objectTypeFrom
			// cannot be converted to $objectTypeTo.
			LogHandler::Log( self::NAME, 'ERROR', 
				'Database already has object type of "'.$objectTypeTo.'" or status defined ' .
				'for object type "'.$objectTypeTo.'".');
			return false;
		}

		// Check what objects need to be converted.
		$needsToUpdateDeletedObjects = self::getByTypes( array($objectTypeFrom), false ); // trash area
		$needsToUpdateObjects = self::getByTypes( array($objectTypeFrom), true ); // workflow area

		// If there are no objects of type 'Other' there is no need to convert.
		if ( !$needsToUpdateDeletedObjects && !$needsToUpdateObjects ) {
			LogHandler::Log( self::NAME, 'INFO', 
				'No objects of type: \'' . $objectTypeFrom . '\' to be converted to ' .
				'\'' . $objectTypeTo . '\'.');
			return true;
		}

		// Gather the actual objects that need an update, both normal objects and deleted objects.
		$deletedObjectsToUpdate = self::getByMimeTypes( $fileFormats, false );
		$workflowObjectsToUpdate = self::getByMimeTypes( $fileFormats, true );
		// Check if we would need to update Deleted Objects.
		if ( count( $deletedObjectsToUpdate ) == 0 ) {
			$needsToUpdateDeletedObjects = false;
			LogHandler::Log( self::NAME, 'INFO', 'No Deleted Objects found to be converted to "'.$objectTypeTo.'".' );
		}

		// Check if we need to update normal Objects.
		if ( count( $workflowObjectsToUpdate ) == 0 ) {
			$needsToUpdateObjects = false;
			LogHandler::Log( self::NAME, 'INFO', 'No Objects found to be converted to "'.$objectTypeTo.'".' );
		}

		if ( !$needsToUpdateDeletedObjects && !$needsToUpdateObjects ) {
			return true;
		}

		// Determine what workflow / authorizations to copy.
		if ( count( $workflowObjectsToUpdate ) != 0 || count( $deletedObjectsToUpdate ) != 0 ) {
			// Duplicate the statuses for the $objectTypeFrom flow if present / needed.
			$workflowStatuses = self::duplicateWorkflowStatuses( $objectTypeFrom, $objectTypeTo );
		}

		// Update objects to their proper state.
		$objToBeConvertedIds = array(); // from trash and workflow area.

		// Update DeletedObjects.
		require_once BASEDIR . '/server/dbclasses/DBDeletedObject.class.php';
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		if ( $deletedObjectsToUpdate ) {
			foreach ( $deletedObjectsToUpdate as $objectId ) {
				$object = DBObject::getObjectRows( $objectId, array( 'Trash' ) );
				$object['state'] = $workflowStatuses[$objectTypeTo][$object['state']];
				$object['type'] = $objectTypeTo;
				DBDeletedObject::update( $object );
				$objToBeConvertedIds[] = intval($object['id']);
			}
		}

		// Update Objects.
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		if ( $workflowObjectsToUpdate ) {
			foreach ( $workflowObjectsToUpdate as $objectId ) {
				$object = DBObject::getObjectRows( $objectId, array( 'Workflow' ) );
				$object['state'] = $workflowStatuses[$objectTypeTo][$object['state']];
				$object['type'] = $objectTypeTo;
				DBObject::update( $object );
				$objToBeConvertedIds[] = intval($object['id']);
			}
		}

		if ( count( $objToBeConvertedIds ) > 0 ) {
			// Update object versions.
			$updated = self::updateObjectVersions($objectTypeTo, $objToBeConvertedIds, $workflowStatuses);
			if (!$updated) {
				LogHandler::Log( self::NAME, 'ERROR', 
					'ObjectVersions could not be updated for type: "' . $objectTypeTo . '"' );
				return false;
			}

			//update authorizations for the groups.
			$updated = self::insertAuthorizations($workflowStatuses, $objectTypeTo);
			if (!$updated) {
				LogHandler::Log( self::NAME, 'ERROR', 
					'Authorizations could not be updated for type: "' . $objectTypeTo . '"' );
				return false;
			}

			// Update log entries if needed.
			$updated = self::updateLogEntriesWithNewStatus($objectTypeFrom, $objectTypeTo, $objToBeConvertedIds, $workflowStatuses);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					'LogEntries could not be updated for type: "' . $objectTypeTo . '"' );
				return false;
			}

			// Update properties used in Dialogs.
			$updated = self::duplicateActionProperties($objectTypeTo, $objectTypeFrom);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					'ActionProperties could not be updated for type: "' . $objectTypeTo . '"' );
				return false;
			}

			// Update properties.
			$updated = self::updateProperties( $objectTypeTo, $objectTypeFrom );
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					'Properties could not be updated for type: "' . $objectTypeTo . '"' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Duplicates the Action Properties with different object type.
	 *
	 * Duplicate the row in action_properties and for the duplicated rows,
	 * column 'type' value is changed from $objectTypeFrom to
	 * $objectTypeTo.
	 *
	 * @param string $objectTypeTo Column 'type' value in the new row of action_properties.
	 * @param string $objectTypeFrom Duplicate the row when the `type` is equal to this value.
	 * @return bool True when duplication succeeded; False otherwise.
	 */
	static protected function duplicateActionProperties($objectTypeTo, $objectTypeFrom)
	{
		require_once BASEDIR . '/config/configserver.php';

		if ( !$dbh = DBDriverFactory::gen() ) {
			return false;
		}

		// Select any ActionProperties of type $objectTypeFrom
		$where = "`type` = ?";
		$rows = DBBase::listRows('actionproperties', null, null, $where, '*', array($objectTypeFrom), null, null, null, null );

		if ($rows) foreach ($rows as $row){
			$dba = $dbh->tablename( 'actionproperties' );

			$edit = (!is_null($row['edit'])) ? $row['edit']: '';
			$mandatory = (!is_null($row['mandatory'])) ? $row['mandatory']: '';
			$action = (!is_null($row['action'])) ? $row['action']: '';
			$restricted = (!is_null($row['restricted'])) ? $row['restricted']: '';
			$refreshonchange = (!is_null($row['refreshonchange'])) ? $row['refreshonchange']: '';

			$sql = "INSERT INTO $dba (`publication`, `orderid`, `property`, `edit`, `mandatory`,`action`,`type`,`restricted`, `refreshonchange`) VALUES (" .
				$row['publication'] . ', ' .
				$row['orderid'] . ', ' .
				"'".$row['property']."'" . ', ' .
				"'$edit'" . ', ' .
				"'$mandatory'" . ', ' .
				"'$action'" . ', ' .
				"'$objectTypeTo'" . ', ' .
				"'$restricted'" . ', ' .
				"'$refreshonchange'" .
				')';
			$sql = $dbh->autoincrement($sql);
			$sth = $dbh->query( $sql );
			if ( !$sth ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Updates the smart_properties table.
	 *
	 * @static
	 * @param string $objectTypeTo
	 * @param string $objectTypeFrom
	 * @return bool
	 */
	static protected function updateProperties($objectTypeTo, $objectTypeFrom)
	{
		require_once BASEDIR . '/config/configserver.php';

		if ( !$dbh = DBDriverFactory::gen() ) {
			return false;
		}

		// Select any ActionProperties of type $objectTypeFrom
		$where = "`type` = ?";
		$actionPropertiesRows = DBBase::listRows('actionproperties', null, null, $where, '*', array($objectTypeTo), null, null, null, null );

		if ($actionPropertiesRows) foreach ($actionPropertiesRows as $actionPropertiesRow){
			$where = "`name` = ? AND `objtype` = ? AND `entity`= ?";
			$propertiesRows = DBBase::listRows('properties', null, null, $where, '*', array($actionPropertiesRow['property'], $objectTypeFrom, 'Object'), null, null, null, null );

			if ($propertiesRows) foreach ($propertiesRows as $propertiesRow){
				$dba = $dbh->tablename( 'properties' );
				$sql = "update $dba set `objtype`='' where `id`= " . $propertiesRow['id'];
				$sth = $dbh->query( $sql );
				if ( !$sth ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Updates the smart_log table.
	 *
	 * For the object type that has been converted to a new type, the log file
	 * which tracks the state(status) is also updated(mapped) accordingly with the new
	 * object type.
	 *
	 * @static
	 * @param string $oldObjType The old object Type that has been converted to $newObjType.
	 * @param string $newObjType The newly converted object Type(from $oldObjType).
	 * @param string $ids The object ids that has been affected with the type conversion.
	 * @param array $statuses The workflow statuses of the old object type and new object type.
	 * @return bool
	 */
	static private function updateLogEntriesWithNewStatus($oldObjType, $newObjType, $ids, $statuses)
	{
		// Update the log entries.
		$objectsList = (count( $ids ) > 1) ? implode( ',', $ids ) : $ids[0];
		require_once BASEDIR . '/config/configserver.php';

		if (isset($statuses[$newObjType])) foreach ( $statuses[$newObjType] as $old => $new ) {
			if ( !$dbh = DBDriverFactory::gen() ) {
				return false;
			}
			$dba = $dbh->tablename( "log" );
			$sql = "UPDATE $dba SET `state`=$new, `type`='$newObjType' WHERE `state`=$old AND `type`='$oldObjType' ".
				"AND `objectid` IN ( $objectsList )";
			$sth = $dbh->query( $sql );
			if ( !$sth ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Use updateLogEntriesWithNewStatus() instead.
	 * Updates the smart_log table.
	 *
	 * @static
	 * @param $type
	 * @param $ids
	 * @param $statuses
	 * @return bool
	 */
	static protected function updateLogEntries( $type, $ids, $statuses )
	{
		// Update the log entries.
		$objectsList = (count( $ids ) > 1) ? implode( ',', $ids ) : $ids[0];
		require_once BASEDIR . '/config/configserver.php';

		if (isset($statuses[$type])) foreach ( $statuses[$type] as $old => $new ) {
			if ( !$dbh = DBDriverFactory::gen() ) {
				return false;
			}
			$dba = $dbh->tablename( "log" );
			$sql = "UPDATE $dba SET `state`=$new, `type`='$type' WHERE `state`=$old AND `type`='Other' AND `objectid` IN ( $objectsList )";
			$sth = $dbh->query( $sql );
			if ( !$sth ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Updates status of the newly converted object type's object with their
	 * new status id in smart_objectversions table.
	 *
	 * @static
	 * @param string $type The object type.
	 * @param array $ids Object ids where the statuses need to be updated.
	 * @param array $statuses The workflow statuses of the old object type and new object type.
	 * @return bool True when successfully inserted; false otherwise.
	 */
	static protected function updateObjectVersions( $type, $ids, $statuses )
	{
		// Update the object versions.
		$objectsList = (count( $ids ) > 1) ? implode( ',', $ids ) : $ids[0];
		require_once BASEDIR . '/config/configserver.php';

		if (isset($statuses[$type])) foreach ( $statuses[$type] as $old => $new ) {
			if( !$dbh = DBDriverFactory::gen() ) {
				return false;
			}
			$dba = $dbh->tablename( "objectversions" );
			$sql = "UPDATE $dba SET `state` = $new WHERE `state` = $old AND `objid` IN ( $objectsList )";
			$sth = $dbh->query( $sql );
			if ( !$sth ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Insert a new Authorizations into smart_authorizations table for the newly
	 * created workflow status.
	 *
	 * @static
	 * @param array $statuses The workflow statuses of the old object type and new object type.
	 * @param string $type Object type.
	 * @return bool True when record successfully inserted, false otherwise.
	 */
	static protected function insertAuthorizations( $workflowStatuses, $type )
	{
		require_once BASEDIR . '/config/configserver.php';

		// update authorizations.
		$authStates = array_keys( $workflowStatuses[$type] ); // old existing workflow status
		$authStates = implode( ',', $authStates );
		if ( !$dbh = DBDriverFactory::gen() ) {
			return false;
		}
		$dba = $dbh->tablename( 'authorizations' );
		$sql = "SELECT * FROM $dba WHERE `state` IN ( $authStates )";
		$sth = $dbh->query( $sql );
		if ( !$sth ) {
			return false;
		}
		while ( ($row = $dbh->fetch( $sth )) ) {
			$oldState = intval( $row['state'] );
			$newId = $workflowStatuses[$type][$oldState];

			$sql = "INSERT INTO $dba (`grpid`, `publication`, `section`, `state`, `profile`,`issue`) VALUES (" .
				$row['grpid'] . ', ' .
				$row['publication'] . ', ' .
				$row['section'] . ', ' .
				$newId . ', ' .
				$row['profile'] . ', ' .
				$row['issue'] . ')';
			$sql = $dbh->autoincrement($sql);
			$sth2 = $dbh->query( $sql );
			if ( !$sth2 ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks if the object of type $objectType do exists in workflow or trash area
	 * and also checks if there's any workflow status defined for the $objectType.
	 *
	 * @param string $objectType
	 * @return boolean True when object or the workflow status exists; False otherwise.
	 */
	static private function doesObjectAndStateExists( $objectType )
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking if objects need to be converted to "'.$objectType.'".' );
		if( self::hasObjectTypeStates( $objectType ) ) {
			LogHandler::Log( self::NAME, 'ERROR', 
				'Cannot convert objects, database contains status for type "'.$objectType.'" already.' );
			return true;
		}

		// Check if there are already $objectType types in deleted objects.
		if ( self::getByTypes( array( $objectType ), false ) ) {
			LogHandler::Log( self::NAME, 'ERROR', 
				'Cannot convert objects, database contains deleted objects for type "'.$objectType.'" already.' );
			return true;
		}

		// Check if there are already $objectType types in objects.
		if ( self::getByTypes( array( $objectType ), true ) ) {
			LogHandler::Log( self::NAME, 'ERROR', 
				'Cannot convert objects, database contains objects for type "'.$objectType.'" already.' );
			return true;
		}

		return false;
	}

	/**
	 * Duplicates Workflow statuses.
	 *
	 * To duplicate the workflow status of object type $oriObjectType and change the
	 * duplicated workflow status to type of $objectTypeToBeDuplicated.
	 *
	 * @param string $oriObjectType The original object type's workflow status to be duplicated if needed.
	 * @param string $objectTypeToBeDuplicated The duplicated workflow status's object type
	 * @return array
	 */
	static private function duplicateWorkflowStatuses( $oriObjectType, $objectTypeToBeDuplicated )
	{
		$states = array();
		// Get all valid states for Objects of type $oriObjectType.
		require_once BASEDIR . '/server/dbclasses/DBWorkflow.class.php';
		$records = DBWorkflow::listStates( null, null, null, $oriObjectType, null, true );
		if ( count( $records ) > 0 ) {
			// The object type to be converted to the new type must have new status, thus, duplicate the
			// status of the original object type and assign the status for the new object type.
			foreach ( $records as $record ) {
				$oldId = $record['id'];
				$states = self::duplicateStatus($record, $states, $objectTypeToBeDuplicated, $oldId);
			}
		}
		return $states;
	}

	/**
	 * Duplicates a status for a specific Object Type
	 *
	 * Duplicates the status and maps it on $states.
	 *
	 * @static
	 * @param array $record The original record to be duplicated
	 * @param array $states The states array to be expanded by this function.
	 * @param string $type The new object type for which to make the new Status.
	 * @param int $oldId The original id of the state being duplicated.
	 * @return array The array of mapped states.
	 */
	static protected function duplicateStatus( $record, $states, $type, $oldId )
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmStatus.class.php';

		if ( !isset($states[$type]) ) {
			$states[$type] = array();
		}

		$record['id'] = '';
		$record['type'] = (string)$type;
		$obj = DBAdmStatus::rowToObj( $record );
		$newStatus = DBAdmStatus::createStatus( $obj );
		$states[$type][$oldId] = intval( $newStatus->Id );
		return $states;
	}

	/**
	 * Preps an array of values for use as string delimited values.
	 * Delimits values by ',' and adds quotes.
	 * @static
	 *
	 * @param array $arr The array of values to be transform into a string delimited by comma.
	 * @return string
	 */
	static private function arrayToSQLString( $arr )
	{
		return (count( $arr > 1 ))
			? $arr = '\'' . implode( '\',\'', $arr ) . '\''
			: "'" . $arr[0] . "'";
	}

	/**
	 * Checks if there are workflow status defined for object type $objectType.
	 *
	 * @param string $objectType
	 * @return bool True when there are states defined for the object type $objectType, False otherwise.
	 */
	static private function hasObjectTypeStates( $objectType )
	{
		// This should check the states table for $objectType.
		require_once BASEDIR . '/server/dbclasses/DBWorkflow.class.php';

		// Check if there are states defined for object type $objectType
		$records = DBWorkflow::listStates( null, null, null, $objectType, null, true );
		return count( $records ) > 0 ? true : false;
	}

    /**
	 * Gets the (deleted)objects of the requested mimetypes.
     *
     * @static
     * @param string[] $mimeTypes
     * @param boolean $workflow Workflow (true) or not.
	 * @return int[] with the ids of the found objects.
     */
	static public function getByMimeTypes($mimeTypes, $workflow)
	{
		$result = array();
		$dbh = DBDriverFactory::gen();
		if ( $workflow ) {
			$tableName = $dbh->tablename( 'objects' );
		} else {
			$tableName = $dbh->tablename( 'deletedobjects' );
		}
		
		$mimeTypesSql = self::arrayToSQLString($mimeTypes);
		$sql = "SELECT `id` FROM $tableName WHERE `format` IN ($mimeTypesSql)";
		$sth = $dbh->query( $sql );

		if ($sth){
			while( ($row = $dbh->fetch( $sth )) ) {
				$result[] = $row['id'];
			}
		}
		
		return $result;
	}

	/**
	 * Gets the (deleted)objects of the requested types.
	 *
	 * @static
	 * @param string[] $objectTypes.
     * @param $workflow Workflow or Trash
	 * @return true if found else false.
	 */
	static public function getByTypes($objectTypes, $workflow)
	{
		$dbh = DBDriverFactory::gen();
		if ( $workflow ) {
			$tableName = $dbh->tablename( 'objects' );
		} else {
			$tableName = $dbh->tablename( 'deletedobjects' );
		}

		$typesSql = self::arrayToSQLString($objectTypes);
		$sql = "SELECT `id` FROM $tableName WHERE `type` IN ($typesSql)";
		$sql = $dbh->limitquery($sql, 0, 1);
		$sth = $dbh->query( $sql );

		if ($sth){
			if ( $dbh->fetch($sth)) {
				return true;
			}
		}

		return false;
	}	
}
