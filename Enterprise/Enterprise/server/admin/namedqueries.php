<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('admin');

// database stuff
$dbh = DBDriverFactory::gen();
$dbnq = $dbh->tablename('namedqueries');

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// determine incoming mode
if( isset($_REQUEST['vdelete']) && $_REQUEST['vdelete'] ) {
	$mode = 'delete';
} else if (isset($_REQUEST['vupdate']) && $_REQUEST['vupdate']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else if (isset($_REQUEST['new']) && $_REQUEST['new']) {
	$mode = 'new';
} else {
	$mode = ($id > 0) ? 'edit' : 'view';
}
// get param's
$query = isset($_REQUEST['query']) ? trim($_REQUEST['query']) : '';
$comment = isset($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
$interface = isset($_REQUEST['interface']) ? $_REQUEST['interface'] : '';
$sqlstat = isset($_REQUEST['sql']) ? $_REQUEST['sql'] : '';
$checkaccess  = isset($_REQUEST['checkaccess'])  && trim($_REQUEST['checkaccess']) ? 'on' : '';

$errors = array();
// handle request
switch ($mode) {
	case 'update':
		// check not null
		if (trim($query) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}
		if(isValid($sqlstat) === false){
			$errors[] = BizResources::localize("ERR_INVALID_SQL");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $dbnq where `query` = '" . $dbh->toDBString($query) . "' and `id` != $id";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}
		if(isValid($sqlstat) === false){
			$errors[] = BizResources::localize("ERR_INVALID_SQL");
			$mode = 'error';
			break;
		}

		// DB
		$sqlstat = $sqlstat;
		$sql = "update $dbnq set";
		$sql .= " `query`='" . $dbh->toDBString( $query ) . "',";
		$sql .= " `comment`='" . $dbh->toDBString( $comment ) . "',";
		$sql .= " `interface`='" . $dbh->toDBString( $interface ) . "',";
		$sql .= " `sql`='" . $dbh->toDBString( $sqlstat ) . "',";
		$sql .= " `checkaccess`='" . $dbh->toDBString($checkaccess) . "'";
		$sql .= " where `id` = $id";
		$sth = $dbh->query($sql);
		break;
	case 'insert':
		// check not null
		if (trim($query) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $dbnq where `query` = '" . $dbh->toDBString( $query ) . "'";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}
		if(isValid($sqlstat) === false){
			$errors[] = BizResources::localize("ERR_INVALID_SQL");
			$mode = 'error';
			break;
		}

		// DB
		$sql = "INSERT INTO $dbnq (`query`, `comment`, `interface`, `sql`, `checkaccess`) ";
		$sql .= "VALUES ('" . $dbh->toDBString($query) . "', ".
					"'" . $dbh->toDBString($comment) . "', ".
					"'" . $dbh->toDBString($interface) . "', ".
					"'" . $dbh->toDBString($sqlstat) . "', ".
					"'" . $dbh->toDBString($checkaccess) . "')";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql);
		if ($id === 0) $id = $dbh->newid($dbnq, true);
		break;
	case 'delete':
		if ($id) {
			$sql = "delete from $dbnq where `id` = $id";
			$sth = $dbh->query($sql);
		}
		break;
}

switch ($mode) {
	case 'edit':
	case 'new':
	case 'error':
		// generate upper part (edit fields)
		if ($mode == 'error')
			$row = array ('query' => $query, 'comment' => $comment, 'interface' => $interface, 'sql' => $sqlstat, 'checkaccess' => $checkaccess);
		elseif ($mode != "new") {
			$sql = "select * from $dbnq where `id` = $id";
			$sth = $dbh->query($sql);
			$row = $dbh->fetch($sth);
		} else
			$row = array ('query' => '', 'comment' => '', 'interface' => '', 'sql' => '', 'checkaccess' => 'on');

		$txt = HtmlDocument::loadTemplate( 'namedqueriesdet.htm' );

		// error handling
		$err = '';
		foreach ($errors as $error) {
			$err .= "$error<br>";
		}
		$txt = str_replace("<!--ERROR-->", $err, $txt);

		//AAA Remove this?? Now you can't insert a " in a query for Oracle?
		//In the former situation, dbindep() would replace ` by " for Oracle
		//Now, the oracle driver will pass the given SQL statement exactly to the database.
		//So no more tricks here.
		//if(strtolower(DBTYPE) == strtolower('oracle')){
		//	$row['sql'] = str_replace('"', '`', $row['sql']);
		//}

		// fields
		$txt = str_replace('<!--VAR:QUERY-->', inputvar( 'id', isset($row['id']) ? $row['id'] : 0, 'hidden').
							'<input maxlength="200" name="query" value="'.formvar($row['query']).'"/>', $txt );
		$txt = str_replace('<!--VAR:COMMENT-->', '<textarea name="comment" rows="5" cols="60">'.formvar($row['comment']).'</textarea>', $txt );
		$txt = str_replace('<!--VAR:INTERFACE-->', '<textarea name="interface" rows="5" cols="60">'.formvar($row['interface']).'</textarea>', $txt );
		$txt = str_replace('<!--VAR:SQL-->', '<textarea name="sql" rows="10" cols="60">'.formvar($row['sql']).'</textarea>', $txt );
		$txt = str_replace('<!--VAR:ACCESS-->', '<input type="checkbox" name="checkaccess"' .(trim($row['checkaccess'])?'checked="checked"':'').' title="<!--RES:QRY_ACCESS_HELP-->"/>', $txt );
		break;
	default:
		$txt = '';
		$sql = "select * from $dbnq order by `query`";
		$sth = $dbh->query($sql);
		while (($row = $dbh->fetch($sth))) {
			$i = $row['id'];
			$txt .= '<tr><td valign="top"><a href="namedqueries.php?id='.$i.'">'.formvar($row['query']).'</a></td>'.
						'<td>'.formvar($row['comment']).'</td></tr>'."\r\n";
		}
		// generate page
		$txt = str_replace('<!--ROWS-->', $txt, HtmlDocument::loadTemplate( 'namedqueries.htm' ) );
		break;
}
print HtmlDocument::buildDocument($txt);

function isValid($sql){
	if(stristr($sql, 'update') !== false){ //tablename is not known
		return false;
	}
	if(stristr($sql, 'insert into') !== false){ //insert is ok as long as its not into something
		return false;
	}
	if(stristr($sql, 'into') !== false){  //select into statement
		return false;
	}
	if(stristr($sql, 'delete from') !== false){ //do not delete my friend!
		return false;
	}
	if(stristr($sql, 'delete *') !== false){ //do not delete everything!
		return false;
	}
	if(stristr($sql, 'create table') !== false){
		return false;
	}
	if(stristr($sql, 'create database') !== false){
		return false;
	}
	if(stristr($sql, 'create view') !== false){
		return false;
	}
	if(stristr($sql, 'alter view') !== false){
		return false;
	}
	if(stristr($sql, 'alter table') !== false){
		return false;
	}
	if(stristr($sql, 'drop view') !== false){
		return false;
	}
	if(stristr($sql, 'drop table') !== false){
		return false;
	}
	if(stristr($sql, 'drop index') !== false){
		return false;
	}
	if(stristr($sql, 'truncate table') !== false){ //deletes all records in table
		return false;
	}
	if(stristr($sql, 'grant') !== false){ //grant rights
		return false;
	}
	if(stristr($sql, 'revoke') !== false){ //revoke rights
		return false;
	}
	return true;
}
