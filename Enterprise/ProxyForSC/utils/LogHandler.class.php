<?php
/**
 * Log handler class.
 *
 * Does all system logging for the server while handling web applications and web services.
 * When PHP raises exception or causes internal errors, those are intercepted and logged here as well.
 * Under the OUTPUTDIRECTORY folder, it creates a <YYMMDD> subfolder where under a client IP subfolder is created.
 * Inside, it does logging for that client calling. The DEBUGLEVELS option tells the granularity of logs made (per client IP).
 * 
 * @package    ProxyForSC
 * @subpackage Utils
 * @since      v1.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class LogHandler
{
	private static $logLevels = array(
		'NONE' 	=> 0,         // Suppress all logging.
		'CONTEXT' => 1,       // Indicates the calling context. NOT directly logged; Logged on first best 'normal' log.
		'ERROR' => 2,         // Exceptional error. Stops execution in most cases.
		'WARN' 	=> 3,         // Warning. Should NOT happen. Recommened option for production.
		'INFO' 	=> 4,         // Bus stop. Operation succesfull or found/determined details.
		'DEBUG' => 5,         // For debugging purposes only. Should not be used in production.
		'NOTINSTALLED' => 6,	// feature is not installed, used in WWTest
	);
	private static $logged = false;
	private static $context = '';
	private static $wroteContext = false;
	private static $caller = null;    // Service (or PHP module) calling server.
	private static $logFolder = null; // Directory used for (client specific) logging.
	private static $logFile = null;   // Current log file used to write logging into.
	private static $clientIP = null;  // Client machine (IP) calling the server.
	private static $debugLevel = null;// Debug level configured for calling client.

	/**
	 * Constructor, made private to avoid creating instances.
	 */
	private function __construct() {}

	/**
	 * Copy constructor, made private to avoid creating instances.
	 */
	private function __clone() {}

	/**
	 * Initialization that should be called only once per session.
	 * Intercepts PHP error- and exception handling to write to Enterprise log files.
	 */
	public static function init()
	{
		// Intercept PHP error- and exception handling to write to Enterprise log files.
		set_error_handler( array(__CLASS__, 'phpErrorHandler') );
		set_exception_handler( array(__CLASS__, 'phpExceptionHandler') );

		// By default, use the PHP module name. Later it can be overruled by service name.
		self::$caller = basename($_SERVER['SCRIPT_NAME']);
		self::$caller = str_replace( '.', '_', self::$caller );

		// Determine the client IP calling us
		self::$clientIP = self::getClientIP();

		// Determine the debug level configured for the client calling us.
		// Fallback to NONE when the whole logging feature is disabled.
		if( OUTPUTDIRECTORY == '' ) { // logging disabled
			self::$debugLevel = 'NONE';
		} else { // logging possible enabled
			$debugLevels = unserialize( DEBUGLEVELS );
			if( isset($debugLevels[self::$clientIP]) ) { // log level configured for this client IP ?
				self::$debugLevel = $debugLevels[self::$clientIP];
			} else { // fall back to default
				self::$debugLevel = $debugLevels['default']; // 'default' is a mandatory key checked by wwtest
			}
		}
	}

	/**
	 * Tells if the (configured) log level is supported.
	 * Typically called by Health Check (wwtest) to determine if the DEBUGLEVELS option is correctly
	 * configured at the configserver.php file.
	 *
	 * @return boolean TRUE when supported, or FALSE if not.
	 */
	public static function isValidLogLevel( $level )
	{
		return array_key_exists( $level, self::$logLevels );
	}

	/**
	 * Class destructor, called by PHP when the LogHandler ends life time. This function moves 
	 * all log collected messages from the tmp log file to the 'real' server log file. The tmp
	 * file is removed afterwards.
	 *
	 * @since 7.5.0
	 */
	public function __destruct()
	{
		$handle = $this->openFile( 'sce_log_'.DBTYPE.'.htm', 'a+');
		if( $handle ) {
			$tmpFile = self::getTmpLogFilePath();
			if( file_exists( $tmpFile ) ) {
				fwrite( $handle, file_get_contents( $tmpFile ) );
				unlink( $tmpFile );
			}
		}
	}

	/**
	 * Tells if for the calling client DEBUG mode is configured at DEBUGLEVELS option in configserver.php.
	 * It also checks if OUTPUTDIRECTORY is configured, or else no logging will be done at all.
	 * 
	 * @return boolean Wether or not DEBUG logging is done.
	 */
	public static function debugMode()
	{
		return self::$debugLevel == 'DEBUG';
	}

	/**
	 * Returns the debug level configured for the calling client at DEBUGLEVELS option in configserver.php.
	 * Preferably use debugMode() function to check for DEBUG value.
	 * 
	 * @retun string Configured value, e.g. 'NONE', 'ERROR', 'WARN', etc.
	 */
	public static function getDebugLevel()
	{
		return self::$debugLevel;
	}

	/**
	 * Determines the log directory and creates path when not existing.
	 * The path is built upon configured OUTPUTDIRECTORY option under which a subfolder is
	 * created with current date. Underneath, there is another folder created with calling
	 * client IP address:
	 *    OUTPUTDIRECTORY / <date> / <client ip> /
	 *
	 * @return string Path of log folder. NULL when logging is disabled or when folder creation failed.
	 */
	public static function getLogFolder()
	{
		if( OUTPUTDIRECTORY == '' ) {
			return null; // logging disabled
		}

		if( is_null(self::$logFolder) ) {

			// Create YYMMDD subfolder (when missing)
			if( !is_dir( OUTPUTDIRECTORY ) ) { // BZ#23964
				// When OUTPUTDIRECTORY doesnt exists, just bail out:
				// It is an error as the OUTPUTDIRECTORY should exists and valid,
				// when it is not, it will be detected by the HealthCheck, no point throwing
				// BizException here as it will end up no valid folder to write the error.
				return null;
			}
	   		// Create client IP subfolder (when missing)
			self::$logFolder = OUTPUTDIRECTORY.date('Ymd').'/';
			$dayPhpInfoFile = self::$logFolder.'phpinfo.htm';
			self::$logFolder .= self::$clientIP.'/';
			if( !file_exists(self::$logFolder) ) {
				require_once BASEDIR.'/utils/FolderUtils.class.php';
				FolderUtils::mkFullDir( self::$logFolder );
				// Quit when folder creation failed to avoid caller to continue with bad paths.
				if( !file_exists(self::$logFolder) ) {
					// => do not nullify self::$logFolder here to avoid error over and over again
					return null; // folder creation failed
				}
			}
			// Check whether phpinfo.htm file exists at the 'day' folder, when exist, copy to the client IP folder.
			// When not exists, create a new one in the client IP folder and copy it to the parent 'day' folder.
			$ipPhpInfoFile = self::$logFolder.'phpinfo.htm';
			if( file_exists($dayPhpInfoFile) ) {
				if( !file_exists($ipPhpInfoFile) ) {
					copy( $dayPhpInfoFile, $ipPhpInfoFile );
				}
			} else {
				require_once BASEDIR.'/utils/PhpInfo.php';
				file_put_contents( $ipPhpInfoFile, WW_Utils_PhpInfo::getAllInfo() );
				copy( $ipPhpInfoFile, $dayPhpInfoFile );
			}
		}
		return self::$logFolder;
   	}

	/**
	 * Builds and returns a file header (single line) with column information that matches with 
	 * getLogLine() function. Supports plain and html formats.
	 * Called after creating a new log file.
	 *
	 * @return string Formatted header.
	 */
	private static function getLogFileHeader()
	{
		if( LOGFILE_FORMAT == 'plain' ) {
			$header = chr( 0xEF ) . chr( 0xBB ). chr( 0xBF ); // start with UTF-8 BOM marker to let editors recognize format
			$header .= sprintf( '%-25s %-8s %-15s %s'.PHP_EOL, 'TIME', 'LEVEL', 'AREA', 'MESSAGE' );
		} else { // html (or other?)
			require_once BASEDIR.'/utils/HtmlLog.class.php';
			$header = '<html>'.WW_Utils_HtmlLog::getHead( 'System Log' );
			$header .= '<table><tr><th>Time</th><th>Level</th><th>Area</th><th>Message</th></tr>'.PHP_EOL;
		}
		return $header;
	}

	/**
	 * Builds and returns a single line with given logging information.
	 * Supports plain and html formats.
	 * Called for every Log operation.
	 *
	 * @param string $area    Indication of caller. It is recomended to pass __CLASS__.
	 * @param string $level   Log level. Should be any value of self::$logLevels.
	 * @param string $message HTML formatted string to write to log file.
	 * @param string $time    Current time stamp of the logged message. Leave empty to auto calculate.
	 * @param string $logFile File name of the current log file. Use to refer from error log to current log.
	 * @return string Formatted log line.
	 */
	private static function getLogLine( $level, $area, $message, &$time, $logFile )
	{
		if( !$time ) {
			list($ms, $sec) = explode(" ", microtime()); // get seconds with ms part
			$msFmt = sprintf( '%03d', round( $ms*1000, 0 ));
			$time = date('Y-m-d H:i:s',$sec).'.'.$msFmt;
		}
		if( LOGFILE_FORMAT == 'plain' ) {
			$line = sprintf( '%-25s %-8s %-15s %s'.PHP_EOL, $time, $level, $area, $message );
		} else { // html (or other?)
// COMMENTED OUT: The original Enterprise defines cannot be used in the Proxy environment.
//			if( $logFile ) {
//				$logFileRef = SERVERURL_ROOT.INETROOT.'/server/admin/showlog.php?act=logfile'.
//								'&file='.basename( $logFile ).'#'.$time;
//				$reference = '<br/><a href="'.$logFileRef.'">Show full context</a>';
//			} else {
				$reference = '';
//			}
			$levColor = ($level == 'ERROR') ? '#ff0000' : (($level == 'WARN') ? '#ffaa00' : '#00cc00'); // red,orange,green
			$line = 
				'<tr class="d" id="'.$time.'">'.
					'<td><nobr>'.$time.'</nobr>'.$reference.'</td>'.
					'<td><font color="'.$levColor.'">'.$level.'</font></td>'.
					'<td>'.$area.'</td><td>'.nl2br($message).'</td>'.
				'</tr>'.PHP_EOL;
		}
		return $line;
	}

	/**
	 * Builds and returns a header (single line) with calling script name. 
	 * Supports plain and html formats.
	 * Called once per session (service request).
	 *
	 * @return string Formatted header.
	 */
	private static function getLogRequestHeader()
	{
		$script = strtolower($_SERVER['SCRIPT_NAME']);
		if( LOGFILE_FORMAT == 'plain' ) {
			$header = $script.':'.PHP_EOL;
		} else { // html (or other?)
			$header = '<tr class="h"><td colspan="4">'.$script.'</td></tr>'.PHP_EOL;
		}
		return $header;
	}

	/**
	 * Opens log file. Creates new one when not exists and inserts a table header for HTML logging.
	 *
	 * @return resource Log file handle or false in case of error.
	 */
	private static function openFile( $logFile, $opentype )
	{
		// Open or create the log file
		$exists = file_exists( $logFile );
		$handle = fopen( $logFile, $opentype );
		if( !is_resource($handle) ) {
			return false; // error
		}

   		// Just created this file / for first time use?
		if( !$exists ) {
			fwrite( $handle, self::getLogFileHeader() );
			// In debug mode, make sure the last log is written to file, so in case of an 
			// unexpected PHP crash, we know what happened right before that point.
			// For production, we do not like the penalty for the delay.
			if( self::$debugLevel == 'DEBUG' ) {
				fflush( $handle );
			}
		}
		return $handle;
   	}

	/**
	 * Returns the contents of the log file.
	 *
	 * @return string
	 */
	public static function getLog()
	{
		$logFile = self::getLogFile();
		return empty($logFile) ? '' : file_get_contents( $logFile );
	}

	/**
	 * Returns the full file path of the log file.
	 * When folder does not exists, it gets created. When file does not exist, it is NOT created.
	 *
	 * @return string File path
	 */
	public static function getLogFile()
	{
		// Directly return log file when determined before
		if( !is_null(self::$logFile) ) {
			return self::$logFile; // determined before
		}

		// Determine log folder or quit when logging is disabled.		
		$logFolder = self::getLogFolder();
		if( is_null($logFolder) ) {
			return ''; // logging disabled
		}

   		// Determine log file and return to caller
		$microtime = explode(" ", microtime());
		$time = sprintf( '%03d', round($microtime[0]*1000) );
		$fileExt = (LOGFILE_FORMAT == 'plain') ? '.txt' : '.htm';
		self::$logFile = $logFolder.date('His').'_'.$time.'_'.self::$caller.$fileExt;
		return self::$logFile;
	}

	/**
	 * Returns the full file path of the php.log file. This is used for fatal errors.
	 *
	 * @return string File path
	 */
	public static function getPhpLogFile()
	{
		$logFolder = self::getLogFolder();
		if( !is_null($logFolder) ) {
			$logFile = $logFolder.'php.log';
			if( file_exists( $logFile ) ) {
				return $logFile;
			}
		}
		return '';
	}
	
	/**
	 * Dumps the entire content of a given PHP object into a new temp file at server log folder.
	 * There are several methods supported to dump the PHP object.
	 *
	 * @param stdClass $phpObject The PHP object to dump.
	 * @param string $method Support values: 'var_dump', 'var_export', 'serialize' or 'print_r'. See PHP manual.
	 * @param string $postfix Additional string to add to temp file name.
	 * @return string The create file path.
	 */
	public static function logPhpObject( $phpObject = null, $method = 'var_dump', $postfix = '' )
	{
		// Determine log folder or quit when logging is disabled.		
		$logFolder = self::getLogFolder();
		if( is_null($logFolder) ) {
			return ''; // logging disabled
		}
		
		// Dump the PHP object data in memory.
		switch( $method ) {
			case 'var_dump':
				ob_start();
				var_dump( $phpObject );
				$content = ob_get_contents();
				ob_end_clean();
				break;
			case 'var_export':
				$content = var_export( $phpObject, true );
				break;
			case 'serialize':
				$content = serialize( $phpObject );
				break;
			case 'json_encode':
				$content = json_encode( $phpObject, JSON_PRETTY_PRINT );
				break;
			case 'print_r':
			default:
				$content = print_r( $phpObject, true );
				break;
		}

		// Determine unique file name at logging folder. When calling within the same milisecond,
		// increase an internal counter. This counter is converted to [a...z] and later to the ms.

		$microTime = explode( ' ', microtime() );
		$time = sprintf( '%03d', round($microTime[0]*1000) );
		$currTimeStamp = date('His').'_'.$time;

		static $callsCount;
		static $lastTimeStamp;
		if( $lastTimeStamp == $currTimeStamp ) {
			$callsCount++;
		} else {
			$callsCount = 0;
		}
		$lastTimeStamp = $currTimeStamp;
		$callerId = chr( $callsCount + 65 );

		$objName = is_object( $phpObject ) ? get_class( $phpObject ) : gettype( $phpObject );
		$phpLogFile = $logFolder.$currTimeStamp.$callerId.'_'.$method.'_'.$objName;
		if( $postfix ) {
			$phpLogFile .= '_'.$postfix;
		}
		$phpLogFile .= '.txt';

		// Write the dumped PHP object data and return file path to caller.
		file_put_contents( $phpLogFile, $content );
		return $phpLogFile;
	}

	/**
	 * Writes one log entry to the log file.
	 * Use debugMode() function in case you want to write large amount of data, typically
	 * through print_r($data,true). See file header of this module how to do such.
	 *
	 * @param string $area    Indication of caller. It is recomended to pass __CLASS__.
	 * @param string $level   Log level. Should be any value of self::$logLevels.
	 * @param string $message HTML formatted string to write to log file.
	 * @param Exception|null $exception Optionally, provide a caught exception to log all details for as well.
	 */
	public static function Log( $area, $level, $message, $exception=null )
	{
		// Quit when logging feature is disabled
		if( OUTPUTDIRECTORY == '' || self::$debugLevel == 'NONE' ) {
			return;
		}
   		
		// Quit when given log level is too detailed (compared to configured level by system admin)
		if( self::$logLevels[self::$debugLevel] < self::$logLevels[$level] ) {
			return;
		}
   		// Suppress logging for frequently polling services (except when there are errors or warnings).
		if( $level != 'ERROR' && $level != 'WARN' && self::suppressLoggingForService() ) {
			return;
		}

		// Insert header with calling script name, only for first log of this session
		if(self::$logged) {
			$header = '' ;
		} else  {
			$header = self::getLogRequestHeader();
			self::$logged = true;
		}
		$msg = $header;

		// Add calling stack information in debug mode for errors and warnings.
		$isDebugWarnError = (self::$debugLevel == 'DEBUG' && ($level == 'ERROR' || $level == 'WARN'));
		if( $isDebugWarnError ) {
			$message .= "\nStack:\n".self::getDebugBackTrace();
			if( $exception ) {
				$message .= "\n-------------\nException Message: ".$exception->getMessage();
				if( get_class($exception) == '' ) {
					$message .= "\nException Details: ".$exception->getDetail();
				}
				$message .= "\nException Stack:\n".self::getExceptionBackTrace( $exception );
			}
		}

		// Format message to log
		$time = '';
		$msg .= self::getLogLine( $level, $area, $message, $time, null );

		// Don't log CONTEXT messages in ERROR/WARN mode, so cache them (and log them at first best ERROR/WARN)
		if( $level == 'CONTEXT' && !self::$wroteContext ) {
			self::$context .= $msg; // cache context to log later (on first best 'normal' log request)
			return; // see you next time
		}

		// Insert cached context when not written before
		if( !empty(self::$context) && !self::$wroteContext ) {
			$msg = self::$context.$msg;
			self::$wroteContext = true;
			self::$context = '';
		}

		// Write log to file
		$logFile = self::getLogFile();
		if( !empty($logFile) ) {
			$handle = self::openFile( $logFile, 'a+' );
			if( $handle ) {
				fwrite( $handle, $msg );
				// In debug mode, make sure the last log is written to file, so in case of an 
				// unexpected PHP crash, we know what happened right before that point.
				// For production, we do not like the penalty for the delay.
				if( self::$debugLevel == 'DEBUG' ) {
					fflush( $handle );
				}
			}
		}

		// For debugging convenience, collect errors and warning to higher level error log file
		if( $isDebugWarnError ) {
			$logFolder = self::getLogFolder();
			if( !empty($logFolder) ) {
				$fileExt = (LOGFILE_FORMAT == 'plain') ? '.txt' : '.htm';
				$handle = self::openFile( $logFolder.'ent_log_errors'.$fileExt, 'a+', false );
				if( $handle ) {
					$errMsg = $header.self::getLogLine( $level, $area, $message, $time, $logFile );
					fwrite( $handle, $errMsg );
					// In debug mode, make sure the last log is written to file, so in case of an 
					// unexpected PHP crash, we know what happened right before that point.
					// For production, we do not like the penalty for the delay.
					if( self::$debugLevel == 'DEBUG' ) {
						fflush( $handle );
					}
				}
			}
		}
	}

	/**
	 * Some frequently called polling services would blur the server log files.
	 * For example CS is calling the OperationProgress service every second during publishing.
	 * Therefore all DEBUG and INFO logging for this request needs to be suppressed. 
	 * Pass in a NULL for $serviceName to depend on the active service being handled.
	 *
	 * Exception: The SOAP Server is unpacking the arrived message and therefore has a 'raw' 
	 * service name. The 'raw' name is slightly different than the 'official' service name. 
	 * That name is the same as the class name of the service and is resolved right after, 
	 * as soon as the service layer is hit. In this case the 'raw' service name should be
	 * passed in for the $serviceName parameter.
	 *
	 * @param string|null $serviceName NULL to check active service. Else provide 'raw' service name.
	 * @return bool Whether or not logging should be suppressed for the active/given service.
	 */
	public static function suppressLoggingForService( $serviceName = null )
	{
		// @TODO: Do we need this function in Proxy?
		return false;
	}

	/**
	  * Gives the location of the error file that contains log errors only.
	  * When there is no error logged, the returned path is empty!
	  * Only used in debug mode to quickly check and report errors, typically used by developers.
	  * So, empty means, there are no errors or not running in DEBUG mode.
	  *
	  * @return string Full file path to error log file. Empty when no errors logged.
	  */
	public static function getDebugErrorLogFile()
	{
		$logFile = '';
		if( self::$debugLevel == 'DEBUG' ) {
			$logFolder = self::getLogFolder();
			if( !empty($logFolder) ) {
				$fileExt = (LOGFILE_FORMAT == 'plain') ? '.txt' : '.htm';
				$logFile = $logFolder.PRODUCT_NAME_SHORT.'_errors'.$fileExt;
				if( !file_exists( $logFile ) )  {
					$logFile = ''; // no file, no errors => return empty
				}
			}
		}
		return $logFile;
	}

	/**
	 * PHP exception handler as installed by the init() function. 
	 * It gets called by PHP whenever a thrown exception was not catched.
	 * Logs the exception's message to log file.
	 *
	 * @param Exception $exception
	 */
	public static function phpExceptionHandler( $exception )
	{
		$error = 'Uncaught "'.get_class($exception).'" exception.';
		LogHandler::Log( 'errorhandler', 'ERROR', $error, $exception );
	}

	/**
	 * PHP error handler as installed by the init() function. 
	 * It gets called by PHP whenever an internal error/warning/notice occurs.
	 * Logs the error message to log file.
	 *
	 * @param integer $errno
	 * @param string  $errmsg
	 * @param string  $file
	 * @param integer $line
	 * @param boolean $debug
	 */
	public static function phpErrorHandler( $errno, $errmsg, $file, $line, $debug )
	{
		$debug = $debug; // keep analyzer happy

		// When the error handler catches the error, the @ puts silently error_reporting level to 0,
		// so you can detect errors comming from 'arobased' instructions.
		if( error_reporting() == 0 ) {
			return;
		}

		// handle error
		if ($errno == E_STRICT){
			self::Log('errorhandler', 'WARN', 'Strict: '.$file.' '.$line.' '.$errmsg);
		}else if($errno == E_NOTICE){
			self::Log('errorhandler', 'WARN', 'Notice: '.$file.' '.$line.' '.$errmsg);
			return;
		}else if($errno == E_WARNING){
			self::Log('errorhandler', 'WARN', 'Warning: '.$file.' '.$line.' '.$errmsg);
			return;
		}else if($errno == E_USER_NOTICE){
			self::Log('errorhandler', 'WARN', 'User Notice: '.$file.' '.$line.' '.$errmsg);
			return;
		}else if($errno == E_USER_WARNING){
			self::Log('errorhandler', 'WARN', 'User Warning: '.$file.' '.$line.' '.$errmsg);
			return;
		}else if($errno == E_USER_ERROR){
			self::Log('errorhandler', 'ERROR', 'User Error: '.$file.' '.$line.' '.$errmsg);
			// fatal user error has been set explicitly, so we continue to return SOAP fault
		} else {
			// Let's be robust here on the newbees (we are in error mode, so let's try to get most out of it)
			if( !defined('E_RECOVERABLE_ERROR') ) define('E_RECOVERABLE_ERROR', 4096 ); // PHP v5.2
			if( !defined('E_DEPRECATED') )        define('E_DEPRECATED',        8192 ); // PHP v5.3
			if( !defined('E_USER_DEPRECATED') )   define('E_USER_DEPRECATED',  16384 ); // PHP v5.3

			if( $errno == E_RECOVERABLE_ERROR ) {
				self::Log('errorhandler', 'ERROR', 'Recoverable Error: '.$file.' '.$line.' '.$errmsg);
				// Catchable fatal error. It indicates that a probably dangerous error occured,
				// but did not leave the Engine in an unstable state. So we quit here to continue production.
				return;
			} else if( $errno == E_DEPRECATED ) {
				self::Log('errorhandler', 'WARN', 'Deprecated: '.$file.' '.$line.' '.$errmsg);
				return;
			} else if( $errno == E_USER_DEPRECATED ) {
				self::Log('errorhandler', 'WARN', 'User Deprecated: '.$file.' '.$line.' '.$errmsg);
				return;
			} else { // should not happen...
				self::Log('errorhandler', 'ERROR', 'Unknown Error: '.$file.' '.$line.' '.$errmsg);
				// since we don't know what happened here, we try to return this error through SOAP fault as well
			}
		}
		// others means trouble
	}

	/**
	 * Determine temporary log file that is used for this request only.
	 * The folder is automatically created. The file itself is not.
	 * @since 7.5.0
	 *
	 * @return string Full file path to tmp log file.
	 */
	private static function getTmpLogFilePath()
	{
		static $tmpLogFile = null;
		if( is_null( $tmpLogFile ) ) {
			$logFolder = self::getLogFolder();
			if( file_exists($logFolder) ) {
				$microtime = explode(" ", microtime());
				$time = sprintf( '%03d', round($microtime[0]*1000) );
				$tmpLogFile = $logFolder.date('His').'_'.$time.'_service_tmp_log.htm';
			}
		}
		return $tmpLogFile;
	}

	/**
	 * Logs a service request or response. <br>
	 * For each call, a file is created with timestamp prefix and method name. <br>
	 * Log files are created in OUTPUTDIRECTORY/services/<today> folder. <br>
	 * 
	 * @param string $methodName   Service method used to give log file a name.
	 * @param string $transData    Raw transport data to be written in log file.
	 * @param boolean $isRequest   TRUE to indicate a request, FALSE for a response, or NULL for error.
	 * @param string $protocol     Service protocol (e.g. 'soap') used in log file postfix, and to determine file extension.
	 * @param string $fileExt      File extension to use for logging. Supersedes $protocol.
	 * @param bool $forceLog       Whether or not to suppress the DEBUGLEVELS setting.
	 */
	public static function logService( $methodName, $transData, $isRequest, $protocol, $fileExt=null, $forceLog=false )
	{
		if( ($forceLog || self::$debugLevel == 'DEBUG') &&
			!self::suppressLoggingForService( $methodName ) ) {

			if( $isRequest ) { // Futher check on if LogOn request, Password value will change to ***
				$transData = self::replacePasswordWithAsterisk( $methodName, $transData, $protocol );
			}

			// Build file path for log file
			$logFolder = self::getLogFolder();
			if( !empty($logFolder) ) {
				$microTime = explode(" ", microtime());
				$time = sprintf( '%03d', round($microTime[0]*1000) );
				$logFile = $logFolder.date('His').'_'.$time.'_'.$methodName;
				$postfix = '_'.$protocol;
				$postfix .= is_null($isRequest) ? '_Err' : ($isRequest ? '_Req' : '_Resp');
				$postfix .= '.';
				if( $fileExt ) {
					$postfix .= trim($fileExt, '.');
				} else {
					$postfix .= ($protocol == 'SOAP') ? 'xml' : 'txt';
				}

				// Log service (request/response)
				$fileName = $logFile.$postfix;
				$file = fopen( $fileName, 'a' );
				fwrite( $file, $transData );
				fclose( $file );
				
				// Log link to the created service log file.
// COMMENTED OUT: The original Enterprise defines cannot be used in the Proxy environment.
//				$msg = '<a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/showlog.php?act=logfile'.
//						'&file='.basename($fileName).'">'.$methodName.' '.
//						($isRequest ? 'request' : 'response' ).'</a>';
// And replace with the below:
				$msg = $methodName .' '. ($isRequest ? 'request' : 'response' );
				LogHandler::Log( 'webservice', 'DEBUG',  $msg );
			}
		}
	}

	/**
	 * Returns a string with useful details of the current debug stack.
	 * See {@link markupBackTrace()} for details.
	 *
	 * @param integer $hideToplevels Number of rows on top of stack to hide. E.g. pass 2 to hide getDebugBackTrace() function call and yourself.
	 * @return string The stack with \n separations.
	 */
	public static function getDebugBackTrace( $hideToplevels = 1 )
	{
		return self::markupBackTrace( debug_backtrace(), $hideToplevels );
	}

	/**
	 * Returns a string with useful details of the stack of a caught exception.
	 * Typically used to handle uncaught exceptions or exceptions thrown by integrated
	 * systems (non-BizExceptions) to get details of the thrower's stack.
	 * See {@link markupBackTrace()} for details.
	 *
	 * @param Exception The caught exception for which to compose the stack trace.
	 * @param integer $hideToplevels Number of rows on top of stack to hide. E.g. pass 2 to hide getDebugBackTrace() function call and yourself.
	 * @return string The stack with \n separations.
	 */
	public static function getExceptionBackTrace( Exception $exception, $hideToplevels = 1 )
	{
		return self::markupBackTrace( $exception->getTrace(), $hideToplevels );
	}
	
	/**
	 * Returns a string with useful details of the stack. Items are separated by \n markers.
	 * Typically used when ERROR or WARN is raised to give build details for error log.
	 * It goes down the stack until it hits the Service layer (typically for web services).
	 * For web applications, it possibly won't find that layer and so it shows the full stack.
	 *
	 * @param integer $hideToplevels Number of rows on top of stack to hide. E.g. pass 2 to hide getDebugBackTrace() function call and yourself.
	 * @return string The stack with \n separations.
	 */
	private static function markupBackTrace( array $trace, $hideToplevels = 1 )
	{
		$log = '';
		$countDown = count($trace) - 1;
		for( $lev = $hideToplevels; $lev <= $countDown; $lev++ ) {
			$log .= '- ';
			$log .= isset($trace[$lev]['class']) ? $trace[$lev]['class'] : '';
			$log .= isset($trace[$lev]['type']) ? $trace[$lev]['type'] : '';
			$log .= isset($trace[$lev]['function']) ? $trace[$lev]['function'] : '';
			//$log .= isset($trace[$lev]['args']) ? '('.implode(", ", $trace[$lev]['args']).')' : '()'; // causes PHP Catchable fatal error
			$log .= isset($trace[$lev]['line']) ? ' (line#'.$trace[$lev]['line']. ' at ' : '(';
			$log .= isset($trace[$lev]['file']) ? basename($trace[$lev]['file']).')' : ')';
			$log .= "\n";
			if( isset($trace[$lev]['class']) ) {
				if( substr( $trace[$lev]['class'], -8 ) == 'Services' ) { // stop when hitting WorkflowServices class (or any other Serives class)
					break;
				}
			}
		}
		return $log;
	}

	/**
	 * Check on LogOn request, to replace password value with asterisk *
	 *
	 * @param string $methodName
	 * @param string|object $transData Could be string or object
	 * @param string $protocol
	 * @return string $transData
	 */
	public static function replacePasswordWithAsterisk( $methodName, $transData, $protocol )
	{
		$logonMethods = array( 'LogOn', 'WflLogOn', 'LogOnRequest', 'AdmLogOn', 'PlnLogOn' );
		if( in_array($methodName, $logonMethods) ) {
			if( $protocol == 'SOAP' ) {
				// e.g. <Password>xxx</Password> => <Password>***</Password>
				$transData = preg_replace('/<Password>(.*)<\/Password>/is', '<Password>***</Password>', $transData, 1 );
			} elseif( $protocol == 'Service' ) {
				// e.g. [Password] => xxx => [Password] => ***
				$transData = preg_replace('/\[Password\] => (.*)\n(.*)/i', "[Password] => ***\n$2", $transData, 1 );
			} elseif( $protocol == 'AMF' ) {
				// e.g. [Password] => xxx => [Password] => ***
				$transData = preg_replace('/\[Password\] => (.*)\n(.*)/i', "[Password] => ***\n$2", $transData, 1 );
			} elseif( $protocol == 'JSON' ) {
				// e.g. "Password":"xxx" => "Password":"***"
				$transData = preg_replace('/\"Password\":\"(.*)\"/i', "\"Password\":\"***\"", $transData, 1 );
			}
		}

		return $transData;
	}

	/**
	 * Retrieve a member of the $_SERVER superglobal
	 *
	 * @param string $key
	 * @return string Returns null if key does not exist
	 */
	static private function getServerOpt( $key )
	{
		return (isset($_SERVER[$key])) ? $_SERVER[$key] : null;
	}

	/**
	 * Get the remote client IP addres. When any localhost variation is found it simply returns '127.0.0.1'
	 * to make life easier for caller.
	 * Please no more use $_SERVER[ 'REMOTE_ADDR' ] to resolve the remotely calling client IP.
	 * Reasons are that:
	 * - it could be ::1 which represents the localhost (in IPv6 format) and sometimes can not be resolved.
	 * - the client could be a forewarded, for which the address needs to be taken from HTTP_X_FORWARDED_FOR.
	 * - there could be localhost or 127.0.0.1 which are the same, but could lead to mismatches in string compares.
	 *
	 * @return string
	 */
	static private function getClientIP()
	{
		if( self::getServerOpt('HTTP_CLIENT_IP') ) {
			$clientIP = self::getServerOpt('HTTP_CLIENT_IP');
		} else if( self::getServerOpt('HTTP_X_FORWARDED_FOR') ) {
			$clientIP = self::getServerOpt('HTTP_X_FORWARDED_FOR');
		} else {
			$clientIP = self::getServerOpt('REMOTE_ADDR');
		}
		$clientIP = ($clientIP == '::1' || $clientIP == 'localhost' || !$clientIP) ? '127.0.0.1' : $clientIP;
		return $clientIP;
	}
}
