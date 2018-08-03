<?php
/**
 * Spelling checker and suggestions using Aspell.
 *
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/interfaces/plugins/connectors/Spelling_EnterpriseConnector.class.php';

class AspellShellSpelling_Spelling extends Spelling_EnterpriseConnector
{
	private $tmpFile = null;
	private $stdErrOutputFile = null;
	
	/**
	 * Refer to abstract class checkSpelling() header.
	 */
	public function checkSpelling( array $dictionaries, array $wordsToCheck ) 
	{
		$this->tmpFile = $this->getTempFileName();
		$this->stdErrOutputFile = $this->getTempFileName();
		$command = $this->getCommand( $dictionaries );
		$mistakeWords = array();
		
		$fh = fopen( $this->tmpFile, 'w' );
		if( !$fh ) {
			$detail = 'Aspell Shell Spelling could not create temporary file "'.$this->tmpFile.'" for writing. '.
				'Please check if www/inet user has write access to the system temp folder. ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		fwrite( $fh, "!\n" );
		foreach( $wordsToCheck as $wordToCheck ) {
			if( trim( $wordToCheck ) ) {
				fwrite($fh, '^' . $wordToCheck . "\n");
			}
		}
		fclose( $fh );
		if( OS == 'WIN' ){ // Need to change to Aspell dir in Windows to run aspell.exe
			$workingDir = getcwd();
			$aspellDir =  $this->getAspellDir( $this->config['location'] );
			LogHandler::Log('Aspell','INFO', __METHOD__.': Changing from current working directory ' . $workingDir . 
						' to Aspell directory:' . $aspellDir );
			chdir( $aspellDir );
		}
			
		$mistakesWithSuggestions = shell_exec( $command );
		@unlink( $this->tmpFile );
		
		if( OS == 'WIN' ){
			LogHandler::Log('Aspell','INFO', __METHOD__.': Returning back to working directory [' . $workingDir . 
						'] from Aspell directory' );
			chdir( $workingDir ); // After executing Aspell, return back to the directory the system was at.
		}
		
		if( filesize( $this->stdErrOutputFile ) > 0 ) {
			$detail = 'Aspell Shell Spelling engine failed to check spelling '.
				'for language(s): "'. implode( ',', $dictionaries ).'". '.
				'Error from spelling engine: "'.file_get_contents( $this->stdErrOutputFile ).'". '.
				'Command used: "'.$command.'". ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		@unlink( $this->stdErrOutputFile );
		$mistakesWithSuggestions = mb_split("[\r\n]", $mistakesWithSuggestions );

		foreach ( $mistakesWithSuggestions as $mistakeWithSuggestions ){
			if( mb_ereg_match('^@', $mistakeWithSuggestions ) ){
				continue;
			}
			if( $mistakeWithSuggestions ){
				$mistakeWord = null;
				list(, $mistakeWord ) = mb_split( '\s', $mistakeWithSuggestions ); // retrieve mistake word from [mistake]+[suggestions]
				$mistakeWords[] = $mistakeWord;
			}	
		}
		return $mistakeWords;
	}

	/**
	 * Refer to abstract class getSuggestions() header.
	 */
	public function getSuggestions( array $dictionaries, $wordForSuggestions ) 
	{
		$this->tmpFile = $this->getTempFileName();
		$this->stdErrOutputFile = $this->getTempFileName();
		$command = $this->getCommand( $dictionaries );
		$fh = fopen( $this->tmpFile, 'w' );
		if( !$fh ) {
			$detail = 'Aspell Shell Spelling could not create temporary file "'.$this->tmpFile.'" for writing. '.
				'Please check if www/inet user has write access to the system temp folder. ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		fwrite( $fh, "!\n" );
		fwrite( $fh, "^$wordForSuggestions\n" );
		fclose( $fh );

		if( OS == 'WIN' ){ // Need to change to Aspell dir in Windows to run aspell.exe
			$workingDir = getcwd();
			$aspellDir =  $this->getAspellDir( $this->config['location'] );
			LogHandler::Log('Aspell','INFO', __METHOD__.': Changing from current working directory ' . $workingDir . 
						' to Aspell directory:' . $aspellDir );
			chdir( $aspellDir );
		}
		
		$suggestions = shell_exec( $command );
		@unlink( $this->tmpFile );
		
		if( OS == 'WIN' ){
			LogHandler::Log('Aspell','INFO', __METHOD__.': Returning back to working directory [' . $workingDir . 
						'] from Aspell directory' );
			chdir( $workingDir ); // After executing Aspell, return back to the directory the system was at.
		}

		if( filesize( $this->stdErrOutputFile ) > 0 ){
			$detail = 'Aspell Shell Spelling engine failed to get suggestions '.
				'for language(s): "'. implode( ',', $dictionaries ).'". '.
				'Error from spelling engine: "'.file_get_contents( $this->stdErrOutputFile ).'". '.
				'Command used: "'.$command.'". ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		@unlink( $this->stdErrOutputFile );

		$suggestions = mb_split( "\n", $suggestions );
		$suggestedWords = array();
		if( $suggestions ) foreach($suggestions as $suggestion ) {
			$suggestion = trim( $suggestion );
			if( $suggestion ) {
				if( mb_ereg_match('^@', $suggestion ) ){
					continue;
				}

				$matchedData = array();
				mb_eregi('\&[^:]+: (.*)', $suggestion, $matchedData );
				if( isset($matchedData[1]) ){
					$suggestedWords = mb_split( ',\s', $matchedData[1] );
					$suggestedWords = array_slice( $suggestedWords, 0, $this->config['suggestions'] );
				}
			}	
		}
		return $suggestedWords;
	}
	
	/*
	* Returns temporary file name with prefix 'esp'.
	*
	* @return string temporary file name
	*/
	private function getTempFileName()
	{
		$tmpDir = sys_get_temp_dir();
		$tmpFile = tempnam( $tmpDir, 'esp' ); // esp = Enterprise Spelling
		if( !$tmpFile ) {
			$detail = 'Aspell Shell Spelling could not generate temporary file at "'.$tmpDir.'" folder. '.
				'Please check if www/inet user has write access to the system temp folder. ';
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		return $tmpFile;
	}
	
	/**
	* Returns Aspell command based on the OS the script is running.
	* Null command will be returned if temp folder($this->tmpFile) is not defined.
	*
	* @param array $dictionaries
	* @return string
	*/
	private function getCommand( array $dictionaries )
	{
		$aspellLang = $dictionaries[0];
		$extraDictionaries = null;
		if( count( $dictionaries ) > 1){
			array_shift( $dictionaries );
			foreach( $dictionaries as $dictionary ){
				$extraDictionaries .=  ' --extra-dicts=' . $dictionary;
			}
			$extraDictionaries = escapeshellarg( $extraDictionaries );
		}
		if( OS == 'WIN' ) {
			$command = $this->getAspellCmd( $this->config['location'] ).' -a --lang='. escapeshellarg($aspellLang) . $extraDictionaries .
					   ' --encoding=utf-8 -H < ' . escapeshellarg($this->tmpFile) . ' 2>' . escapeshellarg( $this->stdErrOutputFile );
		} else {
			$command = '/bin/cat '. escapeshellarg($this->tmpFile) .' | "' .$this->config['location'] . '"'.
						' -a --encoding=utf-8 -H --lang='. escapeshellarg($aspellLang) . $extraDictionaries . 
						' 2>' . escapeshellarg( $this->stdErrOutputFile );
		}
		return $command;
	}

	/**
	 * Refer to abstract class getEngineVersion() header.
	 */
	public function getEngineVersion()
	{
		$stdErrOutputFile = $this->getTempFileName();
		if( OS == 'WIN' ){
			$workingDir = getcwd();
			$aspellDir =  $this->getAspellDir( $this->config['location'] );
			LogHandler::Log('Aspell','INFO', __METHOD__.': Changing from current working directory ' . $workingDir . 
						' to Aspell directory:' . $aspellDir );
			chdir( $aspellDir );
			$command = $this->getAspellCmd( $this->config['location'] ).' -v 2>'.$stdErrOutputFile;
		}else{
			$command = '"'.$this->config['location'].'" -v 2>'.$stdErrOutputFile;	
		}		
		
		$version = shell_exec( $command ); 
		if( filesize( $stdErrOutputFile ) > 0 ){
			$message = 'Could not determined the version of the Aspell Shell Spelling engine. '.
				'Error from spelling engine: "'.file_get_contents( $stdErrOutputFile ).'". '.
				'Command used: "'.htmlentities( $command ).'". ';
			LogHandler::Log( 'Spelling', 'ERROR', $message );
			$version = '?';
		}else{
			// $version is now something like "@(#) International Ispell Version 3.1.20 (but really Aspell 0.60.6)"
			$aspellPos = strripos( $version, 'Aspell ' );
			if( $aspellPos ) {
				$charsToReplace = $aspellPos + strlen('Aspell ');
				$version = substr_replace( $version, '', 0, $charsToReplace );
				$version = trim( str_replace( ')', '', $version ) );
			}		
		}
		@unlink( $stdErrOutputFile );
		if( OS == 'WIN' ){
			LogHandler::Log('Aspell','INFO', __METHOD__.': Returning back to working directory [' . $workingDir . 
						'] from Aspell directory' );
			chdir( $workingDir ); // After executing Aspell, return back to the directory the system was at.
		}
		return $version;
	}
	
	/**
	 * Refer to abstract class getInstalledDictionaries() header.
	 */
	public function getInstalledDictionaries()
	{
		$stdErrOutputFile = $this->getTempFileName();
		if( OS == 'WIN' ){
			$workingDir = getcwd();
			$aspellDir =  $this->getAspellDir( $this->config['location'] );
			LogHandler::Log('Aspell','INFO', __METHOD__.': Changing from current working directory ' . $workingDir . 
						' to Aspell directory:' . $aspellDir );
			chdir( $aspellDir );			
			$command = $this->getAspellCmd( $this->config['location'] ).' dump dicts 2>"'.$stdErrOutputFile.'"';
		}else{			
			$command = '"'.$this->config['location'].'" dump dicts 2>"'.$stdErrOutputFile.'"';
		}	
		
		$dictionaries = null;
		$dictionaries = trim( shell_exec( $command ));
		$dictionaries = mb_split( "\n", $dictionaries );

		if( OS == 'WIN' ){
			LogHandler::Log('Aspell','INFO', __METHOD__.': Returning back to working directory [' . $workingDir . 
					'] from Aspell directory' );
			chdir( $workingDir ); // After executing Aspell, return back to the directory the system was at.
		}
		if( filesize( $stdErrOutputFile ) > 0 ) {
			$detail = 'Aspell Shell Spelling engine failed to retrieve installed dictionaries. '.
				'Error from spelling engine: "'.file_get_contents( $stdErrOutputFile ).'". '.
				'Command used: "'.$command.'". ';
			@unlink( $stdErrOutputFile ); // delete the errorOutput File before throwing error.	
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}else{
			@unlink( $stdErrOutputFile );
		}
		
		return $dictionaries;
	}
	
	/*
	* Returns Aspell executable filename (which is configured in ENTERPRISE_SPELLING
	* option in configserver.php). Called when the system runs on Windows machine.
	* For an example: 'C:/Aspell/aspell.exe' is being configure, 'aspell.exe' is returned.
	* @param string $location Full file path of executable Aspell. E.g: 'C:/Aspell/aspell.exe'
	* @return string Aspell executable filename.
	*/
	private function getAspellCmd( $location )
	{
		return basename( $location );
	}
	
	/*
	* Returns the file path where Aspell resides (which is configured in ENTERPRISE_SPELLING
	* option in configserver.php). Called when the system runs on Windows machine.
	* For an example: 'C:/Aspell/aspell.exe' is being configure, 'C:/Aspell' is returned.
	* @param string $location Full file path of executable Aspell. E.g: 'C:/Aspell/aspell.exe'
	* @return string Aspell file path.
	*/
	private function getAspellDir( $location )
	{
		return dirname( $location );	
	}
}
