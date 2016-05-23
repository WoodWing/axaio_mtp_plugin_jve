<?php

require_once dirname(__FILE__) . '/ReadWriteFieldHandler.class.php';

class ReadOnlyFieldHandler extends ReadWriteFieldHandler
{
	public function __construct($lvsFieldName, $multiValue, $dataType, $entPropertyName) {
		parent::__construct($lvsFieldName, $multiValue, $dataType, $entPropertyName);
	}
	
	public function write($entMetadata, &$elvisMetadata) {
		return null;
		// Do nothing
	}
}