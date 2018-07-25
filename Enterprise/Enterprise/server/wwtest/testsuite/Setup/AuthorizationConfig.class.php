<?php
/**
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This class makes it easy to setup users, groups, brand authorizations and access profiles.
 * Al it requires is a home brewed data structure (to be provided) to specify the authorization setup.
 * Functions are available to retrieve the DB ids of the created admin entities mentioned.
 * It offers a tear down function to delete everything it has created before.
 */
require_once BASEDIR.'/server/wwtest/testsuite/Setup/AbstractConfig.class.php';

class WW_TestSuite_Setup_AuthorizationConfig extends WW_TestSuite_Setup_AbstractConfig
{
	/** @var WW_TestSuite_Setup_PublicationConfig */
	private $publicationConfig;
	/** @var array */
	private $userNameIdMap;
	/** @var array */
	private $groupNameIdMap;
	/** @var array  */
	private $userGroupAuthorizationIds;
	/** @var array */
	private $accessProfileNameIdMap;

	/**
	 * @param WW_TestSuite_Setup_PublicationConfig $publicationConfig
	 */
	public function setPublicationConfig( WW_TestSuite_Setup_PublicationConfig $publicationConfig )
	{
		$this->publicationConfig = $publicationConfig;
	}

	/**
	 * @inheritdoc
	 */
	public function setupTestData()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

