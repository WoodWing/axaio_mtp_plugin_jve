<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Offers cascade copy- and delete functionality for publications and its defintions.
 * This includes channels, issues, editions, sections, statuses, deadlines, authorizations, workflow and routing.
 *
 * The functionality is made at this (low) level since these kind of operations won't need much business logics.
 * This is because that logic has been already applied before since all records to exist and won't get changed.
 * In other terms, it does a kind of 'raw' copy of records without undestanding all details. Only relations between
 * tables are taken care of.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/interfaces/services/BizException.class.php';

class DBCascadePub extends DBBase
{
	static private $NamePrefix;  // Name prefix to apply to all copied publication items; used for debugging only
	static private $EditionsMap; // Mapping table of original edition ids onto copied edition ids.
	static private $SectionsMap; // ...
	static private $StatusesMap;
	static private $IssuesMap;
	static private $ChannelsMap;

	/**
	 * Makes 'raw' copy of a table row.
	 * This is without understanding the exact meaning of individual fields.
	 *
	 * @param string $tableName  Name of table without prefix or quotes
	 * @param array  $sourceRow    Row to be copied. Keys are column names, values are field data.
	 * @param array  $overruleFields Some fields to be filled in during copy
	 * @return string The id of the created row (the copy), or null if copy failed.
	 */
	static private function copyRow( $tableName, $sourceRow, $overruleFields, $autoincrement = true )
	{
		// Copy record in memory, except the id
		$copyRow = $sourceRow;

		// Take care that both $copyRow and $overruleFields have lowercase keys, as this may not be garantueed?!?
		$copyRow = array_change_key_case($copyRow);
		$overruleFields = array_change_key_case($overruleFields);

		// Apply overruled data
		foreach ($overruleFields as $overfieldname => $overvalue) {
			if (isset($copyRow[$overfieldname])) {
				$copyRow[$overfieldname] = $overvalue;
			}
		}

		// Insert the copy into DB
		$newId = self::insertRow($tableName, $copyRow, $autoincrement);
		
		return $newId;
		// Get fresh copy to make sure we're looking at correct data, and to get the rid of quoted fields as added above
		//return self::getRow( $tableName, "`$idFieldName` = $newId" );
	}

	/**
	 * Performs cascade copy of a publication.
	 * It copies all definions that are made for the publication.
	 * This includes channels, issues, editions, sections, statuses, deadlines, authorizations, workflow and routing.
	 *
	 * @param array   $srcPubRow    Source publication row to be copied.
	 * @param string  $copyPubName  New name to apply to copied publication.
	 * @param boolean $copyIssues   Whether or not to copy issues too.
	 * @param string  $namePrefix   Debug feature; name prefix to apply to all copied items inside publication. Just to ease recognizion.
	 * @return string Id of the copied publication
	 */
	static public function copyPublication( $srcPubRow, $copyPubName, $copyIssues, $namePrefix )
	{
		// Init copy scenario
		self::$NamePrefix = $namePrefix;
		self::$EditionsMap = array();
		self::$SectionsMap = array();
		self::$StatusesMap = array();
		self::$IssuesMap   = array();
		self::$ChannelsMap = array();

		// Copy publication row
		$copyPubId = self::copyRow( 'publications', $srcPubRow,
									array( 'publication' => $copyPubName, 'defaultchannelid' => 0 ), true );
		// Copy publication configurations
		self::copyChannels( $srcPubRow['id'], $copyPubId , $copyIssues );
		self::copySections( $srcPubRow['id'], $copyPubId, 0 );
		self::copyStatuses( $srcPubRow['id'], $copyPubId, 0 );

		self::copyAuthorizations( $srcPubRow['id'], $copyPubId, 0 );
		self::copyRoutings      ( $srcPubRow['id'], $copyPubId, 0 );
		self::copyProperties    ( $srcPubRow['id'], $copyPubId );
		self::copyDeadlines();

		// Resolve default channel
		if( $srcPubRow['defaultchannelid'] ) {
			$copyDefChan = self::$ChannelsMap[$srcPubRow['defaultchannelid']];
		} else {
			$copyDefChan = 0;
		}
		self::updateRow( 'publications', array( 'defaultchannelid' => $copyDefChan ), "`id` = ".$copyPubId );
		
		// Copy custom properties
		self::copyExtraMetaData( 'Publication', $srcPubRow['id'], $copyPubId );
		return $copyPubId;
	}

