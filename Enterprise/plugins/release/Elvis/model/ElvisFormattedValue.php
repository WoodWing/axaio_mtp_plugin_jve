<?php

require_once dirname(__FILE__) . '/AbstractRemoteObject.php';

class ElvisFormattedValue extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.logic.xmlservice.search.model.FormattedValue';
	}

	/**
	 * Value
	 *
	 * @var object
	 */
	public $value;
	
	/**
	 * Formatted value
	 *
	 * @var string
	 */
	public $formatted;
	
}
