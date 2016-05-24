<?php
/**
  * Shows the server log file or the log file that contains errors only.
  * The application is typically used for debugging perposes.
  */
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';

// Let's ignore check or else in error status you need to logon, but that fail because of error, etc, etc
//checkSecure( 'admin' ); 

switch( $_REQUEST['act'] ) {

	case 'errorsonly': // show special debug log file with errors only
		$errorFile = LogHandler::getDebugErrorLogFile();
		if( !empty($errorFile) ) {
			echo file_get_contents($errorFile);
		}
	break;

	case 'logfile': // show a given server log file
		$logFolder = LogHandler::getLogFolder();
		$logFile = $_REQUEST['file'];
		if( $logFolder && $logFile &&
			// anti-hack: block file paths...
			strpos( $logFile, '..' ) === false &&
			strpbrk( $logFile, '\\/?*' ) === false ) {
			
			// Set header base on get file extension.
			$fullPath = $logFolder.$logFile;
			$pieces = explode( '.', $fullPath );
			$extension = array_pop( $pieces );
			switch( $extension ) {
				case 'txt':
					header( 'content-type: text/plain' );
					break;
				case 'xml':
					header( 'content-type: text/xml' );
					break;
			}
			// Return whole log file to waiting web browser.
			echo file_get_contents( $fullPath );
		}
	break;

	case 'phplog': // show normal server log file
		$logFile = LogHandler::getPhpLogFile();
		if( !empty($logFile) ) {
			header( 'Content-type: text/plain' );
			$phpLog =  file_get_contents($logFile);
			echo str_replace( BASEDIR, '', $phpLog ); // let's remove long base paths to improve readability
		}
	break;

	case 'delerrors': // remove the special debug log file with errors only
		$errorFile = LogHandler::getDebugErrorLogFile();
		if( !empty($errorFile) ) {
			unlink($errorFile);
			// auto close window
			echo '<html><script language="javascript">window.close();</script></html>';
		}
	break;
		
	case 'delphplog': // remove the php error log file
		$errorFile = LogHandler::getPhpLogFile();
		if( !empty($errorFile) ) {
			unlink($errorFile);
			// auto close window
			echo '<html><script language="javascript">window.close();</script></html>';
		}
	break;
}
