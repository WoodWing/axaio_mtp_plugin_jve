<?php

class MappedField {

	public $fieldName;			// String
	public $dataType;			// String: text, number, decimal, datetime, date, json
	public $multiValue;			// Boolean
	public $wwMetadataCategory;	// String
	public $property;			// WW Property (Name, DisplayName, Type)

	/*public function __construct($fieldName, $dataType, $multiValue, $wwMetadataCategory, $property) {
		$this->fieldName = $fieldName;
	$this->dataType = $dataType;
	$this->multiValue = $multiValue;
	$this->wwMetadataCategory = $wwMetadataCategory;
	$this->property = $property;
	}*/
	
	// FIXME 1: check displayName and datatype, should be in Enterprise (localized?) Is there a list of Property objects by name where we can fetch the properties from?
	// FIXME 2: elvis FieldInfo should be passed from server in LoginResponse
	public function __construct($fieldName, $dataType, $multiValue, $wwMetadataCategory, $wwName, $wwDisplayName, $wwType) {
		$this->fieldName = $fieldName;
		$this->dataType = $dataType;
		$this->multiValue = $multiValue;
		$this->wwMetadataCategory = $wwMetadataCategory;
		$this->property = new Property($wwName, $wwDisplayName, $wwType);
	}
}