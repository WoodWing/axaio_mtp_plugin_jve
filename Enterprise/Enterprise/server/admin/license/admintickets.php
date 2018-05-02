<?php
require_once __DIR__.'/../../../config/config.php';
include_once( BASEDIR."/server/utils/license/license.class.php" );
require_once BASEDIR.'/server/secure.php';

ob_start();
$license = new License();
$connectionMaintenanceApp = new ConnectionMaintenanceApp();
//If no license installed yet: everyone may install the first license (the SCE Server license)
//Once a license has been installed, only admin users may do something here...
if( !$license->hasLicense() ) {
	print BizResources::localize( "LIC_NO_SCENT_LICENSE_INSTALLED" );
	$connectionMaintenanceApp->buildLicensePage();
	exit;
}

require_once BASEDIR.'/server/utils/HttpRequest.class.php';
$requestParams = WW_Utils_HttpRequest::getHttpParams( 'GP' );
$adminUser = isset( $requestParams['adminUser'] ) ? $requestParams['adminUser'] : '';
if( !$connectionMaintenanceApp->checkSessionVariables( $adminUser ) ) {
	print BizResources::localize( "LIC_ACCESS_DENIED" );
	$connectionMaintenanceApp->buildLicensePage();
	exit;
}

$deleteByUser = isset( $requestParams['usr'] ) ? $requestParams['usr'] : '';
if( $deleteByUser ) { // Delete by user.
    require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
	if( is_null( DBTicket::DbPurgeTicketsByUser( $deleteByUser ) ) ) {
		$connectionMaintenanceApp->buildLicensePage();
		exit;
	}
}

$id = isset( $requestParams['id'] ) ? $requestParams['id'] : '';
if( $id ) { //Delete by id
	$where = '`id`= ?';
	$params = array( intval( $id ) );
	$result = DBBase::deleteRows( 'tickets', $where, $params );
	if( is_null( $result ) ) {
		$connectionMaintenanceApp->buildLicensePage();
		exit;
	}
}

$time = isset( $requestParams['time'] ) ? $requestParams['time'] : '';
if( $time ) { //Delete by logon before
	$where = '`logon` <= ?';
	$params = array( strval( $time ) );
	$result = DBBase::deleteRows( 'tickets', $where, $params );
	if( is_null( $result ) ) {
		$connectionMaintenanceApp->buildLicensePage();
		exit;
	}
}

$users = $connectionMaintenanceApp->getUsersInfo();
if( !$users ) {
	$connectionMaintenanceApp->buildLicensePage();
	exit;
}

$ticketRowsSortedByUser = $connectionMaintenanceApp->getTicketsSortBy( 'usr' );
if( is_null( $ticketRowsSortedByUser ) ) {
	$connectionMaintenanceApp->buildLicensePage();
	exit;
}

$ticketRowsSortedByLogon = $connectionMaintenanceApp->getTicketsSortBy( 'logon' );
if( is_null( $ticketRowsSortedByLogon ) ) {
	$connectionMaintenanceApp->buildLicensePage();
	exit;
}

$user2NumTickets = $connectionMaintenanceApp->calculateTicketsByUser( $ticketRowsSortedByUser );
$tempsessionid = session_id();
$license->showStatusInHTML( true );
$connectionMaintenanceApp->printHeaderDeleteSection( $adminUser, $tempsessionid );
$connectionMaintenanceApp->printDeleteByUserSection( $user2NumTickets );
$connectionMaintenanceApp->printDeleteByLogOnTimeSection( $adminUser, $tempsessionid );
$connectionMaintenanceApp->addJsScripts();
$connectionMaintenanceApp->printConnectionsByUserTable( $ticketRowsSortedByLogon, $users );
$connectionMaintenanceApp->buildLicensePage();

/**
 * @package Enterprise
 * @subpackage  License
 * @since 	10.4.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Helper class, strictly bound to above license application page.
 */

class ConnectionMaintenanceApp
{

	/**
	 * Builds default page frame.
	 */
	public function buildLicensePage()
	{
		$txt = ob_get_contents();
		ob_end_clean();
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$txt = HtmlDocument::buildDocument( $txt, true, null, false, true );
		print $txt;
	}

	/**
     * Checks if the user is the same as the one set in the session. Also the hash value set in the session is checked.
     *
	 * @param string $user
	 * @return bool
	 */
	public function checkSessionVariables( $user )
	{
	    $ok = false;
		if( $user ) {
			$sessionName = 'ww_userlimit_admin_session';
			session_name( $sessionName );
			session_start();
			$adminUser = $_SESSION['adminUser'];
			$hash = $_SESSION['hash'];
			$myhash = md5( $user."bla" );
			if( ( $user == $adminUser ) && ( $hash == $myhash ) ) {
				$ok = true;
			}
		}

		return $ok;
	}

	/**
     * Returns basic user information of all users.
     *
	 * @return array
	 */
	public function getUsersInfo()
    {
	    require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
	    $rows = DBBase::listRows( 'users', '', null, '', array( 'fullname', 'user' ) );
	    $users = array();
	    if( $rows ) foreach( $rows as $row ) {
		    $users[ $row['user'] ] = $row['fullname'];
	    }

	    return $users;
    }

