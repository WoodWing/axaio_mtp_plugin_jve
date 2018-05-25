<?php
/**
 * Implements DB querying of PropertyInfo objects from smart_properties table.
 * Customizations made in MetaData maintenance pages are reflected through PropertyInfo objects.
 *
 * @since 		v7.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class AdmPropertyInfo  {} // TODO: Add to admin WSDL and generate this class

class DBAdmProperty extends DBBase
{
	const TABLENAME = 'properties';

	/**
	 * Creates a new record at smart_properties table based on given AdmPropertyInfo object.
	 * Given object gets updated automatically with information from database after creation. This
	 * is typically useful to get the new Id, but also assures that all info is correctly round-tripped.
	 *
	 * @param AdmPropertyInfo $obj
	 * @throws BizException on fatal DB error
	 */
	static public function insertAdmPropertyInfo( AdmPropertyInfo &$obj )
	{
		self::clearError();
		$blobValues = null;
		$row = self::objToRow( $obj, $blobValues );
		$id = self::insertRow( self::TABLENAME, $row, true, $blobValues );
		if( $id ) {
			$tmp = self::getAdmPropertyInfo( $id );
			if( !is_null($tmp) ) {
				$obj = $tmp;
			}
		}
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Updates an existing record at smart_properties table based on given AdmPropertyInfo object.
	 * Given object gets updated automatically with information from database after creation. This is typically
	 * useful to enrich object with info from DB, but also assures that all info is correctly round-tripped.
	 *
	 * @param AdmPropertyInfo $obj
	 * @throws BizException on fatal DB error
	 */
	static public function updateAdmPropertyInfo( AdmPropertyInfo &$obj )
	{
		self::clearError();
		$blobValues = null;
		$row = self::objToRow( $obj, $blobValues );
		if( self::updateRow( self::TABLENAME, $row, "`id` = ?", array( intval($obj->Id) ), $blobValues ) ) {
			$tmp = self::getAdmPropertyInfo( $obj->Id );
			if( !is_null($tmp) ) {
				$obj = $tmp;
			}
		}
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
	}

	/**
	 * Retrieve an existing record from smart_properties table as an AdmPropertyInfo object.
	 *
	 * @param integer $id Record id at DB.
	 * @return AdmPropertyInfo object
	 * @throws BizException on fatal DB error
	 */
	static public function getAdmPropertyInfo( $id )
	{
		self::clearError();
		$row = self::getRow( self::TABLENAME, "`id` = ?", '*', array( intval($id) ) );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		if( !is_null($row) ) {
			return self::rowToObj( $row );
		}
		return null; // should never happen
	}

	/**
     * Converts a smart_property DB row to a AdmPropertyInfo object.
     *
     * @param array $row DB row (with key values)
     * @return AdmPropertyInfo
    **/
	final static protected function rowToObj( $row )
	{
		$obj = new AdmPropertyInfo();
		$obj->Id				= $row['id'];
		$obj->PublicationId		= $row['publication'];
		$obj->ObjectType		= $row['objtype'];
		$obj->Name				= $row['name'];
		$obj->DisplayName		= $row['dispname'];
		$obj->Category			= $row['category'];
		$obj->Type				= $row['type'];
		$obj->DefaultValue		= $row['defaultvalue'];
		$obj->ValueList			= self::unpackValueList($row['type'], $row['valuelist']);
		$obj->MinValue			= $row['minvalue'];
		$obj->MaxValue			= $row['maxvalue'];
		$obj->MaxLength			= $row['maxlen'];
		$obj->DBUpdated			= ($row['dbupdated'] == 0) ? false : true;
		$obj->DependentProperties = null; // future
		$obj->PluginName		= $row['serverplugin'];
		$obj->Entity			= $row['entity'];
		// future: DependentProperties
		// @todo: support for adminui, propertyvalues, minresolution, maxresolution, publishsystem, templateid
		return $obj;
	}

	/**
	 * Converts a AdmPropertyInfo object into a smart_property DB row.
	 *
	 * @param AdmPropertyInfo $obj
	 * @param null|array $blobValues
	 * @return array DB row (with key values)
	 */
	final static protected function objToRow( AdmPropertyInfo $obj, &$blobValues )
	{
		$row = array();
		if(isset($obj->Id)){
			$row['id'] 			= intval($obj->Id);
		}
		if(!is_null($obj->PublicationId)){
			$row['publication'] = intval($obj->PublicationId);
		}
		if(!is_null($obj->ObjectType)){
			$row['objtype'] 	= strval($obj->ObjectType);
		}
		if(!is_null($obj->Name)){
			$row['name'] 		= strval($obj->Name);
		}
		if(!is_null($obj->DisplayName)){
			$row['dispname'] 	= strval($obj->DisplayName);
		}
		if(!is_null($obj->Category)){
			$row['category'] 	= strval($obj->Category);
		}
		if(!is_null($obj->Type)){
			$row['type'] 		= strval($obj->Type);
		}
		if(!is_null($obj->DefaultValue)){
			$default = strval($obj->DefaultValue);
			if( $obj->Type == 'bool' ) {
				// Let's be robust and cast all kind of boolean notations to '1' or '0'.
				$default = strtolower( $default );
				$default = ($default == 'on' || $default == 'true' || $default == '1' || $default == 'y') ? '1' : '0';
			}
			$row['defaultvalue']= $default;
		}
		if(!is_null($obj->ValueList) && !is_null($obj->Type)){
			if ( !is_array($blobValues) ) {
				$blobValues = array();
			}
			$row['valuelist'] 	= '#BLOB#';
			$blobValues[] 		= self::packValueList( $obj->Type, $obj->ValueList );
		}
		if(!is_null($obj->MinValue)){
			$row['minvalue'] 	= strval($obj->MinValue);
		}
		if(!is_null($obj->MaxValue)){
			$row['maxvalue'] 	= strval($obj->MaxValue);
		}
		if(!is_null($obj->MaxLength)){
			$row['maxlen'] 		= intval($obj->MaxLength);
		}
		if(!is_null($obj->DBUpdated)){
			$row['dbupdated']	= ($obj->DBUpdated == false ? 0 : 1);
		}
		if(!is_null($obj->PluginName)){
			$row['serverplugin']= strval($obj->PluginName);
		}
		if(!is_null($obj->Entity)){
			$row['entity']		= strval($obj->Entity);
		}
		// future: DependentProperties
		// @todo: support for adminui, propertyvalues, minresolution, maxresolution, publishsystem, templateid
		return $row;
	}

	/**
	 * Implodes (multi)value array into single DB storage stream.
	 * Typically to build PropertyInfo->ValueList structure after DB retrieval.
	 *
	 * @param string $type Primitive data type, e.g. 'integer', 'bool', etc
	 * @param array $values List of value(s) to pack to DB stream.
	 * @return string Packed DB stream
	 */
	static private function packValueList( $type, $values )
	{
		switch( $type ) {
			case 'list':
			case 'multilist':
				$stream = implode( ',', $values );
				break;
			default:
				$stream = strval($values[0]);
				break;
		}
		return $stream;
	}

	/**
	 * Explodes single DB storage stream into (multi)value array.
	 * Typically used to prepare PropertyInfo->ValueList for DB storage.
	 *
	 * @param string $type Primitive data type, e.g. 'integer', 'bool', etc
	 * @param string $stream Packed DB stream to unpack to list of value(s).
	 * @return array Unpacked list of value(s)
	 */
	static private function unpackValueList( $type, $stream )
	{
		switch( $type ) {
			case 'list':
			case 'multilist':
				$values = explode( ',', $stream );
				break;
			default:
				$values = array( $stream );
				break;
		}
		return $values;
	}

	/**
	 * Retrieves all AdmPropertyInfo definitions for a given admin entity that were once specified
	 * and created by a server plug-in.
	 *
	 * @param string|null $entity Configuration type: Publication, PubChannel or Issue. NULL for all.
	 * @param string|null $pluginName Name of the server plug-in. NULL for all.
	 * @param string|null $propName Name of the custom admin property. NULL for all.
	 * @return AdmPropertyInfo[] Array of AdmPropertyInfo Objects.
	 * @throws BizException on SQL error.
	 */
	static public function getPropertyInfos( $entity = null, $pluginName = null, $propName = null )
	{
		$whereChunks = array();
		$params = array();
		if( !is_null($entity) ) {
			$whereChunks[] = "`entity` = ?";
			$params[] = $entity;
		}
		if( !is_null($pluginName) ) {
			$whereChunks[] = "`serverplugin` = ?";
			$params[] = $pluginName;
		}
		if( !is_null($propName) ) {
			$whereChunks[] = "`name` = ?";
			$params[] = $propName;
		}
		$where = implode( ' AND ', $whereChunks );
		$rows = self::listRows( self::TABLENAME, 'id', '', $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$objs = array();
		foreach( $rows as $row ) {
			$objs[] = self::rowToObj( $row );
		}
		return $objs;
	}

	/**
	 * Removes AdmPropertyInfo definitions made for given server plug-in that ain't used anymore by that plug-in.
	 *
	 * @param string $entity Configuration type: Publication, PubChannel, Issue, Edition, Section.
	 * @param string $pluginName Name of the server plug-in
	 * @param array $excludePropNames New configuration set from server plug-in that should stay (-> to exclude from deletion).
	 * @return void
	 */
	/*static public function deleteUnusedPluginPropInfos( $entity, $pluginName, array $excludePropNames )
	{
		$where = "`entity` = ? AND `serverplugin` = ? ";
		// Create a seperate where statement for the deletion of the channeldata
		$params = array( $entity, $pluginName );

		if( !empty($excludePropNames) ) {
			foreach( $excludePropNames as $key => $val ) {
				$dbDriver = DBDriverFactory::gen();
				$excludePropNames[$key] = "'".$dbDriver->toDBString($val)."'";
			}
			$excludePropNames = implode(',', $excludePropNames);
			$where .= "AND `name` NOT IN ($excludePropNames) ";
			self::deleteRows( self::TABLENAME, $where, $params );
		}
	}*/

	/**
	 * Deletes an AdmPropertyInfo.
	 *
	 * @param AdmPropertyInfo $obj
	 * @throws BizException Throws BizException if the object could not be deleted.
	 * @return bool Whether or not the operation was successful.
	 */
	public static function deleteAdmPropertyInfo( AdmPropertyInfo $obj )
	{
		self::clearError();
		$where = '`publication` = ? AND `objtype` = ? AND `name` = ?';
		$params = array( $obj->PublicationId, $obj->ObjectType, $obj->Name );
		$result = (bool)self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $result;
	}
}