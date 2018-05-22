<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once 'AbstractRemoteObject.php';

class ElvisFormattedValue extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.logic.xmlservice.search.model.FormattedValue';
	}

	/** @var object $value */
	public $value;

	/** @var string $formatted Formatted value */
	public $formatted;
}
