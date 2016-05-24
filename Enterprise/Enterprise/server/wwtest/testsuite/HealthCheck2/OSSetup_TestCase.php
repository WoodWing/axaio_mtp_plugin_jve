<?php
/**
 * Operating Sysstem TestCase class that belongs to the TestSuite of wwtest.
 * It checks the operating system is configured well and permissions and settings
 * are correect. 
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_OSSetup_TestCase extends TestCase
{
	private $tmpFile = null;
	private $osInfo = '';
	private $tmpDir = null;

	public function getDisplayName() { return 'Operating System'; }
	public function getTestGoals()   { return 'To ensure that the OS allows Enteprise Server to run shell commands and has access to the system temporary directory.'; }
	public function getTestMethods() { return 'Checks if rights and permissions are correct.'; }
    public function getPrio()        { return 26; }
	
	final public function runTest()
	{
		// Set default OS description
		switch ( OS) {
			case 'WIN':
				$this->osInfo = 'Microsoft Windows';
				break;
			case 'UNIX':
				$this->osInfo = 'Mac OS X';
				break;
			case 'LINUX':
				$this->osInfo = 'Linux';
				break;
		}		

		// Check if the PHP-process itself can create/write/read delete temporary files.
		if ( $this->getTempFileName() ) {
			if ( $this->writeToTemporaryFile() ) {
				$this->readTemporaryFile();
			}
		}

		if ( $this->tmpFile ) {
			$this->deleteTempFile();
			$this->getTempFileName();
			// Create new one for next step.
			// New one is not created if creating a temporary file failed during
			// the first step.
		} 		

		// Check if external commands can be executed.
		if ( $this->testExternalCommand()) {
			if ( $this->tmpFile ) {
				$this->writeToTemporaryFileExternal();
			}
		}
		
		if ( $this->tmpFile ) {
			$this->deleteTempFileExternal();
		} 

		LogHandler::Log('wwtest', 'INFO', 'Operating System checked: ' . $this->osInfo);
	}

	/**
	 * Checks if it is possible to execute a command via shell and return the
	 * complete output as a string.
	 * 
	 * @return boolean true if success else false. 
	 */
	private function testExternalCommand()
	{
		$help = '';
		$output = '';
		$command = '';
		$result = false;
		
		switch ( OS) {
			case 'WIN':
				$command = "ver";
				$help = "Check if internet user (IUSR) has permission to execute cmd.exe.";
				break;
			case 'UNIX':
				$command = "uname -a";
				break;
			case 'LINUX':
				$command = "uname -a";
				break;
		}		
		
		$output = shell_exec($command);
		$output = trim($output);
		if (empty($output)) {
			$this->setResult( 'ERROR',  "Executing external command shell_exec($command) failed." , $help );	
		} else {
			$this->osInfo = $output;
			$result = true;
		}

		return $result;
	}

	/*
	* Returns temporary file name with prefix 'wwt' (= WWTest)
	*
	* @return boolean true if success else false.
	*/
	private function getTempFileName()
	{
		$this->tmpDir = sys_get_temp_dir();
		$this->tmpFile = tempnam( $this->tmpDir, 'wwt' ); //WWTest
		if( !$this->tmpFile ) {
			$help = 'Please check if www/inet user has write access to the system temp folder.';
			$this->setResult( 'ERROR',  'Enterprise could not generate temporary file at "'.$this->tmpDir.'" folder. ' , $help );	
			return false;
		}

		$old_umask = umask(0); // Needed for mkdir, see http://www.php.net/umask
		chmod($this->tmpFile, 0777);	 // We cannot alway set access with mkdir because of umask	
		umask($old_umask);
		
		return true;
	}

	/* Checks if the php-process can write to the system temporary directory.
	* 
	* @return boolean true if success else false.
	*/
	private function writeToTemporaryFile()
	{
		$handle = fopen($this->tmpFile, 'wb');
		$result = fwrite($handle, '0123456789');
		if ($result === false) {
			$help = 'Please check if www/inet user has write access to the system temp folder.';
			$this->setResult( 'ERROR',  "Enterprise could not write to temporary file $this->tmpFile. " , $help );		
		}

		fclose($handle);

		return $result ? true : false;
	}

	/* Checks if the php-process can read from the system temporary directory.
	* 
	* @return boolean true if success else false.
	*/
	private function readTemporaryFile()
	{
		$handle = fopen($this->tmpFile, 'r');
		$result = fread($handle, filesize( $this->tmpFile));

		if (!$result) {
			$help = 'Please check if www/inet user has read access to the system temp folder.';
			$this->setResult( 'ERROR',  "Enterprise could not read the temporary file $this->tmpFile. " , $help );		
		}

		fclose($handle);
		
		return $result ? true : false;
	}

	/* Checks if the shell-process can write to the system temporary directory.
	* 
	* @return boolean true if success else false.
	*/
	private function writeToTemporaryFileExternal()
	{
		$help = '';

		switch ( OS) {
			case 'WIN':
				$command = "ver > " .  escapeshellarg($this->tmpFile);
				$help = "Check if user SERVICE or Everyone has write permission on system temp folder ($this->tmpDir).";
				break;
			case 'UNIX':
				$command = "uname -a > " . escapeshellarg($this->tmpFile);
				break;
			case 'LINUX':
				$command = "uname -a > " . escapeshellarg($this->tmpFile);
				break;
		}		


		/*$output = */shell_exec($command);

		if (filesize( $this->tmpFile ) == 0 ) {
			$this->setResult( 'ERROR',  "Enterprise could not write to temporary file $this->tmpFile by external command shell_exec($command)." , $help );	
			return false;
		}	
		
		return true;
	}

	/* Checks if the shell-process can delete the temporary file from 
	*  the system temporary directory.
	* 
	* @return boolean true if success else false.
	*/
	private function deleteTempFileExternal()
	{
		$help = '';

			switch ( OS) {
			case 'WIN':
				$command = "del " .  escapeshellarg($this->tmpFile);
				$help = "Check if user SERVICE or Everyone has delete permission on system temp folder ($this->tmpDir).";
				break;
			case 'UNIX':
				$command = "rm " . escapeshellarg($this->tmpFile);
				break;
			case 'LINUX':
				$command = "rm " . escapeshellarg($this->tmpFile);
				break;
		}		


		/*$output = */shell_exec($command);

		if (file_exists( $this->tmpFile )) {
			$this->setResult( 'ERROR',  "Enterprise could not delete the temporary file $this->tmpFile by external command shell_exec($command)." , $help );	
			return false;
		}	
		
		return true;
	}	
	
	/* Checks if the php-process can delete an entry from the system temporary directory.
	* 
	* @return boolean true if success else false.
	*/
	private function deleteTempFile()
	{
		$result = unlink($this->tmpFile);
		$help = "Please check if www/inet user has delete right on the system temp folder ($this->tmpDir).";
		if (!$result) {
			$this->setResult( 'ERROR',  "Enterprise could not delete temporary file $this->tmpFile." , $help );		
		}
	}
}

