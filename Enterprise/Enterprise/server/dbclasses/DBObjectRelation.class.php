<?php
/**
 * Implements DB querying objects relations.
 * 
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
require_once BASEDIR.'/server/dbintclasses/ObjectRelation.class.php';

class DBObjectRelation extends DBBase
{
	const TABLENAME = 'objectrelations';
	const KEYCOLUMN = 'id'; //Normaly 'id'
	const DBINT_CLASS = 'WW_DBIntegrity_ObjectRelation';
		
	/**************************** Insert ******************************************/

	/**
	 * Inserts records with the new values for passed columns.
	 *
	 * @static
	 * @param array $newValues Column/value pairs of the columns to be inserted.
	 * @param bool $autoIncrement Apply auto increment for primary key (true/false).
	 * @return bool|int
	 */
	public static function insert( array $newValues, $autoIncrement )
	{
		return parent::doInsert( self::TABLENAME, self::DBINT_CLASS, $newValues, $autoIncrement, false );
	}		

	/**
	 * Creates a new ObjectRelation.
	 *
	 * @static
	 * @param integer $parent The ID of the Parent Object.
	 * @param integer $child The ID of the Child Object.
	 * @param string $relType The Type of the ObjectRelation.
	 * @param string $childType The Type of the Child Object.
	 * @param string $pageRange Contains the page range.
	 * @param string $rating The rating for the ObjectRelation.
	 * @param string $parentType The Type of the Parent Object.
	 * @return integer|bool The ID of the created ObjectRelation or false if unsuccesful.
	 */
	public static function createObjectRelation( $parent, $child, $relType, $childType, $pageRange, $rating, $parentType )
	{
		// Ensure pagerange is DB savvy:
    	$dbDriver = DBDriverFactory::gen();
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		$pageRange = UtfString::truncateMultiByteValue( $pageRange, 50 );

		// Zerofy rating when no or bad value is given.
		if( !is_numeric($rating) ) {
			$rating = 0;
		}

		// Create object relation record at DB.
		$newValues = array(
				'parent'    => $parent,
				'child'     => $child,
				'type'      => $relType,
				'subid'     => $childType,
				'pagerange' => strval( $pageRange ),
				'rating'    => $rating,
				'parenttype' => $parentType);
		$result = self::insert( $newValues, true );
		return $result ? $result : null;
	}

	/**************************** Update ******************************************/
	/**
	 * Updates records with the new values for passed columns.
	 *
	 * @static
	 * @param array $whereParams column/array of value pairs for where clause.
	 * @param array $newValues column/value pairs of the columns to be updated.
	 * @return integer|null Number of records updated or null in case of error.
	 */
	public static function update(array $whereParams, array $newValues)
	{
		$params = array();
		$where = self::makeWhereForSubstitutes($whereParams, $params);
		return self::updateRow(self::TABLENAME, $newValues, $where, $params);
	}	
	
	/**
	 * Deletes Object Relations and Relational Placements (to Trash Can).
	 *
	 * @since 9.7.0
	 * @param int $objectId Object id of parent or child.
	 * @return bool
	 */
	static public function deleteObjectRelations( $objectId )
	{
		// Delete relations where object is PARENT.
		if( !self::deleteOrRestoreObjectRelations( $objectId, true, false ) ) {
			return false; 
		}
		// Delete relations where object is CHILD.
		if( !self::deleteOrRestoreObjectRelations( $objectId, false, false ) ) {
			return false; 
		}
		return true;
	}

	/**
	 * Restores Object Relations and Relational Placements (from Trash Can).
	 *
	 * @since 9.7.0
	 * @param int $objectId Object id of parent or child.
	 * @return bool
	 */
	static public function restoreObjectRelations( $objectId )
	{
		// Restore relations where object is PARENT.
		if( !self::deleteOrRestoreObjectRelations( $objectId, true, true ) ) {
			return false; 
		}
		// Restore relations where object is CHILD.
		if( !self::deleteOrRestoreObjectRelations( $objectId, false, true ) ) {
			return false; 
		}
		return true;
	}
	
	/**
	 * Restores or deletes Object Relations and Relational Placements from/to the Trash Can.
	 *
	 * @since 9.7.0 Before, this was a public function named updateObjectRelation
	 * @param int $objectId Object id of parent or child.
	 * @param bool $isParent TRUE when $objectId refers to parent, FALSE when refers to child.
	 * @param bool $restore TRUE to restore the relation, FALSE to delete the relation.
	 * @return bool
	 */
    static private function deleteOrRestoreObjectRelations( $objectId, $isParent, $restore )
	{
		// Delete/Restore object relations.
		if( !self::deleteOrRestoreObjectRelationsForTable( $objectId, $isParent, self::TABLENAME, $restore ) ) {
			return false;
		}
		// Delete/Restore relational placements.
		if( !self::deleteOrRestoreObjectRelationsForTable( $objectId, $isParent, "placements", $restore ) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Returns object relation based on the Relation Db id given.
	 * @param int $relId Relation id, the Db id for the record to be retrieved.
	 * @throws BizException Throws BizException when no record found.
	 * @return Relation
	 */
	static public function getObjectRelationByRelId( $relId )
	{
		$where = 'id = ?';
		$params = array( $relId );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( !$row ) {		
			throw new BizException( 'ERR_DATABASE', 'Server', 'Unknown relation Db id requested: '.$relId );
		}
		return self::rowToObj( $row );		
	}
	
	// Update the relation's pagerange field:
	static public function updateObjectRelationPageRange( $parent, $child, $pageRange )
	{
		// Ensure pagerange is DB savvy:
		require_once BASEDIR.'/server/utils/UtfString.class.php';
		$pageRange = UtfString::truncateMultiByteValue( $pageRange, 50 );
		
		$whereParams = array('parent' => array($parent), 'child' => array($child));
		$result = self::update($whereParams, array('pagerange' => $pageRange));
		if ( !$result ) {
			return $result;
		}
		return true;
	}
	
	/**
	 * Restores or deletes Object Relations -OR- Relational Placements from/to the Trash Can.
	 *
	 * @since 9.7.0 Before, this function was named updateObjectRelationTable
	 * @param int $objectId Object id of parent or child.
	 * @param bool $isParent TRUE when $objectId refers to parent, FALSE when refers to child.
	 * @param string $table DB tablename to update: 'relations' or 'placements'
	 * @param bool $restore TRUE to restore the relation, FALSE to delete the relation.
	 * @return bool
	 */
	private static function deleteOrRestoreObjectRelationsForTable( $objectId, $isParent, $table, $restore )
	{
    	$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename($table);

		$where = $isParent ? '`parent`= ?' : '`child`= ?';
		$params = array( intval( $objectId ) );

		// Restoring type from 'deleted' to original relation type?
		if( $restore ) {
			// Check whether the related id is also present in the objects table
			// In other words: a relation can only be restored in case
			// both parent and child exists in the objects table.
			$ids = array();
			if( $isParent ) {
				// Looking for a certain parent: check whether child is present in the objects table.
				$sql = "SELECT `id`, `child` FROM $db WHERE $where";
			} else {
				// Looking for a certain child: check whether parent is present in the objects table.
				$sql = "SELECT `id`, `parent` FROM $db WHERE $where";
			}
			$sth = $dbDriver->query( $sql, $params );
			if( !$sth ) {
				return false;
			}
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			while( ($row = $dbDriver->fetch($sth)) ) {
				$otherObjectId = $isParent ? $row['child'] : $row['parent'];
				if( DBObject::objectExists( $otherObjectId, 'Workflow' ) ) {
					$ids[] = $row['id'];
				}
			}

			if( !$ids ) {
				LogHandler::Log( 'DBObjectRelation', 'DEBUG', "deleteOrRestoreObjectRelationsForTable: nothing to do" );
				return true; // nothing to do
			}
			$where = '`id` IN ('.implode( ',', $ids ).')';
			$params = array();
		}

		$objectRelationTypes = array( 'Placed', 'Planned', 'Contained', 'Related' );
		foreach( $objectRelationTypes as $ort ) {
			if ( $restore ) {
				$sql = "UPDATE $db SET `type`='$ort' WHERE $where AND `type`='Deleted$ort'";
			} else {
				$sql = "UPDATE $db SET `type`='Deleted$ort' WHERE $where AND `type`='$ort'";
			}
			$sth = $dbDriver->query( $sql, $params );
			if( !$sth ) {
				return false;
			}
		}
		return true;
	}
		
	/**************************** Delete ******************************************/
	/**
	 * Deletes records .....  
	 * @param array $whereParams column/array of value pairs for where clause
	 * @return number of records updated or null in case of error.
	 */	
	protected static function delete($whereParams)
	{
		return parent::doDelete($whereParams, self::TABLENAME, self::KEYCOLUMN, self::DBINT_CLASS);
	}    
    
	/**
	 * Removes all rows from the targets- and targeteditions-table identified by $parent/$child.
	 * 
	 * Note that this function permanently deletes (purges) the object relations. 
	 * See {@link: deleteObjectRelations()} to send relations to the Trash Can instead.
	 *
	 * Supports the following combinations of parent-child parameters:
	 * - parent = id, child = null                => delete all childrelations from id as parent
	 * - parent = id, child = id, parent <> child => delete specific parent-child relation
	 * - parent = id, child = id, parent == child => delete all relations to parents and childs
	 *
	 * @param integer $parent Id of parent object.
	 * @param integer|null $child Id of child object.
	 * @param string|null $type Object relation type.
	 * @return boolean|null TRUE on success, NULL on error.
	 */
	static public function deleteObjectRelation( $parent, $child, $type = null )
	{
		$whereParam1 = array();
		if ($type) {
			$whereParam1 = array('type' => array($type));
		}

		if ($child) {
			if ($parent == $child) {
				$whereParam2 = array('parent' => array($parent));
				$whereParams = array_merge($whereParam1, $whereParam2);
				$result = self::delete($whereParams);
				if ($result === null) return null;
				$whereParam2 = array('child' => array($child));
				$whereParams = array_merge($whereParam1, $whereParam2);
				$result = self::delete($whereParams);
				if ($result === null) return null;
			} else {
				$whereParam2 = array('parent' => array($parent), 'child' => array($child));
				$whereParams = array_merge($whereParam1, $whereParam2);
				$result = self::delete($whereParams);
				if ($result === null) return null;
			}
		}
		else {
			$whereParam2 = array('parent' => array($parent));
			$whereParams = array_merge($whereParam1, $whereParam2);
			$result = self::delete($whereParams);
			if ($result === null) return null;
		}

		require_once BASEDIR.'/server/dbclasses/DBPlacements.class.php';
		$result = DBPlacements::deletePlacements( $parent, $child, $type );
		if ($result === null) return null;
		
		return true;
	}


	/**************************** Query *******************************************/
	/**
	 * @param $id
	 * @param bool|true $childs
	 * @param null $type
	 * @param bool|false $alsoDeletedOnes
	 * @return Resource
	 * @throws BizException
	 * @deprecated; use getObjectRelations instead!
	 */
	static public function getObjectRelation( $id, $childs = true, $type = null, $alsoDeletedOnes = false )
	{
    	$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);

		if ($childs) {
			$sql = "SELECT `id`, `child`, `type`, `subid`, `pagerange`, `rating` FROM $db WHERE `parent` = ? ";
		} else {
			$sql = "SELECT `id`, `parent`, `type`, `subid`, `pagerange`, `rating` FROM $db WHERE `child` = ? ";
		}
		$params = array( intval( $id ) );
		// Never return relations that are marked as 'deleted'.
		if ($type) {
			$sql .= " AND `type` = ? ";
			$params[] = strval( $type );
		} elseif ( !$alsoDeletedOnes ) {
			$sql .= " AND `type` NOT LIKE 'Deleted%'";
		}
		$sth = $dbDriver->query($sql, $params );

		return $sth;
	}

	/**
	 * Returns relations that are made between two workflow objects.
	 * When parents are requested ($related='parents'), the given base object ($id) is assumed to be the child.
	 * When childs are requested ($related='childs'), the given base object ($id) is assumed to be the parent.
	 * When both are requested ($related='both'), the given base object ($id) is assumed to be the child or the parent.
	 *
	 * @param integer|integer[] $id The object id(s) used as base; either parent(s) or child(s)
	 * @param string $related The relational type which implies how the $id is used; 'parents', 'childs' or 'both'
	 * @param string $type The RelationType; Can be 'Contained', 'Placed', 'Planned', 'Related', 'Candidate', 'DeletedContained', 'DeletedPlaced', 'DeletedPlanned', 'DeletedRelated', 'DeletedCandidate'
	 * @param boolean $alsoDeletedOnes Wether or not deleted relations should be included. Default false.
	 * @return array of DB relation rows. Keys are the 'id' fields and values are the rows containing all fields.
	 * @throws BizException when bad params provided.
	 */
	static public function getObjectRelations( $id, $related, $type = null, $alsoDeletedOnes = false )
	{
		$multiIds = is_array($id);
		$paramsIds = $multiIds ? array() : array( $id );

		$typeWhere = '';
		$paramsType = array();
		if( $type ) {
			$paramsType[] = $type;
			if( $alsoDeletedOnes === true ) {
				$typeWhere .= " AND (`type` = ? OR `type` = ?) ";
				$paramsType[] = "Deleted$type";
			} else {
				$typeWhere .= " AND (`type` = ?) ";
			}
		} else if( $alsoDeletedOnes === false ) {
			$typeWhere .= " AND (`type` NOT LIKE 'Deleted%') ";
		}

		switch( $related ) {
			case 'parents':
				if( $multiIds ) {
					$idsCsv = implode( ',', $id );
					$where = "(`child` IN( $idsCsv )) ";
				} else {
					$where = "(`child` = ?) ";
				}
				$where .= $typeWhere;
				$params = array_merge( $paramsIds, $paramsType);
				break;
			case 'childs':
				if( $multiIds ) {
					$idsCsv = implode( ',', $id );
					$where = "(`parent` IN( $idsCsv )) ";
				} else {
					$where = "(`parent` = ?) ";
				}
				$where .= $typeWhere; 
				$params = array_merge( $paramsIds, $paramsType);
				break;
			case 'both':
				// In case the object can be both parent or child the filter on the type is added twice.
				// Instead of (`parent` = X OR `child` = X) AND (`type` = 'Y'), which is shorter, the where clause is
				// (`parent` = X AND (`type` = 'Y')) OR (`child` = X AND (`type` = 'Y')). The reason is that this is
				// much faster because the dbms now knows which indexes to choose.
				if( $multiIds ) {
					$idsCsv = implode( ',', $id );
					$where = "(`parent` = IN( $idsCsv )$typeWhere) OR (`child` = IN( $idsCsv )$typeWhere) ";
					$params = array_merge( $paramsIds, $paramsType, $paramsIds, $paramsType);
				} else {
					$where = "(`parent` = ?$typeWhere) OR (`child` = ?$typeWhere) ";
					$params = array_merge( $paramsIds, $paramsType, $paramsIds, $paramsType);
				}
				break;
			default:
				throw new BizException( 'ERR_DATABASE', 'Server', 'Unknown relation requested: '.$related );
		}


		return self::listRows( self::TABLENAME, 'id', null, $where, '*', $params);
	}
	
	/**
	 * Returns the DB id of a workflow object relation.
	 *
	 * @param integer $parent Object id of parent
	 * @param integer $child Object id of child
	 * @param string $type The RelationType; Can be 'Contained', 'Placed', 'Planned', 'Related', 'Candidate', 'DeletedContained', 'DeletedPlaced', 'DeletedPlanned', 'DeletedRelated', 'DeletedCandidate'
	 * @return int|null The record id when found. Null when not found.
	 */
	static public function getObjectRelationId( $parent, $child, $type )
	{
		$where = "parent = ? AND child = ? AND `type` = ? ";
		$params = array($parent, $child, $type);
		$fields = array('id' => 'id');
		$result = self::getRow( self::TABLENAME, $where, $fields, $params );
		return is_null($result) ? null : $result['id'];
	}

	/**
	 * Returns the DB id and page range of a workflow object relations.
	 *
	 * @param Relation[] $relations
	 * @return null|array For each parent/child combinations an infoObj containing the Id and the PageRange.
	 */
	static public function getObjectRelationInfoOfRelations( $relations )
	{
		$params = array();
		$wheres = array();
		foreach( $relations as $relation ) {
			$wheres[] = '( `parent`= ? AND `child`= ? AND `type` = ? ) ';
			$params[] = $relation->Parent;
			$params[] = $relation->Child;
			$params[] = $relation->Type;
		}
		$where = '('.implode( ' OR ', $wheres ).') ';
		$rows = self::listRows( self::TABLENAME, 'id', '', $where, array( 'id', 'parent', 'child', 'pagerange' ), $params );
		$result = array();
		if( $rows ) foreach( $rows as $row ) {
			$infoObj = new stdClass();
			$infoObj->Id = $row['id'];
			$infoObj->PageRange = $row['pagerange'];
			$result[$row['parent']][$row['child']] = $infoObj;
		}

		return $result;
	}

	/**
	 * Returns unplaced adverts for a certain layout within the specified publication/
	 * issue/section.
	 * Note: Method is not used internally at the moment.
	 * @param integer $lay_id
	 * @param integer $publication id
	 * @param integer $issue id
	 * @param integer $section section id
	 * @return array|boolean Array with ids of unplaced adverts, false in case of an error.
	 */
	static public function unplacedAdverts( $lay_id, $publication, $issue, $section )
	{
    	$dbDriver = DBDriverFactory::gen();
		$db1 = $dbDriver->tablename("objects");
		$db2 = $dbDriver->tablename(self::TABLENAME);

		$sql = "SELECT `id` FROM $db1 WHERE `type` = ? AND `publication` = ? AND `issue` = ? AND (`section` = ? or `section` = ? )";
		$params = array( 'Advert', intval( $publication), intval( $issue ), intval( $section ), 0 );
		$sth = $dbDriver->query( $sql, $params );
		if (!$sth) return false;

		$arr = array();
		while (($row = $dbDriver->fetch($sth))) {
			// simultate not exist-clause to be db independent
			// ...and not exists (select * from smart_objectrelations as r where r.child = o.id and r.type = 'Placed')
			$adv_id = $row["id"];
			$sql = "SELECT 1 FROM $db2 WHERE ";
			// (`type` = 'Placed' and `child` = $adv_id) or `parent` = $lay_id or (`child` = $adv_id and `parent` != $lay_id) ";
			$sql .= "(`type` = 'Placed' AND `child` = ? ) OR ";
			$sql .= "(`parent` = ? AND `type` NOT LIKE 'Deleted%') OR ";
			$sql .= "(`child` = ? AND `parent` != ? AND `type` NOT LIKE 'Deleted%' ) ";
			$params = array( intval( $adv_id ), intval( $lay_id ), intval( $adv_id ), intval( $lay_id ) );
			$sth2 = $dbDriver->query($sql, $params );
			if (!$sth2) return false;
			$row2 = $dbDriver->fetch($sth2);
			if (!$row2)
				$arr[] = $adv_id;
		}
		return $arr;
	}	
	
	/**
	 * Filter out duplicate placements with other channels than the relation (BZ#17837).
	 * See DBPlacements::getDuplicatePlacements() which is called to pass in the placement ($rows)
	 * which -could- be duplicate. It could contain too many placements, which are filtered out here.
	 * 
	 * When 'all' editions are used, InDesign passes in 'null' for the edition of the placement 
	 * relation and so zero (0) gets stored at the DB. But in this context, 'all' editions are the
	 * ones of the layout (and not all editions configured for the channel!). Therefore, whenever
	 * this function finds an edition id set to zero, it should lookup the layout's editions to see
	 * what 'all' actually means. (For object relations, the layout is the parent.) This needs to be
	 * done for the parent of the passed in $relation, but also for 'other' parents. That are layouts
	 * one which the article/image is also placed.
	 *
	 * Without these logics in place, too often the duplicate edition error was raised; Because of the
	 * wrong interpretation of 'all' editions, an article placed on N-Z edition, while the layout has 
	 * been targetted to N-Z only, InDesign indicates 'all' editions. When the article is placed on
	 * on another layout for edition East only, comparing 'all' with East raises the error unexpectedly
	 * See BZ#19595 for more info and scenarios.
	 * 
	 * @param array $rows result rows from DBPlacements::getDuplicatePlacements
	 * @param Relation $relation
	 * @return array
	 */
	public static function filterDuplicatePlacements( $rows, Relation $relation )
	{
		$duplicatesPlacements = array();
		$parentEditionIds = array();
		if( count($rows) > 0 ) {
		
			// Collect relational target editions.
			$channels = array();
			if( $relation->Targets ) foreach( $relation->Targets as $target ) {
				$channelId = intval($target->PubChannel->Id);
				$channels[$channelId] = $channelId;
				if( $target->Editions ) foreach( $target->Editions as $edition ) {
					$parentEditionIds[] = $edition->Id;
				}
			}
			
			// When no relational editions, collect the parent object editions instead. (BZ#19595)
			if( !$parentEditionIds ) {
				$parentEditions = DBTarget::getObjectEditions( $relation->Parent );
				foreach( $parentEditions as $edition ) {
					$parentEditionIds[] = $edition->Id;
				}
			}

			// Collect object ids of 'other' parents that are targetted to the same editions,
			// and therefore could cause duplicate edition conflicts for their placements.
			$parentIds = array();
			foreach( $rows as $key => $row ) {
			
				// Collect all editions of the other parent. (BZ#19595)
				$otherParentEditionIds = array();
				if( $row['edition'] ) {
					$otherParentEditionIds[] = $row['edition'];
				} else { // When row edition=0, get row parent object all editions instead
					$otherParentEditions = DBTarget::getObjectEditions( $row['parent'] ); // Get the parent object editions
					if( $otherParentEditions ) foreach( $otherParentEditions as $edition ) {
						$otherParentEditionIds[] = $edition->Id;
					}
				}
				
				// Include the 'other' parent (id), when any editions are the same.or both have no editions.
				// Else, exclude the placement to avoid unwanted duplicate edition errors.
				if( array_intersect( $otherParentEditionIds, $parentEditionIds ) || // Has same edition(s)?
					( empty($otherParentEditionIds) && empty($parentEditionIds) ) ) {  // Parent and other have no editions BZ#30871
					$parentIds[$row['parent']] = $row['parent']; // Include the 'other' parent.
				} else { // None of the ediions are the same.
					unset( $rows[$key] ); // Exclude the placement. (BZ#19595)
				}
			}

			// No common parents so early return. 
			// We do not check on channels as targets must have channels.
			// Early return on channels would obfuscate missing data.
			if ( empty( $parentIds ) ) { 
				return $duplicatesPlacements;
			}

			// Placing is about relational targets, so only check the placed relational targets
			// get the parents with the same channel as given parent channel(s).
			$dbDriver = DBDriverFactory::gen();
			$targetsTable = $dbDriver->tablename( 'targets' );
			$relationsTable = $dbDriver->tablename( self::TABLENAME );
			$sql = 'SELECT rel.`parent` '
				. "FROM $relationsTable rel "
				. "INNER JOIN $targetsTable tar ON (rel.`id` = tar.`objectrelationid`) "
				. 'WHERE rel.`parent` IN (' . implode(',', $parentIds) . ') '
				. 'AND rel.`child` = ? '
				. 'AND rel.`type` = ? ';
            if( $channels ) {
            	$sql .= 'AND tar.`channelid` IN (' . implode(',', $channels) . ') ';
            }
			$sth = $dbDriver->query( $sql, array($relation->Child, 'Placed') );
			$sameChannelParents = self::fetchResults( $sth, 'parent' );
			
			// Return only rows with parents that have the same channel.
			foreach( $rows as $row ) {
				if( isset($sameChannelParents[$row['parent']]) ) {
					$duplicatesPlacements[] = $row;
				}
			}
		}
		
		return $duplicatesPlacements;
	}	

	/**
	 * Checks if an object, used as child, has more than $manifold relations.
	 * It is not about 'related' relations but especially  'placed' and 'contained'
	 * relations.
	 * @param int 		$childId 	Object id of the child.
	 * @param int 		$manifold	Total number of relations.
	 * @return boolean	Total number of relations exceeds $manifold (true/false). 
	 */
	public static function childPlacedManifold( $childId, $manifold)
	{
    	$dbDriver = DBDriverFactory::gen();
		$objrel = $dbDriver->tablename(self::TABLENAME);
		
		$sql  = "SELECT `child` ";
		$sql .= "FROM $objrel ";
		$sql .= "WHERE `child` = ? ";
		$sql .= "AND `type` <> 'Related' ";
		$sql .= "GROUP BY `child` ";
		$sql .= "HAVING COUNT(1) > $manifold ";
		$params = array( intval( $childId ) );
		
		$sth = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch($sth); 
		
		return $row ? true : false;
		
	}
	/**
	 * Checks if a list of objects, used as child, has more than $manifold relations.
	 *
	 * It is not about 'related' relations but especially  'placed' and 'contained'
	 * relations.
	 *
	 * @param array $childIds 	Array of child ids.
	 * @param int $manifold Threshold value for manifold placed objects.
	 * @return array List of child objects id of which its relations exceed $manifold relations.
	 */
	public static function childrenPlacedManifold( array $childIds, $manifold )
	{
		$manifoldUsed = array();
		if ( $childIds ) {
			$where = self::addIntArrayToWhereClause( 'child', $childIds, false );
			$where .= "AND `type` <> ? ";
			$params = array( 'Related' );
			$rows = self::listRows(
						self::TABLENAME,
						'child',
						'',
						$where,
						array( 'child' ),
						$params,
						null,
						null,
						array( 'child' ),
						"COUNT(1) > $manifold" );
			$manifoldUsed = array_keys( $rows );
		}

		return $manifoldUsed;
	}
	/**************************** Other *******************************************/	

	/**
	 * Converts a relation data object into a DB row (array).
	 *
	 * @param Relation $obj Data object
	 * @return array Relation DB row
	 */
	static public function objToRow( $obj )
	{
		$row = array();

		if( !is_null( $obj->Parent ) ) {
			$row['parent'] = intval( $obj->Parent );
		}
		if( !is_null( $obj->Child ) ) {
			$row['child'] = intval( $obj->Child );
		}
		if( !is_null( $obj->Type ) ) {
			$row['type'] = $obj->Type;
		}
		if( !is_null( $obj->Rating ) ) {
			$row['rating'] = intval( $obj->Rating );
		}
		if( isset( $obj->ParentInfo->Type ) ) {
			$row['parenttype'] = $obj->ParentInfo->Type;
		}
		if( isset( $obj->ChildInfo->Type ) ) {
			$row['subid'] = $obj->ChildInfo->Type; // subid=childtype
		}

		return $row;
	}

	/**
	 * Converts a relation DB row (array) into a data object.
	 *
	 * @param array $row Relation DB row
	 * @return Relation Data object
	 */
	static public function rowToObj( $row )
	{
		$obj = new Relation();

		if( array_key_exists( 'parent', $row ) ) {
			$obj->Parent = intval( $row['parent'] );
		}
		if( array_key_exists( 'child', $row ) ) {
			$obj->Child = intval( $row['child'] );
		}
		if( array_key_exists( 'type', $row ) ) {
			$obj->Type = $row['type'];
		}
		if( array_key_exists( 'rating', $row ) ) {
			$obj->Rating = intval( $row['rating'] );
		}
		
		// Resolve (read-only) info of parent object.
		if( array_key_exists( 'parentversion', $row ) ) {
			$obj->ParentVersion = $row['parentversion'];
		}
		if( array_key_exists( 'parenttype', $row ) ) {
			$obj->ParentInfo = new ObjectInfo();
			if( array_key_exists( 'parent', $row ) ) {
				$obj->ParentInfo->ID = intval( $row['parent'] );
			}
			$obj->ParentInfo->Type = $row['parenttype'];
			if( array_key_exists( 'parentname', $row ) ) {
				$obj->ParentInfo->Name = $row['parentname'];
			}
			if( array_key_exists( 'parentformat', $row ) ) {
				$obj->ParentInfo->Format = $row['parentformat'];
			}
		}
		
		// Resolve (read-only) info of child object.
		if( array_key_exists( 'childversion', $row ) ) {
			$obj->ChildVersion = $row['childversion'];
		}
		if( array_key_exists( 'subid', $row ) ) { // subid=childtype
			$obj->ChildInfo = new ObjectInfo();
			if( array_key_exists( 'child', $row ) ) {
				$obj->ChildInfo->ID = intval( $row['child'] );
			}
			$obj->ChildInfo->Type = $row['subid']; // subid=childtype
			if( array_key_exists( 'childname', $row ) ) {
				$obj->ChildInfo->Name = $row['childname'];
			}
			if( array_key_exists( 'childformat', $row ) ) {
				$obj->ChildInfo->Format = $row['childformat'];
			}
		}
		
		return $obj;
	}
}
