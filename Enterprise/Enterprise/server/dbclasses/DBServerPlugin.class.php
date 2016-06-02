<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	DBClasses
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php'; 

class DBServerPlugin extends DBBase
{
	const TABLENAME = 'serverplugins';
	
	/**
	 *  Checks if the server plugin object exists at DB
	 *
	 *  @param string $plugin Plugin object (PluginInfoData) to be checked at DB
	 *  @return int plugin id or 0 if plugin not found
	**/
	static public function getPluginId( PluginInfoData $plugin )
	{
		self::clearError();
		$fields = array( '`id`' );
		if( $plugin->Id ) {
			$row = self::getRow( self::TABLENAME, "`id` = '$plugin->Id' ", $fields );
		} else if( $plugin->UniqueName ) {
			$row = self::getRow( self::TABLENAME, "`uniquename` = '$plugin->UniqueName' ", $fields );
		} else {
			$row = null;
		}
		return $row ? $row['id'] : 0;
	}
	
	/**
	 *  Retrieves one server plugin object from DB
	 *
	 *  @param object $plugin Plugin object (PluginInfoData) to be retrieved from DB
	 *  @return object of PluginInfoData if succeeded, null if no record returned
	**/
	static public function getPlugin( PluginInfoData $plugin )
	{
		self::clearError();
		if( $plugin->Id ) {
			$row = self::getRow( self::TABLENAME, "`id` = '$plugin->Id' " );
			if( !$row ) {
				self::setError( BizResources::localize('ERR_NOTFOUND') );
			}
		} else if( $plugin->UniqueName ) {
			$row = self::getRow( self::TABLENAME, "`uniquename` = '$plugin->UniqueName' " );
			if( !$row ) {
				self::setError( BizResources::localize('ERR_NOTFOUND') );
			}
		} else {
			$row = null;
			self::setError( BizResources::localize('ERR_ARGUMENT') );
		}
		if( $row ) {
			return self::rowToObj( $row );
		}
		return null;
	}

	/**
	  * Get plugin objects
	  *
	  * @return array of PluginInfoData
	  */
	static public function getPlugins()
	{
		self::clearError();
		$where = "";
		$rows = self::listRows( self::TABLENAME, 'id', 'uniquename', $where, '*' );
		$objs = array();
		if( $rows ) foreach( $rows as $row ) {
			$objs[$row['uniquename']] = self::rowToObj( $row );
		}
		return $objs;
	}

	/**
	  * Get plugin objects by interface
	  *
	  * @param  string $interface interface of the plugin(s)
	  * @return array with uniquename and displayname
	  */ 
	static public function getPluginsByInterface($interface)
	{
		self::clearError();
		$dbDriver = DBDriverFactory::gen();
		$pluginTable = $dbDriver->tablename(self::TABLENAME);
		$connectorTable = $dbDriver->tablename('serverconnectors');

		$sql  = "SELECT `uniquename`, `displayname` ";
		$sql .= "FROM $pluginTable plug ";
		$sql .= "INNER JOIN $connectorTable con ON (plug.`id` = con.`pluginid`) ";
		$sql .= "WHERE con.`interface` = '$interface' ";
		$sql .= "ORDER BY `displayname` ";
		
		$sth = $dbDriver->query($sql);
		if( is_null($sth) ) {
			$err = trim( $dbDriver->error() );
			self::setError( empty($err) ? BizResources::localize('ERR_DATABASE') : $err );
			return null;
		}		
		
		$rows = array();
		while( ($row = $dbDriver->fetch($sth)) ) {
			$rows[] = $row;
		}
		
		return $rows;
	}	
	
	/**
	 *  Create new plugin object
	 *  
	 *  @param object $plugin Plugin info data object (PluginInfoData) that need to be created
	 *  @return object The created plugin objects (from DB), or null on failure
	**/
	static public function createPlugin( PluginInfoData $plugin )
	{	
		self::clearError();
		$row = self::objToRow( $plugin );
		self::insertRow( self::TABLENAME, $row );
		$dbDriver = DBDriverFactory::gen();
		$newid = $dbDriver->newid( self::TABLENAME, true );
		if( is_null($newid) ) {
			self::setError( BizResources::localize('ERR_DATABASE') );
		} else {
			$plugin->Id = $newid;
			return self::getPlugin( $plugin );
		}
		return null; // failed
	}
	
