<?php

require_once 'ReadOnlyFieldHandler.class.php';

class CopyrightMarkedFieldHandler extends ReadOnlyFieldHandler {
	
	function __construct() {
		//Maps indirect to copyright field
		parent::__construct("copyright", false, "", "CopyrightMarked");
	}
	
	public function read($entMetadata, $elvisMetadata) {
		//True when copyright is set
		if (is_null($this->lvsFieldName) || !isset($elvisMetadata[$this->lvsFieldName])) {
			$copyright = null;
		} else {
			$copyright = $elvisMetadata[$this->lvsFieldName];
		}
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} =
		!(!isset($copyright) || trim($copyright)==='');
	}
}
?>
