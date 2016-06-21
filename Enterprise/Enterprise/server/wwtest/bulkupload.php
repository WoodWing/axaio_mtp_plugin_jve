<?php 

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Introduction

echo '<html><body style="font-family: Arial;">';
echo '<h3>Bulk Upload</h3>
	This Bulk Upload tool uploads files from a folder (on disk) to the Enterprise database.
	The folder is specified at this module through the IMAGE_STORE_FOLDER option.
	This should point to a root folder under which an hierarchy of subfolders is expected.
	At the first level of the hierarchy, brands (publications) are expected.
	The names of those folders must match with existing brands at Enterprise.
	Under these "brand folders", a second level at hierarchy is expected, representing issues.
	Issues are automatically created! Therefore, the names of those folders should NOT match with any existing issue under the brand.
	Under these "issue folders", a third level at hierarchy is expected, representing categories (sections).
	Categories are automatically created! Therefore, the names of those folders should NOT match with any existing category under the brand.
	Under these "category folders", files are expected which will get automatically uploaded.
	For each file, an Enterprise object is created. File extensions are used to determine object types.
	The TESTSUITE option (at configserver.php file) is used to pick user and password. This user must have admin rights to the brands!';
echo '<br/>';

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Options

define( 'IMAGE_STORE_FOLDER', '/MyBulkUploadFiles/' ); // include '/' at end!   -> root path to upload images from (having pub/iss/sec folder structure)
define( 'OVERRULE_PUBLICATION', false ); // whether to create section inside issues (and set option per issue)

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Init

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';

if( !is_dir(IMAGE_STORE_FOLDER) ) { 
	showError( 'Image storage not found at: "'.IMAGE_STORE_FOLDER.'". '.
		'Make sure the folder exists and web user has read access, '.
		'or change location by adjusting IMAGE_STORE_FOLDER option at the '.basename(__FILE__).' file.', 'CONFIGURATION ERROR' );
	die();
} 

set_time_limit(3600);

$session['watch'] = new StopWatch();

require_once BASEDIR.'/server/protocols/soap/AdmClient.php';
$session['adminClient'] = new WW_SOAP_AdmClient();

require_once BASEDIR.'/server/protocols/soap/WflClient.php';
$session['workflowClient'] = new WW_SOAP_WflClient();

if( !defined('TESTSUITE') ) {
	showError( 'The TESTSUITE setting was not found. '.
		'Please add the TESTSUITE setting to your configserver.php file.', 'CONFIGURATION ERROR' );
	die();
}
$suiteOpts = unserialize( TESTSUITE );
$session['routeTo'] = $suiteOpts['User'];

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// File uploads

logOn( $session, $suiteOpts['User'], $suiteOpts['Password'] );
createPubIssSecStructure( $session, IMAGE_STORE_FOLDER );
uploadAllIFileInsideStructure( $session, IMAGE_STORE_FOLDER );
logOff( $session );

echo '<br/><br/>';
if( $session['is_error'] ) {
	echo '<b><font color="red">Bulk Upload has failed!</font><br/></b>';
} else {
	echo '<b><font color="green">Bulk Upload was successful!</font><br/></b>';
}
echo '</body></html>';

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Logging helper functions

function showTitle( $caption )
{
	echo('<hr/><font color="blue"><b>'.$caption.'</b></font><br/>' );
}

function showError( $errStr, $caption = null )
{
	if( $caption ) {
		echo '<h3><font color="red">'.$caption.'</font></h3>';
	}
	echo '<font color="red">'.$errStr.'</font><br/>';
}

function showException( $e )
{
	showError( $e->getMessage() );
}

