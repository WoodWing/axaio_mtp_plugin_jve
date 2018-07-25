<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure( 'admin' );
$tpl = HtmlDocument::loadTemplate( 'userqueries.htm' );

// Get form params
$inUser = isset($_POST['user']) ? $_POST['user'] : '';
$inQueryCount = isset($_POST['queryCount']) ? intval($_POST['queryCount']) : 0;
$inUserCount = isset($_POST['userCount']) ? intval($_POST['userCount']) : 0;
$inGroupCount= isset($_POST['groupCount']) ? intval($_POST['groupCount']) : 0;
$k=0;
$success=false;
$dbDriver = DBDriverFactory::gen();
if (isset($_POST['submitButton']))
 {
	// Create a array of users from the groups selected.
	if($inGroupCount>0)
	{
		$users = array();
		$db = $dbDriver->tablename("groups");
		$db2 = $dbDriver->tablename("usrgrp");
		for($k=0;$k<$inGroupCount;$k++)
		{
			if(isset($_POST["groupCheck".$k])){
				$ID = intval($_POST['group'.$k]);
				//$sql="Select * from $db where id=$ID"
				$sql="select a.`usrid` as `uid` from $db2 a where a.`grpid`=$ID";
				$sth = $dbDriver->query($sql);
				if (!$sth) die("something went wrong");
				while (($row = $dbDriver->fetch($sth))) {
					$UID = $row['uid'];
					//$value = $row['value'];
					$udb = $dbDriver->tablename("users");
					$sql = "select `user` from $udb where `id`=$UID";
					$sth1 = $dbDriver->query($sql);
					while (($row1 = $dbDriver->fetch($sth1))) {
						$users[] = $row1['user'];
					}
				}
			}
		}

	}
	if ($inQueryCount > 0 && $inUserCount > 0)
	 {
		$queries = array();
		for ($i = 0; $i < $inQueryCount; $i++) {
			if (isset($_POST["queryCheck".$i])) {
				$queries[] = intval($_POST["queryID".$i]);
			}
		}
		//print_r($queries);
		//$inUserCount=$inUserCount+$inGroupCount;
		//Replacing i with k to assign the count from the last number left.
		for ($i = 0; $i < $inUserCount; $i++) {
			if (isset($_POST["userCheck".$i])) {
				$user = $_POST["user".$i];
				$users[] = $user;
			}
		}

		// Replace the User Queries (by deleting and inserting records)
		$queriesCount = count($queries);
		$usersCount = count($users);
		$db = $dbDriver->tablename("settings");
		for( $i = 0; $i < $queriesCount; $i++ ) {
			$success = false;
			$id = $queries[$i];
			$sql = "SELECT * from $db WHERE `id` = $id";
			$sth = $dbDriver->query( $sql );
			if( !$sth ) break;
			$row = $dbDriver->fetch( $sth );
			if( !$row ) break;
			$setting = $row['setting'];
			$appName = $row['appname'];
			$value = $row['value'];
			for( $j = 0; $j < $usersCount; $j++ ) {
				$user = $dbDriver->toDBString( $users[$j] );
				$sql = "DELETE FROM $db WHERE `user` = '$user' ".
					"AND `setting` = '".$dbDriver->toDBString($setting)."' ".
					"AND `appname` = '".$dbDriver->toDBString($appName)."'";
				$sth = $dbDriver->query( $sql );
				if( !$sth ) {
					$succes = false;
					break 2; // break out both loops!
				}
				$sql = "INSERT INTO $db (`user`, `setting`, `value`, `appname`) ".
					"VALUES ('$user', '".$dbDriver->toDBString($setting)."', #BLOB#, ".
					"'".$dbDriver->toDBString($appName)."')";
				$sql = $dbDriver->autoincrement( $sql );
				$sth = $dbDriver->query( $sql, array(), $value );
				if( !$sth ) {
					$succes = false;
					break 2; // break out both loops!
				}
				$success = true;
			}
		}
		if( $success ) {
			$message = BizResources::localize("ERR_SUCCESS_COPY");
		} else {
			$message = BizResources::localize("ERR_SUCCESS_COPY_E");
		}
	}
}

// Fill user combo
$db = $dbDriver->tablename("users");
$sql = "SELECT `id`, `user`  from $db order by `user`";
$sth = $dbDriver->query($sql);
if (!$sth) exit;

