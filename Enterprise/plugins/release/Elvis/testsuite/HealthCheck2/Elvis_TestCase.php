<?php
/**
 * @since      9.0.0
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

	/**
	 * @inheritdoc
	 */
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
		if( !$this->checkDbModelElvisPlugin() ) {
			return;
		}
		if ( !$this->checkBrandSetup() ) {
			return;
		}
		if ( !$this->checkSuperUser() ) {
			return;
		}
		if ( !$this->checkAdminUser() ) {
			return;
		}
		if ( !$this->checkSyncModule() ) {
			return;
		}
	}

	/**
	 * Checks if all defines exists in the Elvis/config.php file.
	 *
	 * @since 10.1.1
	 * @return bool
	 */
	private function checkDefinesExist() : bool
	{
		$result = true;

		// Check the defines that should exist and should be filled in (not empty).
		$nonEmptyDefines = array(
			'ELVIS_URL', 'ELVIS_CLIENT_URL', 'ELVIS_CLIENT_ID', 'ELVIS_CLIENT_SECRET',
			'ELVIS_CONNECTION_TIMEOUT', 'ELVIS_CONNECTION_REATTEMPTS',
			'ELVIS_ENT_ADMIN_USER', 'ELVIS_SUPER_USER',
			'ELVIS_NAMEDQUERY', 'ELVIS_CREATE_COPY', 'IMAGE_RESTORE_LOCATION'
		);
		if( !$this->utils->validateDefines( $this, $nonEmptyDefines, self::CONFIG_FILES, 'ERROR' ) ) {
			$result = false;
		}

		// Check the passwords that should exist and should be filled in (not empty), but suppress logging the values.
		$nonEmptySecureDefines = array( 'ELVIS_ENT_ADMIN_PASS' );
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

		// Check the deprecated defines that should NO longer exist.
		$help = 'Please check the '.self::CONFIG_FILES.' files and remove the obsoleted option.';
		$obsoletedDefines = array(
			array( 'name' => 'ELVIS_SUPER_USER_PASS', 'help' => 'It is superseded by the ELVIS_CLIENT_ID and ELVIS_CLIENT_SECRET options.' ),
		);
		foreach( $obsoletedDefines as $opt ) {
			if( defined( $opt['name'] ) ) {
				$this->setResult( 'WARN', 'The option '.$opt['name'].' is obsoleted. '.$opt['help'].'', $help );
			}
		}

		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server defines existence checked.' );
		return $result;
	}

	/**
	 * Checks if all defines in the Elvis/config.php file are correctly filled in.
	 *
	 * @since 10.1.1
	 * @return bool
	 */
	private function checkDefinedValues() : bool
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
		if( ( ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' && IMAGE_RESTORE_LOCATION == 'Elvis_Original' ) || // [EN-88325]
			( ELVIS_CREATE_COPY == 'Hard_Copy_To_Enterprise' && IMAGE_RESTORE_LOCATION == 'Elvis_Copy' ) ) {    // [EN-88426]
			$message = 'The ELVIS_CREATE_COPY option is set to "'.ELVIS_CREATE_COPY.'" and '.
				'the IMAGE_RESTORE_LOCATION option is set to "'.IMAGE_RESTORE_LOCATION.'". '.
				'However, this combination is not supported.';
			$this->setResult( 'ERROR', $message , $help );
			$result = false;
		}
		if( $result ) { // Only continue checking when all the above settings are fine.
			if( ELVIS_CREATE_COPY_WHEN_MOVED_FROM_PRODUCTION_ZONE && ELVIS_CREATE_COPY != 'Copy_To_Production_Zone' ) {
				$message = 'When ELVIS_CREATE_COPY_WHEN_MOVED_FROM_PRODUCTION_ZONE is set to true, ELVIS_CREATE_COPY option has to be set to ' .
					'"Copy_To_Production_Zone".';
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
	private function checkPhpExtensions() : bool
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
	private function checkOpenSslCipherMethod() : bool
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
	private function getPhpIni() : string
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
	 * @return bool TRUE when could connect (regardless if Elvis is not running / available), else FALSE.
	 */
	private function checkConnection() : bool
	{
		$result = true;
		$this->serverVersion = null;
		$serverInfo = null;
		try {
			require_once __DIR__.'/../../bizclasses/Client.class.php';
			$client = new Elvis_BizClasses_Client( null );
			$serverInfo = $client->getElvisServerInfo();
			$this->serverVersion = $serverInfo->version;
		} catch ( BizException $e ) {
			$help = "Possible causes:".
				"<ul>".
					"<li>The Elvis Server version is too old; Please check Elvis Server and the Compatibility Matrix.</li>".
					"<li>Elvis Server is not running at the configured IP; Please check the ELVIS_URL option in the ".self::CONFIG_FILES." file.</li>".
					"<li>Elvis Server is not listening at configured port; Please check the serverPort option in the node-config.properties.txt file.</li>".
				"</ul>";
			if( $e->getErrorCode() == 'S1036' ) {
				$this->setResult( 'ERROR', 'Could not detect Elvis Server version.', $help );
			} elseif( $e->getErrorCode() == 'S1144' ) { // suppress the long-winded common error message that is meant for end users
				$this->setResult( 'ERROR', 'Could not connect to Elvis Server. '.$e->getDetail(), $help );
			} else {
				$this->setResult( 'ERROR', $e->getMessage().' '.$e->getDetail(), $help );
			}
			$result = false;
		}

		$help = 'Please check your Elvis installation.';
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
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server connection checked.' );
		return $result;
	}

	/**
	 * Checks if Elvis Server version is compatible with Enterprise Server. See compatibility Matrix for details.
	 *
	 * @since 10.1.1
	 * @return bool
	 */
	private function checkVersionCompatibility() : bool
	{
		$versionOk = version_compare( $this->serverVersion, ELVIS_MINVERSION, '>=' );
		if( !$versionOk ) {
			$help = 'Please check the Compatibility Matrix.';
			$message = 'Elvis Server v'.$this->serverVersion.' is not compatible with Enterprise Server v'.SERVERVERSION.'.';
			$this->setResult( 'ERROR', $message, $help );
		}
		LogHandler::Log( 'Elvis', 'INFO', 'Elvis Server version compatibility checked.' );
		return $versionOk;
	}

	/**
	 * Make sure that the DB model shipped with the Elvis plugin is installed.
	 *
	 * @since 10.5.0
	 * @return bool
	 */
	private function checkDbModelElvisPlugin() : bool
	{
		require_once BASEDIR.'/server/dbscripts/dbinstaller/ServerPlugin.class.php';
		require_once BASEDIR.'/server/dbmodel/Factory.class.php';
		$pluginDbModelOk = true;
		$pluginName = 'Elvis';
		$url = SERVERURL_ROOT.INETROOT.'/server/admin/dbadmin.php?plugin='.$pluginName;

		// Check if the DB model for the server plugin is installed or upgraded to the wanted/current/latest DB model version.
		$installer = new WW_DbScripts_DbInstaller_ServerPlugin( null, $pluginName );
		$installedVersion = $installer->getInstalledDbVersion();
		$definition = WW_DbModel_Factory::createModelForServerPlugin( $pluginName );
		$requiredVersion = $definition->getVersion();
		if( $installedVersion !== $requiredVersion ) {
			$pluginInfo = BizServerPlugin::getInstalledPluginInfo( $pluginName );
			if( $installedVersion == null ) {
				$help = 'Please setup the database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
				$this->setResult( 'ERROR', "Database is not initialized for {$pluginInfo->DisplayName} server plug-in.", $help );
			} else {
				$help = 'Please update the database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
				$this->setResult( 'ERROR', "The actual database model version v{$installedVersion} ".
					"for {$pluginInfo->DisplayName} server plug-in does not meet the required version v{$requiredVersion}.", $help );
			}
			$pluginDbModelOk = false; // flag as error, but continue checking the other plugins
		} else {
			LogHandler::Log( 'wwtest', 'INFO',
				"Database model version v{$installedVersion} for server plugin {$pluginName} is correct." );

			// Check if there are patches or data upgrades to be installed for of the server plugin.
			$sqlPatchScripts = $installer->getDbModelScripts( $installedVersion, false, false ); // Look for patches only
			if( count( $sqlPatchScripts ) > 0 || $installer->needsDataUpgrade() ) {
				$pluginInfo = BizServerPlugin::getInstalledPluginInfo( $pluginName );
				$help = 'Please update database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
				$this->setResult( 'ERROR', "Database patches are available for {$pluginInfo->DisplayName} ".
					"server plug-in which needs to be installed.", $help );
				$pluginDbModelOk = false; // flag as error, but continue checking the other plugins
			} else {
				LogHandler::Log( 'wwtest', 'INFO',
					"DB patches and data upgrades are installed for server plugin {$pluginName}." );
			}
		}
		if( !$pluginDbModelOk ) {
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', "Checked the DB model provided by server plugin {$pluginName}." );
		return true;
	}

	/**
	 * When the Copy To Production Zone feature is enabled, it checks if all brands have the Production Zone filled in.
	 *
	 * @since 10.1.1
	 * @return bool
	 */
	private function checkBrandSetup() : bool
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
	 * Checks if the configured super user is configured at the Elvis server.
	 *
	 * @return bool
	 */
	private function checkSuperUser() : bool
	{
		$user = null;
		try {
			$user = $this->getUserFromElvis( ELVIS_SUPER_USER );
		} catch( BizException $e ) {
			if( !$this->handleComminicationErrors( $e, 'ELVIS_SUPER_USER' ) ) {
				$message = 'The configured user "'.ELVIS_SUPER_USER.'" could not be found at Elvis Server.'.
					' Reason: '.$e->getMessage().' Detail: '.$e->getDetail();
				$help = 'Please check the user access configuration in Elvis and check the ELVIS_SUPER_USER '.
					'option in the '.self::CONFIG_FILES.' file.';
				$this->setResult( 'ERROR', $message, $help );
			}
		}
		LogHandler::Log( 'Elvis', 'INFO', 'ELVIS_SUPER_USER option checked.' );
		return !is_null( $user );
	}

	/**
	 * Checks if the configured admin user is configured at the Enterprise Server.
	 *
	 * @return boolean
	 */
	private function checkAdminUser() : bool
	{
		$result = $this->logOn( ELVIS_ENT_ADMIN_USER, ELVIS_ENT_ADMIN_PASS );
		if( !$result ) {
			$message = 'The configured user "'.ELVIS_ENT_ADMIN_USER.'" could not log on to the Enterprise Server.';
			$help = 'Please check the user access configuration in Enterprise and check the ELVIS_ENT_ADMIN_USER and '.
				'ELVIS_ENT_ADMIN_PASS options in the '.self::CONFIG_FILES.' file.';
			$this->setResult( 'ERROR', $message, $help );
		}

		if( $result ) {
			require_once BASEDIR.'/server/secure.php';
			$result = hasRights( null, ELVIS_ENT_ADMIN_USER ); // system admin rights?
			if( !$result ) {
				$message = 'The configured user "'.ELVIS_ENT_ADMIN_USER.'" does not have system access rights for Enterprise.';
				$help = 'Please check the user access configuration in Enterprise and check the ELVIS_ENT_ADMIN_USER '.
					'option in the '.self::CONFIG_FILES.' file.';
			}

			LogHandler::Log( 'Elvis', 'INFO', 'ELVIS_ENT_ADMIN_USER option checked.' );
		}
		return $result;
	}

	/**
	 * Detect and report low-level communication errors.
	 *
	 * @since 10.5.0
	 * @param BizException $e
	 * @param string $userDefine
	 * @return bool Whether or not an error was detected and handled.
	 */
	private function handleComminicationErrors( BizException $e, string $userDefine ) : bool
	{
		$handled = false;
		$userName = constant( $userDefine );
		if( $e->getDetail() == 'SCEntError_ElvisAccessTokenError' ) {
			$help = "Possible causes:".
				"<ul>".
					"<li>Enterprise is not configured for Elvis; Please register Enterprise Server at Elvis Server and set ".
						"the ELVIS_CLIENT_ID and ELVIS_CLIENT_SECRET options in the ".self::CONFIG_FILES." file.</li>".
					"<li>The configured user named '{$userName}' is unknown to Elvis. Please check the {$userDefine} option in ".
						"the ".self::CONFIG_FILES." file. Also check the internal-users.properties.txt file (provided by Elvis).'</li>".
					"<li>The configured user is no super user in Elvis. Please make sure the ROLE_SUPERUSER option is set ".
						"for the user in the internal-users.properties.txt file (provided by Elvis).</li>".
				"</ul>";
			$message = 'Failed to retrieve an access token from Elvis Server.';
			$this->setResult( 'ERROR', $message, $help );
			$handled = true;
		}
		return $handled;
	}

	/**
	 * Retrieve user info from Elvis Server.
	 *
	 * @param string $username
	 * @return Elvis_DataClasses_EntUserDetails
	 */
	private function getUserFromElvis( string $username ) : Elvis_DataClasses_EntUserDetails
	{
		require_once __DIR__.'/../../logic/ElvisContentSourceService.php';

		$service = new ElvisContentSourceService();
		return $service->getUserDetails( $username );
	}

	/**
	 * Logon a given user to Elvis Server.
	 *
	 * @param string $user user name.
	 * @param string $password password.
	 * @return bool TRUE when the user could logon, else FALSE.
	 */
	private function logOn( string $user, string $password ) : bool
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$retVal = false;
		try {
			$request = new WflLogOnRequest();
			$request->User = $user;
			$request->Password = $password;
			$request->Server = 'Enterprise Server';
			$request->ClientAppName = __CLASS__;
			$request->ClientAppVersion = 'v'.SERVERVERSION;

			require_once BASEDIR.'/server/utils/UrlUtils.php';
			$clientip = WW_Utils_UrlUtils::getClientIP();
			$request->ClientName = isset( $_SERVER['REMOTE_HOST'] ) ? $_SERVER['REMOTE_HOST'] : '';
			// >>> BZ#6359 Let's use ip since gethostbyaddr could be extreemly expensive! which also risks throwing "Maximum execution time of 11 seconds exceeded"
			if( empty( $request->ClientName ) ) {
				$request->ClientName = $clientip;
			}
			$request->RequestInfo = array(); // ticket only

			$service = new WflLogOnService();
			/** @var $response WflLogOnResponse */
			$response = $service->execute( $request );
			$retVal = !empty( $response->Ticket );
		} catch( BizException $e ) {
		}
		return $retVal;
	}

	/**
	 * Checks whether the sync.php module is currently running and configured correctly.
	 *
	 * The sync.php module has checking by itself but it has no good way of communicating errors with the
	 * system admin user. It is setup with the help of a Crontab/Scheduler but the errors thrown/logged
	 * by the module may not be visible for the system admin user. Since 10.1.1 the value ranges are changed
	 * to avoid problems e.g. with AWS ELB that ends a HTTP connection after 60 seconds of no activity, which
	 * is the case for the long poll as implemented with sync.php. See EN-88406 for more details.
	 *
	 * For this reason the Health Check reports errors when anything wrong with the sync.php module.
	 *
	 * @since 10.2.0
	 * @return bool
	 */
	private function checkSyncModule() : bool
	{
		require_once __DIR__.'/../../ElvisSync.class.php';
		$result = true;

		// When options on the URL are badly configured, the sync.php module refuses to run.
		// Therefore we check the option first, and when ok, we check if the module is running.
		try {
			$options = ElvisSync::readLastOptions();
			if( $options ) {
				ElvisSync::validateOptions( $options );
			} // else {
				// The module always saves options, so when not options found it won't have ran ever before.
				// There is no need to report at this point because below we detect and error when not running.
				// We do NOT set $result to false here to allow detecting ElvisSync::isRunning below.
			// }
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage().' '.$e->getDetail() );
			$result = false;
		}
		if( $result && !ElvisSync::isRunning( 5 ) ) { // wait max 5 seconds for new cron to startup
			$help = 'Please make sure a Crontab/Scheduler is setup to periodically run this module and has full time coverage.';
			$this->setResult( 'ERROR', 'The sync.php module does not seem to be running.', $help );
			$result = false;
		}
		return $result;
	}
}
