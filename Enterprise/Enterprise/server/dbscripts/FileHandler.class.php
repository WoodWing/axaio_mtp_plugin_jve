<?php
/**
 * File handler that can read the SQL files located in the dbscripts folder,
 * and can compose the names for those files using a specific naming convention.
 *
 * The dbscripts folder of the core server is located at Enterprise/server/dbscripts.
 * Aside to that, any server plug-in could also provide a dbscripts folder.
 *
 * @package    Enterprise
 * @subpackage DbScripts
 * @since      v10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_DbScripts_FileHandler
{
	/** @var string The full file path of the dbscripts folder where the SQL scripts are located */
	private $scriptsFolder;

	/**
	 * Constructor.
	 *
	 * @param string|null $pluginName Internal name of the server plug-in. NULL when scripts should be handled for the core server.
	 * @throws BizException When plugin is not installed nor activated or when the dbscripts folder does not exists.
	 */
	public function __construct( $pluginName )
	{
		$this->scriptsFolder = null;
		if( $pluginName ) {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$pluginInfo = BizServerPlugin::getInstalledPluginInfo( $pluginName );
			if( !$pluginInfo ) {
				$message = "Could not find an installed server plug-in named {$pluginName}. Please install and try again.";
				throw new BizException( '', 'Server', '', $message );
			}
			if( !$pluginInfo->IsActive ) {
				$message = "The server plug-in named {$pluginName} is not activated. Please activate and try again.";
				throw new BizException( '', 'Server', '', $message );
			}
			$pluginFolder = BizServerPlugin::getPluginFolder( $pluginInfo );
			$this->scriptsFolder = $pluginFolder.'dbscripts/';
		} else {
			$this->scriptsFolder = BASEDIR.'/server/dbscripts/';
		}
		if( !file_exists( $this->scriptsFolder ) ) {
			$message = "The dbscripts folder {$this->scriptsFolder} does not exists. Please create and try again.";
			throw new BizException( '', 'Server', '', $message );
		}
	}

	/**
	 * Provide the full file path of the dbscripts folder where the SQL scripts are located.
	 *
	 * @return string
	 */
	public function getScriptsFolder()
	{
		return $this->scriptsFolder;
	}

	/**
	 * Provide the prefix that is used to recognize the SQL scripts to generate or install.
	 *
	 * @return string
	 */
	private function getScriptFilePrefix()
	{
		return 'ent';
	}

	/**
	 * Compose a full path for an SQL script that is used to full/clean install a DB model.
	 *
	 * @param string $dbVersion DB model version in 'major.minor' notation
	 * @param string $dbmsName
	 * @return string
	 */
	public function composeFilenameForIFullnstallScript( $dbVersion, $dbmsName )
	{
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}_{$dbVersion}_{$dbmsName}.sql";
	}

	/**
	 * Compose a full path for an SQL script that is used to upgrade an old DB model to a new version.
	 *
	 * @param string $versionFrom DB model version to upgrade from in 'major.minor' notation
	 * @param $versionTo DB model version to upgrade to in 'major.minor' notation
	 * @param string $dbmsName
	 * @return string
	 */
	public function composeFilenameForUpdateScript( $versionFrom, $versionTo, $dbmsName )
	{
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}_{$versionFrom}_{$versionTo}_{$dbmsName}.sql";
	}

	/**
	 * Compose a full path for an SQL script that is used to run before/after upgrading a DB model.
	 *
	 * @param string $versionFrom DB model version to upgrade from in 'major.minor' notation
	 * @param $versionTo DB model version to upgrade to in 'major.minor' notation
	 * @param string $dbmsName
	 * @param string $mode 'pre' or 'post'
	 * @return string
	 */
	public function composeFilenameForPrePostUpdateScript( $versionFrom, $versionTo, $dbmsName, $mode )
	{
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}_{$versionFrom}_{$versionTo}_{$mode}_{$dbmsName}.sql";
	}

	/**
	 * Compose a full path for an SQL script that is used to patch a DB model.
	 *
	 * @param string $versionFrom DB model version to upgrade from in 'major.minor' notation
	 * @param $versionTo DB model version to upgrade to in 'major.minor' notation
	 * @param string $dbmsName
	 * @param string $patchName
	 * @return string
	 */
	public function composeFilenameForPatchScript( $versionFrom, $versionTo, $patchName, $dbmsName )
	{
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}_{$versionFrom}_{$versionTo}_patch_{$patchName}_{$dbmsName}.sql";
	}

	/**
	 * Get all SQL files, if any, that contain manually coded instructions to install a DB model.
	 *
	 * @return string[] List of file paths indexed by DB model version and DBMS name.
	 */
	public function getManuallyCodedSqlFiles()
	{
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		$files = scandir( $dir );
		$sqlFiles = array();
		foreach( $files as $file ) {
			if( substr( $file, 0, 1 ) == '.' ) { // exclude hidden files
				continue;
			}
			if( substr( $file, -4 ) != ".txt" ) { // exclude files that don't have the extension
				continue;
			}
			$r = array();
			if( preg_match( '/'.$prefix.'_([0-9]+\.[0-9]+)_([a-z]+).txt/i', $file, $r ) > 0 ) {
				$sqlFiles[ $r[2] ][ $r[1] ] = $dir.$file;
				// Example: when $file == 'ent_10.2_mysql.txt' then $r[1] == '10.2' and $r[2] == 'mysql'.
			}
		}
		return $sqlFiles;
	}

	/**
	 * Get all SQL script files that are shipped in the dbscripts folder for a given DBMS name.
	 *
	 * @param string $dbmsName
	 * @return WW_DbScripts_FileDescriptor[]
	 */
	public function getSqlFiles( $dbmsName )
	{
		require_once BASEDIR.'/server/dbscripts/FileDescriptor.class.php';

		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		$files = scandir( $dir );
		$fileExt = '.sql';
		$sqlFiles = array();

		foreach( $files as $file ) {
			if( substr( $file, 0, 1 ) == '.' ) { // exclude hidden files
				continue;
			}
			if( substr( $file, -strlen( $fileExt ) ) != $fileExt ) { // exclude files that don't have the extension
				continue;
			}
			if( substr( $file, 0, strlen( $prefix ) ) != $prefix ) { // exclude files that don't have the prefix
				continue;
			}
			$parts = explode( '_', substr( $file, 0, -strlen( $fileExt ) ) );
			array_shift( $parts ); // remove the prefix
			if( end( $parts ) != $dbmsName ) { // only include files for the requested DBMS
				continue;
			}
			array_pop( $parts ); // remove the DBMS name

			$sqlFile = new WW_DbScripts_FileDescriptor( $dir.$file );
			if( count($parts) >= 2 && $this->isMajorMinorVersion( $parts[0] ) && $this->isMajorMinorVersion( $parts[1] ) ) {
				$versionFrom = $parts[0];
				$versionTo = $parts[1];
				if( count($parts) >= 4 && $parts[2] == 'patch') {
					$sqlFile->setPatchType( $versionFrom, $versionTo, $parts[3] );
				} else {
					if( count($parts) >= 3 ) {
						if( $parts[2] == 'pre' ) {
							$sqlFile->setPreUpgradeType( $versionFrom, $versionTo );
						} elseif( $parts[2] == 'post' ) {
							$sqlFile->setPostUpgradeType( $versionFrom, $versionTo );
						}
					} else {
						$sqlFile->setUpgradeType( $versionFrom, $versionTo );
					}
				}
			} elseif( count($parts) > 0 && $this->isMajorMinorVersion( $parts[0] )) {
				$sqlFile->setFullInstallType( $parts[0] );
			}
			$sqlFiles[] = $sqlFile;
		}
		return $sqlFiles;
	}

	/**
	 * Validate the format of a given version in 'major.minor' notation.
	 *
	 * @param string $version version in 'major.minor'
	 * @return bool Whether or not the format is correct.
	 */
	private function isMajorMinorVersion( $version )
	{
		$parts = explode( '.', $version );
		return count( $parts ) == 2 && ctype_digit( $parts[0] ) && ctype_digit( $parts[1] );
	}
}