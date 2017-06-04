<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
$ticket = checkSecure('admin');
$sessionUser = DBTicket::checkTicket( $ticket );

// get param's
$id          = isset($_REQUEST['id'])       ? intval($_REQUEST['id']) : 0;
$user        = isset($_REQUEST['user'])     ? $_REQUEST['user'] : '';
$fullName    = isset($_REQUEST['fullname']) ? $_REQUEST['fullname'] : '';
$disable     = isset($_REQUEST['disable'])  && $_REQUEST['disable'] ? 'on'  : '';
$email       = isset($_REQUEST['email'])    ? $_REQUEST['email'] : '';
$emailUsr    = isset($_REQUEST['emailusr']) && $_REQUEST['emailusr']  ? 'on' : '';
$emailGrp    = isset($_REQUEST['emailgrp']) && $_REQUEST['emailgrp']  ? 'on' : '';
$fixedPass   = isset($_REQUEST['fixedpass'])&& $_REQUEST['fixedpass'] ? 'on' : '';
$pass		 = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
// do not set password if $pass is empty string
if( empty($pass) ) {
	// pass null/nil to the admin service, this prevents setting the password
	$pass = null;
}
$organization= isset($_REQUEST['organization']) ? $_REQUEST['organization'] : '';
$location	 = isset($_REQUEST['location']) ? $_REQUEST['location'] : '';

$inpStartDate = isset($_REQUEST['startdate'])      ? $_REQUEST['startdate'] : '';
$startYear	 = isset($_REQUEST['startdate_years']) ? intval($_REQUEST['startdate_years']) : 0;
$startMonth  = isset($_REQUEST['startdate_months'])? intval($_REQUEST['startdate_months']) : 0;
$startDay	 = isset($_REQUEST['startdate_days'])  ? intval($_REQUEST['startdate_days']): 0;

$inpEndDate  = isset($_REQUEST['enddate'])         ? $_REQUEST['enddate'] : '';
$endYear	 = isset($_REQUEST['enddate_years'])   ? intval($_REQUEST['enddate_years']) : 0;
$endMonth  	 = isset($_REQUEST['enddate_months'])  ? intval($_REQUEST['enddate_months']) : 0;
$endDay	 	 = isset($_REQUEST['enddate_days'])    ? intval($_REQUEST['enddate_days']) : 0;

$expireDays  = isset($_REQUEST['expiredays'])      ? intval($_REQUEST['expiredays']) : 0;
$newLanguage = isset($_REQUEST['newlanguage'])     ? $_REQUEST['newlanguage'] : '';
$userColor   = isset($_REQUEST['color1'])          ? $_REQUEST['color1'] : '';
$groupId     = isset($_REQUEST['grpid'])           ? intval($_REQUEST['grpid']) : 0;

// Create new user maintenance app
$app = new UserMaintenanceApp();

// Determine incoming mode for app
$mode = $app->getMode( $id );

$errors = array( 'ERROR' => array(),
					'ERROR2' => array(),
					'ERROR3' => array(),
					'ERROR4' => array(),
					'ERROR5' => array(),
					'ERROR6' => array()
					);

$startDate = $app->getStartDate( $inpStartDate, $startYear, $startMonth, $startDay );
$endDate = $app->getEndDate( $inpEndDate, $endYear, $endMonth, $endDay );
$mode = $app->validateStartEndDate( $inpStartDate, $startDate, $inpEndDate, $endDate, $mode, $errors );

BizSession::startSession( $ticket );

