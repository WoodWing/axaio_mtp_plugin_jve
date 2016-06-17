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
$tpl = HtmlDocument::loadTemplate('dssettings.htm');

$error = '';

// is a datasource selected?
if( isset($_REQUEST["datasource"]) && intval($_REQUEST["datasource"]) > 0 )
{
	// get the datasource info
	$datasourceInfo = BizAdminDatasource::getDatasourceInfo( intval($_REQUEST["datasource"]) );
	
	// get settings (if any)
	$datasource = BizAdminDatasource::getDatasource( $datasourceInfo->ID );
	$settings = $datasource->Settings;
	
	// get settings UI from datasource
	$settingsUI = BizAdminDatasource::getSettingsDetails( $datasourceInfo->ID );
	
	if( isset($_REQUEST['ok']) )
	{
		foreach( $settingsUI as $settingUI )
		{
			$settingToSave = isset( $_REQUEST[$settingUI->Name] ) ? $_REQUEST[$settingUI->Name] : '';
			if( $settingUI->Type == "checkbox" ) {
				$settingToSave = ($settingToSave == 'on') ? '1' : '0';
			}
			DsSettingsAdminApp::saveSetting( $datasourceInfo->ID, $settingUI->Name, $settingToSave, $error );
		}
	}
	
}else{
	// otherwise die
	die('<font color="red">ERROR: Invalid Redirect<br/>You were not properly redirected to this page.<br/></font>'); // BZ#636
}

// parse the sub-title
$tpl = str_replace("<!--PAR:MODE_TITLE-->", formvar($datasourceInfo->Name) . " - <!--RES:DS_SETTINGS_TITLE-->",$tpl); // Settings

// parse the settings UI
$parsedUI = '';
if( is_array($settingsUI) && count($settingsUI) > 0)
{
	foreach( $settingsUI as $settingUI )
	{
		$prevalue = '';
		foreach( $settings as $setting )
		{
			if( $setting->Name == $settingUI->Name )
			{
				$prevalue = $setting->Value;
			}
		}
		$parsedUI .= DsSettingsAdminApp::buildSettingUI( $settingUI, $prevalue );
	}
}else{
	$parsedUI = '<i><!--RES:DS_NO_SETTINGS--></i>'; // There are no settings specified for this DataSource.
}
// put it in the template
$tpl = str_replace("<!--PAR:SETTINGSUI-->",$parsedUI,$tpl);

$tpl = str_replace("<!--PAR:BUT_OK-->","<!--RES:BUT_SAVE_DS-->",$tpl);
$tpl = str_replace("<!--PAR:BUT_CANCEL-->","<!--RES:BUT_RESET-->",$tpl);

$tpl = str_replace("<!--VAR:HIDDEN-->","<input type=\"hidden\" name=\"datasource\" value=\"$datasourceInfo->ID\">",$tpl);
$tpl = str_replace("<!--PAR:PRESELECTED_DATASOURCE-->",$datasourceInfo->ID,$tpl);

// parse errors
$tpl = str_replace("<!--PAR:ERROR-->",$error, $tpl);

print HtmlDocument::buildDocument($tpl,true,'');

// Admin web application helper
class DsSettingsAdminApp 
{
	static public function buildSettingUI( $settingUI, $prevalue )
	{
		// <tr><td>Type: </td><td><!--VAR:TYPE--><br/></td></tr>
		$return = '<tr><td>'.formvar($settingUI->Description).': </td><td>';
		$list = isset($settingUI->List) ? $settingUI->List : '';
		$size = isset($settingUI->Size) ? $settingUI->Size : '';
		$return .= self::parseInputElement( $settingUI->Type, $settingUI->Name, $prevalue, $list, $size );
		$return .= "<br/></td></tr>\n";
		return $return;
	}
	
	static private function parseInputElement( $type, $name, $prevalue, $list='', $size='' )
	{
		$inputElement = '';
		switch( $type )
		{
			case "text":
			case "password":
				$inputElement = "<input type=\"".formvar($type)."\" name=\"".formvar($name)."\" value=\"".formvar($prevalue)."\"";
				if( $size ) $inputElement .= " size=\"".intval($size)."\"";
				$inputElement .= ">";
			break;
			
			case "select":
				$inputElement .= "<select name=\"".formvar($name)."\">";
				// parse the options
				$options = explode("/",$list);
				foreach( $options as $option )
				{
					$inputElement .= "<option value=\"".formvar($option)."\"";
					if( $prevalue == $option ) $inputElement .= " selected";
					$inputElement .=">".formvar($option)."</option>";
				}
				$inputElement .= "</select>";
			break;
			
			case "checkbox":
				if( $prevalue == "1" )
				{
					 $prevalue = "checked";
				}else{
					$prevalue = "";
				}
				$inputElement = "<input type=\"checkbox\" name=\"".formvar($name)."\" $prevalue>";
		}
		return $inputElement;
	}
	
	static public function saveSetting( $datasourceid, $name, $value, &$error )
	{
		try {
			BizAdminDatasource::saveSetting( $datasourceid, $name, $value );
		} catch( BizException $e ) {
			$error = $e->getDetail();
		}
		
		header("Location: dssettings.php?datasource=".$datasourceid);
	}
}
?>