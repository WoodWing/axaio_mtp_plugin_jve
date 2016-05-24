<?php
/**
 * Helper functions for the implementation of the Export pages
 * 
 * @package 	Enterprise
 * @subpackage 	Apps
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/admin/global_inc.php'; // formvar() / inputvar()

class ExportUtils
{
	public static function downloadFile($ticket, $objectID, $issId ) {
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
		$IDs = array($objectID);

		try {
			$getObjectsReq = new WflGetObjectsRequest( $ticket, $IDs, false, 'native', null);
			$getObjectsService = new WflGetObjectsService();
			$getObjectsResp = $getObjectsService->execute( $getObjectsReq );
			$objects = $getObjectsResp->Objects;
		} catch( BizException $e ) {
			// Ignore failres
			$e = $e; // keep code analyzer happy
		}

		if( !$objects || count($objects) != 1) {
			return BizResources::localize("ERR_EXPORT_FAILURE");
		}
		$object = $objects[0];
		
		$pub         = $object->MetaData->BasicMetaData->Publication->Name;
		$issue = DBIssue::getIssueName( $issId ); // BZ#30353
		$articleName = $object->MetaData->BasicMetaData->Name;
		$format 	 = $object->MetaData->ContentMetaData->Format;
		// Change Format into extension
		$extension = MimeTypeHandler::mimeType2FileExt($format);
	
		// Retreive file data
		if( $object->Files && count($object->Files) > 0 ) {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$value = $transferServer->getContent($object->Files[0]);			
		}
	
		// Create file and put value(=Atricle data = inCopy) into file
		// if file allready exists then it is over-writen
		if (EXPORTDIRECTORY != "") {
			if( !is_dir(EXPORTDIRECTORY) ) {
				mkdir(EXPORTDIRECTORY);
			}
			$exportname = $pub . "-". $issue ."-" . $articleName;
			if (defined('FILENAME_ENCODING')) {
				$exportname = iconv('UTF-8', FILENAME_ENCODING, $exportname);
			}
			$fp = fopen(EXPORTDIRECTORY . $exportname . $extension, "w+");

			if ($fp) {
				fputs($fp, $value);
				fclose($fp);
			} else {
				$sErrorMessage = BizResources::localize("ERR_EXPORT_FOLDER_EXISTS");
				return $sErrorMessage . " " . EXPORTDIRECTORY;
			}
		} else {
			return BizResources::localize("ERR_EXPORT_FAILURE");
		}
		return '';
	}
	
	public static function fillCombos( &$inPub, &$inIssue, $inEdition, &$tpl ) // BZ#5455
	{
		require_once BASEDIR.'/server/bizclasses/PubMgr.class.php';
		require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';

		global $globUser;
		
		 /////////////////////// *** Publication combo *** ///////////////////////
		$comboBoxPub = '<select name="Publication" style="width:150px" onchange="validateOnSubmit(\'Brand\');">';
		//$comboBoxPub .= "<option></option>"; // Fixed: no/any publication makes no sense
		$arrayOfPublications = PubMgr::getPublications($globUser, false, false ); //full is false, no exceptions
		if ($arrayOfPublications) foreach ($arrayOfPublications as $pub) {
			$pubID = $pub->Id;
			$pubName = $pub->Name;
	
			if( empty($inPub) ) { // auto selecting first pub (when none given)
				$inPub = $pub->Id;
			}
			if ($pubID!= $inPub) {
				$comboBoxPub .= '<option value="'.$pubID.'">'.formvar($pubName).'</option>';
			}
			else {
				$comboBoxPub .= '<option value="'.$pubID.'" selected="selected">'.formvar($pubName).'</option>';
			}
		}
		$comboBoxPub .= '</select>';
		$tpl = str_replace ('<!--COMBOPUB-->',$comboBoxPub, $tpl);
	
		/////////////////////// *** Issue combo *** ///////////////////////
		$comboBoxIss = '<select name="Issue" style="width:150px" onchange="validateOnSubmit(\'Issue\');">';
		//$comboBoxIss .= "<option></option>"; // Fixed: no/any issue makes no sense
		$arrayOfIssues = PubMgr::getIssues($globUser,$inPub, false, false);
		$pcn_issues = self::getListOfPrevCurrNextIssues($inPub, $arrayOfIssues);

		// Add items to the combo box
		$comboBoxIss = self::addPrevCurrNextToComboBoxIss($comboBoxIss, $pcn_issues, $inIssue);
		$comboBoxIss = self::addListOfIssuesToComboBoxIss($comboBoxIss, $arrayOfIssues, $inIssue);
		$comboBoxIss .= '</select>';
			
		$tpl = str_replace ('<!--COMBOISS-->',$comboBoxIss, $tpl);

		$inIssue = self::getSelectedIssue($inIssue, $pcn_issues);
		
		/////////////////////// *** Edition combo *** ///////////////////////
		if( !is_null($inEdition) ) {
			$comboBoxEdi = '<select name="Edition" style="width:150px" onchange="submit();">';
			$arrayOfEditions = PubMgr::getEditions($inPub, $inIssue, false);
			if ($arrayOfEditions) foreach ($arrayOfEditions as $edition) {
				$editionID 	 = $edition->Id;
				$editionName     = $edition->Name;
		
				if ( $editionID != $inEdition ) {
					$comboBoxEdi .= '<option value="'.$editionID.'">'.formvar($editionName).'</option>';
				} else {
					$comboBoxEdi .= '<option value="'.$editionID.'" selected="selected">'.formvar($editionName).'</option>';
				}
			}
			$comboBoxEdi .= '</select>';
			$tpl = str_replace ('<!--COMBOEDI-->',$comboBoxEdi, $tpl);
		}
	}

	public static function getObjectList( $ticket, $publ, $issue, $objType )
	{
		// Query all objects from publ/issue of specfied object type
		$ArrayOfQueryParams = array();
		$ArrayOfQueryParams[] = new QueryParam ('PublicationId', '=', $publ);
		$ArrayOfQueryParams[] = new QueryParam ('IssueId', '=', $issue);
		$ArrayOfQueryParams[] = new QueryParam ('Type', '=', $objType);

		$reqProps = array('ID','Type','Name','Format','PlacedOn','FileSize','StateId','State','StateColor');
		$reqPropKeys = array_flip($reqProps);
		$retObjs = array();
		try {
			require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
			require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
			
			$service = new WflQueryObjectsService();
			$resp = $service->execute( new WflQueryObjectsRequest( 
				$ticket, // Ticket
				$ArrayOfQueryParams, // Params
				null, // FirstEntry
				0,    // MaxEntries (0 = return all objects, independent of MAXQuery)
				null, // Hierarchical
				BizQuery::getQueryOrder( 'Name', 'asc' ), // Order
				null, // MinimalProps
				$reqProps // RequestProps
				) );
			if( isset($resp->Rows) ) foreach( $resp->Rows as $row ) {
				if( $row[$reqPropKeys['StateId']] == -1 ) {
					$row[$reqPropKeys['StateColor']] = PERSONAL_STATE_COLOR;
				}
				$statusObj = new State( 
					$row[$reqPropKeys['StateId']], 
					$row[$reqPropKeys['State']], 
					null, // Type
					null, // Produce
					substr($row[$reqPropKeys['StateColor']],1), // skip leading # char
					null ); // DefaultRouteTo
				$retObjs[] = array(
					'id'     => $row[$reqPropKeys['ID']], 
					'placed' => $row[$reqPropKeys['PlacedOn']], 
					'name'   => $row[$reqPropKeys['Name']], 
					'size'   => $row[$reqPropKeys['FileSize']], 
					'state'  => $statusObj );
			}
		} catch( BizException $e ) {
			$e = $e; // ignore errors
		}
		return $retObjs;
	}

	///////////////////////////// private functions ////////////////////////////
	
	private static function getListOfPrevCurrNextIssues($publication, $allIssues)
	{
		$pcn_issues = array();

		// Just get previous/current/next issues without checking authorization
		$pcn_issues = BizPublication::listPrevCurrentNextIssues($publication);

		// The $allIssues contains the issues checked for authorizations.
		// The previous/current/next must be in line with the list of issues.
		foreach ($pcn_issues as $key_pcn_issue => $pcn_issue) {
			$found  = false;
			foreach ($allIssues as $allIssue) {
				if ($pcn_issue['id'] == $allIssue->Id) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				unset($pcn_issues[$key_pcn_issue]);
			}
		}

		return $pcn_issues;
	}

	private static function addPrevCurrNextToComboBoxIss($comboBoxIss, $pcn_issues, $inIssue)
	{
		if ( isset($pcn_issues['current']) && is_array($pcn_issues['current'])) {
			$selected = ($inIssue == -2) ? 'selected="selected"' : '';
			$comboBoxIss .= '<option value="-2" '.$selected.'>' . BizResources::localize('CURRENT_ISSUE') . ' (' . $pcn_issues['current']['issue'] . ')' . '</option>';
		}

		if ( isset($pcn_issues['prev']) && is_array($pcn_issues['prev'])) {
			$selected = ($inIssue == -3) ? 'selected="selected"' : '';
			$comboBoxIss .= '<option value="-3" '.$selected.'>' . BizResources::localize('PREV_ISSUE') . ' (' . $pcn_issues['prev']['issue'] . ')' . '</option>';
		}

		if ( isset($pcn_issues['next']) && is_array($pcn_issues['next'])) {
			$selected = ($inIssue == -4) ? 'selected="selected"' : '';
			$comboBoxIss .= '<option value="-4" '.$selected.'>' . BizResources::localize('NEXT_ISSUE') . ' (' . $pcn_issues['next']['issue'] . ')' . '</option>';
		}

		return $comboBoxIss;
	}

	private static function addListOfIssuesToComboBoxIss( $comboBoxIss, $allIssues, &$inIssue )
	{
		if( !empty($allIssues) ) {
			$found = false;
			foreach( $allIssues as $issue ) {
				if( $inIssue == $issue->Id ) {
					$found = true;
					break;
				}
			}
			if( count($allIssues) > 0 ) {
				if( empty($inIssue) || // auto-select first issue (when none given)
					($inIssue > 0 && !$found) ) { // auto-correct issue when other publication is selected
					$inIssue = $allIssues[0]->Id;
				}
			}
			foreach( $allIssues as $issue ) {
				if( $issue->Id != $inIssue ) {
					$comboBoxIss .= '<option value="'.$issue->Id.'">'.formvar($issue->Name).'</option>';
				} else {
					$comboBoxIss .= '<option value="'.$issue->Id.'" selected="selected">'.formvar($issue->Name).'</option>';
				}
			}
		}

		return $comboBoxIss;
	}

	private static function getSelectedIssue($inIssue, $pcn_issues)
	{
		// Based on the name of the selected item the ID of the selected item is
		// determined.
		if ($inIssue > 0) {
			return $inIssue; // name is ID
		}

		if (isset($pcn_issues['current']) && is_array($pcn_issues['current']) && ($inIssue == -2)) {
			return $pcn_issues['current']['id'];
		}

		if (isset($pcn_issues['prev']) && is_array($pcn_issues['prev']) && ($inIssue == -3)) {
			return $pcn_issues['prev']['id'];
		}

		if (isset($pcn_issues['next']) && is_array($pcn_issues['next']) && ($inIssue == -4)) {
			return $pcn_issues['next']['id'];
		}

		return 0;
	}
}