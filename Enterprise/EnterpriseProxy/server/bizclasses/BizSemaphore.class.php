<?php
/**
 * @package 	EnterpriseProxy
 * @subpackage 	BizClasses
 * @since 		v9.6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizSemaphore
{
	/** @var resource[] */
	static $semaHandles;

	/** @var  int */
	private static $lifeTime;

	/** @var  array */
	private $attempts;

	public function __construct()
	{
		// Default 18 attempts to create semaphore. Roughly 6 seconds wait in total.
		// Numbers are represented in milliseconds.
		$this->attempts = array( 5, 10, 15, 25, 50, 125, 250, 500, // First second to aggressively get in.
				500, 500, 500, 500, 500, 500, 500, 500, 500, 500 ); // Keep on trying for another 5 seconds (less aggressive).

		// Default 120 seconds life time of a semaphore.
		self::$lifeTime = 120;
	}

	/**
	 * Customize the number of attempts to enter the semaphore. Each attempt represents an amount
	 * of milliseconds to wait before retrying to enter again. When all attempts are done, it gives up.
	 * By default it tries 18 times (5, 10, 15, 25, 50, 125, 250, 500, 00, 500, 500, 500, 500, 500, 500,
	 * 500, 500, 500 milliseconds) with a total wait of roughly 6 seconds.
	 *
	 * @param array List of attempts (milliseconds).
	 */
	public function setAttempts( $attempts )
	{
		$this->attempts = $attempts;
	}

	/**
	 * Customize the life time of the semaphore. Once expired, the door to the semaphore gets unlocked
	 * automatically and so other processes may enter. Once a process enters, it may reset the life
	 * time to indicates it is still alive and needs more time to complete the job. In other terms,
	 * the process needs to implement some kind of 'heart beat' when it could take longer than
	 * the life time. The default is 120 seconds. When a process needs more, it can either set the life
	 * time to higher value or implement a 'heart beat' by calling refreshSemaphore() at regular basis,
	 * which reset the timer.
	 *
	 * @param integer $lifeTime Time to live in seconds.
	 */
	public function setLifeTime( $lifeTime )
	{
		self::$lifeTime = $lifeTime;
	}

	/**
	 * Establishes a semaphore.
	 *
	 * @param string $semaName Unique Identifier of the semaphore which is the object id.
	 * @return bool Whether or not semaphore can be created. When true, call releaseSemaphore() after usage.
	 */
	public function createSemaphore( $semaName )
	{
		LogHandler::Log( 'Semaphore', 'DEBUG', 'Creating semaphore: ' . $semaName );

		// Avoid time outs in case the semaphore directory is not writable at once
		// When logging on, an error message should occur after 40 seconds.
		set_time_limit( 0 );

		$result = false;
		$lock = false;
		foreach( $this->attempts as $waitTime ) {
			$lock  = self::lock( $semaName );
			if( $lock ) {
				$result = true;
				LogHandler::Log( 'Semaphore', 'DEBUG', 'Created semaphore: ' . $semaName );
				break; // we are in!
			}
			$sleep = ($waitTime + rand(1,5));
			LogHandler::Log( 'Semaphore', 'DEBUG',
				'Waiting '.$sleep.'ms for semaphore "'.$semaName.'" to get released by another process...' );
			usleep( $sleep * 1000 ); // microseconds
		}

		if( !$lock ) {
			// Just a warning, not fatal since the process still can go on. Only attachments cannot be saved into the
			// cache for optimized result.
			LogHandler::Log( 'Semaphore', 'WARN', 'Failed creating semaphore: "' . $semaName );
		}
		return $result;
	}

	/**
	 * Releases a semaphore that was obtained before through createSemaphore().
	 *
	 * @param string $semaName Identifier of the semaphore (use the object id).
	 * @param int $maxAttempts Maximum attempts to release the semaphore when the attempts are not successful.
	 */
	public function releaseSemaphore( $semaName, $maxAttempts=20 )
	{
		LogHandler::Log( 'Semaphore', 'DEBUG', 'Releasing semaphore: ' . $semaName );
		$n = 0;
		do {
			$unlocked  = self::unlock( $semaName );
			if ( !$unlocked ) {
				if ( $maxAttempts > 1 ) {
					usleep( 5000 ); // 5ms wait.
				}
				$n++;
			}
		} while( !$unlocked && ( $n < $maxAttempts ));

		if ( $n >= $maxAttempts ) {
			LogHandler::Log( 'Semaphore', 'ERROR', 'Failed releasing semaphore: "' . $semaName . '" after ' .
				$maxAttempts . ' attempts.');
		}
	}

	/**
	 * To lock the semaphore file.
	 *
	 * Lock the semaphore file by writing the current timestamp into the semaphore file.
	 *
	 * If the semaphore file already consists of a timestamp, then the timestamp is
	 * checked whether it is already expired:
	 *      L> Already expired: The semaphore file will be overwritten with a new current timestamp and the lock is granted.
	 *      L> Not yet expired: The semaphore file cannot be overwritten, therefore the lock cannot be granted.
	 *
	 * If the semaphore file does not exists yet:
	 *      L> The semaphore file will be written with the current timestamp and the lock is granted.
	 *
	 * @param string $semaName
	 * @return bool
	 */
	private static function lock( $semaName )
	{
		$locked = false;
		$semaFolder = PROXYSERVER_CACHE_PATH . '/temp/Sema';
		$fileName = $semaFolder . '/'. $semaName . '.sema';

		if( file_exists( $fileName )) {
			$fh = fopen( $fileName, 'r+b' );
			if( $fh ) {
				if( flock( $fh, LOCK_EX ) ) {
					$stamp = fread( $fh, 1024 );
					if( $stamp ) {
						$expirationTime = time() - self::$lifeTime;
						if( $stamp < $expirationTime ) { // Expired.
							$stamp = 0; // Already expired, so resetting it.
						}
					}
					if( !$stamp ) {
						$stamp = time();
						ftruncate( $fh, 0 );
						fwrite( $fh, $stamp );
						fflush( $fh );
						$locked = true;
					}
					flock( $fh, LOCK_UN );
				}
				fclose( $fh );
			}
		} else {
			require_once BASEDIR .'/server/utils/FolderUtils.class.php';
			if( !file_exists( $semaFolder )) {
				FolderUtils::mkFullDir( $semaFolder );
			}
			$fh = fopen( $fileName, 'w+b' );
			if( $fh ) {
				if( flock( $fh, LOCK_EX )) {
					$stamp = time();
					ftruncate( $fh, 0 );
					fwrite( $fh, $stamp );
					fflush( $fh );
					flock( $fh, LOCK_UN );
					$locked = true;
				}
				fclose( $fh );
			}
		}
		return $locked;
	}

	/**
	 * To unlock the semaphore file.
	 *
	 * Unlock the semaphore file by clearing the timestamp ((setting to empty string)
	 * in the semaphore file.
	 *
	 * @param string $semaName
	 * @return bool
	 */
	private static function unlock( $semaName )
	{
		$unlocked = false;
		$semaFolder = PROXYSERVER_CACHE_PATH . '/temp/Sema';
		$fileName = $semaFolder . '/'. $semaName . '.sema';

		if( file_exists( $fileName )) { // Only when file exists, we can use 'r+b'.
			// Reason for not using 'w+b' is because during the releasing of semaphore (unlocking), we do not want
			// to create a new file when the file never exists. When file doesn't exists, we assume it was never
			// locked or it was already released (releaseSemaphore() is called twice for the same semaphore), therefore
			// nothing to do for unlock().
			$fh = fopen( $fileName, 'r+b' );
			if( $fh ) {
				if( flock( $fh, LOCK_EX ) ) {
					ftruncate( $fh, 0 ); // Clear the timestamp
					fflush( $fh );
					flock( $fh, LOCK_UN );
					$unlocked = true;
				}
				fclose( $fh );
			}
		} else {
			$unlocked = true; // Requested semaphore doesn't exists, therefore nothing to unlock.
		}
		return $unlocked;
	}
}
