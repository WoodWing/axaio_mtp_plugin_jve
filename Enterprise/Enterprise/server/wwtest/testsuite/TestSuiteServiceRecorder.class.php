<?php

/**
 * Enterprise Service Recorder to ease creating Test Suite modules (for automatic testing).
 *
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Supported features:
 * - Unicode support for string data. => chr() is used to represent multibyte chars.
 * - File attachments. => Attachments are stored at (and loaded from) TestData folder of recorded test script.
 * - Error report when recorded service does not match replayed service.
 * - Detection and handling of thrown exceptions. When exception recorded, it is expected when replayed.
 * - Replaying a recorded session is not recorded again (suppressed for convenience).
 * - Service calls are listed at test methods (at header) as a scenario (to enrich manually with logic steps).
 * - Multi-threaded applications (like CS). => DB semaphore serializes services fired in parallel.
 * - Nested service calls. => When core/plugin runs service in service, inner services are not recorded.
 */
class TestSuiteServiceRecorder
{
	private $settings = null;
	private $fileName = null;
	private $testFolder = null;
	private $serviceName = null;
	private $callsCountStr = '';
	private static $semaphoreId = null;
	
	/**
	 * Initializes the recorder. Creates new test module based on the TestSuiteTemplate.php file 
	 * when first time recording. Uses static member variable to avoid recording service calls
	 * service calls. Uses semaphore to serialize services that were fired in parallel. See func body.
	 *
	 * @param string $serviceName
	 * @param string $recordModule
	 * @param string $recordFolder
	 * @throws BizException
	 */
	public function __construct( $serviceName, $recordModule, $recordFolder )
	{
		// When client is calling service A, the core server or a server plug-in could call
		// the help of service B. In that case, we do NOT want to record service B, since that
		// would disturb playing recorded services (e.g. service B will be called twice).
		if( self::$semaphoreId ) {
			return;
		}
		
		// When client fires services in parallel (e.g. multi threaded application, such as CS),
		// they need to be recorded in one TestCase file. To avoid race conditions, we use a
		// semaphore to let one write at the same time. Doing so, we wait for the first service
		// to complete before running the second service. In fact, this serializes services, that
		// were originally fired in parallel. Assuming this has no side effects.
		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
		$semaName = 'TestSuite_ServiceRecorder';
		$bizSemaphore = new BizSemaphore();
		$bizSemaphore->setLifeTime( 60 ); // 1 minute
		$attempts = array( // miliseconds
			// Wait 5 secs, while trying to squeeze in, between other service calls.
			// E.g. CS could fire 5 QueryObjects. Serializing those, needs 5 sec wait total.
			1, 2, 5, 10, 15, 25, 50, 125, 250, 500, // = one sec wait
			1, 2, 5, 10, 15, 25, 50, 125, 250, 500, // = one sec wait
			1, 2, 5, 10, 15, 25, 50, 125, 250, 500, // = one sec wait
			1, 2, 5, 10, 15, 25, 50, 125, 250, 500, // = one sec wait
			1, 2, 5, 10, 15, 25, 50, 125, 250, 500, // = one sec wait
		);
		$bizSemaphore->setAttempts( $attempts );
		self::$semaphoreId = $bizSemaphore->createSemaphore( $semaName );
		if( !self::$semaphoreId ) {
			$otherUser = BizSemaphore::getSemaphoreUser( $semaName );
			$detail = 'After 60 seconds wait, user \''.$otherUser.'\' is still busy (which blocks the service recording).';
			throw new BizException( null, 'Server', $detail, 'Could not record the service.' );
		}

		// Init class members.
		$basePath = BASEDIR.'/server/wwtest/testsuite/'.$recordFolder;

		// Need to get the TestCase scripts number( to be used in Prio Num ) before creating a new TestCase script.
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$testCaseFiles = FolderUtils::getFilesInFolderRecursive( $basePath, array( 'TestSuite.php' ));
		// Make sure only xxx_TestCase.php files are taken into account.
		$totalTestCaseFiles = 0;
		foreach( $testCaseFiles as $testCaseFile ) {
			list( , $testCaseExtensionName ) = explode( '_', $testCaseFile );
			if( $testCaseExtensionName == 'TestCase.php' ) {
				$totalTestCaseFiles++; // Used for prio number later.
			}
		}

		$this->fileName = $basePath.$recordModule.'_TestCase.php';
		$this->testFolder = $basePath.$recordModule.'_TestData';
		$className = 'WW_TestSuite_'.str_replace('/','_',$recordFolder).$recordModule.'_TestCase';
		$this->serviceName = $serviceName;

		// Read template, fill in the variables and write all to new TestSuite file.
		if( !file_exists( $this->fileName ) ) {
			$templateData = file_get_contents( BASEDIR.'/server/wwtest/testsuite/TestSuiteTemplate.php' );
			$templateData = str_replace( '<!--TESTSUITE_CLASSNAME-->', $className, $templateData );
			$templateData = str_replace( '<!--RECORDING_NAME-->', $recordModule, $templateData );
			$serverVersion = explode( '.', SERVERVERSION );
			$serverVersion = $serverVersion[0].'.'.$serverVersion[1]; // major.minor
			$templateData = str_replace( '<!--SERVER_VERSION-->', $serverVersion, $templateData );

			require_once BASEDIR.'/server/wwtest/testsuite/'.$recordFolder.'TestSuite.php';
			$testAreaClass = 'WW_TestSuite_'.str_replace('/','_',$recordFolder).'TestSuite'; // WW_TestSuite_BuildTest2_TestSet001_Features_TestSuite
			$testAreaObj = new $testAreaClass;
			$areaPrioNum = $testAreaObj->getPrio();

			$prioNum = $totalTestCaseFiles + 1;
			$totalTestCasesAllowed = 1000;
			if( $prioNum > $totalTestCasesAllowed ) {
				$this->releaseSemaphore();
				throw new BizException( 'ERR_ERROR', 'Server',
					'TestCase is full. Reached TestCase recording limit "1000".',
					'Could not record the service.' );
			}
			$templateData = str_replace( '<!--PRIO_NUMBER-->', $prioNum, $templateData );

			// InitialAutoIncrement
			$initialAutoIncrement = 100000000;
			$totalRecordsAllowed  = 100;
			$perTestAreaSpace = ( $totalTestCasesAllowed /*test cases*/ ) * ( SERVICE_RECORDING_FUNCTION_LIMIT /*service call or fx*/ ) * 
								( $totalRecordsAllowed /*records*/); // = 100,000,000
			$perTestCaseSpace = ( $totalTestCasesAllowed /*test cases*/ ) * ( $totalRecordsAllowed /*records*/); // = 100,000
			$initialAutoIncrementBoundary = $initialAutoIncrement + ( $areaPrioNum * $perTestAreaSpace ) + ( $prioNum * $perTestCaseSpace );
			$markerData = '//<!--INITIAL_AUTO_INCREMENT-->';
			$templateData = str_replace( $markerData, $initialAutoIncrementBoundary . ';' . 
										 $markerData . $initialAutoIncrementBoundary, $templateData );

			// LastAutoIncrement: The final id number it can reach
			$lastAutoIncrementBoundary = $initialAutoIncrementBoundary + ( SERVICE_RECORDING_FUNCTION_LIMIT * $totalRecordsAllowed );
			$markerData = '//<!--LAST_AUTO_INCREMENT-->';
			$templateData = str_replace( $markerData, $lastAutoIncrementBoundary . ';' . 
										 $markerData . $lastAutoIncrementBoundary, $templateData );

			// Save file data (in memory) to disk.				
			file_put_contents( $this->fileName, $templateData );
			chmod( $this->fileName, 0777 );
		}
	}
	
