<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAnaIssue extends DBBase
{
	/**
	 * Get a list of statuses that belong to a publication.
	 *
	 * When issue id ($issueId) is passed in, it should be an overrule issue id.
	 * The statuses defined under this overrule issue will be returned instead.
	 *
	 * @param int $pubId Publication id of the statuses to be retrieved.
	 * @param int $issueId Overrule issue id, 0 should be passed in when getting statuses of normal publication.
	 * @return Object[] List of statuses.
	 */
	public static function getStatuses( $pubId, $issueId=0 )
	{
		$params = array();
		$where = "`publication` = ? AND `issue` = ? ORDER BY `code`, `id`";
		$params[] = intval($pubId);
		$params[] = intval($issueId);

		$fields = array( 'id', 'type', 'state', 'color', 'phase' );
		$rows = self::listRows( 'states', null, null, $where, $fields, $params );
		$statusesObj = array();
		if( $rows ) foreach( $rows as $row ) {
			$obj = new stdClass();
			$obj->entid	= intval( $row['id'] );
			$obj->type = $row['type'];
			$obj->name = $row['state'];
			$obj->color = $row['color'] ? $row['color'] : '#A0A0A0';
			$obj->phase = $row['phase'];
			$statusesObj[] = $obj;
		}
		return $statusesObj;
	}

	/**
	 * Gets publication object given the publication id.
	 *
	 * @param int $pubId Publication id.
	 * @return null|stdClass Publication stdClass object, null when no publication found.
	 */
	public static function getPublication( $pubId )
	{
		$where = '`id` = ? ';
		$params = array( intval($pubId) );
		$fields = array( 'id', 'publication', 'description', 'readingorderrev' );
		$row = self::getRow( 'publications', $where, $fields, $params );

		if( $row ) {
			$publicationObj = new stdClass();
			$publicationObj->entid	= intval( $row['id'] );
			$publicationObj->name = $row['publication'];
			$publicationObj->description = $row['description'];
			$publicationObj->readingorder = $row['readingorderrev'] ? 'rtl' : 'ltr';
		} else {
			$publicationObj = null;
		}
		return $publicationObj;
	}

	/**
	 * Gets a list of categories defined under a publication.
	 *
	 * Only pass in the issue id ($issueId) when dealing with overrule issue.
	 *
	 * @param int $pubId Publication id.
	 * @param int $issueId Issue id.
	 * @return stdClass[]
	 */
	public static function getCategories( $pubId, $issueId=0 )
	{
		$params = array();
		$params[] = intval( $pubId );
		$params[] = intval( $issueId );
		$where = "`publication` = ? AND `issue` = ? ORDER BY `code`, `id`";
		$fields = array( 'id', 'section', 'description' );
		$rows = self::listRows( 'publsections', null, null, $where, $fields, $params );
		$categoryObjs = array();
		if( $rows ) foreach( $rows as $row ) {
			$obj = new stdClass();
			$obj->entid	= intval($row['id']);
			$obj->name = $row['section'];
			$obj->description = $row['description'];
			$categoryObjs[] = $obj;
		}
		return $categoryObjs;
	}

	/**
	 * Gets publication channel object.
	 *
	 * @param int $pubChannelId Publication channel id.
	 * @return stdClass
	 */
	public static function getPubChannel( $pubChannelId )
	{
		$where = '`id` = ? ';
		$params = array( intval($pubChannelId) );
		$fields = array( 'id', 'name', 'type', 'description', 'publishsystem', 'publishsystemid' );
		$row = self::getRow( 'channels', $where, $fields, $params );
		$pubChannelObj = null;
		if( $row ) {
			$pubChannelObj = new stdClass();
			$pubChannelObj->entid = intval( $row['id'] );
			$pubChannelObj->name = $row['name'];
			$pubChannelObj->type = $row['type'];
			$pubChannelObj->description = $row['description'];
			$pubChannelObj->publishsystem = $row['publishsystem'];
			$pubChannelObj->publishsystemid = $row['publishsystemid'];
		}
		return $pubChannelObj;
	}

	/**
	 * Gets a list of editions.
	 *
	 * @param int $pubChannelId Publication channel id.
	 * @param int $issueId Issue id.
	 * @return stdClass[]
	 */
	public static function getEditions( $pubChannelId, $issueId )
	{
		$where = "`channelid` = ? AND `issueid` = ? ORDER BY `code`, `id`";
		$params = array();
		$params[] = intval( $pubChannelId );
		$params[] = intval( $issueId );

		$fields = array( 'id', 'name', 'description' );
		$rows = self::listRows( 'editions', null, null, $where, $fields, $params );
		$editionObjs = array();
		if( $rows ) foreach( $rows as $row ) {
			$obj = new stdClass();
			$obj->entid	= intval($row['id']);
			$obj->name = $row['name'];
			$obj->description = $row['description'];
			$editionObjs[] = $obj;
		}
		return $editionObjs;
	}

	/**
	 * Gets the publication channel id of the passed in issue.
	 *
	 * @param int $issueId Issue id.
	 * @return null|int Channel id.
	 */
	public static function getPubChannelId( $issueId )
	{
		$where = '`id` = ? ';
		$params = array( intval( $issueId ) );
		$fields = array( 'channelid' );
		$row = self::getRow( 'issues', $where, $fields, $params );
		return $row ? $row['channelid'] : null;
	}

	/**
	 * Gets the publication id for the passed in publication channel.
	 *
	 * @param $pubChannelId Publication channel id.
	 * @return null|int Publication id.
	 */
	public static function getPublicationId( $pubChannelId )
	{
		$where = '`id` = ? ';
		$params = array( intval($pubChannelId) );
		$fields = array( 'publicationid' );
		$row = self::getRow( 'channels', $where, $fields, $params );
		return $row ? $row['publicationid'] : null;
	}
}