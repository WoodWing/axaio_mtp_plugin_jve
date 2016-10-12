<?php	 
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
$ticket = checkSecure('admin');

require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
$sessionUser = DBTicket::checkTicket( $ticket );

// get param's
$id          = isset($_REQUEST['id'])       ? intval($_REQUEST['id']) : 0;
$user        = isset($_REQUEST['user'])     ? $_REQUEST['user'] : '';
$fullname    = isset($_REQUEST['fullname']) ? $_REQUEST['fullname'] : '';
$disable     = isset($_REQUEST['disable'])  && $_REQUEST['disable'] ? 'on'  : '';
$email       = isset($_REQUEST['email'])    ? $_REQUEST['email'] : '';
$emailusr    = isset($_REQUEST['emailusr']) && $_REQUEST['emailusr']  ? 'on' : '';
$emailgrp    = isset($_REQUEST['emailgrp']) && $_REQUEST['emailgrp']  ? 'on' : '';
$fixedpass   = isset($_REQUEST['fixedpass'])&& $_REQUEST['fixedpass'] ? 'on' : '';
$pass		 = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
// do not set password if $pass is empty string
if (empty($pass)){
	// pass null/nil to the admin service, this prevents setting the password
	$pass = null;
}
$organization= isset($_REQUEST['organization']) ? $_REQUEST['organization'] : '';
$location	 = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';

$inpstartdate = isset($_REQUEST['startdate'])      ? $_REQUEST['startdate'] : '';
$startyear	 = isset($_REQUEST['startdate_years']) ? intval($_REQUEST['startdate_years']) : 0;
$startmonth  = isset($_REQUEST['startdate_months'])? intval($_REQUEST['startdate_months']) : 0;
$startday	 = isset($_REQUEST['startdate_days'])  ? intval($_REQUEST['startdate_days']): 0;

$inpenddate  = isset($_REQUEST['enddate'])         ? $_REQUEST['enddate'] : '';
$endyear	 = isset($_REQUEST['enddate_years'])   ? intval($_REQUEST['enddate_years']) : 0;
$endmonth  	 = isset($_REQUEST['enddate_months'])  ? intval($_REQUEST['enddate_months']) : 0;
$endday	 	 = isset($_REQUEST['enddate_days'])    ? intval($_REQUEST['enddate_days']) : 0;

$expiredays  = isset($_REQUEST['expiredays'])      ? intval($_REQUEST['expiredays']) : 0;
$newlanguage = isset($_REQUEST['newlanguage'])     ? $_REQUEST['newlanguage'] : '';
$usercolor   = isset($_REQUEST['color1'])          ? $_REQUEST['color1'] : '';
$groupId     = isset($_REQUEST['grpid'])           ? intval($_REQUEST['grpid']) : 0;

// determine incoming mode
if (isset($_REQUEST['update']) && $_REQUEST['update']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['deldet']) && $_REQUEST['deldet']) {
	$mode = 'deldet';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}

$errors = $errors2 = $errors3 = $errors4 = $errors5 = $errors6 = array();

if ($startyear && $startmonth && $startday) {
	$startdate = DateTimeFunctions::validDate($startday . '-' . $startmonth . '-' . $startyear, false);	
} else {
	$startdate = DateTimeFunctions::validDate($inpstartdate, false);
}
if ($endyear && $endmonth && $endday) {
	$enddate = DateTimeFunctions::validDate($endday . '-' . $endmonth . '-' . $endyear, false);	
} else {
	$enddate = DateTimeFunctions::validDate($inpenddate, false);
}

if (!$startdate && $inpstartdate) {
	$mode = 'error';
	$sErrorMessage = BizResources::localize("INVALID_DATE");
	$errors5[] = $sErrorMessage;
}
if (!$enddate && $inpenddate) {
	$mode = 'error';
	$sErrorMessage = BizResources::localize("INVALID_DATE");
	$errors6[] = $sErrorMessage;
}
if ( DateTimeFunctions::diffIsoTimes( $startdate, $enddate ) > 0)
{
	$mode = 'error';
	$sErrorMessage = BizResources::localize("INVALID_DATE");
	$errors6[] = $sErrorMessage;
}


BizSession::startSession( $ticket );

