<?php
/**
 * EntHit data class.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_EntHit
{
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

	/**
	 * Convert a stdClass object into an Elvis_DataClasses_EntHit object.
	 *
	 * REST responses from Elvis server are JSON decoded and result into stdClass.
	 * This function can be called to convert it to the real data class Elvis_DataClasses_EntHit.
	 *
	 * @since 10.5.0
	 * @param stdClass $stdClassHit
	 * @return Elvis_DataClasses_EntHit
	 */
	public static function fromStdClass( stdClass $stdClassHit ) : Elvis_DataClasses_EntHit
	{
		/** @var Elvis_DataClasses_EntHit $hit */
		$hit = WW_Utils_PHPClass::typeCast( $stdClassHit, 'Elvis_DataClasses_EntHit' );
		$hit->metadata = (array)$hit->metadata;
		if( isset( $hit->id ) ) {
			$hit->metadata[ 'id' ] = $hit->id;
		}
		foreach( $hit->metadata as $key => $value ) {
			if( isset( $value->value ) ) {
				$hit->metadata[ $key ] = $value->value;
			}
		}
		$datetimes = array( 'assetCreated', 'assetFileModified', 'fileCreated', 'fileModified' );
		foreach( $datetimes as $datetime ) {
			if( isset( $hit->metadata[ $datetime ] ) ) {
				$hit->metadata[ $datetime ] = $hit->metadata[ $datetime ] / 1000; // msec to sec
			}
		}
		return $hit;
	}
}