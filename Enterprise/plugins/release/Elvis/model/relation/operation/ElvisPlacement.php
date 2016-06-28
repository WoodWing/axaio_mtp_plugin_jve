<?php

require_once dirname(__FILE__) . '/../../AbstractRemoteObject.php';

class ElvisPlacement extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.Placement';
	}

	/**
	 * @var Page
	 */
	public $page;
	
	/**
	 * @var double
	 */
	public $top;
	
	/**
	 * @var double
	 */
	public $left;
	
	/**
	 * @var double
	 */
	public $width;
	
	/**
	 * @var double
	 */
	public $height;
	
	/**
	 * @var boolean
	 */
	public $onPasteBoard;

	/**
	 * @var boolean
	 */
	public $onMasterPage;

	/**
	 * @var SabreAMF_ArrayCollection<EntityDescriptor>
	 */
	public $editions;
}
