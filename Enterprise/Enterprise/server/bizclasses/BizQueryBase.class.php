<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
**/

require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';

class BizQueryBase
{
    /**
     * Checks all given rows for personal statuses (StateId==-1) and fills in the status name (State).
     * Assumed is that if State is requested, StateId is returned too (see buildSelect function that takes care about this).
     *
     * Also removes the # prefix for status colors (StateColor).
     *
     * @param array $rows
     */
	static protected function resolvePersonalStatusesAndFixColors( array &$rows )
	{
		if ( count($rows) == 0 ) {
			return;
		}
		reset($rows);
		$currow = current($rows);
		if (is_array($currow)) {
			$updatePersonal = array_key_exists('StateId', $currow) && array_key_exists('State', $currow);
			$updateColor = array_key_exists('StateColor', $currow);
			if( $updatePersonal || $updateColor ) { // this check is only for performance reasons
				static $personalStatus = null;
				if( $updatePersonal ) {
					if( is_null($personalStatus) ) { // Performance: Avoid calling 50x same translation
						$personalStatus = BizResources::localize('PERSONAL_STATE'); 
					}
				}
				foreach ( $rows as &$row ) {
					if( $updatePersonal ) {
						if ( $row['StateId'] == -1 ) { // happens for browse queries and user queries
							$row['State'] = $personalStatus;
						}
						else if( is_null($row['StateId']) && is_null($row['State']) ) { // happens for named queries
							$row['StateId'] = -1;
							$row['State'] = $personalStatus;
						}
					}
					if( $updateColor ) {
						if( $updatePersonal && $row['StateId'] == -1 ) {
							$row['StateColor'] = PERSONAL_STATE_COLOR;
						}
						$row['StateColor'] = substr( $row['StateColor'], 1 ); // remove # prefix
					}
				}
			}
		}
	}

	/**
	 * Determines the query mode, which tells what columns to return (on queries).
	 *
	 * @param string $ticket
	 * @param boolean $forceapp
	 * @return string The query mode
	 */
	static protected function getQueryMode( $ticket, $forceapp )
	{
		if( $forceapp ) {
			$mode = $forceapp;
		} else {
			$app = DBTicket::DBappticket( $ticket );
			if( stristr( $app, 'web' ) ) {
				$mode = 'web';
			} elseif( stristr( $app, 'indesign' ) ) {
				$mode = 'indesign';
			} elseif( stristr( $app, 'content station' ) ) {
				$mode = 'contentstation';
			} elseif( stristr( $app, 'incopy' ) ) {
				$mode = 'incopy';
			} else {
				$mode = '';
			}
		}
		return $mode;
	}

	/**
	 * Determines the query action which tells what columns to return (on queries).
	 * @param string $mode Taken from {@link: getQueryMode}
	 * @return string The query action, 'QueryOut<app>'
	 */
    static private function getQueryAction( $mode )
	{
		switch( $mode ) {
			case 'web':
				$action = "QueryOutWeb";
				break;
			case 'indesign':
				$action = "QueryOutInDesign";
				break;
			case 'incopy':
				$action = "QueryOutInCopy";
				break;
			case 'contentstation':
				$action = "QueryOutContentStation";
				break;
			case 'Planning':	// Content Planning view, used by Content Station
				$action = "QueryOutPlanning";
				break;
			default:
				$action = "QueryOut";
				break;
		}
		return $action;
	}

