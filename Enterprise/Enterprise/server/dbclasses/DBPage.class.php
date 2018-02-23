<?php
/**
 * Implements DB querying of pages, typically for layout objects.
 * Produced and planned pages are supported.
 * Note: The edition property of a page needs some extra explanation. A channel can be configured with or without
 * editions.
 * - Without:
 *  In this case the edition property is 0 (zero) for all pages of the layout.
 * - With:
 *  A page which is meant for a specific edition has an edition property unequal to 0 (zero).
 *  A page which is the same for all editions has an edition property of 0 (zero).
 * It is important that pages are always requested for a certain edition in case editions are configured. So only if no
 * editions are used the $edition(id) parameter can be set to 0 (zero). If editions are used the methods below must be
 * called with a correct $edition(id) parameter. So calling the method with $edition(id) = 0 is incorrect in that case.
 * It is up to caller to pass the correct id.
 *
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBPage extends DBBase
{
	const TABLENAME = 'pages';

	/**
	 * Remove pages (Typically for Layout)
	 *
	 * @param string $objid
	 * @param string $instance
	 * @return bool
	 */
	public static function cleanPages( $objid, $instance )
	{
		$dbdriver = DBDriverFactory::gen();	
		$db = $dbdriver->tablename(self::TABLENAME);
		$params = array();

		$sql = "DELETE FROM $db WHERE `objid`= ? AND `instance` = ?";
		$params[] = intval($objid);
		$params[] = $instance;
		$sth = $dbdriver->query($sql, $params);
		 
		return $sth ? true : false;
	}

	/**
	 * Get pages. (Typically for Layout)
	 *
	 * @param string $objid
	 * @param string $instance
	 * @param null|int $pageid
	 * @param null|int $edition (about how to use see the explanation in the class header).
	 * @param bool $unique
	 * @param bool $resolveEditionName If true the edition name is also returned.
	 * @return Resource
	 */
	public static function getPages( $objid, $instance, $pageid=null, $edition = null, $unique = false, $resolveEditionName = false )
	{
		$dbdriver = DBDriverFactory::gen();	
		$db = $dbdriver->tablename(self::TABLENAME);
		$params = array();

		$sql = "SELECT p.* ";
		if( $resolveEditionName ) {
			$sql .= ", e.`name` as \"editionname\" ";
		}
		$sql .= "FROM $db p ";
		
		if( $resolveEditionName ) {
			$dbEditions = $dbdriver->tablename('editions');
			$sql .= "LEFT JOIN $dbEditions e ON ( p.`edition` = e.`id` ) ";
		}

		$where = " WHERE p.`objid`= ? AND p.`instance` = ?";
		$params[] = intval($objid);
		$params[] = $instance;
		if ($pageid) {
			$where .= ' AND p.`pageorder` = ?';
			$params[] = intval($pageid);
		}
		if ($edition) {
			$where .= ' AND (p.`edition` = 0 OR p.`edition` = ?)';
			$params[] = intval($edition);
		} else if ($unique) {
			$where .= " AND p.`edition` = (SELECT MIN(p.`edition`) FROM $db p $where)";
			$params = array_merge($params, $params); // Duplicate $params array,
													 // $where is extended with itself
		}
		$sql .= "$where ORDER BY p.`pagesequence`, p.`pageorder` ";
		$sth = $dbdriver->query($sql, $params);

		return $sth;
	}
	
	/**
	 *	Lists all pages of a specific issue (and possibly edition), either in normal order or reverse order.
	 *
	 *	@param $issueId integer: id of the issue to get the rows for
	 *	@param $editionId integer: about how to use see the explanation in the class header.
	 *	@param $instance string: either empty, 'Production' or 'Planning'
	 *  @param $categoryId integer: either 0 (all) or a specific Id.
	 *  @param $stateId integer: either 0 (all) or a specific Id.
	 *	@return array of rows describing individual pages.
	 */
	public static function listPagesByLayoutPerIssue( $issueId, $editionId = 0, $instance = 'Production', $categoryId = 0, $stateId = 0 )
	{
		$dbDriver = DBDriverFactory::gen();
		$pagTable = $dbDriver->tablename(self::TABLENAME);
		$objTable = $dbDriver->tablename('objects');
		$tarTable = $dbDriver->tablename('targets');
		$tarEditionTable = $dbDriver->tablename('targeteditions');
		$params = array();
		
		$sql = "SELECT p.*, o.`section` ";
		if( $editionId ) {
			$sql .= ", e.`name` as \"editionname\" ";
		}
		$sql .= "FROM $pagTable p ";
		
		if( $editionId ) {
			$dbEditions = $dbDriver->tablename('editions');
			$sql .= "LEFT JOIN $dbEditions e ON ( p.`edition` = e.`id` ) ";
		}
		$sql .= "INNER JOIN $objTable o ON (o.`id` = p.`objid`) ";
		$sql .= "INNER JOIN $tarTable tar ON (tar.`objectid` = o.`id`) ";
		if ($editionId) {
			// Note that if a layout is targeted for all editions, for all editions targeteditions are created.
			// This makes it possible to use the inner join.
			$sql .= "INNER JOIN $tarEditionTable taredition ON (tar.`id` = taredition.`targetid`) ";	
		}
		
		$where = 'WHERE (tar.`issueid` = ?) AND (o.`type` = ?) ';
		$params[] = intval($issueId);
		$params[] = 'Layout';

		if ( $categoryId ) {
			$where .= 'AND (o.`section` = ?) ';
			$params[] = $categoryId;
		}

		if ( $stateId ) {
			$where .= 'AND (o.`state` = ?) ';
			$params[] = $stateId;
		}

		if( !empty($instance) ) {
			$where .= 'AND p.`instance` = ? ';
			$params[] = $instance;
		}
	
		if ($editionId) {
			$where .= 'AND (p.`edition` = 0 OR p.`edition` = ?) ';
			$params[] = intval($editionId);
			$where .= 'AND (taredition.`editionid` = ?) ';
			$params[] = intval($editionId);
		} else {
			$where .= 'AND p.`edition` = 0 ';
		}
		
		$sql = $sql . $where;
		$sql .= ' ORDER BY p.`objid`, p.`pagesequence`, p.`pageorder` ';
		
		$pages = array();
		$sth = $dbDriver->query($sql, $params);
		
		$holdObjid = -1;
		while (($row = $dbDriver->fetch($sth))) {
			if ( $row['objid'] != $holdObjid ) {
				$pages[$row['objid']] = array();
				$holdObjid = $row['objid'];
			}	
			$pages[$row['objid']][] = $row;
		}
		return $pages;
	}

	/**
	 *	Lists all pages of specific layouts (and possibly edition), either in normal order or reverse order.
	 *
	 *  @param integer[] Array with (layout) object ids.
	 *	@param integer $editionId About how to use see the explanation in the class header.
	 *	@param string $instance Either empty, 'Production' 'Planning'
	 *	@return array of rows describing individual pages.
	 */
	public static function listPagesByLayoutPerIds( array $layoutIds, $editionId = 0, $instance = 'Production' )
	{
		$dbDriver = DBDriverFactory::gen();
		$pagTable = $dbDriver->tablename(self::TABLENAME);
		$objTable = $dbDriver->tablename('objects');
		$params = array();
		
		$sql = "SELECT p.*, o.`section` ";
		if( $editionId ) {
			$sql .= ", e.`name` as \"editionname\" ";
		}
		$sql .= "FROM $pagTable p ";
		
		if( $editionId ) {
			$dbEditions = $dbDriver->tablename('editions');
			$sql .= "LEFT JOIN $dbEditions e ON ( p.`edition` = e.`id` ) ";
		}
		$sql .= " INNER JOIN $objTable o ON (o.`id` = p.`objid`) ";
		$where 	= " WHERE " . DBBase::addIntArrayToWhereClause('p.objid', $layoutIds, false); 
		$where .= " AND (o.`type` = 'Layout') ";
	
		if (!empty($instance)) {
			$where .= 'AND p.`instance` = ? ';
			$params[] = $instance;
		}
	
		if( $editionId ) {
			$where .= 'AND (p.`edition` = ? OR p.`edition` = ?) ';
			$params[] = 0;
			$params[] = intval($editionId);
		} else {
			$where .= ' AND p.`edition` = 0 ';
		}
		
		$sql = $sql . $where;
		$sql .= " ORDER BY p.`objid`, p.`pagesequence`, p.`pageorder` ";
		
		$pages = array();
		$sth = $dbDriver->query($sql, $params);
		
		$holdObjid = -1;
		while (($row = $dbDriver->fetch($sth))) {
			if ( $row['objid'] != $holdObjid ) {
				$pages[$row['objid']] = array();
				$holdObjid = $row['objid'];
			}	
			$pages[$row['objid']][] = $row;
		}
		return $pages;
	}

	/**
	 * Lists all pages of a specific issue (and possibly edition), either in normal order or reverse order.
	 *
	 * @param int $issueid integer: id of the issue to get the rows for
	 * @param int $editionid integer: About how to use see the explanation in the class header.
	 * @param bool $pageordered If true sort on 'pageorder' else on 'code' (sorting order).
	 * @param int $sectionid The section (category) Id. 0 means do not filter.
	 * @param int $layoutid The object Id of the layout to filer on. 0 means do not filter.
	 * @param string $instance string: either empty, production or ???planning???
	 *
	 * @return array of rows describing individual pages.
	 */
	public static function listIssuePages( $issueid, $editionid = 0, $pageordered = false, $sectionid = 0, $layoutid = 0, $instance = 'Production' )
	{
		$dbDriver = DBDriverFactory::gen();
		$pagTable = $dbDriver->tablename(self::TABLENAME);
		$objTable = $dbDriver->tablename('objects');
		$tarTable = $dbDriver->tablename('targets');
		$secTable = $dbDriver->tablename('publsections');
		$params = array();
		
		$sql =	" SELECT p.`objid`, p.`pageorder`, p.`pagenumber`, p.`pagesequence`, p.`edition`, p.`instance`, o.`section`, ";
		$sql .=	" p.`width`, p.`pagenumber`, p.`pagesequence`, p.`edition`, p.`instance`, o.`section` ";
		$sql .=	" FROM $pagTable p ";
		$sql .= " INNER JOIN $objTable o ON (o.`id` = p.`objid`) ";
		$sql .= " LEFT JOIN $secTable s ON (s.`id` = o.`section`) ";
		$sql .= " INNER JOIN $tarTable tar ON (tar.`objectid` = o.`id`) ";
		$sql .= " WHERE (tar.`issueid` = ?) AND (o.`type` = 'Layout') ";
		$params[] = intval($issueid);
	
		if (!empty($instance)) {
			$sql .= ' AND p.`instance` = ? ';
			$params[] = strval( $instance );
		}
	
		if (!empty($editionid)) {
			$sql .= ' AND (p.`edition` = ? OR p.`edition` = 0) ';
			$params[] = intval($editionid);
		}
	
		if (!empty($sectionid)) {
			$sql .= ' AND (o.`section` = ?) ';
			$params[] = intval($sectionid);	
		}
	
		if (!empty($layoutid)) {
			$sql .= ' AND (o.`id` = ?) ';
			$params[] =	intval($layoutid);
		}
	
		if ($pageordered == false) {
			$sql .= " ORDER BY s.`code` ASC, o.`id` ASC, `pagesequence` ASC, `pageorder` ASC";
		}
		else {
			$sql .= " ORDER BY `pageorder` ASC, o.`name` ASC ";
		}
		
		$pages = array();
		$sth = $dbDriver->query($sql, $params);
		while (($row = $dbDriver->fetch($sth))) {
			$pages[] = $row;
		}
		return $pages;
	}

	/**
	 * Resolve related pages information for the Parallel Editions feature of the Publication Overview.
	 *
	 * @param integer[] $layoutIds
	 * @param integer[] $pageSequences
	 * @return array 3-dim array of page fields, indexed by ids of publication, issue and edition and sorted by their names and pagesequence.
	 * @since 10.4.0
	 */
	public static function listRelatedPagesRows( $layoutIds, $pageSequences )
	{
		$dbDriver = DBDriverFactory::gen();
		$pagesTable = $dbDriver->tablename( self::TABLENAME );
		$editionsTable = $dbDriver->tablename( 'editions' );
		$channelsTable = $dbDriver->tablename( 'channels' );
		$targetsTable = $dbDriver->tablename( 'targets' );
		$issuesTable = $dbDriver->tablename( 'issues' );
		$publicationsTable = $dbDriver->tablename( 'publications' );

		$wheres = array(
			DBBase::addIntArrayToWhereClause( 'pag.objid', $layoutIds, false ),
			DBBase::addIntArrayToWhereClause( 'pag.pagesequence', $pageSequences, false ),
			"pag.`instance` = 'Production'",
		);
		$params = array();

		$sql =
			"SELECT pag.*, edi.`id` as \"editionid\", edi.`name` as \"editionname\",  ".
				"tar.`issueid` as \"issueid\", iss.`name` as \"issuename\", ".
				"pub.`id` as \"publicationid\", pub.`publication` as \"publicationname\" ".
			"FROM $pagesTable pag ".
			"LEFT JOIN $editionsTable edi ON ( edi.`id` = pag.`edition` ) ". // could be no editions configured for the brand
			"INNER JOIN $targetsTable tar ON ( tar.`objectid` = pag.`objid` ) ". // layouts always have 1 issue
			"INNER JOIN $channelsTable cha ON ( cha.`id` = tar.`channelid` ) ".
			"INNER JOIN $issuesTable iss ON ( iss.`id` = tar.`issueid` ) ".
			"INNER JOIN $publicationsTable pub ON ( pub.`id` = cha.`publicationid` ) ".
			"WHERE ".implode( ' AND ', $wheres )." ".
			"ORDER BY pub.`publication`, iss.`name`, edi.`name`, pag.`pagesequence` ";

		$rows = array();
		$sth = $dbDriver->query( $sql, $params );
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$rows[ $row['publicationid'] ][ $row['issueid'] ][ $row['editionid'] ][] = $row;
		}
		return $rows;
	}
	
	public static function insertPage( $objid, $width, $height, $pagenumber, $pageorder, $pagesequence, 
		$edition, $master, $instance, $nr, $types, $orientation )
	{
		if( is_null( $pagenumber ) ) $pagenumber = "0";
		if( is_null( $pageorder ) ) $pageorder = 0;
		if( is_null( $pagesequence ) ) $pagesequence = 0;
		if( is_null( $width ) ) $width = 0;
		if( is_null( $height ) ) $height = 0;
		if( is_null( $nr ) ) $nr = 0;
		if( is_null( $edition ) ) $edition = 0;
		if( is_null( $master ) ) $master = '';
		if( is_null( $instance ) ) $instance = 'Production';
		if( is_null( $orientation) ) $orientation = '';

		$dbdriver = DBDriverFactory::gen();	
		$db = $dbdriver->tablename(self::TABLENAME);

		$sql = 'INSERT INTO '.$db.' (`objid`, `width`, `height`, `pagenumber`, `pageorder`, `pagesequence`, '.
			'`edition`, `master`, `instance`, `nr`, `orientation`, `types`) '.
			'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, #BLOB#)';
		$params = array(intval($objid), $width, $height, $pagenumber, intval($pageorder),
						intval($pagesequence), intval($edition), $master, $instance, intval($nr), $orientation);
		
		$sql = $dbdriver->autoincrement($sql);
		$sth = $dbdriver->query( $sql, $params, $types );

		return $sth;
	}
	
	public static function updatePage( $id, $objid, $width, $height, $pagenumber, $pageorder, $pagesequence, 
		$edition, $master, $instance, $nr, $types, $orientation )
	{
		if( is_null( $pagenumber ) ) $pagenumber = "0";
		if( is_null( $pageorder ) ) $pageorder = 0;
		if( is_null( $pageorder ) ) $pagesequence = 0;
		if( is_null( $width ) ) $width = 0;
		if( is_null( $height ) ) $height = 0;
		if( is_null( $nr ) ) $nr = 0;
		if( is_null( $master ) ) $master = '';
		if( is_null( $edition) ) $edition = 0;
		if( is_null( $instance ) ) $instance = 'Production';
		if( is_null( $orientation) ) $orientation = '';

		$dbdriver = DBDriverFactory::gen();	
		$db = $dbdriver->tablename(self::TABLENAME);

		$sql = "UPDATE $db ";
		$sql .= "SET `objid` = ?, `width` = ?, `height` = ?, `pagenumber` = ?, `pageorder` = ?, `pagesequence` = ?, ";
		$sql .= "`edition` = ?, `master` = ?, `instance` = ?, `nr` = ?, `types` = #BLOB#, `orientation` = ?";
		$sql .= "WHERE `id` = ? ";
		$params = array(intval($objid), $width, $height, $pagenumber, intval($pageorder),
						intval($pagesequence), intval($edition), $master, $instance, intval($nr), intval($id), $orientation);
		$sth = $dbdriver->query( $sql, $params, $types );

		return $sth;
	}
	
	/**
	 * Returns the file renditions of the first Production page of the given layout ids.
	 *
	 * Note that for a certain layout, all its pages have the same set of available 
	 * file renditions (such as thumb, preview and output). Therefore it is safe to take 
	 * the 'first' page only to determine the page renditions (types field) of the layout.
	 *
	 * @param integer[] $objIds IDs of the Layouts or Layout Modules.
	 * @return array Map with object IDs (keys) and PHP serialized value containing content of the 'types' field (values).
	 * @since 9.7.0
	 */
	public static function getRenditionsOfFirstProductionPage( array $objIds )
	{
		$retVal = array();
		if( $objIds ) {
			$dbDriver = DBDriverFactory::gen();	
			$pagesTable = $dbDriver->tablename( self::TABLENAME );
			$objIds = implode( ',', array_map( 'intval', $objIds ) );
			$sql =  'SELECT p1.`objid`, p1.`types` FROM '.$pagesTable.' p1 '.
					'INNER JOIN ( '.
						'SELECT `objid`, MIN(`id`) AS `minid` FROM '.$pagesTable.' '.
						'WHERE `objid` IN ( '.$objIds.' ) AND `instance` = ? '.
						'GROUP BY `objid` '.
					') p2 ON p1.`id` = p2.`minid` ';
			$params = array( 'Production' );
			$sth = $dbDriver->query( $sql, $params );
			while( ( $row = $dbDriver->fetch( $sth ) ) ) {
				$retVal[$row['objid']] = $row['types'];
			}
		}
		return $retVal;
	} 
}