<?php
/**
 * HttpServerEncoding TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_HttpServerEncoding_TestCase extends TestCase
{
	public function getDisplayName() { return 'HTTP Server Encoding'; }
	public function getTestGoals()   { return 'Checks if the HTTP Server deals with Unicode text. '; }
	public function getTestMethods() { return 'Sends accented Unicode characters through HTTP and compares that with the original data read from test file.'; }
    public function getPrio()        { return 9; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/utils/UrlUtils.php';
		$testFile = dirname(__FILE__).'/HttpServerEncoding_Data.xml';
		$testUrl = WW_Utils_UrlUtils::fileToUrl( $testFile, 'server' );

		try {
			$testUri = Zend\Uri\UriFactory::factory( $testUrl );
			$isHttps = $testUri && $testUri->getScheme() == 'https';
		} catch( Exception $e ) {
			throw new BizException( null, 'Server', 'URL to download test file does not seem to be valid: '
				.$e->getMessage(), 'Configuration error' );
		}

		$httpClient = new Zend\Http\Client( $testUrl );

		if( $isHttps ) {
			$localCert = BASEDIR.'/config/encryptkeys/cacert.pem'; // for HTTPS / SSL only
			if( !file_exists($localCert) ) {
				throw new BizException( null, 'Server', null,
					'The certificate file "'.$localCert.'" does not exists.' );
			}
			// Because the Zend\Http\Client class supports SSL, but does not validate certificates / hosts / peers (yet),
			// and therefore its HTTPS connections are NOT safe! See http://www.zendframework.com/issues/browse/ZF-4838
			// For this reason, the Zend\Http\Client\Adapter\Curl adapter is passed onto
			// the Zend\Http\Client class, which enables us to set the secure options and certificate.
			$httpClient->setOptions(
				array(
					'adapter' => 'Zend\Http\Client\Adapter\Curl',
					'curloptions' => $this->getCurlOptionsForSsl( $localCert )
				)
			);
		}

		$contents = null;
		$details = null;
		try {
			$httpClient->setUri( $testUrl );
			$httpClient->setMethod( Zend\Http\Request::METHOD_GET );
			$response = $httpClient->send();
			if( $response->isSuccess() ) {
				$contents = $response->getBody();
				LogHandler::Log( __CLASS__, 'INFO',  "Download remote test file successful." );
			} else {
				$details = $response->getHeaders()->toString();
			}
		} catch( Exception $e ) {
			$details = $e->getMessage();
		}
		if( is_null($contents) ) {
			throw new BizException( null, 'Server', null, 'Could not download remote  test '.
									'file using URL: "'.$testUrl.'". '.$details );
		}
		
		$contents2 = file_get_contents( $testFile );
		if( $contents != $contents2 ){
			$this->setResult( 'ERROR', 'Test data through HTTP differs from direct file read.', 
					'Your AddDefaultCharset or your DefaultType setting is possibly incorrect.' );
		}
	}
	
	/**
	 * Returns a list of options to set to Curl to make HTTP secure (HTTPS).
	 *
	 * @param string $localCert File path to the certificate file (PEM). Required for HTTPS (SSL) connection.
	 * @return array
	 */
	private function getCurlOptionsForSsl( $localCert )
	{
		return array(
		//	CURLOPT_SSLVERSION => 2, Let php determine itself. Otherwise 'unknow SSL-protocol' error.
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_CAINFO => $localCert
		);
	}
}
