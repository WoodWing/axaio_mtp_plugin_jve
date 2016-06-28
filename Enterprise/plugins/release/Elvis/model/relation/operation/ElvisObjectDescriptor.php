<?php

require_once dirname(__FILE__) . '/ElvisEntityDescriptor.php';

class ElvisObjectDescriptor extends ElvisEntityDescriptor {
	
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.ObjectDescriptor';
	}

	/**
	 * @var String
	 */
	public $type;	
	
	/**
	 * @var EntityDescriptor
	 */
	public $publication;
	
	/**
	 * @var EntityDescriptor
	 */
	public $category;
}
