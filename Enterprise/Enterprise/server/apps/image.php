<?php
//
// +--------------------------------------------------------------------+
// | server\apps\image.php												|
// +--------------------------------------------------------------------+
// | This page displays JPEG image for given object.					|
// | The following HTTP parameters are accepted:						|
// | - $id (unique object DBID)											|
// | - $type (object type, such as Layout/Article/Image)				|
// | - $rendition (resource file, such as thmub/preview/native)			|
// | - $version (object version number, or empty for active version)	|
// | - $page (optional; page number; can used for layouts)				|
// |																	|
// | If no valid file is found, a transparent image is shown instead.	|
// +--------------------------------------------------------------------+
//

//Avoid error messages to be written before headers are sent or to interfere with binary image data
ob_start(); 

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR."/server/secure.php";
require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';

global $globUser;
$ticket = isset($_GET["ticket"]) ? $_GET["ticket"] : null; // allow overrule (URL param)
$ticket = checkSecure( null, null, true, $ticket );

$jpeg = isset($_GET['jpeg']) ? $_GET['jpeg'] : '';
$fileFormat = isset($_GET['fileformat']) ? $_GET['fileformat'] : '';
$id = intval($_GET['id']); // can this be an alien id?
$type = isset($_GET['type']) ? $_GET['type'] : '';
$width = isset($_GET['width']) ? $_GET['width'] : '';
$height = isset($_GET['height']) ? $_GET['height'] : '';
$area = isset($_GET['areas']) ? $_GET['areas'] : 'Workflow';
$maxTextThumbHeight = 100; // need to get this from some settings?
$maxTextFullWidth = 768;
$rendition  = $_GET['rendition'];
$fileName = composeFileName($id);

switch( $rendition )
{
	case 'eMagThumb':
	case 'eMagPreview':
	case 'preview':
	case 'eMagInPageSlide':
	case 'eMagFullSlide':
	case 'eMagTextThumb':
	case 'eMagTextFull';
	case 'thumb':
		$contenttype = "image/jpeg";
		break;
	default: {
		$contenttype = "application/pdf";
		header("Content-Disposition: attachment; filename=$fileName");
	}
}
$version = isset($_GET['version']) ? $_GET['version'] : ''; // string e.g. "1.5"
$page    = isset($_GET['pageord']) ? intval($_GET['pageord']) : 0;
$edition = isset($_GET['edition']) ? intval($_GET['edition']) : 0; // edition id
$pnr     = isset($_GET['page'])    ? $_GET['page'] : '';
$pagesequence = isset($_GET['pagesequence']) ? intval($_GET['pagesequence']) : 0;