	/**
	 * Records a service request object. It adds a function to the test module that builds
	 * the request object in memory. This enables the test module to fire the request again.
	 * To add the function, some markers are used using this format: <!-- ... -->
	 * Also a test function is added and a call to that test function from the runTest method.
	 * When the test module runs, this function is called, and re-constructs the request and
	 * response. Then it compares the recorded response with the original response.
	 *
	 * @param object $req Server request object.
	 * @throws BizException
	 */
	public function recordRequest( $req )
	{
		$totalRecordsAllowed = 100; 
		if( $this->fileName ) {
			$fileData = file_get_contents( $this->fileName );
			$servicePrefix = substr( $this->serviceName, 0, 3 );
			$serviceFolder = strtolower( $servicePrefix );

			// Increment function counter at the file data (in memory).
			$markerData = '//<!--FUNCTION_CALLS_COUNT-->';
			$markerPos = strpos( $fileData, $markerData );
			$callsCount = intval( substr( $fileData, $markerPos + strlen($markerData), 3 ) );
			if( $callsCount == SERVICE_RECORDING_FUNCTION_LIMIT ) {
				$this->releaseSemaphore();
				throw new BizException( 'ERR_ERROR', 'Server', 
					'TestCase is full. Reached service/function recording limit "' . SERVICE_RECORDING_FUNCTION_LIMIT .'".',
					'Could not record the service.' );
			}
			$callsCount++;
			$this->callsCountStr = sprintf( '%03d', $callsCount );
			$fileData =
				substr( $fileData, 0, $markerPos ).$markerData.$this->callsCountStr.
				substr( $fileData, $markerPos + strlen( $markerData ) + 3 );
			
			// Set the DB auto increment before doing the recording.
			require_once BASEDIR.'/server/utils/TestSuite.php';
			$testSuitUtils = new WW_Utils_TestSuite();
			$dbTablesWithAutoIncrement = $testSuitUtils->getDbTablesWithAutoIncrement();
			$markerData = '//<!--INITIAL_AUTO_INCREMENT-->';
			$markerPos = strpos( $fileData, $markerData );
			$initialAutoIncrementBoundary = intval( substr( $fileData, $markerPos + strlen($markerData), 9 ) );
			$initialAutoIncrement = $initialAutoIncrementBoundary + ( $callsCount * $totalRecordsAllowed/*rows*/ );
			
			//Retrieve the -last- db id boundary to check if auto increment has exceeded this boundary.
			$markerDataTemp = '//<!--LAST_AUTO_INCREMENT-->';
			$markderPosTemp = strpos( $fileData, $markerDataTemp );
			$lastAutoIncrementBoundary = intval( substr( $fileData, $markderPosTemp + strlen($markerDataTemp), 9 ) );
			if( $initialAutoIncrement > $lastAutoIncrementBoundary ) {
				$this->releaseSemaphore();
				throw new BizException( 'ERR_ERROR', 'Server', 
					'Db id has exceeded the permitted id boundary [' . $lastAutoIncrementBoundary.'].', 'Could not record the service.' );
			}
			$testSuitUtils->setAutoIncrement( $dbTablesWithAutoIncrement, $initialAutoIncrement );
			$fileData =
				substr( $fileData, 0, $markerPos ).$markerData.$initialAutoIncrementBoundary.
				substr( $fileData, $markerPos + strlen($markerData.$initialAutoIncrement) );
			
			
			// Insert call to new test function into the file data (in memory).
			$markerData = '//<!--FUNCTION_CALLS_INSERTION_POINT-->';
			$autoIncrementReset = '$testSuitUtils->setAutoIncrement( $this->dbTablesWithAutoIncrement, '. 
									'$this->initialAutoIncrement() + ( '. $callsCount * $totalRecordsAllowed .' ) );' . PHP_EOL;
			$funcCall = "\t\t".'$this->testService'.$this->callsCountStr.'(); // '.$this->serviceName.PHP_EOL;
			$markerPos = strpos( $fileData, $markerData );
			$fileData =
				substr( $fileData, 0, $markerPos ).$autoIncrementReset.$funcCall."\t\t".$markerData.
				substr( $fileData, $markerPos + strlen($markerData) );

			// Add step to the scenario (at test header) into the file data (in memory).
			$markerData = '<!--SCENARIO_STEPS_INSERTION_POINT-->';
			$scenarioStep = '<li>'.$this->callsCountStr.': ... ('.$this->serviceName.')</li>'.PHP_EOL;
			$markerPos = strpos( $fileData, $markerData );
			$fileData =
				substr( $fileData, 0, $markerPos ).$scenarioStep."\t\t".$markerData.
				substr( $fileData, $markerPos + strlen($markerData) );

			// Build the test function.
			$funcBody = PHP_EOL."\t".'private function testService'.$this->callsCountStr.'()'.PHP_EOL;
			$funcBody .= "\t".'{'.PHP_EOL;
			$funcBody .= "\t\t".'require_once BASEDIR.\'/server/interfaces/services/'.$serviceFolder.'/DataClasses.php\';'. PHP_EOL;			
			$funcBody .= "\t\t".'require_once BASEDIR.\'/server/services/'.$serviceFolder.'/'.$this->serviceName.'Service.class.php\';'.PHP_EOL;
			if( $this->serviceName == 'WflGetDialog' ) { // ugly exception (no good way to solve?)
				$funcBody .= "\t\t".'require_once BASEDIR.\'/server/interfaces/services/wfl/WflGetStatesResponse.class.php\';'.PHP_EOL;
			}
			$funcBody .= "\t\t".'$req = $this->getRecordedRequest'.$this->callsCountStr.'();'.PHP_EOL;
			//$funcBody .= "\t\t".'$req->Ticket = $this->ticket; // repair recorded with current'.PHP_EOL;
			$funcBody .= '<!--GET_RECORDED_RESP_OR_RECORDED_EXCEPTION_INSERTION_POINT-->'.PHP_EOL;
			$funcBody .= '<!--CALL_SERVICE_INSERTION_POINT-->'.PHP_EOL;
			$funcBody .= "\t\t".'<!--SERVICE_RESP_OR_EXCEPTION_INSERTION_POINT-->'.PHP_EOL;
			$funcBody .= "\t".'}'.PHP_EOL;

			// Build the Request function.
			$funcBody .= PHP_EOL."\t".'private function getRecordedRequest'.$this->callsCountStr.'()'.PHP_EOL;
			$funcBody .= "\t".'{'.PHP_EOL;
			$funcBody .= $this->buildDataObject( '$request', $req );
			$funcBody .= "\t\t".'return $request;'.PHP_EOL;
			$funcBody .= "\t".'}'.PHP_EOL;

			// Insert the function (as built above) into the file data (in memory).
			$fileData = $this->appendFunction( $fileData, $funcBody );

			// Save file data (in memory) to disk.				
			file_put_contents( $this->fileName, $fileData );
			chmod( $this->fileName, 0777 );
		}
	}

