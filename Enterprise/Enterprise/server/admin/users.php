<?php
require_once dirname( __FILE__ ).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

checkSecure( 'admin' );
$txt = '';
$users = DBUser::getUsersByWhere( '', array() );
foreach( $users as $user ) {
	$disable = $user->Deactivated ? CHECKIMAGE : '';
	$languagecode = BizUser::validUserLanguage( $user->Language );//BZ#2575 Added showing the name of the language here
	$languagename = BizResources::localize( "LAN_NAME_$languagecode" );
	$trackchangescolor = $user->TrackChangesColor ? $user->TrackChangesColor : '#FF9900' ;
	$userName = trim( $user->Name ) ? $user->Name : '&nbsp;';
	$txt .= '<tr><td><a href="hpusers.php?id='.$user->Id.'">'.formvar( $userName ).'</a></td><td>'.formvar( $user->FullName ).'</td><td>'.formvar( $user->EmailAddress ).'</td>';
	$txt .= "<td>".formvar( $languagename )."</td>";
	$txt .= '<td bgcolor="'.formvar( $trackchangescolor ).'"></td>';
	$txt .= "<td>$disable</td>";
	$isAssignedToGroup = ( BizUser::getMemberships( $user->Id ) ) ? BizResources::localize( 'FEATURE_YES' ) : BizResources::localize( 'FEATURE_NO' );
	$txt .= '<td>'.$isAssignedToGroup.'</td>';
	$txt .= "</tr>\r\n";
}

$txt = str_replace( "<!--ROWS-->", $txt, HtmlDocument::loadTemplate( 'users.htm' ) );
$assignedToGroupsHeader = '<th>'.BizResources::localize( 'USR_IMPORTED_GROUPS' ).'</th>';
$txt = str_replace( "<!--PAR:USR_IMPORTONLOGON_HEADER-->", $assignedToGroupsHeader, $txt );
print HtmlDocument::buildDocument( $txt );
