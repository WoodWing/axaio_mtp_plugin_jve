<?php

require_once __DIR__.'/ElvisEntityDescriptor.php';

class ElvisObjectDescriptor extends ElvisEntityDescriptor
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.ObjectDescriptor';
	}

	/** @var string $type */
	public $type;

	/** @var ElvisEntityDescriptor $publication */
	public $publication;

	/** @var ElvisEntityDescriptor $category */
	public $category;
}