	/**
	 * Records a service response object. It adds a function to the test module that reconstructs
	 * the recorded response. See recordRequest() function for more info.
	 *
	 * @param object $resp The service response object.
	 */
	public function recordResponse( $resp )
	{
		if( $this->fileName ) {
			$fileData = file_get_contents( $this->fileName );

			// <!-- Add remaining body of testServiceNR function.
			// Insert 'calling the getRecordedResponse' code here.
			$markerData = '<!--GET_RECORDED_RESP_OR_RECORDED_EXCEPTION_INSERTION_POINT-->';
			$markerPos = strpos( $fileData, $markerData );
			
			$callRecordedResp = "\t\t".'$recResp = $this->getRecordedResponse'.$this->callsCountStr.'();'.PHP_EOL;
			$fileData =
				substr( $fileData, 0, $markerPos ).$callRecordedResp."\t".
				substr( $fileData, $markerPos + strlen($markerData) );
				
			// Insert 'callService' code here.
			$markerData = '<!--CALL_SERVICE_INSERTION_POINT-->';
			$markerPos = strpos( $fileData, $markerData );
			
			$callServiceCode = "\t\t".'require_once BASEDIR.\'/server/utils/TestSuite.php\';'.PHP_EOL;
			$callServiceCode .= "\t\t".'$testSuitUtils = new WW_Utils_TestSuite();'.PHP_EOL;
			$callServiceCode .= "\t\t".'$curResp = $testSuitUtils->callService( $this, $req, \'testService#'.$this->callsCountStr.'\');'.PHP_EOL;
			$fileData =
				substr( $fileData, 0, $markerPos ).$callServiceCode.
				substr( $fileData, $markerPos + strlen($markerData) );
			
			// Insert Service response validation code here.
			$markerData = '<!--SERVICE_RESP_OR_EXCEPTION_INSERTION_POINT-->';
			$markerPos = strpos( $fileData, $markerData );

			$funcBody = 'require_once BASEDIR.\'/server/utils/PhpCompare.class.php\';'.PHP_EOL;
			$funcBody .= "\t\t".'$phpCompare = new WW_Utils_PhpCompare();'.PHP_EOL;
			$funcBody .= "\t\t".'$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked'.PHP_EOL;
			$funcBody .= "\t\t".'if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {'.PHP_EOL;
			$funcBody .= "\t\t\t".'$recRespFile = LogHandler::logPhpObject( $recResp, \'print_r\', \''.$this->callsCountStr.'\' );'.PHP_EOL;
			$funcBody .= "\t\t\t".'$curRespFile = LogHandler::logPhpObject( $curResp, \'print_r\', \''.$this->callsCountStr.'\' );'.PHP_EOL;
			$funcBody .= "\t\t\t".'$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );'.PHP_EOL;
			$funcBody .= "\t\t\t".'$errorMsg .= \'Recorded response: \'.$recRespFile.\'<br/>\';'.PHP_EOL;
			$funcBody .= "\t\t\t".'$errorMsg .= \'Current response: \'.$curRespFile.\'<br/>\';'.PHP_EOL;
			$funcBody .= "\t\t\t".'$this->setResult( \'ERROR\', $errorMsg, \'Error occured in '.$this->serviceName.' response.\');'.PHP_EOL;
			$funcBody .= "\t\t\t".'return;'.PHP_EOL;
			$funcBody .= "\t\t".'}'.PHP_EOL;
			$fileData =
				substr( $fileData, 0, $markerPos ).$funcBody."\t".
				substr( $fileData, $markerPos + strlen($markerData) );

			// Finish adding remaining body of testServiceNR functio. -->				
			
			// Build the Response function.
			$funcBody = PHP_EOL."\t".'private function getRecordedResponse'.$this->callsCountStr.'()'.PHP_EOL;
			$funcBody .= "\t".'{'.PHP_EOL;
			$funcBody .= $this->buildDataObject( '$response', $resp );
			$funcBody .= "\t\t".'return $response;'.PHP_EOL;
			$funcBody .= "\t".'}'.PHP_EOL;

			// Insert the function (as built above) into the file data.
			$fileData = $this->appendFunction( $fileData, $funcBody );

			// Save file data (in memory) to disk.				
			file_put_contents( $this->fileName, $fileData );
			chmod( $this->fileName, 0777 );
		}
		
		// Release the semaphore to allow other/next services getting recorded.
		$this->releaseSemaphore();
	}

