<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/DataSource_EnterpriseConnector.class.php';

class CustomDataSourceDemo_DataSource extends DataSource_EnterpriseConnector
{
	final public function getPrio()           { return self::PRIO_DEFAULT; }
	final public function getConnectorType()  { return 'custom_example'; }

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
		
		$setting1 = array("name"=>"host","desc"=>"Database host","type"=>"text");
		$setting2 = array("name"=>"user","desc"=>"Database user","type"=>"text");
		$setting3 = array("name"=>"pass","desc"=>"Database pass","type"=>"password");
		$setting4 = array("name"=>"database","desc"=>"The Database name","type"=>"text");
		$setting5 = array("name"=>"table","desc"=>"The table to write changes back","type"=>"select","list"=>"movieTable");
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
			throw new BizException( 'ERR_DATABASE', 'Server', 'host and/or user setting not set. check your data source settings.' );
		}
		if( !mysql_connect( $this->Settings['host'],$this->Settings['user'],$this->Settings['pass'] ) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
		}
			
		if( !mysql_select_db( $this->Settings['database'] ) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
		}
	}
	
	final public function getRecords( $query, $recordid, $queryparameters )
	{
		$queryparameters = $queryparameters;
		// our output variable; The array of record objects
		$records = array();
		
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
		
		// execute the query and fill the objects and arrays
		$exec = mysql_query( $query );
		if( !$exec ) {
			throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
		}
		while( ($result = mysql_fetch_array($exec)) )
		{
			
			/**
			 * LIST EXAMPLE:
			 * If the query selects every movie from our table,
			 * Perform a subquery to obtain all actors in the movie and
			 * create a list. It is possible to add multiple attributes to list items.
			 * 
			 * NOTE: In XML this 'list' would look like this;
			 * <Actors>
			 * 		<Actor role="Frank">John</Actor>
			 * 		<Actor role="Linda">Jane</Actor>
			 * </Actors>
			 */
			//if( $query == "select * from movieTable" )
			if ( preg_match( "/select \* from movieTable/i", $query ) )
			{
				// subquery #1
				$q1 = mysql_query("SELECT * FROM actorsTable WHERE mid = '".$result["id"]."'");
				if( !$q1 ) {
					throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );
				}
				// Field of type: ListValue $actorList
				$actorList = array();
				while( ($r1 = mysql_fetch_array($q1)) )
				{
					// our temporary array to fill with attribute objects
					$actorAttributes = array();
					// create an array of attributes
					$actorAttributes[] = BizDatasourceUtils::attributeToObj("role",utf8_encode($r1["role"]));
					// add the found actor (and it's attributes) to the list
					$actorList[] = BizDatasourceUtils::listItemToObj("actor",utf8_encode($r1["name"]),$actorAttributes);
				}
				// inject the actorlist to our result (so it will act as a field later on)
				$result["actors"] = $actorList;
				
				// connect to SCEnterprise to get images
				$dbdriver = DBDriverFactory::gen();
				$dbu = $dbdriver->tablename("objects");
				
				$sql = "select * from $dbu where `type`='Image'";
				$sth = $dbdriver->query($sql);
				
				$images = array();
				while( $image = $dbdriver->fetch($sth) ) {
					$images[] = BizDatasourceUtils::imageListItemToObj("SCEnterprise Image",$image["id"],array());
				}
				$result["images"] = $images;
			}
			
			/**
			 * ATTRIBUTES EXAMPLE:
			 * If you'd like to add attributes to fields, follow this example.
			 * It is possible to add multiple attributes to fields.
			 * 
			 * NOTE: In XML a field with attributes would look like this;
			 * <Fields>
			 * 		<Field attribute="value">Value</Field>
			 * </Fields>
			 * ------
			 * PHP SNIPPET:
			 * $attributes = array();
			 * for( arguments )
			 * {
			 * 	$attributes[] = BizDatasourceUtils::attributeToObj("-attribute name-","-attribute value-");
			 * }
			 */
		
			// our array of field objects
			$fields = array();
			
			foreach( $result as $key=>&$value )
			{
				if( !is_numeric($key) && $key != "id" )
				{
					// our array of attributes (empty for this example)
					$attributes = array();
					// determine the type of value
					$valueType = BizDatasourceUtils::getValueType( $key, utf8_encode($value) );
					// fill the fields array with field objects
					$fields[] = BizDatasourceUtils::fieldToObj($key, $valueType, utf8_encode($value), $attributes, 'none', 'none' );
				}
			}
			$records[] = BizDatasourceUtils::recordToObj($result["id"],$fields);
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
						// don't handle ListValue's
						if($key == "StrValue" || $key == "IntValue")
						{
							$updateStatement .= $field->Name . "='".$value."',";
						}
					}
				}
			}
			if( $updateStatement )
			{
				$updateStatement = substr($updateStatement,0, (strlen($updateStatement)-1) );
				$sql .= " UPDATE movieTable SET ".$updateStatement." WHERE ".$recordid."='".$id."';";
			}
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
	 * @return object Query. Or null on failure.
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
