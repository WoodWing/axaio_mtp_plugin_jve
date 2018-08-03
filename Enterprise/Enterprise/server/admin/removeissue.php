<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

// Set timeout to one hour.
set_time_limit(3600);

// Validate user, access rights and ticket.
require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
checkSecure('publadmin');
$user = BizSession::getShortUserName();
$objectmap = getObjectTypeMap();

// Retrieve value of the combo boxes.
$inPub     = isset($_REQUEST['Publication']) ? intval($_REQUEST['Publication']) : 0;
$inIssue   = isset($_REQUEST['Issue'])       ? intval($_REQUEST['Issue']) : 0;
$inObjType = isset($_REQUEST['ObjType'])     ? trim($_REQUEST['ObjType']) : '';

// Retrieve operation/action request parameters.
$show = isset($_REQUEST['show']) ? (bool)$_REQUEST['show'] : false;
$del = isset($_POST['del']) ? (bool)$_POST['del'] : false;
$directdel = isset($_REQUEST['directdel']) ? (bool)$_REQUEST['directdel'] : false;

$dum = null;
cookie( 'removeByDate2', $inPub == '', $inPub, $inIssue, $inObjType, $dum, $dum, $dum, $dum );

// Re-validate data retrieved from cookie! (XSS attacks)
$inPub = intval($inPub); 
$inIssue = intval($inIssue);
$inObjType = array_key_exists($inObjType, $objectmap) ? $inObjType : '';

