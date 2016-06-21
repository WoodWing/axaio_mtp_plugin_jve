<?php

require_once '../../config/config.php';

/**
 * Raises flags for brands and overrule issues if relative deadlines are configured.
 *
 * Since the introduction of the MultiSetObjectProperties feature, this DB upgrade
 * became needed to simplify the detection of (relative) deadline configurations.
 * This is done over many database tables, which is rather complicated (expensive) to 
 * detect, which got significantly more complicated for MultiSetObjectProperties.
 * This script detects deadline configurations at any of those tables and if found,
 * it raises the 'calculatedeadlines' flag in smart_publications or smart_issues.
 *
 * Deadline configurations are detected in any of the following database tables:
 * - smart_publsections
 * - smart_issuesection
 * - smart_sectionstate
 * - smart_issuesectionstate
 * - smart_states
 *
 * Note that the following tables have deadlines but are not checked here
 * since it seems to be impossible to populate the deadlines through the admin apps:
 * - smart_publeditions
 * - smart_issueeditions
 * - smart_channels
 * - smart_editions
 *
 * Note that smart_issues table has deadlines, but because those are directly
 * bound to the issue, they are not part of the complicated deadline calculation
 * and therefore are not seen as a trigger to raise the deadline flag.
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.2.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/dbscripts/DbUpgradeModule.class.php';
 
class DBUpgradeDeadlineFlags extends DbUpgradeModule
{
	const NAME = 'RaiseFlagForCalculatedDeadlines';
	
	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name 
	 */
	protected function getUpdateFlag()
	{
		return 'raiseflag_calculateddeadlines'; // Important: never change this name
	}

	/**
	 * Raises flags for brands and overrule issues if relative deadlines are configured.
	 *
	 * @return bool Whether or not the conversion was successful.
	 */
	public function run()
	{
		// Init.
		$pubIds = array();
		$issueIds = array();
		$this->upgradeSuccessful = true;
		
		// Check smart_sectionstate table.
		if( $this->fieldExists( 'sectionstate', 'deadlinerelative' ) ) {
			$pubIds = array_merge( $pubIds, $this->getPubIdsForSectionStateTable() );
			$issueIds = array_merge( $issueIds, $this->getIssueIdsForSectionStateTable() );
		}

		// Check smart_issuesection table.
		if( $this->fieldExists( 'issuesection', 'deadlinerelative' ) ) {
			$issueIds = array_merge( $issueIds, $this->getIssueIdsForIssueSectionTable() );
		}

		// Check smart_issuesectionstate table.
		if( $this->fieldExists( 'issuesectionstate', 'deadlinerelative' ) ) {
			$issueIds = array_merge( $issueIds, $this->getIssueIdsForIssueSectionStateTable() );
		}

		// Check smart_states table.
		if( $this->fieldExists( 'states', 'deadlinerelative' ) ) {
			$pubIds = array_merge( $pubIds, $this->getPubIdsForStatesTable() );
			$issueIds = array_merge( $issueIds, $this->getIssueIdsForStatesTable() );
		}
		
		// Check smart_publsections table.
		if( $this->fieldExists( 'publsections', 'deadlinerelative' ) ) {
			$pubIds = array_merge( $pubIds, $this->getPubIdsForPublSectionsTable() );
			$issueIds = array_merge( $issueIds, $this->getIssueIdsForPublSectionsTable() );
		}
		
		// Update DB.
		if( $pubIds ) {
			$pubIds = array_unique( $pubIds ); // remove duplicates
			$this->raiseFlagsForPubIds( $pubIds );
		}
		if( $issueIds ) {
			$issueIds = array_unique( $issueIds ); // remove duplicates
			$this->raiseFlagsForIssueIds( $issueIds );
		}
		
		return $this->upgradeSuccessful;
	}

	public function introduced()
	{
		return '920';
	}
	
	/**
	 * Tells whether or not a given field exists in the given database table.
	 *
	 * Note that deadline fields are detected with care because this feature might
	 * change or get removed in future versions. This script can be run at future
	 * versions as well, e.g. in case of 9.0 -> 10.0 upgrade that jumps over 9.2;
	 * In this example it will run on 10.0.
	 *
	 * @param string $tableName
	 * @param string $fieldName
	 * @return boolean TRUE when exists, else FALSE.
	 */
	private function fieldExists( $tableName, $fieldName )
	{
		$dbDriver = DBDriverFactory::gen();
		$exists = false;
		if( $dbDriver->tableExists( $tableName ) ) {
			$exists = $dbDriver->fieldExists( $tableName, $fieldName );
		}
		return $exists;
	}

	/**
	 * Checks the smart_sectionstate table for which brands there are relative deadlines configured.
	 *
	 * @return integer[] List of brand ids for which relative deadlines are configured.
	 */
	private function getPubIdsForSectionStateTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for relative deadline configurations '.
						'for brands in smart_sectionstate table...' );
		$dbDriver = DBDriverFactory::gen();	
		$pubTbl = $dbDriver->tablename( 'publications' );
		$secTbl = $dbDriver->tablename( 'publsections' );
		$secSttTbl = $dbDriver->tablename( 'sectionstate' );
		$sql =  'SELECT DISTINCT pub.id AS "pubid" '.
				'FROM '.$secSttTbl.' ss '.
				'LEFT JOIN '.$secTbl.' sec ON ( sec.`id` = ss.`section` ) '.
				'LEFT JOIN '.$pubTbl.' pub ON ( pub.`id` = sec.`publication` ) '.
				'WHERE ss.`deadlinerelative` > ? '. // relative deadlines set?
				'AND sec.`issue` = ? '; // exclude overrule issues
		$params = array( 0, 0 );
				
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'pubid' );
		if( $rows ) {
			$pubIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for brand ids: '.implode(',',$pubIds) );
		} else {
			$pubIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $pubIds;
	}
	
	/**
	 * Checks the smart_sectionstate table for which overrule issues there are relative deadlines configured.
	 *
	 * @return integer[] List of overrule issue ids for which relative deadlines are configured.
	 */
	private function getIssueIdsForSectionStateTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for relative deadline configurations '.
						'for overrule issues in smart_sectionstate table...' );
		$dbDriver = DBDriverFactory::gen();	
		$issTbl = $dbDriver->tablename( 'issues' );
		$secTbl = $dbDriver->tablename( 'publsections' );
		$secSttTbl = $dbDriver->tablename( 'sectionstate' );
		$sql =  'SELECT DISTINCT iss.`id` AS "issueid" '.
				'FROM '.$secSttTbl.' ss '.
				'LEFT JOIN '.$secTbl.' sec ON ( sec.`id` = ss.`section` ) '.
				'LEFT JOIN '.$issTbl.' iss ON ( iss.`id` = sec.`issue` ) '.
				'WHERE ss.`deadlinerelative` > ? '. // relative deadlines set?
				'AND iss.`overrulepub` = ? '; // only include overrule issues
		$params = array( 0, 'on' );
				
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'issueid' );
		if( $rows ) {
			$issueIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for overrule issue ids: '.implode(',',$issueIds) );
		} else {
			$issueIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $issueIds;
	}
	
	/**
	 * Checks the smart_issuesection table for which overrule issues there are abs deadlines configured.
	 *
	 * @return integer[] List of overrule issue ids for which relative deadlines are configured.
	 */
	private function getIssueIdsForIssueSectionTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for absolute deadline configurations '.
						'for overrule issues in smart_issuesection table...' );
		$dbDriver = DBDriverFactory::gen();	
		$issTbl = $dbDriver->tablename( 'issues' );
		$issSecTbl = $dbDriver->tablename( 'issuesection' );
		$sql =  'SELECT DISTINCT iss.`id` AS "issueid" '.
				'FROM '.$issSecTbl.' issSec '.
				'LEFT JOIN '.$issTbl.' iss ON ( iss.`id` = issSec.`issue` ) '.
				'WHERE (issSec.`deadline` <> ? AND issSec.`deadline` IS NOT NULL) '. // absolute deadlines set?
				'AND iss.`overrulepub` = ? '; // only include overrule issues
		$params = array( '', 'on' );
		
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'issueid' );
		if( $rows ) {
			$issueIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for overrule issue ids: '.implode(',',$issueIds) );
		} else {
			$issueIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $issueIds;
	}
	
	/**
	 * Checks the smart_issuesectionstate table for which overrule issues there are relative/absolute deadlines configured.
	 *
	 * @return integer[] List of overrule issue ids for which deadlines are configured.
	 */
	private function getIssueIdsForIssueSectionStateTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for relative or absolute deadline configurations '.
						'for overrule issues in smart_issuesectionstate table...' );
		$dbDriver = DBDriverFactory::gen();	
		$issTbl = $dbDriver->tablename( 'issues' );
		$issSecSttTbl = $dbDriver->tablename( 'issuesectionstate' );
		$sql =  'SELECT DISTINCT iss.`id` AS "issueid" '.
				'FROM '.$issSecSttTbl.' issSecStt '.
				'LEFT JOIN '.$issTbl.' iss ON ( iss.`id` = issSecStt.`issue` ) '.
				'WHERE ((issSecStt.`deadline` <> ? AND issSecStt.`deadline` IS NOT NULL) '. // abs deadlines set?
				'OR issSecStt.`deadlinerelative` > ? )'. // relative deadlines set?
				'AND iss.`overrulepub` = ? '; // only include overrule issues
		$params = array( '', 0, 'on' );
		
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'issueid' );
		if( $rows ) {
			$issueIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for overrule issue ids: '.implode(',',$issueIds) );
		} else {
			$issueIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $issueIds;
	}

	/**
	 * Checks the smart_sectionstate table for which brands there are relative deadlines configured.
	 *
	 * @return integer[] List of brand ids for which relative deadlines are configured.
	 */
	private function getPubIdsForStatesTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for relative deadline configurations '.
						'for brands in smart_states table...' );
		$dbDriver = DBDriverFactory::gen();	
		$pubTbl = $dbDriver->tablename( 'publications' );
		$sttTbl = $dbDriver->tablename( 'states' );
		$sql =  'SELECT DISTINCT pub.id AS "pubid" '.
				'FROM '.$sttTbl.' stt '.
				'LEFT JOIN '.$pubTbl.' pub ON ( pub.`id` = stt.`publication` ) '.
				'WHERE stt.`deadlinerelative` > ? '. // relative deadlines set?
				'AND stt.`issue` = ? '; // exclude overrule issues
		$params = array( 0, 0 );
				
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'pubid' );
		if( $rows ) {
			$pubIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for brand ids: '.implode(',',$pubIds) );
		} else {
			$pubIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $pubIds;
	}

	/**
	 * Checks the smart_sectionstate table for which overrule issues there are relative deadlines configured.
	 *
	 * @return integer[] List of overrule issue ids for which relative deadlines are configured.
	 */
	private function getIssueIdsForStatesTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for relative deadline configurations '.
						'for overrule issues in smart_states table...' );
		$dbDriver = DBDriverFactory::gen();	
		$issTbl = $dbDriver->tablename( 'issues' );
		$sttTbl = $dbDriver->tablename( 'states' );
		$sql =  'SELECT DISTINCT iss.id AS "issueid" '.
				'FROM '.$sttTbl.' stt '.
				'LEFT JOIN '.$issTbl.' iss ON ( iss.`id` = stt.`issue` ) '.
				'WHERE stt.`deadlinerelative` > ? '. // relative deadlines set?
				'AND iss.`overrulepub` = ? '; // only include overrule issues
		$params = array( 0, 'on' );
				
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'issueid' );
		if( $rows ) {
			$pubIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for brand ids: '.implode(',',$pubIds) );
		} else {
			$pubIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $pubIds;
	}

	/**
	 * Checks the smart_publsections table for which brands there are relative/absolute deadlines configured.
	 *
	 * @return integer[] List of brand ids for which deadlines are configured.
	 */
	private function getPubIdsForPublSectionsTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for deadline configurations '.
						'for brands in smart_publsections table...' );
		$dbDriver = DBDriverFactory::gen();	
		$pubTbl = $dbDriver->tablename( 'publications' );
		$pubSecTbl = $dbDriver->tablename( 'publsections' );
		$sql =  'SELECT DISTINCT pub.`id` AS "pubid" '.
				'FROM '.$pubSecTbl.' pubSec '.
				'LEFT JOIN '.$pubTbl.' pub ON ( pub.`id` = pubSec.`publication` ) '.
				'WHERE ((pubSec.`deadline` <> ? AND pubSec.`deadline` IS NOT NULL) '. // abs deadlines set?
				'OR pubSec.`deadlinerelative` > ? )'. // relative deadlines set?
				'AND pubSec.`issue` = ? '; // exclude overrule issues
		$params = array( '', 0, 0 );
		
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'pubid' );
		if( $rows ) {
			$pubIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for brand ids: '.implode(',',$pubIds) );
		} else {
			$pubIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $pubIds;
	}

	/**
	 * Checks the smart_publsections table for which overrule issues there are relative/absolute deadlines configured.
	 *
	 * @return integer[] List of overrule issue ids for which deadlines are configured.
	 */
	private function getIssueIdsForPublSectionsTable()
	{
		LogHandler::Log( self::NAME, 'INFO', 'Checking for deadline configurations '.
						'for overrule issues in smart_publsections table...' );
		$dbDriver = DBDriverFactory::gen();	
		$issTbl = $dbDriver->tablename( 'issues' );
		$pubSecTbl = $dbDriver->tablename( 'publsections' );
		$sql =  'SELECT DISTINCT iss.`id` AS "issueid" '.
				'FROM '.$pubSecTbl.' pubSec '.
				'LEFT JOIN '.$issTbl.' iss ON ( iss.`id` = pubSec.`issue` ) '.
				'WHERE ((pubSec.`deadline` <> ? AND pubSec.`deadline` IS NOT NULL) '. // abs deadlines set?
				'OR pubSec.`deadlinerelative` > ? )'. // relative deadlines set?
				'AND iss.`overrulepub` = ? '; // only include overrule issues
		$params = array( '', 0, 'on' );
		
		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			$this->upgradeSuccessful = false;
			return array();
		}
		$rows = DBBase::fetchResults( $sth, 'issueid' );
		if( $rows ) {
			$issueIds = array_keys( $rows );
			LogHandler::Log( self::NAME, 'INFO', 
				'Deadlines found for overrule issue ids: '.implode(',',$issueIds) );
		} else {
			$issueIds = array();
			LogHandler::Log( self::NAME, 'INFO', 'No deadlines found. No flag needed.' );
		}
		return $issueIds;
	}

	/**
	 * Updates relative deadlines flag in the smart_publications table for the given brand ids.
	 *
	 * @param integer[] $pubIds Brand ids to upgrade.
	 */
	private function raiseFlagsForPubIds( array $pubIds )
	{
		LogHandler::Log( self::NAME, 'INFO', 'Raising flags for brand ids: '.implode(',',$pubIds) );
		$dbDriver = DBDriverFactory::gen();	
		$pubTbl = $dbDriver->tablename( 'publications' );
		$sql =  'UPDATE '.$pubTbl.' SET `calculatedeadlines` = ? '.
				'WHERE `id` IN ( '.implode(',',$pubIds).' ) ';
		$params = array( 'on' );

		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			LogHandler::Log( self::NAME, 'ERROR', 'Failed raising flag for brand ids.' );
			$this->upgradeSuccessful = false;
		} else {
			LogHandler::Log( self::NAME, 'INFO', 'Successfully raised flags for brand ids.' );
		}
	}

	/**
	 * Updates relative deadlines flag in the smart_issue table for the given overrule issue ids.
	 *
	 * @param integer[] $issueIds Overrule Issue ids to upgrade.
	 */
	private function raiseFlagsForIssueIds( array $issueIds )
	{
		LogHandler::Log( self::NAME, 'INFO', 'Raising flags for overrule issue ids: '.implode(',',$issueIds) );
		$dbDriver = DBDriverFactory::gen();	
		$issTbl = $dbDriver->tablename( 'issues' );
		$sql =  'UPDATE '.$issTbl.' SET `calculatedeadlines` = ? '.
				'WHERE `id` IN ( '.implode(',',$issueIds).' ) ';
		$params = array( 'on' );

		$sth = $dbDriver->query( $sql, $params );
		if( !$sth ) {
			LogHandler::Log( self::NAME, 'ERROR', 'Failed raising flag for overrule issue ids.' );
			$this->upgradeSuccessful = false;
		} else {
			LogHandler::Log( self::NAME, 'INFO', 'Successfully raised flags for overrule issue ids.' );
		}
	}
	
}
