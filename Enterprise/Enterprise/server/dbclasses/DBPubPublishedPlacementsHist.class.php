<?php
require_once BASEDIR . "/server/dbclasses/DBBase.class.php";

/**
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v10.1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Tracks the history of published placements in the DB. Used by cropped images placed on publish forms.
 */

class PubPublishedPlacement
{
	/** @var integer $Id DB record id. */
	public $Id;

	/** @var integer $ObjectId The id of the Enterprise object of which the native file is used for publishing. */
	public $ObjectId;
	
	/** @var integer $PublishId Foreign key that refers to the publish operation of the dossier / publish form. */
	public $PublishId;
	
	/** @var string $ObjectVersion Major.Minor version of the object. */
	public $ObjectVersion;
	
	/** @var string $ExternalId Id retrieved from the CMS that refers to the published file. */
	public $ExternalId;
	
	/** 
	 * @var string $PlacementHash 
	 * This information is used to determine whether or not the placement has changed since the previous publish operation. '.
	 * When there is no matching record in this table found, the placement was changed or never published before.
	 * In both cases it needs to be re-published to reflect changes at the CMS. E.g. in case of image cropping
	 * the crop geometry, scale, form widget id, output mime type and DPI are hashed. The object id and version are excluded.
	 */
	public $PlacementHash;
}

class DBPubPublishedPlacementsHistory extends DBBase
{
	const TABLENAME = 'publishedplcmtshist';

	/**
	 * Adds a history record for a published placement in the DB.
	 *
	 * @param PubPublishedPlacement $history
	 * @return integer|boolean New inserted row DB Id when record is successfully inserted; FALSE otherwise.
	 */
	public static function addPublishedPlacementHistory( PubPublishedPlacement $history )
	{
		$row = self::objToRow( $history );
		return self::insertRow( self::TABLENAME, $row );
	}

	/**
	 * Retrieve published history of all published placements involved with a given publish operation.
	 *
	 * @param integer $publishId
	 * @return PubPublishedPlacement[]
	 */
	public static function listPublishedPlacements( $publishId )
	{
		$where = '`publishid` = ?';
		$params = array( $publishId );
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );

		$pubPlacements = array();
		if( $rows ) foreach( $rows as $row ) {
			$pubPlacements[] = self::rowToObj( $row );
		}
		return $pubPlacements;
	}

	/**
	 * Converts a data object to a DB row, both representing a publish placement history.
	 *
	 * @param PubPublishedPlacement $obj
	 * @return array DB row
	 */
	private static function objToRow( PubPublishedPlacement $obj )
	{
		$row = array();
		if( !is_null( $obj->Id ) ) {
			$row['id'] = $obj->Id;
		}
		if( !is_null( $obj->ObjectId ) ) {
			$row['objectid'] = $obj->ObjectId;
		}
		if( !is_null( $obj->PublishId ) ) {
			$row['publishid'] = $obj->PublishId;
		}
		self::splitMajorMinorVer( $obj->ObjectVersion, $row, '' );
		if( !is_null( $obj->ExternalId ) ) {
			$row['externalid'] = $obj->ExternalId;
		}
		if( !is_null( $obj->PlacementHash ) ) {
			$row['placementhash'] = $obj->PlacementHash;
		}
		return $row;
	}

	/**
	 * Converts a DB row to a data object, both representing a publish placement history.
	 *
	 * @param array $row
	 * @return PubPublishedPlacement
	 */
	private static function rowToObj( $row )
	{
		$obj = new PubPublishedPlacement();
		if( array_key_exists( 'id', $row ) ) {
			$obj->Id = $row['id'];
		}
		if( array_key_exists( 'objectid', $row ) ) {
			$obj->ObjectId = $row['objectid'];
		}
		if( array_key_exists( 'publishid', $row ) ) {
			$obj->PublishId = $row['publishid'];
		}
		self::joinMajorMinorVer( $obj->ObjectVersion, $row, '' );
		if( array_key_exists( 'externalid', $row ) ) {
			$obj->ExternalId = $row['externalid'];
		}
		if( array_key_exists( 'placementhash', $row ) ) {
			$obj->PlacementHash = $row['placementhash'];
		}
		return $obj;
	}
}
