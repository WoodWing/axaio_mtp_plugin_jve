<?php
/**
 * PhpIni TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_PhpIni_TestCase extends TestCase
{
	public function getDisplayName() { return 'php.ini'; }
	public function getTestGoals()   { return 'Checks if the php.ini settings are correct. '; }
	public function getTestMethods() { return 'Various PHP settings are checked that are crucial for Enterprise Server. Also checked is if required PHP extentions are installed and loaded.'; }
    public function getPrio()        { return 3; }

    private $phpVersion = null;      // Php version

	final public function runTest()
	{
		$help = 'Your php.ini file is located at: <br>&nbsp;&nbsp;&nbsp;"'.$this->getPhpIni().'"<br/>';
		$help .= 'If your changes to the ini file are not reflected, try restarting the web service.';

		// Note: Database PHP extensions are checked in WW_TestSuite_HealthCheck2_DatabaseConnection_TestCase.

		// Get installed PHP version
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$this->phpVersion = NumberUtils::getPhpVersionNumber();

		// Check PHP extensions
		$this->checkPhpExtensions();

		// Check mandatory PHP settings
		$this->checkMandatoryPhpSettings( $help );

		// Check PHP session directory
		$this->checkPhpSessionDirectory( $help );

		// Check system temp directory
		$this->checkSystemTempDirectory();

		// Check Zend OPcache extension
		$this->checkOpcacheExtension();
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
	   phpinfo( INFO_GENERAL );
	   $phpinfo = ob_get_contents();
	   ob_end_clean();
	   $found = array();
	   return preg_match( '/\(php.ini\).*<\/td><td[^>]*>([^<]+)/', $phpinfo, $found ) ? trim( $found[1] ) : '';
   }

    /**
     * Session error handler
     *
     * @param string $errno
     * @param string $errstr
     * @param $errfile
     * @param $errline
     * @param $errcontext
     * @throws Exception
     */
    public function session_error_handler( $errno, $errstr,  $errfile, $errline, $errcontext )
    {
        /** @noinspection PhpSillyAssignmentInspection */ $errfile = $errfile; // keep analyzer happy
        /** @noinspection PhpSillyAssignmentInspection */ $errline = $errline;
        /** @noinspection PhpSillyAssignmentInspection */ $errcontext = $errcontext;

    	throw new Exception( $errstr, $errno );
    }

	/**
	 * Check if mandatory extension libraries is loaded in PHP
	 */
	private function checkPhpExtensions()
	{
		// Mandatory PHP extensions for which an ERROR should raise.
		$exts = array(
			'gd' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/image-installation',
			'exif' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/exif-installation',
			'sockets' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/sockets-installation',
			'mbstring' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/mbstring-installation',
			'soap' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/soap-installation',
			'iconv' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/iconv-installation',
			'curl' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/curl-installation',
			'zlib' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/zlib-installation',
				// L> Note that zlib is needed since Enterprise 9.5 to support Deflate compression
				//    during file uploads/downloads through Transfer Server.
			'xsl' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/xsl-installation'
		);

		// Optional PHP extentions for which a WARNing should raise instead.
		$optExtWarnings = array(
			'xsl' => 'This will break HTML5 exports from ContentStation'
		);

		if( defined('ENCRYPTION_PRIVATEKEY_PATH') ) {
			LogHandler::Log('wwtest', 'INFO', 'Using password encryption which requires PHP "openssl" library.');
			$exts['openssl'] = 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/openssl-installation';
		}

		// Note that since Enterprise 10.0 RabbitMQ AMQP requires the bcmatch library.
		// For example used in: php-amqplib/PhpAmqpLib/Wire/AMQPWriter.php.
		require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
		if( BizMessageQueue::isInstalled()) {
			LogHandler::Log('wwtest', 'INFO', 'Using RabbitMQ which requires PHP "bcmath" library.');
			$exts['bcmath'] = 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/bcmath-installation';
		}

		foreach( $exts as $ext => $phpManual ) {
			if( !extension_loaded($ext) ) {
				$extPath = ini_get('extension_dir');
				$help = 'Please see <a href="'.$phpManual.'" target="_blank">PHP manual</a> for instructions.<br/>'.
					'Note that the PHP extension path is "'.$extPath.'".<br/>'.
					'PHP compilation options can be found in <a href="phpinfo.php" target="_blank">PHP info</a>.<br/>'.
					'Your php.ini file is located at "'.$this->getPhpIni().'".';
				$msg = 'The PHP library "<b>'.$ext.'</b>" is not loaded.';
				if( isset($optExtWarnings[$ext]) ) {
					$this->setResult( 'WARN', $msg.'<br/>'.$optExtWarnings[$ext], $help );
				} else {
					$this->setResult( 'ERROR', $msg, $help );
				}
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'PHP libraries checked.');
	}

	/**
	 * Check if mandatory PHP Settings is set
	 *
	 * @param string $help Help message
	 */
	private function checkMandatoryPhpSettings( $help )
	{
		$a = array();
		if( get_cfg_var('allow_url_fopen') != '1' ) {
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
		if( $result == 0 || $a[0] < 100 ){
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

		$this->checkPhpEncodingOptions();

		$tz = ini_get('date.timezone');
		if( !$tz ){
			$this->setResult( 'ERROR', "PHP setting <b>\"date.timezone\"</b> is not defined in php.ini. "
				."Should be set to current timezone. See <a href='http://nl3.php.net/manual/en/timezones.php' target='_blanc'>timezones</a> for supported values.", $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'date.timezone checked. Set to: ['.$tz.']');

        if( version_compare($this->phpVersion, '5.6.0') >= 0 && ini_get('always_populate_raw_post_data') != -1 ) {
            $this->setResult( 'ERROR', "PHP setting <b>\"always_populate_raw_post_data\"</b> is enabled. Please disable this (deprecated) option in php.ini by setting its value to \"-1\".", $help );
        }
		LogHandler::Log('wwtest', 'INFO', 'always_populate_raw_post_data checked.');

		// BZ#11308
		// Check if session.auto_start is on. If it's on, then BizSession doesn't start a session
		// and BizResources won't cache the resource table => performance impact
		// ini_get('session.auto_start') will return 0 or empty string for no autostart or 1 for autostart
		if( ini_get('session.auto_start') ) {
			$this->setResult( 'WARN', 'Session start on request is on. This will affect the Enterprise performance. ' .
				'If you only run Enterprise on this server, check your session section in php.ini and make sure that <b>"session.auto_start"</b> is set to 0', $help);
		}
		LogHandler::Log('wwtest', 'INFO', 'session.auto_start checked.');
	}

	/**
	 * Check if PHP session directory exists and is writable
	 *
	 * @param string $help Help message
	 */
	private function checkPhpSessionDirectory( $help )
	{
		$error = '';
		$oldHandler = set_error_handler( array($this, 'session_error_handler') );
		try {
			if( session_id() != '' ) {
				// destroy auto_start session
				session_destroy();
			}
			session_start();
		} catch( Exception $e ) {
			$error = $e->getMessage();
		}
		if( $oldHandler != NULL ) {
			set_error_handler($oldHandler);
		}
		if( $error != '' ) {
			$this->setResult( 'ERROR', 'Could not start PHP session. Please check your session section in php.ini and make sure that <b>"session.save_path"</b> is writable by the webserver' .
				'<br />Error details: ' . $error, $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'PHP session directory checked.');
	}

	/**
	 * Check if system temp directory is writable
	 *
	 */
	private function checkSystemTempDirectory()
	{
		// Check system temp folder write access, since that folder is used through tempnam() and tmpfile()
		// functions at several places, such as the SOAP Server's getRequestFileHandle() function.
		// When we would not check the temp folder here, the SOAP tests fails, but gives hard to explain error: "looks like we got no XML document".
		$sysTmp = sys_get_temp_dir();
		if( is_writable($sysTmp) ) {
			// Count files in system temp folder to signal potential performance issues
			$tempFiles = count( glob($sysTmp.'/*') );
			if( $tempFiles > 1000 ) { // let's be a bit more flexible than above
				// Windows bug: When there are 65536 files, no temp files can be created anymore!!!
				$severity = ($tempFiles > 10000) ? 'ERROR' : 'WARN';
				$this->setResult( $severity, 'There are many files ('.$tempFiles.') in the system temp folder ('.$sysTmp.'). This could have performance impact. Please clear the folder.' );
			}
			LogHandler::Log('wwtest', 'INFO', 'Files in system temp folder counted: '.$tempFiles );
		} else {
			$this->setResult( 'ERROR', 'The system temp folder ('.$sysTmp.') folder is not writable. Make sure the folder exists and is writable from the Webserver.' );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'System temp directory checked.');
	}

	/**
	 * Checks if Zend OPcache extension is loaded
	 *
	 */
	private function checkOpcacheExtension()
	{
		if( OS != 'WIN' ) { // For Windows there are no prebuild packages yet. Do not check on that platform.
			require_once BASEDIR . '/server/utils/ZendOpcache.php';
			$isInstalled = WW_Utils_ZendOpcache::isOpcacheInstalled();
			if( !$isInstalled ) {
				$help = 'See <a href="https://redirect.woodwing.com/v1/?path=enterprise-server/'.ONLINEHELP_SERVER_MAJOR_VERSION.'/help/zend-opcache" '.
					'target="_blank">Enterprise Help</a> for instructions.';
				$this->setResult( 'WARN',  "Zend OPcache is not installed.", $help );
			}
			LogHandler::Log('wwtest', 'INFO', 'Zend OPcache checked.');
		}
	}

	/**
	 * Check if PHP character encoding options are set correctly
	 *
	 */
	private function checkPhpEncodingOptions()
	{
		// From PHP 5.6 onwards, the character encoding option precedence is: default_charset < internal_encoding < mbstring.internal_encoding
		// The checking belows will follow the sequence of the precedence.
		$badEncoding = false;
		if( version_compare($this->phpVersion, '5.6.0') >= 0 ) {
			// Check all options if it is set with "UTF-8" encoding
			$settings = array(
				'default_charset',
				'internal_encoding',
				'input_encoding',
				'output_encoding'
			);
			foreach( $settings as $setting ) {
				$value = ini_get( $setting );
				if( !empty($value) && $value != 'UTF-8' ) {
					$this->setResult('ERROR', 'Bad character encoding detected: '. $value .'.',
						'Please set <b>'.$setting.'</b> to UTF-8 in php.ini.');
					$badEncoding = true;
				}
			}
			LogHandler::Log( 'wwtest', 'INFO', 'PHP character encoding options checked' );

			// Check all deprecated options if it is set with "UTF-8" encoding
			$settings = array(
				// deprecated             => use instead
				'mbstring.internal_encoding' => 'internal_encoding',
				'mbstring.http_input'     => 'input_encoding',
				'mbstring.http_output'    => 'output_encoding',
			);
			foreach( $settings as $deprecated => $useInstead ) {
				$value = ini_get( $deprecated );
				if( !empty($value) && $value != 'UTF-8' ) {
					$this->setResult('ERROR', 'Deprecated encoding option detected: '. $deprecated .'.',
						'Please use the <b>'.$useInstead.'</b> option instead in php.ini.');
					$badEncoding = true;
				}
			}
			LogHandler::Log( 'wwtest', 'INFO', 'PHP deprecated character encoding options checked' );
		}

		// >>> BZ#20608: Since PHP 5.3, the mb_regex_encoding() seems to be changed to 'EUC-JP' causing mb functions to fail.
		// Happens only when the mbstring.internal_encoding is empty. When set, mb_regex_encoding() respects mbstring.internal_encoding.
		// Do not rely on php.ini; The mb_string library could have all kind of logics to determine defaults etc. So we call the lib functions instead!
		// For PHP 5.6, although the option mb_string.internal_encoding is deprecated, checking is still needed, this is due to when
		// mb_string.internal_encoding is empty, mb_internal_encoding and mb_regex_encoding will return "UTF-8", it looks like PHP bug,
		// it didn't respect default_charset or global internal_encoding option according to the character encoding options precedence.
		// @TODO: When the PHP bug fixed, the checking should remove for PHP 5.6
		if( function_exists('mb_internal_encoding') ) {
			$intEnc = mb_internal_encoding();
			$regEnc = mb_regex_encoding();
			LogHandler::Log( 'wwtest', 'INFO', 'Checking mb_regex_encoding() / mbstring.internal_encoding: ['.$regEnc.'] / ['.$intEnc.']' );
			if( $intEnc != 'UTF-8' && $regEnc != 'UTF-8') {
				$this->setResult( 'ERROR', 'Bad multi-byte string encoding detected: '.$regEnc,
					'Please set <b>mbstring.internal_encoding</b> to UTF-8 in php.ini.' );
				$badEncoding = true;
			}
			LogHandler::Log( 'wwtest', 'INFO', 'mb_regex_encoding() / mbstring.internal_encoding checked' );
		}

		// When all PHP encoding options above are checked with valid value, then only perform the last checking to check if
		// functions mb_strlen are working fine with 3 bytes chinese character that exists in "UTF-8" encoding.
		// This is to ensure that the correct "UTF-8" encoding option is set.
		if( !$badEncoding ) {
			$chineseChar = chr(0xEF).chr(0xA5).chr(0x89);
			if( mb_strlen( $chineseChar ) !== 1 ) {
				$this->setResult( 'ERROR', 'Most common character encoding options are checked but the mb_strlen() function has failed.',
					'Please set encoding options to UTF-8 in php.ini.' );
			}
		}
	}
}
