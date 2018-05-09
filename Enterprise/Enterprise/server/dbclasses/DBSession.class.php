<?php
/**
 * @package     Enterprise
 * @subpackage  DBClasses
 * @since       10.5.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * For the current user session, this class requests the DB to resolve ticket info, user info, the Enterprise System ID
 * and the local cache ids.
 *
 * Since the network access time to the DB is relatively expensive, all info is requested in just one SQL statement.
 * The results are cached in memory so it can be requested again within the same service without need for DB access.
 * In case the actor is not a user but a server job, the ticket info can not be resolved.
 */
class WW_DbClasses_Session extends DBBase
{
	/** @var array */
	private $sessionUserRow;

	/** @var array */
	private $sessionTicketRow;

	/** @var string */
	private $enterpriseSystemId;

	/** @var array */
	private $localCacheBuckets;

	/**
	 * Setup the memory cache with session info. See module header for details.
	 *
	 * @param string $ticket
	 * @param ServerJob $serverJob
	 * @return bool Whether or not initialized.
	 */
	private function init( $ticket, $serverJob )
	{
		$this->sessionUserRow = null;
		$this->sessionTicketRow = null;
		$this->enterpriseSystemId = null;
		$this->localCacheBuckets = null;

		if( $serverJob && $serverJob->TicketSeal == $ticket ) {
			$retVal = $this->initForServerJobUser( $serverJob->ActingUser );
		} else {
			$retVal = $this->initForUserTicket( $ticket );
		}
		if( !$retVal ) {
			if( $this->autoRepairConfigOptions() ) { // only retry when we have fixed the problem
				$retVal = $this->init( $ticket, $serverJob );
			}
		}
		return $retVal;
	}

	/**
	 * Setup the memory cache with session info for acting server job. See module header for details.
	 *
	 * @param string $user
	 * @return bool
	 */
	private function initForServerJobUser( $user )
	{
		$tables = array( 'c1' => 'config', 'c2' => 'config', 'u' => 'users' );
		$fields = array(
			'c1' => array( 'enterprise_system_id' => 'value' ),
			'c2' => array( 'local_cache_buckets' => 'value' ),
			'u' => array( '*' )
		);
		$where = 'c1.`name` = ? AND c2.`name` = ? AND u.`user` = ?';
		$params = array( 'enterprise_system_id', 'local_cache_buckets', strval( $user ) );
		$row = self::getRow( $tables, $where, $fields, $params );
		if( $row ) {
			$excludeFieldsFromUserRow = array( 'enterprise_system_id', 'local_cache_buckets' );
			$this->sessionUserRow = array_diff_key( $row, array_flip( $excludeFieldsFromUserRow ) );
			$this->sessionTicketRow = null;
			$this->enterpriseSystemId = $row['enterprise_system_id'];
			$this->localCacheBuckets = unserialize( $row['local_cache_buckets'] );
		}
		return (bool)$row;
	}

	/**
	 * Setup the memory cache with session info for acting user. See module header for details.
	 *
	 * @param string $ticket
	 * @return bool
	 */
	private function initForUserTicket( $ticket )
	{
		// Get values from multiple tables with the following SQL statement (in MySQL notation):
		//    SELECT t.`usr`, t.`appname`, t.`appversion`, t.`expire`, t.`masterticketid`,
		//       c1.`value` AS 'enterprise_system_id', c2.`value` AS 'local_cache_buckets', u.*
		//    FROM `smart_tickets` t, `smart_config` c1, `smart_config` c2, `smart_users` u
		//    WHERE t.`ticketid` = '...' AND c1.`name` = 'enterprise_system_id' AND c2.`name` = 'local_cache_buckets'
		//       AND t.`usr` = u.`user`

		$tables = array( 't' => 'tickets', 'c1' => 'config', 'c2' => 'config', 'u' => 'users' );
		$fields = array(
			't' => array( 'usr', 'appname', 'appversion', 'expire', 'masterticketid' ),
			'c1' => array( 'enterprise_system_id' => 'value' ),
			'c2' => array( 'local_cache_buckets' => 'value' ),
			'u' => array( '*' )
		);
		$where = 't.`ticketid` = ? AND c1.`name` = ? AND c2.`name` = ? AND t.`usr` = u.`user`';
		$params = array( strval( $ticket ), 'enterprise_system_id', 'local_cache_buckets' );
		$row = self::getRow( $tables, $where, $fields, $params );
		if( $row ) {
			$excludeFieldsFromUserRow = array_merge( $fields['t'], array( 'enterprise_system_id', 'local_cache_buckets' ) );
			$this->sessionUserRow = array_diff_key( $row, array_flip( $excludeFieldsFromUserRow ) );
			$this->sessionTicketRow = array_intersect_key( $row, array_flip( $fields['t'] ) );
			$this->enterpriseSystemId = $row['enterprise_system_id'];
			$this->localCacheBuckets = unserialize( $row['local_cache_buckets'] );
		}
		return (bool)$row;
	}

	/**
	 * In case one of the config options are not installed, the user can not login because the initialization
	 * functions defined above will fail since the options are invoked in the SQL (for performance reasons).
	 * This may happen during an Enterprise Server installation or upgrade and is blocking the admin to logon.
	 * So let's auto-repair this config option on-the-fly to break through this chicken-and-egg problem.
	 *
	 * @return bool TRUE when the options are installed by this function, or FALSE when installed before or when installation failed.
	 */
	private function autoRepairConfigOptions()
	{
		$autoRepaired = false;
		require_once BASEDIR .'/server/dbscripts/dbupgrades/LocalCacheBuckets.class.php';
		require_once BASEDIR .'/server/dbscripts/dbupgrades/EnterpriseSystemId.class.php';
		$dbUpgrades = array(
			new WW_DbScripts_DbUpgrades_LocalCacheBuckets(), // installer for the 'local_cache_buckets' config option
			new WW_DbScripts_DbUpgrades_EnterpriseSystemId() // installer for the 'enterprise_system_id' config option
		);
		foreach( $dbUpgrades as $dbUpgrade ) {
			if( !$dbUpgrade->isUpdated() ) {
				if( $dbUpgrade->setUpdated() ) {
					$autoRepaired = true;
				}
			}
		}
		return $autoRepaired;
	}

