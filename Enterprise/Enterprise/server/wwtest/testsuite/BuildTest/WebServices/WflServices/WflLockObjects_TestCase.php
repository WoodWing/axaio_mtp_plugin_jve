<?php
/**
 * @package   Enterprise
 * @subpackage   TestSuite
 * @since      v10.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class  WW_TestSuite_BuildTest_WebServices_WflServices_WflLockObjects_TestCase extends TestCase
{
	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflServicesUtils */
	private $testSuiteUtils = null;

	/** @var string workflowTicket */
	private $workflowTicket = '';

	/** @var WW_TestSuite_Setup_WorkflowFactory */
	private $workflowFactory;

	/** @var int profileId */
	private $profileId;

	/** @var FeatureProfile */
	private $featureProfile;

	/** @var string workflowUser */
	private $workflowUser;

	/** @var Object test Object */
	private $testLayout;

	/** @var Attachment[] Layout files copied to the transfer server folder. */
	private $transferServerFiles = array();

	// Step#01: Fill in the TestGoals, TestMethods and Prio...
	public function getDisplayName()
	{
		return 'Lock Objects';
	}

	public function getTestGoals()
	{
		return 'Checks if objects get locked, but only if the user is entitled.';
	}

	public function getTestMethods()
	{
		return 'Scenario:<ol>
			\'<li>Scenario:</li>\'.
			\'<li>Create users and groups, add access rights for these groups for a newly created brand.</li>\'.
			\'<li>An object is created.</li>\'.
			\'<li>Of this object the right to lock the object is tested.</li>\'.
			\'<li>First by a user that is entitled.</li>\'.
			\'<li>Next by a user that is not entitled. In this case an \'Access Denied\' error is exepeted.</li>\'.
			\'<li>Test data is cleaned up.</li>\'.
		</ol>';
	}

	public function getPrio()
	{
		return 190;
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
		$this->assertNotNull( $admTicket );
		require_once BASEDIR.'/server/wwtest/testsuite/Setup/WorkflowFactory.class.php';
		$this->workflowFactory = new WW_TestSuite_Setup_WorkflowFactory( $this, $admTicket, $this->testSuiteUtils );
		$this->workflowFactory->setConfig( $this->getWorkflowConfig() );
		$this->workflowFactory->setupTestData();
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
	}

	/**
	 * Test the lock object right on the created object.
	 *
	 * first log on with the user that has sufficient rights. Create the object. First lock the object by the user that
	 * is entitled, next by a user that has not sufficient rights. In that case an 'S1002' error is expected.
	 */
	private function testWorkflowAccess()
	{
		$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "John %timestamp%" );
		$this->doLogOn();
		$this->testLayout = $this->createLayout( 'Layout %timestamp%' );
		$this->testLockObject( null );
		$this->doLogOff();
		$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "Jim %timestamp%" );
		$this->doLogOn();
		$this->testLockObject( '(S1002)' );
		$this->doLogOff();
	}

	/**
	 * Lock the object and unlock it afterwards.
	 *
	 * In case no error is expectd pass in null, else the expected S-code.
	 *
	 * @param string|null $expectedError
	 */
	private function testLockObject( $expectedError )
	{
		$this->lockObject( $this->testLayout, $expectedError );
		$this->unlockObjects( array( $this->testLayout->MetaData->BasicMetaData->ID ) );
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
	 * @param null|string $expectedError
	 */
	private function lockObject( Object $object, $expectedError )
	{
		require_once BASEDIR.'/server/services/wfl/WflLockObjectsService.class.php';
		$request = new WflLockObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->HaveVersions = array();
		$request->HaveVersions[0] = new ObjectVersion();
		$request->HaveVersions[0]->ID = $object->MetaData->BasicMetaData->ID;
		$request->HaveVersions[0]->Version = $object->MetaData->WorkflowMetaData->Version;

		$response = $this->testSuiteUtils->callService( $this, $request, 'Lock Object.', $expectedError, false );
		if( !$expectedError ) {
			$this->assertInstanceOf( 'WflLockObjectsResponse', $response );
		}
	}

	/**
	 * Unlocks object locked by this test.
	 *
	 * @param int[] $objectIds
	 */
	private function unlockObjects( array $objectIds )
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$service = new WflUnlockObjectsService();
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->IDs = $objectIds;
		$service->execute( $request );
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
		if( !$this->workflowUser ) {
			$this->workflowUser = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "John %timestamp%" );
			$this->doLogOn();
		}
		if( $this->testLayout ) {
			$errorReport = '';
			$this->testSuiteUtils->deleteObject( $this, $this->workflowTicket, $this->testLayout->MetaData->BasicMetaData->ID,
				'Deleting layout for Lock Objects BuildTest.', $errorReport );
		}
		$this->doLogOff();
		if( $this->transferServerFiles ) {
			foreach( $this->transferServerFiles as $file ) {
				$this->transferServer->deleteFile( $file->FilePath );
			}
			unset( $this->transferServerFiles );
		}
		$this->workflowFactory->teardownTestData();
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
		"FullName": "Jim Parker %timestamp%",
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
		"Name": "CannotLock %timestamp%",
		"Admin": false
	},{
		"Name": "BrandAdmin %timestamp%",
		"Admin": false
	}],
	"Memberships": [{
		"User": "John %timestamp%",
		"UserGroup": "CanLock %timestamp%"
	},{
		"User": "Jim %timestamp%",
		"UserGroup": "CannotLock %timestamp%"
	}],
	"AccessProfiles": [{
		"Name": "CanLock %timestamp%",
		"ProfileFeatures": ["View", "Read", "Write", "Open_Edit", "Delete", "Purge" ]
	},{
		"Name": "CannotLock %timestamp%",
		"ProfileFeatures": ["View", "Read", "Write", "Delete", "Purge" ]
	}],
	"UserAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "CanLock %timestamp%",
		"AccessProfile": "CanLock %timestamp%"	
	},{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "CannotLock %timestamp%",
		"AccessProfile": "CannotLock %timestamp%"	
	}],
	"AdminAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "BrandAdmin %timestamp%"
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
}
