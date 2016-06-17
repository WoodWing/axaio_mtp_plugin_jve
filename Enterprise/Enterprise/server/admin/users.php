<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizLDAP.class.php';

checkSecure('admin');

$isLdapEnabled = BizLdap::isInstalled();

$dbh = DBDriverFactory::gen();
$dbu = $dbh->tablename("users");
$dbx = $dbh->tablename("usrgrp");

$txt = '';
$sql = "select * from $dbu order by `user`";
$sth = $dbh->query($sql);
while ( ($row = $dbh->fetch($sth)) )
{
	$u = $row["id"];
	
	$disable = '';
	if (trim($row["disable"])) $disable = CHECKIMAGE;
	
//BZ#2575 Added showing the name of the language here
	require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
	$languagecode = BizUser::validUserLanguage( $row["language"] );
	$languagename = BizResources::localize("LAN_NAME_$languagecode");
	
//Added showing color here
	$trackchangescolor = $row["trackchangescolor"];
	if (empty($trackchangescolor)) {
		$trackchangescolor = '#FF9900';	
	}

	$user = formvar($row['user']);
	if (!trim($user)) $user = '&nbsp;';
	$txt .= '<tr><td><a href="hpusers.php?id='.$u.'">'.$user.'</a></td><td>'.formvar($row["fullname"]).'</td><td>'.formvar($row["email"]).'</td>';
	$txt .= "<td>".formvar($languagename)."</td>";
	$txt .= '<td bgcolor="'.formvar($trackchangescolor).'"></td>';
	$txt .= "<td>$disable</td>";
	if ( $isLdapEnabled ) {
		// Add the field values.
		$importOnLogon = ($row['importonlogon'] == 'on') ? BizResources::localize('FEATURE_NO') : BizResources::localize('FEATURE_YES');
		$txt .= '<td>' . $importOnLogon . '</td>';
	}
	$txt .= "</tr>\r\n";
}

// generate page
$txt = str_replace("<!--ROWS-->", $txt, HtmlDocument::loadTemplate( 'users.htm' ) );

// Add optional header for the importonlogon field.
if ( $isLdapEnabled ) {
	// Add the header:
	$importedGroupsHeader = '<th>' . BizResources::localize('USR_IMPORTED_GROUPS') . '</th>';
	$txt = str_replace("<!--PAR:USR_IMPORTONLOGON_HEADER-->", $importedGroupsHeader, $txt);
}
print HtmlDocument::buildDocument($txt);
?>