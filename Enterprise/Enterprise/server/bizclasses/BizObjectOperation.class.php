<?php
/**
 * Biz logics class for Object Operations.
 *
 * A list of Object Operations can be recorded for a certain layout object. This can
 * only be done when the acting user has a lock for the layout. Operations are there
 * to implement the Automated Print Workflow feature, as introduced since 9.7.
 *
 * When the layout is opened for editing in SC, its operations can be retrieved (from DB)
 * to let SC process them onto the layout. When the layout is saved, the operations 
 * are assumed to be processed and so they are removed (from DB). When the layout object
 * is purged from Trash Can, the operations are cascade deleted from DB.
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.8
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizObjectOperation
{
	/**
	 * Creates operations for an object. Only layout objects are supported. 
	 *
	 * The layout should be locked by caller before, or else the server will try 
	 * to obtain a lock during the service call. The operations can only be created
	 * for the latest version of the layout. When the layout is opened in SC, the
	 * operations will be processed on the layout with help of an AutomatedPrintWorkflow connector.
	 *
	 * @param string $user
	 * @param string $objectId
	 * @param string $objectVersion
	 * @param ObjectOperation[] $operations
	 * @throws BizException On lock error, object version mismatch or on fatal SQL errors.
	 * @throws null
	 */
	static public function createOperations( $user, $objectId, $objectVersion, $operations )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		if( !$objectId  || !$objectVersion || !$operations ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		// Lock the object when not locked yet. Error when could not lock or locked by someone else.
		require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
		$lockedBy = DBObjectLock::checkLock( $objectId );
		if( is_null( $lockedBy ) ) { // nobody has lock?
			DBObjectLock::lockObject( $objectId, $user ); // throws ERR_LOCKED when locked due to race condition
			$lockedByUs = true;
		} else {
			if( $lockedBy != $user ) { // no locked by caller?
				throw new BizException( 'ERR_LOCKED', 'Client', $objectId );
			}
			$lockedByUs = false;
		}
		
		// Catch errors so we can unlock the objects again.
		try {
			// Error when the caller does not provide the current object version.
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$currentVersion = DBObject::getObjectVersion( $objectId );
			if( is_null($currentVersion) || $currentVersion != $objectVersion ) {
				throw new BizException( 'ERR_OBJ_VERSION_MISMATCH', 'Client', $objectId );
			}
			
			// Let biz connectors resolve operations into more/lesser/other operations.
			$operations = self::resolveOperations( $objectId, $operations );
			
			// Create the operations.
			require_once BASEDIR.'/server/dbclasses/DBObjectOperation.class.php';
			DBObjectOperation::createOperations( $objectId, $operations );
			$e = null;
		} catch( BizException $e ) {
		}
		
		// Unlock the object when it was locked by us above.
		if( $lockedByUs ) {
			DBObjectLock::unlockObjects( array( $objectId ), $user );
		}
		
		// Re-throw the exception to tell caller the creation failed.
		if( $e ) {
			throw $e;
		}
	}
	
	/**
	 * Retrieves the operations from DB that were created for a given object.
	 *
	 * @param integer $objectId
	 * @return ObjectOperation[]
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	public static function getOperations( $objectId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectOperation.class.php';
		return DBObjectOperation::getOperations( $objectId );
	}

	/**
	 * Removes the operation from the DB with the given object id and operation GUID.
	 *
	 * @param integer $objectId
	 * @param string $guid
	 * @throws BizException On bad given params or fatal SQL errors.
	 * @since 10.1.3
	 */
	public static function deleteOperation( $objectId, $guid )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectOperation.class.php';
		DBObjectOperation::deleteOperation( $objectId, $guid );
	}
	
	/**
	 * Removes the operations from DB that were created for a given object.
	 *
	 * @param integer $objectId
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	public static function deleteOperations( $objectId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObjectOperation.class.php';
		DBObjectOperation::deleteOperations( $objectId );
	}
	
	/**
	 * Calls the AutomatedPrintWorkflow biz connectors of the enabled server plugins 
	 * and requests them to resolve operations.
	 *
	 * The default shipped AutomatedPrintWorkflow plugin implements this feature and
	 * replaces the PlaceDossier operation with PlaceImage/PlaceArticleElement operations.
	 *
	 * @param integer $objectId
	 * @param ObjectOperation[] $operations
	 * @return ObjectOperation[]
	 */
	public static function resolveOperations( $objectId, $operations )
	{
		$resolvedOperations = null;
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		if( $operations ) {
			$resolvedOperations = array();
			foreach( $operations as $key => $operation ) {
				$connectors = BizServerPlugin::searchConnectors( 'AutomatedPrintWorkflow', null );
				if( $connectors ) {
					foreach( $connectors as $connector ) {
						$connectorOperations = BizServerPlugin::runConnector( $connector,
							'resolveOperation', array( $objectId, $operation )
						);
						$resolvedOperations = array_merge( $resolvedOperations, $connectorOperations );
					}
				}
			}
		}
		return $resolvedOperations;
	}
	
}