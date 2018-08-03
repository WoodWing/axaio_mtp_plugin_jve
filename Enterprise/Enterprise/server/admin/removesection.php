<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBSection.class.php';

checkSecure('publadmin');
$tpl = HtmlDocument::loadTemplate( 'removesection.htm' );
$objectmap = getObjectTypeMap();

// Retrieve value of the combo boxes
$inPub     = isset($_REQUEST['Publication']) ? intval($_REQUEST['Publication']) : 0;
$inSection = isset($_REQUEST['Section'])  ? intval($_REQUEST['Section']) : 0;
$inObjType = isset($_REQUEST['ObjType']) ? $_REQUEST['ObjType'] : '';
$del = isset($_POST['del']) ? (bool)$_POST['del'] : false;

$sectionInfo = DBSection::getSectionObj($inSection);
$inIssue = !empty($sectionInfo->IssueId) ? $sectionInfo->IssueId : null;

$dum = null;
cookie( 'removeSection2', $inPub == '', $inPub, $inObjType, $dum, $dum, $dum, $dum, $dum );

// Re-validate data retrieved from cookie! (XSS attacks)
$inPub = intval($inPub); 
$inSection = intval($inSection); 
$inObjType = array_key_exists($inObjType, $objectmap) ? $inObjType : '';

//
/////////////////////// *** Delete objects *** ///////////////////////
//

$succeed = true;
$message = "";

if ($del === true) {
	$newSection = array();
	$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
	
	$moveObjIds = array();
	for ($i = 0; $i < $amount; $i++) {
		$value = $_POST['radio'.$i]; 
		if ($value == 'delete') {
			$deleteObjIds[] = $_POST['objectID'.$i]; // can be alien id (=string!)
		} else {
			$moveObjIds[] = $_POST['objectID'.$i]; // can be alien id (=string!)
			$newSection[] = intval($_POST['newSection'.$i]);
		}
	}
	
	// Move objects to different Section
	if ($moveObjIds) foreach( $moveObjIds as $counter => $id) {
		try {
			$req = new WflGetObjectsRequest( BizSession::getTicket(), array($id), false, 'none', null );
			$service = new WflGetObjectsService();
			$getObjectsResp = $service->execute( $req );
			$objects = $getObjectsResp->Objects;
		} catch( BizException $e ) {
			$succeed = false;
			$message = $e->getMessage();
		}
		if( $objects ) foreach( $objects as $object ) {
			$id = $object->MetaData->BasicMetaData->ID;
			$object->MetaData->BasicMetaData->Category = new Category( $newSection[$counter] );
			try {
				require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
				$req = new WflSetObjectPropertiesRequest( BizSession::getTicket(), $id, $object->MetaData, $object->Targets );
				$service = new WflSetObjectPropertiesService();
				$service->execute( $req );
			} catch( BizException $e ) {
				$succeed = false;
				$message = $e->getMessage();
			}
		}
	}
	
	// Delete all checked objects
	if( $amount > 0 && isset($deleteObjIds) ) {
		try {
			require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
			$service = new WflDeleteObjectsService();

			$deleteObjectsReq = new WflDeleteObjectsRequest();
			$deleteObjectsReq->Ticket = BizSession::getTicket();
			$deleteObjectsReq->IDs = $deleteObjIds;
			$deleteObjectsReq->Permanent = true;

			$resp = $service->execute( $deleteObjectsReq );
			if( !$resp->Reports ) { // Introduced since v8.0
				$succeed = true;
			} else {
				$succeed = false;
				$message = '';
				foreach( $resp->Reports as $report ){
					foreach( $report->Entries as $reportEntry ) {
						$message .= $reportEntry->Message . PHP_EOL;
					}					
				}			
			}	
			
		} catch( BizException $e ) {
			$succeed = false;
			$message = $e->getMessage();
		}
	}
	
	// Delete Section
	$sectionID = isset($_POST['sectionID']) ? intval($_POST['sectionID']) : 0;
	
	// query objects from publ/section
	$queryParams = array();
	$queryParams[] = new QueryParam ('PublicationId', '=', $inPub );
	$queryParams[] = new QueryParam ('SectionId', '=', $inSection );

	try {
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$service = new WflQueryObjectsService();
		$result = $service->execute( new WflQueryObjectsRequest( BizSession::getTicket(), $queryParams,
									null, null, null, null, 
									reqPropsForRemoveSection(), null ) );
		$maxnum_results = sizeof($result->Rows);
		if( $sectionID && ($maxnum_results == 0) ) {
			require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
			$service = new AdmDeleteSectionsService();
			$request = new AdmDeleteSectionsRequest( BizSession::getTicket(), $inPub, $inIssue, array( $sectionID ) );
			$service->execute( $request );
		}
	} catch( BizException $e ) {
		$succeed = false;
		$message = $e->getMessage();
	}
}

