<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

checkSecure('publadmin');

// load the template
$tpl = HtmlDocument::loadTemplate('dspublications.htm');

if( isset($_REQUEST['ok']) && isset($_POST["publication"]) && $_POST["publication"] != "0" )
{
	BizAdminDatasource::savePublication( intval($_REQUEST["datasource"]), intval($_POST["publication"]) );
}

// is a datasource selected?
if( isset($_REQUEST['datasource']) )
{
	// get the datasource info
	$datasourceInfo = BizAdminDatasource::getDatasourceInfo( intval($_REQUEST["datasource"]) );
}else{
	// otherwise die
	die('<font color="red">ERROR: Invalid Redirect<br/>You were not properly redirected to this page.<br/></font>'); // BZ#636
}

if( isset($_REQUEST['mode']) && $_REQUEST['mode'] == "delete" && isset($_POST["publication"]) )
{
	// remove the publication from the datasource
	$error = '';
	BizAdminDatasource::deletePublication( $datasourceInfo->ID, intval($_POST["publication"]), $error );
}

// get the publications
$datasource = BizAdminDatasource::getDatasource( $datasourceInfo->ID );
$linkedpublications = $datasource->Publications;

$publications = BizAdminDatasource::getPublications();

// parse the sub-title
$tpl = str_replace("<!--PAR:MODE_TITLE-->", formvar($datasourceInfo->Name) . " - <!--RES:DS_PUBLICATIONS_TITLE-->",$tpl); // Publications

// print all linked publications in html as listed rows
$tplRec = array();
$keysPattern = '/<!--PAR:PUBLICATION_LIST>-->.*<!--<PAR:PUBLICATION_LIST-->/is';
if( preg_match( $keysPattern, $tpl, $tplRec ) > 0 ) {
	$rec = '';
	foreach( $linkedpublications as $publication )
	{
		$rec .= DsPublicationsAdminApp::publicationInfo2HTML( $tplRec[0], $publication, $datasourceInfo->ID );
		//$rec .= DataSourcesAdminApp::datasourceInfo2HTML( $tplRec[0], $datasource );
	}
	$tpl = preg_replace( $keysPattern, $rec, $tpl );
}

$publicationList = "<select name=\"publication\">";
$listentries = false;
foreach( $publications as $publication )
{
	// if the publication is already linked, don't show it!
	$linked = false;
	foreach( $linkedpublications as $linkedpublication )
	{
		if( $linkedpublication->ID == $publication->ID )
		{
			$linked = true;
		}
	}
	
	if( $linked == false )
	{
		$publicationList .= "<option value=\"$publication->ID\">".formvar($publication->Name)."</option>";
		$listentries = true;
	}
}
if( $listentries == false )
{
	$publicationList .= "<option value=\"0\"><!--RES:DS_NO_PUBLICATIONS--></option>"; // No publications
}
$publicationList .= "</select>";
$tpl = str_replace("<!--VAR:PUBLICATIONS_LIST-->",$publicationList,$tpl);

$tpl = str_replace("<!--VAR:HIDDEN-->","<input type=\"hidden\" name=\"datasource\" value=\"$datasourceInfo->ID\">",$tpl);
$tpl = str_replace("<!--PAR:PRESELECTED_DATASOURCE-->",$datasourceInfo->ID,$tpl);

print HtmlDocument::buildDocument($tpl,true,'');

// Admin web application helper
class DsPublicationsAdminApp 
{
	static public function publicationInfo2HTML( $rec, $publicationInfo, $datasourceid )
	{
		$rec = str_replace ('<!--PAR:DATASOURCE_ID-->',   		intval($datasourceid), $rec );
		$rec = str_replace ('<!--PAR:PUBLICATION_NAME-->',   	formvar($publicationInfo->Name), $rec );
		$rec = str_replace ('<!--PAR:PUBLICATION_DESC-->',   	formvar($publicationInfo->Description), $rec );
		$rec = str_replace ('<!--PAR:PUBLICATION_ID-->',   		intval($publicationInfo->ID), $rec );
		return $rec;
	}
}
?>