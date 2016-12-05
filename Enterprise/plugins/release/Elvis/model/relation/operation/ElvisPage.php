<?php

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisPage extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.Page';
	}

	/** @var string $number */
	public $number;

	/** @var double $width */
	public $width;

	/** @var double $height */
	public $height;
}