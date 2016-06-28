<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

$ticket = checkSecure("admin");
$tpl = HtmlDocument::loadTemplate( 'duplicate_group.htm' );
$err = '';

// Form params
$inGID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0; // Group id
$inNewName = isset($_REQUEST['newName']) ? $_REQUEST['newName'] : ''; // Copy name for the copy destination
$inNewFullName = isset($_REQUEST['newFullName']) ? $_REQUEST['newFullName'] : '';

// Hidden multicopy feature: get params to copy multiple user groups at once
$startIdx = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 1; // postfix number to append to destination user name
$countIdx = isset($_REQUEST['count']) ? intval($_REQUEST['count']) : 1; // number of users to copy (based on source user)
if( $countIdx > 1 ) { set_time_limit(3600); }

// Duplicate user and its memberships
if( $inGID > 0 && !$err ) for( $currIdx = $startIdx; $currIdx < ($startIdx + $countIdx); $currIdx++ )
{
	// Hidden multicopy feature: add postfixes to given names
	$inNewNamePlusIdx = $inNewName;
	if( $countIdx > 1 ) $inNewNamePlusIdx .= ' '.sprintf('%03d',$currIdx); // optionally add postfix number
	$inNewFullNamePlusIdx = $inNewFullName;
	if( $countIdx > 1 ) $inNewFullNamePlusIdx .= ' '.sprintf('%03d',$currIdx); // optionally add postfix number

	try {
		// TODO - Call the CopyUserGroupsService (when became available in future) and replace the below...

		// Get details of the selected user group from DB
		require_once BASEDIR . '/server/services/adm/AdmGetUserGroupsService.class.php';
		$service = new AdmGetUserGroupsService();
		$request = new AdmGetUserGroupsRequest( $ticket, array() );
		$request->GroupIds = array($inGID);
		$response = $service->execute($request);
		$userGrp = $response->UserGroups[0];

		// Transform source into new target user group (in memory)
		$userGrp->Id = null;
		$userGrp->Name = $inNewNamePlusIdx;
		$userGrp->Description = $inNewFullNamePlusIdx;
		$userGrp->ExternalId = null;
		
		// Create new user group (target) at DB
		require_once BASEDIR . '/server/services/adm/AdmCreateUserGroupsService.class.php';
		$service = new AdmCreateUserGroupsService();
		$request = new AdmCreateUserGroupsRequest($ticket, array(), array($userGrp));
		$response = $service->execute($request);
		$newID = $response->UserGroups[0]->Id;

		// Get the selected user group members from DB
		require_once BASEDIR . '/server/services/adm/AdmGetUsersService.class.php';
		$service = new AdmGetUsersService();
		$request = new AdmGetUsersRequest($ticket);
		$request->RequestModes = array();
		$request->GroupId = $inGID;
		$response = $service->execute($request);
		$users = $response->Users;
		$userIds = array();
		foreach( $users as $user ) {
			$userIds[] = $user->Id;
		}

		// Make all users (of the 'source' group) member of the copied group (target) as well
		require_once BASEDIR . '/server/services/adm/AdmAddUsersToGroupService.class.php';
		$service = new AdmAddUsersToGroupService();
		$request = new AdmAddUsersToGroupRequest( $ticket );
		$request->GroupId = $newID;
		$request->UserIds = $userIds;
		$response = $service->execute($request);

	} catch( BizException $e ) {
		$err = $e->getMessage();
	}

	// Authorize copied group for the same pub/iss (as the 'source' group)
	if( $countIdx > 1 ) { // only for multicopy mode
		if (!$dbh = DBDriverFactory::gen()) die ( BizResources::localize('ERR_DATABASE') );
		$dba = $dbh->tablename('authorizations');
		$sql = "SELECT * FROM $dba WHERE `grpid` = $inGID";
		$sth = $dbh->query( $sql );
		if (!$sth) die( BizResources::localize('ERR_ERROR').' '.$dbh->errorcode().': '.$dbh->error()."<br/><br/>\n" );
		while( ($row = $dbh->fetch($sth))) {
			$sql = "INSERT INTO $dba (`grpid`, `publication`, `section`, `state`, `profile`,`issue`) VALUES (".
				$newID.', '.
				$row['publication'].', '.
				$row['section'].', '.
				$row['state'].', '.
				$row['profile'].', '.
				$row['issue'].')';
			$sql = $dbh->autoincrement( $sql );
			$sth2 = $dbh->query( $sql );
			if (!$sth) die( BizResources::localize("ERR_ERROR").' '.$dbh->errorcode().': '.$dbh->error()."<br/><br/>\n");
		}
	}
	
	// Redirect to user groups page only for last iteration
	if( empty($err) && // no redirect to show error
		$currIdx == ($startIdx + $countIdx - 1)) {
		if( $countIdx > 1 ) {
			header('Location: groups.php'); 
		} else {
			header('Location: hpgroups.php?id='.$newID); 
		}
		exit();
	}	
}

// Fill group combo
try {
	require_once BASEDIR . '/server/services/adm/AdmGetUserGroupsService.class.php';
	$service = new AdmGetUserGroupsService();
	$request = new AdmGetUserGroupsRequest( $ticket, array() );
	$response = $service->execute($request);
	$userGrps = $response->UserGroups;
	$comboBoxGroup = '<select name="Groups" style="width:150px">';
	foreach( $userGrps as $userGrp ) {
		$comboBoxGroup .= '<option value='.$userGrp->Id.'>' . formvar($userGrp->Name) . '</option>';
	}
	$comboBoxGroup .= '</select>';
} catch( BizException $e ) {
	$err = $e->getMessage();
}

// Build HTML form
$GroupTable = '<tr>'.
	'<td>'.$comboBoxGroup.'</td>'.
	'<td><input maxlength="100" type="text" name="newusrName" value="'.formvar($inNewName).'"/></td>'.
	'<td><input maxlength="255" type ="text" name="FullName" value="'.formvar($inNewFullName).'"/></td>'.
'</tr>'."\n";
$tpl = str_replace( '<!--PAR:GROUP-->', $GroupTable, $tpl );
$tpl = str_replace( '<!--PAR:ERROR-->', $err, $tpl);

$body = '';
print HtmlDocument::buildDocument($tpl, true, $body);
