<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Elvis_TestCase  extends TestCase
{
	public function getDisplayName() { return 'Elvis Content Source'; }
	public function getTestGoals()   { return 'Validates wether the Elvis integration is ready for production.'; }
	public function getTestMethods() { return 'It checks if options are correctly defined in the Elvis/config.php file and '.
		'wether a connection with Elvis Server can be established. It detects if Elvis Server is running and validates if the '.
		'version is compatible with Enterprise. It determines if the configured admin user and super user can log on to Elvis.'; }
	public function getPrio()        { return 24; }

	/** @var WW_Utils_TestSuite */
	private $utils;

	/** @var string Version of Elvis Server */
	private $serverVersion;

	const CONFIG_FILES = 'Enterprise/config/plugins/Elvis/config.php or Enterprise/config/overrule_config.php';

	final public function runTest()
	{
		require_once __DIR__.'/../../config.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		if ( !$this->checkDefinesExist() ) {
			return;
		}
		if ( !$this->checkDefinedValues() ) {
			return;
		}
		if( !$this->checkPhpExtensions() ) {
			return;
		}
		if( !$this->checkOpenSslCipherMethod() ) {
			return;
		}
		if ( !$this->checkConnection() ) {
			return;
		}
		if ( !$this->checkVersionCompatibility() ) {
			return;
		}
		if( !$this->checkIsLoadBalancerSupported() ) {
			return;
		}
		if ( !$this->checkFeatureCompatibility() ) {
			return;
		}
		if ( !$this->checkBrandSetup() ) {
			return;
		}
		if ( !$this->checkAdminUser() ) {
			return;
		}
		if ( !$this->checkSuperUser() ) {
			return;
		}
	}

	/**
	 * Checks if all defines exists in the Elvis/config.php file.
	 *
	 * @since 10.1.1
	 * @return boolean TRUE when check OK, else FALSE.
	 */
	private function checkDefinesExist()
	{
		$result = true;

		// Check the defines that should exist and should be filled in (not empty).
		$nonEmptyDefines = array(
			'ELVIS_URL', 'ELVIS_CLIENT_URL', 'ELVIS_NAMEDQUERY',
			'ELVIS_ENT_ADMIN_USER', 'ELVIS_SUPER_USER',
			'ELVIS_CREATE_COPY', 'IMAGE_RESTORE_LOCATION'
		);
		if( !$this->utils->validateDefines( $this, $nonEmptyDefines, self::CONFIG_FILES, 'ERROR' ) ) {
			$result = false;
		}

		// Check the passwords that should exist and should be filled in (not empty), but suppress logging the values.
		$nonEmptySecureDefines = array( 'ELVIS_ENT_ADMIN_PASS', 'ELVIS_SUPER_USER_PASS' );
		if( !$this->utils->validateDefines( $this, $nonEmptySecureDefines, self::CONFIG_FILES, 'ERROR',
			WW_Utils_TestSuite::VALIDATE_DEFINE_ALL, null, function( $defineName ) { return '***'; } ) ) {
			$result = false;
		}

		// Check the defines that should exist (without checking any values since empty is allowed).
		$existDefines = array( 'DEFAULT_ELVIS_PRODUCTION_ZONE' );
		if( !$this->utils->validateDefines( $this, $existDefines, self::CONFIG_FILES, 'ERROR',
			WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
			$result = false;
		}

		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server defines existence checked.' );
		return $result;
	}

	/**
	 * Checks if all defines in the Elvis/config.php file are correctly filled in.
	 *
	 * @since 10.1.1
	 * @return boolean TRUE when check OK, else FALSE.
	 */
	private function checkDefinedValues()
	{
		$result = true;
		$help = 'Please check the '.self::CONFIG_FILES.' file.';
		$options = array( 'Copy_To_Production_Zone', 'Hard_Copy_To_Enterprise', 'Shadow_Only' );
		if( !in_array( ELVIS_CREATE_COPY, $options ) ) {
			$message = 'The value "'.ELVIS_CREATE_COPY.'" set for the ELVIS_CREATE_COPY option is not supported.';
			$this->setResult( 'ERROR', $message , $help );
			$result = false;
		}
		$options = array( 'Elvis_Copy', 'Elvis_Original', 'Enterprise' );
		if( !in_array( IMAGE_RESTORE_LOCATION, $options ) ) {
			$message = 'The value "'.IMAGE_RESTORE_LOCATION.'" set for the IMAGE_RESTORE_LOCATION option is not supported.';
			$this->setResult( 'ERROR', $message , $help );
			$result = false;
		}
		if( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' && DEFAULT_ELVIS_PRODUCTION_ZONE == ''  ) {
			$message = 'The ELVIS_CREATE_COPY is set to "'.ELVIS_CREATE_COPY.'" '.
				'but the DEFAULT_ELVIS_PRODUCTION_ZONE option is empty.';
			$this->setResult( 'ERROR', $message , $help );
			$result = false;
		}
		// Check for the possible options combination.
		if( ( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' &&
					( IMAGE_RESTORE_LOCATION == 'Elvis_Original' || IMAGE_RESTORE_LOCATION == 'Enterprise' ) ) || // [EN-88325]
			( ELVIS_CREATE_COPY == 'Hard_Copy_To_Enterprise' &&
				    ( IMAGE_RESTORE_LOCATION == 'Elvis_Copy' || IMAGE_RESTORE_LOCATION == 'Elvis_Original' ) ) || // [EN-88426]
			( ELVIS_CREATE_COPY == 'Shadow_Only' &&
					( IMAGE_RESTORE_LOCATION == 'Elvis_Copy' || IMAGE_RESTORE_LOCATION == 'Enterprise' ) ) ) {
			$message = 'The ELVIS_CREATE_COPY option is set to "'.ELVIS_CREATE_COPY.'" and '.
				'the IMAGE_RESTORE_LOCATION option is set to "'.IMAGE_RESTORE_LOCATION.'". '.
				'However, this combination is not supported.';
			$this->setResult( 'ERROR', $message , $help );
			$result = false;
		}

		if( $result ) { // Only continue checking when all the above settings are fine.
			// When ELVIS_ALWAYS_CREATE_COPY_FOR_IMAGE is set to True.
			if( ELVIS_ALWAYS_CREATE_COPY_FOR_IMAGE &&
				( ELVIS_CREATE_COPY != 'Copy_To_Production_Zone' || IMAGE_RESTORE_LOCATION != 'Elvis_Copy' )) {
				$message = 'The ELVIS_ALWAYS_CREATE_COPY_FOR_IMAGE option is set to "true" but '.
					'the ELVIS_CREATE_COPY option is set to "'.ELVIS_CREATE_COPY.'" and '.
					'the IMAGE_RESTORE_LOCATION option is set to "'.IMAGE_RESTORE_LOCATION.'". '.
					'This combination is not supported.';
				$this->setResult( 'ERROR', $message , $help );
				$result = false;
			}
			// When ELVIS_ALWAYS_CREATE_COPY_FOR_IMAGE is set to False.
			$possibleOptions1 = ( ELVIS_CREATE_COPY == 'Hard_Copy_To_Enterprise' && IMAGE_RESTORE_LOCATION == 'Enterprise' );
			$possibleOptions2 = ( ELVIS_CREATE_COPY == 'Shadow_Only' && IMAGE_RESTORE_LOCATION == 'Elvis_Original' );
			$possibleOptions3 = ( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' && IMAGE_RESTORE_LOCATION == 'Elvis_Copy' );
			if( !ELVIS_ALWAYS_CREATE_COPY_FOR_IMAGE && ( !$possibleOptions1 && !$possibleOptions2 && !$possibleOptions3 )) {
				$message = 'The ELVIS_ALWAYS_CREATE_COPY_FOR_IMAGE option is set to "false" but '.
					'the ELVIS_CREATE_COPY option is set to "'.ELVIS_CREATE_COPY.'" and '.
					'the IMAGE_RESTORE_LOCATION option is set to "'.IMAGE_RESTORE_LOCATION.'". '.
					'This combination is not supported.';
				$this->setResult( 'ERROR', $message , $help );
				$result = false;

			}
		}

		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server defined values checked.' );
		return $result;
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
	 * Checks if the configured ELVIS_URL is valid by trying to connect to Elvis Server.
	 *
	 * When successful, it retrieves server info from it and populates $serverInfo.
	 * When successful, but Elvis tells it is not running / available, a warning is raised.
	 *
	 * @return boolean TRUE when could connect (regardless if Elvis is not running / available), else FALSE.
	 */
	private function checkConnection()
	{
		require_once __DIR__.'/../../logic/ElvisRESTClient.php';
		$client = new ElvisRESTClient();
		$this->serverVersion = $client->getElvisServerVersion();

		// The server info service is introduced since Elvis 5.
		// So we skip this test for Elvis 4 and assume all is ok.
		if( version_compare( $this->serverVersion, '5.0', '<' ) ) {
			$this->setResult( 'INFO', 'Connected to Elvis Server v'.$this->serverVersion.'.' );
			return true;
		}
		$serverInfo = $client->getElvisServerInfo();
		$help = 'Please check your Elvis installation.';
		$result = true;
		if( $serverInfo ) {
			if( $serverInfo->state == 'running' && $serverInfo->available ) {
				$this->setResult( 'INFO', 'Elvis Server v'.$this->serverVersion.' is available and running.' );
			}
			if( !$serverInfo->available ) {
				$this->setResult( 'WARN', 'Elvis Server v'.$this->serverVersion.' is not available.', $help );
				// no hard failure, leave $result == true untouched to continue testing the succeeding cases
			} elseif( $serverInfo->state !== 'running' ) {
				$this->setResult( 'WARN', 'Elvis Server v'.$this->serverVersion.' is not running.', $help );
				// no hard failure, leave $result == true untouched to continue testing the succeeding cases
			}
		} else {
			$help = 'Please check the ELVIS_URL option in the '.self::CONFIG_FILES.' file.';
			$this->setResult( 'ERROR', 'Could not connect to Elvis Server.', $help );
			$result = false;
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server connection checked.' );
		return $result;
	}

	/**
	 * Checks if Elvis Server version is compatible with Enterprise Server. See compatibility Matrix for details.
	 *
	 * @since 10.1.1
	 * @return boolean TRUE when check OK, else FALSE.
	 */
	private function checkVersionCompatibility()
	{
		$result = true;
		if( version_compare( $this->serverVersion, '4.6.4', '<' ) ) {
			$result = false;
		} elseif(
			version_compare( $this->serverVersion, '5.0', '>=' ) &&
			version_compare( $this->serverVersion, '5.0.60', '<' ) ){
			$result = false;
		}
		if( !$result ) {
			$help = 'Please check the Compatibility Matrix.';
			$message = 'Elvis Server v'.$this->serverVersion.' is not compatible with Enterprise Server v'.SERVERVERSION.'.';
			$this->setResult( 'ERROR', $message, $help );
		}

		// With the Elvis_Original option there were some problems with older Elvis Server < v5.14. [EN-88325]
		if( version_compare( $this->serverVersion, '5.14', '<=' ) &&
			IMAGE_RESTORE_LOCATION == 'Elvis_Original' ) {
			$help = 'Please check the '.self::CONFIG_FILES.' file.';
			$message = 'The IMAGE_RESTORE_LOCATION option is set to "'.IMAGE_RESTORE_LOCATION.'" '.
				'but Elvis Server v'.$this->serverVersion.' does not support this feature. '.
				'Please adjust the option or upgrade the Elvis Server.';
			$this->setResult( 'ERROR', $message, $help );
			$result = false;
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server version compatibility checked.' );
		return $result;
	}

	/**
	 * Function checks on which Load Balancer is/are Elvis connected to.
	 *
	 * Currently, only AWS Elastic Load Balancer (ELB) is supported.
	 * Application Load Balancer (ALB) is not supported.
	 *
	 * Function returns a false when an Application Load Balancer is used.
	 * Returns true when Classic Load Balancer is used / no Load Balancer is used.
	 *
	 * @since 10.0.5 / 10.1.2
	 * @return boolean See function header above.
	 */
	private function checkIsLoadBalancerSupported()
	{
		$result = true;
		require_once __DIR__.'/../../logic/ElvisRESTClient.php';
		$client = new ElvisRESTClient();
		$this->serverVersion = $client->getElvisServerVersion();

		$loadBalancerType = $client->getLoadBalancerType();
		if( $loadBalancerType == 'AWSALB' ) {
			$link = 'https://aws.amazon.com/elasticloadbalancing/classicloadbalancer/';
			$help = 'Use the Amazon AWS Classic Load Balancer. '.
				'For more information, see the <a href="'.$link.'" target="_blank">Amazon documentation</a>.';
			$message = 'The Amazon AWS Application Load Balancer that is currently configured is not supported.';
			$this->setResult( 'ERROR', $message, $help );
			$result = false;
		} else if( $loadBalancerType == 'AWSELB' ) {
			LogHandler::Log('Elvis','INFO','AWS Classic Load Balancer is configured / used.');
		} else if( is_null( $loadBalancerType )) {
			LogHandler::Log('Elvis','INFO','Either no load balancer is configured / used or ' .
				'the type of Load Balancer is unknown.' );
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server Load Balancer checked.' );
		return $result;
	}

	/**
	 * Checks if Elvis Server version is supports the features that are enabled for the integration.
	 *
	 * @since 10.1.1
	 * @return boolean TRUE when check OK, else FALSE.
	 */
	private function checkFeatureCompatibility()
	{
		$result = true;
		if( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' &&
			version_compare( $this->serverVersion, '5.18', '<' ) ) { // Feature introduced since Elvis 5.18
			$help = 'Either change the option or upgrade Elvis Server to v5.18 or newer.';
			$message = 'The ELVIS_CREATE_COPY option is set to \'Copy_To_Production_Zone\' but this feature is not '.
				' supported by Elvis Server v'.$this->serverVersion.'.';
			$this->setResult( 'ERROR', $message, $help );
			$result = false;
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server feature compatibility checked.' );
		return $result;
	}

	/**
	 * When the Copy To Production Zone feature is enabled, it checks if all brands have the Production Zone filled in.
	 *
	 * @since 10.1.1
	 * @return boolean TRUE when check OK, else FALSE.
	 */
	private function checkBrandSetup()
	{
		$result = true;
		if( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
			require_once __DIR__.'/../../util/ElvisBrandAdminConfig.class.php';
			$typeMap = BizAdmProperty::getCustomPropertyTypes( 'Publication' );
			$publications = DBAdmPublication::listPublicationsObj( $typeMap );
			/** @var AdmPublication[] $pubsToFix */
			$pubsToFix = array();
			if( $publications ) foreach( $publications as $publication ) {
				$productionZone = null;
				if( $publication->ExtraMetaData ) {
					$productionZone = ElvisBrandAdminConfig::getProductionZone( $publication );
				}
				if( !$productionZone ) {
					$pubsToFix[] = $publication;
				}
			}
			if( $pubsToFix ) {
				$pubIdsToFix = array();
				$message = 'The following brands do not have the property filled in: <ul>';
				foreach( $pubsToFix as $pubToFix ) {
					$message .= '<li>'.$pubToFix->Name.'</li>';
					$pubIdsToFix[] = $pubToFix->Id;
				}
				$help = 'Alternatively, reconsider the configured setting for the ELVIS_CREATE_COPY option in the '.self::CONFIG_FILES.' file.';
				$link = '../../config/plugins/Elvis/testsuite/HealthCheck2/repair_prodzone.php?ids='.implode(',',$pubIdsToFix);
				$message .= '</ul>Click <a href="'.$link.'" target="_blank">here</a> to automatically update the brands with the default value '.DEFAULT_ELVIS_PRODUCTION_ZONE.'.';
				$this->setResult( 'ERROR', $message );
				$result = false;
			}
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Production Zone property for brand setup checked.' );
		return $result;
	}

	/**
	 * Checks if the configured admin user can log on to the Elvis server.
	 *
	 * This user is needed for metadata synchronisation from Elvis to Enterprise
	 *
	 * @return boolean TRUE when the user could logon, else FALSE.
	 */
	private function checkAdminUser()
	{
		$result = $this->logOn( ELVIS_ENT_ADMIN_USER, ELVIS_ENT_ADMIN_PASS );
		if( !$result ) {
			$message = 'The configured user "'.ELVIS_ENT_ADMIN_USER.'" could not log on to the Elvis Server.';
			$help = 'Please check the user access configuration in Elvis and check the ELVIS_ENT_ADMIN_USER and '.
				'ELVIS_ENT_ADMIN_PASS options in the '.self::CONFIG_FILES.' file.';
			$this->setResult( 'ERROR', $message, $help );
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server admin user logon checked.' );
		return $result;
	}

	/**
	 * Checks if the configured super user can log on to the Elvis server.
	 *
	 * This user is needed for creating PDF previews with InDesign Server.
	 *
	 * @return boolean TRUE when the user could logon, else FALSE.
	 */
	private function checkSuperUser()
	{
		$result = $this->logOn( ELVIS_SUPER_USER, ELVIS_SUPER_USER_PASS );
		if( !$result ) {
			$message = 'The configured user "'.ELVIS_SUPER_USER.'" could not log on to the Elvis Server.';
			$help = 'Please check the user access configuration in Elvis and check the ELVIS_SUPER_USER and '.
				'ELVIS_SUPER_USER_PASS options in the '.self::CONFIG_FILES.' file.';
			$this->setResult( 'ERROR', $message, $help );
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server super user logon checked.' );
		return $result;
	}

	/**
	 * Logon a given user to Elvis Server.
	 *
	 * @param string $user user name.
	 * @param string $password password.
	 * @return boolean TRUE when the user could logon, else FALSE.
	 */
	private function logOn( $user, $password )
	{
		require_once __DIR__.'/../../logic/ElvisAMFClient.php';
		$result = true;
		try {
			$credentials = base64_encode($user . ':' . $password); // User name and password are base 64 encoded.
			ElvisAMFClient::loginByCredentials( $credentials );
		} catch ( BizException $e ) {
			$result = false;
		}
		return $result;
	}
}
