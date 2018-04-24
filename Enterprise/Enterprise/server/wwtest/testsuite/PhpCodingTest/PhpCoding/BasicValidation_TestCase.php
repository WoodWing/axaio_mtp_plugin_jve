<?php
/**
 * @package   Enterprise
 * @subpackage   TestSuite
 * @since      v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Checks all PHP modules for the following potential problems due to PHP coding:
 * - Whether the include paths of all PHP modules can be resolved that belong to Enterprise Server.
 * - Whether ionCube Loader + Zend Optimizer could crash due to try-catch.
 * - Whether the class constructors are not using the old style that is no longer compatible with PHP7.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/utils/FolderInterface.intf.php';
require_once BASEDIR.'/server/utils/FolderUtils.class.php';

class WW_TestSuite_PhpCodingTest_PhpCoding_BasicValidation_TestCase extends TestCase implements FolderIterInterface
{
	private $globIncPathsMixed;
	private $globIncPathsLower;
	private $phpFilePaths;
	private $fileCollectMode;

	public function getDisplayName()
	{
		return 'Basic PHP validation';
	}

	public function getTestGoals()
	{
		return 'Avoid on-site problems due to commonly made mistakes at PHP coding.';
	}

	public function getTestMethods()
	{
		return 'Parses PHP sources using the internal tokenizer. Checks for use of deprecated PHP functions, case sensitive include paths for Linux compatibility, etc.';
	}

	public function getPrio()
	{
		return 10;
	}

	private $obsoletedPhpFunctions = array(

		// Deprecated since PHP 5.3 (or before):				
		'define_syslog_variables',
		'register_globals',
		'register_long_arrays',
		'safe_mode',
		'magic_quotes_gpc',
		'magic_quotes_runtime',
		'magic_quotes_sybase',
		'call_user_method',
		'call_user_method_array',
		'define_syslog_variables',
		'dl',
		'ereg',
		'ereg_replace',
		'eregi',
		'eregi_replace',
		'set_magic_quotes_runtime',
		'session_register',
		'session_unregister',
		'session_is_registered',
		'set_socket_blocking',
		'split',
		'spliti',
		'sql_regcase',
		'mysql_db_query',
		'mysql_escape_string',

		// Deprecated since PHP 5.4:
		'mcrypt_generic_end',
		'mysql_list_dbs',

		// Deprecated since PHP 5.5:
		'mysql_affected_rows',
		'mysql_client_encoding',
		'mysql_close',
		'mysql_connect',
		'mysql_create_db',
		'mysql_data_seek',
		'mysql_db_name',
		'mysql_db_query',
		'mysql_drop_db',
		'mysql_errno',
		'mysql_error',
		'mysql_escape_string',
		'mysql_fetch_array',
		'mysql_fetch_assoc',
		'mysql_fetch_field',
		'mysql_fetch_lengths',
		'mysql_fetch_object',
		'mysql_fetch_row',
		'mysql_field_flags',
		'mysql_field_len',
		'mysql_field_name',
		'mysql_field_seek',
		'mysql_field_table',
		'mysql_field_type',
		'mysql_free_result',
		'mysql_get_client_info',
		'mysql_get_host_info',
		'mysql_get_proto_info',
		'mysql_get_server_info',
		'mysql_info',
		'mysql_insert_id',
		'mysql_list_dbs',
		'mysql_list_fields',
		'mysql_list_processes',
		'mysql_list_tables',
		'mysql_num_fields',
		'mysql_num_rows',
		'mysql_pconnect',
		'mysql_ping',
		'mysql_query',
		'mysql_real_escape_string',
		'mysql_result',
		'mysql_select_db',
		'mysql_set_charset',
		'mysql_stat',
		'mysql_tablename',
		'mysql_thread_id',
		'mysql_unbuffered_query',
		'mcrypt_cbc',
		'mcrypt_cfb',
		'mcrypt_ecb',
		'mcrypt_ofb',
	);

	public function __construct()
	{
		$this->obsoletedPhpFunctions = array_flip( $this->obsoletedPhpFunctions );
	}

	/**
	 * Performs the test as written in module header.
	 */
	final public function runTest()
	{
		$this->filesChecked = 0;
		$this->globIncPaths = explode( PATH_SEPARATOR, ini_get( 'include_path' ) );

		// Collect all PHP file paths for lower/upper case validation later
		$this->phpFilePathsMixed = array();
		$this->phpFilePathsLower = array();
		$this->fileCollectMode = true;
		$exclFolders = array(); // do NOT exclude anything!
		FolderUtils::scanDirForFiles( $this, BASEDIR, array( 'php' ), $exclFolders );
		$this->phpFilePathsLower = array_change_key_case( $this->phpFilePathsMixed, CASE_LOWER );
		$this->fileCollectMode = false;

		// Test: Enterprise Server
		$exclFolders = FolderUtils::getLibraryFolders();
		$exclFolders[] = BASEDIR.'/mytests'; // ignore local tests
		$exclFolders[] = BASEDIR.'/config/plugins'; // ignore custom plugins
		$exclFolders[] = BASEDIR.'/server/buildtools'; // not shipped, so not tested
		$exclFolders[] = BASEDIR.'/config/plugin-templates'; // contains templates to be filled in runtime
		FolderUtils::scanDirForFiles( $this, BASEDIR, array( 'php' ), $exclFolders );

		// Test: Demo/example server plug-ins
		// TODO: Uncomment and solve report problems
		//$exclFolders = array();
		//FolderUtils::scanDirForFiles( $this, BASEDIR.'/../plugins', array('php'), $exclFolders );

		//$this->setResult( 'INFO', $this->filesChecked.' PHP files checked.' );
	}

	/**
	 * Transform file path into readable HTML fragment with file path info to be used as prefix
	 * in setResult() messages.
	 *
	 * @param string $filePath Full file path of a PHP module being parsed.
	 * @return string The HTML fragment, as explained above.
	 */
	private function getFileInfoStr( $filePath )
	{
		$pathInfo = pathinfo( $filePath );
		$pathInfo['dirname'] = str_replace( BASEDIR, '', $pathInfo['dirname'] );
		return '<i>'.$pathInfo['dirname'].DIRECTORY_SEPARATOR.'<b>'.$pathInfo['basename'].':</b></i><br/>';
	}

	/**
	 * Check the PHP source file (called by parent class).
	 *
	 * @param string $filePath Full file path of PHP file.
	 * @param integer $level Current ply in folder structure of recursion search.
	 */
	public function iterFile( $filePath, $level )
	{
		if( $this->fileCollectMode ) {
			// Make first character to uppercase, else later comparison with path returned by realpath() will not match,
			// realpath return uppercase drive letter
			$this->phpFilePathsMixed[ ucfirst( $filePath ) ] = true;
		} else {
			$this->checkFile( $filePath );
			$this->filesChecked += 1;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function skipFile( $filePath, $level )
	{
	}

	/**
	 * @inheritdoc
	 */
	public function iterFolder( $folderPath, $level )
	{
	}

	/**
	 * @inheritdoc
	 */
	public function skipFolder( $folderPath, $level )
	{
	}

	/**
	 * The actual PHP source file validation. See module header for functionality.
	 * In case the file is encrypted no validation is done.
	 *
	 * @param string $filePath Full file path of PHP file.
	 */
	private function checkFile( $filePath )
	{
		// Parse given source
		$sourceCode = file_get_contents( $filePath );

		$ioncubeHeader = substr( $sourceCode, 0, 1000 );
		// First 1000 characters will contain information to check if file is encrypted
		if( strpos( $ioncubeHeader, "extension_loaded('ionCube Loader')" ) !== false ) {
			$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).
				' This file is ionCube encoded and therefor it can not be checked.' );
			return;
		}

		$tokens = token_get_all( $sourceCode );
		$this->checkInclude( $filePath, $sourceCode, $tokens );
		$this->checkTryCatchExceptionError( $filePath, $sourceCode, $tokens );
		$this->checkConstructors( $filePath, $sourceCode, $tokens );
	}

	/**
	 * Checks if the include paths of all PHP modules can be resolved that belong to Enterprise Server.
	 *
	 * The few paths that are built dynamically (based on variables) are skipped.
	 * 3rd party includes are skipped too since they are considered to be fine.
	 * The path checking is case sensitive because Enterprise needs to run at Linux too!
	 * When path can not be resolved, an error is displayed.
	 * Includes that are relative to the source path result into warnings since
	 * it easily breaks when moving around some source files (e.g. refactoring).
	 * Includes that depend on the include_path setting result into warnings too,
	 * because relying on global settings is something we want to avoid.
	 *
	 * @param string $filePath Full file path of PHP file.
	 * @param string $sourceCode Content of PHP file.
	 * @param array $tokens Parsed tokens of the PHP file.
	 */
	private function checkInclude( $filePath, &$sourceCode, &$tokens )
	{
		// reset include mode
		$incMode = false;
		$incPath = '';
		$pathPart = '';

		foreach( $tokens as $token ) {

			if( is_string( $token ) ) {
				if( $incMode ) {
					if( $token == ';' || $token == ')' ) {
						if( !empty( $incPath ) ) { // empty happens for variable path names, which we don't check
							$reliesOnGlobIncPath = false;
							$reliesOnSourceBase = false;
							$incRealPath = realpath( $incPath ); // does also resolve upper/lower case (for PHP < v5.3 only)
							$pathInfo = pathinfo( $filePath ); // this source file as base
							if( DIRECTORY_SEPARATOR == '\\' ) { // for Windows make uniform file path (to compare later)
								$incRealPath = str_replace( '\\', '/', $incRealPath );
								$pathInfo['dirname'] = str_replace( '\\', '/', $pathInfo['dirname'] );
							}
							if( !is_readable( $incRealPath ) ) { // try relative to base path of current source file
								$incRealPath = $pathInfo['dirname'].'/'.$incPath;
								if( is_readable( $incRealPath ) ) {
									$pathPart = str_replace( '../', '', $incPath );
									if( strpos( $pathPart, 'config/config.php' ) === false && // we only allow relative config.php includes
										$pathInfo['basename'] != 'config.php'
									) { // we also allow all relative includes made from the config.php file
										$reliesOnSourceBase = true;
									}
									// NOTE: Relative includes must go all the way up to the BASEDIR first, then down again. 
									//       No matter if included from the config sub-folder! This is a house rule.
								}
							}
							if( !is_readable( $incRealPath ) ) { // try relative to global include path setting
								foreach( $this->globIncPaths as $globIncPath ) {
									$incRealPath = $globIncPath.'/'.$incPath;
									if( is_readable( $incRealPath ) ) {
										$pathPart = str_replace( '../', '', $incPath );
										if( strpos( $pathPart, 'config/config.php' ) === false && // we only allow relative config.php includes
											$pathInfo['basename'] != 'config.php'
										) { // we also allow all relative includes made from the config.php file
											$reliesOnGlobIncPath = true;
											break; // found, stop search
										}
									}
								}
							}
							if( is_readable( $incRealPath ) ) {
								do {
									if( preg_match( '/\/config\/plugins\//', $incPath )
										&& strpos( $filePath, '/server/wwtest/testsuite/BuildTest/' ) !== false
									) {
										// We can skip references from the BuildTest to files of custom plugins
										// They don't necessarily have to be installed and the BuildTest will check for them.
										// (Note that Health Check test cases are shipped within the plugins, 
										// while BuildTest test cases are shipped with the core server.)
										break; // we can skip further testing
									}
									// This works for PHP 5.3+ since realpath() does no longer resolve upper/lower case file names
									if( isset( $this->phpFilePathsLower[ strtolower( $incRealPath ) ] ) && !isset( $this->phpFilePathsMixed[ ucfirst( $incRealPath ) ] ) ) {
										$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).
											'Upper/lower case mismatch for [<b>'.$incPath.'</b>]' );
										break; // we can skip further testing
									}
								} while( false );
							} else {
								do {
									if( preg_match( '/\/config\/plugins\//', $incPath )
										&& strpos( $filePath, '/server/wwtest/testsuite/BuildTest/' ) !== false
									) {
										// We can skip references from the BuildTest to files of custom plugins
										// They don't necessarily have to be installed and the BuildTest will check for them.
										// (Note that Health Check test cases are shipped within the plugins, 
										// while BuildTest test cases are shipped with the core server.)
										break; // we can skip further testing
									}

									if( strpos( $filePath, '/config/plugins/' ) === false ||
										strpos( $incPath, '../../../config/config.php' ) === false
									) { // suppress custom plugins doing the logical link trick
										$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).
											'Could not find [<b>'.$incPath.'</b>]' );
									}
								} while( false );
							}
							if( $reliesOnGlobIncPath ) {
								if( strpos( $incPath, 'Zend/' ) === false ) { // ignore Zend includes
									$this->setResult( 'WARN', $this->getFileInfoStr( $filePath ).
										'Include relies on include_path setting: [<b>'.$incPath.'</b>]' );
								}
							}
							if( $reliesOnSourceBase ) {
								$this->setResult( 'WARN', $this->getFileInfoStr( $filePath ).
									'Include ['.$pathPart.'] relies on (relative to) the source location: [<b>'.$incPath.'</b>]' );
							}
						}
						// reset include mode
						$incMode = false;
						$incPath = '';
					} else {
						//echo "($token)";
					}
				}
			} else {
				// token array
				list( $id, $text ) = $token;

				/* // ONLY FOR DEBUGGING THIS CLASS:
				if( $incMode ) {
					$this->setResult( 'INFO', '['.token_name($id).'='.$text.']' );
				}*/

				switch( $id ) {
					case T_INCLUDE:
					case T_INCLUDE_ONCE:
					case T_REQUIRE:
					case T_REQUIRE_ONCE:
						//echo "[$text]";
						$incMode = true;
						break;

					case T_VARIABLE: // we can't check dynamically built include paths, so skip this one
						// reset include mode
						$incMode = false;
						$incPath = '';
						break;

					case T_STRING:
						if( $incMode ) {
							if( $text == 'BASEDIR' ) {
								$incPath .= '../../..';
							}
						}
						if( array_key_exists( $text, $this->obsoletedPhpFunctions ) ) {
							if( !$this->isIoncubeWizardFile( $filePath )  ) {
								$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).
									'Obsoleted function: [<b>'.$text.'</b>]' );
							}
						}
						break;

					case T_CONSTANT_ENCAPSED_STRING:
						if( $incMode ) {
							if( strlen( $text ) > 1 ) {
								$incPath .= substr( $text, 1, strlen( $text ) - 2 ); // get path, all between quotes
							}
						}
						if( $text == "'PHP_SELF'" ) {
							if( !$this->isIoncubeWizardFile( $filePath ) ) {
								$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).
									'Not allowed: [<b>'.$text.'</b>] Use INETROOT to resolve the relative path.' );
							}
						}
						break;

					default:
						//if( $incMode ) {
						//	echo '{'.$text.'}';
						//}
						break;
				}
			}
		}
	}

	/**
	 * The php files used by the ionCube wizard as loaded before any other files. These files can be seen as third-party
	 * files and are excluded from the validation.
	 *
	 * @param $file
	 * @return bool
	 */
	private function isIoncubeWizardFile( $file )
	{
		$result = false;
		$ignoreFiles = array(
			// This file uses the dl() function, but it is shipped with ionCube Loader:
			BASEDIR.'/server/wwtest/loader-wizard.php' => true,
			// This file is derived from loader-wizard.php and so has same problem:
			BASEDIR.'/server/wwtest/wwioncubetest.php' => true,
		);

		if( array_key_exists( $file, $ignoreFiles ) ) {
			$result = true;
		}

		return $result;
	}

	/**
	 * Detects strange ionCube Loader + Zend Optimizer crash (BZ#17063).
	 *
	 * @param string $filePath
	 * @param string $sourceCode
	 * @param array $tokens
	 */
	private function checkTryCatchExceptionError( $filePath, &$sourceCode, &$tokens )
	{
		// BZ#17063 try to detect strange ionCube + Zend Optimizer crash
		// find function ... ( ... ) { try { ... } catch ( ... ) { ... throw ... }
		// only in encoded directories
		$encodedDirs = array(
			BASEDIR.'/server/admin/license',
			BASEDIR.'/server/utils/license',
			BASEDIR.'/server/dbclasses',
			BASEDIR.'/server/dbdrivers',
			BASEDIR.'/server/services',
			BASEDIR.'/server/appservices',
			BASEDIR.'/server/wwtest/ngrams',
		);
		$isEncodedDir = false;
		foreach( $encodedDirs as $encodedDir ) {
			if( strpos( $filePath, $encodedDir ) === 0 ) {
				$isEncodedDir = true;
				break;
			}
		}
		// TODO Some encoded files are not part of an encoded directory. These files are now skipped.
		if( $isEncodedDir ) {
			$matches = array();
			$pregRes = preg_match( '/function\\s+\\w+\\s*\\([^)]*\\)\\s*\\{\\s*try\\s\\{.*?\\}\s*catch\s*\\(.*?\\)\s*\\{.*?throw.*?\\}\\}/s', $sourceCode, $matches );
			if( $pregRes !== false && $pregRes > 0 ) {
				$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).
					'Found possible ionCube and Zend Optimizer combination problem.' );
			}
		}
	}

	/**
	 * Checks if the class constructors are not using the old style that is no longer compatible with PHP7.
	 *
	 * Enterprise 10.2 support PHP 7.0 and therefore constructors with the name of the class is obsoleted.
	 *
	 * @since 10.2.0
	 * @param string $filePath Full file path of PHP file.
	 * @param string $sourceCode Content of PHP file.
	 * @param array $tokens Parsed tokens of the PHP file.
	 */
	private function checkConstructors( $filePath, &$sourceCode, &$tokens )
	{
		$catchClassName = false;
		$className = '';
		$catchFuncName = false;
		$catchBracketCount = false;
		$bracketCount = 0;
		foreach( $tokens as $token ) {
			if( is_string( $token ) ) {
				if( $catchBracketCount ) {
					if( $token == '{' ) {
						$bracketCount++;
					} elseif( $token == '}' ) {
						$bracketCount--;
					}
				}
				if( $bracketCount <= 0 ) {
					$catchBracketCount = false;
					$bracketCount = 0;
				}
			} else {
				list( $id, $text ) = $token;
				switch( $id ) {
					case T_STRING:
						if( $catchClassName ) {
							$catchClassName = false;
							$className = $text;
							$catchBracketCount = true;
							$bracketCount = 0;
						}
						if( $catchFuncName ) {
							$funcName = $text;
							if( $funcName == $className ) {
								$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).
									' Contructor of class <b>\''.$className.'\'</b> uses the old style. '.
									'This is no longer compatible with PHP7. Please use \'__construct\' instead.' );
							}
							$catchClassName = false;
							$className = '';
							$catchFuncName = false;
							$catchBracketCount = false;
							$bracketCount = 0;
						}
						break;
					case T_FUNCTION:
						if( $catchBracketCount == 1 ) {
							$catchFuncName = true;
						}
						break;
					case T_CLASS:
						$catchClassName = true;
						$className = '';
						break;
				}
			}
		}
	}
}