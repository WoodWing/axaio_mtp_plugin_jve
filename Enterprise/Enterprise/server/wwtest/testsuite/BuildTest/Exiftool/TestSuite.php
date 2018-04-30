<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Exiftool_TestSuite extends TestSuite
{
	public function getDisplayName()
	{
		return 'Exiftool';
	}

	public function getTestGoals()
	{
		return 'Extracts image metadata and checks if the dimensions values are correctly stored. ';
	}

	public function getTestMethods()
	{
		return 'Database images are created and the stored dimensions are validated.';
	}

	public function getPrio()
	{
		return 4500;
	}
}