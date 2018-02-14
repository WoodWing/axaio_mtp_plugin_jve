<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Manages the smart_issues DB table to support admin functionality.
 * For workflow functionality, the DBIssue class must be used instead.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmIssue extends DBBase
{
	const TABLENAME = 'issues';

	/**
	 * Get an issue admin object from database (smart_issues table) for a given issue id.
	 * Also custom issue properties properties are retrieved from DB, which are returned through
	 * the issue's ExtraMetaData property.
	 * Also custom issue-section properties properties are retrieved from DB, which are returned
	 * through the issue's SectionMapping property.
	 *
	 * @param integer $issueId
	 * @return AdmIssue|null Returns NULL when issue not found.
	 * @throws BizException on SQL error.
	 */
	static public function getIssueObj( $issueId )
	{
		// Get built-in issue properties.
		$where = '`id` = ?';
		$params  = array( intval( $issueId ) );
		$issueRow = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		if( !$issueRow ) {
			return null;
		}

		// Add the custom properties
		self::getCustomPropertiesForIssue( intval($issueId), $issueRow );
		
		// Return all issue properties to caller.
		return self::rowToObj( $issueRow );
	}

	/**
	 * Adds the ExtraMetaData and SectionMapping info to the database row.
	 *
	 * @param int $issueId
	 * @param array $issueRow
	 */
	private static function getCustomPropertiesForIssue( $issueId, &$issueRow )
	{
		// Collect custom issue properties in DEBUG mode.
		$debugMode = LogHandler::debugMode();
		if( $debugMode ) {
			$customProps = array();
		}

		// Get custom issue properties.
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$extraMetaDataRows = DBChanneldata::getCustomPropertiesForIssue( $issueId );
		if( $extraMetaDataRows ) foreach( $extraMetaDataRows as $extraMetaData ) {
			if( $debugMode ) {
				/** @noinspection PhpUndefinedVariableInspection */
				// Can be assumed that this will be defined.
				$customProps[] .= $extraMetaData['name'] . ' - ' . $extraMetaData['value'];
			}
			$issueRow['ExtraMetaData'][$extraMetaData['name']] = $extraMetaData['value'];
		}

		// Get custom issue-section properties.
		$sectionMappingRows = DBChanneldata::getSectionMappingsForIssue( $issueId );
		if( $sectionMappingRows ) foreach( $sectionMappingRows as $sectionMapping ) {
			if( $debugMode ) {
				/** @noinspection PhpUndefinedVariableInspection */
				// Can be assumed that this will be defined.
				$customProps[] .= $sectionMapping['name'] . ' - ' . $sectionMapping['value'];
			}
			$issueRow['SectionMapping'][$sectionMapping['section']][$sectionMapping['name']] = $sectionMapping['value'];
		}

		// Log custom issue properties in DEBUG mode.
		if( $debugMode ) {
			/** @noinspection PhpUndefinedVariableInspection */
			// Can be assumed that this will be defined.
			if( $customProps ) {
				LogHandler::Log( __CLASS__, 'DEBUG',
					'Read custom issue (id='.$issueId.') properties from DB:<br/>'.implode( '<br/>', $customProps ) );
			} else {
				LogHandler::Log( __CLASS__, 'DEBUG',
					'There are no custom properties configured for issue (id='.$issueId.').' );
			}
		}
	}

	/**
	 * Retrieves the first configured issue object (from smart_issues table) that is active and owned by the given channel.
	 *
	 * @param int $channelId
	 * @return AdmIssue object or null when none found
	 * @throws BizException on SQL error.
	 */
	public static function getFirstActiveChannelIssueObj( $channelId )
	{
		$where = '`active` = ? and `channelid` = ?';
		$params = array( 'on', intval( $channelId ) );
		$orderBy = array( 'code' => true, 'id' => true );
		$row = self::getRow( self::TABLENAME, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 *  Retrieves all issues from smart_issues table that are owned by given channel.
	 *
	 *  @param int $channelId
	 *  @return array of issue objects. Empty when none found.
	 */
	static public function listChannelIssuesObj( $channelId )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$rows = DBIssue::listChannelIssues( $channelId );

		$issues = array();
		if( $rows ) foreach( $rows as $row ) {
			// Add the custom properties as well
			self::getCustomPropertiesForIssue( $row['id'], $row );

			$issues[] = self::rowToObj( $row );
		}
		return $issues;
	}

	/**
	 * Requests the parent publication id of an issue by the issue id.
	 *
	 * @param integer $issueId
	 * @param bool|null $isOverrule If true: Overrule issue; if false: Regular issue; if null: Either.
	 * @param bool|null $isActive If true: Active issue; if false: Non-active issue; if null: Either.
	 * @return integer|null The parent publication id of the given issue, null if not found.
	 * @since 10.2.0
	 */
	public static function getPubIdForIssueId( $issueId, $isOverrule = null, $isActive = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$issuesTable = $dbDriver->tablename( self::TABLENAME );
		$channelsTable = $dbDriver->tablename( 'channels' );
		$sql =
			"SELECT c.`publicationid` ".
			"FROM {$issuesTable} i, {$channelsTable} c ".
			"WHERE i.`channelid` = c.`id` ".
			"AND i.`id` = ? ";
		$params = array( intval( $issueId ) );

		if( $isOverrule === true ) { //overrule
			$sql .= 'AND i.`overrulepub` = ? ';
			$params[] = 'on';
		} elseif( $isOverrule === false ) {
			$sql .= 'AND i.`overrulepub` = ? ';
			$params[] = '';
		}//else: either overrule or non-overrule

		if( $isActive === true ) { //active issue
			$sql .= 'AND i.`active` = ? ';
			$params[] = 'on';
		} elseif( $isActive === false ) { //non-active issue
			$sql .= 'AND i.`active` = ? ';
			$params[] = '';
		}//else: either active or non-active

		$sth = self::query( $sql, $params );
		$row = self::fetch( $sth );
		return $row ? intval($row['publicationid']) : null;
	}

	/**
	 * Creates a new issue into smart_issues table
	 *
	 * @param int $channelId publication channel to become owner of the new issue
	 * @param AdmIssue[] $issues new issues to create
	 * @return AdmIssue[] new created issues
	 * @throws BizException on failure
	 */
	static public function createIssuesObj( $channelId, $issues )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$newIssues = array();

		foreach( $issues as $issue ) {
	
			// Substitute empty string if value null
			if(is_null($issue->Subject)) {
				$issue->Subject = '';
			}
			if(is_null($issue->Description)) {
				$issue->Description = '';
			}
			$issueRow = self::objToRow( $channelId, $issue );

			// If the array key 'ExtraMetaData' exists save it for later use and unset the value in the row
			$extraMetaData = array();
			if( array_key_exists( 'ExtraMetaData', $issueRow ) ) {
				$extraMetaData = $issueRow['ExtraMetaData'];
				unset($issueRow['ExtraMetaData']);
			}

			// If the array key 'SectionMapping' exists save it for later use and unset the value in the row
			$sectionMappingData = array();
			if( array_key_exists( 'SectionMapping', $issueRow ) ) {
				$sectionMappingData = $issueRow['SectionMapping'];
				unset($issueRow['SectionMapping']);
			}

			// check for duplicate issue within the same channel
			$where = '`channelid` = ? and `name` = ?';
			$params = array( intval( $issueRow['channelid'] ), $issueRow['name'] );
			$row = self::getRow( self::TABLENAME, $where, '*', $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null);
			}
			
			// create the issue at DB
			$issueId = self::insertRow( self::TABLENAME, $issueRow );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $issueId ) {
				// add the custom properties
				if( !empty( $extraMetaData ) ) {
					foreach( $extraMetaData as $name => $value ) {
						DBChanneldata::insertCustomPropertyForIssue( $issueId, $name, $value );
					}
				}

				// add section mapping
				if( !empty( $sectionMappingData ) ) {
					foreach( $sectionMappingData as $sectionId => $data ) {
						foreach( $data as $name => $value ) {
							DBChanneldata::insertSectionMappingsForIssue( $issueId, $sectionId, $name, $value );
						}
					}
				}

				$newIssues[] = self::getIssueObj( $issueId );
			}
		}
		return $newIssues;
	}

	/**
	 * Modifies issue objects
	 *
	 * @param int $channelId
	 * @param array $issues  Values to modify existing issue
	 * @return array of modified AdmIssue objects
	 * @throws BizException on failure
	**/
	 public static function modifyIssuesObj( $channelId, $issues )
	{
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$modifyissues = array();

		foreach( $issues as $issue ) {
			$issueRow = self::objToRow( $channelId, $issue );

			// check for duplicates within the same channel
			$where = "`name` = ? AND `channelid` = ? AND `id` != ?";
			$params = array( $issue->Name, intval( $issueRow['channelid'] ), intval( $issue->Id ) );
			$row = self::getRow( self::TABLENAME, $where, '*', $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null);
			}

			if( isset($issueRow['ExtraMetaData']) ) {
				$extraMetaData = $issueRow['ExtraMetaData'];
				foreach( $extraMetaData as $name => $value ) {
					$exists = DBChanneldata::channelDataExists($issue->Id, 0, $name);
					if ( $exists ) {					 	
					 	DBChanneldata::updateCustomPropertyForIssue( $issue->Id, $name, $value );
					} else {
						DBChanneldata::insertCustomPropertyForIssue( $issue->Id, $name, $value );
					}
				}
				unset( $issueRow['ExtraMetaData'] );
			}
				
				

			if( isset($issueRow['SectionMapping']) ) {
				$sectionMapping = $issueRow['SectionMapping'];

				foreach( $sectionMapping as $sectionId => $data ) {
					DBChanneldata::deleteSectionMappingsForIssue( $issue->Id, $sectionId );
					foreach( $data as $name => $value ) {
						DBChanneldata::insertSectionMappingsForIssue( $issue->Id, $sectionId, $name, $value );
					}
				}

				unset( $issueRow['SectionMapping'] );
			}

			$where = '`id` = ?';
			$params = array( $issue->Id );
			$result = self::updateRow( self::TABLENAME, $issueRow, $where, $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $result === true){
				$modifyissue = self::getIssueObj( $issue->Id );
				$modifyissues[] = $modifyissue;
			}
		}
		return $modifyissues;
	}

	/**
	 * Calculate the issue count for each brand.
	 *
	 * @since 10.2.0
	 * @param integer[] $pubIds
	 * @return array with publication ids in keys and issue counts in values.
	 */
	static public function  countIssuesPerPublication( $pubIds )
	{
		$rows = null;
		$where = self::addIntArrayToWhereClause( 'pub.id', $pubIds );
		if( $where ) {
			$dbh = DBDriverFactory::gen();
			$publicationTable = $dbh->tablename( 'publications' );
			$channelsTable = $dbh->tablename( 'channels' );
			$issuesTable = $dbh->tablename( 'issues' );
			$sql = "SELECT COUNT(1) AS `issuecnt`, pub.`id` AS `pubid` FROM {$issuesTable} iss ".
				"LEFT JOIN {$channelsTable} chn ON ( chn.`id` = iss.`channelid` ) ".
				"LEFT JOIN {$publicationTable} pub ON ( pub.`id` = chn.`publicationid` ) ".
				"WHERE $where ".
				"GROUP BY pub.`id` ";
			$sth = self::query( $sql );
			$rows = self::fetchResults( $sth );
		}

		$result = array();
		if( $rows ) {
			$result = array_combine( $pubIds, array_fill( 0, count( $pubIds ), 0 ) );
			foreach( $rows as $row ) {
				$result[ $row['pubid'] ] = $row['issuecnt'];
			}
		}
		return $result;
	}

	/**
	 *  Converts an issue object value to an array
	 *  It return an array with the mapping value for object to row
	 *
	 *  @param  string $channelId Channel id
	 *  @param  object $obj issue object
	 *  @return array of issue value
	 */
	static public function objToRow( $channelId, $obj )
	{
		$fields = array();

		if(!is_null($obj->Name)){
			$fields['name']		 	= strval($obj->Name);
		}

		if (!is_null($channelId)) {
			$fields['channelid'] = intval($channelId);
		}

		if(!is_null($obj->PublicationDate)){
			$fields['publdate']			= $obj->PublicationDate ? strval($obj->PublicationDate) : '';
		}

		if(!is_null($obj->Deadline)){
			$fields['deadline']			= $obj->Deadline ? strval($obj->Deadline) : '';
		}

		if(!is_null($obj->ExpectedPages)){
			$fields['pages'] 	  		= (is_numeric($obj->ExpectedPages) ? intval($obj->ExpectedPages) : 0);
		}

		if(!is_null($obj->Subject)){
			$fields['subject']			= $obj->Subject ? strval($obj->Subject) : '';
		}

		if(!is_null($obj->Description)){
			$fields['description'] 		= $obj->Description ? strval($obj->Description) : '';
		}

		if(!is_null($obj->Activated)){
			$fields['active']			= ($obj->Activated === true ? 'on' : '');
		}

		if(!is_null($obj->ReversedRead)){
			$fields['readingorderrev'] 	= ($obj->ReversedRead === true ? 'on' : '');
		}

		if(!is_null($obj->OverrulePublication)){
			$fields['overrulepub'] 		= ($obj->OverrulePublication === true ? 'on' : '');
		}

		if(!is_null($obj->SortOrder)){
			$fields['code'] 			= (is_numeric($obj->SortOrder )? intval($obj->SortOrder) : 0);
		}

		if( !is_null( $obj->CalculateDeadlines ) ) {
			$fields['calculatedeadlines'] = ( $obj->CalculateDeadlines == true ? 'on' : '' );
		}

		// section mapping
		if( isset($obj->SectionMapping) ) { // isset also check for null values
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php'; // TODO: DB calling Biz = against architecture!
			BizAdmProperty::enrichDBRowWithSectionMapping( $obj->SectionMapping, $fields );
		}

		// custom admin properties
		if( isset($obj->ExtraMetaData) ) { // isset also check for null values
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php'; // TODO: DB calling Biz = against architecture!
			/** @noinspection PhpDeprecationInspection */
			// This can be ignored by the analyzer, the deprecated function should not be called by the functions going
			// to be created in the future, the existing ones will still use the deprecated ones.
			BizAdmProperty::enrichDBRowWithCustomMetaData( 'Issue', $obj->ExtraMetaData, $fields );
		}
		return $fields;
	}

	/**
	 *  Converts row value to an object
	 *  It return an object with the mapping value for row to object
	 *  @param array $row row contains key values
	 *  @return AdmIssue of issue
	 */
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$issue = new AdmIssue();
		$issue->Id 					= intval($row['id']);
		$issue->Name				= $row['name'];
		$issue->Description			= $row['description'];
		$issue->PublicationDate		= $row['publdate'];
		$issue->Deadline			= $row['deadline'];
		$issue->ExpectedPages		= $row['pages'];
		$issue->Subject				= $row['subject'];
		$issue->Activated			= ($row['active'] == 'on' ? true : false);
		$issue->ReversedRead		= ($row['readingorderrev'] == 'on' ? true : false);
		$issue->OverrulePublication = ($row['overrulepub'] == 'on' ? true : false);
		$issue->SortOrder			= intval($row['code']);
		$issue->CalculateDeadlines   = ( $row['calculatedeadlines'] == 'on' ? true : false );

		// custom admin properties
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php'; // TODO: DB calling Biz = against architecture!
		if( isset( $row['ExtraMetaData'] ) ) {
			/** @noinspection PhpDeprecationInspection */
			// This can be ignored by the analyzer, the deprecated function should not be called by the functions going
			// to be created in the future, the existing ones will still use the deprecated ones.
			$issue->ExtraMetaData = BizAdmProperty::buildCustomMetaDataFromDBRow( 'Issue', $row['ExtraMetaData'] );
		} else {
			$issue->ExtraMetaData = array();
		}

		// section mapping
		if( isset( $row['SectionMapping'] ) ) {
			$issue->SectionMapping = BizAdmProperty::buildSectionMappingFromDBRow( $row['SectionMapping'] );
		} else {
			$issue->SectionMapping = array();
		}

		return $issue;
	}
}
