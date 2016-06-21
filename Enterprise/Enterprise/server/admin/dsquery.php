<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

checkSecure('publadmin');

// load the template
$tpl = HtmlDocument::loadTemplate('dsquery.htm');

// get the mode to run in (new/details)
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'none';

$error = '';

switch( $mode )
{
	case 'new':
		$mode_title = '<!--RES:DS_QUERY_CREATE_TITLE-->'; // Create a new Query
		if( isset($_REQUEST['ok']) ) {
			DsQueryAdminApp::newQuery( intval($_REQUEST['datasource']), $_POST['name'], 
					$_POST['sqlquery'], $_POST['interface'], $_POST['comment'], 
					$_POST['recordid'], $_POST['recordfamily'], $error );
		}
	break;
	
	case 'details':
		$mode_title = ' - <!--RES:DS_QUERY_DETAILS_TITLE-->'; // Details
		if( isset($_REQUEST['ok']) ) {
			DsQueryAdminApp::saveQuery( intval($_REQUEST['datasource']), intval($_REQUEST['query']), 
					$_POST['name'], $_POST['sqlquery'], $_POST['interface'], $_POST['comment'], 
					$_POST['recordid'], $_POST['recordfamily'], $error );
		}
		if( isset($_REQUEST['delete']) ) {
			DsQueryAdminApp::deleteQuery( intval($_REQUEST['query']), intval($_REQUEST['datasource']) );
		}
	break;
	
	case "none":
	default: die('<font color="red">ERROR: Invalid Redirect<br/>You were not properly redirected to this page.<br/></font>'); // BZ#636
}

if( isset($_REQUEST['datasource']) )
{
	// get datasource info
	$datasourceInfo = BizAdminDatasource::getDatasourceInfo( intval($_REQUEST['datasource']) );
	
	if( isset($_REQUEST['query']) )
	{
		// get query
		$queryObj = BizAdminDatasource::getQuery( intval($_REQUEST['query']) );
	}
	
	$queryid = isset($_REQUEST['query']) ? intval($queryObj->ID) : 0;
	$name = isset($_REQUEST['query']) ? $queryObj->Name : '';
	$sqlquery = isset($_REQUEST['query']) ? $queryObj->Query : '';
	$interface = isset($_REQUEST['query']) ? $queryObj->Interface : '';
	$comment = isset($_REQUEST['query']) ? $queryObj->Comment : '';
	$recordid = isset($_REQUEST['query']) ? $queryObj->RecordID : ''; // NOTE: this is not supposed to be an integer!! Identifiers come in all shapes and sizes ;-)
	$recordfamily = isset($_REQUEST['query']) ? $queryObj->RecordFamily : '';
	
	$sqlquery = isset($_POST['sqlquery']) ? $_POST['sqlquery'] : $sqlquery;
	$interface = isset($_POST['interface']) ? $_POST['interface'] : $interface;
	$comment = isset($_POST['comment']) ? $_POST['comment'] : $comment;
	$recordid = isset($_POST['recordid']) ? intval($_POST['recordid']) : $recordid;
	$recordfamily = isset($_POST['recordfamily']) ? $_POST['recordfamily'] : $recordfamily;
	
}else{
	die('<font color="red">ERROR: Invalid Redirect<br/>You were not properly redirected to this page.<br/></font>'); // BZ#636
}

$name_replace = "<input type=\"text\" name=\"name\" value=\"".formvar($name)."\">";
$tpl = str_replace("<!--VAR:NAME-->",$name_replace,$tpl);

$query_replace = "<textarea name=\"sqlquery\" rows=8 cols=60>".formvar($sqlquery)."</textarea>";
$tpl = str_replace("<!--VAR:QUERY-->",$query_replace,$tpl);

$interface_replace = "<textarea name=\"interface\" rows=8 cols=60>".formvar($interface)."</textarea>";
$tpl = str_replace("<!--VAR:INTERFACE-->",$interface_replace,$tpl);

$comment_replace = "<textarea name=\"comment\" rows=8 cols=60>".formvar($comment)."</textarea>";
$tpl = str_replace("<!--VAR:COMMENT-->",$comment_replace,$tpl);

