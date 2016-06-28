<?php
/**
 * HealthCheck test case for Proxy Server and Proxy Stub.
 *
 * This class is automatically read and run by BizHealthCheck class.
 *
 * @package     ProxyForSC
 * @subpackage  HealthCheck
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck_ProxyConfig_TestCase extends TestCase
{
	/**
	 * @var WW_Utils_TestSuite $utils
	 */
	private $utils;
	
	public function getDisplayName() { return 'Proxy Configuration'; }
	public function getTestGoals()   { return 'Checks if the settings in the config.php file are correct. '; }
	public function getTestMethods() { return 'Checks if file paths options have proper slashes and exist on disk, etc, etc.'; }
	public function getPrio()        { return 2; }

	final public function runTest()
	{
		require_once BASEDIR.'/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		do {
			// Validate the BASEDIR option.
			if( !$this->testBaseDir() ) {
				break;
			}
			// Validate the DEBUGLEVELS, PROFILELEVEL and LOGFILE_FORMAT options.
			if( !$this->testLoggingOptions() ) {
				break;
			}
			// Validate the OUTPUTDIRECTORY option.
			if( !$this->testOutputDirectory() ) { 
				break;
			}
			// Check if the version of proxy and stub are matching.
			if( PRODUCT_NAME_SHORT == 'proxystub' && // only when running as proxy stub
				array_key_exists( 'version',  $_GET ) ) { // called by proxy server?
				if( $_GET['version'] != PRODUCT_VERSION ) {
					$help = 'Please install the same version at both machines.';
					$this->setResult('ERROR', 
						'The Proxy Server version '.$_GET['version'].' does not match '.
						'with the Proxy Stub version '.PRODUCT_VERSION.'. ', $help );
					break;
				}
			}
			// Validate the PROXYSTUB_URL option.
			if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
				if( !$this->testProxyStubUrl() ) {
					break;
				}
			}
			// Validate the ENTERPRISE_URL option.
			if( !$this->testEnterpriseUrl() ) {
				break;
			}
			// Validate the PROXYSERVER_TRANSFER_PATH option.
			if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
				if( !$this->testProxyServerTransferPath() ) {
					break;
				}
			}
			// Validate the PROXYSTUB_TRANSFER_PATH option.
			if( !$this->testProxyStubTransferPath() ) {
				break;
			}
			// Validate the ENTERPRISEPROXY_TRANSFER_PROTOCOL option.
			if( !$this->testEnterpriseProxyTransferProtocol() ) {
				break;
			}
			// Validate file access to transfer folders over SSH.
			if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
				if( !$this->testTransferFolderAccessOverSSH() ) {
					break;
				}
			}
		} while( false );
	}
	
	/**
	 * Validates the BASEDIR option. 
	 *
	 * @return bool TRUE when configured correctly, else FALSE.
	 */
	private function testBaseDir()
	{
		$result = false;
		do {
			$help = 'Please check the BASEDIR option in the config.php file.';
			$serverscriptpath = $_SERVER['SCRIPT_FILENAME'];
			$serverscriptpath = str_replace('\\', '/', $serverscriptpath);
			if( !stristr( $serverscriptpath, BASEDIR ) ) {
				$this->setResult( 'ERROR',  'The BASEDIR setting seems to be incorrect '.
					'The BASEDIR config is the location on your hard drive where your proxy is located.', $help );
				break;
			}
			LogHandler::Log( 'wwtest', 'INFO', 'BASEDIR checked: '.BASEDIR );
			$result = true;
		} while( false );
		return $result;
	}
	
	/**
	 * Validates the DEBUGLEVELS, PROFILELEVEL and LOGFILE_FORMAT options. 
	 *
	 * @return bool TRUE when configured correctly, else FALSE.
	 */
	private function testLoggingOptions()
	{
		$result = false;
		do {
			// Check defines used for logging to avoid many checks for 'defined()'
			if( !$this->utils->validateDefines( $this, array('DEBUGLEVELS', 'PROFILELEVEL', 'LOGFILE_FORMAT'), 
										'config.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
				return false;
			}
			$help = 'Please check the DEBUGLEVELS option in the config.php file.';
			$debugLevels = unserialize(DEBUGLEVELS);
			if( !isset($debugLevels['default']) ) {
				$this->setResult( 'ERROR', 'For the DEBUGLEVELS option, the "defaults" key is missing. Please add.', $help );
			}
			foreach( $debugLevels as $debugLevel ) {
				if( !LogHandler::isValidLogLevel($debugLevel) ) {
					$this->setResult( 'ERROR', 'For the DEBUGLEVELS option an unsupported value "'.$debugLevel.'" is set.', $help );
				}
			}
			LogHandler::Log( 'wwtest', 'INFO', 'DEBUGLEVELS checked: '.print_r($debugLevels,true) );
			$result = true;
		} while( false );
		return $result;
	}
	
	/**
	 * Validates the OUTPUTDIRECTORY option. Path should exist, or value should be empty.
	 *
	 * @return bool TRUE when configured correctly, else FALSE.
	 */
	private function testOutputDirectory()
	{
		$result = false;
		do {
			if( !$this->utils->validateDefines( $this, array('OUTPUTDIRECTORY'),
				'config.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
				break;
			}
			if( OUTPUTDIRECTORY != '' ) {
				$help = 'Please check the OUTPUTDIRECTORY option in the config.php file.';
				if( !$this->utils->validateFilePath( $this, OUTPUTDIRECTORY, $help, true, 'ERROR',
					WW_Utils_TestSuite::VALIDATE_PATH_ALL & ~WW_Utils_TestSuite::VALIDATE_PATH_NO_SLASH ) ) { // must have slash at end
					break;
				}
			}
			LogHandler::Log( 'wwtest', 'INFO', 'OUTPUTDIRECTORY checked: '.OUTPUTDIRECTORY );
			$result = true;
		} while( false );
		return $result;
	}

	/**
	 * Validates the PROXYSTUB_URL option. URL should be valid and accessible.
	 *
	 * @return bool TRUE when configured correctly, else FALSE.
	 */
	private function testProxyStubUrl()
	{
		$result = false;
		do {
			if( !$this->utils->validateDefines( $this, array('PROXYSTUB_URL') ) ) {
				break;
			}
			$help = 'Please check the PROXYSTUB_URL option in the config.php file.';
			$endsWithSlash = (strrpos(PROXYSTUB_URL,'/') === (strlen(PROXYSTUB_URL)-1));
			if( !$endsWithSlash ) {
				$this->setResult( 'ERROR', 'PROXYSTUB_URL should end with a slash.', $help );
				break;
			}
			if( !$this->utils->validateUrl( $this, PROXYSTUB_URL.'proxystub/index.php', $help ) ) {
				break;
			}
			LogHandler::Log( 'wwtest', 'INFO', 'PROXYSTUB_URL checked: '.PROXYSTUB_URL );
			$result = true;
		} while( false );
		return $result;
	}

	/**
	 * Validates the ENTERPRISE_URL option. URL should be valid and accessible.
	 *
	 * @return bool TRUE when configured correctly, else FALSE.
	 */
	private function testEnterpriseUrl()
	{
		$result = false;
		do {
			if( !$this->utils->validateDefines( $this, array('ENTERPRISE_URL') ) ) {
				break;
			}
			$help = 'Please check the ENTERPRISE_URL option in the config.php file.';
			$endsWithSlash = (strrpos(ENTERPRISE_URL,'/') === (strlen(ENTERPRISE_URL)-1));
			if( !$endsWithSlash ) {
				$this->setResult( 'ERROR', 'ENTERPRISE_URL should end with a slash.', $help );
				break;
			}
			if( !$this->utils->validateUrl( $this, ENTERPRISE_URL.'index.php', $help ) ) {
				break;
			}
			LogHandler::Log( 'wwtest', 'INFO', 'ENTERPRISE_URL checked: '.ENTERPRISE_URL );
			$result = true;
		} while( false );
		return $result;
	}

	/**
	 * Validates the PROXYSERVER_TRANSFER_PATH option. Path should exists and should be writable.
	 *
	 * @return bool TRUE when configured correctly, else FALSE.
	 */
	private function testProxyServerTransferPath()
	{
		$result = false;
		do {
			if( !$this->utils->validateDefines( $this, array('PROXYSERVER_TRANSFER_PATH') ) ) {
				break;
			}
			$help = 'Please check the PROXYSERVER_TRANSFER_PATH option in the config.php file.';
			if( !$this->utils->validateFilePath( $this, PROXYSERVER_TRANSFER_PATH, $help, true, 'ERROR',
				WW_Utils_TestSuite::VALIDATE_PATH_ALL & ~WW_Utils_TestSuite::VALIDATE_PATH_NO_SLASH ) ) { // must have slash at end
				break;
			}
			if( !$this->utils->validateDirAccess( $this, PROXYSERVER_TRANSFER_PATH, $help ) ) {
				break;
			}
			LogHandler::Log( 'wwtest', 'INFO', 'PROXYSERVER_TRANSFER_PATH checked: '.PROXYSERVER_TRANSFER_PATH );
			$result = true;
		} while( false );
		return $result;
	}

	/**
	 * Validates the PROXYSTUB_TRANSFER_PATH option. Path should exists and should be writable.
	 *
	 * @return bool TRUE when configured correctly, else FALSE.
	 */
	private function testProxyStubTransferPath()
	{
		$result = false;
		do {
			if( !$this->utils->validateDefines( $this, array('PROXYSTUB_TRANSFER_PATH') ) ) {
				break;
			}
			if( PRODUCT_NAME_SHORT == 'proxystub' ) {
				$help = 'Please check the PROXYSTUB_TRANSFER_PATH option in the config.php file.';
				if( !$this->utils->validateFilePath( $this, PROXYSTUB_TRANSFER_PATH, $help, true, 'ERROR',
					WW_Utils_TestSuite::VALIDATE_PATH_ALL & ~WW_Utils_TestSuite::VALIDATE_PATH_NO_SLASH ) ) { // must have slash at end
					break;
				}
				if( !$this->utils->validateDirAccess( $this, PROXYSTUB_TRANSFER_PATH, $help ) ) {
					break;
				}

				// Check if the option has the same value configured for both machines; proxy and stub.
				if( array_key_exists( 'transferpath',  $_GET ) ) { // called by proxy server?
					if( ENTERPRISEPROXY_TRANSFER_PROTOCOL != 'ascp' ) { // for Aspera paths may differ (although they should point to the same location)
						$transferPath = $_GET['transferpath'];
						if( $transferPath != PROXYSTUB_TRANSFER_PATH ) {
							$help = 'Please check the PROXYSTUB_TRANSFER_PATH in the config.php file at both machines.';
							$this->setResult('ERROR', 
								'The option PROXYSTUB_TRANSFER_PATH configured '.
								'for the Proxy Server ('.$transferPath.') does not match the one '.
								'configured for the Proxy Stub ('.PROXYSTUB_TRANSFER_PATH.'). ', $help );
							break;
						}
					}
				}
			}

			LogHandler::Log( 'wwtest', 'INFO', 'PROXYSTUB_TRANSFER_PATH checked: '.PROXYSTUB_TRANSFER_PATH );
			$result = true;
		} while( false );
		return $result;
	}

	/**
	 * File transfer method of HTTP requests and responses between Proxy Server and Proxy Stub.
	 *
	 * Check which if a transfer protocol is configured. Then, look for defined options for that protocol and if the given values are valid.
	 *
	 * @return bool
	 */
	private function testEnterpriseProxyTransferProtocol()
	{
		$result = false;
		do {
			// Check if the option exists and has a known value.
			$help = 'Define the ENTERPRISEPROXY_TRANSFER_PROTOCOL with any of these options: cp, scp, bbcp, ascp';
			if( !$this->utils->validateDefines( $this, array('ENTERPRISEPROXY_TRANSFER_PROTOCOL'),
				'config.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_ALL, $help ) ) {
				break;
			}
			
			switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
				case 'cp':
					break;
				case 'scp':
					if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
						if( !$this->utils->validateDefines( $this, array(
							'SSH_STUB_HOST', 'SSH_STUB_PORT', 'SSH_STUB_USERNAME', 'SSH_STUB_PASSWORD') ) ) {
							break 2; // break out switch() + while()
						}
						// Note that the SSH_PROXY_... settings are not used by scp.
					}
					break;
				case 'bbcp':
					if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
						if( !$this->utils->validateDefines( $this, array(
							'SSH_PROXY_HOST', 'SSH_PROXY_PORT', 'SSH_PROXY_USERNAME', 'SSH_PROXY_PASSWORD',
							'SSH_STUB_HOST', 'SSH_STUB_PORT', 'SSH_STUB_USERNAME', // 'SSH_STUB_PASSWORD',
							'BBCP_COPYTO_CMD', 'BBCP_COPYFROM_CMD' ) ) ) {
							break 2; // break out switch() + while()
						}
					}
					break;
				case 'ascp':
					if( PRODUCT_NAME_SHORT == 'proxyserver' ) { // only when running as proxy server
						if( !$this->utils->validateDefines( $this, array(
							'ASPERA_USER', 'ASPERA_CERTIFICATE', 'ASPERA_SERVER', 'ASPERA_OPTIONS') ) ) {
							break 2; // break out switch() + while()
						}
					}
					break;
				default:
					$this->setResult('ERROR', 'The option ENTERPRISEPROXY_TRANSFER_PROTOCOL is not valid.', $help );
					break 2; // break out switch() + while()
			}
			
			// Check if the option has the same value configured for both machines; proxy and stub.
			if( PRODUCT_NAME_SHORT == 'proxystub' && 
				array_key_exists( 'protocol',  $_GET ) ) { // called by proxy server?
				$proxyServerProtocol = $_GET['protocol'];
				if( $proxyServerProtocol != ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
					$help = 'Please check the ENTERPRISEPROXY_TRANSFER_PROTOCOL in the config.php file at both machines.';
					$this->setResult('ERROR', 
						'The option ENTERPRISEPROXY_TRANSFER_PROTOCOL configured '.
						'for the Proxy Server ('.$proxyServerProtocol.') does not match the one '.
						'configured for the Proxy Stub ('.ENTERPRISEPROXY_TRANSFER_PROTOCOL.'). ', $help );
					break;
				}
			}
			LogHandler::Log( 'wwtest', 'INFO', 'ENTERPRISEPROXY_TRANSFER_PROTOCOL checked: '.ENTERPRISEPROXY_TRANSFER_PROTOCOL );
			$result = true;
		} while( false );
		return $result;
	}
	
	/**
	 * Check whether or not the SSH user is allowed to create and delete files at the transfer folder(s).
	 *
	 * For 'scp' this is tested for Proxy Stub. 
	 * For 'bbcp' this is tested for Proxy Stub and Proxy Server.
	 *
	 * @return bool
	 */
	private function testTransferFolderAccessOverSSH()
	{
		$result = true;
		switch( ENTERPRISEPROXY_TRANSFER_PROTOCOL ) {
			case 'scp': 
				$result = $this->testProxyStubTransferFolderAccessOverSSH();
			break;
			case 'bbcp':
				$result = 
					$this->testProxyServerTransferFolderAccessOverSSH() &&
					$this->testProxyStubTransferFolderAccessOverSSH();
			break;
		}
		return $result;
	}
	
	/**
	 * Check whether or not the SSH user is allowed to create and delete files at the 
	 * transfer folder configured for the Proxy Server (PROXYSERVER_TRANSFER_PATH).
	 *
	 * The SSH user is logged in (with configured credentials), a file is created, 
	 * access is changed for that file (chmod 777) and then deleted.
	 *
	 * @return bool
	 */
	private function testProxyServerTransferFolderAccessOverSSH()
	{
		$connection = ssh2_connect( SSH_PROXY_HOST, SSH_PROXY_PORT );
		if( !$connection ) {
			$this->setResult('ERROR', 'Could not connect over SSH using SSH_PROXY_HOST and SSH_PROXY_PORT settings.' );
			return false;
		}
		$result = ssh2_auth_password( $connection, SSH_PROXY_USERNAME, SSH_PROXY_PASSWORD );
		if( !$result ) {
			$this->setResult('ERROR', 'Could not authorize over SSH using SSH_PROXY_USERNAME and SSH_PROXY_PASSWORD settings.' );
			return false;
		}
		require_once BASEDIR.'/utils/NumberUtils.class.php';
		$testFile = PROXYSERVER_TRANSFER_PATH . NumberUtils::createGUID();
		if( !$this->sshExecCommand( $connection, 'echo "test123" > '.$testFile ) ) {
			$this->setResult('ERROR', 'Failed to create a file at PROXYSERVER_TRANSFER_PATH folder over SSH.' );
			return false;
		}
		if( !$this->sshExecCommand( $connection, 'chmod 777 '.$testFile ) ) {
			$this->setResult('ERROR', 'Failed to chmod a file at PROXYSERVER_TRANSFER_PATH folder over SSH.' );
			return false;
		}
		if( !$this->sshExecCommand( $connection, 'rm -f '.$testFile ) ) {
			$this->setResult('ERROR', 'Failed to remove a file from PROXYSERVER_TRANSFER_PATH folder over SSH.' );
			return false;
		}
		return true;
	}
	
	/**
	 * Check whether or not the SSH user is allowed to create and delete files at the 
	 * transfer folder configured for the Proxy Stub (PROXYSTUB_TRANSFER_PATH).
	 *
	 * The SSH user is logged in (with configured credentials), a file is created, 
	 * access is changed for that file (chmod 777) and then deleted.
	 *
	 * @return bool
	 */
	private function testProxyStubTransferFolderAccessOverSSH()
	{
		$connection = ssh2_connect( SSH_STUB_HOST, SSH_STUB_PORT );
		if( !$connection ) {
			$this->setResult('ERROR', 'Could not connect over SSH using SSH_STUB_HOST and SSH_STUB_PORT settings.' );
			return false;
		}
		$result = ssh2_auth_password( $connection, SSH_STUB_USERNAME, SSH_STUB_PASSWORD );
		if( !$result ) {
			$this->setResult('ERROR', 'Could not authorize over SSH using SSH_STUB_USERNAME and SSH_STUB_PASSWORD settings.' );
			return false;
		}
		require_once BASEDIR.'/utils/NumberUtils.class.php';
		$testFile = PROXYSTUB_TRANSFER_PATH . NumberUtils::createGUID();
		if( !$this->sshExecCommand( $connection, 'echo "test123" > '.$testFile ) ) {
			$this->setResult('ERROR', 'Failed to create a file at PROXYSTUB_TRANSFER_PATH folder over SSH.' );
			return false;
		}
		if( !$this->sshExecCommand( $connection, 'chmod 777 '.$testFile ) ) {
			$this->setResult('ERROR', 'Failed to chmod a file at PROXYSTUB_TRANSFER_PATH folder over SSH.' );
			return false;
		}
		if( !$this->sshExecCommand( $connection, 'rm -f '.$testFile ) ) {
			$this->setResult('ERROR', 'Failed to remove a file from PROXYSTUB_TRANSFER_PATH folder over SSH.' );
			return false;
		}
		return true;
	}

	/**
	 * Run a command over SSH.
	 *
	 * @param resource $connection
	 * @param string $command
	 * @return bool Whether or not successful.
	 */
	private function sshExecCommand( $connection, $command )
	{
		$stream = ssh2_exec( $connection, $command );
		$errorStream = ssh2_fetch_stream( $stream, SSH2_STREAM_STDERR );
		stream_set_blocking( $errorStream, true );
		$errMsg = stream_get_contents( $errorStream );
		if( $errMsg ) {
			$this->setResult( 'ERROR', 'Command over SSH has failed:<br/>'.$errMsg. '<br/>Command: '.$command );
		}
		fclose( $errorStream );
		fclose( $stream );
		return empty($errMsg);
	}
}
