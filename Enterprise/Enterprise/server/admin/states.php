<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDiffTimeField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';

define( 'HTML_EOL', "\r\n" );

$ticket = checkSecure('publadmin');

// domains
$typesDomain = getObjectTypeMap();
asort($typesDomain);

//create 1 deadlineRelativeFields for single input
$deadlineRelativeField = new HtmlDiffTimeField( null, 'deadlinerelativefield' );

//create 100 deadlineRelative-fields for multiple input
$deadlineRelativeFields = array();
for( $i=0; $i<100; $i++ ) {
	$deadlineRelativeFields[$i] = new HtmlDiffTimeField( null, 'deadlinerelativefield_' . $i );
}

// determine incoming mode
$pubId  = isset($_REQUEST['publ'])  ? intval($_REQUEST['publ'])  : 0; // zero should never happen
$issueId = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0; // zero for overruled issues
$type  = isset($_REQUEST['type'])  ? $_REQUEST['type']  : ''; // empty for all types for pub/issue

// Check the brand to see if Deadline calculation is enabled, if it isn't the deadline fields on the states
// should be hidden.
require_once BASEDIR.'/server/dbclasses/DBAdmPublication.class.php';
$admPublObj = DBAdmPublication::getPublicationObj( $pubId );
$useDeadlines = false;
if ($admPublObj ) {
	$useDeadlines = ( $admPublObj->CalculateDeadlines );
}
$useSkipIdsa = isIdsaUsed();

$records = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;
$insert = isset($_REQUEST['insert']) ? (bool)$_REQUEST['insert'] : false;

// check publication rights
checkPublAdmin( $pubId );

// determine incoming mode
if( !isset( $_REQUEST['type'] ) ) {
	$mode = 'select';
} elseif( isset( $_REQUEST['update'] ) && $_REQUEST['update'] ) {
	$mode = 'update';
} elseif( isset( $_REQUEST['delete'] ) && $_REQUEST['delete'] ) {
	$mode = 'delete';
} elseif( isset($_REQUEST['add'] ) && $_REQUEST['add'] ) {
	$mode = 'add';
} else {
	$mode = 'view';
}
$errors = array();

