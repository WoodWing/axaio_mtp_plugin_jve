<?php

require_once dirname(__FILE__) . '/../../AbstractRemoteObject.php';

class ElvisObjectRelation extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.operation.ObjectRelation';
	}
	
	/**
	 * @var String
	 */
	public $type; // Placed or Contained.

	/**
	 * @var String
	 */
	public $assetId;

	/**
	 * @var SabreAMF_ArrayCollection<Placement>
	 */
	public $placements;
}
