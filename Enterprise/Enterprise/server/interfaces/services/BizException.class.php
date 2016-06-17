<?php
/**
 * Exception class thrown by the Business Services layer.<br>
 * This extends the standard Exception class with:
 * - Error type, who is making a mistake: 'Client' or 'Server'
 * - Detail, more info about the error
 * - MessageKey, unique error message key
 * apart from these we also use the message of the standard Exception
 * we do not use the code property of the standard Exception which is an integer
 * 
 * @package Enterprise
 * @subpackage BizServices
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class BizException extends Exception
{
	/**
	 * @var string type of exception: 'Client', 'Server'
	 */
	private $Type;

	/**
	 * @var string error detail or code specifying the error
	 */
	private $Detail;

	/**
	 * @var string unique error message key
	 */
	private $MessageKey;

	/**
	 * @var string Severity of exception (shown to the end user)
	 */
	private $Severity;

	/**
	 * @var string Severity of exception (log level)
	 */
	private $LogSeverity;

	/**
	 * @var boolean rollback database transaction
	 */
	private $Rollback;
	
	/**
	 * @var array Severity mapping used to adjust the severity of messages before they get logged. See also {BizExceptionSeverityMap}.
	 */
	private static $mapStack = array();
	
	/**
	 * Construct the BizException
	 *
	 * @param string 	$messageKey   Error message key (for automatic localization)
	 * @param string 	$type         Error type: 'Client' or 'Server'
	 * @param string 	$detail       Error detail of code specifying the error
	 * @param string 	$message      Localized message (optional). When given, it overrules automatic localization of $messageKey param.
	 * @param array  	$params       List of parameters to fill in resource string during localization.
	 * @param string    $severity     Severity / log level, such as 'ERROR', 'WARN', etc. See LogHandler for accepted values.
	 * @param boolean	$rollback	  Rollback database transaction. 
	 */
	public function __construct( $messageKey, $type, $detail='', $message=null, $params=null, $severity=null, $rollback=true )
	{
		// Determine log level (severity)
		if( is_null($severity) ) {
			// Note: Letting caller explicitly define the severity is preferred!
			//       The below is a quick hack to flatten hundreds of callees to ERROR/WARN level.
			
			// Determine the log level and exception type.
			$isServerOrClient = strcasecmp( $type, 'client' ) == 0 || strcasecmp( $type, 'server' ) == 0;
			if( $messageKey && $isServerOrClient ) { 
				// Consider some frequently used keys that are defenitely programming errors as serious
				// These errors will show up as errors in the server logs, the rest of the errors will show as warnings in the logs
				// E.g. An object locked error is a error for the end user but a warning in the server logs for the system administrators.
				$this->LogSeverity = in_array( $messageKey, array( 'ERR_DATABASE', 'ERR_ARGUMENT', 'ERR_ERROR' ) ) ? 'ERROR' : 'WARN';
				// Every BizException is an error for the end user. The process actually stops. So in the Error report these
				// exceptions will show up as errors.
				$this->Severity = 'ERROR';
			} else {
				// There are cases when the type parameter is used wrongly. Then it doesn't contain server or client
				// but the level (e.g. WARN or ERROR).
				$this->Severity = $isServerOrClient ? 'ERROR' : $type; // Default report level is ERROR for server or client exceptions without a message key
				$this->LogSeverity = $isServerOrClient ? 'WARN' : $type; // Default log level is WARN for server or client exceptions without a message key
				$type = 'Server'; // assume this is a server issue
			}			
		} else { // preferred way
			$this->Severity = $severity;
			$this->LogSeverity = $severity;
		}
		
		// Localize message key when given.
		if( is_null( $message ) ) {
			require_once BASEDIR.'/server/secure.php';
			$this->message = BizResources::localize( $messageKey, true, $params );
		}  else {
			$this->message = $message;
		}
		
		// Check if any of the callers on the stack know the severity better than
		// the one throwing the exception. If so, adjust the severity accordingly.
		$code = $this->getErrorCodeFromMessage( $this->message );
		$codeOrMsg = $code ? $code : ($messageKey ? $messageKey : $this->message); // prefer S-code, then message key, then message text
		foreach( self::$mapStack as $severities ) {
			if( array_key_exists( $codeOrMsg, $severities ) ) {
				$this->LogSeverity = $severities[$codeOrMsg];
				break; // let the oldest caller win; he knows the best!
			}
		}

		// Log the message.
		$logMessage = "Message: {$this->message}\nMessageKey: $messageKey\nType: $type\nDetail: $detail";

		// Log the exception (in case of ERROR/WARN; log includes stack dump)
		LogHandler::Log( 'BizException', $this->LogSeverity, $logMessage );

		// Take over given param
		$this->Type = $type;
		$this->Detail = $detail;
		$this->MessageKey = $messageKey;
		$this->Rollback = $rollback;
		 // Parent class handles message
		parent::__construct($this->message);
	}

	/**
	 * Returns type of error: 'Client' or 'Server'.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->Type;
	}

	/**
	 * Returns error details (or specific error code).
	 *
	 * @return string
	 */
	public function getDetail()
	{
		return $this->Detail;
	}

	/**
	 * Returns error message key.
	 *
	 * @return string
	 */
	public function getMessageKey()
	{
		return $this->MessageKey;
	}
	
	/**
	 * Returns the severity of the exception.
	 *
	 * @return string
	 */
	public function getSeverity()
	{
		return $this->Severity;
	}

	/**
	 * Returns the log severity of the exception.
	 *
	 * @return string
	 */
	public function getLogSeverity()
	{
		return $this->LogSeverity;
	}

	/**
	 * Returns if a database transaction must be canceled (rollback).
	 *
	 * @return boolean
	 */
	public function getRollback()
	{
		return $this->Rollback;
	}
	
	/**
	 * 'Extracts' error code (S-code) which is in the form of S(xxxx) from a given message.
	 * When there's no Scode in the errorMessage, empty string return.
	 *
	 * @param string $message Localized message that might contain the S-code
	 * @return string sCode. E.g: S1014
	 */
	private function getErrorCodeFromMessage( $message )
	{
		$sCodes = array();
		preg_match_all( '/\((S[0-9]+)\)/', $message, $sCodes); //grab S(xxxx) error code (S-code) from Exception::getMessage()
		// There should be only one S-code, but when many, take last one since those codes are at the end of message (=rule). 
		$sCode = count($sCodes[1]) > 0 ? $sCodes[1][count($sCodes[1])-1] : ''; 
		return $sCode;
	}
	
	/**
	 * 'Extracts' error code (S-code) which is in the form of S(xxxx) from Exception::getMessage()
	 * When there's no Scode in the errorMessage, empty string return.
	 *
	 * @return string sCode. E.g: S1014
	 */
	public function getErrorCode()
	{
		return $this->getErrorCodeFromMessage( $this->getMessage() );
	}
	
	/**
	 * Registers a BizExceptionSeverityMap. 
	 * Do not call this function; It is for for internal usage only.
	 *
	 * @param integer $id Map id.
	 * @param array $severities Keys = S-codes, Values = severities (INFO, WARN, ERROR)
	 */
	static public function registerSeverityMap( $id, array $severities )
	{
		self::$mapStack[ $id ] = $severities;
	}
	
	/**
	 * Unregisters a BizExceptionSeverityMap. 
	 * Do not call this function; It is for for internal usage only.
	 *
	 * @param integer $id Map id.
	 */
	static public function unregisterSeverityMap( $id )
	{
		if( array_key_exists( $id, self::$mapStack ) ) {
			unset( self::$mapStack[ $id ] );
		} else {
			LogHandler::Log( 'BizException', 'ERROR', 
				'unregisterSeverityMap() called with severity map id that does not exists: '.$id );
		}
	}
}

