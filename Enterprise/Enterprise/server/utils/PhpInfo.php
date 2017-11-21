<?php
/**
 * Helper class to display defines made in PHP config files.
 *
 * It has plenty helper functions all returning HTML fragments that can be combined to
 * build a HTML info page. See its getAllInfo() function that builds a full page.
 * 
 * @package Enterprise
 * @subpackage Utils
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_PhpInfo
{
	/**
	 * Tries to connect to DB and checks if Enterprise tables are installed. 
	 * When fails, it does not log nor bails out, but reports  the error through 
	 * the given $info param.
	 *
	 * @param string $info HTML formatted message in case of error.
	 * @return WW_DbDrivers_DriverBase|null DB driver on success, else null.
	 */
	private static function connectToDb( &$info )
	{
		$map = new BizExceptionSeverityMap( array( 'S1003' => 'INFO' ) );
		try {
			$dbDriver = DBDriverFactory::gen();
		} catch( BizException $e ) {
			$msg = '<font color="red">ERROR: '.$e->getMessage().'</font>';
			$info .= self::getSetting( $msg, $e->getDetail() );
			$dbDriver = null;
		}
		if( $dbDriver ) {
			if( !$dbDriver->isConnected() ) {
				$msg = '<font color="red">ERROR: Could not connect to database</font>';
				$info .= self::getSetting( $msg, $dbDriver->error() );
				$dbDriver = null;
			} else if( !$dbDriver->tableExists( 'config' ) ) { // just pick smart_config to check
				$msg = '<font color="red">ERROR: Could not connect to database</font>';
				$info .= self::getSetting( $msg, 'No Enterprise tables installed.' );
				$dbDriver = null;
			}
		}
		return $dbDriver;
	}
	
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
		
		// Get Enterprise server config files
		$versionInfo = trim( SERVERVERSION . ' ' . SERVERVERSION_EXTRAINFO );
		$info .= self::getChapterHeader( 'Enterprise Server v'.$versionInfo, 'Configuration files' );
		$info .= self::getConfigFileInfo();
		$info .= self::getChapterFooter();
		
		// Get Enterprise server database details
		$info .= self::getChapterHeader( 'Enterprise Server', 'Storage' );
		$info .= self::getDatabaseInfo();
		$info .= self::getDatabasePopulation();
		$info .= self::getDatabaseIdentification();
		$info .= self::getChapterFooter();
		
		// Get Enterprise server plug-ins
		$info .= self::getChapterHeader( 'Enterprise Server', 'Server Plug-ins' );
		$info .= self::getServerPlugins();
		$info .= self::getChapterFooter();

		// Get Enterprise server plug-ins
		$info .= self::getChapterHeader( 'Enterprise Server', 'Runt-time PHP info' );
		$info .= self::getRuntimePhpInfo();
		$info .= self::getChapterFooter();

		$info .= self::getPhpInfo();
		
		$info .= self::getPageFooter();
		return $info;
	}

	/**
	 * Returns all info of the config files, including custom plugins that have the ConfigFiles interface implemented.
	 *
	 * @return string
	 */
	private static function getConfigFileInfo()
	{
		$dbDriver = self::connectToDb( $info );
		if( $dbDriver ) {
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connectors = BizServerPlugin::searchConnectors( 'ConfigFiles', null, false );
		} else {
			$connectors = array();
		}
		$info = '';
		$info .= self::getConfigFile( BASEDIR.'/config/config.php', 'config.php' );
		$info .= self::getConfigFile( BASEDIR.'/config/configserver.php', 'configserver.php' );
		$info .= self::getConfigFile( BASEDIR.'/config/config_overrule.php', 'config_overrule.php', $connectors );
		$info .= self::getConfigFile( BASEDIR.'/server/serverinfo.php', 'serverinfo.php' );
		if( $connectors ) foreach( $connectors as $connectorClass => $connector ) {
			$configFiles = BizServerPlugin::runConnector( $connector, 'getConfigFiles', array() );
			if( $configFiles ) foreach( $configFiles as $displayName => $configFile ) {
				$info .= self::getConfigFile( $configFile, $displayName, array( $connector ) );
			}
		}
		return $info;
	}

	/**
	 * Returns all info from the currently running PHP instance.
	 *
	 * @return string HTML fragment with requested info.
	 */
	public static function getPhpInfo()
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
	 *
	 * @param string $filePath Full file path of the config file.
	 * @param string $displayName Logical name of the config file e.g. base name of file or short path.
	 * @param ConfigFiles_EnterpriseConnector[] $connectors (since v10.1.1)
	 * @return string HTML fragment with requested info.
	 */
	public static function getConfigFile( $filePath, $displayName, $connectors = array() )
	{
		require_once $filePath;
		$info = self::getSectionHeader( $displayName );

		require_once BASEDIR.'/server/utils/PhpConfigParser.class.php';
		$parser = new PhpConfigParser( $filePath );
		$defValues = $parser->ParseDefineValues();
		for( $i = 0; $i < sizeof($defValues); $i++ ) {
			$defValue = $defValues[$i];
			$defValShow = empty($defValue[1]) ? '<i>'.$defValue[2].'</i>' : $defValue[1];

			// Hide the password value, replace with asterisks
			if ( $defValue[0] === 'DBPASS' || $defValue[0] === 'EMAIL_SMTP_PASS' || $defValue[0] === 'MTP_PASSWORD' ) {
				$defValShow = '***';
			}
			
			// Allow plugins to hide their passwords.
			if( $connectors ) foreach( $connectors as $connector ) {
				$defValShow = $connector->displayOptionValue( $displayName, $defValue[0], $defValShow );
			}

			$showVal = $defValShow;
			// Show array values in same cell
			while( ($i+1 < sizeof($defValues)) && empty( $defValues[$i+1][0] ) ) {
				$i++;
				$showVal .= '<br/>';

				$show = empty($defValues[$i][1]) ? '<i>'.$defValues[$i][2].'</i>' : $defValues[$i][1];

				// Check if TESTSUITE and replace the password with ***
				if( $defValue[0] == 'TESTSUITE' ) {
					$show = self::replacePasswordInPasswordKeyValueString( $show );
				}

				// Check if MESSAGE_QUEUE_CONNECTIONS and replace the password with ***
				if( $defValue[0] == 'MESSAGE_QUEUE_CONNECTIONS' ) {
					$show = self::replacePasswordInPasswordKeyValueString( $show );
				}

				// Allow plugins to hide their passwords.
				if( $connectors ) foreach( $connectors as $connector ) {
					$show = $connector->displayOptionValue( $displayName, $defValue[0], $show );
				}

				$showVal .= $show;
			}
			$info .= self::getSetting( $defValue[0], $showVal );
		}
		$info .= self::getSectionFooter();
		return $info;
	}

	private function replacePasswordInPasswordKeyValueString( $keyValueString )
	{
		return preg_replace('/Password => .*$/', 'Password => ***', $keyValueString);
	}

	
	/**
	 * Returns database client-, server- and connection information.
	 * @return string HTML fragment with requested info.
	 */
	public static function getDatabaseInfo()
	{
		$info = self::getSectionHeader( 'Database info' );

		$dbDriver = self::connectToDb( $info );
		if( $dbDriver ) {
			$clientInfo = $dbDriver->getClientServerInfo();
			foreach( $clientInfo as $key => $value ) {
				$info .= self::getSetting( $key, $value );
			}
		}
		
		$info .= self::getSectionFooter();
		return $info;
	}

	/**
	 * Returns table names and their record counts.
	 * @return string HTML fragment with requested info.
	 */
	public static function getDatabasePopulation()
	{
		$info = '';
		$dbDriver = self::connectToDb( $info );
		if( $dbDriver ) {
			require_once BASEDIR.'/server/dbscripts/dbmodel.php';
			$dbStruct = new DBStruct();
			$tables = $dbStruct->listTables();

			$info .= self::getSectionHeader( 'Database population ('.count($tables).' tables)' );

			// BZ#29418
			// Currently, there's no best solution for count(*) to speed up the performance.
			// The best way to tackle this is to use the index field (but not primary field)
			// to count instead of *.
			// This however, is only imply on the tables that most likely will have large amount
			// of data. These tables are defined in $bigTables.
			// It is a table_name => index_field array. Note that the index_field is not a primary field.
			$bigTables = array(
							'smart_objects' => 'publication', 
							'smart_deletedobjects' => 'publication', 
							'smart_objectversions' => 'objid',
							'smart_objectrelations' => 'parent', 
							'smart_pages' => 'objid',
							'smart_placements' => 'parent',
							'smart_elements' => 'objid', 
							'smart_targets' => 'objectid',
							'smart_targeteditions' => 'targetid' );
			$bigTablesNames = array_flip( array_keys( $bigTables ) );
			foreach ($tables as $table) {
			
				// When DB is about to get upgraded, new tables are still missing.
				// To avoid errors in server log, skip it and continue with next table.
				if( !$dbDriver->tableExists( substr( $table['name'], strlen(DBPREFIX) ) ) ) {
					continue; // skip
				}
				
				// Count the records on the table.
				if( array_key_exists( $table['name'], $bigTablesNames ) ) {
					// Use index field(not primary field) instead of count(*).
					$sql = 'select count('.$bigTables[ $table['name'] ].') as `c` from ' . $table['name'];
				} else {
					$sql = 'select count(*) as `c` from '.$table['name'];
				}
				$sth = $dbDriver->query($sql,null,null); // no log to avoid recursion
				$row = $dbDriver->fetch($sth);
				$dbn = trim($table['name'], "`[]\"'");
				$recs = $row ? $row['c'] : '-';
				
				// Add the record count to the HTML overview.
				$info .= self::getSetting( $dbn, $recs );
			}
			$info .= self::getSectionFooter();
		}
		return $info;
	}

	/**
	 * Returns the Enterprise System ID and DB model version info.
	 *
	 * @return string HTML fragment with requested info.
	 */
	public static function getDatabaseIdentification()
	{
		$info = '';
		$dbDriver = self::connectToDb( $info );
		if( $dbDriver ) {
			require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
			$info .= self::getSectionHeader( 'Database references' );
			$info .= self::getSetting( 'Enterprise System ID', BizSession::getEnterpriseSystemId() );
			$info .= self::getSetting( 'Installed DB model version', DBConfig::getSCEVersion() );
			$info .= self::getSetting( 'Required DB model version', SCENT_DBVERSION );
			$info .= self::getSectionFooter();
		}
		return $info;
	}

	/**
	 * Returns all Server Plug-ins as known/installed at DB (not at plugins folders!)
	 * @return string HTML fragment with requested info.
	 */
	public static function getServerPlugins()
	{
		$info = '';
		$dbDriver = self::connectToDb( $info );
		if( $dbDriver ) {
			require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
			$plugins = DBServerPlugin::getPlugins();
			if( $plugins ) foreach( $plugins as $plugin ) {
				$info .= self::getSectionHeader( $plugin->DisplayName );
				$info .= self::getSetting( 'Version', 'v'.$plugin->Version.' ('.$plugin->Modified.')' );
				$info .= self::getSetting( 'Active / System / Installed', ($plugin->IsActive ? 'yes' : 'no').
					' / '.($plugin->IsSystem ? 'yes' : 'no').' / '.($plugin->IsInstalled ? 'yes' : 'no') );
				$info .= self::getSectionFooter();
			}
		}
		return $info;
	}

	/**
	 * Returns some PHP information that is requested runtime.
	 *
	 * @return string HTML fragment with requested info.
	 */
	public static function getRuntimePhpInfo()
	{
		$info = '';
		$info .= self::getSetting( 'PHP executable (PHP_BINDIR)', PHP_BINDIR );
		$info .= self::getSetting( 'System temp folder (sys_get_temp_dir())', sys_get_temp_dir() );
		return $info;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// Display helper functions...

	public static function getPageHeader()
	{
		/** @noinspection CssRedundantUnit */
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

	public static function getPageFooter()
	{
		return 
			'<table width="600" cellpadding="3" border="0"><tr class="v"><td colspan="2">'.
				'<br/>Copyright (c) 2000-'.date('Y').' WoodWing Software BV<br/><br/></td></tr>'.
			'</table><br/><br/><br/>'.
		'</div></body></html>';
	}

	public static function getChapterHeader( $title, $subtitle )
	{
		return
			'<table border="0" cellpadding="3" width="600">'.
				'<tr class="h"><td colspan="2">'.
					'<a href="http://www.woodwing.com/" target="_blank"><img border="0" src="wwlogo.gif" alt="WoodWing Logo" /></a>'.
					'<h1 class="p">'.$title.'<br/><br/>'.$subtitle.'</h1>'.
				'</td></tr>'.
				'<tr><td class="n">&nbsp;</td></tr>'; // empty row
	}

	public static function getChapterFooter()
	{
		return 
			'</table>'.
			'<tr><td class="n">&nbsp;</td></tr>'; // empty row
	}

	public static function getSectionHeader( $title )
	{
		return '<tr><td class="h"><b>'.$title.'</b></td></tr>';
	}

	public static function getSectionFooter()
	{
		return '<tr><td class="n">&nbsp;</td></tr>'; // empty row
	}

	public static function getSetting( $key, $value )
	{
		return '<tr><td class="e">'.$key.'</td><td class="v">'.$value.'</td></tr>';
	}
}
