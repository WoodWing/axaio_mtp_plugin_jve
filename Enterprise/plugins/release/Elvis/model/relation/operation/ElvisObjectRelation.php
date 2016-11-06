<?php

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisObjectRelation extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.ObjectRelation';
	}

	/** @var string $type 'Placed' or 'Contained' */
	public $type;

	/** @var string $assetId */
	public $assetId;

	/** @var ElvisPlacement[] */
	public $placements;
}