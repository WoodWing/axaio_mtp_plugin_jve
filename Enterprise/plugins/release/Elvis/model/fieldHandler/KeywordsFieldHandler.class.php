<?php

require_once 'ReadOnlyFieldHandler.class.php';

// TODO: remove and us standard ReadWriteFieldHandler? doesn't seem to add a lot of logic...
class KeywordsFieldHandler extends ReadWriteFieldHandler {

	function __construct() {
		$this->lvsFieldName = "tags";
		$this->multiValue = true;
		$this->dataType = "text";
		
		$this->entMetadataCategory = 'ContentMetaData';
		$propertyInfos = BizProperty::getPropertyInfos();
		$this->property = $propertyInfos['Keywords'];
	}
}
?>
