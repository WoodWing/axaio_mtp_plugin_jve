<?php

/**
 * Installer for Enterprise databases. 
 * Can install a new database or upgrade an existing database.
 *
 * It uses a report to collect all messages and problems during installation.
 * There are no errors logged when those are expected to be solved by the user.
 * Instead, the reported errors are shown on screen.
 *
 * The report is also used to indicate if the next step in the installation can be
 * taken, or there is a need from user to solve problems first. Those problems are
 * indicated with the FATAL severity. The canContinue() function is called to detect this.
 *
 * When there is no DB engine connected or no DB table space created yet, this installer
 * needs to act smooth without throwing nor logging errors all over the shop.
 *
 * Before v9.0 there was just a dbadmin.php module that did it all. Since 9.0 its logics
 * are taken out and moved to this class. The dbadmin.php module does UI only. Reason to  
 * split was not only to have a MVC model in place, but also to call the installer from  
 * command prompt as used for automated build tests.
 *
 * @package 	Enterprise
 * @subpackage 	dbscripts
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class WW_DbScripts_DbInstaller
{
	const MINUPDATEVERSION = 800; // To be updated when SQL update scripts for this version are no longer provided
	const DBSCRIPTPATH = '/server/dbscripts/';

	/**
	 * @var int The database model version (taken from smart_config table) of the current installation
	 */
	private $flatInstalledVersion = null; 
	
	/**
	 * @var int The database model version (taken from serverinfo.php) we want to install or update to
	 */
	private $flatWantedDbVersion = null;
	
	/**
	 * @var boolean Whether or not the database model needs to be upgraded.
	 */
	private $dbModelUpgrade = false;
	
	/**
	 * @var boolean Whether or not the database needs a major update, such as 8.2 => 9.0.
	 */
	private $majorUpdate = false;

	/**
	 * @var boolean Whether or not the database needs a clean/full installation.
	 */
	private $newInstallation = false;

	/**
	 * @var boolean Whether or not could connect to DB.
	 */
	private $dbConnectError = false;
	
	/**
	 * @var boolean Whether or not the data update modules needs to be run (after the database model upgrade).
	 */
	private $dbDataUpgrade = false;

	/**
	 * @var string[] List of SQL scripts that are needed to install or upgrade the database model.
	 */
	private $dbModelScripts = array();

	/**
	 * @var string[] List of scripts that are needed to update data within the database.
	 */
	private $dbDataUpgradeScripts = array();

	/**
	 * @var WW_Utils_Report Collection of operation results (errors, warning, infos, etc).
	 */
	private $report = null;

	/**
	 * @var WW_Utils_Report Collection of SQL operations that were ran against DB.
	 */
	private $sqlReport = null;
	
	/**
	 * @var callable Callback function to validate whether or not the system admin user is logged. Only called when not installing/upgrading the DB.
	 */
	private $checkSystemAdmin = null;

	/**
	 * Retrieves a list of SQL scripts that are needed to install the database.
	 * @return string[]
	 */
	public function getAllSqlScripts()
	{
		if( $this->canContinue() && $this->phase == 'connect_db' ) {
			$sqlScripts = $this->dbModelScripts;
			if ( $this->dbDataUpgrade ) {
				$sqlScripts = array_merge( $sqlScripts, $this->dbDataUpgradeScripts );
			}
		} else {
			// On failure of current phase are after install/update we're not gonna 
			// tell which scripts needs to be run for the next phase.
			$sqlScripts = array();
		}
		return $sqlScripts;
	}

	/**
	 * Retrieves a collection of operation results (errors, warning, infos, etc).
	 * @return WW_Utils_Report
	 */
	public function getReport()
	{
		return $this->report;
	}

	/**
	 * Retrieves a collection of SQL operations that were ran against DB.
	 * @return WW_Utils_Report
	 */
	public function getSqlReport()
	{
		return $this->sqlReport;
	}

	/**
	 * Initializes the installer.
	 */
	public function __construct( $checkSystemAdmin )
	{
		require_once BASEDIR.'/server/utils/Report.class.php';
		$this->report = new WW_Utils_Report();
		$this->sqlReport = new WW_Utils_Report( $checkSystemAdmin );
		$this->checkSystemAdmin = $checkSystemAdmin;
	}
	
	/**
	 * Runs the installer for a given phase. Use getNextPhases() to find out
	 * if there are more phases to run to complete the installation.
	 *
	 * @param string $phase 'connect_db', 'install_db', 'update_db' or 'goto_licenses'.
	 */
	public function run( $phase )
	{
		$this->report->clearReport();
		$this->sqlReport->clearReport();

		$this->phase = is_null($phase) ? 'connect_db' : $phase;
		switch( $this->phase ) {
			case 'connect_db':
			case 'install_db':
			case 'update_db':
				$this->installDatabase();
			break;
			
			case 'goto_licenses':
			break;
			
			default:
				LogHandler::Log( 'DbInstaller', 'ERROR', 'Unknown phase: '.$this->phase );
			break;
		}
	}
	
	/**
	 * Determines which phase(s) can be entered after the current phase.
	 *
	 * @return array Keys are internal phase names, Values are the localized display names.
	 */
	public function getNextPhases()
	{
		$phases = array();
		if( $this->dbConnectError ) {
			$phases['connect_db'] = $this->localizePhase( 'connect_db' );
		} else {
			switch( $this->phase ) {
				case 'connect_db':
					if( $this->newInstallation ) {
						$phases['install_db'] = $this->localizePhase( 'install_db' );
					} else if( $this->dbModelUpgrade || $this->dbDataUpgrade ) {
						$phases['update_db'] = $this->localizePhase( 'update_db' );
					}
				//break; // continue...!
				
				case 'install_db':
				case 'update_db':
					if( empty( $phases ) ) {
						$phases['goto_licenses'] = $this->localizePhase( 'goto_licenses' );
					}
				break;
				
				case 'goto_licenses':
					// no more phases (all done)
				break;
				
				default:
					LogHandler::Log( 'DbInstaller', 'ERROR', 'Unknown phase: '.$this->phase );
				break;
			}
		}
		return $phases;
	}
	
	/**
	 * Translates a given phase (internal key) to human readable string.
	 * @todo Add resource keys to provide localized strings.
	 *
	 * @param string $phase 'connect_db', 'install_db', 'update_db' or 'goto_licenses'.
	 * @return string Human readable phase.
	 */
	private function localizePhase( $phase )
	{
		switch( $phase ) {
			case 'connect_db'    : $localized = BizResources::localize( 'DBINSTALLER_CONNECTDB_BTN'); break;
			case 'install_db'    : $localized = BizResources::localize( 'DBINSTALLER_CREATEDB_BTN'); break;
			case 'update_db'     : $localized = BizResources::localize( 'DBINSTALLER_UPDATEDB_BTN'); break;
			case 'goto_licenses' : $localized = BizResources::localize( 'DBINSTALLER_LICENSE_BTN'); break;
		}
		return $localized;
	}
	
	/**
	 * Runs one of the following phases: 'connect_db' 'install_db' or 'update_db'.
	 */
	private function installDatabase()
	{
		$this->flatWantedDbVersion = self::dotVersionToNumber( SCENT_DBVERSION );
		$this->dbModelScripts = array();
		$this->dbDataUpgradeScripts = array();
		
		// Determine the current status of DB setup by initializing the class members.
		if( $this->canContinue() ) {
			$this->determineRunMode();
		}
		
		// Validate and retrieve database update scripts
		if( $this->canContinue() ) {
			$this->dbModelScripts = $this->checkdbModelScripts();
			$this->dbDataUpgradeScripts = $this->determineDataUpdateScripts();
		}
		
		// Try to connect to DB and validate its version and basic settings.
		if( $this->canContinue() ) {
			$this->checkDbConnection();
		}

		// Run the database model installation or update.
		if( $this->canContinue() ) {

			// Only allowed if we are admin ( with exception if there is no DB or update in progress ).
			if( !$this->newInstallation && !$this->dbModelUpgrade ) {
				call_user_func( $this->checkSystemAdmin );
			}

			if( $this->phase == 'update_db' || $this->phase == 'install_db' ) {

				// Prevent mssql timeout error during upgrade.
				if( $this->dbModelUpgrade && DBTYPE == 'mssql' ) {
					if( intval( ini_get( 'mssql.timeout' ) ) <= 120 ) {
						ini_alter( 'mssql.timeout', '360' );
					}
				}

				$needToExecuteSql = true; // Assume to execute SQL, unless following checks stop us.

				// Before the migration, we want to make sure that the ServerJobs table is empty.
				// Reason is that new fields have been added and old fields have been taken out in v9.4.
				// We don't want to convert the old fields data into the new fields format as every
				// ServerJob Type might carry different type of data. So for convenience, we clear
				// everything before migrating to the latest version 9.4.
				// @todo The if-part below can be removed when self::MINUPDATEVERSION >= 930.
				if( $this->dbModelUpgrade &&
					( $this->flatInstalledVersion >= 800 && $this->flatInstalledVersion <= 920 ) &&
					  $this->flatWantedDbVersion >= 930 ) {
					if( !$this->isServerJobsEmpty() ) {
						$cleanup =  $this->cleanUpTable( 'serverjobs' );
						$needToExecuteSql = $cleanup; // If the 'serverjobs' table is not empty, do not proceed.
						$this->dbDataUpgrade = $cleanup;
					}
				}

				// Since 9.7 the id field for the smart_indesignserverjobs table is replaced by the jobid field. 
				// The id (integer) is dropped and the new jobid (string,guid) has been added instead.
				// Both fields are primary fields, which is challenging in terms of DB migration.
				// The DB scripts (SQL modules) support such conversion but the table has to be empty.
				// Therefore, before the migration, we error when the InDesign Server Jobs table is not empty.
				// @todo The if-part below can be removed when self::MINUPDATEVERSION >= 970.
				if( $this->dbModelUpgrade && $this->flatInstalledVersion <= 960 && $this->flatWantedDbVersion >= 970 ) {
					if( !$this->isInDesignServerJobsEmpty() ) {
						$cleanup =  $this->cleanUpTable( 'indesignserverjobs' );
						$needToExecuteSql = $cleanup; // If the 'indesignserverjobs' table is not empty, do not proceed.
						$this->dbDataUpgrade = $cleanup;
					}
				}
				
				// Run the DB installation scripts (SQL).
				if( $needToExecuteSql ) {
					$dbDriver = DBDriverFactory::gen();
					foreach( $this->dbModelScripts as $sqlScript ) {
						$this->runSqls( $dbDriver, BASEDIR.self::DBSCRIPTPATH.$sqlScript );
						$this->sqlReport->add( 'DbInstaller', 'INFO', 'INFO',
												$sqlScript, '', '', 
												array( 'phase' => $this->phase ) );
					}
				}
			}
		}

		if( $this->canContinue() ) {
			
			switch( $this->phase ) {

				// When init ok, suggest to install/upgrade.
				case 'connect_db':
					if( $this->newInstallation ) {
						$params = array( $this->localizePhase('install_db') );
						$message = BizResources::localize( 'DBINSTALLER_CLICK_CREATEDB_BTN', true, $params );
					} else if( $this->dbModelUpgrade || $this->dbDataUpgrade ) {
						$params = array( $this->localizePhase('update_db') );
						$message = BizResources::localize( 'DBINSTALLER_CLICK_UPDATEDB_BTN', true, $params );
					} else {
						$message = '';
					}
					if( $message ) {
						$this->report->add( 'DbInstaller', 'INFO', 'INFO', $message, '', '',
											array( 'phase' => $this->phase ) );
					}
				break;
			
				// Determine and execute post installation upgrades.
				case 'update_db':
				case 'install_db':
					if( $this->newInstallation || $this->dbModelUpgrade || $this->dbDataUpgrade ) {
					
						// Inform user about install/update success.
						if( $this->newInstallation ) {
							$this->report->add( 'DbInstaller', 'INFO', 'INFO', 
												BizResources::localize( 'DBINSTALLER_DBTABLES_CREATED' ), '', '',
												array( 'phase' => $this->phase ) );
						} else if( $this->dbModelUpgrade ) {
							$this->report->add( 'DbInstaller', 'INFO', 'INFO', 
												BizResources::localize( 'DBINSTALLER_DBTABLES_UPDATED' ), '', '',
												array( 'phase' => $this->phase ) );
						}
						
						// For upgrades, but also for clean installations there is a need
						// to call runUpgrades() to make sure they get flagged afterwards.
						$this->runDbDataUpgrades();
						
						// Inform user about upgrade success.
						if( $this->dbDataUpgrade && $this->canContinue() ) {
							$this->report->add( 'DbInstaller', 'INFO', 'INFO',
												BizResources::localize( 'DBINSTALLER_DBDATA_MIGRATED' ), '', '',
												array( 'phase' => $this->phase ) );
						}
						
						// For upgrades, remind user to re-index Solr.
						if( $this->dbModelUpgrade ) {
							$this->report->add( 'DbInstaller', 'INFO', 'INFO',
												BizResources::localize( 'SOLR_RE_INDEX' ), '', '',
												array( 'phase' => $this->phase ) );
						}
						$params = array( $this->localizePhase('goto_licenses') );
						$this->report->add( 'DbInstaller', 'INFO', 'INFO', 
											BizResources::localize( 'DBINSTALLER_CLICK_LICENSE_BTN', true, $params ), '', '',
											array( 'phase' => $this->phase ) );

					}
				break;
			}
		}
	}
	
	/**
	 * Whether or not it makes sense to continue the installation. When there
	 * was a fatal error or when there is nothing to do, this function returns false.
	 *
	 * @return boolean See function header.
	 */
	public function canContinue()
	{
		return !$this->report->hasFatal() && !$this->dbConnectError;
	}
	
	/**
	 * Composes a list of the options that configure the DB connection. The returned
	 * array carries the localized terms in its keys and the configured data in its values.
	 * 
	 * @return array DB connection options.
	 */
	public function getDbConfiguration()
	{
		return array(
			BizResources::localize( 'DBINSTALLER_DBTYPE' ) => DBTYPE,
			BizResources::localize( 'DBINSTALLER_DBSELECT' ) => DBSELECT,
			BizResources::localize( 'DBINSTALLER_DBSERVER' ) => DBSERVER,
			BizResources::localize( 'DBINSTALLER_DBUSER' ) => DBUSER );
	}
	
	/**
	 * Composes a list of DB versions, containing the installed version and the required version. 
	 * The returned array carries the localized terms in its keys and the versions in its values.
	 *
	 * @param string $installedVersion Installed DB model version to include.
	 * @return array Installed- and required DB versions.
	 */
	public function getDbVersions( $installedVersion )
	{
		return array(
			BizResources::localize( 'DBINSTALLER_INSTALLED_DBVERSION' ) => $installedVersion ? $installedVersion : '?',
			BizResources::localize( 'DBINSTALLER_REQUIRED_DBVERSION' ) => SCENT_DBVERSION );
	}
	
	/**
	 * Tries to connect to the DB with the configured settings.
	 * Once connected it validates the its version and some basic settings.
	 * When could not connect, the $this->dbConnectError flag is raised.
	 * When DB version or settings are wrong, the FATAL flag in $this->report is raised.
	 */
	private function checkDbConnection()
	{
		// Having no DB connection (S1003) is very well possible and therefor changed
		// into INFO when logged. Reason is that this installer is there to guide the
		// admin user setting up the DB. So no reason to panic yet. Nevertheless, this
		// must be reported to screen as a FATAL since it is blocking the installer from
		// offering next steps in the installation procedure.
		$map = new BizExceptionSeverityMap( array( 'S1003' => 'INFO' ) );
		$map = $map; // keep analyzer happy

		// Validate the database version and settings.
		$help = '';
		try {
			$dbDriver = DBDriverFactory::gen();
			if( $dbDriver->isConnected() ) {
				$dbDriver->checkDbVersion( $help );
				$dbDriver->checkDbSettings( $help );
			}
		} catch( BizException $e ) {
			$logSeverity = 'ERROR';
			if( $e->getErrorCode() == 'S1003' ) {
				$this->dbConnectError = true;
				$help = $this->getDbConnectionTip();
				$logSeverity = 'INFO';
			}
			$this->report->add( 'DbInstaller', 'FATAL', $logSeverity,
								$e->getMessage(), $e->getDetail(), $help,
								array( 'phase' => $this->phase ) );
		}
	}

	/**
	 * Connect to the database (which might fail) and retrieve last stored/updated
	 * Enterprise DB version number (from smart_config table).
	 *
	 * @return string|null Version when found, or NULL on connection error.
	 */
	public function getInstalledDbVersion()
	{
		// Having no DB connection (S1003) is very well possible and therefor changed
		// into INFO when logged. Reason is that this installer is there to guide the
		// admin user setting up the DB. So no reason to panic yet. Nevertheless, this
		// must be reported to screen as a FATAL since it is blocking the installer from
		// offering next steps in the installation procedure.
		$map = new BizExceptionSeverityMap( array( 'S1003' => 'INFO' ) );
		$map = $map; // keep analyzer happy
		
		try {
			require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
			$installedVersion = DBConfig::getSCEVersion();
		} catch( BizException $e ) {
			$help = '';
			$installedVersion = null;
			$logSeverity = 'ERROR';
			if( $e->getErrorCode() == 'S1003' ) {
				$this->dbConnectError = true;
				$help = $this->getDbConnectionTip();
				$logSeverity = 'INFO';
			}
			$this->report->add( 'DbInstaller', 'FATAL', $logSeverity,
								$e->getMessage(), $e->getDetail(), $help,
								array( 'phase' => $this->phase ) );
		}
		return $installedVersion;
	}
	
	/**
	 * Determines the installation mode for the database. Doing so, all kind of local
	 * member attributes are initialized, such as newInstallation, upgrade, majorUpdate, etc
	 */
	private function determineRunMode()
	{
		$this->flatInstalledVersion = null;
		$this->newInstallation = false;
		$this->dbModelUpgrade = false;
		$this->dbDataUpgrade = false;
		$this->majorUpdate = false;
		$installedVersion = $this->getInstalledDbVersion();
		if( $this->canContinue() ) {

			if( $this->phase == 'connect_db' ) {
				$message = BizResources::localize( 'DBINSTALLER_CONNECTEDTODB', true, array(DBSELECT) );
				$this->report->add( 'DbInstaller', 'INFO', 'INFO', $message, '', '',
					array( 'phase' => $this->phase ) );
			}

			if( $installedVersion === null ) {
				$this->newInstallation = true; // No version found
			} else {
				// Determine whether or not this is a major update, such as 8.2 => 9.0
				// It will be set FALSE when there is minor update, such as 8.0 => 8.2
				require_once BASEDIR.'/server/utils/VersionUtils.class.php';
				$installedVersionInfo = VersionUtils::getVersionInfo( $installedVersion );
				$wantedVersionInfo = VersionUtils::getVersionInfo( SCENT_DBVERSION );
				if( $installedVersionInfo !== false && $wantedVersionInfo !== false ) {
					$this->majorUpdate = $installedVersionInfo['major'] != $wantedVersionInfo['major'];
				}

				$this->flatInstalledVersion = self::dotVersionToNumber( $installedVersion );
				$sqlScripts = $this->getDbModelScripts( $this->flatWantedDbVersion, $this->flatInstalledVersion,
					false, false ); // Look for patches
				if( $this->flatWantedDbVersion > $this->flatInstalledVersion || count ( $sqlScripts ) > 0 ) { // Need to upgrade
					$this->dbModelUpgrade = true;
					$this->checkPreConditionsUpgrade();
				} else {
					$this->report->add( 'DbInstaller', 'INFO', 'INFO',
						BizResources::localize( 'OBJ_DATABASE_TABLES_ARE_UP_TO_DATE' ).
						' ( '.BizResources::localize( 'OBJ_VERSION' ) .' '.SCENT_DBVERSION.' )', '', '',
						array( 'phase' => $this->phase ) );
				}
			}

			// If the database is up to date it might be needed to check / update objects.
			if( !$this->newInstallation && $this->needsDbDataUpgrade() ) {
				$this->dbDataUpgrade = true;
			}
		}
	}

	/**
	 * Check if the preconditions to do an upgrade are met.
	 */
	private function checkPreConditionsUpgrade()
	{
		// Before the migration, we want to make sure that the ServerJobs table is empty.
		// Reason is that new fields have been added and old fields have been taken out in v9.4.
		// We don't want to convert the old fields data into the new fields format as every
		// ServerJob Type might carry different type of data. So for convenience, we clear
		// everything before migrating to the latest version 9.4.
		// Only show the warning at the initial page load (when checking the connection).
		// @todo The if-part below can be removed when self::MINUPDATEVERSION >= 920.
		if( ( $this->flatInstalledVersion >= 800 && $this->flatInstalledVersion <= 920 ) &&
				$this->flatWantedDbVersion >= 930 ) {
			if( !$this->isServerJobsEmpty() && $this->phase == 'connect_db' ) {
				$this->report->add( 'DbInstaller', 'WARN', 'INFO',
					BizResources::localize( 'MSG_EMPTY_SERVERJOBS' ), '', '',
					array( 'phase' => $this->phase ) );
			}
		}
		
		// Since 9.7 the id field for the smart_indesignserverjobs table is replaced by the jobid field. 
		// The id (integer) is dropped and the new jobid (string,guid) has been added instead.
		// Both fields are primary fields, which is challenging in terms of DB migration.
		// The DB scripts (SQL modules) support such conversion but the table has to be empty.
		// Therefore, before the migration, we error when the InDesign Server Jobs table is not empty.
		// Only show the warning at the initial page load (when checking the connection).
		// @todo The if-part below can be removed when self::MINUPDATEVERSION >= 970.
		if( $this->flatInstalledVersion <= 960  && $this->flatWantedDbVersion >= 970 ) {
			if( !$this->isInDesignServerJobsEmpty() &&  $this->phase == 'connect_db' ) {
				$this->report->add( 'DbInstaller', 'WARN', 'INFO',
					BizResources::localize( 'MSG_EMPTY_INDESIGNSERVERJOBS' ), '', '',
					array( 'phase' => $this->phase ) );
			}
		}
}

	/**
	 * Determines what data modifications needs to be ran. If the model needs to be updated these scripts are ran after
	 * the database moel has been changed. If the model is already up to date these scripts can just be executed.
	 *
	 * @return array Array containing the available updates and their installation states.
	 */
	private function determineDbDataUpgrades()
	{
		static $upgrades = null;
		if( $upgrades ) {
			return $upgrades;
		}
		$upgrades = array( 
			'800' => array(),
			'900' => array(),
		);

		$dbUpgradeFiles = $this->getDBUpgradeFiles();

		if ( $dbUpgradeFiles ) foreach ( $dbUpgradeFiles as $dbUpgradeFile ) {
			require_once BASEDIR.'/server/dbscripts/dbupgrades/'.$dbUpgradeFile;
			$fileParts = explode( '.', $dbUpgradeFile );
			$className = $fileParts[0];
			$upgradeObject = new $className();
			$introduced = $upgradeObject->introduced();
			$upgrades[$introduced][] = array(
				'upgrade' => !$upgradeObject->isUpdated(),
				'object' => $upgradeObject,
			);
		}

		return $upgrades;
	}

	/**
	 * Scans the given folder for upgrade scripts.
	 *
	 * @throws BizException
	 * @return array of with the names of the found upgrade files.
	 */
	public function getDBUpgradeFiles()
	{
		$dbDataUpgradeFiles = array();
		$dirName = BASEDIR.'/server/dbscripts/dbupgrades/';

		if ( $thisDir = opendir( $dirName ) ) {
			while ( ($itemName = readdir( $thisDir )) !== false ) {
				if ( is_file( $dirName.$itemName ) && $itemName[0] !== '.' ) { // Skip hidden files.
					$dbDataUpgradeFiles[] = $itemName;
				}
			}
			closedir( $thisDir );
		}

		return $dbDataUpgradeFiles;
	}

	/**
	 * Checks if data updates are needed.
	 *
	 * @return bool Whether or not an upgrade of the data is needed.
	 */
	private function needsDbDataUpgrade()
	{
		// In case of new/clean installations, there is no need for post upgrades.
		$retVal = false;
			// Check if any of the post upgrade scripts wants to be executed.
		$upgrades = $this->determineDbDataUpgrades();
		if( $upgrades ) foreach( $upgrades as $version ) {
			foreach( $version as $upgrade ) {
				if( $upgrade['upgrade'] === true ) {
					$retVal = true;
					break 2;
				}
			}
		}
		return $retVal;
	}

	/**
	 * Determines the scripts to be run to update the DB.
	 *
	 * @return string[] The script module names (PHP).
	 */
	private function determineDataUpdateScripts()
	{
		$scripts = array();
		$upgrades = $this->determineDbDataUpgrades();
		if( $upgrades ) foreach( $upgrades as $version ) {
			foreach( $version as $upgrade ) {
				if( $upgrade['upgrade'] === true ) {
					$scripts[] = get_class($upgrade['object']).'.class.php';
				}
			}
		}
		return $scripts;
	}
	
	/**
	 * Runs the given DB data upgrades one by one.
	 */
	private function runDbDataUpgrades()
	{
		$upgrades = $this->determineDbDataUpgrades();
		if( $upgrades ) foreach( $upgrades as $version ) {
			foreach( $version as $upgrade ) {
				if( $this->dbDataUpgrade ) {
					// If there is a need to update the DB, run the update script.
					// In case of an error, update the report to inform admin user.
					$script = get_class($upgrade['object']).'.class.php';
					if( $upgrade['upgrade'] === true ) {
						if ( !$this->newInstallation ) {
							$result = $upgrade['object']->run();
						}
						if( $result ) {
							LogHandler::Log( 'DbInstaller', 'INFO', 
								'Successfully run DB migration script '.$script );
						} else {
							$this->report->add( 'DbInstaller', 'FATAL', 'ERROR', 
								BizResources::localize('DBINSTALLER_DBUPGRADE_FAILED'), 
								BizResources::localize('SEELOGFILES'),
								'', array( 'phase' => $this->phase ) );
						}
					} else {
						LogHandler::Log( 'DbInstaller', 'INFO', 
							'The DB migration script '.$script. 'was '.
							'skipped since it was already run before.' );
					}
				}
				// No matter if the above did run the update script, there is a need
				// to flag it, to make sure we don't suggest to run over and over again.
				// This also implies that for clean installations all upgrade scripts will get flagged.
				$upgrade['object']->setUpdated();
			}
		}
	}
	
	/**
	 * Converts a version string in '<major>.<minor>[.<patch>]' format into a single version number.
	 * For example, '8.0' or  '8.0.0' gets converted into 800.
	 *
	 * @param string $version Dotted version string.
	 * @return int Single version number.
	 */
	public static function dotVersionToNumber( $version )
	{
		$parts = explode( '.', $version );
		if( count($parts) == 2 ) { // assume patch level 0 when <major>.<minor> is given only
			$parts[] = '0';
		}
		if( count($parts) != 3 ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Wrong version provided ['.$version.']. Should be in major.minor[.patch] format. ' );
		}
		return implode( '',  $parts );
	}

	/**
	 * Converts a single version number into a version string in '<major>.<minor.<patch>' format.
	 * For example, 800 gets converted into '8.0.0'.
	 *
	 * @param int $numVersion Single version number.
	 * @return string Dotted version string.
	 */
	private function numberToDotVersion( $numVersion )
	{
		$majVersion = floor( $numVersion / 100 );
		$minVersion = floor( ($numVersion - ($majVersion*100)) / 10 );
		return "$majVersion.$minVersion";
	}
	
	/**
	 * Reads the SQL modules from the server/dbscripts folder and validates if all complete.
	 *
	 * NOTE: In theory the system admin could have changed the package. This is far from likely
	 * to happen, nevertheless this function does all kind of paranoid checksums. Because
	 * in practice this never will go wrong, error strings are English-only in this function.
	 *
	 * @return string[] List of SQL file names to execute.
	 */
	private function checkdbModelScripts()
	{
		$sqlScripts = array();
		if( $this->canContinue() ) {
			// Check if the package is complete. This is an extra checksum especially for development and QA.
			$sqlScripts = $this->getDbModelScripts( $this->flatWantedDbVersion, null, true, false );
			if( count($sqlScripts) == 0 ) {
				$detail = 'The clean database installation scripts for v'.
						self::numberToDotVersion($this->flatWantedDbVersion).
						' are missing at '.BASEDIR.self::DBSCRIPTPATH.'.';
				$this->report->add( 'DbInstaller', 'FATAL', 'ERROR', 
									'Incomplete installation package.', $detail, '',
									array( 'phase' => $this->phase ) );
			}
		}
		if( $this->canContinue() && $this->dbModelUpgrade ) {
			$sqlScripts = $this->getDbModelScripts( $this->flatWantedDbVersion, self::MINUPDATEVERSION, false, true );
			if( count($sqlScripts) == 0 ) {
				$detail = 'The database update scripts for v'.
						self::numberToDotVersion(self::MINUPDATEVERSION).
						' => v'.self::numberToDotVersion($this->flatWantedDbVersion).
						' are missing at '.BASEDIR.self::DBSCRIPTPATH.'.';
				$this->report->add( 'DbInstaller', 'FATAL', 'ERROR', 
									'Incomplete installation package.', $detail, '',
									array( 'phase' => $this->phase ) );
			}
		}

		if( $this->canContinue() ) {
			// Get the SQL scripts to use for the installation or update of the database.
			$sqlScripts = $this->getDbModelScripts( $this->flatWantedDbVersion, $this->flatInstalledVersion,
												$this->newInstallation, $this->dbModelUpgrade );
			if( $this->canContinue() ) {
				if( count( $sqlScripts ) == 0 )  {
					if( $this->flatWantedDbVersion > $this->flatInstalledVersion ) {
						$help = 'Your database model is too old. First, you need an older version of '.
							'Enterprise Server to update your database to an intermediate version before '.
							'you can continue. See Admin Guide for more details.';
					} else {
						$help = 'Your database model is too new. You need a new version of Enterprise Server '.
							'to work with this database.';
					}
					if( $this->newInstallation ) {
						$detail = 'Clean installation for v'.
								self::numberToDotVersion($this->flatInstalledVersion).
								' is not supported. ';
						$this->report->add( 'DbInstaller', 'FATAL', 'ERROR', 
											'No update path available.', $detail, $help,
											array( 'phase' => $this->phase ) );
					}
					if( $this->dbModelUpgrade ) {
						$detail = 'Update from v'.
								self::numberToDotVersion($this->flatInstalledVersion).
								' to v'.self::numberToDotVersion($this->flatWantedDbVersion).
								' is not supported. ';
						$this->report->add( 'DbInstaller', 'FATAL', 'ERROR', 
											'No update path available.', $detail, $help,
											array( 'phase' => $this->phase ) );
					}
				}
			}
		}
		
		// Forget the answer when fatal error occured.
		if( !$this->canContinue() ) {
			$sqlScripts = array();
		}
		return $sqlScripts;
	}

	/**
	 * Builds file paths for SQL modules that are expected to be present in the 
	 * server/dbscripts folder. Which files are choosen depends on the DB flavor
	 * and if it needs a clean installation or an update.
	 *
	 * @return string[] List of SQL file names to execute.
	 */
	public function getDbModelScripts( $flatWantedDbVersion, $flatInstalledVersion, $newInstallation, $upgrade )
	{
		$files = scandir( BASEDIR.self::DBSCRIPTPATH );
		$selectfiles = array();
		$type = DBTYPE;
		$sqlFilePrefix = 'scent';
		$internalVersion = $flatWantedDbVersion - 1; // e.g. 599, 699, 799

		if( strtolower( $type ) == 'oracle' ) { // ora is shortcut for oracle
			$type = 'ora';
		}

		foreach( $files as $file ) {
			if( substr( $file, 0, 1 ) == '.' ) { // hidden files
				continue;
			}
			if( substr( $file, -4 ) != '.sql' ) { //extension must be 'sql'
				continue;
			}
			if( substr( $file, 0, strlen($sqlFilePrefix) ) != $sqlFilePrefix ) { // prefix must be 'scent'
				continue;
			}
			if( !stristr( $file, $type ) ) { // Wrong DBMS type
				continue;
			}
			if( $newInstallation ) {
				if( preg_match( "/$sqlFilePrefix$flatWantedDbVersion\\./i", $file ) > 0 && stristr( $file, ".$type.sql" ) ) {
					$selectfiles[] = $file;
				}
			} elseif( $upgrade ) {
				$parts = explode( '.', str_replace( '_', '.', substr( $file, strlen( $sqlFilePrefix ) ) )  );
				if( count($parts) > 1 && ctype_digit( $parts[0] ) && ctype_digit( $parts[1] ) ) {
					$fromVersion = $parts[0];
					$toVersion = $parts[1];
					if( stristr( $file, ".$type.sql" ) && ( $fromVersion == $flatInstalledVersion ) && ( $toVersion == $internalVersion ) ) {
						$selectfiles[] = $file;
					}
					if( stristr( $file, ".$type.sql" ) && ( $fromVersion == $internalVersion ) && ( $toVersion == $flatWantedDbVersion ) ) {
						if( $flatInstalledVersion < $internalVersion ) { //first upgrade to internal version
							$selectfiles[] = $file;
						}
					}
					if( stristr( $file, ".$type.sql" ) && ( $fromVersion == $flatInstalledVersion ) && ( $toVersion == $flatWantedDbVersion ) ) {
						$selectfiles[] = $file;
					}
				}
			}
			if ( !$newInstallation ) { // Patches are not applicable in case of a new installation.
			// But in case there is no 'normal' upgrade still check if a patch must be installed. Either we are moving
			// a patched release or the installed version was patched.
				if (( preg_match( "/{$sqlFilePrefix}{$flatWantedDbVersion}_{$flatInstalledVersion}_patch/i", $file ) > 0  ||
					preg_match( "/{$sqlFilePrefix}{$flatInstalledVersion}_{$flatWantedDbVersion}_patch/i", $file ) > 0 ) && stristr( $file, "$type.sql" ) ) {
					$fileParts = explode( '_', $file);
					$patchName = $fileParts[3];
					require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
					if ( !DBConfig::getValue( $patchName )) { // Check if the patch is already installed.
						$selectfiles[] = $file;
					}
				}
			}
		}

		if ( $flatInstalledVersion >= 800 && $flatInstalledVersion <= 899 ) {
			// Check if database changes are already made in version 8.3.4 or later. See BZ#34633.
			$dbdriver = DBDriverFactory::gen();
			// If the database changes are not found we do not have to remove them. But if they are found the
			// ...800_920_pre... script must be added. That script will remove the changes so they can be added
			// later on in the normal ...800_920... script can run without causing errors.
			if ( !$this->indexOnInDesignServerJobsExists( $dbdriver )) {
				foreach ( $selectfiles as $key => $selectfile ) {
					if ( preg_match( '/pre/i', $selectfile ) > 0 && preg_match( '/8[0-9][0-9]/i', $selectfile ) > 0 ) {
						unset( $selectfiles[$key] );
						break;
					}
				}
			}
		}

		usort( $selectfiles, array($this, 'sortFiles') );
		return $selectfiles;
	}

	/**
	 * Checks if the index 'issueid_targets' on the smart_targets table exists.
	 * @param type $dbdriver Connection to the database.
	 * @return boolean Index exists.
	 */
	private function indexOnIssueOnTargetsExists( $dbdriver )
	{
		$indexes = $dbdriver->listIndexOnTable( $dbdriver->tablename( 'targets' ) );
		return in_array( 'issueid_targets', $indexes );
	}

	/**
	 * Checks if the index 'objid_indesignserverjobs' on the smart_indesignserverjobs table exists.
	 *
	 * @param object $dbdriver Connection to the database.
	 * @return boolean Index exists.
	 */
	private function indexOnInDesignServerJobsExists( $dbdriver )
	{
		$indexes = $dbdriver->listIndexOnTable( $dbdriver->tablename( 'indesignserverjobs' ) );
		return in_array( 'objid_indesignserverjobs', $indexes );
	}
	
	/**
	 * Callback function that does sort files on their names.
	 * The SQL files having a 'pre' as prefix are put on top of the list.
	 *
	 * @param stirng $str1 File name (LHS)
	 * @param stirng $str2 File name (RHS)
	 * @return int Returns < 0 if str1 is less than str2; > 0 if str1 is greater than str2, and 0 if they are equal.
	 */
	private function sortFiles( $str1, $str2 )
	{
		if( substr( $str1, 0, 12 ) == substr( $str2, 0, 12 ) ) {
			if( preg_match( '/pre/i', $str1 ) > 0 ) {
				return -1;
			} else {
				return 1;
			}
		} else {
			return strcmp( $str1, $str2 );
		}
	}
	
	/**
	 * Runs an SQL script on the database as a part of the installation.
	 *
	 * @param object $dbDriver
	 * @param string $sqlScript SQL script to run.
	 */
	private function runSqls( $dbDriver, $sqlScript )
	{
		$sqlTxt = file_get_contents( $sqlScript );
		$sqlStatements = explode( ';', $sqlTxt );
		array_pop( $sqlStatements ); // remove the last empty element ( after the ; )

		$sqlStatementCompleet = '';
		if( $sqlStatements ) foreach( $sqlStatements as $sqlStatement ) {
			$sqlStatementCompleet .= $sqlStatement;
			if ( !$dbDriver->isCompleteStatement( $sqlStatementCompleet ) ) {
				continue;
			}
			$sth = $dbDriver->query( $sqlStatementCompleet );
			$sqlStatementCompleet = '';
			if( !$sth ) {
				$message = $dbDriver->error().' ('.$dbDriver->errorcode().')';
				$this->report->add( 'DbInstaller', 'FATAL', 'ERROR', 
									$message, 'SQL: '.$sqlStatement, '',
									array( 'phase' => $this->phase ) );
			}
		}
	}

	/**
	 * Checks whether the smart_serverjobs table is empty.
	 *
	 * @return bool Returns true if the smart_serverjobs table is empty, false otherwise
	 */
	private function isServerJobsEmpty()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		// Can actually use 'id' as the fieldName in countRecordsInTable() instead of 'jobstatus'
		// but just to be sure, better use 'jobstatus' because 'id' has been taken out since v9.4.
		// return DBBase::countRecordsInTable( 'serverjobs', 'id' ) == 0;
		return DBBase::countRecordsInTable( 'serverjobs', 'jobstatus' ) == 0;
	}

	/**
	 * Deletes all rows from the specified table.
	 *
	 * @param string $tableName
	 * @return bool true if no error else false.
	 */
	private function cleanUpTable( $tableName)
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

		return (bool) DBBase::deleteRows( $tableName, '1 = 1' ) ;
	}

	/**
	 * Checks whether the smart_indesignserverjobs table is empty.
	 *
	 * @since 9.7.0
	 * @return bool Returns true if the smart_indesignserverjobs table is empty, false otherwise
	 */
	private function isInDesignServerJobsEmpty()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		return DBBase::countRecordsInTable( 'indesignserverjobs', 'objid' ) == 0;
		// L> Note that 'objid' is passed in as $fieldName in countRecordsInTable() because 
		//    that field already exists when the table was introduced. The 'id' field was 
		//    introduced since 9.7 and so is not reliable in context of DB migrations that
		//    may run on an old DB model.
	}
	
	/**
	 * Returns info (tip) how to setup DB connection.
	 *
	 * @return string
	 */
	private function getDbConnectionTip() 
	{
		$params = array( DBSERVER, DBUSER, DBSELECT, $this->localizePhase('connect_db') );
		$tip = BizResources::localize( 'DBINSTALLER_DBSETTINGS_HELP', true, $params );
		$tip = str_replace( "\\n", '<br />', $tip );
		return $tip;
	}
}