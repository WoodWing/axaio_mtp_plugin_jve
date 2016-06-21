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
		$header .= '	<tr><td class="h">Base folder:</td><td class="d" colspan="5">'.BASEDIR.'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">Location:</td><td class="d" colspan="3">'.OUTPUTDIRECTORY.'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">Processor:</td><td class="d" colspan="3">'.(isset($_SERVER['PROCESSOR_IDENTIFIER'])?$_SERVER['PROCESSOR_IDENTIFIER']:'').'</td>';
		$header .= '		<td class="h">Count:</td><td class="d">'.(isset($_SERVER['NUMBER_OF_PROCESSORS'])?$_SERVER['NUMBER_OF_PROCESSORS']:'').'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">PHP:</td><td class="d">'.phpversion().'</td>';
		$header .= '		<td class="h">HTTP:</td><td class="d" colspan="3">'.(isset($_SERVER['SERVER_SOFTWARE'])?$_SERVER['SERVER_SOFTWARE']:'').', '.(isset($_SERVER['GATEWAY_INTERFACE'])?$_SERVER['GATEWAY_INTERFACE']:'').'</td></tr>'.PHP_EOL;
		$header .= '	<tr><td class="h">OS:</td><td class="d" colspan="3">'.php_uname().'</td>';
		$header .= '		<td class="h">SAPI:</td><td class="d">'.php_sapi_name().'</td></tr>'.PHP_EOL;
		$header .= '</table><br/>';

		return $header;
	}
}
