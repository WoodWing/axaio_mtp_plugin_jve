<?php
/**
 * Tika TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_SimpleFileSystem_TestCase extends TestCase
{
	public function getDisplayName() { return 'Simple File System'; }
	public function getTestGoals()   { return 'Checks if a folder location is configured and is readable. '; }
	public function getTestMethods() { return 'Checks if the SFS_LOCALCONTENTFOLDER option is '.
												'configured in the plugin\'s config.php file and '.
												'if the inet/www user has read access to the folder.'; }
    public function getPrio()        { return 0; }
	
	final public function runTest()
	{
		require_once dirname(__FILE__) . '/../../config.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$utils = new WW_Utils_TestSuite();
		
		// Check syntax of SFS_LOCALCONTENTFOLDER option and if points to a valid directory.
		$configFile = realpath( dirname(__FILE__) . '/../../config.php' );
		$help = 'Please check the SFS_LOCALCONTENTFOLDER option in the "'.$configFile.'" file.';
		if( !$utils->validateDefines( $this, array('SFS_LOCALCONTENTFOLDER'), 'configserver.php', 
			'ERROR', WW_Utils_TestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
			return;
		}
		if( !$utils->validateFilePath( $this, SFS_LOCALCONTENTFOLDER, $help, true, 'ERROR',
			WW_Utils_TestSuite::VALIDATE_PATH_ALL & ~WW_Utils_TestSuite::VALIDATE_PATH_NO_SLASH ) ) { // must have slash at end
			return;				
		}
		
		// Check if inet/www user has read access to the SFS_LOCALCONTENTFOLDER folder.
		$help = 'Please check the file access rights of the internet user (IUSR/www).';
		if( !is_readable(SFS_LOCALCONTENTFOLDER) ) {
			$msg = 'The folder "'.SFS_LOCALCONTENTFOLDER.'" does not exist or can not be read.';
			$this->setResult( 'ERROR', $msg, $help );
		}
		if( !FolderUtils::isDirWritable(SFS_LOCALCONTENTFOLDER) ) {
			$msg = 'No write access to the folder "'.SFS_LOCALCONTENTFOLDER.'".';
			$this->setResult( 'ERROR', $msg, $help );
		}
	}
}