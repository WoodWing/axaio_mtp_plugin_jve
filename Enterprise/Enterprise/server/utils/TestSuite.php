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
	/** @var callable $requestComposer  */
	private $requestComposer = null;

	/**
	 * Initializes this test utils class.
	 *
	 * @since 10.0.0
	 * @param string|null $protocol The used protocol for service calls. (Options: SOAP or JSON.) If null a regular service call is made.
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
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$isLocalRootFolder = FolderUtils::isLocalRootFolder( $filePath );
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
	 * Setup a callback function that is called just before calling the webservice.
	 *
	 * This enables the caller to manupulate the request that could be composed by other functions.
	 * The callback function accepts one parameter, which is the request.
	 *
	 * @since 10.2.0
	 * @param callable $callback
	 */
	public function setRequestComposer( $callback )
	{
		$this->requestComposer = $callback;
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
	 * @param string $providerShort Abbreviated name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
	 * @param string $providerFull Full name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
	 * @return mixed A corresponding response object when service was successful or NULL on error.
	 */
	public function callService( TestCase $testCase, $request, $stepInfo, $expectedErrorCodeOrMsgKey = null, 
		$throwException = false, $providerShort = '', $providerFull = '' )
	{
		// Copy and clear request composer since it can throw exception.
		$requestComposer = $this->requestComposer;
		$this->requestComposer = null; // reset (has to be set per function call)

		// Let caller overrule request composition.
		if( $requestComposer ) {
			call_user_func( $requestComposer, $request );
		}

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
				$response = $this->executeSoap( $request, $expectedSCode, $providerShort, $providerFull );
			} elseif( $this->protocol == 'JSON' ) {
				$response = $this->executeJson( $request, $expectedSCode, $providerShort, $providerFull );
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
					$message = 'Test: '.$stepInfo.PHP_EOL.
							'The service response was unexpected: Success!'.PHP_EOL.
							'Expected response: '.$expectedErrorCodeOrMsgKey.'.';
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
					$message = 'Test:'.$stepInfo.PHP_EOL.
								'The service response was unexpected: '.$e->getMessage().
								' (Detail: '.$e->getDetail().')'.PHP_EOL.
								'Expected response: '.$expectedErrorCodeOrMsgKey.'.';
					if( $throwException ) {
						$testCase->throwError( $message );
					} else {
						$testCase->setResult( 'ERROR', $message, '' );
					}
				}				
			} else { // No error expected
				$message =  'Test: '.$stepInfo.PHP_EOL.
							'The service response was unexpected: '.$e->getMessage().
							' (Detail: '.$e->getDetail().')'.PHP_EOL.
							'Expected response: Success!';
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
	 * @param string $providerShort Abbreviated name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
	 * @param string $providerFull Full name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
	 * @return object Response object.
	 * @throws BizException when the web service failed.
	 */
	private function executeSoap( $request, $expectedSCode, $providerShort, $providerFull )
	{
		$requestClass = get_class( $request ); // e.g. 'WflDeleteObjectsRequest' or 'CsPubPublishArticleRequest'
		$webInterface = substr( $requestClass, strlen($providerShort), 3 );
		$funtionNameLen = strlen($requestClass) - strlen($providerShort) - strlen($webInterface) - strlen('Request');
		$functionName = substr( $requestClass, strlen($providerShort) + strlen($webInterface), $funtionNameLen );

		if( $providerShort ) { // plugin
			require_once BASEDIR."/config/plugins/{$providerFull}/protocols/soap/".strtolower($webInterface).'/Client.php';
			$clientClass = "{$providerFull}_Protocols_Soap_{$webInterface}_Client";
		} else { // server
			require_once BASEDIR.'/server/protocols/soap/'.$webInterface.'Client.php';
			$clientClass = 'WW_SOAP_'.$webInterface.'Client';
		}
		$options = array(
			'transfer' => 'HTTP',
			'protocol' => 'SOAP',
		);
		if( $expectedSCode ) {
			$options['expectedError'] = $expectedSCode;
		}
		try {
			$client = new $clientClass( $options );
			$response = $client->$functionName( $request );
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
	 * @param string $providerShort Abbreviated name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
	 * @param string $providerFull Full name of provider that has implemented the Web Service. EMPTY for the core Enterprise Server, or set for server plugin.
	 * @return object Response object.
	 * @throws BizException when the web service failed.
	 */
	private function executeJson( $request, $expectedSCode, $providerShort, $providerFull )
	{
		$requestClass = get_class( $request ); // e.g. 'WflDeleteObjectsRequest' or 'CsPubPublishArticleRequest'
		$webInterface = substr( $requestClass, strlen($providerShort), 3 );
		$funtionNameLen = strlen($requestClass) - strlen($providerShort) - strlen($webInterface) - strlen('Request');
		$functionName = substr( $requestClass, strlen($providerShort) + strlen($webInterface), $funtionNameLen );

		if( $providerShort ) { // plugin
			require_once BASEDIR."/config/plugins/{$providerFull}/protocols/json/".strtolower($webInterface).'/Client.php';
			$clientClass = "{$providerFull}_Protocols_Json_{$webInterface}_Client";
		} else { // server
			require_once BASEDIR.'/server/protocols/json/'.$webInterface.'Client.php';
			$clientClass = 'WW_JSON_'.$webInterface.'Client';
		}
		$options = array();
		if( $expectedSCode ) {
			$options['expectedError'] = $expectedSCode;
		}
		try {
			$client = new $clientClass( '', $options );
			$response = $client->$functionName( $request );
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
	 * Creates a new Edition.
	 *
	 * @since 10.4.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @param int $pubId
	 * @param int $pubChannelId
	 * @param int $issueId
	 * @param AdmEdition $edition
	 * @return AdmEdition
	 * @throws BizException
	 */
	public function createNewEdition( TestCase $testCase, $ticket, $pubId, $pubChannelId, $issueId, AdmEdition $edition )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';
		$request = new AdmCreateEditionsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationId = $pubId;
		$request->PubChannelId = $pubChannelId;
		$request->IssueId = $issueId;
		$request->Editions = array( $edition );

		/** @var AdmCreateEditionsResponse $response */
		$response = $this->callService( $testCase, $request, 'Create Edition.' );
		$testCase->assertInstanceOf( 'AdmCreateEditionsResponse', $response );

		return $response->Editions[0];
	}

	/**
	 * Deletes an Edition.
	 *
	 * @since 10.4.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket
	 * @param int $publicationId
	 * @param int $pubChannelId
	 * @param int $issueId
	 * @param int $editionId
	 * @throws BizException
	 */
	public function removeEdition( TestCase $testCase, $ticket, $publicationId, $pubChannelId, $issueId, $editionId )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';
		$request = new AdmDeleteEditionsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $publicationId;
		$request->PubChannelId = $pubChannelId;
		$request->IssueId = $issueId;
		$request->EditionIds = array( $editionId );

		/** @var AdmDeleteEditionsResponse $response */
		$response = $this->callService( $testCase, $request, 'Remove Edition.' );
		$testCase->assertInstanceOf( 'AdmDeleteEditionsResponse', $response );
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
		require_once BASEDIR.'/server/dbmodel/Reader.class.php';
		require_once BASEDIR.'/server/dbmodel/Factory.class.php';

		$dbTablesWithoutAutoIncrement = array();
		$dbTables = array();
		$definitions = WW_DbModel_Factory::createModels();
		foreach( $definitions as $definition ) {
			$reader = new WW_DbModel_Reader( $definition );
			$dbTables = array_merge( $dbTables, $reader->listTables() );
			$dbTablesWithoutAutoIncrement = array_merge( $dbTablesWithoutAutoIncrement, $definition->getTablesWithoutAutoIncrement() );
		}

		$dbTablesHavingAutoIncrement = array();
		foreach( $dbTables as $dbTable ) {
			if( !in_array( $dbTable['name'], $dbTablesWithoutAutoIncrement )) {
				$dbTablesHavingAutoIncrement[] = $dbTable['name'];
			}
		}
		return $dbTablesHavingAutoIncrement;
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
	 * @param PublicationInfo $pubInfo
	 * @param string $type Object type (e.g. Article)
	 * @return State|null Status info, or null on failure.
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
	 * @param Object[] $objects List of objects to be created via CreateObjects service call.
	 * @param bool $lock Whether or not to lock the object
	 * @param string|null $stepInfo Optional step info in case an error occurs
	 * @param string|NULL $expectedErrorCodeOrMsgKey The exception message from BizException->getMessage(), Null when no error is expected.
	 * @return WflCreateObjectsResponse|null
	 */
	public function callCreateObjectService( TestCase $testCase, $ticket, $objects, $lock = false, $stepInfo = null, $expectedErrorCodeOrMsgKey = null )
	{
		// Create the article objects at Enterprise DB
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket	= $ticket;
		$request->Lock		= $lock;
		$request->Objects	= $objects;

		return self::callService( $testCase, $request, $stepInfo ? $stepInfo : 'Create object', $expectedErrorCodeOrMsgKey );
	}

	/**
	 * Save new version of workflow objects into DB / FileStore.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase
	 * @param string $ticket
	 * @param Object[] $objects Workflow objects to save.
	 * @param bool $unlock Whether or not to unlock the object
	 * @param string|null $stepInfo Optional step info in case an error occurs
	 * @param string|NULL $expectedErrorCodeOrMsgKey The exception message from BizException->getMessage(), Null when no error is expected.
	 * @return WflSaveObjectsResponse|null
	 */
	public function saveObjects( TestCase $testCase, $ticket, $objects, $unlock, $stepInfo = null, $expectedErrorCodeOrMsgKey = null )
	{
		// Create the article objects at Enterprise DB
		require_once BASEDIR . '/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = $unlock;
		$request->Objects = $objects;

		return self::callService( $testCase, $request, $stepInfo ? $stepInfo : 'Save object', $expectedErrorCodeOrMsgKey );
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
	 * Creates a Publication and returns the id.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function
	 * @param string $ticket The user's session ticket.
	 * @param AdmPublication|null $publication The publication to be created.
	 * @return integer The id of the newly created publication.
	 */
	public function createNewPublication( TestCase $testCase, $ticket, $publication = null )
	{
		if( !$publication ) {
			$publication = new AdmPublication();
			$publication->Name = 'AdmPublication_T_'.date_format( date_create(), 'dmy_his_u' );
		}
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationsService.class.php';
		$request = new AdmCreatePublicationsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->Publications = array( $publication );
		$response = $this->callService( $testCase, $request, 'Create a publication.' );

		return $response->Publications[0]->Id;
	}

	/**
	 * Deletes one or more publications.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function
	 * @param string $ticket The user's session ticket.
	 * @param array $publicationIds
	 */
	public function deletePublications( TestCase $testCase, $ticket, array $publicationIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
		$request = new AdmDeletePublicationsRequest();
		$request->Ticket = $ticket;
		$request->PublicationIds = $publicationIds;
		$this->callService( $testCase, $request, 'Delete one or more publications.' );
	}

	/**
	 * Creates a Section and returns the id.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer $publicationId The publication id.
	 * @param integer $issueId The issue id.
	 * @param AdmSection|null $section The section to be created. If null, a generic section will be created.
	 * @return integer The id of the created section.
	 */
	public function createNewSection( TestCase $testCase, $ticket, $publicationId, $issueId, $section = null )
	{
		if( !$section ) {
			$section = new AdmSection();
			$section->Name = 'AdmSection_T_'.date_format( date_create(), 'dmy_his_u' );
		}

		require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';
		$request = new AdmCreateSectionsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->IssueId = $issueId;
		$request->Sections = array( $section );
		$environment = ( $issueId ) ? 'issue' : 'brand';
		$stepInfo = 'Create a section for a ' . $environment . '.';
		$response = $this->callService( $testCase, $request, $stepInfo );

		return $response->Sections[0]->Id;
	}

	/**
	 * Deletes one or more sections.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer $publicationId The publication id.
	 * @param integer|null $issueId The issue id.
	 * @param array $sectionIds A list of section ids to be deleted
	 */
	public function deleteSections( TestCase $testCase, $ticket, $publicationId, $issueId, array $sectionIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
		$request = new AdmDeleteSectionsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $publicationId;
		$request->IssueId = $issueId;
		$request->SectionIds = $sectionIds;
		$this->callService( $testCase, $request, 'Delete one or more sections.' );
	}

	/**
	 * Create a new access profile.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param AdmAccessProfile|null $accessProfile The access profile to be created. If null, a generic access profile is created.
	 * @return AdmAccessProfile The created access profile.
	 */
	public function createNewAccessProfile( TestCase $testCase, $ticket, $accessProfile = null )
	{
		if( !$accessProfile ) {
			$accessProfile = new AdmAccessProfile();
			$accessProfile->Name = 'AccessProfile_T_'.date_format( date_create(), 'dmy_his_u' );
			$accessProfile->Description = 'An access profile created for testing.';
		}
		require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';
		$request = new AdmCreateAccessProfilesRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->AccessProfiles = array( $accessProfile );
		$response = $this->callService( $testCase, $request, 'Create an access profile.' );

		return $response->AccessProfiles[0];
	}

	/**
	 * Delete one or more access profiles.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param array $accessProfileIds List of access profile ids to be deleted.
	 */
	public function deleteAccessProfiles( TestCase $testCase, $ticket, array $accessProfileIds)
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
		$request = new AdmDeleteAccessProfilesRequest();
		$request->Ticket = $ticket;
		$request->AccessProfileIds = $accessProfileIds;
		$this->callService( $testCase, $request, 'Delete one or more access profiles.' );
	}

	/**
	 * Adds or removes AdmProfileFeatures to an access profile.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param AdmAccessProfile $accessProfile The access profile whose features will be modified.
	 * @param array $featureNames A list of (unique) feature names.
	 * @param boolean $doRemove If true, features are removed. If false, features are added.
	 */
	public function modifyProfileFeaturesOfProfile( TestCase $testCase, $ticket, AdmAccessProfile $accessProfile, array $featureNames, $doRemove )
	{
		$profileFeatures = array();
		if( $featureNames ) foreach( $featureNames as $featureName ) {
			$profileFeature = new AdmProfileFeature();
			$profileFeature->Value = ( $doRemove ) ? 'No' : 'Yes';
			$profileFeature->Name = $featureName;
			$profileFeatures[] = $profileFeature;
		}
		$accessProfile->ProfileFeatures = $profileFeatures;

		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';
		$request = new AdmModifyAccessProfilesRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->AccessProfiles = array( $accessProfile );
		$this->callService( $testCase, $request,
			( $doRemove ? 'Removed' : 'Added' ) . ' profile features ' . implode( ', ', $featureNames ) . ' to access profile.' );
	}

	/**
	 * Creates a new status.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer|null $publicationId The publication id.
	 * @param integer $issueId The issue id.
	 * @param AdmStatus|null $status The status to be created. If null, a generic status will be created.
	 * @return integer The id of the newly created status.
	 */
	public function createNewStatus( TestCase $testCase, $ticket, $publicationId, $issueId, $status = null )
	{
		if( !$status ) {
			$status = new AdmStatus();
			$status->Name = 'Status_T_'.date_format( date_create(), 'dmy_his_u' );
			$status->Color = 'A0A0A0';
			$status->Type = 'Article';
		}
		require_once BASEDIR.'/server/services/adm/AdmCreateStatusesService.class.php';
		$request = new AdmCreateStatusesRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $publicationId;
		$request->IssueId = $issueId;
		$request->Statuses = array( $status );
		$response = $this->callService( $testCase, $request, 'Create a status.' );

		return reset( $response->Statuses )->Id;
	}

	/**
	 * Deletes one or more statuses.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param array $statusIds List of status ids to be deleted.
	 */
	public function deleteStatuses( TestCase $testCase, $ticket, array $statusIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';
		$request = new AdmDeleteStatusesRequest();
		$request->Ticket = $ticket;
		$request->StatusIds = $statusIds;
		$this->callService( $testCase, $request, 'Delete one or more statuses.' );
	}

	/**
	 * Creates a new user and returns the id.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase
	 * @param string $ticket The user's session ticket.
	 * @param AdmUser|null $user The user to be created. If null, a generic user will be created.
	 * @return integer|null The id of the newly created user, or null when failed.
	 */
	public function createNewUser( TestCase $testCase, $ticket, $user = null )
	{
		$userId = null;
		if( !$user ) {
			$user = $this->buildUser();
		}

		try {
			require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';

			$request = new AdmCreateUsersRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->Users = array( $user );

			$stepInfo = 'Create new User.';
			$response = $this->callService( $testCase, $request, $stepInfo );

			if ( !$response instanceof AdmCreateUsersResponse ) {
				throw new BizException( 'ERR_ERROR', 'Server', __FUNCTION__.'()', 'Could not create User' );
			}
			$userId = $response->Users[0]->Id;
		}
		catch ( BizException $e ) {
			LogHandler::Log( 'Services', 'ERROR', __CLASS__.'::'.__FUNCTION__.'(): '.$e->__toString() );
		}

		return $userId;
	}

	/**
	 * Deletes one or more users.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module that called this function.
	 * @param string $ticket The user's session ticket.
	 * @param array $userIds The list of user ids to be deleted.
	 */
	public function deleteUsers( TestCase $testCase, $ticket, array $userIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
		$request = new AdmDeleteUsersRequest();
		$request->Ticket = $ticket;
		$request->UserIds = $userIds;
		$this->callService( $testCase, $request, 'Delete one or more users.' );
	}

	/**
	 * Create a new user group and returns the id.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param AdmUserGroup|null $userGroup The user group to be created. If null, a general one will be created.
	 * @return integer The id of the newly created user group.
	 */
	public function createNewUserGroup( TestCase $testCase, $ticket, $userGroup = null )
	{
		if( !$userGroup ) {
			$userGroup = new AdmUserGroup();
			$userGroup->Name = 'UserGroup_T_'.date_format( date_create(), 'dmy_his_u' );
			$userGroup->Admin = false;
			$userGroup->Routing = false;
		}

		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
		$request = new AdmCreateUserGroupsRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->UserGroups = array( $userGroup );
		$response = $this->callService( $testCase, $request, 'Create a new user group.' );

		return $response->UserGroups[0]->Id;
	}

	/**
	 * Deletes one or more user groups.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param array $userGroupIds The list of user group ids to be deleted.
	 */
	public function deleteUserGroups( TestCase $testCase, $ticket, array $userGroupIds )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
		//TODO: Should call DeleteUserGroupsService in the future

		foreach( $userGroupIds as $userGroupId ) {
			BizAdmUser::deleteUserGroup( $userGroupId );
		}
	}

	/**
	 * Add a user to a user group.
	 *
	 * @since 10.4.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer $userId
	 * @param integer $groupId
	 */
	public function createUserMemberships( TestCase $testCase, $ticket, $userId, $groupId )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $ticket;
		$request->GroupId = $groupId;
		$request->UserIds = array( $userId );
		/** @var AdmAddUsersToGroupResponse $response */
		$response = $this->callService( $testCase, $request, 'Create a user membership.' );
		$testCase->assertInstanceOf( 'AdmAddUsersToGroupResponse', $response );
	}

	/**
	 * Remove a user from a user group.
	 *
	 * @since 10.4.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer $userId
	 * @param integer $groupId
	 */
	public function removeUserMemberships( TestCase $testCase, $ticket, $userId, $groupId )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';
		$request = new AdmRemoveUsersFromGroupRequest();
		$request->Ticket = $ticket;
		$request->GroupId = $groupId;
		$request->UserIds = array( $userId );
		/** @var AdmRemoveUsersFromGroupResponse $response */
		$response = $this->callService( $testCase, $request, 'Remove a user membership.' );
		$testCase->assertInstanceOf( 'AdmRemoveUsersFromGroupResponse', $response );
	}

	/**
	 * Create a workflow user group authorization rule and returns the id.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer|null $publicationId The brand id.
	 * @param integer|null $issueId The issue id.
	 * @param integer $userGroupId The user group id.
	 * @param integer $accessProfileId The access profile id.
	 * @param integer|null $sectionId The section id. (optional)
	 * @param integer|null $statusId The status id. (optional)
	 * @return integer user group authorization id.
	 */
	public function createNewWorkflowUserGroupAuthorization(
		TestCase $testCase, $ticket, $publicationId, $issueId, $userGroupId, $accessProfileId, $sectionId, $statusId )
	{
		$wflUGAuth = new AdmWorkflowUserGroupAuthorization();
		$wflUGAuth->AccessProfileId = $accessProfileId;
		$wflUGAuth->UserGroupId = $userGroupId;
		$wflUGAuth->SectionId = $sectionId;
		$wflUGAuth->StatusId = $statusId;

		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmCreateWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $publicationId;
		$request->IssueId = $issueId;
		$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
		$response = $this->callService( $testCase, $request, 'Create a WorkflowUserGroupAuthorization.' );

		return $response->WorkflowUserGroupAuthorizations[0]->Id;
	}

	/**
	 * Deletes one or more workflow user group authorization rules.
	 *
	 * @since 10.2.0
	 * @param TestCase $testCase The test module that calls this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer|null $publicationId The brand id.
	 * @param integer|null $issueId The issue id.
	 * @param integer|null $userGroupId The user group id.
	 * @param array|null $wflUGAuthIds List of workflow user group authorization ids.
	 */
	public function deleteWorkflowUserGroupAuthorizations(
		TestCase $testCase, $ticket, $publicationId, $issueId, $userGroupId, $wflUGAuthIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmDeleteWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $publicationId;
		$request->IssueId = $issueId;
		$request->UserGroupId = $userGroupId;
		$request->WorkflowUserGroupAuthorizationIds = $wflUGAuthIds;
		$this->callService( $testCase, $request, 'Delete one or more workflow user group authorization rules.' );
	}

	/**
	 * Create an admin user group authorization.
	 *
	 * @since 10.4.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer $publicationId The brand id.
	 * @param integer $userGroupId The user group id.
	 */
	public function createNewPublicationAdminAuthorization( TestCase $testCase, $ticket, $publicationId, $userGroupId )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';
		$request = new AdmCreatePublicationAdminAuthorizationsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $publicationId;
		$request->UserGroupIds = array( $userGroupId );

		/** @var AdmCreatePublicationAdminAuthorizationsResponse $response */
		$response = $this->callService( $testCase, $request, 'Create a PublicationAdminAuthorization.' );
		$testCase->assertInstanceOf( 'AdmCreatePublicationAdminAuthorizationsResponse', $response );
	}

	/**
	 * Remove an admin user group authorization.
	 *
	 * @since 10.4.0
	 * @param TestCase $testCase The test module calling this function.
	 * @param string $ticket The user's session ticket.
	 * @param integer $publicationId The brand id.
	 * @param integer $userGroupId The user group id.
	 */
	public function removePublicationAdminAuthorization( TestCase $testCase, $ticket, $publicationId, $userGroupId )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationAdminAuthorizationsService.class.php';
		$request = new AdmDeletePublicationAdminAuthorizationsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $publicationId;
		$request->UserGroupIds = array( $userGroupId );

		/** @var AdmDeletePublicationAdminAuthorizationsResponse $response */
		$response = $this->callService( $testCase, $request, 'Delete a PublicationAdminAuthorization.' );
		$testCase->assertInstanceOf( 'AdmDeletePublicationAdminAuthorizationsResponse', $response );
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
		// For zendframework v2.5.3: Make sure that the Execution timeout (CURLOPT_TIMEOUT) is set in the 'curloptions' key.
		// If we would simply call $client->setOptions( 'timeout' => $operationTimeout ) the Curl Adapter would apply the
		// same value for 'CURLOPT_TIMEOUT' and 'CURLOPT_CONNECTTIMEOUT' which is not wanted.
		$result = true;
		try {
			$client = new Zend\Http\Client();
			$client->setUri( LOCALURL_ROOT.INETROOT.'/jobindex.php' );
			$client->setMethod( Zend\Http\Request::METHOD_GET );
			$client->setParameterGet( array(
				'createrecurringjob' => $serverJobName,
				//'XDEBUG_SESSION_START' => 'PHPSTORM',
				// L> To debug a job, uncomment the above and clear the TESTSUITE['SoapUrlDebugParams'] option.
			) );
			$client->setOptions( array(
				'timeout' => null, // trick to allow overruling CURLOPT_TIMEOUT / CURLOPT_CONNECTTIMEOUT
				'adapter' => 'Zend\Http\Client\Adapter\Curl',
				'curloptions' => array(
					CURLOPT_CONNECTTIMEOUT => 5,
					CURLOPT_TIMEOUT => 60
				) ) );
			$client->send();
			$response = $client->getResponse();
			if( !$response->isSuccess() ) {
				$testCase->setResult( 'ERROR', 'Failed calling jobindex.php to create a new Server Job: '.$response->getReasonPhrase().PHP_EOL );
				$result = false;
			}
		} catch( Exception $e ) {
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
	 * @param int $maxProcessJobs Max number of jobs to execute.
	 * @return bool
	 */
	public function callRunServerJobs( TestCase $testCase, $maxProcessJobs = 1 )
	{
		$result = true;
		try {
			// For zendframework v2.5.3: Make sure that the Execution timeout (CURLOPT_TIMEOUT) is set in the 'curloptions' key.
			// If we would simply call $client->setOptions( 'timeout' => $operationTimeout ) the Curl Adapter would apply the
			// same value for 'CURLOPT_TIMEOUT' and 'CURLOPT_CONNECTTIMEOUT' which is not wanted.

			$maxExecTime = 60;
			$client = new Zend\Http\Client();
			$client->setUri( LOCALURL_ROOT.INETROOT.'/jobindex.php' );
			$client->setMethod( Zend\Http\Request::METHOD_GET );
			$client->setParameterGet( array(
				'maxjobprocesses' => 10,
				'maxexectime' => $maxExecTime,
				'processmaxjobs' => $maxProcessJobs,
				//'XDEBUG_SESSION_START' => 'PHPSTORM',
				// L> To debug a job, uncomment the above and clear the TESTSUITE['SoapUrlDebugParams'] option.
			) );
			$client->setOptions( array(
				'timeout' => null, // trick to allow overruling CURLOPT_TIMEOUT / CURLOPT_CONNECTTIMEOUT
				'adapter' => 'Zend\Http\Client\Adapter\Curl',
				'curloptions' => array(
					CURLOPT_CONNECTTIMEOUT => 5,
					CURLOPT_TIMEOUT => $maxExecTime + 2 // Add two seconds to allow the jobindex to finish before curl finishes.
				) ) );
			$client->send();
			$response = $client->getResponse();
			if( !$response->isSuccess() ) {
				$testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$response->getReasonPhrase().PHP_EOL );
				$result = false;
			}
		} catch( Exception $e ) {
			$testCase->setResult( 'ERROR', 'Failed calling jobindex.php: '.$e->getMessage() );
			$result = false;
		}

		return $result;
	}

	/**
	 * Empty the Enterprise Server jobs in the job queue created by a test.
	 *
	 * In case of error in the BuildTest, the server jobs cannot be processed,
	 * they are left in the queue. This function clears all the jobs in the queue
	 * to make sure that the next run of the test, it starts with a cleared queue.
	 *
	 * This function can be called before and after the test.
	 * @since 10.1.8
	 */
	public function emptyServerJobsQueue()
	{
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		// Clear all the jobs created in the job queue.
		$bizServerJob = new BizServerJob();
		$jobs = $bizServerJob->listJobs();
		if ( count( $jobs ) > 0 ) {
			foreach( array_keys( $jobs ) as $jobId ) {
				$bizServerJob->deleteJob( $jobId );
			}
		}
	}

}