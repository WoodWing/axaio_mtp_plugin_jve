<?php

require_once dirname(__FILE__) . '/AbstractRemoteObject.php';

/**
 * Simplified version of com.ds.acm.logic.xmlservice.search.model.SearchResponse
 */
class ElvisSearchResponse extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.logic.xmlservice.search.model.SearchResponse';
	}
	
	/**
	 * @var int
	 */
	public $firstResult;

	/**
	 * @var int
	 */
	public $maxResultHits;

	/**
	 *  @var int
	 */
	public $totalHits;

	/**
	 *  @var SabreAMF_ArrayCollection<HitElement>
	 */
	public $hits;
	
	/**
	 * 
	 * @var unknown_type - not used
	 */
	public $facets;
	
}