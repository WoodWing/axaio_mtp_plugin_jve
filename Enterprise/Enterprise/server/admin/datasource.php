<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

checkSecure('publadmin');

// load the template
$tpl = HtmlDocument::loadTemplate('datasource.htm');

// get the mode to run in (new/details)
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'none';
$error = '';
$inDatasourceId = isset($_REQUEST['datasource']) ? intval($_REQUEST['datasource']) : 0;
$inName = isset($_POST['name']) ? $_POST['name'] : '';
$inType = isset($_POST['type']) ? $_POST['type'] : '';
$inBidirectional = isset($_POST['bidirectional']) ? 'on' : '';

switch( $mode )
{
	case 'new':
		$mode_title = '<!--RES:DS_NEW_TITLE-->'; // Create a Data Source
		if( isset($_REQUEST['ok']) ) {
			DataSourceAdminApp::newDataSource( $inName, $inType, $inBidirectional, $error );
		}
	break;
	
	case 'details':
		$mode_title = ' - <!--RES:DS_DETAILS_TITLE-->'; // Details
		if( isset($_REQUEST['ok']) ) // save changes
			DataSourceAdminApp::saveDataSource( $inDatasourceId, $inName, $inBidirectional, $error );

		if( isset($_REQUEST['delete']) ) // delete datasource
			DataSourceAdminApp::deleteDataSource( $inDatasourceId );

	break;
	
	case 'none':
	default: die('<font color="red">ERROR: Invalid Redirect<br/>You were not properly redirected to this page.<br/></font>'); // BZ#636
}

// is a datasource selected?
$datasourceInfo = new stdClass();
if( $inDatasourceId > 0 )
{
	// get the data source
	$datasourceInfo = BizAdminDatasource::getDatasourceInfo( $inDatasourceId );
}

$dsname = property_exists($datasourceInfo,"Name") ? $datasourceInfo->Name : '';
$dstype = property_exists($datasourceInfo,"Type") ? $datasourceInfo->Type : '';
$dsid	= property_exists($datasourceInfo,"ID") ? intval($datasourceInfo->ID) : 0;
$dsbi	= property_exists($datasourceInfo,"Bidirectional") ? $datasourceInfo->Bidirectional : '';

// parse error
$tpl = str_replace("<!--ERROR-->",$error,$tpl);

$name_replace = "<input type=\"text\" name=\"name\"";
if($error) $name_replace .= " value=\"".formvar($inName)."\"";
else $name_replace .= " value=\"".formvar($dsname)."\"";
$name_replace .= ">";
$tpl = str_replace("<!--VAR:NAME-->",$name_replace,$tpl);

$types = BizAdminDatasource::getDatasourceTypes();
$typeList = "<select name=\"type\"";
if( $dstype != "" ) $typeList .= " disabled";
$typeList .= ">";
foreach( $types as $type )
{
	$typeList .= "<option value=\"".formvar($type->Type)."\"";
	if($error && $type->Type == $inType) $typeList .= " selected";
	elseif( $type->Type == $dstype) $typeList .= " selected";
	$typeList .= ">".formvar($type->Type)."</option>";
}
$typeList .= "</select>";

$tpl = str_replace("<!--VAR:TYPE-->",$typeList,$tpl);

$bidirectional_replace = "<input type=\"checkbox\" name=\"bidirectional\"";
if( ($error && $inBidirectional == 'on') || ($dsbi == 1) ) $bidirectional_replace .= " checked";
$bidirectional_replace .= ">";
$tpl = str_replace("<!--VAR:BIDIRECTIONAL-->",$bidirectional_replace,$tpl);

if( $inDatasourceId > 0 )
{
	$okButton = "<!--RES:BUT_SAVE_DS-->"; // Save
	$cancelButton = "<input type=\"submit\" value=\"Delete\" name=\"delete\" onClick=\"return confirm('<!--RES:DS_DELETE_CONFIRM-->')\">"; // Are you sure you want to delete this datasource?
}else{
	$okButton = "<!--RES:BUT_CREATE_DS-->"; // Create 
	$cancelButton = "<input type=\"reset\" value=\"<!--RES:BUT_RESET-->\" name=\"reset\">";
}
$tpl = str_replace("<!--PAR:BUT_OK-->",$okButton,$tpl);
$tpl = str_replace("<!--PAR:BUT_CANCEL-->",$cancelButton,$tpl);


$tpl = str_replace("<!--VAR:HIDDEN-->","<input type=\"hidden\" name=\"mode\" value=\"".formvar($mode)."\">
										<input type=\"hidden\" name=\"datasource\" value=\"$dsid\">",$tpl);

if( $mode == "details" )
{
	$mode_title = formvar($dsname) . $mode_title;
}

$tpl = str_replace("<!--PAR:PRESELECTED_DATASOURCE-->", $inDatasourceId, $tpl);

// parse the sub-title
$tpl = str_replace("<!--PAR:MODE_TITLE-->",$mode_title,$tpl);

print HtmlDocument::buildDocument($tpl,true,'');

// Admin web application helper
class DataSourceAdminApp 
{
	static public function newDataSource( $name, $type, $bidirectional, &$error )
	{		
		if($bidirectional == "on") {
			$bidirectional = "1";
		}else{
			$bidirectional = "0";
		}
		
		try {
			$datasourceid = BizAdminDatasource::newDatasource($name,$type,$bidirectional);
		} catch ( BizException $e ) {
			$error = $e->getDetail();
		}
		
		if( !$error ) {
			// goto detail view
			header("Location: datasource.php?mode=details&datasource=".$datasourceid);
		}
	}
	
	static public function saveDataSource( $datasourceid, $name, $bidirectional, &$error )
	{
		if($bidirectional == "on") {
			$bidirectional = "1";
		}else{
			$bidirectional = "0";
		}
		
		try {
			BizAdminDatasource::saveDatasource( $datasourceid, $name, $bidirectional );
		} catch (BizException  $e ) {
			$error = $e->getDetail();
		}
		
		if( !$error ) {
			// goto data sources overview
			header("Location: datasources.php?dsSelect=".$datasourceid);
		}
	}
	
	static public function deleteDataSource( $datasourceid )
	{
		BizAdminDatasource::deleteDatasource( $datasourceid );
		// goto data sources overview
		header("Location: datasources.php");
	}
}
?>