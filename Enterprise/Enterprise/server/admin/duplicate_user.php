<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

$ticket = checkSecure("admin");
$tpl = HtmlDocument::loadTemplate( 'duplicate_user.htm' );
$err='';

// Get form params
$inUID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
$inNewName = isset($_REQUEST['newName']) ? $_REQUEST['newName'] : '';
$inNewFullName = isset($_REQUEST['newFullName']) ? $_REQUEST['newFullName'] : '';
if (!$inNewFullName) $inNewFullName = $inNewName;
$newPass_1 = isset($_REQUEST['newPass_1']) ? $_REQUEST['newPass_1'] : '';
$newPass_2 = isset($_REQUEST['newPass_2']) ? $_REQUEST['newPass_2'] : '';
$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
$newPass_1 = trim ($newPass_1);
$newPass_2 = trim ($newPass_2);
$succeed = false;
 
// Hidden multicopy feature: get params to copy multiple users at once
$startIdx = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 1; // postfix number to append to destination user name
$countIdx = isset($_REQUEST['count']) ? intval($_REQUEST['count']) : 1; // number of users to copy (based on source user)
if( $countIdx > 1 ) { set_time_limit(3600); }

 //
 /////////////////////// *** Duplicate  User *** ///////////////////////
 //
if( $inUID > 0 ) for( $currIdx = $startIdx; $currIdx < ($startIdx + $countIdx); $currIdx++ )
{ 
	// Hidden multicopy feature: add postfixes to given names
	$inNewNamePlusIdx = $inNewName;
	if( $countIdx > 1 ) $inNewNamePlusIdx .= ' '.sprintf('%03d',$currIdx); // optionally add postfix number
	$inNewFullNamePlusIdx = $inNewFullName;
	if( $countIdx > 1 ) $inNewFullNamePlusIdx .= ' '.sprintf('%03d',$currIdx); // optionally add postfix number

	try {
		// TODO - Call the CopyUsersService (when became available in future) and replace the below...
		
		// Get source user from DB
		require_once BASEDIR . '/server/services/adm/AdmGetUsersService.class.php';
		$service = new AdmGetUsersService();
		$request = new AdmGetUsersRequest( $ticket );
		$request->UserIds = array($inUID);
		$request->RequestModes = array( 'GetUsers' );
		$response = $service->execute($request);
		$userObj = $response->Users[0];
		
		// Transform source into new target user (in memory)
		$userObj->Id = null;
		$userObj->Name = $inNewNamePlusIdx;
		$userObj->FullName = $inNewFullNamePlusIdx;
		$userObj->Password = $newPass_1;
		$userObj->Deactivated = false;
		$userObj->EmailAddress = $email;
		$userObj->ExternalId = ''; // The external id of the Ldap system is unique per user. It must not be copied.

		// Create new target user at DB
		require_once BASEDIR . '/server/services/adm/AdmCreateUsersService.class.php';
		$service = new AdmCreateUsersService();
		$request = new AdmCreateUsersRequest($ticket, array(), array($userObj));
		$response = $service->execute($request);
		$newID = $response->Users[0]->Id;
	
		// Get all groups the source user is member of
		require_once BASEDIR . '/server/services/adm/AdmGetUserGroupsService.class.php';
		$service = new AdmGetUserGroupsService();
		$request = new AdmGetUserGroupsRequest( $ticket, array() );
		$request->UserId = $inUID;
		$response = $service->execute($request);
		$userGrps = $response->UserGroups;
		$groupIds = array();
		foreach( $userGrps as $userGrp ) {
			$groupIds[] = $userGrp->Id;
		}
	
		// Make new target user member of the same groups (as source user)
		require_once BASEDIR . '/server/services/adm/AdmAddGroupsToUserService.class.php';
		$service = new AdmAddGroupsToUserService();
		$request = new AdmAddGroupsToUserRequest( $ticket );
		$request->UserId = $newID;
		$request->GroupIds = $groupIds;
		$response = $service->execute($request);

	} catch( BizException $e ) {
		$err .= $e->getMessage() . "<br/>";
	}

	// Redirect to users page only for last iteration
	if( empty($err) && // no redirect to show error
		$currIdx == ($startIdx + $countIdx - 1)) {
		if( $countIdx > 1 ) {
			header('Location: users.php'); 
		} else {
			header('Location: hpusers.php?id='.$newID); 
		}
		exit();
	}	
}	

try {
	// Get all users from DB
	require_once BASEDIR . '/server/services/adm/AdmGetUsersService.class.php';
	$service = new AdmGetUsersService();
	$request = new AdmGetUsersRequest( $ticket );
	$request->RequestModes = array( 'GetUsers' );
	$response = $service->execute($request);
	$userObjs = $response->Users;
	
	// List users at combo
	$comboBoxUser = '<select name="Users" style="width:150px" onchange = "getusrID(this.value);">'.PHP_EOL;
	$comboBoxUser .= '<option value="-1">Select</option>'.PHP_EOL; // TODO: localize!
	foreach( $userObjs as $userObj ) {
		$selected = ($userObj->Id == $inUID) ? ' selected="selected"' : '';
		$comboBoxUser .= '<option value="'.$userObj->Id.'"'.$selected.'>'.formvar($userObj->Name).'</option>'.PHP_EOL;
	}
	$comboBoxUser .= '</select>'.PHP_EOL;
} catch( BizException $e ) {
	$err .= $e->getMessage() . "<br/>";
}

// Build the application
$UsrTable = 
	'<tr>'.
		'<td valign="top">'.$comboBoxUser.'</td>'.
		'<td><input maxlength="40" type="text" name="newusrName" value="'.formvar($inNewName).'"/>'.
			'<table class="text"><tr><td>'.BizResources::localize('USR_FULL_NAME').'</td></tr>'.
				'<tr><td valign="top">'.'<input maxlength="255" type="text" name="FullName" value="'.formvar($inNewFullName).'"/></td></tr>'.
				'<tr><td valign="top">'.BizResources::localize('PSW_NEW').'</td></tr>'.
				'<tr><td valign="top"><input maxlength="40" type="password" name="newPass_1" value="'.formvar($newPass_1).'"/></td></tr>'.
				'<tr><td valign="top">'.BizResources::localize('PSW_VERIFY_NEW').'</td></tr>'.
				'<tr><td valign="top"><input maxlength="40" type="password" name="newPass_2" value="'.formvar($newPass_2).'"/></td></tr>'.
				'<tr><td valign="top">'.BizResources::localize('WFL_EMAIL').'</td></tr>'.
				'<tr><td valign="top"><input maxlength="100" name="email" value="'.formvar($email).'"/></td></tr>'.
			'</table>'.
		'</td>'.
	'</tr>'."\n";
$tpl = str_replace ('<!--PAR:USERS-->',$UsrTable, $tpl);
$tpl = str_replace ('<!--PAR:ERROR-->',$err, $tpl);
print HtmlDocument::buildDocument($tpl);
