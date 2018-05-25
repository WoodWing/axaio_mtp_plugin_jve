<?php

/**
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';

class DBSection extends DBBase
{
	const TABLENAME = 'publsections';

	/**
	 * Resolve a section name by id.
	 *
	 * @param integer $id Section id.
	 * @return string The section name. Empty when not found.
	 */
	static public function getSectionName( $id )
	{
		$fieldNames = array( 'id', 'section' );
		$where = '`id` = ?';
		$params = array( intval( $id ) );
		$row = self::getRow( self::TABLENAME, $where, $fieldNames, $params );
		return $row ? $row['section'] : '';
	}

	/**
	 * Update an Section definition (record in the publsections-table) with the values supplied in $values
	 *
	 * @param $sectionDefId Id of the Section-definition to update
	 * @param $values array of values to update, indexed by fieldname. $values['issue'] = issue1, etc...
	 *        The array does NOT need to contain all values, only values that are to be updated.
	 * @return true if succeeded, false if an error occured.
	 */
    public static function updateSectionDef( $sectionDefId, $values )
    {
	    $where = '`id` = ?';
	    $params = array( intval( $sectionDefId ) );
	    return self::updateRow( self::TABLENAME, $values, $where, $params );
    }

    /**
     *  Lists all sectiondefinitions defined for the publication
     *  What is exactly returned depends on the value of $fieldnames:
     *  - Either null or false -> returns an array of rows indexed by the value in $keycol, each containing the name ($namecol) of the row.
     *  - Either '*' or true -> returns an array of rows indexed by the value in $keycol, each containing an array with all values.
     *  - An array with fieldnames -> returns an array of rows indexed by the value in $keycol, each containing an array with the values in $fieldnames.
     *
     * @param integer $pubId Id of the publication, if $publid = 0 null is returned;
     * @param string|string[] $fieldNames see function description
     * @return array|null NULL in case of error, otherwise see function description
     */
    public static function listPublSectionDefs( $pubId, $fieldNames = '*' )
    {
	    if( empty( $pubId ) ) {
		    return null;
	    }
	    $where = '`publication` = ? AND `issue` = ? ';
	    $params = array( intval( $pubId ), 0 );
	    $orderBy = array( 'code' => true );
	    return self::listRows( self::TABLENAME, 'id', 'section', $where, $fieldNames, $params, $orderBy );
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
		if( $issue['overrulepub'] === true ) {
			$where = '`issue` = ?';
			$params = array( intval( $issueId ) );
			$orderBy = array( 'code' => true );
			return self::listRows( self::TABLENAME, 'id', 'section', $where, $fieldnames, $params, $orderBy );
		} else {
			return $nopubldefs ? null : self::listPublSectionDefs( $issue['publication'] );
		}
	}

	/**
	 *  Lists all sections for the issue
	 *  What is exactly returned depends on the value of $fieldnames:
	 *  - Either null or false -> returns an array of rows indexed by the value in $keycol, each containing the name ($namecol) of the row.
	 *  - Either '*' or true -> returns an array of rows indexed by the value in $keycol, each containing an array with all values.
	 *  - An array with fieldnames -> returns an array of rows indexed by the value in $keycol, each containing an array with the values in $fieldnames.
	 *
	 * @param  int $issueId Id of the issue, if $issueid = 0 null is returned;
	 * @param  mixed $fieldNames see function description
	 * @return  null in case of error, otherwise see function description
	 */
	public static function listIssueSections( $issueId, $fieldNames = '*' )
	{
		$where = '`issue` = ?';
		$params = array( intval( $issueId ) );
		return self::listRows( 'issuesection', 'id', 'section', $where, $fieldNames, $params );
	}

	/**
	 *  Gets exactly one section from issue $issueid, defined by $sectiondefid
	 *  What is exactly returned depends on the value of $fieldnames:
	 *  - Either null or false -> returns an array with the following two keys: 'id' and 'issue'.
	 *  - Either '*' or true -> returns an array with all fieldname-value-pairs of the issue.
	 *  - An array with fieldnames -> returns an array with the fieldname-value-pairs in $fieldnames.
	 *
	 * @param  int $issueId Id of the issue
	 * @param  int $sectionDefId Id of the section definition by which section is defined.
	 * @param  mixed $fieldNames see function description.
	 * @return  null in case of error, else see function description
	 */
	public static function getIssueSection( $issueId, $sectionDefId, $fieldNames = '*' )
	{
		$where = '`issue` = ? AND `section` = ? ';
		$params = array( intval( $issueId ), intval( $sectionDefId ) );
		return self::getRow( 'issuesection', $where, $fieldNames, $params );
	}

    /**
     *  updates a Section (record in the issuesections-table) with the values supplied in $values
     *  @param $issueSectionId Id of the section to update
     *  @param $values array of values to update, indexed by fieldname. $values['issue'] = issue1, etc...
     *         The array does NOT need to contain all values, only values that are to be updated.
     *  @return true if succeeded, false if an error occurred.
     */
	public static function updateIssueSection( $issueSectionId, $values )
	{
		$where = '`id` = ?';
		$params = array( intval( $issueSectionId ) );
		return self::updateRow( 'issuesection', $values, $where, $params );
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
	public static function insertIssueSection( $issueId, $sectionDefId, $values, $updateIfExists = false )
	{
		$issueId = intval( $issueId );
		$sectionDefId = intval( $sectionDefId );
		$where = '`issue` = ? AND `section` = ? ';
		$params = array( $issueId, $sectionDefId );
		$sectionExists = self::getRow( 'issuesection', $where, array('id'), $params );
		if( $sectionExists ) {
			if( $updateIfExists ) {
				self::updateIssueSection( $sectionExists['id'], $values );
			} else {
				self::setError( "ERR_RECORDEXISTS" );
			}
		} else {
			$values['issue'] = $issueId;
			$values['section'] = $sectionDefId;
			self::insertRow( 'issuesection', $values );
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
		$where = "`publication` = ? ";
		$params = array( intval( $pubId ) );
		if( ((string)($issueId) === (string)(int)($issueId)) && $issueId > 0 ) { // natural and positive
			$where .= "AND (`issue` = ? OR `issue` = ?) ";
			$params[] = intval( $issueId );
			$params[] = 0;
		} else {
			$where .= "AND `issue` = ? ";
			$params[] = 0;
		}
		if( ((string)($sectionId) === (string)(int)($sectionId)) && $sectionId > 0 ) { // natural and positive
			$where .= "AND `id` = ? ";
			$params[] = intval( $sectionId );
		}
		if( $sectionName ) { 
			$where .= "AND `section` = ? ";
			$params[] = trim( strval( $sectionName ) );
		}
		// run DB query
		$db = $dbDriver->tablename( self::TABLENAME );
		$sql = "SELECT `id`, `issue`, `section` from $db WHERE $where ORDER BY `code`, `id` ";
		$sth = $dbDriver->query( $sql, $params );
		return $sth;
	}

	/**
	 * Lists all sections of the publication and issue as objects.
	 *
	 * @param int $pubId Id of the publication that sections belongs to
	 * @param int $issueId Id of the issue that sections belongs to
	 * @return AdmSection[] List of sections found. Empty when none found.
	 * @throws BizException on SQL error
	 */
	static public function listSectionsObj( $pubId, $issueId )
	{
		$where = "`publication` = ? and `issue` = ? ";
		$params = array( intval($pubId), intval($issueId) );
		$orderBy = array( 'code' => true );
		$rows = self::listRows( self::TABLENAME, 'id', 'section', $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$sections = array();
		if( $rows ) {
			foreach( $rows as $row ) {
				$sections[] = self::rowToObj( $row );
			}
		}
		return $sections;
	}

	/**
	 * Get one section by id from DB.
	 * 
    * @param int $sectionId Id of the section to get the values from
    * @return AdmSection|null Section if succeeded, or null if the section was not found.
	 * @throws BizException on SQL error
	 */
	static public function getSectionObj( $sectionId )
	{
		$where = '`id` = ?';
		$params = array( intval($sectionId) );
		$row = self::getRow(self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj($row) : null;
	}

	/**
	 * Gets section objects based on a list of section ids from DB
	 *
	 * @since 10.2.0
	 * @param integer[] $sectionIds List of section ids.
	 * @return AdmSection[] The list of section objects if succeeded.
	 * @throws BizException on SQL error
	 */
	static public function getSectionObjs( array $sectionIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $sectionIds );
		if( !$where ) {
			throw new BizException('ERR_ARGUMENT', 'Client', 'No section ids provided.' );
		}
		$rows = self::listRows( self::TABLENAME, null, null, $where );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		$sections = array();
		foreach( $rows as $row ) {
			$sections[] = self::rowToObj( $row );
		}
		return $sections;
	}

	/**
	 *  Create new section object
	 *
	 * @param int $pubId publication that new section belongs to
	 * @param int $issueId Issue that new section belongs to
	 * @param array $sections array of new sections that will created
	 * @return AdmSection[] new created section objects
	 * @throws BizException on SQL error or when section already exists
	 */
	public static function createSectionsObj( $pubId, $issueId, $sections )
	{
		$dbdriver = DBDriverFactory::gen();
		$newsections = array();

		foreach( $sections as $section ) {
			$values = self::objToRow( $pubId, $issueId, $section );

			// check duplicates
			$where = "`section` = ? and `publication` = ? and `issue` = ? ";
			$params = array( strval($values['section']), intval($values['publication']), intval($values['issue']) );
			$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			self::insertRow( self::TABLENAME, $values );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
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
	 * @return array of modified Section objects
	 * @throws BizException on SQL error or when section already exists
	 */
	public static function modifySectionsObj( $pubId, $issueId, $sections )
	{
		$dbdriver = DBDriverFactory::gen();
		$modifysections = array();

		foreach( $sections as $section ) {
			$values = self::objToRow( $pubId, $issueId, $section );

			// check duplicates
			$where = '`section` = ? AND `publication` = ? AND `issue` = ? AND `id` != ?';
			$params = array( strval($section->Name), intval($values['publication']), intval($values['issue']), intval($section->Id) );
			$row = self::getRow( self::TABLENAME, $where, array('id'), $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
			if( $row ) {
				throw new BizException( 'ERR_DUPLICATE_NAME', 'Client', $row['id'] );
			}

			$where = '`id` = ?';
			$params = array( intval($section->Id) );
			$result = self::updateRow( self::TABLENAME, $values, $where, $params );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}

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
	static public function objToRow( $pubId, $issueId, $obj )
	{
		$row = array();

		if( !is_null( $obj->Name ) ) {
			$row['section'] = strval( $obj->Name );
		}
		if( !is_null( $issueId ) ) {
			$row['issue'] = intval( $issueId );
		}
		if( !is_null( $pubId ) ) {
			$row['publication'] = intval( $pubId );
		}
		if( !is_null( $obj->Deadline ) ) {
			$row['deadline'] = $obj->Deadline ? strval( $obj->Deadline ) : '';
		}
		if( !is_null( $obj->ExpectedPages ) ) {
			$row['pages'] = intval( $obj->ExpectedPages );
		}
		if( !is_null( $obj->Description ) ) {
			$row['description'] = strval( $obj->Description );
		}
		if( !is_null( $obj->SortOrder ) ) {
			$row['code'] = intval( $obj->SortOrder );
		}

		return $row;
	}
	
	/**
    * Converts a smart_sections DB row to an AdmSection data object.
	 *
    * @param array $row The smart_sections DB row.
    * @return AdmSection The AdmSection data object.
    */
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$section = new AdmSection();
		$section->Id            = intval($row['id']);
		$section->Name          = strval($row['section']);
    	$section->Description   = strval($row['description']);
    	$section->Deadline      = strval($row['deadline']);
    	$section->ExpectedPages = intval($row['pages']);
    	$section->SortOrder     = intval($row['code']);
    	$section->IssueId       = intval($row['issue']); // hack! (used by removesection.php)
		return $section;
	}
}
