<?php

require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
require_once 'ReadOnlyFieldHandler.class.php';

class TypeFieldHandler extends ReadOnlyFieldHandler {
	
	function __construct() {
		parent::__construct("assetDomain", false, "text", "Type");
	}
	
	/**
	 * 
	 * @param unknown_type $smartObject
	 * @param SimpleHit $hit
	 */
	public function read($entMetadata, $elvisMetadata) {
		$mimeType = '';
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} =
		MimeTypeHandler::filename2ObjType($mimeType, $elvisMetadata['filename'], false);
	}
}
?>
