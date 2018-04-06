<?php
/**
 * HttpServerEncoding TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 * Checks FileStore related options: ATTACHMENTDIRECTORY, EXPORTDIRECTORY, TEMPDIRECTORY
 *
 * @package SCEnterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_FileStore_TestCase extends TestCase
{
	public function getDisplayName() { return 'File Storage'; }
	public function getTestGoals()   { return 'Checks if uploaded files can be stored at application server or in database. '; }
	public function getTestMethods() { return 'Checks if ATTACHMENTDIRECTORY, EXPORTDIRECTORY, TEMPDIRECTORY, PERSISTENTDIRECTORY, AUTOCOMPLETEDIRECTORY options are configured correctly at configserver.php. Usage of slashes are checked as well as folder existence. When FILE option is used, it tries to create and remove subfolders to determine access rights.'; }
    public function getPrio()        { return 5; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();

		// Check syntax of ATTACHMENTDIRECTORY (for FILE storage) option
		if( !$utils->validateDefines( $this, array('ATTACHMENTDIRECTORY'), 'config.php' ) ) {
			return;
		}
		$help = 'Make sure that the '.ATTACHMENTDIRECTORY.' folder exists or that the correct folder path is configured in the ATTACHMENTDIRECTORY option.'.
		    	'<br/>Note that the folder path is case sensitive.<br/> Under Windows, the drive letter should be upper case.<br/> Make sure that the Web Server has read and write access to the folder.';
		if( !$utils->validateFilePath( $this, ATTACHMENTDIRECTORY, $help ) ) {
			return;
		}

		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		if(!FolderUtils::isDirWritable( ATTACHMENTDIRECTORY )){
			$this->setResult( 'ERROR', 'The '.ATTACHMENTDIRECTORY.' (ATTACHMENTDIRECTORY) folder is not writable.', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', ATTACHMENTDIRECTORY.'/wwtest folder removed.');
		
		// Check syntax of EXPORTDIRECTORY option
		if ( !$this->validateDirOfDefine('EXPORTDIRECTORY', true) ) {
			return;
		}

		// Check syntax of TEMPDIRECTORY option
		if ( !$this->validateDirOfDefine('TEMPDIRECTORY', false) ) {
			return;
		}
		
		// Count files in TEMPDIRECTORY folder to signal potential performance issues (BZ#18489)
		$tempFiles = count( glob(TEMPDIRECTORY.'/*') ); 
		if( $tempFiles > 100 ) {
			$this->setResult( 'WARN', 'There are many files ('.$tempFiles.') in the TEMPDIRECTORY folder. This could have performance impact. Please clear the folder.' );
		}
		LogHandler::Log('wwtest', 'INFO', 'Files in TEMPDIRECTORY folder counted: '.$tempFiles );

		// Check syntax of PERSISTENTDIRECTORY option
		if ( !$this->validateDirOfDefine('PERSISTENTDIRECTORY', false) ) {
			return;
		}

		// Check syntax of AUTOCOMPLETEDIRECTORY option
		if( !$this->validateDirOfDefine( 'AUTOCOMPLETEDIRECTORY', false) ) {
			return;
		}
    }

	/**
	 * Validates a define which contains path. It checks if the path contains an ending '/', if needed. Furthermore
	 * the parent directory is validated. If the define is correct it checks if the folder exists. If not, an url is
	 * generated which will be returned in the error message. This url can be used to automatically create the folder.
	 * @param string $defineName Name of the define.
	 * @param bool $endSlash If the directory must have an ending '/'.
	 * @return boolean true if the define contains a correct path and the folder exists and is writable. Else, false.
	 * n
	 */
	private function validateDirOfDefine( $defineName, $endSlash )
	{
		// Check syntax of the DEFINE 
		require_once BASEDIR . '/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		if( !$utils->validateDefines( $this, array( $defineName ), 'config.php' ) ) {
			return false;
		}
		
		$directory = constant($defineName);
		
		$help = 'Check the Admin Guide how to configure the '.$defineName.' option in config.php.';
		$help .= $endSlash ? ' Make sure it has an ending slash.' : ' Make sure it has NO ending slash.';  
		$validateOption = WW_Utils_TestSuite::VALIDATE_PATH_ALL & ~WW_Utils_TestSuite::VALIDATE_PATH_FILE_EXISTS; // Folder may not exist. 
		$validateOption = $endSlash ? $validateOption & ~WW_Utils_TestSuite::VALIDATE_PATH_NO_SLASH : $validateOption;
		if( !$utils->validateFilePath( $this, $directory, $help, true, 'ERROR', $validateOption ) ) { 
			return false;
		}

		if ( $endSlash ) { // Path is checked, now remove ending slash for further testing.
			$directory = substr( $directory, 0, strlen( $directory )-1 ); 
		}
		
		$this->validateParentDirectory( $directory, $defineName );
	
		if( !$utils->dirPathExists( $directory ) ){
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$testFile = dirname(__FILE__).'/createTempFolder.php';
			$url = WW_Utils_UrlUtils::fileToUrl( $testFile, 'server', false );
			//$url .= '?define='.$defineName;
			$url .= '?path='.htmlentities($directory);
			$create = ' Please run the <a href="'.$url.'" target="_blank">Create folder</a> page that will automatically create the missing folder.';
			$this->setResult( 'ERROR', 'The '.$directory.' folder does not exist or is not a folder.', $create );
			return false;
		}
		LogHandler::Log('wwtest', 'INFO', 'The '.$defineName.' folder exists and is a directory' );

		$help = 'Make sure the '.$directory.' folder exists and is writable from the Webserver.';
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		if(!FolderUtils::isDirWritable($directory)){
			$this->setResult( 'ERROR', 'The '.$directory.' folder is not writable.', $help );
			return false;
		}
		LogHandler::Log('wwtest', 'INFO', 'The '.$directory.' folder is writable.' );

		return true;
	}

	/**
	 * Checks if the parent directory of passed directory is writable.
	 *
	 * @param string $directory Path of the directory.
	 * @param string $define Name of the define
	 * @return void True if parent is valid, else false.
	 */
	private function validateParentDirectory( $directory, $define )
	{
		// Check existence and write access for parent(!) of $directory folder
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$parentDir = FolderUtils::extractParentFolder( $directory );
		$help = 'Make sure the '.$parentDir.' folder (parent of '.$define.') exists and is writable from the webserver.';

		require_once BASEDIR . '/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		if( !$utils->validateFilePath( $this, $parentDir, $help ) ) {
			return;
		}

		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		if(!FolderUtils::isDirWritable( $parentDir )){
			$this->setResult( 'ERROR', 'The '.$parentDir.' (parent of '.$define.') folder is not writable.', $help );
			return;
		}
	}	

}