$message = '';
$succeed = true;
if( $directdel === true ||            // Did admin user press delete button, without first listing the objects to delete?
	($show === false && $del === true) ) {	// Admin user pressed the delete button after querying objects;
											// and choosing between move/delete operations per object?
	if( $inPub && $inIssue ) {	// Did admin user select publication and issue?
		try {
			$moveObjIds     = array(); // Objects to move to different issue.
			$newIssueIds    = array(); // New issue (ids) per object to move to.
			$deleteObjIds   = array(); // Object to delete.
			$unassignObjIds = array(); // Objects to unassign from issue.
			
			if( $del === true ) { 
				$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
			
				for ($i = 0; $i < $amount; $i++) {
					$value = $_POST["objAction$i"];
					$objId = $_POST["objectID$i"]; // Can be alien id (=string!).
					switch( $value ) { 
						case 'delete':
							$deleteObjIds[] = $objId;
							break;
						case 'move':
							$moveObjIds[] = $objId;
							$newIssueIds[$objId] = intval($_POST["newIssue$i"]);
							break;
						case 'unassign':
							$unassignObjIds[] = $objId;
							break;
						case 'none':
							break;
					}
				}
			} elseif( $directdel === true ) {
				// Query for objects as admin user has selected publication, issue and object type.
				$qoResp = doQueryObjects( $inPub, $inIssue, $inObjType );
				// Bugfix: $inObjType was not passed... and so *all* objects were removed,
				//         even when user has just one object type selected !!!
			
				// Handle objects marked to remove from issue.
				if( $qoResp && isset($qoResp->Rows) && count($qoResp->Rows) > 0 ) { // Any object found?
					$colIndexes = queryObjectsColumnIndexes( reqPropsForRemoveIssue(), $qoResp->Columns );

					// Split objects into two collections; the ones that have single issue and 
					// the ones that have multiple issues assigned...
					foreach( $qoResp->Rows as $row ) {
						$issueIds = explode( ', ', $row[$colIndexes['IssueIds']] );
						$issueIds = array_flip( $issueIds );
						unset( $issueIds[$inIssue] ); // Pop out the issue we've queried for.
						if( count($issueIds) > 0 ) { // The object has more issues assigned than the one we queried for?
							$unassignObjIds[] = $row[$colIndexes['ID']];
						} else { // The issue we queried for, is the only one assigned to this object?
							$deleteObjIds[] = $row[$colIndexes['ID']];
						}
					}
				}
			}

			/* // For heavy debugging only.
			echo 'deleteObjIds: ';   print_r($deleteObjIds);   echo "<br/><br/>\n\n";
			echo 'unassignObjIds: '; print_r($unassignObjIds); echo "<br/><br/>\n\n";
			echo 'moveObjIds: ';     print_r($moveObjIds);     echo "<br/><br/>\n\n";
			echo 'newIssueIds: ';    print_r($newIssueIds);    echo "<br/><br/>\n\n";
			*/

			// Delete objects that have single issues at once.
			if( count($deleteObjIds) > 0 ) {
				require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
				$service = new WflDeleteObjectsService();
				$deleteObjectsReq = new WflDeleteObjectsRequest();
				$deleteObjectsReq->Ticket = BizSession::getTicket();
				$deleteObjectsReq->IDs = $deleteObjIds;
				$deleteObjectsReq->Permanent = true; // delete permanent, or else we still can't delete the issue!
				$deleteObjectsReq->Areas = array('Workflow');
				$deleteObjectsReq->Context = 'Issue';
				$resp = $service->execute( $deleteObjectsReq );

				if( $resp->Reports ){ // since v8.0, deleteObjects service will not throw any errors but it is captured in deleteObjects resp.
					foreach( $resp->Reports as $report ){
						$errMsg = 'Failed deleted ObjID:' . $report->BelongsTo->ID . PHP_EOL;
						foreach( $report->Entries as $reportEntry ) {
							$errMsg .= $reportEntry->Message . PHP_EOL;
						}
						LogHandler::Log('removeissue','ERROR', $errMsg );
					}
					$message = BizResources::localize('ERR_DELETE');
					$succeed = false;
				}
			}
			
			// Keep the objects that have multiple issues assigned in the system, but just remove the current issue requested.
			if( count($unassignObjIds) > 0 ) {
				require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
				foreach( $unassignObjIds as $objectId ) {
                    // Remove the object target for the selected issue of the object.
                    // If the issue is published the user gets a warning. BZ#30518, 29391.
                    // Also the relational targets of the children of the object are deleted.
					DBTarget::removeSomeTargetsByObject( $objectId, null, $inIssue );
                    // Remove the relation target(s) of the object for the selected issue.
                    DBTarget::removeRelationalTargetsByChildObjectAndIssue( $objectId, $inIssue );
					// Removing the target from the database is not enough, we also need to update Solr for the unassigned object.
					reindexObject($objectId);
				}
			}

			// Handle objects marked to move to different issue.
			if( count($moveObjIds) > 0 ) {
			
				// Get the objects to move.
				$objects = getObjects($moveObjIds);

				// Move objects to different Issue.
				foreach ($objects as $object) {
					$id = $object->MetaData->BasicMetaData->ID;
					if( $object->Targets ) foreach( $object->Targets as &$target ) {
						if( isset($target->Issue->Id) && $target->Issue->Id == $inIssue ) {
							$target->PubChannel = null; // clear to make sure we did not select the wrong one (resolved server side)
							$target->Issue = new Issue( $newIssueIds[$id] ); // move!
								// We do not touch the editions here! ... For this reason we can not use 
								// the Create-/Delete-ObjectTargets services, which would be more efficient.
						}
					}
					
					try {
						$request = new WflSetObjectPropertiesRequest();
						$request->Ticket = BizSession::getTicket();
						$request->ID = $id;
						$request->MetaData = $object->MetaData;
						$request->Targets = $object->Targets;
						$service = new WflSetObjectPropertiesService();
						$service->execute( $req );
					} catch( BizException $e ) { // Set props failed.
						$succeed = false;
						$message = $object->MetaData->BasicMetaData->Name .': ' . $e->getMessage() . '<br/>';
					}
				}
			}
		} catch( BizException $e ) {
			$message = $e->getMessage();
			$succeed = false;
		}

		// Delete the issue definition itself...
		if( $succeed == true ) { // No error on object deletions/movings above?
			try {			
				// Check if there are no objects left for the issue, so this time query for *all* object types.
				$qoResp = doQueryObjects( $inPub, $inIssue, '' );
				if( $qoResp && count($qoResp->Rows) == 0 ) { // No objects related anymore?
		
					// Resolve issue name before we delete it.
					require_once( BASEDIR . '/server/dbclasses/DBIssue.class.php' );
					$issueRow = DBIssue::getIssue( $inIssue );
		
					// Remove the entire issue definition (with every definition in it; statuses, sections, etc)
					require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
					$request = new AdmDeleteIssuesRequest();
					$request->Ticket = BizSession::getTicket();
					$request->PublicationId = $inPub;
					$request->IssueIds = array( $inIssue );
					$service = new AdmDeleteIssuesService();
					$service->execute( $request );

					// Inform admin user.
					$message = BizResources::localize('ERR_SUCCESS_DELETED') . ' ' . formvar($issueRow['name']);
				}
				else { // Selected items are succesfully deleted.
					$message = BizResources::localize('ACT_ALL_DONE');
				}
			} catch( BizException $e ) {
				$message = $e->getMessage();
				$succeed = false;
			}
		}
	}
}

