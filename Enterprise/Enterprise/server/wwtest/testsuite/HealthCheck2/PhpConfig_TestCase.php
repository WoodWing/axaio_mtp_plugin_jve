<?php
/**
 * PhpConfig TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_PhpConfig_TestCase extends TestCase
{
	public function getDisplayName() { return 'PHP Configuration'; }
	public function getTestGoals()   { return 'Checks if settings in config.php and configserver.php files are correct and all 3rd-party libraries are installed. '; }
	public function getTestMethods() { return 'Checks if file paths options have proper slashes and exist on disk, etc, etc.'; }
    public function getPrio()        { return 2; }
	
	const PACKAGE_HELP = 'Check the original Enterprise Server installation package.';

	/** @var WW_Utils_TestSuite */
	private $utils;
	
	final public function runTest()
    {
    	// BZ#23964
    	// OUTPUTDIRECTORY should be checked before the full HealthCheck, this is because when there's any 
    	// error in the HealthCheck test, the error will not be able to be written in the log folder;
    	// hence, causing fatal error in IIS.
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		if( !$this->utils->validateDefines( $this, array('OUTPUTDIRECTORY'), 
									'configserver.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
			return;
		}
		if( OUTPUTDIRECTORY != '' ) {
			if( !$this->utils->validateFilePath( $this, OUTPUTDIRECTORY, self::PACKAGE_HELP, true, 'ERROR',
				WW_Utils_TestSuite::VALIDATE_PATH_ALL & ~WW_Utils_TestSuite::VALIDATE_PATH_NO_SLASH ) ) { // must have slash at end
					return;				
				}
		}

    	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// CONFIG.PHP OPTIONS
    	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

		$help = 'Check your config.php file.';
		$serverscriptpath = $_SERVER['SCRIPT_FILENAME'];
		$serverscriptpath = str_replace('\\', '/', $serverscriptpath);
		if(!stristr($serverscriptpath, BASEDIR)) {
			$this->setResult( 'ERROR',  'The BASEDIR setting seems to be incorrect '.
				'The BASEDIR config is the location on your hard drive where your Enterprise Server is located.', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'BASEDIR checked: '.BASEDIR);

		$serverscriptname = $_SERVER['SCRIPT_NAME'];
		$serverscriptname = str_replace(' ', '%20', $serverscriptname);
		if(!stristr($serverscriptname, INETROOT) || strpos(INETROOT,'/') !== 0 || strrpos(INETROOT,'/') === (strlen(INETROOT)-1) ){
			$this->setResult( 'ERROR', 'The INETROOT setting seems to be incorrect '.
				'The INETROOT is the location where your Enterprise Server is located on the web. '.
				'The server address (such as "http://localhost") should be excluded, for example: "/Enterprise". '.
				'It must start with a slash, but should NOT end with a slash.', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'INETROOT checked: '.INETROOT);

		if( !defined('LOCALURL_ROOT')) {
			$this->setResult( 'ERROR', 'The LOCALURL_ROOT is not defined.', $help );
			return;
		}
		if(strrpos(LOCALURL_ROOT,'/') === (strlen(LOCALURL_ROOT)-1) ){
			$this->setResult( 'ERROR', 'The LOCALURL_ROOT setting should not end with a slash. '.
				'Current value is: "'.LOCALURL_ROOT.'".', $help );
			return;
		}
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		if( !WW_Utils_UrlUtils::isResponsiveUrl( LOCALURL_ROOT.INETROOT.'/index.php?test=ping' ) ) {
			$this->setResult( 'ERROR', 'It seems to be impossible to connect to "'.LOCALURL_ROOT.'". '.
				'Please check your LOCALURL_ROOT setting and make sure the server can access that URL.', $help );
			return;
		}		
		LogHandler::Log('wwtest', 'INFO', 'LOCALURL_ROOT checked: '.LOCALURL_ROOT);
		
    	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// LIBRARIES
    	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		// Test if we can find our own JavaChart lib:
		if( !file_exists(BASEDIR.'/server/javachart/jars') ) {
			$this->setResult( 'ERROR', 'PHP library "JavaChart" not installed, should be present at: '.
				BASEDIR."/server/javachart", self::PACKAGE_HELP );
			return;
		}		
		LogHandler::Log('wwtest', 'INFO', 'JavaChart library checked');
		
		
    	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// CONFIGSERVER.PHP OPTIONS
    	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

		$this->testHtmlLinkFiles();
		$this->testEnterpriseProxy();
		$this->testObsoleteOptions();
		$this->testExtensionMap();
	    $this->testRemoteLocations();
	    $this->testClientServerFeatures();
	}
	
	/**
	 * Checks defines used for logging.
	 */
	private function testDebugOptions()
	{
		if( !$this->utils->validateDefines( $this, array('DEBUGLEVELS', 'PROFILELEVEL', 'LOGSQL', 'LOGFILE_FORMAT'), 
									'configserver.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
			return;
		}
		$debugLevels = unserialize(DEBUGLEVELS);
		if( !isset($debugLevels['default']) ) {
			$this->setResult( 'ERROR', 'For the DEBUGLEVELS option, the "defaults" key is missing. Please add.', self::PACKAGE_HELP );
		}
		foreach( $debugLevels as $debugLevel ) {
			if( !LogHandler::isValidLogLevel($debugLevel) ) {
				$this->setResult( 'ERROR', 'For the DEBUGLEVELS option an unsupported value "'.$debugLevel.'" is set.', self::PACKAGE_HELP );
			}
		}
		if( !defined('TESTSUITE') ) {
			$help = 'Since v7.0, the TestSuite (at wwtest page) needs to have a TESTSUITE option defined. '.
				'Please merge the option from the original server package into your configserver.php file.';
			$this->setResult( 'ERROR', 'The TESTSUITE option is not defined.', $help );
			return;
		}
	}
		
	/**
	 * Checks the HTMLLINKFILES option.
	 */
	private function testHtmlLinkFiles()
	{
		if( !defined('HTMLLINKFILES') ) {
			$help = 'Since Enterprise v8.0, the HTMLLINKFILES option has to be defined as either True or False. ' . 
					'Please merge the option from the orginal server package into your configserver.php file.';
			$this->setResult( 'ERROR', 'The HTMLLINKFILES option is not defined.', $help );
			return;
		} else { // Check for the HTMLLINKFILES value
			if( HTMLLINKFILES !== true && HTMLLINKFILES !== false ) {
				$help = 'Boolean value True or False is expected.';
				$this->setResult( 'ERROR', 'For the HTMLLINKFILES option, an unsupported value "'.HTMLLINKFILES.'" is set.', $help );
				return;
			}
			// Obsolete since v9.2, show warning to advice it to be turned off
			if( HTMLLINKFILES == true ) {
				$this->setResult( 'WARN', BizResources::localize('HTML_LINK_FILES_OBSOLETE') );
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'HTMLLINKFILES option checked');
	}
	
	/**
	 * Checks the ENTERPRISE_PROXY option.
	 */
	private function testEnterpriseProxy()
	{
		if( defined('ENTERPRISE_PROXY') && ENTERPRISE_PROXY != '' ) {
			$configs = unserialize( ENTERPRISE_PROXY );
			if( $configs ) {			
				if( !isset( $configs['proxy_host'] ) ) { // Mandatory field.
					$help = 'Please add "proxy_host" into ENTERPRISE_PROXY option in configserver.php.';
					$this->setResult( 'ERROR', 'The option ENTERPRISE_PROXY is defined but there is no setting for proxy_host.', $help );
					return;
				}
				
				$proxyFields = array( 'proxy_host', 'proxy_port', 'proxy_user', 'proxy_pass', 'proxy_auth' );
				$shouldNotExists = array();				
				foreach( $configs as $config => $value ) { // from configserver.php
					if( !in_array( $config, $proxyFields ) ) {
						$shouldNotExists[] = $config; // just continue to check, raise error below
					} else { // Further validate the value of the proxy field key.
						if( empty( $value ) ) {
							$help = 'Please check ENTERPRISE_PROXY option in configserver.php and fill in a valid value.';
							$this->setResult( 'ERROR', 'The setting "'.$config.'" is configured but it has an empty value.', $help );
							return;
						}
						if( $config == 'proxy_port' ) {
							if( !is_int( $value ) ){
								$help = 'Please check ENTERPRISE_PROXY option in configserver.php.';
								$this->setResult( 'ERROR', 'The setting "proxy_port" should be an integer.', $help );
								return;
							}
						}					
					}					
				}
				
				if( $shouldNotExists ) {
					$help = 'The allowed/valid settings are: "'. implode( ',', $proxyFields ) .'"';
					$this->setResult( 'ERROR', 'The settings "'. implode( ',', $shouldNotExists ).'" in option ENTERPRISE_PROXY is/are not valid.', $help );
					return;
				}
			}			
		}
		LogHandler::Log('wwtest', 'INFO', 'ENTERPRISE_PROXY option checked');
	}
	
	/**
	 * Checks if there are some obsoleted options defined.
	 */
	private function testObsoleteOptions()
	{
		$versionHelp = 'Please use "Create Permanent Version" options on the Workflow Maintenance page.';
		$help = 'Check your configserver.php file and remove the obsoleted option.';
		$obsoletedDefines = array( 
			array( 'name' => 'CREATEVERSION_ONSTATECHANGE', 'help' => $versionHelp ),
			array( 'name' => 'SAVEFIRST_ARTICLE_VERSION', 'help' => $versionHelp ),
			array( 'name' => 'SAVEFIRST_IMAGE_VERSION',   'help' => $versionHelp ),
			array( 'name' => 'SAVEFIRST_VIDEO_VERSION',   'help' => $versionHelp ),
			array( 'name' => 'SAVEFIRST_AUDIO_VERSION',   'help' => $versionHelp ),
			array( 'name' => 'SAVEFIRST_LAYOUT_VERSION',  'help' => $versionHelp ),
			array( 'name' => 'SAVEFIRST_LIBRARY_VERSION', 'help' => $versionHelp ),
			array( 'name' => 'WITH_PLANNEDPAGERANGE',     'help' => 'The database model has the PlannedPageRange field added to the smart_objects and smart_deletedobjects tables, so there is no need for this option any longer.' ),
			array( 'name' => 'WEBEDIT_MAXELEMENTS',       'help' => 'Since v7.0, the Web Editor always runs in Bulk Mode; When an article has multiple components, the pane at left side is shown (which alows switching between components). For single component articles, the pane is always hidden.' ), // BZ#8761
			array( 'name' => 'MIMEMAP',	'help' => 'This option is superseded by EXTENSIONMAP.' ),
			array( 'name' => 'DEBUGLEVEL',	'help' => 'This option is superseded by DEBUGLEVELS.' ),
			array( 'name' => 'NETWORK_DOMAINS',	'help' => 'This option is no longer used.' ),
			array( 'name' => 'ATTACHSTORAGE',	'help' => 'This option is no longer used, as there is only one type of storage supported which is FILE' ),

		);
		foreach( $obsoletedDefines as $opt ) {
			if( defined( $opt['name'] ) ) {
				$this->setResult( 'WARN', 'The option '.$opt['name'].' is obsoleted. '.$opt['help'].'', $help );
			}
		}
		
		$help = 'Check your configserver.php file for the SERVERFEATURES setting and remove the obsoleted option from that list.';
		$deprecatedFeatures = array( 
			array( 'name' => 'ServerCreateImagePreview', 'help' => 'This option is no longer used. Please enable/disable the preview Server Plug-ins instead.' ),
		);
		require_once BASEDIR.'/server/bizclasses/BizSettings.class.php';
		foreach( $deprecatedFeatures as $opt ) {
			if( BizSettings::isFeatureEnabled( $opt['name'] ) ) {
				$this->setResult( 'WARN', 'The server feature '.$opt['name'].' is obsoleted. '.$opt['help'].'', $help );
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'Obsoleted options checked');
	}
	
	/**
	 * Checks the EXTENSIONMAP for several mime types if they have the correct Enterprise object type.
	 * These object types have been newly introduced: Archive, Presentation and Spreadsheet and the mime types
	 * need to be mapped correctly.
	 */
	private function testExtensionMap()
	{
	    require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
	    // Mime types that has received new Enterprise Object Type.
	    $newObjectTypes = array(
			'.ppt' => array( 'application/vnd.ms-powerpoint', 'Presentation' ),
			'.pptx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'Presentation' ),
			'.pptm' => array( 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',              'Presentation' ),
			'.ppsx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',  'Presentation' ),
			'.ppsm' => array( 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',                 'Presentation' ),
			'.sldx' => array( 'application/vnd.openxmlformats-officedocument.presentationml.slide',      'Presentation' ),
			'.sldm' => array( 'application/vnd.ms-powerpoint.slide.macroEnabled.12',                     'Presentation' ),
			'.odp' => array( 'application/vnd.oasis.opendocument.presentation',          'Presentation' ),
			'.key' => array( 'application/x-apple-keynote', 'Presentation' ),
			'.xls' => array( 'application/vnd.ms-excel',      'Spreadsheet' ),
			'.xlsx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',       'Spreadsheet' ),
			'.xlsm' => array( 'application/vnd.ms-excel.sheet.macroEnabled.12',                          'Spreadsheet' ),
			'.xltx' => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',    'Spreadsheet' ),
			'.xltm' => array( 'application/vnd.ms-excel.template.macroEnabled.12',                       'Spreadsheet' ),
			'.xlsb' => array( 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',                   'Spreadsheet' ),
			'.ods' => array( 'application/vnd.oasis.opendocument.spreadsheet',           'Spreadsheet' ),
			'.ots' => array( 'application/vnd.oasis.opendocument.spreadsheet-template',  'Spreadsheet' ),
			'.numbers' => array( 'application/x-apple-numbers', 'Spreadsheet' ),
		 );
	    // Checks if the MimeTypes have been mapped to the correct Object Type in 'EXTENSIONMAP' option.
	    $definedExtMap = MimeTypeHandler::getExtensionMap();
		foreach( $newObjectTypes as $expectedFileExtension => $mimeAndObjType ) {
			$definedExtension = MimeTypeHandler::mimeType2FileExt( $mimeAndObjType[0], $mimeAndObjType[1] );
			if( $definedExtension != $expectedFileExtension ) {
				$help = 'Please make sure it has the following entry:' . PHP_EOL .
					'\''.$expectedFileExtension.'\' => array( \''.$mimeAndObjType[0].'\', \''.$mimeAndObjType[1].'\'),';
				if( isset($definedExtMap[$expectedFileExtension] )) {
					$fileExtensionForErrMsg = $expectedFileExtension;
				} else {
					$fileExtensionForErrMsg = $definedExtension;
				}
				$this->setResult( 'ERROR', 'The file extension "'.$fileExtensionForErrMsg.'" defined in the ' .
					'EXTENSIONMAP option of the configserver.php file has an invalid ObjectType.', $help );
				return;
			}
		}
	    LogHandler::Log('wwtest', 'INFO', 'EXTENSIONMAP option with the new ObjectType checked.');
    }
    
	/**
	 * Checks for the REMOTE_LOCATIONS_INCLUDE and REMOTE_LOCATIONS_EXCLUDE values.
	 */
    private function testRemoteLocations()
    {
		require_once BASEDIR.'/server/utils/IpAddressRange.class.php';
		if( $this->utils->validateDefines( $this, array('REMOTE_LOCATIONS_INCLUDE', 'REMOTE_LOCATIONS_EXCLUDE'), 
						'configserver.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
			$ranges = unserialize( REMOTE_LOCATIONS_INCLUDE );
			foreach( $ranges as $range ) {
				if( !WW_Utils_IpAddressRange::isValidRange( $range ) ) {
					$this->setResult( 'ERROR', 'The IP range "'.$range.'" specified in the REMOTE_LOCATIONS_INCLUDE '.
						'option is not valid. See configserver.php file for more information. ' );
				}
			}
			$ranges = unserialize( REMOTE_LOCATIONS_EXCLUDE );
			foreach( $ranges as $range ) {
				if( !WW_Utils_IpAddressRange::isValidRange( $range ) ) {
					$this->setResult( 'ERROR', 'The IP range "'.$range.'" specified in the REMOTE_LOCATIONS_EXCLUDE '.
						'option is not valid. See configserver.php file for more information. ' );
				}
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'Checked REMOTE_LOCATIONS_INCLUDE and REMOTE_LOCATIONS_EXCLUDE options.');
	}
	
	/**
	 * Checks for the CLIENTFEATURES value.
	 */
    private function testClientServerFeatures()
	{
		// Check the structure of the CLIENTFEATURES option.
		if( $this->utils->validateDefines( $this, array('CLIENTFEATURES'), 
						'configserver.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
			$help = 'Check your configserver.php file for the CLIENTFEATURES setting.';
			$clientFeatures = unserialize( CLIENTFEATURES );
			if( !is_array($clientFeatures) ||
				!is_array($clientFeatures['InDesign']) ||
				!is_array($clientFeatures['InDesign']['local']) ||
				!is_array($clientFeatures['InDesign']['remote']) ||
				!is_array($clientFeatures['InDesign Server']) ||
				!is_array($clientFeatures['InDesign Server']['default']) ||
				!is_array($clientFeatures['InDesign Server']['IDS_AUTOMATION']) ) {
					$this->setResult( 'ERROR', 'The CLIENTFEATURES setting does not have the correct structure. '.
						'Expected structure is: '.
						"<pre>define ('CLIENTFEATURES', serialize(array(".PHP_EOL.
						"	'InDesign' => array(".PHP_EOL.
						"		'local' => array(".PHP_EOL.
						"		),".PHP_EOL.
						"		'remote' => array(".PHP_EOL.
						"		),".PHP_EOL.
						"	),".PHP_EOL.
						"	'InDesign Server' => array(".PHP_EOL.
						"		'default' => array(".PHP_EOL.
						"		),".PHP_EOL.
						"		'IDS_AUTOMATION' => array(".PHP_EOL.
						"		),".PHP_EOL.
						"	),".PHP_EOL.
						")));</pre>", $help );
			}
			foreach( $clientFeatures as $clientName => $subEntries ) {
				foreach( $subEntries as $subEntry => $clientFeatureList ) {
					foreach( $clientFeatureList as $clientFeature ) {
						if( !is_object( $clientFeature ) || get_class( $clientFeature ) != 'Feature' ) {
							$this->setResult( 'ERROR', "The CLIENTFEATURES->{$clientName}->{$subEntry} setting contains wrong values. ".
								'Expected to have "new Feature()" items only. Expected structure is: '.
								"<pre>define ('CLIENTFEATURES', serialize(array(".PHP_EOL.
								"	...".PHP_EOL.
								"	'$clientName' => array(".PHP_EOL.
								"		'$subEntry' => array(".PHP_EOL.
								"			new Feature( '...' ),".PHP_EOL.
								"			...".PHP_EOL.
								"		),".PHP_EOL.
								"		...".PHP_EOL.
								"	),".PHP_EOL.
								"	...".PHP_EOL.
								")));</pre>", $help );
						}
						break 3; // avoid cascade of errors
					}
				}
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'The structure of the CLIENTFEATURES option has been checked.');
		
		// Check if all CLIENTFEATURES options are moved away from SERVERFEATURES.
		$help = 'Check your configserver.php file for the SERVERFEATURES and CLIENTFEATURES settings.';
		$movedFeatures = array( 
			'CreatePageEPS', 'CreatePageEPSOnProduce', 'CreatePagePDF', 
			'CreatePagePDFOnProduce', 'CreatePagePreview', 'CreatePagePreviewOnProduce', 
			'PagePreviewQuality', 'PagePreviewResolution' 
		);
		require_once BASEDIR.'/server/bizclasses/BizSettings.class.php';
		foreach( $movedFeatures as $movedFeature ) {
			if( BizSettings::isFeatureEnabled( $movedFeature ) ) {
				$this->setResult( 'ERROR', 'The feature '.$movedFeature.' should be moved from the SERVERFEATURES setting to the CLIENTFEATURES setting.', $help );
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'The SERVERFEATURES has been checked for options that should be moved to CLIENTFEATURES.');
	}
}
