<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DataSource_EnterpriseConnector.class.php';

class CSVDataSourceDemo_DataSource extends DataSource_EnterpriseConnector
{
	final public function getPrio()           { return self::PRIO_DEFAULT; }
	final public function getConnectorType()  { return 'csv'; }

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
		// add a test query to the query array
		$this->Queries[] = BizDatasourceUtils::queryToObj("3","Select All","NULL","Test,string","id","genre");
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
		$setting1 = array("name"=>"file","desc"=>"File name","type"=>"text");
		$setting2 = array("name"=>"delimiter","desc"=>"CSV delimiter","type"=>"text"); 	// BZ#636
		
		$settings = array( $setting1, $setting2 );
		return $settings;
	}

	final public function getRecords( $query, $recordid, $queryparameters )
	{
		if( !$this->Settings["file"] )
		{
			throw new BizException( 'ERR_DATABASE', 'Server', 'file setting not set. check your data source settings.' );
		}
		
		if( !is_file($this->Settings["file"]) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', "file [".$this->Settings["file"]."] not found" );
		}
		
		// get the file content
		$filecontent = file_get_contents($this->Settings["file"]);
		
		// replace \r
		$filecontent = str_replace("\r","",$filecontent);
		
		// seperate the lines
		$lines = explode("\n",$filecontent);
		
		// determine the line with the fields in it.
		$fieldline = $lines[0];
		
		// seperate the fields
		$foundfields = array();
		preg_match_all("/(?(?=[\"\s])\"[^\"]*\"|[^,]*)/",$fieldline,$foundfields);
		// clean array (RESOLVE THIS BY REVIEWING THE REGEXP!!)
		$fields = array();
		for( $i=0;$i<count($foundfields[0]);$i++ )
		{
			if( $foundfields[0][$i] )
			{
				array_push($fields,$foundfields[0][$i]);
			}
		}
		
		// init the record array which will contain the record objects
		$records = array();
		
		// every line (except the 0-line is a record)
		for($i=1;$i<count($lines)-1;$i++)
		{
			// every comma seperated variable in the line is a value
			// so seperate the values
			$foundvalues = array();
			preg_match_all("/(?(?=[\"\s])\"[^\"]*\"|[^,]*)/",$lines[$i],$foundvalues);
			// clean array (RESOLVE THIS BY REVIEWING THE REGEXP!!)
			$recordvalues = array();
			for($x = 0; $x<count($foundvalues[0]);$x++)
			{
				if( $foundvalues[0][$x] )
				{
					array_push($recordvalues,$foundvalues[0][$x]);
				}
			}
			
			// init the field array which will contain the field objects
			$fieldarray = array();
			
			// clean the values and create objects
			for($x=0;$x<count($recordvalues);$x++)
			{
				$fieldarray[] = BizDatasourceUtils::fieldToObj($fields[$x],"StrValue",utf8_encode($recordvalues[$x]),array());
			}
			$records[] = BizDatasourceUtils::recordToObj( $i, $fieldarray );
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
		// not implemented in a standard CSV datasource
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
		// not implemented in a standard CSV datasource
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
		// EXAMPLE on how to return a query manually
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
