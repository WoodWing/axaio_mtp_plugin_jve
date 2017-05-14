<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
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
	 * Called when user places a dossier onto an InDesignArticle in the CS preview.
	 * CS then calls CreateObjectOperations and so the core server calls this function.
	 *
	 * Also through InstantiateTemplate, CreateObjects and SaveObjects, operations can
	 * be created implicity, and so the core server calls this function.
	 *
	 * When there are operations listed that needs to be refined/tweaked/resolved server 
	 * side, the connector can do here. Then, when the layout object is about to open 
	 * for editing in SC, the (resolved) operations will be executed on the layout. 
	 *
	 * When one operation is passed in, and many are returned, the new operations are
	 * inserted in the operation list, directly after the first returned operation.
	 * Note that operations are identified by their Id field, which requires a GUID value.
	 *
	 * @param integer $objectId
	 * @param ObjectOperation $operation
	 * @return ObjectOperation[] Resolved operations.
	 */
	public function resolveOperation( $objectId, $operation )
	{
		return array( $operation ); // by default no resolve
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