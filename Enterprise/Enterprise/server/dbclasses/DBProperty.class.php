<?php
/**
 * Implements DB querying of properties and property usages.
 * Customizations made in Dialog Setup and MetaData maintenance pages are respected.
 * 
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBProperty extends DBBase
{
	const TABLENAME = 'properties';
	
	/**
	 * Retrieves the properties from the database.
	 *
	 * List of properties for a specific level of customization. When no customizations are found at that level, it
	 * returns an empty collection. Since 9.0.0, extra property members called AdminUI, PublishSystem and TemplateId are
	 * returned which are not defined in the WSDL, but is used internally.
	 *
	 * @param string $publ    Publication ID.
	 * @param string $objType Object type.
	 * @param boolean $customOnly
	 * @param string $publishSystem
	 * @param integer $templateId
	 * @param bool $filtered Whether or not to exclude certain fields defined in the function or to return a full set.
	 * @param bool $onlyAllObjType Only return "All" object type property.
	 * @return PropertyInfo[]  Property definitions as used in the workflow WSDL.
	 */
	public static function getProperties( $publ, $objType, $customOnly = false, $publishSystem = null, $templateId = null, $filtered = true, $onlyAllObjType = false )
	{
		// validate params
		if( !$publ ) { $publ = 0; }
		if( !$objType ) { $objType = ''; }
		// null is not a valid index of an array
		$publishSystemIndex = ( $publishSystem ) ? $publishSystem : '';
		$templateIdIndex = ( $templateId ) ? $templateId : 0;

		static $propertiesStored  = array();
		// Check if properties are already available
		if (!empty($propertiesStored)) {
			if (isset($propertiesStored[$publ][$objType][$customOnly][$publishSystemIndex][$templateIdIndex]['result'])) {
				return $propertiesStored[$publ][$objType][$customOnly][$publishSystemIndex][$templateIdIndex]['result'];
			}
		}		
		
		require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';
		$params = array();

		if (empty($publ)) {
			$where = "pr.`publication` = 0 ";
		}
		else {
			$where = "(pr.`publication` = ? OR pr.`publication` = 0) ";
			$params[] = $publ;
		}

		// When objType is '', means 'All' object types, therefore no need set the objtype to return all
		if( !empty($objType) ) {
			$where .= "AND (pr.`objtype` = ? OR pr.`objtype` = '') ";
			$params[] = $objType;
		} else {
    		if( $onlyAllObjType ) { // Return only "All" object type property, excluding other object type
    			$where .= "AND pr.`objtype` = ? ";
    			$params[] = $objType;
    		}
    	}

		if( !empty( $publishSystem ) ) {
			$where .= "AND (pr.`publishsystem` = ? OR pr.`publishsystem` = '') ";
			$params[] = $publishSystem;
		}

		if( !empty($templateId ) ) {
			$where .= "AND (pr.`templateid` = ? OR pr.`templateid` = 0) ";
			$params[] = $templateId;
		}

		// Note: '_' is a wildcard, so we need to escape it to search on c_*:
		$customFilter = $customOnly ? "(pr.`name` LIKE '" . DBQuery::escape4like('c_' , '|') . "%' ESCAPE '|' OR pr.`name` LIKE '" . DBQuery::escape4like('C_', '|') . "%' ESCAPE '|') AND " : '';

		$where .= "AND pr.`entity` = 'Object' ";

		// Exclude properties for type File / ArticleComponent, these should never be visible to end users.
		if ( $filtered ) {
			$where .= self::getWherePartForExcludedPropertyTypes();
		}

		$where .= "AND " . $customFilter. 'pr.`dbupdated` = 1 ';

		$dbDriver = DBDriverFactory::gen();
		$dbProps = $dbDriver->tablename(  self::TABLENAME );
		$dbTermEntities = $dbDriver->tablename( 'termentities' );

		$sql = "SELECT pr.*, te.`name` as `termentityname`, te.`provider` as `autocompleteprovider`, te.`publishsystemid` ".
			"FROM $dbProps pr " .
			"LEFT JOIN $dbTermEntities te ON (te.`id` = pr.`termentityid` ) ".
			"WHERE $where ".
			'ORDER BY pr.`publication` DESC, pr.`objtype` DESC '; // Publication has precedence over object type.

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth, null, false, $dbDriver );

		$result = array();
		if ( $rows ) {
			$result = self::createPropertyInfos( $rows, $objType );
		}

		$propertiesStored[$publ][$objType][$customOnly][$publishSystemIndex][$templateIdIndex]['result'] = $result;

		return $result;
	}

	/**
	 * Transforms database property rows to PropertyInfo objects.
	 * Metadata can be specified on different levels e.g. All/All, All/Layout, WW News/All, WW News/Layout.
	 * Property rows are processed from more specific to the more general level. Normally only one level is taken
	 * into account. There are two exceptions:
	 * - Custom properties on a higher level are also taken into account.
	 * - The more specific level only contains properties added by server plug-ins. These custom properties cannot be
	 * changed in the Metadata set up. If the 'adminui' flag is set the property is displayed on the page otherwise they
	 * are hidden. These properties are commonly defined on All/Object Type (e.g. publication = 0, type = Layout) level.
	 *
	 * @param array $propertyRows Database property rows.
	 * @param string $objType Object type
	 * @return PropertyInfo[]
	 */
	static private function createPropertyInfos( array $propertyRows, $objType )
	{
		$propertyInfos = array();
		$holdPubl = $propertyRows[0]['publication'];
		$holdType = $propertyRows[0]['objtype'];
		$onlyAddedByPlugIn = true;
		$levelChanged = false;
		foreach( $propertyRows as $row ) {
			if( self::isLevelChanged( $holdPubl, $holdType, $row, $objType ) ) {
				$levelChanged = true;
			}
			if ( !$levelChanged ) {
				$propertyInfos = self::addRowToPropertyInfos( $propertyInfos, $row );
				$onlyAddedByPlugIn = $onlyAddedByPlugIn ? self::isAddedByPlugIn( $row ) : $onlyAddedByPlugIn;
			} else {
				if( $onlyAddedByPlugIn || self::isCustomPropertyName( $row['name'] ) ) {
					$propertyInfos = self::addRowToPropertyInfos( $propertyInfos, $row );
				}
			}
			$holdPubl = $row['publication'];
			$holdType = $row['objtype'];
		}

		return $propertyInfos;
	}

	/**
	 * Checks if the publication/object type is changed.
	 * When request object type is "All", means it include "All" and specific object type, it should treat in the same level
	 * When "All" is passed, the $objType is empty
	 *
	 * @param integer $holdPubl
	 * @param string $holdType
	 * @param array $propertyRow
	 * @param string $objType
	 * @return bool Publication or object type has changed, true, else false.
	 */
	static private function isLevelChanged( $holdPubl, $holdType, $propertyRow, $objType  )
	{
		$result = false;
		if( $holdPubl != $propertyRow['publication'] || (!empty($objType) && $holdType != $propertyRow['objtype'] ) ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Adds the database row, after translating it, to the other PropertyInfos.
	 *
	 * @param PropertyInfo[] $propertyInfos
	 * @param array $propertyRow
	 * @return PropertyInfo[]
	 */
	static private function addRowToPropertyInfos( array $propertyInfos, array $propertyRow )
	{
		if (isset($propertyInfos[$propertyRow['name']])){
			// property already exists, only copy the type if it's not set
			// this is the case when you define a custom property on brand level
			if (empty($propertyInfos[$propertyRow['name']]->Type)){
				$propertyInfos[$propertyRow['name']]->Type = $propertyRow['type'];
			}
		} else {
			$propInfo = self::rowToObj( $propertyRow );
			$propertyInfos[$propertyRow['name']] = $propInfo;
		}

		return $propertyInfos;
	}

	/**
	 * Checks if a property is added by a server plug-in.
	 *
	 * @param array $propertyRow
	 * @return bool Property is added by plug-in, true, else false.
	 */
	static private function isAddedByPlugIn( array $propertyRow )
	{
		return ( !empty( $propertyRow['serverplugin'] ) );
	}	

	/**
	 * Get the property type of the given property name.
	 *
	 * @param string $propertyName
	 * @param int $publicationId The publication level the custom property is defined for. 0 (Default) for all publication.
	 * @param string $objectType The object type level the custom property is defined for. Empty (Default) for all object type.
	 * @return string The property type such as 'string', 'int', 'bool', 'list' and etc, Empty when type is not defined.
	 */
	public static function getCustomPropertyType( $propertyName, $publicationId = 0 , $objectType = '' )
	{
		$customProps = self::listCustomPropertyTypes( $publicationId, $objectType );
		return isset( $customProps[$propertyName] ) ? $customProps[$propertyName] : '';
	}

	/**
	 * Returns a list of custom property name and its type.
	 *
	 * When Publication and ObjectType are not set (which is the default), only custom properties defined at
	 * all-all (pub-objectType) level are returned.
	 * When Publication and/or ObjectType are set, the custom properties defined at the requested customized level
	 * and all-all (pub-objectType) level will be returned.
	 *
	 * Custom property name is unique through out the whole
	 * Enterprise, and so when one custom property is defined in a specific level AND also all-all level, we can assume
	 * that the custom property type is the same, thus function only return one time (for that specific custom property )
	 * in the list.
	 *
	 * The property type can be 'string', 'int', 'bool', 'list' and etc.
	 *
	 * @param int $publication The publication level the custom property is defined for. 0 (Default) for all publication.
	 * @param string $objectType The object type level the custom property is defined for. Empty (Default) for all object type.
	 * @return array List of Key-value pairs where key is the property name and the value is the property type.
	 */
	public static function listCustomPropertyTypes( $publication = 0, $objectType = '' )
	{
		static $customProps = null;
		if( !isset( $customProps ) ) {
			$params = array();
			$where = '';

			// Publication
			if( $publication != 0 ) {
				$where .= '`publication` = ? OR `publication` = 0 ';
				$params[] = $publication;
			} else {
				$where .= '`publication` = 0 ';
			}

			// ObjectType
			if( $objectType != '' ) {
				$where .= 'AND `objtype` = ? OR `objtype` = \'\' ';
				$params[] = $objectType;
			} else {
				$where .= 'AND `objtype` = \'\' ';
			}

			// Entity
			$where .= 'AND `entity` = ? ';
			$params[] = 'Object';

			// Since smart_properties table only has unique record of the property names, can safely assume here that
			// the $propertyRows contain only unique property names.
			$rows = self::listRows( self::TABLENAME, 'name', 'type', $where, null, $params );
			if( $rows ) foreach( $rows as $name => $row ) {
				$customProps[$name] = $row['type'];
			}
		}
		return $customProps;
	}

	/**
	 * Method to find out if a property name is a custom property
	 * All custom properties started with c_ (lowercase c)
	 * In v5.0 custom properties can start with C_ (uppercase C) as well.
	 *
	 * @param string $propName Name of the property to check
	 * @return boolean: true if the $propertyname starts with c_ or C_, else false
	 */
	public static function isCustomPropertyName( $propName )
	{
		//at this moment, it is not possible to have c_ case insensitive as server as well as client do not support it. 
		//return (stripos( $propName, 'c_' ) === 0); // performance fix: the line below is 36 times faster
		return isset($propName[1]) && $propName[1] == '_' && ($propName[0] == 'c' || $propName[0] == 'C');
	}

	/**
	 * Retrieves the object property configurations made system wide or brand specific.
	 *
	 * To retrieve system wide (brand-less) configurations only, pass in zero for $pubIds.
	 * For brand specific configurations, pass in list of brand ids or a single brand id.
	 *
	 * @param integer[]|integer $pubIds See function description.
	 * @param string|null $type Object type filter
	 * @return resource DB handle
	 */
	static public function getPropertiesSth( $pubIds, $type = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename("properties");

		$sql = "SELECT * FROM $db pr ";
		$params = array();
		if( is_array( $pubIds ) ) {
			$sql .= "WHERE pr.`publication` IN ( ".implode(',',$pubIds)." ) ";
		} else { // single brand id
			$sql .= "WHERE pr.`publication` = ? ";
			$params[] = intval($pubIds);
		}
		if( $type ) {
			$sql .= "AND (pr.`objtype` = ? OR  pr.`objtype` = ?) ";
			$params[] = strval($type);
			$params[] = '';
		}
    	$sql .= "AND pr.`entity` = ? ";
		$params[] = 'Object';

		// Also exclude PublishForm(Template) specific properties.
		// The "is null" is needed for oracle (empty strings automatically become null values)
		$sql .= "AND (pr.`objtype` <> ? OR pr.`objtype` IS NULL) ";
		$params[] = 'PublishForm';

		$sql .= "AND pr.`adminui` = ? AND pr.`publishsystem` = ? AND pr.`templateid` = ? ";
		$params[] = 'on';
		$params[] = '';
		$params[] = 0;

		// Exclude properties for type File / ArticleComponent, these should never be visible to end users.
		$sql .= self::getWherePartForExcludedPropertyTypes();

		$sql .= "ORDER BY pr.`objtype`, pr.`id`";
		$sth = $dbDriver->query( $sql, $params );
		return $sth;
	}

	/**
	 * Returns a list of property name and its display name.
	 *
	 * @return array
	 */
	public static function listPropertyDisplayNames()
	{
		$where = "`publication` = ? AND `dbupdated` = ? AND `objtype` = ? AND `entity` = ? ";
		$params = array( 0, 1, '', 'Object' );
		$orderBy = array( 'name' => true );
		$rows = self::listRows( self::TABLENAME, 'name', 'dispname', $where, null, $params, $orderBy );
		$props = array();
		if( $rows ) foreach( $rows as $name => $row ) {
			$props[$name] = $row['dispname'];
		}
		return $props;
	}

	/**
	 * Use getPropertyByNameAndFields() instead.
	 * Looks up a property with a given name ($name).
	 * Returns NULL when no property was not found.
	 *
	 * @param string $name
	 * @return PropertyInfo|null
	 * @since v7.5.0 
	 */
	public static function getObjectPropertyByName( $name )
	{
		$dbDriver = DBDriverFactory::gen();
		$tablename = $dbDriver->tablename( self::TABLENAME );
		$dbTermEntities = $dbDriver->tablename( 'termentities' );

		$where = "pr.`name` = ? AND pr.`entity` = ?";
		$params = array( $name, 'Object' );

		$sql =  "SELECT pr.*, te.`name` as `termentityname`, te.`provider` as `autocompleteprovider`, te.`publishsystemid` ".
				"FROM $tablename pr " .
				"LEFT JOIN $dbTermEntities te ON (te.`id` = pr.`termentityid` ) ".
				"WHERE $where ";

		$queryresult = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch( $queryresult );
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * @since v9.0.0
	 * Get properties with customized conditions.
	 *
	 * **$whereConditions is an array of key=>value pairs,
	 * 		where key is the db field and value is the value of the field. e.g array( 'objid' => 44 )
	 *      Since name, entity and publicationId are covered by the function, so key with these three
	 *	    fields (name, entity and publication ) will be skipped if found in the whereConditions.
	 * 
	 * @param string $name The property name
	 * @param string $entity Entity of the property. Default is 'Object'. Set to 'all' for all types of entity.
	 * @param mixed $publ Publication Id. Null for all publications (0 and any other Pub Id)
	 * @param array $whereConditions Extra conditions(Optional).An array of key=>value pairs. Read function header**.
	 * @return array List of PropertyInfo returned by the query requested.
	 */	 
	public static function getPropertyByNameAndFields( $name, $entity='Object', $publ = null, $whereConditions = array() )
	{
		$where = "pr.`name` = ? ";
		$params = array();
		$params[] = $name;

		if( $entity == 'all' ) { // since retrieving all
			$entity = null; // not putting 'entity' as filter criteria.
		}
		if( !is_null( $entity ) ) {
			$where .= "AND pr.`entity` = ? ";
			$params[] = $entity;
		}

		if( !is_null( $publ ) ) {
			$where .= "AND pr.`publication` = ? ";
			$params[] = $publ;
		}

		if( $whereConditions ) {
			foreach( $whereConditions as $field => $value ) {
				if( $field == 'name' || $field == 'entity' || $field == 'publication' ) {
					continue;
				}
				$where .= 'AND pr.`' . $field . '` = ? ';
				$params[] = $value;
			}
		}

		$dbDriver = DBDriverFactory::gen();
		$tablename = $dbDriver->tablename( self::TABLENAME );
		$dbTermEntities = $dbDriver->tablename( 'termentities' );

		$sql = "SELECT pr.*, te.`name` as `termentityname`, te.`provider` as `autocompleteprovider`, te.`publishsystemid` ".
			   "FROM $tablename pr " .
			   "LEFT JOIN $dbTermEntities te ON (te.`id` = pr.`termentityid` ) ".
			   "WHERE $where ";

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth, null, false, $dbDriver );
		$propInfos = array();
		foreach( $rows as $row ) {
			$propInfos[] = self::rowToObj( $row );
		}
		return $propInfos;
	}

	/**
	 * Returns PropertyInfo objects from the smart_properties table that are installed
	 * by a given plugin for a given entity.
	 *
	 * @param string $pluginName Plugin unique name.
	 * @param string $propName The name of the property.
	 * @param string $objectType Object type.
	 * @param string $publishSystem For which Publish System the property is applicable.
	 * @param integer $templateId The unique publishing form template database id.
	 * @param bool $getTermEntityPropOnly When set to true,only Term Entity properties are returned, else all properties are returned.
	 * @return array
	 *
	 * @since v9.0.0
	 */
	public static function getPropertyInfos( $pluginName, $propName, $objectType = null, $publishSystem = null,
	                                         $templateId = null, $getTermEntityPropOnly = false )
	{
		$where = "pr.`entity` = ? ";
		$params = array( 'Object' );
		if( !is_null($pluginName) ) {
			$where .= "AND pr.`serverplugin` = ? ";
			$params[] = $pluginName;
		}
		if( !is_null($propName) ) {
			$where .= "AND pr.`name` = ? ";
			$params[] = $propName;
		}

		// When objType is '', means All object types, therefore no need set the objtype to return all
		if( !empty($objectType) ) {
			$where .= "AND ( pr.`objtype` = ? OR pr.`objtype` = '') ";
			$params[] = $objectType;
		}

		if( !empty($publishSystem) ) {
			$where .= "AND ( pr.`publishsystem` = ? OR pr.`publishsystem` = '') ";
			$params[] = $publishSystem;
		}

		if( !empty($templateId) ) {
			$where .= "AND ( pr.`templateid` = ? OR pr.`templateid` = 0) ";
			$params[] = $templateId;
		}

		if( $getTermEntityPropOnly ) {
			$where .= "AND pr.`termentityid` > 0 ";
		}

		return self::getPropertyInfoObjects( $where, $params );
	}

	public static function getPropertyInfosByBrand( $pluginName, $propName, $brandId )
	{
		$where = "pr.`entity` = ? ";
		$params = array( 'Object' );
		if( !is_null($pluginName) ) {
			$where .= "AND pr.`serverplugin` = ? ";
			$params[] = $pluginName;
		}
		if( !is_null($propName) ) {
			$where .= "AND pr.`name` = ? ";
			$params[] = $propName;
		}
		if( !empty( $brandId ) ) {
			$where .= "AND ( pr.`publication` = ? OR pr.`publication` = ? ) ";
			$params[] = $brandId;
			$params[] = 0;
		}

		return self::getPropertyInfoObjects( $where, $params );
	}

	private static function getPropertyInfoObjects( $where, $params )
	{
		$dbDriver = DBDriverFactory::gen();
		$tablename = $dbDriver->tablename( self::TABLENAME );
		$dbTermEntities = $dbDriver->tablename( 'termentities' );

		$sql =  "SELECT pr.*, te.`name` as `termentityname`, te.`provider` as `autocompleteprovider`, te.`publishsystemid` ".
			"FROM $tablename pr " .
			"LEFT JOIN $dbTermEntities te ON (te.`id` = pr.`termentityid` ) ".
			"WHERE $where ";

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth, null, false, $dbDriver );
		$objs = array();
		foreach( $rows as $row ) {
			$objs[] = self::rowToObj( $row );
		}

		return $objs;
	}
	
	/**
	 * Insert PropertyInfo object's properties into smart_properties table.
	 *
	 * @since v9.0.0
	 * @param PropertyInfo $propInfo Custom property definition to be added to the DB model.
	 * @return integer|boolean New inserted propertyRow DB Id when record is successfully inserted; False otherwise.
	 */
	public static function insertPropertyInfo( PropertyInfo $propInfo )
	{
		$blobValues = null;
		$row = self::objToRow( $propInfo, $blobValues );
		return self::insertRow( self::TABLENAME, $row, true, $blobValues );
	}
	
	/**
	 * Update Property Info in smart_properties table.
	 * The Property to be updated can be updated with conditions in the $whereConditions.
	 * Otherwise, the property gets updated with the given prop name.
	 *
	 * @since v9.0.0
	 * @param string $propName The custom property name to be updated.
	 * @param PropertyInfo $propInfo The property Info of the property to be updated in the DB. 
	 * @param array $whereConditions Extra conditions(Optional).An array of key=>value pairs, where key is the db field and value is the value of the field. e.g array( 'objid' => 44 )
	 * @return Boolean True if the updated succeeded, false if an error occured.
	 */
	public static function updatePropertyInfo( $propName, PropertyInfo $propInfo, $whereConditions=array() )
	{
		$params = array();
		$where = ' `name` = ? ';
		$params[] = $propName;

		if( $whereConditions ) {
			foreach( $whereConditions as $field => $value ) {
				$where .= 'AND `' . $field . '` = ? ';
				$params[] = $value;
			}
		}

		$blobValues = null;
		$row = self::objToRow( $propInfo, $blobValues );
		return self::updateRow( self::TABLENAME, $row, $where, $params, $blobValues );
	}

	/**
	 * Deletes a ProperyInfo definition from DB.
	 *
	 * @param string $pubId Brand ID
	 * @param string $objType Object type
	 * @param string $propName Property name
	 * @return bool Whether the operation was succesful or not.
	 */
	public static function deletePropertyInfo( $pubId, $objType, $propName )
	{
		// TODO: Shouldn't we pass an PropertyInfo as parameter in here as well? as the WHERE can be built using that.
		// TODO: Used properties are not located correctly, looking at the use in the multichannelpublishing some of these need to be moved to BizAdmProperty.class.php

		$where = '`publication` = ? AND `objtype` = ? AND `name` = ?';
		$params = array( $pubId, $objType, $propName );
		return (bool)self::deleteRows( self::TABLENAME, $where, $params );
	}
	
	/**
	 * Converts a DB property record (array) into a property info data object.
	 * 
	 * @param array $row DB channel record
	 * @return PropertyInfo 
	 * @since v7.5.0 
	 */
	private static function rowToObj( $row )
	{
		$property = new PropertyInfo();
		$property->Name         = $row['name'];
		$property->DisplayName  = $row['dispname'];
		$property->Category     = trim($row['category']) ? $row['category'] : '';
		$property->Type         = $row['type'];
		$property->DefaultValue = trim($row['defaultvalue']) ? $row['defaultvalue'] : '';
		if( $property->Type == 'multilist' || $property->Type == 'list' ) {
			$property->ValueList    = explode( ',', $row['valuelist'] );
		} else { // Type other than 'multilist' and 'list' should not have valuelist.
			$property->ValueList    = null;
		}
		$property->MaxValue     = trim($row['maxvalue']) ? $row['maxvalue'] : '';
		$property->MinValue     = trim($row['minvalue']) ? $row['minvalue'] : '';
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		if ( BizProperty::isCustomPropertyName( $row['name'] ) ) {
			$property->MaxLength = ( $row['maxlen'] > 0 ) ? $row['maxlen'] : null;
		} else {
			$property->MaxLength = BizProperty::getStandardPropertyMaxLength( $row['name'] );
		}
		$property->AdminUI      = ( $row['adminui'] == 'on' ? true : false );
		$property->DBUpdated    = ( $row['dbupdated'] == 0 ) ? false : true;
		$obj = unserialize($row['propertyvalues']);
		$property->PropertyValues = ($obj) ? $obj : array();
		$property->MinResolution = $row['minresolution'];
		$property->MaxResolution = $row['maxresolution'];
		$property->PublishSystem = $row['publishsystem'];
		$property->TemplateId = $row['templateid'];
		$property->TermEntity = $row['termentityname'];
		$property->SuggestionEntity = $row['suggestionentity'];
		$property->AutocompleteProvider = $row['autocompleteprovider'];
		$property->PublishSystemId = $row['publishsystemid'];

		return $property;
	}

	/**
	 * Converts a property info data object into a DB property record (array).
	 *
	 * @since v9.0.0
	 * @param PropertyInfo $obj
	 * @param null|array $blobValues
	 * @return array
	 */
	private static function objToRow( $obj, &$blobValues )
	{
		$row = array();
		if( isset( $obj->PublicationId ) ) {
			$row['publication']  	= intval( $obj->PublicationId );
		}
		if( isset( $obj->ObjectType ) ) {
			$row['objtype'] 	 	= $obj->ObjectType;
		}
		if(!is_null( $obj->Name ) ) {
			$row['name'] 	 	 	= $obj->Name;
		}
		if(!is_null( $obj->DisplayName ) ) {
			$row['dispname']     	= $obj->DisplayName;
		}
		if(!is_null( $obj->Category ) ) {
			$row['category'] 	 	= $obj->Category;
		}
		if(!is_null( $obj->Type ) ) {
			$row['type'] 	     	= $obj->Type;
		}
		if(!is_null( $obj->DefaultValue ) ) {
			$row['defaultvalue'] 	= $obj->DefaultValue;
		}
		if( !is_null( $obj->ValueList ) ) {
			if ( !is_array($blobValues) ) {
				$blobValues = array();
			}
			$row['valuelist'] 		= '#BLOB#';
			$blobValues[]			= implode( ',', $obj->ValueList );
		}
		if( !is_null( $obj->PropertyValues ) ) {
			if ( !is_array($blobValues) ) {
				$blobValues = array();
			}
			$row['propertyvalues'] 	= '#BLOB#';
			$blobValues[]			= serialize($obj->PropertyValues);
		}
		if( !is_null( $obj->MinValue ) ) {
			$row['minvalue'] 	 	= $obj->MinValue;
		}
		if( !is_null( $obj->MaxValue) ) {
			$row['maxvalue'] 	 	= $obj->MaxValue;
		}
		if (!is_null( $obj->MaxLength ) ) {
			$row['maxlen'] 	 	 	= intval( $obj->MaxLength );
		}
		if( isset( $obj->DBUpdated )){
			$row['dbupdated']	= ( $obj->DBUpdated == false ? 0 : 1 );
		}
		if( isset( $obj->Entity ) ) {
			$row['entity'] 	 	 	= $obj->Entity;
		}
		if( isset( $obj->PlugIn ) ) {
			$row['serverplugin'] 	= $obj->PlugIn;
		}
		if( isset( $obj->AdminUI )) {
			$row['adminui']         = ( $obj->AdminUI == true ? 'on' : '' );
		}
		if( !is_null( $obj->MinResolution ) ) {
			$row['minresolution'] 	 	= $obj->MinResolution;
		}
		if( !is_null( $obj->MaxResolution) ) {
			$row['maxresolution'] 	 	= $obj->MaxResolution;
		}
		if( isset( $obj->PublishSystem )) {
			$row['publishsystem']       = $obj->PublishSystem;
		}
		if( isset( $obj->TemplateId )) {
			$row['templateid']          = $obj->TemplateId;
		}
		if( !is_null( $obj->SuggestionEntity )) {
			$row['suggestionentity']          = $obj->SuggestionEntity;
		}
		if( !is_null( $obj->TermEntity ) && !is_null( $obj->AutocompleteProvider ) && !is_null( $obj->PublishSystemId )) {
			require_once BASEDIR .'/server/dbclasses/DBAdmAutocompleteTermEntity.class.php';
			$entity = new AdmTermEntity();
			$entity->Name = $obj->TermEntity;
			$entity->AutocompleteProvider = $obj->AutocompleteProvider;
			$entity->PublishSystemId = $obj->PublishSystemId;
			$entity = DBAdmAutocompleteTermEntity::getTermEntity( $entity );
			if( $entity ) {
				$row['termentityid'] = $entity->Id;
			}
		}

		// id is not used here
		return $row;
	}
	
	public function updateProperty( $values, $where )
	{
		$result = self::updateRow(self::TABLENAME, $values, $where);
		return $result;
	}	
	
	/**
	 * Compiles a part of the WHERE statement to exclude certain Property types.
	 *
	 * Certain type of properties should not be retrieved from the database
	 * when gathering Object properties, these properties do not have a column
	 * in the respective objects / deletedobjects table.
	 * @since v9.0.0
	 * @return string A Where part for querying.
	 */
	private static function getWherePartForExcludedPropertyTypes()
	{
		$where = '';
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		$excludedPropTypes = BizCustomField::getExcludedObjectFields();
		$excludedPropArray = array();
		if( !empty($excludedPropTypes) ) {
			$dbDriver = DBDriverFactory::gen();
			foreach( $excludedPropTypes as $val ) {
				$excludedPropArray[] = "'".$dbDriver->toDBString($val)."'";
			}
			$excludeProps = implode(',', $excludedPropArray);
			$where .= "AND pr.`type` NOT IN (". $excludeProps .") ";
		}
		return $where;
	}

	/**
	 * Retrieve unused publish form properties by publish system
	 * @param string $publishSystem Publish system name, default is null
	 * @return array $propertyRows Array of unused publish form properties
	 * @throws BizException
	 */
	public static function getUnusedPublishFormProperties( $publishSystem = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbp = $dbDriver->tablename( self::TABLENAME );
		$dbo = $dbDriver->tablename( 'objects' );
		$dbdo= $dbDriver->tablename( 'deletedobjects' );
		$dbor = $dbDriver->tablename( 'objectrelations' );

		$sql  = 'SELECT `id`, `name`, `publication`, `type` '.
				'FROM '.$dbp.' '.
				'WHERE `entity` = ? AND `templateid` <> ? ';
		$params = array( 'Object', 0 );
		if( !empty( $publishSystem )) {
			$sql .= 'AND `publishsystem` = ? ';
			$params[] = $publishSystem;
		}
		$sql .= 'AND `templateid` NOT IN ( ';
		$sql .= 'SELECT `id` FROM '.$dbo.' WHERE `type` = ? ';  // Ensure template object not in smart_objects table
		$params[] = 'PublishFormTemplate';
		$sql .= 'UNION ';
		$sql .= 'SELECT `id` FROM '.$dbdo.' WHERE `type` = ? '; // Ensure template object not in trash can
		$params[] = 'PublishFormTemplate';
		$sql .= 'UNION ';
		$sql .= 'SELECT `parent` FROM '.$dbor.' WHERE `parenttype` = ? ) '; // Ensure template object didn't have child relations
		$params[] = 'PublishFormTemplate';

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth, 'id');
		return $rows;
	}

	/**
	 * Delete property by property Ids
	 * @param array $ids Array of property Ids
	 * @return bool True | False
	 */
	public static function deleteProperties( $ids )
	{
		$ids = implode( ',', $ids );
		$where = "`id` IN ( $ids )";
		return self::deleteRows( self::TABLENAME, $where );
	}

	/**
	 * Returns the property propertyRows for object entities. Only those are returned with the AdminUI flag ('adminui') set.
	 * So not the properties created for publish forms or for entities like issue or publication. The returned properties
	 * ar typically the ones that used in the Metadata set up.
	 *
	 * @return array The property propertyRows.
	 * @throws BizException
	 */
	static public function getAdminUIPropertiesOfObjects()
	{
		$dbDriver = DBDriverFactory::gen();
		$properties = $dbDriver->tablename( self::TABLENAME );

		$sql =   'SELECT DISTINCT props.`publication` as `pubid`, pubs.`publication`, props.`objtype` '.
					'FROM '.$properties.' props '.
					'LEFT JOIN `smart_publications` pubs on (props.`publication` = pubs.`id`) '.
					'WHERE props.`entity` = ? '.
					'AND props.`adminui` = ? '.
					'GROUP BY  props.`publication`,  props.`objtype` , pubs.`publication` '.
					'ORDER BY  props.`publication`,  props.`objtype` ';
		$params = array( 'Object', 'on');

		$sth = $dbDriver->query($sql, $params);
		$rows = self::fetchResults( $sth );

		return $rows;
	}

	/**
	 *
	 * @param integer $publicationId
	 * @param string $objectType
	 * @return array The property propertyRows.
	 * @throws BizException
	 */
	static public function getObjectPropertiesByObjectType( $publicationId, $objectType )
	{
		$dbDriver = DBDriverFactory::gen();
		$properties = $dbDriver->tablename( self::TABLENAME );

		$sql =   'SELECT * '.
					'FROM '.$properties.' '.
					'WHERE `publication` = ? AND `objtype` = ? AND `entity` = ? '.
					'ORDER BY `category`, `name` ';
		$params = array( $publicationId, $objectType, 'Object' );

		$sth = $dbDriver->query($sql, $params);
		$rows = self::fetchResults( $sth );

		return $rows;
	}
}
