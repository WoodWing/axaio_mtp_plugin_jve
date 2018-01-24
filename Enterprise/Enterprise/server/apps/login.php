<?php
require_once dirname( __FILE__ ).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php'; // set $sLanguage_code
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Guess language for login screen, in order of preference
// 1. language specified in URL, as a emergency workaround for users
// 2. language in cookie from previous sessions (already set by secure.php)
// 3. company wide default language (already searched in secure.php)
// 4. English as last resort (already defined in secure.php)
global $sLanguage_code;	// defined in secure.php
require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
$sUrlLanguage = isset( $_GET['language'] ) ? $_GET['language'] : '';
$sLanguage_code = BizUser::validUserLanguage( $sUrlLanguage );

$login = isset( $_POST['login'] ) ? $_POST['login'] : '';
$user = isset( $_POST['usr'] ) ? $_POST['usr'] : '';
$password = isset( $_POST['psswd'] ) ? $_POST['psswd'] : '';
$ticket = isset( $_POST['ticket'] ) ? $_POST['ticket'] : '';

$logout = isset( $_REQUEST['logout'] );
$parMessage = isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '';
$message    = '';

// Determine if this logon page was redirected by another page, or that this is the first time calling.
// When $redir=false this is the first time calling. When $redir=true, this is the first redirection.
// When $redir=-N this is the Nth time calling the logon page (e.g. badly typed named/password).
$redir = array_key_exists( 'redir', $_REQUEST ) ? $_REQUEST['redir'] : 'false';
$redir = ( $redir == 'true' ) ? -1 : $redir;

// LOGOUT
if( $logout ) {
	$ticket = checkSecure();
	try {
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		$service = new WflLogOffService();
		$service->execute( new WflLogOffRequest( $ticket, false, null, null ) );
		setLogCookie( 'ticket', '' );
	}
	catch( BizException $e ) {
		$message = $e->getMessage(); // . ': '. $e->getDetail(); // BZ#9890: No details for SQL injection attacks
	}
}

// LOGON
$loginsucceed = false;
$passExpired = false;
$userLimitAdmin = false;
$licenseAdmin = false;
if( !empty( $login ) && !empty( $user ) ) // && !empty($password))
{	
	require_once BASEDIR.'/server/utils/UrlUtils.php';
	$server		= 'Enterprise Server';
	$clientip = WW_Utils_UrlUtils::getClientIP();
	$clientname = isset( $_SERVER['REMOTE_HOST'] ) ? $_SERVER['REMOTE_HOST'] : '';
	if( !$clientname || ( $clientname == $clientip ) )
		$clientname = gethostbyaddr( $clientip );
	$domain		= '';
	$appname    = 'Web';
	$appversion	= 'v'.SERVERVERSION;
	$appserial	= ''; //Keep empty avoid concurrent license check (on application base)
	$appproductcode = '';

	$ini = ini_get( 'display_errors' ); // Make sure no errors/warns are sent output, or else setLogCookie fails later on!
	ini_set( 'display_errors', '0' );
	try {
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$service = new WflLogOnService();
		$ret = $service->execute( new WflLogOnRequest( $user, $password, '', $server, $clientname,  
										$domain, $appname, $appversion, $appserial, $appproductcode,
			null, array() ) ); // we only need the ticket (performance reason);
	    $ticket = $ret->Ticket;
		if( isset( $ret->Messages ) ) foreach( $ret->Messages as $m ) {
			if( !empty( $message ) ) {
	    		$message .= '\n';
	    	}
		    $message .= $m->Message;
		}

	    $loginsucceed = true;
	} catch( BizException $e ) {
		$message = $e->getMessage(); // . ': '. $e->getDetail(); // BZ#9890: No details for SQL injection attacks
		$key = $e->getMessageKey();
		if( $e->getDetail() == 'SCEntError_PasswordExpired' ) {
			$passExpired = true;
		}
		if( ( $key == 'WARN_USER_LIMIT' ) || ( $key == 'ERR_LICENSE' ) ) {
			//user should be the shortuser (spelled exactly as in the database)!!
			$isadmin = hasRights( DBDriverFactory::gen(), $user, $appname );
			if( $isadmin ) {
				if( $key == 'WARN_USER_LIMIT' ) {
					$userLimitAdmin = true; 
				} else if( $key == 'ERR_LICENSE' ) {
					$licenseAdmin = true; 
				}
			}
		}
	}
	ini_set( 'display_errors', $ini ); // Restore option
}

$errNoJavascript = '<noscript><br><font color="red">'.BizResources::localize( "ERR_NO_JAVASCRIPT_SUPPORT" ).'</font></noscript>';

