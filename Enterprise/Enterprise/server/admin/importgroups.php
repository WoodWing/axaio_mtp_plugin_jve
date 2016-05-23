<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/interfaces/services/BizException.class.php';
require_once BASEDIR.'/server/bizclasses/BizLDAP.class.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR.'/server/bizclasses/BizUser.class.php'; // includes DBUser
require_once BASEDIR.'/server/utils/HtmlParamPack.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

// Determine web app access
$userRow = null;
$ticket = checkSecure('admin' );
global $globUser;  // set by checkSecure()

// Retrieve request params
$parDomain = isset( $_REQUEST['DomainsCombo'] ) ? $_REQUEST['DomainsCombo'] : getOptionalCookie('LdapDomain');
$parSearch = isset( $_REQUEST['SearchTxt'] ) ? $_REQUEST['SearchTxt'] : getOptionalCookie('LdapSearch');
$parToImport = isset( $_REQUEST['TaggedImport'] ) ? $_REQUEST['TaggedImport'] : '';
$parUserName = isset( $_REQUEST['UserName'] ) ? $_REQUEST['UserName'] : $globUser;
$parPassword = isset( $_REQUEST['Password'] ) ? $_REQUEST['Password'] : getOptionalCookie('LdapPasswd');

// Save request params
setcookie( 'LdapDomain', $parDomain, 0, INETROOT );
setcookie( 'LdapSearch', $parSearch, 0, INETROOT );
setcookie( 'LdapPasswd', $parPassword, 0, INETROOT ); // BUG fix! (was $parSearch, and so overwriting search field into password field)

$txt = HtmlDocument::loadTemplate( 'importgroups.htm' );

// Init
$serverOptions = '';
$tableRows = '';
BizSession::startSession( $ticket );

// Import selected LDAP user groups in SCE db
if( mb_strlen($parToImport) > 0 ) {
	try {
		$groups = HtmlParamPack::unpackObjects( $parToImport );
		foreach( $groups as  $group ) {
			require_once BASEDIR . '/server/services/adm/AdmCreateUserGroupsService.class.php';
			$groupObj = new AdmUserGroup( null, $group->Name, $group->Description, null, null, null);
			$groupObj->ExternalId = $group->ExternalId; // This property didn't exist in service layer, temporary assign here
			$service = new AdmCreateUserGroupsService();
			$request = new AdmCreateUserGroupsRequest($ticket, array(), array($groupObj));
			$response = $service->execute($request);
			$id = $response->UserGroups[0]->Id;
		}
	} catch ( BizException $e ) {
		$txt = str_replace( '<!--LDAP_ERROR-->', $e->getMessage(), $txt );
	}
}

// Collect configured LDAP servers
$ldapServers = BizSettings::getLdapServers();
if( !is_null( $ldapServers ) ) {
	foreach( $ldapServers as $ldapServer ) {
		$sel = ($parDomain == $ldapServer->SuffixDNS) ? 'selected="selected" ' : '';
		$serverOptions .= "\r\n".'<option '.$sel.'value="'.formvar($ldapServer->SuffixDNS).'">'.formvar($ldapServer->SuffixDNS).'</option>';
	}
}

// Get all SCE user groups
$sceAllGroupRows = DBUser::getUserGroups();
$sceAllGroups = array();
$sceAllGroupsExtId = array();
$sceAllGroupsIdNameMap = array();
foreach( $sceAllGroupRows as $sceAllGroupRow ) {
	$sceAllGroupsExtId[$sceAllGroupRow['externalid']] = $sceAllGroupRow['id'];
	$sceAllGroups[$sceAllGroupRow['name']] = $sceAllGroupRow['id'];
	$sceAllGroupsIdNameMap[$sceAllGroupRow['id']] = $sceAllGroupRow['name'];
}

// Import groups application
if( !empty( $parDomain ) && !empty($parPassword) ) {
	try {
		// Show LDAP groups
		$ldap = new BizLDAP();
		$ldapAllGroups = $ldap->findGroups( $parUserName, $parPassword, $parDomain, $parSearch );
		foreach( $ldapAllGroups as $ldapGroup ) {
			// Pack entire user group object to inject it into the checkbox's value
			// which enables us to retrieve it from posted page later on (without querying LDAP/db again)
			$grpData = HtmlParamPack::packObject( $ldapGroup );
			$tableRows .= "\r\n<tr>";
			$name = $ldapGroup->Name;
			if( array_key_exists( $ldapGroup->ExternalId, $sceAllGroupsExtId ) ) {
				// matched on external id
				$tableRows .= '<td>'.CHECKIMAGE.'</td>'; // Show tagged icon for already imported groups
				// show Enterprise name too, if it has been changed
				$entName = $sceAllGroupsIdNameMap[$sceAllGroupsExtId[$ldapGroup->ExternalId]];
				if ($entName != $ldapGroup->Name){
					$name .= ' (' . $entName . ')';
				}
			} else if ( array_key_exists( $ldapGroup->Name, $sceAllGroups ) ){
				// matched on name
				$tableRows .= '<td>'.CHECKIMAGE.'</td>'; // Show tagged icon for already imported groups
				// update external id
				$id = $sceAllGroups[$ldapGroup->Name];
				$oldGroup = BizUser::getUserGroupById($id);
				require_once BASEDIR . '/server/services/adm/AdmModifyUserGroupsService.class.php';
				$groupObj = new AdmUserGroup( $id, $oldGroup->Name, $oldGroup->Description, $oldGroup->Admin, $oldGroup->Routing, null);
				$groupObj->ExternalId = $ldapGroup->ExternalId;
				$service = new AdmModifyUserGroupsService();
				$request = new AdmModifyUserGroupsRequest($ticket, array(), array($groupObj));
				$response = $service->execute($request);
			} else {
				$tableRows .= '<td><input id="chkgrp" name="chkgrp" value="'.formvar($grpData).'" type="checkbox"></td>';
			}
			$tableRows .= '<td>'.formvar($name).'</td><td>'.formvar($ldapGroup->Description).'</td></tr>';
		}
	} catch ( BizException $e ) {
		$txt = str_replace( '<!--LDAP_ERROR-->', $e->getMessage(), $txt );
	}
}

BizSession::endSession();

// Output HTML page
$txt = str_replace( '<!--LDAP_SERVERS-->', $serverOptions, $txt );
$txt = str_replace( '<!--LDAP_GROUPS-->', $tableRows, $txt );
$txt = str_replace( '<!--GROUP_SEARCH-->', formvar($parSearch), $txt );
$txt = str_replace( '<!--USERNAME-->', formvar($parUserName), $txt );
$txt = str_replace( '<!--PASSWORD-->', formvar($parPassword), $txt );

// For debugging
/*if( !empty($parPassword) ) {
	$ldapAllUsers = BizLDAP::findUsers( $parUserName, $parPassword, $parDomain, 'SCE*' );
	$txt .= print_r($ldapAllUsers,true);
}*/

print HtmlDocument::buildDocument( $txt );

?>