$err = '';
if( !$succeed ) {
	$err = "onLoad='javaScript:Message(\"$message\")'";
}
//
/////////////////////// *** Publication combo *** ///////////////////////
//
$comboBoxPub = '<select name="Publication" style="width:150px" onchange="submit();">';
$comboBoxPub .= '<option></option>';	

require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
$pubs = BizPublication::getPublications( BizSession::getShortUserName() );
foreach( $pubs as $pub ) {
	if( $pub->Id != $inPub ) {
		$comboBoxPub .= '<option value="'.$pub->Id.'">'.formvar($pub->Name).'</option>';
	} else {
		$comboBoxPub .= '<option value="'.$pub->Id.'" selected="selected">'.formvar($pub->Name).'</option>';
	}
}
$comboBoxPub .= '</select>';
$tpl = str_replace ('<!--COMBOPUB-->',$comboBoxPub, $tpl);

//
/////////////////////// *** Section combo *** ///////////////////////
//
$comboBoxSect = '<select name="Section" style="width:150px" onchange="submit();">';
$comboBoxSect .= '<option></option>';	

// comboBoxNewSection used later on to select new section for objects that are moved
$comboBoxNewSection = "<option selected></option>";
$sections = BizPublication::getSections( BizSession::getShortUserName(), $inPub, $inIssue, 'flat', true );
foreach( $sections as $section ) {
	if( $section->Id != $inSection ) {
		$comboBoxSect .= '<option value="'.$section->Id.'">'.formvar($section->Name).'</option>';
		$comboBoxNewSection .= '<option value="'.$section->Id.'">'.formvar($section->Name).'</option>';
	} else {
		$comboBoxSect .= '<option value="'.$section->Id.'" selected="selected">'.formvar($section->Name).'</option>';
	}
}
$comboBoxSect .= '</select>';
$tpl = str_replace ('<!--COMBOSECT-->',$comboBoxSect, $tpl);

//
/////////////////////// *** Object Type combo *** ///////////////////////
//
$comboBoxObjType = '<select name="ObjType" style="width:150px" onchange="submit();">';
$comboBoxObjType .= '<option></option>';	
$objTypes = getObjectTypeMap();
asort($objTypes);
foreach( $objTypes as $objTypeKey => $objTypeDisplay ) {
	if( $objTypeKey != $inObjType ) {
		$comboBoxObjType .= '<option value="'.$objTypeKey.'">'.formvar($objTypeDisplay).'</option>';
	} else {
		$comboBoxObjType .= '<option value="'.$objTypeKey.'" selected="selected">'.formvar($objTypeDisplay).'</option>';
	}
}
$comboBoxObjType .= '</select>';
$tpl = str_replace ('<!--COMBOOBJTYPE-->',$comboBoxObjType, $tpl);

	
if( $inPub && $inSection ) {
	$txt = showfiles( $inPub, $inSection, $inObjType, $comboBoxNewSection );
	$tpl = str_replace ("<!--CONTENT-->", $txt, $tpl);
}

	
print HtmlDocument::buildDocument($tpl, true, $err);

