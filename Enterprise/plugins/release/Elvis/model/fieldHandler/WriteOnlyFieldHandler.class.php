<?php

require_once dirname(__FILE__) . '/ReadWriteFieldHandler.class.php';

class WriteOnlyFieldHandler extends ReadWriteFieldHandler
{
	public function __construct($lvsFieldName, $multiValue, $dataType, $entPropertyName) {
		parent::__construct($lvsFieldName, $multiValue, $dataType, $entPropertyName);
	}
	
	public function read($smartObject, $hit) {
		// Do nothing
	}
}