<?php

/**
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBVersion extends DBBase
{
	const TABLENAME = 'objectversions';
	/**
	 * Returns all field names of the smart_objectversions table.
	 * @return array of string.
	 */
	static private function getVersionFields()
	{
		static $versionFields;
		if( !isset($versionFields) ) {
			$versionFields = array(
				'description' => 'blob', 'descriptionauthor' => 'string',  'keywords' => 'blob', 
				'slugline' => 'string',  'format' => 'string', 
				'_columns' => 'int', 'width' => 'double', 'depth' => 'double', 'dpi' => 'double',
				'lengthwords' => 'int', 'lengthchars' => 'int', 'lengthparas' => 'int', 'lengthlines' => 'int',
				'colorspace' => 'string', 'plaincontent' => 'blob', 'filesize' => 'int',
				'comment' => 'string', 'types' => 'blob', 'modifier' => 'string', 'state' => 'int',
				'orientation' => 'int'
				// these have special treatment: 'majorversion', 'minorversion'
				);
		}
		return $versionFields;
	}
	
	/**
	 * Tells if a given field is defined at the smart_objectversions table.
	 *
	 * @param string $field
	 * @return boolean
	 */
	static public function isVersionField( $field )
	{
		return array_key_exists( $field, self::getVersionFields() );
	}
	
	/**
	 * Inserts a record in the smart_objectversions table
	 * @param string $id Object id
	 * @param integer $version Object version number
	 * @param array $arr The record (key-value pairs)
	 * @return boolean Wether or not the insert was successful.
	 */
	static public function insertVersion( $id, $version, $arr )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename( self::TABLENAME );
		$now = $arr['modified'];

		$sql = "INSERT into $db (`objid`, `majorversion`, `minorversion`, `created`";
		
		$verArr = array();
		if( !self::splitMajorMinorVersion( $version, $verArr ) ) {
			return false; // should never happen
		}
		
		$versionFields = self::getVersionFields();
		foreach( array_keys($versionFields) as $key ) {
			if (array_key_exists($key, $arr))
				$sql .= ", ".$dbDriver->quoteIdentifier($key);
		}

		$blob = null;

		$sql .= ") VALUES ($id, ".$verArr['majorversion'].",".$verArr['minorversion'].", '$now'";
		$comma = ',';
		$metaFields = BizProperty::getMetaDataObjFields();
		$metaFields = array_diff( $metaFields, array(null) );
		$metaFields = array_flip( $metaFields );
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		foreach( array_keys($versionFields) as $key ) {
			if ( array_key_exists( $key, $arr ) ) {
				$sql .= DBObject::handleObjectUpdateInsert( 'insert', $key, $metaFields[$key], $arr[$key], $dbDriver, $comma, $blob );
				$comma = ',';
			}
		}
		
		$sql .= " )";
		$sql = $dbDriver->autoincrement($sql);
		$sth = $dbDriver->query($sql, array(), $blob);

		return $sth ? true : false;
	}
	
	/**
	 * Removes one or more version records from the smart_objectversions table.
	 *
	 * @param array $ids Object ids of which versions need to be deleted
	 * @param mixed $versions Array of object version numbers, one for each id
	 * @return boolean Whether or not the deletions were successful.
	 */
	static public function deleteVersions( array $ids, $versions=null )
	{
		$where = '';
		$params = array();
		if( $versions ) {
			foreach( $ids as $i => $id ) {
				$verArr = array();
				if( !self::splitMajorMinorVersion( $versions[$i], $verArr ) ) {
					return false;
				}
				if( !empty( $where ) ) {
					$where .= ' OR ';
				}
				$where .= '(`objid`= ? AND `majorversion`= ? AND `minorversion`= ?)';
				$params[] = $id;
				$params[] = $verArr['majorversion'];
				$params[] = $verArr['minorversion'];
			}
		} else {
			$where .= '`objid` IN ( ' . implode( ',', $ids ) . ' )';
		}

		$result = DBBase::deleteRows(self::TABLENAME, $where, $params);
		return $result ? true : false;
	}
	
	/**
	 * Returns a record from smart_objectversions the table (order by version)
	 * @param string $id Object id
	 * @param integer $version Object version number
	 * @return array of key-value pairs
	 * @throws BizException
	 */
	static public function getVersions( $id, $version=null )
	{
		$dbdriver = DBDriverFactory::gen();
		$versionstable = $dbdriver->tablename(self::TABLENAME);
		$sql  = "SELECT v.*, ";
		$sql .= $dbdriver->concatFields(array("v.`majorversion`","'.'","v.`minorversion`")) . " as \"version\" ";
		$sql .= "FROM $versionstable v WHERE v.`objid`=$id";
		if( $version ) { // need to get specific version?
			$verArray = array();
			self::splitMajorMinorVersion( $version, $verArray );
			$sql .=' AND v.`majorversion` = '.$verArray['majorversion'].' AND v.`minorversion` = '.$verArray['minorversion'];
		}
		$sql .= " ORDER BY v.`majorversion`, v.`minorversion` ";
		$sth = $dbdriver->query($sql);

		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbdriver->error() );
		}

		$rows = array();
		for (;;)
		{
			$row = $dbdriver->fetch($sth);
			if (!$row) {
				break;
			}
			$rows[] = $row;
		}
		
		// Note the return contains objid which make translate fail, so for now stick with rows:
		// require_once BASEDIR.'/server/bizclasses/BizProperty.class.php'; // to convert to biz props
		// BizProperty::objRowArrayToPropValues( $rows );
		
		return $rows;
	}

	/**
	 * Returns all version records for each requested object id.
	 *
	 * @param array $objectIds Array of object ids
	 * @return array with object ids as key containing arrays of versions
	 * @throws BizException on failure
	 */
	static public function getVersionsForObjectIds( $objectIds )
	{
		foreach( $objectIds as $id ) {
			if( (string)intval($id) != (string)$id || $id <= 0 ) {
				throw new BizException( 'ERR_INVALID_OPERATION', 'Server', 'Invalid ObjectIds specified' );
			}
		}

		$dbdriver = DBDriverFactory::gen();

		$versionsTable = $dbdriver->tablename( self::TABLENAME );
		$sql = 'SELECT v.*, ';
		$sql .= $dbdriver->concatFields(array('`majorversion`',"'.'","`minorversion`")) . " as \"version\"";
		$sql .= 'FROM ' . $versionsTable . ' v ';
		$sql .= 'WHERE v.`objid` IN ( ' . implode( ',', $objectIds ) . ' ) ';
		$sql .= 'ORDER BY v.`majorversion`, v.`minorversion` ';
		$sth = $dbdriver->query( $sql );

		$rows = self::fetchResults( $sth, null, true );

		$objectVersions = array();
		if( !self::hasError() ) {
			if( $rows ) foreach( $rows as $row ) {
				$objectVersions[$row['objid']][] = $row;
			}
		}

		return $objectVersions;
	}

	/**
	 * Retrieves the current object version from DB. (Also tries smart_deletedobjects table.)
	 * @param string $objId Object id.
	 * @return string Version in major.minor notation.
	 */
	static public function getCurrentVersionNrFromId( $objId )
	{
		$where = '`id` = ?';
		$params = array( intval($objId) );
		$fieldNames = array( 'id', 'majorversion', 'minorversion' );
		$row = self::getRow( 'objects', $where, $fieldNames, $params );
		if( !$row ) { // try deleted objects...
			$row = self::getRow( 'deletedobjects', $where, $fieldNames, $params );
		}
		if( $row ) {
			return self::joinMajorMinorVersion( $row );
		} else {
			return ''; // error
		}
	}
    
	/**
	 * Splits major.minor object version into two parts.
	 * @param string $objVersion Object version in major.minor notation
	 * @param array $verArr Returned values at keys "majorversion" and "minorversion"
	 * @param string $fieldPrefix Optional. Prefix for "majorversion" and "minorversion" field names. Default none.
	 * @return boolean Succes
	 */
	static public function splitMajorMinorVersion( $objVersion, &$verArr, $fieldPrefix='' )
	{
		$parts = explode( '.', $objVersion );
		if( $parts && count( $parts ) > 1 ) {
			$verArr[$fieldPrefix.'majorversion'] = intval($parts[0]);
			$verArr[$fieldPrefix.'minorversion'] = intval($parts[1]);
			return true;
		}
		return false;
	}

	/**
	 * Formats major.minor version
	 * TO DO: this function accepts DB props, so need to become private!
	 * @param array $verArr Object array containing "majorversion" and "minorversion" key-values
	 * @param string $fieldPrefix Optional. Prefix for "majorversion" and "minorversion" field names. Default none.
	 * @return string
	 */
	static public function joinMajorMinorVersion( $verArr, $fieldPrefix='' )
	{
		if( self::validMajorMinorVersion( $verArr, $fieldPrefix ) ) {
			return $verArr[$fieldPrefix.'majorversion'].'.'.$verArr[$fieldPrefix.'minorversion'];
		} else {
			return ''; // error
		}
	}

	/**
	 * Formats major.minor version out of object properties (so NOT DB props)
	 *
	 * @param array $objProps Object array containing "MajorVersion" and "MinorVersion" key-values
	 * @param string $fieldPrefix Like 'server' in 'servermajorversion'.
	 * @return string
	*/
	static public function getVersionNumber( $objProps, $fieldPrefix='' )
	{
		if( self::validMajorMinorVersionBiz( $objProps ) ) {
			return $objProps[$fieldPrefix.'MajorVersion'].'.'.$objProps[$fieldPrefix.'MinorVersion'];
		} else {
			return ''; // error
		}
	}

	/**
	 * Checks if major and minor version are valid.
	 *
	 * @param array $verArr Key-value pairs majorversion and minorversion to get updated.
	 * @param string $fieldPrefix Optional. Prefix for "majorversion" and "minorversion" field names. Default none.
	 * @return boolean If valid true else false.
	 */
	static private function validMajorMinorVersion( $verArr, $fieldPrefix = '' )
	{
		return isset( $verArr[ $fieldPrefix.'majorversion' ] ) && isset( $verArr[ $fieldPrefix.'minorversion' ] )
		&& is_numeric( $verArr[ $fieldPrefix.'majorversion' ] ) && is_numeric( $verArr[ $fieldPrefix.'minorversion' ] )
		&& $verArr[ $fieldPrefix.'majorversion' ] != -1 && $verArr[ $fieldPrefix.'minorversion' ] != -1;
	}

	/**
	 * Checks if major and minor version are valid.
	 *
	 * @param array $objProps Object properties containing key-value pairs of major version and minor version.
	 * @return boolean True if valid else false.
	 */
	static private function validMajorMinorVersionBiz( $objProps )
	{
		return isset( $objProps['MajorVersion'] ) && isset( $objProps['MinorVersion'] )
		&& is_numeric( $objProps['MajorVersion'] ) && is_numeric( $objProps['MinorVersion'] )
		&& $objProps['MajorVersion'] != -1 && $objProps['MinorVersion'] != -1;
	}

	/**
	 * Increases the major version number.
	 * Pass null value for inital version.
	 * @param array $verArr Key-value pairs majorversion and minorversion to get updated.
	 */
	static public function nextPermanentVersion( &$verArr )
	{
		if( self::validMajorMinorVersion( $verArr ) ) {
			$verArr['majorversion']++;
			$verArr['minorversion'] = 0;
		} else { // 0.0 = first permanent version
			$verArr['majorversion'] = 0;
			$verArr['minorversion'] = 0;
		}
	}

	/**
	 * Increases the minor version number.
	 * Pass null value for inital version.
	 * @param array $verArr Key-value pairs majorversion and minorversion to get updated.
	 */
	static public function nextIntermediateVersion( &$verArr )
	{
		if( self::validMajorMinorVersion( $verArr ) ) {
			// don't touch: $verArr['majorversion']
			$verArr['minorversion']++;
		} else { // 0.1 = first intermediate version
			$verArr['majorversion'] = 0;
			$verArr['minorversion'] = 1;
		}
	}

	/**
	 * Get object major/minor versions of multiple objects.
	 *
	 * @param array $objIds array of object ids
	 * @param bool $deletedOnes
	 * @return array with key is <object id> and value is array like
	 *         array('id'=><object id>, 'majorversion'=><major version>, 'minorversion'=><minor version>)
	 *         returns null in case of a database error
	 */
	static public function getObjectVersions($objIds, $deletedOnes = false)
	{
		$tablename = $deletedOnes ? 'deletedobjects' : 'objects';
		$rows = array();
		if (count($objIds) > 0){
			$rows = self::listRows($tablename, 'id', '' , '`id` IN ('.implode(',',$objIds).')', array('id', 'majorversion', 'minorversion'));
		}

		return $rows;
	}
}
