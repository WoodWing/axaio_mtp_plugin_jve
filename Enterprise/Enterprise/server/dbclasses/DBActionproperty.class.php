<?php
/**
 * Implements DB querying of actionproperties.
 * 
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v6.5
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBActionproperty extends DBBase
{
	const TABLENAME = 'actionproperties';
	
	/**
	 * Updates an action property record, specified by its id, with the passed
	 * values.
	 * 
	 * @param integer $id id of the record to be updated.
	 * @param array $values array of fields and their values
	 * @return boolean (true on success else false)
	 */
	static public function updateActionproperty($id, $values)
	{
		$id = intval($id); //Convert to integer
		$where = '`id` = ?';
		$params = array($id);
			
		return self::updateRow(self::TABLENAME, $values, $where, $params);
		
	}
	
	/**
	 * Adds a new action property record.
	 *
	 * @param array $values array of fields and their values
	 * @return boolean (true on success else false)
	 */
	static public function insertActionproperty($values)
	{
		return self::insertRow(self::TABLENAME, $values);
		
	}

	/**
	 * Deletes an action property record, specified by ids id.
	 *
	 * @param integer $id, id of record to be deleted
	 * @return true on success else null
	 */
	static public function deleteActionproperty($id)
	{
		$id = intval($id); //Convert to integer
		$where = '`id` = ?';
		$params = array($id);
		
		return self::deleteRows(self::TABLENAME, $where, $params);
		
	}

	/**
	 * Returns actions by publication and object type.
	 *
	 * @return array with results.
	 */
	static public function listActionpropertyGroups()
	{
		$dbDriver = DBDriverFactory::gen();
		$dbap = $dbDriver->tablename(self::TABLENAME);
				
		$sql = "SELECT DISTINCT actprops.`publication` AS `pubid`, pubs.`code`, pubs.`publication`, actprops.`type`, actprops.`action` ";
		$sql .= "FROM $dbap actprops ";
        $sql .= "LEFT JOIN `smart_publications` pubs ON ( actprops.`publication`  = pubs.`id` ) ";
        $sql .= "GROUP BY actprops.`publication`, actprops.`type`, pubs.`code`, pubs.`publication`, actprops.`action` ";
        $sql .= "ORDER BY pubs.`code` ASC, actprops.`type` ASC, actprops.`action` ASC";
        
        $sth = $dbDriver->query($sql);
        
        return self::fetchResults($sth);		
	}
	
	/**
	 * Returns the action properties with the display name/category defined either
	 * on object type level or generic level.
	 *
	 * In the query, the first left join will search for exact brand-object type level defined properties
	 * or "ALL" object type for custom property.
	 * The second left join, will search for "ALL" brand with exact object type level defined properties
	 * OR "ALL" object type for custom property.
	 *
	 * When the first join, exact brand-object type level defined properties is found the second join values will be ignored.
	 *
	 * @param integer $publ Publication on with action property is defined
	 * @param string $objType Object type on which action property is defined
	 * @param string $action Action on which action property is defined
	 * @return array with results
	 */
	static public function listActionPropertyWithNames( $publ, $objType, $action )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbap = $dbDriver->tablename(self::TABLENAME);
		$dbpr = $dbDriver->tablename('properties');
		$params = array();

		$nullObjType1 = '';
		$nullObjType2 = '';
		// For oracle, the comparison between null values of 2 tables fields returns zero row,
		// therefore we need to check if a particular field is a null value or not.
		if( $objType == '' ) {
			$nullObjType1 = 'OR pr.`objtype` is null ';
			$nullObjType2 = 'OR pr2.`objtype` is null ';
		}

		$sql =  "SELECT ap.`id`, ap.`orderid`, ap.`property`, ap.`edit`,".
				"ap.`mandatory`, ap.`restricted`, ap.`refreshonchange`, ap.`multipleobjects`, pr.`publication`, " .
				"pr.`category`, pr.`dispname`, pr2.`category` as `category2`, pr2.`dispname` as `dispname2` ".
				"FROM $dbap ap ".
				"LEFT JOIN $dbpr pr ON ( pr.`publication`= ap.`publication` AND pr.`name` = ap.`property` ".
				"AND ( ap.`type` = pr.`objtype` " . $nullObjType1 . "OR pr.`name` LIKE 'C_%' ) ) ".
				"LEFT JOIN $dbpr pr2 ON ( pr2.`publication` = 0 AND pr2.`name` = ap.`property` ".
				"AND ( ap.`type` = pr2.`objtype` ".$nullObjType2 . "OR pr2.`name` LIKE 'C_%' ) ) ".
				"WHERE ap.`publication` = ? AND ap.`type` = ? AND ap.`action` = ? ".
				"ORDER BY ap.`orderid`, ap.`id`";

		$params[] = $publ;
		$params[] = $objType;
		$params[] = $action;

		$sth = $dbDriver->query($sql, $params);
        
        return self::fetchResults($sth);	
	}
	
	/**
	 * List of property usages for a specific level of customization.
	 * When no customizations are found at that level, it returns an empty collection.
	 *
	 * $wiwiwUsages: This is only used when in the context of dealing with PublishFormTemplates and PublishForms.
	 * Send in an empty array when caller is dealing with PublishFormTemplates and PublishForms; Null otherwise.
	 * When empty array is sent in, a three dimensional list of PropertyUsages that belong to placed objects on the form
	 * will be returned. Keys are used as follows: $wiwiwUsages[mainProp][wiwProp][wiwiwProp]

	 *
	 * @param string $publ    Publication ID.
	 * @param string $objType Object type.
	 * @param string $action  Action type.
	 * @param boolean $explicit  Request NOT to fall back at global definition levels. Specified level only. (BZ#14553)
	 * @param string $documentId Optional DocumentId to be used when querying the database.
	 * @param null|array $wiwiwUsages [writable] See header above.
	 * @return PropertyUsage[]  PropertyUsage definitions as used in workflow WSDL.
	 */
	public static function getPropertyUsages( $publ, $objType, $action, $explicit=false, $documentId=null, &$wiwiwUsages )
	{
		if (!$publ) $publ = 0;
		if (!$objType) $objType = '';
		if (!$action) $action = '';

		$params = array( $publ, $objType, $action );
		if( $explicit ) {
			$where = 
				"(`publication` = ?) and ".
				"(`type` = ?) and ".
				"(`action` = ?) ";
		} else {
			$where = 
				"(`publication` = ? or `publication` = 0) and ".
				"(`type` = ? or `type` = '') and ".
				"(`action` = ? or `action` = '') ";
		}

		if (!is_null($documentId)) {
			$where .= "AND `documentid` = ? ";
			$params[] = $documentId;
		}

    	$where .= ' ORDER BY `publication` DESC, `type` DESC, `action` DESC, `orderid` DESC ';
		// query db
		$rows = self::listRows(self::TABLENAME, 'id', 'property', $where, true, $params);

		// fetch into array
		$ret = array();
		$first = true;

		// Initialize variables.
		$firstPub = null;
		$firstTyp = null;
		$firstAct = null;

        foreach( $rows as $row ) {
        	if( $first === true ) {
        		$first = false;
        		$firstPub = $row['publication'];
        		$firstTyp = $row['type'];
        		$firstAct = $row['action'];
        	} else {
        		if( $firstPub != $row['publication'] || $firstTyp != $row['type'] || $firstAct != $row['action'] ) {
        			break; // quit on any change; no inheritance!
        		}
        	}
	        $addPropIntoUsages = true;
	        $propUsage = new PropertyUsage();
	        $propUsage->Name      = $row['property'];
	        $propUsage->Editable  = $row['edit']=='on';
	        $propUsage->Mandatory = $row['mandatory']=='on';
	        $propUsage->Restricted = $row['restricted']=='on';
	        $propUsage->RefreshOnChange = $row['refreshonchange']=='on';
	        $propUsage->InitialHeight = $row['initialheight'];
	        $propUsage->MultipleObjects = $row['multipleobjects']=='on';

	        if (!is_null($documentId)) {
		        $wiwiwPropParentId = $row['parentfieldid'];
		        if( $wiwiwPropParentId && !is_null( $wiwiwUsages ) ) {
			        $wiwPropParentId = self::getParentPropId( $wiwiwPropParentId );
			        if( !is_null( $wiwPropParentId) ) {
				        // $wiwiwUsages[mainProp][wiwProp][wiwiwProp]
				        $mainProp = $rows[$wiwPropParentId];
				        $wiwProp = $rows[$wiwiwPropParentId];
				        $wiwiwUsages[$mainProp['property']][$wiwProp['property']][$row['property']] = $propUsage;
				        $addPropIntoUsages = false; // It is a field in wiwiw, already added in $wiwiwUsages, so don't add it in $ret(which is mainWidgets and wiw).
			        }
		        }

		        $propUsage->ParentFieldId = $wiwiwPropParentId;
		        $propUsage->Id = $row['id'];
	        }
	        if( $addPropIntoUsages ) {
	            $ret[$row['property']] = $propUsage;
	        }
		}
		$ret = array_reverse( $ret, true ); // respect order; undo `orderid` DESC
		return $ret;
	}

	/**
	 * Retrieves the dialog setup configurations made system wide or brand specific.
	 *
	 * To retrieve system wide (brand-less) configurations only, pass in zero for $pubIds.
	 * For brand specific configurations, pass in list of brand ids or a single brand id.
	 *
	 * @param integer[]|integer $pubIds Brand id(s). Since 9.7 an array is allowed to retrieve for many brands at once.
	 * @param string|null $objType Filter on object type.
	 * @param string|null $action Filter on dialog type (shown for user action).
	 * @return resource DB handle
	 */
    static public function getPropertyUsagesSth( $pubIds, $objType = null, $action = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);
		$params = array();
		
		$sql = "SELECT * FROM $db ";
		if( is_array( $pubIds ) ) {
			$sql .= "WHERE `publication` IN ( ".implode(',',$pubIds)." ) ";
		} else { // single brand id
			$sql .= "WHERE `publication` = ? ";
			$params[] = intval($pubIds); 
		}
		
		if ($action) {
			$sql .= " AND `action` = ?";
			$params[] = $action; 
		} else {
			// Exclude all the SetPublishProperties dialogs
			// The "is null" is needed for oracle (empty strings automatically become null values)
			$sql .= " and (`action` <> ? or `action` is null)";
			$params[] = 'SetPublishProperties';
		}
		if ($objType) {
			$sql .= " AND `type` = ?";
			$params[] = $objType;
		}
		$sql .= " ORDER BY `action`, `type`, `orderid`, `id`";
		$sth = $dbDriver->query($sql, $params);
		
		return $sth;
	}

	/**
	 * Deletes all action properties with name = $propname from the actionproperties table
	 *
	 * @param string $propname name of the prop to delete, in case of custom props: starting with C_ (!)
	 * @param integer $pubId Brand/Publication Id
	 * @return void
	 */
	
	static public function deletePropFromActionProperties( $propname, $pubId )
	{
		$params = array();
		
		$dbDriver = DBDriverFactory::gen();
		$actionpropstable = $dbDriver->tablename(self::TABLENAME);
	
		$sql  = "DELETE FROM $actionpropstable ";
		$sql .= "WHERE `property` = ? AND `publication` = ?";
		$params[] = $propname;
		$params[] = $pubId;
		 
		$dbDriver->query($sql, $params);
	}

	/**
	 * Lists Action Properties based on the input parameters.
	 *
	 * @static
	 * @param int $publid Publication ID.
	 * @param string|null $objType Object type.
	 * @param string|null $action Action type.
	 * @param string|null $documentId Id retrieved from the external integration (like Drupal).
	 *                    Concatenation of siteId and the content TypeId.
	 * @return array
	 */
	public static function listActionProperties($publid, $objType = null, $action = null, $documentId = null)
	{
		$publid = intval($publid); //Convert to integer	
		$dbdriver = DBDriverFactory::gen();
		$table = $dbdriver->tablename(self::TABLENAME);
		$params = array();
		
		$sql = "SELECT * from $table where `publication` = ?";
		$params[] = $publid; 
		if ($action) {
			$sql .= " and `action` = ?";
			$params[] = $action;
		}
		if ($objType) {
			$sql .= " and `type` = ?";
			$params[] = $objType;
		}

		if ($documentId) {
			$sql .= " and `documentid` = ?";
			$params[] = $documentId;
		}

		$sql .= " order by `action`, `type`, `orderid`, `id`";
		$sth = $dbdriver->query($sql, $params);

		$rows = array();
		
		for (;;) {
			$row = $dbdriver->fetch($sth);
			if (!$row) {break;}
			$rows[$row['id']] = $row;
		}
		return $rows;
	}

	/**
	 * To retrieve the parent property id of the passed in custom property $custPropId.
	 *
	 * @param int $custPropId
	 * @return int|null Null when the custom property $custPropId has no parent property; Prop Id of the parent is returned..
	 */
	private static function getParentPropId( $custPropId )
	{
		$where = '`id` = ? ';
		$params = array( $custPropId );
		$rows = self::listRows( self::TABLENAME, 'id', 'parentfieldid', $where, null, $params);

		return $rows[$custPropId]['parentfieldid'] ? $rows[$custPropId]['parentfieldid'] : null;
	}

	/**
	 * Retrieve unused action properties by docuumentid prefix.
	 *
	 * @param string $documentIdPrefix DocumentId prefix, usually it is the server plugin name.
	 * @return array Array of unused action properties
	 * @throws BizException
	 */
	public static function getUnusedActionProperties( $documentIdPrefix )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbap = $dbDriver->tablename(self::TABLENAME);
		$dbo  = $dbDriver->tablename('objects');
		$dbdo = $dbDriver->tablename('deletedobjects');
		$sql =  'SELECT * '.
				'FROM '.$dbap.' '.
				'WHERE `type` = ? '.
				'AND (`documentid` LIKE ? OR `documentid` LIKE ? ) '.
				'AND `documentid` NOT IN ( '.
									'SELECT `documentid` FROM '.$dbo.' WHERE `documentid` LIKE ? OR `documentid` LIKE ? '.
									'UNION '.
									'SELECT `documentid` FROM '.$dbdo.' WHERE  `documentid` LIKE ? OR `documentid` LIKE ? ) ';

		$params = array(
					'PublishFormTemplate',
					$documentIdPrefix.'%',
					strtolower($documentIdPrefix).'%', // Put prefix to lowercase for Oracle case sensitive query
					$documentIdPrefix.'%',
					strtolower($documentIdPrefix).'%',
					$documentIdPrefix.'%',
					strtolower($documentIdPrefix).'%' );

		$sth = $dbDriver->query( $sql, $params );
		return self::fetchResults( $sth );
	}

	/**
	 * Delete action property by ids
	 *
	 * @param array $ids Array of action property Ids
	 * @return bool True when execution fine | False when error occur
	 */
	public static function deleteActionProperties( $ids )
	{
		$ids = implode( ',', $ids );
		$where = "`id` IN ( $ids )";

		return self::deleteRows(self::TABLENAME, $where);
	}
}