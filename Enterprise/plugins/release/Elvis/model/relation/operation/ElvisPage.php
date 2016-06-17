<?php

require_once dirname(__FILE__) . '/../../AbstractRemoteObject.php';

class ElvisPage extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.Page';
	}

	/**
	 * @var String
	 */
	public $number;

	/**
	 * @var double
	 */
	public $width;
	
	/**
	 * @var double
	 */
	public $height;
}