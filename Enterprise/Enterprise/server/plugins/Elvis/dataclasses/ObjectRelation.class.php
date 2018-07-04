<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_ObjectRelation
{
	/** @var string $type 'Placed' or 'Contained' */
	public $type;

	/** @var string $assetId */
	public $assetId;

	/** @var Elvis_DataClasses_Placement[] */
	public $placements;

	/** @var string $publicationDate Datetime in yyyyMMdd'T'HH:mm:ss format */
	public $publicationDate;

	/** @var string $publicationUrl Web location where image is published to. Used for images placed on Publish Forms. */
	public $publicationUrl;
}