<?php
/**
 * @package 	Enterprise
 * @subpackage 	AdobeDps2
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Manages the custom Upload Status field in smart_objects table for Adobe DPS article uploads.
 */

class AdobeDps2_DbClasses_UploadStatus extends DBBase
{
	/**
	 * Resolves the Upload Status field values for given layouts (ids).
	 *
	 * @param array|null $layoutIds List of layout ids.
	 * @return string[] List of Upload Status values. (Keys = layout ids.)
	 */
	public static function resolveUploadStatusForLayoutIds( $layoutIds )
	{
		$uploadStatuses = array();
		if( $layoutIds ) {
			$layoutIds = array_keys($layoutIds);
			$dbDriver = DBDriverFactory::gen();
			$params = array();
			$sql =  'SELECT `id`, `C_DPS2_UPLOADSTATUS` as "uploadstatus" '.
					'FROM '.$dbDriver->tablename( 'objects' ).' '.
					'WHERE '.self::makeWhereForSubstitutes( array('id' => $layoutIds), $params );
			$sth = $dbDriver->query( $sql, $params );
			$rows = DBBase::fetchResults( $sth, 'id', false, $dbDriver );
			if( $rows ) foreach( $rows as $row ) {
				$uploadStatuses[$row['id']] = $row['uploadstatus'];
			}
		}
		return $uploadStatuses;
	}
}