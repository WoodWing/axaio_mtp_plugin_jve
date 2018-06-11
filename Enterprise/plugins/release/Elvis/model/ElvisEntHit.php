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

	/**
	 * Convert a stdClass object into an ElvisEntHit object.
	 *
	 * REST responses from Elvis server are JSON decoded and result into stdClass.
	 * This function can be called to convert it to the real data class ElvisEntHit.
	 *
	 * @since 10.5.0
	 * @param stdClass $stdClassHit
	 * @return ElvisEntHit
	 */
	public static function fromStdClass( stdClass $stdClassHit ) : ElvisEntHit
	{
		/** @var ElvisEntHit $hit */
		$hit = WW_Utils_PHPClass::typeCast( $stdClassHit, 'ElvisEntHit' );
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