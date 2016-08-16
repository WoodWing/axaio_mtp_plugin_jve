<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DataSource_EnterpriseConnector.class.php';
require_once dirname(__FILE__)."/DBUtils.class.php";
require_once BASEDIR.'/server/bizclasses/BizDatasource.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
require_once BASEDIR.'/server/dbclasses/DBDatasource.class.php';

class DBDataSource_DataSource extends DataSource_EnterpriseConnector
{
	final public function getPrio()           { return self::PRIO_DEFAULT; }
	final public function getConnectorType()  { return 'Database'; }

	/**
	 * Settings array with settings (name as key, value as value)
	 * that are free to use through a data source plugin
	 *
	 * @var Array
	 */
	private $Settings = array();
	
	/**
	 * Queries array which should contain all queries
	 *
	 * @var $Queries
	 */
	private $Queries = array();
	
	/**
	 * Accepts $settings that come from the SCE database.
	 * The settings can be added in the flex admin UI
	 * and can be used through the DatasourcePlugin file. 
	 * 
	 * e.g: $this->Settings['database']
	 * This is added in the flex admin UI as
	 * Name: database	|	Value: 127.0.0.1
	 * 
	 * An unlimited number of settings can be used.
	 * 
	 * NOTE: The DatasourceID is always available.
	 * 
	 * @param Array $settings
	 */
	final public function setSettings( $settings )
	{
		// apply the settings
		$this->Settings = $settings;
	}
	
	/**
	 * List what settings are needed (to use in the admin webapp)
	 *
	 * Settings are array's which describe an individual setting
	 * E.g: When you need to define a password setting, it could be done like this:
	 * 
	 * 		Name of the setting | 	 Description	 | 	 Type of setting
	 * array("name"=>"password" , "desc"=>"Password" , "type"=>"password");
	 * 
	 * A setting can have any chosen name, any kind of description and the following kind of types:
	 * - text
	 * - password (chars not shown as plain text but as *)
	 * - checkbox (to use with booleans)
	 * - select (which is a dropdown)
	 * 
	 * if the type is "select" you can add the following to your setting array to list options:
	 * "list"=>"option1/option2/option3/option4"
	 */
	final public function getSettings()
	{		
		$settings = array( 	array("name" => "hostname", "desc" => "Host name", 		"type" => "text"),
							array("name" => "username", "desc" => "User name", 		"type" => "text"),
							array("name" => "password", "desc" => "Password", 		"type" => "password"),
							array("name" => "database", "desc" => "Database", 		"type" => "text"),
							array("name" => "type",		"desc" => "Database type", 	"type" => "select", "list"=>"mysql/mssql/oracle"),
						 );

		if( $this->Settings["DatasourceID"] )
		{
			$datasourceInfo = BizDatasource::getDatasource( $this->Settings["DatasourceID"] );
			if( $datasourceInfo["bidirectional"] == "true" )
			{
				$settings[] = array("name" => "table",	"desc" => "Table (to save changes)", "type" => "text");
			}
		}
						 
		return $settings;
	}
	
	private function checkSettings() {
		
		// check the data source settings
		if( !$this->Settings["hostname"] || !$this->Settings["username"] || !$this->Settings["database"] )
		{
			throw new BizException( 'ERR_ERROR', 'Server', 'checkSettings()', 'The Data Source is not set up correctly. Please, check the Data Source settings.' );
		}
		
		if( $this->Settings["DatasourceID"] )
		{
			$datasourceInfo = BizDatasource::getDatasource( $this->Settings["DatasourceID"] );
			if( $datasourceInfo["bidirectional"] == "true" && !$this->Settings["table"] )
			{
				throw new BizException( 'ERR_ERROR', 'Server', 'checkSettings()', 'The Data Source was set as bi-directional, but some required settings were left empty. Please, check the Data Source settings.' );
			}
		}
	}
	
