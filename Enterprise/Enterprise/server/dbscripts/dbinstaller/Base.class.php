<?php

/**
 * Installer for Enterprise databases.
 *
 * Can install a new database or upgrade an existing database for the core server and/or for server plugins.
 *
 * It uses a report to collect all messages and problems during installation. There are no errors logged when those
 * are expected to be solved by the user. Instead, the reported errors are shown on screen.
 *
 * The report is also used to indicate if the next step in the installation can be taken, or there is a need from user
 * to solve problems first. Those problems are indicated with the FATAL severity. The canContinue() function is called
 * to detect this.
 *
 * When there is no DB engine connected or no DB table space created yet, this installer needs to act smooth without
 * throwing nor logging errors all over the shop.
 *
 * Before v9.0 there was just a dbadmin.php module that did it all. Since 9.0 its logics are taken out and moved to this
 * class. The dbadmin.php module does UI only. Reason to split was not only to have a MVC model in place, but also to
 * call the installer from command prompt as used for automated build tests.
 *
 * Since 10.2 the WW_DbScripts_DbInstaller class and was located in server/dbscripts/DbInstaller.class.php but moved
 * into here. It was changed into an abstract class to share logics between core server installation and server plugin
 * installation that also may have their own DB models.
 *
 * @package    Enterprise
 * @subpackage dbscripts
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

abstract class WW_DbScripts_DbInstaller_Base
{
	/**
	 * @var string The database model version (taken from smart_config table) of the current installation in 'major.minor' notation.
	 */
	protected $installedVersion = null;

	/** @var string The oldest DB model version (in 'major.minor' notation) for which upgrade scripts are shipped with this ES installation.  */
	private $minGenVersion = null;

	/** @var string The lastest/current/wanted DB model version (in 'major.minor' notation) to install or upgrade to.  */
	private $wantedVersion = null;

	/**
	 * @var boolean Whether or not the database model needs to be upgraded.
	 */
	protected $dbModelUpgrade = false;

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
	protected $dbConnectError = false;

	/**
	 * @var boolean Whether or not the data update modules needs to be run (after the database model upgrade).
	 */
	protected $dbDataUpgrade = false;

	/**
	 * @var WW_DbScripts_FileDescriptor[] List of SQL scripts that are needed to install or upgrade the database model.
	 */
	private $dbModelScripts = array();

	/**
	 * @var string[] List of scripts that are needed to update data within the database.
	 */
	private $dbDataUpgradeScripts = array();

	/**
	 * @var WW_Utils_Report Collection of operation results (errors, warning, infos, etc).
	 */
	protected $report = null;

	/**
	 * @var WW_Utils_Report Collection of SQL operations that were ran against DB.
	 */
	private $sqlReport = null;

	/**
	 * @var callable Callback function to validate whether or not the system admin user is logged. Only called when not installing/upgrading the DB.
	 */
	private $checkSystemAdmin = null;

	/** @var WW_DbScripts_FileHandler */
	private $scriptFileHandler = null;

	/**
	 * Initializes the installer.
	 *
	 * @param callable|null $checkSystemAdmin
	 * @param string|null $pluginName Internal name of the server plug-in. NULL for the core server.
	 */
	public function __construct( $checkSystemAdmin, $pluginName = null )
	{
		require_once BASEDIR.'/server/utils/Report.class.php';
		$this->report = new WW_Utils_Report();
		$this->sqlReport = new WW_Utils_Report();
		$this->checkSystemAdmin = $checkSystemAdmin;

		require_once BASEDIR.'/server/dbscripts/FileHandler.class.php';
		$this->scriptFileHandler = new WW_DbScripts_FileHandler( $pluginName );

		require_once BASEDIR.'/server/dbmodel/Factory.class.php';
		if( $pluginName ) {
			$definition = WW_DbModel_Factory::createModelForServerPlugin( $pluginName );
		} else {
			$definition = WW_DbModel_Factory::createModelForEnterpriseServer();
		}

		require_once BASEDIR.'/server/dbmodel/Reader.class.php';
		$reader = new WW_DbModel_Reader( $definition );
		$dbMigrationVersions = $reader->getDbModelProvider()->getDbMigrationVersions();
		$this->minGenVersion = reset( $dbMigrationVersions );
		$this->wantedVersion = $reader->getDbModelProvider()->getVersion();
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
				/** @noinspection PhpMissingBreakStatementInspection */
				case 'connect_db':
					if( $this->newInstallation ) {
						$phases['install_db'] = $this->localizePhase( 'install_db' );
					} else if( $this->dbModelUpgrade || $this->dbDataUpgrade ) {
						$phases['update_db'] = $this->localizePhase( 'update_db' );
					}
				//break; // continue...!

				case 'install_db':
				case 'update_db':
					if( empty( $phases ) && $this->gotoLicensePageAfterSuccess() ) {
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
	 *
	 * @param string $phase 'connect_db', 'install_db', 'update_db' or 'goto_licenses'.
	 * @return string|null Human readable phase.
	 */
	private function localizePhase( $phase )
	{
		$localized = null;
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
		$this->dbModelScripts = array();
		$this->dbDataUpgradeScripts = array();

		// Determine the current status of DB setup by initializing the class members.
		if( $this->canContinue() ) {
			$this->determineRunMode();
		}

		// Validate and retrieve database update scripts
		if( $this->canContinue() ) {
			$this->dbModelScripts = $this->checkdbModelScripts();
			$this->dbDataUpgradeScripts = $this->determineDataUpgradeScripts();
		}

		// Try to connect to DB and validate its version and basic settings.
		if( $this->canContinue() ) {
			$this->checkDbConnection();
		}

		// Run the database model installation or update.
		if( $this->canContinue() ) {

			// Only allowed if we are admin ( with exception if there is no DB or update in progress ).
			if( !$this->newInstallation && !$this->dbModelUpgrade && $this->checkSystemAdmin ) {
				call_user_func( $this->checkSystemAdmin );
			}

			if( $this->phase == 'update_db' || $this->phase == 'install_db' ) {

				// Prevent mssql timeout error during upgrade.
				if( $this->dbModelUpgrade && DBTYPE == 'mssql' ) {
					if( intval( ini_get( 'mssql.timeout' ) ) <= 120 ) {
						ini_alter( 'mssql.timeout', '360' );
					}
				}

				// Let subclasses do their specifics.
				$this->beforeRunSql();

				// Run the DB installation scripts (SQL).
				if( $this->canContinue() ) {
					$dbDriver = DBDriverFactory::gen();
					foreach( $this->dbModelScripts as $sqlScript ) {
						$this->runSqls( $dbDriver, $sqlScript->getSqlFilePath() );
						$this->sqlReport->add( 'DbInstaller', 'INFO', 'INFO',
							$sqlScript->getSqlFilePath(), '', '',
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
						// to call runDataUpgrades() to make sure they get flagged afterwards.
						$this->runDataUpgrades();

						// Inform the user about data upgrade success.
						if( $this->dbDataUpgrade && $this->canContinue() ) {
							$this->report->add( 'DbInstaller', 'INFO', 'INFO',
								BizResources::localize( 'DBINSTALLER_DBDATA_MIGRATED' ), '', '',
								array( 'phase' => $this->phase ) );
						}

						// After a DB model update, remind the user to re-index Solr.
						if( $this->dbModelUpgrade && $this->showSolrReindexMessageAfterSuccess() ) {
							$this->report->add( 'DbInstaller', 'INFO', 'INFO',
								BizResources::localize( 'SOLR_RE_INDEX' ), '', '',
								array( 'phase' => $this->phase ) );
						}

						// Remind the user to navigate to the license page.
						if( $this->gotoLicensePageAfterSuccess() ) {
							$params = array( $this->localizePhase( 'goto_licenses' ) );
							$this->report->add( 'DbInstaller', 'INFO', 'INFO',
								BizResources::localize( 'DBINSTALLER_CLICK_LICENSE_BTN', true, $params ), '', '',
								array( 'phase' => $this->phase ) );
						}

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
	 * Allow subclass to check things just before calling runSql().
	 *
	 * The subclass can abort installation by reporting errors through $this->report->add().
	 */
	protected function beforeRunSql()
	{
	}

	/**
	 * Allow subclass to check if the preconditions to do an upgrade are met.
	 */
	protected function checkPreConditionsUpgrade()
	{
	}

	/**
	 * Allow subclass to disable the last step offering nagivation to the license page.
	 *
	 * @return bool
	 */
	protected function gotoLicensePageAfterSuccess()
	{
		return true;
	}

	/**
	 * Allow subclass to suppress the reminder for user to re-index Solr.
	 *
	 * @return bool
	 */
	protected function showSolrReindexMessageAfterSuccess()
	{
		return true;
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
			BizResources::localize( 'DBINSTALLER_REQUIRED_DBVERSION' ) => $this->wantedVersion );
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
	abstract public function getInstalledDbVersion();

	/**
	 * Determines the installation mode for the database. Doing so, all kind of local
	 * member attributes are initialized, such as newInstallation, upgrade, majorUpdate, etc
	 */
	private function determineRunMode()
	{
		$this->installedVersion = null;
		$this->newInstallation = false;
		$this->dbModelUpgrade = false;
		$this->dbDataUpgrade = false;
		$this->majorUpdate = false;
		if( $this->canContinue() ) {

			if( $this->phase == 'connect_db' ) {
				$message = BizResources::localize( 'DBINSTALLER_CONNECTEDTODB', true, array(DBSELECT) );
				$this->report->add( 'DbInstaller', 'INFO', 'INFO', $message, '', '',
					array( 'phase' => $this->phase ) );
			}

			$this->installedVersion = $this->getInstalledDbVersion();
			if( $this->installedVersion === null ) {
				$this->newInstallation = true; // No version found
			} else {
				// Determine whether or not this is a major update, such as 8.2 => 9.0
				// It will be set FALSE when there is minor update, such as 8.0 => 8.2
				require_once BASEDIR.'/server/utils/VersionUtils.class.php';
				$installedVersionInfo = VersionUtils::getVersionInfo( $this->installedVersion );
				$wantedVersionInfo = VersionUtils::getVersionInfo( $this->wantedVersion );
				if( $installedVersionInfo !== false && $wantedVersionInfo !== false ) {
					$this->majorUpdate = $installedVersionInfo['major'] != $wantedVersionInfo['major'];
				}

				$sqlScripts = $this->getDbModelScripts( $this->installedVersion,false, false ); // Look for patches
				if( version_compare( $this->wantedVersion, $this->installedVersion, '>' ) || count ( $sqlScripts ) > 0 ) { // Need to upgrade
					$this->dbModelUpgrade = true;
					$this->checkPreConditionsUpgrade();
				} else {
					$this->report->add( 'DbInstaller', 'INFO', 'INFO',
						BizResources::localize( 'OBJ_DATABASE_TABLES_ARE_UP_TO_DATE' ).
						' ( '.BizResources::localize( 'OBJ_VERSION' ) .' '.$this->wantedVersion.' )', '', '',
						array( 'phase' => $this->phase ) );
				}
			}

			// If the database is up to date it might be needed to check / update objects.
			if( !$this->newInstallation && $this->needsDataUpgrade() ) {
				$this->dbDataUpgrade = true;
			}
		}
	}

	/**
	 * Allows the subclass to provide the path to the dbupgrades folder.
	 *
	 * @return null|string Full path, or NULL when no folder available.
	 */
	protected function getDataUpgradesFolder()
	{
		return null;
	}

	/**
	 * Determines what data modifications needs to be ran. If the model needs to be updated these scripts are ran after
	 * the database moel has been changed. If the model is already up to date these scripts can just be executed.
	 *
	 * @return array Array containing the available updates and their installation states.
	 */
	private function determineDataUpgrades()
	{
		$upgrades = array();
		$dbUpgradeFiles = $this->getDataUpgradeFiles();
		if( $dbUpgradeFiles ) foreach( $dbUpgradeFiles as $dbUpgradeFile ) {
			require_once $this->getDataUpgradesFolder().$dbUpgradeFile;
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
	public function getDataUpgradeFiles()
	{
		$dbDataUpgradeFiles = array();
		$dirName = $this->getDataUpgradesFolder();
		if( $dirName && ( $thisDir = opendir( $dirName ) ) ) {
			while( ( $itemName = readdir( $thisDir ) ) !== false ) {
				if( is_file( $dirName.$itemName ) && $itemName[0] !== '.' ) { // Skip hidden files.
					$dbDataUpgradeFiles[] = $itemName;
				}
			}
			closedir( $thisDir );
		}
		return $dbDataUpgradeFiles;
	}

	/**
	 * Checks if any of the data upgrade scripts needs to be executed.
	 *
	 * @return bool Whether or not an upgrade of the data is needed.
	 */
	public function needsDataUpgrade()
	{
		$retVal = false;
		$upgrades = $this->determineDataUpgrades();
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
	private function determineDataUpgradeScripts()
	{
		$scripts = array();
		$upgrades = $this->determineDataUpgrades();
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
	private function runDataUpgrades()
	{
		$upgrades = $this->determineDataUpgrades();
		if( $upgrades ) foreach( $upgrades as $version ) {
			foreach( $version as $upgrade ) {
				if( $this->dbDataUpgrade ) {
					// If there is a need to update the DB, run the update script.
					// In case of an error, update the report to inform admin user.
					$script = get_class($upgrade['object']).'.class.php';
					if( $upgrade['upgrade'] === true ) {
						if ( !$this->newInstallation ) {
							$result = $upgrade['object']->run();
							if( $result ) {
								LogHandler::Log( 'DbInstaller', 'INFO',
									'Successfully run DB migration script '.$script );
							} else {
								$this->report->add( 'DbInstaller', 'FATAL', 'ERROR',
									BizResources::localize('DBINSTALLER_DBUPGRADE_FAILED'),
									BizResources::localize('SEELOGFILES'),
									'', array( 'phase' => $this->phase ) );
							}
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
	 * Reads the SQL modules from the server/dbscripts folder and validates if all complete.
	 *
	 * NOTE: In theory the system admin could have changed the package. This is far from likely
	 * to happen, nevertheless this function does all kind of paranoid checksums. Because
	 * in practice this never will go wrong, error strings are English-only in this function.
	 *
	 * @return WW_DbScripts_FileDescriptor[] List of SQL files to execute.
	 */
	private function checkdbModelScripts()
	{
		$sqlScripts = array();
		if( $this->canContinue() ) {
			// Check if the package is complete. This is an extra checksum especially for development and QA.
			$sqlScripts = $this->getDbModelScripts( null, true, false );
			if( count($sqlScripts) == 0 ) {
				$detail = 'The clean database installation scripts for v'.$this->wantedVersion.
					' are missing at '.$this->scriptFileHandler->getScriptsFolder().'.';
				$this->report->add( 'DbInstaller', 'FATAL', 'ERROR',
					'Incomplete installation package.', $detail, '',
					array( 'phase' => $this->phase ) );
			}
		}
		if( $this->canContinue() && $this->dbModelUpgrade ) {
			$sqlScripts = $this->getDbModelScripts( $this->minGenVersion, false, true );
			if( count($sqlScripts) == 0 ) {
				$detail = 'The database update scripts for v'.$this->minGenVersion.' => v'.$this->wantedVersion.
					' are missing at '.$this->scriptFileHandler->getScriptsFolder().'.';
				$this->report->add( 'DbInstaller', 'FATAL', 'ERROR',
					'Incomplete installation package.', $detail, '',
					array( 'phase' => $this->phase ) );
			}
		}

		if( $this->canContinue() ) {
			// Get the SQL scripts to use for the installation or update of the database.
			$sqlScripts = $this->getDbModelScripts( $this->installedVersion, $this->newInstallation, $this->dbModelUpgrade );
			if( $this->canContinue() ) {
				if( count( $sqlScripts ) == 0 )  {
					if( version_compare( $this->wantedVersion, $this->installedVersion, '>' ) ) {
						$help = 'Your database model is too old. First, you need an older version of '.
							'Enterprise Server to update your database to an intermediate version before '.
							'you can continue. See Admin Guide for more details.';
					} else {
						$help = 'Your database model is too new. You need a new version of Enterprise Server '.
							'to work with this database.';
					}
					if( $this->newInstallation ) {
						$detail = 'Clean installation for v'.$this->installedVersion.' is not supported. ';
						$this->report->add( 'DbInstaller', 'FATAL', 'ERROR',
							'No update path available.', $detail, $help,
							array( 'phase' => $this->phase ) );
					}
					if( $this->dbModelUpgrade ) {
						$detail = 'Update from v'.$this->installedVersion.' to v'.$this->wantedVersion.' is not supported. ';
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
	 * @param string $installedVersion Currently installed DB version in 'major.minor' notation.
	 * @param bool $newInstallation
	 * @param bool $upgrade
	 * @return WW_DbScripts_FileDescriptor[] List of SQL files to execute.
	 */
	public function getDbModelScripts( $installedVersion, $newInstallation, $upgrade )
	{
		/** @var WW_DbScripts_FileDescriptor[] $selectFiles */
		$selectFiles = array();
		$files = $this->scriptFileHandler->getSqlFiles( DBTYPE );
		foreach( $files as $file ) {
			if( $newInstallation ) {
				if( $file->isFullInstallType() ) {
					if( $file->versionFrom == $this->wantedVersion ) {
						$selectFiles[] = $file;
					}
				}
			} elseif( $upgrade ) {
				if( $file->isUpgradeType() || $file->isPreUpgradeType() || $file->isPostUpgradeType() ) {
					if( $file->versionFrom == $installedVersion && $file->versionTo == $this->wantedVersion ) {
						$selectFiles[] = $file;
					}
				}
			}
			if( !$newInstallation ) {
				if( $file->isPatchType() ) { // Patches are not applicable in case of a new installation.
					// But in case there is no 'normal' upgrade still check if a patch must be installed. Either we are moving
					// a patched release or the installed version was patched.
					if( ( $file->versionFrom == $this->wantedVersion && $file->versionTo == $installedVersion ) ||
						( $file->versionFrom == $installedVersion && $file->versionTo == $this->wantedVersion ) ) {
						require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
						if( !DBConfig::getValue( $file->patchName ) ) { // Check if the patch is already installed.
							$selectFiles[] = $file;
						}
					}
				}
			}
		}
		$this->filterDbModelScripts( $installedVersion, $newInstallation, $upgrade, $selectFiles );

		usort( $selectFiles, array( 'WW_DbScripts_FileDescriptor', 'compare' ) );
		return $selectFiles;
	}

	/**
	 * Allow subclass to add/remove any
	 *
	 * @param string $installedVersion Currently installed DB version in 'major.minor' notation.
	 * @param bool $newInstallation
	 * @param bool $upgrade
	 * @param WW_DbScripts_FileDescriptor[] $selectFiles
	 */
	protected function filterDbModelScripts( $installedVersion, $newInstallation, $upgrade, &$selectFiles )
	{
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
	 * Returns info (tip) how to setup DB connection.
	 *
	 * @return string
	 */
	protected function getDbConnectionTip()
	{
		$params = array( DBSERVER, DBUSER, DBSELECT, $this->localizePhase('connect_db') );
		$tip = BizResources::localize( 'DBINSTALLER_DBSETTINGS_HELP', true, $params );
		$tip = str_replace( "\\n", '<br />', $tip );
		return $tip;
	}

	/**
	 * Retrieves a list of SQL scripts that are needed to install the database.
	 *
	 * @return string[]
	 */
	public function getAllSqlScripts()
	{
		if( $this->canContinue() && $this->phase == 'connect_db' ) {
			$sqlScripts = array_map(
				function( WW_DbScripts_FileDescriptor $fd ) {
					$filePath = $fd->getSqlFilePath();
					// To provide a little context without giving the full file paths (huge!),
					// take the filename and prefix it with 2 parent folders, for example:
					// - ContentStation/dbscripts/ent_1.0_mysql.sql
					// - server/dbscripts/ent_10.2_mysql.sql
					return substr( $filePath, strlen( dirname( $filePath, 3 ) ) + 1 );
				},
				$this->dbModelScripts
			);
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
	 *
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
}
