<html>
<head>
	<title>Datasource Service - Test page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<?php 
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/interfaces/services/dat/DataClasses.php';

set_time_limit(3600);
ini_set( 'display_errors', true );

$watch = new StopWatch();
$app = new DatasourceTestApp( $watch );
$app->testLogOn();
$app->testQueryDatasources();
$app->testGetDatasource();
$app->testGetRecords();
$app->testSetRecords();
$app->testHasUpdates();
$app->testGetUpdates();
$app->testOnSave();
$app->testLogOff();
?>
</body>	
</html>

<?php
class DatasourceTestApp
{
	private $proxy = null;
	private $ticket = null;
	
	// test data to work with picked up from responses...
	private $publication = null;
	private $datasource = null;
	private $query = null;
	private $records = null;
	
	public function __construct( $watch )
	{
		$this->proxy = new DatasourceTestSoapProxy( $watch );
	}
	
	/**
	 * LogOn
	 */
	public function testLogOn()
	{
		$suiteOpts = unserialize( TESTSUITE );
		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php';
		$req = new WflLogOnRequest();
		$req->User		 	= $suiteOpts['User'];
		$req->Password 		= $suiteOpts['Password'];
		$req->ClientName 	= 'My machine IP';
		$req->ClientAppName = 'Web';
		$req->ClientAppVersion = 'v'.SERVERVERSION;
		
		$resp = $this->proxy->call( $req );
		if( is_null($resp) ) die();
		$this->publication = null;
		foreach( $resp->Publications as $pub ) {
			if( $pub->Name == $suiteOpts['Brand'] ) {
				$this->publication = $pub;
				break;
			}
		}
		if( !$this->publication ) {
			echo '<font color="red">Could not find publication "'.$suiteOpts['Brand'].'".</font><br/>'.
				'Make sure that the TESTSUITE->Brand option (at configserver.php) matches with your brand setup.<br/>';
			die(); // too essential info missing, so quit
		}
		$this->ticket = $resp->Ticket;
	}
	
	/**
	 * QueryDatasources
	 * 
	 * Retrieve a list of available datasources (by publication id)
	 */
	public function testQueryDatasources()
	{
		require_once BASEDIR.'/server/interfaces/services/dat/DatQueryDatasourcesRequest.class.php';
		$req = new DatQueryDatasourcesRequest();
		$req->Ticket = $this->ticket;
		$req->PublicationID = $this->publication->Id;
		$resp = $this->proxy->call( $req );
		// select a datasource to test with (select the first, always)
		$this->datasource = count($resp->Datasources) > 0 ? $resp->Datasources[0] : null;
		if( $this->datasource ) {
			echo 'Picked up data source "'.$this->datasource->Name.'" to test with.<br/>';
		} else {
			$this->showErrorNoDataSource();
		}
	}
	
	/**
	 * GetDatasource
	 * 
	 * Retrieve information (List of Queries) of
	 * a datasource (by datasource id)
	 */
	public function testGetDatasource()
	{
		if( !$this->datasource ) {
			echo '<hr/><font color="blue"><b>Operation: </b>GetDatasource</font><br/>';
			$this->showErrorNoDataSource();
			return; // skip
		}
		require_once BASEDIR.'/server/interfaces/services/dat/DatGetDatasourceRequest.class.php';
		$req = new DatGetDatasourceRequest();
		$req->Ticket = $this->ticket;
		$req->DatasourceID = $this->datasource->ID;
		$resp = $this->proxy->call( $req );
		$this->query = count($resp->Queries) > 0 ? $resp->Queries[0] : null;
		if( $this->query ) {
			echo 'Picked up query "'.$this->query->Name.'" to test with.<br/>';
		} else {
			$this->showErrorNoQuery( $this->datasource->Name );
		}
	}
	
