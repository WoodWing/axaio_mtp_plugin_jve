<?php
/**
 * @package 	SCEnterprise
 * @subpackage 	Apps
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/apps/ExportUtils.class.php';
require_once BASEDIR.'/server/utils/NumberUtils.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar() / inputvar()

$ticket = checkSecure();
$tpl = HtmlDocument::loadTemplate( 'export2.htm' );
$objectmap = getObjectTypeMap();

$inPub     = isset($_POST['Publication']) ? intval($_POST['Publication']) : 0;
$inIssue   = isset($_POST['Issue'])       ? intval($_POST['Issue']) : 0;
$inObjType = trim($_REQUEST['ObjType']);
$inObjType = array_key_exists($inObjType, $objectmap) ? $inObjType : '';

$dum = null;
cookie("exportObject", $inPub == '', $inPub, $inIssue, $dum, $dum, $dum, $dum, $dum);

// use of cookie is insecure, parse the same variables as at the beginning
$inPub = intval($inPub);
$inIssue = intval($inIssue);

/////////////////////// *** Download selected objects *** ///////////////////////
$objectID = 0;
$txt = "";
$message = "";

// Fill in Pub & Issue combo's
ExportUtils::fillCombos( $inPub, $inIssue, null, $tpl );

$amount = isset($_POST["amount"]) ? intval($_POST["amount"]) : 0;
if ($amount > 0) {
	$sErrorMessage = BizResources::localize("ERR_SUCCES");
	$txt .= "<font class='text'>" . $sErrorMessage . "<ul>";
	// Download all objects one by one, this also ensures that one failure will still export the rest
	for ($i = 0; $i < $amount; $i++) {
		if (isset($_POST["checkbox".$i])) {
			$objectID = intval($_POST["objectID".$i]); // special service, no alien objects supported, so intval is allowed
			$txt .= "<li>" . formvar($_POST["name".$i]) . "</li>";
			$message = ExportUtils::downloadFile($ticket, $objectID, $inIssue );
		}
	}
	if ($message == "") {
		$sErrorMessage = BizResources::localize("ERR_TO");
		$txt .= "</ul>" . $sErrorMessage . " " . EXPORTDIRECTORY . "</font><br/><br/>";
	} else {
		$txt .= "<font class='text'>" . $message;
		$txt .= "<br><a href='javascript:history.back()'>".BizResources::localize('ACT_RETRY')."</a></font><br/><br/>";
	}
}

 /////////////////////// *** Stats *** ///////////////////////
$objCount = 0;
if ($inPub && $inIssue) {
	$txt .= showfiles( $ticket, $inPub, $inIssue, $inObjType, $objCount );
} else {
	$txt .= BizResources::localize("ERR_NONE_AVAILABLE").'<br/><br/>';
}
$tpl = str_replace ("<!--PAR:EXPORTCONTENT-->", $txt, $tpl);
$tpl = str_replace ("<!--PAR:EXPORTTITLE-->", getLocalizedAction($inObjType), $tpl);
$tpl = str_replace ("<!--PAR:EXPORTPAGE-->", "exportObject.php?ObjType=".urlencode($inObjType), $tpl);
$tpl = str_replace ("<!--PAR:APP_ICON-->", getAppIcon($inObjType), $tpl);
$tpl = str_replace( '<!--PAR:AMOUNT-->', $objCount, $tpl );
$tpl = str_replace( '<!--PAR:OBJTYPE-->', formvar($inObjType), $tpl );
print HtmlDocument::buildDocument($tpl);

function showfiles($ticket, $publ, $issue, $objType, &$ix )
{
	$objects = ExportUtils::getObjectList( $ticket, $publ, $issue, $objType );

	if (count($objects) == 0) {
		$txt = BizResources::localize("ERR_NONE_AVAILABLE");
		return $txt;
	}

	// show rows
	// display pages in table
	$txt = '';
	$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
	for ($ix = 0; $ix < count($objects); $ix++) {
		$clr = '#'.formvar($objects[$ix]['state']->Color);
		$txt .= '<tr bgcolor="#DDDDDD" onmouseOver="this.bgColor=\'#FF9342\';" onmouseOut="this.bgColor=\'#DDDDDD\';">
					<td align="center">
						<input id="checkbox'.$ix.'" name="checkbox'.$ix.'" type="checkbox" checked="checked"/>
						<input type="hidden" name="objectID'.$ix.'" value="'.formvar($objects[$ix]['id']).'"/>
						<input type="hidden" name="name'.$ix.'" value="'.formvar($objects[$ix]['name']).'"/>
					</td>
					<td onmouseUp="popUp(\'../apps/info.php?id='.urlencode($objects[$ix]['id']).'\');">'.formvar($objects[$ix]['name']).'</td>
					<td onmouseUp="popUp(\'../apps/info.php?id='.urlencode($objects[$ix]['id']).'\');">'.formvar($objects[$ix]['placed']).'</td>
					<td onmouseUp="popUp(\'../apps/info.php?id='.urlencode($objects[$ix]['id']).'\');">
						<table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td bgColor="'.$clr.'"></td></tr></table>
					</td>
					<td nowrap="nowrap" onmouseUp="popUp(\'../apps/info.php?id='.urlencode($objects[$ix]['id']).'\');">'.formvar($objects[$ix]['state']->Name).'</td>
					<td align="right" onmouseUp="popUp(\'../apps/info.php?id='.urlencode($objects[$ix]['id']).'\');">'.formvar(NumberUtils::getByteString($objects[$ix]['size'])).'</td>
				</tr>';
	}
	return $txt;
}

function getLocalizedAction( $objType )
{
	$map = array( 
		'Image'   => 'ACT_EXPORT_IMAGES', 
		'Article' => 'ACT_EXPORT_ARTICLES', 
		'Audio'   => 'ACT_EXPORT_AUDIOS', 
		'Video'   => 'ACT_EXPORT_VIDEOS' );
	if( !isset($map[$objType]) ) {
		die( 'Unsupported objecttype: '.$objType );
	}
	return BizResources::localize($map[$objType]);
}

function getAppIcon( $objType )
{
	$map = array( 
		'Image'   => '../../config/images/image_32.gif', 
		'Article' => '../../config/images/xml_32.gif', 
		'Audio'   => '../../config/images/audio_32.gif', 
		'Video'   => '../../config/images/video_32.gif' );
	if( !isset($map[$objType]) ) {
		die( 'ERROR: Unsupported object type: '.$objType );
	}
	return $map[$objType];
}