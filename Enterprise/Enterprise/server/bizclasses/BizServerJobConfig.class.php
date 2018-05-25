<?php
/**
 * @since       v8.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Manages the server job configurations at DB and the environment* / installation.
 *
 * Configurations are introduced by the core and by server plug-ins.
 * After that, they can be tweaked / customized by system admins. Therefore, this
 * class is a two-fold: One handles the DB, and the other handles the environment.
 *
 * (*) The 'environment' stands for the built-in job handlers of core server -plus- the
 *     job handlers of the server plug-ins.
 *
 * IMPORTANT: Note that job types are unique per server type (not system wide).
 */

require_once BASEDIR.'/server/dbclasses/DBServerJobConfig.class.php';
require_once BASEDIR.'/server/dataclasses/ServerJobConfig.class.php';
 
class BizServerJobConfig
{
	private $dbJobConfig = null; // DB helper (DBServerJobConfig)

	private static $builtinHandlers = array(
		// 'AsyncImagePreview' => true, 
		// L> COMMENTED OUT: Server Jobs should not be used for workflow production in Ent 8.0
		// 'UpdateParentModifierAndModified' => true,
		'TransferServerCleanUp' => true, 
		'AutoPurgeTrashCan' => true,
		'EnterpriseEvent' => true,
		'AutoCleanServerJobs' => true,
		'AutoCleanServiceLogs' => true,
	);

	public function __construct()
	{
		$this->dbJobConfig = new DBServerJobConfig();
	}

	// ------------------------------------------------------------------------
	// Manage server job configs at >>>DATABASE<<<
	// ------------------------------------------------------------------------
	
	/**
	 * Returns list of configured server jobs.
	 * The returned configurations are organized in two-dimensional array.
	 * The first index is the server name and the second index is the job type.
	 * Note: Call getJobConfigsFromEnvironment() to get configs from the installation instead.
	 *
	 * @return array of ServerJobConfig
	 */
	public function listJobConfigs()
	{
		$retConfigs = array();
		$jobConfigs = $this->dbJobConfig->listJobConfigs();
		foreach( $jobConfigs as $jobConfig ) {
			$this->enrichJobConfig( $jobConfig );
			$retConfigs[$jobConfig->ServerType][$jobConfig->JobType] = $jobConfig;
		}
		return $retConfigs;
	}

	/**
	 * Retrieves one configured server jobs from DB.
	 *
	 * @param integer $jobConfigId
	 * @return ServerJobConfig
	 */
	public function getJobConfig( $jobConfigId )
	{
		$jobConfig = $this->dbJobConfig->getJobConfig( $jobConfigId );
		$this->enrichJobConfig( $jobConfig );
		return $jobConfig;
	}

