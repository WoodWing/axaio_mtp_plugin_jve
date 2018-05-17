<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This class provides functionality to read/write data in the local cache of the application server machine.
 *
 * Enterprise Server can be installed on multiple application servers. For each application server a local cache is created.
 * Assumed is that SSD is used so a local read is faster than reading from DB or FileStore installed on another machine.
 * And, reading locally reduces the stress on a DB or FileStore which is better scalable. This way performance can be
 * gained by buffering data in a local cache. The local cache is built-up from the data taken from DB or FileStore.
 *
 * The data is stored in so called 'buckets' in the local cache. Each bucket has a fixed name (id) and a version.
 * Whenever a data set changes in the DB or FileStore, a new version (GUID) is generated for the bucket that caches it.
 * For each session, the bucket ids and version are read from DB and so it can be checked if a bucket is outdated.
 * If that is the case, the caller is responsible to read the data from DB or FileStore and repopulate that bucket.
 *
 * Inside a bucket, so called 'items' can be cached. For example, an item could hold the result of a DB query. The data
 * of an item is written in a file in the local cache's folder structure. The item has a fixed name (id) which is used
 * as file name. Callers should keep in mind that items should be cached in 'lazy loading' matter to avoid a heavy load
 * on the DB or FileStore when the bucket gets outdated implicitly making all its items outdated.
 *
 * The following folder structure is used by the local cache:
 * - system temp folder
 *   - "ww_ent_server_local_cache" root folder
 *     - "<Enterprise System ID>" folder
 *       - "<date timestamp>" folder
 *         - "<Enterprise Server version>" base folder
 *           - <bucket id> folder
 *             - <bucket version> folder
 *               L> <item id> files
 *               ...
 * Notes:
 * - The cache must match with the current DB and FileStore. For this reason the Enterprise System ID is in the path.
 * - The cache should be automatically cleaned in nightly hours. For this reason the date timestamp is in the path.
 * - When one data set changes, others remain the same. For this reason the bucket id is in the path.
 * - A data set may change whenever related data is mutated. For this reason the bucket version is in the path.
 * - The data structure of cached item may change in time. For this reason the Enterprise Server version is in the path.
 *
 * Since the "date timestamp" is in the path, every day a new cache is built-up underneath that folder. The reason to make
 * one per day is to make sure that the process that cleans outdated folders never interferes with other processes that
 * happened to read/write cache files at the very moment the cache gets cleaned (which could cause file access issues).
 * The cleaning is automatically done in context of the first call on the new day (when it is about to create a new date
 * timestamp folder). It checks whether there are folders with a "date timestamp" older than 2 days and deletes those.
 *
 * Error handling - This class does not throw BizException because it is up to the caller to decide whether or not the
 * local cache functionality is mandatory.
 */

class WW_BizClasses_LocalCache
{
	const FILE_ACCESS_MODE = 0644; // read+write access for PHP, readonly for the group and others
	const DIRECTORY_ACCESS_MODE = 0755; // same as above, but now with execute (x) rights to allow 'cd' into a folder

	/**
	 * Write data in a bucket item in the local cache.
	 *
	 * @param string $bucketId
	 * @param string $itemId
	 * @param string $data The data to write.
	 * @return bool Whether or not it could be successfully written into the cache.
	 */
	public function writeBucketItemData( string $bucketId, string $itemId, string $data ): bool
	{
		if( !$this->validateBucketId( $bucketId ) || !$this->validateItemId( $itemId ) ) {
			return false;
		}
		$bucketPath = $this->composeBucketPath( $bucketId );
		if( $bucketPath === false ){
			return false;
		}
		PerformanceProfiler::startProfile( 'LocalCache - '.$bucketId, 4 );
		$wroteBytes = $this->writeDataInFile( $bucketPath.'/'.$itemId, $data );
		PerformanceProfiler::stopProfile( 'LocalCache - '.$bucketId, 4 );
		return $wroteBytes !== false;
	}

	/**
	 * Write data in the local cache.
	 *
	 * @param string $fullPath Full file path to be used to write the data.
	 * @param string $data The data to write.
	 * @return bool|int Number of bytes written, or FALSE on failure.
	 */
	private function writeDataInFile( string $fullPath, string $data )
	{
		$wroteBytes = false;
		if( $this->createFolderIfNotExists( dirname( $fullPath ) ) ) {
			$wroteBytes = file_put_contents( $fullPath, $data );
			if( $wroteBytes !== false ) {
				chmod( $fullPath, self::FILE_ACCESS_MODE );
			}
		}
		return $wroteBytes;
	}

