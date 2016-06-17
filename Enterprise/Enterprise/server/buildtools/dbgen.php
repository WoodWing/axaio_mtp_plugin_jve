<?php

/**
 * Generator for database scripts, for developers only!
 * This generates the SQL modules picked up by database installation/update scripts.
 *
 * Use "dbgen.php?verbose=full" to dump all generated SQL on screen too.
 *
 * @package 	Enterprise
 * @subpackage 	Admin
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/dbscripts/dbmodel.php';

ini_set('display_errors', 1);
$verboseMode = isset($_GET['verbose']) && $_GET['verbose'] == 'full'; // running verbose mode?

$dbGen = new DbGenTool( $verboseMode );
$dbGen->generateSqlScripts();

class DbGenTool
{
	private $scriptDir;
	private $supportedDBMS;

	public function __construct( $versboseMode )
	{
		$this->verboseMode = $versboseMode;
		$this->supportedDBMS = array( 'mysql', 'mssql', 'oracle' );
	}

	/**
	 * Generates SQL scripts for MySQL, MS SQL and Oracle
	 */
	public function generateSqlScripts()
	{
		// Where to generate files.
		$this->scriptDir = BASEDIR.'/server/dbscripts';

		// Initiate database structure and determine versions.
		$this->dbStruct = new DBStruct();
		$allVersions = $this->dbStruct->getVersions();
		$this->lastVersionIdx = count($allVersions)-1;
		$this->lastVersion = $allVersions[$this->lastVersionIdx]; // Example: 800
		$this->lastMajorMinorVersion = substr($this->lastVersion,0,-2).'.'.substr($this->lastVersion,-2,1); // Example: 8.0

		// Which updates needs to be generated (empty if none).
		$this->previousVersions = $this->dbStruct->getDbMigrationVersions();
		$this->upgradeVersions = array($this->lastVersion);

		// Get all, if any, manually coded instructions.
		$files = scandir($this->scriptDir);
		$this->addedCode = array();
		foreach( $files as $file ) {
			if (substr($file, -4) != ".txt") {
				continue;
			}
			$r = array();
			if (preg_match('/scent([0-9]*)\.([a-z]*).txt/i', $file, $r) > 0) {
				$this->addedCode[$r[2]][$r[1]] = $file;
			}
		}

		// Generate the DD (data definition) scripts.
		foreach ($this->supportedDBMS as $DBMS ) {
			$this->generateDDSScripts( $DBMS );
		}

		// Data conversion.
		require_once BASEDIR.'/server/dbscripts/DBConversion.php';
		foreach ( array('pre', 'post') as $mode ) {
			foreach ( $this->previousVersions as $previousVersion ) {
				foreach ( $this->supportedDBMS as $DBMS ) {
					$generator = $this->createGenerator( $DBMS, false );
					$dbmsName = $generator->getDBName();
					DBConversion::generateDBConvScripts( $generator, $mode, $previousVersion, $this->lastVersion );
					$sqlFile = "{$this->scriptDir}/scent{$previousVersion}_{$this->lastVersion}_{$mode}.{$dbmsName}";
					$this->materializeSQLFile( $generator, $sqlFile, false );
					$this->logErrors( $generator->getErrors() );
				}
			}
		}
		print '<br/>Done!';
	}

	/**
	 * Generates Data Definitions Statements scripts for specified DBMS (database management system). Scripts are
	 * generated for new installations and for upgrades from previous versions. Finally specific scripts are created
	 * to handle patches.
	 *
	 * @param string DBMS Database Management System
	 */
	private function generateDDSScripts( $DBMS )
	{
		$generator = $this->createGenerator( $DBMS, true );
		$this->dbStruct->generate($this->lastVersion, $generator);
		$generator->setVersion($this->lastMajorMinorVersion);
		$dbmsName = $generator->getDBName();

		// Scripts used for new installations.
		$sqlFile = "{$this->scriptDir}/scent{$this->lastVersion}.{$dbmsName}";
		$this->materializeSQLFile( $generator, $sqlFile, true );

		$this->logErrors( $generator->getErrors() );
		$this->logErrors( $this->dbStruct->getErrors() );

		// Scripts used for upgrades.
		foreach ($this->previousVersions as $previousVersion) {
			foreach ($this->upgradeVersions as $upgradeVersion) {
				if (intval($previousVersion) < intval($upgradeVersion)) {
					$generator = $this->createGenerator( $DBMS, true );
					$this->dbStruct->generateUpgrade($previousVersion, $upgradeVersion, $generator);
					if (isset($this->addedCode[$dbmsName])) {
						foreach ($this->addedCode[$dbmsName] as $k => $file) {
							if ($k > $previousVersion && $k <= $upgradeVersion) {
								$generator->addTxt(file_get_contents("{$this->scriptDir}/$file") . "\r\n");
							}
						}
					}
					$generator->setVersion($this->lastMajorMinorVersion);

					$sqlFile = "{$this->scriptDir}/scent$previousVersion" . "_{$upgradeVersion}.{$dbmsName}";
					$this->materializeSQLFile( $generator, $sqlFile, true );

					$previousVersion = $upgradeVersion; // Upgrading is done from previous to intermediate versions.
					$this->logErrors( $generator->getErrors() );
					$this->logErrors( $this->dbStruct->getErrors() );
				}
			}
		}

		// Scripts to handle patches.
		$this->addPatchScripts( $DBMS );

		$this->logErrors( $generator->getErrors() );
		$this->logErrors( $this->dbStruct->getErrors() );
	}

	/**
	 * Adds patches to the SQL-scripts. Patches are changes made in the database model during a patch release.
	 * The patches must be applied when an upgrade is done from a previous, major, version but also when the current
	 * version is patched.
	 *
	 * @param string DBMS Database Management System
	 */
	private function addPatchScripts( $DBMS)
	{
		$patches = $this->dbStruct->getPatchInfo( $this->lastVersion );
		$patchVersions = array_merge( $this->previousVersions, $this->upgradeVersions ); // Patches are applied on the
		// previous version and the current version.
		foreach ($patchVersions as $patchVersion) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			foreach ( $this->upgradeVersions as $upgradeVersion ) {
				$this->generatePatchScripts( $patches, $DBMS, $patchVersion );
			}
		}

	}

	/**
	 * Logs generated SQL file to web browser screen (HTML format)
	 *
	 * @param string $dbmsName DB flavor
	 * @param string $sqlFile Full path of generated SQL file
	 */
	private function logSQL( $dbmsName, $sqlFile )
	{
		$title = 'Unknown DBMS';
		switch ( $dbmsName ) {
			case 'mysql':
				$title = 'MySQL';
				break;
			case 'mssql':
				$title= 'MS SQL';
				break;
			case 'ora':
				$title = 'Oracle';
				break;
		}
		if( $this->verboseMode === true ) {
			$sql = nl2br(file_get_contents( $sqlFile ));
			print '========================================================<br/>';
			print $title.' - Generated '.$sqlFile.'<br/>';
			print '========================================================<br/>';
			print '<code>'.$sql.'</code>';
		} else {
			print $title.' - Generated '.$sqlFile.'<br/>';
		}
	}

	/**
	 * Logs errors to web browser screen (HTML format).
	 *
	 * @param String[] $errors
	 */
	private function logErrors( array $errors )
	{
		if( $errors ) foreach( $errors as $error ) {
			echo '<p style="padding-left: 25px;"><font color="red">ERROR: </font>'.$error.'</p>';
		}
	}

	/**
	 * Creates scripts that are needed for upgrades from versions that did have patches on the database model. These
	 * scripts are used if the previous version did not have the patch installed.
	 *
	 * @param array $patches Patch info.
	 * @param string $DBMS Name of the database.
	 * @param string $previousVersion Version from which the upgrade is done.
	 */
	private function generatePatchScripts( $patches, $DBMS, $previousVersion )
	{
		$generator = $this->createGenerator( $DBMS, true );
		$dbmsName = $generator->getDBName();
		foreach ( $patches as $patch ) {
			if ( intval( $patch['version'] ) == intval( $this->lastVersion ) ||
				 intval( $patch['version'] ) == intval( $previousVersion ) ) {
				// Generate scripts for patches introduced in previous versions.
				$generator->clean();
				$sqlFile = "{$this->scriptDir}/scent{$previousVersion}_{$this->lastVersion}_patch_{$patch['name']}_{$dbmsName}";
				$allVersions = $this->dbStruct->getVersions();
				$patchIndex = array_search( $patch['version'], $allVersions );
				$prePatchVersion = $allVersions[$patchIndex - 1];
				// Patches are a change compared with the preceding version in which the patch is introduced.
				$this->dbStruct->generatePatch( $prePatchVersion, $patch['version'], $patch, $generator );
				$this->materializeSQLFile( $generator, $sqlFile, true );
			}
		}
	}

	/**
	 * Creates files in the scripts folder with the proper extensions.
	 *
	 * @param object $generator	Generator class with logic and storage
	 * @param string $sqlFile Name of the sql file without extension.
	 * @param bool $twoUsers If separate scripts must be generated for normal and system users. Only used for Oracle.
	 */
	private function materializeSQLFile( $generator, $sqlFile, $twoUsers = false )
	{
		$dbmsName = $generator->getDBName();
		$sqlFilePlusExt = $sqlFile.'.sql';
		if( $generator->materialize( $sqlFilePlusExt ) ) {
			$this->logSQL( $dbmsName, $sqlFilePlusExt, $this->verboseMode );
		}

		if ( $dbmsName == 'ora' && $twoUsers ) {
			$sqlFilePlusExt = $sqlFile.'.sys.sql';
			if( $generator->materializeSys( $sqlFilePlusExt ) ) {
				$this->logSQL( $dbmsName, $sqlFilePlusExt );
			}

			$sqlFilePlusExt = $sqlFile.'.trx.sql';
			if( $generator->materializeTrx( $sqlFilePlusExt ) ) {
				$this->logSQL( $dbmsName, $sqlFilePlusExt );
			}
		}
	}

	/**
	 * Returns a script generator for a particular DBMS.
	 *
	 * @param string $DBMS Name of the Database Management System.
	 * @param bool $twoUsers If separate scripts must be generated for normal and system users. Only used for Oracle.
	 * @return MssqlGenerator|MysqlGenerator|OraGenerator|null
	 */
	private function createGenerator( $DBMS, $twoUsers )
	{
		$generator = null;

		switch ( $DBMS ) {
			case 'mysql':
				$generator  = new MysqlGenerator( false );
				break;
			case 'mssql':
				$generator = new MssqlGenerator( false );
				break;
			case 'oracle':
				if ( $twoUsers) {
					$generator = new OraGenerator( false, 'root', 'woodwing' );
				} else {
					$generator = new OraGenerator( false );
				}
				break;
		}

		return $generator;
	}
}
