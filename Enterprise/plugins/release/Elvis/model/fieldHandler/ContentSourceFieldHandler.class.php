<?php

require_once 'ReadOnlyFieldHandler.class.php';

class ContentSourceFieldHandler extends ReadOnlyFieldHandler
{
	public function __construct()
	{
		parent::__construct( "", false, "text", "ContentSource" );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		require_once __DIR__.'/../../config.php'; // ELVIS_CONTENTSOURCEID
		$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = ELVIS_CONTENTSOURCEID;
	}
}