<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflGetRelatedPages_TestCase extends TestCase
{
	/** @var WW_Utils_TestSuite */
	private $testSuiteUtils;

	/** @var WW_TestSuite_Setup_WorkflowFactory */
	private $workflowFactory;

	/** @var BizTransferServer */
	private $transferServer;

	/** @var string Session ticket of the admin user who does setup the brand, workflow and access rights. */
	private $adminTicket;

	/** @var string Session ticket of the end workflow user who creates, copies and deletes layouts. */
	private $workflowTicket;

	/** @var Object Layout with editions. */
	private $layout1;

	/** @var Object Copy (variant) of $layout1 */
	private $copiedLayout1;

	/** @var Object Master layout without edition. */
	private $layout2;

	/** @var Object Copy (variant) of $layout2 */
	private $copiedLayout2;

	/** @var Attachment[] Layout files copied to the transfer server folder. */
	private $transferServerFiles = array();

	public function getDisplayName() { return 'Related Pages'; }
	public function getTestGoals()   { return 'Checks if the GetRelatedPages and GetRelatedPagesInfo web services are working correctly.'; }
	public function getPrio()        { return '180'; }
	public function isSelfCleaning() { return true; }

	public function getTestMethods()
	{
		return
			'Scenario: <ul>'.
			'<li>Setup a brand with a print channel, two issues, two categories, two layout statuses and two editions.</li>'.
			'<li>Setup a user, user group, access right profile and give the user access to the new brand.</li>'.
			'<li>Create a new master layout with two pages and two editions (CreateObjects).</li>'.
			'<li>Copy the master layout to make a layout variant (CopyObjects).</li>'.
			'<li>Request for the info of related layout pages of the first master page (GetRelatedPagesInfo).</li>'.
			'<li>Request for the files of related layout pages of the first master page (GetRelatedPages).</li>'.
			'<li>Repeat all steps above but now for a brand without editions.</li>'.
			'<li>Remove the layouts, the brand setup and the user access setup.</li>'.
			'</ul>';
	}

	/**
	 * @inheritdoc
	 */
	final public function runTest()
	{
		try {
			$this->setupTestData();

			$this->testGetRelatedPagesInfo1();
			$this->testGetRelatedPages1();

			$this->testGetRelatedPagesInfo2();
			$this->testGetRelatedPages2();

		} catch( BizException $e ) {}

		$this->teardownTestData();
	}

	/**
	 * Construct test data used in the script.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->testSuiteUtils = new WW_Utils_TestSuite();

		$this->vars = $this->getSessionVariables();
		$this->adminTicket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->assertNotNull( $this->adminTicket );

		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();

		require_once BASEDIR.'/server/wwtest/testsuite/Setup/WorkflowFactory.class.php';
		$this->workflowFactory = new WW_TestSuite_Setup_WorkflowFactory( $this, $this->adminTicket, $this->testSuiteUtils );
		$this->workflowFactory->setConfig( $this->getWorkflowConfig() );
		$this->workflowFactory->setupTestData();

		$this->testSuiteUtils->setRequestComposer(
			function( WflLogOnRequest $req ) {
				$req->RequestInfo = array(); // request to resolve ticket only
				$req->User = $this->workflowFactory->getAuthorizationConfig()->getUserShortName( "John %timestamp%" );
				$req->Password = 'ww';
			}
		);
		$response = $this->testSuiteUtils->wflLogOn( $this );
		$this->workflowTicket = $response->Ticket;

		// Test layout with editions.
		$this->layout1 = $this->createLayout( 'Layout1 %timestamp%' );
		$this->copiedLayout1 = $this->copyLayout( $this->layout1->MetaData->BasicMetaData->ID, 'Copy of Layout1 %timestamp%' );
		$this->lockLayout( $this->copiedLayout1->MetaData->BasicMetaData->ID, '0.1' );
		$this->copiedLayout1 = $this->saveLayout( $this->copiedLayout1 );

		// Test layout without editions.
		$this->layout2 = $this->createLayout( 'Layout2 %timestamp%' );
		$this->copiedLayout2 = $this->copyLayout( $this->layout2->MetaData->BasicMetaData->ID, 'Copy of Layout2 %timestamp%' );
		$this->lockLayout( $this->copiedLayout2->MetaData->BasicMetaData->ID, '0.1' );
		$this->copiedLayout2 = $this->saveLayout( $this->copiedLayout2 );
	}

	/**
	 * Remove all leftover test data used in the script.
	 */
	private function teardownTestData()
	{
		foreach( array( $this->layout1, $this->copiedLayout1, $this->layout2, $this->copiedLayout2 ) as $layout ) {
			if( $layout ) {
				$errorReport = '';
				$this->testSuiteUtils->deleteObject( $this, $this->workflowTicket, $layout->MetaData->BasicMetaData->ID,
					'Deleting layout for GetRelatedPages BuildTest.', $errorReport );
			}
		}
		unset( $this->layout1 );
		unset( $this->copiedLayout1 );
		unset( $this->layout2 );
		unset( $this->copiedLayout2 );

		if( $this->workflowTicket ) {
			$this->testSuiteUtils->wflLogOff( $this, $this->workflowTicket );
		}
		$this->workflowFactory->teardownTestData();
		if( $this->transferServerFiles ) {
			foreach( $this->transferServerFiles as $file ) {
				$this->transferServer->deleteFile( $file->FilePath );
			}
			unset( $this->transferServerFiles );
		}
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
	},{
		"Name": "PubTest2 %timestamp%",
		"PubChannels": [{
			"Name": "Print",
			"Type": "print",
			"PublishSystem": "Enterprise",
			"Issues": [{ "Name": "Week 45" },{ "Name": "Week 46" }]
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
		"Categories": [{ "Name": "News" },{ "Name": "Finance" }]
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
		"ProfileFeatures": ["View", "Read", "Write", "Open_Edit", "Delete", "Purge", "Change_Status"]
	}],
	"UserAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "Editors %timestamp%",
		"AccessProfile": "Full %timestamp%"
	},{
		"Publication": "PubTest2 %timestamp%",
		"UserGroup": "Editors %timestamp%",
		"AccessProfile": "Full %timestamp%"
	}],
	"AdminAuthorizations": [{
		"Publication": "PubTest1 %timestamp%",
		"UserGroup": "Editors %timestamp%"
	},{
		"Publication": "PubTest2 %timestamp%",
		"UserGroup": "Editors %timestamp%"
	}],
	"Objects":[{
		"Name": "Layout1 %timestamp%",
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
	},{
		"Name": "Copy of Layout1 %timestamp%",
		"Type": "Layout",
		"Format": "application/indesign",
		"FileSize": 1146880,
		"DocumentID": "xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4",
		"Comment": "Created by Build Test class: %classname%",
		"Publication": "PubTest1 %timestamp%",
		"Category": "Sport",
		"State": "Layout Draft",
		"Targets": [{
			"PubChannel": "Print",
			"Issue": "Week 36",
			"Editions": [ "North", "South" ]
		}]
	},{
		"Name": "Layout2 %timestamp%",
		"Type": "Layout",
		"Format": "application/indesign",
		"FileSize": 1146880,
		"DocumentID": "xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4",
		"Comment": "Created by Build Test class: %classname%",
		"Publication": "PubTest2 %timestamp%",
		"Category": "News",
		"State": "Layout Ready",
		"Targets": [{
			"PubChannel": "Print",
			"Issue": "Week 45",
			"Editions": null
		}]
	},{
		"Name": "Copy of Layout2 %timestamp%",
		"Type": "Layout",
		"Format": "application/indesign",
		"FileSize": 1146880,
		"DocumentID": "xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4",
		"Comment": "Created by Build Test class: %classname%",
		"Publication": "PubTest2 %timestamp%",
		"Category": "Finance",
		"State": "Layout Draft",
		"Targets": [{
			"PubChannel": "Print",
			"Issue": "Week 46",
			"Editions": null
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
	 * Test the GetRelatedPagesInfo web service for $this->layout1 and $this->copiedLayout1 having editions.
	 */
	private function testGetRelatedPagesInfo1()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesInfoService.class.php';
		$request = new WflGetRelatedPagesInfoRequest();
		$request->Ticket = $this->workflowTicket;
		$request->LayoutId = $this->layout1->MetaData->BasicMetaData->ID;
		$request->PageSequences = array( 1 );

		$service = new WflGetRelatedPagesInfoService();
		/** @var WflGetRelatedPagesInfoResponse $response */
		$actualResponse = $service->execute( $request );
		$this->assertInstanceOf( 'WflGetRelatedPagesInfoResponse', $actualResponse );

		$expectedResponse = $this->getRecordedGetRelatedPagesInfoResponse1();

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $expectedResponse, $actualResponse ) ) {
			$expectedResponseFile = LogHandler::logPhpObject( $expectedResponse );
			$actualResponseFile = LogHandler::logPhpObject( $actualResponse );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Expected response: '.$expectedResponseFile.'<br/>';
			$errorMsg .= 'Current response: '.$actualResponseFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetRelatedPagesInfo response.');
			return;
		}
	}

	/**
	 * Test the GetRelatedPages web service for $this->layout1 and $this->copiedLayout1 having editions.
	 */
	private function testGetRelatedPages1()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesService.class.php';
		$request = new WflGetRelatedPagesRequest();
		$request->Ticket = $this->workflowTicket;
		$request->LayoutId = $this->layout1->MetaData->BasicMetaData->ID;
		$request->PageSequences = array( 1 );
		$request->Rendition = 'preview';

		$service = new WflGetRelatedPagesService();
		/** @var WflGetRelatedPagesResponse $response */
		$actualResponse = $service->execute( $request );
		$this->assertInstanceOf( 'WflGetRelatedPagesResponse', $actualResponse );

		$expectedResponse = $this->getRecordedGetRelatedPagesResponse1();

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $expectedResponse, $actualResponse ) ) {
			$expectedResponseFile = LogHandler::logPhpObject( $expectedResponse );
			$actualResponseFile = LogHandler::logPhpObject( $actualResponse );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Expected response: '.$expectedResponseFile.'<br/>';
			$errorMsg .= 'Current response: '.$actualResponseFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetRelatedPages response.');
			return;
		}
	}

	/**
	 * Test the GetRelatedPagesInfo web service for $this->layout2 and $this->copiedLayout2 having no editions.
	 */
	private function testGetRelatedPagesInfo2()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesInfoService.class.php';
		$request = new WflGetRelatedPagesInfoRequest();
		$request->Ticket = $this->workflowTicket;
		$request->LayoutId = $this->layout2->MetaData->BasicMetaData->ID;
		$request->PageSequences = array( 1 );

		$service = new WflGetRelatedPagesInfoService();
		/** @var WflGetRelatedPagesInfoResponse $response */
		$actualResponse = $service->execute( $request );
		$this->assertInstanceOf( 'WflGetRelatedPagesInfoResponse', $actualResponse );

		$expectedResponse = $this->getRecordedGetRelatedPagesInfoResponse2();

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $expectedResponse, $actualResponse ) ) {
			$expectedResponseFile = LogHandler::logPhpObject( $expectedResponse );
			$actualResponseFile = LogHandler::logPhpObject( $actualResponse );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Expected response: '.$expectedResponseFile.'<br/>';
			$errorMsg .= 'Current response: '.$actualResponseFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetRelatedPagesInfo response.');
			return;
		}
	}

	/**
	 * Test the GetRelatedPages web service for $this->layout2 and $this->copiedLayout2 having no editions.
	 */
	private function testGetRelatedPages2()
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesService.class.php';
		$request = new WflGetRelatedPagesRequest();
		$request->Ticket = $this->workflowTicket;
		$request->LayoutId = $this->layout2->MetaData->BasicMetaData->ID;
		$request->PageSequences = array( 1 );
		$request->Rendition = 'thumb';

		$service = new WflGetRelatedPagesService();
		/** @var WflGetRelatedPagesResponse $response */
		$actualResponse = $service->execute( $request );
		$this->assertInstanceOf( 'WflGetRelatedPagesResponse', $actualResponse );

		$expectedResponse = $this->getRecordedGetRelatedPagesResponse2();

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $expectedResponse, $actualResponse ) ) {
			$expectedResponseFile = LogHandler::logPhpObject( $expectedResponse );
			$actualResponseFile = LogHandler::logPhpObject( $actualResponse );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Expected response: '.$expectedResponseFile.'<br/>';
			$errorMsg .= 'Current response: '.$actualResponseFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetRelatedPages response.');
			return;
		}
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
	 * Make a copy of a given layout respecting given pre-configured layout data.
	 *
	 * @param string $sourceId The layout object DB id to copy from.
	 * @param string $objectConfigName Pre-configured layout metadata and targets to create in DB.
	 * @return Object
	 */
	private function copyLayout( $sourceId, $objectConfigName )
	{
		$object = $this->workflowFactory->getObjectConfig()->getComposedObject( $objectConfigName );

		require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
		$request = new WflCopyObjectRequest();
		$request->Ticket = $this->workflowTicket;
		$request->SourceID = $sourceId;
		$request->MetaData = $object->MetaData;
		$request->Targets = $object->Targets;

		$service = new WflCopyObjectService();
		/** @var WflCopyObjectResponse $response */
		$response = $service->execute( $request );
		$this->assertInstanceOf( 'WflCopyObjectResponse', $response );
		$this->assertInstanceOf( 'MetaData', $response->MetaData );

		$copiedLayout = new Object();
		$copiedLayout->MetaData = $response->MetaData;
		$copiedLayout->Targets = $response->Targets;
		$copiedLayout->Relations = $response->Relations;

		return $copiedLayout;
	}

	/**
	 * Lock a layout for editing.
	 *
	 * @param string $layoutId
	 * @param string $layoutVersion
	 */
	private function lockLayout( $layoutId, $layoutVersion )
	{
		require_once BASEDIR.'/server/services/wfl/WflLockObjectsService.class.php';
		$request = new WflLockObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->HaveVersions = array();
		$request->HaveVersions[0] = new ObjectVersion();
		$request->HaveVersions[0]->ID = $layoutId;
		$request->HaveVersions[0]->Version = $layoutVersion;

		$service = new WflLockObjectsService();
		/** @var WflLockObjectsResponse $response */
		$response = $service->execute( $request );
		$this->assertInstanceOf( 'WflLockObjectsResponse', $response );
	}

	/**
	 * Save a new version of a layout and release the lock.
	 *
	 * @param Object $object
	 * @return Object
	 */
	private function saveLayout( Object $object )
	{
		$object->Relations = array();

		$object->Pages = array();
		$object->Pages[0] = new Page();
		$object->Pages[0]->Width = 595;
		$object->Pages[0]->Height = 842;
		$object->Pages[0]->PageNumber = '4';
		$object->Pages[0]->PageOrder = 4;
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
		$object->Pages[1]->PageNumber = '5';
		$object->Pages[1]->PageOrder = 5;
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

		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->workflowTicket;
		$request->CreateVersion = true;
		$request->Unlock = true;
		$request->ForceCheckIn = false;
		$request->Objects = array( $object );

		$service = new WflSaveObjectsService();
		/** @var WflSaveObjectsResponse $response */
		$response = $service->execute( $request );
		$this->assertInstanceOf( 'WflSaveObjectsResponse', $response );
		$this->assertInstanceOf( 'Object', $response->Objects[0] );

		return $response->Objects[0];
	}

	/**
	 * Compose a response of the GetRelatedPagesInfo service that we expect to receive for $this->layout1 and $this->copiedLayout1 having editions.
	 *
	 * @return WflGetRelatedPagesInfoResponse
	 */
	private function getRecordedGetRelatedPagesInfoResponse1()
	{
		$layout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Layout1 %timestamp%' );
		$copiedLayout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Copy of Layout1 %timestamp%' );

		$response = new WflGetRelatedPagesInfoResponse();
		$response->EditionsPages = array();

		$response->EditionsPages[0] = new EditionPages();
		$response->EditionsPages[0]->Edition = null;
		$response->EditionsPages[0]->PageObjects = array();
		$response->EditionsPages[0]->PageObjects[0] = new PageObject();
		$response->EditionsPages[0]->PageObjects[0]->IssuePagePosition = null;
		$response->EditionsPages[0]->PageObjects[0]->PageOrder = 1;
		$response->EditionsPages[0]->PageObjects[0]->PageNumber = '1';
		$response->EditionsPages[0]->PageObjects[0]->PageSequence = 1;
		$response->EditionsPages[0]->PageObjects[0]->Height = 842;
		$response->EditionsPages[0]->PageObjects[0]->Width = 595;
		$response->EditionsPages[0]->PageObjects[0]->ParentLayoutId = $this->layout1->MetaData->BasicMetaData->ID;
		$response->EditionsPages[0]->PageObjects[0]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos = null;

		$response->EditionsPages[1] = new EditionPages();
		$response->EditionsPages[1]->Edition = null;
		$response->EditionsPages[1]->PageObjects = array();
		$response->EditionsPages[1]->PageObjects[0] = new PageObject();
		$response->EditionsPages[1]->PageObjects[0]->IssuePagePosition = null;
		$response->EditionsPages[1]->PageObjects[0]->PageOrder = 4;
		$response->EditionsPages[1]->PageObjects[0]->PageNumber = '4';
		$response->EditionsPages[1]->PageObjects[0]->PageSequence = 1;
		$response->EditionsPages[1]->PageObjects[0]->Height = 842;
		$response->EditionsPages[1]->PageObjects[0]->Width = 595;
		$response->EditionsPages[1]->PageObjects[0]->ParentLayoutId = $this->copiedLayout1->MetaData->BasicMetaData->ID;
		$response->EditionsPages[1]->PageObjects[0]->OutputRenditionAvailable = false;
		$response->EditionsPages[1]->PageObjects[0]->PlacementInfos = null;

		$response->LayoutObjects = array();
		$response->LayoutObjects[0] = new LayoutObject();
		$response->LayoutObjects[0]->Id = $layout->MetaData->BasicMetaData->ID;
		$response->LayoutObjects[0]->Name = $layout->MetaData->BasicMetaData->Name;
		$response->LayoutObjects[0]->Category = new Category();
		$response->LayoutObjects[0]->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest1 %timestamp%', 'People' ) );
		$response->LayoutObjects[0]->Category->Name = 'People';
		$response->LayoutObjects[0]->State = new State();
		$response->LayoutObjects[0]->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest1 %timestamp%', 'Layout Ready' ) );
		$response->LayoutObjects[0]->State->Name = 'Layout Ready';
		$response->LayoutObjects[0]->State->Type = 'Layout';
		$response->LayoutObjects[0]->State->Produce = null;
		$response->LayoutObjects[0]->State->Color = 'FFFFFF';
		$response->LayoutObjects[0]->State->DefaultRouteTo = null;
		$response->LayoutObjects[0]->Version = '0.1';
		$response->LayoutObjects[0]->LockedBy = '';
		$response->LayoutObjects[0]->Flag = 0;
		$response->LayoutObjects[0]->FlagMsg = '';
		$response->LayoutObjects[0]->Publication = new Publication();
		$response->LayoutObjects[0]->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest1 %timestamp%' ) );
		$response->LayoutObjects[0]->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest1 %timestamp%' );
		$response->LayoutObjects[0]->Target = new Target();
		$response->LayoutObjects[0]->Target->PubChannel = new PubChannel();
		$response->LayoutObjects[0]->Target->PubChannel->Id = 0;
		$response->LayoutObjects[0]->Target->PubChannel->Name = '';
		$response->LayoutObjects[0]->Target->Issue = new Issue();
		$response->LayoutObjects[0]->Target->Issue->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getIssueId( 'PubTest1 %timestamp%', 'Print', 'Week 35' ) );
		$response->LayoutObjects[0]->Target->Issue->Name = 'Week 35';
		$response->LayoutObjects[0]->Target->Issue->OverrulePublication = null;
		$response->LayoutObjects[0]->Target->Editions = null;
		$response->LayoutObjects[0]->Target->Editions[0] = new Edition();
		$response->LayoutObjects[0]->Target->Editions[0]->Id = $this->workflowFactory->getPublicationConfig()
			->getEditionId( 'PubTest1 %timestamp%', 'Print', 'North' );
		$response->LayoutObjects[0]->Target->Editions[0]->Name = 'North';
		$response->LayoutObjects[0]->Target->Editions[1] = new Edition();
		$response->LayoutObjects[0]->Target->Editions[1]->Id = $this->workflowFactory->getPublicationConfig()
			->getEditionId( 'PubTest1 %timestamp%', 'Print', 'South' );
		$response->LayoutObjects[0]->Target->Editions[1]->Name = 'South';
		$response->LayoutObjects[0]->Target->PublishedDate = null;
		$response->LayoutObjects[0]->Target->PublishedVersion = null;
		$response->LayoutObjects[0]->Modified = '...';

		$response->LayoutObjects[1] = new LayoutObject();
		$response->LayoutObjects[1]->Id = $copiedLayout->MetaData->BasicMetaData->ID;
		$response->LayoutObjects[1]->Name = $copiedLayout->MetaData->BasicMetaData->Name;
		$response->LayoutObjects[1]->Category = new Category();
		$response->LayoutObjects[1]->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest1 %timestamp%', 'Sport' ) );
		$response->LayoutObjects[1]->Category->Name = 'Sport';
		$response->LayoutObjects[1]->State = new State();
		$response->LayoutObjects[1]->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest1 %timestamp%', 'Layout Draft' ) );
		$response->LayoutObjects[1]->State->Name = 'Layout Draft';
		$response->LayoutObjects[1]->State->Type = 'Layout';
		$response->LayoutObjects[1]->State->Produce = null;
		$response->LayoutObjects[1]->State->Color = 'FFFFFF';
		$response->LayoutObjects[1]->State->DefaultRouteTo = null;
		$response->LayoutObjects[1]->Version = '0.1';
		$response->LayoutObjects[1]->LockedBy = '';
		$response->LayoutObjects[1]->Flag = 0;
		$response->LayoutObjects[1]->FlagMsg = '';
		$response->LayoutObjects[1]->Publication = new Publication();
		$response->LayoutObjects[1]->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest1 %timestamp%' ) );
		$response->LayoutObjects[1]->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest1 %timestamp%' );
		$response->LayoutObjects[1]->Target = new Target();
		$response->LayoutObjects[1]->Target->PubChannel = new PubChannel();
		$response->LayoutObjects[1]->Target->PubChannel->Id = 0;
		$response->LayoutObjects[1]->Target->PubChannel->Name = '';
		$response->LayoutObjects[1]->Target->Issue = new Issue();
		$response->LayoutObjects[1]->Target->Issue->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getIssueId( 'PubTest1 %timestamp%', 'Print', 'Week 36' ) );
		$response->LayoutObjects[1]->Target->Issue->Name = 'Week 36';
		$response->LayoutObjects[1]->Target->Issue->OverrulePublication = null;
		$response->LayoutObjects[1]->Target->Editions = array();
		$response->LayoutObjects[1]->Target->Editions[0] = new Edition();
		$response->LayoutObjects[1]->Target->Editions[0]->Id = $this->workflowFactory->getPublicationConfig()
			->getEditionId( 'PubTest1 %timestamp%', 'Print', 'North' );;
		$response->LayoutObjects[1]->Target->Editions[0]->Name = 'North';
		$response->LayoutObjects[1]->Target->Editions[1] = new Edition();
		$response->LayoutObjects[1]->Target->Editions[1]->Id = $this->workflowFactory->getPublicationConfig()
			->getEditionId( 'PubTest1 %timestamp%', 'Print', 'South' );;
		$response->LayoutObjects[1]->Target->Editions[1]->Name = 'South';
		$response->LayoutObjects[1]->Target->PublishedDate = null;
		$response->LayoutObjects[1]->Target->PublishedVersion = null;
		$response->LayoutObjects[1]->Modified = '...';
		return $response;
	}

	/**
	 * Compose a response of the GetRelatedPages service that we expect to receive for $this->layout1 and $this->copiedLayout1 having editions.
	 *
	 * @return WflGetRelatedPagesResponse
	 */
	private function getRecordedGetRelatedPagesResponse1()
	{
		$layout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Layout1 %timestamp%' );
		$copiedLayout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Copy of Layout1 %timestamp%' );

		$response = new WflGetRelatedPagesResponse();
		$response->ObjectPageInfos = array();

		$response->ObjectPageInfos[0] = new ObjectPageInfo();
		$response->ObjectPageInfos[0]->MetaData = new MetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ID = $layout->MetaData->BasicMetaData->ID;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Name = $layout->MetaData->BasicMetaData->Name;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest1 %timestamp%' ) );
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest1 %timestamp%' );
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest1 %timestamp%', 'People' ) );
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Name = 'People';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->MasterId = '0';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->StoreName = '...';
		$response->ObjectPageInfos[0]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Urgency = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modifier = $this->workflowFactory->getAuthorizationConfig()
			->getUserShortName( 'John %timestamp%' );
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modified = '...';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: WW_TestSuite_BuildTest_WebServices_WflServices_WflGetRelatedPages_TestCase';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest1 %timestamp%', 'Layout Ready' ) );
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Name = 'Layout Ready';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Color = 'FFFFFF';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[0]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[0]->Pages = array();
		$response->ObjectPageInfos[0]->Pages[0] = new Page();
		$response->ObjectPageInfos[0]->Pages[0]->Width = '595';
		$response->ObjectPageInfos[0]->Pages[0]->Height = '842';
		$response->ObjectPageInfos[0]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[0]->Pages[0]->PageOrder = '1';
		$response->ObjectPageInfos[0]->Pages[0]->Files = array();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Rendition = 'preview';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->EditionId = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->ContentSourceFileLink = null;
		$response->ObjectPageInfos[0]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[0]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[0]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[0]->Pages[0]->PageSequence = '1';
		$response->ObjectPageInfos[0]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[0]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[0]->Messages = null;
		$response->ObjectPageInfos[0]->MessageList = null;

		$response->ObjectPageInfos[1] = new ObjectPageInfo();
		$response->ObjectPageInfos[1]->MetaData = new MetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ID = $copiedLayout->MetaData->BasicMetaData->ID;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->DocumentID = 'xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Name = $copiedLayout->MetaData->BasicMetaData->Name;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest1 %timestamp%' ) );
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest1 %timestamp%' );
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest1 %timestamp%', 'Sport' ) );
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Name = 'Sport';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->MasterId = $layout->MetaData->BasicMetaData->ID;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->StoreName = '...';
		$response->ObjectPageInfos[1]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Urgency = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modifier = $this->workflowFactory->getAuthorizationConfig()
			->getUserShortName( 'John %timestamp%' );
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modified = '...';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: WW_TestSuite_BuildTest_WebServices_WflServices_WflGetRelatedPages_TestCase';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest1 %timestamp%', 'Layout Draft' ) );
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Name = 'Layout Draft';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Color = 'FFFFFF';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[1]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[1]->Pages = array();
		$response->ObjectPageInfos[1]->Pages[0] = new Page();
		$response->ObjectPageInfos[1]->Pages[0]->Width = '595';
		$response->ObjectPageInfos[1]->Pages[0]->Height = '842';
		$response->ObjectPageInfos[1]->Pages[0]->PageNumber = '4';
		$response->ObjectPageInfos[1]->Pages[0]->PageOrder = '4';
		$response->ObjectPageInfos[1]->Pages[0]->Files = array();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Rendition = 'preview';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->EditionId = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->ContentSourceFileLink = null;
		$response->ObjectPageInfos[1]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[1]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[1]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[1]->Pages[0]->PageSequence = '1';
		$response->ObjectPageInfos[1]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[1]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[1]->Messages = null;
		$response->ObjectPageInfos[1]->MessageList = null;

		return $response;
	}

	/**
	 * Compose a response of the GetRelatedPagesInfo service that we expect to receive for $this->layout2 and $this->copiedLayout2 having no editions.
	 *
	 * @return WflGetRelatedPagesInfoResponse
	 */
	private function getRecordedGetRelatedPagesInfoResponse2()
	{
		$layout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Layout2 %timestamp%' );
		$copiedLayout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Copy of Layout2 %timestamp%' );

		$response = new WflGetRelatedPagesInfoResponse();
		$response->EditionsPages = array();

		$response->EditionsPages[0] = new EditionPages();
		$response->EditionsPages[0]->Edition = null;
		$response->EditionsPages[0]->PageObjects = array();
		$response->EditionsPages[0]->PageObjects[0] = new PageObject();
		$response->EditionsPages[0]->PageObjects[0]->IssuePagePosition = null;
		$response->EditionsPages[0]->PageObjects[0]->PageOrder = 1;
		$response->EditionsPages[0]->PageObjects[0]->PageNumber = '1';
		$response->EditionsPages[0]->PageObjects[0]->PageSequence = 1;
		$response->EditionsPages[0]->PageObjects[0]->Height = 842;
		$response->EditionsPages[0]->PageObjects[0]->Width = 595;
		$response->EditionsPages[0]->PageObjects[0]->ParentLayoutId = $this->layout2->MetaData->BasicMetaData->ID;
		$response->EditionsPages[0]->PageObjects[0]->OutputRenditionAvailable = false;
		$response->EditionsPages[0]->PageObjects[0]->PlacementInfos = null;

		$response->EditionsPages[1] = new EditionPages();
		$response->EditionsPages[1]->Edition = null;
		$response->EditionsPages[1]->PageObjects = array();
		$response->EditionsPages[1]->PageObjects[0] = new PageObject();
		$response->EditionsPages[1]->PageObjects[0]->IssuePagePosition = null;
		$response->EditionsPages[1]->PageObjects[0]->PageOrder = 4;
		$response->EditionsPages[1]->PageObjects[0]->PageNumber = '4';
		$response->EditionsPages[1]->PageObjects[0]->PageSequence = 1;
		$response->EditionsPages[1]->PageObjects[0]->Height = 842;
		$response->EditionsPages[1]->PageObjects[0]->Width = 595;
		$response->EditionsPages[1]->PageObjects[0]->ParentLayoutId = $this->copiedLayout2->MetaData->BasicMetaData->ID;
		$response->EditionsPages[1]->PageObjects[0]->OutputRenditionAvailable = false;
		$response->EditionsPages[1]->PageObjects[0]->PlacementInfos = null;

		$response->LayoutObjects = array();
		$response->LayoutObjects[0] = new LayoutObject();
		$response->LayoutObjects[0]->Id = $layout->MetaData->BasicMetaData->ID;
		$response->LayoutObjects[0]->Name = $layout->MetaData->BasicMetaData->Name;
		$response->LayoutObjects[0]->Category = new Category();
		$response->LayoutObjects[0]->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest2 %timestamp%', 'News' ) );
		$response->LayoutObjects[0]->Category->Name = 'News';
		$response->LayoutObjects[0]->State = new State();
		$response->LayoutObjects[0]->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest2 %timestamp%', 'Layout Ready' ) );
		$response->LayoutObjects[0]->State->Name = 'Layout Ready';
		$response->LayoutObjects[0]->State->Type = 'Layout';
		$response->LayoutObjects[0]->State->Produce = null;
		$response->LayoutObjects[0]->State->Color = 'FFFFFF';
		$response->LayoutObjects[0]->State->DefaultRouteTo = null;
		$response->LayoutObjects[0]->Version = '0.1';
		$response->LayoutObjects[0]->LockedBy = '';
		$response->LayoutObjects[0]->Flag = 0;
		$response->LayoutObjects[0]->FlagMsg = '';
		$response->LayoutObjects[0]->Publication = new Publication();
		$response->LayoutObjects[0]->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest2 %timestamp%' ) );
		$response->LayoutObjects[0]->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest2 %timestamp%' );
		$response->LayoutObjects[0]->Target = new Target();
		$response->LayoutObjects[0]->Target->PubChannel = new PubChannel();
		$response->LayoutObjects[0]->Target->PubChannel->Id = 0;
		$response->LayoutObjects[0]->Target->PubChannel->Name = '';
		$response->LayoutObjects[0]->Target->Issue = new Issue();
		$response->LayoutObjects[0]->Target->Issue->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getIssueId( 'PubTest2 %timestamp%', 'Print', 'Week 45' ) );
		$response->LayoutObjects[0]->Target->Issue->Name = 'Week 45';
		$response->LayoutObjects[0]->Target->Issue->OverrulePublication = null;
		$response->LayoutObjects[0]->Target->Editions = null;
		$response->LayoutObjects[0]->Target->PublishedDate = null;
		$response->LayoutObjects[0]->Target->PublishedVersion = null;
		$response->LayoutObjects[0]->Modified = '...';

		$response->LayoutObjects[1] = new LayoutObject();
		$response->LayoutObjects[1]->Id = $copiedLayout->MetaData->BasicMetaData->ID;
		$response->LayoutObjects[1]->Name = $copiedLayout->MetaData->BasicMetaData->Name;
		$response->LayoutObjects[1]->Category = new Category();
		$response->LayoutObjects[1]->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest2 %timestamp%', 'Finance' ) );
		$response->LayoutObjects[1]->Category->Name = 'Finance';
		$response->LayoutObjects[1]->State = new State();
		$response->LayoutObjects[1]->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest2 %timestamp%', 'Layout Draft' ) );
		$response->LayoutObjects[1]->State->Name = 'Layout Draft';
		$response->LayoutObjects[1]->State->Type = 'Layout';
		$response->LayoutObjects[1]->State->Produce = null;
		$response->LayoutObjects[1]->State->Color = 'FFFFFF';
		$response->LayoutObjects[1]->State->DefaultRouteTo = null;
		$response->LayoutObjects[1]->Version = '0.1';
		$response->LayoutObjects[1]->LockedBy = '';
		$response->LayoutObjects[1]->Flag = 0;
		$response->LayoutObjects[1]->FlagMsg = '';
		$response->LayoutObjects[1]->Publication = new Publication();
		$response->LayoutObjects[1]->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest2 %timestamp%' ) );
		$response->LayoutObjects[1]->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest2 %timestamp%' );
		$response->LayoutObjects[1]->Target = new Target();
		$response->LayoutObjects[1]->Target->PubChannel = new PubChannel();
		$response->LayoutObjects[1]->Target->PubChannel->Id = 0;
		$response->LayoutObjects[1]->Target->PubChannel->Name = '';
		$response->LayoutObjects[1]->Target->Issue = new Issue();
		$response->LayoutObjects[1]->Target->Issue->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getIssueId( 'PubTest2 %timestamp%', 'Print', 'Week 46' ) );
		$response->LayoutObjects[1]->Target->Issue->Name = 'Week 46';
		$response->LayoutObjects[1]->Target->Issue->OverrulePublication = null;
		$response->LayoutObjects[1]->Target->Editions = null;
		$response->LayoutObjects[1]->Target->PublishedDate = null;
		$response->LayoutObjects[1]->Target->PublishedVersion = null;
		$response->LayoutObjects[1]->Modified = '...';
		return $response;
	}

	/**
	 * Compose a response of the GetRelatedPagesInfo service that we expect to receive for $this->layout2 and $this->copiedLayout2 having no editions.
	 *
	 * @return WflGetRelatedPagesResponse
	 */
	private function getRecordedGetRelatedPagesResponse2()
	{
		$layout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Layout2 %timestamp%' );
		$copiedLayout = $this->workflowFactory->getObjectConfig()->getComposedObject( 'Copy of Layout2 %timestamp%' );

		$response = new WflGetRelatedPagesResponse();
		$response->ObjectPageInfos = array();

		$response->ObjectPageInfos[0] = new ObjectPageInfo();
		$response->ObjectPageInfos[0]->MetaData = new MetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ID = $layout->MetaData->BasicMetaData->ID;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Name = $layout->MetaData->BasicMetaData->Name;
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest2 %timestamp%' ) );
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest2 %timestamp%' );
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest2 %timestamp%', 'News' ) );
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->MasterId = '0';
		$response->ObjectPageInfos[0]->MetaData->BasicMetaData->StoreName = '...';
		$response->ObjectPageInfos[0]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Urgency = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modifier = $this->workflowFactory->getAuthorizationConfig()
			->getUserShortName( 'John %timestamp%' );
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Modified = '...';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: WW_TestSuite_BuildTest_WebServices_WflServices_WflGetRelatedPages_TestCase';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest2 %timestamp%', 'Layout Ready' ) );
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Name = 'Layout Ready';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->Color = 'FFFFFF';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[0]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[0]->Pages = array();
		$response->ObjectPageInfos[0]->Pages[0] = new Page();
		$response->ObjectPageInfos[0]->Pages[0]->Width = '595';
		$response->ObjectPageInfos[0]->Pages[0]->Height = '842';
		$response->ObjectPageInfos[0]->Pages[0]->PageNumber = '1';
		$response->ObjectPageInfos[0]->Pages[0]->PageOrder = '1';
		$response->ObjectPageInfos[0]->Pages[0]->Files = array();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->EditionId = null;
		$response->ObjectPageInfos[0]->Pages[0]->Files[0]->ContentSourceFileLink = null;
		$response->ObjectPageInfos[0]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[0]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[0]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[0]->Pages[0]->PageSequence = '1';
		$response->ObjectPageInfos[0]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[0]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[0]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[0]->Messages = null;
		$response->ObjectPageInfos[0]->MessageList = null;

		$response->ObjectPageInfos[1] = new ObjectPageInfo();
		$response->ObjectPageInfos[1]->MetaData = new MetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData = new BasicMetaData();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ID = $copiedLayout->MetaData->BasicMetaData->ID;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->DocumentID = 'xmp.did:d623825c-75a6-4da2-aa9a-9c9d1dedc1c4';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Name = $copiedLayout->MetaData->BasicMetaData->Name;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication = new Publication();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getPublicationId( 'PubTest2 %timestamp%' ) );
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Publication->Name = $this->workflowFactory->getPublicationConfig()
			->getPublicationName( 'PubTest2 %timestamp%' );
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category = new Category();
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getCategoryId( 'PubTest2 %timestamp%', 'Finance' ) );
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->Category->Name = 'Finance';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->ContentSource = '';
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->MasterId = $layout->MetaData->BasicMetaData->ID;
		$response->ObjectPageInfos[1]->MetaData->BasicMetaData->StoreName = '...';
		$response->ObjectPageInfos[1]->MetaData->RightsMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->SourceMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->ContentMetaData = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deadline = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Urgency = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modifier = $this->workflowFactory->getAuthorizationConfig()
			->getUserShortName( 'John %timestamp%' );
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Modified = '...';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Creator = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Created = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Comment = 'Created by Build Test class: WW_TestSuite_BuildTest_WebServices_WflServices_WflGetRelatedPages_TestCase';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State = new State();
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Id = strval( $this->workflowFactory->getPublicationConfig()
			->getStatusId( 'PubTest2 %timestamp%', 'Layout Draft' ) );
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Name = 'Layout Draft';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Type = 'Layout';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->Color = 'FFFFFF';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->LockedBy = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Rating = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deletor = null;
		$response->ObjectPageInfos[1]->MetaData->WorkflowMetaData->Deleted = null;
		$response->ObjectPageInfos[1]->MetaData->ExtraMetaData = null;
		$response->ObjectPageInfos[1]->Pages = array();
		$response->ObjectPageInfos[1]->Pages[0] = new Page();
		$response->ObjectPageInfos[1]->Pages[0]->Width = '595';
		$response->ObjectPageInfos[1]->Pages[0]->Height = '842';
		$response->ObjectPageInfos[1]->Pages[0]->PageNumber = '4';
		$response->ObjectPageInfos[1]->Pages[0]->PageOrder = '4';
		$response->ObjectPageInfos[1]->Pages[0]->Files = array();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0] = new Attachment();
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Rendition = 'thumb';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->Content = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FilePath = '';
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->FileUrl = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->EditionId = null;
		$response->ObjectPageInfos[1]->Pages[0]->Files[0]->ContentSourceFileLink = null;
		$response->ObjectPageInfos[1]->Pages[0]->Edition = null;
		$response->ObjectPageInfos[1]->Pages[0]->Master = 'Master';
		$response->ObjectPageInfos[1]->Pages[0]->Instance = 'Production';
		$response->ObjectPageInfos[1]->Pages[0]->PageSequence = '1';
		$response->ObjectPageInfos[1]->Pages[0]->Renditions = array();
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[0] = 'thumb';
		$response->ObjectPageInfos[1]->Pages[0]->Renditions[1] = 'preview';
		$response->ObjectPageInfos[1]->Pages[0]->Orientation = '';
		$response->ObjectPageInfos[1]->Messages = null;
		$response->ObjectPageInfos[1]->MessageList = null;

		return $response;
	}

	/**
	 * Compose list of properties that should be ignored when comparing actual- with expected response data.
	 *
	 * @return array
	 */
	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true,
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true, 'StoreName' => true
		);
	}
}