// handle request
try {
	switch( $mode ) {
		case 'insert':
			require_once BASEDIR . '/server/services/adm/AdmCreateUsersService.class.php';
			require_once BASEDIR . '/server/interfaces/services/adm/AdmCreateUsersRequest.class.php';
			$userObjs = array( new AdmUser( null, $user, $fullname, $disable == 'on', $pass,
			 					    $fixedpass == 'on', $email, $emailusr == 'on', $emailgrp == 'on',
			 					    $expiredays, $startdate, $enddate, $newlanguage,
			 					    substr($usercolor,1), $organization, $location, null));
			$service = new AdmCreateUsersService();
			$request = new AdmCreateUsersRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->Users  = $userObjs;
			$response = $service->execute($request);
			$id = $response->Users[0]->Id; //We only get one user back
			break;
		case 'update':
			require_once BASEDIR . '/server/services/adm/AdmModifyUsersService.class.php';
			require_once BASEDIR . '/server/interfaces/services/adm/AdmModifyUsersRequest.class.php';
			$userObjs = array( new AdmUser($id, $user, $fullname, $disable == 'on', $pass,
			 					    $fixedpass == 'on', $email, $emailusr == 'on', $emailgrp == 'on',
			 					    $expiredays, $startdate, $enddate, $newlanguage,
			 					    substr($usercolor,1), $organization, $location, null));
			$service = new AdmModifyUsersService();
			$request = new AdmModifyUsersRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->Users  = $userObjs;
			$response = $service->execute($request);

			global $globUser;
			if( $user == $globUser ) {
				setLogCookie( 'language', $newlanguage );
				// update global language to reflect change immediately
				global $sLanguage_code;
				$sLanguage_code = $newlanguage;
			}
			//ENDOF#2575
			break;
		case 'delete':
			require_once BASEDIR . '/server/services/adm/AdmDeleteUsersService.class.php';
			$service = new AdmDeleteUsersService();
			$request = new AdmDeleteUsersRequest();
			$request->Ticket = $ticket;
			$request->UserIds= array( $id );
			$response = $service->execute($request);
			break;
		case 'deldet':
			require_once BASEDIR . '/server/services/adm/AdmRemoveGroupsFromUserService.class.php';
			require_once BASEDIR . '/server/interfaces/services/adm/AdmRemoveGroupsFromUserRequest.class.php';
			$userGroups = array($groupId);
			$service = new AdmRemoveGroupsFromUserService();
			$request = new AdmRemoveGroupsFromUserRequest();
			$request->Ticket  = $ticket;
			$request->GroupIds= array( $groupId );
			$request->UserId  = $id;
			$response = $service->execute($request);
			break;
	
	}
} catch( BizException $e ) {
	switch( $e->getMessageKey() ) {
		case 'ERR_DUPLICATE_NAME':
			$errors2[] = $e->getMessage();
			$mode = 'error';
			break;
		case 'ERR_INVALID_EMAIL':
			$errors3[] = $e->getMessage();
			$mode = 'error';
			break;
		case 'ERR_NOT_EMPTYPASS':
		case 'PASS_TOKENS':
		case 'PASS_LOWER':
		case 'PASS_UPPER':
		case 'PASS_SPECIAL':
			$errors4[] = $e->getMessage();
			$mode = 'error';
			break;
		default:
			$errors[] = $e->getMessage();
			$mode = 'error';
			break;
	}
}

BizSession::endSession();

// delete: back to overview
if ($mode == 'delete') {
	header("Location:users.php");
	exit();
}

// generate upper part (edit fields)
if ($mode == 'error') {
	$row = array ('user' => $user, 'fullname' => $fullname, 'disable' => $disable, 
				'email' => $email, 'emailgrp' => $emailgrp, 'emailusr' => $emailusr, 
				'fixedpass' => $fixedpass, 'startdate' => $inpstartdate, 'enddate' => $inpenddate, 
				'expiredays' => $expiredays, 'language' => $newlanguage, 'trackchangescolor' => $usercolor,
				'organization' => $organization, 'location' => $location );
} elseif ($mode != "new") {
	$row = DBUser::getUserById($id); 
	$row['startdate'] = DateTimeFunctions::iso2date( $row['startdate'] );
	$row['enddate'] = DateTimeFunctions::iso2date( $row['enddate'] );
} else {
	$row = array ('user' => '', 'fullname' => '', 'disable' => '', 'email' => '', 'emailgrp' => 'on', 
				'emailusr' => 'on', 'fixedpass' => '', 'startdate' => '', 'enddate' => '', 
				'expiredays' => PASSWORD_EXPIRE, 'language' => $newlanguage, 'trackchangescolor' => DEFAULT_USER_COLOR,
				'organization' => '', 'location' => '' );
}
$txt = HtmlDocument::loadTemplate( 'hpusers.htm');

// error handling
$err = '';
foreach ($errors as $error) {
	$err .= formvar($error).'<br/>';
}
$txt = str_replace('<!--ERROR-->', $err, $txt);
$err = '';
foreach ($errors2 as $error) {
	$err .= formvar($error).'<br/>';
}
$txt = str_replace('<!--ERROR2-->', $err, $txt);
$err = '';
foreach ($errors3 as $error) {
	$err .= formvar($error).'<br/>';
}
$txt = str_replace('<!--ERROR3-->', $err, $txt);
$err = '';
foreach ($errors4 as $error) {
	$err .= formvar($error).'<br/>';
}
$txt = str_replace('<!--ERROR4-->', $err, $txt);
$err = '';
foreach ($errors5 as $error) {
	$err .= formvar($error).'<br/>';
}
$txt = str_replace('<!--ERROR5-->', $err, $txt);
$err = '';
foreach ($errors6 as $error) {
	$err .= formvar($error).'<br/>';
}
$txt = str_replace('<!--ERROR6-->', $err, $txt);

