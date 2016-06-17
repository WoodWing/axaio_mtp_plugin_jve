<?php
	require_once dirname(__FILE__).'/../../../config/config.php';
	include_once( BASEDIR . '/server/utils/license/license.class.php' );

	global $sLanguage_code;
	if(!$sLanguage_code) {
		$sLanguage_code = 'enUS';
	}
	ob_start();

	$lic = new License();

	//If no license installed yet: everyone may install the first license (the SCE Server license)
	//Once a license has been installed, only admin users may do something here...
	$hasLicense = $lic->hasLicense();
	if ( $hasLicense ) {
		$SCEAppserial = $lic->getSerial( PRODUCTKEY );
		$info = array();
		$errMsg = '';
		$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $SCEAppserial, $info, $errMsg );
		//The user should only be an administrator if he can logon as an administrator
		//In case of an license error, he is not able to logon as administrator.
		if ( $licenseStatus <= WW_LICENSE_OK_MAX ) {
			require_once BASEDIR.'/server/secure.php';
			$ticket = checkSecure( 'admin' ); // Security: should be admin user
		}
	}
	
	if ( isset( $_POST[ 'license' ] ))
	{
		$serial = $_POST[ 'serial' ];
		$license = $_POST[ 'license' ];
		$productcode = $_POST[ 'productcode' ];
		$productname = $_POST[ 'productname' ];
		if ( $license && $serial && $productcode && $productname )
		{
			$license = trim( $license );
			$manual = 1;
			$errorMessage = '';
			
			$licenseStatus = $lic->installLicense( $productcode, $productname, $serial, $license, $manual, $errorMessage );
			if ( $licenseStatus === false )
			{
				print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
				print $errorMessage;
				print "<br><br><a href='javascript:history.go(-1)'>Back</a>";
				setlicense_buildDoc();
				exit;
			}

			if ( $licenseStatus == WW_LICENSE_OK )
			{
				print "<h2>" . BizResources::localize("LIC_LICENSE_STATUS") . "</h2>";
				print BizResources::localize("LIC_LICENSE_INSTALLED");
				print "<br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
			}
			else if ( $licenseStatus == WW_LICENSE_OK_USERLIMIT )
			{
				print BizResources::localize("LIC_LICENSE_INSTALLED_AND_LIMITREACHED");
				print "<br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
			}
			else
			{
				print "<h2>" . BizResources::localize("LIC_LICENSE_STATUS") . "</h2>";
				print "$licenseStatus";
				print "<br>" . $errorMessage;
				print "<br><br><a href='javascript:history.go(-1)'>Back</a>";
				print "<br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
			}
			setlicense_buildDoc();
			exit;
		}
	}
?>

	<h2>Install License</h2>
	<form method="POST" action="#">
		<br>Product code: <input name='productcode'>
		<br>Product name: <input name='productname'>
		<br>Serial: <input name='serial'>
		<br>License: <input name='license'>
		<br><input type='submit' value='Install'>
	</form>
	
	
<?php
setlicense_buildDoc();
function setlicense_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/secure.php';
	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
}

?>