<?php
/**
 * DatabaseConnection TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package SCEnterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_DatabaseConnection_TestCase extends TestCase
{
	public function getDisplayName() { return 'Database Connection'; }
	public function getTestGoals()   { return 'Checks if the database is properly configured, has the right DB model version installed and is accessible by configured user. '; }
	public function getTestMethods() { return 'Tries to connect to database using the configured options at config.php and configserver.php. Also checks the configured character encoding by query database options.'; }
   public function getPrio()        { return 4; }

   /** @var WW_DbDrivers_DriverBase */
   private $dbdriver;

	final public function runTest()
	{
		do {
			if( !$this->checkDbConnectionSettings() ) {
				break;
			}
			if( !$this->checkDbConnection() ) {
				break;
			}
			if( !$this->checkDbModelCoreServer() ) {
				break;
			}
			if( !$this->checkDbModelServerPlugins() ) {
				break;
			}
			if( !$this->checkUserExists() ) {
				break;
			}
			if( !$this->checkDeletedObjectIDs() ) {
				break;
			}
			if( !$this->checkUnusedOverruleBrandIssue() ) {
				break;
			}
		} while( false ); // once
	}

	/**
	 * Check whether the DB connection setting are correctly filled in.
	 *
	 * @return bool
	 */
	private function checkDbConnectionSettings()
	{
		// Check DBTYPE option
		$help = 'The DBTYPE option in configserver.php should be "mysql" or "mssql".';
		$definedDbType = defined( 'DBTYPE' ) ? trim( DBTYPE ) : null;
		if( is_null( $definedDbType ) || empty( $definedDbType ) ) {
			$this->setResult( 'FATAL', 'The DBTYPE option is not defined or not filled in.', $help );
			return false;
		}
		if( DBTYPE != 'mysql' && DBTYPE != 'mssql' ) {
			$this->setResult( 'FATAL', 'Database type option not recognized: "'.DBTYPE.'"', $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'Database type (DBTYPE) correct: '.DBTYPE );

		// Check the DBSERVER, DBSELECT, DBUSER, DBPASS options.
		$help = 'The DBSERVER option should be configured at configserver.php.';
		$definedDbServer = defined( 'DBSERVER' ) ? trim( DBSERVER ) : null;
		if( is_null( $definedDbServer ) || empty( $definedDbServer ) ) {
			$this->setResult( 'FATAL', 'The DBSERVER option is not defined or not filled in.', $help );
			return false;
		}
		$help = 'The DBSELECT, DBUSER and DBPASS options should be configured at config.php.';
		$definedDbSelect = defined( 'DBSELECT' ) ? trim( DBSELECT ) : null;
		if( is_null( $definedDbSelect ) || empty( $definedDbSelect ) ) {
			$this->setResult( 'FATAL', 'The DBSELECT option is not defined or not filled in.', $help );
			return false;
		}
		$definedDbUser = defined( 'DBUSER' ) ? trim( DBUSER ) : null;
		if( is_null( $definedDbUser ) || empty( $definedDbUser ) ) {
			$this->setResult( 'FATAL', 'The DBUSER option is not defined or not filled in.', $help );
			return false;
		}
		if( !defined( 'DBPASS' ) ) {
			$this->setResult( 'FATAL', 'The DBPASS option is not defined.', $help );
			return false;
		}
		return true;
	}

	/**
	 * Check if we can connect to the DB and if DB driver versions are correct.
	 *
	 * @return bool
	 */
	private function checkDbConnection()
	{
		// Check if PHP extension for database is loaded.
		$dbConfig = '- Database user (DBUSER): '.DBUSER.'<br/>';
		$dbConfig .= '- Database type (DBTYPE): '.DBTYPE.'<br/>';
		$dbConfig .= '- Database server (DBSERVER): '.DBSERVER.'<br/>';
		$dbConfig .= '- Database name (DBSELECT): '.DBSELECT.'<br/>';
		$this->dbdriver = DBDriverFactory::gen( DBTYPE, DBSERVER, DBUSER, DBPASS, DBSELECT, false );
		if( !$this->dbdriver->isPhpDriverExtensionLoaded() ) {
			$msg = null;
			$help = null;
			switch( DBTYPE ) {
				case 'mysql':
					$msg = 'Could not load the PHP extension "mysqli".';
					$help = 'Please make sure that:  <br/>'.
						'- The PHP extension for MySQL (mysqli) is installed. <br/>'.
						'- The PHP extension "mysqli" is enabled in the php.ini file.<br/>';
					break;
				case 'mssql':
					$msg = 'Could not load the PHP extension "Microsoft Driver for PHP for SQL Server".';
					$help = 'Please make sure that:  <br/>'.
						'- The PHP extension for MSSQL (php_sqlsrv) version 4.3 is installed. <br/>'.
						'- The PHP extension for MSSQL is enabled in the php.ini file.<br/>'.
						'- The PHP extension for MSSQL is PHP 7.1 and 64 bit compatible.<br/>'.
						'For more information about Microsoft Drivers for PHP for SQL Server, '.
						'click <a href="http://technet.microsoft.com/en-us/library/cc296170(v=sql.105).aspx">here</a>.<br/>';
					break;
			}
			$this->setResult( 'FATAL', $msg, $help );
			return false;
		}

		// Check if database is connected.
		if( !$this->dbdriver->isConnected() ) {
			$help = 'Please check the following options in the config.php and '.
				'configserver.php files:<br/>'.$dbConfig;
			if( DBTYPE == 'mssql' ) {
				// BZ#16885 we cannot check version at this point, so show hint
				$help .= 'Make sure that Microsoft ODBC Driver 11 (11.00.2100 or higher) for SQL Server or '.
					'Microsoft ODBC Driver 13.1 for SQL Server is installed.<br/>';
			}
			$msg = 'Could not connect to the database.<br/>';
			$dbError = trim( $this->dbdriver->error() );
			if( $dbError ) {
				$msg .= $dbError;
				$dbCode = trim( $this->dbdriver->errorcode() );
				if( $dbCode ) {
					$msg .= ' ('.$dbCode.')';
				}
			}
			$this->setResult( 'FATAL', $msg, $help );
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', "Database connection established based on:\r\n{$dbConfig}" );

		// Check DB engine version
		$driverHelp = '';
		try {
			$this->dbdriver->checkDbVersion( $driverHelp );
			LogHandler::Log( 'wwtest', 'INFO', 'Database version checked.' );

			$this->dbdriver->checkDbSettings( $driverHelp );
			LogHandler::Log( 'wwtest', 'INFO', 'Database settings checked.' );

		} catch( BizException $e ) {
			$this->setResult( 'FATAL', $e->getMessage().'<br/>'.$e->getDetail(), $driverHelp );
			return false;
		}
		return true;
	}

	/**
	 * Check if there are any installations or updates needed for the installed DB model of the core server.
	 *
	 * @return bool
	 */
	private function checkDbModelCoreServer()
	{
		// Check if the DB model for the core server is installed or upgraded to the wanted/current/latest DB model version.
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$version = DBConfig::getSCEVersion();
		$url = SERVERURL_ROOT.INETROOT.'/server/admin/dbadmin.php';
		if( $version != SCENT_DBVERSION ) {
			if( $version == null ) {
				$help = 'Please install the database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
				$this->setResult( 'ERROR', 'Database is not initialized.', $help );
			} else {
				$help = 'Please update the database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
				$this->setResult( 'ERROR', 'The actual database model version v'.$version.
					'for the core server does not meet the required version v'.SCENT_DBVERSION, $help );
			}
			return false;
		}
		LogHandler::Log('wwtest', 'INFO', 'Database model version for the core server is correct: '.$version );

		// Check if there are patches or data upgrades to be installed for the core server.
		require_once BASEDIR.'/server/dbscripts/dbinstaller/CoreServer.class.php';
		$installer = new WW_DbScripts_DbInstaller_CoreServer( null );
		$sqlPatchScripts = $installer->getDbModelScripts( SCENT_DBVERSION, false, false ); // Look for patches only.
		if ( count( $sqlPatchScripts ) > 0  || $installer->needsDataUpgrade() ) {
			$help = 'Please update the database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
			$this->setResult( 'ERROR', 'Database patches are available for the core server which needs to be installed.', $help );
			return false;
		}
		LogHandler::Log('wwtest', 'INFO', 'DB patches and data upgrades are installed for core server.' );
		return true;
	}

	/**
	 * Check if there are any installations or updates needed for the installed DB models provided by the server plugins.
	 *
	 * @since 10.2.0
	 * @return bool
	 */
	private function checkDbModelServerPlugins()
	{
		require_once BASEDIR.'/server/dbscripts/dbinstaller/ServerPlugin.class.php';
		require_once BASEDIR.'/server/dbmodel/Factory.class.php';
		$pluginDbModelsOk = true;
		$pluginNames = WW_DbScripts_DbInstaller_ServerPlugin::getPluginsWhichProvideTheirOwnDbModel();
		foreach( $pluginNames as $pluginName ) {
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
					$this->setResult( 'ERROR', "The actual database model version v{$requiredVersion} ".
						"for {$pluginInfo->DisplayName} server plug-in does not meet the required version v{$requiredVersion}.", $help );
				}
				$pluginDbModelsOk = false; // flag as error, but continue checking the other plugins
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
					$pluginDbModelsOk = false; // flag as error, but continue checking the other plugins
				} else {
					LogHandler::Log( 'wwtest', 'INFO',
						"DB patches and data upgrades are installed for server plugin {$pluginName}." );
				}
			}
		}
		if( !$pluginDbModelsOk ) {
			return false;
		}
		LogHandler::Log( 'wwtest', 'INFO', 'Checked the DB models provided by the server plugins.' );
		return true;
	}

	/**
	 * Check if there is any users configured.
	 */
	private function checkUserExists()
	{
		$userCount = DBBase::countRecordsInTable( 'users', 'id' );
		if( !$userCount ) {
			$this->setResult( 'ERROR', 'There is no user in the database.' );
			return false;
		}
		LogHandler::Log('wwtest', 'INFO', "Database has {$userCount} users." );
		return true;
	}
	
	/**
	 * Function checks if the id of an object in the 'smart_deletedobjects' table is higher than the auto_increment
	 * value of the 'smart_objects' (BZ#18312)
	 *
	 * @return bool
	 */
	private function checkDeletedObjectIDs()
	{
		// Get the autoincrement id of the 'smart_objects' table
		$nextObjectId = null;
		$objectsTable = DBPREFIX."objects";
		if ( DBTYPE == 'mysql' ) {
			$sql = "SHOW TABLE STATUS LIKE '$objectsTable'";
			$sth = $this->dbdriver->query($sql);
			$row = $this->dbdriver->fetch($sth);
			$nextObjectId = $row['Auto_increment'];
		}
		elseif( DBTYPE == 'mssql') {
			$sql = "Select IDENT_CURRENT('$objectsTable') as id";
			$sth = $this->dbdriver->query($sql);
			$row = $this->dbdriver->fetch($sth);
			$nextObjectId = $row['id'] + 1; // The next identity value is the current value plus 1.
		}

		// Get the highest id present in the 'smart_deletedobjects' table
		$deletedObjectsTable = DBPREFIX."deletedobjects";	
		$sql = "SELECT MAX(id) as `id` FROM `$deletedObjectsTable`";
		$sth = $this->dbdriver->query($sql);
		$row = $this->dbdriver->fetch($sth);
		$deletedObjectsID = $row['id'];

		// Compare the id's. If the deletedObjects ID is equal or higher we are in a fault situation
		if ( $deletedObjectsID && $deletedObjectsID >= $nextObjectId ) {
			$objectID = $nextObjectId;
			$deletedId = $deletedObjectsID;
			$url = SERVERURL_ROOT.INETROOT.'/server/wwtest/testsuite/HealthCheck2/fixAutoIncrement.php?object_id='.$objectID.'&deleted_id='.$deletedId;
			$help = 'Run <a href="'.$url.'" target="_blank">this</a> script to correct this.';
			$this->setResult( 'ERROR', 'The smart_objects and smart_deletedobjects tables are out of sync.', $help );
			return false;
		}
		LogHandler::Log('wwtest', 'INFO', 'smart_objects auto_increment vs smart_deletedobjects max id checked.');

		return true;
	}
	
	/**
	 * Warn when an unused overrule issue can be found in the brand setup.
	 *
	 * @return bool
	 */
	private function checkUnusedOverruleBrandIssue()
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$overruleIssues = DBIssue::listAllOverruleIssues();
		$warnMsg = '';
		if( !empty($overruleIssues) ) {
			require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';
			foreach( $overruleIssues as $issueId ) {
				$issuePieces[] = "tar.`issueid` IN (" . implode(',', array($issueId)) . ")";
				$sql = DBQuery::getIssueSubSelect($issuePieces);
				$sth = $this->dbdriver->query($sql);
				$row = $this->dbdriver->fetch($sth);
				if( !$row ) {
					$issueName = DBIssue::getIssueName($issueId);
					$warnMsg .= empty($warnMsg) ? 'The following unused Overruled Issue has been found:<br/>' : ', ';
					$warnMsg .= $issueName;
				} else {
					$warnMsg = '';
					break; // At least one overrule brand issue is used.
				}
			}
		}
		$warnMsg .= empty($warnMsg) ? '' : '.<br/>Unused Overruled Issues will negatively affect query performance, we advice to remove this issue.';
		if( !empty($warnMsg) ) {
			$this->setResult( 'WARN', $warnMsg );
		}
		LogHandler::Log('wwtest', 'INFO', 'Unused Overrule Issue checked.');
		return true;
	}
}
