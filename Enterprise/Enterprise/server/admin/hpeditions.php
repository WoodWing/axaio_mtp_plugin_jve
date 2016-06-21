<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure('publadmin');

// database stuff
$dbh = DBDriverFactory::gen();
//BZ#7258
$db = $dbh->tablename('editions');
$dbp = $dbh->tablename('publications');
$dbch = $dbh->tablename('channels');
$dbpi = $dbh->tablename('issues');

// get edition identification
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$name = isset($_REQUEST['ename']) ? trim($_REQUEST['ename']) : '';

// determine incoming mode
if (isset($_REQUEST['vupdate'])) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['delete'])) {
	$mode = 'delete';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}

// get param's
$publ = intval($_REQUEST['publ']); // mandatory
assert($publ > 0);
$channelid = isset($_REQUEST['channelid']) ? intval($_REQUEST['channelid']) : 0;
$issue = isset($_REQUEST['issue']) ? intval($_REQUEST['issue']) : 0;
assert($issue > 0 || $channelid > 0); // edition has either channel or issue as parent
$description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';

// check publication rights
checkPublAdmin($publ);

$errors = array();

// handle request
switch ($mode) {
	case 'update':
		// check not null
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $db where `name` = '" . $dbh->toDBString($name) . "' and `channelid` = $channelid and `issueid` = $issue and `id` != $id";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}

		// DB
		$sql = "update $db set `name`='" . $dbh->toDBString($name) . "', `issueid` = $issue , `description` = '" . $dbh->toDBString($description) . "'" . " where `id` = $id";
		$sth = $dbh->query($sql);
		break;
	case 'insert':
		// check not null
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $db where `name` = '" . $dbh->toDBString($name) . "' and `channelid` = $channelid and `issueid` = $issue";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}

		// DB
		$sql = "INSERT INTO $db (`issueid`, `name`, `channelid`, `description`, `code`) ".
				"VALUES ($issue, '" . $dbh->toDBString($name) . "', $channelid, '" . $dbh->toDBString($description) . "', 0)";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql);
		if (!$id) $id = $dbh->newid($db, true);
		break;
	case 'delete':
		try {
			require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';
			$service = new AdmDeleteEditionsService();
			$request = new AdmDeleteEditionsRequest( $ticket, $publ, $channelid, $issue, array( $id ) );
			$service->execute( $request );
		} catch( BizException $e ) {
			$errors[] = $e->getMessage() . '<br/>' . $e->getDetail();
		}
		break;
}

// delete: back to overview
if( $mode == 'delete' || $mode == 'update' || $mode == 'insert' ) {
	$url = '';
	if( $issue > 0 ) {
		$url = "hppublissues.php?id=$issue";
	} else if( $publ > 0 && $channelid > 0 ) {
		$url = "editChannel.php?publid=$publ&channelid=$channelid";
	}
	if ($url != ''){
		// show errors if there are any
		if (count($errors) > 0) {
			$errorText = implode('<br/>', $errors);
			$errorText = addslashes($errorText);
			$errorText = str_replace('<br/>', '\n', $errorText);
			// show errors and redirect after
			echo '<script type="text/javascript">alert("' . $errorText . '");document.location.href="' .
				 $url . '"</script>';
		} else {
			// redirect directly
			header("Location: $url");
		}
		exit;
	}
}

// generate upper part (edit fields)
{
	if ($mode == 'error') {
		$row = array ('name' => $name, 'description' => $description);
	} elseif ($mode != "new") {
		$sql = "select * from $db where `id` = $id";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
	} else {
		$row = array ('name' => '', 'description' => '');
	}
	$sql = "select * from $dbp where `id` = $publ";
	$sthp = $dbh->query($sql);
	$rowp = $dbh->fetch($sthp);

	$txt = HtmlDocument::loadTemplate( 'hpeditions.htm' );

	// error handling
	$err = '';
	foreach ($errors as $error) {
		$err .= "$error<br>";
	}
	$txt = str_replace("<!--ERROR-->", $err, $txt);

	if ($issue > 0) {
		$sql = "select `name` from $dbpi where `id` = $issue";
		$sth = $dbh->query($sql);
		$rowi = $dbh->fetch($sth);

		$txt = str_replace("<!--VAR:ISSUE-->", formvar($rowi['name']).inputvar( 'issue', $issue, 'hidden' ), $txt);
	} else {
		$txt = preg_replace("/<!--IF:ISSUE-->.*?<!--ENDIF-->/s",'', $txt);
	}
	
	require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
	$rowc = DBChannel::getChannel( $channelid );

	// fields
	$txt = str_replace('<!--VAR:NAME-->', '<input maxlength="255" name="ename" value="'.formvar($row['name']).'"/>', $txt );
	$txt = str_replace('<!--VAR:PUBL-->', formvar($rowp['publication']).inputvar( 'publ', $publ, 'hidden' ), $txt );
	$txt = str_replace('<!--VAR:CHANNEL-->', formvar($rowc['name']).inputvar( 'channelid', $channelid, 'hidden' ), $txt );
	$txt = str_replace('<!--VAR:DESCRIPTION-->', inputvar('description', $row['description'], 'area'), $txt );
	$txt = str_replace('<!--VAR:HIDDEN-->', inputvar( 'id', $id, 'hidden' ), $txt );
	
	if ($id > 0) {
		$str = "publ=$publ&edition=$id&channelid=$channelid";
		if ($issue > 0) $str .= "&issue=$issue";
	}
	
	if( $issue > 0 ) {
		$back = "hppublissues.php?id=$issue";
	} else {
		$back = "editChannel.php?publid=$publ&channelid=$channelid";
	}
	$txt = str_replace("<!--BACK-->", $back, $txt);
}

//set focus to the first field
$txt .= "<script language='javascript'>document.forms[0].ename.focus();</script>";

// generate total page
print HtmlDocument::buildDocument($txt);

?>