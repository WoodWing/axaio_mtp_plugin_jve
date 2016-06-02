<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';

checkSecure('publadmin');

try
{
	$datasources = BizAdminDatasource::queryDatasources();
	$queries = array();
	foreach( $datasources as $datasource )
	{
		$datasourceInfo = BizAdminDatasource::getDatasource($datasource->ID);
		$queries[$datasource->ID] = $datasourceInfo->Queries;
	}
} catch ( BizException $e ) {
	echo '<font color="red">ERROR: '.$e->getMessage().'<br/>'.$e->getDetail().'<br/></font>';
	die();
}

$tpl = HtmlDocument::loadTemplate('datasources.htm');

// print all datasources in html as listed rows
$tplRec = array();
$keysPattern = '/<!--PAR:DATASOURCES_LIST>-->.*<!--<PAR:DATASOURCES_LIST-->/is';
if( preg_match( $keysPattern, $tpl, $tplRec ) > 0 ) {
	$rec = '';
	foreach( $datasources as $datasource )
	{
		$rec .= DataSourcesAdminApp::datasourceInfo2HTML( $tplRec[0], $datasource );
	}
	$tpl = preg_replace( $keysPattern, $rec, $tpl );
}

// embed (hidden) all queries in html as listed rows
$tplRec = array();
$keysPattern = '/<!--PAR:DATASOURCE_QUERIES>-->.*<!--<PAR:DATASOURCE_QUERIES-->/is';
if( preg_match( $keysPattern, $tpl, $tplRec ) > 0 ) {
	$rec = '';
	foreach( $datasources as $datasource )
	{
		$rec .= str_replace('<!--PAR:DATASOURCE_ID-->', $datasource->ID, $tplRec[0]);
	}
	$tpl = preg_replace( $keysPattern, $rec, $tpl );
}

// replace all query lists with a row of queries
$tplRec = array();
foreach( $datasources as $datasource )
{
	$keysPattern = '/<!--PAR:QUERY_LIST#'.$datasource->ID.'>-->.*<!--<PAR:QUERY_LIST#'.$datasource->ID.'-->/is';
	if( preg_match( $keysPattern, $tpl, $tplRec ) > 0 ) {
		$rec = '';
		
		if( count($queries[$datasource->ID]) == 0 )
		{
			$nonQuery = new stdClass();
			$nonQuery->ID = "0";
			$nonQuery->Name = BizResources::localize("DS_NO_QUERIES"); // No queries available..
			$queries[$datasource->ID] = array($nonQuery);
		}
		
		foreach( $queries[$datasource->ID] as $query )
		{
			$rec .= DataSourcesAdminApp::queryInfo2HTML( $tplRec[0], $query );
		}
		$tpl = preg_replace( $keysPattern, $rec, $tpl );
	}
}

$dsSelectReplace = isset($_REQUEST['dsSelect']) ? intval($_REQUEST['dsSelect']) : -1;
$qSelectReplace = isset($_REQUEST['qSelect']) ? intval($_REQUEST['qSelect']) : -1;

$tpl = str_replace('<!--PAR:PRESELECTED_DATASOURCE-->',$dsSelectReplace,$tpl);
$tpl = str_replace('<!--PAR:PRESELECTED_QUERY-->',$qSelectReplace,$tpl);

print HtmlDocument::buildDocument($tpl,true,'');

// Admin web application helper
class DataSourcesAdminApp 
{
	// Replaces all placeholders in html record with datasource info properties
	static public function datasourceInfo2HTML( $rec, $datasourceInfo )
	{
		$rec = str_replace ('<!--PAR:DATASOURCE_NAME-->',   		formvar($datasourceInfo->Name), $rec );
		$rec = str_replace ('<!--PAR:DATASOURCE_ID-->',   			intval($datasourceInfo->ID), $rec );
		$rec = str_replace ('<!--PAR:DATASOURCE_TYPE-->',   		formvar($datasourceInfo->Type), $rec );
		$rec = str_replace ('<!--PAR:DATASOURCE_BIDIRECTIONAL-->',  self::intToCheckBox($datasourceInfo->Bidirectional), $rec );
		return $rec;
	}
	
	// Replaces all placeholders in html record with query info properties
	static public function queryInfo2HTML( $rec, $queryInfo )
	{
		$rec = str_replace ('<!--PAR:QUERY_NAME-->',   		formvar($queryInfo->Name), $rec );
		$rec = str_replace ('<!--PAR:QUERY_ID-->',   		intval($queryInfo->ID), $rec );
		return $rec;
	}
	
	static private function intToCheckBox( $int )
	{
		if( $int == 1 )
		{
			return "checked";
		}else{
			return null;
		}
	}
}
?>