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
$tpl = HtmlDocument::loadTemplate('dsfields.htm');
$_POST["readonly"] = isset($_POST["readonly"]) ? $_POST["readonly"] : '';
$_POST["priority"] = isset($_POST["priority"]) ? $_POST["priority"] : '';

$error = '';

if( isset($_REQUEST['ok']) )
{
	try {
		BizAdminDatasource::saveQueryField( intval($_POST["query"]), $_POST["name"], $_POST["priority"], $_POST["readonly"] );
	} catch( BizException $e ) {
		$error = $e->getDetail();
	}
}

// is a datasource selected?
if( isset($_REQUEST['datasource']) && isset($_REQUEST['query']) )
{
	// get the datasource info
	$datasourceInfo = BizAdminDatasource::getDatasourceInfo( intval($_REQUEST["datasource"]) );
	// get the query info
	$queryInfo = BizAdminDatasource::getQuery( intval($_REQUEST["query"]) );
}else{
	// otherwise die
	die('<font color="red">ERROR: Invalid Redirect<br/>You were not properly redirected to this page.<br/></font>'); // BZ#636
}

if( isset($_REQUEST['mode']) && $_REQUEST['mode'] == "delete" && isset($_POST["field"]) )
{
	// remove the field from the query
	BizAdminDatasource::deleteQueryField( intval($_POST["field"]) );
}

if( isset($_REQUEST['mode']) && $_REQUEST['mode'] == "update" && isset($_POST["field"]) )
{
	try {
		BizAdminDatasource::saveQueryField( intval($_REQUEST["query"]), $_POST["fieldname"], $_POST["priority"], $_POST["readonly"], true );
	} catch( BizException $e ) {
		$error = $e->getDetail();
	}
}

// get the fields by query id
$fields = BizAdminDatasource::getQueryFields( intval($_REQUEST["query"]) );

// parse the sub-title
$tpl = str_replace("<!--PAR:MODE_TITLE-->", formvar($queryInfo->Name) . " - <!--RES:DS_QUERY_FIELDS_TITLE-->",$tpl); // Query Fields

// print all linked publications in html as listed rows
$tplRec = array();
$keysPattern = '/<!--PAR:FIELD_LIST>-->.*<!--<PAR:FIELD_LIST-->/is';
if( preg_match( $keysPattern, $tpl, $tplRec ) > 0 ) {
	$rec = '';
	if( count($fields) > 0 )
	{
		foreach( $fields as $field )
		{
			$rec .= DsFieldsAdminApp::fieldInfo2HTML( $tplRec[0], $field, $datasourceInfo->ID, $queryInfo->ID );
		}
	}
	$tpl = preg_replace( $keysPattern, $rec, $tpl );
}

$tpl = str_replace("<!--VAR:HIDDEN-->","<input type=\"hidden\" name=\"datasource\" value=\"$datasourceInfo->ID\" />
										<input type=\"hidden\" name=\"query\" value=\"".intval($_REQUEST["query"])."\" />",$tpl);
$tpl = str_replace("<!--PAR:PRESELECTED_DATASOURCE-->",$datasourceInfo->ID,$tpl);
$tpl = str_replace("<!--PAR:PRESELECTED_QUERY-->",intval($_REQUEST["query"]),$tpl);

// parse errors
$tpl = str_replace("<!--PAR:ERROR-->", $error, $tpl);

print HtmlDocument::buildDocument($tpl,true,'');


// Admin web application helper
class DsFieldsAdminApp 
{
	static public function fieldInfo2HTML( $rec, $field, $datasourceid, $queryid )
	{
		$rec = str_replace ('<!--PAR:DATASOURCE_ID-->',   		intval($datasourceid), $rec );
		$rec = str_replace ('<!--PAR:QUERY_ID-->',   			intval($queryid), $rec );
		$rec = str_replace ('<!--PAR:FIELD_ID-->',				intval($field->ID), $rec);
		$rec = str_replace ('<!--PAR:FIELD_NAME-->',   			formvar($field->Name), $rec );
		
		$prio = "<input type=\"checkbox\" name=\"priority\"";
		if( $field->Priority == "1" ) $prio .= ' checked="checked"';
		$prio .= " />";
		
		$ro = "<input type=\"checkbox\" name=\"readonly\"";
		if( $field->ReadOnly == "1" ) $ro .= ' checked="checked"';
		$ro .= " />";
		
		$rec = str_replace ('<!--PAR:FIELD_PRIO-->',   			$prio, $rec );
		$rec = str_replace ('<!--PAR:FIELD_READONLY-->',   		$ro, $rec );
		return $rec;
	}
}
