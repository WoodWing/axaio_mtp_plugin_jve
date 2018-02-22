<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_NameValidation_AutoNamingRule_TestCase extends TestCase
{
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $wflServicesUtils = null;
	private $dossiers = null;
	private $pubInfoObj = null;
	private $categoryInfoObj = null;
	private $pubChannelObj = null;
	private $issueObj = null;
	private $autoRenamedDossierObjs = null;


	public function getDisplayName() { return 'Auto Naming Rule'; }
	public function getTestGoals()   { return 'Checks if NameValidation auto name rule works correctly or not'; }
	public function getPrio()        { return 103; }
	public function getTestMethods() { return
		 'Call CreateObject services and validate the auto naming rule between Smart Mover and other client.
		 <ol>
		 	<li>Create test dossier objects.</li>
		 	<li>Create dossier object with ApplyAutoNaming = True.</li>
		 	<li>Create dossier object with ApplyAutoNaming = False.</li>
			<li>Create dossier object with ApplyAutoNaming = Null.</li>
			<li>Create dossier object with accented name with ApplyAutoNaming = Null.</li>
			<li>Move dossier objects to TrashCan.</li>
			<li>Create dossier object with ApplyAutoNaming = True.</li>
			<li>Create dossier object with ApplyAutoNaming = True.</li>
			<li>Create dossier object with accented name with ApplyAutoNaming = Null</li>
			<li>Restore dossier objects that will automatic apply autonaming.</li>
			<li>Logon as Smart Mover client, autonaming always set to True.</li>
			<li>Create dossier object with ApplyAutoNaming = True.</li>
			<li>Create dossier object with ApplyAutoNaming = False.</li>
			<li>Create dossier object with ApplyAutoNaming = Null.</li>
			<li>Create dossier object with accented name with ApplyAutoNaming = Null.</li>
			<li>Move create dossier objects to TrashCan.</li>
			<li>Create dossier object with ApplyAutoNaming = True.</li>
			<li>Create dossier object with ApplyAutoNaming = True.</li>
			<li>Create dossier object with accented name with ApplyAutoNaming = Null</li>
			<li>Restore dossier objects that will automatic apply autonaming.</li>
		 	<li>Logoff as Smart Mover client.</li>
		 	<li>Teardown test objects.</li>
		 </ol>';
	}
	
	final public function runTest()
	{
		try {
			$this->setupTestData();
			$this->testByWebServiceClient();
			$this->testByMoverClient();
		} catch( BizException $e ) {
		}
		$this->tearDownTestData();
	}

	/*
	 * Setup test data for testing.
	 *
	 * @return bool Whether the setup is successful.
	 */
	private function setupTestData()
	{
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/WebServices/WflServices/Utils.class.php';
		$this->wflServicesUtils = new WW_TestSuite_BuildTest_WebServices_WflServices_Utils();

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
		$this->vars  = $this->getSessionVariables();
		$this->ticket = @$this->vars['BuildTest_NV']['ticket'];
		$this->assertNotNull( $this->ticket, 'No ticket found. Please enable the "Setup test data" test case and try again.' );

		$this->pubInfoObj = $this->vars['BuildTest_NV']['Brand'];
		$this->assertInstanceOf( 'PublicationInfo', $this->pubInfoObj );

		$this->categoryInfoObj = $this->vars['BuildTest_NV']['Category'];
		$this->assertInstanceOf( 'CategoryInfo', $this->categoryInfoObj );

		$this->issueObj = @$this->vars['BuildTest_NV']['Issue'];
		$this->assertInstanceOf( 'IssueInfo', $this->issueObj );

		$this->getDefaultPubChannel();
		$this->createDossiers();
	}

	/**
	 * Test auto naming by using Web client.
	 *
	 * @throws BizException
	 */
	private function testByWebServiceClient()
	{
		try {
			$this->testUniqueNameTypeObject( 'web' );
		} catch( BizException $e ) {
			throw $e;
		}
	}

	/**
	 * Test auto naming by logon as Smart Mover client
	 * First - logon testsuite user as Smart Mover client to Enterprise
	 * Second - perform testing on the auto naming
	 * Third - logoff the Smart Mover client user
	 *
	 * @throws BizException
	 */
	private function testByMoverClient()
	{
		try {
			$this->logonAsMoverClient();
			$this->testUniqueNameTypeObject( 'mover' );
			$this->logOffAsMoverClient();
		} catch( BizException $e ) {
			throw $e;
		}
	}

	/**
	 * Create test dossier object
	 *
	 * @param string $dossierName Dossier object name
	 * @param string $stepInfo Description about the test step
	 * @param string $expectedError Expected error string
	 * @return mixed
	 */
	private function createDossier( $dossierName, $stepInfo, $expectedError = null )
	{
		$dossierObj = $this->buildDossierObject( null, $dossierName );
		$response = $this->utils->callCreateObjectService( $this, $this->ticket, array($dossierObj), false, $stepInfo, $expectedError );
		return $response;
	}

	/**
	 * Create initial test dossier objects
	 *
	 */
	private function createDossiers()
	{
		// Create test dossier objects with specific name
		$dossierObjs = array();
		$dossierNames = array( 'dossier_web_123', 'dossier_web_abc', 'dossier_web_xyz',
								'dossier_mover_123', 'dossier_mover_abc', 'dossier_mover_xyz' );
		foreach( $dossierNames as $dossierName ) {
			$dossierObj = $this->buildDossierObject( null, $dossierName );
			$dossierObjs[] = $dossierObj;
		}

		$stepInfo = 'Create test dossier objects.';
		$response = $this->utils->callCreateObjectService( $this, $this->ticket, $dossierObjs, false, $stepInfo );
		if( $response ) {
			foreach( $response->Objects as $responseObject )  {
				$this->assertInstanceOf( 'Object', $responseObject );
			}
			$this->dossiers = $response->Objects;
		}
	}

	/**
	 * Returns "dossier_$appName_xyz" ( y = with a diaeresis y )
	 *
	 * @param string $appName Client application name, Web|Mover
	 * @return string
	 */
	private function getDossierNameWithAccent( $appName )
	{
		return 'dossier_'.$appName.'_x' .chr( 0xC3 ).chr( 0xBF ) . 'z';
	}

	/**
	 * Logon TESTSUITE user as Smart Mover client
	 *
	 */
	private function logonAsMoverClient()
	{
		$suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();

		// Determine client app name
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientIP = WW_Utils_UrlUtils::getClientIP();
		$clientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
		if( empty($clientName) ) {
			$clientName = $clientIP;
		}

		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$request = new WflLogOnRequest();
		$request->User = $suiteOpts['User'];
		$request->Password = $suiteOpts['Password'];
		$request->Ticket = '';
		$request->Server = 'Enterprise Server';
		$request->ClientName = $clientName;
		$request->Domain = '';
		$request->ClientAppName = 'Mover-Wfl';
		$request->ClientAppVersion = 'v'.SERVERVERSION;
		$request->ClientAppSerial = '';
		$request->ClientAppProductKey = '';

		$stepInfo = 'Logon TESTSUITE user as Smart Mover Client.';
		$response = $this->utils->callService( $this, $request, $stepInfo, null, 'SOAP' );

		if( !is_null($response) ) {
			$this->ticket = $response->Ticket;
			if( !$this->ticket ) {
				$this->setResult( 'ERROR',  'Could not logon as Smart Mover client user.', 'Please check the TESTSUITE setting in configserver.php.' );
			}
		}
	}

	/**
	 * Logoff Smart Mover client, reset back the ticket to the test case user ticket
	 *
	 */
	private function logOffAsMoverClient()
	{
		// LogOff when we did LogOn as mover client before.
		if( $this->ticket ) {
			$this->utils->wflLogOff( $this, $this->ticket );
		}
		$this->ticket = @$this->vars['BuildTest_NV']['ticket'];
	}

	/**
	 * Test the object type that must have unique name within an issue
	 *
	 * @param string $appName Client application name, Web|Mover
	 */
	private function testUniqueNameTypeObject( $appName )
	{
		// Test create first dossier that having auto naming = true
		$this->testFirstTrueAutoNaming( $appName );

		// Test create dossier that having auto naming = false
		$this->testFalseAutoNaming( $appName );

		// Test create dossier that having auto naming = null
		$this->testNullAutoNaming( $appName );

		// Test create dossier with accented name that having auto naming = null
		$this->testFirstNullAutoNamingWithAccentedName( $appName );

		// Test create second dossier with accented name that having auto naming = null
		$this->testSecondNullAutoNamingWithAccentedName( $appName );

		// Move the renamed dossier object to TrashCan
		$this->moveDossierToTrashCan();

		// Test create second dossier that having auto naming = true
		$this->testSecondTrueAutoNaming( $appName );

		// Test create third dossier that having auto naming = true
		$this->testThirdTrueAutoNaming( $appName );

		// Test create third dossier with accented name that having auto naming = null
		$this->testThirdNullAutoNamingWithAccentedName( $appName );

		// Test restoring object with name already exists in DB
		$this->testRestoreObjectAutoNaming();
	}

	/**
	 * Restore object from TrashCan to test the auto naming
	 *
	 * @param integer $dossierId Dossier object Id
	 * @param string $stepInfo Test step description
	 * @return WflRestoreObjectsResponse
	 */
	private function restoreObject( $dossierId, $stepInfo )
	{
		require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';
		$request = new WflRestoreObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $dossierId );
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return $response;
	}

	/*
	 * Removes used test objects data.
	 */
	private function tearDownTestData()
	{
		// Delete dossier objects from Enterprise
		if( $this->dossiers ) {
			$dossierIds = array();
			foreach( $this->dossiers as $object ) {
				$dossierIds[] = $object->MetaData->BasicMetaData->ID;
			}
			$this->deleteObjects( $dossierIds );
			$this->dossiers = null;
		}
		// Delete the dossier object from TrashCan, this is possible when there is a test failed in the middle before restoring the object
		if( $this->autoRenamedDossierObjs ) foreach( $this->autoRenamedDossierObjs as $autoRenamedDossierObj ) {
			$dossierId = $autoRenamedDossierObj->MetaData->BasicMetaData->ID;
			$this->deleteObjects( array($dossierId), true );
		}
	}

	/**
	 * Build dossier object
	 *
	 * @param int $dossierId Dossier Id
	 * @param string $dossierName Dossier name
	 * @return null|Object
	 */
	private function buildDossierObject( $dossierId, $dossierName )
	{
		$metaData = $this->wflServicesUtils->buildEmptyMetaData();
		$dossierStatusInfo = $this->utils->getFirstStatusInfoForType( $this, $this->pubInfoObj, 'Dossier' );
		if( is_null($dossierStatusInfo) ) {
			return null;
		}

		$metaData->BasicMetaData->ID            = $dossierId;
		$metaData->BasicMetaData->Name          = $dossierName;
		$metaData->BasicMetaData->Type          = 'Dossier';
		$metaData->BasicMetaData->Publication   = new Publication( $this->pubInfoObj->Id, $this->pubInfoObj->Name );
		$metaData->BasicMetaData->Category      = new Category( $this->categoryInfoObj->Id, $this->categoryInfoObj->Name );
		$metaData->ContentMetaData->Format      = '';
		$metaData->ContentMetaData->FileSize    = 0;
		$metaData->WorkflowMetaData->State      = new State( $dossierStatusInfo->Id, $dossierStatusInfo->Name );

		// Create dossier object
		$object = new Object();
		$object->MetaData 	= $metaData;
		$object->Relations 	= array();
		$object->Targets = array();
		$object->Targets[0] = $this->composeTarget();

		return $object;
	}

	/**
	 * Composes a Target for a dossier object.
	 *
	 * The target is based on the created pubchannel/issue/editions during setup.
	 *
	 * @return Target
	 */
	private function composeTarget()
	{
		$pubChannel = new PubChannel();
		$pubChannel->Id = $this->pubChannelObj->Id;
		$pubChannel->Name = $this->pubChannelObj->Name;

		$issue = new Issue();
		$issue->Id   = $this->issueObj->Id;
		$issue->Name = $this->issueObj->Name;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = $this->pubChannelObj->Editions;

		return $target;
	}

	/**
	 * Get and set the default channel from the publication info object
	 */
	private function getDefaultPubChannel()
	{
		if( $this->pubInfoObj->PubChannels ) foreach( $this->pubInfoObj->PubChannels  as $pubChannel ) {
			foreach( $pubChannel->Issues as $issue ) {
				if( $issue->Name == $this->issueObj->Name ) {
					$this->pubChannelObj = $pubChannel;
					return;
				}
			}
		}
	}

	/**
	 * Delete objects
	 *
	 * @param array $dossierIds Array of dossier id
	 * @param bool $trash Indicator the area type, whether it is trash or not
	 */
	private function deleteObjects( $dossierIds, $trash = false )
	{
		try {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$request = new WflDeleteObjectsRequest();
			$request->Ticket    = $this->ticket;
			$request->IDs       = $dossierIds;
			$request->Permanent = true;
			if( $trash ) {
				$request->Areas = array( 'Trash' );
				$stepInfo = 'Delete an object in TrashCan (that was used for this test).';
			} else {
				$stepInfo = 'Delete objects (that was used for this test).';
			}
			$response = $this->utils->callService( $this, $request, $stepInfo );

			if( $response && $response->Reports ) { // Introduced in v8.0
				$errMsg = '';
				foreach( $response->Reports as $report ){
					foreach( $report->Entries as $reportEntry ) {
						$errMsg .= $reportEntry->Message . PHP_EOL;
					}
				}
				if( $errMsg ) {
					$this->throwError( 'DeleteObjects: failed: "'.$errMsg.'"' );
				}
			}
		} catch( BizException $e ) {
		}
	}

	/**
	 * Test auto naming with applyautonaming is true
	 *
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testFirstTrueAutoNaming( $appName )
	{
		$dossierName = 'dossier_'.$appName.'_123';
		$stepInfo = 'Test auto naming = true(Auto naming applied).';
		$expectedUniqueName = $dossierName . '_' . str_pad( 1, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
		$response = $this->createDossier( $dossierName, $stepInfo );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
			$this->autoRenamedDossierObjs[] = $response->Objects[0];
		}
	}

	/**
	 * Test auto naming with applyautonaming is false
	 *
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testFalseAutoNaming( $appName )
	{
		$dossierName = 'dossier_'.$appName.'_abc';
		$stepInfo = 'Test auto naming = false(No auto naming applied).';
		$expectedError = ($appName == 'web') ? '(S1025)' : null;
		$expectedUniqueName = $dossierName . '_' . str_pad( 1, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
		$response = $this->createDossier( $dossierName, $stepInfo, $expectedError );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
		}
	}

	/**
	 * Test auto naming when applyautonaming is null
	 *
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testNullAutoNaming( $appName )
	{
		$dossierName = 'dossier_'.$appName.'_xyz';
		$stepInfo = 'Test auto naming = null(Core server will decide the auto naming).';
		$expectedError = ( $appName == 'web' ) ? '(S1025)' : null;
		$expectedUniqueName = $dossierName.'_'.str_pad( 1, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
		$response = $this->createDossier( $dossierName, $stepInfo, $expectedError );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
		}
	}

	/**
	 * Create object for the first time when applyautonaming is null
	 *
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testFirstNullAutoNamingWithAccentedName( $appName )
	{
		$dossierName = self::getDossierNameWithAccent( $appName );
		$stepInfo = 'Test auto naming = null(Core server will decide the auto naming).';
		$expectedUniqueName = $dossierName;
		$response = $this->createDossier( $dossierName, $stepInfo );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
			$this->autoRenamedDossierObjs[] = $response->Objects[0];
		}
	}

	/**
	 * Create object for the second time with the same object name where applyautonaming is null.
	 *
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testSecondNullAutoNamingWithAccentedName( $appName )
	{
		$dossierName = self::getDossierNameWithAccent( $appName );
		$stepInfo = 'Test auto naming = null(Core server will decide the auto naming).';
		$expectedError = ( $appName == 'web' ) ? '(S1025)' : null;
		$expectedUniqueName = $dossierName.'_'.str_pad( 1, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
		$response = $this->createDossier( $dossierName, $stepInfo, $expectedError );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
		}
	}

	/**
	 * Move dossier object to TrashCan
	 *
	 */
	private function moveDossierToTrashCan()
	{
		if( $this->autoRenamedDossierObjs ) foreach( $this->autoRenamedDossierObjs as $autoRenamedDossierObj ) {
			$errorReport = null;
			$id = $autoRenamedDossierObj->MetaData->BasicMetaData->ID;
			$stepInfo = 'Move auto renamed dossier object with Name="'. $autoRenamedDossierObj->MetaData->BasicMetaData->Name.'" to TrashCan.';
			if( !$this->utils->deleteObject( $this, $this->ticket, $autoRenamedDossierObj->MetaData->BasicMetaData->ID, $stepInfo, $errorReport, null, false ) ) {
				$this->setResult( 'ERROR',  'Could not tear down object with id '.$id.'.' );
			}
		}
	}

	/**
	 * Test to create second time for the same object name when applyautonaming is true
	 * Expected unique object name appended with "0001" as suffix return
	 *
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testSecondTrueAutoNaming( $appName )
	{
		$dossierName = 'dossier_'.$appName.'_123';
		$expectedUniqueName = $dossierName . '_' . str_pad( 1, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
		$stepInfo = 'Test with dossier object with Name="'. $dossierName .'" that will return AutoNaming = true{Auto naming applied).';
		$response = $this->createDossier( $dossierName, $stepInfo );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
		}
	}

	/**
	 * Test to create third time for the same object name when applyautonaming is true
	 * Expected unique incremental name as '0002' from last auto naming object name in DB.
	 *
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testThirdTrueAutoNaming( $appName )
	{
		$dossierName = 'dossier_'.$appName.'_123';
		$expectedUniqueName = $dossierName . '_' . str_pad( 2, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
		$stepInfo = 'Test with dossier object with Name="'. $dossierName .'" that will return AutoNaming = true{Auto naming applied).';
		$response = $this->createDossier( $dossierName, $stepInfo );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
		}
	}

	/**
	 * Test to create second time for the same object name when applyautonaming is null
	 *
	 * There's one object in the TrashCan with the same object name,
	 * therefore the expected name should be the same as the original intended name ( no error should be thrown )
	 * When $appName = 'mover', applyautonaming is always true.
	 *
	 * @param string $appName Client application name Web|Mover
	 */
	private function testThirdNullAutoNamingWithAccentedName( $appName )
	{
		$dossierName = self::getDossierNameWithAccent( $appName );
		$stepInfo = 'Test auto naming = null(Core server will decide the auto naming).';
		$expectedUniqueName = $dossierName;
		$response = $this->createDossier( $dossierName, $stepInfo );
		if( $response ) {
			$this->assertInstanceOf( 'Object', $response->Objects[0] );
			$this->dossiers[] = $response->Objects[0];
			$this->assertEquals( $expectedUniqueName, $response->Objects[0]->MetaData->BasicMetaData->Name );
		}
	}

	/**
	 * Test auto naming when restore objects which name already exists in DB
	 */
	private function testRestoreObjectAutoNaming()
	{
		if( $this->autoRenamedDossierObjs ) {
			foreach( $this->autoRenamedDossierObjs as $autoRenamedDossierObj ) {
				$dossierId = $autoRenamedDossierObj->MetaData->BasicMetaData->ID;
				$stepInfo = 'Restore the auto rename dossier object from the TrashCan.';
				$expectedUniqueName = $autoRenamedDossierObj->MetaData->BasicMetaData->Name . '_' . str_pad( 1, AUTONAMING_NUMDIGITS, '0', STR_PAD_LEFT );
				$response = $this->restoreObject( $dossierId, $stepInfo );
				if( !$response ) {
					$this->setResult( 'ERROR',  'Could not restore dossier from trashCan with id '.$dossierId.'.' );
				}
			}
			$this->autoRenamedDossierObjs = null;
		}
	}
}