$lockObject = false;
$done = false;
$IDs = array($id);
$areas = array($area);
$transferServer = new BizTransferServer();
if (isset($jpeg) && !empty ($jpeg)) { // just display JPEG
	//Avoid error messages to be written before headers are send or to interfere with binary image data
	if( ob_get_contents() ) while( ob_end_clean()); 
	// security: jpeg must start with webeditor location or filestore as fallback, check realpath to prevent
	//           hacks to go to a higher directory (e.g. ..)
	$jpeg = realpath($jpeg);
	if (strpos($jpeg, realpath(WEBEDITDIR)) === 0
		|| strpos($jpeg, realpath(ATTACHMENTDIRECTORY)) === 0){
		header("Content-type: image/jpeg");
		readfile($jpeg);
	} else {
		LogHandler::Log('image', 'ERROR', 'Could not display image "' . $jpeg . '"');
	}
	$done = true;
}
elseif( !empty($version) ) //requested version
{
	try {
		require_once BASEDIR.'/server/services/wfl/WflGetVersionService.class.php';
		$service = new WflGetVersionService();
		$resp = $service->execute( new WflGetVersionRequest( $ticket, $id, $version, $rendition ) );
		$file = $resp->VersionInfo->File;
		if( $file ) {
			$thumbPath = $file->FilePath;
			// Some thumbnails are empty, in that case show the default (below)
			if ( !empty($thumbPath) )
			{
				//Avoid error messages to be written before headers are send or to interfere with binary image data
				if( ob_get_contents() ) while( ob_end_clean());
				header("Content-type: $contenttype");
				outputContent( $thumbPath );
				$done = true;
			}
		}
	} catch( BizException $e ) {
		$e = $e; // ignore error
	}
}
elseif( $page ) // page of layout
{
	require_once BASEDIR."/server/bizclasses/BizPage.class.php";
	try {
		BizSession::startSession( $ticket );
		$objPages = BizPage::GetPages($ticket, $globUser, null, $IDs, array($page), false, $edition , array( $rendition ), null, array($pagesequence) );
		$objectPage = $objPages[0];
	
		if( !empty($objectPage->Pages) && $objectPage->Pages[0]->Renditions[0] ) {
			$attachment = $objectPage->Pages[0]->Files[0];
			$pagePath = $attachment->FilePath;
			if( !empty($pagePath) ) {
				if( ob_get_contents() ) while( ob_end_clean()); 
				header("Content-type: $contenttype");
				outputContent( $pagePath );
				$done = true;
			}
		}
	} catch( BizException $e ) {
		echo '<font color="red">'.$e->getMessage().'<br/>'.$e->getDetail().'</font>';
		exit();
	}
	BizSession::endSession();
}
elseif( $rendition == 'eMagThumb' || $rendition == 'eMagPreview')
{
	$tempRendition = 'preview';
	
	require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
	try {
		BizSession::startSession( $ticket );
		$getObjReq = new WflGetObjectsRequest( $ticket, $IDs, $lockObject, $tempRendition, null, null, $areas);
		$getObjService = new WflGetObjectsService();
		$getObjResp = $getObjService->execute( $getObjReq );
		$objects = $getObjResp->Objects;
	} catch( BizException $e ) {
	}
	BizSession::endSession();
	if( $objects && $objects[0] ) {
		$object = $objects[0];
	}
	
	if( isset($object->Files) ) {
		foreach( $object->Files as $file ) {
			if ( $file && $file->Rendition == $tempRendition ) {	
				$type = $file->Type;
				$filePath = $file->FilePath;
				LogHandler::Log('image.php', 'DEBUG', 'pnr: ' . $pnr);
				
				if( ($pnr) && isset( $object->Pages )) {
					foreach ($object->Pages as $p) {
						if( $p->PageNumber == $pnr) {
							$attachment = $p->Files[0];		
							if($attachment) {
								$filePath = $attachment->FilePath;
								break;
							}
						}
					}
				}
				
				if ( !empty($filePath) ) {
					//Avoid error messages to be written before headers are send or to interfere with binary image data
					if( ob_get_contents() ) while( ob_end_clean());
					header('Content-type: '.$type);
					
					$max = 100;
					$quality = 0;
					switch ($rendition) {
						case 'eMagThumb':
							$max = 150;
							$quality = 60;
							break;
						case 'eMagPreview':
							$max = 1024;
							$quality = 100;
							break;
					}
					
					require_once BASEDIR.'/server/utils/ImageUtils.class.php';
					ImageUtils::ResizeJPEG( $max, $filePath, $filePath, $quality, null, null );
					
					outputContent( $filePath );
					$done = true;
				}
			}
		}
	}
}
elseif( $rendition == 'eMagTextThumb'|| $rendition == 'eMagTextFull' || $rendition == 'eMagSlideFull')
{	
	// use preview rendition (jpg) and scale that
	require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
	$Files="";
	$Pages="";
	$objectType="";
	$objects = array();
	
	$tempRendition = 'preview';
	$contenttype = "image/jpeg";

	try {
		BizSession::startSession( $ticket );
		$getObjReq = new WflGetObjectsRequest( $ticket, $IDs, $lockObject, $tempRendition, null, null, $areas);
		$getObjService = new WflGetObjectsService();
		$getObjResp = $getObjService->execute( $getObjReq );
		$objects = $getObjResp->Objects;
	} catch( BizException $e ) {
	}
	BizSession::endSession();
	if( $objects && $objects[0] ) {
		$object = $objects[0];
	}
	
	$done = false;
	
	if( isset($object->Files) ) foreach( $object->Files as $file )
	{
		if ( $file && $file->Rendition == $tempRendition )
		{	
			$filePath = $file->FilePath;
			// Some thumbnails are empty, in that case show the default (below)
			if ( !empty($filePath) )  // Note this is 1, because when getting blob from DB on Oracle the length is 1...
			{
				//Avoid error messages to be written before headers are send or to interfere with binary image data
				if( ob_get_contents() ) while( ob_end_clean());
				$format = !empty($file->Type) ? $file->Type : $contenttype;
				header('Content-type: '.$format);				
				$imageWidth = $object->MetaData->ContentMetaData->Width;
				$imageHeight = $object->MetaData->ContentMetaData->Height;
				if( $rendition == 'eMagTextThumb' )
				{
					$scaleFactor = $imageHeight/$maxTextThumbHeight;
					$width = $imageWidth / $scaleFactor;
					$height = $maxTextThumbHeight;
				}
				else if( $rendition == 'eMagTextFull' )
				{
					$width = min($maxTextFullWidth, $imageWidth);
					$scaleFactor = $imageWidth / $width;
					$height = $imageHeight / $scaleFactor;
					LogHandler::Log('image', 'INFO', '$imageWidth:'.$imageWidth. ',$width:'.$width.',$imageHeight:'.$imageHeight);
				}
				else if( $rendition == 'eMagSlideFull' )
				{
					$width = min($maxTextFullWidth, $imageWidth);
					$scaleFactor = $imageWidth / $width;
					$height = $imageHeight / $scaleFactor;
					LogHandler::Log('image', 'INFO', '$imageWidth:'.$imageWidth. ',$width:'.$width.',$imageHeight:'.$imageHeight);
				}

				require_once BASEDIR.'/server/utils/ImageUtils.class.php';
				ImageUtils::ResizeJPEG( null, $filePath, $filePath, 90, $width, $height );

				outputContent( $filePath );
				$done = true;
			}
		}
	}
}
elseif( $rendition == 'eMagInPageSlide')
{	
	// two ways. If png use native rendition and scale to correct size
	// if other, use preview rendition (jpg) and scale that
	require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
	$Files="";
	$Pages="";
	$objectType="";
	$objects = array();
	
	// if we're dealing with a png file use native rendition and scale it
	if ($fileFormat == "image/png") {
		$tempRendition = 'native';
		$contenttype = $fileFormat;	
	}
	else{
		$tempRendition = 'preview';
		$contenttype = "image/jpeg";
	}
	try {
		BizSession::startSession( $ticket );
		$getObjReq = new WflGetObjectsRequest( $ticket, $IDs, $lockObject, $tempRendition, null, null, $areas);
		$getObjService = new WflGetObjectsService();
		$getObjResp = $getObjService->execute( $getObjReq );
		$objects = $getObjResp->Objects;
	} catch( BizException $e ) {
	}
	BizSession::endSession();
	if( $objects && $objects[0] ) {
		$object = $objects[0];
	}
	
	$done = false;
	
	if( isset($object->Files) ) foreach( $object->Files as $file )
	{
		if ( $file && $file->Rendition == $tempRendition )
		{	
			$filePath = $file->FilePath;
			// Some thumbnails are empty, in that case show the default (below)
			if ( !empty($filePath) )
			{
				//Avoid error messages to be written before headers are send or to interfere with binary image data
				if( ob_get_contents() ) while( ob_end_clean());
				$format = !empty($file->Type) ? $file->Type : $contenttype;
				header('Content-type: '.$format);

				require_once BASEDIR.'/server/utils/ImageUtils.class.php';
				if ($fileFormat == "image/png") {
					ImageUtils::ResizePNG( null, $filePath, $filePath, $width, $height );
				}
				else {
					ImageUtils::ResizeJPEG( null, $filePath, $filePath, 100, $width, $height );

				}
				outputContent( $filePath );
				$done = true;
			}
		}
	}

}
else
{	
	require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
	$Files="";
	$Pages="";
	$objectType="";
	$objects = array();
	
	try {
		BizSession::startSession( $ticket );
		$getObjReq = new WflGetObjectsRequest( $ticket, $IDs, $lockObject, $rendition, null, null, $areas);
		$getObjService = new WflGetObjectsService();
		$getObjResp = $getObjService->execute( $getObjReq );
		$objects = $getObjResp->Objects;
	} catch( BizException $e ) {
	}
	BizSession::endSession();
	if( $objects && $objects[0] ) {
		$object = $objects[0];
	}
	
	$done = false;
	if( $type != 'Article' )
	{
		if( ($page || $pnr) && isset( $object->Pages ))// layouts -> output page attachment
		{
			foreach ($object->Pages as $p) 
			{
				if ($p->Edition->Id != 0 && $p->Edition->Id != $edition) continue;
				if( $p->PageOrder == $page || $p->PageNumber == $pnr)
				{
					$attachment = $p->Files[0]->value;
					//LogHandler::Log('image', 'DEBUG', 'Files: '.print_r($attachment,true) );		
					if($attachment) 
					{
						$thumbPath = $attachment->FilePath;
						//Some thumbnails are empty, in that case show the default (below)
						if ( !empty($thumbPath) )
						{
							//Avoid error messages to be written before headers are send or to interfere with binary image data
							if( ob_get_contents() ) while( ob_end_clean());
							header("Content-type: $contenttype");
							outputContent( $thumbPath );
							$done = true;
						}
					}
				}
			}
		}
		else // non-layouts -> output object attachment
		{
			if( isset($object->Files) ) foreach( $object->Files as $file )
			{
				if ( $file && $file->Rendition == $rendition )
				{	
					$thumbPath = $file->FilePath;
					// Some thumbnails are empty, in that case show the default (below)
					if ( !empty($thumbPath) )  // Note this is 1, because when getting blob from DB on Oracle the length is 1...
					{
						//Avoid error messages to be written before headers are send or to interfere with binary image data
						if( ob_get_contents() ) while( ob_end_clean());
						$format = !empty($file->Type) ? $file->Type : $contenttype;
						header('Content-type: '.$format);
						outputContent( $thumbPath );
						$done = true;
					}
				}
			}
		}
	}
}