	/**
	 * Returns ticket info sorted by $sortBy column.
	 *
	 * @param string $sortBy
	 * @return array
	 */
    public function getTicketsSortBy( $sortBy )
    {
	    require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
	    $fields = array( 'id', 'usr', 'clientname', 'clientip', 'appname', 'appproductcode', 'appversion', 'expire', 'logon' );
	    $orderBy = array( $sortBy => true );
	    $rows = DBBase::listRows( 'tickets', '', null, '', $fields, array(), $orderBy );

	    return $rows;
    }

	/**
	 * Calculates the number of tickets per user.
	 *
	 * @param array $ticketRowsSortedByUser
	 * @return mixed
	 */
    public function calculateTicketsByUser( $ticketRowsSortedByUser )
    {
	    if( $ticketRowsSortedByUser ) foreach( $ticketRowsSortedByUser as $row ) {
		    $usr = $row['usr'];
		    if( !isset( $user2NumTickets[ $usr ] ) ) {
			    $user2NumTickets[ $usr ] = 1;
		    } else {
			    $user2NumTickets[ $usr ]++;
		    }
	    }

	    return $user2NumTickets;
    }

	/**
	 * Generates the general part of the the delete section.
	 *
	 * @param string $adminUser
	 * @param string $tempsessionid
	 */
    public function printHeaderDeleteSection( $adminUser, $tempsessionid )
    {
	    print "<h2>".BizResources::localize( "LIC_USAGE_LIMIT_REACHED" )."</h2>";
	    print BizResources::localize( "LIC_CHOOSE_DELETE_METHOD" );
	    print "<form method='post' action='#'>";
	    print "<input type='hidden' name='adminUser' value='$adminUser'>";
	    print "<input type='hidden' name='ww_userlimit_admin_session' value='$tempsessionid'>";
    }

	/**
	 * Generates the section to select the user of whom the tickets can be deleted.\
	 *
	 * @param array $user2NumTickets
	 */
    public function printDeleteByUserSection( $user2NumTickets )
    {
	    print "<h3>".BizResources::localize( "LIC_1_DELETE_TICKETS_OF_USERS" )."</h3>";
	    print BizResources::localize( "LIC_TICKETS_OF_USER" )."<select name='usr'>";
	    print "<option value=''>".BizResources::localize( "LIC_PLEASE_CHOOSE" )."</option>";
	    foreach( $user2NumTickets as $usr => $numTickets )
		    print "<option value=\"".htmlspecialchars( $usr )."\">$usr ($numTickets)</option>\n";
	    print "</select>"."<input type='submit' value='Delete'>"."</form>";
    }

	/**
	 * Generates the section to select the user of whom the tickets can be deleted.
	 *
	 * @param string $adminUser
	 * @param string $tempSessionId
	 */
    public function printDeleteByLogOnTimeSection( $adminUser, $tempSessionId  )
    {
	    print "<h3>".BizResources::localize( "LIC_2_DELETE_LONGEST_TICKETS" )."</h3>";
	    print "<form method='post' action='#' name='bytime'>";
	    print "<input type='hidden' name='adminUser' value='$adminUser'>";
	    print "<input type='hidden' name='ww_userlimit_admin_session' value='$tempSessionId'>";
	    print "<input type='hidden' name='id' value=''>";
	    print "<input type='hidden' name='time' value=''>";
	    print "</form>";
    }

	/**
	 * Generates the section to deleted tickets based on the log on time.
	 *
	 * @param string $ticketRowsSortedByLogon
	 * @param array $users
	 */
	public function printConnectionsByUserTable( $ticketRowsSortedByLogon, $users ): void
	{
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

		$numberOfConnections = 0;
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		if( $ticketRowsSortedByLogon ) foreach( $ticketRowsSortedByLogon as $row ) {
			$numberOfConnections++;
			$txt .= "<tr bgcolor='#DDDDDD'>";
			$txt .= "<td class='text'>$numberOfConnections.</td>";
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
			$txt .= "<td class='text'>";
			$txt .= DateTimeFunctions::iso2date( $row['logon'] );;
			$txt .= "</td>";
			$txt .= "<td class='text'>";
			$txt .= DateTimeFunctions::iso2date( $row['expire'] );
			$txt .= "</td>";
			$txt .= "<td class='text'>";
			$txt .= "<a href='javascript:delById(".$row['id'].")'>".BizResources::localize( "ACT_DEL" )."</a>";
			$txt .= "</td>";
			$txt .= "<td class='text'>";
			if( $numberOfConnections > 1 )
				$txt .= "<a href='javascript:delBeforeTime( \"".$row['logon']."\");'>".BizResources::localize( "LIC_DELETE_ALL_TICKETS_1_TO" )." $numberOfConnections</a>";
			else
				$txt .= '&nbsp;';
			$txt .= "</td>";
			$txt .= "</tr>";
		}

		print "<table>";
		print $txt;
		print "</table>";
		print "<a href='../../apps/login.php?logout=true'>".BizResources::localize( "LIC_RELOGON" )."</a>";
	}

	/**
	 * Add some javascript functions to handle the selected delete option.
	 */
	public function addJsScripts()
    {
        $txt =
	    "<script language='Javascript' type='text/Javascript'>
        //<![CDATA[
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

        //]]>
        </script>";
        print $txt;
    }
}
