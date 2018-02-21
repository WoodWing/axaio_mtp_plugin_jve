<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Manages the smart_issues DB table to support workflow functionality.
 * For admin functionality, the DBAdmIssue class must be used instead.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBIssue extends DBBase
{
	const TABLENAME = 'issues';

	/**
	 * Get all issues by name
	 *
	 * @param string $name Issue name
	 * @return array of issue rows
	 * @throws BizException on SQL error
	 */
	static public function getIssuesByName( $name )
	{
		$where = '`name` = ?';
		$params = array( strval($name) );
		$rows = self::listRows( self::TABLENAME, 'id', 'name', $where, null, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $rows;
	}

	/**
	 * Get all issues by name and channel Id.
	 *
	 * @param string $name Issue name
    * @param int $channelId
	 * @return array of issue rows
	 * @deprecated since 10.2.0, seems no longer called
	 */
	static public function getIssuesByNameAndChannel( $name, $channelId )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbi = $dbDriver->tablename('issues');
		$sql = 'SELECT `id`, `name` FROM '.$dbi.' WHERE `name` = ? AND `channelid` = ? ';
		$params = array( $name, $channelId );
		$sth = $dbDriver->query( $sql, $params );

		$rows = self::fetchResults( $sth );
		return $rows;
	}

	/**
	 * Resolve the Issue names ($issueNames) into its corresponding Issue id(s).
	 *
	 * Function iterates through the list of QueryParams passed in.
	 * It searches for Publication id(s) and Publication Channel id(s) if there're any.
	 * When found, the two will be taken into account as well
	 * when resolving the Issue names into the Issue ids.
	 *
	 * Nevertheless, it is assumed that for the QueryParam passed in,
	 * the Publication(s) and Publication channel(s) are already resolved into its id(s).
	 *
	 * @param string[] $issueNames The list of Issue Names of which its corresponding ids should be resolved.
	 * @param QueryParam[] $params List of QueryParams.
	 * @return int[] List of resolved issue ids.
	 * @throws BizException
	 */
	public static function resolveIssueIdsByNameAndParams( $issueNames, $params )
	{
		if( !$issueNames ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server',
				'Issue names parameter is mandatory for resolveIssueIdsByNameAndParams().' );
		}

		// Search if there's any Pub id(s) or PubChannel id(s).
		$pubIds = array();
		$channelIds = array();
		if( $params ) foreach( $params as $param ) {
			if( strtolower($param->Property == 'PublicationId' ) && $param->Operation == '=' ) {
				$pubIds[] = $param->Value;
			}
			if( strtolower($param->Property == 'PublicationIds' ) && $param->Operation == '=' ) {
				$pubIds = array_merge( $pubIds, explode( ',', $param->Value ) );
			}
			if( strtolower($param->Property == 'PubChannelId' ) && $param->Operation == '=' ) {
				$channelIds[] = $param->Value;
			}
			if( strtolower($param->Property == 'PubChannelIds' ) && $param->Operation == '=' ) {
				$channelIds = array_merge( $channelIds, explode( ',', $param->Value ) );
			}
		}

		$dbDriver = DBDriverFactory::gen();
		$dbi = $dbDriver->tablename( self::TABLENAME );
		$dbc = $dbDriver->tablename( 'channels' );
		$where = array();
		$joins = array();
		$params = array();
		// Publication Id(s) if there's any.
		if( $pubIds ) {
			$tmpWhere = array();
			$joins[] = "LEFT JOIN $dbc cha ON ( iss.`channelid` = cha.`id` ) ";
			foreach( $pubIds as $pubId ) {
				$tmpWhere[] = "cha.`publicationid` = ? ";
				$params[] = intval( $pubId );
			}

			if( $tmpWhere ) {
				$where[] = "( " . implode( " OR ", $tmpWhere ) . " ) ";
			}
		}
		// Publication Channel Id(s) if there's any.
		if( $channelIds ) {
			$tmpWhere = array();
			foreach( $channelIds as $channelId ) {
				$tmpWhere[] = "iss.`channelid` = ? ";
				$params[] = intval( $channelId );
			}

			if( $tmpWhere ) {
				$where[] = "( " . implode( " OR ", $tmpWhere ) . " ) ";
			}
		}

		// The compulsory Issue Name(s).
		$nameValues = array( 'iss.`name`' => $issueNames );
		$nameParams = array();
		$where[] = self::makeWhereForSubstitutes( $nameValues, $nameParams ) ;
		$params = array_merge( $params, $nameParams );

		// Compose the Sql
		$sql = "SELECT iss.`id` FROM $dbi iss ";
		$sql .= implode( " ", $joins ); // the join(s)
		$sql .= "WHERE " . implode( " AND ", $where ); // the where(s)

		$sth = $dbDriver->query( $sql, $params );
		$rows = self::fetchResults( $sth );
		$issueIds = array();
		if( $rows ) foreach( $rows as $row ) {
			$issueIds[] = $row['id'];
		}
		return $issueIds;
	}

	/**
	 * Updates an issue in the smart_issues table with a given issue row
	 *
	 * @param int $issueId Id of the Issue to update
	 * @param array $issueRow array of values to update, indexed by fieldname. $values['issue'] = issue1, etc...
	 *        The array does NOT need to contain all values, only values that are to be updated.
	 * @return bool Returns true if succeeded, false if an error occurred.
	 * @throws BizException on SQL error
	 */
	static public function updateIssue( $issueId, $issueRow )
	{
		if( isset( $issueRow['ExtraMetaData'] ) ) {
			$extraMetaData = $issueRow['ExtraMetaData'];
			$where = '`issue` = ?';
			$params = array( intval( $issueId ) );
			self::deleteRows( 'channeldata', $where, $params );
			foreach( $extraMetaData as $name => $value ) {
				$row = array();
				$row['issue'] = intval( $issueId );
				$row['name'] = strval( $name );
				self::insertRow( 'channeldata', $row, false, $value );
				if( self::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
				}
			}
			unset( $issueRow['ExtraMetaData'] );
		}

		if( isset( $issueRow['SectionMapping'] ) ) {
			$sectionMapping = $issueRow['SectionMapping'];
			foreach( $sectionMapping as $sectionId => $data ) {
				$where = '`issue` = ? AND `section` = ?';
				$params = array( intval( $issueId ), intval( $sectionId ) );
				self::deleteRows( 'channeldata', $where, $params );
				foreach( $data as $name => $value ) {
					$row = array();
					$row['issue'] = intval( $issueId );
					$row['section'] = intval( $sectionId );
					$row['name'] = strval( $name );
					self::insertRow( 'channeldata', $row, false, $value );
					if( self::hasError() ) {
						throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
					}
				}
			}
			unset( $issueRow['SectionMapping'] );
		}

		return self::updateRow(self::TABLENAME, $issueRow, "`id` = ?", array( intval( $issueId ) ) );
	}

	/**
	 * Retrieves one issues from smart_issues table with given id.
	 *
	 * @param int $issueId Id of the issue to get
	 * @return array issue row. Null when not found.
	 * @throws BizException on SQL error
	 */
	static public function getIssue( $issueId )
	{
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		
		$where = '`id` = ?';
		$params = array( intval( $issueId ) );
		$result = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		if( $result ) {
			$result['overrulepub'] = $result['overrulepub'] === 'on' ? true : false;
			$channel = DBChannel::getChannel( $result['channelid'] );
			if( $channel ) {
				$result['publication'] = $channel['publicationid'];
				return $result;
			}
		}
		return null; // failed
	}

	static public function getIssueName( $issueId )
	{
		$fieldNames = array( 'id', 'name' );
		$where = '`id` = ?';
		$params = array( intval( $issueId ) );
		$row = self::getRow( self::TABLENAME, $where, $fieldNames, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? $row['name'] : '';
	}

	/**
	 * Returns if the specified issue has the overrule option set
	 *
	 * @param int $issueId
	 * @return boolean True if the issue is an overule brand issue, else false.
	 * @throws BizException on SQL error
	 */
	static public function isOverruleIssue( $issueId )
	{
		$fieldNames = array( 'overrulepub' );
		$where = '`id` = ?';
		$params = array( intval( $issueId ) );
		$row = self::getRow( self::TABLENAME, $where, $fieldNames, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? $row['overrulepub'] == 'on' : false;
	}

	/**
	 * Name based search for an issue owned by given channel ($channelType) which belongs to 
	 * given brand ($pubId).
	 *
	 * @param $pubId integer Id of brand that owns the channel
	 * @param $channelType string Type of channel that owns the issue (e.g. 'print', 'web', etc)
	 * @param $issueName string Name of issue to search for
	 * @param $pubChannelId integer Id of the channel that owns the channel
	 * @return array database row or null if nothing found.
	 */
	static public function findIssueId( $pubId, $channelType, $issueName, $pubChannelId = 0 )
	{
		$dbDriver = DBDriverFactory::gen();
		$issTable = $dbDriver->tablename( self::TABLENAME );
		$chaTable = $dbDriver->tablename( 'channels' );

		$sql = "SELECT iss.`id` ".
				"FROM $issTable iss ".
				"INNER JOIN $chaTable cha ON (iss.`channelid` = cha.`id`) ".
				"WHERE cha.`publicationid` = ? AND cha.`type` = ? AND iss.`name` = ? ";
		$params = array( intval( $pubId ), strval( $channelType ), strval( trim( $issueName ) ) );
		if( $pubChannelId ) {
			$sql .= " AND cha.`id` = ? ";
			$params[] = intval( $pubChannelId );
		}

	      $sql .= " AND cha.`id` = ? ";
	      $params[] = intval( $pubChannelId );
		$sth = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch( $sth );
		return $row ? $row['id'] : null;
	}

	/**
	 * Retrieves all issues from smart_issues table that are owned by given publication.
	 * The name of channel is resolved and returned as 'channelname'.
	 *
	 * @param int $pubId
	 * @param boolean $overruleIssOnly Set to True when only overrule issue is needed; false(default) when ALL issues needed.
	 * @return array of issue rows
	 */
	public static function listPublicationIssues( $pubId, $overruleIssOnly=false )
	{
		$dbDriver = DBDriverFactory::gen();
		$issuesTable = $dbDriver->tablename( self::TABLENAME );
		$channelsTable = $dbDriver->tablename( 'channels' );

		$sql = "SELECT i.*, ch.`name` as \"channelname\" ".
				"FROM {$channelsTable} ch ".
				"INNER JOIN {$issuesTable} i ON (i.`channelid` = ch.`id`) ".
				"WHERE ch.`publicationid` = ? ";
		$params = array( intval( $pubId ) );
		if( $overruleIssOnly ) {
			$sql .= ' AND `overrulepub` = ? ';
			$params[] = 'on';
		}
		$sql .= "ORDER BY ch.`publicationid`, ch.`code`, ch.`id`, i.`code` ";
		$sth = $dbDriver->query( $sql, $params );

		$results = array();
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$row['overrulepub'] = $row['overrulepub'] === 'on' ? true : false;
			$results[ $row['id'] ] = $row;
		}
		return $results;
	}

	/**
	 * Retrieves all issue rows from smart_issues table that are owned by the given channel.
	 *
	 * @param int $channelId
	 * @return array of issue rows
	 * @throws BizException on SQL error
	 */
	public static function listChannelIssues( $channelId )
	{
		$where = '`channelid` = ?';
		$params = array( intval( $channelId ) );
		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, 'id', null, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $rows;
	}

	/**
	 * Resolves issue ids from the list of given object ids.
	 * The issue must be overrule issue (in which there can be only one issue (target) per object.)
	 * Function returns the list of object ids and its corresponding overrule issue id, objects that
	 * don't have overruleIssue will not be returned.
	 *
	 * @param int[] $objectIds List of object ids.
	 * @return null|array List of object ids and its corresponding overrule issue id. Null when there's error.
	 */
	public static function getOverruleIssueIdsFromObjectIds( $objectIds )
	{
		$results = array();
		if( $objectIds ) {
			$dbDriver = DBDriverFactory::gen();

			$selectSql = 'SELECT tar.`objectid`, tar.`issueid` ';
			$sql = self::getOverruleissueFromObjectIdsSQL( $objectIds, $selectSql );
			$sth = $dbDriver->query($sql);

			if( is_null( $sth )) {
				$results = null;
			} else {
				while (($row = $dbDriver->fetch($sth))) {
					$results[$row['objectid']] = $row['issueid'];
				}
			}
		}
		return $results;
	}

	/**
	 * Checks whether the list of object ids passed in has the same overrule issue.
	 *
	 * **When the function detected there are mixture of overrule and normal workflow objects, it will also return false.
	 *
	 * @param int[] $objectIds List of Object ids.
	 * @return null|bool True when all object ids have the same overruleissue, false otherwise(**Refer to function header). Null when none overrule objects found.
	 */
	public static function isSameOverruleIssue( $objectIds )
	{
		// Check if all the object ids passed in are overrule objects.
		$overruleIssueObjIds = self::getOverruleIssueIdsFromObjectIds( $objectIds );
		$nonOverruleIssueIds = array_diff( $objectIds, array_keys( $overruleIssueObjIds ) );

		$totalIncomingObjIds = count( $objectIds );
		$totalOverruleIssueObjIds = count( $overruleIssueObjIds );
		$totalNonOverruleIssueIds = count( $nonOverruleIssueIds );
		$result = false; // If there is a mixture of overrule issues with or without normal issues, return false

		if( $totalNonOverruleIssueIds == 0 &&
			$totalIncomingObjIds == $totalOverruleIssueObjIds ) { // Only proceed to check if $objectIds are from the same overruleissue if they are all overruleissue objects.
			$dbDriver = DBDriverFactory::gen();

			$selectSql = 'SELECT DISTINCT tar.`issueid` ';
			$getOverruleissueObjectIdsSql = self::getOverruleissueFromObjectIdsSQL( $objectIds, $selectSql );

			$sql =  'SELECT COUNT( iss.`issueid` ) AS `totalcount` FROM ';
			$sql .=	'(' . $getOverruleissueObjectIdsSql . ') iss ';
			$sth = $dbDriver->query($sql);
			$row = $dbDriver->fetch($sth);

			if ( $row ) {
				if( $row['totalcount'] == 1 ) {
					$result = true; // All objects have the same overruleissue.
				}
			}
		} else if( $totalOverruleIssueObjIds == 0 &&
			$totalNonOverruleIssueIds == $totalIncomingObjIds ) {
			$result = null; // No overruleissues found in the list of objects given.
		}
		return $result;
	}

	/**
	 * Compose a sql which will get all the overruleissues for list of object ids.
	 *
	 * $selectSql: The caller forms the 'select' sql statement since each caller might want to
	 * have different fields being retrieved.
	 * Example of $selectSql:
	 * - 'SELECT DISTINCT tar.`issueid` '
	 * - 'SELECT tar.`objectid`, tar.`issueid` '
	 *
	 * The 'FROM', 'JOIN', and 'WHERE' clause are fixed in the sql statement, which is as below:
	 *  FROM `smart_targets` tar
	 *  INNER JOIN `smart_channels` cha ON ( cha.`id` = tar.`channelid` )
	 *  LEFT JOIN `smart_issues` iss ON ( iss.`id` = tar.`issueid` )
	 *  WHERE tar.`objectid` IN ( 123, 456, 999 ) AND iss.`overrulepub` = 'on'
	 *
	 * So, if $selectSql = 'SELECT DISTINCT tar.`issueid` '
	 * Function returns the following sql statement:
	 *  SELECT DISTINCT tar.`issueid`
	 *  FROM `smart_targets` tar
	 *  INNER JOIN `smart_channels` cha ON ( cha.`id` = tar.`channelid` )
	 *  LEFT JOIN `smart_issues` iss ON ( iss.`id` = tar.`issueid` )
	 *  WHERE tar.`objectid` IN ( 123, 456, 999 ) AND iss.`overrulepub` = 'on'
	 *
	 * @param int[] $objectIds List of Object ids.
	 * @param string $selectSql Refer to function header.
	 * @return string The sql syntax to retrieve overruleissues from the list of object ids.
	 */
	private static function getOverruleissueFromObjectIdsSQL( $objectIds, $selectSql )
	{
		$dbDriver = DBDriverFactory::gen();
		$targetsTable = $dbDriver->tablename( 'targets' );
		$channelsTable = $dbDriver->tablename( 'channels' );
		$issuesTable = $dbDriver->tablename( self::TABLENAME );

		$sql = $selectSql;
		$sql .= 'FROM '.$targetsTable.' tar ';
		$sql .= 'INNER JOIN '.$channelsTable.' cha ON ( cha.`id` = tar.`channelid` ) ';
		$sql .= 'LEFT JOIN '.$issuesTable.' iss ON ( iss.`id` = tar.`issueid` ) ';
		$sql .= 'WHERE tar.`objectid` IN ( '.implode( ',', $objectIds ).' ) AND iss.`overrulepub` = \'on\' ';

		return $sql;
	}

	/**
	 *  Lists ALL issues that overrule their publication in a array
	 *
	 *  @return integer[] of id's of issues that overrule
	 */
	static public function listAllOverruleIssues()
	{
		//BZ#7258
		$dbdriver = DBDriverFactory::gen();
		$issuestable = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT `id` FROM $issuestable WHERE `overrulepub` = 'on' ORDER BY `id` ASC";
		$sth = $dbdriver->query($sql);
		$result = array();
		while (($row = $dbdriver->fetch($sth))) {
			$result[] = intval($row['id']);
		}
		return $result;
	}

	/**
	 *  Lists ALL issues that non-overrule their publication in an array.
	 *  @param int $brandId Brand Id to filter on.
	 *  @return integer[] of id's of non-overrule brand issues.
	 */
	static public function listNonOverruleIssuesByBrand( $brandId )
	{
		$dbDriver = DBDriverFactory::gen();
		$issuesTable = $dbDriver->tablename( self::TABLENAME );
		$channelsTable = $dbDriver->tablename( 'channels' );

		$sql = "SELECT i.`id` ".
				"FROM {$issuesTable} i ".
				"INNER JOIN {$channelsTable} c ON (i.`channelid` = c.`id`) ".
				"WHERE i.`overrulepub` = ? AND c.`publicationid` = ? ";
		$params = array( '', intval( $brandId ) );
		$sth = $dbDriver->query( $sql, $params );

		$result = array();
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$result[] = intval( $row['id'] );
		}
		return $result;
	}
	
	/**
	 * Lists ALL issues that overrule their publication in a array
	 *
	 * @param bool $onlyActive Return only active overrule brand issues. Default is false.
	 * @return array (key-value), key being the issueId and value the publication id of that overrule issue
	 */
	static public function listAllOverruleIssuesWithPub( $onlyActive = false )
	{
		//BZ#7258
		$dbDriver = DBDriverFactory::gen();
		$issuesTable = $dbDriver->tablename( self::TABLENAME );
		$channelsTable = $dbDriver->tablename( 'channels' );
		$params = array();

		$sql = "SELECT issues.`id`, channels.`publicationid` ".
				"FROM {$issuesTable} issues ".
				"INNER JOIN {$channelsTable} channels ON (channels.`id` = issues.`channelid`) ".
				"WHERE issues.`overrulepub` = ? ";
				$params[] = 'on';
				if( $onlyActive ) {
					$sql .= "AND issues.`active` = ? ";
					$params[] = 'on';
				}
		$sql .= "ORDER BY issues.`id` ASC ";
		$sth = $dbDriver->query( $sql, $params );

		$result = array();
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$result[ intval( $row['id'] ) ] = intval( $row['publicationid'] );
		}
		return $result;
	}

	/**
	 * Looks up the channel id at smart_issues table.
	 *
	 * @param $issueId int
	 * @return integer Channel id or null when not found.
	 * @throws BizException on SQL error
	 */
	static public function getChannelId( $issueId )
	{
		$fieldNames = array( 'channelid' );
		$where = '`id` = ?';
		$params = array( intval( $issueId ) );
		$row = self::getRow( self::TABLENAME, $where, $fieldNames, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? intval( $row['channelid'] ) : null;
	}

	/**
	 * Retrieves editions from smart_editions table owned by given issue.
	 * If issue overrules publication, the issue's edition is returned,
	 * or else the publication's edition is returned.
	 *
	 * @param int $issueId  (Also used to derive publication from.)
	 * @param boolean $noPubDefs Suppresses returning editions owned by publication (issue only).
	 * @return array List of edition DB rows.
	 */
	public static function listIssueEditionDefs( $issueId, $noPubDefs = false )
	{
		$issueRow = self::getIssue( $issueId );
		if( $issueRow['overrulepub'] === true ) {
			return self::listRows( 'editions','id','name',"`issueid` = ? ORDER BY `code` ASC", '*', array( intval( $issueId ) ) );
		} else {
			require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
			return $noPubDefs ? null : DBEdition::listPublEditions( $issueRow['publication'] );
		}
	}

	public static function hasOverruleIssue( $issueIds )
	{
		$whereIds = self::addIntArrayToWhereClause( 'id', $issueIds );
		if( !$whereIds ) {
			return false;
		}
		$fieldNames = array( 'id' );
		$where = "`overrulepub` = ? AND $whereIds ";
		$params = array( 'on' );
		$row = self::getRow( self::TABLENAME, $where, $fieldNames, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? intval( $row['id'] ) : false;
	}

	/**
	 * Method returns a list of overrule issues. If no publication(s) or issue(s)
	 * are passed all overule issues are returned. If publications are passed then
	 * the overrule issues of these publications are returned. If issues are passed
	 * we only look which of these issues are overrule issues.
	 *
	 * @param array $publications if empty all publications are taken into account
	 * @param array $issues if empty no filter on specific issues
	 * @return array of overrule issue (id's).
	 */
	static public function listOverruleIssueIds($publications = array(), $issues = array())
	{
		$dbdriver = DBDriverFactory::gen();
		$issuestable = $dbdriver->tablename(self::TABLENAME);
		$channelstable = $dbdriver->tablename('channels');
		$result = array();

		$whereinpublications = '';
		if (!empty($publications)) {
			$whereinpublications = implode(',', $publications);
		}
		$whereinissues = '';
		if (!empty($issues)) {
			$whereinissues = implode(',', $issues);
		}

		$sql  = "SELECT i.`id` ";
		$sql .= "FROM $issuestable i ";
		if ($publications) {$sql .= "INNER JOIN $channelstable c ON (i.`channelid` = c.`id`) ";}
		$sql .= "WHERE i.`overrulepub` = 'on' ";
		if ($issues) {$sql .= "AND i.`id` IN ($whereinissues) ";}
		if ($publications) {$sql .= "AND c.`publicationid` IN ($whereinpublications) ";}

		$sth = $dbdriver->query($sql);
		$rows = self::fetchResults($sth);
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				$result[] = $row['id'];
			}
		}

		return $result;
	}

	/**
	 * Clears the issue deadline property and removes all its related deadline information, including
	 * category/status/object deadlines. This affects absolute deadlines only and does not touch
	 * relative deadlines configured at brand level nor for statuses.
	 *
	 * @param int $issueId
	 */
	static public function deleteDeadlines( $issueId )
	{
		$dbDriver = DBDriverFactory::gen();
		$issues = $dbDriver->tablename(self::TABLENAME);
		$issueeditions = $dbDriver->tablename('issueeditions');
		$issuesection = $dbDriver->tablename('issuesection');
		$issuesectionstate = $dbDriver->tablename('issuesectionstate');

		$sql = "UPDATE $issues SET `deadline` = '' WHERE `id` = ? ";
		$dbDriver->query($sql, array( intval( $issueId ) ));

		$sql = "DELETE FROM $issueeditions WHERE `issue` = ? ";
		$dbDriver->query($sql, array( intval( $issueId ) ) );

		$sql = "DELETE FROM $issuesection WHERE `issue` = $issueId ";
		$dbDriver->query($sql);

		//IssueSectionState table holds both relative deadlines (timedifferences) for overruling issues
		//as well as deadlines (datetimes). Here be sure to delete only the deadlines by requiring
		//deadlinerelative = 0 => This can never destroy the relative deadlines as a non-existing row
		//is interpreted as having an deadlinerelative of 0.
		$sql = "DELETE FROM $issuesectionstate WHERE `issue` = ? AND `deadlinerelative` = 0 ";
		$dbDriver->query($sql, array( intval( $issueId ) ) );
	}
	
	/**
	 * This function checks whether the given issue id
	 * is in active or inactive status.
	 *
	 * @param int $issuedId Database of the issue ID.
	 * @return  boolean True when active, False otherwise.
	 */
	static public function isIssueActive( $issuedId )
	{
		$where = " `id` = ? AND `active` = ? ";
		$params = array( intval( $issuedId ), 'on' );
		$result = self::getRow( 'issues', $where, array( 'id' ), $params );
		return $result ? true : false;
	}

	/**
	 * Get overrule publication info fields by their object ids.
	 *
	 * This function returns an array in the following format:
	 * $row[objectId][pubid]
	 *               [pubname]
	 *               [issueid]
	 *               [issuename]
	 *
	 * @since 10.2.0
	 * @param array $objectIds
	 * @return array
	 */
	public static function getOverrulePublicationsByObjectIds( array $objectIds )
	{
		$result = array();
		$dbDriver = DBDriverFactory::gen();
		$objects = $dbDriver->tablename( 'objects' );
		$targets = $dbDriver->tablename( 'targets' );
		$issues = $dbDriver->tablename( 'issues' );

		$params = array( 'on' );
		$sql = 'SELECT o.`id`, o.`publication` AS pubid, p.`publication` AS pubname, t.`issueid` AS issueid, i.`name` AS issuename '.
			'FROM `smart_objects` o '.
			'LEFT JOIN `smart_targets` t ON o.`id` = t.`objectid` '.
			'LEFT JOIN `smart_issues` i ON t.`issueid` = i.`id` '.
			'LEFT JOIN `smart_publications` p ON p.`id` = o.`publication` '.
			'WHERE i.`overrulepub` = ? '.
			'AND ' . self::addIntArrayToWhereClause( 'o.id', $objectIds );

		$sth = $dbDriver->query( $sql, $params );
		if( $sth ) {
			while( $row = $dbDriver->fetch( $sth ) ) {
				$result[$row['id']] = $row;
			}
		}
		return $result;
	}
}