function showDuration( $duration )
{
	echo('<b>Duration: </b>'.$duration.' sec<br/>');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// SOAP helper functions

// Perform admin SOAP request: LogOn
function logOn( &$session, $user, $password )
{
	try {
		showTitle( 'Log on user "'.$user.'"' );
		$session['watch']->Start();
	
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnResponse.class.php';
		$req = new AdmLogOnRequest();
		$req->AdminUser 		= $user;
		$req->Password 		= $password;
		$req->ClientName 		= 'My machine IP';
		$req->ClientAppName 	= 'Bulk Upload Test'; 
		$req->ClientAppVersion = 'v'.SERVERVERSION;
	
		$resp = $session['adminClient']->LogOn( $req );
		
	} catch( SoapFault $e ) {
		showException( $e );
		$resp = null;
	} catch( BizException $e ) {
		showException( $e );
		$resp = null;
	}
	$session['is_error'] = is_null($resp) || is_soap_fault($resp);
	showDuration( $session['watch']->Fetch() );
	$session['ticket'] = $session['is_error'] ? '' : $resp->Ticket;
}

// Perform admin SOAP request: LogOff
function logOff( &$session )
{
	if( !$session['ticket'] ) { return; } // nothing to do
	try	{
		showTitle('Log off' );
		$session['watch']->Start();

		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOffResponse.class.php';
		$req = new AdmLogOffRequest( $session['ticket'] );
		$session['adminClient']->LogOff( $req );
		
	} catch( SoapFault $e ) {
		showException( $e );
	} catch( BizException $e ) {
		showException( $e );
	}
	showDuration( $session['watch']->Fetch() );
}

// Perform admin SOAP request: GetPublications
function getPublication( &$session, $pubName )
{
	if( $session['is_error'] ) { return null; } // nothing to do
	try {
		showTitle( 'Get brand "'.$pubName.'"' );
		$session['watch']->Start();
		
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsResponse.class.php';
		$req = new AdmGetPublicationsRequest( $session['ticket'], array() );
		$resp = $session['adminClient']->GetPublications( $req );
		
	} catch( SoapFault $e ) {
		showException( $e );
		$resp = null;
	} catch( BizException $e ) {
		showException( $e );
		$resp = null;
	}
	$session['is_error'] = is_null($resp) || is_soap_fault($resp);
	showDuration( $session['watch']->Fetch() );
	if( $session['is_error'] ) {
		return null;
	}
	foreach( $resp->Publications as $iterPub ) {
		if( $iterPub->Name == $pubName ) {
			return $iterPub;
		}
	}
	showError( 'Could not find brand "'.$pubName.'" at Enterprise.' );
	$session['is_error'] = true;
	return null;
}


// Perform admin SOAP request: CreateIssues
function createIssue( &$session, $pubId, $issueName )
{
	if( $session['is_error'] ) { return null; } // nothing to do
	try {
		showTitle('Create issue "'.$issueName.'" for brand "'.$session['curPubDir'].'"' );
		$session['watch']->Start();
		
		$newiss = new AdmIssue();
		$newiss->Name 					= $issueName; // . '_' . date('dmy_his');
		$newiss->Description 			= 'Created by Bulk Upload Test';
		$newiss->SortOrder				= 0;
		$newiss->EmailNotify			= false;
		$newiss->ReversedRead			= false;
		$newiss->OverrulePublication	= OVERRULE_PUBLICATION;
		//$newiss->Deadline				= date("Y-m-d\\TH:i ", mktime( 0, 0, 0, date("m"), date("d")+2, date("Y"))); 
		$newiss->ExpectedPages			= 32;
		$newiss->Subject				= 'Bulk Upload Test';
		$newiss->Activated				= true;
		$newiss->PublicationDate		= date("Y-m-d\\TH:i:s", mktime( 0, 0, 0, date("m"), date("d"), date("Y"))); 

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateIssuesRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateIssuesResponse.class.php';
		$req = new AdmCreateIssuesRequest();
		$req->Ticket 		= $session['ticket']; 
		$req->RequestModes 	= array();
		$req->PublicationId = $pubId;
		$req->Issues 		= array($newiss);
	
		$resp = $session['adminClient']->CreateIssues( $req );
		
	} catch( SoapFault $e ) {
		showException( $e );
		$resp = null;
	} catch( BizException $e ) {
		showException( $e );
		$resp = null;
	}
	$session['is_error'] = is_null($resp) || is_soap_fault($resp);
	showDuration( $session['watch']->Fetch() );
	return $session['is_error'] ? null : $resp->Issues[0];
}

// Perform admin SOAP request: CreateSections
function createSection( &$session, $pubId, $issId, $sectionName )
{
	if( $session['is_error'] ) { return null; } // nothing to do
	try {
		showTitle( 'Create category "'.$sectionName.'" for brand "'.$session['curPubDir'].'"' );
		$session['watch']->Start();
	
		$newsec = new AdmSection();
		$newsec->Name 				= $sectionName; // . '_' . date('dmy_his');
		$newsec->Description 		= 'Created by Bulk Upload Test';
		$newsec->SortOrder 			= 0;
		//$newsec->Deadline			=  date("Y-m-d\\TH:i ", mktime( 0, 0, 0, date("m"), date("d")+2, date("Y"))); 
		$newsec->ExpectedPages		= 32;

		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateSectionsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/adm/AdmCreateSectionsResponse.class.php';
		$req = new AdmCreateSectionsRequest();
		$req->Ticket 		= $session['ticket']; 
		$req->RequestModes 	= array();
		$req->PublicationId = $pubId;
		$req->IssueId		= OVERRULE_PUBLICATION === true ? $issId : null;
		$req->Sections 		= array($newsec);
		
		$resp = $session['adminClient']->CreateSections( $req );
		
	} catch( SoapFault $e ) {
		showException( $e );
		$resp = null;
	} catch( BizException $e ) {
		showException( $e );
		$resp = null;
	}
	$session['is_error'] = is_null($resp) || is_soap_fault($resp);
	showDuration( $session['watch']->Fetch() );
	return $session['is_error'] ? null : $resp->Sections[0];
}

// Runs through folder structure and upload all files at 3rd folder level (=section level).
function uploadAllIFileInsideStructure( &$session, $path, $ply=0 )
{
	if( $session['is_error'] ) { return; } // nothing to do
	$objects = array();
    $items = glob($path . '*');
	foreach ($items as $item) {
		if (is_dir($item)) {
			// Keep track of current pub/iss/sec
			switch( $ply ) {
				case 0: $session['curPubDir'] = basename($item); break;
				case 1: $session['curIssDir'] = basename($item); break;
				case 2: $session['curSecDir'] = basename($item); break;
			}
			// Retrieve statusses at pub or issue level
			if( $ply == 0 && OVERRULE_PUBLICATION === false ) { // pub level (not overruled by iss)
				$pub = $session['publications'][$session['curPubDir']]['id'];
				$session['curStates'] = getStates( $session, $pub );
			}
			else if( $ply == 1 && OVERRULE_PUBLICATION === true ) { // issue level (overruled pub)
				$pub = $session['publications'][$session['curPubDir']]['id'];
				$iss = $session['publications'][$session['curPubDir']]['issues'][$session['curIssDir']]['id'];
				$session['curStates'] = getStates( $session, $pub, $iss );
			}
			// Continue recusive search through folders
			$subdir = $path . basename($item);
			uploadAllIFileInsideStructure( $session, $subdir . '/', $ply+1 );
		} else if( is_file($item) ) {
			if( $ply == 3 ) { // files at section level?
				
				// Derive pub/iss/sec ids from current path
				$pub = $session['publications'][$session['curPubDir']]['id'];
				$iss = $session['publications'][$session['curPubDir']]['issues'][$session['curIssDir']]['id'];
				$sec = $session['publications'][$session['curPubDir']]['issues'][$session['curIssDir']]['sections'][$session['curSecDir']]['id'];
				
				// Derive object type and format from file extention
				$mimeType = MimeTypeHandler::filePath2MimeType( $item );
				$objType = MimeTypeHandler::filename2ObjType( $mimeType, $item );
				
				// Search for first best found status for the object type
				$stt = '';
				foreach( $session['curStates'] as $status ) {
					if( $status->Type == $objType ) {
						$stt = $status->Id;
						break; // stop search
					}
				}				
				// Collect object structs (in preparation to real CreateObjects request)
				if( $stt == '' ) {
					showError( 'Could not find a status for object type "'.$objType.'".' );
					$session['is_error'] = true;
				} else {
					$objects[] = objectStruct( $session, $pub, $iss, $sec, $stt, basename($item), $objType, $mimeType, $item );
				}
			}
		}
	}
	// Perform CreateObjects to create new object
	if( count($objects) > 0 ) {
		createObjects( $session, $objects );
	}
}

// buid workflow SOAP object complex type structure (used for CreateObjects request)
function objectStruct( $session, $pub, $iss, $sec, $stt, $name, $objType, $format, $filePath )
{
	$meta = objectMetaData( $session, $pub, $sec, $stt, $name, $format, filesize($filePath), $objType );
	$objTarget = objectTarget( $pub, $iss );

	require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
	$attachment = new Attachment('native', 'application/indesign');					
	$transferServer = new BizTransferServer();
	$transferServer->copyToFileTransferServer($filePath , $attachment);		
	$files = array( $attachment );

	$newObj = new Object();
	$newObj->MetaData = $meta;
	$newObj->Relations = array();
	$newObj->Pages = array();
	$newObj->Files = $files;
	$newObj->Targets = array( $objTarget );
	return $newObj;
}

// Perform workflow SOAP request CreateObjects
function createObjects( &$session, $objects )
{
	if( $session['is_error'] ) { return; } // nothing to do
	$session['watch']->Start();
	showTitle( 'Upload files ('.count($objects).') at: '.$session['curPubDir'].' / '.$session['curIssDir'].' / '.$session['curSecDir'].'' );
	try {
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsResponse.class.php';
		$req = new WflCreateObjectsRequest( $session['ticket'], false, $objects );
		$session['workflowClient']->CreateObjects( $req ); 
	} catch( SoapFault $e ) {
		showException( $e );
	} catch( BizException $e ) {
		showException( $e );
	}
	showDuration( $session['watch']->Fetch() );
}

// Perform workflow SOAP request GetStates
function getStates( &$session, $pubId, $issId=null )
{
	if( $session['is_error'] ) { return null; } // nothing to do
	$session['watch']->Start();
	$pubObj = new Publication( $pubId );
	$issObj = is_null($issId) ? null : new Issue( $issId );

	// perform SOAP call
	showTitle( 'Get statusses for brand "'.$session['curPubDir'].'"' );
	try {
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesRequest.class.php';
		require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesResponse.class.php';
		$req = new WflGetStatesRequest( $session['ticket'], null, $pubObj, $issObj );
		$resp = $session['workflowClient']->GetStates( $req );
	} catch( SoapFault $e ) {
		showException( $e );
		$resp = null;
	} catch( BizException $e ) {
		showException( $e );
		$resp = null;
	}
	$session['is_error'] = is_null($resp) || is_soap_fault($resp);
	showDuration( $session['watch']->Fetch() );
	return $session['is_error'] ? null : $resp->States;
}

// Runs through folder structure and get publication info for 1 level folder and create issues and sections for resp. 2 and 3 level folders.
// All pub/iss/sec info is stored in $session['publications'] tree.
function createPubIssSecStructure( &$session, $path, $ply=0 )
{
	if( $session['is_error'] ) { return 0; } // nothing to do
	$thisPly = 0;
	$dirs = glob($path . '*');
	foreach ($dirs as $dir) {
		if (is_dir($dir)) {
			
			// Get publication or create issues and section, depending on folder level
			switch( $ply ) {
				case 0: // pub
					$pub = getPublication( $session, basename($dir) );
					if( !is_null($pub)) {
						$session['curPubId'] = $pub->Id;
						$session['curPubDir'] = basename($dir);
						$session['publications'][$session['curPubDir']]['id'] = $pub->Id;
					}
					break;
				case 1: // iss
					$iss = createIssue( $session, $session['curPubId'], basename($dir) );
					if( !is_null($iss)) {
						$session['curIssId'] = $iss->Id;
						$session['curIssDir'] = basename($dir);
						$session['publications'][$session['curPubDir']]['issues'][$session['curIssDir']]['id'] = $iss->Id;
					}
					break;
				case 2: // sec
					$sec = createSection( $session, $session['curPubId'], $session['curIssId'], basename($dir) );
					if( !is_null($sec)) {
						$session['curSecId'] = $sec->Id;
						$session['curSecDir'] = basename($dir);
						$session['publications'][$session['curPubDir']]['issues'][$session['curIssDir']]['sections'][$session['curSecDir']]['id'] = $sec->Id;
					}
					break;
			}
			if( (isset($pub) && !is_null($pub)) || (isset($iss) && !is_null($iss)) || (isset($sec) && !is_null($sec)) ) {
				// Continue searching folders recursively
				$subdir = $path . basename($dir);
				$thisPly = createPubIssSecStructure( $session, $subdir . '/', $ply+1 );
				$thisPly = max( $thisPly, $ply );
			}
		}
	}
	return $thisPly;
}

/**
 * Returns object Target
 * @param int $pubId Publication Db Id
 * @param int $issId Issue Db Id
 * @return Target $target
 */
function objectTarget( $pubId, $issId )
{
	// To build Target
	$pubChannel = new PubChannel();
	$pubChannel->Id = $pubId;
	
	$issue = new Issue();
	$issue->Id = $issId;
	
	$target = new Target();
	$target->PubChannel = $pubChannel;
	$target->Issue      = $issue;
	return $target;
}

// Return basic meta data (workflow SOAP) structure for some given object details.
function objectMetaData( $session, $pubId, $secId, $sttId, $objName, $format, $fileSize, $objType )
{
	// infos
	$pubObj = new Publication( $pubId );
	$secObj = new Category( $secId );
	
	// build metadata
	$basMD = new BasicMetaData();
	$basMD->Name = $objName;
	$basMD->Type = $objType;
	$basMD->Publication = $pubObj;
	$basMD->Category = $secObj;
	
	$srcMD = new SourceMetaData();
	$srcMD->Author = $session['routeTo'];
	$rigMD = new RightsMetaData();
	$rigMD->Copyright = 'copyright';

	$cntMD = new ContentMetaData();
	$cntMD->Slugline = 'slug';
	$cntMD->Format = $format;
	$cntMD->FileSize = $fileSize;
	$wflMD = new WorkflowMetaData();
	//$wflMD->Deadline = date('Y-m-d\TH:i:s'); 
	//$wflMD->Urgency = 'Top';
	$wflMD->State = new State( $sttId );
	$wflMD->RouteTo = $session['routeTo'];
	$wflMD->Comment = 'Created by Bulk Upload Test';
	$extMD = new ExtraMetaData(); 

	$metaData                   = new MetaData();
	$metaData->BasicMetaData    = $basMD;
	$metaData->RightsMetaData   = $rigMD;
	$metaData->SourceMetaData   = $srcMD;
	$metaData->ContentMetaData  = $cntMD;
	$metaData->WorkflowMetaData = $wflMD;
	$metaData->ExtraMetaData    = $extMD;
	return $metaData;
	
}
