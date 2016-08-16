<?php
/**
 * @package    	Enterprise
 * @subpackage HealthCheck2
 * @since       v8.3
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * 
 *  CopyHTMLResourceCache class handles the copying of the HTMLResource cache from the old location to the new one.
 *  Prior to version 8. the HTML Resource cache was stored within the 'export' folder. The problem was that the 
 *  'export' folder could not be cleaned anymore which resulted in storage problems. From version 8.3 onwards the
 *  cache is stored in a 'persistent' location.
 *
 */
class CopyHTMLResourceCache
{
	const UPDATEFLAG = 'adobedps_copy_cache'; 
	private $foldersToCopy = array();
	private $foldersFailure = array();

	/**
	 * Copies the HTML Resource caches from the 'export' location to the 'persistent' location. This is only needed if
	 * the copy action has run before.
	 * @return boolean True if already copied or successful copied, else false.
	 */
	public function doCopyHTMLResourcesCache()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		
		if ( $this->isCopied() ) {
			return true;
		} else {
			$this->setCopyFlag( '0' ); // By setting the flag we know that the copy process has run.
			$this->copyFolders();
			if ( $this->foldersFailure ) {
				return false;
			} else {
				$this->setCopyFlag( '1' ); // Copy action was successful. 
			}
		}

		return true;
	}

	/**
	 * Checks if the copy action is needed.
	 * @return boolean True if needed else false.
	 */
	public function isCopyNeeded()
	{
		$result = false;
		if ( !$this->isCopied() ) {
			$oldBasePath = substr( ADOBEDPS_EXPORTDIR, 0, -1 ); // remove trailing '/'
			$files = scandir($oldBasePath);
			if ( $files ) foreach( $files as $file) {
				if ( substr( $file, 0, 1) !== '.') { //Skip hidden files, parent and current directory	
					$result = true;
				}	
			}
		}  
		
		return $result;
	}	
	
	/**
	 * Returns whether or not the copy action has already been completed or not.
	 * @return boolean True if already copied else false.
	 */
	private function isCopied()
	{
		$isUpdated = false;
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		$row = DBConfig::getRow( DBConfig::TABLENAME, 'name = ?', '*', array( self::UPDATEFLAG ) );

		if ( $row ) {
			$isUpdated = ($row['value'] == '1'); // '1' means successful
		}

		return $isUpdated;
	}	

	/**
	 * Cache folders that have not been copied.
	 * @return array
	 */
	public function getFoldersFailure()
	{
		return $this->foldersFailure;
	}	

	/**
	 * Stores a variable in the database to denote that the folder copy was done successfully.
	 * @return bool Whether or not the updated flag was set correctly.
	 */
	private function setCopyFlag( $flag )
	{
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( self::UPDATEFLAG , $flag );
	}		
	
	/**
	 * Copies the cache folders from the 'export' location to the 'persistent location. The complete 'export' folder
	 * is recursively scanned. All folders named like 'HTMLResources_cache' are copied. As the structure of the new and
	 * old location are the same the base path of the old structure is replaced by the base path of the new location.  
	 */
	private function copyFolders()
	{
		$oldBasePath = ADOBEDPS_EXPORTDIR;
		$oldBasePath = substr( $oldBasePath, 0, -1 ); // remove trailing '/'
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		FolderUtils::scanDirForFiles( $this, $oldBasePath );
		$newBasePath = ADOBEDPS_PERSISTENTDIR; 
		
		foreach( $this->foldersToCopy as $folderToCopy) {
			$newPath = str_replace($oldBasePath, $newBasePath, $folderToCopy); 
			require_once BASEDIR.'/server/utils/TestSuite.php';
			$utils = new WW_Utils_TestSuite();
			if ( $utils->dirPathExists( $folderToCopy ) && !$utils->dirPathExists( $newPath )) {
				FolderUtils::copyDirectoryRecursively( $folderToCopy, $newPath );
				if ( $utils->dirPathExists( $newPath )) {
					FolderUtils::cleanDirRecursive( $folderToCopy, true );
				} else {
					$this->foldersFailure[] = $folderToCopy;
				}
			}
		}
	}

	/**
	 * Called by the FolderUtils class, which iterates through the subfolder and calls this function.
	 * The cache folders are added to an array which will be used afterwards to copy the folders and remove the old
	 * ones.
	 * 
	 * @param type $folderPath
	 * @param type $level
	 */
	public function iterFolder( $folderPath, $level )
	{
		$baseName = basename($folderPath);
		if ( $baseName == 'HTMLResources_cache' ) {
			$this->foldersToCopy[] = $folderPath;
		}
		
	}
	
	// These three functions are called by parent class, but have no meaning here.
	public function skipFile( $filePath, $level ) {}
	public function skipFolder( $folderPath, $level ) {}
	public function iterFile( $filePath, $level ) {}
}
