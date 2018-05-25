<?php
/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class with static functions called to do validations of name, password etc.
 * This allows to have custom validation rules.
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class NameValidation_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Validate the specified password. Return false to stop the core
	 * server from applying the standard validation rules.
	 * Throw a BizException when password is invalid.
	 *
	 * @param string $password - the new password about to apply
	 */
	abstract public function validatePassword( $password );

	/**
	 * Validate (and possible change) metadata before an object's meta data is changed.
	 * For example apply naming conventions or filter meta data to prevent a user
	 * from setting a specific field.
	 * 
	 * Throw a BizException to cancel the action.
	 *
	 * @param string	$user - user setting the meta data
	 * @param MetaData	$meta - meta data to validate
	 * @param array		$targets - list of Target objects to validate
	 */
	abstract public function validateMetaDataAndTargets( $user, MetaData &$meta, &$targets );

	/**
	 * Validate changed metadata before an object's meta data is changed during multi-set object properties.
	 *
	 * For example apply naming conventions or filter meta data to prevent a user
	 * from setting a specific field.
	 *
	 * During a multi-set object properties operation, this function is called per object to validate the
	 * changed metadata properties.
	 *
	 * $changedMetaDataValues can modified to remove or add properties on-the-fly.
	 * Note however added or removed properties will be applied to all objects part
	 * of the multi-set properties operation!
	 *
	 * Throw a BizException to cancel the action for the validated object.
	 * Note that throwing a BizException does not cancel the multi-set properties 
	 * operation for other objects.
	 *
	 * @param string	$user - user setting the meta data
	 * @param MetaData	$invokedMetaData MetaData containing essential properties of the tested object
	 * @param array		&$changedMetaDataValues Array of MetaDataValues, containing changed properties only that will be applied to all objects.
	 */
	public function validateMetaDataInMultiMode( $user, MetaData $invokedMetaData, array &$changedMetaDataValues )
	{
	}
	
	/**
	 * To inform the core how the connector wants the autonaming 
	 * @param string	$user - user setting the meta data
	 * @param MeataData $metaData Metadata of the object. 
	 * @param array Object targets  
	 * @param array Relations of the object 
	 * @return null|boolean Null if core should decide, true if autonaming must be applied, false if no autonaming must
	 * be applied.
	 */
	public function applyAutoNamingRule( $user, $metaData, $targets, $relations )
	{
		return null;
	}

	/**
	 * When a printable object is added to a dossier, the core server targets it automatically to the print targets of the dossier.
	 * However, this behaviour can be overruled by the connector by implementing this function.
	 *
	 * @param string $user - acting user
	 * @param Relation $relation The object relation being created.
	 * @param string $parentType Object type of parent e.g. Layout, Dossier, etc
	 * @param string $childType Object type of child, e.g. Article, Image, etc.
	 * @param Target[] &$extraTargets List of targets returned by connector to automatically add to the relation. Empty when none.
	 * @return boolean Return true to let the core apply the auto targeting rule, else false.
	 */
	public function applyAutoTargetingRule( $user, Relation $relation, $parentType, $childType, &$extraTargets )
	{
		return true;
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
