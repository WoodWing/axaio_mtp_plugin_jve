<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class WwcxToWcmlUtils
{
	private $guid;
	
	/**
	 * Retrieves the file content from a given object
	 *
	 * @param object $object
	 * @return string $fileCont File content
	 */
	public function getContent( $object )
	{
		$fileContent = null;
		if( isset($object->Files) && count($object->Files) > 0 ) {
			require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$fileContent = $transferServer->getContent( $object->Files[0] );
		}
		return $fileContent;
	}

	/**
	 * Sets the latest converted content back to the given attachment
	 *
	 * @param string $wcmlPath
	 * @param Attachment $attachment
	 * @param string $format The object file's mime type
	 */
	public function setContent( $wcmlPath, Attachment $attachment, $format )
	{
		$icDoc = new DOMDocument();
		$icDoc->loadXML( file_get_contents( $wcmlPath ));

		// >>> TODO: To check, why file deletion is needed:
		// When below deleteFile function call is remark, there seems like client didn't receive the converted attachment
		// Check on writeContentToFileTransferServer, the file did successfully being overwritten by latest content,
		// but it somehow fail to load and shows wheel spinning in Content Station.
		// <<<
			
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		//$transferServer->deleteFile( $attachment->FilePath );
		$transferServer->writeContentToFileTransferServer( $icDoc->saveXML(), $attachment );
		$attachment->Type = $format;
	}
	
	/**
	 * Creates a workspace folder, seen from Enterprise Server point of view.
	 *
	 * @return string Workspace folder. Returns NULL when creation failed.
	 */
	public function createWorkspaceFolder()
	{
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';

		// Create session subfolder at workspace to store files for editing and its related or generated files
		$this->guid = NumberUtils::createGUID();
		$workspaceDir = WEBEDITDIR . $this->guid;
		if( file_exists( $workspaceDir ) ) { // should not happen
			LogHandler::Log( 'WwcxToWcmlConversion', 'ERROR', 'Could not create workspace folder. '.
				'Temporary workspace folder "'.$workspaceDir.'" already exists.' );
			$this->guid = null; // forget GUID to avoid futher usage
			return null;
		}
		$old_umask = umask(0); // Needed for mkdir, see http://www.php.net/umask
		if( !mkdir( $workspaceDir, 0777 ) ) {
			LogHandler::Log( 'WwcxToWcmlConversion', 'ERROR', 'Could not create workspace folder "'.$workspaceDir.'". ' );
			$this->guid = null; // forget GUID to avoid futher usage
			return null;
		}
		chmod($workspaceDir, 0777);	 // We cannot always set access with mkdir because of umask	
		umask($old_umask);

		return $workspaceDir;
	}
	
	/**
	 * Retrieves the workspace folder, seen from InDesign Servers's point of view.
	 *
	 * @return string Workspace folder
	 */
	public function getIdsWorkspaceFolder()
	{
		return $this->guid ? WEBEDITDIRIDSERV . $this->guid : null;
	}

	/**
	 * Writes a file content to a temporary workspace directory
	 *
	 * @param string $fileContent The native file string content
	 * @param string $wwcxLocalPath Temporary workspace file
	 * @return boolean True when file write success, else false
	 */
	public function writeFileToWorkspace( $fileContent, $wwcxLocalPath )
	{
		require_once BASEDIR.'/server/utils/FileHandler.class.php';
		$fileHandler = new FileHandler();
		return $fileHandler->writeFile( $wwcxLocalPath, $fileContent );
	}

	/**
	 * Cleans up the workspace folder
	 */
	public function cleanupWorkSpace()
	{
		if( $this->guid ) {
			$workspaceDir = WEBEDITDIR . $this->guid;
			if( file_exists( $workspaceDir ) ) {
				require_once BASEDIR . '/server/utils/FolderUtils.class.php';
				if( FolderUtils::cleanDirRecursive( $workspaceDir ) ) {
					LogHandler::Log( 'WwcxToWcmlConversion', 'INFO', 
						'Removed temporary workspace folder: "'.$workspaceDir.'".' );
				} else {
					LogHandler::Log( 'WwcxToWcmlConversion', 'WARN', 
						'Could not remove temporary workspace folder "'.$workspaceDir.'".' );
					// No reason to throw error since that would block the workflow operation
				}
			} else {
				LogHandler::Log( 'WwcxToWcmlConversion', 'WARN', 
					'Could not remove temporary workspace folder "'.$workspaceDir.'" since it does not exist.' );
				// No reason to throw error since that would block the workflow operation
			}
			$this->guid = null; // forget GUID to avoid futher usage
		} else {
			LogHandler::Log( 'WwcxToWcmlConversion', 'WARN', 
				'Could not remove temporary workspace folder since it was not created before.' );
			// No reason to throw error since that would block the workflow operation
		}
	}

	/**
	 * Checks if there is an InDesign Server CS5 (or later, up till CC 2015) configured and set active.
	 *
	 * @return boolean True when found, else false
	 */
	public function hasActiveInDesignServerForWcmlConversion()
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		$idsObjs = BizInDesignServer::listInDesignServers();
		foreach( $idsObjs as $idsObj ) {
    		if( $idsObj->Active && (int)$idsObj->ServerVersion >= 7 && (int)$idsObj->ServerVersion <= 11 ) { // 7 = CS5, 11 = CC 2015
    			return true; // Active InDesign Server found in the correct version range
			}
		}
		return false;
	}
}