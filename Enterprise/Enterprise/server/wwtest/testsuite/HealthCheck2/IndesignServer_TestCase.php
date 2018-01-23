<?php
/**
 * InDesignServer TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_InDesignServer_TestCase extends TestCase
{
	public function getDisplayName() { return 'InDesign Server / CS Editor'; }
	public function getTestGoals()   { return 'Checks if the InDesign Server and the InDesign Server Jobs are correctly configured.'; }
	public function getTestMethods() { return 'Checks if all the priorities are covered by the configured InDesign servers'; }
    public function getPrio()        { return 11; }
	
	final public function runTest()
	{
    	$errmsg = ''; // no error

		// - - - - - - - - - - - - - - - IDS installed? - - - - - - - - - - - - - - - - - - - - 
		
		// BEFORE checking all kind of configuration options, check if any IDS is configured at all. (BZ#8380)
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		if( !DBInDesignServer::isInDesignServerInstalled() ) {
			$idsVersions = array_keys( unserialize( ADOBE_VERSIONS ) );
			$idsLastVersion = array_pop( $idsVersions );
			$idsVersionsCSV = implode( ', ', $idsVersions ).' or '.$idsLastVersion;
    		$this->setResult( 'NOTINSTALLED', 
    			'No active InDesign Server(s) '.$idsVersionsCSV.' configured in Enterprise Server. '.
    			'As a result, the Preview and Write-To-Fit features are disabled in the Content Station '.
    			'Multi-Channel Text Editor. ', 
    			'To enable this functionality, please install InDesign Server(s) and configure them '.
    			'on the <a href="../../server/admin/indesignservers.php">InDesign Servers</a> page. '.
    			'Run this test again to validate the configuration.' );
    		return;
		}

		// - - - - - - - - - - - - - - - CS Editor - - - - - - - - - - - - - - - - - - - - 

    	// Test WEBEDITDIR setting
    	$help = 'Please make sure you have correcty filled in the WEBEDITDIR setting in the configserver.php.<br/>';
    	$help .= 'This is the workfolder of the CS Editor which should exist on file sytem.<br/>';
		$help .= 'Make sure the Webserver user (Mac OSX: www, Windows: IUSR_&lt;servername&gt;, Linux: nobody) has write access to this folder.<br/>';
		if( empty($errmsg) && (!defined('WEBEDITDIR') || WEBEDITDIR == '') ) {
	    	$errmsg = 'CS Editor workspace (WEBEDITDIR) is not defined or not filled in.';
		}
		if( empty($errmsg) && strrpos(WEBEDITDIR,'/') != (strlen(WEBEDITDIR)-1) ) {
	    	$errmsg = 'Configured CS Editor workspace (WEBEDITDIR) has no slash (/) as last character.';
		}
    	if( empty($errmsg) && !is_dir(WEBEDITDIR) ) { // try to create when not present
    		if( mkdir( WEBEDITDIR ) ) { // tell user we have not just tested it, but actually created the folder!
	    		$infoMsg = 'This test has created the CS Editor workspace folder (WEBEDITDIR) for you at "'.WEBEDITDIR.'"';
	    		$this->setResult( 'INFO', $infoMsg, '' );
	    	}
    	}
    	if( empty($errmsg) && !is_dir(WEBEDITDIR) ) {
	    	$errmsg = 'Configured CS Editor workspace (WEBEDITDIR) could not be created.';
    	}
		if( !empty($errmsg) ) { 
    		$this->setResult( 'ERROR', $errmsg, $help );
    		return;
    	}
    	
		// Create subfolder in workspace (because CS Editor does the same!) and let JS try to create products.xml file in it.
		// This is done to check if both CS Editor (PHP) -and- ID Server (JS) have write access. 
		// Note that ID Server is a different process than PHP and might act for different users and so have different access profile!
		$help = 'CS Editor needs write access to create subfolders at its workspace:<br/><pre>&nbsp;&nbsp;&nbsp;'.WEBEDITDIR.'</pre>';
		$help .= 'Make sure the Webserver user (Mac OSX: www, Windows: IUSR_&lt;servername&gt;, Linux: nobody) has write access.<br/>';
		$help .= 'See also Admin Guide on how to install and configure CS Editor and InDesign Server.';

    	if( !is_dir(WEBEDITDIR.'wwtest') ) {
    		if( !mkdir( WEBEDITDIR.'wwtest' ) ) {
	    		$errmsg = 'Enterprise Server has no write access to the workspace.';
    			$this->setResult( 'ERROR', $errmsg, $help );
	    		return;
    		}
    		rmdir(WEBEDITDIR.'wwtest');
    	}

    	// Test WEBEDITDIRIDSERV setting
    	$help = 'Please make sure you have correcty filled in the WEBEDITDIRIDSERV setting in the configserver.php.<br/>';
    	$help .= 'This is the workfolder of the InDesign Server which should be mounted on the server.<br/>';
		$help .= 'Make sure it has write access to this folder.<br/>';
		if( empty($errmsg) && (!defined('WEBEDITDIRIDSERV') || WEBEDITDIRIDSERV == '') ) {
	    	$errmsg = 'InDesign Server workspace (WEBEDITDIRIDSERV) is not defined or not filled in.';
		}
		if( empty($errmsg) && strrpos(WEBEDITDIRIDSERV,'/') != (strlen(WEBEDITDIRIDSERV)-1) ) {
	    	$errmsg = 'Configured InDesign Server workspace (WEBEDITDIRIDSERV) has no slash (/) as last character.';
		}

		// - - - - - - - - - - - - - - - InDesign Servers - - - - - - - - - - - - - - - - - - - - 

		// Check if the INDESIGNSERV_APPSERVER option is set
		if( !defined('INDESIGNSERV_APPSERVER') || trim(INDESIGNSERV_APPSERVER) == '' ) {
    		$this->setResult( 'ERROR', 'The INDESIGNSERV_APPSERVER option is not set. Please check your configserver.php file.' );
    		return;
		}

    	// Check configured InDesign Servers
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
   		$idsObjs = BizInDesignServer::listInDesignServers();
		foreach( $idsObjs as $idsObj ) {
			$msgPrefix = '<h3><span style="color:white; background-color:gray;">&nbsp;InDesign Server:&nbsp;></span>&nbsp;'.$idsObj->Name.'</h3>';
    		if( $idsObj->Active ) {
				$this->checkInDesignServer( $idsObj, $msgPrefix );
			} else {
	    		$this->setResult( 'NOTINSTALLED', $msgPrefix.'Set to non-Active.' );
			}
		}

		// Check defined curl command path
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerDispatcher.class.php';
		$curlPath = BizInDesignServerDispatcher::getCurlPath();
		if( !file_exists($curlPath) ) {
			$help = 'InDesign Server background job needs to have CURL installed and defined. '.
					'Please install CURL and set the define in the configserver.php file.';
			$this->setResult( 'ERROR', 'The CURL command path "'. $curlPath .'" is not found.', $help );
			return;
		}

		// - - - - - - - - - - - - - - - InDesign Server Jobs - - - - - - - - - - - - - - - - - - - -

		// Check if all the priorities are covered
		$retVal = BizInDesignServer::checkCoveredPriorities();

		if( $retVal ){
			$this->setResult('ERROR', $retVal);
			return;
		}

		// success
		LogHandler::Log('wwtest', 'INFO', 'Indesign Server installation correct.');
    }

	private function checkInDesignServer( $idsObj, $msgPrefix )
	{
		$errmsg = '';
		$help = '';
		// RBU removed: We cannot test on this app server if InDesign Server can access the path from his own perspective
    	//if( empty($errmsg) && !is_dir(WEBEDITDIRIDSERV) ) {
	    // 	$errmsg = 'Configured InDesign Server workspace (WEBEDITDIRIDSERV) could not be created.';
    	//}
		$prodInfoPath = WEBEDITDIR.'products.xml';
		if( empty($errmsg) ) {
			// Let ID Server (JavaScript) try to create products.xml file in its workspace.
			// This is done to check if ID Server (JS) has write access. 
			// Note that ID Server is a different process than PHP and might act for different users and so have different access profile!
			clearstatcache( true, $prodInfoPath );

			// EN-90166: For cifs-systems opening and closing a file updates the cache.
			@fclose( fopen ( $prodInfoPath, 'r' )); // Not interested if there's any error, suppress it with @. Do 'r' mode as no new file should be created.

			if( file_exists($prodInfoPath) ) {
				if( !unlink( $prodInfoPath ) ) { // clear previous runs
			    	$errmsg = 'Could not remove test file of previous runs:<pre>&nbsp;&nbsp;&nbsp;'.$prodInfoPath.'</pre>';
			    	$help = 'Please remove this file manually (since PHP does not require write access to this InDesign Server workspace).';
				}
			}
		}
		if( !empty($errmsg) ) { 
    		$this->setResult( 'ERROR', $msgPrefix.$errmsg, $help );
    		return;
    	}

    	// Check installed ID Server plugins
    	$idsVersions = array_keys( unserialize( ADOBE_VERSIONS ) );
    	$idsLastVersion = array_pop( $idsVersions );
    	$idsVersionsCSV = implode( ', ', $idsVersions ).' or '.$idsLastVersion;
    	$help = 'Please check the following for all configurations made at the InDesign Servers maintenance page:<ul>';
    	$help .= '<li>The complete package of Smart Connection plug-ins must be installed at your InDesign Server installation.</li>';
    	$help .= '<li>The Enterprise Server should have HTTP access through configured URL (and port) and InDesign Server should run and listen to it.</li>';
    	$help .= '<li>Both InDesign Server version and Smart Connection plug-ins version needs to be '.$idsVersionsCSV.'.</li>';
    	$help .= '<li>The plug-ins must be registered using the activation file (WWActivate.xml).</li></ul>';
    	$help .= 'See also Admin Guide about how to install and configure CS Editor and InDesign Server.';


		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
		$prodInfoPathInDesignServer = WEBEDITDIRIDSERV.'products.xml';
		try {
			BizInDesignServerJobs::createAndRunJob(
				file_get_contents( dirname(__FILE__).'/getprodinfo.js' ),
				array( 'respfile' => $prodInfoPathInDesignServer ), 
				'Get installed products info', null, // job type, object id
				$idsObj, null, null, // ids obj, min ids version, max ids version
				'Health Check' // context
			);
			clearstatcache( true, $prodInfoPath ); // Make sure that file_exists() not fails as the products.xml has just been deleted above.
		} catch( BizException $e ) {
			$errmsg = 'The getprodinfo request failed. '.$e->getMessage();
    		$this->setResult( 'ERROR', $msgPrefix.$errmsg, $help );
    		return;
		}

		// EN-90166: For cifs-systems opening and closing a file updates the cache.
		@fclose( fopen ( $prodInfoPath, 'r' )); // Not interested if there's any error, suppress it with @. Do 'r' mode as no new file should be created.

		// Check InDesign Server write access in CS Editor workspace
		if( !file_exists($prodInfoPath) ) {
			$errmsg = 'The getprodinfo.js could not write to:<pre>&nbsp;&nbsp;&nbsp;'.$prodInfoPath.'</pre>';
			$help = 'Make sure InDesign Server has write access to subfolders being created in its workspace:<br/>'.
				'<pre>&nbsp;&nbsp;&nbsp;'.WEBEDITDIRIDSERV.'</pre><br/>'.
				'For Macintosh you could start InDesign Server (without use of sudo) as follows:<br/>'.
				'<pre>&nbsp;&nbsp;&nbsp;./InDesignServer -port 18383</pre>';
			$help .= 'See also Admin Guide about how to install and configure CS Editor and InDesign Server.';
    		$this->setResult( 'ERROR', $msgPrefix.$errmsg, $help );
    		return;
		}

		// parse product info of installed plugins
		$xml_parser = xml_parser_create();
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
		$prodParser = new ProdParser();
		xml_set_object( $xml_parser, $prodParser );
		xml_set_element_handler($xml_parser, "startElementXML", "endElementXML");
		xml_set_character_data_handler($xml_parser, "characterDataXML");
		$prodInfoFile = file_get_contents($prodInfoPath);
	    if (!xml_parse($xml_parser, $prodInfoFile, true)) {
	        $errmsg = sprintf("XML error: %s at line %d",
	                    xml_error_string(xml_get_error_code($xml_parser)),
	                    xml_get_current_line_number($xml_parser));
    		$this->setResult( 'ERROR', $msgPrefix.$errmsg, $help );
    		return;
	    }
		xml_parser_free($xml_parser);
		$prodInfos = $prodParser->getProdInfos();
		if( empty( $prodInfos ) ) {
			$errmsg = 'No installed products found.';
    		$this->setResult( 'ERROR', $msgPrefix.$errmsg, $help );
    		return;
		}
		
		// validate product info of installed plugins
		$scSceFound = false;
		$errmsg = '';
		$showProducts =
			'<table border="1"><tr>'.
				'<td><b>Name</b></td>'.
				'<td><b>Code</b></td>'.
				'<td><b>Version</b></td>'.
				'<td><b>State</b></td>'.
			'</tr>';
		foreach( $prodInfos as $prodInfo ) {
			$isSce = (strpos( $prodInfo['NAME'], 'SCID' ) === 0) ||  // v6.1
					 (strpos( $prodInfo['NAME'], 'SCEntIDS' ) === 0); // v6.0 => No good version, but that is checked later. Here we try to resolve the product name! (Else you get vague messages like 'product not found')
			$isPro = strpos( $prodInfo['NAME'], 'SCPRID' ) === 0;
			$isIDS = strpos( $prodInfo['NAME'], 'InDesign Server' ) === 0;
			$isIMG = strpos( $prodInfo['NAME'], 'SmartImageIDS' ) === 0;
			$isJMP = strpos( $prodInfo['NAME'], 'SJump' ) === 0;
			if( $isPro ) { // SC Pro ?
				$prodName = 'Smart Connection Pro';
			} elseif( $isIDS ) {
				$prodName = 'InDesign Server';
			} elseif( $isSce ) { // Smart Connection plug-ins?
				$scSceFound = true;
				$prodName = 'Smart Connection';
			} elseif( $isIMG ) {
				$prodName = 'Smart Image';
			} elseif( $isJMP ) {
				$prodName = 'Smart Jump';
			} else {
				$prodName = $prodInfo['NAME'];
			}
			// InDesign Server major version should match shipped serverinfo.php range
			if( $isIDS ) {
				$verInfo = array();
				preg_match( "/v([0-9]+).([0-9]+).([0-9]+).([0-9]+)/", $prodInfo['VERSION'], $verInfo );
				if( count( $verInfo ) >= 4 ) { // major, minor, patch, build
					$adobeVersions = unserialize( ADOBE_VERSIONS );
					if( !in_array( $verInfo[1] . '.0', $adobeVersions ) ) {
						if( !empty( $errmsg ) ) $errmsg .= '<br/>';
						$errmsg .= $prodName . ' version not supported: ' . $prodInfo['VERSION'];
					}
				} else {
					if( !empty( $errmsg ) ) $errmsg .= '<br/>';
					$errmsg .= $prodName . ' version has bad format: ' . $prodInfo['VERSION'];
				}
			}
			if( $prodInfo['STATE'] != 'serial' &&
				$prodInfo['STATE'] != 'limited serial' &&
				$prodInfo['STATE'] != 'server' &&
				$prodInfo['STATE'] != 'limited server' ) {
				if( !empty($errmsg) ) {
					$errmsg .= '<br/>';
				}
				$errmsg .= $prodName.' version has bad state: '.$prodInfo['STATE'] ;
			}
				
			$showProducts .=
				'<tr>'.
					'<td>'.$prodName.'</td>'.
					'<td>'.$prodInfo['NAME'].'</td>'.
					'<td>'.$prodInfo['VERSION'].'</td>'.
					'<td>'.$prodInfo['STATE'].'</td>'.
				'</tr>';
		}
		$showProducts .= '</table>';
		if( $scSceFound === false ) {
			if(!empty($errmsg)) $errmsg .= '<br/>';
			$errmsg .= 'No Smart Connection Enterprise plug-ins found.';
		}

		// show installed products
   	$this->setResult( 'INFO',  $msgPrefix.'Installed products:<br/>'.$showProducts );

		if( !empty($errmsg) ) {
    		$this->setResult( 'ERROR', $errmsg, $help );
    		return;
		}
		
		// Get the userName and password from TestSuite setting in ConfigServer.php		
		if( !defined('TESTSUITE') ) {
			$help = 'Since v7.0, the TestSuite (at wwtest page) needs to have a TESTSUITE option defined. '.
				'Please merge the option from the original server package into your configserver.php file.';
			$this->setResult( 'ERROR', 'The TESTSUITE option is not defined.', $help );
			return;
		}

		$suiteOpts = unserialize( TESTSUITE );
		$scriptResult = '';
		
		// Check if INDESIGNSERV_APPSERVER matches at IDS end
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
		try {
			$scriptParams = array( 
				'appServer' => INDESIGNSERV_APPSERVER, 
				'Username' => $suiteOpts['User'], 
				'Password' => $suiteOpts['Password']
			);
			$scriptResult = BizInDesignServerJobs::createAndRunJob(
				file_get_contents( dirname(__FILE__).'/IndesignServer_CheckConfig.js' ), $scriptParams,
				'Check IDS->Enterprise connection', null, // job type, object id
				$idsObj, null, null, // ids obj, min ids version, max ids version
				'Health Check' // context
			);
		} catch( BizException $e ) {
			$errmsg = 'The IndesignServer_CheckConfig.js module failed: '.$e->getMessage();
    		$this->setResult( 'ERROR', $msgPrefix.$errmsg, $help );
    		return;
		}

		$responseData =  explode( ' ', $scriptResult ); // Limit is set to ' ' as neither the ticket nor the URL contain a ' '.
		$ticket = $responseData[0];
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		if ( !DBTicket::getTicket( $ticket ) ) {
			$serverURL = htmlentities($responseData[1]);
			$errmsg = 'Configuration error. InDesign Server is connecting to another Enterprise Server ('.$serverURL.').';
			$help = 'Check if the WWSettings.xml file contains servers with the same name. '.
					'Next, check if the APPLICATION_SERVERS setting in the configserver.php file is correct.';
			$this->setResult( 'ERROR', $errmsg, $help );
			return;
		}

		$previewOption = $responseData[2];
		if ( $previewOption == 'ImagePreviewOptionNotSet' ) {
			$help = 'Please check if the \'-previews\' option is added to the start command or start parameters. ';
			$errmsg = 'InDesign Server is started without the \'-previews\' option. This could result in missing image previews.';
			$this->setResult( 'ERROR', $errmsg, $help );
		}
	}
}

// ------------------------------------------

class ProdParser
{
	//private $Indent = 0; // for havvy debugging only
	private $ProdInfos = array();
	
	public function startElementXML($parser, $name, $attrs) 
	{
		/*$this->Indent++;
		for ( $i=0; $i < $this->Indent; $i++ ) {
			print "&nbsp;";
		}
		print "&lt;<font color='#0000ff'>$name</font>&gt;";
		foreach( $attrs as $attrKey => $attrVal ) {
			print "<font color='#ff0000'>$attrKey=</font>$attrVal ";
		}
		print "<br/>";*/
		if( strtoupper($name) == 'PRODINFO' ) {
			$this->ProdInfos[] = $attrs;
		}
	}
	
	public function endElementXML($parser, $name) 
	{
		//print "&lt;/<font color='#0000ff'>$name</font>&gt;<br>";
	    //$this->Indent--;
	}
	
	public function characterDataXML($parser, $data ) 
	{
		// nothing to do
	}
	
	public function getProdInfos() 
	{ 
		return $this->ProdInfos;
	}
}
