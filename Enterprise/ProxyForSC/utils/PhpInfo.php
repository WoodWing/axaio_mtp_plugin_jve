<?php
/**
 * Helper class to display defines made in PHP config files.
 *
 * It has plenty helper functions all returning HTML fragments that can be combined to
 * build a HTML info page. See its getAllInfo() function that builds a full page.
 * 
 * @package    ProxyForSC
 * @subpackage Utils
 * @since      v1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_PhpInfo
{
	/**
	 * Builds a full HTML page with all PHP config info on it. This includes options set
	 * at Enterprise config files, and all info from the currently running PHP instance.
	 *
	 * @return string HTML page with requested info.
	 */
	public static function getAllInfo()
	{
		$info = self::getPageHeader();
		
		// Warn when server is running in DEBUG mode
		if( LogHandler::debugMode() ) {
			$info .= '<center><font size="+1" color="#ff0000"><b>Running in DEBUG mode</b></font></center><br/>';
		}
		
		// Read config file and build a HTML doc with the configured options.
		$info .= self::getChapterHeader( PRODUCT_NAME_FULL.' v'.PRODUCT_VERSION, 'Configuration files' );
		$info .= self::getConfigFile( BASEDIR.'/'.PRODUCT_NAME_SHORT.'/config.php', PRODUCT_NAME_SHORT.'/config.php' );
		$info .= self::getChapterFooter();
		$info .= self::getPhpInfo();
		$info .= self::getPageFooter();
		return $info;
	}

	/**
	 * Returns all info from the currently running PHP instance.
	 *
	 * @return string HTML fragment with requested info.
	 */
	private static function getPhpInfo()
	{
		// Get phpinfo (without HTML headers since we already have!)
		ob_start();
		phpinfo();
		$phpInfo = ob_get_contents();
		ob_end_clean();
		$phpInfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpInfo);
		return $phpInfo;
	}
	
	/**
	 * Returns all names and values of the defines made in a config files (by parsing it manually).
	 * It also includes the file and asks PHP for actual values of those defines.
	 * @return string HTML fragment with requested info.
	 */
	private static function getConfigFile( $filePath, $displayName )
	{
		require_once $filePath;
		$info = self::getSectionHeader( $displayName );

		require_once BASEDIR.'/utils/PhpConfigParser.class.php';
		$parser = new PhpConfigParser( $filePath );
		$defValues = $parser->ParseDefineValues();
		for( $i = 0; $i < sizeof($defValues); $i++ ) {
			$defValue = $defValues[$i];
			$defValShow = empty($defValue[1]) ? '<i>'.$defValue[2].'</i>' : $defValue[1];

			// Hide the password value, replace with asterisks
			if ( $defValue[0] === 'SSH_STUB_PASSWORD' || $defValue[0] === 'SSH_PROXY_PASSWORD' ) {
				$defValShow = preg_replace('/.*/i', '***', $defValShow, 1);
			}

			$showVal = $defValShow;
			// Show array values in same cell
			while( ($i+1 < sizeof($defValues)) && empty( $defValues[$i+1][0] ) ) {
				$i++;
				$showVal .= '<br/>';
				$showVal .= empty($defValues[$i][1]) ? '<i>'.$defValues[$i][2].'</i>' : $defValues[$i][1];
			}
			$info .= self::getSetting( $defValue[0], $showVal );
		}
		$info .= self::getSectionFooter();
		return $info;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Display helper functions...

	private static function getPageHeader()
	{
		return '<html><head><style type="text/css"><!--
			body {background-color: #ffffff; color: #000000;}
			body, td, th, h1, h2 {font-family: sans-serif;}
			pre {margin: 0px; font-family: monospace;}
			a:link {color: #000099; text-decoration: none; background-color: #ffffff;}
			a:hover {text-decoration: underline;}
			table {border-collapse: collapse;}
			.center {text-align: center;}
			.center table { margin-left: auto; margin-right: auto; text-align: left;}
			.center th { text-align: center !important; }
			td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
			h1 {font-size: 150%;}
			h2 {font-size: 125%;}
			.p {text-align: left;}
			.e {background-color: #ccccff; font-weight: bold; color: #000000;}
			.h {background-color: #9999cc; font-weight: bold; color: #000000;}
			.v {background-color: #cccccc; color: #000000;}
			.n {border: 0;}
			i {color: #666666; background-color: #cccccc;}
			img {float: right; border: 0px;}
			hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
			//--></style>
			<title>Enterprise Server Info</title></head>
			<body><div class="center">';
	}

	private static function getPageFooter()
	{
		return 
			'<table width="600" cellpadding="3" border="0"><tr class="v"><td colspan="2">'.
				'<br/>Copyright (c) 2000-'.date('Y').' WoodWing Software BV<br/><br/></td></tr>'.
			'</table><br/><br/><br/>'.
		'</div></body></html>';
	}

	private static function getChapterHeader( $title, $subtitle )
	{
		return
			'<table border="0" cellpadding="3" width="600">'.
				'<tr class="h"><td colspan="2">'.
					'<a href="http://www.woodwing.com/" target="_blank"><img border="0" src="wwlogo.gif" alt="WoodWing Logo" /></a>'.
					'<h1 class="p">'.$title.'<br/><br/>'.$subtitle.'</h1>'.
				'</td></tr>'.
				'<tr><td class="n">&nbsp;</td></tr>'; // empty row
	}

	private static function getChapterFooter()
	{
		return 
			'</table>'.
			'<tr><td class="n">&nbsp;</td></tr>'; // empty row
	}

	private static function getSectionHeader( $title )
	{
		return '<tr><td class="h"><b>'.$title.'</b></td></tr>';
	}

	private static function getSectionFooter()
	{
		return '<tr><td class="n">&nbsp;</td></tr>'; // empty row
	}

	private static function getSetting( $key, $value )
	{
		return '<tr><td class="e">'.$key.'</td><td class="v">'.$value.'</td></tr>';
	}
}
