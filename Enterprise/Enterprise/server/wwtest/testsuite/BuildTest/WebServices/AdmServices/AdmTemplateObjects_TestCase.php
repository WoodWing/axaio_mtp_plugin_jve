<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_AdmServices_AdmTemplateObjects_TestCase extends TestCase
{
	public function getDisplayName() { return 'Template object access'; }
	public function getTestGoals() { return 'Checks if template object access rules can be round-tripped and deleted successfully.'; }
	public function getTestMethods() {
		return 'Setup test data: <ol>
					<li>Pre-configured variables: System user SU, brand B and pubchannel CH. (As configured in TESTSUITE option, created in setup of TestSuite.)</li>
					<li>SU: Create an overrule issue OI under brand B.</li>
					<li>SU: Create a user group UG.</li>
					<li>SU: Create a user WU and add it to user group UG.</li>
					<li>WU: Logon user WU.</li>
					<li>SU: Give user group UG workflow authorization in brand B and overrule issue OI.</li>
					<li>SU: Create a status STB in brand B.</li>
					<li>SU: Create a status SI in overrule issue OI.</li>
					<li>SU: Create a category CB in brand B.</li>
					<li>SU: Create a category CI in overrule issue OI.</li>
					<li>SU: Create a spare brand SB.</li>
					<li>SU: Create a spare issue SI under brand B.</li>
					<li>SU: Create a spare user group SG.</li>
					<li>WU: Create a dossier template object DTB in brand B.</li>
					<li>WU: Create a dossier template object DTI in overrule issue OI.</li>
				</ol>
				Scenario 1: Give the user access to a template object and see if the user can request it. Try again without access.<ol>
					<li>SU: Give user group UG template object access for dossier template DTB.</li>
					<li>WU: Search for dossier templates and check if dossier template DTB is returned in the response for brand B.</li>
					<li>SU: Remove the template object access rights from user group UG.</li>
					<li>WU: Search for dossier templates and check if dossier template DTB is not returned in the response for brand B.</li>
				</ol>
				Bad attempt tests for AddTemplateObjectsService.
				Bad attempt tests for GetTemplateObjectsService.
				Bad attempt tests for RemoveTemplateObjectsService.
				Tear down test data:<ol>
					<li>SU: Delete dossier template object DTI.</li>
					<li>SU: Delete dossier template object DTB.</li>
					<li>SU: Delete spare user group SG.</li>
					<li>SU: Delete spare issue SI.</li>
					<li>SU: Delete spare brand SB.</li>
					<li>SU: Delete status STB of brand B.</li>
					<li>SU: Delete status SI of overrule issue OI.</li>
					<li>SU: Delete category CB of brand B.</li>
					<li>SU: Delete category CI of overrule issue OI.</li>
					<li>SU: Remove brand authorization for brand B from user group UG.</li>
					<li>WU: Logoff user WU.</li>
					<li>SU: Delete user group UG.</li>
					<li>SU: Delete user WU.</li>
					<li>SU: Delete overrule issue OI.</li>
				</ol>';
	}
	public function getPrio() { return 210; }
	public function isSelfCleaning() { return true; }

	/** @var WW_Utils_PhpCompare $compare */
	private $compare = null;
	/** @var WW_Utils_TestSuite $utils */
	private $utils = null;
	/** @var string $ticket The system admin ticket of the user created during initialisation of test data. */
	private $ticket = null;
	/** @var integer $publicationId The publication id of the publication created during initialisation of test data. *///
	private $publicationId = null;
	/** @var integer $pubChannelId The publication channel id created during initialisation of test data. */
	private $pubChannelId = null;
	/** @var int $postfix Counter to ensure unique objects names. */
	private $postfix = 0;

	private $overruleIssueId = null;
	private $userGroupId = null;

	private $userId = null;
	private $userTicket = null;
	private $brandStatusId = null;
	private $issueStatusId = null;
	private $brandSectionId = null;
	private $issueSectionId = null;
	private $brandDossierTemplateId = null;
	private $issueDossierTemplateId = null;

	private $spareUserGroupId = null;
	private $sparePublicationId = null;
	private $spareIssueId = null;

	private $brandWflUgAuthId = null;
	private $issueWflUgAuthId = null;

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

		// Perform the test with ContentStationListDossiers plug-in activated.
		$didActivate = $this->utils->activatePluginByName( $this, 'ContentStationListDossiers' );
		if( is_null($didActivate) ) { // error?
			$this->setResult( 'ERROR', 'ContentStationListDossiers plugin cannot be activated.', 'Please check if the plugin is '.
				'installed and activate it in the ServerPlugin page.' );
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
			if( !$this->testBadAttemptsGetService() ) {
				break;
			}
			if( !$this->testBadAttemptsDeleteService() ) {
				break;
			}
		} while( false );
		$this->tearDownTestData();

		if( $didActivate ) { //if it was activated before, it should be deactivated again
			$this->utils->deactivatePluginByName( $this, 'ContentStationListDossiers' );
		}

	}

	/**
	 * Set up the data to be used in the tests.
	 *
	 * Create users, groups, publications, (overrule) issues, sections, statuses and dossier
	 * templates to play with. Logon workflow user.
	 *
	 * @return bool True if no errors, false otherwise.
	 */
	private function setUpTestData()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$this->postfix += 1;

		//SU: Create an overrule issue OI under brand B.
		$issue = new AdmIssue();
		$issue->Name = 'OverruleIssue_T'.date_format( date_create(), 'dmy_his_u' );
		$issue->Description = 'An overrule issue used in the workflow user group authorizations build test.';
		$issue->OverrulePublication = true;
		$issue->Activated = true;
		$response = $this->utils->createNewIssue( $this, $this->ticket, $this->publicationId, $this->pubChannelId, $issue );
		$this->overruleIssueId = $response->Issues[0]->Id;

		//SU: Create a user group UG.
		$userGroup = new AdmUserGroup();
		$userGroup->Name = 'UserGroup_T'.date_format( date_create(), 'dmy_his_u' );
		$userGroup->Admin = false;
		$userGroup->Routing = false;
		$this->userGroupId = $this->utils->createNewUserGroup( $this, $this->ticket, $userGroup );

		//SU: Create a user WU and add it to user group UG.
		$user = new AdmUser();
		$user->Name = 'User_T'.date_format( date_create(), 'dmy_his_u' );
		$user->Password = 'UserPassword'.$this->postfix;
		$this->userId = $this->utils->createNewUser( $this, $this->ticket, $user );

		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$request = new AdmAddUsersToGroupRequest();
		$request->Ticket = $this->ticket;
		$request->GroupId = $this->userGroupId;
		$request->UserIds = array( $this->userId );
		$this->utils->callService( $this, $request, 'Setup: Add admUser to admUserGroup.' );

		//WU: Logon user WU.
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User = $user->Name;
		$request->Password = 'UserPassword'.$this->postfix;
		$request->ClientName = 'TemplateObjects build test';
		$request->ClientAppName = 'Web';
		$request->ClientAppVersion = SERVERVERSION;
		$response = $this->utils->callService( $this, $request, 'Log on the wflUser.' );
		$this->userTicket = $response->Ticket;

		//SU: Give user group UG workflow authorization in brand B and overrule issue OI.
		$wflUGAuth = new AdmWorkflowUserGroupAuthorization();
		$wflUGAuth->UserGroupId = $this->userGroupId;
		$wflUGAuth->AccessProfileId = 1; //Full Control
		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';
		$request = new AdmCreateWorkflowUserGroupAuthorizationsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->IssueId = 0;
		$request->WorkflowUserGroupAuthorizations = array( $wflUGAuth );
		$response = $this->utils->callService( $this, $request, 'Setup: Giving the user workflow rights in the brand.' );
		$this->brandWflUgAuthId = $response->WorkflowUserGroupAuthorizations[0]->Id;

		$request->IssueId = $this->overruleIssueId;
		$response = $this->utils->callService( $this, $request, 'Setup: Giving the user workflow rights in the overrule issue.' );
		$this->issueWflUgAuthId = $response->WorkflowUserGroupAuthorizations[0]->Id;

		//SU: Create a status SB in brand B.
		$status = new AdmStatus();
		$status->Name = 'Status_T'.date_format( date_create(), 'dmy_his_u' );
		$status->Type = 'DossierTemplate';
		$status->Color = 'A0A0A0';
		$this->brandStatusId = $this->utils->createNewStatus( $this, $this->ticket, $this->publicationId, 0, $status );
		//SU: Create a status SI in overrule issue OI.
		$status->Name .= '2';
		$this->issueStatusId = $this->utils->createNewStatus( $this, $this->ticket, $this->publicationId, $this->overruleIssueId, $status );

		//SU: Create a category CB in brand B.
		$this->brandSectionId = $this->utils->createNewSection( $this, $this->ticket, $this->publicationId, 0 );
		//SU: Create a category CI in overrule issue OI.
		$this->issueSectionId = $this->utils->createNewSection( $this, $this->ticket, $this->publicationId, $this->overruleIssueId );

		//SU: Create a spare brand SB.
		$this->sparePublicationId = $this->utils->createNewPublication( $this, $this->ticket );

		//SU: Create a spare issue SI under brand B.
		$issue = new AdmIssue();
		$issue->Name = 'SpareIssue_T'.date_format( date_create(), 'dmy_his_u' );
		$issue->OverrulePublication = false;
		$issue->Activated = true;
		$response = $this->utils->createNewIssue( $this, $this->ticket, $this->publicationId, $this->pubChannelId, $issue );
		$this->spareIssueId = $response->Issues[0]->Id;

		//SU: Create a spare user group SG.
		$userGroup->Name .= '2';
		$this->spareUserGroupId = $this->utils->createNewUserGroup( $this, $this->ticket, $userGroup );

		//SU: Create a dossier template object DTB in brand B.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		$metadata = new MetaData();
		$metadata->BasicMetaData = new BasicMetaData();
		$metadata->BasicMetaData->Name = 'DossierTemplateObject_T'.date_format( date_create(), 'dmy_his_u' );
		$metadata->BasicMetaData->Publication = new Publication( $this->publicationId, '' );
		$metadata->BasicMetaData->Category = new Category( $this->brandSectionId, '' );
		$metadata->BasicMetaData->Type = 'DossierTemplate';
		$metadata->WorkflowMetaData = new WorkflowMetaData();
		$metadata->WorkflowMetaData->State = new State( $this->brandStatusId, '' );
		$dossierTemplate = new Object();
		$dossierTemplate->MetaData = $metadata;

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->userTicket;
		$request->Lock = false;
		$request->Objects = array( $dossierTemplate );
		$response = $this->utils->callService( $this, $request, 'Create a dossier template in a brand.' );
		$this->brandDossierTemplateId = $response->Objects[0]->MetaData->BasicMetaData->ID;

		//SU: Create a dossier template object DTI in overrule issue OI.
		$metadata->BasicMetaData->Name .= '2';
		$dossierTemplate->MetaData->BasicMetaData->Publication = new Publication( $this->publicationId, '' );
		$dossierTemplate->MetaData->BasicMetaData->Category = new Category( $this->issueSectionId, '' );
		$dossierTemplate->MetaData->WorkflowMetaData->State = new State( $this->issueStatusId, '' );
		$target = new Target();
		$target->PubChannel = new PubChannel( $this->pubChannelId, '' );
		$target->Issue = new Issue( $this->overruleIssueId, '' );
		$targets[] = $target;
		$dossierTemplate->Targets = $targets;
		$response = $this->utils->callService( $this, $request, 'Create a dossier template in an overrule issue.' );
		$this->issueDossierTemplateId = $response->Objects[0]->MetaData->BasicMetaData->ID;

		return $this->hasError() ? false : true;
	}

	/**
	 * Scenario: Give the user access to a template object and see if the user can request it.
	 * Try again without access.
	 *
	 * @return bool True if no errors, false otherwise.
	 */
	private function scenario001()
	{
		//SU: Give user group UG template object access for dossier template DTB.
		$templateObjAccess = new AdmTemplateObjectAccess();
		$templateObjAccess->TemplateObjectId = $this->brandDossierTemplateId;
		$templateObjAccess->UserGroupId = $this->userGroupId;
		require_once BASEDIR.'/server/services/adm/AdmAddTemplateObjectsService.class.php';
		$request = new AdmAddTemplateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $this->publicationId;
		$request->IssueId = 0;
		$request->TemplateObjects = array( $templateObjAccess );
		$this->utils->callService( $this, $request, 'Scenario 001: Give template object access to a user group.' );

		//WU: Search for dossier templates and check if dossier template DTB is returned in the response for brand B.
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		$namedQueryRequest = new WflNamedQueryRequest();
		$namedQueryRequest->Ticket = $this->userTicket;
		$namedQueryRequest->Query = 'DOSSIER_TEMPLATES_IN_APPLICATIONS';
		$param = new QueryParam();
		$param->Property = 'DossierTemplateId';
		$param->Operation = '=';
		$param->Value = $this->brandDossierTemplateId;
		$namedQueryRequest->Params = array( $param );
		$response = $this->utils->callService( $this, $namedQueryRequest, 'Scenario001: Search for the dossier template.' );

		$dossierTemplateFound = false;
		if( $response->Rows ) foreach( $response->Rows as $row ) {
			if( $row[0] == $this->brandDossierTemplateId ) {
				$dossierTemplateFound = true;
			}
		}
		if( !$dossierTemplateFound ) {
			$this->setResult( 'ERROR', 'Scenario 001: Dossier template was not found when it should be.' );
		}

		//SU: Remove the template object access rights from user group UG.
		require_once BASEDIR.'/server/services/adm/AdmRemoveTemplateObjectsService.class.php';
		$request = new AdmRemoveTemplateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $this->publicationId;
		$request->IssueId = 0;
		$request->TemplateObjects = array( $templateObjAccess );
		$this->utils->callService( $this, $request, 'Scenario 001: Delete template object access of a user group.' );

		//WU: Search for dossier templates and check if dossier template DTB is not returned in the response for brand B.
		$response = $this->utils->callService( $this, $namedQueryRequest, 'Scenario001: Search for the dossier template again.' );

		if( $response->Rows ) foreach( $response->Rows as $row ) {
			if( $row[0] == $this->brandDossierTemplateId ) {
				$this->setResult( 'ERROR', 'Scenario 001: Dossier template was found when it should not be.' );
				break;
			}
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Test the create service with bad attempt scenarios.
	 *
	 * @return bool True if no errors, false otherwise.
	 */
	private function testBadAttemptsCreateService()
	{
		//setup
		require_once BASEDIR.'/server/services/adm/AdmAddTemplateObjectsService.class.php';
		$request = new AdmAddTemplateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		$templateObjects = array();
		for( $i = 1; $i <= 11; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$templateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = PHP_INT_MAX-1;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$templateObject = $this->buildTemplateObjectAccess( $this->issueDossierTemplateId, $this->userGroupId );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = PHP_INT_MAX-1;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object with a non-existing overrule issue id.';
					$expError = '(S1056)';
					break;
				case 3:
					$templateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, PHP_INT_MAX-1 );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $templateObject ) ;
					$stepInfo = 'Create a template object with a non-existing user group id.';
					$expError = '(S1056)';
					break;
				case 4:
					$templateObject = $this->buildTemplateObjectAccess( PHP_INT_MAX-1, $this->userGroupId );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object access rule with a non-existing dossier template object id.';
					$expError = '(S1056)';
					break;
				case 5:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array();
					$stepInfo = 'Create a template object access rule with an empty list of template object access rules.';
					$expError = '(S1000)';
					break;
				case 6:
					$tempTemplateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, $this->spareUserGroupId );
					$request->TemplateObjects = array( $tempTemplateObject );
					$this->utils->callService( $this, $request, 'Create a spare template object access rule.' );
					$templateObjects[] = $tempTemplateObject; //add it to array to be deleted
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $tempTemplateObject );
					$stepInfo = 'Create a template object access rule that already exists. (A matching combination of ids.)';
					$expError = '(S1038)';
					break;
				case 7:
					$templateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, -500 );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object access rule with negative integers as ids.';
					$expError = '(S1000)';
					break;
				case 8:
					$templateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, 'integer' );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object access rule with a non-numeric id.';
					$expError = '(S1000)';
					break;
				case 9:
					$templateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object access rule without either a brand or issue id given.';
					$expError = '(S1000)';
					break;
				case 10:
					$templateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = $this->sparePublicationId;
					$request->IssueId = $this->overruleIssueId;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object access rule with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 11:
					$templateObject = $this->buildTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = $this->spareIssueId;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Create a template object access rule with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
			}
			$response = $this->utils->callService( $this, $request, $stepInfo, $expError );
			if( $response ) {
				$templateObjects[] = $request->TemplateObjects[0];
			}
		}
		//tear down
		if( !empty( $templateObjects ) ) {
			$this->removeTemplateObjectAccess( $this->publicationId, 0, $templateObjects );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Test the get service using bad attempt scenarios.
	 *
	 * @return bool True if no errors, false otherwise.
	 */
	private function testBadAttemptsGetService()
	{
		//setup
		require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';
		$request = new AdmGetTemplateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();

		for( $i = 1; $i <= 9; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$request->PublicationId = PHP_INT_MAX-1;
					$request->IssueId = 0;
					$request->TemplateObjectId = $this->brandDossierTemplateId;
					$stepInfo = 'Get a template object with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = PHP_INT_MAX-1;
					$request->TemplateObjectId = $this->issueDossierTemplateId;
					$stepInfo = 'Get a template object with a non-existing overrule issue id.';
					$expError = '(S1056)';
					break;
				case 3:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->UserGroupId = PHP_INT_MAX-1;
					$stepInfo = 'Get a template object with a non-existing user group id.';
					$expError = '(S1056)';
					break;
				case 4:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjectId = PHP_INT_MAX-1;
					$stepInfo = 'Get a template object access rule with a non-existing dossier template object id.';
					$expError = '(S1056)';
					break;
				case 5:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjectId = -500;
					$stepInfo = 'Get a template object access rule with negative integers as ids.';
					$expError = '(S1000)';
					break;
				case 6:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjectId = 'integer';
					$stepInfo = 'Get a template object access rule with a non-numeric id.';
					$expError = '(S1000)';
					break;
				case 7:
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->TemplateObjectId = $this->brandDossierTemplateId;
					$stepInfo = 'Get a template object access rule without either a brand or issue id given.';
					$expError = '(S1000)';
					break;
				case 8:
					$request->PublicationId = $this->sparePublicationId;
					$request->IssueId = $this->overruleIssueId;
					$request->TemplateObjectId = $this->brandDossierTemplateId;
					$stepInfo = 'Get a template object access rule with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 9:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = $this->spareIssueId;
					$request->TemplateObjectId = $this->brandDossierTemplateId;
					$stepInfo = 'Get a template object access rule with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Test the delete service using bad attempt scenarios.
	 *
	 * @return bool True if no errors, false otherwise.
	 */
	private function testBadAttemptsDeleteService()
	{
		//setup
		require_once BASEDIR.'/server/services/adm/AdmRemoveTemplateObjectsService.class.php';
		$request = new AdmRemoveTemplateObjectsRequest();
		$request->Ticket = $this->ticket;

		for( $i = 1; $i <= 7; $i++ ) {
			$stepInfo = '';
			$expError = '';
			switch( $i ) {
				case 1:
					$templateObject = new AdmTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = PHP_INT_MAX-1;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Delete a template object with a non-existing brand id.';
					$expError = '(S1056)';
					break;
				case 2:
					$templateObject = new AdmTemplateObjectAccess( $this->issueDossierTemplateId, $this->userGroupId );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = PHP_INT_MAX-1;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Delete a template object with a non-existing overrule issue id.';
					$expError = '(S1056)';
					break;
				case 3:
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array();
					$stepInfo = 'Delete a template object access rule with an empty list of template object access rules.';
					$expError = '(S1000)';
					break;
				case 4:
					$templateObject = new AdmTemplateObjectAccess( $this->brandDossierTemplateId, 'integer' );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = 0;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Delete a template object access rule with a non-numeric id.';
					$expError = '(S1000)';
					break;
				case 5:
					$templateObject = new AdmTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = null;
					$request->IssueId = null;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Delete a template object access rule without either a brand or issue id given.';
					$expError = '(S1000)';
					break;
				case 6:
					$templateObject = new AdmTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = $this->sparePublicationId;
					$request->IssueId = $this->overruleIssueId;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Delete a template object access rule with a brand id that is not the brand that the issue overrules.';
					$expError = '(S1000)';
					break;
				case 7:
					$templateObject = new AdmTemplateObjectAccess( $this->brandDossierTemplateId, $this->userGroupId );
					$request->PublicationId = $this->publicationId;
					$request->IssueId = $this->spareIssueId;
					$request->TemplateObjects = array( $templateObject );
					$stepInfo = 'Delete a template object access rule with an issue id that refers to an issue that has NOT the "Overrule Brand" option enabled.';
					$expError = '(S1000)';
					break;
			}
			$this->utils->callService( $this, $request, $stepInfo, $expError );
		}
		return $this->hasError() ? false : true;
	}

	/**
	 * Tear down the test data.
	 *
	 * Delete the users, groups, publications, (overrule) issues, sections, statuses and dossier
	 * templates that were created. Logoff workflow user.
	 */
	private function tearDownTestData()
	{
		if( isset( $this->brandDossierTemplateId ) ) {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->userTicket;
			$request->IDs = array( $this->brandDossierTemplateId );
			$request->Permanent = true;
			$this->utils->callService( $this, $request, 'Teardown: Deleting the dossier template object from brand.' );
			unset( $this->brandDossierTemplateId );
		}
		if( isset( $this->issueDossierTemplateId ) ) {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$request = new WflDeleteObjectsRequest();
			$request->Ticket = $this->userTicket;
			$request->IDs = array( $this->issueDossierTemplateId );
			$request->Permanent = true;
			$this->utils->callService( $this, $request, 'Teardown: Deleting the dossier template object from overrule issue.' );
			unset( $this->issueDossierTemplateId );
		}
		if( isset( $this->spareUserGroupId ) ) {
			$this->utils->deleteUserGroups( $this, $this->ticket, array( $this->spareUserGroupId ) );
			unset( $this->spareUserGroupId );
		}
		if( isset( $this->spareIssueId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
			$request = new AdmDeleteIssuesRequest();
			$request->Ticket = $this->ticket;
			$request->PublicationId = $this->publicationId;
			$request->IssueIds = array( $this->spareIssueId );
			$this->utils->callService( $this, $request, 'Teardown: Deleting the overrule issue.');
			unset( $this->spareIssueId );
		}
		if( isset( $this->sparePublicationId ) ) {
			$this->utils->deletePublications( $this, $this->ticket, array( $this->sparePublicationId ) );
			unset( $this->sparePublicationId );
		}
		if( isset( $this->brandStatusId ) ) {
			$this->utils->deleteStatuses( $this, $this->ticket, array( $this->brandStatusId ) );
			unset( $this->brandStatusId );
		}
		if( isset( $this->issueStatusId ) ) {
			$this->utils->deleteStatuses( $this, $this->ticket, array( $this->issueStatusId ) );
			unset( $this->issueStatusId );
		}
		if( isset( $this->brandSectionId ) ) {
			$this->utils->deleteSections( $this, $this->ticket, $this->publicationId, null, array( $this->brandSectionId ) );
			unset( $this->brandSectionId );
		}
		if( isset( $this->issueSectionId ) ) {
			$this->utils->deleteSections( $this, $this->ticket, $this->publicationId, null, array( $this->issueSectionId ) );
			unset( $this->issueSectionId );
		}
		if( isset( $this->brandWflUgAuthId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';
			$request = new AdmDeleteWorkflowUserGroupAuthorizationsRequest();
			$request->Ticket = $this->ticket;
			$request->WorkflowUserGroupAuthorizationIds = array( $this->brandWflUgAuthId );
			$this->utils->callService( $this, $request, 'Teardown: Deleting the workflow rights of the user in the brand.');
		}
		if( isset( $this->issueWflUgAuthId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';
			$request = new AdmDeleteWorkflowUserGroupAuthorizationsRequest();
			$request->Ticket = $this->ticket;
			$request->WorkflowUserGroupAuthorizationIds = array( $this->issueWflUgAuthId );
			$this->utils->callService( $this, $request, 'Teardown: Deleting the workflow rights of the user in the overrule issue.');
		}
		if( isset( $this->userTicket ) ) {
			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = $this->userTicket;
			$this->utils->callService( $this, $request, 'Teardown: Log off user.' );
			unset( $this->userTicket );
		}
		if( isset( $this->userGroupId ) ) {
			$this->utils->deleteUserGroups( $this, $this->ticket, array( $this->userGroupId ) );
			unset( $this->userGroupId );
		}
		if( isset( $this->userId ) ) {
			$this->utils->deleteUsers( $this, $this->ticket, array( $this->userId ) );
			unset( $this->userId );
		}
		if( isset( $this->overruleIssueId ) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
			$request = new AdmDeleteIssuesRequest();
			$request->Ticket = $this->ticket;
			$request->PublicationId = $this->publicationId;
			$request->IssueIds = array( $this->overruleIssueId );
			$this->utils->callService( $this, $request, 'Teardown: Deleting the overrule issue.');
			unset( $this->overruleIssueId );
		}
		unset( $this->postfix );
		unset( $this->utils );
		unset( $this->compare );
		unset( $this->pubChannelId );
		unset( $this->publicationId );
		unset( $this->ticket );
	}

	/**
	 * Remove a template object access rule.
	 *
	 * @param integer $publicationId The publication id.
	 * @param integer $issueId The issue id.
	 * @param array $templateObjects A list of template objects to be deleted.
	 */
	private function removeTemplateObjectAccess( $publicationId, $issueId, array $templateObjects )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveTemplateObjectsService.class.php';
		$request = new AdmRemoveTemplateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = $publicationId;
		$request->IssueId = $issueId;
		$request->TemplateObjects = $templateObjects;
		$this->utils->callService( $this, $request, 'Delete a template object access rule.' );
	}

	/**
	 * Builds a TemplateObjectAccess object.
	 *
	 * This function is initially used to subvert Zend Code Analyzer issues, where creating an object within every
	 * switch case made ZCA interpret the code wrongly.
	 *
	 * @param integer $objId The template object id.
	 * @param integer $userGroupId The user group id.
	 * @return AdmTemplateObjectAccess
	 */
	private function buildTemplateObjectAccess( $objId, $userGroupId )
	{
		return new AdmTemplateObjectAccess( $objId, $userGroupId );
	}
}