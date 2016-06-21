<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_AdobeInCopy_TestSuite extends TestSuite
{
	public function getDisplayName() { return 'Adobe InCopy files to HTML conversion'; }
	public function getTestGoals()   { return 'Checks if the conversion of WWCX and WCML files to HTML files is working.'; }
	public function getTestMethods() { return 'Test if the expected output is given by the converters.'; }
    public function getPrio()        { return 5200; }
}