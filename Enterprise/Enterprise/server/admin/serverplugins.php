<?php

// Raise the max execution time to ensure that all the plugins have enough time to be configured / installed.
set_time_limit(3600);

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // inputvar() , formvar()
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';

$ticket = checkSecure('admin');
$tpl = HtmlDocument::loadTemplate( 'serverplugins.htm' );

BizSession::startSession($ticket);

// handle user request to enabling/disabling a certain plugin
$toggledPluginId = isset($_REQUEST['ToggledPluginId']) ? $_REQUEST['ToggledPluginId'] : null;
$registerPlugins = isset($_REQUEST['RegisterPlugins']) ? ($_REQUEST['RegisterPlugins'] == 'true') : false;

try {
	$pluginInfos = array(); // PluginInfoData
	$connInfos   = array(); // ConnectorInfoData
	$pluginErrs  = array(); // plugins (messages) that are in error
	
	$registerPlugins = true; // temporary forced to always register => TODO: Remove once this optimization is tested well.
	if( $registerPlugins ) {
		// Scan plugins at config- and server- folders and save changed data in DB
		$pluginObjs  = array(); // EnterprisePlugin
		$connObjs    = array(); // EnterpriseConnector
		BizServerPlugin::registerServerPlugins( $pluginObjs, $pluginInfos, $connObjs, $connInfos, $pluginErrs );
	} else {
		// Read plugins as registered in DB. (Ignore plugins stored in folders.)
		BizServerPlugin::readPluginInfosFromDB( $pluginInfos, $connInfos );
	}

	// Allow user to make changes too	
	if( $toggledPluginId ) {
		foreach( $pluginInfos as $pluginKey => $pluginInfo ) {
			if( !isset( $pluginErrs[$pluginKey] ) ) { // ignore toggle when plugin in error status
				if( $pluginInfo->Id == $toggledPluginId ) {
					try {
						if( $pluginInfo->IsActive ) {
							$pluginInfos[$pluginKey] = BizServerPlugin::deactivatePluginByName( $pluginInfo->UniqueName );
						} else {
							$pluginInfos[$pluginKey] = BizServerPlugin::activatePluginByName( $pluginInfo->UniqueName );
						}
					} catch( BizException $e ) {
						$pluginErrs[$pluginKey] = $e->getMessage()."\n".$e->getDetail();
					}
				}
			}
		}
	}
	
	if( $registerPlugins ) {
		// Specific case; Validate custom admin properties provided by AdminProperties connectors.
		// This is done here (instead of letting each connector checking itself at isInstalled) to
		// allow checking duplicate properties and doing checks all in the way (more robust and reliable).
		require_once BASEDIR.'/server/bizclasses/BizAdmProperty.class.php';
		BizAdmProperty::validateAndInstallCustomProperties( $pluginErrs );
	
		// Same for custom Object properties.
		require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
		BizProperty::validateAndInstallCustomProperties( null, $pluginErrs, true );
	}

} catch( BizException $e ) {
	echo '<font color="red">ERROR: '.$e->getMessage().'<br/>'.$e->getDetail().'<br/></font>';
	die();
}

// embed (hidden) all plugin records in html as listed rows
$tplRec = array();
$keysPattern = '/<!--PAR:PLUGIN_LISTED_RECORDSET>-->.*<!--<PAR:PLUGIN_LISTED_RECORDSET-->/is';
if( preg_match( $keysPattern, $tpl, $tplRec ) > 0 ) {
	$records = '';
	foreach( $pluginInfos as $pluginKey => $pluginInfo ) {
		$errMsg = isset($pluginErrs[$pluginKey]) ? $pluginErrs[$pluginKey] : '';
		$rec = ServerPluginAdminApp::pluginInfo2HTML( $tplRec[0], $pluginInfo, $errMsg );
		$records .= str_replace ('<!--PAR:PLUGIN_FIRSTCONNECTOR-->', ServerPluginAdminApp::getFirstConnectorId( $connInfos, $pluginKey ), $rec );
	}
	$tpl = preg_replace( $keysPattern, $records, $tpl );
}

