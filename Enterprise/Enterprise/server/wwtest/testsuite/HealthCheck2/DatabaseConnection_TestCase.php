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
	
	final public function runTest()
	{
		// Check DBTYPE option
		$help = 'The DBTYPE option in configserver.php should be "mysql" or "mssql".';
		$definedDbType = defined('DBTYPE') ? trim(DBTYPE) : null;
		if( is_null($definedDbType) || empty($definedDbType) ) {
			$this->setResult( 'FATAL', 'The DBTYPE option is not defined or not filled in.', $help );
			return;
		}
		if( DBTYPE != 'mysql' && DBTYPE != 'mssql' ) {
			$this->setResult( 'FATAL', 'Database type option not recognized: "'.DBTYPE.'"', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Database type (DBTYPE) correct: '.DBTYPE);
		
		// Check the DBSERVER, DBSELECT, DBUSER, DBPASS options.
		$help = 'The DBSERVER option should be configured at configserver.php.';
		$definedDbServer = defined('DBSERVER') ? trim(DBSERVER) : null;
		if( is_null($definedDbServer) || empty($definedDbServer) ) {
			$this->setResult( 'FATAL', 'The DBSERVER option is not defined or not filled in.', $help );
			return;
		}
		$help = 'The DBSELECT, DBUSER and DBPASS options should be configured at config.php.';
		$definedDbSelect = defined('DBSELECT') ? trim(DBSELECT) : null;
		if( is_null($definedDbSelect) || empty($definedDbSelect) ) {
			$this->setResult( 'FATAL', 'The DBSELECT option is not defined or not filled in.', $help );
			return;
		}
		$definedDbUser = defined('DBUSER') ? trim(DBUSER) : null;
		if( is_null($definedDbUser) || empty($definedDbUser) ) {
			$this->setResult( 'FATAL', 'The DBUSER option is not defined or not filled in.', $help );
			return;
		}
		if( !defined('DBPASS') ) {
			$this->setResult( 'FATAL', 'The DBPASS option is not defined.', $help );
			return;
		}
		
		// Check if PHP extension for database is loaded.
		$dbConfig = '- Database user (DBUSER): '.DBUSER.'<br/>';
		$dbConfig .= '- Database type (DBTYPE): '.DBTYPE.'<br/>';
		$dbConfig .= '- Database server (DBSERVER): '.DBSERVER.'<br/>';
		$dbConfig .= '- Database name (DBSELECT): '.DBSELECT.'<br/>';
		$dbdriver = DBDriverFactory::gen( DBTYPE, DBSERVER, DBUSER, DBPASS, DBSELECT, false );
		if( !$dbdriver->isPhpDriverExtensionLoaded() ) {
			$msg = null;
			$help = null;
			switch( DBTYPE )
			{
				case 'mysql':
					$msg = 'Could not load the PHP extension "mysqli".';
					$help = 'Please make sure that:  <br/>'.
						 '- The PHP extension for MySQL (mysqli) is installed. <br/>' .
						 '- The PHP extension "mysqli" is enabled in the php.ini file.<br/>';
				break;
				case 'mssql':
					$msg = 'Could not load the PHP extension "Microsoft Driver for PHP for SQL Server".';
					$help = 'Please make sure that:  <br/>'.
						'- The PHP extension for MSSQL (php_sqlsrv) version 4.3 is installed. <br/>' .
						'- The PHP extension for MSSQL is enabled in the php.ini file.<br/>'.
						'- The PHP extension for MSSQL is PHP 7.1 and 64 bit compatible.<br/>'.
						 'For more information about Microsoft Drivers for PHP for SQL Server, '.
						 'click <a href="http://technet.microsoft.com/en-us/library/cc296170(v=sql.105).aspx">here</a>.<br/>';
				break;
			}
			$this->setResult( 'FATAL', $msg, $help );
			return;
		}
	
		// Check if database is connected.
		if( !$dbdriver->isConnected() ) {
			$help = 'Please check the following options in the config.php and '.
					'configserver.php files:<br/>'.$dbConfig;
			if ( DBTYPE == 'mssql' ){
				// BZ#16885 we cannot check version at this point, so show hint
				$help .= 'Make sure that Microsoft ODBC Driver 11 (11.00.2100 or higher) for SQL Server or '.
					'Microsoft ODBC Driver 13.1 for SQL Server is installed.<br/>';
			}
			$msg = 'Could not connect to the database.<br/>';
			$dbError = trim($dbdriver->error());
			if( $dbError ) {
				$msg .= $dbError;
				$dbCode = trim($dbdriver->errorcode());
				if( $dbCode ) {
					$msg .= ' ('.$dbCode.')';
				}
			}
			$this->setResult( 'FATAL', $msg , $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', "Database connection established based on:\r\n{$dbConfig}" );
		
		// Check DB engine version
		$driverHelp = '';
		try {
			$dbdriver->checkDbVersion( $driverHelp );
			LogHandler::Log('wwtest', 'INFO', 'Database version checked.');		
	
			$dbdriver->checkDbSettings( $driverHelp );
			LogHandler::Log('wwtest', 'INFO', 'Database settings checked.');
			
		} catch( BizException $e ) {
			$this->setResult( 'FATAL', $e->getMessage().'<br/>'.$e->getDetail(), $driverHelp );
			return;
		}
	
		// Check DB model version
		$dbc = $dbdriver->tablename("config");
		$sql = "SELECT * FROM $dbc WHERE `name` = 'version'";
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		$version = $row['value'];
		$url = SERVERURL_ROOT.INETROOT.'/server/admin/dbadmin.php';
		if( $version != SCENT_DBVERSION ) {
			$help = 'Please update database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
			if($version == null) {
				$this->setResult( 'ERROR', 'Database is not initialized.', $help );
				return;
			}
			$this->setResult( 'ERROR', 'Actual version v'.$version.' does not meet the required version v'.SCENT_DBVERSION, $help );
			return;
		} else { // See if there are patches to be installed.
			require_once BASEDIR.'/server/dbscripts/DbInstaller.class.php';
			$checkSystemAdmin = array( $this, '' );
			$installer = new WW_DbScripts_DbInstaller( $checkSystemAdmin );
			$sqlScripts = $installer->getDbModelScripts( SCENT_DBVERSION, false, false ); // Look for patches();
			$dbUpgradeScripts = $installer->getDBUpgradeFiles();
			$notInstalled = false;
			if ( $dbUpgradeScripts ) foreach ( $dbUpgradeScripts as $dbUpgradeScript ) {
				require_once BASEDIR.'/server/dbscripts/dbupgrades/'.$dbUpgradeScript;
				$fileParts = explode( '.', $dbUpgradeScript );
				$className = $fileParts[0];
				$upgradeObject = new $className();
				if ( !$upgradeObject->isUpdated() ) {
					$notInstalled = true;
					break;
				}
			}
			if ( count( $sqlScripts ) > 0  || $notInstalled ) {
				$help = 'Please update database through this page: <a href="'.$url.'" target="_top">DB Admin</a>';
				$this->setResult( 'ERROR', 'Database patches are available to enable new functionality.', $help );
			}
		}

		LogHandler::Log('wwtest', 'INFO', 'Database version correct:'.$version);
		
		// Check if there is any users configured
		$dbc = $dbdriver->tablename("users");
		$sql = "SELECT * FROM $dbc";
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		if(!$row){
			$this->setResult( 'ERROR', 'There is no user in the database.' );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Database has users.');
		
		// BZ #18312 Check if there are deletedObjects with a higher id than the auto_increment of the objects
		$id = 0;
		$deletedId = 0;
		$result = $this->testDeletedObjectIDs($id, $deletedId);
		if (!$result) {
			$url = SERVERURL_ROOT.INETROOT.'/server/wwtest/testsuite/HealthCheck2/fixAutoIncrement.php?object_id='.$id.'&deleted_id='.$deletedId;
			$this->setResult( 'ERROR', 'The smart_objects and smart_deletedobjects tables are out of sync. Run <a href="'.$url.'" target="_blank">this</a> script to correct this.' );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Smart_objects auto_increment vs smart_deletedobjects max id checked.');

		// BZ#22212 Check if there are unused Overrule Brand Issues in production, which will slow down a lot on query performance
		$warnMsg = $this->checkUnusedOverruleBrandIssue();
		if( !empty($warnMsg) ) {
			$this->setResult( 'WARN', $warnMsg );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Unused Overrule Issue checked.');
	}
	
	/**
	 * Function checks if the id of an object in the 'smart_deletedobjects' table is higher than the auto_increment
	 * value of the 'smart_objects' (BZ#18312)
	 *
	 * @param integer $objectID
	 * @param integer $deletedId
	 * @return bool
	 */
	private function testDeletedObjectIDs(&$objectID, &$deletedId)
	{
		// Get the autoincrement id of the 'smart_objects' table
		$dbdriver = DBDriverFactory::gen();
		$nextObjectId = null;
		$objectsTable = DBPREFIX."objects";
		if ( DBTYPE == 'mysql' ) {
			$sql = "SHOW TABLE STATUS LIKE '$objectsTable'";
			$sth = $dbdriver->query($sql);
			$row = $dbdriver->fetch($sth);
			$nextObjectId = $row['Auto_increment'];
			//LogHandler::Log('wwtest', 'DEBUG', 'smart_objects autoincrement ID: '.print_r($objectsID, true));
		}
		elseif( DBTYPE == 'mssql') {
			$sql = "Select IDENT_CURRENT('$objectsTable') as id";
			$sth = $dbdriver->query($sql);
			$row = $dbdriver->fetch($sth);
			$nextObjectId = $row['id'] + 1; // The next identity value is the current
										 // value plus 1.
			//LogHandler::Log('wwtest', 'DEBUG', 'smart_objects autoincrement ID: '.print_r($objectsID, true));
		}

		// Get the highest id present in the 'smart_deletedobjects' table
		$deletedObjectsTable = DBPREFIX."deletedobjects";	
		$sql = "SELECT MAX(id) as `id` FROM `$deletedObjectsTable`";
		$sth = $dbdriver->query($sql);
		$row = $dbdriver->fetch($sth);
		$deletedObjectsID = $row['id'];
		//LogHandler::Log('wwtest', 'DEBUG', 'smart_deletedObjects result: '.print_r($deletedObjectsID, true));
	
		// Compare the id's. If the deletedObjects ID is equal or higher we are in a fault situation
		if ( $deletedObjectsID && $deletedObjectsID >= $nextObjectId ) {
			$objectID = $nextObjectId;
			$deletedId = $deletedObjectsID;
			return false;
		}
	
		return true;
	}
	
	/**
	 * Check whether exist unused overrule issue
	 *
	 * @return string $warnMsg
	 */
	private function checkUnusedOverruleBrandIssue()
	{
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$overruleIssues = DBIssue::listAllOverruleIssues();
		$warnMsg = '';
		if( !empty($overruleIssues) ) {
			$dbdriver = DBDriverFactory::gen();
			require_once BASEDIR.'/server/dbclasses/DBQuery.class.php';
			foreach( $overruleIssues as $issueId ) {
				$issuePieces[] = "tar.`issueid` IN (" . implode(',', array($issueId)) . ")";
				$sql = DBQuery::getIssueSubSelect($issuePieces);
				$sth = $dbdriver->query($sql);
				$row = $dbdriver->fetch($sth);
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
		return $warnMsg;
	}
}
