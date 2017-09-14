<?php
/**
 * File handler that can read the SQL files located in the Enterprise/server/dbscripts folder,
 * and can compose the names for those files using a specific naming convention.
 *
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_DbScripts_FileHandler
{
	/**
	 * Provide the full file path of the Enterprise/server/dbscripts folder where our SQL scripts are located.
	 *
	 * @return string
	 */
	public function getScriptsFolder()
	{
		return BASEDIR.'/server/dbscripts/';
	}

	/**
	 * Provide the prefix that is used to recognize our SQL scripts to generate or install.
	 *
	 * @return string
	 */
	private function getScriptFilePrefix()
	{
		return 'scent';
	}

	/**
	 * Convert a version from 'major.minor' notation to an integer notation used for SQL script file names.
	 *
	 * @param string $version in 'major.minor' notation
	 * @return int version
	 */
	private function majorMinorVersionToInt( $version )
	{
		$parts = explode( '.', $version );
		if( count( $parts ) !== 2 ) {
			LogHandler::Log( __CLASS__, 'ERROR', "Bad DB model version format provided {$version}." );
			$version = 0;
		} else {
			if( $parts[1] == '99' ) { // for example convert 5.99 into 599
				$version = $parts[0].$parts[1];
			} else { // for example convert 8.0 into 800
				$version = $parts[0].$parts[1].'0';
			}
		}
		return intval( $version );
	}

	/**
	 * Convert a version from an integer notation used for SQL script file names to a 'major.minor' notation.
	 *
	 * @param integer $version
	 * @return string version in 'major.minor' notation
	 */
	private function intToMajorMinorVersion( $version )
	{
		$version = strval( $version );
		if( substr( $version, -1 ) == '0' ) {
			$version = substr( $version, 0, -1 ); // remove the patch
			$major = substr( $version, 0, -1 ); // take all except last digit
			$minor = substr( $version, -1 ); // take last digit
		} else if( substr( $version, -2 ) == '99' ) {
			$major = substr( $version, 0, -1 ); // take all except last digit
			$minor = substr( $version, -1 ); // take last digit
		} else {
			LogHandler::Log( __CLASS__, 'ERROR', "Bad DB model version format provided {$version}." );
			$major = '0';
			$minor = '0';
		}
		return "{$major}.{$minor}";
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
		$dbVersion = $this->majorMinorVersionToInt( $dbVersion );
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}{$dbVersion}.{$dbmsName}.sql";
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
		$versionFrom = $this->majorMinorVersionToInt( $versionFrom );
		$versionTo = $this->majorMinorVersionToInt( $versionTo );
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}{$versionFrom}_{$versionTo}.{$dbmsName}.sql";
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
		$versionFrom = $this->majorMinorVersionToInt( $versionFrom );
		$versionTo = $this->majorMinorVersionToInt( $versionTo );
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}{$versionFrom}_{$versionTo}_{$mode}.{$dbmsName}.sql";
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
		$versionFrom = $this->majorMinorVersionToInt( $versionFrom );
		$versionTo = $this->majorMinorVersionToInt( $versionTo );
		$dir = $this->getScriptsFolder();
		$prefix = $this->getScriptFilePrefix();
		return "{$dir}{$prefix}{$versionFrom}_{$versionTo}_patch_{$patchName}_{$dbmsName}.sql";
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
			if( substr( $file, -4 ) != ".txt" ) {
				continue;
			}
			$r = array();
			// TODO: suppport major.minor
			if( preg_match( '/'.$prefix.'([0-9]*)\.([a-z]*).txt/i', $file, $r ) > 0 ) {
				$sqlFiles[ $r[2] ][ $r[1] ] = $dir.$file;
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
		$sqlFiles = array();

		foreach( $files as $file ) {
			if( substr( $file, 0, 1 ) == '.' ) { // hidden files
				continue;
			}
			if( substr( $file, -4 ) != '.sql' ) { //extension must be 'sql'
				continue;
			}
			if( substr( $file, 0, strlen( $prefix ) ) != $prefix ) { // prefix must be 'scent'
				continue;
			}
			$parts = explode( '.', str_replace( '_', '.', substr( $file, strlen( $prefix ) ) )  );
			array_pop( $parts ); // remove file extension
			if( end( $parts ) != $dbmsName ) { // only include files for requested DBMS
				continue;
			}
			array_pop( $parts ); // remove DBMS name

			$sqlFile = new WW_DbScripts_FileDescriptor( $dir.$file );
			if( count($parts) >= 2 && ctype_digit( $parts[0] ) && ctype_digit( $parts[1] ) ) {
				$versionFrom = $this->intToMajorMinorVersion( $parts[0] );
				$versionTo = $this->intToMajorMinorVersion( $parts[1] );
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
			} elseif( count($parts) > 0 && ctype_digit( $parts[0] )) {
				$version = $this->intToMajorMinorVersion( $parts[0] );
				$sqlFile->setFullInstallType( $version );
			}
			$sqlFiles[] = $sqlFile;
		}
		return $sqlFiles;
	}
}