// handle status updates
if( $records ) {
	try {
		// build list of (sorted) status objects from HTTP request
		$statusList = array();
		for( $i=0; $i < $records; $i++ ) {
			$statusTmp = StatusAdminApp::httpRequestToStatusObj( $type,	$_REQUEST, $deadlineRelativeFields[$i]->requestValue(), $i );
			$statusList[] = $statusTmp;
		}
		krsort( $statusList );

		// send modify request to service layer
		require_once BASEDIR.'/server/services/adm/AdmModifyStatusesService.class.php';
		$request = new AdmModifyStatusesRequest();
		$request->Ticket = $ticket;
		$request->Statuses = $statusList;
		$service = new AdmModifyStatusesService();
		$response = $service->execute( $request );
		$statusList = $response->Statuses;
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
}

if( $insert ) {
	$statusIns = StatusAdminApp::httpRequestToStatusObj( $type, $_REQUEST, $deadlineRelativeField->requestValue() );
	try {
		require_once BASEDIR.'/server/services/adm/AdmCreateStatusesService.class.php';
		$request = new AdmCreateStatusesRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $pubId;
		$request->IssueId = $issueId;
		$request->Statuses = array( $statusIns );
		$service = new AdmCreateStatusesService();
		$response = $service->execute( $request );
		$statusIns = reset( $response->Statuses );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'errorins';
	}
}

if( $mode == 'delete' ) {
	try {
		// Check to see if there are statuses that are still linked to Authorizations.
		$id = intval($_REQUEST['id']);

		require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';
		$request = new AdmDeleteStatusesRequest();
		$request->Ticket = $ticket;
		$request->StatusIds = array( $id );
		$service = new AdmDeleteStatusesService();
		$service->execute( $request );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
}

// generate upper part (info or select fields)
$txt = HtmlDocument::loadTemplate( 'states.htm' );

if( $mode == 'select' ) {
	$sAll = BizResources::localize( 'LIS_ALL' );
	$typtxt = '<select name="type" onChange="this.form.submit()">';
	foreach( $typesDomain as $key => $sDisplayType ) {
		$typtxt .= '<option value="'.$key.'">'.formvar( $sDisplayType ).'</option>'.PHP_EOL;
	}
	$typtxt .= '</select>';
	$typtxt .= inputvar( 'add', '1', 'hidden' );
} else {
	$typtxt = formvar( $typesDomain[$type] ).inputvar( 'type', $type, 'hidden' );
}

// Add a publication field to the form
try {
	require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
	$request = new AdmGetPublicationsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$request->PublicationIds = array($pubId);
	$service = new AdmGetPublicationsService();
	$response = $service->execute( $request );
	$pubObj = $response->Publications[0];
	$txt = str_replace( '<!--VAR:PUBL-->', formvar( $pubObj->Name ).inputvar( 'publ', $pubId, 'hidden' ), $txt );
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
}

// Remove the Deadline header in the html document if deadlines are not used.
if ( !$useDeadlines ) {
	$txt = preg_replace('/<!--IF:USEDEADLINES-->.*<!--ENDIF:USEDEADLINES-->/is', '', $txt);
}
// Remove the Skip InDesign Server Automation in the html document if the plug-in is not enabled.
if ( !$useSkipIdsa ) {
	$txt = preg_replace('/<!--IF:USESKIPIDSA-->.*<!--ENDIF:USESKIPIDSA-->/is', '', $txt);
}

if( $issueId ) {
	//Add an issue field to the form
	try {
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
		$request = new AdmGetIssuesRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array();
		$request->PublicationId = $pubId;
		$request->IssueIds = array($issueId);
		$service = new AdmGetIssuesService();
		$response = $service->execute( $request );
		$issueObj = $response->Issues[0];
		$txt = str_replace( '<!--VAR:ISSUE-->', formvar( $issueObj->Name ).inputvar( 'issue', $issueId, 'hidden' ), $txt );
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}

} else {
	$txt = preg_replace( '/<!--IF:STATE-->.*<!--ENDIF-->/is', '', $txt );
}
$txt = str_replace( '<!--VAR:TYPE-->', $typtxt, $txt );

// generate lower part
$detailTxt = '';
$boxSize = preg_match( "/safari/", strtolower( $_SERVER['HTTP_USER_AGENT'] ) ) ? 10 : 13;

try {
	require_once BASEDIR.'/server/services/adm/AdmGetStatusesService.class.php';
	$request = new AdmGetStatusesRequest();
	$request->Ticket = $ticket;
	$request->PublicationId = $pubId;
	$request->IssueId = $issueId;
	if( $type ) {
		$request->ObjectType = $type;
	}
	$service = new AdmGetStatusesService();
	$response = $service->execute( $request );
	$statuses = $response->Statuses;
} catch( BizException $e ) {
	$errors[] = $e->getMessage();
	$mode = 'error';
	$statuses = null;
}
$statusDomain = array();
if( $statuses ) foreach( $statuses as $statusTmp ) {
	$statusDomain[$statusTmp->Id] = $statusTmp->Name;
}

// error handling
$err = '';
if( $errors ) foreach( $errors as $error ) {
	$err .= $error.'<br/>';
}
$phasedomain = getPhaseTypeMap();
$txt = str_replace( '<!--ERROR-->', $err, $txt );

switch( $mode ) {
	case 'view':
	case 'update':
	case 'delete':
	case 'error':
		if( $mode == 'error' ) {
			// BZ#25049 - When there is no records from request, take the list of status of the publication.
			// Reason for this is, not to have empty status list.
			if( $records > 0 ) {
				$statuses = array();
				for( $i=0; $i < $records; $i++ ) {
					$statuses[] = StatusAdminApp::httpRequestToStatusObj( $type, $_REQUEST, $deadlineRelativeFields[$i]->requestValue(), $i );
				}
			}
		}
		$i = 0;
		$color = array( " bgcolor='#eeeeee'", '' );
		$flip = 0;
		if( $statuses ) foreach( $statuses as $status ) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$thisDomain = array();
			if( $statusDomain ) foreach( array_keys( $statusDomain ) as $state ) {
				if( $state != $status->Id ) {
					$thisDomain[$state] = $statusDomain[$state];
				} else {
					$thisDomain[0] = '(' . $statusDomain[$state] . ')' ;
				}
			}

			require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
			$request = new WflQueryObjectsRequest();
			$request->Ticket = $ticket;
			$request->Params = array( new QueryParam( 'State', '=', $status->Id ) );
			$request->Areas = array( 'Workflow' );
			$request->RequestProps = array( 'ID', 'Type', 'Name' );
			$service = new WflQueryObjectsService();
			$response = $service->execute( $request );
			$arrayOfWflRow = $response->Rows;
			$request->Areas = array( 'Trash' );
			$response = $service->execute( $request );
			$arrayOfTrashRow = $response->Rows;
			$cnt = ( $arrayOfWflRow ) ? count( $arrayOfWflRow ) : 0;
			$cnt += ( $arrayOfTrashRow ) ? count( $arrayOfTrashRow ) : 0;

			$authCount = 0;
			try {
				$authId = intval( $status->Id );
				$authCount = BizAdmStatus::getAuthorizationsCountById( $authId );
			} catch ( BizException $e ) {
				$errors[] = $e->getMessage();
				$mode = 'error';
			}

			$deltxt = '<a href="states.php?publ='.$pubId.'&issue='.$issueId.'&type='.urlencode($type).'&delete=1&id='.$status->Id.'" onclick="return myconfirm(\'delstate\', ' . $cnt . ', ' . $authCount . ')">'.HTML_EOL
					.'<img src="../../config/images/remov_16.gif" border="0" width="16" height="16" title="'.BizResources::localize('ACT_DEL').'"/></a>'.HTML_EOL;
			$tcolor = '#'.$status->Color;

			$nextStatusId = $status->NextStatus ? intval( $status->NextStatus->Id ) : 0;

			$disableSkipIdsa = true;
			if ( $useSkipIdsa ) {
				require_once BASEDIR.'/server/plugins/IdsAutomation/IdsAutomationUtils.class.php';
				$disableSkipIdsa = !IdsAutomationUtils::isLayoutObjectType( $type );
			}



			$deadlineRelativeFields[$i]->setValue( $status->DeadlineRelative );

			$detailTxt .=
				'<tr'.$clr.'>'.HTML_EOL
					.'<td>'.inputvar('code'.$i, $status->SortOrder, 'small').'</td>'.HTML_EOL
					.'<td>'.inputvar('state'.$i, $status->Name, 'shortname').'</td>'.HTML_EOL
					.'<td>'.inputvar('produce'.$i, trim( $status->Produce ), 'checkbox').'</td>'.HTML_EOL
					.'<td>'.inputvar('createpermanentversion'.$i, trim($status->CreatePermanentVersion), 'checkbox').'</td>'.HTML_EOL
					.'<td>'.inputvar('removeintermediateversions'.$i, trim($status->RemoveIntermediateVersions), 'checkbox').'</td>'.HTML_EOL
					//.'<td>'.inputvar('automaticallysendtonext'.$i, trim($status->AutomaticallySendToNext), 'checkbox').'</td>'.HTML_EOL
					.'<td>'.inputvar('readyforpublishing'.$i, trim($status->ReadyForPublishing), 'checkbox').'</td>'.HTML_EOL;
					if ( $useSkipIdsa ) {
						$detailtxt .= '<td>'.inputvar( 'skipidsa'.$i, trim( $status->SkipIdsa ), 'checkbox', null, true, null, $disableSkipIdsa ).'</td>';
					}
			$detailTxt .=
					'<td>'.inputvar('nextstate'.$i, $nextStatusId, 'combo', $thisDomain, false).'</td>'.HTML_EOL
					.'<td>'.inputvar('phase'.$i, $status->Phase, 'combo', $phasedomain, false).'</td>'.HTML_EOL
					//.'<td id="colorpicker'.$i.'" bgcolor="'.$tcolor.'"><input type="hidden" name="color'.$i.'" value="'.$tcolor.'"/>&nbsp;&nbsp;</td>'.HTML_EOL
					//."<td><a href='#' onclick=\"cp2.select(document.forms[0].color$i, document.getElementById('colorpicker$i'), 'pick$i');return false;\" name='pick$i' id='pick$i'>".BizResources::localize('PICK').'</a></td>'.HTML_EOL
					.'<td align="center">'.HTML_EOL
						."<a href='#' onclick=\"cp2.select(document.forms[0].color$i, document.getElementById('colorpicker$i'), 'pick$i');return false;\" name='pick$i' id='pick$i'>".HTML_EOL
							.inputvar("color$i",$tcolor,'hidden').'<table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td id="colorpicker'.$i.'" bgcolor="'.$tcolor.'"></td></tr></table></td>'.HTML_EOL
						.'</a></td>'.HTML_EOL;

			        // Only show the deadline fields if the brand has the calculation of deadlines set to enabled.
					if ( $useDeadlines ) {
						$detailTxt .= '<td>'.$deadlineRelativeFields[$i]->drawBody().'</td>'.HTML_EOL;
					}
					$detailTxt .= '<td>'.$deltxt.'</td>'.HTML_EOL;
					$detailTxt .= '</tr>'.HTML_EOL.HTML_EOL;
			$detailTxt .= inputvar("id$i",$status->Id,'hidden').HTML_EOL;
			$i++;
		}
		$detailTxt .= inputvar('recs',$i,'hidden').HTML_EOL;
		break;
	case 'add':
		$row = array("code"=>'', "state" => '', "produce" => '', 'nextstate' => '', "phase" => $phasedomain['Production'], 'color' => '#a0a0a0',
				'deadlinerelative' => '', 'createpermanentversion' => '', 'automaticallysendtonext' => '',
				'removeintermediateversions' => '', 'readyforpublishing' => '', 'skipidsa' => '' );
		// no break
	case 'errorins':
		if ($mode == 'errorins') {
			$row = array(
				'code' => $statusIns->SortOrder, 'state' => $statusIns->Name, 'produce' => $statusIns->Produce,
				'nextstate' => ($statusIns->NextStatus) ? $statusIns->NextStatus->Id : 0, 'color' => $statusIns->Color,
				'createpermanentversion' => $statusIns->CreatePermanentVersion, 
				'removeintermediateversions' => $statusIns->RemoveIntermediateVersions,
				'automaticallysendtonext' => $statusIns->AutomaticallySendToNext,
				'readyforpublishing' => $statusIns->ReadyForPublishing,
				'skipidsa' => $statusIns->SkipIdsa,
				);
		}
		// 1 row to enter new record
		$tcolor = $row['color'];
		$deadlineRelativeField->setValue( DateTimeFunctions::validRelativeTime( $row['deadlinerelative'] ) );
		$detailTxt .=
			'<tr>'.HTML_EOL
				.'<td>'.inputvar('code', $row['code'], 'small').'</td>'.HTML_EOL
				.'<td>'.inputvar('state', $row['state'], 'shortname').'</td>'.HTML_EOL
				.'<td>'.inputvar('produce',trim($row['produce']), 'checkbox').'</td>'.HTML_EOL
				.'<td>'.inputvar('createpermanentversion',trim($row['createpermanentversion']), 'checkbox').'</td>'.HTML_EOL
				.'<td>'.inputvar('removeintermediateversions',trim($row['removeintermediateversions']), 'checkbox').'</td>'.HTML_EOL
				//.'<td>'.inputvar('automaticallysendtonext',trim($row['automaticallysendtonext']), 'checkbox').'</td>'.HTML_EOL
				.'<td>'.inputvar('readyforpublishing',trim($row['readyforpublishing']), 'checkbox').'</td>'.HTML_EOL;
				if ( $useSkipIdsa ) {
					$detailtxt .= '<td>'.inputvar( 'skipidsa', trim( $row['skipidsa'] ), 'checkbox', null, true, null, $disableSkipIdsa ).'</td>';
				}
		$detailTxt .=
				'<td>'.inputvar('nextstate', $row['nextstate'], 'combo', $statusDomain).'</td>'.HTML_EOL
				.'<td>'.inputvar('phase', $row['phase'], 'combo', $phasedomain, false).'</td>'.HTML_EOL
				//.'<td id="colorpicker" bgcolor="'.$tcolor.'"><input type="hidden" name="color" value="'.$tcolor.'"/>&nbsp;&nbsp;</td>'.HTML_EOL
				//."<td><a href='#' onclick=\"cp2.select(document.forms[0].color, document.getElementById('colorpicker'), 'pick');return false;\" name='pick' id='pick'>".BizResources::localize('PICK').'</a></td>'.HTML_EOL
				.'<td align="center">'.HTML_EOL
					."<a href='#' onclick=\"cp2.select(document.forms[0].color, document.getElementById('colorpicker'), 'pick');return false;\" name='pick' id='pick'>".HTML_EOL
						.'<input type="hidden" name="color" value="'.$tcolor.'"/><table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td id="colorpicker" bgcolor="'.$tcolor.'"></td></tr></table></td>'.HTML_EOL
					.'</a></td>'.HTML_EOL;
				// Only show the deadline inputs if the calculation of deadlines is enabled for the brand.
		        if ( $useDeadlines ) {
					$detailTxt .= '<td>'.$deadlineRelativeField->drawBody().'</td>'.HTML_EOL;
				}
				$detailTxt .= '<td></td>'.HTML_EOL;
				$detailTxt .= '</tr>'.HTML_EOL.HTML_EOL;
		$detailTxt .= inputvar('insert','1','hidden').HTML_EOL;

		require_once BASEDIR.'/server/services/adm/AdmGetStatusesService.class.php';
		$request = new AdmGetStatusesRequest();
		$request->Ticket = $ticket;
		$request->PublicationId = $pubId;
		$request->IssueId = $issueId;
		$request->ObjectType = $type;
		$service = new AdmGetStatusesService();
		$response = $service->execute( $request );
		$statuses = $response->Statuses;

		$color = array (' bgcolor="#eeeeee"', '');
		$flip = 0;
		foreach( $statuses as $status ) {
			$clr = $color[$flip];
			$flip = 1- $flip;
			$tcolor = $status->Color;
			$nextStatusId = isset( $status->NextStatus->Id ) ? intval( $status->NextStatus->Id ) : 0;
			$detailTxt .=
				'<tr'.$clr.'>'.HTML_EOL
					.'<td>'.$status->SortOrder.'</td>'.HTML_EOL
					.'<td>'.formvar( $status->Name ).'</td>'.HTML_EOL
					.'<td>'.( trim( $status->Produce ) ? CHECKIMAGE : '' ).'</td>'.HTML_EOL
					.'<td>'.( trim( $status->CreatePermanentVersion ) ? CHECKIMAGE : '' ).'</td>'.HTML_EOL
					.'<td>'.( trim( $status->RemoveIntermediateVersions )?CHECKIMAGE:'' ).'</td>'.HTML_EOL
					//.'<td>'.(trim($status->AutomaticallySendToNext)?CHECKIMAGE:'').'</td>'.HTML_EOL
					.'<td>'.( trim( $status->ReadyForPublishing ) ? CHECKIMAGE : '' ).'</td>'.HTML_EOL
					.'<td>'.( trim( $status->SkipIdsa ) ? CHECKIMAGE : '' ).'</td>'
					.'<td>'.( $nextStatusId ? $statusDomain[$nextStatusId] : '' ).'</td>'.HTML_EOL
					.'<td>'.(trim( $status->Phase )?$phasedomain[$status->Phase]:'').'</td>'.HTML_EOL
					//.'<td bgcolor="'.$tcolor.'">&nbsp;&nbsp;</td>'.HTML_EOL
					.'<td align="center"><table border="1" style="border-collapse: collapse" bordercolor="#606060" height="'.$boxSize.'" width="'.$boxSize.'"><tr><td id="colorpicker" bgcolor="'.$tcolor.'"></td></tr></table></td>'.HTML_EOL
					.'<td>'.DateTimeFunctions::relativeDate( $status->DeadlineRelative ).'</td>'.HTML_EOL
				.'</tr>'.HTML_EOL.HTML_EOL;
		}
		break;
}

// generate total page
$txt = str_replace("<!--ROWS-->", $detailTxt, $txt);
if ($issueId) {
	$back = "hppublissues.php?id=$issueId";
} else {
	$back = "hppublications.php?id=$pubId";
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
	 * Converts a HTTP request into a admin status object.
	 *
	 * @param string $type The ObjectType
	 * @param array $req HTTP request
	 * @param string|integer $deadlineRel
	 * @param string $i Index; The HTTP request and some indexed properties which vary per status. Others are not indexed, see(*).
	 * @return object $obj Admin status object
	**/
	static public function httpRequestToStatusObj( $type, $req, $deadlineRel, $i = '' )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$obj = new AdmStatus();
		$obj->Id                    = isset( $req['id'.$i] ) ? intval($req['id'.$i]) : 0;
		$obj->Type                = $type; // *
		$obj->Phase		      = $req['phase'.$i];
		$obj->Name              = trim( stripslashes( $req['state'.$i] ) );
		$obj->Produce           = (isset($req['produce'.$i]) && $req['produce'.$i] == 'on' ? true : false);
		// Validate hex color (starts with # plus 6 alphanumeric characters, BZ#31651.
		$obj->Color             = preg_match('/^#[a-f0-9]{6}$/i', $req['color'.$i]) ? substr($req['color'.$i], 1) : 'A0A0A0'; //remove # to make service validator happy
		$obj->NextStatus         = (intval($req['nextstate'.$i])) ? new AdmIdName( intval($req['nextstate'.$i]), '') : null;
		$obj->SortOrder         = intval($req['code'.$i]);
		$obj->DeadlineRelative  = $deadlineRel;
		$obj->CreatePermanentVersion     = (isset( $req['createpermanentversion'.$i] )     && $req['createpermanentversion'.$i]     == 'on' ? true : false);
		$obj->RemoveIntermediateVersions = (isset( $req['removeintermediateversions'.$i] ) && $req['removeintermediateversions'.$i] == 'on' ? true : false);
		$obj->AutomaticallySendToNext    = (isset( $req['automaticallysendtonext'.$i] )    && $req['automaticallysendtonext'.$i]    == 'on' ? true : false);
		$obj->ReadyForPublishing         = (isset( $req['readyforpublishing'.$i] )         && $req['readyforpublishing'.$i]         == 'on' ? true : false);
		$obj->SkipIdsa                   = (isset( $req['skipidsa'.$i] )                   && $req['skipidsa'.$i]                   == 'on' ? true : false);
		return $obj;
		// *Those properties are the same per submit, so these are not indexed.
	}

}
