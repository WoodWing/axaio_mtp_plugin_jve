<?php
/**
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v4.2
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Manages the smart_publications DB table to support workflow functionality.
 * For admin functionality, the DBAdmPublication class must be used instead.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBPublication extends DBBase
{
	const TABLENAME = 'publications';

	/**
	 * Get Publication from smart_publications table.
	 *
	 * @param string $pubId
	 * @return array
	 */
	public static function getPublication($pubId)
    {
        $result = self::getRow(self::TABLENAME, "`id` = '$pubId' ", true);
        return $result;
    }
    
	/**
	 * Get DB records from smart_publications table.
	 *
	 * @param string|array $fieldNames
	 * @param integer[] $pubIds [9.7] Provide ids to get specific brands, or leave empty to get all brands (system wide).
	 * @return array
	 */
    public static function listPublications( $fieldNames = '*', $pubIds = array() )
    {
    	if( $pubIds ) {
    		$where = '`id` IN ( '.implode(',',$pubIds).' )';
    	} else {
	    	$where = '1>0'; // true
	    }
	    $orderBy = array( 'code' => true ); // ascending
        return self::listRows( self::TABLENAME, 'id', 'publication', $where, $fieldNames, array(), $orderBy );
    }

	/**
	 * Returns publications rows filtered on publication name or id (or a combination).
	 * If no filter is passed in all publication rows are returned sorted on sorting order.
	 *
	 * @param null $name
	 * @param null $id
	 * @return bool|null|resource
	 * @throws BizException
	 */
	static public function listPublicationsByNameId( $name = null, $id = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);

		$name = $dbDriver->toDBString($name);

		$sql = "SELECT * FROM $db WHERE 1=1";
		if ($name) { $sql .= " AND `publication` = '$name'"; }
		if ($id) { $sql .= " AND `id` = $id"; }
		$sql .= " ORDER BY `code`, `id`";
		$sth = $dbDriver->query($sql);
		return $sth;
	}

	static public function getPublicationName( $id ) 
	{
		$dbDriver = DBDriverFactory::gen();
		$dbo  = $dbDriver->tablename(self::TABLENAME);
		$sql = 'SELECT `id`, `publication` FROM '.$dbo.' WHERE `id` = '.$id;
		$sth = $dbDriver->query($sql);
		$row = $dbDriver->fetch($sth);
		if( empty($row) === false ) {
			return $row['publication'];
		} else {
			return '';
		}
	}
}
