<?php

require_once dirname(__FILE__).'/../../config/config.php';
include_once BASEDIR.'/server/admin/global_inc.php'; // inputvar
require_once BASEDIR.'/server/utils/NumberUtils.class.php';

define('DIME', 'DIME');
define('TRANSFERSERVERHTTP', 'HTTP');
define('TRANSFERFOLDERHSCP', 'HSCP');

// Log start time at screen
$speedTester = new SpeedTester();
$completeSettingsWarnings = $speedTester->setBasicSettings();
$time_start = getMicrotime();
print '<html><body style="font-family: Arial;">';

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$testRuns = isset($_POST['runs']) ? intval($_POST['runs']) : 1;
$attachmentHandling = isset($_POST['attachment']) ? ($_POST['attachment']) : '';
$version = isset( $_POST['version']) ? ( $_POST['version']) : '';
$testDataSize = isset( $_POST['testDataSize'] ) ? $_POST['testDataSize'] : '';

// Show form to let user choose test params
print '<font style="font-family: Arial; font-size:11px;">';
print '<form action="speedtest.php" enctype="multipart/form-data" name="speedtestform" method="post">';
print 'Mode: '.inputvar( 'mode', $mode, 'combo', array('WorkflowServices' => 'Workflow Services', 'NamedQueries' => 'Named Queries', 'AutocompleteServices' => 'Autocomplete Services', 'MultiObjectWorkflow' => "Multi Object Workflow"), '' );
print '&nbsp;&nbsp;&nbsp;Attachment Handling: '.inputvar( 'attachment', $attachmentHandling, 'combo', 
	array(
		TRANSFERSERVERHTTP => 'Transfer Server / HTTP', 
		TRANSFERFOLDERHSCP => 'Transfer Folder / HSCP', 
		DIME => 'Dime Attachment',
	), '' );
