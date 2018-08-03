<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

/**
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Tika addendum to the DBObjects class.
**/

class TikaDBObjects
{
	const TABLENAME = 'objects';

	/**
	 * Counts the objects at smart_objects table that have no plain content
	 * but DO have text file attached with given formats.
	 *
	 * @return integer Object count.
	 */
	static public function countObjectsToExtract( $types, $formats, $pcEquals )
	{
		// Quit on bad params; nothing to do
		if( count($types) == 0 && count($formats) == 0 ) return 0; 

		// Run SQL
		$dbh = DBDriverFactory::gen();
		$dbo = $dbh->tablename(self::TABLENAME);
		$params = array();
		$where = self::buildWhereClause( $dbh, $params, $types, $formats, $pcEquals );
		$sql = "SELECT count(*) as `c` FROM $dbo o WHERE $where";
		$sth = $dbh->query($sql,$params);

		// Return record count
		$row = $dbh->fetch($sth);
		return intval($row['c']);
	}

	/**
	 * Get object rows for which plaincontent needs to be extracted, up to specified maximum amount.
	 *
	 * @param integer	$lastObjId The last (max) object id that was handled the previous time. Used for pagination.
	 * @param integer	$maxCount  Maximum number of objects to return. Used for pagination.
	 * @return array of object rows
	 */
	static public function getObjectsToExtract( $lastObjId, $maxCount, $types, $formats, $pcEquals )
	{
		$rows = array();
		$dbh = DBDriverFactory::gen();
		$dbo = $dbh->tablename(self::TABLENAME);
		
		$params = array();
		$where = self::buildWhereClause( $dbh, $params, $types, $formats, $pcEquals );
		$verFld = $dbh->concatFields( array( 'o.`majorversion`', "'.'", 'o.`minorversion`' )).' as "version"';
		$sql = "SELECT o.`id`, $verFld, o.`storename`, o.`types` FROM $dbo o WHERE $where AND o.`id` > ? ORDER BY o.`id` ASC ";
		$params[] = intval($lastObjId);
		
		if( $maxCount > 0 ) {
			$sql = $dbh->limitquery( $sql, 0, $maxCount );
		}
		$sth = $dbh->query($sql, $params);
		while( ( $row = $dbh->fetch($sth) ) ) {
			$rows[] = $row;
		}
		return $rows;
	}
	
	static private function buildWhereClause( $dbh, &$params, $types, $formats, $pcEquals )
	{
		// Build the WHERE clause
		$where = '';
		if( count($types) > 0 ) {
			$typesSQL = "'".implode("','",$types)."'";
			if( $where ) $where .= 'AND ';
			$where .= 'o.`type` IN ('.$typesSQL.') '; // can not use '?' param; quotes get escaped
		}
		if( count($formats) > 0 ) {
			$formatsSQL = "'".implode("','",$formats)."'";
			if( $where ) $where .= 'AND ';
			$where .= 'o.`format` IN ('.$formatsSQL.') '; // can not use '?' param; quotes get escaped
		}
		if( !is_null($pcEquals) ) {
			if( $where ) $where .= 'AND ';
			$where .= self::buildWhereBlobParam($pcEquals, 'o', 'plaincontent');
			$params[] = $pcEquals;
		}
		return $where;
	}
	
   static private function buildWhereBlobParam(&$param, $tablePrefix, $paramname)
    {
		$dbdriver = DBDriverFactory::gen();
        $param = $dbdriver->toDBString($param);
		require_once BASEDIR .'/server/dbclasses/DBQuery.class.php';
        $param = DBQuery::escape4like($param, '|');
        
        $sql = '';
        
        switch (DBTYPE)
        {
            case 'mysql':
            {
                $sql = "CONVERT( $tablePrefix.`$paramname` USING utf8) COLLATE utf8_general_ci LIKE ?";
                $sql .= " ESCAPE '|' ";
                break;
            }
            case 'mssql':
            {
                $sql = "$tablePrefix.`$paramname` LIKE ?";
                $sql .= " ESCAPE '|' ";
                break;
            }
        }
        return "($sql)";    	
    }	
}