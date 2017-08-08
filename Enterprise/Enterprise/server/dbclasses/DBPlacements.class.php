<?php
/**
 * Implements DB querying of placements.
 * 
 * @package 	Enterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBPlacements extends DBBase
{
	const TABLENAME = 'placements';

	/**
	 * Creates placements for specified parent/child object
	 *
	 * Since 9.7: When a placement has InDesignArticleIds defined, another placement is 
	 * created in DB, this time with child = 0. For those records a reference is created 
	 * in the smart_indesignarticlesplacements table.
	 *
	 * @param string $parent Object ID of object on which is placed.
	 * @param string $child  Object ID of object being placed.
	 * @param string $type   Placed or Planned
	 * @param Placement $plc The placement details
	 * @return integer|boolean Placement id. False when creation failed (DB error).
	 */
	static public function insertPlacement( $parent, $child, $type, $plc )
	{
		// If child=0, we expect InDesignArticleIds.
		if( !$child && !$plc->InDesignArticleIds ) {
			LogHandler::Log( 'DBPlacements', 'ERROR', 'No InDesignArticleIds provided while child=0.' );
			return false;
		}
		
		// Whether or not a child was given, first create a placement with child=0
		// and reference it from smart_indesignarticlesplacements table.
		$placementId = false;
		if( $plc->InDesignArticleIds ) {
			// To avoid creating duplicate InDesign Article placements (e.g. in context
			// of UpdateObjectRelations, EN-86772) first check if the DB has a placement
			// for the InDesign Article with matching spline id.
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
			$createNewPlacement = false;
			$placementIdByArticleId = array();
			foreach( $plc->InDesignArticleIds as $idArticleId ) {
				$dbPlacements = DBInDesignArticlePlacement::getPlacementIdsByInDesignArticleIdAndSplineId( $parent, $idArticleId, $plc->SplineID );
				$found = false;
				if( $dbPlacements ) {
					foreach( $dbPlacements as $placement ) {
						// If the edition corresponds to this placement a link should be created.
						if( $placement['edition'] == 0 || $placement['edition'] == $plc->Edition->Id ) {
							$placementIdByArticleId[$idArticleId] = $placement['plcid'];
							$found = true;
						}
					}

				}
				if( !$found ) {
					$createNewPlacement = true;
				}
			}
			
			// When no InDesign Article placement found in DB, create a new one.
			$newPlacementId = null;
			if( $createNewPlacement ) {
				$row = self::objToRow( $plc );
				$row['parent'] = $parent;
				$row['type']   = $type;
				// InDesignArticle placements are object placements which are separated from 
				// relational placements, and therefor never have a child nor elementid set.
				$row['child']  = 0; 
				$row['elementid'] = '';
				$newPlacementId = self::insertRow( self::TABLENAME, $row );
			}

			// Create relations between the InDesign Articles and their placements.
			foreach( $plc->InDesignArticleIds as $idArticleId ) {
				if( isset( $placementIdByArticleId[$idArticleId] ) ) {
					$placementId = $placementIdByArticleId[$idArticleId];
					if( !DBInDesignArticlePlacement::inDesignArticlePlacementExists( $parent, $idArticleId, $placementId ) ) {
						DBInDesignArticlePlacement::linkInDesignArticleToPlacement( $parent, $idArticleId, $placementId );
					}
				} else {
					DBInDesignArticlePlacement::linkInDesignArticleToPlacement( $parent, $idArticleId, $newPlacementId );
				}
			}
		}

		// When child given, create one more placement, this time NOT referenced.
		// This is the object relational placement.
		if( $child ) {
			$row = self::objToRow( $plc );
			$row['parent'] = $parent;
			$row['type']   = $type;
			$row['child']  = $child;
			$placementId = self::insertRow( self::TABLENAME, $row );
		}

		return $placementId;
	}

	/**
	 * Creates InDesign Article placements for a specific parent object.
	 *
	 * Pre-condition is that no IDA-placements are yet inserted. So records are just inserted. No check is done on
	 * duplicates.
	 * Since 9.7: When a placement has InDesignArticleIds defined, another placement is
	 * created in DB, this time with child = 0. For those records a reference is created
	 * in the smart_indesignarticlesplacements table.
	 * After the placements are added, the InDesignArticle placements are added. These are also added by one statement.
	 *
	 * @since 10.1.2
	 * @param string $parent Object ID of object on which is placed.
	 * @param Placement[] $IDAPlacements The placement details
	 * @throws BizException
	 */
	static public function insertInDesignArticlePlacementsFromScratch( $parent, $IDAPlacements )
	{
		// Whether or not a child was given, first create a placement with child=0
		// and reference it from smart_indesignarticlesplacements table.
		$values = array();
		$row = array();
		if( $IDAPlacements ) foreach( $IDAPlacements as $plc ) {
			if( $plc->InDesignArticleIds ) {
					$row = self::objToRow( $plc, true );
					$row['parent'] = $parent;
					$row['type'] = 'Placed';
					$row['child'] = 0;
					$row['elementid'] = '';
					$values[] = array_values( $row );
			}
		}

		if( $values ) {
			$result = self::insertRows( self::TABLENAME, array_keys( $row ), $values );
			if( !$result ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}

		$iDAPlacementRows = self::getInDesignArticlePlacementRows( $parent, array( 'id', 'splineid') );
		$values = array();
		// Create relations between the InDesign Articles and their placements.
		if( $IDAPlacements ) foreach( $IDAPlacements as $plc ) {
			$newPlacementId = 0;
			foreach( $iDAPlacementRows as $iDAPlacementRow ) {
				if( $plc->SplineID == $iDAPlacementRow['splineid'] ) {
					$newPlacementId = $iDAPlacementRow['id'];
				}
			}
			foreach( $plc->InDesignArticleIds as $idArticleId ) {
				$row = array();
				$row['objid'] = $parent;
				$row['artuid'] = $idArticleId;
				$row['plcid'] = $newPlacementId;
				$values[] = array_values( $row );
			}
		}
		if( $values ) {
			$result = self::insertRows( 'idarticlesplacements', array_keys( $row ), $values );
			if( !$result ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}
	}

	/**
	 * Updates a placement record based on the filter of the $whereFields.
	 *
	 * @param stdClass identifier Identifies the unique placement to be updated.
	 * @param Placement Changed placement
	 */
	static public function updatePlacement( $identifier, $placement )
	{
		$whereFields = array(
			'parent' => intval( $identifier->Parent ),
			'child' => intval( $identifier->Child ),
			'type' => strval( $identifier->Type ),
			'edition' => intval( $identifier->EditionId ),
			'frameid' => strval( $identifier->FrameId )
		);
		$where = implode( ' AND ',
								array_map(
									function( $column ) { return "`{$column}` = ? "; },
									array_keys( $whereFields )
								)
							 );
		$params = array_values( $whereFields );
		$values = self::objToRow( $placement );
		DBBase::updateRow( self::TABLENAME, $values, $where, $params );
	}

	/**
	 * Copies placements for specified parent/child object
	 *
	 * @param string $fromparent Object ID (source) of object on which is placed.
	 * @param string $child      Object ID of object being placed.
	 * @param string $toparent   Object ID (target) of object on which is placed.
	 * @param integer $pageOffset Page Order number
	 * @param string $type       Placed or Planned
	 * @return boolean Whether or not insertion was successfull.
	 */
	static public function copyPlacements( $fromparent, $child, $toparent,
											/** @noinspection PhpUnusedParameterInspection */ $pageOffset = 0, $type = 'Placed' )
	{
		$placements = self::getPlacements( $fromparent, $child, $type );
		if( is_null($placements) ) {
			return false; // DB error
		}
		foreach( $placements as $plc ) {
			if( !isset( $plc->Page ) ) $plc->Page = 0;
			$placementId = self::insertPlacement( $toparent, $child, $type, $plc );
			if( !$placementId ) {
				return false; // DB error
			} else {
				if( isset($plc->Tiles) && is_array($plc->Tiles) ) {
					require_once BASEDIR.'/server/dbclasses/DBPlacementTiles.class.php';
					foreach( $plc->Tiles as $placementTile ) {
						$placementTileId = DBPlacementTiles::createPlacementTile( $placementId, $placementTile );
						if( !$placementTileId ) {
							return false;
						}
					}
				}
			}
		}
		return true; // no error
	}
		
	/**
	 * Removes placements for specified parent/child object, possibly filtered on type or/and formwidgetid.
	 *
	 * @param integer $parent Id of parent object.
	 * @param integer|null $child Id of child object.
	 * @param string|null $type Object relation type.
	 * @param string $formWidgetId Name/Identifier of the PublishForm widget.
	 * @return boolean null in case of error, true in case of succes
	 * @throws BizException when bad params provided.
	 */
	public static function deletePlacements( $parent, $child, $type = null, $formWidgetId = null )
	{
		// Validate function parameters.
		if( !$parent ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}
		
		// Compose the WHERE clause for a SQL query to search for placements in DB (to be deleted).
		$wheres = array();
		$params = array();
		if( $child && $child == $parent ) {
			$wheres[] = '`parent` = ? OR `child` = ?';
			$params[] = $parent;
			$params[] = $child;
		} else {
			$wheres[] = '`parent` = ?';
			$params[] = $parent;
			if( !is_null( $child ) ) {
				$wheres[] = '`child` = ?';
				$params[] = $child;
			}
		}
		if( $type ) {
			$wheres[] = '`type` = ?';
			$params[] = $type;
		}
		if( $formWidgetId ) {
			$wheres[] = '`formwidgetid` = ?';
			$params[] = $formWidgetId;
		}
		
		// Query the placement ids (to be deleted).
		$select = array( 'id' );
		$where = '('.implode( ') AND (', $wheres ).')'; // since parent is mandatory, we always have $wheres
		$rows = self::listRows( self::TABLENAME, null, null, $where, $select, $params );
		
		// Compose a list of placement ids.
		$placementIds = array();
		foreach( $rows as $row ) {
			$placementIds[] = $row['id'];
		}
		
		// Delete the placements from DB.
		return self::deletePlacementsByIds( $placementIds );
	}
	
	/**
	 * Removes placements and their tiles and unlinks them from InDesignArticle (if linked).
	 *
	 * @since 9.7.0
	 * @param integer[] $placementIds
	 * @return bool
	 */
	public static function deletePlacementsByIds( array $placementIds )
	{
		$retVal = true;
		if( $placementIds ) {
			$where = '`id` IN ('.implode(',',$placementIds).')';
			$retVal = self::deleteRows( self::TABLENAME, $where );

			// Cascade delete the placement's tiles.
			require_once BASEDIR.'/server/dbclasses/DBPlacementTiles.class.php';
			DBPlacementTiles::deletePlacementTiles( $placementIds );
			
			// Cascade delete the InDesignArticle placements.
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
			DBInDesignArticlePlacement::unlinkInDesignArticleToPlacementByPlacementIds( $placementIds );
		}
		return $retVal;
	}

	/**
	 * Determines if a given placement (parent/layout and child/article) is 'duplicate'.
	 * Duplicate means, the article is placed twice (or more) in the same edition (or both at no edition).
	 * This typically could be case when an article is placed twice on a different or the same layout.
	 * Elements of the article that are in different editions are *not* considered as 'duplicate'.
	 * If found, some info of the duplicates is returned. Note that the returned parent (id) could
	 * be an 'other' layout than the requested parent, in case the child/article is placed on a second layout.
	 * Related issues: BZ#9774, BZ#9937, BZ#8468, BZ#8544 and BZ#9906
	 *
	 * @param string $parentId Id of the layout we are checking the children for
	 * @param string $childId Id of the article we are checking
	 * @return array of rows with some placements fields: parent (id), edition (id) and element (name).
	 *  Empty array when no duplicates are found.
	**/
	static public function getDuplicatePlacements( $parentId, $childId )
	{
		$dbDriver = DBDriverFactory::gen();
		$placementsTable = $dbDriver->tablename( self::TABLENAME );

		// Get the elements for the placement to be checked.
		$sql  = "SELECT pa.`id`, pa.`edition`, pa.`elementid` ";
		$sql .= "FROM $placementsTable pa ";
		$sql .= "WHERE  pa.`parent` = ? ";
		$sql .= "AND pa.`type` = 'Placed' ";// Ignore Planned and other relation types.
		$sql .= "AND pa.`child` = ? ";
		$sql .= " AND pa.`frameorder` = 0 ";
		$params = array( $parentId, $childId );

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth );

		// Check if placements occur more than one time.
		$resultRows = array();
		if ( $rows )  foreach ( $rows as $row ) {
			$sql = "SELECT pb.`parent`, pb.`edition`, pb.`element` ";
			$sql .= "FROM $placementsTable pb ";
			$sql .= "WHERE pb.`elementid` = ? ";
			$sql .= "AND pb.`type` = 'Placed' "; // Ignore Planned and other relation types.
			$sql .= "AND pb.`frameorder` = 0 "; // Only include the beginning of stories (start with frame 0).
			$sql .= "AND pb.`id` <> ? "; // Avoid matching same record.
			$sql .= "AND pb.`child` = ? "; // Exclude InDesignArticle placements.
			$sql .= "AND ( pb.`edition` = 0 OR pb.`edition` = ? ) "; // Suppress one placed in North and other in South, which is no duplicate!
			$sql .= "GROUP BY pb.`parent`, pb.`edition`, pb.`element` "; // Suppress returning same records twice.
			$params = array( $row['elementid'], $row['id'], $childId, $row['edition'] );
			$sth = $dbDriver->query( $sql, $params );
			$subResults = self::fetchResults( $sth );
			$resultRows = array_merge( $resultRows, $subResults );
		}

		return $resultRows;
	}
	
	/**
	 * Retrieves the placed object ids for those articles for which the given element ids 
	 * are already placed on a layout for a given edition. This can be used to detect 
	 * whether or not the user is about to place an element (text component) twice.
	 *
	 * Optionally a parent id can be given to exclude the child ids that are placed on the given
	 * layout id. This is useful when you want to know if the elementIds are placed on another layout.
	 *
	 * @param string[] $elementIds
	 * @param integer $editionId
	 * @param integer $excludeParentId Optional id of a parent of which the relations should be excluded.
	 * @return array Map with element ids and edition ids, both as keys.
	 */
	static public function getChildsIdsForPlacedElementIdsAndEdition( array $elementIds, $editionId, $excludeParentId = null )
	{
		$select = array( 'child', 'elementid' );
		$where = "`elementid` IN ('".implode("','",$elementIds)."') ".
				"AND `type` = ? ". // Ignore Planned and other relation types.
				"AND `frameorder` = 0 ". // Only include the beginning of stories (start with frame 0).
				"AND ( `edition` = 0 OR `edition` = ? ) "; // Suppress one placed in North and other in South, which is no duplicate!
		$params = array( 'Placed', $editionId );

		if( $excludeParentId ) {
			$where .= 'AND `parent` <> ?';
			$params[] = $excludeParentId;
		}

		$rows = self::listRows( self::TABLENAME, null, null, $where, $select, $params );
		
		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[$row['child']][$row['elementid']] = true;
		}
		return $map;
	}

	/**
	 * Retrieves placements together with the orientation they are in.
	 *
	 * Retrieves a merged result between the placements table and the
	 * pages table.
	 *
	 * @static
	 * @param int $parentId
	 * @param int $childId
	 * @return array placement rows.
	 */
	static public function getPlacementsWithPageOrientation( $parentId, $childId )
	{
		$dbDriver = DBDriverFactory::gen();
		$placementsTable = $dbDriver->tablename( self::TABLENAME );

		require_once BASEDIR.'/server/dbclasses/DBPage.class.php';
		$pageTable = $dbDriver->tablename( DBPage::TABLENAME );

		$sql  = "SELECT pl.`parent`, pl.`edition`, pl.`element`, pa.`orientation` ";
		$sql .= "FROM $placementsTable pl, $pageTable pa ";
		$sql .= "WHERE ";
		$sql .= " pl.`type` = 'Placed' ";
		$sql .= " AND pl.`child` = ? ";
		$sql .= " AND pl.`parent` = ? ";
		$sql .= " AND (pa.`edition` = pl.`edition` OR pl.`edition` = 0 ) ";
		$sql .= " AND pa.`pagesequence` = pl.`pagesequence` ";
		$sql .= " AND pa.`objid` = pl.`parent` ";
		$sql .= " AND pl.`frameorder` = 0 ";
		$sql .= "ORDER BY pl.`edition` ASC ";
		$params = array( intval( $childId ), intval( $parentId ) );

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth );
		return $rows;
	}

	/**
	 * Method checks if elements of an article are also placed on another
	 * layout of same edition. If so, the method returns a list of the id(s) of  
	 * the layout were the elements are also placed.
	 * If the edition of the placement is 0 (zero) the placement is applicable to 
	 * all editions of the parent (layout). In that case the editions from target-
	 * editions are taken into account.
	 * Furthermore we are only interested in 'Placed' relations.
	 *
	 * @param integer object id of layout containing the article $parentId
	 * @param integer object id of article $childId
	 * @return array of objects id's or, if nothing found, null
	 */
	public static function getLayoutsWithSameArticleElements($parentId, $childId)
	{
		$dbdriver = DBDriverFactory::gen();	
		$dbp = $dbdriver->tablename(self::TABLENAME);
		$dbt = $dbdriver->tablename("targets");
		$dbte = $dbdriver->tablename("targeteditions");
		$dbor = $dbdriver->tablename("objectrelations");
		$result = null;
		
        $sql = 	"SELECT plb.`parent` " .
        		"FROM $dbp pla " . 
                "INNER JOIN $dbp plb ON (pla.`child` = plb.`child` and pla.`elementid` = plb.`elementid`) " .
                "INNER JOIN $dbt taa on (pla.`parent` = taa.`objectid`) " .
                "INNER JOIN $dbt tab on (plb.`parent` = tab.`objectid`) " .
                "LEFT JOIN $dbte tea on (taa.`id` = tea.`targetid`) " .
                "LEFT JOIN $dbte teb on (tab.`id` = teb.`targetid`) " .
                "INNER JOIN $dbor ora on (ora.`parent` = pla.`parent` and ora.`child` = pla.`child` and ora.`type` = 'Placed') " .
                "INNER JOIN $dbor orb on (orb.`parent` = plb.`parent` and orb.`child` = plb.`child` and orb.`type` = 'Placed') " .
                "WHERE pla.`child` = ? " .
                "AND pla.`parent` = ? " .
                "AND pla.`id` <> plb.`id` " . 
                "AND ( " .
                "	 (pla.`edition` = plb.`edition` AND pla.`edition` <> 0 AND plb.`edition` <>0) OR " .
                "     (tea.`editionid` = plb.`edition` AND pla.`edition` = 0 AND plb.`edition` <> 0) OR " .
                "     (teb.`editionid` = pla.`edition` AND pla.`edition` <> 0 AND plb.`edition` = 0) OR " .
                "     (teb.`editionid` = tea.`editionid` AND pla.`edition` = 0 AND plb.`edition` = 0) " .
                "     ) ";
		$params = array( intval( $childId ), intval( $parentId ) );
		$sth = $dbdriver->query($sql, $params );
		
		while (($row = $dbdriver->fetch($sth))) {
			$result[] = $row;
		}
		
		return $result;
	}

	/**
	 * Returns placements for specified parent/child object, possibly filtered on type
	 *
	 * @param string $parent Object ID of object on which is placed.
	 * @param string $child  Object ID of object being placed.
	 * @param string $type   Placed or Planned
	 * @return Placement[]|null The placements, or NULL in case of DB error
	 */
	static public function getPlacements( $parent, $child, $type = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);
		$editionstable = $dbDriver->tablename("editions");
		
		$sql = "SELECT pla.*, edi.`name` FROM $db pla ";
		$sql .= "LEFT JOIN $editionstable edi ON (pla.`edition` = edi.`id`) ";
		$sql .= "WHERE `child`= ? AND `parent`= ? ";
		$params = array( intval( $child ), intval( $parent ) );

		// Never return relations that are marked as 'deleted'.
		if ($type) {
			$sql .= " AND `type` = ? ";
			$params[] = strval( $type );
		} else {
			$sql .= " AND `type` NOT LIKE 'Deleted%'";
		}

		// Placements need to be returned in creation order. Because for updates,
		// the placements are removed and re-created, the 'id' field can be used.
		$sql .= ' ORDER BY pla.`id` ASC';

		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			return null; // DB error
		}
		
		// fetch into array
		$placements = array();
		while (($row = $dbDriver->fetch($sth) )) {
			$placement = self::rowToObj( $row );
			$placement->Tiles = self::resolveTiles( $row );
			$placement->InDesignArticleIds = array();
			$placements[$row['id']] = $placement;
		}
		
		if( $placements ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
			$articleIds = DBInDesignArticlePlacement::getInDesignArticleIds( $parent, array_keys( $placements ) );
			if( $articleIds ) foreach( $articleIds as $placementId => $placementArticleIds ) {
				$placements[$placementId]->InDesignArticleIds = $placementArticleIds;
			}
		}
		
		return array_values( $placements );
	}

	/**
	 * Returns InDesign Article placements for specified parent object.
	 *
	 * In case of InDesign Article placements the child is set to 0 and the type is 'Placed'.
	 *
	 * @param string $parent Object ID of object on which is placed.
	 * @param mixed fields Array with names of the fields to query or '*' to retrieve all.
	 * @return Placement[]|null The placements, or NULL in case of DB error
	 */
	static private function getInDesignArticlePlacementRows( $parent, $fields = array() )
	{
		if( !$fields ) {
			$fields = '*';
		}
		$where = ' `child`= ? AND `parent`= ? AND `type` = ? ';
		$params = array( 0, $parent, 'Placed' );

		return self::listRows( self::TABLENAME, '', '', $where, $fields, $params );
	}

	/**
	 * Returns the first placement(s!) for specified parent/child object that allows caller to
	 * determine the x,y position on the page where the object is placed. Images have one frame.
	 * Articles can have many stories. Each story can have many frames and starts with frameorder=0.
	 * (The stories are grouped with the elementid field, which is unique within the whole article.)
	 * When a frame is placed outside the page (=pasteboard), it has width/height set to zero.
	 * Those frames are skipped when there are more frames that belong to the same story !!!
	 * In other terms, when first frame is placed on pasteboard, it searches if there is any next
	 * frame at that story which *has* valid width/height values, which indicates it is placed on the page.
	 * When *no* frames are found for a certain story, it returns the first frame (frameorder=0) !!!
	 * In this sitation, *all* frames of a story are placed on the pasteboard (outside the page).
	 * This way, the caller has *exactly* one frame per story, which is the 'best' one... that is,
	 * the first best found that is actually placed on page, or when there is none placed, the first frame.
	 *
	 * @param string $parent Object ID of object on which is placed.
	 * @param string $child  Object ID of object being placed.
	 * @return Placement[]|null The placements, or NULL in case of DB error
	 */
	static public function getFirstPlacementsPlacedOnPages( $parent, $child )
	{
		if( !$parent || !$child ) {
			return null; // be very specific to find first
		}
		$dbDriver = DBDriverFactory::gen();
		$dbp = $dbDriver->tablename(self::TABLENAME);
		$sql =
			"SELECT * FROM $dbp a "
			."WHERE a.`child` = ? AND a.`parent` = ? AND a.`type` = 'Placed'  "
			."AND a.`frameorder` = ( "
			."	SELECT MIN(b.`frameorder`) "               // Find the *first* frame...
			."	FROM $dbp b "
			."	WHERE b.`child` = ? AND b.`parent` = ? AND b.`type` = 'Placed' "
			."	AND b.`width` > 0 AND b.`height` > 0 "     // ...that is placed on a page...
			."	AND a.`elementid` = b.`elementid` ) "      // ...and belongs to the same story.
			."	OR ( a.`child` = ? AND a.`parent` = ? AND a.`type` = 'Placed' "
			."  	AND a.`frameorder` = 0 "               // But when all frames are on pasteboard...
			."		AND a.`height` = 0 AND a.`width` = 0 " // ...take the first frame...
			."		AND NOT EXISTS ( "                     // ...when *none* of that story are placed on the page!
			."			SELECT * "
			."			FROM $dbp c "
			."			WHERE c.`child` = ? AND c.`parent` = ? AND c.`type` = 'Placed' "
			."			AND c.`width` > 0 AND c.`height` > 0 "
			."			AND a.`elementid` = c.`elementid`) ) ";
		$params = array( intval( $child ), intval( $parent ), intval( $child ), intval( $parent ),
								intval( $child ), intval( $parent ), intval( $child ), intval( $parent ) );

		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			return null; // DB error
		} 
		// fetch into array
		$placements = array();
		while (($row = $dbDriver->fetch($sth) )) {
			$placement = self::rowToObj( $row );
			$placement->Tiles = self::resolveTiles( $row );
			$placement->InDesignArticleIds = array();
			$placements[$row['id']] = $placement;
		}

		if( $placements ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
			$articleIds = DBInDesignArticlePlacement::getInDesignArticleIds( $parent, array_keys( $placements ) );
			if( $articleIds ) foreach( $articleIds as $placementId => $placementArticleIds ) {
				$placements[$placementId]->InDesignArticleIds = $placementArticleIds;
			}
		}

		return $placements;
	}
	
	/**
	 * Gets all placements of an object as child or parent or both.
	 * If both $parentId and $childId are given, it returns placements of both.
	 * The $deletedOnes indicates whether to get the 'Deleted' placement (laying in Trash area)
	 * or the 'Non-Deleted' placement (laying in Workflow area).
	 * 
	 * Example return value:
	 * array('1-2-Placed' => array( new Placement(), new Placement() )
	 * 		 '1-3-Placed' => array( new Placement() ) )
	 *
	 * @param integer|integer[] $parentId parent object id(s)
	 * @param integer|integer[] $childId child object id(s)
	 * @param string $type placement type
	 * @param bool $deletedOnes 
	 * @return array with key <parent id>-<child id>-<type> and value an array
	 * 			of Placement objects.
	 */
	static public function getAllPlacements( $parentId = 0, $childId = 0, $type = '', $deletedOnes=false)
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);
		$editionstable = $dbDriver->tablename('editions');
		$tilestable = $dbDriver->tablename('placementtiles');
		
		$sql = 'SELECT pla.*, edi.`name`, tiles.`id` as "hastiles" '
			. "FROM $db pla "
			. "LEFT JOIN $editionstable edi ON (pla.`edition` = edi.`id`) "
			. "LEFT JOIN $tilestable tiles ON (pla.`id` = tiles.`placementid` ) ";
		$where = '';
		$params = array();
		if( $parentId && $childId ) {
			$where .= '(';
			if( is_array( $parentId ) ) {
				$idsCsv = implode( ',', $parentId );
				$where .= "pla.`parent` IN ($idsCsv) ";
			} else {
				$where .= "pla.`parent` = ? ";
				$params[] = intval( $parentId );
			}			
			if( is_array( $childId ) ) {
				$idsCsv = implode( ',', $childId );
				$where .= "OR pla.`child` IN ($idsCsv) ";
			} else {
				$where .= "OR pla.`child` = ? ";
				$params[] = intval( $childId );
			}
			$where .= ')';
		} elseif( $parentId ) {
			if( is_array( $parentId ) ) {
				$idsCsv = implode( ',', $parentId );
				$where .= "pla.`parent` IN ($idsCsv) ";
			} else {
				$where .= "pla.`parent` = ? ";
				$params[] = intval( $parentId );
			}
		} elseif( $childId ) {
			if( is_array( $childId ) ) {
				$idsCsv = implode( ',', $childId );
				$where .= "pla.`child` IN ($idsCsv) ";
			} else {
				$where .= "pla.`child` = ? ";
				$params[] = intval( $childId );
			}
		}
		if ($where != ''){
			$where .= ' AND';
		}
		if ($type != ''){
			$where .= " pla.`type`= ? ";
			$params[] = strval( $type );
		} elseif ( $deletedOnes) {
			$where .= " pla.`type` LIKE 'Deleted%'";
		} else{
			// Never return relations that are marked as 'deleted' unless asked.
			$where .= " pla.`type` NOT LIKE 'Deleted%'";
		}
		
		// Placements need to be returned in creation order. Because for updates,
		// the placements are removed and re-created, the 'id' field can be used.
		$orderBy = 'ORDER BY pla.`id` ASC';

		$sth = $dbDriver->query( $sql.' WHERE '.$where.' '.$orderBy, $params );
		if( !$sth ) {
			return null; // DB error
		} 
		
		// fetch into array
		require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
		$placements = array();
		$placementIds = array();
		while (($row = $dbDriver->fetch($sth) )) {
			$key = $row['parent'] . '-' . $row['child'] . '-' . $row['type'];
			if (! isset($placements[$key])){
				// create new array to hold placements for given key
				$placements[$key] = array();
			}
			// add placement to given key
			if ( !isset( $placementIds[$row['id']] )) {
				$placement = self::rowToObj( $row );
				$placement->Tiles = self::resolveTiles( $row );
				$placement->InDesignArticleIds = array();
				$placements[$key][$row['id']] = $placement;
				$placementIds[$row['id']] = true;
				// In case a placement consists of two tiles the above query returns the placement twice. One time
				// for each tile. To prevent the doubling of the placement the id of the placement is used to check
				// if the placement is already handled.
			}
		}
		
		// Resolve the InDesignArticleIds (through just one SQL statement).
		if( $placements ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignArticlePlacement.class.php';
			$articleIds = DBInDesignArticlePlacement::getInDesignArticleIds( $parentId, array_keys( $placementIds ) );
			if( $articleIds ) foreach( $articleIds as $placementId => $placementArticleIds ) {
				foreach( array_keys($placements) as $key ) {
					if( isset($placements[$key][$placementId]) ) {
						$placements[$key][$placementId]->InDesignArticleIds = $placementArticleIds;
					}
				}
			}
		}
		
		// Remove the placement ids used in keys (2nd dimension).
		$retPlacements = array();
		foreach( $placements as $key => $keyPlacements ) {
			$retPlacements[$key] = array_values( $keyPlacements );
		}
		
		return $retPlacements;
	}

	/**
	 * Retrieves placements from DB by given placement ids.
	 * If a placement consists of threaded frames and only the first one is needed onlyFirstFrame has to be set to true.
	 *
	 * For each placement a basic property set is resolved: Element, ElementID, FrameID, 
	 * FrameType and Edition. Additionally an extra property named ChildId is provided,
	 * which is not defined for the Placement data object.
	 *
	 * @param integer[] $placementIds
	 * @param boolean $onlyFirstFrame Return placements with frameorder set to 0.
	 * @return Placement[] Retrieved placements (values) and placement ids (keys).
	 * @throws BizException On bad given params or fatal SQL errors.
	 */
	static public function getPlacementBasicsByIds( array $placementIds, $onlyFirstFrame = false )
	{	
		// Bail out when invalid parameters provided. (Paranoid check.)
		if( !$placementIds  ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Invalid params provided for '.__METHOD__.'().' );
		}

		$select = array( 'id', 'element', 'elementid', 'frameid', 'frametype', 'frameorder', 'edition', 'child', 'splineid' );
		$where = '`id` IN ('.implode(',',$placementIds).')';
		$params = array();
		if ( $onlyFirstFrame ) {
			$where .= 'AND `frameorder` = ? ';
			$params[] = 0;
		}
		$rows = self::listRows( self::TABLENAME, null, null, $where, $select, $params );
		if( self::hasError() || is_null($rows) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		
		$placements = array();
		if( $rows ) foreach( $rows as $row ) {
			$placements[ $row['id'] ] = self::rowToObj( $row );
			$placements[ $row['id'] ]->ChildId = $row['child']; // hack
		}
		return $placements;
	}
	
	/**
	 * Resolves the placement's tiles from DB.
	 *
	 * @param array $row Placement DB record.
	 * @return PlacementTile[]
	 */
	static private function resolveTiles( array $row )
	{
		require_once BASEDIR.'/server/dbclasses/DBPlacementTiles.class.php';
		$tiles = null;
		if ( array_key_exists( 'hastiles', $row ) ) { // Check on the existence of tiles.
			if ( !is_null( $row['hastiles'])) { // At least one tile is found.
				$tiles = DBPlacementTiles::listPlacementTiles( $row['id'] );
			} else {
				$tiles = array();
			}	
		} else { // No check done so go to the tiles table.
			$tiles = DBPlacementTiles::listPlacementTiles( $row['id'] );
		}
		return $tiles;
	}

	/**
	 * Converts a Placement workflow data object into a placement record (array of DB fields).
	 *
	 * @param Placement $obj Workflow placement data object
	 * @param bool $default In case the object property is null set the default (as defined in dbmodel)
	 * @return array DB placement record (array of DB fields)
	 */
	static private function objToRow( $obj, $default = false )
	{
		if( $default ) {
			$row = self::setDefaultsForRow();
		} else {
			$row = array();
		}
		if( !is_null( $obj->Page ) ) $row['page'] = is_numeric( $obj->Page ) ? intval( $obj->Page ) : 0;
		if( !is_null( $obj->Element ) ) $row['element'] = $obj->Element;
		if( !is_null( $obj->ElementID ) ) $row['elementid'] = $obj->ElementID;
		if( !is_null( $obj->FrameOrder ) ) $row['frameorder'] = is_numeric( $obj->FrameOrder ) ? intval( $obj->FrameOrder ) : 0;
		if( !is_null( $obj->FrameID ) ) $row['frameid'] = $obj->FrameID;
		if( !is_null( $obj->Left ) ) $row['_left'] = is_numeric( $obj->Left ) ? floatval( $obj->Left ) : 0;
		if( !is_null( $obj->Top ) ) $row['top'] = is_numeric( $obj->Top ) ? floatval( $obj->Top ) : 0;
		if( !is_null( $obj->Width ) ) $row['width'] = is_numeric( $obj->Width ) ? floatval( $obj->Width ) : 0;
		if( !is_null( $obj->Height ) ) $row['height'] = is_numeric( $obj->Height ) ? floatval( $obj->Height ) : 0;
		if( !is_null( $obj->Overset ) ) $row['overset'] = is_numeric( $obj->Overset ) ? floatval( $obj->Overset ) : 0;
		if( !is_null( $obj->OversetChars ) ) $row['oversetchars'] = is_numeric( $obj->OversetChars ) ? intval( $obj->OversetChars ) : 0;
		if( !is_null( $obj->OversetLines ) ) $row['oversetlines'] = is_numeric( $obj->OversetLines ) ? intval( $obj->OversetLines ) : 0;
		if( !is_null( $obj->Layer ) ) $row['layer'] = $obj->Layer;
		if( !is_null( $obj->Content ) ) $row['content'] = ( strtolower( DBTYPE ) == 'mysql' ) ? mb_strcut( $obj->Content, 0, 64000, 'UTF-8' ) : $obj->Content;
		if( !is_null( $obj->Edition ) ) $row['edition'] = $obj->Edition->Id ? intval( $obj->Edition->Id ) : 0;
		if( !is_null( $obj->ContentDx ) ) $row['contentdx'] = is_numeric( $obj->ContentDx ) ? floatval( $obj->ContentDx ) : 0;
		if( !is_null( $obj->ContentDy ) ) $row['contentdy'] = is_numeric( $obj->ContentDy ) ? floatval($obj->ContentDy ) : 0;
		if( !is_null( $obj->ScaleX ) ) $row['scalex'] = is_numeric( $obj->ScaleX ) ? floatval( $obj->ScaleX ) : 1;
		if( !is_null( $obj->ScaleY ) ) $row['scaley'] = is_numeric( $obj->ScaleY ) ? floatval( $obj->ScaleY ): 1;
		if( !is_null( $obj->PageSequence ) ) $row['pagesequence'] = is_numeric( $obj->PageSequence ) ? intval( $obj->PageSequence ) : 0;
		if( !is_null( $obj->PageNumber ) ) $row['pagenumber'] = $obj->PageNumber;
		if( !is_null( $obj->FormWidgetId ) ) $row['formwidgetid'] = $obj->FormWidgetId;
		if( !is_null( $obj->FrameType ) ) $row['frametype'] = $obj->FrameType;
		if( !is_null( $obj->SplineID ) ) $row['splineid'] = $obj->SplineID;

		return $row;
	}

	/**
	 * Returns columns with their default value.
	 *
	 * @return array with column/default as key/value.
	 */
	static private function setDefaultsForRow()
	{
		$row = array(
		  'page' => 0,
		  'element' => '',
		  'elementid' => '',
		  'frameorder' => 0,
		  'frameid' => '',
		  '_left' => 0,
		  'top' => 0,
		  'width' => 0,
		  'height' => 0,
		  'overset' => 0,
		  'oversetchars' => 0,
		  'oversetlines' => 0,
		  'layer' => '',
		  'content' => '',
		  'edition' => 0,
		  'contentdx' => 0,
		  'contentdy' => 0,
		  'scalex' => 0,
		  'scaley' => 0,
		  'pagesequence' => 0,
		  'pagenumber' => '',
		  'formwidgetid' => '',
		  'frametype' => '',
		  'splineid' => '',
		);

		return $row;
	}

	/**
	 * Converts a placement record (array of DB fields) into a Placement workflow data object.
	 *
	 * @param array $row DB placement record (array of DB fields)
	 * @return Placement Workflow placement data object
	 */
	static private function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		$placement				= new Placement();
		if( array_key_exists('page', $row ) ) {
			$placement->Page		= $row['page'];
		}
		if( array_key_exists('element', $row ) ) {
			$placement->Element 	= $row['element'];
		}
		if( array_key_exists('elementid', $row ) ) {
			$placement->ElementID	= $row['elementid'];
		}
		if( array_key_exists('frameorder', $row ) ) {
			$placement->FrameOrder	= $row['frameorder'];
		}
		if( array_key_exists('frameid', $row ) ) {
			$placement->FrameID		= $row['frameid'];
		}
		if( array_key_exists('_left', $row ) ) {
			$placement->Left		= $row['_left'];
		}
		if( array_key_exists('top', $row ) ) {
			$placement->Top			= $row['top'];
		}
		if( array_key_exists('width', $row ) ) {
			$placement->Width		= $row['width'];
		}
		if( array_key_exists('height', $row ) ) {
			$placement->Height		= $row['height'];
		}
		if( array_key_exists('overset', $row ) ) {
			$placement->Overset		= $row['overset'];
		}
		if( array_key_exists('oversetchars', $row ) ) {
			$placement->OversetChars= $row['oversetchars'];
		}
		if( array_key_exists('oversetlines', $row ) ) {
			$placement->OversetLines= $row['oversetlines'];
		}
		if( array_key_exists('layer', $row ) ) {
			$placement->Layer		= $row['layer'];
		}
		if( array_key_exists('content', $row ) ) {
			$placement->Content		= $row['content'];
			if( is_null($placement->Content) ) { // happens for empty value in Oracle (blob)
				$placement->Content = '';
			}
		}
		if( array_key_exists('edition', $row ) ) {
			if( isset($row['edition']) && $row['edition'] != 0 ) {
				$editionName = isset($row['name']) ? $row['name'] : null;
				$placement->Edition = new Edition( $row['edition'], $editionName );
			} else {
				$placement->Edition = null;
			}
		}
		if( array_key_exists('contentdx', $row ) ) {
			$placement->ContentDx	= $row['contentdx'];
		}
		if( array_key_exists('contentdy', $row ) ) {
			$placement->ContentDy	= $row['contentdy'];
		}
		if( array_key_exists('scalex', $row ) ) {
			$placement->ScaleX		= $row['scalex'];
		}
		if( array_key_exists('scaley', $row ) ) {
			$placement->ScaleY		= $row['scaley'];
		}
		if( array_key_exists('pagesequence', $row ) ) {
			$placement->PageSequence= $row['pagesequence'];
		}
		if( array_key_exists('pagenumber', $row ) ) {
			$placement->PageNumber	= $row['pagenumber'];
		}
		if( array_key_exists('formwidgetid', $row ) ) {
			$placement->FormWidgetId = $row['formwidgetid'];
		}
		if( array_key_exists('frametype', $row ) ) {
			$placement->FrameType    = $row['frametype'];
		}
		if( array_key_exists('splineid', $row ) ) {
			$placement->SplineID    = $row['splineid'];
		}
		return $placement;
	}

	/**
	 * Returns all placement ids belonging to the relations.
	 *
	 * @param Relation[] $relations
	 * @return array|null
	 */
	static public function getPlacementIdsByRelations( array $relations )
	{
		$placementsIds = array();
		if( $relations ) {
			$or = '';
			$where = '(';
			$params = array();
			foreach( $relations as $relation ) {
				$where .= $or;
				$where .= '( `parent`= ? AND `child`= ? AND `type` = ? ) ';
				$params[] = $relation->Parent;
				$params[] = $relation->Child;
				$params[] = $relation->Type;
				$or = 'OR ';
			}
			$where .= ')';
			$rows = self::listRows( self::TABLENAME, '', '', $where, array('id'), $params );
			$placementsIds = array_map( function( $row) { return $row['id']; }, $rows);
		}

		return $placementsIds;
	}
}