print '&nbsp;&nbsp;&nbsp;Number of runs: '.inputvar( 'runs', $testRuns, 'combo', array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5' ), '' );
print '&nbsp;&nbsp;&nbsp;Version: '.inputvar( 'version', $version, 'combo', array( '9.2.0' => '9.2.0', '8.0.0' => '8.0.0', '7.0.0' => '7.0.0' ), '' );

// Only show the combo box to test with large data when the largedata sample folder(testdata/largeSpeedTestData) is available.
if( file_exists( dirname(__FILE__).'/testdata/largeSpeedTestData/' ) ) {
	$testDataSizeCombobox = array('small' => 'Small data', 'large' => 'Large data' );
} else {
	$testDataSizeCombobox = array('small' => 'Small data');
}
print '&nbsp;&nbsp;&nbsp;DataFile: '.inputvar( 'testDataSize', $testDataSize, 'combo', $testDataSizeCombobox, '' );
print '&nbsp;&nbsp;&nbsp;<input type="submit" name="test" value="Start Test"/>';
if ( $completeSettingsWarnings ) {
	print '<br />';
	foreach( $completeSettingsWarnings as $warning) {
		print '<font color="orange" size="2">'.$warning.'</font><br />';
	}
}
print '</form></font>';
	
if( !$mode ) {
	exit; // first time; let user choose run mode
}

$speedTester->setTestDataWeight( $testDataSize );
$speedTester->setAttachmentHandling( $attachmentHandling );
$speedTester->initializeClient();
$speedTester->setVersion( $version );

print ( 'Performing speed test....<br/><br/>Started at: '.date('H:i:s').'<br/>' );
/*
print 'Mode:' . $mode . '<br/>';
print 'Attachment Handling:' . $attachmentHandling . '<br/>';
print 'Number of runs:' . $testRuns . ' times.<br/>';
print 'Test run on server version ' . $version . '<br/>';
print 'Data file size:' . $testDataSize . ' data.<br/>';
print '<br/>';
*/
set_time_limit(3600);

// Log client initialisation time at screen
$time_end = getMicrotime();
$time = $time_end - $time_start;
printf( 'Client init time: %0.3fs<br/>', $time );
$speedTester->startReport( $testRuns );

for( $i = 1; $i <= $testRuns; $i++ ) {
	// Perform the speedtest
	$speedTester->startTestRun();
	
	switch( $_POST['mode'] ) { 
		case 'NamedQueries': // special test for all named queries only
			if( $i == 1 ) { // first time only
				if ( $speedTester->checkVersion('0.0.0')) { 
					// false => Do not want to time/record the service when it is only run once 
					// regardless of the number of $testRuns
					$speedTester->callLogOn( false ); 
				} 
			}
			$speedTester->startServerDurations();
			if ( $speedTester->checkVersion('0.0.0')) {$speedTester->callNamedQueries(); }
			$speedTester->stopServerDurations();
			if( $i == $testRuns ) { // last time only
				if ( $speedTester->checkVersion('0.0.0')) { 	
					// false => Do not want to time/record the service when it is only run once 
					// regardless of the number of $testRuns
					$speedTester->callLogOff( false ); 
				}
			}
			break;
		case 'AutocompleteServices':
			$speedTester->startServerDurations();
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callLogOn(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callAutocompleteService(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callLogOff(); }
			$speedTester->stopServerDurations();
			break;
		case 'MultiObjectWorkflow': // Tests (limited set of) workflow with many objects
			$speedTester->startServerDurations();
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callLogOn(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callMultiCreateObjects(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callMultiGetObjects(); }
			if ( $speedTester->checkVersion('9.2.0') ) { $speedTester->callMultiSetObjectProperties(); }
			if( !isset($_GET['skipdelete']) ) {
				$areas = array('Workflow');
				if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callMultiDeleteObjects(false, $areas); }
				$areas = array('Trash');
				if ( $speedTester->checkVersion('8.0.0')) { $speedTester->callMultiDeleteObjects(true, $areas); } //replace purgeObjects
			}
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callLogOff(); }
			$speedTester->stopServerDurations();
			break;
		case 'WorkflowServices': // default: full test of all workflow services
		default:
			$speedTester->startServerDurations();
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callGetServers(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callLogOn(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callCreateObjects(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callGetObjects(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callSaveObjects(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callUnlockObjects(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callSendTo(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callCreateObjectRelations(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callUpdateObjectRelations(); }
	  		if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callGetObjectRelations(); }
			if( !isset($_GET['skipdelete']) ) {
				if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callDeleteObjectRelations(); }
			}
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callCopyObject(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callSetObjectProperties(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callQueryObjects(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callNamedQuery(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callChangePassword(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callSendMessages(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callListVersions(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callGetVersion(); }
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callRestoreVersion(); }
			if( !isset($_GET['skipdelete']) ) {
				$areas = array('Workflow');
				if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callDeleteObjects(false, $areas); }
				if ( $speedTester->checkVersion('8.0.0')) { $speedTester->callRestoreObjects(); }
				$areas = array('Workflow');
				if ( $speedTester->checkVersion('8.0.0')) { $speedTester->callDeleteObjects(false,$areas); }
				$areas = array('Trash');
				if ( $speedTester->checkVersion('8.0.0')) { $speedTester->callDeleteObjects(true,$areas); } //replace purgeObjects
			}
			if ( $speedTester->checkVersion('0.0.0')) { $speedTester->callLogOff(); }
			$speedTester->stopServerDurations();
			break;
	} // end switch
	$speedTester->stopTestRun();
}

$speedTester->stopReport();

// Log duration and end time
print '<br/>';
print 'Completed at: '.date('H:i:s').'<br/>';

$time_end = getMicrotime();
$time = $time_end - $time_start;
printf( 'Time elapsed: %0.3fs<br/>', $time );
print '</body></html>';

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

/**
 * Return the current time in seconds.
 */
function getMicrotime()
{ 
	list($usec, $sec) = explode(" ",microtime()); 
	return ((float)$usec + (float)$sec); 
}

/**
 * To execute web services or named queries service calls
 * to measure the time taken for all the service calls executed.
 * Test unit can be done in workflow service test or namedQuery test.
 * To get the average, one unit test can be executed more than one time.
 * Max run is five times. 
 */
class SpeedTester
{
	// Session details
	private $soapClient;
	private $user;
	private $password;
	private $ticket;
	private $namedQuery;
	private $namedQueries;
	private $attachmentHandling;
	private $version;
	private $speedTestReport;	
	private $uploadedFileSize;
	private $downloadedFileSize;
	private $multiMode;

	// Layout test object details
	private $layoutId;
	private $layoutName;
	private $layoutVersion;
	private $layoutStatusInfo;
	private $copyLayoutId;
	private $layoutPath;

	// Article test object details
	private $articleId;
	private $articleName;
	private $articleStatusInfo;
	private $content;

	// Dossier test object details
	private $dossierStatusInfo;
	
	// Terminology used before- or since v6
	private $pubTerm;
	private $categoryTerm;

	// Properties to use for a test layout
	private $publicationInfo = null;
	private $issueInfo;
	private $categoryInfo;

	public function __construct()
	{
		$this->speedTestReport = new SpeedTestReport();
		$this->avgDurations[] = array();
	}

	/**
	 * Based on the TESTSUITE options Basisc settings as user, password
	 * and publication info are resolved. 
	 * @return array of strings Warnings generated due to missing options.
	 */
	public function setBasicSettings()
	{
		$result = array();

		// Enterprise user account for speedtest session
		if( defined('TESTSUITE') ) {
			$suiteOpts = unserialize( TESTSUITE );
			$this->user = $suiteOpts['User'] ?  $suiteOpts['User'] : '';
			$this->password = $suiteOpts['Password'] ? $suiteOpts['Password'] : '';
			$publicationName = $suiteOpts['Brand'] ? $suiteOpts['Brand'] : '';
			$issueName = $suiteOpts['Issue'] ? $suiteOpts['Issue'] : '';
		} else {
			$result[] = 'No TESTSUITE setting at configserver.php. The speedtest will use default settings.';
		}

		/*if( defined('TESTSUITE7') ) { // @TODO: Maybe support v7 server.
			$suiteOpts = unserialize( TESTSUITE );
			$this->url = $suiteOpts['URL'] ?  $suiteOpts['URL'] : '';
			$this->user = $suiteOpts['User'] ?  $suiteOpts['User'] : '';
			$this->password = $suiteOpts['Password'] ? $suiteOpts['Password'] : '';
			$publicationName = $suiteOpts['Brand'] ? $suiteOpts['Brand'] : '';
			$issueName = $suiteOpts['Issue'] ? $suiteOpts['Issue'] : '';
		}*/

		$incomplete = false;
		if ( empty($this->user) ) {
			$result[] = 'Default user is set to "woodwing".';
			$this->user = 'woodwing';
			$incomplete = true;
		}

		if ( empty($this->password) ) {
			$result[] = 'Default password is set to "ww".';
			$this->password = 'ww';
			$incomplete = true;
		}

		if ( empty($publicationName) ) {
			$result[] = 'Default Brand will be retrieved from the Log On response.';
			$incomplete = true;
		} else {
			require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
			$publicationInfos = BizPublication::getPublications($this->user, 'full');	
			if ( $publicationInfos ) foreach( $publicationInfos as $publicationInfo ) {
				if ( $publicationInfo->Name === $publicationName) {
					$this->publicationInfo = $publicationInfo;
					break;
				}
			}
			if ( is_null( $this->publicationInfo)) {
				$result[] = 'TestSuite Brand is not found. Default Brand will be retrieved from the Log On response.';
				$incomplete = true;
			}	
		}

		if ( empty($issueName) ) {
			$incomplete = true;
			if ( $this->publicationInfo ) {
				$result[] = 'TestSuite Issue is not set. Default Issue will be retrieved from the TestSuite Brand.';
			} else {
				$result[] = 'TestSuite Issue is not set. Default Issue will be retrieved from the Log On response.';
			}
		} else {
			// Only resolve if a valid publication was found.
			$issueId = null;
			if ( $this->publicationInfo) {
				require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
				$publicationIssues = BizPublication::getIssues($this->user, $this->publicationInfo->Id, 'flat');
				if ( $publicationIssues ) foreach( $publicationIssues as $publicationIssue ) {
					if ( $publicationIssue->Name === $issueName ) {
						$issueId = $publicationIssue->Id;
						break;
					}
				}
				if ( is_null( $issueId)) {
					if ( $this->publicationInfo ) {
						$result[] = 'TestSuite Issue is not found. Default Issue will be retrieved from the TestSuite Brand.';
					} else {
						$result[] = 'TestSuite Issue is not found. Default Issue will be retrieved from the Log On response.';
					}	
				}
			} else {
				$result[] = 'TestSuite Issue cannot be determined. Default Issue will be retrieved from the Log On response.';
			}
		}

		if ( $incomplete ) {
			$result = array_merge( array('TestSuite options are incomplete:'), $result);
		}

		if ( $this->publicationInfo ) {
			$this->findPublicationForLayout( array($this->publicationInfo), $issueId);
		}

		return $result;
	}	

	/**
	 * Set the Enterprise version the unit test is going to run in.
	 */
	public function setVersion( $version )
	{
		$this->version = $version;
	}

	/**
	 * Set unit test in multi mode
	 */
	public function setMultiMode( $multiMode )
	{
		$this->multiMode = $multiMode;
	}

	/**
	 * Set the test data whether it should involve
	 * small files* or large files*.
	 * files* = layout, article, image
	 */
	public function setTestDataWeight( $testDataSize )
	{
		$this->testDataSize = $testDataSize;
	}

	/**
	 * Initialize the client before the test run is executed.
	 */
	public function initializeClient()
	{
		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		$options = array(
			'transfer' => $this->attachmentHandling, 
			'protocol' => 'SOAP',
//			'location' => 'http://127.0.0.1/Ent76x/index.php' // Uncomment this if connecting to Ent v7.
		);
		$this->soapClient = new WW_SOAP_WflClient( $options );		
	}
	
	/**
	 * Set if the attachment handling is either one of the three below:
	 * - Transfer Server over HTTP
	 * - Transfer Server over HSCP
	 * - Dime attachment
	 *
	 */
	public function setAttachmentHandling( $attachmentHandling )
	{
		$this->attachmentHandling = $attachmentHandling;	
	}

	/**
	 * Indicates that the speed test is about to run.
	 * Calls speedTestReport->startReport to do the neccessary initialization.
	 *
	 */
	public function startReport( $totalTestRun ) { $this->speedTestReport->startReport( $totalTestRun ); }
	public function stopReport()  { $this->speedTestReport->stopReport();  }
	
	/*
	 * Indicates the start and stop of Nth test run.
	 * N can be 1 till 5.
	 */
	public function startTestRun() { $this->speedTestReport->startTestRun(); }
	public function stopTestRun()  { $this->speedTestReport->stopTestRun();  }

	/**
	 * Start and stop the server recording.
	 * For certain service call test, it is not needed to be recorded, thus stopServerDurations()
	 * will be called for that particular service call so that no time measurement is taken.
	 * To resume the recording, startServerDurations() can be called.
	 */
	public function startServerDurations() { $this->speedTestReport->startServerDurations(); }
	public function stopServerDurations()  { $this->speedTestReport->stopServerDurations(); }

	/**
	 * GetServers
	 */
	public function callGetServers()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetServersRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetServersResponse.class.php';
		$req = new WflGetServersRequest();
		$this->callSoapService( 'GetServers', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * Autocomplete
	 */
	public function callAutocompleteService()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflAutocompleteRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflAutocompleteResponse.class.php';
		$req = new WflAutocompleteRequest();
		$req->Ticket               = $this->ticket;
		$req->AutocompleteProvider = 'AutocompleteSample';
		$req->PublishSystemId      = '';
		$req->Property             = new AutoSuggestProperty( 'C_CITIES', 'City', array ( 'Amsterdam' ) );
		$req->TypedValue           = 'ams';
		$this->callSoapService( 'Autocomplete', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * LogOn
	 */
	public function callLogOn( $calculateAverage=true )
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnResponse.class.php';
		if( $this->version == '8.0.0' ) {
			$serverVersion = 'v'.SERVERVERSION;
		} else {
			$serverVersion = 'v'.$this->version . ' Build 0'; // For v7, build number is not interesting.
		}
		$req = new WflLogOnRequest( 
			// $User, $Password, $Ticket, $Server, $ClientName, $Domain,
			$this->user, $this->password, '', '', '', '',
			//$ClientAppName, $ClientAppVersion, $ClientAppSerial, $ClientAppProductKey, $RequestTicket, $RequestInfo
			'Logon SOAP Test', $serverVersion, '', '', null, null );
		$resp = $this->callSoapService( 'LogOn', $req );
		$this->parseLogOnResponse( $resp );
		$this->speedTestReport->stopTestCase( $calculateAverage );
	}

	/**
	 * CreateObjects
	 */
	public function callCreateObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsResponse.class.php';

		// Create layout
		$this->speedTestReport->startTestCase();
		$this->layoutName = 'LayTest '.date("m d H i s");
		$layoutObj = $this->buildLayoutObject( null, $this->layoutName );
		$req = new WflCreateObjectsRequest( $this->ticket, false, array($layoutObj) );
		$resp = $this->callSoapService( 'CreateObjects', $req );
		$this->layoutId = $resp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->layoutName = $resp->Objects[0]->MetaData->BasicMetaData->Name;
		$this->speedTestReport->addComment( "Created layout object \"$this->layoutName\" (id=$this->layoutId)" );
		$this->speedTestReport->stopTestCase();
		
		// Create article
		$this->speedTestReport->startTestCase();
		$this->articleName = 'ArtTest '.date("m d H i s");
		$articleObj = $this->buildArticleObject( null, $this->articleName );
		$req = new WflCreateObjectsRequest( $this->ticket, false, array($articleObj) );
		$resp = $this->callSoapService( 'CreateObjects', $req );
		$this->articleId = $resp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->articleName = $resp->Objects[0]->MetaData->BasicMetaData->Name;
		$this->speedTestReport->addComment( "Created article object \"$this->articleName\" (id=$this->articleId)" );
		$this->speedTestReport->stopTestCase();

		// Create Image
		$this->speedTestReport->startTestCase();
		$this->imageName = 'ImgTest '.date("m d H i s");
		$imageObj = $this->buildImageObject( null, $this->imageName );
		$req = new WflCreateObjectsRequest( $this->ticket, false, array($imageObj) );
		$resp = $this->callSoapService( 'CreateObjects', $req );
		$this->imageId = $resp->Objects[0]->MetaData->BasicMetaData->ID;
		$this->imageName = $resp->Objects[0]->MetaData->BasicMetaData->Name;
		$this->speedTestReport->addComment( "Created image object \"$this->imageName\" (id=$this->imageId)" );
		$this->speedTestReport->stopTestCase();
		
	}

	/**
	 * GetObjects
	 */
	public function callGetObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsResponse.class.php';
		$ids = array( $this->layoutId, $this->articleId, $this->imageId );
		foreach( $ids as $id ) {
			$this->speedTestReport->startTestCase();
			$this->downloadedFileSize = 0;
			$req = new WflGetObjectsRequest( $this->ticket, array( $id ), true, 'native' );
			$resp = $this->callSoapService( 'GetObjects', $req );
			$objName = $resp->Objects[0]->MetaData->BasicMetaData->Name;
			$objType = $resp->Objects[0]->MetaData->BasicMetaData->Type;
			$this->speedTestReport->addComment( 'Get '. $objType .' object "'. $objName .'" (id="'.$id.')"' );

			foreach( $resp->Objects as $object ) { // should be only one object
				if ( $object->Files ) foreach ($object->Files as $file ) {
					$this->downloadFile( $file );
				}
				if ( $object->Pages ) foreach ( $object->Pages as $page ) {
					if ( $page->Files ) foreach ( $page->Files as $file ) {
						$this->downloadFile( $file );
					}
				}
			}
			if( $this->downloadedFileSize > 0 ) {
				$this->speedTestReport->addComment( 'Downloaded file size:'.
					NumberUtils::getByteString( $this->downloadedFileSize ) );
			}
			$this->speedTestReport->stopTestCase();
		}
	}

	/**
	 * GetStates
	 */
	public function callGetStates()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesResponse.class.php';
		$req = new WflGetStatesRequest( $this->ticket, $this->layoutId, $this->imageId );
		$this->callSoapService( 'GetStates', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * SaveObjects
	 */
	public function callSaveObjects()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSaveObjectsResponse.class.php';
		$layoutObj = $this->buildLayoutObject( $this->layoutId, $this->layoutName );
		$req = new WflSaveObjectsRequest( $this->ticket, true, false, false, array($layoutObj) );
		$this->callSoapService( 'SaveObjects', $req );
		$this->speedTestReport->stopTestCase();
	}
	
	/**
	 * UnlockObjects
	 */
	public function callUnlockObjects()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsResponse.class.php';
		$ids = array( $this->layoutId, $this->articleId, $this->imageId );
		$req = new WflUnlockObjectsRequest( $this->ticket, $ids );
		$this->callSoapService( 'UnlockObjects', $req );
		$this->speedTestReport->stopTestCase();
	}
	
	/**
	 * SendTo
	 */
	public function callSendTo()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSendToRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSendToResponse.class.php';
		$ids = array( $this->layoutId );
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline = date('Y-m-d\TH:i:s'); 
		$wflMD->Urgency  = 'Top';
		$wflMD->State    = new State( $this->layoutStatusInfo->Id, $this->layoutStatusInfo->Name );
		$wflMD->RouteTo  = $this->user;
		$wflMD->Comment  = 'After SendTo';
		$req = new WflSendToRequest( $this->ticket, $ids, $wflMD );
		$this->callSoapService( 'SendTo', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * CreateObjectRelations
	 */
	public function callCreateObjectRelations()
	{
		$this->speedTestReport->startTestCase();
		// place article onto layout
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectRelationsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectRelationsResponse.class.php';
		$pls = array();
		$pls[] = new Placement( array( new Page( '1', 'el', 'eid', 2, 'a', 10, 10, 100, 100 )), null, null, 0, null, 0, 0, 0, 0, null, null, null, null, null, null, null, null, null, null, 1, 1);
		$pls[] = new Placement( array( new Page( '2', 'el', 'eid', 3, 'b', 100, 100, 100, 100 )), null, null, 0, null, 0, 0, 0, 0, null, null, null, null, null, null, null, null, null, null, 1, 1);
		$rels = array( new Relation( $this->layoutId, $this->articleId, 'Placed', $pls ));
		$req = new WflCreateObjectRelationsRequest( $this->ticket, $rels );
		$this->callSoapService( 'CreateObjectRelations', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * UpdateObjectRelations
	 */
	public function callUpdateObjectRelations()
	{
		$this->speedTestReport->startTestCase();
		$this->uploadedFileSize = 0;
		require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectRelationsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectRelationsResponse.class.php';
		$geoAtt = new Attachment();
		$geoAtt->Type = 'xml';
		$geoAtt->Rendition = 'native';
		$geoAtt->Content = 'test file xml';
		$this->uploadFile( $geoAtt );
		if( $this->uploadedFileSize > 0 ) {
			$this->speedTestReport->addComment( 'Uploaded file size:'.
					NumberUtils::getByteString( $this->uploadedFileSize ) );
		}		
		
		$rel = new Relation( $this->layoutId, $this->articleId, 'Placed' );
		$rel->Geometry = $geoAtt;
		$req = new WflUpdateObjectRelationsRequest( $this->ticket, array($rel) );
		$this->callSoapService( 'UpdateObjectRelations', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * GetObjectRelations
	 */
	public function callGetObjectRelations()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectRelationsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectRelationsResponse.class.php';
		$req = new WflGetObjectRelationsRequest( $this->ticket, $this->layoutId );
		$this->callSoapService( 'GetObjectRelations', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * DeleteObjectRelations
	 */
	public function callDeleteObjectRelations()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectRelationsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectRelationsResponse.class.php';
		$rels = array( new Relation( $this->layoutId, $this->articleId, 'Placed', null ) );
		$req = new WflDeleteObjectRelationsRequest( $this->ticket, $rels );
		$this->callSoapService( 'DeleteObjectRelations', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * CopyObject
	 */
	public function callCopyObject()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObjectRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCopyObjectResponse.class.php';
		$meta = $this->buildLayoutMetaData( null, 'LayCopy '.date("m d H i s"), null );
		$objTarget = $this->buildObjectTarget();

		$req = new WflCopyObjectRequest();
		$req->Ticket   = $this->ticket;
		$req->SourceID = $this->layoutId;
		$req->MetaData = $meta;
		$req->Targets  = array( $objTarget );		
		$resp = $this->callSoapService( 'CopyObject', $req );
		$this->copyLayoutId = $resp->MetaData->BasicMetaData->ID;
		$copyObjName = $resp->MetaData->BasicMetaData->Name;
		$this->speedTestReport->addComment( "Copied layout object \"$copyObjName\" (id=$this->copyLayoutId)" );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * SetObjectProperties
	 */
	public function callSetObjectProperties()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectPropertiesRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSetObjectPropertiesResponse.class.php';
		$meta = $this->buildLayoutMetaData( $this->layoutId, $this->layoutName, null );
		$objTarget = $this->buildObjectTarget();
		
		$req = new WflSetObjectPropertiesRequest();
		$req->Ticket   = $this->ticket;
		$req->ID       = $this->layoutId;
		$req->MetaData = $meta;
		$req->Targets  = array( $objTarget );
		$this->callSoapService( 'SetObjectProperties', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * QueryObjects
	 */
	public function callQueryObjects()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsResponse.class.php';
		$ps = array();
		$ps[] = new QueryParam( 'Publication', "=", $this->publicationInfo->Name );
		$ps[] = new QueryParam( 'Category', "=", $this->categoryInfo->Name );
		$req = new WflQueryObjectsRequest( $this->ticket, $ps );
		$resp = $this->callSoapService( 'QueryObjects', $req );
		$totalEntries = $resp->TotalEntries > 0 ? $resp->TotalEntries : count($resp->Rows); // work around v5 bug to have at least some indication
		$this->speedTestReport->addComment( "Found ".$totalEntries." objects when querying for $this->categoryTerm \"{$this->categoryInfo->Name}\" of $this->pubTerm \"{$this->publicationInfo->Name}\"." );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * NamedQuery
	 */
	public function callNamedQuery()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		$ps = array();
		$req = new WflNamedQueryRequest( $this->ticket, $this->namedQuery, $ps );
		$resp = $this->callSoapService( 'NamedQuery', $req );
		$totalEntries = $resp->TotalEntries > 0 ? $resp->TotalEntries : count($resp->Rows); // work around v5 bug to have at least some indication
		$this->speedTestReport->addComment( "Found ".$totalEntries." objects using Named Query \"$this->namedQuery\"." );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * NamedQueries
	 */
	public function callNamedQueries()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryResponse.class.php';
		if( count( $this->namedQueries ) > 0 ) {
			foreach( $this->namedQueries as $namedQuery ) {
				$ps = array();
				$req = new WflNamedQueryRequest( $this->ticket, $namedQuery->Name, $ps );
				$resp = $this->callSoapService( 'NamedQuery', $req );
				$totalEntries = $resp->TotalEntries > 0 ? $resp->TotalEntries : count($resp->Rows); // work around v5 bug to have at least some indication
				$this->speedTestReport->addComment( "Found ".$totalEntries." objects using Named Query \"$namedQuery->Name\"." );
			}
		}
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * ChangePassword
	 */
	public function callChangePassword()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflChangePasswordRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflChangePasswordResponse.class.php';
		$req = new WflChangePasswordRequest( $this->ticket, $this->password, $this->password, null );
		$this->callSoapService( 'ChangePassword', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * callSendMessages
	 */
	public function callSendMessages()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSendMessagesRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflSendMessagesResponse.class.php';
		$messages = array();
		$messages[] = new Message( $this->layoutId,  null, 'Client', 'system', 'DesignUpdate', 'Is my hair ok?', date('Y-m-d\TH:i:s'));
		$messages[] = new Message( $this->layoutId, null, 'Client', 'system', 'DesignUpdate', 'Is my hair ok?', date('Y-m-d\TH:i:s'));
		$req = new WflSendMessagesRequest( $this->ticket, $messages );
		$this->callSoapService( 'SendMessages', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * ListVersions
	 */
	public function callListVersions()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflListVersionsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflListVersionsResponse.class.php';
		$req = new WflListVersionsRequest( $this->ticket, $this->layoutId );
		$resp = $this->callSoapService( 'ListVersions', $req );
		$this->layoutVersion = $resp->Versions[0]->Version;
		$this->speedTestReport->addComment( "Found object version number: $this->layoutVersion" );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * GetVersion
	 */
	public function callGetVersion()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetVersionRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetVersionResponse.class.php';
		$req = new WflGetVersionRequest( $this->ticket, $this->layoutId, $this->layoutVersion, 'thumb' );
		$this->callSoapService( 'GetVersion', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * RestoreVersion
	 */
	public function callRestoreVersion()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreVersionRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreVersionResponse.class.php';
		$req = new WflRestoreVersionRequest( $this->ticket, $this->layoutId, $this->layoutVersion );
		$this->callSoapService( 'RestoreVersion', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * DeleteObjects
	 */
	public function callDeleteObjects( $permanent, $areas )
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
		$ids = array( $this->layoutId, $this->articleId, $this->copyLayoutId, $this->imageId );
		$req = new WflDeleteObjectsRequest( $this->ticket, $ids, $permanent, null /*$params*/, $areas );
		$this->callSoapService( 'DeleteObjects', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * RestoreObjects
	 */
	public function callRestoreObjects()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflRestoreObjectsResponse.class.php';
		$ids = array( $this->layoutId, $this->articleId, $this->copyLayoutId, $this->imageId );
		$req = new WflRestoreObjectsRequest( $this->ticket, $ids );

		$this->callSoapService( 'RestoreObjects', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * MultiCreateObjects
	 */
	public function callMultiCreateObjects()
	{
		$this->speedTestReport->startTestCase();

		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsResponse.class.php';

		$numberOfObjects = $this->testDataSize == 'small' ? 40 : 200;

		// Create $numberOfObjects dossier objects
		$this->dossierObjects = array();
		$multiDossiersMeta = array();
		for( $i = 0; $i < $numberOfObjects; $i++ ) {
			$dossierName = 'DossierMultiSetTest ' . $i .date("m d H i s");
			$dossierObj = $this->buildDossierObject( null, $dossierName );
			$multiDossiersMeta[] = $dossierObj;
		}

		$req = new WflCreateObjectsRequest( $this->ticket, false, $multiDossiersMeta );
		$resp = $this->callSoapService( 'CreateObjects', $req );

		foreach( $resp->Objects as $obj ) {
			$this->dossierObjects[] = $obj->MetaData->BasicMetaData->ID;
		}

		$this->speedTestReport->addComment( 'Created ' . $numberOfObjects . ' dossier objects' );

		$this->speedTestReport->stopTestCase();
	}

	/**
	 * Deletes a large set of objects at once
	 *
	 * @param array $permanent Delete forever, not to trashcan.
	 * @param array $areas Area from which to be deleted (workflow, trash)
	 */
	public function callMultiDeleteObjects( $permanent, $areas )
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
		$ids = $this->dossierObjects;
		$req = new WflDeleteObjectsRequest( $this->ticket, $ids, $permanent, null /*$params*/, $areas );
		$this->callSoapService( 'DeleteObjects', $req );

		if( $permanent ) {
			$this->speedTestReport->addComment( 'Delete permanently from area(s) ' . implode(',', $areas) );
		} else {
			$this->speedTestReport->addComment( 'Delete from area(s) ' . implode(',', $areas) . ' to Trash' );
		}

		$this->speedTestReport->stopTestCase();
	}


	/**
	 * MultiGetObjects
	 */
	public function callMultiGetObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsResponse.class.php';
		$ids = $this->dossierObjects;

		$this->speedTestReport->startTestCase();
		$req = new WflGetObjectsRequest( $this->ticket, $ids, false, 'native' );
		$this->callSoapService( 'GetObjects', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * MultiSetObjectProperties
	 */
	public function callMultiSetObjectProperties()
	{
		$this->speedTestReport->startTestCase();

		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsResponse.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectPropertiesRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectPropertiesResponse.class.php';

		// Set property using the multi-set object property
		$mdValue = new MetaDataValue();
		$mdValue->Property = 'Comment';
		$propValue = new PropertyValue();
		$propValue->Value = 'Multi-set comment';
		$mdValue->PropertyValues = array( $propValue );

		$req = new WflMultiSetObjectPropertiesRequest();
		$req->Ticket   = $this->ticket;
		$req->IDs = $this->dossierObjects;
		$req->MetaData = array( $mdValue );
		$this->callSoapService( 'MultiSetObjectProperties', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * LogOff
	 */
	public function callLogOff( $calculateAverage=true )
	{
		// Make analyzer happy
		$calculateAverage = $calculateAverage;

		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffResponse.class.php';
		$setting = array();
		$setting[] = new Setting( "Time", date("H:i:s") );
		$setting[] = new Setting( "Date", date("Y-M-D") );
		$req = new WflLogOffRequest( $this->ticket, true, $setting );
		$this->callSoapService( 'LogOff', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * Builds workflow Object for a layout.
	 */
	private function buildLayoutObject( $layoutId, $layoutName )
	{
		$this->uploadedFileSize = 0;
		if( $this->testDataSize == 'small' ) {
			$newObj = $this->buildSmallLayoutObject( $layoutId, $layoutName );
		} elseif( $this->testDataSize == 'large' ) {
			$newObj = $this->buildBigLayoutObject( $layoutId, $layoutName );
		}
	
		if( $this->uploadedFileSize > 0 ) {
			$this->speedTestReport->addComment( 'Uploaded file size:'.
				NumberUtils::getByteString( $this->uploadedFileSize ) );
		}
		return $newObj;
	}
	
	/**
	 * Build layout object: This will be called when 
	 * $this->testDataSize is set to small.
	 */
	private function buildSmallLayoutObject( $layoutId, $layoutName )
	{		
		$this->layoutPath = dirname(__FILE__) . '/testdata/native1.indd';

		$native = new Attachment();
		$native->FilePath = $this->layoutPath;
		$native->Rendition = 'native';
		$native->Type = 'application/indesign';
		$this->uploadFile( $native );

		$thumb = new Attachment();
		$thumb->FilePath = dirname(__FILE__) . '/testdata/thumb1.jpg';
		$thumb->Rendition = 'thumb';
		$thumb->Type = 'image/jpeg';
		$this->uploadFile( $thumb );
		
		$page1 = new Attachment();
		$page1->FilePath = dirname(__FILE__) . '/testdata/preview1page1.jpg';
		$page1->Rendition = 'preview';
		$page1->Type = 'image/jpeg';
		$this->uploadFile( $page1 );
		
		$page2 = new Attachment();
		$page2->FilePath = dirname(__FILE__) . '/testdata/preview1page2.jpg';
		$page2->Rendition = 'preview';
		$page2->Type = 'image/jpeg';
		$this->uploadFile( $page2 );
		
		$fileSize = filesize($native->FilePath);
		$files = array( $native, $thumb );
		$pag1Att = array( $page1 );
		$pag2Att = array( $page2 );

		$fileSize = filesize($native->FilePath);
		$pages = array(
			new Page( 400, 300, 'pag2', 2, $pag1Att, null, null, 'Production'  ),
			new Page( 400, 300, 'pag3', 3, $pag2Att,  null, null, 'Production') );
		$meta = $this->buildLayoutMetaData( $layoutId, $layoutName, $fileSize );
		$objTarget = $this->buildObjectTarget();
		
		$newObj = new Object();
		$newObj->MetaData  = $meta;
		$newObj->Relations = array();
		$newObj->Pages     = $pages;
		$newObj->Files     = $files;
		$newObj->Targets   = array( $objTarget );
		
		return $newObj;
	}

	/**
	 * Build layout object: This will be called when 
	 * $this->testDataSize is set to large.
	 */	
	private function buildBigLayoutObject( $layoutId, $layoutName )
	{
		// Build MetaData
		$newObj = new Object();
		$newObj->MetaData = new MetaData();
		$newObj->MetaData->BasicMetaData = new BasicMetaData();
		$newObj->MetaData->BasicMetaData->ID = $layoutId;
		$newObj->MetaData->BasicMetaData->DocumentID = 'xmp.did:F6AF1330152068118A6D913082738720';
		$newObj->MetaData->BasicMetaData->Name = $layoutName;
		$newObj->MetaData->BasicMetaData->Type = 'Layout';
		$newObj->MetaData->BasicMetaData->Publication = new Publication();
		$newObj->MetaData->BasicMetaData->Publication->Id = $this->publicationInfo->Id;
		$newObj->MetaData->BasicMetaData->Publication->Name = $this->publicationInfo->Name;
		$newObj->MetaData->BasicMetaData->Category = new Category();
		$newObj->MetaData->BasicMetaData->Category->Id = $this->categoryInfo->Id;
		$newObj->MetaData->BasicMetaData->Category->Name = $this->categoryInfo->Name;
		$newObj->MetaData->BasicMetaData->ContentSource = null;
		$newObj->MetaData->RightsMetaData = new RightsMetaData();
		$newObj->MetaData->RightsMetaData->CopyrightMarked = null;
		$newObj->MetaData->RightsMetaData->Copyright = 'copyright';
		$newObj->MetaData->RightsMetaData->CopyrightURL = null;
		$newObj->MetaData->SourceMetaData = new SourceMetaData();
		$newObj->MetaData->SourceMetaData->Credit = null;
		$newObj->MetaData->SourceMetaData->Source = null;
		$newObj->MetaData->SourceMetaData->Author = $this->user;
		$newObj->MetaData->ContentMetaData = new ContentMetaData();
		$newObj->MetaData->ContentMetaData->Description = null;
		$newObj->MetaData->ContentMetaData->DescriptionAuthor = null;
		$newObj->MetaData->ContentMetaData->Keywords = array("Key", "word");
		$newObj->MetaData->ContentMetaData->Slugline = null;
		$newObj->MetaData->ContentMetaData->Format = 'application/indesign';
		$newObj->MetaData->ContentMetaData->Columns = 0;
		$newObj->MetaData->ContentMetaData->Width = 0;
		$newObj->MetaData->ContentMetaData->Height = 0;
		$newObj->MetaData->ContentMetaData->Dpi = 0;
		$newObj->MetaData->ContentMetaData->LengthWords = 0;
		$newObj->MetaData->ContentMetaData->LengthChars = 0;
		$newObj->MetaData->ContentMetaData->LengthParas = 0;
		$newObj->MetaData->ContentMetaData->LengthLines = 0;
		$newObj->MetaData->ContentMetaData->PlainContent = null;
		$newObj->MetaData->ContentMetaData->FileSize = 50581504;
		$newObj->MetaData->ContentMetaData->ColorSpace = null;
		$newObj->MetaData->ContentMetaData->HighResFile = null;
		$newObj->MetaData->ContentMetaData->Encoding = null;
		$newObj->MetaData->ContentMetaData->Compression = null;
		$newObj->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$newObj->MetaData->ContentMetaData->Channels = null;
		$newObj->MetaData->ContentMetaData->AspectRatio = null;
		$newObj->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$newObj->MetaData->WorkflowMetaData->Deadline = date('Y-m-d\TH:i:s'); 
		$newObj->MetaData->WorkflowMetaData->Urgency = 'Top';
		$newObj->MetaData->WorkflowMetaData->Modifier = null;
		$newObj->MetaData->WorkflowMetaData->Modified = null;
		$newObj->MetaData->WorkflowMetaData->Creator = null;
		$newObj->MetaData->WorkflowMetaData->Created = null;
		$newObj->MetaData->WorkflowMetaData->Comment = 'CREATE OBJECT';
		$newObj->MetaData->WorkflowMetaData->State = new State();
		$newObj->MetaData->WorkflowMetaData->State->Id = $this->layoutStatusInfo->Id;
		$newObj->MetaData->WorkflowMetaData->State->Name = $this->layoutStatusInfo->Name;
		$newObj->MetaData->WorkflowMetaData->State->Type = null;
		$newObj->MetaData->WorkflowMetaData->State->Produce = null;
		$newObj->MetaData->WorkflowMetaData->State->Color = null;
		$newObj->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$newObj->MetaData->WorkflowMetaData->RouteTo = $this->user;
		$newObj->MetaData->WorkflowMetaData->LockedBy = null;
		$newObj->MetaData->WorkflowMetaData->Version = null;
		$newObj->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$newObj->MetaData->WorkflowMetaData->Rating = null;
		$newObj->MetaData->WorkflowMetaData->Deletor = null;
		$newObj->MetaData->WorkflowMetaData->Deleted = null;
		$newObj->MetaData->ExtraMetaData = null;
		$newObj->Relations = array();

		// Build list of Pages
		$newObj->Pages = array();
		for( $p = 0; $p < 20; $p++ ) {
			$newObj->Pages[$p] = new Page();
			$newObj->Pages[$p]->Width = 595.275591;
			$newObj->Pages[$p]->Height = 841.889764;
			$newObj->Pages[$p]->PageNumber = (string)$p;
			$newObj->Pages[$p]->PageOrder = $p;
			$newObj->Pages[$p]->Files = array();
			
			// Build page thumb
			$newObj->Pages[$p]->Files[0] = new Attachment();
			$newObj->Pages[$p]->Files[0]->Rendition = 'thumb';
			$newObj->Pages[$p]->Files[0]->Type = 'image/jpeg';
			$newObj->Pages[$p]->Files[0]->FileUrl = null;
			$newObj->Pages[$p]->Files[0]->EditionId = '';
			
			$inputPath = dirname(__FILE__).'/testdata/largeSpeedTestData/page_thumb.jpg';
			$newObj->Pages[$p]->Files[0]->Content = null;
			$newObj->Pages[$p]->Files[0]->FilePath = $inputPath;
			$this->uploadFile( $newObj->Pages[$p]->Files[0] );
			
			// Build page preview
			$newObj->Pages[$p]->Files[1] = new Attachment();
			$newObj->Pages[$p]->Files[1]->Rendition = 'preview';
			$newObj->Pages[$p]->Files[1]->Type = 'image/jpeg';
			$newObj->Pages[$p]->Files[1]->FileUrl = null;
			$newObj->Pages[$p]->Files[1]->EditionId = '';

			$inputPath = dirname(__FILE__).'/testdata/largeSpeedTestData/page_preview.jpg';
			$newObj->Pages[$p]->Files[1]->Content = null;
			$newObj->Pages[$p]->Files[1]->FilePath = $inputPath;
			$this->uploadFile( $newObj->Pages[$p]->Files[1] );

			$newObj->Pages[$p]->Edition = null;
			$newObj->Pages[$p]->Master = 'Master';
			$newObj->Pages[$p]->Instance = 'Production';
			$newObj->Pages[$p]->PageSequence = 1;
			$newObj->Pages[$p]->Renditions = null;
			$newObj->Pages[$p]->Orientation = null;
		}

		// Native layout file			
		$newObj->Files = array();
		$newObj->Files[0] = new Attachment();
		$newObj->Files[0]->Rendition = 'native';
		$newObj->Files[0]->Type = 'application/indesign';
		$newObj->Files[0]->FileUrl = null;
		$newObj->Files[0]->EditionId = '';
		
		$inputPath = dirname(__FILE__).'/testdata/largeSpeedTestData/layout_native.indd';
		$newObj->Files[0]->Content = null;
		$newObj->Files[0]->FilePath = $inputPath;
		$this->uploadFile( $newObj->Files[0] );

		// Thumbnail file of layout (take first page)
		$newObj->Files[1] = new Attachment();
		$newObj->Files[1]->Rendition = 'thumb';
		$newObj->Files[1]->Type = 'image/jpeg';
		$newObj->Files[1]->FileUrl = null;
		$newObj->Files[1]->EditionId = '';
		
		$inputPath = dirname(__FILE__).'/testdata/largeSpeedTestData/page_thumb.jpg';
		$newObj->Files[1]->Content = null;
		$newObj->Files[1]->FilePath = $inputPath;
		$this->uploadFile( $newObj->Files[1] );

		// Preview file of layout (take first page)
		$newObj->Files[2] = new Attachment();
		$newObj->Files[2]->Rendition = 'preview';
		$newObj->Files[2]->Type = 'image/jpeg';
		$newObj->Files[2]->FileUrl = null;
		$newObj->Files[2]->EditionId = '';
		
		$inputPath = dirname(__FILE__).'/testdata/largeSpeedTestData/page_preview.jpg';
		$newObj->Files[2]->Content = null;
		$newObj->Files[2]->FilePath = $inputPath;
		$this->uploadFile( $newObj->Files[2] );

		$newObj->Messages = null;
		$newObj->Elements = null;
		$objTarget = $this->buildObjectTarget();
		$newObj->Targets = array( $objTarget );
		$newObj->Renditions = null;
		$newObj->MessageList = null;	
		
		return $newObj;
	}
	
	/**
	 * Builds workflow Object for an article.
	 */
	private function buildArticleObject( $articleId, $articleName )
	{
		$this->uploadedFileSize = 0;
		if( $this->testDataSize == 'small' ) {
			$newObj = $this->buildSmallArticleObject( $articleId, $articleName );
		
		} elseif( $this->testDataSize == 'large' ) {
			$newObj = $this->buildBigArticleObject( $articleId, $articleName );
		}
		
		if( $this->uploadedFileSize > 0 ) {
			$this->speedTestReport->addComment( 'Uploaded file size:'.
				NumberUtils::getByteString( $this->uploadedFileSize ) );
		}
		return $newObj;	 	
	}

	/**
	 * Build article object: This will be called when 
	 * $this->testDataSize is set to small.
	 */
	private function buildSmallArticleObject( $articleId, $articleName )
	{
		$this->content = 'To temos aut explabo. Ipsunte plat. Em accae eatur? Ihiliqui oditatem. Ro ipicid '.
			'quiam ex et quis consequae occae nihictur? Giantia sim alic te volum harum, audionseque '.
			'rem vite nobitas perrum faccuptias sunt fugit eliquatint velit a aut milicia consecum '.
			'veribus auda ides ut quia commosa quam et moles iscil mo conseque magnim quis ex ex eaquamet '.
			'ut adi dolor mo odis magnihi ligendit ut lam reperibusam quatumquam labor renis pe con eos '.
			'magnima gnatiur sitaepeles quatia namus ni aut adit at ad quundem laudia qui ut ratempe '.
			'rnatestorro te por alis acidunt volore nobit harciminum re eatus repudiatem ame prati bere '.
			'cus minveliquis serum, ute velecus cipiciur, occum nulpario quat fugitatur, nihillu ptatqui '.
			'ventibus doluptatur? Dus alique nonectoribus inciend elenim di sunt que mollis autempo ribus. '.
			'Totatent peliam aut facipsuntur aut pra quam es rem abo.';
		$fileSize = strlen($this->content);

		$attachment = new Attachment();
		$attachment->Content = $this->content;
		$attachment->Rendition = 'native';
		$attachment->Type = 'text/plain';
		$this->uploadFile( $attachment );
		$files = array( $attachment );

		$meta = $this->buildArticleMetaData( $articleId, $articleName, $fileSize );
		$objTarget = $this->buildObjectTarget();

		$newObj            = new Object();
		$newObj->MetaData  = $meta;
		$newObj->Relations = array();
		$newObj->Pages     = array();
		$newObj->Files     = $files;
		$newObj->Targets   = array( $objTarget );
		return $newObj;
	}

	/**
	 * Build article object: This will be called when 
	 * $this->testDataSize is set to large.
	 */
	private function buildBigArticleObject( $articleId, $articleName )
	{
		$newObj = new Object();
		$newObj->MetaData = new MetaData();
		$newObj->MetaData->BasicMetaData = new BasicMetaData();
		$newObj->MetaData->BasicMetaData->ID = $articleId;
		$newObj->MetaData->BasicMetaData->DocumentID = null;
		$newObj->MetaData->BasicMetaData->Name = $articleName;
		$newObj->MetaData->BasicMetaData->Type = 'Article';
		$newObj->MetaData->BasicMetaData->Publication = new Publication();
		$newObj->MetaData->BasicMetaData->Publication->Id = $this->publicationInfo->Id;
		$newObj->MetaData->BasicMetaData->Publication->Name = $this->publicationInfo->Name;
		$newObj->MetaData->BasicMetaData->Category = new Category();
		$newObj->MetaData->BasicMetaData->Category->Id = $this->categoryInfo->Id;
		$newObj->MetaData->BasicMetaData->Category->Name = $this->categoryInfo->Name;
		$newObj->MetaData->BasicMetaData->ContentSource = null;
		$newObj->MetaData->RightsMetaData = new RightsMetaData();
		$newObj->MetaData->RightsMetaData->CopyrightMarked = false;
		$newObj->MetaData->RightsMetaData->Copyright = null;
		$newObj->MetaData->RightsMetaData->CopyrightURL = null;
		$newObj->MetaData->SourceMetaData = new SourceMetaData();
		$newObj->MetaData->SourceMetaData->Credit = null;
		$newObj->MetaData->SourceMetaData->Source = null;
		$newObj->MetaData->SourceMetaData->Author = $this->user;
		$newObj->MetaData->ContentMetaData = new ContentMetaData();
		$newObj->MetaData->ContentMetaData->Description = null;
		$newObj->MetaData->ContentMetaData->DescriptionAuthor = null;
		$newObj->MetaData->ContentMetaData->Keywords = array( 'Key', 'Word' );
		$newObj->MetaData->ContentMetaData->Slugline = null;
		$newObj->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$newObj->MetaData->ContentMetaData->Columns = null;
		$newObj->MetaData->ContentMetaData->Width = null;
		$newObj->MetaData->ContentMetaData->Height = null;
		$newObj->MetaData->ContentMetaData->Dpi = null;
		$newObj->MetaData->ContentMetaData->LengthWords = null;
		$newObj->MetaData->ContentMetaData->LengthChars = null;
		$newObj->MetaData->ContentMetaData->LengthParas = null;
		$newObj->MetaData->ContentMetaData->LengthLines = null;
		$newObj->MetaData->ContentMetaData->PlainContent = null;
		$newObj->MetaData->ContentMetaData->FileSize = 11758316;
		$newObj->MetaData->ContentMetaData->ColorSpace = null;
		$newObj->MetaData->ContentMetaData->HighResFile = null;
		$newObj->MetaData->ContentMetaData->Encoding = null;
		$newObj->MetaData->ContentMetaData->Compression = null;
		$newObj->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$newObj->MetaData->ContentMetaData->Channels = null;
		$newObj->MetaData->ContentMetaData->AspectRatio = null;
		$newObj->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$newObj->MetaData->WorkflowMetaData->Deadline = date('Y-m-d\TH:i:s'); 
		$newObj->MetaData->WorkflowMetaData->Urgency = 'Top';
		$newObj->MetaData->WorkflowMetaData->Modifier = null;
		$newObj->MetaData->WorkflowMetaData->Modified = null;
		$newObj->MetaData->WorkflowMetaData->Creator = null;
		$newObj->MetaData->WorkflowMetaData->Created = null;
		$newObj->MetaData->WorkflowMetaData->Comment = null;
		$newObj->MetaData->WorkflowMetaData->State = new State();
		$newObj->MetaData->WorkflowMetaData->State->Id = $this->articleStatusInfo->Id;
		$newObj->MetaData->WorkflowMetaData->State->Name = $this->articleStatusInfo->Name;
		$newObj->MetaData->WorkflowMetaData->State->Type = 'Article';
		$newObj->MetaData->WorkflowMetaData->State->Produce = null;
		$newObj->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$newObj->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$newObj->MetaData->WorkflowMetaData->RouteTo = '';
		$newObj->MetaData->WorkflowMetaData->LockedBy = null;
		$newObj->MetaData->WorkflowMetaData->Version = null;
		$newObj->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$newObj->MetaData->WorkflowMetaData->Rating = null;
		$newObj->MetaData->WorkflowMetaData->Deletor = null;
		$newObj->MetaData->WorkflowMetaData->Deleted = null;
		$newObj->MetaData->ExtraMetaData = array();
		$newObj->Relations = array();
		$newObj->Pages = null;
		
		$newObj->Files = array();
		$newObj->Files[0] = new Attachment();
		$newObj->Files[0]->Rendition = 'native';
		$newObj->Files[0]->Type = 'application/incopyicml';
		$newObj->Files[0]->FileUrl = null;
		$newObj->Files[0]->EditionId = null;

		$inputPath = dirname(__FILE__).'/testdata/largeSpeedTestData/article_native.wcml';
		$newObj->Files[0]->Content = null;
		$newObj->Files[0]->FilePath = $inputPath;
		$this->uploadFile( $newObj->Files[0] );

		$newObj->Messages = null;
		$newObj->Elements = array();	
		$objTarget = $this->buildObjectTarget();
		$newObj->Targets = array( $objTarget );
		$newObj->Renditions = null;
		$newObj->MessageList = null;
		
		

		return $newObj;
	}
	
	/**
	 * Build image object.
	 */
	private function buildImageObject( $imageId, $imageName )
	{
		$this->uploadedFileSize = 0;
		$newObj = new Object();
		$newObj->MetaData = new MetaData();
		$newObj->MetaData->BasicMetaData = new BasicMetaData();
		$newObj->MetaData->BasicMetaData->ID = $imageId;
		$newObj->MetaData->BasicMetaData->DocumentID = null;
		$newObj->MetaData->BasicMetaData->Name = $imageName;
		$newObj->MetaData->BasicMetaData->Type = 'Image';
		$newObj->MetaData->BasicMetaData->Publication = new Publication();
		$newObj->MetaData->BasicMetaData->Publication->Id = $this->publicationInfo->Id;
		$newObj->MetaData->BasicMetaData->Publication->Name = $this->publicationInfo->Name;
		$newObj->MetaData->BasicMetaData->Category = new Category();
		$newObj->MetaData->BasicMetaData->Category->Id = $this->categoryInfo->Id;
		$newObj->MetaData->BasicMetaData->Category->Name = $this->categoryInfo->Name;
		$newObj->MetaData->BasicMetaData->ContentSource = null;
		$newObj->MetaData->RightsMetaData = new RightsMetaData();
		$newObj->MetaData->RightsMetaData->CopyrightMarked = false;
		$newObj->MetaData->RightsMetaData->Copyright = null;
		$newObj->MetaData->RightsMetaData->CopyrightURL = null;
		$newObj->MetaData->SourceMetaData = new SourceMetaData();
		$newObj->MetaData->SourceMetaData->Credit = null;
		$newObj->MetaData->SourceMetaData->Source = null;
		$newObj->MetaData->SourceMetaData->Author = null;
		$newObj->MetaData->ContentMetaData = new ContentMetaData();
		$newObj->MetaData->ContentMetaData->Description = null;
		$newObj->MetaData->ContentMetaData->DescriptionAuthor = null;
		$newObj->MetaData->ContentMetaData->Keywords = array( 'Key', 'word' );
		$newObj->MetaData->ContentMetaData->Slugline = null;
		$newObj->MetaData->ContentMetaData->Format = 'image/jpeg';
		$newObj->MetaData->ContentMetaData->Columns = null;
		$newObj->MetaData->ContentMetaData->Width = null;
		$newObj->MetaData->ContentMetaData->Height = null;
		$newObj->MetaData->ContentMetaData->Dpi = null;
		$newObj->MetaData->ContentMetaData->LengthWords = null;
		$newObj->MetaData->ContentMetaData->LengthChars = null;
		$newObj->MetaData->ContentMetaData->LengthParas = null;
		$newObj->MetaData->ContentMetaData->LengthLines = null;
		$newObj->MetaData->ContentMetaData->PlainContent = null;
		if( $this->testDataSize == 'small' ) {
			$newObj->MetaData->ContentMetaData->FileSize = 47423;
		} else if( $this->testDataSize == 'large' ) {
			$newObj->MetaData->ContentMetaData->FileSize = 46948966;
		}
		$newObj->MetaData->ContentMetaData->ColorSpace = null;
		$newObj->MetaData->ContentMetaData->HighResFile = null;
		$newObj->MetaData->ContentMetaData->Encoding = null;
		$newObj->MetaData->ContentMetaData->Compression = null;
		$newObj->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$newObj->MetaData->ContentMetaData->Channels = null;
		$newObj->MetaData->ContentMetaData->AspectRatio = null;
		$newObj->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$newObj->MetaData->WorkflowMetaData->Deadline = null;
		$newObj->MetaData->WorkflowMetaData->Urgency = 'Top';
		$newObj->MetaData->WorkflowMetaData->Modifier = null;
		$newObj->MetaData->WorkflowMetaData->Modified = null;
		$newObj->MetaData->WorkflowMetaData->Creator = null;
		$newObj->MetaData->WorkflowMetaData->Created = null;
		$newObj->MetaData->WorkflowMetaData->Comment = null;
		$newObj->MetaData->WorkflowMetaData->State = new State();
		$newObj->MetaData->WorkflowMetaData->State->Id = $this->imageStatusInfo->Id;
		$newObj->MetaData->WorkflowMetaData->State->Name = $this->imageStatusInfo->Name;
		$newObj->MetaData->WorkflowMetaData->State->Type = 'Image';
		$newObj->MetaData->WorkflowMetaData->State->Produce = null;
		$newObj->MetaData->WorkflowMetaData->State->Color = 'FFFF00';
		$newObj->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$newObj->MetaData->WorkflowMetaData->RouteTo = null;
		$newObj->MetaData->WorkflowMetaData->LockedBy = null;
		$newObj->MetaData->WorkflowMetaData->Version = null;
		$newObj->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$newObj->MetaData->WorkflowMetaData->Rating = null;
		$newObj->MetaData->WorkflowMetaData->Deletor = null;
		$newObj->MetaData->WorkflowMetaData->Deleted = null;
		$newObj->MetaData->ExtraMetaData = array();
		$newObj->Relations = array();
		$newObj->Pages = null;
		
		$newObj->Files = array();
		$newObj->Files[0] = new Attachment();
		$newObj->Files[0]->Rendition = 'native';
		$newObj->Files[0]->Type = 'image/jpeg';
		$newObj->Files[0]->FileUrl = null;
		$newObj->Files[0]->EditionId = null;	
		if( $this->testDataSize == 'small' ) {
			$inputPath = dirname(__FILE__).'/testdata/preview1page1.jpg';
		} else if( $this->testDataSize == 'large' ) {
			$inputPath = dirname(__FILE__).'/testdata/largeSpeedTestData/image_native.jpg';
		}
		$newObj->Files[0]->Content = null;
		$newObj->Files[0]->FilePath = $inputPath;
		$this->uploadFile( $newObj->Files[0] );
				
		$newObj->Messages = null;
		$newObj->Elements = array();
		$objTarget = $this->buildObjectTarget();
		$newObj->Targets = array( $objTarget );
		$newObj->Renditions = null;
		$newObj->MessageList = null;
		
		if( $this->uploadedFileSize > 0 ) {
			$this->speedTestReport->addComment( 'Uploaded file size:'.
				NumberUtils::getByteString( $this->uploadedFileSize ) );
		}		
		return $newObj;
	}


	/**
	 * Builds object target.
	 *
	 * @return Target $target Target consists of PubChannel and Issue only.
	 */
	private function buildObjectTarget()
	{
		$pubChannel = new PubChannel();
		$pubChannel->Id = $this->publicationInfo->Id;
		$pubChannel->Name = $this->publicationInfo->Name;
	
		$issue = new Issue();
		$issue->Id = $this->issueInfo->Id;
		$issue->Name = $this->issueInfo->Name;
		
		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue = $issue;
		
		return $target;
	}	

	/**
	 * Builds workflow MetaData for a layout.
	 */
	private function buildLayoutMetaData( $layoutId, $layoutName, $fileSize )
	{
		// infos
		$publ = new Publication( $this->publicationInfo->Id, $this->publicationInfo->Name );
		$category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );

		// build metadata
		$basMD = new BasicMetaData();
		$basMD->ID = $layoutId;
		$basMD->Name = $layoutName;
		$basMD->Type = 'Layout';
		$basMD->Publication = $publ;
		$basMD->Category = $category;
		
		$srcMD = new SourceMetaData();
		$srcMD->Author = $this->user;
		$rigMD = new RightsMetaData();
		$rigMD->Copyright = 'copyright';
		$cntMD = new ContentMetaData();
		$cntMD->Keywords = array("Key", "word");	
		$cntMD->Format = 'application/indesign';
		$cntMD->FileSize = $fileSize;
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline = date('Y-m-d\TH:i:s'); 
		$wflMD->Urgency = 'Top';
		$wflMD->State = new State( $this->layoutStatusInfo->Id, $this->layoutStatusInfo->Name );
		$wflMD->RouteTo = $this->user;
		$wflMD->Comment = 'CREATE OBJECT';
		$extMD = new ExtraMetaData(); 

		$md = new MetaData();
		$md->BasicMetaData    = $basMD;
		$md->RightsMetaData   = $rigMD;
		$md->SourceMetaData   = $srcMD;
		$md->ContentMetaData  = $cntMD;
		$md->WorkflowMetaData = $wflMD;
		$md->ExtraMetaData    = array( $extMD );		
		return $md;
	}

	/**
	 * Builds workflow MetaData for an article.
	 */
	private function buildArticleMetaData( $articleId, $articleName, $fileSize )
	{
		// infos
		$publ = new Publication( $this->publicationInfo->Id, $this->publicationInfo->Name );
		$category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );

		// build metadata
		$basMD = new BasicMetaData();
		$basMD->ID = $articleId;
		$basMD->Name = $articleName;
		$basMD->Type = 'Article';
		$basMD->Publication = $publ;
		$basMD->Category = $category;
		
		$srcMD = new SourceMetaData();
		$srcMD->Author = $this->user;
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
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline = date('Y-m-d\TH:i:s'); 
		$wflMD->Urgency = 'Top';
		$wflMD->State = new State( $this->articleStatusInfo->Id, $this->articleStatusInfo->Name );
		$wflMD->RouteTo = $this->user;
		$wflMD->Comment = 'CREATE OBJECT';
		$extMD = new ExtraMetaData(); 

		$md = new MetaData();
		$md->BasicMetaData    = $basMD;
		$md->RightsMetaData   = $rigMD;
		$md->SourceMetaData   = $srcMD;
		$md->ContentMetaData  = $cntMD;
		$md->WorkflowMetaData = $wflMD;
		$md->ExtraMetaData    = array( $extMD );		
		return $md;
	}

	/**
	 * Builds workflow MetaData for a layout.
	 */
	private function buildDossierMetaData( $dossierId, $dossierName )
	{
		// infos
		$publ = new Publication( $this->publicationInfo->Id, $this->publicationInfo->Name );
		$category = new Category( $this->categoryInfo->Id, $this->categoryInfo->Name );

		// build metadata
		$basMD = new BasicMetaData();
		$basMD->ID = $dossierId;
		$basMD->Name = $dossierName;
		$basMD->Type = 'Dossier';
		$basMD->Publication = $publ;
		$basMD->Category = $category;

		$srcMD = new SourceMetaData();
		$srcMD->Author = $this->user;
		$rigMD = new RightsMetaData();
		$rigMD->Copyright = 'copyright';
		$cntMD = new ContentMetaData();
		$wflMD = new WorkflowMetaData();
		$wflMD->Deadline = date('Y-m-d\TH:i:s');
		$wflMD->Urgency = 'Top';
		$wflMD->State = new State( $this->dossierStatusInfo->Id, $this->dossierStatusInfo->Name );
		$wflMD->RouteTo = $this->user;
		$wflMD->Comment = 'CREATE OBJECT';
		$extMD = new ExtraMetaData();

		$md = new MetaData();
		$md->BasicMetaData    = $basMD;
		$md->RightsMetaData   = $rigMD;
		$md->SourceMetaData   = $srcMD;
		$md->ContentMetaData  = $cntMD;
		$md->WorkflowMetaData = $wflMD;
		$md->ExtraMetaData    = array( $extMD );
		return $md;
	}

	/**
	 * Builds workflow Object for an article.
	 */
	private function buildDossierObject( $dossierId, $dossierName )
	{
		$meta = $this->buildDossierMetaData( $dossierId, $dossierName );
		$objTarget = $this->buildObjectTarget();

		$newObj            = new Object();
		$newObj->MetaData  = $meta;
		$newObj->Relations = array();
		$newObj->Pages     = array();
		$newObj->Targets   = array( $objTarget );
		return $newObj;
	}
	
	/**
	 * Does an Enterprise server SOAP request to run a specific service
	 * and measures start/stop times and durations.
	 *
	 * @param string $service The workflow service to run.
	 * @param array $parameters The list of parameters for the service.
	 * @return object The SOAP response object of PEAR's SOAP client class.
	 */
	private function callSoapService( $service, $req )
	{
		$this->speedTestReport->startWebService( $service );
		$errMsg = null;
		try { 
			$resp = $this->soapClient->$service( $req ); 
		} catch( SoapFault $e ) { 
			$errMsg = $e->getMessage(); 
			$resp = null;
		} catch( BizException $e ) { 
			$errMsg = $e->getMessage(); 
			$resp = null;
		}
		if( isset($resp->Reports) && !empty( $resp->Reports ) ) { // Introduced since v8.0 but not all services support this
			$errMsg = '';
			foreach( $resp->Reports as $report ) {
				foreach( $report->Entries as $reportEntry ) {
					$errMsg .= $reportEntry->Message . PHP_EOL;
				}
			}
			$resp = null;
		}
		$this->speedTestReport->stopWebService();

		if( !$resp && $errMsg ) {
			$this->speedTestReport->reportErrorAndExit( $errMsg );
		}	
		return $resp;
	}
	
	/**
	 * Requests the Transfer Server to -download- files.
	 * This is done -after- web services call.
	 * For DIME this is done in memory, so no action is taken.
	 * In both cases, the duration is reported.
	 */
	private function downloadFile( Attachment $attachment )
	{
		$this->speedTestReport->startFileTransfer(); // Start timing for fileTransfer
		if ( $this->attachmentHandling == DIME ) {
			// TODO: should be comparable with file tranfer solution;
			//       => either stream to temp file, or let file transfer work in memory
			
		} else {
			if( $this->attachmentHandling == TRANSFERSERVERHTTP ) {
				require_once BASEDIR.'/server/utils/TransferClient.class.php';
				$transferClient = new WW_Utils_TransferClient( $this->ticket );
			} else { // TRANSFERFOLDERHSCP
				require_once BASEDIR.'/server/utils/ShellTransferClient.class.php';
				$transferClient = new WW_Utils_ShellTransferClient( 'hscp' );
			}
			// Just download and clean up.
			$result = $transferClient->downloadFile( $attachment );
			if( $result ) {
				$this->downloadedFileSize += strlen( $attachment->Content );
			} else {
				$this->speedTestReport->reportErrorAndExit( 'Failed downloading file "'.$attachment->FilePath.'".' );
			}
		}
		$this->speedTestReport->stopFileTransfer(); // Stop timing for fileTransfer
	}
	
	/**
	 * Requests the Transfer Server to -upload- files.
	 * This is done -before- web services call.
	 * For DIME this is done in memory, so no action is taken.
	 * In both cases, the duration is reported.
	 */
	private function uploadFile( Attachment $attachment )
	{
		$this->speedTestReport->startFileTransfer(); // Start timing for fileTransfer
			
		if ( $this->attachmentHandling == DIME ) {
			if( $attachment->FilePath ) {
				$attachment->Content = new SOAP_Attachment( 'Content', 'application/octet-stream', $attachment->FilePath );
				$attachment->FilePath = null;
			} elseif( $attachment->Content ) {
				$attachment->Content = new SOAP_Attachment( 'Content', 'application/octet-stream', null, $attachment->Content );
				$attachment->FilePath = null;
			} else {
				$this->speedTestReport->reportErrorAndExit( "Failed uploading file (rendition={$attachment->Rendition}, format={$attachment->Type}." );
			}
		} else {
			// To keep track of fileSize.
			if( $attachment->FilePath ) {
				$uploadedFileSize = filesize( $attachment->FilePath );
			} else if( $attachment->Content ) {
				$uploadedFileSize = strlen( $attachment->Content );			
			}
			
			if( $this->attachmentHandling == TRANSFERSERVERHTTP ) {
				require_once BASEDIR.'/server/utils/TransferClient.class.php';
				$transferClient = new WW_Utils_TransferClient( $this->ticket );
			} else { // TRANSFERFOLDERHSCP
				require_once BASEDIR.'/server/utils/ShellTransferClient.class.php';
				$transferClient = new WW_Utils_ShellTransferClient( 'hscp' );
			}
			if( !$transferClient->uploadFile( $attachment ) ) {
				$this->speedTestReport->reportErrorAndExit( 'Failed uploading file "'.$attachment->FilePath.'".' );
			} else { // Successfuly uploaded.
				$this->uploadedFileSize += $uploadedFileSize;
			}
		}
		$this->speedTestReport->stopFileTransfer(); // Stop timing for fileTransfer
	}

	/**
	 * Checks the LogOn response and takes out all member attributes of this class.
	 * When any data could not be found, it logs error to screen and dies!
	 * Catched data is logged to screen on success.
	 */
	private function parseLogOnResponse( $logonResp )
	{
		$serverInfo = $logonResp->ServerInfo;
		$this->speedTestReport->addComment( "Connected to: \"$serverInfo->Name\" version: \"$serverInfo->Version\"" );
		
		$this->pubTerm = 'Brand';
		$this->categoryTerm = 'Category';
		
		// Get the ticket from LogOn reponse
		$this->ticket = trim($logonResp->Ticket);
		if( empty($this->ticket) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find a ticket.' );
		}
		$this->speedTestReport->addComment( "Retrieved ticket: \"$this->ticket\"" );

		// Find a publication that has a section, an issue and a layout status configured.
		// TESTSUITE options are not set or incomplete so use the log on response.
		if ( is_null( $this->publicationInfo) ) {
			$this->publicationInfo = $this->findPublicationForLayout( $logonResp->Publications );
		}
		$this->validatePublicationForLayout();

		// Find a Named Query
		$this->namedQueries = $logonResp->NamedQueries;
		$this->namedQuery = null;
		if( count( $logonResp->NamedQueries ) > 0 ) {
			$this->namedQuery = $logonResp->NamedQueries[0]->Name;
		}
		if( is_null($this->namedQuery) || empty($this->namedQuery) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find a Named Query.' );
		}
		$this->speedTestReport->addComment( "Found Named Query: \"$this->namedQuery\"" );
	}

	/**
	 * Finds a publication that has a section, an issue and layout+article+dossier statuses configured.
	 * Alternatively, find one that has an overrule issue with a section and a layout+article statuses configured.
	 * If an issueId is passed then skip the other issues of the publication. Else, just pick an issue.
	 */
	private function findPublicationForLayout( array $publicationInfos, $issueId = null )
	{
		$publicationInfo = null;
		foreach( $publicationInfos as $pub ) {
			// Determine issue
			$this->issueInfo = null;
			foreach( $pub->PubChannels as $pubChannel ) {
				foreach( $pubChannel->Issues as $issue ) {
					if ( !is_null( $issueId )) {
						// Use the specified issue
						if ( $issue->Id !== $issueId) {
							continue;
						}
					}
					if( $issue->OverrulePublication == 'true' ) { // overruled publication:
			
						// Determine an issue's section
						$this->categoryInfo = count( $issue->Sections ) > 0  ? $issue->Sections[0] : null;
						
						// Determine layout status
						$this->layoutStatusInfo = null;
						foreach( $issue->States as $status ) {
							if( $status->Type == 'Layout' ) {
								$this->layoutStatusInfo = $status;
								if( $status->Id != -1 ) { // prefer non-personal status
									break;
								}
							}
						}
						// Determine article status
						$this->articleStatusInfo = null;
						foreach( $issue->States as $status ) {
							if( $status->Type == 'Article' ) {
								$this->articleStatusInfo = $status;
								if( $status->Id != -1 ) { // prefer non-personal status
									break;
								}
							}
						}
						// Determine image status
						$this->imageStatusInfo = null;
						foreach( $issue->States as $status ) {
							if( $status->Type == 'Image' ) {
								$this->imageStatusInfo = $status;
								if( $status->Id != -1 ) { // prefer non-personal status
									break;
								}
							}
						}
						// Determine dossier status
						$this->dossierStatusInfo = null;
						foreach( $issue->States as $status ) {
							if( $status->Type == 'Dossier' ) {
								$this->dossierStatusInfo = $status;
								if( $status->Id != -1 ) { // prefer non-personal status
									break;
								}
							}
						}

						if( !is_null($this->categoryInfo) && 
							!is_null($this->layoutStatusInfo) &&
							!is_null($this->articleStatusInfo) &&
							!is_null($this->dossierStatusInfo) ) {
							$this->issueInfo = $issue;
							break;
						}
			
					} else { // non-overruled publication:
						$this->issueInfo = $issue;
						break;
					}
				}
			}
			if( !is_null($this->issueInfo) && $this->issueInfo->OverrulePublication != 'true' ) {
				// Determine a publication's section
				$this->categoryInfo = count( $pub->Categories ) > 0 ? $pub->Categories[0] : null;
		
				// Determine layout status
				$this->layoutStatusInfo = null;
				foreach( $pub->States as $status ) {
					if( $status->Type == 'Layout' ) {
						$this->layoutStatusInfo = $status;
						if( $status->Id != -1 ) { // prefer non-personal status
							break;
						}
					}
				}
				// Determine article status
				$this->articleStatusInfo = null;
				foreach( $pub->States as $status ) {
					if( $status->Type == 'Article' ) {
						$this->articleStatusInfo = $status;
						if( $status->Id != -1 ) { // prefer non-personal status
							break;
						}
					}
				}
				// Determine image status
				$this->imageStatusInfo = null;
				foreach( $pub->States as $status ) {
					if( $status->Type == 'Image' ) {
						$this->imageStatusInfo = $status;
						if( $status->Id != -1 ) { // prefer non-personal status
							break;
						}
					}
				}
				// Determine dossier status
				$this->dossierStatusInfo = null;
				foreach( $pub->States as $status ) {
					if( $status->Type == 'Dossier' ) {
						$this->dossierStatusInfo = $status;
						if( $status->Id != -1 ) { // prefer non-personal status
							break;
						}
					}
				}
				
			}
			// Quit search when all found in this publication
			if( !is_null($this->issueInfo) && !is_null($this->categoryInfo) && 
				!is_null($this->layoutStatusInfo) && !is_null($this->articleStatusInfo) &&
				!is_null($this->dossierStatusInfo) ) {
				$publicationInfo = $pub;
				break;
			}
		}
		
		return $publicationInfo;
	}

	/**
	 * Validates all data found by findPublicationForLayout() method.
	 * When any data was not be found, it logs error to screen and dies!
	 */
	private function validatePublicationForLayout()
	{
		if( is_null($this->publicationInfo) || empty($this->publicationInfo->Id) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find any $this->pubTerm that defines a layout Status.' );
		}
		$this->speedTestReport->addComment( "Found $this->pubTerm: \"{$this->publicationInfo->Name}\" (id={$this->publicationInfo->Id})" );
		
		if( is_null($this->issueInfo) || empty($this->issueInfo->Id) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find an issue in any $this->pubTerm that defines a layout Status.' );
		}
		$this->speedTestReport->addComment( "Found Issue: \"{$this->issueInfo->Name}\" (id={$this->issueInfo->Id})" );
		
		if( is_null($this->categoryInfo) || empty($this->categoryInfo->Id) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find a $this->categoryTerm in any $this->pubTerm that defines a layout Status.' );
		}
		$this->speedTestReport->addComment( "Found $this->categoryTerm: \"{$this->categoryInfo->Name}\" (id={$this->categoryInfo->Id})" );
		
		if( is_null($this->layoutStatusInfo) || empty($this->layoutStatusInfo->Id) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find a layout Status in any $this->pubTerm.' );
		}
		$this->speedTestReport->addComment( "Found layout Status: \"{$this->layoutStatusInfo->Name}\" (id={$this->layoutStatusInfo->Id})" );

		if( is_null($this->articleStatusInfo) || empty($this->articleStatusInfo->Id) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find an article Status in any $this->pubTerm.' );
		}
		$this->speedTestReport->addComment( "Found article Status: \"{$this->articleStatusInfo->Name}\" (id={$this->articleStatusInfo->Id})" );
	}

	/**
	 * Checks if the version of the speedtest is running in, is comparable with the version of
	 * the specific test. If the test runs in 7.0.0 version mode, only tests added in a version of
	 * 7.0.0 or lower must be excuted.
	 * So if $versionRH = '0.0.0' is passed than the test is always executed.
	 * @param string $versionRH Version to compare the test run version against.
	 * @return boolean true if the run mode version is equal or lower than the version of the test.
	 */
	public function checkVersion( $versionRH )
	{
		return ( version_compare( $this->version, $versionRH, '>=' ) );
	}	
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

/**
 * To record the duration taken for each service call.
 * Calculate the average when a test run is executed more than once.
 */
class SpeedTestReport
{
	// test case:
	private $comments;
	private $start;
	private $startMS;
	private $end;
	private $endMS;
	private $total;
	private $serviceName;

	// file transfer:
	private $fileTransferMS;
	private $fileTransferEndStart;
	private $fileTransferEndMS;

	// web service:
	private $webServiceMS;
	private $webServiceStartMS;
	private $webServiceEndMS;

	// test run (whole block of N test runs):
	private $totalTestRun; // integer (N test runs)
	private $currentRun; // integer
	private $serverDurations; // array or arrays
	private $serverRecording; // boolean
	
	/**
	 * The test application is about to run a test for which a new report must be built.
	 * @param int $totalTestRun The number of times the test run will be executed.
	 */
	public function startReport( $totalTestRun )
	{
		$this->currentRun = -1;
		$this->totalTestRun = $totalTestRun;
	}

	/**
	 * The test application has completed a test run for which a report must completed.
	 */
	public function stopReport()
	{
		if( $this->totalTestRun > 1 ) { // zero indexed: [0...n-1]
			print '<br/>Average server durations:';
			$this->showServerDurations();
		}
	}
	
	/**
	 * Starts the test run.
	 * Each test run will call the necessary web services and the duration
	 * taken for each service call is taken down.
	 */
	public function startTestRun()
	{
		$this->currentRun += 1;
		$this->serverDurations[$this->currentRun] = array();

		print '<br/>';
		if( $this->totalTestRun > 1 ) {
			$currentRun = $this->currentRun + 1;
			print "Test#{$currentRun}:";
		}
		$style = 'style="padding-left:10px;" valign="top"';
		print '<table border="0" style="font-size:11px;"><tr style="font-weight: bold;">'.
				"<td $style>Start</td><td $style>Stop</td><td $style>TransFile</td>".
				"<td $style>Service</td><td $style>Total</td><td $style>ServiceName";		
	}

	/**
	 * Stops the test run. Refer to startTestRun function header for more info.
	 */
	public function stopTestRun()
	{
		print '</table>' . "\r\n";
	}

	/**
	 * Display the duration taken for the web services test call.
	 * When the test is executed more than one time, the average is calculated.
	 */
	private function showServerDurations()
	{
		$style = 'style="padding-left:10px;" valign="top"';
		print '<table border="0" style="font-size:11px;">';
		// show header
		print '<tr style="font-weight: bold;">';
		for( $run = 0; $run <= $this->currentRun; $run++ ) {
			print "<td $style>Test#".($run+1)."</td>";
		}
		print "<td $style>Average</td>";
		print "<td $style>Service</td>";
		print "</tr>\r\n";
		// show body
		for( $row = 0; $row < count( $this->serverDurations[0] ); $row++ ) {
		
			$average = 0;
			print '<tr>';
			for( $run = 0; $run <= $this->currentRun; $run++ ) {
				$duration = $this->serverDurations[$run][$row]['total'];
				print "<td $style>".sprintf( "%0.3fs", $duration)."</td>";
				$average += $duration;
			}
			$average /= ($this->currentRun + 1);
			print "<td $style>".sprintf( "%0.3fs",$average)."</td>";
			print "<td $style>".$this->serverDurations[0][$row]['service'] . "</td>";
			print "</tr>\r\n";
		}
		print '</table>';
	}

	/**
	 * Enable / Disable recording the time taken for service calls.
	 */
	public function startServerDurations() { $this->serverRecording = true; }
	public function stopServerDurations()  { $this->serverRecording = false; }
	
	/**
	 * The test function calls this to indicate a web service and file transfer needs to be measured.
	 */
	public function startTestCase()
	{
		// test case:
		$this->start = date('H:i:s');
		$this->startMS = getMicrotime();
		$this->end = '';
		$this->endMS = 0;
		$this->total = 0;
		$this->comments = array();
		
		// file transfer:
		$this->fileTransferMS = 0;
		$this->fileTransferEndStart = 0;
		$this->fileTransferEndMS = 0;
		
		// web service
		$this->webServiceMS = 0;
		$this->webServiceStartMS = 0;
		$this->webServiceEndMS = 0;
	}

	/**
	 * The test function calls this to indicate a web service and file transfer are completed.
	 * It reports the duration times measured.
	 * @param Boolean $calculateAverage When set to True, it records down the total time to calculate Average later.
	 */
	public function stopTestCase( $calculateAverage=true )
	{
		$this->endMS = getMicrotime();
		$this->end = date('H:i:s');
		$totalMS = $this->endMS - $this->startMS;
		$total = sprintf( "%0.3fs", $totalMS );

		$webService = sprintf( "%0.3fs", $this->webServiceMS );
		$fileTransfer = sprintf( "%0.3fs", $this->fileTransferMS );
		
		$style = 'style="padding-left:10px;" valign="top"';
		print "<tr><td $style>{$this->start}</td><td $style>{$this->end}</td><td $style>{$fileTransfer}</td>".
			"<td $style>{$webService}</td><td $style>{$total}</td><td $style>{$this->serviceName}<br/>";
		foreach( $this->comments as $comment ) {
			print '<font color="gray">' . /*str_repeat(' ', 27 ) .*/ '- ' . $comment . '</font><br/>';
		}
		print '</td></tr>'."\r\n";

		if( $calculateAverage ) {		
			$this->serverDurations[$this->currentRun][] = array( 'total' => $total, 'service' => $this->serviceName );
		}	
	}

	/**
	 * Comments are collected here for each service call.
	 * @param string $comment Comment to be shown.
	 */
	public function addComment( $comment )
	{
		$this->comments[] = $comment;
	}

	/**
	 * Starts the recording of the web service call.
	 * This is only started when recording is enabled ($this->serverRecording).
	 * @param string $serviceName The service name of the web serivce going to be recorded.
	 */
	public function startWebService( $serviceName )
	{
		$this->serviceName = $serviceName;
		if( $this->serverRecording ) {
			$this->webServiceStartMS = getMicrotime();
		}
	}

	/**
	 * Stops the recording of the web service call.
	 * And also calculate the time taken for the web service call.
	 * When the same service call is needed to be called several times,
	 * then the total time taken is accumulated.
	 * For an example, NamedQuery, needs to be called several times
	 * for different query (but they are all under same web service name),
	 * so the total time taken for each service calls are accumulated.
	 */
	public function stopWebService()
	{
		if( $this->serverRecording ) {
			$this->webServiceEndMS = getMicrotime();
			$this->webServiceMS += ($this->webServiceEndMS - $this->webServiceStartMS);
		}
	}

	/**
	 * Starts the recording of the file transfering in transfer server.
	 */
	public function startFileTransfer()
	{
		$this->fileTransferStartMS = getMicrotime();
	}

	/**
	 * Stops the recording of the file transfering in transfer server.
	 */
	public function stopFileTransfer()
	{
		$this->fileTransferEndMS = getMicrotime();
		$this->fileTransferMS += ($this->fileTransferEndMS - $this->fileTransferStartMS);
	}
	
	/**
	 * Shows the collected report/results so far,
	 * print the Error message and abort the test.
	 * @param string $msg The error message that is going to be shown to the user before aborting the test.
	 */
	public function reportErrorAndExit( $msg )
	{
		$this->stopReport();
		print '<font color="red"><b>ERROR</b>: ' . $msg . '</font><br/>';
		die( 'Test aborted' );
	}
}