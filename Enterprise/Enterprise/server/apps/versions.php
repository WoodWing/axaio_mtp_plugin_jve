<?php
/**
 * @since      v3
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This web page shows all available versions of an object.
 * Users can activate a version by cliking the Restore button.
 */

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';


$ticket = isset($_GET["ticket"]) ? $_GET["ticket"] : null; // allow overrule (URL param)
$ticket = checkSecure( null, null, true, $ticket );
$objectmap = getObjectTypeMap();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0; // assumed: no support for alien ids (=string!)
$type = isset($_GET['type']) ? $_GET['type'] : '';
$version = isset($_GET['version']) ? $_GET['version'] : ''; // object version string: major.minor
$download = (isset($_GET['download']) && $_GET['download'] == 'true');
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';

//echo 'versions.php: id=['.$id.'] type =['.$type.'] version=['.$version.']<br>';
$bodyadd = '';

if ($download === true) {
	downloadFile( $ticket, $id, $version );
}

if( $version != "" & $download === false ) {
	try {
		require_once BASEDIR.'/server/services/wfl/WflRestoreVersionService.class.php';
		$service = new WflRestoreVersionService();
		$service->execute( new WflRestoreVersionRequest( $ticket, $id, $version ) );
		echo "<script language='Javascript'>";
			echo "document.location.href='browse.php';";
		echo "</script>";
	} catch( BizException $e ) {
		$bodyadd = "onLoad='showMessage(\"" . $e->getMessage() . "\");'";
	}
}

$tpl = HtmlDocument::loadTemplate( 'versions.htm' );
$tpl = str_replace ("<!--TYPE-->", $objectmap[$type], $tpl);
$tpl = str_replace ("<body>", '',$tpl);
$tpl = str_replace ("</body>", '',$tpl);

$slugAware = ( $type == 'Article' || $type == 'ArticleTemplate' || $type == 'Spreadsheet' );
if ( $slugAware ) {
	$thumbSlug = BizResources::localize("OBJ_SLUGLINE");
} else {
	$thumbSlug = BizResources::localize("OBJ_THUMBNAIL");
}
$tpl = str_replace("<!--PAR:THUMB_SLUG-->", $thumbSlug, $tpl);

try {
	require_once BASEDIR.'/server/services/wfl/WflListVersionsService.class.php';
	$service = new WflListVersionsService();
	$resp = $service->execute( new WflListVersionsRequest( $ticket, $id, null ) );
	$ArrayOfVersions = $resp->Versions;
} catch( BizException $e ) {
	$e = $e; // ignore error
	$ArrayOfVersions = array();
}

$objectVersion = "";
$start = 0;
krsort( $ArrayOfVersions ); // active version on top

if ($ArrayOfVersions) foreach ( $ArrayOfVersions as $ver ) {

	$start++;

	$objectVersion .= "\r\n\t\t".'<tr class="text" bgcolor=#DDDDDD>';
	if( $start == 1 ) { // mark current version nr bold
		$objectVersion .= '<td align="right"><b>'.$ver->Version.'</b></td>';
	} else {
		$objectVersion .= '<td align="right">'.$ver->Version.'</td>';
	}

	// restore icon
	if( $start==1 ) { // current/active version?
		$objectVersion .= '<td><img src="../../config/images/sinfo_16.gif" border="0" title="'.BizResources::localize('OBJ_CURRENT_VERSION').'"/></a></td>';
	} else {
		$sErrorMessage = BizResources::localize("ERR_ACTIVATE_VERSION");	
		$objectVersion .= '<td align="center">'
			.'<a href="javascript:activateVersion(\'versions.php?'."id=$id&download=false&version=$ver->Version&mode=".urlencode($mode).'&type='.urlencode($type).'\',\''.$sErrorMessage.'\');">'
			.'<img src="../../config/images/undo_16.gif" '
			.'title="'.BizResources::localize('ACT_RESTORE').'" />'
			.'</a></td>';
	}

	// download icon
	/* // EKL: Why suppressing download feature for articles?
	if ($slugAware) {
		$tpl = str_replace ("<!--PAR:DOWNLOAD-->", "", $tpl);
		$objectVersion .= "<td></td>";
	}
	else*/ {
		$tpl = str_replace ("<!--PAR:DOWNLOAD-->", BizResources::localize("ACT_DOWNLOAD"), $tpl);
		$sErrorMessage = BizResources::localize("ERR_DOWNLOAD_VERSION");
		if( $start==1 ) { // current/active version?
			$objectVersion .= "<td align='center'>"
				."<a href=\"javascript:downloadVersion('versions.php?download=true&id=$id&active=true','".$sErrorMessage."')\">"
				.'<img src="../../config/images/impt_16.gif" title="'.BizResources::localize("ACT_DOWNLOAD").'"/>'
				."</a></td>";
		} else { // old version
			$objectVersion .=
				"<td align='center'>"
				."<a href=\"javascript:downloadVersion('versions.php?download=true&id=$id&version=$ver->Version','".$sErrorMessage."')\">"
				.'<img src="../../config/images/impt_16.gif" title="'.BizResources::localize("ACT_DOWNLOAD").'"/>'
				."</a></td>";
		}
	}

	// When not formatted (timestamp(14)), format to YYYY-MM-DDTHH:MM:SS (varchar(30)) to prepare for date_timeFormat function
	if( strlen($ver->Created) == 14 && preg_match('/[0-9]+/i',$ver->Created) > 0 ) // only 14 digits?
	{
		$buf = $ver->Created;
		$ver->Created = substr($buf,0,4)."-".substr($buf,4,2)."-".substr($buf,6,2)."T".substr($buf,8,2).":".substr($buf,10,2).":".substr($buf,12,2);
	}
	
	$objectVersion .= '<td>'.formvar($ver->User).'</td>';
	$date_timeCreated = timeConverter($ver->Created);
	$objectVersion .= '<td><nobr>'.formvar($date_timeCreated).'</nobr></td>';
	
	if( empty($ver->Comment) ) {
		$objectVersion .= '<td><i>'.BizResources::localize('ERR_NO_COMMENT').'</i></td>';
	} else {
		$objectVersion .= '<td>'.formvar($ver->Comment).'</td>';
	}

	// Status color and name
	$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
	$objectVersion .= '<td><table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td bgColor="#'.$ver->State->Color.'"></td></tr></table></td>';
	$objectVersion .= '<td>'.formvar($ver->State->Name).'</td>';

	if ($slugAware) {
		$objectVersion .= '</tr><tr class="text" bgcolor="#EEEEEE">'; //'<td colspan="4" align="right"><b>'.$thumbSlug.':</b></td>';
		if ($ver->Slugline=="") {
			$objectVersion .= '<td/><td colspan="8"><i>' . BizResources::localize("ERR_NO_COMMENT") . "<i></td>";
		} else {
			$objectVersion .= '<td/><td colspan="8">'.formvar($ver->Slugline).'</td>';
		}
	}
	else {
		if( $start==1 ) { // current/active version?
			$objectVersion .= "<td><a href=javascript:popUpThumb('thumbnail.php?id=$id&rendition=preview')><img src=\"image.php?id=$id&rendition=thumb\" border=0></a></td>";
		}
		else {
			$objectVersion .= "<td><a href=javascript:popUpThumb('thumbnail.php?id=$id&rendition=preview&version=$ver->Version')><img src=\"image.php?id=$id&rendition=thumb&version=$ver->Version\" border=0></a></td>";
		}
	}
	
	$objectVersion .= "</tr>";
}