// Get publications, issues from DB
$pubs = BizPublication::getPublications( $user );
$issues = $inPub ? BizPublication::getIssues( $user, $inPub ) : array();

// Determine the object types, user can choose from, for the selected issue.
// Also count objects per type (to show user) and hide types that don't occur at issue.
if( $inPub && $inIssue ) {
	// Get all possible object types to start with.
	$objTypes = getObjectTypeMap();
	$deletedObjTypes = $objTypes;
	asort($objTypes);
	// Remove object types that don't occur in the issue, and add object counts (per type) to the object types combo box!
	$summaryRows = DBObject::getObjectCountsPerType( $inIssue, true ); // true = search workflow objects.
	foreach( array_keys($objTypes) as $objType ) {
		if( isset( $summaryRows[$objType] ) ) {
			$objTypes[$objType] .= ' ('.$summaryRows[$objType].')';
		} else {
			unset( $objTypes[$objType] );
		}
	}
	
	// Now find in TrashCan (Trash area).
	asort($deletedObjTypes);
	$summaryDeletedRows = DBObject::getObjectCountsPerType( $inIssue, false ); // false = search deleted objects
	foreach( array_keys($deletedObjTypes) as $deletedObjType ) {
		if( isset( $summaryDeletedRows[$deletedObjType] ) ) {
			$deletedObjTypes[$deletedObjType] .= ' ('.$summaryDeletedRows[$deletedObjType].')';
		} else {
			unset( $deletedObjTypes[$deletedObjType] );
		}
	}

} else { // No issue selected.
	$summaryRows = array();
	$summaryDeletedRows = array();
	$objTypes = array();
	$inObjType = ''; // clear!
}

// Build HTML combo boxes for publications, issues and object types in memory.
$comboBoxPub = buildPublicationsCombo( $pubs, $inPub );
$comboBoxIss = buildIssuesCombo( $issues, $inIssue );
$comboBoxObjType = buildObjectTypesCombo( $objTypes, $inObjType );

// Load HTML template and inject the built combo boxes to show end user.
$tpl = HtmlDocument::loadTemplate( 'removeissue.htm' );
$tpl = str_replace ("<!--COMBOPUB-->",$comboBoxPub, $tpl);
$tpl = str_replace ("<!--COMBOISS-->",$comboBoxIss, $tpl);
$tpl = str_replace ("<!--COMBOOBJTYPE-->",$comboBoxObjType, $tpl);

// Show the object selection as HTML table and inject at HTML template to show end user.
if( $inPub && $inIssue ) {
	$txt = showFiles( $inPub, $inIssue, $inObjType, $issues, $summaryRows, $summaryDeletedRows, $show );
	$tpl = str_replace ("<!--CONTENT-->", $txt, $tpl);
}

// Build the entire HTML page.
$err = $message ? "onLoad='javaScript:message(\"$message\")'" : '';
print HtmlDocument::buildDocument( $tpl, true, $err );

/**
 * Reindexes an object in the Solr search indexes.
 *
 * @param int $objectId The ID of the object to be reindexed.
 * @return void
 */
