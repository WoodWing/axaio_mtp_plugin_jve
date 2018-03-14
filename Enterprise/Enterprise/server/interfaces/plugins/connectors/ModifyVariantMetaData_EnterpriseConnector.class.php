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
	 * Enables this connector to override an object's properties when creating a variant. The values of both the source
	 * (original) and the variant can be overridden.
	 *
	 * The only properties that will be modified are the MetaData and Targets. No other object properties (such as Relations,
	 * Pages, Messages, etc.) need to be supplied to this function since they will be ignored.
	 *
	 * It allows to hook into the Create Print Variant action and set predefined metadata such as a custom name, status
	 * or targets.
	 *
	 * Default behaviour of the Create Print Variant operation is to copy the source metadata directly onto the variant.
	 *
	 * This function is ONLY called when creating a print variant from a digital article to a print article.
	 * This means that the ContentStation plugin should be enabled if you want to implement this connector.
	 *
	 * The way that metadata can be modified should be defined per implementation.
	 * For an example see the ContentStation plugin's ModifyVariantMetadata connector.
	 *
	 * @param Object $source The source object from which a variant is created.
	 * @param Object $variant The created variant object.
	 */
	abstract public function modifyVariantMetadata( Object $source, Object $variant );


	// ===================================================================================

	// Generic methods that can be overruled by a connector:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that cannot be overruled by a connector:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
