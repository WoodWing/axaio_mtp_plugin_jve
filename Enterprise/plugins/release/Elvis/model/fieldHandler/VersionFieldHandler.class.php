<?php

require_once 'ReadOnlyFieldHandler.class.php';
require_once dirname(__FILE__) . '/../../util/ElvisUtils.class.php';


class VersionFieldHandler extends ReadOnlyFieldHandler {
	
	function __construct() {
		parent::__construct("versionNumber", false, "number", "Version");
	}
	
	public function read($entMetadata, $elvisMetadata) {
		$elvisVersion = $elvisMetadata[$this->lvsFieldName];
		if(isset($elvisVersion)){
			$enterpriseVersion = ElvisUtils::getEnterpriseVersionNumber($elvisVersion);
			$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = $enterpriseVersion;
		}
	}
}
?>
