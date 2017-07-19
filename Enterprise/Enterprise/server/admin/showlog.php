<?php
/**
  * Shows the server log file or the log file that contains errors only.
  * The application is typically used for debugging purposes.
  */
require_once __DIR__.'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';

// This tool is for administrators only. Security is required to avoid reveiling information to hackers.
// In the exceptional case that the logon itself maybe erratic, the logging could be manually taken from disk.
checkSecure('admin');

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

	case 'rootfolderindex':
		$app = new WW_Admin_ShowLog();
		$app->showRootFolderIndex();
	break;

	case 'dailyfolderindex':
		$app = new WW_Admin_ShowLog();
		$app->showDailyFolderIndex();
	break;

	case 'clientipfolderindex':
		$app = new WW_Admin_ShowLog();
		$app->showClientIpFolderIndex();
	break;

	case 'clientiplogfile':
		$app = new WW_Admin_ShowLog();
		$app->showClientIpLogFile();

	break;
}

/**
 * Application that allows admin users to explore the server log files.
 *
 * @since 10.1.4
 * @todo Use a HTML template and make it an official (and localised) admin app under Advanced menu.
 * @todo Support ZIP+download an entire log folder.
 * @todo Make the hyperlinks work for the logfiles itself once downloaded to ease analysing on other machine.
 */
class WW_Admin_ShowLog
{
	/**
	 * Show a list of (daily) subfolders that can be found directly under the root log folder.
	 *
	 * Each folder is represented as a hyperlink to allow the admin user to step down one level deeper.
	 * @since 10.1.4
	 */
	public function showRootFolderIndex()
	{
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$page = '<h2>Server Logging</h2>';
		$page .= self::composeBreadcrumb().'<h3>Daily log folders</h3>';
		$page .= '<table><tbody>';
		$dailyFolders = LogHandler::listDailyRootFolders();
		if( $dailyFolders ) foreach( $dailyFolders as $dailyFolder ) {
			$url = 'showlog.php?act=dailyfolderindex&dailyfolder='.urlencode($dailyFolder);
			$page .= '<tr><td><a href="'.$url.'">'.$dailyFolder.'</a></td></tr>';
		}
		$page .= '</tbody></table>';
		print HtmlDocument::buildDocument( $page );
	}

	/**
	 * Show a list of (client ip) subfolders that can be found directly under the parental daily log folder.
	 *
	 * Each folder is represented as a hyperlink to allow the admin user to step down one level deeper.
	 * For each client ip, the user name and the client application name are resolved to help admin navigate.
	 * @since 10.1.4
	 */
	public function showDailyFolderIndex()
	{
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$dailyFolder = $_GET['dailyfolder'];
		$page = '<h2>Server Logging</h2>';
		$page .= self::composeBreadcrumb( $dailyFolder ).'<h3>Client IP log folders</h3>';
		$page .= '<table><thead><tr><td>Client IP</td><td>User</td><td>Application</td></tr></thead><tbody>';
		$clientIpFolders = LogHandler::listClientIpSubFolders( $dailyFolder );
		if( $clientIpFolders ) {
			$onlineUsers = self::resolveOnlineUsersFromClientIps( $clientIpFolders );
			foreach( $clientIpFolders as $clientIpFolder ) {
				$url = 'showlog.php?act=clientipfolderindex&dailyfolder='.urlencode($dailyFolder).
					'&clientipfolder='.urlencode($clientIpFolder);
				if( isset($onlineUsers[$clientIpFolder]) ) {
					foreach( $onlineUsers[$clientIpFolder] as $index => $onlineUser ) {
						if( $index == 0 ) {
							$page .= '<tr><td><a href="'.$url.'">'.$clientIpFolder.'</a></td>';
						} else {
							$page .= '<tr><td/>';
						}
						$page .=	'<td>'.formvar( $onlineUser['User'] ).'</td>'.
							'<td>'.formvar( $onlineUser['Client'] ).'</td></tr>';
					}
				} else {
					$page .= '<tr><td><a href="'.$url.'">'.$clientIpFolder.'</a></td><td colspan="3"/></tr>';
				}
			}
		}
		$page .= '</tbody></table>';
		print HtmlDocument::buildDocument( $page );
	}

