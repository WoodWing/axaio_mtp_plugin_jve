<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDiffTimeField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';

checkSecure('publadmin');

// domains
$typesdomain = getObjectTypeMap();
asort($typesdomain);

// Create 1 deadlinerelativefields for single input
$deadlinerelativefield = new HtmlDiffTimeField(null, 'deadlinerelativefield');

// determine incoming mode
$publ  = isset($_REQUEST['publ'])  ? intval($_REQUEST['publ'])  : 0; // zero should never happen
$issue = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; // zero for overruled issues
$type  = isset($_REQUEST['type'])  ? $_REQUEST['type']  : ''; // empty for all types for pub/issue

// Check the brand to see if Deadline calculation is enabled, if it isn't the deadline fields on the states
// should be hidden.
require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
$admPublObj = DBAdmPublication::getPublicationObj( $publ );
$useDeadlines = false;
if ($admPublObj ) {
	$useDeadlines = ( $admPublObj->CalculateDeadlines );
}
$useSkipIdsa = isIdsaUsed();

$records = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;
$insert = isset($_REQUEST['insert']) ? (bool)$_REQUEST['insert'] : false;

// check publication rights
checkPublAdmin($publ);

if (!isset($_REQUEST['type'])) {
	$mode = 'select';
} else if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = 'update';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['add']) && $_REQUEST['add']) {
	$mode = 'add';
} else {
	$mode = 'view';
}
$errors = array();

