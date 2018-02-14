<?php

class HtmlLog
{
	public static function getHead( $title )
	{
		$header = '<head>';
		$header .= '	<style type="text/css">';
		$header .= '		table {border-collapse: collapse;}';
		$header .= '		body, td, th, h1, h2 {font-family: sans-serif;}';
		$header .= '		th { border: 1px solid #000000; vertical-align: baseline; font-weight: bold; background-color: #ffaa00; color: #ffffff; }';
		$header .= '		td { border: 1px solid #000000; vertical-align: baseline; }';
		$header .= '		.d {background-color: #eeeeee; color: #000000; }'; // default
		$header .= '		.h {background-color: #dddddd; font-weight: bold; color: #000000;}'; // highlighted
		$header .= '	</style>';
		$header .= '	<meta http-equiv="content-type" content="text/html;charset=utf-8" />';
		$header .= '	<title>'.$title.'</title>';
		$header .= '</head>'.PHP_EOL;
		return $header;
	}
	
	public static function getHeader( $product, $title )
	{
		// css stylesheet
		$header = '<html>'.self::getHead( $title ); 
		$header .= '<body><h1>'.$product.' - '.$title.'</h1>'.PHP_EOL;

		// server properties
		$header .= '<h2>System configuration</h2><table>'.PHP_EOL;
		$header .= '	<tr><th>Property</th><th colspan="5">Value</th></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">SCE Server:</td><td class="d">'.SERVERVERSION.'</td>';
		$header .= '		<td class="h">URL:</td><td class="d" colspan="3">'.SERVERURL.'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">Base folder:</td><td class="d" colspan="5">'.BASEDIR.'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">DB Type:</td><td class="d">'.DBTYPE.'</td>';
		$header .= '		<td class="h">DB Server:</td><td class="d">'.DBSERVER.'</td>';
		$header .= '		<td class="h">DB Name:</td><td class="d">'.DBSELECT.'</td></tr>'.PHP_EOL;
		$header .= '		<td class="h">Location:</td><td class="d" colspan="3">'.ATTACHMENTDIRECTORY.'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">Max query:</td><td class="d">'.DBMAXQUERY.'</td>';
		$header .= '		<td class="h">Use geo save:</td><td class="d">'.UPDATE_GEOM_SAVE.'</td>';
		$header .= '		<td class="h">Personal status:</td><td class="d">'.PERSONAL_STATE.'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">Debug level:</td><td class="d">'.LogHandler::getDebugLevel().'</td>';
		$header .= '		<td class="h">Location:</td><td class="d" colspan="3">'.OUTPUTDIRECTORY.'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">Processor:</td><td class="d" colspan="3">'.(isset($_SERVER['PROCESSOR_IDENTIFIER'])?$_SERVER['PROCESSOR_IDENTIFIER']:'').'</td>';
		$header .= '		<td class="h">Count:</td><td class="d">'.(isset($_SERVER['NUMBER_OF_PROCESSORS'])?$_SERVER['NUMBER_OF_PROCESSORS']:'').'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">PHP:</td><td class="d">'.phpversion().'</td>';
		$header .= '		<td class="h">HTTP:</td><td class="d" colspan="3">'.(isset($_SERVER['SERVER_SOFTWARE'])?$_SERVER['SERVER_SOFTWARE']:'').', '.(isset($_SERVER['GATEWAY_INTERFACE'])?$_SERVER['GATEWAY_INTERFACE']:'').'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">OS:</td><td class="d" colspan="3">'.php_uname().'</td>';
		$header .= '		<td class="h">SAPI:</td><td class="d">'.php_sapi_name().'</td></tr>'.PHP_EOL;
		$header .= '</table><br/>';

		// server features
		$FeatureSet = unserialize(SERVERFEATURES);
		$header .= '<h2>Feature Set</h2><table>'.PHP_EOL;
		$header .= '	<tr><th>Setting</th><th>Value</th></tr>'.PHP_EOL;
		foreach( $FeatureSet as &$feature ) {
			$header .= '<tr><td class="h">'.$feature->Key.'</td><td class="d">'.$feature->Value.'</td></tr>'.PHP_EOL;
		}
		$header .= '</table><br/>'.PHP_EOL;


		// IMPORTANT: Commented out below; we can NOT access DB from here or else we end up in MAJOR troubles: BZ#8483
		
		// db population
		/*require_once BASEDIR.'/server/dbmodel/Definition.class.php';
		$dbStruct = new WW_DbModel_Definition();
		$tables = $dbStruct->listTables();
		$header. = '<h2>Database Population</h2><table><tr><th>Table</th><th>Records</th><th>Table</th><th>Records</th></tr>';
		$dbh = DBDriverFactory::gen();
		$first = true;
		foreach ($tables as $table) {
			$sql = 'select count(*) as `c` from '.$table['name'];
			$sth = $dbh->query($sql,null,false); // no log to avoid recursion
			$row = $dbh->fetch($sth);
			$dbn = trim($table['name'], "`[]\"'");
			$recs = $row ? $row['c'] : '-';
			if ($first) $header .= '<tr>';
			$header .= '<td class="h">'.$dbn.'</td><td class="d">'.$recs.'</td>';
			if (!$first) $header .= '</tr>';
			$first = !$first;
		}
		if (!$first) $header .= '</tr>';
		$header .= '</table><br/>';*/


		// IMPORTANT: Commented out below; we can NOT access DB from here or else we end up in MAJOR troubles: BZ#8483

		// Show all Server Plug-ins as known/installed at DB (not at plugins folders!)
		/*require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		$plugins = DBServerPlugin::getPlugins();
		$header .= '<h2>Server Plug-ins</h2><table>';
		$header .= '	<tr><th>Name</th><th>Version</th><th>Active</th><th>System</th><th>Installed</th><th>Modified</th></tr>';
		if( $plugins ) foreach( $plugins as $plugin ) {
			$header .= '<tr><td class="h">'.$plugin->DisplayName.'</td><td class="d">'.$plugin->Version.'</td>'.
						'<td class="d">'.($plugin->IsActive?'v':'-').'</td><td class="d">'.($plugin->IsSystem?'v':'-').'</td>'.
						'<td class="d">'.($plugin->IsInstalled?'v':'-').'</td><td class="d">'.$plugin->Modified.'</td></tr>';
		}
		$header .= '</table><br/>';*/

		return $header;
	}
	
}
