<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Script that runs TestSuite tests (of Enterprise Server) over HTTP and converts the output to JUnit notation.
 * The output is a JUnit test report and is returned in XML format over HTTP.

 * The script itself talks to Enterprise Server (also over HTTP) and does a HTTP request per test case.
 * Since all tests are executed in the context of the waiting caller, this method is suitable for test scripts
 * that take less than 1h execution time all together. This is especially suiteable for build tests shipped with
 * server plugins that are build separately (such as the Content Station plugin) since it can be called over HTTP
 * from another machine (e.g. Jenkins slave) that handles the automated testing and test reporting.
 *
 * For more information, see header of testsuite/TestSuiteJunitClient.class.php module.
 * Note that there is a fellow script junicliclient.php that allows you to run the tests over CLI.
 *
 * Usage:
 *    http://<root>/Enterprise/server/wwtest/testsuite/junithttpclient.php?testSuite=<TestSuite>
 *
 * The <TestSuite> parameter can be either a top level suite or a path to a child suite.
 */
require_once __DIR__.'/TestSuiteJunitClient.class.php';

$client = new WW_TestSuite_JunitClient();
$content = $client->handle( $_GET['testSuite'] );

header( 'Content-Type: text/xml; charset=UTF-8' );
echo $content;
