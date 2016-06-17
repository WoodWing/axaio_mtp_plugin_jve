<?php

require_once dirname(__FILE__) . '/../../AbstractRemoteObject.php';

class ElvisUpdateObjectOperation extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.UpdateObjectOperation';
	}
	
	/**
	 * @var String
	 */
	public $enterpriseSystemId;
	
	/**
	 * @var ObjectDescriptor
	 */
	public $object;

	/**
	 * @var SabreAMF_ArrayCollection<ObjectRelation>
	 */
	public $relations;

	/**
	 * @var SabreAMF_ArrayCollection<Target>
	 */
	public $targets;
}
