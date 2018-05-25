<?php
/**
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmPublicationAdminAuthorizations_TestCase extends TestCase
{
	public function getDisplayName() { return 'Publication admin authorizations'; }
	public function getTestGoals() { return 'Checks if publication admin authorizations can be round-tripped and deleted successfully.'; }
	public function getTestMethods() {
		return 'Setup test data: <ol>
					<li>Pre-configured variables: System user SU and brand B. (As configured in TESTSUITE option, created in setup of TestSuite.)</li>
					<li>SU: Create UserGroup G1 (without system admin option).</li>
					<li>SU: Create User U1 and make U1 member of G1. </li>
					<li>U1: Log on user.</li>
				</ol>
				Scenario 1: Request Brand with and without Brand admin rights. <ol>
					<li>SU: Create an Authorization Rule R1 for [Brand B - User Group - G1]</li>
					<li>U1: Request Brand B.</li>
					<li>SU: Delete Authorization Rule R1.</li>
					<li>U1: Request Brand B. Expected: Access denied error.</li>
				</ol>
				Scenario 2: Request Brand as User without a User Group. <ol>
					<li>SU: Create an Authorization Rule R2 for [Brand B - User Group - G1]</li>
					<li>SU: Remove User U1 from Group G1.</li>
					<li>U1: Request Brand B. Expected: Access denied error.</li>
					<li>SU: Add User U1 back to Group G1 (repair setup data).</li>
					<li>U1: Request Brand B.</li>
					<li>SU: Delete Authorization Rule R2.</li>
				</ol>
				Tear down test data: <ol>
					<li>U1: Log off user</li>
					<li>SU: Delete User U1.</li>
					<li>SU: Delete User Group G1.</li>
				</ol>';
	}
	public function getPrio() { return 180; }
	public function isSelfCleaning() { return true; }

	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	private $postfix = null;		//Counter to ensure unique objects names.

	private $ticket = null;			//The system admin ticket of the user configured in TESTSUITE.
	private $publicationId = null;	//The publication id of the publication configured in TESTSUITE.
	private $pubChannelId = null;	//The publication channel if configured in TESTSUITE.

	private $admTicket = null;		//Ticket of the brand admin user.
	private $userId = null;			//Brand admin user.
	private $userGroupId = null;	//User group of the brand admin user.

	/**
	 * The main test function
	 *
	 * Called to execute a test case. Needs to be implemented by subclass of TestCase.
	 * There can be many steps to be tested, which all need to take place within this
	 * function. The setResult() function can be used by the implementer to report any
	 * problems found during the test. It is up to the implementer to decide whether or
	 * not to continue with the next step. Precessing errors can be detected by calling
	 * the hasError() function.
	 */
	public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';

		// Because we dynamically create users / groups / authorizations, we can not rely on the current PHP session
		// that runs this test script and simply call services directly. Instead, we use a JSON client to create a new
		// PHP session at the core server to initialize the authorization cache for the acting user (per web service call).
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		$vars = $this->getSessionVariables();
		$this->ticket = @$vars['BuildTest_WebServices_AdmServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR', 'Could not find ticket to test with.', 'Please enable the AdmInitData test.' );
			return;
		}
		$this->publicationId = @$vars['BuildTest_WebServices_AdmServices']['publicationId'];
		if( !$this->publicationId ) {
			$this->setResult( 'ERROR', 'Could not find publicationId to test with.', 'Please enable the AdmInitData test.' );
			return;
		}
		$this->pubChannelId = @$vars['BuildTest_WebServices_AdmServices']['pubChannelId'];
		if( !$this->pubChannelId ) {
			$this->setResult( 'ERROR', 'Could not find pubChannelId to test with.', 'Please enable the AdmInitData test.' );
			return;
		}

		do {
			if( !$this->setUpTestData() ) {
				break;
			}
			if( !$this->scenario001() ) {
				break;
			}
			if( !$this->scenario002() ) {
				break;
			}
			if( !$this->testBadAttemptsCreateService() ) {
				break;
			}
			if( !$this->testBadAttemptsGetService() ) {
				break;
			}
			if( !$this->testBadAttemptsDeleteService() ) {
				break;
			}
		} while( false );
		$this->tearDownTestData();
	}

	/**
	 * Set up the data to be used in the tests.
	 *
	 * Create user and group. Log in the user.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function setUpTestData()
	{
		$this->postfix += 1;

		$userGroup = new AdmUserGroup();
		$userGroup->Name = 'UserGroup_T'.$this->getPrio().'_'.$this->postfix.'_'.date( 'dmy_his' );
		$userGroup->Description = 'User group used in the publication admin authorizations build test.';
		$userGroup->Admin = false;
		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
		$request = new AdmCreateUserGroupsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->UserGroups = array( $userGroup );
		$response = $this->utils->callService( $this, $request, 'Create a user group.' );
		$this->userGroupId = $response->UserGroups[0]->Id;

		$user = new AdmUser();
		$user->Name = 'User_T'.$this->getPrio().'_'.$this->postfix.'_'.date( 'dmy_his' );
		$user->Password = 'password'.$this->postfix;
		$user->UserGroups = array( $userGroup );
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		$request = new AdmCreateUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Users = array( $user );
		$response = $this->utils->callService( $this, $request, 'Create a user.' );
		$user = $response->Users[0];
		$user->Password = 'password'.$this->postfix;
		$this->userId = $user->Id;

		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->userGroupId;
		$request->UserIds = array( $this->userId );
		$this->utils->callService( $this, $request, 'Add user to user group' );

		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User = $user->Name;
		$request->Password = $user->Password;
		$request->ClientName = 'WorkflowUserGroupAuthorization build test';
		$request->ClientAppName = 'Web';
		$request->ClientAppVersion = SERVERVERSION;
		$response = $this->utils->callService( $this, $request, 'Logon the user.' );
		$this->admTicket = $response->Ticket;

		//workaround. In a regular scenario this global is set in the web app, needed for pubAdmin authentication
		global $globUser;
		$globUser = $user->Name;

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Request Brand with and without Brand admin rights.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function scenario001()
	{
		$this->createPubAdmAuth( $this->publicationId, $this->userGroupId );

		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->admTicket;
		$request->RequestModes = array();
		$request->PublicationIds = array( $this->publicationId );
		$this->utils->callService( $this, $request, 'Scenario001: Request the publication before deletion of authorization rule.' );

		$this->deletePubAdmAuths( $this->publicationId, array( $this->userGroupId ) );

		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->admTicket;
		$request->RequestModes = array();
		$request->PublicationIds = array( $this->publicationId );
		$this->utils->callService( $this, $request, 'Scenario001: Request the publication after deletion of authorization rule.', '(S1002)' );

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Request Brand as User without a User Group.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function scenario002()
	{
		$this->createPubAdmAuth( $this->publicationId, $this->userGroupId );

		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';
		$request = new AdmRemoveUsersFromGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->userGroupId;
		$request->UserIds = array( $this->userId );
		$this->utils->callService( $this, $request, 'Remove user from user group.' );

		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->admTicket;
		$request->RequestModes = array();
		$request->PublicationIds = array( $this->publicationId );
		$this->utils->callService( $this, $request, 'Scenario002: Request the publication with user that does not belong to a user group.', '(S1002)' );

		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->userGroupId;
		$request->UserIds = array( $this->userId );
		$this->utils->callService( $this, $request, 'Re-add user to user group.' );

		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$request = new AdmGetPublicationsRequest();
		$request->Ticket = $this->admTicket;
		$request->RequestModes = array();
		$request->PublicationIds = array( $this->publicationId );
		$this->utils->callService( $this, $request, 'Scenario002: Request the publication with the user re-added to the user group.' );

		//tear down
		$this->deletePubAdmAuths( $this->publicationId, array( $this->userGroupId ) );

		return $this->hasError() ? false : true;
	}

	/**
	 * Test bad scenarios using the creation services.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsCreateService()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';
		$request = new AdmCreatePublicationAdminAuthorizationsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 3; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$request->UserGroupIds = array( $this->userGroupId );
					$stepInfo = 'Create a PublicationAdminAuthorization with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = $this->publicationId;
					$request->UserGroupIds = array( PHP_INT_MAX-1 );
					$stepInfo = 'Create a PublicationAdminAuthorization with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 3:
					$request->PublicationId = $this->publicationId;
					$request->UserGroupIds = array( 'id' );
					$stepInfo = 'Create a PublicationAdminAuthorization providing a non-numeric id.';
					$expError = '(S1000)';
					break;
				/*case 4:
					$stepInfo = 'Create a PublicationAdminAuthorization with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 5:
					$stepInfo = 'Create a PublicationAdminAuthorization with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Test bad scenarios using the get services.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsGetService()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationAdminAuthorizationsService.class.php';
		$request = new AdmGetPublicationAdminAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		for( $i = 1; $i <= 2; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$stepInfo = 'Get one or more PublicationAdminAuthorizations with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = 'id';
					$stepInfo = 'Get one or more PublicationAdminAuthorizations providing a non-numeric id.';
					$expError = '(S1000)';
					break;
				/*case 3:
					$stepInfo = 'Get one or more PublicationAdminAuthorizations with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 4:
					$stepInfo = 'Get one or more PublicationAdminAuthorizations with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Test bad scenarios using the delete services.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsDeleteService()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationAdminAuthorizationsService.class.php';
		$request = new AdmDeletePublicationAdminAuthorizationsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 2; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$stepInfo = 'Delete one or more PublicationAdminAuthorizations with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = 'id';
					$stepInfo = 'Delete one or more PublicationAdminAuthorizations providing a non-numeric id.';
					$expError = '(S1000)';
					break;
				/*case 3:
					$stepInfo = 'Delete one or more PublicationAdminAuthorizations with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 4:
					$stepInfo = 'Delete one or more PublicationAdminAuthorizations with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Tear down test data.
	 *
	 * Log off user; delete user and user group; deallocate variables.
	 */
	private function tearDownTestData()
	{
		if( isset( $this->admTicket ) ) {
			require_once BASEDIR.'/server/services/adm/AdmLogOffService.class.php';
			$request = new AdmLogOffRequest();
			$request->Ticket = $this->admTicket;
			$this->utils->callService( $this, $request, 'Log off user.' );
			unset( $this->admTicket );
		}
		if( isset( $this->userId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
			$request = new AdmDeleteUsersRequest();
			$request->Ticket = $this->ticket;
			$request->UserIds = array( $this->userId );
			$this->utils->callService( $this, $request, 'TearDown: Delete AdmUser.' );
			unset( $this->admUser );
		}
		if( isset( $this->userGroupId ) ) {
			//TODO: Should call DeleteUserGroupsService, in the future
			require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
			BizAdmUser::deleteUserGroup( $this->userGroupId );
			LogHandler::Log( 'WorkflowUserGroupAuthorizations_TestCase', 'INFO', 'TearDown: Delete AdmUserGroup' );
			unset( $this->admUserGroup );
		}

		unset( $this->utils );
		unset( $this->postfix );
		unset( $this->ticket );
		unset( $this->publicationId );
	}

	/************************** UTIL FUNCTIONS **************************/

	/**
	 * Create a publication admin authorization rule.
	 *
	 * @param integer $publicationId
	 * @param integer $userGroupId
	 */
	private function createPubAdmAuth( $publicationId, $userGroupId )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';
		$request = new AdmCreatePublicationAdminAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->UserGroupIds = array( $userGroupId );
		$this->utils->callService( $this, $request, 'Create a PublicationAdminAuthorization rule.' );
	}

	/**
	 * Delete one or more publication admin authorization rules.
	 *
	 * @param integer $publicationId
	 * @param integer[] $userGroupIds
	 */
	private function deletePubAdmAuths( $publicationId, array $userGroupIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationAdminAuthorizationsService.class.php';
		$request = new AdmDeletePublicationAdminAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->UserGroupIds = $userGroupIds;
		$this->utils->callService( $this, $request, 'Delete PublicationAdminAuthorization rules.' );
	}
}