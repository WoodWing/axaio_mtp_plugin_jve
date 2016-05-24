<?php

require_once dirname(__FILE__) . '/../../AbstractRemoteObject.php';

class ElvisTarget extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.Target';
	}

	/**
	 * @var EntityDescriptor
	 */
	public $pubChannel;
	
	/**
	 * @var EntityDescriptor
	 */
	public $issue;
	
	/**
	 * @var SabreAMF_ArrayCollection<EntityDescriptor>
	 */
	public $editions;
}
