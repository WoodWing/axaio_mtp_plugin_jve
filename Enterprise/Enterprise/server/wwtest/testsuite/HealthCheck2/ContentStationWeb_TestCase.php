<?php
/**
 * ContentStationWeb TestCase class that belongs to the TestSuite of wwtest.
 * It checks the existence of ContentStation web directory and ensure that 
 * web/inet user has access to the directory.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/utils/UrlUtils.php';


class WW_TestSuite_HealthCheck2_ContentStationWeb_TestCase extends TestCase
{
	public function getDisplayName() { return 'Content Station Web'; }
	public function getTestGoals()   { return 'To ensure that Content Station Web is installed.'; }
	public function getTestMethods() { return 'Checks if the contentstation/index.html file can be read through HTTP.'; }
    public function getPrio()        { return 22; }
	
	final public function runTest()
	{
		$contentStationWeb = BASEDIR.'/contentstation/index.html';
		$contentStationWebUrl = WW_Utils_UrlUtils::fileToUrl( $contentStationWeb, 'contentstation' );
		$handle = @fopen( $contentStationWebUrl, 'r' ); // suppress error since we already report below
		if( !$handle ) {
		   $this->setResult( 'NOTINSTALLED', 'Unable to access the Content Station Web folder "'.$contentStationWebUrl . '".'. PHP_EOL .
		   							  'Please ensure that the "contentstation" directory is placed in the "' . BASEDIR . '" folder ' .
		   							  'and that the internet user (www/inet) has read access to it.');
		   return;
		}
		fclose($handle);
	}
}

