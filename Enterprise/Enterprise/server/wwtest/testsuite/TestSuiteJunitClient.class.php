<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Client class that runs TestSuite tests (of Enterprise Server) over CLI or HTTP and converts the output to JUnit notation.
 *
 * When running the 'PhpCodingTest' test case, Enterprise Server should be unencrypted and does not have to be installed.
 * For other test cases, Enterprise Server could be encrypted and should be installed.
 *
 * Usage for CLI:
 *    $testSuite = $GLOBALS['argv'][0];
 *    $client = new WW_TestSuite_JunitClient();
 *    $client->handle( $testSuite, $testSuite == 'PhpCodingTest' );
 *
 * Usage for HTTP:
 *    $client = new WW_TestSuite_JunitClient();
 *    $client->handle( $_GET['testSuite'] );
 */

class WW_TestSuite_JunitClient_Test
{
	public $ClassPath;
	public $DisplayName;
	public $Status;
	public $Time = 0;
	public $Message = '';
	public $ExtendedMessage = '';
	public $SessionId = '';
}

class WW_TestSuite_JunitClient
{
	/** @var string $testUri The endpoint of the TestSuite to connect to over HTTP.  */
	private $testUri;

	/** @var string $tesSuite Name or path of the TestSuite to run. */
	private $testSuite;

	/** @var string $sessionId The session of this test run.  */
	private $sessionId;

	/** @var boolean $coldTest TRUE when ES is not installed and files should be examined without ES execution. FALSE to execute tests with help of running ES. */
	private $coldTest;

	/**
	 * Run all tests under the given TestSuite and converts the output to JUnit notation.
	 *
	 * There are two modes in the way it can invoke Enterprise Server (ES); cold and warm.
	 * In cold mode it includes ES files without the need of having ES running. Use for static testing such as 'PhpCodingTest'.
	 * In warm mode it runs tests against an installed/configured/running ES.
	 *
	 * In CLI mode it outputs the JUnit XML report as a file prefixed with TEST and written in the given output folder.
	 * In HTTP mode it returns the JUnit XML report over HTTP.
	 *
	 * @param string $testSuite
	 * @param bool $coldTest
	 * @return string JUnit test report in XML
	 */
	public function handle( $testSuite, $coldTest = false )
	{
		$this->coldTest = $coldTest;
		$this->testSuite = $testSuite;

		$this->initTest();
		$tests = $this->runTests();
		return $this->composeJUnitTestResultReport( $tests );
	}

	/**
	 * Create a new test session.
	 */
	private function initTest()
	{
		if( $this->coldTest == true ) {
			define( 'BASEDIR', realpath( __DIR__.'/../../../' ) ); // Overrule the default config.php.
			require_once BASEDIR.'/config/config.php';
			require_once BASEDIR.'/config/configserver.php';
			// Create a new session id
			require_once BASEDIR.'/server/utils/NumberUtils.class.php';
			$this->sessionId = NumberUtils::createGUID();
		} else {
			require_once __DIR__.'/../../../config/config.php';
			$this->testUri = LOCALURL_ROOT.INETROOT.'/server/wwtest/testsuite.php';
			// Per test suite a different session id. (only on the first level (this is also done in the javascript implementation)
			$this->sessionId = file_get_contents( $this->testUri.'?command=CreateSession' );
		}
	}

	/**
	 * Determine which tests are under the TestSuite and run them all.
	 *
	 * @return WW_TestSuite_JunitClient_Test[] Tests that were ran.
	 */
	private function runTests()
	{
		try {
			$tests = $this->getTests();
			foreach( $tests as $test ) {
				$this->runTest( $test );
			}
		} catch( Exception $e ) {
			// show exceptions as one test with error
			$test = new WW_TestSuite_JunitClient_Test();
			$test->ClassPath = $this->testSuite;
			$test->DisplayName = $this->testSuite;
			$test->Status = 'error';
			$test->Message = $e->getMessage();
			$test->ExtendedMessage = $e->getMessage();
			$tests = array( $test );
		}
		return $tests;
	}

	/**
	 * Retrieve list of test cases that needs to be executed for the TestSuite.
	 *
	 * @return WW_TestSuite_JunitClient_Test[]
	 * @throws Exception
	 */
	private function getTests()
	{
		if( $this->coldTest == true ) {
			require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteFactory.class.php';
			$contents = TestSuiteFactory::getTestsAsXml( $this->testSuite );
		} else {
			$contents = file_get_contents( $this->testUri.'?command=InitTest&testSuite='.$this->testSuite );
		}
		$xml = new SimpleXMLElement( $contents );

		$tests = array();
		if( isset( $xml->Test[0]->Tests[0]->Test ) ) {
			$this->getTestsFromXML( $tests, $xml->Test[0]->Tests[0]->Test );
		} else {
			// testsuite doesn't contain tests
			// maybe it contains an error
			$error = '';
			if( isset( $xml->Description ) ) {
				$error = $xml->Description;
			} else {
				$error = 'Unable to parse contents, contents: '.$contents;
			}
			throw new Exception( $error );
		}
		return $tests;
	}