// embed (hidden) all plugin records in html as property details sheets
$tplRec = array();
$keysPattern = '/<!--PAR:PLUGIN_DETAILS_RECORDSET>-->.*<!--<PAR:PLUGIN_DETAILS_RECORDSET-->/is';
if( preg_match( $keysPattern, $tpl, $tplRec ) > 0 ) {
	$records = '';
	foreach( $pluginInfos as $pluginKey => $pluginInfo ) {
		$errMsg = isset($pluginErrs[$pluginKey]) ? $pluginErrs[$pluginKey] : '';
		$rec = ServerPluginAdminApp::pluginInfo2HTML( $tplRec[0], $pluginInfo, $errMsg );
		$records .= str_replace ('<!--PAR:PLUGIN_FIRSTCONNECTOR-->', ServerPluginAdminApp::getFirstConnectorId( $connInfos, $pluginKey ), $rec );
	}
	$tpl = preg_replace( $keysPattern, $records, $tpl );
}

// embed (hidden) all connector records in html as listed rows
foreach( $pluginInfos as $pluginKey => $pluginInfo ) {
	$recSetKey = 'PAR:CONNECTOR_LISTED_RECORDSET#'.$pluginInfo->Id;
	$sttKeyStr = '<!--'.$recSetKey.'>-->';
	$endKeyStr = '<!--<'.$recSetKey.'-->';
	if( ($sttKeyPos = strpos( $tpl, $sttKeyStr )) !== false &&
		($endKeyPos = strpos( $tpl, $endKeyStr, $sttKeyPos )) !== false ) {
		$endKeyPos += strlen($endKeyStr);
		$tplRec = substr( $tpl, $sttKeyPos, $endKeyPos-$sttKeyPos );
		$records = '';
		if( isset($connInfos[$pluginKey])) foreach( $connInfos[$pluginKey] as $connKey => $connInfo ) {
			//$isService = in_array( 'ServiceConnector', class_parents($connObjs[$pluginKey][$connKey]));
			$isService = (substr( $connInfo->Type, -strlen('Service') ) == 'Service');
			$records .= ServerPluginAdminApp::connInfo2HTML( $tplRec, $connInfo, $isService );
		}
		$tpl = substr( $tpl, 0, $sttKeyPos ) . $records . substr( $tpl, $endKeyPos );
	}
	// Commented out much(!) slower solution: (faster solution is written above)
	//$tplRec = array();
	//$keysPattern = '<!--'.$recSetKey.'>-->.*<!--<'.$recSetKey.'-->';
	//if( eregi( $keysPattern, $tpl, $tplRec ) ) {
	//	...
	//}
	//$tpl = eregi_replace( $keysPattern, $records, $tpl );
}

// embed (hidden) all connector records in html as property details sheets
$sttKeyStr = '<!--PAR:CONNECTOR_DETAILS_RECORDSET>-->';
$endKeyStr = '<!--<PAR:CONNECTOR_DETAILS_RECORDSET-->';
if( ($sttKeyPos = strpos( $tpl, $sttKeyStr )) !== false &&
	($endKeyPos = strpos( $tpl, $endKeyStr, $sttKeyPos )) !== false ) {
	$endKeyPos += strlen($endKeyStr);
	$tplRec = substr( $tpl, $sttKeyPos, $endKeyPos-$sttKeyPos );
	$records = '';
	foreach( $pluginInfos as $pluginKey => $pluginInfo ) {
		if( isset($connInfos[$pluginKey]) ) foreach( $connInfos[$pluginKey] as $connKey => $connInfo ) {
			//$isService = in_array( 'ServiceConnector', class_parents($connObjs[$pluginKey][$connKey]));
			$isService = (substr( $connInfo->Type, -strlen('Service') ) == 'Service');
			$records .= ServerPluginAdminApp::connInfo2HTML( $tplRec, $connInfo, $isService );
		}
	}
	$tpl = substr( $tpl, 0, $sttKeyPos ) . $records . substr( $tpl, $endKeyPos );
}
// Commented out much(!) slower solution: (faster solution is written above)
//$tplRec = array();
//$keysPattern = '<!--PAR:CONNECTOR_DETAILS_RECORDSET>-->.*<!--<PAR:CONNECTOR_DETAILS_RECORDSET-->';
//if( eregi( $keysPattern, $tpl, $tplRec ) ) {
//	...
//}
//$tpl = eregi_replace( $keysPattern, $records, $tpl );

