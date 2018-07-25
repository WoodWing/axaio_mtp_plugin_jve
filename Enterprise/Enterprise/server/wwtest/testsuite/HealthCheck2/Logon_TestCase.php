<?php
/**
 * Logon TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Logon_TestCase extends TestCase
{
	public function getDisplayName() { return 'LogOn / LogOff'; }
	public function getTestGoals()   { return 'Checks if user can logon to the application server. '; }
	public function getTestMethods() { return 'Uses a SOAP client to logon and logoff through workflow services at application server.'; }
	public function getPrio()        { return 8; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		if( !WW_Utils_UrlUtils::isResponsiveUrl( LOCALURL_ROOT.INETROOT.'/index.php?test=ping' ) ) {
			$this->setResult( 'ERROR', 'It seems to be impossible to connect to "'.LOCALURL_ROOT.'". ',
				'Please check your LOCALURL_ROOT setting at the config.php file. Make sure the server can access that URL.' );
			return;
		}		

		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		try {			
			$client = new WW_SOAP_WflClient();
			$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();

			// LogOn
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnResponse.class.php';
			$logonResp = $client->LogOn( new WflLogOnRequest( 
				// $User, $Password, $Ticket, $Server, $ClientName, $Domain,
				$suiteOpts['User'], $suiteOpts['Password'], '', '', '', '',
				//$ClientAppName, $ClientAppVersion, $ClientAppSerial, $ClientAppProductKey, $RequestTicket, $RequestInfo
				'Logon SOAP Test', 'v'.SERVERVERSION, '', '', null, null ) );
	
			// LogOff
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffResponse.class.php';
			/*$logoffResp =*/ $client->LogOff( new WflLogOffRequest( $logonResp->Ticket, false, null, null ));
			
	    	LogHandler::Log('wwtest', 'INFO', 'LogOff successful.');
		} catch( SoapFault $e ) {
			if( stripos( $e->getMessage(), '(S1053)' ) !== false ) { // wrong user/password?
				$help = 'Make sure the TESTSUITE setting at configserver.php has options named "User" and "Password". '.
					'That should be an existing user account at your server installation. '.
					'For new installations, typically the defaults "woodwing" and "ww" are used.';
			} else if( preg_match( '/(S2[0-9]{3})/is', $e->getMessage() ) > 0 ) { // S2xxx code => license error
				$help = 'See license test for more details.';
			} else {
				$help = '';
			}
			$this->setResult( 'ERROR', $e->getMessage(), $help );
		} catch( BizException $e ) {
			$help = 'Please check your LOCALURL_ROOT setting at the config.php file. Make sure the server can access that URL.';
			$this->setResult( 'ERROR', $e->getMessage(), $help );
		}
    }
}