<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizAdmDatasource.class.php';

checkSecure('publadmin');

// load the template
$tpl = HtmlDocument::loadTemplate('dscopy.htm');

$error = '';

if( isset($_POST['Copy']) && isset($_POST['datasource']) ) {
	DsCopyApp::copyDataSource(intval($_POST['datasource']), $_POST['newname'], (bool)$_POST['copyqueries'], $error);
}

$datasourceList = '<select name="datasource">';
$datasources = BizAdminDatasource::queryDatasources();
foreach( $datasources as $datasource ) {
	$datasourceList .= '<option value="'.$datasource->ID.'"';
	if( isset($_REQUEST['datasource']) && $datasource->ID == intval($_REQUEST['datasource']) ) $datasourceList .= ' selected'; 
	$datasourceList .='>'.formvar($datasource->Name).'</option>';
}
$datasourceList .= '</select>';

$tpl = str_replace('<!--PAR:DATASOURCE_LIST-->',$datasourceList,$tpl);

$tpl_newname = isset( $_POST['newname'] ) ? $_POST['newname'] : '';
$tpl_copyqueries = isset( $_POST['copyqueries'] ) ? 'checked' : '';
$tpl = str_replace('<!--VAR:NEWNAME-->', formvar($tpl_newname),$tpl);
$tpl = str_replace('<!--VAR:COPYQUERIES-->',$tpl_copyqueries,$tpl);

$tpl = isset($_REQUEST['datasource']) ? str_replace('<!--PAR:PRESELECTED_DATASOURCE-->',intval($_REQUEST['datasource']),$tpl) : str_replace('<!--PAR:PRESELECTED_DATASOURCE-->','0',$tpl);

$tpl = str_replace('<!--PAR:ERROR-->',$error,$tpl);

print HtmlDocument::buildDocument($tpl,true,'');

// Admin web application helper
class DsCopyApp 
{
	public static function copyDataSource( $datasourceid, $newname, $copyqueries, &$error )
	{
		// check the newname
		self::checkNewName( $newname, $error );
		
		if( !$error )
		{
			try{
				$new_datasource_id = BizAdminDatasource::copyDatasource( $datasourceid, $newname, $copyqueries );
			} catch( BizException $e ) {
				$error = $e->getDetail();
			}
		}else{
			return;
		}
		
		if( $new_datasource_id )
		{
			header("Location: datasources.php?dsSelect=".$new_datasource_id);
		}
	}
	
	private static function checkNewName( $newname, &$error )
	{
		if( trim($newname) == "")
		{
			$error = "The new name of the data source was empty."; // BZ#636
			return;
		}else{
			$datasourcerow = DBDatasource::getDatasourceByName( $newname );
			if( $datasourcerow[0] ) $error = "The new name of the data source already exists."; // BZ#636
		}
	}
}
?>