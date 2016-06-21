<?php
/**
 * Adobe DPS TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_AdobeDps_TestCase extends TestCase
{
	public function getDisplayName() { return 'Adobe Digital Publishing System (DPS)'; }
	public function getTestGoals()   { return 'Checks if Adobe DPS is installed and configured correctly.'; }
	public function getTestMethods() { return 'Configuration options in config_dps.php for Adobe DPS is checked.'; }
	public function getPrio()        { return 25; }
	
	final public function runTest()
	{
		if( !$this->checkServerPlugin() ) {
			return;
		}

		$this->checkPhpVersion();

		$this->checkMemoryLimit();

		if( !$this->checkDigitalMagazineFeature() ) {
			return;
		}

		if( !$this->checkArticleAccessProp() ) {
			return;
		}

		if ( !$this->checkMigrationKicker() ) {
			return;
		}

		if( !$this->checkCustomProperties() ) {
			return;
		}

		if ( !$this->checkDpsConfigProperties() ) {
			return;
		}

		if( !$this->checkExportPath() ) {
			return;
		}
		
		if( !$this->checkPersistentPath() ) {
			return;
		}

		if( !$this->dpsCheckConnection() ) {
			return;
		}

		if( !$this->dpsChannelEditionConfig() ) {
			return;
		}

		if( !$this->checkDeviceSetup() ) {
			return;
		}

		if( !$this->checkZipCommandLineInstallation() ) {
			return;
		}
		
		if( !$this->checkZipCommandLineIntegration() ) {
			return;	
		}
		
		if( !$this->checkParallelUploadSettings() ) {
			return;
		}

		if ( !$this->syncFreePaidPerDevice() ) {
			return;
		}

		if ( !$this->isHTMLResourcesCacheCopied() ) {
			return;
		}

		LogHandler::Log( 'wwtest', 'INFO', 'Validated Adobe DPS configuration.' );
	}

	/**
	 * Check if Adobe DPS server plugin is installed.
	 *
	 * @return boolean True if Adobe DPS server plugin is installed.
	 */
	private function checkServerPlugin()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
		
		// When plug-in is not installed or disabled, skip test and refer to the 
		// Server Plug-ins page to install/enable.
		$help = 'Check <a href="../../server/admin/serverplugins.php' . '">Server Plug-ins</a>';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'AdobeDps_PubPublishing' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult( 'NOTINSTALLED', 'The Adobe DPS server plug-in is disabled.', $help );
				return false;
			}
		} else {
			$this->setResult( 'NOTINSTALLED', 'The Adobe DPS server plug-in is not installed.', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'Checked server plug-in Adobe DPS installation.' );

		return true;
	}

	/**
	 * Checks if installed PHP version meet minimum PHP 5.3.6 version for Windows environment.
	 *
	 * @return boolean Whether it meets minimum PHP 5.3.6 version or not(true/false).
	 */
	private function checkPhpVersion()
	{
		if( OS == 'WIN' ) { // Check PHP version only for Windows environment.
			$minPhpVersion = '5.3.6';
			$help = 'Minimum version of PHP 5.3.6 required.';
			$phpVer = phpversion();
			if ( version_compare($phpVer, $minPhpVersion, '<' ) ) {
				$this->setResult( 'WARN', 'Unsupported version of PHP installed: v'.$phpVer, $help );
			}
			LogHandler::Log( 'wwtest', 'INFO', 'Minimum PHP ' . $minPhpVersion . ' version checked.' );
		}
	}

	/**
	 * Check if DigitalMagazine server feature is enable.
	 *
	 * @return boolean True if server feature disable.
	 */
	private function checkDigitalMagazineFeature()
	{
		// The DigitalMagazine option is added on-the-fly when the DPS plugin is enabled (=new way).
		// When this option is still manually added (=old way) then we need to error.
		if( BizSettings::isFeatureEnabled( 'DigitalMagazine' ) ) {
			$this->setResult( 'ERROR', 'The "DigitalMagazine" option should no longer be set. ',
				'Please remove this option from the SERVERFEATURES setting at the configserver.php file.' ); 
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'Checked DigitalMagazine option at SERVERFEATURES setting.' );

		return true;
	}

	/**
	 * Check if PHP memory limit meets the minimum 512M requirement.
	 *
	 */
	private function checkMemoryLimit()
	{
		$memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
		// -1 means no limit
		if( $memoryLimit != -1 ) {
			if ( $memoryLimit < $this->parseMemoryLimit('512M') ) {
				$message = 'The PHP setting <b>"memory_limit"</b> is set to '.ini_get('memory_limit').'; please increase it to at least 512M in the php.ini file.<br />';
				$message .= 'A higher value than 512M is recommended when uploading a large issue with the asynchronous upload option enabled.';
				$help = 'Your php.ini can be found in the following location: <br>&nbsp;&nbsp;&nbsp;"'.$this->getPhpIni().'"<br/>';
				$help .= 'If your changes to the ini file are not reflected, try restarting the Web Server.';
				$this->setResult( 'WARN', $message, $help );
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'memory_limit checked.');
	}

	/**
	 * Checks if given custom properties are correctly configured at MetaData Setup.
	 * If one (or more) properties is not configured or has wrong type the check fails.
	 *
	 * @return boolean All mandatory properties are set up and of the correct type.
	 */
	private function checkCustomProperties()
	{
		// Check if custom metadata properties (as required by Digital Magazine) are created well
		$customProps = array( 
			'C_READER_LABEL'       => 'string',
			'C_INTENT'             => 'list',
			'C_DOSSIER_INTENT'     => 'list',
			'C_WIDGET_MANIFEST'    => 'multiline',
			'C_DOSSIER_IS_AD'      => 'bool',
			'C_OVERLAYS_IN_BROWSE' => 'bool',
			'C_HIDE_FROM_TOC'      => 'bool',
			'C_LAYOUT_FOR_TOC'     => 'list',
			'C_DPS_SECTION'        => 'list',
			'C_DOSSIER_NAVIGATION' => 'list',
			'C_ARTICLE_ACCESS'     => 'list',
			'C_KICKER'             => 'list',
		);

		// An array of property names for which we only want to check the type, if the field exists.
		$onlyCheckPropType = array(
			'C_DPS_SECTION',
			'C_DOSSIER_NAVIGATION',
		);

		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		$undefProps = array();
		$badTypes = '';
		foreach( $customProps as $propName => $propType ) {
			$property = DBProperty::getObjectPropertyByName( $propName );
			if( !$property ) {
				if ( !in_array( $propName, $onlyCheckPropType ) ) {
					$undefProps[substr( $propName, 2 )] = $propType;
				}
			} elseif( $property->Type != $propType ) {
				$badTypes .= 'Custom property "'.substr( $propName, 2 ).'" has wrong type "'.
								$property->Type.'". Type should be "'.$propType.'". ';
			}
		}
		$help = 'Please go to MetaData Setup page to configure.';
		if( count( $undefProps ) >= 1 ) {
			$msg = ( count( $undefProps ) == 1 ) ? 'Custom property ' : 'Custom properties ';
			$comma = '';
			foreach( $undefProps as $propName => $propType ) {
				$msg .= $comma."\"$propName\" (type \"$propType\")";
				$comma = ', ';
			}
			$msg .= ' not defined. ';
			$this->setResult( 'ERROR', $msg, $help );
			return false;
		}
		if( !empty( $badTypes ) ) {
			$this->setResult( 'ERROR', $badTypes, $help );
			return false;
		}

		return true;
	}

	/**
	 * Checks if the ADOBEDPS_READER_VERSIONS option is set correctly.
	 *
	 * @return boolean True if ADOBEDPS_READER_VERSIONS is valid
	 */
	private function checkDpsConfigProperties()
	{
		require_once BASEDIR.'/config/config_dps.php';

		if ( !defined( 'ADOBEDPS_READER_VERSIONS' ) ) {
			$error = 'The configuration for ADOBEDPS_READER_VERSIONS cannot be found in the configuration files.';
			$tip = 'Please merge config_dps.php with the latest version.';
			$this->setResult( 'ERROR', $error, $tip );
			return false;
		}

		$readerVersions = unserialize( ADOBEDPS_READER_VERSIONS );
		if ( !is_array( $readerVersions ) ) {
			$error = 'The configuration for ADOBEDPS_READER_VERSIONS should be an array.';
			$tip = 'Please change the configuration to an array in config_dps.php.';
			$this->setResult( 'ERROR', $error, $tip );
			return false;
		}

		foreach ( $readerVersions as $readerVersion ) {
			// If the user entered integers in the array instead of strings check them properly.
			// Reader Version needs to be a positive number.
			$hasError = ( is_int( $readerVersion ) && $readerVersion < 0 );

			// If there is no error yet, but the readerVersion is not numeric thats wrong.
			$hasError = ( !$hasError && !is_numeric( $readerVersion ) );

			// If there is no error but readerVersion is numeric, check if it represents a positive integer.
			if ( !$hasError && is_numeric( $readerVersion ) ){
				$intVal = intval( $readerVersion );
				if ( $intVal < 0 ) {
					$hasError = true;
				} else {
					// Check that the entered value is an integer.
					$intVal = (string)$intVal;
					if ( $intVal != $readerVersion ) {
						$hasError = true;
					}
				}
			}

			if ( $hasError ) {
				$error = 'The configuration for ADOBEDPS_READER_VERSIONS should be an array of integers. The option \''.$readerVersion.'\' is invalid.';
				$tip = 'Please change the option to an positive integer in config_dps.php.';
				$this->setResult( 'ERROR', $error, $tip );
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the path used for persistent storage (HTML Resources cache e.g.) is valid, writable etc.
	 * 
	 * @return boolean
	 */
	private function checkPersistentPath()
	{
		// Check syntax of the DEFINE 
		require_once BASEDIR . '/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		if( !$utils->validateDefines( $this, array( 'ADOBEDPS_PERSISTENTDIR' ), 'config_dps.php')) {
			return false;
		}

		$help = 'Make sure the '.ADOBEDPS_PERSISTENTDIR.' folder exists and is writable from the Webserver.';
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		if(!FolderUtils::isDirWritable(ADOBEDPS_PERSISTENTDIR)){
			$this->setResult( 'ERROR', 'The '.ADOBEDPS_PERSISTENTDIR.' folder is not writable.', $help );
			return false;
		}
		LogHandler::Log('wwtest', 'INFO', 'The '.ADOBEDPS_PERSISTENTDIR.' folder is writable.' );		
		return true;
	}	

	/**
	 * Checks if the path used for exporting files is valid, writable etc.
	 *
	 * @return boolean Export path is valid and writable (true/false).
	 */
	private function checkExportPath()
	{
		require_once BASEDIR.'/config/config_dps.php';
		$help = 'Check the ADOBEDPS_EXPORTDIR option in config_dps.php.';
		if( !defined('ADOBEDPS_EXPORTDIR') ) {
			$this->setResult( 'ERROR', 'The ADOBEDPS_EXPORTDIR option is not defined.', $help );
			return false;
		}

		if( trim(ADOBEDPS_EXPORTDIR) == '' ) {
			$this->setResult( 'ERROR', 'The ADOBEDPS_EXPORTDIR option is empty.', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'ADOBEDPS_EXPORTDIR option: '.ADOBEDPS_EXPORTDIR);
		
		if( !substr(ADOBEDPS_EXPORTDIR, -1, 1) == '/' ) {
			$this->setResult( 'ERROR', 'The '.ADOBEDPS_EXPORTDIR.' (ADOBEDPS_EXPORTDIR option) has not an ending slash "/".', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'The ADOBEDPS_EXPORTDIR syntax is correct.' );

		// Check existence and write access of ATTACHMENTDIRECTORY folder		
		$help = 'Make sure the '.ADOBEDPS_EXPORTDIR.' (ADOBEDPS_EXPORTDIR) folder exists and is writable from the Webserver.';
		if(!is_dir(ADOBEDPS_EXPORTDIR)){
			$this->setResult( 'ERROR', 'The '.ADOBEDPS_EXPORTDIR.' folder does not exist or is not a folder.', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'The ADOBEDPS_EXPORTDIR folder exists.' );
		
		if(!is_writable(ADOBEDPS_EXPORTDIR)){
			$this->setResult( 'ERROR', 'The '.ADOBEDPS_EXPORTDIR.' (ADOBEDPS_EXPORTDIR) folder is not writable.', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'The ATTACHMENTDIRECTORY folder is writable.' );
		
		if( !@mkdir(ADOBEDPS_EXPORTDIR.'/wwtest')){
			$this->setResult( 'ERROR', 'The '.ADOBEDPS_EXPORTDIR.' (ATTACHMENTDIRECTORY) folder is not writable.', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', ADOBEDPS_EXPORTDIR.'/wwtest folder created.' );
		
		if( !@rmdir(ADOBEDPS_EXPORTDIR.'/wwtest')){
			$this->setResult( 'ERROR', 'Could not remove wwtest folder in '.ADOBEDPS_EXPORTDIR.' (ADOBEDPS_EXPORTDIR) folder. Please make sure delete rights are granted.', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', ADOBEDPS_EXPORTDIR.'/wwtest folder removed.' );

		return true;
	}

	/**
	 * Checks the connection to the defined DPS URLs
	 */
	private function dpsCheckConnection()
	{
		// The ENTERPRISE_CA_BUNDLE should be defined in configserver.php
		if ( !defined('ENTERPRISE_CA_BUNDLE') ) {
			$help = 'Make sure the ENTERPRISE_CA_BUNDLE define is present in configserver.php';
			$this->setResult( 'ERROR', 'The Enterprise CA bundle is not defined.', $help  );
		}

		$dpsAccounts = unserialize( ADOBEDPS_ACCOUNTS );

		// Make sure the url is only test once
		$serverUrls = array();
		if( $dpsAccounts ) foreach( $dpsAccounts as $dpsAccs ) {
			if( $dpsAccs ) foreach( $dpsAccs as $dpsAccSettings ) {
				if ( isset($dpsAccSettings['serverurl']) && !in_array($dpsAccSettings['serverurl'], $serverUrls) ) {
					$serverUrls[] = $dpsAccSettings['serverurl'];
				}
			}
		}

		require_once BASEDIR . '/server/utils/DigitalPublishingSuiteClient.class.php';
		foreach ( $serverUrls as $url ) {
			$dpsService = new WW_Utils_DigitalPublishingSuiteClient( $url );

			$curlErrNr = null;
			$result = $dpsService->connectionCheck( $curlErrNr );
			if ( !$result && $curlErrNr ) {
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
					$testFile = dirname(__FILE__).'/caCertificates.php';
					$wwtestUrl = WW_Utils_UrlUtils::fileToUrl( $testFile, 'server', false );
					$help = ' Please run the <a href="'.$wwtestUrl.'" target="_blank">Get CA Certificates</a> page that will download the CA certificates.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. There seems to be a SSL certificate problem.', $help  );
					return false;
				}

				// Test of the peer certificate failed. This usually happens when you use the ip address instead of the URL.
				if ( $curlErrNr == CURLE_SSL_PEER_CERTIFICATE ) {
					$help = ' Make sure you use the correct URL, IP addresses are not allowed.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. There seems to be a SSL problem with the server certificate.', $help  );
					return false;
				}

				if ( $curlErrNr == CURLE_UNSUPPORTED_PROTOCOL ) {
					$help = ' Update the url to a valid URL in config/config_dps.php.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The protocol is not supported by cURL.', $help  );
					return false;
				}

				if ( $curlErrNr == CURLE_URL_MALFORMAT ) {
					$help = ' Update the url to a valid URL in config/config_dps.php.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The URL is malformed.', $help  );
					return false;
				}

				if ( $curlErrNr == 4 ) { // cURL error CURLE_NOT_BUILT_IN constant is not defined, but is a valid error, hence using the number.
					$help = ' cURL could not find the feature that is requested.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'.', $help  );
					return false;
				}

				if ( $curlErrNr == CURLE_COULDNT_RESOLVE_PROXY ) {
					$help = ' Check the proxy setting in configserver.php.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The proxy could not be resolved.', $help  );
					return false;
				}

				if ( $curlErrNr == CURLE_COULDNT_RESOLVE_HOST ) {
					$help = ' Update the url to a valid URL in config/config_dps.php.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'. The host could not be found.', $help  );
					return false;
				}

				if ( $curlErrNr == CURLE_COULDNT_CONNECT ) {
					$help = ' Check the connection to the URL.';
					$this->setResult( 'ERROR', 'Could not connect to: \'' . $url . '\'.', $help  );
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Validate the Adobe DPS account configuration. It check if:
	 * - the obsoleted ADOBEDPS_CONFIG option in config_dps.php file is no longer used;
	 * - the new ADOBEDPS_ACCOUNTS option is used instead, as introduced since 7.6.0;
	 * - the ADOBEDPS_ACCOUNTS is correctly configured, matching channels/edition in system;
	 * - the account configured at ADOBEDPS_ACCOUNTS can be used to login at the DPS server.
	 *
	 * @since v7.6.0
	 * @return boolean TRUE when all ok. FALSE when any configuration error found.
	 */
	private function dpsChannelEditionConfig()
	{
		require_once BASEDIR . '/server/utils/DigitalPublishingSuiteClient.class.php';
		$generalHelp = 'Check the ADOBEDPS_ACCOUNTS option in config_dps.php.';
		
		if( defined('ADOBEDPS_CONFIG') ) { // Obsoleted since v7.6.0
			$help = 'Please use ADOBEDPS_ACCOUNTS instead.';
			$this->setResult( 'ERROR', 'The ADOBEDPS_CONFIG is obsoleted but it is defined in config_dps.php.', $help );
			return false;
		}
		
		if( !defined('ADOBEDPS_ACCOUNTS') ) { // Introduced since v7.6.0
			$this->setResult( 'ERROR', 'The ADOBEDPS_ACCOUNTS option is not defined.', $generalHelp );
			return false;
		}

		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
		$pubChannelBrandSetup = new WW_Utils_ResolveBrandSetup();
		$dpsAccounts = unserialize( ADOBEDPS_ACCOUNTS );

		foreach( $dpsAccounts as $channelId => $dpsAccs ) {
		
			// validate channelId and its carried editionId + dps setting.
			if( !$dpsAccs ) {
				$this->setResult( 'ERROR', 'No edition id and Adobe DPS credentials for channel id ' . $channelId .'.', $generalHelp );
				return false;
			}

			if( !is_int( $channelId ) ) {
				$this->setResult( 'ERROR', 'Channel id (id=' . $channelId .') should be an integer but it is currently not.', $generalHelp );
				return false;
			}
			
			foreach( $dpsAccs as $editionId => $dpsAccSettings ) {
				if( !is_int( $editionId ) ) {
					$this->setResult( 'ERROR', 'Edition id (id=' . $editionId .') should be an integer but it is currently not.', $generalHelp );
					return false;
				}
				
				if( $channelId == 0 && $editionId == 0 ) { // All channels and All editions
					if( !$this->validateAdobeAccSettings( $dpsAccSettings, 0, 'All', 0, 'All' ) ){
						return false;					
					}
				} else if ( $channelId != 0 && $editionId != 0 ) { // Specific channel and specific edition
					try {
						$pubChannelBrandSetup->resolveEditionPubChannelBrand( $editionId );
					} catch( BizException $e ) {
						$help = $generalHelp . 'Please ensure edition id is a valid Db id';
						$this->setResult( 'ERROR', $e->getDetail(), $help );
						return false;
					}
					
					$edition = $pubChannelBrandSetup->getEdition();
					$pubChannel = $pubChannelBrandSetup->getPubChannelInfo();					
					
					// validate channel Id
					if( $pubChannel->Id != $channelId ) {
						$this->setResult( 'ERROR', 'Configured channel id (id='.$channelId.') does not have ' .
													'edition '.$edition->Name.' (id=' .$editionId.')', $generalHelp );
						return false;
					}
					
					if( $pubChannel->PublishSystem != 'AdobeDps' ) {
						$this->setResult( 'ERROR', 'Configured channel id (id='.$channelId.') and ' .
													'edition id (id=' .$editionId.')' .
													' do not belong to Adobe Dps channel.', $generalHelp );					
						return false;
					}

					if( !$this->validateAdobeAccSettings( $dpsAccSettings, 
								$pubChannel->Id, $pubChannel->Name, $edition->Id, $edition->Name ) ){
						return false;					
					}
					
				} else if( $channelId != 0 && $editionId == 0 ) { // Specific channel and all editions
					require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
					
					$dbChannelRow = DBChannel::getChannel( $channelId );
					if( !$dbChannelRow ) {
						$this->setResult( 'ERROR', 'Configured channel id (id='.$channelId.') does not exists in the database.', $generalHelp );
						return false;
					}
					
					if( $dbChannelRow['publishsystem'] != 'AdobeDps' ) {
						$this->setResult( 'ERROR', 'Configured channel id (id='.$channelId.') and ' .
													'for all edition (id=' .$editionId.')' .
													' do not belong to Adobe Dps channel.', $generalHelp );					
						return false;
					}

					if( !$this->validateAdobeAccSettings( $dpsAccSettings, 
								$dbChannelRow['id'], $dbChannelRow['name'], 0, 'All' ) ){
						return false;					
					}					
					
				} else if( $channelId == 0 && $editionId != 0 ) { // All channel but specific edition ?! => is not allowed!
					$this->setResult( 'ERROR', 'Having all channel (id = 0) with a specific edition id (id='.$editionId.') is not allowed.', 
						$generalHelp );
					return false;
				}
			}
		}
		
		return true;
	}

	/**
	 * Validate the ADOBEDPS_ACCOUNTS setting in config_dps.php file.
	 * This function ensures that there are three settings defined ('serverurl', 'username' and
	 * 'password') and all these values are not empty.
	 * It also does a sign-in to the Adobe Dps server to ensure that those credentials are valid.
	 *
	 * @param array $dpsAccSettings Dps account setting in key-value pairs. Keys are 'serverurl','username' and 'password'
	 * @param int $channelId Db channel Id for error information incase the account settings are not valid.
	 * @param string $channelName Pubchannel name for error information incase the account settings are not valid.
	 * @param int $editionId Db edition id for error information incase the account settings are not valid.
	 * @param string $editionName Edition name for error information incase the account settings are not valid.
	 * @return boolean FALSE if any settings are found to be invalid/incomplete, or TRUE if all passed.
	 */
	private function validateAdobeAccSettings( $dpsAccSettings, $channelId, $channelName, $editionId, $editionName )
	{
		$generalHelp = 'Check the ADOBEDPS_ACCOUNTS option in config_dps.php.';
		// Check if all settings for ADOBEDPS_ACCOUNTS are filled and valid.
		foreach( $dpsAccSettings as $setting => $value ) {
			if( trim( $value ) == '' ) {
				$this->setResult( 'ERROR', 'The setting "'.$setting.'" for channel '.$channelName .
						'(id='.$channelId.') with edition '. $editionName.' (id='.$editionId.') is empty.',
						$generalHelp );
				return false;
			}
		}
		
		$requiredSettings = array( 'serverurl', 'username', 'password' );
		foreach( $requiredSettings as $requiredSetting ) {
				if( !isset( $dpsAccSettings[$requiredSetting] )) {
					$this->setResult( 'ERROR', 'The setting "'.$requiredSetting.'" for channel '.$channelName .
						'(id='.$channelId.') with edition '. $editionName.' (id='.$editionId.') does not exists.',
						$generalHelp );
					return false;
				}
		
		}
		
		// Check if we can login at Adobe DPS with the configured user.
		try {
			$dpsService = new WW_Utils_DigitalPublishingSuiteClient( $dpsAccSettings['serverurl'], $dpsAccSettings['username'] );
			$dpsService->signIn( $dpsAccSettings['username'], $dpsAccSettings['password'] );
		} catch( BizException $e ) {
			$e = $e; // Keep the Code Analyzer happy.
			switch( $dpsService->getHttpCode() ) {
				case 401 : // Authentication problem reported by Adobe DPS.
					$this->setResult( 'ERROR', 'Adobe DPS credentials for channel '.$channelName .
						' (id='.$channelId.') with edition '. $editionName.' (id='.$editionId.') are invalid.', $generalHelp );
					break;
				case 404 : // Host cannot be resolved.
					$message = 'Could not resolve Host "' . $dpsAccSettings['serverurl'] . '" Possible causes: [1] The '
						. 'host is temporarily unavailable. [2] The configured host name for '
						. 'the "serverurl" setting is incorrect in config_dps.php for channel: ' .$channelName .
						'(id='.$channelId.') with edition '. $editionName.' (id='.$editionId.'). [3] A Firewall is blocking '
						. 'communication with the host.';
					$this->setResult( 'ERROR', $message, $generalHelp );
					break;
				default : // Everything else.
					$message = 'Connection with AdobeDPS could not be established.';
					$this->setResult( 'ERROR', $message, 'Please see the error log for further details.' );
					break;
			}
			return false;
		}
		return true;
	}
	
	/**
	 * Checks if there is at least one device configured at SERVERFEATURES option.
	 * It also checks if all configured devices are valid.
	 *
	 * @return boolean Whether the configuration for devices is complete and valid (true/false).
	 */
	private function checkDeviceSetup()
	{
		// Check if pages will meet the iPad screen resolution of 132 DPI when using the default device
		require_once BASEDIR.'/server/bizclasses/BizAdmOutputDevice.class.php';
		$bizDevice = new BizAdmOutputDevice();
		$devices = $bizDevice->getDevices(); 
		
		$help = 'Please check your devices configured at the Output Devices admin page.';
		if( !$devices ) {
			$this->setResult( 'ERROR', 'No devices found.', $help );
			return false;
		}
		foreach( $devices as $device ) {
			if( $device->isValid() == false ) {
				$message = 'Device "'.$device->Name.'" (id='.$device->Id.') isn\'t configured correctly. ';
				$this->setResult( 'ERROR', $message, $help );
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks if zip/unzip command line tools are installed,
	 * also make sure that the maker of zip/unzip is by Info-ZIP
	 * correct version is used.
	 *
	 * Shows error and bail out when any of the criteria is not met.
	 *
	 */
	private function checkZipCommandLineInstallation()
	{
		require_once BASEDIR.'/server/utils/ZipUtility.class.php';
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';	
		
		$cmd = 'zip -h'; // zip -v doesn't work for Zip v2.1: Returns garbage which makes the whole health check halts.
		$zipResult = array();
		exec( $cmd, $zipResult );
		$zipResult = implode( PHP_EOL, $zipResult );
		////////////////////////////////////////////////////////////////////////////////////////////////////
		//      $zipResult = "Copyright (c) 1990-2008 Info-ZIP - Type zip \"-L\" for software license.    //
		//        Zip 3.0 (July 5th 2008) ...	"                                                         //
		////////////////////////////////////////////////////////////////////////////////////////////////////
		
		// Generic help(Tip) message for zip utility.
		if( OS == 'WIN' ) {
			$zipHelp = 'Please download and install the \'Info-ZIP\' Zip package. Links to use: '. PHP_EOL .
						'<a href="ftp://ftp.info-zip.org/pub/infozip/win32/zip300xn.zip">Windows 32-bit version</a>, '.
						'<a href="ftp://ftp.info-zip.org/pub/infozip/win32/zip300xn-x64.zip">Windows 64-bit version</a>. '.
						'In case the package is already installed, please ensure that the ZIP directory '.
						'is set in the environment called \'Path\'.';
		}
		
		if( OS == 'UNIX' || OS == 'LINUX' ) {
			$zipHelp = 'Please download and install the \'Info-ZIP\' Zip package from ' .
						'<a href="http://www.info-zip.org/Zip.html">info-zip.org</a>.';
			
		}

		// Is zip installed?
		if( !$zipResult ) {
			$this->setResult( 'ERROR', 'The ZIP utility is not found or is not installed.', $zipHelp );	
			return false;
		}
	
		// Is the zip from Info-ZIP?
		if( stripos( $zipResult, 'Info-ZIP' ) === false ) {
			$this->setResult( 'ERROR', 'The ZIP utility by \'Info-ZIP\' is not found.', $zipHelp );
			return false;
		}
		
		// can find the version?
		$matches = array();
		preg_match( '/zip ([0-9]+\.[0-9]+)/i', $zipResult, $matches );			
		if( isset( $matches[1] ) ) {
			$zipVersion = $matches[1];
			LogHandler::Log('ZipUtility','INFO','Zip version installed:'. $zipVersion );
		} else {			
			$this->setResult( 'ERROR', 'The ZIP utility version info could not be found.', $zipHelp );
			return false;
		}

		// Is the version supported?
		if( version_compare( $zipVersion, '3.0' ) < 0 ) {
			$this->setResult( 'ERROR', 'Unsupported ZIP version (version "'. $zipVersion.'") found. ' .
										'ZIP version 3.0 or higher is expected.', $zipHelp );
			return false;
		}			
		LogHandler::Log( 'zipUtility', 'INFO', 'zip installation checked.' );
		

		// Checking on the unzip installation.
		$cmd = 'unzip -h'; // unzip -v doesn't work for Zip v2.1: Returns garbage which makes the whole health check halts.
		$unzipResult = array();
		exec( $cmd, $unzipResult );
		$unzipResult = implode( PHP_EOL, $unzipResult );
		//////////////////////////////////////////////////////////////////////////////////////////////////////
		///// $unzipResult = "UnZip 5.52 of 28 February 2005, by Info-ZIP.  Maintained by C. Spieler...." ////
		////                                                                                              //// 
		//////////////////////////////////////////////////////////////////////////////////////////////////////
		
		// Generic help(Tip) message for unzip utility.
		if( OS == 'WIN' ) {
			$unzipHelp = ' Please download and install the \'Info-ZIP\' Unzip package from ' .
							'<a href="ftp://ftp.info-zip.org/pub/infozip/win32/unz600xn.exe">info-zip.org</a>.' .
							' In case the package is already installed, please ensure that the Unzip directory is set '.
							'in the environment called \'Path\'.';
		}
		
		if( OS == 'UNIX' || OS == 'LINUX' ) {
			$unzipHelp = 'Please download and install the \'Info-ZIP\' Unzip package from '.
							'<a href="http://www.info-zip.org/UnZip.html">info-zip.org</a>.';
		}
		
		// Is unzip installed?
		if( !$unzipResult ) {
			$this->setResult( 'ERROR', 'The Unzip utility is not found or is not installed.', $unzipHelp );
			return false;
		}
	
		// Is the unzip from Info-ZIP?
		if( stripos( $unzipResult, 'Info-ZIP' ) === false ) {
			$this->setResult( 'ERROR', 'The Unzip utility by \'Info-ZIP\' is not found.', $unzipHelp );
			return false;
		}
		
		// can find the version?
		$matches = array();
		preg_match( '/unzip ([0-9]+\.[0-9]+)/i', $unzipResult, $matches );
		if( isset( $matches[1] ) ) {
			$unzipVersion = $matches[1];
			LogHandler::Log('ZipUtility','INFO','Unzip version installed:'. $unzipVersion );
		} else {
			$this->setResult( 'ERROR', 'The Unzip utility version info could not be found.', $unzipHelp );
			return false;
		}	

		// Is the version supported?
		if( version_compare( $unzipVersion, '5.52' ) < 0 ) {
			$this->setResult( 'ERROR', 'Unsupported Unzip version (version "'. $unzipVersion.'") found. ' .
									'Unzip version 5.52 or higher is expected.', $unzipHelp );									
			return false;
		}		
		LogHandler::Log( 'zipUtility', 'INFO', 'unzip installation checked.' );	
		return true;	
	}
	
	
	/**
	 * Checks if compression method ZIP using command line tool is working.
	 * This functions create a text file, compress (zip) it and extract it.
	 * After extracting, it compares the content whether it is still the same
	 * as before compressing it.
	 *
	 * For Windows:
	 * It does one more extra checking when the zip integration fails:
	 * It checks whether ADOBEDPS_EXPORTDIR is on a UNC path to ensure that
	 * this is not causing the failure.
	 *
	 * Next, it will test whether output file of zip extraction will
	 * overwrites the existing file.
	 * It throws error when the content after extraction is found not to be the same.
	 */
	private function checkZipCommandLineIntegration()
	{
		$zipTestFolder = ADOBEDPS_EXPORTDIR . 'zipTestFolder';		
		$zipDirHelp = 'Please ensure that "'.ADOBEDPS_EXPORTDIR. '" is a valid directory and that it has write access '.
						'for the internet user.';
		
		// Can Health check creates the testing directory?
		if( !FolderUtils::mkFullDir( $zipTestFolder ) ) {
			$this->setResult( 'ERROR', 'Could not create \''. $zipTestFolder.'\' for Zip command line testing.', $zipDirHelp );
			return false;
		}
		
		$txtFile = $zipTestFolder . '/zipTest.txt';		
		if( file_exists( $txtFile ) ) { // is there any left over test file by previous test?
			unlink( $txtFile );
		}

		// Prepare content		
		$testContent = 'Hello World!'; // After compressing the file, the compression file will be extracted to check whether the content is still the same.		
		if( !file_put_contents( $txtFile, $testContent ) ) {
			$this->setResult( 'ERROR', 'Could not create \''.$txtFile.'\' file for Zip command line testing.', $zipDirHelp );
			return false; // no point to continue
		}
		
		$zipFile = $zipTestFolder . '/compressedFile.zip';
		if( file_exists( $zipFile ) ) { // is there any left over zip archive file by previous test?
			unlink( $zipFile );
		}

		// <<--1. Checks if zip file can be created.
		$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility( true );
		// * TRUE because the Health Check wants to ensure that the compression method 
		// in command line tool is working fine. This is needed to produce the folio file
		// that is needed by the Adobe Content Viewer. 
		$zipUtility->createZipArchive( $zipFile ); 	
		$zipUtility->addFile( $txtFile );
		$zipUtility->closeArchive();

		if( !file_exists( $zipFile ) ) { // zip file not created at all?			
			if( OS == 'WIN' && substr( ADOBEDPS_EXPORTDIR, 0, 2 ) == '//' ) { // BZ#27286: If filestore mounted on remote fileshare, it could be UNC path prob.	
				$help = 'UNC paths cannot be used by the Command Processor because this feature has been disabled ' .
						'in the Windows Registry.</br>'. PHP_EOL .
						
						'This feature is required by the Adobe DPS integration because your ADOBEDPS_EXPORTDIR setting points '.
						'to a shared network folder (starting with //).</br>' . PHP_EOL .
						
						'When this feature is not set, the zip.exe and unzip.exe utilities '.
						'(used for creating and extracting the Adobe DPS folio files) will fail.</br></br>' . PHP_EOL .
						PHP_EOL .
						'To fix this, do one of the following: </br>'. PHP_EOL .
						'(1) Map a drive to the UNC location</br>' . PHP_EOL .
						'(2) Disable checking for UNC paths in the registry by doing one of the following:</br>' . PHP_EOL .
						'=> Map the shared network folder to a local drive letter (e.g. \'k:\\\') ' .
						'and adjust the ADOBEDPS_EXPORTDIR setting (e.g. \'k:/AdobeDps/\').</br>'. PHP_EOL .
						'=> Update the registry by running REGEDIT.EXE and create a key named \'DisableUNCCheck\' '.
						'under \'HKEY_CURRENT_USER\Software\Microsoft\Command Processor\' with type \'REG_DWORD\' and '.
						'hex value \'1\'.';				
			} else {
				$help = 'Check the Enterprise Admin Guide for instructions on how to install the ZIP command line tool.';
			}
			$this->setResult( 'ERROR',  'Failed creating the ZIP archive "'.$zipFile .'".' , $help );
			return false;
		}

		$zipTestPassed = true;
		$zipUtility->openZipArchive( $zipFile );
		if( $zipUtility->getFile( basename( $txtFile) ) != $testContent ) {
			$help = 'Check the Enterprise Admin Guide for instructions on how to install the ZIP command line tool.';
			$this->setResult( 'ERROR',  'The ZIP command line integration has failed.' , $help );
			$zipTestPassed = false;
		}
		$zipUtility->closeArchive();
		unlink( $zipFile );
		// Finish checking if zip file can be created. -->>
		
		if( !$zipTestPassed ) { // if zip file cannot be created above, no point continue checking..
			// Do some cleaning before bail out.
			unlink( $txtFile );
			chdir( EXPORTDIRECTORY ); // leave from the zip testing directory, else cannot remove the zip testing dir below.
			rmdir( $zipTestFolder );
			return false;
		}

		// <<--2. Checks if zip extraction overwrites the existing file(s).
		$sourceZipFile = BASEDIR.'/server/plugins/AdobeDps/testsuite/HealthCheck2/compressedFile.zip';
		$zipFile = $zipTestFolder . '/compressedFile.zip';
		copy( $sourceZipFile, $zipFile );

		$txtFile = $zipTestFolder . '/zipTest.txt';
		$zipUtility->openZipArchive( $zipFile );
		$zipUtility->extractArchive( $zipTestFolder );
		$zipUtility->closeArchive();

		$contents = file_get_contents( $txtFile );
		$newTestContent = 'This is content B!';
		$zipOverwriteTest = true;

		if( $contents != $newTestContent ) { // Check once before zipping the file.
			$this->setResult( 'ERROR', 'The UNZIP command did not overwrite the existing text file while unzipping.', $zipDirHelp );
			$zipOverwriteTest = false;
		} else { // When content is the latest one, only proceed with zipping the file.
			$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility( true );
			$zipUtility->createZipArchive( $zipFile );
			$zipUtility->addFile( $txtFile );
			$zipUtility->closeArchive();

			// Check again by opening(unzipping) the zipped file
			$zipUtility->openZipArchive( $zipFile );
			if( $zipUtility->getFile( basename($txtFile) ) != $newTestContent ) {
				$this->setResult( 'ERROR',  'The content is not udpated in the extracted archive file.', $zipDirHelp );
				$zipOverwriteTest = false;
			}
			$zipUtility->closeArchive();
		}				
		// Finish checking if zip extraction overwrites the existing file(s). -->>

		unlink( $zipFile );
		unlink( $txtFile );
		chdir( EXPORTDIRECTORY ); // leave from the zip testing directory, else cannot remove the zip testing dir below.
		rmdir( $zipTestFolder );
		LogHandler::Log('wwtest', 'INFO', 'ZIP integration checked.' );
		if( !$zipOverwriteTest ) {
			return false;	
		}
		return true;
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
	
	/**
	 * Validates the options related to parallel file uploads.
	 *
	 * @since 7.6.7
	 * @return bool TRUE when OK/INFO/WARN. FALSE on ERROR.
	 */
	private function checkParallelUploadSettings()
	{
		require_once BASEDIR.'/config/config_dps.php';
		$help = 'Please check the config_dps.php file.';

		// Check the hidden option, which is optional.
		if( defined( 'PARALLEL_PUBLISHING_ENABLED' ) ) {
			if( gettype( PARALLEL_PUBLISHING_ENABLED ) != 'boolean' ) {
				$msg = 'The PARALLEL_PUBLISHING_ENABLED option should be set to true or false (without quotes). ';
				$this->setResult( 'ERROR', $msg, $help );
				return false;
			}
		}
		
		// Check the max connections setting, which is mandatory and should be [2...25]
		if( !defined( 'PARALLEL_PUBLISHING_MAX_CONNECTIONS' ) ) {
			$msg = 'The PARALLEL_PUBLISHING_MAX_CONNECTIONS option is missing. '.
				'Please merge the shipped config_dps.php file with the installed config_dps.php file. ';
			$this->setResult( 'ERROR', $msg, $help );
			return false;
		}
		
		// Check if the max connections option has correct syntax (integer).
		if( gettype( PARALLEL_PUBLISHING_MAX_CONNECTIONS ) != 'integer' ) {
			$msg = 'The PARALLEL_PUBLISHING_MAX_CONNECTIONS option should be a whole number (without quotes). ';
			$this->setResult( 'ERROR', $msg, $help );
			return false;
		}
		
		// Check the max connection option, which should be positive and > 1.
		if( PARALLEL_PUBLISHING_MAX_CONNECTIONS <= 1 ) {
			$msg = 'The PARALLEL_PUBLISHING_MAX_CONNECTIONS option should be a positive number larger than 1. ';
			// Detect if admin tries to use the option to disable the parallel upload feature
			// and instruct how to use the hidden option instead.
			if( PARALLEL_PUBLISHING_MAX_CONNECTIONS == 0 || PARALLEL_PUBLISHING_MAX_CONNECTIONS == 1 ) {
				$msg .= 'If you want to disable the parallel upload feature, please add the PARALLEL_PUBLISHING_ENABLED '.
					'option to the config_dps.php file and set it to false.';
			}
			$this->setResult( 'ERROR', $msg, $help );
			return false;
		}
		
		// Check the max connections, which should be around 5. Warn on high values (>25).
		if( PARALLEL_PUBLISHING_MAX_CONNECTIONS > 25 ) {
			$msg = 'The PARALLEL_PUBLISHING_MAX_CONNECTIONS option is set to a high value ('.PARALLEL_PUBLISHING_MAX_CONNECTIONS.'). '.
				'This could result in a heavy network load or make the server run out of socket connections. '.
				'Please read the instructions in the config_dps.php file and enter a lower value. ';
			$this->setResult( 'WARN', $msg, $help );
		}

		return true;
	}

	/**
	 * Checks if the Free/Paid flag is synchronized between Adobe Dps and Enterprise. If not, an error message is set
	 * with a link to an update page. 
	 * @return boolean Systems are synchorinized (true/false).
	 */
	private function syncFreePaidPerDevice()
	{
		require_once dirname(__FILE__).'/SyncFreePaidPerDevice.class.php'; 

		if ( SyncFreePaidPerDevice::isUpdated() ) {
			return true;
		} else {
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$syncFile = dirname(__FILE__).'/syncFreePaidPerDevice.php';
			$syncUrl = WW_Utils_UrlUtils::fileToUrl( $syncFile, 'server', false );
			$help = ' Please run the <a href="'.$syncUrl.'" target="_blank">Synchronize Free/Paid Setting</a> page that will synchronize the setting between Adobe Dps and Enterprise.';
			$this->setResult( 'ERROR', 'Free/Paid setting is not yet synchronized.', $help  );
			return false;
		}
	}	

	/**
	 * Checks if the HTML Resource cache is copied from the export folder to the persistent location. If not, an error
	 * message is set with a link to an update page. 
	 * @return boolean Folders are copied (true/false).
	 */
	private function isHTMLResourcesCacheCopied()
	{
		require_once dirname(__FILE__).'/CopyHTMLResourceCache.class.php'; 
		$copyFolder = new CopyHTMLResourceCache(); 
		if ( $copyFolder->isCopyNeeded() ) {
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$copyFile = dirname(__FILE__).'/copyHTMLResourceCache.php';
			$copyUrl = WW_Utils_UrlUtils::fileToUrl( $copyFile, 'server', false );
			$help = ' Please run the <a href="'.$copyUrl.'" target="_blank">Copy HTML Resource cache folders</a> page that will copy the cache folders from '.ADOBEDPS_EXPORTDIR.' to '.ADOBEDPS_PERSISTENTDIR.'.';
			$this->setResult( 'ERROR', 'HTML Resource cache folders not yet copied to persisitent location.', $help  );
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if 'PROTECT' custom property has been changed into 'ARTICLE_ACCESS',
	 * when it is not yet changed, update is needed; otherwise no update needed.
	 *
	 * @return bool True when the property has already been updated; False otherwise.
	 */
	private function checkArticleAccessProp()
	{
		require_once dirname(__FILE__).'/AddArticleAccessProp.class.php';

		if ( AddArticleAccessProp::isUpdated() ) {
			return true;
		} else {
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$addPropFile = dirname(__FILE__).'/AddArticleAccessProp.php';
			$addPropUrl = WW_Utils_UrlUtils::fileToUrl( $addPropFile, 'server', false );
			$help = ' Please run the <a href="'.$addPropUrl.'" target="_blank">article_access custom property</a> page that will convert the custom property \'PROTECT\' into \'ARTICLE_ACCESS\'.';
			$this->setResult( 'ERROR', 'Custom property \'PROTECT\' is not yet converted into \'ARTICLE_ACCESS\'.', $help  );
			return false;
		}
	}

	/**
	 * In version 9.7 a new field is introduced 'Kicker'. This property replaces the 'slugline' that is used as 'Kicker'
	 * field until now. The information stored in the 'slugline' field can be migrated to the new custom property
	 * 'Kicker'. After the migration the smart_config table is updated. This function checks if the upgrade is already
	 * executed and, if not, the user will get a link to the page that guides him through the upgrade.
	 *
	 * @return bool True if the migration is already done, else false.
	 * @since 9.7.0
	 */
	private function checkMigrationKicker()
	{
		require_once dirname(__FILE__).'/MigrateKickerContent.class.php';

		if ( MigrateKickerContent::isUpdated() ) {
			return true;
		} else {
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$addPropFile = dirname(__FILE__).'/MigrateKickerContent.php';
			$addPropUrl = WW_Utils_UrlUtils::fileToUrl( $addPropFile, 'server', false );
			$help = ' Please run the <a href="'.$addPropUrl.'" target="_blank">kicker custom property migration</a> Page that handles the migration of the \'slugline\' property into \'KICKER\'.';
			$this->setResult( 'WARN', 'Migration of custom property \'KICKER\' is not yet handled. ', $help  );
			return false;
		}
	}

}