// handle request
if( $records ) {
	try {
		// build list of (sorted) status objects from HTTP request
		$statusList = array();
		for( $i=0; $i < $records; $i++ ) {
			// Create deadlinerelative-fields for multiple input
			$deadlinerelativefields[$i] = new HtmlDiffTimeField( null, 'deadlinerelativefield_' . $i );
			$statusTmp = StatusAdminApp::httpRequestToStatusObj( $publ, $issue, $type,  
									$_REQUEST, $deadlinerelativefields[$i]->requestValue(), $i );
			$statusList[ $statusTmp->SortOrder.'_'.$i ] = $statusTmp;
		}
		krsort( $statusList );
		// perform DB update with posted statusses
		$statusList = BizAdmStatus::modifyStatuses( $statusList );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
}

if( $insert ) {
	$statusIns = StatusAdminApp::httpRequestToStatusObj( $publ, $issue, $type, 
								$_REQUEST, $deadlinerelativefield->requestValue() );
	try {
		$statusIns = BizAdmStatus::createStatus( $statusIns );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'errorins';
	}
}

if( $mode == 'delete' ) {
	try {

		// Check to see if there are statuses that are still linked to Authorizations.
		$id = intval($_REQUEST['id']);

		require_once BASEDIR.'/server/bizclasses/BizCascadePub.class.php';
		BizCascadePub::deleteStatus( $id ); // only for deletions, status id is provided

	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
}

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'states.htm' );

// error handling
$err = '';
foreach ($errors as $error) {
	$err .= $error.'<br/>';
}
$txt = str_replace('<!--ERROR-->', $err, $txt);

if ($mode == 'select') {
	$sAll = BizResources::localize('LIS_ALL');
	$typtxt = '<select name="type" onChange="this.form.submit()">';
	foreach ($typesdomain as $key => $sDisplayType) {
		$typtxt .= '<option value="'.$key.'">'.formvar($sDisplayType).'</option>';
	}
	$typtxt .= '</select>';
	$typtxt .= inputvar( 'add', '1', 'hidden' );
} else {
	$typtxt = formvar($typesdomain[$type]).inputvar('type',$type,'hidden');
}

require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
$publName = DBPublication::getPublicationName( $publ );
$txt = str_replace('<!--VAR:PUBL-->', formvar( $publName ).inputvar('publ',$publ,'hidden'), $txt);

// Remove the Deadline header in the html document if deadlines are not used.
if ( !$useDeadlines ) {
	$txt = preg_replace('/<!--IF:USEDEADLINES-->.*<!--ENDIF:USEDEADLINES-->/is', '', $txt);
}
// Remove the Skip InDesign Server Automation in the html document if the plug-in is not enabled.
if ( !$useSkipIdsa ) {
	$txt = preg_replace('/<!--IF:USESKIPIDSA-->.*<!--ENDIF:USESKIPIDSA-->/is', '', $txt);
}

if ($issue) {
	require_once BASEDIR.'/server/dbclasses/DBIssue.class.php';
	$issueName = DBIssue::getIssueName( $issue );
	$txt = str_replace('<!--VAR:ISSUE-->', formvar( $issueName ).inputvar('issue',$issue,'hidden'), $txt);
} else {
	$txt = preg_replace('/<!--IF:STATE-->.*<!--ENDIF-->/is', '', $txt);
}
$txt = str_replace('<!--VAR:TYPE-->', $typtxt, $txt);

// generate lower part
$detailtxt = '';
$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
$rows = BizAdmStatus::getStatuses( $publ, $issue, $type );
$statedomain = array();
foreach( $rows as $statusTmp ) {
	$statedomain[ $statusTmp->Id ] = $statusTmp->Name;
}
$phasedomain = getPhaseTypeMap();

switch( $mode ) {
	case 'view':
	case 'update':
	case 'delete':
	case 'error':
		if ($mode == 'error') {
			// BZ#25049 - When there is no records from request, take the list of status of the publication.
			// Reason for this is, not to have empty status list.
			if( $records > 0 ) { 
				$rows = array();
				for( $i=0; $i < $records; $i++ ) {
					// Create deadlinerelative-fields for multiple input
					$deadlinerelativefields[$i] = new HtmlDiffTimeField( null, 'deadlinerelativefield_' . $i );
					$rows[] = StatusAdminApp::httpRequestToStatusObj( $publ, $issue, $type, 
													$_REQUEST, $deadlinerelativefields[$i]->requestValue(), $i );
				}
			}
		}
		$i = 0;
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		if ( $rows ) foreach ($rows as $row) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$thisdomain = array();
			// Create deadlinerelative-fields for multiple input
			$deadlinerelativefields[$i] = new HtmlDiffTimeField( null, 'deadlinerelativefield_' . $i );
			if ($statedomain) foreach (array_keys($statedomain) as $state) {
				if( $state != $row->Id ) {
					$thisdomain[$state] = $statedomain[$state];
				} else {
					$thisdomain[0] = '(' . $statedomain[$state] . ')' ;	
				}
			}
			// get number of objects with this state
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$cnt = DBObject::getNumberOfObjectsByStatus( $row->Id );
			$authCount = 0;
			try {
				$authId = intval($row->Id);
				$authCount = BizAdmStatus::getAuthorizationsCountById($authId);
			} catch ( BizException $e ) {
				$errors[] = $e->getMessage();
				$mode = 'error';
			}

			$deltxt = '<a href="states.php?publ='.$publ.'&issue='.$issue.'&type='.urlencode($type).'&delete=1&id='.$row->Id.'" onclick="return myconfirm(\'delstate\', ' . $cnt . ', ' . $authCount . ')">'
					.'<img src="../../config/images/remov_16.gif" border="0" width="16" height="16" title="'.BizResources::localize('ACT_DEL').'"/></a>';
			$tcolor = $row->Color;
			$disableSkipIdsa = true;
			if ( $useSkipIdsa ) {
				require_once BASEDIR.'/server/plugins/IdsAutomation/IdsAutomationUtils.class.php';
				$disableSkipIdsa = !IdsAutomationUtils::isLayoutObjectType( $type );
			}
			$deadlinerelativefields[$i]->setValue( DateTimeFunctions::validRelativeTime( $row->DeadlineRelative ) );
			$detailtxt .=
					'<tr'.$clr.'>'
					.'<td>'.inputvar('code'.$i, $row->SortOrder, 'small').'</td>'
					.'<td>'.inputvar('state'.$i, $row->Name, 'shortname').'</td>'
					.'<td>'.inputvar('produce'.$i, trim($row->Produce), 'checkbox').'</td>'
					.'<td>'.inputvar('createpermanentversion'.$i, trim($row->CreatePermanentVersion), 'checkbox').'</td>'
					.'<td>'.inputvar('removeintermediateversions'.$i, trim($row->RemoveIntermediateVersions), 'checkbox').'</td>'
					//.'<td>'.inputvar('automaticallysendtonext'.$i, trim($row->AutomaticallySendToNext), 'checkbox').'</td>'
					.'<td>'.inputvar('readyforpublishing'.$i, trim($row->ReadyForPublishing), 'checkbox').'</td>';
					if ( $useSkipIdsa ) {
						$detailtxt .= '<td>'.inputvar( 'skipidsa'.$i, trim( $row->SkipIdsa ), 'checkbox', null, true, null, $disableSkipIdsa ).'</td>';
					}
					$detailtxt .='<td>'.inputvar('nextstate'.$i, $row->NextStatusId, 'combo', $thisdomain, false).'</td>'
					.'<td>'.inputvar('phase'.$i, $row->Phase, 'combo', $phasedomain, false).'</td>'
					//.'<td id="colorpicker'.$i.'" bgcolor="'.$tcolor.'"><input type="hidden" name="color'.$i.'" value="'.$tcolor.'"/>&nbsp;&nbsp;</td>'
					//."<td><a href='#' onclick=\"cp2.select(document.forms[0].color$i, document.getElementById('colorpicker$i'), 'pick$i');return false;\" name='pick$i' id='pick$i'>".BizResources::localize('PICK').'</a></td>'
					.'<td align="center">'
						."<a href='#' onclick=\"cp2.select(document.forms[0].color$i, document.getElementById('colorpicker$i'), 'pick$i');return false;\" name='pick$i' id='pick$i'>"
							.inputvar("color$i",$tcolor,'hidden').'<table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td id="colorpicker'.$i.'" bgcolor="'.$tcolor.'"></td></tr></table></td>'
						.'</a></td>';

			        // Only show the deadline fields if the brand has the calculation of deadlines set to enabled.
					if ( $useDeadlines ) {
						$detailtxt .= '<td>'.$deadlinerelativefields[$i]->drawBody().'</td>';
					}
					$detailtxt .= '<td>'.$deltxt.'</td>'
				.'</tr>';
			$detailtxt .= inputvar("id$i",$row->Id,'hidden');
			$i++;
		}
		$detailtxt .= inputvar('recs',$i,'hidden');
		break;
	case 'add':
		$row = array("code"=>'', "state" => '', "produce" => '', 'nextstate' => '', "phase" => $phasedomain['Production'], 'color' => '#a0a0a0',
				'deadlinerelative' => '', 'createpermanentversion' => '', 'automaticallysendtonext' => '',
				'removeintermediateversions' => '', 'readyforpublishing' => '', 'skipidsa' => '' );
		// no break
	case 'errorins':
		if ($mode == 'errorins') {
			$row = array( 'code' => $statusIns->SortOrder, 'state' => $statusIns->Name, 'phase' => $statusIns->Phase,
				'produce' => $statusIns->Produce, 'nextstate' => $statusIns->NextStatusId, 'color' => $statusIns->Color,
				'deadlinerelative' => (isset($_REQUEST['deadlinerelative']) ? $_REQUEST['deadlinerelative'] : ''),
				'createpermanentversion' => $statusIns->CreatePermanentVersion, 
				'removeintermediateversions' => $statusIns->RemoveIntermediateVersions,
				'automaticallysendtonext' => $statusIns->AutomaticallySendToNext,
				'readyforpublishing' => $statusIns->ReadyForPublishing,
				'skipidsa' => $statusIns->SkipIdsa,
				);
		}
		// 1 row to enter new record
		$tcolor = $row['color'];
		$deadlinerelativefield->setValue( DateTimeFunctions::validRelativeTime( $row['deadlinerelative'] ) );
		$disableSkipIdsa = true;
		if ( $useSkipIdsa ) {
			require_once BASEDIR.'/server/plugins/IdsAutomation/IdsAutomationUtils.class.php';
			$disableSkipIdsa = !IdsAutomationUtils::isLayoutObjectType( $type );
		}
		$detailtxt .=
			'<tr>'
				.'<td>'.inputvar('code', $row['code'], 'small').'</td>'
				.'<td>'.inputvar('state', $row['state'], 'shortname').'</td>'
				.'<td>'.inputvar('produce',trim($row['produce']), 'checkbox').'</td>'
				.'<td>'.inputvar('createpermanentversion',trim($row['createpermanentversion']), 'checkbox').'</td>'
				.'<td>'.inputvar('removeintermediateversions',trim($row['removeintermediateversions']), 'checkbox').'</td>'
				//.'<td>'.inputvar('automaticallysendtonext',trim($row['automaticallysendtonext']), 'checkbox').'</td>'
				.'<td>'.inputvar('readyforpublishing',trim($row['readyforpublishing']), 'checkbox').'</td>';
				if ( $useSkipIdsa ) {
					$detailtxt .= '<td>'.inputvar( 'skipidsa', trim( $row['skipidsa'] ), 'checkbox', null, true, null, $disableSkipIdsa ).'</td>';
				}
				$detailtxt .= '<td>'.inputvar('nextstate', $row['nextstate'], 'combo', $statedomain).'</td>'
				.'<td>'.inputvar('phase', $row['phase'], 'combo', $phasedomain, false).'</td>'
				//.'<td id="colorpicker" bgcolor="'.$tcolor.'"><input type="hidden" name="color" value="'.$tcolor.'"/>&nbsp;&nbsp;</td>'
				//."<td><a href='#' onclick=\"cp2.select(document.forms[0].color, document.getElementById('colorpicker'), 'pick');return false;\" name='pick' id='pick'>".BizResources::localize('PICK').'</a></td>'
				.'<td align="center">'
					."<a href='#' onclick=\"cp2.select(document.forms[0].color, document.getElementById('colorpicker'), 'pick');return false;\" name='pick' id='pick'>"
						.'<input type="hidden" name="color" value="'.$tcolor.'"/><table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td id="colorpicker" bgcolor="'.$tcolor.'"></td></tr></table></td>'
					.'</a></td>';
				// Only show the deadline inputs if the calculation of deadlines is enabled for the brand.
		        if ( $useDeadlines ) {
					$detailtxt .= '<td>'.$deadlinerelativefield->drawBody().'</td>';
				}
				$detailtxt .= '<td></td>'
			.'</tr>';
		$detailtxt .= inputvar('insert','1','hidden');

		// show other states as info
		require_once BASEDIR.'/server/dbclasses/DBStates.class.php';
		$stateRows = DBStates::getStates( $publ, $issue, $type );
		$color = array (' bgcolor="#eeeeee"', '');
		$flip = 0;
		if ( $stateRows ) foreach( $stateRows as $row ) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$tcolor = $row['color'];
			$detailtxt .=
				'<tr'.$clr.'>'
					.'<td>'.$row['code'].'</td>'
					.'<td>'.formvar($row['state']).'</td>'
					.'<td>'.(trim($row['produce'])?CHECKIMAGE:'').'</td>'
					.'<td>'.(trim($row['createpermanentversion'])?CHECKIMAGE:'').'</td>'
					.'<td>'.(trim($row['removeintermediateversions'])?CHECKIMAGE:'').'</td>'
					//.'<td>'.(trim($row['automaticallysendtonext'])?CHECKIMAGE:'').'</td>'
					.'<td>'.(trim($row['readyforpublishing'])?CHECKIMAGE:'').'</td>'
					.'<td>'.(trim($row['skipidsa'])?CHECKIMAGE:'').'</td>'
					.'<td>'.(trim($row['nextstate'])?$statedomain[$row['nextstate']]:'').'</td>'
					.'<td>'.(trim($row['phase'])?$phasedomain[$row['phase']]:'').'</td>'
					//.'<td bgcolor="'.$tcolor.'">&nbsp;&nbsp;</td>'
					.'<td align="center"><table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td id="colorpicker" bgcolor="'.$tcolor.'"></td></tr></table></td>'
					.'<td></td>'
					.'<td>'.DateTimeFunctions::relativeDate( $row['deadlinerelative'] ).'</td>'
				.'</tr>';
		}
		break;
}

