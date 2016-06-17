<html>
<head>
	<title>AppSession Service - Test page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<?php 
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/appservices/DataClasses.php';

set_time_limit(3600);

$app = new AppSessionTestApp();
$app->testLogOn();
$app->testGetObjectIcons();
$app->testGetPubChannelIcons();
$app->testLogOff();

?>
</body>	
</html>

<?php

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AppSessionTestApp
{
	private $proxy = null;
	private $ticket = null;
	
	public function __construct( $watch )
	{
		$this->proxy = new AppSessionTestSoapProxy( $watch );
	}

	public function testLogOn()
	{
		$suiteOpts = unserialize( TESTSUITE );
		$req = new stdClass();
		$req->User = $suiteOpts['User'];
		$req->Password = $suiteOpts['Password'];
		$req->ClientName = 'My machine IP';
		$req->ClientAppName = 'Web'; 
		$req->ClientAppVersion = 'v'.SERVERVERSION;
		
		$resp = $this->proxy->call( 'LogOn', $req );
		if( is_null($resp) ) die();
		$this->ticket = $resp->Ticket;
	}

	public function testGetObjectIcons()
	{
		$req = new stdClass();
		$req->Ticket = $this->ticket;
		$req->IconMetrics = array( '16x16', '32x32' );
		$this->proxy->call( 'GetObjectIcons', $req );
	}

	public function testGetPubChannelIcons()
	{
		$req = new stdClass();
		$req->Ticket = $this->ticket;
		$req->IconMetrics = array( '16x16', '24x24', '32x32' );
		$this->proxy->call( 'GetPubChannelIcons', $req );
	}

	public function testLogOff()
	{
		$req = new stdClass();
		$req->Ticket = $this->ticket;
		$this->proxy->call( 'LogOff', $req );
	}

}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AppSessionTestSoapProxy
{
	private $watch = null;
	private $client = null;
	
	public function __construct()
	{
		require_once BASEDIR.'/server/protocols/soap/AppClient.php';
		$options = array( 'uri' => 'urn://www.woodwing.com/sce/AppService/AppSession' ); // select the AppSession service!
		$this->client = new WW_SOAP_AppClient( null, $options );
		
		require_once BASEDIR.'/server/utils/StopWatch.class.php';
		$this->watch = new StopWatch();
	}

	public function call( $operation, $req )
	{
		//$service = substr( get_class($req), 0, 3 );
		$this->showOperation( $operation );
		$this->watch->Start();
		try {
			$resp = $this->client->$operation( $req );
			$this->showSuccess( $this->watch->Fetch() );
		} catch( SoapFault $e ){
			$resp = null;
			$this->showException( $e, $this->watch->Fetch() );
		} catch( BizException $e ){
			$resp = null;
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
