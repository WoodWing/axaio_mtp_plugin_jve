<?php
/**
 * Interface- and abstract classses to be implemented by TestCase and TestSuite classes.
 * Those classes can be picked up from disk and run automatically by TestSuiteFactory.
 *
 * A TestCase is a single class that has a test method to be run and called by TestSuiteFactory.
 * A TestSuite is a class that bundles other TestCase classes which belong to each other.
 * Assumed is that there is only 1 TestSuite file per folder and there can many TestCase files per folder.
 * Each folder directly under /wwtest/testsuite is a root test folder that can be run.
 * Those root folder can be seen as test applications that be clicked by admin users to run.
 * All folders under those roots can be seen a test suites that be tagged by users to 
 * participate with the test run or untagged to exclude from test runs.
 *
 * @package     ProxyForSC
 * @subpackage  TestSuite
 * @since       v1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

interface TestModule
{
	public function getDisplayName();
	public function getDisplayWarn();
	public function getTestGoals();
	public function getTestMethods();
    public function getPrio();
	/**
	 * Checks whether or this TestCase can be tested.
	 * 
	 * If it cannot be tested, set the reason in the test results.
	 *
	 * @return bool true when TestCase can be tested
	 */
    public function isTestable();
	/**
	 * Return the reason why this is testable.
	 * 
	 * @return string reason why it's testable or not
	 */
    public function getIsTestableReason();

    /**
     * Tells if the test cleans all its created records and files from the DB and filestore.
     *
     * @return boolean
     */
    public function isSelfCleaning();
    
    /**
     * Table names that will be excluded from being checked whether
     * the table has been self clean.
     * This function is only useful when isSelfCleaning() is True.
     *
     * @return array of Table names that will be excluded
     */     
    public function getNonCleaningTables();
}

abstract class TestSuite implements TestModule
{
	public function getDisplayWarn()
	{
		return ''; // no warning (by default)
	}

	public function isTestable()
	{
		// default implementation
		return true;
	}

	public function getIsTestableReason()
	{
		// default implementation
		return '';
	}

    public function isSelfCleaning()
    {
    	return false;
    }
    
    public function getNonCleaningTables()
    {
    	return null;
    }
}

abstract class TestCase implements TestModule
{
	/**
	 * All test results collected during a runTest() call.
	 * @var TestResult[]
	 */
	private $results = array();
	
	/**
	 * Whether or not the $this->results contains an 'ERROR' or 'FATAL'.
	 * @var boolean
	 * @since 9.0.0
	 */
	private $hasError = false;
	
	/**
	 * Whether or not the $this->results contains an 'WARN'.
	 * @var boolean
	 * @since 9.5.0
	 */
	private $hasWarning = false;
	
	const SESSION_NAMESPACE = 'TestCase';

	/**
	 * Called to execute a test case. Needs to be implemented by subclass of TestCase.
	 * There can be many steps to be tested, which all need to take place within this
	 * function. The setResult() function can be used by the implementor to report any
	 * problems found during the test. It is up to the implementor to decide whether or
	 * not to continue with the next step. Precessing errors can be detected by calling
	 * the hasError() function. 
	 */
	abstract public function runTest();

	/**
	 * Returns all test results collected during a runTest() call.
	 * Used to report test results to the test client application.
	 *
	 * @return TestResult[]
	 */
	public function getResults()
	{
		return $this->results;
	}
	
	/**
	 * During the runTest() call, the TestCase implementor can record results by calling 
	 * this function. There can be multiple results for one test. When no results are set, 
	 * the test is considered to be totally fine (everything is OK).
	 *
	 * When raising the 'FATAL' status the whole test run is cancelled, whereby next 
	 * Test Cases are skipped / ignored. Basically this ends a whole test batch and
	 * therefore should be used when it is absolutely sure that further testing is useless.
	 *
	 * @param string $status 'FATAL', 'ERROR', 'WARN', 'INFO" or 'NOTINSTALLED'
	 * @param string $message Display text of the result.
	 * @param string $configTip Description how user can resolve the problem.
	 * @param bool $writeToLog Whether or not to write message to server logging
	 */	
	public function setResult( $status, $message, $configTip='', $writeToLog=true )
	{
		if( !$this->hasError ) {
			$this->hasError = ($status == 'FATAL' || $status == 'ERROR' );
		}
		if( !$this->hasWarning ) {
			$this->hasWarning = ($status == 'WARN');
		}
		if( $writeToLog ) {
			$level = $status == 'NOTINSTALLED' ? 'WARN' : $status;
			$level = $status == 'FATAL' ? 'ERROR' : $status;
			LogHandler::Log( 'wwtest', $level, $message );
		}
		$this->results[] = new TestResult( $status, $message, $configTip );
	}
	
