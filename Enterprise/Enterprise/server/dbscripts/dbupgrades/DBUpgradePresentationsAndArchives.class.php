<?php
/**
 * Fixes Object type problems occurring during migrations from Ent Server 6.x/7.x to 8.0.
 *
 * Fixes conversion problems between 6.x, 7.x and Enterprise Server 8.0 where objects of 
 * type Other have been split into types of Presentation, Archive and Other. To correctly 
 * update Presentation or Archive objects this script searches for these objects and 
 * duplicates the workflow and authorizations for these objects, then converts the objects
 * and versions to the new types.
 *
 * Affected Tables:
 * - smart_actionproperties
 * - smart_authorizations
 * - smart_config
 * - smart_deletedobjects
 * - smart_log
 * - smart_objects
 * - smart_objectversions
 * - smart_properties
 * - smart_states
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v8.0.0 (this module was split from ObjectConverter class since 9.0.0)
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/ObjectConverter.class.php';

class DBUpgradePresentationsAndArchives extends ObjectConverter
{
	const OBJECT_TYPE_PRESENTATION = 'Presentation';
	const OBJECT_TYPE_ARCHIVE = 'Archive';
	const OBJECT_TYPE_OTHER = 'Other';
	const NAME = 'DBUpgradePresentationsAndArchives';

 	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name 
	 */
	protected function getUpdateFlag()
	{
		return 'dbadmin_types_converted'; // Important: never change this name
	}

	/**
	 * Runs the conversion script.
	 *
	 * Checks if the objects of Type 'Other' contain objects (deleted or normal) that match the mime types for
	 * presentations or archives. If this is the case it attempts to convert these to 'Presentation' and 'Archive' objects.
	 * The workflow and the authorizations are copied and tied together for these new types. Object versions are updated.
	 *
	 * @return bool Whether or not the updates were succesful.
	 */
	public function run()
	{
		// Check the initial database state.
		$cannotUpdate = self::checkDatabaseState();
		if ($cannotUpdate) {
			return false;
		}

		// Check what objects need to be converted.
		$needsToUpdateDeletedObjects = self::getByTypes( array(self::OBJECT_TYPE_OTHER), false );
		$needsToUpdateObjects = self::getByTypes( array(self::OBJECT_TYPE_OTHER), true );

		// If there are no objects of type 'Other' there is no need to convert.
		if ( !$needsToUpdateDeletedObjects && !$needsToUpdateObjects ) {
			LogHandler::Log( self::NAME, 'INFO', 'No objects of type: \'' . self::OBJECT_TYPE_OTHER . '\' to be converted.' );
			return true;
		}

		// Get the MimeTypes for Presentation and Archive types from the configuration to match against existing objects.
		$mimeMap = self::getMimeMap();

		// Gather the actual objects that need an update, both normal objects and deleted objects.
		$deletedArchiveObjectsToUpdate = self::getByMimeTypes( $mimeMap[self::OBJECT_TYPE_ARCHIVE], false );
		$deletedPresentationObjectsToUpdate = self::getByMimeTypes( $mimeMap[self::OBJECT_TYPE_PRESENTATION], false );
		$archiveObjectsToUpdate = self::getByMimeTypes( $mimeMap[self::OBJECT_TYPE_ARCHIVE], true );
		$presentationObjectsToUpdate = self::getByMimeTypes( $mimeMap[self::OBJECT_TYPE_PRESENTATION], true );

		// Check if we would need to update Deleted Objects.
		if ( count( $deletedArchiveObjectsToUpdate ) == 0 && count( $deletedPresentationObjectsToUpdate ) == 0 ) {
			$needsToUpdateDeletedObjects = false;
			LogHandler::Log(self::NAME, 'INFO', 'No Deleted Objects found to be converted.');
		}

		// Check if we need to update normal Objects.
		if ( count( $archiveObjectsToUpdate ) == 0 && count( $presentationObjectsToUpdate ) == 0 ) {
			$needsToUpdateObjects = false;
			LogHandler::Log(self::NAME, 'INFO', 'No Objects found to be converted.');
		}

		$needsUpdate = false;
		if ( $needsToUpdateDeletedObjects || $needsToUpdateObjects ) {
			$needsUpdate = true;
		} else {
			LogHandler::Log( self::NAME, 'INFO', "No objects to be converted." );
			return true;
		}

		// Determine what workflow / authorizations to copy.
		$needsToCopyArchiveWorkflow = false;
		if ( $needsUpdate && count( $archiveObjectsToUpdate ) != 0 || count( $deletedArchiveObjectsToUpdate ) != 0 ) {
			$needsToCopyArchiveWorkflow = true;
		}

		$needsToCopyPresentationWorkflow = false;
		if ( $needsUpdate && count( $presentationObjectsToUpdate ) != 0 || count( $deletedPresentationObjectsToUpdate ) != 0 ) {
			$needsToCopyPresentationWorkflow = true;
		}

		// Duplicate the statuses for the Other flow if present / needed.
		$workflowStatuses = self::duplicateWflStatusesForArchiveAndPresentation( $needsToCopyArchiveWorkflow, $needsToCopyPresentationWorkflow );

		// Update objects to their proper state.
		$presentationObjectIds = array();
		$archiveObjectIds = array();

		// Update DeletedObjects.
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		if ( $deletedArchiveObjectsToUpdate ) {
			foreach ( $deletedArchiveObjectsToUpdate as $objectId ) {
				$object = DBObject::getObjectRows( $objectId, 'Trash' );
				$object['state'] = $workflowStatuses[self::OBJECT_TYPE_ARCHIVE][$object['state']];
				$object['type'] = (string)self::OBJECT_TYPE_ARCHIVE;
				DBDeletedObject::update( $object );
				$archiveObjectIds[] = intval($object['id']);
			}
		}
		if ( $deletedPresentationObjectsToUpdate ) {
			foreach ( $deletedPresentationObjectsToUpdate as $objectId ) {
				$object = DBObject::getObjectRows( $objectId, 'Trash' );
				$object['state'] = $workflowStatuses[self::OBJECT_TYPE_PRESENTATION][$object['state']];
				$object['type'] = (string)self::OBJECT_TYPE_PRESENTATION;
				DBDeletedObject::update( $object );
				$presentationObjectIds[] = intval($object['id']);
			}
		}

		// Update Objects.
		if ( $presentationObjectsToUpdate ) {
			foreach ( $presentationObjectsToUpdate as $objectId ) {
				$object = DBObject::getObjectRows( $objectId, 'Workflow' );
				$object['state'] = $workflowStatuses[self::OBJECT_TYPE_PRESENTATION][$object['state']];
				$object['type'] = (string)self::OBJECT_TYPE_PRESENTATION;
				DBObject::update( $object );
				$presentationObjectIds[] = intval($object['id']);
			}
		}
		if ( $archiveObjectsToUpdate ) {
			foreach ( $archiveObjectsToUpdate as $objectId ) {
				$object = DBObject::getObjectRows( $objectId, 'Workflow' );
				$object['state'] = $workflowStatuses[self::OBJECT_TYPE_ARCHIVE][$object['state']];
				$object['type'] = (string)self::OBJECT_TYPE_ARCHIVE;
				DBObject::update( $object );
				$archiveObjectIds[] = intval($object['id']);
			}
		}

		if ( count( $presentationObjectIds ) > 0 ) {
			// Update object versions.
			$updated = self::updateObjectVersions(self::OBJECT_TYPE_PRESENTATION, $presentationObjectIds, $workflowStatuses);
			if (!$updated) {
				LogHandler::Log( self::NAME, 'ERROR', 
					"ObjectVersions could not be updated for type: '" . self::OBJECT_TYPE_PRESENTATION . "'" );
				return false;
			}

			// Update authorizations for the groups.
			$updated = self::insertAuthorizations($workflowStatuses, self::OBJECT_TYPE_PRESENTATION);
			if (!$updated) {
				LogHandler::Log( self::NAME, 'ERROR', 
					"Authorizations could not be updated for type: '" . self::OBJECT_TYPE_PRESENTATION . "'" );
				return false;
			}

			// Update log entries if needed.
			$updated = self::updateLogEntries(self::OBJECT_TYPE_PRESENTATION, $presentationObjectIds, $workflowStatuses);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					"LogEntries could not be updated for type: '" . self::OBJECT_TYPE_PRESENTATION . "'" );
				return false;
			}

			// Update properties used in Dialogs.
			$updated = self::duplicateActionProperties(self::OBJECT_TYPE_PRESENTATION, self::OBJECT_TYPE_OTHER);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					"ActionProperties could not be updated for type: '" . self::OBJECT_TYPE_PRESENTATION . "'" );
				return false;
			}

			// Update properties.
			$updated = self::updateProperties(self::OBJECT_TYPE_PRESENTATION, self::OBJECT_TYPE_OTHER);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					"Properties could not be updated for type: '" . self::OBJECT_TYPE_PRESENTATION . "'" );
				return false;
			}
		}

		if ( count( $archiveObjectIds ) > 0 ) {
			// Update object versions.
			$updated = self::updateObjectVersions(self::OBJECT_TYPE_ARCHIVE, $archiveObjectIds, $workflowStatuses);
			if (!$updated) {
				LogHandler::Log( self::NAME, 'ERROR', 
					"ObjectVersions could not be updated for type: '" . self::OBJECT_TYPE_ARCHIVE . "'" );
				return false;
			}

			//update authorizations for the groups.
			$updated = self::insertAuthorizations($workflowStatuses, self::OBJECT_TYPE_ARCHIVE);
			if (!$updated) {
				LogHandler::Log( self::NAME, 'ERROR', 
					"Authorizations could not be updated for type: '" . self::OBJECT_TYPE_ARCHIVE . "'" );
				return false;
			}

			// Update log entries if needed.
			$updated = self::updateLogEntries(self::OBJECT_TYPE_ARCHIVE, $archiveObjectIds, $workflowStatuses);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					"LogEntries could not be updated for type: '" . self::OBJECT_TYPE_ARCHIVE . "'" );
				return false;
			}

			// Update properties used in Dialogs.
			$updated = self::duplicateActionProperties(self::OBJECT_TYPE_ARCHIVE, self::OBJECT_TYPE_OTHER);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					"ActionProperties could not be updated for type: '" . self::OBJECT_TYPE_ARCHIVE . "'" );
				return false;
			}

			// Update properties.
			$updated = self::updateProperties(self::OBJECT_TYPE_ARCHIVE, self::OBJECT_TYPE_OTHER);
			if (!$updated){
				LogHandler::Log( self::NAME, 'ERROR', 
					"Properties could not be updated for type: '" . self::OBJECT_TYPE_ARCHIVE . "'" );
				return false;
			}
		}
		return true;
	}

	public function introduced()
	{
		return '800';
	}

	/**
	 * Use doesObjectAndStateExists() instead.
	 * Checks the initial state of the database.
	 *
	 * Prints a message on the screen in case of errors.
	 *
	 * @static
	 * @return bool Whether or not the database is up to date.
	 */
	static private function checkDatabaseState()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking if objects need to be converted.' );
		
		// If the database is not yet converted but contains states for Presentation or Archive already.
		if( self::hasArchiveOrPresentationTypes() ) {
			LogHandler::Log( self::NAME, 'ERROR', 
				'Cannot convert objects, database contains statuses for type Presentation or Archive already.' );
			return true;
		}

		// Check if there are already Archive or Presentation types in deleted objects.
		if( self::getByTypes( array(self::OBJECT_TYPE_ARCHIVE, self::OBJECT_TYPE_PRESENTATION), false ) ) {
			LogHandler::Log( self::NAME, 'ERROR', 
				'Cannot convert objects, database contains deleted objects for type Presentation or Archive already.' );
			return true;
		}

		// Check if there are already Archive or Presentation types in objects.
		if( self::getByTypes( array(self::OBJECT_TYPE_ARCHIVE, self::OBJECT_TYPE_PRESENTATION), true ) ) {
			LogHandler::Log( self::NAME, 'ERROR', 
				'Cannot convert objects, database contains objects for type Presentation or Archive already.' );
			return true;
		}

		return false;
	}

	/**
	 * Use duplicateWorkflowStatuses() instead.
	 * Duplicates Workflow statuses.
	 *
	 * @static
	 * @param $needsToCopyArchiveWorkflow
	 * @param $needsToCopyPresentationWorkflow
	 * @return array
	 */
	static private function duplicateWflStatusesForArchiveAndPresentation( $needsToCopyArchiveWorkflow, $needsToCopyPresentationWorkflow )
	{
		$states = array();

		if ( !$needsToCopyArchiveWorkflow && !$needsToCopyPresentationWorkflow ) {
			return $states;
		}

		// Get all valid states for Other Objects.
		require_once BASEDIR . '/server/dbclasses/DBWorkflow.class.php';
		$records = DBWorkflow::listStates( null, null, null, self::OBJECT_TYPE_OTHER, null, true );
		if ( count( $records ) == 0 ) {
			return $states;
		}

		foreach ( $records as $record ) {
			$oldId = $record['id'];

			if ( $needsToCopyArchiveWorkflow ) {
				$states = self::duplicateStatus($record, $states, self::OBJECT_TYPE_ARCHIVE, $oldId);
			}

			if ( $needsToCopyPresentationWorkflow ) {
				$states = self::duplicateStatus($record, $states, self::OBJECT_TYPE_PRESENTATION, $oldId);
			}
		}
		return $states;
	}

	/**
	 * Retrieves the MimeType mapping from configserver.
	 *
	 * @static
	 * @return array
	 */
	static private function getMimeMap()
	{
		$mimeMap = array(self::OBJECT_TYPE_PRESENTATION, self::OBJECT_TYPE_ARCHIVE);
		$mimeMap[self::OBJECT_TYPE_ARCHIVE] = array();
		$mimeMap[self::OBJECT_TYPE_ARCHIVE] = array();

		$mimetypes = unserialize( EXTENSIONMAP );

		if ( $mimetypes ) {
			foreach ( $mimetypes as $pair ) {
				if ( $pair[1] === self::OBJECT_TYPE_ARCHIVE ) {
					$mimeMap[self::OBJECT_TYPE_ARCHIVE][] = $pair[0];
				}

				if ( $pair[1] === self::OBJECT_TYPE_PRESENTATION ) {
					$mimeMap[self::OBJECT_TYPE_PRESENTATION][] = $pair[0];
				}
			}
		}
		return $mimeMap;
	}

	/**
	 * Use hasObjectTypeStates() instead.
	 * Checks if there are Archive or Presentation states in the database.
	 *
	 * @static
	 * @return bool
	 */
	static private function hasArchiveOrPresentationTypes()
	{
		// This should check the states table for Archive or Presentation.
		require_once BASEDIR . '/server/dbclasses/DBWorkflow.class.php';

		// Check for type Presentation
		$records = DBWorkflow::listStates( null, null, null, self::OBJECT_TYPE_PRESENTATION, null, true );
		if ( count( $records ) > 0 ) {
			return true;
		}

		// Check for the type Archive.
		$records = DBWorkflow::listStates( null, null, null, self::OBJECT_TYPE_ARCHIVE, null, true );
		if ( count( $records ) > 0 ) {
			return true;
		}

		return false;
	}
}