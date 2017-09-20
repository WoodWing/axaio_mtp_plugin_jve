<?php
/**
 * Installer for the database of Enterprise server core. See base class for more details.
 *
 * @package    Enterprise
 * @subpackage dbscripts
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/dbscripts/dbinstaller/Base.class.php';

class WW_DbScripts_DbInstaller_CoreServer extends WW_DbScripts_DbInstaller_Base
{
	/**
	 * @inheritdoc
	 */
	public function getInstalledDbVersion()
	{
		// Having no DB connection (S1003) is very well possible and therefor changed
		// into INFO when logged. Reason is that this installer is there to guide the
		// admin user setting up the DB. So no reason to panic yet. Nevertheless, this
		// must be reported to screen as a FATAL since it is blocking the installer from
		// offering next steps in the installation procedure.
		$map = new BizExceptionSeverityMap( array( 'S1003' => 'INFO' ) );

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
	 * @inheritdoc
	 */
	protected function filterDbModelScripts( $installedVersion, $newInstallation, $upgrade, &$selectFiles )
	{
		if( version_compare( $installedVersion, '8.0', '>=' ) && version_compare( $installedVersion, '9.0', '<' ) ) {
			// Check if database changes are already made in version 8.3.4 or later. See BZ#34633.
			// If the database changes are not found we do not have to remove them. But if they are found the
			// ...800_920_pre... script must be added. That script will remove the changes so they can be added
			// later on in the normal ...800_920... script can run without causing errors.
			if( !$this->indexOnInDesignServerJobsExists() ) {
				foreach( $selectFiles as $key => $selectFile ) {
					$isVersionFrom8 = version_compare( $selectFile->versionFrom, '8.0', '>=' ) && version_compare( $selectFile->versionFrom, '9.0', '<' );
					if( $selectFile->isPreUpgradeType() && $isVersionFrom8 ) {
						unset( $selectFiles[ $key ] );
						break;
					}
				}
			}
		}
	}

	/**
	 * Checks if the index 'objid_indesignserverjobs' on the smart_indesignserverjobs table exists.
	 *
	 * @return boolean Index exists.
	 */
	private function indexOnInDesignServerJobsExists()
	{
		$dbdriver = DBDriverFactory::gen();
		$indexes = $dbdriver->listIndexOnTable( $dbdriver->tablename( 'indesignserverjobs' ) );
		return in_array( 'objid_indesignserverjobs', $indexes );
	}

	/**
	 * @inheritdoc
	 */
	protected function beforeRunSql()
	{
		// Before the migration, we want to make sure that the ServerJobs table is empty.
		// Reason is that new fields have been added and old fields have been taken out in v9.4.
		// We don't want to convert the old fields data into the new fields format as every
		// ServerJob Type might carry different type of data. So for convenience, we clear
		// everything before migrating to the latest version 9.4.
		// @todo The if-part below can be removed when $this->minGenVersion >= 9.3
		if( $this->dbModelUpgrade &&
			( version_compare( $this->installedVersion, '8.0', '>=' ) && version_compare( $this->installedVersion, '9.2', '<=' ) ) ) {
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
		// @todo The if-part below can be removed when $this->minGenVersion >= 9.7
		if( $this->dbModelUpgrade && version_compare( $this->installedVersion, '9.6', '<=' ) ) {
			if( !$this->isInDesignServerJobsEmpty() ) {
				if( $this->cleanUpTable( 'indesignserverjobs' ) ) {
					$this->dbDataUpgrade = true;
				} else {
					// If the 'indesignserverjobs' table is not empty, do not proceed.
					$this->report->add( 'DbInstaller', 'FATAL', 'ERROR',
						'Could not clear the smart_indesignserverjobs table.', // should never happen, so English only
						'', '', array( 'phase' => $this->phase ) );
				}
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function checkPreConditionsUpgrade()
	{
		// Before the migration, we want to make sure that the ServerJobs table is empty.
		// Reason is that new fields have been added and old fields have been taken out in v9.4.
		// We don't want to convert the old fields data into the new fields format as every
		// ServerJob Type might carry different type of data. So for convenience, we clear
		// everything before migrating to the latest version 9.4.
		// Only show the warning at the initial page load (when checking the connection).
		// @todo The if-part below can be removed when $this->minGenVersion >= 9.2
		if( ( version_compare( $this->installedVersion, '8.0', '>=' ) && version_compare( $this->installedVersion, '9.2', '<=' ) ) ) {
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
		// @todo The if-part below can be removed when $this->minGenVersion >= 9.7
		if( version_compare( $this->installedVersion, '9.6', '<=' ) ) {
			if( !$this->isInDesignServerJobsEmpty() &&  $this->phase == 'connect_db' ) {
				$this->report->add( 'DbInstaller', 'WARN', 'INFO',
					BizResources::localize( 'MSG_EMPTY_INDESIGNSERVERJOBS' ), '', '',
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
	private function cleanUpTable( $tableName )
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
	 * @inheritdoc
	 */
	protected function getDataUpgradesFolder()
	{
		return BASEDIR.'/server/dbscripts/dbupgrades/';
	}

	/**
	 * @inheritdoc
	 */
	protected function getDataUpgradeClassPrefix()
	{
		return 'WW_DbScripts_DbUpgrades_';
	}
}