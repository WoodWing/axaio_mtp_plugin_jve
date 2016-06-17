<?php

require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/utils/license/StealthInstaller.class.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/license/license.class.php';
require_once BASEDIR.'/server/regserver.inc.php';

// If no license installed yet: everyone may install the Enterprise Server license.
// Once a license has been installed, only admin users may do something here.
$lic = new License();
$hasLicense = $lic->hasLicense();
if ( $hasLicense ) {
	$appSerial = $lic->getSerial( PRODUCTKEY );
	$info = array();
	$errMsg = '';
	$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $appSerial, $info, $errMsg );

	// The user should only be an administrator if he can logon as an administrator
	// In case of an license error, he is not able to logon as administrator.
	if( $licenseStatus <= WW_LICENSE_OK_MAX ) {
		require_once BASEDIR.'/server/secure.php';
		$ticket = checkSecure( 'admin' ); // Security: should be admin user
	}
}
	
$txt = '<h2>Automated License Activation</h2>';
$msg = '';
$errMsg = '';
$installer = new WW_Utils_License_StealthInstaller();
if( $installer->canAutoActivate() ) {
	$mode = $_GET['mode'];
	switch( $mode ) {
		case 'activate':
			$installer->installProductLicenses(true);
			$msg = 'Licenses activated.';
			break;
		case 'deactivate':
			$installer->installProductLicenses(false);
			$msg = 'Licenses deactivated.';
			break;
		default:
			$installer = null;
			$errMsg = BizResources::localize( 'ERR_ARGUMENT' );
			LogHandler::Log( 'StealthInstaller', 'ERROR', 'Wrong HTTP param given: '.$mode );
			break;
	}
} else {
	$installer = null;
	$errMsg = 'No WWActivate.xml file found.';
}

if( $installer && !$errMsg ) {
	$errMsg = $installer->getError();
}
if( $errMsg ) {
	$error = BizResources::localize( 'ERR_GENERAL_ERROR' );
	$txt .= '<font color="red"><b>'.$error.':</b> '.$errMsg.'</font><br/><br/>';
} else {
	$txt .= $msg.'<br/><br/>';
}
$txt .= '<a href="index.php">License Status</a>';

require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
print HtmlDocument::buildDocument( $txt, true, null, false, true );