// prefer taking last user selection of plugin and connector
$firstPluginId = isset($_REQUEST['LastSelectedPluginId']) ? intval($_REQUEST['LastSelectedPluginId']) : 0;
$firstConnId = $firstPluginId && isset($_REQUEST['LastSelectedConnId']) ? intval($_REQUEST['LastSelectedConnId']) : 0;
if( !$firstPluginId && !$firstConnId ) { // not selected?
	// pre-select first plugin and its first connector
	$firstPluginId = 0;
	$firstConnId = 0;
	foreach( $pluginInfos as $pluginKey => $pluginInfo ) {
		$firstPluginId = $pluginInfo->Id;
		foreach( $connInfos[$pluginKey] as $connInfo ) {
			$firstConnId = $connInfo->Id;
			break;
		}
		break;
	}
}
$tpl = str_replace ('<!--PAR:FIRST_PLUGIN-->',$firstPluginId, $tpl );
$tpl = str_replace ('<!--PAR:FIRST_CONNECTOR-->',$firstConnId, $tpl );

$tpl = str_replace ('<!--FRM:LastSelectedPluginId-->',$firstPluginId, $tpl );
$tpl = str_replace ('<!--FRM:LastSelectedConnId-->',$firstConnId, $tpl );

// output html
print HtmlDocument::buildDocument( $tpl, true, '' );

