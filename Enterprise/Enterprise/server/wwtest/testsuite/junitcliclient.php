<?php
/**
 * @since      v10.2.0 This module is a replacement for the old wwtest/testcli.php and wwtest/testphpcodingcli.php scripts.
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Script that runs TestSuite tests (of Enterprise Server) on the commandline and converts the output to JUnit notation.
 * The output is a JUnit test report which is written in an XML file in the output folder. The file has a "TEST-" prefix.
 *
 * The script talks to Enterprise over HTTP and does a HTTP request per test case. This is done for all tests
 * that require Enterprise to be installed and configured. There is one exception, which is the PhpCodingTest. This test
 * requires Enterprise to be unencoded and not installed since that test is running on the build machine. In this
 * exception, the test includes Enterprise modules directly and executes the tests one by one.
 *
 * For more information, see header of testsuite/TestSuiteJunitClient.class.php module.
 * Note that there is a fellow script junihttpclient.php that allows you to run the tests over HTTP.
 *
 * Usage:
 *    php testsuite/junitcliclient.php <TestSuite> <OutputFolder>
 *
 * The <TestSuite> parameter can be either a top level suite or a path to a child suite.
 */

require_once __DIR__.'/TestSuiteJunitClient.class.php';

$argv = $GLOBALS['argv'];
$testSuite = 'HealthCheck2'; // default suite
if( isset($argv[1]) ) {
	$testSuite = $argv[1];
}
$outputDir = null;
if( isset( $argv[2] ) && is_dir( $argv[2] ) ) {
	$outputDir = $argv[2];
}
$coldTest = $testSuite == 'PhpCodingTest';

$client = new WW_TestSuite_JunitClient();
$content = $client->handle( $testSuite, $coldTest );

if( is_null($outputDir) ) {
	$outputDir = TEMPDIRECTORY;
}
$outputFile = $outputDir.DIRECTORY_SEPARATOR.'TEST-' . str_replace( '/','_', $testSuite ) . '.xml';
echo 'Writing test results into: '.$outputFile.PHP_EOL;
file_put_contents( $outputFile, $content );
