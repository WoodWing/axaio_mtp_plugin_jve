<?php

ini_set('display_errors', '1');
set_time_limit(3600);

require_once dirname(__FILE__).'/../../../config/config.php';

$app = new TicketTestApp();
if( $app->runTest() ) {
	echo 'Ticket test result: <font color="green">Ok!</font><br/>';
} else {
	echo 'Ticket test result: <font color="red">Failed!</font><br/>';
}

class TicketTestApp
{
	public function runTest()
	{
		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		$client = new WW_SOAP_WflClient();
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();

		$server = $_SERVER['SERVER_NAME'];
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$domain = WW_Utils_UrlUtils::getClientIP();
		$clientname = 'Server';
		$appname = 'Ticket Test';
		$appversion = 'v'.SERVERVERSION;
		$appserial = '';

		$retVal = true;
		try {
			// LogOn
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnResponse.class.php';
			$req = new WflLogOnRequest( 
					// $User, $Password, $Ticket, $Server, $ClientName, $Domain,
					$suiteOpts['User'], $suiteOpts['Password'], '', $server, $clientname, $domain,
					//$ClientAppName, $ClientAppVersion, $ClientAppSerial, $ClientAppProductKey, $RequestTicket, $RequestInfo
					$appname, $appversion, $appserial, '', null, null );
			$resp = $client->LogOn( $req );
			$ticket = $resp->Ticket;	
	
			// Check ticket at DB
			require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';		
			if( !DBTicket::DBfindticket( $suiteOpts['User'], $server, $clientname, $domain, $appname, $appversion, $appserial ) ) {
				echo '<font color="red">ERROR: Ticket not found at DB</font><br/>';
				$retVal = false;
			}

			// LogOff
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffResponse.class.php';
			$req = new WflLogOffRequest( $ticket, false, null, null );
			$resp = $client->LogOff( $req );
			
		} catch( SoapFault $e ) {
			echo '<font color="red">'.$e->getMessage().'</font><br/>';
			$retVal = false;
		}
		return $retVal;
	}
}	
?>