// Admin web application helper
class ServerPluginAdminApp 
{
	// Replaces all placeholders in html record with plugin info properties
	static public function pluginInfo2HTML( $rec, $pluginInfo, $errMsg )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		$rec = str_replace ('<!--PAR:PLUGIN_DISPLAYNAME-->',   formvar($pluginInfo->DisplayName), $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_VERSION-->',       formvar($pluginInfo->Version), $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_DESCRIPTION-->',   nl2br(formvar($pluginInfo->Description)), $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_COPYRIGHT-->',     nl2br(formvar($pluginInfo->Copyright)), $rec );
		if( !empty($errMsg) ) {
			$errMsg = nl2br($errMsg);
			$errMsg = str_replace( '/', ' / ', $errMsg ); // make sure path names used in error message get word wrapped at HTML
			$errMsg = '<!--RES:IDS_STATE_ERROR-->: '.$errMsg;
			$rec = str_replace ('<!--PAR:PLUGIN_ISACTIVE_ICON_SMALL-->', 'plugin_error_16.gif', $rec );
			$rec = str_replace ('<!--PAR:PLUGIN_ISACTIVE_ICON_BIG-->',   'plugin_error_24.gif', $rec );
			$isActiveTitle = '<!--RES:PLN_CLICKTOREPAIR-->';
		} else {
			$rec = str_replace ('<!--PAR:PLUGIN_ISACTIVE_ICON_SMALL-->', $pluginInfo->IsActive ? 'plugin_connect_16.gif' : 'plugin_disconnect_16.gif', $rec );
			$rec = str_replace ('<!--PAR:PLUGIN_ISACTIVE_ICON_BIG-->',   $pluginInfo->IsActive ? 'plugin_connect_24.gif' : 'plugin_disconnect_24.gif', $rec );
			$isActiveTitle = $pluginInfo->IsActive ? '<!--RES:PLN_CLICKTOPLUGOUT-->' : '<!--RES:PLN_CLICKTOPLUGIN-->';
		}		
		$rec = str_replace ('<!--PAR:PLUGIN_ISACTIVE_TITLE-->',$isActiveTitle, $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_ID-->',            $pluginInfo->Id, $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_NAME-->',          formvar($pluginInfo->UniqueName), $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_ISSYSTEM_ICON-->', $pluginInfo->IsSystem ? 'plugin_system_16.gif' : 'plugin_custom_16.gif', $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_ISSYSTEM_TITLE-->',$pluginInfo->IsSystem ? '<!--RES:PLN_SYSTEMPLUGIN-->' : '<!--RES:PLN_CUSTOMPLUGIN-->', $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_MODIFIED-->',      formvar(DateTimeFunctions::iso2date($pluginInfo->Modified)), $rec );
		$rec = str_replace ('<!--PAR:PLUGIN_ERRORMSG-->',      $errMsg, $rec );
		return $rec;
	}

	// Replaces all placeholders in html record with connector info properties
	static public function connInfo2HTML( $rec, $connInfo, $isService )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		$runModes = array( 'Before' => '<!--RES:PLN_RUNMODE_BEFORE-->', 'After' => '<!--RES:PLN_RUNMODE_AFTER-->', 'BeforeAfter' => '<!--RES:PLN_RUNMODE_BEFOREAFTER-->',
			'Overrule' => '<!--RES:PLN_RUNMODE_OVERRULE-->', 'Synchron' => '<!--RES:PLN_RUNMODE_SYNCHRON-->', 'Background' => '<!--RES:PLN_RUNMODE_BACKGROUND-->' );
		$rec = str_replace ('<!--PAR:CONN_CLASSNAME-->',formvar($connInfo->ClassName), $rec );
		$rec = str_replace ('<!--PAR:CONN_INTERFACE-->',formvar($connInfo->Interface), $rec );
		$rec = str_replace ('<!--PAR:CONN_RUNMODE-->',  $runModes[$connInfo->RunMode], $rec );
		$rec = str_replace ('<!--PAR:CONN_PRIO-->',     formvar($connInfo->Prio), $rec );
		$rec = str_replace ('<!--PAR:CONN_TYPE-->',     formvar($connInfo->Type), $rec );
		$rec = str_replace ('<!--PAR:CONN_MODIFIED-->', formvar(DateTimeFunctions::iso2date($connInfo->Modified)), $rec );
		$rec = str_replace ('<!--PAR:CONN_CLASSFILE-->',formvar(str_replace('/', ' / ', $connInfo->ClassFile)), $rec );
		$rec = str_replace ('<!--PAR:CONN_ID-->',       $connInfo->Id, $rec );
		if( $isService ) {
			$rec = str_replace ('<!--PAR:CONN_ICON_SMALL-->', 'plugin_serviceconn_16.gif', $rec );
			$rec = str_replace ('<!--PAR:CONN_ICON_BIG-->',   'plugin_serviceconn_24.gif', $rec );
			$rec = str_replace ('<!--PAR:CONN_CLASSTYPE-->',  '<!--RES:PLN_SERVICECONNECTOR-->', $rec );
			
		} else {
			$rec = str_replace ('<!--PAR:CONN_ICON_SMALL-->', 'plugin_defaultconn_16.gif', $rec );
			$rec = str_replace ('<!--PAR:CONN_ICON_BIG-->',   'plugin_defaultconn_24.gif', $rec );
			$rec = str_replace ('<!--PAR:CONN_CLASSTYPE-->',  '<!--RES:PLN_DEFAULTCONNECTOR-->', $rec );
		}
		return $rec;
	}
	
	// Looks up the first connector of the given plugin
	static public function getFirstConnectorId( $connInfos, $pluginKey )
	{
		$firstConnId = 0;
		if( isset($connInfos[$pluginKey]) ) foreach( $connInfos[$pluginKey] as $connInfo ) {
			$firstConnId = $connInfo->Id;
			break;
		}
		return $firstConnId;
	}
}