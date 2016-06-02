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