	final public function getRecords( $query, $recordid, $queryparameters )
	{
		// check the settings of this data source
		$this->checkSettings();
		
		// init the record array which will contain the record objects
		$records = array();
		
		// execute the query
		$result = DBUtils::executeQuery( $this->Settings, $query );
		foreach( $result as $row )
		{
			$fields = array();
			foreach( $row as $fieldName => $fieldValue )
			{
				// determine the $fieldType
				$fieldType = "StrValue";
				if( ctype_digit($fieldValue) ) {
					$fieldType = "IntValue";
				}
				
				$readOnly = false;
				if( $fieldName == $recordid ) {
					$readOnly = true;
				}
				
				$fields[] = BizDatasourceUtils::fieldToObj($fieldName, $fieldType, $fieldValue, array(), 'none', 'none', $readOnly);
			}
			
			$rowId = "";
			if( array_key_exists($recordid, $row) ) {
				$rowId = $row[$recordid];
			}else{
				throw new BizException( 'ERR_ERROR', 'Server', 'getRecords()', 'The field \''.$recordid.'\' was not found and could not be used as Record ID. Please, check the query definition.' );
			}
			
			$records[] = BizDatasourceUtils::recordToObj($rowId, $fields);
		}
				
		return $records;
	}
	
	/**
	 * updateData (mandatory)
	 * 
	 * This function is able to write data back to the source.
	 * If a datasource is bidirectional, this function should handle
	 * the user's changes to the records, made in the client.
	 *
	 * @param Array of Records	 	$records
	 * @return String				$message
	 */
	final public function setRecords( $records, $recordid, $queryparameters )
	{
		// check the settings of this data source
		$this->checkSettings();
		
		foreach( $records as $datRecordObj )
		{
			$paramValues = array();
			$paramNames = array();
			foreach($datRecordObj->Fields as $datRecordFieldObj )
			{
				if( $datRecordFieldObj->Name != $recordid ) { // skip the ID field
					$paramNames[] = $datRecordFieldObj->Name;
					if( property_exists($datRecordFieldObj, "IntValue") ) {
						$paramValues[] = $datRecordFieldObj->IntValue;
					}elseif( property_exists($datRecordFieldObj, "StrValue") ) {
						$paramValues[] = $datRecordFieldObj->StrValue;
					}else{
						throw new BizException( 'ERR_ERROR', 'Server', 'setRecords()', 'The field \''.$datRecordFieldObj->Name.'\' has an unkown value type.' ); // sanity check, shouldn't happen
					}
				}
			}
			
			$query = "UPDATE `".$this->Settings["table"]."` SET ";
			foreach( $paramNames as $paramName )
			{
				$query .= "`".$paramName."`=?, ";
			}
			// strip the last ,
			$query = substr($query, 0, strlen($query) - 2);
			
			$query .= " WHERE `".$recordid."`=?";
			$paramValues[] = $datRecordObj->ID;
			
			// execute the query
			DBUtils::executeUpdate( $this->Settings, $query, $paramValues );
		}
	}
	
	/**
	 * getUpdates (mandatory)
	 * 
	 * This function will retrieve updated data from a source of data,
	 * if a notification is send to plutus.
	 * 
	 * Plutus will call this function if it recieves an update notification.
	 * The output (Array of Records) will be sent to the Client.
	 * 
	 * @param 	Array of FamilyValues 	$familyvalues
	 * @return Array of Records		$records 
	 */
	final public function getUpdates( $familyvalue )
	{
		// not implemented in a standard CSV data source
	}
	
	/**
	 * getQueries (mandatory)
	 *
	 */
	final public function getQueries()
	{
		return $this->Queries;
	}
	
	/**
	 * getQuery (mandatory)
	 *
	 * @param Query ID $queryid
	 * @return object Query. Or null when not found.
	 */
	final public function getQuery( $queryid )
	{
		for($i=0; $i<count($this->Queries); $i++)
		{
			$query = $this->Queries[$i];
			if( $query->ID == $queryid )
			{
				return $query;
			}
		}
		return null;
	}
}
