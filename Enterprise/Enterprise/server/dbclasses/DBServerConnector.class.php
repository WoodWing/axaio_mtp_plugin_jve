<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBServerConnector extends DBBase
{
	const TABLENAME = 'serverconnectors';

	/**
	 * Checks if the server connector object exists at DB and returns its id
	 *
	 * @param ConnectorInfoData $conn Connector object (ConnectorInfoData) to be checked at DB.
	 * @return string Connector id. Zero when not found.
	 */
	static public function getConnectorId( ConnectorInfoData $conn )
	{
		$fields = array( '`id`' );
		/*if( $conn->Id ) {
			$row = self::getRow( 'serverconnectors', "`id` = '$conn->Id' ", $fields );
		} else*/
		if( $conn->ClassName ) {
			$row = self::getRow( self::TABLENAME, "`classname` = '$conn->ClassName' ", $fields );
		} else {
			$row = null;
		}
		return $row ? $row['id'] : 0;
	}

	/**
	 *  Retrieves one server connector object from DB
	 *
	 * @param ConnectorInfoData $conn Connector object (ConnectorInfoData) to be retrieved from DB
	 * @return ConnectorInfoData|null ConnectorInfoData if succeeded, null if no record returned
	 */
	static public function getConnector( ConnectorInfoData $conn )
	{
		self::clearError();
		/*if( $conn->Id ) {
			$row = self::getRow( 'serverconnectors', "`id` = '$conn->Id' ", '*' );
			if( !$row ) {
				self::setError( BizResources::localize('ERR_NOTFOUND') );
			}
		} else */
		if( $conn->ClassName ) {
			$row = self::getRow( self::TABLENAME, "`classname` = '$conn->ClassName' ", '*' );
			if( !$row ) {
				self::setError( BizResources::localize( 'ERR_NOTFOUND' ) );
			}
		} else {
			$row = null;
			self::setError( BizResources::localize( 'ERR_ARGUMENT' ) );
		}
		if( $row ) {
			return self::rowToObj( $row );
		}
		return null;
	}

	/**
	  * Get connector objects
	  *
	  * @param string $interface  	Connector interface. Null for any interface.
	  * @param string $type		  	Connector type. Null for any type.
	  * @param boolean $activeOnly	Include connectors of activated plugins only. Default true.
	  * @param boolean $installedOnly	Include connectors of installed plugins only. Default true.
	  * @return array of ConnectorInfoData
	  */
	static public function getConnectors( $interface, $type, $activeOnly = true, $installedOnly = true )
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$conTable = $dbDriver->tablename( self::TABLENAME );
		$plnTable = $dbDriver->tablename( 'serverplugins' );
		$sql = "SELECT c.* FROM $conTable c LEFT JOIN $plnTable p ON (p.`id` = c.`pluginid`) ";
		$where = '';
		if( $activeOnly === true ) {
			if( !empty( $where ) ) $where .= 'AND ';
			$where .= "p.`active` = 'on' ";
		}
		if( $installedOnly === true ) {
			if( !empty( $where ) ) $where .= 'AND ';
			$where .= "p.`installed` = 'on' ";
		}
		if( !is_null($interface) ) {
			if( !empty( $where ) ) $where .= 'AND ';
			$where .= "c.`interface` = '$interface' ";
		}
		if( !is_null($type) ) {
			if( !empty( $where ) ) $where .= 'AND ';
			$where .= "c.`type` = '$type' ";
		}
		if( !empty( $where ) ) {
			$sql .= 'WHERE '.$where;
		}
        $sth = $dbDriver->query($sql);
		if( is_null($sth) ) {
			$err = trim($dbDriver->error());
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			$rows = null;
		} else {
			$rows = self::fetchResults( $sth, 'id' );
		}

		$objs = array();
		if( $rows ) foreach( $rows as $row ) {
			$objs[$row['classname']] = self::rowToObj( $row );
		}
		return $objs;
	}

	/**
	 *  Create new connector object
	 *
	 * @param ConnectorInfoData $conn Connector info data object (ConnectorInfoData) that need to be created
	 * @return object The created connector objects (from DB), or null on failure
	 */
	static public function createConnector( ConnectorInfoData $conn )
	{
		self::clearError();
		$row = self::objToRow( $conn );
		self::insertRow( self::TABLENAME, $row );
		$dbDriver = DBDriverFactory::gen();
		$newid = $dbDriver->newid( self::TABLENAME, true );
		if( is_null( $newid ) ) {
			self::setError( BizResources::localize( 'ERR_DATABASE' ) );
		} else {
			$conn->Id = $newid;
			return self::getConnector( $conn );
		}
		return null; // failed
	}

	/**
	 *  Modify connector object
	 *
	 * @param ConnectorInfoData $conn Connector info data object (ConnectorInfoData) that need to be modified
	 * @return object The modified connector object (from DB), or null on failure
	 */
	static public function updateConnector( ConnectorInfoData $conn )
	{
		self::clearError();
		$row = self::objToRow( $conn );
		unset( $row['id'] );
		if( self::updateRow( self::TABLENAME, $row, "`classname` = '$conn->ClassName'" ) ) { // does set error
			return self::getConnector( $conn );
		}
		return null; // failed
	}

	/**
	 * Delete plugin's connectors from DB that do not exist at given list.
	 *
	 * For each given connector, its Id or ClassName should be valid
	 * and its PluginId must match the given $pluginId.
	 *
	 * @param string[] $connClasses List of connector class names that should NOT be touched at DB
	 */
	static public function deleteConnectorsOnClassNames( $connClasses )
	{
		self::clearError();
		$where = '';
		foreach( $connClasses as $connClass ) {
			if( $where ) $where .= ' AND ';
			if( $connClass ) {
				$where .= "NOT ( `classname` = '$connClass' ) ";
			} else {
				self::setError( BizResources::localize( 'ERR_ARGUMENT' ) );
				$where = '';
				break;
			}
		}
		if( $where ) {
			self::deleteRows( self::TABLENAME, $where );
		}
	}
	
	/**
	  * Delete all connectors from DB that belong to the given plugin.
	  *
	  * @param string $pluginId  Database id of plugin
	  */
	static public function deleteConnectorsOnPluginId( $pluginId )
	{
		if( !$pluginId ) {
			self::setError( BizResources::localize('ERR_ARGUMENT') );
		}
		$where = "`pluginid` = '$pluginId' ";
		self::deleteRows( self::TABLENAME, $where );
	}

	/**
	 * Converts a connector info data object into a DB connector record (array).
	 *
	 * @param ConnectorInfoData $conn ConnectorInfoData object.
	 * @return array DB connector row
	 */
	static public function objToRow( ConnectorInfoData $conn )
	{
		$row = array();
		if( !is_null( $conn->Id ) ) $row['id'] = $conn->Id ? $conn->Id : 0;
		if( !is_null( $conn->PluginId ) ) $row['pluginid'] = $conn->PluginId ? $conn->PluginId : 0;
		if( !is_null( $conn->ClassName ) ) $row['classname'] = $conn->ClassName;
		if( !is_null( $conn->Interface ) ) $row['interface'] = $conn->Interface;
		if( !is_null( $conn->Type ) ) $row['type'] = trim( $conn->Type );
		if( !is_null( $conn->Prio ) ) $row['prio'] = $conn->Prio ? $conn->Prio : 500;
		if( !is_null( $conn->RunMode ) ) $row['runmode'] = $conn->RunMode;
		if( !is_null( $conn->ClassFile ) ) $row['classfile'] = $conn->ClassFile;
		if( !is_null( $conn->Modified ) ) $row['modified'] = $conn->Modified;
		return $row;
	}
	
	/**
	 *  Converts a DB connector record (array) into a connector info data object.
	 *
	 *  @param array $row DB connector row
	 *  @return object ConnectorInfoData
	**/
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/plugins/ConnectorInfoData.class.php';
		$obj = new ConnectorInfoData();
		$obj->Id			= $row['id'] ? $row['id'] : '';
		$obj->PluginId		= $row['pluginid'] ? $row['pluginid'] : '';
		$obj->ClassName		= $row['classname'] ? $row['classname'] : '';
		$obj->Interface		= $row['interface'] ? $row['interface'] : '';
		$obj->Type			= $row['type'] ? trim($row['type']) : '';
		$obj->Prio			= $row['prio'] ? $row['prio'] : 500;
		$obj->RunMode		= $row['runmode'] ? $row['runmode'] : '';
		$obj->ClassFile		= $row['classfile'] ? $row['classfile'] : '';
		$obj->Modified		= $row['modified'] ? $row['modified'] : '';
		return $obj;
	}
}
