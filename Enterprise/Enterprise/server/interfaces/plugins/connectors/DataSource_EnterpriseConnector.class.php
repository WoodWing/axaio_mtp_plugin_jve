<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * Datasource Interface.
 * The interface every (custom) datasource should implement in order to work with Plutus
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class DataSource_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Gets a list of queries from a specific source, other than SCE DB
	 * where queries are normaly stored (in smart_datasourcesqueries)
	 * The result (An array of query objects) will be sent back to the client.
	 * 
	 * @return Array of Queries
	 */
	abstract public function getQueries();
	
	/**
	 * Returns ONE query from the list of queries
	 * 
	 * @param Query ID				$queryid
	 * @return Query Object
	 */
	abstract public function getQuery( $queryid );
	
	/**
	 * Executes a specific query on a given (custom) data source.
	 *
	 * @param 	Query 						$query
	 * @param	Record ID					$recordid
	 * @param	Array of Query Parameters 	$queryparameters
	 * @return 	Array of Records
	 */
	abstract public function getRecords( $query, $recordid, $queryparameters );
	
	/**
	 * Updates data on a given (custom) data source.
	 * 
	 * @param 	Array of Records 			$records
	 * @param	Record ID					$recordid
	 * @param	Array of Query Parameters 	$queryparameters
	 */
	abstract public function setRecords( $records, $recordid, $queryparameters );
	
	/**
	 * If SCE Server recieves an update notification of any kind,
	 * it will call this method. This method asks the external source of data
	 * for the updates and will return them to the server.
	 *
	 * @param 	FamilyValue (string)	 	$familyvalue
	 * @return 	Array of Records
	 */
	abstract public function getUpdates( $familyvalue );
	
	/**
	 * This method is mandatory because it receives any (necessairy) settings
	 * The $settings param (which is an Array) is never empty. Even if there are NO settings
	 * defined in the plutus database, it will ALWAYS contain the DatasourceID.
	 *
	 * @param Array $settings
	 */
	abstract public function setSettings( $settings );
	
	/**
	 * This method is mandatory because it defines any (necessairy) settings
	 * The output parameter is an array filled with settings. Each setting is another array,
	 * which defines the setting (name, type, etc)
	 * 
	 * @return Array of Settings
	 */
	abstract public function getSettings();

	// ===================================================================================

	// Generic methods that can be overruled by a connector:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that cannot be overruled by a connector:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}