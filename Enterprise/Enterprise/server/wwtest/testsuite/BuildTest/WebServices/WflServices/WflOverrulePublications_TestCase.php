<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflOverrulePublications_TestCase extends TestCase
{
	private $ticket = null;
	private $vars = null;
	private $publication = null;
	private $issueInfo = null;
	private $category = null;
	private $articleStatus = null;
	private $dossierStatus = null;
	private $printTarget = null; // Target
	private $pubChannel = null;
	private $admPubChannel = null; // Copy of the original publication channel
	private $originalAdmPubChannel = null; // Copy of the original publication channel
	private $utils = null; // WW_Utils_TestSuite
	private $wflServicesUtils = null; // WW_TestSuite_BuildTest_WebServices_WflServices_Utils

	private $overruledPublications = array();

	// Objects used for testing
	private $createdObjects = array(); // For cleanup
	private $dossiers = array();
	const MAX_DOSSIERS = 3;

	public function getDisplayName() { return 'Overrule Issues'; }
	public function getTestGoals()   { return 'Checks if invalid overruled brands states are detected in objects.'; }
	public function getPrio()        { return 160; }
	public function getTestMethods() { return
		 'Tests behavior of issues with overruled brands.
		 <ol>
		 	<li>Setup Overrule publications with statuses and categories</li>
		 	<li>Create an article object A1 for a regular brand and issue</li>
		 	<li>Change targets A1 to an overruled issue and matching status and category</li>
		 	<li>Change targets A1 to another overruled issue with matching status and category</li>
		 	<li>Remove object targets from A1 and set matching status and category</li>
		 	<li>Create article A2 with overrule issue directly set, including correct status and category</li>
		 	<li>Change state of two article objects using multi-set properties to a valid state</li>
		 	<li>Simulate creating an article object with an overrule issue from Content Station</li>
			<li>Try add multiple overruled brands to article A1 (negative test S1128)</li>
		 	<li>Try mix overruled brand with regular issues in article A1 (negative test S1128)</li>
		 	<li>Try remove an overruled brand (negative test S1128)</li>
			<li>Change state of two article objects using multi-set properties to an invalid state (negative test S1128)</li>
		 	<li>Create dossier D1 with a regular print target</li>
		 	<li>Create an issue-less article and add to dossier D1</li>
		 	<li>Create dossier D2 with an overruled brand as object target</li>
		 	<li>Created an article with overruled brand in dossier D2</li>
		 	<li>Create an article with overruled brand in dossier D2, but with wrong status (negative test S1128)</li>
		 	<li>Create issue-less object with wrong category in dossier D2 (negative test S1128)</li>
		 	<li>Change the "Current Issue" of the test publication channel to an Overrule Issue</li>
		 	<li>Repeat previous object tests with the new "Current Issue"</li>
		 	<li>Teardown test data</li>
		 </ol>';
	}

	final public function runTest()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
		$this->vars = $this->getSessionVariables();
		$this->ticket        = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->userGroup     = @$this->vars['BuildTest_WebServices_WflServices']['userGroup'];
		$this->publication   = @$this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->pubChannel    = @$this->vars['BuildTest_WebServices_WflServices']['printPubChannel'];
		$this->issueInfo     = @$this->vars['BuildTest_WebServices_WflServices']['printIssue'];
		$this->category      = @$this->vars['BuildTest_WebServices_WflServices']['category'];
		$this->articleStatus = @$this->vars['BuildTest_WebServices_WflServices']['articleStatus'];
		$this->dossierStatus = @$this->vars['BuildTest_WebServices_WflServices']['dossierStatus'];
		$this->printTarget   = @$this->vars['BuildTest_WebServices_WflServices']['printTarget'];

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();
		$this->wflServicesUtils->initTest( $this ); // also validates the BuildTest_WebServices_WflServices options above

		$this->articleStatus->Produce = null;

		do {
			if( !$this->setupTestData() ) {
				break;
			}

			$objectTestPrefix = 'OverruleTest';

			// Creates objects with overruled publications
			if( !$this->testCreateOverruledPublicationObject( $objectTestPrefix ) ) {
				break;
			}

			// Creates objects with overruled publications in dossiers
			if( !$this->testCreateOverruledPublicationObjectInDossiers( $objectTestPrefix ) ) {
				break;
			}

			// Repeat tests with an Overrule Issue set as current issue on the test publication channel.
			// The current issue is used as default issue, but when it's an overruled issue it should be
			// ignored in various scenarios.
			$this->admPubChannel->CurrentIssue = $this->overruledPublications[0]['issue']->Id;
			$this->utils->modifyAdmPubChannel( $this, $this->ticket, $this->publication->Id, $this->admPubChannel );

			$objectTestPrefix = 'OverruleTest2';

			// Creates objects with overruled publications (pubchan current issue is first overrule issue)
			if( !$this->testCreateOverruledPublicationObject( $objectTestPrefix ) ) {
				break;
			}

			// Creates objects with overruled publications in dossiers (pubchan current issue is first overrule issue)
			if( !$this->testCreateOverruledPublicationObjectInDossiers( $objectTestPrefix ) ) {
				break;
			}

		} while( false );

		$tipMsg = 'Tearing down the test data.';
		$this->tearDownTestData( $tipMsg );

		$this->setSessionVariables( $this->vars );
	}

	/**
 	 * Creates issues with overruled brands.
	 *
	 * @return bool Whether or not the setup was successful.
	 */
	private function setupTestData()
	{
		$retVal = true;

		do {
			// Enable the CS Overrule Compatibility server plugin.
			$this->didActivate = $this->utils->activatePluginByName( $this, 'ContentStationOverruleCompatibility' );
			if( is_null($this->didActivate) ) {
				return false;
			}
			
			// Store original test publication channel
			$this->admPubChannel = $this->utils->getAdmPubChannel( $this, $this->ticket, $this->publication->Id,
				$this->pubChannel->Id, 'Getting the Adm publication channel' );
			$this->originalAdmPubChannel = clone $this->admPubChannel;

			if( !$this->setupAdmOverruledPublications() ) {
				$retVal = false;
				break;
			}
		} while( false );

		return $retVal;
	}

	/**
	 * Removes created objects during testing and removes issues with overruled brands.
	 *
	 * @param string $tipMsg To be used in the error message if there's any error.
	 */
	private function tearDownTestData( $tipMsg )
	{
		// Restore old publication channel
		$this->utils->modifyAdmPubChannel( $this, $this->ticket, $this->publication->Id, $this->originalAdmPubChannel );

		$i = 1;
		if( $this->createdObjects ) foreach( $this->createdObjects as $object ) {
			$errorReport = null;
			$id = $object->MetaData->BasicMetaData->ID;
			$stepInfo = 'Tear down object #'.$i.'.';
			if( !$this->utils->deleteObject( $this, $this->ticket, $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR',  'Could not tear down object #'.$i.'.'.$errorReport, $tipMsg );
			}
			$i++;
		}

		$this->dossiers = array(); // clear cache

		$this->cleanupAdmOverruledPublications();

		// If we did enable, disable the CS Overrule Compatibility server plugin again.
		if( $this->didActivate === true ) {
			$this->utils->deactivatePluginByName( $this, 'ContentStationOverruleCompatibility' );
		}
	}

	/**
	 * Creates issues with overruled brands for testing.
	 *
	 * @return bool
	 */
	private function setupAdmOverruledPublications()
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$retVal = true;

		for( $i = 0; $i < 2; $i++ ) {
			// Make a new issue
			$issue = new AdmIssue();

			$issueName                   = 'OvPubIss_' . date('dmy_his') . '_' . $i;
			$issue->Name                 = $issueName;
			$issue->Description          = 'Created Issue';
			$issue->SortOrder            = 2;
			$issue->EmailNotify          = false;
			$issue->ReversedRead         = false;
			$issue->OverrulePublication  = true;
			$issue->Deadline             = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d')+1, date('Y')));
			$issue->PublicationDate      = date('Y-m-d\TH:i:s', mktime( 0, 0, 0, date('m'), date('d'), date('Y')));
			$issue->ExpectedPages        = 32;
			$issue->Subject              = 'Build Test for the CreateIssues service';
			$issue->Activated            = true;
			$issue->ExtraMetaData        = null;

			$resp = $this->createIssue( $issue, $this->publication->Id, $this->pubChannel->Id );
			$recvIssue1 = $resp->Issues[0];
			$issueId = $recvIssue1->Id;

			// Add States
			$dossierStatusName = $issue->Name . '_DossSta';
			$dossierStatusObj = $this->wflServicesUtils->createStatus( $dossierStatusName, 'Dossier', $this->publication->Id, 0, $issueId  );
			$articleStatusName = $issue->Name . '_ArtSta';
			$articleStatusObj = $this->wflServicesUtils->createStatus( $articleStatusName, 'Article', $this->publication->Id, 0, $issueId );

			if( !$dossierStatusObj || !$articleStatusObj ) {
				$this->setResult( 'ERROR', 'Could not create statuses for Overrule Issues' );
				$retVal = false;
				break;
			}

			// Add categories
			$categoryName = $issue->Name . '_category';
			$stepInfo = 'Creating category for Overrule Issue ' . $issueId;
			$category = $this->wflServicesUtils->createCategory( $this->publication->Id, $stepInfo, $categoryName, $issueId );

			$dossierColor = $dossierStatusObj->Color;
			$articleColor = $articleStatusObj->Color;
			BizAdmStatus::restructureMetaDataStatusColor( $articleStatusObj->Id, $articleColor );
			BizAdmStatus::restructureMetaDataStatusColor( $dossierStatusObj->Id, $dossierColor );

			// Store it
			// 'Produce' is not set because the set properties response always seems to return null for Produce
			$this->overruledPublications[] = array(
				'issue' => new Issue( $issueId, $issueName, true ),
				'articleStatus' => new State( $articleStatusObj->Id, $articleStatusObj->Name, $articleStatusObj->Type,
						null /*$articleStatusObj->Produce*/, $articleColor ),
				'dossierStatus' => new State( $dossierStatusObj->Id, $dossierStatusObj->Name, $dossierStatusObj->Type,
						null /*$dossierStatusObj->Produce*/, $dossierColor ),
				'category' => $category,
			);

			// Use TESTSUITE defined test user (for wwtest)
			$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
			if( !$suiteOpts ){
				$this->setResult( 'ERROR', 'Could not find the test user: ',
					'Please check the TESTSUITE setting in configserver.php.' );
				$retVal = false;
				break;
			}

			// Get our user group
			require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
			$request = new AdmGetUserGroupsRequest();
			$request->Ticket = $this->ticket;
			$request->RequestModes = array();
			$response = $this->utils->callService( $this, $request, 'Getting test suite user group' );

			$groupId = null;
			foreach( $response->UserGroups as $userGroupObj ) {
				if( $userGroupObj->Name == $this->userGroup->Name ) {
					$groupId = $userGroupObj->Id;
					break;
				}
			}
			if( is_null( $groupId ) ) {
				$this->setResult( 'ERROR', 'Could not find the test user group id: ',
					'Please check the TESTSUITE setting in configserver.php and the user groups.' );
				$retVal = false;
				break;
			}

			$targetProfileName = 'Full Control';
			$profileId = null;
			$profiles = $this->profiles();
			foreach( $profiles as $profId => $profileName ) {
				if( $profileName == $targetProfileName ) {
					$profileId = $profId;
					break;
				}
			}

			if( is_null( $profileId ) ) {
				$this->setResult( 'ERROR', 'Could not find a profile id.',
					'Please make sure "'.$targetProfileName.'" profile exists for test suite user.' );
				$retVal = false;
				break;
			}

			$id = $this->wflServicesUtils->addAuthorization( $this->publication->Id, $issueId, $groupId, 0, 0, $profileId );
			if( is_null( $id ) ) {
				$this->setResult( 'ERROR', 'Failed to add authorization for overruled brand.',
					'' );
				$retVal = false;
				break;
			}
		}

		return $retVal;
	}

	/**
	 * Deletes created overruled publications including their statuses and categories
	 */
	private function cleanupAdmOverruledPublications()
	{
		$issueIds = array();
		foreach( $this->overruledPublications as $overrulePub ) {
			$issueIds[] = $overrulePub['issue']->Id;
		}
		$this->deleteIssues( $issueIds, $this->publication->Id );
	}

	/**
	 * Creates an issue at the DB through the CreateIssues admin web service.
	 *
	 * @param AdmIssue $issue
	 * @param $publicationId
	 * @param $pubChannelId
	 * @return AdmCreateIssuesResponse
	 */
	private function createIssue( AdmIssue $issue, $publicationId, $pubChannelId )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = $publicationId;
		$request->PubChannelId = $pubChannelId;
		$issue = unserialize( serialize( $issue ) );
		$request->Issues = array( $issue );
		$response = $this->utils->callService( $this, $request, 'Buildtest Overruled Brand' );
		return $response;
	}

	/**
	 * Delete issues through the DeleteIssues admin web service.
	 *
	 * @param array $issuesToBeDeleted Array of issues id to be deleted
	 * @param $publicationId
	 */
	private function deleteIssues( $issuesToBeDeleted, $publicationId )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
		$request = new AdmDeleteIssuesRequest();
		$request->Ticket                = $this->ticket;
		$request->PublicationId         = $publicationId;
		$request->IssueIds              = $issuesToBeDeleted;
		/* $response = */ $this->utils->callService( $this, $request, 'Delete Issues' );
	}

	/**
	 * Returns all available control profile names.
	 *
	 * @return array
	 */
	private function profiles()
	{
		$dbh = DBDriverFactory::gen();
		$dbp = $dbh->tablename("profiles");

		$sql = "SELECT * FROM $dbp ORDER BY `code`, `profile`";
		$sth = $dbh->query($sql);
		$arr = array();
		while (($row = $dbh->fetch($sth)) ) {
			$arr[$row["id"]] = $row["profile"];
		}
		return $arr;
	}

	/**
	 * Tests creating and using overruled publications.
	 *
	 * @param string $objectTestPrefix Prefix used for creating test objects
	 * @return bool Whether or not the validation succeeded.
	 */
	private function testCreateOverruledPublicationObject( $objectTestPrefix )
	{
		$retVal = true;

		do {
			$ticket = $this->ticket;
			$regularPub = $this->publication;
			$overrulePub1 = $this->overruledPublications[0];
			$overrulePub2 = $this->overruledPublications[1];

			$targetIssue = $this->composeTarget( $this->pubChannel, $this->issueInfo );
			$targetPub1 = $this->composeTarget( $this->pubChannel, $overrulePub1['issue'] );
			$targetPub2 = $this->composeTarget( $this->pubChannel, $overrulePub2['issue'] );

			// ---- Positive tests ----
			// Create an issue-less regular article object
			$articleName = $objectTestPrefix.'Article_' .date("m d H i s");
			$articleObj = $this->createArticle( 'Creating a regular object', $articleName, $regularPub,
												array(), $this->articleStatus, $this->category );
			if( !$articleObj ) {
				$retVal = false;
				break;
			}
			$this->createdObjects[] = $articleObj;

			// Change issue to overrulePub1 (change from regular pub to a overruled pub)
			$articleObj->Targets = array( $targetPub1 );
			$articleObj->MetaData->WorkflowMetaData->State = $overrulePub1['articleStatus'];
			$articleObj->MetaData->BasicMetaData->Category = $overrulePub1['category'];
			$changedPropPaths = array(
				'WorkflowMetaData->State->Id' => $overrulePub1['articleStatus']->Id,
				'BasicMetaData->Category->Id' => $overrulePub1['category']->Id,
			);

			if( !$this->utils->setObjectProperties( $this, $ticket, $articleObj, 'Changing Article Object Target to Overruled pub 1', null, $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Change issue to overrulePub2 (change from one overrule pub to another one)
			$articleObj->Targets = array( $targetPub2 );
			$articleObj->MetaData->WorkflowMetaData->State = $overrulePub2['articleStatus'];
			$articleObj->MetaData->BasicMetaData->Category = $overrulePub2['category'];
			$changedPropPaths = array(
				'WorkflowMetaData->State->Id' => $overrulePub2['articleStatus']->Id,
				'BasicMetaData->Category->Id' => $overrulePub2['category']->Id,
			);

			if( !$this->utils->setObjectProperties( $this, $ticket, $articleObj, 'Changing Article Object Target to Overruled pub 2', null, $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Make article object issue-less (i.e. remove object targets) and set status/category to one valid from the brand
			$category = new Category( $this->category->Id, $this->category->Name );
			$articleObj->Targets = array();
			$articleObj->MetaData->WorkflowMetaData->State = $this->articleStatus;
			$articleObj->MetaData->BasicMetaData->Category = $category;
			$changedPropPaths = array(
				'WorkflowMetaData->State->Id' => $this->articleStatus->Id,
				'BasicMetaData->Category->Id' => $category->Id,
			);

			if( !$this->utils->setObjectProperties( $this, $ticket, $articleObj, 'Making article issue-less with a valid status and category', null, $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Create article object with overrule issue directly set
			$articleName = $objectTestPrefix.'Article2_' .date("m d H i s");
			$articleObj2 = $this->createArticle( 'Creating an overruled object', $articleName, $regularPub,
				array($overrulePub1['issue']), $overrulePub1['articleStatus'], $overrulePub1['category'] );
			if( !$articleObj2 ) {
				$retVal = false;
				break;
			}
			$this->createdObjects[] = $articleObj2;

			// Create another article object with overrule issue directly set
			$articleName = $objectTestPrefix.'Article3_' .date("m d H i s");
			$articleObj3 = $this->createArticle( 'Creating an overruled object', $articleName, $regularPub,
				array($overrulePub1['issue']), $overrulePub1['articleStatus'], $overrulePub1['category'] );
			if( !$articleObj3 ) {
				$retVal = false;
				break;
			}
			$this->createdObjects[] = $articleObj3;

			// Do a multi-set object properties
			$updateProps = array();
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'StateId';
			$propValue = new PropertyValue();
			$propValue->Value = $overrulePub1['articleStatus']->Id;
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			$stepInfo = 'Changing article status of overruled objects using multi-set object properties.';
			$expectedErrors = array();
			$changedPropPaths = array(
				'MetaData->WorkflowMetaData->State->Id' => $overrulePub1['articleStatus']->Id,
			);
			if( !$this->utils->multiSetObjectProperties( $this, $this->ticket,  array( $articleObj2, $articleObj3 ),
				$stepInfo, $expectedErrors, $updateProps, $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Simulate creating an article object with an overrule issue from Content Station.
			$articleName = $objectTestPrefix.'Article4_'.date( 'm d H i s' );
			$csPublication = new Publication( ":{$regularPub->Id}:{$overrulePub1['issue']->Id}");
			$articleObj4 = $this->buildArticleObject( null, $articleName, $csPublication, array( $overrulePub1['issue'] ), $overrulePub1['articleStatus'], $overrulePub1['category'], array() );
			if( !$articleObj4 ) {
				$retVal = false;
				break;
			}
			$this->createObjectFromContentStation( $articleObj4, 'Creating a Content Station article.' );
			$this->createdObjects[] = $articleObj4;

			// ---- Negative tests ----
			// Try add multiple overrule publications as object targets
			$articleObj->Targets = array( $targetPub1, $targetPub2 );
			$articleObj->MetaData->WorkflowMetaData->State = $overrulePub1['articleStatus'];
			$articleObj->MetaData->BasicMetaData->Category = $overrulePub1['category'];
			$changedPropPaths = array(
				'WorkflowMetaData->State->Id' => $overrulePub1['articleStatus']->Id,
				'BasicMetaData->Category->Id' => $overrulePub1['category']->Id,
			);

			if( !$this->utils->setObjectProperties( $this, $ticket, $articleObj, 'Try add multiple overrule publications', '(S1019)', $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Try mix an overrule publication with regular issues (multiple object targets)
			$articleObj->Targets = array( $targetPub1, $targetIssue );
			$articleObj->MetaData->WorkflowMetaData->State = $overrulePub1['articleStatus'];
			$articleObj->MetaData->BasicMetaData->Category = $overrulePub1['category'];
			$changedPropPaths = array(
				'WorkflowMetaData->State->Id' => $overrulePub1['articleStatus']->Id,
				'BasicMetaData->Category->Id' => $overrulePub1['category']->Id,
			);

			if( !$this->utils->setObjectProperties( $this, $ticket, $articleObj, 'Try mix an overrule publication with regular issues (multiple object targets)', '(S1019)', $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Try remove an overrule publication without changing the status and category
			$articleObj2->Targets = array();
			$articleObj2->MetaData->WorkflowMetaData->State = $overrulePub1['articleStatus'];
			$articleObj2->MetaData->BasicMetaData->Category = $overrulePub1['category'];
			$changedPropPaths = array(
				'WorkflowMetaData->State->Id' => $overrulePub1['articleStatus']->Id,
				'BasicMetaData->Category->Id' => $overrulePub1['category']->Id,
			);

			if( !$this->utils->setObjectProperties( $this, $ticket, $articleObj2, 'Try remove an overrule publication without changing the status and category', '(S1017)', $changedPropPaths ) ) {
				$retVal = false;
				break;
			}

			// Do a multi-set object properties with an invalid status
			// This reports back as "Invalid Status" with type set to "State" and "Id" to the state id
			$updateProps = array();
			$mdValue = new MetaDataValue();
			$mdValue->Property = 'StateId';
			$propValue = new PropertyValue();
			$propValue->Value = $this->articleStatus->Id;
			$mdValue->PropertyValues = array( $propValue );
			$updateProps[] = $mdValue;

			$stepInfo = 'Changing article status of overruled objects using multi-set object properties.';

			$expectedReports = array();
			$report = new ErrorReport();
			$report->BelongsTo = new ErrorReportEntity( 'State', $this->articleStatus->Id );
			$report->Entries = array( new ErrorReportEntry( null, null, null, 'S1128', 'Error' ) );
			$expectedReports[] = $report;

			$changedPropPaths = array();
			$exclObjIds = array( $articleObj2->MetaData->BasicMetaData->ID, $articleObj3->MetaData->BasicMetaData->ID );
			if( !$this->utils->multiSetObjectProperties( $this, $this->ticket,  array( $articleObj2, $articleObj3 ),
				$stepInfo, $expectedReports, $updateProps, $changedPropPaths, $exclObjIds ) ) {
				$retVal = false;
				break;
			}

		} while( false );

		return $retVal;
	}

	/**
	 * Tests creating and using overruled publications in dossiers.
	 *
	 * @param string $objectTestPrefix Prefix used for creating test objects
	 * @return bool Whether or not the validation succeeded.
	 */
	private function testCreateOverruledPublicationObjectInDossiers( $objectTestPrefix )
	{
		$retVal = true;
		do {
			$publication = $this->publication;
			$overrulePub1 = $this->overruledPublications[0];

			// ---- Positive tests ----
			// Create a dossier with a regular print target
			$dossierName = $objectTestPrefix.'Dossier1_' .date("m d H i s");
			$dossier = $this->composeDossier( $dossierName, $publication, array( $this->issueInfo ),
				$this->category, $this->dossierStatus );
			if( !$this->createObject( $dossier, 'Creating dossier for built test.' ) ) {
				$this->setResult( 'ERROR',  'Could not create Dossier object.', '' );
				$retVal = false;
				break;
			}
			$this->createdObjects[] = $dossier;

			// Create issue-less article object and add to dossier
			$relation = new Relation();
			$relation->Parent = $dossier->MetaData->BasicMetaData->ID;
			$relation->Child = null;
			$relation->Type = 'Contained';

			$articleName = $objectTestPrefix.'ArticleDossier_' .date("m d H i s");
			$articleObj = $this->createArticle( 'Creating a regular object', $articleName, $publication,
				array(), $this->articleStatus, $this->category, array( $relation ) );
			if( !$articleObj ) {
				$retVal = false;
				break;
			}
			$this->createdObjects[] = $articleObj;

			// Create a dossier with an overruled publication issue target
			$dossierName = $objectTestPrefix.'Dossier2_' .date("m d H i s");
			$dossierOverruled = $this->composeDossier( $dossierName, $publication, array( $overrulePub1['issue'] ),
				$overrulePub1['category'], $overrulePub1['dossierStatus'] );
			if( !$this->createObject( $dossierOverruled, 'Creating dossier for built test with overruled issue.' ) ) {
				$this->setResult( 'ERROR',  'Could not create Dossier object with overruled issue.', '' );
				$retVal = false;
				break;
			}
			$this->createdObjects[] = $dossierOverruled;

			// Create overruled publication article object and add to dossier
			$relation = new Relation();
			$relation->Parent = $dossierOverruled->MetaData->BasicMetaData->ID;
			$relation->Child = null;
			$relation->Type = 'Contained';

			$articleName = $objectTestPrefix.'ArticleDossier2_' .date("m d H i s");
			$articleObj2 = $this->createArticle( 'Creating a regular object', $articleName, $publication,
				array($overrulePub1['issue']), $overrulePub1['articleStatus'], $overrulePub1['category'], array( $relation ) );
			if( !$articleObj2 ) {
				$retVal = false;
				break;
			}
			$this->createdObjects[] = $articleObj2;

			// ---- Negative tests ----
			// Create overruled publication object with wrong status and add to dossier
			$relation = new Relation();
			$relation->Parent = $dossierOverruled->MetaData->BasicMetaData->ID;
			$relation->Child = null;
			$relation->Type = 'Contained';

			$articleName = $objectTestPrefix.'ArticleDossier3_' .date("m d H i s");
			$this->createArticle( 'Creating an overruled publication object with invalid status', $articleName, $publication,
				array($overrulePub1['issue']), $this->articleStatus, $overrulePub1['category'], array( $relation ), '(S1017)' );
			if( $this->hasError() ) {
				$retVal = false;
				break;
			}

			// Create issue-less object with invalid category and add to dossier
			$relation = new Relation();
			$relation->Parent = $dossierOverruled->MetaData->BasicMetaData->ID;
			$relation->Child = null;
			$relation->Type = 'Contained';

			$articleName = $objectTestPrefix.'ArticleDossier4_' .date("m d H i s");
			$this->createArticle( 'Creating an issue-less object with invalid category', $articleName, $publication,
				array(), $this->articleStatus, $overrulePub1['category'], array( $relation ), '(S1129)' );
			if( $this->hasError() ) {
				$retVal = false;
				break;
			}

		} while( false );

		return $retVal;
	}

	/**
	 * Composes a dossier object in memory.
	 *
	 * @param string $dossierName
	 * @param Publication $publication
	 * @param array $issueTargets
	 * @param Category $category
	 * @param State $state
	 * @return Object Dossier object.
	 */
	private function composeDossier( $dossierName, $publication, $issueTargets, $category, $state )
	{
		// Ensure they are the right type
		$publication = new Publication( $publication->Id, $publication->Name );
		$category = new Category( $category->Id, $category->Name );

		// Compose empty MetaData structure.
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->WorkflowMetaData = new WorkflowMetaData();

		// Fill in dossier properties.
		$metaData->BasicMetaData->Name = $dossierName;
		$metaData->BasicMetaData->Type = 'Dossier';
		$metaData->BasicMetaData->Publication = $publication;
		$metaData->BasicMetaData->Category = $category;
		$metaData->ContentMetaData->Description =
			'Temporary dossier created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData->State = $state;

		// Compose the dossier object.
		$dossier = new Object();
		$dossier->MetaData = $metaData;

		foreach( $issueTargets as $issueObj ) {
			$dossier->Targets[] = $this->composeTarget( $this->pubChannel, $issueObj );
		}

		return $dossier;
	}

	/**
	 * Builds workflow object for an article.
	 *
	 * @param int $articleId
	 * @param string $articleName
	 * @param $publication
	 * @param $issues
	 * @param $status
	 * @param $category
	 * @param array, $relations
	 * @return Object. Null on error.
	 */
	private function buildArticleObject( $articleId, $articleName, $publication, $issues, $status, $category, $relations )
	{
		// Setup an attachment for the article that holds some plain text content (in memory)
		$content = 'To temos aut explabo. Ipsunte plat. Em accae eatur? Ihiliqui oditatem. Ro ipicid '.
			'quiam ex et quis consequae occae nihictur? Giantia sim alic te volum harum, audionseque '.
			'rem vite nobitas perrum faccuptias sunt fugit eliquatint velit a aut milicia consecum '.
			'veribus auda ides ut quia commosa quam et moles iscil mo conseque magnim quis ex ex eaquamet '.
			'ut adi dolor mo odis magnihi ligendit ut to dendron quatumquam labor renis pe con eos '.
			'magnima gnatiur sitaepeles quatia namus ni aut adit at ad quundem laudia qui ut ratempe '.
			'rnatestorro te por alis acidunt volore nobit harciminum re eatus repudiatem ame prati bere '.
			'cus minveliquis serum, ute velecus cipiciur, occum nulpario quat fugitatur, nihillu ptatqui '.
			'ventibus doluptatur? Dus alique nonectoribus inciend elenim di sunt que mollis autempo ribus. '.
			'Totatent peliam aut facipsuntur aut pra quam es rem abo.';
		$attachment = new Attachment();
		$attachment->Rendition = 'native';
		$attachment->Type = 'text/plain';
		$attachment->Content = $content;

		// Build the article object (in memory)
		$fileSize = strlen($content);
		$meta = $this->buildArticleMetaData( $articleId, $articleName, $fileSize, $publication, $status, $category );
		if( !$meta ) {
			return null; // error handled above
		}
		$articleObj = new Object();
		$articleObj->MetaData = $meta;
		$articleObj->Files = array( $attachment );

		foreach( $issues as $issueObj ) {
			$articleObj->Targets[] = $this->composeTarget( $this->pubChannel, $issueObj );
		}

		$articleObj->Relations = $relations;

		return $articleObj;
	}

	/**
	 * Builds workflow MetaData
	 *
	 * @param int $articleId
	 * @param string $articleName
	 * @param int $fileSize
	 * @param Publication $publication
	 * @param State $status
	 * @param Category $category
	 * @return MetaData. Null on error.
	 */
	private function buildArticleMetaData( $articleId, $articleName, $fileSize, $publication, $status, $category )
	{
		// Make sure it's a Publication/Category/State type
		$publication = new Publication( $publication->Id, $publication->Name );
		$category = new Category( $category->Id, $category->Name );
		$state = $status;

		// retrieve user (shortname) of the logOn test user.
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$user = BizSession::checkTicket( $this->ticket );

		// build metadata
		$basMD = new BasicMetaData();
		$basMD->ID = $articleId;
		$basMD->DocumentID = null;
		$basMD->Name = $articleName;
		$basMD->Type = 'Article';
		$basMD->Publication = $publication;
		$basMD->Category = $category;

		$srcMD = new SourceMetaData();
		$srcMD->Author = $user;
		$rigMD = new RightsMetaData();
		$rigMD->Copyright = 'copyright';
		$cntMD = new ContentMetaData();
		$cntMD->Keywords = array("Key", "word");
		$cntMD->Slugline = 'slug';
		$cntMD->Width = 123;
		$cntMD->Height = 45;
		$cntMD->Format = 'text/plain';
		$cntMD->FileSize = $fileSize;
		$cntMD->Columns = 4;
		$cntMD->LengthWords = 300;
		$cntMD->LengthChars = 1200;
		$cntMD->LengthParas = 4;
		$cntMD->LengthLines = 12;
		$cntMD->PlainContent = 'some searchable content';
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline = date('Y-m-d\TH:i:s');
		$wflMD->Urgency = 'Top';
		$wflMD->State = $state;
		$wflMD->RouteTo = $user;
		$wflMD->Comment = 'Creating Object for Overruled publications build test';
		$extMD = array();

		$md = new MetaData();
		$md->BasicMetaData    = $basMD;
		$md->RightsMetaData   = $rigMD;
		$md->SourceMetaData   = $srcMD;
		$md->ContentMetaData  = $cntMD;
		$md->WorkflowMetaData = $wflMD;
		$md->ExtraMetaData    = $extMD;
		return $md;
	}

	/**
	 * Creates a new article.
	 *
	 * @param string $stepInfo Step description used in case of an error
	 * @param string $articleName Name used for object creation
	 * @param Publication $publication
	 * @param Issue[] $issues
	 * @param State $status
	 * @param Category $category
	 * @param array $relations
	 * @param string $expectedError The expected S code error if any
	 * @return null|Object The created object, or null in case of error(s)
	 */
	private function createArticle( $stepInfo, $articleName, $publication, $issues, $status, $category, $relations = array(), $expectedError = null )
	{
		$articleObj = $this->buildArticleObject( null, $articleName, $publication, $issues, $status, $category, $relations );
		if( !$this->utils->uploadObjectsToTransferServer( $this, array($articleObj) ) ) {
			return null;
		}
		if( !$this->createObject( $articleObj, $stepInfo, false /* lock */, $expectedError ) ) {
			return null;
		}
		return $articleObj;
	}

	/**
	 * Creates an object in the database.
	 *
	 * @param Object $object The object to be created. On success, it gets updated with latest info from DB.
	 * @param string $stepInfo Extra logging info.
	 * @param bool $lock Whether or not the lock the object.
	 * @param string|null $expectedError S-code when error expected. NULL when no error expected.
	 * @return bool Whether or not service response was according to given expectations ($expectedError).
	 */
	private function createObject( /** @noinspection PhpLanguageLevelInspection */ Object &$object, $stepInfo, $lock = false, $expectedError = null )
	{
		$response = $this->utils->callCreateObjectService( $this, $this->ticket, array( $object ), $lock, $stepInfo, $expectedError );

		if( isset($response->Objects[0]) ) {
			$object = $response->Objects[0];
		}
		return ($response && !$expectedError) || (!$response && $expectedError);
	}

	/**
	 * Simulates a create objects request sent from Content Station.
	 *
	 * @param Object $object The object to be created. On success, it gets updated with the latest info from the database.
	 * @param string $stepInfo Extra logging info.
	 * @param bool $lock Whether or not to lock the object.
	 * @param null $expectedError S-code when an error is expected, null if it is not.
	 * @return boolean Whether or not service response was according to given expectations ($expectedError).
	 */
	private function createObjectFromContentStation( /** @noinspection PhpLanguageLevelInspection */ Object &$object,
		$stepInfo, $lock = false, $expectedError = null )
	{
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
		if( $suiteOpts ) {
			require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
			$request = new WflLogOnRequest( );
			$request->User = $suiteOpts['User'];
			$request->Password = $suiteOpts['Password'];
			$request->Ticket = null;
			$request->Server = 'Enterprise Server';
			$request->ClientName = null;
			$request->Domain = '';
			$request->ClientAppName = 'Content Station Buildtest';
			$request->ClientAppVersion = 'v'.SERVERVERSION;
			$response = $this->utils->callService( $this, $request, 'Logging in a new user through SOAP.', null, 'SOAP' );
			$soapTicket = $response->Ticket;

			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer( $object->Files[0]->Content, $object->Files[0] );
			$transferServer->switchFilePathToURL( $object );

			if( OverruleCompatibility::isContentStation( $soapTicket ) ) {
				require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
				$request = new WflCreateObjectsRequest();
				$request->Ticket	= $soapTicket;
				$request->Lock		= $lock;
				$request->Objects	= array( $object );
				$response = $this->utils->callService( $this, $request, $stepInfo, $expectedError, 'SOAP' );
			}

			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$request = new WflLogOffRequest();
			$request->Ticket = $soapTicket;
			$this->utils->callService( $this, $request, 'Logging off a user through SOAP.', null, 'SOAP' );

			if( isset($response->Objects[0]) ) {
				$object = $response->Objects[0];
			}
			return ($response && !$expectedError) || (!$response && $expectedError);
		} else {
			return false;
		}
	}

	/**
	 * Builds a Target from given channel, issue and editions.
	 *
	 * @param PubChannelInfo $chanInfo
	 * @param IssueInfo|Issue $issueInfo
	 * @return Target $target
	 */
	private function composeTarget( PubChannelInfo $chanInfo, $issueInfo )
	{
		$pubChannel = new PubChannel();
		$pubChannel->Id = $chanInfo->Id;
		$pubChannel->Name = $chanInfo->Name;

		$issue = new Issue();
		$issue->Id   = $issueInfo->Id;
		$issue->Name = $issueInfo->Name;
		$issue->OverrulePublication = $issueInfo->OverrulePublication;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = $chanInfo->Editions;

		return $target;
	}
}