$txt = '<select name="user" style="width:100%" onchange="submit();">';
$txt .= "<option></option>";
while (($row = $dbDriver->fetch($sth))) {
	$user = $row['user'];
	if ($user == $inUser) {
		 $txt .= '<option value="' . formvar($user). '" selected="selected">' . formvar($user) . '</option>';
	} else {
		$txt .= '<option value="' . formvar($user) . '">' . formvar($user). '</option>';
	}
}
$txt .= '</select>';

$tpl = str_replace ("<!--USERCOMBO-->", $txt, $tpl);

if ($inUser) {
	require_once BASEDIR.'/server/bizclasses/BizUserSetting.class.php';
	$bizUserSettings = new WW_BizClasses_UserSetting();

	// Fill user userqueries
	$db = $dbDriver->tablename('settings');
	$sql = "SELECT * FROM $db WHERE `user` = '" . $dbDriver->toDBString($inUser) . "' ORDER BY `setting`, `appname`";
	$sth = $dbDriver->query($sql);
	$txt = '';
	if( $sth ) {
		$queryCount = 0;
		while( ($row = $dbDriver->fetch($sth)) ) {
			// Examples of user settings: UserQuery_... or UserQuery2_... or QueryPanels
			// Filter out the UserQuery settings with following pattern: UserQuery[<ver>]_<name>
			// In other terms, we ignore other settings, such as QueryPanels.
			$underscore = strpos( $row['setting'], '_' ); // position of underscore in UserQuery[<ver>]_<name>
			if( $underscore !== FALSE && substr( $row['setting'], 0, strlen('UserQuery') ) == 'UserQuery' ) {
				$userQuery = substr( $row['setting'],$underscore+1 ); // get name of user query
				$clientAppName = $bizUserSettings->enrichClientAppNameForDisplay( $row['appname'] );
				$txt .= '<div style="white-space: nowrap;"><input type="checkbox" name="queryCheck'.$queryCount.'"/>'.
					'&nbsp;'.formvar($userQuery).'&nbsp;('.formvar($clientAppName).')';
				$txt .= '<input type="hidden" name="queryID'.$queryCount.'" value="'.$row['id'].'"/></div>';
				$queryCount++;
			}
		}
		$txt .= inputvar( 'queryCount', $queryCount, 'hidden' );
	}
	$tpl = str_replace ('<!--USERQUERIES-->', $txt, $tpl);
} else {
	$tpl = str_replace ('<!--USERQUERIES-->', '', $tpl);
}


// Fill other userlist
$db = $dbDriver->tablename("users");
$sql = "SELECT `id`, `user`  from $db order by `user`";
$sth = $dbDriver->query($sql);
if (!$sth) exit;
$userCount = 0;
$txt = "";
while (($row = $dbDriver->fetch($sth))) {
	$user = $row['user'];
	if ($user != $inUser) {
		 $txt .= '<input type="checkbox" name="userCheck'.$userCount.'" />&nbsp;' . formvar($row['user']) .
		 		inputvar( 'user'.$userCount, $row['user'], 'hidden') . '<br/>';
		$userCount++;
	}
}
$txt .= inputvar( 'userCount', $userCount, 'hidden' );

// Fill groups list
$db = $dbDriver->tablename("groups");
$sql = "SELECT `id`, `name`  from $db order by `name`";
$sth = $dbDriver->query($sql);
if (!$sth) exit;
$groupCount = 0;
$grouptxt = "";
while (($row = $dbDriver->fetch($sth))) {
	$grouptxt .= '<input type="checkbox" name="groupCheck'.$groupCount.'" />&nbsp;' . formvar($row['name']) . 
				inputvar( 'group'.$groupCount, $row['id'], 'hidden' ) . '<br/>';
	$groupCount++;
}
$grouptxt .= inputvar( 'groupCount', $groupCount, 'hidden' );

$tpl = str_replace ("<!--OTHERUSERS-->", $txt, $tpl);
$tpl = str_replace ("<!--GROUP-->", $grouptxt, $tpl);

$tpl = str_replace ("<!--CONTENT-->", "", $tpl);

print HtmlDocument::buildDocument($tpl);
if( isset($message) && !empty($message) )
{
	echo '<script type="text/javascript">';
	echo "alert(\"$message !\");";
	echo "document.location.href='userqueries.php';";
	echo "</script>";
}

?>