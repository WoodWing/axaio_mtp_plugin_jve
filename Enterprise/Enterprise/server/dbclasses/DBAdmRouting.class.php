<?php

/**
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmRouting extends DBBase
{
	const TABLENAME = 'routing';

	/**
	 * Checks if a duplicate routing rule exists in the database.
	 *
	 * A duplicate authorization has the same variables, but not the same id (if given).
	 *
	 * @param AdmRouting $routing The Routing object to be tested.
	 * @return bool If true, a duplicate has been found, false otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function routingExists( AdmRouting $routing )
	{
		$where = '`publication` = ? AND `issue` = ? AND `section` = ? AND `state` = ? AND `routeto` = ? ';
		$sectionId = isset( $routing->SectionId ) ? $routing->SectionId : 0;
		$statusId = isset( $routing->StatusId ) ? $routing->StatusId : 0;
		$params = array(
			intval( $routing->PublicationId ),
			intval( $routing->IssueId ),
			intval( $sectionId ),
			intval( $statusId ),
			strval( $routing->RouteTo ),
		);
		if( $routing->Id ) {
			$where .= 'AND `id` != ? ';
			$params[] = intval( $routing->Id );
		}

		$row = self::getRow( self::TABLENAME, $where, array( 'id' ), $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return (bool)$row;
	}

	/**
	 * Create a new routing rule at the database.
	 *
	 * @param AdmRouting $routing The Routing object to be created.
	 * @return bool|int New inserted routing id when it is successfully inserted, false otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function createRouting( AdmRouting $routing )
	{
		$routing->Id = null;
		$row = self::objToRow( $routing );
		$newId = self::insertRow( self::TABLENAME, $row );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $newId;
	}

	/**
	 * Modify an existing routing rule at the database.
	 *
	 * @param AdmRouting $routing The Routing object to be modified.
	 * @return bool True if the modify was successful, false otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function modifyRouting( AdmRouting $routing )
	{
		$row = self::objToRow( $routing );
		unset( $row['id'] );
		$where = '`id` = ? ';
		$params = array( intval( $routing->Id ) );
		$updated = self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $updated;
	}

	/**
	 * Requests routing rules from the database.
	 *
	 * @param integer|null $publicationId The publication id, can be set to null if routing ids are used.
	 * @param integer|null $issueId The issue id, null if routing ids are used.
	 * @param integer|null $sectionId The section id, null if routing ids are used.
	 * @param array|null $routingIds List of routing ids, null if filter ids are used.
	 * @return AdmRouting[] List of requested Routing objects.
	 * @throws BizException on SQL error.
	 */
	public static function getRoutings( $publicationId, $issueId, $sectionId, $routingIds )
	{
		$where = '';
		$params = array();
		if( $publicationId ) {
			$where .= '`publication` = ? ';
			$params[] = intval( $publicationId );
		}
		if( isset( $issueId ) ) {
			$where .= 'AND `issue` = ? ';
			$params[] = intval( $issueId );
		}
		if( $sectionId ) {
			$where .= 'AND `section` = ? ';
			$params[] = intval( $sectionId );
		}
		if( $routingIds ) {
			$wherePart = self::addIntArrayToWhereClause( 'id', $routingIds );
			if( $wherePart ) {
				$where .= $where ? 'AND ' : '';
				$where .=  $wherePart. ' ';
			}
		}
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$routings = array();
		if( $rows ) foreach( $rows as $row ) {
			$routings[$row['id']] = self::rowToObj( $row );
		}
		return $routings;
	}

	/**
	 * Deletes routing rules from the database.
	 *
	 * @param integer|null $publicationId The publication id, null if routing ids are used.
	 * @param integer|null $issueId The issue id, null if routing ids are used.
	 * @param integer|null $sectionId The section id, null if routing ids are used.
	 * @param integer[]|null $routingIds List of routing ids, null if filter ids are used.
	 * @return bool True if the delete was successful, false otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function deleteRoutings( $publicationId, $issueId, $sectionId, $routingIds )
	{
		$where = '';
		$params = array();
		if( $publicationId ) {
			$where .= '`publication` = ? ';
			$params[] = intval( $publicationId );
		}
		if( $issueId ) {
			$where .= 'AND `issue` = ? ';
			$params[] = intval( $issueId );
		}
		if( $sectionId ) {
			$where .= 'AND `section` = ? ';
			$params[] = intval( $sectionId );
		}
		if( $routingIds ) {
			$wherePart = self::addIntArrayToWhereClause( 'id', $routingIds );
			if( $wherePart ) {
				$where .= $where ? 'AND ' : '';
				$where .=  $wherePart. ' ';
			}
		}
		$deleted = self::deleteRows( self::TABLENAME, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $deleted;
	}

	/**
	 * Retrieves the publication ids of a list of Routing ids.
	 *
	 * @param integer[] $routingIds List of Routing ids.
	 * @return array Paired array with routingId and pubId
	 * @throws BizException on SQL error.
	 */
	public static function getPubIdsForRoutingIds( array $routingIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $routingIds );
		if( !$where ) {
			return array();
		}
		$rows = self::listRows( self::TABLENAME, 'id', 'publication', $where, null );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[ $row['id'] ] = $row['publication'];
		}
		return $map;
	}

	/**
	 * Retrieves the issue ids of a list of Routing ids.
	 *
	 * @param integer[] $routingIds List of Routing ids.
	 * @return array Paired array with routingId and issueId
	 * @throws BizException on SQL error.
	 */
	public static function getIssueIdsForRoutingIds( array $routingIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $routingIds );
		if( !$where ) {
			return array();
		}
		$rows = self::listRows( self::TABLENAME, 'id', 'issue', $where, null );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$map = array();
		if( $rows ) foreach( $rows as $row ) {
			$map[ $row['id'] ] = $row['issue'];
		}
		return $map;
	}

	/**
	 * Checks in the database if the given route to property exists.
	 *
	 * @param string $routeTo The name of a user or user group.
	 * @return bool Return true if the route to refers to an existing user (group), false otherwise.
	 * @throws BizException on SQL error.
	 */
	public static function routeToExists( $routeTo )
	{
		$dbDriver = DBDriverFactory::gen();
		$usersTable = $dbDriver->tablename( 'users' );
		$groupsTable = $dbDriver->tablename( 'groups' );
		$sql = "SELECT * ".
				"FROM {$usersTable} u, {$groupsTable} g ".
				"WHERE u.`user` = ? OR g.`name` = ? ";
		$params = array( strval( $routeTo ), strval( $routeTo ) );
		$sth = self::query( $sql, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return DBBase::fetch( $sth ) ? true : false;
	}

	/**
	 * Deletes a routing rule when given a user group id.
	 *
	 * This function is mostly to be used for cascade delete purposes where only the id of a user group is known, instead of the name.
	 *
	 * @param integer|null $userGroupId The user group id.
	 * @param integer[]|null $wflUGAuthIds A list of workflow
	 * @throws BizException on SQL error.
	 */
	public static function deleteRoutingFromUserGroupAuthorizations( $userGroupId, $wflUGAuthIds )
	{
		$dbDriver = DBDriverFactory::gen();
		$authTable = $dbDriver->tablename( 'authorizations' );
		$groupsTable = $dbDriver->tablename( 'groups' );
		$where = null;
		$params = array();
		if( $userGroupId ) {
			$where = "`routeto` = ( SELECT `name` FROM {$groupsTable} WHERE `id` = ? ) ";
			$params = array( intval( $userGroupId ) );

		} elseif( $wflUGAuthIds ) {
			$wherePart = self::addIntArrayToWhereClause( 'a.id', $wflUGAuthIds );
			if( $wherePart ) {
				$where = "`routeto` IN ( SELECT name FROM {$groupsTable} g ".
					"LEFT JOIN {$authTable} a ON a.`grpid` = g.`id` WHERE {$wherePart} ) ";
			}
		}
		if( $where ) {
			self::deleteRows( 'routing', $where, $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}
	}

	/**
	 * Converts a Routing object to a database routing row (array).
	 *
	 * @param AdmRouting $obj The Routing object.
	 * @return array The database routing row.
	 */
	public static function objToRow( AdmRouting $obj )
	{
		$row = array();
		if(!is_null($obj->PublicationId)) $row['publication'] = isset( $obj->PublicationId ) ? intval( $obj->PublicationId ) : 0;
		if(!is_null($obj->IssueId))       $row['issue']       = isset( $obj->IssueId )       ? intval( $obj->IssueId ) : 0;
		if(!is_null($obj->SectionId))     $row['section']     = isset( $obj->SectionId )     ? intval( $obj->SectionId ) : 0;
		if(!is_null($obj->StatusId))      $row['state']       = isset( $obj->StatusId )      ? intval( $obj->StatusId ) : 0;
		if(!is_null($obj->RouteTo))       $row['routeto']     = isset( $obj->RouteTo )       ? strval( $obj->RouteTo ) : '';
		return $row;
	}

	/**
	 * Converts a database routing row (array) to a Routing object.
	 *
	 * @param array $row The database routing row.
	 * @return AdmRouting The Routing object.
	 */
	public static function rowToObj( $row )
	{
		$obj = new AdmRouting;
		$obj->Id            = intval($row['id']);
		$obj->PublicationId = intval($row['publication']);
		$obj->IssueId       = intval($row['issue']);
		$obj->SectionId     = intval($row['section']);
		$obj->StatusId      = intval($row['state']);
		$obj->RouteTo       = strval($row['routeto']);
		return $obj;
	}
}
