<?php

/**
 * @since       v6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBIssueSection extends DBBase
{
	const TABLENAME = 'issuesection';	
	
	/* Method retrieves row for specied issue/section
	 * @param 	$issue	issueId		int
	 * @param 	$section	sectionId	int
	 * @return	retrieved row with 'deadline'
	 */
	static public function getIssueSection($issue, $section)
	{
		$dbDriver = DBDriverFactory::gen();
		$dbdis = $dbDriver->tablename(self::TABLENAME);
		$sql = "SELECT `deadline` FROM $dbdis WHERE `section` = ? AND `issue` = ?";
		$params = array( intval( $section ), intval( $issue ) );
		$sth = $dbDriver->query($sql, $params);
		$result = $dbDriver->fetch($sth);	
		return $result;
	}

	/**
	 * Returns the earliest deadline defined for a collection of issues and a specified
	 * category and status.
	 * The order by iss.`deadline` ASC is done to make sure the erliest deadline is retrieved.
	 * The other rows are not fetched as we only need the earliest one.
	 * @param array $issueids Collection of issue ids
	 * @param integer $sectionid Specified category
	 * @param integer $stateid Specified status
	 * @return string deadline if found else empty string
	 */
	static public function getDeadlineForIssueCategoryStatus( $issueids, $sectionid, $stateid )
	{
		$dbDriver = DBDriverFactory::gen();
		$tb_iss_sec_sta = $dbDriver->tablename("issuesectionstate");
		$tb_issues = $dbDriver->tablename('issues');
		$issues = implode(', ',$issueids); // BZ#21218: For multiple issues, no deadline was set at all (due to missing comma).

		$sql  = " SELECT iss.`deadline` FROM $tb_iss_sec_sta iss ";
		$sql .= " INNER JOIN $tb_issues issues ON ( iss.`issue` = issues.`id` AND issues.`active` = 'on') ";
		$sql .= " WHERE iss.`issue` IN ($issues) AND iss.`section` = ? AND iss.`state` = ? ";
		$sql .= " ORDER BY iss.`deadline` ASC ";
		$params = array( intval( $sectionid ), intval( $stateid ) );
		$sth  = $dbDriver->query( $sql, $params );
		$row  = $dbDriver->fetch($sth);

		if ( $row ) {
			return $row['deadline'];
		}

		return '';
	}

	/**
	 * Returns the earliest deadline defined for a collection of issues and a specified
	 * category.
	 * The order by iss.`deadline` ASC is done to make sure the erliest deadline is retrieved.
	 * The other rows are not fetched as we only need the earliest one.
	 * @param array $issueids Collection of issue ids
	 * @param integer $sectionid Specified category
	 * @return string deadline if found else empty string
	 */
	static public function getDeadlineForIssueCategory( $issueids, $sectionid )
	{
		$dbDriver = DBDriverFactory::gen();
		$tb_iss_sec = $dbDriver->tablename("issuesection");
		$tb_issues = $dbDriver->tablename('issues');
		$issues = implode(', ',$issueids); // BZ#21218: For multiple issues, no deadline was set at all (due to missing comma).

		$sql  = " SELECT * FROM $tb_iss_sec isec ";
		$sql .= " INNER JOIN $tb_issues issues ON ( isec.`issue` = issues.`id` AND issues.`active` = 'on') ";
		$sql .= " WHERE isec.`issue` IN ($issues) AND isec.`section` = ? ";
		$sql .= " ORDER BY isec.`deadline` ASC ";
		$params = array( intval( $sectionid ) );
		$sth = $dbDriver->query( $sql, $params );
		$row = $dbDriver->fetch($sth);

		if ( $row ) {
			return $row['deadline'];
		}

		return '';
	}
}
