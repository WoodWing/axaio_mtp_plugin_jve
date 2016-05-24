<?php

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';

class DBRouting extends DBBase 
{
	const TABLENAME = 'routing';
	
	static public function getRouting( $publ, $issue = null )
	{
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename(self::TABLENAME);

		$sql = "SELECT `section`, `state`, `routeto` FROM $db WHERE `publication` = $publ";
		if ($issue) {
			$sql .= " AND (`issue` = $issue OR `issue` = 0)";
		} else {
			$sql .= " AND `issue` = 0";
		}
		$sth = $dbDriver->query($sql);
		return $sth;
	}

	/**
	 * Delete routing records.
	 * 
	 * Deletes routing records based on the passed filters.
	 * The parameters can either be set to null, which means ignore, or can be an id. If the id is 0 it means <All>.
	 * Routing is set at issue (overrule brand) or brand level. In both cases the brand id is mandatory. 
	 * To delete Routing at brand level pass 0 as $issueId. For overrule brand issues pass the id of the issue.
	 * @param int $pubId Publication Id
	 * @param null|int $issueId Issue Id
	 * @param null|int $sectionId Category Id
	 * @param null|int $stateId State Id
	 * @param null|int $routeTo Name of the route to group.
	 * @return null|bool Null in case of error else true 
	 * @throws BizException
	 */	
	static function deleteRouting( $pubId, $issueId, $sectionId, $stateId, $routeTo )
	{
		if ( is_null( $pubId ) || $pubId == 0 ) {
			throw new BizException( 'ERR_ARGUMENT', 'client', null, null);	
		}
		
		$params = array();
		
		$where = '`publication` = ? ';
		$params[] = $pubId;
		
		if ( !is_null( $issueId )) {
			$where .= 'AND `issue` = ? ';
			$params[] = $issueId;
		}
		if ( !is_null( $sectionId )) {
			$where .= 'AND `section` = ? ';
			$params[] = $sectionId;
		}
		if ( !is_null( $stateId )) {
			$where .= 'AND `state` = ? ';
			$params[] = $stateId;
		}
		if ( !is_null( $routeTo )) {
			$where .= 'AND `routeto` = ? ';
			$params[] = $routeTo;
		}
		
		return self::deleteRows(self::TABLENAME, $where, $params);		
		
	}	
}