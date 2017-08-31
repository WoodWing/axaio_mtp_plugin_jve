<?php
// info.php: Shows object information with following fucntionallity:
// - Delete relation between layouts and placements
// - Show thumbnail and preview
// - Show versions
// - For layouts: link to show pages at Publication Overview

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar() / inputvar()

global $isadmin;
global $ispubladmin;
$ticket = checkSecure();

$ID         = isset($_REQUEST['id'])   ? intval($_REQUEST['id']) : 0;
$childId    = isset($_GET['child'])    ? intval($_GET['child']) : 0;
$parentId   = isset($_GET['parent'])   ? intval($_GET['parent']) : 0;
$parRelType = isset($_GET['reltype'])  ? $_GET['reltype'] : 'Placed'; // 'Planned' or 'Placed'
$Unlock     = isset($_GET['unlock'])   ? $_GET['unlock'] : '';

$message = '';
try {
	// Unlock objects
	if( !empty($Unlock) ) {
		if( checkSecure('publadmin') ) {
			require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
			$request = new WflUnlockObjectsRequest( $ticket, array($ID), null );
			$service = new WflUnlockObjectsService();
			$service->execute( $request );
		}
	}

	// Delete child object relations
	if( !empty($childId) ) {
		if( checkSecure('publadmin') ) {
			$relations = array( new Relation( $ID, $childId, $parRelType, array() ) );
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectRelationsService.class.php';
			$service = new WflDeleteObjectRelationsService();
			$request = new WflDeleteObjectRelationsRequest( $ticket, $relations );
			$service->execute( $request );
		}	
	}

	// Delete parent object relations
	if( !empty($parentId) ) {
		if( checkSecure('publadmin') ) {
			$relations = array( new Relation( $parentId, $ID, $parRelType, array() ) );
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectRelationsService.class.php';
			$service = new WflDeleteObjectRelationsService();
			$request = new WflDeleteObjectRelationsRequest( $ticket, $relations );
			$service->execute( $request );
		}
	}
} catch( BizException $e ) {
	$message .= $e->getMessage().'\\n';
}

// Get the object
$object = null;
try {
	require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
	$request = new WflGetObjectsRequest( $ticket, array($ID), false, 'none', null );
	$service = new WflGetObjectsService();
	$response = $service->execute( $request );
	$objects = $response->Objects;
	$object = $objects[0];
} catch( BizException $e ) {
	$message .= $e->getMessage().'\\n';
}

$tpl = HtmlDocument::loadTemplate( 'info.htm' );
if( $object ) {
	$ID = $object->MetaData->BasicMetaData->ID;
	$tpl = showMetaData( $tpl, $object );
	$tpl = showObjectTargets( $tpl, $object );
	$tpl = showObjectRelations( $ticket, $tpl, $object );
}

if( $message ) {
	$message = '<script language="javascript">alert("'.$message.'");</script>';
}
$tpl = str_replace('<!--PAR:MESSAGE-->', $message, $tpl );

$logtxt = '';
if( $isadmin || $ispubladmin ) {
	$logtxt = "<a href=\"../admin/log.php?id=$ID\">".BizResources::localize("SHOW_LOG")."</a>";
}
$tpl = str_replace("<!--LOG-->", $logtxt, $tpl );
print HtmlDocument::buildDocument( $tpl, true );

