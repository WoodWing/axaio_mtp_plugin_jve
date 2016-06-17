<?php
/**
 * @package 	Enterprise
 * @subpackage 	Apps
 * @since 		v5.0
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
$tpl = HtmlDocument::loadTemplate( 'export_pdf.htm' );

$inPub     = isset($_POST['Publication']) ? intval($_POST['Publication']) : 0;
$inIssue   = isset($_POST['Issue'])       ? intval($_POST['Issue']) : 0;
$inEdition = isset($_POST['Edition'])     ? intval($_POST['Edition']) : 0; // BZ# 5455

$dum = null;
cookie("exportPDF", $inPub == '', $inPub, $inIssue, $inEdition, $dum, $dum, $dum, $dum); // BZ# 5455

// use of cookie is insecure, parse the same variables as at the beginning
$inPub = intval( $inPub );
$inIssue = intval( $inIssue );
$inEdition = intval( $inEdition );

/////////////////////// *** Download selected PDF(s) *** ///////////////////////
$objectID = 0;
$pageNr = "";
$txt = "";
$message = "";

$amount = isset($_POST["amount"]) ? intval($_POST["amount"]) : 0;
if ($amount > 0) {
	$sErrorMessage = BizResources::localize("ERR_SUCCES");
	$txt .= "<font class='text'>" . $sErrorMessage . "<ul>";
	for ($i = 0; $i < $amount; $i++) {
		if (isset($_POST["checkbox".$i])) {
			$layoutName = '';
			$pageInfo = $_POST["pageInfo".$i];
			$pageInfo = explode( '-', $pageInfo);
			$objectID = intval( $pageInfo[0] ); // special service, no alien objects supported, so intval is allowed
			$pageNr = $pageInfo[1];
			$pageOrd = intval( $pageInfo[2] );
			$message = downloadPDF($ticket, $objectID, $pageNr, $pageOrd, $inEdition, $layoutName);
			$txt .= "<li>" . $layoutName . " - page : " . formvar($pageNr) . "</li>";
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

// Fill in Publications / Issues / Editions combo's
ExportUtils::fillCombos( $inPub, $inIssue, $inEdition, $tpl ); // BZ# 5455

// Fill in Publications / Issues / Editions combo's
if ( $inPub && $inIssue ) {
	require_once BASEDIR."/server/bizclasses/BizPublication.class.php";
	$editions =  BizPublication::getEditions( $inPub, $inIssue );
	if ( $editions ) {
		if ( $inEdition == 0 ) {
			$inEdition = $editions[0]->Id; // Pick the first one (if editions are used, it is mandatory to pass one).
		}
	} else {
		$inEdition = null;
	}
}

 /////////////////////// *** Stats *** ///////////////////////
// BZ#5455
$objCount = 0;
if ($inPub && $inIssue) {
	$txt .= showfiles($ticket, $inPub, $inIssue, $inEdition, $objCount);
} else {
	$txt .= BizResources::localize("ERR_NONE_AVAILABLE") . '<br/><br/>';
}

$tpl = str_replace ("<!--EXPORTCONTENT-->", $txt, $tpl);
$tpl = str_replace ("<!--EXPORTTITLE-->", BizResources::localize('HEA_EXPORT_PDF'), $tpl);
$tpl = str_replace ("<!--EXPORTPAGE-->", "exportPDF.php", $tpl);
$tpl = str_replace( '<!--PAR:AMOUNT-->', $objCount, $tpl );
print HtmlDocument::buildDocument($tpl);

function showfiles($ticket, $publ, $issue, $edition, &$ix )
{
	// query all layout object Id from issue
	$objectIds = getObjectIds( $ticket, $issue, $edition );

	// Get all the objects pages
	$objectPages = getObjectsPages( $ticket, $objectIds, $edition );

	global $globUser;
	require_once BASEDIR.'/server/bizclasses/PubMgr.class.php';
	$states = PubMgr::getStates($globUser, $publ, $issue, 'Layout', false);

	// get some information about images
	$objects = array();
	if( $objectPages ) foreach( $objectPages as $objectPage ) {
		// get id and state info from layout
		$id = $objectPage->MetaData->BasicMetaData->ID;
		$name = $objectPage->MetaData->BasicMetaData->Name;
		$sid =  $objectPage->MetaData->WorkflowMetaData->State->Id;
		$stinfo = null;
		foreach( $states as $state ) {
			if( $state->Id == $sid ) {
				$stinfo = $state;
			}
		}
		// get each pages
		if( isset($objectPage->Pages) ) foreach ( $objectPage->Pages as $page ) {
			$pnr = $page->PageNumber;
			$lengthdata = 0;
			if( !empty( $page->Files ) ) {
				$lengthdata = filesize($page->Files[0]->FilePath);
			}
			if( $lengthdata ) {
				$pgsize = NumberUtils::getByteString( $lengthdata );
				$objects[] = array('id' => $id, 'pageNumber' => $pnr, 'page' => $page, 
									'state' => $stinfo, 'name' => $name, 'size' => $pgsize );
			}
		}
	}

	if( count($objects) == 0 ) {
		$txt = BizResources::localize("ERR_NONE_AVAILABLE");
		return $txt;
	}

	array_qsort($objects, "pageNumber");

	// show rows
	$txt = '';

	// display pages in table
	$boxSize = 13;
	for ($ix = 0; $ix < count($objects); $ix++) {
		$clr = formvar('#'.$objects[$ix]['state']->Color);
		$txt .= '<tr bgcolor="#DDDDDD" onmouseOver="this.bgColor=\'#FF9342\';" onmouseOut="this.bgColor=\'#DDDDDD\';">'."\r\n".
					'<td align="center">'."\r\n".
						'<input id="checkbox'.$ix.'" name="checkbox'.$ix.'" type="checkbox" checked="checked"/>'."\r\n".
						'<input type="hidden" name="pageInfo'.$ix.'" value="'."\r\n".
																		formvar($objects[$ix]['id']).
																	'-'.formvar($objects[$ix]['page']->PageNumber).
																	'-'.formvar($objects[$ix]['page']->PageOrder).'"/>'."\r\n".
					'</td>'."\r\n".
					'<td onmouseUp="popUp(\'../apps/info.php?id=' . urlencode($objects[$ix]['id']) . '\');">' . formvar($objects[$ix]['page']->PageNumber) . '</td>'."\r\n".
					'<td onmouseUp="popUp(\'../apps/info.php?id=' . urlencode($objects[$ix]['id']) . '\');">' . formvar($objects[$ix]['name']) . '</td>'."\r\n".
					'<td onmouseUp="popUp(\'../apps/info.php?id=' . urlencode($objects[$ix]['id']) . '\');">'."\r\n".
						'<table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td bgColor="'.$clr.'"></td></tr></table>'."\r\n".
					'</td>'."\r\n".
					'<td nowrap="nowrap" onmouseUp="popUp(\'../apps/info.php?id=' . urlencode($objects[$ix]['id']) . '\');">'. formvar($objects[$ix]['state']->Name) . '</td>'."\r\n".
					'<td align="right" onmouseUp="popUp(\'../apps/info.php?id=' . urlencode($objects[$ix]['id']) . '\');">' . formvar($objects[$ix]['size']) . '</td>'."\r\n".
				'</tr>'."\r\n";
	}
	return $txt;
}

function getPDF( $ticket, $id, $pageOrd, $edition, &$pubName, &$sectionName, &$layoutName )
{
	static $collectedResponses = array();
	$resp = null;

	if ( isset( $collectedResponses[ $id] ) ) {
		if ( $edition > 0 ) {
			$resp = isset( $collectedResponses[$id][$edition] ) ? $collectedResponses[$id][$edition] : null;
		} else {
			$resp = isset( $collectedResponses[$id][0] ) ? $collectedResponses[$id][0] : null;
		}
	}

	if ( is_null( $resp ) ) {
		try {
			require_once BASEDIR . '/server/services/wfl/WflGetPagesService.class.php';
			require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
			$req = new WflGetPagesRequest();
			$req->Ticket = $ticket;
			$req->IDs = array($id);
			$req->Edition = DBEdition::getEdition( $edition ); // Returns null if not found
			$req->Renditions = array('output');

			$service = new WflGetPagesService();
			$resp = $service->execute( $req );
			if ( $edition > 0 ) {
				$collectedResponses[$id][$edition] = $resp;
			} else {
				$collectedResponses[$id][0] = $resp;
			}
		} catch ( BizException $e ) {
			$e = $e; // keep analyzer happy
		}
	}

	if ( $resp ) {
		$objectPage = $resp->ObjectPageInfos[0];
		$pubName = $objectPage->MetaData->BasicMetaData->Publication->Name;
		$sectionName = $objectPage->MetaData->BasicMetaData->Category->Name;
		$layoutName = $objectPage->MetaData->BasicMetaData->Name;
		if ( $objectPage->Pages ) foreach ( $objectPage->Pages as $page ) {
			if ( $page->PageOrder == $pageOrd ) {
				return $page->Files[0]->FilePath;
			}
		}
	}

	return null;
}

function array_qsort (&$array, $column = 0, $order = SORT_ASC, $first = 0, $last = -2) {
	// $array  - the array to be sorted
	// $column - index (column) on which to sort
	//          can be a string if using an associative array
	// $order  - SORT_ASC (default) for ascending or SORT_DESC for descending
	// $first  - start index (row) for partial array sort
	// $last  - stop  index (row) for partial array sort

	if($last == -2) $last = count($array) - 1;
	if($last > $first) {
		$alpha = $first;
		$omega = $last;
		$guess = $array[$alpha][$column];
		while($omega >= $alpha) {
			if($order == SORT_ASC) {
				while($array[$alpha][$column] < $guess) $alpha++;
				while($array[$omega][$column] > $guess) $omega--;
			} else {
				while($array[$alpha][$column] > $guess) $alpha++;
				while($array[$omega][$column] < $guess) $omega--;
			}
			if($alpha > $omega) break;
			$temporary = $array[$alpha];
			$array[$alpha++] = $array[$omega];
			$array[$omega--] = $temporary;
		}
		array_qsort ($array, $column, $order, $first, $omega);
		array_qsort ($array, $column, $order, $alpha, $last);
	}
}

function downloadPDF( $ticket, $objectID, $pageNr, $pageOrd, $edition, &$layoutName )
{
	// Retrieve PDF data
	$pubName = '';
	$sectionName = '';
	$editionName = '';
	if( $edition ) { // when edition is not 0, get edition name
		require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
		$editionObj = DBEdition::getEdition( $edition );
		$editionName = $editionObj->Name;
	}
	$filePath = getPDF( $ticket, $objectID, $pageOrd, $edition, $pubName, $sectionName, $layoutName );

	// Create file and put value(=PDF data) into file
	// if file allready exists then it is over-writen
	if (EXPORTDIRECTORY != "") {
		if( !is_dir(EXPORTDIRECTORY) ) {
			mkdir(EXPORTDIRECTORY);
		}

		// Page Number, Brand Name and Category Name are human readable. It can contain any characters 
		// including ones that are not allowed at file systems.
		// For example, Page Number might contain user typed page section prefixes (typed in InDesign).
		// The replaceDangerousChars function remove chars that have special meaning for Win/Mac/Linux 
		// file systems just for the generated output PDF file to avoid file creation/write problems.

		// BZ#4906: The Page Number is always added to the filename, fixing the error.
		// This also constitutes change in behavior though as it is visible to everybody all the time.

		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$exportname = $pubName;
		if( !empty($editionName) ) {
			$exportname .= '-' . $editionName;
		}
		$exportname .= '-' .$sectionName . '-' . $layoutName;
		$exportfilename = EXPORTDIRECTORY . FolderUtils::replaceDangerousChars( $exportname . "-" . $pageNr );
		$exportfilename = FolderUtils::encodePath( $exportfilename );

		// Write PDF file
		if ( copy( $filePath,  $exportfilename . '.pdf' )) {
			return ''; // OK !
		} else {
			$sErrorMessage = BizResources::localize("ERR_EXPORT_FOLDER_EXISTS");
			return $sErrorMessage . " " . EXPORTDIRECTORY;
		}
	}
	return BizResources::localize("ERR_EXPORT_FAILURE");
}

function getObjectIds( $ticket, $issueId = null, $edition ) {

	$objectIds = array();
	if( $issueId ) {
		$queryParams = array();
		$queryParams[] = new QueryParam( 'IssueId', '=', $issueId );
		$queryParams[] = new QueryParam( 'Type', '=', 'Layout' );
		if ( $edition > 0 ) {
			$queryParams[] = new QueryParam( 'EditionId', '=', $edition );
		}

		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req = new WflQueryObjectsRequest();
		$req->Ticket = $ticket;
		$req->Params = $queryParams;
		$req->FirstEntry = 1;
		$req->MaxEntries = 0;
		$req->RequestProps = array( 'ID', 'Name', 'Type' ); // At least three properties are needed to let the
															// Oracle driver decide if Properties or DB fields are asked.
		$req->Areas = array( 'Workflow' );

		$service = new WflQueryObjectsService();
		try {
			$resp = $service->execute( $req );
			// Determine the object ID column index
			$idIdx = 0;
			if( isset($resp->Columns) ) foreach( $resp->Columns as $col ) {
				if( $col->Name == 'ID' ) {
					break; // found!
				}
				$idIdx++;
			}
			if( isset($resp->Rows) ) foreach( $resp->Rows as $row ) {
				$objectIds[] = $row[$idIdx];
			}
		} catch ( BizException $e) {
			LogHandler::Log( __FILE__, 'ERROR', $e->getMessage() );
		}
	}
	
	return $objectIds;
}

function getObjectsPages( $ticket, $objectIds, $edition ) {
	$objectPages = null;
	if( !empty( $objectIds) ) {
		try {
			require_once BASEDIR.'/server/services/wfl/WflGetPagesService.class.php';
			$req = new WflGetPagesRequest();
			$req->Ticket = $ticket;
			$req->IDs = $objectIds;
            require_once BASEDIR.'/server/dbclasses/DBEdition.class.php';
            $req->Edition = DBEdition::getEdition( $edition ); // Returns null if not found
			$req->Renditions = array( 'output' );

			$service = new WflGetPagesService();
			$resp = $service->execute( $req );

			$objectPages = $resp->ObjectPageInfos;
		} catch( BizException $e ) {
			LogHandler::Log( __FILE__, 'ERROR', $e->getMessage() );
		}
	}
	return $objectPages;
}