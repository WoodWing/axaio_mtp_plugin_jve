<?php
/*
 * Utility class for handling folders.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
class FolderUtils
{
	/*
	 * Creates a directory path for many levels, like mkdir does for one level only.
	 *
	 * Since 9.5 no longer 0770 is taken as default mode. Instead, the access rights are
	 * taken over from the parental folder. That makes access easier for system admins
	 * who typically are no member of the www group.
	 *
	 * @param string $dirName Full path name that needs to be created. Use forward slashes.
	 * @param integer $mode The mask to apply to the created folder. Provide ordinal value, such as 0x775.
	 * @return boolean Whether or not the folder could be created.
	 */
	static public function mkFullDir( $dirName, $mode = null )
	{
		$result = true;
		$newDir = '';
		
		$dirParts = explode('/', $dirName);

		// >>> BZ#27286 Special treatment for mounted folders on network servers under Windows:
		// Only start to check if subdirectories exists (or not) under the mounted network folder.
		// For example we create my_folder in '//network_server/mounted_folder/my_folder/' but
		// we 'skip' the parental mounted_folder and network_server.
		// Therefore we set the $createFrom to 4.
		$createFrom = 0;
		if( OS == 'WIN' ) {
			// Did we find this pattern? //network_server/mounted_folder/my_folder
			if( count( $dirParts ) > 3 && 
				empty( $dirParts[0] ) && empty( $dirParts[1] ) &&    // two leading slashes: //
				!empty( $dirParts[2] ) && !empty( $dirParts[3] ) ) { // network_server/mounted_folder
				$createFrom = 4;                                     // my_folder
			}
		} // <<<
		
		foreach( $dirParts as $dirIndex => $dirPart) {	
			$newDir .= $dirPart.'/';
			if( $dirIndex < $createFrom ) { // see comments above
				continue;
			}
			// Create folder when not exists (and set access rights).
			if( !file_exists( $newDir ) ) {
				// If no preferred mode given, take over access rights from parent folder.
				if( $mode == null ) {
					$mode = (fileperms( dirname($newDir) ) & 0777);
				}
				$result = mkdir( $newDir, $mode );
				if ( !$result ) { // If one level fails no use to go further
					break;
				}
				chmod( $newDir, $mode );
			}
		}
		
		return $result;
	}
	
	/**
	 * This function copies all the files recursively from the source directory to the destination directory.
	 * 
	 * @param string $sourceDirectory
	 * @param string $destinationDirectory 
	 */
	static public function copyDirectoryRecursively($sourceDirectory, $destinationDirectory) 
	{
		self::mkFullDir($destinationDirectory);
		
		$dirHandle = opendir($sourceDirectory);
		if( $dirHandle === false ) {
			return; // bail out since readdir() does not return false when $dirHandle is false (PHP bug?)
		}
			
		while (false !== ( $file = readdir($dirHandle))) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if (is_dir($sourceDirectory . '/' . $file)) {
					self::copyDirectoryRecursively($sourceDirectory . '/' . $file, $destinationDirectory . '/' . $file);
				} else {
					copy($sourceDirectory . '/' . $file, $destinationDirectory . '/' . $file);
				}
			}
		}
		closedir($dirHandle);
	}
	
	/**
	 * Checks if the given directory is writable by creating and removing a directory in it.
	 *
	 * @param string $dirName without a trailing /
	 * @return boolean 
	 */
	static public function isDirWritable( $dirName )
	{
		if(!is_dir($dirName)){
			LogHandler::Log('FolderUtils', 'ERROR', 'The '.$dirName.' folder is not a directory.' );
			return false;
		}
		if(!is_writable($dirName)){
			LogHandler::Log('FolderUtils', 'ERROR', 'The '.$dirName.' folder is not writable.' );
			return false;
		}
		LogHandler::Log('FolderUtils', 'INFO', 'The '.$dirName.' folder is writable.');
		
		if( !@mkdir($dirName.'/writable_test')){
			LogHandler::Log('FolderUtils', 'ERROR', 'The '.$dirName.' folder is not writable.' );
			return false;
		}
		LogHandler::Log('FolderUtils', 'INFO', $dirName.'/writable_test folder created.');
		
		if( !@rmdir($dirName.'/writable_test')){
			LogHandler::Log('FolderUtils', 'ERROR', 'Could not remove writable_test folder in '.$dirName.' folder. Please make sure delete rights are granted.' );
			return false;
		}
		LogHandler::Log('FolderUtils', 'INFO', $dirName.'/writable_test folder removed.');
		
		return true;
	}
	

	/*
	 * Searches through the folder structure for all files under the given root folder.
	 * It does full depth search (recursion) and for all items the FolderIterInterface is called.
	 *
	 * @param $class       object  Call-back object instance implementing FolderIterInterface.
	 * @param $dirName     string  Root folder for which all children needs.
	 * @param $fileExts    array   List of file extensions. For matching files, iterFile is called, else skipFile.
	 * @param $exclFolders array   List of folders that should be skipped. Given folders can be relative or absolute.
	 * @param $level       integer Current ply in folder structure of recursion search. (default zero).
	 */
	static public function scanDirForFiles( $class, $dirName, $fileExts=array(), $exclFolders=array(), $level=0 )
	{
		//echo 'Called for: '.str_repeat( '&nbsp;', $level*3 ).$dirName.'<br/>';
		// walk through dir items (files and folders)
		if( !is_dir($dirName) ) {
			return; // bail out to avoid warning on opendir()
		}

		$thisDir = opendir( $dirName );
		if( !$thisDir ) {
			return; // bail out since readdir() does not return false when $thisDir is false (PHP bug?)
		}
		
		while( ($itemName = readdir($thisDir)) !== false ) {	

			$skipItem = false;
			if( count($exclFolders) > 0 ) foreach( $exclFolders as $exclFolder ) {
				if( stripos( $dirName.'/'.$itemName, $exclFolder ) !== false ) {
					$skipItem = true; // found excluded folder
					break;
				}
			}

			if( $itemName == '.' || $itemName == '..' ) {
				// cur dir / parent dir
			} else if( is_dir( $dirName.'/'.$itemName ) ) {
				if( $skipItem ) {
					$class->skipFolder( $dirName.'/'.$itemName, $level+1 );
				} else {
					$class->iterFolder( $dirName.'/'.$itemName, $level+1 );
				}
				self::scanDirForFiles( $class, $dirName.'/'.$itemName, $fileExts, $exclFolders, $level+1 ); // recursion, even for skipped folders!
			} else if( is_file( $dirName.'/'.$itemName ) ) {
				if( $skipItem ) {
					$class->skipFile( $dirName.'/'.$itemName, $level );
				} else {
					if( count($fileExts) == 0 ) {
						$class->iterFile( $dirName.'/'.$itemName, $level );
					} else {
						$extension = substr(strrchr($itemName, '.'), 1);
						if( in_array( $extension, $fileExts ) ) {
							$class->iterFile( $dirName.'/'.$itemName, $level );
						} else {
							$class->skipFile( $dirName.'/'.$itemName, $level );
						}
					}
				}
			}
		}
		closedir( $thisDir );
	}
	
	/**
	 * Cleans up all files and subdirectories from a directory. Next the directory itself
	 * is removed if choosen. A directory can only be removed if it is empty. So if cleaning up one
	 * of the files or subdirectory fails the cleanup of the folder fails.
	 * If the owner of the process has no access rights the clean up fails.
	 * Optionaly a Unix timestamp can be passed in which case only files older than the passed time will be removed.
	 * @param string $directory
	 * @param boolean $removeTopFolder boolean whether the folder itself should be removed. Defaults to true.
	 * @param int $olderThan if a file is last modified before the passed time, it will be removed (Unix timestamp). 
	 * @return bool True if directory is removed else false.
	 * @since 7.0.12
	 */
	public static function cleanDirRecursive( $directory, $removeTopFolder = true, $olderThan = null )
	{
    	if(substr($directory, -1) == "/") {
        	$directory = substr($directory,0,-1);
    	}

		$result = true;

		if (($handle = opendir($directory))) {
	    	/* This is the correct way to loop over the directory. */
			while ((false !== ($file = readdir($handle)))) {
				$childResult = true;
				if ( $file == '.' || $file == '..' ) { //current, parent directory are ignored, hidden files are deleted
					continue;
				}
				$filePath = $directory.'/'.$file;
				if ( is_link( $filePath )) { // links will not be resolved.
					continue;
				} elseif ( is_dir( $filePath ) ) {
					$childResult = self::cleanDirRecursive( $filePath );
				} elseif (is_file(  $filePath )) {
					if ( is_null( $olderThan )) {
						$childResult = unlink( $filePath );
					} elseif ( filemtime( $filePath ) < $olderThan) {
						$childResult = unlink( $filePath );
					} else {
						$childResult = false; // No clean up because file is to young
					}
				} else { // In case of no search-permission on directory (no x-bit on directory)
					$childResult = false;
    			}
				if ( $childResult == false) {
					$result = false;
				}
			}
			clearstatcache(); // Make sure all changes are flushed.
			closedir( $handle );
		} else { // Opening failed so nothing removed.
			$result = false;
		}

		$folderCleanUp = true;
		if ($result && $removeTopFolder) {
			$folderCleanUp = rmdir( $directory );
		}

    	$result = (!$result ? $result : $folderCleanUp);
		// If removing a directory fails the result remains false.
		return $result;
	}

	/**
	 * Places dangerous characters with "-" characters. Dangerous characters are the ones that 
	 * might error at several file systems while creating files or folders. This function does
	 * NOT check the platform, since the Server and Filestore can run at different platforms!
	 * So it replaces all unsafe characters, no matter the OS flavor. 
	 * Another advantage of doing this, is that it keeps filestores interchangable.
	 * IMPORTANT: The given file name should NOT include the file path!
	 *
	 * @param string $fileName Base name of file. Path excluded!
	 * @return string The file name, without dangerous chars.
	 */
	static public function replaceDangerousChars( $fileName )
	{
		$dangerousChars = "`~!@#$%^*\\|;:'<>/?\"";
		$safeReplacements = str_repeat( '-', strlen($dangerousChars) );
		return strtr( $fileName, $dangerousChars, $safeReplacements );
	}
	
	/**
	 * Encodes the given file path respecting the FILENAME_ENCODING setting.
	 *
	 * @param string $path2encode The file path to encode
	 * @return string The encoded file path
	 */
	static public function encodePath( $path2encode )
	{
		if (defined('FILENAME_ENCODING')) {
			return iconv( 'UTF-8', FILENAME_ENCODING, $path2encode );
		}
		return $path2encode;		
	}

	/**
	 * Scan (and return) all the files and directory paths in an array relative to the given directory
	 *
	 * @param string $directory The directory you want to scan, with a trailing /
	 * @param array $excludeFileNames The filenames you don't want to include in the filelist 
	 * @param string $currentDir Always leave empty.. Is used for recursively calling the function
	 * @return array with all the files
	 */
	static public function getFilesInFolderRecursive( $directory, $excludeFileNames = array(), $currentDir = '' )
	{
		$files = array();
		if ( is_dir ( $directory ) ) {
			foreach ( glob ( $directory . $currentDir .'*' ) as $file ) {
				if ( $file != "." && $file != ".." && !in_array(basename($file), $excludeFileNames) ) { // Fix for BZ# 22340, check if the filename is not in the excludeFileNames array
					$fileName = $currentDir . basename($file);
					if ( is_dir ( $file ) ) {
						$fileName .= '/'; // Add the / to a directory
						$newCurrentDir = $currentDir . basename( $file ) . '/';
						$files = array_merge ( $files, self::getFilesInFolderRecursive( $directory, $excludeFileNames, $newCurrentDir ) );
					}
					$files[] = $fileName;
				}
			}
		}
		return $files;
	}

	/**
	 * Returns true when there are files in the given directory.
	 * Hidden files are skipped. When the given path isn't a
	 * directory false is returned.
	 *
	 * @param string $directory
	 * @return bool
	 */
	static public function isEmptyDirectory( $directory )
	{
		if (is_dir($directory)) {
			$files = scandir($directory);
			foreach ( $files as $name ) {
				if ($name[0] != '.' ) {
					return false; // directory not empty
				}
			}
		} else {
			return false;
		}

		return true;
	}
	
	/**
	 * Returns a list of Enterprise Server source code paths that contain its shipped 3rd party libraries.
	 * @return array of full folder paths
	 */
	static public function getLibraryFolders()
	{
		return array(
			BASEDIR.'/server/dgrid', 
			BASEDIR.'/server/javachart', 
			BASEDIR.'/server/jquery', 
			BASEDIR.'/server/vendor',
			BASEDIR.'/server/ZendFramework'
		);
	}
	
	/**
	 * Builds a path for an XML file in the system temp folder that can be used to build
	 * a class/function definition package.
	 *
	 * @param string $version Version of the package to build.
	 * @param string $type The logical package name: 'EnterpriseServer_Core', 'EnterpriseServer_Libraries' or 'PHP_Internal'
	 * @return string Path of XML file.
	 */
	static function getReflectionPath( $version, $type )
	{
		switch( $type ) {
			case 'EnterpriseServer_Core':      $type = 'ww_ent_server_core'; break;
			case 'EnterpriseServer_Libraries': $type = 'ww_ent_server_lib';  break;
			case 'PHP_Internal':               $type = 'ww_ent_server_core'; break;
			default: return '';
		}
		$version = implode( '.', array_slice( explode('.',$version), 0, 2 ) );
		return sys_get_temp_dir().'/ww_php_internal.v'.$version.'.phpdb.xml';
	}
}
