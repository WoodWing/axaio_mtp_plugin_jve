<html>
<head>
	<title>Smart Catalog Enterprise - Update Notification Process Test Page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<?php 
require_once dirname(__FILE__).'/../../config/config.php';
if( !defined('TESTSUITE') ) {
	showError( 'The TESTSUITE setting was not found. '.
		'Please add the TESTSUITE setting to your configserver.php file.', 'CONFIGURATION ERROR' );
	die();
}
set_time_limit(3600);
DsUpdateTestApp::runTest();
?>

</body>	
</html>

<?php
class DsUpdateTestApp
{
	public static function runTest()
	{
		if( isset($_REQUEST['hasUpdatesInfo']) && $_REQUEST['hasUpdatesInfo'] ) {
			$testProxy = new DsUpdateTestSoapProxy();
			$suiteOpts = unserialize( TESTSUITE );
			$ticket = $testProxy->logOn( $suiteOpts['User'], $suiteOpts['Password'] );
			if( $ticket ) {
				$testProxy->hasUpdates( $ticket, $_REQUEST["DatasourceID"], $_REQUEST["FamilyValue"] );
				$testProxy->logOff( $ticket );
				print('<hr/><input type="button" value="Reset!" OnClick="javascript:window.location=\'./dsupdatetest.php\'">');
			}
		} else {
			// create interface elements
			print('<form name="updateinfo" action="./dsupdatetest.php" method="post">');
			print('<table>
					<tr><td>Datasource ID:</td><td><input type="text" name="DatasourceID"></td></tr>
					<tr><td>Family Value:</td><td><input type="text" name="FamilyValue"></td></tr>
				   </table>');
			print('<input type="submit" value="Update!" name="hasUpdatesInfo"> </form>');
		}
	}
}

class DsUpdateTestSoapProxy
{
	private $datClient = null;
	private $admClient = null;
	
	public function __construct()
	{
		require_once BASEDIR.'/server/protocols/soap/DatClient.php';
		require_once BASEDIR.'/server/protocols/soap/AdmClient.php';
	 	$this->datClient = new WW_SOAP_DatClient();
		$this->admClient = new WW_SOAP_AdmClient();
	}

	public function logOn( $user, $password )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnResponse.class.php';
		$req = new AdmLogOnRequest();
		$req->AdminUser 		= $user;
		$req->Password 			= $password;
		$req->ClientName 		= 'My machine IP';
		$req->ClientAppName 	= 'Web';
		$req->ClientAppVersion = 'v'.SERVERVERSION;
		try {
			self::showTitle( 'Log on user "'.$user.'"' );
			$resp = $this->admClient->LogOn( $req );
		} catch( SoapFault $e ){
			self::showException( $e );
		} catch( BizException $e ){
			self::showException( $e );
		}
		return isset($resp) ? $resp->Ticket : null;
	}

	public function logOff( $ticket )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffResponse.class.php';
		$req = new AdmLogOffRequest();
		$req->Ticket = $ticket;
		try {
			self::showTitle( 'Log off' );
			/*$resp =*/ $this->admClient->LogOff( $req );
		} catch( SoapFault $e ){
			self::showException( $e );
		} catch( BizException $e ){
			self::showException( $e );
		}
	}
	
	public function hasUpdates( $ticket, $dataSourceID, $familyValue )
	{
		require_once BASEDIR.'/server/interfaces/services/dat/DatHasUpdatesRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/dat/DatHasUpdatesResponse.class.php';
		$req = new DatHasUpdatesRequest();
		$req->Ticket = $ticket;
		$req->DatasourceID = $dataSourceID;
		$req->FamilyValue = $familyValue;
		try {
			self::showTitle( 'Has updates' );
			/*$resp =*/ $this->datClient->HasUpdates( $req );
		} catch( SoapFault $e ){
			self::showException( $e );
		} catch( BizException $e ){
			self::showException( $e );
		}
	}

	static public function showTitle( $caption )
	{
		echo '<hr/><font color="blue"><b>'.$caption.'</b></font><br/>';
	}
	
	static public function showError( $errStr, $caption = null )
	{
		if( $caption ) {
			echo '<h3><font color="red">'.$caption.'</font></h3>';
		}
		echo '<font color="red">'.$errStr.'</font><br/>';
	}
	
	static public function showException( $e )
	{
		self::showError( $e->getMessage() );
	}
}
