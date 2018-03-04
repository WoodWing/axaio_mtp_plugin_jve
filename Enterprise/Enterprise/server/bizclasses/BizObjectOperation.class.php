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
		require_once BASEDIR.'/server/bizclasses/BizObjectLock.class.php';
		$objectLock = new BizObjectLock( $objectId, $user );
		if( !$objectLock->isLocked() ) {
			$objectLock->lockObject();
			$lockedByUs = true;
		} else {
			if( !$objectLock->isLockedByUser( $user ) ) {
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
			$objectLock->releaseLock();
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
		$resolvedOperations = array();
		if( $operations ) {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connectors = BizServerPlugin::searchConnectors( 'AutomatedPrintWorkflow', null );
			if( $connectors ) {
				foreach( $operations as $operation ) {
					// Since ES 10.2.0 this part is redesigned to allow recurively resolving operations.
					// For example PlaceDigitalArticle can be resolved into PlaceArticle which can be resolved into multiple
					// PlaceArticleElement. This can be done regardless of the order the connectors are called because it
					// keeps on resolving operation as long as it detects one of the connectors returns different results.
					$connectorOperations = array();
					self::resolveOperation( $connectors, $objectId, $operation, $connectorOperations );
					$resolvedOperations = array_merge( $resolvedOperations, $connectorOperations );
				}
			}
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 'Resolved operations for object (id='.$objectId.'): '.
				self::operationsToString( $resolvedOperations ) );
		}
		return $resolvedOperations;
	}

	/**
	 * Resolves a give operation by calling the resolveOperation() function of AutomatedPrintWorkflow connectors.
	 *
	 * As long as one (or more) connectors resolve the operation into something different, this function recursively
	 * calls itself until none of the connectors has anything to adjust on the resolved operations.
	 *
	 * @param EnterpriseConnector[] $connectors
	 * @param integer $objectId
	 * @param ObjectOperation $operation
	 * @param ObjectOperation[] $connectorOperations
	 * @return ObjectOperation[]
	 */
	private static function resolveOperation( $connectors, $objectId, $operation, &$connectorOperations )
	{
		$newOperations = self::resolveOperationByConnectors( $connectors, $objectId, $operation );
		$resolvedDiffers = empty( $newOperations ) ||
			( count( $newOperations ) == 1 && !self::isSameOperation( reset($newOperations), $operation ) );
		if( $resolvedDiffers ) {
			foreach( $newOperations as $newOperation ) {
				self::resolveOperation( $connectors, $objectId, $newOperation, $connectorOperations );
			}
		} else {
			$connectorOperations = array_merge( $connectorOperations, $newOperations );
		}
		return $connectorOperations;
	}

	/**
	 * Tells whether two operations are the same.
	 *
	 * @param ObjectOperation $lhs Left hand side
	 * @param ObjectOperation $rhs Right hand side
	 * @return bool
	 */
	private static function isSameOperation( ObjectOperation $lhs, ObjectOperation $rhs )
	{
		return $lhs->Name == $rhs->Name && $lhs->Type == $rhs->Type;
	}

	/**
	 * Resolves a give operation by calling the resolveOperation() function of AutomatedPrintWorkflow connectors.
	 *
	 * It transforms the returned values of resolveOperation() function calls into something easier to handle by our caller:
	 * - NULL (connector asks to remove operation) => empty array (asking caller to remove this operation and stop resolving)
	 * - empty array (connector indicates there is nothing to resolve) => array with the same/given operation (asking caller to add this operation and stop resolving)
	 * - array with one or more operations (connector tells it did resolve) => array with the resolved operations (asking caller to continue resolving)
	 *
	 * @param EnterpriseConnector[] $connectors
	 * @param integer $objectId
	 * @param ObjectOperation $operation
	 * @return ObjectOperation[]
	 */
	private static function resolveOperationByConnectors( $connectors, $objectId, $operation )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

		$connectorOperations = array();
		$keepOperation = true;
		foreach( $connectors as $connectorClass => $connector ) {
			$thisConnectorOperations = BizServerPlugin::runConnector( $connector,
				'resolveOperation', array( $objectId, $operation )
			);
			if( LogHandler::debugMode() ) {
				LogHandler::Log( __CLASS__, 'DEBUG',
					'Connector '.$connectorClass.' resolved operation '.self::operationToString( $operation ).' into: '.
					self::operationsToString( $thisConnectorOperations )  );
			}
			if( is_null( $thisConnectorOperations ) ) {
				// This connector has recognized the operation has resolved it into nothing, so the operation should be removed.
				$keepOperation = false;
				$thisConnectorOperations = array();
			} elseif( is_array( $thisConnectorOperations ) && !empty( $thisConnectorOperations ) ) {
				// This connector has recognized the operation has resolved it into something, so those operations should be kept.
				$connectorOperations = array_merge( $connectorOperations, $thisConnectorOperations );
			}
		}
		if( empty( $connectorOperations ) && $keepOperation ) {
			// If none of the connectors have been able to resolve operations, it means that the operation has
			// not been recognized by any of the plugins or it could no longer be transformed into another operation.
			// Those are simply returned as resolved operations to allow further processing (in InDesign Server script).
			$connectorOperations[] = $operation;
		}
		if( LogHandler::debugMode() ) {
			LogHandler::Log( __CLASS__, 'DEBUG', 'Connectors have resolved operation '.
				self::operationToString( $operation ).' into: '.self::operationsToString( $connectorOperations ) );
		}
		return $connectorOperations;
	}

	/**
	 * Composes a string of a given list of object operations for debug purposes.
	 *
	 * @param ObjectOperation[] $operations
	 * @return string
	 */
	private static function operationsToString( $operations )
	{
		if( is_null( $operations ) ) {
			return '(null)';
		} elseif( empty ( $operations ) ) {
			return '(empty)';
		}
		return implode( ', ', array_map( array( __CLASS__, 'operationToString' ), $operations ) );
	}

	/**
	 * Composes a string of a given object operations for debug purposes.
	 *
	 * @param ObjectOperation $operation
	 * @return string
	 */
	private static function operationToString( ObjectOperation $operation )
	{
		return $operation->Name.' ('.$operation->Type.')';
	}
}