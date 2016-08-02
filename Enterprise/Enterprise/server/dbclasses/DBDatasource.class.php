<?php
/**
 * @package     SCEnterprise
 * @subpackage  DBClasses
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

//@todo Split up in different classes for each database table
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBDatasource extends DBBase
{
	const TABLENAME = 'datasources';
	
	/**
	 * Retrieve a list of Datasources (by publication id)
	 *
	 * @param int $publicationid Publication ID
	 * @return array|null List of rows when there's Datasources, Null otherwise.
	 */
	public static function queryDatasourcesByPub( $publicationid )
	{
		self::clearError();
		
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dspublications');
		$dbx = $dbdriver->tablename(self::TABLENAME);
		$publicationid = intval($publicationid);
		$params = array();
		
		// get datasource by publication id
		$sql = "SELECT d.* FROM $dbx d ".
				"LEFT JOIN $dbu dp ON d.`id` = dp.`datasourceid` ".
				"WHERE dp.`publicationid` = ?";
		$params[] = $publicationid;
		
		$sth = $dbdriver->query($sql, $params);
		
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		return self::fetchResults($sth);
	}
	
	/**
	 * ADMIN: Retrieve a list of Datasources
	 *
	 * @return array
	 */
	public static function queryDatasources()
	{
		self::clearError();
		
		// get datasources
		return self::listRows(self::TABLENAME, '', '', '', '*');
	}
	
	/**
	 * Get the basic information of a data source
	 *
	 * @param  int   $datasourceid DataSource ID
	 * @return array DataSource Row
	 */
	public static function getDatasourceInfo( $datasourceid )
	{
		self::clearError();
		$datasourceid = intval($datasourceid);
		$where = '`id` = ?';
		$params = array($datasourceid);
			
		// get datasource by id
		$datasource = self::getRow(self::TABLENAME, $where, '*', $params);
		
		if($datasource) {
			return $datasource;
		}
		
		return null;
	}
	
	/**
	 * Return datasource id identified by a name
	 *
	 * @param string $name
	 * @return array with one key: id
	 */
	public static function getDatasourceByName( $name )
	{
		self::clearError();
		
		$where = '`name` = ?';
		$params = array($name);
		$fieldnames = array('id');
		
		$datasource =  self::getRow(self::TABLENAME, $where, $fieldnames, $params);
		
		if ($datasource) {
			return $datasource;
		}
		
		return null;
	}
	
	/**
	 * Retrieve Datasource by Query id
	 *
	 * @param  int $queryid Query id 
	 * @return array|null Datasource row or null when datasource isn't found
	 * 
	 * @deprecated DEPRECATED SINCE January 17th 2008
	 */
	public static function getDatasource( $queryid )
	{
		self::clearError();

		$queryid = intval($queryid);
		$params = array();
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename(self::TABLENAME);
		$dbx = $dbdriver->tablename('dsqueries');
		
		// get datasource by query id
		$sql = "SELECT d.* FROM $dbu d ".
				"LEFT JOIN $dbx dq on d.`id` = dq.`datasourceid` ".
				"WHERE dq.`id` = ?";
		$params[] = $queryid;
		 
		$sth = $dbdriver->query($sql, $params);
		
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		$datasource = $dbdriver->fetch($sth);
		if( $datasource ) {
			return $datasource; // found datasource
		}
		
		return null; // not found
	}
	
	/**
	 * Retrieve Datasource type by Datasource id
	 *
	 * @param int $datasourceid Datasource id
	 * @return array|null Datasource type or null when not found
	 */
	public static function getDatasourceType( $datasourceid )
	{
		self::clearError();
		
		$datasourceid = intval($datasourceid);
		$fieldnames = array('type');
		$where = '`id` = ?';
		$params = array($datasourceid);
		
		$datasourcetype = self::getRow(self::TABLENAME, $where, $fieldnames, $params);
		
		if ($datasourcetype) {
			return $datasourcetype;
		}
		
		return null;
	}
	
	/**
	 * Return datasource types
	 *
	 * @return array with array('type'=><type>) 
	 */
	public static function getDatasourceTypes()
	{
		self::clearError();
		
		$where = '`interface` = ?';
		$params = array('DataSource');
		$fieldnames = array('type');

		return self::listRows('serverconnectors', '', '', $where, $fieldnames, $params);
	}
	
	/**
	 * Return if datasource is bidirectional
	 *
	 * @param int $datasourceid
	 * @return string|null bidirectional value or null when not found
	 */
	public static function getBidirectional( $datasourceid )
	{
		self::clearError();
		$datasourceid = intval($datasourceid);
		$where = '`id` = ?';
		$params = array($datasourceid);
		$fieldnames = array('bidirectional');
		
		$fetch = self::getRow(self::TABLENAME, $where, $fieldnames, $params);
		
		if (!$fetch) {
			return null;
		}
		
		return $fetch["bidirectional"];
	}
	
	/**
	 * Retrieve a list of Queries (by datasource id)
	 *
	 * @param int $dsid Datasource id
	 * @return array|null Array of Queries or null when a dabase error occurs
	 */
	public static function getQueries( $dsid )
	{
		self::clearError();
		
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dsqueries');
		$params = array();
		$dsid = intval($dsid);
		
		// get queries by datasource id
		$sql = "SELECT * FROM $dbu WHERE `datasourceid` = ? ORDER BY `name`";
		$params[] = $dsid;
		
		$sth = $dbdriver->query($sql, $params);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		return self::fetchResults($sth);
	}
	
	/**
	 * Retrieve a Query (by query id)
	 *
	 * @param int $queryid Query id
	 * @return array|null Query row or null when not found
	 */
	public static function getQuery( $queryid )
	{
		self::clearError();
		
		$queryid = intval($queryid);
		$where = '`id` = ?';
		$params = array($queryid);
		$fieldnames = '*';
		$query = self::getRow('dsqueries', $where, $fieldnames, $params);
		
		if( $query ) {
			return $query; // found query
		}
		
		return null; // not found
	}
	
	/**
	 * Insert query into database. (admin function)
	 *
	 * @param int $datasourceid
	 * @param string $name
	 * @param string $query
	 * @param string $interface
	 * @param string $comment
	 * @param string $recordid
	 * @param string $recordfamily
	 * @return int|null new id or null when error
	 */
	public static function newQuery( $datasourceid, $name, $query, $interface, $comment, $recordid, $recordfamily )
	{
		self::clearError();
		
		$values = array('datasourceid'=>intval($datasourceid), 'name'=>$name, 'query'=>$query,
		 				'interface'=>$interface, 'comment'=>$comment, 'recordid'=>$recordid, 'recordfamily'=>$recordfamily);
		
		$result = self::insertRow('dsqueries', $values);	
		
		if( !$result) {
			return null;
		}
		
		return $result; //new id
	}
	
	/**
	 * Update an existing query. (admin function)
	 *
	 * @param int $queryid
	 * @param string $name
	 * @param string $query
	 * @param string $interface
	 * @param string $comment
	 * @param string $recordid
	 * @param string $recordfamily
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function updateQuery( $queryid, $name, $query, $interface, $comment, $recordid, $recordfamily )
	{
		self::clearError();
		
		$queryid = intval($queryid);
		$where = '`id` = ?';
		$params = array($queryid);
		$values = array('name'=> $name, 'query'=>$query, 'interface'=>$interface, 'comment'=>$comment,
		 				'recordid'=>$recordid, 'recordfamily'=>$recordfamily);
		$result = self::updateRow('dsqueries', $values, $where, $params);
		
		if(!$result) {
			return null;
		}
		
		return $result;
	}
	
	/**
	 * Delete a Query (by query id)
	 *
	 * @param int $queryid Query id
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function deleteQuery( $queryid )
	{
		self::clearError();
		
		$queryid = intval($queryid);
		$where = '`id` = ?';
		$params = array($queryid);

		$result = self::deleteRows('dsqueries', $where, $params);
		
		return $result;
	}
	
	/**
	 * Create new Datasource
	 *
	 * @param string $name
	 * @param string $type
	 * @param string $bidirectional
	 * @return int|null new id or null when error
	 */
	public static function newDatasource( $name, $type, $bidirectional )
	{
		self::clearError();
		
		$values = array('type'=>$type, 'name'=>$name, 'bidirectional'=>$bidirectional);
		$result = self::insertRow(self::TABLENAME, $values);
		
		if( !$result) {
			return null;
		}
		
		return $result; //new id
	}
	
	/**
	 * Update Datasource
	 *
	 * @param int $id
	 * @param string $name
	 * @param string $bidirectional
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function updateDatasource( $id, $name, $bidirectional )
	{
		self::clearError();
		
		$id = intval($id);
		$where = '`id` = ?';
		$params = array($id);
		$values = array('name'=>$name, 'bidirectional'=>$bidirectional); 
		$result = self::updateRow(self::TABLENAME, $values, $where, $params);
		
		if( !$result) {
			return null;
		}
		
		return $result; 
	}
	
	/**
	 * Delete Datasource
	 * @todo Check return results
	 *
	 * @param int $datasourceid Datasource ID
	 * @return undefined|null null in case of an error
	 */
	public static function deleteDatasource( $datasourceid )
	{
		self::clearError();

		$datasourceid = intval($datasourceid);
		$where = '`id` = ?';
		$params = array($datasourceid);
		$result = self::deleteRows(self::TABLENAME, $where, $params);
		
		if($result == null) { //error
			return null;
		}
		
		/** Because we have no transaction handling we just try to delete related
		records and do not stop if an action fails.
		*/
		
		// also delete all settings
		$result = self::deleteSettings( $datasourceid );
		// also delete all publications
		$result = self::deletePublications( $datasourceid );
		// also delete all queries
		$result = self::deleteQueries( $datasourceid );
		
		return $result;
	}
	
	/**
	 * Update setting
	 *
	 * @param int $datasourceid
	 * @param string $name
	 * @param string $value
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function updateSetting( $datasourceid, $name, $value )
	{
		self::clearError();
		
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dssettings');
		$name = $dbdriver->toDBString($name);
		
		$sql = "UPDATE $dbu set `name`='$name', `value`=#BLOB# ".
				"WHERE `datasourceid`='$datasourceid' AND `name`='$name'";
		$sth = $dbdriver->query($sql, array(), $value);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null;
		}
		
		return true;
	}
	
	/**
	 * Create new setting
	 *
	 * @param int $datasourceid
	 * @param string $name
	 * @param string $value
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function newSetting( $datasourceid, $name, $value )
	{
		self::clearError();
		
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dssettings');
		$name = $dbdriver->toDBString($name);
		
		$sql = 	"INSERT INTO $dbu (`datasourceid`,`name`,`value`) ";
		$sql .= "VALUES ('$datasourceid','$name',#BLOB#)";
		$sql = $dbdriver->autoincrement($sql);
		$sth = $dbdriver->query($sql, array(), $value);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null;
		}
		
		return true;
	}
	
	/**
	 * Delete setting
	 *
	 * @param int $setting Setting
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function deleteSetting( $setting )
	{
		self::clearError();
		
		$where = '`id` = ?';
		$params = array($setting->ID);
		$result = self::deleteRows('dssettings', $where, $params);
		
		return $result;
	}
	
	/**
	 * Delete setting
	 *
	 * @param int $datasourceid Datasource ID
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function deleteSettings( $datasourceid )
	{
		self::clearError();
		
		$datasourceid = intval($datasourceid);
		$where = '`datasourceid` = ?';
		$params = array($datasourceid);
		$result = self::deleteRows('dssettings', $where, $params);

		return $result;
	}
	
	
	/**
	 * Create new publication
	 *
	 * @param int $publicationid
	 * @param int $datasourceid
	 * @return int|null new id or null when error
	 */
	public static function newPublication( $publicationid, $datasourceid )
	{
		self::clearError();

		$values = array('datasourceid'=>$datasourceid, 'publicationid'=>$publicationid);
		$result = self::insertRow('dspublications', $values);
		
		if(!$result) {
			return null;
		}
		
		return $result;
	}
	
	/**
	 * Delete publication
	 *
	 * @param int $publicationid
	 * @param int $datasourceid
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function deletePublication( $publicationid, $datasourceid )
	{
		self::clearError();

		$publicationid = intval($publicationid);
		$datasourceid = intval($datasourceid);
		$where = '`publicationid`= ? and `datasourceid`= ?';
		$params = array($publicationid, $datasourceid);
		$result = self::deleteRows('dspublications', $where, $params);
		
		return $result;
	}
	
	/**
	 * Delete all publications from a datasource
	 *
	 * @param int $datasourceid
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function deletePublications( $datasourceid )
	{
		self::clearError();
		
		$datasourceid = intval($datasourceid);
		$where = '`datasourceid`= ?';
		$params = array($datasourceid);
		$result = self::deleteRows('dspublications', $where, $params);
		
		return $result;
	}
	
	/**
	 * Delete all queries from a datasource
	 *
	 * @param int $datasourceid
	 */
	public static function deleteQueries( $datasourceid )
	{
		$queries = self::getQueries( $datasourceid );
		foreach( $queries as $query )
		{
			self::deleteQuery( $query["id"] );
		}
	}
	
	/**
	 * Delete all fields from a query
	 *
	 * @param int $queryid
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function deleteQueryFields( $queryid )
	{
		self::clearError();
		
		$queryid = intval($queryid);
		$where = '`queryid`= ?';
		$params = array($queryid);
		$result = self::deleteRows('dsqueryfields', $where, $params);

		return $result;
	}
	
	/**
	 * Delete a Query Field (by id)
	 *
	 * @param int $fieldid
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function deleteQueryField( $fieldid )
	{
		self::clearError();
		
		$fieldid = intval($fieldid);
		$where = '`id`= ?';
		$params = array($fieldid);
		$result = self::deleteRows('dsqueryfields', $where, $params);
		
		return $result;
	}
	
	/**
	 * Retrieve a Datasource's Settings
	 *
	 * @param int $dsid Datasource id
	 * @param bool|null $isAdmin
	 * @return array
	 */
	public static function getSettings( $dsid, $isAdmin=null )
	{
		self::clearError();
		
		$dsid = intval($dsid);
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dssettings');
		$params = array($dsid);
		
		$sql = "SELECT * FROM $dbu WHERE `datasourceid` = ?";
		$sth = $dbdriver->query($sql, $params);
		
		// no error handling on $sth since it is possible to fail without obstructing the entire process
		$settings = array();
		while( ($setting = $dbdriver->fetch($sth)) ) {
			if($isAdmin)
			{
				$settings[] = $setting;	
			}else{
				$settings[$setting['name']] = $setting['value'];
			}
		}
		
		// Add DatasourceID as a standard parameter to the settings array.
		if( !$isAdmin )
		{
			$settings["DatasourceID"] = $dsid;
		}
		
		return $settings;
	}
	
	/**
	 * Retrieve a list of Publications (by Datasource ID)
	 *
	 * @param int $dsid
	 * @return array|null Query rows or null when not found
	 */
	public static function getPublications( $dsid )
	{
		self::clearError();
		
		$dsid = intval($dsid);
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('publications');
		$dbx = $dbdriver->tablename('dspublications');
		
		// select id, publication and description from the datasource related publications
		$sql = "SELECT d.`id`, d.`publication`, d.`description` FROM $dbu d ".
				"LEFT JOIN $dbx dq on d.`id` = dq.`publicationid` ".
				"WHERE dq.`datasourceid` = ?";
		$params = array($dsid);
		$sth = $dbdriver->query($sql, $params);
		
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		$publications = self::fetchResults($sth);
		
		return $publications;
		
	}
	
	/**
	 * Retrieve a list of all Publications
	 * @return array|null Query rows or null when not found
	 */
	public static function getAllPublications()
	{
		self::clearError();
		
		$fieldnames = array('id', 'publication', 'description');
		$publications = self::listRows('publications', '', '', '', $fieldnames);
		
		return $publications;
		
	}
	
	/**
	 * Get a list of 'special' fields by Query ID
	 * The 'special' fields are readonly or priority fields.
	 *
	 * @param Query ID $queryid
	 * @return array|null Query rows or null when not found
	 */
	public static function getQueryFields( $queryid )
	{
		self::clearError();
		
		$queryid = intval($queryid);
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dsqueryfields');
		
		$sql = "SELECT * FROM $dbu f WHERE f.`queryid` = ? ORDER BY `name`";
		$params = array($queryid);
		$sth = $dbdriver->query($sql, $params);
		
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		$fields = self::fetchResults($sth);
		
		return $fields;
	}
	
	/**
	 * Update query field
	 *
	 * @param int $id
	 * @param string $name
	 * @param int $priority
	 * @param int $readonly
	 * @return bool true when succeeded or false when failed
	 */
	public static function updateField( $id, $name, $priority, $readonly )
	{
		self::clearError();
		
		$id = intval($id);
		$values = array('priority'=>$priority, 'name'=>$name, 'readonly'=>$readonly);
		$where = '`id` = ?';
		$params = array($id);
		$result = self::updateRow('dsqueryfields', $values, $where, $params);

		return $result;
	}
	
	/**
	 * Create new query field
	 *
	 * @param int $queryid
	 * @param string $name
	 * @param int $priority
	 * @param int $readonly
	 * @return bool|null true when succeeded or null when failed
	 */
	public static function newField( $queryid, $name, $priority, $readonly )
	{
		self::clearError();
		
		$values = array('queryid'=>$queryid, 'priority'=>$priority, 'name'=>$name, 'readonly'=>$readonly);
		$result = self::insertRow('dsqueryfields', $values);
		
		if(!$result) {
			return null;
		}
		
		return $result;
	}
	
	
	public static function getDirtyDocuments( $familyvalue )
	{
		self::clearError();
		
		$dbdriver = DBDriverFactory::gen();
		$dbx = $dbdriver->tablename('dsqueryplacements');
		$dby = $dbdriver->tablename('dsqueryfamilies');

		$sql = "SELECT qpc.`id`, qpc.`objectid` FROM $dbx qpc ".
				"LEFT JOIN $dby qf on qpc.`id` = qf.`queryplacementid` ".
				"WHERE qf.`familyvalue` = ?";
		$params = array($familyvalue);
		
		$sth = $dbdriver->query($sql, $params);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		$docs = self::fetchResults($sth);

		return $docs;
	}
	
	
	public static function flagDirtyDocuments( $queryplacementid )
	{
		self::clearError();
		
		$queryplacementid = intval($queryplacementid);
		$values = array('dirty'=>'1');
		$where = '`dirty`= ? and `id`= ?';
		$params = array('0', $queryplacementid);
		$result = self::updateRow('dsqueryplacements', $values, $where, $params);

		if(!$result) {
			return null; // failed
		}
		
		return $result;
	}

	
	public static function getQueryPlacement( $objectid )
	{
		self::clearError();
		
		$objectid = intval($objectid);
		$where = '`objectid` = ?';
		$fieldnames = '*';
		$params = array($objectid);
		$placement = self::getRow('dsqueryplacements', $where, $fieldnames, $params);

		return $placement;
	}
	
	
	public static function updateQueryPlacement( $objectid, $datasourceid )
	{
		self::clearError();
		
		$objectid = intval($objectid);
		$datasourceid = intval($datasourceid);
		$values = array('dirty'=>'0', 'datasourceid'=>$datasourceid);
		$where = '`objectid`= ?';
		$params = array($objectid);
		$result = self::updateRow('dsqueryplacements', $values, $where, $params);

		if(!$result) {
			return null; // failed
		}
		
		return  $result;
	}
	
	
	public static function newQueryPlacement( $objectid, $datasourceid )
	{
		self::clearError();

		$objectid = intval($objectid);
		$datasourceid = intval($datasourceid);
		$values = array('objectid'=>$objectid, 'datasourceid'=>$datasourceid, 'dirty'=>'0');
		$result = self::insertRow('dsqueryplacements', $values);

		if( !$result) {
			return null; // failed
		}
		
		return $result; // new id 
	}
	
	
	public static function deleteQueryPlacement( $objectid )
	{
		self::clearError();
		
		$objectid = intval($objectid);
		$where = '`objectid`= ?';
		$params = array($objectid);
		$result = self::deleteRows('dsqueryplacements', $where, $params);
		
		return $result;
	}
	
	
	public static function newFamilyValue( $queryplacementid, $familyfield, $familyvalue )
	{
		self::clearError();
		
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dsqueryfamilies');
		$familyfield = $dbdriver->toDBString($familyfield);

		$sql = "INSERT INTO $dbu (`queryplacementid`,`familyfield`,`familyvalue`) ".
				"VALUES ('$queryplacementid','$familyfield',#BLOB#)";
		$sql = $dbdriver->autoincrement($sql);
		$sth = $dbdriver->query($sql, array(), $familyvalue);
		
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		return $dbdriver->newid($dbu, true); // New id
	}
	
	public static function deleteFamilyValues( $queryplacementid )
	{
		self::clearError();
		
		$queryplacementid = intval($queryplacementid);
		$where = '`queryplacementid` = ?';
		$params = array($queryplacementid);
		$result = self::deleteRows('dsqueryfamilies', $where, $params);

		return $result;
	}
	
	public static function storeUpdate( $serializedset, $familyvalue )
	{
		self::clearError();
		
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dsupdates');

		$familyvalue = $dbdriver->toDBString($familyvalue);
		
		$sql = "INSERT INTO $dbu (`recordset`,`familyvalue`) VALUES (#LARGEBLOB#,'$familyvalue')";
		$sql = $dbdriver->autoincrement($sql);
		$sth = $dbdriver->query($sql, array(), $serializedset);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		return $dbdriver->newid($dbu, true); //New id
	}
	
	public static function getFamilyValues( $objectid )
	{
		self::clearError();

		$objectid = intval($objectid);
		$dbdriver = DBDriverFactory::gen();
		$dbu = $dbdriver->tablename('dsqueryplacements');
		$dbx = $dbdriver->tablename('dsqueryfamilies');
		
		$sql = "SELECT qf.* FROM $dbx qf ".
				"LEFT JOIN $dbu qp on qf.`queryplacementid` = qp.`id` ".
				"WHERE qp.`objectid` = ?";
		$params = array($objectid);
		$sth = $dbdriver->query($sql, $params);
		if( !$sth ) {
			self::setError( $dbdriver->error() );
			return null; // failed
		}
		
		$families = self::fetchResults($sth);

		return $families;
	}
	
	public static function getUpdate( $updateid )
	{
		self::clearError();
		
		$updateid = intval($updateid);
		$where = '`id` = ?';
		$params = array($updateid);
		$fieldnames = array('familyvalue', 'recordset');
		$result = self::getRow('dsupdates', $where, $fieldnames, $params);

		if (!$result) {
			return null;
		}
		return $result;
	}
	
	public static function deleteUpdate( $updateid )
	{
		self::clearError();
		
		$updateid = intval($updateid);
		$where = '`id` = ?';
		$params = array($updateid);
		$result = self::deleteRows('dsupdates', $where, $params);
		
		return $result;
	}
	
	public static function getUpdateRelation( $updateid='', $objectid='' )
	{
		self::clearError();
		
		$fieldnames = array('id', 'objectid');
		if( $updateid ) {
			$updateid = intval($updateid);
			$where = '`updateid` = ?';
			$params = array($updateid);
		} elseif( $objectid ) {
			$objectid = intval($objectid);
			$where  = '`objectid` = ?';
			$params = array($objectid);
		}
		
		$relations = self::listRows('dsobjupdates', '', '', $where, $fieldnames, $params);
		
		return $relations;
	}
	
	public static function storeUpdateObjectRelation( $objectid, $updateid )
	{
		self::clearError();
		
		$objectid = intval($objectid);
		$updateid = intval($updateid);
		
		$values = array('objectid'=>$objectid, 'updateid'=>$updateid);
		
		$result = self::insertRow('dsobjupdates', $values);	
		
		if( !$result) {
			return null;
		}
		
		return $result; //new id		
	}
	
	public static function getUpdateObjectRelation( $objectid, $updateid )
	{
		self::clearError();
		
		$objectid = intval($objectid);
		$updateid = intval($updateid);
		
		self::clearError();
		$where = '`objectid` = ? AND `updateid` = ?';
		$params = array($objectid, $updateid);
		$fieldnames = array('id');
			
		// get datasource by id
		$result = self::getRow('dsobjupdates', $where, $fieldnames, $params);
		
		if($result) {
			return $result;
		}
		
		return null;		
		
	}
	
	public static function deleteUpdateObjectRelation( $objectid, $updateid='' )
	{
		self::clearError();
		
		$updateid = intval($updateid);
		$objectid = intval($objectid);
		
		if( $updateid ) {
			$where = '`updateid` = ? AND `objectid` = ?';
			$params = array($updateid);
			$params = array($objectid);
		} elseif( $objectid ) {
			$where  = '`objectid` = ?';
			$params = array($objectid);
		}		
		
		$result = self::deleteRows('dsobjupdates', $where, $params);
		
		return $result;		
	}
}
