<?php

require_once dirname(__FILE__) . '/AbstractRemoteObject.php';

/**
 * Simplified version of com.ds.acm.logic.xmlservice.search.model.EntHit
 */
class ElvisEntHit extends AbstractRemoteObject {
	
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.EntHit';
	}
	
	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $thumbnailUrl;

	/**
	 * @var string
	 */
	public $previewUrl;
	
	/**
	 * @var string
	 */
	public $originalUrl;
	
	/**
	 * 
	 */
	public $metadata;
	
	/**
	 * @var int
	 */
	public $permissions;
	
}