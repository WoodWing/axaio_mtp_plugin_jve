<?php

require_once dirname( __FILE__ ).'/../../../config/config.php';
include_once( BASEDIR.'/server/utils/license/license.class.php' );
require_once BASEDIR.'/server/secure.php';

ob_start();

$showStatus = false;
$lic = new License();

// If no license installed yet: everyone may install the Enterprise Server license.
// Once a license has been installed, only admin users may do something here.
$hasLicense = $lic->hasLicense();
if( $hasLicense ) { // One or more licenses installed?
	$someFatalError = false;
	$wwTested = false;
	$wwTestResult = false;
	$errorMessage = '';
	$help = '';
	$warn = '';
	$extended = false;
	$errMsg = '';

	$appSerial = $lic->getSerial( PRODUCTKEY );
	if( $appSerial === false ) {
		// No license installed, or error read/writing the license data.
		$someFatalError = true;
	} else {
		$info = array();
		$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $appSerial, $info, $errMsg );

		// The user should only be an administrator if he can logon as an administrator.
		// In case of an license error, he is not able to logon as administrator.
		if( $licenseStatus <= WW_LICENSE_OK_MAX ) {
			// Although the status can be OK, Logon may fail in case of a write access problem.
			// wwTestWritable() can check that.
			$wwTestResult = $lic->wwTestWritable();
			$wwTested = true;
			if( !$wwTestResult ) {
				$someFatalError = true;
			}
		}

		if( !$someFatalError ) {
			if( $licenseStatus <= WW_LICENSE_OK_MAX ) {
				// License OK, then the admin user should be able to logon normally.
				require_once BASEDIR.'/server/secure.php';
				$ticket = checkSecure( 'admin' ); // Security: should be admin user
				$showStatus = true;
			} else {
				// License not OK, then the admin user is not able to logon normally.
				// Then verify whether this is a valid admin user via the session!
				require_once BASEDIR.'/server/utils/HttpRequest.class.php';
				$requestParams = WW_Utils_HttpRequest::getHttpParams( 'GP' );
				$user = isset( $requestParams['adminUser'] ) ? $requestParams['adminUser'] : '';
				// Admin user should always logon AFTER the max usage limit has been reached.
				// If necessary he should first logoff.
				// By logging on, the _install_ user will be removed from the tickets table, 
				// and his lastlogon timestamp will be set!
				if( $user ) {
					$sessionName = isset( $requestParams['sessionName'] ) ? $requestParams['sessionName'] : '';
					session_name( $sessionName );
					session_start();
					$adminUser = $_SESSION['adminUser'];
					$hash = $_SESSION['hash'];
					$myhash = md5( $user."bla" );
					if( $user == $adminUser && $hash == $myhash ) {
						$showStatus = true;
					}
				}
			}
		}
	}

	// In case of fatal error, showing the status overview makes no sense.
	// Show the fatal error.
	if( $someFatalError ) {
		if( !$wwTested ) {
			$wwTestResult = $lic->wwTest( $errorMessage, $help, $warn, $extended );
		}
		if( !$wwTestResult || $errMsg ) {
			print '<h1>'.BizResources::localize( 'ERR_LICENSE' ).'</h1>';
		}
		if( $errMsg ) {
			print $errMsg;
			print '<br/>';
		}
		if( !$wwTestResult ) {
			print BizResources::localize( 'LIC_ERR_SCENT_INSTALL' );
			print '<br/><br/><a href="'.SERVERURL_ROOT.INETROOT.'/server/wwtest/index.htm">wwtest</a>';
		}
		if( !$errMsg && $wwTestResult && !$appSerial ) {
			$showStatus = true;
		}
	}
} else {
	$showStatus = true;
}
if( $showStatus ) {
	$lic->showStatusInHTML();
}
$txt = ob_get_contents();
ob_end_clean();

require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$txt = HtmlDocument::buildDocument( $txt, true, null, false, true );
print $txt;
