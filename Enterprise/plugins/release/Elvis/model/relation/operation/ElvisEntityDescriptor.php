<?php

require_once dirname(__FILE__) . '/../../AbstractRemoteObject.php';

class ElvisEntityDescriptor extends AbstractRemoteObject {
	
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.EntityDescriptor';
	}
	
	/**
	 * @var String
	 */
	public $id;
	
	/**
	 * @var String
	 */
	public $name;
}