$tpl = str_replace ("<!--PAR:VERSION-->",$objectVersion, $tpl);
$tpl = str_replace ("<!--PAR:SHOWTHUMBCOL-->", !$slugAware, $tpl);

print HtmlDocument::buildDocument( $tpl, true, $bodyadd );


function downloadFile( $ticket, $objectID, $version = '' )
{
	if( empty($version) ) { // current version => get actual object
		$IDs = array($objectID);
		try {
			// Get native rendition and lock it:
			$getObjReq = new WflGetObjectsRequest( $ticket, $IDs, false, 'native' );
			$getObjService = new WflGetObjectsService();
			$getObjResp = $getObjService->execute( $getObjReq );
			$objects = $getObjResp->Objects;
		} catch( BizException $e ) {
			// ignore errors
			$e = $e;
		}
		
		$object = &$objects[0];

		$objectname = $object->MetaData->BasicMetaData->Name;
		$format = $object->MetaData->ContentMetaData->Format;
		$filePath = $object->Files[0]->FilePath;
	} else { // old version

		try {
			require_once BASEDIR.'/server/services/wfl/WflGetVersionService.class.php';
			$service = new WflGetVersionService();
			$vobject = $service->execute( new WflGetVersionRequest( $ticket, $objectID, $version, 'native' ) );
		} catch( BizException $e ) {
			$e = $e; // ignore error
			return; // get out of here
		}
		$objVer = $vobject->VersionInfo;
		$objectname = $objVer->Object;
		$format = $objVer->File->Type;
		$filePath = $objVer->File->FilePath;
	}

	$contenttype = $format;
	$extension = MimeTypeHandler::mimeType2FileExt($format);
	$filename = $objectname . $extension;
	download($filename, $filePath, $contenttype);
}

function download( $fileName, $filePath, $contenType )
{
	$file = basename($fileName);
	$fileSize = filesize($filePath);
	// Check if we deal with IE, using pattern matching: "MSIE 5.5" or "MSIE 6.0", etc
	$isIE = preg_match("/MSIE [0-9]+.[0-9]+/", $_SERVER['HTTP_USER_AGENT']);
	if( $isIE ) {
		$file = rawurlencode($file);
	} else {
		$file = str_replace( '"', '', $file );
	}

	header("Content-Type: $contenType");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=\"$file\"");

	header("Content-Transfer-Encoding: binary");
	header("Content-length: " . $fileSize);
	// BZ#8633 Solve output problems of very large files (> 120MB) on Windows servers
	// DO NOT echo $content;
	if ( ($phpoutput = fopen('php://output', 'wb')) ){
		if( ($fileInput = fopen( $filePath, 'rb' )) ) {
			require_once BASEDIR.'/server/utils/FileHandler.class.php';
			$bufSize =  FileHandler::getBufferSize( $fileSize);
			while( !feof($fileInput) ) {
				fwrite( $phpoutput, fread( $fileInput, $bufSize ) );
			}
			fclose( $fileInput );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			BizTransferServer::deleteFile( $filePath ); // Remove the file in transfer folder
		}
		fclose($phpoutput);
	} else {
		// revert to old method, THIS SHOULDN'T HAPPEN!!
		LogHandler::Log( 'versions', 'ERROR', 'Using old output method!' );
		echo file_get_contents( $filePath );
	}
	exit();
}

function timeConverter( $val ) 
{
	$val_array = preg_split('/[T]/', $val);
	$date_array = preg_split('/[-]/', $val_array['0']);
	$date_formated = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];
	return $date_formated . " " . $val_array['1'];
}
?>