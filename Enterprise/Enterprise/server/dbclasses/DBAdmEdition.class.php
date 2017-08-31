<?php

/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class DBAdmEdition extends DBBase
{
	const TABLENAME = 'editions';

	/**
	 * Creates new editions at smart_edition table owned by channel and issue.
	 *
	 * @param integer $channelId Publication channel (id) that new edition belongs to
	 * @param integer $issueId Issue (id) that new edition belongs to (in case the issue overrules the publication)
	 * @param AdmEdition[] $editions to create
	 * @return AdmEdition[] created editions
	 * @throws BizException on SQL error or when edition name already exists
	 */
	public static function createEditionsObj( $channelId, $issueId, $editions )
	{
		$dbdriver = DBDriverFactory::gen();
		$newEditions = array();

		if( $editions ) foreach( $editions as $edition ) {

			// check duplicates
			$where = '`name` = ? AND `channelid` = ? AND `issueid` = ?';
			$params = array( strval( $edition->Name ), intval( $channelId ), intval( $issueId ) );
			$row = self::getRow( self::TABLENAME, $where, array( 'id', 'name' ), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			$editionRow = self::objToRow( $edition );
			$editionRow['channelid'] = intval( $channelId );
			$editionRow['issueid'] = intval( $issueId );
			$newId = self::insertRow( self::TABLENAME, $editionRow );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}

			if( $newId ) {
				$newEditions[] = self::getEditionObj( $newId );
			}
		}
		return $newEditions;
	}

	/**
	 * Modifies an edition in the smart_editions table.
	 *
	 * @param int $channelId Publication channel that own the edition
	 * @param int $issueId Issue (id) that owns the edition
	 * @param AdmEdition[] $editions edition to modify
	 * @return AdmEdition[] modified editions
	 * @throws BizException on SQL error or when the modified edition name already exists
	 */
	public static function modifyChannelEditionsObj( $channelId, $issueId, $editions )
	{
		$modifiedEditions = array();
		if( $editions ) foreach( $editions as $edition ) {

			// check duplicates
			$where = '`name` = ? AND `issueid` = ? AND `channelid` = ? AND `id` != ?';
			$params = array( strval($edition->Name), intval($issueId), intval($channelId), intval($edition->Id) );
			$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			$editionRow = self::objToRow( $edition );
			$editionRow['channelid'] = intval( $channelId );
			$editionRow['issueid'] = intval( $issueId );
			$where = '`id` = ?';
			$params = array( intval($edition->Id) );
			$updated = self::updateRow( self::TABLENAME, $editionRow, $where, $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $updated ) {
				$modifiedEditions[] = self::getEditionObj( $edition->Id );
			}
		}
		return $modifiedEditions;
	}

	/**
	 * Retrieves an edition from the smart_editions table.
	 *
	 * @param int $editionId Id of the edition to get the values from
	 * @return AdmEdition|null The edition, or null when not found.
	 * @throws BizException on SQL error
	 */
	static public function getEditionObj( $editionId )
	{
		$where = '`id` = ?';
		$params = array( intval( $editionId ) );
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Lists all editions from smart_editions that are owned by the given issue.
	 *
	 * @param string $issueId
	 * @return AdmEdition[] The editions found. Empty when none found.
	 * @throws BizException on SQL error
	 */
	static public function listIssueEditionsObj( $issueId )
	{
		$where = "`issueid` = ? ";
		$params = array( intval($issueId) );
		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows(self::TABLENAME,null,null, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$editions = array();
		if( $rows ) {
			foreach( $rows as $row ) {
				$editions[] = self::rowToObj( $row );
			}
		}
		return $editions;
	}

	/**
	 * Lists all editions from smart_editions that are owned by the given channel.
	 * The channel->issue->editions are NOT included!
	 *
	 * @param int $channelId
	 * @return AdmEdition[] The editions found.
	 * @throws BizException on SQL error
	 */
	static public function listChannelEditionsObj( $channelId )
	{
		$where = "`channelid` = ? AND `issueid` = ?";
		$params = array( intval( $channelId ), 0 );
		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, 'id', 'name', $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$editions = array();
		if( $rows ) {
			foreach( $rows as $row ) {
				$editions[] = self::rowToObj( $row );
			}
		}
		return $editions;
	}

	/**
	 *  Converts an edition object into DB row.
	 *
	 *  @param object $obj Edition object
	 *  @return array Edition DB row
	 */
	static private function objToRow ( $obj )
	{
		$row = array();

		if( !is_null( $obj->Id ) ) {
			$row['id'] = intval( $obj->Id );
		}
		if( !is_null( $obj->Name ) ) {
			$row['name'] = strval( $obj->Name );
		}
		if( !is_null( $obj->DeadlineRelative ) ) {
			$row['deadlinerelative'] = intval( $obj->DeadlineRelative );
		}
		// a value for description is required as it is a blob
		$row['description'] = strval( $obj->Description );

		if( !is_null( $obj->SortOrder ) ) {
			$row['code'] = intval( $obj->SortOrder );
		}
		return $row;
	}

	/**
	 *  Converts an edition DB row into object.
	 *
	 *  @param array $row Edition DB row
	 *  @return AdmEdition Edition object
	 */
	static private function rowToObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$edition = new AdmEdition();
		$edition->Id               = intval($row['id']);
		$edition->Name             = strval($row['name']);
		$edition->Description      = strval($row['description']);
		$edition->DeadlineRelative = strval($row['deadlinerelative']);
		$edition->SortOrder        = intval($row['code']);
		return $edition;
	}
}