function reindexObject( $objectId){
	// Retrieve the Object to be updated.
	$objects = getObjects(array($objectId));
	if (1 != count($objects)){
		$detail = 'Error: Expected only one object. Found: ' . count($objects);
		LogHandler::Log('removeissue', 'ERROR', $detail);
		return; // Reindexing is not possible, we only expected a single result.
	}

	$object = $objects[0];

	// Update the search index.
	require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
	BizSearch::indexObjects( array( $object ), true, array( 'Workflow' ), true );
}

/**
 * Retrieve objects by their ids.
 *
 * @param string[] $objIds An array of Object ids.
 *
 * @return array|object[] $objects The array of retrieved objects or an empty array if none were found.
 */
function getObjects( array $objIds ) {
	$request = new WflGetObjectsRequest();
	$request->Ticket = BizSession::getTicket();
	$request->IDs = $objIds;
	$request->Lock = false;
	$request->Rendition = 'none';
	$request->RequestInfo = array( 'Targets', 'MetaData', 'Relations' );
	$service = new WflGetObjectsService();
	$response = $service->execute( $request );
	$objects = ( $response && $response->Objects ) ? $response->Objects : array();
	return $objects;
}

/**
 * Lists objects in HTML table for a given publication, issue and object type.
 *
 * It allows end user to select action per listed object; move or delete.
 *
 * @param string $pubId Publication (id) that owns the issue.
 * @param string $issueId Issue (id) to show objects for.
 * @param string $objType Object type to show objects for. Empty to show all types.
 * @param array $summaryRows List of rows with 'count' and 'type' values of objects that reside at the issue.
 * @param array $summaryDeletedRows List of rows with 'count' and 'type values of deleted objects that reside at the issue.
 * @param boolean $show True to show all objects. False to show summary of counts and types only.
 *
 * @return string HTML stream representing the objects table (or summary) to show.
 */
