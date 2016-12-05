<?php

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisTarget extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.Target';
	}

	/** @var ElvisEntityDescriptor $pubChannel */
	public $pubChannel;

	/** @var ElvisEntityDescriptor $issue */
	public $issue;

	/** @var ElvisEntityDescriptor[] $editions */
	public $editions;
}
