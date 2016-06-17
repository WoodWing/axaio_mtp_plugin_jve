<?php

require_once dirname(__FILE__) . '/../../AbstractRemoteObject.php';

class ElvisDeleteObjectOperation extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.DeleteObjectOperation';
	}

	/**
	 * @var String
	 */
	public $enterpriseSystemId;

	/**
	 * @var ObjectDescriptor
	 */
	public $object;
}
