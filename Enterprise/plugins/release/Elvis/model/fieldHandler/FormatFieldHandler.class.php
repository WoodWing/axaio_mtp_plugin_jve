<?php

require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
require_once 'ReadWriteFieldHandler.class.php';

class FormatFieldHandler extends ReadWriteFieldHandler {
	
	function __construct() {

		parent::__construct("mimeType", false, "text", "Format");

	}
	
	/**
	 * 
	 * @param unknown_type $smartObject
	 * @param SimpleHit $hit
	 */
	public function read($entMetadata, $elvisMetadata) {
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} =
			MimeTypeHandler::filePath2MimeType($elvisMetadata['filename']);
	}
}
?>