	/**
	 * Removes one configured server job from DB.
	 *
	 * @param integer $jobConfigId
	 * @throws BizException on DB error
	 */
	public function deleteJobConfig( $jobConfigId )
	{
		$retVal = $this->dbJobConfig->deleteJobConfig( $jobConfigId );
		if( DBBase::hasError() || is_null($retVal) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBBase::getError() );
		}
	}

	/**
	 * Updates one configured server job at DB.
	 * The given $jobConfig gets update with lastest info from DB.
	 *
	 * @param ServerJobConfig $jobConfig
	 * @throws BizException on DB error
	 */
	public function updateJobConfig( ServerJobConfig & $jobConfig )
	{
		$this->validateJobConfig( $jobConfig );
		$original = $this->dbJobConfig->findJobConfig( $jobConfig->JobType, $jobConfig->ServerType );
		if( $original ) {
			$jobConfig->Id = $original->Id;
			$this->dbJobConfig->updateJobConfig( $jobConfig );
		} else {
			$this->dbJobConfig->createJobConfig( $jobConfig );
		}
		if( DBBase::hasError() || is_null($jobConfig) ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBBase::getError() );
		}
		$jobConfig = $this->getJobConfig( $jobConfig->Id );
	}

	/**
	 * Name based search for one server job config (object).
	 *
	 * @param string $jobType
	 * @param string $serverType
	 * @return ServerJobConfig Returns NULL when not found.
	 */
	public function findJobConfig( $jobType, $serverType )
	{
		return $this->dbJobConfig->findJobConfig( $jobType, $serverType );
	}

	/**
	 * Returns a new server job configuration with all properties set to null.
	 * Note the returned object is NOT created into DB yet! Call updateJobConfig() to do that.
	 *
	 * @return ServerJobConfig
	 */
	public function newJobConfig()
	{
		$jobConfig = new ServerJobConfig();
		foreach( array_keys( get_object_vars( $jobConfig ) ) as $prop ) {
			$jobConfig->$prop = null;
		}
		return $jobConfig;
	}
	
	/**
	 * Completes the given server job configuration with DB info. Only properties that are 
	 * null are updated. For new records (Id=0), no props are taken from DB.
	 * Also the properties are enriched with run time checked info. See enrichJobConfig().
	 *
	 * @param ServerJobConfig $jobConfig
	 */
	public function completeJobConfig( ServerJobConfig $jobConfig )
	{
		if( $jobConfig->Id ) {
			$dbConfig = $this->getJobConfig( $jobConfig->Id );
			foreach( array_keys( get_object_vars( $jobConfig ) ) as $prop ) {
				if( is_null( $jobConfig->$prop ) ) {
					$jobConfig->$prop = $dbConfig->$prop;
				}
			}
		}
		$this->enrichJobConfig( $jobConfig );
	}

	/**
	 * Enriches the given server job configuration with runtime checked info.
	 *
	 * @param ServerJobConfig $jobConfig
	 */
	private function enrichJobConfig( ServerJobConfig $jobConfig )
	{
		// TODO: Add localized job kind, such as 'normal' or 'recurring'
	}

	/**
	 * Validates and repairs the given server job config.
	 *
	 * @param ServerJobConfig $jobConfig
	 * @throws BizException Throws BizException when the validation fails.
	 */
	private function validateJobConfig( ServerJobConfig $jobConfig )
	{
		$jobConfig->JobType = trim($jobConfig->JobType);
		if( !$jobConfig->JobType ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Server Job configuration has empty JobType.' );
		}
		$jobConfig->ServerType = trim($jobConfig->ServerType);
		if( !$jobConfig->ServerType ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Server Job configuration has empty ServerType.' );
		}
	}

	// ------------------------------------------------------------------------
	// Determine server job configs at >>>RUNTIME<<<
	// ------------------------------------------------------------------------

	/**
 	 * TODO: How to check/avoid for server plugins trying to create builtin jobs?
 	 *
	 * @param string $jobType
	 * @return boolean Whether or not the job type is built-in.
 	 */
	static function isBuiltInJobType( $jobType )
	{
		return isset( self::$builtinHandlers[$jobType] );
	}

	/**
	 * Returns a list of Server Job types that are handled by the given server.
	 * This can be job types handled by the core server and/or the ones handled by server plug-ins.
	 *
	 * @param string $serverType
	 * @throws BizException
	 * @return array Job types are returned through array keys.
	 */
	public function getServerJobTypes( $serverType )
	{
		require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
		switch( $serverType ) {
			case BizServer::SERVERTYPE_ENTERPRISE:
				$types = self::$builtinHandlers;
				require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
				$connectors = BizServerPlugin::searchConnectors( 'ServerJob', null ); // null = any job type
				if( $connectors ) foreach( $connectors as $connector ) {
					$jobType = BizServerPlugin::getPluginUniqueNameForConnector( get_class($connector) );
					$types[$jobType] = true;
				}
			break;
			default:
				throw new BizException( 'ERR_ARGUMENT', 'Server', 'Unsupported server type "'.$serverType.'" requested.' );
		}
		return $types;
	}

	/**
	 * Determines if there are server job configurations that have become obsoleted due to changes made
	 * by system admin users to the enviroment* / installation. This function returns the configs 
	 * that are installed at DB, but no longer found at environment. (*) See module header.
	 *
	 * This function does -not- check the config properties, since those can be changed by system users.
	 * Instead, it checks if both collections have the same entries (server types / job types).
	 *
	 * All server job configs are organized in two-dimensional arrays; The first index is the server type
	 * and the second index is the job type. The values are ServerJobConfig objects.
	 *
	 * @param array $dbConfigs  Database job configs to be taken from listJobConfigs().
	 * @param array $envConfigs Database job configs to be taken from getJobConfigsFromEnvironment().
	 * @return array Obsoleted job configs to remove
	 */
	public function getObsoletedJobConfigs( array $dbConfigs, array $envConfigs )
	{
		// Get configs found at DB, but no longer at installation
		$obsConfigs = array_diff_key( $dbConfigs, $envConfigs ); 
		foreach( array_keys($dbConfigs) as $serverType ) {
			if( !isset($obsConfigs[$serverType]) ) {
				$diff = array_diff_key( $dbConfigs[$serverType], $envConfigs[$serverType] );
				if( count($diff) > 0 ) foreach( $diff as $jobType => $dbConfig ) {
					$obsConfigs[$serverType][$jobType] = $dbConfig;
				}
			}
		}
		return $obsConfigs;
	}
	
	/**
	 * Determines if there server job configurations that to be newly introduced due to changes made
	 * by system admin users to the enviroment* / installation. This function returns the configs 
	 * that are found at environment, but no yet registered at the database. (*) See module header.
	 *
	 * This function does -not- check the config properties, since those can be changed by system users.
	 * Instead, it checks if both collections have the same entries (server types / job types).
	 *
	 * All server job configs are organized in two-dimensional arrays; The first index is the server type
	 * and the second index is the job type. The values are ServerJobConfig objects.
	 *
	 * @param array $dbConfigs  Database job configs to be taken from listJobConfigs().
	 * @param array $envConfigs Database job configs to be taken from getJobConfigsFromEnvironment().
	 * @return array New job configs to register
	 */
	public function getIntroducedJobConfigs( array $dbConfigs, array $envConfigs )
	{
		// Get configs found at installation, but not yet imported into DB
		$newConfigs = array_diff_key( $envConfigs, $dbConfigs ); 
		foreach( array_keys($envConfigs) as $serverType ) {
			if( !isset($newConfigs[$serverType]) ) {
				$diff = array_diff_key( $envConfigs[$serverType], $dbConfigs[$serverType] );
				if( count($diff) > 0 ) foreach( $diff as $jobType => $envConfig ) {
					$newConfigs[$serverType][$jobType] = $envConfig;
				}
			}
		}
		return $newConfigs;
	}
	
	/**
	 * Retrieves job configs from the the environment* / installation (*see module header).
	 * The returned configurations are organized in two-dimensional array.
	 * The first index is the server name and the second index is the job type.
	 * Note: Call listJobConfigs() to get configs from the database instead.
	 *
	 * @throws BizException
	 * @return array of ServerJobConfig
	 */
	public function getJobConfigsFromEnvironment()
	{
		require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
		$jobConfigs = array();
		
		// Collect job configs handled by core server
		foreach( array_keys(self::$builtinHandlers) as $jobType ) {

			// Call core job handler to update config with defaults
			$jobConfig = $this->newJobConfig();
			$jobConfig->ServerType = BizServer::SERVERTYPE_ENTERPRISE;
			$jobConfig->JobType = $jobType;
			$jobConfig->Active = true; // enable, but allow handler to switch off by default
			switch( $jobType ) {
				case 'AsyncImagePreview':
					require_once BASEDIR.'/server/bizclasses/BizMetaDataPreview.class.php';
					$bizMetaPreview = new BizMetaDataPreview();
					$bizMetaPreview->getJobConfig( $jobConfig );
					break;
				case 'UpdateParentModifierAndModified':
					require_once BASEDIR.'/server/bizclasses/BizObjectJob.class.php';
					$updateParentJob = new WW_BizClasses_ObjectJob();
					$updateParentJob->getJobConfig( $jobConfig );
					break;
				case 'TransferServerCleanUp':
					require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
					$bizTransferServer = new BizTransferServer();
					$bizTransferServer->getJobConfig( $jobConfig );
					break;
				case 'AutoPurgeTrashCan':
					require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
					$bizAutoPurge = new BizAutoPurge();
					$bizAutoPurge->getJobConfig( $jobConfig );
					break;
				case 'EnterpriseEvent':
					require_once BASEDIR.'/server/bizclasses/BizEnterpriseEvent.class.php';
					$bizEntEvent = new BizEnterpriseEvent();
					$bizEntEvent->getJobConfig( $jobConfig );
					break;
				case 'AutoCleanServerJobs':
					require_once BASEDIR.'/server/bizclasses/BizServerJobCleanup.class.php';
					$bizServerJobCleanup = new BizServerJobCleanup();
					$bizServerJobCleanup->getJobConfig( $jobConfig );
					break;
				case 'AutoCleanServiceLogs':
					require_once BASEDIR.'/server/bizclasses/BizServiceLogsCleanup.class.php';
					$bizServiceLogsCleanup = new BizServiceLogsCleanup();
					$bizServiceLogsCleanup->getJobConfig( $jobConfig );
					break;
				default:
					throw new BizException( 'ERR_ARGUMENT', 'Server', 
						'No core server job handler class found for Server Job type "'.$jobType.'".' );
			}
			
			// Validate config
			$this->validateJobConfig( $jobConfig );
			if( isset($jobConfigs[$jobConfig->ServerType][$jobConfig->JobType]) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 
					'Duplicate Server Job handlers found for server type "'.$jobConfig->ServerType.'" and job type "'.$jobConfig->JobType.'".' );
			}
			$jobConfigs[$jobConfig->ServerType][$jobConfig->JobType] = $jobConfig;
		}
		
		// Collect job configs handled by server plug-ins
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connectors = BizServerPlugin::searchConnectors( 'ServerJob', null ); // null = all job types
		if( $connectors ) foreach( $connectors as $connector ) {
			
			// Call connector to update config with defaults
			$internalPluginName = BizServerPlugin::getPluginUniqueNameForConnector( get_class($connector) );
			$jobConfig = $this->newJobConfig();
			$jobConfig->ServerType = BizServer::SERVERTYPE_ENTERPRISE;
			$jobConfig->JobType = $internalPluginName;
			$jobConfig->Active = true; // enable, but allow handler to switch off by default
			BizServerPlugin::runConnector( $connector, 'getJobConfig', array($jobConfig) );
			
			// Silently 'repair' the job type...
			// As long as we do not support multiple job types per ServerJob connector, the core server 
			// assumes (many places in the code) that the job type is equal to the internal plugin name.
			$jobConfig->JobType = $internalPluginName;

			// Validate config
			$this->validateJobConfig( $jobConfig );
			if( isset($jobConfigs[$jobConfig->ServerType][$jobConfig->JobType]) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 
					'Duplicate Server Job handlers found for server type "'.$jobConfig->ServerType.'" and job type "'.$jobConfig->JobType.'".' );
			}
			$jobConfigs[$jobConfig->ServerType][$jobConfig->JobType] = $jobConfig;
		}
		
		return $jobConfigs;
	}
}