// handle request
try {
	switch( $mode ) {
		case 'insert':
			require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
			$userObjs = array( new AdmUser( null, $user, $fullName, $disable == 'on', $pass,
			 					    $fixedPass == 'on', $email, $emailUsr == 'on', $emailGrp == 'on',
			 					    $expireDays, $startDate, $endDate, $newLanguage,
			 					    substr($userColor,1), $organization, $location, null));
			$service = new AdmCreateUsersService();
			$request = new AdmCreateUsersRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->Users  = $userObjs;
			$response = $service->execute($request);
			$id = $response->Users[0]->Id; //We only get one user back
			break;
		case 'update':
			require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';
			$userObjs = array( new AdmUser($id, $user, $fullName, $disable == 'on', $pass,
			 					    $fixedPass == 'on', $email, $emailUsr == 'on', $emailGrp == 'on',
			 					    $expireDays, $startDate, $endDate, $newLanguage,
			 					    substr($userColor,1), $organization, $location, null));
			$service = new AdmModifyUsersService();
			$request = new AdmModifyUsersRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->Users  = $userObjs;
			$response = $service->execute($request);

			global $globUser;
			if( $user == $globUser ) {
				setLogCookie( 'language', $newLanguage );
				// update global language to reflect change immediately
				global $sLanguage_code;
				$sLanguage_code = $newLanguage;
			}
			//ENDOF#2575
			break;
		case 'delete':
			require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
			$service = new AdmDeleteUsersService();
			$request = new AdmDeleteUsersRequest();
			$request->Ticket = $ticket;
			$request->UserIds= array( $id );
			$response = $service->execute($request);
			break;
		case 'deldet':
			require_once BASEDIR.'/server/services/adm/AdmRemoveGroupsFromUserService.class.php';
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
			$errors['ERROR2'][] = $e->getMessage();
			$mode = 'error';
			break;
		case 'ERR_INVALID_EMAIL':
			$errors['ERROR3'][] = $e->getMessage();
			$mode = 'error';
			break;
		case 'ERR_NOT_EMPTYPASS':
		case 'PASS_TOKENS':
		case 'PASS_LOWER':
		case 'PASS_UPPER':
		case 'PASS_SPECIAL':
			$errors['ERROR4'][] = $e->getMessage();
			$mode = 'error';
			break;
		default:
			$errors['ERROR'][] = $e->getMessage();
			$mode = 'error';
			break;
	}
}

BizSession::endSession();

// delete: back to overview
if( $mode == 'delete' ) {
	header("Location:users.php");
	exit();
}

$txt = HtmlDocument::loadTemplate('hpusers.htm');

// generate upper part (edit fields)
if( $mode == 'error' ) {
	$row = array ('user' => $user, 'fullname' => $fullName, 'disable' => $disable,
				'email' => $email, 'emailgrp' => $emailGrp, 'emailusr' => $emailUsr,
				'fixedpass' => $fixedPass, 'startdate' => $inpStartDate, 'enddate' => $inpEndDate,
				'expiredays' => $expireDays, 'language' => $newLanguage, 'trackchangescolor' => $userColor,
				'organization' => $organization, 'location' => $location );
} elseif( $mode != "new" ) {
	$row = DBUser::getUserById($id); 
	$row['startdate'] = DateTimeFunctions::iso2date( $row['startdate'] );
	$row['enddate'] = DateTimeFunctions::iso2date( $row['enddate'] );
} else {
	$row = array ('user' => '', 'fullname' => '', 'disable' => '', 'email' => '', 'emailgrp' => 'on', 
				'emailusr' => 'on', 'fixedpass' => '', 'startdate' => '', 'enddate' => '', 
				'expiredays' => PASSWORD_EXPIRE, 'language' => $newLanguage, 'trackchangescolor' => DEFAULT_USER_COLOR,
				'organization' => '', 'location' => '' );
}

// Error handling
$txt = $app->replaceErrorMessages( $txt, $errors );

// Build the upper part of user form
$txt = $app->buildUserForm( $id, $row, $txt );

// Build lower part user groups table
$txt = $app->buildUserGroupsTable( $id, $mode, $sessionUser, $txt );

// Set focus to the first field
$txt .= '<script language="javascript">document.forms[0].user.focus();</script>';

print HtmlDocument::buildDocument($txt);

/**
 * Helper class for the admin application: User Maintenance App
 */
