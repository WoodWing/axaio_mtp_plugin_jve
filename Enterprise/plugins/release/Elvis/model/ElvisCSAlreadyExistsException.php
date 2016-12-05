<?php

require_once 'ElvisCSException.php';

class ElvisCSAlreadyExistsException extends ElvisCSException
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.CSAlreadyExistsException';
	}
}
