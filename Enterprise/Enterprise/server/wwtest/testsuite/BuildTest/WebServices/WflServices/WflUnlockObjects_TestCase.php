<?php
/**
 * @package   Enterprise
 * @subpackage   TestSuite
 * @since      v10.4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class  WW_TestSuite_BuildTest_WebServices_WflServices_WflUnlockObjects_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite $testSuiteUtils */
	private $testSuiteUtils = null;

	/** @var string $workflowTicket */
	private $workflowTicket = '';

	/** @var WW_TestSuite_Setup_WorkflowFactory $workflowFactory */
	private $workflowFactory;

	/** @var int $profileId */
	private $profileId;

	/** @var FeatureProfile $featureProfile */
	private $featureProfile;

	/** @var string $workflowUser */
	private $workflowUser;

	/** @var Object $testLayout */
	private $testLayout;

	/** @var Attachment[] $transferServerFiles Layout files copied to the transfer server folder. */
	private $transferServerFiles = array();

	/** @var BizTransferServer $transferServer */
	private $transferServer;

	/** @var array $vars Placeholder for the session variables. */
	private $vars;

	/** @var string $testStartTime Used for cleaning up of IDSA jobs.*/
	private $testStartTime = null;

	/** @var string $appName Name of the application */
	private $appName = '';

	// Step#01: Fill in the TestGoals, TestMethods and Prio...
	public function getDisplayName()
	{
		return 'Unlock Objects';
	}

	public function getTestGoals()
	{
		return 'Checks if object can get unlocked only by the user who locks the object or the Brand Admin, ' .
			'other users will not be allowed to do the unlock to this object.';
	}

	public function getTestMethods()
	{
		return 'Scenario:<ol>'.
			'<li>Scenario:</li>'.
			'<li>Create users and groups, add access rights for these groups for a newly created brand.</li>'.
			'<li>An object is created.</li>'.
			'<li>This object is locked by user "John" on client application A.</li>'.
			'<li>This object is unlocked by user "Jim" on client application A. This fails.</li>'.
			'<li>This object is unlocked by user "John" on client application B. This is successful.</li>'.
			'<li>This object is locked again by user "John" on client application A.</li>'.
			'<li>This object is unlocked by a brand admin user on client application B. This is successful.</li>'.
			'<li>Test data is cleaned up.</li>'.
			'</ol>';
	}

	public function getPrio()
	{
		return 195;
	}

	public function isSelfCleaning()
	{
		return true;
	}

	/**
	 * Runs the test.
	 */
	final public function runTest()
	{
		try {
			$this->setupTest();
			$this->testWorkflowAccess();
			$this->teardownTest();
		} catch( BizException $e ) {
			$this->teardownTest();
		}
	}

	/**
	 * Set up test data.
	 *
	 * Brand, user(groups) etc..
	 */
	final public function setupTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->testSuiteUtils = new WW_Utils_TestSuite();
		$this->vars = $this->getSessionVariables();
		$admTicket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$message = 'Please enable the Setup test data test.';
		$this->assertNotNull( $admTicket, $message );
		require_once BASEDIR.'/server/wwtest/testsuite/Setup/WorkflowFactory.class.php';
		$this->workflowFactory = new WW_TestSuite_Setup_WorkflowFactory( $this, $admTicket, $this->testSuiteUtils );
		$this->workflowFactory->setConfig( $this->getWorkflowConfig() );
		$this->workflowFactory->setupTestData();
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		$this->testStartTime = date('Y-m-d\TH:i:s');
	}

	/**
	 * Test the lock object right on the created object.
	 *
	 * First log on with the user that has sufficient rights. Create the object. First lock the object by the user that
	 * is entitled, next by a user that has not sufficient rights. In that case an 'S1002' error is expected.
	 */
	private function testWorkflowAccess()
	{
		// User 'John' creates the layout and locks with client application A.
		$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "John %timestamp%" );
		$this->appName = 'Client-A';
		$this->doLogOn();
		$this->testLayout = $this->createLayout( 'Layout %timestamp%' );
		$this->lockObject( $this->testLayout );
		$this->doLogOff();

		// User 'Jim' tries to unlock the layout with client application A.
		$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "Jim %timestamp%" );
		$this->doLogOn();
		$this->unlockObjects( array( $this->testLayout->MetaData->BasicMetaData->ID ) , '(S1147)' );
		$this->doLogOff();

		// User 'John' tries to unlock the layout with client application B.
		$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "John %timestamp%" );
		$this->appName = 'Client-B';
		$this->doLogOn();
		$this->unlockObjects( array( $this->testLayout->MetaData->BasicMetaData->ID ), null );
		$this->doLogOff();

		// User 'John' locks the layout with client application A.
		$this->appName = 'Client-A';
		$this->doLogOn();
		$this->lockObject( $this->testLayout );
		$this->doLogOff();

		// Brand admin tries to unlock the layout with client application B.
		$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "Brand Admin %timestamp%" );
		$this->appName = 'Client-B';
		$this->doLogOn();
		$this->unlockObjects( array( $this->testLayout->MetaData->BasicMetaData->ID ), null );
		$this->doLogOff();
	}

	/**
	 * Log off the workflow user.
	 */
	private function doLogOff()
	{
		if( $this->workflowTicket ) {
			$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );
			$this->workflowTicket = '';
		}
	}

	/**
	 * Call the LockObjects service.
	 *
	 * @param Object $object
	 */
	private function lockObject( Object $object )
	{
		require_once BASEDIR.'/server/services/wfl/WflLockObjectsService.class.php';
		$request = new WflLockObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->HaveVersions = array();
		$request->HaveVersions[0] = new ObjectVersion();
		$request->HaveVersions[0]->ID = $object->MetaData->BasicMetaData->ID;
		$request->HaveVersions[0]->Version = $object->MetaData->WorkflowMetaData->Version;

		$response = $this->testSuiteUtils->callService( $this, $request, 'Lock Object.');
	}

	/**
	 * Unlock one (or more) objects.
	 *
	 * @param array $objectIds
	 * @param null|string $expectedError
	 */
	private function unlockObjects( array $objectIds, ?string $expectedError )
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$service = new WflUnlockObjectsService();
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->IDs = $objectIds;
		$response = $this->testSuiteUtils->callService( $this, $request, 'Unlock object.', $expectedError, false );
		if( !$expectedError ) {
			$this->assertInstanceOf( 'WflUnlockObjectsResponse', $response );
		}
	}

	/**
	 * Log on the current workflow user.
	 */
	private function doLogOn()
	{
		$this->testSuiteUtils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->RequestInfo = array( 'FeatureProfiles' ); // request to resolve ticket only
				$req->User = $this->workflowUser;
				$req->Password = 'ww';
				$req->ClientAppName = $this->appName ;
			}
		);
		$response = $this->testSuiteUtils->wflLogOn( $this );
		$this->workflowTicket = $response->Ticket;
		$this->assertNotCount( 0, $response->FeatureProfiles );
	}

	/**
	 * Tear down all created test data.
	 */
	private function teardownTest()
	{
		if( $this->testLayout ) {
			if( $this->workflowTicket ) {
				$this->doLogOff(); // LogOff since no idea this ticket belongs to which test user.
			}
			try {
				$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "John %timestamp%" );
				$message = "Failed retrieving user John to do logOn to delete test layout.";
				$this->assertNotEquals( $this->workflowUser, "John %timestamp%", $message );
				$this->doLogOn();
				$errorReport = '';
				$this->testSuiteUtils->deleteObject( $this, $this->workflowTicket, $this->testLayout->MetaData->BasicMetaData->ID,
					'Deleting layout for Lock Objects BuildTest.', $errorReport );
				$this->testLayout = null;
				$this->doLogOff();
				$this->workflowFactory->teardownTestData();
			} catch( BizException $e ) {
				// Don't want to bail out here as we want to continue doing other cleaning ....
				$message = "Failed clearing layout " . $this->testLayout . '. Please check log files for more information.';
				$this->setResult( 'ERROR', $message  );
			}
			$this->workflowFactory = null;
		}
		if( $this->workflowFactory ) {
			$this->workflowFactory->teardownTestData();
		}
		if( $this->transferServerFiles ) {
			foreach( $this->transferServerFiles as $file ) {
				$this->transferServer->deleteFile( $file->FilePath );
			}
			unset( $this->transferServerFiles );
		}
		$this->clearIdsServerJobsInTheQueue();
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
	},{
		"Name": "Jim %timestamp%",
		"FullName": "Jim Jones %timestamp%",
		"Password": "ww",
		"Deactivated": false,
		"FixedPassword": false,
		"EmailUser": false,
		"EmailGroup": false
	},{
		"Name": "Brand Admin %timestamp%",
		"FullName": "Brand Administrator %timestamp%",
		"Password": "ww",
		"Deactivated": false,
		"FixedPassword": false,
		"EmailUser": false,
		"EmailGroup": false
	}],
	"UserGroups": [{
		"Name": "CanLock %timestamp%",
		"Admin": false
	},{
		"Name": "BrandAdmins %timestamp%",
		"Admin": false
	}],
	"Memberships": [{
		"User": "John %timestamp%",
		"UserGroup": "CanLock %timestamp%"
	},{
		"User": "Jim %timestamp%",
		"UserGroup": "CanLock %timestamp%"
	},{
		"User": "Brand Admin %timestamp%",
		"UserGroup": "BrandAdmins %timestamp%"
	}],
	"AccessProfiles": [{
		"Name": "CanLock %timestamp%",
		"ProfileFeatures": ["View", "Read", "Write", "Open_Edit", "Delete", "Purge" ]
	}],
	"UserAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "CanLock %timestamp%",
		"AccessProfile": "CanLock %timestamp%"	
	}],
	"AdminAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "BrandAdmins %timestamp%"
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

	/**
	 * Create a pre-configured layout in the DB.
	 *
	 * @param string $objectConfigName
	 * @return Object
	 */
	private function createLayout( $objectConfigName )
	{
		$object = $this->workflowFactory->getObjectConfig()->getComposedObject( $objectConfigName );

		$object->Relations = array();

		$object->Pages = array();
		$object->Pages[0] = new Page();
		$object->Pages[0]->Width = 595;
		$object->Pages[0]->Height = 842;
		$object->Pages[0]->PageNumber = '1';
		$object->Pages[0]->PageOrder = 1;
		$object->Pages[0]->Files = array();
		$object->Pages[0]->Files[0] = new Attachment();
		$object->Pages[0]->Files[0]->Rendition = 'thumb';
		$object->Pages[0]->Files[0]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_thumb.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $object->Pages[0]->Files[0] );
		$object->Pages[0]->Files[1] = new Attachment();
		$object->Pages[0]->Files[1]->Rendition = 'preview';
		$object->Pages[0]->Files[1]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_preview.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $object->Pages[0]->Files[1] );
		$object->Pages[0]->Edition = null;
		$object->Pages[0]->Master = 'Master';
		$object->Pages[0]->Instance = 'Production';
		$object->Pages[0]->PageSequence = 1;
		$this->transferServerFiles = array_merge( $this->transferServerFiles, $object->Pages[0]->Files );

		$object->Pages[1] = new Page();
		$object->Pages[1]->Width = 595;
		$object->Pages[1]->Height = 842;
		$object->Pages[1]->PageNumber = '2';
		$object->Pages[1]->PageOrder = 2;
		$object->Pages[1]->Files = array();
		$object->Pages[1]->Files[0] = new Attachment();
		$object->Pages[1]->Files[0]->Rendition = 'thumb';
		$object->Pages[1]->Files[0]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_thumb.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $object->Pages[1]->Files[0] );
		$object->Pages[1]->Files[1] = new Attachment();
		$object->Pages[1]->Files[1]->Rendition = 'preview';
		$object->Pages[1]->Files[1]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/page1_preview.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $object->Pages[1]->Files[1] );
		$object->Pages[1]->Edition = null;
		$object->Pages[1]->Master = 'Master';
		$object->Pages[1]->Instance = 'Production';
		$object->Pages[1]->PageSequence = 2;
		$this->transferServerFiles = array_merge( $this->transferServerFiles, $object->Pages[1]->Files );

		$object->Files = array();
		$object->Files[0] = new Attachment();
		$object->Files[0]->Rendition = 'native';
		$object->Files[0]->Type = 'application/indesign';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/layout_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $object->Files[0] );
		$object->Files[1] = new Attachment();
		$object->Files[1]->Rendition = 'thumb';
		$object->Files[1]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/layout_thumb.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $object->Files[1] );
		$object->Files[2] = new Attachment();
		$object->Files[2]->Rendition = 'preview';
		$object->Files[2]->Type = 'image/jpeg';
		$inputPath = __DIR__.'/testdata/WflGetRelatedPages/layout_preview.jpeg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $object->Files[2] );
		$this->transferServerFiles = array_merge( $this->transferServerFiles, $object->Files );

		$object->MessageList = new MessageList();

		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->Lock = false;
		$request->Objects = array( $object );

		$service = new WflCreateObjectsService();
		/** @var WflCreateObjectsResponse $response */
		$response = $service->execute( $request );
		$this->assertInstanceOf( 'WflCreateObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );

		return $response->Objects[0];
	}

	/**
	 * Clean up all the IDS server jobs in the job queue created by this build test.
	 *
	 * @since 10.4.2
	 */
	private function clearIdsServerJobsInTheQueue()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$where = '`queuetime` >= ? ';
		$params = array( strval( $this->testStartTime ) );
		$result = DBBase::deleteRows( 'indesignserverjobs', $where, $params );
	}
}
