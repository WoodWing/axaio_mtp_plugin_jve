<?php

/*
 * Copies all files of the Enterprise server and removes all comments for PHP files. <br/>
 * This util can be used for performance optimization purposes, security and shipping. <br/>
 * Logging is echoed to HTML output (browser screen).
 *
 * @package SCEnterprise
 * @subpackage Utils
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
*/

require_once BASEDIR.'/server/utils/FolderInterface.intf.php';
require_once BASEDIR.'/server/utils/FolderUtils.class.php';

if (!defined('T_ML_COMMENT')) {
    define('T_ML_COMMENT', T_COMMENT);
} else {
    define('T_DOC_COMMENT', T_ML_COMMENT);
}

class RemovePhpComments implements FolderIterInterface
{
	private $destFolder = null;
	private $sourceFolder = null;
	
	/*
	 * Copies all files from sourceFolder to destFolder respecting the folder structure. </br>
	 * All copied files are checked against given parameters if their PHP comments needs to be removed. </br>
	 *
	 * @param $sourceFolder string Full root path of files/folders to scan. <br/>
	 * @param $destFolder   string Full root path where to build the files/folders tree. <br/>
	 * @param $fileExts     array  List of file extensions. For matching files, iterFile is called, else skipFile.
	 * @param $exclFolders  array  List of folders that should be skipped. Given folders can be relative or absolute.
	 */
	public function removeAllComments( $sourceFolder, $destFolder, $fileExts, $exclFolders )
	{
		if( file_exists($destFolder) ) die('Writing in existing folder is not supported. Given path: "'.$destFolder.'" Please remove folder and try again.' );
		echo 'Source: '.$sourceFolder.'<br/>Destination: '.$destFolder.'<br/><br/>';
		$this->destFolder = $destFolder;
		$this->sourceFolder = $sourceFolder;
		FolderUtils::mkFullDir( $destFolder );
		FolderUtils::scanDirForFiles( $this, $sourceFolder, $fileExts, $exclFolders );
	}
	
	/*
	 * Copy given file from sourceFolder to destFolder
	 * During copy operation, all C-style comments are removed.
	 *
	 * @param $filePath string  Full file path to copy. <br/>
	 * @param $level    integer Current ply in folder structure of recursion search.
	 */
	public function iterFile( $filePath, $level )
	{
		// Write uncommented file to destination
		$relPath = substr( $filePath, strlen( $this->sourceFolder ) );
		$newFilePath = $this->destFolder.$relPath;
		echo 'Remove comments for file: '.str_repeat( '&nbsp;', $level*3 ).$newFilePath.'<br/>';
		$newFileObj = fopen( $newFilePath, 'w' );

		// Parse given source
		$source = file_get_contents( $filePath );
		$tokens = token_get_all($source);
		
		foreach ($tokens as $token) {
		    if (is_string($token)) {
		        // simple 1-character token
				fwrite( $newFileObj, $token );
		    } else {
		        // token array
		        list($id, $text) = $token;
		
		        switch ($id) {
		            case T_COMMENT:
		            case T_ML_COMMENT: // we've defined this
		            case T_DOC_COMMENT: // and this
		                // no action on comments
		                break;
		
		            default:
		                // anything else -> output "as is"
						fwrite( $newFileObj, $text );
		                break;
		        }
		    }
		}
		fclose( $newFileObj );
	}
	
	/*
	 * Copy given file from sourceFolder to destFolder
	 *
	 * @param $filePath string  Full file path to copy. <br/>
	 * @param $level    integer Current ply in folder structure of recursion search.
	 */
	public function skipFile( $filePath, $level )
	{
		$relPath = substr( $filePath, strlen( $this->sourceFolder ) );
		$newFilePath = $this->destFolder.$relPath;
		echo 'Copy file: '.str_repeat( '&nbsp;', $level*3 ).$newFilePath.'<br/>';
		copy( $filePath, $newFilePath );
	}

	/*
	 * Create given folder into destFolder building same struct as sourceFolder
	 *
	 * @param $folderPath string  Full folder path to create. <br/>
	 * @param $level      integer Current ply in folder structure of recursion search.
	 */
	public function iterFolder( $folderPath, $level ) 
	{
		$relPath = substr( $folderPath, strlen( $this->sourceFolder ) );
		$newFilePath = $this->destFolder.$relPath;
		echo 'Create folder: '.str_repeat( '&nbsp;', $level*3 ).$newFilePath.'<br/>';
		mkdir( $newFilePath );
	}
	
	/*
	 * Create given folder into destFolder building same struct as sourceFolder
	 *
	 * @param $folderPath string  Full folder path to create. <br/>
	 * @param $level      integer Current ply in folder structure of recursion search.
	 */
	public function skipFolder( $folderPath, $level )
	{
		$relPath = substr( $folderPath, strlen( $this->sourceFolder ) );
		$newFilePath = $this->destFolder.$relPath;
		echo 'Create folder: '.str_repeat( '&nbsp;', $level*3 ).$newFilePath.'<br/>';
		mkdir( $newFilePath );
	}
	
}
?>