// output blanc image on failure
if( !$done )
{
	//Avoid error messages to be written before headers are send or to interfere with binary image data
	if( ob_get_contents() ) while( ob_end_clean()); 
	header("Content-type: image/gif");
	readfile( BASEDIR."/config/images/transparent.gif" );
}

function composeFileName($objectid) 
{
	require_once BASEDIR."/server/dbclasses/DBObject.class.php";
	$fileName = '';

	if ($objectid) {
		$fileName = DBObject::getObjectName($objectid);
   		$fileName .= '.pdf';
	}
	
	return $fileName;
}

/**
 * Output binary content to the browser.
 *
 * @param string $filePath File transfer path 
 */
function outputContent ( $filePath )
{
	$transverServer = new BizTransferServer();
	$fileSize = filesize($filePath);
	header("Content-length: " . $fileSize);
	if (($phpoutput = fopen('php://output', 'wb'))) {
		if( ($fileInput = fopen( $filePath, 'rb' )) ) {
			require_once BASEDIR.'/server/utils/FileHandler.class.php';
			$bufSize =  FileHandler::getBufferSize( $fileSize);
			while( !feof($fileInput) ) {
				fwrite( $phpoutput, fread( $fileInput, $bufSize ) );
			}
			fclose( $fileInput );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transverServer->deleteFile( $filePath ); // Remove the file in transfer folder
		}
		fclose( $phpoutput );
	} else {
		// revert to old method, THIS SHOULDN'T HAPPEN!!
		LogHandler::Log('image', 'ERROR', 'Using old output method!');
		echo file_get_contents( $filePath );
	}
}
?>
