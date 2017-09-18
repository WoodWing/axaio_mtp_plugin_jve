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

ini_set('display_errors', 1);
if( php_sapi_name() !== 'cli' ) {
	exit( 'Please run this tool from the command line.' );
}

$pluginName = parseCliArguments();
try {
	$dbGen = new WW_BuildTools_DbGen( $pluginName );
	$dbGen->generateSqlScripts();
} catch( BizException $e ) {
	exit( $e->getMessage().' '.$e->getDetail().PHP_EOL );
} catch( Throwable $e ) {
	exit( $e->getMessage().PHP_EOL );
}

/**
 * Reads the command line arguments.
 *
 * @return string|null Name of the server plug-in provided at CLI. NULL when no 'plugin' param given.
 */
function parseCliArguments()
{
	$opts = new Zend\Console\Getopt( array(
		'plugin|p=s' => 'Optional. Name of the server plugin to generate the SQL scripts for. '.
			'When not provided, the SQL scripts classes for the core Enterprise Server are generated.',
		'help|h' => 'Show this information.'
	) );
	try {
		$arguments = $opts->getArguments();
	} catch( Exception $e ) {
		echo $opts->getUsageMessage();
		exit( 0 );
	}
	$plugin = isset( $arguments['plugin'] ) ? strval( $arguments['plugin'] ) : null; // optional
	if( isset( $arguments['help'] ) ) {
		echo $opts->getUsageMessage();
		exit( 0 );
	}
	return $plugin;
}

class WW_BuildTools_DbGen
{
	/** @var string[] List of DB driver names that are supported by ES. */
	private $supportedDBMS;

	/** @var string[] list of versions in 'major.minor' notation  */
	private $previousVersions;

	/** @var string the wanted version in 'major.minor' notation to upgrade to */
	private $lastVersion;

	/** @var WW_DbScripts_FileHandler */
	private $scriptFileHandler = null;

	/** @var WW_DbModel_Reader */
	private $reader;

	/** @var string[] Errors found when parsing the DB model definition. */
	private $errors;

	/**
	 * Constructor.
	 *
	 * @param string|null $pluginName
	 */
	public function __construct( $pluginName )
	{
		require_once BASEDIR.'/server/dbmodel/Factory.class.php';
		if( $pluginName ) {
			$definition = WW_DbModel_Factory::createModelForServerPlugin( $pluginName );
		} else {
			$definition = WW_DbModel_Factory::createModelForEnterpriseServer();
		}

		$this->supportedDBMS = DBDriverFactory::getSupportedDrivers();

		require_once BASEDIR.'/server/dbscripts/FileHandler.class.php';
		$this->scriptFileHandler = new WW_DbScripts_FileHandler( $pluginName );

		require_once BASEDIR.'/server/dbmodel/Reader.class.php';
		$this->reader = new WW_DbModel_Reader( $definition );
	}

