<?php
/**
 * Includes two wrapper classes; one for the built-in ZipArchive class and one for the zip/unzip
 * command line tools. Also includes a factory class to create the one of those wrapper classes.
 *
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
/**
 * Helper class to create the correct WW_Utils_ZipUtility class.
 */
class WW_Utils_ZipUtility_Factory
{
	/**
	 * Creates and returns an instance of the WW_Utils_ZipUtility class.
	 * Two possible ways to create zip utility which are:
	 * - via zipArchive.
	 * - via zipCommandLine.
	 * When zipArchive is chose, the 'zip' extension is checked whether it is loaded,
	 * if the extension is not loaded, it will fallback to zipCommandLine. 
	 * @param bool $forceUsingCommandLine TRUE to always use commandline. FALSE to use zipArchive.
	 * @return WW_Utils_ZipUtility
	 */
	static public function createZipUtility( $forceUsingCommandLine = false )
	{
		if( extension_loaded('zip') && !$forceUsingCommandLine ) {
			LogHandler::Log( 'ZipUtility', 'INFO', 'ZIP utility created: ZipArchive.' );
			return new WW_Utils_ZipUtility_ZipArchive();
		} else {
			LogHandler::Log( 'ZipUtility', 'INFO', 'ZIP utility created: ZipCommandLine.' );
			return new WW_Utils_ZipUtility_CommandLine();
		}
	}
}

/**
 * Abstract class that defines the WW_Utils_ZipUtility class. 
 */
abstract class WW_Utils_ZipUtility
{
	/**
	 * Opens the zip archive of the given filepath for reading
	 * 
	 * @param string $filepath
	 */
	abstract public function openZipArchive( $filepath );
	
	/**
	 * Writes the given file content to a temporary file and opens the archive for reading.
	 * 
	 * @param string $fileContent
	 */
	abstract public function openZipArchiveWithString( $fileContent );

	/**
	 * Creates a new zip archive. Returns the filepath to the archive.
	 * 
	 * @param string $archivePath Optional. Allows specifying own filepath. NULL to let function make up one.
	 * @return string 
	 */
	abstract public function createZipArchive( $archivePath = null );
	
	/**
	 * Gets the file content form a single file of an opened archive. 
	 * 
	 * @param string $filename
	 * 
	 * @return string
	 */
	abstract public function getFile( $filename );

	/**
	 * Gets the file contents of all the files mentioned in the filenames array.
	 * Returns an array with the filename as key and the filecontent as value.
	 * 
	 * @param array $filenames
	 * 
	 * @return array
	 */
	abstract public function getFiles( $filenames );

	/**
	 * Adds a file to a ZIP archive from the given path.
	 * You can pass your own name or the basename of the added file is used.
	 * @param string $fileName The path to the file to add. 
	 * @param string $ownName Overwrites the basename of $fileName. 
	 * @return boolean TRUE on success or FALSE on failure. 
	 */
	abstract public function addFile( $fileName, $ownName = null );

	/**
	 * Add all the files from a directory recursively to a newly created archive. 
	 * When the given $directory has an ending with slash, the folder name itself will be excluded
	 * from the archive file being created. Without ending slash, the folder name will be included.
	 *
	 * @param string $directory
	 */
	abstract public function addDirectoryToArchive( $directory );
	
	/**
	 * Extracts the archive to the given destination. If the entries parameter is given
	 * only the files mentioned in that parameter are extracted. The entries parameter
	 * can be a string or array. 
	 * 
	 * @param string $destination
	 * @param mixed $entries 
	 * @return boolean TRUE on success or FALSE on failure. 
	 */
	abstract public function extractArchive( $destination, $entries = null );

	/**
	 * Closes the archive. 
	 */
	abstract public function closeArchive( );
	
	/**
	 * Returns the location of a generated zip archive. 
	 */
	abstract protected function getTempFileLocation( );
	
