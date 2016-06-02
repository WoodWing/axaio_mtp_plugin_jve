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
	 */
	static public function getIssueObj( $issueId )
	{
		// Get built-in issue properties.
		$where = '`id` = ?';
		$params  = array( intval( $issueId ) );
		$issueRow = self::getRow( self::TABLENAME, $where, '*', $params );
		if( !$issueRow ) {
			return null;
		}

		// Add the custom properties
		self::getCustomPropertiesForIssue( $issueId, $issueRow );
		
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
	 * @return Issue object or null when none found
	 */
	public static function getFirstActiveChannelIssueObj( $channelId )
	{
		$where = '`active` = ? and `channelid` = ?';
		$params = array( 'on', intval( $channelId ) );
		$orderBy = array( 'code' => true, 'id' => true );
		$row = self::getRow( self::TABLENAME, $where, '*', $params, $orderBy );
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 *  Retrieves all issues from smart_issues table that are owned by given channel.
	 *
	 *  @param int $channelId
	 *  @return array of issue objects. Empty when none found. Null on failure.
	 */
	static public function listChannelIssuesObj( $channelId )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$rows = DBIssue::listChannelIssues( $channelId );
		if (!$rows) return null;

		$issues = array();
		foreach( $rows as $row ) {
			// Add the custom properties as well
			self::getCustomPropertiesForIssue( $row['id'], $row );

			$issues[] = self::rowToObj( $row );
		}
		return $issues;
	}


	/**
	 * Creates a new issue into smart_issues table
	 *
	 * @param int $channelId publication channel to become owner of the new issue
	 * @param array $issues array of new sections to create
	 * @return array of new created AdmIssue objects
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
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null);
			}
			
			// create the issue at DB
			$issueId = self::insertRow( self::TABLENAME, $issueRow );
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

			$result = self::updateRow( self::TABLENAME, $issueRow, " `id` = '$issue->Id'" );
			if( $result === true){
				$modifyissue = self::getIssueObj( $issue->Id );
				$modifyissues[] = $modifyissue;
			}
		}
		return $modifyissues;
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
			$fields['name']		 	= $obj->Name;
		}

		if (!is_null($channelId)) {
			$fields['channelid'] = $channelId;
		}

		if(!is_null($obj->PublicationDate)){
			$fields['publdate']			= $obj->PublicationDate ? $obj->PublicationDate : '';
		}

		if(!is_null($obj->Deadline)){
			$fields['deadline']			= $obj->Deadline ? $obj->Deadline : '';
		}

		if(!is_null($obj->ExpectedPages)){
			$fields['pages'] 	  		= (is_numeric($obj->ExpectedPages) ? $obj->ExpectedPages : 0);
		}

		if(!is_null($obj->Subject)){
			$fields['subject']			= $obj->Subject ? $obj->Subject : '';
		}

		if(!is_null($obj->Description)){
			$fields['description'] 		= $obj->Description ? $obj->Description : '';
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
			$fields['code'] 			= (is_numeric($obj->SortOrder )? $obj->SortOrder : 0);
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
			BizAdmProperty::enrichDBRowWithCustomMetaData( 'Issue', $obj->ExtraMetaData, $fields );
		}
		return $fields;
	}

	/**
	 *  Converts row value to an object
	 *  It return an object with the mapping value for row to object
	 *  @param array $row row contains key values
	 *  @return object of issue
	 */
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$issue = new AdmIssue();
		$issue->Id 					= $row['id'];
		$issue->Name				= $row['name'];
		$issue->Description			= $row['description'];
		$issue->PublicationDate		= $row['publdate'];
		$issue->Deadline			= $row['deadline'];
		$issue->ExpectedPages		= $row['pages'];
		$issue->Subject				= $row['subject'];
		$issue->Activated			= ($row['active'] == 'on' ? true : false);
		$issue->ReversedRead		= ($row['readingorderrev'] == 'on' ? true : false);
		$issue->OverrulePublication = ($row['overrulepub'] == 'on' ? true : false);
		$issue->SortOrder			= $row['code'];
		$issue->CalculateDeadlines   = ( $row['calculatedeadlines'] == 'on' ? true : false );

		// custom admin properties
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php'; // TODO: DB calling Biz = against architecture!
		if( isset( $row['ExtraMetaData'] ) ) {
			/** @noinspection PhpDeprecationInspection */
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
