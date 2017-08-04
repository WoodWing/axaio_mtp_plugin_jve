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
	 */
	static public function getIssuesByName( $name )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbi = $dbDriver->tablename('issues');
		$sql = 'SELECT `id`, `name` FROM '.$dbi.' WHERE `name` = ? ';
		$params = array( $name );
		$sth = $dbDriver->query( $sql, $params );

		$rows = self::fetchResults( $sth );
		return $rows;	
	}

	/**
	 * Get all issues by name and channel Id.
	 *
	 * @param string $name Issue name
    * @param int $channelId
	 * @return array of issue rows
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
	 * Updates an issue in the smart_issues table with a given issue row
	 *
	 * @param int $issueId Id of the Issue to update
	 * @param array $issueRow array of values to update, indexed by fieldname. $values['issue'] = issue1, etc...
	 *        The array does NOT need to contain all values, only values that are to be updated.
	 * @return bool Returns true if succeeded, false if an error occurred.
	 */
	static public function updateIssue( $issueId, $issueRow )
	{
		if( isset($issueRow['ExtraMetaData']) ) {
			$extraMetaData = $issueRow['ExtraMetaData'];
			self::deleteRows('channeldata', "`issue` = ?", array( intval( $issueId ) ));
			foreach($extraMetaData as $name => $value) {
				$data['issue'] = $issueId;
				$data['name'] = $name;
				$data['value'] = $value;
				self::insertRow('channeldata', $data, false);
			}

			unset($issueRow['ExtraMetaData']);
		}

		if( isset($issueRow['SectionMapping']) ) {
			$sectionMapping = $issueRow['SectionMapping'];

			foreach($sectionMapping as $sectionId => $data) {
				$params = array( intval( $issueId ), intval( $sectionId ) );
				self::deleteRows('channeldata', "`issue` = ? AND `section` = ?", $params);
				foreach($data as $name => $value) {
					$row['issue'] = $issueId;
					$row['section'] = $sectionId;
					$row['name'] = $name;
					$row['value'] = $value;
					self::insertRow('channeldata', $row, false);
				}
			}

			unset($issueRow['SectionMapping']);
		}

		return self::updateRow(self::TABLENAME, $issueRow, "`id` = ?", array( intval( $issueId ) ) );
	}

	/**
	 * Retrieves one issues from smart_issues table with given id.
	 *
	 * @param int $issueId Id of the issue to get
	 * @return array issue row. Null on failure.
	 */
	static public function getIssue( $issueId )
	{
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		
		$where = '`id` = ?';
		$params = array( intval( $issueId ) );
		$result = self::getRow( self::TABLENAME, $where, '*', $params );
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
		$dbDriver = DBDriverFactory::gen();
		$dbi = $dbDriver->tablename('issues');
		$sql = "SELECT `id`, `name` FROM $dbi WHERE `id` = ?";
		$sth = $dbDriver->query($sql, array( intval( $issueId ) ) );
		$row = $dbDriver->fetch($sth);
		return $row ? $row['name'] : '';
	}

	/**
	 * Returns if the specified issue has the overrule option set
	 *
	 * @param int $issueId
	 * @return boolean True if overrule brand issue, else false
	 */
	static public function isOverruleIssue( $issueId )
	{
		if( $issueId ) {
			$dbDriver = DBDriverFactory::gen();
			$dbi = $dbDriver->tablename("issues");
			$sql = "SELECT `overrulepub` FROM $dbi WHERE `id` = ?";
			$sth = $dbDriver->query($sql, array( intval( $issueId ) ) );
			$rowi = $dbDriver->fetch($sth);
			return ($rowi['overrulepub'] == 'on');
		}
		else {
			return false;
		}
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
		$issTable = $dbDriver->tablename('issues');
		$chaTable = $dbDriver->tablename('channels');
		$issueName = $dbDriver->toDBString( trim($issueName) );
		
		$sql  = "SELECT iss.`id` ";
		$sql .= "FROM $issTable iss ";
		$sql .= "INNER JOIN $chaTable cha ON (iss.`channelid` = cha.`id`) ";
		$sql .= "WHERE cha.`publicationid` = ? AND cha.`type` = ? AND iss.`name` = ? ";
		$params = array( intval( $pubId ), strval( $channelType ), strval( $issueName ) );
	   if ( $pubChannelId ) {
	      $sql .= " AND cha.`id` = $pubChannelId ";
	      $params[] = array( intval( $pubChannelId ) );
	   }
		
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
        $dbdriver = DBDriverFactory::gen();
        $issuestable = $dbdriver->tablename(self::TABLENAME);
        $channelstable = $dbdriver->tablename('channels');

        $sql  = "SELECT i.*, ch.`name` as \"channelname\" ";
        $sql .= "FROM $channelstable ch ";
        $sql .= "INNER JOIN $issuestable i ON (i.`channelid` = ch.`id`) ";
        $sql .= "WHERE (ch.`publicationid` = ?) ";
        $params = array( intval( $pubId ) );
        if( $overruleIssOnly ) {
        	$sql .= ' AND (`overrulepub` = ? ) ';
        	$params[] = 'on';
        }
        $sql .= "ORDER BY ch.`publicationid`, ch.`code`, ch.`id`, i.`code` ";

        $sth = $dbdriver->query($sql, $params);

        $results = array();
        while (($row = $dbdriver->fetch($sth))) {
			$row['overrulepub'] = $row['overrulepub'] === 'on' ? true : false;
            $results[$row['id']] = $row;
        }
        return $results;
    }

	/**
	 * Retrieves all issue rows from smart_issues table that are owned by the given channel.
	 *
	 * @param int $channelId
	 * @return array of issue rows
	 */
	public static function listChannelIssues( $channelId )
	{
		$rows = self::listRows( self::TABLENAME,'id','name',"`channelid` = ? ORDER BY `code` ASC, `id` ASC", '*', array( intval( $channelId ) ) );
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
	 *  @return array of id's of issues that overrule
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
			$result[] = $row['id'];
		}
		return $result;
	}

	/**
	 *  Lists ALL issues that non-overrule their publication in an array.
	 *  @param int $brandId Brand Id to filter on.
	 *  @return array of id's of non-overrule brand issues.
	 */
	static public function listNonOverruleIssuesByBrand( $brandId )
	{
		$dbdriver = DBDriverFactory::gen();
		$issuestable = $dbdriver->tablename(self::TABLENAME);
		$channelstable = $dbdriver->tablename('channels');

		$sql  = "SELECT i.`id` ";
		$sql .= "FROM $issuestable i ";
		$sql .= "INNER JOIN $channelstable c ON (i.`channelid` = c.`id`) ";
		$sql .= "WHERE i.`overrulepub` = '' ";
		$sql .= "AND c.`publicationid` = ? ";
		$sth = $dbdriver->query($sql, array( intval( $brandId ) ) );
		$result = array();
		while (($row = $dbdriver->fetch($sth))) {
			$result[] = $row['id'];
		}
		return $result;
	}
	
	/**
	 *  Lists ALL issues that overrule their publication in a array
	 *  @return array (key-value), key being the issueId and value the publication id of that overrule issue
	 */
	static public function listAllOverruleIssuesWithPub()
	{
		//BZ#7258
		$dbdriver = DBDriverFactory::gen();
		$issuestable = $dbdriver->tablename(self::TABLENAME);
		$channeltable = $dbdriver->tablename('channels');
		$sql  = "SELECT issues.`id`, channels.`publicationid` ";
		$sql .= "FROM $issuestable issues ";
		$sql .= "INNER JOIN $channeltable channels ON (channels.`id` = issues.`channelid`) ";
		$sql .= "WHERE issues.`overrulepub` = 'on' AND issues.`active` = 'on' ORDER BY issues. `id` ASC";
		$sth = $dbdriver->query($sql);
		$result = array();
		while (($row = $dbdriver->fetch($sth))) {
			$result[$row['id']] = $row['publicationid'];
		}
		return $result;
	}

	/**
	  * Looks up the channel id at smart_issues table.
	  *
	  * @param $issueId int
	  * @return string Channel id. Returns null on failure.
	  */
	static public function getChannelId( $issueId )
	{
		$dbdriver = DBDriverFactory::gen();
		$issuesTable = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT `channelid` FROM $issuesTable WHERE `id` = ? ";
		$sth = $dbdriver->query( $sql, array( intval( $issueId ) ) );
		if( !$sth ) return null;
		$row = $dbdriver->fetch( $sth );
		if( !$row ) return null;
		return $row['channelid'];
	}

	/**
	 * Retrieves editions from smart_editions table owned by given issue.
	 * If issue overrules publication, the issue's edition is returned,
	 * or else the publication's edition is returned.
	 *
	 * @param int $issueId  (Also used to derive publication from.)
	 * @param boolean $noPubDefs Supresses returning editions owned by publication (issue only).
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

	public static function hasOverruleIssue($issueids)
	{
		$dbdriver = DBDriverFactory::gen();
		$issuestable = $dbdriver->tablename(self::TABLENAME);
		$sql = "SELECT `id` FROM $issuestable WHERE `overrulepub` = 'on' AND `id` IN (" . implode(',',$issueids) . ")";
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		if ($row) {
			return $row['id'];
		}
		return false;
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
		$result = self::getRow('issues', $where, 'id', array( intval( $issuedId ), 'on'));
		return $result ? true : false;
	}
}
