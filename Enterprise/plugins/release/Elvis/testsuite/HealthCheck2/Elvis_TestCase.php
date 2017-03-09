<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v<!--SERVER_VERSION-->
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Elvis_TestCase  extends TestCase
{
	// Step#01: Fill in the TestGoals, TestMethods and Prio...
	public function getDisplayName() { return 'Elvis Content Source'; }
	public function getTestGoals()   { return 'Checks the connection to Elvis'; }
	public function getTestMethods() { return ''; }
	public function getPrio()        { return 24; }

	final public function runTest()
	{
		require_once BASEDIR . '/server/utils/TestSuite.php';
		require_once dirname( __FILE__)  . '/../../config.php';
		$testSuitUtils = new WW_Utils_TestSuite();

		if( !$this->checkPhpExtensions() ) {
			return false;
		}
		if( !$this->checkOpenSslCipherMethod() ) {
			return false;
		}
		if ( !$this->checkAccess() ) {
			return false;
		}
		if ( !$this->checkAdminUser() ) {
			return false;
		}
		if ( !$this->checkSuperUser() ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the PHP extensions that are required by Elvis ContentSource plugin are installed.
	 *
	 * Note that extensions required by the core ES are assumed to be checked already, so not checked here.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @return bool Whether or not all required extensions are installed.
	 */
	private function checkPhpExtensions()
	{
		$result = true;
		$exts = array(
			'openssl' => 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/openssl-installation'
		);
		$optExtWarnings = array();
		foreach( $exts as $ext => $phpManual ) {
			if( !extension_loaded( $ext ) ) {
				$extPath = ini_get( 'extension_dir' );
				$help = 'Please see <a href="'.$phpManual.'" target="_blank">PHP manual</a> for instructions.<br/>'.
					'Note that the PHP extension path is "'.$extPath.'".<br/>'.
					'PHP compilation options can be found in <a href="phpinfo.php" target="_blank">PHP info</a>.<br/>'.
					'Your php.ini file is located at "'.$this->getPhpIni().'".';
				$msg = 'The PHP library "<b>'.$ext.'</b>" is not loaded.';
				if( isset( $optExtWarnings[ $ext ] ) ) {
					$this->setResult( 'WARN', $msg.'<br/>'.$optExtWarnings[ $ext ], $help );
				} else {
					$this->setResult( 'ERROR', $msg, $help );
					$result = false;
				}
			}
		}
		return $result;
	}

	/**
	 * Checks if the cipher method used for password encryption is supported by PHP's openssl module.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @return bool Whether or not the method is supported.
	 */
	private function checkOpenSslCipherMethod()
	{
		$result = true;
		$methods = openssl_get_cipher_methods();
		if( !in_array( 'aes-256-cbc', $methods ) ) {
			$extPath = ini_get( 'extension_dir' );
			$phpManual = 'https://redirect.woodwing.com/v1/?path=enterprise-server/php-manual/openssl-installation';
			$help = 'Please see <a href="'.$phpManual.'" target="_blank">PHP manual</a> for instructions.<br/>'.
				'Note that the PHP extension path is "'.$extPath.'".<br/>'.
				'PHP compilation options can be found in <a href="phpinfo.php" target="_blank">PHP info</a>.<br/>'.
				'Your php.ini file is located at "'.$this->getPhpIni().'".';
			$msg = 'The openssl cipher method "aes-256-cbc" is not supported.';
			$this->setResult( 'ERROR', $msg, $help );
			$result = false;
		}
		return $result;
	}

	/**
	 * Get the path to the php.ini file.
	 *
	 * @since 10.0.5 / 10.1.2
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

	/**
	 * Checks if the Elvis Server url is valid and gets version info from it. If this fails an error is.
	 *
	 * @return Connection was made, true, else false.
	 */
	private function checkAccess()
	{
		$elvisUrl = defined( 'ELVIS_URL' ) ? ELVIS_URL : '';
		$result = true;
		if ( $elvisUrl ) {
			$url = $elvisUrl.'/version.jsp';
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch,  CURLOPT_RETURNTRANSFER , 1 );
			$output = curl_exec($ch);
			curl_close($ch);
			if ( $output == false ) {
				$message = 'Could not connect to Elvis Server (url: '.$url.')';
				$help = 'Please check your configuration.';
				$this->setResult( 'Error', $message, $help);
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Checks if the configured user needed for metadata synchronisation from Elvis to Enterprise can log on to the
	 * Elvis server.
	 *
	 * @return bool Can log on, true, else false.
	 */
	private function checkAdminUser()
	{
		$user = defined( 'ELVIS_ENT_ADMIN_USER' ) ? ELVIS_ENT_ADMIN_USER : '';
		$password = defined( 'ELVIS_ENT_ADMIN_PASS' ) ? ELVIS_ENT_ADMIN_PASS : '';
		$result = true;

		if ( $user && $password ) {
			$result = $this->logOn( $user, $password );
		}

		return $result;
	}

	/**
	 * Checks if the configured user needed for creating PDF previews with InDesign Server can log on to the
	 * Elvis server.
	 *
	 * @return bool Can log on, true, else false.
	 */
	private function checkSuperUser()
	{
		$user = defined( 'ELVIS_SUPER_USER' ) ? ELVIS_SUPER_USER : '';
		$password = defined( 'ELVIS_SUPER_USER_PASS' ) ? ELVIS_ENT_ADMIN_PASS : '';
		$result = true;

		if ( $user && $password ) {
			$result = $this->logOn( $user, $password );
		}

		return $result;
	}

	/**
	 * Helper method that does the log on. User name and password are base 64 encoded.
	 *
	 * @param string $user user name.
	 * @param string $password password.
	 * @return bool Log on was successful, true, else false.
	 */
	private function logOn( $user, $password )
	{
		try {
			require_once dirname( __FILE__).'/../../util/ElvisSessionUtil.php';
			$credentials = base64_encode($user . ':' . $password);
			require_once dirname( __FILE__).'/../../logic/ElvisAMFClient.php';
			ElvisAMFClient::loginByCredentials( $credentials );
			return true;
		} catch ( BizException $e) {
			$message = 'The configured user "'.$user.'" could not log on to the Elvis server.';
			$help = 'Please check your configuration.';
			$this->setResult( 'ERROR', $message, $help );
			return false;
		}
	}
}
