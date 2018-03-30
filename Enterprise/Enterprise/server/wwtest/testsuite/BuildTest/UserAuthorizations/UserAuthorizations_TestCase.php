<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_UserAuthorizations_UserAuthorizations_TestCase extends TestCase
{
	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflServicesUtils */
	private $testSuiteUtils = null;

	/** @var string admTicket */
	private $admTicket = '';

	/** @var string workflowTicket */
	private $workflowTicket = '';

	/** @var WW_TestSuite_Setup_WorkflowFactory */
	private $workflowFactory;

	/** @var int profileId */
	private $profileId;

	/** @var string profileName */
	private $profileName;

	/** @var FeatureProfile */
	private $featureProfile;

	/** @var string workflowUser */
	private $workflowUser;

	const APPLYCHARSTYLES_FEATURE_ID = 104;

	const APPLYCHARSTYLES_FEATURE_NAME = 'ApplyCharStyles';

	public function getDisplayName()
	{
		return 'Check user authorizations.  ';
	}

	public function getTestGoals()
	{
		return 'Checks if user rights are correctly applied. Both a client feature right and some workflow rights are tested.  ';
	}

	public function getTestMethods()
	{
		return 'Create a user(group), add access rights, check if the rights are correctly applied. The value of the '.
			'client feature is changed and the feature profile is removed (meaning no right). An abject is created in memory '.
			'and of this object the workflow rights are checked.' ;
	}

	public function getPrio()
	{
		return 1;
	}

	final public function runTest()
	{
		try {
			$this->setupTest();
			$this->testClientFeatures();
			$this->testWorkflowAccess();
			$this->teardownTest();
		} catch( BizException $e ) {
			$this->teardownTest();
		}
	}

	final public function setupTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->testSuiteUtils = new WW_Utils_TestSuite();
		$response = $this->testSuiteUtils->wflLogOn( $this );
		$this->admTicket = $response->Ticket;
		$this->assertNotNull( $this->admTicket );
		require_once BASEDIR.'/server/wwtest/testsuite/Setup/WorkflowFactory.class.php';
		$this->workflowFactory = new WW_TestSuite_Setup_WorkflowFactory( $this, $this->admTicket, $this->testSuiteUtils );
		$this->workflowFactory->setConfig( $this->getWorkflowConfig() );
		$this->workflowFactory->setupTestData();
		$this->profileId = $this->workflowFactory->getAuthorizationConfig()->getAccessProfileId( "Full %timestamp%" );
		$this->profileName = $this->resolveProfileName();
		$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "John %timestamp%" );
	}

	/**
	 * Sets and unsets the 'ApplyCharStyles' feature right in different ways. If a client feature is not set it is
	 * returned in the LogOn response. The LogOn response contains those features to which the user is not entitled.
	 * Unset means either the feature is not in the smart_profiletfeatures table or the value in this table is 'No'.
	 */
	private function testClientFeatures()
	{
		// Initially not set so, feature is not in the smart_profilefeatures table.
		$this->doLogOnAndResolveProfileFeature();
		$this->checkApplyCharStyle( true );
		$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );

		// Add the feature to the smart_profilefeatures table, set value to 'Yes'.
		require_once BASEDIR.'/server/dbclasses/DBAdmProfileFeature.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$feature = new AdmProfileFeature();
		$feature->Name = self::APPLYCHARSTYLES_FEATURE_NAME;
		$feature->DisplayName = BizResources::localize('ACT_APPLYCHARSTYLES');
		$feature->Value = 'Yes';
		DBAdmProfileFeature::updateProfileFeatures( $this->profileId, array( self::APPLYCHARSTYLES_FEATURE_ID => $feature ));
		$this->doLogOnAndResolveProfileFeature();
		$this->checkApplyCharStyle( false );
		$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );

		// Setting value to 'No' cannot be done with DBAdmProfileFeature::updateProfileFeatures() as this method removes
		// the record when the value of the feature is 'No'. A feature can have the value 'No' if it is inserted for
		// example when a database is created.
		$this->updateProfileFeatureRecord( 'No' );
		$this->doLogOnAndResolveProfileFeature();
		$this->checkApplyCharStyle( true );
		$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );
	}

	/**
	 * Test the workflow rights on the object created during the set up.
	 */
	private function testWorkflowAccess()
	{
		global $globAuth;
		$globAuth->getRights($this->workflowUser);
		$layout1 = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Layout %timestamp%' );
		require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
		$severityMapHandle = new BizExceptionSeverityMap( array( 'S1002' => 'INFO' ) );
		$hasRight =	BizAccess::checkRightsForMetaDataAndTargets( $this->workflowUser, 'C', false, $layout1->MetaData, array() );
		$this->assertFalse( $hasRight);
		$hasRight =	BizAccess::checkRightsForMetaDataAndTargets( $this->workflowUser, 'VREDW', false, $layout1->MetaData, array() );
		$this->assertTrue( $hasRight);
	}

	/**
	 * Updates the prifilefeature record directly in the database.
	 *
	 * @param string $value
	 */
	private function updateProfileFeatureRecord( string $value )
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$where = '`profile` = ? AND `feature` = ?';
		$params = array( $this->profileId, self::APPLYCHARSTYLES_FEATURE_ID );
		DBBase::updateRow('profilefeatures', array( 'value' => $value ), $where, $params );
	}

	/**
	 * Logs the workflow on and extracts the Features property.
	 */
	private function doLogOnAndResolveProfileFeature()
	{
		$this->testSuiteUtils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->RequestInfo = array( 'FeatureProfiles' ); // request to resolve ticket only
				$req->User = $this->workflowUser;
				$req->Password = 'ww';
			}
		);
		$response = $this->testSuiteUtils->wflLogOn( $this );
		$this->workflowTicket = $response->Ticket;
		$this->assertNotCount( 0, $response->FeatureProfiles );
		$this->resolveProfileFeaturesFromLogResponse( $response );
	}

	/**
	 * Extracts the features from the configured profile from the LogOn response.
	 *
	 * @param WflLogOnResponse $logOnResponse
	 */
	private function resolveProfileFeaturesFromLogResponse( WflLogOnResponse $logOnResponse )
	{
		foreach( $logOnResponse->FeatureProfiles as $featureProfile ) {
			if( $featureProfile->Name = $this->profileName ) {
				$this->featureProfile = $featureProfile;
				break;
			}
		}
	}

	/**
	 * Returns the name of the profile that was configured to be automatically creeated.
	 *
	 * @return string Name of the created profile.
	 */
	private function resolveProfileName()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
		$row = DBBase::getRow( 'profiles', '`id` = ?', array( 'profile' ), array( $this->profileId ) );
		$this->assertNotNull( $row );
		return $row['profile'];
	}

	/**
	 * Checks if the feature is set for the profile as it was returned by the LogOn response.
	 *
	 * @param $isSet
	 */
	private function checkApplyCharStyle( bool $isSet )
	{
		$found = false;
		foreach( $this->featureProfile->Features as $feature ) {
			if( $feature->Name == self::APPLYCHARSTYLES_FEATURE_NAME ) {
				$found = true;
				break;
			}
		}
		$actual = $found == true ? 'set ' : 'not set ';
		$expected = $isSet == true ? 'expected.' : 'not expected.';
		$message = self::APPLYCHARSTYLES_FEATURE_NAME.' feature is '.$actual.'while it is '.$expected;
		$this->assertEquals( $isSet, $found, $message );
	}

	private function teardownTest()
	{
		$this->workflowFactory->teardownTestData();
		$this->assertNotNull( $this->admTicket );
		$this->testSuiteUtils->wflLogOff( $this, $this->admTicket );
	}

	/**
	 * Compose a home brewed data structure which specifies the brand setup, user authorization and workflow objects.
	 *
	 * These are the admin entities to be automatically setup (and tear down) by the $this->workflowTicket utils class.
	 * It composes the specified layout objects for us as well but without creating/deleting them in the DB.
	 *
	 * @return stdClass
	 */
	private function getWorkflowConfig()
	{
		$config = <<<EOT
{
	"Publications": [{
		"Name": "PubTest1 %timestamp%",
		"PubChannels": [{
			"Name": "Print",
			"Type": "print",
			"PublishSystem": "Enterprise",
			"Issues": [{ "Name": "Week 35" },{ "Name": "Week 36" }],
			"Editions": [{ "Name": "North" },{ "Name": "South"	}]
		}],
		"States": [{
			"Name": "Layout Draft",
			"Type": "Layout",
			"Color": "FFFFFF"
		},{
			"Name": "Layout Ready",
			"Type": "Layout",
			"Color": "FFFFFF"
		}],
		"Categories": [{ "Name": "People" },{ "Name": "Sport" }]
	}],
	"Users": [{
		"Name": "John %timestamp%",
		"FullName": "John Smith %timestamp%",
		"Password": "ww",
		"Deactivated": false,
		"FixedPassword": false,
		"EmailUser": false,
		"EmailGroup": false
	}],
	"UserGroups": [{
		"Name": "Editors %timestamp%",
		"Admin": false
	}],
	"Memberships": [{
		"User": "John %timestamp%",
		"UserGroup": "Editors %timestamp%"
	}],
	"AccessProfiles": [{
		"Name": "Full %timestamp%",
		"ProfileFeatures": ["View", "Read", "Write", "Open_Edit", "Delete", "Purge" ]
	}],
	"UserAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "Editors %timestamp%",
		"AccessProfile": "Full %timestamp%"	
	}],
	"AdminAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "Editors %timestamp%"
	}],
	"Objects":[{
		"Name": "Layout %timestamp%",
		"Type": "Layout",
		"Format": "application/indesign",
		"FileSize": 1146880,
		"DocumentID": "xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4",
		"Comment": "Created by Build Test class: %classname%",
		"Publication": "PubTest1 %timestamp%",
		"Category": "People",
		"State": "Layout Ready",
		"Targets": [{
			"PubChannel": "Print",
			"Issue": "Week 35",
			"Editions": [ "North", "South" ]
		}]
	}]
}
EOT;

		$config = str_replace( '%classname%', __CLASS__, $config );
		$config = json_decode( $config );
		$this->assertNotNull( $config );
		return $config;
	}
}