// generate total page
$txt = str_replace("<!--ROWS-->", $detailtxt, $txt);
if ($issue) {
	$back = "hppublissues.php?id=$issue";
} else {
	$back = "hppublications.php?id=$publ";
}
$txt = str_replace("<!--BACK-->", $back, $txt);
print HtmlDocument::buildDocument($txt);

function isIdsaUsed()
{
	require_once BASEDIR . '/server/bizclasses/BizServerPlugin.class.php';
	$pluginObj = BizServerPlugin::getPluginForConnector( 'IdsAutomation_WflGetObjects' );
	if( $pluginObj && $pluginObj->IsInstalled ) {
		if( !$pluginObj->IsActive ) {
			return false;
		}
	} else {
		return false;
	}

	return true;
}

class StatusAdminApp
{
	/**
	 *  Converts a HTTP request into a admin status object.
	 *
	 * @param array $req HTTP request
	 * @param string $i Index; The HTTP request and some indexed properties which vary per status. Others are not indexed, see(*).
	 * @return object $obj Admin status object
	 **/
	static public function httpRequestToStatusObj( $publ, $issue, $type, $req, $deadlineRel, $i = '' )
	{
		$obj = new stdClass();
		$obj->Id = isset( $req[ 'id'.$i ] ) ? intval( $req[ 'id'.$i ] ) : 0;
		$obj->PublicationId = $publ;
		$obj->Type = $type;
		$obj->Phase = $req[ 'phase'.$i ];
		$obj->Name = trim( stripslashes( $req[ 'state'.$i ] ) );
		$obj->Produce = ( isset( $req[ 'produce'.$i ] ) && $req[ 'produce'.$i ] == 'on' ? true : false );
		$obj->Color = preg_match( '/^#[a-f0-9]{6}$/i', $req[ 'color'.$i ] ) ? $req[ 'color'.$i ] : '#A0A0A0';
		// Validate hex color (starts with # plus 6 alphanumeric characters, BZ#31651.
		$obj->NextStatusId = intval( $req[ 'nextstate'.$i ] );
		$obj->SortOrder = intval( $req[ 'code'.$i ] );
		$obj->IssueId = $issue;
		$obj->SectionId = 0; // not supported
		$obj->DeadlineStatusId = $deadlineRel ? $obj->Id : 0;
		$obj->DeadlineRelative = $deadlineRel;
		$obj->CreatePermanentVersion = ( isset( $req[ 'createpermanentversion'.$i ] ) && $req[ 'createpermanentversion'.$i ] == 'on' ? true : false );
		$obj->RemoveIntermediateVersions = ( isset( $req[ 'removeintermediateversions'.$i ] ) && $req[ 'removeintermediateversions'.$i ] == 'on' ? true : false );
		$obj->AutomaticallySendToNext = ( isset( $req[ 'automaticallysendtonext'.$i ] ) && $req[ 'automaticallysendtonext'.$i ] == 'on' ? true : false );
		$obj->ReadyForPublishing = ( isset( $req[ 'readyforpublishing'.$i ] ) && $req[ 'readyforpublishing'.$i ] == 'on' ? true : false );
		$obj->SkipIdsa = ( isset( $req[ 'skipidsa'.$i ] ) && $req[ 'skipidsa'.$i ] == 'on' ? true : false );
		return $obj;
		// *Those properties are the same per submit, so these are not indexed.
	}

}