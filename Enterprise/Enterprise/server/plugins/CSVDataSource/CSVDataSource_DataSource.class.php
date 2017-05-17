<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DataSource_EnterpriseConnector.class.php';

class CSVDataSource_DataSource extends DataSource_EnterpriseConnector
{
	final public function getPrio()           { return self::PRIO_DEFAULT; }
	final public function getConnectorType()  { return 'CSV'; }

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
	 * Accepts $settings that come from the Enterprise database.
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
		// create a static default query
		$this->Queries[] = BizDatasourceUtils::queryToObj("CSV1","Select all","","",$settings["id"],$settings["family"]);
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
		$settings = array(
			array("name" => "file",			"desc" => "File path",	"type" => "text"),
			array("name" => "delimiter",	"desc" => "Delimiter",	"type" => "text"),
			array("name" => "id",			"desc" => "Id field",	"type" => "text"),
			array("name" => "family",		"desc" => "Set field",	"type" => "text"),
						 );
		return $settings;
	}
	
	private function checkSettings()
	{
		// check the data source settings
		if( !$this->Settings["file"] )
		{
			throw new BizException( 'ERR_ERROR', 'Server', '', 'File path not set. Please, check the Data Source settings.' );
		}
		
		if( !$this->Settings["delimiter"] )
		{
			throw new BizException( 'ERR_ERROR', 'Server', '', 'Delimiter not set. Please, check the Data Source settings.' );
		}
		
		if( !$this->Settings["id"] )
		{
			throw new BizException( 'ERR_ERROR', 'Server', '', 'Id field not set. Please, check the Data Source settings.' );
		}
	}

	private function readFile()
	{
		$localFile = false;

		// check if the file exists local
		if( is_file($this->Settings["file"]) ) {
			$localFile = true;
		}
		
		$contents = file_get_contents($this->Settings["file"]);
		if( $contents === false ) {
			// file not readable
			if( $localFile == true ) {
				throw new BizException( 'ERR_ERROR', 'Server', '', 'The local file was not readable. Please, check the permissions of this file.' );
			}else{
				throw new BizException( 'ERR_ERROR', 'Server', '', 'The external file was not readable. Please, verify that it existst and that Enterprise can access it.' );
			}
		}
		
		// replace \r
		$contents = str_replace("\r", "", $contents);
		
		return $contents;
	}
	
	private function getSeperatedValues( $line )
	{
		$foundvalues = array();
		preg_match_all('/(?(?=[\"\s])\"[^\"]*\"|[^'.addslashes($this->Settings["delimiter"]).']*)/i', $line, $foundvalues);
		$foundvalues = $foundvalues[0];
		
		$values = array();
		foreach( $foundvalues as $value ) {
			
			// strip " from the beginning and end
			if( substr($value, 0, 1) == "\"" ) {
				$value = substr($value, 1);
			}
			
			if( substr($value, strlen($value) - 1, 1) == "\"" ) {
				$value = substr($value, 0, strlen($value)-1);
			}
			
			if( trim($value) != "" ) {
				$values[] = $value;
			}
		}
		
		return $values;
	}
	
	final public function getRecords( $query, $recordid, $queryparameters )
	{
		// check the settings of this data source
		$this->checkSettings();
		// read the file
		$contents = $this->readFile();
		
		// seperate the lines
		$lines = explode("\n",$contents);
		
		// get a clean array of fields (always the first line)
		$fields = $this->getSeperatedValues( $lines[0] );
		
		// init the record array which will contain the record objects
		$records = array();
		
		// create some check booleans
		$idPresent = false;
		$familyPresent = false;
		
		// create id array, to store id values
		$idValues = array();
		
		// compose records
		$recordcounter = 0;
		for( $i = 1; $i < count( $lines ) - 1; $i++ ) {
			if( trim($lines[$i]) != "" ) {
				$values = $this->getSeperatedValues( $lines[$i] );
				
				$fieldarray = array();
				$fieldcounter = 0;
				foreach ($values as $value ) {
					
					// if the field is an id field, add the value to the array (for later unique check)
					if( $fields[$fieldcounter] == $this->Settings["id"] ) {
						$idPresent = true;
						$idValues[] = $value;
					}
					
					if( $fields[$fieldcounter] == $this->Settings["family"] ) {
						$familyPresent = true;
					}
					
					$fieldarray[] = BizDatasourceUtils::fieldToObj($fields[$fieldcounter], "StrValue", utf8_encode($value), array());
					$fieldcounter++;
				}
				
				$records[] = BizDatasourceUtils::recordToObj( $recordcounter, $fieldarray );
				$recordcounter++;
			}
		}
		
		if( $idPresent == true ) {
			// check if id's were unique
			$uniqueIdValues = array_unique( $idValues );
			if( $idValues != $uniqueIdValues ) {
				LogHandler::Log("SERVER","ERROR", "The values in the field marked as 'Id field' (".$this->Settings["id"].") where not unique. The Id's are replaced with dummy values.");
			}else{
				// if they are unique, replace them by the id values
				$idcounter = 0;
				foreach( $records as &$record ) {
					$record->ID = $idValues[$idcounter];
					$idcounter++;
				}
			}
		}else{
			LogHandler::Log("SERVER","ERROR", "The field marked as 'Id field' (".$this->Settings["id"].") was not found. The Id's are replaced with dummy values.");
		}
		
		if( $familyPresent == false ) {
			LogHandler::Log("SERVER","ERROR", "The field marked as 'Set field' (".$this->Settings["family"].") was not found.");
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
	 * @param array $records List of records
	 * @param string $recordid
	 * @param QueryParam[] $queryparameters
	 */
	final public function setRecords( $records, $recordid, $queryparameters )
	{
		// not implemented in a standard CSV data source
	}
	
	/**
	 * getUpdates (mandatory)
	 * 
	 * This function will retrieve updated data from a source of data,
	 * if a notification is send to plutus.
	 * 
	 * Plutus will call this function if it receives an update notification.
	 * The output (Array of Records) will be sent to the Client.
	 * 
	 * @param array $familyvalue List of FamilyValues
	 * @return array
	 */
	final public function getUpdates( $familyvalue )
	{
		// not implemented in a standard CSV data source
		return array();
	}
	
	/**
	 * getQueries (mandatory)
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