	/**
	 * Records a BizException that was thrown during service handling. 
	 * See recordRequest() function for more info.
	 *
	 * @param BizException $e
	 */
	public function recordBizException( $e )
	{
		if( $this->fileName ) {
			$fileData = file_get_contents( $this->fileName );
			
			// <!-- Add remaining body of testServiceNR function.
			// Insert calling the recorded response code here.
			$markerData = '<!--GET_RECORDED_RESP_OR_RECORDED_EXCEPTION_INSERTION_POINT-->';
			$markerPos = strpos( $fileData, $markerData );
			
			$callRecordedResp = "\t\t".'$recException = $this->getRecordedBizException'.$this->callsCountStr.'();'.PHP_EOL;
			// Insert Service response validation code into the file data (in memory).
			$fileData =
				substr( $fileData, 0, $markerPos ).$callRecordedResp."\t".
				substr( $fileData, $markerPos + strlen($markerData) );
				
			// Insert Call service code here.
			$markerData = '<!--CALL_SERVICE_INSERTION_POINT-->';
			$markerPos = strpos( $fileData, $markerData );
			
			$callServiceCode = "\t\t".'require_once BASEDIR.\'/server/utils/TestSuite.php\';'.PHP_EOL;
			$callServiceCode .= "\t\t".'$testSuitUtils = new WW_Utils_TestSuite();'.PHP_EOL;
			$callServiceCode .= "\t\t".'$testSuitUtils->callService( $this, $req, \'testService#'.
								$this->callsCountStr.'\', $recException );';

			// Insert Service response validation code into the file data (in memory).
			$fileData =
				substr( $fileData, 0, $markerPos ).$callServiceCode.
				substr( $fileData, $markerPos + strlen($markerData) );
				
			// Insert Service response validation code here.
			$markerData = '<!--SERVICE_RESP_OR_EXCEPTION_INSERTION_POINT-->';
			$markerPos = strpos( $fileData, $markerData );

			$funcBody = '';
		
			// Insert Service response validation code into the file data (in memory).
			$fileData =
				substr( $fileData, 0, $markerPos ).$funcBody."\t".
				substr( $fileData, $markerPos + strlen($markerData) );
				
			// Finish adding remaining body of testServiceNR functio. -->
			
			// Build the BizException function
			$funcBody = PHP_EOL."\t".'private function getRecordedBizException'.$this->callsCountStr.'()'.PHP_EOL;
			$funcBody .= "\t".'{'.PHP_EOL;			
			$funcBody .= "\t\t".'return "' .$e->getMessage() .'";'.PHP_EOL;
			$funcBody .= "\t".'}'.PHP_EOL;
			
			// Insert the function (as built above) into the file data.
			$fileData = $this->appendFunction( $fileData, $funcBody );
			
			// Save file data (in memory) to disk.
			file_put_contents( $this->fileName, $fileData );
			chmod( $this->fileName, 0777 );

		}

		// Release the semaphore to allow other/next services getting recorded.
		$this->releaseSemaphore();
	}
	
