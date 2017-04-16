<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmUsers_TestCase extends TestCase
{
	public function getDisplayName() { return 'Admin users and groups'; }
	public function getTestGoals()   { return 'Checks if user related admin services work as expected. '; }
	public function getTestMethods() { return
		'Calls user related admin services in all various ways to hit all business logics.
		<ol>
			<li>User and usergroup creation (valid and invalid data)</li>
			<li>User and usergroup modifying (valid and invalid data)</li>
			<li>User and usergroup memberships modifications and retriavals</li>
			<li>Access rights for end-users</li>
			<li>Usability test, delete or deactivate your own user</li>
		</ol>';
	}
    public function getPrio()        { return 100; }

	// TODO: Read field lenghts from dbmodel.php instead of these defines:
	const ADMTEST_USER_SHORTNAMELEN = 40;
	const ADMTEST_USER_FULLNAMELEN  = 255;
	const ADMTEST_USERGROUP_NAMELEN = 100;
	
	private $userIds = array(); // created user ids (for garbage collector)
	private $groupIds = array(); // created user group ids (for garbage collector)
	private $postfix = 0; // internal counter to make users and groups unique
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	
	final public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';

		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		// Retrieve the Ticket that has been determined by AdmInitData TestCase.
   		$vars = $this->getSessionVariables();
   		$this->ticket = @$vars['BuildTest_WebServices_AdmServices']['ticket'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the AdmInitData test.' );
			return;
		}

		// ------- USER/GROUP TESTS -------

		// Test creating users
		$this->updateMode = false;
		$users = $this->testUsersWithGoodNames();
		$existingUser = end($users);
		$this->testUsersWithBadNames( $existingUser->Name );
		$this->testCreateUsersWithBadMiscProps();
		
		// Test creating groups
		$groups = $this->testUserGroupsWithGoodNames();
		$existingGroup = end($groups);
		$this->testUserGroupsWithBadNames( $existingGroup->Name );
			
		// Test modifying users
		$this->updateMode = true;
		/*$users =*/ $this->testUsersWithGoodNames();
		$this->testUsersWithBadNames( $existingUser->Name );
		$this->testCreateUsersWithBadMiscProps();
		
		// Test modifying groups
		/*$groups =*/ $this->testUserGroupsWithGoodNames();
		$this->testUserGroupsWithBadNames( $existingGroup->Name );

		// Test user-group memberships and retrievals
		$this->testAddMemberships();
		$this->testUsersRetrievals();
		$this->testUserGroupsRetrievals();

		//Testing retrieval with RequestModes
		//For GetUsers
		$this->testUsersRetrievalsWithUserGroups();
		//For ModifyUsers
		$this->testModifyUsersWithUserGroups();
		//For GetUserGroups
		$this->testUserGroupsRetrievalsWithUsers();
		//For ModifyUserGroups
		$this->testModifyUserGroupsWithUsers();
		$this->testRemoveMemberships();

		// Test import user with the encrypted password
		$this->testImportUserWithEncryptedPassword( $existingUser );

		// ------- ACCESS RIGHTS -------

		// Logon normal end-user for access/security testing 
		$logonReq = $this->buildLogOn();
		$logonReq->User = $existingUser->Name;
		$logonReq->Password = 'ww'; // this is not returned on purpose: $existingUser->Password;
		$this->logon( $logonReq, 'Logon end-user for access/security testing.' );
		if( $this->ticket ) {

			// Try if all tested admin services have the door closed for end-users
			$this->testAllWithEndUserRights();
		
			// Logout admin/test user
			$this->logoff();
		}

		// ------- USABILITY TESTING -------
		//Login again, because a logoff is done in the code above
		$logonReq = $this->buildLogOn();
		$this->logon($logonReq, 'Logon admin user for adding another user to the admin group');
		//Store the current active ticket to restore at the end, this saves some logon/off requests
		$this->adminTicket = $this->ticket;
		$this->prepareSelfTests();
		$this->testSelfDeactivation();
		$this->testSelfDeletion();
		//Logoff the selftest user and restore the previous ticket
		$this->logoff();
		$this->ticket = $this->adminTicket;
		$this->testDeleteSelfTestUser();

		$this->testDeleteUserGroups();

		// ------- GARBAGE COLLECTION -------

		// Login the admin/test user again to clean-up created users/groups
		$logonReq = $this->buildLogOn();
		$this->logon( $logonReq, 'Logon TESTSUITE admin user again, to cleanup created users and groups.' );
		if( !$this->ticket ) {
			return;
		}

		// Cleanup created users and groups
		$this->cleanupUserGroups();
		$this->cleanupUsers();

		// Logout admin/test user
		$this->logoff();

		/*
		// >>> Example to init Enterprise Server when needed to call biz/db layer (by-passing services):
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		BizSession::startSession( $ticket );
		$user = BizSession::checkTicket( $ticket );
		$this->setResult( 'INFO',  'Working in the name of user: '.$user, '' );
		// ... call biz/db layer here ...
		BizSession::endSession();
		// <<<
		*/
	}

	// - - - - - - - - - - - - - - - - - - - - - USERS - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Returns the first created user id.
	 * @return integer
	 */
	private function getUserId()
	{
		$keys = array_keys( $this->userIds );
		return reset( $keys );
	}
	
	/**
	 * Create new users with good names.
	 * @return array of AdmUser.
	 */
	private function testUsersWithGoodNames()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';
		$users = array();
		for( $i = 1; $i <= 9; $i++ ) {
			$user = $this->buildUser();
			$action = $this->updateMode ? 'Update' : 'Create';
			$stepInfo = '';
			switch( $i ) {
				case 1:
					// => rely on default $user->Name
					$stepInfo = $action.' user with decent properties filled in.';
					break;
				case 2:
					$user->Name = str_pad( $user->Name, self::ADMTEST_USER_SHORTNAMELEN, '_' );
					$stepInfo = $action.' user with an exactly fitting short-name.';
					break;
				case 3:
					// => rely on default $user->Name
					$user->FullName = str_pad( $user->Name, self::ADMTEST_USER_FULLNAMELEN, '_' );
					$stepInfo = $action.' user with an exactly fitting full-name.';
					break;
				case 4:
					$user->Name .= chr(0xE6).chr(0x98).chr(0x9F);
					$user->Name .= chr(0xE6).chr(0xB4).chr(0xB2);
					$stepInfo = $action.' user with a Chinese short-name (3-byte Unicode).';
					break;
				case 5:
					$user->Name .= chr(0xE8).chr(0xAA).chr(0xAD);
					$user->Name .= chr(0xE5).chr(0xA3).chr(0xB2);
					$stepInfo = $action.' user with a Japanese short-name (3-byte Unicode).';
					break;
				case 6:
					$user->Name .= chr(0xED).chr(0x95).chr(0x9C);
					$user->Name .= chr(0xEA).chr(0xB5).chr(0xAD);
					$stepInfo = $action.' user with a Korean short-name (3-byte Unicode).';
					break;
				case 7:
					$user->Name .= chr(0xD0).chr(0x92);
					$user->Name .= chr(0xD0).chr(0xBB);
					$stepInfo = $action.' user with a Russian short-name (2-byte Unicode).';
					break;
				case 8:
					$user->Name .= '\'';
					$stepInfo = $action.' user with a single quote used in short-name.';
					break;
				case 9:
					$name = $user->Name;
					$password = $user->Password;
					$user = new AdmUser();
					$user->Name = $name;
					$user->Password = $password;
					$stepInfo = $action.' user with all properties nullified except the short-name.';
					break;
			}
			if( $this->updateMode ) {
				$user->Id = $this->getUserId();
				$request = new AdmModifyUsersRequest();
			} else {
				$request = new AdmCreateUsersRequest();
			}
			$request->Ticket 		= $this->ticket;
			$request->RequestModes 	= array();
			$request->Users 		= array( $user );
			$response = $this->utils->callService( $this, $request, $stepInfo );
			$this->collectUsers( @$response->Users );
			if( $response && count($response->Users) ) {
				$newUser = $response->Users[0];
				$users[$newUser->Id] = $newUser;
			}
		}
		return $users;
	}

	/**
	 * Attempts to create/modify users with all kind of bad names.
	 *
	 * @param string $existingName
	 */
	private function testUsersWithBadNames( $existingName )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';
		for( $i = 1; $i <= 5; $i++ ) {
			$user = $this->buildUser();
			$action = $this->updateMode ? 'Update' : 'Create';
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$user->Name = null;
					$stepInfo = $action.' user with nullified name.';
					$expError = '(S1032)';
					break;
				case 2:
					$user->Name = '';
					$stepInfo = $action.' user with empty name.';
					$expError = '(S1032)';
					break;
				case 3:
					$user->Name = str_pad( $user->Name, self::ADMTEST_USER_SHORTNAMELEN+1, '_' );
					$stepInfo = $action.' user with a too long short-name.';
					$expError = '(S1026)';
					break;
				case 4:
					// => rely on default $user->Name
					$user->FullName = str_pad( $user->FullName, self::ADMTEST_USER_FULLNAMELEN+1, '_' );
					$stepInfo = $action.' user with a too long full-name.';
					$expError = '(S1026)';
					break;
				case 5:
					$user->Name = $existingName;
					$stepInfo = $action.' user with an existing name.';
					$expError = '(S1010)';
					break;
			}
			if( $this->updateMode ) {
				$user->Id = $this->getUserId();
				$request = new AdmModifyUsersRequest();
			} else {
				$request = new AdmCreateUsersRequest();
			}
			$request->Ticket 		= $this->ticket;
			$request->RequestModes 	= array();
			$request->Users 		= array( $user );
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			$this->collectUsers( @$response->Users );
		}
	}

	/**
	 * Attempts to create user with all kind of bad misc property values (other than the dedicated tests above).
	 */
	private function testCreateUsersWithBadMiscProps()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';
		for( $i = 1; $i <= 6; $i++ ) {
			$user = $this->buildUser();
			$action = $this->updateMode ? 'Update' : 'Create';
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$user->Password = null;
					$stepInfo = $action.' user with nullified password.';
					$expError = $this->updateMode ? '' : '(S1033)';
					break;
				case 2:
					$user->Password = '';
					$stepInfo = $action.' user with empty password.';
					$expError = '(S1033)';
					break;
				case 3:
					$user->EmailAddress = 'woodwing.com';
					$stepInfo = $action.' user having an email address without an @ char.';
					$expError = '(S1018)';
					break;
				case 4:
					$user->EmailAddress = 'hello@woodwing';
					$stepInfo = $action.' user having an email address without a dot.';
					$expError = '(S1018)';
					break;
				case 5:
					$user->ValidFrom = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+7, date('Y')));
					$user->ValidTill = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
					$stepInfo = $action.' user having Valid From set -after- the Valid Till.';
					$expError = '(S1122)';
					break;
				case 6:
					$user->TrackChangesColor = '111111'; // Needs to be confrom the Color specification, but this is an invalid status color
					$stepInfo = $action.' user with bad Tracked Changes Color.';
					$expError = '(S1056)';
					break;
			}
			if( $this->updateMode ) {
				$user->Id = $this->getUserId();
				$request = new AdmModifyUsersRequest();
			} else {
				$request = new AdmCreateUsersRequest();
			}
			$request->Ticket 		= $this->ticket;
			$request->RequestModes 	= array();
			$request->Users 		= array( $user );
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			$this->collectUsers( @$response->Users );
		}
	}

	/**
	 * Test getting users in several valid/eratic ways.
	 */
	private function testUsersRetrievals()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';
		for( $i = 1; $i <= 5; $i++ ) {
			$userIds = null;
			$groupId = null;
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1;
					$userIds = null;
					$groupId = null;
					$stepInfo = 'Get all users (system wide).';
					$expError = null;
					break;
				case 2:
					$userIds = null;
					$groupId = $this->getUserGroupId();
					$stepInfo = 'Get all users for one specific group.';
					$expError = null;
					break;
				case 3;
					$userIds = array_keys( $this->userIds );
					$groupId = null;
					$stepInfo = 'Get a specified list of user groups.';
					$expError = null;
					break;
				case 4;
					$userIds = null;
					$groupId = PHP_INT_MAX-3;
					$stepInfo = 'Get all users for a non-existing group.';
					$expError = '(S1056)';
					break;
				case 5;
					$userIds = array( PHP_INT_MAX-3, PHP_INT_MAX-4 );
					$groupId = null;
					$stepInfo = 'Get a specified list of non-existing users.';
					$expError = '(S1056)';
					break;
			}
			$request = new AdmGetUsersRequest();
			$request->Ticket 		= $this->ticket;
			$request->RequestModes 	= array();
			$request->UserIds 		= $userIds;
			$request->GroupId 		= $groupId;
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
	}
	/**
	 * Test getting users with userGroups included in several valid/eratic ways.
	 */
	private function testUsersRetrievalsWithUserGroups()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';

		$stepInfo = 'Get all users for one specific group including their groups.';
		$request = new AdmGetUsersRequest();
		$request->Ticket = $this->ticket;
		//Also set the RequestModes
		$request->RequestModes = array("GetUserGroups");
		$request->UserIds = null;
		$request->GroupId = $this->getUserGroupId();

		$result = $this->utils->callService( $this, $request, $stepInfo);
		$testPass = true;
		foreach ($result->Users as $user) {
			$pass = false;
			foreach($user->UserGroups as $userGroup) {
				if ($userGroup->Id == $request->GroupId) {
					$pass = true;
				}
			}
			if (!$pass) {
				$testPass = false;
			}
		}
		if (!$testPass) {
			$this->setResult('ERROR', 'The user returned by AdmGetUsers service call has no userGroups, but they are expected.');
		}
	}

	/**
	 * Modify users, then test if the userGroups are returned
	 */
	private function testModifyUsersWithUserGroups()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';

		$user = $this->buildUser();
		$user->Name = str_pad( $user->Name, self::ADMTEST_USER_SHORTNAMELEN, '_' );
		$stepInfo = 'After an user has been modified it should also return the UserGroups';
		//There is no check that this users actually has groups, but that is not needed for the test
		$user->Id = $this->getUserId();
		$request = new AdmModifyUsersRequest();

		$request->Ticket = $this->ticket;
		//Also set the RequestModes
		$request->RequestModes = array("GetUserGroups");
		$request->Users = array( $user );
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$this->collectUsers( @$response->Users );

		$testPass = true;
		foreach ($response->Users as $user) {
			if(count($user->UserGroups) < 1){
				$testPass = false;
			}
		}
		if (!$testPass) {
			$this->setResult('ERROR', 'The user returned by Modify service call has no userGroups, but they are expected.');
		}
	}

	// - - - - - - - - - - - - - - - - - - - USER GROUPS - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Returns the first created user group id.
	 * @return integer
	 */
	private function getUserGroupId()
	{
		$keys = array_keys( $this->groupIds );
		return reset( $keys );
	}

	/**
	 * Creates/Updates user groups.
	 * @return array of AdmUserGroup
	 */
	private function testUserGroupsWithGoodNames()
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmModifyUserGroupsService.class.php';
		$groups = array();
		for( $i = 1; $i <= 6; $i++ ) {
			$stepInfo = '';
			$group = $this->buildUserGroup();
			$action = $this->updateMode ? 'Update' : 'Create';
			switch( $i ) {
				case 1:
					// => rely on default $group->Name
					$stepInfo = $action.' user group with decent properties filled in.';
					break;
				case 2:
					$group->Name = str_pad( $group->Name, self::ADMTEST_USERGROUP_NAMELEN, '_' );
					$stepInfo = $action.' user group with an exactly fitting name.';
					break;
				case 3:
					$group->Name .= chr(0xE6).chr(0x98).chr(0x9F);
					$group->Name .= chr(0xE6).chr(0xB4).chr(0xB2);
					$stepInfo = $action.' user group with a Chinese short-name (3-byte Unicode).';
					break;
				case 4:
					$group->Name .= chr(0xE8).chr(0xAA).chr(0xAD);
					$group->Name .= chr(0xE5).chr(0xA3).chr(0xB2);
					$stepInfo = $action.' user group with a Japanese short-name (3-byte Unicode).';
					break;
				case 5:
					$group->Name .= chr(0xED).chr(0x95).chr(0x9C);
					$group->Name .= chr(0xEA).chr(0xB5).chr(0xAD);
					$stepInfo = $action.' user group with a Korean short-name (3-byte Unicode).';
					break;
				case 6:
					$group->Name .= chr(0xD0).chr(0x92);
					$group->Name .= chr(0xD0).chr(0xBB);
					$stepInfo = $action.' user group with a Russian short-name (2-byte Unicode).';
					break;
			}
			if( $this->updateMode ) {
				$group->Id = $this->getUserGroupId();
				$request = new AdmModifyUserGroupsRequest();
			} else {
				$request = new AdmCreateUserGroupsRequest();
			}
			$request->Ticket 		= $this->ticket;
			$request->RequestModes 	= array();
			$request->UserGroups 	= array( $group );
			$response = $this->utils->callService( $this, $request, $stepInfo );
			$this->collectUserGroups( @$response->UserGroups );
			if( $response && count($response->UserGroups) ) {
				$newGroup = $response->UserGroups[0];
				$groups[$newGroup->Id] = $newGroup;
			}
		}
		return $groups;
	}

	/**
	 * Test getting user groups in several valid/eratic ways.
	 */
	private function testUserGroupsRetrievals()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
		for( $i = 1; $i <= 5; $i++ ) {
			$userId = null;
			$groupIds = null;
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1;
					$userId = null;
					$groupIds = null;
					$stepInfo = 'Get all user groups (system wide).';
					$expError = null;
					break;
				case 2:
					$userId = $this->getUserId();
					$groupIds = null;
					$stepInfo = 'Get all user groups for one specific user.';
					$expError = null;
					break;
				case 3;
					$userId = null;
					$groupIds = array_keys( $this->groupIds );
					$stepInfo = 'Get a specified list of user groups.';
					$expError = null;
					break;
				case 4;
					$userId = PHP_INT_MAX-3;
					$groupIds = null;
					$stepInfo = 'Get all user groups for a non-existing user.';
					$expError = '(S1056)';
					break;
				case 5;
					$userId = null;
					$groupIds = array( PHP_INT_MAX-3, PHP_INT_MAX-4 );
					$stepInfo = 'Get a specified list of non-existing user groups.';
					$expError = '(S1056)';
					break;
			}
			$request = new AdmGetUserGroupsRequest();
			$request->Ticket 		= $this->ticket;
			$request->RequestModes 	= array();
			$request->UserId 		= $userId;
			$request->GroupIds	 	= $groupIds;
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
	}

	/**
	 * Attempts to create/update user groups with all kind of bad names.
	 *
	 * @param string $existingName
	 */
	private function testUserGroupsWithBadNames( $existingName )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmModifyUserGroupsService.class.php';
		for( $i = 1; $i <= 4; $i++ ) {
			$group = $this->buildUserGroup();
			$action = $this->updateMode ? 'Update' : 'Create';
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$group->Name = null;
					$stepInfo = $action.' user group with nullified name.';
					$expError = '(S1032)';
					break;
				case 2:
					$group->Name = '';
					$stepInfo = $action.' user group with empty name.';
					$expError = '(S1032)';
					break;
				case 3:
					$group->Name = str_pad( $group->Name, self::ADMTEST_USERGROUP_NAMELEN+1, '_' );
					$stepInfo = $action.' user group with a too long name.';
					$expError = '(S1026)';
					break;
				case 4:
					$group->Name = $existingName;
					$stepInfo = $action.' user group with an existing name.';
					$expError = '(S1010)';
					break;
			}
			if( $this->updateMode ) {
				$group->Id = $this->getUserGroupId();
				$request = new AdmModifyUserGroupsRequest();
			} else {
				$request = new AdmCreateUserGroupsRequest();
			}
			$request->Ticket 		= $this->ticket;
			$request->RequestModes 	= array();
			$request->UserGroups 	= array( $group );
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			$this->collectUserGroups( @$response->UserGroups );
		}
	}

	/**
	 * Modify usergroup, then test if the users are returned
	 */
	private function testModifyUserGroupsWithUsers()
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyUserGroupsService.class.php';

		$usergroup = $this->buildUserGroup();
		$usergroup->Name = str_pad( $usergroup->Name, self::ADMTEST_USERGROUP_NAMELEN, '_' );
		$stepInfo = 'After an usergroup has been modified it should also return the Users';
		//There is no check that this usergroups actually has users, but that is not needed for the test
		$usergroup->Id = $this->requestModesData['groupId'];
		$request = new AdmModifyUserGroupsRequest();

		$request->Ticket = $this->ticket;
		//Also set the RequestModes
		$request->RequestModes = array("GetUsers");
		$request->UserGroups = array( $usergroup );
		$response = $this->utils->callService( $this, $request, $stepInfo );

		$idCount = 0;
		$testPass = false;
		//This should be only one
		foreach ($response->UserGroups as $usergroup) {
			foreach($usergroup->Users as $user) {
				if (is_numeric($user->Id) && $this->requestModesData['userIds'][$user->Id] == 1) {
					$idCount++;
				}
			}
			if ($idCount == count($this->requestModesData['userIds'])
				&& count($response->UserGroups) == 1) {
				$testPass = TRUE;
			}
		}
		if (!$testPass) {
			$this->setResult('ERROR', 'The usergroups returned by Modify service call has no users, but they are expected.');
		}
	}

	/**
	 * Test getting users with userGroups included in several valid/eratic ways.
	 */
	private function testUserGroupsRetrievalsWithUsers()
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';

		$stepInfo = 'Get all usergroups including their users.';
		$request = new AdmGetUserGroupsRequest();
		$request->Ticket = $this->ticket;
		//Also set the RequestModes
		$request->RequestModes = array("GetUsers");
		$request->UserIds = null;
		$request->GroupIds = array( $this->requestModesData['groupId'] );

		//$result will contain all userGroups
		$result = $this->utils->callService( $this, $request, $stepInfo);
		$idCount = 0;
		$testPass = FALSE;
		//This should be only one
		foreach ($result->UserGroups as $usergroup) {
			foreach($usergroup->Users as $user) {
				if (is_numeric($user->Id) && $this->requestModesData['userIds'][$user->Id] == 1) {
					$idCount++;
				}
			}
			if ($idCount == count($this->requestModesData['userIds'])
				&& count($result->UserGroups) == 1) {
				$testPass = TRUE;
			}
		}

		if (!$testPass) {
			$this->setResult('ERROR', 'The usergroup returned by AdmGetUserGroups service call has no users, but they are expected.');
		}
	}

	// - - - - - - - - - - - - - - - - - - - - - MEMBERSHIPS - - - - - - - - - - - - - - - - - - - -

	/**
	 * Adds users-groups memberships.
	 */
	private function testAddMemberships()
	{
		$userId = $this->getUserId();
		$groupId = $this->getUserGroupId();
		
		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds = array_keys( $this->userIds );
		$request->GroupId = $groupId;
		//Store these ids for later use with RequestModes
		$this->requestModesData = array(
			'groupId' => $groupId,
			'userIds' => $this->userIds
		);
		$stepInfo = 'Add users to group.';
		$this->utils->callService( $this, $request, $stepInfo );

		require_once BASEDIR.'/server/services/adm/AdmAddGroupsToUserService.class.php';
		$request = new AdmAddGroupsToUserRequest();
		$request->Ticket 		= $this->ticket;
		$request->UserId 		= $userId;
		$request->GroupIds		= array_keys( $this->groupIds );
		$stepInfo = 'Add groups to user.';
		$this->utils->callService( $this, $request, $stepInfo );
	}

	// - - - - - - - - - - - - - - - - - - - - - SELF DELETING - - - - - - - - - - - - - - - - - - - -


	/**
	 * Prepare the test
	 */
	private function prepareSelfTests()
	{
		//Create an user to use for the test
		$request = new AdmCreateUsersRequest();

		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		//Build the user
		$request->Users = array( $this->buildUser() );
		$response = $this->utils->callService( $this, $request, 'Build user for self delete test' );
		//We will delete this user in a test, but to sure we also add it to the garbage collector
		$this->collectUsers( @$response->Users );
		$this->selfUser = @$response->Users[0];

		//Create an userGroup that has admin rights
		$group = $this->buildUserGroup();
		$group->Admin = true;
		$request = new AdmCreateUserGroupsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->UserGroups = array( $group );
		$stepInfo = "Create userGroup for self delete test";
		$response = $this->utils->callService( $this, $request, $stepInfo );
		//The garbage collector takes care of deleting this usergroup
		$this->collectUserGroups( @$response->UserGroups );
		$adminGroup = @$response->UserGroups[0];

		//Make the user an admin
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds = array($this->selfUser->Id);
		$request->GroupId = $adminGroup->Id;
		$stepInfo = 'Add test user to admin group.';
		$this->utils->callService( $this, $request, $stepInfo );

		//Logon with the new user
		$logonReq = $this->buildLogOn();
		$logonReq->User = $this->selfUser->Name;
		$logonReq->Password = 'ww';
		$this->logon( $logonReq, 'Logon test user.' );
	}

	/**
	 * Try to delete the active account
	 */
	private function testSelfDeletion()
	{
		//Try to delete ourself, this should fail
		require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
		$request = new AdmDeleteUsersRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds = array($this->selfUser->Id);
		$stepInfo = 'Delete own user with expected S1131';
		$this->utils->callService( $this, $request, $stepInfo, "(S1131)" );

	}
	/**
	 * Try to delete the selftest user with another user
	 */
	private function testDeleteSelfTestUser()
	{
		//Delete the test user, this should pass now
		$request = new AdmDeleteUsersRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds = array($this->selfUser->Id);
		$stepInfo = 'Delete the selfDelete user';
		$this->utils->callService( $this, $request, $stepInfo );
	}
	/**
	 * Try to deactivate the active account
	 */
	private function testSelfDeactivation()
	{
		//Try to deactivate ourself, this should fail
		$request = new AdmModifyUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$user = $this->selfUser;
		$user->Deactivated = true;
		$request->Users = array($user);
		$stepInfo = 'Deactivate own user with expected S1132';
		$this->utils->callService( $this, $request, $stepInfo, "(S1132)" );

		//Try to modify ourself, this should pass
		$request = new AdmModifyUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$user = $this->selfUser;
		$user->Deactivated = false;
		$user->Location = "WoodWing HQ";
		$user->TrackChangesColor = "FFFFFF";
		$request->Users = array($user);
		$stepInfo = 'Modify own user';
		$this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * Adds users-groups memberships.
	 */
	private function testRemoveMemberships()
	{
		$userId = $this->getUserId();
		$groupId = $this->getUserGroupId();

		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';
		$request = new AdmRemoveUsersFromGroupRequest();
		$request->Ticket 		= $this->ticket;
		$request->UserIds 		= array_keys( $this->userIds );
		$request->GroupId 		= $groupId;
		$stepInfo = 'Remove users from group.';
		$this->utils->callService( $this, $request, $stepInfo );

		require_once BASEDIR.'/server/services/adm/AdmRemoveGroupsFromUserService.class.php';
		$request = new AdmRemoveGroupsFromUserRequest();
		$request->Ticket 		= $this->ticket;
		$request->UserId 		= $userId;
		$request->GroupIds		= array_keys( $this->groupIds );
		$stepInfo = 'Remove groups from user.';
		$this->utils->callService( $this, $request, $stepInfo );
	}

	// - - - - - - - - - - - - - - - - - - - ACCESS RIGHTS  - - - - - - - - - - - - - - - - - - 
	
	private function testAllWithEndUserRights()
	{
		for( $i = 1; $i <= 12; $i++ ) {
			$request = null;
			switch( $i ) {
				case 1:
					require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
					$request = new AdmCreateUsersRequest();
					$request->RequestModes = array();
					$request->Users = array();
					break;
				case 2:
					require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';
					$request = new AdmModifyUsersRequest();
					$request->RequestModes = array();
					$request->Users = array();
					break;
				case 3:
					require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';
					$request = new AdmGetUsersRequest();
					$request->RequestModes = array();
					break;
				case 4:
					require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
					$request = new AdmCreateUserGroupsRequest();
					$request->RequestModes = array();
					$request->UserGroups = array();
					break;
				case 5:
					require_once BASEDIR.'/server/services/adm/AdmModifyUserGroupsService.class.php';
					$request = new AdmModifyUserGroupsRequest();
					$request->RequestModes = array();
					$request->UserGroups = array();
					break;
				case 6:
					require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
					$request = new AdmGetUserGroupsRequest();
					$request->RequestModes = array();
					break;
				case 7:
					require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
					$request = new AdmAddUsersToGroupRequest();
					$request->UserIds = array();
					$request->GroupId = 0;
					break;
				case 8:
					require_once BASEDIR.'/server/services/adm/AdmAddGroupsToUserService.class.php';
					$request = new AdmAddGroupsToUserRequest();
					$request->GroupIds = array();
					$request->UserId = 0;
					break;
				case 9:
					require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';
					$request = new AdmRemoveUsersFromGroupRequest();
					$request->UserIds = array();
					$request->GroupId = 0;
					break;
				case 10:
					require_once BASEDIR.'/server/services/adm/AdmRemoveGroupsFromUserService.class.php';
					$request = new AdmRemoveGroupsFromUserRequest();
					$request->GroupIds = array();
					$request->UserId = 0;
					break;
				case 11:
					require_once BASEDIR.'/server/services/adm/AdmDeleteUserGroupsService.class.php';
					$request = new AdmDeleteUserGroupsRequest();
					$request->GroupIds = array();
					$request->UserId = 0;
					break;
				case 12:
					require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
					$request = new AdmDeleteUsersRequest();
					$request->UserIds = array();
					$request->UserId = 0;
					break;
			}
			$request->Ticket = $this->ticket;
			$stepInfo = 'Check service without admin rights.';
			$this->utils->callService( $this, $request, $stepInfo, '(S1002)' );
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - GARBAGE COLLECTION - - - - - - - - - - - - - - - - - - 

	/**
	 * Garbage collector, cleaning up created users (for testing).
	 */
	private function cleanupUsers()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
		$request = new AdmDeleteUsersRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds= array_keys($this->userIds);
		$stepInfo = 'Garbage collector removing users';
		$this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * Garbage collector, caching users to cleanup later.
	 * @param array of AdmUser
	 */
	private function collectUsers( $users )
	{
		if( $users ) foreach( $users as $user ) {
			$this->userIds[$user->Id] = true;
		}
	}

	/**
	 * Garbage collector, cleaning up created user groups (for testing).
	 */
	private function cleanupUserGroups()
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUserGroupsService.class.php';
		$request = new AdmDeleteUserGroupsRequest();
		$request->Ticket 		= $this->ticket;
		//$this->groupsIds is [123 => true], so we flip it to get the ids
		$request->GroupIds = array_keys($this->groupIds);
		$stepInfo = 'Garbage collector removing user groups';
		$this->utils->callService( $this, $request, $stepInfo );
		BizSession::endSession();
	}

	/**
	 * Garbage collector, caching user groups to cleanup later.
	 * @param array of AdmUserGroup
	 */
	private function collectUserGroups( $groups )
	{
		if( $groups ) foreach( $groups as $group ) {
			$this->groupIds[$group->Id] = true;
		}
	}

	// - - - - - - - - - - - - - - - - - - - - OBJECT FACTORIES - - - - - - - - - - - - - - - - - -

	/**
	 * Build a valid and fully filled in AdmUser object in memory to use as basis for testing user services.
	 * @return AdmUser object.
	 */
	private function buildUser()
	{
		$this->postfix += 1;  // avoid duplicate names when many created within the same second
		$user = new AdmUser();
		$user->Name				= 'User_'. date('dmy_his').'#'.$this->postfix;
		$user->Password			= 'ww';
		$user->EmailAddress		= $user->Name.'@woodwing.com';
		$user->Deactivated 		= false;
		$user->FixedPassword 	= false;
		$user->EmailUser		= true;
		$user->EmailGroup		= true;
		$user->ValidFrom		= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d') , date('Y')));
		$user->ValidTill		= date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+90, date('Y')));
		$user->TrackChangesColor= '00FFFF'; // cyan
		$user->PasswordExpired	= 0;
		$user->Language			= 'nlNL';
		$user->Organization		= 'WoodWing';
		$user->Location			= 'Zaandam';
		return $user;
	}

	/**
	 * Build a valid and fully filled in AdmUserGroup object in memory to use as basis for testing user group services.
	 * @return AdmUserGroup object.
	 */
	private function buildUserGroup()
	{
		$this->postfix += 1; // avoid duplicate names when many created within the same second
		$group = new AdmUserGroup();
		$group->Name			= 'Group_'. date('dmy_his').'#'.$this->postfix;
		$group->Description 	= 'Generated by the Build Test';
		$group->Admin 			= false;
		$group->Routing 		= true;
		$group->ExternalId 		= null;
		$group->Users 			= null;
		return $group;
	}

	// - - - - - - - - - - - - - - - - - - - - - SERVICE UTILS - - - - - - - - - - - - - - - - - - -

	// TODO: Move the functions below to a helper class for web service testing!
	
	/**
	 * Builds a default logon request to be used as basis for testing.
	 * It respects the user account configured with the TESTSUITE option at configserver.php.
	 *
	 * @return WflLogOnRequest
	 */
	private function buildLogOn()
	{
		// We fallback on the TESTSUITE defined test user (for wwtest)
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
		
		// Determine client app name
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientIP = WW_Utils_UrlUtils::getClientIP();
		$clientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
		if ( !$clientName || ($clientName == $clientIP )) {
			$clientName = gethostbyaddr($clientIP); 
		}

		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php';
		$request = new WflLogOnRequest();
		$request->User = $suiteOpts['User'];
		$request->Password = $suiteOpts['Password'];
		$request->Ticket = '';
		$request->Server = 'Enterprise Server';
		$request->ClientName = $clientName;
		$request->Domain = '';
		$request->ClientAppName = 'AdmUsers TestCase'; // Do not use "Web" or else it would implicitly expire the ticket of AdmInitData test.
		$request->ClientAppVersion = 'v'.SERVERVERSION;
		$request->ClientAppSerial = '';
		$request->ClientAppProductKey = '';
		$request->RequestInfo = array(); // we only need the ticket (performance reason)
		return $request;
	}

	/**
	 * Tries to logon.
	 * 
	 * @param WflLogOnRequest $request
	 * @param string $stepInfo
	 * @param string|null $expError
	 * @return WflLogOnResponse
	 */
	private function logon( WflLogOnRequest $request, $stepInfo, $expError = null )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
		$this->ticket = $response->Ticket;
		return $response;
	}
	
	/**
	 * When the test user is logged in, do a logout request
	 */
	private function logoff()
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		$request = new WflLogOffRequest();
		$request->Ticket = $this->ticket;
		$request->SaveSettings = false;
		$stepInfo = 'Logoff TESTSUITE user from Enterprise Server.';
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * Test import user with encrypted password
	 *
	 * @param AdmUser $oriUser
	 */
	private function testImportUserWithEncryptedPassword( $oriUser )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';

		$stepInfo = 'Import user with encrypted password.';
		$oriUser->Id	= null;
		$oriUser->Name	= $oriUser->Name . '_Imported';
		$oriUser->FullName = $oriUser->Name;
		$request = new AdmCreateUsersRequest();

		$request->Ticket 		= $this->ticket;
		$request->RequestModes 	= array();
		$request->Users 		= array( $oriUser );
		$response = $this->utils->callService( $this, $request, $stepInfo );
		$this->collectUsers( @$response->Users );
		if( $response && count($response->Users) ) {
			$newUser = $response->Users[0];
			if( $newUser->EncryptedPassword != $oriUser->EncryptedPassword ) {
				$this->setResult( 'ERROR', '<b>Test: </b>'.$stepInfo.'<br/>'.
					'<b> The encrypted password for the imported user differs from the original source user.</b>', '' );
			}
		}
	}
	/**
	 * Attempts to delete usergroups
	 */
	private function testDeleteUserGroups()
	{
		//Load the last user and usergroup
		$userId = $this->getUserId();
		$groupId = $this->getUserGroupId();

		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteUserGroupsService.class.php';
		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';

		//First add the user to the test usergroup
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds = array( $userId );
		$request->GroupId = $groupId;
		$stepInfo = 'Add users to test group.';
		$this->utils->callService( $this, $request, $stepInfo );

		//Delete the usergroup
		$request = new AdmDeleteUserGroupsRequest();
		$request->Ticket = $this->ticket;
		$request->GroupIds = array( $groupId );
		$stepInfo = 'Delete an usergroup.';
		$this->utils->callService( $this, $request, $stepInfo );

		//Try to retrieve it, this should fail
		$request = new AdmGetUserGroupsRequest();
		$request->Ticket = $this->ticket;
		$request->GroupIds = array( $groupId );
		$request->RequestModes = array();
		$stepInfo = 'Retrieve an usergroup.';
		$this->utils->callService( $this, $request, $stepInfo, '(S1056)');

		//Get the user and his usergroups, the usergroup should not be there
		$request = new AdmGetUsersRequest();
		$request->Ticket = $this->ticket;
		$request->UserIds = array( $userId );
		//Make sure we get the groups
		$request->RequestModes = array("GetUserGroups");
		$stepInfo = 'Retrieve the user that was in the group';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		foreach ($response->Users[0]->UserGroups as $group) {
			if ($group->Id == $groupId) {
				$this->setResult( 'ERROR', '<b>Test: </b>'.$stepInfo.'<br/>'.
					'<b>The user still has a reference to the deleted group.</b>', '' );
			}
		}
	}
}