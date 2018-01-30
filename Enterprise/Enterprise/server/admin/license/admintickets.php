<?php
require_once dirname( __FILE__ ).'/../../../config/config.php';
include_once( BASEDIR."/server/utils/license/license.class.php" );
require_once BASEDIR.'/server/secure.php';

ob_start();
$lic = new License();

//If no license installed yet: everyone may install the first license (the SCE Server license)
//Once a license has been installed, only admin users may do something here...
$hasLicense = $lic->hasLicense();
if( !$hasLicense ) {
	print BizResources::localize( "LIC_NO_SCENT_LICENSE_INSTALLED" );
	admintickets_buildDoc();
	exit;
}

$ok = false;
require_once BASEDIR.'/server/utils/HttpRequest.class.php';
$requestParams = WW_Utils_HttpRequest::getHttpParams( 'GP' );
$user = isset( $requestParams['adminUser'] ) ? $requestParams['adminUser'] : '';

//Admin user should always logon AFTER the max usage limit has been reached.
//If necessary he should first logoff.
//By logging on, the _install_ user will be removed from the tickets table, and his lastlogon timestamp will be set.
if( $user ) {
	$sessionName = 'ww_userlimit_admin_session';
	session_name( $sessionName );
	session_start();
	$adminUser = $_SESSION['adminUser'];
	$hash = $_SESSION['hash'];
	$myhash = md5( $user."bla" );
	if( ( $user == $adminUser ) &&
		( $hash == $myhash ) ) {
		$ok = true;
	}
}

if( !$ok ) {
	print BizResources::localize( "LIC_ACCESS_DENIED" );
	admintickets_buildDoc();
	exit;
}

require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
$rows = DBBase::listRows( 'users', '', null, '', array( 'fullname', 'user' ) );
if( is_null( $rows ) ) { // Error occurred
	admintickets_buildDoc();
	exit;
}
$users = array();
if( $rows ) foreach( $rows as $row ) {
	$users[ $row['user'] ] = $row['fullname'];
}

//Delete by usr
$usr = isset( $requestParams['usr'] ) ? $requestParams['usr'] : '';
if( $usr ) {
    $where = '`usr`= ?';
    $params = array( strval( $usr ));
    $result = DBBase::deleteRows( 'tickets', $where, $params  );
	if( is_null( $result ) ) {
		admintickets_buildDoc();
		exit;
	}
}
//Delete by id
$id = isset( $requestParams['id'] ) ? $requestParams['id'] : '';
if( $id ) {
    $where = '`id`= ?';
    $params = array( intval( $id ));
    $result = DBBase::deleteRows( 'tickets', $where, $params  );
	if( is_null( $result ) ) { // Error occurred
		admintickets_buildDoc();
		exit;
	}
}
//Delete by time
$time = isset( $requestParams['time'] ) ? $requestParams['time'] : '';
if( $time ) {
	$where = '`logon` <= ?';
	$params = array( strval( $time ));
	$result = DBBase::deleteRows( 'tickets', $where, $params  );
	if( is_null( $result ) ) { // Error occurred
		admintickets_buildDoc();
		exit;
	}
}

function timeConverter( $val )
{
	$val_array = preg_split( '/[T]/', $val );
	$date_array = preg_split( '/[-]/', $val_array['0'] );
	$date_formated = $date_array[2]."-".$date_array[1]."-".$date_array[0];
	return $date_formated." ".$val_array['1'];
}

//1 delete based on usr
$fields = array('usr', 'clientname', 'clientip', 'appname', 'appversion', 'expire', 'logon');
$orderBy = array( 'usr' => true );
$rows = DBBase::listRows( 'tickets', '', null, '', $fields, array(), $orderBy );
if( is_null( $rows ) ) {
	admintickets_buildDoc();
	exit;
}
$user2NumTickets = array();
if( $rows ) foreach( $rows as $row ) {
	$usr = $row['usr'];
	if( !isset( $user2NumTickets[ $usr ] ) ) {
		$user2NumTickets[ $usr ] = 1;
	} else {
		$user2NumTickets[ $usr ]++;
	}
}

print "<h2>".BizResources::localize( "LIC_USAGE_LIMIT_REACHED" )."</h2>";
print BizResources::localize( "LIC_CHOOSE_DELETE_METHOD" );
print "<form method='post' action='#'>";
print "<input type='hidden' name='adminUser' value='$adminUser'>";
$tempsessionid = session_id();
print "<input type='hidden' name='ww_userlimit_admin_session' value='$tempsessionid'>";
print "<h3>".BizResources::localize( "LIC_1_DELETE_TICKETS_OF_USERS" )."</h3>";
print BizResources::localize( "LIC_TICKETS_OF_USER" )."<select name='usr'>";
print "<option value=''>".BizResources::localize( "LIC_PLEASE_CHOOSE" )."</option>";

