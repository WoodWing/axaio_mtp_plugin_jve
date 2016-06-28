<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DataSource_EnterpriseConnector.class.php';
 
class DBDataSourceDemo_DataSource extends DataSource_EnterpriseConnector
{
	final public function getPrio()           { return self::PRIO_DEFAULT; }
	final public function getConnectorType()  { return 'db'; }

	/**
	 * Settings array with settings (name as key, value as value)
	 * that are free to use through a data source plugin
	 *
	 * @var Array
	 */
	private $Settings = array();
	
	/**
	 * Accept $settings that come from the SCE database.
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
		$settings = array();
		
		$setting1 = array("name"=>"host","desc"=>"Database host","type"=>"text"); // BZ#636
		$setting2 = array("name"=>"user","desc"=>"Database user","type"=>"text"); // BZ#636
		$setting3 = array("name"=>"pass","desc"=>"Database pass","type"=>"password"); // BZ#636
		$setting4 = array("name"=>"database","desc"=>"Database name","type"=>"text"); // BZ#636
		
		$tableList = "no available tables yet."; // BZ#636
		if( key_exists("host",$this->Settings) && key_exists("user",$this->Settings) )
		{
			if( $link = mysql_connect($this->Settings["host"], $this->Settings["user"], $this->Settings["pass"], true) )
			{
				$tableList = "";
				$tables = mysql_query("SHOW TABLES FROM ".$this->Settings["database"],$link);
				//$tables = mysql_list_tables( $this->Settings["database"] );
				while( $table = mysql_fetch_array($tables) )
				{
					$tableList .= $table[0]."/";
				}
				$tableList = substr($tableList,0,strlen($tableList)-1);
			}
		}
		$setting5 = array("name"=>"table","desc"=>"Table to store changes","type"=>"select","list"=>$tableList); // BZ#636
		$settings = array( $setting1, $setting2, $setting3, $setting4, $setting5 );
		return $settings;
	}
	
	/**
	 * Added (not mandatory) function for database connections
	 * 
	 * NOTE: This function uses the available Settings. Multiple instances of this
	 * class, with different Settings attached, are able to have different outcomes.
	 * 
	 * e.g:
	 * [Datasource #1]
	 * $this->Settings['host'] = 127.0.0.1
	 * $this->Settings['user'] = root
	 * $this->Settings['database'] = datasourcetable1
	 * 
	 * [Datasource #2]
	 * $this->Settings['host'] = 127.0.0.1
	 * $this->Settings['user'] = root
	 * $this->Settings['database'] = datasourcetable2
	 */
	final public function dbConnect()
	{
		if( !$this->Settings['host'] || !$this->Settings['user'] )
		{
			throw new BizException( 'ERR_DATABASE', 'Server', 'host and/or user setting not set. check your data source settings.' ); // BZ#636
		}
		if( !mysql_connect( $this->Settings['host'],$this->Settings['user'],$this->Settings['pass'] )) {
			throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
		}
		if( !mysql_select_db($this->Settings['database']) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
		}
	}
	
	final public function getRecords( $query, $recordid, $queryparameters )
	{
		$queryparameters = $queryparameters;
		$this->dbConnect();
		
		/**
		 * Structure of the output;
		 * + Array of Records (1)
		 * 		+ Record Object (0 .. n)
		 * 			+ Array of Fields (1)
		 * 				- Field Objects (0 .. n)
		 * 	
		 * ---- Field has the following attributes ----
		 * Name (String), Type (StrValue, ListValue, IntValue), Value (String, if Type is ListValue; Array), Attributes (Array)
		 * 
		 * Structure of a Value as Array;
		 * + ListValue (1)
		 * 		+ ListItem Objects (0 .. n)
		 * 			+ Array of Attributes (1)
		 * 				- Attribute Objects (0 .. n)
		 * 
		 * Structure of Attributes;
		 * + Array of Attributes
		 * 		+ Attribute Objects (0 .. n)
		 */	
		
		// our output variable; The array of record objects
		$records = array();
		
		// execute the query and fill the objects and arrays
		$exec = mysql_query( $query );
		if( !$exec ) {
			throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
		}
		while( ($result = mysql_fetch_array($exec)) )
		{			
			// our array of field objects
			$fields = array();
			
			foreach( $result as $key=>&$value )
			{
				if( !is_numeric($key) && $key != $recordid )
				{
					// our array of attributes (empty for this example)
					$attributes = array();
					// determine the type of value
					$valueType = BizDatasourceUtils::getValueType( $key, utf8_encode($value) );
					// fill the fields array with field objects
					$fields[] = BizDatasourceUtils::fieldToObj($key, $valueType, utf8_encode($value), $attributes );
				}
			}
			$records[] = BizDatasourceUtils::recordToObj( $result[$recordid], $fields );
		}
		return $records;
	}
	
	/**
	 * setRecords (mandatory)
	 * 
	 * This function is able to write data back to the source.
	 * If a datasource is bidirectional, this function should handle
	 * the user's changes to the records, made in the client.
	 *
	 * @param Array of Records	 	$records
	 */
	final public function setRecords( $records, $recordid, $queryparameters )
	{
		$queryparameters = $queryparameters;
		$this->dbConnect();
		
		if( !$this->Settings['table'] )
		{
			throw new BizException( 'ERR_DATABASE', 'Server', 'Table setting not set. Check your data source settings.' ); // BZ#636
		}
		
		// create an SQL statement out of the record structure.
		// every record is a new statement.
		$sql = "";
		foreach( $records as &$record )
		{		
			$id = $record->ID;	// the ID in the where clause
			$updateStatement = "";
			foreach( $record->Fields as &$field )
			{
				if( $field->UpdateType == "changed" )
				{
					foreach( $field as $key=>&$value )
					{
						// don't handle ListValue's or ImageListValue's
						if($key == "StrValue" || $key == "IntValue")
						{
							$updateStatement .= "`".$field->Name . "`='".$value."',";
						}
					}
				}
			}
			if( $updateStatement )
			{
				$updateStatement = substr($updateStatement,0, (strlen($updateStatement)-1) );
				$sql .= " UPDATE `".$this->Settings["table"]."` SET ".$updateStatement." WHERE `".$recordid."`='".$id."';";
			}
		}
		
		// attempt to throw a self explanitory error message
		if( $sql == "" )
		{
			throw new BizException( 'ERR_DATASOURCE', 'Server', 'There were no changes found in the records, nothing was saved.', 'There were no changes found in the records, nothing was saved.' ); // BZ#636
		}
		
		// execute the statement
		$exec = mysql_query($sql);
		if( !$exec ) {
			throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
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
		$familyvalue = $familyvalue; // made code analyzer happy
		// not implemented in a standard database-datasource
	}
	
	/**
	 * getQueries (mandatory)
	 *
	 */
	final public function getQueries()
	{
		// not implemented in a standard database-datasource
	}
	
	/**
	 * getQuery (mandatory)
	 *
	 * @param Query ID $queryid
	 */
	final public function getQuery( $queryid )
	{
		$queryid = $queryid; // made code analyzer happy
		// not implemented in a standard database-datasource
	}
}