	/**
	 * @return array DB record from smart_users table containing all fields.
	 */
	public function getSessionUserRow()
	{
		return $this->sessionUserRow;
	}

	/**
	 * Return the ticket info for regular web services. For server jobs, return NULL.
	 *
	 * @return array|null DB record from the smart_tickets table containing fields: 'usr', 'appname', 'appversion', 'expire', 'masterticketid'
	 */
	public function getSessionTicketRow()
	{
		return $this->sessionTicketRow;
	}

	/**
	 * Provide the Enterprise System ID
	 *
	 * This ID is needed to access the local cache of this application server.
	 * See WW_BizClasses_LocalCache class for more information.
	 *
	 * @return string Enterprise System ID (GUID)
	 */
	public function getEnterpriseSystemId()
	{
		return $this->enterpriseSystemId;
	}

	/**
	 * Provide the local cache ids.
	 *
	 * These ids are needed to access the local cache of this application server.
	 * See WW_BizClasses_LocalCache class for more information.
	 *
	 * @return array Map of local cache ids (logical names) and versions (GUIDs)
	 */
	public function getLocalCacheBuckets()
	{
		return $this->localCacheBuckets;
	}

	/**
	 * Create and return a new bucket version (regardless whether the bucket already exists in the local cache).
	 *
	 * @param string $bucketId
	 * @return string The bucket version (GUID).
	 * @throws BizException
	 */
	public function forceCreateBucketVersionInLocalCache( string $bucketId )
	{
		$this->createBucketVersionInLocalCacheByCondition( $bucketId, true );
		return $this->localCacheBuckets[ $bucketId ];
	}

	/**
	 * Return a bucket version. When the bucket is not present in the local cache yet, create it.
	 *
	 * @param string $bucketId
	 * @return string The bucket version (GUID).
	 * @throws BizException
	 */
	public function getOrCreateBucketVersionInLocalCache( string $bucketId )
	{
		$this->createBucketVersionInLocalCacheByCondition( $bucketId, false );
		return $this->localCacheBuckets[ $bucketId ];
	}

	/**
	 * Create a new bucket version when needed.
	 *
	 * Note that creations are guarded by a semaphore. This is needed because there could be process A and B running
	 * in parallel and e.g. both decide to introduce new bucket at the very same time. So process A creates bucket X
	 * and process B creates bucket Y. Without semaphore, the last process performing the UPDATE operation in the DB
	 * would overwrite the UPDATE of the first process. To avoid this, within the semaphore the 'local_cache_buckets'
	 * option is read from DB, updated in memory and written back into the DB. Only then the semaphore is released.
	 *
	 * @param string $bucketId
	 * @param bool $forceNewVersion TRUE to always create a new bucket version, FALSE to only create a version when the bucket does not exist yet.
	 * @throws BizException
	 */
	private function createBucketVersionInLocalCacheByCondition( string $bucketId, bool $forceNewVersion )
	{
		if( $forceNewVersion || !array_key_exists( $bucketId, $this->localCacheBuckets ) ) {

			require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
			$bizSemaphore = new BizSemaphore();
			$bizSemaphore->setAttempts( array_fill( 0, 20, 250 ) ); // 20 attempts x 250ms wait = 5s max total wait
			$bizSemaphore->setLifeTime( 3 ); // 3 seconds to live (to execute the below)
			$semaphoreId = $bizSemaphore->createSemaphore( 'DBSession_CreateBucketInLocalCache' );
			if( !$semaphoreId ) {
				$details = 'Failed to create bucket in local cache, because there is another process blocking the semaphore.';
				throw new BizException( 'ERR_ERROR', 'Server', $details );
			}

			require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
			$this->localCacheBuckets = unserialize( DBConfig::getValue( 'local_cache_buckets' ) );
			if( $forceNewVersion || !array_key_exists( $bucketId, $this->localCacheBuckets ) ) {
				require_once BASEDIR.'/server/utils/NumberUtils.class.php';
				$this->localCacheBuckets[ $bucketId ] = NumberUtils::createGUID();
				DBConfig::storeValue( 'local_cache_buckets', serialize( $this->localCacheBuckets ) );
			}

			BizSemaphore::releaseSemaphore( $semaphoreId );
		}
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// The below enforce this class can only be used as a singleton.
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/** @var WW_DbClasses_Session The instance of the singleton. */
	private static $instance;

	/** @var string The user session ticket this singleton is created for. */
	private static $ticket;

	/**
	 * Return the singleton instance of this class.
	 *
	 * @param string $ticket
	 * @param ServerJob $serverJob
	 * @return WW_DbClasses_Session
	 */
	public static function getInstance( $ticket, $serverJob )
	{
		if( null === self::$instance ) {
			self::$instance = new self();
		}
		if( $ticket ) {
			if( self::$ticket !== $ticket ) {
				if( self::$instance->init( $ticket, $serverJob ) ) {
					self::$ticket = $ticket;
				}
			}
		}
		return self::$instance;
	}

	// Block callers of this class from creating instances through new, clone or unserialize operations.
	protected function __construct() {}
	private function __clone() {}
	private function __wakeup() {}
}