	/**
	 * Whether or not the Test Case has already raised the ERROR or FATAL flag in
	 * the current runTest() call. Can be used e.g. to determine whether or not to 
	 * continue with next test step.
	 *
	 * @since 9.0.0
	 * @return boolean
	 */
 	public function hasError()
 	{
 		return $this->hasError;
 	}
	
	/**
	 * Whether or not the Test Case has raised the WARN flag in the current runTest() call.
	 *
	 * @since 9.5.0
	 * @return boolean
	 */
 	public function hasWarning()
 	{
 		return $this->hasWarning;
 	}
	
	public function getDisplayWarn()
	{
		return ''; // no warning (by default)
	}

	public function isTestable()
	{
		// default implementation
		return true;
	}

	public function getIsTestableReason()
	{
		// default implementation
		return '';
	}

    public function isSelfCleaning()
    {
    	return false;
    }

    public function getNonCleaningTables()
    {
    	return null;
    }

	/**
	 * Returns the start boundary of auto increment value
	 */
	public function initialAutoIncrement()
	{
		return 0;
	}

	/**
	 * Returns the end boundary of auto increment value
	 */	
	public function lastAutoIncrement()
	{
		return 0;
	}
	
	/** - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	 * The session related functions (below) allow you to read/write test results into the PHP session. 
	 * An example how to use this is added to the Build Test:
	 *    .../server/wwtest/testsuite/BuildTest/WebServices/AdmServices/AdmInitData_TestCase.php
	 *    .../server/wwtest/testsuite/BuildTest/WebServices/AdmServices/AdmUsers_TestCase.php
	 * It passes through the retrieved Enterprise Server ticket. (But this can be any data.) 
	 * The ticket must be seen as data. Please do not confuse the test session id with the 
	 * Enterprise Server ticket as tracked by BizSession:
	 * - The test session id is needed to access the test session data. 
	 * - The ticket is needed to access the server session data. 
	 * The ticket is stored in the test session data to let all TestCase modules use it directly, 
	 * without the need to re-logon the test user over an over again. The very same mechanism 
	 * can be used to pass through any data from one TestCase module to another. 
	 *
	 * Note that test sessions should not be confused with Enterprise sessions (tickets).
	 * Why not re-using the Enterprise Server session (at BizSession) to read/write test data?
	 * Reason to have separate/isolated test session is that there might be no Enterprise Server
	 * session at all when the TestCase...
	 * - just tests a class/function (not through services)
	 * - runs services before LogOn (having no ticket for the session)
	 * - is a SOAP client, for which no session is started for the client's PHP process
	 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	 */

	private $sessionId = null;

	/**
	 * Set the test session id.
	 *
	 * @param string $sessionId
	 */
	public function setSessionId( $sessionId ) { $this->sessionId = $sessionId; }

	/**
	 * Get the test session id.
	 * @return string $sessionId
	 */
	public function getSessionId() { return $this->sessionId; }
	
	/**
	 * Set multiple session variables.
	 * This function opens the session, sets variables and closes the session. This way, other
	 * processes won't be locked by PHP's session semaphore.
	 * Other sessions, such as Enterprise's BizSession/ticket, are preserved by reading and restoring it.
	 *
	 * Please note: with concurrent processes the last call to
	 * setSessionVariables will overrule the earlier one
	 *
	 * @param array $variables key values pairs
	 */
	public function setSessionVariables( $variables )
	{
		$originalId = session_id(); // remember original session (most likely BizSession ticket)
		session_id( $this->sessionId );
		session_start();
		if( !isset( $_SESSION[self::SESSION_NAMESPACE] ) ) {
			$_SESSION[self::SESSION_NAMESPACE] = array();
		}
		foreach( $variables as $key => $value ) {
			$_SESSION[self::SESSION_NAMESPACE][$key] = $value;
		}
		session_write_close();
		session_id( $originalId ); // restore original session (most likely BizSession ticket)
	}

	/**
	 * Get multiple session variables.
	 * This function opens the session, gets variables and closes the session. This way, other
	 * processes won't be locked by PHP's session semaphore.
	 * Other sessions, such as Enterprise's BizSession/ticket, are preserved by reading and restoring it.
	 * 
	 * @return array key value pairs
	 */
	public function getSessionVariables()
	{
		$originalId = session_id(); // remember original session (most likely BizSession ticket)
		session_id( $this->sessionId );
		session_start();
		$variables = array();
		if( isset( $_SESSION[self::SESSION_NAMESPACE] ) ) {
			foreach( $_SESSION[self::SESSION_NAMESPACE] as $key => $value ) {
				$variables[$key] = $value;
			}
		}
		session_write_close();
		session_id( $originalId ); // restore original session (most likely BizSession ticket)
		return $variables;
	}
}

class TestResult
{
	public $Status;
	public $Message;
	public $ConfigTip;
	
	public function __construct( $status, $message, $configTip ) 
	{
		$this->Status = $status;
		$this->Message = $message;
		$this->ConfigTip = $configTip;
	}
}
