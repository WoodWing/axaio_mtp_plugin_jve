<?php
/**
 * Transfer Server TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 * Checks the Transfer Server settings made at the configserver.php file.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_TransferServer_TestCase extends TestCase
{
	public function getDisplayName() { return 'Transfer Server'; }
	public function getTestGoals()   { return 'Checks if uploaded/downloaded files can be stored by the Transfer Server and if the server is reachable through HTTP. '; }
	public function getTestMethods() { return 'Checks if Transfer Server settings are configured correctly at the configserver.php file.'; }
	public function getPrio()        { return 25; }
	
	final public function runTest()
	{
		// Check all defines used by Transfer Server (to avoid many checks for 'defined()')
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		if( !$utils->validateDefines( $this, array('HTTP_FILE_TRANSFER_REMOTE_URL', 'HTTP_FILE_TRANSFER_LOCAL_URL'), 
									'configserver.php', 'ERROR' ) ) {
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'All the Transfer Server settings are present.');

		
		// Check syntax of Transfer Server URLs
		$help = 'Check the configserver.php file how to configure the HTTP_FILE_TRANSFER_REMOTE_URL option.';
		if( substr(HTTP_FILE_TRANSFER_REMOTE_URL, -1, 1) == '/' ) {
			$this->setResult( 'ERROR', 'The '.HTTP_FILE_TRANSFER_REMOTE_URL.' (HTTP_FILE_TRANSFER_REMOTE_URL option) has ending slash "/".', $help );
			return;
		}

		// Test the remote URL validity.
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		try {
			WW_Utils_UrlUtils::validateUrl(HTTP_FILE_TRANSFER_REMOTE_URL);
		} catch (BizException $e) {
			LogHandler::Log( 'wwtest', 'ERROR', 'The TRANSFER_SERVER_REMOTE_URL does not appear to be a valid URL: ' . $e->getMessage());
			$this->setResult( 'ERROR', 'The '.HTTP_FILE_TRANSFER_REMOTE_URL.' (HTTP_FILE_TRANSFER_REMOTE_URL option) does not appear to be a valid URL', $help );
			return;
		}

		LogHandler::Log('wwtest', 'INFO', 'The HTTP_FILE_TRANSFER_REMOTE_URL syntax is ok.');

		$help = 'Check the configserver.php file how to configure the HTTP_FILE_TRANSFER_LOCAL_URL option.';
		if( substr(HTTP_FILE_TRANSFER_LOCAL_URL, -1, 1) == '/' ) {
			$this->setResult( 'ERROR', 'The '.HTTP_FILE_TRANSFER_LOCAL_URL.' (HTTP_FILE_TRANSFER_LOCAL_URL option) has ending slash "/".', $help );
			return;
		}

		// Test the local URL validity.
		try {
			WW_Utils_UrlUtils::validateUrl(HTTP_FILE_TRANSFER_LOCAL_URL);
		} catch (BizException $e) {
			LogHandler::Log( 'wwtest', 'ERROR', 'The HTTP_FILE_TRANSFER_LOCAL_URL does not appear to be a valid URL: ' . $e->getMessage());
			$this->setResult( 'ERROR', 'The '.HTTP_FILE_TRANSFER_LOCAL_URL.' (HTTP_FILE_TRANSFER_LOCAL_URL option) does not appear to be a valid URL', $help );
			return;
		}

		LogHandler::Log('wwtest', 'INFO', 'The HTTP_FILE_TRANSFER_LOCAL_URL syntax is ok.');
		
		// Check if Transfer Server is reachable (we can check the 'local' option, but NOT the 'remote' option!)

		$httpMethods = array( 'GET', 'POST', 'PUT', 'DELETE' );
		$testUrl = HTTP_FILE_TRANSFER_LOCAL_URL;
		foreach( $httpMethods as $httpMethod ) {
			if( !WW_Utils_UrlUtils::isResponsiveUrl( $testUrl.'?test=ping', $httpMethod ) ) {
				if( $httpMethod != 'GET' ) {
					$testUrlParts = @parse_url( $testUrl );
					$limit = '<a href="http://httpd.apache.org/docs/2.0/mod/core.html#limit">&lt;Limit&gt;</a>';
					$limitExcept = '<a href="http://httpd.apache.org/docs/2.0/mod/core.html#limitexcept">&lt;LimitExcept&gt;</a>';
					$help = 'The Transfer Server index file needs to support GET, POST, PUT and DELETE methods. '.
						'However, the HTTP server seems to block access for some of those. <br/><br/>'.
						'When running <b>IIS</b>, check if WebDAV is enabled in Modules. If enabled, please navigate to ' .
						'IIS manager -> Default Web Site -> Modules. Remove WebDAVModule and restart IIS. <br/><br/>' .						
						'When running <b>Apache</b>, please check the httpd.conf file (or any .htaccess files) '.
						'and check for any '.$limit.' or '.$limitExcept.' configurations. '.
						'You might want to allow explict access to the index file like this:<br/>'.
						'&lt;Location "'.$testUrlParts['path'].'"&gt;<br/>'.
						'&nbsp;&nbsp;&nbsp;&lt;Limit GET POST PUT DELETE&gt;<br/>'.
						'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Allow from all<br/>'.
						'&nbsp;&nbsp;&nbsp;&lt;/Limit&gt;<br/>'.
						'&lt;/Location&gt;<br/>';
					// TODO: Make description for IIS explicit for the index.php, instead of all *.php files.
				}
				$this->setResult( 'ERROR', 'The Transfer Server is unreachable through "'.
					$testUrl.'" for HTTP '.$httpMethod.' method.', $help );
				return;
			}
			LogHandler::Log( 'wwtest', 'INFO', 'The HTTP_FILE_TRANSFER_LOCAL_URL is responsive for HTTP '.$httpMethod.' method.' );
		}

		// Test the FILE_TRANSFER_LOCAL_PATH, which might be on a remote machine, therefore call it through the transferindex.php.
		$testUrl = $testUrl . '?test=path';
		$status = null;
		$httpCode = null;
		require_once BASEDIR.'/server/utils/TransferClient.class.php';
		try {
			$client = new WW_Utils_TransferClient( '' );
			$client->callService($testUrl, $status, $httpCode, 'wwtest' );
		} catch (Exception $e) {
			LogHandler::Log( 'wwtest', 'ERROR', 'Could not connect to: ' . $testUrl . ' received error: ' . $e->getMessage());
			$this->setResult( 'ERROR', 'Could not connect to: ' . $testUrl, 'Please contact your system administrator.' );
			return;
		}

		// Check if we received an error for the TRANSFER_SERVER_LOCAL_PATH.
		if (200 == $httpCode){
			LogHandler::Log( 'wwtest', 'INFO', 'Succesfully validated the FILE_TRANSFER_LOCAL_PATH');
		} else {
			LogHandler::Log('wwtest', 'ERROR', 'FILE_TRANSFER_LOCAL_PATH is incorrect, received response: ' . $httpCode
				. ' - ' . $status);
			$transferServerName = parse_url(HTTP_FILE_TRANSFER_LOCAL_URL, PHP_URL_HOST);
			$this->setResult( 'ERROR', 'FILE_TRANSFER_LOCAL_PATH is not set up correctly at Server: ' . $transferServerName, $status);
		}

		// Check if handshake protocol works
		$help = '';
		$client = new WW_Utils_TransferClient( '' );
		$techDefs = $client->getTechniques();
		if( count($techDefs) == 0 ) {
			$this->setResult( 'ERROR', 'Handshake (through HTTP) with Enterprise Server failed.', $help );
			return;
		}
		
		// Validate the most preferred technique
		if( HTTP_FILE_TRANSFER_REMOTE_URL != '' ) { // HTTP
			if( $techDefs[0]['protocol'] != 'AMF' || $techDefs[0]['transfer'] != 'HTTP' ) {
				$this->setResult( 'ERROR', 'Bad handshake results. '.
					'Expected AMF/HTTP as best technique, but found '.$techDefs[0]['protocol'].'/'.$techDefs[0]['transfer'], $help );
			}
		} else { // DIME
			if( $techDefs[0]['protocol'] != 'SOAP' || $techDefs[0]['transfer'] != 'DIME' ) {
				$this->setResult( 'ERROR', 'Bad handshake results. '.
					'Expected SOAP/DIME as best technique, but found '.$techDefs[0]['protocol'].'/'.$techDefs[0]['transfer'], $help );
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'The handshake with Enterprise Server works as expected and found most preferred technique: '.$techDefs[0]['protocol'].'/'.$techDefs[0]['transfer'] );
    }
}