class UserMaintenanceApp
{
	/**
	 * Build the user form fields at the upper part of the user maintenance page
	 * @param $id
	 * @param $row
	 * @param $txt
	 * @return mixed
	 */
	public function buildUserForm( $id, $row, $txt )
	{
		// fields
		$txt = str_replace('<!--VAR:USER-->', '<input maxlength="40" name="user" value="'.formvar($row['user']).'"/>', $txt );
		$txt = str_replace('<!--VAR:FULLNAME-->', '<input name="fullname" maxlength="255" value="'.formvar($row['fullname']).'"/>', $txt );
		$txt = str_replace('<!--VAR:DISABLE-->', '<input type="checkbox" name="disable" '.(trim($row['disable'])?'checked="checked"':'').'/>', $txt );
		$txt = str_replace('<!--VAR:PASSWORD-->', '<input name="password" maxlength="40" type="password"/>', $txt );
		$txt = str_replace('<!--VAR:FIXEDPASS-->', inputvar('fixedpass', $row['fixedpass'], 'checkbox'), $txt );

		$txt = str_replace('<!--VAR:ORGANIZATION-->', '<input name="organization" maxlength="255" value="'.formvar($row['organization']).'"/>', $txt );
		$txt = str_replace('<!--VAR:LOCATION-->', '<input name="location" maxlength="255" value="'.formvar($row['location']).'"/>', $txt );

		$startDateField = inputvar('startdate', $row['startdate'], 'date', null, true);
		$txt = str_replace('<!--VAR:STARTDATE-->', $startDateField , $txt );

		$endDateField = inputvar('enddate', $row['enddate'], 'date', null, true);
		$txt = str_replace('<!--VAR:ENDDATE-->', $endDateField , $txt );

		$txt = str_replace('<!--VAR:EXPIREDAYS-->', inputvar('expiredays', $row['expiredays']), $txt );
		$txt = str_replace('<!--VAR:EMAIL-->', '<input name="email" maxlength="100" value="'.formvar($row['email']).'"/>', $txt );
		$txt = str_replace('<!--VAR:EMAILGRP-->', inputvar('emailgrp', $row['emailgrp'], 'checkbox'), $txt );
		$txt = str_replace('<!--VAR:EMAILUSR-->', inputvar('emailusr', $row['emailusr'], 'checkbox'), $txt );
		$txt = str_replace('<!--VAR:HIDDEN-->', inputvar('id', $id, 'hidden'), $txt );

		//BZ#2575
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		$userLanguageCode = BizUser::validUserLanguage( $row['language'] );
		$languageCodes = BizResources::getLanguageCodes();
		$languageComboDef = '<select name="newlanguage">';
		foreach( $languageCodes as $languageCode ) {
			$selected = ($languageCode === $userLanguageCode) ? 'selected="selected"' : '';
			$languageName = BizResources::localize('LAN_NAME_'.$languageCode);
			$languageComboDef .= '<option '.$selected.' value="'.$languageCode.'">'.formvar($languageName).'</option>';
		}
		$languageComboDef .= '</select>';
		$txt = str_replace('<!--VAR:LANGUAGE-->', $languageComboDef, $txt );
		//ENDOF#2575

		$userColor = $row['trackchangescolor'];
		if( empty($userColor) ) {
			$userColor = DEFAULT_USER_COLOR; // should not happen in v8.
		}
		$txt = str_replace('<!--VAR:TRACKCHANGESCOLOR-->',formvar($userColor), $txt);

		return $txt;
	}