	/**
	 * Show a list of log files that can be found directly under the parental client ip log folder.
	 *
	 * Each file is represented as a hyperlink to allow the admin user to inspect its content.
	 * @since 10.1.4
	 */
	public function showClientIpFolderIndex()
	{
		require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
		$dailyFolder = $_GET['dailyfolder'];
		$clientIpFolder = $_GET['clientipfolder'];
		$page = '<h2>Server Logging</h2>';
		$page .= self::composeBreadcrumb( $dailyFolder, $clientIpFolder ).'<h3>Client IP log files</h3>';
		$page .= '<table><tbody>';
		$logFiles = LogHandler::listLogFiles( $dailyFolder, $clientIpFolder );
		if( $logFiles ) foreach( $logFiles as $logFile ) {
			$url = 'showlog.php?act=clientiplogfile&dailyfolder='.urlencode( $dailyFolder ).
				'&clientipfolder='.urlencode( $clientIpFolder ).
				'&logfile='.urlencode( $logFile );
			$page .= '<tr><td><a href="'.$url.'">'.$logFile.'</a></td></tr>';
		}
		$page .= '</tbody></table>';
		print HtmlDocument::buildDocument( $page );
	}

	/**
	 * Output a logfile directly to web browser to allow admin user to inspect the server logging of a service request.
	 *
	 * @since 10.1.4
	 */
	public function showClientIpLogFile()
	{
		$dailyFolder = $_GET['dailyfolder'];
		$clientIpFolder = $_GET['clientipfolder'];
		$logFile = $_GET['logfile'];
		if( $logFile && $logFile[0] !== '.' &&
			strpos( $logFile, '..' ) === false &&
			strpbrk( $logFile, '\\/?*' ) === false ) {
			if( strrpos( $logFile, '.' ) ) {
				$logFileParts = explode( '.', $logFile );
				$fileExt = array_pop( $logFileParts );
				$fileExt = strtolower( $fileExt );
				switch( $fileExt ) {
					case 'txt':
						header( 'content-type: text/plain' );
						break;
					case 'xml':
						header( 'content-type: text/xml' );
						break;
					case 'htm':
						header( 'content-type: text/html' );
						break;
				}
			}
		}
		print LogHandler::getLogFileContent( $dailyFolder, $clientIpFolder, $logFile );
	}

	/**
	 * Composes a string with hyperlinks to let the user directly navigate to specific parental folders.
	 *
	 * @since 10.1.4
	 * @param string|null $dailyFolder
	 * @param string|null $clientIpFolder
	 * @return string HTML fragment with hyperlinks.
	 */
	public static function composeBreadcrumb( $dailyFolder = null, $clientIpFolder = null )
	{
		$baseUrl = 'showlog.php?act=rootfolderindex';
		$breadcrumb = '<a href="'.$baseUrl.'">'.formvar( '<root>' ).'</a>';

		if( $dailyFolder ) {
			$url = 'showlog.php?act=dailyfolderindex&dailyfolder='.urlencode( $dailyFolder );
			$breadcrumb .= ' / <a href="'.$url.'">'.$dailyFolder.'</a>';
		}

		if( $clientIpFolder ) {
			$url = 'showlog.php?act=clientipfolderindex&dailyfolder='.urlencode( $dailyFolder ).
				'&clientipfolder='.urlencode( $clientIpFolder );
			$breadcrumb .= ' / <a href="'.$url.'">'.$clientIpFolder.'</a>';
		}

		return '<p>Path: '.$breadcrumb.'</p>';
	}

	/**
	 * Resolves the user names and the client applications of online users, given a list of client IPs.
	 *
	 * @since 10.1.4
	 * @param string[] $clientIps
	 * @return array Online information with keys: Ticket, User and Client
	 */
	public static function resolveOnlineUsersFromClientIps( $clientIps )
	{
		if( !$clientIps ) {
			return array();
		}
		$dbDriver = DBDriverFactory::gen();
		$db = $dbDriver->tablename( "tickets" );
		$sql = "SELECT `ticketid`, `usr`, `clientip`, `appname`, `appversion` ".
			"FROM $db ".
			"WHERE `clientip` IN ('".implode( "','", $clientIps )."')";
		$sth = $dbDriver->query( $sql );
		if( !$sth ) {
			return array();
		}
		$onlineInfo = array();
		while( ( $row = $dbDriver->fetch( $sth ) ) ) {
			$onlineInfo[ $row['clientip'] ][] = array(
				'Ticket' => $row['ticketid'],
				'User' => $row['usr'],
				'Client' => $row['appname'].' '.$row['appversion'],
			);
		}
		return $onlineInfo;
	}
}