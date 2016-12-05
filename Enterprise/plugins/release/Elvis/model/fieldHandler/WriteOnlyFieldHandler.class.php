<?php

require_once 'ReadWriteFieldHandler.class.php';

class WriteOnlyFieldHandler extends ReadWriteFieldHandler
{
	public function __construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName )
	{
		parent::__construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $smartObject, $hit )
	{
		// Do nothing
	}
}