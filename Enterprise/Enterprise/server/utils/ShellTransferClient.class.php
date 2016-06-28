<?php
/**
 * Client proxy class to talk to the Transfer Server through shell commands such as HSCP.
 * This is a client helper class that deals with file uploads/downloads.
 * Normally, there is no reason to travel through HTTP since the PHP client is already
 * running inside Enterprise Server, and so this class should NOT be used!
 * Nevertheless, for testing or integration, this class can be of great help. 
 * In architecture, the TransferClient class and the SoapClient class are in the same 'layer'.
 * They are each other co-workers; One does the file transfers and the other does the messaging.
 *
 * @package Enterprise
 * @subpackage BizClasses
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/TransferClient.class.php';

class WW_Utils_ShellTransferClient extends WW_Utils_TransferClient
{
	private $transfer = null;
	
	public function __construct( $transfer )
	{
		$this->transfer = $transfer;
	}

	/**
	 * Provides a list of arguments that needs to be filled in at the shell commands.
	 *
	 * @param Attachment $attachment
	 * @param string $fileguid
	 * @return array
	 */
	private function getCommandParams( Attachment $attachment, $fileguid )
	{
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		return array( 
			'host' => 'macedsnow.local', // ???
			'localfile' => $attachment->FilePath,
			'remotefile' => $transferServer->composeTransferPath( $fileguid ), // client IP 
			'username' => 'wwfiletransfer'
			// port? password?
		);
	}
	
	/**
	 * Uploads a file to transfer server through running shell commands.
	 * The FilePath (or Content), Type and Rendition props must be set.
	 * After calling, the file will be uploaded and the FileUrl will be set.
	 *
	 * @param Attachment $attachment 
	 * @param string $compression This feature is not supported by this subclass.
	 * @param string $httpMethod This feature is not supported by this subclass.
	 * @return bool Tells if upload was successful. If true, the $attachment->FileUrl is set.
	 */
	public function uploadFile( Attachment $attachment, $compression = '', $httpMethod = 'PUT' )
	{
		$compression = $compression; $httpMethod=$httpMethod; // keep code analyzer happy
		
		// When content given, create temp file.
		$tmpFile = false;
		if( is_null($attachment->FilePath) && !is_null($attachment->Content) ) {
			$attachment->FilePath = tempnam( sys_get_temp_dir(), 'hscp' );
			chmod( $attachment->FilePath, 0777 );
			file_put_contents( $attachment->FilePath, $attachment->Content );
			$attachment->Content = null;
			$tmpFile = true;
		}

		// Make-up a new fileguid
		require_once BASEDIR . '/server/utils/NumberUtils.class.php';
		$fileguid = NumberUtils::createGUID(); // create unique name for our file in transferserver
		$uploadUrl = HTTP_FILE_TRANSFER_LOCAL_URL . '?fileguid='.$fileguid;
		LogHandler::Log( __CLASS__, 'INFO',  "uploadFile: Started upload \"$uploadUrl\" over HSCP." );
		
		// Read template command file (custom config)
		$fileExt = OS == 'WIN' ? '.bat' : '.sh';
		$cmdFile = BASEDIR.'/config/shellscripts/filetransfer/'
			.$this->transfer.'/'.strtolower( OS ).'/fileupload'.$fileExt; 
	
		PerformanceProfiler::startProfile( 'transfer client file upload', 3 );
		$retVal = false;

		require_once BASEDIR.'/server/utils/ShellScript.php';
		$cmd = new WW_Utils_ShellScript();
		if( $cmd->init( $this->transfer.' fileupload', $cmdFile ) ) {
			/*$errorMsg =*/ $cmd->shell_exec( $this->getCommandParams( $attachment, $fileguid ) );
			$retVal = true;
			// Commented out: Progress info is returned through stderr(!?) and so we can not 
			// tell difference between an error and progress...?
			/*if( empty($errorMsg) ) {
				$retVal = true;
			} else {
				LogHandler::Log( __CLASS__, 'ERROR',  'uploadFile failed: '.$errorMsg );
			}*/
		}
		PerformanceProfiler::stopProfile( 'transfer client file upload', 3 );

		// Cleanup temp file
		if( $tmpFile ) {
			unlink( $attachment->FilePath );
		}
		
		// Clear-up attachment props (to indicate caller what is valid and not)
		if( $retVal ) {
			$attachment->FileUrl = $uploadUrl;
			$attachment->FilePath = null; 
			$attachment->Content = null;
		}
		return $retVal;
	}

	/**
	 * Downloads a file from the transfer server through shell commands.
	 * The FileUrl property must be set. After downloading the downloaded file
	 * is deleted from the transferserver if $cleanup is set to true.
	 *
	 * @param Attachment $attachment
	 * @param bool $cleanup Whether or not to remove the file from Transfer Folder after download.
	 * @param string $compression This compression feature is not supported by this subclass.
	 * @param string $stripWcml This optimization feature is not supported by this subclass.
	 * @return bool Tells if download was successful. If true, the $attachment->Content is set.
	 */	
	public function downloadFile( Attachment $attachment, $cleanup = true, $compression = '' )
	{
		$compression = $compression; // keep code analyzer happy

		// Read template command file (custom config)
		$fileExt = OS == 'WIN' ? '.bat' : '.sh';
		$cmdFile = BASEDIR.'/config/shellscripts/filetransfer/'
			.$this->transfer.'/'.strtolower( OS ).'/filedownload'.$fileExt; 
	
		$retVal = false;
		$fileguid = $this->getFileGuidFromUrl( $attachment->FileUrl );

		PerformanceProfiler::startProfile( 'transfer client file download', 3 );
		require_once BASEDIR.'/server/utils/ShellScript.php';
		$cmd = new WW_Utils_ShellScript();
		if( $cmd->init( $this->transfer.' filedownload', $cmdFile ) ) {
			/*$errorMsg =*/ $cmd->shell_exec( $this->getCommandParams( $attachment, $fileguid ) );
			$retVal = true;
		}
		PerformanceProfiler::stopProfile( 'transfer client file download', 3 );
		if( $cleanup ) {
			$this->cleanupFile( $attachment );
		}
		return $retVal;
	}

	/**
	 * Removes a file from the Transfer Folder through HTTP (!). Assumed is that shell
	 * commands (such as HSCP) does NOT support deletes, so parent HTTP client is used
	 * since packages are really small/fast anyway.
	 *
	 * @param Attachment $attachment Specifies the file to remove. The FileUrl property must be set.
	 * @return bool Tells if file was successfully removed.
	 */	
	public function cleanupFile( Attachment $attachment )
	{
		$this->cleanupFile( $attachment ); // oursourced to parental class
	}
}
