<?php
/**
 * @since      v9.8
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business connector interface that can be implemented by a server plug-in connector.
 * It enables the connector to customize automated placement of objects (contained by
 * a dossier) onto a given layout.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class AutomatedPrintWorkflow_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Enables this connector to resolve given object operations with other operations.
	 *
	 * This function is called whenever object operations are created. This is done explicitly
	 * through the CreateObjectOperations webservice (for example when a user places a dossier
	 * onto an InDesignArticle in the CS preview). Operations can also be created implicitly
	 * through the InstantiateTemplate, CreateObjects, and SaveObjects webservices.
	 *
	 * This connector can be used to either:
	 * 1. Refine or tweak an existing object operation;
	 * 2. Resolve the given operation into new object operations;
	 * 3. Acknowledge the operation, but consciously suppress it. (Return NULL)
	 *
	 * The default behaviour of this connector is to not acknowledge any operations. (Return empty array)
	 *
	 * When multiple operations are resolved from a single operation, the new operations
	 * will be inserted in place of the initial operation (meaning the initial operation
	 * is not automatically included).
	 * Note that operations are identified by their Id field, which requires a GUID value.
	 *
	 * Also note that when an operation is not acknowledged by any connector, it will be
	 * treated as a resolved operation and be added to the layout.
	 *
	 * @param integer $objectId Id of the Layout object the operations are going to be added to.
	 * @param ObjectOperation $operation The operation to resolve.
	 * @return ObjectOperation[]|null Resolved operations, NULL when $operation is recognized but should not be resolved.
	 */
	public function resolveOperation( $objectId, $operation )
	{
		return array(); // Do not resolve any operations by default.
	}
	
	/**
	 * Converts operation parameters from data objects into key-value pairs format.
	 *
	 * @param Param[] $params Params as data objects
	 * @return array Params as key-value pairs
	 */
	static protected function paramsToKeyValues( array $params )
	{
		$keyValues = array();
		foreach( $params as $param ) {
			$keyValues[$param->Name] = $param->Value;
		}
		return $keyValues;
	}

	/**
	 * Converts operation parameters from key-value pairs into data objects format.
	 *
	 * @param array $keyValues Params as key-value pairs
	 * @return Param[] Params as data objects
	 */
	static protected function keyValuesToParams( array $keyValues )
	{
		$params = array();
		foreach( $keyValues as $key => $value ) {
			$param = new Param();
			$param->Name = $key;
			$param->Value = $value;
			$params[] = $param;
		}
		return $params;
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); }
	final public function getInterfaceVersion() { return 1; }

}