foreach( $user2NumTickets as $usr => $numTickets )
	print "<option value=\"".htmlspecialchars( $usr )."\">$usr ($numTickets)</option>\n";

print "</select>"."<input type='submit' value='Delete'>"."</form>";
print "<h3>".BizResources::localize( "LIC_2_DELETE_LONGEST_TICKETS" )."</h3>";
print "<form method='post' action='#' name='bytime'>";
print "<input type='hidden' name='adminUser' value='$adminUser'>";
print "<input type='hidden' name='ww_userlimit_admin_session' value='$tempsessionid'>";
print "<input type='hidden' name='id' value=''>";
print "<input type='hidden' name='time' value=''>";
print "</form>";
?>

    <script language='Javascript' type='text/Javascript'>
        <!--
        function delById(id) {
            var f = document.forms.bytime;
            f.id.value = id;
            f.submit();
        }

        function delBeforeTime(t) {
            var f = document.forms.bytime;
            f.time.value = t;
            f.submit();
        }

        //-->
    </script>

<?php

$numToShow = 20;
$fields = array('id', 'usr', 'clientname', 'clientip', 'appname', 'appproductcode', 'appversion', 'expire', 'logon');
$orderBy = array( 'logon' => true );
$rows = DBBase::listRows( 'tickets', '', null, '', $fields, array(), $orderBy );
if( is_null( $rows ) ) {
	admintickets_buildDoc();
	exit;
}

$txt = "";
$txt .= "<tr bgcolor='#DDDDDD'>";
$txt .= "<th class='text'>#</td>";
$txt .= "<th class='text'>User</td>";
$txt .= "<th class='text'>Name</td>";
$txt .= "<th class='text'>IP</td>";
$txt .= "<th class='text'>Application</td>";
$txt .= "<th class='text'>AppCode</td>";
$txt .= "<th class='text'>Logon</td>";
$txt .= "<th class='text'>Expire</td>";
$txt .= "<th class='text'>&nbsp;</td>";
$txt .= "<th class='text'>&nbsp;</td>";
$txt .= "</tr>";

// Build table rows with the results.
$n = 0;
if( $rows ) foreach( $rows as $row ) {
	$n++;
	$txt .= "<tr bgcolor='#DDDDDD'>";
	$txt .= "<td class='text'>$n.</td>";
	$txt .= "<td class='text'>";
	$txt .= $row['usr'];
	$txt .= "</td>";
	$txt .= "<td class='text'>";
	$row['usr'] = strtolower( $row['usr'] );
	$users = array_change_key_case( $users );
	if( array_key_exists( $row['usr'], $users ) ) {
		$txt .= $users[ $row['usr'] ];
	} else {
		$txt .= "&nbsp;";
	}
	$txt .= "</td>";
	$txt .= "<td class='text'>";
	$txt .= $row['clientip'];
	$txt .= "</td>";
	$txt .= "<td class='text'>";
	$txt .= $row['appname'];
	$txt .= "</td>";
	$txt .= "<td class='text'>";
	$txt .= $row['appproductcode'];
	$txt .= "</td>";
	$logontime = timeConverter( $row['logon'] );
	$txt .= "<td class='text'>";
	$txt .= $logontime;
	$txt .= "</td>";
	$txt .= "<td class='text'>";
	$txt .= timeConverter( $row['expire'] );
	$txt .= "</td>";
	$txt .= "<td class='text'>";
	$txt .= "<a href='javascript:delById(".$row['id'].")'>".BizResources::localize( "ACT_DEL" )."</a>";
	$txt .= "</td>";
	$txt .= "<td class='text'>";
	if( $n > 1 )
		$txt .= "<a href='javascript:delBeforeTime( \"".$row['logon']."\");'>".BizResources::localize( "LIC_DELETE_ALL_TICKETS_1_TO" )." $n</a>";
	else
		$txt .= '&nbsp;';
	$txt .= "</td>";
	$txt .= "</tr>";

	if( $n >= $numToShow )
		break;
}

print "<table>";
print $txt;
print "</table>";

$lic->showStatusInHTML( true );

print "<a href='../../apps/login.php?logout=true'>".BizResources::localize( "LIC_RELOGON" )."</a>";

admintickets_buildDoc();
function admintickets_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();
	require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
	$txt = HtmlDocument::buildDocument( $txt, true, null, false, true );
	print $txt;
}
