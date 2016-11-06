<?php

require_once 'ReadOnlyFieldHandler.class.php';

class ShadowIdFieldHandler extends ReadOnlyFieldHandler
{
	public function __construct()
	{
		parent::__construct( "id", false, "text", "DocumentID" );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = $elvisMetadata['id'];
	}
}