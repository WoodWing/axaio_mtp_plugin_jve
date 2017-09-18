<?php

/**
 * Automated installation script to setup a new Enteprise database.
 * After installation, it uses the Enterprise/config/WWActivate.xml file to activate
 * all licenses configured in that file. Then it registers the registers all server 
 * plug-ins (that were found at the file system) at the database.
 *
 * This script should be called through CLI (command line).
 * Returns 0 on success or 1 on failure. 
 * Errors are logged in Enterprse_AutoInstallDb::AUTO_INSTALL_DB_LOG.
 */

require_once dirname(__FILE__).'/../Enterprise/config/config.php';

$installer = new Enterprse_AutoInstallDb();
$mode = isset($argv[1]) ? $argv[1] : '';
$scriptOk = $installer->run( $mode );
exit( $scriptOk ? 0 : 1 ); // 0 = ok, 1 = failed

class Enterprse_AutoInstallDb
{
	const AUTO_INSTALL_DB_LOG = '/FileStores/EntMig/_SYSTEM_/Temp/auto_install_db.txt';
	
	/**
	 * Installs the database and activates the licenses.
	 *
	 * @param string $mode What step to install. Empty to install all steps. Values: database, license, plugins, jobs
	 * @return bool Whether or not the installation was successful.
	 */
	public function run( $mode )
	{
		// Whether or not the script could run without errors.
		$this->scriptOk = true; 

		// Delete results of previous runs.
		if( file_exists( self::AUTO_INSTALL_DB_LOG ) && !unlink( self::AUTO_INSTALL_DB_LOG ) ) {
			$this->scriptOk = false;
			$this->logMessage( 'ERROR: Could not remove log file: '.self::AUTO_INSTALL_DB_LOG );
		}
		
		// Validate the given mode.
		if( !in_array( $mode, array( '', 'database', 'license', 'plugins' ) ) ) {
			$this->scriptOk = false;
			$this->logMessage( 'ERROR: Unknown mode: '.$mode );
		}
		
		// Install the database.
		if( $this->scriptOk && ($mode=='' || $mode=='database') ) {
			$this->installDb();
		}
		
		// Activate the licenses.
		if( $this->scriptOk && ($mode=='' || $mode=='license') ) {
			$this->activateLicenses();
		}
		
		// Register server plug-ins at DB.
		if( $this->scriptOk && ($mode=='' || $mode=='plugins') ) {
			$this->registerServerPlugins();
		}
		
		// TODO: Register server jobs at DB.

		return $this->scriptOk;
	}
	
	/**
	 * Installs the database. It dynamically runs through the installation phases.
	 * This way it can be used for DB upgrades as well as clean DB installations.
	 * And, when in the future phases are added, this script does not need to be adjusted.
	 */
	private function installDb()
	{
		require_once BASEDIR.'/server/dbscripts/DbInstaller.class.php';
		$checkSystemAdmin = array( $this, 'checkSystemAdmin' ); // callback function
		$installer = new WW_DbScripts_DbInstaller( $checkSystemAdmin );
		
		$nextPhase = null;
		do {
			$installer->run( $nextPhase );
		
			$reportItems = $installer->getReport()->get();
			if( $reportItems ) foreach( $reportItems as $reportItem ) {
				switch( $reportItem->severity ) {
					case 'FATAL':
						$this->scriptOk = false;
						$this->logReportItem( $reportItem );
						break 2; // fatal means that we can not continue, so we quit entirely
					break;
					case 'ERROR':
						$this->scriptOk = false;
						$this->logReportItem( $reportItem );
					break;
					case 'WARN':
					case 'INFO':
					default:
						$this->logReportItem( $reportItem );
					break;
				}
			}
			
			$nextPhases = array_keys( $installer->getNextPhases() );
			$nextPhase = count($nextPhases) > 0 ? $nextPhases[0] : null;
		} while( $nextPhase );
	}	

	/**
	 * Installs licenses from WWActivate.xml file.
	 */
	private function activateLicenses()
	{
		require_once BASEDIR.'/server/utils/license/StealthInstaller.class.php';
		$installer = new WW_Utils_License_StealthInstaller();
		if( $installer->canAutoActivate() ) {
			$installer->installProductLicenses(true);
		} else {
			$this->scriptOk = false;
			$this->logMessage( 'ERROR: No Enterprise/config/WWActivate.xml file installed.' );
		}
	}	
	
	/**
	 * Register server plug-ins (installed at file system) into the database.
	 */
	private function registerServerPlugins()
	{
		try {
			// Scan plugins at config- and server- folders and save changed data in DB
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$pluginInfos = array(); // PluginInfoData
			$connInfos   = array(); // ConnectorInfoData
			$pluginObjs  = array(); // EnterprisePlugin
			$connObjs    = array(); // EnterpriseConnector
			$pluginErrs  = array(); // plugins (messages) that are in error
			BizServerPlugin::registerServerPlugins( $pluginObjs, $pluginInfos, $connObjs, $connInfos, $pluginErrs );

			// Specific case; Validate custom admin properties provided by AdminProperties connectors.
			// This is done here (instead of letting each connector checking itself at isInstalled) to
			// allow checking duplicate properties and doing checks all in the way (more robust and reliable).
			require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
			BizAdmProperty::validateAndInstallCustomProperties( $pluginErrs );
		
			// Same for custom Object properties.
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			BizProperty::validateAndInstallCustomProperties( null, $pluginErrs, true );
		
		} catch( BizException $e ) {
			$this->scriptOk = false;
			$this->logMessage( 'ERROR: '.$e->getMessage().PHP_EOL.$e->getDetail() );
		}
	}
	
	/**
	 * Adds a given report item to the self::AUTO_INSTALL_DB_LOG file.
	 * The message, detail and tip of the report item are logged.
	 *
	 * @param WW_Utils_ReportItem $reportItem The item to log.
	 */
	private function logReportItem( WW_Utils_ReportItem $reportItem )
	{
		$message = $reportItem->severity . ': '.$reportItem->message.PHP_EOL;
		if( $reportItem->detail ) {
			$message .= 'Detail: '.$reportItem->detail.PHP_EOL;
		}
		if( $reportItem->help ) {
			$message .= 'Tip:'.$reportItem->help.PHP_EOL;
		}
		$this->logMessage( $message );
	}
	
	/**
	 * Adds a given message to the self::AUTO_INSTALL_DB_LOG file.
	 *
	 * @param string $message The text to log.
	 */
	private function logMessage( $message )
	{
		if( ($fp = fopen( self::AUTO_INSTALL_DB_LOG, 'a' )) ) {
			fwrite( $fp, $message . PHP_EOL );
			fclose( $fp );
		}
	}
	
	/**
	 * Called when the is no DB installation, nor a DB upgrade to be done.
	 * Then the installer will end with the message that the DB is already up-to-date.
	 * In that case there is nothing to do and we do not continue, so there no reason to authorize.
	 */
	public function checkSystemAdmin()
	{
		// Nothing to do. See function header.
	}
}