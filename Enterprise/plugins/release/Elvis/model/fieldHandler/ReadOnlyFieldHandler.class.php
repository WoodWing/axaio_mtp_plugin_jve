<?php

require_once 'ReadWriteFieldHandler.class.php';

class ReadOnlyFieldHandler extends ReadWriteFieldHandler
{
	public function __construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName )
	{
		parent::__construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName );
	}

	public function write( $entMetadata, &$elvisMetadata )
	{
		return null; // Do nothing
	}
}