function showMetaData( $tpl, $object )
{
	$objectmap = getObjectTypeMap();
	
	$basicMetaData    = $object->MetaData->BasicMetaData;
	$contentMetaData  = $object->MetaData->ContentMetaData;
	$sourceMetaData   = $object->MetaData->SourceMetaData;
	$rightsMetaData   = $object->MetaData->RightsMetaData;
	$workflowMetaData = $object->MetaData->WorkflowMetaData;

	$ID = $basicMetaData->ID;
	$type = $basicMetaData->Type;

	// Show regular object properties
	$tpl = str_replace( '<!--OBJID-->',   $basicMetaData->ID, $tpl );
	$tpl = str_replace( '<!--OBJTYPE-->', $objectmap[$basicMetaData->Type], $tpl );
	$tpl = str_replace( '<!--OBJNAME-->', formvar($basicMetaData->Name), $tpl );
	$tpl = str_replace( '<!--OBJPUB-->',  formvar($basicMetaData->Publication->Name), $tpl );
	$tpl = str_replace( '<!--OBJSEC-->',  formvar($basicMetaData->Category->Name), $tpl );
	$tpl = str_replace( '<!--OBJFORMAT-->', formvar($contentMetaData->Format), $tpl );

	$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
	$statusColor = $workflowMetaData->State->Id == -1 ? PERSONAL_STATE_COLOR : $workflowMetaData->State->Color;
	$statusColor = '<table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td bgColor="'.formvar($statusColor).'"></td></tr></table>';
	$statusColor = '<table style="border-collapse: collapse"><tr><td>'.$statusColor.'</td><td>&nbsp;</td><td>'.formvar($workflowMetaData->State->Name).'</td></tr></table>';
	$tpl = str_replace( '<!--OBJSTA-->', $statusColor, $tpl );
	$tpl = str_replace( '<!--OBJAUTHOR-->', formvar($sourceMetaData->Author), $tpl );
	$tpl = str_replace( '<!--OBJDEADLINE-->', formvar($workflowMetaData->Deadline), $tpl );
	
	$tpl = str_replace( '<!--OBJCREATOR-->', formvar($workflowMetaData->Creator), $tpl );
	$date_timeCreated = timeConverter($workflowMetaData->Created);
	$tpl = str_replace( '<!--OBJCREATED-->', formvar($date_timeCreated), $tpl );
	
	$tpl = str_replace( '<!--OBJCOPYRIGHT-->',formvar($rightsMetaData->Copyright), $tpl );
	$tpl = str_replace( '<!--OBJPRI-->',      formvar($workflowMetaData->Urgency), $tpl );
	$tpl = str_replace( '<!--OBJMODIFIER-->', formvar($workflowMetaData->Modifier), $tpl );
	
	$date_timeModified = timeConverter($workflowMetaData->Modified);
	$tpl = str_replace( '<!--OBJMODIFIED-->', formvar($date_timeModified), $tpl );
	
	if (empty($workflowMetaData->LockedBy)) {
		$noLock ='</td><td>';
		$tpl = str_replace( '<!--OBJLOCKED-->',$noLock, $tpl );
	} else {
		$sErrorMessage = BizResources::localize('ERR_UNLOCK');
		$ObjUnlock = '<img src="../../config/images/lock_16.gif">&nbsp;'.formvar($workflowMetaData->LockedBy).'</td>'.
				'<td onmouseUp="Question(\'info.php?id='.urlencode($ID).'&unlock=Unlock\', \''.$sErrorMessage.'\');" '.
					'onmouseOver="this.bgColor=\'#FF9342\';" onmouseOut="this.bgColor=\'#DDDDDD\';">'.
				'<img src="../../config/images/ulock_16.gif" border="0"> [ Unlock ]</a>';
		$tpl = str_replace( '<!--OBJLOCKED-->',$ObjUnlock, $tpl );
	}
	$tpl = str_replace( '<!--OBJROUTE-->', formvar($workflowMetaData->RouteTo), $tpl );
	
	if( !empty($contentMetaData->Slugline) ) {
		$tpl = str_replace( '<!--OBJSLUGLINE-->', formvar(disablehref($contentMetaData->Slugline)), $tpl );
	} else {
		$tpl = str_replace( '<!--OBJSLUGLINE-->', '', $tpl );
		$tpl = str_replace( 'SEC:OBJSLUGLINE>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:OBJSLUGLINE', '', $tpl );
	}

	if( !empty($contentMetaData->PlainContent) ) {
		$tpl = str_replace( '<!--PLAINCONTENT-->', formvar(disablehref($contentMetaData->PlainContent)), $tpl );
	} else {
		$tpl = str_replace( '<!--PLAINCONTENT-->', '', $tpl );
		$tpl = str_replace( 'SEC:PLAINCONTENT>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:PLAINCONTENT', '', $tpl );
	}

	if( !empty($contentMetaData->Description) ) {
		$tpl = str_replace( '<!--DESCRIPTION-->', formvar(disablehref($contentMetaData->Description)), $tpl );
	} else {
		$tpl = str_replace( '<!--DESCRIPTION-->', '', $tpl );
		$tpl = str_replace( 'SEC:DESCRIPTION>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:DESCRIPTION', '', $tpl );
	}

	if( !empty($workflowMetaData->Comment) ) {
		$tpl = str_replace( '<!--OBJCOMMENT-->', formvar(disablehref($workflowMetaData->Comment)), $tpl );
	} else {
		$tpl = str_replace( '<!--OBJCOMMENT-->', '', $tpl );
		$tpl = str_replace( 'SEC:OBJCOMMENT>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:OBJCOMMENT', '', $tpl );
	}

	$keywords = trim(implode( ' ', $contentMetaData->Keywords )); // sometimes there is an array with one empty keyword
	if( !empty($keywords) ) {
		$tpl = str_replace( '<!--OBJKEYWORD-->', formvar( $keywords ), $tpl );
	} else {
		$tpl = str_replace( '<!--OBJKEYWORD-->', '', $tpl );
		$tpl = str_replace( 'SEC:OBJKEYWORD>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:OBJKEYWORD', '', $tpl );
	}

	if( !empty($contentMetaData->HighResFile) ) {
		$tpl = str_replace( '<!--OBJHIGHRESFILE-->', formvar($contentMetaData->HighResFile), $tpl );
	} else {
		$tpl = str_replace( '<!--OBJHIGHRESFILE-->', '', $tpl );
		$tpl = str_replace( 'SEC:OBJHIGHRESFILE>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:OBJHIGHRESFILE', '', $tpl );
	}

	// deadline info
	$Deadline = isset($workflowMetaData->Deadline) && !is_null($workflowMetaData->Deadline) ? trim($workflowMetaData->Deadline) : '';
	$DeadlineSoft = isset($workflowMetaData->DeadlineSoft) && !is_null($workflowMetaData->DeadlineSoft) ? trim($workflowMetaData->DeadlineSoft) : '';
	if ( !empty($DeadlineSoft) || !empty($Deadline)) {
		$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
		$deadlinetxt = '';
		$now = date ("Y-m-d\\TH:i:s");
		$color = '#00ff00'; // green
		if( !empty($DeadlineSoft) && $now > $DeadlineSoft ) {
			$color = '#ffff00'; // orange
		}
		if( !empty($Deadline) && $now > $Deadline ) {
			$color = '#ff0000'; // red
		}
		$deadlinetxt .= 
			'<tr bgcolor="#DDDDDD">'.
				'<td>'.
					'<table border="1" style="border-collapse: collapse" bordercolor="#606060" '.
							'height="'.$boxSize.'" width="'.$boxSize.'">'.
						'<tr><td bgColor="'.$color.'"></td></tr>'.
					'</table>'.
				'</td>'.
				'<td>'.formvar(DateTimeFunctions::iso2date( $Deadline )).'</td>'.
				'<td>'.formvar(DateTimeFunctions::iso2date( $DeadlineSoft )).'</td>'.
				'<td></td>'.
			'</tr>'.PHP_EOL;
		$tpl = str_replace ("<!--DEADLINES-->",$deadlinetxt, $tpl );
	} else {
		$tpl = str_replace( '<!--DEADLINES-->', '', $tpl );
		$tpl = str_replace( 'SEC:DEADLINES>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:DEADLINES', '', $tpl );
	}

	// Show object type specific properties
	
	// Pre-translate the OBJ_SPECIFIC_INFORMATION key to fill in the user name
	$msg = BizResources::localize( 'OBJ_SPECIFIC_INFORMATION' );
	$msg = str_replace( '%', $objectmap[$type], $msg );
	$tpl = str_replace( '<!--RES:OBJ_SPECIFIC_INFORMATION-->', $msg, $tpl );
	
	$elRows = '';
	if( $type == 'Image' || $type == 'Advert' || $type == 'AdvertTemplate')
	{
		$tpl = str_replace( '<!--OBJ_SI_NAME_1-->', BizResources::localize('OBJ_THUMBNAIL'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_2-->', BizResources::localize('OBJ_VERSION'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_3-->', BizResources::localize('OBJ_DPI'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_4-->', BizResources::localize('OBJ_WIDTH'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_5-->', BizResources::localize('OBJ_HEIGHT'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_6-->', BizResources::localize('OBJ_SIZE'), $tpl );
	
		$OjbThumb = "<a href=javascript:popUpThumb('thumbnail.php?id=$ID&rendition=preview')><img src=\"image.php?id=$ID&rendition=thumb\" border=0></a>";
		$tpl = str_replace( '<!--OBJ_SI_VALUE_1-->',$OjbThumb, $tpl );
	
		// Pre-translate the OBJ_SHOW_TYPE_VERSIONS key to fill in the object type name
		$msg = BizResources::localize( 'OBJ_SHOW_TYPE_VERSIONS' );
		$msg = str_replace( '%', $objectmap[$type], $msg );
	
		$OjbVersion = '<a href="javascript:popUp(\'versions.php?id='.urlencode($ID).'&type='.$type.'\')">'.$msg.'</a>';
		$tpl = str_replace( '<!--OBJ_SI_VALUE_2-->',$OjbVersion, $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_3-->',formvar($contentMetaData->Dpi).' '.BizResources::localize('OBJ_DPI'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_4-->',formvar($contentMetaData->Width), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_5-->',formvar($contentMetaData->Height), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_6-->',formvar(calculateSize($contentMetaData->FileSize)), $tpl );
	} 
	elseif( $type == 'Layout' || $type == 'LayoutTemplate' || $type == 'Library') 
	{
		$tpl = str_replace( '<!--OBJ_SI_NAME_1-->', BizResources::localize('OBJ_VERSIONS'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_2-->', BizResources::localize('OBJ_SIZE'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_3-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_4-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_5-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_6-->','', $tpl );
	
		// Determine first and last page order
		$firstPag = $lastPag = -1;
		if( isset($object->Pages) ) foreach( $object->Pages as $page ) {
			if( $page->PageOrder < $firstPag || $firstPag == -1 ) {
				$firstPag = $page->PageOrder;
			}
			if( $page->PageOrder > $lastPag || $lastPag == -1 ) {
				$lastPag = $page->PageOrder;
			}
		}
	
		// Pre-translate the OBJ_SHOW_TYPE_VERSIONS key to fill in the object type name
		$msg = BizResources::localize( 'OBJ_SHOW_TYPE_VERSIONS' );
		$msg = str_replace( '%', $objectmap[$type], $msg );
	
		$OjbVersion = '<a href="javascript:popUp(\'versions.php?id='.urlencode($ID).'&type='.$type.'\')">'.$msg.'</a>';
		$tpl = str_replace( '<!--OBJ_SI_VALUE_1-->',$OjbVersion, $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_2-->',formvar(calculateSize($contentMetaData->FileSize)), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_3-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_4-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_5-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_6-->','', $tpl );
	}
	elseif ($type == 'Video' || $type == 'Audio')
	{
		$tpl = str_replace( '<!--OBJ_SI_NAME_1-->', BizResources::localize('OBJ_THUMBNAIL'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_2-->', BizResources::localize('OBJ_VERSION'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_3-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_4-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_5-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_6-->', BizResources::localize('OBJ_SIZE'), $tpl );
	
		$OjbThumb = "<a href=javascript:popUpThumb('thumbnail.php?id=$ID&rendition=preview')><img src=\"image.php?id=$ID&rendition=thumb\" border=0></a>";
		$tpl = str_replace ("<!--OBJ_SI_VALUE_1-->",$OjbThumb, $tpl );
	
		// Pre-translate the OBJ_SHOW_TYPE_VERSIONS key to fill in the object type name
		$msg = BizResources::localize( 'OBJ_SHOW_TYPE_VERSIONS' );
		$msg = str_replace( '%', $objectmap[$type], $msg );
	
		$OjbVersion = '<a href="javascript:popUp(\'versions.php?id='.urlencode($ID).'&type='.$type.'\')">'.$msg.'</a>';
		$tpl = str_replace( '<!--OBJ_SI_VALUE_2-->',$OjbVersion, $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_3-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_4-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_5-->','', $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_6-->',formvar(calculateSize($contentMetaData->FileSize)), $tpl );
	}
	elseif( $type == 'Article' || $type == 'ArticleTemplate' || $type == 'Spreadsheet' )
	{
		$tpl = str_replace( '<!--OBJ_SI_NAME_1-->', BizResources::localize('OBJ_ARTICLES'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_2-->', BizResources::localize('OBJ_VERSIONS'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_3-->', BizResources::localize('OBJ_LENGTHCHARS_ABR'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_4-->', BizResources::localize('OBJ_LENGTHWORDS_ABR'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_5-->', BizResources::localize('OBJ_LENGTHLINES_ABR'), $tpl );
		$tpl = str_replace( '<!--OBJ_SI_NAME_6-->', BizResources::localize('OBJ_LENGTHPARAS_ABR'), $tpl );
	
		$Articles = BizResources::localize('OBJ_ARTICLE').' '.BizResources::localize('OBJ_INFO');
		$tpl = str_replace( '<!--OBJ_SI_VALUE_1-->',$Articles, $tpl );
	
		// Pre-translate the OBJ_SHOW_TYPE_VERSIONS key to fill in the object type name
		$msg = BizResources::localize( 'OBJ_SHOW_TYPE_VERSIONS' );
		$msg = str_replace( '%', $objectmap[$type], $msg );
	
		$OjbVersion = '<a href="javascript:popUp(\'versions.php?id='.urlencode($ID).'&type='.$type.'\')">'.$msg.'</a>';
		$tpl = str_replace( '<!--OBJ_SI_VALUE_2-->',$OjbVersion, $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_3-->',$contentMetaData->LengthChars, $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_4-->',$contentMetaData->LengthWords, $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_5-->',$contentMetaData->LengthLines, $tpl );
		$tpl = str_replace( '<!--OBJ_SI_VALUE_6-->',$contentMetaData->LengthParas, $tpl );
	
		// Show elements:
		$elements = $object->Elements;
		if($elements) foreach($elements as $element){
			$elRows .= 
				'<tr bgcolor="#DDDDDD">'.
					'<td>'.formvar($element->Name).'</td>'.
					'<td>'.$element->LengthChars.'</td>'.
					'<td>'.$element->LengthWords.'</td>'.
					'<td>'.$element->LengthLines.'</td>'.
					'<td>'.$element->LengthParas.'</td>'.
					'<td>'.formvar($element->Version).'</td>'.
					'<td>'.formvar($element->ID).'</td>'.
				'</tr>'.
				'<tr bgcolor="#DDDDDD">'.
					'<td colspan="7"><i><b>'.BizResources::localize("OBJ_SNIPPET").'</b>: '.formvar($element->Snippet).'</i></td>'.
				'</tr>'.PHP_EOL;
		}
	} else {
		for( $ctr=1; $ctr<=6; $ctr++ ){
			$objName  = '<!--OBJ_SI_NAME_' . $ctr . '-->';
			$objValue = '<!--OBJ_SI_VALUE_' . $ctr . '-->';
			$tpl = str_replace( $objName, '', $tpl );
			$tpl = str_replace( $objValue, '', $tpl );
		}
		$tpl = str_replace( 'SEC:SPECIFICS>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:SPECIFICS', '', $tpl );
	}
	
	// Elements
	$tpl = str_replace ("<!--OBJELEMENTS-->",$elRows,$tpl);
	if( empty($elRows) ) {
		$tpl = str_replace( '<!--OBJELEMENTS-->', '', $tpl );
		$tpl = str_replace( 'SEC:OBJELEMENTS>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:OBJELEMENTS', '', $tpl );
	}
	
	return $tpl;
}

function showObjectTargets( $tpl, $object )
{
	if( $object->Targets ) {
		$targets = '';
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
		foreach( $object->Targets as $target ) {
			$pubId = DBChannel::getPublicationId( $target->PubChannel->Id );
			$pubName = DBPublication::getPublicationName( $pubId );
			$targets .= 
				'<tr bgcolor="#DDDDDD">'.
					'<td>'.formvar( $pubName ).'</td>'.
					'<td>'.formvar( $target->PubChannel->Name ).'</td>'.
					'<td>'.formvar( $target->Issue->Name ).'</td>'.
					'<td>'.formvar( arrayToNameString( $target->Editions ) ).'</td>'.
				'</tr>'.PHP_EOL;
		}
		$tpl = str_replace( '<!--TARGETS-->',  $targets, $tpl );
	} else {
		$tpl = str_replace( '<!--TARGETS-->', '', $tpl );
		$tpl = str_replace( 'SEC:TARGETS>>>-->', '', $tpl );
		$tpl = str_replace( '<!--<<<SEC:TARGETS', '', $tpl );
	}
	return $tpl;
}

function showObjectRelations( $ticket, $tpl, $object )
{
	require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
	require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';

	$ID = $object->MetaData->BasicMetaData->ID;
	$objectParents = '';
	$objectChilds = '';
	$objectPlacements = '';
	$objTypeMap = getObjectTypeMap();
	
	$relations = $object->Relations;
	$relTargets = '';
	if( $relations ) foreach( $relations as $relation ) {
		$the_Relation = ( $ID == $relation->Parent ) ? $relation->Child : $relation->Parent;
	
		try {
			require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
			$request = new WflGetObjectsRequest( $ticket, array($the_Relation), false, 'none', null );
			$service = new WflGetObjectsService();
			$response = $service->execute( $request );
			$objects = $response->Objects;
		} catch( BizException $e ) {
		}
	
		$relObjMD = $objects[0]->MetaData;
		$rel_name = $relObjMD->BasicMetaData->Name;
		$relObjType = $relObjMD->BasicMetaData->Type;
		$relObjStatusColor = $relObjMD->WorkflowMetaData->State->Id == -1 ? PERSONAL_STATE_COLOR : $relObjMD->WorkflowMetaData->State->Color;
		$rel_type = $relation->Type;
	
		$popup = "<td onmouseUp=\"popUp('info.php?id=$the_Relation');\">";
		$sErrorMessage = BizResources::localize("ERR_DEL_RELATION");
		$theRelType = ( $ID == $relation->Parent ) ? 'child' : 'parent';
		$objectRelation_Del = "<td onmouseUp=\"Question('info.php?id=$ID&$theRelType=$the_Relation&reltype=$rel_type', '".$sErrorMessage."');\">";
		$objectRelation_Del .= "<img src='../../config/images/trash_16.gif' title=\"".BizResources::localize('ACT_DEL_RELATION')."\" border=\"0\">";
	
		$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
		$typeIcon = BizObject::getTypeIcon( $relObjType );
		$objectRelation = "<tr bgcolor='#DDDDDD' onmouseOver=\"this.bgColor='#FF9342'\"; onmouseOut=\"this.bgColor='#DDDDDD'\">";
		$objectRelation .= $popup.'<img src="../../config/images/'.$typeIcon.'" border="0" title="'.$objTypeMap[$relObjType].'"/></td>';
		$objectRelation .= $popup.formvar($rel_name).'</td>'.$popup.$rel_type.'</td>';
		$objectRelation .= $popup.'<table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td bgColor="'.formvar($relObjStatusColor).'"></td></tr></table>';
		$objectRelation .= $popup.formvar($relObjMD->WorkflowMetaData->State->Name).'</td>';
		$objectRelation .= $popup.formvar($relObjMD->WorkflowMetaData->RouteTo).'</td>';
		$objectRelation .= $objectRelation_Del.'</td>';
		$objectRelation .= '</tr>'.PHP_EOL;

		if( $relation->Targets ) foreach( $relation->Targets as $target ) {
			$pubId = DBChannel::getPublicationId( $target->PubChannel->Id );
			$pubName = DBPublication::getPublicationName( $pubId );
			$relTargets .= 
				"<tr bgcolor='#DDDDDD' onmouseOver=\"this.bgColor='#FF9342'\"; onmouseOut=\"this.bgColor='#DDDDDD'\">".
					$popup.'<img src="../../config/images/'.$typeIcon.'" border="0" title="'.$objTypeMap[$relObjType].'"/></td>'.
					$popup.formvar( $rel_name ).'</td>'.
					$popup.formvar( $pubName ).'</td>'.
					$popup.formvar( $target->PubChannel->Name ).'</td>'.
					$popup.formvar( $target->Issue->Name ).'</td>'.
					$popup.formvar( arrayToNameString( $target->Editions ) ).'</td>'.
				'</tr>'.PHP_EOL;

		}

		if( $ID == $relation->Parent ) {
			$objectChilds .= $objectRelation;
		} else {
			$objectParents .= $objectRelation;
		}
		
		if(isset($relation->Placements) && count($relation->Placements) > 0 ) {
			if( $ID == $relation->Parent ) {
				$RL_Type = BizResources::localize("ACT_PLACED_ITEM") ." [ ".BizResources::localize('OBJ_CHILDREN')." ]";
			} else {
				$RL_Type = BizResources::localize("ACT_PLACED_ON") . " [ ".BizResources::localize('OBJ_PARENTS')." ]";
			}
			$tpl = str_replace ("<!--RELATION_TYPE-->",$RL_Type, $tpl );
			
			foreach ($relation->Placements as $rel_placement) {
				$objectPlacements .= "<tr bgcolor='#DDDDDD' onmouseOver=\"this.bgColor='#FF9342'\"; onmouseOut=\"this.bgColor='#DDDDDD'\">";
				$objectPlacements .= /*$popup.$the_Relation.'</td>'.*/$popup.formvar($rel_name).'</td>'.$popup.$rel_type.'</td>';
				// To do for v5, make layer and page into list
				$objectPlacements .= $popup.$rel_placement->Page.'</td>'.$popup.formvar($rel_placement->Layer).'</td>';
				$objectPlacements .= $popup.round($rel_placement->Left).'</td>'.$popup.round($rel_placement->Top).'</td>';
				$objectPlacements .= $popup.round($rel_placement->Width).'</td>'.$popup.round($rel_placement->Height).'</td>';
				$objectPlacements .= $popup.round($rel_placement->ContentDx).'</td>'.$popup.round($rel_placement->ContentDy).'</td>';
				$objectPlacements .= $popup.round($rel_placement->ScaleX*100,1).'%</td>'.$popup.round($rel_placement->ScaleY*100,1).'%</td>';
				$objectPlacements .= '</tr>'.PHP_EOL;
			}
		}
	}
	$tpl = str_replace ("<!--OBJPARENTS-->",$objectParents, $tpl );
	if( empty($objectParents) ) {
		$stt = strpos( $tpl, '<!--SEC:OBJPARENTS>>>-->' );
		$end = strpos( $tpl, '<!--<<<SEC:OBJPARENTS-->' ) + strlen('<!--<<<SEC:OBJPARENTS-->');
		$tpl = substr( $tpl, 0, $stt ) . substr( $tpl, $end );
	}
	$tpl = str_replace ("<!--OBJCHILDREN-->",$objectChilds, $tpl );
	if( empty($objectChilds) ) {
		$stt = strpos( $tpl, '<!--SEC:OBJCHILDREN>>>-->' );
		$end = strpos( $tpl, '<!--<<<SEC:OBJCHILDREN-->' ) + strlen('<!--<<<SEC:OBJCHILDREN-->');
		$tpl = substr( $tpl, 0, $stt ) . substr( $tpl, $end );
	}
	$tpl = str_replace ("<!--OBJPLACEMENTS-->",$objectPlacements, $tpl );
	if( empty($objectPlacements) ) {
		$stt = strpos( $tpl, '<!--SEC:OBJPLACEMENTS>>>-->' );
		$end = strpos( $tpl, '<!--<<<SEC:OBJPLACEMENTS-->' ) + strlen('<!--<<<SEC:OBJPLACEMENTS-->');
		$tpl = substr( $tpl, 0, $stt ) . substr( $tpl, $end );
	}
	$tpl = str_replace( '<!--RELTARGETS-->',  $relTargets, $tpl );
	if( empty($relTargets) ) {
		$stt = strpos( $tpl, '<!--SEC:RELTARGETS>>>-->' );
		$end = strpos( $tpl, '<!--<<<SEC:RELTARGETS-->' ) + strlen('<!--<<<SEC:RELTARGETS-->');
		$tpl = substr( $tpl, 0, $stt ) . substr( $tpl, $end );
	}
	return $tpl;
}

function calculateSize( $size )
{
	$sizes = Array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
	$ext = $sizes[0];
	for ($i=1; (($i < count($sizes)) && ($size >= 1024)); $i++) {
		$size = $size / 1024;
		$ext  = $sizes[$i];
	}
	return round($size, 2) . " " . $ext;
}

function disablehref( $str )
{
	$str = str_replace('href=', '', $str);
	$str = str_replace('<a', '', $str);
	$str = str_replace('/a>', '', $str);
	return $str;
}

function timeConverter( $val )
{
	$val_array = preg_split('/[T]/', $val);
	$date_array = preg_split('/[-]/', $val_array['0']);
	$date_formated = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];
	return $date_formated . " " . $val_array['1'];
}

/**
 * Get a comma separated name string from array of objects
 *
 * @param $elements array   	Array (non-SOAP) of stdClass objects with name field
 * @return string				Comma separated string of all names
 */
function arrayToNameString( $elements )
{
	$returnString = '';
	$komma = '';
	
	if( $elements ) foreach( $elements as $element ) {
		$returnString .= $komma.$element->Name;
		$komma = ', ';
	}
	return $returnString;
}
