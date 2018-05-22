<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once 'ElvisCSException.php';

class ElvisCSLinkedToOtherSystemException extends ElvisCSException
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.CSLinkedToOtherSystemException';
	}
}