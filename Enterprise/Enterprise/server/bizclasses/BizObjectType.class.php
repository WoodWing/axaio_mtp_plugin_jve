<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class BizObjectType
{
	const OBSOLETED  = 0x0001;
	const TEMPLATE   = 0x0002;
	const CONTAINER  = 0x0004;
	const LAYOUTKIND = 0x0008;

	/**
	 * List all object types defined in the workflow WSDL (SCEnterprise.wsdl).
	 *
	 * For each type, specify a set of flags that describe what kind of object type it is.
	 *
	 * @return array Object types as keys and flags as values.
	 */
	static private function getObjectTypesMap()
	{
		static $types = array(
			'Advert' => 0,
			'AdvertTemplate' => self::TEMPLATE,
			'Archive' => 0,
			'Article' => 0,
			'ArticleTemplate' => self::TEMPLATE,
			'Audio' => 0,
			'Dossier' => self::CONTAINER,
			'DossierTemplate' => self::TEMPLATE | self::CONTAINER,
			'Hyperlink' => 0,
			'Image' => 0,
			'Layout' => self::LAYOUTKIND,
			'LayoutModule' => self::LAYOUTKIND,
			'LayoutTemplate' => self::LAYOUTKIND | self::TEMPLATE | self::CONTAINER,
			'LayoutModuleTemplate' => self::LAYOUTKIND | self::TEMPLATE | self::CONTAINER,
			'Library' => 0,
			'Plan' => self::OBSOLETED,
			'PublishForm' => 0,
			'PublishFormTemplate' => self::TEMPLATE,
			'Presentation' => 0,
			'Other' => 0,
			'Task' => self::CONTAINER,
			'Spreadsheet' => 0,
			'Video' => 0,
		);
		return $types;
	}

	/**
	 * List all object types defined in the workflow WSDL (SCEnterprise.wsdl).
	 *
	 * @return string[] The object types.
	 */
	static public function getObjectTypes()
	{
		return array_keys( self::getObjectTypesMap() );
	}

	/**
	 * Checks whether the given object type is on one of the provided object type collections.
	 *
	 * @param string $objectType The object type to be checked.
	 * @param integer $typeFilters One or more bit flags (see class constants). Each flag represents a collection of object types to search in.
	 * @return boolean
	 */
	static public function isObjectTypeAnyOf( $objectType, $typeFilters )
	{
		$objectsTypes = self::getObjectTypesMap();
		$flags = $objectsTypes[$objectType];
		return boolval( $flags & $typeFilters );
	}
}