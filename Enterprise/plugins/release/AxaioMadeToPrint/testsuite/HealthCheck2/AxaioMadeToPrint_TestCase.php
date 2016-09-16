<?php
/**
 * axaio MadeToPrint TestCase class that belongs to the TestSuite of the HealthCheck.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once(BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php');
#require_once(BASEDIR.'/server/utils/InDesignServer.class.php');
require_once(BASEDIR.'/config/plugins/AxaioMadeToPrint/config.php');

class WW_TestSuite_HealthCheck2_AxaioMadeToPrint_TestCase extends TestCase
{
    public function getDisplayName() { return 'axaio MadeToPrint'; }
    public function getTestGoals()   { return 'Checks if axaio MadeToPrint is installed and configured correctly.'; }
    public function getTestMethods() { return 'Check for required Tables and Triggers'; }
    public function getPrio()        { return 0; }
	
	final public function runTest()
	{
        LogHandler::Log('wwtest', 'INFO', 'Testing MadeToPrint configuration');

        if( !$this->checkInstalled() ) {
			return;
		}
        
        // check server version
        // SERVERVERSION => '9.2.0 Build 53'
        $version = array();
        if(! preg_match( "/^(\\d+)\\.(\\d+)\\.(\\d+).+/" , SERVERVERSION, $version)) {
            $this->setResult( 'ERROR', "Could not find server version.", "" );
			return;
        }
        if( $version[1] < 9 || ($version[1] == 9 && $version[2] < 2))
		{
			$this->setResult( 'ERROR', "AxaioMadeToPrint requires Enterprise 9.2.0 or newer", "" );
			return;
		}
        
        // check for old settings, mostly from earlier integrations
        $this->checkObsoletedSettings();
    
        // check folder if they are writable
        if( !$this->checkMTPFolder( 'AXAIO_MTP_SERVER_FOLDER_IN') 
		||	!$this->checkMTPFolder( 'AXAIO_MTP_AXAIO_FOLDER_IN' ) 
		||	!$this->checkMTPFolder( 'AXAIO_MTP_AXAIO_FOLDER_OUT') 
        ) {
			return;
		}
        
        /*
        if( !$this->checkURLResponse( 'AXAIO_MTP_PREPROCESS_LOC' )
        ||  !$this->checkURLResponse( 'AXAIO_MTP_POSTPROCESS_LOC')
        ) {
            return;
        }
        */
		// check if the MtP user is configured and try to logon/logoff 
		if( trim(AXAIO_MTP_USER) == '' ) {
			$help = 'Please check AXAIO_MTP_USER option at config/plugins/AxaioMadeToPrint/config.php.';
			$this->setResult( 'ERROR', "No MadeToPrint user name specified.", $help );
			return;

		}
		if( trim(AXAIO_MTP_PASSWORD) == '' ) {
			$help = 'Please check AXAIO_MTP_PASSWORD option at config/plugins/AxaioMadeToPrint/config.php.';
			$this->setResult( 'ERROR', "No MadeToPrint user password specified.", $help );
			return;
		}
		try {
			require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
			$req = new WflLogOnRequest( AXAIO_MTP_USER, AXAIO_MTP_PASSWORD, null, null, '', null, 'MtP test', null, null, null, true );
			$service = new WflLogOnService();
			$ret = $service->execute( $req );
			$ticket = $ret->Ticket;
		} catch( BizException $e ) {
				$this->setResult( 'ERROR', 'Failed to logon the configured MadeToPrint user. Error returned from server: '.$e->getMessage(), 'Please check AXAIO_MTP_USER and AXAIO_MTP_PASSWORD options at configserver.php.');
				return;
		}
		try {
			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$req = new WflLogOffRequest( $ticket, null, null, null );
			$service = new WflLogOffService();
			$service->execute( $req );
		} catch( BizException $e ) {
            $this->setResult( 'ERROR', 'Failed to logoff the configured MadeToPrint user. Error returned from server: '.$e->getMessage(), 'Please check AXAIO_MTP_USER and AXAIO_MTP_PASSWORD options at configserver.php.');
				return;
		}
		

		$sql_error = '';
		$dbDriver = DBDriverFactory::gen();

		$check_tables = array('axaio_mtp_trigger', 'axaio_mtp_sentobjects');
		$not_found_tables =array();
        foreach ($check_tables as $value) {
            if (!$dbDriver->tableExists($value, false)) {
                $not_found_tables[] = $value;
            }
        }
		if(!empty($not_found_tables))
		{
			$tables = (count($not_found_tables) == 1) ? 'Table' : 'Tables';
			$not_found_tables = implode(', ', $not_found_tables);
			$url = SERVERURL_ROOT.INETROOT.'/config/plugins/AxaioMadeToPrint/testsuite/HealthCheck2/createTable.php';
			$help = 'Please update database through this page: <a href="'.$url.'" target="_blank">Axaio DB Admin</a>';
			$this->setResult( 'ERROR', "Required $tables <b>$not_found_tables</b> not found.", $help );
			return;
		}

		return;
	}


    /**
	 * Checks if configured MadeToprint paths are OK.
	 *
	 * @return boolean TRUE when 'good enough'. FALSE when is makes no sense to continue testing.
	 */
	private function checkMTPFolder( $constName ) 
    {
        $folder = constant($constName);
        if( empty($folder)) { // NULL or '' or not defined properly
            $this->setResult( 'ERROR', "{$constName} is not specified.", "Please define and set the {$constName} option at the plugin config.php.");
            return false;
        }
        
        if( substr($folder, -1, 1) != '/' && substr($folder, -1, 1) != '\\' ) {
            $this->setResult( 'ERROR', "The specified MadeToPrint folder {$constName} has no slash (/) or backslash (\\) at the end.", "Please check {$constName} option at configserver.php.");
            return false;
        }

        
        $tokenBCnt = (defined('AXAIO_MTP_TOKEN_BEGIN'))?substr_count($folder, AXAIO_MTP_TOKEN_BEGIN):0;
        $tokenECnt = (defined('AXAIO_MTP_TOKEN_END')  )?substr_count($folder, AXAIO_MTP_TOKEN_END  ):0;
        if($tokenBCnt && $tokenBCnt!=$tokenECnt) {
            $this->setResult( 'WARN', "There is indication that tokens are used in {$constName}, but number of AXAIO_MTP_TOKEN_BEGIN and AXAIO_MTP_TOKEN_END does not match.");
            // continue, may be on purpose
        }
        if($tokenBCnt && $tokenECnt) {
            $this->setResult( 'WARN', "There are tokens used in {$constName}, please double check that the folders exists and are writeable.");
        } else { //no token used, so check folder for existance
            if($constName === 'AXAIO_MTP_SERVER_FOLDER_IN') { // only check the server in folder, and only if no tokens are used
                if(!is_dir($folder)) {
                    $this->setResult( 'ERROR', "The specified MadeToPrint IN folder {$constName} does not exist at file system.", "Please check that '{$folder}' exists of define the correct folder.");
                    return FALSE;
                }
                if(!is_writable($folder)) {
                    $this->setResult( 'ERROR', "No write access to specified MadeToPrint IN folder {$constName}.", "Please check that '{$folder}' can be written or define the correct folder.");
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
	 * Checks if MadeToprint server plug-in is installed and enabled.
	 *
	 * @return boolean True when 'good enough'. False when is makes no sense to continue testing.
	 */
	private function checkInstalled()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$pluginObj = BizServerPlugin::getPluginForConnector( 'AxaioMadeToPrint' );
		if( $pluginObj && $pluginObj->IsInstalled ) {
			if( !$pluginObj->IsActive ) {
				$this->setResult('ERROR', 'The MadeToPrint server plug-in is disabled.', 'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>');
				// continue
			}
		} else {
			$this->setResult('NOTINSTALLED', 'The MadeToPrint server plug-in is not installed.', 'Check <a href="../../server/admin/serverplugins.php'.'">Server Plug-ins</a>');
			// continue
		}
		return true;
	}

	/**
	 * Checks if there are still some obsoleted defines and settings made at the config file or DB.
	 *
	 * @return boolean TRUE when 'good enough'. False when is makes no sense to continue testing.
	 */
	private function checkObsoletedSettings()
	{
        $errorDefs = array( 'MTP_SERVER_DEF_ID');
		foreach( $errorDefs as $define ) {
			if( defined($define) && constant($define) != '') {
				$this->setResult( 'ERROR', "When using the axaio MadeToPrint Server Plug-In, {$define} option in configserver.php must be disabled.", 'Remove the option from your configserver.php file. Configuration for the plugin is done in the plugins config.php file' );
				return FALSE;
			}
		}
		
		// Check obsoleted defines
		$defines = array( 'AXAIO_MTP_INFOLDER_PER_PUBLICATION','AXAIO_MTP_ALL_METADATA' );
		foreach( $defines as $define ) {
			if( defined($define) ) {
				$this->setResult( 'INFO', "The {$define} option is no longer supported.", 'Remove the option from your configserver.php file.' );
				// continue
			}
		}
		
		return TRUE;
	}
    /**
	 * Checks if the SOLR_SERVER_URL at the config_solr.php file is set correct.
	 * It also checks if the Solr server is responsive on the configured URL.
	 *
	 * @return boolean True when 'good enough'. False when is makes no sense to continue testing.
	 */
    private function checkURLResponse( $constName )
    {
        $url = constant($constName);
        if( empty($url)) { // NULL or '' or not defined properly
            $this->setResult( 'ERROR', "{$constName} is not specified.", "Please define and set the {$constName} option at configserver.php.");
            return FALSE;
        }

        $urlParts = @parse_url( $url );
        if( !$urlParts || !isset($urlParts["host"]) ) {
            $this->setResult( 'ERROR', "The specified {$constName} seems to be malformed.", "Please check {$constName} option at configserver.php.");
            return FALSE;
        }
	
        $host = $urlParts["host"];
        $port = isset($urlParts["port"]) ? $urlParts["port"] : 80;
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen( $host, $port, $errno, $errstr, 5 );
        if( !$socket ) {
            $this->setResult( 'ERROR', "The specified {$constName} is not responsive ({$errstr})", "Please check {$constName} option at configserver.php.");
            return FALSE;
        }
        fclose( $socket );
    }


	/**
	 * This method checks if the mandatory fields are set in config_solr.php  and if 
	 * they match with the settings of schema.xml
	 *
	 * @param array $schemaInfo Info read from the schema.xml file.
	 */
	private function checkMandatoryFields($schemaInfo) 
	{
		$mandatoryFields = array('ID', 'PublicationId', 'IssueId', 'CategoryId', 'StateId', 'IssueIds', 'Issues', 'Closed');
		
		foreach ($mandatoryFields as $mandatoryField) {
			if (!array_key_exists($mandatoryField, $schemaInfo)) {
					$this->setResult( 'ERROR', 'Mandatory field ' . "'$mandatoryField'" . ' not in &lt;fields&gt; section of schema.xml.' , 'Check &lt;fields&gt; tag in schema.xml.');
			}		
			elseif ($schemaInfo[$mandatoryField]['indexed'] !== 'true') {
					$this->setResult( 'ERROR', "Mandatory field $mandatoryField must be indexed." , "Add 'indexed = \"true\"' on field $mandatoryField in schema.xml.");
			}
		} 
	}
    
    /**
	 * Checks all MadeToPrint options at the configserver.php file.
	 * This check is not done at wwtest since the MadeToPrint integrations is optional.
	 * Nevertheless, it needs to be checked when using MadeToPrint.
	 *
	 * @return string Error message or empty when no error.
	 */
	private function testMtpConfigServer()
	{
		// The application server name determines if MtP is enabled or not.
		if( trim(AXAIO_MTP_SERVER_DEF_ID) == '' ) {
			return 'MadeToPrint is disabled. Set options at configserver.php to enable. The AXAIO_MTP_SERVER_DEF_ID option tells if MadeToPrint is enabled or not.';
		}
	
		// Check if in/out folders are configured correctly and accessable	
		if( trim(AXAIO_MTP_SERVER_FOLDER_IN) == '' ) {
			return 'No MadeToPrint in-folder specified. Please check AXAIO_MTP_SERVER_FOLDER_IN option at configserver.php.';
		}
		if( strrpos(AXAIO_MTP_SERVER_FOLDER_IN,'/') != (strlen(AXAIO_MTP_SERVER_FOLDER_IN)-1) ) {
			return 'The specified MadeToPrint in-folder has no slash (/) at the end. Please check AXAIO_MTP_SERVER_FOLDER_IN option at configserver.php.';
		}
		if( !is_dir(AXAIO_MTP_SERVER_FOLDER_IN) ) {
			return 'The specified MadeToPrint in-folder does not exist at file system. Please check file system and AXAIO_MTP_SERVER_FOLDER_IN option at configserver.php.';
		}
		if(!is_writable(AXAIO_MTP_SERVER_FOLDER_IN)){
			return 'No write access to specified MadeToPrint in-folder. Please check file system and AXAIO_MTP_SERVER_FOLDER_IN option at configserver.php.';
		}
		
		if( trim(AXAIO_MTP_AXAIO_FOLDER_IN) == '' ) {
			return 'No MadeToPrint in-folder specified. Please check AXAIO_MTP_AXAIO_FOLDER_IN option at configserver.php.';
		}
		if( strrpos(AXAIO_MTP_AXAIO_FOLDER_IN,'/') != (strlen(AXAIO_MTP_AXAIO_FOLDER_IN)-1) ) {
			return 'The specified MadeToPrint in-folder has no slash (/) at the end. Please check AXAIO_MTP_AXAIO_FOLDER_IN option at configserver.php.';
		}
		
		if( trim(AXAIO_MTP_AXAIO_FOLDER_OUT) == '' ) {
			return 'No MadeToPrint out-folder specified. Please check AXAIO_MTP_AXAIO_FOLDER_OUT option at configserver.php.';
		}
		if( strrpos(AXAIO_MTP_AXAIO_FOLDER_OUT,'/') != (strlen(AXAIO_MTP_AXAIO_FOLDER_OUT)-1) ) {
			return 'The specified MadeToPrint out-folder has no slash (/) at the end. Please check AXAIO_MTP_AXAIO_FOLDER_IN option at configserver.php.';
		}
	
		// Check if post process is configured and can be ping-ed
		if( trim(AXAIO_MTP_POSTPROCESS_LOC) == '' ) {
			return 'No MadeToPrint post process specified. Please check AXAIO_MTP_POSTPROCESS_LOC option at configserver.php.';
		}
		$urlParts = @parse_url( AXAIO_MTP_POSTPROCESS_LOC );
		if( !$urlParts || !isset($urlParts["host"]) ) {
			return 'The specified MadeToPrint post process is not valid. Please check AXAIO_MTP_POSTPROCESS_LOC option at configserver.php.';
		}
		$host = $urlParts["host"];
		$port = isset($urlParts["port"]) ? $urlParts["port"] : 80;
		$errno = 0;
		$errstr = '';
		$socket = @fsockopen( $host, $port, $errno, $errstr, 5 );
		if( !$socket ) {
			return 'The specified MadeToPrint post process is not responsive ('.$errstr.'). Please check AXAIO_MTP_POSTPROCESS_LOC option at configserver.php.';
		}
		fclose( $socket );
	
		// Check if the MtP user is configured and try to logon/logoff 
		if( trim(AXAIO_MTP_USER) == '' ) {
			return 'No MadeToPrint user name specified. Please check AXAIO_MTP_USER option at configserver.php.';
		}
		if( trim(AXAIO_MTP_PASSWORD) == '' ) {
			return 'No MadeToPrint user password specified. Please check AXAIO_MTP_PASSWORD option at configserver.php.';
		}
		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		$client = new WW_SOAP_WflClient();
		try {
			require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
			$req = new WflLogOnRequest( AXAIO_MTP_USER, AXAIO_MTP_PASSWORD, null, null, '', null, 'MtP test', SERVERVERSION, null, null, true );
			$logOnResp = $client->LogOn( $req );
			$ticket = $logOnResp->Ticket;
		} catch( BizException $e ) {
			return 'Failed to logon the configured MadeToPrint user. Please check AXAIO_MTP_USER and AXAIO_MTP_PASSWORD options at configserver.php.'.
					'Error returned from server: '.$e->getMessage();
		}
		try {
			require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
			$req = new WflLogOffRequest( $ticket, null, null, null );
			/* $logOffResp = */ $client->LogOff( $req );
		} catch( BizException $e ) {
			return 'Failed to logoff the configured MadeToPrint user. Please check AXAIO_MTP_USER and AXAIO_MTP_PASSWORD options at configserver.php.'.
					'Error returned from server: '.$e->getMessage();
		}
		
		return '';
	}

}