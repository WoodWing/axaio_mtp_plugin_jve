<?php
/**
 * ExifTool TestCase class that belongs to the TestSuite of wwtest.
 *
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since      v10.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_ExifTool_TestCase extends TestCase
{
	public function getDisplayName() { return 'ExifTool MetaData'; }
	public function getTestGoals()   { return 'Checks if correct version of ExifTool is installed.'; }
	public function getTestMethods() { return 'Runs the tool in the commandline and requests for its version.'; }
   public function getPrio()        { return 30; }

	final public function runTest()
	{
		require_once BASEDIR.'/server/plugins/ExifTool/ExifTool_MetaData.class.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';

		$utils = new WW_Utils_TestSuite();
		$help = 'Check the EXIFTOOL_APP_PATH option at the configserver.php file.';

		// Check if there is a definition made for the file path.
		if( !$utils->validateDefines( $this, array( 'EXIFTOOL_APP_PATH' ),
			'configserver.php', 'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_ALL, $help ) ) {
			return;
		}

		// Check if the configured file path does exists on disk.
		if( !$utils->validateFilePath( $this, EXIFTOOL_APP_PATH, $help, true ) ) {
			return;
		}

		// Check if the ExifTool executable can be found on disk.
		$execPath = ExifTool_MetaData::composeExifToolExecutableFilePath();
		$help .= " Using the following ExifTool installation path: $execPath";
		if( !$utils->validateFilePath( $this, $execPath, $help, false ) ) {
			return;
		}

		// Check if PHP can run the ExifTool.
		if( !is_executable( $execPath ) ) {
			$this->setResult( 'ERROR', "The ExifTool does not seems to be executable. ".
				"Make sure the internet user has execute rights.", $help );
		}

		// Check the version of the ExifTool.
		$returnStatus = 0;
		$installedVersion = ExifTool_MetaData::callExifTool( '-ver', 'check version', $returnStatus );
		if( !$installedVersion ) {
			$this->setResult( 'ERROR',
				"Could not detect the installed version. ".
				"Error code returned from command line: $returnStatus.", $help );
			return;
		}
		$minimumVersion = '10.0';
		if( version_compare( $installedVersion, $minimumVersion ) < 0 ) {
			$this->setResult( 'ERROR',
				"Required minimum version is $minimumVersion. ".
				"Installed version is $installedVersion. ".
				"Please upgrade your ExifTool installation.", $help );
			return;
		}
	}
}