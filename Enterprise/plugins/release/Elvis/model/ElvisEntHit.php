<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Simplified version of com.ds.acm.logic.xmlservice.search.model.EntHit
 */

require_once __DIR__.'/AbstractRemoteObject.php';

class ElvisEntHit extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.EntHit';
	}

	/** @var string $id */
	public $id;

	/** @var string $thumbnailUrl */
	public $thumbnailUrl;

	/** @var string $previewUrl */
	public $previewUrl;

	/** @var string $originalUrl */
	public $originalUrl;

	/** @var array $metadata */
	public $metadata;

	/** @var int $permissions */
	public $permissions;
}