	/**
	 * Performs cascade copy of an issue.
	 * It copies all definions that are made for the issue.
	 * This includes editions, sections, deadlines, authorizations, workflow and routing.
	 *
	 * @param string  $copyChanId     Id of destination channel to copy issues into. Duplicate Issue passes zero to copy within same channel.
	 * @param array   $srcIssueRow    Source issue row to be copied.
	 * @param string  $copyIssueObj   New issue to be copy.
	 * @param string  $namePrefix     Debug feature; name prefix to apply to all copied items inside issue. Just to ease recognizion.
	 * @return string Id of the copied issue
	 */
	static public function copyIssue( $srcPubId, $copyChanId, $srcIssueRow, $copyIssueObj, $namePrefix )
	{
		// Init copy scenario
		self::$NamePrefix  = $namePrefix;
		self::$EditionsMap = array();
		self::$SectionsMap = array();
		self::$StatusesMap = array();
		self::$IssuesMap   = array();
		self::$ChannelsMap = array();

		// Perform copy operation
		return self::doCopyIssue( $srcPubId, $srcPubId /*within same pub!*/, $copyChanId, $srcIssueRow, $copyIssueObj );
	}

	/**
	 * Same as above, but this one is used internally because it does not start a new copy session.
	 */
	static public function doCopyIssue( $srcPubId, $copyPubId, $copyChanId, $srcIssueRow, $copyIssueObj )
	{
		// Copy publication row
		$copyIssueId = self::copyRow( 'issues', $srcIssueRow,
									array( 'name' => $copyIssueObj->Name,
											'channelid' => $copyChanId ? $copyChanId : $srcIssueRow['channelid'] ), true );

		// Copy publication configurations
		self::$IssuesMap[$srcIssueRow['id']] = $copyIssueId;

		self::copyIssueEditions( $copyChanId, $srcIssueRow['id'], $copyIssueId );
		self::copySections( $srcPubId, $copyPubId, $srcIssueRow['id'] );
		self::copyStatuses( $srcPubId, $copyPubId, $srcIssueRow['id'] );

		self::copyAuthorizations( $srcPubId, $copyPubId, $srcIssueRow['id'] );
		self::copyRoutings      ( $srcPubId, $copyPubId, $srcIssueRow['id'] );
		self::copyExtraMetaDataForIssue( $srcIssueRow['id'], $copyIssueObj );

		// Notify event plugins. Note this is not part of the Biz layer, because it would require adding in many places
		// and might also require accessing the database to get the issue list (e.g. copy publication or channel).
		require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
		BizEnterpriseEvent::createIssueEvent( $copyIssueId, 'create' );

		return $copyIssueId;
	}

	/**
	 * Performs cascade copy of all channels of a given publication.
	 * This includes its issues and edtions.
	 *
	 * @param string  $srcPubId    Id of source publication to copy channels from.
	 * @param string  $copyPubId   Id of destination publication to copy channels into.
	 * @param boolean $copyIssues  Whether or not to copy issues too.
	 */
	static private function copyChannels( $srcPubId, $copyPubId, $copyIssues )
	{
		// Get source publication channels
		$srcChanRows = self::listRows( 'channels', 'id', 'name', "`publicationid` = $srcPubId" );
		foreach( $srcChanRows as $srcChanRow ) {
			// Copy channel
			$copyChanId = self::copyRow( 'channels', $srcChanRow,
								array( 'publicationid' => $copyPubId,
										'name' => self::$NamePrefix.$srcChanRow['name'],
										'currentissueid' => 0 ), true ); // resolved below
			if( $copyChanId && $srcChanRow['id'] ) {
				self::$ChannelsMap[$srcChanRow['id']] = $copyChanId;
			}
			// Copy issues
			if( $copyIssues ) {
				self::copyIssues( $srcPubId, $copyPubId, $srcChanRow['id'], $copyChanId );
			}

			// Copy custom properties
			self::copyExtraMetaData( 'PubChannel', $srcChanRow['id'], $copyChanId );
		}
		// -> Now $issueMap is completed, which we need below !
		foreach( $srcChanRows as $srcChanRow ) {
			// Copy editions
			$copyChanId = self::$ChannelsMap[$srcChanRow['id']];
			self::copyChannelEditions( $srcChanRow['id'], $copyChanId );

			// Resolve current issue
			if( $copyIssues && isset($srcChanRow['currentissueid']) && $srcChanRow['currentissueid'] ) {
				$copyCurIss = self::$IssuesMap[$srcChanRow['currentissueid']];
				self::updateRow( 'channels', array( 'currentissueid' => $copyCurIss ), "`id` = ".$copyChanId );
			}
		}
	}

