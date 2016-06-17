<?php 

/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v6.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * The BizLinkFiles class manages all so called 'link' files (htm) under the <FileStore>/_BRANDS_ folder.
 * Objects can be assigned to zero, one or many issues. For each assignment, a 'link' file (htm)
 * is created which refers to the actual FILE storage. The 'link' files allow admin users to lookup
 * objects by directory browsing through publication / channel /issue structure in case catastrophic
 * system failure took place. 
 *
 * The 'link' files are in XHTML v1.1 format and use Unicode UTF-8 encoding.
 * They can be shown in web browser and allow admin users to download native file and look at its preview.
 * Those files can be found as follows: <FileStore>/_BRANDS_/<Publication>[/<PubChannel>[/<Issue>]]/<Object>.htm
 * For each entity at this path, the name is given followed by its id between brackets (). The name 
 * allows admin users to manually lookup objects by directory browsing, and the id guarantees uniqueness at file system.
 * For example:
 *    .../_BRANDS_/WW News (1)/Print (1)/1st issue (1)/my object (31).htm
 * For all methods there is NO BizException thrown because we don't like to fail workflow operations
 * whenever this class is in trouble. This is because its functionality is not essential to the core system to work well.
 * Instead of throwing an exception, an error is logged.
**/

class BizLinkFiles
{
	/**
	 * Determines the folder of the 'link' file (htm) at filestore under
	 * a reserved subfolder named "_BRANDS_".
	 * Path: <FileStore>/_BRANDS_/<Publication>[/<PubChannel>[/<Issue>]]/
	 *
	 * @param string $publId Publication ID
	 * @param string $publName Publication Name
	 * @param Target $target Target object. Null when object has no target.
	 * @return string The (encoded) folder where the 'link' file (htm) resides
	 */
	static private function calcLinkFileDir( $publId, $publName, $target = null )
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$pubsDir = ATTACHMENTDIRECTORY . '/_BRANDS_/';
		$publName = FolderUtils::replaceDangerousChars( $publName );
		$linkFileDir = $pubsDir . $publName . " ($publId)/";
		if ($target != null) {
			if (is_object($target->PubChannel)) {
				$channelName = FolderUtils::replaceDangerousChars( $target->PubChannel->Name );
				$channelId = $target->PubChannel->Id;
				$linkFileDir = $linkFileDir . $channelName . " ($channelId)/";
				if (is_object($target->Issue)) {
					$issueName = FolderUtils::replaceDangerousChars( $target->Issue->Name );
					$issueId = $target->Issue->Id;
					$linkFileDir = $linkFileDir . $issueName . " ($issueId)/";
				}
			}
		}
		return FolderUtils::encodePath( $linkFileDir );
	}
	
	/**
	 * Determines the folder of the 'link' file (htm) at filestore under
	 * a reserved subfolder named "_BRANDS_".
	 * Path: <FileStore>/_BRANDS_/<Publication>[/<PubChannel>[/<Issue>]]/
	 *
	 * @param string $publId Publication ID
	 * @param string $publName Publication Name
	 * @param string $objectId Object ID
	 * @param string $objectName Object Name
	 * @param Target|null $target Target object. Null when object has no target.
	 * @return string The (encoded) file path of the 'link' file (htm)
	 */
	static private function calcLinkFileName( $publId, $publName, $objectId, $objectName, $target = null )
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$linkFileDir = self::calcLinkFileDir( $publId, $publName, $target );
		$linkFileName = FolderUtils::encodePath( $objectName . " ($objectId).htm" );
		return $linkFileDir . $linkFileName;
	}	
	
	/**
	 * Removes all 'link' files (htm) from the <FileStore>/_BRANDS_ folder for one object.
	 *
	 * @param string $publId Publication ID
	 * @param string $publName Publication Name
	 * @param string $objectId Object ID
	 * @param string $objectName Object Name
	 * @param array $targets List of target objects to which the given object is currently assigned
	 */
	static public function deleteLinkFiles( $publId, $publName, $objectId, $objectName, $targets = null )
	{
		if ($targets == null || count($targets) == 0) {
			$linkFileName = self::calcLinkFileName( $publId, $publName, $objectId, $objectName, null );
			if (file_exists($linkFileName)) {
				unlink($linkFileName);
			}
			self::removeEmptyLinkFileDir( dirname( $linkFileName ) );
		}
		else {
			foreach ($targets as $target) {
				$linkFileName = self::calcLinkFileName( $publId, $publName, $objectId, $objectName, $target );
				if (file_exists($linkFileName)) {
					unlink($linkFileName);
				}
				self::removeEmptyLinkFileDir( dirname( $linkFileName ) );
			}
		}
	}
	
	/**
	 * Removes the given folder when empty. If so, it does the same for the parent folder
	 * until it reaches the root folder of the 'link' files (backtracking), which is <FileStore>/_BRANDS_
	 *
	 * @param string $dirName The (encoded) folder to be removed (when empty).
	 */
	static private function removeEmptyLinkFileDir( $dirName )
	{
		// Stop recursion when we've reached the root folder <FileStore>/_BRANDS_
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$pubsDir = FolderUtils::encodePath( ATTACHMENTDIRECTORY . '/_BRANDS_' );
		if( $dirName == $pubsDir ) {
			return; // reached root; stop recursion
		}

		if( !is_dir( $dirName ) ) {
			// Happens when link files were never created before. (Possbile since v8.0 with HTMLLINKFILES option in configserver.php)
			LogHandler::Log( 'BizLinkFiles', 'INFO', 'removeEmptyLinkFileDir: Folder "'.$dirName .'" does not exists. '.
													'Emptying link file directory is not performed.' );
			return;
		}
		
		// Determine if the folder is empty
		$thisDir = opendir( $dirName );
		if( !$thisDir ) {
			LogHandler::Log( 'BizLinkFiles', 'ERROR', 'removeEmptyLinkFileDir: Could not access folder '.$dirName );
			return; // quit on error
		}
		$itemsExist = false;
		while( ($itemName = readdir($thisDir)) !== false ) {
			if( $itemName == '.' || $itemName == '..' ) {
				// current dir and parent dir are not counted
			} else {
				$itemsExist = true;
				break; // found; quit search
			}
		}
		closedir( $thisDir );

		// When there are no folders/files inside the requested dir,
		// remove the dir and recursively do the same for the parent dir (backtracking).
		if( !$itemsExist ) { // folder empty?
			if( rmdir( $dirName ) ) {
				$parentDir = substr( $dirName, 0, strrpos( $dirName, '/' ) );
				self::removeEmptyLinkFileDir( $parentDir );
			} else {
				LogHandler::Log( 'BizLinkFiles', 'ERROR', 'removeEmptyLinkFileDir: Could not remove folder '.$dirName );
				// Recursion does not make sense here; when this folder can not be removed, the parent won't either
			}
		} // else: Nothing to do as long as there are files/folders inside
	}
	
	/**
	 * Re-creates the 'link' files (htm) for an object.
	 * Old files are removed and new files are created.
	 *
	 * @param object $object
	 * @param string $storeName
	 */
	/* // Commented out: Method not used
	static public function refreshLinkFiles( $object, $storeName )
	{
		$publId = $object->MetaData->BasicMetaData->Publication->Id;
		$objectId = $object->MetaData->BasicMetaData->ID;
		
		$linkFiles = self::scanDirs4LinkFiles( $publId, $objectId );
		foreach ($linkFiles as $linkFile) {
			if (file_exists($linkFile)) {
				unlink($linkFile);
			}
		}
		self::createLinkFiles( $object, $storeName );
	}*/
	
	/**
	 * Removes the issue folder from the <FileStore>/_BRANDS_/<Publication>/ folder.
	 * Assumed is that the issue folder is empty and all 'link' files (htm) of assigned objects
	 * are removed (purged by admin user). This function does NOT take care of 'link' file deletions.
	 * Tyically called when admin user is cleaning up an entire issue (purges objects).
	 * When there are files/folders inside the issue folder, or the folder could not be removed,
	 * an error is written to log file and false is returned.
	 *
	 * @param string $issueId Issue ID.
	 * @return boolean Wether or not the folder could be removed.
	 */
	/* // Commented out: Method not used. Maybe useful in future.
	static public function deleteIssueDir( $issueId )
	{
		$pubsDir = ATTACHMENTDIRECTORY . '/_BRANDS_/';
		$issueDirs = array();
		$pubDirs = glob($pubsDir . "*", GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
		foreach ($pubDirs as $pubDir) {
			$channelDirs = glob($pubDir . "*", GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
			foreach ($channelDirs as $channelDir) {
				$issueDirs = glob($channelDir . "($issueId)", GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
			}
		}

		foreach ($issueDirs as $issueDir) {
			$filesToDelete = glob($issueDir . "*");
			foreach ($filesToDelete as $fileToDelete) {
				if (is_file($fileToDelete)) {
					unlink($fileToDelete);
				}
			}
			rmdir($issueDir);
		}
	}*/

	/**
	 * Iterates through all pub channels and issue folders inside the <FileStore>/_BRANDS_ folder
	 * for a given publication. Inside it scans for all 'link' files (htm) of the given object.
	 * All found paths to 'link' files (htm) are collected and returned to caller.
	 *
	 * @param string $publId Publication ID
	 * @param string $objectId Object ID
	 * @return array of string; file paths to found 'link' files (htm)
	 */
	/* // Commented out: Method not used. Maybe useful in future.
	static private function scanDirs4LinkFiles( $publId, $objectId )
	{
		$pubsDir = ATTACHMENTDIRECTORY . '/_BRANDS_/';
		$dirsToScan = array();
		$pubDirs = glob($pubsDir . "*($publId)" , GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
		$dirsToScan = array_merge($dirsToScan, $pubDirs);
		foreach ($pubDirs as $pubDir) {
			$channelDirs = glob($pubDir . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
			$dirsToScan = array_merge($dirsToScan, $channelDirs);
			foreach ($channelDirs as $channelDir) {
				$issueDirs = glob($channelDir . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
				$dirsToScan = array_merge($dirsToScan, $issueDirs);
			}
		}

		$objectMask = "*($objectId).htm";
		$linkFiles = array();
		foreach ($dirsToScan as $dirToScan) {
			$linkFiles = array_merge($linkFiles, glob($dirToScan . $objectMask));
		}
		return $linkFiles;
	}*/
	
	/**
	 * Creates a 'link' file (htm) somewhere (deep) under <FileStore>/_BRANDS_ folder.
	 * The 'link' file is created for one object and one target (issue).\
	 * The used file format is XHTML v1.1 and the encoding is Unicode UTF-8.
	 *
	 * @param resource $fp File pointer of the 'link' file (htm)
	 * @param int $publId Publication ID
	 * @param string $publName Publication name
	 * @param string $objectId Object ID
	 * @param string $objectName Object name
	 * @param string $objectType Object type (layout, article, etc)
	 * @param string $objectVersion Object version in major.minor notation
	 * @param string $objectFormat Object format (mime type)
	 * @param string $storeName The original object name initially used to store object at filestore
	 * @param Target $target The object's target
	 */
	static private function writeLinkFile( $fp, $publId, $publName, $objectId, $objectName, $objectType, 
								$objectVersion, $objectFormat, $storeName, $target )
	{
		if ($target) {
			$channelName = $target->PubChannel ? $target->PubChannel->Name : '';
			$issueName = $target->Issue ? $target->Issue->Name : '';
		}
		else {
			$channelName = '';
			$issueName = '';
		}

		$issueDir = $channelDir = $publDir = null;
		if ($target != null) {
			if (is_object($target->Issue)) {
				$issueDir = './';
				$channelDir = '../';
				$publDir = '../../';
			}
			elseif (is_object($target->PubChannel)) {
				$channelDir = './';
				$publDir = '../';
			}
			else {
				$publDir = './';
			}
		}
		else {
			$publDir = './';
		}

		// Determine native/preview/thumb file paths of the object
		$relpath2filestore = self::calcRelPath2FileStore( $target );
		$fileNames = self::getObjectFileNames( $storeName, $objectId, $objectFormat, $objectVersion );
		$filesdir = $relpath2filestore . '.';
		$nativeFileName  = isset($fileNames['native'])  ? $nativeFileName  = $relpath2filestore . $fileNames['native']  : null;
		$previewFileName = isset($fileNames['preview']) ? $previewFileName = $relpath2filestore . $fileNames['preview'] : null;
		$thumbFileName   = isset($fileNames['thumb'])   ? $thumbFileName   = $relpath2filestore . $fileNames['thumb']   : null;

		$channelLink = null;
		$issueLink = null;
		// Generate the 'link' file in XHTML 1.1 format
		// IMPORTANT: When you make changes below, validate them at http://validator.w3.org/#validate_by_upload
		$pubLink = '<a href="'.$publDir.'">'.$publName.'</a>';
		if ($channelDir) {$channelLink = '<a href="'.$channelDir.'">'.$channelName.'</a>';}
		if ($issueDir) {$issueLink = '<a href="'.$issueDir.'">'.$issueName.'</a>';}
		$filesdirlink = '<a href="'.$filesdir.'">All files</a>';
		$thumblink = $thumbFileName ? '<img src="'.$thumbFileName.'" alt="Thumbnail"/>' : '';
		$nativelink = $nativeFileName ? '<a href="'.$nativeFileName.'">'.$objectId.': '.$objectName.' ('.$objectFormat.')</a>' : '';
		$previewlink = $previewFileName ? '<a href="'.$previewFileName.'" title="Click for Preview">'.$thumblink.'</a>' : '';
		$title = "$objectType: $objectName ($objectId)";

		if ($channelDir && $issueDir) {
			$body = "\t\t<p>$pubLink - $channelLink - $issueLink</p>\n";
		} elseif ($channelDir) {
			$body = "\t\t<p>$pubLink - $channelLink</p>\n";
		} else {
			$body = "\t\t<p>$pubLink</p>\n";
		}
		$body .= "\t\t<p>$nativelink</p>\n";
		$body .= "\t\t<p>$filesdirlink</p>\n";
		if ($previewFileName) {
			$body .= "\t\t<p>$previewlink</p>\n";
		}
		// To the 'link' file we add some hidden fields to let XML parsers (client tools) get essential data out
		$body .= "\t\t".'<fieldset style="display:none;">'."\n";
		$body .= "\t\t\t".'<input type="hidden" id="ID" value="'.$objectId.'"/>'."\n";
		$body .= "\t\t\t".'<input type="hidden" id="Name" value="'.$objectName.'"/>'."\n";
		$body .= "\t\t\t".'<input type="hidden" id="Type" value="'.$objectType.'"/>'."\n";
		$body .= "\t\t\t".'<input type="hidden" id="Format" value="'.$objectFormat.'"/>'."\n";
		$body .= "\t\t\t".'<input type="hidden" id="Version" value="'.$objectVersion.'"/>'."\n";
		$body .= "\t\t\t".'<input type="hidden" id="PublicationId" value="'.$publId.'"/>'."\n";
		if ($target != null) {
			if( is_object($target->Issue) ) {
				$body .= "\t\t\t".'<input type="hidden" id="IssueId" value="'.$target->Issue->Id.'"/>.'."\n";
			}
			if( is_object($target->PubChannel) ) {
				$body .= "\t\t\t".'<input type="hidden" id="PubChannelId" value="'.$target->PubChannel->Id.'"/>'."\n";
			}
		}
		$body .= "\t\t</fieldset>\n";

		$html = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>'.$title.'</title>
		<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8" />
	</head>
	<body style="text-align:center">
'.$body.'		
	</body>
</html>';
		fwrite($fp, $html);
		return;
	}

	/**
	 * Returns collection of file paths of native/thmub/preview renditions of a given object.
	 * Paths are relatively to the filestore (ATTACHMENTDIRECTORY) and don't start with slash (/).
	 *
	 * @param string $storeName The original object name initially used to store object at filestore
	 * @param string $objectId Object ID
	 * @param string $format File format of object's native file
	 * @param string $version Version number of object in major.minor notation
	 * @return array of string; List of relative file paths
	 */
	static private function getObjectFileNames( $storeName, $objectId, $format, $version )
	{
		$fileNames = array();
		//BZ#10786 get renditions from database first
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$rows =  DBObject::getObjectRows($objectId);
		$types = unserialize($rows['types']);
		$renditions = array_keys($types);
		if (count($renditions) > 0){
			require_once BASEDIR.'/server/bizclasses/BizStorage.php';
			$filestorage = new FileStorage( $storeName, $objectId, reset($renditions), $format, $version );
			$fileNames = $filestorage->listFilenames( $objectId, $storeName, $renditions, $version );
			
			// Change full paths into relative paths by removing filestore path (ATTACHMENTDIRECTORY)
			$len = strlen(ATTACHMENTDIRECTORY) + 1;
			foreach ($fileNames as &$fileName) {
				$fileName = substr($fileName, $len);
			}
		}
		
		return $fileNames;
	}

	/**
	 * Creates/Updates one 'link' files (htm) for a given object and target (issue).
	 * Files are created somewhere (deep) under <FileStore>/_BRANDS_ folder.
	 *
	 * @param string $publId Publication ID
	 * @param string $publName Publication name
	 * @param string $objectId Object ID
	 * @param string $objectName Object name
	 * @param string $objectType Object type (layout, article, etc)
	 * @param string $objectVersion Object version in major.minor notation
	 * @param string $objectFormat Object format (mime type)
	 * @param string $storeName The original object name initially used to store object at filestore
	 * @param Target|null $target Target object to which the given object is currently assigned
	 * @return boolean Whether or not the file could be created
	 */
	static private function createLinkFile( $publId, $publName, $objectId, $objectName, $objectType, 
											$objectVersion, $objectFormat, $storeName, $target )
	{
		$linkFileName = self::calcLinkFileName( $publId, $publName, $objectId, $objectName, $target );
		$fp = fopen($linkFileName, 'w');
		if (!$fp) {
			LogHandler::Log( 'BizLinkFiles', 'ERROR', 'createLinkFile: Could not open file '.$linkFileName );
			return false;
		}
		self::writeLinkFile( $fp, $publId, $publName, $objectId, $objectName, $objectType, 
							$objectVersion, $objectFormat, $storeName, $target );
		fclose($fp);
		return true;
	}
	
	/**
	 * Creates/Updates all 'link' files (htm) for a given object. Per target (issue), one file is created.
	 * Files are created somewhere (deep) under <FileStore>/_BRANDS_ folder.
	 *
	 * @param string $publId Publication ID
	 * @param string $publName Publication Name
	 * @param string $objectId Object ID
	 * @param string $objectName Object name
	 * @param string $objectType Object type (layout, article, etc)
	 * @param string $objectVersion Object version in major.minor notation
	 * @param string $objectFormat Object format (mime type)
	 * @param string $storeName The original object name initially used to store object at filestore
	 * @param Target[] $targets List of target objects to which the given object is currently assigned
	 */
	static public function createLinkFiles( $publId, $publName, $objectId, $objectName, $objectType, 
											$objectVersion, $objectFormat, $storeName, $targets )
	{
		if ($targets == null || count($targets) == 0) {
			$dir = self::forceLinkFileDirs( $publId, $publName, null );
			if( !empty($dir) ) { // success?
				self::createLinkFile( $publId, $publName, $objectId, $objectName, $objectType, 
											$objectVersion, $objectFormat, $storeName, null );
			}
		}
		else {
			foreach ($targets as $target) {
				$dir = self::forceLinkFileDirs( $publId, $publName, $target );
				if( !empty($dir) ) { // success?
					self::createLinkFile( $publId, $publName, $objectId, $objectName, $objectType, 
											$objectVersion, $objectFormat, $storeName, $target );
				}
			}
		}
	}

	/**
	 * Same as {@link: createLinkFiles} but then accepting an object.
	 *
	 * @param string $object The Object with MetaData and Targets defined/resolved
	 * @param string $storeName The original object name initially used to store object at filestore
	 */
	static public function createLinkFilesObj( $object, $storeName )
	{
		$targets = $object->Targets;

		$publId = $object->MetaData->BasicMetaData->Publication->Id;
		$publName = $object->MetaData->BasicMetaData->Publication->Name;

		$objectId = $object->MetaData->BasicMetaData->ID;
		$objectName = $object->MetaData->BasicMetaData->Name;
		$objectType = $object->MetaData->BasicMetaData->Type;
		$objectVersion = $object->MetaData->WorkflowMetaData->Version;
		$objectFormat = $object->MetaData->ContentMetaData->Format;
		
		self::createLinkFiles( $publId, $publName, $objectId, $objectName, $objectType, 
								$objectVersion, $objectFormat, $storeName, $targets );
	}

	/**
	 * Determines the relative file path of a 'link' file (htm) to the filestore (parent) folder.
	 * For objects that are NOT assigned to channels or issues, this is '../../'
	 * For objects assigned to channels, but NOT to issues, this is '../../../'
	 * For objects assigned to channels and issues, this is '../../../../'
	 *
	 * @param object $target The object's target
	 * @return string The relative path
	 */
	static private function calcRelPath2FileStore( $target )
	{
		$result = '../../';
		if ($target) {
			if (is_object($target->PubChannel)) {
				$result .= '../';
				if (is_object($target->Issue)) {
					$result .= '../';
				}
			}
		}
		return $result;
	}
	
	/**
	 * Makes sure the folder of the 'link' file (htm) is created.
	 * It detects if any of the publication/channel/issue folders is missing, 
	 * which then will be created.
	 * This is all done for one object and one target.
	 *
	 * @param string $publId Publication ID
	 * @param string $publName Publication name
	 * @param Target|null $target Object's target
	 * @return string The path of the folder. Empty on error.
	 */
	static private function forceLinkFileDirs( $publId, $publName, $target )
	{
		require_once BASEDIR . '/server/utils/FolderUtils.class.php';
		$linkFileDir = self::calcLinkFileDir( $publId, $publName, $target );
		FolderUtils::mkFullDir( $linkFileDir );
		if( !file_exists( $linkFileDir ) ) {
			LogHandler::Log( 'BizLinkFiles', 'ERROR', 'forceLinkFileDirs: Could not create folder '.$linkFileDir );
			return '';
		}
		return $linkFileDir;
	}
}
