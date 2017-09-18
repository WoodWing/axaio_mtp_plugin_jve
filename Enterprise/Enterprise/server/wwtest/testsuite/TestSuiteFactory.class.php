<?php
/**
 * Factorty class that constructs TestCase and TestSuite classes and runs their test methods.
 * It can read all tests and return their descriptions telling admin users what those tests actually do.
 * The results from test runs and the descriptions can be returned in XML format.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
class TestSuiteFactory
{
	static private $CurrentTestsNode = null; // "Tests" XML node, which is current insertion point while building XML doc.
	
	/**
	  * Includes TestCase or TestSuite PHP file and creates an instance of its class.
	  *
	  * @param string $testFile The PHP file to include
	  * @return TestCase|TestSuite.
	  * @throws Exception
	  */
	static private function createTestModule( $testFile )
	{
		if( strpos( $testFile, BASEDIR ) !== 0 ) {
			return null; // paranoid check
		}
		if( !file_exists( $testFile ) ) {
			return null;
		}
		// Get relative file. (e.g. WebServices/AdmServices/Logon_TestCase.php)
		$testRelFile = substr( $testFile, strpos( $testFile, '/testsuite/' ) + strlen('/testsuite/') );
		// Get relative file parts. (e.g. array('WebServices','AdmServices','Logon_TestCase.php'))
		$pathParts = explode( '/', $testRelFile );
		// Get file name by removing it from the end. (e.g. Logon_TestCase.php)
		$fileName = array_pop( $pathParts ); 
		// Derive class name from file name. Step 1: remove .php extension.
		$className = substr( $fileName, 0, strpos( $fileName, '.php' ) );
		if( $className ) {
			// Derive class name from file name. Step 2: Insert path members (respecting name convention)
			$className = implode( '_', $pathParts ).'_'.$className;
			// Derive class name from file name. Step 3: Insert prefix (respecting name convention).
			$className = 'WW_TestSuite_'.$className;
			require_once $testFile;
			if( class_exists( $className ) ) {
				$testObj = new $className();
				if( $testObj instanceOf TestCase || $testObj instanceOf TestSuite ) {
					return $testObj;
				}
			} else {
				throw new Exception( 'Error: "'.$testFile.'" has no class named "'.$className.'" which is required!' );
			}
		}
		return null;
	}

	/**
	 * Adds TestCase/TestSuite objects ($testObjs) to a given XML parent node ($xmlTests).
	 * Properties of the objects (ClassPath, Prio, Type, etc) are written as XML elements.
	 *
	 * @param DOMDocument $xmlDoc The XML document to write into
	 * @param DOMNode $xmlTests The parent XML node (TestSuite) to add new XML nodes (TestCases) to.
	 * @param array $testObjs List of TestCase/TestSuite objects to be added to XML.
	 * @throws Exception When there is a prio conflict between the test objects. (Same prio is not allowed.)
	 */
	static private function addTestObjectsToXmlTree( $xmlDoc, $xmlTests, $testObjs )
	{
		// Sort TestCases on prio
		$testObjsSorted = array();
		foreach( $testObjs as $classPath => $testObj ) {
			if( isset($testObjsSorted[$testObj->getPrio()]) ) {
				$present = $testObjsSorted[$testObj->getPrio()];
				throw new Exception( 'Error: "'.$present['testCase']->getDisplayName().'" has same prio as "'.$testObj->getDisplayName().'" which is not allowed!' );
			}
			$testObjsSorted[$testObj->getPrio()] = array( 'testCase' => $testObj, 'classPath' => $classPath );
		}
		ksort($testObjsSorted);

		//$xmlTests = $xmlDoc->createElement( 'Tests' );
		//$xmlParent->appendChild( $xmlTests );

		// Fill XML document with details of all test objects
		foreach( $testObjsSorted as $prio => $test ) {
			$xmlCase = $xmlDoc->createElement( 'Test' );
			$xmlTests->appendChild( $xmlCase );
			$type = $test['testCase'] instanceof TestSuite ? 'TestSuite' : 'TestCase';
			self::createTextElem( $xmlDoc, $xmlCase, 'ClassPath',   $test['classPath'] );
			self::createTextElem( $xmlDoc, $xmlCase, 'Prio',        $prio );
			self::createTextElem( $xmlDoc, $xmlCase, 'Type', 		$type );
			self::createTextElem( $xmlDoc, $xmlCase, 'DisplayName', $test['testCase']->getDisplayName() );
			self::createTextElem( $xmlDoc, $xmlCase, 'DisplayWarn', $test['testCase']->getDisplayWarn() );
			self::createTextElem( $xmlDoc, $xmlCase, 'TestGoals',   $test['testCase']->getTestGoals() );
			self::createTextElem( $xmlDoc, $xmlCase, 'TestMethods', $test['testCase']->getTestMethods() );
			self::createTextElem( $xmlDoc, $xmlCase, 'isTestable', $test['testCase']->isTestable() ? 'true' : 'false');
			self::createTextElem( $xmlDoc, $xmlCase, 'isTestableReason', $test['testCase']->getIsTestableReason());

			$xmlSuiteTests = $xmlDoc->createElement( 'Tests' );
			$xmlCase->appendChild( $xmlSuiteTests );
			if( $type == 'TestSuite' ) {
				$suiteDir = BASEDIR . $test['classPath'];
				if( is_dir( $suiteDir ) ) {
					self::readTestObjectsFromFolders( $xmlDoc, $xmlSuiteTests, $suiteDir );
				}
				self::$CurrentTestsNode = $xmlSuiteTests;
			}
		}		

	}

	/**
	 * Scans the given folder for subfolder that contains test module php files.
	 *
	 * @param DOMDocument $xmlDoc
	 * @param DOMNode $xmlTests
	 * @param string $dirName
	 * @throws Exception
	 */
	static private function readTestObjectsFromFolders( $xmlDoc, $xmlTests, $dirName )
	{
		$testObjs = array();
		$thisDir = opendir( $dirName );
		if( $thisDir === false ) {
			throw new Exception( 'Error: Could not read from folder: '.$dirName );
		}
		while( ($itemName = readdir($thisDir)) !== false ) {
			$testObj = null;
			if( $itemName == '.' || $itemName == '..' || $itemName == '.DS_Store' ) {
				// cur dir / parent dir
			} else if( is_file( $dirName.'/'.$itemName ) && strstr( $itemName, '_TestCase.php' ) !== false ) {
				$testObj = self::createTestModule( $dirName.'/'.$itemName );
			} else if( is_file( $dirName.'/'.$itemName.'/TestSuite.php' ) ) {
				$testObj = self::createTestModule( $dirName.'/'.$itemName.'/TestSuite.php' );
			}
			if( $testObj ) {
				$testFile = substr( $dirName.'/'.$itemName, strlen(BASEDIR) );
				$testObjs[$testFile] = $testObj;
			}
		}
		closedir( $thisDir );
		self::addTestObjectsToXmlTree( $xmlDoc, $xmlTests, $testObjs );
	}

	/**
	  * Reads TestCase and TestSuite PHP files from testsuite folder and creates instances.
	  * It runs there method to collect details and writes them in XML format.
	  *
	  * @param string $testSuite Relative folder to testsuite folder of PHP files to include.
	  * @return string XML stream.
	  */
	static public function getTestsAsXml( $testSuite )
	{
		try {
			// Create XML output stream to return caller
			$xmlDoc = new DOMDocument();
			$xmlTests = $xmlDoc->createElement( 'Root' );
			$xmlDoc->appendChild( $xmlTests );

			// Collect core server testsuite folder if $testSuite exists. (Note that $testSuite could be a relative path.
			// This path may not exist, in case the testsuite is provided by one of the server plugins.)
			$suiteDirs = array();
			$dirName = BASEDIR.'/server/wwtest/testsuite/'.$testSuite;
			if( is_file( $dirName.'/TestSuite.php' ) ) {
				$suiteDirs[] = $dirName;
			}

			// Collect server plugin testsuite folders
			$pluginDirs = array( BASEDIR.'/server/plugins/', BASEDIR.'/config/plugins/' );
			foreach( $pluginDirs as $pluginDir ) {
				$scanDirs = glob( $pluginDir . '*');
				foreach( $scanDirs as $scanDir ) {
					$suiteDir = $scanDir.'/'.'testsuite/'.$testSuite;
					if( is_dir( $suiteDir ) ) {
						$suiteDirs[] = $suiteDir;
					}
				}
			}

			// Read TestCase class file from disk and create instance
			if( $suiteDirs ) {
				$dirName = reset($suiteDirs);
				if( is_file( $dirName.'/TestSuite.php' ) ) {
					$testObj = self::createTestModule( $dirName.'/TestSuite.php' );
					if( $testObj ) {
						$testFile = substr( $dirName.'/TestSuite.php', strlen(BASEDIR) );
						$testObjs[$testFile] = $testObj;
						self::addTestObjectsToXmlTree( $xmlDoc, $xmlTests, $testObjs );
						$xmlTests = self::$CurrentTestsNode; // Set by addTestObjectsToXmlTree()
					}
				}
			}

			foreach( $suiteDirs as $suiteDir ) {
				self::readTestObjectsFromFolders( $xmlDoc, $xmlTests, $suiteDir );
			}

		} catch( Exception $e ) {
			$xmlDoc = new DOMDocument();
			$xmlError = $xmlDoc->createElement( 'FatalError' );
			self::createTextElem( $xmlDoc, $xmlError, 'Description', $e->getMessage() );
			$xmlDoc->appendChild( $xmlError );
		}
		return $xmlDoc->saveXML(); // return XML stream to caller
	}
	
	/**
	  * Includes TestCase or TestSuite PHP file and creates an instance of its class.
	  * It runs its test method runTest() and collects the results in XML format.
	  *
	  * @param string $sessionId Test session. Do NOT confuse with Enterprise sessions (ticket).
	  * @param string $classPath Relative file path to testsuite folder of the PHP file to include.
	  * @return string XML stream.
	  */
	static public function runTest( $sessionId, $classPath ) 
	{
		// Read TestCase class file from disk, create instance and run its test method
		$testResults = array();
		$snapBefore = null;
		try {
			// Start test session.
			ob_start(); // Capture std output. See ob_get_contents() below for details.
			$testObj = self::createTestModule( BASEDIR.$classPath );
			$testObj->setSessionId( $sessionId );
			
			// Only perform for normal test cases or for test cases that belong to
			// server plugins that are installed and active.
			if( !self::skipWhenNotInstalledPlugin( $classPath, $testObj ) ) {
				
				// Make snapshot of DB when the test is self-cleaning.
				$selfCleaning = $testObj->isSelfCleaning();
				if( $selfCleaning ) {
					$snapBefore =  self::getSnapShotOfDbTables();
				}
				
				// Make snapshot of the server error log.
				$errorFileOrgPath = LogHandler::getDebugErrorLogFile();
				$errorFileOrgSize = $errorFileOrgPath ? filesize($errorFileOrgPath) : 0;
				
				// Run the actual test.
				$testObj->runTest();
				
				// When the script did not report any errors by itself, raise error when 
				// the script has caused errors in server logging. Should be error free.
				if( !$testObj->hasError() && !$testObj->hasWarning() ) {
					$errorFileNewPath = LogHandler::getDebugErrorLogFile();
					$errorFileNewSize = $errorFileNewPath ? filesize($errorFileNewPath) : 0;
					if( $errorFileOrgPath != $errorFileNewPath || $errorFileOrgSize != $errorFileNewSize ) {
						$errorFilePath = $errorFileNewPath ? $errorFileNewPath : $errorFileOrgPath;
						$testObj->setResult( 'ERROR', 
							'Script has caused errors or warnings in server logging. ',
							'Please check the ones listed in this file: '.$errorFilePath );
					}
				}
				
				// Validate if the test is leaking records in the DB when the test has stated 
				// that it should be self-cleaning.
				if( $selfCleaning ) {
					$nonCleaningTables = $testObj->getNonCleaningTables();
					if( is_null($nonCleaningTables) ) {
						$nonCleaningTables = array( 
							'smart_log',
							'smart_config',
							'smart_messagelog',
							'smart_publishhistory',
							'smart_publishedobjectshist',
							'smart_serverjobs' // e.g. unprocessed jobs for issue/object events (Analytics)
						);
					}   	
					$snapAfter = self::getSnapShotOfDbTables();
					$testResults = self::validateSnapShots( $snapBefore, $snapAfter, $nonCleaningTables );
				}
			}
			
			// Combine the collected test results.
			$testResults = array_merge( $testObj->getResults(), $testResults );
			
			// Check if the test did write to std output (e.g. print()). This is always wrong 
			// because it should set the test results instead. Bad behavior is flagged with ERROR. 
			$printed = ob_get_contents();
			ob_end_clean();
			if( strlen(trim($printed)) > 0 ) {
				$testResults[] = new TestResult( 'ERROR', 'The script has output unofficial message:<br/>'.$printed, '' );
			}
			if( count($testResults) == 0) { // no results, means it all went fine... so we let client know.
				$testResults[] = new TestResult( 'OK', '', '' );
			}
		} catch( Exception $e ) {
			$testResults[] = new TestResult( 'ERROR', $e->getMessage(), '' );
		}

		// Create XML output stream to return caller
		$xmlDoc = new DOMDocument();
		$xmlReport = $xmlDoc->createElement( 'TestReport' );
		$xmlDoc->appendChild( $xmlReport );

		// Add TestCase details to XML stream
		$xmlCase = $xmlDoc->createElement( 'TestCase' );
		$xmlReport->appendChild( $xmlCase );
		self::createTextElem( $xmlDoc, $xmlCase, 'ClassPath', $classPath );

		// Add TestCase results from test method to XML stream
		$xmlResults = $xmlDoc->createElement( 'TestResults' );
		$xmlCase->appendChild( $xmlResults );

		foreach( $testResults as $testResult ) {

			$xmlResult = $xmlDoc->createElement( 'TestResult' );
			$xmlResults->appendChild( $xmlResult );

			self::createTextElem( $xmlDoc, $xmlResult, 'Status',    $testResult->Status );
			self::createTextElem( $xmlDoc, $xmlResult, 'Message',   $testResult->Message );
			self::createTextElem( $xmlDoc, $xmlResult, 'ConfigTip', $testResult->ConfigTip );
		}
		return $xmlDoc->saveXML(); // return XML stream to caller
	}

	static private function createTextElem( $xmlDoc, $xmlParent, $nodeName, $nodeText )
	{
		$xmlNode = $xmlDoc->createElement( $nodeName );
		$xmlParent->appendChild( $xmlNode );
		$xmlText = $xmlDoc->createTextNode( $nodeText );
		$xmlNode->appendChild( $xmlText );
		return $xmlNode;
	}
	
	/**
	 * Checks if the test is shipped by a server plug-in. It returns FALSE when not.
	 * If it's a plugin, it checks if it is installed and enabled. If not, it flags
	 * the test with "Not Installed" and returns TRUE (else FALSE).
	 *
	 * @param string $classPath
	 * @param TestCase $testObj
	 * @return boolean Whether or test belongs to plugin that is not installed.
	 */
	static private function skipWhenNotInstalledPlugin( $classPath, $testObj )
	{
		$pluginName = null;
		$pluginDirs = array( BASEDIR.'/server/plugins/', BASEDIR.'/config/plugins/' );
		foreach( $pluginDirs as $pluginDir ) {
			if( strpos( BASEDIR.$classPath, $pluginDir ) === 0 ) {
				$path = substr( BASEDIR.$classPath, strlen($pluginDir) );
				$pathParts = explode( '/', $path );
				$pluginName = $pathParts[0];
				break;
			}
		}
		$plugInfo = null;
		if( $pluginName ) {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$plugInfo = BizServerPlugin::getInstalledPluginInfo( $pluginName );
			if( $plugInfo && $plugInfo->IsInstalled ) {
				if( !$plugInfo->IsActive ) {
					$testObj->setResult( 'NOTINSTALLED', 
						'The "'.$plugInfo->DisplayName.'" server plug-in is disabled.', 
						'Check <a href="../../server/admin/serverplugins.php'.'">'.
						'Server Plug-ins</a> to enable the plug-in.' );
				}
			} else {
				$pluginDisplayName = $plugInfo ? $plugInfo->DisplayName : $pluginName;
				$testObj->setResult( 'NOTINSTALLED', 
					'The "'.$pluginDisplayName.'" server plug-in is not installed.', 
					'Check <a href="../../server/admin/serverplugins.php'.'">'.
					'Server Plug-ins</a> to install the plug-in.' );
			}
		}
		return $plugInfo && !$plugInfo->IsActive;
	}
	
	/**
	 * It records the maximum Id for each Db table that has primary key.
	 * When there are no records yet in the table, it simply set MaxId as 0.
	 * For tables that do not have primary key, it will record the
	 * total records in that table.
	 *
	 * *Returned value:TableName is with the prefix 'smart_'. e.g 'smart_objects'
	 * 
	 * @return array $map Key-Value array where Key is the *Tablename and Value is the maxId or the total record.
	 */
	static private function getSnapShotOfDbTables()
	{
		require_once BASEDIR.'/server/dbmodel/Reader.class.php';
		require_once BASEDIR.'/server/dbmodel/Definition.class.php';

		$definition = new WW_DbModel_Definition();
		$reader = new WW_DbModel_Reader( $definition );
		$dbdriver = DBDriverFactory::gen();
		$tablesWithoutAutoIncrement = $definition->getTablesWithoutAutoIncrement();

		$map = array();
		foreach( $reader->listTables() as $dbTable ) {
			$dbFieldId = in_array( $dbTable['name'], $tablesWithoutAutoIncrement ) ? null : 'id';
			if( $dbFieldId == 'id' ) {
				$sql = 'SELECT max(`'.$dbFieldId.'`) as `maxid` FROM '. $dbTable['name'] ;
			} else{
				$sql = 'SELECT count(*) as `maxid` FROM '. $dbTable['name'] ;
			}
			$sth = $dbdriver->query($sql);
			$row = $dbdriver->fetch($sth);
			$map[$dbTable['name']] = isset( $row['maxid'] ) ? $row['maxid'] : 0;
		}
		return $map;
	}
	
	/**
	 * For each Db table, MaxId or total records are remembered before the BuildTest.
	 * After the BuildTest, MaxId or total records are taken from each Db table again.
	 *
	 * This function checks if the MaxId/ total records are still the same before and
	 * after the Buildtest.
	 * When a table is found to have different MaxId / total records from before the
	 * BuildTest, it will further check which Db ids are affected and show as Error in
	 * BuildTest.
	 * For tables that do not have primary key, this function can ONLY show how many
	 * new records were inserted but not exactly which records were newly inserted.
	 *
	 * Tables that are defined in $nonCleaningTables will be excluded from the checking.
	 *
	 * @param array $snapBefore $map Key-Value Tablename:MaxId/Total records before the buildTest
	 * @param array $snapAfter $map Key-Value Tablename:MaxId/Total records after the buildTest
	 * @param array $nonCleaningTables Array of tables that will be excluded from checking
	 * @return array $testResults An array of TestResult object consists of 'Status', 'Message' and 'ConfigTip'.
	 */
	static private function validateSnapShots( $snapBefore, $snapAfter, $nonCleaningTables )
	{
		require_once BASEDIR.'/server/dbmodel/Definition.class.php';

		$definition = new WW_DbModel_Definition();
		$tablesWithoutAutoIncrement = $definition->getTablesWithoutAutoIncrement();
		$testResults = array();
		foreach( $snapBefore as $tableName => $maxIdBefore ) {
			if( !in_array( $tableName, $nonCleaningTables ) ) { // skip if table is excluded
				$maxIdAfter = $snapAfter[$tableName];
				if( $maxIdBefore != $maxIdAfter ) {
					$dbTable = str_replace( DBPREFIX, '', $tableName );
					$dbFieldId = in_array( $tableName, $tablesWithoutAutoIncrement ) ? null : 'id';
					if( $dbFieldId == 'id' ) { // DB table with primary key
						$where = '`id` > ? AND `id` <= ?';
						$fieldNames = '*';
						$params = array( $maxIdBefore, $maxIdAfter );
						$rows = DBBase::listRows( $dbTable, '', '', $where, $fieldNames, $params );					
					
						if( !is_null( $rows ) ) {							
							foreach( $rows as $row ) {
								if( !is_null( $dbFieldId )) {
									$testResults[] = new TestResult( 'ERROR', 						
										'Found record ['. $dbFieldId .'='. $row[$dbFieldId] .'] still pending in DB table ['. $tableName.'] , while the script indicates that it is self cleaning.', '' );	
								}
							}	
						} 
					} else { // Db table without primary key, cannot point out which records exactly that are not deleted.
						$newRecords = $maxIdAfter - $maxIdBefore;
						$testResults[] = new TestResult( 'ERROR', 						
									'Found '. $newRecords.' records still pending in DB table ['. $tableName.'] , while the script indicates that it is self cleaning.', 
									'At the moment, the BuildTest cannot provide which record exactly that is/are not deleted.' );
					}
					
				}
			}
		}

		return $testResults;
	}
}
