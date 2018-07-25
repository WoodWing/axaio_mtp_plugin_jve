<?php
/**
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmWorkflowUserGroupAuthorizations_TestCase extends TestCase
{
	public function getDisplayName() { return 'Workflow user group authorizations'; }
	public function getTestGoals() { return 'Checks if workflow user group authorizations can be round-tripped and deleted successfully.'; }
	public function getTestMethods() {
		return 'Setup test data: Create profiles, users and groups to play with. Logon brand admin and workflow users.<ol>
					<li>Pre-configured variables: System user SU and brand B. (As configured in TESTSUITE option, created in setup of TestSuite.)</li>
					<li>SU: Create Access Profiles P1 and P2.</li>
					<li>SU: Add [Read, Write, View and Edit] Profile Features to Access Profile P1.</li>
					<li>SU: Add [Delete] Profile Feature to Access Profile P2.</li>
					<li>SU: Create a User Group AG (without system admin option).</li>
					<li>SU: Create a brand admin User AU and make AU member of User Group AG.</li>
					<li>SU: Add User Group AG to Admin Authorizations of Brand B.</li>
					<li>AU: LogOn user AU and check if brand B is listed in response.</li>
					<li>SU: Create a User Group WG (without system admin option).</li>
					<li>SU: Create a workflow User WI and make WU member of User Group WG.</li>
					<li>AU: Add User Group to User Authorizations of Brand B.</li>
					<li>WU: LogOn user WU and check if Brand B is listed in response.</li>
					<li>AU: Create Statuses S1 and S2 for a Hyperlink.</li>
					<li>AU: Create a Category C1 under Brand B.</li>
				</ol>
				Scenario 1: Allow editing hyperlink but no deletion. Add the Delete feature and delete hyperlink. <ol>
					<li>AU: Create an Authorization Rule R1 with [Category C1 - Access Profile P1 - Status S1] for [Brand B - User Group WG].</li>
					<li>WU: Create a Hyperlink H1 in Brand B, Category C1 and Status S1.</li>
					<li>WU: Lock the Hyperlink H1 for editing.</li>
					<li>WU: Unlock Hyperlink H1.</li>
					<li>WU: Try to delete the Hyperlink H1. Expected: access denied error.</li>
					<li>AU: Add the Delete rights to Access Profile P1.</li>
					<li>WU: Delete the Hyperlink H1 again</li>
					<li>AU: Delete Authorization Rule R1.</li>
					<li>SU: Remove the Delete rights from Access Profile P1.</li>
				</ol>
				Scenario 2: Allow editing Hyperlink in one Status, but not another. Change the Status at Authorization and edit Hyperlink.<ol>
					<li>AU: Create a new Authorization Rule R2 with [Category C1 - Access Profile P1 - Status S1] for [Brand B - User Group WG].</li>
					<li>AU: Create a new Authorization Rule R3 with [Category &lt;All&gt; - Access Profile P2 - Status &lt;All&gt;] for [Brand B - User Group WG].</li>
					<li>AU: Create an Hyperlink H2 in Brand B, Category C1 and Status S1.</li>
					<li>AU: Create an Hyperlink H3 in Brand B, Category C1 and Status S2.</li>
					<li>WU: Lock Hyperlink H2 for editing.</li>
					<li>WU: Unlock Hyperlink H2.</li>
					<li>WU: Try to lock Hyperlink H3. Expected: access denied error.</li>
					<li>AU: Modify the Authorization Rule R2 by changing Status S1 into S2.</li>
					<li>WU: Try to lock Hyperlink H2. Expected: access denied error.</li>
					<li>WU: Lock Hyperlink H3.</li>
					<li>WU: Unlock Hyperlink H3.</li>
					<li>WU: Delete Hyperlinks H2 and H3. (Delete rights should be obtained through R3.)</li>
					<li>AU: Delete Authorization Rules R2 and R3.</li>
				</ol>
				Scenario 3: Editing article not allowed when user is not in authorized group or when group is not authorized to brand.<ol>
					<li>AU: Create a new Authorization Rule R4 with [Category &lt;All&gt; - Access Profile P1 - Status &lt;All&gt;] for [Brand B - User Group WG].</li>
					<li>WU: Create Hyperlink H4.</li>
					<li>SU: Remove the User WU from the User Group WG</li>
					<li>WU: Try to lock Hyperlink H4. Expected: access denied error.</li>
					<li>SU: Add the User WU back to the User Group WG.</li>
					<li>AU: Delete Authorization Rule R4.</li>
					<li>WU: Try to lock Hyperlink H4. Expected: access denied error.</li>
				</ol>
				Scenario 4: Validate cascade deletions of user authorizations when deleting status, category or profile.<ol>
					<li>SU: Create Access Profile P3.</li>
					<li>SU: Add the Profile Features [Read+Write] to P3.</li>
					<li>SU: Create Category C2 for Brand B.</li>
					<li>SU: Create Hyperlink Status S3 for Brand B.</li>
					<li>AU: Create a new Authorization Rule R5 with [Category C2 - Access Profile P3 - Status &lt;All&gt;] for [Brand B - User Group WG].</li>
					<li>AU: Create a new Authorization Rule R6 with [Category &lt;All&gt; - Access Profile P3 - Status S3] for [Brand B - User Group WG].</li>
					<li>AU: Create a new Authorization Rule R7 with [Category &lt;All&gt; - Access Profile P3 - &lt;All&gt;] for [Brand B - User Group WG].</li>
					<li>SU: Delete Category C2. Check if R5 is cascade deleted.</li>
					<li>SU: Delete Status S3. Check if R6 is cascade deleted.</li>
					<li>SU: Delete Access Profile P3. Check if R7 is cascade deleted. Check if Profile Features [Read+Write] are cascade deleted.</li>
				</ol>
				Tear down test data:<ol>
					<li>WU: LogOff.</li>
					<li>AU: LogOff.</li>
					<li>SU: Delete Access Profiles P1 and P2.</li>
					<li>SU: Delete Users WU and AU.</li>
					<li>SU: Delete User Groups WG and AG.</li>
				</ol>';
	}
	public function getPrio() { return 170; }
	public function isSelfCleaning() { return true; }

	/** @var WW_Utils_PhpCompare $compare */
	private $compare = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	/** @var string $ticket The system admin ticket of the user created during initialisation of test data. */
	private $ticket = null;
	/** @var integer $publicationId The publication id of the publication created during initialisation of test data. */
	private $publicationId = null;
	/** @var integer $pubChannelId The publication channel id created during initialisation of test data. */
	private $pubChannelId = null;
	/** @var integer $postfix Counter to ensure unique objects names. */
	private $postfix = 0;

	/** @var AdmUser $admUser The brand admin user. */
	private $admUser = null;
	/** @var integer $admUserGroupId User group of the brand admin user. */
	private $admUserGroupId = null;
	/** @var string $admTicket Ticket of the brand admin user. */
	private $admTicket = null;
	/** @var AdmWorkflowUserGroupAuthorization $admUserAuthRule Authentication rule of the brand admin user, giving basic permissions used in tests. */
	private $admUserAuthRule = null;

	/** @var AdmUser $wflUser The workflow user. */
	private $wflUser = null;
	/** @var integer $wflUserGroupId User group of the workflow user. */
	private $wflUserGroupId = null;
	/** @var string $wflTicket Ticket of the workflow user. */
	private $wflTicket = null;

	/** @var AdmAccessProfile $accessProfile1 An access profile containing Read, Write, Edit and View rights. */
	private $accessProfile1 = null;
	/** @var AdmAccessProfile $accessProfile2 An access profile containing Delete rights. */
	private $accessProfile2 = null;
	/** @var integer $sectionId The id of the section of the publication. */
	private $sectionId = null;

	/** @var integer $statusId1 The id of a status with the Hyperlink type. */
	private $statusId1 = null;
	/** @var integer $statusId2 The id of another status with the Hyperlink type. */
	private $statusId2 = null;

	/** @var integer $sparePubId The id of a second publication, used for some bad attempt tests. */
	private $sparePubId = null;
	/** @var integer $overruleIssueId An overrule issue created within the TESTSUITE publication. */
	private $overruleIssueId = null;
	/** @var integer $spareIssueId A regular issue created within the TESTSUITE publication. */
	private $spareIssueId = null;

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
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';

		// Because we dynamically create users / groups / authorizations, we can not rely on the current PHP session
		// that runs this test script and simply call services directly. Instead, we use a JSON client to create a new
		// PHP session at the core server to initialize the authorization cache for the acting user (per web service call).
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		$this->compare = new WW_Utils_PhpCompare();
		$this->compare->initCompare(
			array( 'AdmWorkflowUserGroupAuthorization->SectionId' => true,
				'AdmWorkflowUserGroupAuthorization->StatusId' => true ) );

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
			if( !$this->scenario003() ) {
				break;
			}
			if( !$this->scenario004() ) {
				break;
			}
			if( !$this->testBadAttemptsCreateService() ) {
				break;
			}
			if( !$this->testBadAttemptsModifyService() ) {
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
	 * Create profiles, users, groups, publications, issues, sections and
	 * statuses to play with. Logon brand admin and workflow users.
	 *
	 * @return bool True if no errors, false otherwise.
	 * @throws BizException If logged in user is not part of the brand.
	 */
	private function setUpTestData()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

		$this->accessProfile1 = $this->createAccessProfile();
		$this->modifyProfileFeaturesOfProfile( $this->accessProfile1, array( 'View', 'Read', 'Write', 'Open_Edit' ), false );
		$this->accessProfile2 = $this->createAccessProfile();
		$this->modifyProfileFeaturesOfProfile( $this->accessProfile2, array( 'Delete', 'Purge', 'Read' ), false );

		$this->admUserGroupId = $this->createUserGroup( true );
		$this->admUser = $this->createUser();

		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->admUserGroupId;
		$request->UserIds = array( $this->admUser->Id );
		$this->utils->callService( $this, $request, 'Setup: Add admUser to admUserGroup.' );

		$this->admUserAuthRule = $this->createWflUGAuth( $this->ticket, $this->admUserGroupId, $this->accessProfile1->Id, null, null );

		//Add the user to the brand as a brand admin
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';
		$request = new AdmCreatePublicationAdminAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->UserGroupIds = array( $this->admUserGroupId );
		$this->utils->callService( $this, $request, 'Setup: Authorize the admUser as brand admin.' );

		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User = $this->admUser->Name;
		$request->Password = $this->admUser->Password;
		$request->ClientName = 'WorkflowUserGroupAuthorization build test';
		$request->ClientAppName = 'Web';
		$request->ClientAppVersion = SERVERVERSION;
		$response = $this->utils->callService( $this, $request, 'Log on the admUser.' );
		$this->admTicket = $response->Ticket;
		$success = false;
		foreach( $response->Publications as $publicationInfo ) {
			if( $publicationInfo->Id == $this->publicationId ) {
				$success = true;
				break;
			}
		}
		if( !$success ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'The admUser is not authorized to the brand.' );
		}

		$this->wflUserGroupId = $this->createUserGroup( false );
		$this->wflUser = $this->createUser();

		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->wflUserGroupId;
		$request->UserIds = array( $this->wflUser->Id );
		$this->utils->callService( $this, $request, 'Setup: Add wflUser to wflUserGroup.' );

		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User = $this->wflUser->Name;
		$request->Password = $this->wflUser->Password;
		$request->ClientName = 'WorkflowUserGroupAuthorization build test';
		$request->ClientAppName = 'Web';
		$request->ClientAppVersion = SERVERVERSION;
		$response = $this->utils->callService( $this, $request, 'Log on the wflUser.' );
		$this->wflTicket = $response->Ticket;

		$this->sectionId = $this->createSection( true );
		$this->statusId1 = $this->createStatus( 'Hyperlink', $this->publicationId );
		$this->statusId2 = $this->createStatus( 'Hyperlink', $this->publicationId );
		$this->overruleIssueId = $this->createIssue( true );
		$this->spareIssueId = $this->createIssue( false );
		$this->sparePubId = $this->createPublication();

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Allow editing article but no deletion. Add the Delete
	 * feature and delete article.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function scenario001()
	{
		$rule1 = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );
		$hyperlinkId = $this->createHyperlink( $this->wflTicket, $this->publicationId, $this->sectionId, $this->statusId1, true );

		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->IDs = array( $hyperlinkId );
		$this->utils->callService( $this, $request, 'Scenario001: WflUser unlocks the Hyperlink Object.' );

		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Permanent = true;
		$request->IDs = array( $hyperlinkId );
		$this->utils->callService( $this, $request, 'Scenario001: WflUser attempts to delete the Hyperlink Object.', '(S1002)' );

		//Admin user adds the delete profile feature to the access profile
		$this->modifyProfileFeaturesOfProfile( $this->accessProfile1, array( 'Delete', 'Purge' ), false );

		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Permanent = true;
		$request->IDs = array( $hyperlinkId );
		$this->utils->callService( $this, $request, 'Scenario001: WflUser deletes the Hyperlink Object.' );

		$this->deleteWflUGAuth( array( $rule1->Id ) );

		//Return the used access profile to its original state
		$this->modifyProfileFeaturesOfProfile( $this->accessProfile1, array( 'Delete', 'Purge' ), true );

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Allow editing article in one status, but not another.
	 * Change the status at authorization and edit article.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function scenario002()
	{
		//setup
		$rule1 = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );
		$rule2 = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $this->accessProfile2->Id, null, null );

		$hyperlinkId2 = $this->createHyperlink( $this->admTicket, $this->publicationId, $this->sectionId, $this->statusId1 );
		$hyperlinkId3 = $this->createHyperlink( $this->admTicket, $this->publicationId, $this->sectionId, $this->statusId2 );

		//lock hyperlink2
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->IDs = array( $hyperlinkId2 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser locks Hyperlink2.' );

		//unlock hyperlink2
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->IDs = array( $hyperlinkId2 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser unlocks Hyperlink2.' );

		//lock hyperlink3 (access denied)
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->IDs = array( $hyperlinkId3 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser locks Hyperlink3.', '(S1002)' );

		//change status of rule
		$rule1->StatusId = $this->statusId2;
		require_once BASEDIR.'/server/services/adm/AdmModifyWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmModifyWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->admTicket;
		$request->PublicationId = $this->publicationId;
		$request->WorkflowUserGroupAuthorizations = array( $rule1 );
		$response = $this->utils->callService( $this, $request, 'Scenario002: Modify the status of an authorization id.' );
		$this->validateWorkflowUserGroupAuthorizationResponse( $rule1, $response->WorkflowUserGroupAuthorizations[0], 'modify' );

		//lock hyperlink2 (access denied)
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->IDs = array( $hyperlinkId2 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser locks Hyperlink2.', '(S1002)' );

		//lock hyperlink3
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->IDs = array( $hyperlinkId3 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser locks Hyperlink3.' );

		//unlock hyperlink3
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->IDs = array( $hyperlinkId3 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser unlocks Hyperlink3.' );

		//delete the hyperlink objects
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Permanent = true;
		$request->IDs = array( $hyperlinkId3 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser deletes the used Hyperlink3.' );

		//delete the hyperlink objects
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Permanent = true;
		$request->IDs = array( $hyperlinkId2 );
		$this->utils->callService( $this, $request, 'Scenario002: WflUser deletes the used Hyperlink2.' );

		$this->deleteWflUGAuth( array( $rule1->Id, $rule2->Id ) );

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Editing article not allowed when user is not in
	 * authorized group or when group is not authorized to brand.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function scenario003()
	{
		$rule1 = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $this->accessProfile1->Id, null, null );
		$hyperlinkId = $this->createHyperlink( $this->wflTicket, $this->publicationId, $this->sectionId, $this->statusId1 );

		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';
		$request = new AdmRemoveUsersFromGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->wflUserGroupId;
		$request->UserIds = array( $this->wflUser->Id );
		$this->utils->callService( $this, $request, 'Scenario003: Remove the wflUser from the wflUserGroup.' );

		//lock articleObj1 > access denied
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->IDs = array( $hyperlinkId );
		$this->utils->callService( $this, $request, 'Scenario003: WflUser locks Hyperlink when not in user group.', '(S1002)' );

		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->wflUserGroupId;
		$request->UserIds = array( $this->wflUser->Id );
		$this->utils->callService( $this, $request, 'Scenario003: Re-add the wflUser from the wflUserGroup.' );

		$this->deleteWflUGAuth( array( $rule1->Id ) );

		//lock articleObj1 (access denied)
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->IDs = array( $hyperlinkId );
		$this->utils->callService( $this, $request, 'Scenario003: WflUser locks Hyperlink without proper authorization.', '(S1002)' );

		//give user authorization to delete
		$tempRule = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $this->accessProfile2->Id, null, null );

		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Permanent = true;
		$request->IDs = array( $hyperlinkId );
		$this->utils->callService( $this, $request, 'Scenario003: WflUser deletes the used article object.' );

		$this->deleteWflUGAuth( array( $tempRule->Id ) );

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Validate cascade deletions of user authorizations when
	 * deleting status, category or profile.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function scenario004()
	{
		$accessProfile = $this->createAccessProfile();
		$this->modifyProfileFeaturesOfProfile( $accessProfile, array( 'Read', 'Write' ), false );
		$sectionId = $this->createSection( true );
		$statusId = $this->createStatus( 'Hyperlink', $this->publicationId );
		$rule1 = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $accessProfile->Id, $sectionId, null );
		$rule2 = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $accessProfile->Id, null, $statusId );
		$rule3 = $this->createWflUGAuth( $this->admTicket, $this->wflUserGroupId, $accessProfile->Id, null, null );

		require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
		$request = new AdmDeleteSectionsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->SectionIds = array( $sectionId );
		$this->utils->callService( $this, $request, 'Scenario004: Delete section for cascade test.' );

		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmGetWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->WorkflowUserGroupAuthorizationIds = array( $rule1->Id );
		$this->utils->callService( $this, $request, 'Scenario004: Get rule that should be cascade deleted.', '(S1056)' );

		require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';
		$request = new AdmDeleteStatusesRequest();
		$request->Ticket = $this->ticket;
		$request->StatusIds = array( $statusId );
		$this->utils->callService( $this, $request, 'Scenario004: Delete status for cascade test.' );

		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmGetWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->WorkflowUserGroupAuthorizationIds = array( $rule2->Id );
		$this->utils->callService( $this, $request, 'Scenario004: Get rule that should be cascade deleted.', '(S1056)' );

		require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
		$request = new AdmDeleteAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->AccessProfileIds = array( $accessProfile->Id );
		$this->utils->callService( $this, $request, 'Scenario004: Delete access profile for cascade test.' );

		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmGetWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->WorkflowUserGroupAuthorizationIds = array( $rule3->Id );
		$this->utils->callService( $this, $request, 'Scenario004: Get rule that should be cascade deleted.', '(S1056)' );

		return $this->hasError() ? false : true;
	}

	/**
	 * Test the create service with bad attempt scenarios.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsCreateService()
	{
		//setup
		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmCreateWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 9; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1, '100' );
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization object has an id.';
					$expError = '(S1000)';
					break;
				case 2:
					$tempExistingWflUGAuth = $this->createWflUGAuth( $this->ticket, $this->wflUserGroupId, $this->accessProfile2->Id, $this->sectionId, $this->statusId2 );
					$wflUGAuthIds[] = $tempExistingWflUGAuth->Id;
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile2->Id, $this->sectionId, $this->statusId2 );
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization rule that already exists. (Matching combination of ids.)';
					$expError = '(S1038)';
					break;
				case 3:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );
					$request->PublicationId = PHP_INT_MAX-1;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 4:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX-1;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with a non-existing issue id.';
					$expError = '(S1056)';
					break;
				case 5:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, PHP_INT_MAX-1, $this->statusId1 );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with a non-existing category id.';
					$expError = '(S1056)';
					break;
				case 6:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, PHP_INT_MAX-1 );
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with a non-existing status id.';
					$expError = '(S1056)';
					break;
				case 7:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, PHP_INT_MAX-1, $this->sectionId, $this->statusId1 );
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with a non-existing access profile id.';
					$expError = '(S1056)';
					break;
				case 8:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 9:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = $this->spareIssueId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
				/*case 10:
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 11:
					$stepInfo = 'Create a WorkflowUserGroupAuthorization with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response ) {
				$wflUGAuthIds[] = $response->WorkflowUserGroupAuthorizations[0]->Id;
			}
		}
		//tear down
		if( !empty( $wflUGAuthIds ) ) {
			$this->deleteWflUGAuth( $wflUGAuthIds );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Test the modify service with bad attempt scenarios.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsModifyService()
	{
		//setup
		$wflUGAuth = $this->createWflUGAuth( $this->ticket, $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId2 );
		$wflUGAuthId = $wflUGAuth->Id;

		require_once BASEDIR.'/server/services/adm/AdmModifyWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmModifyWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 9; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$wflUGAuth->Id = PHP_INT_MAX - 1;
					$wflUGAuth->AccessProfileId = $this->accessProfile2->Id;
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization object with a non-existing id.';
					$expError = '(S1056)';
					break;
				case 2:
					$tempExistingWflUGAuth = $this->createWflUGAuth( $this->ticket, $this->wflUserGroupId, $this->accessProfile2->Id, $this->sectionId, $this->statusId2 );
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile2->Id, $this->sectionId, $this->statusId2, $wflUGAuthId );
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization rule that already exists. (Matching combination of ids.)';
					$expError = '(S1038)';
					break;
				case 3:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId2, $wflUGAuthId );
					$request->PublicationId = PHP_INT_MAX - 1;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 4:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId2, $wflUGAuthId );
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX - 1;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with a non-existing issue id.';
					$expError = '(S1056)';
					break;
				case 5:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, PHP_INT_MAX - 1, $this->statusId2, $wflUGAuthId );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with a non-existing category id.';
					$expError = '(S1056)';
					break;
				case 6:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, PHP_INT_MAX - 1, $wflUGAuthId );
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with a non-existing status id.';
					$expError = '(S1056)';
					break;
				case 7:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, PHP_INT_MAX - 1, $this->sectionId, $this->statusId2, $wflUGAuthId );
					$request->PublicationId = $this->publicationId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with a non-existing access profile id.';
					$expError = '(S1056)';
					break;
				case 8:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId2, $wflUGAuthId );
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 9:
					$wflUGAuth = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId2, $wflUGAuthId );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = $this->spareIssueId;
					$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
				/*case 10:
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 11:
					$stepInfo = 'Modify a WorkflowUserGroupAuthorization with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		//tear down
		if( isset( $tempExistingWflUGAuth ) ) {
			$this->deleteWflUGAuth( array( $wflUGAuthId, $tempExistingWflUGAuth->Id ) );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Test the get service with bad attempt scenarios.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsGetService()
	{
		//setup
		$wflUGAuth1 = $this->createWflUGAuth( $this->ticket, $this->wflUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );
		$wflUGAuth2 = $this->createWflUGAuth( $this->ticket, $this->wflUserGroupId, $this->accessProfile1->Id, null, $this->statusId1 );
		$wflUGAuth3 = $this->createWflUGAuth( $this->ticket, $this->wflUserGroupId, $this->accessProfile1->Id, null, null );
		$wflUGAuthIds = array( $wflUGAuth1->Id, $wflUGAuth2->Id, $wflUGAuth3->Id );

		//create a rule in a different brand
		$wflUGAuth4 = $this->buildWflUGAuthObject( $this->wflUserGroupId, $this->accessProfile1->Id, null, null );
		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmCreateWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->sparePubId;
		$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth4 );
		$response = $this->utils->callService( $this, $request, 'Create a WorkflowUserGroupAuthorization rule.' );
		$wflUGAuth4 = $response->WorkflowUserGroupAuthorizations[0];

		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmGetWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		for( $i = 1; $i <= 7; $i++ ) {
			$testWflUGAuthIds = $wflUGAuthIds;
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$stepInfo = 'Get WorkflowUserGroupAuthorizations with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX-1;
					$stepInfo = 'Get WorkflowUserGroupAuthorizations with a non-existing issue id.';
					$expError = '(S1056)';
					break;
				case 3:
					$request->IssueId = null;
					$request->WorkflowUserGroupAuthorizationIds = array_merge( $testWflUGAuthIds, array( PHP_INT_MAX-1 ) );
					$stepInfo = 'Get WorkflowUserGroupAuthorizations providing a non-existing id.';
					$expError = '(S1056)';
					break;
				case 4:
					$request->WorkflowUserGroupAuthorizationIds = array_merge( $testWflUGAuthIds, array( $wflUGAuth4->Id ) );
					$stepInfo = 'Get WorkflowUserGroupAuthorizations providing ids of rules that do not belong to the given brand id.';
					$expError = '(S1000)';
					break;
				case 5:
					$request->WorkflowUserGroupAuthorizationIds = array_merge( $testWflUGAuthIds, array( 'id' ) );
					$stepInfo = 'Get WorkflowUserGroupAuthorizations providing a non-numeric id.';
					$expError = '(S1000)';
					break;
				case 6:
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$stepInfo = 'Get WorkflowUserGroupAuthorizations providing a brand that is not the brand that the issue id overrules.';
					$expError = '(S1000)';
					break;
				case 7:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = $this->spareIssueId;
					$stepInfo = 'Get WorkflowUserGroupAuthorizations providing an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
				/*case 8:
					$stepInfo = 'Get one or more WorkflowUserGroupAuthorizations with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 9:
					$stepInfo = 'Get one or more WorkflowUserGroupAuthorizations with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		//tear down
		$this->deleteWflUGAuth( $wflUGAuthIds + array( $wflUGAuth4->Id ) );
		return $this->hasError() ? false : true;
	}

	/**
	 * Test the delete service with bad attempt scenarios.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsDeleteService()
	{
		//setup
		$wflUGAuth1 = $this->createWflUGAuth( $this->ticket, $this->admUserGroupId, $this->accessProfile1->Id, $this->sectionId, $this->statusId1 );

		//create a rule in a different brand
		$wflUGAuth2 = $this->buildWflUGAuthObject( $this->admUserGroupId, $this->accessProfile1->Id, null, null );
		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmCreateWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->sparePubId;
		$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth2 );
		$response = $this->utils->callService( $this, $request, 'Create a WorkflowUserGroupAuthorization rule.' );
		$wflUGAuth2 = $response->WorkflowUserGroupAuthorizations[0];

		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmDeleteWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 6; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$stepInfo = 'Delete WorkflowUserGroupAuthorizations with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX-1;
					$stepInfo = 'Delete WorkflowUserGroupAuthorizations with a non-existing issue id.';
					$expError = '(S1056)';
					break;
				case 3:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->WorkflowUserGroupAuthorizationIds = array( $wflUGAuth1->Id, $wflUGAuth2->Id );
					$stepInfo = 'Delete WorkflowUserGroupAuthorizations providing both filter ids and authorization ids.';
					$expError = '(S1000)';
					break;
				case 4:
					$request->PublicationId = null;
					$request->WorkflowUserGroupAuthorizationIds = array( $wflUGAuth1->Id, 'id' );
					$stepInfo = 'Delete WorkflowUserGroupAuthorizations providing a non-numeric id.';
					$expError = '(S1000)';
					break;
				case 5:
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$stepInfo = 'Delete WorkflowUserGroupAuthorizations providing a brand that is not the brand that the issue id overrules.';
					$expError = '(S1000)';
					break;
				case 6:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = $this->spareIssueId;
					$stepInfo = 'Delete WorkflowUserGroupAuthorizations providing an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
				/*case 7:
					$stepInfo = 'Delete one or more WorkflowUserGroupAuthorizations with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 8:
					$stepInfo = 'Delete one or more WorkflowUserGroupAuthorizations with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		//tear down, needs to be split since they are in different brands
		$this->deleteWflUGAuth( array( $wflUGAuth1->Id ) );
		$this->deleteWflUGAuth( array( $wflUGAuth2->Id ) );

		return $this->hasError() ? false : true;
	}

	/**
	 * Tear down the test data.
	 *
	 * Delete the profiles, users, groups, sections and statuses that were
	 * created in setup. Log off brand admin and workflow users.
	 */
	private function tearDownTestData()
	{
		if( isset( $this->admTicket ) ) {
			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = $this->admTicket;
			$this->utils->callService( $this, $request, 'Log off admUser.' );
			unset( $this->admTicket );
		}
		if( isset( $this->wflTicket ) ) {
			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = $this->wflTicket;
			$this->utils->callService( $this, $request, 'Log off wflUser.' );
			unset( $this->wflTicket );
		}
		if( isset( $this->admUser ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
			$request = new AdmDeleteUsersRequest();
			$request->Ticket = $this->ticket;
			$request->UserIds = array( $this->admUser->Id );
			$this->utils->callService( $this, $request, 'TearDown: Delete AdmUser.' );
			unset( $this->admUser );
		}
		if( isset( $this->wflUser ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
			$request = new AdmDeleteUsersRequest();
			$request->Ticket = $this->ticket;
			$request->UserIds = array( $this->wflUser->Id );
			$this->utils->callService( $this, $request, 'TearDown: Delete WflUser.' );
			unset( $this->wflUser );
		}
		if( isset( $this->admUserGroupId) ) {
			//TODO: Should call DeleteUserGroupsService, in the future
			require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
			BizAdmUser::deleteUserGroup( $this->admUserGroupId );
			LogHandler::Log( 'WflUGAuths_TestCase', 'INFO', 'TearDown: Delete AdmUserGroup' );
			unset( $this->admUserGroupId );
		}
		if( isset( $this->wflUserGroupId ) ) {
			//TODO: Should call DeleteUserGroupsService, in the future
			require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
			BizAdmUser::deleteUserGroup( $this->wflUserGroupId );
			LogHandler::Log( 'WflUGAuths_TestCase', 'INFO', 'TearDown: Delete WflUserGroup' );
			unset( $this->wflUserGroupId );
		}
		if( isset( $this->accessProfile1 ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
			$request = new AdmDeleteAccessProfilesRequest();
			$request->Ticket = $this->ticket;
			$request->AccessProfileIds = array( $this->accessProfile1->Id );
			$this->utils->callService( $this, $request, 'TearDown: Delete AccessProfile1.' );
			unset( $this->accessProfile1 );
		}
		if( isset( $this->accessProfile2 ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
			$request = new AdmDeleteAccessProfilesRequest();
			$request->Ticket = $this->ticket;
			$request->AccessProfileIds = array( $this->accessProfile2->Id );
			$this->utils->callService( $this, $request, 'TearDown: Delete AccessProfile2.' );
			unset( $this->accessProfile2 );
		}
		if( isset( $this->statusId1 ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';
			$request = new AdmDeleteStatusesRequest();
			$request->Ticket = $this->ticket;
			$request->StatusIds = array( $this->statusId1 );
			$this->utils->callService( $this, $request, 'TearDown: Delete Status1.' );
			unset( $this->statusId1 );
		}
		if( isset( $this->statusId2 ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';
			$request = new AdmDeleteStatusesRequest();
			$request->Ticket = $this->ticket;
			$request->StatusIds = array( $this->statusId2 );
			$this->utils->callService( $this, $request, 'TearDown: Delete Status2.' );
			unset( $this->statusId2 );
		}
		if( isset( $this->sectionId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
			$request = new AdmDeleteSectionsRequest();
			$request->Ticket = $this->ticket;
			$request->PublicationId = $this->publicationId;
			$request->IssueId = 0;
			$request->SectionIds = array( $this->sectionId );
			$this->utils->callService( $this, $request, 'TearDown: Delete Section1.' );
			unset( $this->sectionId );
		}
		if( isset( $this->overruleIssueId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
			$request = new AdmDeleteIssuesRequest();
			$request->Ticket = $this->ticket;
			$request->PublicationId = $this->publicationId;
			$request->IssueIds = array( $this->overruleIssueId );
			$this->utils->callService( $this, $request, 'TearDown: Delete overrule issue.' );
			unset( $this->overruleIssueId );
		}
		if( isset( $this->spareIssueId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
			$request = new AdmDeleteIssuesRequest();
			$request->Ticket = $this->ticket;
			$request->PublicationId = $this->publicationId;
			$request->IssueIds = array( $this->spareIssueId );
			$this->utils->callService( $this, $request, 'TearDown: Delete spare issue.' );
			unset( $this->spareIssueId );
		}
		if( isset( $this->sparePubId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
			$request = new AdmDeletePublicationsRequest();
			$request->Ticket = $this->ticket;
			$request->PublicationIds = array( $this->sparePubId );
			$this->utils->callService( $this, $request, 'TearDown: Delete spare publication.' );
			unset( $this->sparePubId );
		}
		if( isset( $this->wflUGAuthIds ) ) {
			if( count( $this->wflUGAuthIds ) > 0 ) {
				require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';
				$request = new AdmDeleteWorkflowUserGroupAuthorizationsRequest();
				$request->Ticket = $this->ticket;
				$request->PublicationId = $this->publicationId;
				$request->WorkflowUserGroupAuthorizationIds = $this->wflUGAuthIds;
			}
			unset( $this->wflUGAuthIds );
		}

		unset( $this->postfix );
		unset( $this->utils );
		unset( $this->compare );
		unset( $this->publicationId );
		unset( $this->ticket );
	}

	/**
	 * Compares a response to the original object to validate it.
	 *
	 * An error is set if the objects that are compared are not equal.
	 *
	 * @param AdmWorkflowUserGroupAuthorization $baseObj The original object.
	 * @param AdmWorkflowUserGroupAuthorization $responseObj The object returned in the response.
	 * @param string $operation The type of service that the comparison is for.
	 */
	private function validateWorkflowUserGroupAuthorizationResponse( $baseObj, $responseObj, $operation )
	{
		if( is_null( $responseObj ) ) {
			$this->setResult( 'ERROR', 'Invalid response was returned.', 'No response found for ' . $operation . ' service.' );
		} else {
			$result = $this->compare->compareTwoObjects( $baseObj, $responseObj );
			if( !$result ) {
				$this->setResult( 'ERROR', implode( PHP_EOL, $this->compare->getErrors() ), 'Error occurred in '.$operation.' response.');
			}
		}
	}

	/************************** UTIL FUNCTIONS **************************/

	/**
	 * Build a WorkflowUserGroupAuthorization object and return it.
	 *
	 * @param integer $userGroupId The group id of the user.
	 * @param integer $accessProfileId The access profile id.
	 * @param integer|null $sectionId The section id. (optional)
	 * @param integer|null $statusId The status id. (optional)
	 * @param integer|null $id The WorkflowUserGroupAuthorization id. (optional)
	 * @return AdmWorkflowUserGroupAuthorization
	 */
	private function buildWflUGAuthObject( $userGroupId, $accessProfileId, $sectionId, $statusId, $id = null )
	{
		$wflUGAuth = new AdmWorkflowUserGroupAuthorization();
		$wflUGAuth->Id = $id;
		$wflUGAuth->UserGroupId = $userGroupId;
		$wflUGAuth->AccessProfileId = $accessProfileId;
		$wflUGAuth->SectionId = $sectionId;
		$wflUGAuth->StatusId = $statusId;

		return $wflUGAuth;
	}

	/**
	 * Create a workflow user group authorization rule and return it.
	 *
	 * Before the rule is returned, the response is validated using a comparison function.
	 *
	 * @param string $ticket The session ticket.
	 * @param integer $userGroupId The group id of the user.
	 * @param integer $accessProfileId The access profile id.
	 * @param integer|null $sectionId The section id. (optional)
	 * @param integer|null $statusId The status id. (optional)
	 * @return AdmWorkflowUserGroupAuthorization
	 */
	private function createWflUGAuth( $ticket, $userGroupId, $accessProfileId, $sectionId, $statusId )
	{
		$wflUGAuth = new AdmWorkflowUserGroupAuthorization();
		$wflUGAuth->AccessProfileId = $accessProfileId;
		$wflUGAuth->UserGroupId = $userGroupId;
		$wflUGAuth->SectionId = $sectionId;
		$wflUGAuth->StatusId = $statusId;

		require_once BASEDIR . '/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmCreateWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $this->publicationId;
		$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
		$response = $this->utils->callService( $this, $request, 'Create a regular WorkflowUserGroupAuthorization.' );

		$responseWflUGAuth = $response->WorkflowUserGroupAuthorizations[0];
		$wflUGAuth->Id = $responseWflUGAuth->Id; //set id for the pre-create object for good comparison

		$this->validateWorkflowUserGroupAuthorizationResponse( $wflUGAuth, $responseWflUGAuth, 'CreateWorkflowUserGroupAuthorization');

		return $responseWflUGAuth;
	}

	/**
	 * Delete one or more workflow user group authorization rules.
	 *
	 * @param integer[] $wflUGAuthIds List of WorkflowUserGroupAuthorization ids.
	 */
	private function deleteWflUGAuth( array $wflUGAuthIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmDeleteWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->WorkflowUserGroupAuthorizationIds = $wflUGAuthIds;
		$this->utils->callService( $this, $request, 'Delete WorkflowUserGroupAuthorization rules.' );
	}

	/**
	 * Adds or removes AdmProfileFeatures to an access profile.
	 *
	 * @param AdmAccessProfile $accessProfile
	 * @param array $featureNames A list of (unique) feature names.
	 * @param boolean $doRemove If true, features are removed. If false, features are added.
	 */
	private function modifyProfileFeaturesOfProfile( AdmAccessProfile $accessProfile, array $featureNames, $doRemove )
	{
		$profileFeatures = array();
		if( $featureNames ) foreach( $featureNames as $featureName ) {
			$profileFeature = new AdmProfileFeature();
			$profileFeature->Value = ( $doRemove ) ? 'No' : 'Yes';
			$profileFeature->Name = $featureName;
			$profileFeatures[] = $profileFeature;
		}
		$accessProfile->ProfileFeatures = $profileFeatures;

		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';
		$request = new AdmModifyAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->AccessProfiles = array( $accessProfile );
		$this->utils->callService( $this, $request,
			( $doRemove ? 'Removed' : 'Added' ) . ' profile features ' . implode( ', ', $featureNames ) . ' to access profile.' );
	}

	/**
	 * Create user group and return it.
	 *
	 * @param boolean $admin If true the user group had sysadmin rights.
	 * @return integer The user group id.
	 */
	private function createUserGroup( $admin )
	{
		$this->postfix += 1;
		$userGroup = new AdmUserGroup();
		$userGroup->Name = 'UserGroup_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );
		$userGroup->Description = 'User group used in the workflow user group authorizations build test.';
		$userGroup->Admin = $admin;

		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
		$request = new AdmCreateUserGroupsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->UserGroups = array( $userGroup );
		$response = $this->utils->callService( $this, $request, 'Create a user group.' );
		return $response->UserGroups[0]->Id;
	}

	/**
	 * Create a user and return it.
	 *
	 * @return AdmUser The created user.
	 */
	private function createUser()
	{
		$this->postfix += 1;
		$user = new AdmUser();
		$user->Name = 'User_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );
		$user->Password = 'password'.$this->postfix;

		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		$request = new AdmCreateUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Users = array( $user );
		$response = $this->utils->callService( $this, $request, 'Create a user.' );
		$user = $response->Users[0];
		$user->Password = 'password'.$this->postfix;
		return $user;
	}

	/**
	 * Creates an overrule issue and returns the id.
	 *
	 * @param boolean $overrule Issue overrules the brand if true.
	 * @return integer The id of the created issue.
	 */
	private function createIssue( $overrule )
	{
		$this->postfix += 1;
		$issue = new AdmIssue();
		$issue->Name = 'OverruleIssue_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );
		$issue->Description = 'An overrule issue used in the workflow user group authorizations build test.';
		$issue->OverrulePublication = $overrule;

		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->PubChannelId = $this->pubChannelId;
		$request->Issues = array( $issue );
		$response = $this->utils->callService( $this, $request, 'Create an overrule issue.' );

		return $response->Issues[0]->Id;
	}

	/**
	 * Creates a publication and returns the id.
	 *
	 * @return integer The id of the created publication.
	 */
	private function createPublication()
	{
		$this->postfix += 1;
		$publication = new AdmPublication();
		$publication->Name = 'Publication_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );
		$publication->Description = 'A spare publication used in the workflow user group authorizations build test.';

		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationsService.class.php';
		$request = new AdmCreatePublicationsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Publications = array( $publication );
		$response = $this->utils->callService( $this, $request, 'Create a publication.' );

		return $response->Publications[0]->Id;
	}

	/**
	 * Creates a section and returns the id.
	 *
	 * @param boolean $isBrand If true, the section will be under the brand, if false it is under an overruling issue.
	 * @return integer
	 */
	private function createSection( $isBrand )
	{
		$this->postfix += 1;
		$section = new AdmSection();
		$section->Name = 'Section_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );

		require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';
		$request = new AdmCreateSectionsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		if( $isBrand ) {
			$request->PublicationId = $this->publicationId;
			$request->IssueId = 0;
		} else {
			$request->PublicationId = 0;
			$request->IssueId = $this->overruleIssueId;
		}
		$request->Sections = array( $section );
		$environment = ( $isBrand ) ? 'brand' : 'issue';
		$response = $this->utils->callService( $this, $request, 'Create a section for a ' . $environment . '.' );

		return $response->Sections[0]->Id;
	}

	/**
	 * Creates a Status object and returns the id.
	 *
	 * @param string $type The ObjectType of the status.
	 * @param integer $publicationId The publication id the status belongs to.
	 * @return integer The id of the created status.
	 */
	private function createStatus( $type, $publicationId )
	{
		$this->postfix += 1;
		$status = new AdmStatus();
		$status->Name = 'Status_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );
		$status->Type = $type;
		$status->Color = 'A0A0A0';

		require_once BASEDIR.'/server/services/adm/AdmCreateStatusesService.class.php';
		$request = new AdmCreateStatusesRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->Statuses = array( $status );
		$response = $this->utils->callService( $this, $request, 'Create a status.' );
		$status = reset( $response->Statuses );
		return $status->Id;
	}


	/**
	 * Creates an Access Profile object and returns it.
	 *
	 * @return AdmAccessProfile The created access profile.
	 */
	private function createAccessProfile()
	{
		$this->postfix += 1;
		$accessProfile = new AdmAccessProfile();
		$accessProfile->Name = 'AccessProfile_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );
		$accessProfile->Description = 'Access profile used in the workflow user group authorizations build test.';

		require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';
		$request = new AdmCreateAccessProfilesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->AccessProfiles = array( $accessProfile );
		$response = $this->utils->callService( $this, $request, 'Create an access profile.' );

		return $response->AccessProfiles[0];
	}


	/**
	 * Creates a Hyperlink object and returns it.
	 *
	 * @param string $ticket The ticket of the user creating the object.
	 * @param integer $publicationId The id of the publication the object is made for.
	 * @param integer $sectionId The id of the section the object is made in.
	 * @param integer $statusId The id of the status the object belongs to.
	 * @param bool $lock If true the object is locked on create, if false the object is not.
	 * @return Object|null The created Hyperlink object if successful, null otherwise.
	 */
	private function createHyperlink( $ticket, $publicationId, $sectionId, $statusId, $lock = false )
	{
		$publication = new Publication();
		$publication->Id = $publicationId;
		$publication->Name = 'publication';

		$category = new Category();
		$category->Id = $sectionId;
		$category->Name = 'category';

		$status = new State();
		$status->Id = $statusId;
		$status->Name = 'status';
		$status->Color = 'A0A0A0';

		$this->postfix += 1;
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->BasicMetaData->Name = 'ObjectHyperLink_T'.$this->getPrio().'_'.$this->postfix.'_'. date( 'dmy_his' );
		$metaData->BasicMetaData->Type = 'Hyperlink';
		$metaData->BasicMetaData->Publication = $publication;
		$metaData->BasicMetaData->Category = $category;
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->ContentMetaData->Description = 'Temporary hyperlink to test workflow authorizations. Created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->WorkflowMetaData->State = $status;
		$metaData->ExtraMetaData = array();

		$object = new Object();
		$object->MetaData = $metaData;

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket	= $ticket;
		$request->Lock		= $lock;
		$request->Objects	= array( $object );
		$response = $this->utils->callService( $this, $request, 'Create a Hyperlink object.' );
		return ( $response->Objects ) ? $response->Objects[0]->MetaData->BasicMetaData->ID : null;
	}
}