function showFiles( $pubId, $issueId, $objType, $issues, $summaryRows, $summaryDeletedRows, $show )
{
	$txt = '';
	$totalWflObjcts = count( $summaryRows );
	$totalDeletedObjects = count( $summaryDeletedRows );

	if( $show === false ){
			
		if( $totalWflObjcts > 0 && $totalDeletedObjects > 0 ){
			$txt = BizResources::localize('OBJ_N_OBJ_DELETED_FOUND_ISS_MSG').'<br><br>' . PHP_EOL;
			$txt .= BizResources::localize('ACT_PURGE_OBJ_NOTIFY_MSG') . '.<br>';			
		}
	
		
		if( $totalWflObjcts > 0 ) {
			$objectLists = '<ul>';
			foreach( $summaryRows as $sumObjType => $sumObjCount ) {
				if( $objType == '' || $objType == $sumObjType ) {
					$objectLists.= '<li>'.formvar($sumObjType).' ('.$sumObjCount.')</li>';
				}
			}
			$objectLists .= '</ul>';
			$txt .= BizResources::localize('OBJ_FOUND_MSG'). $objectLists;
		}
		
		if( $totalDeletedObjects > 0 ){
			$objectLists = '<ul>';
			foreach( $summaryDeletedRows as $sumObjType => $sumObjCount ) {
				if( $objType == '' || $objType == $sumObjType ) {
					$objectLists.= '<li>'.formvar($sumObjType).' ('.$sumObjCount.')</li>';
				}
			}
			$objectLists .= '</ul>';
			$txt .= BizResources::localize('OBJ_DELETED_FOUND_MSG').'<br>' . 
					BizResources::localize('ACT_PURGE_OBJ_INFORM_MSG'). $objectLists;

		}
		$qoResp = doQueryObjects( $pubId, $issueId, 'Dossier' );
		$dossierIds = array();
		if( $qoResp && isset($qoResp->Rows) && count($qoResp->Rows) > 0 ) {
			$colIndexes = queryObjectsColumnIndexes( reqPropsForRemoveIssue(), $qoResp->Columns );
			foreach( $qoResp->Rows as $row ) {
				if( !in_array($row[$colIndexes['ID']], $dossierIds) ) {
					$dossierIds[] = $row[$colIndexes['ID']];
				}
			}
		}
	
		// BZ#29391 - Check whether issue content published before
		// When issue published before, warn the user
		$issuePublished = false;
		if( count($dossierIds) > 0 ) {
			require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
			$channelId = DBIssue::getChannelId( $issueId );
			foreach( $dossierIds as $dossierId ) {
				require_once BASEDIR.'/server/dbclasses/DBPublishHistory.class.php';
				$issuePublished = DBPublishHistory::isDossierWithinIssuePublished($dossierId, $channelId, $issueId);
				if ( $issuePublished) {
					break;
				}
			}
		}	

		if( $issuePublished ) {
			foreach( $issues as $issue ) {
				if( $issue->Id == $issueId ) {
					$issueName = $issue->Name;
					break;
				}
			}
			$sErrorMessage1 = BizResources::localize( 'ERR_DELETE_PUBLISHED_ISSUE', true, array($issueName) );
		} else {
			$sErrorMessage1 = BizResources::localize('ERR_DELETE_ISSUE');
			$issuePublished = 'false'; // Set false as string, else HTML won't display
		}
		$txt .= '<table border=0>' . PHP_EOL;
		$txt .=	inputvar( 'issueID', $issueId, 'hidden' ).
				inputvar( 'show', '', 'hidden' ).
				inputvar( 'del', '', 'hidden' ).
				inputvar( 'directdel', '', 'hidden' );
		$txt .= '<tr><td>';
		
		// Button to go 'List workflow objects'.
		if( $totalWflObjcts > 0 ){
			$txt .=	'<a href="javascript:showObjects();">
						<img src="../../config/images/prefs_16.gif" border="0" title="'.BizResources::localize('ACT_SHOW').'"/>'.
						BizResources::localize('OBJ_SHOW_MSG').'</a>';
		}		

		// Button to 'Delete the issue and the objects tied to it'
		if( $totalWflObjcts > 0 && $totalDeletedObjects == 0 ) { // Only show Delete button when there's no deletedObjects bound to this issue
			$txt .=	'<a href="javascript:directDelete(\''. $sErrorMessage1 . '\','. $issuePublished .');">
						<img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize('ACT_DELETE').'"/>'.
						BizResources::localize('ACT_DELETE_ALL_PERMANENT') . '</a>';
		}		
				
		$txt .=	'</td></tr></table>' . PHP_EOL;
		} else {
				if( $totalWflObjcts > 0 ){  // only allow user to move issue / delete object when the object is from workflow area.
				// Query objects and collect some properties we want to show end user.
				$objectmap = getObjectTypeMap();
				$elements = array();
				try {
					$qoResp = doQueryObjects( $pubId, $issueId, $objType );
					if( $qoResp && isset($qoResp->Rows) && count($qoResp->Rows) > 0 ) { // Any object found?
						$colIndexes = queryObjectsColumnIndexes( reqPropsForRemoveIssue(), $qoResp->Columns );
						foreach( $qoResp->Rows as $row ) {
							$issueIds = explode( ', ', $row[$colIndexes['IssueIds']] );
							$issueIds = array_flip( $issueIds );
							$elements[] = array(
								'id'   => $row[$colIndexes['ID']],       'placed' => $row[$colIndexes['PlacedOn']], 
								'used' => $row[$colIndexes['LockedBy']], 'state'  => $row[$colIndexes['StateId']], 
								'name' => $row[$colIndexes['Name']],     'size'   => $row[$colIndexes['FileSize']], 
								'type' => $row[$colIndexes['Type']],     'issues' => $row[$colIndexes['Issues']],
								'issueIds' => $issueIds );
						}
					}
				} catch( BizException $e ) {
					$e = $e; // Ignore errors.
				}
				
				// Show object properties at screen (table of rows and columns).
				$isOverruleIssue = checkIssForOverride( $issueId );
				$txt .= '<form name="content" method="post" action="removeissue.php" enctype="multipart/form-data">
						<table class="text">
							<tr>
								<td style="width:75px"><b> '.BizResources::localize('ACT_ACTION').' </b></td>
								<td style="width:75px"><b> '.BizResources::localize('ACT_MOVE').' </b></td>
								<td style="width:75px"><b> '.BizResources::localize('OBJ_TYPE2').' </b></td>
								<td style="width:125px"><b> '.BizResources::localize('OBJ_NAME').' </b></td>
								<td style="width:100px"><b> '.BizResources::localize('ACT_PLACED_ON').' </b></td>
								<td style="width:100px"><b> '.BizResources::localize('OBJ_LOCKED_BY').' </b></td>
								<td style="width:75px"><b> '.BizResources::localize('OBJ_SIZE').' </b></td>
								<td style="width:100px"><b> '.BizResources::localize('ISSUES').' </b></td>
							</tr>';
		
				$ix = 0;
				for ($ix; $ix < count($elements); $ix++) {
		
					// Build combo per listed object that shows the list of possible issues to which the object is allowed to move to.
					$moveToIssuesCount = 0;
					$comboBoxNewIssue =  '<select name="newIssue'.$ix.'" style="width:100px" disabled="disabled">';
					$comboBoxNewIssue .= '<option selected="selected"></option>';
					foreach( $issues as $iss ) {
						if( $iss->Id != $issueId && // Target issue can not be the same as source issue.
								!isset($elements[$ix]["issueIds"][$iss->Id]) ) { // Target issue can not be one of the multiple issues that are already assigned to the object.
							$overrulePub = isset($iss->OverrulePublication) ? (trim($iss->OverrulePublication) != '') : false;
							if( !$overrulePub ) { // Target issue can not be an overrule issue (or else if could get moved to different workflow system!).
								$comboBoxNewIssue .= '<option value="'.$iss->Id.'">'.formvar($iss->Name).'</option>';
								$moveToIssuesCount++;
							}
						}
					}
					$comboBoxNewIssue .= '</select>';
		
					// Build object action combo box, that shows possible operations per object, user can choose from.
					// IMPORTANT: The order of action items listed at combo is important; first we prefer unassign, 
					//            then delete, then move, then none.
					//            Some options we don't add, and so the 'best' option gets on top of list and gets 
					//            pre-selected, which is how we implement the best default action!
					$actionCombo = '<select name="objAction'.$ix.'" style="width:100px" onclick="javascript:onChangeObjActionCombo(\'' . $ix . '\');">';
					if( trim($elements[$ix]['used']) == '') { // Object is not locked?
						$issueIdsOfElement = array_keys($elements[$ix]["issueIds"]);
						if( count($elements[$ix]["issueIds"]) > 1 || (count( $issueIdsOfElement ) == 1 && $issueIdsOfElement[0] != $issueId)) {
							// Object is assigned to multiple issues or is assigned to one but that is not the selected one. This is the case
							// when an object is assigned to many issues and then only the issue is shown of the object target (EN-83890).
							$actionCombo .= '<option value="unassign">'.BizResources::localize('ACT_UNASSIGN').'</option>';
						}
						$actionCombo .= '<option value="delete">'.BizResources::localize('ACT_DELETE').'</option>';
						// Disable "move to" option when object is locked, or current issue is overrule issue, 
						// or there are no issues to move to (for which overrule issues are excluded!)
						if( !$isOverruleIssue && $moveToIssuesCount > 0 ) {
							$actionCombo .= '<option value="move">'.BizResources::localize('ACT_MOVE').'</option>';
						}
					}
					$actionCombo .= '<option value="none">'.BizResources::localize('LIS_NONE').'</option>';
					$actionCombo .= '</select>';
		
					// Show object at table row.
					$txt .= '<tr bgcolor="#DDDDDD" onmouseOver="this.bgColor=\'#FF9342\';" onmouseOut="this.bgColor=\'#DDDDDD\';">
								<td>'.
									inputvar( 'objectID'.$ix, $elements[$ix]['id'], 'hidden' ).
									$actionCombo.
								'</td>
								<td>'.$comboBoxNewIssue.'</td>
								<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($objectmap[$elements[$ix]['type']]) . '
								<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($elements[$ix]['name']) . '</td>
								<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($elements[$ix]['placed']) . '</td>
								<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');"><font color="red">' . formvar($elements[$ix]['used']) . '</font></td>
								<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');" align="right">' . formvar(calculateSize($elements[$ix]['size'])) . '</td>
								<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($elements[$ix]['issues']) . '</td>
							</tr>';
				}
		
				// Build master issue/action combo boxes.
				// The master combos allow user to select all combos at object rows at once.
				if( count($elements) > 1 ) { // add master combos only when multiple rows are shown
					$comboBoxNewIssue =  '<select name="newIssueMaster" style="width:100px" disabled="disabled" onclick="javascript:onChangeMasterNewIssueCombo(this);">';
					$comboBoxNewIssue .= '<option selected="selected"></option>';
					foreach( $issues as $iss ) {
						if( $iss->Id != $issueId ) { // Target issue can not be the same as source issue.
							$overrulePub = isset($iss->OverrulePublication) ? (trim($iss->OverrulePublication) != '') : false;
							if( !$overrulePub ) { // Target issue can not be an overrule issue (or else if could get moved to different workflow system!)
								$comboBoxNewIssue .= '<option value="'.$iss->Id.'">'.formvar($iss->Name).'</option>';
							}
						}
					}
					$comboBoxNewIssue .= '</select>';
			
					$actionCombo = '<select name="objActionMaster" style="width:100px" onClick="javascript:onChangeMasterObjActionCombo(this);">';
					$actionCombo .= '<option value="default">('.BizResources::localize('REINIT').')</option>';
					$actionCombo .= '<option value="unassign">'.BizResources::localize('ACT_UNASSIGN').'</option>';
					$actionCombo .= '<option value="delete">'.BizResources::localize('ACT_DELETE').'</option>';
					$actionCombo .= '<option value="move">'.BizResources::localize('ACT_MOVE').'</option>';
					$actionCombo .= '<option value="none">'.BizResources::localize('LIS_NONE').'</option>';
					$actionCombo .= '</select>';
					
					$txt .= '
						<tr><td colspan="7">&nbsp;</td></tr>
						<tr><td>'.$actionCombo.'</td><td colspan="6">'.$comboBoxNewIssue.'</td></tr>';
				}
				
				// Show the objects in HTML table and Refresh/Delete buttons.
				$sErrorMessage1 = BizResources::localize('ERR_DELETE_ISSUE');
				$txt .= '
						<tr><td colspan="7">&nbsp;</td></tr>
						<tr>
							<td colspan="7">
								<a href="javascript:document.remove.submit();">
									<img src="../../config/images/ref_16.gif" border="0" title="'.BizResources::localize('ACT_REFRESH').'"/>
									'.BizResources::localize('ACT_REFRESH').'
								</a>
								<a href="javascript:areYouSure(\''. $sErrorMessage1 . '\');">
								<img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize('ACT_PURGE_ISSUE').'"/>
									'.BizResources::localize('ACT_DELETE').'
								</a>'.
								inputvar( 'amount', $ix, 'hidden' ).
								inputvar( 'issueID', $issueId, 'hidden' ).
								inputvar( 'del', '', 'hidden' ).
								inputvar( 'directdel', '', 'hidden' ).
							'</td>
						</tr></table></form>';
			} /*else{ // When objects are in Trash Can } // Will not happen	*/
		}	
	return $txt;
}

