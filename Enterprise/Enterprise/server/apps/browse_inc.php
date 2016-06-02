<?php
require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/utils/NumberUtils.class.php';

//Warning: this module is included by apps/browse.php, apps/nqbrowse.php AND admin/trash.php.
//Check whether changes work properly for all pages.

function ShowQueryResults( $queryResp, $ord, $sort, $Thumbnail_view, &$ObjectTitels, &$num_results, $allowEdit = false )
{
	$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;

	// List of all status colors
	$dbh = DBDriverFactory::gen();
	$dbst = $dbh->tablename("states");
	$sql = "select `id`, `color` from $dbst ";
	$sth = $dbh->query($sql);
	$stateColors = array();
	while( ($row = $dbh->fetch($sth)) ) {
		$stateColors[$row['id']]=$row['color'];
	}
	$stateColors[-1] = PERSONAL_STATE_COLOR;
	$ObjectRes = "";
	
	// List of internal property names that needs to be hidden from end-user
	$arrIntProps = array( "ID", "Format", "PublicationId", "IssueId", "SectionId", "StateId", "LockForOffline", "DeadlineSoft" );

	// List of all object types
	$objTypeMap = getObjectTypeMap();

	$idxPublId = -1;      // index for PublicationId column
	$idxIssueId = -1;     // index for IssueId column
	$idxFormatId = -1;    // index for FormatId column
	$idxLockedBy = -1;    // index for LockedBy column
	$idxObjType  = -1;    // index for Type column
	$idxLockOffline = -1; // index for LockForOffline column
	$idxStateId = -1;     // index for StateId column
	$idxDeadlineHard = -1;	// index for deadlinehard (to display)
	$idxDeadlineSoft = -1;	// index for deadlinesoft (trick: color)
	$idxState = -1;       // index for State column
	$idxFileSize = -1;    // index for FileSize column
	$idxName = -1;    // index for FileSize column


	// get childs & components
	$isHierView = false;
	$refparent = array();
	$refparentComp = array();
	if( $queryResp->ChildRows && is_array( $queryResp->ChildRows ) ) {
		$refparent = getchilds( $queryResp->ChildRows );
		$isHierView = true;
	}
	if( $queryResp->ComponentRows && is_array( $queryResp->ComponentRows ) ) {
		$refparentComp = getchilds( $queryResp->ComponentRows );
		$isHierView = true;
	}
	
	// handle rows
	$rownr = 0;
	$Properties = array();
	if( $queryResp->Columns && is_array( $queryResp->Columns ) ) {
		$Properties = $queryResp->Columns;
		$ObjectTitels .= "<th></th>\r\n\r\n";
		if( $allowEdit ) {
			$ObjectTitels .= "<th></th>\r\n";
		}
		if ($isHierView) $ObjectTitels .= "<th></th>";
		if( !empty($Thumbnail_view) ) {// show thmubnail column (optional)
			$ObjectTitels .= "\t<th align=left width=\"100\">".BizResources::localize("OBJ_THUMBNAIL")."</th>\r\n";
		}
		for( $idxProp = 0; $idxProp < count($Properties); $idxProp++ ) {
			$sPropName = $Properties[$idxProp]->Name;
			if( $sPropName == "FormatId" ) {
				$idxFormatId = $idxProp;
			} elseif( $sPropName == "Format" ){
				$idxFormatId = $idxProp;
			} elseif( $sPropName == "PublicationId" ) {
				$idxPublId = $idxProp;
			} elseif( $sPropName == "IssueId" ) {
				$idxIssueId = $idxProp;
			} elseif( $sPropName == "LockedBy" ) {
				$idxLockedBy = $idxProp;
			} elseif( $sPropName == "Type" ) {
				$idxObjType = $idxProp;
			} elseif( $sPropName == "State" ) {
				$idxState = $idxProp;
			} elseif( $sPropName == "DeadlineSoft" ) {
				$idxDeadlineSoft = $idxProp;
			} elseif( $sPropName == "Deadline" ) {
				$idxDeadlineHard = $idxProp;
			} elseif( $sPropName == "StateId" ) {
				$idxStateId = $idxProp;
			} elseif( $sPropName == "LockForOffline" ) {
				$idxLockOffline = $idxProp;
			} elseif( $sPropName == "FileSize" ) {
				$idxFileSize = $idxProp;
			} elseif( $sPropName == "Size" ) {
				$idxFileSize = $idxProp;
			} elseif( $sPropName == "Name" ) {
				$idxName = $idxProp;
			}
		}

		for( $idxProp = 0; $idxProp < count($Properties); $idxProp++ ) {
			$sPropName = $Properties[$idxProp]->Name;
			if( $sPropName == 'Type' ) {  // we don't show header for Type icon column
				$sPropDisp = '';
			} else {
				$sPropDisp = $Properties[$idxProp]->DisplayName;
			}
			if( !in_array( $sPropName, $arrIntProps ) ) { // hide internal properties
				if( $idxProp == $idxState    ) {
					$ObjectTitels .= "\t<th>&nbsp;&nbsp;&nbsp;</th>\r\n"; // state icon column
				} elseif( $idxProp == $idxDeadlineHard) {
					$ObjectTitels .= "\t<th>&nbsp;&nbsp;&nbsp;</th>\r\n"; // deadline icon column
				} elseif( $idxProp == $idxLockedBy ) {
					$ObjectTitels .= "\t<th>&nbsp;&nbsp;&nbsp;</th>\r\n"; // lockby icon column
				}
				$sortIcon = "";
				$sortNew = "asc";
				if( $sPropName == $ord ) {
					$sortNew = ($sort == "asc") ? "desc" : "asc"; // swap sorting order
					if( $sortNew == 'asc' ) {
						$sortIcon = "<img src='../../config/images/desc.gif'>&nbsp;";
					} else {
						$sortIcon = "<img src='../../config/images/asc.gif'>&nbsp;";
					}
				}
				// XSS note: $sPropName and $sPropDisp are read from XML and therefor HTML safe, so do NOT use htmlentities or formvar!
				if( strlen( $sort ) > 0 ) {
					$ObjectTitels .= "\t<th align=left>$sortIcon<a href=\"javascript:SortColumn( '$sPropName', '$sortNew' );\" ";
					$ObjectTitels .= "title='Sort $sortNew'>$sPropDisp</a></th>\r\n";
				}
				else {
					$ObjectTitels .= "<th>$sPropDisp</th>\r\n";
				}
			}
		}
	}

	// remember global env

	$env = array(
	"allowEdit" => $allowEdit,
	"Thumbnail_view" => $Thumbnail_view,
	"idxState" => $idxState,
	"idxStateId" => $idxStateId,
	"idxLockedBy" => $idxLockedBy,
	"idxObjType" => $idxObjType,
	"idxLockOffline" => $idxLockOffline,
	"idxFileSize" => $idxFileSize,
	"idxPublId" => $idxPublId,
	"idxIssueId" => $idxIssueId,
	"idxFormatId" => $idxFormatId,
	"idxDeadlineSoft" => $idxDeadlineSoft,
	"idxDeadlineHard" => $idxDeadlineHard,
	"idxName" => $idxName,
	"headerrow" => $Properties,
	"arrIntProps" => $arrIntProps,
	"boxSize" => $boxSize,
	"objTypeMap" => $objTypeMap,
	);

	if($queryResp->Rows && is_array($queryResp->Rows) ) {
		$num_results = count($queryResp->Rows);
		for($i = 0; $i < $num_results; $i++) {
			// Removed PEAR soap hack since we use PHP SoapServer for QueryObjects/NamedQueries
			//$rowvalues = $queryResp->Rows[$i]['Row'];
			$rowvalues = $queryResp->Rows[$i];
			$id = $rowvalues[0];
			$rownr++;
			// hierarchical view
			$ObjectChild = '';
			$childnr = 0;

			// childs
			if (isset($refparent[$id])) {
				foreach ($refparent[$id] as $childrow) {
					$childvalues = $childrow;
					$childnr++;
					// child comp
					$cid = $childvalues[0];
					$subchildnr = 0;
					$comps = '';
					if (isset($refparentComp[$cid]) && $refparentComp[$cid]) {
						foreach ($refparentComp[$cid] as $comprow) {
							// generate comp row
							$subchildnr++;
							$comps .= values2htmlrow($stateColors, $comprow, $env, $isHierView, false, 2, true, $rownr, $childnr, $subchildnr);
						}
					}

					// display rows with its comps
					if ($comps) {
						$ObjectChild .= values2htmlrow($stateColors, $childvalues, $env, $isHierView, true, 1, false, $rownr, $childnr, $subchildnr);
						$ObjectChild .= $comps;
					} else {
						$ObjectChild .= values2htmlrow($stateColors, $childvalues, $env, $isHierView, false, 1, false, $rownr, $childnr);
					}
				}
			}
			// components
			if (isset($refparentComp[$id])) {
				foreach ($refparentComp[$id] as $comprow) {
					// generate comp row
					$childnr++;
					$ObjectChild .= values2htmlrow($stateColors, $comprow, $env, $isHierView, false, 1, true, $rownr, $childnr);
				}
			}

			// display rows with its childs
			if ($ObjectChild) {
				$ObjectRes .= values2htmlrow($stateColors, $rowvalues, $env, $isHierView, true, 0, false, $rownr, $childnr);
				$ObjectRes .= $ObjectChild;
			} else {
				$ObjectRes .= values2htmlrow($stateColors, $rowvalues, $env, $isHierView, false, 0);
			}
		}
	}
	$ObjectTitels .= "</tr>\r\n";

	return $ObjectRes;
}