		if( isset( $this->config->Users ) ) {
			foreach( $this->config->Users as $userConfig ) {
				$user = new AdmUser();
				$this->copyConfigPropertiesToAdminClass( $userConfig, $user );
				$userId = $this->testSuiteUtils->createNewUser( $this->testCase, $this->ticket, $user );
				$this->userNameIdMap[ $userConfig->Name ] = $userId;
			}
		}
		if( isset( $this->config->UserGroups ) ) {
			foreach( $this->config->UserGroups as $groupConfig ) {
				$group = new AdmUserGroup();
				$this->copyConfigPropertiesToAdminClass( $groupConfig, $group );
				$groupId = $this->testSuiteUtils->createNewUserGroup( $this->testCase, $this->ticket, $group );
				$this->groupNameIdMap[ $groupConfig->Name ] = $groupId;
			}
		}
		if( isset( $this->config->Memberships ) ) {
			foreach( $this->config->Memberships as $memberConfig ) {
				$userId = $this->userNameIdMap[ $memberConfig->User ];
				$groupId = $this->groupNameIdMap[ $memberConfig->UserGroup ];
				$this->testCase->assertGreaterThan( 0, $userId,
					"While creating the Memberships from the JSON config setup, the User '{$memberConfig->User}' ".
					"could not be found under Users." );
				$this->testCase->assertGreaterThan( 0, $groupId,
					"While creating the Memberships from the JSON config setup, the UserGroup '{$memberConfig->UserGroup}' ".
					"could not be found under UserGroups." );
				$this->testSuiteUtils->createUserMemberships( $this->testCase, $this->ticket, $userId, $groupId );
			}
		}
		if( isset( $this->config->AccessProfiles ) ) {
			foreach( $this->config->AccessProfiles as $profileConfig ) {
				$accessProfile = new AdmAccessProfile();
				$accessProfile->Name = $this->replaceTimeStampPlaceholder( $profileConfig->Name );

				$profileFeatures = array();
				if( isset( $profileConfig->ProfileFeatures ) ) {
					foreach( $profileConfig->ProfileFeatures as $featureName ) {
						$profileFeature = new AdmProfileFeature();
						$profileFeature->Value = 'Yes';
						$profileFeature->Name = $featureName;
						$accessProfile->ProfileFeatures[] = $profileFeature;
					}
				}

				$accessProfile = $this->testSuiteUtils->createNewAccessProfile( $this->testCase, $this->ticket, $accessProfile );
				$this->accessProfileNameIdMap[ $profileConfig->Name ] = $accessProfile->Id;
			}
		}
		if( isset( $this->config->UserAuthorizations ) ) {
			foreach( $this->config->UserAuthorizations as $userAuthConfig ) {
				$pubId = $this->publicationConfig->getPublicationId( $userAuthConfig->Publication );
				$groupId = $this->groupNameIdMap[ $userAuthConfig->UserGroup ];
				$accessProfileId = $this->accessProfileNameIdMap[ $userAuthConfig->AccessProfile ];
				$this->testCase->assertGreaterThan( 0, $pubId,
					"While creating the UserAuthorizations from the JSON config setup, the Publication ".
					"'{$userAuthConfig->Publication}' could not be found under Publications." );
				$this->testCase->assertGreaterThan( 0, $groupId,
					"While creating the UserAuthorizations from the JSON config setup, the UserGroup ".
					"'{$userAuthConfig->UserGroup}' could not be found under UserGroups." );
				$this->testCase->assertGreaterThan( 0, $accessProfileId,
					"While creating the UserAuthorizations from the JSON config setup, the AccessProfile ".
					"'{$userAuthConfig->AccessProfile}' could not be found under AccessProfiles." );
				$this->userGroupAuthorizationIds[ $pubId ][ $groupId ] = $this->testSuiteUtils->createNewWorkflowUserGroupAuthorization(
					$this->testCase, $this->ticket, $pubId, null, $groupId, $accessProfileId, null, null );
			}
		}
		if( isset( $this->config->AdminAuthorizations ) ) {
			foreach( $this->config->AdminAuthorizations as $adminAuthConfig ) {
				$pubId = $this->publicationConfig->getPublicationId( $adminAuthConfig->Publication );
				$groupId = $this->groupNameIdMap[ $adminAuthConfig->UserGroup ];
				$this->testCase->assertGreaterThan( 0, $pubId,
					"While creating the AdminAuthorizations from the JSON config setup, the Publication ".
					"'{$adminAuthConfig->Publication}' could not be found under Publications." );
				$this->testCase->assertGreaterThan( 0, $groupId,
					"While creating the AdminAuthorizations from the JSON config setup, the UserGroup ".
					"'{$adminAuthConfig->UserGroup}' could not be found under UserGroups." );
				$this->testSuiteUtils->createNewPublicationAdminAuthorization( $this->testCase, $this->ticket, $pubId, $groupId );
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function teardownTestData()
	{
		if( isset( $this->config->AdminAuthorizations ) ) {
			foreach( $this->config->AdminAuthorizations as $adminAuthConfig ) {
				$pubId = $this->publicationConfig->getPublicationId( $adminAuthConfig->Publication );
				$groupId = $this->groupNameIdMap[ $adminAuthConfig->UserGroup ];
				$this->testSuiteUtils->removePublicationAdminAuthorization( $this->testCase, $this->ticket, $pubId, $groupId );
			}
		}
		if( isset( $this->config->UserAuthorizations ) ) {
			foreach( $this->config->UserAuthorizations as $userAuthConfig ) {
				$pubId = $this->publicationConfig->getPublicationId( $userAuthConfig->Publication );
				$groupId = $this->groupNameIdMap[ $userAuthConfig->UserGroup ];
				$userGroupAuthorizationId = $this->userGroupAuthorizationIds[ $pubId ][ $groupId ];
				$this->testSuiteUtils->deleteWorkflowUserGroupAuthorizations( $this->testCase, $this->ticket, null, null,
					null, array( $userGroupAuthorizationId ) );
			}
		}
		foreach( $this->accessProfileNameIdMap as  $accessProfileId ) {
			$this->testSuiteUtils->deleteAccessProfiles( $this->testCase, $this->ticket, array( $accessProfileId ) );
		}
		// Note that Memberships are removed implicitly when removing users or groups.
		foreach( $this->userNameIdMap as $userId ) {
			$this->testSuiteUtils->deleteUsers( $this->testCase, $this->ticket, array( $userId ) );
		}
		foreach( $this->groupNameIdMap as $groupId ) {
			$this->testSuiteUtils->deleteUserGroups( $this->testCase, $this->ticket, array( $groupId ) );
		}
	}

	/**
	 * @param string $userConfigName
	 * @return integer
	 */
	public function getUserId( $userConfigName )
	{
		return $this->userNameIdMap[ $userConfigName ];
	}

	/**
	 * @param string $userConfigName
	 * @return string
	 */
	public function getUserShortName( $userConfigName )
	{
		return $this->replaceTimeStampPlaceholder( $userConfigName );
	}

	/**
	 * @param string $groupConfigName
	 * @return integer
	 */
	public function getUserGroupId( $groupConfigName )
	{
		return $this->groupNameIdMap[ $groupConfigName ];
	}

	/**
	 * @param string $profileConfigName
	 * @return integer
	 */
	public function getAccessProfileId( $profileConfigName )
	{
		return $this->accessProfileNameIdMap[ $profileConfigName ];
	}
}