function showfiles( $publ, $section, $objType, $comboBoxNewSection )
{
	$objTypes = getObjectTypeMap();

	// Query objects from publ/section
	$queryParams = array();
	$queryParams[] = new QueryParam ('PublicationId', '=', $publ);
	$queryParams[] = new QueryParam ('SectionId', '=', $section);
	if ($objType) $queryParams[] = new QueryParam ('Type', '=', $objType);

	try {
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$service = new WflQueryObjectsService();
		$resp = $service->execute( new WflQueryObjectsRequest( BizSession::getTicket(), $queryParams,
									null, null, null, null,
									reqPropsForRemoveSection(), null ) );
	} catch( BizException $e ) {
		return '<font color="red">'.$e->getMessage().'</font>';
	}

	// Show table header
	$txt = '<form name="content" method="post" action="removesection.php" enctype="multipart/form-data">
			<table class="listpane">
				<tr>
					<th style="width:25px">'.BizResources::localize('ACT_DELETE').'</th>
					<th style="width:25px">'.BizResources::localize('ACT_MOVE').'</th>
					<th style="width:125px"></th>
					<th style="width:75px">'.BizResources::localize('OBJ_TYPE2').'</th>
					<th style="width:125px">'.BizResources::localize('OBJ_NAME').'</th>
					<th style="width:100px">'.BizResources::localize('ACT_PLACED_ON').'</th>
					<th style="width:100px">'.BizResources::localize('OBJ_LOCKED_BY').'</th>
					<th style="width:75px">'.BizResources::localize('OBJ_SIZE').'</th>
				</tr>';
	
	// Show object as rows in table
	$rowIdx = 0;
	$colIndexes = queryObjectsColumnIndexes( reqPropsForRemoveSection(), $resp->Columns );
	if( $resp->Rows ) foreach( $resp->Rows as $row ) {
		$objId    = $row[$colIndexes['ID']];
		$placedOn = $row[$colIndexes['PlacedOn']];
		$lockedBy = $row[$colIndexes['LockedBy']];
		$objType  = $row[$colIndexes['Type']];
		$objName  = $row[$colIndexes['Name']];
		$fileSize = $row[$colIndexes['FileSize']];

		$txt .= '<tr bgcolor="#DDDDDD" onmouseOver="this.bgColor=\'#FF9342\';" onmouseOut="this.bgColor=\'#DDDDDD\';">
					<td align="center">'.
						inputvar( 'objectID'.$rowIdx, $objId , 'hidden' ).
						inputvar( 'placed'.$rowIdx, checkPlaced($placedOn ) , 'hidden' ).
						'<input type="radio" name="radio'.$rowIdx.'" value="delete" checked="checked" onclick="javascript:ComboBoxChanger(\''.$rowIdx.'\')"';
						if (trim($lockedBy) != '') $txt .= ' disabled="disabled" '; 
						$txt .= '>
					</td>
					<td align="center"><input type="radio" name="radio'.$rowIdx.'" value="move" onclick="javascript:ComboBoxChanger(\''.$rowIdx.'\')"';
					if (trim($lockedBy) != '') $txt .= ' disabled="disabled" '; 
					$txt.= '></td>
					<td>
						<select name="newSection'.$rowIdx.'" style="width:100px" disabled="disabled">'.$comboBoxNewSection.'</select>
					</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $objId . '\');">' . formvar($objTypes[$objType])  . '</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $objId . '\');">' . formvar($objName)  . '</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $objId . '\');">' . formvar($placedOn) . '</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $objId . '\');"><font color="red">' . formvar($lockedBy) . '</font></td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $objId . '\');">' . formvar(calculateSize( $fileSize )) . '</td> 
				</tr>';
		$rowIdx++;
	}
	
	// Add hidden form params
	$sErrorMessage = BizResources::localize('ERR_DELETE_SECTION');
	$txt .= '<tr class="listbtnbar">
				<td colspan="9" align="right">
					<br/>
					<input type="submit" value="'.BizResources::localize('ACT_REFRESH').'"/>
					<input type="button" value="'.BizResources::localize('ACT_CLEAR').'" onclick="javascript:AreYouSure(\''. $sErrorMessage . '\')"/>'.
					inputvar( 'amount', $rowIdx, 'hidden' ).
					inputvar( 'sectionID', $section, 'hidden' ).
					inputvar( 'del', '', 'hidden' ).
				'</td>
			</tr></table></form>';
	return $txt;
}

function checkPlaced( $placed ) 
{
	if ($placed != "") {
		return "1";
	} else {
		return "0";
	}
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

/**
 * Gives the collection of properties to use for QueryObjects
 * to be able to show overview of objects in this application.
 *
 * @return array of property names
 */
function reqPropsForRemoveSection()
{
	return array( 'ID', 'Name', 'Type', 
					'PublicationId', 'StateId', 'SectionId', 
					'PlacedOn', 'LockedBy', 'FileSize' );
}

/**
 * Determines the index of all requested columns passed to QueryObjects.
 * This is usedful for looking up property values at returned row values.
 *
 * @param array $reqCols List of requested object property names.
 * @param array $respCols List of returned columns from QueryObjects.
 * @return array indexes; keys = property names, values = column indexes
 */
function queryObjectsColumnIndexes( $reqCols, $respCols )
{
	$indexedCols = array();
	
	// Mark requested column indexes as untreated
	if( $reqCols ) foreach( $reqCols as $colName ) {
		$indexedCols[$colName] = -1;
	}
	
	// Determine column index of columns we've asked for
	if( $respCols ) foreach( $respCols as $key => $col ) {
		$indexedCols[$col->Name] = $key;
	}
	
	// Debug: Paranoid check if there is any requested column missing
	if( LogHandler::debugMode() ) {
		foreach( $reqCols as $colName ) {
			if( !isset($indexedCols[$colName]) || $indexedCols[$colName] == -1 ) {
				echo 'Fatal error: Required column not returned by QueryObjects service: '.formvar($colName).'<br/>';
			}
		}
	}
	return $indexedCols;
}
