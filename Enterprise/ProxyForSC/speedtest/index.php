<?php
/**
 * Index entry point for the Speed Test.
 *
 * @package     ProxyForSC
 * @subpackage  Index
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/config.php';
require_once BASEDIR.'/utils/NumberUtils.class.php';

// Log start time at screen
$time_start = getMicrotime();
print '<html><body style="font-family: Arial;">';

$startTest = isset($_POST['starttest']) ? $_POST['starttest'] : '';
$testRuns = isset($_POST['runs']) ? intval($_POST['runs']) : 1;
$testDir = isset( $_POST['testDir'] ) ? $_POST['testDir'] : '';
$connection = isset($_POST['connection']) ? $_POST['connection'] : 'proxy';

$testDirCombobox = listSubDirectories( BASEDIR.'/speedtest/testdata/' );
if( !$testDir && count($testDirCombobox)>0 ) {
	$testDir = $testDirCombobox[0]; // pick the first folder when none selected
}

// Show form to let user choose test params
print '<h2>'.PRODUCT_NAME_FULL.' - Proxy for SC</h2>';
print '<form action="index.php" enctype="multipart/form-data" name="speedtestform" method="post" style="border-style:solid;border-color:#AAAAAA;border-width:thin;padding:5px;">';
print '&nbsp;&nbsp;&nbsp;Number of runs: '.inputvar( 'runs', $testRuns, 'combo', 
	array('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5' ), '' );
print '&nbsp;&nbsp;&nbsp;Connect to: '.inputvar( 'connection', $connection, 'combo', 
	array('proxy' => 'Proxy Server', 'enterprise' => 'Enterprise Server'), '' );
print '&nbsp;&nbsp;&nbsp;Test data: '.inputvar( 'testDir', $testDir, 'combo', $testDirCombobox, false );
print '&nbsp;&nbsp;&nbsp;<input type="submit" id="starttest" name="starttest" value="Start Test"/>';
print '</form>';

if( !$startTest ) {
	exit; // Let user choose parameters first. (Wait until the Start Test button it clicked.)
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

print '<table><tr><td valign="top">';
$expectedFiles = array( 'article.wcml' => true, 'layout.indd' => true, 'image.jpg' => true );
$testFiles = listDirectoryFiles( BASEDIR.'/speedtest/testdata/'.$testDir );
if( $testFiles ) {
	print 'Files to test with:<ul>';
	foreach( $testFiles as $fileName => $fileSize ) {
		if( isset($expectedFiles[$fileName]) ) {
			print '<li>'.$fileName.' ('.NumberUtils::getByteString($fileSize,1).')</li>';
			unset($expectedFiles[$fileName]);
		}
	}
	print '</ul>';
} 
if( $expectedFiles ) {
	$msg = 'In the selected in folder "'.BASEDIR.'/speedtest/testdata/'.$testDir.'", '.
			'the following files are missing: "'.implode('", "', array_keys($expectedFiles) ).'". '.
			'Please provide those files and try again.';
	print '<font color="red"><b>ERROR</b>: ' . $msg . '</font><br/>';
	exit;
}
print '</td><td valign="top">';
$proxyInfo = json_decode( file_get_contents( PROXYSERVER_URL.'proxyserver/apps/getserverinfo.php' ) );
print 'Software:<ul>';
print    '<li>'.PRODUCT_NAME_FULL.' v'.PRODUCT_VERSION.'</li>';
print    '<li>Proxy Server v'.$proxyInfo->proxyserver->version.'</li>';
print    '<li>Proxy Stub v'.$proxyInfo->proxystub->version.'</li>';
print '</ul>';
print '</td><td valign="top">';
print 'File transfer:<ul>';
print    '<li>Compression: '.($proxyInfo->proxyserver->file_compression?'yes':'no').'</li>';
print    '<li>Copy method: '.$proxyInfo->proxyserver->file_transfer.'</li>';
print    '<li>Use Proxy Stub: '.$proxyInfo->proxyserver->use_proxystub.'</li>';
print '</ul>';
print '</td></tr></table>';

if( PRODUCT_VERSION != $proxyInfo->proxyserver->version ) {
	$msg = 'The version of the Speed Test is not the same as the Proxy Server. '.
			'Please install the same version and try again.';
	print '<font color="red"><b>ERROR</b>: ' . $msg . '</font><br/>';
	exit;
}
if( PRODUCT_VERSION != $proxyInfo->proxystub->version ) {
	$msg = 'The version of the Speed Test is not the same as the Proxy Stub. '.
			'Please install the same version and try again.';
	print '<font color="red"><b>ERROR</b>: ' . $msg . '</font><br/>';
	exit;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

$speedTester = new SpeedTester();
$speedTester->setTestDataWeight( $testDir );
$speedTester->initializeClient( $connection );

print ( 'Performing speed test....<br/><br/>Started at: '.date('H:i:s').'<br/>' );

// Log client initialisation time at screen
$time_end = getMicrotime();
$time = $time_end - $time_start;
printf( 'Client init time: %0.3fs<br/>', $time );

// Perform the speedtest
$speedTester->startReport( $testRuns );
for( $i = 1; $i <= $testRuns; $i++ ) {
	set_time_limit(3600); // one hour per run
	$speedTester->startTestRun();
	
	$speedTester->startServerDurations();
	$speedTester->callGetServers();
	$speedTester->callLogOn();
	$speedTester->callCreateObjects();
	$speedTester->callGetObjects();
	$speedTester->callSaveObjects();
	$speedTester->callUnlockObjects();
	$speedTester->callDeleteObjects();
	$speedTester->callLogOff();
	$speedTester->stopServerDurations();
	
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

function formvar( $var )
{
	return htmlentities( $var, ENT_QUOTES, 'UTF-8' );
}

function inputvar( $name, $value, $type=null, $domain = null, $nullallowed = true, $title=null )
{
	switch ($type) {
		case 'checkbox':
			return '<input type="checkbox" title="'.$title.'" name="'.$name.'" '.(trim($value)?'checked="checked"':'').'/>';
			
		case 'combo':
			$combo = '<select name="'.$name.'">';
			if ($nullallowed) {
				$txt = '';
				if ($nullallowed !== true) $txt = "<".$nullallowed.">";
				$combo.= '<option value="">'.formvar($txt).'</option>';
			}
			if ($domain) foreach (array_keys($domain) as $key) {
				$combo .= '<option value="'.$key.'" '.($value==$key?'selected="selected"':'').'>'.formvar($domain[$key]).'</option>';
			}
			$combo .= '</select>';
			return $combo;
			
		case 'small':
			return '<input maxlength="8" size="5" name="'.$name.'" value="'.formvar($value).'"/>';

		case 'area':
			return '<textarea name="'.$name.'" rows="5" cols="30">'.formvar($value).'</textarea>';
			
		case 'shortname':
			return '<input maxlength="40" name="'.$name.'" value="'.formvar($value).'"/>';

		case 'hidden':
			return '<input type="hidden" name="'.$name.'" value="'.formvar($value).'"/>';
	}

	// default	
	return '<input maxlength="255" name="'.$name.'" value="'.formvar($value).'"/>';
}

function listSubDirectories( $parentDirPath )
{
	$parentDirHandle = opendir( $parentDirPath );
	$dirNames = array();
	while( false !== ( $dirName = readdir( $parentDirHandle ) ) ) {
		if( $dirName && $dirName[0] != '.' ) { // skip '.' and '..' and hidden files (prefixed with dot)
			if( is_dir( $parentDirPath . '/' . $dirName ) ) {
				$dirNames[] = $dirName;
			}
		}
	}
	closedir( $parentDirHandle );
	return $dirNames;
}

function listDirectoryFiles( $dirPath )
{
	$dirHandle = opendir( $dirPath );
	$files = array();
	while( false !== ( $fileName = readdir( $dirHandle ) ) ) {
		if( $fileName && $fileName[0] != '.' ) { // skip '.' and '..' and hidden files (prefixed with dot)
			if( is_file( $dirPath . '/' . $fileName ) ) {
				$files[$fileName] = filesize( $dirPath . '/' . $fileName );
			}
		}
	}
	closedir( $dirHandle );
	return $files;
}

/**
 * To execute web services or named queries service calls
 * to measure the time taken for all the service calls executed.
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
	private $speedTestReport;	

	// Layout test object details
	private $layoutId;
	private $layoutName;
	private $layoutVersion;
	private $layoutStatusInfo;
	private $layoutPath;

	// Article test object details
	private $articleId;
	private $articleName;
	private $articleStatusInfo;
	private $content;

	// Context where to create objects in.
	private $publicationInfo;
	private $pubChannelInfo;
	private $issueInfo;
	private $categoryInfo;

	public function __construct()
	{
		$this->speedTestReport = new SpeedTestReport();
		$this->user = SPEEDTEST_USERNAME;
		$this->password = SPEEDTEST_PASSWORD;
	}

	/**
	 * Set the test data whether it should involve
	 * small files* or large files*.
	 * files* = layout, article, image
	 */
	public function setTestDataWeight( $testDir )
	{
		$this->testDir = $testDir;
	}

	/**
	 * Initialize the client before the test run is executed.
	 */
	public function initializeClient( $connection )
	{
		require_once BASEDIR.'/speedtest/soap/WflClient.php';
		$options = array(
			'transfer' => 'DIME', 
			'protocol' => 'SOAP',
		);
		switch( $connection ) {
			case 'proxy':
				$options['location'] = PROXYSERVER_URL.'proxyserver/index.php';
				break;
			case 'enterprise':
				$options['location'] = ENTERPRISE_URL.'index.php';
				break;
			default:
				$this->speedTestReport->reportErrorAndExit( 'Unknown connection: '.$connection );
				break;
		}
		$this->soapClient = new WW_SOAP_WflClient( $options );		
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
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflGetServersRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflGetServersResponse.class.php';
		$req = new WflGetServersRequest();
		$this->callSoapService( 'GetServers', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * LogOn
	 */
	public function callLogOn( $calculateAverage=true )
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflLogOnRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflLogOnResponse.class.php';
		$req = new WflLogOnRequest( 
			// $User, $Password, $Ticket, $Server, $ClientName, $Domain,
			$this->user, $this->password, '', '', '', '',
			// $ClientAppName, $ClientAppVersion, $ClientAppSerial, $ClientAppProductKey, $RequestTicket
			PRODUCT_NAME_FULL, PRODUCT_VERSION, '', '', false );
		$resp = $this->callSoapService( 'LogOn', $req );
		$this->parseLogOnResponse( $resp );
		$this->speedTestReport->stopTestCase( $calculateAverage );
	}

	/**
	 * CreateObjects
	 */
	public function callCreateObjects()
	{
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflCreateObjectsRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflCreateObjectsResponse.class.php';

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
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflGetObjectsRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflGetObjectsResponse.class.php';
		$ids = array( $this->layoutId, $this->articleId, $this->imageId );
		foreach( $ids as $id ) {
			$this->speedTestReport->startTestCase();
			$req = new WflGetObjectsRequest( $this->ticket, array( $id ), true, 'native' );
			$resp = $this->callSoapService( 'GetObjects', $req );
			$objName = $resp->Objects[0]->MetaData->BasicMetaData->Name;
			$objType = $resp->Objects[0]->MetaData->BasicMetaData->Type;
			$this->speedTestReport->addComment( 'Get '. $objType .' object "'. $objName .'" (id="'.$id.')"' );

			$this->speedTestReport->stopTestCase();
		}
	}

	/**
	 * SaveObjects
	 */
	public function callSaveObjects()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflSaveObjectsRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflSaveObjectsResponse.class.php';
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
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflUnlockObjectsRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflUnlockObjectsResponse.class.php';
		$ids = array( $this->layoutId, $this->articleId, $this->imageId );
		$req = new WflUnlockObjectsRequest( $this->ticket, $ids );
		$this->callSoapService( 'UnlockObjects', $req );
		$this->speedTestReport->stopTestCase();
	}
	
	/**
	 * DeleteObjects. Permanently deletes (purges) the test objects from the Workflow area.
	 */
	public function callDeleteObjects()
	{
		$this->speedTestReport->startTestCase();
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflDeleteObjectsRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflDeleteObjectsResponse.class.php';
		$ids = array( $this->layoutId, $this->articleId, $this->imageId );
		$req = new WflDeleteObjectsRequest( $this->ticket, $ids, true, null, array('Workflow') );
		$this->callSoapService( 'DeleteObjects', $req );
		$this->speedTestReport->addComment( 'Purge objects, ids: '.implode(', ',$ids) );
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
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflLogOffRequest.class.php';
		require_once BASEDIR.'/speedtest/dataclasses/wfl/WflLogOffResponse.class.php';
		$settings = array(
			new Setting( 'Time', date('H:i:s') ),
			new Setting( 'Date', date('Y-M-D') )
		);
		$req = new WflLogOffRequest( $this->ticket, true, $settings );
		$this->callSoapService( 'LogOff', $req );
		$this->speedTestReport->stopTestCase();
	}

	/**
	 * Build layout object
	 */	
	private function buildLayoutObject( $layoutId, $layoutName )
	{
		$inputPath = BASEDIR.'/speedtest/testdata/'.$this->testDir.'/layout.indd';

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
		$newObj->MetaData->ContentMetaData->FileSize = filesize($inputPath);
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
		$newObj->Files[0]->Content = null;
		$newObj->Files[0]->FilePath = $inputPath;
		$this->attachFile( $newObj->Files[0] );
		
		$newObj->Messages = null;
		$newObj->Elements = null;
		$objTarget = $this->buildObjectTarget();
		$newObj->Targets = array( $objTarget );
		$newObj->Renditions = null;
		$newObj->MessageList = null;	
		
		return $newObj;
	}
	
	/**
	 * Build article object
	 */
	private function buildArticleObject( $articleId, $articleName )
	{
		$inputPath = BASEDIR.'/speedtest/testdata/'.$this->testDir.'/article.wcml';

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
		$newObj->MetaData->ContentMetaData->FileSize = filesize($inputPath);
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
		$newObj->Files[0]->Content = null;
		$newObj->Files[0]->FilePath = $inputPath;
		$this->attachFile( $newObj->Files[0] );

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
		$inputPath = BASEDIR.'/speedtest/testdata/'.$this->testDir.'/image.jpg';
		
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
		$newObj->MetaData->ContentMetaData->FileSize = filesize($inputPath);
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
		$newObj->Files[0]->Content = null;
		$newObj->Files[0]->FilePath = $inputPath;
		$this->attachFile( $newObj->Files[0] );
				
		$newObj->Messages = null;
		$newObj->Elements = array();
		$objTarget = $this->buildObjectTarget();
		$newObj->Targets = array( $objTarget );
		$newObj->Renditions = null;
		$newObj->MessageList = null;
		
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
	 * Does an Enterprise server SOAP request to run a specific service
	 * and measures start/stop times and durations.
	 *
	 * @param string $service The workflow service name to run.
	 * @param object $req The SOAP request object.
	 * @return object The SOAP response object.
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
		} catch( Exception $e ) { 
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
	 * Requests the Transfer Server to -upload- files.
	 * This is done -before- web services call.
	 * For DIME this is done in memory, so no action is taken.
	 * In both cases, the duration is reported.
	 */
	private function attachFile( Attachment $attachment )
	{
		if( $attachment->FilePath ) {
			$attachment->Content = new SOAP_Attachment( 'Content', 'application/octet-stream', $attachment->FilePath );
			$attachment->FilePath = null;
		} elseif( $attachment->Content ) {
			$attachment->Content = new SOAP_Attachment( 'Content', 'application/octet-stream', null, $attachment->Content );
			$attachment->FilePath = null;
		} else {
			$this->speedTestReport->reportErrorAndExit( "Failed uploading file (rendition={$attachment->Rendition}, format={$attachment->Type}." );
		}
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
		
		// Get the ticket from LogOn reponse
		$this->ticket = trim($logonResp->Ticket);
		if( empty($this->ticket) ) {
			$this->speedTestReport->reportErrorAndExit( 'Could not find a ticket.' );
		}
		$this->speedTestReport->addComment( "Retrieved ticket: \"$this->ticket\"" );

		// Lookup the configured Brand in the logon response.
		$this->publicationInfo = null;
		foreach( $logonResp->Publications as $publicationInfo ) {
			if( $publicationInfo->Name == SPEEDTEST_BRANDNAME ) {
				$this->publicationInfo = $publicationInfo;
				break;
			}
		}
		if( !$this->publicationInfo ) {
			$this->speedTestReport->reportErrorAndExit( 
				'Could not find the Brand specified by the SPEEDTEST_BRANDNAME option in the '.
				'config.php file. Please check if the Brand exists. And, make sure that the '.
				'user specified by the SPEEDTEST_USERNAME option has access rights to the brand. ' );
		}
		$this->speedTestReport->addComment( "Found Brand: \"{$this->publicationInfo->Name}\" (id={$this->publicationInfo->Id})" );
		
		// Lookup the configured PubChannel in the logon response.
		$this->pubChannelInfo = null;
		foreach( $this->publicationInfo->PubChannels as $pubChannel ) {
			if( $pubChannel->Name == SPEEDTEST_PUBCHANNELNAME ) {
				$this->pubChannelInfo = $pubChannel;
				break;
			}
		}
		if( !$this->pubChannelInfo ) {
			$this->speedTestReport->reportErrorAndExit( 
				'Could not find the PubChannel specified by the SPEEDTEST_PUBCHANNELNAME option in the '.
				'config.php file. Please check if the Publication Channel exists and is configured under '.
				'the Brand specified by the SPEEDTEST_BRANDNAME option. ' );
		}
		$this->speedTestReport->addComment( "Found PubChannel: \"{$this->pubChannelInfo->Name}\" (id={$this->pubChannelInfo->Id})" );

		// Lookup the configured Issue in the logon response.
		$this->issueInfo = null;
		foreach( $this->pubChannelInfo->Issues as $issue ) {
			if( $issue->Name == SPEEDTEST_ISSUENAME ) {
				$this->issueInfo = $issue;
				break;
			}
		}
		if( !$this->issueInfo ) {
			$this->speedTestReport->reportErrorAndExit( 
				'Could not find the Issue specified by the SPEEDTEST_ISSUENAME option in the '.
				'config.php file. Please check if the Issue exists and is configured under '.
				'the Publication Channel specified by the SPEEDTEST_PUBCHANNELNAME option. ' );
		}
		if( $this->issueInfo->OverrulePublication ) {
			$this->speedTestReport->reportErrorAndExit( 
				'The Issue specified by the SPEEDTEST_ISSUENAME option in the config.php file '.
				'has the Overrule Brand option enabled. Please disable that option or configure '.
				'a different Issue (that has the option disabled). ' );
		}
		$this->speedTestReport->addComment( "Found Issue: \"{$this->issueInfo->Name}\" (id={$this->issueInfo->Id})" );
		
		// Pick the first Category (under the found Brand) from the logon response.
		$this->categoryInfo = count( $this->publicationInfo->Categories ) > 0 ? $this->publicationInfo->Categories[0] : null;
		if( !$this->categoryInfo ) {
			$this->speedTestReport->reportErrorAndExit( 
				'Could not find the any Category un the Brand specified by the SPEEDTEST_BRANDNAME '.
				'option in the config.php file. Please check the Brand setup and add at least one Category. ' );
		}
		$this->speedTestReport->addComment( "Found Category: \"{$this->categoryInfo->Name}\" (id={$this->categoryInfo->Id})" );
		
		// Determine layout status
		$this->layoutStatusInfo = null;
		foreach( $this->publicationInfo->States as $status ) {
			if( $status->Type == 'Layout' ) {
				$this->layoutStatusInfo = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$this->layoutStatusInfo ) {
			$this->speedTestReport->reportErrorAndExit( 
				'Could not find the any Layout Status un the Brand specified by the SPEEDTEST_BRANDNAME '.
				'option in the config.php file. Please check the Brand setup and add at least one Layout Status. ' );
		}
		$this->speedTestReport->addComment( "Found Layout Status: \"{$this->layoutStatusInfo->Name}\" (id={$this->layoutStatusInfo->Id})" );
		
		// Determine article status
		$this->articleStatusInfo = null;
		foreach( $this->publicationInfo->States as $status ) {
			if( $status->Type == 'Article' ) {
				$this->articleStatusInfo = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$this->articleStatusInfo ) {
			$this->speedTestReport->reportErrorAndExit( 
				'Could not find the any Article Status un the Brand specified by the SPEEDTEST_BRANDNAME '.
				'option in the config.php file. Please check the Brand setup and add at least one Article Status. ' );
		}
		$this->speedTestReport->addComment( "Found Layout Status: \"{$this->articleStatusInfo->Name}\" (id={$this->articleStatusInfo->Id})" );

		// Determine image status
		$this->imageStatusInfo = null;
		foreach( $this->publicationInfo->States as $status ) {
			if( $status->Type == 'Image' ) {
				$this->imageStatusInfo = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$this->imageStatusInfo ) {
			$this->speedTestReport->reportErrorAndExit( 
				'Could not find the any Image Status un the Brand specified by the SPEEDTEST_BRANDNAME '.
				'option in the config.php file. Please check the Brand setup and add at least one Image Status. ' );
		}
		$this->speedTestReport->addComment( "Found Layout Status: \"{$this->imageStatusInfo->Name}\" (id={$this->imageStatusInfo->Id})" );
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
				"<td $style>Start</td><td $style>Stop</td>".
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
		
		$style = 'style="padding-left:10px;" valign="top"';
		print "<tr><td $style>{$this->start}</td><td $style>{$this->end}</td>".
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