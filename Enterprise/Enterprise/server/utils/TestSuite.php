<?php

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

	/** @var string $protocol  */
	private $protocol = null;

	/**
	 * Initializes this test utils class.
	 *
	 * @since 10.0.0
	 * @param string|null $protocol The used protocol for service calls. (Options: SOAP or JSON.) If null a regular service call is made.
	 * @return bool Whether or not all session variables are complete.
	 */
	public function initTest( $protocol = null )
	{
		$this->protocol = $protocol;
	}

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
	 * @param callable $valueToString [v10.1.1] Callback function that serializes the value before written to log.
	 * @return boolean Whether or not all ok. False when one validation failed. No matter failure, it tests -all- defines.
	 */
	public function validateDefines( 
			TestCase $testCase, array $defineNameTypes, $configFile = 'configserver.php',
			$errorLevel = 'ERROR', $validateOpts = self::VALIDATE_DEFINE_ALL, $help=null, $valueToString = null )
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
					$pleaseCheck = is_null($help) ? 'Please add it to the '.$configFile.' file. ' : $help;
					$testCase->setResult( $errorLevel, "The $defineName option is not defined.", $pleaseCheck );
					$valid = false;
					continue;
				}
				$pleaseCheck = is_null( $help ) ? 'Please fill in a value at the '.$configFile.' file. ' : $help;
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
				LogHandler::Log('wwtest', 'INFO', "Validated the $defineName option at the $configFile file.".
					' Current value: '.( $valueToString ? $valueToString( $defineName ) : constant( $defineName ) ) );
			} else {
				if( defined($defineName) ) {
					$pleaseCheck = is_null($help) ? 'Please remove it from the '.$configFile.' file.' : $help;
					$testCase->setResult( $errorLevel, "The option $defineName is obsolted but still defined.", $pleaseCheck );
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
		$isLocalRootFolder = $this->isLocalRootFolder( $filePath );
		if( !$isLocalRootFolder ) { // Root folder per definition has an end slash.
			$endsWithSlash = (strrpos($filePath,'/') === (strlen($filePath)-1));
			if( ( $validateOpts & self::VALIDATE_PATH_NO_SLASH ) == self::VALIDATE_PATH_NO_SLASH ) {
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
				if( !$isLocalRootFolder && strrpos($filePath, '/' ) == strlen($filePath) - 1 ) {
					$filePath = substr( $filePath, 0, strrpos( $filePath, '/' ) ); // Remove end slash but not for root folder.
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

	/**
	 * Checks if a directory is the root directory. On Unix the root directory is '/'. On windows it is '<drive>:/',
	 * e.g. 'C:/'. This method does not check if a directory is a UNC root folder. You cannot end up in the UNC root
	 * folder as a path to such a directory is always something like '//volume/sharedfolder'.
	 *
	 * @param string $directory
	 * @return bool
	 */
	private function isLocalRootFolder( $directory )
	{
		if( OS == 'WIN' ) {
			$isLocalRoot = preg_match('/^[a-zA-Z]:\/$/', $directory);
		} else {
			$isLocalRoot = $directory == '/';
		}

		return $isLocalRoot;
	}

	/**
	 * Extracts the parent directory of the passed in directory.
	 *
	 * The parent directory is returned without ending slash except when the parent directory is the root directory.
	 * Unix:
	 * $directory           $parentDir
	 * '/parent/child/'   => '/parent'
	 * '/parent/child'   => '/parent'
	 * '/child/'          => '/'
	 * '/child'          => '/'
	 * Windows:
	 * 'C:/parent/child/' => 'C:/parent'
	 * 'C:/parent/child' => 'C:/parent'
	 * 'C:/child/'        => 'C:/'
	 * 'C:/child'        => 'C:/'
	 *
	 * @since 10.1.7
	 * @param string $directory
	 * @return string
	 */
	public function extractParentFolder( $directory )
	{
		if( strrpos($directory, '/' ) == strlen($directory) - 1 ) {
			$directory = substr( $directory, 0, strrpos( $directory, '/' ) ); // Remove end slash
		}
		if( substr_count( $directory, '/') == 1 ) { // Only one remaining slash
			$parentDir = substr( $directory, 0, strrpos( $directory, '/' ) + 1 );
		} else {
			$parentDir = substr( $directory, 0, strrpos( $directory, '/' ) );
		}

		return $parentDir;
	}

	/**
	 * Check whether given folder path is a directory with case sensitivity.
	 * The behavior of internal PHP functions(is_dir, realpath) checking on case sensitive path is different between PHP 5.2 / 5.3
	 * and between the OS flavors and therefore this functions should be used.
	 * PHP realpath() fails on a UNC path, it is returning the UNC path in uppercase. See EN-30405
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
	 * @param string $filePath
	 * @return string Real path
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
	 * Runs any service request. When a given expected error is not raised by the service,
	 * the test fails. And vice versa, when no error expected, but raised, the test fails.
	 *
	 * The expected error ($expectedErrorCodeOrMsgKey) is preferably an error code, but when
	 * not available, an error message key. The code (in "(Sxxxx)" format) is taken from 
	 * BizException->getErrorCode(). Since 9.4, when there is no such S-code provided, the 
	 * resource key is taken from BizException->getMessageKey() and used instead.
	 *
	 * @param TestCase $testCase The test module calling this function.
	 * @param object $request
	 * @param string $stepInfo Logical test description.
	 * @param string|null $expectedErrorCodeOrMsgKey The expected error message key or error code. Null when no error is expected.
	 * @param bool $throwException TRUE to throw BizException and setError(), FALSE to setError() only, on unexpected failures
	 * @return mixed A corresponding response object when service was successful or NULL on error.
	 */
	public function callService( TestCase $testCase, $request, $stepInfo, $expectedErrorCodeOrMsgKey = null, 
		$throwException = false )
	{
		$baseName = get_class( $request );
		$baseName = substr( $baseName, 0, strlen($baseName) - strlen('Request') );
		$responseName = $baseName.'Response';

		// When there is a sCode from $expectedErrorCodeOrMsgKey, add it to the severity map
		// to suppress errors appearing at server logging.
		$expectedSCode = null;
		$expectedError = null; // expected error code or error message
		if( $expectedErrorCodeOrMsgKey ) { // Error expected.
			$expectedSCodes = array();
			preg_match_all( '/\((S[0-9]+)\)/', $expectedErrorCodeOrMsgKey, $expectedSCodes); //grab S(xxxx) error code (S-code) from Exception::getMessage()
			// There should be only one S-code, but when many, take last one since those codes are at the end of message (=rule).
			$expectedSCode = count($expectedSCodes[1]) > 0 ? $expectedSCodes[1][count($expectedSCodes[1])-1] : '';
			$expectedError = $expectedSCode ? $expectedSCode : $expectedErrorCodeOrMsgKey;
			if( $expectedError ) {
				$map = new BizExceptionSeverityMap( array( $expectedError => 'INFO' ) );
			}
		}
				
		LogHandler::Log( 'TestSuite', 'DEBUG', "Test: {$stepInfo}" );
		$response = null;
		try {
			if( !$this->protocol ) {
				$serviceName = $baseName.'Service';
				$service = new $serviceName();
				$service->suppressRecording();
				$response = $service->execute( $request );
			} elseif( $this->protocol == 'SOAP' ) {
				$response = $this->executeSoap( $request, $expectedSCode );
			} elseif( $this->protocol == 'JSON' ) {
				$response = $this->executeJson( $request, $expectedSCode );
			} else {
				$message = 'Invalid protocol used for service call: "'.$this->protocol.'"';
				if( $throwException ) {
					$testCase->throwError( $message );
				} else {
					$testCase->setResult( 'ERROR', $message, '' );
				}
			}
			if( $response ) {
				if( $expectedErrorCodeOrMsgKey ) { // Should not come here as error is expected!!
					$message = '<b>Test: </b>'.$stepInfo.'<br/>'.
							'<b>The service response was unexpected: </b>Success!<br/>'.
							'<b>Expected response: </b>'.$expectedErrorCodeOrMsgKey.'.';
					if( $throwException ) {
						$testCase->throwError( $message );
					} else {
						$testCase->setResult( 'ERROR', $message, '' );
					}
				}
				$actualResponse = get_class( $response );
				if( $responseName != $actualResponse ) {
					$message = "Expected object class is $responseName but actual is $actualResponse.";
					if( $throwException ) {
						$testCase->throwError( $message );
					} else {
						$testCase->setResult( 'ERROR', $message, '' );
					}
				}
			}
		} catch( BizException $e ) {
			if( $expectedErrorCodeOrMsgKey ) { // Error expected.
				
				// Compare error codes or message keys or message texts.
				$sysError = $expectedSCode ? $e->getErrorCode() : $e->getMessageKey();
				if( !$sysError ) {
					$sysError = $e->getMessage();
				}
				
				// Expect an error here, but is the error same as the $expectedError?
				if( $sysError != $expectedError ) {
					$message = '<b>Test: </b>'.$stepInfo.'<br/>'.
								'<b>The service response was unexpected: </b>'.$e->getMessage().
								' (Detail: '.$e->getDetail().')<br/>'.
								'<b>Expected response: </b>'.$expectedErrorCodeOrMsgKey.'.';
					if( $throwException ) {
						$testCase->throwError( $message );
					} else {
						$testCase->setResult( 'ERROR', $message, '' );
					}
				}				
			} else { // No error expected
				$message =  '<b>Test: </b>'.$stepInfo.'<br/>'.
							'<b>The service response was unexpected: </b>'.$e->getMessage().
							' (Detail: '.$e->getDetail().')<br/>'.
							'<b>Expected response: </b>Success!';
				if( $throwException ) {
					$testCase->throwError( $message );
				} else {
					$testCase->setResult( 'ERROR', $message, '' );
				}
			}
		}
		return $response;
	}

	/**
	 * Executes any service through a soap client.
	 *
	 * @param object $request Request object to execute.
	 * @param string|null $expectedSCode Expected server error (S-code). Use null to indicate no error is expected.
	 * @return object Response object.
	 * @throws BizException when the web service failed.
	 */
	private function executeSoap( $request, $expectedSCode )
	{
		$requestClass = get_class( $request ); // e.g. returns 'WflDeleteObjectsRequest'
		$servicePrefix = substr( $requestClass, 0, 3 );
		$function = substr( $requestClass, 3, strlen($requestClass) - 3 - strlen('Request') );

		require_once BASEDIR.'/server/protocols/soap/'.$servicePrefix.'Client.php';
		$clientClass = 'WW_SOAP_'.$servicePrefix.'Client';
		$options = array(
			'transfer' => 'HTTP',
			'protocol' => 'SOAP',
		);
		if( $expectedSCode ) {
			$options['expectedError'] = $expectedSCode;
		}
		try {
			$soapClients[$servicePrefix] = new $clientClass( $options );
			$response = $soapClients[$servicePrefix]->$function( $request );
		} catch( SoapFault $e ) {
			throw new BizException( '', 'Server', '', $e->getMessage() );
		}
		return $response;
	}

	/**
	 * Executes any service through a JSON client.
	 *
	 * @since 10.0.0
	 * @param object $request Request object to execute.
	 * @param string|null $expectedSCode Expected server error (S-code). Use null to indicate no error is expected.
	 * @return object Response object.
	 * @throws BizException when the web service failed.
	 */
	private function executeJson( $request, $expectedSCode )
	{
		$requestClass = get_class( $request ); // e.g. returns 'WflDeleteObjectsRequest'
		$servicePrefix = substr( $requestClass, 0, 3 );
		$function = substr( $requestClass, 3, strlen($requestClass) - 3 - strlen('Request') );

		require_once BASEDIR.'/server/protocols/json/'.$servicePrefix.'Client.php';
		$clientClass = 'WW_JSON_'.$servicePrefix.'Client';
		$options = array();
		if( $expectedSCode ) {
			$options['expectedError'] = $expectedSCode;
		}
		try {
			$soapClients[$servicePrefix] = new $clientClass( '', $options );
			$response = $soapClients[$servicePrefix]->$function( $request );
		} catch( Exception $e ) {
			throw new BizException( '', 'Server', '', $e->getMessage() );
		}
		return $response;
	}

	/**
	 * Calls the admin interface to LogOn given user.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @return AdmLogOnResponse|null Response on success. NULL on error.
	 */
	public function admLogOn( TestCase $testCase )
	{
		// Determine client app name
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientIP = WW_Utils_UrlUtils::getClientIP();
		$clientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
		// >>> BZ#6359 Let's use ip since gethostbyaddr could be extreemly expensive!
		if( empty($clientName) ) { $clientName = $clientIP; }
		// if ( !$clientName || ($clientName == $clientIP )) { $clientName = gethostbyaddr($clientIP); }
		// <<<
		
		// Build the LogOn request.
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		require_once BASEDIR.'/server/services/adm/AdmLogOnService.class.php';
		$request = new AdmLogOnRequest();
		$request->AdminUser     = WW_Utils_TestSuiteOptions::getUser();
		$request->Password      = WW_Utils_TestSuiteOptions::getPassword();
		$request->Server        = 'Enterprise Server';
		$request->ClientName    = $clientName;
		$request->Domain        = '';
		$request->ClientAppName = 'TestSuite-Adm';
		$request->ClientAppVersion = 'v'.SERVERVERSION;
		$request->ClientAppSerial = '';
		$request->ClientAppProductKey = '';

		// Logon the user at Enterprise Server through the admin interface.
		$stepInfo = 'Logon TESTSUITE user.';
		$response = $this->callService( $testCase, $request, $stepInfo );
		
		// In case of error, show hint at BuildTest page.
		if( is_null($response) ) {
			$testCase->setResult( 'ERROR', 'Could not logon test user. ', 
				'Please check the TESTSUITE option at configserver.php.' );
		}
		return $response;
	}

	/**
	 * Logout the TESTSUITE user by calling the LogOff request through admin interface.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 */
	public function admLogOff( TestCase $testCase, $ticket )
	{
		require_once BASEDIR.'/server/services/adm/AdmLogOffService.class.php';
		$request = new AdmLogOffRequest();
		$request->Ticket = $ticket;
		$stepInfo = 'LogOff TESTSUITE user.';
		/*$response =*/ $this->callService( $testCase, $request, $stepInfo );		
	}

	/**
	 * Ensures that TESTSUITE is defined in the configserver.php file.
	 * Does logon the TESTSUITE user at Enterprise Server (by using LogOn web service).
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @return WflLogOnResponse|null Response on success. NULL on error.
	 */	 
	public function wflLogOn( TestCase $testCase )
	{	
		// Determine client app name
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientIP = WW_Utils_UrlUtils::getClientIP();
		$clientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
		// >>> BZ#6359 Let's use ip since gethostbyaddr could be extremely expensive!
		if( empty($clientName) ) { $clientName = $clientIP; }
		// if ( !$clientName || ($clientName == $clientIP )) { $clientName = gethostbyaddr($clientIP); }
		// <<<
		
		// Build the LogOn request.
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		require_once BASEDIR.'/server/utils/TestSuiteOptions.php';
		$request = new WflLogOnRequest();
		$request->User = WW_Utils_TestSuiteOptions::getUser();
		$request->Password = WW_Utils_TestSuiteOptions::getPassword();
		$request->Ticket = '';
		$request->Server = 'Enterprise Server';
		$request->ClientName = $clientName;
		$request->Domain = '';
		$request->ClientAppName = 'TestSuite-Wfl';
		$request->ClientAppVersion = 'v'.SERVERVERSION;
		$request->ClientAppSerial = '';
		$request->ClientAppProductKey = '';
		
		// LogOn the TESTSUITE user at Enterprise Server.
		$stepInfo = 'Logon TESTSUITE user.';
		$response = $this->callService( $testCase, $request, $stepInfo );

		// In case of error, show hint at BuildTest page.
		if( is_null($response) ) {
			$testCase->setResult( 'ERROR', 'Could not logon test user.', 
				'Please check the TESTSUITE option at configserver.php.' );
		}
		return $response;
	}
	
	/**
	 * Logout the TESTSUITE user by calling the LogOff request through workflow interface.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 */
	public function wflLogOff( TestCase $testCase, $ticket )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		$request = new WflLogOffRequest();
		$request->Ticket = $ticket;
		$request->SaveSettings = false;
		$stepInfo = 'LogOff TESTSUITE user.';
		/*$response =*/ $this->callService( $testCase, $request, $stepInfo );		
	}

	/**
	 * Copies a given publication/brand ($sourcePubId) and returns this newly copied publication/brand.
	 *
	 * @since 10.1.6
	 * @param int $sourcePubId The original publication id to be copied to a new one.
	 * @param string $newPubName The New name for the newly copied publication.
	 * @param bool $copyIssues True to copy over the issues from the original publication, false otherwise.
	 * @param string $prefixName For debugging purposes, name prefix to apply to all copied items inside publication for ease recognizion.
	 *                           Leave this empty when no prefix is needed.
	 * @throws BizException
	 * @return int The newly copied publication id.
	 */
	public function copyPublication( $sourcePubId, $newPubName, $copyIssues, $prefixName )
	{
		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		return BizCascadePub::copyPublication( $sourcePubId, $newPubName, $copyIssues, $prefixName );
	}

	/**
	 * Deletes a publication and all its corresponding settings.
	 *
	 * @since 10.1.6
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket A valid test session ticket.
	 * @param int $pubId Publication id of the publication to be deleted.
	 * @throws BizException
	 */
	public function deletePublication( TestCase $testCase, $ticket, $pubId )
	{
		try {
			require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
			$request = new AdmDeletePublicationsRequest();
			$request->Ticket = $ticket;
			$request->PublicationIds = array( $pubId );

			$stepInfo = 'Delete Brand ( id = ' . $pubId . ' ).';
			$response = $this->callService( $testCase, $request, $stepInfo );

			if ( !$response instanceof AdmDeletePublicationsResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()',
					'Could not delete Brand ( id = ' . $pubId . ' ).' );
			}
		} catch( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
	}
	
	/**
	 * Creates a new Publication Channel
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @param int $pubId
	 * @param AdmPubChannel $pubChannel
	 * @return AdmCreatePubChannelsResponse|null
	 * @throws BizException
	 */
	public function createNewPubChannel( TestCase $testCase, $ticket, $pubId, $pubChannel )
	{
		try {
			require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';
			$request = new AdmCreatePubChannelsRequest();
			$request->Ticket = $ticket;
			$request->RequestModes  = array();
			$request->PublicationId = $pubId;
			$request->PubChannels   = array( $pubChannel );

			$stepInfo = 'Create new Publication Channel.';
			$response = $this->callService( $testCase, $request, $stepInfo );

			if ( !$response instanceof AdmCreatePubChannelsResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not create Publication Channel' );
			}
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		return $response;
	}

	/**
	 * Deletes a PubChannel given the Publication Id and PubChannel Id.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @param int $publicationId
	 * @param int $pubChannelId
	 * @return AdmDeletePubChannelsResponse|null
	 * @throws BizException
	 */
	public function removePubChannel( TestCase $testCase, $ticket, $publicationId, $pubChannelId )
	{
		try {
			require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';
			$request = new AdmDeletePubChannelsRequest();
			$request->Ticket        = $ticket;
			$request->PublicationId = $publicationId;
			$request->PubChannelIds = array( $pubChannelId );

			$stepInfo = 'Remove Publication Channel.';
			$response = $this->callService( $testCase, $request, $stepInfo );

			if ( !$response instanceof AdmDeletePubChannelsResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not remove Publication Channel' );
			}
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		return $response;
	}
	
	/**
	 * Creates a new Issue.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @param int $pubId
	 * @param int $pubChannelId
	 * @param AdmIssue $issue
	 * @return AdmCreateIssuesResponse|null
	 * @throws BizException
	 */
	public function createNewIssue( TestCase $testCase, $ticket, $pubId, $pubChannelId, $issue )
	{
		try {
			require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
			$request = new AdmCreateIssuesRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->PublicationId = $pubId;
			$request->PubChannelId  = $pubChannelId;
			$request->Issues        = array( $issue );

			$stepInfo = 'Create new Issue.';
			$response = $this->callService( $testCase, $request, $stepInfo );

			if ( !$response instanceof AdmCreateIssuesResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not create Issue' );
			}
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		return $response;
	}

	/**
	 * Deletes an Issue.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @param int $publicationId
	 * @param int $issueId
	 * @return AdmDeleteIssuesResponse|null
	 * @throws BizException
	 */
	public function removeIssue( TestCase $testCase, $ticket, $publicationId, $issueId )
	{
		try {
			require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
			$request = new AdmDeleteIssuesRequest();
			$request->Ticket        = $ticket;
			$request->PublicationId = $publicationId;
			$request->IssueIds      = array( $issueId );

			$stepInfo = 'Remove Issue.';
			$response = $this->callService( $testCase, $request, $stepInfo );

			if ( !$response instanceof AdmDeleteIssuesResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not remove Issue' );
			}
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		return $response;
	}

	/**
	 * Returns those table names of the DB model that have an auto increment field defined.
	 *
	 * It is mainly used when caller wants to set the auto increment value for a table. 
	 * The caller needs to know which table has primary key to set its auto increment value.
	 *
	 * Note that there are many places in the core server assuming that when a table
	 * has an "id" field, the auto increment option must be enabled for that field.
	 * And, if there is no "id" field, there is no auto increment defined for the table.
	 *
	 * @return array Table names which has primary key. (Table names with prefix 'smart_')
	 */
	public function getDbTablesWithAutoIncrement()
	{
		require_once BASEDIR.'/server/dbscripts/dbmodel.php';

		$dbTablesWithAutoIncrement = array();
		$dbStruct = new DBStruct();		
		$tablesWithoutAutoIncrement = $dbStruct->getTablesWithoutAutoIncrement();
		$dbTables = $dbStruct->listTables();
		foreach( $dbTables as $dbTable ) {
			if( !in_array( $dbTable['name'], $tablesWithoutAutoIncrement )) {
				$dbTablesWithAutoIncrement[] = $dbTable['name'];
			}
		}
		return $dbTablesWithAutoIncrement;
	}
	
	/**
	 * Set the auto increment of $dbTables to a value given.
	 *
	 * @param integer $autoIncrementVal Value to be reset to in the auto increment.
	 * @param array $dbTables array of Db table names.
	 */
	public function setAutoIncrement( $dbTables, $autoIncrementVal )
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		foreach( $dbTables as $dbTable ) {
			DBBase::resetAutoIncreament( $dbTable, $autoIncrementVal );
		}
	}
	
	/**
	 * Activates a given plug-in, but only when not activated yet. Before activation
	 * the plug-in will get implicitly installed when needed.
	 * Activation errors are recorded through $testCase->setError().
	 *
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $pluginName Server Plug-in to activate.
	 * @return TRUE when activated. FALSE when no action taken. NULL on activation error.
	 */
	public function activatePluginByName( TestCase $testCase, $pluginName )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		if( BizServerPlugin::isPluginActivated( $pluginName ) ) {
			$didActivate = false;
		} else {
			try {
				$pluginInfo = BizServerPlugin::activatePluginByName( $pluginName );
				if( $pluginInfo ) {
					$didActivate = true;
				} else {
					$msg = 'Server Plug-in "'.$pluginName.'" could not be activated. '.
							'The plug-in could not be found in database. ';
					$testCase->setResult( 'ERROR',  $msg, '' );
					$didActivate = null;
				}
			} catch( BizException $e ) {
				$msg = 'Server Plug-in "'.$pluginName.'" could not be activated. '.
						$e->getMessage().' '.$e->getDetail();
				$testCase->setResult( 'ERROR',  $msg, '' );
				$didActivate = null;
			}
		}
		return $didActivate;
	}
	
	/**
	 * Deactivates a given plug-in, but only when not activated yet.
	 * Deactivation errors are recorded through $testCase->setError().
	 *
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $pluginName Server Plug-in to deactivate.
	 * @return TRUE when deactivated. FALSE when no action taken. NULL on deactivation error.
	 */
	public function deactivatePluginByName( TestCase $testCase, $pluginName )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		if( !BizServerPlugin::isPluginActivated( $pluginName ) ) {
			$didDeactivate = false;
		} else {
			try {
				$pluginInfo = BizServerPlugin::deactivatePluginByName( $pluginName );
				if( $pluginInfo ) {
					$didDeactivate = true;
				} else {
					$msg = 'Server Plug-in "'.$pluginName.'" is could not be deactivated. '.
							'The plug-in could not be found in database. ';
					$testCase->setResult( 'ERROR',  $msg, '' );
					$didDeactivate = null;
				}
			} catch( BizException $e ) {
				$msg = 'Server Plug-in "'.$pluginName.'" is could not be deactivated. '.
						$e->getMessage().' '.$e->getDetail();
				$testCase->setResult( 'ERROR',  $msg, '' );
				$didDeactivate = null;
			}
		}
		return $didDeactivate;
	}

	/**
	 * Parses the test options info from the logon response.
	 *
	 * Builds an array with an info structure for each option in TESTSUITE.
	 * Returns null and sets an appropriate error message on failure.
	 *
	 * @param TestCase $testCase test case parsing the logon response
	 * @param WflLogOnResponse $response The test module calling this function.
	 * @return array contains test info options, null on failure.
	 */
	public function parseTestSuiteOptions( TestCase $testCase, WflLogOnResponse $response )
	{
		$testSuiteOptions = array();

		$testOptions = (defined('TESTSUITE')) ? unserialize( TESTSUITE ) : array();
		if( !$testOptions ) {
			$testCase->setResult( 'ERROR', 'TESTSUITE options not defined.',
				'Please check the TESTSUITE setting in configserver.php.' );
			return null;
		}

		// Determine the brand+channels and the print channel+issue to work with.
		if( count($response->Publications) > 0 ) {
			require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

			// Search for the Brand specified in the TESTSUITE['Brand'] option.
			foreach( $response->Publications as $pub ) {
				if( $pub->Name == $testOptions['Brand'] ) {

					// Remember the Brand and its Pub Channels.
					$testSuiteOptions['Brand'] = $pub;
					$testSuiteOptions['Channels'] = $pub->PubChannels;

					// Search for the desired print Issue and Pub Channel.
					foreach( $pub->PubChannels as $pubChannel ) {
						foreach( $pubChannel->Issues as $issue ) {
							if( $issue->Name == $testOptions['Issue'] ) {
								$testSuiteOptions['Issue'] = $issue;
							}
						}
					}
				}
			}
		}

		// Add a default category
		$testSuiteOptions['Category'] = count( $testSuiteOptions['Brand']->Categories ) > 0  ? $testSuiteOptions['Brand']->Categories[0] : null;
		if( !$testSuiteOptions['Category'] ) {
			$testCase->setResult( 'ERROR', 'Could not find a test Category for Brand "'.$testOptions['Brand'].'". ',
				'Check your Brand setup and add a category.' );
			return null;
		}

		// Validate the found admin entities.
		if( !$testSuiteOptions['Brand'] ) {
			$testCase->setResult( 'ERROR', 'Could not find the test Brand "'.$testOptions['Brand'].'". ',
				'Please check the TESTSUITE setting in configserver.php and/or check your Brand setup.' );
			return null;
		}
		if ( !$testSuiteOptions['Issue'] ) {
			$testCase->setResult( 'ERROR', 'Could not find the test Issue "'.$testOptions['Issue'].'" '.
				'configured for test Brand "'.$testOptions['Brand'].'". ',
				'Please check the TESTSUITE setting in configserver.php and/or check your Brand setup.' );
			return null;
		}

		return $testSuiteOptions;
	}

	/**
	 * Returns the first encountered status from publication info of the specified type.
	 *
	 * Non personal statuses are preferred.
	 *
	 * @param TestCase $testCase
	 * @param $pubInfo Publication info object
	 * @param string $type Object type (e.g. Article)
	 * @return Status info object, or null on failure
	 */
	public function getFirstStatusInfoForType( TestCase $testCase, $pubInfo, $type )
	{
		$statusInfo = null;
		if( $pubInfo->States ) foreach( $pubInfo->States as $status ) {
			if( $status->Type == $type ) {
				$statusInfo = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$statusInfo ) {
			$testCase->setResult( 'ERROR', 'Brand "'.$pubInfo->Name.'" has no ' . $type . ' Status to work with.',
				'Please check the Brand Maintenance page and configure one.' );
			return null;
		}
		return $statusInfo;
	}

	/**
	 * Upload article content to Transfer Server (no longer using DIME attachments).
	 * @param TestCase $testCase
	 * @param array $objects List of objects where its file attachment are to be uploaded to the TransferServer.
	 * @return bool true on success
	 */
	public function uploadObjectsToTransferServer( TestCase $testCase, array $objects )
	{
		foreach( $objects as $object  ) {
			$attachment = $object->Files[0];
			$content = $attachment->Content;
			$attachment->Content = null;
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( !$transferServer->writeContentToFileTransferServer( $content, $attachment ) ) {
				$objectName = $object->MetaData->BasicMetaData->Name;
				$testCase->setResult( 'ERROR', 'Failed uploading native file for object "'.$objectName.'".',
					'Check the Transfer Server settings at the configserver.php file.' );
				return false;
			}
		}
		return true;
	}

	/**
	 * Create object via CreateObjects service call.
	 *
	 * @param TestCase $testCase
	 * @param string $ticket A valid test session ticket.
	 * @param array $objects List of objects to be created via CreateObjects service call.
	 * @param bool $lock Whether or not to lock the object
	 * @param string|null $stepInfo Optional step info in case an error occurs
	 * @param string|NULL $expectedErrorCodeOrMsgKey The exception message from BizException->getMessage(), Null when no error is expected.
	 * @return created objects
	 */
	public function callCreateObjectService( TestCase $testCase, $ticket, $objects, $lock = false, $stepInfo = null, $expectedErrorCodeOrMsgKey = null )
	{
		// Create the article objects at Enterprise DB
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket	= $ticket;
		$request->Lock		= $lock;
		$request->Objects	= $objects;

		$response = self::callService( $testCase, $request, $stepInfo ? $stepInfo : 'Create object', $expectedErrorCodeOrMsgKey );
		if( is_null($response) ) {
			return null;
		}

		return $response;
	}

	/**
	 * Deletes a given object from the database by calling the DeleteObjects service.
	 *
	 * @param TestCase $testCase Test case being executed
	 * @param string $ticket A valid test session ticket.
	 * @param int $objId The id of the object to be removed.
	 * @param string $stepInfo Extra logging info.
	 * @param string &$errorReport To fill in the error message if there's any during the delete operation.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @param bool $permanent Whether or not to delete the object permanently.
	 * @param array $areas The areas to test against.
	 * @return bool Whether or not service response was according to given expectations ($expectedError).
	 */
	public function deleteObject( TestCase $testCase, $ticket, $objId, $stepInfo, &$errorReport, $expectedError = null, $permanent=true, $areas=array('Workflow'))
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket    = $ticket;
		$request->IDs       = array($objId);
		$request->Permanent = $permanent;
		$request->Areas     = $areas;

		$response = $this->callService( $testCase, $request, $stepInfo, $expectedError );
		if( is_null( $response ) ) {
			return false;
		}

		$deleteSuccessful = true;
		if( $response->Reports && count( $response->Reports ) > 0 ) {
			foreach( $response->Reports as $report ) {
				$errorReport .= 'Failed deleted ObjectID:"' . $report->BelongsTo->ID . '" </br>';
				$errorReport .= 'Reason:';
				if( $report->Entries ) foreach( $report->Entries as $reportEntry ) {
					$errorReport .= $reportEntry->Message . '</br>';
				}
				$errorReport .= '</br>';
			}
			$deleteSuccessful = false;
		}
		return $deleteSuccessful;
	}

	/**
	 * Updates an object with given metadata by calling the SetObjectProperties service.
	 *
	 * @param $TestCase
	 * @param string $ticket A valid test session ticket.
	 * @param Object $object Object properties an targets to update. On success, it gets updated with latest info from DB.
	 * @param string $stepInfo Extra logging info.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @param array $changedPropPaths List of changed metadata properties, expected to be different.
	 * @return bool true on success
	 */
	public function setObjectProperties( $TestCase, $ticket, /** @noinspection PhpLanguageLevelInspection */
	                                     Object $object, $stepInfo, $expectedError, array $changedPropPaths )
	{
		// Call the SetObjectProperties service.
		require_once BASEDIR . '/server/services/wfl/WflSetObjectPropertiesService.class.php';
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket	= $ticket;
		$request->ID        = $object->MetaData->BasicMetaData->ID;
		$request->MetaData  = $object->MetaData;
		$request->Targets   = $object->Targets;
		$response = $this->callService( $TestCase, $request, $stepInfo, $expectedError );
		$responseOk = ($response && !$expectedError) || (!$response && $expectedError);

		$compareOk = true;
		if( !is_null($response) ) {

			// Validate MetaData and Targets; Compare the original ones with the ones found in service response.
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();

			$phpCompare->initCompare( $changedPropPaths, array() );
			if( !$phpCompare->compareTwoProps( $object->MetaData, $response->MetaData ) ) {
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$TestCase->setResult( 'ERROR', $errorMsg, 'Problem detected in MetaData of SetObjectProperties response.');
				$compareOk = false;
			}
			foreach( $changedPropPaths as $changedPropPath => $expPropValue ) {
				$retPropValue = null;
				eval( '$retPropValue = $response->MetaData->'.$changedPropPath.';' );
				if( $retPropValue != $expPropValue ) {
					$errorMsg = 'The returned MetaData->'.$changedPropPath.' is set to "'.
						$retPropValue.'" but should be set "'.$expPropValue.'".';
					$TestCase->setResult( 'ERROR', $errorMsg, 'Problem detected in MetaData of SetObjectProperties response.');
					$compareOk = false;
				}
			}
			$phpCompare->initCompare( array(), array() );
			if( !$phpCompare->compareTwoProps( $object->Targets, $response->Targets ) ) {
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$TestCase->setResult( 'ERROR', $errorMsg, 'Problem detected in Targets of SetObjectProperties response.');
				$compareOk = false;
			}

			// Update the orignal/cached object with response data.
			$object->MetaData = $response->MetaData;
			$object->Targets  = $response->Targets;
		}
		return $compareOk && $responseOk;
	}

	/**
	 * Updates an object with given metadata by calling the MultiSetObjectProperties service.
	 *
	 * @param TestCase $testCase The test case executing the service. Errors are set on the test case object.
	 * @param string $ticket A valid test session ticket.
	 * @param Object[] $objects Objects properties to update. On success, they are updated with latest info from the DB.
	 * @param string $stepInfo Extra logging info.
	 * @param array $expectedReports Array of expected ErrorReport(s).
	 * @param MetaDataValue[] $updateProps List of metadata properties to update.
	 * @param string[] $changedPropPaths List of changed metadata properties, expected to be different.
	 * @param array $exclVerObjIds List of objectIds to be excluded from verification.
	 * @return bool|null Whether or not the operation was succesful, null when the service failed.
	 */
	public function multiSetObjectProperties(
		TestCase $testCase, $ticket, $objects, $stepInfo, array $expectedReports,
		array $updateProps, array $changedPropPaths, array $exclVerObjIds = array() )
	{
		// Collect object ids.
		$objectIds = array();
		foreach( $objects as $object ) {
			$objectIds[] = $object->MetaData->BasicMetaData->ID;
		}

		// Suppress errors that are expected.
		$serverityMap = array();
		$expectedError = '';
		foreach( $expectedReports as $expectedReport ) {
			foreach( $expectedReport->Entries as $entry ) {
				$expectedError = trim( $entry->ErrorCode,'()' ); // Remove () brackets.
				$serverityMap[$expectedError] = 'INFO';
			}
		}
		$severityMapHandle = new BizExceptionSeverityMap( $serverityMap );

		// Call the SetObjectProperties service.
		require_once BASEDIR . '/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket	= $ticket;
		$request->IDs       = $objectIds;
		$request->MetaData  = $updateProps;
		$response = $this->callService( $testCase, $request, $stepInfo, null );
		if( !$response ) {
			return null;
		}
		unset($severityMapHandle); // Until here the errors are expected, so end it.

		// Check if expected errors can be found in the returned error reports.
		$compareOk = true;

		foreach( $expectedReports as $expectedReport ) {
			$foundExpected = false;
			$expectedBelongsTo = $expectedReport->BelongsTo;
			foreach( $response->Reports as $report ) {
				$belongsTo = $report->BelongsTo;
				if( $belongsTo->Type == $expectedBelongsTo->Type && $belongsTo->ID == $expectedBelongsTo->ID ) {
					$foundExpected = true;
				}
			}
			if( !$foundExpected ) {
				$errorMsg = 'Expected to raise error "'.$expectedError.'" for '.
					'type "'.$expectedBelongsTo->Type.'" with ID "' . $expectedBelongsTo->ID
					. '" but it was not found in the error reports.';
				$errorContext = 'Problem detected in the Reports section of the MultiSetObjectProperties response.';
				$testCase->setResult( 'ERROR', $errorMsg, $errorContext );
				$compareOk = false;
			}
		}

		// Don't get objects for which an error was expected.
		$getObjIds = array();
		foreach( $objectIds as $objectId ) {
			foreach( $expectedReports as $expectedReport ) {
				$expectedBelongsTo = $expectedReport->BelongsTo;
				if( in_array( $objectId, $exclVerObjIds ) || $expectedBelongsTo->Type == 'Object' && $expectedBelongsTo->Id == $objectId ) {
					continue;
				}
				$getObjIds[] = $objectId;
			}
		}

		if ( !$getObjIds ) { // Bail out. Nothing can be requested.
			return null;
		}

		// Call GetObjects to retrieve all changed properties from database.
		require_once BASEDIR .'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $ticket;
		$request->IDs = $getObjIds;
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'MetaData', 'Targets' );
		$response = $this->callService( $testCase, $request, $stepInfo, null );
		if( !$response ) {
			return null;
		}

		foreach( $response->Objects as $respObject ) {

			// Lookup the original/cached object for the object returned through web service response.
			$orgObject = null;
			foreach( $objects as $orgObject ) {
				if( $orgObject->MetaData->BasicMetaData->ID == $respObject->MetaData->BasicMetaData->ID ) {
					break; // Found.
				}
			}

			if (!is_null( $orgObject )) {
				// Simulate the property updates in memory on the orignal/cached object.
				require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
				$flatMD = new stdClass();
				$flatMD->MetaDataValue = $updateProps;
				BizProperty::updateMetaDataTreeWithFlat( $orgObject->MetaData, $flatMD );
			}

			// Validate MetaData and Targets; Compare the original ones with the ones found in service response.
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();

			$phpCompare->initCompare( $changedPropPaths, array() );

			// Validate ExtraMetaData.
			if( !$phpCompare->compareTwoProps( $orgObject->MetaData, $respObject->MetaData ) ) {
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorContext = 'Problem detected in MetaData of GetObjects response after calling MultiSetObjectProperties.';
				$testCase->setResult( 'ERROR', $errorMsg, $errorContext );
				$compareOk = false;
			}
			foreach( $changedPropPaths as $changedPropPath => $expPropValue ) {
				$retPropValue = null;
				eval( '$retPropValue = $respObject->'.$changedPropPath.';' );
				if( $retPropValue != $expPropValue ) {
					$errorMsg = 'The returned '.$changedPropPath.' is set to "'.
						$retPropValue.'" but should be set to "'.$expPropValue.'".';
					$errorContext = 'Problem detected in the MetaData of the GetObjects response after calling MultiSetObjectProperties.';
					$testCase->setResult( 'ERROR', $errorMsg, $errorContext );
					$compareOk = false;
				}
			}

			// Update the original/cached object with response data.
			if ( !is_null( $orgObject ) ) {
				$orgObject->MetaData = $respObject->MetaData;
			}
		}
		return $compareOk;
	}

	/**
	 * Validates the response returned by $operation.
	 *
	 * BuildTest shows an error when the PubChannel is not round-tripped.
	 *
	 * @param TestCase $testCase The test case calling this validation.
	 * @param AdmPubChannel $pubChannel The original AdmPubChannel.
	 * @param AdmPubChannel|null $responsePubChan Response returned by $operation
	 * @param string $operation The service name to be validated, possible values: 'CreatePubChannels', 'ModifyPubChannels', 'GetPubChannels'
	 */
	public function validateAdmPubChannel( $testCase, $pubChannel, $responsePubChan, $operation )
	{
		if( is_null( $responsePubChan )) {
			$testCase->setResult( 'ERROR',  'Invalid response returned.',
				'No response found for the ['.$operation.'] service request.' );
		} else {
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(
				'AdmPubChannel->PublishSystemId' => true,
			) );
			if( !$phpCompare->compareTwoObjects( $pubChannel, $responsePubChan ) ){
				$testCase->setResult( 'ERROR', implode( PHP_EOL, $phpCompare->getErrors() ), 'Error occurred in '.$operation.' response.');
			}
			LogHandler::Log( 'AdmPubChannels', 'INFO', 'Completed validating '.$operation.' response.' );
		}
	}

	/**
	 * Retrieves the test PubChannel from DB by calling GetPubChannels admin web service.
	 *
	 * @param TestCase $testCase The test case doing the modify request.
	 * @param string $ticket The ticket to use when retrieving the AdmPubChannel.
	 * @param int $publicationId The publication id of the pub channel.
	 * @param int $pubChannelId The publication channel object id.
	 * @param string $stepInfo The optional step info.
	 * @return AdmPubChannel|null The requested AdmPubChannel, or null when it could not be retrieved.
	 */
	public function getAdmPubChannel( $testCase, $ticket, $publicationId, $pubChannelId, $stepInfo = null )
	{
		$responseChan = null;

		// Retrieve the test PubChannel from the DB.
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
		$request = new AdmGetPubChannelsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->PubChannelIds = array( $pubChannelId );

		$stepInfo = $stepInfo ? $stepInfo : 'Testing on GetPubChannels web service.';
		$response = $this->callService( $testCase, $request, $stepInfo );

		// Error when the returned pubChannel is not the same as the one we already have.
		if( !is_null( $response ) ) {
			$responseChan = isset( $response->PubChannels[0] ) ? $response->PubChannels[0] : null;
			if( $responseChan ) {
				require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
				$sortedExtraMetaData = BizAdmProperty::sortCustomProperties( $responseChan->ExtraMetaData );
				if( !is_null( $sortedExtraMetaData ) ) {
					$responseChan->ExtraMetaData = BizAdmProperty::sortCustomProperties( $sortedExtraMetaData );
				}
			}
		}
		return $responseChan;
	}

	/**
	 * Updates an AdmPubChannel object on the server.
	 *
	 * Validates afterwards if the AdmPubChannel was correctly updated.
	 *
	 * @param TestCase $testCase The test case doing the modify request.
	 * @param string $ticket The ticket to use for the modification request.
	 * @param int $publicationId The publication id of the pub channel.
	 * @param AdmPubChannel $pubChannel The updated publication channel object.
	 * @param string $stepInfo The optional step info.
	 */
	public function modifyAdmPubChannel( $testCase, $ticket, $publicationId, &$pubChannel, $stepInfo = null )
	{
		// Update the DB with the modified test PubChannel.
		require_once BASEDIR.'/server/services/adm/AdmModifyPubChannelsService.class.php';
		$request = new AdmModifyPubChannelsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->PubChannels = array( $pubChannel );

		$stepInfo = $stepInfo ? $stepInfo : 'Testing on ModifyPubChannels web service.';
		$response = $this->callService( $testCase, $request, $stepInfo );

		// Error when the returned pubChannel is not the same as the modified one.
		if( !is_null( $response ) ) {
			$responseChan = isset( $response->PubChannels[0] ) ? $response->PubChannels[0] : null;
			// Copy the read-only props to avoid validation errors.
			if( $responseChan ) {
				$pubChannel->DirectPublish = $responseChan->DirectPublish;
				$pubChannel->SupportsForms = $responseChan->SupportsForms;
				$pubChannel->SupportsCropping = $responseChan->SupportsCropping;
			}
			$this->validateAdmPubChannel( $testCase, $pubChannel, $responseChan, 'ModifyPubChannels' );
			$pubChannel = $responseChan;
		} else {
			$testCase->setResult( 'ERROR',  'No response returned.',
				'No response found for [ModifyPubChannels] service request.' );
		}
	}

	/**
	 * Creates a new User.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @return AdmCreateUsersResponse|null
	 * @throws BizException
	 */
	public function createNewUser( TestCase $testCase, $ticket )
	{
		$newUser = null;
		try {
			require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';

			$user = $this->buildUser();
			$request = new AdmCreateUsersRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->Users = array( $user );

			$stepInfo = 'Create new User.';
			$response = $this->callService( $testCase, $request, $stepInfo );
			if( $response && count($response->Users) ) {
				$newUser = $response->Users[0];
			}

			if ( !$response instanceof AdmCreateUsersResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not create User' );
			}
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		return $newUser;
	}

	/**
	 * Deletes an Issue.
	 *
	 * @since 9.0.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @param integer $userId
	 * @return AdmDeleteIssuesResponse|null
	 * @throws BizException
	 */
	public function removeUser( TestCase $testCase, $ticket, $userId )
	{
		try {
			require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
			$request = new AdmDeleteUsersRequest();
			$request->Ticket = $ticket;
			$request->UserIds = array( $userId );

			$stepInfo = 'Remove User.';
			$response = $this->callService( $testCase, $request, $stepInfo );

			if ( !$response instanceof AdmDeleteUsersResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not remove User' );
			}
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
			throw ($e);
		}
		return $response;
	}

	/**
	 * Build a valid and fully filled in AdmUser object in memory to use as basis for testing user services.
	 * @return AdmUser object.
	 */
	private function buildUser()
	{
		$this->postfix += 1;  // avoid duplicate names when many created within the same second
		$user = new AdmUser();
		$user->Name				= 'User_'. date('dmy_his').'#'.$this->postfix;
		$user->Password			= 'ww';
		$user->EmailAddress		= $user->Name.'@woodwing.com';
		$user->Deactivated 		= false;
		$user->FixedPassword 	= false;
		$user->EmailUser		= true;
		$user->EmailGroup		= true;
		$user->ValidFrom		= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d') , date('Y')));
		$user->ValidTill		= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+90, date('Y')));
		$user->TrackChangesColor= '00FFFF'; // cyan
		$user->PasswordExpired	= 0;
		$user->Language			= 'nlNL';
		$user->Organization		= 'WoodWing';
		$user->Location			= 'Zaandam';
		return $user;
	}

	/**
	 * The order of some data items under the Object data structure are not preserved in the DB.
	 * So after a round-trip through DB (e.g. SaveObjects followed by GetObjects) some data
	 * might appear in different order. This function puts all data in a fixed order, so that,
	 * after calling, the whole Object can be compared with another Object.
	 *
	 * @param Object $object
	 */
	public function sortObjectDataForCompare( /** @noinspection PhpLanguageLevelInspection */ Object $object )
	{
		if( $object->Placements ) {
			$this->sortPlacementsForCompare( $object->Placements );
		}
		if( $object->Relations ) {
			$this->sortObjectRelationsForCompare( $object->Relations );
		}
	}

	/**
	 * Sorts Object->Relations structure. See {@link:sortObjectDataForCompare()} for more info.
	 *
	 * @param Relation[] $relations
	 */
	public function sortObjectRelationsForCompare( array &$relations )
	{
		// Sort the relations. For that we compose a special temporary sort key whereby
		// we prefix the digits with leading zeros. Note that max int 64 bit = '9223372036854775807' 
		// which has 19 positions, so let's take 20 digits to compose our IDs.
		$sortRelations = array();
		foreach( $relations as $relation ) {
			if( $relation->Placements ) {
				$this->sortPlacementsForCompare( $relation->Placements );
			}
			$sortKey = sprintf( '%020d_%020d_%s', $relation->Parent, $relation->Child, $relation->Type );
			$sortRelations[$sortKey] = $relation;

			// Sort Editions
			if( $relation->Targets ) foreach( $relation->Targets as $relationTarget ) {
				if( $relationTarget->Editions ) {
					$this->sortEditionsForCompare( $relationTarget->Editions );
				}
			}
		}
		ksort( $sortRelations );
		$relations = array_values( $sortRelations ); // remove the temp keys
	}

	/**
	 * Sorts Object->Placements and Object->Relation->Placement structures. See {@link:sortObjectDataForCompare()} for more info.
	 *
	 * @param Placement[] $placements
	 */
	public function sortPlacementsForCompare( array &$placements )
	{
		foreach( $placements as $placement ) {
			sort( $placement->InDesignArticleIds );
			
			// MSSQL returns more precision than we save:
			$placement->Left = round( $placement->Left, 3 ); 
			$placement->Top = round( $placement->Top, 3 ); 
			$placement->Width = round( $placement->Width, 3 ); 
			$placement->Height = round( $placement->Height, 3 ); 
			$placement->Overset = round( $placement->Overset, 3 );
		}
	}

	/**
	 * Sorts Editions.
	 *
	 * @param Edition[] $editions
	 */
	private function sortEditionsForCompare( &$editions )
	{
		if( $editions ) {
			usort( $editions, function( Edition $editionA, Edition $editionB ) {
				return $editionA->Id < $editionB->Id ? -1 : 1; // assumed that ids are never equal
			} );
		}
	}

	/**
	 * Creates a new Server Job via jobindex.php.
	 *
	 * Typically needed for recurring Server Job.
	 *
	 * @since 10.1.7
	 * @param TestCase $testCase
	 * @param string $serverJobName
	 * @return bool
	 */
	public function callCreateServerJob( TestCase $testCase, $serverJobName )
	{
		$result = true;
		try {
			require_once 'Zend/Http/Client.php';
			$url = LOCALURL_ROOT.INETROOT.'/jobindex.php';
			$client = new Zend_Http_Client();
			$client->setUri( $url );
			$client->setParameterGet( 'createrecurringjob', $serverJobName );
			$client->setConfig( array( 'timeout' => 5 ) );
			$response = $client->request( Zend_Http_Client::GET );

			if( !$response->isSuccessful() ) {
				$testCase->setResult( 'ERROR', 'Failed calling jobindex.php to create a new Server Job: '.$response->getHeadersAsString( true, '<br/>' ) );
				$result = false;
			}
		} catch ( Zend_Http_Client_Exception $e ) {
			$testCase->setResult( 'ERROR', 'Failed calling jobindex.php to create a new Server Job: '.$e->getMessage() );
			$result = false;
		}
		return $result;
	}

	/**
	 * Run the job scheduler by calling the jobindex.php.
	 *
	 * @since 10.1.7
	 * @param TestCase $testCase
	 * @param int $maxExecTime The max execution time of jobindex.php in seconds.
	 * @param int $maxJobProcesses The maximum number of jobs that the job processor is allowed to pick up at any one time.
	 * @return bool
	 */
	public function callRunServerJobs( TestCase $testCase, $maxExecTime = 5, $maxJobProcesses = 3 )
	{
		$result = true;
		try {
			require_once 'Zend/Http/Client.php';
			$url = LOCALURL_ROOT.INETROOT.'/jobindex.php';
			$client = new Zend_Http_Client();
			$client->setUri( $url );
			$client->setParameterGet( 'maxexectime', $maxExecTime );
			$client->setParameterGet( 'maxjobprocesses', $maxJobProcesses );
			$client->setConfig( array( 'timeout' => $maxExecTime + 30 ) ); // before breaking connection, let's give the job processor 30s more to complete
			$response = $client->request( Zend_Http_Client::GET );

			if( !$response->isSuccessful() ) {
				$testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$response->getHeadersAsString( true, '<br/>' ) );
				$result = false;
			}
		} catch ( Zend_Http_Client_Exception $e ) {
			$testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$e->getMessage() );
			$result = false;
		}

		sleep( 10 ); // To make sure that the server job is really ended.
		return $result;
	}
}