	/**
	 * Generates SQL scripts for MySQL, MS SQL and Oracle
	 */
	public function generateSqlScripts()
	{
		// Initiate database structure and determine versions.
		$allVersions = $this->reader->getDbModelProvider()->getVersions();
		$this->lastVersion = end( $allVersions ); // Example: '10.2'

		// Which updates needs to be generated (empty if none).
		$this->previousVersions = $this->reader->getDbModelProvider()->getDbMigrationVersions();

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
		print 'Done!'.PHP_EOL;
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
		$this->generateInstallScript( $this->lastVersion, $generator );
		$generator->setVersion( $this->lastVersion );
		$dbmsName = $generator->getDBName();

		// Scripts used for new installations.
		$sqlFile = $this->scriptFileHandler->composeFilenameForIFullnstallScript( $this->lastVersion, $dbmsName );
		$this->materializeSQLFile( $generator, $sqlFile );

		$this->logErrors( $generator->getErrors() );
		$this->logErrors( $this->errors );

		// Scripts used for upgrades.
		foreach( $this->previousVersions as $previousVersion ) {
			if( version_compare( $previousVersion, $this->lastVersion, '<' ) ) {
				$generator = $this->createGenerator( $dbms );
				$this->generateUpgradeScript( $previousVersion, $this->lastVersion, $generator, $this->reader->getDbModelProvider()->getStruct() );
				if( isset( $this->addedCode[ $dbmsName ] ) ) {
					foreach( $this->addedCode[ $dbmsName ] as $k => $file ) {
						if( $k > $previousVersion && $k <= $this->lastVersion ) {
							$generator->addTxt( file_get_contents( $file )."\r\n" );
						}
					}
				}
				$generator->setVersion( $this->lastVersion );

				$sqlFile = $this->scriptFileHandler->composeFilenameForUpdateScript( $previousVersion, $this->lastVersion, $dbmsName );
				$this->materializeSQLFile( $generator, $sqlFile );

				$previousVersion = $this->lastVersion; // Upgrading is done from previous to intermediate versions.
				$this->logErrors( $generator->getErrors() );
				$this->logErrors( $this->errors );
			}
		}

		// Scripts to handle patches.
		$this->addPatchScripts( $dbms );

		$this->logErrors( $generator->getErrors() );
		$this->logErrors( $this->errors );
	}

