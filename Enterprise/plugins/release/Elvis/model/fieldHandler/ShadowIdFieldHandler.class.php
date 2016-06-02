<?php

require_once 'ReadOnlyFieldHandler.class.php';
require_once dirname(__FILE__) . '/../../config.php';

class ShadowIdFieldHandler extends ReadOnlyFieldHandler {
	
	function __construct() {
		parent::__construct("id", false, "text", "DocumentID");
	}
	
	public function read($entMetadata, $elvisMetadata) {
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} =
			$elvisMetadata['id'];
	}

}
?>
