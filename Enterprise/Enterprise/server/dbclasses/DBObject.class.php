<?php

define('AUTONAMING_NUMDIGITS',4);
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBObject extends DBBase
{
	const TABLENAME = 'objects';
	
	static public function updateObjectDeadlines($issueid, $sectiondefid, $statedefid, $deadlinehard, $deadlinesoft)
	{
		$dbdriver = DBDriverFactory::gen();
		$objectstable = $dbdriver->tablename(self::TABLENAME);
		$targetstable = $dbdriver->tablename('targets');

		$sql  = " UPDATE $objectstable o ";
		$sql .= " INNER JOIN $targetstable tar ON (tar.`objectid` = o.`id`) ";
		$sql .= " SET `deadline` = '$deadlinehard' , `deadlinesoft` = '$deadlinesoft' , `deadlinechanged` = 'Y' ";
		$sql .= " WHERE tar.`issueid` = $issueid AND `section` = $sectiondefid AND `state` = $statedefid AND (`deadline` <> '$deadlinehard' OR `deadline` IS NULL )";

		$dbdriver->query($sql);

		return 1;
	}

	/**
	 * Generates an unique objectname in all issueids the object is targeted for (rework for v6.1) <br/>
	 * Maximum length of an objectname is 63 characters. These characters may be multibyte though.
	 * Break of at the (63-6)st character. This leaves 1 for _ and 5 characters for an unique number =>
	 * So it is assumed that there will not be more than 99999 objects starting with the same proposed name in any issue.
	 *
	 * @param array of int $issueidsarray: id's of the issues the object is targeted for.
	 * @param string $objtype The type of the object.
	 * @param string $proposedobjectname: proposed name of the new object.
	 * @return string Either new objectname or null if no name found.
	 * @deprecated since 10.0.4 Please use BizObject::getUniqueObjectName() instead.
	 */
	static public function getUniqueObjectName($issueidsarray, $objtype, $proposedobjectname)
	{
		if (empty($issueidsarray)) {
			return $proposedobjectname;
		}
		
		$dbdriver = DBDriverFactory::gen();
		$startofname = mb_substr($proposedobjectname, 0, (63-6), 'UTF8');
		$issueids = implode(',', $issueidsarray); 

		//Use statics to increase performance if the startofname has not been modified since previous call(s)
		static $prevstartofname;
		static $existingnames;
		static $previssueids;
		static $prevobjtype;

		//Only requery if $startofname has changed.
		//Changed to strcasecmp as an extra param was expected
		if (!isset($existingnames) || (strcasecmp($prevstartofname, $startofname) != 0) || ($previssueids != $issueids) || ($prevobjtype != $objtype))
		{
			$prevstartofname = $startofname;
			$previssueids = $issueids;
			$prevobjtype = $objtype;
			$existingnames = array();
			$objectstable = $dbdriver->tablename("objects");
			$targetstable = $dbdriver->tablename("targets");
			$sql  = "SELECT `name` FROM $objectstable o ";
			$sql .= "INNER JOIN $targetstable tar ON (o.`id` = tar.`objectid`) ";
			$sql .= "WHERE o.`name` LIKE '$startofname%' AND tar.`issueid` IN ($issueids) AND o.`type` = '$objtype'";
			// escape4like on $startofname is not needed. $startofname is already validated 
			$sth = $dbdriver->query($sql);
			while( ( $row = $dbdriver->fetch($sth) ) ) {
				$existingnames[$row['name']] = true;
			}
		}

		$result = self::makeNameUnique( $existingnames, $startofname ); 

		//Add $result to existingnames
		if (!empty($result)) {
			$existingnames[$result] = true;
		}

		return $result;
	}

	/**
	 * Returns a unique name for an object in a dossier/task.
	 * Based on the type of the object all names of comparable objects within a dossier/task are checked.
	 * If the name already exists a new name is returned in de format <name>_<suffix>. The suffix is a number in the
	 * range 0-9999 with leading zeros.
	 *  
	 * @param integer $parent Id of the container (dossier/task)
	 * @param string $proposedName Name to be checked
	 * @param string $childType Object type for which the name must be unique
	 * @param integer $id Id of the object, null in case an object is created.
	 * @return string unique name
	 */
	static public function getUniqueNameForChildren( $parent, $proposedName, $childType, $id )
	{
		$dbDriver = DBDriverFactory::gen();
		$objectstable = $dbDriver->tablename(self::TABLENAME);
		$relationtable = $dbDriver->tablename('objectrelations');
		$sql = 	'SELECT o.`name` '.
				'FROM '.$objectstable.' o '.
				'INNER JOIN '.$relationtable.' rel ON ( rel.`child` = o.`id` ) '.
				'WHERE rel.`parent` = ? '.
				'AND o.`type` = ? ';
		$params = array( $parent, $childType );
		if ( $id ) { // Exclude the name of the object itself otherwise own name is seen as duplicate. 
			$sql .= 'AND rel.`child` != ? ';
			$params[] = $id;
		}
		$sth = $dbDriver->query( $sql, $params );
		$existingChildNames = array();
		while( ( $row = $dbDriver->fetch($sth) ) ) {
				$existingChildNames[$row['name']] = true;
		}

		$result = self::makeNameUnique( $existingChildNames, $proposedName ); 
		
		return $result;	
	}

	/**
	 * Returns a new name when the name already exists.
	 *
	 * A proposed name is checked against an list of existing names. If it is in the list a new is generated. This is
	 * done by adding a suffix in the format _0000.
	 * In case long names are used (around the maximum length) the new name cannot be just the old name plus a suffix.
	 * E.g. if the old name is 60 characters long the new name would become 65 characters long.
	 * In such cases the proposed name is shorted to the maximum length of Name property minus the 5 characters
	 * for the suffix (AUTONAMING_NUMDIGITS + underscore).
	 *
	 * @param array $existingNames List against which the proposal is checked.
	 * @param string $proposedName Proposed name.
	 * @return string Name, either the proposed one if it is unique or a new one.
	 */
	static public function makeNameUnique( $existingNames, $proposedName )
	{
		if( !array_key_exists( $proposedName, $existingNames ) ) {
			$result = $proposedName;
		} else {
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$maxNameLength = BizProperty::getStandardPropertyMaxLength( 'Name' ) - ( AUTONAMING_NUMDIGITS + 1 );
			if ( mb_strlen( $proposedName, 'UTF8' ) >  $maxNameLength ) {
				$proposedName = mb_substr( $proposedName, 0, $maxNameLength, 'UTF8' );
			}
			$result = '';
			$maxSuffix = intval( str_repeat( '9', AUTONAMING_NUMDIGITS) );
			for( $i = 1; $i <= $maxSuffix; $i++ ) {
				$newName = $proposedName.'_'.str_pad( $i, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
				if( !array_key_exists( $newName, $existingNames ) ) {
					$result = $newName;
					break;
				}
			}
		}

		return $result;
	}

	static public function createObject( $storename, $id, $user, $created, $arr, $modified )
	{
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';

		$dbDriver = DBDriverFactory::gen();
		//BZ#7258 issue is not set here anymore
		unset($arr['issue']);

		if (!array_key_exists('majorversion', $arr)) {
			$arr['majorversion'] = 0;
		}
		if (!array_key_exists('minorversion', $arr)) {
			$arr['minorversion'] = 1;
		}

		$dbstorename = $dbDriver->toDBString($storename);
		$db = $dbDriver->tablename("objects");
		$user = $dbDriver->toDBString($user);

		$arr['storename'] = $dbstorename;
		$arr['creator']   = $user;
		$arr['created']   = $created;
		$arr['modifier']  = $user;
		$arr['modified']  = $modified;

		$sql = "INSERT INTO $db ( ";
		$komma = '';
		if ($id) {
			$sql .= "`id` ";
			$komma = ',';
		}

		// std fields
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$fields = BizProperty::getMetaDataObjFields();
		$fields = array_diff( $fields, array(null) ); // remove non-db props
		$fields = array_unique($fields); // remove duplicates, like Section+Category props are both 'section' in DB world
		foreach( $fields as $key ) {
			if (array_key_exists($key, $arr)) {
				$sql .= "$komma ".$dbDriver->quoteIdentifier($key);
				$komma = ',';
			}
		}
		// custom fields
		foreach (array_keys($arr) as $key) {
			if( DBProperty::isCustomPropertyName($key) ) {
				$sql .= "$komma ".$dbDriver->quoteIdentifier($key);
				$komma = ',';
			}
		}

		$sql .= ") VALUES ( ";
		$komma = '';
		if ($id) {
			$sql .= "$id ";
			$komma = ',';
		}
		
		$blob = null;
		// std fields
		foreach( $fields as $propName => $key ) {
			if (array_key_exists($key, $arr)) {
				$sql .= self::handleObjectUpdateInsert( 'insert', $key, $propName, $arr[$key], $dbDriver, $komma, $blob );
				$komma = ',';
			}	
		}
		// custfields
		foreach (array_keys($arr) as $key) {
			if( DBProperty::isCustomPropertyName($key) ) {
				$sql .= self::handleObjectUpdateInsert( 'insert', $key, strtoupper($key), $arr[$key], $dbDriver, $komma, $blob );
				$komma = ',';
			}
		}
//		$sql = preg_replace( '/\,$/', '', $sql );
		$sql .= " )";
		$sth = $dbDriver->query($sql, array(), $blob);
		return $sth;
	}

	/*
	 * Use getObjectRows() instead to get the Object properties.
	 * Returns a db handler after performing a query on
	 * retrieving object db properties for an object on
	 * all the version histories.
	 *
	 * @param int $id Object DB id.
	 * @param array $areas The area, 'Workflow' or 'Trash' where the object resides.
	 * @return DB resource handler.
	 */
	static public function getObject( $id, $areas=null )
	{
		if( is_null( $areas ) ) {
			$areas = array('Workflow');
		}		
	
		$dbDriver = DBDriverFactory::gen();
		$verFld = $dbDriver->concatFields( array( 'o.`majorversion`', "'.'", 'o.`minorversion`' )).' as "version"';

		$dbo = ($areas[0] == 'Workflow') ? $dbDriver->tablename( self::TABLENAME ) : $dbDriver->tablename( 'deletedobjects' );
		$sql = "SELECT o.*, $verFld FROM $dbo o WHERE `id` = ?";
		$params = array(intval( $id ));
		$sth = $dbDriver->query( $sql, $params );

		return $sth;
	}

    /**
     * Returns the database row of an object, either from the Workflow or from the Trash.
     * First the Workflow area is checked and if nothing is found the Trash Can is checked.
     *
     * @param int|string $objectId Id of the object.
     * @param boolean $workflow Found in the Workflow.
     * @return array database row when found else empty.
     */
    static public function getObjectRow( $objectId, &$workflow )
    {
        $areas = array ( 'Workflow', 'Trash' );
        $result = array();
        foreach ( $areas as $area ) {
            $sth = self::getObject( intval( $objectId ), array( $area ) );
            if( $sth ) {
                $result = self::fetchResults( $sth );
            }
            if ( $result ) {
                $workflow = ( $area == 'Workflow' ) ? true : false ;
                break;
            }
        }

        return $result ? $result[0] : $result; // database row or empty array;
    }

	/**
	 * Returns the current version of an object. The format is <majorversion>.<minorversion>
	 * @param int $id Object ID
	 * @param string $area The area, 'Workflow' or 'Trash' where the object resides.
	 * @return null | string <majorversion>.<minorversion>
	 * @throws BizException
	 */
	static public function getObjectVersion( $id, $area = 'Workflow' )
	{
		$dbDriver = DBDriverFactory::gen();
		$verFld = $dbDriver->concatFields( array( 'o.`majorversion`', "'.'", 'o.`minorversion`' )).' as "version"';
		$dbo = ($area == 'Workflow') ? $dbDriver->tablename( self::TABLENAME ) : $dbDriver->tablename( 'deletedobjects' );
		$result = null;

		$sql = 'SELECT '.$verFld.' FROM '.$dbo.' o WHERE `id` = ?';
		$params = array(intval( $id ));
		$sth = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch( $sth  );

		if ( $row ) {
			$result = $row[ 'version'];
		}
		return $result;
	}

	/**
	 * Returns the current version for a list of given objects (ids).
	 *
	 * @param integer[] $ids List of object ids.
	 * @return string[] List of object ids (keys) and versions <majorversion>.<minorversion> (values)
	 * @throws BizException
	 */
	static public function getObjectVersions( array $ids )
	{
		// Bail out when invalid parameters provided. (Paranoid check.)
		if( !$ids ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		$dbDriver = DBDriverFactory::gen();
		$select = array( 'id', $dbDriver->concatFields( array( '`majorversion`', "'.'", '`minorversion`' )).' as "version"' );
		$where = '`id` IN ('.implode( ',', $ids ).')';
		$rows = self::listRows( self::TABLENAME, null, null, $where, $select );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		$versions = array();
		foreach( $rows as $row ) {
			$versions[$row['id']] = $row['version'];
		}
		return $versions;
	}

	/**
	 * Resolves the Type property value of a given Object (id).
	 *
	 * @param integer $id The id of the Object.
	 * @param string $area 'Workflow' or 'Trash' where the object resides.
	 * @return string The requested Type value, or NULL when Object was not found.
	 */
	static public function getObjectType( $id, $area = 'Workflow' )
	{
		return self::getColumnValueByName( $id, $area, 'type' );
	}

	/**
	 * Resolves the Deadline property value of a given Object (id).
	 *
	 * @param integer $id The id of the Object.
	 * @param string $area 'Workflow' or 'Trash' where the object resides.
	 * @return string The requested Deadline value, or NULL when Object was not found.
	 */
	static public function getObjectDeadline( $id, $area = 'Workflow' )
	{
		return self::getColumnValueByName( $id, $area, 'deadline' );
	}

	/**
	 * Resolves the Name property value of a given Object (id).
	 *
	 * @param integer $id The id of the Object.
	 * @param string $area 'Workflow' or 'Trash' where the object resides.
	 * @return string The requested Name value, or NULL when Object was not found.
	 */
	static public function getObjectName( $id, $area = 'Workflow' )
	{
		return self::getColumnValueByName( $id, $area, 'name' );
	}

	/**
	 * Resolves the PublicationId property value of a given Object (id).
	 *
	 * @param integer $id The id of the Object.
	 * @param string $area 'Workflow' or 'Trash' where the object resides.
	 * @return integer The requested PublicationId value, or NULL when Object was not found.
	 */
	static public function getObjectPublicationId( $id, $area = 'Workflow' )
	{
		return self::getColumnValueByName( $id, $area, 'publication' );
	}

	/**
	 * Resolves the StateId property value of a given Object (id).
	 *
	 * @param integer $id The id of the Object.
	 * @param string $area 'Workflow' or 'Trash' where the object resides.
	 * @return integer The requested StateId value, or NULL when Object was not found.
	 */
	static public function getObjectStatusId( $id, $area = 'Workflow' )
	{
		return self::getColumnValueByName( $id, $area, 'state' );
	}
	
	/**
	 * Returns array of db props for the object. Relations not resolved.
	 *
	 * @param int $id Object DB id.
	 * @param array $areas 'Workflow' or 'Trash'. Where the object resides.
	 * @return array of DB properties of the object.
	 * @throws BizException
	 */
	static public function getObjectRows( $id, array $areas=null )
	{
		$dbDriver = DBDriverFactory::gen();

		$sth = self::getObject( $id, $areas );

		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		$objRow = $dbDriver->fetch($sth);
		if( !$objRow ) {
			throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
		}
		return $objRow;
	}
	
	/**
	 * Returns array of BizProps for the object. Relations not resolved.
	 * @param int $id Id of the object for which the BizProp is retrieved.
	 * @param array $areas 'Workflow' or 'Trash' where the object is resided.
	 * @return array of Biz properties of the object retrieved.
	 */
	static public function getObjectProps( $id, array $areas=null )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php'; // to convert to biz props
		return BizProperty::objRowToPropValues( self::getObjectRows( $id, $areas ) );
	}

	/**
	 * Retrieves the essential object properties to serve the MultisetProperties feature.
	 * This is the minimum set of props that is required for the web service (and plugins)
	 * to recognize the objects and apply biz logics before it comes to the real operation
	 * of updating (a few) object properties for (many) multiple objects.
	 *
	 * @since 9.2.0
	 * @param integer[] $objectIds List of object ids.
	 * @return MetaData[] List of properties indexed by object id.
	 */
	static public function getMultipleObjectsProperties( array $objectIds )
	{
		$rows = self::getMultipleObjectDBRows( $objectIds );
		$mds = array();
		if( $rows ) foreach( $rows as $row ) {

			$md = new MetaData();
			$md->BasicMetaData = new BasicMetaData();
			$md->RightsMetaData = new RightsMetaData();
			$md->SourceMetaData = new SourceMetaData();
			$md->ContentMetaData = new ContentMetaData();
			$md->WorkflowMetaData = new WorkflowMetaData();

			$md->BasicMetaData->StoreName = $row['storename']; // internal prop (not in WSDL)
			$md->BasicMetaData->ID = $row['id'];
			$md->BasicMetaData->DocumentID = $row['documentid'];
			$md->BasicMetaData->Name = $row['name'];
			$md->BasicMetaData->Type = $row['type'];
			$md->BasicMetaData->Publication = new Publication();
			$md->BasicMetaData->Publication->Id = $row['publication'];
			$md->BasicMetaData->Publication->Name = $row['pubname'];
			$md->BasicMetaData->Category = new Category();
			$md->BasicMetaData->Category->Id = $row['section'];
			$md->BasicMetaData->Category->Name = $row['secname'];
			$md->BasicMetaData->ContentSource = $row['contentsource'];
			$md->ContentMetaData->Format = $row['format'];
			$md->WorkflowMetaData->State = new State();
			$md->WorkflowMetaData->State->Id = $row['state'];
			$md->WorkflowMetaData->State->Name = $row['state'] == -1 ? BizResources::localize( 'PERSONAL_STATE' ) : $row['sttname'];
			$md->WorkflowMetaData->State->Type = $row['state'] == -1 ? $row['type'] : $row['stttype'];
			if( $row['state'] == -1 ) { // Personal State
				$md->WorkflowMetaData->State->Color = substr( PERSONAL_STATE_COLOR, 1 );
			} else {
				$md->WorkflowMetaData->State->Color = substr( $row['sttcolor'], 1 ); // remove # prefix
			}
			$md->WorkflowMetaData->RouteTo = $row['routeto'];
			$md->WorkflowMetaData->LockedBy = $row['lockedby'];
			$md->WorkflowMetaData->Version = $row['version'];
			$md->WorkflowMetaData->Modified = $row['modified'];
			$md->WorkflowMetaData->Modifier = $row['modifier'];
			$md->WorkflowMetaData->Comment = $row['comment'];
			$mds[ $row['id'] ] = $md;
		}
		return $mds;
	}

	/**
	 * Returns an array of database object rows.
	 *
	 * @param array $objectIds
	 * @return array with object rows.
	 */
	static public function getMultipleObjectDBRows( array $objectIds )
	{
		$dbDriver = DBDriverFactory::gen();
		$objTbl = $dbDriver->tablename( self::TABLENAME );
		$pubTbl = $dbDriver->tablename( 'publications' );
		$secTbl = $dbDriver->tablename( 'publsections' );
		$sttTbl = $dbDriver->tablename( 'states' );
		$lckTbl = $dbDriver->tablename( 'objectlocks' );
		$verFld = $dbDriver->concatFields( array( 'o.`majorversion`', "'.'", 'o.`minorversion`' ) ).' as "version"';

		$sql = 'SELECT o.`id`, o.`documentid`, o.`name`, o.`type`, o.`contentsource`, o.`storename`, '.
			'o.`publication`, o.`section`, o.`state`, o.`format`, o.`modified`, o.`modifier`, o.`comment`, '.
			'pub.`publication` as "pubname", sec.`section` as "secname", stt.`state` as "sttname", '.
			'stt.`type` as "stttype", stt.`color` as "sttcolor", o.`routeto`, lck.`usr` as "lockedby", '.$verFld.' '.
			"FROM $objTbl o ".
			"INNER JOIN $pubTbl pub ON (o.`publication` = pub.`id` ) ".
			"LEFT JOIN $secTbl sec ON (o.`section` = sec.`id` ) ".
			"LEFT JOIN $sttTbl stt ON (o.`state` = stt.`id` ) ". // LEFT JOIN because of Personal status = -1
			"LEFT JOIN $lckTbl lck ON (o.`id` = lck.`object` ) ".
			'WHERE o.`id` IN ( '.implode( ',', $objectIds ).' ) ';
		$params = array();
		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth, 'id', false, $dbDriver );

		return $rows;
	}

	/**
	 * Retrieves the flag and its message for a list of objects.
	 *
	 * Function returns a list of array in the following format:
	 * $returnRows[objId] = array( 'objid' => 10, 'flag' => 2, 'message' => 'The flag message' )
	 *
	 * @param string[] $objectIds
	 * @return string[] Refer to function header
	 */
	public static function getMultipleObjectsFlags( $objectIds )
	{
		$dbDriver = DBDriverFactory::gen();
		$objFlagTbl = $dbDriver->tablename( 'objectflags' );

		$sql = 'SELECT `objid`, `flag`, `message` FROM '. $objFlagTbl .
				' WHERE `objid` IN ('. implode( ',', $objectIds ) .')';
		$sth = $dbDriver->query( $sql );
		$rows = self::fetchResults( $sth, 'objid', false, $dbDriver );

		return $rows;
	}

	static public function getTemplateObject( $name, $type )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename("objects");

		$sql = "SELECT `id`, `publication`, `issue` FROM $dbo WHERE `name` = '$name' AND `TYPE` = '$type'";
		$sth = $dbDriver->query($sql);

		return $sth;
	}

	/**
	 * Returns id of shadow object for an alien object
	 *
	 * @param string	$contentSource		Unique ID of 3rd party content source
	 * @param string	$externalID			External ID of the alien object
	 * @return string of shadow object id or null if there is not shadow for this alien
	 */
	static public function getObjectForAlien( $contentSource, $externalID )
	{
		$dbDriver = DBDriverFactory::gen();

		$dbo = $dbDriver->tablename(self::TABLENAME);
		$sql = "SELECT o.id FROM $dbo o WHERE `contentsource` = '$contentSource' AND `documentid` = '$externalID'";
		$sth = $dbDriver->query($sql);

		if( $sth) {
			$currRow = $dbDriver->fetch($sth);
			if( $currRow ) {
				return $currRow['id'];
			}
		}
		return null;
	}

	/**
	 * Returns a list of properties for a shadow object.
	 *
	 * Currently, function only returns shadow object id and shadow object type in the list.
	 *
	 * @since 10.1.3
	 * @param string $contentSource Shadow-object's Content Source.
	 * @param string $externalId Id that is unique to the ContentSource, which is the documentId in Enterprise.
	 * @return null|string[]
	 */
	public static function getObjectPropsForShadowObject( $contentSource, $externalId )
	{
		$dbDriver = DBDriverFactory::gen();

		$dbo = $dbDriver->tablename( self::TABLENAME );

		$sql = "SELECT o.`id`, o.`type` FROM $dbo o WHERE o.`contentsource` = ? AND o.`documentid` = ? ";
		$params = array( strval( $contentSource ), strval( $externalId ));
		$sth = $dbDriver->query( $sql, $params );

		$row = null;
		if( $sth ) {
			$row = $dbDriver->fetch( $sth );
		}
		return $row;
	}

	/**
	 * Returns rows of shadow objects for the given alien objects.
	 *
	 * The rows returned consists of id, contentsource and documentid db field names.
	 *
	 * @param array	$externalIds Unique IDs of 3rd party content source. Key is ContentSource and value is list of external ids.
	 * @return null|array List of shadow objects rows, null when no alien object ids are passed in.
	 */
	static public function getObjectsForAliens( $externalIds )
	{
		$rows = null;
		if( $externalIds ) {
			$wheres = array();
			$params = array();
			if( $externalIds ) foreach( $externalIds as $contentSourceId => $extIds ) {
				if( $extIds ) foreach( $extIds as $extId ) {
					$wheres[] = '(o.`contentsource` = ? AND o.`documentid` = ?)';
					$params[] = strval( $contentSourceId );
					$params[] = strval( $extId );
				}
			}

			$dbDriver = DBDriverFactory::gen();
			$objTable = $dbDriver->tablename( self::TABLENAME );
			$sql =  'SELECT o.`id`,  o.`contentsource`, o.`documentid` '.
				'FROM ' . $objTable . ' o '.
				'WHERE '.implode( ' OR ', $wheres ).' ';
			$sth = $dbDriver->query( $sql, $params );
			$rows = self::fetchResults( $sth, null, false ,$dbDriver );
		}
		return $rows;
	}

	/**
	 * Returns true when specified object name already exists in database.
	 * Can be used for new and existing objects. Only objecttargets are taken
	 * into account.
	 *
	 * @param array $issueIds List of issue ids to check for object name uniqueness
	 * @param string $name Object name
	 * @param string $type Object type
	 * @param int $id Object id if object already exists
	 * 
	 * @return int object id if name exists
	 */
	static public function objectNameExists( $issueIds, $name, $type, $id=null )
	{
		$dbdriver = DBDriverFactory::gen();
		$objectsTable = $dbdriver->tablename(self::TABLENAME);
		$targetsTable = $dbdriver->tablename('targets');
		$issueIdsStr = implode( ', ', $issueIds );

		$sql  = "SELECT o.`id` FROM $objectsTable o ";
		$sql .= "INNER JOIN $targetsTable tar ON (tar.`objectid` = o.`id`) ";
		$sql .= 'WHERE o.`name` = ? ';
		$sql .= 'AND o.`type` = ? ';
		$sql .= "AND tar.`issueid` IN ( $issueIdsStr ) ";
		$params = array( $name, $type );
		if ($id){
			$sql .= 'AND o.`id` != ? ';
			$params[] = $id;
		}

		$sth = $dbdriver->query( $sql, $params );
		$row = $dbdriver->fetch($sth);
		return (bool)$row;
	}

	/**
	 * Checks if the Object exists.
	 *
	 * Searches either the smart_deletedobjects or the smart_objects table based on the $area parameter
	 * and returns whether or not an object exists in that table.
	 *
	 * @param integer $id The Object Id to search for.
	 * @param string $area The area to search for 'Trash' or 'Workflow'.
	 * @return bool Whether or not the Object was found in the specified area.
	 * @throws BizException Throws an Exception if the Database connection fails.
	 */
	public static function objectExists( $id, $area )
	{
		$tableName = $area == 'Workflow' ? self::TABLENAME : 'deletedobjects';
		$select = array( 'id' );
		$where = '`id` = ?';
		$params = array( $id );
		$row = self::getRow( $tableName, $where, $select, $params );
		return isset( $row['id'] );
	}

	/**
	 * Checks if the Objects exists.
	 *
	 * Searches either the smart_deletedobjects or the smart_objects table based on the $area parameter
	 * and returns a list of object ids of those objects that do exists in that table.
	 *
	 * @param integer[] $ids The Object Ids to search for.
	 * @param string $area The area to search for 'Trash' or 'Workflow'.
	 * @return integer[] Object ids of those Objects that were found in the specified area.
	 * @throws BizException Throws an Exception if the Database connection fails.
	 */
	public static function filterExistingObjectIds( array $ids, $area )
	{
		$where = self::addIntArrayToWhereClause( 'id', $ids, false );
		if( !$where ) { // Bail out for bad collection of ids.
			return array();
		}
		$tableName = $area == 'Workflow' ? self::TABLENAME : 'deletedobjects';
		$rows = self::listRows( $tableName, 'id', '', $where );
		return $rows ? array_keys( $rows ) : array();
	}

	/**
	 * Tells if a given definition is in use by any object in the DB.
	 * Obviously, this is no matter the access rights of user asking.
	 * It also can check if there are *deleted* objects using the definition.
	 *
	 * @param int     $defId   The id of the definition (-> def kind is indicated by $defType)
	 * @param string  $idType  The type of $defId; PublicationId, PubChannelId, IssueId, SectionId, StateId or EditionId
	 * @param boolean $deleted True to check smart_deletedobjects table. False to check smart_objects table.
	 * @return boolean Wether or not any object could be found.
	 */
	static public function inUseByObjects( $defId, $idType, $deleted )
	{
		$dbDriver = DBDriverFactory::gen();
		$objTab = $dbDriver->tablename( $deleted ? 'deletedobjects' : self::TABLENAME );
		$tarTab = $dbDriver->tablename( 'targets' );
		$tedTab = $dbDriver->tablename( 'targeteditions' );

		$sql = "SELECT o.`id` FROM $objTab o ";
		switch( $idType ) {
			case 'PublicationId':
				$sql .= "WHERE o.`publication` = $defId ";
				break;
			case 'PubChannelId':
				$sql .= "LEFT JOIN $tarTab tar ON (tar.`objectid` = o.`id`) ";
				$sql .= "WHERE tar.`channelid` = $defId ";
				break;
			case 'IssueId':
				$sql .= "LEFT JOIN $tarTab tar ON (tar.`objectid` = o.`id`) ";
				$sql .= "WHERE tar.`issueid` = $defId ";
				break;
			case 'SectionId':
				$sql .= "WHERE o.`section` = $defId ";
				break;
			case 'StateId':
				$sql .= "WHERE o.`state` = $defId ";
				break;
			case 'EditionId':
				$sql .= "LEFT JOIN $tarTab tar ON (tar.`objectid` = o.`id`) ";
				$sql .= "LEFT JOIN $tedTab ted ON (ted.`targetid` = tar.`id`) ";
				$sql .= "WHERE ted.`editionid` = $defId ";
				break;
		}
		$sth = $dbDriver->query( $sql );
		$row = $dbDriver->fetch( $sth );
		return (bool)$row;
	}


    static public function checkNameObject( $publ, /** @noinspection PhpUnusedParameterInspection */
                                            $issue, $name, $type = null, $id = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$verFld = $dbDriver->concatFields( array( 'o.`majorversion`', "'.'", 'o.`minorversion`' )).' as "version"';

		$dbo = $dbDriver->tablename(self::TABLENAME);
		$publ = $dbDriver->toDBString($publ);
		$name = $dbDriver->toDBString($name);

		//TODO BZ#7258
		//and `issue` = $issue
		$sql = "SELECT o.*, $verFld FROM $dbo o WHERE o.`publication` = $publ AND o.`name` = '$name'";
		if ($type) $sql .= " AND o.`type` = '$type'";
		if ($id) $sql .= " AND o.`id` != $id";
		$sth = $dbDriver->query($sql);

		return $sth;
	}

	/**
	 * The ` character is used to indicate a DB column name
	 * It will be translated to e.g. [] for MSSQL by driver->query()
	 * To avoid that an odd number of ` signs in a text field (slugline, description)
	 * causes an invalid SQL statement, they are replaced by a 'normal' quote
	 *
	 * @param integer|integer[] $objectIds One or multiple object ids.
	 * @param string $modifier Full name of user who performs the update.
	 * @param array $arr List object properties to be updated.
	 * @param string $modified Datetime when properties are updated.
	 * @param string $storename Internal file storage name. Should be used for single object updates only.
	 * @return resource DB handle that can be used to fetch results.
	 * @throws BizException.
	 */
	static public function updateObject( $objectIds, $modifier, $arr, $modified, $storename = '' )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbstorename = $dbDriver->toDBString($storename);
		$db = $dbDriver->tablename("objects");
		$modifier = $dbDriver->toDBString($modifier);

		$sql = "UPDATE $db SET ";
		$comma ='';
		if ($modifier) {
			$sql .= "`modifier`='$modifier', `modified`='$modified'";
			$comma = ', ';
			// avoid duplicate insertions BZ#8267
			if( isset($arr['modifier']) ) unset($arr['modifier']);
			if( isset($arr['modified']) ) unset($arr['modified']);
		}
		if ($storename && !is_array($objectIds)) {
			$sql .= $comma."`storename` = '$dbstorename'";
			$comma = ', ';
			// avoid duplicate insertions BZ#8267
			if( isset($arr['storename']) ) unset($arr['storename']);
		}
		// avoid error on MSSQL: 8102:Cannot update identity column 'id'
		if( isset($arr['id']) ) unset($arr['id']);

		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$fields = BizProperty::getMetaDataObjFields();
		$fields = array_unique($fields);
		$blob = null;

		// Built-in properties
		foreach( $fields as $propName => $key ) {
			if ( array_key_exists( $key, $arr ) ) {
				$sql .= self::handleObjectUpdateInsert( 'update', $key, $propName, $arr[$key], $dbDriver, $comma, $blob );
				$comma = ',';
			}	
		}
		// Custom properties
		foreach ( array_keys( $arr ) as $key ) {
			require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
			if( DBProperty::isCustomPropertyName($key) ) {
				$sql .= self::handleObjectUpdateInsert( 'update', $key, strtoupper($key), $arr[$key], $dbDriver, $comma, $blob );
				$comma = ',';			}
		}		
		
		if( is_array($objectIds) ) { // multiple ids
			$sql .= ' WHERE `id` IN ( '.implode(',',$objectIds).' ) ';
		} else { // single id
			$sql .= " WHERE `id` = $objectIds ";
		}
		$sth = $dbDriver->query($sql, array(), $blob);

		if( !$sth ) {
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}

		return $sth;
	}

	static public function updatePageRange( $id, $range, $instance = 'Production' )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename("objects");
		$params = array( $range, $id );

		if ($instance == 'Production') {
			$sql = 'UPDATE ' .$db. ' SET `pagerange`= ? WHERE `id`= ? ';
			$dbDriver->query( $sql, $params );
		} else if ($instance == 'Planning') {
			$sql = 'UPDATE ' . $db . ' SET `plannedpagerange`= ? WHERE `id`= ? ';
			$dbDriver->query( $sql, $params );
		}
	}

	/**
	 * Update the deadlines information in the smart_objects table.
	 *
	 * The  deadlinechange field will be flagged when the field deadline and deadlinesoft are updated.
	 *
	 * @param int $objectId Object of which its deadline will be updated.
	 * @param string $deadlineHard Deadline to be updated in the database in datetime format.
	 * @return string[] Recalculated deadlines in datetime format.
	 */
	static public function setObjectDeadline( $objectId, $deadlineHard )
	{
		$deadlineSoft = '';
		self::updateDeadlines( $deadlineHard, $deadlineSoft, $objectId, false );

		return array( 'Deadline' => $deadlineHard, 'DeadlineSoft' => $deadlineSoft );
	}

	/**
	 * Sets the deadline for a newly created object given by objectid.
	 * The deadline is queried from either the issuesectionstate-table or the issuesection table, depending on the 
	 * usage of PERSONAL state or not, then updated in the objecttable (if changed).
	 * The algorithm and interface has been modified since previous versions to allow for multiple issue id's.
	 * Always the deadline is chosen for an still active issue and the earliest deadline.
	 *
	 * @param int $objectId Id of the object to set the deadline of
	 * @param array $issueids issues needed to calculate the deadline
	 * @param int $sectionid Sectionid of the object
	 * @param int $stateid Stateid of the object, can be personal
	 * @return string[] Recalculated deadlines in datetime format.
	 */
	static public function objectSetDeadline( $objectId, $issueids, $sectionid, $stateid )
	{
		$deadlineHard = '';
		$deadlineSoft = '';
		if ($issueids && $stateid != -1) { //personal state can not have a deadline
			require_once BASEDIR . '/server/dbclasses/DBIssueSection.class.php';
			//fetch the issue-section-state deadline
			$deadlineHard = DBIssueSection::getDeadlineForIssueCategoryStatus($issueids, $sectionid, $stateid);
			if ( !$deadlineHard ) {
				$deadlineHard = DBIssueSection::getDeadlineForIssueCategory($issueids, $sectionid);
			}
		}

		self::updateDeadlines( $deadlineHard, $deadlineSoft, $objectId, true );

		return array( 'Deadline' => $deadlineHard, 'DeadlineSoft' => $deadlineSoft );
	}

	/**
	 * Update the hard deadline, soft deadline and deadlinechanged field in database.
	 *
	 * @param string $deadlineHard The deadline of the object.
	 * @param string $deadlineSoft[out] The deadline of the object which will be calculated by the function.
	 * @param int $objectId
	 * @param bool $checkDeadline When True, no updates take place if the deadline is not changed.
	 */
	public static function updateDeadlines( $deadlineHard, &$deadlineSoft, $objectId, $checkDeadline )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename( self::TABLENAME );

		if (!empty( $deadlineHard )) {
			require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
			$deadlineSoft = DateTimeFunctions::calcTime( $deadlineHard, -DEADLINE_WARNTIME );
		}

		// Only flag that deadlinechanged when the new deadline differs from the one stored in the database.
		$sql =  "UPDATE $dbo SET `deadline` = '$deadlineHard', `deadlinesoft` = '$deadlineSoft', `deadlinechanged` = 'Y' ".
			"WHERE `id` = $objectId ";
		if( $checkDeadline ) { // Only add this condition when requires it
			$sql .= "AND ( `deadline` <> '$deadlineHard' OR `deadline` IS NULL ) ";
		}
		$dbDriver->query($sql);
	}

	/**
	 * Lists all publications to which the array of objectid's are assigned. <br/>
	 * @param array $objectids: ids of objects.
	 * @return array of $rows with unique publicationid's.
	 */
	static public function listPublications($objectids)
	{
		$dbdriver = DBDriverFactory::gen();
		$objectstable = $dbdriver->tablename(self::TABLENAME);
		$ids = implode(',',$objectids);
		$sql = "SELECT DISTINCT `publication` FROM $objectstable WHERE `id` IN ( $ids ) ";
		$sth = $dbdriver->query($sql);
		return self::fetchResults($sth);
	}

	/**
	 * Reads the placed on object names and placed on pages.
	 *
	 * @param int $objId
	 * @return array with arrays with key "name" for the placed on object name
	 * 			     and key "pagerange" for the placed on page
	 */
	static public function getPlacedOnRows($objId)
	{
		$dbDriver = DBDriverFactory::gen();

		$objectsTable = $dbDriver->tablename(self::TABLENAME);
		$objectRelationsTable = $dbDriver->tablename('objectrelations');

		//TODO user rights?
		$sql  = "SELECT po.`name`, rel.`pagerange` "
			. "FROM $objectRelationsTable rel "
			. "LEFT JOIN $objectsTable po ON (po.`id` = rel.`parent`) "
			. "WHERE rel.`child` = %d AND rel.`type` = 'Placed' ";
		$sql = sprintf($sql, $objId);
		$sth = $dbDriver->query($sql);
		$rows = self::fetchResults($sth);

		return $rows;
	}

	/**
	 * Reads the placed on object names and placed on pages per passed object id.
	 * Same as getPlacedOnRows() but then of multible objects.
	 * 
	 * @param integer[] $objIds
	 * @return array (key is object id) with arrays with key "name" for the placed on object name
	 * and key "pagerange" for the placed on page.
	 */
	static public function getPlacedOnRowsByObjIds( array $objIds )
	{
		$dbDriver = DBDriverFactory::gen();

		$objectsTable = $dbDriver->tablename( self::TABLENAME );
		$objectRelationsTable = $dbDriver->tablename( 'objectrelations' );

		$sql  = 'SELECT rel.`child`, po.`name`, rel.`pagerange` '
			. "FROM $objectRelationsTable rel "
			. "LEFT JOIN $objectsTable po ON (po.`id` = rel.`parent`) "
			. 'WHERE ' . self::addIntArrayToWhereClause( 'rel.child', $objIds, false ) .' AND rel.`type` = ? ';
		$params = array( 'Placed' );
		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth );

		$result = array();
		foreach( $rows as $row ) {
			$result[ $row[ 'child' ] ][] = $row;	
		} 

		return $result;
	}

	/**
	 * Returns the page range and the planned page range of a object.
	 *
	 * @param int $objId
	 * @return array with keys "pagerange" and "plannedpagerange" or null if object not found
	 */
	static public function getPageRangeRow($objId)
	{
		$dbDriver = DBDriverFactory::gen();

		$objectsTable = $dbDriver->tablename(self::TABLENAME);

		$sql  = "SELECT `pagerange`, `plannedpagerange` "
			. "FROM $objectsTable "
			. "WHERE `id` = %d ";
		$sql = sprintf($sql, $objId);
		$sth = $dbDriver->query($sql);
		$rows = self::fetchResults($sth);

		$result = null;
		if (count($rows) > 0){
			// only first row (should not get more)
			$result = $rows[0];
		}

		return $result;
	}

	/**
	 * Returns the page range and the planned page range of multiple objects.
	 * Same as getPageRangeRow() but then of multiple objects.
	 * 
	 * @param integer[] $objIds
	 * @return array (with key object id ) of array with keys "pagerange" and "plannedpagerange".
	 */
	static public function getPageRangeRowByObjIds( $objIds )
	{
		$dbDriver = DBDriverFactory::gen();
		$objectsTable = $dbDriver->tablename( self::TABLENAME );

		$sql  = 'SELECT `id`, `pagerange`, `plannedpagerange` '
			. "FROM $objectsTable "
			. 'WHERE '.self::addIntArrayToWhereClause( 'id', $objIds, false );
		$sth = $dbDriver->query( $sql );
		$rows = self::fetchResults( $sth, 'id' );


		return $rows;
	}	

	/**
	 * Recalculates all deadlines of objects of an issue given by $issueid
	 *
	 * @param int $issueid 
	 */
	static public function recalcObjectDeadlines($issueid)
	{
		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		$dbDriver = DBDriverFactory::gen();
		$objectstable = $dbDriver->tablename(self::TABLENAME);
		$targetstable = $dbDriver->tablename('targets');
		$relationtable = $dbDriver->tablename('objectrelations');
		
		//Select all objects in an issue with all issues (targets) the object is targeted for
		$sql  = " SELECT o.`id`, o.`section`, o.`state`, tar.`issueid` ";
		$sql .= " FROM $objectstable o ";
		$sql .= " INNER JOIN $targetstable tar ON (o.`id` = tar.`objectid`) ";
		$sql .= " WHERE tar.`issueid` = $issueid ";
		
		$sth = $dbDriver->query($sql);
		
		//Loop through all rows, resolve the issueids for each object
		$rows = array();
		$foundObjects = array();
		
		while( ($row = $dbDriver->fetch($sth)) ) {
			if (!isset($rows[$row['id']])) {
				$foundObjects[] = $row['id'];
				$rows[$row['id']] = $row;
				$rows[$row['id']]['issueids'] = array($row['issueid']);
			}
			else {
				$rows[$row['id']]['issueids'][] = $row['issueid'];
			}
		}

		// Get relational-target issues for objects (Images/Articles) with no object-target issue BZ#21218
		if ($foundObjects) { // If no objects are found with an object-target issue there cannot be any child.
			$excludeObjects = implode(', ', $foundObjects);
			$canInheritParentDeadlineTypes = BizDeadlines::getCanInheritParentDeadline();
			$canInheritParentDeadlineTypes = array_keys( $canInheritParentDeadlineTypes );
			foreach ( $canInheritParentDeadlineTypes as &$canInheritParentDeadlineType) {
				$canInheritParentDeadlineType = "'$canInheritParentDeadlineType'"; // Make sure elements get quoted
			}
			$objectTypes = implode(', ', $canInheritParentDeadlineTypes );

			$sql = " SELECT o.`id`, o.`section`, o.`state`, tar.`issueid` ";
			$sql .= " FROM $objectstable o ";
			$sql .= " INNER JOIN $relationtable rel ON ( rel.`child` = o.`id` )";
			$sql .= " INNER JOIN $targetstable tar ON ( rel.`id` = tar.`objectrelationid` )";
			$sql .= " WHERE o.`id` NOT IN ( $excludeObjects )";
			$sql .= " AND o.`type` IN ( $objectTypes )";
			$sql .= " AND tar.`issueid` = $issueid ";
			$sql .= " AND rel.`type` IN ( 'Placed', 'Contained' )";

			$sth = $dbDriver->query( $sql );

			//Loop through all rows, resolve the issueids for each object
			while (($row = $dbDriver->fetch($sth))) {
				if (!isset($rows[$row['id']])) {
					$rows[$row['id']] = $row;
					$rows[$row['id']]['issueids'] = array($row['issueid']);
				} else {
					$rows[$row['id']]['issueids'][] = $row['issueid'];
				}
			}
		}

		foreach ($rows as $row) {
			// Object has no object-target issues. See if there are relational-target issues.
			// Only for images and articles. BZ#21218
			if ( !$row['issueids'] && ( BizDeadlines::canInheritParentDeadline( $row['type'])) ) {
				$row['issueids'] = BizTarget::getRelationalTargetIssuesForChildObject( $row['id'] );
			}
			self::objectSetDeadline($row['id'], $row['issueids'], $row['section'], $row['state']);
		}
	}	
	
	/**
	 * Marks objects as indexed.
	 *
	 * @param array $objectIds: ids of objects. Null for all objects at once.
	 * @param boolean $deletedObjects True for using smart_deletedobjects table. False for using smart_objects instead.
	 * @return void
	 */
	static public function setIndexed( $objectIds, $deletedObjects = false )
	{
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename( $deletedObjects ? 'deletedobjects' : self::TABLENAME );
		$ids = implode(',',$objectIds);
		$sql = "UPDATE $dbo SET `indexed` = 'on' ";
		if( count($objectIds) > 0 ) {
			$sql .= "WHERE `id` IN ( $ids ) ";
		}
		$dbdriver->query($sql);
	}

	/**
	 * Marks objects as non-indexed.
	 *
	 * @param array $objectIds: ids of objects. Null for all objects at once.
	 * @param string[] $areas Either 'Workflow' or 'Trash'.
	 * @return void
	 */
	static public function setNonIndex( $objectIds, $areas = array('Workflow'))
	{
		$dbdriver = DBDriverFactory::gen();
		
		foreach ( $areas as $area ){
			$dbo = ($area == 'Workflow') ? $dbdriver->tablename( self::TABLENAME ) : $dbdriver->tablename('deletedobjects');
			
			$sql = "UPDATE $dbo SET `indexed` = '' ";
			if( count($objectIds) > 0 ) {
				$ids = implode(',',$objectIds);
				$sql .= "WHERE `id` IN ( $ids ) ";
			}
			
			$dbdriver->query($sql);
		}
	}	

	/**
	 * Get object rows that needs to be indexed, up to specified maximum amount.
	 *
	 * @param integer	$lastObjId The last (max) object id that was indexed the previous time. Used for pagination.
	 * @param integer	$maxCount  Maximum number of objects to return. Used for pagination.
	 * @return array of object rows
	 */
	static public function getObjectsToIndex( $lastObjId, $maxCount )
	{
		$objids = array();
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename(self::TABLENAME);
		
		$params = array();
		$sql = "SELECT o.`id` FROM $dbo o WHERE o.`indexed`= '' AND o.`id` > ? ORDER BY o.`id` ASC ";
		$params[] = intval($lastObjId);
		
		if( $maxCount > 0 ) {
			$sql = $dbdriver->limitquery( $sql, 0, $maxCount );
		}
		$sth = $dbdriver->query($sql, $params);
		while( ( $row = $dbdriver->fetch($sth) ) ) {
			$objids[]=$row;
		}
		return $objids;
	}
	
	/**
	 * Get object ids that needs to be unindexed, up to specified maximum amount.
	 *
	 * @param integer	$lastObjId The last (max) object id that was unindexed the previous time. Used for pagination.
	 * @param integer	$maxCount  Maximum number of object ids to return. Used for pagination.
	 * @return array of object ids
	 */
	static public function getIndexedObjects( $lastObjId, $maxCount )
	{
		// query DB
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename(self::TABLENAME);
		$params = array();
		$sql = "SELECT o.`id` FROM $dbo o WHERE o.`indexed`='on' AND o.`id` > ? ORDER BY o.`id` ASC ";
		$params[] = intval($lastObjId);
		if( $maxCount > 0 ) {
			$sql = $dbdriver->limitquery( $sql, 0, $maxCount );
		}
		$sth = $dbdriver->query($sql, $params);

		// collect ids
		$ids = array();
		while( ( $row = $dbdriver->fetch($sth) ) ) {
			$ids[] = $row['id'];
		}
		return $ids;
	}	
	
	/**
	 * Counts the objects at smart_objects table that needs to be indexed (or needs to be un-indexed).
	 *
	 * @param boolean $toIndex Whether to count objects to index or to un-index
	 * @return integer Object count.
	 */
	static public function countObjectsToIndex( $toIndex )
	{
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT count(*) as `c` FROM $dbo o ";
		if( $toIndex ) {
			$sql .= "WHERE o.`indexed`='' "; // un-indexed = needs to be indexed
		} else { // to un-index
			$sql .= "WHERE o.`indexed`='on' "; // indexed = needs to be un-indexed
		}
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		return intval($row['c']);
	}		

	/**
	 * Counts the objects at smart_objects table that changed since last optimization.
	 *
	 * @param string $lastOpt Timestap (datetime) of last successful optimization.
	 * @return integer Object count.
	 */
	static public function countObjectsToOptimize( $lastOpt )
	{
		if( empty($lastOpt) ) return self::countObjects(); // no time means all
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename(self::TABLENAME);
		$params = array();
		$sql = "SELECT count(*) as `c` FROM $dbo o WHERE o.`modified` > ? ";
		$params[] = $lastOpt;
		$sth = $dbDriver->query($sql, $params);
		$row = $dbDriver->fetch($sth);
		return intval($row['c']);
	}		

	/**
	 * Counts the objects at smart_objects table.
	 *
	 * @return integer Object count.
	 */
	static public function countObjects()
	{
		$dbdriver = DBDriverFactory::gen();
		$dbo = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT count(*) as `c` FROM $dbo o ";
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		return intval($row['c']);
	}

	/**
	 * Returns an array with DB values of object property for a range of object
	 * ids.
	 *
	 * @param array $objectids Contains the object ids
	 * @param string $property Biz property to get (is translated to db column)
	 * @return array with object ids as keys, each containing an array with db column
	 * as key and db value [1234][`name`, 'MyName']
	 */
	static public function getAttributeOfObjects($objectids, $property)
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$fields = BizProperty::getMetaDataObjFields();
		$dbColumn = isset($fields[$property])?$fields[$property]:null;
		if ($dbColumn == null) {
			return array();
		}
			
		$where = '`id` in (' . implode(',', $objectids) . ')'; 
		$rows = DBBase::listRows(self::TABLENAME, 'id', $property, $where, array($dbColumn));		
		return $rows;
	}

	/**
	 * Generates a part of a sql-statement that can be used to update/insert object
	 * information in the database. Typically used if an object is added/updated.
	 * Furthermore object information is also written to smart_deletedobjects and
	 * smart_objectversions.
	 * 
	 * @param string $operation Fragment is used for update or insert operation
	 * @param string $dbField name of the field as sent to the dbdriver
	 * @param string $propertyName name of the field as know in the Biz-layer
	 * @param string/integer/double $value value to be inserted/updated
	 * @param WW_DbDrivers_DriverBase $dbDriver connection to the database
	 * @param string $comma separator
	 * @param array $blobs placeholder of blobs to be inserted/updated
	 * @return string SQL-statement.
	 */
	static public function handleObjectUpdateInsert( $operation, $dbField, $propertyName, $value, $dbDriver, $comma, &$blobs )
	{
		// $fieldType = string, int, blob, double
		// DBObject::updateObject
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';

		if( !BizProperty::isCustomPropertyName( $propertyName ) ) { // Only format DB value for non custom props
			$formattedDbValue = self::formatDbValue( $propertyName, $value );
		} else {
			$formattedDbValue = $value;
		}
		$descript = '';
		if ( self::isBlob( $propertyName ) ) {
			$blobs[] = $formattedDbValue;
			$descript = '#BLOB#';
		} else {
			require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
			$customProp = BizProperty::isCustomPropertyName( $propertyName );
			$propType = self::getPropertyType( $propertyName, $customProp );

			// Fix for BZ#31337. A custom property of the type string can only save up to 200 characters/bytes.
			if ( $customProp && ($propType == 'string' || $propType == 'list') ) {
				$formattedDbValue = self::truncateCustomPropertyValue( $propertyName, $formattedDbValue );
			}

			$quotingDbValueNeeded = self::isQuoteDBValueNeeded( $propType );
			if ( $quotingDbValueNeeded ) {
				$descript = "'" . $dbDriver->toDBString( $formattedDbValue ) ."'"; // Need to be quoted with ''
			} elseif( !$value ) {
				// When there's no value and no quote needed, it needs to be transformed into appropriate 'value' before it is inserted into DB.
				if( $propType == 'bool' ) {
					$descript = $customProp ? '0' : "''"; // read isQuoteDBValueNeeded() header.				
				} elseif ( $propType == 'int' || $propType == 'double' ) {
					$descript = '0';
				}
			} else {
				$descript = $formattedDbValue; // Nothing extra to do with the formatted db value.		
			}
		}		
		
		if ( $operation == 'insert' ) {
			$sql = "$comma $descript";
		} else {
			$sql = $comma . " " . $dbDriver->quoteIdentifier( $dbField ) . " = " . $descript;
		}
		
		return $sql;
	}
	/**
	 * Checks if a property is stored as a 'blob'. Standard properties are marked
	 * as 'blob' in their $SqlTProps[] (see BizProperty). Custom properties are either
	 * marked as 'blob' (mysql), 'text' (mssql) or 'clob' (oracle).
	 * 
	 * @param string $bizProperty Property to be checked.
	 * @return true if property is stored as 'blob' else false.
	 */
	// TODO Move to BizProperty or dbdriver.
	static protected function isBlob( $bizProperty )
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$dbType = BizProperty::getDBTypeProperty( $bizProperty ); // handles standard and custom properties
		switch ($dbType) {
			case 'blob': // Standard property, custom property mysql
			case 'text': // Custom property mssql
			case 'clob': // Custom property oracle
				return true;
			default:
				return false;
		}
	}
	/**
	 * Checks if the value to be stored must be sent as quoted string to the database.
	 * Custom properties and standard properties of type int and double will not
	 * be quoted. Standard properties with Property->Type = bool are stored as strings
	 * in the database (their db type is 'string'). Custom boolean properties have
	 * db type integer and will not be quoted. 
	 * @param string $propertyType
	 * @return true if value must be quoted else false
	 */
	static protected function isQuoteDBValueNeeded( $propertyType )
	{
		switch ($propertyType) {
			case 'bool': // Custom boolean property
			case 'int': // Custom/standard integer
			case 'double': // Custom/standard double
				return false;
			default:
				return true;
		}
	}

	/**
	 * Returns the property type of the property name given ($property).
	 *
	 * @param string $property The name of the property to be checked for the property type.
	 * @param boolean $customProp True if it is a custom property, False for standard/builtin property.
	 * @return string The PropertyType or an empty string if the PropertyType could not be determined.
	 */
	static protected function getPropertyType( $property, $customProp )
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		if( $customProp ) {
			$type = BizProperty::getCustomPropertyType( $property );
		} else {
			$types = BizProperty::getMetaDataSqlFieldTypes();
			$type = $types[$property];			
		}
		return $type;
	}
	
	/**
	 * In some cases the value of a property has to be formatted/truncated etc. before
	 * it is stored into the database.
	 * 
	 * @param string $propName
	 * @param mixed $value Value of the Property $propName
	 * @return string Formatted/adjusted value
	 */
	static protected function formatDbValue( $propName, $value )
	{
		$formattedValue = null;
		switch ($propName) {
			case 'PlainContent':
				if ( DBTYPE == 'mysql' ) {
					require_once BASEDIR.'/server/utils/UtfString.class.php';
					$formattedValue = UtfString::truncateMultiByteValue( $value, 64000 );
					// @TODO If applicable for all mysql blobs this can be moved to the dbdriver.
				} else {
					$formattedValue = $value;
				}
				break;
			case 'CopyrightMarked':
				// BZ#10541 When SOAP client does set the xsi:type="xsd:boolean" attribute, 
				// the PHP type becomes boolean. The odd situation is that the DB field
				// is a string. In the past, true/false string are stored, so we cast them 
				// to string type, to keep acting the same and so not disturbing further processing.
				if ( gettype( $value ) == 'boolean' ) {
					$formattedValue = $value ? 'true' : 'false';
				} else { // else: SOAP clients that do NOT set the attribute: type = string
					// Some SOAP clients (like SB) send '1' for booleans. Let's be flex here...
					$trimVal = trim( strtolower( $value ) );
					$formattedValue = ($trimVal == 'true' || $trimVal == '1' || $trimVal == 'on' || $trimVal == 'y') ? 'true' : 'false';
				}
				break;
			case 'HighResFile':
				$formattedValue = addslashes( $value );
				break;
			case 'Name':
				require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
				$formattedValue = mb_substr( $value, 0, BizProperty::getStandardPropertyMaxLength( 'Name' ), 'UTF8' );
				break;
			default:
				$formattedValue = self::truncatePropertyValue( $propName, $value );
				break;
		}
		return $formattedValue;
	}
	
	/**
	 * For each metaKey with its metaValue that passed in,
	 * it is checked if the metaKey has MaxLength defined in
	 * BizProperty, if it is defined, it will truncate the extra
	 * characters or bytes. Whether it is truncating characters or bytes
	 * depends on DB flavors:
	 *     L> MYSQL: Truncates defined MAXLENGTH characters.
	 *     L> MSSQL,ORACLE: Truncates defined MAXLENGTH bytes.
	 *
	 * @param string $metaKey The property name
	 * @param string $metaValue The property value to be checked if it needs to be truncated.
	 * @return string Meta valuethat has been adjusted if the chars/bytes has exceeded MAXLENGTH.
	 */
	static private function truncatePropertyValue( $metaKey, $metaValue )
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$infoProps = BizProperty::getPropertyInfos();
		if( $infoProps[$metaKey] && 
				isset($infoProps[$metaKey]->MaxLength) && $infoProps[$metaKey]->MaxLength > 0 )
		{
			$dbdriver = DBDriverFactory::gen();
			if( $dbdriver->hasMultibyteSupport() ) { // MYSQL
				// mb_substr gets the length of string in number of characters
				$metaValue = mb_substr( $metaValue, 0, $infoProps[$metaKey]->MaxLength, 'UTF-8' );
			} else { // Oracle & MSSQL
				// mb_strcut gets the length of string in bytes
				$metaValue = mb_strcut( $metaValue, 0, $infoProps[$metaKey]->MaxLength, 'UTF-8' );
			}
		}
		return $metaValue;
	}

	/**
	 * The custom properties of the type string has a max length of 200 characters (MySQL)
	 * or 200 bytes (MSSQL or Oracle). This function truncates the length to 200 bytes/characters
	 * (dependent on the used database system).
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @return string
	 */
	static protected function truncateCustomPropertyValue($propertyName, $value)
	{
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		$property = DBProperty::getObjectPropertyByName( $propertyName );
		// When the max length is set to 200 or less, use that value, longer values can't be saved.
		$maxLength = ($property->MaxLength && $property->MaxLength <= 200) ? $property->MaxLength : 200;

		$dbdriver = DBDriverFactory::gen();
		if( $dbdriver->hasMultibyteSupport() ) { // MYSQL
			// mb_substr gets the length of string in number of characters
			$value = mb_substr( $value, 0, $maxLength, 'UTF-8' );
		} else { // Oracle & MSSQL
			// mb_strcut gets the length of string in bytes
			$value = mb_strcut( $value, 0, $maxLength, 'UTF-8' );
		}

		return $value;
	}
	
	/**
	 * To update modifier and modified fields of an Object given the Object DB Id.
	 *
	 * @param int $id DB id of the object to be updated.
	 * @param string $modifier
	 * @param string $modified Modified time in datetime format.
	 * @return boolean True on sucess, False otherwise.
	 */
	static public function updateObjectModifierAndModified( $id, $modifier, $modified )
	{
		$values = array();
		$values['modifier'] = $modifier;
		$values['modified'] = $modified;
		
		return self::updateRow(self::TABLENAME, $values, " `id` = '$id'");
	}

	/**
	 * Gives a summary overview of the count of objects per type that reside in the given publication and issue.
	 * Both the object targets and the relational targets are taken into account.
	 *
	 * @param integer $issueId Issue id.
	 * @param bool $workflowObject Objects still in the workflow or in the trash can.
	 * @return array List of key-value pairs; Key: object type, Value: object count.
	 */
	static public function getObjectCountsPerType( $issueId, $workflowObject )
	{
		$dbh = DBDriverFactory::gen();
		$objectsTable = $workflowObject ? $dbh->tablename( 'objects' ) : $dbh->tablename( 'deletedobjects' );
		$targetsTable = $dbh->tablename( 'targets' );
		$objRelTable = $dbh->tablename( 'objectrelations' );

		// Object targets
		$sql = 'SELECT DISTINCT o.`type`, o.`id` ' .
			"FROM $objectsTable o, $targetsTable tar " .
			'WHERE ( o.`id` = tar.`objectid` AND tar.`issueid` = ? ) ';
		$params = array( $issueId );
		$sth = $dbh->query( $sql, $params );

		$objIdType = array();
		if ( $sth ) {
			while ( ($row = $dbh->fetch( $sth )) ) {
				$objIdType[$row['id']] = $row['type'];
			}
		}

		// Relational targets
		$sql = 'SELECT DISTINCT o.`type`, o.`id` ' .
			"FROM $objectsTable o, $targetsTable tar, $objRelTable rel " .
			'WHERE  ( o.`id` = rel.`child` AND rel.`id` = tar.`objectrelationid` AND tar.`issueid` = ? ) ';
		$params = array( $issueId );
		$sth = $dbh->query( $sql, $params );

		if ( $sth ) {
			while ( ($row = $dbh->fetch( $sth )) ) {
				$objIdType[$row['id']] = $row['type'];
			}
		}

		$countPerType = array();
		if ( $objIdType ) foreach ( $objIdType as $type ) {
			if ( isset($countPerType[$type]) ) {
				$countPerType[$type] += 1;
			} else {
				$countPerType[$type] = 1;
			}
		}

		return $countPerType;
	}

    /**
	 * Returns all objects matching the specified types.
	 *
	 * @static
	 * @param string $objectTypes Object types delimited by comma.
	 * @return null|array $ret The result set.
	 */
	static public function getByTypes($objectTypes){
		$ret = array();
		$dbh = DBDriverFactory::gen();
		$tableName = $dbh->tablename(self::TABLENAME);

		$sql = "SELECT * FROM $tableName WHERE `type` IN ($objectTypes)";
		$sth = $dbh->query( $sql );

		if ($sth){
			while (($row = $dbh->fetch($sth))) {
				$ret[] = $row;
			}
		}
		return $ret;
	}

    /**
     * Returns all objects matching the specified mime type.
     *
     * @static
     * @param $mimeTypes
     * @return array
     */
	static public function getByMimeTypes($mimeTypes) {
		$ret = array();
		$dbh = DBDriverFactory::gen();
		$tableName = $dbh->tablename(self::TABLENAME);

		$sql = "SELECT * FROM $tableName WHERE `format` IN ($mimeTypes)";
		$sth = $dbh->query( $sql );

		if ($sth){
			while (($row = $dbh->fetch($sth))) {
				$ret[] = $row;
			}
		}
		return $ret;
	}

	/**
	 * Updates a row in the database.
	 *
	 * @static
	 * @param $row
	 * @return bool
	 */
	static public function update($row){
		$id = intval($row['id']);
		unset($row['id']);

		$where = ' `id` = ? ';
		$params[] = $id;

		if( self::updateRow( self::TABLENAME, $row, $where, $params) ) {
			return true;
		}
		return false; // failed
	}

	/**
	 * Returns ObjectInfo (data object) and Version property for a list of objects ($objIds).
	 * This is a preparation step to build a Relation (data object) from the returned info.
	 *
	 * @param string[] $objIds
	 * @param boolean $deletedOnes FALSE to read from workflow or TRUE to read from trash can.
	 * @return array Structure: array( <object id> => array( 'ObjectInfo' => <ObjectInfo data>, 'Version' => <major.minor> ) )
	 */
    static public function getObjectsPropsForRelations( $objIds, $deletedOnes = false )
    {
    	require_once BASEDIR.'/server/dbclasses/DBVersion.class.php';
    	
    	$tablename = $deletedOnes ? 'deletedobjects' : 'objects';
    	$rows = array();
    	if( count($objIds) > 0 ) {
    		$objIds = array_map( 'intval', $objIds ); // cast all ids to integers to block SQL injection
    		$where = '`id` IN ('.implode(',',$objIds).')';
    		$fields = array( 'id', 'type', 'name', 'format', 'storename', 'majorversion', 'minorversion' );
    		$rows = self::listRows( $tablename, 'id', '' , $where, $fields );
    	}
    	$retVal = array();
    	if( $rows ) foreach( $rows as $objId => $row ) {
    		$retVal[$objId] = array(
    			'ID'      	=> $objId,
    			'Name'    	=> $row['name'],
    			'Type'    	=> $row['type'],
    			'Format'  	=> $row['format'],
    			'Version' 	=> DBVersion::joinMajorMinorVersion( $row ),
    			'StoreName'	=> $row['storename'],
    		);
    	}
    	return $retVal;
    }

	/**
	 * This method returns a column value by its name.
	 *
	 * The returned value is always a string. So when requesting for special data types or custom properties,
	 * be aware that the raw value is returned.
	 *
	 * @static
	 * @param integer $id The id of the Object for which to get the column value.
	 * @param string $area 'Workflow' or 'Trash' where the object resides.
	 * @param string $columnName The name of the column for which the value is requested.
	 * @return String The column value.
	 */
	static public function getColumnValueByName( $id, $area = 'Workflow', $columnName )
	{
		$result = null;
		$dbDriver = DBDriverFactory::gen();
		$dbo = ($area == 'Workflow') ? $dbDriver->tablename( self::TABLENAME ) : $dbDriver->tablename( 'deletedobjects' );
		$sql = 'SELECT o.`' . $columnName . '` FROM ' . $dbo . ' o WHERE `id` = ' . $id;

		$sth = $dbDriver->query($sql);
		$currRow = $dbDriver->fetch($sth);

		if ($currRow) {
			$result = $currRow[$columnName];
		}

		return $result;
	}

	/**
	 * This method returns an array of column values by its name for one or more ids
	 *
	 * The returned value is always an array of strings. So when requesting for
	 * special data types or custom properties, be aware that the raw value is returned.
	 *
	 * @param array $ids The ids of the Objects for which to get the column values.
	 * @param string $area 'Workflow' or 'Trash' where the object resides.
	 * @param string $columnName The name of the column for which the values are requested.
	 * @return array The column values for each requested id.
	 */
	static public function getColumnValueByNameForIds( $ids, $area = 'Workflow', $columnName )
	{
		$tableName = ($area == 'Workflow') ? self::TABLENAME : 'deletedobjects';
		$where = '`id` IN (' . implode( ',', $ids ) . ')';
		$rows = self::listRows( $tableName, 'id', '', $where, $columnName );

		$results = array();
		if( !self::hasError() ) {
			if( $rows ) foreach( $rows as $row ) {
				$results[] = $row[$columnName];
			}
		}

		return $results;
	}

	/**
	 * Retrieves values of column names from smart_objects and/or smart_deletedobjects table.
	 *
	 * @param integer[] $objectIds The object ids for retrieve values for.
	 * @param string[] $areas Where to search in: 'Workflow' (smart_objects) and/or 'Trash' (smart_deletedobjects).
	 * @param string[] $columnNames The names of the columns to retrieve values for.
	 * @return array
	 */
	static public function getColumnsValuesForObjectIds( $objectIds, $areas, $columnNames )
	{
		$results = array();
		if( $objectIds && $areas && $columnNames ) {
			foreach( $areas as $area ) {
				$tableName = ( $area == 'Workflow' ) ? self::TABLENAME : 'deletedobjects';
				$where = '`id` IN ('.implode( ',', $objectIds ).')';
				$objRows = self::listRows( $tableName, 'id', '', $where, $columnNames );
				if( $objRows ) foreach( $objRows as $objectId => $objRow ) {
					$results[ $objectId ] = $objRow;
				}
			}
		}
		return $results;
	}

	/**
	 * Updates values on an Object.
	 *
	 * @param int $id DB id of the object to be updated.
	 * @param string[] $values, an array with the column name as key, and the new value as value.
	 * @param string $area Whether to use the normal Objects table or the DeletedObjects table.
	 * @return boolean Whether or not the operation was succesful.
	 */
	static public function updateRowValues( $id, $values, $area = 'Workflow')
	{
		$tableName = ($area == 'Workflow') ? self::TABLENAME : 'deletedobjects';
		$where = ' `id` = ?';
		$params = array( $id );
		return self::updateRow($tableName, $values, $where, $params);
	}

	/**
	 * Retrieves the ID of an Object belonging to the specified DocumentId.
	 *
	 * Returns null if there is no matching Object found.
	 * @param string $area The Area in which to search, `Workflow` or `Trash`.
	 * @param string $documentId The DocumentId for which to search the ObjectId.
	 * @param string $objectType The type of the object.
	 * @return null|int The Object ID or null if not found.
	 */
	static public function getObjectIdByDocumentId( $area, $documentId, $objectType )
	{
		$result = null;
		$where = '`documentid` = ? AND `type` = ? ';
		$params = array( $documentId, $objectType);
		$tableName = ( $area == 'Workflow' ) ? self::TABLENAME : 'deletedobjects';

		$row = self::getRow( $tableName, $where, array('id'), $params);
		if ($row) {
			$result = $row['id'];
		}
		return $result;
	}

	/**
	 * Checks if the list of object ids in $objIds belong to the same Object Type and Publication.
	 *
	 * @param array $objIds List of Object Ids to check for their object type and publication.
	 * @return bool Whether the ids in $objIds all belong to the same object Type and Publication.
	 */
	static public function isSameObjectTypeAndPublication( $objIds )
	{
		$result = false;
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename( self::TABLENAME );
		$sql = "SELECT COUNT(o.`type`) AS `totalcount` FROM ";
		$sql .= "(SELECT DISTINCT `type`, `publication` FROM $dbo ";
		$sql .= "WHERE `id` IN (".implode( ',', $objIds ).") ) o";
		$sth = $dbDriver->query($sql);
		$row = $dbDriver->fetch($sth);

		if ( $row ) {
			if( $row['totalcount'] == 1 ) {
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * Returns the number of objects that are in a certain state.
	 *
	 * @param integer $statusId State Id
	 * @return int number of objects in the specified state.
	 * @throws BizException
	 */
	static public function getNumberOfObjectsByStatus( $statusId )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbo = $dbDriver->tablename( self::TABLENAME );
		$sql = 'SELECT COUNT(*) as `cnt` FROM '.$dbo.' WHERE `state` = ? ';
		$params = array( $statusId );
		$sth = $dbDriver->query($sql, $params );
		$row = $dbDriver->fetch($sth);
		
		return $row ? $row['cnt'] : 0;		
	}

	/**
	 * Selects an object thereby filtering on the name of the object, brand and issue. Also the the object type is used
	 * to filter. Optionally the name of the channel can be used as filter option.
	 *
	 * @param string $objectName
	 * @param string $objectType
	 * @param string $brandName
	 * @param string $issueName
	 * @param string $channelName (optional)
	 * @return array|boolean Array with the row or false if not found.
	 */
	static public function getObjectByTypeAndNames( $objectName, $objectType, $brandName, $issueName, $channelName = '' )
	{
		$dbDriver = DBDriverFactory::gen();
		$odb = $dbDriver->tablename( 'objects' );
		$tdb = $dbDriver->tablename( 'targets' );
		$pdb = $dbDriver->tablename( 'publications' );
		$idb = $dbDriver->tablename( 'issues' );
		$cdb = $dbDriver->tablename( 'channels' );

		$tablesSql = ", $pdb p, $idb i ";
		$where = "WHERE o.`name` = ? AND o.`type` = ? AND
					o.`publication` = p.`id` AND p.`publication` = ? AND 
					t.`issueid` = i.`id` AND i.`name` = ? ";
		$params = array( $objectName, $objectType, $brandName, $issueName );
		if ( $channelName ) {
			$tablesSql .= ", $cdb c ";
			$where .= "	AND i.`channelid` = c.`id` AND c.`name` = ? ";
			$params[] = $channelName;
		}
		$verFld = $dbDriver->concatFields( array( 'o.`majorversion`', "'.'", 'o.`minorversion`' ) ).' as "version"';
		$sql = "SELECT o.`id`, o.`name`, o.`storename`, $verFld ";
		$sql .= "FROM $odb o ";
		$sql .= "LEFT JOIN $tdb t ON (o.`id` = t.`objectid`) ";
		$sql .= $tablesSql;
		$sql .= $where;

		$sth = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch( $sth );

		return $row;
	}

	/**
	 * Returns the documentid of a publish form template that uses a specified property.
	 *
	 * @since 10.1.2
	 * @param string $propertyName
	 * @return string
	 */
	static public function getDocumentIdOfPublishFormTemplateUsedByProperty( $propertyName )
	{
		$dbh = DBDriverFactory::gen();
		$objects = $dbh->tablename( self::TABLENAME );
		$properties = $dbh->tablename( 'properties' );
		$result = null;
		$sql = 'SELECT o.`documentid` '.
				 'FROM '.$objects.' as o, '.$properties.' as p '.
				 'WHERE p.`name` = ? '.
				 'AND o.`id` = p.`templateid` ';

		$params = array( strval( $propertyName ) );
		$sth = $dbh->query( $sql, $params );
		if( $sth ) {
			$row = $dbh->fetch( $sth );
			$result = $row['documentid'];
		}

		return $result;
	}

}
