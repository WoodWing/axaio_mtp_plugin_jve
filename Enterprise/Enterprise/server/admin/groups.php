<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/bizclasses/BizLDAP.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';

checkSecure('admin');

$tableRows = '';
$groups = DBUser::listUserGroupsObj( null, null ); // All groups.
$userByGroups = DBUser::getNumberOfUsersByGroupId();
if ( $groups ) foreach ( $groups as $group ) {
	$g = $group->Id;
	$admin = (trim($group->Admin)) ? CHECKIMAGE : '';
	$routing = (trim($group->Routing)) ?  CHECKIMAGE : '';
	$name = (trim($group->Name)) ? $group->Name : '';
	$cu = isset( $userByGroups[ $g ] ) ? $userByGroups[ $g ] : 0 ;

	$tableRows .= '<tr><td><a href="hpgroups.php?id='.$g.'">'.formvar($name).'</a></td>'.
			'<td>'.formvar($group->Description).'</td><td>'.$admin.'</td>'.
			'<td>'.$routing.'</td><td>'.$cu.'</td></tr>'."\r\n";
}

$ldap = BizLDAP::isInstalled();

// generate page
$txt = HtmlDocument::loadTemplate( 'groups.htm' );
$txt = str_replace( '<!--ROWS-->', $tableRows, $txt );
$txt = str_replace( '<!--SHOW_IMPORT_BTN-->', $ldap ? 'true' : 'false', $txt );
print HtmlDocument::buildDocument($txt);
