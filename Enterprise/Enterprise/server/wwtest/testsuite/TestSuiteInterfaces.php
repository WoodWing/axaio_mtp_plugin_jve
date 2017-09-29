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
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
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

	/**
	 * Whether or not to auto clean the IDS jobs.
	 *
	 * @since 10.1.4
	 * @var boolean
	 */
	private $autoCleanIdsJobs = false;

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

	/**
	 * To set whether or not to auto clean the IDS jobs after each TestCase.
	 *
	 * @param boolean $autoCleanIdsJobs True to auto clean the IDS jobs, false otherwise.
	 */
	public function setAutoCleanIdsJobs( $autoCleanIdsJobs )
	{
		$this->autoCleanIdsJobs = $autoCleanIdsJobs;
	}

	/**
	 * Returns boolean whether or not to auto clean the IDS jobs.
	 *
	 * @return boolean
	 */
	public function getAutoCleanIdsJobs()
	{
		return $this->autoCleanIdsJobs;
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

	/** - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	 * Assert functions. 
	 *
	 * IMPORTANT: Keep interface the same as PHPUnit_Framework_Assert since that would ease migration in future.
	 *  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 */
	
	/**
	 * Raises an error. The test case is marked with ERROR and BizException is thrown.
	 *
	 * @since 9.5.0
	 * @param string $message
	 * @throws BizException
	 */
	public function throwError( $message )
	{
		$trace = debug_backtrace();
		$countDown = count($trace) - 1;
		$stackDump = '';
		$ourselfIndex = null;
		for( $lev = 1; $lev <= $countDown; $lev++ ) {
			$stackClass = isset($trace[$lev]['class']) ? $trace[$lev]['class'] : '';
			if( $stackClass != __CLASS__ ) {
				$ourselfIndex = $lev - 1;
				break;
			}
		}
		for( ; $lev <= $countDown; $lev++ ) {
			$stackClass = isset($trace[$lev]['class']) ? $trace[$lev]['class'] : '';
			$stackDump .= '- '.$trace[$lev]['class'].'::'.$trace[$lev]['function'].'()'."\r\n".
				'at line '.$trace[$lev-1]['line'].' in '.$trace[$lev-1]['file']."\r\n";
			if( in_array( 'TestCase', class_parents( $stackClass ) ) ) {
				break;
			}
		}
		if( $ourselfIndex ) {
			$message = $trace[$ourselfIndex]['class'].'::'.$trace[$ourselfIndex]['function'].'(): '.$message;
		}
		if( $stackDump ) {
			$message .= "\r\n".'Stack:'."\r\n".$stackDump.'';
		}
	
		$this->setResult( 'ERROR', $message, '', false ); // suppress logging since that is done by BizException too
		throw new BizException( null, 'Server', null, $message ); 
	}

	/**
	 * Asserts that a haystack contains a needle.	
	 *
	 * @since 9.5.0
	 * @param mixed  $needle
	 * @param mixed  $haystack
	 * @param string $message
	 * @throws BizException if $haystack does not contain $needle
	 */
	public function assertContains( $needle, $haystack, $message = '' )
	{
		if( !in_array( $needle, $haystack ) ) {
			if( !$message ) {
				$message = "The haystack does not contain $needle, which is unexpected.";
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a haystack that is stored in a static attribute of a class
	 * or an attribute of an object contains a needle.
	 *
	 * @since 9.5.0
	 * @param integer $needle
	 * @param string  $haystackAttributeName
	 * @param mixed   $haystackClassOrObject
	 * @param string  $message
	 * @throws BizException if haystack does not contain $expectedCount elements
	 */
	function assertAttributeContains( $needle, $haystackAttributeName, $haystackClassOrObject, $message = '' )
	{
		if( property_exists( $haystackClassOrObject, $haystackAttributeName ) ) {
			$this->assertCount( $needle, $haystackClassOrObject->$haystackAttributeName, $message );
		} else {
			$this->throwError( "Object $haystackClassOrObject has no property $haystackAttributeName." );
		}
	}
	
	/**
	 * Asserts that a haystack does not contain a needle.	
	 *
	 * @since 9.5.0
	 * @param mixed  $needle
	 * @param mixed  $haystack
	 * @param string $message
	 * @throws BizException if $haystack does not contain $needle
	 */
	public function assertNotContains( $needle, $haystack, $message = '' )
	{
		if( in_array( $needle, $haystack ) ) {
			if( !$message ) {
				$message = "The haystack does contain $needle, which is unexpected.";
			}
			$this->throwError( $message );
		}
	}
	
	/**
	 * Asserts that a haystack that is stored in a static attribute of a class
	 * or an attribute of an object does not contain a needle.
	 *
	 * @since 9.5.0
	 * @param integer $needle
	 * @param string  $haystackAttributeName
	 * @param mixed   $haystackClassOrObject
	 * @param string  $message
	 * @throws BizException if haystack does contain $needle
	 */
	function assertAttributeNotContains( $needle, $haystackAttributeName, $haystackClassOrObject, $message = '' )
	{
		if( property_exists( $haystackClassOrObject, $haystackAttributeName ) ) {
			$this->assertNotContains( $needle, $haystackClassOrObject->$haystackAttributeName, $message );
		} else {
			$this->throwError( "Object $haystackClassOrObject has no property $haystackAttributeName." );
		}
	}
	
	/**
	 * Asserts the number of elements of an array.
	 *
	 * @since 9.5.0
	 * @param integer $expectedCount
	 * @param mixed   $haystack
	 * @param string  $message
	 * @throws BizException if $haystack does not contain $expectedCount elements
	 */
	public function assertCount( $expectedCount, $haystack, $message = '' )
	{
		$actualCount = count( $haystack );
		if( $actualCount != $expectedCount ) {
			if( !$message ) {
				$message = "The haystack contains $actualCount elements, but expected is $expectedCount elements.";
			}
			$this->throwError( $message );
		}
	}
	
	/**
	 * Asserts the number of elements of an array, Countable or Traversable
	 * that is stored in an attribute.
	 *
	 * @since 9.5.0
	 * @param integer $expectedCount
	 * @param string  $haystackAttributeName
	 * @param mixed   $haystackClassOrObject
	 * @param string  $message
	 * @throws BizException if haystack does not contain $expectedCount elements
	 */
	function assertAttributeCount( $expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '' )
	{
		if( property_exists( $haystackClassOrObject, $haystackAttributeName ) ) {
			$this->assertCount( $expectedCount, $haystackClassOrObject->$haystackAttributeName, $message );
		} else {
			$this->throwError( "Object $haystackClassOrObject has no property $haystackAttributeName." );
		}
	}	
	
	/**
	 * Asserts the number of elements of an array.
	 *
	 * @since 9.5.0
	 * @param integer $expectedCount
	 * @param mixed   $haystack
	 * @param string  $message
	 * @throws BizException if $haystack contains $expectedCount elements
	 */
	public function assertNotCount( $expectedCount, $haystack, $message = '' )
	{
		$actualCount = count( $haystack );
		if( $actualCount == $expectedCount ) {
			if( !$message ) {
				$message = "The haystack contains $actualCount elements, which is unexpected.";
			}
			$this->throwError( $message );
		}
	}
	
	/**
	 * Asserts the number of elements of an array, Countable or Traversable
	 * that is stored in an attribute.
	 *
	 * @since 9.5.0
	 * @param integer $expectedCount
	 * @param string  $haystackAttributeName
	 * @param mixed   $haystackClassOrObject
	 * @param string  $message
	 * @throws BizException if haystack does contains $expectedCount elements
	 */
	function assertAttributeNotCount( $expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '' )
	{
		if( property_exists( $haystackClassOrObject, $haystackAttributeName ) ) {
			$this->assertNotCount( $expectedCount, $haystackClassOrObject->$haystackAttributeName, $message );
		} else {
			$this->throwError( "Object $haystackClassOrObject has no property $haystackAttributeName." );
		}
	}	
	
	/**
	 * Asserts that two variables are equal.
	 *
	 * @since 9.5.0
	 * @param mixed  $expected
	 * @param mixed  $actual
	 * @param string $message
	 * @throws BizException if $actual is not equal with $expected
	 */	
	public function assertEquals( $expected, $actual, $message = '' )
	{
		if( $expected != $actual ) {
			if( !$message ) {
				$message = "The expected value $expected does not equals the actual value $actual, which is unexpected.";
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a variable is equal to an attribute of an object.
	 *
	 * @since 9.5.0
	 * @param string $expected
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $classOrObject->$attributeName is not greater than $expected
	 */
	public function assertAttributeEquals( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertEquals( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}

	/**
	 * Asserts that two variables are not equal.
	 *
	 * @since 9.5.0
	 * @param mixed  $expected
	 * @param mixed  $actual
	 * @param string $message
	 * @throws BizException if $actual equals with $expected
	 */	
	public function assertNotEquals( $expected, $actual, $message = '' )
	{
		if( $expected == $actual ) { // this check was fixed in EN-89534
			if( !$message ) {
				$message = "The expected value $expected equals the actual value $actual, which is unexpected.";
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a variable is not equal to an attribute of an object.
	 *
	 * @since 9.5.0
	 * @param string $expected
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $classOrObject->$attributeName equals with $expected
	 */
	public function assertAttributeNotEquals( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertNotEquals( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}

	/**
	 * Asserts that a value is greater than another value.
	 *
	 * @since 9.5.0
	 * @param mixed  $expected
	 * @param mixed  $actual
	 * @param string $message
	 * @throws BizException if $actual is not greater than $expected
	 */
	function assertGreaterThan( $expected, $actual, $message = '' )
	{
		if( !($actual > $expected) ) {
			if( !$message ) {
				$message = "The value $actual is not greater than $expected, which is unexpected.";
			}
			$this->throwError( $message );
		}
	}
	
	/**
	 * Asserts that an attribute value is greater than another value.
	 *
	 * @since 9.5.0
	 * @param string $expected
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $classOrObject->$attributeName is not greater than $expected
	 */
	public function assertAttributeGreaterThan( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertGreaterThan( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}

	/**
	 * Asserts that a value is greater than or equal to another value.
	 *
	 * @since 9.5.0
	 * @param mixed  $expected
	 * @param mixed  $actual
	 * @param string $message
	 * @throws BizException if $actual is not greater or equal than $expected
	 */
	function assertGreaterThanOrEqual( $expected, $actual, $message = '' )
	{
		if( !($actual >= $expected) ) {
			if( !$message ) {
				$message = "The value $actual is not greater than nor equal to $expected, which is unexpected.";
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that an attribute value is greater than or equal to another value.
	 *
	 * @since 9.5.0
	 * @param string $expected Expected type name.
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $classOrObject->$attributeName is not greater or equal than $expected
	 */
	public function assertAttributeGreaterThanOrEqual( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertGreaterThanOrEqual( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}

	/**
	 * Asserts that a variable is of a given type.
	 *
	 * @since 9.5.0
	 * @param string $expected Expected class name.
	 * @param mixed  $actual Object to be type checked.
	 * @param string $message
	 * @throws BizException if $actual is no instance of $expected
	 */
	public function assertInstanceOf( $expected, $actual, $message = '' )
	{
		if( is_object( $actual ) ) {
			$actualClass = get_class( $actual );
			if( $actualClass != $expected ) {
				if( !$message ) {
					$message = "Expected object class is $expected but actual is $actualClass.";
				}
				$this->throwError( $message );
			}
		} else {
			if( !$message ) {
				$message = "Expected object class is $expected but actual is not an object.";
			}
			$this->throwError( $message );
		}
	}
	
	/**
	 * Asserts that a variable is not of a given type.
	 *
	 * @since 9.5.0
	 * @param string $expected Expected class name.
	 * @param mixed  $actual Object to be type checked.
	 * @param string $message
	 * @throws BizException if $actual is an instance of $expected
	 */
	public function assertNotInstanceOf( $expected, $actual, $message = '' )
	{
		if( is_object( $actual ) ) {
			$actualClass = get_class( $actual );
			if( $actualClass == $expected ) {
				if( !$message ) {
					$message = "Expected object class is not $expected but the actual is.";
				}
				$this->throwError( $message );
			}
		}
	}
	
	/**
	 * Asserts that a variable is of a given type.
	 *
	 * @since 9.5.0
	 * @param string $expected Expected type name.
	 * @param mixed  $actual Primitive data to be type checked.
	 * @param string $message
	 * @throws BizException if $actual is not of $expected type.
	 */
	public function assertInternalType( $expected, $actual, $message = '' )
	{
		$actualType = gettype( $actual );
		if( $actualType != $expected ) {
			if( !$message ) {
				$message = "Expected type is $expected but the actual is $actualType.";
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that an attribute is of a given type.
	 *
	 * @since 9.5.0
	 * @param string $expected Expected type name.
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $actual is not of $expected type.
	 */
	public function assertAttributeInternalType( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertInternalType( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}

	/**
	 * Asserts that a variable is not of a given type.
	 *
	 * @since 9.5.0
	 * @param string $expected Expected type name.
	 * @param mixed  $actual Primitive data to be type checked.
	 * @param string $message
	 * @throws BizException if $actual is of $expected type.
	 */
	public function assertNotInternalType( $expected, $actual, $message = '' )
	{
		$actualType = gettype( $actual );
		if( $actualType == $expected ) {
			if( !$message ) {
				$message = "Expected type is not $expected but the actual is.";
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that an attribute is not of a given type.
	 *
	 * @since 9.5.0
	 * @param string $expected Expected type name.
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $actual is of $expected type.
	 */
	public function assertAttributeNotInternalType( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertNotInternalType( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}

	/**
	 * Asserts that a value is smaller than another value.
	 *
	 * @since 9.5.0
	 * @param mixed  $expected
	 * @param mixed  $actual
	 * @param string $message
	 * @throws BizException if $actual is not less than $expected
	 */
	function assertLessThan( $expected, $actual, $message = '' )
	{
		if( !($actual < $expected) ) {
			if( !$message ) {
				$message = "The value $actual is not less than $expected, which is unexpected.";;
			}
			$this->throwError( $message );
		}
	}
	
	/**
	 * Asserts that an attribute is smaller than another value.
	 *
	 * @since 9.5.0
	 * @param string $expected
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $classOrObject->$attributeName is not less than $expected
	 */
	public function assertAttributeLessThan( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertLessThan( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}

	/**
	 * Asserts that a value is smaller than or equal to another value.
	 *
	 * @since 9.5.0
	 * @param mixed  $expected
	 * @param mixed  $actual
	 * @param string $message
	 * @throws BizException if $actual is not less or equal than $expected
	 */
	function assertLessThanOrEqual( $expected, $actual, $message = '' )
	{
		if( !($actual <= $expected) ) {
			if( !$message ) {
				$message = "The value $actual is not less than nor equal to $expected, which is unexpected.";;
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that an attribute is smaller than or equal to another value.
	 *
	 * @since 9.5.0
	 * @param string $expected
	 * @param string $attributeName
	 * @param mixed  $classOrObject
	 * @param string $message
	 * @throws BizException if $classOrObject->$attributeName is not less than nor equal to $expected
	 */
	public function assertAttributeLessThanOrEqual( $expected, $attributeName, $classOrObject, $message = '' )
	{
		if( property_exists( $classOrObject, $attributeName ) ) {
			$this->assertLessThanOrEqual( $expected, $classOrObject->$attributeName, $message );
		} else {
			$this->throwError( "Object $classOrObject has no property $attributeName." );
		}
	}
	
	/**
	 * Asserts that a variable is NULL.
	 *
	 * @since 9.5.0
	 * @param mixed  $actual Data to be checked.
	 * @param string $message
	 * @throws BizException if $actual is nol null.
	 */
	public function assertNull( $actual, $message = '' )
	{
		if( !is_null( $actual ) ) {
			if( !$message ) {
				$message = 'Data expected to be null, but the actual is set.';
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a variable is not NULL.
	 *
	 * @since 9.5.0
	 * @param mixed  $actual Data to be checked.
	 * @param string $message
	 * @throws BizException if $actual is null.
	 */
	public function assertNotNull( $actual, $message = '' )
	{
		if( is_null( $actual ) ) {
			if( !$message ) {
				$message = 'Data expected not to be null, but the actual is null.';
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a condition is true.
	 *
	 * @since 9.5.0
	 * @param bool   $condition
	 * @param string $message
	 * @throws BizException if $condition is not true.
	 */
	public function assertTrue( $condition, $message = '' )
	{
		if( $condition !== true ) {
			if( !$message ) {
				$message = 'Condition expected to be true, which is not the case.';
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a condition is not true.
	 *
	 * @since 9.5.0
	 * @param bool   $condition
	 * @param string $message
	 * @throws BizException if $condition is true.
	 */
	public function assertNotTrue( $condition, $message = '' )
	{
		if( $condition === true ) {
			if( !$message ) {
				$message = 'Condition expected to be not true, which is not the case.';
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a condition is false.
	 *
	 * @since 9.5.0
	 * @param bool   $condition
	 * @param string $message
	 * @throws BizException if $condition is not false.
	 */
	public function assertFalse( $condition, $message = '' )
	{
		if( $condition !== false ) {
			if( !$message ) {
				$message = 'Condition expected to be false, which is not the case.';
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Asserts that a condition is not false.
	 *
	 * @since 9.5.0
	 * @param bool   $condition
	 * @param string $message
	 * @throws BizException if $condition is false.
	 */
	public function assertNotFalse( $condition, $message = '' )
	{
		if( $condition === false ) {
			if( !$message ) {
				$message = 'Condition expected to be not false, which is not the case.';
			}
			$this->throwError( $message );
		}
	}

	/**
	 * Helper function that checks whether a callback function throws BizException with a specific server error code.
	 *
	 * @since 10.2.0
	 * @param string $expectedErrorCode The expected server error code (S-code)
	 * @param callable $callback Function to be called (e.g. closure) for which the exception is expected
	 */
	public function assertBizException( $expectedErrorCode, callable $callback )
	{
		$map = new BizExceptionSeverityMap( array( $expectedErrorCode => 'INFO' ) );
		$e = null;
		try {
			call_user_func( $callback );
		} catch( BizException $e ) {
		}
		$this->assertInstanceOf( 'BizException', $e );
		$this->assertEquals( $expectedErrorCode, $e->getErrorCode() );
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