$recordid_replace = "<input type=\"text\" name=\"recordid\" value=\"".$recordid."\">";
$tpl = str_replace("<!--VAR:RECORDID-->",$recordid_replace,$tpl);

$recordfamily_replace = "<input type=\"text\" name=\"recordfamily\" value=\"".formvar($recordfamily)."\">";
$tpl = str_replace("<!--VAR:RECORDFAMILY-->",$recordfamily_replace,$tpl);

if( isset($_GET['query']) )
{
	$okButton = "<!--RES:BUT_SAVE_DS-->";
	$cancelButton = "<input type=\"submit\" value=\"Delete\" name=\"delete\" onClick=\"return confirm('<!--RES:DS_QUERY_DELETE-->')\">"; // Are you sure you want to delete this query?
}else{
	$okButton = "<!--RES:BUT_CREATE_DS-->"; 
	$cancelButton = "<input type=\"reset\" value=\"<!--RES:BUT_RESET-->\" name=\"reset\">";
}
$tpl = str_replace("<!--PAR:BUT_OK-->",$okButton,$tpl);
$tpl = str_replace("<!--PAR:BUT_CANCEL-->",$cancelButton,$tpl);


$tpl = str_replace("<!--VAR:HIDDEN-->","<input type=\"hidden\" name=\"mode\" value=\"".formvar($mode)."\">
										<input type=\"hidden\" name=\"datasource\" value=\"$datasourceInfo->ID\">
										<input type=\"hidden\" name=\"query\" value=\"$queryid\">",$tpl);

if( $mode == "details" )
{
	$mode_title = formvar($name).$mode_title;
	$tpl = str_replace("<!--PAR:PRESELECTED_QUERY-->",intval($_REQUEST["query"]),$tpl);
	$tpl = str_replace("<!--PAR:PRESELECTED_DATASOURCE-->",intval($_REQUEST['datasource']),$tpl);
}

$tpl = isset($_REQUEST["query"]) ? str_replace("<!--PAR:PRESELECTED_QUERY-->",intval($_REQUEST["query"]),$tpl) : str_replace("<!--PAR:PRESELECTED_QUERY-->",'0',$tpl);
$tpl = isset($_REQUEST["datasource"]) ? str_replace("<!--PAR:PRESELECTED_DATASOURCE-->",intval($_REQUEST["datasource"]),$tpl) : str_replace("<!--PAR:PRESELECTED_DATASOURCE-->","",$tpl);

// parse the sub-title
$tpl = str_replace("<!--PAR:MODE_TITLE-->",$mode_title,$tpl);

// parse errors
$tpl = str_replace("<!--PAR:ERROR-->", $error, $tpl);

print HtmlDocument::buildDocument($tpl,true,'');

// Admin web application helper
class DsQueryAdminApp 
{
	static public function newQuery( $datasourceid, $name, $sqlquery, $interface, $comment, $recordid, $recordfamily, &$error )
	{
		try {
			$newqueryid = BizAdminDatasource::newQuery( $datasourceid, $name, $sqlquery, $interface, $comment, $recordid, $recordfamily );	
		} catch ( BizException $e) {
			$error = $e->getDetail();
		}
		
		if( !$error )
		{
			// go to detail view
			header("Location: dsquery.php?mode=details&datasource=".$datasourceid."&query=".$newqueryid);
		}
	}
	
	static public function deleteQuery( $queryid, $datasourceid )
	{
		BizAdminDatasource::deleteQuery( $queryid );
		// get back to overview
		header("Location: datasources.php?dsSelect=".$datasourceid);
	}
	
	static public function saveQuery( $datasourceid, $queryid, $name, $sqlquery, $interface, $comment, $recordid, $recordfamily, &$error )
	{
		try {
			BizAdminDatasource::saveQuery( $queryid, $name, $sqlquery, $interface, $comment, $recordid, $recordfamily );
		} catch( BizException $e ) {
			$error = $e->getDetail();
		}
		
		if( !$error )
		{
			// go to detail view
			header("Location: dsquery.php?mode=details&datasource=".$datasourceid."&query=".$queryid);
		}
	}
}
?>