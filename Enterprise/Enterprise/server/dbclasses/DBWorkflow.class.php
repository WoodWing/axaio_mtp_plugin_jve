<?php

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';

class DBWorkflow extends DBBase 
{
	const TABLENAME = 'states';
	static $cacheState;	// cache for States
    /**
     *  updates an Statedefinition (record in the states-table) with the values supplied in $values
     *  @param $statedefid Id of the State-definition to update
     *  @param $values array of values to update, indexed by fieldname. $values['issue'] = issue1, etc...
     *         The array does NOT need to contain all values, only values that are to be updated.
     *  @return true if succeeded, false if an error occured.
    **/

    public static function updateStateDef($statedefid, $values)
    {
        return self::updateRow(self::TABLENAME, $values, "`id` = '$statedefid' ");
    }

    /**
     *  Lists all sectionstatedefinitions defined for the section-definition
     *  What is exactly returned depends on the value of $fieldnames:
     *  - Either null or false -> returns an array of rows indexed by the value in $keycol, each containing the name ($namecol) of the row.
     *  - Either '*' or true -> returns an array of rows indexed by the value in $keycol, each containing an array with all values.
     *  - An array with fieldnames -> returns an array of rows indexed by the value in $keycol, each containing an array with the values in $fieldnames.
     *  @param  $sectiondefid Id of the section-definition, if $sectiondefid = 0 null is returned;
     *  @param  $fieldnames see functiondescription
     *  @return  null in case of error, otherwise see functiondescription
    **/

    public static function listSectionStateDefs($sectiondefid, $fieldnames = '*')
    {
        return self::listRows('sectionstate', 'id', 'state', "`section` = '$sectiondefid' ", $fieldnames);
    }
        
    public static function listPublWorkflowDefs( $publid )
    {
        $states = self::listRows(self::TABLENAME, 'id', 'type', "`publication` = '$publid' AND `issue` = '0'", null);
        $temp = array();
        $workflowdefs = array();
        foreach ($states as $state)
        {
            if (array_key_exists($state['type'],$temp))
            {
                continue;   
            }
            $temp[$state['type']] = null;
            $workflowdefs[] = array('id' => $state['id'], 'name' => $state['type']);
        }
        return $workflowdefs;
    }

    public static function listIssueWorkflowDefs($issueid, $nopubldefs = false)
    {
        $issue = DBIssue::getIssue($issueid);
        if ($issue['overrulepub'] === true)
        {
            $states = self::listRows(self::TABLENAME, 'id', 'type', "`issue` = '$issueid'", null);
            $temp = array();
            $workflowdefs = array();
            foreach ($states as $state)
            {
                if (array_key_exists($state['type'],$temp))
                {
                    continue;   
                }
                $temp[$state['type']] = null;
                $workflowdefs[] = array('id' => $state['id'], 'name' => $state['type']);
            }
            return $workflowdefs;
        }
        else
        {
            return $nopubldefs ? null : self::listPublWorkflowDefs($issue['publication']);   
        }
    }

    public static function listPublStateDefs($publid, $sortorder = 'ASC', $fieldnames = '*')
    {
        return self::listRows(self::TABLENAME,'id','state'," `publication` = $publid AND `issue` = 0 ORDER BY `code` $sortorder ", $fieldnames);
    }

    public static function listIssueStateDefs($issueid, $sortorder = 'ASC', $fieldnames = '*', $nopubldefs = false)
    {
        $issue = DBIssue::getIssue($issueid);
        if ($issue['overrulepub'] === true)
        {
            return self::listRows(self::TABLENAME,'id','state'," `issue` = '$issueid' ORDER BY `code` $sortorder ", $fieldnames);                   
        }
        else
        {
            return $nopubldefs ? null : self::listPublStateDefs($issue['publication'], $sortorder, $fieldnames); 
        }
    }

