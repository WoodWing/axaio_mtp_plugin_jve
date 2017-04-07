<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Business logics of the the deadline feature. This module is responsible for brand/issue deadline
 * (re)calculations. 
 *
 * The brand is seen as a 'template' of deadlines when calculating deadlines for issues. 
 * It is 'inheriting' relative deadlines set for statuses and makes all that concrete, by
 * storing absolute deadlines for all possible category/status combinations.
 *
 * When the issue's deadline (property) changes, the deadlines of the categories, statuses
 * and objects needs to be recalculated. And the same, when the Category deadline is adjusted by,
 * admin user, the status- and object deadlines needs to be recalculated.
 */

class BizDeadlines
{ 
	/**
	 * Updates issue properties (in the form of a DB row). This can be used to update the
	 * issue deadline. Note that this does -not affect nor recalc deadlines for the issue.
	 * After this function, call updateRecalcIssueDeadlines() to such thing. Or, when the 
	 * deadline is set empty, call deleteDeadlines().
	 *
	 * @param integer $issueId
	 * @param array $values List of issue properties values to be updated.
	 * @return bool True if the update succeeded, false otherwise.
	 */
	public static function updateIssue( $issueId, $values )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		return DBIssue::updateIssue( $issueId, $values );
	}

	/**
	 * Recalculates the Category/Status/Object deadlines for a new given issue deadline and 
	 * updates the DB with the calculated values. The issue deadline itself must be valid
	 * and set (not empty) and already be stored in the DB (along with other issue properties).
	 * Any customized deadlines will get lost; overwritten with new calculated values.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param string $issueDeadline
	 */
	public static function updateRecalcIssueDeadlines( $pubId, $issueId, $issueDeadline )
	{
		// Recalculate all category deadlines and update the outcome directly into DB.
		self::updateRecalcSectionDefs( $pubId, $issueId, $issueDeadline );

		// Recalculate all status deadlines and update the outcome directly into DB. 
		self::updateRecalcIssueSectionStates( $pubId, $issueId, $issueDeadline );

		// Recalculate all object deadlines and update the outcome directly into DB. 
		self::updateObjectDeadlines( $issueId );
	}
	
	public static function insertSectionStateDef( $categoryId, $statusId, $values, $updateIfExists = false )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		return DBWorkflow::insertSectionStateDef( $categoryId, $statusId, $values, $updateIfExists );
	}

	/**
	 * Inserts a Section for issue $issueId, defined by $categoryId
	 *
	 * @param int $issueId Id of the issue to create the section for
	 * @param int $categoryId Id of the category to be used.
	 * @param array $values List of values to be updated, indexed by fieldname.
	 *              The array does NOT need to contain all values, only values that are to be updated.
	 * @param bool $updateIfExists
	 */
	public static function insertIssueSection( $issueId, $categoryId, $values, $updateIfExists = true )
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		DBSection::insertIssueSection( $issueId, $categoryId, $values, $updateIfExists );
	}

	/**
	 * Adds a record in smart_issuesectionstate table.
	 *
	 * When $updateIfExists is set to true, the record will be updated if there's an existing record,
	 * otherwise function will raise error.
	 * When no existing record is found, a new one is added.
	 *
	 * @param int $issueId
	 * @param int $categoryId
	 * @param int $statusId
	 * @param array $values
	 * @param bool $updateIfExists
	 * @return mixed False when the insert fails, the new db id when the insertion is successful.
	 */
	public static function insertIssueSectionState( $issueId, $categoryId, $statusId, $values, $updateIfExists = true )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		return DBWorkflow::insertIssueSectionState( $issueId, $categoryId, $statusId, $values, $updateIfExists );
	}
	
	public static function getPublId( $issueId )
	{
		$issue = self::getIssue( $issueId );
		if( $issue ) {
			return $issue['publication'];
		} 
		return null;   
	}

	public static function listSectionStateDefs( $categoryId, $fieldnames = '*' )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		return DBWorkflow::listSectionStateDefs( $categoryId, $fieldnames );
	}

	private static function findRelativeStatusDeadlineByStatusId( $sectionStateDefs, $statusId )
	{
		$result = null;
		foreach ( $sectionStateDefs as $sectionstatedef ) {
			if ( $sectionstatedef['state'] == $statusId ) {
				$result = $sectionstatedef;
				break;
			}
		}
		return $result;
	}
	
	public static function listIssueSectionStates( $issueId, $categoryId, $fieldnames = '*' )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		return DBWorkflow::listIssueSectionStates( $issueId, $categoryId, $fieldnames );
	}
	
	private static function findAbsoluteStatusDeadlineByStatusId( $issueSectionStates, $statusId )
	{
		$result = null;
		foreach( $issueSectionStates as $issueSectionState ) {
			if( $issueSectionState['state'] == $statusId ) {
				$result = $issueSectionState;
				break;
			}
		}
		return $result;
	}

	public static function listIssueSections( $issueId, $fieldnames = '*' )
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		return DBSection::listIssueSections( $issueId, $fieldnames );   
	}

	private static function findAbsoluteCategoryDeadlineByCategoryId( $issueSections, $categoryId )
	{
		$result = null;
		foreach( $issueSections as $issueSection ) {
			if( $issueSection['section'] == $categoryId ) {
				$result = $issueSection;
				break;
			}
		}
		return $result;
	}

	/**
	 * Recalculates all category deadlines for a given brand/issue. It returns a category DB row
	 * enriched with 'deadline' and 'deadlinerelative' that are recalculated. This information
	 * is -not- stored at the DB (call updateRecalcSectionDefs() to do such).
	 *
	 * When the issue deadline has changed, the $reset flag can be raised to reflect that deadline
	 * through all categories. In that mode, manual justifications to category deadlines are not 
	 * respected on purpose and all relative deadlines (to the issue) obviously become zero.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param string $issueDeadline
	 * @param boolean $reset
	 * @return array Category DB row enriched with calc deadline info. See above.
	 */
	public static function recalcSectionDefs( $pubId, $issueId, $issueDeadline, $reset )
	{
		// Get all category definitions for the brand (or overrule issue) from DB
		// with their relative deadlines.
		$sectionDefs = self::listSectionDefs( $pubId, $issueId );
		
		// Get all absolute category deadlines from DB.
		$issueSections = self::listIssueSections( $issueId );

		// Enrich all category definitions with recalculated deadlines.		
		foreach( $sectionDefs as $categoryId => & $sectionDef ) {
		
			// Find out if there is an absolute deadline configured for this category.
			$curIssueSection = self::findAbsoluteCategoryDeadlineByCategoryId( $issueSections, $categoryId );

			// Fall back to issue's deadline when caller asked for reset or when deadline not configured yet.
			if( !$curIssueSection || $reset ) {
				$sectionDef['deadline'] = DateTimeFunctions::calcTime( $issueDeadline, -$sectionDef['deadlinerelative'] );
				$sectionDef['deadline_edit'] = false;
			} else { // Set the Category deadline and calc category's deadline relative to issue's deadline.
				$sectionDef['deadline'] = $curIssueSection['deadline'];
				if( empty($sectionDef['deadline']) ) {
					$sectionDef['deadline'] = $issueDeadline;
				}
				$sectionDef['deadline_edit'] = ($sectionDef['deadline'] != DateTimeFunctions::calcTime( $issueDeadline, -$sectionDef['deadlinerelative'] ));
				$sectionDef['deadlinerelative'] = DateTimeFunctions::diffIsoTimes( $issueDeadline, $sectionDef['deadline'] );
			}
			$isoDeadline = DateTimeFunctions::iso2time($sectionDef['deadline']);
			$sectionDef['deadline_nonworking'] = DateTimeFunctions::nonWorkDay( $isoDeadline );
			$sectionDef['deadline_toolate'] = $isoDeadline > DateTimeFunctions::iso2time( $issueDeadline );
		}
		return $sectionDefs;
	}

	/**
	 * Same as recalcSectionDefs() but now the calculated deadlines are stored into the DB.
	 * Any manual justifications on category deadlines will be lost.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param string $issueDeadline
	 */
	public static function updateRecalcSectionDefs( $pubId, $issueId, $issueDeadline )
	{
		$sectiondefs = self::recalcSectionDefs( $pubId, $issueId, $issueDeadline, true ); // reset=true
		foreach( $sectiondefs as $categoryId => $sectiondef ) {
			$values = array( 'deadline' => $sectiondef['deadline'] );
			self::insertIssueSection( $issueId, $categoryId, $values, true );
		}
	}
	
	/**
	 * Recalculates status deadlines for a given issue/category. It returns an array with two
	 * items: 'deadline' and 'deadlinerelative' that are recalculated. This information
	 * is -not- stored at the DB (call updateRecalcIssueSectionStates() to do such).
	 *
	 * When the issue- or category deadline has changed, the $reset flag can be raised to reflect 
	 * that deadline through all statuses. In that mode, manual justifications to status deadlines  
	 * are not respected on purpose and all relative deadlines (to the issue) obviously become zero.
	 *
	 * When there are no -relative- status deadlines configured at brand level, there won't be
	 * -absolute- status deadlines returned (configured at issue level). Nevertheless, all combinations
	 * are returned (and must all be stored in DB later). Those rows can be recognized by empty 'deadline'
	 * and zerofied 'deadlinerelative'.
	 *
	 * @param string $categoryDeadline
	 * @param string $preDeadline The previous deadline; That is the issue deadline for the first call, and the category deadline for next calls.
	 * @param array $issueSectionStates Pass the result of listIssueSectionStates().
	 * @param array $sectionStateDefs Pass the result of listSectionStateDefs().
	 * @param integer $statusId Status id.
	 * @param boolean $reset
	 * @return array Calculated deadline info. See above.
	 */
	public static function recalcIssueSectionState( $categoryDeadline, $preDeadline, $issueSectionStates, $sectionStateDefs, $statusId, $reset )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		$retVals = array();

		// Get the relative deadline configured at Workflow Setup and calculate the absolute status deadline.
		$sectionStateDef = self::findRelativeStatusDeadlineByStatusId( $sectionStateDefs, $statusId );

		// Lookup absolute status deadline configuration made at Issue Deadline setup.
		$curIssueSectionState = $reset ? null : self::findAbsoluteStatusDeadlineByStatusId( $issueSectionStates, $statusId );

		// Get the status deadline and calculate the corresponding relative deadline
		if( $curIssueSectionState ) {
			$retVals['deadline'] = $curIssueSectionState['deadline'];
			if( empty($retVals['deadline']) ) {
				$retVals['deadline'] = $preDeadline;
			}
			$retVals['deadlinerelative'] = DateTimeFunctions::diffIsoTimes( $preDeadline, $retVals['deadline'] );
			$retVals['deadline_edit'] = $sectionStateDef['deadlinerelative'] != $retVals['deadlinerelative'];
		} else {
			if( $sectionStateDef['deadlinerelative'] > 0 ) {
				$retVals['deadlinerelative'] = $sectionStateDef['deadlinerelative'];
				$retVals['deadline'] = DateTimeFunctions::calcTime( $preDeadline, -$retVals['deadlinerelative'] );
			} else {
				$retVals['deadlinerelative'] = 0;
				if( $preDeadline == $categoryDeadline ) {
					$retVals['deadline'] = $categoryDeadline;
				} else { // BZ#22854 - when preDeadline contain value other than categoryDeadline, it means there exist previous status deadline that should use
					$retVals['deadline'] = $preDeadline;
				}
			}
			$retVals['deadline_edit'] = false;
		}
		$isoDeadline = DateTimeFunctions::iso2time($retVals['deadline']);
		$retVals['deadline_nonworking'] = DateTimeFunctions::nonWorkDay( $isoDeadline );
		$retVals['deadline_toolate'] = $isoDeadline > DateTimeFunctions::iso2time( $preDeadline );
		return $retVals;
	}

	/**
	 * Same as recalcIssueSectionState() but now the calculated deadlines are stored into the DB.
	 * Any manual justifications on status deadlines will be lost.
	 *
	 * @param integer $pubId
	 * @param integer $issueId
	 * @param string $issueDeadline
	 */
	public static function updateRecalcIssueSectionStates( $pubId, $issueId, $issueDeadline )
	{
		$sectiondefs = self::recalcSectionDefs( $pubId, $issueId, $issueDeadline, false ); // reset=false
		$statedefs = self::listStateDefs( 0, $issueId, 'DESC' );
		$workflowdefs = self::listWorkflowDefs( 0, $issueId );
		//$sectiondefs = self::listSectionDefs( $pubId, $issueId );
		foreach( $sectiondefs as $categoryId => $sectiondef ) {
			$sectionStateDefs = self::listSectionStateDefs( $categoryId );
			$issueSectionStates = self::listIssueSectionStates( $issueId, $categoryId, true );
			foreach ( $workflowdefs as /*$workflowdefid =>*/ $workflow ) {
				$filteredstatedefs = self::filterStateDefsByType( $statedefs, $workflow['name'] );
				$curStatusDeadline = $sectiondef['deadline'];
				//if( empty($curStatusDeadline) ) {
				//	$curStatusDeadline = $issueDeadline;
				//}
				foreach ( array_keys($filteredstatedefs) as $statusId ) {
					$issCatStatusValues = self::recalcIssueSectionState( $sectiondef['deadline'], $curStatusDeadline, 
										$issueSectionStates, $sectionStateDefs, $statusId, true ); // reset=true
					$updateValues = array( 'deadline' => $issCatStatusValues['deadline'] ); // do not save relative (only 'deadline')
					self::insertIssueSectionState( $issueId, $categoryId, $statusId, $updateValues, true );
					$curStatusDeadline = $issCatStatusValues['deadline'];
				}                
			}
		}
	}

	public static function updateSectionDef( $categoryId, $values )
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		return DBSection::updateSectionDef( $categoryId, $values );
	}
	
	public static function updateStateDef( $statusId, $values )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		return DBWorkflow::updateStateDef( $statusId, $values );
	}

	public static function getPublication( $pubId )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		return DBPublication::getPublication( $pubId );
	}
	
	public static function getIssue( $issueId )
	{    
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$issue = DBIssue::getIssue( $issueId );
		$issue['deadline_edit'] = $issue['deadline'] != $issue['publdate'];
		$isoDeadline = DateTimeFunctions::iso2time($issue['deadline']);
		$issue['deadline_nonworking'] = DateTimeFunctions::nonWorkDay( $isoDeadline );
		$issue['deadline_toolate'] = $isoDeadline > DateTimeFunctions::iso2time( $issue['publdate'] );
		return $issue;
	}
	
	public static function listPublicationIssues( $pubId )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		return DBIssue::listPublicationIssues( $pubId );
	}
	
	public static function listPublications( $fieldnames = '*' )
	{
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		return DBPublication::listPublications( $fieldnames );   
	}
	
	public static function listSectionDefs( $pubId, $issueId = 0, $fieldnames = '*' )
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		if ( $issueId ) {
			return DBSection::listIssueSectionDefs( $issueId, $fieldnames );	
		} else {
			return DBSection::listPublSectionDefs( $pubId, $fieldnames );	
		}
	}
	
	public static function getIssueSection( $issueId, $sectionid )
	{
		require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
		return DBSection::getIssueSection( $issueId, $sectionid );
	}

	public static function listWorkflowDefs( $pubId, $issueId = 0 )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		if ( $issueId ) {
			return DBWorkflow::listIssueWorkflowDefs( $issueId, false );	
		} else {
			return DBWorkflow::listPublWorkflowDefs( $pubId );	
		}
	}

	public static function getTranslatedWorkflowName( $workflowname )
	{
		$workflowname = trim( $workflowname );
		$map = getObjectTypeMap();
		return $map[$workflowname];
	}

	public static function getTypeIcon( $typename )
	{
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		return BizObject::getTypeIcon( $typename );
	}
	
	public static function listStateDefs( $pubId, $issueId = 0, $sortorder = 'ASC', $fieldnames = '*' )
	{
		require_once BASEDIR.'/server/dbclasses/DBWorkflow.class.php';
		if( $issueId ) {
			return DBWorkflow::listIssueStateDefs( $issueId, $sortorder, $fieldnames );
		} else {
			return DBWorkflow::listPublStateDefs( $pubId, $sortorder, $fieldnames );                
		}
	}

	public static function filterStateDefsByType( $statedefs, $typename )
	{
		$result = array( );
		foreach ( $statedefs as $statusId => $statedef ) {
			if ( $statedef['type'] === $typename ) {
				$result[$statusId] = $statedef;   
			}
		}
		return $result;
	}
	
	public static function filterStatesByStateDef( $states, $statusId )
	{
		$result = array( );
		foreach ( $states as $stateid => $state ) {
			if ( $state['state'] == $statusId ) {
				$result[$stateid] = $state;
			}
		}
		return $result;
	}
	
	public static function filterStatesBySectionDef( $states, $categoryId )
	{
		$result = array( );
		foreach ( $states as $stateid => $state ) {
			if ( $state['section'] === $categoryId ) {
				$result[$stateid] = $state;
			}
		}
		return $result;
	}
	
	public static function updateObjectDeadlines( $issueId )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		DBObject::recalcObjectDeadlines($issueId);
	}
	
	/**
	 * Clears the issue deadline property and removes all its related deadline information, including
	 * category/status/object deadlines. This affects absolute deadlines only and does not touch
	 * relative deadlines configured at brand level nor for statuses.
	 *
	 * @param integer $issueId
	 */
	public static function deleteDeadlines($issueId)
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		DBIssue::deleteDeadlines($issueId);
	}

	/**
	 * Returns the deadline color (red/orange/green) depending on the given dates.
	 * This takes the DEADLINE_WARNTIME setting into account; 
	 * - exceeded => red
	 * - within   => orange
	 * - before   => green
	 *
	 * @param string $now Current dateTime (in ISO format)
	 * @param string $deadline The deadline of subject dateTime (in ISO format)
	 * @return string Color in 6 hex digit RGB format with # prefix
	 */
	public static function deadlineColor( $now, $deadline )
	{
		$timeBeforeDeadline = DateTimeFunctions::diffTime( 
					DateTimeFunctions::iso2time( $deadline ), 
					DateTimeFunctions::iso2time( $now ) );
		if( $timeBeforeDeadline > DEADLINE_WARNTIME ) {
			$result = '#4FFF4F'; // green / no rush
		} else if ( $timeBeforeDeadline > 0 ) {
			$result = '#FFFF4F'; // orange / warn
		} else {
			$result = '#FF0000'; // red / expired
		}
		return $result;
	}

	/**
	 * This method updates the deadlines of children within a dossier or placed
	 * on a layout. This is only done if the children have no object-target issue.
	 * Next to that we only handle images and articles. This function is called in case
	 * the deadline of the parent is recalculated. If the deadline of the parent is
	 * set by hand call setDeadlinesIssuelessChilds() afterwards.
	 * @param integer $parent Parent of the children
	 */
	public static function recalcDeadlinesIssuelessChilds( $parent )
	{
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR . '/server/smartevent.php';

		$childs = DBObjectRelation::getObjectRelations( $parent, 'childs' );
		if ($childs) foreach ($childs as $child) {
			$childId = $child['child'];
			$type = DBObject::getObjectType( $childId );
			if ( self::canInheritParentDeadline( $type ) ) {
				$objectTargets = BizTarget::getTargets( '', $childId );
				if (!$objectTargets) {
					$issueIdsChild = BizTarget::getRelationalTargetIssuesForChildObject( $childId );

					// Determine if it is normal brand or overruleIssue.
					$overruleIssueId = 0;
					if( count( $issueIdsChild ) == 1 ) { // When there are more than 1 issue targeted to an Object, it's definitely not an overruleIssue, so don't need to check.
						$overruleIssueId = DBIssue::isOverruleIssue( $issueIdsChild[0] ) ? $issueIdsChild[0] : 0;
					}

					$pubId = DBObject::getObjectPublicationId( $childId );
					if( BizPublication::isCalculateDeadlinesEnabled( $pubId, $overruleIssueId ) ) {
						$childRow = DBObject::getObjectRows( $childId );
						$deadlines = DBObject::objectSetDeadline( $childId, $issueIdsChild, $childRow['section'], $childRow['state'] );
						new smartevent_deadlinechanged( null, $childId, $deadlines['Deadline'], $deadlines['DeadlineSoft'] );
					}
				}
			}
		}
	}

	/**
	 * This method updates the deadlines of children within a dossier or placed
	 * on a layout. This is only done if the children have no object-target issue.
	 * Next to that we only handle images and articles. This function is called in case
	 * the deadline of the parent is set by hand. If the deadline of the parent is
	 * recalculated call recalcDeadlinesIssuelessChilds() afterwards.
	 * @param integer $parent Parent of the children
	 * @param string $deadline Set deadline
	 */
	public static function setDeadlinesIssuelessChilds( $parent, $deadline )
	{
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		require_once BASEDIR . '/server/dbclasses/DBObjectRelation.class.php';
		require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
		require_once BASEDIR . '/server/smartevent.php';

		$childs = DBObjectRelation::getObjectRelations( $parent, 'childs' );
		if ($childs) foreach ($childs as $child) {
			$childId = $child['child'];
			$type = DBObject::getObjectType( $childId );
			if ( self::canInheritParentDeadline( $type ) ) {
				$objectTargets = BizTarget::getTargets( '', $childId );
				if (!$objectTargets) {
					DBObject::setObjectDeadline( $childId, $deadline );
					$deadlinesoft = DateTimeFunctions::calcTime( $deadline, -DEADLINE_WARNTIME );
					new smartevent_deadlinechanged( null, $childId, $deadline, $deadlinesoft );
				}
			}
		}
	}

	/**
	 * Checks if the deadline is before the earliest deadline of each issue/category
	 * combination. If not, a BizException is thrown.
	 *
	 * @param array $issueIds Issues of which the deadline setting is tested.
	 * @param integer $section Applicable category Id
	 * @param string $deadline Deadline to test against (iso format)
	 * @throws BizException
	 */
	static public function checkDeadline($issueIds, $section, $deadline )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssueSection.class.php';
			foreach ($issueIds as $issueId) {
				$detrow = DBIssueSection::getIssueSection($issueId, $section);
				if ($detrow && DateTimeFunctions::diffIsoTimes( $deadline, $detrow["deadline"] ) > 0 ) {
					throw new BizException( 'BEYOND_DATE', 'Client', $deadline );
			}
		}
	}

	/**
	 * Check if an object a certain type can inherit the deadline from a parent it
	 * is placed on or contained by. Inheritance takes places by taking over the issues
	 * of the parent. Based on these issues and the childs own category/state a
	 * deadline can be calculated.
	 *
	 * @staticvar array $canInheritParentDeadline Array with object types that can inherit deadline
	 * @param string $objectType
	 * @return true if inheritance is possible else false
	 */
	static public function canInheritParentDeadline( $objectType )
	{
		$canInheritParentDeadline = self::getCanInheritParentDeadline();

		$result = isset($canInheritParentDeadline[$objectType]) ? $canInheritParentDeadline[$objectType] : false;

		return $result;
	}

	/**
	 * Returns certain object types that can inherit the deadline from a parent it
	 * is placed on or contained by. Inheritance takes places by taking over the issues
	 * of the parent. Based on these issues and the childs own category/state a
	 * deadline can be calculated.
	 *
	 * @staticvar array $canInheritParentDeadline Array with object types that can inherit deadline
	 * @return array with object types that can inherit the deadline from a parent
	 */
	static public function getCanInheritParentDeadline()
	{
		static $canInheritParentDeadline = array('Article' => true, 'Image' => true, 'Spreadsheet' => true );

		return $canInheritParentDeadline;
	}

	/**
	 * Check if an object of a certain type can pass the deadline to its children.
	 * (placed on / contained by). Inheritance takes places by taking over the issues
	 * of the parent. Based on these issues and the childrens own category/state a
	 * deadline can be calculated.
	 *
	 * @staticvar array $canPassDeadlineToChild Array with object types that can pass deadline
	 * @param string $objectType
	 * @return true if passing deadline is possible else false
	 */
	static public function canPassDeadlineToChild( $objectType )
	{
		static $canPassDeadlineToChild = array('Dossier' => true, 'Layout' => true, 'LayoutModule' => true, 'PublishForm' => true );

		$result = isset($canPassDeadlineToChild[$objectType]) ? $canPassDeadlineToChild[$objectType] : false;

		return $result;
	}
}