	/**
	 * Release the semaphore that was earlier on being created during recording.
	 */
	public function releaseSemaphore()
	{
		if( self::$semaphoreId ) {
			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			BizSemaphore::releaseSemaphore( self::$semaphoreId );
			self::$semaphoreId = null;
		}
	}
	
	/**
	 * Adds a function body to the test module. Those are inserted before a special marker
	 * that is assumed to be present in the template: <!--FUNCTION_BODIES_INSERTION_POINT-->
	 *
	 * @param string $fileData File contents of the test module.
	 * @param string $funcBody Function body to add.
	 * @return string Test module update with the function body.
	 */
	private function appendFunction( $fileData, $funcBody )
	{
		// Insert the function into the file data (in memory).
		$markerData = '//<!--FUNCTION_BODIES_INSERTION_POINT-->';
		$markerPos = strpos( $fileData, $markerData );
		$fileData =
			substr( $fileData, 0, $markerPos ).$funcBody."\t".$markerData.
			substr( $fileData, $markerPos + strlen($markerData) );
		return $fileData;
	}
	
	/**
	 * Generates PHP code that can reconstruct a given object. This is used to build function bodies
	 * that can reconstruct request- or response objects.
	 * Gets called in recursion whereby the given variable name ($path) grows, depending on the 
	 * data tree inside. For example: $request->WflGetObjects->Objects[0]->MetaData->BasicMetaData->ID
	 *
	 * @param string $path Variable name that represents the PHP object.
	 * @param mixed $obj The PHP object to build function body for.
	 * @return string Generated PHP code (function body).
	 */
	private function buildDataObject( $path, $obj )
	{
		$funcBody = '';
		if( is_object( $obj ) ) {
			// Handle attachments.
			$className = get_class( $obj );
			$localFile = ($className == 'Attachment') ? $this->copyAttachmentToTestDataFolder( $obj ) : '';
			// Handle element properties.
			$funcBody .= "\t\t".$path.' = new '.$className.'();'.PHP_EOL;
			foreach( get_object_vars( $obj ) as $key => $val ) {
				if( $localFile && $key == 'FilePath' ) {
					$val = ''; // FilePath is not used, clear it to avoid confusion, $inputPath is used instead.
				}
				$funcBody .= $this->buildDataObject( $path.'->'.$key, $val );
			}
			if( $localFile ) {
				$funcBody .= "\t\t".'$inputPath = dirname(__FILE__).\'/'.$localFile.'\';'.PHP_EOL;
				$funcBody .= "\t\t".'$this->transferServer->copyToFileTransferServer( $inputPath, '.$path.' );'.PHP_EOL;
			}
		} else if( is_array( $obj ) ) {
			$funcBody .= "\t\t".$path.' = array();'.PHP_EOL;
			foreach( $obj as $key => $val ) {
				if( is_string( $key ) ) {
					$funcBody .= $this->buildDataObject( $path.'[\''.$key.'\']', $val );
				} else {
					$funcBody .= $this->buildDataObject( $path.'['.$key.']', $val );
				}
			}
		} else if( is_string( $obj ) ) {
			$funcBody .= "\t\t".$path.' = '.$this->multibytes2Chrs( $obj ).';'.PHP_EOL;
		} else if( is_bool( $obj ) ) {
			$funcBody .= "\t\t".$path.' = '.($obj?'true':'false').';'.PHP_EOL;
		} else if( is_null( $obj ) ) {
			$funcBody .= "\t\t".$path.' = null;'.PHP_EOL;
		} else {
			$funcBody .= "\t\t".$path.' = '.$obj.';'.PHP_EOL;
		}
		return $funcBody;
	}
	
