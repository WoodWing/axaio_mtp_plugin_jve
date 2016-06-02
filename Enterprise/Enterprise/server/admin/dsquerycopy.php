<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';

checkSecure('publadmin');

// load the template
$tpl = HtmlDocument::loadTemplate('dsquerycopy.htm');

$_REQUEST["target"] = isset($_REQUEST["target"]) ? $_REQUEST["target"] : '';
$_REQUEST["datasource"] = isset($_REQUEST["datasource"]) ? $_REQUEST["datasource"] : '';
$_POST["copyfields"] = isset($_POST["copyfields"]) ? $_POST["copyfields"] : '';

$error = '';

if( isset($_POST["Copy"]) && isset($_POST["query"]) )
{
	DsQueryCopyApp::copyQuery( intval($_POST["query"]), $_POST["target"], $_POST["newname"], $_POST["copyfields"], $error );
}

$type = BizAdminDatasource::getDatasourceType( intval($_REQUEST["datasource"]) );

$datasourceList = "<select name='target'>";
$datasources = BizAdminDatasource::queryDatasources($type);
foreach( $datasources as $datasource )
{
	$datasourceList .= "<option value='$datasource->ID'";
	if( isset($_REQUEST["target"]) || isset($_REQUEST["datasource"]) && 
		($datasource->ID == intval($_REQUEST["datasource"]) || $datasource->ID == intval($_REQUEST["target"])) ) {
		$datasourceList .= " selected"; 
	}
	$datasourceList .=">".formvar($datasource->Name)."</option>";
}
$datasourceList .= "</select>";

$tpl = str_replace("<!--PAR:DATASOURCE_LIST-->",$datasourceList,$tpl);

$tpl_newname = isset( $_POST["newname"] ) ? $_POST["newname"] : '';
$tpl_copyfields = isset( $_POST["copyfields"] ) ? 'checked' : '';
$tpl = str_replace("<!--VAR:NEWNAME-->", formvar($tpl_newname),$tpl);
$tpl = str_replace("<!--VAR:COPYFIELDS-->",$tpl_copyfields,$tpl);

$tpl = str_replace("<!--PAR:PRESELECTED_DATASOURCE-->",intval($_REQUEST["datasource"]),$tpl);
$tpl = str_replace("<!--PAR:PRESELECTED_QUERY-->",intval($_REQUEST["query"]),$tpl);

$query = BizAdminDatasource::getQuery( intval($_REQUEST["query"]) );
$tpl = str_replace("<!--PAR:SUB_TITLE-->", formvar($query->Name), $tpl);

$tpl = str_replace("<!--PAR:HIDDEN-->","<input type='hidden' value='".intval($_REQUEST["query"])."' name='query'>
										<input type='hidden' value='".intval($_REQUEST["datasource"])."' name='datasource'>",$tpl);

$tpl = str_replace("<!--PAR:ERROR-->",$error,$tpl);

print HtmlDocument::buildDocument($tpl,true,'');

// Admin web application helper
class DsQueryCopyApp 
{
	public static function copyQuery( $queryid, $targetid, $newname, $copyfields, &$error )
	{
		// check the newname
		self::checkNewName( $targetid, $newname, $error );
		
		if( !$error )
		{
			try {
				$new_query_id= BizAdminDatasource::copyQuery( $queryid, $targetid, $newname, $copyfields );
			} catch( BizException $e ) {
				$error = $e->getDetail();
			}
		}
		
		if( $new_query_id )
		{
			header("Location: datasources.php?dsSelect=".$targetid."&qSelect=".$new_query_id);
		}
	}
	
	private static function checkNewName( $targetid, $newname, &$error )
	{
		if( trim($newname) == "")
		{
			$error = "The new name of the query was empty."; // BZ#636
			return;
		}else{
			
			// the code below is not efficient, but is better then a crash (Rev#18673 introcuded a crash; reference to a non existing database function)
			// the code below is a re-implementation of the code in v6.0, which was working
			$queries = BizAdminDatasource::getQueries( $targetid );
			foreach( $queries as $query )
			{
				if( $query->Name == $newname )
				{
					$error = "The new name of the query already exists."; // BZ#36
					return;
				}
			}
			
			/*$query = DBDatasource::getQueryByName( $newname );
			if( $query[0] )
			{
				$error = "The new name of the query already exists."; // BZ#636
			}*/
		}
	}
}
?>