	 /**
	 *  Modify plugin object
	 *  
	 *  @param object $plugin Plugin info data object (PluginInfoData) that need to be modified
	 *  @return object The modified plugin object (from DB), or null on failure
	**/
	static public function updatePlugin( PluginInfoData $plugin )
	{	
		self::clearError();
		$row = self::objToRow( $plugin );
		unset($row['id']);
		if( self::updateRow( self::TABLENAME, $row, " `id` = '$plugin->Id'" ) ) { // does set error
			return self::getPlugin( $plugin );
		}
		return null; // failed
	}
	
	/**
	  * Delete plugins from DB that do not exist at given list
	  *
	  * For each given plugin, its Id or UniqueName should be valid.
	  * @param array $pluginInfos List of PluginInfoData objects that should NOT be touched at DB
	  * @return array of deleted plugins (PluginInfoData objects) 
	  */
	static public function deleteNonExistingPlugins( $pluginInfos )
	{
		self::clearError();
		$where = '';
		$deletedObjs = array();
		foreach( $pluginInfos as $plugin ) {
			if( $where ) $where.= ' AND ';
			if( $plugin->Id ) {
				$where .= "NOT (`id` = '$plugin->Id') ";
			} else if( $plugin->UniqueName ) {
				$where .= "NOT (`id` = '$plugin->UniqueName') ";
			} else {
				self::setError( BizResources::localize('ERR_ARGUMENT') );
				$where = '';
				break;
			}
		}
		if( $where ) {
			$rows = self::listRows( self::TABLENAME, 'id', 'uniquename', $where, '*' );
			if( $rows ) {
				foreach( $rows as $row ) {
					$deletedObjs[] = self::rowToObj( $row );
				}
				if( self::hasError() ) {
					$deletedObjs = array(); // clear
				} else {
					self::deleteRows( self::TABLENAME, $where );
				}
			}
		}
		return $deletedObjs;
	}
	
	/**
	 *  Converts a plugin info data object into a DB plugin record (array).
	 *
	 *  @param object $plugin PluginInfoData
	 *  @return array DB plugin row
	**/
	static public function objToRow( PluginInfoData $plugin )
	{	
		$row = array();
		if(!is_null($plugin->Id))			$row['id'] 			= $plugin->Id ? $plugin->Id : 0;	
		if(!is_null($plugin->UniqueName))	$row['uniquename'] 	= $plugin->UniqueName;
		if(!is_null($plugin->DisplayName))	$row['displayname'] = $plugin->DisplayName;	
		if(!is_null($plugin->Version))		$row['version'] 	= (string)$plugin->Version; // make it string to support === and !== compares
		if(!is_null($plugin->Description))	$row['description'] = $plugin->Description;
		if(!is_null($plugin->Copyright))	$row['copyright'] 	= $plugin->Copyright;
		if(!is_null($plugin->IsActive))		$row['active'] 		= $plugin->IsActive == true ? 'on' : '';	
		if(!is_null($plugin->IsSystem))		$row['system'] 		= $plugin->IsSystem == true ? 'on' : '';	
		if(!is_null($plugin->IsInstalled))	$row['installed']	= $plugin->IsInstalled == true ? 'on' : '';	
		if(!is_null($plugin->Modified))		$row['modified'] 	= $plugin->Modified;
		return $row;
	}
	
	/**
	 *  Converts a DB plugin record (array) into a plugin info data object.
	 *
	 *  @param array $row DB plugin row
	 *  @return object PluginInfoData
	**/
	static public function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$obj = new PluginInfoData();
		$obj->Id			= $row['id'] ? $row['id'] : '';
		$obj->UniqueName	= $row['uniquename'] ? $row['uniquename'] : '';
		$obj->DisplayName	= $row['displayname'] ? $row['displayname'] : '';
		$obj->Version		= $row['version'] ? (string)$row['version'] : ''; // make it string to support === and !== compares
		$obj->Description	= $row['description'] ? $row['description'] : '';
		$obj->Copyright		= $row['copyright'] ? $row['copyright'] : '';
		$obj->IsActive		= ($row['active'] == 'on' ? true : false);
		$obj->IsSystem		= ($row['system'] == 'on' ? true : false);
		$obj->IsInstalled	= ($row['installed'] == 'on' ? true : false);
		$obj->Modified		= $row['modified'] ? $row['modified'] : '';
		return $obj;
	}
}
