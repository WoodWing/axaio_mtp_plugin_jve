<?php
/**
 * Log handler class for Enterprise Server.
 * Does all system logging for the server while handling web applications, web services and server plug-ins.
 * When PHP raises exception or causes internal errors, those are intercepted and logged here as well.
 * Under the OUTPUTDIRECTORY folder, it creates a <YYMMDD> subfolder where under a client IP subfolder is created.
 * Inside, it does logging for that client calling. The DEBUGLEVELS option tells the granularity of logs made (per client IP).
 * 
 * @package Enterprise
 * @subpackage Logging
 * @since v3.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

/*
Improvements since v8.0 with changed behavior:
- To ease clean-up, log files are moved to a subfolder (with YYMMDD format) that is automatically created per day.
  For example, you can throw away folders older than one week manually or by your own batch job.
- To increase performance (avoiding clients fighting for the log file handler):
	- Activities are logged per client inside its 'own' subfolder (named after client IP).
	- Instead of adding logs to one file, per application call or service request a new file is created.
	- The system information shown at log file headers is no longer done. Use 'Config Overview' at wwtest instead.
- The sce_log_<DB>.htm log file is renamed to ent_log_<module>.htm. The <module> is the name of the called PHP file.
  The server product is no longer named SC Enterprise so it became confusing. Using the DB name does not add much.
- The sce_log_errors.htm log file is renamed to ent_log_errors.htm. This file resides in each client IP folder.
- 1MB file limit is no longer needed/used (after which current log file was renamed and new one was created)
- When two requests were handled at the same time, their logging got mixed. It simply started two headers (grey rows)
  followed by mixture of rows of both not telling what belongs to what. Now each call gets its own log file.
- For DEBUG level, WARN was 'increased' on-the-fly to ERROR in order to collect them at sce_log_errors.htm file. 
  This was done to trigger the error dialog shown at admin pages when the error file was created.
  The goal was to show warnings to developers to they get fixed sooner and to inform system users asap.
  Now it is changed; WARN is still WARN in DEBUG mode, but also logged at ent_log_errors.htm file.
  This way, the goal is still served but the levels are better respected which is more clear.
- All public non-static functions of the LogHandler class became static. 
  Call LogHandler::function() instead of LogHandler::getInstance()->function().
- The configserver.php no longer initiates error- and exception handler. This is moved to the init() function.
  The init() function should only be called once per client call. So never call it, but include config.php instead.
- All logged messages have \n at end of line, also for HTML format.
  This makes it possible to use grep from command line searching for a specific error.
- For logged errors and warnings (ERROR and WARN) the PHP stack is logged in DEBUG mode.
  So, there is no longer a need to call getDebugBackTrace() or debug_backtrace() and pass it to LogHandler::Log().
- For raised exceptions (BizException) an ERROR or WARN is always logged. (Before, it was done in DEBUG mode only.)
  Do not call LogHandler::Log() just before raising a BizException, or after catching it, or else you'll have two logs.
- The debug loglevel can now be configured per client (IP). Therefor, DEBUGLEVEL option is replaced by DEBUGLEVELS (plural).
  Making a code fragment debug-only must be done with LogHandler::debugMode() which checks the configured debug
  level for the client (IP) doing the request. The function also checks OUTPUTDIRECTORY option to be configured.
- Defines/settings made at configserver.php for log configuration became mandatory and are checked at wwtest.
  This includes the OUTPUTDIRECTORY, DEBUGLEVELS, LOGSQL and PROFILELEVEL options.
  There is no longer the need to call the defined(...) function before accessing their values.
- When Solr was down, there were 2 warnings and 1 error logged. The warnings did show the PHP stack but the error did not.
  Now there is just one error logged, which is made possible through new serverity param at BizException class.
  The code raising the BizException can tell how bad the error is. You can even pass 'NONE' if there is no problem.
- When manually removing log files, admin user has to login because files were owner by www/inet user.
  Now this is fixed since all log files (and subfolders) have the same rights applied as their parental folder.
- Aside to the current HTML format at log files, there is now support for plain UTF-8 text format too.
  HTML is better readable in web browser, but plain format is easier for searching e.g. using grep.
  To distinguish between both formats, an option named LOGFILE_FORMAT is added to configserver.php file.
- The header that is now removed from all log files. Instead, much more PHP info is written to log folder
  (at _phpinfo.htm file) once the client IP folder gets created, which is a more efficient solution.
- For each log operation (writing a single line), the log file is flushed in DEBUG mode, to make sure the
  last log is actually written in case of unexpected PHP crash. For production, this delay is unwanted, so skipped.

Removed functions since v8.0:
- getInstance() => no more needed since all methods became static. Call LogHandler::function() instead of LogHandler::getInstance()->function().
- writeLog() => use LogHandler::Log() instead
- Dump() and Trace() 
	=> By passing ERROR/WARN level for LogHandler::Log() function, the stack is automatically logged in debug mode. 
	For explicit stack dumps, call the getDebugBackTrace() function instead.
- setAreasToLog() => Feature was not used and is no longer supported.

Example of avoiding expensive print_r() calls...
	Before Enterprise 8, checks were done like this:
		if( defined('DEBUGLEVEL') && DEBUGLEVEL == 'DEBUG' &&
			 defined('OUTPUTDIRECTORY') && OUTPUTDIRECTORY != '' ) { // Performance check: Avoid print_r for production !!
			LogHandler::Log('filestore', 'DEBUG', 'Renditions: '.print_r($renditions, true) );
		}
	Since Enteprise 8, it should be done like this:
		if( LogHandler::debugMode() ) { // Performance check: Avoid print_r for production !!
			LogHandler::Log('filestore', 'DEBUG', 'Renditions: '.print_r($renditions, true) );
		}

Improvements since 10.1.4:
- Messages are passed in without any formatting for the 'html' format. To ensure secure logging the message is
  properly encoded before added to the (html) log file.
  - Instead of using <br/> to enforce line breaks, the log message should contain \r\n. This improves the outline of the
    plain text log file. In case of the 'html' format the \r\n is replaced by <br/> before the message is written to the
    (html) log file.
  - All html tags within the message are ignored and are encoded.
- Method LogHandler::logRaw is added. This can be used to write the message 'as is' to the log file. It is up to the
  caller to ensure that the encoding of the message is secure. To create a secure message two helper methods are added:
  - LogHandler::composeCodeBlock(): To format a (part of the) message to be displayed as a code fragment.
  - LogHandler::encodeLogMessage(): To ensure that the (rest) message is properly encoded.
  Example: LogHandler::logRaw(
            __CLASS__,
				'DEBUG',
				LogHandler::encodeLogMessage( "Error: {$e->message}\r\nData: \r\n" ).LogHandler::composeCodeBlock( print_r( $data, 1 ) ) );
  - The LogHandler::logRaw() should only be used for special cases. LogHandler::Log() is standard way used for logging.
*/

