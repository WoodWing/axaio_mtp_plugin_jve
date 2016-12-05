<?php

class MappedField
{
	/** @var  string $fieldName */
	public $fieldName;
	/** @var  string $dataType text, number, decimal, datetime, date, json  */
	public $dataType;
	/** @var  boolean $multiValue */
	public $multiValue;
	/** @var  string $wwMetadataCategory */
	public $wwMetadataCategory;
	/** @var Property $property Name, DisplayName, Type */
	public $property;

	/**
	 * MappedField constructor.
	 *
	 * @param string $fieldName
	 * @param string $dataType
	 * @param bool $multiValue
	 * @param string $wwMetadataCategory
	 * @param string $wwName
	 * @param string $wwDisplayName
	 * @param string $wwType
	 */
	// FIXME 1: check displayName and datatype, should be in Enterprise (localized?) Is there a list of Property objects by name where we can fetch the properties from?
	// FIXME 2: elvis FieldInfo should be passed from server in LoginResponse
	public function __construct( $fieldName, $dataType, $multiValue, $wwMetadataCategory, $wwName, $wwDisplayName, $wwType )
	{
		$this->fieldName = $fieldName;
		$this->dataType = $dataType;
		$this->multiValue = $multiValue;
		$this->wwMetadataCategory = $wwMetadataCategory;
		$this->property = new Property( $wwName, $wwDisplayName, $wwType );
	}
}