<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_ProxyConfig_TestCase extends TestCase
{
    /**
     * @var WW_Utils_ProxyTestSuite $utils
     */
    protected $utils;

    public function getDisplayName() { return 'Proxy Configuration'; }
    public function getTestGoals()   { return 'Checks if settings in config.php and configcache.php files are correct. '; }
    public function getTestMethods() { return 'Checks if file paths options have proper slashes and exist on disk, etc, etc.'; }
    public function getPrio()        { return 2; }

    final public function runTest()
    {
        // Start test session.
        ob_start(); // Capture std output. See ob_get_contents() below for details.

        // Make snapshot of the server error log.
        $errorFileOrgPath = LogHandler::getDebugErrorLogFile();
        $errorFileOrgSize = $errorFileOrgPath ? filesize($errorFileOrgPath) : 0;

        require_once BASEDIR.'/server/utils/ProxyTestSuite.php';
        $this->utils = new WW_Utils_ProxyTestSuite();
        if (!$this->testOutputDirectory()) { return; }
        if (!$this->testProxyStubUrl()) { return; }
        if (!$this->testEnterpriseUrl()) { return; }
        if (!$this->testProxyStubTransferPath()) { return; }
        if (!$this->testProxyServerCachePath()) { return; }
        if (!$this->testEnterpriseProxyTransferProtocol()) { return; }
    }

    public function showResults()
    {
        // When the script did not report any errors by itself, raise error when
        // the script has caused errors in server logging. Should be error free.
        if( !$this->hasError() && !$this->hasWarning() ) {
            $errorFileNewPath = LogHandler::getDebugErrorLogFile();
            $errorFileNewSize = $errorFileNewPath ? filesize($errorFileNewPath) : 0;
            if( $errorFileOrgPath != $errorFileNewPath || $errorFileOrgSize != $errorFileNewSize ) {
                $errorFilePath = $errorFileNewPath ? $errorFileNewPath : $errorFileOrgPath;
                $this->setResult( 'ERROR',
                    'Script has caused errors or warnings in server logging. ',
                    'Please check the ones listed in this file: '.$errorFilePath );
            }
        }

        // Combine the collected test results.
        $testResults = $this->getResults();

        // Check if the test did write to std output (e.g. print()). This is always wrong
        // because it should set the test results instead. Bad behavior is flagged with ERROR.
        $printed = ob_get_contents();
        ob_end_clean();
        if( strlen(trim($printed)) > 0 ) {
            $testResults[] = new TestResult( 'ERROR', 'The script has output unofficial message:<br/>'.$printed, '' );
        }
        if( count($testResults) == 0) { // no results, means it all went fine... so we let client know.
            $testResults[] = new TestResult( 'OK', '', '' );
        }

        foreach( $testResults as $testResult ) {

            echo 'Status:    '.$testResult->Status."<br/>\n";
            echo 'Message:   '.$testResult->Message."<br/>\n";
            echo 'ConfigTip: '.$testResult->ConfigTip."<br/>\n";
        }
    }

    private function testOutputDirectory()
    {
        $result = true;
        if( !$this->utils->validateDefines( $this, array('OUTPUTDIRECTORY'),
            'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
            $result = false;
        }

        return $result;
    }

    private function testProxyStubUrl()
    {
        $result = true;
        if( !$this->utils->validateDefines( $this, array('PROXYSTUB_URL'),
            'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
            $result = false;
        }
        elseif (!filter_var(constant('PROXYSTUB_URL'), FILTER_VALIDATE_URL)) {
            $this->setResult('ERROR', 'The option PROXYSTUB_URL is not a valid URL', 'Please provide a valid URL');
            $result = false;
        }

        return $result;
    }

    private function testEnterpriseUrl()
    {
        $result = true;
        if( !$this->utils->validateDefines( $this, array('ENTERPRISE_URL'),
            'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
            $result = false;
        }
        elseif (!filter_var(constant('ENTERPRISE_URL'), FILTER_VALIDATE_URL)) {
            $this->setResult('ERROR', 'The option ENTERPRISE_URL is not a valid URL', 'Please provide a valid URL');
            $result = false;
        }

        return $result;
    }

    private function testProxyServerCachePath()
    {
        $result = true;
        if( !$this->utils->validateDefines( $this, array('PROXYSERVER_CACHE_PATH'),
            'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
            $result = false;
        }
        elseif (!$this->utils->validateFilePath( $this, constant('PROXYSERVER_CACHE_PATH'), 'Make sure the path for PROXYSERVER_CACHE_PATH exists.' ) ) {
            $result = false;
        }
        elseif (!$this->utils->validateDirAccess( $this, constant('PROXYSERVER_CACHE_PATH'), 'Make sure the path for PROXYSERVER_CACHE_PATH is writable.' ) ) {
            $result = false;
        }

        return $result;
    }

    /**
     * File transfer folder that temporary holds request/response data for proxy-stub traffic.
     * The folder resides at the proxy stub.
     *
     * @return bool
     */
    private function testProxyStubTransferPath()
    {
        $result = true;
        if( !$this->utils->validateDefines( $this, array('PROXYSTUB_TRANSFER_PATH'),
            'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY ) ) {
            $result = false;
        }
        elseif (!$this->utils->validateFilePath( $this, constant('PROXYSTUB_TRANSFER_PATH'), 'Make sure the path for PROXYSTUB_TRANSFER_PATH exists.' ) ) {
            $result = false;
        }
        elseif (!$this->utils->validateDirAccess( $this, constant('PROXYSTUB_TRANSFER_PATH'), 'Make sure the path for PROXYSTUB_TRANSFER_PATH is writable.' ) ) {
            $result = false;
        }

        return $result;
    }

    /**
     * File transfer method of HTTP requests and responses between Proxy Server and Proxy Stub.
     *
     * Check which if a transfer protocol is configured. Then, look for defined options for that protocol and if the given values are valid.
     *
     * @return bool
     */
    private function testEnterpriseProxyTransferProtocol()
    {
        $result = true;
        if ( !$this->utils->validateDefines( $this, array('ENTERPRISEPROXY_TRANSFER_PROTOCOL'),
            'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY, 'Define the ENTERPRISEPROXY_TRANSFER_PROTOCOL with any of these options: None, SSH, Aspera' ) ) {
            $result = false;
        }

        switch (ENTERPRISEPROXY_TRANSFER_PROTOCOL) {

            default:
            case 'None':
                break;

            case 'SSH':

                if( !$this->utils->validateDefines( $this, array('SSH_STUBHOST', 'SSH_USERNAME', 'SSH_PASSWORD'),
                    'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY | WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_NOT_EMPTY ) ) {
                    $result = false;
                }

                break;

            case 'Aspera':

                if( !$this->utils->validateDefines( $this, array('ASPERA_USER', 'ASPERA_CERTIFICATE', 'ASPERA_SERVER', 'ASPERA_OPTIONS'),
                    'config.php', 'ERROR', WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_MANDATORY | WW_Utils_ProxyTestSuite::VALIDATE_DEFINE_NOT_EMPTY ) ) {
                    $result = false;
                }

                break;
        }

        return $result;
    }
}

var_dump( get_defined_constants(1) ); exit;

$testcase = new WW_TestSuite_HealthCheck2_ProxyConfig_TestCase();
$testcase->runTest();
$testcase->showResults();