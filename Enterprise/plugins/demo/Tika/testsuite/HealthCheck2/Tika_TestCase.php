<?php
/**
 * Tika TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since 9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Tika_TestCase extends TestCase
{
	private static $interfaceVersion = '1.0';		// Tika Server plugin interface version
	private static $minJavaVersion = 1.4;			// Minimum Java Version 1.4 to run Tika Toolkit
	private static $requiredTikaVersion = '0.7';	// Tika version

	public function getDisplayName() { return 'Tika'; }
	public function getTestGoals()   { return 'Checks if a Tika Server is configured and is accessible. '; }
	public function getTestMethods() { return 'Uses the TIKA_SERVER_URL option to connect to a Tika Server. Checks if the Tika formats (specified in the TIKA_FORMATS option) are known to Enterprise (specified in the EXTENSIONMAP option).'; }
    public function getPrio()        { return 20; }
	
	final public function runTest()
	{
		require_once dirname(__FILE__) . '/../../config.php';

		// Check TIKA_SERVER_URL option
		$help = 'Please check the TIKA_SERVER_URL option in the Tika/config.php file.';
		if( !defined('TIKA_SERVER_URL') || !($tmp = TIKA_SERVER_URL) || empty( $tmp ) ) {
			$this->setResult( 'ERROR', 'Tika Server URL is not configured.', $help );
			return;
		}

		require_once 'Zend/Uri.php';
		$validURL = Zend_Uri::check(TIKA_SERVER_URL . 'getplaintext.php');
		if( !$validURL ) {
			$this->setResult( 'ERROR', 'Tika Server URL is invalid.', $help );
			return;
		}

		if( substr(TIKA_SERVER_URL, -1, 1) != '/' ) {
			$this->setResult( 'ERROR', 'Tika Server URL must have ending slash "/".', $help );
			return;
		}

		// Check TIKA_CONNECTION_TIMEOUT option.
		$help = 'Please check the TIKA_CONNECTION_TIMEOUT option in the Tika/config.php file.';
		if( !defined('TIKA_CONNECTION_TIMEOUT') ) {
			$this->setResult( 'ERROR', 'Tika Server timeout is not configured.', $help );
			return;
		}

		// Check TIKA_FORMATS option.
		$help = 'Please check the TIKA_FORMATS option in the Tika/config.php file.';
		if(!defined('TIKA_FORMATS')) {
			$this->setResult( 'ERROR', 'There are no file formats configured for Tika.', $help );
			return;
		}
		$tikaFormats = unserialize(TIKA_FORMATS);
		if( empty($tikaFormats) ) {
			$this->setResult( 'ERROR', 'There are no file formats configured for Tika. '.
				'At least one file format is required.', $help );
			return;
		}
	
		// Check if Tika formats (TIKA_FORMATS) are also specified at Enterprise (EXTENSIONMAP)
		// IMPORATANT: The following set should match type checking done in BizMetaDataPreview::readMetaData() !
		$tikaObjTypes = array_flip(array('Article', 'Spreadsheet', 'Other', 'Image', 'Advert', 'Presentation', 'Archive'));
		$entFormats = array(); // collect formats from EXTENSIONMAP configured for any of the $tikaObjTypes
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$extMap = MimeTypeHandler::getExtensionMap();
		foreach( $extMap as /*$fileExt =>*/ $fileFormatObjType ) {
			$fileFormat = $fileFormatObjType[0];
			$objType    = $fileFormatObjType[1];
			if( isset( $tikaObjTypes[$objType] ) ) {
				$entFormats[ $fileFormat ] = true;
			}
		}
		foreach( $tikaFormats as $tikaFormat ) {
			if( !isset( $entFormats[$tikaFormat] ) ) {
				$detail = implode( ', ', array_flip($tikaObjTypes) );
				$this->setResult( 'ERROR',
					'The format "'.$tikaFormat.'" configured for Tika is not configured '.
					'at Enterprise for object types "'.$detail.'".',
					'Please check the TIKA_FORMATS option in the Tika/config.php against '.
					'the EXTENSIONMAP option in the configserver.php file.' );
				return;
			}
		}

		require_once 'Zend/Http/Client.php';
		try {
			$http = new Zend_Http_Client();
			$tikaHealthURL = TIKA_SERVER_URL . 'tikahealthtest.php';
			$http->setUri( $tikaHealthURL );
			$http->setConfig( array( 'timeout' => 20 ) );
			$response = $http->request(Zend_Http_Client::POST);
			if( !$response->isSuccessful() ) {
				self::handleHttpError( $response );
				return;
			}
		} catch( Zend_Http_Client_Exception $e ) {
			$this->setResult( 'ERROR', 'Tika error: '.$e->getMessage() );
			return;
		}

		// Perform healthtest on Tika Server
		try {
			$http = new Zend_Http_Client();
			$tikaHealthURL = TIKA_SERVER_URL . 'tikahealthtest.php';
			$http->setUri( $tikaHealthURL );
			$http->setConfig( array( 'timeout' => 120 ) );
			$http->setParameterPost(array(
				'healthTest' => 1,
				'multiByte'	=> chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2).chr(0xE6).chr(0x97).chr(0xA5).chr(0xE5).chr(0xA0).chr(0xB1))
			);
			$response = $http->request(Zend_Http_Client::POST);
			if( $response->isSuccessful() ) {
				$respBody = $response->getBody();
				$gotContentType = self::getContentType( $response );
				$expContentType = 'text/xml';
				if( $gotContentType == $expContentType ) {
					if( !$this->checkTikaServerHealthResponse( $respBody ) ) {
						return;
					}
				} else {
					$detailMsg = 'Unexpected content type "'.$gotContentType.'". Expected: "'.$expContentType.'".';
					LogHandler::Log('Tika', 'ERROR', $detailMsg .'. First 100 bytes: '. substr( $response->getBody(), 0, 100) );
					$this->setResult( 'ERROR', $detailMsg );
					return;
				}
			} else {
				$this->handleHttpError( $response );
				return;
			}
		} catch( Zend_Http_Client_Exception $e ) {
			$this->setResult( 'ERROR', 'Tika error: '.$e->getMessage() );
		}
	}

	/**
	 * Checks the Tika Server health response.
	 * 
	 * @param Zend_Http_Response $response
	 * @return boolean true when no errors found, else return false
	 */
	private function checkTikaServerHealthResponse( $responseBody )
	{
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML( $responseBody );
		$xpath = new DOMXPath( $xmlDoc );
		
		// Get and compare server plugin interface version
		$query = '/TikaServer/Interface/Version';
		$entries = $xpath->query( $query );
		$serverPluginVersion = $entries->item(0)->nodeValue;
		if ( $serverPluginVersion != self::$interfaceVersion ) { //unsupported version
			$this->setResult( 'ERROR',
				'Tika interface version not matched, current version is '. self::$interfaceVersion.  '.',
				'Please make sure you have installed a correct version.' );
			return false;
		}

		// Get and compare PHP version
		$query = '/TikaServer/PHP/Version';
		$entries = $xpath->query( $query );
		$phpVersion = $entries->item(0)->nodeValue;
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
    	$help = 'Supported PHP versions: v'.implode(', v', NumberUtils::getSupportedPhpVersions() ).'<br/>';
    	$unsupportedPhpVersions = NumberUtils::getUnsupportedPhpVersions();
    	if( $unsupportedPhpVersions ) {
	    	$help .= 'Unsupported PHP versions: v'.implode(', v', $unsupportedPhpVersions ).'<br/>';
	    }
   		$help .= 'Please make sure you have installed a supported version.';

		if( !NumberUtils::isPhpVersionSupported( $phpVersion ) ) { // unsupported version
			$this->setResult( 'ERROR', 'Unsupported version of PHP installed: v'.$phpVersion, $help );
			return false;
		} else {
			$customPhpVersions = defined('SCENT_CUSTOM_PHPVERSIONS') ? unserialize(SCENT_CUSTOM_PHPVERSIONS) : array();
			if( in_array($phpVersion, $customPhpVersions) ) {
				// Give a warning on custom php versions
				$this->setResult( 'WARN', 'Unsupported version of PHP installed: v'.$phpVersion, $help );
			}
		}

		// Get and compare the minimum Java version
		$query = '/TikaServer/Java/Version';
		$entries = $xpath->query( $query );
		$javaVersion = floatval($entries->item(0)->nodeValue);
		if( $javaVersion < self::$minJavaVersion ) {
			$this->setResult( 'ERROR',
				'Tika Server requires Java version '.self::$minJavaVersion.' and above to run.',
				'Please install the correct Java version in Tika Server.' );
			return false;
		}

		// Get and compare the Tika version
		$query = '/TikaServer/Tika/Version';
		$entries = $xpath->query( $query );
		$tikaVersion = $entries->item(0)->nodeValue;
		if( intval($tikaVersion) < intval(self::$requiredTikaVersion) ) {
			$this->setResult( 'ERROR',
				'Tika Server plug-in required Tika version ' . self::$requiredTikaVersion.' to be run on the server.',
				'Please install the correct Tika version in Tika Server.' );
			return false;
		}

		// Get and compare the tika supported file format
		/*$tikaSupportedFormat = array();
		$query = '/TikaServer/SupportedFormat/Format';
		$entries = $xpath->query( $query);
		foreach ( $entries as $supportedFormat ) {
			$tikaSupportedFormat[] = $supportedFormat->nodeValue;
		}
		$tikaConfigFormats = unserialize(TIKA_FORMATS);
		foreach( $tikaConfigFormats as $configFormat ) {
			if( !in_array($configFormat, $tikaSupportedFormat ) ) {
				$this->setResult( 'ERROR',
					'File format '.$configFormat.' doesn't support by Tika Server.',
					'Please check the TIKA_FORMATS option in the Tika/config.php.' );
				return false;
			}
		}*/

		// Get and compare the multibyte characters
		$multiByte = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2).chr(0xE6).chr(0x97).chr(0xA5).chr(0xE5).chr(0xA0).chr(0xB1);
		$query = '/TikaServer/MultiByte/Value';
		$entries = $xpath->query( $query );
		$retMultiByte = $entries->item(0)->nodeValue;
		if( $retMultiByte != $multiByte ) {
			$this->setResult( 'ERROR',
				'Multibyte response is not correct or match.',
				'Please check your HTTP server settings and make sure default encodings are set to UTF-8.' );
			return false;
		}

		// Get the write access right on the Tika Server system temp folder
		$query = '/TikaServer/SysTempFolder/Name';
		$entries = $xpath->query( $query );
		$query = '/TikaServer/SysTempFolder/Value';
		$entries2 = $xpath->query( $query );
		$writeAccess = $entries2->item(0)->nodeValue;
		if ( !$writeAccess ) { // no write access rights on the system temp folder
			$this->setResult( 'ERROR',
				'The Tika Server system temp folder '.$entries->item(0)->nodeValue. ' cannot be read.',
				'Please assign Read access to the system temp folder.' );
			return false;
		}

		return true;
	}

	/**
	 * Returns the content-type paramters of the given http response.
	 *
	 * @param Zend_Http_Response $response
	 * @return string The content type
	 */
	static private function getContentType( $response )
	{
		$responseHeaders = $response->getHeaders();
		$contentType = $responseHeaders['Content-type'];
		// Strip other params that might follow, like "charset: windows-1252"
		$chuncks = explode( ';', $contentType );
		return $chuncks[0]; 
	}

	/**
	 * Checks status and throws exception on communication errors.
	 * Assumed is that response is an error.
	 * 
	 * @param Zend_Http_Response $response
	 */
	private function handleHttpError( $response )
	{
		$respBody = $response->getBody();
		$respStatusCode = $response->getStatus();
		$respStatusText = $response->responseCodeAsText( $respStatusCode );

		if( $respStatusCode == '404' ) {
			$errMsg = 'Tika Server error: '.$respStatusText.' (HTTP '.$respStatusCode.' error).';
			$help = 'Please check the TIKA_SERVER_URL option in the Tika/config.php file.';
			LogHandler::Log( 'Tika', 'ERROR',  $respBody );
		} else {
			$errMsg = $respBody;
			$help = '';
		}
		$this->setResult( 'ERROR', $errMsg, $help );
	}
}