    public static function listIssueSectionStates($issueid, $sectiondefid, $fieldnames = '*')
    {
        $sectionwhere = ($sectiondefid != 0) ? " AND (`section` = '$sectiondefid') " : ' ';
        return self::listRows('issuesectionstate', 'id', 'state', "(`issue` = '$issueid') $sectionwhere ", $fieldnames);
    }
    
    public static function updateIssueSectionState($issuesectionstateid, $values)
    {
        return self::updateRow('issuesectionstate', $values, "`id` = '$issuesectionstateid' ");
    }
    
    public static function insertSectionStateDef($sectiondefid, $statedefid, $values, $updateifexists = true)
    {
        $result = null;
        $sectionstateexists = self::getRow('sectionstate', " `section` = '$sectiondefid' AND `state` = '$statedefid' ", null);
        if ($sectionstateexists)
        {
            if ($updateifexists)
            {
                $result = self::updateSectionStateDef($sectionstateexists['id'],$values);
            }
            else 
            {
                self::setError("ERR_RECORDEXISTS");
            }
        }
        else 
        {
            $values['section'] = $sectiondefid;
            $values['state'] = $statedefid;
            $result = self::insertRow('sectionstate', $values);            	
        }
        return $result;
    }

    public static function updateSectionStateDef($sectionstateid, $values)
    {
        return self::updateRow('sectionstate', $values, "`id` = '$sectionstateid' ");
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
    public static function insertIssueSectionState( $issueId, $categoryId, $statusId, $values,
                                                    $updateIfExists = true)
    {
        $result = null;
        $stateexists = self::getRow('issuesectionstate', " `issue` = '$issueId' AND `section` = '$categoryId' AND `state` = '$statusId' ", null);
        if ($stateexists) {
            if ( $updateIfExists ) {
                $result = self::updateIssueSectionState($stateexists['id'], $values);
            } else {
                self::setError("ERR_RECORDEXISTS");
            }
        } else {
            $values['issue'] = $issueId;
            $values['section'] = $categoryId;
            $values['state'] = $statusId;
            $result = self::insertRow('issuesectionstate', $values);
        }
        return $result;
    }
    
	static public function nextState( $id )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename("states");
		$sql = null;
		if( $id != null ) {
			$sql = "SELECT `nextstate` from $db where `id` = $id";
		} else {
			$sql = "SELECT `nextstate` from $db where `id` = ''";
		}
		$sth = $dbDriver->query($sql);
		if (!$sth) return false;

		$row = $dbDriver->fetch($sth);
		if (!$row) return false;

		return trim($row['nextstate']);
	}

	static public function checkState( $id )
	{
		//personal state
		if($id == -1){
			return true;
		}
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename("states");
		$sql = null;
		if( $id != null ) {
			$sql = "SELECT `id` from $db where `id` = $id";
		} else {
			$sql = "SELECT `id` from $db where `id` = ''";
		}

		$sth = $dbDriver->query($sql);
		if (!$sth) return false;

		$row = $dbDriver->fetch($sth);
		if (!$row) return false;

		return true;
	}

	/**
	 * Flushes the States cache.
	 *
	 * This is needed sometimes when States are added and removed rapidly like in the case with the buildtest, in which
	 * case we need to flush the states to ensure we get the latest from the database.
	 *
	 * @static
	 * @return void
	 */
	static public function flushStatesCache() {
		if ( isset(self::$cacheState) ) {
			self::$cacheState = null;
		}
	}

