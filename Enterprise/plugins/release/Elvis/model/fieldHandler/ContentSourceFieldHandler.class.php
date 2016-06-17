<?php

require_once 'ReadOnlyFieldHandler.class.php';
require_once dirname(__FILE__) . '/../../config.php';

class ContentSourceFieldHandler extends ReadOnlyFieldHandler {
	
	function __construct() {
		parent::__construct("", false, "text", "ContentSource");
	}
	
	/**
	 * 
	 * @param unknown_type $smartObject
	 * @param SimpleHit $hit
	 */
	public function read($entMetadata, $elvisMetadata) {
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} =
			ELVIS_CONTENTSOURCEID;
	}
}
?>
