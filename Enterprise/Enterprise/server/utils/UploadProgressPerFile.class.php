<?php
/**
 * Co-worker class for WW_Utils_DigitalPublishingSuiteClient to monitor the upload for one file.
 *
 * Introduced to support the parallel upload feature for Adobe DPS.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v7.6.7
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_UploadProgressPerFile
{
	private $curl = null;
	private $uploadedBytes = null;
	private $filePath = null;
	private $fileSize = null;
	private $fileId = null;
	
	private static $globalFileId = 0;
	private static $globalUploadedBytes = 0;
	private static $globalUploadSize = 0;
	
	public function __construct( $filePath )
	{
		$this->filePath = $filePath;
		$this->uploadedBytes = 0;
		$this->fileSize = filesize($filePath);
		if( !is_null(self::$globalFileId) ) {
			self::$globalFileId += 1;
		}
		self::$globalUploadSize += $this->fileSize;
		$this->fileId = self::$globalFileId;
		LogHandler::Log( 'UploadProgressPerFile', 'DEBUG', 
						"About to upload file #{$this->fileId}: $filePath" );
	}
	
	/**
	 * Callback function for the progress when using the cURL adapter.
	 *
	 * @param integer $downloadSize
	 * @param integer $downloaded
	 * @param integer $uploadSize
	 * @param integer $uploaded
	 */
	public function curlProgressCallback( $downloadSize, $downloaded, $uploadSize, $uploaded )
	{
		if( $uploaded > 0 ) {
			// When the caller does not set the file size, we use the raw http body size
			// as given by PHP. However, that is less accurate, since it is bigger than
			// the file size. As a result, the progress will reach 100% earlier than
			// predicted and remains 100% for a while.
			if( !$this->fileSize ) {
				$this->fileSize = $uploadSize; // less accurate
			}

			// cURL does callback this function every few miliseconds. This gives us the chance to:
			// - avoid the semaphore to expire (or else someone else could start publishing the very same issue/edition).
			// - avoid the ticket to expire (or else the service would run into problems in the end).
			// - update the progressbar with byte count of files being uploaded.
			//
			// The semaphore expires after one minute (60 seconds) without updates. The ticket
			// expired after one hour. The semaphore and ticket are updated in the database at 
			// BizSemaphore::refreshSession(). Updating the database every few miliseconds will 
			// put huge stress on it.This is something to avoid, as implemented with the
			// $needToRefreshSemaphore flag. The flag is raised every 5 seconds.
			//
			// CS is asking every second for progress information during publishing operations.
			// The progressbar info is saved on disk in a temp file. Updating the file every
			// new miliseconds would put huge stress on the harddisk. This is something to avoid, as 
			// implemented with the $needsToUpdateProgressbar flag. The flag is raised every 0.5 second.
			//
			// Note that for the static variables below, there is only ONE global instance in the session 
			// so it is shared between parallel uploads, as intended.
			//
			$now = microtime( true ); // current timestamp in microseconds
			static $lastRefreshSemaphore = null;
			static $lastUpdateProgressbar = null;
			if( is_null( $lastRefreshSemaphore ) ) {
				$lastRefreshSemaphore = $now; // init at first call
			}
			if( is_null( $lastUpdateProgressbar ) ) {
				$lastUpdateProgressbar = $now; // init at first call
			}
			$needToRefreshSemaphore = ($now - $lastRefreshSemaphore) >= 5.0; // 5 seconds elapsed?
			$needsToUpdateProgressbar = ($now - $lastUpdateProgressbar >= 0.5); // half seconds elapsed?
			if( $needToRefreshSemaphore ) {
				$lastRefreshSemaphore = $now; // remember for next call
			}
			if( $needsToUpdateProgressbar ) {
				$lastUpdateProgressbar = $now; // remember for next call
			}

			// Update the progressbar.
			if( $needsToUpdateProgressbar || // when called not too often
				$uploadSize == $this->fileSize ) { // when file upload completed
				
				require_once BASEDIR.'/server/utils/PublishingProgressBar.class.php';
				$progressIndicator = WW_Utils_PublishingProgressBarStore::getProgressIndicator();
				if ( !is_null( $progressIndicator ) ) {
					$progressInBytes = $uploaded - $this->uploadedBytes;
					self::$globalUploadedBytes += $progressInBytes;

					LogHandler::Log( 'UploadProgressPerFile', 'DEBUG', 
						"Progress info for file #{$this->fileId}: ".
						"Bytes to upload: $uploadSize, ".
						"Bytes uploaded: $uploaded <br/>".
						"Global progress info: ".
						"Bytes to upload: ".self::$globalUploadSize.", ".
						"Bytes uploaded: ".self::$globalUploadedBytes );

					$phaseId = 'upload';
					$progressIndicator->setProgressNext( $phaseId, $progressInBytes );
					$this->uploadedBytes = $uploaded;
				}
			}

			// Make sure uploading does not result in expiration of the semaphore.
			if( $needToRefreshSemaphore ) {
				require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
				$semaphore = BizSemaphore::getSessionSemaphoreId();
				if( !is_null( $semaphore ) ) {
					BizSemaphore::refreshSession( $semaphore );
				}
			}
		}
	}
}
