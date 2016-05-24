<?php

/**
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBObjectRenditions extends DBBase
{
	const TABLENAME = 'objectrenditions';
	
	/**
	 * Retrieves edition/device specific renditions.
	 *
	 * @param integer $objectId
	 * @param integer $editionId Optional. When NULL, all editions renditions are are returned for the given object.
	 * @return array  List of DB rows. Keys of outer list are edition ids.
	 */
	static private function getEditionRenditions( $objectId, $editionId = null )
	{
		$where = ' `objid` = ? ';
		$params = array( intval($objectId) );
		if( $editionId ) {
			$where .= ' AND `editionid` = ? ';
			$params[] = intval($editionId);
		}
		$rows = self::listRows( self::TABLENAME, 'id', '', $where, '*', $params );
		$editionRows = null;
		if( $rows ) {
			$editionRows = array();
			foreach( $rows as $row ) {
				$editionRows[ $row['editionid'] ][] = $row;
			}
		}
		return $editionRows;
	}
	
	/**
	 * Retrieves available edition/device specific renditions for a given object.
	 *
	 * @param integer $objectId
	 * @return array of EditionRenditionsInfo
	 */
	static public function getEditionRenditionsInfo( $objectId )
	{
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$editionRows = self::getEditionRenditions( $objectId );
		$infos = array();
		if( $editionRows ) foreach( $editionRows as $editionId => $rows ) {
			$info = new EditionRenditionsInfo();
			$info->Edition = DBEdition::getEdition( $editionId );
			$info->Renditions = array();
			if( $rows ) foreach( $rows as $row ) {
				$typeInfo = new RenditionTypeInfo();
				$typeInfo->Rendition = $row['rendition'];
				$typeInfo->Type = $row['format'];
				$info->Renditions[] = $typeInfo;
			}
			$infos[] = $info;
		}
		return $infos;
	}
	
	/**
	 * Retrieves edition/device specific object version (at the time the rendition was saved).
	 * Note that e.g. output renditions can be saved one by one and so they do not have to have 
	 * the same version as the current object version...!
	 *
	 * @param integer $objectId
	 * @param integer $editionId
	 * @param string  $rendition
	 * @return string Object version in major.minor format. NULL when not available.
	 */
	static public function getEditionRenditionVersion( $objectId, $editionId, $rendition )
	{
		$where = ' `objid` = ? AND `editionid` = ? AND `rendition` = ? ';
		$params = array( intval($objectId), intval($editionId), $rendition );
		$orderBy = array( 'majorversion' => false, 'minorversion' => false ); // sort descending to find latest version
		$fields = array( 'majorversion', 'minorversion' );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params, $orderBy );
		return $row ? $row['majorversion'].'.'.$row['minorversion'] : null;
	}

	/**
	 * Retrieves edition/device specific object rendition format
	 *
	 * @param integer $objectId
	 * @param integer $editionId
	 * @param string  $rendition
	 * @return string Object format. NULL when not available.
	 */
	static public function getEditionRenditionFormat( $objectId, $editionId, $rendition )
	{
		$where = ' `objid` = ? AND `editionid` = ? AND `rendition` = ? ';
		$params = array( intval($objectId), intval($editionId), $rendition );
		$orderBy = array( 'majorversion' => false, 'minorversion' => false ); // sort descending to find latest version
		$fields = array( 'format', 'majorversion', 'minorversion' );
		$row = self::getRow( self::TABLENAME, $where, $fields, $params, $orderBy );
		return $row ? $row['format'] : null;
	}

	/**
	 * Retrieves edition/device specific object rendition formats
	 *
	 * @param integer $objectId
	 * @param string $version Version <major>.<minor>
	 * @param integer $editionId
	 * @param string  $rendition
	 * @return string[] File formats.
	 */
	static public function getEditionRenditionFormats( $objectId, $version, $editionId, $rendition )
	{
		$verArr = array();
		require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
		DBVersion::splitMajorMinorVersion( $version, $verArr );

		$where = ' `objid` = ? AND `editionid` = ? AND `rendition` = ? AND `majorversion` = ? AND `minorversion` = ? ';
		$params = array( intval($objectId), intval($editionId), $rendition, $verArr['majorversion'], $verArr['minorversion'] );
		$fields = array( 'format' );
		$rows = self::listRows( self::TABLENAME, null, null, $where, $fields, $params );
		$formats = array();
		if( $rows ) foreach( $rows as $row ) {
			$formats[] = $row['format'];
		}
		return $formats;
	}

	/**
	 * Stores an edition/device specific rendition.
	 *
	 * @param integer $objectId
	 * @param integer $editionId
	 * @param string  $rendition File rendition.
	 * @param string  $format    File format (mime-type).
	 * @param string  $version   Object version in major.minor format.
	 * @return boolean Whether or not the save operation was successful.
	 */
	public static function saveEditionRendition( $objectId, $editionId, $rendition, $format, $version )
	{
		$versionParts = explode( '.', $version );
		$row = array(
			'objid'        => intval( $objectId ), 
			'editionid'    => intval( $editionId ), 
			'rendition'    => $rendition, 
			'format'       => $format, 
			'majorversion' => intval( $versionParts[0] ), 
			'minorversion' => intval( $versionParts[1] ) 
		);
		return (bool)self::insertRow( self::TABLENAME, $row );
	}
	
	/**
	 * Deletes edition/device specific renditions.
	 *
	 * @param integer $objectId
	 * @param integer $editionId Optional. When NULL, all edition renditions get removed for the given object.
	 * @return boolean Whether or not the delete operation was successful.
	 */
	static public function deleteEditionRenditions( $objectId, $editionId = null )
	{
		$where = ' `objid` = ? ';
		$params = array( intval($objectId) );
		if( $editionId ) {
			$where .= ' AND `editionid` = ? ';
			$params[] = intval($editionId);
		}
		return (bool)self::deleteRows( self::TABLENAME, $where, $params );
	}

	/**
	 * Retrieve edition/device of the object for specific rendition and version
	 *
	 * @param integer $objectId Id of the object.
	 * @param string $rendition e.g. 'output'.
	 * @param string $version Version <major>.<minor>
	 * @return array with editions IDs. 
	 */
	static public function getEditionIds( $objectId, $rendition, $version )
	{
		if( $version ) {
			$verArr = array();
			require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
			if( !DBVersion::splitMajorMinorVersion( $version, $verArr ) ) {
				return false;
			}
		}	

		$where = ' `objid` = ? AND `rendition` = ? AND `majorversion` = ? AND `minorversion` = ? ';
		$params = array( intval($objectId), $rendition, $verArr['majorversion'], $verArr['minorversion'] );
		$rows = self::listRows( self::TABLENAME, 'editionid', '', $where, array('editionid'), $params );
		
		$editions = array(); 
		if ( $rows ) {
			$editions = array_keys($rows);
		}
		return $editions;
	}	
}
