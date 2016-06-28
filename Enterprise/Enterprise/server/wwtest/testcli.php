<?php
/**
 * Test script
 * 
 * Usage
 * Test script must be run from the command line and on an installed sever
 * 
 * php testcli.php <TestSuite>
 */

require_once dirname(__FILE__) . '/../../config/config.php';

$testsuitePath = '/server/wwtest/testsuite.php';
define('TESTSUITE_URI', LOCALURL_ROOT . INETROOT . $testsuitePath);

class WW_Test
{
	public $ClassPath;
	public $DisplayName;
	public $Status;
	public $Time = 0;
	public $Message = '';
	public $ExtendedMessage = '';
	public $SessionId = '';
}

function getTestsFromXML(&$tests, $xmlTests, $sessionId = null )
{
	if ( !$sessionId ) {
		// Per test suite a different session id. (only on the first level (this is also done in the javascript implementation)
		$sessionId = file_get_contents(TESTSUITE_URI . '?command=CreateSession');
	}
	foreach ($xmlTests as $xmlTest){
		// a test can contain sub tests
		if ($xmlTest->Type == 'TestSuite'){
			getTestsFromXML($tests, $xmlTest->Tests[0]->Test, $sessionId);
		} else {
			$test = new WW_Test();
			$test->ClassPath = strval($xmlTest->ClassPath);
			$test->DisplayName = strval($xmlTest->DisplayName);
			$test->SessionId = strval($sessionId);
			$tests[] = $test;
		}
	}
	
}

function getTests($testSuite)
{
	$contents = file_get_contents(TESTSUITE_URI . '?command=InitTest&testSuite=' . $testSuite);
	
	$xml = new SimpleXMLElement($contents);

	$tests = array();
	if (isset($xml->Test[0]->Tests[0]->Test)){
		getTestsFromXML($tests, $xml->Test[0]->Tests[0]->Test);
	} else {
		// testsuite doesn't contain tests
		// maybe it contains an error
		$error = '';
		if (isset($xml->Description)){
			$error = $xml->Description;
		} else {
			$error = 'Unable to parse contents, contents: ' . $contents;
		}
		throw new Exception($error);
	}
	
	return $tests;
}

function pollTest(WW_Test $test)
{
	$startTime = microtime(true);
	$options = array( 'timeout' => 3600 ); // Max 1 hour
	$client = new Zend\Http\Client( TESTSUITE_URI . '?command=PollTest&classPath=' . $test->ClassPath .'&sessionId=' . $test->SessionId, $options );
	$response = $client->send();
	$contents = $response->getBody();
	$test->Time = microtime(true) - $startTime;
	
	if( !$contents ) {
		throw new Exception( 'The '.$test->ClassPath.' test did not return any results. '.
			'The PHP process might have exit unexpectedly. Check php.log and server logging.' );
	}
	
	$xml = new SimpleXMLElement($contents);
	
	// if xml is not correct, the result is error
	if (! isset($xml->TestCase[0]->TestResults[0]->TestResult)){
		$test->Status = 'error';
		$test->Message = 'Unknown error';
	} else {
		// one test can have multiple results, if one of them is error the result is failure
		// assume we don't have multiple NOTINSTALLED, OK, INFO and WARN messages
		$failure = false;
		$failureMsg = '';
		foreach ($xml->TestCase[0]->TestResults[0]->TestResult as $testResult){
			switch ($testResult->Status){
				case 'FATAL':
				case 'ERROR':
					if (! $failure){
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
			$test->ExtendedMessage .= $testResult->Status . ': ' . $testResult->Message;
		}
		if ($failure){
			$test->Status = 'failure';
			$test->Message = $failureMsg;
		}
	}
}

function writeJUnitTestResultReport($outputDir, $testSuite, $tests)
{
	$fileName = 'TEST-' . $testSuite . '.xml';
	$filePath = $outputDir . DIRECTORY_SEPARATOR . $fileName;
	echo 'Writing test results into: '.$filePath.PHP_EOL;
	
	$domDoc = new DOMDocument();
	
	$root = $domDoc->createElement('testsuite');
	$root = $domDoc->appendChild($root);
	$root->setAttribute('name', $testSuite);
	$root->setAttribute('tests', count($tests));
	
	$errors = 0;
	$failures = 0;
	$skipped = 0;
	$time = 0;
	foreach ($tests as $test){
		$testName = preg_replace('|/\\\\|', '_', $test->ClassPath);
		
		$testCase = $domDoc->createElement('testcase');
		$testCase = $root->appendChild($testCase);
		
		$testCase->setAttribute('classname', $testName);
		$testCase->setAttribute('name', $test->DisplayName);
		$testCase->setAttribute('time', $test->Time);
		$testCase->setAttribute('status', $test->Status);
		
		switch ($test->Status){
			case 'error':
				$errorEl = $domDoc->createElement('error');
				$errorEl = $testCase->appendChild($errorEl);
				$errorEl->setAttribute('message', $test->Message);
				$textEl = $domDoc->createTextNode($test->ExtendedMessage);
				$errorEl->appendChild($textEl);
				$errors++;
				break;
			case 'failure':
				$failureEl = $domDoc->createElement('failure');
				$failureEl = $testCase->appendChild($failureEl);
				$failureEl->setAttribute('message', $test->Message);
				$textEl = $domDoc->createTextNode($test->ExtendedMessage);
				$failureEl->appendChild($textEl);
				$failures++;
				break;
			case 'skipped':
				$skipped++;
				break;
		}
		$time += $test->Time;
	}
	
	$root->setAttribute('failures', $failures);
	$root->setAttribute('errors', $errors);
	$root->setAttribute('skipped', $skipped);
	$root->setAttribute('time', $time);
	
	$domDoc->formatOutput = true;
	file_put_contents($filePath, $domDoc->saveXML());
}
$argv = $GLOBALS['argv'];
$testSuite = 'HealthCheck2';
if (isset($argv[1])){
	$testSuite = $argv[1];
}

$outputDir = TEMPDIRECTORY;
if (isset($argv[2]) && is_dir($argv[2])){
	$outputDir = $argv[2];
}

try {
	$tests = getTests($testSuite);
	foreach ($tests as $test)
	{
		pollTest($test);
	}
} catch (Exception $e) {
	// show exceptions as one test with error
	$test = new WW_Test();
	$test->ClassPath = $testSuite;
	$test->DisplayName = $testSuite;
	$test->Status = 'error';
	$test->Message = $e->getMessage();
	$test->ExtendedMessage = $e->getMessage();
	$tests = array($test);
}

writeJUnitTestResultReport($outputDir, $testSuite, $tests);