/**
 * Calculates the converted size for $size.
 *
 * Note: currently hardcoded to return the size in Bytes.
 *
 * @param int $size Number of bits.
 *
 * @return string The string size.
 */
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
 * Checks if the 'overrule publication' value is set for the Issue.
 *
 * @param $id The ID to check for.
 * @return bool Whether or not the 'overrule publication' value is set.
 */
function checkIssForOverride( $id )
{
	$dbh = DBDriverFactory::gen();
	$iss = $dbh->tablename('issues');
	$sql = "SELECT `overrulepub` FROM $iss WHERE `id` = $id";
	$sth = $dbh->query( $sql );
	$result = $dbh->fetch( $sth );
	return ($result['overrulepub'] == 'on');
}

/**
 * Gives the collection of properties to use for QueryObjects
 * to be able to show overview of objects in this application.
 *
 * @return array of property names.
 */
function reqPropsForRemoveIssue()
{
	return array( 'ID', 'Name', 'Type', 'PublicationId', 'StateId', 'SectionId', 'PlacedOn', 'LockedBy', 'FileSize',
	              'IssueIds', 'Issues' );
}

/**
 * Function returns the objects related to an issue.
 *
 * @param integer $pubId   Id of publication that owns the issue.
 * @param integer $issueId Issue id
 * @param integer $objType Object type. Empty for all types.
 *
 * @return object WflQueryObjectsResponse
*/
function doQueryObjects( $pubId, $issueId, $objType )
{	
	// query objects from publ/issue
	$queryParams = array();
	$queryParams[] = new QueryParam ('PublicationId', '=', $pubId);
	$queryParams[] = new QueryParam ('IssueId', '=', $issueId);
	if( $objType ) $queryParams[] = new QueryParam ('Type', '=', $objType);

	require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
	$request = new WflQueryObjectsRequest();
	$request->Ticket = BizSession::getTicket();
	$request->Params = $queryParams;
	$request->MaxEntries = 0; // return all objects, independent of DBMAXQUERY
	$request->MinimalProps = reqPropsForRemoveIssue();
	$service = new WflQueryObjectsService();
	return $service->execute( $request );
}


