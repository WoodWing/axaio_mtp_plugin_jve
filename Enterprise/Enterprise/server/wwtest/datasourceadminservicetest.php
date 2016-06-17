<html>
<head>
	<title>Datasource Admin Service - Test page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<?php 
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/interfaces/services/ads/DataClasses.php';

set_time_limit(3600);

$watch = new StopWatch();
$app = new DatasourceAdminTestApp( $watch );
$app->testLogOn();
$app->testGetPublications();
$app->testQueryDatasources();
$app->testGetDatasource();
$app->testGetQuery();
$app->testGetDatasourceTypes();
$app->testSaveQuery();
$app->testCopyQuery();
$app->testDeleteQuery();
$app->testSaveDatasource();
$app->testLogOff();
?>
</body>	
</html>

<?php
class DatasourceAdminTestApp
{
	private $proxy = null;
	private $ticket = null;
	
	public function __construct( $watch )
	{
		$this->proxy = new DatasourceAdminTestSoapProxy( $watch );
	}
	
	/**
	 * LogOn
	 */
	public function testLogOn()
	{
		$suiteOpts = unserialize( TESTSUITE );
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
		$req = new AdmLogOnRequest();
		$req->AdminUser 	= $suiteOpts['User'];
		$req->Password 		= $suiteOpts['Password'];
		$req->ClientName 	= 'My machine IP';
		$req->ClientAppName = 'Web';
		$req->ClientAppVersion = 'v'.SERVERVERSION;
		
		$resp = $this->proxy->call( $req ); // todo: validate response
		if( is_null($resp) ) die();
		$this->ticket = $resp->Ticket;
	}

	/**
	 * GetPublications
	 */
	public function testGetPublications()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsGetPublicationsRequest.class.php';
		$req = new AdsGetPublicationsRequest();
		$req->Ticket = $this->ticket;
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * QueryDatasources
	 */
	public function testQueryDatasources()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsQueryDatasourcesRequest.class.php';
		$req = new AdsQueryDatasourcesRequest();
		$req->Ticket = $this->ticket;
		$req->PublicationID = '1';
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * GetDatasource
	 */
	public function testGetDatasource()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceRequest.class.php';
		$req = new AdsGetDatasourceRequest();
		$req->Ticket = $this->ticket;
		$req->DatasourceID = "1";
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * GetQuery
	 */
	public function testGetQuery()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsGetQueryRequest.class.php';
		$req = new AdsGetQueryRequest();
		$req->Ticket = $this->ticket;
		$req->QueryID = "1";
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * GetDatasourceTypes
	 */
	public function testGetDatasourceTypes()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsGetDatasourceTypesRequest.class.php';
		$req = new AdsGetDatasourceTypesRequest();
		$req->Ticket = $this->ticket;
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * SaveQuery
	 */
	public function testSaveQuery()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveQueryRequest.class.php';
		$req = new AdsSaveQueryRequest();
		$req->Ticket = $this->ticket;
		$req->QueryID		=	'3';
		$req->Name			=	'UPDATED Test Query #1';
		$req->Query			=	'select * from movieTable';
		$req->Interface		=	'Test,string';
		$req->Comment		=	'No comments..';
		$req->RecordID		=	'id';
		$req->RecordFamily	=	'genre';
		
		/*$field1->Action			=	'update';
		$field1->ID				=	'1';
		$field1->Name			=	'title';
		$field1->Priority		=	'0';
		$field1->ReadOnly		=	'1';
		
		$field2->Action			=	'update';
		$field2->ID				=	'10';
		$field2->Name			=	'time';
		$field2->Priority		=	'1';
		$field2->ReadOnly		=	'1';
		$params->Fields = array( $field1, $field2 );*/
		
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * CopyQuery
	 */
	public function testCopyQuery()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsCopyQueryRequest.class.php';
		$req = new AdsCopyQueryRequest();
		$req->Ticket = $this->ticket;
		$req->QueryID = '3';				// id of the query to copy
		$req->TargetID = '32';				// the id of the datasource to copy the query to
		$req->NewName = 'Copied Query #'.substr(md5(microtime()),0,6);	// new query name
		$req->CopyFields = ''; // ?	
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * DeleteQuery
	 */
	public function testDeleteQuery()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsDeleteQueryRequest.class.php';
		$req = new AdsDeleteQueryRequest();
		$req->Ticket = $this->ticket;
		$req->QueryID = '3';
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}

	/**
	 * SaveDatasource
	 */
	public function testSaveDatasource()
	{
		require_once BASEDIR.'/server/interfaces/services/ads/AdsSaveDatasourceRequest.class.php';
		$req = new AdsSaveDatasourceRequest();
		$req->Ticket = $this->ticket;
		$req->DatasourceID = '1';
		$req->Name = 'Datasource #'.substr(md5(microtime()),0,6);
		$req->Bidirectional = '0';
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}
	
	/**
	 * LogOff
	 */
	public function testLogOff()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
		$req = new AdmLogOffRequest();
		$req->Ticket = $this->ticket;
		/*$resp =*/ $this->proxy->call( $req ); // todo: validate response
	}
}

class DatasourceAdminTestSoapProxy
{
	private $watch = null;
	private $client = null;
	private $admClient = null;
	
	public function __construct( $watch )
	{
		require_once BASEDIR.'/server/protocols/soap/AdsClient.php';
		$this->client = new WW_SOAP_AdsClient();

		require_once BASEDIR.'/server/protocols/soap/AdmClient.php';
		$this->admClient = new WW_SOAP_AdmClient();

		$this->watch = $watch;	
	}

	public function __destruct()
	{
		$this->watch = null;	
		$this->client = null;
		$this->admClient = null;
	}

	public function call( $req )
	{
		$service = substr( get_class($req), 0, 3 );
		$client = ($service == 'Adm' ) ? $this->admClient : $this->client;
		$operation = substr( get_class($req), 3, -strlen('Request'));
		$this->showOperation( $operation );
		$resp = null;
		$this->watch->Start();
		try {
			$resp = $client->$operation( $req );
			$this->showSuccess( $this->watch->Fetch() );
		} catch( SoapFault $e ){
			$this->showException( $e, $this->watch->Fetch() );
		} catch( BizException $e ){
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