	/**
	 * Read data from a bucket item in the local cache.
	 *
	 * @param string $bucketId
	 * @param string $itemId
	 * @return bool|string The data read, or FALSE when item was not present in cache.
	 */
	public function readBucketItemData( string $bucketId, string $itemId )
	{
		if( !$this->validateBucketId( $bucketId ) || !$this->validateItemId( $itemId ) ) {
			return false;
		}
		$bucketPath = $this->composeBucketPath( $bucketId );
		if( $bucketPath === false ){
			return false;
		}
		PerformanceProfiler::startProfile( 'LocalCache - '.$bucketId, 4 );
		$readData = $this->readDataFromFile( $bucketPath.'/'.$itemId );
		PerformanceProfiler::stopProfile( 'LocalCache - '.$bucketId, 4 );
		return $readData;
	}

	/**
	 * Read data from the local cache.
	 *
	 * @param string $file Full file path to be used to read the data.
	 * @return bool|string The data read, or FALSE on failure.
	 */
	private function readDataFromFile( string $file )
	{
		// Note that file_exists() requires disk access and file_get_contents() causes a warning when the file does not exists.
		// To be as fast as possible, the below skips file_exists() and suppresses the warning with the @ prefix.
		return @file_get_contents( $file );
	}

	/**
	 * Reset all cached items in a bucket.
	 *
	 * By simply creating a new bucket version, items in the old bucket version are no longer used.
	 * Old buckets that are left abandoned are automatically cleaned by server jobs during nightly hours.
	 *
	 * @param string $bucketId
	 */
	public function resetBucket( string $bucketId )
	{
		if( $this->validateBucketId( $bucketId ) ) {
			BizSession::forceCreateBucketVersionInLocalCache( $bucketId );
		}
	}

