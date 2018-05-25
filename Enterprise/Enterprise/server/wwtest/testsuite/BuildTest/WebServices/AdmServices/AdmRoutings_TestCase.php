<?php
/**
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmRoutings_TestCase extends TestCase
{
	public function getDisplayName() { return 'Routing rules'; }
	public function getTestGoals() { return 'Checks if routing rules can be round-tripped and deleted successfully.'; }
	public function getTestMethods()
	{
		return 'Setup test data: Create all necessary data to be used in tests.<ol>
					<li>Pre-configured variables: System user SU and Brand B. (As configured in TESTSUITE option, created in setup of TestSuite.)</li>
					<li>SU: Create overruling Issue OI under Brand B.</li>
					<li>SU: Create a User Group WG (with routing option and without system admin option).</li>
					<li>SU: Create a workflow User WU and make WU member of User Group WG.</li>
					<li>WU: Logon User WU.</li>
					<li>SU: Create an access profile P1.</li>
					<li>SU: Give the access profile View, Read, Write, Open_Edit, Delete and Purge rights.</li>
					<li>SU: Create Status S3 under Brand B and Type "Hyperlink".</li>
					<li>SU: Create Status S2 under Brand B, Type "Hyperlink" and make S3 the next status.</li>
					<li>SU: Create Status S1 under Brand B, Type "Hyperlink" and make S2 the next Status.</li>
					<li>SU: Create a Category C1 under Brand B.</li>
					<li>SU: Create a Workflow User Group Authorization rule A1 for User Group WG, access profile P1, Category C1.</li>
					<li>SU: Create a spare Brand B2.</li>
					<li>SU: Create a regular Issue I.</li>
				</ol>
				Scenario 1: Routing should be updated according to the current status an object is in.<ol>
					<li>SU: Create a Routing rule R1 for User WG, Category C1 and Status S2.</li>
					<li>WU: Create a Hyperlink object H1 in Brand B, Category C1 and Status S1.</li>
					<li>WU: Validate that the object is not routed to anyone.</li>
					<li>WU: Send H1 to Status S2.</li>
					<li>WU: Validate that the object is routed to User WG.</li>
					<li>WU: Send H1 to Status S3.</li>
					<li>WU: Validate that the object is not routed to anyone anymore.</li>
					<li>WU: Delete Hyperlink object H1.</li>
					<li>SU: Delete Routing rule R1.</li>
				</ol>
				Bad attempt testing for create services.<br/>
				Bad attempt testing for modify services.<br/>
				Bad attempt testing for get services.<br/>
				Bad attempt testing for delete services.<br/><br/>
				Tear down test data: Deletion of all test data, according to "first in last out".<ol>
					<li>SU: Delete regular Issue I.</li>
					<li>SU: Delete overruling Issue OI.</li>
					<li>SU: Delete spare Brand B2.</li>
					<li>SU: Delete Workflow User Group Authorization rule A1.</li>
					<li>SU: Delete Category C1.</li>
					<li>SU: Delete Statuses S1, S2 and S3.</li>
					<li>SU: Delete Access Profile P1.</li>
					<li>WU: Logoff User WU.</li>
					<li>SU: Delete User WU.</li>
					<li>SU: Delete User Group WG.</li>
					<li>SU: Delete overruling issue OI.</li>
				</ol>';
	}
	public function getPrio() { return 200; }
	public function isSelfCleaning() { return true; }

	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	private $compare = null;         //The PhpCompare utils class.
	private $ticket = null;          //The system admin ticket of the user created during initialisation of test data.
	private $publicationId = null;   //The publication id of the publication created during initialisation of test data.
	private $pubChannelId = null;    //The publication channel id created during initialisation of test data.

	private $overruleIssueId = null; //An overrule issue created within the TESTSUITE publication.
	private $userGroupId = null;     //User group of the workflow user.
	private $user = null;            //The workflow user.
	private $wflTicket = null;       //Ticket of the workflow user.
	private $accessProfile1 = null;  //An access profile.
	private $statusId1 = null;       //The id of a status with the Hyperlink type.
	private $statusId2 = null;       //The id of another status. It is the next status of status1.
	private $statusId3 = null;       //The id of another status. It is the next status of status2.
	private $sectionId = null;       //The id of a section in the publication.
	private $wflUGAuthId1 = null;    //The id of a workflow authorization rule of the user group, access profile and section.
	private $sparePubId = null;      //The id of a second publication.
	private $issueId = null;         //The id of a regular issue in the second publication.

	public function runTest()
	{
		// Init utils.
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/utils/TestSuite.php';
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';

		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		$this->compare = new WW_Utils_PhpCompare();
		$this->compare->initCompare( array() );

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
	 * Create statuses, category, authorization rule, access profile, overrule issue, user group
	 * and user. Logon the user.
	 *
	 * @return bool True if no errors, false otherwise.
	 * @throws BizException If logged in user is not part of the brand.
	 */
	private function setUpTestData()
	{
		// Because we dynamically create users / groups / authorizations, we can not rely on the current PHP session
		// that runs this test script and simply call services directly. Instead, we use a JSON client to create a new
		// PHP session at the core server to initialize the authorization cache for the acting user (per web service call).
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		//Pre-configured variables: System user SU and Brand B. (As configured in TESTSUITE option, created in setup of TestSuite.)

		//SU: Create overruling Issue I under Brand B.
		$issue = new AdmIssue();
		$issue->Name = 'OverruleIssue_T_'.date_format( date_create(), 'dmy_his_u' );
		$issue->OverrulePublication = true;
		$response = $this->utils->createNewIssue( $this, $this->ticket, $this->publicationId, $this->pubChannelId, $issue );
		$this->overruleIssueId = $response->Issues[0]->Id;

		//SU: Create a User Group WG (with routing option and without system admin option).
		$userGroup = new AdmUserGroup();
		$userGroup->Name = 'UserGroup_T_'.date_format( date_create(), 'dmy_his_u' );
		$userGroup->Admin = false;
		$userGroup->Routing = true;
		$this->userGroupId = $this->utils->createNewUserGroup( $this, $this->ticket, $userGroup );

		//SU: Create a workflow User WU and make WU member of User Group WG.
		$this->user = $this->createUser();

		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->userGroupId;
		$request->UserIds = array( $this->user->Id );
		$this->utils->callService( $this, $request, 'Setup: Add wflUser to wflUserGroup.' );

		//WU: Logon User WU.
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User = $this->user->Name;
		$request->Password = $this->user->Password;
		$request->ClientName = 'WorkflowUserGroupAuthorization build test';
		$request->ClientAppName = 'Web';
		$request->ClientAppVersion = SERVERVERSION;
		$response = $this->utils->callService( $this, $request, 'Log on the wflUser.' );
		$this->wflTicket = $response->Ticket;

		//SU: Create an access profile P1.
		$this->accessProfile1 = $this->utils->createNewAccessProfile( $this, $this->ticket );

		//SU: Give the access profile View, Read, Write, Open_Edit, Delete and Purge rights.
		$this->utils->modifyProfileFeaturesOfProfile( $this, $this->ticket, $this->accessProfile1, array( 'View', 'Read', 'Write', 'Open_Edit', 'Delete', 'Purge', 'Change_Status' ), false );

		//SU: Create Status S3 under Brand B and Type "Hyperlink".
		$status = new AdmStatus();
		$status->Name = 'Status_T_'.date_format( date_create(), 'dmy_his_u' );
		$status->Color = 'A0A0A0';
		$status->Type = 'Hyperlink';
		$this->statusId3 = $this->utils->createNewStatus( $this, $this->ticket, $this->publicationId, null, $status );

		//SU: Create Status S2 under Brand B, Type "Hyperlink" and make S3 the next status.
		$status->Name .= '2';
		$status->NextStatus = $this->statusId3;
		$this->statusId2 = $this->utils->createNewStatus( $this, $this->ticket, $this->publicationId, null, $status );

		//SU: Create Status S1 under Brand B, Type "Hyperlink" and make S2 the next Status.
		$status->Name .= '1';
		$status->NextStatus = $this->statusId2;
		$this->statusId1 = $this->utils->createNewStatus( $this, $this->ticket, $this->publicationId, null, $status );

		//SU: Create a Category C1 under Brand B.
		$this->sectionId = $this->utils->createNewSection( $this, $this->ticket, $this->publicationId, 0 );

		//SU: Create a Workflow User Group Authorization rule A1 for User Group WG, access profile P1, Category C1.
		$this->wflUGAuthId1 = $this->utils->createNewWorkflowUserGroupAuthorization( $this, $this->ticket, $this->publicationId, 0, $this->userGroupId, $this->accessProfile1->Id, $this->sectionId, null );

		//SU: Create a spare Brand B2.
		$this->sparePubId = $this->utils->createNewPublication( $this, $this->ticket );

		//SU: Create a regular Issue I.
		$issue = new AdmIssue();
		$issue->Name = 'AdmIssue_T_'.date_format( date_create(), 'dmy_his_u' );
		$issue->OverrulePublication = false;
		$response = $this->utils->createNewIssue( $this, $this->ticket, $this->publicationId, $this->pubChannelId, $issue );
		$this->issueId = $response->Issues[0]->Id;

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Allow editing article but no deletion. Add the Delete
	 * feature and delete article.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 * @throws BizException if something goes wrong during the scenario.
	 */
	private function scenario001()
	{
		//SU: Create a Routing rule R1 for User WG, Category C1 and Status S2.
		$routingId = $this->createRouting( $this->sectionId, $this->statusId2, $this->user->Name );

		//WU: Create a Hyperlink object H1 in Brand B, Category C1 and Status S1.
		$hyperlink = $this->createHyperlink( $this->wflTicket, $this->publicationId, $this->sectionId, $this->statusId1 );

		//WU: Validate that the object is not routed to anyone.
		if( $hyperlink->MetaData->WorkflowMetaData->RouteTo ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'The RouteTo property should not be set for this newly created object.' );
		}

		//WU: Send H1 to Status S2.
		$wflMD = new WorkflowMetaData();
		$wflMD->State = new State( $this->statusId2, 'status2' );
		require_once BASEDIR.'/server/services/wfl/WflSendToService.class.php';
		$request = new WflSendToRequest();
		$request->Ticket = $this->wflTicket;
		$request->IDs = array( $hyperlink->MetaData->BasicMetaData->ID );
		$request->WorkflowMetaData = $wflMD;
		$this->utils->callService( $this, $request, 'Scenario001: Send Hyperlink H1 to Status2' );

		//WU: Validate that the object is routed to User WG.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->IDs = array( $hyperlink->MetaData->BasicMetaData->ID );
		$response = $this->utils->callService( $this, $request, 'Scenario001: Get the Hyperlink object after the first SendTo.' );

		$hyperlink = $response->Objects[0];
		if( !strcmp( $hyperlink->MetaData->WorkflowMetaData->RouteTo, $this->user->Name ) ) {
			$routeto = $hyperlink->MetaData->WorkflowMetaData->RouteTo;
			throw new BizException( 'ERR_ARGUMENT', 'Server',
				'The RouteTo property of the Hyperlink object is not what was expected. Expected: "'.$this->user->Name.'", received: "'.$routeto.'"' );
		}

		//WU: Send H1 to Status S3.
		$wflMD = new WorkflowMetaData();
		$wflMD->State = new State( $this->statusId3, 'status3' );
		require_once BASEDIR.'/server/services/wfl/WflSendToService.class.php';
		$request = new WflSendToRequest();
		$request->Ticket = $this->wflTicket;
		$request->IDs = array( $hyperlink->MetaData->BasicMetaData->ID );
		$request->WorkflowMetaData = $wflMD;
		$this->utils->callService( $this, $request, 'Scenario001: Send Hyperlink H1 to Status3' );

		//WU: Validate that the object is not routed to anyone anymore.
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->IDs = array( $hyperlink->MetaData->BasicMetaData->ID );
		$response = $this->utils->callService( $this, $request, 'Scenario001: Get the Hyperlink object after the second SendTo.' );
		$hyperlink = $response->Objects[0];

		if( $hyperlink->MetaData->WorkflowMetaData->RouteTo ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'The RouteTo property should not be set after sending Hyperlink to Status3' );
		}

		//WU: Delete Hyperlink object H1.
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->wflTicket;
		$request->Permanent = true;
		$request->IDs = array( $hyperlink->MetaData->BasicMetaData->ID );
		$this->utils->callService( $this, $request, 'Scenario001: WflUser deletes the Hyperlink Object.' );

		//SU: Delete Routing rule R1.
		$this->deleteRoutings( array( $routingId ) );

		return $this->hasError() ? false : true;
	}

	/**
	 * Test the create service with bad attempt scenarios.
	 *
	 * @return bool True if no error occurred, false otherwise.
	 */
	private function testBadAttemptsCreateService()
	{
		$this->utils = new WW_Utils_TestSuite();
		//setup
		//$noRightsUser = $this->createUser();
		//$soap = false;

		require_once BASEDIR.'/server/services/adm/AdmCreateRoutingsService.class.php';
		$request = new AdmCreateRoutingsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 11; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$routing = $this->buildRouting( null, null, $this->statusId1, $this->user->Name );
					$request->PublicationId = PHP_INT_MAX-1;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$routing = $this->buildRouting( null, null, $this->statusId1, $this->user->Name );
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX-1;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with a non-existing issue id.';
					$expError = '(S1000)';
					break;
				case 3:
					$routing = $this->buildRouting( null, PHP_INT_MAX-1, null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with a non-existing section id.';
					$expError = '(S1056)';
					break;
				case 4:
					$routing = $this->buildRouting( null, null, PHP_INT_MAX-1, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with a non-existing status id.';
					$expError = '(S1056)';
					break;
				case 5:
					$routing = $this->buildRouting( null, null, null, 'non-existing user' );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with a non-existing user (group) as RouteTo.';
					$expError = '(S1056)';
					break;
				case 6:
					$routing = $this->buildRouting( '1', null, null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule while it already has an id.';
					$expError = '(S1000)';
					break;
				case 7:
					$tempRoutingId = $this->createRouting( $this->sectionId, $this->statusId3, $this->user->Name );
					$routingIds[] = $tempRoutingId;
					$routing = $this->buildRouting( null, $this->sectionId, $this->statusId3, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule that already exists (matching combination of ids).';
					$expError = '(S1038)';
					break;
				case 8:
					$routing = $this->buildRouting( null, 'id', null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule using non-numeric characters in ids.';
					$expError = '(S1000)';
					break;
				case 9:
					$routing = $this->buildRouting( null, -10, null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule using negative ids.';
					$expError = '(S1000)';
					break;
				case 10:
					$routing = $this->buildRouting( null, null, null, $this->user->Name );
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 11:
					$routing = $this->buildRouting( null, null, null, $this->user->Name );
					$request->PublicationId = null;
					$request->IssueId = $this->issueId;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
				/*case 12:
					$routing = $this->buildRouting( null, null, null, $this->user->Name );
					require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
					DBTicket::expireTicket( $this->ticket );
					$request->Ticket = $this->ticket;
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Create a routing rule with an expired ticket.';
					$expError = '(S1043)';
					break;
				case 13:
					$routing = $this->buildRouting( null, null, null, $this->user->Name );
					$ticket = $this->wflSoapLogOn( $noRightsUser );
					$request->Ticket = $ticket;
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$soap = true;
					$stepInfo = 'Create a routing rule with an unauthorized user.';
					$expError = '(S1002)';
					break;*/
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response ) {
				$routingIds[] = $response->Routings[0]->Id;
			}
		}
		/*if( isset( $ticket ) ) {
			$this->wflSoapLogOff( $ticket );
		}
		//re-logon the test user
		$response = $this->utils->admLogOn( $this );
		$this->ticket = $response->Ticket;

		if( isset( $user ) ) {
			$this->utils->deleteUsers( $this, $this->ticket, array( $user->Id ) );
		}*/
		//tear down
		if( !empty( $routingIds ) ) {
			$this->deleteRoutings( $routingIds );
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
		$this->utils = new WW_Utils_TestSuite();
		//setup
		$routingId = $this->createRouting( null, null, $this->user->Name );
		$routingIds[] = $routingId;
		//$soap = false;
		//$noRightsUser = $this->createUser();

		require_once BASEDIR.'/server/services/adm/AdmModifyRoutingsService.class.php';
		$request = new AdmModifyRoutingsRequest();
		$request->Ticket = $this->ticket;


		for( $i = 1; $i <= 12; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$routing = $this->buildRouting( $routingId, null, null, $this->user->Name );
					$request->PublicationId = PHP_INT_MAX-1;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$routing = $this->buildRouting( $routingId, null, null, $this->user->Name );
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX-1;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with a non-existing issue id.';
					$expError = '(S1000)';
					break;
				case 3:
					$routing = $this->buildRouting( PHP_INT_MAX-1, null, $this->statusId2, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with a non-existing routing id.';
					$expError = '(S1056)';
					break;
				case 4:
					$routing = $this->buildRouting( $routingId, PHP_INT_MAX-1, null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with a non-existing section id.';
					$expError = '(S1056)';
					break;
				case 5:
					$routing = $this->buildRouting( $routingId, null, PHP_INT_MAX-1, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with a non-existing status id.';
					$expError = '(S1056)';
					break;
				case 6:
					$routing = $this->buildRouting( $routingId, null, null, 'non-existing user' );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with a non-existing user (group) as RouteTo.';
					$expError = '(S1056)';
					break;
				case 7:
					$tempRoutingId = $this->createRouting( $this->sectionId, $this->statusId3, $this->user->Name );
					$routingIds[] = $tempRoutingId;
					$routing = $this->buildRouting( $routingId, $this->sectionId, $this->statusId3, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule that already exists. (matching combination of settings)';
					$expError = '(S1038)';
					break;
				case 8:
					$routing = $this->buildRouting( null, null, null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule and give no id to the routing rule.';
					$expError = '(S1000)';
					break;
				case 9:
					$routing = $this->buildRouting( $routingId, 'id', null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule using non-numeric characters in ids.';
					$expError = '(S1000)';
					break;
				case 10:
					$routing = $this->buildRouting( $routingId, -10, null, $this->user->Name );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule using negative ids.';
					$expError = '(S1000)';
					break;
				case 11:
					$routing = $this->buildRouting( $routingId, null, null, $this->user->Name );
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 12:
					$routing = $this->buildRouting( $routingId, null, null, $this->user->Name );
					$request->PublicationId = null;
					$request->IssueId = $this->issueId;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
				/*case 13:
					$routing = $this->buildRouting( $routingId, null, null, $this->user->Name );
					require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
					DBTicket::expireTicket( $this->ticket );
					$request->Ticket = $this->ticket;
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$stepInfo = 'Modify a routing rule with an expired ticket.';
					$expError = '(S1043)';
					break;
				case 14:
					$routing = $this->buildRouting( $routingId, null, null, $this->user->Name );
					$ticket = $this->wflSoapLogOn( $noRightsUser );
					$request->Ticket = $ticket;
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->Routings = array( $routing );
					$soap = true;
					$stepInfo = 'Modify a routing rule with an unauthorized user.';
					$expError = '(S1002)';*/
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response ) {
				$routingIds[] = $response->Routings[0]->Id;
			}
		}
		/*if( isset( $ticket ) ) {
			$this->wflSoapLogOff( $ticket );
			BizSession::endSession();
		}
		//re-logon the test user
		$response = $this->utils->admLogOn( $this );
		$this->ticket = $response->Ticket;

		if( isset( $user ) ) {
			$this->utils->deleteUsers( $this, $this->ticket, $user->Id );
		}*/
		//tear down
		if( !empty( $routingIds ) ) {
			$this->deleteRoutings( $routingIds );
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
		$routingId1 = $this->createRouting( null, null, $this->user->Name );
		$routingId2 = $this->createRouting( $this->sectionId, null, $this->user->Name );
		$routingIds = array( $routingId1, $routingId2 );

		require_once BASEDIR.'/server/services/adm/AdmGetRoutingsService.class.php';
		$request = new AdmGetRoutingsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		for( $i = 1; $i <= 10; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Get one or more routing rules by filter ids with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX-1;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Get one or more routing rules by filter ids with a non-existing issue id.';
					$expError = '(S1000)';
					break;
				case 3:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = PHP_INT_MAX-1;
					$request->RoutingIds = null;
					$stepInfo = 'Get one or more routing rules by filter ids with a non-existing section id.';
					$expError = '(S1056)';
					break;
				case 4:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = $routingIds;
					$stepInfo = 'Get one or more routing rules by filter ids with also routing rule ids given.';
					$expError = '(S1000)';
					break;
				case 5:
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->SectionId = $this->sectionId;
					$request->RoutingIds = null;
					$stepInfo = 'Get one or more routing rules by filter ids with no publication or issue id given.';
					$expError = '(S1000)';
					break;
				case 6:
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = array_merge( $routingIds, array( PHP_INT_MAX-1 ) );
					$stepInfo = 'Get one or more routing rules by routing rule ids with a non-existing routing rule.';
					$expError = '(S1056)';
					break;
				case 7:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = array_merge( $routingIds, array( 'id' ) );
					$stepInfo = 'Get one or more routing rules using non-numeric characters in ids.';
					$expError = '(S1000)';
					break;
				case 8:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = array_merge( $routingIds + array( -10 ) );
					$stepInfo = 'Get one or more routing rules using negative ids.';
					$expError = '(S1000)';
					break;
				case 9:
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Get one or more routing rules with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 10:
					$request->PublicationId = null;
					$request->IssueId = $this->issueId;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Get one or more routing rules with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response && !in_array( $response->Routings[0]->Id, $routingIds ) ) {
				$routingIds[] = $response->Routings[0]->Id;
			}
		}
		//tear down
		if( !empty( $routingIds ) ) {
			$this->deleteRoutings( $routingIds );
		}
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
		$routingId1 = $this->createRouting( null, null, $this->user->Name );
		$routingId2 = $this->createRouting( $this->sectionId, null, $this->user->Name );
		$routingIds = array( $routingId1, $routingId2 );

		require_once BASEDIR.'/server/services/adm/AdmDeleteRoutingsService.class.php';
		$request = new AdmDeleteRoutingsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 8; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules by filter ids with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = null;
					$request->IssueId = PHP_INT_MAX-1;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules by filter ids with a non-existing issue id.';
					$expError = '(S1000)';
					break;
				case 3:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = PHP_INT_MAX-1;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules by filter ids with a non-existing section id.';
					$expError = '(S1056)';
					break;
				case 4:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = $routingIds;
					$stepInfo = 'Delete one or more routing rules by filter ids with also routing rule ids given.';
					$expError = '(S1000)';
					break;
				case 5:
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->SectionId = $this->sectionId;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules by filter ids with no publication or issue id given.';
					$expError = '(S1000)';
					break;
				case 6:
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = array_merge( $routingIds, array( 'id' ) );
					$stepInfo = 'Delete one or more routing rules using non-numeric characters in ids.';
					$expError = '(S1000)';
					break;
				case 7:
					$request->PublicationId = $this->sparePubId;
					$request->IssueId = $this->overruleIssueId;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 8:
					$request->PublicationId = null;
					$request->IssueId = $this->issueId;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
				/*case 9:
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = array_merge( $routingIds, array( -10 ) );
					$stepInfo = 'Delete one or more routing rules using negative ids.';
					$expError = '(S1000)';
					break;
				case 10:
					$user = $this->createUser();
					$ticket = $this->wflSoapLogOn( $user );
					$request->Ticket = $ticket;
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules with an unauthorized user.';
					$expError = '(S1002)';
					break;
				case 11:
					require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
					DBTicket::expireTicket( $this->ticket );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = null;
					$request->SectionId = null;
					$request->RoutingIds = null;
					$stepInfo = 'Delete one or more routing rules with an expired ticket.';
					$expError = '(S1043)';
					break;*/
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response ) {
				$routingIds[] = $response->Routings[0]->Id;
			}
		}
		/*//re-logon the test user
		$response = $this->utils->admLogOn( $this );
		$this->ticket = $response->Ticket;

		if( isset( $ticket ) ) {
			$this->wflSoapLogOff( $ticket );
		}
		if( isset( $user ) ) {
			$this->utils->deleteUsers( $this, $this->ticket, $user->Id );
		}*/
		//tear down
		if( !empty( $routingIds ) ) {
			$this->deleteRoutings( $routingIds );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Tear down the test data.
	 *
	 * Log off the workflow user. Delete the statuses, category, authorization rule, access profile,
	 * overrule issue, user group and user that were created in setup.
	 */
	private function tearDownTestData()
	{
		$this->utils = new WW_Utils_TestSuite();

		//SU: Delete regular Issue I.
		if( isset( $this->issueId ) ) {
			$this->utils->removeIssue( $this, $this->ticket, $this->publicationId, $this->issueId );
			unset( $this->issueId );
		}
		//SU: Delete spare Brand B2.
		if( isset( $this->sparePubId ) ) {
			$this->utils->deletePublications( $this, $this->ticket, array( $this->sparePubId ) );
			unset( $this->sparePubId );
		}
		//SU: Delete Workflow User Group Authorization rule A1.
		if( isset( $this->wflUGAuthId1 ) ) {
			$this->utils->deleteWorkflowUserGroupAuthorizations( $this, $this->ticket, null, null, null, array( $this->wflUGAuthId1 ) );
			unset( $this->wflUGAuthId1 );
		}
		//SU: Delete Category C1.
		if( isset( $this->sectionId ) ) {
			$this->utils->deleteSections( $this, $this->ticket, $this->publicationId, null, array( $this->sectionId ) );
			unset( $this->sectionId );
		}
		//SU: Delete Statuses S1, S2 and S3.
		$statusIds = array();
		if( isset( $this->statusId1 ) ) {
			$statusIds[] = $this->statusId1;
			unset( $this->statusId1 );
		}
		if( isset( $this->statusId2 ) ) {
			$statusIds[] = $this->statusId2;
			unset( $this->statusId2 );
		}
		if( isset( $this->statusId3 ) ) {
			$statusIds[] = $this->statusId3;
			unset( $this->statusId3 );
		}
		if( !empty( $statusIds ) ) {
			$this->utils->deleteStatuses( $this, $this->ticket, $statusIds );
		}
		//SU: Delete Access Profile P1.
		if( isset( $this->accessProfile1 ) ) {
			$this->utils->deleteAccessProfiles( $this, $this->ticket, array( $this->accessProfile1->Id ) );
			unset( $this->accessProfile1 );
		}
		//WU: Logoff User WU.
		if( isset( $this->wflTicket ) ) {
			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = $this->wflTicket;
			$this->utils->callService( $this, $request, 'Log off wflUser.' );
			unset( $this->wflTicket );
		}
		//SU: Delete User UG.
		if( isset( $this->user ) ) {
			$this->utils->deleteUsers( $this, $this->ticket, array( $this->user->Id ) );
			unset( $this->user );
		}
		//SU: Delete User Group WG.
		if( isset( $this->userGroupId ) ) {
			$this->utils->deleteUserGroups( $this, $this->ticket, array( $this->userGroupId ) );
			unset( $this->userGroupId );
		}
		//SU: Delete overruling Issue OI.
		if( isset( $this->overruleIssueId ) ) {
			$this->utils->removeIssue( $this, $this->ticket, $this->publicationId, $this->overruleIssueId );
			unset( $this->overruleIssueId );
		}
	}

	/**
	 * Builds an AdmRouting object and returns it.
	 *
	 * @param integer|null $routingId Optional routing id.
	 * @param integer|null $sectionId Optional section id used in the routing rule.
	 * @param integer|null $statusId Optional status id used in the routing rule.
	 * @param string $routeTo The user (group) the routing rule is for.
	 * @return AdmRouting
	 */
	private function buildRouting( $routingId = null, $sectionId, $statusId, $routeTo )
	{
		$routing = new AdmRouting();
		$routing->Id = $routingId;
		$routing->SectionId = $sectionId;
		$routing->StatusId = $statusId;
		$routing->RouteTo = $routeTo;
		return $routing;
	}

	/**
	 * Create a routing rule and return the id.
	 *
	 * @param integer $sectionId The section id.
	 * @param integer $statusId The status id.
	 * @param string $routeTo The user or user group the routing is for.
	 * @return integer The id of the newly created routing.
	 */
	private function createRouting( $sectionId, $statusId, $routeTo )
	{
		$routing = $this->buildRouting( null, $sectionId, $statusId, $routeTo );

		require_once BASEDIR.'/server/services/adm/AdmCreateRoutingsService.class.php';
		$request = new AdmCreateRoutingsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->IssueId = null;
		$request->Routings = array( $routing );
		$response = $this->utils->callService( $this, $request, 'Create a routing rule.' );
		return key(reset($response));
	}

	/**
	 * Delete one or more routing rules.
	 *
	 * @param array $routingIds The list of routing ids to be deleted.
	 */
	private function deleteRoutings( array $routingIds )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteRoutingsService.class.php';
		$request = new AdmDeleteRoutingsRequest();
		$request->Ticket = $this->ticket;
		$request->RoutingIds = $routingIds;
		$this->utils->callService( $this, $request, 'Delete one or more routing rules.' );
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

		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->BasicMetaData->Name = 'ObjectHyperLink_T_'.date_format( date_create(), 'dmy_his_u' );
		$metaData->BasicMetaData->Type = 'Hyperlink';
		$metaData->BasicMetaData->Publication = $publication;
		$metaData->BasicMetaData->Category = $category;
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->ContentMetaData->Description = 'Temporary hyperlink user for testing. Created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->WorkflowMetaData->State = $status;
		$metaData->ExtraMetaData = array();

		$object = new Object();
		$object->MetaData = $metaData;

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket	= $ticket;
		$request->Lock		= $lock;
		$request->Objects	= array( $object );
		$response = $this->utils->callService( $this, $request, 'Create a Hyperlink object.' );
		return ( $response->Objects ) ? reset( $response->Objects ) : null;
	}

	/**
	 * Create a user and return it.
	 *
	 * @return AdmUser The created user.
	 */
	private function createUser()
	{
		$user = new AdmUser();
		$user->Name = 'User_T_'.date( 'dmy_his' );
		$user->Password = 'password';

		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		$request = new AdmCreateUsersRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->Users = array( $user );
		$response = $this->utils->callService( $this, $request, 'Create a user.' );
		$user = $response->Users[0];
		$user->Password = 'password';
		return $user;
	}
}