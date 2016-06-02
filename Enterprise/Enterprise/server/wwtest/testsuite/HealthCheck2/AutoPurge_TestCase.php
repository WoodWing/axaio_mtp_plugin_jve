<?php
/**
 * AutoPurge TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_AutoPurge_TestCase extends TestCase
{
	private $autoPurge = null;
	public function getDisplayName() { return 'Auto Purge'; }
	public function getTestGoals()   { return 'Checks if the Auto Purge feature is enabled and if there are objects in the Trash Can that are too old (8 days + AutoPurge option on Brand Maintenance page).'; }
	public function getTestMethods() { return 'Checks if any server is assigned to handle the AutoPurgeTrashCan server job type, which indicates the feature is enabled. Also checks if objects in the Trash Can are older than 8 days + AutoPurge option on Brand Maintenance page and that are owned by Brands that have the Auto Purge option enabled.'; }
    public function getPrio()        { return 14; }
	
	
	final public function runTest()
	{
		// Check if AutoPurge registered in the system?	
		require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
		$bizJobConfig = new BizServerJobConfig();

		$dbConfigs = $bizJobConfig->listJobConfigs();
		$envConfigs = $bizJobConfig->getJobConfigsFromEnvironment();		
		$notRegisteredJobConfig = $bizJobConfig->getIntroducedJobConfigs( $dbConfigs, $envConfigs );
		
		if( count( $notRegisteredJobConfig ) ) {
			foreach( array_values( $notRegisteredJobConfig ) as $jobConfigs  ) {
				foreach( $jobConfigs as /*$jobType =>*/ $jobConfig ) {
					if( $jobConfig->JobType == 'AutoPurgeTrashCan' ) {
						$this->setResult( 'NOTINSTALLED', 'An AutoPurgeTrashCan Server Job configuration is found in your installation but it is ' .
											'not registered yet.',
											'Click <a href="../../server/admin/serverjobconfigs.php'.'">here</a> to register it in the database.' );
						return;	
					}
				}				
			}
		}
		require_once BASEDIR.'/server/bizclasses/BizAutoPurge.class.php';
		// check AutoPurge enabled?
		if( !BizAutoPurge::isAutoPurgeEnabled() ){	
			$help = 'To enable it, please assign at least one server to the AutoPurgeTrashCan Server Job <a href="../../server/admin/servers.php'.'">here</a>.';
			$this->setResult( 'NOTINSTALLED', 'AutoPurgeTrashCan is not enabled.', $help );
			return;
		}
		
		// Inform the user if there're deleted obejcts much older than the configured days.
		// Specified 8 here because assumed is that customers run Auto Purge batch daily or weekly.
		// Taking one day extra (7+1=8) to avoid any possible race conditions.
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
		$this->user = $suiteOpts['User'];
		$this->password = $suiteOpts['Password'];

		$response = $this->logOn();
		if( !is_null( $response ) ) {
			$ticket = $response->Ticket;
			
			// StartSession is needed here as for HealthCheck, once LogOn service has ended,
			// the session is ended as well. (Each service only has its own session and it ends
			// within that service call)
			// BizAutoPurge class needs the session to get the ticket, and therefore, here
			// HealthCheck startSession with the $ticket returned by the logOn response.
			require_once BASEDIR.'/server/bizclasses/BizSession.class.php';			
			BizSession::startSession( $ticket );
			
			$this->checkAutoPurgeUserSettings();
			
			$autoPurge = new BizAutoPurge();
			$autoPurgePubs = $autoPurge->retrieveAutoPurgePublications();
			foreach ( $autoPurgePubs as $pubId => $afterDayForPurging ){
				if( $afterDayForPurging > 0 ){ // only check if it is configured to be more than 1day (0 means disabled for this particular brand)
					// retrieve deletedObject(s) that stays in trashCan more than the days specified
					require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';					
					$params = array();
					$params[] = new QueryParam( 'Deleted', '<', BizAutoPurge::getDateForPurging($afterDayForPurging+8));
					$params[] = new Queryparam( 'PublicationId','=', $pubId);
					
					require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
					$pubName = DBPublication::getPublicationName($pubId);
				
					$obj =  BizQuery::queryObjects( 
						$ticket, $this->user, $params, null /*FirstEntry*/, 
						0 /*MaxEntries = All*/, 0 /* deletedobjects*/, null /* forceapp */, false /*Hierarchical*/, 
						null /*Order*/, null /*MinimalProps*/, null /*RequestProps*/, array('Trash') );
					if( count( $obj->Rows) >0 ){
						$help = 'There are \''. count( $obj->Rows ).'\' objects from Brand \'' . $pubName . '\' found in the Trash Can ' .
						'that are much older than \''.$afterDayForPurging.'\' days. ';
						$this->setResult( 'WARN', 'Trash Can Needs Cleaning: Please check your batch that should run daily or weekly.', $help );
					}
				}
			}

			// Ends the session here started by the HealthCheck above.
			BizSession::endSession();
			$this->logOff( $ticket );
		}
   	}
   	
   	/**
   	 * Logon with user and password defined in TESTSUITE setting in configserver.php.
   	 * @return WflogOnResponse on succes. NULL on error
   	 */
   	private function logOn()
   	{
   		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		
		try {
			$service = new WflLogOnService();
			$request = new WflLogOnRequest();
			$request->User          = $this->user;
			$request->Password      = $this->password;
			$request->Server        = 'Enterprise Server';
			$request->ClientAppName = 'AutoPurgeHealthCheck';
			$request->ClientAppVersion = 'v'.SERVERVERSION;
			
			require_once BASEDIR.'/server/utils/UrlUtils.php';
			$clientip = WW_Utils_UrlUtils::getClientIP();
			$request->ClientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
			// >>> BZ#6359 Let's use ip since gethostbyaddr could be extreemly expensive! which also risks throwing "Maximum execution time of 11 seconds exceeded" 
			if( empty($request->ClientName) ) { $request->ClientName = $clientip; }			
			$request->RequestInfo	= array(); // ticket only
			
			$response = $service->execute($request);
		} catch ( BizException $e ) {
			$e = $e; // To keep analyzer happy. ( Why logOn failed is not interest of this test case )
			$this->setResult( 'ERROR', 'Failed to login to check for old deleted objects that need to be purged.', 
								'Please check the TESTSUITE setting in configserver.php if the test user and password are set correctly.' );
			return null;								
		}
		return $response;
   	}
   	
	/**
	 * LogOff user with the given ticket.
	 *
	 * @param string $ticket Ticket to be logged off.
	 */
   	private function logOff( $ticket )
   	{
   		try{
			require_once BASEDIR . '/server/services/wfl/WflLogOffService.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
			$service = new WflLogOffService();
			$request = new WflLogOffRequest();
			$request->Ticket = $ticket;
			$service->execute($request);
		} catch ( BizException $e){
			$this->setResult( 'ERROR', 'Could not logOff user after testing with AutoPurge test case: '.$e->getDetail(),  $e->getMessage() );
		}
   	}
   	
   	/**
   	 * Checks if AutoPurgeTrashCan server job has any user assigned to it.
   	 * When there is no user, error is raised, else it continues and check
   	 * if the user assigned has email filled in (To send notification after
   	 * purging the objects) and also if the user has the rights to purge
   	 * the deleted objects.
   	 *
   	 * @return Boolean True when all the settings are fine, False otherwise.
   	 */
	private function checkAutoPurgeUserSettings()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerJobConfig.class.php';
		$bizJobConfig = new BizServerJobConfig();
		$dbConfigs = $bizJobConfig->listJobConfigs();
		
		foreach ( $dbConfigs as /* $server => */$jobConfigs ) {
			foreach( $jobConfigs as $jobConfig ) {
				if( $jobConfig->JobType == 'AutoPurgeTrashCan' ) {
					if( !$jobConfig->UserId ) {
						$this->setResult( 'ERROR', 'There is no user assigned to the AutoPurgeTrashCan Server Job. '.
						'Click <a href="../../server/admin/serverjobconfigs.php">here</a> to assign a user.');
						return false;
					} else {
						$userId = $jobConfig->UserId;
						break;
					}
				}
			}
		}
		
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$userInfo = DBUser::getUserById( $userId );
		$autoPurgeUser = $userInfo['user'];
		if( !$userInfo['email']) {
			$help = 'Please fill in the email for user \'' . $autoPurgeUser .'\' on the User Maintenance page.';
			$this->setResult( 'WARN', 'Empty Email Address', $help );
		}

		// Need to make sure whether the configured user is System Admin
		// Otherwise auto purging may fail if the user has no authorize to purge!
		$isadmin = hasRights( DBDriverFactory::gen(), $autoPurgeUser );
		if( !$isadmin ) {
			$help = 'Please ensure that user \''.$autoPurgeUser.'\' assigned for AutoPurgeTrashCan server job '.
				'is a System Administrator.';
			$this->setResult( 'ERROR', 'Need a System Administrator', $help );
			return false;
		}
		return true;
	}   	
}