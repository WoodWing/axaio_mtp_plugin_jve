<?php
/**
 * Test script
 *
 * Usage
 * Test script must be run from the command line and on the unencrypted server code
 *
 * php testphpcodingcli.php <output directory>
 */

require_once BASEDIR . '/config/config.php';
/**
 * We need to overrule the default config.php.
 */
define('BASEDIR', realpath(dirname(__FILE__) . '/../../'));
require_once BASEDIR . '/config/configserver.php';

// Create a new session id
require_once BASEDIR . '/server/utils/NumberUtils.class.php';
$sessionId = NumberUtils::createGUID();

// Only one testsuite is supported
$testSuite = "PhpCodingTest";
try {
	$tests = getTests($testSuite, $sessionId);

	foreach ($tests as $test) {
		require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteFactory.class.php';
		$contents = TestSuiteFactory::runTest($sessionId, $test->ClassPath);

		$xml = new SimpleXMLElement($contents);

		// if xml is not correct, the result is error
		if (!isset($xml->TestCase[0]->TestResults[0]->TestResult)) {
			$test->Status = 'error';
			$test->Message = 'Unknown error';
		} else {
			// one test can have multiple results, if one of them is error the result is failure
			// assume we don't have multiple NOTINSTALLED, OK, INFO and WARN messages
			$failure = false;
			$failureMsg = '';
			foreach ($xml->TestCase[0]->TestResults[0]->TestResult as $testResult) {
				switch ($testResult->Status) {
					case 'FATAL':
					case 'ERROR':
						if (!$failure) {
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
			if ($failure) {
				$test->Status = 'failure';
				$test->Message = $failureMsg;
			}
		}
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

$argv = $GLOBALS['argv'];

// The default output directory is the current directory (from where the test is started)
$outputDir = '.';
if (isset($argv[1]) && is_dir($argv[1])) {
	$outputDir = $argv[1];
}

writeJUnitTestResultReport($outputDir, 'PhpCodingTest', $tests);

// ===

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

function getTestsFromXML(&$tests, $xmlTests, $sessionId)
{
	foreach ($xmlTests as $xmlTest) {
		// a test can contain sub tests
		if ($xmlTest->Type == 'TestSuite') {
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

function getTests($testSuite, $sessionId)
{
	// Get the test directly from the TestSuiteFactory because we do not run within a webserver
	require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteFactory.class.php';
	$contents = TestSuiteFactory::getTestsAsXml($testSuite);

	$xml = new SimpleXMLElement($contents);

	$tests = array();
	if (isset($xml->Test[0]->Tests[0]->Test)) {
		getTestsFromXML($tests, $xml->Test[0]->Tests[0]->Test, $sessionId);
	} else {
		// testsuite doesn't contain tests
		// maybe it contains an error
		$error = '';
		if (isset($xml->Description)) {
			$error = $xml->Description;
		} else {
			$error = 'Unable to parse contents, contents: ' . $contents;
		}
		throw new Exception($error);
	}

	return $tests;
}

function writeJUnitTestResultReport($outputDir, $testSuite, $tests)
{
	$fileName = 'TEST-' . $testSuite . '.xml';
	$filePath = $outputDir . DIRECTORY_SEPARATOR . $fileName;
	echo 'Writing test results into: ' . $filePath . PHP_EOL;

	$domDoc = new DOMDocument();

	$root = $domDoc->createElement('testsuite');
	$root = $domDoc->appendChild($root);
	$root->setAttribute('name', $testSuite);
	$root->setAttribute('tests', count($tests));

	$errors = 0;
	$failures = 0;
	$skipped = 0;
	$time = 0;
	foreach ($tests as $test) {
		$testName = preg_replace('|/\\\\|', '_', $test->ClassPath);

		$testCase = $domDoc->createElement('testcase');
		$testCase = $root->appendChild($testCase);

		$testCase->setAttribute('classname', $testName);
		$testCase->setAttribute('name', $test->DisplayName);
		$testCase->setAttribute('time', $test->Time);
		$testCase->setAttribute('status', $test->Status);

		switch ($test->Status) {
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