	/**
	 * GetRecords
	 * 
	 * Include a datasource and execute a query by the given query id
	 * and user input (DatQueryParam)
	 */
	public function testGetRecords()
	{
		if( !$this->datasource || !$this->query ) {
			echo '<hr/><font color="blue"><b>Operation: </b>GetRecords</font><br/>';
			if( !$this->datasource ) $this->showErrorNoDataSource();
			if( !$this->query ) $this->showErrorNoQuery( $this->datasource->Name );
			return; // skip
		}
		require_once BASEDIR.'/server/interfaces/services/dat/DatGetRecordsRequest.class.php';
		$req = new DatGetRecordsRequest();
		$req->Ticket = $this->ticket;
		$req->ObjectID = '1'; // for meta data!
		$req->QueryID = $this->query->ID;
		$req->DatasourceID = $this->datasource->ID;
		$req->Params = array( new DatQueryParam( 'Title', '=', 'Once' ) );
		$resp = $this->proxy->call( $req );
		$this->records = count($resp->Records) > 0 ? $resp->Records : null;
		if( $this->records ) {
			echo 'Picked up record (with '.count($this->records[0]->Fields).' fields) to test with.<br/>';
		} else {
			$this->showErrorNoRecord( $this->datasource->Name, $this->query->Name );
		}
	}
	
	/**
	 * SetRecords
	 */
	public function testSetRecords()
	{
		if( !$this->datasource || !$this->query ) {
			echo '<hr/><font color="blue"><b>Operation: </b>SetRecords</font><br/>';
			if( !$this->datasource ) $this->showErrorNoDataSource();
			if( !$this->query ) $this->showErrorNoQuery( $this->datasource->Name );
			return; // skip
		}

		/* // >>> FOR DEBUGGING PURPOSES ONLY
		// add some test objects to the arrays
		// records we are going to send
		$arrayofrecords = array();
		$arrayoffields = array();
		$arrayofattributes = array();
		// to do a setRecords SOAP call, we 'need' some objects (to make it easier)
		require_once BASEDIR.'/server/bizclasses/BizDatasourceUtils.php';
		$arrayoffields[] = BizDatasourceUtils::fieldToObj('title','StrValue','testing!',$arrayofattributes,'changed');
		$arrayofrecords[] = BizDatasourceUtils::recordToObj('3',$arrayoffields,'changed');
		*/ // <<<
		
		require_once BASEDIR.'/server/interfaces/services/dat/DatSetRecordsRequest.class.php';
		$req = new DatSetRecordsRequest();
		$req->Ticket = $this->ticket;
		$req->QueryID = $this->query->ID;
		$req->DatasourceID = $this->datasource->ID;
		$req->Records = $this->records; // $arrayofrecords;
		/*$resp =*/ $this->proxy->call( $req );
	}
	
	/**
	 * HasUpdates
	 */
	public function testHasUpdates()
	{
		if( !$this->datasource ) {
			echo '<hr/><font color="blue"><b>Operation: </b>HasUpdates</font><br/>';
			$this->showErrorNoDataSource();
			return; // skip
		}
		require_once BASEDIR.'/server/interfaces/services/dat/DatHasUpdatesRequest.class.php';
		$req = new DatHasUpdatesRequest();
		$req->Ticket = $this->ticket;
		$req->DatasourceID = $this->datasource->ID;
		$req->FamilyValue = 'Action';
		/*$resp =*/ $this->proxy->call( $req );
	}

	/**
	 * GetUpdates
	 */
	public function testGetUpdates()
	{
		require_once BASEDIR.'/server/interfaces/services/dat/DatGetUpdatesRequest.class.php';
		$req = new DatGetUpdatesRequest();
		$req->Ticket = $this->ticket;
		$req->ObjectID = '1';
		$req->UpdateID = '1';
		/*$resp =*/ $this->proxy->call( $req );
	}
	
	/**
	 * OnSave
	 */
	public function testOnSave()
	{
		if( !$this->datasource || !$this->query ) {
			echo '<hr/><font color="blue"><b>Operation: </b>OnSave</font><br/>';
			if( !$this->datasource ) $this->showErrorNoDataSource();
			if( !$this->query ) $this->showErrorNoQuery( $this->datasource->Name );
			return; // skip
		}
		
		$pc = new DatPlacedQuery();
		$pc->QueryID = $this->query->ID;
		$pc->FamilyValues = array( 'Action' );

		$pm = new DatPlacement();
		$pm->ObjectID = '1';
		$pm->PlacedQueries = array( $pc );

		require_once BASEDIR.'/server/interfaces/services/dat/DatOnSaveRequest.class.php';
		$req = new DatOnSaveRequest();
		$req->Ticket = $this->ticket;
		$req->ObjectID = '1';
		$req->DatasourceID = $this->datasource->ID;
		$req->Placements = array( $pm );
		/*$resp =*/ $this->proxy->call( $req );
	}
	