	/**
	 * Lists the States from the cache, or retrieves and caches them if the cache does not exist.
	 *
	 * Note: To influence the cache (flush it) use DBWorkflow::flushStateCache()
	 *
	 * @static
	 * @param $publication
	 * @param $issue
	 * @param $section
	 * @param $type
	 * @param null $name
	 * @return array|bool
	 */
	static public function listStatesCached( $publication, $issue, $section, $type, $name = null )
	{
		// if not cached: cache all states
		if (!isset(self::$cacheState)) {
			$dbDriver = DBDriverFactory::gen();
			self::$cacheState = array();
			$sth = self::listStates( null, null, null, null );
			if (!$sth) return false;		// DB error
			while (($row = $dbDriver->fetch($sth))) {
				self::$cacheState[] = $row;
			}
		}
		// get from cache.
		$ret = array();
		if (self::$cacheState) foreach (self::$cacheState as $row) {
			$add = true;
			if ($publication && $row['publication'] != $publication) $add = false;
			if ($type && $row['type'] != $type){
				$add = false;
			}
			if ($name && $row['name'] != $name){
				$add = false;
			}
			if ($issue) {
				if ($row['issue'] != 0 && $row['issue'] != $issue){
					$add = false;
				}
			} else {
				if ($row['issue'] != 0) $add = false;
			}
			if ($section) {
				if ($row['section'] != $section && $row['section'] != 0){
					$add = false;
				}
			} else {
				if ($row['section'] != 0) $add = false;
			}
			if ($add) $ret[] = $row;
		}
		return $ret;
	}

	/**
	  * Query for a statuses of a given object type.
	  * It allows to filter for pubs, issues and sections too, as well as the status name.
	  *
	  * @param int $pubId         Publication id to filter. Null for no filter.
	  * @param int $issueId       Issue id to filter. Zero for pub statuses only. Null for no filter.
	  * @param int $sectionId     Section id to filter. Zero for object type-less configs. Null for no filter.
	  * @param string $objType    Object type. Empty for no filter.
	  * @param string $statusName Name of status. Empty for no filter.
	  * @param boolean $retRows   Return all rows at once (perfered/new style). Else, default old/obsoleted way returning query result handle (resource).
	  * @return Mixed Either an array of rows ($retRows=true) or a resource ($retRows=false).
	  */
	static public function listStates( $pubId, $issueId, $sectionId, $objType, $statusName=null, $retRows=false )
	{
		$dbDriver = DBDriverFactory::gen();
		$where = '';
		if( $pubId ) {
			$where .= "`publication` = $pubId ";
		}
		if( $objType ) {
			$objType = $dbDriver->toDBString( $objType );
			if( $where ) $where .= 'AND ';
			$where .= "`type` = '$objType' ";
		}
		if( $statusName ) {
			$statusName = $dbDriver->toDBString( $statusName );
			if( $where ) $where .= 'AND ';
			$where .= "`state` = '$statusName' ";
		}
		if( !is_null($issueId) ) {
			if( $issueId ) {
				if( $where ) $where .= 'AND ';
				$where .= "(`issue` = $issueId OR `issue` = 0) ";
			} else {
				if( $where ) $where .= 'AND ';
				$where .= "`issue` = 0 ";
			}
		}
		if( !is_null($sectionId) ) {
			if( $sectionId ) {
				if( $where ) $where .= 'AND ';
				$where .= "(`section` = $sectionId OR `section` = 0) ";
			} else {
				if( $where ) $where .= 'AND ';
				$where .= "`section` = 0 ";
			}
		}

		if( $retRows ) { // the new, preferred way
			return self::listRows( self::TABLENAME, 'id', 'state', "$where ORDER BY `code`" );
		} else { // the old, obsoleted way
			if( $where ) {
				$where = 'WHERE '.$where;
			}
			$db = $dbDriver->tablename( self::TABLENAME );
			$sth = $dbDriver->query( "SELECT * FROM $db $where ORDER BY `code`" );
			return $sth;
		}
	}

	/**
	 * Returns the status name given the status id.
	 *
	 * @param string $id
	 * @return string Returns the status name, empty string when the status for the given id is not found.
	 */
	static public function getStatusName( $id )
   	{
		$dbDriver = DBDriverFactory::gen();
		$dbo  = $dbDriver->tablename(self::TABLENAME);
		$sql = 'SELECT `id`, `state` FROM '.$dbo.' WHERE `id` = '.$id;
		$sth = $dbDriver->query($sql);
		$row = $dbDriver->fetch($sth);
		if( empty($row) === false ) {
			return $row['state'];
		} else {
			return '';
		}
	}

}
?>