	/**
	 * Build the user groups table for the user at the lower part of the maintenance page
	 *
	 * @param $id
	 * @param $mode
	 * @param $sessionUser
	 * @param $txt
	 * @return mixed
	 */
	public function buildUserGroupsTable( $id, $mode, $sessionUser, $txt )
	{
		$detailTxt = '';
		if( $mode != "new" && $mode != "error" ) {
			$detail = '';
			if( $id > 0 ) {
				require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
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
			$detailTxt = str_replace("<!--ROWS-->", $detail, HtmlDocument::loadTemplate( 'hpusersdet.htm' ) );
			$detailTxt = str_replace("<!--ID-->", $id, $detailTxt);
		}
		$txt = str_replace('<!--DETAILS-->', $detailTxt, $txt);

		return $txt;
	}

	/**
	 * Replace with error messages
	 *
	 * @param $txt
	 * @param $errorFields
	 * @return mixed
	 */
	public function replaceErrorMessages( $txt, $errorFields )
	{
		foreach( $errorFields as $field => $errors ) {
			$err = $this->concatErrorMessage( $errors );
			$txt = str_replace('<!--'.$field.'-->', $err, $txt);
		}

		return $txt;
	}

	/**
	 * Concatenate error message into string
	 *
	 * @param $errors
	 * @return string
	 */
	private function concatErrorMessage( $errors )
	{
		$errorMsg = '';
		foreach( $errors as $error ) {
			$errorMsg .= formvar($error).'<br/>';
		}

		return $errorMsg;
	}

	/**
	 * Get the mode of the user maintenance pages
	 *
	 * @param $id
	 * @return string
	 */
	public function getMode( $id )
	{
		if( isset($_REQUEST['update']) && $_REQUEST['update'] ) {
			$mode = ($id > 0) ? 'update' : 'insert';
		} else if( isset($_REQUEST['delete']) && $_REQUEST['delete'] ) {
			$mode = 'delete';
		} else if(isset($_REQUEST['deldet']) && $_REQUEST['deldet'] ) {
			$mode = 'deldet';
		} else {
			$mode = ($id > 0) ? 'edit' : 'new';
		}

		return $mode;
	}

	/**
	 * Get Start Date
	 *
	 * @param $inpStartDate
	 * @param $startYear
	 * @param $startMonth
	 * @param $startDay
	 * @return string
	 */
	public function getStartDate( $inpStartDate, $startYear, $startMonth, $startDay )
	{
		if( $startYear && $startMonth && $startDay ) {
			$startDate = DateTimeFunctions::validDate( $startDay . '-' . $startMonth . '-' . $startYear, false );
		} else {
			$startDate = DateTimeFunctions::validDate( $inpStartDate, false );
		}

		return $startDate;
	}

	/**
	 * Get the end date
	 *
	 * @param $inpEndDate
	 * @param $endYear
	 * @param $endMonth
	 * @param $endDay
	 * @return string
	 */
	public function getEndDate( $inpEndDate, $endYear, $endMonth, $endDay )
	{
		if( $endYear && $endMonth && $endDay ) {
			$endDate = DateTimeFunctions::validDate( $endDay . '-' . $endMonth . '-' . $endYear, false );
		} else {
			$endDate = DateTimeFunctions::validDate( $inpEndDate, false );
		}

		return $endDate;
	}

	/**
	 * Validate the start and end date
	 *
	 * @param $inpStartDate
	 * @param $startDate
	 * @param $inpEndDate
	 * @param $endDate
	 * @param $mode
	 * @param $errors
	 * @return string
	 */
	public function validateStartEndDate( $inpStartDate, $startDate, $inpEndDate, $endDate, $mode, &$errors )
	{
		if( !$startDate && $inpStartDate ) {
			$mode = 'error';
			$sErrorMessage = BizResources::localize("INVALID_DATE");
			$errors['ERROR5'][] = $sErrorMessage;
		}
		if( !$endDate && $inpEndDate ) {
			$mode = 'error';
			$sErrorMessage = BizResources::localize("INVALID_DATE");
			$errors['ERROR6'][] = $sErrorMessage;
			return $mode;
		}
		if( DateTimeFunctions::diffIsoTimes( $startDate, $endDate ) > 0 ) {
			$mode = 'error';
			$sErrorMessage = BizResources::localize("INVALID_DATE");
			$errors['ERROR6'][] = $sErrorMessage;
		}
		return $mode;
	}
}