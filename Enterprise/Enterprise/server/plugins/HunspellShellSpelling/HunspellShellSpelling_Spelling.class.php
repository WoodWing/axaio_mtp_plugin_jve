<?php
/**
 * Spelling checker and suggestions integrating Hunspell through command shell.
 *
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/interfaces/plugins/connectors/Spelling_EnterpriseConnector.class.php';

class HunspellShellSpelling_Spelling extends Spelling_EnterpriseConnector
{
	private $tmpFile = null;
	private $stdErrOutputFile = null;
	private $installedDictionaries = null;

	/**
	 * Refer to abstract class checkSpelling() header.
	 * @inheritdoc
	 */
	public function checkSpelling( array $dictionaries, array $wordsToCheck ) 
	{
		$context = $this->prepareForCommandExecutions();
		$this->tmpFile = $this->getTempFileName();
		$this->stdErrOutputFile = $this->getTempFileName(); 
		$command = $this->getCommandForSpellChecking( $dictionaries );

		// When array of $wordsToCheck (all words to be checked) is sent in, 
		// Only 250 words are picked up each time from $wordsToCheck for Hunspell checking.
		// This is due to Hunspell cannot handle too many words,
		// at certain amount of words, Hunspell fails but no idea yet what's exactly the cause.
		// There's no certain pattern or certain word limit that will fail the spell checking.
		// So, the safest for now is to send in 250 words each time for Hunspell checking.
		$totalWordsToCheck = count( $wordsToCheck );
		$allMistakeWords = array();
		for( $counter=0; $counter < $totalWordsToCheck; $counter+=250 ){
			$wordsToCheckNow = array_slice( $wordsToCheck, $counter, 250 );
		
			$mistakeWords = array();
			if( $this->tmpFile ){
				$stringToCheck = implode(' ', $wordsToCheckNow );
				
				file_put_contents( $this->tmpFile, $stringToCheck );
			} else {
				$detail = 'Hunspell Shell Spelling could not create temporary file "'.$this->tmpFile.'" for writing. '.
					'Please check if www/inet user has write access to the system temp folder. ';
				throw new BizException( null, 'Server', $detail, 'Configuration error' );
			}
			
			$mistakeWords = trim( shell_exec( $command ));
			
			if( filesize( $this->stdErrOutputFile ) > 0 ) {
				$this->roundupAfterCommandExecutions( $context );
				$detail = 'Hunspell Shell Spelling engine failed to check spelling '.
					'for language(s): "'. implode( ',', $dictionaries ).'". '.
					'Error from spelling engine: "'.file_get_contents( $this->stdErrOutputFile ).'". '.
					'Command used: "'.$command.'". ';
				throw new BizException( null, 'Server', $detail, 'Configuration error' );
			}
			
			if( $mistakeWords ){
				$mistakeWords = mb_split( "[\r\n]", $mistakeWords );
				$allMistakeWords = array_merge( $allMistakeWords, $mistakeWords );
			}
		}
		$this->roundupAfterCommandExecutions( $context );
		@unlink( $this->tmpFile );
		@unlink( $this->stdErrOutputFile );
		return $allMistakeWords;
	}

	/**
	 * Refer to abstract class getSuggestions() header.
	 * @inheritdoc
	 */
	public function getSuggestions( array $dictionaries, $wordForSuggestions ) 
	{
		$context = $this->prepareForCommandExecutions();
		$this->stdErrOutputFile = $this->getTempFileName();
		
		if( OS == 'WIN' ){ // Need to change to Hunspell dir in Windows to run hunspell.exe
			$this->tmpFile = $this->getTempFileName();
			file_put_contents( $this->tmpFile, $wordForSuggestions );
		}
		
		$command = $this->getCommandForWordSuggestions( $dictionaries, $wordForSuggestions );
		
		$wordForSuggestions = trim( $wordForSuggestions );
		$mistakesWithSuggestions = shell_exec( $command );
		
		if( filesize( $this->stdErrOutputFile ) > 0 ){
			$this->roundupAfterCommandExecutions( $context );
			$detail = 'Hunspell Shell Spelling engine failed to get suggestions '.
				'for language(s): "'. implode( ',', $dictionaries ).'". '.
				'Error from spelling engine: "'.file_get_contents( $this->stdErrOutputFile ).'". '.
				'Command used: "'.$command.'". ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		$this->roundupAfterCommandExecutions( $context );
		@unlink( $this->stdErrOutputFile );
		@unlink ( $this->tmpFile );	// Only applicable when OS is WIN
				
		$mistakesWithSuggestions = mb_split("[\r\n]", $mistakesWithSuggestions );
		$suggestedWords = array();
		foreach( $mistakesWithSuggestions as $mistakeWithSuggestions ){
			if( mb_ereg_match( '^&', $mistakeWithSuggestions ) ){
				list( , $suggestions ) = mb_split(': ', $mistakeWithSuggestions );
				$suggestedWords = mb_split( ', ', $suggestions );
				$suggestedWords = array_slice( $suggestedWords, 0, $this->config['suggestions'] );
				break;
			}
		}
		return $suggestedWords;
	}
	
	/**
	 * Refer to abstract class getEngineVersion() header.
	 * @inheritdoc
	 */
	public function getEngineVersion()
	{
		$context = $this->prepareForCommandExecutions();
		$stdErrOutputFile = $this->getTempFileName();
		if( OS == 'WIN' ){
			$command = $this->getHunspellCmd( $this->config['location'] ) . ' -vv 2>' . escapeshellarg( $stdErrOutputFile ); 
		}else{
			$command = '"'.$this->config['location']. '" -vv 2>' . escapeshellarg( $stdErrOutputFile );
		}
		
		LogHandler::Log( 'Spelling', 'DEBUG', __CLASS__.'::getEngineVersion() runs shell command: '.htmlentities( $command) );
		$version = shell_exec( $command );
		if( filesize( $stdErrOutputFile ) > 0 ) {
			$message = 'Could not determined the version of the Hunspell Shell Spelling engine. '.
				'Error from spelling engine: "'.file_get_contents( $stdErrOutputFile ).'". '.
				'Command used: "'.$command.'". ';
			LogHandler::Log( 'Spelling', 'ERROR', $message );
			$version = '?';
		}
		$this->roundupAfterCommandExecutions( $context );
		@unlink( $stdErrOutputFile );

		// $version is now something like "@(#) International Ispell Version 3.2.06 (but really Hunspell 1.2.8)"
		$hunspellPos = strripos( $version, 'Hunspell ' );
		if( $hunspellPos ) {
			$charsToReplace = $hunspellPos + strlen('Hunspell ');
			$version = substr_replace( $version, '', 0, $charsToReplace );
			$version = trim( str_replace( ')', '', $version ) );
		}
		return $version;
	}
	
	/**
	 * Refer to abstract class getInstalledDictionaries() header.
	 * @inheritdoc
	 */	
	public function getInstalledDictionaries()
	{
		return $this->getInstalledDictionariesByCommand( true );
	}
	
	/**
	 *	Returns installed dictionaries together with the full path.
	 */
	public function getInstalledDictionariesAndPath()
	{
		return $this->getInstalledDictionariesByCommand( false );
	}
	
	/**
	 * Retrieves installed dictionary for Hunspell via command execution.
	 *
	 * @param bool $baseName True to return only base name of the dictionary; False to return the full path of dictionary.
	 * @throws BizException when failed to retrieves dictionary.
	 * @returns array of dictionary(s) (With / Without path) (Dictionaries returned are ALWAYS WITHOUT .aff or .dic extension)
	 */
	private function getInstalledDictionariesByCommand( $baseName )
	{
		$dictionaries = array();
		
		if( OS == 'WIN' ){ // For Windows, "hunspell -D" doesn't return all installed dictionaries but only one, so script has to retrieve the dictionaries from the dictionary path.
			$dictionaryPath = $this->getEnvVar( 'DICPATH' );
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			$this->installedDictionaries = array();
			$exclFolders = array();
			FolderUtils::scanDirForFiles( $this, $dictionaryPath, array('dic'), $exclFolders );
			$results = 	$this->installedDictionaries;				
		}else{// for OSX / Linux, script can use 'hunspell -D' to get the installed dictionaries
			$context = $this->prepareForCommandExecutions();
			$tmpFile = $this->getTempFileName();
			
			// The command "hunspell -D" will display the installed dictionaries and also wait for input words (words to check)
			// from the STDIN, so the command just pass in an empty file ($tmpFile) as word input
			// and Hunspell will return its loaded dictionary into STDERR (2) and 
			// so here the command redirect the results back into the $tmpFile. (Re-use the $tmpFile).
			$command = '"'.$this->config['location']. '" -D < ' . escapeshellarg($tmpFile) . ' 2> ' . escapeshellarg($tmpFile);
			LogHandler::Log( 'Spelling', 'DEBUG', __CLASS__.'::getInstalledDictionaries() runs shell command: '.htmlentities( $command));
			shell_exec( $command );

			$results = file_get_contents( $tmpFile );
			$results = mb_split( PHP_EOL, $results );
			
			$this->roundupAfterCommandExecutions( $context );
			@unlink( $tmpFile );
			

			if( count($results) == 1 ){ // Note: We do not check $dictionaries since that is validated by caller
				$detail = 'Could not retrieve dictionaries from Hunspell Shell Spelling engine. '.
					'Error from spelling engine: "'.print_r($results,true).'". '.
					'Command used: "'.$command.'". ';
				throw new BizException( null, 'Server', $detail, 'Configuration error' );
			}
			
		}	
		if( $results ) foreach( $results as $result ){
	       	if( file_exists( $result ) ) { // Happens on Windows
		       	if( substr( $result, -4 ) == '.dic' ){
	       			if( $baseName ){
	       				$dictionaries[] = basename( $result, '.dic' ); // without .dic extension
	       			}else{ // fullpath						
						$dictionaries[] = substr( $result, 0, -4 ); // without .dic extension
					}
				}
			} else if( file_exists( $result . '.dic' ) ) { // Happens on Mac/Linux
	      			if( $baseName ){
	      				$dictionaries[] = basename( $result );
	      			}else{ // fullpath
					$dictionaries[] = $result; // always without .dic extension, so don't have to do anything.
				}
			}
		}
		

		return $dictionaries;
	}
	
	/**
	* Called by parent class for each file found at the testdata folder.
	*
	* @param $filePath string  Full file path of the file.
	* @param $level    integer Current ply in folder structure of recursion search.
	*/
	public function iterFile( $filePath, $level )
	{		
		$this->installedDictionaries[] = $filePath;
	}

	// These three functions are called by parent class, but have no meaning here.
	public function skipFile( $filePath, $level ) {}
	public function iterFolder( $folderPath, $level ) {}
	public function skipFolder( $folderPath, $level ) {}
	
	/**
	* Retrieves requested environment variable and validate the values.
	* Typically needed for Windows only.
	* 
	* @param string $envVar Environment variable name to be requested. Possible names: 'DICPATH', 'DICTIONARY'
	* @return string Value of environment variable requested.
	* @throws BizException When env variable is not set or set with invalid value. 
	**/
	public function getEnvVar( $envVar )
	{		
		$envVarValue = getEnv( $envVar );		
		if( $envVar == 'DICPATH' ){
			// Check whether it is alerady set
			if( !$envVarValue ){
				$detail = 'Environment variable \'DICPATH\' is not set and is needed by Hunspell spell engine.'.
				'Please set with a valid Hunspell dictionary path. Reboot after setting the environment variable '.
				'for the changes to take effect.';					
				throw new BizException( null, 'Server', $detail, 'Configuration error' );
			}
		
			// Check whether it is a valid directory.
			if( !is_dir( $envVarValue ) ){
				$detail = 'Environment variable \'DICPATH\' is set with value \''. $envVarValue.'\' but ' .
							'it is not a valid path. Make sure it is set with a valid path and '.
							'reboot the machine after changing the environment variable for the changes to take effect.';
				throw new BizException( null, 'Server', $detail, 'Configuration error' );	
			}
		}
		
		if( $envVar == 'DICTIONARY' ){
			// Check whether it is alerady set
			if( !$envVarValue ){
				$detail = 'Environment variable \'DICTIONARY\' is not set and is needed by Hunspell spell engine.'.
				'Please set with a default Hunspell dictionary. Reboot after setting the environment variable '.
				'for the changes to take effect.';					
				throw new BizException( null, 'Server', $detail, 'Configuration error' );
			}
			
			// Check whether the DICTIONARY being set is from the list of dictionaries already installed	
			$installedDicts = $this->getInstalledDictionaries();			
			$found=false;
			if( $installedDicts ) foreach( $installedDicts as $installedDict ){
				if( $envVarValue == $installedDict ){
					$found = true;
					break; // found the installed dictionary set as env variable.
				}
			}	
			if( !$found ){
				$detail = 'Environment variable \'DICTIONARY\' is set with value \''. $envVarValue.'\' '.
							'but it is not a installed dictionary.';
				if( $installedDicts ){			
					$detail .= 'Choose a default dictionary from the list ['. implode(',', $installedDicts ).'] and '.
							'set it to \'DICTIONARY\'.Reboot the machine after resetting for the changes to take effect.';
				}else{
					$detail .= 'There is NO dictionaries installed.Please install the required dictionary for Hunspell and '.
							'set it to \'DICTIONARY\'.Reboot the machine after resetting for the changes to take effect.';
				}
				throw new BizException( null, 'Server', $detail, 'Configuration error' );	
			}		
		}
				
		return $envVarValue;
	}
	
	/*
	* Returns temporary file name with prefix 'esp' (= Enterprise SPelling)
	*
	* @return string temporary file name
	*/
	private function getTempFileName()
	{
		$tmpDir = sys_get_temp_dir();
		$tmpFile = tempnam( $tmpDir, 'esp' );
		if( !$tmpFile ) {
			$detail = 'Hunspell Shell Spelling could not generate temporary file at "'.$tmpDir.'" folder. '.
				'Please check if www/inet user has write access to the system temp folder. ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}

		$old_umask = umask(0); // Needed for mkdir, see http://www.php.net/umask
		chmod($tmpFile, 0777);	 // We cannot alway set access with mkdir because of umask	
		umask($old_umask);
		return $tmpFile;
	}
	
	/**
	* Returns Hunspell command to be used to check spelling for the words contain in temp file.
	* The command is based on the OS the script is running.
	* Null command will be returned if temp folder($this->tmpFile) is not defined.
	*
	* @param array $dictionaries
	* @return string $command
	*/
	private function getCommandForSpellChecking( array $dictionaries )
	{
		$hunspellLang = implode( ',', $dictionaries );
		if( OS == 'WIN' ) {			
			$command = $this->getHunspellCmd( $this->config['location'] ) . ' -l -i utf-8 -d '. escapeshellarg($hunspellLang) . 
						' < ' . escapeshellarg($this->tmpFile) . ' 2>' . escapeshellarg( $this->stdErrOutputFile );
		} else {
			$command = '"'.$this->config['location'] . '" -l -i utf-8 -d '. escapeshellarg($hunspellLang) .
						' < ' . escapeshellarg($this->tmpFile) . ' 2>' . escapeshellarg( $this->stdErrOutputFile );
		}
		LogHandler::Log( 'Spelling', 'DEBUG', __CLASS__.'::getCommandForSpellChecking() returns shell command: '.htmlentities($command) );
		return $command;
	}
	
	/*
	* Returns Hunspell command to be used to get suggestions for the word passed in ($wordForSuggestions).
	* The command is based on the OS the script is running.	
	* e.g: Linux and MAC: echo 'tst' | hunspell -i utf-8 -d en_US
	* @since v7.6.0: WIN: hunspell -i utf-8 -d en_US < tmpFile
	*/
	private function getCommandForWordSuggestions( array $dictionaries, $wordForSuggestions )
	{
		$hunspellLang = implode( ',', $dictionaries );
		if( OS == 'WIN' ){		
			$command = '"' . $this->getHunspellCmd( $this->config['location'] ). '" -i UTF-8 -d ' . escapeshellarg( $hunspellLang ) .
					   ' < ' .	escapeshellarg( $this->tmpFile ) . ' 2>' . escapeshellarg( $this->stdErrOutputFile );
		} else {			
			$command = '/bin/echo "' . $wordForSuggestions . '" | ' . 
					'"' .$this->config['location']. '" -i UTF-8 -d ' . escapeshellarg( $hunspellLang ) .
					' 2>' . escapeshellarg( $this->stdErrOutputFile );
		}
		
		LogHandler::Log( 'Spelling', 'DEBUG', __CLASS__.'::getCommandForWordSuggestions() returns shell command: '.htmlentities($command) );
		return $command;	
	}
	
	/*
	* Returns Hunspell executable filename (which is configured in ENTERPRISE_SPELLING
	* option in configserver.php). Called when the system runs on Windows machine.
	* For an example: 'C:/Hunspell/hunspell.exe' is being configure, 'hunspell.exe' is returned.
	* @param string $location Full file path of executable Hunspell. E.g: 'C:/Hunspell/hunspell.exe'
	* @return string Hunspell executable filename.
	*/
	private function getHunspellCmd( $location )
	{
		return basename( $location );
	}
	
	/*
	* Returns the file path where Hunspell resides (which is configured in ENTERPRISE_SPELLING
	* option in configserver.php). Called when the system runs on Windows machine.
	* For an example: 'C:/Hunspell/hunspell.exe' is being configure, 'C:/Hunspell' is returned.
	* @param string $location Full file path of executable Hunspell. E.g: 'C:/Hunspell/hunspell.exe'
	* @return string Hunspell file path.
	*/
	private function getHunspellDir( $location )
	{
		return dirname( $location );	
	}
	
	/**
	 * Before using the command line in PHP, there are preparations to be taken in order
	 * to let Hunspell commands work well. For Windows it changes the working directory to
	 * the Hunspell directory and for MacOSX/Linux it clears the DYLD_LIBRARY_PATH variable.
	 *
	 * @return array Contextual information for {@link:roundupAfterCommandExecutions()}
	 */
	private function prepareForCommandExecutions()
	{
		$context = array();
		if( OS == 'WIN' ) {
			// Need to change to Hunspell dir in Windows to run hunspell.exe
			$workingDir = getcwd();
			$hunspellDir =  $this->getHunspellDir( $this->config['location'] );
			LogHandler::Log('Hunspell','INFO', __METHOD__.': Changing from current working '.
				'directory ' . $workingDir . ' to Hunspell directory:' . $hunspellDir );
			chdir( $hunspellDir );
			$context['workingDir'] = $workingDir;
		} else { // MacOSX/Linux
			// The DYLD_LIBRARY_PATH variable might be set for the PHP command line environment
			// (e.g. /usr/lib). When Hunspell was installed with MacPorts, the variable should
			// be set to /opt/usr/local/lib or else dynamic loading of Hunspell libraries fails.
			// Therefore, before running Hunspell, we clear the variable to avoid this error.
			$oriDyldLibPath = getenv( 'DYLD_LIBRARY_PATH' ); // Get original varibale value
			LogHandler::Log( 'Hunspell', 'INFO', __METHOD__.': Clearing the DYLD_LIBRARY_PATH '.
				'setting that was set to [' . $oriDyldLibPath . '] for PHP command line enviroment.' );
			putenv( 'DYLD_LIBRARY_PATH=' );
			$context['oriDyldLibPath'] = $oriDyldLibPath;
		}
		return $context;
	}
	
	/**
	 * After running Hunspell commands, the preparations to the PHP command line environment
	 * needs to be restored to avoid side effects to any next following commands within this
	 * PHP session. For Windows, it changes back to the original working directory and for
	 * MacOSX/Linux it restores the DYLD_LIBRARY_PATH variable.
	 * 
	 * @param array $context Contextual information from {@link:prepareForCommandExecutions()}
	 */
	private function roundupAfterCommandExecutions( array $context )
	{
		if( OS == 'WIN' ) { 
			// Finish working on hunspell, return to the original working directory.
			$workingDir = $context['workingDir'];
			LogHandler::Log( 'Hunspell', 'INFO', __METHOD__.': Returning back to working '.
				'directory [' . $workingDir . '] from Hunspell directory' );
			chdir( $workingDir );	
		} else { // MacOSX/Linux
			// Restore the DYLD_LIBRARY_PATH variable to its original value.
			// This is to avoid any side effects with other commands run in this session.
			$oriDyldLibPath = $context['oriDyldLibPath'];
			LogHandler::Log( 'Hunspell', 'INFO', __METHOD__.': Restoring the DYLD_LIBRARY_PATH '.
				'setting back to [' . $oriDyldLibPath . '] for PHP command line enviroment.' );
			putenv( 'DYLD_LIBRARY_PATH='.$oriDyldLibPath );
		}
	}
}