	/**
	 * Copies all issues of given a channel.
	 *
	 * @param string $srcPubId   Id of source publication that owns the issues.
	 * @param string $copyPubId  Id of destination publication to copy issues into.
	 * @param string $srcChanId  Id of source channel to copy issues from.
	 * @param string $copyChanId Id of destination channel to copy issues into.
	 */
	static private function copyIssues( $srcPubId, $copyPubId, $srcChanId, $copyChanId )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
		// Get source issue
		$srcIssueRows = self::listRows( 'issues', 'id', 'name', "`channelid` = $srcChanId" );
		// Copy issues
		foreach( $srcIssueRows as $srcIssueRow ) {
			$copyIssueObj = DBAdmIssue::getIssueObj( $srcIssueRow['id']);
			//$copyIssueId = self::doCopyIssue( $srcPubId, $copyPubId, $copyChanId, $srcIssueRow, self::$NamePrefix.$srcIssueRow['name'] );
			$copyIssueId = self::doCopyIssue( $srcPubId, $copyPubId, $copyChanId, $srcIssueRow, $copyIssueObj );
			if( $copyIssueId && $srcIssueRow['id'] ) {
				self::$IssuesMap[$srcIssueRow['id']] = $copyIssueId;
			}
		}
	}

	/**
	 * Copies all sections of a given publication.
	 *
	 * @param string $srcPubId   Id of source publication to copy sections from.
	 * @param string $copyPubId  Id of destination publication to copy sections into.
	 * @param string $srcIssueId Id of source issue to copy sections from. Pass zero to copy pub sections only.
	 */
	static private function copySections( $srcPubId, $copyPubId, $srcIssueId )
	{
		// Get source sections
		$srcSectionRows = self::listRows( 'publsections', 'id', 'section',
								"( `publication` = ".$srcPubId." ) AND ( `issue` = ".$srcIssueId." )" );
		// Copy sections
		foreach( $srcSectionRows as $srcSectionRow ) {
			$copyIssueId = $srcSectionRow['issue'] > 0 ? self::$IssuesMap[$srcSectionRow['issue']] : 0;
			$copySectionId = self::copyRow( 'publsections', $srcSectionRow,
										array( 'publication' => $copyPubId, 'issue' => $copyIssueId,
												'section' => self::$NamePrefix.$srcSectionRow['section'] ), true);
			if( $copySectionId && $srcSectionRow['id'] ) {
				self::$SectionsMap[$srcSectionRow['id']] = $copySectionId;
			}
		}
	}

	/**
	 * Copies all editions of a given channel.
	 *
	 * @param string $srcChanId  Id of source channel to copy editions from.
	 * @param string $copyChanId Id of destination channel to copy editions into.
	 */
	static private function copyChannelEditions( $srcChanId, $copyChanId )
	{
		// Get source editions
		$srcEditionsRows = self::listRows( 'editions', 'id', 'name',
											"( `channelid` = $srcChanId ) AND ( `issueid` = 0 )" );
		// Copy editions
		foreach( $srcEditionsRows as $srcEditionsRow ) {
			$copyIssueId = $srcEditionsRow['issueid'] > 0 ? self::$IssuesMap[$srcEditionsRow['issueid']] : 0;
			$copyEditionId = self::copyRow( 'editions', $srcEditionsRow,
										array( 'channelid' => $copyChanId, 'issueid' => $copyIssueId ,
												'name' => self::$NamePrefix.$srcEditionsRow['name'] ), true);
			if( $copyEditionId && $srcEditionsRow['id'] ) {
				self::$EditionsMap[$srcEditionsRow['id']] = $copyEditionId;
			}
		}
	}

	/**
	 * Copies all editions of a given issue.
	 *
	 * @param string $copyChanId  Id of destination channel to copy editions into.
	 * @param string $srcIssueId  Id of source issues to copy editions from.
	 * @param string $copyIssueId Id of destination issues to copy editions into.
	 */
	static private function copyIssueEditions( $copyChanId, $srcIssueId, $copyIssueId )
	{
		// Get source editions
		$srcEditionsRows = self::listRows( 'editions', 'id', 'name', "`issueid` = $srcIssueId" );
		// Copy editions
		foreach( $srcEditionsRows as $srcEditionsRow ) {
			$copyEditionId = self::copyRow( 'editions', $srcEditionsRow,
										array( 'channelid' => $copyChanId, 'issueid' => $copyIssueId,
												'name' => self::$NamePrefix.$srcEditionsRow['name'] ), true);
			if( $copyEditionId && $srcEditionsRow['id'] ) {
				self::$EditionsMap[$srcEditionsRow['id']] = $copyEditionId;
			}
		}
	}

	/**
	 * Copies all statuses of a given publication.
	 * Also resolves the next statuses in the copied environment.
	 *
	 * @param string $srcPubId   Id of source publication to copy statuses from.
	 * @param string $copyPubId  Id of destination publication to copy statuses into.
	 * @param string $srcIssueId Id of source issue to copy statuses from. Pass zero to copy pub statuses only.
	 */
	static private function copyStatuses( $srcPubId, $copyPubId, $srcIssueId )
	{
		// Get source statuses
		$srcStatusRows = self::listRows( 'states', 'id', 'state',
								"( `publication` = ".$srcPubId." ) AND ( `issue` = ".$srcIssueId." )" );
		// Copy statuses
		foreach( $srcStatusRows as $srcStatusRow ) {
			$copyIssueId = $srcStatusRow['issue'] > 0 ? self::$IssuesMap[$srcStatusRow['issue']] : 0;
			$copyStatusId = self::copyRow( 'states', $srcStatusRow,
										array( 'publication' => $copyPubId, 'issue' => $copyIssueId,
												'state' => self::$NamePrefix.$srcStatusRow['state'],
												'section' => 0,  // sections are always zero (not supported)
												'nextstate' => 0 ), true); // to be resolved below
			if( $copyStatusId && $srcStatusRow['id'] ) {
				self::$StatusesMap[$srcStatusRow['id']] = $copyStatusId;
			}
		}
		// Resolve 'next status' fields...!
		foreach( $srcStatusRows as $srcStatusRow ) {
			if( isset($srcStatusRow['nextstate']) && $srcStatusRow['nextstate'] ) {
				$copyStatusId  = self::$StatusesMap[$srcStatusRow['id']];
				$copyNextState = self::$StatusesMap[$srcStatusRow['nextstate']];
				self::updateRow( 'states', array( 'nextstate' => $copyNextState ), "`id` = ".$copyStatusId );
			}
		}
	}

	/**
	 * Copies all user authorizations for a given publication.
	 *
	 * @param string $srcPubId    Id of source publication to copy authorizations from.
	 * @param string $copyPubId   Id of destination publication to copy authorizations into.
	 * @param string $srcIssueId  Id of source issue to copy user auth from. For pubs, pass zero to copy pub admin too.
	 */
	static private function copyAuthorizations( $srcPubId, $copyPubId, $srcIssueId )
	{
		if( $srcIssueId == 0 ) { // only pubs have pub admins
			// Get source pub admin authorizations
			$srcAdminRows = self::listRows( 'publadmin', 'id', '', "`publication` = ".$srcPubId );
			// Copy authorizations
			foreach( $srcAdminRows as $srcAdminRow ) {
				self::copyRow( 'publadmin', $srcAdminRow,
								array( 'publication' => $copyPubId ), true );
			}
		}

		// Get source pub user authorizations
		$srcAuthRows = self::listRows( 'authorizations', 'id', '',
								"( `publication` = ".$srcPubId." ) AND ( `issue` = ".$srcIssueId." )" );
		// Copy authorizations
		foreach( $srcAuthRows as $srcAuthRow ) {
			$copyIssueId   = $srcAuthRow['issue']   > 0 ? self::$IssuesMap  [$srcAuthRow['issue']] : 0;
			$copySectionId = $srcAuthRow['section'] > 0 ? self::$SectionsMap[$srcAuthRow['section']] : 0;
			$copyStatusId  = $srcAuthRow['state']   > 0 ? self::$StatusesMap[$srcAuthRow['state']] : 0;
			self::copyRow( 'authorizations', $srcAuthRow,
							array( 'publication' => $copyPubId, 'issue' => $copyIssueId,
									'section' => $copySectionId, 'state' => $copyStatusId ), true);
		}
	}

	/**
	 * Copies all routing definitions for a given publication.
	 *
	 * @param string $srcPubId    Id of source publication to copy routings from.
	 * @param string $copyPubId   Id of destination publication to copy routings into.
	 * @param string $srcIssueId  Id of source issue to copy routings from. Pass zero to copy pub routings only.
	 */
	static private function copyRoutings( $srcPubId, $copyPubId, $srcIssueId )
	{
		// Get source pub user authorizations
		$srcRoutingRows = self::listRows( 'routing', 'id', '',
								"( `publication` = ".$srcPubId." ) AND ( `issue` = ".$srcIssueId." )" );
		// Copy authorizations
		foreach( $srcRoutingRows as $srcRoutingRow ) {
			$copyIssueId   = $srcRoutingRow['issue']   > 0 ? self::$IssuesMap  [$srcRoutingRow['issue']] : 0;
			$copySectionId = $srcRoutingRow['section'] > 0 ? self::$SectionsMap[$srcRoutingRow['section']] : 0;
			$copyStatusId  = $srcRoutingRow['state']   > 0 ? self::$StatusesMap[$srcRoutingRow['state']] : 0;
			self::copyRow( 'routing', $srcRoutingRow,
							array( 'publication' => $copyPubId, 'issue' => $copyIssueId,
									'section' => $copySectionId, 'state' => $copyStatusId ), true);
		}
	}

	
	/**
	 * Copies all extra metadata for a given issue. Values are added by an update statement
	 * as these fields are blobs.
	 *
	 * @param string $srcIssueId  Id of source issue to copy extra metadata from.
	 * @param Object $copyIssueObj The copied object.
	 * @return void
	 */
	static private function copyExtraMetaDataForIssue( $srcIssueId, $copyIssueObj )
	{
		// Get source pub user authorizations
		$where = '`issue` = ?';
		$params = array( $srcIssueId );
		$srcChannelDataRows = self::listRows( 'channeldata', '', '', $where, '*', $params );
		$extra = self::getExtraMetaDataForCopyIssueObject($copyIssueObj);
		$propValues = array();
		if( isset($copyIssueObj->ExtraMetaData) ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php'; // TODO: DB calling Biz = against architecture!
			/** @noinspection PhpDeprecationInspection */
			BizAdmProperty::enrichDBRowWithCustomMetaData( 'Issue', $copyIssueObj->ExtraMetaData, $propValues );
		}
		foreach( $srcChannelDataRows as $srcChannelDataRow ) {
			$copyIssueId   = $srcChannelDataRow['issue']   > 0 ? self::$IssuesMap[$srcChannelDataRow['issue']] : 0;
			$value = $srcChannelDataRow['value']; 
			if (array_key_exists($srcChannelDataRow['name'], $extra)){
				if( $value != $propValues['ExtraMetaData'][$srcChannelDataRow['name']]) {
					$value = $propValues['ExtraMetaData'][$srcChannelDataRow['name']];
				}
			}

			unset($srcChannelDataRow['value']); // Handled by the update beneath
			self::copyRow( 'channeldata', $srcChannelDataRow,
							array( 'issue' => $copyIssueId ), false );
			$where = '`issue` = ? AND `section` = ? AND `name` = ? ';
			$params = array( $copyIssueId, $srcChannelDataRow['section'], $srcChannelDataRow['name']);
			self::updateRow('channeldata', array('value' => '#BLOB#'), $where, $params, $value);
		}
	}
	
	/**
	 * Copies custom admin properties from one admin entity ($srcId) to another ($destId). 
	 * At the time calling this function, both entities should exist in DB.
	 *
	 * Note that only the configured custom properties are copied. So when the configuration
	 * has been changed after the original entity was created, the copied properties are
	 * respecting the definitions, and not the source entity. An example: Assume the source  
	 * entity has custom property A and B. Now the admin user removes B from the definitions
	 * (by unplugging a server plugin that defines B) and adds C to the definitions (by plugging
	 * in another server plugin that defines C). Then the admin user copies the entity.
	 * As a result, the copied entity has properties A and C, but not B. C has default value.
	 *
	 * @param string $entity 'Publication', 'PubChannel' or 'Issue'
	 * @param integer $srcId ID of the admin entity to copy from.
	 * @param integer $destId ID of the admin entity to copy to.
	 */
	static private function copyExtraMetaData( $entity, $srcId, $destId )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChanneldata.class.php';
		$typeMap = BizAdmProperty::getCustomPropertyTypes( $entity );
		$srcMetadatas = DBChanneldata::getCustomProperties( $entity, $srcId, $typeMap );
		$newMetadatas = BizAdmProperty::buildCustomMetaDataFromValues( $entity, $srcMetadatas );
		DBChanneldata::saveCustomProperties( $entity, $destId, $newMetadatas, $typeMap );
	}

	/**
	 * Builds an array of changed values on the copied object.
	 *
	 * Checks the object being copied for fields that should not be copied from
	 * the original, but rather set to the values supplied in the copied object
	 * already.
	 *
	 * @static
	 * @param AdmIssue $copyIssueObj
	 * @return array
	 */
	static private function getExtraMetaDataForCopyIssueObject( $copyIssueObj )
	{
		$extra = array();

		if (is_array($copyIssueObj->ExtraMetaData)){
		    foreach ($copyIssueObj->ExtraMetaData as $metadata){
			    $extra[$metadata->Property] = $metadata->Values;
			}
		}

		return $extra;
	}

	/**
	 * Copies all property definitions for a given publication.
	 *
	 * @param string $srcPubId    Id of source publication to copy properties from.
	 * @param string $copyPubId   Id of destination publication to copy properties into.
	 */
	static private function copyProperties( $srcPubId, $copyPubId )
	{
		// Get source properties
		$srcPropRows = self::listRows( 'properties', 'id', 'name', "`publication` = ".$srcPubId );
		// Copy properties
		foreach( $srcPropRows as $srcPropRow ) {
			self::copyRow( 'properties', $srcPropRow,
										array( 'publication' => $copyPubId,
												'dispname' => self::$NamePrefix.$srcPropRow['dispname'] ), true);
		}

		// Get source action properties
		$srcActsRows = self::listRows( 'actionproperties', 'id', 'property', "`publication` = ".$srcPubId );
		// Copy action properties
		foreach( $srcActsRows as $srcActsRow ) {
			self::copyRow( 'actionproperties', $srcActsRow,
										array( 'publication' => $copyPubId ), true);
		}
	}

	/**
	 * Copies all deadline definitions for a given issue.
	 */
	static private function copyDeadlines()
	{
		if( count(self::$IssuesMap) > 0 ) {
			$srcIssueIds = implode( ', ', array_keys( self::$IssuesMap ) );

			// Get sections source
			$srcSectionRows = self::listRows( 'issuesection', 'id', '', "`issue` IN ( ".$srcIssueIds." )" );
			// Copy sections
			foreach( $srcSectionRows as $srcSectionRow ) {
				$copyIssueId   = self::$IssuesMap  [$srcSectionRow['issue']];
				$copySectionId = self::$SectionsMap[$srcSectionRow['section']];
				self::copyRow( 'issuesection', $srcSectionRow,
								array( 'issue' => $copyIssueId, 'section' => $copySectionId ), true);
			}

			// Get statuses source
			$srcStatusRows = self::listRows( 'issuesectionstate', 'id', '', "`issue` IN ( ".$srcIssueIds." )" );
			// Copy statuses
			foreach( $srcStatusRows as $srcStatusRow ) {
				$copyIssueId   = self::$IssuesMap  [$srcStatusRow['issue']];
				$copySectionId = self::$SectionsMap[$srcStatusRow['section']];
				$copyStatusId  = self::$StatusesMap[$srcStatusRow['state']];
				self::copyRow( 'issuesectionstate', $srcStatusRow,
								array( 'issue' => $copyIssueId, 'section' => $copySectionId, 'state' => $copyStatusId ), true);
			}

			// Get editions source
			$srcEditionRows = self::listRows( 'issueeditions', 'id', '', "`issue` IN ( ".$srcIssueIds." )" );
			// Copy editions
			foreach( $srcEditionRows as $srcEditionRow ) {
				$copyIssueId   = self::$IssuesMap[$srcEditionRow['issue']];
				$copyEditionId = self::$EditionsMap[$srcEditionRow['edition']];
				self::copyRow( 'issueeditions', $srcEditionRow,
								array( 'issue' => $copyIssueId, 'edition' => $copyEditionId ), true);
			}
		}
		if( count( self::$SectionsMap ) > 0 ) {
			$srcSectionIds = implode( ', ', array_keys( self::$SectionsMap ) );

			// Get statuses source
			$srcSecSttRows = self::listRows( 'sectionstate', 'id', '', "`section` IN ( ".$srcSectionIds." )" );
			// Copy statuses
			foreach( $srcSecSttRows as $srcSecSttRow ) {
				$copySectionId = self::$SectionsMap[$srcSecSttRow['section']];
				$copyStatusId  = self::$StatusesMap[$srcSecSttRow['state']];
				self::copyRow( 'sectionstate', $srcSecSttRow,
								array( 'section' => $copySectionId, 'state' => $copyStatusId ), true);
			}
		}
	}

	/**
	 * Performs cascade deletion of a given publication.
	 *
	 * It removes all definitions made for a publication and the publication itself.
	 * This includes channels, issues, editions, sections, statuses, deadlines, authorizations, workflow, routing and logging.
	 *
	 * @param string $pubId Id of publication to delete.
	 */
	static public function deletePublication( $pubId )
	{
		// Remove sections
		$sectionRows = self::listRows( 'publsections', 'id', 'name', "`publication` = $pubId" );
		$sectionIds = array_diff( array_keys($sectionRows), array( 0 ) ); // paranoid filter; remove zeros
		if( count($sectionIds) > 0 ) {
			$sectionIds = implode( ', ', $sectionIds ); // make ids comma separated to fit into SQL
			self::deleteRows( 'sectionstate',"`section` IN ( $sectionIds )" );
		}

		// Remove sections
		self::deleteRows( 'publsections', "`publication` = $pubId" );

		// Remove channels, issues and editions
		$chanRows = self::listRows( 'channels', 'id', 'name', "`publicationid` = $pubId" );
		if( $chanRows ) {
			self::deleteChannels( array_keys($chanRows) );
		}
		self::deleteRows( 'channels',        "`publicationid` = $pubId" ); // remove zerofied channels

		// Remove statuses and routing
		self::deleteRows( 'states',          "`publication` = $pubId" );
		self::deleteRows( 'routing',         "`publication` = $pubId" );

		// Remove user/admin authorizations
		self::deleteRows( 'authorizations',  "`publication` = $pubId" );
		self::deleteRows( 'publadmin',       "`publication` = $pubId" );
		
		// Remove custom properties (values)
		self::deleteRows( 'channeldata', 	  "`publication` IN ( $pubId )" );
		
		// Remove custom properties (definitions)
		self::deleteRows( 'properties',      "`publication` = $pubId" );
		self::deleteRows( 'actionproperties',"`publication` = $pubId" );

		// Unlink from logging
		self::updateRow( 'log', array( 'publication' => 0 ), "`publication` = $pubId" );

		// Remove master record
		self::deleteRows( 'publications', "`id` = $pubId" );
	}

	/**
	 * Performs cascade deletion of a given list of channels.
	 * It removes all issues and editions of the channels and the channels itself.
	 *
	 * @param array $chanIds List of channel ids to delete.
	 */
	static public function deleteChannels( $chanIds )
	{
		$chanIds = array_diff( $chanIds, array( 0 ) ); // paranoid filter; remove zeros
		if( count($chanIds) > 0 ) {
			$chanIds = implode( ', ', $chanIds ); // make ids comma separated to fit into SQL

			// Remove issues
			$issueRows = self::listRows( 'issues', 'id', 'name', "`channelid` IN ( $chanIds )" );
			if( $issueRows ) {
				self::deleteIssues( array_keys($issueRows) );
			}
			self::deleteRows( 'issues', "`channelid` IN ( $chanIds )" ); // remove zerofied issues

			// Remove editions
			self::deleteRows( 'editions', "`channelid` IN ( $chanIds )" );

			// Remove custom properties (values)
			self::deleteRows( 'channeldata', 	  "`pubchannel` IN ( $chanIds )" );
			
			// Remove channels master records
			self::deleteRows( 'channels', "`id` IN ( $chanIds )" );
		}
	}
	
	/**
	 * BZ#20601
	 * Reset the default publication channel id to 0 when this default pub channel is deleted.
	 *
	 * @param int $chanId PubChannel DB Id.
	 */
	static public function updatePubDefaultChannelId( $chanId )
	{
		$values = array('defaultchannelid' => 0);
		$where = '`defaultchannelid` = ' . $chanId;
		self::updateRow( 'publications', $values, $where );
	}

	/**
	 * Performs cascade deletion of a given list of issues.
	 * It removes all deadline definitions made for the issue and the issue itself.
	 *
	 * @param array $editionIds List of edition ids to delete.
	 */
	static public function deleteEditions( array $editionIds )
	{
		$editionIds = array_diff( $editionIds, array( 0 ) ); // paranoid filter; remove zeros
		if( count($editionIds) > 0 ) {
			$editionIds = implode( ', ', $editionIds ); // make ids comma separated to fit into SQL

			// Remove deadlines
			self::deleteRows( 'issueeditions', " `edition` IN ( $editionIds ) " );

			// Remove master records
			self::deleteRows( 'editions', "`id` IN ( $editionIds ) " );
		}
	}

	/**
	 * Performs cascade deletion of a given list of issues.
	 * It removes all deadline definitions made for the issue and the issue itself.
	 *
	 * @param array $issueIds List of issue ids to delete.
	 */
	static public function deleteIssues( $issueIds )
	{
		$issueIds = array_diff( $issueIds, array( 0 ) ); // paranoid filter; remove zeros
		if( count($issueIds) > 0 ) {
			$issueIds = implode( ', ', $issueIds ); // make ids comma separated to fit into SQL

			// Remove sections
			self::deleteRows( 'publsections', "`issue` IN ( $issueIds )" );

			// Remove statuses
			$statusRows = self::listRows( 'states', 'id', 'state', "`issue` IN ( $issueIds )" );
			if( $statusRows ) {
				self::deleteStatuses( array_keys($statusRows) );
			}
			self::deleteRows( 'states', "`issue` IN ( $issueIds )" ); // removes zerofied statuses

			// Remove editions
			$editionRows = self::listRows( 'editions', 'id', 'name', "`issueid` IN ( $issueIds )" );
			if( $editionRows ) {
				self::deleteEditions( array_keys($editionRows) );
			}
			self::deleteRows( 'editions', "`issueid` IN ( $issueIds )" ); // removes zerofied editions

			// Remove deadlines
			self::deleteRows( 'issuesection',     "`issue` IN ( $issueIds )" );
			self::deleteRows( 'issuesectionstate',"`issue` IN ( $issueIds )" );
			self::deleteRows( 'issueeditions',    "`issue` IN ( $issueIds )" );

			// Remove routing and user authorizations
			self::deleteRows( 'routing',          "`issue` IN ( $issueIds )" );
			self::deleteRows( 'authorizations',   "`issue` IN ( $issueIds )" );

			// Unlink from logging
			self::updateRow( 'log', array( 'issue' => 0 ), "`issue` IN ( $issueIds )" );
			
			// Reset current issues from the channels
			self::updateRow( 'channels', array( 'currentissueid' => 0 ), "`currentissueid` IN ( $issueIds )" );

			// Remove custom property values
			self::deleteRows( 'channeldata', 	  "`issue` IN ( $issueIds )" );

			// Remove master records
			self::deleteRows( 'issues',           "`id` IN ( $issueIds )" );
		}
	}

	/**
	 * Performs cascade deletion of a given list of statuses.
	 * It removes all deadline/routing definitions made for the status and the status itself.
	 *
	 * @param array $statusIds List of status ids to delete.
	 */
    public static function deleteStatuses( $statusIds )
    {
		$statusIds = array_diff( $statusIds, array( 0 ) ); // paranoid filter; remove zeros
		if( count($statusIds) > 0 ) { // avoid zero, and ignore personal statuses
			$statusIds = implode( ', ', $statusIds ); // make ids comma separated to fit into SQL

			// Remove deadlines
			self::deleteRows('issuesectionstate',"`state` IN ( $statusIds ) ");
			self::deleteRows('sectionstate',     "`state` IN ( $statusIds ) ");

			// Remove routings
			self::deleteRows('routing',          "`state` IN ( $statusIds ) ");

			// Remove authorizations.
			self::deleteRows('authorizations',   "`state` IN ( $statusIds ) ");

			// Unlink from logging
			self::updateRow( 'log', array( 'state' => 0 ), "`state` IN ( $statusIds )" );

			// Remove master record
			self::deleteRows('states', "`id` IN ( $statusIds ) ");
		}
    }


	/**
	 * Performs cascade deletion of a given list of sections.
	 * It removes all deadline/routing definitions made for the section and the section itself.
	 *
	 * @param array $sectionIds List of section ids to delete.
	 */
    public static function deleteSections( array $sectionIds )
    {
		$sectionIds = array_diff( $sectionIds, array( 0 ) ); // paranoid filter; remove zeros
		if( count($sectionIds) > 0 ) {
			$sectionIds = implode( ', ', $sectionIds ); // make ids comma separated to fit into SQL

			// Remove deadlines
			self::deleteRows('issuesection',      "`section` IN ( $sectionIds ) ");
			self::deleteRows('issuesectionstate', "`section` IN ( $sectionIds ) ");
			self::deleteRows('sectionstate',      "`section` IN ( $sectionIds ) ");

			// Remove authorizations, routing and states
			self::deleteRows('authorizations',    "`section` IN ( $sectionIds ) ");
			self::deleteRows('routing',           "`section` IN ( $sectionIds ) ");
			self::deleteRows('states',            "`section` IN ( $sectionIds ) ");

			// Unlink from logging
			self::updateRow( 'log', array( 'section' => 0 ), "`section` IN ( $sectionIds )" );

			// Remove master record
			self::deleteRows('publsections', "`id` IN ( $sectionIds ) ");
		}
	}
}
