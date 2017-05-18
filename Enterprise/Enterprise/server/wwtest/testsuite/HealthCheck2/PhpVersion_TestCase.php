<?php
/**
 * PhpVersion TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package SCEnterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_PhpVersion_TestCase extends TestCase
{
	public function getDisplayName() { return 'PHP Version'; }
	public function getTestGoals()   { return 'Checks if the installed PHP version is correct. '; }
	public function getTestMethods() { return 'Retrieves the PHP version at runtime to make sure the currently running PHP instance is taken.'; }
    public function getPrio()        { return 1; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
    	$help = 'Supported PHP versions: v'.implode(', v', NumberUtils::getSupportedPhpVersions() ).'<br/>';
    	$unsupportedPhpVersions = NumberUtils::getUnsupportedPhpVersions();
    	if( $unsupportedPhpVersions ) {
	    	$help .= 'Unsupported PHP versions: v'.implode(', v', $unsupportedPhpVersions ).'<br/>';
	    }
   		$help .= 'Please make sure you have installed a supported version.';

		$phpVersion = NumberUtils::getPhpVersionNumber();
		if( !NumberUtils::isPhpVersionSupported( $phpVersion ) ) { // unsupported version
            $this->setResult( 'ERROR', 'Unsupported version of PHP installed: v'.$phpVersion, $help );
		} else {
			$customPhpVersions = defined('SCENT_CUSTOM_PHPVERSIONS') ? unserialize(SCENT_CUSTOM_PHPVERSIONS) : array();
			if( in_array($phpVersion, $customPhpVersions) ) {
				// Give a warning on custom php versions
				$this->setResult( 'WARN', 'Unsupported version of PHP installed: v'.$phpVersion, $help );
			}
		}

		LogHandler::Log('wwtest', 'INFO', 'PHP version (v'.$phpVersion.').');

		// check if the crypt() function is working for an allowed version. In PHP 5.3.0 this function is bugged for < 4 characters
		$this->runCryptTest();
		// check if xml_parser parses correctly, see BZ#11327
		$this->runXMLParserTest();
		// check if PHP doesn't use too much memory with fileread
		$this->runFileReadTest();
		// known problem with native Japanese WinXP
		$this->runBasenameCheck();
		// windows does not allow utf-8 to be set with setlocale, instead investigate what the settings are.
		$this->runEncodingCheck();
	}

	/**
	 * Check the crypt() function is working properly for less than 4 characters. In PHP 5.3.0 the crypt() function fails.
	 * Check if the SHA-512 hashing mechanism  is supported. PHP 5.3.2 and above support SHA-512 hashing.
	**/	
	protected function runCryptTest()
	{
		if( (!defined('CRYPT_SHA512') || !CRYPT_SHA512) ) {
			$this->setResult( 'FATAL', 'The crypt() function check failed, SHA-512 hash type is not supported', 'Please upgrade your PHP version to 5.3.2 or higher.' );
		}

		$user_input = 'tes';
		$password = ww_crypt('tes', null, true); // let the salt be automatically generated
		if( ww_crypt($user_input, $password) != $password ) {
			$this->setResult( 'FATAL', 'crypt() function check failed', 'Please upgrade your PHP version.' );
		}

		LogHandler::Log('wwtest', 'INFO', 'crypt() function checked.');
	}
	
	protected function runXMLParserTest()
	{
		$parser = xml_parser_create();
		$test = array();
		xml_parse_into_struct($parser, '<test>&lt;</test>', $test);
		xml_parser_free($parser);
		if (! $test || ! isset($test[0]) || ! isset($test[0]['value']) || $test[0]['value'] != '<') {
			$this->setResult( 'ERROR', 'XML is not parsed correctly', 'Recompile PHP with libexpat. See <a href="http://bugs.php.net/bug.php?id=45996">PHP Bug 45996</a> for more information' );
		}
		LogHandler::Log('wwtest', 'INFO', 'XML parser checked.');
	}
	
	protected function runFileReadTest()
	{
		// create large file
		$filePath = tempnam( sys_get_temp_dir(), '' );
		$data = str_pad( '', 1024 );
		if (($fh = fopen( $filePath, 'wb' ))) {
			$filesize = 0;
			for ($i = 0; $i < 16384; $i ++) {
				$result = fputs( $fh, $data );
				if ($result === FALSE){
					break;
				}
				$filesize += $result;
			}
			fclose( $fh );
			// on some Windows installation, you cannot get the filesize with the function filesize if you
			// don't have the permission "List Folder / Read Data"
			LogHandler::Log( 'wwtest', 'INFO', "Created " . $filePath . " with filesize = " . number_format( $filesize ) );
			if ( $filesize == (1024*16384) ) {
				$peakStart = memory_get_peak_usage();
				LogHandler::Log( 'wwtest', 'INFO', "Memory usage at start: " . number_format( memory_get_usage() ) . "; peak: " . number_format( $peakStart ) );
				
				if (($fh = fopen( $filePath, 'rb' ))) {
					$y = fread( $fh, $filesize );
					fclose( $fh );
				}
				
				$peakEnd = memory_get_peak_usage();
				LogHandler::Log( 'wwtest', 'INFO', "Memory usage at afterward: " . number_format( memory_get_usage() ) . "; peak: " . number_format(	$peakEnd ) );
				LogHandler::Log( 'wwtest', 'INFO', "Memory used: " . number_format( $peakEnd - $peakStart ) . "; 1.2 x filesize: " . number_format(	1.2 * $filesize ) );
				if (($peakEnd - $peakStart) >= (1.2 * $filesize)) {
					$this->setResult( 'ERROR', 'The memory usage of the function fileread is at least 1.2 times the filesize of the file to be read. Please install an other PHP version.' );
				}
			} else {
				$this->setResult( 'ERROR', 'Could not create test file "' . $filePath . '" with ' . number_format(1024*16384) . ' but with ' . number_format(	$filesize ) . ' bytes' );
			}
			unlink( $filePath );
		} else {
			$this->setResult( 'ERROR', 'Could not perform test because the test file "' . $filePath . '" couldn\'t be created.' );
		}
		LogHandler::Log( 'wwtest', 'INFO', 'File Read checked.' );
	}

	protected function runBasenameCheck()
	{
		// We know the following fails for (at least) native Japanese WinXP:
		$FW1 = chr( 0xEF ) . chr( 0xBC ) . chr( 0x91 ); // 'full width digit 1'
		$file = $FW1 . 'images.jpg';
		$base = basename( dirname( __FILE__ ) . '/' . $file );
		LogHandler::Log( 'wwtest', 'INFO', 'file: ' . $file . '; basename: ' . $base );
		if ($base != $file) {
			$this->setResult( 'ERROR', 'Your locale setting badly affects PHP functions. Please set locale to English.' );
		}
		
		LogHandler::Log( 'wwtest', 'INFO', 'Basename checked.' );
	}

	/**
	 * This function is a placeholder for specific encoding tests on Windows.
	 */
	protected function runEncodingCheck(){

		// setLocale is used on Windows to set the locale to 'us', please see config.php.
		// setLocale does not allow UTF-8 to be set on windows machines, this can cause all kinds of problems with underlying
		// functions. Therefore it should be tested if there are problems with UTF-8 and if present the user should
		// receive instructions how to resolve the problems.

		// Functions affected by setlocale (probably more, these are the ones i found):
		//   strcoll
		//   strtoupper
		//   strtolower
		//   ucfirst
		//   ucwords
		//   localeconv
		//   strftime

		// UTF 8 Codepage: 65001, change using the command chcp <param>. (chcp 65001)
		// No param requests the current codepage.

		// Windows allows the codepage to be set (chcp 65001), however this can only be done for the current command prompt (or in the
		// language / regional settings menu) this however is not persistent, and only valid for that command prompt.
		// In most cases the regional settings menu does not allow you to set a single encoding. Since the webserver can
		// be run in various ways this also does not fix the problem.

		// There are several encoding settings that could be explored:
		// Apache has the mod_mime module, which contains several directives that might be useful (experimental)
		// IIS can have settings for the mime types, of limited usability.
		// PHP default character encoding(s) (mb_string, default)

		//$input = '';
	    //$success = $this->hasUtf8Encoding($input);
	}
/*
	protected function hasUtf8Encoding($input){
		$regex = '%^(?:[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF]'.
			'[\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF])*$%D';

		// check if mb_strlen is available.
		if (extension_loaded('mb_string')){
			$parts = ceil( mb_strlen($input, 'UTF-8') / 2048 );
			$stepSize = 2048;
			for ( $i=1; $i < $parts+1; $i++ ) {
				$step = $stepSize * $i - $stepSize;
				if ( preg_match($regex, mb_substr($input, $step, $stepSize, 'UTF-8')) != 1 ) {
					return false;
				}
			}
			return true;
		} else {
			return false;
		}
	}
*/
}