if( !$loginsucceed ) {
	if( $passExpired ) {
		header( 'Location: password.php?userPwdExpir='.urlencode( $user ) );
		exit(); //After setting the header, always quit: don't send extra data to the browser
	}
	if( $userLimitAdmin || $licenseAdmin ) {
		//In case of error, there is no 'secure' admin session (the user can't logon!)
		//But these admin pages should be able to be fetched, 
		//and need to verify that only admin users are requesting them
		session_id( 'ww-userlimit-admin-session-id' );
		session_start();
		$_SESSION['adminUser'] = $user;
		$_SESSION['hash'] = md5( $user."bla" );
		$_SESSION['start'] = time();
	}
	if( $userLimitAdmin ) {
		header( 'Location: '.SERVERURL_ROOT.INETROOT.'/server/admin/license/admintickets.php?adminUser='.urlencode( $user ).'&'.'sessionId'.'='.session_id() );
		exit(); //After setting the header, always quit: don't send extra data to the browser
	}
	if( $licenseAdmin ) {
		header( 'Location: '.SERVERURL_ROOT.INETROOT.'/server/admin/license/index.php?adminUser='.urlencode( $user ).'&'.'sessionId'.'='.session_id() );
		exit(); //After setting the header, always quit: don't send extra data to the browser
	}
	$tpl = HtmlDocument::loadTemplate( 'login.htm' );
	
	$showDialogs = '';
	if( !empty( $login ) ) {
		if( empty( $user ) || empty( $password ) ) {
			if( empty( $user ) )
				$showDialogs = "<script language='javascript'>Required_U();</script>";
			if( empty( $password ) )
				$showDialogs = "<script language='javascript'>Required_P();</script>";
			if( empty( $user ) && empty( $password ) )
				$showDialogs = "<script language='javascript'>Required_UP();</script>";
		}
	} 
	if( empty( $showDialogs ) && !empty( $message ) ) {
		$message = str_replace( "'", "\\'", $message ); // Bugfix: Messages with single quotes are NOT shown at all!
		$showDialogs = "<script language='javascript'>Message('$message');</script>";
	}

	$tpl = str_replace( "<!--USERNAME-->", formvar( $user ), $tpl );

	$versionInfo = trim( SERVERVERSION.' '.SERVERVERSION_EXTRAINFO );
	$tpl = str_replace( "<!--VERSIONINFO-->", $versionInfo, $tpl );

	$messageList = '';
	// Running in debug mode?
	if( LogHandler::debugMode() ) {
		$messageList .= '<font size="2" color="#ff0000"><b>'.BizResources::localize('ACT_RUNNING_IN_DEBUG_MODE').'</b></font><br/>';
	}
	// Message param given?
	if( !empty( $parMessage ) ) {
		$messageList .= '<font size=2 color="#00aa00">'.formvar( $parMessage ).'</font><br/>';
	}
	$tpl = str_replace( '<!--MESSAGE-->', $messageList, $tpl );
	$redir--; // remember each post/submit to jump back to initiator page at once (see else part below)
	$tpl = str_replace( '<!--PAR:REDIR-->', formvar( $redir ), $tpl );
	$tpl .= "<script language='javascript'>document.forms[0].usr.focus();</script>";
	$tpl .= $showDialogs;
	$tpl .= "\n".$errNoJavascript;
	print HtmlDocument::buildDocument( $tpl, false );
}
else	// is succeeded
{
	$shortusername = BizSession::getShortUserName();
	// homepage for successful logon
	$isadmin = hasRights( DBDriverFactory::gen(), $shortusername, 'Web' );
	$ispubladmin = publRights( DBDriverFactory::gen(), $shortusername );
	if( $isadmin || $ispubladmin ) { // admin user
		define( 'STARTHTM', '../admin/index.php' );
	} else { // normal user
		define( 'STARTHTM', '../apps/index.php' );
	}
	
	$sLanguage_code = BizSession::getUserLanguage();
	setLogCookie( "language", $sLanguage_code ); // remember for next requests
	setLogCookie( "ticket", $ticket );
	setLogCookie( 'debuglevel', LogHandler::getDebugLevel() );

	// Bug: IIS5 needs a body when setting a cookie.
	//      If there is no body, the cookie will be ignored.
	//      That's why we don't use: header("Location: ".STARTHTM);
	print '<html><body>';
	if( $message ) {
		$message = str_replace( "'", "\\'", $message ); // Bugfix: Messages with single quotes are NOT shown at all!
		print '<script language="javascript">alert("'.$message.'");</script>';
	}
	if( $redir != 'false' ) { // Logon originated from other page? Let's go back then...
		print '<script language="javascript">top.location.replace("'.STARTHTM.'");</script>';
		// Rolled back since I can not get this bullet-proof: logoff, start new tab, goto Google, then to browse page, logon (redir) -> goes back to Google instead of browse page
		//print '<script language="javascript">if( window.history.length > 2 ) { history.go('.$redir.'); } else { top.location.replace("'.STARTHTM.'"); }</script>';
	} else { // Initial logon? Let's start at the index page...
		print '<script language="javascript">top.location.replace("'.STARTHTM.'");</script>';
	}
	print $errNoJavascript;
	print '</body></html>';
}