/**
 * Severity mapper for the BizException class. This is to define a severity for a certain
 * server error code (S-code). This only affects the serverity being logged to server log.
 *
 * It is seen quite often that the function throwing an exception does not know what is
 * the 'correct' severity, but the caller does. For example, a SQL function that is requested
 * to insert a record, which fails, will throw an ERROR (because the whole function can not
 * do what has been asked), while the caller does this simply it to detect whether or not
 * a workflow object could be locked. In case the caller has a good code path for both
 * cases (success and failure) it actually wants this to be logged as an INFO to avoid
 * panic for nothing for system admin users reading the server log.
 * 
 * The following example shows how set the severity to "INFO" for the "S5000" error
 * thrown by another function:
 *
 *  public function Foo()
 *  {
 *		$map = new BizExceptionSeverityMap( array( 'S5000' => 'INFO' ) );
 *		$map = $map; // keep code analyzer happy
 *		try {
 *			... // call function that might throw BizException
 *		} catch( BizException $e ) {
 *			...
 *		}
 *  } // => here the destructor of BizExceptionSeverityMap is called !
 *
 * There is a need to put the mapper into a variable (e.g. $map) because when it
 * outruns the function scope, it's destructor gets called and so the severity map
 * gets automatically unregistered. This is by design; the life span of a map is the
 * function call itself. When the function is popped of the stack, the map is gone too.
 *
 * In case a function has two try-catch segments after each other, please put an
 * unset( $map ) in between to force the severity map being destroyed and to avoid
 * it to get applied for the second try-catch unintendedly.
 *
 * IMPORTANT: If there are severity maps on the calling stack, each defining a severity
 * for the -same- error (S-code), the most outer caller (deepest at stack) wins.
 * This is because that function has the greatest overview of what functionality is
 * intended.
 *
 * When the expected error does not contain an error code, use the error message key instead:
 *   public function Foo()
 *   {
 *      $map = new BizExceptionSeverityMap( array( 'ERR_ASSIGN_OBJECT_LABELS' => 'INFO' ) );
 *      ...
 *
 */
class BizExceptionSeverityMap
{
	private static $maxId = 0;
	private $id;
	private $severities;
	
	public function __construct( array $severities )
	{
		self::$maxId += 1;
		$this->id = self::$maxId;
		$this->severities = $severities;
		BizException::registerSeverityMap( $this->id, $severities );
	}
	
	public function __destruct()
	{
		BizException::unregisterSeverityMap( $this->id );
	}
	
	public function getId()
	{
		return $this->id;
	}

	public function getSeverities()
	{
		return $this->severities;
	}
}