	/**
	 * Removes all cached files that are outdated (older than two days).
	 *
	 * The reason to take two days is to avoid file access problems that e.g. may occur when a production process has
	 * started just before 24:00h and continues the next day meanwhile this cleaning process has started just after 24:00h.
	 *
	 * @param integer Current time (Unix Epoch notation)
	 */
	private function cleanOutdatedCacheFolders( $timeStamp )
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$entPath = $this->getEnterpriseSystemPath();
		$entDir = opendir( $entPath );
		if( $entDir ) {
			LogHandler::Log( 'LocalCache', 'DEBUG', 'Auto cleaning started.' );
			$twoDaysInSeconds = 2 * 24 * 3600;
			while( ( $itemName = readdir( $entDir ) ) !== false ) {
				if( $itemName == '.' || $itemName == '..' ) {
					// skip parent directory and current directory
				} else if( is_dir( $entPath.'/'.$itemName ) ) {
					$dirDateTime = DateTime::createFromFormat('Y-m-d', $itemName );
					if( $dirDateTime !== false ) {
						if( ( $timeStamp - $dirDateTime->getTimestamp() ) > $twoDaysInSeconds ) {
							LogHandler::Log( 'LocalCache', 'DEBUG', 'Auto cleaning folder '.$entPath.'/'.$itemName );
							FolderUtils::cleanDirRecursive( $entPath.'/'.$itemName );
						}
					}
				}
			}
			closedir( $entDir );
		}
	}

	/**
	 * Return the full folder path of a bucket in the local cache.
	 *
	 * When the bucket does not exist yet, it gets registered in the DB.
	 * When the cache base folder does not exist yet, it gets created.
	 *
	 * @param string $bucketId
	 * @return string|bool The full folder path. This folder may or may not exist. FALSE when the cache base folder could not be created.
	 */
	private function composeBucketPath( string $bucketId )
	{
		$basePath = $this->ensureBasePathExists();
		if( $basePath === false ) {
			return false;
		}
		$bucketVersion = $this->getBucketVersion( $bucketId );
		return $basePath.'/'.$bucketId.'/'.$bucketVersion;
	}

	/**
	 * Return a bucket version. When the bucket is not present in local cache, create it.
	 *
	 * @param string $bucketId
	 * @return string
	 */
	private function getBucketVersion( string $bucketId )
	{
		$buckets = BizSession::getLocalCacheBuckets();
		if( array_key_exists( $bucketId, $buckets ) ) {
			$bucketVersion = $buckets[ $bucketId ];
		} else {
			$bucketVersion = BizSession::getOrCreateBucketVersionInLocalCache( $bucketId );
		}
		return $bucketVersion;
	}

	/**
	 * Validate the given bucket item id. See validateSafeAlphaIdentifier() for validation rules.
	 *
	 * @param string $itemId
	 * @return bool Whether or not valid.
	 */
	private function validateItemId( string $itemId ) : bool
	{
		return $this->validateSafeAlphanumericIdentifier( $itemId );
	}

	/**
	 * Validate the given bucket id. See validateSafeAlphaIdentifier() for validation rules.
	 *
	 * @param string $bucketId
	 * @return bool Whether or not valid.
	 */
	private function validateBucketId( string $bucketId ) : bool
	{
		return $this->validateSafeAlphanumericIdentifier( $bucketId );
	}

	/**
	 * Check whether the given identifier is valid.
	 *
	 * A valid identifier:
	 * - is alphanumeric or contains dashes(-) or underscores (_)
	 * - contains at least one character, but no more than 255 characters
	 *
	 * @param string $identifier
	 * @return bool Whether or not valid.
	 */
	private function validateSafeAlphanumericIdentifier( string $identifier ) : bool
	{
		$allowedSymbols = array( '-', '_' );
		$isValid = $identifier && strlen( $identifier ) <= 255 &&
			ctype_alnum( str_replace( $allowedSymbols, '', $identifier ) );
		if( !$isValid ) {
			$message = 'Bad identifier "'.$identifier.'" provided.';
			LogHandler::Log( __METHOD__, 'ERROR', $message );
		}
		return $isValid;
	}

	/**
	 * Return the daily local cache base folder of this application server version.
	 *
	 * When the cache base folder does not exist yet, it gets created.
	 * When the new date timestamp folder does not exist yet, it gets created and old folders are auto cleaned.
	 *
	 * @return string|bool Full file path of the folder. FALSE when folder creation failed.
	 */
	private function ensureBasePathExists(): string
	{
		// Note that the date timestamp needs to be consistent throughout the current session to keep using the same
		// folder when this session happen to pass midnight (24:00h). In other words, $dateStamp has to be static.
		static $timeStamp;
		if( !$timeStamp ) {
			$timeStamp = time();
		}
		$dateStamp = date( 'Y-m-d', $timeStamp );

		// Trigger the auto cleaning procedure of the local cache when we are about to create a date timestamp folder.
		$dateStampDir = $this->getEnterpriseSystemPath().'/'.$dateStamp;
		if( !is_dir( $dateStampDir ) ) {
			$this->cleanOutdatedCacheFolders( $timeStamp );
		}

		// Compose the base path and create when not exists.
		$esVersion = str_replace( array(' ', '.'), '_', SERVERVERSION );
		$cacheDir = $dateStampDir.'/'.$esVersion;
		if( !$this->createFolderIfNotExists( $cacheDir ) ) {
			return false;
		}
		return $cacheDir;
	}

	/**
	 * Return the local cache folder of this Enterprise Server installation.
	 *
	 * @return string Full file path of the folder.
	 */
	private function getEnterpriseSystemPath(): string
	{
		return $this->getRootPath().'/'.BizSession::getEnterpriseSystemId();
	}

	/**
	 * Return the local cache root folder of the application server.
	 *
	 * @return string Full file path of the folder.
	 */
	private function getRootPath(): string
	{
		$tmpDir = rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR );
		return $tmpDir.'/ww_ent_server_local_cache';
	}

	/**
	 * Check whether the given folder exists and create the full path when it doesn't.
	 *
	 * @param string $folder
	 * @return bool Whether or not the folder could be created or already exists.
	 */
	private function createFolderIfNotExists( string $folder ) : bool
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$createdOrExists = FolderUtils::ensureDirExists( $folder, self::DIRECTORY_ACCESS_MODE );
		if( !$createdOrExists ) {
			$message = 'Failed to create folder "'.$folder.'" in local application cache.';
			LogHandler::Log( __METHOD__, 'ERROR', $message );
		}
		return $createdOrExists;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// The below enforce this class can only be used as a singleton.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/** @var WW_BizClasses_LocalCache The instance of the singleton. */
	private static $instance;

	/**
	 * Return the singleton instance of this class.
	 *
	 * @return WW_BizClasses_LocalCache
	 */
	public static function getInstance()
	{
		if( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// Block callers of this class from creating instances through new, clone or unserialize operations.
	protected function __construct() {}
	private function __clone() {}
	private function __wakeup() {}
}
