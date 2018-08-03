<?php
/**
 * "OpenCalais" TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since       v9.1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_OpenCalais_TestCase extends TestCase
{
	public function getDisplayName() { return 'OpenCalais'; }
	public function getTestGoals()   { return 'Checks if the API configuration is correct and the service is reachable.'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 24; }

	final public function runTest()
	{
		// Validate that the API Key was stored in the database.
		require_once BASEDIR . '/server/plugins/OpenCalais/OpenCalais.class.php';
		$key = OpenCalais::getApiKey();
		if( empty( $key ) ) {
			$url = SERVERURL_ROOT.INETROOT.'/server/admin/webappindex.php?webappid=Configuration&plugintype=server&pluginname=OpenCalais';
			$help = 'Please set the API key on the <a href="' . $url . '">OpenCalais Maintenance page</a>.';
			$this->setResult( 'ERROR', 'The API key for OpenCalais is not set.', $help );
		} else {
			// Attempt to connect using the API key, to see if it is valid.
			$content = 'The Calais web service automatically attaches rich semantic metadata to the content you submit.'
				. 'Using natural language processing, machine learning and other methods, Calais categorizes and links '
				. 'your document with entities (people, places, organizations, etc.), facts (person "x" works for '
				. 'company "y"), and events (person "z" was appointed chairman of company "y" on date "x").';

			$curlError = null;
			if( !$this->connectionCheck( $curlError ) ) {
				$this->handleCurlError( OpenCalais::getUrl(), $curlError );
				return;
			}

			// Normally we call the webservices when running a request, but in this case call the OpenCalais plugin
			// Directly.
			$content = mb_substr( $content, 0, 99999, 'UTF-8' );
			$response = OpenCalais::suggest( $content, array(
				'SociaLTags' => 'socialtags', 
				'IndustryTerm' => 'industryterm'
			));

			// Check if there was a valid response.
			if( count( $response ) == 0 ) {
				$help = 'Please see the error logging for more details.';
				$this->setResult( 'ERROR', 'The OpenCalais request could not be sent.', $help );
			}
		}
	}

	/**
	 * Checks whether or not the OpenCalais URL can be used to reach the OpenCalais web service.
	 *
	 * @param integer $curlErrNr
	 * @return bool
	 */
	private function connectionCheck( &$curlErrNr )
	{
		$httpClient = OpenCalais::httpClient();
		try {
			$httpClient->request();
		} catch ( Exception $e ) {
			$adapter = $httpClient->getAdapter();
			if ( $adapter instanceof Zend_Http_Client_Adapter_Curl ) {
				$curl = $adapter->getHandle();
				$curlErrNr = curl_errno($curl);
			}
			return false;
		}
		return true;
	}

	/**
	 * Reports a cURL connection error.
	 *
	 * @param string $url The connection URL being tested.
	 * @param integer $curlErrNr cURL error number
	 */
	private function handleCurlError( $url, $curlErrNr )
	{
		$sslErrors = array(
			CURLE_SSL_CONNECT_ERROR,
			CURLE_SSL_ENGINE_NOTFOUND,
			CURLE_SSL_ENGINE_SETFAILED,
			CURLE_SSL_CERTPROBLEM,
			CURLE_SSL_CACERT,
			77 // CURLE_SSL_CACERT_BADFILE - Not in the PHP defined constants, but is returned by cURL
		);
		if ( in_array($curlErrNr, $sslErrors) ) {
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$testFile = BASEDIR.'/server/plugins/OpenCalais/testsuite/HealthCheck2/caCertificates.php';
			$wwtestUrl = WW_Utils_UrlUtils::fileToUrl( $testFile, 'server', false );
			$help = ' Please run the <a href="'.$wwtestUrl.'" target="_blank">Get CA Certificates</a> page that will download the CA certificates.';
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. There seems to be a SSL certificate problem.', $help  );
			return;
		}

		$channelHelp = 'If you use a proxy server please check the ENTERPRISE_PROXY option in your configserver.php file.';

		// Test of the peer certificate failed. This usually happens when you use the ip address instead of the URL.
		if ( $curlErrNr == CURLE_SSL_PEER_CERTIFICATE ) {
			$help = ' Make sure you use the correct URL, IP addresses are not allowed. '.$channelHelp;
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. There seems to be a SSL problem with the server certificate.', $help  );
			return;
		}

		if ( $curlErrNr == CURLE_UNSUPPORTED_PROTOCOL ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The protocol is not supported by cURL.', $channelHelp  );
			return;
		}

		if ( $curlErrNr == CURLE_URL_MALFORMAT ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The URL is malformed.', $channelHelp  );
			return;
		}

		if ( $curlErrNr == 4 ) { // cURL error CURLE_NOT_BUILT_IN constant is not defined, but is a valid error, hence using the number.
			$help = 'cURL could not find the feature that is requested. '.$channelHelp;
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'.', $help  );
			return;
		}

		if ( $curlErrNr == CURLE_COULDNT_RESOLVE_PROXY ) {
			$help = 'Please check the ENTERPRISE_PROXY setting in the configserver.php file. ';
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The proxy could not be resolved.', $channelHelp  );
			return;
		}

		if ( $curlErrNr == CURLE_COULDNT_RESOLVE_HOST ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The host could not be resolved.', $channelHelp  );
			return;
		}

		if ( $curlErrNr == CURLE_COULDNT_CONNECT ) {
			$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'.', $channelHelp  );
			return;
		}

		$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. Unknown cURL error: '.$curlErrNr, $channelHelp  );
	}
}