	/**
	 * Determines what columns to return (on queries).
	 * $areas could be Workflow or Trash(Where deleted objects reside). When it is Trash Area, the column varies as
	 * columns like 'Modifier' and 'Modified' are not needed but we need 'Deleter' and 'Deleted'
	 *
	 * @param string $mode Taken from {@link: getQueryMode}
	 * @param array $areas 
	 * @return array of property names (strings)
	 */
	static protected function getQueryProperties( $mode, $areas = array( 'Workflow' ) )
	{
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBActionproperty.class.php';

		$dbDriver = DBDriverFactory::gen();

		// These are always needed (clients are depending on it!)
		$needs = array( 'ID', 'Type', 'Name' );
		$orgNeedsCount = count( $needs );
		$action = self::getQueryAction( $mode );

		// Get actionproperties for this action to limit fields
		$sth = DBActionproperty::getPropertyUsagesSth( 0, null, $action );
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$needs[] = $row['property'];
		}
		// When none, fall back at actionprops defined for common QueryOut
		if( $orgNeedsCount == count( $needs ) && $action != 'QueryOut' ) {
			// No entries: try general queryout
			$sth = DBActionproperty::getPropertyUsagesSth( 0, null, 'QueryOut' );
			while( ( $row = $dbDriver->fetch( $sth ) ) ) {
				$needs[] = $row['property'];
			}
		}
		// When still none, do backwards compatible mode
		if( $orgNeedsCount == count( $needs ) ) {
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			if( $mode == 'web' ) {
				$needs = BizProperty::getWebQueryPropIds( $areas );
			} else {
				$needs = BizProperty::getStandardQueryPropIds( $areas );
			}
		}
		return $needs;
	}

	public static function queryorder2SQL($queryorder, $nq_orderby)
	{
		// Get the complete map of Property <-> smart_objects
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		$objFields = BizProperty::getMetaDataObjFields();
		$joins = BizProperty::getJoinProps();
		$joinFields = BizProperty::getJFldProps();

		$queryorder = self::filterOrderByFields($queryorder);
		$queryorder = self::addOrderByFields($queryorder);

		$orderBy = '';
		if (empty($queryorder)) {
			if (empty($nq_orderby)) {
				$orderBy = 'ORDER BY o.`id` ASC';
			} else {
				$orderBy = $nq_orderby;
			}
		} else {
			$orderByColumns = array();
			foreach ($queryorder as $qo) {
				$order = ($qo->Direction === false) ? 'DESC' : 'ASC';
				// BZ#32400 - Don't add the order when order property is empty or non boolean order direction
            	if( empty($qo->Property) || !is_bool($qo->Direction) ) {
					if (empty($nq_orderby)) {
						$orderBy = 'ORDER BY o.`id` ASC';
            		} else {
						$orderBy = $nq_orderby;
					}
					break;
				}
				else if ($qo->Property == 'Version') {
					$orderByColumns['o.`majorversion`'] = $order;
					$orderByColumns['o.`minorversion`'] = $order;
				} else if ($qo->Property == 'State') {
					$orderByColumns['sta.`code`'] = $order;
					$orderByColumns['sta.`state`'] = $order;
					self::requireJoin4Order('sta');
				} else if ($qo->Property == 'Issue' || $qo->Property == 'Issues') {
			   		// BZ#16205 Sort on issues (this only works correct when objects have zero or one issue)
			   		// don't require tar here, it's automatically added
			   		self::requireJoin4Order('iss');
					$orderByColumns['iss.`code`'] = $order;
					$orderByColumns['iss.`name`'] = $order;
			   	} else if ($qo->Property == 'Editions') { // BZ#16685 Enable editions sorting
			   		self::requireJoin4Order('edi');
					$orderByColumns['edi.`code`'] = $order;
					$orderByColumns['edi.`name`'] = $order;
				} else {
					if ($qo->Property == 'LockedBy') {
						// don't require lcb here, it's automatically added
						$alias = $joins[$qo->Property];
						$jfield = $joinFields[$qo->Property];
						self::requireJoin4Order($alias);
						$column = "$alias.`$jfield` ";
					} else if ($qo->Property == 'Category') {
						$alias = $joins[$qo->Property];
						$jfield = 'section';
						self::requireJoin4Order($alias);
						$column = "$alias.`$jfield` ";
					} else if (!empty($joins[$qo->Property])) {
						$alias = $joins[$qo->Property];
						$jfield = $joinFields[$qo->Property];
						self::requireJoin4Order($alias);
						$column = "$alias.`$jfield` ";
					} else if (BizProperty::isCustomPropertyName($qo->Property)) {
						$column = "o.`" . $qo->Property . "`";
					} else {
						$column = "o.`" . $objFields[$qo->Property] . "`";
					}
					$orderByColumns[$column] = $order;
				}
				// Debug: Fail when property has no field in the object-table AND no alias-joinfield (but respect custom props)
				/*
				if( LogHandler::debugMode() ) {
					if( (!array_key_exists( $qo->Property, $objFields ) || !$objFields[$qo->Property]) && stripos( $qo->Property, 'c_' ) !== 0  && !$joins[$qo->Property]) {
						throw new BizException( '', 'Server', '', __METHOD__.' - Sorting on unknown property: '.$qo->Property );
					}
				}
				*/
			}
            if( !empty($orderByColumns) ) {
				$orderBy = self::createOrderByStatement($orderByColumns);
            }
		}
		return $orderBy;
	}
    
    /*
     * Based on the order by columns and the sorting direction the order by statement
     * is made.
     * @param array $orderByColumns contains the column names (keys) and the sorting direction (value)
     * @return string with order by clause 
     */
    public static function createOrderByStatement($orderByColumns)
    {
    	$orderByStatement = '';
    	$separator = ',';
    	foreach($orderByColumns as $column => $sortOrder) {
    		$orderByStatement .= " $column $sortOrder$separator";
    	}
    	
    	if (!empty($orderByStatement)) {
    		$orderByStatement = 'ORDER BY' . substr($orderByStatement, 0, -1);
    		//-1 to remove last separator
    	}
    	
    	return $orderByStatement;
    }

    /**
     * Builds a list of properties for the given columns.
     * This is used to resolve the display names to show in column headers
     * of query results.
     *
     * @param array $rows List of column names.
     * @return array of Property objects.
     */
	static private function doGetColumns( $rows )
	{
		if( empty($rows) ) return null;

		// Get built-in props
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		$propInfos = BizProperty::getPropertyInfos();

		// Get custom props, which can also include customized standard properties
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		$customProps = DBProperty::getProperties( 0, '', false );

		// Build props with internal name and display names
		$columns = array_keys(current($rows));
		$properties = array();
		foreach( $columns as $col ) {
			if( isset($customProps[$col])) {
				$displayName = $customProps[$col]->DisplayName;
				$propType    = $customProps[$col]->Type;
			} elseif( isset($propInfos[$col])) {
				$displayName = $propInfos[$col]->DisplayName;
				$propType    = $propInfos[$col]->Type;
			} else { // typically happens for child row props like IDC, Snippet, etc
				$displayName = $col;
				$propType    = 'string';
			}
			$properties[] = new Property( $col, $displayName, $propType );
		}
		return $properties;		
	}

    static protected function getColumns( $rows )          { return self::doGetColumns( $rows ); }
    static protected function getChildColumns( $rows )     { return self::doGetColumns( $rows ); }
    static protected function getComponentColumns( $rows ) { return self::doGetColumns( $rows ); }

    static protected function getComponents($rows)
    {
        $returnrows = array();
        foreach ($rows as $row) {
        	$returnrows[] = self::getComponentChildRow($row);
        }
        return $returnrows;
    }

    static protected function getComponentChildRow($row)
    {
		$parentid = $row['Parent'];
		if (isset($row['Page'])) {
			$row['Parent'] = $row['Page'];
		}
		else {
			$row['Parent'] = '';
		}
		/* Client expects the page number to be in the 'parent' tag. So we put
		 * the page number in the 'parent' tag. The page tag is ignored on client
		 * side */
    	return new ChildRow( array($parentid), self::childrow2string($row) );
    }

    static protected function getRows($rows)
    {
        $returnrows = array();
        foreach ($rows as $row) {
        	// >>> Removed PEAR SOAP hack since QueryObjects/NamedQuery runs through PHP SoapServer
			//$returnrow['Row'] = self::row2string($row);
	        //$returnrows[] = $returnrow;
	        $returnrows[] = self::row2string($row);
	        // <<<
        }
        return $returnrows;
    }

    static protected function getChildRow($row)
    {
		$parents = array();
        if (isset( $row['smart_parents'] ) && $row['smart_parents']) {
			foreach ($row['smart_parents'] as $parent) {
            	$parents[] = $parent['id'];
			}
	        unset( $row['smart_parents'] );
        }
        return new ChildRow( $parents, self::childrow2string($row) );
    }

    static protected function getChildRows($rows)
    {
        $returnrows = array();
        foreach ($rows as $row) {
        	$returnrows[] = self::getChildRow($row);
        }
        return $returnrows;
    }

    static protected function row2string($row)
    {
        $result = array();
		$row = self::replaceBooleans($row);

        $values = array_values( $row );
        foreach( $values as $value ) {
            $result[] = $value ? $value :'';
        }
        return $result;
    }

    static protected function childrow2string($row)
    {
		$result = array();
		$row = self::replaceBooleans($row);

        $values = array_values( $row );
    	foreach ($values as $value) {
		    $result[] = $value ? $value : '';
        }
        return $result;
    }

	/**
	 * This method replaces the value of boolean properties of string type. 
	 * The booleans are stored in the database in several ways (as shown below). 
	 * According to the WSDL, booleans shoud be 'true' or 'false' and therefore they
	 * need fixing before sending to clients through QueryObjects / NamedQuery responses.
	 * See the 'Repaired' column for booleans that are fixed (to 'true'/'false') by this function.
	 *
	 * Since v7.0.8 CopyrightMarked and DeadlineChanged are fixed. 
	 * Since v8.0.0 Flag property.
	 *
	 * Possible boolean properties:
	 *   ------------------------------------------------------------------------------
	 *   Property Name   | DB field        | DB type      | true   | false   | Repaired
	 *   ------------------------------------------------------------------------------
	 * - CopyrightMarked | copyrightmarked | varchar(255) | 'true' | 'false' | Yes, fixed since BZ#20301 / 7.0.8
	 * - DeadlineChanged | deadlinechanged | char(1)      | 'Y'    | 'N'     | Yes, fixed since BZ#20301 / 7.0.8
	 * - Flag            | flag            | mediumint(9) | 1      | 0       | Yes, since v8.0
	 * - LockForOffline  | lockoffline     | varchar(2)   | 'on'   | ''      | Yes
	 * - Indexed         | indexed         | varchar(2)   | 'on'   | ''      | Yes
	 * - Closed          | closed          | varchar(2)   | 'on'   | ''      | Yes
	 * - HasChildren     | <computed>      |              | 'on'   | ''      | Yes  (related to BizQuery::resolveHasChildren)
	 * - Custom property | C_...           | int(11)      |  1     | 0       | No
	 *   ------------------------------------------------------------------------------
	 * 
	 * @param array $row List of properties and their values
	 * @return array List of properties with correct boolean values
	 */
    static protected function replaceBooleans($row)
    {
		$boolProperties = BizProperty::getBoolTypeProperty();
		$boolProperties = array_flip($boolProperties);
		// Filter boolean properties and their values from the row
		$rowBooleanValues = array_intersect_key($row, $boolProperties);

		if(!empty($rowBooleanValues) ) {
			foreach ($rowBooleanValues as $key => $rowBooleanValue) {
				if (is_string($rowBooleanValue)) {
					// The below is strongly related to BizObject::queryRow2MetaData()
					$trimVal = trim(strtolower($rowBooleanValue)); // MSSQL fix BZ#8465
					if ($trimVal == 'on' || // Indexed, Closed, Flag, LockForOffline, HasChildren (on/<empty>)
						$trimVal == 'y' || // DeadlineChanged (Y/N)
						$trimVal == 'true' || // CopyrightMarked (true/false) -> Fixed for BZ#10541
						$trimVal == '1' ) { // repair old boolean fields that were badly casted/stored in the past
						$rowBooleanValues[$key] = 'true' ;
					} else {
						$rowBooleanValues[$key] = 'false';
					}
					// BZ#20301: Rewrote the "if(empty($rowBooleanValue))" check with the fragment above 
					//           since CopyrightMarked and DeadlineChanged failed: 'false' and 'N' are not empty!
				} else {
					// This below is done since v8.0. It was too risky for v7.0.x.
					// v7.3 clients are informed through BZ about this change.
					$rowBooleanValues[$key] = $rowBooleanValue ? 'true' : 'false'; // Repair 0/1/empty/null.
				}
				
			}
			$row = array_merge($row, $rowBooleanValues);
		}
		return $row;
    }
    
	static protected function requireJoin($joinalias = null)
	{
		static $requiredjoins = array();
		
		if (!empty($joinalias)) {
        	if (!isset($requiredjoins[$joinalias])) {
				$requiredjoins[$joinalias] = $joinalias;
				// also select dependencies
				$deps = self::joinDependencies();
				if (isset($deps[$joinalias])) {
					foreach ($deps[$joinalias] as $dep) {
						self::requireJoin($dep);
					}
				}
			}
		}
		
		return $requiredjoins;
	}

    static protected function requireJoin4Where( $joinalias = null )
    {
    	static $requiredjoins4where = array();
    	
        if (!empty($joinalias)) {
            $requiredjoins4where[$joinalias] = $joinalias;
            // also select dependencies
            $deps = self::joinDependencies();
            if (isset($deps[$joinalias])){
            	foreach ($deps[$joinalias] as $dep){
            		self::requireJoin4Where($dep);
            	}
            }
        }
        return $requiredjoins4where;
    }

    /**
     * Save join tables which have to be used for queries with a ORDER BY clause.
     *
     * @param string $joinalias table alias name
     * @return array key value array both with the table alias name
     */
    static protected function requireJoin4Order( $joinalias = null )
    {
    	static $requiredjoins4order = array();

    	if (!empty($joinalias)) {
            $requiredjoins4order[$joinalias] = $joinalias;
            // also select dependencies
            $deps = self::joinDependencies();
            if (isset($deps[$joinalias])){
            	foreach ($deps[$joinalias] as $dep){
            		self::requireJoin4Order($dep);
            	}
            }
        }
        return $requiredjoins4order;
    }

    /**
     * Returns all available joins for the SELECT or WHERE part.
     * If the list is changed also check joinDependencies
     * @see joinDependencies
     *
     * @return array
     */
	static protected function listAvailableJoins()
    {
        $joins = array();
        $joins['pub'] = "smart_publications pub ON (o.`publication` = pub.`id`)";
        $joins['sec'] = "smart_publsections sec ON (o.`section` = sec.`id`)";
        $joins['sta'] = "smart_states sta ON (o.`state` = sta.`id`)";
        $joins['ofl'] = "smart_objectflags ofl ON (o.`id` = ofl.`objid`)";
        $joins['lcb'] = "smart_objectlocks lcb ON (o.`id` = lcb.`object`)";
		$joins['lcc'] = "smart_users lcc ON (lcb.`usr` = lcc.`user` )";
        $joins['mdf'] = "smart_users mdf ON (o.`modifier` = mdf.`user`)";
        $joins['crt'] = "smart_users crt ON (o.`creator` = crt.`user`)";
        $joins['tar'] = "smart_targets tar ON (o.`id` = tar.`objectid`)";
        $joins['tar2'] = "smart_targets tar2 ON (o.`id` = tar2.`objectid` AND tar2.`issueid` > 0)";
        $joins['iss'] = "smart_issues iss ON (tar.`issueid` = iss.`id`)";
        $joins['cha'] = "smart_channels cha ON (tar.`channelid` = cha.`id`)";
        $joins['pch'] = "smart_channels pch ON (pub.`defaultchannelid` = pch.`id`)";
		$joins['rtu'] = "smart_users rtu ON (o.`routeto` = rtu.`user` )";
		$joins['rtg'] = "smart_groups rtg ON (o.`routeto` = rtg.`name` )";
		$joins['dlu'] = "smart_users dlu ON (o.`deletor` = dlu.`user` )";
		$joins['dlg'] = "smart_groups dlg ON (o.`deletor` = dlg.`name` )";
        $joins['ted'] = "smart_targeteditions ted ON (ted.`targetid` = tar.`id`)";
        $joins['edi'] = "smart_editions edi ON (ted.`editionid` = edi.`id`)";
        $joins['chi'] = "smart_objectrelations chi ON (o.`id` = chi.`child`)";
        $joins['par'] = "smart_objects par ON (chi.`parent` = par.`id`)";
        $joins['par2'] = "smart_objectrelations par2 ON (o.`id` = par2.`parent`)";
        $joins['chi2'] = "smart_objects chi2 ON (par2.`child` = chi2.`id`)";
        $joins['elm'] = "smart_elements elm ON (elm.`objid` = o.`id`)";
        return $joins;
    }

    /**
     * Returns join dependencies expect for dependency on the objects table (o)
     * Joins in the function listAvailableJoins sometimes depend on other tables, this
     * function returns them.
     *
     * @see listAvailableJoins
     * 
     * @return array key value array
     */
    static protected function joinDependencies()
    {
    	static $dependencies = array(
    		'lcc' => array('lcb'),
    		'iss' => array('tar'),
    		'cha' => array('tar'),
    		'pch' => array('pub'),
    		'ted' => array('tar'),
    		'edi' => array('ted'),
    		'par' => array('chi'),
    		'chi2' => array('par2'),
    	);
    	
    	return $dependencies;
    }

	/**
	 * Fills $rows with issue, issueid, issues, issueids, editions, editionids, channels, channelids - properties from targets.
	 * Only fills these values if they are in $reqprops. Method is called more than once for the same set of objects.
	 * To prevent that value, set in the first call, are overwritten special precautions had to be made.
	 *
	 * @param &array $rows rows to add the target-info to ($rows are adjusted by reference!!!)
	 * @param $targets array of array Target, indexed by objectid
	 * @param $reqprops array of props to fill.
	 * @param bool $keepRowValue
	 */
	static public function resolveTargets(&$rows, $targets, $reqprops, $keepRowValue=false)
	{
		$reqIssueIds = in_array('IssueIds', $reqprops) ? true : false;
		$reqEditionIds = in_array('EditionIds', $reqprops) ? true : false;
		$reqPubChannelIds = in_array('PubChannelIds', $reqprops) ? true : false;
		$reqIssues = in_array('Issues', $reqprops) ? true : false;
		$reqEditions = in_array('Editions', $reqprops) ? true : false;
		$reqPubChannels = in_array('PubChannels', $reqprops) ? true : false;
		$reqIssue = in_array('Issue', $reqprops) ? true : false;
		$reqIssueId = in_array('IssueId', $reqprops) ? true : false;
		if( in_array('Targets', $reqprops ) ) {
			$addTargets = true;
		} else {
			$addTargets = false;
		}
		//Merge targets (issueid, issue, issues, editions) with $rows
		foreach ($rows as &$row) {
			$objectid = $row['ID'];
			$issueids = array();
			$issuenames = '';
			$editionids = array();
			$editionnames = '';
			$channelids = array();
			$channelnames = '';
			// Initialize requested properties only for those objects of which they are not set. This makes it
			// possible to call this function two times for the same $rows but with two different sets of targets.
			if ($reqIssueIds) {$row['IssueIds'] = isset( $row['IssueIds'] ) ? $row['IssueIds'] : '';  }
			if ($reqEditionIds) {$row['EditionIds'] = isset( $row['EditionIds'] ) ? $row['EditionIds'] : '';}
			if ($reqPubChannelIds) {$row['PubChannelIds'] = isset( $row['PubChannelIds'] ) ? $row['PubChannelIds'] : '';}
			if ($reqIssues) {$row['Issues'] = isset( $row['Issues'] ) ? $row['Issues'] : '';}
			if ($reqEditions) {$row['Editions'] = isset( $row['Editions'] ) ? $row['Editions'] : '';}
			if ($reqPubChannels) {$row['PubChannels'] = isset( $row['PubChannels'] ) ? $row['PubChannels'] : '';}
			if ($reqIssue) {$row['Issue'] = isset( $row['Issue'] ) ? $row['Issue'] : '';}
			if ($reqIssueId && !$keepRowValue) {$row['IssueId'] = isset( $row['IssueId'] ) ? $row['IssueId'] : '';}
			if ( isset($targets[$objectid]) ) {
				$objecttargets = $targets[$objectid];
				foreach ($objecttargets as $objecttarget) {
					if ($objecttarget->Issue) {
						if (!empty($objecttarget->Issue->Id)) {
							// The IsRelational property is an internal property. That property is only set when using the
							// DBTarget::getArrayOfTargetsByObjectViewId function.
							// The IssueId and Issue properties are old and are a left over from the old structe where
							// a layout for example could only have one object target. Therefore we check if this is an object
							// target, and if so then set the name and id property of an issue.
							$isRelationalTarget = isset($objecttarget->IsRelational) ? $objecttarget->IsRelational : false;
							if ($reqIssueId && $isRelationalTarget == false) {
								$row['IssueId'] = $objecttarget->Issue->Id;
							}
							if ($reqIssue && $isRelationalTarget == false) {
								$row['Issue'] = $objecttarget->Issue->Name;
							}
						}
						if (!in_array($objecttarget->Issue->Id, $issueids)) {
							$issueids[] = $objecttarget->Issue->Id;
							$issuenames .= $objecttarget->Issue->Name . ',';
						}
					}
					if ($objecttarget->Editions) {
						foreach ($objecttarget->Editions as $curedition) {
							if (!in_array($curedition->Id, $editionids)) {
								$editionids[] = $curedition->Id;
								$editionnames .= $curedition->Name . ', ';
							}
						}
					}
					if ($objecttarget->PubChannel) {
						if (!in_array($objecttarget->PubChannel->Id, $channelids)) {
							$channelids[] = $objecttarget->PubChannel->Id;
							$channelnames .= $objecttarget->PubChannel->Name . ', ';
						}
					}
				}
				if ($reqIssueIds) {
					$row['IssueIds'] = '';
					$comma = "";
					foreach ($issueids as $issueid) {
						$row['IssueIds'] .= $comma . $issueid;
						$comma = ", ";
					}
				}
				if ($reqEditionIds) {
					$row['EditionIds'] = '';
					$comma = "";
					foreach ($editionids as $editionid) {
						$row['EditionIds'] .= $comma . $editionid;
						$comma = ", ";
					}
				}
				if ($reqPubChannelIds) {
					$row['PubChannelIds'] = '';
					$comma = "";
					foreach ($channelids as $channelid) {
						$row['PubChannelIds'] .= $comma . $channelid;
						$comma = ", ";
					}
				}
				if ($reqIssues) {
				 	$issuenames = substr($issuenames,0,-1); // Sorting is only done on names of issues
					$sorted = explode(',', $issuenames);	// BZ#20985
					sort($sorted);
					$row['Issues'] = implode(', ', $sorted);
				}
				if ($reqEditions) {
					$row['Editions'] = substr($editionnames,0,-2); //remove last comma and space from $editionnames
				}
				if ($reqPubChannels) {
					$row['PubChannels'] = substr($channelnames,0,-2); //remove last comma and space from $channelnames
				}

				if( $addTargets ) {
					$row['Targets'] = $objecttargets;
				}
			} else {
				if ( $addTargets && !isset( $row['Targets'] ) ) { // Prevent overwriting, see header.
					$row['Targets'] = array();
				}
			}
		}
	}
	
	/**
	 * Adds objects to the list of objects on which he has 'list' right. If objects
	 * are depends on the fact if they are already added before and if the user
	 * has the proper rights.
	 * 
	 * @param string $user short user name
	 * @param array $childsIds contains the object ids
	 * @param array $areas  Can be 'Workflow' or Trash'(where all the deleted objects reside)
	 */
	static public function addObjectsToAuthorizedView ($user, $childsIds, $areas=array('Workflow'))
	{
		require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';
		
		$rows  = DBQuery::getObjectsFromAuthorizationView($childsIds);
		
		$foundIds = array();
		foreach ($rows as $row) {
			$foundIds[$row['id']] =  array('id' => $row['id']);
		}
		
		// Only objects not yet in the authorized view have to b taken into account.
		$notfoundIds = array_diff_key($childsIds, $foundIds);

		if (!empty($notfoundIds)) {
			//$authorizedIds = array();
			$params = array();
			require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
			foreach ($notfoundIds as $notfoundId) {
				$params[] = new QueryParam('ID', '=', $notfoundId['id'], false);
			}
			$objectsWhere = BizQuery::getObjectsWhere($params);
			//Add the not found objects to the authorized view if user has the proper rights.
			$deletedObjects = in_array('Trash',$areas) ? true : false;
			DBQuery::createAuthorizedObjectsView($user, $deletedObjects, null, false, true, $objectsWhere);	
		}				
	}	
	
	/**
	 * Not all properties are suitable to sort (order by) on. This is the case for
	 * fields who are not stored in de objects table and of which the value is calculated.
	 * Example is the 'PlacedOn' field which is calculated (derived) from the placements table.
	 * Furthermore there is a technical restriction on 'blob' fields. These fields
	 * are stored as 'text' fields in MSSQL. Sorting on this kind of fields results in a
	 * sql error. So custom propterties of type blob/text are excluded.  
	 * @param array() $queryorder Order by fields as requested
	 * @return array() $supported Supported order by fields
	 */
	static private function filterOrderByFields($queryorder)
	{
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
        $unsupported = array('PubChannels', 'EditionIds', 'IssueIds', 'PubChannelIds', 'PlacedOn', 'ElementName', 'Score', 'UnreadMessageCount', 'Dossier');
        // Score is a Solr property but if Solr is down DB query is the fall back. In that case ignore the sorting on Score.
        $supported = array();
        if (is_array($queryorder)) {
            foreach ($queryorder as $qo) {
                if (!in_array($qo->Property, $unsupported)) {
                	if (BizProperty::isCustomPropertyName($qo->Property)) {
                		$customType= BizProperty::getCustomPropertyType($qo->Property);
                		switch ($customType) {
                			case 'multiline':
                			case 'multistring':
                			case 'multilist':
                				break;
                				// Unsupported custom property types
                				// See BZ#18151	
                			default:	
                    			$supported[] = $qo;
                    			break;
                		}
                	}
                	else { // Supported standard property
               			$supported[] = $qo;
                	}
                }
            }
        }
        
        return $supported;		
	}

	/**
	 * If a column is used to sort on it can be needed to add extra columns to sort on.
	 * E.g. if a sort is done on 'State' the sorting should be on Publication/Type/State.
	 * @param QueryOrder[] $queryorder
	 * @return QueryOrder[]
	 */
	static private function addOrderByFields($queryorder)
	{
		$stateUsed = false;
		$publicationUsed = false;
		$typeUsed = false;
		if ( $queryorder ) foreach ( $queryorder as $qo ) {
			if ( $qo->Property == 'State' ) {
				$stateUsed = true;
			}
			if ( $qo->Property == 'Publication' ) {
				$publicationUsed = true;
			}
			if ( $qo->Property == 'Type' ) {
				$typeUsed = true;
			}
		}

		if ( $stateUsed ) {
			if ( !$publicationUsed ) {
				$qo = new QueryOrder();
				$qo->Property = 'Publication';
				$qo->Direction = true;
				$queryorder[] = $qo;
			}
			if ( !$typeUsed ) {
				$qo = new QueryOrder();
				$qo->Property = 'Type';
				$qo->Direction = true;
				$queryorder[] = $qo;
			}
		}
		return $queryorder;
	}

	/**
	 * Resolves and returns the QueryOrder.
	 *
	 * @param QueryOrder[]|null $queryOrder The QueryOrder as passed in the Request.
	 * @param string[] $areas The Areas for which to apply the QueryOrder.
	 * @return array An array containing the QueryOrder.
	 */
	static public function resolveQueryOrder($queryOrder, $areas)
	{
		$queryOrder = self::resolveQueryOrderDirection( $queryOrder );

		if ($queryOrder == null) {
			$queryOrder = array();
			$prop = in_array( 'Workflow', $areas ) ? 'Modified' : 'Deleted';
			$defaultqo = new QueryOrder( $prop, false);
			$queryOrder[] = $defaultqo;
		} else if (count($queryOrder) == 0) {
			$prop = in_array( 'Workflow', $areas ) ? 'Modified' : 'Deleted';
			$defaultqo = new QueryOrder( $prop, false);
			$queryOrder[] = $defaultqo;
		}
		return $queryOrder;
	}

	/**
	 * Translates special query parameters to appropriate, internal, values.
	 * Instead of issue ids the client can ask for current, next or previous issues. For each publication these values
	 * are translated to internal values, the issue ids. In case the special value cannot be translated (because e.g.
	 * a publication has no previous issue) the parameter is filtered out.
	 * 
	 * @param QueryParam[] $params The query parameters.
	 * @return QueryParam[] query parameters with resolved special parameters.
	 * @throws BizException
	 */
	static public function resolveSpecialParams( $params )
	{
		$publicationIds = array();
		$specialParams = array();
		$existingIssueIds = array();
		foreach( $params as $paramKey => $param ) {
			if( strtolower($param->Property) == 'publicationid' ) {
				if( !empty($param->Value) && is_numeric($param->Value) ) {
					$publicationIds[] = $param->Value; // Get all publication id, in case multiple brand selected
				}
			}
			if ( $param->Special ) {
				if( strtolower($param->Property ) !== 'issueid' ) {
					throw new BizException('ERR_ARGUMENT', 'Client', 'Special param given but property not supported');
				}
				$specialParams[] = $param;
				unset( $params[$paramKey] );
			}
			if( strtolower( $param->Property ) == 'issueid' && !$param->Special) { // Param is sent in as "real" IssueId
				$existingIssueIds[] = $param->Value;
			}
		}
		if ( !$specialParams ) {
			return $params;
		}
		if ( $specialParams && !$publicationIds ) {
			throw new BizException('ERR_ARGUMENT', 'Client', 'Special param given but no publicationid');
		}

		$newParams = array();
		foreach( $specialParams as $specialParam ) {
			foreach( $publicationIds as $publicationId ) { // Process all publicationids, to get respective special issue id 
				switch( strtolower( $specialParam->Value )) {
					case 'current':
						{
						$modifiedParamValue = DBQuery::queryCurrentIssueId($publicationId);
						break;
						}
					case 'prev':
					case 'previous':
						{
						$modifiedParamValue = DBQuery::queryPrevIssueId($publicationId);
						break;
						}
					case 'next':
						{
						$modifiedParamValue = DBQuery::queryNextIssueId($publicationId);
						break;
						}
					default:
						{
						throw new BizException('ERR_ARGUMENT', 'Client', 'Special param given but with unknown value');
						}
				}
				// $modifiedParamValue is null if the special param could not be resolved. By setting it to -1 we make
				// sure that nothing is returned if none of the special params could be resolved.
				if( is_null( $modifiedParamValue )) {
					$modifiedParamValue = -1;
				}
				if( !in_array( $modifiedParamValue, $existingIssueIds )) {
					$newParam = clone( $specialParam );
					$newParam->Value = $modifiedParamValue;
					$newParam->Special = false;
					$newParams[] = $newParam; // Add new param object when multiple brand selected
				}
			}
		}
		$params = array_merge( $params, $newParams ); // Merge with new query params when multiple brand selected.

		return $params;
	}

	static public function resolvePublicationNameParams($params)
	{
		$pubrows = null;
		foreach ($params as &$param) {
			if (strtolower($param->Property == 'publication')) {
				if( is_null($pubrows)) {
					require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
					$pubrows = DBPublication::listPublications( array( 'id', 'publication' ) );
				}
				foreach ($pubrows as $pubrow) {
					if (strtolower($pubrow['publication']) == strtolower($param->Value) && $param->Operation == '=') {
						$param->Property = 'publicationid';
						$param->Value = $pubrow['id'];
						break; // found the corresponding publication id
					}
				}
			}
		}
		return $params;
	}

	/**
	 * Resolve QueryParam that contains Issue as the Property.
	 *
	 * When the Property is sent in as Issue ( the Issue name(s) ),
	 * function resolves the name(s) to its corresponding Issue id(s).
	 *
	 * @param QueryParam[] $params
	 * @return QueryParam[] With the resolved Issue names to Issue ids when necessary.
	 */
	public static function resolveIssueNameParams( $params )
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$issueNames = array();
		if( $params ) foreach( $params as $param ) {
			if( strtolower( $param->Property == 'Issue' )) {
				$issueNames[] = $param->Value;
			}
		}
		$issueIds = $issueNames ? DBIssue::resolveIssueIdsByNameAndParams( $issueNames, $params ) : array();
		if( $issueIds ) {
			// Re-construct the QueryParams.
			$newParams = array();
			$existingIssueIds = array();

			// Collect QueryParam that is/are not Issue
			foreach( $params as $param ) {
				if( strtolower( $param->Property != 'Issue' )) {
					$newParams[] = $param;
				}
				if( strtolower( $param->Property == 'IssueId' )) {
					$existingIssueIds[] = $param->Value;
				}
			}

			// Filling in the Issue id(s)
			require_once BASEDIR .'/server/interfaces/services/wfl/DataClasses.php';
			foreach ( $issueIds as $issueId ) {
				if( !in_array( $issueId, $existingIssueIds )) { // Add in the IssueId only when it does not exist yet to avoid duplicates.
					$queryParam = new QueryParam();
					$queryParam->Property = 'IssueId';
					$queryParam->Operation = '=';
					$queryParam->Value = $issueId;
					$newParams[] = $queryParam;
				}
			}
			$params = $newParams;
		}

		return $params;
	}

	static protected function getPropNames($mode, $minimalProps, $requestProps, $areas)
	{
		// If $requestProps set, this is the complete list of properties, so if no empty return this
		if( !empty( $requestProps ) ) {
			return $requestProps;
		}

		//All these props require each other, let us make sure they are allways both selected when the other is selected.
		$reqprops = array();
		$reqprops['Publication'] = 'PublicationId';
		$reqprops['PublicationId'] = 'Publication';
		$reqprops['Category'] 	 = 'CategoryId';
		$reqprops['CategoryId']  = 'Category';
		$reqprops['Section'] 	 = 'SectionId';
		$reqprops['SectionId'] 	 = 'Section';
		$reqprops['Issue'] 	 	 = 'IssueId';
		$reqprops['IssueId'] 	 = 'Issue';
		$reqprops['State']	 	 = 'StateId';
		$reqprops['StateId'] 	 = 'State';

		$propnames = self::getQueryProperties($mode, $areas);

		$reqpropnames = array();
		foreach ($propnames as $propname) {
			$reqpropnames[] = $propname;
			if (isset($reqprops[$propname])) {
				$reqpropnames[] = $reqprops[$propname];
			}
		}

		// Merge required props into configured list:
		if( !empty($minimalProps) ) {
			$reqpropnames = array_merge( $reqpropnames, $minimalProps );
		}

		return array_unique($reqpropnames);
	}

	/**
	 * After the rows are read from the database the objects have to be processed.
	 * Meaning that they will be extended with e.g. 'Placed on', 'Target' information. Some objects have many/many
	 * relations and targets (like e.g. icons). These relations and targets are not resolved due to performance issues.
	 * Furthermore some attributes are mapped to a meaningful value like the short
	 * route to name and the personal state.
	 * Finally the result is enriched with all properties that are requested but could not be resolved.
	 *
	 * @param array $rows the database objects
	 * @param array $requestedpropnames properties requested
	 * @param string $limitPlacedView identifier for subselect on temporary table with limited placed objects.
	 * @param string $allView identifier of the temporary table containing all object ids.
	 * @param array $componentrows element information
	 * @param bool $returnComponentRows true if function should return $componentrows else false (BZ#17057)
	 * @param array $areas 'Workflow' or 'Trash'
	 * @param string $orientation
	 * @return array processed object rows
	 */
	static public function processResultRows( $rows, $requestedpropnames, $limitPlacedView, $allView, &$componentrows,
	                                          $returnComponentRows = true, $areas = array('Workflow'), $orientation='Object')
	{
		$resultRows = $rows;
		self::resolvePersonalStatusesAndFixColors($resultRows);

		$deleted = in_array('Trash',$areas) ? true : false;
		$resultRows = self::addTargetsToLimitPlacedObjects(
						$resultRows, $requestedpropnames, $limitPlacedView, $deleted, $orientation );
		$resultRows = self::addObjectTargetToMultiplePlacedObjects(
						$resultRows, $requestedpropnames, $allView, $limitPlacedView );

		self::resolveRouteTo($resultRows);

		$elementNameRequested = in_array('ElementName', $requestedpropnames); // bool
		if ($elementNameRequested || $returnComponentRows){
			$componentrows = DBQuery::getElementsByView($limitPlacedView);
		}
		if ($elementNameRequested) {
			self::resolveElementName($resultRows, $componentrows);
		}

		if (in_array('HasChildren', $requestedpropnames)){
			self::resolveHasChildren($resultRows, $deleted);
		}

		if( in_array( 'Dimensions', $requestedpropnames ) ) {
			self::determineDimensions( $resultRows, $areas );
		}

		if ( in_array( 'UnreadMessageCount', $requestedpropnames )) {
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
			$unreadMessageRows = DBMessage::getUnReadMessageCountForView( $limitPlacedView );
			self::resolveUnreadMessageCount( $resultRows, $unreadMessageRows );
		}

		$resultRows = self::addMissingRequestedProperties($requestedpropnames, $resultRows);

		return $resultRows;
	}

	/**
	 * Populate the Dimensions column in the search results.
	 *
	 * The Dimensions property is resolved through Width, Height and Orientation properties.
	 *
	 * IMPORTANT: Please keep this function in-sync with BizObjects::determineDimensions() !
	 *
	 * @since 10.1.0
	 * @param array $resultRows the database objects that will be enriched.
	 * @param array $areas 'Workflow' or 'Trash'
	 */
	private static function determineDimensions( &$resultRows, $areas )
	{
		$objectIds = array_keys( $resultRows );
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objRows = DBObject::getColumnsValuesForObjectIds( $objectIds, $areas, array( 'id', 'width', 'depth', 'orientation' ) );
		foreach( $resultRows as $objectId => &$resultRow ) {
			$objRow = isset( $objRows[$objectId] ) ? $objRows[$objectId] : null;
			if( isset( $objRow['width'] ) && $objRow['width'] > 0 &&
				isset( $objRow['depth'] ) && $objRow['depth'] > 0 ) {
				if( isset( $objRow['orientation'] ) && $objRow['orientation'] >= 5 ) {
					$lhs = $objRow['depth'];
					$rhs = $objRow['width'];
				} else {
					$lhs = $objRow['width'];
					$rhs = $objRow['depth'];
				}
				$resultRow['Dimensions'] = "$lhs x $rhs";
			} else {
				$resultRow['Dimensions'] = '';
			}
		}
	}

	/**
	 * Enriches objects with target and placement information. The $limitView contains objects that are not placed many
	 * times. A lot of placements means that an object has a lot of relations. To resolve all these placements and
	 * relations leads to a severe deterioration of the performance.
	 *
	 * @param array     $resultRows the database objects that will be enriched.
	 * @param array     $requestedPropNames properties requested
	 * @param string    $view identifier for select on temporary table
	 * @param bool      $deleted Look for 'Trash' or 'Workflow' objects
	 * @param string    $orientation
	 * @return array    Enriched database objects.
	 */
	static private function addTargetsToLimitPlacedObjects(
								$resultRows, $requestedPropNames, $view, $deleted, $orientation )
	{
		if (in_array( 'PlacedOn', $requestedPropNames) || in_array('PlacedOnPage', $requestedPropNames )) {
			$placedonrows = DBQuery::getPlacedOnRowsByView( $view, $deleted );
			self::resolvePlacedOn( $resultRows, $placedonrows );
		}

		//Queries all targets for all parents
		$toptargets = DBTarget::getArrayOfTargetsByObjectViewId( $view, $deleted );
		//Resolve targets by adding entries into $toprows
		$keepOriginalValuesIfNotSet = ( $orientation != 'Object' );

		self::resolveTargets( $resultRows, $toptargets, $requestedPropNames, $keepOriginalValuesIfNotSet );

		return $resultRows;
	}

	/**
	 * Enriches objects with object-target information. Two view identifiers are passed. The $allView contains all in-
	 * volved objects (objects which are placed many times and objects which have a normal amount of placements). A lot
	 * of placements means also that an object has a lot of relations. To resolve all these placements and relations
	 * leads to a severe deterioration of the performance. For the many placed objects still the object targets are
	 * needed. So for all objects in $allView which are not in the $limitView the object-targets are resolved.
	 *
     * @param array $objectRows the database objects
     * @param array $requestedPropNames properties requested
     * @param string $allView identifier for select on temporary table
     * @param string $limitView identifier for select on temporary table
	 * @return array with objects enriched with object-target information.
	 */
	static private function addObjectTargetToMultiplePlacedObjects(
								$objectRows, $requestedPropNames, $allView, $limitView  )
	{
		$resultRows = $objectRows;

		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$objIds = DBBase::diffOfViews( $allView, $limitView );

		if ( $objIds ) {
			require_once BASEDIR . '/server/dbclasses/DBTargetEdition.class.php';
			$objTargetRows = DBTargetEdition::listTargetEditionRowsByObjectId( $objIds );
			$targets = array();
			// Prepare an array with targets which can be as input for the resolveTargets() method.
    		if ($objTargetRows) foreach ($objTargetRows as $objTargetRow){
				$target = new Target(
					new PubChannel($objTargetRow['channelid'], $objTargetRow['channelname']),
					new Issue($objTargetRow['issueid'], $objTargetRow['issuename']), array());
				// Object targets only
				$target->IsRelational = false;
				if (! isset($targets[$objTargetRow['objectid']])){
					$targets[$objTargetRow['objectid']] = array();
				}
				$targets[$objTargetRow['objectid']][] = $target;
    		}
			self::resolveTargets($resultRows, $targets, $requestedPropNames);
		}

		if (in_array('PlacedOn', $requestedPropNames) || in_array('PlacedOnPage', $requestedPropNames)) {
			$message = BizResources::localize( "OBJ_MULTIPLE_OBJECTS", false );
			foreach( $resultRows as &$resultRow ) {
				if ( in_array($resultRow['ID'], $objIds ))	{
					$resultRow['PlacedOn'] = $message;
					$resultRow['PlacedOnPage'] = $message;
				}
			}
		}

		return $resultRows;
	}


	static public function resolvePlacedOn(&$objectrows, $placedons) {
		foreach ($objectrows as &$objectrow) {
			$objectid = $objectrow['ID'];
			if (isset($placedons[$objectid])) {
				$objectrow['PlacedOn'] = $placedons[$objectid]['PlacedOn'];
				$objectrow['PlacedOnPage'] = $placedons[$objectid]['PlacedOnPage'];
			}
			else {
				$objectrow['PlacedOn'] = '';
				$objectrow['PlacedOnPage'] = '';
			}
		}
	}

	static public function resolveRouteTo(&$rows)
	{
		foreach ($rows as &$row) {
			if (!empty($row['RouteToUser'])) {
				$row['RouteTo'] = $row['RouteToUser'];
			}
			else if (!empty($row['RouteToGroup'])) {
				$row['RouteTo'] = $row['RouteToGroup'];
			}
			unset($row['RouteToUser']);
			unset($row['RouteToGroup']);
		}
	}

	static public function resolveElementName(&$rows, $componentrows)
	{
		foreach ($rows as &$row) {
			$row['ElementName'] = '';
		}
		foreach ($componentrows as $componentrow) {
			$objectid = $componentrow['Parent'];
			$rows[$objectid]['ElementName'] .= $componentrow['Name'] . ' ';
		}
	}

	/**
	 * Adds the number of unread messages to each object. Default is set to
	 * zero.
	 * @param array $rows result rows to which the unread messages are added.
	 * @param array $unreadMessageRows unread messages by object.
	 */
	static public function resolveUnreadMessageCount( &$rows, $unreadMessageRows)
	{
		foreach ($rows as &$row) {
			$row['UnreadMessageCount'] = 0;
		}

		foreach ( $unreadMessageRows as $objectId => $unreadMessageRow ) {
			$rows[$objectId]['UnreadMessageCount'] = $unreadMessageRow['total'];
		}
	}

	/**
	 * Resolve special property "HasChildren" in query result.
	 *
	 * @param array $rows key value array with object id as key
	 * @param bool $deletedObjects
	 */
	static public function resolveHasChildren(&$rows, $deletedObjects)
	{
		if (count($rows) > 0){
			// get all parent ids
			$parentIds = array_keys($rows);
			$parentsWithChildren = DBQuery::queryHasChildren($parentIds, $deletedObjects);
			// set HasChildren to 'on'
			foreach ($parentsWithChildren as $parentWithChildren){
				$rows[$parentWithChildren['Parent']]['HasChildren'] = 'on';
			}
			// set other rows who don't have property HasChildren yet
			foreach ($rows as &$row){
				if (! isset($row['HasChildren'])){
					$row['HasChildren'] = '';
				}
			}
		}
	}

	static public function reorderColumns($rows, $columnorder)
	{
		$resultrows = array();
		foreach ($rows as $row) {
			$newrow = array();
			foreach ($columnorder as $co) {
				if (array_key_exists($co, $row)) { // DO NOT USE isset, it will skip empty elements!
					$newrow[$co] = $row[$co];
				} else { // Oracle specific (driver returns custom fields in rows with lower case key)
					require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
					if (BizProperty::isCustomPropertyName($co)) { //Custom property check
						if (array_key_exists(strtolower($co), $row)) { // DO NOT USE isset, it will skip empty elements!
							$newrow[$co] = $row[strtolower($co)];
						}
					}
				}
			}
			if (isset($row['smart_parents'])) {
				$newrow['smart_parents'] = $row['smart_parents'];
			}
			$resultrows[] = $newrow;
		}
		return $resultrows;
	}

	/**
	 * Adds missing requested properties to the rows. In case a property is requested ( as it is added to the dialog set up)
	 * but it cannot be resolved, it is removed from the result during the query and resolve process. These properties
	 * are added to give consistent UI behavior. As these properties could not be resolved their value is set to null.
	 * @param array $requestedPropNames Properties requested.
	 * @param array $rows rows containing the queried and resolved properties.
	 * @return array rows where the missing properties are added (if needed).
	 */
	static public function addMissingRequestedProperties( array $requestedPropNames, array $rows)
	{
		if ( !$rows ) {
			return $rows;
		}

		reset( $rows );
		$row = current($rows);
		$missingProperties = array();

		foreach( $requestedPropNames as $propertyName ) {
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			// In case of custom properties the row can either contain the property in lower cases (Oracle) or in upper
			// cases (Mysql/Mssql). A requested custom property is always in upper case.
			if( BizProperty::isCustomPropertyName( $propertyName ) ) {
				if( !array_key_exists( $propertyName, $row) /*Mysql/Mssql*/ &&  !array_key_exists( strtolower($propertyName), $row) /*Oracle*/ ) {
					$missingProperties[$propertyName] = null;
				}
			} else {
				if( !array_key_exists( $propertyName, $row) ) {
					$missingProperties[$propertyName] = null;
				}
			}
		}

		$resultRows = $rows;
		if( $missingProperties ) {
			foreach( $rows as $Id => $rowToUpdate ) {
				// Missing properties must be 1st array to merge to existing row, to avoid overwrite existing row property value
				$resultRows[$Id] = array_merge( $rowToUpdate,  $missingProperties );
			}
		}

		return $resultRows;
	}

	/**
	 * Updates the Direction property of the QueryOrder Object.
	 *
	 * The QueryOrder's Direction property is passed as a String, we require it to be
	 *
	 * @param array $queryOrder The QueryOrder Params that need to be resolved.
	 * @return array The resolved QueryOrder Params.
	 */
	static public function resolveQueryOrderDirection( $queryOrder )
	{
		//BZ#8455 $qo->Direction is passed as string, not a boolean. Replacing... Should really be solved in soap-layer.
		if ($queryOrder) foreach ($queryOrder as &$qo) {
			if (is_string($qo->Direction)) {
				$qo->Direction = (strtolower(trim($qo->Direction)) == 'false') ? false : true;
			}
		}
		return $queryOrder;
	}
}