	/**
	 * Adds patches to the SQL-scripts. Patches are changes made in the database model during a patch release.
	 * The patches must be applied when an upgrade is done from a previous, major, version but also when the current
	 * version is patched.
	 *
	 * @param string $dbms DBMS Database Management System
	 */
	private function addPatchScripts( $dbms )
	{
		// Patches are applied on the previous versions and the last version.
		$patchVersions = $this->previousVersions;
		$patchVersions[] = $this->lastVersion;
		$patches = $this->reader->getDbModelProvider()->getPatches();
		foreach( $patchVersions as $patchVersion ) {
			$this->generatePatchScripts( $patches, $dbms, $patchVersion );
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
		print $title.' - Generated '.$sqlFile.PHP_EOL;
	}

	/**
	 * Logs errors to web browser screen (HTML format).
	 *
	 * @param String[] $errors
	 */
	private function logErrors( array $errors )
	{
		if( $errors ) foreach( $errors as $error ) {
			echo '---------------------------------'.PHP_EOL;
			echo '>>> ERROR:'.$error.'<<<'.PHP_EOL;
			echo '---------------------------------'.PHP_EOL;
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
				$allVersions = $this->reader->getDbModelProvider()->getVersions();
				$patchIndex = array_search( $patch['version'], $allVersions );
				$prePatchVersion = $allVersions[$patchIndex - 1];
				// Patches are a change compared with the preceding version in which the patch is introduced.
				$this->generateUpgradeScript( $prePatchVersion, $patch['version'], $generator, $patch );
				$generator->insertPatchInfo( $patch['name'] );
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
			$this->logSQL( $dbmsName, $sqlFile );
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

	/**
	 * Generates sql-scripts for a clean install of the whole model. Scripts will be stored in generator.
	 *
	 * @param string $version			Version to generate
	 * @param WW_DbScripts_Generators_Base $generator			Generator class with logic and storage
	 */
	private function generateInstallScript( $version, $generator )
	{
		$this->errors = array();
		$generator->clean();
		foreach( $this->reader->listTables() as $table ) {
			$this->generateTable( $version, $table, $generator, true );
		}
	}

	/**
	 * Generates sql-scripts for given table. Scripts will be stored in generator.
	 * Normally default data is only added to a table when a database is newly generated.
	 * During an upgrade only the definitions are changed. In some special cases it is needed
	 * to add default data during the upgrade process. This is handled by the $insertrecords.
	 *
	 * @param string $version Version to generate.
	 * @param array $table Table info for the table to generate.
	 * @param WW_DbScripts_Generators_Base $generator	Generator class with logic and storage.
	 * @param boolean $insertrecords True if default data must be inserted, else false.
	 */
	private function generateTable( $version, $table, $generator, $insertrecords )
	{
		$versions = $this->reader->getDbModelProvider()->getVersions();
		if( !in_array( $table['v'], $versions ) ) {
			$this->errors[] = 'Table '.$table['name'].' refers to unknown version: '.$table['v'];
		}
		$isLastVersion = $version == end( $versions );

		if( version_compare( $table ['v'], $version, '<=' ) ) {
			$generator->tablePre( $table );
			$hasIdField = false;
			foreach( $table['fields'] as $fld ) {
				if( !in_array( $fld['v'], $versions ) ) {
					$this->errors[] = 'Field '.$table['name'].'::'.$fld['name'].' refers to unknown version: '.$fld['v'];
				}
				$toBeDropped = $this->reader->toBeDropped( $fld, '0' );
				$changeToField = $fld;
				$oldfield = $this->reader->getAlteredDBInfo( $version, $changeToField );
				if( $oldfield && !in_array( $oldfield['v'], $versions ) ) {
					$this->errors[] = 'Field '.$table['name'].'::'.$oldfield['name'].' refers to unknown version: '.$oldfield['v'];
				}
				// Regular case; field is added in some previous version and not dropped yet.
				if( version_compare( $fld ['v'], $version, '<=' ) && !$toBeDropped ) {
					$generator->field( $table, $fld, true );
				} elseif( $toBeDropped ) {
					$originalField = $this->reader->getDroppedInfo( $fld );
					// Field will be dropped in a future version but is still actual for the version created now.
					if( version_compare( $fld['v'], $version, '>' ) && version_compare( $originalField, $version, '<=' ) ) {
						$generator->field( $table, $originalField, true );
					}
				} // Field has been changed.
				elseif( $oldfield && !$toBeDropped ) {
					// Take the definition that is applicable for this version.
					// E.g. definition is created in version 5.0.
					// Definition is changed in version 8.0.
					// Run is done for version 6.0.
					// Take definition of version 5.0 as version 8.0 is not yet applicable.
					if( version_compare( $oldfield['v'], $version, '<=' ) ) {
						$generator->field( $table, $oldfield, true );
					}
				}
				if( !$toBeDropped ) {
					if( $fld['name'] == 'id' ) {
						$hasIdField = true;
					}
					// Note that there are many places in the core server assuming that when a table
					// has an "id" field, the auto increment option must be enabled for that field.
					// And, if there is no "id" field, there is no auto increment defined for the table.
					// Here we validate these assumptions.
					if( isset( $fld['autoincrement'] ) && $fld['autoincrement'] ) {
						if( $fld['name'] != 'id' ) {
							$this->errors[] = 'Table "'.$table['name'].'" has field "'.$fld['name'].'" '.
								'for which the "autoincrement" option is set. '.
								'This should be done for id fields only.';
						}
					} else {
						if( $fld['name'] == 'id' ) {
							$this->errors[] = 'Table "'.$table['name'].'" has field "id" '.
								'for which the "autoincrement" option is not set. '.
								'This should always be done for id fields.';
						}
					}
				}
			}
			$generator->tablePost( $table );

			// Validate tables in DB model against tables in getTablesWithoutAutoIncrement() function.
			if( $isLastVersion ) {
				$tablesWithoutIncr = $this->reader->getDbModelProvider()->getTablesWithoutAutoIncrement();
				$isListedInTablesWithoutIncr = in_array( $table['name'], $tablesWithoutIncr );
				if( $hasIdField && $isListedInTablesWithoutIncr ) {
					$this->errors[] = 'Table '.$table['name'].' has id field but table is '.
						'listed in the getTablesWithoutAutoIncrement() function.';
				} elseif( !$hasIdField && !$isListedInTablesWithoutIncr ) {
					$this->errors[] = 'Table '.$table['name'].' has no id field but table is '.
						'not listed in the getTablesWithoutAutoIncrement() function.';
				}
			}

			if( isset ( $table ['indexes'] ) )
				foreach( $table ['indexes'] as $ix ) {
					if( !in_array( $ix['v'], $versions ) ) {
						$this->errors[] = 'Index "'.$table['name'].'->'.$ix['name'].'" refers to unknown version: '.$ix['v'];
					}
					if( version_compare( $ix ['v'], $version, '<=' ) ) {
						$generator->index( $ix, $table );
					} else {   // It is possible that an index is created for an intermediate version and will changed later on.
						// During creation of the table the latest version of the index is used.
						$latestindex = $this->reader->getAlteredDBInfo( $version, $ix );
						if( $latestindex != null ) {
							if( version_compare( $latestindex['v'], $version, '<=' ) ) {
								$generator->index( $latestindex, $table );
							}
						}
					}
				}
			if( isset ( $table ['inserts'] ) && $insertrecords )
				foreach( $table ['inserts'] as $ins ) {
					$generator->insert( $ins, $table );
				}
			$generator->insertpost( $table );
		}
	}

	/**
	 * Generates upgrade scripts from one version (from) to another version (till).
	 *
	 * @param string $fromversion
	 * @param string $tillversion
	 * @param WW_DbScripts_Generators_Base $generator
	 * @param array $tableInfo
	 */
	private function generateUpgradeScript( $fromversion, $tillversion, $generator, $tableInfo )
	{
		$generator->clean();
		$this->errors = array();
		$generator->upgradePre();
		foreach( $tableInfo['tables'] as $table ) {
			if( $this->reader->isObsolete( $table, $fromversion ) ) {
				continue;
			}
			$tableToBeDropped = $this->reader->toBeDropped( $table, $fromversion );
			$originalTable = null;
			if( $tableToBeDropped ) {
				$originalTable = $this->reader->getDroppedInfo( $table );
			}
			if( version_compare( $table['v'], $fromversion, '<=' )
				&& !$tableToBeDropped ) { // Table already exists in the $fromversion and will not be dropped.
				$addedFields = false;   // Look if fields are added/changed or dropped.
				if( isset( $table['fields'] ) ) {
					foreach( $table['fields'] as $field ) {
						$changeToField = $field;
						$fromversionField = $this->reader->getAlteredDBInfo( $fromversion, $changeToField );
						$firstField = $this->reader->getFirstDefinitonAlteredField( $field );
						$fieldToBeDropped = $this->reader->toBeDropped( $field, $fromversion );
						$originalField = null;
						if( $fieldToBeDropped ) {
							$originalField = $this->reader->getDroppedInfo( $field );
						}
						if( !$fromversionField ) { // Skip field updates and dropping of fields (handled later).
							$fieldToAdd = false;
							$newField = null;
							$patchedFrom = $this->reader->patchedDBInfo( $field, $fromversion );
							if( !$fieldToBeDropped &&
								!$patchedFrom &&
								version_compare( $field['v'], $fromversion, '>' ) &&
								version_compare( $field['v'], $tillversion, '<=' ) ) {
								// Add the field that is added in a version between the from and the till version.
								$fieldToAdd = true;
								$newField = $field;
							} elseif( $fieldToBeDropped &&
								!$patchedFrom &&
								version_compare( $originalField['v'], $fromversion, '>' ) &&
								version_compare( $originalField['v'], $tillversion, '<=' ) &&
								version_compare( $field['v'], $tillversion, '>' ) ) {
								// Add the field also if the field in later conversion is dropped again, but not if it
								// was as already added via a patch.
								$fieldToAdd = true;
								$newField = $originalField;
							} elseif( $firstField &&
								!$fieldToBeDropped &&
								!$patchedFrom &&
								version_compare( $firstField['v'], $fromversion, '>' ) &&
								version_compare( $firstField['v'], $tillversion, '<=' ) ) {
								// Field is added after the from version and before the till version. Next it is changed.
								// Field must be added before it can be changed later on.
								$fieldToAdd = true;
								$newField = $firstField; // Take the initial definition. Beneath the changed definition
								// is picked up.
							}
							if( $fieldToAdd ) {
								if( $addedFields === false ) { // First time only.
									$generator->alterPre( $table );
								}
								$generator->addField( $table['name'], $newField );
								$addedFields = true;
							}
						}
					}
					if( $addedFields ) {
						$generator->alterPost();
					}

					// Check for changed (alter) fields.
					foreach( $table['fields'] as $field ) {
						$changeToField = $field;
						$fromversionField = $this->reader->getAlteredDBInfo( $fromversion, $changeToField );
						$fieldToBeDropped = $this->reader->toBeDropped( $field, $fromversion );
						$patchedFrom = $this->reader->patchedDBInfo( $field, $fromversion );
						if( $fromversionField && !$fieldToBeDropped ) {
							if( ( version_compare( $changeToField['v'], $fromversion, '>' ) ) &&
								( version_compare( $changeToField['v'], $tillversion, '<=' ) ) &&
								( version_compare( $patchedFrom, $field['v'], '<' ) ) ) {
								$generator->alterField( $table, $fromversionField, $changeToField );
							}
						}
					}
				}

				// Check for new indexes.
				if( isset( $table['indexes'] ) ) {
					foreach( $table['indexes'] as $indexInfo ) {
						$oldindex = $this->reader->getAlteredDBInfo( $fromversion, $indexInfo );
						if( $oldindex == null ) { //New index
							if( ( version_compare( $indexInfo['v'], $fromversion, '>' ) ) &&
								( version_compare( $indexInfo['v'], $tillversion, '<=' ) ) ) {
								$generator->index( $indexInfo, $table );
							}
						}
					}
				}

				// check for changed indexes
				if( !$generator->addToIdentScript( $fromversion, $table ) ) {
					// If a table is newly created because the identity is changed, the index must not be updated
					// because this results in an error.
					if( isset( $table['indexes'] ) ) {
						foreach( $table['indexes'] as $indexInfo ) {
							if( ( version_compare( $indexInfo['v'], $fromversion, '>' ) ) &&
								( version_compare( $indexInfo['v'], $tillversion, '<=' ) ) ) {
								$oldindex = $this->reader->getAlteredDBInfo( $fromversion, $indexInfo );
								if( $oldindex != null ) { //Changed index
									$generator->alterIndex( $table, $oldindex, $indexInfo );
								}
							}
						}
					}
				}

				// Check for fields to be dropped.
				if( isset( $table['fields'] ) ) {
					// Check for fields to be dropped.
					foreach( $table ['fields'] as $field ) {
						if( version_compare( $field ['v'], $fromversion, '>' ) &&
							version_compare( $field['v'], $tillversion, '<=' ) ) {
							$fieldToBeDropped = $this->reader->toBeDropped( $field, $fromversion );
							if( $fieldToBeDropped ) {
								$generator->dropField( $table, $field );
							}
						}
					}
				}
			} elseif( ( !$tableToBeDropped &&
				version_compare( $table['v'], $fromversion, '>' ) &&
				version_compare( $table['v'], $tillversion, '<=' ) ) ) { // Add new tables.
				$insertrecords = false;
				if( $table['name'] == 'smart_profiles' || $table['name'] == 'smart_profilefeatures' ) {
					// Profile 'Full Control' and its features must be inserted even when upgrading
					$insertrecords = true;
				}
				$this->generateTable( $tillversion, $table, $generator, $insertrecords );
			} elseif( ( $tableToBeDropped &&
				version_compare( $originalTable['v'], $fromversion, '>' ) &&
				version_compare( $originalTable['v'], $tillversion, '<=' ) &&
				version_compare( $table['v'], $tillversion, '>' ) ) ) {
				// Add new table even if table is dropped later on, else the drop will result in an error.
				$this->generateTable( $tillversion, $originalTable, $generator, false );
			} elseif( $tableToBeDropped &&
				version_compare( $table['v'], $fromversion, '>' ) &&
				version_compare( $table['v'], $tillversion, '<=' ) ) { // Drop tables
				$generator->addTxt( $generator->dropTable( $table['name'] ) );
			}
		}
		$generator->upgradePost();
	}
}
