<?php
/**
 * Runs the testsuite/HealthCheck test cases and returns a HTML report.
 *
 * @package 	ProxyForSC
 * @subpackage 	BizClasses
 * @since 		v1.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizHealthCheck
{
	public function runTests()
	{
		// Start test session.
		PerformanceProfiler::startProfile( 'Health Check', 1 );

		$testCases = array();
		require_once BASEDIR.'/testsuite/HealthCheck/PhpConfig_TestCase.php';
		$testCases[] = new WW_TestSuite_HealthCheck_PhpConfig_TestCase();
		require_once BASEDIR.'/testsuite/HealthCheck/OSSetup_TestCase.php';
		$testCases[] = new WW_TestSuite_HealthCheck_OSSetup_TestCase();
		require_once BASEDIR.'/testsuite/HealthCheck/ProxyConfig_TestCase.php';
		$testCases[] = new WW_TestSuite_HealthCheck_ProxyConfig_TestCase();
		
		$testReport = array();
		foreach( $testCases as $testCase ) {
			ob_start(); // Capture std output. See ob_get_contents() below for details.
			
			// Make snapshot of the server error log.
			$errorFileOrgPath = LogHandler::getDebugErrorLogFile();
			$errorFileOrgSize = $errorFileOrgPath ? filesize($errorFileOrgPath) : 0;
			
			// Run the test case.
			$testCase->runTest();
		
			// When the script did not report any errors by itself, raise error when
			// the script has caused errors in server logging. Should be error free.
			if( !$testCase->hasError() && !$testCase->hasWarning() ) {
				$errorFileNewPath = LogHandler::getDebugErrorLogFile();
				$errorFileNewSize = $errorFileNewPath ? filesize($errorFileNewPath) : 0;
				if( $errorFileOrgPath != $errorFileNewPath || $errorFileOrgSize != $errorFileNewSize ) {
					$errorFilePath = $errorFileNewPath ? $errorFileNewPath : $errorFileOrgPath;
					$testCase->setResult( 'ERROR',
						'Script has caused errors or warnings in server logging. ',
						'Please check the ones listed in this file: '.$errorFilePath );
				}
			}

			// Combine the collected test results.
			$testReport[$testCase->getDisplayName()] = $testCase->getResults();
			
			// Check if the test did write to std output (e.g. print()). This is always wrong
			// because it should set the test results instead. Bad behavior is flagged with ERROR.
			$printed = ob_get_contents();
			ob_end_clean();
			if( strlen(trim($printed)) > 0 ) {
				$testReport[$testCase->getDisplayName()][] = new TestResult( 'ERROR', 'The script has output unofficial message:<br/>'.$printed, '' );
			}
			if( count($testReport[$testCase->getDisplayName()]) == 0) { // no results, means it all went fine... so we let client know.
				$testReport[$testCase->getDisplayName()][] = new TestResult( 'OK', '', '' );
			}
			
		}
		
		// Output the test results.
		if( PRODUCT_NAME_SHORT == 'proxyserver' ) {
			echo '<h2>Health Check - Proxy for SC</h2>';
		}
		echo '<h3>'.PRODUCT_NAME_FULL.'</h3><table border="1" cellpadding="5" style="border-collapse:collapse; border-color:#DDDDDD;">';
		foreach( $testReport as $displayName => $testResults ) {
			foreach( $testResults as $testResult ) {
				// Determine status color.
				switch( $testResult->Status ) {
					case 'OK':
						$color = 'green';
						break;
					case 'INFO':
					case 'SKIPPED':
						$color = 'blue';
						break;
					case 'NOTINSTALLED':
					case 'WARN':
						$color = '#f6a124';
						break;
					default: // FATAL, ERROR, other
						$color = 'red';
						break;
				}			
			
				// Output the test result.
				$message = $testResult->Message;
				if( $testResult->ConfigTip ) {
					$message .= '<br/><b>Tip: </b>'.$testResult->ConfigTip;
				}
				$status = '<font color="'.$color.'">'.$testResult->Status.'</font>';
				echo '<tr><td style="vertical-align: top;">'.$displayName.'</td>'.
						'<td style="vertical-align: top;">'.$message.'</td>'.
						'<td style="vertical-align: top;">'.$status.'</td></tr>';
			}
		}
		echo '</table>';
		
		// Output the test results returned by the Proxy Stub
		// Run the HealtCheck page of the Proxy Stub
		if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
			echo $this->runHealthCheckAtProxyStub();
		}
		PerformanceProfiler::stopProfile( 'Health Check', 1 );
	}

	/**
	 * Proxy Server invokes the HealthCheck page at the Proxy Stub machine.
	 */
	private function runHealthCheckAtProxyStub()
	{
		try {
			require_once 'Zend/Http/Client.php';
			$httpClient = new Zend_Http_Client( PROXYSTUB_URL.'proxystub/apps/healthcheck.php'.
				'?protocol='.urlencode(ENTERPRISEPROXY_TRANSFER_PROTOCOL).
				'&transferpath='.urlencode(PROXYSTUB_TRANSFER_PATH).
				'&version='.urlencode(PRODUCT_VERSION)
			);
			$response = $httpClient->request( 'GET' );
			if( $response->isSuccessful() ) {
				$message = $response->getBody();
			} else {
				$message = 'ERROR: URL does not seems to be responsive: '
					.'HTTP/'.$response->getVersion().' '.$response->getStatus().' '.$response->getMessage();
			}
		} catch( Zend_Http_Client_Exception $e ) {
			$message = 'ERROR: URL does not seems to be responsive: '.$e->getMessage();
		}
		return $message;
	}
}