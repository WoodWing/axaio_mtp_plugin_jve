<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBChanneldata extends DBBase
{
	const TABLENAME = 'channeldata';
	
	/**
	 * Removes channeldata definitions made for given server plug-in that ain't used anymore by that plug-in.
	 *
	 * @param array $excludePropNames New configuration set from server plug-in that should stay 
	 *                                (-> to exclude from deletion).
	 */
	/*static public function deleteUnusedPluginChanneldata( array $excludePropNames )
	{
		if( !empty($excludePropNames) ) {
			$dbDriver = DBDriverFactory::gen();
			foreach( $excludePropNames as $key => $val ) {
				$excludePropNames[$key] = "'".$dbDriver->toDBString($val)."'";
			}
			$excludePropNames = implode( ',', $excludePropNames );
			$where = "`name` NOT IN ($excludePropNames) ";
			self::deleteRows('channeldata', $where);
		}
	}*/
	
	/**
	 * Checks if a custom admin property is specified for a certain issue (id), section (id) and name.
	 * @since 7.0.13
	 *
	 * @param integer $issueId
	 * @param integer $sectionId
	 * @param string $name Property name.
	 * @return boolean Whether or not exists.
	 */
	static public function channelDataExists( $issueId, $sectionId, $name )
	{
		$where = '`issue` = ? AND `section` = ? AND `name` = ?';
		$params = array( intval( $issueId ), intval( $sectionId ), $name );
		return (bool)self::getRow( self::TABLENAME, $where, array('issue'), $params );
	}
	
	/**
	 * Insert a record in the channeldata table.
	 * @since 7.0.13
	 *
	 * @deprecated Since 7.5.0. Please use the other insertXxx() functions of this class.
	 * @todo Remove this function for v8
	 *
	 * @param array $values array of column/value pairs
	 */
	static public function insert( array $values )
	{
		self::insertRow( self::TABLENAME, $values, false );
	}

	/**
	 * Update all records that fulfill the where clause.
	 * @since 7.0.13
	 *
	 * @deprecated Since 7.5.0. Please use the other updateXxx() functions of this class.
	 * @todo Remove this function for v8
	 *
	 * @param array $values array of column/value pairs
	 * @param string $where where clause
	 * @param array $params parameters used for subsitute ?-marks in where clause
	 * @return boolean Whether or not successful.
	 */
	public static function update( array $values, $where, array $params )
	{
		return self::updateRow( self::TABLENAME, $values, $where, $params );
	}
	
	/**
	 * Return a custom admin property (value) configured for a given issue (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @param string $name Property name
	 * @return string|null Property value. NULL when not found.
	 */
	public static function getCustomPropertyValueForIssue( $issueId, $name )
	{
		$params = array( intval($issueId), 0, $name );
		$where = '`issue` = ? AND `section` = ? AND `name` = ?';
		$row = self::getRow( self::TABLENAME, $where, array('value'), $params );
		return $row ? $row['value'] : null;
	}

	/**
	 * Set a custom admin property (value) configured for a given issue (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @param string $name Property value
	 * @return boolean Whether or not successful.
	 */
	public static function setCustomPropertyValueForIssue( $issueId, $name, $value )
	{
		if( self::channelDataExists( $issueId, 0, $name ) ) {
			$retVal = self::updateCustomPropertyForIssue( $issueId, $name, $value );
		} else {
			$retVal = self::insertCustomPropertyForIssue( $issueId, $name, $value );
		}
		return $retVal;
	}

	/**
	 * Return all custom admin properties (names/values) configured for a given issue (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @return array DB rows each representing a property (name/value).
	 */
	public static function getCustomPropertiesForIssue( $issueId )
	{
		$params = array( intval($issueId), 0 );
		$where = '`issue` = ? AND `section` = ?';
		return self::listRows( self::TABLENAME, '', '', $where, '*', $params );
	}

	/**
	 * Insert a custom admin property (name/value) for a given issue (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @param string $name Property name
	 * @param string $value Property value
	 * @return boolean Whether or not successful.
	 */
	public static function insertCustomPropertyForIssue( $issueId, $name, $value )
	{
		$row = array( 
			'issue' => intval( $issueId ),
			'section' => 0,
			'name' => $name,
			'value' => '#BLOB#' );
		return (bool)self::insertRow( self::TABLENAME, $row, false, $value );
	}

	/**
	 * Update a custom admin property (name/value) for a given issue (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @param string $name Property name
	 * @param string $value Property value
	 * @return boolean Whether or not successful.
	 */
	public static function updateCustomPropertyForIssue( $issueId, $name, $value )
	{
		$row = array( 
			'issue' => intval( $issueId ),
			'section' => 0,
			'name' => $name,
			'value' => '#BLOB#' );
		$where = ' `issue` = ? AND `section` = ? AND `name` = ?';
		$params = array( intval( $issueId ), 0, $name );
		return (bool)self::updateRow( self::TABLENAME, $row, $where, $params, $value );
	}

	/**
	 * Return all section mappings (names/values) configured for a given issue (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @return array DB rows each representing a property (name/value) for a section (id).
	 */
	public static function getSectionMappingsForIssue( $issueId )
	{
		$params = array( intval( $issueId ), 0 );
		$where = '`issue` = ? AND `section` != ?';
		return self::listRows( self::TABLENAME, '', '', $where, '*', $params );
	}

	/**
	 * Insert a section mapping (names/values) for a given issue (id) - section (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @param integer $sectionId
	 * @param string $name Property name
	 * @param string $value Property value
	 * @return boolean Whether or not successful.
	 */
	public static function insertSectionMappingsForIssue( $issueId, $sectionId, $name, $value )
	{
		$row = array( 
			'issue' => intval( $issueId ),
			'section' => intval( $sectionId ),
			'name' => $name,
			'value' => '#BLOB#' );
		return (bool)self::insertRow( self::TABLENAME, $row, false, $value );
	}

	/**
	 * Remove all section mappings (names/values) for a given issue (id) - section (id).
	 * @since 7.5.0
	 *
	 * @param integer $issueId
	 * @param integer $sectionId
	 * @param string $name Property name
	 * @param string $value Property value
	 * @return boolean Whether or not successful.
	 */
	public static function deleteSectionMappingsForIssue( $issueId, $sectionId )
	{
		$where = '`issue` = ? AND `section` = ?';
		$params = array( intval( $issueId ), intval( $sectionId ) );
		return (bool)self::deleteRows( self::TABLENAME, $where, $params );
	}

	/**
	 * Returns the default column name of the auto-increment field. 
	 */
	static public function getAutoincrementColumn()
	{
		return '';
	}		

	/**
	 * Checks if a property set for an issue is unique. A property can have multiple values or just one.
	 * So only one value can be passed or an array of values. Unique means that at least one of the values of
	 * the property differs from the values of the other issues.
	 * @param integer $issueId Id of the issue which is checked. If 0 is passed the uniqueness is checked
	 * for all issues. E.g. during a copy action the id is not yet known so you want to check against all issues.
	 * @param string $name Name of the property
	 * @param mixed $values Value(s) to be checked.
	 * @return bool True if unique else false.
	 */
	public static function isUnique( $issueId, $name, $values )
	{
		if ( !is_array( $values )) {
			$values = array( $values );
		}

		$where = "`name` = ? "; // Check against all issues
		$params = array( $name );
		if ( $issueId > 0 ) {
			$where .= "AND `issue` != ? "; // Check against all 'other' issues.
			$params[] = $issueId;
		}
		$where .= "AND `value` LIKE ? "; // Check against all issues
		
	 	// As the 'value' column is of the type text, in case of Mssql, the LIKE operator must be used.
		foreach ( $values as $value ) {
			$paramsAll = $params;
			$paramsAll[] = $value;
			$result = (DBBase::getRow( self::TABLENAME, $where, array('issue'), $paramsAll )) ? true : false ;
			if ( !$result ) { // One of the values of the property is the different.
				return true;
			}
		}
		
		return false;
	}	

	/**
	 * Checks if a property set for an issue is unique within a channel. A property can have multiple values or just one.
	 * So only one value can be passed or an array of values. Unique means that at least one of the values of
	 * the property differs from the values of the other issues in the channel.
	 * @param integer $channelId Id of the channeld which is checked.
	 * @param integer $issueId Id of the issue which is checked. If 0 is passed the uniqueness is checked
	 * for all issues in the channel. E.g. during a copy action the id is not yet known so you want to check against
	 * all issues in the channel.
	 * @param string $name Name of the property
	 * @param mixed $values Value(s) to be checked.
	 * @return bool True if unique else false.
	 */
	public static function isUniqueInChannel( $channelId, $issueId, $name, $values )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$channelIssueRows = DBIssue::listChannelIssues( $channelId );
		
		if ( !is_array( $values )) {
			$values = array( $values );
		}

		$where = "`name` = ? "; // Check against all issues
		$params = array( $name );
		if ( $issueId > 0 ) {
			unset( $channelIssueRows[$issueId]);
		}
		$channelIssues = array_keys( $channelIssueRows);

		$where .= 'AND ' . DBBase::addIntArrayToWhereClause('issue', $channelIssues, false);
		$where .= "AND `value` LIKE ? "; // Check against all issues
		
	 	// As the 'value' column is of the type text, in case of Mssql, the LIKE operator must be used.
		foreach ( $values as $value ) {
			$paramsAll = $params;
			$paramsAll[] = $value;
			$result = (DBBase::getRow( self::TABLENAME, $where, array('issue'), $paramsAll )) ? true : false ;
			if ( !$result ) { // One of the values of the property is the different.
				return true;
			}
		}
		
		return false;
	}	
	
	// - - - - - - - - Custom admin properties per entity - - - - - - 

	/**
	 * Returns all custom admin properties (names/values) configured for a given entity
	 * and admin data object (id).
	 *
	 * @since 9.0.0
	 * @param string $entity 'Publication', 'PubChannel' or 'Issue'
	 * @param integer $id Admin data object ID
	 * @return array List of AdmExtraMetaData (the retrieved custom props).
	 * @param array $typeMap Lookup table with custom property names as keys and types as values.
	 */
	public static function getCustomProperties( $entity, $id, $typeMap )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php'; // AdmExtraMetaData
		switch( $entity ) {
			case 'Publication':
				$params = array( intval( $id ), 0, 0, 0 );
				$fieldNames = array( 'publication', 'name', 'value' );
			break;
			case 'PubChannel':
				$params = array( 0, intval( $id ), 0, 0 );
				$fieldNames = array( 'pubchannel', 'name', 'value' );
			break;
			case 'Issue':
				$params = array( 0, 0, intval( $id ), 0 );
				$fieldNames = array( 'issue', 'name', 'value' );
			break;
			default:
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Bad entity param given.' );
		}
		$where = '`publication` = ? AND `pubchannel` = ? AND `issue` = ? AND `section` = ?';
		$rows = self::listRows( self::TABLENAME, '', '', $where, $fieldNames, $params );
		
		$extraMetaDatas = null;
		if( !is_null($rows) ) { // no DB error
			$extraMetaDatas = array();
			foreach( $rows as $row ) {
				$type = $typeMap[$row['name']];
				$extraMetaData = new AdmExtraMetaData();
				$extraMetaData->Property = $row['name'];
				$extraMetaData->Values = self::unpackPropValues( $type, $row['value'] );
				$extraMetaDatas[] = $extraMetaData;
			}
		}
		return $extraMetaDatas;
	}

	/**
	 * Saves a custom admin property (name/value) in the DB for a given entity
	 * and admin data object (id).
	 *
	 * @since 9.0.0
	 * @param string $entity 'Publication', 'PubChannel' or 'Issue'
	 * @param integer $id Admin data object ID
	 * @param array $extraMetaDatas List of AdmExtraMetaData (custom props to save).
	 * @param array $typeMap Lookup table with custom property names as keys and types as values.
	 * @return boolean Whether or not successful.
	 */
	public static function saveCustomProperties( $entity, $id, $extraMetaDatas, $typeMap )
	{
		$retVal = true;
		if( $extraMetaDatas ) foreach( $extraMetaDatas as $extraMetaData ) {
			$exists = self::doesCustomPropertyExist( $entity, $id, $extraMetaData->Property );
			$type = $typeMap[$extraMetaData->Property];
			if( $exists ) {					 	
				$saved = self::updateCustomProperty( $entity, $id, $extraMetaData, $type );
			} else {
				$saved = self::insertCustomProperty( $entity, $id, $extraMetaData, $type );
			}
			if( !$saved ) {
				$retVal = false;
			}
		}
		return $retVal;
	}

	/**
	 * Inserts a custom admin property (name/value) in the DB for a given entity
	 * and admin data object (id).
	 *
	 * @since 9.0.0
	 * @param string $entity 'Publication', 'PubChannel' or 'Issue'
	 * @param integer $id Admin data object ID
	 * @param AdmExtraMetaData $extraMetaData Custom admin property to store.
	 * @return boolean Whether or not successful.
	 */
	private static function insertCustomProperty( $entity, $id, AdmExtraMetaData $extraMetaData, $type )
	{
		$name = $extraMetaData->Property;
		$value = self::packPropValues( $type, $extraMetaData->Values );
		$row = array( 
			'publication' => 0,
			'issue' => 0,
			'section' => 0,
			'name' => $name,
			'value' => '#BLOB#' );
		switch( $entity ) {
			case 'Publication':
				$row['publication'] = intval( $id );
			break;
			case 'PubChannel':
				$row['pubchannel'] = intval( $id );
			break;
			case 'Issue':
				$row['issue'] = intval( $id );
			break;
			default:
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Bad entity param given.' );
		}
		return self::insertRow( self::TABLENAME, $row, false, $value ) !== false;
		// L> Note that insertRow() returns 0 on success because smart_channeldata
		//    has no id field and so the 'inserted record id' from the DB driver remains zero.
		//    Therefor we do not cast the return value to boolean, but compare with "!== false".
	}

	/**
	 * Updates a custom admin property (name/value) in the DB for a given entity
	 * and admin data object (id).
	 *
	 * @since 9.0.0
	 * @param string $entity 'Publication', 'PubChannel' or 'Issue'
	 * @param integer $id Admin data object ID
	 * @param AdmExtraMetaData $extraMetaData Custom admin property to store.
	 * @return boolean Whether or not successful.
	 */
	private static function updateCustomProperty( $entity, $id, AdmExtraMetaData $extraMetaData, $type )
	{
		$name = $extraMetaData->Property;
		$value = self::packPropValues( $type, $extraMetaData->Values );
		$row = array( 
			'publication' => 0,
			'issue' => 0,
			'section' => 0,
			'name' => $name,
			'value' => '#BLOB#' );
		switch( $entity ) {
			case 'Publication':
				$params = array( intval( $id ), 0, 0, 0, $name );
				$row['publication'] = intval( $id );
			break;
			case 'PubChannel':
				$params = array( 0, intval( $id ), 0, 0, $name );
				$row['pubchannel'] = intval( $id );
			break;
			case 'Issue':
				$params = array( 0, 0, intval( $id ), 0, $name );
				$row['issue'] = intval( $id );
			break;
			default:
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Bad entity param given.' );
		}
		$where = '`publication` = ? AND `pubchannel` = ? AND `issue` = ? AND `section` = ? AND `name` = ?';
		return (bool)self::updateRow( self::TABLENAME, $row, $where, $params, $value );
	}
	
	/**
	 * Checks if a custom admin property is specified for a given entity, 
	 * admin data object id and custom admin property name.
	 *
	 * @since 9.0.0
	 * @param string $entity 'Publication', 'PubChannel' or 'Issue'
	 * @param integer $id Admin data object ID
	 * @param string $name Custom admin property name.
	 * @return boolean Whether or not exists.
	 */
	private static function doesCustomPropertyExist( $entity, $id, $name )
	{
		switch( $entity ) {
			case 'Publication':
				$params = array( intval( $id ), 0, 0, 0, $name );
				$fieldNames = array( 'publication' );
			break;
			case 'PubChannel':
				$params = array( 0, intval( $id ), 0, 0, $name );
				$fieldNames = array( 'pubchannel' );
			break;
			case 'Issue':
				$params = array( 0, 0, intval( $id ), 0, $name );
				$fieldNames = array( 'issue' );
			break;
			default:
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'Bad entity param given.' );
		}
		$where = '`publication` = ? AND `pubchannel` = ? AND `issue` = ? AND `section` = ? AND `name` = ?';
		return (bool)self::getRow( self::TABLENAME, $where, $fieldNames, $params );
	}
	
	/**
	 * Converts a DB package (read from DB) into list of custom admin property values 
	 * Typically called when user typed values needs to be loaded / shown.
	 *
	 * Custom property values are stored in the DB in a BLOB field. When read from
	 * the DB they are typed to a PHP data primitive that matches the property type.
	 * For example, a multilist type is stored as comma separated string in the BLOB
	 * field in DB but need to be converted ('unpacked') to a PHP array once read from DB.
	 * And, the other way around, it needs to be converted back ('packed') before storing
	 * into the DB again. The functions packPropValues() and unpackPropValues() do these
	 * conversions for all supported custom property types.
	 *
	 * @since 9.0.0
	 * @todo: Make this function private.
	 * @param string $type Custom property type.
	 * @param string $package Value as stored in DB (BLOB field).
	 * @return array List of PHP type casted property values.
	 */
	public static function unpackPropValues( $type, $package )
	{
		switch( $type ) {
			case 'integer':
				$propVals = array( intval( $package ) );
				break;
			case 'double':
				$propVals = array( doubleval( $package ) );
				break;
			case 'bool':
				$propVals = array( ($package == 'on' ? true : false) );
				break;
			case 'multilist':
			case 'multistring':
				$propVals = explode( ',', $package );
				break;
			case 'password':
				$propVals = array( self::decrypt($package) );
				break;
			default:
				$propVals = array( $package );
				break;
		}
		return $propVals;
	}

	/**
	 * Converts a list of custom admin property values into a package that is ready 
	 * to get stored in DB. Typically called when user typed values needs to be saved.
	 * See unpackPropValues() function header for more details.
	 *
	 * @since 9.0.0
	 * @todo: Make this function private.
	 * @param string $type Custom property type.
	 * @param array $values List of PHP type casted property values.
	 * @return array Value as stored in DB (BLOB field).
	 */
	public static function packPropValues( $type, $values )
	{
		switch( $type ) {
			case 'integer':
			case 'double':
				$package = is_numeric($values[0]) ? $values[0] : 0;
				break;
			case 'bool':
				$package = ($values[0] == true ? 'on' : '');
				break;
			case 'multilist':
			case 'multistring':
				$package = implode( ',', $values );
				break;
			case 'password':
				$package = self::encrypt($values[0]);
				break;
			default:
				$package = $values[0];
				break;
		}
		return $package;
	}

    static private function encrypt($text)
    {
    	return $text;
        //return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, 'enterprise_salt', $text,
        //	MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

    static private function decrypt($text)
    {
    	return $text;
        //return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, 'enterprise_salt', base64_decode($text),
        //	MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }
}