class LogHandler
{
	private static $logLevels = array(
		'NONE'         => 0, // Suppress all logging.
		'CONTEXT'      => 1, // Indicates the calling context. NOT directly logged; Logged on first best 'normal' log.
		'ERROR'        => 2, // Exceptional error. Stops execution in most cases.
		'WARN'         => 3, // Warning. Should NOT happen. Recommended option for production.
		'INFO'         => 4, // Bus stop. Operation successful or found/determined details.
		'DEPRECATED'   => 5, // To inform that the code / function  is deprecated.
		'DEBUG'        => 6, // For debugging purposes only. Should not be used in production.
		'NOTINSTALLED' => 7, // feature is not installed, used in WWTest
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
	 * Builds and returns a single line in html format.
	 *
	 * @since 10.1.4
	 * @param string $area Indication of caller. It is recommended to pass __CLASS__.
	 * @param string $level Log level. Should be any value of self::$logLevels.
	 * @param string $message Message to write to log file.
	 * @param string $time Current time stamp of the logged message. Leave empty to auto calculate.
	 * @param string $logFile File name of the current log file. Use to refer from error log to current log.
	 * @return string Formatted log line.
	 */
	private static function composeHtmlLogLine( $level, $area, $message, &$time, $logFile )
	{
		if( $logFile ) {
			$logFileRef = SERVERURL_ROOT.INETROOT.'/server/admin/showlog.php?act=logfile'.
				'&file='.basename( $logFile ).'#'.$time;
			$reference = '<br/><a href="'.$logFileRef.'">Show full context</a>';
		} else {
			$reference = '';
		}
		$levColor = ( $level == 'ERROR' ) ? '#ff0000' : ( ( $level == 'WARN' || $level == 'DEPRECATED' ) ? '#ffaa00' : '#00cc00' ); // red,orange,green
		$line =
			'<tr class="d" id="'.$time.'">'.
			'<td><nobr>'.$time.'</nobr>'.$reference.'</td>'.
			'<td><font color="'.$levColor.'">'.$level.'</font></td>'.
			'<td>'.$area.'</td><td>'.$message.'</td>'.
			'</tr>'.PHP_EOL;
		return $line;
	}

	/**
	 * Builds and returns a single line in plain text format.
	 *
	 * @since 10.1.4
	 * @param string $area Indication of caller. It is recommended to pass __CLASS__.
	 * @param string $level Log level. Should be any value of self::$logLevels.
	 * @param string $message Plain text string to write to log file.
	 * @param string $time Current time stamp of the logged message. Leave empty to auto calculate.
	 * @return string Formatted log line.
	 */
	private static function composePlainLogLine( $level, $area, $message, &$time )
	{
		return sprintf( '%-25s %-8s %-15s %s'.PHP_EOL, $time, $level, $area, $message );
	}

	/**
	 * Composes the error stack.
	 *
	 * @since 10.1.4
	 * @param Exception $exception
	 * @return string The stack that led to the exception.
	 */
	private static function composeStack( $exception )
	{
		$stack = "\nStack:\n".self::getDebugBackTrace( 3 );
		if( $exception ) {
			$stack .= "\n-------------\nException Message: ".$exception->getMessage();
			$stack .= "\nException File: ".$exception->getFile() . ' ' . $exception->getLine();
			if( get_class( $exception ) == '' ) {
				$stack .= "\nException Details: ".$exception->getDetail();
			}
			$stack .= "\nException Stack:\n".self::getExceptionBackTrace( $exception );
		}

		return self::encodeLogMessage( $stack );
	}

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
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		self::$clientIP = WW_Utils_UrlUtils::getClientIP();

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
	 *
	 * Typically called by Health Check (wwtest) to determine if the DEBUGLEVELS option is correctly
	 * configured at the configserver.php file.
	 *
	 * @param integer $level
	 * @return boolean TRUE when supported, or FALSE if not.
	 */
	public static function isValidLogLevel( $level )
	{
		return array_key_exists( $level, self::$logLevels );
	}

	/**
	 * Class destructor, called by PHP when the LogHandler ends life time.
	 *
	 * This function moves all log collected messages from the tmp log file to the 'real' server log file.
	 * The tmp file is removed afterwards.
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
	 *
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
			// Remove dangerous characters of the log folder, that prohibited in file system
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			$newClientIP = FolderUtils::replaceDangerousChars(self::$clientIP );
			self::$logFolder .= $newClientIP.'/';

			if( !file_exists(self::$logFolder) ) {
				require_once BASEDIR.'/server/utils/FolderUtils.class.php';
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
				require_once BASEDIR.'/server/utils/PhpInfo.php';
				file_put_contents( $ipPhpInfoFile, WW_Utils_PhpInfo::getAllInfo() );
				copy( $ipPhpInfoFile, $dayPhpInfoFile );
			}
		}
		return self::$logFolder;
	}

	/**
	 * Validates a given folder name that resides directly under the root log folder.
	 *
	 * A valid name should be in 'Ymd' notation representing a date.
	 *
	 * @since 10.1.4
	 * @param string $folder The name of the folder (not the full path).
	 * @return bool TRUE when valid, else FALSE.
	 */
	private static function isValidDailyFolderName( $folder )
	{
		return strlen( $folder ) == 8 && ctype_digit( $folder );
	}

	/**
	 * Validates a given client ip folder name that resides directly under the daily log folder.
	 *
	 * A valid name should represent a valid IP address (either IPv4 or IPv6).
	 *
	 * @since 10.1.4
	 * @param string $folder The name of the folder (not the full path).
	 * @return bool TRUE when valid, else FALSE.
	 */
	private static function isValidClientIpFolderName( $folder )
	{
		return filter_var( $folder, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Validates a given log file that typically resides directly under the client ip log folder.
	 *
	 * @since 10.1.4
	 * @param string $logFile The name of the file (not the full path).
	 * @return bool TRUE when valid, else FALSE.
	 */
	private static function isValidLogFileName( $logFile )
	{
		return $logFile &&
			$logFile[0] !== '.' && // don't reveal hidden files
			strpos( $logFile, '..' ) === false && // don't allow change dir
			strpbrk( $logFile, '\\/?*' ) === false; // don't allow suspecious chars
	}

	/**
	 * Returns the (daily) folders that resides directly under the root log folder.
	 *
	 * @return array List of folder names (not the full paths).
	 */
	public static function listDailyRootFolders()
	{
		$dailyFolders = array();
		$rootDir = OUTPUTDIRECTORY;
		if( $rootDir && is_dir( $rootDir ) ) {
			$folders = scandir( $rootDir );
			if( $folders ) foreach( $folders as $folder ) {
				if( $folder[0] != '.' && is_dir( $rootDir.$folder ) ) {
					if( self::isValidDailyFolderName( $folder ) ) { // check 'Ymd' date notation (anti hack)
						$dailyFolders[] = $folder;
					}
				}
			}
		}
		return $dailyFolders;
	}

	/**
	 * Returns the (client ip) folders that resides directly under the daily log folder.
	 *
	 * @since 10.1.4
	 * @param string $dailyFolder The name of the folder (not the full path).
	 * @return array List of folder names (not the full paths).
	 */
	public static function listClientIpSubFolders( $dailyFolder )
	{
		$clientIpFolders = array();
		if( self::isValidDailyFolderName( $dailyFolder ) ) { // check 'Ymd' date notation (anti hack)
			$rootDir = OUTPUTDIRECTORY;
			if( $rootDir && is_dir( $rootDir ) ) {
				$dailyDir = $rootDir.'/'.$dailyFolder;
				if( is_dir( $dailyDir ) ) {
					$folders = scandir( $dailyDir );
					if( $folders ) foreach( $folders as $folder ) {
						if( $folder[0] != '.' && is_dir( $dailyDir.'/'.$folder ) ) {
							if( self::isValidClientIpFolderName( $folder ) ) { // check IP notation (anti hack)
								$clientIpFolders[] = $folder;
							}
						}
					}
				}
			}
		}
		return $clientIpFolders;
	}

	/**
	 * Returns the log files that resides directly under the client ip log folder.
	 *
	 * @since 10.1.4
	 * @param string $dailyFolder The name of the folder (not the full path).
	 * @param string $clientIpFolder The name of the folder (not the full path).
	 * @return array List of folder names (not the full paths).
	 */
	public static function listLogFiles( $dailyFolder, $clientIpFolder )
	{
		$logFiles = array();
		if( self::isValidDailyFolderName( $dailyFolder ) ) { // check 'Ymd' date notation (anti hack)
			if( self::isValidClientIpFolderName( $clientIpFolder ) ) { // check IP notation (anti hack)
				$rootDir = OUTPUTDIRECTORY;
				if( $rootDir && is_dir( $rootDir ) ) {
					$clientIpDir = $rootDir.'/'.$dailyFolder.'/'.$clientIpFolder;
					if( is_dir( $clientIpDir ) ) {
						$files = scandir( $clientIpDir );
						if( $files ) foreach( $files as $file ) {
							if( $file[0] != '.' && is_file( $clientIpDir.'/'.$file ) ) {
								$logFiles[] = $file;
							}
						}
					}
				}
			}
		}
		return $logFiles;
	}

	/**
	 * Returns the content of a log file that typically resides directly under the client ip log folder.
	 *
	 * @since 10.1.4
	 * @param string $dailyFolder The name of the folder (not the full path).
	 * @param string $clientIpFolder The name of the folder (not the full path).
	 * @param string $logFile The name of the file (not the full path).
	 * @return string The file content.
	 */
	public static function getLogFileContent( $dailyFolder, $clientIpFolder, $logFile )
	{
		$content = '';
		$rootDir = OUTPUTDIRECTORY;
		if( $rootDir && is_dir( $rootDir ) ) {
			if( self::isValidDailyFolderName( $dailyFolder ) ) { // check 'Ymd' date notation (anti hack)
				if( self::isValidClientIpFolderName( $clientIpFolder ) ) { // check IP notation (anti hack)
					if( self::isValidLogFileName( $logFile ) ) { // check dangerous chars (anti hack)
						$file = OUTPUTDIRECTORY."/$dailyFolder/$clientIpFolder/$logFile";
						if( is_file( $file ) ) {
							$content = file_get_contents( $file );
						}
					}
				}
			}
		}
		return $content;
	}

	/**
	 * Returns the configured OUTPUTDIRECTORY option value (without ending '/'), but only when folder exists.
	 *
	 * @since 10.1.4
	 * @return string Full file path when valid, or EMPTY when not valid.
	 */
	private static function getValidRootLogFolder()
	{
		$rootDir = OUTPUTDIRECTORY;
		if( $rootDir && is_dir( $rootDir ) ) {
			$rootDir = substr( $rootDir, 0, -1 ); // remove '/' from the end
		} else {
			$rootDir = '';
		}
		return $rootDir;
	}

	/**
	 * Returns the folder name of the root log folder.
	 *
	 * @since 10.1.4
	 * @return string Folder name (not the full path).
	 */
	public static function getRootLogFolderName()
	{
		$rootFolder = '';
		if( ( $rootDir = self::getValidRootLogFolder() ) ) {
			$rootFolder = basename( $rootDir );
		}
		return $rootFolder;
	}

	/**
	 * Returns the (daily) folders that resides directly under the root log folder.
	 *
	 * @since 10.1.4
	 * @return array List of folder names (not the full paths).
	 */
	public static function listDailySubFolders()
	{
		$dailyFolders = array();
		if( ( $rootDir = self::getValidRootLogFolder() ) ) {
			$folders = scandir( $rootDir );
			if( $folders ) foreach( $folders as $folder ) {
				if( $folder[0] != '.' && is_dir( "{$rootDir}/{$folder}" ) ) {
					if( self::isValidDailyFolderName( $folder ) ) { // check 'Ymd' date notation (anti hack)
						$dailyFolders[] = $folder;
					}
				}
			}
		}
		return $dailyFolders;
	}

	/**
	 * Removes the files and folders under the root log folder.
	 *
	 * @since 10.1.4
	 */
	public static function deleteRootFolder()
	{
		if( ( $rootDir = self::getValidRootLogFolder() ) ) {
			self::deleteDir( $rootDir );
		}
	}

	/**
	 * Archives the files and folders under the root log folder.
	 *
	 * @since 10.1.4
	 * @return string Name of the archived file.
	 */
	public static function archiveRootFolder()
	{
		$archiveFilePath = '';
		if( ( $rootDir = self::getValidRootLogFolder() ) ) {
			$archiveFilePath = self::archiveDir( $rootDir );
		}
		return $archiveFilePath;
	}

	/**
	 * Removes the files and folders under the given daily log folder.
	 *
	 * @since 10.1.4
	 * @param string $dailyFolder The name of the folder (not the full path).
	 */
	public static function deleteDailyFolder( $dailyFolder )
	{
		if( self::isValidDailyFolderName( $dailyFolder ) ) { // check IP notation (anti hack)
			if( ( $rootDir = self::getValidRootLogFolder() ) ) {
				$dailyDir = "{$rootDir}/{$dailyFolder}";
				self::deleteDir( $dailyDir );
			}
		}
	}

	/**
	 * Archives the files and folders under the given daily log folder.
	 *
	 * @since 10.1.4
	 * @param string $dailyFolder The name of the folder (not the full path).
	 * @return string Name of the archived file.
	 */
	public static function archiveDailyFolder( $dailyFolder )
	{
		$archiveFilePath = '';
		if( self::isValidDailyFolderName( $dailyFolder ) ) { // check IP notation (anti hack)
			if( ( $rootDir = self::getValidRootLogFolder() ) ) {
				$dailyDir = "{$rootDir}/{$dailyFolder}";
				$archiveFilePath = self::archiveDir( $dailyDir );
			}
		}
		return $archiveFilePath;
	}

	/**
	 * Removes the given ip log folder and all the log files that resides inside that folder.
	 *
	 * @since 10.1.4
	 * @param string $dailyFolder The name of the folder (not the full path).
	 * @param string $clientIpFolder The name of the folder (not the full path).
	 */
	public static function deleteClientIpSubFolder( $dailyFolder, $clientIpFolder )
	{
		if( self::isValidDailyFolderName( $dailyFolder ) && // check 'Ymd' date notation (anti hack)
			self::isValidClientIpFolderName( $clientIpFolder ) ) { // check IP notation (anti hack)
			if( ( $rootDir = self::getValidRootLogFolder() ) ) {
				$clientIpDir = "{$rootDir}/{$dailyFolder}/{$clientIpFolder}";
				self::deleteDir( $clientIpDir );
			}
		}
	}

	/**
	 * Archives the given ip log folder and all the log files that resides inside that folder.
	 *
	 * @since 10.1.4
	 * @param string $dailyFolder The name of the folder (not the full path).
	 * @param string $clientIpFolder The name of the folder (not the full path).
	 * @return string Name of the archived file.
	 */
	public static function archiveClientIpSubFolder( $dailyFolder, $clientIpFolder )
	{
		$archiveFilePath = '';
		if( self::isValidDailyFolderName( $dailyFolder ) && // check 'Ymd' date notation (anti hack)
			self::isValidClientIpFolderName( $clientIpFolder ) ) { // check IP notation (anti hack)
			if( ( $rootDir = self::getValidRootLogFolder() ) ) {
				$clientIpDir = "{$rootDir}/{$dailyFolder}/{$clientIpFolder}";
				$archiveFilePath = self::archiveDir( $clientIpDir );
			}
		}
		return $archiveFilePath;
	}

	/**
	 * Recursively removes the given folder and any files and folders inside.
	 *
	 * @since 10.1.4
	 * @param string $dirPath
	 */
	private static function deleteDir( $dirPath )
	{
		if( is_dir( $dirPath ) ) {
			if( substr( $dirPath, strlen( $dirPath ) - 1, 1 ) != '/' ) {
				$dirPath .= '/';
			}
			$files = glob( $dirPath.'*', GLOB_MARK );
			if( $files ) foreach( $files as $file ) {
				if( is_dir( $file ) ) {
					self::deleteDir( $file );
				} elseif( is_file( $file ) ) {
					unlink( $file );
				}
			}
			rmdir( $dirPath );
		}
	}

	/**
	 * Creates a ZIP file of a given folder that contains all files and subfolders inside.
	 *
	 * @since 10.1.4
	 * @param string $dirPath
	 * @return string Path of created archive file.
	 */
	private static function archiveDir( $dirPath )
	{
		$archivePath = '';
		if( is_dir( $dirPath ) ) {
			$archivePath = $dirPath.'.zip';
			require_once BASEDIR.'/server/utils/ZipUtility.class.php';
			$zipUtility = WW_Utils_ZipUtility_Factory::createZipUtility();
			$zipUtility->createZipArchive( $archivePath );
			$zipUtility->addDirectoryToArchive( $dirPath.'/' );
		}
		return $archivePath;
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
			require_once BASEDIR.'/server/utils/htmlclasses/HtmlLog.class.php';
			$header = '<html>'.HtmlLog::getHead( 'System Log' );
			$header .= '<table><tr><th>Time</th><th>Level</th><th>Area</th><th>Message</th></tr>'.PHP_EOL;
		}
		return $header;
	}

	/**
	 * Builds and returns a single line with given logging information.
	 *
	 * Supports plain and html formats. Called for every Log operation.
	 *
	 * @param string $area Indication of caller. It is recommended to pass __CLASS__.
	 * @param string $level Log level. Should be any value of self::$logLevels.
	 * @param string $message HTML formatted string to write to log file.
	 * @param string $time Current time stamp of the logged message. Leave empty to auto calculate.
	 * @param string $logFile File name of the current log file. Use to refer from error log to current log.
	 * @return string Formatted log line.
	 */
	private static function getLogLine( $level, $area, $message, &$time, $logFile )
	{
		if( !$time ) {
			list( $microSeconds, $seconds ) = explode( " ", microtime() );
			$microsecondsFormatted = sprintf( '%03d', round( $microSeconds * 1000, 0 ) );
			$time = date( 'Y-m-d H:i:s', $seconds ).'.'.$microsecondsFormatted;
		}

		if( LOGFILE_FORMAT == 'plain' ) {
			$line = self::composePlainLogLine( $level, $area, $message, $time );
		} else { // HTML.
			$line = self::composeHtmlLogLine( $level, $area, $message, $time, $logFile );
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
	 * @param string $logFile
	 * @param integer $fileMode The type of access required to the file.
	 * @return resource|boolean Log file handle or false in case of error.
	 */
	private static function openFile( $logFile, $fileMode )
	{
		// Open or create the log file
		$exists = file_exists( $logFile );
		$handle = fopen( $logFile, $fileMode );
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
	 * Returns the full file path of php.log file.
	 *
	 * The file php.log is used for fatal errors.
	 *
	 * @return string File path of the php.log file or empty string when logging is disabled or when php.log file doesn't exist.
	 */
	public static function getPhpLogFile()
	{
		if( OUTPUTDIRECTORY == '' ) {
			return ''; // logging disabled
		}

		$logFile = '';
		if( is_dir( OUTPUTDIRECTORY )) { // HealthCheck should take care that this directory exists, so no error handling needed when it doesn't exist.
			$logFile = file_exists( OUTPUTDIRECTORY.'php.log' ) ? OUTPUTDIRECTORY.'php.log' : '';
		}
		return $logFile;
	}

	/**
	 * Compose a unique file name for an object file.
	 *
	 * Can be used e.g. for debugging to copy a native object file into the log folder for further analysis.
	 *
	 * @since 10.2.0
	 * @param string $postfix Name to be added to the filename.
	 * @param string|null $format Mimetype, used in conjunction with $objectType to resolve the file extension.
	 * @param string|null $objectType
	 * @return string Full file path
	 */
	public static function composeFileNameInLogFolder( $postfix, $format=null, $objectType=null )
	{
		$fileExt = '';
		if( $format ) {
			require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
			$fileExt = MimeTypeHandler::mimeType2FileExt( $format, $objectType );
		}
		$phpLogFile = self::getLogFolder().self::getCurrentTimestamp().'_'.$postfix.$fileExt;
		return $phpLogFile;
	}

	/**
	 * Dumps the entire content of a given PHP object into a new temp file at server log folder.
	 * There are several methods supported to dump the PHP object.
	 *
	 * @param stdClass $phpObject The PHP object to dump.
	 * @param string $method Support values: 'var_dump', 'var_export', 'serialize' or 'print_r'. See PHP manual. 
	 *                       [v9.7] Also 'pretty_print' is supported which is similar to print_r but then with type info.
	 *                       See {@link:prettyPrint} for more info.
	 * @param string $postfix Additional string to add to temp file name.
	 * @return string The create file path.
	 */
	public static function logPhpObject( $phpObject = null, $method = 'pretty_print', $postfix = '' )
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
				$content = print_r( $phpObject, true );
				break;
			case 'pretty_print':
			default:
				$content = self::prettyPrint( $phpObject );
				break;
		}

		$objName = is_object( $phpObject ) ? get_class( $phpObject ) : gettype( $phpObject );
		$phpLogFile = $logFolder.self::getCurrentTimestamp().'_'.$method.'_'.$objName;
		if( $postfix ) {
			$phpLogFile .= '_'.$postfix;
		}
		$phpLogFile .= '.txt';

		// Write the dumped PHP object data and return file path to caller.
		file_put_contents( $phpLogFile, $content );
		return $phpLogFile;
	}

	/**
	 * Compose a string with current time indication in micro seconds.
	 *
	 * For example it returns "234500_021A" (which means 23h 45' 00" and 21 ms for the first caller "A").
	 * When in the very same micro second this function is called again, it will use new postfix "B", etc.
	 *
	 * @since 10.2.0
	 * @return string
	 */
	static private function getCurrentTimestamp()
	{
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

		return $currTimeStamp.$callerId;
	}

	/**
	 * Composes a similar log as the print_r() function, but now with type information.
	 * And, instead of printing to standard output, the composed log is returned.
	 * Strings are quoted, other types are not. So "true" is a string with boolean value,
	 * while true is a real boolean. Object properties and array key-values are indented.
	 *
	 * @since 9.7.0
	 * @param mixed $data Any kind of PHP data to be logged; object, array, string, number, etc.
	 * @param integer $indent Initial number of tabs to indent the whole tree of log lines. During internal recursion it is used to indent array items and object properties.
	 * @return string The composed log line to be printed by caller.
	 */
	static public function prettyPrint( $data, $indent = 0 )
	{
		$tabs = str_repeat( "\t", $indent );
		if( is_null( $data ) ) {
			$log = 'null';
		} elseif( is_object( $data ) ) {
			$log = get_class( $data ) . ' (object) {' . PHP_EOL;
			foreach( get_object_vars( $data ) as $key => $value ) {
				$log .= $tabs . "\t" . $key . ' => ';
				$log .= self::prettyPrint( $value, $indent+1 ) . PHP_EOL;
			}
			$log .= $tabs . '}';
		} elseif( is_array( $data ) ) {
			if( $data ) {
				$log = 'array(' . PHP_EOL;
				foreach( $data as $key => $value ) {
					if( is_null($key) ) {
						$keyStr = 'null';
					} elseif( is_string($key) ) {
						$keyStr = '"'.$key.'"';
					} elseif( is_bool($key) ) {
						$keyStr = $key ? 'true' : 'false';
					} else { // integer, float
						$keyStr = $key;
					}
					$log .= $tabs . "\t" . '[' . $keyStr . '] => ';
					$log .= self::prettyPrint( $value, $indent+1 ) . PHP_EOL;
				}
				$log .= $tabs . ')';
			} else {
				$log = 'array()';
			}
		} elseif( is_resource( $data ) ) {
			$log = get_resource_type($data) . ' (resource)';
		} elseif( is_string( $data ) ) {
			$log = '"' . $data . '"';
		} elseif( is_bool( $data ) ) {
			$log = $data ? 'true' : 'false';
		} else { // integer, float
			$log = $data. ' (' . gettype($data) . ')';
		}
		return $log;
	}

	/**
	 * Writes one log entry to the log file.
	 *
	 * Before the message is written to the log file it will be encoded in case the log format is HTML.
	 * Use debugMode() function in case you want to write large amount of data, typically
	 * through print_r($data,true). See file header of this module how to do such.
	 *
	 * @param string $area Indication of caller. It is recommended to pass __CLASS__.
	 * @param string $level Log level. Should be any value of self::$logLevels.
	 * @param string $message String to write to log file.
	 * @param Exception|null $exception Optionally, provide a caught exception to log all details for as well.
	 */
	public static function Log( $area, $level, $message, $exception = null )
	{
		$message = self::encodeLogMessage( $message );
		self::logRaw( $area, $level, $message, $exception );
	}

	/**
	 * Writes one log entry to the log file.
	 *
	 * No encoding is done on the message. Use this method when message is already correctly encoded. Especially in case
	 * the log format is HTML it is important that the message is correctly encoded to prevent JavaScript injection.
	 * To prevent injection use the self::encodeLogMessage generate a secure log message. If a (part of the) message
	 * should not be encoded use the self::composeCodeBlock method to generate a secure message.
	 * Use debugMode() function in case you want to write large amount of data, typically
	 * through print_r($data,true). See file header of this module how to do such.
	 *
	 * @since 10.1.4
	 * @param string $area Indication of caller. It is recommended to pass __CLASS__.
	 * @param string $level Log level. Should be any value of self::$logLevels.
	 * @param string $message String to write to log file.
	 * @param Exception|null $exception Optionally, provide a caught exception to log all details for as well.
	 */
	public static function logRaw( $area, $level, $message, $exception = null )
	{
		// Quit when logging feature is disabled
		if( OUTPUTDIRECTORY == '' || self::$debugLevel == 'NONE' ) {
			return;
		}

		// Quit when given log level is too detailed (compared to configured level by system admin)
		if( self::$logLevels[ self::$debugLevel ] < self::$logLevels[ $level ] ) {
			return;
		}
		// Suppress logging for frequently polling services (except when there are errors or warnings).
		if( $level != 'ERROR' && $level != 'WARN' && $level != 'DEPRECATED' && self::suppressLoggingForService() ) {
			return;
		}

		// Insert header with calling script name, only for first log of this session
		if( self::$logged ) {
			$header = '';
		} else {
			$header = self::getLogRequestHeader();
			self::$logged = true;
		}
		$msg = $header;

		// Add calling stack information in debug mode for errors and warnings.
		$isDebugWarnError = ( self::$debugLevel == 'DEBUG' && ( $level == 'ERROR' || $level == 'WARN' || $level == 'DEPRECATED' ));
		if( $isDebugWarnError ) {
			$message .= self::composeStack( $exception );
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
		if( !empty( self::$context ) && !self::$wroteContext ) {
			$msg = self::$context.$msg;
			self::$wroteContext = true;
			self::$context = '';
		}

		// Write log to file
		$logFile = self::getLogFile();
		if( !empty( $logFile ) ) {
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
			if( !empty( $logFolder ) ) {
				$fileExt = ( LOGFILE_FORMAT == 'plain' ) ? '.txt' : '.htm';
				$handle = self::openFile( $logFolder.'ent_log_errors'.$fileExt, 'a+' );
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
	 * Ensures that a string is properly encoded before sending it to the log file.
	 *
	 * This is especially needed when the log format is 'html'. All special characters are converted to html entities.
	 * The new line markers (\r\n) are replaced by '<br/>' to support line breaks in case of 'html' format.
	 *
	 * @since 10.1.4
	 * @param string $message
	 * @return string encode message
	 */
	public static function encodeLogMessage( $message )
	{
		$result = $message;
		if( LOGFILE_FORMAT == 'html' ) {
			$result = htmlentities( $message, ENT_SUBSTITUTE );
			$result = nl2br( $result );
		}

		return $result;
	}

	/**
	 * Adds formatting to a 'code' fragment so it is nicely displayed.
	 *
	 * @since 10.1.4
	 * @param string $codeBlock
	 * @return string code fragment ready for display.
	 */
	public static function composeCodeBlock( $codeBlock )
	{
		$result = $codeBlock;
		if( LOGFILE_FORMAT == 'html' ) {
			$result = self::encodeLogMessage( $codeBlock );
			$result = "<code>$result</code>";
		}

		return $result;
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
		if( !$serviceName ) {
			$serviceName = BizSession::getServiceName();
		}
		return BizSession::isFeatherLightService( $serviceName );
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
				$logFile = $logFolder.'ent_log_errors'.$fileExt;
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
			// Let's be robust here on the newbies (we are in error mode, so let's try to get most out of it)
			if( !defined('E_RECOVERABLE_ERROR') ) define('E_RECOVERABLE_ERROR', 4096 ); // PHP v5.2
			if( !defined('E_DEPRECATED') )        define('E_DEPRECATED',        8192 ); // PHP v5.3
			if( !defined('E_USER_DEPRECATED') )   define('E_USER_DEPRECATED',  16384 ); // PHP v5.3

			if( $errno == E_RECOVERABLE_ERROR ) {
				self::Log('errorhandler', 'ERROR', 'Recoverable Error: '.$file.' '.$line.' '.$errmsg);
				// Catchable fatal error. It indicates that a probably dangerous error occured,
				// but did not leave the Engine in an unstable state. So we quit here to continue production.
				return;
			} else if( $errno == E_DEPRECATED ) {
				self::Log('errorhandler', 'DEPRECATED', 'Deprecated: '.$file.' '.$line.' '.$errmsg);
				return;
			} else if( $errno == E_USER_DEPRECATED ) {
				self::Log('errorhandler', 'DEPRECATED', 'User Deprecated: '.$file.' '.$line.' '.$errmsg);
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
	 * @param callable|null $customObfuscatePassword A PHP callable to a custom function to obfuscate passwords for logging purposes.
	 */
	public static function logService( $methodName, $transData, $isRequest, $protocol, $fileExt=null, $forceLog=false, $customObfuscatePassword=null )
	{
		if( ($forceLog || self::$debugLevel == 'DEBUG') &&
			!self::suppressLoggingForService( $methodName ) ) {

			if( $isRequest ) { // Futher check on if LogOn request, Password value will change to ***
				if( !$customObfuscatePassword ) {
					$transData = self::replacePasswordWithAsterisk( $methodName, $transData, $protocol );
				} elseif( is_callable($customObfuscatePassword) ) {
					$transData = call_user_func( $customObfuscatePassword, $methodName, $transData );
				}
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
				$msg = '<a href="'.SERVERURL_ROOT.INETROOT.'/server/admin/showlog.php?act=logfile'.
						'&file='.basename($fileName).'">'.$methodName.' '.
						($isRequest ? 'request' : 'response' ).'</a>';
				self::logRaw( 'webservice', 'DEBUG',  $msg );
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
	 * @param Exception|Throwable $exception The caught exception for which to compose the stack trace. Since PHP7 it can be any kind of Throwable exceptions.
	 * @param integer $hideToplevels Number of rows on top of stack to hide. E.g. pass 2 to hide getDebugBackTrace() function call and yourself.
	 * @return string The stack with \n separations.
	 */
	public static function getExceptionBackTrace( $exception, $hideToplevels = 1 )
	{
		return self::markupBackTrace( $exception->getTrace(), $hideToplevels );
	}
	
	/**
	 * Returns a string with useful details of the stack. Items are separated by \n markers.
	 * Typically used when ERROR or WARN is raised to give build details for error log.
	 * It goes down the stack until it hits the Service layer (typically for web services).
	 * For web applications, it possibly won't find that layer and so it shows the full stack.
	 *
	 * @param array $trace
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
	 * Even in case of empty passwords the password needs to be replaced by asterisk. When the logon is done by using
	 * a ticket instead of a user/password combination the password is also set to asterisk. So the replacement should
	 * never fail.
	 *
	 * @param string $methodName
	 * @param string|object $transData Could be string or object
	 * @param string $protocol
	 * @return string $transData
	 */
	public static function replacePasswordWithAsterisk( $methodName, $transData, $protocol )
	{
		$logonMethods = array( 'LogOn', 'WflLogOn', 'LogOnRequest', 'AdmLogOn', 'PlnLogOn' );
		$expression = '';
		$replacement = '';
		if( in_array($methodName, $logonMethods) ) {
			if( $protocol == 'SOAP' ) {
				// e.g. <Password>xxx</Password> => <Password>***</Password>
				$expression = '/<Password>(.*)<\/Password>/is';
				$replacement = '<Password>***</Password>';
			} elseif( $protocol == 'Service' ) {
				// e.g. [Password] => xxx => [Password] => ***
				$expression = '/Password => (.*)\n(.*)/i';
				$replacement = "Password => ***\n$2";
			} elseif( $protocol == 'AMF' ) {
				// e.g. [Password] => xxx => [Password] => ***
				$expression = '/\[Password\] => (.*)\n(.*)/i';
				$replacement = "[Password] => ***\n$2";
			} elseif( $protocol == 'JSON' ) {
				// e.g. "Password":"xxx" => "Password":"***"
				$expression = '/\"Password\":\s*\"(.*)\"/i';
				$replacement = "\"Password\": \"***\"";
			}
		}
		if( $methodName == 'RabbitMQ_createOrUpdateUser' && $protocol == 'REST' ) {
			$expression = '/\"password\":\"(.*)\"/i';
			$replacement = "\"password\":\"***\"";
		}

		if( $expression ) {
			$count = 0;
			$transData = preg_replace( $expression, $replacement, $transData, 1, $count );
			if( $count == 0 ) {
				self::Log( 'Security', 'ERROR', $methodName.': Logged password is not obfuscated. This is a potential security risk when the log folder is accessed by unauthorized people.' );
			}
		}

		return $transData;
	}
}