/**
 * Determines the index of all requested columns passed to QueryObjects.
 * This is usedful for looking up property values at returned row values.
 *
 * This is useful for looking up property values at returned row values.
 *
 * @param array $reqCols List of requested object property names.
 * @param array $respCols List of returned columns from QueryObjects.
 *
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

/**
 * Returns a HTML combo box filled with the given publications.
 *
 * @param array $pubs List of Publication objects with Id and Name attributes.
 * @param string $selectPubId The publication (id) to pre-select in the combo box.
 *
 * @return string HTML stream with <select> element representing the combo box and all data inside
 */
function buildPublicationsCombo( $pubs, $selectPubId )
{
	$combo = '<select name="Publication" style="width:150px" onchange="submit();">';
	$combo .= '<option></option>'; // empty item
	foreach( $pubs as $pub ) {
		if( $pub->Id != $selectPubId ) {
			$combo .= '<option value="'.$pub->Id.'">'.formvar($pub->Name).'</option>';
		} else {
			$combo .= '<option value="'.$pub->Id.'" selected="selected">'.formvar($pub->Name).'</option>';
		}
	}
	$combo .= '</select>';
	return $combo;
}

/**
 * Returns a HTML combo box filled with the given issues.
 *
 * @param array $issues List of Issue objects with Id and Name attributes.
 * @param string $selectIssueId The issue (id) to pre-select in the combo box.
 *
 * @return string HTML stream with <select> element representing the combo box and all data inside
 */
