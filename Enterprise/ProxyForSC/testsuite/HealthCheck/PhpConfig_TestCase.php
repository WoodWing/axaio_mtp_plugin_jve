<?php
/**
 * HealthCheck test case for Proxy Server and Proxy Stub.
 *
 * This class is automatically read and run by BizHealthCheck class.
 *
 * @package     ProxyForSC
 * @subpackage  HealthCheck
 * @since       v1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck_PhpConfig_TestCase extends TestCase
{
	/**
	 * @var WW_Utils_TestSuite $utils
	 */
	private $utils;
	
	public function getDisplayName() { return 'Php Configuration'; }
	public function getTestGoals()   { return 'Checks if PHP is setup correctly. '; }
	public function getTestMethods() { return 'Checks if the require modules are installed, options are set, etc.'; }
	public function getPrio()        { return 1; }

	final public function runTest()
	{
		require_once BASEDIR.'/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		
		$this->testPhpVersion();
		$this->testPhpIni();
		$this->testFileRead();
		$this->testBasename();
	}
	
	private function testPhpVersion()
	{
		require_once BASEDIR.'/utils/NumberUtils.class.php';
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
	}
	
	private function testPhpIni()
	{
		$help = 'Your php.ini file is located at: <br>&nbsp;&nbsp;&nbsp;"'.$this->getPhpIni().'"<br/>';
		$help .= 'If your changes to the ini file are not reflected, try restarting the web service.';
	
		// Check mandatory PHP libraries
		// Note that zlib is needed to support Deflate compression during file uploads/downloads.
		$exts = array( 'gd', 'exif', 'sockets', 'mbstring', 'iconv', 'curl', 'zlib' );
		switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
			case 'scp':
			case 'bbcp':
				if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
					$exts[] = 'openssl';
					$exts[] = 'ssh2';
				}
			break;
		}
		$a = array();
		foreach( $exts as $ext ) {
			if( !extension_loaded($ext) ) {
				$this->setResult( 'ERROR',  "PHP library \"<b>$ext</b>\" not loaded, check php.ini.", $help );
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'PHP libraries checked.');
	
		// Check mandatory PHP settings
		if (get_cfg_var('allow_url_fopen') != '1' ) {
			$this->setResult( 'ERROR', "PHP setting <b>\"allow_url_fopen\"</b> is disabled. Please enable this option in php.ini by setting its value to \"On\".", $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'allow_url_fopen checked.');

		$numberPattern = '/^[0-9]+/';
		$result = preg_match( $numberPattern, get_cfg_var('upload_max_filesize'), $a );
		if( $result == 0 || $a[0] < 100 ){
			$this->setResult( 'ERROR', "PHP setting <b>\"upload_max_filesize\"</b> set to ".get_cfg_var('upload_max_filesize').", please increase to at least 100M in php.ini.", $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'upload_max_filesize checked.');

		$result = preg_match( $numberPattern, get_cfg_var('post_max_size'), $a );
		if( $result == 0 || $a[0] < 100 ) {
			$this->setResult( 'ERROR', "PHP setting <b>\"post_max_size\"</b> set to ".get_cfg_var('post_max_size').", please increase to at least 100M in php.ini.", $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'post_max_size checked.');

		// Check the PHP memory limit
		$memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
		// -1 means no limit
		if( $memoryLimit != -1 ) {
			if ( $memoryLimit < $this->parseMemoryLimit('100M') ) {
				$this->setResult( 'ERROR', "PHP setting <b>\"memory_limit\"</b> set to ".get_cfg_var('memory_limit').", please increase to at least 100M in php.ini.", $help );
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'memory_limit checked.');

		if ( get_cfg_var('request_order') ) {
			// If 'request_order' is defined (PHP 5.3.0 and higher) check if 'C' is set
			if ( !strstr( get_cfg_var('request_order'), 'C' ) ) {
				$this->setResult( 'ERROR', "PHP setting <b>\"request_order\"</b> set to ".get_cfg_var('request_order').", please include C in php.ini.", $help );
			}
			LogHandler::Log('wwtest', 'INFO', 'request_order checked.');	
		}
	
		// >>> BZ#20608: Since PHP 5.3, the mb_regex_encoding() seems to be changed to 'EUC-JP' causing mb functions to fail.
		// Happens only when the mbstring.internal_encoding is empty. When set, mb_regex_encoding() respects mbstring.internal_encoding.
		// Do not rely on php.ini; The mb_string library could have all kind of logics to determine defaults etc. So we call the lib functions instead!
		$intEnc = mb_internal_encoding();
		$regEnc = mb_regex_encoding();
		LogHandler::Log( 'wwtest', 'INFO', 'Checking mb_regex_encoding() / mbstring.internal_encoding: ['.$regEnc.'] / ['.$intEnc.']' );
		if( $intEnc != 'UTF-8' && $regEnc != 'UTF-8') {
			$this->setResult( 'ERROR', 'Bad multi-byte string encoding detected: '.$regEnc, 
					'Please set <b>mbstring.internal_encoding</b> to UTF-8 in php.ini.' ); 
		}
		LogHandler::Log( 'wwtest', 'INFO', 'mb_regex_encoding() / mbstring.internal_encoding checked' );
		// <<<	

		$tz = ini_get('date.timezone');
		if( !$tz ){
			$this->setResult( 'ERROR', "PHP setting <b>\"date.timezone\"</b> is not defined in php.ini. "
				."Should be set to current timezone. See <a href='http://nl3.php.net/manual/en/timezones.php' target='_blanc'>timezones</a> for supported values.", $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'date.timezone checked. Set to: ['.$tz.']');

		if( get_magic_quotes_gpc() ) {
			$this->setResult( 'ERROR', "PHP setting <b>\"magic_quotes_gpc\"</b> is enabled. Please disable this (deprecated) option in php.ini by setting its value to \"Off\".", $help );
		}
		if( get_magic_quotes_runtime() ) {
			$this->setResult( 'ERROR', "PHP setting <b>\"magic_quotes_runtime\"</b> is enabled. Please disable this (deprecated) option in php.ini by setting its value to \"Off\".", $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'magic_quotes_gpc and magic_quotes_runtime checked.');

		// Check if CURL library is loaded when running HTTPS
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') { 
			LogHandler::Log('wwtest', 'INFO', 'Using HTTPS.');
			if (!extension_loaded('curl')){
				$this->setResult( 'ERROR', "PHP library \"<b>curl</b>\" not loaded, check php.ini.", $help );
			}
		}
	
		// Check if PHP session directory exists and is writable
		$error = '';
		$oldHandler = set_error_handler(array($this, 'session_error_handler'));
		try {
			if (session_id() != ''){
				// destroy auto_start session
				session_destroy();
			}
			session_start();
		} catch (Exception $e){
			$error = $e->getMessage();
		}
		if ($oldHandler != NULL){
			set_error_handler($oldHandler);
		}
		if ($error != ''){
			$this->setResult( 'ERROR', 'Could not start PHP session. Please check your session section in php.ini and make sure that <b>"session.save_path"</b> is writable by the webserver' .
				'<br />Error details: ' . $error, $help );
		}

		// Check system temp folder write access, since that folder is used through tempnam() and tmpfile()
		// functions at several places, such as the SOAP Server's getRequestFileHandle() function.
		// When we would not check the temp folder here, the SOAP tests fails, but gives hard to explain error: "looks like we got no XML document".
		$sysTmp = sys_get_temp_dir();
		if( is_writable($sysTmp) ) {
			// Count files in system temp folder to signal potential performance issues
			$tempFiles = count( glob($sysTmp.'/*') ); 
			if( $tempFiles > 1000 ) { // let's be a bit more flexible than above
				// Windows bug: When there are 65536 files, no temp files can be created anymore!!!
				$serverity = ($tempFiles > 10000) ? 'ERROR' : 'WARN';
				$this->setResult( $serverity, 'There are many files ('.$tempFiles.') in the system temp folder ('.$sysTmp.'). This could have performance impact. Please clear the folder.' );
			}
			LogHandler::Log('wwtest', 'INFO', 'Files in system temp folder counted: '.$tempFiles );
		} else {
			$this->setResult( 'ERROR', 'The system temp folder ('.$sysTmp.') folder is not writable. Make sure the folder exists and is writable from the Webserver.' );
		}
	}

	private function testFileRead()
	{
		// create large file
		$filePath = tempnam( sys_get_temp_dir(), '' );
		$data = str_pad( '', 1024 );
		if (($fh = fopen( $filePath, 'wb' ))) {
			$filesize = 0;
			for ($i = 0; $i < 4096; $i ++) {
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
			if ( $filesize == (1024*4096) ) {
				$peakStart = memory_get_peak_usage();
				LogHandler::Log( 'wwtest', 'INFO', "Memory usage at start: " . number_format( memory_get_usage() ) . "; peak: " . number_format( $peakStart ) );
			
				if (($fh = fopen( $filePath, 'rb' ))) {
					$y = fread( $fh, $filesize );
					fclose( $fh );
				}
			
				$peakEnd = memory_get_peak_usage();
				LogHandler::Log( 'wwtest', 'INFO', "Memory usage at afterward: " . number_format( memory_get_usage() ) . "; peak: " . number_format(	$peakEnd ) );
				// do nothing with y
				$y = $y;
				LogHandler::Log( 'wwtest', 'INFO', "Memory used: " . number_format( $peakEnd - $peakStart ) . "; 1.2 x filesize: " . number_format(	1.2 * $filesize ) );
				if (($peakEnd - $peakStart) >= (1.2 * $filesize)) {
					$this->setResult( 'ERROR', 'The memory usage of the function fileread is at least 1.2 times the filesize of the file to be read. Please install an other PHP version.' );
				}
			} else {
				$this->setResult( 'ERROR', 'Could not create test file "' . $filePath . '" with ' . number_format(1024*4096) . ' but with ' . number_format(	$filesize ) . ' bytes' );
			}
			unlink( $filePath );
		} else {
			$this->setResult( 'ERROR', 'Could not perform test because the test file "' . $filePath . '" couldn\'t be created.' );
		}
		LogHandler::Log( 'wwtest', 'INFO', 'File Read checked.' );
	}

	private function testBasename()
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
	 * Parses the set memory_limit in php.ini and converts
	 * it to bytes.
	 *
	 * @param string $size
	 * @return integer
	 */
	private function parseMemoryLimit( $size )
	{
		if ( $size == -1 ) {
			return -1;
		}

		$suffixes = array(
			'' => 1,
			'k' => 1024,
			'm' => 1048576, // 1024 * 1024
			'g' => 1073741824, // 1024 * 1024 * 1024
		);
		$match = array();
		if (preg_match('/([0-9]+)\s*(k|m|g)?(b?(ytes?)?)/i', $size, $match)) {
			return $match[1] * $suffixes[strtolower($match[2])];
		}

		return 0;
	}

	/**
	 * Get the path to the php.ini file.
	 *
	 * @return string
	 */
   private function getPhpIni()
    {
        ob_start();
        phpinfo(INFO_GENERAL);
        $phpinfo = ob_get_contents();
        ob_end_clean();
		$found = array();
        return preg_match('/\(php.ini\).*<\/td><td[^>]*>([^<]+)/',$phpinfo,$found) ? $found[1] : '';
    }

    public function session_error_handler($errno, $errstr,  $errfile, $errline, $errcontext)
    {
    	// keep analyzer happy
    	$errfile = $errfile;
    	$errline = $errline;
    	$errcontext = $errcontext;
    	throw new Exception($errstr, $errno);
    }
}
