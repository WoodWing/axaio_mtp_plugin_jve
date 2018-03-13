<?php
/**
 * @package    Enterprise
 * @subpackage ServerPlugins
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business connector interface that can be implemented by a server plug-in connector.
 * It enables the connector to override metadata of source and target objects when creating
 * variants of them.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class ModifyVariantMetaData_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Enables this connector to override metadata values when creating a variant. The values of both the source (original)
	 * and the target (variant) can be overridden.
	 *
	 * @param Object &$source
	 * @param Object &$target
	 */
	abstract public function modifyVariantMetadata( &$source, &$target );


	// ===================================================================================

	// Generic methods that can be overruled by a connector:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that cannot be overruled by a connector:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