	/**
	 * LogOff
	 */
	public function testLogOff()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
		$req = new WflLogOffRequest();
		$req->Ticket = $this->ticket;
		/*$resp =*/ $this->proxy->call( $req );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// internal helper functions that do logging at screen

	/**
	 * Displays error on screen when no data source could be found. Also some instructions how to solve.
	 */
	private function showErrorNoDataSource()
	{
		$suiteOpts = unserialize( TESTSUITE );
		echo '<font color="red"><b>ERROR: </b>Could not find any Data Source.</font><br/>';
		echo 'Please configure one at the Data Sources admin pages ';
		echo 'and make sure that Brand "'.$suiteOpts['Brand'].'" is added to it.<br/>';
	}

	/**
	 * Displays error on screen when no data source query could be found. Also some instructions how to solve.
	 *
	 * @param string $dsName Name of data source
	 */
	private function showErrorNoQuery( $dsName )
	{
		$suiteOpts = unserialize( TESTSUITE );
		echo '<font color="red"><b>ERROR: </b>Could not find any queries for Data Source '.$dsName.'.</font><br/>';
		echo 'Please configure one at the Data Sources admin pages ';
		echo 'and make sure that Brand "'.$suiteOpts['Brand'].'" is added to it.<br/>';
	}

	/**
	 * Displays error on screen when no data source query record could be found. Also some instructions how to solve.
	 *
	 * @param string $dsName Name of data source
	 * @param string $qName  Name of query
	 */
	private function showErrorNoRecord( $dsName, $qName )
	{
		$suiteOpts = unserialize( TESTSUITE );
		echo '<font color="red"><b>ERROR: </b>Could not find any records with query "'.$qName.'" for Data Source '.$dsName.'.</font><br/>';
		echo 'Please configure one at the Data Sources admin pages ';
		echo 'and make sure that Brand "'.$suiteOpts['Brand'].'" is added to it.<br/>';
	}
}

class DatasourceTestSoapProxy
{
	private $watch = null;
	private $client = null;
	private $wflClient = null;
	
	public function __construct( $watch )
	{
		require_once BASEDIR.'/server/protocols/soap/DatClient.php';
		$this->client = new WW_SOAP_DatClient();

		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		$this->wflClient = new WW_SOAP_WflClient();

		$this->watch = $watch;	
	}

	public function call( $req )
	{
		$service = substr( get_class($req), 0, 3 );
		$client = ($service == 'Wfl' ) ? $this->wflClient : $this->client;
		$operation = substr( get_class($req), 3, -strlen('Request'));
		$this->showOperation( $operation );
		$resp = null;
		$this->watch->Start();
		try {
			$resp = $client->$operation( $req );
			$this->showSuccess( $this->watch->Fetch() );
		} catch( SoapFault $e ) {
			$this->showException( $e, $this->watch->Fetch() );
		} catch( BizException $e ) {
			$this->showException( $e, $this->watch->Fetch() );
		}
		return $resp;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// internal helper functions that do logging at screen
	
	private function showOperation( $operation )
	{
		echo '<hr/><font color="blue"><b>Operation: </b>'.$operation.'</font><br/>';
	}
	
	private function showDuration( $duration )
	{
		echo '<b>Duration: </b>'.$duration.' sec<br/>';
	}

	private function showError( $error )
	{
		echo '<font color="red"><b>ERROR: </b>'.$error.'</font><br/>';
	}

	private function showSuccess( $duration )
	{
		$this->showDuration( $duration );
	}

	private function showException( $e, $duration )
	{
		$this->showDuration( $duration );
		self::showError( $e->getMessage() );
	}
}
