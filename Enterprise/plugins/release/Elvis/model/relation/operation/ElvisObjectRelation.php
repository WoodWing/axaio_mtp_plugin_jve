<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once __DIR__.'/../../AbstractRemoteObject.php';

class ElvisObjectRelation extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.operation.ObjectRelation';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		require_once __DIR__.'/../../../logic/ElvisAMFClient.php';
		if( ElvisAMFClient::getInterfaceVersion() >= 2 ) {
			$name = 'ElvisObjectRelation_v2';
		} else {
			$name = parent::getName();
		}
		return $name;
	}

	/** @var string $type 'Placed' or 'Contained' */
	public $type;

	/** @var string $assetId */
	public $assetId;

	/** @var ElvisPlacement[] */
	public $placements;
}

/**
 * Adds the $publicationDate and $publicationUrl properties to the ElvisObjectRelation data class in backwards compatible manner.
 *
 * These properties are introduced since Elvis 5.18 and Enterprise 10.1.1. If both versions are
 * matching or newer, the interface version is set to v2 and this data class is used instead.
 *
 * @since 10.1.1
 */
class ElvisObjectRelation_v2 extends ElvisObjectRelation
{
	/** @var string $publicationDate Datetime in yyyyMMdd'T'HH:mm:ss format */
	public $publicationDate;

	/** @var string $publicationUrl Web location where image is published to. Used for images placed on Publish Forms. */
	public $publicationUrl;
}

class ElvisObjectRelationFactory
{
	/**
	 * Creates an Elvis object relation (AMF data object).
	 *
	 * @return ElvisObjectRelation|ElvisObjectRelation_v2
	 */
	public static function create()
	{
		$className = ElvisObjectRelation::getName();
		return new $className;
	}
}