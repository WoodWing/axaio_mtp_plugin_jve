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
	/** @var string[] List of DB driver names that are supported by ES. */
	private $supportedDBMS;

	/** @var string[] list of versions in 'major.minor' notation  */
	private $previousVersions;

	/** @var string[] list of versions in 'major.minor' notation  */
	private $upgradeVersions;

	/** @var WW_DbScripts_FileHandler */
	private $scriptFileHandler = null;

	/** @var bool */
	private $verboseMode;

	/** @var DBStruct */
	private $dbStruct;

	/**
	 * Constructor.
	 *
	 * @param bool $versboseMode
	 */
	public function __construct( $versboseMode )
	{
		$this->verboseMode = $versboseMode;
		$this->supportedDBMS = DBDriverFactory::getSupportedDrivers();

		require_once BASEDIR.'/server/dbscripts/FileHandler.class.php';
		$this->scriptFileHandler = new WW_DbScripts_FileHandler();
	}

	/**
	 * Generates SQL scripts for MySQL, MS SQL and Oracle
	 */
	public function generateSqlScripts()
	{
		// Initiate database structure and determine versions.
		$this->dbStruct = new DBStruct();
		$allVersions = $this->dbStruct->getVersions();
		$this->lastVersion = end( $allVersions ); // Example: '10.2'

		// Which updates needs to be generated (empty if none).
		$this->previousVersions = $this->dbStruct->getDbMigrationVersions();
		$this->upgradeVersions = array($this->lastVersion);

		// Get all, if any, manually coded instructions.
		$this->addedCode = $this->scriptFileHandler->getManuallyCodedSqlFiles();

		// Generate the DD (data definition) scripts.
		foreach ($this->supportedDBMS as $dbms ) {
			$this->generateDDSScripts( $dbms );
		}

		// Data conversion.
		require_once BASEDIR.'/server/dbscripts/DBConversion.php';
		foreach ( array('pre', 'post') as $mode ) {
			foreach ( $this->previousVersions as $previousVersion ) {
				foreach ( $this->supportedDBMS as $dbms ) {
					$generator = $this->createGenerator( $dbms );
					$dbmsName = $generator->getDBName();
					DBConversion::generateDBConvScripts( $generator, $mode, $previousVersion, $this->lastVersion );
					$sqlFile = $this->scriptFileHandler->composeFilenameForPrePostUpdateScript( $previousVersion, $this->lastVersion, $dbmsName, $mode );
					$this->materializeSQLFile( $generator, $sqlFile );
					$this->logErrors( $generator->getErrors() );
				}
			}
		}
		print '<br/>Done!';
	}

	/**
	 * Generates Data Definitions Statements scripts for specified DBMS (database management system).
	 *
	 * Scripts are generated for new installations and for upgrades from previous versions. Finally specific scripts are
	 * created to handle patches.
	 *
	 * @param string $dbms DBMS Database Management System
	 */
	private function generateDDSScripts( $dbms )
	{
		$generator = $this->createGenerator( $dbms );
		$this->dbStruct->generate( $this->lastVersion, $generator );
		$generator->setVersion( $this->lastVersion );
		$dbmsName = $generator->getDBName();

		// Scripts used for new installations.
		$sqlFile = $this->scriptFileHandler->composeFilenameForIFullnstallScript( $this->lastVersion, $dbmsName );
		$this->materializeSQLFile( $generator, $sqlFile );

		$this->logErrors( $generator->getErrors() );
		$this->logErrors( $this->dbStruct->getErrors() );

		// Scripts used for upgrades.
		foreach( $this->previousVersions as $previousVersion ) {
			foreach( $this->upgradeVersions as $upgradeVersion ) {
				if( version_compare( $previousVersion, $upgradeVersion, '<' ) ) {
					$generator = $this->createGenerator( $dbms );
					$this->dbStruct->generateUpgrade( $previousVersion, $upgradeVersion, $generator );
					if( isset( $this->addedCode[ $dbmsName ] ) ) {
						foreach( $this->addedCode[ $dbmsName ] as $k => $file ) {
							if( $k > $previousVersion && $k <= $upgradeVersion ) {
								$generator->addTxt( file_get_contents( $file )."\r\n" );
							}
						}
					}
					$generator->setVersion( $this->lastVersion );

					$sqlFile = $this->scriptFileHandler->composeFilenameForUpdateScript( $previousVersion, $upgradeVersion, $dbmsName );
					$this->materializeSQLFile( $generator, $sqlFile );

					$previousVersion = $upgradeVersion; // Upgrading is done from previous to intermediate versions.
					$this->logErrors( $generator->getErrors() );
					$this->logErrors( $this->dbStruct->getErrors() );
				}
			}
		}

		// Scripts to handle patches.
		$this->addPatchScripts( $dbms );

		$this->logErrors( $generator->getErrors() );
		$this->logErrors( $this->dbStruct->getErrors() );
	}

	/**
	 * Adds patches to the SQL-scripts. Patches are changes made in the database model during a patch release.
	 * The patches must be applied when an upgrade is done from a previous, major, version but also when the current
	 * version is patched.
	 *
	 * @param string $dbms DBMS Database Management System
	 */
	private function addPatchScripts( $dbms)
	{
		$patches = $this->dbStruct->getPatchInfo( $this->lastVersion );
		$patchVersions = array_merge( $this->previousVersions, $this->upgradeVersions ); // Patches are applied on the
		// previous version and the current version.
		foreach ($patchVersions as $patchVersion) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			foreach ( $this->upgradeVersions as $upgradeVersion ) {
				$this->generatePatchScripts( $patches, $dbms, $patchVersion );
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
	 * @param string $dbms Name of the database.
	 * @param string $previousVersion Version from which the upgrade is done.
	 */
	private function generatePatchScripts( $patches, $dbms, $previousVersion )
	{
		$generator = $this->createGenerator( $dbms );
		$dbmsName = $generator->getDBName();
		foreach ( $patches as $patch ) {
			if ( $patch['version'] == $this->lastVersion || $patch['version'] == $previousVersion ) {
				// Generate scripts for patches introduced in previous versions.
				$generator->clean();
				$sqlFile =  $this->scriptFileHandler->composeFilenameForPatchScript( $previousVersion, $this->lastVersion, $patch['name'], $dbmsName );
				$allVersions = $this->dbStruct->getVersions();
				$patchIndex = array_search( $patch['version'], $allVersions );
				$prePatchVersion = $allVersions[$patchIndex - 1];
				// Patches are a change compared with the preceding version in which the patch is introduced.
				$this->dbStruct->generatePatch( $prePatchVersion, $patch['version'], $patch, $generator );
				$this->materializeSQLFile( $generator, $sqlFile );
			}
		}
	}

	/**
	 * Creates files in the scripts folder with the proper extensions.
	 *
	 * @param WW_DbScripts_Generators_Base $generator Generator class with logic and storage
	 * @param string $sqlFile Full path name of the SQL file.
	 */
	private function materializeSQLFile( $generator, $sqlFile )
	{
		$dbmsName = $generator->getDBName();
		if( $generator->materialize( $sqlFile ) ) {
			$this->logSQL( $dbmsName, $sqlFile, $this->verboseMode );
		}
	}

	/**
	 * Returns a script generator for a particular DBMS.
	 *
	 * @param string $dbms Name of the Database Management System.
	 * @return WW_DbScripts_Generators_Base|null
	 */
	private function createGenerator( $dbms )
	{
		require_once BASEDIR.'/server/dbscripts/generators/Factory.class.php';
		return WW_DbScripts_Generators_Factory::createGenerator( $dbms );
	}
}
