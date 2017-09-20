<?php

require_once dirname( __FILE__ ).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';

set_time_limit( 3600 );

try {
	$app = new DbAdmin_AdminApp();
	$app->run();
} catch( BizException $e ) {
	exit( $e->getMessage().' '.$e->getDetail() );
} catch( Throwable $e ) {
	exit( $e->getMessage() );
}
class DbAdmin_AdminApp
{
	/** @var WW_DbScripts_DbInstaller_Base Logics to install or update a core- or plugin database. */
	private $installer;

	/** @var PluginInfoData|null Server plugin, or NULL for core server. */
	private $pluginInfo;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		require_once BASEDIR.'/server/utils/HttpRequest.class.php';
		$requestParams = WW_Utils_HttpRequest::getHttpParams( 'PG' ); // take from POST or GET, but not from cookies
		$pluginName = isset($requestParams['plugin']) && !empty($requestParams['plugin']) ? $requestParams['plugin'] : null;

		$checkSystemAdmin = array( $this, 'checkSystemAdmin' ); // callback function
		if( $pluginName ) {
			require_once BASEDIR.'/server/dbscripts/dbinstaller/ServerPlugin.class.php';
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$this->installer = new WW_DbScripts_DbInstaller_ServerPlugin( $checkSystemAdmin, $pluginName );
			$this->pluginInfo = BizServerPlugin::getInstalledPluginInfo( $pluginName );
		} else {
			require_once BASEDIR.'/server/dbscripts/dbinstaller/CoreServer.class.php';
			$this->installer = new WW_DbScripts_DbInstaller_CoreServer( $checkSystemAdmin );
		}
	}
	
	/**
	 * Validates whether or not the system user is logged in. If not, the current page
	 * gets redirected to the login page. This might happen when the dbadmin.php page
	 * is accessed again, -after- the DB was already installed or upgraded.
	 */
	public function checkSystemAdmin()
	{
		checkSecure( 'admin' );
	}
	
	/**
	 * Runs the next phase in the installation process and prints the results to HTML page.
	 */
	public function run()
	{
		// Run the installer.
		$phase = isset( $_POST['action'] ) ? $_POST['action'] : null;
		$this->installer->run( $phase );
		
		// Redirect to the license page.
		if( $phase == 'goto_licenses' ) {
			header( 'Location:license/index.php' );
			exit();
		}
		
		// Build the HTML page.
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$tpl = HtmlDocument::loadTemplate( 'dbadmin.htm' );
		$tpl = $this->showTitles( $tpl );
		$tpl = $this->showConfiguration( $tpl );
		$tpl = $this->showVersionInfo( $tpl );
		$tpl = $this->showSqlScripts( $tpl );
		$tpl = $this->showButtonBar( $tpl );
		$tpl = $this->showMessages( $tpl );
		$tpl = $this->roundtripHttpGetParamsAndFormPostParams( $tpl );
		print HtmlDocument::buildDocument( $tpl, true, null, true, false, false );
	}

	/**
	 * Update titles in the HTML page to suite either the core server context or the plugin context.
	 *
	 * @param string $tpl HTML template to be filled in.
	 * @return string HTML template enriched with the messages.
	 */
	private function showTitles( $tpl )
	{
		if( $this->pluginInfo ) {
			$title1 = '<h1>'.BizResources::localize('PLN_SERVERPLUGIN' ).': '.formvar( $this->pluginInfo->DisplayName ).'</h1>';
		} else {
			$title1 = '';
		}
		$tpl = str_replace("<!--PAR:PLUGIN_TITLE-->", $title1, $tpl );

		if( $this->pluginInfo ) {
			$title2 = BizResources::localize('DBINSTALLER_DBMODELINFO_PLUGIN' );
		} else {
			$title2 = BizResources::localize('DBINSTALLER_DBMODELINFO' );
		}
		$tpl = str_replace( '<!--PAR:DBINSTALLER_DBMODELINFO-->', $title2, $tpl );

		return $tpl;
	}

	/**
	 * Displays the Db configuration at the HTML page.
	 *
	 * @param string $tpl HTML template to be filled in.
	 * @return string HTML template enriched with the config info.
	 */
	private function showConfiguration( $tpl )
	{
		$dbConfiguration = $this->installer->getDbConfiguration();
		
		$tpl_rec = array();
		$keysPattern = '/<!--PAR:CONFIG_RECORDSET>-->.*<!--<PAR:CONFIG_RECORDSET>-->/is';
		if( preg_match( $keysPattern, $tpl, $tpl_rec ) > 0 ) {
			$tpl_rec = str_replace( '<!--PAR:CONFIG_RECORDSET>-->', '', $tpl_rec ); // remove prefix
			$tpl_rec = str_replace( '<!--<PAR:CONFIG_RECORDSET>-->', '', $tpl_rec ); // remove postfix
			$records = '';
			foreach( $dbConfiguration as $confKey => $confValue ) {
				$records .= $this->confInfo2HTML( $tpl_rec[0], $confKey, $confValue );
			}
			$tpl = preg_replace( $keysPattern, $records, $tpl );
		}
		return $tpl;
	}

	/**
	 * Displays the Db version at the HTML page.
	 *
	 * @param string $tpl HTML template to be filled in.
	 * @return string HTML template enriched with the version info.
	 */
	private function showVersionInfo( $tpl )
	{
		$installedVersion = $this->installer->getInstalledDbVersion();
		$versions = $this->installer->getDbVersions( $installedVersion );

		if( $this->pluginInfo ) {
			$plugin = BizResources::localize( 'PLN_SERVERPLUGIN' ).': '.formvar( $this->pluginInfo->DisplayName );
			$versions = array_merge( array( $plugin => formvar( $this->pluginInfo->Version ) ), $versions );
		}

		$tpl_rec = array();
		$keysPattern = '/<!--PAR:VERSION_RECORDSET>-->.*<!--<PAR:VERSION_RECORDSET>-->/is';
		if( preg_match( $keysPattern, $tpl, $tpl_rec ) > 0 ) {
			$tpl_rec = str_replace( '<!--PAR:VERSION_RECORDSET>-->', '', $tpl_rec ); // remove prefix
			$tpl_rec = str_replace( '<!--<PAR:VERSION_RECORDSET>-->', '', $tpl_rec ); // remove postfix
			$records = '';
			foreach( $versions as $confKey => $confValue ) {
				$records .= $this->confInfo2HTML( $tpl_rec[0], $confKey, $confValue );
			}
			$tpl = preg_replace( $keysPattern, $records, $tpl );
		}
		return $tpl;
	}

	/**
	 * Displays the SQL scripts (to run) at the HTML page.
	 *
	 * @param string $tpl HTML template to be filled in.
	 * @return string HTML template enriched with the SQL info.
	 */
	private function showSqlScripts( $tpl )
	{
		$sqlScripts = $this->installer->getAllSqlScripts();
		
		if( count( $sqlScripts ) == 0 ) {
			require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';
			$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'SCRIPTS_TOBE_EXECUTED' );
			$tpl = $sectionObj->replaceSection( $tpl, '' );
		} else {
			$tpl_rec = array();
			$keysPattern = '/<!--PAR:FILES_RECORDSET>-->.*<!--<PAR:FILES_RECORDSET>-->/is';
			if( preg_match( $keysPattern, $tpl, $tpl_rec ) > 0 ) {
				$tpl_rec = str_replace( '<!--PAR:FILES_RECORDSET>-->', '', $tpl_rec ); // remove prefix
				$tpl_rec = str_replace( '<!--<PAR:FILES_RECORDSET>-->', '', $tpl_rec ); // remove postfix
				$records = '';
				foreach( $sqlScripts as $arrayNumber => $sqlScript ) {
					$scriptNumber = $arrayNumber + 1; // Index starts at 0
					$records .= $this->scriptsInfo2HTML( $tpl_rec[0], $scriptNumber.')', $sqlScript );
				}
				$tpl = preg_replace( $keysPattern, $records, $tpl );
			}
		}
		return $tpl;
	}

	/**
	 * Draws a button bar on the HTML page with a START button and a NEXT button.
	 * The buttons are only visible when determined that the operation makes sense:
	 *
	 * START button:
	 * Shown when the DB needs to be installed or updated.
	 *
	 * NEXT button:
	 * When a new DB has been installed or when a major* DB update was done, the product 
	 * licenses will need to be installed/updated as well. Reason is that product keys
	 * differ per major version. In that case there will be a Next button shown on the 
	 * HTML page that navigates the user to the License admin pages.
	 * [*] Major update means e.g. 8.2 => 9.0 (but not 8.0 => 8.2)
	 * 
	 * @param string $tpl HTML document (template to be 'filled in').
	 * @return string The HTML template, now updated with a button bar.
	 */
	private function showButtonBar( $tpl )
	{
		// Build the button bar.
		$buttonBar = '';
		$nextPhases = $this->installer->getNextPhases();
		if( $nextPhases ) foreach( $nextPhases as $nextPhaseId => $nextPhaseText ) {
			$actionButton =
				'<button type="submit" value="'.$nextPhaseId.'" name="action">'.
					$nextPhaseText.
				'</button>';
			$buttonBar .= $actionButton.'&nbsp;';
		}
		
		// Draw the button bar.
		if( $buttonBar ) {
			$tpl = str_replace( '<!--PAR:BUTTONBAR-->', $buttonBar, $tpl );
		} else {
			// When no buttons, leave out the whole button bar section.
			require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';
			$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'NEXTSTEP' );
			$tpl = $sectionObj->replaceSection( $tpl, '' );
		}
		return $tpl;
	}

	/**
	 * Displays errors/warnings/messages raised by the installer at the HTML page.
	 *
	 * @param string $tpl HTML template to be filled in.
	 * @return string HTML template enriched with the messages.
	 */
	private function showMessages( $tpl )
	{
		$reportItems = $this->installer->getReport()->get();
		$sqlReportItems = $this->installer->getSqlReport()->get();
		if( count($reportItems) || count($sqlReportItems) > 0 ) {
			$messages = '';
			$separator = '';
			foreach( $reportItems as $reportItem ) {
				switch( $reportItem->severity ) {
					case 'FATAL':
					case 'ERROR':
						$title = BizResources::localize( 'ERR_GENERAL_ERROR' ).': ';
						$color = '#ff0000'; // red
					break;
					case 'WARN':
						$title = BizResources::localize( 'WARNING' ).': ';
						$color = '#000f90'; // orange
					break;
					case 'INFO':
					default:
						$title = '';
						$color = '#0000ff'; // blue
					break;
				}
				$messages .= $separator.'<b><font color="'.$color.'">'.
					nl2br($title.$reportItem->message).'</font></b>';
				if( $reportItem->detail ) {
					$messages .= '<br/><b><font color="'.$color.'">'.
						nl2br($reportItem->detail).'</font></b>';
				}
				if( $reportItem->help ) {
					$messages .= '<br/><b>Tip:</b> '.nl2br($reportItem->help);
				}
				$separator = '<br/><br/>'; // for next message
			}
			if( $sqlReportItems ) {
				$showTableMsg = BizResources::localize( 'DBINSTALLER_CLICKTOSHOWTABLES' );
				$messages .= $separator.'<b><font color="#0000ff"><a href="javascript:showTables();">'.$showTableMsg.'</a></font></b>';
				// Print overview of the created/upgraded tables
				$tpl = str_replace( '<!--PAR:TABLEOVERVIEW-->', $this->tableOverview(), $tpl );
			}
			$tpl = str_replace( '<!--PAR:CONFMESSAGE-->', $messages, $tpl );
		} else {
			require_once BASEDIR.'/server/utils/htmlclasses/TemplateSection.php';
			$sectionObj = new WW_Utils_HtmlClasses_TemplateSection( 'MESSAGES' );
			$tpl = $sectionObj->replaceSection( $tpl, '' );
		}
		return $tpl;
	}

	/**
	 * Injects HTTP GET params and HTML form post data let them round-trip through the next form post.
	 *
	 * @param string $tpl HTML template to be filled in.
	 * @return string HTML template enriched with the messages.
	 */
	private function roundtripHttpGetParamsAndFormPostParams( $tpl )
	{
		$param = "<input type='hidden' value='".formvar($this->pluginName)."' name='plugin'/>";
		return str_replace("<!--PAR:HIDDEN-->", $param, $tpl );
	}
	
	/**
	 * Takes a HTML template section and fills in a key-value of a setting.
	 *
	 * @param string $one_rec HTML template section (record) to fill in.
	 * @param string $confKey Key value.
	 * @param string  $confValue Data value.
	 * @return string The updated HTML section, with details filled in.
	 */
	private function confInfo2HTML( $one_rec, $confKey, $confValue )
	{
		$one_rec = str_replace( '<!--PAR:SETTING-->', $confKey, $one_rec );
		$one_rec = str_replace( '<!--PAR:VALUE-->', $confValue, $one_rec );
		return $one_rec;
	}
	
	/**
	 * Takes a HTML template section and fills in given details of a SQL script.
	 *
	 * @param string $one_rec HTML template section (record) to fill in.
	 * @param string $confKey SQL script id.
	 * @param string  $confValue SQL script file path.
	 * @return string The updated HTML section, with details filled in.
	 */
	private function scriptsInfo2HTML( $one_rec, $confKey, $confValue )
	{
		$one_rec = str_replace( '<!--PAR:NUMBER-->', $confKey, $one_rec );
		$one_rec = str_replace( '<!--PAR:FILEPATH-->', $confValue, $one_rec );
		return $one_rec;
	}

	/**
	 * Returns the installed DB tables in HTML format to show at the HTML page.
	 *
	 * @return string DB table info in HTML format.
	 */
	private function tableOverview()
	{
		require_once BASEDIR.'/server/dbmodel/Factory.class.php';
		require_once BASEDIR.'/server/dbmodel/Reader.class.php';

		// Retrieve tables names from the database model
		if( $this->pluginName ) {
			$definitions = array( WW_DbModel_Factory::createModelForServerPlugin( $this->pluginName ) );
		} else {
			$definitions = WW_DbModel_Factory::createModels();
		}
		foreach( $definitions as $definition ) {
			$reader = new WW_DbModel_Reader( $definition );
			$tables = $reader->listTables();
			$dbTables = array();
			foreach( $tables as $table ) {
				$dbTables[ $table['name'] ] = true;
			}
		}
		ksort($dbTables);

		// Show record counts for each table
		$txt = '<br/>'.BizResources::localize('DBINSTALLER_DBCONTAINSTABLES', true, array(count($tables))).'<br/>';
		$txt .= '<table border="0"><tr>'
			.'<th>'.BizResources::localize( 'OBJ_TABLE' ).'</th>'
			.'<th>'.BizResources::localize( 'OBJ_RECORDS' ).'</th>'
			.'<th> </th>'
			.'<th>'.BizResources::localize( 'OBJ_TABLE' ).'</th>'
			.'<th>'.BizResources::localize( 'OBJ_RECORDS' ).'</th>'
			.'</tr>';
		$first = true;
		$dbh = DBDriverFactory::gen();
		foreach( array_keys($dbTables) as $tableName ) {
			$sql = "select count( 1 ) as `c` from $tableName";
			$sth = $dbh->query( $sql );
			$row = $dbh->fetch( $sth );
			if( $row ) {
				$recs = $row['c'];
				if( $first ) {
					$txt .= "<tr><td>$tableName</td><td>$recs</td><td></td>";
				} else {
					$txt .= "<td>$tableName</td><td>$recs</td></tr>";
				}
				$first = !$first;
			} else {
				$txt .= "<td>$tableName</td><td>-</td></tr>";
			}
		}
		if( !$first ) $txt .= '</tr>';
		$txt .= '</table>';
		return $txt;
	}
}
