<?php
/**
 * ServerJobs TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_ServerJobs_TestCase extends TestCase
{
	public function getDisplayName() { return 'Server Jobs'; }
	public function getTestGoals()   { return 'Checks if the Server Jobs configurations are registered at DB. '; }
	public function getTestMethods() { return 'Retrieves configurations from ServerJob the core server and its server plug-in connectors. '.
												'Those are compared against the configurations registered at the database '.
												'and reports if there are new ones to register or obsoleted ones to remove.'.
												'Also the co-workers are checked if they are responsive.'; }
    public function getPrio()        { return 21; }
	
	final public function runTest()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
		$bizJobConfig = new BizServerJobConfig();

		$dbConfigs = $bizJobConfig->listJobConfigs();
		$envConfigs = $bizJobConfig->getJobConfigsFromEnvironment();

		if( count($bizJobConfig->getIntroducedJobConfigs( $dbConfigs, $envConfigs ) ) ) {
			$this->setResult( 'WARN', 'There are new Server Job configurations found at your installation.',
				'Click <a href="../../server/admin/serverjobconfigs.php'.'">here</a> to register those at the database.' );
		}

		if( count( $bizJobConfig->getObsoletedJobConfigs( $dbConfigs, $envConfigs ) ) ) {
			$this->setResult( 'ERROR', 'There are obsoleted Server Job configurations found at your installation.',
				'Click <a href="../../server/admin/serverjobconfigs.php'.'">here</a> to delete those from your the database.' );
		}

		$this->checkServerJobAssignments( $dbConfigs );

		$this->checkCoWorkers();

		$this->checkServerJobSettings( $dbConfigs );		
	}

	/**
	 * Checks for all the recurring job whether there is a user assigned to it.
	 * 
	 * @param array $dbConfigs Array of servers with its server job configs.
	 */
	private function checkServerJobSettings( $dbConfigs )
	{
		foreach ( $dbConfigs as /* $server => */$jobConfigs ) {
			foreach( $jobConfigs as $jobConfig ) {
				if( $jobConfig->Active == true && (( $jobConfig->UserConfigNeeded && !$jobConfig->UserId ) || // ServerJobConfig ( UserConfigNeeded ) tells if user is compulsory.
					( $jobConfig->Recurring && !$jobConfig->UserId ))) { // Nevertheless, when it is a recurring, regardless of UserConfigNeeded, it should always has a user assigned.
					$this->setResult( 'ERROR', 'Server Job "'.$jobConfig->JobType.'" has no user assigned to it. '.
							'Click <a href="../../server/admin/serverjobconfigs.php">here</a> to assign a user.');				
				}		
			}
		}		
	}

	/**
	 * Checks whether all the different server job types are assigned to a server.
	 *
	 * @param array $dbConfigs Array of servers with its server job configs.
	 */
	private function checkServerJobAssignments( $dbConfigs )
	{
		require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
		$bizServer = new BizServer();
		$servers = $bizServer->listServers();

		// Get a full list of all the assigned job types
		$assignedJobTypes = array();
		foreach ( $servers as $server ) {
			// If one server is supporting all the server jobs we can stop the check
			if ( $server->JobSupport == "A" ) {
				return;
			}

			foreach ( $server->JobTypes as $jobType => $value ) {
				if ( !isset( $assignedJobTypes[$jobType] ) ) {
					$assignedJobTypes[$jobType] = $value;
				}
			}
		}

		// Check if all the active db job configurations are set for at least one server
		require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerJobCleanup.class.php';
		$notAssigned = array();
		foreach ( $dbConfigs as $jobConfigs ) {
			foreach ( $jobConfigs as $name => $jobConfig ) {
                if ( $jobConfig->Active === true ) {
                    if ( !isset($assignedJobTypes[$name]) ) {
                        if ( $name == 'AutoPurgeTrashCan' && !BizAutoPurge::isAutoPurgeEnabled() ) {
                            continue;
                        } else if( $name == 'AutoCleanServerJobs' && !BizServerJobCleanup::isServerJobCleanupEnabled() ) {
	                        continue;
                        }
                        $notAssigned[] = $name;
                    }
                }
			}
		}

		// If there are some types not assigned to a server report it to the user
		if ( !empty( $notAssigned ) ) {
			if ( count( $notAssigned) == 1 ) {
				$this->setResult( 'ERROR', 'Server Job "' . implode(", ", $notAssigned) . '" is not assigned to any server.' );
			} else {
				$this->setResult( 'ERROR', 'Server Jobs "' . implode(", ", $notAssigned) . '" are not assigned to any server.' );
			}
		}
	}

	/**
	 * Check if all the defined co-workers are responsive
	 */
	private function checkCoWorkers()
	{
		require_once BASEDIR.'/server/bizclasses/BizServer.class.php';
		$bizServer = new BizServer();
		$coWorkerServers = $bizServer->listServers();

		require_once BASEDIR.'/server/utils/UrlUtils.php';
		if( $coWorkerServers ) foreach( $coWorkerServers as $coWorkerServer ) {
			$configTip = 'Please check the URL defined on the Server Maintenance page. Make sure that the Server can access the URL.';
			if( !empty( $coWorkerServer->URL ) ) {
				$url = $coWorkerServer->URL.'/jobindex.php?test=ping';
				// Check if the full url path is responsive
				if( !WW_Utils_UrlUtils::isResponsiveUrl( $url ) ) {
					$this->setResult( 'ERROR', 'Unable to connect to the "'.$coWorkerServer->URL.'" Server.', $configTip  );
				}
			} else {
				// The URL is empty so give a proper error messages
				$this->setResult( 'ERROR', 'Unable to connect to the "'.$coWorkerServer->Name.'" Server. No URL is available.', $configTip );
			}
		}
	}
}
