<?php

/**
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';

class DBSection extends DBBase
{
	const TABLENAME = 'publsections';

	static public function getSectionName( $id )
	{
		$dbDriver = DBDriverFactory::gen();
		$dbo  = $dbDriver->tablename('publsections');
		$sql = 'SELECT `id`, `section` FROM '.$dbo.' WHERE `id` = '.$id;
		$sth = $dbDriver->query($sql);
		$row = $dbDriver->fetch($sth);
		if( empty($row) === false ) {
			return $row['section'];
		} else {
			return '';
		}
	}

	/**
     *  updates an Sectiondefinition (record in the publsections-table) with the values supplied in $values
     *  @param $sectiondefid Id of the Section-definition to update
     *  @param $values array of values to update, indexed by fieldname. $values['issue'] = issue1, etc...
     *         The array does NOT need to contain all values, only values that are to be updated.
     *  @return true if succeeded, false if an error occured.
    **/

    public static function updateSectionDef($sectiondefid, $values)
    {
        return self::updateRow(self::TABLENAME, $values, "`id` = '$sectiondefid' ");
    }

    /**
     *  Lists all sectiondefinitions defined for the publication
     *  What is exactly returned depends on the value of $fieldnames:
     *  - Either null or false -> returns an array of rows indexed by the value in $keycol, each containing the name ($namecol) of the row.
     *  - Either '*' or true -> returns an array of rows indexed by the value in $keycol, each containing an array with all values.
     *  - An array with fieldnames -> returns an array of rows indexed by the value in $keycol, each containing an array with the values in $fieldnames.
     *
     * @param integer $publid Id of the publication, if $publid = 0 null is returned;
     * @param string|string[] $fieldnames see function description
     * @return array|null NULL in case of error, otherwise see function description
     */
    public static function listPublSectionDefs($publid, $fieldnames = '*')
    {
        if (empty($publid))
        {
            return null;   
        }
        return self::listRows(self::TABLENAME,'id','section',"`publication` = '$publid' AND `issue` = '0' ORDER BY `code` ASC ", $fieldnames);
    }
    
    /**
     *  Lists all sectiondefinitions defined for the (overruled) issue
     *  What is exactly returned depends on the value of $fieldnames:
     *  - Either null or false -> returns an array of rows indexed by the value in $keycol, each containing the name ($namecol) of the row.
     *  - Either '*' or true -> returns an array of rows indexed by the value in $keycol, each containing an array with all values.
     *  - An array with fieldnames -> returns an array of rows indexed by the value in $keycol, each containing an array with the values in $fieldnames.
     *
	 * @param int $issueId Id of the issue, if $issueid = 0, null is returned;
	 * @param mixed $fieldnames See function description
	 * @param bool $nopubldefs
	 * @return array|null
	 */
	public static function listIssueSectionDefs( $issueId, $fieldnames = '*', $nopubldefs = false)
    {
        $issue = DBIssue::getIssue( $issueId );
        if ($issue['overrulepub'] === true) {
            $sectiondefs = self::listRows(self::TABLENAME,'id','section',"`issue` = '$issueId' ORDER BY `code` ASC ", $fieldnames);
            return $sectiondefs;                   
        }
        else {
            return $nopubldefs ? null : self::listPublSectionDefs($issue['publication']);   
        }
    }

	/**
	 *  Lists all sections for the issue
	 *  What is exactly returned depends on the value of $fieldnames:
	 *  - Either null or false -> returns an array of rows indexed by the value in $keycol, each containing the name ($namecol) of the row.
	 *  - Either '*' or true -> returns an array of rows indexed by the value in $keycol, each containing an array with all values.
	 *  - An array with fieldnames -> returns an array of rows indexed by the value in $keycol, each containing an array with the values in $fieldnames.
	 *
	 * @param  int $issueid Id of the issue, if $issueid = 0 null is returned;
	 * @param  mixed $fieldnames see function description
	 * @return  null in case of error, otherwise see functiondescription
	 */
	public static function listIssueSections( $issueid, $fieldnames = '*' )
	{
		return self::listRows( 'issuesection', 'id', 'section', "`issue` = '$issueid' ", $fieldnames );
	}

	/**
	 *  Gets exactly one section from issue $issueid, defined by $sectiondefid
	 *  What is exactly returned depends on the value of $fieldnames:
	 *  - Either null or false -> returns an array with the following two keys: 'id' and 'issue'.
	 *  - Either '*' or true -> returns an array with all fieldname-value-pairs of the issue.
	 *  - An array with fieldnames -> returns an array with the fieldname-value-pairs in $fieldnames.
	 *
	 * @param  int $issueid Id of the issue
	 * @param  int $sectiondefid Id of the sectiondefinition by which section is defined.
	 * @param  mixed $fieldnames see function description.
	 * @return  null in case of error, else see function description
	 */
	public static function getIssueSection( $issueid, $sectiondefid, $fieldnames = '*' )
	{
		return self::getRow( 'issuesection', " ( `issue` = '$issueid' AND `section` = '$sectiondefid' ) ", $fieldnames );
	}

    /**
     *  updates a Section (record in the issuesections-table) with the values supplied in $values
     *  @param $issuesectionid Id of the section to update
     *  @param $values array of values to update, indexed by fieldname. $values['issue'] = issue1, etc...
     *         The array does NOT need to contain all values, only values that are to be updated.
     *  @return true if succeeded, false if an error occured.
     **/
    public static function updateIssueSection($issuesectionid, $values)
    {
        return self::updateRow('issuesection', $values, "`id` = '$issuesectionid' ");
    }

    /**
     * Inserts a Section for issue $issueId, defined by $sectionDefId
     *
     * @param int $issueId Id of the issue to create the section for
     * @param int $sectionDefId Id of the sectiondefinition to use
     * @param array $values List of values to be updated, indexed by fieldname.
     *         The array does NOT need to contain all values, only values that are to be updated.
	 * @param bool $updateIfExists
	 */
	public static function insertIssueSection($issueId, $sectionDefId, $values, $updateIfExists = false)
    {
	    $issueId = intval( $issueId );
        $sectionexists = self::getRow('issuesection', " `issue` = '$issueId' AND `section` = '$sectionDefId' ", null);
        if ($sectionexists) {
            if ($updateIfExists) {
                self::updateIssueSection($sectionexists['id'], $values);
            }
            else {
                self::setError("ERR_RECORDEXISTS");
            }
        } else {
            $values['issue'] = $issueId;
            $values['section'] = $sectionDefId;
            self::insertRow('issuesection', $values);
        }
    }

	/**
	 * Retrieves sections from smart_publsections table that are owned by given publication or issue.
	 *
	 * @param int pubId
	 * @param int $issueId
	 * @param int $sectionId
	 * @param string $sectionName
	 * @return resource|null
	 */
	static public function listSections( $pubId, $issueId = null, $sectionId = null, $sectionName = null )
	{
		// build WHERE clause
		$dbDriver = DBDriverFactory::gen();
		if( ((string)($pubId) !== (string)(int)($pubId)) || $pubId <= 0 ) { // natural and positive
			self::setError( BizResources::localize('ERR_NO_SUBJECTS_FOUND', true, array('{PUBLICATION}') ) );
			return null;
		}
		$where = "`publication` = $pubId ";
		if( ((string)($issueId) === (string)(int)($issueId)) && $issueId > 0 ) { // natural and positive
			$where .= "AND (`issue` = $issueId OR `issue` = 0) ";
		} else {
			$where .= "AND `issue` = 0 ";
		}
		if( ((string)($sectionId) === (string)(int)($sectionId)) && $sectionId > 0 ) { // natural and positive
			$where .= "AND `id` = $sectionId ";
		}
		if( $sectionName ) { 
			$sectionName = trim($sectionName);
			$sectionName = $dbDriver->toDBString( $sectionName );
			$where .= "AND `section` = '$sectionName' ";
		}
		// run DB query
		$db = $dbDriver->tablename( self::TABLENAME );
		$sql = "SELECT `id`, `issue`, `section` from $db WHERE $where ORDER BY `code`, `id` ";
		$sth = $dbDriver->query($sql);
		return $sth;
	}
    
	/**
	 * Lists all sections of the publication and issue as objects.
	 *
	 * @param int $pubId Id of the publication that sections belongs to
	 * @param int $issueId Id of the issue that sections belongs to
	 * @return AdmSection[] List of sections if succeeded.
	 */
	static public function listSectionsObj( $pubId, $issueId )
    {
        $where = "`publication` = '$pubId' and `issue` = '$issueId' ";
        $orderby = " ORDER BY `code` ASC ";
    	$sections = array();
    	$rows  = self::listRows(self::TABLENAME,'id','section', $where . $orderby, '*');
        if (!$rows) return null;
        
    	foreach ($rows as $row) {
    	    $sections[] = self::rowToObj($row);	
    	}
        return $sections;
    }
	
	/**
     *  Gets exactly one section object with id $sectionId from DB
     *  @param  $sectionId Id of the section to get the values from
     *  @return object of section if succeeded, null if no record returned
	  */
	static public function getSectionObj( $sectionId )
    {
    	$row   = self::getRow(self::TABLENAME, "`id` = '$sectionId' ", '*');
        if (!$row) return null;
        return self::rowToObj($row);
    }

	/**
	 *  Create new section object
	 *
	 * @param int $pubId publication that new section belongs to
	 * @param int $issueId Issue that new section belongs to
	 * @param array $sections array of new sections that will created
	 * @return array of new created section objects - throws BizException on failure
	 * @throws BizException
	 */
	public static function createSectionsObj( $pubId, $issueId, $sections )
	{
		$dbdriver = DBDriverFactory::gen();
		$newsections = array();

		foreach( $sections as $section ) {
			$values = self::objToRow( $pubId, $issueId, $section );

			// check duplicates
			$row = self::getRow( self::TABLENAME, "`section` = '".$dbdriver->toDBString( $values['section'] )."' and `publication` = '$values[publication]' and `issue` = '$values[issue]' " );
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null );
			}

			self::insertRow( self::TABLENAME, $values );
			$newid = $dbdriver->newid( self::TABLENAME, true );
			if( !is_null( $newid ) ) {
				$newsection = DBSection::getSectionObj( $newid );
				$newsections[] = $newsection;
			}
		}
		return $newsections;
	}

	/**
	 *  Modify Section object
	 *
	 * @param int $pubId Publication that Section belongs to
	 * @param int $issueId Issue that Section belongs to
	 * @param array $sections array of sections that need to be modified
	 * @return array of modified Section objects - throws BizException on failure
	 * @throws BizException
	 */
	public static function modifySectionsObj( $pubId, $issueId, $sections )
	{
		$dbdriver = DBDriverFactory::gen();
		$modifysections = array();

		foreach( $sections as $section ) {
			$values = self::objToRow( $pubId, $issueId, $section );

			// check duplicates
			$row = self::getRow( self::TABLENAME, "`section` = '".$dbdriver->toDBString( $section->Name )."' and `issue` = '$values[issue]' and `publication` = '$values[publication]' and `id` != '$section->Id'" );
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'client', null, null );
			}

			$result = self::updateRow( self::TABLENAME, $values, " `id` = '$section->Id'" );

			if( $result === true ) {
				$modifysection = self::getSectionObj( $section->Id );
				$modifysections[] = $modifysection;
			}
		}
		return $modifysections;
	}
	
    /**
     * Converts an AdmSection data object to a smart_sections DB row.
     *
     * @param int $pubId publication id
     * @param int $issueId issue id
     * @param AdmSection $obj The AdmSection data object.
     * @return array The smart_sections DB row.
     */
	static public function objToRow ( $pubId, $issueId, $obj )
	{
		$fields = array();
		
		if(!is_null($obj->Name)){
			$fields['section']		= $obj->Name;
		}
		if(!is_null($issueId)){
			$fields['issue']		= $issueId;
		}
		if(!is_null($pubId)){
			$fields['publication']	= $pubId;
		}
		if(!is_null($obj->Deadline)){
			$fields['deadline']		= $obj->Deadline ? $obj->Deadline : '';
		}
		if(!is_null($obj->ExpectedPages)){
			$fields['pages']		= (is_int($obj->ExpectedPages) ? $obj->ExpectedPages : 0);
		}
		if(!is_null($obj->Description)){
			$fields['description']	= $obj->Description;
		}
		if(!is_null($obj->SortOrder)){
			$fields['code']			= (is_int($obj->SortOrder )? $obj->SortOrder : 0);
		}
		
		return $fields;
	}
	
	/**
    * Converts a smart_sections DB row to an AdmSection data object.
	 *
    * @param array $row The smart_sections DB row.
    * @return AdmSection The AdmSection data object.
    */
	static public function rowToObj ( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$section = new AdmSection();
		$section->Id 			= $row['id'];
		$section->Name			= $row['section'];
    	$section->Description	= $row['description'];
    	$section->Deadline		= $row['deadline'];
    	$section->ExpectedPages	= $row['pages'];
    	$section->SortOrder		= $row['code'];
    	$section->IssueId		= $row['issue'];
		return $section;
	}
}