	/**
	 * Helper function that creates a temporary file and writes the given file content to it.
	 * Returns a string with the filepath to the newly created file.
	 *
	 * @param string $fileContent
	 * @return string
	 * @throws BizException
	 */
	protected function writeTempFile( $fileContent )
	{
		$tmpFile = $this->getTempFileLocation();
		if ( $tmpFile ) {
			file_put_contents($tmpFile, $fileContent); // Put the contents of the zip in the temp file
		} else {
			throw new BizException( 'ERR_NO_WRITE_TO_DIR', 'Server', null, array('') );
		}
				
		return $tmpFile;
	}
}

/**
 * An implementation of the WW_Utils_ZipUtility class that wraps the ZipArchive class.
 * ZipArchive is a built-in PHP class that can be added by enabling the PHP 'zip' extension.
 */
class WW_Utils_ZipUtility_ZipArchive extends WW_Utils_ZipUtility
{
	private $zipArchive;
	private $tmpFile = null;
	
	public function __construct() 
	{
		$this->zipArchive = new ZipArchive();
	}
	
	public function __destruct()
	{
		if ( !empty( $this->tmpFile ) ) {
			unlink( $this->tmpFile );
			$this->tmpFile = null;
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function openZipArchive( $archivePath )
	{
		$couldOpen = $this->zipArchive->open( $archivePath );
		if( $couldOpen === true ) {
			LogHandler::Log( 'ZipUtility', 'DEBUG', 
				'Opened archive file "'. $archivePath .'" using ZipArchive.' );
		} else {
			LogHandler::Log( 'ZipUtility', 'ERROR',
				'Could not open existing archive file "'.$archivePath.'" using ZipArchive. '.
				$this->getErrorMessage( $couldOpen ).' (error code: '.$couldOpen.').' );
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function openZipArchiveWithString( $fileContent )
	{
		$this->tmpFile = $this->writeTempFile( $fileContent );
		
		$this->openZipArchive( $this->tmpFile );
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function createZipArchive( $archivePath = null )
	{
		if( !$archivePath ) {
			$archivePath = $this->getTempFileLocation();
		}
		$couldOpen = $this->zipArchive->open( $archivePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE );
		if( $couldOpen === true ) {
			LogHandler::Log( 'ZipUtility', 'DEBUG', 
				'Creating archive file "'. $archivePath .'" using ZipArchive.' );
		} else {
			LogHandler::Log( 'ZipUtility', 'ERROR',
				'Could not create empty archive file "'.$archivePath.'" using ZipArchive. '.
				$this->getErrorMessage( $couldOpen ).' (error code: '.$couldOpen.').' );
		}
		return $archivePath;
	}

	/**
	 * Translates an error code (returned by ZipArchive functions) into a readable string.
	 *
	 * @param integer
	 * @return string 
	 */
	private function getErrorMessage( $errCode )
	{
		switch( $errCode ) {
			case ZIPARCHIVE::ER_EXISTS:
				$errMsg = 'File already exists.'; break;
			case ZIPARCHIVE::ER_INCONS:
				$errMsg = 'Zip archive inconsistent.'; break;
			case ZIPARCHIVE::ER_INVAL:
				$errMsg = 'Invalid argument.'; break;
			case ZIPARCHIVE::ER_MEMORY:
				$errMsg = 'Malloc failure.'; break;
			case ZIPARCHIVE::ER_NOENT:
				$errMsg = 'No such file.'; break;
			case ZIPARCHIVE::ER_NOZIP:
				$errMsg = 'Not a zip archive.'; break;
			case ZIPARCHIVE::ER_OPEN:
				$errMsg = 'Can\'t open file.'; break;
			case ZIPARCHIVE::ER_READ:
				$errMsg = 'Read error.'; break;
			case ZIPARCHIVE::ER_SEEK:
				$errMsg = 'Seek error.'; break;
			default:
				$errMsg = 'Unknown error.'; break;
		}
		return $errMsg;
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function getFile( $filename ) 
	{
		return $this->zipArchive->getFromName( $filename );
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function getFiles( $filenames )
	{
		$files = array();
		
		foreach ( $filenames as $filename ) {
			$files[$filename] = $this->getFile( $filename );
		}
		
		return $files;
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function addFile( $fileName, $ownName = null )
	{
		if( is_file( $fileName ) ) {
			$archiveName = $ownName ? $ownName : basename( $fileName ); 
			$couldAdd = $this->zipArchive->addFile( realpath( $fileName ), $archiveName );
			if( $couldAdd ) {
				LogHandler::Log( 'ZipUtility', 'DEBUG', 'Added file "'. $fileName .'" to archive.' );
			} else {
				LogHandler::Log( 'ZipUtility', 'ERROR', 'Failed adding file "'. $fileName .'" to archive.' );
			}
		} else {
			$couldAdd = false;
			LogHandler::Log( 'ZipUtility', 'ERROR', 'Path is no valid file "'. $fileName .'". Not added to archive.' );
		}
		return $couldAdd;
	}
	 
	/**
	 * {@inheritdoc}
	 * IMPORTANT: Be careful with ending slashes at $directory.
	 */
	final public function addDirectoryToArchive( $directory )
	{
		$this->addDirectoryRecursively( $directory );
	}
	
	/**
	/* Private helper function to add all the files in a directory recursively to the zip archive.
	 * Depending on the file size this is done directly or by first reading the file
	 * and then add the content as a string to the archive. The reason not to add all
	 * files directly is that in that case the files remain open (locked by PHP). If the number of open
	 * files exceeds the maximum number of open files supported by the OS the archive
	 * process breaks. On the other hand passing all files as string is also not possible
	 * because of memory limits. 
	 * Files smaller than 1 Mb are added as a string.
	 * Files larger than 1 Mb are added directly. If the number of files added this way reaches
	 * 256 the archive is closed and opened. By closing the archive the file handlers are closed.
	 * Closing/opening an archive has a performance drawback.	
	 * 
	 * @param string $directory
	 * @param string $zipdir 
	 */
	private function addDirectoryRecursively( $directory, $zipdir = '' )
	{
		static $filesOpen = 0;
		$maxFileSize = 1048576; // 1Mb
		$maxFilesOpen = 256; // Maximum number of open files supported by all OS 
		
		LogHandler::Log( 'ZipUtility','DEBUG', 'dir="'.$directory.'" zipdir="'.$zipdir.'".' );
	    if (is_dir($directory)) {

            // Add the directory
            if( $zipdir ) {
            	$this->zipArchive->addEmptyDir($zipdir);
        	}

            // Loop through all the files
   			$files = glob( $directory . '*', GLOB_MARK );
			foreach( $files as $file ) {
				if( is_file($file) ) {
					$result = false;
					$file = realpath($file);
					$localFile = $zipdir.basename($file); // Filename (absolute) in archive 
					if( filesize( $file ) <= $maxFileSize ) { // File is added as string
						$contents = file_get_contents( $file );
						if ( $contents ) {
							$result = $this->zipArchive->addFromString( $localFile, $contents );
						}	
					} else { // File is added directly
						$result = $this->zipArchive->addFile( $file, $localFile);
						$filesOpen += 1;
						if( $filesOpen == $maxFilesOpen ) {
							$zipFileName = $this->zipArchive->filename;
							if( $this->zipArchive->close() ) {
								LogHandler::Log( 'ZipUtility', 'DEBUG', 
									'Temporary closed archive file "'.$zipFileName.'" after adding '.strval( $maxFilesOpen ).' files.' );
								$couldOpen = $this->zipArchive->open( $zipFileName );
								if( $couldOpen === true ) {
									LogHandler::Log( 'ZipUtility', 'DEBUG', 
										'Reopened archive file "'.$zipFileName.'" after adding '.strval( $maxFilesOpen ).' files.' );
								} else {
									LogHandler::Log( 'ZipUtility', 'ERROR', 
										'Could not reopen archive file "'.$zipFileName.'" after adding '.strval( $maxFilesOpen ).' files.'.
										$this->getErrorMessage( $couldOpen ).' (error code: '.$couldOpen.').' );
								}
							} else {
								LogHandler::Log( 'ZipUtility', 'ERROR', 
									'Could not temporary close archive file "'.$zipFileName.'" after adding '.strval( $maxFilesOpen ).' files.' );
							}
							$filesOpen = 0;
						} 
					} 
					if( !$result ) {
						LogHandler::Log( 'ZipUtility', 'ERROR', 'Failed to add file "'. $file .'" to archive.' );	
					} else {
						LogHandler::Log( 'ZipUtility', 'DEBUG', 'Added file "'. $file .'" to archive.' );
					}
				} else {
					$this->addDirectoryRecursively( dirname($file).'/'.basename($file).'/', $zipdir.basename($file).'/' );
				}
			}
		} else {
			LogHandler::Log( 'ZipUtility','ERROR', 'Path is no valid directory "'. $directory .'". Not added to archive.' );
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function extractArchive( $destination, $entries = null )
	{
		// Exclude the __MACOSX folder and the Thumbs.db files when the entries parameter is null
		if ( is_null($entries) ) {
			$entries = array();
			for ($idx = 0; $idx < $this->zipArchive->numFiles; $idx++) {
				$extractedFilePath = $this->zipArchive->getNameIndex($idx);
				if (false === stripos($extractedFilePath, '__MACOSX') && false === stripos($extractedFilePath, "Thumbs.db")) {
					$entries[] = $extractedFilePath;
				}
			} 
		}
		if (is_array($entries) || is_string($entries)) {
			return $this->zipArchive->extractTo( $destination, $entries );
		} else {
			return $this->zipArchive->extractTo( $destination );
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function closeArchive()
	{
		LogHandler::Log( 'ZipUtility', 'DEBUG', 'Closing archive file.' );
		if( $this->zipArchive->close() ) {
			LogHandler::Log( 'ZipUtility', 'DEBUG', 
				'Closed archive file "'.$this->zipArchive->filename.'" after adding all files.' );
		} else {
			LogHandler::Log( 'ZipUtility', 'ERROR', 
				'Could not close archive file "'.$this->zipArchive->filename.'" after adding all files.' );
		}
	}

	/**
	 * Helper function that creates and returns a filepath to the temporary file which can be used as zip archive.
	 * A unique Id is used as filename. If for some not foreseen reason a file with the same name already exists a
	 * new attempt is done (5 attemps max).
	 * 
	 * @return string|bool File path to created file, or FALSE when failed.
	 */
	final protected function getTempFileLocation()
	{
		$tries = 0;
		$created = false;
		$result = false;
		
		while ( !$created && $tries < 5 ) {
			$tries += 1;
			// Let system make-up unique temp file.
			$uniqueId = uniqid( 'ZipUtility_' );
			// Add zip file extension.
			$zipFile = TEMPDIRECTORY . '/' . $uniqueId . '.zip';
			if ( !is_file( $zipFile ) ) {
				$handle = fopen( $zipFile, 'x+' );
				if ( $handle ) {
					$created = true;
					fclose( $handle );
					clearstatcache(); // reflect file creation operation to disk
					$result = $zipFile;
				}
			}
		}
		
		return $result;
	}	
	
}

/**
 * An implementation of the WW_Utils_ZipUtility class that uses the command line zip and unzip tools. 
 * Windows is NOT supported, and therefore this class is only usable on Linux and Mac OS systems.
 */
class WW_Utils_ZipUtility_CommandLine extends WW_Utils_ZipUtility
{
	/**
	 * Saves the path to the archive. 
	 * 
	 * @var string
	 */
	private $archivePath = "";
	/**
	 * Boolean to keep track if it is a temporary file that this class created.
	 * 
	 * @var boolean
	 */
	private $tempFile = false;
		
	/**
	 * Destructor -- Removes the archive file if a temporary file is created.
	 */
	public function __destruct()
	{
		if ( $this->tempFile && is_file($this->archivePath) ) {
			unlink( $this->archivePath );
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function openZipArchive( $archivePath )
	{
		LogHandler::Log( 'ZipUtility', 'DEBUG', 'Opening archive file "'. $archivePath .'" using ZipCommandLine.' );
		$this->archivePath = $archivePath;
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function openZipArchiveWithString( $fileContent )
	{
		$tmpFile = $this->writeTempFile( $fileContent );
		$this->tmpFile = true;
		$this->openZipArchive( $tmpFile );
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function createZipArchive( $archivePath = null )
	{
		$this->archivePath = '';
		if( $archivePath ) {
			if( !is_file($archivePath) || unlink($archivePath) ) {
				$this->archivePath = $archivePath;
			} else {
				LogHandler::Log( 'ZipUtility', 'ERROR',
					'Could not remove archive file "'. $archivePath .'" using ZipCommandLine.' );
			}
		} else {
			$archivePath = $this->getTempFileLocation();
			if( $archivePath ) {
				//unlink( $archivePath );
				$this->archivePath = $archivePath;
			} else {
				LogHandler::Log( 'ZipUtility', 'ERROR',
					'Could not create empty archive file using ZipCommandLine.' );
			}
		}
		if( $this->archivePath ) {
			LogHandler::Log( 'ZipUtility', 'DEBUG', 
				'Created archive file "'. $this->archivePath .'" using ZipCommandLine.' );
		}
		return $this->archivePath;
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function getFile( $filename ) 
	{
		$archiveFileName = basename( $this->archivePath );
		$archivePath = dirname( $this->archivePath );
				
		chdir( $archivePath );
		$cmdTmpFileName = escapeshellarg( $archiveFileName );
		$cmdRequestedFile = escapeshellarg( $filename );
		$cmd = "unzip -o $cmdTmpFileName $cmdRequestedFile";
		LogHandler::Log( 'ZipUtility', 'INFO', 'Extracting file from archive using command line:  "'. $cmd .'".' );
		exec( $cmd );

		if ( file_exists( $filename ) ) {
			$contents = file_get_contents( $filename );
			return $contents;
		}
		
		return false;
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function getFiles( $filenames )
	{
		$files = array();
		
		foreach ( $filenames as $filename ) {
			$files[$filename] = $this->getFile( $filename );
		}
		
		return $files;
	}

	/**
	 * {@inheritdoc}
	 */
	final public function addFile( $fileName, $ownName = null )
	{
		if( is_file( $fileName ) ) {
			LogHandler::Log( 'ZipUtility', 'DEBUG', 'Added file "'. $fileName .'" to archive.' );
			$cmdArchivePath = escapeshellarg( $this->archivePath );
			$cmdFileName = $ownName ?  escapeshellarg( $ownName  ) : escapeshellarg( basename( $fileName ) );
			$directory = dirname( $fileName );
			LogHandler::Log( 'ZipUtility', 'INFO', 'Change to directory:  "'. $directory .'".' );
			chdir( $directory );
			$cmd = "zip $cmdArchivePath $cmdFileName";
			LogHandler::Log( 'ZipUtility', 'INFO', 'Adding file to archive using command line:  "'. $cmd .'".' );
			exec( $cmd );
		} else {
			LogHandler::Log( 'ZipUtility', 'ERROR', 'Path is no valid file "'. $fileName .'". Not added to archive.' );
		}
	}
	
	/**
	 * {@inheritdoc}
	 * IMPORTANT: Be careful with ending slashes at $directory.
	 */
	final public function addDirectoryToArchive( $directory )
	{
		if( is_dir( $directory ) ) {
			$cmdArchivePath = escapeshellarg( $this->archivePath );
			$lastChar = substr( $directory, -1, 1 );
			// Always include all files at the given directory, but depending if an ending slash is
			// given, the folder name is included or excluded at the archive being created.
			if( $lastChar == '/' || $lastChar == '\\' ) { // ending slash = exclude folder name
				$cmd = "zip -r $cmdArchivePath *";
			} else { // no ending slash = include folder name
				$cmdDirectory = escapeshellarg( basename( $directory ) );
				$directory = dirname( $directory ); // climb up to parent folder at chdir() below
				$cmd = "zip -r $cmdArchivePath $cmdDirectory/*";
			}
			LogHandler::Log( 'ZipUtility', 'INFO', 'Change to directory:  "'. $directory .'".' );
			chdir( $directory );
			LogHandler::Log( 'ZipUtility', 'INFO', 'Adding directory to archive using command line:  "'. $cmd .'".' );
			exec( $cmd );
		} else {
			LogHandler::Log( 'ZipUtility', 'ERROR', 'Path is no valid directory "'. $directory .'". Not added to archive.' );
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function extractArchive( $destination, $entries = null )
	{
		$cmdArchivePath = escapeshellarg( $this->archivePath );
		$cmdDestination = escapeshellarg( $destination );
		
		$cmd = "unzip -o $cmdArchivePath";
		if( $entries ) {
			if( is_array($entries) ) {
				foreach( $entries as $entry ) {
					$cmd .= " " . escapeshellarg($entry);
				}
			} else {
				$cmd .= " " . escapeshellarg($entries);
			}
		}
		// We always overwrite existing files and exclude the __MACOSX folder and the Thumbs.db files
		$cmd .= " -x __MACOSX/* Thumbs.db -d $cmdDestination"; 
		
		// Change the current directory to the directory of the file and then call the unzip command
		LogHandler::Log( 'ZipUtility', 'INFO', 'Extracting archive using command line:  "'. $cmd .'".' );
		$output = array();
		$result = null;
		exec( $cmd, $output, $result );

		// return status of the executed command is 0 in case of success.
		return $result == 0 ? true : false;
	}
	
	/**
	 * {@inheritdoc}
	 */
	final public function closeArchive()
	{
		LogHandler::Log( 'ZipUtility', 'DEBUG', 'Closing archive file "'. $this->archivePath .'".' );
		// We don't have to do anything. 
	}
	
	/**
	 * Helper function that creates and returns a filepath to the temporary file which can be used as zip archive.
	 * A unique Id is used as filename. If for some not foreseen reason a file with the same name already exists a
	 * new attempt is done (5 attemps max).
	 * It is not possible to create an empty zip archive. So first an archive is created with one dummy file in it.
	 * Then, the dummy is removed again and the archive remains in the temp folder.
	 * 
	 * @return string|bool File path to created file, or FALSE when failed.
	 */
	final protected function getTempFileLocation()
	{
		$tries = 0;
		$created = false;
		$result = false;
		
		while ( !$created && $tries < 5 ) {
			$tries += 1;
			// Let system make-up unique temp file.
			$uniqueId = uniqid( 'ZipUtility_' );
			// Add zip file extension.
			$zipFile = TEMPDIRECTORY . '/' . $uniqueId . '.zip';
			if ( !is_file( $zipFile ) ) {
				$dummyFile = substr($zipFile, 0, -4);
				$handle = fopen( $dummyFile, 'x+' );
				if ( $handle ) {
					fclose( $handle );
					clearstatcache(); // reflect file creation operation to disk
					$zipFileCmd = escapeshellarg($zipFile);
					$dummyFileCmd = escapeshellarg($dummyFile);
					$cmd = "zip $zipFileCmd $dummyFileCmd"; // Create archive with dummy file.
					exec( $cmd );
					$cmd = "zip $zipFileCmd -d $dummyFileCmd"; // Remove dummy file.
					exec( $cmd );
					unlink( $dummyFile );
					clearstatcache();
					if (is_file( $zipFile )) {
						$result = $zipFile;
						$created = true;
					}	
				}
			}
		}
		
		return $result;
	}	
}
