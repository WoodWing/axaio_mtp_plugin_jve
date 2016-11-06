<?php

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisPlacement extends AbstractRemoteObject
{

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.Placement';
	}

	/** @var Page $page */
	public $page;

	/** @var double $top */
	public $top;

	/** @var double $left */
	public $left;

	/** @var double $width */
	public $width;

	/** @var double $height */
	public $height;

	/** @var boolean $onPasteBoard */
	public $onPasteBoard;

	/** @var boolean $onMasterPage */
	public $onMasterPage;

	/** @var ElvisEntityDescriptor[] */
	public $editions;
}
