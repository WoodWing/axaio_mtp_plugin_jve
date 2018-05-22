<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

abstract class AbstractRemoteObject
{
	/**
	 * Return the name of the class
	 *
	 * @return string
	 */
	public static function getName()
	{
		return get_called_class();
	}
}