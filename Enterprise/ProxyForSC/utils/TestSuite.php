<?php
/**
 * TestSuite utils class.
 *
 * @package    ProxyForSC
 * @subpackage TestSuite
 * @since      v1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
 
class WW_Utils_TestSuite
{
	// Validation flags for validateDefines() function:
	const VALIDATE_DEFINE_MANDATORY = 1;
	const VALIDATE_DEFINE_NOT_EMPTY = 2;
	const VALIDATE_DEFINE_ALL       = 255;

	// Validation flags for validateFilePath() function:
	const VALIDATE_PATH_NOT_EMPTY   = 1;
	const VALIDATE_PATH_FILE_EXISTS = 2;
	const VALIDATE_PATH_NO_SLASH    = 4; // no slash at end of path
	const VALIDATE_PATH_NO_SPACE    = 5; // no space at end of path
	const VALIDATE_PATH_ALL         = 255;

    // Validation flags for validateDirAccess() function:
    const VALIDATE_DIR_ACCESS       = 1;
    const VALIDATE_DIR_ACCESS_ALL   = 255;
    
    // Validation flags for validateUrl() function:
    const VALIDATE_URL_RESPONSIVE   = 1;
    const VALIDATE_URL_ALL          = 255;

	/**
	 * Determines the full file path of the php.ini file as used by current PHP session.
	 * Typically useful to locate the correct ini file in case settings needs to change.
	 *
	 * @return string File path
	 */
	public function getPhpIniPath()
	{
        ob_start();
        phpinfo(INFO_GENERAL);
        $phpinfo = ob_get_contents();
        ob_end_clean();
		$found = array();
        return preg_match('/\(php.ini\).*<\/td><td[^>]*>([^<]+)/',$phpinfo,$found) ? $found[1] : '';
	}

	/**
	 * Validates defines to be made at the PHP configuration files.
	 *
	 * @param TestCase $testCase The test module calling this function.
	 * @param array $defineNameTypes Array of name and type of defines to validate.
	 * @param string $configFile Name of the PHP configuration file. Used for help display only.
	 * @param string $errorLevel Severity when validation fails. Typically 'ERROR' or 'WARN' can be used.
	 * @param integer $validateOpts Flags indicating which validation rules to apply.
	 * @param string $help The custom help string that can be pass by caller
	 * @return boolean Whether or not all ok. False when one validation failed. No matter failure, it tests -all- defines.
	 */
	public function validateDefines(
			TestCase $testCase, array $defineNameTypes, $configFile = 'config.php',
			$errorLevel = 'ERROR', $validateOpts = self::VALIDATE_DEFINE_ALL, $help=null )
	{
		$valid = true;
		foreach( $defineNameTypes as $defineName => $defineType ) {
			// When defineName is type of integer, means is the old array define name without type
			// For backward compatibility, change to the defineName => defineType structure, where defineType default to string type
			if( is_int($defineName) ) {
				$defineName = $defineType;
				$defineType = gettype( constant($defineName) );
				if( $defineType == 'string' ) {
					if( ( $unserializeData = @unserialize( trim( constant($defineName) ) ) ) ) {
						$defineType = gettype( $unserializeData );
					}
				}
			}
			if( ($validateOpts & self::VALIDATE_DEFINE_MANDATORY) == self::VALIDATE_DEFINE_MANDATORY ) {
				if( !defined($defineName) ) {
					$pleaseCheck = is_null($help) ? 'Please add it to the '.$configFile.' file. Check out the Admin Guide how this needs to be done.' : $help;
					$testCase->setResult( $errorLevel, "The $defineName option is not defined.", $pleaseCheck );
					$valid = false;
					continue;
				}
				$pleaseCheck = is_null( $help ) ? 'Please fill in a value at the '.$configFile.
								' file. Check out the Admin Guide how this needs to be done.' : $help;
				switch( $defineType ) {
					case 'string':
						if( ($validateOpts & self::VALIDATE_DEFINE_NOT_EMPTY) == self::VALIDATE_DEFINE_NOT_EMPTY ) {
							$val = trim(constant($defineName));
							if( strlen($val) == 0 ) {
								$testCase->setResult( $errorLevel,
										'The '.$defineName.' option should have a text value set (between quotes).', $pleaseCheck );
								$valid = false;
							}
						}
						break;
					case 'boolean':
						if( !is_bool( constant($defineName) ) ) {
							$testCase->setResult( $errorLevel,
										'The '.$defineName.' option should either be set to true or false and no quotes should be used.',
											$pleaseCheck );
							$valid = false;
						}
						break;
					case 'int':
						if( !is_int( constant($defineName) ) || constant($defineName) <= 0 ) {
							$testCase->setResult( $errorLevel,
										'The '.$defineName.' option should have a numeric, positive value set and no quotes should be used.', $pleaseCheck );
							$valid = false;
						}
						break;
					case 'double':
						if( !is_double( constant($defineName) ) || constant($defineName) <= 0 ) {
							$testCase->setResult( $errorLevel,
										'The '.$defineName.' option should have a numeric, positive value set and no quotes should be used.', $pleaseCheck );
							$valid = false;
						}
						break;
					case 'integer':
					case 'uint':
						if( !is_int( constant($defineName) ) ) {
							$testCase->setResult( $errorLevel,
										'The '.$defineName.' option should have a numeric value set and no quotes should be used.', $pleaseCheck );
							$valid = false;
						}
						break;
					case 'array':
						if( !is_array( unserialize( constant($defineName) ) ) ) {
							$testCase->setResult( $errorLevel,
											'The '.$defineName.' option should contain an array().', $pleaseCheck );
							$valid = false;
						}
						break;
				}
				LogHandler::Log('wwtest', 'INFO', "Validated the $defineName option at the $configFile file. Current value: ".constant($defineName) );
			} else {
				if( defined($defineName) ) {
					$pleaseCheck = is_null($help) ? 'Please remove it from the '.$configFile.' file.' : $help;
					$testCase->setResult( $errorLevel, "The option $defineName is obsoleted but still defined.", $pleaseCheck );
					$valid = false;
					continue;
				}
				LogHandler::Log('wwtest', 'INFO', "Validated the obsoleted $defineName option for the $configFile file." );
			}
		}
		return $valid;
	}

	/**
	 * Validates a given file path.
	 *
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $filePath Full file path to validate.
	 * @param string $help The custom help string that can be pass by caller
	 * @param boolean $isDirPath By default is true to test on directory path, when false test on file path
	 * @param string $errorLevel Severity when validation fails. Typically 'ERROR' or 'WARN' can be used.
	 * @param integer $validateOpts Flags indicating which validation rules to apply.
	 * @return boolean Whether or not ok. False when one validation failed.
	 */
	public function validateFilePath( TestCase $testCase, $filePath, $help, $isDirPath = true,
			$errorLevel = 'ERROR', $validateOpts = self::VALIDATE_PATH_ALL )
	{
		if( ($validateOpts & self::VALIDATE_PATH_NOT_EMPTY) == self::VALIDATE_PATH_NOT_EMPTY ) {
			$val = trim($filePath);
			if( strlen($val) == 0 ) {
				$testCase->setResult( $errorLevel, "The file path $filePath is empty.", $help );
				return false;
			}
		}
		$endsWithSlash = (strrpos($filePath,'/') === (strlen($filePath)-1));
		if( ($validateOpts & self::VALIDATE_PATH_NO_SLASH) == self::VALIDATE_PATH_NO_SLASH ) {
			if( $endsWithSlash ) {
				$testCase->setResult( $errorLevel, "The file path $filePath should not end with a slash.", $help );
				return false;
			}
		} else {
			if( !$endsWithSlash ) {
				$testCase->setResult( $errorLevel, "The file path $filePath should end with a slash.", $help );
				return false;
			}
		}
		$endsWithSpace = (strrpos($filePath,' ') === (strlen($filePath)-1));
		if( ($validateOpts & self::VALIDATE_PATH_NO_SPACE) == self::VALIDATE_PATH_NO_SPACE ) {
			if( $endsWithSpace ) {
				$testCase->setResult( $errorLevel, "The file path $filePath should not end with a space.", $help );
				return false;
			}
		}
		if( ($validateOpts & self::VALIDATE_PATH_FILE_EXISTS) == self::VALIDATE_PATH_FILE_EXISTS ) {
			if( $isDirPath ) {
				if( strrpos($filePath, '/' ) == strlen($filePath) - 1 ) {
					$filePath = substr( $filePath, 0, strrpos( $filePath, '/' ) ); // Remove end slash
				}
				if( !$this->dirPathExists( $filePath ) ) {
					$testCase->setResult( $errorLevel, "The $filePath folder does not exist.", $help );
					return false;
				}
			} else {
				if( !$this->filePathExists( $filePath ) ) {
					$testCase->setResult( $errorLevel, "The file path $filePath does not exist.", $help );
					return false;
				}
			}
		}
		// Note: Do NOT add file access checksum here; Introduce other function to do such! (e.g. validateFileAccess)
		return true;
	}

    public function validateDirAccess( TestCase $testCase, $filePath, $help, $isDirPath = true,
        $errorLevel = 'ERROR', $validateOpts = self::VALIDATE_DIR_ACCESS ) 
    {
        if( ($validateOpts & self::VALIDATE_DIR_ACCESS) == self::VALIDATE_DIR_ACCESS ) {
            $val = trim($filePath);
            require_once BASEDIR.'/utils/FolderUtils.class.php';
            if ( !FolderUtils::isDirWritable( $val ) ) {
                $testCase->setResult( $errorLevel, "The file path $filePath is not accessible.", $help );
                return false;
            }
        }
        return true;
    }

	/**
	 * Check whether given folder path is a directory with case sensitivity.
	 * The behavior of internal PHP functions(is_dir, realpath) checking on case sensitive path is different between PHP 5.2 / 5.3
	 * and between the OS flavors and therefore this functions should be used.
	 * PHP realpath() fails on UNC path, it is returning the UNC path in uppercase. See EN-30405
	 * Use is_dir() to check a UNC path, and use realpath to test a local path.
	 * A UNC path can either start with '//' or '\\\\'. See EN-86710
	 * Note that within single quotes the '\' still has its special meaning when followed by a '\' or a single quote.
	 *
	 * @param string $filePath Given file path must be without ending slash
	 * @return boolean true|false
	 */
	public function dirPathExists( $filePath )
	{
		$realPath = null;
		if( OS == 'WIN' ) {
			if( substr( $filePath, 0, 2 ) == '//' || substr( $filePath, 0, 2 ) == '\\\\') { // Network UNC path
				return is_dir( $filePath );
			}

			$realPath = $this->realPathFwdSlash( $filePath );
		} else {
			$orgPath = getcwd();
			@chdir( $filePath );
			$realPath = getcwd();
			chdir( $orgPath ); // restore
		}
		return $realPath === $filePath;
	}

	/**
	 * Check whether given folder path is a directory with case sensitivity.
	 * The behavior of internal PHP functions(is_dir, realpath) checking on case sensitive path is different between PHP 5.2 / 5.3
	 * and between the OS flavors and therefore this functions should be used.
	 *
	 * @param string $filePath
	 * @return boolean true|false
	 */
	public function filePathExists( $filePath )
	{
		$result = false;
		if( OS == 'WIN' ) {
			$result = $this->realPathFwdSlash( $filePath ) !== false ? true : false;
		} else {
			$parentDir = dirname($filePath);
			if ( $this->dirPathExists( $parentDir ) !== false ) {
				$globFiles = glob( $parentDir.'/*' );
				$fileName = basename( $filePath );
				foreach( $globFiles as $globFile ) {
					$globFileName = basename($globFile);
					if( strcmp( $fileName, $globFileName ) === 0 ) {
						$result = true;
						break;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Return real path value with replacement of forward slash
	 *
	 * @param string $dir
	 * @return string $realPath
	 */
	public function realPathFwdSlash( $filePath )
	{
		$realPath = realpath( $filePath );
		if( DIRECTORY_SEPARATOR == '\\' ) { // Windows
			$realPath = str_replace( '\\', '/', $realPath );
		}
		return $realPath;
	}
	

	/**
	 * Does Ping the given URL and waits for 5 seconds.
	 * When no port is given, 80 is used for http or 443 is used for https.
	 *
	 * @param string $testUrl
	 * @param string $httpMethod HTTP method: GET, POST, PUT, DELETE, etc
	 * @return bool
	 */
	static public function validateUrl( TestCase $testCase, $testUrl, $help, 
			$httpMethod = 'GET', $errorLevel = 'ERROR', $validateOpts = self::VALIDATE_URL_ALL )
	{
		// Validate URL syntax.
		try {
			require_once 'Zend/Uri.php';
			$testUri = Zend_Uri::factory( $testUrl );
			$isHttps = $testUri && $testUri->getScheme() == 'https';
		} catch( Zend_Uri_Exception $e ) {
			$message = 'URL does not seem to be valid: '.$e->getMessage();
			$testCase->setResult( $errorLevel, $message, $help );
			return false;
		}

		if( ($validateOpts & self::VALIDATE_URL_RESPONSIVE) == self::VALIDATE_URL_RESPONSIVE ) {
			require_once 'Zend/Http/Client.php';
			$httpClient = new Zend_Http_Client( $testUrl );

			// When the cURL extension is loaded we set the curl
			// options. Otherwise we can still try to connect with the
			// default socket adapter.
			if ( extension_loaded('curl') ) {
				// Set CURL options.
				$curlOpts = array();
				if( $isHttps ) { // SSL enabled server
					$curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
				}
				$curlOpts[CURLOPT_TIMEOUT] = 5;

				$httpClient->setConfig(	array(
					'adapter' => 'Zend_Http_Client_Adapter_Curl',
					'curloptions' => $curlOpts ) );
			}

			// Try to connect to given URL.
			try {
				$httpClient->setUri( $testUrl );
				$response = $httpClient->request( $httpMethod );
				if( !$response->isSuccessful() ) {
					$message = 'URL does not seems to be responsive: '
						.'HTTP/'.$response->getVersion().' '.$response->getStatus().' '.$response->getMessage();
					$testCase->setResult( $errorLevel, $message, $help );
					return false;
				}
			} catch( Zend_Http_Client_Exception $e ) {
				$message = 'URL does not seems to be responsive: '.$e->getMessage();
				$testCase->setResult( $errorLevel, $message, $help );
				return false;
			}
		}
		return true;
	}
}