	/**
	 * Compose tests (data objects) from the tests (XML) returned by the TestSuite.
	 *
	 * @param WW_TestSuite_JunitClient_Test[] $tests
	 * @param SimpleXMLElement[] $xmlTests TestSuite tests read from XML
	 */
	private function getTestsFromXML( &$tests, $xmlTests )
	{
		foreach( $xmlTests as $xmlTest ) {
			// a test can contain sub tests
			if( $xmlTest->Type == 'TestSuite' ) {
				$this->getTestsFromXML( $tests, $xmlTest->Tests[0]->Test );
			} else {
				$test = new WW_TestSuite_JunitClient_Test();
				$test->ClassPath = strval( $xmlTest->ClassPath );
				$test->DisplayName = strval( $xmlTest->DisplayName );
				$test->SessionId = strval( $this->sessionId );
				$tests[] = $test;
			}
		}

	}

	/**
	 * Execute a specific test at the TestSuite in ES.
	 *
	 * @param WW_TestSuite_JunitClient_Test $test
	 * @throws Exception
	 */
	private function runTest( WW_TestSuite_JunitClient_Test $test )
	{
		$startTime = microtime( true );
		$options = array( 'timeout' => 3600 ); // Max 1 hour
		if( $this->coldTest == true ) {
			require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteFactory.class.php';
			$contents = TestSuiteFactory::runTest( $this->sessionId, $test->ClassPath, $this->testSuite );
		} else {
			$client = new Zend\Http\Client( $this->testUri.'?command=PollTest&testSuite='.$this->testSuite.
				'&classPath='.$test->ClassPath.'&sessionId='.$test->SessionId, $options );
			$response = $client->send();
			$contents = $response->getBody();
		}
		$test->Time = microtime( true ) - $startTime;

		if( !$contents ) {
			throw new Exception( 'The '.$test->ClassPath.' test did not return any results. '.
				'The PHP process might have exit unexpectedly. Check php.log and server logging.' );
		}

		$xml = new SimpleXMLElement( $contents );

		// if xml is not correct, the result is error
		if( !isset( $xml->TestCase[0]->TestResults[0]->TestResult ) ) {
			$test->Status = 'error';
			$test->Message = 'Unknown error';
		} else {
			// one test can have multiple results, if one of them is error the result is failure
			// assume we don't have multiple NOTINSTALLED, OK, INFO and WARN messages
			$failure = false;
			$failureMsg = '';
			foreach( $xml->TestCase[0]->TestResults[0]->TestResult as $testResult ) {
				switch( $testResult->Status ) {
					case 'FATAL':
					case 'ERROR':
						if( !$failure ) {
							$failure = true;
							$failureMsg = $testResult->Message;
						}
						break;
					case 'NOTINSTALLED':
						$test->Status = 'skipped';
						break;
					case 'OK':
					case 'INFO':
					case 'WARN':
						$test->Status = 'success';
						break;
				}
				$test->ExtendedMessage .= $testResult->Status.': '.$testResult->Message;
			}
			if( $failure ) {
				$test->Status = 'failure';
				$test->Message = $failureMsg;
			}
		}
	}

	/**
	 * Compose a standard JUnit report from given TestSuite results.
	 *
	 * @param WW_TestSuite_JunitClient_Test[] $tests
	 * @return string JUnit report (as XML string).
	 */
	private function composeJUnitTestResultReport( $tests )
	{
		$domDoc = new DOMDocument();

		$root = $domDoc->createElement( 'testsuite' );
		$root = $domDoc->appendChild( $root );
		$root->setAttribute( 'name', $this->testSuite );
		$root->setAttribute( 'tests', count( $tests ) );

		$errors = 0;
		$failures = 0;
		$skipped = 0;
		$time = 0;
		foreach( $tests as $test ) {
			$testName = preg_replace( '|/\\\\|', '_', $test->ClassPath );

			$testCase = $domDoc->createElement( 'testcase' );
			$testCase = $root->appendChild( $testCase );

			$testCase->setAttribute( 'classname', $testName );
			$testCase->setAttribute( 'name', $test->DisplayName );
			$testCase->setAttribute( 'time', $test->Time );
			$testCase->setAttribute( 'status', $test->Status );

			switch( $test->Status ) {
				case 'error':
					$errorEl = $domDoc->createElement( 'error' );
					$errorEl = $testCase->appendChild( $errorEl );
					$errorEl->setAttribute( 'message', $test->Message );
					$textEl = $domDoc->createTextNode( $test->ExtendedMessage );
					$errorEl->appendChild( $textEl );
					$errors++;
					break;
				case 'failure':
					$failureEl = $domDoc->createElement( 'failure' );
					$failureEl = $testCase->appendChild( $failureEl );
					$failureEl->setAttribute( 'message', $test->Message );
					$textEl = $domDoc->createTextNode( $test->ExtendedMessage );
					$failureEl->appendChild( $textEl );
					$failures++;
					break;
				case 'skipped':
					$skipped++;
					break;
			}
			$time += $test->Time;
		}

		$root->setAttribute( 'failures', $failures );
		$root->setAttribute( 'errors', $errors );
		$root->setAttribute( 'skipped', $skipped );
		$root->setAttribute( 'time', $time );

		$domDoc->formatOutput = true;
		return $domDoc->saveXML();
	}
}