	/**
	 * Copies a file attachment (as sent along with the web services) from the Transfer Server
	 * folder to the test module's local xxx_TestData folder. This is used for request objects
	 * whereby attachments are uploaded to the server. Those are stored at the test module to
	 * get reconstructed and fired again.
	 *
	 * @param Attachment $obj
	 * @return string File path the local test module's xxx_TestData folder.
	 */
	private function copyAttachmentToTestDataFolder( $obj )
	{
		// Determine file extension.
		require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';
		$fileExt = MimeTypeHandler::mimeType2FileExt( $obj->Type );

		// Determine unique attachment number.
		static $guidNr = 0;
		$guidStr = sprintf( '%03d', $guidNr );
		$guidNr++;
		
		// Create TestData folder when not exists.
		if( !file_exists($this->testFolder) ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir( $this->testFolder, 0777 );
		}
		
		// Copy the attachment from Transfer Server to TestData folder.
		$localFile = 'rec#'.$this->callsCountStr.'_att#'.$guidStr.'_'.$obj->Rendition.$fileExt;
		copy( $obj->FilePath, $this->testFolder.'/'.$localFile );
		chmod( $this->testFolder.'/'.$localFile, 0777 );
		return basename($this->testFolder).'/'.$localFile;
	}

	/*
	 * Returns hexadecimal representation of a multi-byte string.
	 *
	 * @input $string Multi-byte UTF-8 string
	 * @return string Space separated hex dump.
	 */
	private function multibytes2Chrs( $string )
	{
		$string = str_replace( '\'', '\\\'', $string ); // escape single quotes
		$strByteCnt = strlen( $string );    // byte count
		$strCharCnt = mb_strlen( $string ); // character count
		if( $strByteCnt == $strCharCnt ) {  // avoid expensive preg_split() for ASCII strings (at else part)
			$retVal = '\''.$string.'\'';
		} else {
			$retVal = '\'\''; // start with empty string: ''
			for( $c = 0; $c < $strCharCnt; $c++ ) {
				$char = mb_substr( $string, $c, 1 );
				$strByteCnt = count( preg_split( "`.`", $char ) ) - 1;
				if( $strByteCnt > 1 ) {
					for( $i = 0; $i < $strByteCnt; $i++ ) { // add multi-byte characters (one chr() per byte)
						$retVal .= '.chr(0x'.dechex(ord($char[$i])).')';
					}
				} else {
					$retVal .= '.\''.$char.'\''; // add ASCII char: 'b' added to 'a' becomes 'a'.'b'
				}
			}
			$retVal = str_replace( '\'.\'', '', $retVal ); // remove unneeded glue, e.g.: 'a'.'b' => 'ab'
		}
		return $retVal;
	}
}