function buildIssuesCombo( $issues, $selectIssueId )
{
	$combo = '<select name="Issue" style="width:150px" onchange="submit();">';
	$combo .= '<option></option>'; // empty item
	foreach( $issues as $iss ) {
		if( $iss->Id != $selectIssueId ) {
			$combo .= '<option value="'.$iss->Id.'">'.formvar($iss->Name).'</option>';
		} else {
			$combo .= '<option value="'.$iss->Id.'" selected="selected">'.formvar($iss->Name).'</option>';
		}
	}
	$combo .= '</select>';
	return $combo;
}

/**
 * Returns a HTML combo box filled with the given object types.
 *
 * @param array $objTypes Key-value pairs. Keys: internal object types. Values: localized object types.
 * @param string $selectType The object type (key) to pre-select in the combo box.
 *
 * @return string HTML stream with <select> element representing the combo box and all data inside
 */
function buildObjectTypesCombo( $objTypes, $selectType )
{
	$combo = '<select name="ObjType" style="width:150px" onchange="submit();">';
	$combo .= '<option></option>'; // empty item
	foreach( $objTypes as $objTypeKey => $objTypeDisplay ) {
		if( $objTypeKey != $selectType ) {
			$combo .= '<option value="'.$objTypeKey.'">'.formvar($objTypeDisplay).'</option>';
		} else {
			$combo .= '<option value="'.$objTypeKey.'" selected="selected">'.formvar($objTypeDisplay).'</option>';
		}
	}
	$combo .= '</select>';
	return $combo;
}
