<?php

$command = isset($_REQUEST['command']) ? $_REQUEST['command'] : 'LoadTest';

// When the Health Page is loaded, invoke the ionCube Loader Wizard to validate the ionCube Loader.
// Developers may want to comment out the if-part below to ignore invoking the ionCube Loader Wizard,
// since no loader is needed for an non-encoded installation of Enterprise Server.
// Note that this test can NOT be moved to a test suite case on the Health Check page because
// including the config.php file is already problematic since it includes ionCube encoded files.
if( $command == 'LoadTest' ) {
	if( !isset($_REQUEST['testSuite']) || $_REQUEST['testSuite'] == 'HealthCheck2' ) {
		// The wwioncubetest.php is an Enterprise customization of the standard ionCube Loader Wizard.
		// By including this file, its run() function is automatically called which starts the wizard.
		// Regardsless whether or not the ionCube loader is correctly installed, the wizard outputs
		// a HTML page (e.g. with instructions). When the loader is correct, that HTML is suppressed
		// because the Health Check page is loaded hereafter. When the loader is NOT correct, the HTML
		// page needs to be shown. In that case, the run() does exit by itself.
		ob_start(); // supress the HTML output
		require_once dirname(__FILE__).'/wwioncubetest.php';
		if( ob_get_contents() ) {
			while( ob_get_clean() );
		}
	}
}

require_once dirname(__FILE__).'/../../config/config.php';
// Perform the tests from testsuite folder...

require_once BASEDIR.'/server/secure.php';
set_time_limit(3600);

// Dispatch command
switch( $command ) {
	case 'LoadTest': // Request to return the html page (which then will fire InitTest command).
		$testSuite = isset($_REQUEST['testSuite']) ? $_REQUEST['testSuite'] : 'HealthCheck2';
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$tpl = HtmlDocument::loadTemplate( 'testsuite.htm' );
		print HtmlDocument::buildDocument( $tpl, true, 'onload="initTest(\''.formvar($testSuite).'\');"', false, false, true );
	break;

	case 'InitTest': // Request to return all tests at testsuite folder
		LogHandler::Log( 'wwtest', 'INFO', 'Starting wwtest' );
		$testSuite = $_REQUEST['testSuite'];
		require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteFactory.class.php';
		require_once BASEDIR.'/server/utils/ZendOpcache.php';
		WW_Utils_ZendOpcache::clearOPcache();
		header( 'Content-Type: text/xml' );
		print TestSuiteFactory::getTestsAsXml( $testSuite );

	break;

	case 'CreateSession': // Called when user clicks the 'Test' or 'Retest' button.
		// Make new test session. Do NOT confuse with Enterprise sessions (tickets)...!
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		$sessionId = NumberUtils::createGUID();
		LogHandler::Log( 'wwtest', 'INFO', 'Created test session id: '.$sessionId );
		print $sessionId; // return to Ajax client as plain text
	break;

	case 'PollTest': // Request to run a single test from testsuite folder
		$classPath = $_REQUEST['classPath'];
		$sessionId = $_REQUEST['sessionId'];
		LogHandler::Log( 'wwtest', 'INFO', 'Running test: '.$classPath );
		require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteFactory.class.php';
		header( 'Content-Type: text/xml' );
		print TestSuiteFactory::runTest( $sessionId, $classPath );
	break;
}