function getchilds($arr)
{
	$refparent = array();
	foreach ($arr as $child) {
		$pars = $child->Parents;
		foreach ($pars as $par) {
			$refparent[$par][] = $child->Row;
		}
	}
	
	return $refparent;
}


function values2htmlrow($stateColors, $values, $env, $isHierView, $haschilds, $level, $isComp = false, $rownr = 0, $childnr = 0, $subchildnr = 0)
// haschilds - show expandbox if true
// level - hier. level
// isComp - true = component, false = normal item
// rownr - number of toprow
// childnr - highest nr for childrow (for toprow), number of childrow (for childrow)
{				

	// get environment
	$allowEdit =		$env["allowEdit"];
	$Thumbnail_view =	$env["Thumbnail_view"];
	$idxState =			$env["idxState"];
	$idxStateId =		$env["idxStateId"];
	$idxLockedBy =		$env["idxLockedBy"];
	$idxObjType =		$env["idxObjType"];
	$idxLockOffline =	$env["idxLockOffline"];
	$idxFileSize =		$env["idxFileSize"];
	//$idxPublId =		$env["idxPublId"];
	//$idxIssueId =		$env["idxIssueId"];
	//$idxFormatId =	$env["idxFormatId"];
	$idxDeadlineSoft =	$env["idxDeadlineSoft"];
	$idxDeadlineHard =	$env["idxDeadlineHard"];
	$idxName =			$env["idxName"];
	$arrIntProps =		$env["arrIntProps"];
	$headerrow =		$env["headerrow"];
	$objTypeMap = 		$env["objTypeMap"];
	$boxSize = 			$env["boxSize"];
	
	$id = $values[0];
		
	// generate row
	$ObjectRes = '';
	if ( $allowEdit )
		$tdOpt = "onClick=\"popUp('info.php?id=".urlencode($id)."');\"";
	else
		$tdOpt = '';
		
	$lid = $id;
	if ($level) {
		if ($level >= 2) {
			$lid = 'hv'.$rownr.'-'.$childnr.'-'.$subchildnr;
			$color = 'EEEEEE';
		} else {
			$lid = 'hv'.$rownr.'-'.$childnr;
			$color = 'E5E5E5';
		}
		$jscript = '';
 		if (!$isComp) $jscript = "onmouseOver=\"this.bgColor='#FF9342';\" onmouseOut=\"this.bgColor='#$color';\"";
 		$ObjectRes .= "<tr id='$lid' style='display: none;' bgcolor='#$color' $jscript style='font-size=9px'>";
	} else
		$ObjectRes .= "<tr bgcolor='#DDDDDD' onmouseOver=\"this.bgColor='#FF9342';\"onmouseOut=\"this.bgColor='#DDDDDD';\">";

	$ObjectRes .= "\r\n<td>";
	if (!$isComp) $ObjectRes .= '<input type="checkbox" id="chkobj" name="chkobj" value="'.formvar($id).'"/>';
	$ObjectRes .= "</td>\r\n";

	if ($isHierView) {
		if ($haschilds) {
			$space = '';
			$tid = "hv$rownr";
			$chtxt = $childnr;
			if ($level >= 1) {
				$space = '&nbsp;&nbsp;&nbsp;';
				$tid .= "-$childnr";
				$chtxt = $subchildnr;
			}
			$ObjectRes .= "<td>$space<a href='' id='x$tid' onClick=\"javascript:Toggle('$tid', $chtxt);return false;\"><img src='../../config/images/expand.gif' border=0></a></td>";
		} else {
			$ObjectRes .= "<td></td>";
		}
	}
	
	// components have their own layout
	if ($isComp) {
		if( !empty($Thumbnail_view) ) $ObjectRes .= "<td></td>";
		
		$name = $values[1];
		$snip = $values[6];		
		$ObjectRes .= '<td></td><td>'.formvar($name).'</td><td></td><td colspan="50">'.formvar($snip).'</td></tr>'."\r\n";
		
		return $ObjectRes;
	}
	
	if( !empty($Thumbnail_view) ) {
		// note that this is used by Trash Can admin page, so "../apps" is needed!
		$ObjectRes .= "<td><a href=javascript:popUpThumb('../apps/thumbnail.php?id=".urlencode($id)."&rendition=preview')>";
		$ObjectRes .= "<img src=\"../apps/image.php?id=".urlencode($id)."&rendition=thumb\" border=0></a></td>\r\n";
	}
	$count = count($headerrow);
	if( in_array( $idxStateId, $values) && $values[$idxStateId] == -1){
		$values[$idxState] = BizResources::localize('PERSONAL_STATE');
	}
		
	global $globUser;
	for( $j = 0; $j < $count ; $j++ ) {
		$sPropName = $headerrow[$j]->Name;
		if( !in_array( $sPropName, $arrIntProps ) ) { // hide internal properties
			if( $j == $idxLockedBy ) { // show icon just before LockedBy
				if( $idxLockedBy != -1 && strlen($values[$idxLockedBy]) > 0 ) { // locked by someone?
					if( $idxLockOffline != -1 && $values[$idxLockOffline] == "true" ) {
						if( $values[$idxLockedBy] == $globUser ) // locked by current user?
							$lockIcon = "<img src='../../config/images/lockedit_16_offline.gif' border=0>";
						else
							$lockIcon = "<img src='../../config/images/lock_16_offline.gif' border=0>";
					}
					else {
						if( $values[$idxLockedBy] == $globUser ) // locked by current user?
							$lockIcon = "<img src='../../config/images/lockedit_16.gif' border=0>";
						else
							$lockIcon = "<img src='../../config/images/lock_16.gif' border=0>";
					}
				}
				else
					$lockIcon = "";
				$ObjectRes .= "\t<td $tdOpt>".$lockIcon."</td>\r\n\t<td $tdOpt>".formvar($values[$j])."</td>\r\n";
			}
			else if( $j == $idxObjType ) { // show icon instead of type
				$typeIcon = BizObject::getTypeIcon($values[$idxObjType]);
					// Todo: - Make difference between incopy articles and plain texts.
					//       - Make difference between articles and planned texts (same for adverts).
				$ObjectRes .= "\t<td $tdOpt><img src='../../config/images/$typeIcon' border=0 title='".$objTypeMap[$values[$idxObjType]]."'></td>\r\n";
			}
			else if( $j == $idxState ) { // show status icon and status text
				$color = @$stateColors[$values[$idxStateId]];
				if( !$color ) $color = '#eeeeee';
				$ObjectRes .= "\t<td $tdOpt><table border='1' style='border-collapse: collapse' bordercolor='#606060' height='$boxSize' width='$boxSize'><tr>\t<td bgColor='$color'></td></tr></table></td>\r\n";
				$ObjectRes .= "\t<td $tdOpt>".formvar($values[$j])."</td>\r\n";
			}
			else if ( $j == $idxFileSize ) {
				$ObjectRes .= "\t<td $tdOpt>".NumberUtils::getByteString($values[$j],2)."</td>\r\n";
			}
			else if( $j == $idxDeadlineHard ) { // show status icon and status text
				$color = '#eeeeee'; // empty
				if ( trim($values[$j])) {
					$now = date ("Y-m-d\\TH:i:s");
					$color = '#00ff00'; // green
					$dlSoft = trim($values[$idxDeadlineSoft]);
					if( !empty($dlSoft) && $idxDeadlineSoft != -1 && $now > $dlSoft ) {
						$color = '#ffff00'; // orange
					}
					$dlHard = trim($values[$idxDeadlineHard]);
					if( !empty($dlHard) && $now > $dlHard ) {
						$color = '#ff0000'; // red	
					}
				}
				$ObjectRes .= "\t<td $tdOpt><table border='1' style='border-collapse: collapse' bordercolor='#606060' height='$boxSize' width='$boxSize'><tr>\t<td bgColor='$color'></td></tr></table></td>\r\n";
				$ObjectRes .= "\t<td $tdOpt>".formvar(DateTimeFunctions::iso2date( $values[$j] ))."</td>\r\n";
			}
			else if( $j == $idxName ) {
				$pre = '';
				$ObjectRes .= "\t<td $tdOpt>$pre".formvar($values[$idxName])."</td>\r\n";
			} else {
				$isdate = DateTimeFunctions::iso2date( $values[$j] );
				if ($isdate) $values[$j] = $isdate;
				$ObjectRes .= "\t<td $tdOpt>".formvar($values[$j])."</td>\r\n";
			}
		}
	}
	$ObjectRes .= "</tr>\r\n";
	return $ObjectRes;
}