// fields
$txt = str_replace('<!--VAR:USER-->', '<input maxlength="40" name="user" value="'.formvar($row['user']).'"/>', $txt );
$txt = str_replace('<!--VAR:FULLNAME-->', '<input name="fullname" maxlength="255" value="'.formvar($row['fullname']).'"/>', $txt );
$txt = str_replace('<!--VAR:DISABLE-->', '<input type="checkbox" name="disable" '.(trim($row['disable'])?'checked="checked"':'').'/>', $txt );
$txt = str_replace('<!--VAR:PASSWORD-->', '<input name="password" maxlength="40" type="password"/>', $txt );
$txt = str_replace('<!--VAR:FIXEDPASS-->', inputvar('fixedpass', $row['fixedpass'], 'checkbox'), $txt );

$txt = str_replace('<!--VAR:ORGANIZATION-->', '<input name="organization" maxlength="255" value="'.formvar($row['organization']).'"/>', $txt );
$txt = str_replace('<!--VAR:LOCATION-->', '<input name="location" maxlength="255" value="'.formvar($row['location']).'"/>', $txt );

$startdatefield = inputvar('startdate', $row['startdate'], 'date', null, true);
$txt = str_replace('<!--VAR:STARTDATE-->', $startdatefield , $txt );

$enddatefield = inputvar('enddate', $row['enddate'], 'date', null, true);
$txt = str_replace('<!--VAR:ENDDATE-->', $enddatefield , $txt );
	
$txt = str_replace('<!--VAR:EXPIREDAYS-->', inputvar('expiredays', $row['expiredays']), $txt );
$txt = str_replace('<!--VAR:EMAIL-->', '<input name="email" maxlength="100" value="'.formvar($row['email']).'"/>', $txt );
$txt = str_replace('<!--VAR:EMAILGRP-->', inputvar('emailgrp', $row['emailgrp'], 'checkbox'), $txt );
$txt = str_replace('<!--VAR:EMAILUSR-->', inputvar('emailusr', $row['emailusr'], 'checkbox'), $txt );
$txt = str_replace('<!--VAR:HIDDEN-->', inputvar('id', $id, 'hidden'), $txt );

//BZ#2575
require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
$userlanguagecode = BizUser::validUserLanguage( $row['language'] );
$languagecodes = BizResources::getLanguageCodes();
$languagecombodef = '<select name="newlanguage">';
foreach ($languagecodes as $curcode) {
	$selected = ($curcode === $userlanguagecode) ? 'selected="selected"' : '';
	$languagename = BizResources::localize('LAN_NAME_'.$curcode);
	$languagecombodef .= '<option '.$selected.' value="'.$curcode.'">'.formvar($languagename).'</option>';
}
$languagecombodef .= '</select>';
$txt = str_replace('<!--VAR:LANGUAGE-->', $languagecombodef, $txt );	
//ENDOF#2575

$usercolor = $row['trackchangescolor'];
if (empty($usercolor)) $usercolor = DEFAULT_USER_COLOR; // should not happen in v8.
$txt = str_replace('<!--VAR:TRACKCHANGESCOLOR-->',formvar($usercolor), $txt);

// generate lower part (details)
$detailtxt = '';
if ($mode != "new" && $mode != "error") {
	$detail = '';
	if ($id > 0) {
		$groups = BizAdmUser::listUserGroupsObj( $sessionUser, null, $id, null );
		$color = array (" bgcolor='#eeeeee'", '');
		$flip = 0;
		foreach( $groups as $group ) {
			$clr = $color[$flip];
			$detail .=
				'<tr'.$clr.'><td><a href="hpgroups.php?id='.intval($group->Id).'">'.formvar($group->Name).'</a></td>'.
				'<td>'.formvar($group->Description).'</td><td><a href="hpusers.php?deldet=1&id='.$id.'&grpid='.intval($group->Id).'" '.
				' onclick="return myconfirm(\'delgroup\')">'.BizResources::localize('ACT_DELETE').'</a></td></tr>';
			$flip = 1- $flip;
		}
	}
	$detailtxt = str_replace("<!--ROWS-->", $detail, HtmlDocument::loadTemplate( 'hpusersdet.htm' ) );
	$detailtxt = str_replace("<!--ID-->", $id, $detailtxt);
}

// generate total page
$txt = str_replace('<!--DETAILS-->', $detailtxt, $txt);

//set focus to the first field
$txt .= '<script language="javascript">document.forms[0].user.focus();</script>';

print HtmlDocument::buildDocument($txt);
