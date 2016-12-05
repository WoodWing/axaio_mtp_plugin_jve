<?php

require_once 'AbstractRemoteObject.php';

/**
 * Simplified version of com.ds.acm.logic.xmlservice.search.model.SearchResponse
 */
class ElvisSearchResponse extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.logic.xmlservice.search.model.SearchResponse';
	}

	/** @var int $firstResult */
	public $firstResult;

	/** @var int $maxResultHits */
	public $maxResultHits;

	/** @var int $totalHits */
	public $totalHits;

	/** @var ElvisEntHit $hits */
	public $hits;

	/** @var unknown_type $facets not used */
	public $facets;
}