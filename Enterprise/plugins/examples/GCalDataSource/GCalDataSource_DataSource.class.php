<?php


require_once BASEDIR.'/server/interfaces/plugins/connectors/DataSource_EnterpriseConnector.class.php';

require_once BASEDIR.'/config/plugins/GCalDataSource/Calendar.php';

class GCalDataSource_DataSource extends DataSource_EnterpriseConnector
{
	final public function getPrio()           { return self::PRIO_DEFAULT; }
	final public function getConnectorType()  { return 'GCal_example'; }

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
		
	//	$settings[] = array("name"=>"calendar_url","desc"=>"Calender URL ","type"=>"text"); // BZ#636
		$settings[] = array("name"=>"user","desc"=>"Google Calendar User","type"=>"text"); // BZ#636
		$settings[] = array("name"=>"pass","desc"=>"Google Calendar Password","type"=>"password"); // BZ#636
		
		return $settings;
	}
	
	
	final public function getRecords( $query, $recordid, $queryparameters )
	{
		$user = $this->Settings['user'];
		$pass = $this->Settings['pass'];
		
		//file_put_contents('/WorkSpace/Logs/q.txt', print_r($query,true));
		
		$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME;
		$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
  
	   // $gdataCal�=�new�Zend_Gdata_Calendar();
  
		$gdataCal = new Zend_Gdata_Calendar($client);
		$gquery = $gdataCal->newEventQuery();
		
		//$gquery->setQuery("Kerstdag");
		
		//$gquery->setUser('dutch__nl@holiday.calendar.google.com');
		
		$gquery->setUser('default');
		
		$gquery->setVisibility('private');
		$gquery->setProjection('full');
		$gquery->setOrderby('starttime');
		$gquery->setSortOrder('ascending');
		
		$gquery->setFutureevents(true);
		$eventFeed = $gdataCal->getCalendarEventFeed($gquery);
		
		//file_put_contents('/WorkSpace/Logs/ev.txt', print_r($eventFeed,true));
		
		$recordsresult = array();
		$cnt = 1;
		
		foreach ($eventFeed as $event) 
		{
			$fields = array();
			
		
			$fields[] = BizDatasourceUtils::fieldToObj('where', "StrValue", strval($event->where[0]), array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('who', "StrValue", strval($event->who[0]), array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('title', "StrValue", strval($event->title), array(), 'none', true, false);
						
			$parts = explode('T',$event->when[0]->startTime);
			$date  = explode('-',$parts[0]);
			
			$fields[] = BizDatasourceUtils::fieldToObj('startY', "StrValue", $startY = $date[0], array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('startM', "StrValue", $startM = $date[1], array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('startD', "StrValue", $startD = $date[2], array(), 'none', true, false);

			$date = mktime(0,0,0,$date[1], $date[2], $date[0]);
			$fields[] = BizDatasourceUtils::fieldToObj('startF', "StrValue", date('F' ,$date), array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('startl', "StrValue", date('l' ,$date), array(), 'none', true, false);


			if (sizeof($parts) > 1)
			{
				$time = split(':.',$parts[1]);
				$fields[] = BizDatasourceUtils::fieldToObj('starth', "StrValue", $time[0], array(), 'none', true, false);
				$fields[] = BizDatasourceUtils::fieldToObj('startm', "StrValue", $time[1], array(), 'none', true, false);
//				$fields[] = BizDatasourceUtils::fieldToObj('starts', "StrValue", $time[2], array(), 'none', true, false);
			}
			else
			{
				$fields[] = BizDatasourceUtils::fieldToObj('starth', "StrValue", '', array(), 'none', true, false);
				$fields[] = BizDatasourceUtils::fieldToObj('startm', "StrValue", '', array(), 'none', true, false);
//				$fields[] = BizDatasourceUtils::fieldToObj('starts', "StrValue", '', array(), 'none', true, false);
			}

			
			$parts = explode('T',$event->when[0]->endTime);
			$date  = explode('-',$parts[0]);
			
			$fields[] = BizDatasourceUtils::fieldToObj('endY', "StrValue", $endY = $date[0], array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('endM', "StrValue", $endM = $date[1], array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('endD', "StrValue", $endD = $date[2], array(), 'none', true, false);

			if (sizeof($parts) > 1)
			{
				$time = split(':.',$parts[1]);			
				$fields[] = BizDatasourceUtils::fieldToObj('endh', "StrValue", $time[0], array(), 'none', true, false);
				$fields[] = BizDatasourceUtils::fieldToObj('endm', "StrValue", $time[1], array(), 'none', true, false);
//				$fields[] = BizDatasourceUtils::fieldToObj('ends', "StrValue", $time[2], array(), 'none', true, false);
			}
			else
			{
				$fields[] = BizDatasourceUtils::fieldToObj('endh', "StrValue", '', array(), 'none', true, false);
				$fields[] = BizDatasourceUtils::fieldToObj('endm', "StrValue", '', array(), 'none', true, false);
//				$fields[] = BizDatasourceUtils::fieldToObj('ends', "StrValue", '', array(), 'none', true, false);
			}
			
		//	$fields[] = BizDatasourceUtils::fieldToObj('end', "StrValue", strval($event->when[0]->endTime), array(), 'none', true, false);
			
			
			
		//	$fields[] = BizDatasourceUtils::fieldToObj('comment', "StrValue", strval($event->getComments()), array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('content', "StrValue", strval($event->getContent()), array(), 'none', true, false);
			
			$rid = $query.'['.$cnt++.']';	
			$fields[] = BizDatasourceUtils::fieldToObj('id', "StrValue", $rid, array(), 'none', true, false);
			$fields[] = BizDatasourceUtils::fieldToObj('set', "StrValue", $user, array(), 'none', true, false);

			$recordsresult[] = BizDatasourceUtils::recordToObj($rid, $fields, 'none', 'none', false);
		}
				
		return $recordsresult;		
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
		throw new BizException( 'ERR_DATABASE', 'Server', mysql_error() );		
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
	
	
	
	
	private function http_get($url) {
		require_once 'HTTP/Request.php';
	
		$url = str_replace(' ', '+', $url);
		$rq = new HTTP_Request($url, Array());
		$result = $rq->sendRequest();
		if(PEAR::isError($result))
		return false;
		else
		return $rq->_response->_body;
	}
	
	
	
}
